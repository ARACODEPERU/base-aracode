<?php

namespace Tests\Feature\Academic;

use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
use Modules\Academic\Entities\AcaVirtualActivityOption;
use Modules\Academic\Entities\AcaVirtualActivityQuestion;
use Modules\Academic\Entities\AcaVirtualCourse;
use Modules\Academic\Entities\AcaVirtualQuizAttempt;
use Modules\Academic\Entities\AcaVirtualSubmission;
use Modules\Academic\Entities\AcaVirtualUnit;
use Tests\TestCase;

/**
 * Evaluaciones y tareas del portal del alumno: intentos configurables por el
 * docente, nota vigente del último intento y entrega de tareas en PDF.
 */
class ColegioEvaluacionesTareasTest extends TestCase
{
    use RefreshDatabase;

    private AcaSchool $school;

    private AcaSchoolYear $year;

    private AcaSchoolSection $section;

    private AcaSchoolStudent $student;

    private User $user;

    private AcaVirtualCourse $course;

    private AcaVirtualActivity $quiz;

    private AcaVirtualActivityQuestion $choice;

    private AcaVirtualActivityOption $correctOption;

    private AcaVirtualActivityOption $wrongOption;

    private AcaVirtualActivityQuestion $short;

    private AcaVirtualActivity $task;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

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

        $unit = AcaVirtualUnit::create([
            'course_id' => $this->course->id,
            'title' => 'Energía en movimiento',
            'purpose' => 'Explicar las transformaciones de energía.',
            'sort_order' => 1,
            'status' => true,
        ]);

        $this->quiz = AcaVirtualActivity::create([
            'course_id' => $this->course->id,
            'unit_id' => $unit->id,
            'type' => 'quiz',
            'title' => 'Práctica de energía',
            'instructions' => 'Responde con lo aprendido en clase.',
            'attempts_allowed' => 2,
            'allow_result_download' => false,
            'duration_minutes' => 20,
            'max_score' => 20,
            'grading_scale' => 'numeric',
            'sort_order' => 1,
            'status' => true,
        ]);

        $this->choice = AcaVirtualActivityQuestion::create([
            'activity_id' => $this->quiz->id,
            'type' => AcaVirtualActivityQuestion::TYPE_SINGLE,
            'question' => '¿Qué transformación ocurre en un panel solar?',
            'points' => 10,
            'sort_order' => 1,
        ]);

        $this->correctOption = AcaVirtualActivityOption::create([
            'question_id' => $this->choice->id,
            'text' => 'Luz solar en energía eléctrica',
            'is_correct' => true,
            'sort_order' => 1,
        ]);

        $this->wrongOption = AcaVirtualActivityOption::create([
            'question_id' => $this->choice->id,
            'text' => 'Electricidad en sonido',
            'is_correct' => false,
            'sort_order' => 2,
        ]);

        $this->short = AcaVirtualActivityQuestion::create([
            'activity_id' => $this->quiz->id,
            'type' => AcaVirtualActivityQuestion::TYPE_SHORT,
            'question' => 'Escribe una fuente de energía renovable.',
            'points' => 10,
            'answer_text' => 'solar|eólica',
            'sort_order' => 2,
        ]);

