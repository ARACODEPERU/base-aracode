<?php

use App\Models\Modulo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Permiso propio para editar los datos de un apoderado desde la ficha del
 * alumno (`/academic/school/students/edit/{id}`).
 *
 * Antes esa edicion obligaba a tener `aca_school_alumno_editar` (el permiso que
 * edita al alumno y sus demas acciones del colegio). Con este permiso separado
 * el colegio puede dar a secretaria el mantenimiento de apoderados sin darle
 * permiso para tocar la ficha del alumno.
 *
 * Nota: en una instalacion desde cero las migraciones corren antes que los
 * seeders, asi que no existen ni los roles ni las filas de `modulos`. Por eso el
 * permiso tambien queda declarado en PermissionsReconcileSeeder::ORPHANS, que es
 * quien lo enlaza y lo concede cuando la base se siembra.
 */
return new class extends Migration
{
    /** Identificador del modulo Academico en la tabla `modulos`. */
    private const MODULE_IDENTIFIER = 'M007';

    /** Roles que reciben el permiso (mismos que administran el colegio). */
    private const ROLES = ['admin', 'Administrador'];

    /**
     * @var array<int, string>
     */
    private array $permissions = [
        'aca_school_alumno_apoderados_editar',
    ];

    public function up(): void
    {
        foreach ($this->permissions as $name) {
            $permission = Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);

            $this->linkToModule($permission);
        }

        foreach (self::ROLES as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();

            if (! $role) {
                continue;
            }

            foreach ($this->permissions as $name) {
                if (! $role->hasPermissionTo($name)) {
                    $role->givePermissionTo($name);
                }
            }
        }

        // Los permisos se cachean en spatie; sin esto el nuevo no se ve.
        Artisan::call('permission:cache-reset');
    }

    public function down(): void
    {
        foreach (self::ROLES as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();

            if (! $role) {
                continue;
            }

            foreach ($this->permissions as $name) {
                if ($role->hasPermissionTo($name)) {
                    $role->revokePermissionTo($name);
                }
            }
        }

        // Las filas de role_has_permissions y model_has_permissions caen por cascade.
        Permission::whereIn('name', $this->permissions)
            ->where('guard_name', 'web')
            ->delete();

        Artisan::call('permission:cache-reset');
    }

    /**
     * Enlaza el permiso al modulo Academico para que el editor de roles lo
     * agrupe en su carpeta. Es solo la relacion con el modulo: no concede nada.
     */
    private function linkToModule(Permission $permission): void
    {
        $moduleExists = DB::table('modulos')
            ->where('identifier', self::MODULE_IDENTIFIER)
            ->exists();

        if (! $moduleExists) {
            return;
        }

        $alreadyLinked = DB::table('model_has_permissions')
            ->where('permission_id', $permission->id)
            ->where('model_type', Modulo::class)
            ->where('model_id', self::MODULE_IDENTIFIER)
            ->exists();

        if ($alreadyLinked) {
            return;
        }

        DB::table('model_has_permissions')->insertOrIgnore([
            'permission_id' => $permission->id,
            'model_type' => Modulo::class,
            'model_id' => self::MODULE_IDENTIFIER,
        ]);
    }
};
