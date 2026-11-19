<?php

namespace Modules\Ai\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Parameter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Modules\Ai\Entities\AiRoleLimit;
use Modules\Ai\Support\AccesoIa;
use Modules\Ai\Support\IaSettings;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Configuración de IA desde el módulo.
 *
 * Lee y escribe las mismas filas de la tabla `parameters` que la pantalla de
 * Parámetros del sistema: no hay una segunda configuración. Cualquier cambio
 * aquí invalida la caché y se ve al instante en Parámetros, y viceversa.
 */
class AiConfiguracionController extends Controller
{
    private const SECRET_PLACEHOLDER = '********';

    public function index()
    {
        $parameters = Parameter::query()
            ->whereIn('parameter_code', IaSettings::codes())
            ->orderBy('parameter_code')
            ->get()
            ->map(fn (Parameter $parameter) => $this->formatear($parameter))
            ->values()
            ->all();

        return Inertia::render('Ai::Configuracion/Index', [
            'parameters' => $parameters,
            'roles' => $this->rolesConLimites(),
            'providerOptions' => [
                ['value' => '1', 'label' => 'OpenAI'],
                ['value' => '2', 'label' => 'Gemini AI'],
            ],
            'effective' => [
                'provider' => IaSettings::provider(),
                'openai_model' => IaSettings::openAiModel(),
                'gemini_model' => IaSettings::geminiModel(),
                'max_output_tokens' => IaSettings::maxOutputTokens(),
                'temperature' => IaSettings::temperature(),
                'monthly_budget' => IaSettings::monthlyBudgetUsd(),
                'daily_limit' => IaSettings::dailyLimit(),
                'store_prompts' => IaSettings::storesPrompts(),
            ],
        ]);
    }

