<?php

namespace Tests\Unit\Database;

use App\Models\Parameter;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Contrato de la migracion que ordena la configuracion de IA bajo el prefijo
 * IA-0000:
 *
 * - IA-00001: API Key de OpenAI (confidencial).
 * - IA-00002: proveedor del chat (1 OpenAI, 2 Gemini).
 * - IA-00003: ID del agente guardado de OpenAI.
 *
 * La prueba importante es que P000025 pertenece a Ventas (modo de descuento):
 * solo se rescata como key si su valor realmente empieza con "sk-".
 */
class MoveAiParametersToIaPrefixMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('parameters');
        Schema::create('parameters', function (Blueprint $table) {
            $table->id();
            $table->string('parameter_code')->unique();
            $table->string('description')->nullable();
            $table->string('control_type')->nullable();
            $table->text('json_query_data')->nullable();
            $table->text('value_default')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('parameters');

        parent::tearDown();
    }

    public function test_mueve_la_configuracion_y_rescata_una_key_guardada_en_p000025(): void
    {
        config([
            'academic.openai.api_key' => '',
            'academic.openai.agent_id' => '',
        ]);

        $this->parameter('P000015', '1');
        $this->parameter('P000025', 'sk-legacy-key');

        $this->migration()->up();

        $openAiKey = $this->parameterRow('IA-00001');
        $this->assertSame('sk-legacy-key', $openAiKey->value_default);
        $this->assertSame('pwd', $openAiKey->control_type);

        $provider = $this->parameterRow('IA-00002');
        $this->assertSame('1', $provider->value_default);
        $this->assertSame('sa', $provider->control_type);

        $this->assertNotNull($this->parameterRow('IA-00003'));
        $this->assertSame('tx', $this->parameterRow('IA-00003')->control_type);

        $this->assertNull($this->parameterRow('P000015'), 'El codigo viejo del proveedor debe desaparecer.');
        $this->assertSame('2', $this->parameterRow('P000025')->value_default, 'Ventas recupera su valor por defecto.');
    }

    public function test_no_copia_el_modo_de_descuento_de_ventas_como_api_key(): void
    {
        config([
            'academic.openai.api_key' => '',
            'academic.openai.agent_id' => '',
        ]);

        $this->parameter('P000015', '2');
        $this->parameter('P000025', '2');

        $this->migration()->up();

        $this->assertEmpty($this->parameterRow('IA-00001')->value_default);
        $this->assertSame('2', $this->parameterRow('P000025')->value_default);
        $this->assertSame('2', $this->parameterRow('IA-00002')->value_default);
    }

    public function test_usa_los_valores_iniciales_del_env_y_no_pisa_un_valor_existente(): void
    {
        config([
            'academic.openai.api_key' => 'sk-env-key',
            'academic.openai.agent_id' => 'agent_env',
        ]);

        $this->parameter('IA-00001', 'sk-manual-key', 'tx');

        $this->migration()->up();

        $openAiKey = $this->parameterRow('IA-00001');
        $this->assertSame('sk-manual-key', $openAiKey->value_default, 'No debe pisar la key ya guardada.');
        $this->assertSame('pwd', $openAiKey->control_type, 'La key debe quedar como confidencial.');

        $this->assertSame('agent_env', $this->parameterRow('IA-00003')->value_default);
        $this->assertSame('2', $this->parameterRow('IA-00002')->value_default);
    }

    public function test_es_idempotente(): void
    {
        config([
            'academic.openai.api_key' => '',
            'academic.openai.agent_id' => '',
        ]);

        $this->parameter('P000015', '1');
        $this->parameter('P000025', '2');

        $this->migration()->up();
        Parameter::where('parameter_code', 'IA-00002')->update(['value_default' => '1']);
        $this->migration()->up();

        $this->assertSame(3, Parameter::whereIn('parameter_code', [
            'IA-00001', 'IA-00002', 'IA-00003',
        ])->count());

        $this->assertSame('1', $this->parameterRow('IA-00002')->value_default);
        $this->assertNull($this->parameterRow('P000015'));
    }

    public function test_down_restaura_el_proveedor_y_no_toca_ventas(): void
    {
        config([
            'academic.openai.api_key' => '',
            'academic.openai.agent_id' => '',
        ]);

        $this->parameter('P000015', '1');
        $this->parameter('P000025', '2');

        $migration = $this->migration();
        $migration->up();
        $migration->down();

        $this->assertSame('1', $this->parameterRow('P000015')->value_default);
        $this->assertNull($this->parameterRow('IA-00001'));
        $this->assertNull($this->parameterRow('IA-00002'));
        $this->assertNull($this->parameterRow('IA-00003'));
        $this->assertSame('2', $this->parameterRow('P000025')->value_default);
    }

    private function migration(): object
    {
        return require base_path('database/migrations/2026_10_19_000001_move_ai_parameters_to_ia_prefix.php');
    }

    private function parameter(string $code, string $value, string $controlType = 'tx'): Parameter
    {
        return Parameter::create([
            'parameter_code' => $code,
            'description' => 'Parametro de prueba',
            'control_type' => $controlType,
            'json_query_data' => null,
            'value_default' => $value,
        ]);
    }

    private function parameterRow(string $code): ?Parameter
    {
        return Parameter::where('parameter_code', $code)->first();
    }
}
