<?php

namespace Tests\Feature\Academic;

use App\Models\Person;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request as McpRequest;
use Laravel\Mcp\Response;
use Modules\Academic\Entities\AcaCategoryCourse;
use Modules\Academic\Entities\AcaBrochure;
use Modules\Academic\Entities\AcaContent;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaCourseLanding;
use Modules\Academic\Entities\AcaModality;
use Modules\Academic\Entities\AcaModule;
use Modules\Academic\Entities\AcaTheme;
use Modules\Academic\Entities\AcaTeacher;
use Modules\Academic\Mcp\Tools\AcademicEditActiveContentTool;
use Modules\Academic\Mcp\Tools\AcademicGetPermissionsTool;
use Modules\Academic\Mcp\Tools\AcademicManageActiveCourseImageTool;
use Modules\Academic\Mcp\Tools\AcademicSetActiveBrochureFileTool;
use Modules\Academic\Mcp\Tools\AcademicUpdateActiveCourseTool;
use Modules\Academic\Mcp\Tools\AcademicUpdateActiveLandingTool;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AcademicMcpActiveEditTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    private AcaCourse $curso;

    private AcaTeacher $docente;

    private AcaContent $videoconferencia;

    private AcaContent $archivo;

    private AcaCourseLanding $landing;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->withoutVite();

        $categoria = AcaCategoryCourse::create(['description' => 'Tecnología']);
        $modalidadId = (int) AcaModality::query()->value('id');

        $this->docente = $this->crearDocente();

        $this->curso = AcaCourse::create([
            'status' => true,
            'description' => 'Curso activo de prueba',
            'slug' => 'curso-activo-de-prueba',
            'category_id' => $categoria->id,
            'modality_id' => $modalidadId,
            'type_description' => 'Curso',
            'price' => 150,
            'discount' => 10,
            'discount_applies' => '01',
            'teacher_id' => $this->docente->id,
            'certificate_title' => 'Curso activo de prueba',
            'image' => 'uploads/courses/portada-vieja.jpg',
        ]);
        Storage::disk('public')->put('uploads/courses/portada-vieja.jpg', 'imagen-vieja');

        $modulo = AcaModule::create([
            'course_id' => $this->curso->id,
            'position' => '1',
            'description' => 'Módulo uno',
        ]);
        $tema = AcaTheme::create([
            'module_id' => $modulo->id,
            'position' => '1',
            'description' => 'Clase uno',
        ]);

        $this->videoconferencia = AcaContent::create([
            'theme_id' => $tema->id,
            'position' => '1',
            'description' => 'Clase en vivo',
            'is_file' => 3,
            'content' => 'https://zoom.us/j/123456789',
        ]);

        $this->archivo = AcaContent::create([
            'theme_id' => $tema->id,
            'position' => '2',
            'description' => 'Guía PDF',
            'is_file' => 2,
            'content' => 'uploads/courses/content/guia-vieja.pdf',
        ]);

        $this->landing = AcaCourseLanding::create([
            'course_id' => $this->curso->id,
            'url_slug' => 'curso-activo-de-prueba',
            'is_published' => false,
            'faq_section' => [
                'name' => 'Preguntas',
                'title' => 'Preguntas frecuentes',
                'description' => '',
                'items' => [
                    ['question' => '¿Pregunta original?', 'answer' => 'Respuesta original', 'sort' => 1, 'visible' => true],
                ],
            ],
            'investment_section' => [
                'name' => 'Inversión',
                'title' => 'Precio original',
                'description' => '',
                'items' => [
                    ['title' => 'Plan', 'price_now' => '150', 'features' => ['Acceso']],
                ],
            ],
        ]);

        AcaBrochure::create([
            'course_id' => $this->curso->id,
            'path_file' => 'https://base-aracode.test/storage/uploads/brochures/brochure-viejo.pdf',
            'presentation' => 'Brochure anterior',
        ]);

        $this->usuario = $this->usuarioConPermisos('aca_cursos_editar', 'aca_cursos_listado', 'roles');
        $this->usuario->withAccessToken(
            $this->usuario->createToken('mcp-active', ['academic:read', 'academic:write_active'])->accessToken
        );
    }

    public function test_previsualiza_y_actualiza_solo_campos_permitidos_conservando_slug_y_precio(): void
    {
        $preview = $this->leerJson($this->herramienta(AcademicUpdateActiveCourseTool::class, [
            'course_id' => $this->curso->id,
            'title' => 'Curso activo actualizado',
            'confirm' => false,
        ]));

        $this->assertTrue($preview['requires_confirmation']);
        $this->assertSame('Curso activo de prueba', $this->curso->fresh()->description);

        $resultado = $this->leerJson($this->herramienta(AcademicUpdateActiveCourseTool::class, [
            'course_id' => $this->curso->id,
            'title' => 'Curso activo actualizado',
            'confirm' => true,
            'reason' => 'La persona confirmó el nuevo nombre del curso.',
        ]));

        $this->assertFalse($resultado['requires_confirmation']);
        $this->assertSame('Curso activo actualizado', $this->curso->fresh()->description);
        $this->assertSame('curso-activo-de-prueba', $this->curso->fresh()->slug);
        $this->assertSame('150', (string) $this->curso->fresh()->price);
        $this->assertSame('10', (string) $this->curso->fresh()->discount);
    }

    public function test_bloquea_precio_tipo_estado_y_slug_en_curso_activo(): void
    {
        foreach (['price' => 1, 'type_description' => 'Webinar', 'status' => false, 'slug' => 'otro-slug'] as $campo => $valor) {
            try {
                $this->herramienta(AcademicUpdateActiveCourseTool::class, [
                    'course_id' => $this->curso->id,
                    $campo => $valor,
                    'confirm' => true,
                    'reason' => 'La persona pidió un cambio crítico que no debe aplicarse.',
                ]);
                $this->fail('La herramienta debió rechazar el campo ' . $campo . '.');
            } catch (ValidationException $excepcion) {
                $this->assertStringContainsString('No se permite cambiar por MCP', $excepcion->getMessage());
            }
        }

        $this->assertSame('150', (string) $this->curso->fresh()->price);
        $this->assertSame('Curso', $this->curso->fresh()->type_description);
        $this->assertTrue((bool) $this->curso->fresh()->status);
        $this->assertSame('curso-activo-de-prueba', $this->curso->fresh()->slug);
    }

    public function test_convierte_videoconferencia_zoom_a_video_de_youtube_sin_eliminar_el_contenido(): void
    {
        $antes = $this->leerJson($this->herramienta(AcademicEditActiveContentTool::class, [
            'content_id' => $this->videoconferencia->id,
            'operation' => 'replace_link',
            'link_type' => 'youtube',
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'confirm' => false,
        ]));

        $this->assertTrue($antes['requires_confirmation']);
        $this->assertSame(3, (int) $this->videoconferencia->fresh()->is_file);

        $resultado = $this->leerJson($this->herramienta(AcademicEditActiveContentTool::class, [
            'content_id' => $this->videoconferencia->id,
            'operation' => 'replace_link',
            'link_type' => 'youtube',
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'confirm' => true,
            'reason' => 'La persona confirmó cambiar la videoconferencia por el video grabado.',
        ]));

        $contenido = $this->videoconferencia->fresh();
        $this->assertFalse($resultado['requires_confirmation']);
        $this->assertSame(0, (int) $contenido->is_file);
        $this->assertStringContainsString('youtube.com/embed/dQw4w9WgXcQ', $contenido->content);
        $this->assertDatabaseHas('aca_contents', ['id' => $this->videoconferencia->id]);
        $this->assertSame(2, AcaContent::where('theme_id', $contenido->theme_id)->count());
    }

    public function test_reemplaza_un_documento_por_otro_sin_eliminar_el_contenido(): void
    {
        $resultado = $this->leerJson($this->herramienta(AcademicEditActiveContentTool::class, [
            'content_id' => $this->archivo->id,
            'operation' => 'replace_file',
            'filename' => 'guia-nueva.pdf',
            'file_base64' => base64_encode("%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF"),
            'confirm' => true,
            'reason' => 'La persona confirmó reemplazar la guía anterior por la nueva versión.',
        ]));

        $contenido = $this->archivo->fresh();
        $this->assertFalse($resultado['requires_confirmation']);
        $this->assertSame(2, (int) $contenido->is_file);
        $this->assertStringStartsWith('uploads/courses/content/', (string) $contenido->content);
        Storage::disk('public')->assertExists((string) $contenido->content);
        $this->assertDatabaseHas('aca_contents', ['id' => $this->archivo->id]);
    }

    public function test_actualiza_el_texto_de_un_contenido_sin_cambiar_su_tipo(): void
    {
        $texto = AcaContent::create([
            'theme_id' => $this->archivo->theme_id,
            'position' => '3',
            'description' => 'Lectura de la clase',
            'is_file' => 6,
            'content' => '<p>Texto viejo</p>',
        ]);

        $resultado = $this->leerJson($this->herramienta(AcademicEditActiveContentTool::class, [
            'content_id' => $texto->id,
            'operation' => 'update_text',
            'html' => '<p>Texto nuevo <strong>revisado</strong></p>',
            'confirm' => true,
            'reason' => 'La persona confirmó corregir el texto de la lectura.',
        ]));

        $actualizado = $texto->fresh();
        $this->assertFalse($resultado['requires_confirmation']);
        $this->assertSame(6, (int) $actualizado->is_file);
        $this->assertStringContainsString('Texto nuevo', (string) $actualizado->content);
        $this->assertDatabaseHas('aca_contents', ['id' => $texto->id]);
    }

    public function test_landing_permite_secciones_pero_bloquea_inversion_precios_publicacion_y_slug(): void
    {
        $preview = $this->leerJson($this->herramienta(AcademicUpdateActiveLandingTool::class, [
            'course_id' => $this->curso->id,
            'faq' => ['items' => [['question' => '¿Nueva?', 'answer' => 'Sí', 'sort' => 1]]],
            'confirm' => false,
        ]));

        $this->assertTrue($preview['requires_confirmation']);
        $this->assertSame('¿Pregunta original?', $this->landing->fresh()->faq_section['items'][0]['question']);

        foreach ([
            ['investment' => ['items' => [['price_now' => '1']]]],
            ['is_published' => true],
            ['url_slug' => 'nuevo-slug'],
        ] as $prohibido) {
            try {
                $this->herramienta(AcademicUpdateActiveLandingTool::class, array_merge([
                    'course_id' => $this->curso->id,
                    'confirm' => true,
                    'reason' => 'La persona pidió un cambio de landing que no debe aplicarse.',
                ], $prohibido));
                $this->fail('La landing debió rechazar el campo prohibido.');
            } catch (ValidationException $excepcion) {
                $this->assertStringContainsString('no admite por MCP', $excepcion->getMessage());
            }
        }

        $this->assertSame('¿Pregunta original?', $this->landing->fresh()->faq_section['items'][0]['question']);
        $this->assertSame('150', $this->landing->fresh()->investment_section['items'][0]['price_now']);
        $this->assertFalse((bool) $this->landing->fresh()->is_published);
        $this->assertSame('curso-activo-de-prueba', $this->landing->fresh()->url_slug);

        $resultado = $this->leerJson($this->herramienta(AcademicUpdateActiveLandingTool::class, [
            'course_id' => $this->curso->id,
            'faq' => ['items' => [['question' => '¿Nueva?', 'answer' => 'Sí', 'sort' => 1]]],
            'confirm' => true,
            'reason' => 'La persona confirmó actualizar la pregunta frecuente.',
        ]));

        $this->assertFalse($resultado['requires_confirmation']);
        $this->assertSame('¿Nueva?', $this->landing->fresh()->faq_section['items'][0]['question']);
        $this->assertSame('150', $this->landing->fresh()->investment_section['items'][0]['price_now']);
        $this->assertFalse((bool) $this->landing->fresh()->is_published);
        $this->assertSame('curso-activo-de-prueba', $this->landing->fresh()->url_slug);
    }

    public function test_elimina_solo_la_portada_y_no_el_curso(): void
    {
        $resultado = $this->leerJson($this->herramienta(AcademicManageActiveCourseImageTool::class, [
            'course_id' => $this->curso->id,
            'action' => 'remove',
            'confirm' => true,
            'reason' => 'La persona confirmó quitar la portada incorrecta.',
        ]));

        $this->assertSame('remove', $resultado['result']['action']);
        $this->assertNull($this->curso->fresh()->image);
        $this->assertDatabaseHas('aca_courses', ['id' => $this->curso->id]);
        Storage::disk('public')->assertMissing('uploads/courses/portada-vieja.jpg');
    }

    public function test_reemplaza_solo_el_pdf_del_brochure_y_mantiene_la_misma_ficha(): void
    {
        $preview = $this->leerJson($this->herramienta(AcademicSetActiveBrochureFileTool::class, [
            'course_id' => $this->curso->id,
            'filename' => 'brochure-nuevo.pdf',
            'file_base64' => base64_encode("%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF"),
            'confirm' => false,
        ]));

        $this->assertTrue($preview['requires_confirmation']);
        $this->assertSame(
            'https://base-aracode.test/storage/uploads/brochures/brochure-viejo.pdf',
            AcaBrochure::where('course_id', $this->curso->id)->value('path_file')
        );

        $resultado = $this->leerJson($this->herramienta(AcademicSetActiveBrochureFileTool::class, [
            'course_id' => $this->curso->id,
            'filename' => 'brochure-nuevo.pdf',
            'file_base64' => base64_encode("%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF"),
            'confirm' => true,
            'reason' => 'La persona confirmó reemplazar el brochure por la versión nueva.',
        ]));

        $ficha = AcaBrochure::where('course_id', $this->curso->id)->first();
        $this->assertFalse($resultado['requires_confirmation']);
        $this->assertStringContainsString('/storage/uploads/brochures/', (string) $ficha->path_file);
        $this->assertStringEndsWith('.pdf', (string) $ficha->path_file);
        $this->assertSame(1, AcaBrochure::where('course_id', $this->curso->id)->count());
        $this->assertDatabaseHas('aca_courses', ['id' => $this->curso->id]);
    }

    public function test_la_ability_write_active_es_obligatoria_y_el_permiso_absoluto_no_la_sustituye(): void
    {
        $usuarioSinActivos = $this->usuarioConPermisos('aca_cursos_editar', 'aca_cursos_listado', 'roles');
        $usuarioSinActivos->withAccessToken(
            $usuarioSinActivos->createToken('mcp-solo-lectura', ['academic:read'])->accessToken
        );

        $this->expectException(AuthorizationException::class);

        app(AcademicUpdateActiveCourseTool::class)->handle(new McpRequest([
            'course_id' => $this->curso->id,
            'title' => 'No debe aplicarse',
            'confirm' => true,
            'reason' => 'No tiene la ability de activos.',
        ]));
    }

    public function test_permisos_informan_que_activos_siempre_requieren_confirmacion(): void
    {
        $datos = $this->leerJson($this->herramienta(AcademicGetPermissionsTool::class, []));

        $this->assertTrue($datos['can_edit_active']);
        $this->assertTrue($datos['active_edit_requires_confirmation']);
        $this->assertContains('cualquier cambio en un curso activo o en su landing, incluso con permiso absoluto', $datos['still_requires_confirmation']);
    }

    public function test_un_token_de_activos_exige_tambien_lectura(): void
    {
        $this->actingAs($this->usuario)
            ->postJson(route('aca_api_tokens_store'), [
                'name' => 'Token activo sin lectura',
                'abilities' => ['academic:write_active'],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('abilities');

        $respuesta = $this->actingAs($this->usuario)
            ->postJson(route('aca_api_tokens_store'), [
                'name' => 'Token activo completo',
                'abilities' => ['academic:read', 'academic:write_active'],
            ]);

        $respuesta->assertOk();
        $this->assertDatabaseHas('personal_access_tokens', [
            'name' => 'Token activo completo',
        ]);
    }

    private function crearDocente(): AcaTeacher
    {
        $persona = Person::create([
            'number' => '72000001',
            'names' => 'Docente',
            'father_lastname' => 'Activo',
            'mother_lastname' => 'Prueba',
            'full_name' => 'Docente Activo',
            'email' => 'docente.activo@example.test',
            'ocupacion' => 'Especialista',
        ]);

        return AcaTeacher::create([
            'teacher_code' => 'DOC-ACTIVO',
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
            'number' => (string) (97000000 + $secuencia),
            'names' => 'Agente',
            'father_lastname' => 'Activo',
            'mother_lastname' => 'Prueba',
            'full_name' => 'Agente Activo ' . $secuencia,
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
