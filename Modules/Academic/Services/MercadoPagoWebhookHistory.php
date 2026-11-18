<?php

namespace Modules\Academic\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Integrationhub\Entities\Integration;
use Modules\Integrationhub\Entities\IntegrationExecLog;

/**
 * Historial de las llegadas del webhook de suscripciones.
 *
 * IntegrationHub guarda en `integration_exec_logs` lo que el sistema envía a
 * los servicios externos. Las notificaciones de MercadoPago viajan en sentido
 * contrario, así que no dejaban rastro en la pestaña Historial: una firma
 * inválida o la prueba del panel de MercadoPago no guardaba nada y parecía que
 * el webhook nunca había llegado.
 *
 * Esta clase escribe una fila por cada petición recibida, con el estado HTTP
 * respondido, el payload y el resultado. Nunca lanza errores: un problema al
 * registrar no puede romper la respuesta que MercadoPago espera.
 */
class MercadoPagoWebhookHistory
{
    /**
     * Integración de MercadoPago ya resuelta en esta petición.
     */
    private ?int $integrationId = null;

    private bool $integrationBuscada = false;

    public function record(Request $request, JsonResponse $response, float $startedAt): void
    {
        try {
            $integrationId = $this->integrationId();

            if (! $integrationId) {
                return;
            }

            $crudo = (string) $response->getContent();
            $decodificado = json_decode($crudo, true);
            $cuerpo = is_array($decodificado) ? $decodificado : ['respuesta' => $crudo];
            $codigo = $response->getStatusCode();
            $exitoso = $codigo >= 200 && $codigo < 300;
            $tipo = (string) ($request->input('type') ?? $request->query('type') ?? $request->query('topic') ?? '');

            IntegrationExecLog::create([
                'integration_id' => $integrationId,
                'endpoint_id' => null,
                'batch_id' => mb_substr('webhook:' . ($tipo !== '' ? $tipo : mb_strtolower($request->method())), 0, 80),
                'executed_at' => now(),
                'status' => $exitoso ? 'success' : 'failed',
                'request_payload' => [
                    'metodo' => $request->method(),
                    'url' => $request->fullUrl(),
                    'ip' => $request->ip(),
                    'encabezados' => [
                        'x-request-id' => $request->header('x-request-id'),
                        // La firma no se guarda: solo si vino o no.
                        'x-signature' => $request->header('x-signature') ? 'recibida' : 'ausente',
                    ],
                    'payload' => $request->all(),
                    'query' => $request->query->all(),
                ],
                'response_body' => $cuerpo,
                'response_status_code' => $codigo,
                'error_message' => $exitoso ? null : $this->mensajeDeError($cuerpo, $codigo),
                'execution_time_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]);
        } catch (\Throwable $e) {
            Log::warning('[Suscripciones] No se pudo registrar la llegada del webhook en el Historial.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Identificador de la integración MercadoPago; null si no está registrada.
     */
    private function integrationId(): ?int
    {
        if ($this->integrationBuscada) {
            return $this->integrationId;
        }

        $this->integrationBuscada = true;

        $id = Integration::query()
            ->whereRaw('LOWER(name) = ?', ['mercadopago'])
            ->value('id');

        $this->integrationId = $id ? (int) $id : null;

        return $this->integrationId;
    }

    /**
     * Motivo legible del fallo, con el código interno cuando existe.
     *
     * @param  array<string, mixed>  $cuerpo
     */
    private function mensajeDeError(array $cuerpo, int $codigo): string
    {
        $mensaje = (string) ($cuerpo['message'] ?? ('HTTP ' . $codigo));

        if (isset($cuerpo['reason']) && trim((string) $cuerpo['reason']) !== '') {
            $mensaje .= ' (' . $cuerpo['reason'] . ')';
        }

        return mb_substr(trim($mensaje), 0, 1000);
    }
}
