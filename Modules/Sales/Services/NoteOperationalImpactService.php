<?php

namespace Modules\Sales\Services;

use App\Models\Expense;
use App\Models\PettyCash;
use App\Models\Product;
use App\Models\SaleDocument;
use App\Models\SaleProduct;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Impacto operativo de una nota de crédito/débito ya aceptada por SUNAT:
 *
 *  1. Reingreso de mercadería al almacén (Kardex de entrada + stock), solo por
 *     las líneas que el usuario marcó como devolución.
 *  2. Ajuste de caja por la parte de la nota que se pagó en efectivo:
 *     - caja abierta  -> el reporte de caja ya descuenta la nota aceptada, no se
 *                        registra un egreso extra (evita doble descuento).
 *     - caja cerrada  -> se registra un egreso en la caja abierta del mismo
 *                        local, sin tocar el cierre anterior.
 *     - sin caja      -> no se toca nada y se reporta la advertencia.
 *
 * Todos los métodos están pensados para ejecutarse dentro de una transacción
 * abierta por el llamador (NotaCredito / SaleCreditNotesController).
 */
class NoteOperationalImpactService
{
    /** payment_methods.id del método "Efectivo". */
    public const CASH_PAYMENT_METHOD = 1;

    public const MODE_OPEN_CASH = 'caja_abierta';

    public const MODE_CLOSED_CASH = 'caja_cerrada';

    public const MODE_NO_CASH = 'sin_caja';

    public const MODE_NOT_APPLICABLE = 'no_aplica';

    /** Cache de la comprobación de columnas nuevas (despliegue antes de migrar). */
    private static ?bool $supportsImpactColumns = null;

    public function __construct(private readonly SaleStockService $stock)
    {
    }

    /**
     * Aplica stock y caja a una nota aceptada. Devuelve el detalle de lo hecho.
     *
     * @return array{restock: array<int, array<string, mixed>>, cash: array<string, mixed>, warnings: array<int, string>}
     */
    public function apply(SaleDocument $note): array
    {
        $summary = [
            'restock' => [],
            'cash' => ['mode' => self::MODE_NOT_APPLICABLE, 'amount' => 0.0, 'petty_cash_id' => null],
            'warnings' => [],
        ];

        if (! in_array((string) $note->invoice_type_doc, ['07', '08'], true)) {
            return $summary;
        }

        if ((string) $note->invoice_status !== 'Aceptada') {
            $summary['warnings'][] = 'La nota no está aceptada por SUNAT: no se aplicó impacto operativo.';

            return $summary;
        }

        if (! $this->supportsImpactColumns()) {
            $summary['warnings'][] = 'Faltan columnas de impacto operativo: ejecuta las migraciones pendientes.';

            return $summary;
        }

        $invoice = SaleDocument::with('sale')->find($note->document_id);

        if (! $invoice) {
            $summary['warnings'][] = 'No se encontró el documento afectado.';

            return $summary;
        }

        if ((string) $note->invoice_type_doc === '07') {
            $summary['restock'] = $this->applyRestock($note, $invoice);
        }

        $summary['cash'] = $this->applyCashAdjustment($note, $invoice);

        if ($summary['cash']['mode'] === self::MODE_NO_CASH) {
            $summary['warnings'][] = 'No hay una caja abierta en el local para registrar el egreso de S/ '
                .number_format((float) $summary['cash']['amount'], 2).': regístralo manualmente al abrir caja.';
        }

        $note->save();

        return $summary;
    }

    /**
     * Suma de los pagos en efectivo de una venta.
     *
     * @param  array<int, array<string, mixed>|object>|null  $payments
     */
    public function cashShare(?array $payments): float
    {
        $total = 0.0;

        foreach ((array) $payments as $payment) {
            $type = is_array($payment) ? ($payment['type'] ?? null) : ($payment->type ?? null);

            if ((int) $type !== self::CASH_PAYMENT_METHOD) {
                continue;
            }

            $amount = is_array($payment) ? ($payment['amount'] ?? 0) : ($payment->amount ?? 0);
            $total += (float) $amount;
        }

        return round($total, 2);
    }

    /**
     * Parte del monto de la nota que corresponde a efectivo, prorrateando el
     * porcentaje pagado en efectivo del documento afectado.
     *
     * @param  array<int, array<string, mixed>|object>|null  $payments
     */
    public function prorateNoteCash(float $noteTotal, float $invoiceTotal, ?array $payments): float
    {
        if ($noteTotal <= 0 || $invoiceTotal <= 0) {
            return 0.0;
        }

        $cash = $this->cashShare($payments);

        if ($cash <= 0) {
            return 0.0;
        }

        $share = min(1.0, $cash / $invoiceTotal);

        return round(min($noteTotal, $noteTotal * $share), 2);
    }

