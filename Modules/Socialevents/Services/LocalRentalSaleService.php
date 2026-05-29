<?php

namespace Modules\Socialevents\Services;

use App\Models\PettyCash;
use App\Models\Sale;
use App\Models\SaleDocument;
use App\Models\SaleDocumentType;
use App\Models\SaleProduct;
use App\Models\Serie;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Socialevents\Entities\EvenLocalRental;
use Modules\Socialevents\Entities\EvenLocalRentalPayment;
use RuntimeException;

class LocalRentalSaleService
{
    public function __construct(
        private readonly LocalRentalPricingService $pricingService
    ) {}

    /**
     * Monto ya cobrado al crear la reserva pero aún sin fila en pagos / NV.
     */
    public function unformalizedAdvanceAmount(EvenLocalRental $rental): float
    {
        $recorded = (float) $rental->payments()->sum('amount');

        return max(0, round((float) $rental->paid_amount - $recorded, 2));
    }

    /**
     * Emite NV del adelanto registrado al crear la reserva (sin volver a sumar paid_amount).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function formalizeAdvance(EvenLocalRental $rental, array $data): array
    {
        return DB::transaction(function () use ($rental, $data) {
            $amount = $this->unformalizedAdvanceAmount($rental);

            if ($amount <= 0) {
                throw new RuntimeException('No hay adelanto pendiente de formalizar con nota de venta.');
            }

            $payments = [[
                'payment_method_id' => (int) $data['payment_method_id'],
                'amount' => $amount,
                'reference' => $data['reference'] ?? null,
            ]];

            $sale = $this->createNotaVenta($rental, $amount, $payments, 'adelanto');

            $payment = EvenLocalRentalPayment::create([
                'rental_id' => $rental->id,
                'amount' => $amount,
                'payment_method_id' => (int) $data['payment_method_id'],
                'reference' => $data['reference'] ?? null,
                'sale_id' => $sale->id,
                'registered_by' => Auth::id(),
                'notes' => $data['notes'] ?? 'Adelanto al reservar',
            ]);

            return [
                'success' => true,
                'payment' => $payment->load(['sale', 'registeredBy', 'paymentMethod']),
                'rental' => $rental->fresh(['local', 'customer']),
                'sale_id' => $sale->id,
                'pdf_a4_url' => route('printA4pdf_sales', ['id' => $sale->id]),
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function registerPayment(EvenLocalRental $rental, array $data): array
    {
        return DB::transaction(function () use ($rental, $data) {
            $amount = round((float) $data['amount'], 2);
            $emitNote = $data['emit_note'] ?? true;
            $saleId = null;

            if ($emitNote) {
                $sale = $this->createNotaVenta($rental, $amount, $data['payments'] ?? [], 'abono');
                $saleId = $sale->id;
            }

            $primaryPayment = collect($data['payments'] ?? [])->first();

            $payment = EvenLocalRentalPayment::create([
                'rental_id' => $rental->id,
                'amount' => $amount,
                'payment_method_id' => $primaryPayment['payment_method_id'] ?? null,
                'reference' => $primaryPayment['reference'] ?? null,
                'sale_id' => $saleId,
                'registered_by' => Auth::id(),
                'notes' => $data['notes'] ?? null,
            ]);

            $this->pricingService->applyPayment($rental->fresh(), $amount);

            $response = [
                'success' => true,
                'payment' => $payment->load(['sale', 'registeredBy']),
                'rental' => $rental->fresh(['local', 'customer']),
            ];

            if ($saleId) {
                $response['sale_id'] = $saleId;
                $response['pdf_a4_url'] = route('printA4pdf_sales', ['id' => $saleId]);
            }

            return $response;
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $payments
     */
    private function createNotaVenta(EvenLocalRental $rental, float $amount, array $payments, string $kind = 'abono'): Sale
    {
        $localId = (int) $rental->local_id;
        $rental->loadMissing(['local', 'customer', 'extraCharges']);

        $notaType = SaleDocumentType::where('sunat_id', '80')->first();
        $serie = Serie::where('document_type_id', $notaType?->id ?? 5)
            ->where('local_id', $localId)
            ->first();

        if (! $serie) {
            throw new RuntimeException('No hay serie de nota de venta configurada para el local de esta reserva.');
        }

        $pettyCash = PettyCash::firstOrCreate([
            'user_id' => Auth::id(),
            'state' => 1,
            'local_sale_id' => $localId,
        ], [
            'date_opening' => Carbon::now()->format('Y-m-d'),
            'time_opening' => date('H:i:s'),
            'income' => 0,
        ]);

        $today = Carbon::now()->format('Y-m-d');
        $label = $kind === 'adelanto' ? 'Adelanto alquiler local' : 'Abono alquiler local';
        $description = sprintf(
            '%s — Reserva #%d — %s',
            $label,
            $rental->id,
            $rental->local?->description ?? 'Local'
        );

        $sale = Sale::create([
            'sale_date' => $today,
            'user_id' => Auth::id(),
            'client_id' => $rental->customer_id,
            'local_id' => $localId,
            'total' => $amount,
            'advancement' => $amount,
            'total_discount' => 0,
            'payments' => json_encode($this->mapPaymentsForSale($payments)),
            'petty_cash_id' => $pettyCash->id,
            'physical' => 1,
        ]);

        $document = SaleDocument::create([
            'sale_id' => $sale->id,
            'serie_id' => $serie->id,
            'number' => str_pad($serie->number, 9, '0', STR_PAD_LEFT),
            'overall_total' => $amount,
            'user_id' => Auth::id(),
            'invoice_type_doc' => '80',
            'invoice_serie' => $serie->description,
            'invoice_correlative' => $serie->number,
            'invoice_razon_social' => $rental->customer?->full_name,
        ]);

        $linePayload = [
            'id' => $rental->id,
            'description' => $description,
            'amount' => $amount,
            'quantity' => 1,
        ];

        SaleProduct::create([
            'sale_id' => $sale->id,
            'product' => json_encode($linePayload),
            'saleProduct' => json_encode($linePayload),
            'price' => $amount,
            'discount' => 0,
            'quantity' => 1,
            'total' => $amount,
            'entity_name_product' => EvenLocalRental::class,
            'advancement' => $amount,
        ]);

        $serie->increment('number', 1);

        return $sale;
    }

