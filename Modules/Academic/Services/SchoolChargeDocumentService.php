<?php

namespace Modules\Academic\Services;

use App\Helpers\NumberLetter;
use App\Models\Parameter;
use App\Models\PaymentMethod;
use App\Models\Person;
use App\Models\PettyCash;
use App\Models\Sale;
use App\Models\SaleDocument;
use App\Models\SaleDocumentItem;
use App\Models\SaleDocumentType;
use App\Models\SaleProduct;
use App\Models\Serie;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Entities\AcaSchoolCharge;
use Modules\Academic\Entities\AcaSchoolEnrollment;
use Modules\Academic\Entities\AcaSchoolFeeType;
use Modules\Academic\Entities\AcaSchoolPaymentSchedule;
use Modules\Sales\Services\QuickSaleItemCalculator;
use RuntimeException;

/**
 * Registra el cobro de uno o mas conceptos del colegio y, opcionalmente,
 * emite el comprobante de pago reutilizando el motor de ventas de la
 * plataforma (sales + sale_products + sale_documents + series), igual que
 * cursos y suscripciones:
 *
 * - 80: Nota de venta (documento fisico, sin SUNAT, PDF por venta).
 * - 03: Boleta electronica (con IGV).
 * - 01: Factura electronica (con IGV, cliente con RUC).
 * - sin comprobante: solo registra los cobros escolares.
 *
 * Cada cobro queda vinculado a su venta y comprobante
 * (aca_school_charges.sale_id / sale_document_id).
 */
class SchoolChargeDocumentService
{
    private float $igv;

    private string $ubl;

    private string $top;

    public function __construct(private readonly QuickSaleItemCalculator $calculator)
    {
        $this->ubl = (string) Parameter::where('parameter_code', 'P000003')->value('value_default');
        $this->igv = (float) Parameter::where('parameter_code', 'P000001')->value('value_default');
        $this->top = (string) Parameter::where('parameter_code', 'P000002')->value('value_default');
    }

    /**
     * Registra los cobros y emite el comprobante si corresponde.
     *
     * @param  array<int, array{type: string, id: ?int, fee_type_id: ?int, amount: float, detail: ?string}>  $items
     * @param  array<string, mixed>  $client
     * @return array<string, mixed>
     */
    public function register(AcaSchoolEnrollment $enrollment, array $items, ?string $documentType, ?int $personId, array $client, int $paymentMethodId, ?string $reference, string $paidAt): array
    {
        return DB::transaction(function () use ($enrollment, $items, $documentType, $personId, $client, $paymentMethodId, $reference, $paidAt) {
            return $this->doRegister($enrollment, $items, $documentType, $personId, $client, $paymentMethodId, $reference, $paidAt);
        });
    }

