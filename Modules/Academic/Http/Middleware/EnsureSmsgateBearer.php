<?php

namespace Modules\Academic\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Academic\Services\SmsgateService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Valida el bearer del webhook de SMSGate.
 *
 * La aplicacion SMSGate llama a un endpoint publico de este sistema (no hay
 * sesion ni token de sanctum), asi que se autoriza con el header
 * Authorization: Bearer cuyo valor es el parametro del sistema SC-00003. Se
 * acepta tambien X-Api-Key por comodidad si la app no permite encabezados
 * personalizados de Authorization.
 */
class EnsureSmsgateBearer
{
    public function __construct(private readonly SmsgateService $smsgate)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->tokenFrom($request);

        if ($token === null || ! $this->smsgate->matchesBearer($token)) {
            return response()->json(['message' => 'No autorizado.'], 401);
        }

        return $next($request);
    }

    /**
     * Token enviado por la app, ya sin el prefijo "Bearer".
     */
    private function tokenFrom(Request $request): ?string
    {
        $header = trim((string) $request->header('Authorization', ''));

        if (stripos($header, 'Bearer ') === 0) {
            $token = trim(substr($header, 7));

            return $token === '' ? null : $token;
        }

        $fallback = trim((string) $request->header('X-Api-Key', ''));

        return $fallback === '' ? null : $fallback;
    }
}
