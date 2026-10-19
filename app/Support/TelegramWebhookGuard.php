<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Frena el abuso de los webhooks de Telegram.
 *
 * Telegram entrega cada mensaje como un POST a nuestro servidor y espera una
 * respuesta rápida; por eso una persona que escriba en bucle al bot puede abrir
 * muchos procesos PHP a la vez y dejarnos sin capacidad para el resto del
 * sistema. Este guardia aplica, del freno más barato al más caro:
 *
 *   1. Deduplicación por update_id: si Telegram reintenta el mismo update
 *      (porque el webhook tardó o falló), no se vuelve a procesar.
 *   2. Límite por chat: un mismo chat no puede superar N updates por minuto.
 *   3. Límite global: el bot entero no puede superar M updates por minuto.
 *
 * Un update descartado se responde igual con HTTP 200: un error haría que
 * Telegram lo reintente y el problema empeoraría. Tampoco se avisa al chat
 * descartado, porque ese aviso consume una llamada saliente y actuaría como
 * amplificador del ataque.
 *
 * Los límites se guardan en la caché. Con una sola instancia (o con una caché
 * compartida, por ejemplo Redis) el freno es exacto; con el driver "file" y
 * varios servidores cada uno lleva su propia cuenta.
 *
 * Esto no sustituye a un límite en el servidor web o en el CDN: una inundación
 * volumétrica debe frenarse antes de llegar a PHP (ver
 * docs/BOT_TELEGRAM_LIMITES.md).
 */
class TelegramWebhookGuard
{
    /** Segundos que dura la ventana de los límites. */
    private const WINDOW_SECONDS = 60;

    /** Minutos que se recuerda un update_id ya visto. */
    private const UPDATE_TTL_MINUTES = 10;

    /**
     * @param string $bot Nombre corto del bot (para separar sus claves en caché).
     * @param int $perChatPerMinute Updates por minuto permitidos a un mismo chat.
     * @param int $globalPerMinute Updates por minuto permitidos al bot completo.
     */
    public function __construct(
        private readonly string $bot,
        private readonly int $perChatPerMinute = 20,
        private readonly int $globalPerMinute = 600,
    ) {
    }

    /**
     * true cuando el update debe procesarse; false cuando es un reintento
     * repetido o cuando el chat (o el bot) ya alcanzó su límite por minuto.
     *
     * @param array<string, mixed> $update
     */
    public function allows(array $update, string $chatId): bool
    {
        if ($this->isRepeated($update)) {
            return false;
        }

        $chatId = trim($chatId);

        if ($chatId !== '') {
            $chatKey = $this->key('chat:' . $chatId);

            if (RateLimiter::tooManyAttempts($chatKey, $this->perChatPerMinute)) {
                $this->warn('chat', $chatId);

                return false;
            }

            RateLimiter::hit($chatKey, self::WINDOW_SECONDS);
        }

        $globalKey = $this->key('global');

        if (RateLimiter::tooManyAttempts($globalKey, $this->globalPerMinute)) {
            $this->warn('global', $chatId);

            return false;
        }

        RateLimiter::hit($globalKey, self::WINDOW_SECONDS);

        return true;
    }

    /**
     * true si Telegram ya nos entregó este update (reintento tras un timeout).
     *
     * Los updates sin update_id (por ejemplo, una petición armada a mano en una
     * prueba) no se deduplican.
     *
     * @param array<string, mixed> $update
     */
    private function isRepeated(array $update): bool
    {
        $updateId = trim((string) ($update['update_id'] ?? ''));

        if ($updateId === '') {
            return false;
        }

        return ! Cache::add(
            $this->key('update:' . $updateId),
            true,
            now()->addMinutes(self::UPDATE_TTL_MINUTES)
        );
    }

    /**
     * Deja una sola línea de log por chat y minuto: una inundación no debe
     * llenar el disco con una entrada por cada mensaje descartado.
     */
    private function warn(string $scope, string $chatId): void
    {
        $logKey = $this->key('throttle-log:' . $scope . ':' . $chatId);

        if (! Cache::add($logKey, true, self::WINDOW_SECONDS)) {
            return;
        }

        Log::warning('Webhook de Telegram: mensajes descartados por exceso de tráfico', [
            'bot' => $this->bot,
            'scope' => $scope,
            'chat_id' => $chatId !== '' ? $chatId : null,
            'per_chat_per_minute' => $this->perChatPerMinute,
            'global_per_minute' => $this->globalPerMinute,
        ]);
    }

    private function key(string $suffix): string
    {
        return 'telegram-webhook:' . $this->bot . ':' . $suffix;
    }
}
