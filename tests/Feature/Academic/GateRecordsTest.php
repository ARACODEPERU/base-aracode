<?php

namespace Tests\Feature\Academic;

use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Academic\Entities\AcaSchool;
use Modules\Academic\Entities\AcaSchoolEnrollment;
use Modules\Academic\Entities\AcaSchoolGateAttendance;
use Modules\Academic\Entities\AcaSchoolGrade;
use Modules\Academic\Entities\AcaSchoolLevel;
use Modules\Academic\Entities\AcaSchoolSection;
use Modules\Academic\Entities\AcaSchoolStudent;
use Modules\Academic\Entities\AcaSchoolYear;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Listado histórico de portería: es la vista donde se consultan todas las
 * asistencias institucionales, con filtros por movimiento, estructura
 * académica y búsqueda por alumno (nombre, DNI o código).
 */
class GateRecordsTest extends TestCase
{
    use RefreshDatabase;

    private AcaSchool $school;

    private AcaSchoolYear $year;

    private AcaSchoolSection $sectionA;

    private AcaSchoolSection $sectionB;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = AcaSchool::defaultSchool();
        $this->school->update(['name' => 'Colegio Semilla', 'status' => true]);

        $this->year = AcaSchoolYear::create([
            'school_id' => $this->school->id,
            'year' => 2027,
            'status' => AcaSchoolYear::STATUS_ACTIVE,
        ]);

        $level = AcaSchoolLevel::create([
            'school_id' => $this->school->id,
            'year_id' => $this->year->id,
            'code' => AcaSchoolLevel::PRIMARIA,
            'name' => 'Primaria',
            'status' => true,
        ]);

        $grade = AcaSchoolGrade::create([
            'school_id' => $this->school->id,
            'year_id' => $this->year->id,
            'level_id' => $level->id,
            'name' => '3°',
            'status' => true,
        ]);

        $this->sectionA = AcaSchoolSection::create([
            'school_id' => $this->school->id,
            'year_id' => $this->year->id,
            'grade_id' => $grade->id,
            'name' => 'A',
            'capacity' => 30,
            'shift' => AcaSchoolSection::SHIFT_MANANA,
            'status' => true,
        ]);

        $this->sectionB = AcaSchoolSection::create([
            'school_id' => $this->school->id,
            'year_id' => $this->year->id,
            'grade_id' => $grade->id,
            'name' => 'B',
            'capacity' => 30,
            'shift' => AcaSchoolSection::SHIFT_MANANA,
            'status' => true,
        ]);

        // Lucía entró y salió; Mateo entró y sigue dentro con tardanza.
        $this->createGateRow('Lucía', 'Quispe Ramos', '87654321', '2027-0001', $this->sectionA, [
            'entry_at' => now()->setTime(7, 45),
            'entry_status' => AcaSchoolGateAttendance::ENTRY_ASISTENCIA,
            'exit_at' => now()->setTime(13, 10),
        ]);

        $this->createGateRow('Mateo', 'Ríos Paredes', '12345678', '2027-0002', $this->sectionB, [
            'entry_at' => now()->setTime(8, 20),
            'entry_status' => AcaSchoolGateAttendance::ENTRY_TARDANZA,
        ]);

        $this->user = $this->userWithPermission('aca_school_porteria_reporte');
    }

    public function test_el_listado_muestra_los_registros_con_su_resumen(): void
    {
        $this->actingAs($this->user)
            ->get(route('aca_school_gate_records'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                // El resolvedor de Inertia no busca vistas dentro de Modules/.
                ->component('Academic::School/Gate/Records', false)
                ->has('records.data', 2)
                ->where('summary.total', 2)
                ->where('summary.entries', 2)
                ->where('summary.exits', 1)
                ->where('summary.inside', 1)
                ->where('summary.late', 1));
    }

    public function test_filtra_por_entradas_y_salidas(): void
    {
        $this->actingAs($this->user)
            ->get(route('aca_school_gate_records', ['event' => 'out']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('records.data', 1)
                ->where('records.data.0.student', 'Quispe Ramos Lucía')
                ->where('records.data.0.exit_time', '13:10'));

        $this->actingAs($this->user)
            ->get(route('aca_school_gate_records', ['event' => 'in']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('records.data', 2));
    }

    public function test_filtra_por_seccion(): void
    {
        $this->actingAs($this->user)
            ->get(route('aca_school_gate_records', ['section_id' => $this->sectionB->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('records.data', 1)
                ->where('records.data.0.student', 'Ríos Paredes Mateo'));
    }

    public function test_busca_por_nombre_dni_y_codigo(): void
    {
        foreach (['Ríos', '12345678', '2027-0002'] as $term) {
            $this->actingAs($this->user)
                ->get(route('aca_school_gate_records', ['search' => $term]))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->has('records.data', 1)
                    ->where('records.data.0.student', 'Ríos Paredes Mateo'));
        }
    }

    public function test_filtra_por_rango_de_fechas(): void
    {
        $this->actingAs($this->user)
            ->get(route('aca_school_gate_records', ['date_from' => now()->addDay()->toDateString()]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('records.data', 0));
    }

    public function test_sin_permiso_de_reporte_no_puede_ver_el_listado(): void
    {
        $sinPermiso = $this->userWithPermission('aca_school_porteria_escaner');

        $this->actingAs($sinPermiso)
            ->get(route('aca_school_gate_records'))
            ->assertForbidden();
    }

    /**
     * Crea alumno, matrícula y su fila de portería del día.
     *
     * @param array<string, mixed> $values
     */
    private function createGateRow(
        string $names,
        string $lastnames,
        string $dni,
        string $code,
        AcaSchoolSection $section,
        array $values
    ): AcaSchoolGateAttendance {
        $person = Person::create([
            'names' => $names,
            'father_lastname' => $lastnames,
            'mother_lastname' => 'Prueba',
            'full_name' => $lastnames.' '.$names,
            'number' => $dni,
        ]);

        $student = AcaSchoolStudent::create([
            'school_id' => $this->school->id,
            'person_id' => $person->id,
            'student_code' => $code,
            'status' => true,
        ]);

        $enrollment = AcaSchoolEnrollment::create([
            'school_id' => $this->school->id,
            'year_id' => $this->year->id,
            'student_id' => $student->id,
            'section_id' => $section->id,
            'type' => AcaSchoolEnrollment::TYPE_NUEVA,
            'status' => AcaSchoolEnrollment::STATUS_ACTIVO,
            'enrollment_date' => now()->toDateString(),
        ]);

        return AcaSchoolGateAttendance::create(array_merge([
            'school_id' => $this->school->id,
            'year_id' => $this->year->id,
            'section_id' => $section->id,
            'enrollment_id' => $enrollment->id,
            'student_id' => $student->id,
            'attendance_date' => now()->toDateString(),
            'scans_count' => 1,
        ], $values));
    }

    private function userWithPermission(string ...$permissions): User
    {
        static $sequence = 0;
        $sequence++;

        $person = Person::create([
            'number' => (string) (91000000 + $sequence),
            'names' => 'Secretaría',
            'father_lastname' => 'Prueba',
            'mother_lastname' => 'Colegio',
            'full_name' => 'Secretaría Prueba Colegio '.$sequence,
        ]);

        $user = User::factory()->create(['person_id' => $person->id]);

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $user->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }
}
