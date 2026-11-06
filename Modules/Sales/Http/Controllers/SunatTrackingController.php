<?php

namespace Modules\Sales\Http\Controllers;

use App\Helpers\Invoice\Sunat\SunatFileStorage;
use App\Helpers\Invoice\Sunat\SunatTicketQuery;
use App\Models\SaleDocument;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Sales\Entities\SaleLowCommunication;
use Modules\Sales\Entities\SaleSummary;
use Symfony\Component\HttpFoundation\Response;

/**
 * Punto de entrada unico para la consulta de tickets y la descarga de los
 * archivos SUNAT (XML firmado, ZIP del CDR y XML extraido del CDR) de
 * comprobantes, resumenes diarios y comunicaciones de baja.
 */
class SunatTrackingController extends Controller
{
    public function __construct(
        private readonly SunatTicketQuery $tickets,
        private readonly SunatFileStorage $files,
    ) {
    }

    /**
     * Consulta el ticket y actualiza el estado del documento.
     */
    public function check(string $entity, int $id)
    {
        $record = $this->resolveRecord($entity, $id);

        if ($record instanceof Response) {
            return $record;
        }

        [$model, $type] = $record;

        if (empty($model->ticket)) {
            return response()->json([
                'success' => false,
                'state' => SunatTicketQuery::ERROR,
                'message' => 'El documento todavía no tiene ticket de SUNAT. Envíalo primero.',
            ], 422);
        }

        $result = $this->tickets->query($model->ticket);

        $result = $type === 'summary'
            ? $this->tickets->applyToSummary($model, $result)
            : $this->tickets->applyToLowCommunication($model, $result);

        return response()->json([
            'success' => in_array($result['state'], [SunatTicketQuery::ACEPTADO, SunatTicketQuery::RECHAZADO], true),
            'state' => $result['state'],
            'code' => $result['code'],
            'message' => $result['message'],
            'notes' => $result['notes'] ?? [],
            'ticket' => $result['ticket'],
            'updated' => $result['updated'] ?? false,
            'retryable' => $result['retryable'] ?? false,
            'has_cdr' => ! empty($result['cdr_path']),
            'has_cdr_xml' => ! empty($result['cdr_xml_path']),
        ]);
    }

    /**
     * Descarga el XML firmado ('xml'), el ZIP del CDR ('cdr') o el XML
     * descomprimido del CDR ('cdr-xml') de cualquier documento SUNAT.
     */
    public function download(string $entity, int $id, string $type)
    {
        $record = $this->resolveRecord($entity, $id);

        if ($record instanceof Response) {
            return $record;
        }

        [$model] = $record;
        $type = strtolower($type);

        $base = $this->fileName($model);
        $stored = match ($type) {
            'xml' => $model->xml ?? $model->invoice_xml ?? null,
            'cdr' => $model->cdr ?? $model->invoice_cdr ?? null,
            'cdr-xml' => $this->files->cdrXmlPath($model->cdr ?? $model->invoice_cdr ?? null),
            default => null,
        };

        if ($type === 'cdr-xml' && $stored === null) {
            // El XML del CDR puede faltar en registros viejos: se extrae al vuelo.
            $contents = $this->files->cdrContents($model->cdr ?? $model->invoice_cdr ?? null);
            $stored = $contents['xml'] ?? null;

            if ($stored === null) {
                return $this->missing('El XML del CDR no está disponible. Vuelve a consultar el ticket en SUNAT.');
            }

            return response($stored, 200, [
                'Content-Type' => 'application/xml; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="R-'.$base.'.xml"',
            ]);
        }

        if ($stored === null) {
            return $this->missing($type === 'xml'
                ? 'El XML de este documento todavía no está disponible.'
                : 'SUNAT todavía no devolvió el CDR de este documento. Consulta el ticket para reintentar la descarga.');
        }

        $absolute = $this->files->absolutePath($stored);

        if ($absolute === null) {
            return $this->missing('El archivo ya no existe en el almacenamiento. Vuelve a enviar o consultar el documento.');
        }

        return match ($type) {
            'xml' => response()->download($absolute, $base.'.xml', ['content-type' => 'application/xml']),
            'cdr' => response()->download($absolute, $base.'.zip', ['content-type' => 'application/zip']),
        };
    }

    /**
     * @return array{0: object, 1: string}|Response
     */
    private function resolveRecord(string $entity, int $id)
    {
        $entity = strtolower($entity);

        $model = match ($entity) {
            'document', 'documents', 'comprobante' => SaleDocument::find($id),
            'summary', 'resumen' => SaleSummary::find($id),
            'lowcommunication', 'baja' => SaleLowCommunication::find($id),
            default => null,
        };

        if ($model === null) {
            return $this->missing('No se encontró el documento SUNAT solicitado.', 404);
        }

        return [$model, $entity === 'summary' || $entity === 'resumen' ? 'summary' : 'low'];
    }

    private function fileName(object $model): string
    {
        foreach (['summary_name', 'communication_name', 'invoice_document_name'] as $attribute) {
            $value = $model->{$attribute} ?? null;

            if (is_string($value) && trim($value) !== '') {
                return $this->files->baseName($value);
            }
        }

        if (isset($model->invoice_serie, $model->invoice_correlative)) {
            return $model->invoice_serie.'-'.$model->invoice_correlative;
        }

        return 'sunat-'.$model->getKey();
    }

    private function missing(string $message, int $status = 404): Response
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
