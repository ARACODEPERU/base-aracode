<?php

namespace Modules\Socialevents\Http\Middleware;

use Closure;
use Modules\Socialevents\Services\SportsOrganizationService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSocialeventsAdmin
{
    /**
     * Lecturas que la app móvil pide con otro verbo: el listado de partidos es un
     * PUT y la búsqueda de personas un POST, pero ninguna cambia nada.
     */
    private const RUTAS_DE_LECTURA = [
        'api.api.admin.matches',
        'api.api.admin.persons.search',
        // Preparar la URL de un PDF no modifica datos: con el acceso vencido el
        // organizador debe poder descargar sus actas y planillas.
        'api.api.admin.editions.reports.pdf',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado',
            ], 401);
        }

        $roles = $user->getRoleNames()->map(fn ($role) => strtolower($role));
        $sports = app(SportsOrganizationService::class);

        $isGlobalAdmin = $roles->contains('admin') || $roles->contains('administrador');
        $isSportsOrganizer = $sports->sportsMode()
            && $user->hasRole('sports_organizer')
            && (int) $user->socialevent_organization_id > 0;

        if (! $isGlobalAdmin && ! $isSportsOrganizer) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Se requiere rol administrador.',
            ], 403);
        }

        // Modo solo lectura: vencido el acceso o la prueba, la organización
        // conserva las lecturas pero ya no registra ni edita. Las rutas de la API
        // no pasan por el middleware web socialevents.access, así que el corte se
        // aplica aquí para que la app móvil respete el mismo estado.
        if (! $isGlobalAdmin
            && $this->intentaEscribir($request)
            && ! $sports->canWrite($user)) {
            return response()->json([
                'success' => false,
                'code' => 'ORG_READ_ONLY',
                'message' => 'El acceso de la organización está vencido: la cuenta quedó en solo lectura y no admite nuevos registros ni cambios.',
                // La app actualiza su estado con esta misma estructura, sin
                // esperar a otra petición para saber que debe bloquear la UI.
                'access' => $sports->accessSummary($user),
            ], 403);
        }

        return $next($request);
    }

    /** ¿La petición intenta registrar o modificar algo? */
    private function intentaEscribir(Request $request): bool
    {
        if (in_array(strtoupper($request->method()), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return false;
        }

        return ! in_array((string) $request->route()?->getName(), self::RUTAS_DE_LECTURA, true);
    }
}
