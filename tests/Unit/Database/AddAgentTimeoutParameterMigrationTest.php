<?php

namespace Tests\Unit\Database;

use App\Models\Parameter;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Contrato del parametro IA-0000: guarda en minutos la espera maxima de la
 * respuesta del agente de OpenAI. El valor inicial es 6 minutos y la migracion
 * nunca pisa un valor que el administrador ya haya guardado.
 */
class AddAgentTimeoutParameterMigrationTest extends TestCase
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

    public function test_crea_ia_0000_con_seis_minutos_por_defecto(): void
    {
        config(['academic.openai.agent_timeout_minutes' => 6]);

        $this->migration()->up();

        $parameter = $this->parameterRow('IA-0000');

        $this->assertNotNull($parameter);
        $this->assertSame('6', (string) $parameter->value_default);
        $this->assertSame('tx', $parameter->control_type);
        $this->assertStringContainsString('minutos', $parameter->description);
    }

    public function test_no_pisa_un_valor_ya_configurado(): void
    {
        config(['academic.openai.agent_timeout_minutes' => 6]);

        Parameter::create([
            'parameter_code' => 'IA-0000',
            'description' => 'Valor manual',
            'control_type' => 'tx',
            'json_query_data' => null,
            'value_default' => '10',
        ]);

        $this->migration()->up();

        $this->assertSame('10', (string) $this->parameterRow('IA-0000')->value_default);
        $this->assertSame(1, Parameter::where('parameter_code', 'IA-0000')->count());
    }

    public function test_es_idempotente_y_down_elimina_el_parametro(): void
    {
        $migration = $this->migration();

        $migration->up();
        $migration->up();

        $this->assertSame(1, Parameter::where('parameter_code', 'IA-0000')->count());

        $migration->down();

        $this->assertNull($this->parameterRow('IA-0000'));
    }

    private function migration(): object
    {
        return require base_path('database/migrations/2026_10_21_000001_add_agent_timeout_parameter.php');
    }

    private function parameterRow(string $code): ?Parameter
    {
        return Parameter::where('parameter_code', $code)->first();
    }
}
