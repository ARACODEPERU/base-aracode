<?php

namespace Tests\Feature\Health;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Las rutas del módulo Salud exigen los permisos que ya usa el menú.
 *
 * Antes bastaba con tener sesión: cualquier cuenta entraba a pacientes,
 * atenciones, historia clínica o agenda escribiendo la URL.
 */
class HealRoutesPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_usuario_sin_permisos_no_entra_a_las_rutas_del_modulo(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $usuario = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($usuario);

        $rutas = [
            route('heal_patients_list'),
            route('heal_patients_create'),
            route('heal_doctors_list'),
            route('heal_attentions_list'),
            route('heal_clinical_records_list'),
            route('heal_agendas_list'),
        ];

        foreach ($rutas as $ruta) {
            $this->get($ruta)->assertForbidden();
        }

        $this->delete(route('heal_patients_destroy', 1))->assertForbidden();
        $this->delete(route('heal_doctors_destroy', 1))->assertForbidden();
    }
}
