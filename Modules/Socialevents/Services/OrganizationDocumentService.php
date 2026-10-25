<?php

namespace Modules\Socialevents\Services;

use App\Helpers\Invoice\Documents\Boleta;
use App\Helpers\Invoice\Documents\Factura;
use App\Helpers\NumberLetter;
use App\Models\LocalSale;
use App\Models\Parameter;
use App\Models\Person;
use App\Models\PettyCash;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDocument;
use App\Models\SaleDocumentType;
use App\Models\Serie;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Socialevents\Entities\SocialeventOrganizationDocumentRequest;

class OrganizationDocumentService
{
    public function __construct(
        private SportsOrganizationService $sports,
        private OrganizationTeamService $team
    ) {}

    /**
     * Registra la solicitud del propietario. No emite todavía: el
     * superadministrador revisa y emite desde el panel.
     *
     * @param array<string, mixed> $billing
     * @param array<int, string> $channels
     */
    public function request(
        $actor,
        int $organizationId,
        string $purchaseType,
        int $purchaseId,
        int $documentTypeId,
        array $billing,
        array $channels = []
    ): SocialeventOrganizationDocumentRequest {
        abort_unless($this->team->isOwner($actor), 403, 'Solo el propietario puede solicitar comprobantes.');

        $purchase = $this->purchase($organizationId, $purchaseType, $purchaseId);
        $type = SaleDocumentType::query()->findOrFail($documentTypeId);
        abort_unless(in_array((string) $type->sunat_id, ['01', '03', '80'], true), 422, 'Tipo de comprobante no válido.');

        $billing = $this->normalizeBilling($billing);
        $this->validateBilling($type, $billing);

        $existing = SocialeventOrganizationDocumentRequest::query()
            ->where('organization_id', $organizationId)
            ->where('purchase_type', $purchaseType)
            ->where('purchase_id', $purchaseId)
            ->where('status', 'issued')
            ->first();
        if ($existing) {
            throw ValidationException::withMessages(['document' => 'Esta compra ya tiene un comprobante emitido.']);
        }

        $request = SocialeventOrganizationDocumentRequest::query()
            ->where('organization_id', $organizationId)
            ->where('purchase_type', $purchaseType)
            ->where('purchase_id', $purchaseId)
            ->where('status', 'requested')
            ->first();

        $data = [
            'organization_id' => $organizationId,
            'requested_by' => $actor->id,
            'document_type_id' => $type->id,
            'billing_data' => $billing,
            'delivery_channels' => array_values(array_intersect($channels, ['email', 'whatsapp'])),
            'status' => 'requested',
            'rejection_reason' => null,
        ];

        if ($request) {
            $request->fill($data)->save();
        } else {
            $request = SocialeventOrganizationDocumentRequest::create($data + [
                'purchase_type' => $purchaseType,
                'purchase_id' => $purchaseId,
            ]);
        }

        $this->audit($organizationId, $actor->id, 'document_requested', 'Solicitud de comprobante de compra.', [
            'purchase_type' => $purchaseType,
            'purchase_id' => $purchaseId,
            'document_type_id' => $type->id,
            'amount' => (float) $purchase->amount,
        ]);

        return $request->fresh();
    }

