<?php

namespace Modules\Sales\Support;

use App\Models\Modulo;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

/**
 * Catálogo único de permisos de Guías de Remisión Electrónicas.
 *
 * Lo usan la migración (producción, se ejecuta con `php artisan migrate`) y el
 * seeder (instalaciones nuevas), para que ambos caminos creen exactamente los
 * mismos permisos, los asocien al módulo de Facturación Electrónica (M003) y
 * los asignen a los roles administrativos.
 */
final class GuiaRemisionPermissions
{
    public const MODULE_IDENTIFIER = 'M003'; // Facturación Electrónica

    /**
     * @return array<int, string>
     */
    public static function names(): array
    {
        return [
            'invo_guia_remision',              // Ver el módulo de guías de remisión
            'invo_guia_remision_lista',        // Listar guías
            'invo_guia_remision_nuevo',        // Crear / editar guías
            'invo_guia_remision_envio_sunat',  // Enviar y consultar el ticket en SUNAT
            'invo_guia_remision_anular',       // Anular / eliminar una guía no aceptada
        ];
    }

    /**
     * @param  array<int, string>  $roles
     * @return array<int, string>
     */
    public static function sync(array $roles = ['Administrador', 'admin'], ?int $roleId = 1): array
    {
        try {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (Throwable $e) {
            // Sin caché de permisos disponible (por ejemplo en tests aislados).
        }

        $modulo = Modulo::firstOrCreate(
            ['identifier' => self::MODULE_IDENTIFIER],
            ['description' => 'Facturación Electrónica']
        );

        $created = [];

        foreach (self::names() as $name) {
            $permission = Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);

            $created[] = $name;

            // Asignación a los roles administrativos existentes.
            $query = Role::where('guard_name', 'web')->whereIn('name', $roles);

            if ($roleId !== null) {
                $query->orWhere('id', $roleId);
            }

            foreach ($query->get() as $role) {
                if (! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }

            // Agrupación en el editor de roles (módulo Facturación Electrónica).
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

        try {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (Throwable $e) {
            // Idem: la caché se reconstruye en la siguiente petición.
        }

        return $created;
    }

    /**
     * Revierte solo los permisos creados por este módulo.
     *
     * @return array<int, string>
     */
    public static function remove(): array
    {
        $names = self::names();

        Permission::whereIn('name', $names)->where('guard_name', 'web')->delete();

        try {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (Throwable $e) {
            // Sin caché disponible.
        }

        return $names;
    }
}
