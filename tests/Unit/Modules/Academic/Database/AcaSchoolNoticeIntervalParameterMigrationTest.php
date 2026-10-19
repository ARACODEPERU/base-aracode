<?php

namespace Tests\Unit\Modules\Academic\Database;

use App\Models\Parameter;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Contrato de la migración del intervalo anti-SPAM (SC-00032).
 *
 * La promesa: crea el parámetro de la pausa mínima entre mensajes SMS con
 * 2000 ms por defecto, es idempotente y no pisa un valor ya cargado por el
 * administrador.
 */
class AcaSchoolNoticeIntervalParameterMigrationTest extends TestCase
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

    public function test_crea_el_parametro_con_2000_ms_por_defecto(): void
    {
        $this->migration()->up();

        $this->assertDatabaseHas('parameters', [
            'parameter_code' => 'SC-00032',
            'control_type' => 'tx',
            'value_default' => '2000',
        ]);
    }

    public function test_es_idempotente_y_no_pisa_el_valor_cargado(): void
    {
        $this->migration()->up();

        Parameter::where('parameter_code', 'SC-00032')->update(['value_default' => '5000']);

        $this->migration()->up();

        $this->assertSame(1, Parameter::where('parameter_code', 'SC-00032')->count());
        $this->assertSame('5000', Parameter::where('parameter_code', 'SC-00032')->value('value_default'));
    }

    public function test_down_elimina_solo_la_fila_propia(): void
    {
        Parameter::create([
            'parameter_code' => 'SC-00001',
            'description' => 'Vonage',
            'control_type' => 'pwd',
            'value_default' => 'x',
        ]);

        $this->migration()->up();
        $this->assertSame(2, Parameter::count());

        $this->migration()->down();

        $this->assertSame(1, Parameter::count());
        $this->assertDatabaseHas('parameters', ['parameter_code' => 'SC-00001']);
    }

    private function migration(): object
    {
        return require base_path('Modules/Academic/Database/Migrations/2026_10_14_000001_add_aca_school_notice_interval_parameter.php');
    }
}
