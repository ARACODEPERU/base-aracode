<?php

namespace Tests\Feature\Academic;

use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Modules\Academic\Entities\AcaSchool;
use Modules\Academic\Entities\AcaSchoolArea;
use Modules\Academic\Entities\AcaSchoolEnrollment;
use Modules\Academic\Entities\AcaSchoolGrade;
use Modules\Academic\Entities\AcaSchoolLevel;
use Modules\Academic\Entities\AcaSchoolSection;
use Modules\Academic\Entities\AcaSchoolStudent;
use Modules\Academic\Entities\AcaSchoolYear;
use Modules\Academic\Entities\AcaVirtualActivity;
use Modules\Academic\Entities\AcaVirtualCourse;
use Modules\Academic\Entities\AcaVirtualLesson;
use Modules\Academic\Entities\AcaVirtualResource;
use Modules\Academic\Entities\AcaVirtualUnit;
use Tests\TestCase;

/**
 * Portal del alumno de Secundaria: el login compartido lo reconoce y lo lleva
 * a sus propias vistas, donde ve el contenido que publicó su docente.
 */
class ColegioSecundariaPortalTest extends TestCase
{
    use RefreshDatabase;

    private AcaSchool $school;

    private AcaSchoolYear $year;

    private AcaSchoolSection $section;

    private AcaSchoolStudent $student;

    private User $user;

    private AcaVirtualCourse $course;

    private AcaVirtualUnit $unit;

    private AcaVirtualLesson $lesson;

    private AcaVirtualResource $resource;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = AcaSchool::defaultSchool();
        $this->school->update(['name' => 'Colegio Horizonte', 'status' => true]);

        $this->year = AcaSchoolYear::create([
            'school_id' => $this->school->id,
            'year' => 2027,
            'status' => AcaSchoolYear::STATUS_ACTIVE,
        ]);

        $level = AcaSchoolLevel::create([
            'school_id' => $this->school->id,
            'year_id' => $this->year->id,
            'code' => AcaSchoolLevel::SECUNDARIA,
            'name' => 'Secundaria',
            'status' => true,
        ]);

        $grade = AcaSchoolGrade::create([
            'school_id' => $this->school->id,
            'year_id' => $this->year->id,
            'level_id' => $level->id,
            'name' => '3°',
            'status' => true,
        ]);

        $this->section = AcaSchoolSection::create([
            'school_id' => $this->school->id,
            'year_id' => $this->year->id,
            'grade_id' => $grade->id,
            'name' => 'A',
            'capacity' => 30,
            'shift' => AcaSchoolSection::SHIFT_MANANA,
            'status' => true,
        ]);

        [$this->student, $this->user] = $this->makeStudent('Diego', 'Salazar', '76543210', 'diego@colegio.test');

        AcaSchoolEnrollment::create([
            'school_id' => $this->school->id,
            'year_id' => $this->year->id,
            'student_id' => $this->student->id,
            'section_id' => $this->section->id,
            'type' => AcaSchoolEnrollment::TYPE_NUEVA,
            'status' => AcaSchoolEnrollment::STATUS_ACTIVO,
            'enrollment_date' => now()->toDateString(),
        ]);

        $area = AcaSchoolArea::create([
            'school_id' => $this->school->id,
            'name' => 'Ciencia y Tecnología',
            'level' => AcaSchoolArea::LEVEL_SECUNDARIA,
            'sort_order' => 1,
            'status' => true,
        ]);

        $this->course = AcaVirtualCourse::create([
            'school_id' => $this->school->id,
            'year_id' => $this->year->id,
            'section_id' => $this->section->id,
            'area_id' => $area->id,
            'name' => null,
            'status' => true,
            'sort_order' => 1,
        ]);

        $this->unit = AcaVirtualUnit::create([
            'course_id' => $this->course->id,
            'title' => 'Energía en movimiento',
            'purpose' => 'Explicar las transformaciones de energía en su entorno.',
            'significant_situation' => 'La comunidad necesita reducir el consumo eléctrico del colegio.',
            'duration_hours' => 6,
            'sort_order' => 1,
            'status' => true,
        ]);

