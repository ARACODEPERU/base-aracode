<?php

namespace Tests\Unit\Modules\Security;

use Illuminate\Support\Facades\Route;
use Modules\Security\Entities\SuperEditorStagedChange;
use Modules\Security\Services\PermissionPanel;
use Tests\TestCase;
use Tests\Unit\Modules\Security\Concerns\BuildsSuperEditorSchema;

/**
 * Panel "Configurar permisos de acceso": qué acciones ofrece para un elemento.
 *
 * Esta prueba construye PermissionPanel a mano: no hay sesión de edición, ni
 * petición HTTP, ni ciclo de vida del modo. Solo el esquema y el panel, que es
 * lo que permite cambiar la clasificación de los permisos sin tocar el motor
 * del modo (y viceversa).
 */
class PermissionPanelTest extends TestCase
{
    use BuildsSuperEditorSchema;

    private PermissionPanel $panel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dropSuperEditorSchema();
        $this->createSuperEditorSchema();

        $this->panel = new PermissionPanel();
    }

    protected function tearDown(): void
    {
        $this->dropSuperEditorSchema();

        parent::tearDown();
    }

    // -----------------------------------------------------------------
    // Agrupación por acción
    // -----------------------------------------------------------------

    public function test_agrupa_los_permisos_hermanos_por_accion(): void
    {
        $admin = $this->role('admin');
        $ventas = $this->role('ventas');

        $this->permission('integrationhub_listado');
        $this->permission('integrationhub_crear');
        $this->permission('integrationhub_editar');
        $this->permission('treasury_cuentas_nuevo');

        $admin->givePermissionTo('integrationhub_listado');
        $ventas->givePermissionTo('integrationhub_crear');

        $panel = $this->panel->element('integrationhub_listado', [
            'label' => 'Centro de Integraciones',
            'kind' => 'opción de menú',
            'url' => '/integrationhub',
        ]);

        $this->assertSame(
            ['integrationhub_listado', 'integrationhub_crear', 'integrationhub_editar'],
            array_column($panel['element']['actions'], 'permission')
        );
        $this->assertSame(['ver', 'crear', 'editar'], array_column($panel['element']['actions'], 'action'));
        $this->assertSame('Centro de Integraciones', $panel['element']['label']);
        $this->assertTrue($panel['element']['exists']);

        // Otros módulos no se cuelan en el grupo.
        $this->assertNotContains('treasury_cuentas_nuevo', array_column($panel['element']['actions'], 'permission'));

        $roles = collect($panel['roles'])->keyBy('name');

        $this->assertTrue($roles['admin']['current']['integrationhub_listado']);
        $this->assertFalse($roles['admin']['current']['integrationhub_crear']);
        $this->assertFalse($roles['ventas']['current']['integrationhub_listado']);
        $this->assertTrue($roles['ventas']['current']['integrationhub_crear']);
        $this->assertNull($roles['ventas']['staged']['integrationhub_listado']);
    }

    public function test_ofrece_una_pestana_por_accion_con_el_permiso_que_las_rutas_exigen(): void
    {
        $this->role('admin');
        $this->permission('pagos_listado');
        $this->permission('pagos_ver');
        $this->permission('pagos_nuevo');

        // `pagos_listado` es el permiso que el backend exige de verdad; `pagos_ver`
        // existe en la tabla de permisos pero ninguna ruta lo pide.
        Route::middleware(['web', 'permission:pagos_listado'])
            ->get('/__panel_probe_pagos', fn () => response('ok'));

        $actions = $this->panel->element('pagos_nuevo')['element']['actions'];

        // Las dos formas de decir "Ver" van en la misma pestaña, no en dos.
        $this->assertSame(['ver', 'crear'], array_column($actions, 'action'));
        $this->assertSame(['Ver', 'Crear'], array_column($actions, 'label'));

        // Gana el permiso exigido por una ruta, aunque el huérfano sea más corto.
        $this->assertSame('pagos_listado', $actions[0]['permission']);
        $this->assertTrue($actions[0]['enforced']);
        $this->assertFalse($actions[0]['own']);
        $this->assertSame(
            ['pagos_listado', 'pagos_ver'],
            array_column($actions[0]['candidates'], 'permission')
        );
        $this->assertSame([true, false], array_column($actions[0]['candidates'], 'enforced'));

        // La acción del propio elemento se marca como suya.
        $this->assertSame('pagos_nuevo', $actions[1]['permission']);
        $this->assertTrue($actions[1]['own']);
        $this->assertSame('crear', $actions[1]['phrase']);
        $this->assertFalse($actions[1]['enforced']);
    }

    public function test_al_editar_la_accion_del_elemento_manda_su_propio_permiso(): void
    {
        $this->role('admin');
        $this->permission('pagos_listado');
        $this->permission('pagos_ver');

        Route::middleware(['web', 'permission:pagos_listado'])
            ->get('/__panel_probe_pagos_ver', fn () => response('ok'));

        $actions = $this->panel->element('pagos_ver')['element']['actions'];

        // El engrane se clickeó en un elemento que usa `pagos_ver`: esa pestaña
        // edita ese permiso y ofrece el exigido por rutas como alternativa.
        $this->assertSame('pagos_ver', $actions[0]['permission']);
        $this->assertTrue($actions[0]['own']);
        $this->assertFalse($actions[0]['enforced']);
        $this->assertSame(
            ['pagos_ver', 'pagos_listado'],
            array_column($actions[0]['candidates'], 'permission')
        );
    }

    public function test_un_elemento_cuyo_permiso_no_existe_todavia_se_puede_configurar(): void
    {
        $this->role('admin');

        $panel = $this->panel->element('integrationhub_listado');

        $this->assertFalse($panel['element']['exists']);
        $this->assertSame('integrationhub_listado', $panel['element']['label'], 'Sin etiqueta se usa el nombre del permiso.');
        $this->assertSame(['integrationhub_listado'], array_column($panel['element']['actions'], 'permission'));
        $this->assertTrue($panel['element']['actions'][0]['own']);
    }

    // -----------------------------------------------------------------
    // Borrador
    // -----------------------------------------------------------------

    public function test_refleja_el_borrador_de_la_sesion_de_edicion(): void
    {
        $this->role('admin');
        $ventas = $this->role('ventas');
        $this->permission('integrationhub_listado');

        // Sin sesión de edición no hay borrador que leer.
        $this->assertSame([], $this->panel->element('integrationhub_listado')['staged']);

        SuperEditorStagedChange::create([
            'session_id' => 42,
            'permission_name' => 'integrationhub_listado',
            'role_id' => $ventas->id,
            'role_name' => $ventas->name,
            'allowed' => true,
            'allowed_before' => false,
        ]);

        $panel = $this->panel->element('integrationhub_listado', [], 42);
        $roles = collect($panel['roles'])->keyBy('name');

        $this->assertTrue($roles['ventas']['staged']['integrationhub_listado']);
        $this->assertFalse($roles['ventas']['current']['integrationhub_listado']);
        $this->assertNull($roles['admin']['staged']['integrationhub_listado']);
        $this->assertCount(1, $panel['staged']);
        $this->assertTrue($panel['staged'][0]['allowed']);
    }
}
