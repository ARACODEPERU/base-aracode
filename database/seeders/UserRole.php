<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserRole extends Seeder
{
    /**
     * Run the database seeds.
     *
     * El rol 'admin' y sus permisos base ya existen (RolesSeeder corre antes),
     * así que aquí solo se completan y se crea el usuario administrador.
     *
     * Email y contraseña salen del .env para que una instalación nueva no quede
     * con credenciales fijas conocidas: ADMIN_EMAIL / ADMIN_PASSWORD. Sin esas
     * variables se usan los valores históricos.
     */
    public function run()
    {
        $role = Role::firstOrCreate(['name' => 'admin']);

        $permissions = [];

        array_push($permissions, Permission::firstOrCreate(['name' => 'dashboard']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'configuracion']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'empresa']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'modulos']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'usuarios']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'usuarios_nuevo']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'usuarios_editar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'usuarios_eliminar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'usuarios_ver']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'roles']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'permisos']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'parametros']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'parametros_nuevo']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'parametros_editar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'parametros_eliminar']));

        foreach ($permissions as $permission) {
            $role->givePermissionTo($permission->name);
        }

        $email = (string) env('ADMIN_EMAIL', 'admin@gmail.com');

        // Búsqueda explícita e insensible a mayúsculas: si la collation de la
        // columna es sensible a mayúsculas (utf8mb4_bin, *_as_cs), un `where`
        // directo no encontraría 'Admin@Gmail.com' frente a 'admin@gmail.com'
        // y el INSERT posterior fallaría con Duplicate entry.
        $findByEmail = function (string $value) {
            return User::query()
                ->whereRaw('LOWER(email) = ?', [mb_strtolower($value)])
                ->first();
        };

        $user = $findByEmail($email);

        if (! $user) {
            try {
                $user = User::create([
                    'email'             => $email,
                    'name'              => 'Admin',
                    'password'          => Hash::make((string) env('ADMIN_PASSWORD', '12345678')),
                    'email_verified_at' => Carbon::now(),
                    'local_id'          => 1,
                    'company_id'        => 1,
                ]);
            } catch (UniqueConstraintViolationException $e) {
                // El correo ya existe (p. ej. otra variante de mayúsculas que
                // el LOWER no normalizó por espacios, o un proceso concurrente).
                $user = User::query()
                    ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
                    ->first();

                if (! $user) {
                    throw $e;
                }
            }
        }

        if ($user->wasRecentlyCreated) {
            $this->command?->info("Usuario administrador creado: {$email}.");
        } else {
            $this->command?->info("Usuario administrador ya existe: {$email}. No se vuelve a crear.");
        }

        $user->assignRole('admin');
    }
}
