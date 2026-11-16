<?php

namespace Modules\Treasury\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Treasury\Services\TreasurySettingsService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloquea las pantallas y operaciones de Tesorería cuando el módulo está
 * desactivado. La pantalla de configuración queda fuera de este middleware
 * para poder volver a activarlo.
 */
class EnsureTreasuryEnabled
{
    public function __construct(private TreasurySettingsService $settings)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->settings->enabled()) {
            abort(403, 'Tesorería está desactivada. Un administrador puede activarla en Tesorería → Configuración.');
        }

        return $next($request);
    }
}
