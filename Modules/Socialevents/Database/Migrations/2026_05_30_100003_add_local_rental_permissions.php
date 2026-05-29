<?php

use App\Models\Modulo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'even_alquiler_local_listado',
            'even_alquiler_local_nuevo',
        ];

        $admin = Role::find(1);
        $modulo = Modulo::query()->where('identifier', 'M013')->first();

        foreach ($permissions as $name) {
            $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);

            if ($admin && ! $admin->hasPermissionTo($name)) {
                $admin->givePermissionTo($permission);
            }

            if ($modulo) {
                $exists = DB::table('model_has_permissions')
                    ->where('permission_id', $permission->id)
                    ->where('model_type', Modulo::class)
                    ->where('model_id', $modulo->identifier)
                    ->exists();

                if (! $exists) {
                    DB::table('model_has_permissions')->insert([
                        'permission_id' => $permission->id,
                        'model_type' => Modulo::class,
                        'model_id' => $modulo->identifier,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        $permissions = Permission::whereIn('name', [
            'even_alquiler_local_listado',
            'even_alquiler_local_nuevo',
        ])->get();

        foreach ($permissions as $permission) {
            DB::table('model_has_permissions')->where('permission_id', $permission->id)->delete();
            DB::table('role_has_permissions')->where('permission_id', $permission->id)->delete();
            $permission->delete();
        }
    }
};
