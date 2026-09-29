<?php

namespace Modules\Academic\Services;

use Modules\Integrationhub\Entities\IntegrationFlowId;
use Modules\Integrationhub\Http\Controllers\IntegrationhubController;
use RuntimeException;

/**
 * Notificacion de curso por WhatsApp a traves de Integrationhub.
 *
 * Se hace en dos pasos, igual que el saludo de cumpleanos y el carrito
 * abandonado:
 *
 *   1. create_contact: crea (o actualiza) el contacto en Chatlevel usando el
 *      telefono de la persona. Si el contacto no existe, el flujo no arranca.
 *   2. Inicio_contacto_con_flow_id: inicia el flujo enviando el ID del flujo,
 *      el contacto, el nombre del curso (clave "curso") y el tiempo indicado
 *      por el administrador (clave "tiempo").
 *
 * El ID del flujo se configura en la pestana "Plantillas / Flujos"; mientras
 * este vacio, la opcion de WhatsApp no se ofrece en la interfaz.
 */
class WhatsappCourseNotifier
{
    /**
     * ID de flujo configurado, o null si todavia no se definio.
     */
    public static function flowId(): ?string
    {
        $key = (string) config('academic.notifications.whatsapp.flow_key', 'aca_course_notification');

        $flowId = trim((string) IntegrationFlowId::where('key', $key)->value('flow_id'));

        return $flowId === '' ? null : $flowId;
    }

    /**
     * true solo si hay un ID de flujo definido en Plantillas / Flujos.
     */
    public static function isConfigured(): bool
    {
        try {
            return self::flowId() !== null;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Crea el contacto y lanza el flujo de WhatsApp para un telefono concreto.
     *
     * @param  string|null  $firstName  Nombre de la persona (opcional): ayuda a
     *                                  que el contacto quede identificado en Chatlevel.
     *
     * @throws RuntimeException cuando falta el flujo o alguna de las dos llamadas falla.
     */
    public function send(string $phone, string $courseName, ?string $time, ?string $firstName = null): void
    {
        $flowId = self::flowId();

        if ($flowId === null) {
            throw new RuntimeException(
                'Falta configurar el ID del flujo de WhatsApp en Plantillas / Flujos.'
            );
        }

        // Los telefonos viajan sin el simbolo "+" (formato E.164 sin prefijo).
        $phone = ltrim($phone, '+');

        // Paso 1: sin contacto en la API, el flujo nunca arranca.
        $this->runStep(
            (string) config('academic.notifications.whatsapp.create_contact_endpoint', 'create_contact'),
            array_filter([
                'phone' => $phone,
                'first_name' => trim((string) $firstName) !== '' ? trim((string) $firstName) : 'Alumno',
            ], fn ($value) => $value !== null && $value !== ''),
            'Creacion del contacto'
        );

        // Paso 2: iniciar el flujo con el curso y el tiempo del aviso.
        $this->runStep(
            (string) config('academic.notifications.whatsapp.endpoint', 'Inicio_contacto_con_flow_id'),
            [
                'flow_id' => $flowId,
                'contact_id' => $phone,
                'curso' => $courseName,
                'tiempo' => $time,
            ],
            'Inicio del flujo de WhatsApp'
        );
    }

    /**
     * Ejecuta un endpoint de Integrationhub y exige que la respuesta sea buena.
     *
     * runEndpoint responde JSON en lugar de lanzar excepcion, por eso aqui se
     * revisan dos cosas: el codigo HTTP y el cuerpo, porque Chatlevel responde
     * HTTP 200 con el error dentro del JSON
     * ({"error":{"code":404,"message":"The requested resource doesn't exist"}}).
     *
     * @throws RuntimeException cuando la llamada falla.
     */
    private function runStep(string $endpoint, array $fieldValues, string $stepLabel): void
    {
        $response = app(IntegrationhubController::class)->runEndpoint($endpoint, $fieldValues);

        $payload = method_exists($response, 'getData') ? (array) $response->getData(true) : [];
        $status = method_exists($response, 'getStatusCode') ? (int) $response->getStatusCode() : 200;

        if ($status >= 400) {
            $message = $payload['message'] ?? 'Error desconocido';

            throw new RuntimeException(
                $stepLabel . ': Integrationhub respondio HTTP ' . $status . ': '
                . (is_scalar($message) ? $message : json_encode($message))
            );
        }

        $body = $payload['response'] ?? ($payload['received']['body'] ?? null);

        if (! is_array($body)) {
            return;
        }

        $error = $body['error'] ?? null;

        if (! empty($error) || (($body['success'] ?? null) === false)) {
            $detail = is_array($error)
                ? ($error['message'] ?? json_encode($error))
                : (string) $error;

            throw new RuntimeException(
                $stepLabel . ': la API respondio con error: ' . trim((string) $detail)
            );
        }
    }
}
