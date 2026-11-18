<?php

namespace Tests\Feature\Treasury;

use App\Models\Expense;
use App\Models\PettyCash;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Services\TreasuryLedgerService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Integración entre las pestañas de Tesorería (Fase 3): el efectivo esperado
 * en las cajas abiertas se lee igual en Cuentas, Movimientos y Dashboard, y la
 * pantalla de Efectivo enlaza con el libro de su cuenta.
 */
class TreasuryCrossTabPhase3Test extends TestCase
{
    use RefreshDatabase;

    public function test_el_efectivo_de_cajas_abiertas_cruza_cuentas_movimientos_y_dashboard(): void
    {
        $user = $this->userWith([
            'treasury_cuentas',
            'treasury_movimientos',
            'treasury_dashboard',
            'treasury_efectivo',
        ]);

        $this->actingAs($user);

        $session = $this->makeCashSession(100, '1');

        Expense::create([
            'description' => 'Compra de útiles',
            'document' => 'B001-25',
            'amount' => 30,
            'petty_cash_id' => $session->id,
        ]);

        // Apertura 100 − egreso 30 = 70 esperados en la caja.
        foreach (['treasury_accounts_index', 'treasury_transactions_index', 'treasury_dashboard'] as $routeName) {
            $this->get(route($routeName))
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->where('cashOpen.count', 1)
                    ->where('cashOpen.balance', 70));
        }

        $this->get(route('treasury_cash_index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('open.count', 1)
                ->where('open.balance', 70));
    }

    public function test_una_caja_cerrada_no_cuenta_como_efectivo_esperado(): void
    {
        $this->actingAs($this->userWith(['treasury_efectivo']));

        $this->makeCashSession(500, '0');

        $this->get(route('treasury_cash_index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('open.count', 0)
                ->where('open.balance', 0));
    }

    public function test_la_pantalla_de_efectivo_enlaza_al_libro_de_su_cuenta(): void
    {
        $this->actingAs($this->userWith(['treasury_efectivo']));

        // El sistema ya crea su cuenta de efectivo; si no existe, se crea aquí.
        $account = TreasuryAccount::query()->active()->cash()->first()
            ?? TreasuryAccount::create([
                'type' => TreasuryAccount::TYPE_CASH,
                'label' => 'Caja principal',
                'currency_type_id' => 'PEN',
                'opening_balance' => 0,
                'status' => true,
            ]);

        app(TreasuryLedgerService::class)
            ->registerIncome($account, now()->toDateString(), 200, null, null, null, null, 'Fondeo de caja');

        $this->get(route('treasury_cash_index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('ledger.account_id', $account->id)
                ->where('ledger.label', $account->displayName())
                ->where('ledger.balance', 200));
    }

    private function makeCashSession(float $beginning, string $state): PettyCash
    {
        return PettyCash::create([
            'user_id' => 1,
            'date_opening' => now()->toDateString(),
            'time_opening' => '08:00:00',
            'beginning_balance' => $beginning,
            'state' => $state,
            'seller_name' => 'Cajero de prueba',
        ]);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function userWith(array $permissions): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $user = User::factory()->create();

        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        return $user;
    }
}