    /**
     * Emite el comprobante. Crea la venta y el documento sin volver a tocar el
     * saldo, la vigencia ni la tesorería, porque el pago ya fue acreditado.
     *
     * @param array<string, mixed> $options
     */
    public function issue($admin, int $requestId, array $options = []): SocialeventOrganizationDocumentRequest
    {
        abort_unless($this->sports->isSuperAdmin($admin), 403, 'Solo el superadministrador puede emitir comprobantes.');

        $request = DB::transaction(function () use ($admin, $requestId, $options) {
            $request = SocialeventOrganizationDocumentRequest::query()->lockForUpdate()->findOrFail($requestId);
            abort_if($request->status === 'issued', 422, 'Esta solicitud ya fue emitida.');

            $type = SaleDocumentType::query()->findOrFail($request->document_type_id);
            $billing = array_merge((array) $request->billing_data, (array) ($options['billing_data'] ?? []));
            $this->validateBilling($type, $billing);

            $purchase = $this->purchase((int) $request->organization_id, $request->purchase_type, (int) $request->purchase_id);
            $organization = DB::table('socialevent_organizations')->where('id', $request->organization_id)->first();
            abort_unless($organization, 404);

            $document = $this->createSaleDocument(
                $admin,
                $organization,
                $type,
                (float) $purchase->amount,
                $billing,
                $this->purchaseDescription($request->purchase_type, $purchase),
                $purchase->payment_method ?? null,
                $purchase->reference ?? null
            );

            $request->fill([
                'status' => 'issued',
                'billing_data' => $billing,
                'sale_document_id' => $document->id,
                'issued_by' => $admin->id,
                'issued_at' => now(),
                'rejection_reason' => null,
            ])->save();

            $this->audit((int) $request->organization_id, $admin->id, 'document_issued', 'Emisión de comprobante.', [
                'request_id' => $request->id,
                'document_id' => $document->id,
                'serie' => $document->invoice_serie,
                'number' => $document->invoice_correlative,
                'type' => $type->sunat_id,
            ]);

            return $request;
        });

        $channels = array_values(array_intersect($options['channels'] ?? $request->delivery_channels ?? [], ['email', 'whatsapp']));
        if ($channels !== []) {
            $this->send($request, $channels, $options['email'] ?? null, $options['phone'] ?? null);
        }

        return $request->fresh(['saleDocument']);
    }

    public function reject($admin, int $requestId, string $reason): SocialeventOrganizationDocumentRequest
    {
        abort_unless($this->sports->isSuperAdmin($admin), 403);

        $request = SocialeventOrganizationDocumentRequest::query()->findOrFail($requestId);
        abort_if($request->status === 'issued', 422, 'No se puede rechazar un comprobante ya emitido.');
        $request->fill([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ])->save();
        $this->audit((int) $request->organization_id, $admin->id, 'document_rejected', $reason, [
            'request_id' => $request->id,
        ]);

        return $request->fresh();
    }

    /**
     * Envía el enlace del comprobante por correo y/o WhatsApp. El documento
     * queda visible aunque falle el envío.
     *
     * @param array<int, string> $channels
     */
    public function send(SocialeventOrganizationDocumentRequest $request, array $channels, ?string $email = null, ?string $phone = null): void
    {
        $request->loadMissing('saleDocument', 'organization');
        abort_unless($request->sale_document_id, 422, 'El comprobante aún no fue emitido.');

        $billing = (array) $request->billing_data;
        $email = $email ?: ($billing['email'] ?? null);
        $phone = $phone ?: ($billing['phone'] ?? null);
        $document = $request->saleDocument;
        $label = trim(($document->invoice_serie ?? '') . '-' . ($document->invoice_correlative ?? ''));
        $url = route('even_organizations.documents.download', ['documentRequest' => $request->id]);

        $log = (array) ($request->delivery_log ?? []);
        $sent = false;

        if (in_array('email', $channels, true) && $email) {
            try {
                Mail::raw(
                    "Hola,\n\nAdjuntamos la referencia de tu comprobante {$label} por S/ " . number_format((float) $document->overall_total, 2) . ".\n\nPuedes verlo y descargarlo desde Mi plan:\n{$url}\n\nOrganización: " . ($request->organization->name ?? '') . "\n",
                    function ($message) use ($email, $label) {
                        $message->to($email)->subject('Comprobante ' . $label);
                    }
                );
                $log[] = ['channel' => 'email', 'recipient' => $email, 'status' => 'sent', 'at' => now()->toDateTimeString()];
                $sent = true;
            } catch (\Throwable $error) {
                report($error);
                $log[] = ['channel' => 'email', 'recipient' => $email, 'status' => 'failed', 'error' => $error->getMessage(), 'at' => now()->toDateTimeString()];
            }
        }

        if (in_array('whatsapp', $channels, true) && $phone) {
            $message = "Hola, tu comprobante {$label} está disponible en Mi plan: {$url}";
            $ok = $this->sendWhatsApp($phone, $message);
            $log[] = [
                'channel' => 'whatsapp',
                'recipient' => $phone,
                'status' => $ok ? 'sent' : 'failed',
                'error' => $ok ? null : 'WhatsApp no está configurado o el número no es válido.',
                'at' => now()->toDateTimeString(),
            ];
            $sent = $sent || $ok;
        }

        $request->fill([
            'delivery_channels' => array_values(array_unique(array_merge((array) $request->delivery_channels, $channels))),
            'delivery_log' => $log,
            'sent_at' => $sent ? now() : $request->sent_at,
        ])->save();
    }

