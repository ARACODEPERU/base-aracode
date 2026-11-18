<?php

namespace Modules\Treasury\Services;

use App\Models\BankAccount;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Treasury\Entities\TreasuryPaymentOrder;
use Modules\Treasury\Exceptions\TreasuryOperationException;

/**
 * Pago masivo: arma el archivo que se sube al banco con las órdenes aprobadas.
 *
 * El archivo no ejecuta pagos ni mueve el libro: es la instrucción para el
 * banco. Cada orden exportada conserva el lote y la fecha en que se envió.
 *
 * Los formatos son referenciales (CSV genérico, BCP, BBVA e Interbank). Cada
 * banco cambia su layout: se deben validar con el banco antes de subirlos.
 */
class TreasuryBulkPaymentService
{
    public const FORMATS = [
        'csv' => 'CSV genérico',
        'bcp' => 'BCP (CSV)',
        'bbva' => 'BBVA (TXT)',
        'interbank' => 'Interbank (TXT)',
    ];

    public function __construct(private TreasuryAuditService $audit)
    {
    }

    /**
     * Órdenes disponibles para incluir en un lote.
     *
     * @return array<int, array<string, mixed>>
     */
    public function candidates(): array
    {
        return TreasuryPaymentOrder::query()
            ->open()
            ->with(['supplier.bankAccount.bank', 'account'])
            ->orderBy('due_date')
            ->orderBy('id')
            ->limit(400)
            ->get()
            ->map(fn (TreasuryPaymentOrder $order) => [
                'id' => $order->id,
                'code' => $order->code,
                'description' => $order->description,
                'supplier' => $order->supplier?->name ?? 'Sin proveedor',
                'document' => $order->supplier?->documentLabel(),
                'bank_account' => $order->supplier?->bankAccount?->number,
                'amount' => (float) $order->amount,
                'currency' => strtoupper((string) ($order->currency ?: 'PEN')) ?: 'PEN',
                'due_date' => $order->due_date?->format('Y-m-d'),
                'scheduled_date' => $order->scheduled_date?->format('Y-m-d'),
                'account' => $order->account?->displayName(),
                'account_id' => $order->treasury_account_id,
                'bulk_batch' => $order->bulk_batch,
            ])
            ->all();
    }