    /**
     * Monto a ajustar en caja para la nota, calculado con datos del servidor.
     */
    public function cashAdjustmentFor(SaleDocument $note, SaleDocument $invoice): float
    {
        return $this->prorateNoteCash(
            (float) $note->invoice_mto_imp_sale,
            (float) $invoice->invoice_mto_imp_sale,
            $invoice->sale ? (array) $invoice->sale->payments : null
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function applyRestock(SaleDocument $note, SaleDocument $invoice): array
    {
        if (! $note->restock_inventory || $note->restock_applied_at) {
            return [];
        }

        $localId = (int) ($invoice->sale->local_id ?? 0);

        if ($localId <= 0) {
            return [];
        }

        $applied = [];

        foreach ($note->items()->get() as $item) {
            $quantity = (float) ($item->restock_quantity ?? 0);

            if ($quantity <= 0) {
                continue;
            }

            if ($item->entity_name_product && $item->entity_name_product !== Product::class) {
                continue;
            }

            if (strtoupper((string) $item->unit_type) === 'ZZ') {
                continue;
            }

            $product = Product::find($item->product_id);

            if (! $product || ! $product->is_product) {
                continue;
            }

            $kardex = $this->stock->recordReversal(
                $product,
                $quantity,
                (int) $note->id,
                SaleDocument::class,
                $localId,
                $this->resolveSize($invoice, (int) $product->id),
                'Devolución por '.$note->invoice_document_name
            );

            if ($kardex) {
                $applied[] = [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'kardex_id' => $kardex->id,
                ];
            }
        }

        $note->restock_applied_at = now();

        return $applied;
    }

    /**
     * @return array{mode: string, amount: float, petty_cash_id: int|null}
     */
    protected function applyCashAdjustment(SaleDocument $note, SaleDocument $invoice): array
    {
        $amount = round((float) ($note->cash_adjustment_amount ?? 0), 2);

        if ((string) $note->invoice_type_doc !== '07' || $amount <= 0) {
            $note->cash_adjustment_mode = self::MODE_NOT_APPLICABLE;

            return ['mode' => self::MODE_NOT_APPLICABLE, 'amount' => 0.0, 'petty_cash_id' => null];
        }

        if ($note->cash_adjustment_applied_at) {
            return [
                'mode' => $note->cash_adjustment_mode ?: self::MODE_NOT_APPLICABLE,
                'amount' => $amount,
                'petty_cash_id' => $note->cash_adjustment_petty_cash_id,
            ];
        }

        $sale = $invoice->sale;
        $session = $sale && $sale->petty_cash_id ? PettyCash::find($sale->petty_cash_id) : null;

        // Caja de la venta todavía abierta: su reporte ya resta la nota aceptada.
        if ($session && (int) $session->state === 1) {
            $note->cash_adjustment_mode = self::MODE_OPEN_CASH;
            $note->cash_adjustment_petty_cash_id = $session->id;
            $note->cash_adjustment_applied_at = now();

            return ['mode' => self::MODE_OPEN_CASH, 'amount' => $amount, 'petty_cash_id' => $session->id];
        }

        // Caja histórica ya cerrada: se ajusta en la caja abierta del local, sin
        // modificar el cierre anterior.
        $open = $sale
            ? PettyCash::where('local_sale_id', $sale->local_id)->where('state', 1)->latest('id')->first()
            : null;

        if (! $open) {
            $note->cash_adjustment_mode = self::MODE_NO_CASH;

            return ['mode' => self::MODE_NO_CASH, 'amount' => $amount, 'petty_cash_id' => null];
        }

        Expense::create([
            'amount' => $amount,
            'description' => 'Ajuste por '.$note->invoice_document_name
                .' (nota de crédito sobre '.$invoice->invoice_serie.'-'.$invoice->invoice_correlative.')',
            'document' => $note->invoice_document_name,
            'petty_cash_id' => $open->id,
        ]);

        $note->cash_adjustment_mode = self::MODE_CLOSED_CASH;
        $note->cash_adjustment_petty_cash_id = $open->id;
        $note->cash_adjusted_externally = true;
        $note->cash_adjustment_applied_at = now();

        return ['mode' => self::MODE_CLOSED_CASH, 'amount' => $amount, 'petty_cash_id' => $open->id];
    }

    /**
     * Talla de la línea original, para el Kardex por tallas.
     */
    protected function resolveSize(SaleDocument $invoice, int $productId): ?string
    {
        if (! $invoice->sale_id) {
            return null;
        }

        try {
            $line = SaleProduct::where('sale_id', $invoice->sale_id)
                ->where('product_id', $productId)
                ->first();
        } catch (Throwable $e) {
            return null;
        }

        if (! $line) {
            return null;
        }

        $data = json_decode($line->saleProduct ?? '{}');

        return $data->size ?? null;
    }

    protected function supportsImpactColumns(): bool
    {
        if (self::$supportsImpactColumns === null) {
            try {
                self::$supportsImpactColumns = Schema::hasColumn('sale_documents', 'cash_adjustment_amount');
            } catch (Throwable $e) {
                self::$supportsImpactColumns = false;
            }
        }

        return self::$supportsImpactColumns;
    }
}
