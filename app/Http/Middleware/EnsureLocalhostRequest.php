<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLocalhostRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowedIps = ['127.0.0.1', '::1'];

        if (!in_array($request->ip(), $allowedIps, true)) {
            return response()->json([
                'message' => 'Solo se permiten peticiones desde localhost.',
            ], 403);
        }

        return $next($request);
    }
}
