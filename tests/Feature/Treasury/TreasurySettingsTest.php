<?php

namespace Tests\Feature\Treasury;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasuryAuditLog;
use Modules\Treasury\Services\TreasuryLedgerService;
use Modules\Treasury\Services\TreasurySettingsService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Configuración de puesta en marcha de Tesorería: se puede apagar el módulo,
 * se fija la fecha desde la que cuenta y todo cambio es crítico y auditado.
 */
class TreasurySettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_configuracion_arranca_activa_con_fecha_de_inicio(): void
    {
        $settings = app(TreasurySettingsService::class);

        $this->assertTrue($settings->enabled());
        $this->assertNotNull($settings->startsOn());
    }

    public function test_desactivar_bloquea_las_pantallas_pero_no_la_configuracion(): void
    {
        $user = $this->userWith(['treasury_dashboard', 'treasury_configuracion']);

        app(TreasurySettingsService::class)->update(false, now()->toDateString(), $user->id);

        $this->actingAs($user)->get(route('treasury_dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('treasury_configuration_index'))->assertOk();
    }

    public function test_guardar_la_configuracion_exige_contrasena_y_queda_auditado(): void
    {
        $user = $this->userWith(['treasury_configuracion']);
        $user->password = Hash::make('secreto-123');
        $user->save();

        $this->actingAs($user)
            ->post(route('treasury_configuration_update'), [
                'password' => 'incorrecta',
                'enabled' => false,
                'starts_on' => now()->toDateString(),
            ])
            ->assertSessionHas('error');

        $this->assertTrue(app(TreasurySettingsService::class)->enabled());

        $this->actingAs($user)
            ->post(route('treasury_configuration_update'), [
                'password' => 'secreto-123',
                'enabled' => false,
                'starts_on' => now()->subDay()->toDateString(),
            ])
            ->assertSessionHas('success');

        $this->assertFalse(app(TreasurySettingsService::class)->enabled());
        $this->assertSame(now()->subDay()->toDateString(), app(TreasurySettingsService::class)->startsOn());

        $log = TreasuryAuditLog::query()->where('entity', 'treasury_settings')->latest('id')->first();

        $this->assertNotNull($log, 'El cambio de configuración debe quedar en la auditoría.');
        $this->assertTrue((bool) ($log->meta['critical'] ?? false));
        $this->assertSame($user->id, $log->user_id);
    }

    public function test_lo_anterior_a_la_fecha_de_inicio_no_cuenta_en_el_saldo(): void
    {
        $account = TreasuryAccount::create([
            'type' => TreasuryAccount::TYPE_CASH,
            'label' => 'Caja de prueba',
            'currency_type_id' => 'PEN',
            'opening_balance' => 0,
            'status' => true,
        ]);

        $ledger = app(TreasuryLedgerService::class);

        $ledger->registerIncome($account, now()->subDays(10)->toDateString(), 500, null, null, null, null, 'Antes del inicio');
        $ledger->registerIncome($account, now()->toDateString(), 300, null, null, null, null, 'Desde el inicio');

        app(TreasurySettingsService::class)->update(true, now()->toDateString(), null);

        $this->assertSame(300.0, $ledger->accountBalance($account), 'Lo anterior al corte no suma al saldo.');
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
