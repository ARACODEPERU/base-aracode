<?php

namespace App\Helpers\Invoice\Sunat;

use App\Helpers\Invoice\Documents\LowCommunication;
use App\Helpers\Invoice\Util;
use App\Models\SaleDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Sales\Entities\SaleLowCommunication;
use Modules\Sales\Entities\SaleSummary;
use Modules\Sales\Entities\SaleSummaryDetail;
use Throwable;

/**
 * Consulta asincrona de tickets de SUNAT (resumenes diarios y comunicaciones
 * de baja) y actualizacion segura del estado en base de datos.
 *
 * Usa las clases nativas de Greenter: `See::getStatus($ticket)` devuelve un
 * `StatusResult` con el `CdrResponse` (codigo 0 = aceptado) o un `Error`
 * (98/0098 = SUNAT todavia procesando, 0127 = ticket inexistente).
 *
 * Regla de seguridad: solo se escribe un estado definitivo cuando SUNAT da una
 * respuesta concluyente. Ante un error de red el registro conserva su estado y
 * se puede volver a consultar con el mismo ticket.
 */
class SunatTicketQuery
{
    public const ACEPTADO = 'ACEPTADO';

    public const RECHAZADO = 'RECHAZADO';

    public const EN_PROCESO = 'EN_PROCESO';

    public const NO_EXISTE = 'NO_EXISTE';

    public const ERROR = 'ERROR';

    private ?Util $util;

    private SunatFileStorage $files;

    private SunatResilience $resilience;

    /** Cache de columnas de trazabilidad de bajas (despliegues sin migrar). */
    private ?bool $supportsLowColumns = null;

    public function __construct(?Util $util = null, ?SunatFileStorage $files = null, ?SunatResilience $resilience = null)
    {
        $this->util = $util;
        $this->files = $files ?? app(SunatFileStorage::class);
        $this->resilience = $resilience ?? app(SunatResilience::class);
    }

    /**
     * Consulta el ticket en SUNAT y devuelve el estado normalizado.
     *
     * @return array<string, mixed>
     */
    public function query(string $ticket): array
    {
        $ticket = trim($ticket);

        if ($ticket === '') {
            return $this->normalized(null, 'El ticket está vacío: no hay nada que consultar en SUNAT.');
        }

        try {
            $util = $this->util ?? Util::getInstance();
            $response = $util->getSee()->getStatus($ticket);
        } catch (Throwable $e) {
            return $this->normalized(null, 'No se pudo consultar el ticket '.$ticket.': '.$e->getMessage(), [
                'retryable' => true,
                'technical' => mb_substr(get_class($e).': '.$e->getMessage(), 0, 2000),
            ]);
        }

        return $this->normalize($response, $ticket);
    }

