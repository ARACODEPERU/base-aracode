<?php

namespace Modules\Academic\Services;

use App\Models\Parameter;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Entities\AcaNotificationCampaign;
use Modules\Academic\Entities\AcaNotificationCampaignRecipient;
use Modules\Academic\Support\NotificationMessageText;

/**
 * Canal "SMS via SMSGate" en modo pull.
 *
 * El sistema NO llama a SMSGate: la aplicacion consulta el webhook publico de
 * este sistema enviando el bearer del parametro SC-00003 y recibe los mensajes
 * pendientes; despues reporta el estado de cada uno (enviado / entregado /
 * fallido). Aqui vive la logica de ese intercambio: leer el bearer y la URL del
 * webhook (SC-00004), reclamar los pendientes de forma atomica y actualizar la
 * campana con cada reporte.
 *
 * La ausencia del bearer apaga el canal: isConfigured() devuelve false y la
 * opcion "SMS via SMSGate" no se muestra en la pantalla de Notificaciones.
 */
class SmsgateService
{
    /**
     * Valor del bearer memoizado por instancia.
     *
     * No se cachea en la aplicacion: una edicion del parametro SC-00003 se
     * refleja de inmediato al recargar la pantalla.
     */
    private ?string $resolvedBearer = null;

    private bool $bearerResolved = false;

    /**
     * true solo si el bearer esta configurado (y hay una URL de webhook).
     */
    public function isConfigured(): bool
    {
        return $this->bearer() !== null && $this->webhookUrl() !== '';
    }

    /**
     * Bearer (secreto) del parametro del sistema (SC-00003 por defecto).
     */
    public function bearer(): ?string
    {
        if ($this->bearerResolved) {
            return $this->resolvedBearer;
        }

        $code = trim((string) config('academic.notifications.smsgate.bearer_parameter', 'SC-00003'));

        $value = $code === ''
            ? null
            : Parameter::where('parameter_code', $code)->value('value_default');

        $value = trim((string) $value);

        $this->resolvedBearer = $value === '' ? null : $value;
        $this->bearerResolved = true;

        return $this->resolvedBearer;
    }

    /**
     * URL del webhook de este sistema.
     *
     * Sale del parametro SC-00004 y, si esta vacio, se deriva de config('app.url')
     * (analogo a TelegramBotService::webhookUrl()).
     */
    public function webhookUrl(): string
    {
        $code = trim((string) config('academic.notifications.smsgate.webhook_parameter', 'SC-00004'));

        $value = $code === ''
            ? ''
            : (string) Parameter::where('parameter_code', $code)->value('value_default');

        $value = trim($value);

        if ($value !== '') {
            return $value;
        }

        $path = (string) config('academic.notifications.smsgate.webhook_path', '/api/academic/smsgate/webhook');

        return rtrim((string) config('app.url'), '/') . '/' . ltrim($path, '/');
    }

    /**
     * Compara el token recibido con el bearer configurado, en tiempo constante.
     */
    public function matchesBearer(string $token): bool
    {
        $expected = $this->bearer();

        return $expected !== null && $token !== '' && hash_equals($expected, $token);
    }

    /**
     * Entrega hasta $limit mensajes pendientes y los reclama.
     *
     * Los destinatarios pasan a "sent" en la misma transaccion: asi una segunda
     * consulta de la app no los vuelve a recibir. Devuelve, por mensaje, el id
     * (que la app devuelve en el reporte de estado), el telefono y el texto.
     *
     * @return array<int, array{id: int, campaign_id: int, phone: string, name: string, text: string}>
     */
    public function pendingMessages(?int $limit = null): array
    {
        $limit = $limit ?? max(1, (int) config('academic.notifications.smsgate.pending_limit', 50));

        return DB::transaction(function () use ($limit) {
            $recipients = AcaNotificationCampaignRecipient::query()
                ->where('status', 'pending')
                ->whereHas('campaign', function ($query) {
                    $query->where('channel', 'smsgate')
                        ->whereIn('status', ['pending', 'processing']);
                })
                ->orderBy('id')
                ->limit($limit)
                ->lockForUpdate()
                ->get();

            if ($recipients->isEmpty()) {
                return [];
            }

            $now = now();

            AcaNotificationCampaignRecipient::query()
                ->whereIn('id', $recipients->pluck('id')->all())
                ->update(['status' => 'sent', 'sent_at' => $now, 'error_message' => null]);

            foreach ($recipients->pluck('campaign_id')->unique() as $campaignId) {
                $this->syncCampaign((int) $campaignId);
            }

            $campaigns = AcaNotificationCampaign::with('course')
                ->whereIn('id', $recipients->pluck('campaign_id')->unique()->all())
                ->get()
                ->keyBy('id');

            return $recipients->map(function (AcaNotificationCampaignRecipient $recipient) use ($campaigns) {
                $campaign = $campaigns->get($recipient->campaign_id);

                return [
                    'id' => (int) $recipient->id,
                    'campaign_id' => (int) $recipient->campaign_id,
                    'phone' => (string) $recipient->phone,
                    'name' => (string) $recipient->name,
                    'text' => NotificationMessageText::compose(
                        $campaign?->message,
                        (string) ($campaign?->course?->description ?? ''),
                        $campaign?->time_label
                    ),
                ];
            })->all();
        });
    }

