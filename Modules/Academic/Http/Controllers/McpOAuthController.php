<?php

namespace Modules\Academic\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Modules\Academic\Entities\McpOAuthClient;
use Modules\Academic\Entities\McpOAuthCode;

/**
 * OAuth 2.1 del servidor MCP del Académico.
 *
 * El ERP ya usa Sanctum para la app móvil, así que este flujo emite tokens
 * Sanctum (los mismos que el endpoint /mcp/academic valida) en lugar de
 * instalar Passport, que no puede compartir el trait HasApiTokens del modelo
 * User. Implementa authorization code + PKCE (S256), registro dinámico de
 * clientes públicos y códigos de un solo uso.
 */
class McpOAuthController extends Controller
{
    /** Abilities que puede pedir un conector. */
    private const SUPPORTED_SCOPES = ['academic:read', 'academic:write', 'academic:write_active'];

    /** Vida del token emitido: 90 días. */
    private const TOKEN_DAYS = 90;

    /** Minutos que vive un código de autorización. */
    private const CODE_MINUTES = 10;

    /**
     * Metadatos del recurso protegido (RFC 9728).
     */
    public function protectedResource(): JsonResponse
    {
        return response()->json([
            'resource' => url('/mcp/academic'),
            'authorization_servers' => [url('/')],
            'scopes_supported' => self::SUPPORTED_SCOPES,
            'bearer_methods_supported' => ['header'],
        ]);
    }

    /**
     * Metadatos del servidor de autorización (RFC 8414).
     */
    public function authorizationServer(): JsonResponse
    {
        return response()->json([
            'issuer' => url('/'),
            'authorization_endpoint' => url('/oauth/authorize'),
            'token_endpoint' => url('/oauth/token'),
            'registration_endpoint' => url('/oauth/register'),
            'response_types_supported' => ['code'],
            'code_challenge_methods_supported' => ['S256'],
            'grant_types_supported' => ['authorization_code'],
            'token_endpoint_auth_methods_supported' => ['none'],
            'scopes_supported' => self::SUPPORTED_SCOPES,
        ]);
    }

