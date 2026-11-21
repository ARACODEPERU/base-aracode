<?php

namespace Tests\Feature\Academic;

use App\Models\Person;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Laravel\Mcp\Request as McpRequest;
use Laravel\Mcp\Response;
use Modules\Academic\Entities\AcaCapRegistration;
use Modules\Academic\Entities\AcaCategoryCourse;
use Modules\Academic\Entities\AcaContent;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaExam;
use Modules\Academic\Entities\AcaModality;
use Modules\Academic\Entities\AcaModule;
use Modules\Academic\Entities\AcaStudent;
use Modules\Academic\Entities\AcaStudentAttendance;
use Modules\Academic\Entities\AcaStudentExam;
use Modules\Academic\Entities\AcaStudentHistory;
use Modules\Academic\Entities\AcaStudentSubscription;
use Modules\Academic\Entities\AcaSubscriptionType;
use Modules\Academic\Entities\AcaTeacher;
use Modules\Academic\Entities\AcaTeacherCourse;
use Modules\Academic\Entities\AcaTheme;
use Modules\Academic\Mcp\Tools\AcademicCheckStudentAccessTool;
use Modules\Academic\Mcp\Tools\AcademicExpiringSubscriptionsTool;
use Modules\Academic\Mcp\Tools\AcademicGetStudentTool;
use Modules\Academic\Mcp\Tools\AcademicGetTeacherTool;
use Modules\Academic\Mcp\Tools\AcademicListStudentsTool;
use Modules\Academic\Mcp\Tools\AcademicOverviewTool;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AcademicMcpInsightsTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    private AcaCourse $curso;

    private AcaCourse $cursoGratuito;

    private AcaModule $modulo;

    private AcaContent $contenidoUno;

    private AcaContent $contenidoDos;

    private AcaStudent $alumna;

    private AcaStudent $alumnoVencido;

    private AcaTeacher $docente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $categoria = AcaCategoryCourse::create(['description' => 'Tecnología']);
        $modalidadId = (int) AcaModality::query()->value('id');

        $this->curso = AcaCourse::create([
            'status' => true,
            'description' => 'Excel Aplicado',
            'category_id' => $categoria->id,
            'modality_id' => $modalidadId,
            'type_description' => 'Curso',
            'price' => 120,
            'certificate_title' => 'Excel Aplicado',
        ]);

        $this->cursoGratuito = AcaCourse::create([
            'status' => true,
            'description' => 'Introducción gratuita',
            'category_id' => $categoria->id,
            'modality_id' => $modalidadId,
            'type_description' => 'Curso',
            'price' => 0,
            'certificate_title' => 'Introducción gratuita',
        ]);

        $this->modulo = AcaModule::create([
            'course_id' => $this->curso->id,
            'position' => '1',
            'description' => 'Fundamentos',
        ]);
        $tema = AcaTheme::create([
            'module_id' => $this->modulo->id,
            'position' => '1',
            'description' => 'Primera clase',
        ]);
        $this->contenidoUno = AcaContent::create([
            'theme_id' => $tema->id,
            'position' => '1',
            'description' => 'Lectura inicial',
            'is_file' => 1,
        ]);
        $this->contenidoDos = AcaContent::create([
            'theme_id' => $tema->id,
            'position' => '2',
            'description' => 'Práctica',
            'is_file' => 1,
        ]);

        $this->usuario = $this->usuarioConPermisos(
            'aca_estudiante_listado',
            'aca_docente_listado',
            'aca_cursos_listado',
            'roles'
        );

        $this->alumna = $this->crearEstudiante('Ana Alumna', '71000001', 'ana@example.test');
        $this->alumnoVencido = $this->crearEstudiante('Beto Vencido', '71000002', 'beto@example.test');

        $plan = AcaSubscriptionType::create([
            'title' => 'Plan Total',
            'status' => '1',
            'access_all_courses' => true,
        ]);

        AcaStudentSubscription::create([
            'student_id' => $this->alumna->id,
            'subscription_id' => $plan->id,
            'date_start' => Carbon::today()->subMonth()->toDateString(),
            'date_end' => Carbon::today()->addDays(5)->toDateString(),
            'status' => true,
        ]);

        AcaStudentSubscription::create([
            'student_id' => $this->alumnoVencido->id,
            'subscription_id' => $plan->id,
            'date_start' => Carbon::today()->subMonths(2)->toDateString(),
            'date_end' => Carbon::today()->subDays(5)->toDateString(),
            'status' => false,
        ]);

        AcaCapRegistration::create([
            'student_id' => $this->alumna->id,
            'course_id' => $this->curso->id,
            'status' => true,
            'unlimited' => true,
        ]);

        AcaCapRegistration::create([
            'student_id' => $this->alumnoVencido->id,
            'course_id' => $this->curso->id,
            'status' => true,
            'unlimited' => false,
            'date_start' => Carbon::today()->subMonths(2)->toDateString(),
            'date_end' => Carbon::today()->subDay()->toDateString(),
        ]);

        AcaStudentHistory::create([
            'user_id' => $this->usuario->id,
            'person_id' => $this->alumna->person_id,
            'course_id' => $this->curso->id,
            'module_id' => $this->modulo->id,
            'theme_id' => $tema->id,
            'content_id' => $this->contenidoUno->id,
            'type_content' => 'Contenido',
        ]);

        $examen = AcaExam::create([
            'course_id' => $this->curso->id,
            'module_id' => $this->modulo->id,
            'theme_id' => $tema->id,
            'description' => 'Examen de Excel',
            'status' => true,
            'is_mock' => false,
        ]);

        AcaStudentExam::create([
            'exam_id' => $examen->id,
            'student_id' => $this->alumna->id,
            'punctuation' => '15',
            'status' => 'calificado',
            'date_start' => now(),
        ]);

        AcaStudentAttendance::create([
            'student_id' => $this->alumna->id,
            'course_id' => $this->curso->id,
            'module_id' => $this->modulo->id,
            'content_id' => $this->contenidoUno->id,
            'registered_at' => now(),
            'observation' => 'Asistió a la clase en vivo.',
        ]);

        $this->docente = $this->crearDocente('Docente de Excel', '71000003', 'docente@example.test');
        AcaTeacherCourse::create([
            'teacher_id' => $this->docente->id,
            'course_id' => $this->curso->id,
        ]);
    }

    public function test_lista_estudiantes_con_cursos_y_estado_de_suscripcion(): void
    {
        $datos = $this->leerJson($this->herramienta(AcademicListStudentsTool::class, [
            'per_page' => 10,
        ]));

        $this->assertSame(2, $datos['total']);

        $ana = collect($datos['students'])->firstWhere('student_id', $this->alumna->id);
        $this->assertSame('Ana Alumna', $ana['name']);
        $this->assertSame(1, $ana['courses_count']);
        $this->assertSame(1, $ana['active_courses_count']);
        $this->assertSame('por_vencer', $ana['subscription_status']);
        $this->assertSame(Carbon::today()->addDays(5)->toDateString(), $ana['subscription_ends_at']);
        $this->assertStringNotContainsString('password', strtolower((string) json_encode($datos)));
    }

    public function test_ficha_del_estudiante_incluye_avance_examen_y_asistencia(): void
    {
        $datos = $this->leerJson($this->herramienta(AcademicGetStudentTool::class, [
            'student_id' => $this->alumna->id,
        ]));

        $this->assertSame($this->alumna->id, $datos['student_id']);
        $this->assertSame(1, $datos['summary']['courses_count']);
        $this->assertSame(50, $datos['courses'][0]['progress']['percent']);
        $this->assertSame(1, $datos['summary']['exams']['attempts']);
        $this->assertEquals(15.0, $datos['summary']['exams']['average_score']);
        $this->assertSame(1, $datos['attendance']['records']);
        $this->assertSame('Asistió a la clase en vivo.', $datos['attendance']['recent'][0]['observation']);
        $this->assertSame('por_vencer', $datos['subscription_summary']['status']);
        $this->assertContains('matricula', $datos['courses'][0]['access']['sources']);
        $this->assertContains('suscripcion', $datos['courses'][0]['access']['sources']);
        $this->assertStringNotContainsString('password', strtolower((string) json_encode($datos)));
    }

    public function test_verifica_acceso_por_suscripcion_matricula_y_curso_gratuito(): void
    {
        $conAcceso = $this->leerJson($this->herramienta(AcademicCheckStudentAccessTool::class, [
            'student_id' => $this->alumna->id,
            'course_id' => $this->curso->id,
        ]));
        $this->assertTrue($conAcceso['can_access']);
        $this->assertContains('matricula', $conAcceso['sources']);
        $this->assertContains('suscripcion', $conAcceso['sources']);

        $vencido = $this->leerJson($this->herramienta(AcademicCheckStudentAccessTool::class, [
            'student_id' => $this->alumnoVencido->id,
            'course_id' => $this->curso->id,
        ]));
        $this->assertFalse($vencido['can_access']);

        $gratuito = $this->leerJson($this->herramienta(AcademicCheckStudentAccessTool::class, [
            'student_id' => $this->alumnoVencido->id,
            'course_id' => $this->cursoGratuito->id,
        ]));
        $this->assertTrue($gratuito['can_access']);
        $this->assertContains('curso_gratuito', $gratuito['sources']);
    }

    public function test_resumen_academico_y_suscripciones_por_vencer(): void
    {
        $resumen = $this->leerJson($this->herramienta(AcademicOverviewTool::class, [
            'days' => 30,
            'top' => 5,
        ]));

        $this->assertSame(2, $resumen['totals']['students']);
        $this->assertSame(50.0, (float) $resumen['progress']['average_percent']);
        $this->assertSame(1, $resumen['subscriptions']['expiring_in_15_days']);
        $this->assertSame(1, $resumen['exams']['attempts']);
        $this->assertSame(1, $resumen['attendance']['records']);
        $this->assertSame($this->curso->id, $resumen['top_courses_by_enrollment'][0]['course_id']);

        $vencimientos = $this->leerJson($this->herramienta(AcademicExpiringSubscriptionsTool::class, [
            'days' => 15,
        ]));
        $this->assertSame(1, $vencimientos['total']);
        $this->assertSame($this->alumna->id, $vencimientos['subscriptions'][0]['student_id']);
        $this->assertSame(5, $vencimientos['subscriptions'][0]['days_remaining']);
        $this->assertStringNotContainsString('password', strtolower((string) json_encode($vencimientos)));
    }

    public function test_docente_incluye_cursos_alumnos_y_promedios(): void
    {
        $datos = $this->leerJson($this->herramienta(AcademicGetTeacherTool::class, [
            'teacher_id' => $this->docente->id,
        ]));

        $this->assertSame($this->docente->id, $datos['teacher_id']);
        $this->assertSame('Docente de Excel', $datos['name']);
        $this->assertSame(1, $datos['summary']['courses_count']);
        $this->assertSame(2, $datos['summary']['students_count']);
        $this->assertSame(50.0, (float) $datos['courses'][0]['average_progress']);
        $this->assertStringNotContainsString('password', strtolower((string) json_encode($datos)));
    }

    public function test_las_herramientas_nuevas_son_de_solo_lectura(): void
    {
        $clases = [
            AcademicListStudentsTool::class,
            AcademicGetStudentTool::class,
            AcademicCheckStudentAccessTool::class,
            AcademicOverviewTool::class,
            AcademicExpiringSubscriptionsTool::class,
            AcademicGetTeacherTool::class,
        ];

        foreach ($clases as $clase) {
            $anotaciones = (new \ReflectionClass($clase))->getAttributes();
            $nombres = Collection::make($anotaciones)->map->getName();

            $this->assertTrue(
                $nombres->contains(\Laravel\Mcp\Server\Tools\Annotations\IsReadOnly::class),
                $clase . ' debe estar marcada como IsReadOnly.'
            );
        }
    }

    private function crearEstudiante(string $nombre, string $documento, string $correo): AcaStudent
    {
        $persona = Person::create([
            'number' => $documento,
            'names' => $nombre,
            'father_lastname' => 'Prueba',
            'mother_lastname' => 'Mcp',
            'full_name' => $nombre,
            'email' => $correo,
            'telephone' => '999999999',
        ]);

        return AcaStudent::create([
            'student_code' => 'ALU-' . $documento,
            'person_id' => $persona->id,
        ]);
    }

    private function crearDocente(string $nombre, string $documento, string $correo): AcaTeacher
    {
        $persona = Person::create([
            'number' => $documento,
            'names' => $nombre,
            'father_lastname' => 'Prueba',
            'mother_lastname' => 'Docente',
            'full_name' => $nombre,
            'email' => $correo,
            'ocupacion' => 'Especialista',
        ]);

        return AcaTeacher::create([
            'teacher_code' => 'DOC-' . $documento,
            'person_id' => $persona->id,
        ]);
    }

    private function herramienta(string $clase, array $argumentos): Response
    {
        $this->actingAs($this->usuario);

        return app($clase)->handle(new McpRequest($argumentos));
    }

    private function leerJson(Response $respuesta): array
    {
        return json_decode((string) $respuesta->content(), true) ?: [];
    }

    private function usuarioConPermisos(string ...$permisos): User
    {
        static $secuencia = 0;
        $secuencia++;

        $persona = Person::create([
            'number' => (string) (95000000 + $secuencia),
            'names' => 'Agente',
            'father_lastname' => 'Insights',
            'mother_lastname' => 'Prueba',
            'full_name' => 'Agente Insights ' . $secuencia,
        ]);

        $usuario = User::factory()->create(['person_id' => $persona->id]);

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
            $usuario->givePermissionTo($permiso);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $usuario;
    }
}
