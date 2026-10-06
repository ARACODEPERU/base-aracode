<?php

namespace Modules\Health\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Health\Services\AppointmentNoticeService;

/**
 * Envio de prueba de un aviso de cita.
 *
 * Sale por la misma cola que los avisos reales para comprobar la configuracion
 * de SMSGate sin registrar una entrega (no pertenece a ninguna cita).
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
        $notices->sendTest($this->phone, $this->text);
    }
}
