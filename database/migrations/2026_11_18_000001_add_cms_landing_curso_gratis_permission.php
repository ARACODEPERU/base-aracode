<?php

use App\Models\Modulo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        // Permiso del mantenedor "landing del curso gratuito" del CMS. El
        // seeder del modulo tambien lo crea; aqui se asegura en las bases que
        // ya estaban instaladas y no lo tienen.
        $permission = Permission::firstOrCreate([
            'name' => 'cms_landing_curso_gratis',
            'guard_name' => 'web',
        ]);

        // Acceso inicial solo para los roles de administracion; el resto de
        // roles puede recibirlo despues desde Configuraciones -> Roles.
        foreach (['admin', 'Administrador'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();

            if ($role && ! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }

        // Enlazar el permiso al modulo CMS (M005) para agruparlo en el editor
        // de roles, igual que hace SeedPermissionsUsersTableSeeder.
        $modulo = Modulo::where('identifier', 'M005')->first();

        if ($modulo) {
            DB::table('model_has_permissions')->insertOrIgnore([
                'permission_id' => $permission->id,
                'model_type' => Modulo::class,
                'model_id' => $modulo->identifier,
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', 'cms_landing_curso_gratis')
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
