<?php

namespace Tests\Feature\Treasury;

use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Sales\Entities\SaleExchangeRate;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasuryPaymentOrder;
use Modules\Treasury\Entities\TreasuryRecurringExpense;
use Modules\Treasury\Entities\TreasuryTransaction;
use Modules\Treasury\Exceptions\TreasuryOperationException;
use Modules\Treasury\Services\TreasuryAccountSyncService;
use Modules\Treasury\Services\TreasuryLedgerService;
use Modules\Treasury\Services\TreasuryPaymentOrderService;
use Modules\Treasury\Services\TreasuryPeriodService;
use Modules\Treasury\Services\TreasuryRecurringExpenseService;
use Modules\Treasury\Services\TreasuryReconciliationService;
use Modules\Treasury\Services\TreasuryTransferService;
use Tests\TestCase;

/**
 * Tesorería v2: bloqueo por periodo y conciliación, moneda explícita,
 * consolidado, transferencias entre monedas, órdenes con aprobación y
 * ejecución idempotente de gastos recurrentes.
 */
class TreasuryV2Test extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_el_periodo_cerrado_bloquea_registros_con_esa_fecha(): void
    {
        $account = $this->makeAccount('PEN');

        app(TreasuryLedgerService::class)->registerIncome($account, now()->toDateString(), 100, null, null, null, null, 'Ingreso');

        app(TreasuryPeriodService::class)->close((int) now()->year, (int) now()->month, $this->user->id, 'Cierre de prueba');

        $this->expectException(TreasuryOperationException::class);

        app(TreasuryLedgerService::class)->registerIncome($account, now()->toDateString(), 50, null, null, null, null, 'Ingreso posterior');
    }

    public function test_la_conciliacion_cuadrada_cierra_y_bloquea_las_lineas(): void
    {
        $account = $this->makeAccount('PEN');
        $ledger = app(TreasuryLedgerService::class);

        $income = $ledger->registerIncome($account, now()->toDateString(), 500, null, null, null, null, 'Cobro');
        $ledger->registerExpense($account, now()->toDateString(), 200, null, null, null, null, 'Pago');

        $balance = $ledger->accountBalance($account);

        $act = app(TreasuryReconciliationService::class)->close(
            $account,
            now()->toDateString(),
            $balance,
            [],
            $this->user->id,
        );

        $this->assertSame('closed', $act->status);
        $this->assertNotNull($act->snapshot_hash);
        $this->assertSame($this->user->id, $act->closed_by);

        $income->refresh();
        $this->assertTrue($income->isLocked());

        $this->expectException(TreasuryOperationException::class);
        $income->void($this->user->id);
    }

    public function test_la_conciliacion_no_cierra_si_la_diferencia_no_esta_explicada(): void
    {
        $account = $this->makeAccount('PEN');

        app(TreasuryLedgerService::class)->registerIncome($account, now()->toDateString(), 500, null, null, null, null, 'Cobro');

        $this->expectException(TreasuryOperationException::class);

        app(TreasuryReconciliationService::class)->close(
            $account,
            now()->toDateString(),
            450,
            [],
            $this->user->id,
        );
    }

    public function test_un_periodo_cerrado_no_se_duplica_ni_se_recalcula(): void
    {
        $account = $this->makeAccount('PEN');
        $periods = app(TreasuryPeriodService::class);

        $first = $periods->close((int) now()->year, (int) now()->month, $this->user->id);
        $second = $periods->close((int) now()->year, (int) now()->month, $this->user->id);

        $this->assertSame($first->id, $second->id);

        // Una sola foto por cuenta: el segundo cierre no agrega ni recalcula nada.
        $duplicadas = $first->balances()
            ->selectRaw('treasury_account_id, count(*) as total')
            ->groupBy('treasury_account_id')
            ->havingRaw('count(*) > 1')
            ->get();

        $this->assertCount(0, $duplicadas);
        $this->assertSame(TreasuryAccount::count(), $first->balances()->count());
    }

    public function test_el_consolidado_no_suma_soles_con_dolares(): void
    {
        SaleExchangeRate::create([
            'currency_code' => 'USD',
            'rate_date' => now()->toDateString(),
            'purchase_rate' => 3.70,
            'sale_rate' => 3.75,
            'source' => 'manual',
        ]);

        $pen = $this->makeAccount('PEN');
        $usd = $this->makeAccount('USD');
        $ledger = app(TreasuryLedgerService::class);

        $ledger->registerIncome($pen, now()->toDateString(), 100, null, null, null, null, 'Soles');
        $ledger->registerIncome($usd, now()->toDateString(), 100, null, null, null, null, 'Dólares', null, 'manual', $this->user->id, false, null, 'USD', 3.75);

        $data = $ledger->consolidatedBalances();

        $groups = collect($data['consolidation']['groups']);
        $this->assertCount(2, $groups);
        $this->assertSame(100.0, $groups->firstWhere('currency', 'PEN')['total']);
        $this->assertSame(100.0, $groups->firstWhere('currency', 'USD')['total']);
        $this->assertSame(475.0, $data['consolidation']['consolidated']);
        $this->assertFalse($data['consolidation']['incomplete']);
    }

    public function test_una_transferencia_entre_monedas_convierte_el_monto_destino(): void
    {
        SaleExchangeRate::create([
            'currency_code' => 'USD',
            'rate_date' => now()->toDateString(),
            'purchase_rate' => 3.70,
            'sale_rate' => 3.75,
            'source' => 'manual',
        ]);

        $usd = $this->makeAccount('USD');
        $pen = $this->makeAccount('PEN');

        app(TreasuryLedgerService::class)->registerIncome($usd, now()->toDateString(), 1000, null, null, null, null, 'Fondeo USD', null, 'manual', $this->user->id, false, null, 'USD', 3.75);

        $transfer = app(TreasuryTransferService::class)->transfer(
            $usd,
            $pen,
            100,
            now()->toDateString(),
            [
                'from_fixed_fee' => 0,
                'from_percent_fee' => 0,
                'to_fixed_fee' => 0,
                'to_percent_fee' => 0,
                'own_account_itf_free' => true,
                'charge_origin_fee' => false,
            ],
        );

        $this->assertSame('USD', $transfer->from_currency);
        $this->assertSame('PEN', $transfer->to_currency);
        $this->assertSame(3.75, (float) $transfer->exchange_rate);
        $this->assertSame(375.0, (float) $transfer->amount_destination);
        $this->assertSame(375.0, app(TreasuryLedgerService::class)->accountBalance($pen));
        $this->assertSame(900.0, app(TreasuryLedgerService::class)->accountBalance($usd));
    }

    public function test_una_transferencia_entre_monedas_sin_tipo_de_cambio_falla(): void
    {
        $usd = $this->makeAccount('USD');
        $pen = $this->makeAccount('PEN');

        $this->expectException(TreasuryOperationException::class);

        app(TreasuryTransferService::class)->transfer(
            $usd,
            $pen,
            100,
            now()->toDateString(),
            [
                'from_fixed_fee' => 0,
                'from_percent_fee' => 0,
                'to_fixed_fee' => 0,
                'to_percent_fee' => 0,
                'own_account_itf_free' => true,
                'charge_origin_fee' => false,
            ],
        );
    }

    public function test_una_orden_sobre_el_umbral_requiere_otro_aprobador_y_paga_una_sola_vez(): void
    {
        config(['treasury.approval_threshold' => 2000, 'treasury.require_different_approver' => true]);

        $account = $this->makeAccount('PEN');
        app(TreasuryLedgerService::class)->registerIncome($account, now()->toDateString(), 5000, null, null, null, null, 'Fondeo');

        $orders = app(TreasuryPaymentOrderService::class);

        $order = $orders->create([
            'type' => TreasuryPaymentOrder::TYPE_SUPPLIER_PAYMENT,
            'description' => 'Pago a proveedor de prueba',
            'currency' => 'PEN',
            'amount' => 2500,
            'issue_date' => now()->toDateString(),
            'treasury_account_id' => $account->id,
            'idempotency_key' => 'test-order-1',
        ], $this->user->id);

        $this->assertSame(TreasuryPaymentOrder::STATUS_PENDING, $order->status);

        // El mismo usuario no puede aprobar su propia orden.
        try {
            $orders->approve($order, $this->user->id);
            $this->fail('La aprobación del creador debió rechazarse.');
        } catch (TreasuryOperationException $e) {
            $this->assertStringContainsString('otro usuario', $e->getMessage());
        }

        $approver = User::factory()->create();
        $this->actingAs($approver);

        $orders->approve($order, $approver->id);
        $orders->pay($order, $approver->id);
        $orders->pay($order, $approver->id);

        $this->assertSame(TreasuryPaymentOrder::STATUS_PAID, $order->fresh()->status);
        $this->assertSame(1, TreasuryTransaction::query()
            ->where('origin_type', 'payment_order')
            ->where('origin_id', $order->id)
            ->count());
        $this->assertSame(2500.0, app(TreasuryLedgerService::class)->accountBalance($account));
    }

    public function test_crear_la_misma_orden_dos_veces_devuelve_la_existente(): void
    {
        $account = $this->makeAccount('PEN');
        $orders = app(TreasuryPaymentOrderService::class);

        $data = [
            'type' => TreasuryPaymentOrder::TYPE_PURCHASE,
            'description' => 'Compra repetida',
            'currency' => 'PEN',
            'amount' => 100,
            'issue_date' => now()->toDateString(),
            'treasury_account_id' => $account->id,
            'idempotency_key' => 'misma-clave',
        ];

        $first = $orders->create($data, $this->user->id);
        $second = $orders->create($data, $this->user->id);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, TreasuryPaymentOrder::count());
    }

    public function test_el_gasto_recurrente_no_duplica_el_mismo_periodo(): void
    {
        $account = $this->makeAccount('PEN');
        app(TreasuryLedgerService::class)->registerIncome($account, now()->toDateString(), 5000, null, null, null, null, 'Fondeo');

        $recurring = app(TreasuryRecurringExpenseService::class);

        $expense = $recurring->create([
            'concept' => 'Alquiler de oficina',
            'type' => 'rent',
            'amount' => 800,
            'currency' => 'PEN',
            'treasury_account_id' => $account->id,
            'frequency' => TreasuryRecurringExpense::FREQ_MONTHLY,
            'start_date' => now()->toDateString(),
            'auto_pay' => false,
        ], $this->user->id);

        $runDate = $expense->next_run_date->toDateString();

        $first = $recurring->run($expense, $this->user->id);
        $this->assertNotNull($first);

        // Se fuerza la misma fecha para comprobar que la clave del periodo evita duplicar.
        $expense->refresh();
        $expense->next_run_date = $runDate;
        $expense->save();

        $second = $recurring->run($expense, $this->user->id);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, TreasuryPaymentOrder::query()->where('type', TreasuryPaymentOrder::TYPE_RECURRING)->count());
    }

    /**
     * Cuenta de tesorería de banco en la moneda indicada.
     */
    private function makeAccount(string $currency = 'PEN'): TreasuryAccount
    {
        $bank = Bank::firstOrCreate(
            ['short_name' => 'BCP'],
            ['full_name' => 'Banco de Crédito del Perú', 'status' => true]
        );

        $bankAccount = BankAccount::create([
            'bank_id' => $bank->id,
            'description' => 'Cuenta ' . $currency,
            'number' => '193-' . $currency . '-' . uniqid(),
            'currency_type_id' => $currency,
            'status' => true,
        ]);

        return app(TreasuryAccountSyncService::class)->ensureForBankAccount($bankAccount);
    }
}
