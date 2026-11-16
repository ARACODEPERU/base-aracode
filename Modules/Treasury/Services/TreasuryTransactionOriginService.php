<?php

namespace Modules\Treasury\Services;

use App\Models\Sale;
use App\Models\SaleDocument;
use Modules\Sales\Entities\SaleDocumentQuota;
use Modules\Sales\Entities\SalePaymentQuota;
use Modules\Sales\Entities\SalePaymentSchedule;
use Modules\Sales\Entities\SalePaymentScheduleDestination;
use Modules\Treasury\Entities\TreasurySourceMovement;

/**
 * Traduce el origen de un movimiento del libro (la fila puente que guarda
 * módulo, entidad y operación) a datos legibles para la pantalla: comprobante,
 * cliente, número de cuota, vencimiento, curso/suscripción y método de pago.
 *
 * Solo lee; nunca escribe ni modifica el libro.
 */
class TreasuryTransactionOriginService
{
    /**
     * Detalle por id de transacción del libro.
     *
     * @param  array<int, int|string>  $transactionIds
     * @return array<int, array<string, mixed>>
     */
    public function detailsFor(array $transactionIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $transactionIds))));

        if ($ids === []) {
            return [];
        }

        $bridges = TreasurySourceMovement::query()
            ->whereIn('treasury_transaction_id', $ids)
            ->whereNull('reversal_of_id')
            ->get()
            ->keyBy('treasury_transaction_id');

        $details = [];

        foreach ($bridges as $transactionId => $bridge) {
            $detail = $this->detail($bridge);

            if ($detail !== []) {
                $details[(int) $transactionId] = $detail;
            }
        }

        return $details;
    }

    /**
     * Detalle legible de un movimiento de origen; vacío si no se reconoce.
     *
     * @return array<string, mixed>
     */
    public function detail(TreasurySourceMovement $bridge): array
    {
        return match ($bridge->source_type) {
            TreasurySourceMovement::SOURCE_SALE_PAYMENT_QUOTA => $this->quotaDetail($bridge),
            TreasurySourceMovement::SOURCE_SALE_SCHEDULE_DESTINATION => $this->scheduleDetail($bridge),
            TreasurySourceMovement::SOURCE_SALE_DOCUMENT => $this->documentDetail($bridge),
            TreasurySourceMovement::SOURCE_SALE => $this->saleDetail($bridge),
            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function quotaDetail(TreasurySourceMovement $bridge): array
    {
        $payment = SalePaymentQuota::find((int) $bridge->source_id);
        $quota = $payment?->sale_document_quota_id
            ? SaleDocumentQuota::find($payment->sale_document_quota_id)
            : null;
        $document = $quota?->sale_document_id ? SaleDocument::find($quota->sale_document_id) : null;
        $totalQuotas = $quota
            ? SaleDocumentQuota::where('sale_document_id', $quota->sale_document_id)->count()
            : null;

        return $this->build(
            tipo: $quota?->quota_number
                ? 'Cuota ' . $quota->quota_number . ($totalQuotas ? ' de ' . $totalQuotas : '')
                : 'Abono de cuota',
            document: $document,
            dueDate: $quota?->due_date,
            bridge: $bridge,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function scheduleDetail(TreasurySourceMovement $bridge): array
    {
        $destination = SalePaymentScheduleDestination::find((int) $bridge->source_id);
        $schedule = $destination?->schedule_id
            ? SalePaymentSchedule::find($destination->schedule_id)
            : null;

        $document = $destination?->document_id
            ? SaleDocument::find($destination->document_id)
            : ($schedule?->document_id ? SaleDocument::find($schedule->document_id) : null);

        $totalInstallments = $schedule
            ? SalePaymentSchedule::where('sale_id', $schedule->sale_id)->count()
            : null;

        return $this->build(
            tipo: $schedule?->installment_number
                ? 'Cuota ' . $schedule->installment_number . ($totalInstallments ? ' de ' . $totalInstallments : '')
                : 'Cobro de cronograma',
            document: $document,
            dueDate: $schedule?->payment_date,
            bridge: $bridge,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function documentDetail(TreasurySourceMovement $bridge): array
    {
        $document = SaleDocument::find((int) $bridge->source_id);

        $tipo = match ($bridge->operation) {
            'down_payment' => 'Cuota inicial',
            'full_payment' => 'Pago total',
            'manual_income' => 'Cobro vinculado',
            default => 'Cobro de comprobante',
        };

        return $this->build(
            tipo: $tipo,
            document: $document,
            dueDate: $document?->invoice_due_date,
            bridge: $bridge,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function saleDetail(TreasurySourceMovement $bridge): array
    {
        $sale = Sale::find((int) $bridge->source_id);
        $document = $sale?->documents()->orderByDesc('id')->first();

        return $this->build(
            tipo: 'Cobro de venta',
            document: $document,
            dueDate: $document?->invoice_due_date,
            bridge: $bridge,
        );
    }

    /**
     * Arma la estructura que consume la vista.
     *
     * @return array<string, mixed>
     */
    private function build(
        string $tipo,
        ?SaleDocument $document,
        mixed $dueDate,
        TreasurySourceMovement $bridge,
    ): array {
        $document?->loadMissing('items');

        return [
            'tipo' => $tipo,
            'comprobante' => $this->documentNumber($document),
            'comprobante_tipo' => $this->documentTypeName($document),
            'cliente' => $document?->client_rzn_social,
            'vencimiento' => $this->formatDate($dueDate),
            'concepto' => $this->concept($document),
            'metodo' => $bridge->paymentMethod?->description,
            'referencia' => $bridge->reference,
        ];
    }

    private function documentNumber(?SaleDocument $document): ?string
    {
        if (! $document) {
            return null;
        }

        $serie = trim((string) $document->invoice_serie);
        $correlativo = trim((string) $document->invoice_correlative);

        return $serie !== '' && $correlativo !== '' ? $serie . '-' . $correlativo : null;
    }

    private function documentTypeName(?SaleDocument $document): ?string
    {
        return match ((string) $document?->invoice_type_doc) {
            '01' => 'Factura',
            '03' => 'Boleta',
            '07' => 'Nota de crédito',
            '08' => 'Nota de débito',
            '' => null,
            default => 'Comprobante',
        };
    }

    private function concept(?SaleDocument $document): ?string
    {
        if (! $document) {
            return null;
        }

        $names = $document->items
            ->pluck('decription_product')
            ->filter()
            // Las descripciones de los ítems traen saltos de línea.
            ->map(fn ($name) => trim(preg_replace('/\s+/u', ' ', (string) $name)))
            ->filter()
            ->unique()
            ->values();

        if ($names->isEmpty()) {
            return null;
        }

        return $names->count() > 2
            ? $names->count() . ' items'
            : mb_substr($names->implode(', '), 0, 200);
    }

    private function formatDate(mixed $date): ?string
    {
        if (! $date) {
            return null;
        }

        if ($date instanceof \DateTimeInterface) {
            return $date->format('d/m/Y');
        }

        $value = trim((string) $date);

        return $value !== '' ? substr($value, 0, 10) : null;
    }
}