    /**
     * Guarda solo los códigos IA conocidos. Un confidencial vacío o con la
     * máscara conserva el valor que ya estaba guardado.
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'values' => 'required|array',
            'values.IA-00001' => 'nullable|string|max:500',
            'values.IA-00002' => 'nullable|in:1,2',
            'values.IA-00003' => 'nullable|string|max:255',
            'values.IA-00004' => 'nullable|numeric|min:1|max:30',
            'values.IA-00005' => 'nullable|string|max:100',
            'values.IA-00006' => 'nullable|string|max:100',
            'values.IA-00007' => 'nullable|numeric|min:0',
            'values.IA-00008' => 'nullable|integer|min:0',
            'values.IA-00009' => 'nullable|in:0,1',
            'values.IA-00010' => 'nullable|integer|min:1|max:32000',
            'values.IA-00011' => 'nullable|numeric|min:0|max:2',
            'values.IA-00012' => 'nullable|string|max:500',
        ]);

        foreach ($data['values'] as $code => $value) {
            if (! in_array($code, IaSettings::codes(), true)) {
                continue;
            }

            $parameter = Parameter::firstOrNew(['parameter_code' => $code]);
            $isSecret = $parameter->exists && $parameter->control_type === 'pwd';
            $value = is_string($value) ? trim($value) : (string) $value;

            if ($isSecret && ($value === '' || $value === self::SECRET_PLACEHOLDER)) {
                continue;
            }

            if (! $parameter->exists) {
                $parameter->description = $this->descripcionPorDefecto($code);
                $parameter->control_type = in_array($code, ['IA-00001', 'IA-00012'], true) ? 'pwd' : 'tx';
            }

            $parameter->value_default = $value;
            $parameter->save();

            IaSettings::flush($code);
        }

        return response()->json([
            'success' => true,
            'message' => 'La configuración de IA se guardó correctamente.',
        ]);
    }

    /**
     * Guarda los límites diarios y mensuales por rol. Un 0 no agrega límite.
     */
    public function updateRoles(Request $request)
    {
        $data = $request->validate([
            'limits' => 'required|array',
            'limits.*.role_name' => 'required|string|max:100',
            'limits.*.daily_limit' => 'nullable|integer|min:0|max:100000',
            'limits.*.monthly_budget_usd' => 'nullable|numeric|min:0',
        ]);

        if (! Schema::hasTable('ai_role_limits')) {
            return response()->json([
                'success' => false,
                'message' => 'Falta ejecutar las migraciones del módulo de IA.',
            ], 422);
        }

        $rolesValidos = Role::query()->where('guard_name', 'web')->pluck('name')->all();

        foreach ($data['limits'] as $row) {
            $roleName = (string) ($row['role_name'] ?? '');

            if (! in_array($roleName, $rolesValidos, true)) {
                continue;
            }

            AiRoleLimit::updateOrCreate(
                ['role_name' => $roleName],
                [
                    'daily_limit' => (int) ($row['daily_limit'] ?? 0),
                    'monthly_budget_usd' => (float) ($row['monthly_budget_usd'] ?? 0),
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Los límites por rol se guardaron correctamente.',
        ]);
    }

    /**
     * Habilita o quita Aracodito IA a los roles elegidos por admin.
     *
     * El permiso `ia_aracodito` es la fuente de verdad y el rol admin no se
     * puede quitar. Un rol sin el permiso pierde el acceso de inmediato.
     */
    public function updateAracoditoIa(Request $request)
    {
        if (! AccesoIa::esAdmin($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Solo el rol admin puede cambiar el acceso a la IA de Aracodito.',
            ], 403);
        }

        $data = $request->validate([
            'roles' => 'array',
            'roles.*' => 'string|max:100',
        ]);

        $permiso = Permission::query()
            ->where('name', AccesoIa::PERMISO)
            ->where('guard_name', 'web')
            ->first();

        if (! $permiso) {
            return response()->json([
                'success' => false,
                'message' => 'Falta el permiso ia_aracodito. Ejecuta las migraciones del módulo de IA.',
            ], 422);
        }

        $seleccionados = collect($data['roles'] ?? []);

        foreach (Role::query()->where('guard_name', 'web')->get() as $rol) {
            if ($rol->name === AccesoIa::ROL_ADMIN || $seleccionados->contains($rol->name)) {
                $rol->givePermissionTo($permiso);
            } else {
                $rol->revokePermissionTo($permiso);
            }
        }

        // Los permisos se cachean por petición/grupo: se limpia para que el
        // cambio se note en la siguiente carga y en el mapa de pantallas.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json([
            'success' => true,
            'message' => 'Los roles con Aracodito IA se actualizaron.',
            'roles' => $this->rolesConLimites(),
        ]);
    }

    /**
     * Roles del sistema con su límite propio (0 = sin límite propio).
     *
     * @return array<int, array{name: string, daily_limit: int, monthly_budget_usd: float}>
     */
    private function rolesConLimites(): array
    {
        $limites = Schema::hasTable('ai_role_limits')
            ? AiRoleLimit::all()->keyBy('role_name')
            : collect();

        $permisoIa = Permission::query()
            ->where('name', AccesoIa::PERMISO)
            ->where('guard_name', 'web')
            ->exists();

        return Role::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get()
            ->map(function (Role $role) use ($limites, $permisoIa) {
                $limite = $limites->get($role->name);

                return [
                    'name' => $role->name,
                    'aracodito_ia' => $permisoIa && $role->hasPermissionTo(AccesoIa::PERMISO),
                    'daily_limit' => (int) ($limite->daily_limit ?? 0),
                    'monthly_budget_usd' => (float) ($limite->monthly_budget_usd ?? 0),
                ];
            })
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function formatear(Parameter $parameter): array
    {
        $isSecret = $parameter->control_type === 'pwd';
        $value = $parameter->value_default;

        if ($parameter->control_type === 'chx') {
            $value = in_array(strtolower(trim((string) $value)), ['1', 'true'], true) ? '1' : '0';
        }

        return [
            'code' => $parameter->parameter_code,
            'description' => $parameter->description,
            'value' => $isSecret ? null : $value,
            'is_secret' => $isSecret,
            'has_value' => trim((string) $parameter->value_default) !== '',
            'control_type' => $parameter->control_type,
        ];
    }

    private function descripcionPorDefecto(string $code): string
    {
        return match ($code) {
            'IA-00001' => 'API Key de OpenAI para las consultas de IA del sistema',
            'IA-00002' => 'Proveedor de inteligencia artificial para el chat (1 OpenAI, 2 Gemini)',
            'IA-00003' => 'ID del agente de OpenAI (Agents API) que responde las consultas',
            'IA-00004' => 'Espera máxima del agente de OpenAI, en minutos',
            'IA-00005' => 'Modelo de OpenAI que responde las consultas de IA',
            'IA-00006' => 'Modelo de Gemini que responde las consultas de IA',
            'IA-00007' => 'Presupuesto mensual de IA en dólares (0 = sin límite)',
            'IA-00008' => 'Consultas de IA permitidas por usuario y día (0 = sin límite)',
            'IA-00009' => 'Guardar el texto de las consultas de IA para auditoría',
            'IA-00010' => 'Máximo de tokens de salida por respuesta de IA',
            'IA-00011' => 'Temperatura de las respuestas de IA (0 a 2)',
            'IA-00012' => 'API Key de Gemini para las consultas de IA del sistema',
            default => 'Parámetro de inteligencia artificial',
        };
    }
}