    /**
     * @param  array<int, array<string, mixed>>  $payments
     * @return array<int, array<string, mixed>>
     */
    private function mapPaymentsForSale(array $payments): array
    {
        return collect($payments)->map(function ($payment) {
            return [
                'payment_method_id' => (int) ($payment['payment_method_id'] ?? 0),
                'amount' => round((float) ($payment['amount'] ?? 0), 2),
                'reference' => $payment['reference'] ?? null,
            ];
        })->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function buildPaymentsPayload(EvenLocalRental $rental): array
    {
        $rental->load([
            'local',
            'customer',
            'extraCharges',
            'payments.sale',
            'payments.registeredBy',
            'payments.paymentMethod',
        ]);

        $unformalizedAdvance = $this->unformalizedAdvanceAmount($rental);

        return [
            'rental' => $rental,
            'summary' => [
                'total_price' => (float) $rental->total_price,
                'paid_amount' => (float) $rental->paid_amount,
                'balance_amount' => (float) $rental->balance_amount,
                'payment_status' => $rental->payment_status,
                'unformalized_advance' => $unformalizedAdvance,
            ],
            'payments' => $rental->payments,
            'line_preview' => $this->buildLinePreview($rental),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildLinePreview(EvenLocalRental $rental): array
    {
        $lines = [
            [
                'description' => sprintf('Alquiler base (%s hrs)', $rental->total_hours),
                'amount' => (float) $rental->base_amount,
            ],
        ];

        foreach ($rental->extraCharges as $charge) {
            $lines[] = [
                'description' => $charge->description,
                'amount' => (float) $charge->amount,
            ];
        }

        return $lines;
    }
}
