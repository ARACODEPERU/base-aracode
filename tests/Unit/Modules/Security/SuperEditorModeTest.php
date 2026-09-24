<?php

namespace Tests\Unit\Modules\Security;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\Security\Entities\SuperEditorAudit;
use Modules\Security\Entities\SuperEditorSession;
use Modules\Security\Entities\SuperEditorStagedChange;
use Modules\Security\Http\Middleware\EnsureSuperEditorSession;
use Modules\Security\Services\SuperEditorService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Unit\Modules\Security\Concerns\BuildsSuperEditorSchema;

/**
 * Modo Super Editor: contrato completo del ciclo entrar -> borrador -> salir.
 *
 * En lugar de correr TODAS las migraciones de la aplicación (el suite no puede:
 * hay migraciones con esquema MySQL que sqlite rechaza), esta prueba arma el
 * esquema mínimo que el modo necesita — las tablas de spatie y sus tres tablas,
 * estas últimas ejecutando las migraciones reales del módulo.
 *
 * Lo que se verifica aquí es lo que el modo promete:
 *   - solo el rol autorizado entra, y solo con su contraseña;
 *   - nada toca role_has_permissions hasta que se sale confirmando la contraseña;
 *   - al salir se aplica todo en una transacción y queda auditado;
 *   - salir sin cambios no cambia ningún permiso;
 *   - los permisos protegidos no se le pueden quitar al rol autorizado.
 *
 * Los datos del panel no se prueban aquí: PermissionPanel se construye y se
 * prueba por su cuenta en PermissionPanelTest.
 */
class SuperEditorModeTest extends TestCase
{
    use BuildsSuperEditorSchema;

    private SuperEditorService $service;

    protected function setUp(): void
    {
        parent::setUp();

        SuperEditorService::resetTableCache();

        $this->dropSuperEditorSchema();
        $this->createSuperEditorSchema();

        $this->service = new SuperEditorService();
    }

    protected function tearDown(): void
    {
        $this->dropSuperEditorSchema();

        parent::tearDown();
    }

    // -----------------------------------------------------------------
    // Frontera de rol y contraseña
    // -----------------------------------------------------------------

    public function test_el_rol_no_autorizado_no_puede_entrar(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->open($this->user(admin: false));
    }

