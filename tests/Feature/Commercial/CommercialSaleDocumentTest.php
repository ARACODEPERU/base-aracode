<?php

namespace Tests\Feature\Commercial;

use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\PaymentMethod;
use App\Models\SaleDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Sales\Entities\SaleDocumentQuota;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasurySourceMovement;
use Modules\Treasury\Entities\TreasuryTransaction;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Emision de facturas/boletas desde Comercial agrupando cuotas de contratos.
 *
 * Cubre el camino completo (HTTP -> SaleDocumentController::store) con dos
 * contratos del mismo cliente, el rechazo de cuotas repetidas y las
 * validaciones de serie/local, serie/tipo de comprobante y factura con RUC.
 */
class CommercialSaleDocumentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private int $clientId;

    private int $facturaTypeId;

    private int $boletaTypeId;

    private int $serieFacturaId;

    private int $serieOtroLocalId;

    private int $serieBoletaId;

    private int $paymentMethodId;

    private int $quotaAId;

    private int $quotaBId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->user->local_id = 1;
        $this->user->save();

        $this->actingAs($this->user);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('comm_contratos_cronograma', 'web');
        $this->user->givePermissionTo('comm_contratos_cronograma');

        $this->facturaTypeId = (int) DB::table('sale_document_types')->where('sunat_id', '01')->value('id');
        $this->boletaTypeId = (int) DB::table('sale_document_types')->where('sunat_id', '03')->value('id');

        $this->paymentMethodId = (int) (DB::table('payment_methods')->value('id')
            ?: DB::table('payment_methods')->insertGetId([
                'description' => 'Efectivo',
                'created_at' => now(),
                'updated_at' => now(),
            ]));

        $this->clientId = DB::table('people')->insertGetId([
            'document_type_id' => '6',
            'full_name' => 'Colegio Demo SAC',
            'number' => '20551234567',
            'email' => 'cliente@example.com',
            'telephone' => '999999999',
            'address' => 'Av. Siempre Viva 123',
            'ubigeo' => '150101',
            'ubigeo_description' => 'LIMA-LIMA-LIMA',
            'is_client' => true,
            'gender' => 'M',
            'country_id' => 1,
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $serviceId = DB::table('products')->insertGetId([
            'usine' => 'x',
            'interne' => 'SRV001',
            'description' => 'Servicio de prueba',
            'purchase_prices' => 0,
            'sale_prices' => json_encode(['high' => 700]),
            'stock_min' => 1,
            'stock' => 0,
            'presentations' => false,
            'is_product' => false,
            'type_sale_affectation_id' => '10',
            'type_purchase_affectation_id' => '10',
            'type_unit_measure_id' => 'ZZ',
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->serieFacturaId = $this->makeSerie($this->facturaTypeId, 'F001', 1);
        $this->serieBoletaId = $this->makeSerie($this->boletaTypeId, 'B001', 1);
        $this->serieOtroLocalId = $this->makeSerie($this->facturaTypeId, 'F002', 2);

        $contractA = DB::table('commercial_contracts')->insertGetId([
            'client_id' => $this->clientId,
            'service_id' => $serviceId,
            'contract_type' => 'service',
            'title' => 'Contrato A',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'amount' => 1000,
            'currency' => 'PEN',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $contractB = DB::table('commercial_contracts')->insertGetId([
            'client_id' => $this->clientId,
            'service_id' => $serviceId,
            'contract_type' => 'service',
            'title' => 'Contrato B',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'amount' => 500,
            'currency' => 'PEN',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->quotaAId = $this->makeQuota($contractA, 1, 400);
        $this->quotaBId = $this->makeQuota($contractB, 1, 300);
    }

    public function test_registra_un_comprobante_con_cuotas_de_dos_contratos(): void
    {
        $response = $this->postJson(route('comm_sales_documents_store'), $this->payload());

        $response->assertOk()->assertJson(['success' => true]);

        $document = SaleDocument::query()->orderByDesc('id')->firstOrFail();

        $this->assertSame(700.0, (float) $document->invoice_mto_imp_sale);
        $this->assertCount(2, $document->items);

        foreach ([$this->quotaAId, $this->quotaBId] as $quotaId) {
            $quota = DB::table('commercial_contract_payments')->where('id', $quotaId)->first();
            $links = json_decode((string) $quota->document_payments, true);

            $this->assertSame('paid', $quota->status, (string) $quota->id);
            $this->assertSame(0.0, (float) $quota->balance_amount);
            $this->assertSame($document->id, (int) $links[0]['document_id']);
        }
    }

    public function test_rechaza_una_cuota_repetida_en_el_mismo_comprobante(): void
    {
        $payload = $this->payload([
            'items' => [
                ['quota_id' => $this->quotaAId, 'amount' => 400, 'description' => 'Cuota A'],
                ['quota_id' => $this->quotaAId, 'amount' => 300, 'description' => 'Cuota A repetida'],
            ],
        ]);

        $response = $this->postJson(route('comm_sales_documents_store'), $payload);

        $response->assertStatus(422)->assertJson([
            'success' => false,
            'message' => 'Una cuota no puede cobrarse dos veces en el mismo comprobante.',
        ]);

        $this->assertSame(0, SaleDocument::query()->count());
    }

    public function test_rechaza_una_serie_de_otro_local(): void
    {
        $response = $this->postJson(route('comm_sales_documents_store'), $this->payload([
            'serie' => $this->serieOtroLocalId,
        ]));

        $response->assertStatus(422)->assertJson([
            'success' => false,
            'message' => 'La serie seleccionada no pertenece al local del usuario.',
        ]);

        $this->assertSame(0, SaleDocument::query()->count());
    }

    public function test_rechaza_una_serie_de_otro_tipo_de_comprobante(): void
    {
        $response = $this->postJson(route('comm_sales_documents_store'), $this->payload([
            'serie' => $this->serieBoletaId,
            'sale_documenttype_id' => $this->facturaTypeId,
        ]));

        $response->assertStatus(422)->assertJson([
            'success' => false,
            'message' => 'La serie no corresponde al tipo de comprobante elegido.',
        ]);

        $this->assertSame(0, SaleDocument::query()->count());
    }

    public function test_una_factura_exige_ruc_del_cliente(): void
    {
        DB::table('people')->where('id', $this->clientId)->update(['document_type_id' => '1']);

        $response = $this->postJson(route('comm_sales_documents_store'), $this->payload());

        $response->assertStatus(422)->assertJson([
            'success' => false,
            'message' => 'Para emitir una factura el cliente debe tener RUC.',
        ]);

        $this->assertSame(0, SaleDocument::query()->count());
    }

    public function test_venta_al_credito_programa_las_cuotas_del_comprobante_y_no_paga_las_del_contrato(): void
    {
        $response = $this->postJson(route('comm_sales_documents_store'), $this->payload([
            'forma_pago' => 'Credito',
            'payments' => [],
            'quotas' => [
                'amounts' => [
                    ['amount' => 400, 'dueDate' => now()->addMonth()->toDateString()],
                    ['amount' => 300, 'dueDate' => now()->addMonths(2)->toDateString()],
                ],
            ],
        ]));

        $response->assertOk()->assertJson(['success' => true]);

        $document = SaleDocument::query()->orderByDesc('id')->firstOrFail();

        $this->assertSame(2, SaleDocumentQuota::query()->where('sale_document_id', $document->id)->count());
        $this->assertSame('Credito', $document->forma_pago);

        // La deuda se traslada al comprobante: la cuota del contrato queda
        // amortizada y sin fecha de pago, nunca pagada.
        foreach ([$this->quotaAId, $this->quotaBId] as $quotaId) {
            $quota = DB::table('commercial_contract_payments')->where('id', $quotaId)->first();
            $links = json_decode((string) $quota->document_payments, true);

            $this->assertSame('amortized', $quota->status, (string) $quota->id);
            $this->assertNull($quota->paid_at);
            $this->assertSame(0.0, (float) $quota->balance_amount);
            $this->assertSame($document->id, (int) $links[0]['document_id']);
        }

        // Al credito no haya dinero recibido: no se registra ingreso.
        $this->assertSame(0, TreasuryTransaction::query()->count());
    }

    public function test_usuario_sin_local_puede_emitir_con_la_serie_del_tipo_elegido(): void
    {
        $this->user->local_id = null;
        $this->user->save();

        $response = $this->postJson(route('comm_sales_documents_store'), $this->payload([
            'serie' => $this->serieOtroLocalId,
        ]));

        $response->assertOk()->assertJson(['success' => true]);

        $document = SaleDocument::query()->orderByDesc('id')->firstOrFail();
        $this->assertSame($this->serieOtroLocalId, (int) $document->serie_id);
    }

    public function test_el_comprobante_registrado_queda_reflejado_en_tesoreria(): void
    {
        $method = $this->linkTransferMethod();

        $response = $this->postJson(route('comm_sales_documents_store'), $this->payload([
            'payments' => [['type' => $method->id, 'amount' => 700, 'reference' => 'TRF-C1']],
        ]));

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertSame(1, TreasuryTransaction::query()->count());

        $bridge = TreasurySourceMovement::query()->firstOrFail();

        $this->assertSame(TreasurySourceMovement::STATUS_PROCESSED, $bridge->status);
        $this->assertSame('sale_income', $bridge->operation);
        $this->assertSame(700.0, (float) $bridge->amount);
    }

    public function test_cobro_al_contado_con_dos_medios_de_pago_exige_el_total_y_registra_ambos_movimientos(): void
    {
        $transferencia = $this->linkTransferMethod('Transferencia BCP', 'A');
        $segunda = $this->linkTransferMethod('Transferencia Interbank', 'B');

        $pagos = [
            ['type' => $transferencia->id, 'amount' => 400],
            ['type' => $segunda->id, 'amount' => 200],
        ];

        // La suma de los medios de pago debe igualar el total del comprobante.
        $this->postJson(route('comm_sales_documents_store'), $this->payload(['payments' => $pagos]))
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'El cobro al contado debe ser igual al total del comprobante.',
            ]);

        $this->assertSame(0, SaleDocument::query()->count());

        // 400 + 300 = 700: cada medio deja su propio ingreso en tesoreria.
        $pagos[1]['amount'] = 300;

        $this->postJson(route('comm_sales_documents_store'), $this->payload(['payments' => $pagos]))
            ->assertOk();

        $this->assertSame(
            [[300.0, $segunda->id], [400.0, $transferencia->id]],
            TreasuryTransaction::query()
                ->orderBy('amount')
                ->get()
                ->map(fn ($row) => [(float) $row->amount, (int) $row->payment_method_id])
                ->all()
        );
    }

    /**
     * Metodo de pago con cuenta de tesoreria, para que el ingreso se registre
     * en el libro (los metodos sin cuenta quedan pendientes en Cobros).
     */
    private function linkTransferMethod(string $description = 'Transferencia BCP', string $suffix = ''): PaymentMethod
    {
        $bank = Bank::create([
            'short_name' => 'TST'.$suffix,
            'full_name' => 'Banco de prueba'.$suffix,
            'status' => true,
        ]);

        $bankAccount = BankAccount::create([
            'bank_id' => $bank->id,
            'description' => 'Cuenta de prueba PEN'.$suffix,
            'number' => '000-0001'.$suffix,
            'currency_type_id' => 'PEN',
            'status' => true,
        ]);

        TreasuryAccount::create([
            'type' => TreasuryAccount::TYPE_BANK,
            'bank_account_id' => $bankAccount->id,
            'label' => 'Cuenta de prueba PEN'.$suffix,
            'currency_type_id' => 'PEN',
            'opening_balance' => 0,
            'status' => true,
        ]);

        $method = PaymentMethod::query()->where('description', $description)->first();

        if (! $method) {
            $methodId = DB::table('payment_methods')->insertGetId([
                'description' => $description,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $method = PaymentMethod::findOrFail($methodId);
        }

        $method->bank_account_id = $bankAccount->id;
        $method->save();

        return $method;
    }

    private function makeSerie(int $documentTypeId, string $description, int $localId): int
    {
        return (int) DB::table('series')->insertGetId([
            'document_type_id' => $documentTypeId,
            'description' => $description,
            'number' => 1,
            'user_id' => $this->user->id,
            'local_id' => $localId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeQuota(int $contractId, int $number, float $amount): int
    {
        return (int) DB::table('commercial_contract_payments')->insertGetId([
            'contract_id' => $contractId,
            'payment_number' => $number,
            'description' => "Cuota {$number}",
            'due_date' => now()->addMonth()->toDateString(),
            'payment_type' => 'installment',
            'amount' => $amount,
            'interest_amount' => 0,
            'total_amount' => $amount,
            'balance_amount' => $amount,
            'currency' => 'PEN',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'client_id' => $this->clientId,
            'serie' => $this->serieFacturaId,
            'sale_documenttype_id' => $this->facturaTypeId,
            'date_issue' => now()->toDateString(),
            'date_end' => now()->toDateString(),
            'forma_pago' => 'Contado',
            'payments' => [
                ['type' => $this->paymentMethodId, 'amount' => 700, 'reference' => null],
            ],
            'items' => [
                ['quota_id' => $this->quotaAId, 'amount' => 400, 'description' => 'Cuota A'],
                ['quota_id' => $this->quotaBId, 'amount' => 300, 'description' => 'Cuota B'],
            ],
        ], $overrides);
    }
}
