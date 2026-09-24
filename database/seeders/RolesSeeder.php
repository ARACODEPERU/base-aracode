<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Crea los roles que el sistema espera encontrar desde el primer arranque.
 *
 * Por qué existe: antes cada seeder creaba los roles que necesitaba por su cuenta
 * ('Alumno' y 'Docente' en Académico, 'Doctor' en Salud, 'Administrador' y 'Ventas'
 * en Comercial), así que una instalación nueva quedaba sin los roles que el editor
 * de permisos muestra: solo aparecían los de los módulos que se sembraran.
 *
 * Orden importante: 'admin' se crea PRIMERO para que conserve el id 1. Muchos
 * seeders de módulos resuelven el rol administrador con Role::find(1).
 *
 * No concede permisos: de eso se encargan los seeders de cada módulo.
 */
class RolesSeeder extends Seeder
{
    /**
     * Guard de la aplicación.
     */
    public const GUARD = 'web';

    /**
     * Roles base, en orden. 'admin' primero (id 1).
     *
     * Se usa la grafía que el código busca (`Role::where('name', 'Ventas')`,
     * `assignRole('Docente')`). En MySQL el índice único de spatie es
     * insensible a mayúsculas, así que 'Ventas' y 'ventas' son la MISMA fila:
     * sembrar las dos grafías no crea dos roles, solo confunde al listarlos.
     *
     * @var array<int, string>
     */
    public const ROLES = [
        'admin',
        'Administrador',
        'Ventas',
        'Docente',
        'Alumno',
        'Doctor',
        'BiblioAdmin',
        'Lector',
        'Automatizaciones',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::ROLES as $name) {
            Role::firstOrCreate([
                'name'       => $name,
                'guard_name' => self::GUARD,
            ]);
        }

        $this->command?->info('Roles base verificados: '.implode(', ', self::ROLES).'.');
    }
}
