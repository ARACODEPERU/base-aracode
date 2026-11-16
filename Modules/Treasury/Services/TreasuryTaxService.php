<?php

namespace Modules\Treasury\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasuryTransaction;
use Modules\Treasury\Exceptions\TreasuryOperationException;

/**
 * IGV trasladado de boletas y facturas.
 *
 * Reglas:
 * - Solo se toma en cuenta lo emitido: boletas (03) y facturas (01) con
 *   `invoice_mto_igv` propio, estén cobradas o al crédito. La fecha que manda
 *   es la de emisión (`invoice_broadcast_date`), no la de cobro.
 * - Las notas de crédito (07) aceptadas restan el IGV que acreditan.
 * - Se excluyen comprobantes anulados o rechazados.
 * - El pago se registra como un egreso con categoría «IGV por pagar» y una
 *   clave por periodo: un mismo periodo no se paga dos veces desde la misma
 *   cuenta.
 *
 * El IGV de una venta no se descuenta del ingreso: el comprobante entra al
 * libro por su total y este servicio explica la obligación tributaria aparte.
 */
class TreasuryTaxService
{
    public const ORIGIN_IGV = 'treasury_tax_igv';

    public const IGV_RATE = 0.18;

    public function __construct(
        private TreasuryLedgerService $ledger,
        private TreasuryCurrencyService $currency,
        private TreasuryAuditService $audit,
    ) {
    }

    /**
     * IGV trasladado de un rango de fechas de emisión.
     *
     * @return array<string, mixed>
     */
    public function summary(Carbon|string $from, Carbon|string $to, string $currency = 'PEN'): array
    {
        $from = Carbon::parse($from)->startOfDay();
        $to = Carbon::parse($to)->startOfDay();
        $currency = $this->currency->normalize($currency);

        if (! Schema::hasTable('sale_documents')) {
            return $this->emptySummary($from, $to, $currency);
        }

        $documents = $this->queryDocuments('in', ['01', '03'], $from, $to, $currency);
        $notes = $this->queryDocuments('in', ['07'], $from, $to, $currency);

        $salesTotal = 0.0;
        $salesIgv = 0.0;
        $rows = [];

        foreach ($documents as $document) {
            $igv = $this->documentIgv($document);
            $salesTotal += (float) $document->overall_total;
            $salesIgv += $igv;

            $rows[] = [
                'id' => (int) $document->id,
                'date' => substr((string) $document->invoice_broadcast_date, 0, 10),
                'type' => (string) $document->invoice_type_doc,
                'type_label' => $this->documentTypeLabel((string) $document->invoice_type_doc),
                'number' => trim((string) $document->invoice_serie . '-' . (string) $document->invoice_correlative),
                'client' => (string) ($document->client_rzn_social ?: '—'),
                'total' => round((float) $document->overall_total, 2),
                'igv' => $igv,
                'credit' => $this->isCredit((string) ($document->forma_pago ?: '')),
                'status' => (string) ($document->invoice_status ?: '—'),
            ];
        }

        $notesIgv = 0.0;
        $noteRows = [];

        foreach ($notes as $note) {
            $igv = $this->documentIgv($note);
            $notesIgv += $igv;

            $noteRows[] = [
                'id' => (int) $note->id,
                'date' => substr((string) $note->invoice_broadcast_date, 0, 10),
                'number' => trim((string) $note->invoice_serie . '-' . (string) $note->invoice_correlative),
                'client' => (string) ($note->client_rzn_social ?: '—'),
                'igv' => $igv,
            ];
        }

        $salesTotal = round($salesTotal, 2);
        $salesIgv = round($salesIgv, 2);
        $notesIgv = round($notesIgv, 2);

        // La detracción ya adelantó parte del IGV: rebaja lo que falta pagar.
        $detractions = app(TreasuryDetractionService::class)->registeredBetween(
            $from->toDateString(),
            $to->toDateString(),
            $currency,
        );

        $netIgv = round(max(0, $salesIgv - $notesIgv - $detractions), 2);

        return [
            'currency' => $currency,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'period' => $from->format('Y-m'),
            'documents' => $rows,
            'notes' => $noteRows,
            'documents_count' => count($rows),
            'notes_count' => count($noteRows),
            'sales_total' => $salesTotal,
            // Base imponible aproximada: total menos el IGV trasladado.
            'taxable_base' => round(max(0, $salesTotal - $salesIgv), 2),
            'sales_igv' => $salesIgv,
            'notes_igv' => $notesIgv,
            'detractions' => $detractions,
            'net_igv' => $netIgv,
            'is_debit' => $netIgv > 0,
            'paid' => $this->paidAmount($from->format('Y-m'), $currency),
        ];
    }

