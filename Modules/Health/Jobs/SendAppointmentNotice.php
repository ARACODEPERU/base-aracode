<?php

namespace Modules\Health\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Dental\Entities\DentAppointment;
use Modules\Health\Entities\HealAppointmentNotice;
use Modules\Health\Entities\HealAppointmentNoticeDelivery;
use Modules\Health\Services\AppointmentNoticeService;
use Modules\Integrationhub\Exceptions\SmsgateRejectedException;

/**
 * Envia un aviso de cita por SMS.
 *
 * El comando que corre cada minuto solo encola; el envio real (render + SMSGate)
 * ocurre aqui, en la cola, para no detener el planificador. La fila de entrega
 * ya existe cuando llega el job, asi que se actualiza su estado segun el
 * resultado: sent, skipped (sin telefono) o failed.
 *
 * Un rechazo permanente de SMSGate (numero invalido o 4xx del servidor) queda
 * como failed con su motivo y NO se reintenta: el telefono hay que corregirlo
 * en los datos. Ademas se registra en nivel warning, por debajo del umbral de
 * las alertas, para que un dato malo no despierte al administrador por
 * Telegram.
 */
class SendAppointmentNotice implements ShouldQueue
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

    public function handle(AppointmentNoticeService $notices): void
    {
        $delivery = HealAppointmentNoticeDelivery::find($this->deliveryId);

        if (! $delivery || $delivery->status === 'sent' || $delivery->status === 'skipped') {
            return;
        }

        $notice = HealAppointmentNotice::find($delivery->notice_id);
        $appointment = DentAppointment::with(['patient', 'doctor'])->find($delivery->appointment_id);

        // Cita o bloque borrados: no se puede enviar y no tiene sentido reintentar.
        if (! $notice || ! $appointment) {
            $delivery->update([
                'status' => 'failed',
                'error_message' => 'La cita o el bloque de aviso ya no existe.',
            ]);

            return;
        }

        $phone = $notices->recipientPhone($appointment);

        if ($phone === null) {
            $delivery->update([
                'status' => 'skipped',
                'error_message' => 'La cita no tiene telefono de paciente.',
            ]);

            return;
        }

        $delivery->update(['status' => 'processing', 'error_message' => null]);

        try {
            $notices->sendTo($phone, $notice, $appointment);

            $delivery->update([
                'status' => 'sent',
                'sent_at' => now(),
                'error_message' => null,
            ]);
        } catch (SmsgateRejectedException $exception) {
            // Fallo permanente: el numero (o el mensaje) no sirven y reintentar
            // no cambia nada. Se deja constancia en la entrega y se sigue: no
            // se relanza, asi el job no reintenta ni falla.
            $delivery->update([
                'status' => 'failed',
                'error_message' => mb_substr($exception->getMessage(), 0, 500),
            ]);

            Log::warning('Aviso de cita no enviado: SMSGate rechazo el mensaje', [
                'delivery_id' => $this->deliveryId,
                'appointment_id' => $delivery->appointment_id,
                'notice_id' => $delivery->notice_id,
                'phone' => $phone,
                'error' => $exception->getMessage(),
            ]);
        } catch (\Throwable $exception) {
            $delivery->update([
                'status' => 'failed',
                'error_message' => mb_substr($exception->getMessage(), 0, 500),
            ]);

            // Se relanza para que la cola lo reintente; el estado queda en failed
            // hasta que un reintento tenga exito.
            throw $exception;
        }
    }

    /**
     * Ultimo intento fallido: deja el motivo en la entrega.
     */
    public function failed(\Throwable $exception): void
    {
        $delivery = HealAppointmentNoticeDelivery::find($this->deliveryId);

        if ($delivery) {
            $delivery->update([
                'status' => 'failed',
                'error_message' => mb_substr($exception->getMessage(), 0, 500),
            ]);
        }
    }
}
