<?php

namespace Modules\Sales\Services;

use App\Helpers\NumberLetter;
use App\Models\Kardex;
use App\Models\Parameter;
use App\Models\Person;
use App\Models\PettyCash;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDocument;
use App\Models\SaleDocumentItem;
use App\Models\SaleDocumentType;
use App\Models\SaleProduct;
use App\Models\Serie;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Modules\Sales\Support\ElectronicDiscountMode;
use RuntimeException;

class QuickSaleService
{
    private float $igv;

    private string $ubl;

    private string $top;

    private float $icbper;

    private string $electronicDiscountMode;

    public function __construct(
        private readonly QuickSaleItemCalculator $calculator
    ) {
        $this->ubl = (string) Parameter::where('parameter_code', 'P000003')->value('value_default');
        $this->igv = (float) Parameter::where('parameter_code', 'P000001')->value('value_default');
        $this->top = (string) Parameter::where('parameter_code', 'P000002')->value('value_default');
        $this->icbper = (float) Parameter::where('parameter_code', 'P000004')->value('value_default');
        $this->electronicDiscountMode = ElectronicDiscountMode::resolve(
            Parameter::where('parameter_code', ElectronicDiscountMode::PARAMETER_CODE)->value('value_default')
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function register(array $data): array
    {
        $person = Person::findOrFail($data['client_id']);
        $sunatId = $data['document_type_id'];

        if ($sunatId === '80') {
            return $this->registerNotaVenta($data, $person);
        }

        if (in_array($sunatId, ['01', '03'], true)) {
            return $this->registerComprobanteElectronico($data, $person, $sunatId);
        }

        throw new RuntimeException('Tipo de comprobante no soportado.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function registerNotaVenta(array $data, Person $person): array
    {
        $localId = Auth::user()->local_id;
        $total = $this->calculateCartTotal($data['items']);
        $totalDiscount = $this->calculateCartGrossDiscountTotal($data['items']);
        $pettyCash = $this->ensurePettyCash($localId);
        $today = Carbon::now()->format('Y-m-d');

        $notaType = SaleDocumentType::where('sunat_id', '80')->first();
        $serie = Serie::where('document_type_id', $notaType?->id ?? 5)
            ->where('local_id', $localId)
            ->first();

        if (! $serie) {
            throw new RuntimeException('No hay serie de nota de venta configurada para este local.');
        }

        $sale = Sale::create([
            'sale_date' => $today,
            'user_id' => Auth::id(),
            'client_id' => $person->id,
            'local_id' => $localId,
            'total' => $total,
            'advancement' => $total,
            'total_discount' => $totalDiscount,
            'payments' => json_encode($this->mapPaymentsForSale($data['payments'])),
            'petty_cash_id' => $pettyCash->id,
            'physical' => 1,
        ]);

        $document = SaleDocument::create([
            'sale_id' => $sale->id,
            'serie_id' => $serie->id,
            'number' => str_pad($serie->number, 9, '0', STR_PAD_LEFT),
            'overall_total' => $total,
            'user_id' => Auth::id(),
            'invoice_type_doc' => '80',
            'invoice_serie' => $serie->description,
            'invoice_correlative' => $serie->number,
        ]);

        foreach ($data['items'] as $item) {
            $line = $this->resolveSaleLineItem($item);
            $this->createSaleProductRecord($sale, $line);
            $this->applyStockForLine($line, $document->id, $localId);
        }

        $serie->increment('number', 1);

        return $this->buildResponse($sale, $document, '80');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function registerComprobanteElectronico(array $data, Person $person, string $sunatId): array
    {
        $localId = Auth::user()->local_id;
        $total = $this->calculateCartTotal($data['items']);
        $pettyCash = $this->ensurePettyCash($localId);
        $today = Carbon::now()->format('Y-m-d');

        $docType = SaleDocumentType::where('sunat_id', $sunatId)->firstOrFail();
        $serie = Serie::where('document_type_id', $docType->id)
            ->where('local_id', $localId)
            ->first();

        if (! $serie) {
            throw new RuntimeException(
                'No hay serie configurada para '.strtolower($docType->description).' en este local.'
            );
        }

        $sale = Sale::create([
            'sale_date' => $today,
            'user_id' => Auth::id(),
            'client_id' => $person->id,
            'local_id' => $localId,
            'total' => $total,
            'advancement' => $total,
            'total_discount' => 0,
            'petty_cash_id' => $pettyCash->id,
            'physical' => 2,
        ]);

        $sale->payments = json_encode($this->mapPaymentsForSale($data['payments']));
        $sale->save();

        $typeOperation = $total > 700 ? '1001' : $this->top;
        $numberLetters = new NumberLetter;

        $document = SaleDocument::create([
            'sale_id' => $sale->id,
            'serie_id' => $serie->id,
            'number' => str_pad($serie->number, 9, '0', STR_PAD_LEFT),
            'status' => true,
            'client_type_doc' => $person->document_type_id,
            'client_number' => $person->number,
            'client_rzn_social' => $person->full_name,
            'client_address' => $person->address,
            'client_ubigeo_code' => $person->ubigeo,
            'client_ubigeo_description' => $person->ubigeo_description,
            'client_phone' => $person->telephone,
            'client_email' => $person->email,
            'invoice_ubl_version' => $this->ubl,
            'invoice_type_operation' => $typeOperation,
            'invoice_type_doc' => $sunatId,
            'invoice_serie' => $serie->description,
            'invoice_correlative' => $serie->number,
            'invoice_type_currency' => 'PEN',
            'invoice_broadcast_date' => $today,
            'invoice_due_date' => $today,
            'invoice_send_date' => $today,
            'invoice_legend_code' => '1000',
            'invoice_legend_description' => $numberLetters->convertToLetter($total),
            'invoice_status' => 'Pendiente',
            'user_id' => Auth::id(),
            'overall_total' => $total,
            'forma_pago' => 'Contado',
            'status_pay' => true,
        ]);

        $mtoOperTaxed = 0;
        $mtoIgv = 0;
        $totalIcbper = 0;
        $documentTotal = 0;
        $documentDiscountTotal = 0;
        $grossDiscountTotal = $this->calculateCartGrossDiscountTotal($data['items']);

        foreach ($data['items'] as $item) {
            $line = $this->resolveSaleLineItem($item);
            $produc = $this->mapCartItemToDocumentLine($line);
            $tax = $this->calculator->calculateTaxedLine(
                $produc,
                $this->igv,
                $this->icbper,
                $this->electronicDiscountMode
            );

            SaleDocumentItem::create([
                'document_id' => $document->id,
                'product_id' => $line['product_id'],
                'cod_product' => $produc['interne'],
                'decription_product' => $produc['description'],
                'unit_type' => $produc['unit_type'],
                'quantity' => $produc['quantity'],
                'mto_base_igv' => $tax['mto_base_igv'],
                'percentage_igv' => $this->igv,
                'igv' => $tax['igv'],
                'total_tax' => $tax['total_tax'],
                'type_afe_igv' => $produc['afe_igv'],
                'icbper' => $tax['icbper'],
                'factor_icbper' => $tax['porcentage_item_icbper'],
                'mto_value_sale' => $tax['value_sale'],
                'mto_value_unit' => $tax['value_unit'],
                'mto_price_unit' => $tax['unit_price'],
                'price_sale' => $tax['price_sale'],
                'mto_total' => $tax['line_total'],
                'mto_discount' => $tax['mto_discount'],
                'json_discounts' => json_encode($tax['array_discounts']),
                'entity_name_product' => $line['entity_name_product'],
            ]);

            $this->createSaleProductRecord($sale, $line, $produc, $tax['line_total']);
            $this->applyStockForLine($line, $document->id, $localId);

            $mtoIgv += $tax['igv'];
            $totalIcbper += $tax['icbper'];
            $mtoOperTaxed += $tax['value_sale'];
            $documentTotal += $tax['total_item'];
            $documentDiscountTotal += $tax['mto_discount'];
        }

        $totalTaxes = $mtoIgv + $totalIcbper;
        $subtotal = $totalTaxes + $mtoOperTaxed;
        $ttotal = round($documentTotal, 1);
        $difference = abs($ttotal - $subtotal);
        $rounding = number_format($difference, 2);

        $document->invoice_mto_oper_taxed = $mtoOperTaxed;
        $document->invoice_mto_igv = $mtoIgv;
        $document->invoice_icbper = $totalIcbper;
        $document->invoice_total_taxes = $totalTaxes;
        $document->invoice_value_sale = $mtoOperTaxed;
        $document->invoice_subtotal = $subtotal;
        $document->invoice_mto_discount = round($documentDiscountTotal, 2);
        $document->invoice_rounding = $rounding;
        $document->invoice_mto_imp_sale = $ttotal;
        $document->overall_total = $ttotal;
        $document->save();

        $sale->update([
            'total' => $ttotal,
            'advancement' => $ttotal,
            'total_discount' => round($grossDiscountTotal, 2),
        ]);

        $serie->increment('number', 1);

        return $this->buildResponse($sale, $document, $sunatId);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function calculateCartTotal(array $items): float
    {
        return round(collect($items)->sum(function ($item) {
            $qty = (float) ($item['qty'] ?? 0);
            $price = (float) ($item['price'] ?? 0);
            $discount = min((float) ($item['discount'] ?? 0), $price);

            return max(0, ($price - $discount) * $qty);
        }), 2);
    }

    /**
     * Descuento bruto total: descuento unitario x cantidad.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    private function calculateCartGrossDiscountTotal(array $items): float
    {
        return round(collect($items)->sum(function ($item) {
            $qty = (float) ($item['qty'] ?? 0);
            $price = (float) ($item['price'] ?? 0);
            $discount = min((float) ($item['discount'] ?? 0), $price);

            return $discount * $qty;
        }), 2);
    }

    /**
     * @param  array<int, array<string, mixed>>  $payments
     * @return array<int, array<string, mixed>>
     */
    private function mapPaymentsForSale(array $payments): array
    {
        return collect($payments)->map(function ($payment) {
            return [
                'type' => $payment['payment_method_id'],
                'amount' => round((float) $payment['amount'], 2),
                'reference' => $payment['reference'] ?? null,
            ];
        })->values()->all();
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function resolveSaleLineItem(array $item): array
    {
        $productData = $item['product'] ?? [];
        if (! is_array($productData)) {
            $productData = (array) $productData;
        }

        $entityClass = $item['entity_name_product'] ?? Product::class;
        $productId = (int) ($item['id'] ?? $productData['id'] ?? 0);

        $productJson = $this->encodeProductSnapshot($entityClass, $productId, $productData);

        $qty = (float) $item['qty'];
        $price = (float) $item['price'];
        $discount = min((float) ($item['discount'] ?? 0), $price);
        $lineTotal = round(max(0, ($price - $discount) * $qty), 2);

        $saleProductRow = [
            'id' => $productId,
            'interne' => $productData['interne'] ?? null,
            'description' => $productData['description'] ?? null,
            'price' => $price,
            'discount' => $discount,
            'quantity' => $qty,
            'total' => $lineTotal,
        ];

        $productData = json_decode($productJson, true) ?? $productData;

        return [
            'product_id' => $productId,
            'entity_name_product' => $entityClass,
            'product_data' => $productData,
            'product_json' => $productJson,
            'sale_product_json' => json_encode($saleProductRow),
            'qty' => $qty,
            'price' => $price,
            'discount' => $discount,
            'line_total' => $lineTotal,
            'is_product' => (bool) ($productData['is_product'] ?? false),
        ];
    }

    /**
     * Snapshot completo del producto (mismo criterio que SaleDocumentController).
     *
     * @param  array<string, mixed>  $productData
     */
    private function encodeProductSnapshot(string $entityClass, int $productId, array $productData): string
    {
        if ($this->isAppProductEntity($entityClass) && $productId > 0) {
            $model = Product::find($productId);
            if ($model) {
                return json_encode(array_merge($model->toArray(), $productData));
            }
        }

        return json_encode($productData);
    }

    private function isAppProductEntity(string $entityClass): bool
    {
        return $entityClass === Product::class || $entityClass === 'App\Models\Product';
    }

    /**
     * @param  array<string, mixed>  $line
     */
    private function createSaleProductRecord(
        Sale $sale,
        array $line,
        ?array $saleProductOverride = null,
        ?float $lineTotalOverride = null
    ): void {
        SaleProduct::create([
            'sale_id' => $sale->id,
            'product_id' => $line['product_id'],
            'product' => $line['product_json'],
            'saleProduct' => json_encode($saleProductOverride ?? json_decode($line['sale_product_json'], true)),
            'price' => $line['price'],
            'discount' => $line['discount'],
            'quantity' => $line['qty'],
            'total' => $lineTotalOverride ?? $line['line_total'],
            'entity_name_product' => $line['entity_name_product'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $line
     */
    private function applyStockForLine(array $line, int $documentId, int $localId): void
    {
        if (! $this->isAppProductEntity($line['entity_name_product']) || ! $line['is_product']) {
            return;
        }

        $product = Product::find($line['product_id']);
        if ($product) {
            $this->applyProductStockOut($product, $line['qty'], $documentId, $localId, null);
        }
    }

    /**
     * @param  array<string, mixed>  $line
     * @return array<string, mixed>
     */
    private function mapCartItemToDocumentLine(array $line): array
    {
        $productData = $line['product_data'];

        return [
            'id' => $line['product_id'],
            'interne' => $productData['interne'] ?? '',
            'description' => $productData['description'] ?? '',
            'unit_type' => $productData['type_unit_measure_id'] ?? 'NIU',
            'quantity' => $line['qty'],
            'unit_price' => $line['price'],
            'discount' => $line['discount'],
            'afe_igv' => $productData['type_sale_affectation_id'] ?? '10',
            'icbper' => (int) ($productData['icbper'] ?? 0),
            'is_product' => $line['is_product'],
            'size' => $productData['size'] ?? null,
        ];
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

    private function applyProductStockOut(
        Product $product,
        float $quantity,
        int $documentId,
        int $localId,
        ?string $size
    ): void {
        if (! $product->is_product) {
            return;
        }

        Kardex::create([
            'date_of_issue' => Carbon::now()->format('Y-m-d'),
            'motion' => 'sale',
            'product_id' => $product->id,
            'local_id' => $localId,
            'quantity' => $quantity * (-1),
            'document_id' => $documentId,
            'document_entity' => SaleDocument::class,
            'description' => 'Venta rápida',
        ]);

        $product->decrement('stock', $quantity);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildResponse(Sale $sale, SaleDocument $document, string $sunatId): array
    {
        $urls = [];

        if ($sunatId === '80') {
            $urls['ticket_url'] = route('ticketpdf_sales', ['id' => $sale->id]);
            $urls['pdf_a4_url'] = route('printA4pdf_sales', ['id' => $sale->id]);
        } else {
            $urls['pdf_a4_url'] = route('saledocuments_download', [
                'id' => $document->id,
                'type' => $sunatId,
                'file' => 'PDF',
                'format' => 'A4',
            ]);
            $urls['ticket_url'] = route('saledocuments_download', [
                'id' => $document->id,
                'type' => $sunatId,
                'file' => 'PDF',
                'format' => 't80',
            ]);
        }

        return array_merge([
            'sale_id' => $sale->id,
            'document_id' => $document->id,
            'invoice_type_doc' => $sunatId,
            'invoice_serie' => $document->invoice_serie,
            'invoice_correlative' => $document->invoice_correlative,
        ], $urls);
    }
}
