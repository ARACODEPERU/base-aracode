<?php

namespace Tests\Unit\Modules\Security;

use Modules\Security\Services\PermissionActions;
use Tests\TestCase;

/**
 * Clasificación de permisos del Modo Super Editor: traduce el nombre de un
 * permiso del proyecto (`aca_estudiante_listado`, `usuarios`, `usuarios_nuevo`,
 * `res_insumos_compra`) a una de las acciones del panel.
 *
 * Es lógica pura y vive toda en PermissionActions: si aquí cambia una
 * expectativa, cambia en la tabla única de esa clase y no en el servicio.
 */
class PermissionActionsTest extends TestCase
{
    public function test_reconoce_la_accion_de_cada_sufijo(): void
    {
        $this->assertSame('ver', PermissionActions::actionFor('aca_estudiante_listado'));
        $this->assertSame('ver', PermissionActions::actionFor('usuarios_ver'));
        $this->assertSame('ver', PermissionActions::actionFor('sale_dashboard'));
        $this->assertSame('crear', PermissionActions::actionFor('usuarios_nuevo'));
        $this->assertSame('crear', PermissionActions::actionFor('integrationhub_crear'));
        $this->assertSame('editar', PermissionActions::actionFor('comm_negociaciones_verificar'));
        $this->assertSame('editar', PermissionActions::actionFor('treasury_cuentas_editar'));
        $this->assertSame('eliminar', PermissionActions::actionFor('usuarios_eliminar'));
        $this->assertSame('ejecutar', PermissionActions::actionFor('res_venta_exportar'));
        $this->assertSame('ejecutar', PermissionActions::actionFor('aca_estudiante_matricular'));
    }

    public function test_un_permiso_de_seccion_sin_sufijo_es_ver(): void
    {
        // `usuarios`, `empresa`, `roles` no llevan sufijo: son el permiso que
        // abre una sección completa, es decir el "Ver" de esta aplicación.
        $this->assertSame('ver', PermissionActions::actionFor('usuarios'));
        $this->assertSame('ver', PermissionActions::actionFor('empresa'));
        $this->assertSame('ver', PermissionActions::actionFor('roles'));
        $this->assertSame('ver', PermissionActions::actionFor('parametros'));

        // Un nombre compuesto sin acción reconocida sigue siendo "Otros".
        $this->assertSame('otros', PermissionActions::actionFor('res_insumos_compra'));
        $this->assertSame('otros', PermissionActions::actionFor(''));
    }

    public function test_el_prefijo_quita_el_sufijo_de_accion(): void
    {
        $this->assertSame('aca_estudiante', PermissionActions::prefixFor('aca_estudiante_listado'));
        $this->assertSame('integrationhub', PermissionActions::prefixFor('integrationhub_crear'));
        $this->assertSame('treasury_cuentas', PermissionActions::prefixFor('treasury_cuentas_editar'));
        $this->assertSame('comm_negociaciones', PermissionActions::prefixFor('comm_negociaciones_verificar'));

        // Sin sufijo reconocido, el prefijo es el nombre completo: no se recorta
        // de más y el permiso sigue siendo configurable por sí solo.
        $this->assertSame('dashboard', PermissionActions::prefixFor('dashboard'));
        $this->assertSame('usuarios', PermissionActions::prefixFor('usuarios'));
        $this->assertSame('res_insumos_compra', PermissionActions::prefixFor('res_insumos_compra'));
    }

    public function test_cada_accion_tiene_etiqueta_y_verbo(): void
    {
        $this->assertSame(['ver', 'crear', 'editar', 'eliminar', 'ejecutar', 'otros'], PermissionActions::keys());
        $this->assertSame('Ver', PermissionActions::label('ver'));
        $this->assertSame('Eliminar', PermissionActions::label('eliminar'));
        $this->assertSame('ver', PermissionActions::phrase('ver'));
        $this->assertSame('usar', PermissionActions::phrase('otros'));

        // Una acción desconocida nunca rompe la etiqueta del panel.
        $this->assertSame('Otros', PermissionActions::label('inexistente'));
    }
}
