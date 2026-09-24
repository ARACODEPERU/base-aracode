<?php

namespace Tests\Unit\Modules\Security\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Esquema mínimo del Modo Super Editor para las pruebas.
 *
 * El suite no puede correr todas las migraciones de la aplicación sobre sqlite
 * (hay migraciones con esquema MySQL), así que las pruebas del módulo arman solo
 * lo que necesitan: las tablas de spatie y las tres tablas del modo, estas
 * últimas ejecutando sus migraciones reales.
 *
 * Vive en un trait porque las tres pruebas del módulo (panel, modo y endpoints)
 * compartirían si no la misma copia.
 */
trait BuildsSuperEditorSchema
{
    private const MODULE_MIGRATIONS = [
        'Modules/Security/Database/Migrations/2026_09_23_000001_create_super_editor_sessions_table.php',
        'Modules/Security/Database/Migrations/2026_09_23_000002_create_super_editor_staged_changes_table.php',
        'Modules/Security/Database/Migrations/2026_09_23_000003_create_super_editor_audits_table.php',
    ];

    private function createSuperEditorSchema(): void
    {
        // Solo la usan las pruebas que pasan por HTTP (necesitan un usuario real
        // con rol); el modo y el panel trabajan contra el contrato
        // Authenticatable y no la tocan.
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_type', 'model_id']);
        });

        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['permission_id', 'model_type', 'model_id']);
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });

        foreach (self::MODULE_MIGRATIONS as $migration) {
            (require base_path($migration))->up();
        }
    }

    private function dropSuperEditorSchema(): void
    {
        foreach (self::MODULE_MIGRATIONS as $migration) {
            (require base_path($migration))->down();
        }

        foreach ([
            'role_has_permissions',
            'model_has_permissions',
            'model_has_roles',
            'permissions',
            'roles',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function role(string $name): Role
    {
        return Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    private function permission(string $name): Permission
    {
        return Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }
}