    private function doRegister(AcaSchoolEnrollment $enrollment, array $items, ?string $documentType, ?int $personId, array $client, int $paymentMethodId, ?string $reference, string $paidAt): array
    {
        $rows = collect($items)->map(fn (array $item) => $this->resolveRow($enrollment, $item))->values();

        $total = round((float) $rows->sum('amount'), 2);

        if ($total <= 0) {
            throw new RuntimeException('El total a cobrar debe ser mayor a cero.');
        }

        $documentType = $documentType !== null && $documentType !== '' && $documentType !== 'sin' ? $documentType : null;

        if ($documentType !== null && ! in_array($documentType, ['80', '01', '03'], true)) {
            throw new RuntimeException('Tipo de comprobante no soportado.');
        }

        $result = [
            'sale_id' => null,
            'document_id' => null,
            'invoice_type_doc' => null,
            'invoice_serie' => null,
            'invoice_correlative' => null,
            'total' => $total,
            'pdf_a4_url' => null,
            'ticket_url' => null,
            'message' => 'Cobro registrado correctamente',
        ];

        if ($documentType === null) {
            // Sin comprobante: solo se registran los cobros escolares.
            foreach ($rows as $row) {
                $this->payRow($enrollment, $row, $paymentMethodId, $reference, $paidAt, null, null);
            }

            return $result;
        }

        $localId = (int) Auth::user()->local_id;
        $person = $personId ? Person::findOrFail($personId) : null;

        $docType = SaleDocumentType::where('sunat_id', $documentType)->first();
        if (! $docType) {
            throw new RuntimeException('El tipo de comprobante no está configurado en el sistema.');
        }

        $serie = Serie::where('document_type_id', $docType->id)
            ->where('local_id', $localId)
            ->first();

        if (! $serie) {
            throw new RuntimeException('No hay serie configurada para '.strtolower($docType->description).' en este local.');
        }

        $date = Carbon::parse($paidAt)->format('Y-m-d');
        $pettyCash = $this->ensurePettyCash($localId);

        $sale = Sale::create([
            'sale_date' => $date,
            'user_id' => Auth::id(),
            'client_id' => $person?->id,
            'local_id' => $localId,
            'total' => $total,
            'advancement' => $total,
            'total_discount' => 0,
            'payments' => json_encode([[ // mismo formato que QuickSaleService (el controlador del PDF hace json_decode sobre el valor)
                'type' => $paymentMethodId,
                'amount' => $total,
                'reference' => $reference,
            ]]),
            'petty_cash_id' => $pettyCash->id,
            'physical' => $documentType === '80' ? 1 : 2,
        ]);

        if ($documentType === '80') {
            $document = SaleDocument::create([
                'sale_id' => $sale->id,
                'serie_id' => $serie->id,
                'number' => str_pad((string) $serie->number, 9, '0', STR_PAD_LEFT),
                'overall_total' => $total,
                'user_id' => Auth::id(),
                'invoice_type_doc' => '80',
                'invoice_serie' => $serie->description,
                'invoice_correlative' => $serie->number,
            ]);

            // Items de la venta para el PDF del ticket/A4 (mismo patron que
            // QuickSaleService::registerNotaVenta): cada concepto es una linea.
            foreach ($rows as $row) {
                SaleProduct::create([
                    'sale_id' => $sale->id,
                    'product' => json_encode([
                        'description' => $row['description'],
                        'interne' => null,
                        'size' => null,
                        'unit_type' => 'NIU',
                    ]),
                    'saleProduct' => json_encode([
                        'interne' => null,
                        'description' => $row['description'],
                        'unit_type' => 'NIU',
                        'quantity' => 1,
                        'total' => $row['amount'],
                    ]),
                    'price' => $row['amount'],
                    'discount' => 0,
                    'quantity' => 1,
                    'total' => $row['amount'],
                    'entity_name_product' => AcaSchoolCharge::class,
                    'advancement' => $row['amount'],
                ]);
            }
        } else {
            $document = $this->createElectronicDocument($sale->id, $serie, $documentType, $person, $client, $rows, $total, $date);
        }

        $serie->increment('number', 1);

        foreach ($rows as $row) {
            $this->payRow($enrollment, $row, $paymentMethodId, $reference, $paidAt, $sale->id, $document->id);
        }

        // Tesoreria: ingresos de la venta segun metodo de pago (best-effort).
        try {
            \Modules\Treasury\Services\TreasuryHooks::recordSaleIncomes($sale);
        } catch (\Throwable $e) {
            report($e);
        }

        $result['sale_id'] = $sale->id;
        $result['document_id'] = $document->id;
        $result['invoice_type_doc'] = $documentType;
        $result['invoice_serie'] = $serie->description;
        $result['invoice_correlative'] = $document->invoice_correlative;

        if ($documentType === '80') {
            $result['pdf_a4_url'] = route('printA4pdf_sales', ['id' => $sale->id]);
            $result['ticket_url'] = route('ticketpdf_sales', ['id' => $sale->id]);
        } else {
            $result['pdf_a4_url'] = route('saledocuments_download', ['id' => $document->id, 'type' => $documentType, 'file' => 'PDF', 'format' => 'A4']);
            $result['ticket_url'] = route('saledocuments_download', ['id' => $document->id, 'type' => $documentType, 'file' => 'PDF', 'format' => 't80']);
        }

        $label = ['80' => 'Nota de venta', '03' => 'Boleta', '01' => 'Factura'][$documentType];
        $result['message'] = $label.' emitida: '.$serie->description.'-'.str_pad((string) $document->invoice_correlative, 8, '0', STR_PAD_LEFT);

        return $result;
    }

