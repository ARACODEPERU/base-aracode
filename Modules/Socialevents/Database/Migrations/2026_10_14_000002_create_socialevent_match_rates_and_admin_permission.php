<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public const PERMISO = 'even_organizaciones_admin';

    public function up(): void
    {
        Schema::create('socialevent_match_rates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('description', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $permission = Permission::firstOrCreate(['name' => self::PERMISO, 'guard_name' => 'web']);

        // Solo los roles de administración reciben el permiso; el resto no ve estas vistas.
        foreach (Role::whereIn('name', ['admin', 'Administrador'])->get() as $role) {
            if (! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('socialevent_match_rates');

        $permission = Permission::where('name', self::PERMISO)->first();

        if ($permission) {
            DB::table('model_has_permissions')->where('permission_id', $permission->id)->delete();
            DB::table('role_has_permissions')->where('permission_id', $permission->id)->delete();
            $permission->delete();
        }
    }
};
