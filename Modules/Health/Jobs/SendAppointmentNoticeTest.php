<?php

namespace Modules\Health\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Health\Services\AppointmentNoticeService;
use Modules\Integrationhub\Exceptions\SmsgateRejectedException;

/**
 * Envio de prueba de un aviso de cita.
 *
 * Sale por la misma cola que los avisos reales para comprobar la configuracion
 * de SMSGate sin registrar una entrega (no pertenece a ninguna cita).
 *
 * Un rechazo permanente (numero invalido o 4xx de SMSGate) no se relanza: se
 * registra en nivel warning para que el usuario corrija el numero en la
 * pantalla en lugar de recibir una alerta de error por Telegram.
 */
class SendAppointmentNoticeTest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public function __construct(
        public string $phone,
        public string $text,
    ) {
    }

    public function handle(AppointmentNoticeService $notices): void
    {
        try {
            $notices->sendTest($this->phone, $this->text);
        } catch (SmsgateRejectedException $exception) {
            Log::warning('SMS de prueba rechazado por SMSGate', [
                'phone' => $this->phone,
                'error' => $exception->getMessage(),
            ]);

            return;
        }
    }
}
