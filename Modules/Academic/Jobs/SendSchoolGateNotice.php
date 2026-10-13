<?php

namespace Modules\Academic\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Academic\Entities\AcaSchoolGateAttendance;
use Modules\Academic\Entities\AcaSchoolNoticeDelivery;
use Modules\Academic\Services\SchoolGateNoticeService;
use Modules\Integrationhub\Exceptions\SmsgateRejectedException;

/**
 * Envía un aviso de asistencia al apoderado por SMS.
 *
 * La portería solo encola; el envío real (plantilla + SMSGate) ocurre aquí, en
 * la cola, para no detener el escaneo en la puerta. La fila de entrega ya
 * existe cuando llega el job, así que se actualiza su estado según el
 * resultado: sent, skipped (canal apagado o asistencia borrada) o failed.
 *
 * Un rechazo permanente de SMSGate (número inválido o 4xx del servidor) queda
 * como failed con su motivo y NO se reintenta: el teléfono hay que corregirlo
 * en la ficha del apoderado. Se registra en nivel warning para que un dato malo
 * no despierte al administrador por las alertas de error.
 */
class SendSchoolGateNotice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Reintentos ante un fallo transitorio (red/SMSGate). La idempotencia la
     * garantiza la fila de entrega, no la cola.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Segundos de espera entre reintentos.
     *
     * @var int
     */
    public $backoff = 30;

    public function __construct(public int $deliveryId)
    {
    }

    public function handle(SchoolGateNoticeService $notices): void
    {
        $delivery = AcaSchoolNoticeDelivery::with('guardian')->find($this->deliveryId);

        if (! $delivery || in_array($delivery->status, ['sent', 'skipped'], true)) {
            return;
        }

        $gate = AcaSchoolGateAttendance::with(['student.person', 'section.grade.level', 'school'])
            ->find($delivery->gate_attendance_id);

        // Asistencia borrada: no se puede armar el mensaje y no tiene sentido reintentar.
        if (! $gate) {
            $delivery->update([
                'status' => 'skipped',
                'error_message' => 'La asistencia de portería ya no existe.',
            ]);

            return;
        }

        // El canal pudo apagarse entre el escaneo y el envío.
        if (! $notices->isConfigured() || ! $notices->isChannelActive()) {
            $delivery->update([
                'status' => 'skipped',
                'error_message' => 'El canal de avisos por SMS está desactivado o sin credenciales.',
            ]);

            return;
        }

        $delivery->update(['status' => 'processing', 'error_message' => null]);

        try {
            $text = $notices->renderNoticeText(
                $delivery->event,
                $notices->noticeVariables($gate, $delivery->event, $delivery->guardian?->full_name)
            );

            $notices->sendTo($delivery->phone, $text);

            $delivery->update([
                'status' => 'sent',
                'sent_at' => now(),
                'error_message' => null,
            ]);
        } catch (SmsgateRejectedException $exception) {
            // Fallo permanente: el número o el mensaje no sirven y reintentar no
            // cambia nada. Se deja constancia y se sigue: no se relanza.
            $delivery->update([
                'status' => 'failed',
                'error_message' => mb_substr($exception->getMessage(), 0, 500),
            ]);

            Log::warning('Aviso de asistencia no enviado: SMSGate rechazó el mensaje', [
                'delivery_id' => $this->deliveryId,
                'gate_attendance_id' => $delivery->gate_attendance_id,
                'event' => $delivery->event,
                'phone' => $delivery->phone,
                'error' => $exception->getMessage(),
            ]);
        } catch (\Throwable $exception) {
            $delivery->update([
                'status' => 'failed',
                'error_message' => mb_substr($exception->getMessage(), 0, 500),
            ]);

            // Se relanza para que la cola lo reintente; el estado queda en failed
            // hasta que un reintento tenga éxito.
            throw $exception;
        }
    }

    /**
     * Último intento fallido: deja el motivo en la entrega.
     */
    public function failed(\Throwable $exception): void
    {
        $delivery = AcaSchoolNoticeDelivery::find($this->deliveryId);

        if ($delivery) {
            $delivery->update([
                'status' => 'failed',
                'error_message' => mb_substr($exception->getMessage(), 0, 500),
            ]);
        }
    }
}
