<?php

namespace Tests\Unit\Support;

use App\Support\ModoAlumno;
use Tests\TestCase;

/**
 * Prioridad de roles al entrar al sistema.
 *
 * Contrato:
 *   - una cuenta con rol 'Alumno' y además un rol de panel entra al panel;
 *   - el campus del alumno se muestra solo cuando 'Alumno' es el único rol de peso;
 *   - el dashboard y el middleware de datos personales usan esa misma regla.
 */
class ModoAlumnoTest extends TestCase
{
    /**
     * Usuario mínimo: solo lo que consulta ModoAlumno (los métodos de Spatie).
     */
    private function usuarioCon(array $roles)
    {
        return new class($roles) {
            private array $roles;

            public function __construct(array $roles)
            {
                $this->roles = $roles;
            }

            public function hasRole($role): bool
            {
                return in_array($role, $this->roles, true);
            }

            public function hasAnyRole($roles): bool
            {
                foreach ((array) $roles as $role) {
                    if ($this->hasRole($role)) {
                        return true;
                    }
                }

                return false;
            }
        };
    }

    public function test_solo_el_rol_alumno_abre_el_campus(): void
    {
        $this->assertTrue(ModoAlumno::activo($this->usuarioCon(['Alumno'])));
    }

    public function test_un_administrador_que_tambien_es_alumno_entra_al_panel(): void
    {
        $this->assertFalse(ModoAlumno::activo($this->usuarioCon(['admin', 'Alumno'])));
        $this->assertFalse(ModoAlumno::activo($this->usuarioCon(['Alumno', 'Administrador'])));
    }

    public function test_cualquier_rol_de_panel_tiene_prioridad_sobre_el_alumno(): void
    {
        foreach (ModoAlumno::ROLES_PANEL as $rol) {
            $this->assertFalse(
                ModoAlumno::activo($this->usuarioCon(['Alumno', $rol])),
                "El rol {$rol} debería abrir el panel y no el campus del alumno."
            );
        }
    }

    public function test_sin_usuario_o_sin_rol_alumno_no_hay_campus(): void
    {
        $this->assertFalse(ModoAlumno::activo(null));
        $this->assertFalse(ModoAlumno::activo($this->usuarioCon([])));
        $this->assertFalse(ModoAlumno::activo($this->usuarioCon(['Docente'])));
    }

    public function test_el_dashboard_y_el_middleware_usan_la_regla_compartida(): void
    {
        $dashboard = file_get_contents(base_path('app/Http/Controllers/DashboardController.php'));
        $middleware = file_get_contents(base_path('app/Http/Middleware/RedirectIfInvalidUserData.php'));

        $this->assertStringContainsString('ModoAlumno::activo', $dashboard);
        $this->assertStringNotContainsString("hasRole('Alumno')", $dashboard);

        $this->assertStringContainsString('ModoAlumno::activo', $middleware);
        $this->assertStringNotContainsString("hasRole('Alumno')", $middleware);
    }

    public function test_el_frontend_usa_la_misma_lista_de_roles(): void
    {
        $js = file_get_contents(base_path('resources/js/utils/modoAlumno.js'));

        foreach (ModoAlumno::ROLES_PANEL as $rol) {
            $this->assertStringContainsString("'{$rol}'", $js, "Falta el rol {$rol} en modoAlumno.js.");
        }
    }
}
