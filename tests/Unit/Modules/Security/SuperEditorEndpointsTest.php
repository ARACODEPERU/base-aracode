<?php

namespace Tests\Unit\Modules\Security;

use App\Http\Middleware\UserActivityLogMiddleware;
use App\Models\User;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Modules\Security\Entities\SuperEditorAudit;
use Modules\Security\Entities\SuperEditorSession;
use Modules\Security\Entities\SuperEditorStagedChange;
use Modules\Security\Services\SuperEditorService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Unit\Modules\Security\Concerns\BuildsSuperEditorSchema;

/**
 * Modo Super Editor por HTTP real: rutas + middleware (auth, role:admin,
 * throttle, super.editor) + controlador + servicio + spatie.
 *
 * Aquí no se prueba el servicio, se prueba la aplicación: entrar (basta el rol
 * admin, sin contraseña), dejar un cambio en borrador, salir confirmando la
 * contraseña —que solo se exige cuando hay algo que aplicar— y comprobar que el
 * cambio se aplica a role_has_permissions y que, con ello, cambian las dos capas
 * que el proyecto ya usa: el `auth.permissions` que consume el v-can del
 * frontend y el `permission:` que bloquea las rutas.
 *
 * El esquema se arma con lo mínimo necesario (las migraciones del módulo sí se
 * ejecutan); no se usa RefreshDatabase porque el suite no puede correr todas las
 * migraciones de la aplicación sobre sqlite.
 */
class SuperEditorEndpointsTest extends TestCase
{
    use BuildsSuperEditorSchema;

    private const PASSWORD = 'secreto-seguro';

    protected function setUp(): void
    {
        parent::setUp();

        SuperEditorService::resetTableCache();

        // El registro de actividad es de otra responsabilidad y necesitaría su
        // propia tabla: se desactiva para no mezclar contratos.
        $this->withoutMiddleware(UserActivityLogMiddleware::class);

        $this->dropSuperEditorSchema();
        $this->createSuperEditorSchema();
    }

    protected function tearDown(): void
    {
        $this->dropSuperEditorSchema();

        parent::tearDown();
    }

    // -----------------------------------------------------------------
    // Entrar
    // -----------------------------------------------------------------

    public function test_el_admin_entra_sin_contrasena(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->postJson(route('super_editor_enter'));

        $response->assertOk();
        $response->assertJsonPath('state.active', true);
        $response->assertJsonPath('state.can_use', true);
        $response->assertJsonPath('state.role', 'admin');

        $this->assertNotNull($response->json('state.expires_at'));

        $session = SuperEditorSession::first();
        $this->assertNotNull($session);
        $this->assertSame($admin->id, $session->user_id);
        $this->assertTrue($session->isOpen());
        $this->assertSame(1, SuperEditorAudit::where('action', 'session_opened')->count());
    }

