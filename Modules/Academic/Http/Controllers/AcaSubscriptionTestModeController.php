<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Entities\AcaSubscriptionType;
use Modules\Academic\Services\SubscriptionTestModeService;
use RuntimeException;

/**
 * Botones del modo prueba de cobros recurrentes.
 *
 * Todas las rutas exigen el permiso aca_suscripciones_editar y actúan solo
 * sobre datos marcados como prueba del plan indicado. Las credenciales de
 * MercadoPago que se usan son las de PRUEBA (test_access_token y
 * test_public_key); el servicio rechaza el token de producción.
 */
class AcaSubscriptionTestModeController extends Controller
{
    public function __construct(private readonly SubscriptionTestModeService $testMode)
    {
    }

    /**
     * Crea el alumno y la suscripción de prueba en MercadoPago.
     *
     * Exige que el correo sea de un usuario de prueba comprador; si MercadoPago
     * lo rechaza, simulate() hace el alta sin salir del sistema.
     */
    public function start(Request $request, $id): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'max:150'],
        ], [
            'email.required' => 'Escribe el correo del alumno de prueba.',
            'email.email' => 'El correo del alumno de prueba no es válido.',
        ]);

        return $this->run(function () use ($request, $id): array {
            return $this->testMode->start($this->plan($id), (string) $request->get('email'));
        });
    }

    /**
     * Alta simulada: suscripción y primer cobro sin llamar a MercadoPago.
     *
     * No usa credenciales ni exige un usuario de prueba comprador: sirve para
     * probar Tesorería, correo y acceso con cualquier correo.
     */
    public function simulate(Request $request, $id): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'max:150'],
        ], [
            'email.required' => 'Escribe el correo del alumno de prueba.',
            'email.email' => 'El correo del alumno de prueba no es válido.',
        ]);

        return $this->run(function () use ($request, $id): array {
            return $this->testMode->startSimulated($this->plan($id), (string) $request->get('email'));
        });
    }

    /**
     * Simula el cobro del mes siguiente.
     */
    public function renew($id): JsonResponse
    {
        return $this->run(fn (): array => $this->testMode->simulateRenewal($this->plan($id)));
    }

    /**
     * Envía una notificación firmada a la URL pública del webhook.
     *
     * Valida de punta a punta la ruta HTTP y la firma; con una suscripción
     * simulada además procesa el cobro completo.
     */
    public function notifySigned($id): JsonResponse
    {
        return $this->run(fn (): array => $this->testMode->sendSignedTestNotification($this->plan($id)));
    }

    /**
     * Envía el correo de pago exitoso para comprobar que llega.
     */
    public function notice($id): JsonResponse
    {
        return $this->run(fn (): array => $this->testMode->sendSuccessfulPaymentNotice($this->plan($id)));
    }

    /**
     * Cancela la suscripción de prueba y desactiva el acceso.
     */
    public function cancel($id): JsonResponse
    {
        return $this->run(fn (): array => $this->testMode->cancel($this->plan($id)));
    }

    /**
     * Borra los datos generados por el modo prueba.
     */
    public function purge($id): JsonResponse
    {
        return $this->run(fn (): array => $this->testMode->purge($this->plan($id)));
    }

    /**
     * Plan de suscripción del que se están haciendo las pruebas.
     */
    private function plan($id): AcaSubscriptionType
    {
        return AcaSubscriptionType::findOrFail($id);
    }

    /**
     * Ejecuta la acción y devuelve el resultado que muestra la alerta.
     *
     * @param  callable(): array<string, mixed>  $action
     */
    private function run(callable $action): JsonResponse
    {
        try {
            $resultado = $action();

            return response()->json(array_merge(['success' => true], $resultado));
        } catch (RuntimeException $e) {
            // Errores esperables del flujo (faltan credenciales, no hay prueba
            // creada, etc.): se muestran tal cual, sin ensuciar el log.
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo completar la operación: ' . $e->getMessage(),
            ], 500);
        }
    }
}