    /**
     * Resuelve un item del formulario a la fila de cobro a pagar.
     *
     * @param  array{type: string, id: ?int, fee_type_id: ?int, amount: float, detail: ?string}  $item
     * @return array<string, mixed>
     */
    private function resolveRow(AcaSchoolEnrollment $enrollment, array $item): array
    {
        $amount = round((float) ($item['amount'] ?? 0), 2);

        if ($amount <= 0) {
            throw new RuntimeException('Todos los conceptos seleccionados deben tener monto mayor a cero.');
        }

        switch ($item['type'] ?? null) {
            case 'charge':
                $charge = AcaSchoolCharge::where('id', (int) ($item['id'] ?? 0))
                    ->where('enrollment_id', $enrollment->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($charge->status === AcaSchoolCharge::STATUS_ANULADO) {
                    throw new RuntimeException('El cobro "'.$charge->description.'" está anulado.');
                }
                if ($charge->status === AcaSchoolCharge::STATUS_PAGADO) {
                    throw new RuntimeException('El cobro "'.$charge->description.'" ya fue pagado.');
                }

                return [
                    'charge' => $charge,
                    'schedule' => null,
                    'fee_type_id' => $charge->fee_type_id,
                    'description' => $charge->description,
                    'amount' => $amount,
                ];

            case 'schedule':
                $schedule = AcaSchoolPaymentSchedule::where('id', (int) ($item['id'] ?? 0))
                    ->where('enrollment_id', $enrollment->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($schedule->isPaid()) {
                    throw new RuntimeException('La cuota '.$schedule->installment.' ya fue pagada.');
                }

                $feeTypeName = $schedule->feeType?->name ?? 'Mensualidad';

                return [
                    'charge' => null,
                    'schedule' => $schedule,
                    'fee_type_id' => $schedule->fee_type_id,
                    'description' => $feeTypeName.' - Cuota '.$schedule->installment.' ('.$schedule->due_date->format('d/m/Y').')',
                    'amount' => $amount,
                ];

            case 'concept':
                $feeType = AcaSchoolFeeType::where('id', (int) ($item['fee_type_id'] ?? 0))
                    ->where('status', true)
                    ->first();

                if (! $feeType) {
                    throw new RuntimeException('El concepto seleccionado no existe.');
                }

                $detail = trim((string) ($item['detail'] ?? ''));

                return [
                    'charge' => null,
                    'schedule' => null,
                    'fee_type_id' => $feeType->id,
                    'description' => $feeType->name.($detail !== '' ? ' - '.$detail : '').' '.($enrollment->year?->year ?? ''),
                    'amount' => $amount,
                ];

            default:
                throw new RuntimeException('Concepto de cobro no válido.');
        }
    }

    /**
     * Marca la fila como pagada: crea o actualiza el cobro escolar y,
     * si es cuota, la vincula al cobro.
     */
    private function payRow(AcaSchoolEnrollment $enrollment, array $row, int $paymentMethodId, ?string $reference, string $paidAt, ?int $saleId, ?int $saleDocumentId): void
    {
        $charge = $row['charge'] ?? null;

        if (! $charge) {
            $charge = new AcaSchoolCharge([
                'school_id' => $enrollment->school_id,
                'enrollment_id' => $enrollment->id,
                'fee_type_id' => $row['fee_type_id'],
                'payment_schedule_id' => $row['schedule']?->id,
            ]);
            $charge->description = $row['description'];
        }

        $charge->amount = $row['amount'];
        $charge->status = AcaSchoolCharge::STATUS_PAGADO;
        $charge->paid_at = $paidAt;
        $charge->payment_method = $this->schoolPaymentMethod($paymentMethodId);
        $charge->reference = $reference;
        $charge->sale_id = $saleId;
        $charge->sale_document_id = $saleDocumentId;
        $charge->save();

        if ($row['schedule']) {
            $row['schedule']->update([
                'charge_id' => $charge->id,
                'paid_at' => $paidAt,
            ]);
        }
    }

    /**
     * Documento electronico completo (boleta 03 / factura 01) con IGV
     * por item, totales de cabecera y leyenda en letras.
     *
     * @param  iterable<int, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $client
     */
    private function createElectronicDocument(int $saleId, Serie $serie, string $sunatId, ?Person $person, array $client, iterable $rows, float $total, string $date): SaleDocument
    {
        $clientTypeDoc = (int) ($client['document_type_id'] ?? $person?->document_type_id ?? 1);
        $clientNumber = trim((string) ($client['number'] ?? $person?->number ?? ''));
        $clientName = trim((string) ($client['full_name'] ?? $person?->full_name ?? ''));
        $clientAddress = trim((string) ($client['address'] ?? $person?->address ?? ''));

        if ($clientNumber === '' || $clientName === '') {
            throw new RuntimeException('Indica el documento y el nombre del cliente para el comprobante.');
        }

        if ($sunatId === '01' && ($clientTypeDoc !== 6 || strlen($clientNumber) !== 11)) {
            throw new RuntimeException('Para emitir una factura el cliente debe tener RUC (11 dígitos).');
        }

        $typeOperation = $total > 700 ? '1001' : $this->top;
        $letters = new NumberLetter;

        $document = SaleDocument::create([
            'sale_id' => $saleId,
            'serie_id' => $serie->id,
            'number' => str_pad((string) $serie->number, 9, '0', STR_PAD_LEFT),
            'status' => true,
            'client_type_doc' => $clientTypeDoc,
            'client_number' => $clientNumber,
            'client_rzn_social' => $clientName,
            'client_address' => $clientAddress,
            'client_ubigeo_code' => $client['ubigeo'] ?? $person?->ubigeo,
            'client_ubigeo_description' => $client['ubigeo_description'] ?? $person?->ubigeo_description,
            'client_phone' => $client['telephone'] ?? $person?->telephone,
            'client_email' => $client['email'] ?? $person?->email,
            'invoice_ubl_version' => $this->ubl,
            'invoice_type_operation' => $typeOperation,
            'invoice_type_doc' => $sunatId,
            'invoice_serie' => $serie->description,
            'invoice_correlative' => $serie->number,
            'invoice_type_currency' => 'PEN',
            'invoice_broadcast_date' => $date,
            'invoice_due_date' => $date,
            'invoice_send_date' => $date,
            'invoice_legend_code' => '1000',
            'invoice_legend_description' => $letters->convertToLetter($total),
            'invoice_status' => 'Pendiente',
            'user_id' => Auth::id(),
            'overall_total' => $total,
            'forma_pago' => 'Contado',
            'status_pay' => true,
        ]);

        $mtoOperTaxed = 0;
        $mtoIgv = 0;
        $documentTotal = 0;

        foreach ($rows as $row) {
            $tax = $this->calculator->calculateTaxedLine([
                'unit_price' => $row['amount'],
                'quantity' => 1,
                'discount' => 0,
                'afe_igv' => '10',
                'icbper' => 0,
                'unit_type' => 'ZZ',
                'description' => $row['description'],
                'interne' => 'ACA',
            ], $this->igv, 0);

            $chargeId = $row['charge']?->id ?? $row['schedule']?->id;

            SaleDocumentItem::create([
                'document_id' => $document->id,
                'product_id' => $chargeId,
                'cod_product' => 'ACA'.$chargeId,
                'decription_product' => $row['description'],
                'unit_type' => 'ZZ',
                'quantity' => 1,
                'mto_base_igv' => $tax['mto_base_igv'],
                'percentage_igv' => $this->igv,
                'igv' => $tax['igv'],
                'total_tax' => $tax['total_tax'],
                'type_afe_igv' => '10',
                'icbper' => $tax['icbper'],
                'factor_icbper' => $tax['porcentage_item_icbper'],
                'mto_value_sale' => $tax['value_sale'],
                'mto_value_unit' => $tax['value_unit'],
                'mto_price_unit' => $tax['unit_price'],
                'price_sale' => $tax['price_sale'],
                'mto_total' => $tax['line_total'],
                'mto_discount' => $tax['mto_discount'],
                'json_discounts' => json_encode($tax['array_discounts']),
                'entity_name_product' => AcaSchoolCharge::class,
            ]);

            SaleProduct::create([
                'sale_id' => $saleId,
                'product_id' => $chargeId,
                'product' => json_encode([
                    'id' => $chargeId,
                    'description' => $row['description'],
                    'amount' => $row['amount'],
                ]),
                'saleProduct' => json_encode([
                    'description' => $row['description'],
                    'price' => $row['amount'],
                    'quantity' => 1,
                ]),
                'price' => $row['amount'],
                'discount' => 0,
                'quantity' => 1,
                'total' => $row['amount'],
                'entity_name_product' => AcaSchoolCharge::class,
            ]);

            $mtoIgv += $tax['igv'];
            $mtoOperTaxed += $tax['value_sale'];
            $documentTotal += $tax['line_total'];
        }

        $totalTaxes = $mtoIgv;
        $subtotal = $totalTaxes + $mtoOperTaxed;
        $ttotal = round($documentTotal, 1);
        $rounding = number_format(abs($ttotal - $subtotal), 2);

        $document->update([
            'invoice_mto_oper_taxed' => $mtoOperTaxed,
            'invoice_mto_igv' => $mtoIgv,
            'invoice_icbper' => 0,
            'invoice_total_taxes' => $totalTaxes,
            'invoice_value_sale' => $mtoOperTaxed,
            'invoice_subtotal' => $subtotal,
            'invoice_rounding' => $rounding,
            'invoice_mto_imp_sale' => $ttotal,
        ]);

        return $document;
    }

    private function ensurePettyCash(int $localId): PettyCash
    {
        return PettyCash::firstOrCreate([
            'user_id' => Auth::id(),
            'state' => 1,
            'local_sale_id' => $localId,
        ], [
            'date_opening' => Carbon::now()->format('Y-m-d'),
            'time_opening' => date('H:i:s'),
            'income' => 0,
        ]);
    }

    /**
     * Metodo de pago de la tabla payment_methods -> enum del cobro escolar.
     */
    private function schoolPaymentMethod(int $paymentMethodId): string
    {
        $description = mb_strtolower((string) PaymentMethod::where('id', $paymentMethodId)->value('description'));

        if (str_contains($description, 'yape')) {
            return 'yape';
        }
        if (str_contains($description, 'plin')) {
            return 'plin';
        }
        if (str_contains($description, 'efectivo')) {
            return 'efectivo';
        }
        if (str_contains($description, 'transfer') || str_contains($description, 'bcp') || str_contains($description, 'bbva')) {
            return 'transferencia';
        }

        return 'otro';
    }
}
