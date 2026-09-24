<?php

namespace Tests\Unit\Database;

use Database\Seeders\PermissionsReconcileSeeder;
use Tests\TestCase;

/**
 * Blindaje del hueco entre migraciones y seeders.
 *
 * Las migraciones de los módulos conceden permisos a roles y los enlazan a
 * `modulos`, pero corren ANTES de los seeders: en una instalación desde cero no
 * existe ningún rol ni ninguna fila de `modulos`, así que esas concesiones se
 * pierden. Si el permiso además no lo crea ningún seeder, queda sin dueño.
 *
 * Esta prueba compara los nombres de permiso que aparecen en las migraciones de
 * los módulos contra los que aparecen en los seeders: todo el que solo exista en
 * migraciones tiene que estar declarado en PermissionsReconcileSeeder::ORPHANS
 * (con su módulo y los roles que la migración pretendía).
 */
class PermissionCatalogConsistencyTest extends TestCase
{
    public function test_todo_permiso_que_solo_crean_las_migraciones_esta_en_el_catalogo(): void
    {
        $inMigrations = $this->permissionNamesIn(base_path('Modules/*/Database/Migrations/*.php'));

        $inSeeders = $this->permissionNamesIn(array_merge(
            glob(base_path('Modules/*/Database/Seeders/*.php')) ?: [],
            glob(base_path('database/seeders/*.php')) ?: [],
        ));

        $onlyMigrations = array_values(array_diff($inMigrations, $inSeeders));

        $catalog = array_column(PermissionsReconcileSeeder::ORPHANS, 'name');

        $missing = array_values(array_diff($onlyMigrations, $catalog));

        $this->assertSame(
            [],
            $missing,
            'Estos permisos solo existen por migración y quedarían sin rol en una instalación nueva. '
            .'Agrégalos a PermissionsReconcileSeeder::ORPHANS: '.implode(', ', $missing)
        );
    }

    public function test_el_catalogo_no_tiene_entradas_duplicadas_ni_sin_modulo(): void
    {
        $names = array_column(PermissionsReconcileSeeder::ORPHANS, 'name');

        $this->assertSame($names, array_values(array_unique($names)), 'Hay permisos repetidos en el catálogo.');

        foreach (PermissionsReconcileSeeder::ORPHANS as $orphan) {
            $this->assertMatchesRegularExpression('/^M[0-9]{3}$/', $orphan['module'], "El permiso {$orphan['name']} no apunta a un identificador de módulo válido.");
            $this->assertNotEmpty($orphan['roles'], "El permiso {$orphan['name']} no concede a ningún rol.");
        }
    }

    /**
     * Nombres de permiso declarados en los archivos indicados.
     *
     * Reconoce las formas que usa este repositorio:
     *   - Permission::create(['name' => 'x']) y Permission::firstOrCreate(['name' => 'x'])
     *   - $permissions = ['x', 'y'];, $names = ['x', 'y']; y const ADMIN_PERMISSIONS = ['x', 'y'];
     *   - $permission = 'x';
     *
     * @param  string|array<int, string>  $patterns
     * @return array<int, string>
     */
    private function permissionNamesIn(string|array $patterns): array
    {
        $files = is_array($patterns) ? $patterns : (glob($patterns) ?: []);
        $names = [];

        foreach ($files as $file) {
            $code = file_get_contents($file);

            if (! is_string($code)) {
                continue;
            }

            if (preg_match_all("/Permission::(?:create|firstOrCreate)\(\s*\[\s*'name'\s*=>\s*'([^']+)'/", $code, $matches)) {
                $names = array_merge($names, $matches[1]);
            }

            if (preg_match_all('/\$permission\s*=\s*\'([^\']+)\'/', $code, $matches)) {
                $names = array_merge($names, $matches[1]);
            }

            // Arrays de nombres de permiso: $permissions, $names, const ADMIN_PERMISSIONS...
            if (preg_match_all('/(?:[A-Za-z_$]*permissions?|\$names)\s*=\s*\[(.*?)\];/si', $code, $blocks)) {
                foreach ($blocks[1] as $block) {
                    if (preg_match_all("/'([a-zA-Z][a-zA-Z0-9_]+)'/", $block, $inner)) {
                        $names = array_merge($names, $inner[1]);
                    }
                }
            }
        }

        // Los permisos del sistema llevan guion bajo; así se descartan nombres de
        // roles ('admin', 'Ventas') que también aparecen en esos archivos.
        $names = array_values(array_unique(array_filter(
            $names,
            fn (string $name) => str_contains($name, '_')
        )));

        sort($names);

        return $names;
    }
}
