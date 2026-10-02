<?php

namespace Modules\Academic\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Academic\Entities\AcaNotificationCampaign;
use Modules\Academic\Entities\AcaNotificationCampaignRecipient;
use Modules\Academic\Services\SmsgateService;
use Modules\Academic\Services\TelegramCourseNotifier;
use Modules\Academic\Services\VonageSmsService;
use Modules\Academic\Services\WhatsappCourseNotifier;
use Modules\Academic\Support\NotificationMessageText;

/**
 * Envia una campana de notificaciones completa dentro de la cola.
 *
 * Corre en segundo plano, asi que el administrador puede cerrar el aviso y el
 * proceso continua. Los mensajes salen espaciados (280 ms por defecto, ver
 * config academic.notifications.interval_ms) para no saturar el servidor ni
 * las APIs externas, y el avance se va guardando en la campana para que la
 * barra de progreso pueda mostrar a que numero se esta enviando en ese momento.
 */
class SendAcaNotificationCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Una sola ejecucion: la campana ya es reanudable por si misma (retoma solo
     * las filas pending), asi que un reintento automatico duplicaria envios.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * 1.000 destinatarios a 280 ms son ~4,7 minutos: el timeout por defecto del
     * worker (60 s) seria insuficiente.
     *
     * @var int
     */
    public $timeout = 3600;

    public function __construct(public int $campaignId)
    {
    }

    public function handle(
        VonageSmsService $vonage,
        SmsgateService $smsgate,
        WhatsappCourseNotifier $whatsapp,
        TelegramCourseNotifier $telegram
    ): void
    {
        $campaign = AcaNotificationCampaign::with('course')->find($this->campaignId);

        if (! $campaign || $campaign->isFinished()) {
            return;
        }

        $campaign->update([
            'status' => 'processing',
            'started_at' => $campaign->started_at ?? now(),
            'error_message' => null,
        ]);

        $courseName = (string) ($campaign->course?->description ?? '');
        $time = $campaign->time_label;
        $interval = max(0, (int) config('academic.notifications.interval_ms', 280));

        $sent = (int) $campaign->sent_count;
        $failed = (int) $campaign->failed_count;

        try {
            $recipients = AcaNotificationCampaignRecipient::query()
                ->where('campaign_id', $campaign->id)
                ->where('status', 'pending')
                ->orderBy('id')
                ->get();

            foreach ($recipients as $recipient) {
                // En Telegram no hay telefono: el avance muestra el chat_id.
                $campaign->update(['current_phone' => $recipient->phone ?: $recipient->chat_id]);

                try {
                    if ($campaign->channel === 'whatsapp') {
                        // El nombre solo se aprovecha cuando es un alumno real: en
                        // modo prueba no conviene crear un contacto llamado
                        // "Numero de prueba 1" en la API.
                        $whatsapp->send(
                            $recipient->phone,
                            $courseName,
                            $time,
                            $recipient->student_id ? $recipient->name : 'Prueba'
                        );
                    } elseif ($campaign->channel === 'telegram') {
                        // El aviso de Telegram se arma con la plantilla
                        // configurable: el curso y el tiempo son variables que
                        // salen de este mismo formulario.
                        $telegram->send(
                            (string) $recipient->chat_id,
                            $telegram->renderCampaignText(
                                (string) $campaign->message,
                                $courseName,
                                $time,
                                (string) $recipient->name
                            ),
                            $telegram->campaignIsHtml()
                        );
                    } elseif ($campaign->channel === 'smsgate') {
                        // SMSGate usa el mismo texto que el SMS (mensaje + curso +
                        // tiempo) y envia por el API externo del servidor.
                        $smsgate->send(
                            $recipient->phone,
                            $this->messageText($campaign->message, $courseName, $time)
                        );
                    } else {
                        $vonage->send($recipient->phone, $this->messageText($campaign->message, $courseName, $time));
                    }

                    $recipient->update([
                        'status' => 'sent',
                        'error_message' => null,
                        'sent_at' => now(),
                    ]);

                    $sent++;
                } catch (\Throwable $exception) {
                    // Un destinatario con error no detiene el resto de la campana.
                    $recipient->update([
                        'status' => 'failed',
                        'error_message' => mb_substr($exception->getMessage(), 0, 500),
                    ]);

                    $failed++;
                }

                $campaign->update([
                    'sent_count' => $sent,
                    'failed_count' => $failed,
                ]);

                // Espaciado entre envios: sin esto la cola dispararia todos los
                // mensajes seguidos contra Vonage o el flujo de WhatsApp.
                if ($interval > 0) {
                    usleep($interval * 1000);
                }
            }

            $campaign->update([
                'status' => 'completed',
                'current_phone' => null,
                'finished_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            $campaign->update([
                'status' => 'failed',
                'current_phone' => null,
                'error_message' => mb_substr($exception->getMessage(), 0, 500),
                'finished_at' => now(),
            ]);

            Log::error('Campana de notificaciones academicas interrumpida', [
                'campaign_id' => $campaign->id,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    /**
     * Texto que sale por SMS: mensaje del administrador mas el curso y el
     * tiempo.
     *
     * El SMS conserva esta composicion a proposito (es un canal pagado por
     * segmentos y su formato no depende de la plantilla de Telegram).
     */
    public function messageText(?string $message, string $courseName, ?string $time): string
    {
        return NotificationMessageText::compose($message, $courseName, $time);
    }

    /**
     * Alias del texto del SMS (nombre con el que se publico originalmente).
     */
    public function smsText(?string $message, string $courseName, ?string $time): string
    {
        return $this->messageText($message, $courseName, $time);
    }

    /**
     * Marca la campana como fallida si el job muere por timeout o excepcion
     * fuera del try/catch.
     */
    public function failed(\Throwable $exception): void
    {
        $campaign = AcaNotificationCampaign::find($this->campaignId);

        if (! $campaign) {
            return;
        }

        $campaign->update([
            'status' => 'failed',
            'current_phone' => null,
            'error_message' => mb_substr($exception->getMessage(), 0, 500),
            'finished_at' => now(),
        ]);
    }
}
