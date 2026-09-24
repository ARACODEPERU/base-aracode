<?php

namespace Modules\Security\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Security\Entities\SuperEditorAudit;
use Modules\Security\Entities\SuperEditorSession;
use Modules\Security\Entities\SuperEditorStagedChange;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Modo Super Editor.
 *
 * Qué hace: permite a un admin editar, elemento por elemento de la interfaz,
 * qué roles pueden verlo / crearlo / editarlo / eliminarlo / ejecutarlo.
 *
 * Cómo lo hace: NO crea una ACL paralela. El objeto editado son los permisos
 * de spatie que ya usa el proyecto en sus dos capas (el `v-can` del frontend y
 * el `permission:` de las rutas), así que lo que aquí se cambia afecta a la vez
 * a la visibilidad de botones/menús y al bloqueo real del backend.
 *
 * Ciclo de vida: entrar (basta el rol autorizado) -> dejar cambios en borrador ->
 * salir. La contraseña se pide UNA sola vez: al salir aplicando un borrador con
 * cambios, que es el único momento en que se escribe sobre role_has_permissions.
 * Salir con el borrador vacío no aplica nada y no pide nada. El borrador se
 * aplica en una sola transacción y cada paso queda en la bitácora
 * (super_editor_audits).
 */
class SuperEditorService
{
    /** Clave con la que se guarda la sesión de edición abierta en la sesión de PHP. */
    public const SESSION_KEY = 'super_editor_session_id';

    public const CLOSE_APPLIED = 'applied';
    public const CLOSE_DISCARDED = 'discarded';
    public const CLOSE_EXPIRED = 'expired';
    public const CLOSE_LOGOUT = 'logout';
    public const CLOSE_SUPERSEDED = 'superseded';

    public const AUDIT_OPENED = 'session_opened';
    public const AUDIT_CLOSED = 'session_closed';
    public const AUDIT_STAGED = 'staged';
    public const AUDIT_REVERTED = 'reverted';
    public const AUDIT_APPLIED = 'applied';
    public const AUDIT_DISCARDED = 'discarded';
    public const AUDIT_BLOCKED = 'blocked';
    public const AUDIT_PASSWORD_FAILED = 'password_failed';

    private static ?bool $tablesReady = null;

    // ---------------------------------------------------------------------
    // Configuración y acceso
    // ---------------------------------------------------------------------

    public function enabled(): bool
    {
        return (bool) config('security.super_editor.enabled', true);
    }

    public function roleName(): string
    {
        return (string) config('security.super_editor.role', 'admin');
    }

    public function ttlMinutes(): int
    {
        return max(1, (int) config('security.super_editor.ttl_minutes', 30));
    }

    /**
     * Permisos que el rol admin nunca puede perder desde el editor: son los que
     * sostienen la propia configuración y el acceso al modo editor (evita que
     * un admin se deje fuera a sí mismo).
     */
    public function protectedPermissions(): array
    {
        $configured = (array) config('security.super_editor.protected_permissions', []);

        return array_values(array_unique(array_filter(array_map(
            fn ($name) => Str::lower(trim((string) $name)),
            $configured
        ))));
    }

    public function isProtected(string $permissionName): bool
    {
        $name = Str::lower(trim($permissionName));

        if (Str::startsWith($name, 'super_editor')) {
            return true;
        }

        return in_array($name, $this->protectedPermissions(), true);
    }

    /**
     * Las tablas pueden no existir todavía (p. ej. si aún no se corrió la
     * migración). El modo simplemente queda inactivo en vez de romper la app.
     */
    public function tablesReady(): bool
    {
        if (self::$tablesReady === null) {
            try {
                self::$tablesReady = Schema::hasTable('super_editor_sessions')
                    && Schema::hasTable('super_editor_staged_changes')
                    && Schema::hasTable('super_editor_audits');
            } catch (\Throwable $e) {
                self::$tablesReady = false;
            }
        }

        return self::$tablesReady;
    }