    /**
     * Respuesta de descarga para el organizador o el superadministrador.
     */
    public function download($user, int $requestId, string $file = 'PDF')
    {
        $request = SocialeventOrganizationDocumentRequest::query()->with('saleDocument')->findOrFail($requestId);
        $isAdmin = $this->sports->isSuperAdmin($user);
        $isOwner = $this->team->isOwner($user) && (int) $user->socialevent_organization_id === (int) $request->organization_id;
        abort_unless($isAdmin || $isOwner, 404);
        abort_unless($request->sale_document_id && $request->saleDocument, 404, 'El comprobante aún no fue emitido.');

        $document = $request->saleDocument;
        $sunat = (string) $document->invoice_type_doc;
        $file = strtoupper($file) === 'XML' ? 'XML' : 'PDF';

        if ($sunat === '01' || $sunat === '03') {
            $helper = $sunat === '01' ? new Factura() : new Boleta();
            if ($file === 'XML') {
                $result = $sunat === '01' ? $helper->getFacturaXML($document->id) : $helper->getBoletaXML($document->id);
                $contentType = 'application/xml';
            } else {
                $result = $sunat === '01' ? $helper->getFacturaDomPdf($document->id, 'A4') : $helper->getBoletatDomPdf($document->id, 'A4');
                $contentType = 'application/pdf';
            }
        } else {
            abort_if($file === 'XML', 404, 'La nota de venta no tiene XML.');
            $path = $this->notaVentaPdf($document);
            $result = ['filePath' => $path, 'fileName' => 'nota-venta-' . $document->invoice_correlative . '.pdf'];
            $contentType = 'application/pdf';
        }

        abort_unless(! empty($result['filePath']) && is_file($result['filePath']), 404, 'No se pudo generar el archivo.');
        $name = $result['fileName'] ?? ('comprobante-' . $document->id . '.' . strtolower($file));

        return response()->download($result['filePath'], $name, ['content-type' => $contentType]);
    }

