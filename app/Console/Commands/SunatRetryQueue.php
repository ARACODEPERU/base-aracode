<?php

namespace App\Console\Commands;

use App\Helpers\Invoice\Documents\Boleta;
use App\Helpers\Invoice\Documents\Factura;
use App\Helpers\Invoice\Documents\NotaCredito;
use App\Helpers\Invoice\Documents\NotaDebito;
use App\Helpers\Invoice\Sunat\SunatResilience;
use App\Models\SaleDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SunatRetryQueue extends Command
{
    /** Tras este número de intentos el comprobante pasa a revisión manual. */
    public const MAX_ATTEMPTS = 12;

    protected $signature = 'sunat:retry-queue
                            {--limit=25 : Máximo de comprobantes a procesar}
                            {--dry-run : Solo muestra los comprobantes que se reenviarían}';

    protected $description = 'Reenvía a SUNAT los comprobantes retenidos por fallas de red (cola de contingencia).';

    public function handle(): int
    {
        if (! Schema::hasColumn('sale_documents', 'sunat_send_status')) {
            $this->warn('La cola de SUNAT no está disponible: ejecuta "php artisan migrate".');

            return self::SUCCESS;
        }

        $documents = SaleDocument::query()
            ->where('sunat_send_status', 'reintento')
            ->where(function ($query) {
                $query->whereNull('sunat_next_retry_at')
                    ->orWhere('sunat_next_retry_at', '<=', now());
            })
            ->orderBy('id')
            ->limit(max(1, (int) $this->option('limit')))
            ->get();

        if ($documents->isEmpty()) {
            $this->info('No hay comprobantes pendientes de reenvío.');

            return self::SUCCESS;
        }

        $sent = 0;
        $queued = 0;
        $manual = 0;

        foreach ($documents as $document) {
            $label = trim($document->invoice_serie.'-'.$document->invoice_correlative)
                .' (tipo '.$document->invoice_type_doc.')';

            if ((int) ($document->sunat_retry_attempts ?? 0) >= self::MAX_ATTEMPTS) {
                $document->forceFill(['sunat_send_status' => 'contingencia'])->save();
                $this->warn($label.': superó '.self::MAX_ATTEMPTS.' intentos, queda para revisión manual.');
                $manual++;

                continue;
            }

            if ($this->option('dry-run')) {
                $this->line('[dry-run] '.$label.' intentos: '.(int) ($document->sunat_retry_attempts ?? 0));

                continue;
            }

            try {
                $result = $this->resend($document);
            } catch (Throwable $e) {
                // Error inesperado: se reprograma y no se aborta el lote.
                $this->reprogram($document, $e->getMessage());
                $this->error($label.': '.$e->getMessage());
                $queued++;

                continue;
            }

            if (! empty($result['success'])) {
                $sent++;
                $this->info($label.': enviado correctamente.');
            } elseif (! empty($result['queued'])) {
                $queued++;
                $this->warn($label.': SUNAT sigue sin responder, se reintentará.');
            } else {
                $manual++;
                $this->error($label.': rechazado por SUNAT: '.($result['message'] ?? 'sin detalle'));
            }
        }

        $this->info("Resumen: {$sent} enviados, {$queued} en cola, {$manual} con revisión manual.");

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function resend(SaleDocument $document): array
    {
        return match ((string) $document->invoice_type_doc) {
            '01' => (new Factura)->create($document->id),
            '03' => (new Boleta)->create($document->id),
            '07' => (new NotaCredito)->create($document),
            '08' => (new NotaDebito)->create($document),
            default => throw new \RuntimeException('Tipo de comprobante no soportado: '.$document->invoice_type_doc),
        };
    }

    private function reprogram(SaleDocument $document, string $error): void
    {
        $resilience = app(SunatResilience::class);

        if (! $resilience->supportsQueueColumns()) {
            return;
        }

        $attempts = ((int) ($document->sunat_retry_attempts ?? 0)) + 1;

        $document->forceFill([
            'sunat_send_status' => 'reintento',
            'sunat_retry_attempts' => $attempts,
            'sunat_last_attempt_at' => now(),
            'sunat_next_retry_at' => now()->addMinutes($resilience->backoffMinutes($attempts)),
            'sunat_last_error' => mb_substr($error, 0, 2000),
        ])->save();
    }
}
