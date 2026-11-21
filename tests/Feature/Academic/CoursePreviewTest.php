<?php

namespace Tests\Feature\Academic;

use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Academic\Entities\AcaContent;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaExam;
use Modules\Academic\Entities\AcaExamAnswer;
use Modules\Academic\Entities\AcaExamQuestion;
use Modules\Academic\Entities\AcaModule;
use Modules\Academic\Entities\AcaSubscriptionType;
use Modules\Academic\Entities\AcaTheme;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CoursePreviewTest extends TestCase
{
    use RefreshDatabase;

    private AcaCourse $course;

    private AcaModule $module;

    private AcaTheme $theme;

    private AcaSubscriptionType $subscriptionType;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $permissions = [
            Permission::firstOrCreate(['name' => 'aca_cursos_listado', 'guard_name' => 'web']),
            // HandleInertiaRequests comparte la guía funcional y consulta este permiso.
            Permission::firstOrCreate(['name' => 'roles', 'guard_name' => 'web']),
        ];

        foreach (['admin', 'Administrador', 'Alumno', 'Docente'] as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->givePermissionTo($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->course = AcaCourse::create([
            'description' => 'Curso de prueba para vista previa',
            'type_description' => 'Curso',
            'price' => 150,
            'status' => true,
            'certificate_title' => 'Certificado de prueba',
            'certificate_description' => 'Curso de prueba',
        ]);

        $this->module = AcaModule::create([
            'course_id' => $this->course->id,
            'position' => 1,
            'description' => 'Módulo inicial',
        ]);

        $this->theme = AcaTheme::create([
            'module_id' => $this->module->id,
            'position' => 1,
            'description' => 'Clase inicial',
        ]);

        $contents = [
            ['description' => 'Video embebido', 'content' => '<iframe src="https://www.youtube.com/embed/abc"></iframe>', 'is_file' => 0],
            ['description' => 'Enlace externo', 'content' => 'https://example.com/recurso', 'is_file' => 1],
            ['description' => 'Archivo PDF', 'content' => 'academic/archivo.pdf', 'is_file' => 2],
            ['description' => 'Zoom de la clase', 'content' => 'https://zoom.us/j/123', 'is_file' => 3],
            ['description' => 'Examen de la clase', 'content' => '', 'is_file' => 4],
            ['description' => 'Webinar grabado', 'content' => '<iframe src="https://vimeo.com/event/1/embed"></iframe>', 'is_file' => 5],
        ];

        foreach ($contents as $index => $content) {
            AcaContent::create([
                'theme_id' => $this->theme->id,
                'position' => $index + 1,
                'description' => $content['description'],
                'content' => $content['content'],
                'is_file' => $content['is_file'],
            ]);
        }

        $examContent = AcaContent::where('theme_id', $this->theme->id)
            ->where('is_file', 4)
            ->firstOrFail();

        $this->createExam($this->course->id, $this->module->id, $examContent->id, false, 'Examen de la clase');
        $this->createExam($this->course->id, $this->module->id, null, false, 'Examen del módulo');
        $this->createExam($this->course->id, $this->module->id, null, true, 'Simulacro del módulo');
        $this->createExam($this->course->id, null, null, false, 'Examen final del curso');

        $this->subscriptionType = AcaSubscriptionType::create([
            'title' => 'Plan Básico',
            'status' => true,
            'order_number' => 1,
            'access_all_courses' => true,
            'access_specialization_programs' => false,
            'access_all_webinars' => false,
            'can_view_specialization_zooms' => false,
            'can_view_specialization_webinars' => false,
        ]);
    }

    public function test_admin_ve_el_listado_y_el_modulo_con_la_vista_del_alumno(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->get(route('aca_courses_preview', $this->course->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Academic::Students/Lessons', false)
                ->where('preview.mode', 'all')
                ->where('preview.mode_label', 'Todo el contenido (sin filtros)')
                ->where('preview.has_access', true)
                ->where('preview.prompt_reset', true)
                ->has('course.modules', 1)
                ->where('course.modules.0.description', 'Módulo inicial')
                ->where('preview.modes', fn ($modes) => collect($modes)->contains(fn ($mode) => $mode['value'] === 'subscription:'.$this->subscriptionType->id)
                    && collect($modes)->contains(fn ($mode) => $mode['value'] === 'purchase'))
            );

        $this->actingAs($admin)
            ->get(route('aca_courses_preview_module', [
                'id' => $this->course->id,
                'moduleId' => $this->module->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Academic::Students/Themes', false)
                ->where('preview.mode', 'all')
                ->where('preview.prompt_reset', false)
                ->where('module.description', 'Módulo inicial')
                ->where('module.themes.0.progress', 0)
                ->where('module.exam.student_exams', [])
                ->where('module.mock_exam.student_exams', [])
                ->has('module.exam.questions', 1)
                ->has('module.mock_exam.questions', 1)
                ->where('module.themes.0.contents', fn ($contents) => count($contents) === 6
                    && collect($contents)->contains(fn ($content) => (int) $content['is_file'] === 4
                        && count($content['exam']['questions'] ?? []) === 1))
            );
    }

    public function test_rol_administrador_tambien_puede_entrar(): void
    {
        $this->actingAs($this->userWithRole('Administrador'))
            ->get(route('aca_courses_preview', $this->course->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Academic::Students/Lessons', false)
                ->where('preview.mode', 'all'));
    }

    public function test_docente_y_alumno_reciben_403(): void
    {
        foreach (['Docente', 'Alumno'] as $role) {
            $user = $this->userWithRole($role);

            $this->actingAs($user)
                ->get(route('aca_courses_preview', $this->course->id))
                ->assertForbidden();

            $this->actingAs($user)
                ->get(route('aca_courses_preview_module', [
                    'id' => $this->course->id,
                    'moduleId' => $this->module->id,
                ]))
                ->assertForbidden();
        }
    }

    public function test_modo_curso_comprado_simula_matricula_y_oculta_webinars(): void
    {
        $this->actingAs($this->userWithRole('admin'))
            ->get(route('aca_courses_preview_module', [
                'id' => $this->course->id,
                'moduleId' => $this->module->id,
                'mode' => 'purchase',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Academic::Students/Themes', false)
                ->where('preview.mode', 'purchase')
                ->where('preview.mode_label', 'Curso comprado (matrícula)')
                ->where('module.themes.0.contents', fn ($contents) => count($contents) === 5
                    && ! collect($contents)->contains(fn ($content) => (int) $content['is_file'] === 5))
            );
    }

    public function test_suscripcion_de_especializacion_sin_permisos_oculta_zoom_y_webinar(): void
    {
        $this->course->update(['type_description' => 'Programas de Especialización']);

        $this->subscriptionType->update([
            'access_all_courses' => false,
            'access_specialization_programs' => true,
            'can_view_specialization_zooms' => false,
            'can_view_specialization_webinars' => false,
        ]);

        $this->actingAs($this->userWithRole('admin'))
            ->get(route('aca_courses_preview_module', [
                'id' => $this->course->id,
                'moduleId' => $this->module->id,
                'mode' => 'subscription:'.$this->subscriptionType->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Academic::Students/Themes', false)
                ->where('preview.has_access', true)
                ->where('module.themes.0.contents', fn ($contents) => count($contents) === 4)
            );
    }

    public function test_suscripcion_de_especializacion_con_permisos_muestra_zoom_y_webinar(): void
    {
        $this->course->update(['type_description' => 'Programas de Especialización']);

        $this->subscriptionType->update([
            'access_all_courses' => false,
            'access_specialization_programs' => true,
            'can_view_specialization_zooms' => true,
            'can_view_specialization_webinars' => true,
        ]);

        $this->actingAs($this->userWithRole('admin'))
            ->get(route('aca_courses_preview_module', [
                'id' => $this->course->id,
                'moduleId' => $this->module->id,
                'mode' => 'subscription:'.$this->subscriptionType->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Academic::Students/Themes', false)
                ->where('module.themes.0.contents', fn ($contents) => count($contents) === 6)
            );
    }

    public function test_suscripcion_que_no_incluye_el_curso_muestra_estado_sin_acceso(): void
    {
        $this->subscriptionType->update([
            'access_all_courses' => false,
            'access_specialization_programs' => false,
            'access_all_webinars' => false,
        ]);

        $this->actingAs($this->userWithRole('admin'))
            ->get(route('aca_courses_preview', [
                'id' => $this->course->id,
                'mode' => 'subscription:'.$this->subscriptionType->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Academic::Students/Lessons', false)
                ->where('preview.has_access', false)
                ->has('course.modules', 0)
            );
    }

    public function test_modulo_de_otro_curso_devuelve_404(): void
    {
        $otroCurso = AcaCourse::create([
            'description' => 'Otro curso',
            'type_description' => 'Curso',
            'price' => 100,
            'status' => true,
            'certificate_title' => 'Certificado otro curso',
            'certificate_description' => 'Otro curso',
        ]);

        $this->actingAs($this->userWithRole('admin'))
            ->get(route('aca_courses_preview_module', [
                'id' => $otroCurso->id,
                'moduleId' => $this->module->id,
            ]))
            ->assertNotFound();
    }

    private function createExam(int $courseId, ?int $moduleId, ?int $contentId, bool $isMock, string $title): AcaExam
    {
        $exam = AcaExam::create([
            'course_id' => $courseId,
            'module_id' => $moduleId,
            'content_id' => $contentId,
            'description' => $title,
            'date_start' => now()->subDay()->toDateTimeString(),
            'date_end' => now()->addDay()->toDateTimeString(),
            'status' => true,
            'is_mock' => $isMock,
            'attempts' => 2,
            'duration_minutes' => 30,
        ]);

        $question = AcaExamQuestion::create([
            'exam_id' => $exam->id,
            'description' => '¿Pregunta de prueba?',
            'score' => 10,
            'type_answers' => 'Alternativas',
        ]);

        AcaExamAnswer::create([
            'question_id' => $question->id,
            'description' => 'Alternativa correcta',
            'correct' => true,
            'score' => 10,
        ]);

        AcaExamAnswer::create([
            'question_id' => $question->id,
            'description' => 'Alternativa incorrecta',
            'correct' => false,
            'score' => 0,
        ]);

        return $exam;
    }

    private function userWithRole(string $roleName): User
    {
        $this->sequence++;

        $person = Person::create([
            'number' => (string) (92000000 + $this->sequence),
            'names' => 'Admin',
            'father_lastname' => 'Prueba',
            'mother_lastname' => 'Preview',
            'full_name' => 'Admin Prueba Preview '.$this->sequence,
        ]);

        $user = User::factory()->create(['person_id' => $person->id]);
        // Evita el redirect del campus del alumno cuando el perfil está incompleto.
        $user->forceFill(['updated_information' => true])->save();
        $user->assignRole($roleName);

        return $user;
    }
}