    /**
     * @param array<string, mixed> $billing
     */
    private function createSaleDocument(
        User $admin,
        object $organization,
        SaleDocumentType $type,
        float $amount,
        array $billing,
        string $description,
        ?string $paymentMethod,
        ?string $reference
    ): SaleDocument {
        abort_unless($admin->local_id, 422, 'El administrador necesita un local de ventas asignado para emitir documentos.');
        $local = LocalSale::query()->find($admin->local_id);
        abort_unless($local, 422, 'El local de ventas del administrador no existe.');
        $series = Serie::query()
            ->where('local_id', $local->id)
            ->where('document_type_id', $type->id)
            ->lockForUpdate()
            ->first();
        abort_unless($series, 422, 'No hay una serie configurada para este tipo de comprobante en el local del administrador.');

        $client = $this->resolveClient($organization, (string) $type->sunat_id, $billing);
        $cash = PettyCash::query()->firstOrCreate([
            'user_id' => $admin->id,
            'state' => 1,
            'local_sale_id' => $local->id,
        ], [
            'date_opening' => now()->toDateString(),
            'time_opening' => now()->format('H:i:s'),
            'income' => 0,
        ]);

        $igvRate = (string) $type->sunat_id === '80'
            ? 0.0
            : (float) Parameter::query()->where('parameter_code', 'P000001')->value('value_default');
        $taxableBase = (string) $type->sunat_id === '80' ? $amount : round($amount / (1 + ($igvRate / 100)), 2);
        $taxAmount = round($amount - $taxableBase, 2);

        $sale = Sale::create([
            'sale_date' => now()->toDateString(),
            'user_id' => $admin->id,
            'client_id' => $client->id,
            'local_id' => $local->id,
            'total' => $amount,
            'advancement' => $amount,
            'total_discount' => 0,
            'payments' => json_encode([[
                'type' => $paymentMethod ?: 'mercadopago',
                'amount' => $amount,
                'reference' => $reference,
            ]]),
            'petty_cash_id' => $cash->id,
            'physical' => (string) $type->sunat_id === '80' ? 1 : 2,
        ]);

        $seriesNumber = (int) $series->number;
        $numberLetters = new NumberLetter();
        $document = SaleDocument::create([
            'sale_id' => $sale->id,
            'serie_id' => $series->id,
            'number' => str_pad((string) $seriesNumber, 9, '0', STR_PAD_LEFT),
            'overall_total' => $amount,
            'user_id' => $admin->id,
            'invoice_type_doc' => (string) $type->sunat_id,
            'invoice_serie' => $series->description,
            'invoice_correlative' => $seriesNumber,
            'invoice_broadcast_date' => now()->toDateString(),
            'invoice_due_date' => now()->toDateString(),
            'invoice_send_date' => now(),
            'invoice_type_currency' => 'PEN',
            'invoice_type_operation' => (string) (Parameter::query()->where('parameter_code', 'P000002')->value('value_default') ?: '0101'),
            'invoice_ubl_version' => (string) (Parameter::query()->where('parameter_code', 'P000003')->value('value_default') ?: '2.1'),
            'invoice_legend_code' => '1000',
            'invoice_legend_description' => $numberLetters->convertToLetter($amount, 'SOLES'),
            'invoice_mto_oper_unaffected' => 0,
            'invoice_mto_oper_exonerated' => 0,
            'invoice_mto_oper_taxed' => (string) $type->sunat_id === '80' ? 0 : $taxableBase,
            'invoice_mto_igv' => (string) $type->sunat_id === '80' ? 0 : $taxAmount,
            'invoice_icbper' => 0,
            'invoice_total_taxes' => (string) $type->sunat_id === '80' ? 0 : $taxAmount,
            'invoice_value_sale' => (string) $type->sunat_id === '80' ? $amount : $taxableBase,
            'invoice_subtotal' => (string) $type->sunat_id === '80' ? $amount : $taxableBase + $taxAmount,
            'invoice_mto_imp_sale' => $amount,
            'invoice_rounding' => '0.00',
            'forma_pago' => 'Contado',
            'status_pay' => true,
            'client_type_doc' => $client->document_type_id,
            'client_number' => $client->number,
            'client_rzn_social' => $client->full_name,
            'client_address' => $client->address,
            'client_ubigeo_code' => $client->ubigeo,
            'client_ubigeo_description' => $client->ubigeo_description,
            'client_phone' => $client->telephone,
            'client_email' => $client->email,
            'invoice_status' => (string) $type->sunat_id === '80' ? 'No aplica' : 'Pendiente',
            'status' => true,
        ]);

        $product = Product::query()->firstOrCreate(
            ['interne' => 'ORG-SEATS'],
            [
                'usine' => 'ORG-SEATS',
                'description' => $description,
                'purchase_prices' => $amount,
                'sale_prices' => json_encode(['high' => $amount, 'low' => $amount, 'medium' => $amount]),
                'sizes' => null,
                'stock_min' => 0,
                'stock' => 0,
                'presentations' => false,
                'is_product' => false,
                'type_sale_affectation_id' => '10',
                'type_purchase_affectation_id' => '10',
                'type_unit_measure_id' => 'ZZ',
                'status' => true,
                'icbper' => false,
            ]
        );
        $product->forceFill([
            'description' => $description,
            'sale_prices' => json_encode(['high' => $amount, 'low' => $amount, 'medium' => $amount]),
            'purchase_prices' => $amount,
        ])->save();

        $saleProduct = [
            'id' => $product->id,
            'usine' => $product->usine,
            'interne' => $product->interne,
            'description' => $description,
            'price' => $amount,
            'discount' => 0,
            'quantity' => 1,
            'total' => $amount,
            'unit_type' => 'ZZ',
            'afe_igv' => (string) $type->sunat_id === '80' ? '30' : '10',
            'is_product' => false,
        ];
        $item = DB::table('sale_products')->insertGetId([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'product' => json_encode($product->fresh()->toArray()),
            'saleProduct' => json_encode($saleProduct),
            'price' => $amount,
            'discount' => 0,
            'quantity' => 1,
            'total' => $amount,
            'entity_name_product' => Product::class,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sale_document_items')->insert([
            'document_id' => $document->id,
            'product_id' => $product->id,
            'cod_product' => $product->interne,
            'decription_product' => $description,
            'unit_type' => 'ZZ',
            'quantity' => 1,
            'mto_base_igv' => $taxableBase,
            'percentage_igv' => $igvRate,
            'igv' => $taxAmount,
            'total_tax' => $taxAmount,
            'type_afe_igv' => (string) $type->sunat_id === '80' ? '30' : '10',
            'icbper' => 0,
            'factor_icbper' => 0,
            'mto_value_sale' => $taxableBase,
            'mto_value_unit' => $taxableBase,
            'mto_price_unit' => $amount,
            'price_sale' => $amount,
            'mto_total' => $amount,
            'mto_discount' => 0,
            'json_discounts' => json_encode([]),
            'entity_name_product' => Product::class,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $document->update([
            'invoice_mto_oper_unaffected' => (string) $type->sunat_id === '80' ? $amount : 0,
            'invoice_mto_oper_taxed' => (string) $type->sunat_id === '80' ? 0 : $taxableBase,
            'invoice_mto_igv' => (string) $type->sunat_id === '80' ? 0 : $taxAmount,
            'invoice_total_taxes' => (string) $type->sunat_id === '80' ? 0 : $taxAmount,
            'invoice_value_sale' => $amount,
        ]);
        $series->increment('number');

        if (in_array((string) $type->sunat_id, ['01', '03'], true)) {
            try {
                app(\Modules\Sales\Http\Controllers\SaleDocumentController::class)
                    ->sendSunatDocument((int) $document->id, (string) $type->sunat_id);
            } catch (\Throwable $error) {
                report($error);
            }
        }

        return $document->fresh();
    }

    /**
     * @param array<string, mixed> $billing
     */
    private function resolveClient(object $organization, string $sunat, array $billing): Person
    {
        $isInvoice = $sunat === '01';
        $documentNumber = $isInvoice
            ? (string) ($billing['ruc'] ?? '')
            : (string) ($billing['document_number'] ?? '');
        $documentType = $isInvoice ? '6' : (string) ($billing['document_type'] ?? '1');
        $name = (string) ($billing['business_name'] ?? $organization->name);
        $address = $billing['address'] ?? ($organization->address ?? null);
        $ubigeo = $billing['ubigeo'] ?? ($organization->ubigeo ?? null);
        $email = $billing['email'] ?? null;
        $phone = $billing['phone'] ?? null;

        abort_if($documentNumber === '', 422, $isInvoice
            ? 'Ingresa el RUC para emitir la factura.'
            : 'Ingresa el documento de identidad para emitir el comprobante.');

        $person = Person::query()->where('number', $documentNumber)->first();
        $data = [
            'document_type_id' => $documentType,
            'full_name' => $name,
            'short_name' => $name,
            'email' => $email,
            'telephone' => $phone,
            'address' => $address,
            'ubigeo' => $ubigeo,
            'is_client' => true,
        ];

        if ($person) {
            $person->fill($data)->save();

            return $person;
        }

        return Person::create($data + ['number' => $documentNumber]);
    }

    /**
     * @param array<string, mixed> $billing
     * @return array<string, mixed>
     */
    private function normalizeBilling(array $billing): array
    {
        return [
            'business_name' => trim((string) ($billing['business_name'] ?? '')),
            'ruc' => preg_replace('/\D+/', '', (string) ($billing['ruc'] ?? '')),
            'address' => trim((string) ($billing['address'] ?? '')),
            'ubigeo' => trim((string) ($billing['ubigeo'] ?? '')),
            'document_type' => (string) ($billing['document_type'] ?? '1'),
            'document_number' => trim((string) ($billing['document_number'] ?? '')),
            'email' => trim((string) ($billing['email'] ?? '')),
            'phone' => preg_replace('/\D+/', '', (string) ($billing['phone'] ?? '')),
        ];
    }

    /**
     * @param array<string, mixed> $billing
     */
    private function validateBilling(SaleDocumentType $type, array $billing): void
    {
        $sunat = (string) $type->sunat_id;
        $errors = [];

        if ($sunat === '01') {
            if (strlen((string) $billing['ruc']) !== 11) $errors['ruc'] = 'El RUC debe tener 11 dígitos.';
            if ($billing['business_name'] === '') $errors['business_name'] = 'Ingresa la razón social.';
            if ($billing['address'] === '') $errors['address'] = 'Ingresa la dirección fiscal.';
            if (strlen((string) $billing['ubigeo']) !== 6) $errors['ubigeo'] = 'Selecciona el ubigeo.';
        }

        if ($sunat === '03' && $billing['document_number'] === '') {
            $errors['document_number'] = 'Ingresa el documento de identidad para la boleta.';
        }

        if ($sunat === '80' && $billing['business_name'] === '' && $billing['document_number'] === '') {
            $errors['business_name'] = 'Ingresa el nombre o documento del cliente.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @return object{amount: float, payment_method: ?string, reference: ?string, status: string}
     */
    private function purchase(int $organizationId, string $purchaseType, int $purchaseId): object
    {
        if ($purchaseType === 'seat_purchase' && Schema::hasTable('socialevent_organization_seat_purchases')) {
            $purchase = DB::table('socialevent_organization_seat_purchases')
                ->where('id', $purchaseId)
                ->where('organization_id', $organizationId)
                ->first();
            abort_unless($purchase, 404);
            abort_unless(in_array($purchase->status, ['credited', 'approved'], true), 422, 'La compra todavía no está acreditada.');
            abort_if((float) $purchase->total <= 0, 422, 'La compra no tiene un monto válido.');

            return (object) [
                'amount' => (float) $purchase->total,
                'payment_method' => (string) $purchase->payment_method,
                'reference' => $purchase->provider_payment_id ?: $purchase->external_reference,
                'status' => (string) $purchase->status,
                'quantity' => (int) $purchase->quantity,
            ];
        }

        if ($purchaseType === 'topup' && Schema::hasTable('socialevent_organization_topups')) {
            $purchase = DB::table('socialevent_organization_topups')
                ->where('id', $purchaseId)
                ->where('organization_id', $organizationId)
                ->first();
            abort_unless($purchase, 404);
            abort_unless(in_array($purchase->status, ['credited', 'approved'], true), 422, 'La recarga todavía no está acreditada.');
            abort_if((float) $purchase->amount <= 0, 422, 'La recarga no tiene un monto válido.');

            return (object) [
                'amount' => (float) $purchase->amount,
                'payment_method' => (string) $purchase->payment_method,
                'reference' => $purchase->provider_payment_id ?: $purchase->external_reference,
                'status' => (string) $purchase->status,
                'quantity' => null,
            ];
        }

        abort(404);
    }

    private function purchaseDescription(string $purchaseType, object $purchase): string
    {
        if ($purchaseType === 'seat_purchase') {
            return 'Compra de ' . ((int) ($purchase->quantity ?? 0)) . ' espacio(s) de usuario adicional';
        }

        return 'Recarga de saldo para partidos';
    }

    private function notaVentaPdf(SaleDocument $document): string
    {
        $document->loadMissing('items', 'sale.client');
        $company = \App\Models\Company::first();
        $pdf = Pdf::loadView('socialevents.nota-venta-pdf', [
            'document' => $document,
            'company' => $company,
            'client' => $document->sale?->client,
            'items' => $document->items,
        ]);
        $pdf->setPaper('a4', 'portrait');
        $directory = storage_path('app' . DIRECTORY_SEPARATOR . 'organization-documents');
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
        $path = $directory . DIRECTORY_SEPARATOR . 'nota-venta-' . $document->id . '.pdf';
        $pdf->save($path);

        return $path;
    }

    private function sendWhatsApp(string $phone, string $message): bool
    {
        $token = (string) config('services.whatsapp.token');
        $phoneNumberId = (string) config('services.whatsapp.phone_number_id');
        if ($token === '' || $phoneNumberId === '') {
            return false;
        }

        $number = preg_replace('/\D+/', '', $phone);
        if (strlen($number) === 9) {
            $number = '51' . $number;
        }
        if (strlen($number) < 10) {
            return false;
        }

        try {
            $response = Http::withToken($token)->acceptJson()->post(
                "https://graph.facebook.com/v20.0/{$phoneNumberId}/messages",
                [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $number,
                    'type' => 'text',
                    'text' => ['preview_url' => true, 'body' => $message],
                ]
            );

            return $response->successful();
        } catch (\Throwable $error) {
            report($error);

            return false;
        }
    }

    private function audit(int $organizationId, ?int $userId, string $action, string $reason, array $changes): void
    {
        DB::table('socialevent_organization_audits')->insert([
            'organization_id' => $organizationId,
            'user_id' => $userId,
            'action' => $action,
            'reason' => $reason,
            'changes' => json_encode($changes),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
