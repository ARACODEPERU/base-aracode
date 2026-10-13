<?php

namespace Tests\Unit\Modules\CMS\Concerns;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Esquema mínimo del buzón de contacto para las pruebas.
 *
 * Igual que el resto de los módulos: el suite no puede correr todas las
 * migraciones de la aplicación sobre sqlite, así que aquí se arma solo lo que
 * toca el borrado de un mensaje —la tabla del buzón y las de spatie, que son las
 * que resuelven el permiso— y se ejecutan las migraciones reales del módulo que
 * crean los permisos del buzón.
 */
trait BuildsContactMessagesSchema
{
    private const MODULE_MIGRATIONS = [
        'Modules/CMS/Database/Migrations/2026_09_16_000001_add_contact_messages_blog_subscribers_permissions.php',
        'Modules/CMS/Database/Migrations/2026_10_13_000001_add_contact_messages_delete_permission.php',
    ];

    private function createContactMessagesSchema(): void
    {
        Schema::create('users', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->string('name');
            $tabla->string('email')->unique();
            $tabla->timestamp('email_verified_at')->nullable();
            $tabla->string('password');
            $tabla->rememberToken();
            $tabla->timestamps();
        });

        Schema::create('roles', function (Blueprint $tabla) {
            $tabla->bigIncrements('id');
            $tabla->string('name');
            $tabla->string('guard_name');
            $tabla->timestamps();
            $tabla->unique(['name', 'guard_name']);
        });

        Schema::create('permissions', function (Blueprint $tabla) {
            $tabla->bigIncrements('id');
            $tabla->string('name');
            $tabla->string('guard_name');
            $tabla->timestamps();
            $tabla->unique(['name', 'guard_name']);
        });

        Schema::create('model_has_roles', function (Blueprint $tabla) {
            $tabla->unsignedBigInteger('role_id');
            $tabla->string('model_type');
            $tabla->unsignedBigInteger('model_id');
            $tabla->primary(['role_id', 'model_type', 'model_id']);
        });

        Schema::create('model_has_permissions', function (Blueprint $tabla) {
            $tabla->unsignedBigInteger('permission_id');
            $tabla->string('model_type');
            $tabla->unsignedBigInteger('model_id');
            $tabla->primary(['permission_id', 'model_type', 'model_id']);
        });

        Schema::create('role_has_permissions', function (Blueprint $tabla) {
            $tabla->unsignedBigInteger('permission_id');
            $tabla->unsignedBigInteger('role_id');
            $tabla->primary(['permission_id', 'role_id']);
        });

        // El buzón: la misma forma que su migración real.
        Schema::create('contact_messages', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->string('name');
            $tabla->string('email');
            $tabla->string('phone')->nullable();
            $tabla->string('company')->nullable();
            $tabla->string('service')->nullable();
            $tabla->text('message');
            $tabla->enum('status', ['pending', 'read', 'replied'])->default('pending');
            $tabla->text('admin_notes')->nullable();
            $tabla->timestamps();
        });

        foreach (self::MODULE_MIGRATIONS as $migration) {
            (require base_path($migration))->up();
        }
    }

    private function dropContactMessagesSchema(): void
    {
        // Se tira por tablas en vez de correr los down() de las migraciones: lo
        // que se prueban son sus up() reales.
        foreach ([
            'contact_messages',
            'role_has_permissions',
            'model_has_permissions',
            'model_has_roles',
            'permissions',
            'roles',
            'users',
        ] as $tabla) {
            Schema::dropIfExists($tabla);
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

    private function user(string $email): User
    {
        return User::create([
            'name' => 'Usuario ' . $email,
            'email' => $email,
            'email_verified_at' => now(),
            'password' => Hash::make('secreto-de-prueba'),
        ]);
    }
}
