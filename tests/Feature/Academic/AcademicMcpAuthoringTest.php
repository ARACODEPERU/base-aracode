<?php

namespace Tests\Feature\Academic;

use App\Models\Person;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request as McpRequest;
use Laravel\Mcp\Response;
use Laravel\Sanctum\Sanctum;
use Modules\Academic\Entities\AcaCategoryCourse;
use Modules\Academic\Entities\AcaContent;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaModality;
use Modules\Academic\Entities\AcaModule;
use Modules\Academic\Entities\AcaTheme;
use Modules\Academic\Mcp\Tools\AcademicAddLessonsTool;
use Modules\Academic\Mcp\Tools\AcademicAddModulesTool;
use Modules\Academic\Mcp\Tools\AcademicCreateCourseDraftTool;
use Modules\Academic\Mcp\Tools\AcademicSetContentsTool;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Herramientas MCP del Académico: los cursos se crean en borrador con las
 * mismas reglas que la web, la autorización va por permiso y ability, y cada
 * llamada queda auditada.
 */
class AcademicMcpAuthoringTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    private int $categoriaId;

    private int $modalidadId;

    protected function setUp(): void
    {
        parent::setUp();

        $categoria = AcaCategoryCourse::create(['description' => 'Herramientas']);
        $this->categoriaId = $categoria->id;
        $this->modalidadId = (int) AcaModality::query()->value('id');

        $this->usuario = $this->usuarioConPermisos('aca_cursos_listado', 'aca_cursos_nuevo', 'aca_cursos_editar');
    }

    public function test_crea_un_curso_en_borrador_y_lo_audita(): void
    {
        $respuesta = $this->herramienta(AcademicCreateCourseDraftTool::class, $this->datosCurso());

        $this->assertFalse($respuesta->isError());

        $curso = AcaCourse::firstOrFail();
        $this->assertFalse((bool) $curso->status);
        $this->assertSame('mcp', $curso->created_via);
        $this->assertSame('01', $curso->course_day);
        $this->assertSame('03', $curso->course_month);
        $this->assertSame('2027', $curso->course_year);
        $this->assertSame('Curso de prueba', $curso->certificate_title);
        $this->assertSame('curso-de-prueba', $curso->slug);

        $this->assertDatabaseHas('mcp_audit_logs', [
            'tool' => 'academic_create_course_draft',
            'status' => 'ok',
            'user_id' => $this->usuario->id,
        ]);
    }

    public function test_la_idempotencia_no_duplica_el_curso(): void
    {
        $this->herramienta(AcademicCreateCourseDraftTool::class, $this->datosCurso());
        $segunda = $this->herramienta(AcademicCreateCourseDraftTool::class, $this->datosCurso());

        $this->assertFalse($segunda->isError());
        $this->assertSame(1, AcaCourse::count());
        $this->assertSame('replay', $this->leerJson($segunda)['idempotent_replay'] ? 'replay' : 'nuevo');
    }

    public function test_un_token_sin_la_ability_de_escritura_no_puede_crear(): void
    {
        Sanctum::actingAs($this->usuario, ['academic:read']);

        // El invocador del paquete convierte esta excepción en un error legible
        // para el agente; la herramienta nunca llega a crear el curso.
        try {
            app(AcademicCreateCourseDraftTool::class)->handle(new McpRequest($this->datosCurso()));
            $this->fail('La herramienta debió rechazar el token sin academic:write.');
        } catch (AuthorizationException $excepcion) {
            $this->assertStringContainsString('academic:write', $excepcion->getMessage());
        }

        $this->assertSame(0, AcaCourse::count());
        $this->assertDatabaseHas('mcp_audit_logs', ['tool' => 'academic_create_course_draft', 'status' => 'error']);
    }

    public function test_un_usuario_sin_permiso_del_erp_no_puede_crear(): void
    {
        $this->usuario = $this->usuarioConPermisos('aca_cursos_listado');

        try {
            $this->herramienta(AcademicCreateCourseDraftTool::class, $this->datosCurso());
            $this->fail('La herramienta debió rechazar el usuario sin aca_cursos_nuevo.');
        } catch (AuthorizationException $excepcion) {
            $this->assertStringContainsString('aca_cursos_nuevo', $excepcion->getMessage());
        }

        $this->assertSame(0, AcaCourse::count());
    }

    public function test_no_se_pueden_agregar_modulos_a_un_curso_activo(): void
    {
        $curso = AcaCourse::create([
            'status' => true,
            'description' => 'Curso ya publicado',
            'category_id' => $this->categoriaId,
            'modality_id' => $this->modalidadId,
            'type_description' => 'Curso',
            'certificate_title' => 'Curso ya publicado',
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->herramienta(AcademicAddModulesTool::class, [
            'course_id' => $curso->id,
            'modules' => [['title' => 'Módulo uno']],
        ]);
    }

    public function test_agrega_modulos_clases_y_contenido_saneado(): void
    {
        $this->herramienta(AcademicCreateCourseDraftTool::class, $this->datosCurso());
        $curso = AcaCourse::firstOrFail();

        $modulos = $this->leerJson($this->herramienta(AcademicAddModulesTool::class, [
            'course_id' => $curso->id,
            'modules' => [['title' => 'Fundamentos'], ['title' => 'Tablas dinámicas']],
        ]))['modules'];

        $this->assertCount(2, $modulos);
        $this->assertSame(1, $modulos[0]['position']);
        $this->assertSame(2, $modulos[1]['position']);

        $clases = $this->leerJson($this->herramienta(AcademicAddLessonsTool::class, [
            'module_id' => $modulos[0]['id'],
            'lessons' => [['title' => '¿Qué es una hoja de cálculo?']],
        ]))['lessons'];

        $this->assertCount(1, $clases);
        $this->assertSame('¿Qué es una hoja de cálculo?', AcaTheme::findOrFail($clases[0]['id'])->description);

        $this->herramienta(AcademicSetContentsTool::class, [
            'theme_id' => $clases[0]['id'],
            'contents' => [[
                'title' => 'Lectura 1',
                'html' => '<p onclick="alert(1)">Hola <strong>mundo</strong></p><script>alert(2)</script><iframe src="x"></iframe>',
            ]],
        ]);

        $contenido = AcaContent::firstOrFail();
        $this->assertStringNotContainsString('script', $contenido->content);
        $this->assertStringNotContainsString('onclick', $contenido->content);
        $this->assertStringNotContainsString('iframe', $contenido->content);
        $this->assertStringContainsString('<strong>mundo</strong>', $contenido->content);
        // 6 = texto/HTML; el 0 queda reservado para el iframe de video.
        $this->assertSame('6', (string) $contenido->is_file);
    }

    public function test_el_endpoint_mcp_exige_token(): void
    {
        $this->postJson('/mcp/academic', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
            'params' => [],
        ])->assertUnauthorized();
    }

    public function test_el_endpoint_lista_las_herramientas_con_un_token_valido(): void
    {
        $token = $this->usuario->createToken('mcp', ['academic:read'])->plainTextToken;

        $respuesta = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'MCP-Protocol-Version' => '2026-07-28',
            'Mcp-Method' => 'tools/list',
            'Accept' => 'application/json, text/event-stream',
        ])->postJson('/mcp/academic', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
            'params' => [
                '_meta' => [
                    'io.modelcontextprotocol/protocolVersion' => '2026-07-28',
                    'io.modelcontextprotocol/clientCapabilities' => (object) [],
                    'io.modelcontextprotocol/clientInfo' => ['name' => 'pest', 'version' => '1.0.0'],
                ],
            ],
        ]);

        $this->assertSame(200, $respuesta->getStatusCode(), (string) $respuesta->getContent());
        $this->assertStringContainsString('academic_create_course_draft', $respuesta->getContent());
    }

    /**
     * Datos mínimos de un curso, con la misma idempotency_key en todas las
     * llamadas de una prueba.
     */
    private function datosCurso(array $cambios = []): array
    {
        return array_merge([
            'title' => 'Curso de prueba',
            'category_id' => $this->categoriaId,
            'modality_id' => $this->modalidadId,
            'type_description' => 'Curso',
            'start_date' => '2027-03-01',
            'price' => 120,
            'idempotency_key' => 'curso-de-prueba',
        ], $cambios);
    }

    private function herramienta(string $clase, array $argumentos): Response
    {
        $this->actingAs($this->usuario);

        return app($clase)->handle(new McpRequest($argumentos));
    }

    private function leerJson(Response $respuesta): array
    {
        $texto = (string) $respuesta->content();

        return json_decode($texto, true) ?: [];
    }

    private function usuarioConPermisos(string ...$permisos): User
    {
        static $secuencia = 0;
        $secuencia++;

        $persona = Person::create([
            'number' => (string) (95000000 + $secuencia),
            'names' => 'Agente',
            'father_lastname' => 'Mcp',
            'mother_lastname' => 'Prueba',
            'full_name' => 'Agente Mcp Prueba ' . $secuencia,
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