    /**
     * Registro dinámico de clientes públicos (RFC 7591). Claude.ai lo usa al
     * agregar un conector personalizado.
     */
    public function register(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'client_name' => 'nullable|string|max:120',
            'redirect_uris' => 'required|array|min:1|max:5',
            'redirect_uris.*' => 'required|string|max:500',
        ]);

        $uris = [];

        foreach ($datos['redirect_uris'] as $uri) {
            $partes = parse_url((string) $uri);
            $esquema = $partes['scheme'] ?? '';
            $host = $partes['host'] ?? '';

            $valida = $esquema === 'https' && $host !== ''
                ? true
                : $esquema === 'http' && in_array($host, ['localhost', '127.0.0.1'], true);

            if (! $valida) {
                throw ValidationException::withMessages([
                    'redirect_uris' => 'Las URLs de retorno deben ser https, o http solo para localhost.',
                ]);
            }

            $uris[] = (string) $uri;
        }

        $cliente = McpOAuthClient::create([
            'client_id' => Str::random(40),
            'client_name' => trim((string) ($datos['client_name'] ?? '')) ?: 'Conector MCP',
            'redirect_uris' => $uris,
        ]);

        return response()->json([
            'client_id' => $cliente->client_id,
            'client_name' => $cliente->client_name,
            'redirect_uris' => $cliente->redirect_uris,
            'grant_types' => ['authorization_code'],
            'response_types' => ['code'],
            'token_endpoint_auth_method' => 'none',
        ], 201);
    }

    /**
     * Pantalla de consentimiento: el usuario del ERP aprueba el acceso.
     */
    public function authorizeForm(Request $request): View|RedirectResponse
    {
        $contexto = $this->authorizationContext($request);

        if (! $request->user()) {
            // El login devuelve al usuario a esta misma pantalla.
            return redirect()->guest($request->fullUrl());
        }

        $this->ensurePermission($request->user());

        if ($error = $this->authorizationError($request, $contexto)) {
            return $error;
        }

        return view('academic::mcp.authorize', $contexto);
    }

    /**
     * Aprueba el acceso y devuelve el código a la URL registrada del cliente.
     */
    public function approve(Request $request): RedirectResponse
    {
        $contexto = $this->authorizationContext($request);

        $usuario = $request->user();

        if (! $usuario) {
            return redirect()->guest($request->fullUrl());
        }

        $this->ensurePermission($usuario);

        if ($error = $this->authorizationError($request, $contexto)) {
            return $error;
        }

        // Cancelar devuelve al cliente el error estándar, sin emitir código.
        if ($request->input('decision') === 'deny') {
            return redirect()->away($this->redirectUrl($contexto, ['error' => 'access_denied']));
        }

        // El permiso absoluto solo tiene sentido si la conexión puede escribir.
        // La persona lo elige en esta pantalla y queda atado al código/token.
        $autonomia = $request->boolean('autonomy')
            && in_array('academic:write', $contexto['scopes'], true)
            && ! in_array('academic:write_active', $contexto['scopes'], true);

        $scopes = array_values(array_intersect($contexto['scopes'], self::SUPPORTED_SCOPES));

        if ($scopes === []) {
            $scopes = ['academic:read'];
        }

        // La edición de activos siempre necesita leer el estado actual antes de
        // mostrar el antes/después y pedir confirmación.
        if (in_array('academic:write_active', $scopes, true)
            && ! in_array('academic:read', $scopes, true)) {
            $scopes[] = 'academic:read';
        }

        $codigo = Str::random(64);

        McpOAuthCode::create([
            'code_hash' => hash('sha256', $codigo),
            'client_id' => $contexto['client_id'],
            'user_id' => $usuario->id,
            'redirect_uri' => $contexto['redirect_uri'],
            'code_challenge' => $contexto['code_challenge'],
            'scope' => implode(' ', $scopes),
            'autonomy' => $autonomia,
            'expires_at' => now()->addMinutes(self::CODE_MINUTES),
        ]);

        return redirect()->away($this->redirectUrl($contexto, ['code' => $codigo]));
    }

    /**
     * Canjea el código por un token Sanctum con las abilities del scope.
     */
    public function token(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'grant_type' => 'required|in:authorization_code',
            'code' => 'required|string|max:120',
            'client_id' => 'required|string|max:64',
            'redirect_uri' => 'required|string|max:500',
            'code_verifier' => 'required|string|min:43|max:128',
        ]);

        $llave = 'mcp-oauth-token:' . $request->ip();

        if (RateLimiter::tooManyAttempts($llave, 30)) {
            return $this->tokenJson(['error' => 'temporarily_unavailable', 'error_description' => 'Demasiados intentos.'], 429);
        }

        RateLimiter::hit($llave, 60);

        $registro = McpOAuthCode::where('code_hash', hash('sha256', $datos['code']))->first();

        if (! $registro || ! $registro->isUsable()) {
            return $this->tokenJson(['error' => 'invalid_grant', 'error_description' => 'El código no existe, ya se usó o expiró.'], 400);
        }

        if (! hash_equals($registro->client_id, $datos['client_id']) || ! hash_equals($registro->redirect_uri, $datos['redirect_uri'])) {
            return $this->tokenJson(['error' => 'invalid_grant', 'error_description' => 'El código no corresponde a este cliente o a esta URL de retorno.'], 400);
        }

        $esperado = rtrim(strtr(base64_encode(hash('sha256', $datos['code_verifier'], true)), '+/', '-_'), '=');

        if (! hash_equals($registro->code_challenge, $esperado)) {
            return $this->tokenJson(['error' => 'invalid_grant', 'error_description' => 'El code_verifier no coincide con el code_challenge.'], 400);
        }

        $registro->forceFill(['used_at' => now()])->save();

        $usuario = User::find($registro->user_id);

        if (! $usuario) {
            return $this->tokenJson(['error' => 'invalid_grant', 'error_description' => 'El usuario ya no existe.'], 400);
        }

        $abilities = array_values(array_intersect(
            array_filter(explode(' ', (string) $registro->scope)),
            self::SUPPORTED_SCOPES
        ));

        // La autonomía no es un scope que pida el cliente: es una decisión de
        // la persona en la pantalla de consentimiento. Se traduce a una ability
        // interna del token cuando la conexión puede escribir.
        if ($registro->autonomy && in_array('academic:write', $abilities, true)
            && ! in_array('academic:autonomous', $abilities, true)
            && ! in_array('academic:write_active', $abilities, true)) {
            $abilities[] = 'academic:autonomous';
        }

        if ($abilities === []) {
            $abilities = ['academic:read'];
        }

        $token = $usuario->createToken(
            'mcp-oauth:' . Str::limit($registro->client_id, 20, ''),
            $abilities,
            now()->addDays(self::TOKEN_DAYS)
        );

        return $this->tokenJson([
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_in' => self::TOKEN_DAYS * 24 * 3600,
            // academic:autonomous es una ability interna del token, no un
            // scope OAuth: la respuesta solo declara los scopes concedidos.
            'scope' => implode(' ', array_values(array_intersect($abilities, self::SUPPORTED_SCOPES))),
        ]);
    }

    /**
     * Respuesta del endpoint de token: OAuth exige que no se cachee.
     */
    private function tokenJson(array $datos, int $estado = 200): JsonResponse
    {
        return response()->json($datos, $estado)
            ->header('Cache-Control', 'no-store, no-cache, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    /**
     * Valida los parámetros de la petición de autorización y devuelve el
     * contexto que usa la vista de consentimiento.
     */
    private function authorizationContext(Request $request): array
    {
        // input() lee tanto el query string (GET) como el cuerpo del formulario
        // de consentimiento (POST).
        $clientId = (string) $request->input('client_id', '');
        $redirectUri = (string) $request->input('redirect_uri', '');
        $state = (string) $request->input('state', '');
        $codeChallenge = (string) $request->input('code_challenge', '');
        $scope = trim((string) $request->input('scope', 'academic:read'));

        $cliente = McpOAuthClient::where('client_id', $clientId)->first();

        if (! $cliente) {
            abort(400, 'Cliente OAuth desconocido.');
        }

        // La URL de retorno se compara exacta contra la registrada: si no
        // coincide se corta aquí, nunca se redirige a una URL sin validar.
        if (! $cliente->allowsRedirect($redirectUri)) {
            abort(400, 'La URL de retorno no está registrada para este cliente.');
        }

        $pedidos = array_values(array_filter(preg_split('/\s+/', $scope) ?: []));

        if (in_array('academic:write_active', $pedidos, true)
            && ! in_array('academic:read', $pedidos, true)) {
            $pedidos[] = 'academic:read';
        }

        return [
            'client_id' => $cliente->client_id,
            'client_name' => $cliente->client_name,
            'redirect_host' => parse_url($redirectUri, PHP_URL_HOST) ?: $redirectUri,
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'scope' => implode(' ', $pedidos ?: ['academic:read']),
            'scopes' => $pedidos,
            'autonomy_allowed' => in_array('academic:write', $pedidos, true)
                && ! in_array('academic:write_active', $pedidos, true),
            'active_edit_requested' => in_array('academic:write_active', $pedidos, true),
        ];
    }

    /**
     * Errores de la petición de autorización: se devuelven al cliente con el
     * formato OAuth (error + state) en lugar de un 400 sin contexto.
     */
    private function authorizationError(Request $request, array $contexto): ?RedirectResponse
    {
        $error = null;
        $descripcion = null;

        if ((string) $request->input('response_type', '') !== 'code') {
            $error = 'unsupported_response_type';
            $descripcion = 'Solo se admite response_type=code.';
        } elseif ((string) $request->input('code_challenge_method', '') !== 'S256'
            || trim((string) $request->input('code_challenge', '')) === '') {
            $error = 'invalid_request';
            $descripcion = 'Se requiere PKCE con code_challenge_method=S256.';
        } else {
            $pedidos = array_values(array_filter(
                preg_split('/\s+/', trim((string) $request->input('scope', ''))) ?: []
            ));

            if (array_diff($pedidos, self::SUPPORTED_SCOPES) !== []) {
                $error = 'invalid_scope';
                $descripcion = 'Se pidió un scope no soportado.';
            }
        }

        if ($error === null) {
            return null;
        }

        return redirect()->away($this->redirectUrl($contexto, [
            'error' => $error,
            'error_description' => $descripcion,
        ]));
    }

    /**
     * URL de retorno registrada con los parámetros y el state de la petición.
     */
    private function redirectUrl(array $contexto, array $parametros): string
    {
        if ($contexto['state'] !== '') {
            $parametros['state'] = $contexto['state'];
        }

        $separador = str_contains($contexto['redirect_uri'], '?') ? '&' : '?';

        return $contexto['redirect_uri'] . $separador . http_build_query($parametros);
    }

    /**
     * Solo un usuario que ya puede crear o editar cursos en el ERP puede
     * autorizar a un agente.
     */
    private function ensurePermission(User $usuario): void
    {
        if (! $usuario->can('aca_cursos_nuevo') && ! $usuario->can('aca_cursos_editar')) {
            abort(403, 'Tu usuario no tiene permisos de cursos en el Académico.');
        }
    }
}