    /**
     * Resumen de un mes completo (por fecha de emisión).
     *
     * @return array<string, mixed>
     */
    public function monthSummary(int $year, int $month, string $currency = 'PEN'): array
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();

        return $this->summary($start, $start->copy()->endOfMonth(), $currency);
    }

    /**
     * Registra el pago del IGV de un periodo como egreso de la cuenta.
     * Es idempotente por periodo y cuenta: si ya se pagó, lanza.
     */
    public function registerPayment(
        TreasuryAccount $account,
        string $period,
        float $amount,
        string $date,
        ?string $reference = null,
        ?int $userId = null,
    ): TreasuryTransaction {
        $period = $this->normalizePeriod($period);
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw new TreasuryOperationException('El monto del IGV debe ser mayor que cero.');
        }

        $code = $this->periodCode($period);
        $alreadyPaid = TreasuryTransaction::query()
            ->active()
            ->where('treasury_account_id', $account->id)
            ->where('origin_type', self::ORIGIN_IGV)
            ->where('origin_id', $code)
            ->exists();

        if ($alreadyPaid) {
            throw new TreasuryOperationException('El IGV de ' . $period . ' ya fue pagado desde esta cuenta.');
        }

        $transaction = $this->ledger->registerExpense(
            $account,
            $date,
            $amount,
            $this->ledger->systemCategoryId('IGV por pagar'),
            self::ORIGIN_IGV,
            $code,
            $reference,
            'Pago de IGV ' . $period,
            null,
            TreasuryTransaction::SOURCE_MANUAL,
            $userId,
            false,
            null,
            $this->currency->accountCurrency($account),
        );

        if (! $transaction) {
            throw new TreasuryOperationException('No se pudo registrar el pago de IGV: revisa la cuenta y la fecha.');
        }

        $this->audit->log('tax_igv', $transaction->id, 'created', [], [
            'period' => $period,
            'amount' => $amount,
            'account' => $account->displayName(),
        ]);

        return $transaction;
    }

    /**
     * Pagos de IGV registrados, con su periodo y cuenta.
     *
     * @return array<int, array<string, mixed>>
     */
    public function payments(?string $currency = null, int $limit = 30): array
    {
        $query = TreasuryTransaction::query()
            ->active()
            ->where('origin_type', self::ORIGIN_IGV)
            ->with('account')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit($limit);

        if ($currency) {
            $query->where('currency', strtoupper($currency));
        }

        return $query->get()->map(fn (TreasuryTransaction $payment) => [
            'id' => $payment->id,
            'date' => $payment->transaction_date?->format('Y-m-d'),
            'period' => $payment->origin_id ? substr((string) $payment->origin_id, 0, 4) . '-' . substr((string) $payment->origin_id, 4, 2) : null,
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency ?: 'PEN',
            'account' => $payment->account?->displayName(),
            'reference' => $payment->reference,
            'voided' => $payment->voided_at !== null,
        ])->all();
    }

    /**
     * IGV de un periodo que todavía no tiene pago registrado en esa moneda.
     * Lo usa la previsión para proyectar el egreso tributario.
     */
    public function unpaidForPeriod(string $period, string $currency = 'PEN'): float
    {
        $period = $this->normalizePeriod($period);
        [$year, $month] = array_map('intval', explode('-', $period));

        $data = $this->monthSummary($year, $month, $currency);

        if ($data['net_igv'] <= 0) {
            return 0.0;
        }

        if ($this->paidAmount($period, $currency) > 0) {
            return 0.0;
        }

        return (float) $data['net_igv'];
    }

    /**
     * Fecha de vencimiento del IGV de un periodo (día configurado del mes siguiente).
     */
    public function dueDate(string $period): string
    {
        $period = $this->normalizePeriod($period);
        [$year, $month] = array_map('intval', explode('-', $period));
        $day = (int) config('treasury.igv_due_day', 15);

        $due = Carbon::create($year, $month, 1)->addMonth();
        $due->day(min(max(1, $day), $due->daysInMonth));

        return $due->toDateString();
    }

    /**
     * Total pagado (activo) de un periodo en una moneda.
     */
    public function paidAmount(string $period, string $currency = 'PEN'): float
    {
        $code = $this->periodCode($this->normalizePeriod($period));

        return round((float) TreasuryTransaction::query()
            ->active()
            ->where('origin_type', self::ORIGIN_IGV)
            ->where('origin_id', $code)
            ->where('currency', strtoupper($currency))
            ->sum('amount'), 2);
    }

    /** Periodo YYYY-MM a partir de una fecha. */
    public function periodOf(Carbon|string $date): string
    {
        return Carbon::parse($date)->format('Y-m');
    }

    private function normalizePeriod(string $period): string
    {
        if (preg_match('/^\d{4}-\d{2}$/', $period) === 1) {
            return $period;
        }

        if (preg_match('/^\d{6}$/', $period) === 1) {
            return substr($period, 0, 4) . '-' . substr($period, 4, 2);
        }

        return Carbon::parse($period)->format('Y-m');
    }

    private function periodCode(string $period): int
    {
        return (int) str_replace('-', '', $period);
    }

    /**
     * Comprobantes del rango, sin anulados ni rechazados. `$sign` no cambia la
     * consulta: existe para dejar explícito el sentido del documento.
     *
     * @param  array<int, string>  $types
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function queryDocuments(string $sign, array $types, Carbon $from, Carbon $to, string $currency)
    {
        return DB::table('sale_documents')
            ->whereIn('invoice_type_doc', $types)
            ->whereDate('invoice_broadcast_date', '>=', $from->toDateString())
            ->whereDate('invoice_broadcast_date', '<=', $to->toDateString())
            ->where(function ($query) {
                // Los anulados y rechazados no generan obligación.
                $query->whereNull('invoice_status')
                    ->orWhereNotIn('invoice_status', ['Anulada', 'Por anular', 'Rechazada', 'Enviada Por Anular']);
            })
            ->where(function ($query) {
                // El estado operativo 3 corresponde a comprobantes anulados.
                $query->whereNull('status')->orWhere('status', '<>', 3);
            })
            ->whereRaw("UPPER(COALESCE(invoice_type_currency, 'PEN')) = ?", [$currency])
            ->orderBy('invoice_broadcast_date')
            ->orderBy('id')
            ->get([
                'id',
                'invoice_type_doc',
                'invoice_serie',
                'invoice_correlative',
                'invoice_broadcast_date',
                'invoice_status',
                'invoice_mto_igv',
                'invoice_mto_oper_taxed',
                'overall_total',
                'invoice_type_currency',
                'client_rzn_social',
                'forma_pago',
            ]);
    }

    /**
     * IGV del comprobante. Si el documento no lo guardó, se estima desde el
     * total con la tasa vigente (18 %).
     */
    private function documentIgv(object $document): float
    {
        $igv = (float) ($document->invoice_mto_igv ?? 0);

        if ($igv <= 0) {
            $total = (float) ($document->overall_total ?? 0);
            $igv = $total > 0 ? round($total - ($total / (1 + self::IGV_RATE)), 2) : 0.0;
        }

        return round($igv, 2);
    }

    /**
     * El comprobante es al crédito («Credito», «Crédito» o «credito»).
     */
    private function isCredit(string $formaPago): bool
    {
        $value = mb_strtolower(trim($formaPago));

        return str_contains($value, 'credito') || str_contains($value, 'crédito');
    }

    private function documentTypeLabel(string $type): string
    {
        return match ($type) {
            '01' => 'Factura',
            '03' => 'Boleta',
            '07' => 'Nota de crédito',
            '08' => 'Nota de débito',
            default => 'Comprobante',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function emptySummary(Carbon $from, Carbon $to, string $currency): array
    {
        return [
            'currency' => $currency,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'period' => $from->format('Y-m'),
            'documents' => [],
            'notes' => [],
            'documents_count' => 0,
            'notes_count' => 0,
            'sales_total' => 0.0,
            'taxable_base' => 0.0,
            'sales_igv' => 0.0,
            'notes_igv' => 0.0,
            'detractions' => 0.0,
            'net_igv' => 0.0,
            'is_debit' => false,
            'paid' => 0.0,
        ];
    }
}
