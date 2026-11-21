<?php

namespace Modules\Academic\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Pantalla de tokens del servidor MCP: cada persona crea el suyo, lo copia una
 * sola vez y puede revocarlo. El token no se guarda en claro en ninguna parte.
 */
class AcaApiTokenController extends Controller
{
    /** Abilities que se pueden asignar desde la pantalla. */
    private const ABILITIES = ['academic:read', 'academic:write', 'academic:write_active', 'academic:autonomous'];

    /** Vida del token emitido desde la pantalla: 90 días. */
    private const TOKEN_DAYS = 90;

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
        ], [], [
            'name' => 'nombre del token',
            'abilities' => 'permisos del token',
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

        $token = $request->user()->createToken(
            trim($datos['name']),
            array_values($datos['abilities']),
            now()->addDays(self::TOKEN_DAYS)
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'message' => 'Token creado. Cópialo ahora: no se vuelve a mostrar.',
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
