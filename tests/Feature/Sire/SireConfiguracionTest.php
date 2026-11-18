<?php

namespace Tests\Feature\Sire;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Modules\Sales\Entities\SireSubmissionLog;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Pantalla de configuración del SIRE: credenciales cifradas, modo de trabajo y
 * prueba de conexión (solo token).
 */
class SireConfiguracionTest extends TestCase
{
    use RefreshDatabase;

    private const RUC = '20611376031';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->user = User::factory()->create();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('sire_api', 'web');
        $this->user->givePermissionTo('sire_api');
    }

    public function test_la_pantalla_muestra_el_modo_y_las_credenciales_enmascaradas(): void
    {
        $this->company();

        $this->actingAs($this->user)
            ->get(route('sire_configuracion'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Sales::Sire/Configuracion', false)
                ->where('mode', 'api')
                ->where('credentialsReady', true)
                ->where('credentials.has_client_secret', true)
                ->where('credentials.has_sol_key', true)
                // Nunca se devuelve la credencial completa.
                ->where('credentials.client_id', 'client' . str_repeat('•', 6) . 'ba')
                ->where('credentials.sol_user', 'US' . str_repeat('•', 6) . 'O1'));
    }

    public function test_guarda_las_credenciales_cifradas_y_el_modo(): void
    {
        $company = $this->company([
            'sire_mode' => 'txt',
            'sire_client_id' => null,
            'sire_client_secret' => null,
            'sire_sol_user' => null,
            'sire_sol_key' => null,
        ]);

        $this->actingAs($this->user)
            ->post(route('sire_configuracion_update'), [
                'mode' => 'api',
                'client_id' => 'cliente-nuevo',
                'client_secret' => 'secreto-nuevo',
                'sol_user' => 'USUARIO2',
                'sol_key' => 'clave-nueva',
            ])
            ->assertRedirect()
            ->assertSessionHas('message');

        $company->refresh();

        $this->assertSame('api', $company->sire_mode);
        $this->assertSame('cliente-nuevo', $company->sire_client_id);
        $this->assertSame('secreto-nuevo', $company->sire_client_secret);
        $this->assertSame('USUARIO2', $company->sire_sol_user);
        $this->assertSame('clave-nueva', $company->sire_sol_key);

        $crudo = (string) DB::table('companies')->where('id', $company->id)->value('sire_client_secret');
        $this->assertStringNotContainsString('secreto-nuevo', $crudo);
    }

    public function test_dejar_un_campo_vacio_conserva_la_credencial_anterior(): void
    {
        $company = $this->company();

        $this->actingAs($this->user)
            ->post(route('sire_configuracion_update'), [
                'mode' => 'api',
                'client_id' => '',
                'client_secret' => '',
                'sol_user' => '',
                'sol_key' => '',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $company->refresh();

        $this->assertSame('cliente-prueba', $company->sire_client_id);
        $this->assertSame('clave-sol', $company->sire_sol_key);
    }

    public function test_no_activa_el_modo_api_si_faltan_credenciales(): void
    {
        $company = $this->company([
            'sire_mode' => 'txt',
            'sire_client_id' => null,
            'sire_client_secret' => null,
            'sire_sol_user' => null,
            'sire_sol_key' => null,
        ]);

        $this->actingAs($this->user)
            ->from(route('sire_configuracion'))
            ->post(route('sire_configuracion_update'), [
                'mode' => 'api',
                'client_id' => 'solo-cliente',
                'client_secret' => '',
                'sol_user' => '',
                'sol_key' => '',
            ])
            ->assertRedirect(route('sire_configuracion'))
            ->assertSessionHasErrors('mode');

        $this->assertSame('txt', $company->refresh()->sire_mode);
    }

    public function test_probar_conexion_pide_un_token_nuevo_y_lo_registra(): void
    {
        $this->company();

        Http::fake([
            'api-seguridad.sunat.gob.pe/*' => Http::response([
                'access_token' => 'token-prueba',
                'expires_in' => 3600,
            ]),
        ]);

        $this->actingAs($this->user)
            ->post(route('sire_configuracion_probar'))
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertSame(1, SireSubmissionLog::where('action', 'token')->where('success', true)->count());
        Http::assertSentCount(1);
    }

    public function test_probar_conexion_informa_el_rechazo_de_sunat(): void
    {
        $this->company();

        Http::fake([
            'api-seguridad.sunat.gob.pe/*' => Http::response([
                'error_description' => 'Credenciales inválidas',
            ], 401),
        ]);

        $this->actingAs($this->user)
            ->post(route('sire_configuracion_probar'))
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonFragment(['message' => 'SUNAT rechazó las credenciales: Credenciales inválidas']);

        $this->assertSame(1, SireSubmissionLog::where('action', 'token')->where('success', false)->count());
    }

    public function test_sin_permiso_no_se_entra_a_la_configuracion(): void
    {
        $this->company();

        $otro = User::factory()->create();

        $this->actingAs($otro)->get(route('sire_configuracion'))->assertForbidden();
        $this->actingAs($otro)->post(route('sire_configuracion_probar'))->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function company(array $attributes = []): Company
    {
        return Company::create(array_merge([
            'ruc' => self::RUC,
            'name' => 'ARACODE',
            'business_name' => 'ARACODE SMART SOLUTIONS SAC',
            'tradename' => 'ARACODE',
            'fiscal_address' => 'Av. Los Álamos 123',
            'mode' => 'demo',
            'sire_mode' => 'api',
            'sire_client_id' => 'cliente-prueba',
            'sire_client_secret' => 'secreto-prueba',
            'sire_sol_user' => 'USUARIO1',
            'sire_sol_key' => 'clave-sol',
        ], $attributes));
    }
}
