<?php

namespace Tests\Feature\Academic;

use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Mcp\Request as McpRequest;
use Laravel\Mcp\Response;
use Modules\Academic\Entities\AcaCategoryCourse;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaCourseLanding;
use Modules\Academic\Entities\AcaModality;
use Modules\Academic\Entities\AcaTeacher;
use Modules\Academic\Entities\AcaTeacherCourse;
use Modules\Academic\Mcp\Tools\AcademicGetCourseLandingTool;
use Modules\Academic\Mcp\Tools\AcademicGetPermissionsTool;
use Modules\Academic\Mcp\Tools\AcademicSetBrochureFileTool;
use Modules\Academic\Mcp\Tools\AcademicSetCourseLandingTool;
use Modules\Academic\Mcp\Tools\AcademicSetCourseTeachersTool;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AcademicMcpLandingTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    private AcaCourse $curso;

    private AcaTeacher $docenteUno;

    private AcaTeacher $docenteDos;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->withoutVite();

        $categoria = AcaCategoryCourse::create(['description' => 'Tecnología']);
        $this->curso = AcaCourse::create([
            'status' => false,
            'description' => 'Curso de landing',
            'category_id' => $categoria->id,
            'modality_id' => (int) AcaModality::query()->value('id'),
            'type_description' => 'Curso',
            'certificate_title' => 'Curso de landing',
        ]);

        $this->docenteUno = $this->crearDocente('Docente Uno', 'Analista de datos');
        $this->docenteDos = $this->crearDocente('Docente Dos', 'Especialista en IA');

        $this->usuario = $this->usuarioConPermisos('aca_cursos_listado', 'aca_cursos_editar', 'roles');
    }

    public function test_crea_la_landing_completa_desde_mcp(): void
    {
        $this->herramienta(AcademicSetCourseTeachersTool::class, [
            'course_id' => $this->curso->id,
            'teacher_ids' => [$this->docenteUno->id, $this->docenteDos->id],
            'main_teacher_id' => $this->docenteUno->id,
        ]);

        $respuesta = $this->herramienta(AcademicSetCourseLandingTool::class, [
            'course_id' => $this->curso->id,
            'url_slug' => 'Curso de Landing 2027',
            'banner_start_date' => '2027-03-01',
            'banner_end_date' => '2027-03-31',
            'banner_language' => 'es',
            'banner_video_link' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'whatsapp_link' => 'https://wa.me/51999999999',
            'is_published' => true,
            'professional' => [
                'name' => 'Actualización Profesional',
                'title' => 'Aprende haciendo',
                'items' => [[
                    'icon' => 'fa-briefcase',
                    'title' => 'Aplicación real',
                    'description' => 'Casos de trabajo.',
                ]],
            ],
            'staff' => [
                'name' => 'Nuestro Staff',
                'teacher_ids' => [$this->docenteUno->id, $this->docenteDos->id],
            ],
            'results' => [
                'items' => [[
                    'icon' => 'fa-chart-line',
                    'title' => 'Mejora medible',
                    'description' => 'Resultados del programa.',
                ]],
            ],
            'testimonials' => [
                'items' => [[
                    'name' => 'Alumna de prueba',
                    'presentation' => 'Contadora',
                    'description' => 'Aprendí a aplicar IA.',
                ]],
            ],
            'study_plan' => [
                'title' => 'Ruta de aprendizaje',
                'items' => [
                    ['number' => 1, 'title' => 'Fundamentos', 'description' => 'Primeros pasos.'],
                    ['number' => 2, 'title' => 'Aplicación', 'description' => 'Casos reales.'],
                ],
            ],
            'problem' => [
                'items' => [[
                    'icon' => 'fa-exclamation-triangle',
                    'title' => 'Falta de tiempo',
                    'description' => 'La operación consume el día.',
                ]],
            ],
            'investment' => [
                'items' => [[
                    'tag' => 'Recomendado',
                    'title' => 'Plan completo',
                    'price_now' => '299',
                    'features' => ['Acceso al curso', 'Certificado'],
                ]],
            ],
            'faq' => [
                'items' => [[
                    'question' => '¿Es en vivo?',
                    'answer' => 'Sí, con grabaciones.',
                ]],
            ],
            'utm_config' => [
                'campaign' => 'curso_landing',
                'channels' => [[
                    'id' => 'facebook',
                    'term' => 'ia',
                    'active' => true,
                ]],
            ],
        ]);

        $this->assertFalse($respuesta->isError());

        $landing = AcaCourseLanding::firstOrFail();
        $this->assertSame('curso-de-landing-2027', $landing->url_slug);
        $this->assertTrue((bool) $landing->is_published);
        $this->assertSame(31, $landing->banner_duration);
        $this->assertStringContainsString('youtube.com/embed', $landing->banner_video_link);
        $this->assertSame('Aprende haciendo', $landing->professional_section['title']);
        $this->assertSame('Nuestro Staff', $landing->staff_section['name']);
        $this->assertCount(2, $landing->staff_section['teachers']);
        $this->assertStringContainsString('Docente Uno', $landing->staff_section['teachers'][0]['teacher_names']);
        $this->assertSame('Mejora medible', $landing->results_section['items'][0]['title']);
        $this->assertSame('Alumna de prueba', $landing->testimonials_section['items'][0]['name']);
        $this->assertSame(2, $landing->study_plan_section['items'][1]['number']);
        $this->assertSame('Falta de tiempo', $landing->problem_section['items'][0]['title']);
        $this->assertSame('299', $landing->investment_section['items'][0]['price_now']);
        $this->assertSame('¿Es en vivo?', $landing->faq_section['items'][0]['question']);
        $this->assertSame('curso_landing', $landing->utm_config['campaign']);

        $datos = $this->leerJson($respuesta);
        $this->assertSame($this->curso->id, $datos['landing']['course_id']);
        $this->assertSame('2027-03-01', $datos['landing']['settings']['banner_start_date']);
        $this->assertStringContainsString('Docente Dos', $datos['landing']['teachers'][1]['name']);

        $this->actingAs($this->usuario)
            ->get(route('aca_courses_landing_edit', $this->curso->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Academic::Courses/Landing', false)
                ->where('landing.url_slug', 'curso-de-landing-2027')
                ->has('landing.staff_section.teachers', 2)
                ->has('landing.faq_section.items', 1));
    }

    public function test_el_agente_puede_consultar_si_tiene_permiso_absoluto(): void
    {
        $this->actingAs($this->usuario);

        $token = $this->usuario->createToken('mcp-autonomo', [
            'academic:read',
            'academic:write',
            'academic:autonomous',
        ])->accessToken;

        $this->usuario->withAccessToken($token);

        $datos = $this->leerJson(app(AcademicGetPermissionsTool::class)->handle(new McpRequest([])));

        $this->assertSame('autonomo', $datos['mode']);
        $this->assertTrue($datos['autonomous']);
    }

    public function test_sin_permiso_absoluto_el_agente_debe_confirmar(): void
    {
        $this->actingAs($this->usuario);

        $token = $this->usuario->createToken('mcp-normal', [
            'academic:read',
            'academic:write',
        ])->accessToken;

        $this->usuario->withAccessToken($token);

        $datos = $this->leerJson(app(AcademicGetPermissionsTool::class)->handle(new McpRequest([])));

        $this->assertSame('confirmar', $datos['mode']);
        $this->assertFalse($datos['autonomous']);
    }

    public function test_actualiza_solo_una_seccion_sin_borrar_las_demas(): void
    {
        $this->herramienta(AcademicSetCourseLandingTool::class, [
            'course_id' => $this->curso->id,
            'staff' => ['teacher_ids' => [$this->docenteUno->id]],
            'faq' => ['items' => [['question' => 'Original', 'answer' => 'Respuesta']]],
            'study_plan' => ['items' => [['title' => 'Tema original']]],
        ]);

        $this->herramienta(AcademicSetCourseLandingTool::class, [
            'course_id' => $this->curso->id,
            'faq' => ['items' => [['question' => 'Actualizada', 'answer' => 'Nueva respuesta']]],
        ]);

        $landing = AcaCourseLanding::firstOrFail();
        $this->assertSame('Actualizada', $landing->faq_section['items'][0]['question']);
        $this->assertSame('Tema original', $landing->study_plan_section['items'][0]['title']);
        $this->assertStringContainsString('Docente Uno', $landing->staff_section['teachers'][0]['teacher_names']);
    }

    public function test_guarda_imagen_de_landing_y_pdf_del_brochure(): void
    {
        $png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

        $this->herramienta(AcademicSetCourseLandingTool::class, [
            'course_id' => $this->curso->id,
            'study_plan' => [
                'image_base64' => 'data:image/png;base64,' . $png,
                'items' => [['title' => 'Tema con imagen']],
            ],
            'testimonials' => [
                'items' => [[
                    'name' => 'Alumna con foto',
                    'image_base64' => 'data:image/png;base64,' . $png,
                ]],
            ],
        ]);

        $landing = AcaCourseLanding::firstOrFail();
        $this->assertStringStartsWith('uploads/landing/study_plan/', $landing->study_plan_section['image']);
        Storage::disk('public')->assertExists($landing->study_plan_section['image']);
        $this->assertStringStartsWith('uploads/landing/testimonials/', $landing->testimonials_section['items'][0]['image']);

        $respuesta = $this->herramienta(AcademicSetBrochureFileTool::class, [
            'course_id' => $this->curso->id,
            'filename' => 'brochure.pdf',
            'file_base64' => base64_encode("%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF"),
        ]);

        $this->assertFalse($respuesta->isError());
        $this->assertDatabaseHas('aca_brochures', ['course_id' => $this->curso->id]);
        $this->assertStringContainsString('/storage/uploads/brochures/', $this->leerJson($respuesta)['brochure']['file']);
    }

    public function test_asigna_docentes_y_encargado_del_curso(): void
    {
        $respuesta = $this->herramienta(AcademicSetCourseTeachersTool::class, [
            'course_id' => $this->curso->id,
            'teacher_ids' => [$this->docenteUno->id, $this->docenteDos->id],
            'main_teacher_id' => $this->docenteDos->id,
        ]);

        $this->assertFalse($respuesta->isError());
        $this->curso->refresh();
        $this->assertSame($this->docenteDos->id, $this->curso->teacher_id);
        $this->assertSame(2, AcaTeacherCourse::where('course_id', $this->curso->id)->count());

        $datos = $this->leerJson($respuesta);
        $this->assertSame($this->docenteDos->id, $datos['main_teacher_id']);
    }

    public function test_get_landing_indica_lo_que_falta_y_permite_crearla(): void
    {
        $antes = $this->leerJson($this->herramienta(AcademicGetCourseLandingTool::class, [
            'course_id' => $this->curso->id,
        ]));
        $this->assertNull($antes['landing']);
        $this->assertSame('landing', $antes['missing'][0]);

        $this->herramienta(AcademicSetCourseLandingTool::class, [
            'course_id' => $this->curso->id,
            'banner_start_date' => '2027-03-01',
            'banner_end_date' => '2027-03-02',
        ]);

        $despues = $this->leerJson($this->herramienta(AcademicGetCourseLandingTool::class, [
            'course_id' => $this->curso->id,
        ]));

        $this->assertSame($this->curso->id, $despues['landing']['course_id']);
        $this->assertContains('imagen_de_portada', $despues['landing']['missing']);
        $this->assertSame('2027-03-01', $despues['landing']['settings']['banner_start_date']);
    }

    public function test_el_endpoint_publica_las_herramientas_de_landing_y_docentes(): void
    {
        $token = $this->usuario->createToken('mcp-landing', ['academic:read'])->plainTextToken;
        $cursor = null;
        $nombres = [];

        for ($pagina = 0; $pagina < 5; $pagina++) {
            $parametros = [];

            if ($cursor) {
                $parametros['cursor'] = $cursor;
            }

            $parametros['_meta'] = [
                'io.modelcontextprotocol/protocolVersion' => '2026-07-28',
                'io.modelcontextprotocol/clientCapabilities' => (object) [],
                'io.modelcontextprotocol/clientInfo' => ['name' => 'pest', 'version' => '1.0.0'],
            ];

            $respuesta = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'MCP-Protocol-Version' => '2026-07-28',
                'Mcp-Method' => 'tools/list',
                'Accept' => 'application/json, text/event-stream',
            ])->postJson('/mcp/academic', [
                'jsonrpc' => '2.0',
                'id' => $pagina + 1,
                'method' => 'tools/list',
                'params' => $parametros,
            ]);

            $this->assertSame(200, $respuesta->getStatusCode(), (string) $respuesta->getContent());
            $datos = json_decode((string) $respuesta->getContent(), true);
            $this->assertArrayNotHasKey('error', $datos, (string) $respuesta->getContent());

            foreach ($datos['result']['tools'] ?? [] as $tool) {
                $nombres[] = $tool['name'];
            }

            $cursor = $datos['result']['nextCursor'] ?? null;

            if (! $cursor) {
                break;
            }
        }

        $this->assertContains('academic_set_course_landing', $nombres);
        $this->assertContains('academic_set_course_teachers', $nombres);
        $this->assertContains('academic_set_brochure_file', $nombres);
        $this->assertContains('academic_update_active_course', $nombres);
        $this->assertContains('academic_edit_active_content', $nombres);
        $this->assertContains('academic_update_active_landing', $nombres);
        $this->assertContains('academic_manage_active_course_image', $nombres);
        $this->assertContains('academic_set_active_brochure_file', $nombres);
    }

    private function crearDocente(string $nombre, string $ocupacion): AcaTeacher
    {
        static $secuencia = 0;
        $secuencia++;

        $persona = Person::create([
            'number' => (string) (94000000 + $secuencia),
            'names' => $nombre,
            'father_lastname' => 'Prueba',
            'mother_lastname' => 'Landing',
            'full_name' => $nombre,
            'ocupacion' => $ocupacion,
        ]);

        return AcaTeacher::create([
            'teacher_code' => 'DOC-' . $secuencia,
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
            'number' => (string) (93000000 + $secuencia),
            'names' => 'Agente',
            'father_lastname' => 'Landing',
            'mother_lastname' => 'Prueba',
            'full_name' => 'Agente Landing Prueba ' . $secuencia,
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