        $this->lesson = AcaVirtualLesson::create([
            'unit_id' => $this->unit->id,
            'title' => 'Circuitos y energía',
            'purpose' => 'Identificar los componentes de un circuito simple.',
            'development' => 'Armamos un circuito con una pila, cables y un foco.',
            'duration_minutes' => 90,
            'sort_order' => 1,
            'status' => true,
        ]);

        Storage::fake('public');
        Storage::disk('public')->put('materiales/guia.pdf', '%PDF-1.4');

        $this->resource = AcaVirtualResource::create([
            'course_id' => $this->course->id,
            'unit_id' => $this->unit->id,
            'lesson_id' => $this->lesson->id,
            'type' => 'file',
            'title' => 'Guía de laboratorio',
            'path' => 'materiales/guia.pdf',
            'mime_type' => 'application/pdf',
            'size_kb' => 512,
            'visibility' => 'students',
            'sort_order' => 1,
            'status' => true,
        ]);

        AcaVirtualActivity::create([
            'course_id' => $this->course->id,
            'unit_id' => $this->unit->id,
            'lesson_id' => $this->lesson->id,
            'type' => 'quiz',
            'title' => 'Práctica de laboratorio',
            'instructions' => 'Describe el circuito que armaste y qué ocurrió al cerrarlo.',
            'max_score' => 20,
            'grading_scale' => 'numeric',
            'sort_order' => 1,
            'status' => true,
        ]);
    }

    public function test_el_alumno_de_secundaria_entra_y_es_dirigido_a_su_aula(): void
    {
        $response = $this->post(route('colegio.aula.login.store'), [
            'codigo' => $this->student->student_code,
            'clave' => 'secundaria123',
        ]);

        $response->assertRedirect(route('colegio.secundaria.dashboard'));
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_el_acceso_del_aula_tambien_lo_dirige_a_sus_vistas(): void
    {
        $this->actingAs($this->user)
            ->get(route('colegio.aula.login'))
            ->assertRedirect(route('colegio.secundaria.dashboard'));
    }

    public function test_la_pantalla_de_acceso_de_secundaria_tiene_su_propio_diseno(): void
    {
        $this->get(route('colegio.secundaria.login'))
            ->assertOk()
            ->assertSee('Tu siguiente nivel empieza aquí.')
            ->assertSee('Acceso de estudiantes')
            ->assertSee('Aula Virtual · Secundaria')
            ->assertSee(route('colegio.secundaria.login.store'), false);
    }

    public function test_el_alumno_de_secundaria_entra_desde_su_propio_acceso(): void
    {
        $response = $this->from(route('colegio.secundaria.login'))->post(route('colegio.secundaria.login.store'), [
            'codigo' => $this->student->student_code,
            'clave' => 'secundaria123',
        ]);

        $response->assertRedirect(route('colegio.secundaria.dashboard'));
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_un_alumno_de_primaria_no_entra_por_el_acceso_de_secundaria(): void
    {
        [$student] = $this->makePrimariaStudent();

        $response = $this->from(route('colegio.secundaria.login'))->post(route('colegio.secundaria.login.store'), [
            'codigo' => $student->student_code,
            'clave' => 'primaria123',
        ]);

        $response->assertRedirect(route('colegio.secundaria.login'))
            ->assertSessionHasErrors('codigo');

        $this->assertGuest();
        $this->assertStringContainsString(
            'exclusivo de Secundaria',
            session('errors')->first('codigo')
        );
    }

    public function test_el_panel_muestra_sus_cursos_y_su_progreso(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('colegio.secundaria.dashboard'));

        $response->assertOk()
            ->assertSee('Aula virtual · Secundaria')
            ->assertSee('Diego')
            ->assertSee('Ciencia y Tecnología')
            ->assertSee('3° · Aula A')
            ->assertSee('Mis cursos')
            ->assertSee('Progreso general')
            ->assertSee('0 de 1 actividades');
    }

    public function test_el_curso_muestra_unidades_sesiones_materiales_y_actividades(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('colegio.secundaria.course', $this->course->id));

        $response->assertOk()
            ->assertSee('Energía en movimiento')
            ->assertSee('Circuitos y energía')
            ->assertSee('Armamos un circuito con una pila, cables y un foco.')
            ->assertSee('Práctica de laboratorio')
            ->assertSee('Describe el circuito que armaste')
            ->assertSee('Guía de laboratorio')
            ->assertSee(route('colegio.secundaria.material.view', $this->resource->id), false)
            // El material se abre en el mismo visor del aula, no en otra pestaña.
            ->assertSee('id="materialDialog"', false)
            ->assertSee('data-material-open', false)
            ->assertSee('data-material-id="' . $this->resource->id . '"', false);
    }

    public function test_el_material_del_curso_se_puede_ver_y_descargar(): void
    {
        $this->actingAs($this->user)
            ->get(route('colegio.secundaria.material.view', $this->resource->id))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($this->user)
            ->get(route('colegio.secundaria.material.download', $this->resource->id))
            ->assertOk()
            ->assertDownload('guia-de-laboratorio.pdf');
    }

    public function test_un_alumno_de_primaria_es_devuelto_a_su_propio_aula(): void
    {
        [, $user] = $this->makePrimariaStudent();

        $this->actingAs($user)
            ->get(route('colegio.secundaria.dashboard'))
            ->assertRedirect(route('colegio.aula.dashboard'));
    }

    public function test_el_invitado_es_redirigido_al_login_de_secundaria(): void
    {
        $this->get(route('colegio.secundaria.dashboard'))
            ->assertRedirect(route('colegio.secundaria.login'));

        $this->get(route('colegio.secundaria.course', $this->course->id))
            ->assertRedirect(route('colegio.secundaria.login'));
    }

    public function test_cerrar_sesion_vuelve_al_login_de_secundaria(): void
    {
        $this->actingAs($this->user)
            ->post(route('colegio.secundaria.logout'))
            ->assertRedirect(route('colegio.secundaria.login'));

        $this->assertGuest();
    }

    /**
     * Alumno de Primaria para comprobar que cada acceso respeta su nivel.
     *
     * @return array{0: AcaSchoolStudent, 1: User}
     */
    private function makePrimariaStudent(): array
    {
        $primaria = AcaSchoolLevel::create([
            'school_id' => $this->school->id,
            'year_id' => $this->year->id,
            'code' => AcaSchoolLevel::PRIMARIA,
            'name' => 'Primaria',
            'status' => true,
        ]);

        $grade = AcaSchoolGrade::create([
            'school_id' => $this->school->id,
            'year_id' => $this->year->id,
            'level_id' => $primaria->id,
            'name' => '2°',
            'status' => true,
        ]);

        $section = AcaSchoolSection::create([
            'school_id' => $this->school->id,
            'year_id' => $this->year->id,
            'grade_id' => $grade->id,
            'name' => 'B',
            'capacity' => 25,
            'shift' => AcaSchoolSection::SHIFT_MANANA,
            'status' => true,
        ]);

        [$student, $user] = $this->makeStudent('Ana', 'Flores', '71234567', 'ana@colegio.test', 'primaria123');

        AcaSchoolEnrollment::create([
            'school_id' => $this->school->id,
            'year_id' => $this->year->id,
            'student_id' => $student->id,
            'section_id' => $section->id,
            'type' => AcaSchoolEnrollment::TYPE_NUEVA,
            'status' => AcaSchoolEnrollment::STATUS_ACTIVO,
            'enrollment_date' => now()->toDateString(),
        ]);

        return [$student, $user];
    }

    /**
     * @return array{0: AcaSchoolStudent, 1: User}
     */
    private function makeStudent(string $names, string $lastname, string $document, string $email, string $password = 'secundaria123'): array
    {
        $person = Person::create([
            'names' => $names,
            'father_lastname' => $lastname,
            'mother_lastname' => 'Ramos',
            'full_name' => $names . ' ' . $lastname . ' Ramos',
            'number' => $document,
            'email' => $email,
        ]);

        $user = User::create([
            'name' => $names . ' ' . $lastname,
            'email' => $email,
            'password' => Hash::make($password),
            'person_id' => $person->id,
            'status' => true,
        ]);

        $student = AcaSchoolStudent::create([
            'school_id' => $this->school->id,
            'person_id' => $person->id,
            'student_code' => AcaSchoolStudent::nextStudentCode($this->school->id),
            'status' => true,
        ]);

        return [$student, $user];
    }
}