    /**
     * Traduce la respuesta de Greenter (`StatusResult`) a un estado estable.
     * Es una funcion pura: no toca base de datos ni red.
     *
     * @return array<string, mixed>
     */
    public function normalize(object $response, string $ticket = '', array $extra = []): array
    {
        $isSuccess = method_exists($response, 'isSuccess') && (bool) $response->isSuccess();

        if (! $isSuccess) {
            $error = method_exists($response, 'getError') ? $response->getError() : null;
            $code = $error && method_exists($error, 'getCode') ? (string) $error->getCode() : null;
            $message = $error && method_exists($error, 'getMessage')
                ? (string) $error->getMessage()
                : 'SUNAT no devolvio el detalle del ticket.';

            $digits = $code === null ? '' : ltrim($code, '0');
            $digits = $digits === '' ? ($code === '0' ? '0' : $digits) : $digits;

            $isProcessing = in_array($digits, ['98'], true)
                || stripos($message, 'no ha terminado') !== false
                || stripos($message, 'en proceso') !== false
                || stripos($message, 'procesamiento') !== false;

            $notFound = $digits === '127' || stripos($message, 'ticket no existe') !== false;

            $state = $isProcessing ? self::EN_PROCESO : ($notFound ? self::NO_EXISTE : self::ERROR);

            return $this->normalized($error, $message, array_merge([
                'ticket' => $ticket,
                'state' => $state,
                'code' => $code,
                'retryable' => ! $notFound,
            ], $extra));
        }

        $cdr = method_exists($response, 'getCdrResponse') ? $response->getCdrResponse() : null;

        if (! $cdr) {
            // SUNAT recibio la consulta pero aun no genera la constancia.
            return $this->normalized(null, 'SUNAT todavía no devuelve el CDR del ticket '.$ticket.'. Vuelve a consultar más tarde.', [
                'ticket' => $ticket,
                'state' => self::EN_PROCESO,
                'retryable' => true,
            ]);
        }

        $cdrCode = method_exists($cdr, 'getCode') ? trim((string) $cdr->getCode()) : '';
        $notes = method_exists($cdr, 'getNotes') ? (array) ($cdr->getNotes() ?? []) : [];
        $cdrZip = null;

        try {
            if (method_exists($response, 'getCdrZip')) {
                $cdrZip = $response->getCdrZip();
            }
        } catch (Throwable $e) {
            $cdrZip = null;
        }

        $description = method_exists($cdr, 'getDescription') ? (string) $cdr->getDescription() : '';

        return $this->normalized(null, $description, array_merge([
            'ticket' => $ticket,
            'state' => $cdrCode === '0' ? self::ACEPTADO : self::RECHAZADO,
            'code' => $cdrCode,
            'notes' => $notes,
            'cdr' => $cdr,
            'cdr_zip' => is_string($cdrZip) && $cdrZip !== '' ? $cdrZip : null,
            'cdr_code' => $cdrCode,
            'cdr_description' => $description !== '' ? $description : null,
            'retryable' => false,
        ], $extra));
    }

