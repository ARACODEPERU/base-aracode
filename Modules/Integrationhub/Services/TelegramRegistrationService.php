<?php

namespace Modules\Integrationhub\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Integrationhub\Entities\IntegrationTelegramContact;
use RuntimeException;

/**
 * Altas y bajas del vinculo persona <-> chat de Telegram.
 *
 * El registro es por enlace: el administrador emite un codigo de un solo uso
 * para una persona (issueCode) y comparte el deep link del bot. Cuando la
 * persona abre el enlace, Telegram entrega /start <codigo> al webhook, que
 * consume el codigo con link() y guarda el chat_id.
 */
class TelegramRegistrationService
{
    /**
     * Emite (o reemplaza) el codigo de registro de una persona.
     *
     * Devuelve null cuando la persona ya tiene un chat activo: no tiene sentido
     * pedirle que se registre otra vez.
     */
    public function issueCode(int $personId, ?string $fallbackName = null): ?IntegrationTelegramContact
    {
        if ($personId <= 0) {
            throw new RuntimeException('No se puede emitir un código sin una persona válida.');
        }

        $contact = IntegrationTelegramContact::firstOrNew(['person_id' => $personId]);

        if ($contact->exists && $contact->status === 'active' && trim((string) $contact->chat_id) !== '') {
            return null;
        }

        $ttlDays = max(1, (int) config('integrationhub.telegram.registration_code_ttl_days', 7));

        $contact->registration_code = $this->generateCode();
        $contact->code_expires_at = now()->addDays($ttlDays);

        // El nombre de Telegram solo se conoce al registrarse; se guarda el de
        // la persona como referencia provisional para poder ubicarla.
        if (trim((string) $contact->telegram_first_name) === '' && trim((string) $fallbackName) !== '') {
            $contact->telegram_first_name = trim((string) $fallbackName);
        }

        $contact->save();

        return $contact;
    }

    /**
     * Consume un codigo de registro y vincula el chat a la persona.
     *
     * @throws RuntimeException cuando el codigo no existe, ya se uso o expiro.
     */
    public function link(string $code, string $chatId, ?string $username = null, ?string $firstName = null): IntegrationTelegramContact
    {
        $code = trim($code);
        $chatId = trim($chatId);

        if ($code === '' || $chatId === '') {
            throw new RuntimeException('El código de registro y el chat_id son obligatorios.');
        }

        // La vigencia se revisa fuera de la transaccion: si el codigo caduco se
        // limpia y, al lanzar la excepcion, la transaccion habria revertido esa
        // limpieza.
        $existing = IntegrationTelegramContact::where('registration_code', $code)->first();

        if (! $existing) {
            throw new RuntimeException('El código de registro no existe o ya fue usado.');
        }

        if ($existing->code_expires_at && $existing->code_expires_at->isPast()) {
            $existing->update(['registration_code' => null, 'code_expires_at' => null]);

            throw new RuntimeException('El código de registro ya expiró. Pide un enlace nuevo.');
        }

        return DB::transaction(function () use ($code, $chatId, $username, $firstName) {
            // Se vuelve a leer bajo bloqueo: entre el chequeo previo y aqui otra
            // peticion pudo consumir el mismo codigo.
            $contact = IntegrationTelegramContact::where('registration_code', $code)->lockForUpdate()->first();

            if (! $contact) {
                throw new RuntimeException('El código de registro no existe o ya fue usado.');
            }

            // Un mismo chat no puede pertenecer a dos personas: si el chat ya
            // estaba vinculado a otro registro, se libera del anterior.
            IntegrationTelegramContact::where('chat_id', $chatId)
                ->where('id', '!=', $contact->id)
                ->update(['chat_id' => null, 'status' => 'inactive']);

            $contact->update([
                'chat_id' => $chatId,
                'telegram_username' => $this->clean($username),
                'telegram_first_name' => $this->clean($firstName) ?? $contact->telegram_first_name,
                'status' => 'active',
                'registration_code' => null,
                'code_expires_at' => null,
                'registered_at' => now(),
                'last_seen_at' => now(),
            ]);

            return $contact->refresh();
        });
    }

    /**
     * Alta directa (sin codigo), para altas manuales o pruebas.
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
                'registration_code' => null,
                'code_expires_at' => null,
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

    private function generateCode(): string
    {
        do {
            $code = Str::random(48);
        } while (IntegrationTelegramContact::where('registration_code', $code)->exists());

        return $code;
    }

    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, 150);
    }
}