    public function test_el_endpoint_del_editor_rechaza_a_quien_no_es_admin(): void
    {
        $request = Request::create('/security/super-editor/element', 'GET');
        $request->setUserResolver(fn () => $this->user(admin: false));

        $middleware = new EnsureSuperEditorSession($this->service);
        $response = $middleware->handle($request, fn () => response('ok'));

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_entrar_no_pide_contrasena(): void
    {
        $user = $this->user();

        $session = $this->service->open($user);

        $this->assertTrue($session->isOpen());
        $this->assertNotNull($session->expires_at);
        $this->assertSame(1, SuperEditorSession::count());
        $this->assertSame(1, SuperEditorAudit::where('action', 'session_opened')->count());
        $this->assertSame(0, SuperEditorAudit::where('action', 'password_failed')->count());
    }

    public function test_aplicar_un_borrador_con_cambios_exige_contrasena(): void
    {
        $this->role('admin');
        $ventas = $this->role('ventas');
        $this->permission('integrationhub_listado');

        $session = $this->service->open($this->user());

        $this->service->stage($session, $this->user(), [
            'permission' => 'integrationhub_listado',
            'role_id' => $ventas->id,
            'allowed' => true,
        ]);

        foreach ([null, '', 'incorrecta'] as $password) {
            try {
                $this->service->close($session, $this->user(), $password, SuperEditorService::CLOSE_APPLIED);
                $this->fail('Se esperaba el rechazo por contraseña.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('password', $exception->errors());
            }
        }

        // Nada se aplicó y el borrador sigue intacto y usable.
        $this->assertSame(0, DB::table('role_has_permissions')->count());
        $this->assertSame(1, SuperEditorStagedChange::count());
        $this->assertTrue($session->fresh()->isOpen());
        $this->assertSame(3, SuperEditorAudit::where('action', 'password_failed')->count());
    }

    public function test_salir_sin_cambios_no_pide_contrasena(): void
    {
        $this->role('admin');

        $session = $this->service->open($this->user());
        $closed = $this->service->close($session, $this->user(), null, SuperEditorService::CLOSE_APPLIED);

        $this->assertSame(0, $closed['applied']);
        $this->assertSame(0, $closed['discarded']);
        $this->assertFalse($session->fresh()->isOpen());
        $this->assertSame(0, SuperEditorAudit::where('action', 'password_failed')->count());
    }

    public function test_sin_sesion_de_edicion_activa_el_endpoint_responde_409(): void
    {
        $request = Request::create('/security/super-editor/element', 'GET');
        $request->setUserResolver(fn () => $this->user());
        $request->setLaravelSession(new Store('test', new ArraySessionHandler(120)));

        $middleware = new EnsureSuperEditorSession($this->service);
        $response = $middleware->handle($request, fn () => response('ok'));

        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame('inactive', $response->getData(true)['super_editor']);
    }

    // -----------------------------------------------------------------
    // Borrador -> aplicar al salir
    // -----------------------------------------------------------------

    public function test_el_borrador_no_toca_los_permisos_hasta_salir(): void
    {
        $this->role('admin');
        $ventas = $this->role('ventas');
        $permission = $this->permission('integrationhub_listado');

        $session = $this->service->open($this->user());

        $result = $this->service->stage($session, $this->user(), [
            'permission' => 'integrationhub_listado',
            'role_id' => $ventas->id,
            'allowed' => true,
            'label' => 'Centro de Integraciones',
            'kind' => 'opción de menú',
            'url' => '/integrationhub',
        ]);

        $this->assertNotNull($result['change']);
        $this->assertSame(1, $result['dirty']);

        // Mientras es borrador, el rol NO tiene el permiso todavía.
        $this->assertFalse($ventas->fresh()->hasPermissionTo($permission->name));
        $this->assertSame(0, DB::table('role_has_permissions')->count());

        $closed = $this->service->close($session, $this->user(), 'secret', SuperEditorService::CLOSE_APPLIED);

        $this->assertSame(1, $closed['applied']);
        $this->assertSame(0, $closed['discarded']);
        $this->assertTrue($ventas->fresh()->hasPermissionTo($permission->name));
        $this->assertSame(1, DB::table('role_has_permissions')->count(), 'Solo se aplicó el cambio marcado.');

        // El borrador queda limpio y la sesión cerrada.
        $this->assertSame(0, SuperEditorStagedChange::count());
        $this->assertFalse($session->fresh()->isOpen());
        $this->assertSame(SuperEditorService::CLOSE_APPLIED, $session->fresh()->close_reason);
        $this->assertSame(1, $session->fresh()->changes_applied);
    }

    public function test_al_salir_se_audita_cada_cambio_con_su_etiqueta(): void
    {
        $this->role('admin');
        $ventas = $this->role('ventas');
        $this->permission('integrationhub_crear');

        $session = $this->service->open($this->user());

        $this->service->stage($session, $this->user(), [
            'permission' => 'integrationhub_crear',
            'role_id' => $ventas->id,
            'allowed' => true,
            'label' => 'Nueva Integración',
            'kind' => 'opción de menú',
            'url' => '/integrationhub/create',
        ]);

        $this->service->close($session, $this->user(), 'secret', SuperEditorService::CLOSE_APPLIED);

        $staged = SuperEditorAudit::where('action', 'staged')->first();
        $this->assertSame('integrationhub_crear', $staged->permission_name);
        $this->assertSame('ventas', $staged->role_name);
        $this->assertSame('Nueva Integración', $staged->element_label);
        $this->assertSame('opción de menú', $staged->element_kind);
        $this->assertSame('/integrationhub/create', $staged->source_url);
        $this->assertFalse($staged->allowed_before);
        $this->assertTrue($staged->allowed_after);

        $applied = SuperEditorAudit::where('action', 'applied')->first();
        $this->assertFalse($applied->allowed_before);
        $this->assertTrue($applied->allowed_after);

        $ids = SuperEditorAudit::orderBy('id')->pluck('action')->all();
        $this->assertSame(['session_opened', 'staged', 'applied', 'session_closed'], $ids);
    }

    public function test_quitar_un_permiso_se_aplica_al_salir(): void
    {
        $this->role('admin');
        $ventas = $this->role('ventas');
        $permission = $this->permission('treasury_cuentas');
        $ventas->givePermissionTo($permission->name);

        $session = $this->service->open($this->user());

        $this->service->stage($session, $this->user(), [
            'permission' => 'treasury_cuentas',
            'role_id' => $ventas->id,
            'allowed' => false,
        ]);

        $this->service->close($session, $this->user(), 'secret', SuperEditorService::CLOSE_APPLIED);

        $this->assertFalse($ventas->fresh()->hasPermissionTo($permission->name));
    }

    public function test_salir_sin_editar_no_cambia_ningun_permiso(): void
    {
        $this->role('admin');
        $ventas = $this->role('ventas');
        $permission = $this->permission('treasury_cuentas');
        $ventas->givePermissionTo($permission->name);

        $before = DB::table('role_has_permissions')->orderBy('permission_id')->get();

        $session = $this->service->open($this->user());

        // Sin cambios en el borrador no hay nada que aplicar ni que confirmar.
        $closed = $this->service->close($session, $this->user(), null, SuperEditorService::CLOSE_APPLIED);

        $this->assertSame(0, $closed['applied']);
        $this->assertSame(0, $closed['discarded']);
        $this->assertTrue($ventas->fresh()->hasPermissionTo($permission->name));
        $this->assertEquals($before, DB::table('role_has_permissions')->orderBy('permission_id')->get());
        $this->assertSame(0, SuperEditorAudit::where('action', 'password_failed')->count());
    }

    public function test_salir_con_contrasena_incorrecta_no_aplica_nada(): void
    {
        $this->role('admin');
        $ventas = $this->role('ventas');
        $permission = $this->permission('integrationhub_listado');

        $session = $this->service->open($this->user());

        $this->service->stage($session, $this->user(), [
            'permission' => 'integrationhub_listado',
            'role_id' => $ventas->id,
            'allowed' => true,
        ]);

        try {
            $this->service->close($session, $this->user(), 'incorrecta', SuperEditorService::CLOSE_APPLIED);
            $this->fail('Se esperaba un error de validación por contraseña incorrecta.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('password', $exception->errors());
        }

        $this->assertFalse($ventas->fresh()->hasPermissionTo($permission->name));
        $this->assertSame(1, SuperEditorStagedChange::count(), 'El borrador sigue intacto.');
        $this->assertTrue($session->fresh()->isOpen(), 'La sesión sigue abierta.');
    }

    public function test_salir_descartando_no_aplica_nada(): void
    {
        $this->role('admin');
        $ventas = $this->role('ventas');
        $permission = $this->permission('integrationhub_listado');

        $session = $this->service->open($this->user());

        $this->service->stage($session, $this->user(), [
            'permission' => 'integrationhub_listado',
            'role_id' => $ventas->id,
            'allowed' => true,
        ]);

        // Descartar no escribe permisos: no hace falta confirmar nada.
        $closed = $this->service->close($session, $this->user(), null, SuperEditorService::CLOSE_DISCARDED);

        $this->assertSame(1, $closed['discarded']);
        $this->assertFalse($ventas->fresh()->hasPermissionTo($permission->name));
        $this->assertSame(0, SuperEditorStagedChange::count());
        $this->assertSame(SuperEditorService::CLOSE_DISCARDED, $session->fresh()->close_reason);
        $this->assertSame(1, SuperEditorAudit::where('action', 'discarded')->count());
    }

    public function test_volver_al_estado_original_deja_de_ser_un_cambio(): void
    {
        $this->role('admin');
        $ventas = $this->role('ventas');
        $permission = $this->permission('treasury_cuentas');
        $ventas->givePermissionTo($permission->name);

        $session = $this->service->open($this->user());
        $user = $this->user();

        $first = $this->service->stage($session, $user, [
            'permission' => 'treasury_cuentas',
            'role_id' => $ventas->id,
            'allowed' => false,
        ]);

        $this->assertSame(1, $first['dirty']);

        $second = $this->service->stage($session, $user, [
            'permission' => 'treasury_cuentas',
            'role_id' => $ventas->id,
            'allowed' => true,
        ]);

        $this->assertNull($second['change']);
        $this->assertSame(0, $second['dirty']);
        $this->assertSame(0, SuperEditorStagedChange::count());
        $this->assertSame(1, SuperEditorAudit::where('action', 'reverted')->count());
    }

    public function test_la_sesion_expirada_descarta_el_borrador(): void
    {
        $this->role('admin');
        $ventas = $this->role('ventas');
        $permission = $this->permission('integrationhub_listado');

        $session = $this->service->open($this->user());

        $this->service->stage($session, $this->user(), [
            'permission' => 'integrationhub_listado',
            'role_id' => $ventas->id,
            'allowed' => true,
        ]);

        $session->forceFill(['expires_at' => now()->subMinute()])->save();
        $session->refresh();

        $this->assertTrue($this->service->expireIfNeeded($session));

        $this->assertFalse($ventas->fresh()->hasPermissionTo($permission->name));
        $this->assertSame(SuperEditorService::CLOSE_EXPIRED, $session->fresh()->close_reason);
        $this->assertSame(0, SuperEditorStagedChange::count());
    }

    public function test_abrir_una_sesion_nueva_descarta_la_anterior(): void
    {
        $this->role('admin');
        $ventas = $this->role('ventas');
        $permission = $this->permission('integrationhub_listado');
        $user = $this->user();

        $first = $this->service->open($user);

        $this->service->stage($first, $user, [
            'permission' => 'integrationhub_listado',
            'role_id' => $ventas->id,
            'allowed' => true,
        ]);

        $second = $this->service->open($user);

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame(SuperEditorService::CLOSE_SUPERSEDED, $first->fresh()->close_reason);
        $this->assertFalse($ventas->fresh()->hasPermissionTo($permission->name));
        $this->assertSame(0, SuperEditorStagedChange::count());
    }

    // -----------------------------------------------------------------
    // Permisos protegidos
    // -----------------------------------------------------------------

    public function test_no_se_puede_quitar_del_rol_admin_un_permiso_protegido(): void
    {
        $admin = $this->role('admin');
        $this->permission('permisos');
        $admin->givePermissionTo('permisos');

        $session = $this->service->open($this->user());

        try {
            $this->service->stage($session, $this->user(), [
                'permission' => 'permisos',
                'role_id' => $admin->id,
                'allowed' => false,
            ]);
            $this->fail('Se esperaba el rechazo del borrador por permiso protegido.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('permission', $exception->errors());
        }

        $this->assertSame(0, SuperEditorStagedChange::count());
        $this->assertTrue($admin->fresh()->hasPermissionTo('permisos'));
    }

    public function test_el_permiso_protegido_tambien_se_bloquea_al_aplicar(): void
    {
        $admin = $this->role('admin');
        $permission = $this->permission('configuracion');
        $admin->givePermissionTo('configuracion');

        $session = $this->service->open($this->user());

        // Simula un borrador que llegó por fuera de la validación de stage().
        SuperEditorStagedChange::create([
            'session_id' => $session->id,
            'permission_name' => $permission->name,
            'role_id' => $admin->id,
            'role_name' => $admin->name,
            'allowed' => false,
            'allowed_before' => true,
        ]);

        $closed = $this->service->close($session, $this->user(), 'secret', SuperEditorService::CLOSE_APPLIED);

        $this->assertSame(0, $closed['applied']);
        $this->assertSame(1, $closed['blocked']);
        $this->assertTrue($admin->fresh()->hasPermissionTo('configuracion'));
        $this->assertSame(1, SuperEditorAudit::where('action', 'blocked')->count());
    }

    // -----------------------------------------------------------------
    // Estado compartido con el frontend
    // -----------------------------------------------------------------

    public function test_el_estado_compartido_no_activa_el_modo_sin_sesion(): void
    {
        $this->role('admin');

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(fn () => $this->user());
        $request->setLaravelSession(new Store('test', new ArraySessionHandler(120)));

        $state = $this->service->shareData($request);

        $this->assertTrue($state['can_use']);
        $this->assertFalse($state['active']);
        $this->assertSame(0, $state['dirty']);
        $this->assertNull($state['expires_at']);
        $this->assertSame('admin', $state['role']);
        $this->assertSame([], $state['staged_permissions'], 'sin sesión no hay borrador que anunciar');
    }

    public function test_el_estado_compartido_dice_que_permisos_tienen_borrador(): void
    {
        $this->role('admin');
        $ventas = $this->role('ventas');
        $this->permission('integrationhub_listado');

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(fn () => $this->user());
        $request->setLaravelSession(new Store('test', new ArraySessionHandler(120)));

        $session = $this->service->open($this->user(), null, null, $request->session());

        $this->assertSame([], $this->service->shareData($request)['staged_permissions'], 'recién abierto');

        $this->service->stage($session, $this->user(), [
            'permission' => 'integrationhub_listado',
            'role_id' => $ventas->id,
            'allowed' => true,
        ]);

        $state = $this->service->shareData($request);

        $this->assertSame(1, $state['dirty']);
        $this->assertSame(['integrationhub_listado'], $state['staged_permissions']);

        // Descartar el borrador apaga el aviso: el toggle vuelve a su color normal.
        $this->service->close($session, $this->user(), null, SuperEditorService::CLOSE_DISCARDED);

        $this->assertSame([], $this->service->shareData($request)['staged_permissions']);
    }

    public function test_un_usuario_que_no_es_admin_no_puede_usar_el_modo(): void
    {
        $this->role('admin');

        $this->assertTrue($this->service->canUse($this->user()));
        $this->assertFalse($this->service->canUse($this->user(admin: false)));
        $this->assertFalse($this->service->canUse(null));
    }

    // -----------------------------------------------------------------
    // Utilidades
    // -----------------------------------------------------------------

    /**
     * Usuario mínimo: el servicio depende del contrato Authenticatable, no del
     * modelo User de la aplicación (así esta prueba no necesita la tabla users).
     */
    private function user(string $password = 'secret', bool $admin = true, int $id = 1): Authenticatable
    {
        return new class($id, Hash::make($password), $admin) implements Authenticatable
        {
            // `id` es público porque SuperEditorService lo lee como en un modelo
            // Eloquent ($request->user()->id) al resolver la sesión activa.
            public function __construct(public int $id, private string $password, private bool $admin)
            {
            }

            public function getAuthIdentifierName(): string
            {
                return 'id';
            }

            public function getAuthIdentifier(): mixed
            {
                return $this->id;
            }

            public function getAuthPasswordName(): string
            {
                return 'password';
            }

            public function getAuthPassword(): string
            {
                return $this->password;
            }

            public function getRememberToken(): ?string
            {
                return null;
            }

            public function setRememberToken($value): void
            {
            }

            public function getRememberTokenName(): ?string
            {
                return null;
            }

            public function hasRole($roles): bool
            {
                return $this->admin;
            }
        };
    }
}