    /**
     * Genera el archivo del lote.
     *
     * @param  array<int, int|string>  $orderIds
     * @return array{filename: string, content: string, mime: string, count: int, total: float, currency: string, batch: string, warnings: array<int, string>}
     *
     * @throws TreasuryOperationException
     */
    public function generate(array $orderIds, string $format = 'csv', ?string $date = null, ?int $userId = null): array
    {
        $format = strtolower(trim($format));

        if (! array_key_exists($format, self::FORMATS)) {
            throw new TreasuryOperationException('El formato de archivo no está soportado.');
        }

        $ids = collect($orderIds)->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();

        if ($ids === []) {
            throw new TreasuryOperationException('Selecciona al menos una orden aprobada para el lote.');
        }

        $orders = TreasuryPaymentOrder::query()
            ->open()
            ->with(['supplier.bankAccount.bank', 'account.bankAccount'])
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get();

        if ($orders->isEmpty()) {
            throw new TreasuryOperationException('Las órdenes seleccionadas ya no están disponibles para pago.');
        }

        if ($orders->count() !== count($ids)) {
            throw new TreasuryOperationException(
                'Algunas órdenes ya no están aprobadas o fueron anuladas. Actualiza la lista e inténtalo otra vez.'
            );
        }

        $currencies = $orders
            ->map(fn (TreasuryPaymentOrder $order) => strtoupper((string) ($order->currency ?: 'PEN')) ?: 'PEN')
            ->unique()
            ->values();

        if ($currencies->count() > 1) {
            throw new TreasuryOperationException('Un lote no puede mezclar monedas: genera un archivo por moneda.');
        }

        $accounts = $orders->pluck('treasury_account_id')->filter()->unique()->values();

        if ($accounts->count() > 1) {
            throw new TreasuryOperationException('Todas las órdenes del lote deben pagarse desde la misma cuenta.');
        }

        $currency = $currencies->first();
        $originAccount = $orders->first()->account;
        $processDate = $date ? Carbon::parse($date)->toDateString() : now()->toDateString();
        $batch = 'PM-' . now()->format('Ymd-His') . '-' . str_pad((string) random_int(1, 99), 2, '0', STR_PAD_LEFT);

        $warnings = [];

        if ($accounts->isEmpty()) {
            $warnings[] = 'Las órdenes no tienen cuenta de pago asignada: el archivo sale sin cuenta de origen.';
        }

        $missingBank = $orders->filter(fn (TreasuryPaymentOrder $order) => blank($order->supplier?->bankAccount?->number));

        if ($missingBank->isNotEmpty()) {
            $warnings[] = $missingBank->count() . ' orden(es) no tienen cuenta bancaria del proveedor: revisa el archivo antes de subirlo.';
        }

        $total = round((float) $orders->sum('amount'), 2);
        $content = $this->render($format, $orders, $originAccount, $processDate, $batch, $currency, $total);

        DB::transaction(function () use ($orders, $batch) {
            TreasuryPaymentOrder::query()->whereIn('id', $orders->pluck('id')->all())->update([
                'bulk_batch' => $batch,
                'bulk_exported_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $this->audit->log('payment_batch', (int) $orders->first()->id, 'exported', [], [
            'batch' => $batch,
            'format' => $format,
            'orders' => $orders->pluck('code')->all(),
            'count' => $orders->count(),
            'total' => $total,
            'currency' => $currency,
            'process_date' => $processDate,
        ]);

        return [
            'filename' => 'pago-masivo-' . $format . '-' . $processDate . ($format === 'csv' || $format === 'bcp' ? '.csv' : '.txt'),
            'content' => $content,
            'mime' => $format === 'csv' || $format === 'bcp' ? 'text/csv; charset=UTF-8' : 'text/plain; charset=UTF-8',
            'count' => $orders->count(),
            'total' => $total,
            'currency' => $currency,
            'batch' => $batch,
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, TreasuryPaymentOrder>  $orders
     */
    private function render(
        string $format,
        $orders,
        $originAccount,
        string $processDate,
        string $batch,
        string $currency,
        float $total,
    ): string {
        $origin = $originAccount?->account_number
            ?: $originAccount?->bankAccount?->number
            ?: '';

        return match ($format) {
            'bcp' => $this->renderBcp($orders, $origin, $processDate, $batch, $currency, $total),
            'bbva' => $this->renderBbva($orders, $origin, $processDate, $batch, $currency, $total),
            'interbank' => $this->renderInterbank($orders, $processDate, $batch, $currency, $total),
            default => $this->renderCsv($orders, $processDate, $batch, $currency, $total),
        };
    }

    private function renderCsv($orders, string $processDate, string $batch, string $currency, float $total): string
    {
        $rows = [
            'Lote;' . $batch . ';Fecha;' . $processDate . ';Moneda;' . $currency . ';Registros;' . $orders->count(),
            'TipoDoc;Documento;Beneficiario;Cuenta;CCI;Moneda;Importe;Referencia;Concepto',
        ];

        foreach ($orders as $order) {
            $bank = $order->supplier?->bankAccount;

            $rows[] = implode(';', [
                $this->text($order->supplier?->doc_type),
                $this->text($order->supplier?->doc_number),
                $this->text($order->supplier?->name ?: 'Sin proveedor'),
                $this->text($bank?->number),
                $this->text($bank?->cci),
                $currency,
                $this->amount($order->amount),
                $this->text($order->code),
                $this->text($order->description),
            ]);
        }

        $rows[] = 'TOTAL;;;;;;' . $this->amount($total) . ';;' . $orders->count() . ' registros';

        return "\xEF\xBB\xBF" . implode("\r\n", $rows) . "\r\n";
    }

    private function renderBcp($orders, string $origin, string $processDate, string $batch, string $currency, float $total): string
    {
        $rows = [
            'CTA_ORIGEN;MONEDA;FECHA_PROCESO;LOTE',
            $this->text($origin) . ';' . $currency . ';' . $processDate . ';' . $batch,
            '',
            'TIPO_DOC;NRO_DOC;BENEFICIARIO;CTA_ABONO;CCI;IMPORTE;REFERENCIA;CONCEPTO',
        ];

        foreach ($orders as $order) {
            $bank = $order->supplier?->bankAccount;

            $rows[] = implode(';', [
                $this->text($order->supplier?->doc_type),
                $this->text($order->supplier?->doc_number),
                $this->text($order->supplier?->name ?: 'Sin proveedor'),
                $this->text($bank?->number),
                $this->text($bank?->cci),
                $this->amount($order->amount),
                $this->text($order->code),
                $this->text($order->description),
            ]);
        }

        $rows[] = 'TOTAL;;;;;' . $this->amount($total) . ';;' . $orders->count() . ' registros';

        return "\xEF\xBB\xBF" . implode("\r\n", $rows) . "\r\n";
    }

    private function renderBbva($orders, string $origin, string $processDate, string $batch, string $currency, float $total): string
    {
        // Cabecera, detalle y pie de ancho fijo (layout referencial BBVA).
        $rows = [
            '1'
            . $this->fixed($origin, 20)
            . $this->fixed($currency, 3)
            . str_replace('-', '', $processDate)
            . str_pad((string) $orders->count(), 6, '0', STR_PAD_LEFT)
            . str_pad($this->amount($total), 15, '0', STR_PAD_LEFT)
            . $this->fixed($batch, 20),
        ];

        foreach ($orders as $order) {
            $bank = $order->supplier?->bankAccount;

            $document = trim((string) ($order->supplier?->doc_type ?: '') . (string) ($order->supplier?->doc_number ?: ''));

            $rows[] = '2'
                . $this->fixed($document, 15)
                . $this->fixed($order->supplier?->name ?: 'Sin proveedor', 40)
                . $this->fixed($bank?->number, 20)
                . str_pad($this->amount($order->amount), 15, '0', STR_PAD_LEFT)
                . $this->fixed($order->code, 15);
        }

        $rows[] = '9'
            . str_pad((string) $orders->count(), 6, '0', STR_PAD_LEFT)
            . str_pad($this->amount($total), 15, '0', STR_PAD_LEFT);

        return implode("\r\n", $rows) . "\r\n";
    }

    private function renderInterbank($orders, string $processDate, string $batch, string $currency, float $total): string
    {
        $rows = [
            'INTERBANK|LOTE=' . $batch . '|FECHA=' . $processDate . '|MONEDA=' . $currency,
            'TIPO|DOCUMENTO|BENEFICIARIO|CUENTA|CCI|MONEDA|IMPORTE|REFERENCIA|CONCEPTO',
        ];

        foreach ($orders as $order) {
            $bank = $order->supplier?->bankAccount;

            $rows[] = implode('|', [
                $this->text($order->supplier?->doc_type),
                $this->text($order->supplier?->doc_number),
                $this->text($order->supplier?->name ?: 'Sin proveedor'),
                $this->text($bank?->number),
                $this->text($bank?->cci),
                $currency,
                $this->amount($order->amount),
                $this->text($order->code),
                $this->text($order->description),
            ]);
        }

        $rows[] = 'TOTAL||||' . $currency . '|' . $this->amount($total) . '||' . $orders->count() . ' registros';

        return implode("\r\n", $rows) . "\r\n";
    }

    private function amount($value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    /** Texto de una sola línea, sin el separador del archivo. */
    private function text($value): string
    {
        $value = str_replace(["\r", "\n", ';', '|'], ' ', (string) $value);

        return trim(preg_replace('/\s+/', ' ', $value));
    }

    /** Texto de ancho fijo recortado y rellenado a la derecha. */
    private function fixed($value, int $length): string
    {
        return str_pad(mb_substr($this->text($value), 0, $length), $length, ' ');
    }
}
