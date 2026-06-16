<?php

namespace Modules\Integrationhub\Database\Seeders;

use App\Models\Modulo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;


class IntegrationhubDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::find(1);

        $modulo = Modulo::updateOrCreate(
            ['identifier' => 'M020'],
            ['description' => 'Centro de integraciónes', 'icon' => 'faPuzzlePiece']
        );

        $permissions = [
            'integrationhub_dashboard',
            'integrationhub_listado',
            'integrationhub_crear',
            'integrationhub_editar',
            'integrationhub_eliminar',
            'integrationhub_ejecutar',
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
