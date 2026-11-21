<?php

namespace Tests\Feature\Academic;

use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request as McpRequest;
use Laravel\Mcp\Response;
use Modules\Academic\Entities\AcaCategoryCourse;
use Modules\Academic\Entities\AcaContent;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaExam;
use Modules\Academic\Entities\AcaExamQuestion;
use Modules\Academic\Entities\AcaModality;
use Modules\Academic\Entities\AcaModule;
use Modules\Academic\Entities\AcaTheme;
use Modules\Academic\Mcp\Tools\AcademicAddExamQuestionsTool;
use Modules\Academic\Mcp\Tools\AcademicCreateExamTool;
use Modules\Academic\Mcp\Tools\AcademicSetCourseImageTool;
use Modules\Academic\Mcp\Tools\AcademicValidateCourseTool;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Fase 2 del servidor MCP: portada, exámenes, checklist de revisión y pantalla
 * de tokens.
 */
class AcademicMcpPhaseTwoTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    private int $categoriaId;

    private int $modalidadId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $categoria = AcaCategoryCourse::create(['description' => 'Herramientas']);
        $this->categoriaId = $categoria->id;
        $this->modalidadId = (int) AcaModality::query()->value('id');

        $this->usuario = $this->usuarioConPermisos(
            'aca_cursos_listado',
            'aca_cursos_nuevo',
            'aca_cursos_editar',
            // El middleware de Inertia consulta este permiso al compartir props;
            // en producción existe, en la base de pruebas hay que crearlo.
            'roles'
        );
    }

    public function test_crea_el_examen_con_preguntas_y_opciones(): void
    {
        $contenido = $this->contenidoDeCursoBorrador();

        $examen = $this->leerJson($this->herramienta(AcademicCreateExamTool::class, [
            'content_id' => $contenido->id,
            'title' => 'Examen final de Excel',
            'attempts' => 2,
            'duration_minutes' => 45,
        ]))['exam'];

        $this->assertDatabaseHas('aca_exams', [
            'id' => $examen['id'],
            'description' => 'Examen final de Excel',
            'attempts' => 2,
            'status' => 0,
        ]);

        $this->herramienta(AcademicAddExamQuestionsTool::class, [
            'exam_id' => $examen['id'],
            'questions' => [
                [
                    'statement' => '¿Qué función suma un rango condicionado?',
                    'score' => 10,
                    'type' => 'Alternativas',
                    'options' => [
                        ['text' => 'SUMAR.SI', 'correct' => true, 'score' => 10],
                        ['text' => 'BUSCARV', 'correct' => false, 'score' => 0],
                    ],
                ],
                [
                    'statement' => '¿Qué columnas van en el flujo de caja?',
                    'score' => 10,
                    'type' => 'Varias respuestas',
                    'options' => [
                        ['text' => 'Cobros', 'correct' => true, 'score' => 5],
                        ['text' => 'Pagos', 'correct' => true, 'score' => 5],
                        ['text' => 'Logo', 'correct' => false, 'score' => 0],
                    ],
                ],
            ],
        ]);

        $this->assertSame(2, AcaExamQuestion::count());
        $this->assertSame(5, \Modules\Academic\Entities\AcaExamAnswer::count());
        $this->assertSame(2, (int) AcaExam::first()->questions()->count());
    }

    public function test_rechaza_alternativas_con_dos_respuestas_correctas(): void
    {
        $contenido = $this->contenidoDeCursoBorrador();
        $examen = AcaExam::create([
            'course_id' => $contenido->theme->module->course_id,
            'module_id' => $contenido->theme->module_id,
            'theme_id' => $contenido->theme_id,
            'content_id' => $contenido->id,
            'description' => 'Examen corto',
            'status' => false,
        ]);

        try {
            $this->herramienta(AcademicAddExamQuestionsTool::class, [
                'exam_id' => $examen->id,
                'questions' => [[
                    'statement' => 'Pregunta con dos correctas',
                    'score' => 10,
                    'type' => 'Alternativas',
                    'options' => [
                        ['text' => 'A', 'correct' => true],
                        ['text' => 'B', 'correct' => true],
                    ],
                ]],
            ]);

            $this->fail('La herramienta debió rechazar dos respuestas correctas en Alternativas.');
        } catch (ValidationException $excepcion) {
            $this->assertStringContainsString('exactamente una opción correcta', $excepcion->getMessage());
        }

        $this->assertSame(0, AcaExamQuestion::count());
    }

    public function test_la_validacion_lista_lo_que_falta_para_activar(): void
    {
        $curso = $this->cursoBorrador();

        $resultado = $this->leerJson($this->herramienta(AcademicValidateCourseTool::class, [
            'course_id' => $curso->id,
        ]));

        $this->assertFalse($resultado['ready_for_review']);
        $pendientes = array_column($resultado['pending'], 'item');
        $this->assertContains('portada', $pendientes);
        $this->assertContains('modulos', $pendientes);
        $this->assertContains('contenidos', $pendientes);
    }

    public function test_carga_la_portada_desde_base64(): void
    {
        Storage::fake('public');

        $curso = $this->cursoBorrador();
        $imagen = imagecreatetruecolor(600, 300);
        imagefilledrectangle($imagen, 0, 0, 599, 299, imagecolorallocate($imagen, 67, 97, 238));
        ob_start();
        imagepng($imagen);
        $bytes = (string) ob_get_clean();
        imagedestroy($imagen);

        $respuesta = $this->herramienta(AcademicSetCourseImageTool::class, [
            'course_id' => $curso->id,
            'image_base64' => 'data:image/png;base64,' . base64_encode($bytes),
        ]);

        $this->assertFalse($respuesta->isError());

        $ruta = (string) $curso->fresh()->image;
        $this->assertStringStartsWith('uploads/courses/', $ruta);
        Storage::disk('public')->assertExists($ruta);
    }

    public function test_la_pantalla_de_tokens_lista_y_crea_tokens(): void
    {
        $this->actingAs($this->usuario)
            ->get(route('aca_api_tokens'))
            ->assertOk();

        $respuesta = $this->actingAs($this->usuario)
            ->postJson(route('aca_api_tokens_store'), [
                'name' => 'Claude Code – laptop',
                'abilities' => ['academic:read', 'academic:write'],
            ]);

        $respuesta->assertOk();
        $this->assertNotEmpty($respuesta->json('token'));
        $this->assertDatabaseHas('personal_access_tokens', [
            'name' => 'Claude Code – laptop',
        ]);
    }

    public function test_el_permiso_absoluto_del_token_exige_escritura(): void
    {
        $this->actingAs($this->usuario)
            ->postJson(route('aca_api_tokens_store'), [
                'name' => 'Token incompleto',
                'abilities' => ['academic:read', 'academic:autonomous'],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('abilities');
    }

    /**
     * Curso en borrador con un módulo, una clase y un contenido de texto.
     */
    private function contenidoDeCursoBorrador(): AcaContent
    {
        $curso = $this->cursoBorrador();
        $modulo = AcaModule::create([
            'course_id' => $curso->id,
            'position' => '1',
            'description' => 'Fundamentos',
        ]);
        $clase = AcaTheme::create([
            'module_id' => $modulo->id,
            'position' => '1',
            'description' => 'Primera clase',
        ]);

        $contenido = AcaContent::create([
            'theme_id' => $clase->id,
            'position' => '1',
            'description' => 'Lectura',
            'is_file' => 1,
        ]);
        $contenido->content = htmlentities('<p>Texto de la clase</p>', ENT_QUOTES, 'UTF-8');
        $contenido->save();

        return $contenido->fresh();
    }

    private function cursoBorrador(): AcaCourse
    {
        return AcaCourse::create([
            'status' => false,
            'description' => 'Curso de prueba',
            'category_id' => $this->categoriaId,
            'modality_id' => $this->modalidadId,
            'type_description' => 'Curso',
            'certificate_title' => 'Curso de prueba',
            'created_via' => 'mcp',
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
            'number' => (string) (96000000 + $secuencia),
            'names' => 'Agente',
            'father_lastname' => 'Mcp',
            'mother_lastname' => 'Dos',
            'full_name' => 'Agente Mcp Dos ' . $secuencia,
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
