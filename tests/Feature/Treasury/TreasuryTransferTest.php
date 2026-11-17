<?php

namespace Tests\Feature\Treasury;

use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\BilleteraDigital;
use App\Models\CompanyBilletera;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasuryTransaction;
use Modules\Treasury\Entities\TreasuryTransferProfile;
use Modules\Treasury\Services\TreasuryAccountSyncService;
use Modules\Treasury\Services\TreasuryLedgerService;
use Modules\Treasury\Services\TreasuryTransferService;
use Tests\TestCase;

/**
 * Pruebas de transferencias entre cuentas, guardado del perfil del par
 * y creación de cuentas de Tesorería desde bancos y billeteras.
 */
class TreasuryTransferTest extends TestCase
{
    use RefreshDatabase;

    private TreasuryTransferService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
        $this->service = app(TreasuryTransferService::class);
    }

    /**
     * Datos base del formulario de transferencia sin comisiones.
     *
     * @return array<string, mixed>
     */
    private function datos(array $extra = []): array
    {
        return array_merge([
            'from_fixed_fee' => 0,
            'from_percent_fee' => 0,
            'to_fixed_fee' => 0,
            'to_percent_fee' => 0,
            'own_account_itf_free' => false,
            'charge_origin_fee' => false,
        ], $extra);
    }

    public function test_transferencia_con_itf_descuenta_el_impuesto_desde_el_origen(): void
    {
        $from = $this->makeWalletAccount('Yape');
        $to = $this->makeBankAccountAccount();
        $this->fund($from);

        $transfer = $this->service->transfer($from, $to, 2000, now()->toDateString(), $this->datos());

        // ITF 0,005 % sobre 2000 = 0,10
        $this->assertTrue($transfer->itf_applies);
        $this->assertSame(0.1, (float) $transfer->itf_amount);
        // El ITF se paga en el origen y no se descuenta dos veces al destino.
        $this->assertSame(2000.0, (float) $transfer->net_received);

        // El monto y el ITF son egresos distintos: el ITF es un gasto propio.
        $monto = TreasuryTransaction::where('transfer_id', $transfer->id)
            ->where('origin_type', 'treasury_transfer')
            ->where('type', TreasuryTransaction::TYPE_EXPENSE)
            ->firstOrFail();
        $this->assertSame(2000.0, (float) $monto->amount);

        $itf = TreasuryTransaction::where('transfer_id', $transfer->id)
            ->where('origin_type', 'treasury_transfer_itf')
            ->firstOrFail();
        $this->assertSame(0.1, (float) $itf->amount);
        $this->assertSame(0.1, (float) $itf->itf_amount);
        $this->assertSame('ITF', $itf->category?->name);
    }

    public function test_check_cuenta_propia_libre_de_itf_no_cobra_el_impuesto(): void
    {
        $from = $this->makeWalletAccount('Yape');
        $to = $this->makeBankAccountAccount();
        $this->fund($from);

        $transfer = $this->service->transfer(
            $from,
            $to,
            2000,
            now()->toDateString(),
            $this->datos(['own_account_itf_free' => true]),
        );

        $this->assertFalse($transfer->itf_applies);
        $this->assertSame(0.0, (float) $transfer->itf_amount);
    }

    public function test_menor_a_mil_soles_no_cobra_itf(): void
    {
        $from = $this->makeWalletAccount('Yape');
        $to = $this->makeBankAccountAccount();
        $this->fund($from);

        $transfer = $this->service->transfer($from, $to, 999, now()->toDateString(), $this->datos());

        $this->assertSame(0.0, (float) $transfer->itf_amount);
    }

    public function test_comision_de_destino_reduce_el_neto_recibido(): void
    {
        $from = $this->makeWalletAccount('Yape');
        $to = $this->makeBankAccountAccount();
        $this->fund($from);

        // Comisión destino: fijo 1 + 2 % de 100 = 3; IGV 18 % = 0,54.
        $transfer = $this->service->transfer(
            $from,
            $to,
            100,
            now()->toDateString(),
            $this->datos(['to_fixed_fee' => 1, 'to_percent_fee' => 2]),
        );

        $this->assertSame(0.0, (float) $transfer->from_fixed_fee);
        $this->assertSame(1.0, (float) $transfer->to_fixed_fee);
        $this->assertSame(2.0, (float) $transfer->to_percent_fee);
        $this->assertSame(3.0, (float) $transfer->to_fee_amount);
        $this->assertSame(0.54, (float) $transfer->to_fee_igv);
        $this->assertSame(96.46, (float) $transfer->net_received);

        // El destino recibe el monto completo y la comisión queda como gasto aparte.
        $entrada = TreasuryTransaction::where('transfer_id', $transfer->id)
            ->where('treasury_account_id', $to->id)
            ->where('type', TreasuryTransaction::TYPE_INCOME)
            ->firstOrFail();
        $this->assertSame(100.0, (float) $entrada->amount);

        $comision = TreasuryTransaction::where('transfer_id', $transfer->id)
            ->where('treasury_account_id', $to->id)
            ->where('origin_type', 'treasury_transfer_fee')
            ->firstOrFail();
        $this->assertSame(3.54, (float) $comision->amount);
        $this->assertSame(3.0, (float) $comision->fee_amount);
        $this->assertSame(0.54, (float) $comision->igv_amount);
        $this->assertSame('Comisión/mantenimiento bancario', $comision->category?->name);
    }

    public function test_la_comision_del_origen_con_igv_y_el_itf_son_egresos_propios(): void
    {
        $from = $this->makeWalletAccount('Yape');
        $to = $this->makeBankAccountAccount();
        $this->fund($from);

        // Comisión origen: fijo 1 + 1 % de 1000 = 11; IGV 18 % = 1,98; ITF 0,05.
        $transfer = $this->service->transfer(
            $from,
            $to,
            1000,
            now()->toDateString(),
            $this->datos([
                'charge_origin_fee' => true,
                'from_fixed_fee' => 1,
                'from_percent_fee' => 1,
            ]),
        );

        $this->assertSame(11.0, (float) $transfer->from_fee_amount);
        $this->assertSame(1.98, (float) $transfer->from_fee_igv);

        $comision = TreasuryTransaction::where('transfer_id', $transfer->id)
            ->where('treasury_account_id', $from->id)
            ->where('origin_type', 'treasury_transfer_fee')
            ->firstOrFail();
        $this->assertSame(12.98, (float) $comision->amount);
        $this->assertSame(11.0, (float) $comision->fee_amount);
        $this->assertSame(1.98, (float) $comision->igv_amount);

        // Lo que sale del origen: monto + comisión + IGV + ITF.
        $saldo = app(TreasuryLedgerService::class)->accountBalance($from->refresh());
        $this->assertSame(5000.0 - 1000 - 12.98 - 0.05, $saldo);
    }

    public function test_anular_la_transferencia_anula_tambien_sus_gastos(): void
    {
        $from = $this->makeWalletAccount('Yape');
        $to = $this->makeBankAccountAccount();
        $this->fund($from);

        $transfer = $this->service->transfer(
            $from,
            $to,
            2000,
            now()->toDateString(),
            $this->datos(['to_fixed_fee' => 1]),
        );

        $this->service->void($transfer, auth()->id());

        $this->assertSame(0, TreasuryTransaction::where('transfer_id', $transfer->id)->whereNull('voided_at')->count());
        // Al anularse todo, el origen vuelve al saldo que tenía antes de transferir.
        $this->assertSame(5000.0, app(TreasuryLedgerService::class)->accountBalance($from->refresh()));
    }

    public function test_el_perfil_del_par_guarda_la_eleccion_del_check(): void
    {
        $from = $this->makeWalletAccount('Yape');
        $to = $this->makeBankAccountAccount();
        $this->fund($from, 5000);

        $this->service->transfer(
            $from,
            $to,
            500,
            now()->toDateString(),
            $this->datos(['own_account_itf_free' => true, 'to_fixed_fee' => 1]),
        );

        $perfil = TreasuryTransferProfile::where('from_account_id', $from->id)
            ->where('to_account_id', $to->id)
            ->firstOrFail();
        $this->assertTrue((bool) $perfil->own_account_itf_free);
        $this->assertSame(1.0, (float) $perfil->to_fixed_fee);

        // Al desactivarlo en una nueva transferencia, el perfil se actualiza, no se duplica
        $this->service->transfer($from, $to, 500, now()->toDateString(), $this->datos());

        $this->assertSame(1, TreasuryTransferProfile::count());
        $this->assertFalse((bool) $perfil->fresh()->own_account_itf_free);
    }

    public function test_no_permite_transferir_a_la_misma_cuenta(): void
    {
        $from = $this->makeWalletAccount('Yape');

        $this->expectException(InvalidArgumentException::class);

        $this->service->transfer($from, $from, 100, now()->toDateString(), $this->datos());
    }

    public function test_sincronizacion_crea_cuenta_para_banco_y_billetera_sin_duplicar(): void
    {
        $this->makeBankAccountAccountOnly();
        $this->makeWalletAccountOnly('Plin');

        $sync = app(TreasuryAccountSyncService::class);

        // La caja de efectivo y la cuenta de detracción las crean las
        // migraciones; aquí se crean banco, billetera y la cuenta MercadoPago.
        $this->assertSame(3, $sync->syncAll());
        $this->assertSame(0, $sync->syncAll());
        $this->assertSame(5, TreasuryAccount::count());
        $this->assertDatabaseHas('treasury_accounts', ['type' => TreasuryAccount::TYPE_CASH]);
        $this->assertDatabaseHas('treasury_accounts', ['type' => TreasuryAccount::TYPE_DETRACTION]);
    }

    public function test_crear_billetera_crea_su_cuenta_de_tesoreria(): void
    {
        $billetera = $this->makeWalletAccountOnly('Yape');

        app(TreasuryAccountSyncService::class)->ensureForWallet($billetera);

        $this->assertDatabaseHas('treasury_accounts', [
            'type' => TreasuryAccount::TYPE_WALLET,
        ]);
    }

    /**
     * Fondea la cuenta origen: el libro no permite egresos que dejen saldo
     * negativo, y estos casos prueban comisiones, no el sobregiro.
     */
    private function fund(TreasuryAccount $account, float $amount = 5000): void
    {
        app(TreasuryLedgerService::class)->registerIncome(
            $account,
            now()->toDateString(),
            $amount,
            null,
            null,
            null,
            null,
            'Fondeo de prueba',
        );
    }

    /** Cuenta de tesorería para una billetera (crea también la billetera). */
    private function makeWalletAccount(string $nombre): TreasuryAccount
    {
        return app(TreasuryAccountSyncService::class)->ensureForWallet($this->makeWalletAccountOnly($nombre));
    }

    /** Cuenta de tesorería para un banco en PEN. */
    private function makeBankAccountAccount(): TreasuryAccount
    {
        return app(TreasuryAccountSyncService::class)->ensureForBankAccount($this->makeBankAccountAccountOnly());
    }

    private function makeBankAccountAccountOnly(): BankAccount
    {
        $bank = Bank::create(['short_name' => 'BCP', 'full_name' => 'Banco de Crédito del Perú', 'status' => true]);

        return BankAccount::create([
            'bank_id' => $bank->id,
            'description' => 'Cuenta corriente PEN',
            'number' => '193-0001-PEN',
            'currency_type_id' => 'PEN',
            'status' => true,
        ]);
    }

    private function makeWalletAccountOnly(string $nombre): CompanyBilletera
    {
        $billetera = BilleteraDigital::create([
            'short_name' => $nombre,
            'full_name' => $nombre . ' Digital',
            'status' => true,
        ]);

        return CompanyBilletera::create([
            'billetera_id' => $billetera->id,
            'account_name' => 'ARACODE',
            'account_number' => '999888777',
            'status' => true,
        ]);
    }

    public function test_ingreso_de_billetera_vinculada_cae_en_el_banco_y_la_billetera_no_muestra_saldo(): void
    {
        $bankAccount = $this->makeBankAccountAccountOnly();
        $companyBilletera = $this->makeWalletAccountOnly('Yape');
        $companyBilletera->update(['bank_account_id' => $bankAccount->id]);

        $wallet = app(TreasuryAccountSyncService::class)->ensureForWallet($companyBilletera->fresh());
        $bank = app(TreasuryAccountSyncService::class)->ensureForBankAccount($bankAccount);

        $ledger = app(\Modules\Treasury\Services\TreasuryLedgerService::class);
        $integration = app(\Modules\Treasury\Services\TreasuryIntegrationService::class);

        // Ingreso de una venta cobrada con la billetera (flujo real del puente).
        $bridge = $integration->register(new \Modules\Treasury\Services\FinancialMovement(
            module: 'sales',
            sourceType: 'sale_document',
            sourceId: 990001,
            operation: 'sale_payment',
            type: TreasuryTransaction::TYPE_INCOME,
            amount: 80,
            date: now()->toDateString(),
            treasuryAccountId: $wallet->id,
            description: 'Venta boleta',
        ));
        $integration->process($bridge);

        // El ingreso queda en el banco, con la billetera de origen registrada.
        $this->assertSame(80.0, $ledger->accountBalance($bank->fresh()));
        $this->assertDatabaseHas('treasury_transactions', [
            'treasury_account_id' => $bank->id,
            'type' => TreasuryTransaction::TYPE_INCOME,
            'source_wallet_account_id' => $wallet->id,
        ]);

        // La billetera vinculada no tiene saldo propio, y su periodo muestra el ingreso.
        $balances = collect($ledger->balancesByAccount());
        $this->assertSame(0.0, $balances->firstWhere('account.id', $wallet->id)['balance']);
    }
}