    // Entrar ya no valida contraseña: la contrasena solo protege la aplicacion
    // del borrador, que es lo que se prueba mas abajo.
    public function test_no_se_puede_aplicar_un_borrador_sin_contrasena(): void
    {
        $admin = $this->admin();
        $ventas = $this->role('ventas');
        $this->permission('integrationhub_listado');

        $this->actingAs($admin)
            ->postJson(route('super_editor_enter'))
            ->assertOk();

        $session = ['super_editor_session_id' => SuperEditorSession::first()->id];

        $this->actingAs($admin)->withSession($session)->postJson(route('super_editor_stage'), [
            'permission' => 'integrationhub_listado',
            'role_id' => $ventas->id,
            'allowed' => true,
        ])->assertOk();

        // Sin contrasena (y con la equivocada) el borrador no se aplica.
        $this->actingAs($admin)
            ->withSession($session)
            ->postJson(route('super_editor_exit'), ['mode' => 'apply'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');

        $this->actingAs($admin)
            ->withSession($session)
            ->postJson(route('super_editor_exit'), ['password' => 'otra-cosa', 'mode' => 'apply'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');

        $this->assertSame(0, DB::table('role_has_permissions')->count());
        $this->assertSame(1, SuperEditorStagedChange::count());
        $this->assertTrue(SuperEditorSession::first()->isOpen());
        $this->assertSame(2, SuperEditorAudit::where('action', 'password_failed')->count());
    }

    public function test_un_usuario_sin_el_rol_admin_no_puede_entrar(): void
    {
        $role = $this->role('ventas');
        $user = $this->user('ventas@example.com');
        $user->assignRole($role);

        $response = $this->actingAs($user)->postJson(route('super_editor_enter'));

        $response->assertForbidden();
        $this->assertSame(0, SuperEditorSession::count());
    }

    public function test_los_endpoints_del_editor_exigen_una_sesion_de_edicion_activa(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->getJson(route('super_editor_element', ['permission' => 'integrationhub_listado']))
            ->assertStatus(409)
            ->assertJsonPath('super_editor', 'inactive');
    }

    // -----------------------------------------------------------------
    // Borrador -> aplicar
    // -----------------------------------------------------------------

    public function test_el_ciclo_completo_cambia_los_permisos_del_rol_y_su_efecto_real(): void
    {
        $admin = $this->admin();
        $ventas = $this->role('ventas');
        $this->permission('integrationhub_listado');
        $ventasUser = $this->user('vendedor@example.com');
        $ventasUser->assignRole($ventas);

        // Ruta de prueba con el mismo middleware que usan los módulos.
        Route::middleware(['web', 'permission:integrationhub_listado'])
            ->get('/__probe_integrationhub', fn () => response('ok'));

        $this->assertSame(0, DB::table('role_has_permissions')->count());
        $this->assertNotContains('integrationhub_listado', $this->permissionsOf($ventasUser));
        $this->actingAs($ventasUser)->get('/__probe_integrationhub')->assertForbidden();

        // 1) Entrar.
        $this->actingAs($admin)
            ->postJson(route('super_editor_enter'))
            ->assertOk();

        $sessionId = SuperEditorSession::first()->id;
        $session = ['super_editor_session_id' => $sessionId];

        // 2) El panel del elemento agrupa los permisos hermanos.
        $this->permission('integrationhub_crear');
        $panel = $this->actingAs($admin)
            ->withSession($session)
            ->getJson(route('super_editor_element', ['permission' => 'integrationhub_listado', 'label' => 'Centro de Integraciones', 'kind' => 'opción de menú']))
            ->assertOk();

        $this->assertSame(
            ['integrationhub_listado', 'integrationhub_crear'],
            array_column($panel->json('panel.element.actions'), 'permission')
        );

        // 2b) El panel del botón "NUEVO" ofrece una pestaña por acción: "Ver"
        // apunta al permiso que routes/web.php exige para el listado (`usuarios`)
        // y "Crear" al del propio botón (`usuarios_nuevo`), que es el que se
        // está editando por defecto.
        $this->permission('usuarios');
        $this->permission('usuarios_nuevo');

        $panelUsuarios = $this->actingAs($admin)
            ->withSession($session)
            ->getJson(route('super_editor_element', ['permission' => 'usuarios_nuevo', 'label' => 'NUEVO', 'kind' => 'enlace']))
            ->assertOk();

        $accionesUsuarios = $panelUsuarios->json('panel.element.actions');
        $this->assertSame(['ver', 'crear'], array_column($accionesUsuarios, 'action'));
        $this->assertSame('usuarios', $accionesUsuarios[0]['permission']);
        $this->assertTrue($accionesUsuarios[0]['enforced']);
        $this->assertFalse($accionesUsuarios[0]['own']);
        $this->assertSame('usuarios_nuevo', $accionesUsuarios[1]['permission']);
        $this->assertTrue($accionesUsuarios[1]['own']);

        // 3) Dejar el cambio en borrador: todavía NO se toca ningún permiso.
        $this->actingAs($admin)
            ->withSession($session)
            ->postJson(route('super_editor_stage'), [
                'permission' => 'integrationhub_listado',
                'role_id' => $ventas->id,
                'allowed' => true,
                'label' => 'Centro de Integraciones',
                'kind' => 'opción de menú',
                'url' => '/integrationhub',
            ])
            ->assertOk()
            ->assertJsonPath('state.dirty', 1);

        $this->assertSame(0, DB::table('role_has_permissions')->count());
        $this->assertSame(1, SuperEditorStagedChange::count());
        $this->assertNotContains('integrationhub_listado', $this->permissionsOf($ventasUser->fresh()));

        // 4) Salir confirmando la contraseña: se aplica y queda registrado.
        $this->actingAs($admin)
            ->withSession($session)
            ->postJson(route('super_editor_exit'), ['password' => self::PASSWORD, 'mode' => 'apply'])
            ->assertOk()
            ->assertJsonPath('result.applied', 1)
            ->assertJsonPath('state.active', false);

        $this->assertSame(0, SuperEditorStagedChange::count());
        $this->assertFalse(SuperEditorSession::first()->isOpen());

        // El cambio ya es real en las dos capas del proyecto:
        // (a) lo que ve el frontend (auth.permissions = permisos vía roles)
        $this->assertContains('integrationhub_listado', $this->permissionsOf($ventasUser->fresh()));

        // (b) el bloqueo de las rutas
        $this->actingAs($ventasUser->fresh())->get('/__probe_integrationhub')->assertOk();

        $this->assertSame(1, SuperEditorAudit::where('action', 'applied')->count());
        $this->assertSame(1, SuperEditorAudit::where('action', 'staged')->count());
    }

    public function test_salir_sin_editar_no_cambia_ningun_permiso_ni_pide_contrasena(): void
    {
        $admin = $this->admin();
        $ventas = $this->role('ventas');
        $permission = $this->permission('integrationhub_listado');
        $ventas->givePermissionTo($permission->name);

        $this->actingAs($admin)->postJson(route('super_editor_enter'))->assertOk();

        $session = ['super_editor_session_id' => SuperEditorSession::first()->id];
        $before = DB::table('role_has_permissions')->get();

        // Borrador vacío: sale sin contraseña y sin escribir nada.
        $this->actingAs($admin)
            ->withSession($session)
            ->postJson(route('super_editor_exit'), ['mode' => 'apply'])
            ->assertOk()
            ->assertJsonPath('result.applied', 0)
            ->assertJsonPath('result.discarded', 0);

        $this->assertSame(0, SuperEditorAudit::where('action', 'password_failed')->count());
        $this->assertEquals($before, DB::table('role_has_permissions')->get());
        $this->assertTrue($ventas->fresh()->hasPermissionTo($permission->name));
        $this->assertSame(0, SuperEditorAudit::whereIn('action', ['applied', 'discarded'])->count());
    }

    public function test_salir_con_contrasena_incorrecta_deja_el_borrador_sin_aplicar_y_la_sesion_abierta(): void
    {
        $admin = $this->admin();
        $ventas = $this->role('ventas');
        $this->permission('integrationhub_listado');

        $this->actingAs($admin)->postJson(route('super_editor_enter'))->assertOk();

        $session = ['super_editor_session_id' => SuperEditorSession::first()->id];

        $this->actingAs($admin)->withSession($session)->postJson(route('super_editor_stage'), [
            'permission' => 'integrationhub_listado',
            'role_id' => $ventas->id,
            'allowed' => true,
        ])->assertOk();

        $this->actingAs($admin)
            ->withSession($session)
            ->postJson(route('super_editor_exit'), ['password' => 'incorrecta', 'mode' => 'apply'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');

        $this->assertSame(0, DB::table('role_has_permissions')->count());
        $this->assertSame(1, SuperEditorStagedChange::count());
        $this->assertTrue(SuperEditorSession::first()->isOpen());

        // Y la sesión sigue sirviendo para seguir editando.
        $this->actingAs($admin)
            ->withSession($session)
            ->getJson(route('super_editor_element', ['permission' => 'integrationhub_listado']))
            ->assertOk();
    }

    public function test_salir_descartando_no_aplica_el_borrador(): void
    {
        $admin = $this->admin();
        $ventas = $this->role('ventas');
        $this->permission('integrationhub_listado');

        $this->actingAs($admin)->postJson(route('super_editor_enter'))->assertOk();

        $session = ['super_editor_session_id' => SuperEditorSession::first()->id];

        $this->actingAs($admin)->withSession($session)->postJson(route('super_editor_stage'), [
            'permission' => 'integrationhub_listado',
            'role_id' => $ventas->id,
            'allowed' => true,
        ])->assertOk();

        // Descartar no escribe permisos, asi que no pide contrasena.
        $this->actingAs($admin)
            ->withSession($session)
            ->postJson(route('super_editor_exit'), ['mode' => 'discard'])
            ->assertOk()
            ->assertJsonPath('result.discarded', 1);

        $this->assertSame(0, DB::table('role_has_permissions')->count());
        $this->assertSame(0, SuperEditorStagedChange::count());
        $this->assertSame(1, SuperEditorAudit::where('action', 'discarded')->count());
        $this->assertSame(0, SuperEditorAudit::where('action', 'applied')->count());
    }

    public function test_retirar_del_borrador_los_cambios_de_un_elemento(): void
    {
        $admin = $this->admin();
        $ventas = $this->role('ventas');
        $this->permission('integrationhub_listado');

        $this->actingAs($admin)->postJson(route('super_editor_enter'))->assertOk();

        $session = ['super_editor_session_id' => SuperEditorSession::first()->id];

        $this->actingAs($admin)->withSession($session)->postJson(route('super_editor_stage'), [
            'permission' => 'integrationhub_listado',
            'role_id' => $ventas->id,
            'allowed' => true,
        ])->assertOk();

        $this->actingAs($admin)
            ->withSession($session)
            ->deleteJson(route('super_editor_revert'), ['permission' => 'integrationhub_listado'])
            ->assertOk()
            ->assertJsonPath('state.dirty', 0);

        $this->assertSame(0, SuperEditorStagedChange::count());
        $this->assertSame(1, SuperEditorAudit::where('action', 'reverted')->count());
    }

    public function test_no_se_puede_quitar_un_permiso_protegido_del_rol_admin(): void
    {
        $admin = $this->admin();
        $role = $this->role('admin');
        $permission = $this->permission('configuracion');
        $role->givePermissionTo($permission->name);

        $this->actingAs($admin)->postJson(route('super_editor_enter'))->assertOk();

        $session = ['super_editor_session_id' => SuperEditorSession::first()->id];

        $this->actingAs($admin)->withSession($session)->postJson(route('super_editor_stage'), [
            'permission' => 'configuracion',
            'role_id' => $role->id,
            'allowed' => false,
        ])->assertStatus(422)->assertJsonValidationErrors('permission');

        $this->assertSame(0, SuperEditorStagedChange::count());
        $this->assertTrue($role->fresh()->hasPermissionTo('configuracion'));
    }

    public function test_la_sesion_expirada_cierra_el_modo_y_descarta_el_borrador(): void
    {
        $admin = $this->admin();
        $ventas = $this->role('ventas');
        $this->permission('integrationhub_listado');

        $this->actingAs($admin)->postJson(route('super_editor_enter'))->assertOk();

        $sessionModel = SuperEditorSession::first();
        $session = ['super_editor_session_id' => $sessionModel->id];

        $this->actingAs($admin)->withSession($session)->postJson(route('super_editor_stage'), [
            'permission' => 'integrationhub_listado',
            'role_id' => $ventas->id,
            'allowed' => true,
        ])->assertOk();

        $sessionModel->forceFill(['expires_at' => now()->subMinute()])->save();

        $this->actingAs($admin)
            ->withSession($session)
            ->getJson(route('super_editor_element', ['permission' => 'integrationhub_listado']))
            ->assertStatus(409)
            ->assertJsonPath('super_editor', 'expired');

        $this->assertSame(0, DB::table('role_has_permissions')->count());
        $this->assertSame(0, SuperEditorStagedChange::count());
        $this->assertSame(SuperEditorService::CLOSE_EXPIRED, $sessionModel->fresh()->close_reason);
    }

    public function test_se_puede_crear_desde_el_panel_un_permiso_que_todavia_no_existe(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->postJson(route('super_editor_enter'))->assertOk();

        $session = ['super_editor_session_id' => SuperEditorSession::first()->id];

        // El panel avisa de que el permiso no existe.
        $this->actingAs($admin)
            ->withSession($session)
            ->getJson(route('super_editor_element', ['permission' => 'integrationhub_listado']))
            ->assertOk()
            ->assertJsonPath('panel.element.exists', false);

        $this->actingAs($admin)
            ->withSession($session)
            ->postJson(route('super_editor_permission'), [
                'permission' => 'integrationhub_listado',
                'label' => 'Listado de Integraciones',
                'kind' => 'opción de menú',
            ])
            ->assertOk()
            ->assertJsonPath('panel.element.exists', true);

        $this->assertTrue(Permission::where('name', 'integrationhub_listado')->exists());
        $this->assertTrue(Role::where('name', 'admin')->first()->hasPermissionTo('integrationhub_listado'));
        $this->assertSame(1, SuperEditorAudit::where('permission_name', 'integrationhub_listado')->where('action', 'applied')->count());
    }

    public function test_cerrar_sesion_en_la_aplicacion_cierra_el_modo_y_descarta_el_borrador(): void
    {
        $admin = $this->admin();
        $ventas = $this->role('ventas');
        $this->permission('integrationhub_listado');

        $this->actingAs($admin)->postJson(route('super_editor_enter'))->assertOk();

        $session = ['super_editor_session_id' => SuperEditorSession::first()->id];

        $this->actingAs($admin)->withSession($session)->postJson(route('super_editor_stage'), [
            'permission' => 'integrationhub_listado',
            'role_id' => $ventas->id,
            'allowed' => true,
        ])->assertOk();

        event(new Logout('web', $admin));

        $this->assertSame(SuperEditorService::CLOSE_LOGOUT, SuperEditorSession::first()->close_reason);
        $this->assertSame(0, SuperEditorStagedChange::count());
        $this->assertSame(0, DB::table('role_has_permissions')->count());
    }

    public function test_el_historial_del_modo_es_consultable_solo_por_el_admin(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->postJson(route('super_editor_enter'))->assertOk();

        $this->actingAs($admin)
            ->getJson(route('super_editor_log_data'))
            ->assertOk();

        $role = $this->role('ventas');
        $user = $this->user('vendedor2@example.com');
        $user->assignRole($role);

        $this->actingAs($user)->getJson(route('super_editor_log_data'))->assertForbidden();
    }

    // -----------------------------------------------------------------
    // Utilidades
    // -----------------------------------------------------------------

    private function admin(): User
    {
        $this->role('admin');

        $user = $this->user('admin-test@example.com');
        $user->assignRole('admin');

        return $user;
    }

    private function user(string $email): User
    {
        return User::create([
            'name' => 'Usuario ' . $email,
            'email' => $email,
            'password' => Hash::make(self::PASSWORD),
        ]);
    }

    /**
     * Los permisos efectivos del usuario tal como los comparte la aplicación en
     * `auth.permissions` (los que evalúa el v-can del frontend).
     */
    private function permissionsOf(User $user): array
    {
        return $user->getPermissionsViaRoles()->pluck('name')->all();
    }

}
