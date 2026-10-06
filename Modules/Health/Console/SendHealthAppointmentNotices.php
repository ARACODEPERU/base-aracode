<?php

namespace Modules\Health\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Modules\Dental\Entities\DentAppointment;
use Modules\Health\Entities\HealAppointmentNotice;
use Modules\Health\Entities\HealAppointmentNoticeDelivery;
use Modules\Health\Jobs\SendAppointmentNotice;
use Modules\Health\Services\AppointmentNoticeService;
use Modules\Health\Support\AppointmentNoticeMessage;

/**
 * Detecta los avisos de citas que ya vencieron y los encola.
 *
 * Corre cada minuto desde el planificador. No envia nada por si mismo: por cada
 * cita y bloque vencido crea la fila de entrega (cuya clave unica impide
 * duplicados aunque corra de nuevo al minuto siguiente) y despacha el job, que
 * es quien envia por SMS dentro de la cola.
 *
 * No hace nada cuando el canal esta apagado (parametro SC-00009) o cuando faltan
 * las credenciales de SMSGate de Salud (SC-00006, SC-00007 y SC-00008).
 */
class SendHealthAppointmentNotices extends Command
{
    protected $signature = 'health:send-appointment-notices';

    protected $description = 'Detecta los avisos de citas vencidos y los encola para su envio por SMS';

    public function handle(AppointmentNoticeService $notices): int
    {
        // Instalacion aun sin migrar: no hay nada que hacer.
        if (! Schema::hasTable('heal_appointment_notices') || ! Schema::hasTable('heal_appointment_notice_deliveries')) {
            return self::SUCCESS;
        }

        if (! $notices->isChannelActive()) {
            $this->info('Avisos de citas: el canal SMS Gateway esta desactivado (parametro ' . $notices->channelParameterCode() . ').');

            return self::SUCCESS;
        }

        if (! $notices->isConfigured()) {
            $this->warn('Avisos de citas: faltan las credenciales de SMSGate (Salud): revisa los parametros SC-00006, SC-00007 y SC-00008.');

            return self::SUCCESS;
        }

        $blocks = HealAppointmentNotice::query()->where('active', true)->orderBy('id')->get();

        if ($blocks->isEmpty()) {
            return self::SUCCESS;
        }

        $now = Carbon::now(config('app.timezone'))->seconds(0);
        $windowStart = $now->copy()->startOfDay()->toDateString();
        $windowEnd = $now->copy()->startOfDay()->addDay()->toDateString();

        // Solo las citas pendientes de hoy y de manana pueden tener un aviso
        // vencido: "minutos antes" cae el mismo dia de la cita y "un dia antes",
        // como maximo, el dia anterior a una cita de manana.
        $appointments = DentAppointment::query()
            ->with(['patient', 'doctor'])
            ->where('status', '1')
            ->whereDate('date_appointmen', '>=', $windowStart)
            ->whereDate('date_appointmen', '<=', $windowEnd)
            ->whereDoesntHave('healthAttention')
            ->get();

        $queued = 0;

        foreach ($appointments as $appointment) {
            $appointmentAt = $notices->appointmentAt($appointment);

            // Cita ya pasada: no se avisa.
            if ($now->greaterThanOrEqualTo($appointmentAt)) {
                continue;
            }

            foreach ($blocks as $block) {
                if (! $this->isDue($block, $appointmentAt, $now)) {
                    continue;
                }

                $alreadyQueued = HealAppointmentNoticeDelivery::query()
                    ->where('appointment_id', $appointment->id)
                    ->where('notice_id', $block->id)
                    ->where('channel', 'smsgate')
                    ->exists();

                if ($alreadyQueued) {
                    continue;
                }

                try {
                    $delivery = HealAppointmentNoticeDelivery::create([
                        'appointment_id' => $appointment->id,
                        'notice_id' => $block->id,
                        'channel' => 'smsgate',
                        'status' => 'pending',
                    ]);
                } catch (QueryException) {
                    // Otra corrida la creo entre el exists() y el insert.
                    continue;
                }

                SendAppointmentNotice::dispatch($delivery->id);
                $queued++;
            }
        }

        $this->info("Avisos de citas: {$queued} aviso(s) encolado(s).");

        return self::SUCCESS;
    }

    /**
     * true si el aviso ya debe salir para esa cita.
     */
    private function isDue(HealAppointmentNotice $block, Carbon $appointmentAt, Carbon $now): bool
    {
        if ($block->key === AppointmentNoticeMessage::KEY_DAY_BEFORE) {
            // Es el aviso del dia anterior: si la cita ya es hoy, su momento
            // paso ayer y no se envia tarde (evita avisar el mismo dia).
            if ($appointmentAt->isSameDay($now)) {
                return false;
            }

            [$hour, $minute] = $this->sendTime($block);

            $target = $appointmentAt->copy()->subDay()->setTime($hour, $minute, 0);

            return $now->greaterThanOrEqualTo($target);
        }

        $minutes = max(0, (int) ($block->minutes_before ?? 0));

        return $now->greaterThanOrEqualTo($appointmentAt->copy()->subMinutes($minutes));
    }

    /**
     * Hora y minuto configurados para el aviso "un dia antes".
     *
     * @return array{0: int, 1: int}
     */
    private function sendTime(HealAppointmentNotice $block): array
    {
        $time = trim((string) ($block->send_time ?? ''));

        if (preg_match('/^(\d{1,2}):(\d{2})/', $time, $matches) !== 1) {
            return [8, 0];
        }

        return [(int) $matches[1], (int) $matches[2]];
    }
}
