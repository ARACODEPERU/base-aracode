<?php

namespace Database\Seeders;

use App\Models\Modulo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Cierra el hueco que dejan las migraciones de los módulos.
 *
 * Contexto: 16 migraciones de módulos conceden permisos a roles (`Role::find(1)`,
 * `Role::where('name', 'Administrador')`) y los enlazan a `modulos`
 * (`Modulo::where('identifier', 'M002')`). En una instalación desde cero las
 * migraciones corren ANTES que los seeders, así que no existe ningún rol ni
 * ninguna fila en `modulos` y esas condiciones quedan en `null`: el permiso se
 * crea, pero sin dueño y sin módulo.
 *
 * Lo que hace este seeder, después de los seeders de módulos:
 *  1. Crea, enlaza a su módulo y concede los permisos que HOY solo existen por
 *     migración (ningún seeder los crea), respetando los roles que la propia
 *     migración quería.
 *  2. Concede a los roles de administración los permisos que se quedaron sin
 *     ningún rol (huérfanos reales). Nunca revoca y nunca toca roles operativos
 *     como 'Ventas': eso lo decide el cliente desde el editor de permisos.
 *
 * Es idempotente: si el permiso ya existe, ya tiene módulo o ya está concedido,
 * no hace nada.
 */
class PermissionsReconcileSeeder extends Seeder
{
    /**
     * Guard de la aplicación.
     */
    public const GUARD = 'web';

    /**
     * Roles administradores: reciben los permisos huérfanos.
     *
     * @var array<int, string>
     */
    public const ADMIN_ROLES = ['admin', 'Administrador'];

    /**
     * Permisos que solo existen por migración.
     *
     * `module` es el identificador de `modulos` que la propia migración usaba y
     * `roles` son los roles que la migración intentaba conceder (se respetan tal
     * cual, incluidos los operativos como 'Alumno' o 'Ventas').
     *
     * @var array<int, array{name: string, module: string, roles: array<int, string>}>
     */
    public const ORPHANS = [
        [
            'name'   => 'aca_testimonios',
            'module' => 'M007',
            'roles'  => ['admin', 'Administrador', 'Alumno'],
        ],
        [
            'name'   => 'aca_estudiante_enviar_correo_acceso',
            'module' => 'M007',
            'roles'  => ['admin', 'Administrador', 'Ventas'],
        ],
        [
            'name'   => 'crm_chat_asistente',
            'module' => 'M008',
            'roles'  => ['admin', 'Administrador'],
        ],
        [
            'name'   => 'invo_documento_anular',
            'module' => 'M002',
            'roles'  => ['admin', 'Administrador'],
        ],
    ];

    /**
     * Permisos base (los crea UserRole) que pertenecen al módulo de
     * Configuración y seguridad. No se conceden aquí —ya los tiene `admin`—
     * solo se enlazan para que el editor de roles los agrupe en su módulo en
     * lugar de dejarlos en "Otros permisos".
     */
    public const BASE_MODULE = 'M019';

    /**
     * @var array<int, string>
     */
    public const BASE_PERMISSIONS = [
        'dashboard',
        'usuarios_nuevo',
        'usuarios_editar',
        'usuarios_eliminar',
        'usuarios_ver',
        'parametros_nuevo',
        'parametros_editar',
        'parametros_eliminar',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $created = 0;
        $linked = 0;
        $granted = 0;

        // 1) Permisos que solo crean las migraciones.
        foreach (self::ORPHANS as $orphan) {
            $permission = Permission::firstOrCreate([
                'name'       => $orphan['name'],
                'guard_name' => self::GUARD,
            ]);

            if ($permission->wasRecentlyCreated) {
                $created++;
            }

            if ($this->linkToModule($permission, $orphan['module'])) {
                $linked++;
            }

            $granted += $this->grant($permission->name, $orphan['roles']);
        }

        // Permisos base: solo se enlazan a su módulo (ya los tiene el rol admin).
        foreach (self::BASE_PERMISSIONS as $name) {
            $permission = Permission::query()
                ->where('name', $name)
                ->where('guard_name', self::GUARD)
                ->first();

            if ($permission && $this->linkToModule($permission, self::BASE_MODULE)) {
                $linked++;
            }
        }

        // La caché de spatie pudo quedar desfasada al crear los permisos anteriores.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 2) Permisos sin ningún rol: se conceden a los roles de administración.
        foreach ($this->permissionsWithoutAnyRole() as $name) {
            $granted += $this->grant($name, self::ADMIN_ROLES);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->info(
            "Reconciliación de permisos: {$created} creados, {$linked} enlazados a su módulo, "
            ."{$granted} concedidos a roles. No se revocó nada."
        );
    }

    /**
     * Nombres de los permisos que no tiene ningún rol (huérfanos reales).
     *
     * @return array<int, string>
     */
    private function permissionsWithoutAnyRole(): array
    {
        return Permission::query()
            ->where('guard_name', self::GUARD)
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('role_has_permissions')
                    ->whereColumn('role_has_permissions.permission_id', 'permissions.id');
            })
            ->pluck('name')
            ->all();
    }

    /**
     * Enlaza el permiso a un módulo para que el editor de roles lo agrupe.
     *
     * @return bool true si insertó el enlace, false si ya existía (o si falta el módulo).
     */
    private function linkToModule(Permission $permission, string $moduleIdentifier): bool
    {
        $moduleExists = Modulo::query()
            ->where('identifier', $moduleIdentifier)
            ->exists();

        if (! $moduleExists) {
            return false;
        }

        $alreadyLinked = DB::table('model_has_permissions')
            ->where('permission_id', $permission->id)
            ->where('model_type', Modulo::class)
            ->where('model_id', $moduleIdentifier)
            ->exists();

        if ($alreadyLinked) {
            return false;
        }

        DB::table('model_has_permissions')->insertOrIgnore([
            'permission_id' => $permission->id,
            'model_type'    => Modulo::class,
            'model_id'      => $moduleIdentifier,
        ]);

        return true;
    }

    /**
     * Concede el permiso a los roles indicados (crea el rol si falta, porque
     * algunos roles operativos solo existían si se sembraba cierto módulo).
     *
     * @param  array<int, string>  $roleNames
     * @return int cantidad de concesiones nuevas
     */
    private function grant(string $permissionName, array $roleNames): int
    {
        $granted = 0;

        foreach ($roleNames as $roleName) {
            $role = Role::firstOrCreate([
                'name'       => $roleName,
                'guard_name' => self::GUARD,
            ]);

            if ($role->hasPermissionTo($permissionName)) {
                continue;
            }

            $role->givePermissionTo($permissionName);
            $granted++;
        }

        return $granted;
    }
}