    /**
     * Aplica el resultado de la consulta a un resumen diario.
     *
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    public function applyToSummary(SaleSummary $summary, array $result): array
    {
        $state = $result['state'] ?? self::ERROR;
        $ticket = (string) ($result['ticket'] ?? $summary->ticket);

        // Error de red / SUNAT no disponible: no se toca el estado actual,
        // para no perder un 'Enviado' que todavia puede resolverse.
        if (in_array($state, [self::ERROR], true)) {
            return array_merge($result, ['updated' => false]);
        }

        if ($state === self::NO_EXISTE) {
            $summary->forceFill([
                'response_code' => '0127',
                'response_description' => 'El ticket no existe en SUNAT. Genera un nuevo resumen diario.',
            ])->save();

            return array_merge($result, ['updated' => true]);
        }

        if ($state === self::EN_PROCESO) {
            $summary->forceFill([
                'response_code' => $result['code'] ?? $summary->response_code,
                'response_description' => 'SUNAT sigue procesando el resumen. No es un error; vuelve a consultar más tarde.',
                'status' => in_array($summary->status, [null, '', 'registrado'], true) ? 'Enviado' : $summary->status,
            ])->save();

            return array_merge($result, ['updated' => true]);
        }

        // ACEPTADO / RECHAZADO: se guarda la constancia antes de tocar la BD.
        $stored = $this->files->storeCdr($summary->summary_name ?: 'resumen-'.$summary->id, $result['cdr_zip'] ?? null);

        $updated = DB::transaction(function () use ($summary, $result, $state, $ticket, $stored) {
            $summary->forceFill([
                'status' => $state === self::ACEPTADO ? 'Aceptado' : 'Rechazado',
                'response_code' => mb_substr((string) ($result['cdr_code'] ?? $result['code'] ?? ''), 0, 10),
                'response_description' => ($result['cdr_description'] ?? null) ?: ($result['message'] ?? ''),
                'notes' => ! empty($result['notes']) ? json_encode($result['notes'], JSON_UNESCAPED_UNICODE) : $summary->notes,
            ]);

            if ($stored['zip'] !== null) {
                $summary->cdr = $stored['zip'];
            }

            $summary->save();

            // Solo un CDR aceptado marca los comprobantes del resumen.
            if ($state === self::ACEPTADO) {
                $ids = SaleSummaryDetail::where('summary_id', $summary->id)->pluck('document_id');

                if ($ids->isNotEmpty()) {
                    SaleDocument::whereIn('id', $ids)
                        ->where('invoice_status', '<>', 'Anulada')
                        ->update([
                            'invoice_status' => 'Aceptada',
                            'invoice_response_code' => 0,
                            'invoice_response_description' => 'Enviado en resumen '.$summary->summary_name.' Número ticket: '.$ticket,
                        ]);
                }
            }

            return true;
        });

        return array_merge($result, [
            'updated' => $updated,
            'cdr_path' => $stored['zip'],
            'cdr_xml_path' => $stored['xml'],
        ]);
    }

    /**
     * Aplica el resultado de la consulta a una comunicación de baja.
     *
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    public function applyToLowCommunication(SaleLowCommunication $low, array $result): array
    {
        $state = $result['state'] ?? self::ERROR;
        $ticket = (string) ($result['ticket'] ?? $low->ticket);

        if ($state === self::ERROR) {
            return array_merge($result, ['updated' => false]);
        }

        if ($state === self::NO_EXISTE) {
            $this->saveLow($low, [
                'response_code' => '0127',
                'response_description' => 'El ticket no existe en SUNAT. Genera una nueva comunicación de baja.',
            ]);

            return array_merge($result, ['updated' => true]);
        }

        if ($state === self::EN_PROCESO) {
            $this->saveLow($low, [
                'response_code' => $result['code'] ?? $low->response_code,
                'response_description' => 'SUNAT sigue procesando la baja. No es un error; vuelve a consultar más tarde.',
                'status' => in_array($low->status, [null, '', 'registrado'], true) ? 'Enviado' : $low->status,
                'sunat_result' => 'en_proceso',
            ]);

            return array_merge($result, ['updated' => true]);
        }

        $stored = $this->files->storeCdr($low->communication_name ?: 'baja-'.$low->id, $result['cdr_zip'] ?? null);

        $updated = DB::transaction(function () use ($low, $result, $state, $ticket, $stored) {
            $accepted = $state === self::ACEPTADO;
            $notes = ! empty($result['notes']) ? json_encode($result['notes'], JSON_UNESCAPED_UNICODE) : $low->notes;

            $this->saveLow($low, [
                'status' => $accepted ? 'Aceptado' : 'Rechazado',
                'response_code' => mb_substr((string) ($result['cdr_code'] ?? $result['code'] ?? ''), 0, 10),
                'response_description' => ($result['cdr_description'] ?? null) ?: ($result['message'] ?? ''),
                'notes' => $notes,
                'cdr' => $stored['zip'] ?: $low->cdr,
                'cdr_code' => $result['cdr_code'] ?? null,
                'cdr_description' => $result['cdr_description'] ?? null,
                'sunat_result' => $accepted ? 'aceptado' : 'rechazado',
                'last_checked_at' => now(),
            ]);

            // Solo se anulan los comprobantes cuando SUNAT acepta la baja.
            if ($accepted) {
                app(LowCommunication::class)->annulDocuments($low->id, $low->communication_name, $ticket);
            }

            return true;
        });

        return array_merge($result, [
            'updated' => $updated,
            'cdr_path' => $stored['zip'],
            'cdr_xml_path' => $stored['xml'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function saveLow(SaleLowCommunication $low, array $attributes): void
    {
        if (! $this->supportsLowColumns()) {
            unset($attributes['sunat_result'], $attributes['cdr_code'], $attributes['cdr_description'], $attributes['last_checked_at']);
        }

        $low->forceFill($attributes);
        $low->save();
    }

    private function supportsLowColumns(): bool
    {
        if ($this->supportsLowColumns === null) {
            try {
                $this->supportsLowColumns = Schema::hasColumn('sale_low_communications', 'sunat_result');
            } catch (Throwable $e) {
                $this->supportsLowColumns = false;
            }
        }

        return $this->supportsLowColumns;
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function normalized(?object $error, string $message, array $extra = []): array
    {
        return array_merge([
            'ticket' => null,
            'state' => self::ERROR,
            'code' => null,
            'message' => $message,
            'notes' => [],
            'cdr' => null,
            'cdr_zip' => null,
            'cdr_code' => null,
            'cdr_description' => null,
            'retryable' => false,
            'technical' => null,
        ], $extra, ['message' => $extra['message'] ?? $message]);
    }
}
