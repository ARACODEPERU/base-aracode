<?php

namespace Tests\Feature\Academic;

use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academic\Entities\McpOAuthClient;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Fase 3 del servidor MCP: OAuth 2.1 para conectores web (claude.ai), con
 * registro dinámico de clientes, PKCE y tokens Sanctum de 90 días.
 */
class AcademicMcpOAuthTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = $this->usuarioConPermisos('aca_cursos_listado', 'aca_cursos_nuevo', 'aca_cursos_editar');
    }

    public function test_publica_los_metadatos_oauth(): void
    {
        $recurso = $this->getJson('/.well-known/oauth-protected-resource');
        $recurso->assertOk();
        $this->assertStringEndsWith('/mcp/academic', $recurso->json('resource'));

        $servidor = $this->getJson('/.well-known/oauth-authorization-server');
        $servidor->assertOk();
        $this->assertSame('S256', $servidor->json('code_challenge_methods_supported.0'));
        $this->assertContains('authorization_code', $servidor->json('grant_types_supported'));
    }

    public function test_registra_un_cliente_publico(): void
    {
        $respuesta = $this->postJson('/oauth/register', [
            'client_name' => 'Claude conector',
            'redirect_uris' => ['https://claude.ai/api/mcp/auth_callback'],
        ]);

        $respuesta->assertStatus(201);
        $this->assertNotEmpty($respuesta->json('client_id'));
        $this->assertSame('none', $respuesta->json('token_endpoint_auth_method'));
        $this->assertDatabaseHas('mcp_oauth_clients', ['client_name' => 'Claude conector']);
    }

    public function test_rechaza_urls_de_retorno_no_seguras(): void
    {
        $this->postJson('/oauth/register', [
            'client_name' => 'Conector inseguro',
            'redirect_uris' => ['http://evil.example.com/callback'],
        ])->assertStatus(422);
    }

    public function test_la_pantalla_de_autorizacion_exige_sesion(): void
    {
        $cliente = $this->clienteRegistrado();

        $this->get($this->urlAutorizacion($cliente))
            ->assertRedirect();
    }

    public function test_la_pantalla_de_autorizacion_muestra_el_consentimiento(): void
    {
        $cliente = $this->clienteRegistrado();

        $respuesta = $this->actingAs($this->usuario)->get($this->urlAutorizacion($cliente));

        $respuesta->assertOk();
        $respuesta->assertSee('Autorizar a un agente de IA');
        $respuesta->assertSee('Claude conector');
        $respuesta->assertDontSee('Permiso absoluto para crear y editar');
    }

    public function test_la_pantalla_de_autorizacion_muestra_el_permiso_absoluto_y_sus_riesgos(): void
    {
        $cliente = $this->clienteRegistrado();

        $this->actingAs($this->usuario)
            ->get($this->urlAutorizacion($cliente, 'academic:read academic:write'))
            ->assertOk()
            ->assertSee('Permiso absoluto para crear y editar')
            ->assertSee('Riesgos:');
    }

    public function test_el_permiso_absoluto_se_guarda_en_el_token(): void
    {
        $cliente = $this->clienteRegistrado();
        $verifier = str_repeat('e', 43) . 'a';
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        $autorizacion = $this->actingAs($this->usuario)->post('/oauth/authorize', [
            'client_id' => $cliente->client_id,
            'redirect_uri' => $cliente->redirect_uris[0],
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
            'response_type' => 'code',
            'scope' => 'academic:read academic:write',
            'autonomy' => '1',
            'decision' => 'approve',
        ]);

        parse_str((string) parse_url((string) $autorizacion->headers->get('Location'), PHP_URL_QUERY), $consulta);

        $token = $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'code' => $consulta['code'] ?? '',
            'client_id' => $cliente->client_id,
            'redirect_uri' => $cliente->redirect_uris[0],
            'code_verifier' => $verifier,
        ]);

        $token->assertOk();
        $this->assertSame('academic:read academic:write', $token->json('scope'));
        $this->assertContains('academic:autonomous', $this->usuario->tokens()->latest('id')->first()->abilities);
    }

    public function test_el_consentimiento_de_activos_advierte_y_no_ofrece_permiso_absoluto(): void
    {
        $cliente = $this->clienteRegistrado();

        $this->actingAs($this->usuario)
            ->get($this->urlAutorizacion($cliente, 'academic:read academic:write_active'))
            ->assertOk()
            ->assertSee('Edición de cursos activos: máximo cuidado')
            ->assertSee('No permite cambiar precios')
            ->assertDontSee('Permiso absoluto para crear y editar');
    }

    public function test_el_scope_activo_nunca_recibe_permiso_absoluto_aunque_se_marque(): void
    {
        $cliente = $this->clienteRegistrado();
        $verifier = str_repeat('f', 43) . 'b';
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        $autorizacion = $this->actingAs($this->usuario)->post('/oauth/authorize', [
            'client_id' => $cliente->client_id,
            'redirect_uri' => $cliente->redirect_uris[0],
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
            'response_type' => 'code',
            'scope' => 'academic:read academic:write_active',
            'autonomy' => '1',
            'decision' => 'approve',
        ]);

        parse_str((string) parse_url((string) $autorizacion->headers->get('Location'), PHP_URL_QUERY), $consulta);

        $token = $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'code' => $consulta['code'] ?? '',
            'client_id' => $cliente->client_id,
            'redirect_uri' => $cliente->redirect_uris[0],
            'code_verifier' => $verifier,
        ]);

        $token->assertOk();
        $this->assertSame('academic:read academic:write_active', $token->json('scope'));
        $this->assertNotContains('academic:autonomous', $this->usuario->tokens()->latest('id')->first()->abilities);
    }

    public function test_flujo_completo_emite_un_token_que_usa_el_servidor_mcp(): void
    {
        $cliente = $this->clienteRegistrado();
        $verifier = str_repeat('a', 43) . 'x';
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        // 1. La persona autoriza en el navegador.
        $autorizacion = $this->actingAs($this->usuario)->post('/oauth/authorize', [
            'client_id' => $cliente->client_id,
            'redirect_uri' => $cliente->redirect_uris[0],
            'state' => 'estado-123',
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
            'response_type' => 'code',
            'scope' => 'academic:read academic:write',
            'decision' => 'approve',
        ]);

        $destino = $autorizacion->headers->get('Location');
        $this->assertStringContainsString('claude.ai', (string) $destino);
        $this->assertStringContainsString('state=estado-123', (string) $destino);
        parse_str((string) parse_url((string) $destino, PHP_URL_QUERY), $consulta);
        $codigo = $consulta['code'] ?? '';
        $this->assertNotEmpty($codigo);

        // 2. El conector canjea el código por el token.
        $token = $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'code' => $codigo,
            'client_id' => $cliente->client_id,
            'redirect_uri' => $cliente->redirect_uris[0],
            'code_verifier' => $verifier,
        ]);

        $token->assertOk();
        $this->assertNotEmpty($token->json('access_token'));
        $this->assertSame('academic:read academic:write', $token->json('scope'));

        // 3. Ese token sirve en el endpoint MCP.
        $listado = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->json('access_token'),
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

        $this->assertSame(200, $listado->getStatusCode(), (string) $listado->getContent());
        $this->assertStringContainsString('academic_create_course_draft', $listado->getContent());
    }

    public function test_rechaza_el_codigo_con_verifier_incorrecto(): void
    {
        $cliente = $this->clienteRegistrado();
        $verifier = str_repeat('b', 43) . 'y';
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        $autorizacion = $this->actingAs($this->usuario)->post('/oauth/authorize', [
            'client_id' => $cliente->client_id,
            'redirect_uri' => $cliente->redirect_uris[0],
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
            'response_type' => 'code',
            'scope' => 'academic:read',
            'decision' => 'approve',
        ]);

        parse_str((string) parse_url((string) $autorizacion->headers->get('Location'), PHP_URL_QUERY), $consulta);

        $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'code' => $consulta['code'],
            'client_id' => $cliente->client_id,
            'redirect_uri' => $cliente->redirect_uris[0],
            'code_verifier' => str_repeat('c', 44),
        ])->assertStatus(400);
    }

    public function test_el_codigo_no_se_puede_usar_dos_veces(): void
    {
        $cliente = $this->clienteRegistrado();
        $verifier = str_repeat('d', 43) . 'z';
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        $autorizacion = $this->actingAs($this->usuario)->post('/oauth/authorize', [
            'client_id' => $cliente->client_id,
            'redirect_uri' => $cliente->redirect_uris[0],
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
            'response_type' => 'code',
            'scope' => 'academic:read',
            'decision' => 'approve',
        ]);

        parse_str((string) parse_url((string) $autorizacion->headers->get('Location'), PHP_URL_QUERY), $consulta);

        $datos = [
            'grant_type' => 'authorization_code',
            'code' => $consulta['code'],
            'client_id' => $cliente->client_id,
            'redirect_uri' => $cliente->redirect_uris[0],
            'code_verifier' => $verifier,
        ];

        $this->postJson('/oauth/token', $datos)->assertOk();
        $this->postJson('/oauth/token', $datos)->assertStatus(400);
    }

    public function test_el_usuario_puede_cancelar_sin_emitir_codigo(): void
    {
        $cliente = $this->clienteRegistrado();

        $respuesta = $this->actingAs($this->usuario)->post('/oauth/authorize', [
            'client_id' => $cliente->client_id,
            'redirect_uri' => $cliente->redirect_uris[0],
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', 'x', true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256',
            'response_type' => 'code',
            'scope' => 'academic:read',
            'decision' => 'deny',
        ]);

        $this->assertStringContainsString('error=access_denied', (string) $respuesta->headers->get('Location'));
        $this->assertDatabaseCount('mcp_oauth_codes', 0);
    }

    private function clienteRegistrado(): McpOAuthClient
    {
        return McpOAuthClient::create([
            'client_id' => 'cliente-prueba-123',
            'client_name' => 'Claude conector',
            'redirect_uris' => ['https://claude.ai/api/mcp/auth_callback'],
        ]);
    }

    private function urlAutorizacion(McpOAuthClient $cliente, string $scope = 'academic:read'): string
    {
        return '/oauth/authorize?' . http_build_query([
            'client_id' => $cliente->client_id,
            'redirect_uri' => $cliente->redirect_uris[0],
            'response_type' => 'code',
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', 'x', true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256',
            'scope' => $scope,
        ]);
    }

    private function usuarioConPermisos(string ...$permisos): User
    {
        static $secuencia = 0;
        $secuencia++;

        $persona = Person::create([
            'number' => (string) (97000000 + $secuencia),
            'names' => 'Agente',
            'father_lastname' => 'Oauth',
            'mother_lastname' => 'Prueba',
            'full_name' => 'Agente Oauth Prueba ' . $secuencia,
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
