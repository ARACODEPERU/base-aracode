<?php

namespace Tests\Feature\Academic;

use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request as McpRequest;
use Laravel\Mcp\Response;
use Modules\Academic\Entities\AcaBrochure;
use Modules\Academic\Entities\AcaCategoryCourse;
use Modules\Academic\Entities\AcaContent;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaCourseLanding;
use Modules\Academic\Entities\AcaModality;
use Modules\Academic\Entities\AcaModule;
use Modules\Academic\Entities\AcaTheme;
use Modules\Academic\Entities\McpAuditLog;
use Modules\Academic\Mcp\Tools\AcademicDeleteDraftTool;
use Modules\Academic\Mcp\Tools\AcademicSetBrochureTool;
use Modules\Academic\Mcp\Tools\AcademicUpdateContentTool;
use Modules\Academic\Mcp\Tools\AcademicUpdateCourseTool;
use Modules\Academic\Mcp\Tools\AcademicUpdateLessonTool;
use Modules\Academic\Mcp\Tools\AcademicUpdateModuleTool;
use Modules\Academic\Mcp\Tools\AcademicUpdateLandingSettingsTool;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Fases 4 y 5 del servidor MCP: edición del borrador, brochure, ajustes de
 * landing, borrado controlado y pantalla de auditoría.
 */
class AcademicMcpPhaseFourFiveTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    private int $categoriaId;

    private int $modalidadId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->categoriaId = AcaCategoryCourse::create(['description' => 'Herramientas'])->id;
        $this->modalidadId = (int) AcaModality::query()->value('id');

        $this->usuario = $this->usuarioConPermisos(
            'aca_cursos_listado',
            'aca_cursos_nuevo',
            'aca_cursos_editar',
            'aca_mcp_admin',
            // El middleware de Inertia consulta este permiso al compartir props.
            'roles'
        );
    }

    public function test_las_herramientas_publican_su_esquema_de_entrada(): void
    {
        $esquema = app(AcademicUpdateCourseTool::class)->schema(new JsonSchemaTypeFactory());

        $this->assertArrayHasKey('course_id', $esquema);
        $this->assertArrayHasKey('title', $esquema);
        $this->assertSame('integer', $esquema['course_id']->toArray()['type']);
        $this->assertNotEmpty($esquema['course_id']->toArray()['description'] ?? null);
    }

    public function test_actualiza_los_datos_del_curso_en_borrador(): void
    {
        $curso = $this->cursoBorrador();

        $respuesta = $this->herramienta(AcademicUpdateCourseTool::class, [
            'course_id' => $curso->id,
            'title' => 'Excel financiero para PYMES',
            'start_date' => '2027-05-10',
            'price' => 150,
        ]);

        $this->assertFalse($respuesta->isError());

        $curso = $curso->fresh();
        $this->assertSame('Excel financiero para PYMES', $curso->description);
        $this->assertSame('10', $curso->course_day);
        $this->assertSame('05', $curso->course_month);
        $this->assertSame('2027', $curso->course_year);
        $this->assertSame(150.0, (float) $curso->price);
    }

    public function test_actualiza_modulo_clase_y_contenido_con_saneado(): void
    {
        [$curso, $modulo, $clase, $contenido] = $this->estructura();

        $this->herramienta(AcademicUpdateModuleTool::class, [
            'module_id' => $modulo->id,
            'title' => 'Módulo renombrado',
            'position' => 3,
        ]);
        $this->assertSame('Módulo renombrado', $modulo->fresh()->description);
        $this->assertSame('3', (string) $modulo->fresh()->position);

        $this->herramienta(AcademicUpdateLessonTool::class, [
            'lesson_id' => $clase->id,
            'title' => 'Clase renombrada',
        ]);
        $this->assertSame('Clase renombrada', $clase->fresh()->description);

        $this->herramienta(AcademicUpdateContentTool::class, [
            'content_id' => $contenido->id,
            'html' => '<p>Nuevo texto <strong>clave</strong></p><script>alert(1)</script>',
        ]);

        $this->assertStringContainsString('<strong>clave</strong>', $contenido->fresh()->content);
        $this->assertStringNotContainsString('script', $contenido->fresh()->content);
    }

    public function test_borra_solo_borradores_creados_por_un_agente(): void
    {
        $delAgente = $this->cursoBorrador();
        $respuesta = $this->herramienta(AcademicDeleteDraftTool::class, [
            'course_id' => $delAgente->id,
            'confirm' => true,
        ]);

        $this->assertFalse($respuesta->isError());
        $this->assertNull(AcaCourse::find($delAgente->id));

        $manual = $this->cursoBorrador(['created_via' => 'web']);

        try {
            $this->herramienta(AcademicDeleteDraftTool::class, [
                'course_id' => $manual->id,
                'confirm' => true,
            ]);
            $this->fail('No debía borrar un borrador creado en el ERP.');
        } catch (ValidationException $excepcion) {
            $this->assertStringContainsString('no lo creó un agente', $excepcion->getMessage());
        }

        $this->assertNotNull(AcaCourse::find($manual->id));
    }

    public function test_guarda_el_brochure_saneado(): void
    {
        $curso = $this->cursoBorrador();

        $this->herramienta(AcademicSetBrochureTool::class, [
            'course_id' => $curso->id,
            'presentation' => '<p>Curso práctico</p><script>alert(1)</script>',
            'benefits' => '<ul><li>Certificado</li></ul>',
        ]);

        $brochure = AcaBrochure::where('course_id', $curso->id)->firstOrFail();
        $this->assertStringContainsString('Curso práctico', (string) $brochure->presentation);
        $this->assertStringNotContainsString('script', (string) $brochure->presentation);
        $this->assertStringContainsString('<li>Certificado</li>', (string) $brochure->benefits);
    }

    public function test_actualiza_los_ajustes_de_la_landing(): void
    {
        $curso = $this->cursoBorrador();
        AcaCourseLanding::create([
            'course_id' => $curso->id,
            'url_slug' => 'curso-de-prueba',
        ]);

        $this->herramienta(AcademicUpdateLandingSettingsTool::class, [
            'course_id' => $curso->id,
            'whatsapp_link' => 'https://wa.me/51999999999',
        ]);

        $this->assertSame('https://wa.me/51999999999', AcaCourseLanding::first()->whatsapp_link);

        // Otra landing con el mismo slug no se puede pisar.
        $otro = $this->cursoBorrador(['description' => 'Otro curso']);
        AcaCourseLanding::create(['course_id' => $otro->id, 'url_slug' => 'otro-curso']);

        try {
            $this->herramienta(AcademicUpdateLandingSettingsTool::class, [
                'course_id' => $otro->id,
                'url_slug' => 'curso-de-prueba',
            ]);
            $this->fail('No debía aceptar un slug ya usado.');
        } catch (ValidationException $excepcion) {
            $this->assertStringContainsString('slug ya lo usa', $excepcion->getMessage());
        }
    }

    public function test_la_pantalla_de_auditoria_lista_las_llamadas(): void
    {
        McpAuditLog::create([
            'user_id' => $this->usuario->id,
            'tool' => 'academic_create_course_draft',
            'status' => 'ok',
            'arguments' => ['title' => 'Curso de prueba'],
            'duration_ms' => 120,
            'ip' => '127.0.0.1',
        ]);

        $this->actingAs($this->usuario)
            ->get(route('aca_mcp_audit'))
            ->assertOk();
    }

    public function test_la_auditoria_exige_el_permiso_propio(): void
    {
        $sinPermiso = $this->usuarioConPermisos('aca_cursos_listado', 'roles');

        $this->actingAs($sinPermiso)
            ->get(route('aca_mcp_audit'))
            ->assertForbidden();
    }

    /**
     * Curso con un módulo, una clase y un contenido de texto.
     *
     * @return array{0: AcaCourse, 1: AcaModule, 2: AcaTheme, 3: AcaContent}
     */
    private function estructura(): array
    {
        $curso = $this->cursoBorrador();
        $modulo = AcaModule::create([
            'course_id' => $curso->id,
            'position' => '1',
            'description' => 'Módulo uno',
        ]);
        $clase = AcaTheme::create([
            'module_id' => $modulo->id,
            'position' => '1',
            'description' => 'Clase uno',
        ]);
        $contenido = AcaContent::create([
            'theme_id' => $clase->id,
            'position' => '1',
            'description' => 'Lectura',
            'is_file' => 1,
        ]);
        $contenido->content = htmlentities('<p>Original</p>', ENT_QUOTES, 'UTF-8');
        $contenido->save();

        return [$curso, $modulo, $clase, $contenido->fresh()];
    }

    private function cursoBorrador(array $cambios = []): AcaCourse
    {
        return AcaCourse::create(array_merge([
            'status' => false,
            'description' => 'Curso de prueba',
            'category_id' => $this->categoriaId,
            'modality_id' => $this->modalidadId,
            'type_description' => 'Curso',
            'certificate_title' => 'Curso de prueba',
            'created_via' => 'mcp',
        ], $cambios));
    }

    private function herramienta(string $clase, array $argumentos): Response
    {
        $this->actingAs($this->usuario);

        return app($clase)->handle(new McpRequest($argumentos));
    }

    private function usuarioConPermisos(string ...$permisos): User
    {
        static $secuencia = 0;
        $secuencia++;

        $persona = Person::create([
            'number' => (string) (98000000 + $secuencia),
            'names' => 'Agente',
            'father_lastname' => 'Mcp',
            'mother_lastname' => 'Cuatro',
            'full_name' => 'Agente Mcp Cuatro ' . $secuencia,
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
