<?php

use App\Models\Modulo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    private string $permission = 'crm_chat_asistente';

    /**
     * Permiso para que los administradores vean el chat como los asistentes.
     */
    public function up(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => $this->permission,
            'guard_name' => 'web',
        ]);

        // Solo los administradores pueden hacerse pasar por un asistente.
        foreach (['admin', 'Administrador'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();

            if ($role && ! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }

        // Enlazar el permiso al modulo CRM para agruparlo en el editor de roles.
        $modulo = Modulo::where('identifier', 'M008')->first();

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

    public function down(): void
    {
        $permission = Permission::where('name', $this->permission)->first();

        if ($permission) {
            DB::table('model_has_permissions')
                ->where('permission_id', $permission->id)
                ->where('model_type', Modulo::class)
                ->where('model_id', 'M008')
                ->delete();

            $permission->delete();
        }
    }
};
