<?php

namespace Modules\Integrationhub\Services;

use Illuminate\Support\Facades\DB;
use Modules\Integrationhub\Entities\IntegrationTelegramContact;
use Modules\Integrationhub\Entities\IntegrationTelegramRegistrationSession;
use RuntimeException;

/**
 * Altas y bajas del vinculo persona <-> chat de Telegram.
 *
 * El registro es conversacional: la persona entra por el enlace unico del bot
 * (t.me/<bot>), escribe /start y el bot abre una sesion (beginSession) en la
 * que espera su documento. Con el documento ya validado por quien conoce el
 * padron, el webhook llama a attach() y guarda el chat_id.
 */
class TelegramRegistrationService
{
    /** El bot espera el documento para registrar el chat (conversacion de /start). */
    public const STEP_DOCUMENT = 'awaiting_document';

    /** El bot espera el correo de una consulta (/cursos, /certificados). */
    public const STEP_QUERY_EMAIL = 'awaiting_query_email';

    /** El bot espera el documento que confirma la consulta. */
    public const STEP_QUERY_DOCUMENT = 'awaiting_query_document';

    /**
     * Abre (o reinicia) la conversacion de registro de un chat.
     *
     * Es idempotente a proposito: si la persona ya habia empezado o ya estaba
     * registrada, /start vuelve a pedir el documento desde cero.
     */
    public function beginSession(string $chatId, ?string $username = null, ?string $firstName = null): IntegrationTelegramRegistrationSession
    {
        return $this->openStep($chatId, self::STEP_DOCUMENT, [], $username, $firstName);
    }

    /**
     * Abre una conversacion en el paso indicado, con su contexto.
     *
     * Es la version general de beginSession: la usan el registro (que espera el
     * documento) y las consultas de cursos y certificados, que empiezan pidiendo
     * el correo. Es idempotente: reinicia la conversacion anterior de ese chat.
     *
     * @param array<string, mixed> $context
     */
    public function openStep(
        string $chatId,
        string $step,
        array $context = [],
        ?string $username = null,
        ?string $firstName = null
    ): IntegrationTelegramRegistrationSession {
        $chatId = trim($chatId);

        if ($chatId === '') {
            throw new RuntimeException('No se puede abrir un registro sin el chat de Telegram.');
        }

        $session = IntegrationTelegramRegistrationSession::firstOrNew(['chat_id' => $chatId]);

        $session->fill([
            'telegram_username' => $this->clean($username),
            'telegram_first_name' => $this->clean($firstName),
            'step' => $step,
            'context' => $context,
            'attempts' => 0,
            'expires_at' => now()->addMinutes($this->minutes($step)),
        ])->save();

        return $session->refresh();
    }

    /**
     * Avanza la conversacion a otro paso, conservando o reemplazando el contexto
     * y renovando su vigencia.
     *
     * @param array<string, mixed> $context
     */
    public function advanceStep(
        IntegrationTelegramRegistrationSession $session,
        string $step,
        array $context = []
    ): IntegrationTelegramRegistrationSession {
        $session->fill([
            'step' => $step,
            'context' => $context,
            'expires_at' => now()->addMinutes($this->minutes($step)),
        ])->save();

        return $session->refresh();
    }

    /**
     * Contexto guardado de una conversacion (vacio si no hay ninguno).
     *
     * @return array<string, mixed>
     */
    public function context(IntegrationTelegramRegistrationSession $session): array
    {
        $context = $session->context;

        return is_array($context) ? $context : [];
    }

    /**
     * Minutos de vigencia segun el tipo de conversacion.
     */
    private function minutes(string $step): int
    {
        $key = str_starts_with($step, 'awaiting_query')
            ? 'integrationhub.telegram.query_session_minutes'
            : 'integrationhub.telegram.registration_session_minutes';

        return max(1, (int) config($key, 30));
    }

    /**
     * Sesion vigente de un chat, o null si no hay ninguna o ya vencio.
     *
     * Las vencidas se borran al pasar: asi una conversacion abandonada no deja
     * basura y, sobre todo, el siguiente mensaje suelto no se interpreta como
     * un documento.
     */
    public function sessionFor(string $chatId): ?IntegrationTelegramRegistrationSession
    {
        $session = IntegrationTelegramRegistrationSession::where('chat_id', trim($chatId))->first();

        if ($session === null) {
            return null;
        }

        if ($session->expires_at === null || $session->expires_at->isPast()) {
            $session->delete();

            return null;
        }

        return $session;
    }

    /**
     * Suma un intento fallido y devuelve el total acumulado.
     */
    public function countAttempt(IntegrationTelegramRegistrationSession $session): int
    {
        $session->increment('attempts');

        return (int) $session->refresh()->attempts;
    }

    /**
     * Documentos errados que se toleran antes de cerrar la conversacion.
     */
    public function maxAttempts(): int
    {
        return max(1, (int) config('integrationhub.telegram.registration_max_attempts', 5));
    }

    /**
     * Intentos de verificacion que se toleran en una consulta de cursos.
     *
     * Se separa del registro para poder endurecer la consulta (que expone datos
     * privados) sin afectar el alta del chat_id.
     */
    public function maxQueryAttempts(): int
    {
        return max(1, (int) config('integrationhub.telegram.query_max_attempts', 5));
    }

    /**
     * Cierra la conversacion de registro (al vincular, dar de baja o rendirse).
     */
    public function closeSession(string $chatId): void
    {
        IntegrationTelegramRegistrationSession::where('chat_id', trim($chatId))->delete();
    }

    /**
     * Alta directa (sin conversacion), para altas manuales o pruebas.
     */
    public function attach(int $personId, string $chatId, ?string $username = null, ?string $firstName = null): IntegrationTelegramContact
    {
        $chatId = trim($chatId);

        if ($personId <= 0 || $chatId === '') {
            throw new RuntimeException('Se requiere la persona y el chat_id para registrar el contacto.');
        }

        return DB::transaction(function () use ($personId, $chatId, $username, $firstName) {
            IntegrationTelegramContact::where('chat_id', $chatId)
                ->where('person_id', '!=', $personId)
                ->update(['chat_id' => null, 'status' => 'inactive']);

            $contact = IntegrationTelegramContact::firstOrNew(['person_id' => $personId]);

            $contact->fill([
                'chat_id' => $chatId,
                'telegram_username' => $this->clean($username),
                'telegram_first_name' => $this->clean($firstName),
                'status' => 'active',
                'registered_at' => now(),
                'last_seen_at' => now(),
            ])->save();

            return $contact->refresh();
        });
    }

    /**
     * Baja del bot: deja de recibir campanas sin perder el historico.
     */
    public function deactivate(string $chatId): bool
    {
        $contact = IntegrationTelegramContact::where('chat_id', trim($chatId))->first();

        if (! $contact) {
            return false;
        }

        $contact->update(['status' => 'inactive', 'last_seen_at' => now()]);

        return true;
    }

    public function findByChatId(string $chatId): ?IntegrationTelegramContact
    {
        return IntegrationTelegramContact::where('chat_id', trim($chatId))->first();
    }

    /**
     * Marca actividad del chat (cada mensaje recibido).
     */
    public function touch(string $chatId): void
    {
        IntegrationTelegramContact::where('chat_id', trim($chatId))
            ->update(['last_seen_at' => now()]);
    }

    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, 150);
    }
}
