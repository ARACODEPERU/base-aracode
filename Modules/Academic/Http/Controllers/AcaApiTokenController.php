<?php

namespace Modules\Academic\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Modules\Academic\Support\McpTokenLifetime;

/**
 * Pantalla de tokens del servidor MCP: cada persona crea el suyo, lo copia una
 * sola vez y puede revocarlo. El token no se guarda en claro en ninguna parte.
 */
class AcaApiTokenController extends Controller
{
    /** Abilities que se pueden asignar desde la pantalla. */
    private const ABILITIES = ['academic:read', 'academic:write', 'academic:write_active', 'academic:autonomous'];

    public function index(Request $request)
    {
        $tokens = $request->user()->tokens()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($token) => [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => $token->abilities ?? [],
                'created_at' => optional($token->created_at)->format('Y-m-d'),
                'last_used_at' => optional($token->last_used_at)->format('Y-m-d H:i'),
                'expires_at' => optional($token->expires_at)->format('Y-m-d'),
            ])
            ->values();

        return Inertia::render('Academic::Settings/ApiTokens', [
            'tokens' => $tokens,
            'abilities' => self::ABILITIES,
            // Vigencias que ofrece el desplegable y la que viene marcada.
            'lifetimes' => McpTokenLifetime::paraVista(),
            'defaultLifetime' => McpTokenLifetime::POR_DEFECTO,
            'mcpUrl' => url('/mcp/academic'),
            'storeUrl' => route('aca_api_tokens_store'),
            'revokeUrl' => route('aca_api_tokens'),
        ]);
    }

    /**
     * Crea el token y devuelve el valor en claro una única vez.
     */
    public function store(Request $request)
    {
        $datos = $request->validate([
            'name' => 'required|string|max:80',
            'abilities' => 'required|array|min:1',
            'abilities.*' => 'in:' . implode(',', self::ABILITIES),
            'expires_in' => ['required', 'string', Rule::in(McpTokenLifetime::claves())],
        ], [], [
            'name' => 'nombre del token',
            'abilities' => 'permisos del token',
            'expires_in' => 'vigencia del token',
        ]);

        // El permiso absoluto no sirve sin escritura: se exige la combinación
        // completa para no mostrar un token que promete algo que no puede hacer.
        if (in_array('academic:autonomous', $datos['abilities'], true)
            && ! in_array('academic:write', $datos['abilities'], true)) {
            throw ValidationException::withMessages([
                'abilities' => 'El permiso absoluto requiere también academic:write.',
            ]);
        }

        // La edición de activos debe poder consultar el estado actual antes de
        // pedir confirmación. No se emite un token que no pueda revisar nada.
        if (in_array('academic:write_active', $datos['abilities'], true)
            && ! in_array('academic:read', $datos['abilities'], true)) {
            throw ValidationException::withMessages([
                'abilities' => 'La edición de cursos activos requiere también academic:read.',
            ]);
        }

        $llave = 'academic:api-tokens:' . $request->user()->id;

        if (RateLimiter::tooManyAttempts($llave, 20)) {
            return response()->json([
                'message' => 'Creaste demasiados tokens seguidos. Espera unos minutos e inténtalo de nuevo.',
            ], 429);
        }

        RateLimiter::hit($llave, 3600);

        // «indefinido» produce un vencimiento nulo: Sanctum lo trata como un
        // token que no caduca.
        $vence = McpTokenLifetime::vencimiento($datos['expires_in']);

        $token = $request->user()->createToken(
            trim($datos['name']),
            array_values($datos['abilities']),
            $vence
        );

        $vigencia = mb_strtolower(McpTokenLifetime::etiqueta($datos['expires_in']));

        return response()->json([
            'token' => $token->plainTextToken,
            'expires_at' => optional($vence)->format('Y-m-d'),
            'message' => $vence
                ? 'Token creado con vigencia de ' . $vigencia . ': vence el ' . $vence->format('d/m/Y') . '. Cópialo ahora: no se vuelve a mostrar.'
                : 'Token creado sin vencimiento. Cópialo ahora: no se vuelve a mostrar.',
        ]);
    }

    /**
     * Revoca un token propio. Un token de otra persona responde 404.
     */
    public function destroy(Request $request, int $id)
    {
        $token = $request->user()->tokens()->where('id', $id)->first();

        if (! $token) {
            return response()->json(['message' => 'Ese token no existe o ya fue revocado.'], 404);
        }

        $token->delete();

        return response()->json(['message' => 'Token revocado.']);
    }
}
