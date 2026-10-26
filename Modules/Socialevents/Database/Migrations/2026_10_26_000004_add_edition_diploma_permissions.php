<?php

use App\Models\Modulo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Permisos del módulo de diplomas de torneos.
     *
     * Se crean también aquí (y no solo en el seeder) para que una instalación
     * ya migrada reciba los permisos al correr `artisan migrate`.
     */
    public function up(): void
    {
        $permissions = [
            'even_ediciones_diplomas',
            'even_ediciones_diplomas_disenar',
            'even_ediciones_premiados',
        ];

        $admin = Role::find(1);
        // Los organizadores usan el rol fijo `sports_organizer`: sin esto, una
        // organización existente recibiría 403 al abrir sus diplomas.
        $organizer = Role::where('name', 'sports_organizer')->where('guard_name', 'web')->first();
        $modulo = Modulo::query()->where('identifier', 'M013')->first();

        foreach ($permissions as $name) {
            $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);

            if ($admin && ! $admin->hasPermissionTo($name)) {
                $admin->givePermissionTo($permission);
            }

            if ($organizer && ! $organizer->hasPermissionTo($name)) {
                $organizer->givePermissionTo($permission);
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
        $names = [
            'even_ediciones_diplomas',
            'even_ediciones_diplomas_disenar',
            'even_ediciones_premiados',
        ];

        foreach (Permission::whereIn('name', $names)->get() as $permission) {
            DB::table('model_has_permissions')->where('permission_id', $permission->id)->delete();
            DB::table('role_has_permissions')->where('permission_id', $permission->id)->delete();
            $permission->delete();
        }
    }
};
