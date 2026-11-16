<?php

namespace Tests\Feature\Treasury;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasuryCostCenter;
use Modules\Treasury\Entities\TreasuryTransaction;
use Modules\Treasury\Services\TreasuryLedgerService;
use Modules\Treasury\Services\TreasuryReportService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Pruebas de las rutas HTTP de Tesorería v2.
 *
 * Los tests de servicios no cubren el middleware de permisos ni la
 * validación de los formularios: aquí se entra por ruta, como el usuario.
 */
class TreasuryScreensTest extends TestCase
{
    use RefreshDatabase;

    /** Pantalla => permiso que la habilita. */
    private const SCREENS = [
        'treasury_dashboard' => 'treasury_dashboard',
        'treasury_accounts_index' => 'treasury_cuentas',
        'treasury_transfers_index' => 'treasury_cuentas',
        'treasury_transactions_index' => 'treasury_movimientos',
        'treasury_payments_index' => 'treasury_pagos',
        'treasury_forecast_index' => 'treasury_prevision',
        'treasury_periods_index' => 'treasury_periodos',
        'treasury_reports_index' => 'treasury_reportes',
        'treasury_taxes_index' => 'treasury_impuestos',
        'treasury_detraction_index' => 'treasury_detraccion',
        'treasury_configuration_index' => 'treasury_configuracion',
        'treasury_audit_index' => 'treasury_auditoria',
        'treasury_integration_index' => 'treasury_reintentos',
        'treasury_reconciliation_index' => 'treasury_conciliacion',
    ];

    public function test_las_pantallas_nuevas_cargan_con_su_permiso(): void
    {
        $user = $this->userWith(array_values(self::SCREENS));

        foreach (array_keys(self::SCREENS) as $routeName) {
            $this->actingAs($user)
                ->get(route($routeName))
                ->assertOk();
        }
    }

    public function test_sin_permiso_la_pantalla_responde_403(): void
    {
        $user = $this->userWith([]);

        $this->actingAs($user)
            ->get(route('treasury_dashboard'))
            ->assertForbidden();
    }

    public function test_un_movimiento_manual_guarda_centro_de_costo_y_el_reporte_lo_agrupa(): void
    {
        $user = $this->userWith(['treasury_movimientos', 'treasury_movimientos_nuevo', 'treasury_reportes']);
        $account = $this->makeAccount();
        $center = TreasuryCostCenter::create(['code' => 'ADM', 'name' => 'Administración', 'status' => true]);

        // Fondeo para que el egreso no choque con el bloqueo de sobregiro.
        app(TreasuryLedgerService::class)->registerIncome(
            $account,
            now()->toDateString(),
            1000,
            null,
            null,
            null,
            null,
            'Fondeo de prueba',
        );

        $this->actingAs($user)
            ->post(route('treasury_transactions_store'), [
                'treasury_account_id' => $account->id,
                'transaction_date' => now()->toDateString(),
                'type' => TreasuryTransaction::TYPE_EXPENSE,
                'amount' => 300,
                'description' => 'Alquiler de oficina',
                'cost_center_id' => $center->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('treasury_transactions', [
            'treasury_account_id' => $account->id,
            'type' => TreasuryTransaction::TYPE_EXPENSE,
            'cost_center_id' => $center->id,
        ]);

        $rows = collect(app(TreasuryReportService::class)->byCostCenter(
            now()->toDateString(),
            now()->toDateString(),
            'PEN',
        ));

        $admin = $rows->firstWhere('center', 'Administración');

        $this->assertNotNull($admin, 'El reporte debe agrupar el egreso en su centro de costo.');
        $this->assertSame(300.0, $admin['expenses']);
        $this->assertSame(1, $admin['movements']);

        $sinCentro = $rows->firstWhere('center', 'Sin centro de costo');
        $this->assertTrue(
            $sinCentro === null || $sinCentro['expenses'] < 300.0,
            'El egreso no debe contarse en «Sin centro de costo».',
        );
    }

    public function test_el_listado_de_movimientos_expone_el_centro_de_costo_para_editar(): void
    {
        $user = $this->userWith(['treasury_movimientos']);
        $account = $this->makeAccount();
        $center = TreasuryCostCenter::create(['code' => 'ALM', 'name' => 'Almacén', 'status' => true]);

        app(TreasuryLedgerService::class)->registerExpense(
            $account,
            now()->toDateString(),
            150,
            null,
            null,
            null,
            null,
            'Compra de insumos',
            null,
            TreasuryTransaction::SOURCE_MANUAL,
            $user->id,
            false,
            null,
            'PEN',
            null,
            $center->id,
        );

        $statement = collect(app(TreasuryLedgerService::class)->statement($account));

        $row = $statement->firstWhere('description', 'Compra de insumos');

        $this->assertSame($center->id, $row['cost_center_id']);
        $this->assertSame('Almacén', $row['cost_center']);

        $this->actingAs($user)
            ->get(route('treasury_transactions_index', ['account_id' => $account->id]))
            ->assertOk();
    }

    public function test_el_reporte_incluye_la_proyeccion_y_respeta_el_periodo_pedido(): void
    {
        $user = $this->userWith(['treasury_reportes']);
        $this->makeAccount();

        $response = $this->actingAs($user)->get(route('treasury_reports_index', [
            'from' => now()->startOfMonth()->toDateString(),
            'to' => now()->toDateString(),
            'currency' => 'PEN',
            'days' => 60,
            'preset' => 'month_current',
        ]));

        $response->assertOk();

        $props = $response->viewData('page')['props'];

        $this->assertArrayHasKey('projection', $props);
        $this->assertSame('PEN', $props['projection']['currency']);
        $this->assertSame(60, $props['projection']['days']);
        $this->assertArrayHasKey('totals', $props['projection']);
        $this->assertArrayHasKey('rows', $props['projection']);
        $this->assertSame('month_current', $props['filters']['preset']);
        $this->assertSame(60, $props['filters']['days']);
    }

    public function test_cerrar_conciliacion_exige_el_permiso_especifico(): void
    {
        $user = $this->userWith(['treasury_conciliacion']);

        $this->actingAs($user)
            ->post(route('treasury_reconciliation_close'), [])
            ->assertForbidden();
    }

    public function test_aprobar_pagos_exige_permiso_de_aprobacion(): void
    {
        $user = $this->userWith(['treasury_pagos', 'treasury_pagos_nuevo']);

        $this->actingAs($user)
            ->post(route('treasury_payments_approve', 1))
            ->assertForbidden();
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

    private function makeAccount(): TreasuryAccount
    {
        // Caja: no exige cuenta bancaria vinculada y no bloquea sobregiro.
        return TreasuryAccount::create([
            'type' => TreasuryAccount::TYPE_CASH,
            'label' => 'Caja de prueba',
            'currency_type_id' => 'PEN',
            'opening_balance' => 0,
            'status' => true,
        ]);
    }
}
