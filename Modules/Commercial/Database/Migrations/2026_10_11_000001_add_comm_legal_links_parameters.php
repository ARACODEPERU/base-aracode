<?php

use App\Models\Modulo;
use App\Models\Parameter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Enlaces legales del modulo Comercial que el formulario publico de una
     * negociacion muestra como checks obligatorios (terminos y condiciones, politica
     * de devoluciones y reembolsos, y politica de privacidad), mas los permisos de la
     * pagina donde se configuran.
     *
     * Cada enlace vive en un parametro, con el mismo patron de PN00001 (correos de
     * notificacion), para poder cambiarlo desde Comercial > Configuracion sin tocar
     * codigo. Un valor propio del sitio se guarda como ruta (/terminos-condiciones) y
     * uno externo como URL completa (https://...).
     *
     * Idempotente: si el parametro ya existe se respeta su valor y solo se actualiza
     * la descripcion.
     */
    private const PARAMETERS = [
        'PN00002' => [
            'description' => 'Enlace de los Terminos y condiciones (negociacion publica)',
            'value' => '/terminos-condiciones',
        ],
        'PN00003' => [
            'description' => 'Enlace de la Politica de devoluciones y reembolsos (negociacion publica)',
            'value' => 'https://academy.globalcpaperu.com/politicas-de-devolucion',
        ],
        'PN00004' => [
            'description' => 'Enlace de la Politica de privacidad (negociacion publica)',
            'value' => '/politica-privacidad',
        ],
    ];

    private const PERMISSIONS = [
        'comm_configuracion_ver',
        'comm_configuracion_editar',
    ];

    public function up(): void
    {
        if (Schema::hasTable('parameters')) {
            foreach (self::PARAMETERS as $code => $data) {
                $parameter = Parameter::where('parameter_code', $code)->first();

                if ($parameter) {
                    $parameter->update(['description' => $data['description']]);

                    continue;
                }

                Parameter::create([
                    'parameter_code' => $code,
                    'description' => $data['description'],
                    'control_type' => 'tx',
                    'json_query_data' => null,
                    'value_default' => $data['value'],
                ]);
            }
        }

        if (Schema::hasTable('permissions')) {
            $this->createPermissions();
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('parameters')) {
            // Se revierte solo la fila propia: el codigo igual que en la creacion y la
            // descripcion para no borrar un parametro ajeno con el mismo codigo.
            Parameter::whereIn('parameter_code', array_keys(self::PARAMETERS))
                ->whereIn('description', array_column(self::PARAMETERS, 'description'))
                ->delete();
        }

        if (Schema::hasTable('permissions')) {
            Permission::whereIn('name', self::PERMISSIONS)->delete();
        }
    }

    private function createPermissions(): void
    {
        foreach (self::PERMISSIONS as $permissionName) {
            $permission = Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);

            // Otorgar al rol administrador.
            foreach (['Administrador', 'admin'] as $roleName) {
                $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
                if ($role && ! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }

            // Enlazar el permiso al modulo Comercial para agruparlo en el editor de roles.
            $modulo = Modulo::where('identifier', 'M021')->first();
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
    }
};
