<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Guardado del estilo visual de Aracodito desde la configuración del
 * asistente (Configuración de Aracodito).
 */
class AracoditoStyleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // La ruta de guardado exige el permiso de configuración del asistente.
        Permission::firstOrCreate(['name' => 'conf_asistente', 'guard_name' => 'web']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_guarda_los_estilos_clasico_pixel_y_futbolero(): void
    {
        $user = $this->usuarioConPermiso();

        foreach (['classic', 'pixel', 'futbol'] as $style) {
            $this->actingAs($user)
                ->put(route('profile.aracodito.update'), [
                    'aracodito_enabled' => true,
                    'aracodito_style' => $style,
                ])
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('profile.aracodito'));

            $this->assertSame($style, $user->fresh()->aracodito_style);
        }
    }

    public function test_rechaza_un_estilo_que_no_existe(): void
    {
        $user = $this->usuarioConPermiso();
        $user->aracodito_style = 'pixel';
        $user->save();

        $this->actingAs($user)
            ->from(route('profile.aracodito'))
            ->put(route('profile.aracodito.update'), [
                'aracodito_enabled' => true,
                'aracodito_style' => 'neon',
            ])
            ->assertSessionHasErrors('aracodito_style')
            ->assertRedirect(route('profile.aracodito'));

        // Un valor fuera de la lista no se guarda: se conserva el estilo previo.
        $this->assertSame('pixel', $user->fresh()->aracodito_style);
    }

    /**
     * Usuario autenticado con el permiso de configuración del asistente.
     */
    private function usuarioConPermiso(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('conf_asistente');

        return $user;
    }
}
