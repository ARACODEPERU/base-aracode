<?php

namespace Tests\Feature\Academic;

use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Entities\AcaStudent;
use Modules\Academic\Entities\AcaSubscriptionType;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasurySourceMovement;
use Modules\Treasury\Entities\TreasuryTransaction;
use Modules\Treasury\Services\TreasuryLedgerService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Venta de una suscripción desde Académico ("Cobrar"): el comprobante emitido
 * debe dejar su ingreso en Tesorería, igual que las ventas y las negociaciones.
 */
class AcaSubscriptionTreasuryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private int $clientId;

    private int $studentId;

    private int $subscriptionId;

    private int $serieId;

    private int $boletaTypeId;

    private PaymentMethod $method;

    private TreasuryAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('aca_estudiante_cobrar', 'web');

        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->user->local_id = 1;
        $this->user->save();
        $this->user->givePermissionTo('aca_estudiante_cobrar');

        $this->actingAs($this->user);

        $this->boletaTypeId = (int) DB::table('sale_document_types')->where('sunat_id', '03')->value('id');

        $this->serieId = (int) DB::table('series')->insertGetId([
            'document_type_id' => $this->boletaTypeId,
            'description' => 'B001',
            'number' => 1,
            'user_id' => $this->user->id,
            'local_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->clientId = (int) DB::table('people')->insertGetId([
            'document_type_id' => '1',
            'full_name' => 'Alumno Prueba',
            'number' => '44556677',
            'email' => 'alumno@example.com',
            'telephone' => '999999999',
            'address' => 'Av. Prueba 1',
            'ubigeo' => '150101',
            'ubigeo_description' => 'LIMA-LIMA-LIMA',
            'is_client' => true,
            'gender' => 'M',
            'country_id' => 1,
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->studentId = (int) AcaStudent::create([
            'person_id' => $this->clientId,
            'student_code' => '44556677',
        ])->id;

        $this->subscriptionId = (int) AcaSubscriptionType::create([
            'title' => 'Plan Mensual',
            'description' => 'Suscripción mensual',
            'period' => 'Mensual',
            'prices' => [['currency' => 'PEN', 'amount' => 120]],
            'status' => true,
        ])->id;

        DB::table('aca_student_subscriptions')->insert([
            'student_id' => $this->studentId,
            'subscription_id' => $this->subscriptionId,
            'date_start' => now()->toDateString(),
            'date_end' => now()->addMonth()->toDateString(),
            'status' => true,
            'renewals' => 0,
            'registration_user_id' => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->method = $this->linkTransferMethod();
    }

    public function test_la_venta_de_una_suscripcion_deja_su_ingreso_en_tesoreria(): void
    {
        $response = $this->postJson(route('aca_student_invoice_store'), [
            'client_id' => $this->clientId,
            'client_name' => '44556677-Alumno Prueba',
            'client_rzn_social' => 'Alumno Prueba',
            'client_dti' => '1',
            'client_number' => '44556677',
            'client_ubigeo' => '150101',
            'client_ubigeo_description' => 'LIMA-LIMA-LIMA',
            'client_direction' => 'Av. Prueba 1',
            'client_phone' => '999999999',
            'client_email' => 'alumno@example.com',
            'sale_documenttype_id' => $this->boletaTypeId,
            'student_id' => $this->studentId,
            'serie' => $this->serieId,
            'date_issue' => now()->toDateString(),
            'date_end' => now()->toDateString(),
            'forma_pago' => 'Contado',
            'total' => 120,
            'total_discount' => 0,
            'payments' => [
                ['type' => $this->method->id, 'reference' => 'TRF-1', 'amount' => 120],
            ],
            'items' => [[
                'id' => $this->subscriptionId,
                'description' => 'Plan Mensual',
                'is_product' => false,
                'unit_type' => 'ZZ',
                'quantity' => 1,
                'amount' => 120,
                'discount' => 0,
                'total' => 120,
                'afe_igv' => '10',
                'mode' => 4,
                'originId' => null,
                'size' => null,
                'icbper' => false,
            ]],
        ]);

        $response->assertOk();

        $transaction = TreasuryTransaction::query()->firstOrFail();

        $this->assertSame(120.0, (float) $transaction->amount);
        $this->assertSame((int) $this->account->id, (int) $transaction->treasury_account_id);
        $this->assertSame(1, TreasurySourceMovement::query()->processed()->count());
        $this->assertSame(120.0, app(TreasuryLedgerService::class)->accountBalance($this->account->refresh()));
    }

    /**
     * Método de pago con cuenta bancaria de tesorería, para que el cobro
     * encuentre su cuenta en el libro.
     */
    private function linkTransferMethod(): PaymentMethod
    {
        $bank = Bank::create([
            'short_name' => 'TST',
            'full_name' => 'Banco de prueba',
            'status' => true,
        ]);

        $bankAccount = BankAccount::create([
            'bank_id' => $bank->id,
            'description' => 'Cuenta de prueba PEN',
            'number' => '000-0002',
            'currency_type_id' => 'PEN',
            'status' => true,
        ]);

        $this->account = TreasuryAccount::create([
            'type' => TreasuryAccount::TYPE_BANK,
            'bank_account_id' => $bankAccount->id,
            'label' => 'Cuenta de prueba PEN',
            'currency_type_id' => 'PEN',
            'opening_balance' => 0,
            'status' => true,
        ]);

        $methodId = DB::table('payment_methods')->insertGetId([
            'description' => 'Transferencia BCP',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $method = PaymentMethod::findOrFail($methodId);
        $method->bank_account_id = $bankAccount->id;
        $method->save();

        return $method;
    }
}
