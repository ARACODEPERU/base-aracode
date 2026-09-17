<?php

namespace Modules\Commercial\Database\Seeders;

use App\Models\Modulo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Permisos del modulo Comercial (M021).
 *
 * Los permisos se conceden POR NOMBRE DE ROL, igual que las migraciones del modulo
 * (2026_08_13_000000 y 2026_09_15_000000), nunca por id: el id 1 no es una garantia
 * de "rol administrador" y podia entregar 'comm_negociaciones_verificar' al rol
 * equivocado.
 *
 * Politica:
 *  - admin y Administrador: los 15 permisos, incluido 'verificar' (aprobar, rechazar,
 *    reactivar y ejecutar el proceso de 9 pasos).
 *  - Ventas: listado, alta, edicion y borrado (el borrado y el detalle solo aplican a
 *    las negociaciones que el mismo creo, y eso lo valida CommercialNegotiationController).
 *  - 'comm_negociaciones_verificar' NUNCA para Ventas: el seeder lo revoca si lo tuviera,
 *    para que despues de sembrar la politica quede garantizada.
 *
 * El resto de permisos de administracion que Ventas tuviera NO se revocan a ciegas: podrian
 * ser una decision del cliente desde el editor de roles. En su lugar el seeder avisa por
 * consola de los permisos reservados que encuentre en ese rol.
 *
 * El seeder es idempotente: se puede ejecutar cualquier cantidad de veces sin duplicar
 * filas ni lanzar excepciones.
 */
class CommercialDatabaseSeeder extends Seeder
{
    /** Guard de la aplicacion y de las migraciones del modulo. */
    public const GUARD = 'web';

    /** Identificador del modulo en `modulos`. */
    public const MODULE_IDENTIFIER = 'M021';

    /** Roles administradores: reciben los 15 permisos. */
    public const ADMIN_ROLES = ['admin', 'Administrador'];

    /** Rol comercial: recibe solo los permisos de negociaciones (sin 'verificar'). */
    public const SALES_ROLE = 'Ventas';

    /** Los 15 permisos del modulo. */
    public const ADMIN_PERMISSIONS = [
        'comm_dashboard',
        'comm_clientes_listado',
        'comm_clientes_nuevo',
        'comm_clientes_editar',
        'comm_clientes_eliminar',
        'comm_contratos_listado',
        'comm_contratos_nuevo',
        'comm_contratos_editar',
        'comm_contratos_eliminar',
        'comm_contratos_cronograma',
        'comm_negociaciones_listado',
        'comm_negociaciones_nuevo',
        'comm_negociaciones_editar',
        'comm_negociaciones_eliminar',
        'comm_negociaciones_verificar',
    ];

    /** Lo que si puede tener el rol de Ventas. */
    public const SALES_PERMISSIONS = [
        'comm_negociaciones_listado',
        'comm_negociaciones_nuevo',
        'comm_negociaciones_editar',
        'comm_negociaciones_eliminar',
    ];

    /** Solo administradores: aprobar/rechazar/reactivar y ejecutar el proceso completo. */
    public const ADMIN_ONLY_PERMISSIONS = [
        'comm_negociaciones_verificar',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modulo = Modulo::updateOrCreate(
            ['identifier' => self::MODULE_IDENTIFIER],
            ['description' => 'Comercial', 'icon' => 'faBriefcase', 'status' => true]
        );

        $adminRoles = $this->roles(self::ADMIN_ROLES);
        $ventas = $this->roles([self::SALES_ROLE])->first();

        foreach (self::ADMIN_PERMISSIONS as $permissionName) {
            $permission = $this->permission($permissionName, $modulo);

            $adminRoles->each(fn (Role $role) => $role->givePermissionTo($permission));
        }

        foreach (self::SALES_PERMISSIONS as $permissionName) {
            $ventas->givePermissionTo($this->permission($permissionName, $modulo));
        }

        foreach (self::ADMIN_ONLY_PERMISSIONS as $permissionName) {
            $permission = Permission::query()
                ->where('name', $permissionName)
                ->where('guard_name', self::GUARD)
                ->first();

            if ($permission && $ventas->hasPermissionTo($permission)) {
                $ventas->revokePermissionTo($permission);
            }
        }

        $this->warnAboutAdminPermissionsInSalesRole($ventas);
    }

    /**
     * Avisa (sin revocar) si el rol de Ventas arrastra permisos reservados a administradores,
     * por ejemplo de una siembra antigua que concedia por id de rol.
     */
    private function warnAboutAdminPermissionsInSalesRole(Role $ventas): void
    {
        $reservados = $ventas->permissions()
            ->whereIn('name', array_diff(self::ADMIN_PERMISSIONS, self::SALES_PERMISSIONS))
            ->pluck('name');

        if ($reservados->isEmpty()) {
            return;
        }

        $this->command?->warn(
            'Aviso: el rol '.self::SALES_ROLE.' tiene permisos reservados a administradores en el modulo Comercial: '
            .$reservados->implode(', ').'. Revisalos en el editor de roles y quitalos si no son intencionales.'
        );
    }

    /**
     * Resuelve (o crea) los roles indicados. Crear los roles que falten deja una
     * instalacion nueva con la politica completa en lugar de conceder permisos a nadie.
     *
     * @param  array<int, string>  $names
     * @return Collection<int, Role>
     */
    private function roles(array $names): Collection
    {
        return collect($names)->map(
            fn (string $name) => Role::firstOrCreate([
                'name' => $name,
                'guard_name' => self::GUARD,
            ])
        );
    }

    /**
     * Resuelve (o crea) el permiso y lo enlaza al modulo para agruparlo en el editor de roles.
     */
    private function permission(string $name, Modulo $modulo): Permission
    {
        $permission = Permission::firstOrCreate([
            'name' => $name,
            'guard_name' => self::GUARD,
        ]);

        DB::table('model_has_permissions')->updateOrInsert([
            'permission_id' => $permission->id,
            'model_type' => Modulo::class,
            'model_id' => $modulo->identifier,
        ]);

        return $permission;
    }
}
