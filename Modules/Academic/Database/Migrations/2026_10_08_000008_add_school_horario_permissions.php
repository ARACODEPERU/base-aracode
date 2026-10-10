<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /** Permisos de administracion del horario (ver y editar). */
    public const ADMIN_PERMISSIONS = [
        'aca_school_horario_listado',
        'aca_school_horario_editar',
    ];

    /** Permiso del docente para consultar "Mi horario". */
    public const TEACHER_PERMISSION = 'aca_school_horario_docente';

    /**
     * Permisos del modulo de horarios del colegio.
     *
     * - aca_school_horario_listado / aca_school_horario_editar: mantenedor del
     *   horario y de la jornada (admin y Administrador).
     * - aca_school_horario_docente: el docente consulta su propio horario
     *   (rol Docente). Es el mismo permiso con el que mas adelante registrara
     *   la asistencia de su hora.
     *
     * Se enlazan al modulo M007 si ya existe y se conceden a los roles que
     * corresponden. En una instalacion desde cero los seeders del modulo
     * terminan el trabajo (el PermissionTableSeeder los crea y enlaza).
     */
    public function up(): void
    {
        foreach (self::ADMIN_PERMISSIONS as $name) {
            $permission = Permission::firstOrCreate(['name' => $name]);
            $this->linkToModule($permission);
            $this->grant($name, ['admin', 'Administrador']);
        }

        $teacherPermission = Permission::firstOrCreate(['name' => self::TEACHER_PERMISSION]);
        $this->linkToModule($teacherPermission);
        $this->grant(self::TEACHER_PERMISSION, ['Docente']);
    }

    public function down(): void
    {
        foreach (array_merge(self::ADMIN_PERMISSIONS, [self::TEACHER_PERMISSION]) as $name) {
            $roleNames = $name === self::TEACHER_PERMISSION
                ? ['Docente']
                : ['admin', 'Administrador'];

            $this->revoke($name, $roleNames);
            Permission::where('name', $name)->delete();
        }

        Artisan::call('permission:cache-reset');
    }

    /**
     * Enlaza el permiso al modulo Academico (M007) para que el editor de roles
     * lo agrupe en su modulo en lugar de dejarlo en "Otros permisos".
     */
    private function linkToModule(Permission $permission): void
    {
        $moduleExists = DB::table('modulos')->where('identifier', 'M007')->exists();

        if (! $moduleExists) {
            return;
        }

        $alreadyLinked = DB::table('model_has_permissions')
            ->where('permission_id', $permission->id)
            ->where('model_type', 'App\\Models\\Modulo')
            ->where('model_id', 'M007')
            ->exists();

        if ($alreadyLinked) {
            return;
        }

        DB::table('model_has_permissions')->insertOrIgnore([
            'permission_id' => $permission->id,
            'model_type' => 'App\\Models\\Modulo',
            'model_id' => 'M007',
        ]);
    }

    /**
     * @param  array<int, string> $roleNames
     */
    private function grant(string $permissionName, array $roleNames): void
    {
        foreach ($roleNames as $roleName) {
            $role = Role::where('name', $roleName)->first();

            if ($role && ! $role->hasPermissionTo($permissionName)) {
                $role->givePermissionTo($permissionName);
            }
        }
    }

    /**
     * @param  array<int, string> $roleNames
     */
    private function revoke(string $permissionName, array $roleNames): void
    {
        foreach ($roleNames as $roleName) {
            $role = Role::where('name', $roleName)->first();

            if ($role && $role->hasPermissionTo($permissionName)) {
                $role->revokePermissionTo($permissionName);
            }
        }
    }
};
