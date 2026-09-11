<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate(['name' => 'crm_dudas_comunes']);

        $alumno = Role::where('name', 'Alumno')->first();
        if ($alumno && !$alumno->hasPermissionTo('crm_dudas_comunes')) {
            $alumno->givePermissionTo('crm_dudas_comunes');
        }
    }

    public function down(): void
    {
        $alumno = Role::where('name', 'Alumno')->first();
        if ($alumno) {
            $alumno->revokePermissionTo('crm_dudas_comunes');
        }

        Permission::where('name', 'crm_dudas_comunes')->delete();
    }
};