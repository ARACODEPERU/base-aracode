<?php

namespace Modules\Academic\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Academic\Support\ProfileWebhookPayload;
use Modules\Integrationhub\Entities\IntegrationError;
use Modules\Integrationhub\Http\Controllers\IntegrationhubController;

/**
 * Envia a n8n los datos de la persona y sus cursos por el endpoint
 * n8n_post_negociacion, la misma integracion que usa el ultimo paso del proceso de
 * negociacion de Commercial.
 *
 * Va en cola para que un n8n caido no bloquee al alumno justo despues de guardar su
 * perfil obligatorio, y todo el camino esta blindado: si la integracion no existe, si
 * responde con error, si la cola no arranca o si no se puede ni registrar el fallo, el
 * alumno nunca ve un error. El detalle queda para el sistema en IntegrationError y, en
 * ultimo caso, en el log de Laravel.
 */
class SendProfileWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $personId;

    public string $evento;

    /**
     * @param  int     $personId  Persona cuyos datos viajan (people.id)
     * @param  string  $evento    ProfileWebhookPayload::PERFIL_COMPLETADO o COMPRA_COMPLETADA
     */
    public function __construct(int $personId, string $evento = ProfileWebhookPayload::PERFIL_COMPLETADO)
    {
        $this->personId = $personId;
        $this->evento = $evento;
    }

    /**
     * Encola el envio una sola vez por origen.
     *
     * Tanto el guardado del perfil como el cobro pueden ejecutarse dos veces (doble
     * clic en el formulario o en el boton de pago) y n8n no debe recibir el mismo
     * aviso duplicado. La llave se guarda un dia, tiempo mas que suficiente para
     * cubrir el reintento del usuario.
     */
    public static function dispatchOnce(string $key, int $personId, string $evento): void
    {
        try {
            if (! Cache::add('aca_profile_webhook:'.$key, true, now()->addDay())) {
                return;
            }

            self::dispatch($personId, $evento);
        } catch (\Throwable $e) {
            // Ni siquiera se pudo encolar (cache o cola caida): se registra para el
            // sistema y la pantalla del alumno sigue igual.
            self::registrarError($personId, $evento, $e->getMessage());
        }
    }

    /**
     * Encola el aviso de compra solo cuando la persona ya tenia su cuenta con el perfil
     * completado.
     *
     * Si es la primera vez (su perfil obligatorio sigue pendiente) el aviso no se envia
     * ahora: sale cuando la persona complete su perfil, y ese payload
     * (perfil_completado) ya lleva sus cursos en el bloque 'cursos', asi que el curso
     * recien comprado, el gratis y el webinar no se pierden.
     */
    public static function dispatchCompraOnce(string $key, int $personId): void
    {
        try {
            $updatedInformation = User::where('person_id', $personId)->value('updated_information');

            if (! $updatedInformation) {
                return;
            }

            self::dispatchOnce($key, $personId, ProfileWebhookPayload::COMPRA_COMPLETADA);
        } catch (\Throwable $e) {
            self::registrarError($personId, ProfileWebhookPayload::COMPRA_COMPLETADA, $e->getMessage());
        }
    }

    public function handle(): void
    {
        try {
            $payload = ProfileWebhookPayload::forPersonId($this->personId, $this->evento);

            // La persona pudo eliminarse entre el encolado y la ejecucion.
            if ($payload === null) {
                return;
            }

            $response = app(IntegrationhubController::class)
                ->runEndpoint('n8n_post_negociacion', [], ['body' => $payload], true);

            // runEndpoint no lanza excepcion cuando el endpoint no existe en ninguna
            // integracion ni cuando el servicio externo responde con error: devuelve el
            // estado dentro del JSON. Por eso se revisa aqui y el fallo se registra.
            $result = $response->getData(true);
            $statusCode = (int) ($result['status_code'] ?? 0);

            if ($response->getStatusCode() !== 200 || $statusCode < 200 || $statusCode >= 300) {
                self::registrarError($this->personId, $this->evento, self::descripcionFallo($result, $statusCode));
            }
        } catch (\Throwable $e) {
            self::registrarError($this->personId, $this->evento, $e->getMessage());
        }
    }

    /**
     * Detalle del fallo devuelto por Integrationhub: el mensaje del endpoint externo
     * cuando lo trae, o el propio (por ejemplo cuando el endpoint
     * 'n8n_post_negociacion' no existe en ninguna integracion).
     *
     * @param  array<string, mixed>  $result
     */
    private static function descripcionFallo(array $result, int $statusCode): string
    {
        $external = $result['response'] ?? null;

        if (is_array($external)) {
            $mensaje = $external['message'] ?? $external['error'] ?? null;
        } else {
            $mensaje = is_string($external) ? $external : null;
        }

        if (! $mensaje) {
            $mensaje = $result['message'] ?? "La integracion respondio con estado {$statusCode}.";
        }

        // El mensaje puede venir como objeto o arreglo (columnas de texto en la base):
        // se serializa para que IntegrationError guarde algo legible.
        if (is_array($mensaje)) {
            $mensaje = json_encode($mensaje);
        }

        return "n8n respondio con estado {$statusCode}: {$mensaje}";
    }

    /**
     * Registra el fallo para el sistema. Si ni el registro funciona (base de datos
     * caida) se deja en el log de Laravel; en ningun caso se propaga al alumno.
     */
    private static function registrarError(int $personId, string $evento, string $mensaje): void
    {
        $texto = 'SendProfileWebhook (person_id='.$personId.', evento='.$evento.'): '.$mensaje;

        try {
            IntegrationError::create([
                'message' => $texto,
                'source' => 'SendProfileWebhook',
            ]);
        } catch (\Throwable $e) {
            Log::error($texto);
            Log::error('No se pudo registrar el error del webhook en IntegrationError: '.$e->getMessage());
        }
    }
}
