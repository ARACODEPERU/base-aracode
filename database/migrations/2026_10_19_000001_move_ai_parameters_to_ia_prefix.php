<?php

use App\Models\Parameter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OPENAI_KEY = 'IA-00001';

    private const AI_PROVIDER = 'IA-00002';

    private const OPENAI_AGENT_ID = 'IA-00003';

    private const LEGACY_PROVIDER = 'P000015';

    /**
     * P000025 pertenece a Ventas (modo de descuento). La migracion anterior de
     * OpenAI lo uso por error; aqui solo se usa como fuente si su valor es
     * realmente una key de OpenAI.
     */
    private const LEGACY_OPENAI_KEY = 'P000025';

    private const PROVIDER_OPTIONS = '[{"value":"1","label":"OpenAI"},{"value":"2","label":"Gemini AI"}]';

    public function up(): void
    {
        if (!Schema::hasTable('parameters')) {
            return;
        }

        $this->moveOpenAiKey();
        $this->moveProvider();
        $this->createAgentId();

        // El proveedor ya vive en IA-00002: se elimina el codigo viejo para que
        // no queden dos parametros configurando el mismo ajuste.
        Parameter::where('parameter_code', self::LEGACY_PROVIDER)->delete();
    }

    public function down(): void
    {
        if (!Schema::hasTable('parameters')) {
            return;
        }

        $provider = Parameter::where('parameter_code', self::AI_PROVIDER)->value('value_default');

        if ($provider !== null) {
            Parameter::updateOrCreate(
                ['parameter_code' => self::LEGACY_PROVIDER],
                [
                    'description' => 'Proveedor de inteligencia artificial, las configuraciones se realizan en el archivo .env de base-aracodeo server-socket nodejs',
                    'control_type' => 'sa',
                    'json_query_data' => self::PROVIDER_OPTIONS,
                    'value_default' => $provider,
                ]
            );
        }

        Parameter::whereIn('parameter_code', [
            self::OPENAI_KEY,
            self::AI_PROVIDER,
            self::OPENAI_AGENT_ID,
        ])->delete();
    }

    /**
     * IA-00001: API Key de OpenAI.
     *
     * Si una instalacion vieja guardo la key en P000025, se rescata el valor y
     * se devuelve P000025 a su valor de Ventas. Nunca se copia el "1"/"2" que
     * ese parametro usa para el modo de descuento.
     */
    private function moveOpenAiKey(): void
    {
        $legacyValue = trim((string) Parameter::where('parameter_code', self::LEGACY_OPENAI_KEY)->value('value_default'));
        $initialKey = null;

        if (str_starts_with($legacyValue, 'sk-')) {
            $initialKey = $legacyValue;

            Parameter::where('parameter_code', self::LEGACY_OPENAI_KEY)
                ->update(['value_default' => '2']);

            Log::warning('Se movio la API Key de OpenAI de P000025 a IA-00001 y se restauro P000025 para Ventas.');
        } else {
            $envKey = trim((string) config('academic.openai.api_key'));

            if ($envKey !== '') {
                $initialKey = $envKey;
            }
        }

        $this->saveParameter(
            self::OPENAI_KEY,
            [
                'description' => 'API Key de OpenAI para las consultas de IA del sistema',
                'control_type' => 'pwd',
                'json_query_data' => null,
            ],
            $initialKey,
            fillEmptyValue: true
        );
    }

    /**
     * IA-00002: proveedor del chat (1 OpenAI, 2 Gemini). Se conserva el valor
     * que tuviera P000015; el valor por defecto historico es Gemini (2).
     */
    private function moveProvider(): void
    {
        $legacyValue = trim((string) Parameter::where('parameter_code', self::LEGACY_PROVIDER)->value('value_default'));
        $initialValue = in_array($legacyValue, ['1', '2'], true) ? $legacyValue : '2';

        $this->saveParameter(
            self::AI_PROVIDER,
            [
                'description' => 'Proveedor de inteligencia artificial para el chat de consultas (1 OpenAI, 2 Gemini)',
                'control_type' => 'sa',
                'json_query_data' => self::PROVIDER_OPTIONS,
            ],
            $initialValue
        );
    }

    /**
     * IA-00003: ID del agente guardado de OpenAI. El valor del .env se copia
     * solo la primera vez; despues se administra desde Parametros del sistema.
     */
    private function createAgentId(): void
    {
        $agentId = trim((string) config('academic.openai.agent_id'));

        $this->saveParameter(
            self::OPENAI_AGENT_ID,
            [
                'description' => 'ID del agente de OpenAI (Agents API, formato agent_...) que responde las consultas de los alumnos',
                'control_type' => 'tx',
                'json_query_data' => null,
            ],
            $agentId !== '' ? $agentId : null,
            fillEmptyValue: true
        );
    }

    /**
     * Crea o completa un parametro sin pisar un valor ya cargado por el
     * administrador. Los atributos de presentacion si se normalizan para que
     * una fila vieja (por ejemplo, una key visible como 'tx') quede correcta.
     */
    private function saveParameter(
        string $code,
        array $attributes,
        ?string $initialValue,
        bool $fillEmptyValue = false
    ): void {
        $parameter = Parameter::firstOrNew(['parameter_code' => $code]);

        $parameter->fill($attributes);

        if (!$parameter->exists) {
            $parameter->value_default = $initialValue;
        } elseif ($fillEmptyValue && trim((string) $parameter->value_default) === '' && $initialValue !== null) {
            $parameter->value_default = $initialValue;
        }

        $parameter->save();
    }
};
