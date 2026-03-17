<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\UserActivityLog;
use Illuminate\Support\Facades\Auth;

class UserActivityLogMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Solo registrar si hay usuario autenticado
        if (Auth::check()) {
            // Verificar si ya se registraron datos extras (para casos especiales)
            $detailsData = $request->input('activity_details_data');

            // Ejecutar la solicitud y obtener la respuesta
            $response = $next($request);
            $statusCode = $response->getStatusCode();

            // Crear el registro
            UserActivityLog::create([
                'user_id' => Auth::id(),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'request_payload' => $this->filterPayload($request->all()),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status_code' => $statusCode,
                'error_message' => $statusCode >= 400 ? 'Error ' . $statusCode : null,
                'details_data' => $detailsData,
            ]);

            return $response;
        }

        return $next($request);
    }

    /**
     * Filtrar datos sensibles del payload
     */
    private function filterPayload(array $payload)
    {
        $sensitiveFields = ['password', 'password_confirmation', 'token', 'secret'];

        foreach ($sensitiveFields as $field) {
            if (isset($payload[$field])) {
                $payload[$field] = '***';
            }
        }

        return $payload;
    }
}
