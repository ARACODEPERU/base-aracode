<?php

use App\Models\Modulo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facturador3_import_exclusions', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_database', 100);
            $table->unsignedBigInteger('f3_item_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->unique(['tenant_database', 'f3_item_id']);
            $table->index('tenant_database');
        });

        $permission = Permission::firstOrCreate([
            'name' => 'sale_importar_facturador3',
            'guard_name' => 'web',
        ]);

        $role = Role::find(1);
        if ($role && ! $role->hasPermissionTo('sale_importar_facturador3')) {
            $role->givePermissionTo($permission);
        }

        $modulo = Modulo::where('identifier', 'M002')->first();
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
        Schema::dropIfExists('facturador3_import_exclusions');

        Permission::where('name', 'sale_importar_facturador3')->delete();
    }
};
