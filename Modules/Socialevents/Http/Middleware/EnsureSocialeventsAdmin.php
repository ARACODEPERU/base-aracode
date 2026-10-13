<?php

namespace Modules\Socialevents\Http\Middleware;

use Closure;
use Modules\Socialevents\Services\SportsOrganizationService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSocialeventsAdmin
{
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

        $isGlobalAdmin = $roles->contains('admin') || $roles->contains('administrador');
        $isSportsOrganizer = app(SportsOrganizationService::class)->sportsMode()
            && $user->hasRole('sports_organizer')
            && (int) $user->socialevent_organization_id > 0;

        if (! $isGlobalAdmin && ! $isSportsOrganizer) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Se requiere rol administrador.',
            ], 403);
        }

        return $next($request);
    }
}
