<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permisos del módulo de IA.
 *
 * Se conceden a los roles de administración; el resto de roles puede
 * recibirlos después desde Configuraciones → Roles.
 */
return new class extends Migration
{
    private const PERMISSIONS = [
        'ia_dashboard' => 'Ver el panel del módulo de IA',
        'ia_configuracion' => 'Configurar proveedor, modelos y límites de IA',
        'ia_consumo' => 'Ver el consumo de IA por usuario',
        'ia_precios' => 'Administrar los precios por modelo',
        'ia_probar' => 'Probar una consulta de IA desde el módulo',
        'ia_aracodito' => 'Usar a Aracodito con IA',
    ];

    public function up(): void
    {
        foreach (array_keys(self::PERMISSIONS) as $name) {
            $permission = Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);

            foreach (['Administrador', 'admin'] as $roleName) {
                $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();

                if ($role && ! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', array_keys(self::PERMISSIONS))
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
