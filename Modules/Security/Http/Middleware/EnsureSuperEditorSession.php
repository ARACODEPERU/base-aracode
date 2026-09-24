<?php

namespace Modules\Security\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Security\Services\SuperEditorService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protege los endpoints que solo tienen sentido dentro de una sesión de
 * edición abierta: exige el rol autorizado y una sesión vigente. Si la sesión
 * expiró, la cierra (descartando el borrador, auditado) y responde 409 para que
 * el frontend salga del modo en vez de seguir intentando guardar.
 */
class EnsureSuperEditorSession
{
    public function __construct(private SuperEditorService $service)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->service->canUse($request->user())) {
            return response()->json([
                'super_editor' => 'forbidden',
                'message' => 'El Modo Super Editor solo está disponible para el rol ' . $this->service->roleName() . '.',
            ], 403);
        }

        $session = $this->service->activeSession($request);

        if (! $session) {
            return response()->json([
                'super_editor' => 'inactive',
                'message' => 'No hay una sesión de edición activa: vuelve a entrar al Modo Super Editor.',
            ], 409);
        }

        if ($this->service->expireIfNeeded($session)) {
            return response()->json([
                'super_editor' => 'expired',
                'message' => 'La sesión de edición expiró: el borrador fue descartado y quedó registrado en el historial.',
            ], 409);
        }

        return $next($request);
    }
}
