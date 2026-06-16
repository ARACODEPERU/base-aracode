<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateInternalApiSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredKey = (string) config('services.internal_api.key', '');

        if ($configuredKey === '') {
            return response()->json([
                'message' => 'Internal API key is not configured.',
            ], 503);
        }

        $providedKey = (string) $request->header('X-Internal-Api-Key', '');

        if (! hash_equals($configuredKey, $providedKey)) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        return $next($request);
    }
}