        $this->task = AcaVirtualActivity::create([
            'course_id' => $this->course->id,
            'unit_id' => $unit->id,
            'type' => 'task',
            'title' => 'Informe de laboratorio',
            'instructions' => 'Sube tu informe en PDF.',
            'max_score' => 20,
            'grading_scale' => 'numeric',
            'sort_order' => 2,
            'status' => true,
        ]);
    }

    public function test_el_curso_muestra_las_evaluaciones_y_tareas_del_alumno(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('colegio.secundaria.course', $this->course->id));

        $response->assertOk()
            ->assertSee('Práctica de energía')
            ->assertSee('Rendir evaluación')
            ->assertSee('Informe de laboratorio')
            ->assertSee('Sube tu tarea en PDF')
            ->assertSee(route('colegio.secundaria.quiz', $this->quiz->id), false);
    }

    public function test_el_alumno_responde_y_ve_su_nota_sin_las_respuestas_correctas(): void
    {
        // Antes de iniciar, el examen está bloqueado y no expone preguntas.
        $this->actingAs($this->user)
            ->get(route('colegio.secundaria.quiz', $this->quiz->id))
            ->assertOk()
            ->assertSee('Evaluación bloqueada')
            ->assertSee('Iniciar examen')
            ->assertSee('Tiempo por intento')
            ->assertSee('20 minutos')
            ->assertDontSee('¿Qué transformación ocurre en un panel solar?');

        $this->actingAs($this->user)
            ->post(route('colegio.secundaria.quiz.start', $this->quiz->id))
            ->assertRedirect(route('colegio.secundaria.quiz', $this->quiz->id));

        $response = $this->actingAs($this->user)
            ->get(route('colegio.secundaria.quiz', $this->quiz->id));

        $response->assertOk()
            ->assertSee('¿Qué transformación ocurre en un panel solar?')
            ->assertSee('Luz solar en energía eléctrica')
            ->assertSee('Terminar examen')
            ->assertDontSee('name="is_correct"', false)
            ->assertDontSee('data-correct', false);

        $this->submitQuiz(correct: true)
            ->assertRedirect(route('colegio.secundaria.quiz.result', $this->quiz->id));

        $attempt = AcaVirtualQuizAttempt::where('activity_id', $this->quiz->id)->firstOrFail();

        $this->assertSame(1, (int) $attempt->attempt_number);
        $this->assertSame(AcaVirtualQuizAttempt::STATUS_FINISHED, $attempt->status);
        $this->assertSame(20.0, (float) $attempt->score);

        $this->actingAs($this->user)
            ->get(route('colegio.secundaria.quiz.result', $this->quiz->id))
            ->assertOk()
            ->assertSee('20 / 20')
            ->assertSee('¿Quieres intentarlo otra vez?')
            ->assertDontSee('Calificación automática')
            ->assertDontSee('respuestas correctas (')
            ->assertDontSee('Luz solar en energía eléctrica');
    }

    public function test_el_segundo_intento_reemplaza_la_nota_y_respeta_el_limite(): void
    {
        $this->submitQuiz(correct: false);

        $this->assertSame(0.0, (float) AcaVirtualQuizAttempt::firstOrFail()->score);

        $this->submitQuiz(correct: true);

        $attempts = AcaVirtualQuizAttempt::where('activity_id', $this->quiz->id)
            ->orderBy('attempt_number')
            ->get();

        $this->assertCount(2, $attempts);
        $this->assertSame([1, 2], $attempts->pluck('attempt_number')->map(fn ($n) => (int) $n)->all());
        $this->assertSame(20.0, (float) $attempts->last()->score);

        $submission = AcaVirtualSubmission::where('activity_id', $this->quiz->id)->firstOrFail();
        $this->assertSame(20.0, (float) $submission->score);

        // Ya no le quedan intentos: la pantalla no ofrece otro y el envío falla.
        $this->actingAs($this->user)
            ->get(route('colegio.secundaria.quiz.result', $this->quiz->id))
            ->assertOk()
            ->assertSee('Ya usaste todos los intentos');

        $this->actingAs($this->user)
            ->from(route('colegio.secundaria.quiz', $this->quiz->id))
            ->get(route('colegio.secundaria.quiz', $this->quiz->id))
            ->assertRedirect(route('colegio.secundaria.quiz.result', $this->quiz->id));
    }

    public function test_el_alumno_con_un_intento_puede_volver_a_intentarlo(): void
    {
        $this->submitQuiz(correct: false);

        $this->actingAs($this->user)
            ->get(route('colegio.secundaria.quiz.result', $this->quiz->id))
            ->assertOk()
            ->assertSee('Intentar otra vez');

        $this->actingAs($this->user)
            ->get(route('colegio.secundaria.quiz', $this->quiz->id))
            ->assertOk()
            ->assertSee('Intento 2 de 2')
            ->assertSee('Este intento la reemplazará.');
    }

    public function test_la_descarga_del_examen_resuelto_solo_se_habilita_si_el_docente_lo_configura(): void
    {
        $this->submitQuiz(correct: true);

        $this->actingAs($this->user)
            ->get(route('colegio.secundaria.quiz.download', $this->quiz->id))
            ->assertForbidden();

        $this->quiz->update(['allow_result_download' => true]);

        $response = $this->actingAs($this->user)
            ->get(route('colegio.secundaria.quiz.download', $this->quiz->id));

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_el_alumno_entrega_una_tarea_pdf_y_ve_su_nota(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('colegio.secundaria.task.submit', $this->task->id), [
                'file' => UploadedFile::fake()->create('informe.pdf', 120, 'application/pdf'),
            ]);

        $response->assertRedirect(route('colegio.secundaria.course', $this->course->id));

        $submission = AcaVirtualSubmission::where('activity_id', $this->task->id)->firstOrFail();

        $this->assertSame('submitted', $submission->status);
        $this->assertNotNull($submission->path);
        Storage::disk('public')->assertExists($submission->path);

        $this->actingAs($this->user)
            ->get(route('colegio.secundaria.course', $this->course->id))
            ->assertOk()
            ->assertSee('Entregada')
            ->assertSee('Sube tu tarea en PDF');

        // El docente califica: la nota y el comentario se muestran al alumno.
        $submission->update([
            'status' => 'graded',
            'score' => 18,
            'feedback' => 'Buen resumen del experimento.',
            'graded_at' => now(),
        ]);

        $this->actingAs($this->user)
            ->get(route('colegio.secundaria.course', $this->course->id))
            ->assertOk()
            ->assertSee('Calificada')
            ->assertSee('18')
            ->assertSee('Buen resumen del experimento.');
    }

    public function test_la_tarea_rechaza_archivos_que_no_son_pdf(): void
    {
        $response = $this->actingAs($this->user)
            ->from(route('colegio.secundaria.course', $this->course->id))
            ->post(route('colegio.secundaria.task.submit', $this->task->id), [
                'file' => UploadedFile::fake()->create('informe.docx', 40, 'application/msword'),
            ]);

        $response->assertRedirect(route('colegio.secundaria.course', $this->course->id))
            ->assertSessionHasErrors('file');

        $this->assertDatabaseMissing('aca_virtual_submissions', [
            'activity_id' => $this->task->id,
            'student_id' => $this->student->id,
        ]);
    }

    public function test_primaria_tambien_puede_rendir_la_evaluacion(): void
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
            'name' => '5°',
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

        $course = AcaVirtualCourse::create([
            'school_id' => $this->school->id,
            'year_id' => $this->year->id,
            'section_id' => $section->id,
            'area_id' => $this->course->area_id,
            'name' => null,
            'status' => true,
            'sort_order' => 1,
        ]);

        $unit = AcaVirtualUnit::create([
            'course_id' => $course->id,
            'title' => 'Las plantas',
            'sort_order' => 1,
            'status' => true,
        ]);

        AcaVirtualActivity::create([
            'course_id' => $course->id,
            'type' => 'task',
            'title' => 'Dibujo de una planta',
            'max_score' => 20,
            'grading_scale' => 'numeric',
            'sort_order' => 0,
            'status' => true,
        ]);

        $quiz = AcaVirtualActivity::create([
            'course_id' => $course->id,
            'unit_id' => $unit->id,
            'type' => 'quiz',
            'title' => 'Reto de ciencias',
            'attempts_allowed' => 2,
            'max_score' => 10,
            'grading_scale' => 'numeric',
            'sort_order' => 1,
            'status' => true,
        ]);

        $question = AcaVirtualActivityQuestion::create([
            'activity_id' => $quiz->id,
            'type' => AcaVirtualActivityQuestion::TYPE_SINGLE,
            'question' => '¿Qué necesita una planta para crecer?',
            'points' => 10,
            'sort_order' => 1,
        ]);

        $option = AcaVirtualActivityOption::create([
            'question_id' => $question->id,
            'text' => 'Luz y agua',
            'is_correct' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('colegio.aula.course', $course->id))
            ->assertOk()
            ->assertSee('Reto de ciencias')
            ->assertSee('Responder evaluación')
            ->assertSee('Actividades del curso')
            ->assertSee('Dibujo de una planta');

        $this->actingAs($user)
            ->get(route('colegio.aula.quiz', $quiz->id))
            ->assertOk()
            ->assertSee('Evaluación bloqueada')
            ->assertSee('Iniciar examen')
            ->assertDontSee('¿Qué necesita una planta para crecer?');

        $this->actingAs($user)
            ->post(route('colegio.aula.quiz.start', $quiz->id))
            ->assertRedirect(route('colegio.aula.quiz', $quiz->id));

        $this->actingAs($user)
            ->get(route('colegio.aula.quiz', $quiz->id))
            ->assertOk()
            ->assertSee('¿Qué necesita una planta para crecer?');

        $this->actingAs($user)
            ->post(route('colegio.aula.quiz.submit', $quiz->id), [
                'answers' => [
                    $question->id => ['option_ids' => [$option->id]],
                ],
            ])
            ->assertRedirect(route('colegio.aula.quiz.result', $quiz->id));

        $this->actingAs($user)
            ->get(route('colegio.aula.quiz.result', $quiz->id))
            ->assertOk()
            ->assertSee('10 / 10');
    }

    public function test_no_califica_ni_crea_un_intento_si_el_alumno_no_pulso_iniciar(): void
    {
        $this->actingAs($this->user)
            ->post(route('colegio.secundaria.quiz.submit', $this->quiz->id), [
                'answers' => [
                    $this->choice->id => ['option_ids' => [$this->correctOption->id]],
                    $this->short->id => ['answer_text' => 'solar'],
                ],
            ])
            ->assertRedirect(route('colegio.secundaria.quiz', $this->quiz->id))
            ->assertSessionHasErrors('evaluacion');

        $this->assertDatabaseCount('aca_virtual_quiz_attempts', 0);
    }

    public function test_el_tiempo_empieza_al_iniciar_y_el_examen_muestra_la_cuenta_regresiva(): void
    {
        $this->actingAs($this->user)
            ->post(route('colegio.secundaria.quiz.start', $this->quiz->id))
            ->assertRedirect(route('colegio.secundaria.quiz', $this->quiz->id));

        $attempt = AcaVirtualQuizAttempt::where('activity_id', $this->quiz->id)->firstOrFail();

        $this->assertNotNull($attempt->started_at);
        $this->assertNotNull($attempt->expires_at);
        $this->assertCount(2, $attempt->question_order);
        $this->assertArrayHasKey($this->choice->id, $attempt->option_order);
        $this->assertEqualsWithDelta(
            now()->addMinutes(20)->getTimestamp(),
            $attempt->expires_at->getTimestamp(),
            5
        );

        $this->actingAs($this->user)
            ->get(route('colegio.secundaria.quiz', $this->quiz->id))
            ->assertOk()
            ->assertSee('Tiempo restante')
            ->assertSee('Terminar examen')
            ->assertSee('data-quiz-runner', false)
            ->assertSee('data-total-seconds="1200"', false);
    }

    public function test_el_examen_cierra_en_la_hora_de_disponibilidad_aunque_sobre_tiempo(): void
    {
        $this->quiz->update([
            'close_at' => now()->addMinutes(5),
            'duration_minutes' => 40,
        ]);

        $this->actingAs($this->user)
            ->post(route('colegio.secundaria.quiz.start', $this->quiz->id));

        $attempt = AcaVirtualQuizAttempt::where('activity_id', $this->quiz->id)->firstOrFail();

        $this->assertTrue($attempt->expires_at->equalTo($this->quiz->fresh()->close_at));
    }

    public function test_el_examen_se_cierra_solo_cuando_termina_el_tiempo_del_intento(): void
    {
        $this->actingAs($this->user)
            ->post(route('colegio.secundaria.quiz.start', $this->quiz->id));

        $attempt = AcaVirtualQuizAttempt::where('activity_id', $this->quiz->id)->firstOrFail();
        $attempt->update(['expires_at' => now()->subMinute()]);

        $this->actingAs($this->user)
            ->get(route('colegio.secundaria.quiz', $this->quiz->id))
            ->assertRedirect(route('colegio.secundaria.quiz.result', $this->quiz->id));

        $attempt->refresh();
        $this->assertSame(AcaVirtualQuizAttempt::STATUS_FINISHED, $attempt->status);
        $this->assertSame(0.0, (float) $attempt->score);

        $this->actingAs($this->user)
            ->get(route('colegio.secundaria.quiz.result', $this->quiz->id))
            ->assertOk()
            ->assertSee('data-result-correct>0</strong>', false)
            ->assertSee('data-result-incorrect>2</strong>', false);
    }

    public function test_el_orden_de_preguntas_y_alternativas_se_guarda_por_intento(): void
    {
        $this->actingAs($this->user)
            ->post(route('colegio.secundaria.quiz.start', $this->quiz->id));

        $attempt = AcaVirtualQuizAttempt::where('activity_id', $this->quiz->id)->firstOrFail();

        // Se fija un orden distinto para comprobar que la vista respeta el que
        // quedó guardado al iniciar, no el orden original de las preguntas.
        $attempt->update([
            'question_order' => [$this->short->id, $this->choice->id],
            'option_order' => [
                $this->choice->id => [$this->wrongOption->id, $this->correctOption->id],
            ],
        ]);

        $html = $this->actingAs($this->user)
            ->get(route('colegio.secundaria.quiz', $this->quiz->id))
            ->assertOk()
            ->getContent();

        $shortPosition = strpos($html, 'id="pregunta-' . $this->short->id . '"');
        $choicePosition = strpos($html, 'id="pregunta-' . $this->choice->id . '"');
        $wrongPosition = strpos($html, $this->wrongOption->text);
        $correctPosition = strpos($html, $this->correctOption->text);

        $this->assertNotFalse($shortPosition);
        $this->assertNotFalse($choicePosition);
        $this->assertNotFalse($wrongPosition);
        $this->assertNotFalse($correctPosition);
        $this->assertLessThan($choicePosition, $shortPosition);
        $this->assertLessThan($correctPosition, $wrongPosition);
    }

    public function test_un_envio_fuera_de_tiempo_cierra_el_intento_con_cero(): void
    {
        $this->actingAs($this->user)
            ->post(route('colegio.secundaria.quiz.start', $this->quiz->id));

        $attempt = AcaVirtualQuizAttempt::where('activity_id', $this->quiz->id)->firstOrFail();
        $attempt->update(['expires_at' => now()->subMinutes(2)]);

        $this->actingAs($this->user)
            ->post(route('colegio.secundaria.quiz.submit', $this->quiz->id), [
                'answers' => [
                    $this->choice->id => ['option_ids' => [$this->correctOption->id]],
                    $this->short->id => ['answer_text' => 'solar'],
                ],
            ])
            ->assertRedirect(route('colegio.secundaria.quiz.result', $this->quiz->id));

        $this->assertSame(0.0, (float) $attempt->fresh()->score);
    }

    public function test_el_resultado_muestra_aciertos_y_errores_sin_revelar_respuestas(): void
    {
        $this->actingAs($this->user)
            ->post(route('colegio.secundaria.quiz.start', $this->quiz->id));

        $this->actingAs($this->user)
            ->post(route('colegio.secundaria.quiz.submit', $this->quiz->id), [
                'answers' => [
                    $this->choice->id => ['option_ids' => [$this->correctOption->id]],
                    $this->short->id => ['answer_text' => 'carbón'],
                ],
            ]);

        $this->actingAs($this->user)
            ->get(route('colegio.secundaria.quiz.result', $this->quiz->id))
            ->assertOk()
            ->assertSee('Aciertos')
            ->assertSee('Errores')
            ->assertSee('data-result-correct>1</strong>', false)
            ->assertSee('data-result-incorrect>1</strong>', false)
            ->assertDontSee('Luz solar en energía eléctrica')
            ->assertDontSee('solar');
    }

    private function submitQuiz(bool $correct)
    {
        $answers = [
            $this->choice->id => [
                'option_ids' => [($correct ? $this->correctOption : $this->wrongOption)->id],
            ],
            $this->short->id => [
                'answer_text' => $correct ? 'solar' : 'carbón',
            ],
        ];

        $this->actingAs($this->user)
            ->post(route('colegio.secundaria.quiz.start', $this->quiz->id))
            ->assertRedirect(route('colegio.secundaria.quiz', $this->quiz->id));

        return $this->actingAs($this->user)
            ->post(route('colegio.secundaria.quiz.submit', $this->quiz->id), [
                'answers' => $answers,
            ]);
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
