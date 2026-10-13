<?php

namespace Tests\Unit\Modules\Academic\Database;

use App\Models\Parameter;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Contrato de la migración de parámetros del canal SMS de asistencia.
 *
 * La promesa: crea los doce parámetros del canal (SC-00020 a SC-00031) con los
 * tipos correctos, deja la contraseña como confidencial (pwd) para que la
 * pantalla de Parámetros del sistema no la muestre, es idempotente y no pisa un
 * valor ya cargado por el administrador.
 */
class AcaSchoolNoticeParametersMigrationTest extends TestCase
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

    public function test_crea_los_parametros_del_canal_con_sus_tipos(): void
    {
        $this->migration()->up();

        $this->assertSame(12, Parameter::count());

        $this->assertDatabaseHas('parameters', ['parameter_code' => 'SC-00020', 'control_type' => 'tx']);
        $this->assertDatabaseHas('parameters', ['parameter_code' => 'SC-00021', 'control_type' => 'tx']);
        // La contraseña es confidencial: no se vuelve a mostrar en pantalla.
        $this->assertDatabaseHas('parameters', ['parameter_code' => 'SC-00022', 'control_type' => 'pwd']);

        $this->assertDatabaseHas('parameters', ['parameter_code' => 'SC-00023', 'control_type' => 'chx', 'value_default' => '0']);
        $this->assertDatabaseHas('parameters', ['parameter_code' => 'SC-00024', 'control_type' => 'chx', 'value_default' => '1']);
        $this->assertDatabaseHas('parameters', ['parameter_code' => 'SC-00025', 'control_type' => 'chx', 'value_default' => '1']);
        $this->assertDatabaseHas('parameters', ['parameter_code' => 'SC-00030', 'control_type' => 'chx', 'value_default' => '0']);
        $this->assertDatabaseHas('parameters', ['parameter_code' => 'SC-00031', 'control_type' => 'tx', 'value_default' => '0']);

        // Las plantillas por defecto usan las variables del aviso.
        $this->assertStringContainsString('{alumno}', (string) Parameter::where('parameter_code', 'SC-00028')->value('value_default'));
        $this->assertStringContainsString('{alumno}', (string) Parameter::where('parameter_code', 'SC-00029')->value('value_default'));
    }

    public function test_es_idempotente_y_no_pisa_los_valores_cargados(): void
    {
        $this->migration()->up();

        Parameter::where('parameter_code', 'SC-00021')->update(['value_default' => 'cuenta-colegio']);

        $this->migration()->up();

        $this->assertSame(12, Parameter::count());
        $this->assertSame('cuenta-colegio', Parameter::where('parameter_code', 'SC-00021')->value('value_default'));
    }

    public function test_down_elimina_solo_las_filas_propias(): void
    {
        Parameter::create([
            'parameter_code' => 'SC-00001',
            'description' => 'Vonage',
            'control_type' => 'pwd',
            'value_default' => 'x',
        ]);

        $this->migration()->up();
        $this->assertSame(13, Parameter::count());

        $this->migration()->down();

        $this->assertSame(1, Parameter::count());
        $this->assertDatabaseHas('parameters', ['parameter_code' => 'SC-00001']);
    }

    private function migration(): object
    {
        return require base_path('Modules/Academic/Database/Migrations/2026_10_12_000001_create_aca_school_notice_parameters.php');
    }
}
