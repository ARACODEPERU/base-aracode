<?php

namespace Tests\Feature\Academic;

use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Academic\Entities\AcaSchool;
use Modules\Academic\Entities\AcaSchoolArea;
use Modules\Academic\Entities\AcaSchoolEnrollment;
use Modules\Academic\Entities\AcaSchoolGrade;
use Modules\Academic\Entities\AcaSchoolLevel;
use Modules\Academic\Entities\AcaSchoolSection;
use Modules\Academic\Entities\AcaSchoolStudent;
use Modules\Academic\Entities\AcaSchoolYear;
use Modules\Academic\Entities\AcaVirtualActivity;
use Modules\Academic\Entities\AcaVirtualActivityOption;
use Modules\Academic\Entities\AcaVirtualActivityQuestion;
use Modules\Academic\Entities\AcaVirtualCourse;
use Modules\Academic\Entities\AcaVirtualQuizAttempt;
use Modules\Academic\Entities\AcaVirtualSubmission;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class VirtualClassroomTest extends TestCase
{
    use RefreshDatabase;

    private AcaSchool $school;

    private AcaSchoolYear $activeYear;

    private AcaSchoolSection $section;

    private AcaSchoolArea $area;

    protected function setUp(): void
    {
        parent::setUp();

        // Las migraciones crean el colegio por defecto "Mi Colegio"; el
        // contexto de colegio siempre resuelve ese registro en modo mono-colegio.
        $this->school = AcaSchool::defaultSchool();
        $this->school->update(['name' => 'Colegio de prueba', 'status' => true]);

        $this->activeYear = AcaSchoolYear::create([
            'school_id' => $this->school->id,
            'year' => 2027,
            'status' => AcaSchoolYear::STATUS_ACTIVE,
        ]);

        $level = AcaSchoolLevel::create([
            'school_id' => $this->school->id,
            'year_id' => $this->activeYear->id,
            'code' => AcaSchoolLevel::PRIMARIA,
            'name' => 'Primaria',
            'status' => true,
        ]);

        $grade = AcaSchoolGrade::create([
            'school_id' => $this->school->id,
            'year_id' => $this->activeYear->id,
            'level_id' => $level->id,
            'name' => '1°',
            'status' => true,
        ]);

        $this->section = AcaSchoolSection::create([
            'school_id' => $this->school->id,
            'year_id' => $this->activeYear->id,
            'grade_id' => $grade->id,
            'name' => 'A',
            'shift' => AcaSchoolSection::SHIFT_MANANA,
            'status' => true,
        ]);

        $this->area = AcaSchoolArea::create([
            'school_id' => $this->school->id,
            'name' => 'Matemática',
            'level' => AcaSchoolArea::LEVEL_PRIMARIA,
            'status' => true,
        ]);
    }

    public function test_genera_un_curso_por_seccion_y_area_del_ano_activo(): void
    {
        $user = $this->userWithPermission('aca_virtual_configurar');

        $this->actingAs($user)
            ->post(route('aca_virtual_classroom_generate'))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('aca_virtual_courses', [
            'school_id' => $this->school->id,
            'year_id' => $this->activeYear->id,
            'section_id' => $this->section->id,
            'area_id' => $this->area->id,
        ]);

        $this->assertSame(1, AcaVirtualCourse::count());
    }

    public function test_generar_dos_veces_no_duplica_los_cursos(): void
    {
        $user = $this->userWithPermission('aca_virtual_configurar');

        $this->actingAs($user)->post(route('aca_virtual_classroom_generate'))->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('aca_virtual_classroom_generate'))->assertSessionHasNoErrors();

        $this->assertSame(1, AcaVirtualCourse::count());
    }

    public function test_un_usuario_sin_permiso_no_puede_generar_cursos(): void
    {
        $user = $this->userWithPermission();

        $this->actingAs($user)
            ->post(route('aca_virtual_classroom_generate'))
            ->assertForbidden();

        $this->assertSame(0, AcaVirtualCourse::count());
    }

    public function test_no_permite_registrar_unidades_en_un_ano_historico(): void
    {
        $user = $this->userWithPermission('aca_virtual_configurar', 'aca_virtual_unidad_gestionar');

        $oldYear = AcaSchoolYear::create([
            'school_id' => $this->school->id,
            'year' => 2026,
            'status' => AcaSchoolYear::STATUS_FINISHED,
        ]);

        $course = AcaVirtualCourse::create([
            'school_id' => $this->school->id,
            'year_id' => $oldYear->id,
            'section_id' => $this->section->id,
            'area_id' => $this->area->id,
            'status' => true,
        ]);

        $this->actingAs($user)
            ->post(route('aca_virtual_classroom_unit_store', $course->id), [
                'title' => 'Unidad del año pasado',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('aca_virtual_units', 0);
    }

    public function test_el_listado_muestra_la_opcion_de_generar_para_quien_puede_configurar(): void
    {
        $user = $this->userWithPermission('aca_virtual_listado', 'aca_virtual_configurar');

        $this->actingAs($user)
            ->get(route('aca_virtual_classroom_index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Academic::School/VirtualClassroom/Index', false)
                ->where('canGenerate', true)
                ->has('courses')
                ->has('years'));
    }

    public function test_la_pantalla_del_curso_entrega_recursos_y_actividades(): void
    {
        $user = $this->userWithPermission(
            'aca_virtual_curso_ver',
            'aca_virtual_configurar',
            'aca_virtual_recurso_gestionar',
            'aca_virtual_actividad_gestionar'
        );

        $course = AcaVirtualCourse::create([
            'school_id' => $this->school->id,
            'year_id' => $this->activeYear->id,
            'section_id' => $this->section->id,
            'area_id' => $this->area->id,
            'status' => true,
        ]);

        $this->actingAs($user)
            ->get(route('aca_virtual_classroom_show', $course->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Academic::School/VirtualClassroom/Course', false)
                ->has('resources')
                ->has('activities')
                ->where('canManageResources', true)
                ->where('canManageActivities', true)
                ->where('yearIsActive', true));
    }

    public function test_registra_un_recurso_de_enlace_con_permiso(): void
    {
        $user = $this->userWithPermission('aca_virtual_recurso_gestionar');
        $course = $this->activeCourse($user);

        $this->actingAs($user)
            ->post(route('aca_virtual_classroom_resource_store', $course->id), [
                'type' => 'link',
                'title' => 'Video de la clase',
                'url' => 'https://www.youtube.com/watch?v=abc123',
                'visibility' => 'students',
                'status' => true,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('aca_virtual_resources', [
            'course_id' => $course->id,
            'type' => 'link',
            'title' => 'Video de la clase',
        ]);
    }

    public function test_sube_un_archivo_como_recurso(): void
    {
        Storage::fake('public');

        $user = $this->userWithPermission('aca_virtual_recurso_gestionar');
        $course = $this->activeCourse($user);

        $this->actingAs($user)
            ->post(route('aca_virtual_classroom_resource_store', $course->id), [
                'type' => 'file',
                'title' => 'Guía de ejercicios',
                'file' => UploadedFile::fake()->create('guia.pdf', 500, 'application/pdf'),
                'visibility' => 'students',
                'status' => true,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $resource = \Modules\Academic\Entities\AcaVirtualResource::where('course_id', $course->id)->firstOrFail();

        $this->assertSame('file', $resource->type);
        $this->assertNotNull($resource->path);
        Storage::disk('public')->assertExists($resource->path);
    }

    public function test_no_registra_recursos_sin_permiso(): void
    {
        $user = $this->userWithPermission();
        $course = $this->activeCourse($user);

        $this->actingAs($user)
            ->post(route('aca_virtual_classroom_resource_store', $course->id), [
                'type' => 'link',
                'title' => 'Video de la clase',
                'url' => 'https://example.com',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('aca_virtual_resources', 0);
    }

    public function test_registra_una_actividad_con_permiso(): void
    {
        $user = $this->userWithPermission('aca_virtual_actividad_gestionar');
        $course = $this->activeCourse($user);

        $this->actingAs($user)
            ->post(route('aca_virtual_classroom_activity_store', $course->id), [
                'type' => 'task',
                'title' => 'Resolver ejercicios',
                'instructions' => 'Resuelve los ejercicios 1 al 10.',
                'max_score' => 20,
                'grading_scale' => 'numeric',
                'status' => true,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('aca_virtual_activities', [
            'course_id' => $course->id,
            'type' => 'task',
            'title' => 'Resolver ejercicios',
        ]);
    }

    public function test_califica_una_entrega_con_permiso(): void
    {
        $user = $this->userWithPermission('aca_virtual_actividad_gestionar');
        $course = $this->activeCourse($user);

        $activity = AcaVirtualActivity::create([
            'course_id' => $course->id,
            'type' => 'task',
            'title' => 'Tarea calificable',
            'max_score' => 20,
            'grading_scale' => 'numeric',
            'status' => true,
        ]);

        $studentPerson = Person::create([
            'number' => '90000002',
            'names' => 'Alumno',
            'father_lastname' => 'Prueba',
            'full_name' => 'Alumno Prueba',
        ]);

        $student = AcaSchoolStudent::create([
            'school_id' => $this->school->id,
            'person_id' => $studentPerson->id,
            'student_code' => '2027-0001',
            'status' => true,
        ]);

        $submission = AcaVirtualSubmission::create([
            'activity_id' => $activity->id,
            'student_id' => $student->id,
            'content' => 'Mi respuesta',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('aca_virtual_classroom_submission_grade', $submission->id), [
                'score' => 18,
                'feedback' => 'Buen trabajo',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('aca_virtual_submissions', [
            'id' => $submission->id,
            'status' => 'graded',
            'score' => 18,
            'graded_by_person_id' => $user->person_id,
        ]);
    }

    public function test_permite_registrar_unidades_en_el_ano_activo_con_permiso(): void
    {
        $user = $this->userWithPermission('aca_virtual_unidad_gestionar');

        $course = AcaVirtualCourse::create([
            'school_id' => $this->school->id,
            'year_id' => $this->activeYear->id,
            'section_id' => $this->section->id,
            'area_id' => $this->area->id,
            'teacher_person_id' => $user->person_id,
            'status' => true,
        ]);

        $this->actingAs($user)
            ->post(route('aca_virtual_classroom_unit_store', $course->id), [
                'title' => 'Unidad 1',
                'purpose' => 'Resolver problemas',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('aca_virtual_units', [
            'course_id' => $course->id,
            'title' => 'Unidad 1',
        ]);
    }

    public function test_la_ficha_del_curso_expone_el_material_y_las_preguntas_configuradas(): void
    {
        $user = $this->userWithPermission('aca_virtual_curso_ver', 'aca_virtual_actividad_gestionar');
        $course = $this->activeCourse($user);
        $activity = $this->quizActivity($course);
        $this->question($activity, 'single_choice', 4, ['Correcta' => true, 'Incorrecta' => false]);

        $this->actingAs($user)
            ->get(route('aca_virtual_classroom_show', $course->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Academic::School/VirtualClassroom/Course', false)
                ->has('activities', 1)
                ->where('activities.0.questions_count', 1)
                ->has('activities.0.questions.0.options', 2)
                ->where('activities.0.questions.0.options.0.is_correct', true)
                ->has('activities.0.files'));
    }

    public function test_configura_el_tiempo_y_los_intentos_de_una_evaluacion(): void
    {
        $user = $this->userWithPermission('aca_virtual_actividad_gestionar');
        $course = $this->activeCourse($user);

        $this->actingAs($user)
            ->post(route('aca_virtual_classroom_activity_store', $course->id), [
                'type' => 'quiz',
                'title' => 'Evaluación cronometrada',
                'attempts_allowed' => 3,
                'duration_minutes' => 40,
                'allow_result_download' => true,
                'max_score' => 20,
                'grading_scale' => 'numeric',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('aca_virtual_activities', [
            'course_id' => $course->id,
            'title' => 'Evaluación cronometrada',
            'attempts_allowed' => 3,
            'duration_minutes' => 40,
            'allow_result_download' => true,
        ]);
    }

    public function test_permite_adjuntar_material_de_apoyo_a_una_actividad(): void
    {
        Storage::fake('public');

        $user = $this->userWithPermission('aca_virtual_actividad_gestionar');
        $course = $this->activeCourse($user);

        $this->actingAs($user)
            ->post(route('aca_virtual_classroom_activity_store', $course->id), [
                'type' => 'task',
                'title' => 'Ficha de fracciones',
                'max_score' => 20,
                'files' => [UploadedFile::fake()->create('ficha-fracciones.pdf', 120, 'application/pdf')],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $activity = AcaVirtualActivity::where('title', 'Ficha de fracciones')->firstOrFail();
        $this->assertDatabaseHas('aca_virtual_activity_files', [
            'activity_id' => $activity->id,
            'original_name' => 'ficha-fracciones.pdf',
        ]);

        $file = $activity->files()->firstOrFail();
        Storage::disk('public')->assertExists($file->path);
    }

    public function test_permite_quitar_un_archivo_adjunto_de_la_actividad(): void
    {
        Storage::fake('public');

        $user = $this->userWithPermission('aca_virtual_actividad_gestionar');
        $course = $this->activeCourse($user);
        $activity = $this->quizActivity($course);
        $path = 'colegios/'. $this->school->id .'/aula-virtual/curso-'. $course->id .'/actividades/guia.pdf';
        Storage::disk('public')->put($path, 'contenido');

        $file = $activity->files()->create([
            'path' => $path,
            'original_name' => 'guia.pdf',
            'mime_type' => 'application/pdf',
            'size_kb' => 8,
        ]);

        $this->actingAs($user)
            ->post(route('aca_virtual_classroom_activity_update', $activity->id), [
                'type' => 'quiz',
                'title' => $activity->title,
                'max_score' => $activity->max_score,
                'remove_file_ids' => [$file->id],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('aca_virtual_activity_files', ['id' => $file->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_configura_preguntas_y_respuestas_de_una_evaluacion(): void
    {
        $user = $this->userWithPermission('aca_virtual_actividad_gestionar');
        $course = $this->activeCourse($user);
        $activity = $this->quizActivity($course);

        $this->actingAs($user)
            ->post(route('aca_virtual_classroom_question_store', $activity->id), [
                'type' => 'single_choice',
                'question' => '¿Cuánto es 3/4 + 1/2?',
                'points' => 4,
                'options' => [
                    ['text' => '5/4', 'is_correct' => true],
                    ['text' => '4/6', 'is_correct' => false],
                    ['text' => '1/2', 'is_correct' => false],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $question = $activity->questions()->firstOrFail();

        $this->assertSame('single_choice', $question->type);
        $this->assertSame(3, $question->options()->count());
        $this->assertDatabaseHas('aca_virtual_activity_options', [
            'question_id' => $question->id,
            'text' => '5/4',
            'is_correct' => true,
        ]);
    }

    public function test_rechaza_una_pregunta_de_alternativa_unica_con_dos_respuestas_correctas(): void
    {
        $user = $this->userWithPermission('aca_virtual_actividad_gestionar');
        $course = $this->activeCourse($user);
        $activity = $this->quizActivity($course);

        $this->actingAs($user)
            ->post(route('aca_virtual_classroom_question_store', $activity->id), [
                'type' => 'single_choice',
                'question' => 'Pregunta mal configurada',
                'points' => 2,
                'options' => [
                    ['text' => 'A', 'is_correct' => true],
                    ['text' => 'B', 'is_correct' => true],
                ],
            ])
            ->assertSessionHasErrors('options');

        $this->assertSame(0, AcaVirtualActivityQuestion::count());
    }

    public function test_el_sistema_califica_automaticamente_al_terminar_la_evaluacion(): void
    {
        $teacher = $this->userWithPermission('aca_virtual_actividad_gestionar');
        $course = $this->activeCourse($teacher);
        $activity = $this->quizActivity($course, ['max_score' => 20]);

        $single = $this->question($activity, 'single_choice', 4, ['Correcta' => true, 'Incorrecta' => false]);
        $multiple = $this->question($activity, 'multiple_choice', 3, ['A' => true, 'B' => true, 'C' => false]);
        $short = $this->question($activity, 'short_answer', 3, [], '5/4|1.25');

        $studentUser = $this->enrolledStudentUser('Alumno Uno', '2027-0001');

        $this->actingAs($studentUser)
            ->postJson(route('aca_virtual_classroom_attempt_start', $activity->id))
            ->assertOk()
            ->assertJsonCount(3, 'questions')
            ->assertJsonMissingPath('questions.0.options.0.is_correct');

        $attempt = AcaVirtualQuizAttempt::firstOrFail();

        $this->actingAs($studentUser)
            ->postJson(route('aca_virtual_classroom_attempt_submit', $attempt->id), [
                'answers' => [
                    [
                        'question_id' => $single->id,
                        'option_ids' => [$single->options->firstWhere('is_correct', true)->id],
                    ],
                    [
                        // Elige solo una de las dos correctas: la pregunta queda en cero.
                        'question_id' => $multiple->id,
                        'option_ids' => [$multiple->options->firstWhere('text', 'A')->id],
                    ],
                    [
                        'question_id' => $short->id,
                        'answer_text' => '5/4',
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('attempt.status', 'finished')
            ->assertJsonPath('attempt.points_earned', 7)
            ->assertJsonPath('attempt.points_total', 10)
            ->assertJsonPath('attempt.score', 14);

        $this->assertDatabaseHas('aca_virtual_submissions', [
            'activity_id' => $activity->id,
            'status' => 'graded',
            'score' => 14,
            'graded_by_person_id' => null,
        ]);

        $this->assertSame(3, $attempt->fresh()->answers()->count());
    }

    public function test_una_respuesta_abierta_sin_coincidencia_no_otorga_puntos(): void
    {
        $teacher = $this->userWithPermission('aca_virtual_actividad_gestionar');
        $course = $this->activeCourse($teacher);
        $activity = $this->quizActivity($course, ['max_score' => 10]);
        $short = $this->question($activity, 'short_answer', 5, [], 'simplificar|reducir');

        $studentUser = $this->enrolledStudentUser('Alumno Dos', '2027-0002');

        $this->actingAs($studentUser)->postJson(route('aca_virtual_classroom_attempt_start', $activity->id))->assertOk();
        $attempt = AcaVirtualQuizAttempt::firstOrFail();

        $this->actingAs($studentUser)
            ->postJson(route('aca_virtual_classroom_attempt_submit', $attempt->id), [
                'answers' => [
                    ['question_id' => $short->id, 'answer_text' => '  SIMPLIFICAR  '],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('attempt.score', 10);
    }

    public function test_un_alumno_de_otra_seccion_no_puede_rendir_la_evaluacion(): void
    {
        $teacher = $this->userWithPermission('aca_virtual_actividad_gestionar');
        $course = $this->activeCourse($teacher);
        $activity = $this->quizActivity($course);
        $this->question($activity, 'single_choice', 2, ['Sí' => true, 'No' => false]);

        $studentUser = $this->enrolledStudentUser('Alumno Tres', '2027-0003', enroll: false);

        $this->actingAs($studentUser)
            ->postJson(route('aca_virtual_classroom_attempt_start', $activity->id))
            ->assertForbidden();
    }

    public function test_no_permite_rendir_una_evaluacion_sin_preguntas(): void
    {
        $teacher = $this->userWithPermission('aca_virtual_actividad_gestionar');
        $course = $this->activeCourse($teacher);
        $activity = $this->quizActivity($course);
        $studentUser = $this->enrolledStudentUser('Alumno Cuatro', '2027-0004');

        $this->actingAs($studentUser)
            ->postJson(route('aca_virtual_classroom_attempt_start', $activity->id))
            ->assertStatus(422);
    }

    public function test_no_permite_agregar_preguntas_cuando_la_evaluacion_ya_uso_todos_sus_puntos(): void
    {
        $user = $this->userWithPermission('aca_virtual_actividad_gestionar');
        $course = $this->activeCourse($user);
        $activity = $this->quizActivity($course, ['max_score' => 20]);
        $this->question($activity, 'single_choice', 12, ['A' => true, 'B' => false]);
        $this->question($activity, 'short_answer', 8, [], 'ok');

        $this->actingAs($user)
            ->post(route('aca_virtual_classroom_question_store', $activity->id), [
                'type' => 'true_false',
                'question' => 'Pregunta extra',
                'points' => 1,
                'options' => [
                    ['text' => 'Verdadero', 'is_correct' => true],
                    ['text' => 'Falso', 'is_correct' => false],
                ],
            ])
            ->assertSessionHasErrors('points');

        $this->assertSame(2, $activity->questions()->count());
    }

    public function test_rechaza_una_pregunta_cuyos_puntos_sobrepasan_el_maximo_de_la_evaluacion(): void
    {
        $user = $this->userWithPermission('aca_virtual_actividad_gestionar');
        $course = $this->activeCourse($user);
        $activity = $this->quizActivity($course, ['max_score' => 20]);
        $this->question($activity, 'single_choice', 12, ['A' => true, 'B' => false]);
        $this->question($activity, 'short_answer', 6, [], 'ok');

        $response = $this->actingAs($user)
            ->post(route('aca_virtual_classroom_question_store', $activity->id), [
                'type' => 'short_answer',
                'question' => 'Pregunta de más',
                'points' => 5,
                'answer_text' => 'respuesta',
            ]);

        $response->assertSessionHasErrors('points');
        $this->assertStringContainsString('hasta 2 puntos', (string) session('errors')->first('points'));
        $this->assertSame(2, $activity->questions()->count());
    }

    public function test_permite_editar_una_pregunta_que_ya_ocupa_todos_los_puntos(): void
    {
        $user = $this->userWithPermission('aca_virtual_actividad_gestionar');
        $course = $this->activeCourse($user);
        $activity = $this->quizActivity($course, ['max_score' => 20]);
        $first = $this->question($activity, 'short_answer', 10, [], 'uno');
        $this->question($activity, 'short_answer', 10, [], 'dos');

        $this->actingAs($user)
            ->post(route('aca_virtual_classroom_question_update', $first->id), [
                'type' => 'short_answer',
                'question' => 'Pregunta corregida',
                'points' => 10,
                'answer_text' => 'uno|primero',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('aca_virtual_activity_questions', [
            'id' => $first->id,
            'question' => 'Pregunta corregida',
            'points' => 10,
        ]);
    }

    public function test_el_docente_califica_a_mano_las_respuestas_abiertas_y_la_nota_se_recalcula(): void
    {
        $teacher = $this->userWithPermission('aca_virtual_actividad_gestionar');
        $course = $this->activeCourse($teacher);
        $activity = $this->quizActivity($course, ['max_score' => 10]);

        $single = $this->question($activity, 'single_choice', 5, ['Correcta' => true, 'Incorrecta' => false]);
        $short = $this->question($activity, 'short_answer', 5, [], 'simplificar');

        $studentUser = $this->enrolledStudentUser('Alumno Revisión', '2027-0009');

        $this->actingAs($studentUser)->postJson(route('aca_virtual_classroom_attempt_start', $activity->id))->assertOk();
        $attempt = AcaVirtualQuizAttempt::firstOrFail();

        $this->actingAs($studentUser)
            ->postJson(route('aca_virtual_classroom_attempt_submit', $attempt->id), [
                'answers' => [
                    ['question_id' => $single->id, 'option_ids' => [$single->options->firstWhere('is_correct', true)->id]],
                    ['question_id' => $short->id, 'answer_text' => 'se reduce la fracción'],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('attempt.score', 5);

        $this->actingAs($teacher)
            ->post(route('aca_virtual_classroom_attempt_review', $attempt->id), [
                'answers' => [
                    ['question_id' => $short->id, 'points_earned' => 4],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('aca_virtual_quiz_attempts', [
            'id' => $attempt->id,
            'points_earned' => 9,
            'score' => 9,
        ]);

        $this->assertDatabaseHas('aca_virtual_quiz_answers', [
            'attempt_id' => $attempt->id,
            'question_id' => $short->id,
            'points_earned' => 4,
            'manually_graded' => true,
            'graded_by_person_id' => $teacher->person_id,
        ]);

        $this->assertDatabaseHas('aca_virtual_submissions', [
            'activity_id' => $activity->id,
            'score' => 9,
            'graded_by_person_id' => $teacher->person_id,
        ]);

        $submission = AcaVirtualSubmission::where('activity_id', $activity->id)->firstOrFail();
        $this->assertStringContainsString('Revisión manual', (string) $submission->feedback);
    }

    public function test_un_usuario_sin_permiso_no_puede_revisar_respuestas_abiertas(): void
    {
        $teacher = $this->userWithPermission('aca_virtual_actividad_gestionar');
        $course = $this->activeCourse($teacher);
        $activity = $this->quizActivity($course, ['max_score' => 10]);
        $short = $this->question($activity, 'short_answer', 10, [], 'simplificar');
        $studentUser = $this->enrolledStudentUser('Alumno Sin Permiso', '2027-0010');

        $this->actingAs($studentUser)->postJson(route('aca_virtual_classroom_attempt_start', $activity->id))->assertOk();
        $attempt = AcaVirtualQuizAttempt::firstOrFail();

        $this->actingAs($studentUser)
            ->postJson(route('aca_virtual_classroom_attempt_submit', $attempt->id), [
                'answers' => [['question_id' => $short->id, 'answer_text' => 'sin respuesta']],
            ])
            ->assertOk();

        $this->actingAs($studentUser)
            ->post(route('aca_virtual_classroom_attempt_review', $attempt->id), [
                'answers' => [['question_id' => $short->id, 'points_earned' => 10]],
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('aca_virtual_quiz_attempts', ['id' => $attempt->id, 'score' => 0]);
    }

    private function quizActivity(AcaVirtualCourse $course, array $attributes = []): AcaVirtualActivity
    {
        return AcaVirtualActivity::create(array_merge([
            'course_id' => $course->id,
            'type' => 'quiz',
            'title' => 'Evaluación de fracciones',
            'max_score' => 20,
            'grading_scale' => 'numeric',
            'status' => true,
            'sort_order' => 0,
        ], $attributes));
    }

    /**
     * @param  array<string, bool>  $options
     */
    private function question(
        AcaVirtualActivity $activity,
        string $type,
        float $points,
        array $options = [],
        ?string $answerText = null
    ): AcaVirtualActivityQuestion {
        $question = AcaVirtualActivityQuestion::create([
            'activity_id' => $activity->id,
            'type' => $type,
            'question' => 'Pregunta '.($activity->questions()->count() + 1),
            'points' => $points,
            'answer_text' => $answerText,
            'sort_order' => $activity->questions()->count(),
        ]);

        foreach (array_values($options) as $index => $isCorrect) {
            AcaVirtualActivityOption::create([
                'question_id' => $question->id,
                'text' => array_keys($options)[$index],
                'is_correct' => $isCorrect,
                'sort_order' => $index,
            ]);
        }

        return $question->load('options');
    }

    private function enrolledStudentUser(string $name, string $code, bool $enroll = true): User
    {
        $person = Person::create([
            'number' => '8'.substr($code, -6),
            'names' => $name,
            'father_lastname' => 'Prueba',
            'full_name' => $name,
        ]);

        $student = AcaSchoolStudent::create([
            'school_id' => $this->school->id,
            'person_id' => $person->id,
            'student_code' => $code,
            'status' => true,
        ]);

        if ($enroll) {
            AcaSchoolEnrollment::create([
                'school_id' => $this->school->id,
                'year_id' => $this->activeYear->id,
                'student_id' => $student->id,
                'section_id' => $this->section->id,
                'type' => AcaSchoolEnrollment::TYPE_NUEVA,
                'status' => AcaSchoolEnrollment::STATUS_ACTIVO,
                'enrollment_date' => now()->toDateString(),
            ]);
        }

        return User::factory()->create(['person_id' => $person->id]);
    }

    private function activeCourse(User $teacher): AcaVirtualCourse
    {
        return AcaVirtualCourse::create([
            'school_id' => $this->school->id,
            'year_id' => $this->activeYear->id,
            'section_id' => $this->section->id,
            'area_id' => $this->area->id,
            'teacher_person_id' => $teacher->person_id,
            'status' => true,
        ]);
    }

    private function userWithPermission(string ...$permissions): User
    {
        $person = Person::create([
            'number' => '90000001',
            'names' => 'Docente',
            'father_lastname' => 'Prueba',
            'mother_lastname' => 'Aula',
            'full_name' => 'Docente Prueba Aula',
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
