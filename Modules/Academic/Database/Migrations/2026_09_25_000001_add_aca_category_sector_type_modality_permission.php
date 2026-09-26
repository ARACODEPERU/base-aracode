<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Permiso del mantenedor de Categorias/Tipo/Sector/Modalidad de cursos.
     *
     * Solo los roles administradores (admin y Administrador) podran entrar a
     * ver y editar esas opciones. No se registra en model_has_permissions del
     * modulo M007 (patron del PermissionTableSeeder) para que ningun otro rol
     * lo herede automaticamente.
     */
    public function up(): void
    {
        Permission::firstOrCreate(['name' => 'aca_category_sector_type_modality']);

        foreach (['admin', 'Administrador'] as $roleName) {
            $role = Role::where('name', $roleName)->first();

            if ($role && ! $role->hasPermissionTo('aca_category_sector_type_modality')) {
                $role->givePermissionTo('aca_category_sector_type_modality');
            }
        }
    }

    public function down(): void
    {
        foreach (['admin', 'Administrador'] as $roleName) {
            $role = Role::where('name', $roleName)->first();

            if ($role && $role->hasPermissionTo('aca_category_sector_type_modality')) {
                $role->revokePermissionTo('aca_category_sector_type_modality');
            }
        }

        Permission::where('name', 'aca_category_sector_type_modality')->delete();

        // El seeder del modulo vuelve a crear el permiso al re-sembrar; el
        // cache de permisos de spatie debe refrescarse para que la revocacion
        // surta efecto de inmediato.
        Artisan::call('permission:cache-reset');
    }
};
