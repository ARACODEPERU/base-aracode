<?php

namespace Modules\Commercial\Database\Seeders;

use App\Models\Modulo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CommercialDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = Role::find(1);

        $modulo = Modulo::updateOrCreate(
            ['identifier' => 'M021'],
            ['description' => 'Comercial', 'icon' => 'faBriefcase', 'status' => true]
        );

        $permissions = [
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
        ];

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);

            if ($admin) {
                $admin->givePermissionTo($permission->name);
            }

            DB::table('model_has_permissions')->updateOrInsert([
                'permission_id' => $permission->id,
                'model_type' => Modulo::class,
                'model_id' => $modulo->identifier,
            ]);
        }
    }
}
