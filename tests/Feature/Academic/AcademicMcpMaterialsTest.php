<?php

namespace Tests\Feature\Academic;

use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Mcp\Request as McpRequest;
use Laravel\Mcp\Response;
use Laravel\Sanctum\Sanctum;
use Modules\Academic\Entities\AcaCategoryCourse;
use Modules\Academic\Entities\AcaContent;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaExam;
use Modules\Academic\Entities\AcaModality;
use Modules\Academic\Entities\AcaModule;
use Modules\Academic\Entities\AcaTheme;
use Modules\Academic\Mcp\Tools\AcademicAddLessonFilesTool;
use Modules\Academic\Mcp\Tools\AcademicAddLessonLinksTool;
use Modules\Academic\Mcp\Tools\AcademicCreateFinalExamTool;
use Modules\Academic\Mcp\Tools\AcademicCreateMockExamTool;
use Modules\Academic\Mcp\Tools\AcademicCreateModuleExamTool;
use Modules\Academic\Mcp\Tools\AcademicGetCourseTool;
use Modules\Academic\Mcp\Tools\AcademicUpdateContentTool;
use Modules\Academic\Mcp\Tools\AcademicValidateCourseTool;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Materiales y evaluaciones del curso desde MCP: archivos PDF/Excel/Word,
 * enlaces de videoconferencia y clase grabada, y exámenes de módulo con su
 * simulacro. Todo debe quedar igual que si se hubiera creado en el ERP.
 */
class AcademicMcpMaterialsTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    private AcaCourse $curso;

    private AcaModule $modulo;

    private AcaTheme $clase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->withoutVite();

        $categoria = AcaCategoryCourse::create(['description' => 'Herramientas']);

        $this->curso = AcaCourse::create([
            'status' => false,
            'description' => 'Curso de materiales',
            'category_id' => $categoria->id,
            'modality_id' => (int) AcaModality::query()->value('id'),
            'type_description' => 'Curso',
            'certificate_title' => 'Curso de materiales',
        ]);

        $this->modulo = AcaModule::create([
            'course_id' => $this->curso->id,
            'position' => '1',
            'description' => 'Módulo uno',
            'allow_certificate_download' => false,
        ]);

        $this->clase = AcaTheme::create([
            'module_id' => $this->modulo->id,
            'position' => '1',
            'description' => 'Clase uno',
        ]);

        $this->usuario = $this->usuarioConPermisos(
            'aca_cursos_listado',
            'aca_cursos_nuevo',
            'aca_cursos_editar',
            'aca_cursos_examen_final_crear',
            'aca_cursos_modulos_examen',
            'roles'
        );
    }

    public function test_sube_un_pdf_y_queda_como_archivo_descargable(): void
    {
        $respuesta = $this->herramienta(AcademicAddLessonFilesTool::class, [
            'theme_id' => $this->clase->id,
            'files' => [[
                'title' => 'Guía del módulo',
                'filename' => 'guia.pdf',
                'file_base64' => base64_encode("%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF"),
            ]],
        ]);

        $this->assertFalse($respuesta->isError());

        $contenido = AcaContent::firstOrFail();
        $this->assertSame('2', (string) $contenido->is_file);
        $this->assertSame('Guía del módulo', $contenido->description);
        $this->assertStringStartsWith('uploads/courses/content/', $contenido->content);
        Storage::disk('public')->assertExists($contenido->content);

        $datos = $this->leerJson($respuesta);
        $this->assertSame('Guía del módulo', $datos['files'][0]['title']);
        $this->assertStringContainsString('/storage/uploads/courses/content/', $datos['files'][0]['url']);
    }

    public function test_acepta_word_y_rechaza_extensiones_o_firmas_invalidas(): void
    {
        $this->herramienta(AcademicAddLessonFilesTool::class, [
            'theme_id' => $this->clase->id,
            'files' => [[
                'title' => 'Planilla',
                'filename' => 'planilla.docx',
                'file_base64' => base64_encode("PK\x03\x04contenido de un docx"),
            ]],
        ]);

        $this->assertSame('2', (string) AcaContent::firstOrFail()->is_file);

        try {
            $this->herramienta(AcademicAddLessonFilesTool::class, [
                'theme_id' => $this->clase->id,
                'files' => [[
                    'title' => 'Ejecutable',
                    'filename' => 'virus.exe',
                    'file_base64' => base64_encode('MZ contenido'),
                ]],
            ]);
            $this->fail('La herramienta debió rechazar un .exe.');
        } catch (ValidationException $excepcion) {
            $this->assertStringContainsString('PDF, Excel', $excepcion->getMessage());
        }

        try {
            $this->herramienta(AcademicAddLessonFilesTool::class, [
                'theme_id' => $this->clase->id,
                'files' => [[
                    'title' => 'Falso PDF',
                    'filename' => 'falso.pdf',
                    'file_base64' => base64_encode('esto no es un pdf'),
                ]],
            ]);
            $this->fail('La herramienta debió rechazar un PDF con firma falsa.');
        } catch (ValidationException $excepcion) {
            $this->assertStringContainsString('no corresponde', $excepcion->getMessage());
        }

        $this->assertSame(1, AcaContent::count());
    }

    public function test_crea_videoconferencias_y_clases_grabadas(): void
    {
        $respuesta = $this->herramienta(AcademicAddLessonLinksTool::class, [
            'theme_id' => $this->clase->id,
            'links' => [
                ['title' => 'Sesión en vivo', 'type' => 'zoom', 'url' => 'https://us02web.zoom.us/j/123456789'],
                ['title' => 'Sala de apoyo', 'type' => 'meet', 'url' => 'https://meet.google.com/abc-defg-hij'],
                ['title' => 'Reunión de Teams', 'type' => 'teams', 'url' => 'https://teams.microsoft.com/l/meetup-join/19%3ameeting_abc'],
                ['title' => 'Grabación de la clase', 'type' => 'vimeo', 'url' => 'https://vimeo.com/123456789'],
                ['title' => 'Repaso', 'type' => 'youtube', 'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
            ],
        ]);

        $this->assertFalse($respuesta->isError());
        $this->assertSame(5, AcaContent::count());

        $zoom = AcaContent::where('description', 'Sesión en vivo')->firstOrFail();
        $this->assertSame('3', (string) $zoom->is_file);
        $this->assertSame('https://us02web.zoom.us/j/123456789', $zoom->content);

        $meet = AcaContent::where('description', 'Sala de apoyo')->firstOrFail();
        $this->assertSame('3', (string) $meet->is_file);

        $teams = AcaContent::where('description', 'Reunión de Teams')->firstOrFail();
        $this->assertSame('3', (string) $teams->is_file);

        $vimeo = AcaContent::where('description', 'Grabación de la clase')->firstOrFail();
        $this->assertSame('0', (string) $vimeo->is_file);
        $this->assertStringContainsString('https://player.vimeo.com/video/123456789', $vimeo->content);
        $this->assertStringContainsString('<iframe', $vimeo->content);

        $youtube = AcaContent::where('description', 'Repaso')->firstOrFail();
        $this->assertSame('0', (string) $youtube->is_file);
        $this->assertStringContainsString('https://www.youtube.com/embed/dQw4w9WgXcQ', $youtube->content);
    }

    public function test_no_permite_reemplazar_un_video_por_texto(): void
    {
        $this->herramienta(AcademicAddLessonLinksTool::class, [
            'theme_id' => $this->clase->id,
            'links' => [
                ['title' => 'Video', 'type' => 'youtube', 'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
            ],
        ]);

        $video = AcaContent::firstOrFail();

        $this->expectException(ValidationException::class);

        $this->herramienta(AcademicUpdateContentTool::class, [
            'content_id' => $video->id,
            'html' => '<p>Esto no debería reemplazar el video</p>',
        ]);
    }

    public function test_rechaza_un_enlace_que_no_es_del_dominio_elegido(): void
    {
        $this->expectException(ValidationException::class);

        $this->herramienta(AcademicAddLessonLinksTool::class, [
            'theme_id' => $this->clase->id,
            'links' => [
                ['title' => 'Falsa grabación', 'type' => 'vimeo', 'url' => 'https://example.com/video'],
            ],
        ]);
    }

    public function test_rechaza_una_videoconferencia_de_teams_con_dominio_ajeno(): void
    {
        $this->expectException(ValidationException::class);

        $this->herramienta(AcademicAddLessonLinksTool::class, [
            'theme_id' => $this->clase->id,
            'links' => [
                ['title' => 'Teams falso', 'type' => 'teams', 'url' => 'https://us02web.zoom.us/j/123456789'],
            ],
        ]);
    }

    public function test_acepta_enlaces_de_archivo_de_drive_y_onedrive(): void
    {
        $respuesta = $this->herramienta(AcademicAddLessonLinksTool::class, [
            'theme_id' => $this->clase->id,
            'links' => [
                ['title' => 'Carpeta de Drive', 'type' => 'archivo', 'url' => 'https://drive.google.com/file/d/1AbCdEfGhIjKlMnOp/view?usp=sharing'],
                ['title' => 'Documento de OneDrive', 'type' => 'archivo', 'url' => 'https://1drv.ms/u/s!AbCdEfGhIjKlMnOp'],
            ],
        ]);

        $this->assertFalse($respuesta->isError());
        $this->assertSame(2, AcaContent::count());
        $this->assertSame('1', (string) AcaContent::where('description', 'Carpeta de Drive')->firstOrFail()->is_file);
        $this->assertSame('1', (string) AcaContent::where('description', 'Documento de OneDrive')->firstOrFail()->is_file);
    }

    public function test_el_examen_creado_por_mcp_abre_el_panel_de_preguntas(): void
    {
        $datos = $this->leerJson($this->herramienta(AcademicCreateModuleExamTool::class, [
            'module_id' => $this->modulo->id,
            'title' => 'Examen del módulo desde MCP',
            'date_start' => '2027-04-01 08:00',
            'date_end' => '2027-04-02 23:00',
            'duration_minutes' => 60,
            'attempts' => 2,
            'questions' => [
                [
                    'statement' => '¿Cuál es la capital del Perú?',
                    'score' => 10,
                    'type' => 'Alternativas',
                    'options' => [
                        ['text' => 'Lima', 'correct' => true],
                        ['text' => 'Arequipa', 'correct' => false],
                    ],
                ],
                [
                    'statement' => 'Explica el concepto de oferta y demanda.',
                    'score' => 10,
                    'type' => 'Escribir',
                ],
            ],
        ]));

        $examenId = $datos['exam']['id'];

        $this->actingAs($this->usuario)
            ->get(route('aca_course_module_exam_view_details', [
                $this->curso->id,
                $this->modulo->id,
                $examenId,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Academic::Courses/ModuleExam', false)
                ->where('exam.id', $examenId)
                ->has('exam.questions', 2)
                ->has('exam.questions.0.answers', 2));
    }

    public function test_crea_el_simulacro_del_modulo_con_preguntas(): void
    {
        $respuesta = $this->herramienta(AcademicCreateMockExamTool::class, [
            'module_id' => $this->modulo->id,
            'title' => 'Simulacro del módulo uno',
            'date_start' => '2027-03-01 08:00',
            'date_end' => '2027-03-15 23:00',
            'duration_minutes' => 45,
            'attempts' => 3,
            'questions' => [
                [
                    'statement' => '¿Cuál es la capital del Perú?',
                    'score' => 10,
                    'type' => 'Alternativas',
                    'options' => [
                        ['text' => 'Lima', 'correct' => true],
                        ['text' => 'Arequipa', 'correct' => false],
                    ],
                ],
                [
                    'statement' => 'Explica el concepto de oferta y demanda.',
                    'score' => 10,
                    'type' => 'Escribir',
                ],
            ],
        ]);

        $this->assertFalse($respuesta->isError());

        $examen = AcaExam::firstOrFail();
        $this->assertTrue((bool) $examen->is_mock);
        $this->assertSame($this->modulo->id, $examen->module_id);
        $this->assertSame($this->curso->id, $examen->course_id);
        $this->assertFalse((bool) $examen->status);
        $this->assertSame(2, $examen->questions()->count());

        $datos = $this->leerJson($respuesta);
        $this->assertSame('simulacro', $datos['exam']['type']);
        $this->assertSame(2, $datos['exam']['questions']);
        $this->assertTrue($datos['created']);
    }

    public function test_el_simulacro_se_actualiza_sin_duplicarse(): void
    {
        $argumentos = [
            'module_id' => $this->modulo->id,
            'title' => 'Simulacro',
            'date_start' => '2027-03-01 08:00',
            'date_end' => '2027-03-15 23:00',
            'duration_minutes' => 30,
        ];

        $this->herramienta(AcademicCreateMockExamTool::class, $argumentos);
        $segunda = $this->herramienta(AcademicCreateMockExamTool::class, array_merge($argumentos, [
            'title' => 'Simulacro corregido',
            'duration_minutes' => 60,
        ]));

        $this->assertSame(1, AcaExam::count());
        $this->assertSame('Simulacro corregido', AcaExam::firstOrFail()->description);
        $this->assertFalse($this->leerJson($segunda)['created']);
    }

    public function test_crea_el_examen_regular_del_modulo(): void
    {
        $this->herramienta(AcademicCreateModuleExamTool::class, [
            'module_id' => $this->modulo->id,
            'title' => 'Examen del módulo uno',
            'date_start' => '2027-03-20 08:00',
            'date_end' => '2027-03-21 23:00',
            'duration_minutes' => 90,
        ]);

        $examen = AcaExam::firstOrFail();
        $this->assertFalse((bool) $examen->is_mock);
        $this->assertSame($this->modulo->id, $examen->module_id);
    }

    public function test_crea_el_examen_final_del_curso(): void
    {
        $respuesta = $this->herramienta(AcademicCreateFinalExamTool::class, [
            'course_id' => $this->curso->id,
            'title' => 'Examen final',
            'date_start' => '2027-04-01 08:00',
            'date_end' => '2027-04-05 23:00',
            'duration_minutes' => 120,
            'questions' => [[
                'statement' => 'Pregunta final',
                'score' => 20,
                'type' => 'Alternativas',
                'options' => [
                    ['text' => 'Correcta', 'correct' => true],
                    ['text' => 'Incorrecta', 'correct' => false],
                ],
            ]],
        ]);

        $this->assertFalse($respuesta->isError());

        $examen = AcaExam::firstOrFail();
        $this->assertSame($this->curso->id, $examen->course_id);
        $this->assertNull($examen->module_id);
        $this->assertNull($examen->content_id);
        $this->assertFalse((bool) $examen->is_mock);
        $this->assertSame(1, $examen->questions()->count());

        $curso = $this->leerJson($this->herramienta(AcademicGetCourseTool::class, [
            'course_id' => $this->curso->id,
        ]));

        $this->assertSame('final', $curso['course']['final_exam']['type']);
    }

    public function test_no_permite_materiales_ni_examenes_en_un_curso_activo(): void
    {
        $this->curso->update(['status' => true]);

        foreach ([
            [AcademicAddLessonLinksTool::class, [
                'theme_id' => $this->clase->id,
                'links' => [['title' => 'Zoom', 'type' => 'zoom', 'url' => 'https://us02web.zoom.us/j/1']],
            ]],
            [AcademicCreateMockExamTool::class, [
                'module_id' => $this->modulo->id,
                'title' => 'Simulacro',
                'date_start' => '2027-03-01 08:00',
                'date_end' => '2027-03-02 08:00',
                'duration_minutes' => 30,
            ]],
        ] as [$clase, $argumentos]) {
            try {
                $this->herramienta($clase, $argumentos);
                $this->fail('La herramienta debió rechazar un curso activo.');
            } catch (ValidationException $excepcion) {
                $this->assertStringContainsString('ya está activo', $excepcion->getMessage());
            }
        }

        $this->assertSame(0, AcaContent::count());
        $this->assertSame(0, AcaExam::count());
    }

    public function test_get_course_y_validate_reflejan_materiales_y_simulacros(): void
    {
        $this->herramienta(AcademicAddLessonLinksTool::class, [
            'theme_id' => $this->clase->id,
            'links' => [['title' => 'Grabación', 'type' => 'vimeo', 'url' => 'https://vimeo.com/987654321']],
        ]);

        $this->herramienta(AcademicCreateMockExamTool::class, [
            'module_id' => $this->modulo->id,
            'title' => 'Simulacro',
            'date_start' => '2027-03-01 08:00',
            'date_end' => '2027-03-02 08:00',
            'duration_minutes' => 30,
            'questions' => [[
                'statement' => 'Pregunta de prueba',
                'score' => 5,
                'type' => 'Alternativas',
                'options' => [
                    ['text' => 'Sí', 'correct' => true],
                    ['text' => 'No', 'correct' => false],
                ],
            ]],
        ]);

        $curso = $this->leerJson($this->herramienta(AcademicGetCourseTool::class, [
            'course_id' => $this->curso->id,
        ]));

        $this->assertSame('video_embebido', $curso['modules'][0]['lessons'][0]['contents'][0]['type']);
        $this->assertSame('simulacro', $curso['modules'][0]['mock_exam']['type']);
        $this->assertNull($curso['modules'][0]['exam']);
        $this->assertSame(1, $curso['totals']['mock_exams']);

        $validacion = $this->leerJson($this->herramienta(AcademicValidateCourseTool::class, [
            'course_id' => $this->curso->id,
        ]));

        $checks = collect($validacion['checks'])->keyBy('item');
        $this->assertTrue($checks['materiales']['ok']);
        $this->assertTrue($checks['simulacros']['ok']);
        $this->assertTrue($checks['examenes']['ok']);
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
            'number' => (string) (96000000 + $secuencia),
            'names' => 'Agente',
            'father_lastname' => 'Materiales',
            'mother_lastname' => 'Prueba',
            'full_name' => 'Agente Materiales Prueba ' . $secuencia,
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
