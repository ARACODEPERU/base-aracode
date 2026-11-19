<?php

use App\Models\Parameter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Asegura el módulo de IA en instalaciones que se actualizan solo con
 * `php artisan migrate` (producción).
 *
 * La migración 2026_11_19_000002 creó los permisos y los concedió a los roles
 * `admin` y `Administrador`, pero si en ese momento los roles todavía no
 * existían, el menú y las rutas quedaban ocultos para todos. Lo mismo pasaba
 * con los parámetros IA-* y los precios iniciales si la instalación venía de
 * una versión anterior.
 *
 * Es idempotente y aditiva:
 *  - los permisos se crean con firstOrCreate y solo se conceden si faltan;
 *  - los parámetros se crean si no existen y nunca se pisa un valor guardado;
 *  - los precios solo se insertan cuando el modelo no tiene tarifa;
 *  - down() no revoca nada para no dejar sin acceso a un rol configurado a mano.
 */
return new class extends Migration
{
    /**
     * Permisos que exigen las rutas y el menú del módulo.
     *
     * @var array<int, string>
     */
    private const PERMISSIONS = [
        'ia_dashboard',
        'ia_configuracion',
        'ia_consumo',
        'ia_precios',
        'ia_probar',
        'ia_aracodito',
    ];

    /**
     * Roles que administran el sistema y reciben los permisos al instalar.
     *
     * @var array<int, string>
     */
    private const ADMIN_ROLES = ['admin', 'Administrador'];

    /**
     * Parámetros IA-* con su valor inicial. El valor solo se escribe cuando la
     * fila se crea; los ajustes ya guardados por el administrador se respetan.
     *
     * @var array<string, array{description: string, control_type: string, json_query_data: ?string, value: ?string}>
     */
    private const PARAMETERS = [
        'IA-00001' => [
            'description' => 'API Key de OpenAI para las consultas de IA del sistema',
            'control_type' => 'pwd',
            'json_query_data' => null,
            'value' => null,
        ],
        'IA-00002' => [
            'description' => 'Proveedor de inteligencia artificial para el chat de consultas (1 OpenAI, 2 Gemini)',
            'control_type' => 'sa',
            'json_query_data' => '[{"value":"1","label":"OpenAI"},{"value":"2","label":"Gemini AI"}]',
            'value' => '2',
        ],
        'IA-00003' => [
            'description' => 'ID del agente de OpenAI (Agents API, formato agent_...) que responde las consultas de los alumnos',
            'control_type' => 'tx',
            'json_query_data' => null,
            'value' => null,
        ],
        'IA-00004' => [
            'description' => 'Tiempo maximo de espera de la respuesta del agente de OpenAI (Agents API), en minutos',
            'control_type' => 'tx',
            'json_query_data' => null,
            'value' => '6',
        ],
        'IA-00005' => [
            'description' => 'Modelo de OpenAI que responde las consultas de IA (por ejemplo gpt-4.1-mini)',
            'control_type' => 'tx',
            'json_query_data' => null,
            'value' => 'gpt-4.1-mini',
        ],
        'IA-00006' => [
            'description' => 'Modelo de Gemini que responde las consultas de IA (por ejemplo gemini-2.5-flash)',
            'control_type' => 'tx',
            'json_query_data' => null,
            'value' => 'gemini-2.5-flash',
        ],
        'IA-00007' => [
            'description' => 'Presupuesto mensual de IA en dólares para todo el sistema (0 = sin límite)',
            'control_type' => 'tx',
            'json_query_data' => null,
            'value' => '0',
        ],
        'IA-00008' => [
            'description' => 'Consultas de IA permitidas por usuario y día (0 = sin límite)',
            'control_type' => 'tx',
            'json_query_data' => null,
            'value' => '0',
        ],
        'IA-00009' => [
            'description' => 'Guardar el texto de las consultas de IA para auditoría (1 = sí, 0 = no)',
            'control_type' => 'chx',
            'json_query_data' => null,
            'value' => '0',
        ],
        'IA-00010' => [
            'description' => 'Máximo de tokens de salida por respuesta de IA',
            'control_type' => 'tx',
            'json_query_data' => null,
            'value' => '800',
        ],
        'IA-00011' => [
            'description' => 'Temperatura de las respuestas de IA (0 = precisa, 2 = creativa)',
            'control_type' => 'tx',
            'json_query_data' => null,
            'value' => '0.2',
        ],
        'IA-00012' => [
            'description' => 'API Key de Gemini para las consultas de IA del sistema',
            'control_type' => 'pwd',
            'json_query_data' => null,
            'value' => null,
        ],
        'IA-00013' => [
            'description' => 'Porcentaje del presupuesto mensual de IA para avisar antes de llegar al límite (0 = sin aviso)',
            'control_type' => 'tx',
            'json_query_data' => null,
            'value' => '80',
        ],
        'IA-00014' => [
            'description' => 'Cortar las consultas de IA al superar el presupuesto mensual (1 = sí, 0 = solo avisar)',
            'control_type' => 'chx',
            'json_query_data' => null,
            'value' => '1',
        ],
    ];

    /**
     * Tarifas iniciales en dólares por millón de tokens. Solo se usan cuando el
     * proveedor y el modelo todavía no tienen precio en la tabla.
     *
     * @var array<int, array{provider: string, model: string, input: float, output: float}>
     */
    private const PRICES = [
        ['provider' => 'openai', 'model' => 'gpt-4.1-mini', 'input' => 0.40, 'output' => 1.60],
        ['provider' => 'openai', 'model' => 'gpt-4.1', 'input' => 2.00, 'output' => 8.00],
        ['provider' => 'gemini', 'model' => 'gemini-2.5-flash', 'input' => 0.30, 'output' => 2.50],
        ['provider' => 'gemini', 'model' => 'gemini-2.5-pro', 'input' => 1.25, 'output' => 10.00],
    ];

    public function up(): void
    {
        $this->ensurePermissions();
        $this->seedParameters();
        $this->seedPrices();
    }

    public function down(): void
    {
        // Aditiva a propósito: borrar permisos o parámetros podría dejar sin
        // acceso o sin configuración una instalación ya en uso.
    }

    /**
     * Crea los permisos del módulo y los concede a los roles de administración.
     */
    private function ensurePermissions(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = Role::query()
            ->whereIn('name', self::ADMIN_ROLES)
            ->where('guard_name', 'web')
            ->get();

        foreach (self::PERMISSIONS as $name) {
            $permission = Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);

            foreach ($roles as $role) {
                if (! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Crea los parámetros IA-* que falten sin tocar los valores existentes.
     */
    private function seedParameters(): void
    {
        if (! Schema::hasTable('parameters')) {
            return;
        }

        foreach (self::PARAMETERS as $code => $attributes) {
            $parameter = Parameter::firstOrNew(['parameter_code' => $code]);

            $parameter->fill([
                'description' => $attributes['description'],
                'control_type' => $attributes['control_type'],
                'json_query_data' => $attributes['json_query_data'],
            ]);

            if (! $parameter->exists) {
                $parameter->value_default = $attributes['value'];
            }

            $parameter->save();
        }
    }

    /**
     * Inserta las tarifas iniciales solo donde no haya una ya configurada.
     */
    private function seedPrices(): void
    {
        if (! Schema::hasTable('ai_model_prices')) {
            return;
        }

        foreach (self::PRICES as $price) {
            $existe = DB::table('ai_model_prices')
                ->where('provider', $price['provider'])
                ->where('model', $price['model'])
                ->exists();

            if ($existe) {
                continue;
            }

            DB::table('ai_model_prices')->insert([
                'provider' => $price['provider'],
                'model' => $price['model'],
                'input_price_per_million' => $price['input'],
                'output_price_per_million' => $price['output'],
                'currency' => 'USD',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
