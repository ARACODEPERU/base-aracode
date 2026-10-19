<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public const PERMISOS = [
        'even_local_listado',
        'even_local_nuevo',
        'even_local_editar',
        'even_local_eliminar',
    ];

    public function up(): void
    {
        if (! Schema::hasColumn('even_locals', 'created_by')) {
            Schema::table('even_locals', function (Blueprint $table) {
                $table->foreignId('created_by')->nullable()->after('ubigeo')->constrained('users')->nullOnDelete();
            });
        }

        // Los locales creados antes no tienen dueño: solo los administradores los editan o eliminan en modo deportivo.
        $role = Role::firstOrCreate(['name' => 'sports_organizer', 'guard_name' => 'web']);

        foreach (self::PERMISOS as $name) {
            $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);

            if (! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (Schema::hasColumn('even_locals', 'created_by')) {
            Schema::table('even_locals', function (Blueprint $table) {
                $table->dropConstrainedForeignId('created_by');
            });
        }

        $role = Role::where('name', 'sports_organizer')->where('guard_name', 'web')->first();
        $permissions = Permission::whereIn('name', self::PERMISOS)->get();

        if ($role) {
            foreach ($permissions as $permission) {
                $role->revokePermissionTo($permission);
            }
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
