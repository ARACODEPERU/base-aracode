<?php

namespace Tests\Feature\Health;

use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Health\Entities\HealActivity;
use Modules\Health\Entities\HealPatient;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Alta, edición y baja de pacientes del módulo Salud.
 *
 * La baja debe ser lógica y anonimizar los datos personales (no borrar la
 * historia clínica) y toda mutación queda registrada en heal_activities.
 */
class HealPatientManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->usuario = User::factory()->create(['email_verified_at' => now()]);

        foreach ([
            'heal_pacientes_listado',
            'heal_pacientes_nuevo',
            'heal_pacientes_editar',
            'heal_pacientes_eliminar',
        ] as $permiso) {
            Permission::findOrCreate($permiso, 'web');
            $this->usuario->givePermissionTo($permiso);
        }

        $this->actingAs($this->usuario);
    }

    public function test_la_edicion_devuelve_una_respuesta_inertia_valida(): void
    {
        [$personId, $patientId] = $this->crearPaciente();

        $response = $this->post(route('heal_patients_update'), [
            'id' => $personId,
            'document_type_id' => '1',
            'number' => '87654321',
            'telephone' => '987987987',
            'email' => 'paciente.editado@example.com',
            'address' => 'Av. Nueva 456',
            'ubigeo' => '150101',
            'birthdate' => '1990-05-10',
            'names' => 'María',
            'father_lastname' => 'Quispe',
            'mother_lastname' => 'Rojas',
            'gender' => 'F',
        ]);

        $response->assertRedirect(route('heal_patients_edit', $personId));

        $persona = Person::findOrFail($personId);
        $this->assertSame('87654321', $persona->number);
        $this->assertSame('María', $persona->names);

        $paciente = HealPatient::findOrFail($patientId);
        $this->assertSame('87654321', $paciente->patient_code);
    }

    public function test_no_se_puede_registrar_un_documento_repetido(): void
    {
        $this->crearPaciente(['number' => '12345678']);

        $response = $this->from(route('heal_patients_create'))->post(route('heal_patients_store'), [
            'document_type_id' => '1',
            'number' => '12345678',
            'telephone' => '987987987',
            'email' => 'otro.paciente@example.com',
            'address' => 'Av. Otra 789',
            'ubigeo' => '150101',
            'birthdate' => '1985-01-01',
            'names' => 'Otro',
            'father_lastname' => 'Paciente',
            'mother_lastname' => 'Nuevo',
            'gender' => 'M',
        ]);

        $response->assertSessionHasErrors('number');
        $this->assertSame(1, Person::where('number', '12345678')->count());
    }

    public function test_la_baja_es_logica_anonimiza_y_queda_auditada(): void
    {
        [$personId, $patientId] = $this->crearPaciente([
            'full_name' => 'Juan Pérez Gómez',
            'number' => '11223344',
            'email' => 'juan.perez@example.com',
            'telephone' => '987111222',
            'address' => 'Calle Falsa 123',
        ]);

        $response = $this->delete(route('heal_patients_destroy', $patientId));

        $response->assertRedirect(route('heal_patients_list'));

        // La fila del paciente se conserva para auditoría clínica.
        $this->assertSoftDeleted('heal_patients', ['id' => $patientId]);

        $persona = Person::findOrFail($personId);
        $this->assertSame('Paciente', $persona->names);
        $this->assertSame('Anonimizado', $persona->father_lastname);
        $this->assertSame('ANON' . $personId, $persona->number);
        $this->assertNull($persona->telephone);
        $this->assertNull($persona->address);
        $this->assertStringEndsWith('@anonimo.local', $persona->email);
        $this->assertStringNotContainsString('Juan', $persona->full_name);

        // Trazabilidad: quién dio la baja y que se anonimizó.
        $anonimizacion = HealActivity::where('activity_type', 'anonymized')->firstOrFail();
        $this->assertSame($this->usuario->id, (int) $anonimizacion->actor_id);
        $this->assertSame($patientId, (int) $anonimizacion->patient_id);
        $this->assertSame(HealPatient::class, $anonimizacion->subject_type);
        $this->assertContains('number', $anonimizacion->metadata['campos_retirados']);

        $this->assertTrue(
            HealActivity::where('activity_type', 'deleted')
                ->where('subject_id', $patientId)
                ->exists(),
            'La baja del paciente debe quedar en heal_activities.'
        );
    }

    /**
     * @return array{0:int,1:int}
     */
    private function crearPaciente(array $persona = []): array
    {
        $personId = DB::table('people')->insertGetId(array_merge([
            'document_type_id' => '1',
            'short_name' => 'Juan',
            'names' => 'Juan',
            'father_lastname' => 'Pérez',
            'mother_lastname' => 'Gómez',
            'full_name' => 'Pérez Gómez Juan',
            'description' => 'Paciente',
            'number' => '11223344',
            'telephone' => '987111222',
            'email' => 'juan.perez@example.com',
            'address' => 'Calle Falsa 123',
            'ubigeo' => '150101',
            'birthdate' => '1990-01-01',
            'is_client' => true,
            'is_provider' => false,
            'gender' => 'M',
            'status' => true,
            'country_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], $persona));

        $patientId = DB::table('heal_patients')->insertGetId([
            'person_id' => $personId,
            'patient_code' => $persona['number'] ?? '11223344',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [(int) $personId, (int) $patientId];
    }
}
