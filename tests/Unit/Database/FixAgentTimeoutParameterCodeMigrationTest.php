<?php

namespace Tests\Unit\Database;

use App\Models\Parameter;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * La migracion 2026_10_21_000001 creo el parametro con el codigo IA-0000, que
 * no sigue la secuencia de los parametros de IA. Esta correccion lo deja en
 * IA-00004 (el siguiente libre despues de IA-00001..IA-00003) sin perder el
 * valor configurado en produccion y sin duplicar filas.
 */
class FixAgentTimeoutParameterCodeMigrationTest extends TestCase
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

    public function test_renombra_ia_0000_a_ia_00004_conservando_el_valor_de_produccion(): void
    {
        $this->parameter('IA-00001', 'sk-test');
        $this->parameter('IA-00002', '1');
        $this->parameter('IA-00003', 'agent_test');
        $this->parameter('IA-0000', '10');

        $this->migration()->up();

        $parameter = $this->parameterRow('IA-00004');

        $this->assertNotNull($parameter);
        $this->assertSame('10', (string) $parameter->value_default);
        $this->assertSame('tx', $parameter->control_type);
        $this->assertStringContainsString('agente', $parameter->description);
        $this->assertStringContainsString('minutos', $parameter->description);
        $this->assertNull($this->parameterRow('IA-0000'));
        $this->assertSame(1, Parameter::whereIn('parameter_code', ['IA-0000', 'IA-00004'])->count());
        // Los demas parametros de IA no se tocan.
        $this->assertSame('agent_test', (string) $this->parameterRow('IA-00003')->value_default);
    }

    public function test_crea_ia_00004_con_seis_minutos_cuando_no_existe_ninguno(): void
    {
        $this->migration()->up();

        $this->assertSame('6', (string) $this->parameterRow('IA-00004')->value_default);
        $this->assertNull($this->parameterRow('IA-0000'));
    }

    public function test_completa_el_valor_vacio_con_el_valor_del_codigo_viejo(): void
    {
        $this->parameter('IA-00004', '');
        $this->parameter('IA-0000', '8');

        $this->migration()->up();

        $this->assertSame('8', (string) $this->parameterRow('IA-00004')->value_default);
        $this->assertNull($this->parameterRow('IA-0000'));
    }

    public function test_no_pisa_ia_00004_si_ya_tiene_valor_y_elimina_el_duplicado(): void
    {
        $this->parameter('IA-00004', '12');
        $this->parameter('IA-0000', '8');

        $this->migration()->up();

        $this->assertSame('12', (string) $this->parameterRow('IA-00004')->value_default);
        $this->assertNull($this->parameterRow('IA-0000'));
        $this->assertSame(1, Parameter::whereIn('parameter_code', ['IA-0000', 'IA-00004'])->count());
    }

    public function test_es_idempotente(): void
    {
        $this->parameter('IA-0000', '10');

        $migration = $this->migration();
        $migration->up();
        $migration->up();

        $this->assertSame(1, Parameter::where('parameter_code', 'IA-00004')->count());
        $this->assertSame('10', (string) $this->parameterRow('IA-00004')->value_default);
    }

    public function test_down_devuelve_el_codigo_anterior(): void
    {
        $this->parameter('IA-0000', '10');

        $migration = $this->migration();
        $migration->up();
        $migration->down();

        $this->assertNull($this->parameterRow('IA-00004'));
        $this->assertSame('10', (string) $this->parameterRow('IA-0000')->value_default);
    }

    public function test_down_no_renombra_otro_parametro_con_el_mismo_codigo(): void
    {
        $this->parameter('IA-00004', '5', 'Otro parametro del sistema');

        $this->migration()->down();

        $this->assertSame('5', (string) $this->parameterRow('IA-00004')->value_default);
        $this->assertNull($this->parameterRow('IA-0000'));
    }

    private function migration(): object
    {
        return require base_path('database/migrations/2026_10_21_000002_fix_agent_timeout_parameter_code.php');
    }

    private function parameter(string $code, string $value, string $description = 'Parametro de prueba'): Parameter
    {
        return Parameter::create([
            'parameter_code' => $code,
            'description' => $description,
            'control_type' => 'tx',
            'json_query_data' => null,
            'value_default' => $value,
        ]);
    }

    private function parameterRow(string $code): ?Parameter
    {
        return Parameter::where('parameter_code', $code)->first();
    }
}
