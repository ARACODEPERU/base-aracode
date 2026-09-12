<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Permiso del apartado "Testimonios" del alumno.
     */
    public function up(): void
    {
        Permission::firstOrCreate(['name' => 'aca_testimonios']);

        $alumno = Role::where('name', 'Alumno')->first();

        if ($alumno && !$alumno->hasPermissionTo('aca_testimonios')) {
            $alumno->givePermissionTo('aca_testimonios');
        }
    }

    public function down(): void
    {
        $alumno = Role::where('name', 'Alumno')->first();

        if ($alumno && $alumno->hasPermissionTo('aca_testimonios')) {
            $alumno->revokePermissionTo('aca_testimonios');
        }

        Permission::where('name', 'aca_testimonios')->delete();
    }
};
