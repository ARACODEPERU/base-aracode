<?php

namespace Modules\Treasury\Services;

use App\Models\Sale;
use App\Models\SaleDocument;
use Illuminate\Support\Facades\Route;
use Modules\Onlineshop\Entities\OnliSale;
use Modules\Purchases\Entities\PurcDocument;
use Modules\Purchases\Entities\PurcDocumentItems;
use Modules\Sales\Entities\SaleDocumentQuota;
use Modules\Sales\Entities\SalePaymentQuota;
use Modules\Sales\Entities\SalePaymentSchedule;
use Modules\Sales\Entities\SalePaymentScheduleDestination;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasuryPaymentOrder;
use Modules\Treasury\Entities\TreasuryReconciliation;
use Modules\Treasury\Entities\TreasurySourceMovement;
use Modules\Treasury\Entities\TreasuryTransaction;
use Modules\Treasury\Entities\TreasuryTransfer;
use Modules\Treasury\Entities\TreasuryWalletTransfer;
use Modules\Treasury\Services\TreasuryDetractionService;
use Modules\Treasury\Services\TreasuryTaxService;

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

        // Detracción, IGV y otros movimientos automáticos no tienen puente:
        // su origen se resuelve directamente desde la transacción.
        $withoutBridge = TreasuryTransaction::query()
            ->whereIn('id', $ids)
            ->whereNotIn('id', $bridges->keys()->all())
            ->get();

        foreach ($withoutBridge as $transaction) {
            $detail = $this->transactionDetail($transaction);

            if ($detail !== []) {
                $details[(int) $transaction->id] = $detail;
            }
        }

        // Señal «falta detracción»: la factura está sujeta y todavía no hay
        // ningún movimiento de detracción registrado.
        $documentIds = collect($details)
            ->pluck('document_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        // Estado de conciliación y anulación: se muestra en cualquier detalle,
        // venga de una venta, una transferencia o un impuesto.
        $transactions = TreasuryTransaction::query()
            ->whereIn('id', array_keys($details))
            ->get()
            ->keyBy('id');

        // Acta de conciliación que congeló el movimiento (si existe).
        $reconciliationIds = $transactions->pluck('treasury_reconciliation_id')->filter()->unique()->values()->all();

        $reconciliations = $reconciliationIds === []
            ? collect()
            : TreasuryReconciliation::query()->whereIn('id', $reconciliationIds)->get()->keyBy('id');

        foreach ($details as $transactionId => $detail) {
            $transaction = $transactions->get($transactionId);

            if (! $transaction) {
                continue;
            }

            $act = $transaction->treasury_reconciliation_id
                ? $reconciliations->get((int) $transaction->treasury_reconciliation_id)
                : null;

            $details[$transactionId]['conciliado'] = $transaction->reconciled_at !== null;
            $details[$transactionId]['conciliacion_cerrada'] = $transaction->treasury_reconciliation_id !== null;
            $details[$transactionId]['conciliacion_detalle'] = $act
                ? 'Acta #' . $act->id . ' del ' . $act->cutoff_date?->format('d/m/Y')
                    . ' · ' . ($act->status === TreasuryReconciliation::STATUS_CLOSED ? 'cerrada' : 'borrador')
                    . ' · diferencia ' . $this->money($act->difference, $act->currency)
                : null;
            $details[$transactionId]['anulado'] = $transaction->voided_at !== null;
        }

        if ($documentIds !== []) {
            $registered = TreasuryTransaction::query()
                ->whereIn('origin_id', $documentIds)
                ->whereIn('origin_type', [
                    TreasuryDetractionService::ORIGIN_DETRACTION,
                    TreasuryDetractionService::ORIGIN_AUTO_MOVE,
                ])
                ->where('type', TreasuryTransaction::TYPE_INCOME)
                ->whereNull('voided_at')
                ->pluck('origin_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $documents = SaleDocument::query()->whereIn('id', $documentIds)->get()->keyBy('id');
            $detraction = app(TreasuryDetractionService::class);

            foreach ($details as $transactionId => $detail) {
                $document = $documents->get((int) ($detail['document_id'] ?? 0));

                $details[$transactionId]['detraccion_pendiente'] = $document
                    && ((bool) $document->detraccion || (float) $document->detraccion_amount > 0)
                    && $detraction->applies($document)
                    && ! in_array((int) $document->id, $registered, true);
            }
        }

        return $details;
    }

    /**
     * Detalle del comprobante para un movimiento sin puente de integración.
     *
     * @return array<string, mixed>
     */
    private function transactionDetail(TreasuryTransaction $transaction): array
    {
        $document = null;
        $tipo = null;

        switch ((string) $transaction->origin_type) {
            case 'treasury_transfer':
            case 'treasury_transfer_fee':
            case 'treasury_transfer_itf':
                return $this->transferDetail($transaction);
            case 'treasury_commission':
                return $this->commissionDetail($transaction);
            case TreasuryTaxService::ORIGIN_IGV:
                return $this->taxDetail($transaction);
            case 'treasury_schedule':
                return $this->scheduleChargeDetail($transaction);
            case TreasuryDetractionService::ORIGIN_ADJUST:
                return [
                    'tipo' => 'Ajuste de detracción',
                    'referencia' => $transaction->reference,
                    'meta' => [],
                    'url' => route('treasury_detraction_index'),
                    'url_label' => 'Ver detracción',
                ];
            case TreasuryDetractionService::ORIGIN_RELEASE:
                return $this->releaseDetail($transaction);
            case 'sale_detraction':
                $document = SaleDocument::find((int) $transaction->origin_id);
                $tipo = 'Detracción';
                break;
            case 'detraccion_auto':
                $document = SaleDocument::find((int) $transaction->origin_id);
                $tipo = 'Auto-detracción';
                break;
            case 'sale_document':
                $document = SaleDocument::find((int) $transaction->origin_id);
                $tipo = 'Cobro de comprobante';
                break;
            case 'sale':
                $sale = Sale::find((int) $transaction->origin_id);
                $document = $sale?->documents()->orderByDesc('id')->first();
                $tipo = 'Cobro de venta';
                break;
            default:
                return [];
        }

        if (! $document) {
            return [];
        }

        return $this->build(
            tipo: $tipo,
            document: $document,
            dueDate: $document->invoice_due_date,
            reference: $transaction->reference,
        );
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
            TreasurySourceMovement::SOURCE_PURCHASE_DOCUMENT => $this->purchaseDetail($bridge),
            'onli_sale' => $this->onlineSaleDetail($bridge),
            'payment_order' => $this->paymentOrderDetail($bridge),
            default => [],
        };
    }

    /**
     * Traslado entre dos cuentas de tesorería (o de la cola de billeteras).
     *
     * @return array<string, mixed>
     */
    private function transferDetail(TreasuryTransaction $transaction): array
    {
        $transfer = TreasuryTransfer::query()
            ->with(['fromAccount', 'toAccount'])
            ->find((int) $transaction->origin_id);

        if (! $transfer) {
            return [];
        }

        // Los traslados programados de billeteras también son transferencias:
        // se marcan para que se entienda de dónde salió el dinero.
        $queue = TreasuryWalletTransfer::query()
            ->where('treasury_transfer_id', $transfer->id)
            ->first();

        $tipo = match ((string) $transaction->origin_type) {
            'treasury_transfer_fee' => 'Comisión de transferencia',
            'treasury_transfer_itf' => 'ITF de transferencia',
            default => $queue ? 'Traslado programado de billetera' : 'Transferencia entre cuentas',
        };

        $currency = strtoupper((string) ($transfer->from_currency ?: 'PEN')) ?: 'PEN';
        $meta = [
            ['label' => 'Cuenta origen', 'value' => $transfer->fromAccount?->displayName()],
            ['label' => 'Cuenta destino', 'value' => $transfer->toAccount?->displayName()],
            ['label' => 'Monto transferido', 'value' => $this->money($transfer->amount, $currency)],
        ];

        $received = (float) $transfer->amount_destination ?: (float) $transfer->net_received;

        if ($received > 0 && round($received, 2) !== round((float) $transfer->amount, 2)) {
            $meta[] = [
                'label' => 'Recibido en destino',
                'value' => $this->money($received, strtoupper((string) ($transfer->to_currency ?: $currency))),
            ];
        }

        $fees = round(
            (float) $transfer->from_fee_amount + (float) $transfer->from_fee_igv
            + (float) $transfer->to_fee_amount + (float) $transfer->to_fee_igv
            + (float) $transfer->itf_amount,
            2
        );

        if ($fees > 0) {
            $meta[] = ['label' => 'Comisiones e ITF', 'value' => $this->money($fees, $currency)];
        }

        if ($queue) {
            $meta[] = ['label' => 'Fecha programada', 'value' => $queue->scheduled_date?->format('d/m/Y')];
            $meta[] = [
                'label' => 'Estado del traslado',
                'value' => match ($queue->status) {
                    TreasuryWalletTransfer::STATUS_PROCESSED => 'Ejecutado',
                    TreasuryWalletTransfer::STATUS_FAILED => 'Fallido' . ($queue->error ? ': ' . $queue->error : ''),
                    default => 'Programado',
                },
            ];
        }

        return [
            'tipo' => $tipo,
            'referencia' => $transfer->reference ?: $transaction->reference,
            'estado' => $transfer->isVoided() ? 'Transferencia anulada' : null,
            'meta' => array_values(array_filter($meta, fn (array $row) => filled($row['value']))),
            'url' => route('treasury_transfers_index'),
            'url_label' => 'Ver transferencias',
        ];
    }

    /**
     * Comisión cobrada por recibir un ingreso: se explica a qué cobro pertenece.
     *
     * @return array<string, mixed>
     */
    private function commissionDetail(TreasuryTransaction $transaction): array
    {
        $income = TreasuryTransaction::find((int) $transaction->origin_id);

        $meta = [
            ['label' => 'Cobro que la generó', 'value' => $income ? 'Movimiento #' . $income->id : null],
            ['label' => 'Monto del cobro', 'value' => $income ? $this->money($income->amount, $income->currency ?: 'PEN') : null],
            ['label' => 'Cuenta del cobro', 'value' => $income?->account?->displayName()],
        ];

        return [
            'tipo' => 'Comisión por cobro',
            'referencia' => $transaction->reference,
            'meta' => array_values(array_filter($meta, fn (array $row) => filled($row['value']))),
            'url' => $income
                ? route('treasury_transactions_index', ['account_id' => $income->treasury_account_id])
                : null,
            'url_label' => 'Ver el cobro',
        ];
    }

    /**
     * Pago de IGV de un periodo.
     *
     * @return array<string, mixed>
     */
    private function taxDetail(TreasuryTransaction $transaction): array
    {
        $code = (string) $transaction->origin_id;
        $period = strlen($code) >= 6 ? substr($code, 0, 4) . '-' . substr($code, 4, 2) : null;

        return [
            'tipo' => 'Pago de IGV',
            'referencia' => $transaction->reference,
            'meta' => array_values(array_filter([
                ['label' => 'Periodo tributario', 'value' => $period],
                ['label' => 'Cuenta de pago', 'value' => $transaction->account?->displayName()],
            ], fn (array $row) => filled($row['value']))),
            'url' => route('treasury_taxes_index'),
            'url_label' => 'Ver impuestos',
        ];
    }

    /**
     * Cargo automático de un gasto recurrente programado.
     *
     * @return array<string, mixed>
     */
    private function scheduleChargeDetail(TreasuryTransaction $transaction): array
    {
        return [
            'tipo' => 'Gasto recurrente programado',
            'referencia' => $transaction->reference,
            'meta' => array_values(array_filter([
                ['label' => 'Cuenta', 'value' => $transaction->account?->displayName()],
                ['label' => 'Detalle', 'value' => $transaction->description],
            ], fn (array $row) => filled($row['value']))),
            'url' => route('treasury_payments_index'),
            'url_label' => 'Ver pagos y recurrentes',
        ];
    }

    /**
     * Liberación de detracción hacia una cuenta de la empresa.
     *
     * @return array<string, mixed>
     */
    private function releaseDetail(TreasuryTransaction $transaction): array
    {
        $destination = TreasuryAccount::find((int) $transaction->origin_id);

        return [
            'tipo' => 'Liberación de detracción',
            'referencia' => $transaction->reference,
            'meta' => array_values(array_filter([
                ['label' => 'Cuenta destino', 'value' => $destination?->displayName()],
            ], fn (array $row) => filled($row['value']))),
            'url' => route('treasury_detraction_index'),
            'url_label' => 'Ver detracción',
        ];
    }

    /**
     * Orden de pago ejecutada (compras, proveedores, planillas, gastos).
     *
     * @return array<string, mixed>
     */
    private function paymentOrderDetail(TreasurySourceMovement $bridge): array
    {
        $order = TreasuryPaymentOrder::query()
            ->with('supplier')
            ->find((int) $bridge->source_id);

        $meta = [
            ['label' => 'Código de orden', 'value' => $order?->code ?: $bridge->reference],
            ['label' => 'Tipo', 'value' => $order?->typeLabel()],
            ['label' => 'Proveedor / beneficiario', 'value' => $order?->supplier?->name],
            ['label' => 'Vencimiento', 'value' => $order?->due_date?->format('d/m/Y')],
            ['label' => 'Estado de la orden', 'value' => $order?->statusLabel()],
        ];

        return [
            'tipo' => 'Orden de pago ejecutada',
            'referencia' => $order?->code ?: $bridge->reference,
            'concepto' => $order?->description,
            'meta' => array_values(array_filter($meta, fn (array $row) => filled($row['value']))),
            'url' => route('treasury_payments_index'),
            'url_label' => 'Ver pagos',
        ];
    }

    /** Importe legible con su moneda. */
    private function money(mixed $amount, ?string $currency = 'PEN'): string
    {
        return number_format((float) $amount, 2, '.', ',') . ' ' . (strtoupper((string) ($currency ?: 'PEN')) ?: 'PEN');
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
     * Compra pagada al proveedor (egreso del libro).
     *
     * El comprobante es el documento de compra del módulo Compras: aquí no hay
     * cliente sino proveedor, y el dinero sale de la cuenta del medio de pago
     * con el que se pagó.
     *
     * @return array<string, mixed>
     */
    private function purchaseDetail(TreasurySourceMovement $bridge): array
    {
        $purchase = PurcDocument::with(['provider', 'type'])->find((int) $bridge->source_id);

        if (! $purchase) {
            return [];
        }

        $currency = strtoupper((string) ($purchase->currency_type_id ?: 'PEN')) ?: 'PEN';
        $provider = trim((string) $purchase->provider?->full_name) ?: null;
        $concept = trim(preg_replace('/\s+/u', ' ', (string) $purchase->descripction));

        if ($concept === '') {
            $concept = $this->purchaseItemConcept($purchase);
        }

        $meta = [
            ['label' => 'Proveedor', 'value' => $provider],
            ['label' => 'Fecha de emisión', 'value' => $this->formatDate($purchase->date_of_issue)],
            ['label' => 'Total de la compra', 'value' => $this->money($purchase->total, $currency)],
            ['label' => 'Pagado en este movimiento', 'value' => $this->money($bridge->amount, $bridge->currency ?: $currency)],
            ['label' => 'Cuenta de salida', 'value' => $bridge->account?->displayName()],
            ['label' => 'Estado de la compra', 'value' => $this->purchaseStatusLabel($purchase->status)],
        ];

        return [
            'tipo' => 'Pago de compra',
            'document_id' => null,
            'detraccion_pendiente' => false,
            'comprobante' => $this->purchaseNumber($purchase),
            'comprobante_tipo' => $purchase->type?->description ?: 'Comprobante de compra',
            'cliente' => $provider,
            'vencimiento' => $this->formatDate($purchase->date_of_due),
            'concepto' => $concept ?: null,
            'metodo' => $bridge->paymentMethod?->description,
            'referencia' => $bridge->reference,
            'total' => (float) $purchase->total > 0 ? round((float) $purchase->total, 2) : null,
            'moneda' => $currency,
            'meta' => array_values(array_filter($meta, fn (array $row) => filled($row['value']))),
            'url' => Route::has('purc_documents_list') ? route('purc_documents_list') : null,
            'url_label' => 'Ver compras',
        ];
    }

    /** Número visible del documento de compra. */
    private function purchaseNumber(PurcDocument $purchase): ?string
    {
        $serie = trim((string) $purchase->serie);
        $number = trim((string) $purchase->number);

        return $serie !== '' && $number !== '' ? $serie . '-' . $number : null;
    }

    /** Productos comprados, para el campo «Concepto» cuando la cabecera no lo trae. */
    private function purchaseItemConcept(PurcDocument $purchase): ?string
    {
        $productIds = PurcDocumentItems::query()
            ->where('purchase_id', $purchase->id)
            ->pluck('product_id')
            ->filter()
            ->unique()
            ->values();

        if ($productIds->isEmpty()) {
            return null;
        }

        $names = \App\Models\Product::query()
            ->whereIn('id', $productIds->all())
            ->pluck('description')
            ->filter()
            ->map(fn ($name) => trim(preg_replace('/\s+/u', ' ', (string) $name)))
            ->filter()
            ->values();

        if ($names->isEmpty()) {
            return null;
        }

        return $names->count() > 2
            ? $names->count() . ' items'
            : mb_substr($names->implode(', '), 0, 200);
    }

    /** Estado legible del documento de compra. */
    private function purchaseStatusLabel(?string $status): ?string
    {
        return match ((string) $status) {
            'R' => 'Registrado',
            'A' => 'Anulado',
            'E' => 'Enviado a SUNAT',
            'AP' => 'Aprobado por SUNAT',
            '' => null,
            default => null,
        };
    }

    /**
     * Venta del carrito web cobrada por MercadoPago.
     *
     * El ingreso se registra al aprobarse el pago, antes de que exista la nota
     * de venta; por eso el comprobante se busca en la nota enlazada y, si la
     * boleta o factura todavía no se emitió, se muestra igual el pedido, el
     * cliente y el pago.
     *
     * @return array<string, mixed>
     */
    private function onlineSaleDetail(TreasurySourceMovement $bridge): array
    {
        $onliSale = OnliSale::with(['details.item', 'person'])->find((int) $bridge->source_id);

        if (! $onliSale) {
            return [];
        }

        $sale = $onliSale->nota_sale_id ? Sale::find((int) $onliSale->nota_sale_id) : null;
        $document = $sale?->documents()->orderByDesc('id')->first();

        $detail = $this->build(
            tipo: 'Venta online (carrito)',
            document: $document,
            dueDate: $document?->invoice_due_date,
            bridge: $bridge,
        );

        $currency = strtoupper((string) ($onliSale->currency ?: 'PEN')) ?: 'PEN';
        $clientDocument = trim(
            (string) ($onliSale->identification_type ?? '') . ' ' . (string) ($onliSale->identification_number ?? '')
        );

        $meta = [
            ['label' => 'Pedido online', 'value' => $onliSale->id ? '#' . $onliSale->id : null],
            ['label' => 'Nota de venta', 'value' => $sale ? '#' . $sale->id : null],
            ['label' => 'Documento del cliente', 'value' => $clientDocument ?: null],
            ['label' => 'Email del cliente', 'value' => $onliSale->email],
            ['label' => 'Monto cobrado', 'value' => $this->money($onliSale->total, $currency)],
            ['label' => 'Cuenta destino', 'value' => $bridge->account?->displayName()],
        ];

        $detail['cliente'] = $detail['cliente'] ?: ($onliSale->clie_full_name ?: $onliSale->person?->full_name);
        $detail['concepto'] = $detail['concepto'] ?: $this->onlineSaleConcept($onliSale);
        $detail['metodo'] = $detail['metodo'] ?: $this->onlinePaymentLabel($onliSale);
        $detail['referencia'] = $detail['referencia'] ?: $bridge->reference;
        $detail['total'] = $detail['total'] ?: round((float) $onliSale->total, 2);
        $detail['moneda'] = $detail['moneda'] ?: $currency;
        $detail['meta'] = array_values(array_filter($meta, fn (array $row) => filled($row['value'])));
        $detail['url'] = $document
            ? route('saledocuments_list')
            : (Route::has('onlineshop_sales') ? route('onlineshop_sales') : null);
        $detail['url_label'] = $document ? 'Ver comprobante' : 'Ver pedidos online';

        return $detail;
    }

    /**
     * Productos del pedido para el campo «Concepto».
     */
    private function onlineSaleConcept(OnliSale $onliSale): ?string
    {
        $names = $onliSale->details
            ->map(fn ($detail) => trim(preg_replace('/\s+/u', ' ', (string) ($detail->item?->name ?: $detail->entitie))))
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

    /**
     * Medio con el que se cobró el pedido en MercadoPago.
     */
    private function onlinePaymentLabel(OnliSale $onliSale): string
    {
        $method = trim((string) ($onliSale->response_payment_method_id ?? ''));

        return $method !== '' ? 'MercadoPago (' . strtoupper($method) . ')' : 'MercadoPago';
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
        ?TreasurySourceMovement $bridge = null,
        ?string $reference = null,
    ): array {
        $document?->loadMissing('items');

        $total = null;
        $moneda = null;
        $detraccion = null;
        $detraccionPercent = null;

        if ($document) {
            $total = round((float) ($document->overall_total ?: $document->invoice_mto_imp_sale), 2);
            $moneda = strtoupper((string) ($document->invoice_type_currency ?: 'PEN')) ?: 'PEN';

            // Solo las facturas marcadas con detracción muestran su importe.
            if ((bool) $document->detraccion || (float) $document->detraccion_amount > 0) {
                $detraccionPercent = (float) ($document->detraccion_percent ?? 0)
                    ?: (float) config('treasury.detraction_percent', 12);
                $detraccion = (float) ($document->detraccion_amount ?? 0)
                    ?: round($total * $detraccionPercent / 100, 2);
            }
        }

        return [
            'tipo' => $tipo,
            'document_id' => $document?->id,
            'detraccion_pendiente' => false,
            'meta' => $this->commercialContractMeta($document),
            'url' => $document ? route('saledocuments_list') : null,
            'url_label' => 'Ver comprobantes',
            'comprobante' => $this->documentNumber($document),
            'comprobante_tipo' => $this->documentTypeName($document),
            'cliente' => $document?->client_rzn_social,
            'vencimiento' => $this->formatDate($dueDate),
            'concepto' => $this->concept($document),
            'metodo' => $bridge?->paymentMethod?->description,
            'referencia' => $bridge?->reference ?? $reference,
            'total' => $total > 0 ? $total : null,
            'moneda' => $moneda,
            'detraccion' => $detraccion,
            'detraccion_porcentaje' => $detraccionPercent,
            'detraccion_auto' => (bool) ($document?->detraccion_auto),
        ];
    }

    /**
     * Datos del contrato de Comercial que originó el comprobante.
     *
     * Es solo una referencia para mostrar de dónde salió el cobro: el importe
     * del movimiento es el recibido, no el total del contrato.
     *
     * @return array<int, array{label: string, value: string}>
     */
    private function commercialContractMeta(?SaleDocument $document): array
    {
        if (! $document || ! $document->commercial_contract_id) {
            return [];
        }

        $contractClass = \Modules\Commercial\Entities\CommercialContract::class;
        $paymentClass = \Modules\Commercial\Entities\CommercialContractPayment::class;

        if (! class_exists($contractClass)) {
            return [];
        }

        $contract = $contractClass::find((int) $document->commercial_contract_id);

        if (! $contract) {
            return [];
        }

        $payment = $document->commercial_contract_payment_id && class_exists($paymentClass)
            ? $paymentClass::find((int) $document->commercial_contract_payment_id)
            : null;

        $vigencia = collect([
            $this->formatDate($contract->start_date),
            $this->formatDate($contract->end_date),
        ])->filter()->implode(' - ');

        $meta = [
            ['label' => 'Contrato', 'value' => trim((string) $contract->title) ?: 'Contrato #' . $contract->id],
            ['label' => 'Vigencia del contrato', 'value' => $vigencia ?: null],
        ];

        if ($payment) {
            $totalCuotas = $paymentClass::where('contract_id', $contract->id)->count();

            $meta[] = [
                'label' => 'Cuota del contrato',
                'value' => 'Cuota ' . $payment->payment_number . ($totalCuotas ? ' de ' . $totalCuotas : ''),
            ];
            $meta[] = ['label' => 'Vencimiento de la cuota', 'value' => $this->formatDate($payment->due_date)];
        }

        return array_values(array_filter($meta, fn (array $row) => filled($row['value'])));
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