    /**
     * Aplica el reporte de estado de un mensaje.
     *
     * El id es el del destinatario que viajo en "pending"; "sent"/"delivered"
     * dejan la fila como enviada y "failed" la marcan fallida con su motivo.
     */
    public function reportStatus(string $messageId, string $status, ?string $reason = null): bool
    {
        $id = (int) $messageId;

        if ($id <= 0) {
            return false;
        }

        $recipient = AcaNotificationCampaignRecipient::find($id);

        if (! $recipient) {
            return false;
        }

        if ($status === 'failed') {
            $recipient->update([
                'status' => 'failed',
                'error_message' => $reason !== null && trim($reason) !== ''
                    ? mb_substr(trim($reason), 0, 500)
                    : $recipient->error_message,
            ]);
        } else {
            $recipient->update([
                'status' => 'sent',
                'error_message' => null,
                'sent_at' => $recipient->sent_at ?? now(),
            ]);
        }

        $this->syncCampaign((int) $recipient->campaign_id);

        return true;
    }

    /**
     * Interpreta el cuerpo de una consulta como reporte de estado.
     *
     * Acepta el payload nativo de SMSGate ({event, payload:{messageId,...}}) y
     * el simple ({id, status}). Devuelve null cuando no hay estado reconocible,
     * para que el controlador trate el POST como una consulta de pendientes.
     *
     * @param  array<string, mixed> $payload
     * @return array{id: string, status: string, reason: string|null}|null
     */
    public function extractStatusReport(array $payload): ?array
    {
        $event = strtolower(trim((string) ($payload['event'] ?? '')));

        $body = $payload['payload'] ?? null;

        if (! is_array($body)) {
            $body = $payload;
        }

        $id = $body['id']
            ?? $body['messageId']
            ?? $payload['id']
            ?? $payload['messageId']
            ?? null;

        if ($id === null || trim((string) $id) === '') {
            return null;
        }

        $status = $this->normalizeStatus($body['status'] ?? $payload['status'] ?? null, $event);

        if ($status === null) {
            return null;
        }

        $reason = $body['reason'] ?? $payload['reason'] ?? null;

        return [
            'id' => (string) $id,
            'status' => $status,
            'reason' => is_string($reason) ? $reason : null,
        ];
    }

    /**
     * Recalcula los contadores de la campana desde sus destinatarios y la cierra
     * cuando ya no queda ninguno pendiente.
     */
    public function syncCampaign(int $campaignId): void
    {
        $campaign = AcaNotificationCampaign::find($campaignId);

        if (! $campaign || $campaign->isFinished()) {
            return;
        }

        $counts = AcaNotificationCampaignRecipient::query()
            ->where('campaign_id', $campaignId)
            ->selectRaw("sum(case when status = 'sent' then 1 else 0 end) as sent")
            ->selectRaw("sum(case when status = 'failed' then 1 else 0 end) as failed")
            ->selectRaw("sum(case when status = 'pending' then 1 else 0 end) as pending")
            ->first();

        $sent = (int) ($counts->sent ?? 0);
        $failed = (int) ($counts->failed ?? 0);
        $pending = (int) ($counts->pending ?? 0);

        $changes = [
            'sent_count' => $sent,
            'failed_count' => $failed,
        ];

        // Sin pendientes la campana termino: la app ya recibio todo y reporto
        // (o esta por reportar) cada estado.
        if ($pending === 0) {
            $changes['status'] = 'completed';
            $changes['current_phone'] = null;
            $changes['finished_at'] = $campaign->finished_at ?? now();
        }

        $campaign->update($changes);
    }

    /**
     * Normaliza el estado recibido a "sent" o "failed" (null si no se reconoce).
     */
    private function normalizeStatus(mixed $raw, string $event): ?string
    {
        $value = strtolower(trim((string) $raw));

        if ($event !== '') {
            if (str_contains($event, 'failed') || str_contains($event, 'cancelled')) {
                return 'failed';
            }

            if (str_contains($event, 'sent') || str_contains($event, 'delivered')) {
                return 'sent';
            }
        }

        if (in_array($value, ['failed', 'fail', 'error', 'rejected', 'undelivered'], true)) {
            return 'failed';
        }

        if (in_array($value, ['sent', 'delivered', 'ok', 'success', 'accepted'], true)) {
            return 'sent';
        }

        return null;
    }
}
