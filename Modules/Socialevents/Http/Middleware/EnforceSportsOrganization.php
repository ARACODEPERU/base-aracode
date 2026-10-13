<?php

namespace Modules\Socialevents\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Socialevents\Services\SportsOrganizationService;

class EnforceSportsOrganization
{
    public function handle(Request $request, Closure $next, string $mode = 'read')
    {
        $service = app(SportsOrganizationService::class);

        $service->assertSportsRouteAllowed($request->user(), $request->path(), $request->method());
        $service->assertRequestOwnership($request);

        if ($mode === 'write' || ! in_array(strtoupper($request->method()), ['GET', 'HEAD', 'OPTIONS'], true)) {
            $service->assertCanWrite($request->user());
        } else {
            $service->assertCanRead($request->user());
        }

        return $next($request);
    }
}
