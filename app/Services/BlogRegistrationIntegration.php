<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Modules\Integrationhub\Entities\IntegrationEndpoint;
use Modules\Integrationhub\Entities\IntegrationError;
use Modules\Integrationhub\Http\Controllers\IntegrationhubController;

/**
 * Envia al Integrationhub todos los datos disponibles del lector recien
 * registrado en el blog.
 *
 * El registro nunca depende de este envio: si la integracion no esta
 * configurada, si el servicio externo responde con error o si el propio modulo
 * falla, la cuenta igual queda creada y el detalle se registra en
 * Integrationhub -> Errores para que el administrador lo revise.
 */
class BlogRegistrationIntegration
{
    /**
     * Nombre del endpoint que debe existir en Integrationhub para recibir el
     * alta. Se busca por nombre porque es la forma en que el modulo resuelve
     * los endpoints internos (ver IntegrationhubController::runEndpoint).
     */
    public const ENDPOINT = 'blog_user_registered';

    public function send(User $user): void
    {
        try {
            $user->loadMissing(['person.country', 'roles']);

            $person = $user->person;
            $country = $person?->country;
            $roles = $user->getRoleNames()->values()->all();

            $payload = [
                'evento' => 'blog_registro',
                'origen' => 'blog',
                'usuario_id' => $user->id,
                'persona_id' => $user->person_id,
                'nombre' => $user->name,
                'nombre_completo' => $person?->full_name ?: $user->name,
                'email' => $user->email,
                'telefono' => $user->telefono ?: $person?->telephone,
                'pais_id' => $user->country_id ?: $person?->country_id,
                'pais' => $country?->description,
                // Rol por defecto con el que se crea la cuenta (Alumno) y la lista
                // completa por si el administrador agrega mas roles despues.
                'rol' => $roles[0] ?? null,
                'roles' => $roles,
                'fecha_registro' => $user->created_at?->toIso8601String(),
            ];

            if (! IntegrationEndpoint::where('name', self::ENDPOINT)->exists()) {
                $this->registrarError(
                    'No existe el endpoint '.self::ENDPOINT.' en Integrationhub: el alta del usuario '
                    .$user->id.' no se envio. Configura ese endpoint en el modulo.'
                );

                return;
            }

            $response = app(IntegrationhubController::class)
                ->runEndpoint(self::ENDPOINT, [], ['body' => $payload], true);

            $result = $response->getData(true);
            $statusCode = (int) ($result['status_code'] ?? 0);

            // runEndpoint devuelve el estado dentro del JSON cuando el servicio
            // externo falla, en lugar de lanzar una excepcion.
            if ($response->getStatusCode() !== 200 || $statusCode < 200 || $statusCode >= 300) {
                $this->registrarError(
                    'Integrationhub respondio con estado '.$statusCode
                    .' al enviar el alta del usuario '.$user->id.' desde el blog.'
                );
            }
        } catch (\Throwable $e) {
            $this->registrarError(
                'No se pudo enviar el alta del usuario '.$user->id
                .' a Integrationhub: '.$e->getMessage()
            );
        }
    }

    private function registrarError(string $mensaje): void
    {
        try {
            IntegrationError::create([
                'message' => $mensaje,
                'source' => 'BlogRegistration',
            ]);
        } catch (\Throwable $e) {
            // Si ni el modulo puede registrar el fallo, queda en el log de Laravel.
            Log::error($mensaje);
        }
    }
}