    /**
     * Solo para pruebas: vuelve a comprobar la existencia de las tablas (el
     * resultado se cachea por proceso).
     */
    public static function resetTableCache(): void
    {
        self::$tablesReady = null;
    }

    /**
     * Solo el rol configurado (admin) puede usar el modo.
     */
    public function canUse(?Authenticatable $user): bool
    {
        if (! $this->enabled() || ! $user || ! $this->tablesReady()) {
            return false;
        }

        if (! method_exists($user, 'hasRole')) {
            return false;
        }

        try {
            return (bool) $user->hasRole($this->roleName());
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function assertCanUse(?Authenticatable $user): void
    {
        if (! $this->canUse($user)) {
            throw ValidationException::withMessages([
                'super_editor' => 'El Modo Super Editor solo está disponible para el rol ' . $this->roleName() . '.',
            ]);
        }
    }

    // ---------------------------------------------------------------------
    // Estado compartido con el frontend
    // ---------------------------------------------------------------------

    /**
     * Props que se comparten en cada respuesta Inertia. El frontend no guarda
     * el estado del modo: lo recibe del servidor, de forma que sobrevive a los
     * reloads (que son justamente los que vuelven a montar las directivas).
     */
    public function shareData(Request $request): array
    {
        $user = $request->user();
        $canUse = $this->canUse($user);

        $payload = [
            'can_use' => $canUse,
            'active' => false,
            'expires_at' => null,
            'ttl_minutes' => $this->ttlMinutes(),
            'dirty' => 0,
            'staged_permissions' => [],
            'protected_permissions' => $this->protectedPermissions(),
            'role' => $this->roleName(),
        ];

        if (! $canUse) {
            return $payload;
        }

        $session = $this->activeSession($request);

        if ($session && $this->expireIfNeeded($session)) {
            $session = null;
        }

        if (! $session) {
            return $payload;
        }

        return array_merge($payload, [
            'active' => true,
            'expires_at' => $session->expires_at?->toIso8601String(),
            'dirty' => $this->dirtyCount($session),
            'staged_permissions' => $this->stagedPermissions($session),
        ]);
    }

    public function activeSession(Request $request): ?SuperEditorSession
    {
        if (! $this->tablesReady()) {
            return null;
        }

        $id = $request->session()->get(self::SESSION_KEY);

        if (! $id) {
            return null;
        }

        $session = SuperEditorSession::find($id);

        if (! $session || ! $session->isOpen()) {
            return null;
        }

        if ((int) $session->user_id !== (int) $request->user()?->id) {
            return null;
        }

        return $session;
    }

    public function isExpired(SuperEditorSession $session): bool
    {
        return $session->isExpired();
    }

    /**
     * Cierra la sesión si pasó su TTL, descartando el borrador (queda auditado).
     */
    public function expireIfNeeded(SuperEditorSession $session): bool
    {
        if (! $session->isExpired()) {
            return false;
        }

        $this->closeRaw($session, self::CLOSE_EXPIRED, null, null);

        return true;
    }

    // ---------------------------------------------------------------------
    // Entrar / salir
    // ---------------------------------------------------------------------

    /**
     * Entrar al modo no pide contraseña: la única puerta es el rol autorizado
     * (la ruta ya exige `role:admin`). La contraseña se reserva para aplicar un
     * borrador, que es lo que de verdad cambia permisos.
     */
    public function open(Authenticatable $user, ?string $ip = null, ?string $userAgent = null, ?Session $phpSession = null): SuperEditorSession
    {
        $this->assertCanUse($user);

        // Cualquier sesión de edición previa del mismo usuario se cierra
        // descartando su borrador: nunca se aplican cambios sin confirmación.
        $this->closeOpenForUser($user, self::CLOSE_SUPERSEDED);

        $session = SuperEditorSession::create([
            'user_id' => $user->getAuthIdentifier(),
            'ip' => $ip,
            'user_agent' => $userAgent ? Str::limit($userAgent, 250, '') : null,
            'started_at' => now(),
            'expires_at' => now()->addMinutes($this->ttlMinutes()),
        ]);

        $phpSession?->put(self::SESSION_KEY, $session->id);

        $this->audit($session, $user, [
            'action' => self::AUDIT_OPENED,
            'ip' => $ip,
            'notes' => 'Entró al Modo Super Editor.',
        ]);

        return $session;
    }

    /**
     * Comprueba la contraseña del propio usuario y deja rastro de los intentos
     * fallidos. Solo se usa para aplicar un borrador con cambios (entrar no la
     * pide). No usa el `password.confirm` de Breeze porque ese flujo redirige a
     * HOME y no sirve para peticiones JSON.
     */
    public function verifyPassword(Authenticatable $user, ?string $password, ?string $ip = null, ?SuperEditorSession $session = null): bool
    {
        $password = (string) $password;

        if ($password !== '' && Hash::check($password, $user->getAuthPassword())) {
            return true;
        }

        $this->audit($session, $user, [
            'action' => self::AUDIT_PASSWORD_FAILED,
            'ip' => $ip,
            'notes' => 'Intento fallido de confirmar la contraseña del Modo Super Editor.',
        ]);

        return false;
    }

    /**
     * Salir del modo. Este es el único punto que escribe sobre
     * role_has_permissions, así que exige la contraseña SOLO cuando hay algo que
     * aplicar: con el borrador vacío (o al descartar) no hay escritura y por
     * tanto no hay nada que confirmar.
     *
     * @return array{applied:int, discarded:int, blocked:int, reason:string}
     */
    public function close(
        SuperEditorSession $session,
        Authenticatable $user,
        ?string $password,
        string $mode = self::CLOSE_APPLIED,
        ?string $ip = null,
        ?Session $phpSession = null
    ): array {
        $reason = $mode === self::CLOSE_DISCARDED ? self::CLOSE_DISCARDED : self::CLOSE_APPLIED;

        if ($this->needsPassword($session, $reason) && ! $this->verifyPassword($user, $password, $ip, $session)) {
            throw ValidationException::withMessages([
                'password' => 'La contraseña no es correcta: los cambios siguen sin aplicarse.',
            ]);
        }

        $result = $this->closeRaw($session, $reason, $user, $ip);

        $phpSession?->forget(self::SESSION_KEY);

        return $result;
    }

    /**
     * ¿Este cierre va a escribir permisos? Solo entonces hace falta la
     * contraseña: aplicar un borrador con cambios.
     */
    public function needsPassword(SuperEditorSession $session, string $reason): bool
    {
        return $reason === self::CLOSE_APPLIED && $this->dirtyCount($session) > 0;
    }

    /**
     * Cierra una sesión de edición y, si corresponde, aplica el borrador en una
     * sola transacción. Público porque también lo usan la expiración, el logout
     * y el reemplazo de sesiones.
     *
     * @return array{applied:int, discarded:int, blocked:int, reason:string}
     */
    public function closeRaw(SuperEditorSession $session, string $reason, ?Authenticatable $user = null, ?string $ip = null): array
    {
        if (! $session->isOpen()) {
            return [
                'applied' => (int) $session->changes_applied,
                'discarded' => (int) $session->changes_discarded,
                'blocked' => 0,
                'reason' => (string) $session->close_reason,
            ];
        }

        $apply = $reason === self::CLOSE_APPLIED;
        $changes = SuperEditorStagedChange::where('session_id', $session->id)->get();

        $applied = 0;
        $discarded = 0;
        $blocked = 0;

        DB::transaction(function () use ($session, $changes, $apply, $reason, $user, $ip, &$applied, &$discarded, &$blocked) {
            foreach ($changes as $change) {
                $role = Role::where('name', $change->role_name)->first() ?? Role::find($change->role_id);
                $permission = Permission::where('name', $change->permission_name)->first();

                $before = null;

                if ($role && $permission) {
                    $before = $this->roleHasPermission($role, $permission->name);
                }

                // Un permiso protegido jamás se le quita al rol admin, aunque el
                // borrador lo pidiera (defensa en profundidad, no solo validación).
                if ($apply && $change->allowed === false && $this->isProtected($change->permission_name) && $role?->name === $this->roleName()) {
                    $blocked++;
                    $this->audit($session, $user, [
                        'action' => self::AUDIT_BLOCKED,
                        'permission_name' => $change->permission_name,
                        'role_name' => $change->role_name,
                        'element_label' => $change->element_label,
                        'element_kind' => $change->element_kind,
                        'source_url' => $change->source_url,
                        'allowed_before' => $before,
                        'allowed_after' => $before,
                        'ip' => $ip,
                        'notes' => 'Permiso protegido: no se quitó al rol ' . $this->roleName() . '.',
                    ]);

                    continue;
                }

                if ($apply && $role && $permission) {
                    if ($change->allowed) {
                        $role->givePermissionTo($permission->name);
                    } else {
                        $role->revokePermissionTo($permission->name);
                    }

                    $applied++;
                } else {
                    $discarded++;
                }

                $this->audit($session, $user, [
                    'action' => $apply ? self::AUDIT_APPLIED : self::AUDIT_DISCARDED,
                    'permission_name' => $change->permission_name,
                    'role_name' => $change->role_name,
                    'element_label' => $change->element_label,
                    'element_kind' => $change->element_kind,
                    'source_url' => $change->source_url,
                    'allowed_before' => $before,
                    'allowed_after' => $apply ? (bool) $change->allowed : $before,
                    'ip' => $ip,
                    'notes' => $apply
                        ? 'Cambio aplicado al salir del Modo Super Editor.'
                        : 'Cambio descartado sin aplicar.',
                ]);
            }

            // El borrador nunca sobrevive al cierre, se haya aplicado o no.
            SuperEditorStagedChange::where('session_id', $session->id)->delete();

            $session->forceFill([
                'ended_at' => now(),
                'close_reason' => $reason,
                'changes_applied' => $applied,
                'changes_discarded' => $discarded,
            ])->save();

            $this->audit($session, $user, [
                'action' => self::AUDIT_CLOSED,
                'ip' => $ip,
                'notes' => sprintf(
                    'Sesión cerrada (%s): %d aplicados, %d descartados, %d bloqueados.',
                    $reason,
                    $applied,
                    $discarded,
                    $blocked
                ),
            ]);
        });

        $this->forgetPermissionCache();

        return [
            'applied' => $applied,
            'discarded' => $discarded,
            'blocked' => $blocked,
            'reason' => $reason,
        ];
    }

    /**
     * Cierra la sesión de edición abierta del usuario (si hay). Se usa al abrir
     * una nueva y al cerrar la sesión de la aplicación.
     */
    public function closeOpenForUser(Authenticatable $user, string $reason): void
    {
        if (! $this->tablesReady()) {
            return;
        }

        $sessions = SuperEditorSession::where('user_id', $user->getAuthIdentifier())
            ->whereNull('ended_at')
            ->get();

        foreach ($sessions as $session) {
            $this->closeRaw($session, $reason, $user, null);
        }
    }

    // ---------------------------------------------------------------------
    // Borrador
    // ---------------------------------------------------------------------

    /**
     * Deja (o quita) un cambio en el borrador. Si el valor pedido coincide con
     * el estado real del rol, el cambio deja de existir como borrador: así el
     * contador de "cambios sin aplicar" es siempre honesto.
     */
    public function stage(SuperEditorSession $session, Authenticatable $user, array $payload): array
    {
        $permissionName = trim((string) ($payload['permission'] ?? ''));
        $roleId = (int) ($payload['role_id'] ?? 0);
        $allowed = (bool) ($payload['allowed'] ?? false);

        $permission = Permission::where('name', $permissionName)->first();
        $role = Role::find($roleId);

        if (! $permission || ! $role) {
            throw ValidationException::withMessages([
                'permission' => 'El permiso o el rol indicado no existen.',
            ]);
        }

        if (! $allowed && $this->isProtected($permissionName) && $role->name === $this->roleName()) {
            throw ValidationException::withMessages([
                'permission' => sprintf(
                    '"%s" es un permiso protegido: el rol %s no puede perderlo.',
                    $permissionName,
                    $role->name
                ),
            ]);
        }

        $meta = [
            'element_label' => Str::limit(strip_tags((string) ($payload['label'] ?? '')), 150, ''),
            'element_kind' => Str::limit((string) ($payload['kind'] ?? ''), 50, ''),
            'source_url' => Str::limit((string) ($payload['url'] ?? ''), 490, ''),
        ];

        $existing = SuperEditorStagedChange::where('session_id', $session->id)
            ->where('permission_name', $permissionName)
            ->where('role_id', $role->id)
            ->first();

        $before = $this->roleHasPermission($role, $permissionName);

        if ($allowed === $before) {
            if ($existing) {
                $existing->delete();

                $this->audit($session, $user, array_merge($meta, [
                    'action' => self::AUDIT_REVERTED,
                    'permission_name' => $permissionName,
                    'role_name' => $role->name,
                    'allowed_before' => $before,
                    'allowed_after' => $before,
                    'notes' => 'El borrador volvió al estado actual: deja de ser un cambio.',
                ]));
            }

            return ['change' => null, 'dirty' => $this->dirtyCount($session)];
        }

        $change = SuperEditorStagedChange::updateOrCreate(
            [
                'session_id' => $session->id,
                'permission_name' => $permissionName,
                'role_id' => $role->id,
            ],
            array_merge($meta, [
                'role_name' => $role->name,
                'allowed' => $allowed,
                'allowed_before' => $before,
            ])
        );

        $this->audit($session, $user, array_merge($meta, [
            'action' => self::AUDIT_STAGED,
            'permission_name' => $permissionName,
            'role_name' => $role->name,
            'allowed_before' => $before,
            'allowed_after' => $allowed,
            'notes' => $allowed
                ? 'Se concederá el permiso al aplicar el borrador.'
                : 'Se quitará el permiso al aplicar el borrador.',
        ]));

        return ['change' => $change, 'dirty' => $this->dirtyCount($session)];
    }

    /**
     * Crea un permiso que todavía no existe (un elemento nuevo de la interfaz
     * puede apuntar a un permiso que nadie ha creado aún). Se concede al rol
     * autorizado para que el elemento siga visible mientras se configura el
     * resto de roles en el borrador.
     */
    public function createPermission(SuperEditorSession $session, Authenticatable $user, string $permissionName, array $meta = []): Permission
    {
        $name = trim($permissionName);

        if ($name === '' || ! preg_match('/^[A-Za-z0-9_.:-]{2,255}$/', $name)) {
            throw ValidationException::withMessages([
                'permission' => 'El nombre del permiso es inválido.',
            ]);
        }

        $permission = Permission::firstOrCreate(
            ['name' => $name],
            ['guard_name' => 'web']
        );

        $adminRole = Role::where('name', $this->roleName())->first();

        if ($adminRole && ! $this->roleHasPermission($adminRole, $name)) {
            $adminRole->givePermissionTo($name);
            $this->forgetPermissionCache();
        }

        $this->audit($session, $user, [
            'action' => self::AUDIT_APPLIED,
            'permission_name' => $name,
            'role_name' => $adminRole?->name,
            'element_label' => $meta['label'] ?? null,
            'element_kind' => $meta['kind'] ?? null,
            'source_url' => $meta['url'] ?? null,
            'allowed_before' => false,
            'allowed_after' => true,
            'notes' => 'Permiso creado desde el Modo Super Editor y concedido al rol autorizado.',
        ]);

        return $permission;
    }

    /**
     * Descarta del borrador todos los cambios de un elemento (o de un rol
     * concreto dentro del elemento).
     */
    public function revert(SuperEditorSession $session, Authenticatable $user, string $permissionName, ?int $roleId = null): int
    {
        $query = SuperEditorStagedChange::where('session_id', $session->id)
            ->where('permission_name', $permissionName);

        if ($roleId) {
            $query->where('role_id', $roleId);
        }

        $changes = $query->get();

        foreach ($changes as $change) {
            $this->audit($session, $user, [
                'action' => self::AUDIT_REVERTED,
                'permission_name' => $change->permission_name,
                'role_name' => $change->role_name,
                'element_label' => $change->element_label,
                'element_kind' => $change->element_kind,
                'source_url' => $change->source_url,
                'allowed_before' => $change->allowed_before,
                'allowed_after' => $change->allowed_before,
                'notes' => 'Cambio retirado del borrador.',
            ]);
        }

        $deleted = $query->delete();

        return $deleted;
    }

    public function dirtyCount(SuperEditorSession $session): int
    {
        return SuperEditorStagedChange::where('session_id', $session->id)->count();
    }

    /**
     * Permisos que hoy tienen cambios en borrador. El frontend pinta con esto el
     * toggle del elemento en ámbar, así que basta con los nombres: un cambio que
     * vuelve al estado real deja de existir como borrador (ver stage()).
     */
    public function stagedPermissions(SuperEditorSession $session): array
    {
        return SuperEditorStagedChange::where('session_id', $session->id)
            ->orderBy('permission_name')
            ->pluck('permission_name')
            ->unique()
            ->values()
            ->all();
    }

    // ---------------------------------------------------------------------
    // Bitácora
    // ---------------------------------------------------------------------

    public function auditsQuery(array $filters = [])
    {
        return SuperEditorAudit::query()
            ->with('user')
            ->when(! empty($filters['search']), function ($query) use ($filters) {
                $search = '%' . $filters['search'] . '%';
                $query->where(function ($inner) use ($search) {
                    $inner->where('permission_name', 'like', $search)
                        ->orWhere('role_name', 'like', $search)
                        ->orWhere('element_label', 'like', $search)
                        ->orWhere('notes', 'like', $search);
                });
            })
            ->when(! empty($filters['action']), fn ($query) => $query->where('action', $filters['action']))
            ->orderByDesc('id');
    }

    public function audit(SuperEditorSession|int|null $session, ?Authenticatable $user, array $data): ?SuperEditorAudit
    {
        if (! $this->tablesReady()) {
            return null;
        }

        try {
            return SuperEditorAudit::create([
                'session_id' => $session instanceof SuperEditorSession ? $session->id : ($session ?: null),
                'user_id' => $user?->getAuthIdentifier(),
                'permission_name' => $data['permission_name'] ?? null,
                'role_name' => $data['role_name'] ?? null,
                'element_label' => $data['element_label'] ?? null,
                'element_kind' => $data['element_kind'] ?? null,
                'source_url' => $data['source_url'] ?? null,
                'action' => $data['action'],
                'allowed_before' => $data['allowed_before'] ?? null,
                'allowed_after' => $data['allowed_after'] ?? null,
                'ip' => $data['ip'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // La bitácora nunca debe tumbar una acción del usuario.
            return null;
        }
    }

    // ---------------------------------------------------------------------
    // Utilidades internas
    // ---------------------------------------------------------------------

    public function roleHasPermission(Role $role, string $permissionName): bool
    {
        try {
            return (bool) $role->hasPermissionTo($permissionName);
        } catch (PermissionDoesNotExist $e) {
            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function forgetPermissionCache(): void
    {
        try {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
            // Sin registrar: no es crítico.
        }
    }
}
