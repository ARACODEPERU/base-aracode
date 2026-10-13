<?php

namespace Modules\Health\Services;

use Illuminate\Support\Facades\Schema;
use Modules\Health\Entities\HealDoctorGoogleAccount;
use Modules\Health\Entities\HealGoogleCalendarState;
use Modules\Health\Support\GoogleCalendarAccount;
use Modules\Health\Support\GoogleCalendarOptions;
use RuntimeException;
use Throwable;

/**
 * Agenda de Google Calendar de cada doctor.
 *
 * Un doctor enlaza SU cuenta: sus citas se escriben en su propio calendario y
 * no en la agenda principal del consultorio. Este servicio guarda esa cuenta
 * (token cifrado), la consulta para el envio de citas y la desvincula.
 *
 * Solo sale hacia Google: la lectura de cambios (bandeja "Por revisar") sigue
 * siendo exclusiva de la agenda principal.
 */
class DoctorGoogleCalendarService
{
    public function __construct(
        private readonly GoogleCalendarService $google,
    ) {
    }

    /**
     * true si la tabla de cuentas por doctor ya esta migrada.
     */
    public function tableReady(): bool
    {
        return Schema::hasTable('heal_doctor_google_accounts');
    }

    /**
     * Cuenta enlazada del doctor (null si no tiene).
     */
    public function accountFor(int $doctorId): ?HealDoctorGoogleAccount
    {
        if (! $this->tableReady()) {
            return null;
        }

        return HealDoctorGoogleAccount::where('doctor_id', $doctorId)->first();
    }

    /**
     * Cuenta lista para escribir citas (null si el doctor no tiene una activa).
     */
    public function activeAccount(int $doctorId): ?GoogleCalendarAccount
    {
        $row = $this->accountFor($doctorId);

        return $row !== null && $row->is_active ? $this->toGoogleAccount($row) : null;
    }

    /**
     * Cuenta activa que escribe en el calendario indicado (si la hay).
     *
     * Sirve para borrar un evento que quedo en el calendario de un doctor cuando
     * la cita cambia de doctor.
     */
    public function accountForCalendar(string $calendarId): ?GoogleCalendarAccount
    {
        if (! $this->tableReady() || trim($calendarId) === '') {
            return null;
        }

        $row = HealDoctorGoogleAccount::where('calendar_id', $calendarId)
            ->where('is_active', true)
            ->first();

        return $row !== null ? $this->toGoogleAccount($row) : null;
    }

    /**
     * Estado para la pantalla (nunca incluye el token).
     *
     * @return array<string, mixed>
     */
    public function status(int $doctorId): array
    {
        $row = $this->accountFor($doctorId);

        return [
            'connected' => $row !== null && $row->is_active,
            'email' => $row?->account_email,
            'name' => $row?->account_name,
            'picture' => $row?->account_picture,
            'connectedAt' => $row?->connected_at?->format('Y-m-d H:i'),
            'lastSyncAt' => $row?->last_sync_at?->format('Y-m-d H:i'),
            'lastError' => $row?->last_error,
        ];
    }

    /**
     * true si los doctores pueden enlazar su propia agenda.
     */
    public function allowSelfConnect(): bool
    {
        return GoogleCalendarOptions::read()['allow_doctor_connection'];
    }

    /**
     * Guarda la cuenta de Google que acaba de autorizar el doctor.
     *
     * Rechaza la cuenta si es la agenda principal del consultorio (evita enviar
     * dos veces las mismas citas) o si ese calendario ya pertenece a otro doctor.
     *
     * @throws RuntimeException cuando la cuenta no sirve o Google falla.
     */
    public function completeConnection(int $doctorId, int $userId, string $code): HealDoctorGoogleAccount
    {
        $data = $this->google->tokenFromCode($code);

        $refreshToken = trim((string) ($data['refresh_token'] ?? ''));
        $accessToken = $data['access_token'] ?? null;

        if (! is_string($accessToken) || $accessToken === '') {
            throw new RuntimeException('Google no devolvio un token de acceso.');
        }

        $profile = $this->google->accountFromToken($data);
        $email = $profile['email'] ?? null;

        if ($email === null) {
            throw new RuntimeException('Google no entrego el correo de la cuenta. Vuelve a conectar aceptando el acceso al correo.');
        }

        $principalEmail = $this->principalEmail();

        if ($principalEmail !== null && strcasecmp($principalEmail, $email) === 0) {
            throw new RuntimeException('Esa cuenta es la agenda principal del consultorio. Conecta la cuenta personal del doctor.');
        }

        $calendarId = $this->google->primaryCalendarIdWithToken($accessToken);

        $takenByOther = HealDoctorGoogleAccount::where('calendar_id', $calendarId)
            ->where('doctor_id', '<>', $doctorId)
            ->exists();

        if ($takenByOther) {
            throw new RuntimeException('Ese calendario ya esta enlazado a otro doctor.');
        }

        $previous = $this->accountFor($doctorId);

        $row = HealDoctorGoogleAccount::updateOrCreate(
            ['doctor_id' => $doctorId],
            [
                'calendar_id' => $calendarId,
                'refresh_token' => $refreshToken,
                'account_email' => $email,
                'account_name' => $this->limit($profile['name'] ?? null, 190),
                'account_picture' => $this->limit($profile['picture'] ?? null, 500),
                'connected_by_user_id' => $userId,
                'connected_at' => now(),
                'is_active' => true,
                'last_error' => null,
            ]
        );

        // Si el doctor reemplaza una cuenta anterior, su access token en cache
        // ya no sirve.
        if ($previous !== null) {
            $this->google->forgetAccessToken($this->toGoogleAccount($previous));
        }

        return $row;
    }

    /**
     * Desvincula la cuenta del doctor: revoca el permiso en Google y olvida el token.
     *
     * Las citas que ya se escribieron en su calendario se quedan ahi (no se
     * borran de su cuenta), pero dejan de actualizarse. Devuelve el aviso si
     * Google no confirmo la revocacion (null si todo salio bien).
     */
    public function disconnect(int $doctorId): ?string
    {
        $row = $this->accountFor($doctorId);

        if ($row === null) {
            return null;
        }

        $account = $this->toGoogleAccount($row);
        $warning = null;

        try {
            $this->google->revokeToken($account->refreshToken);
        } catch (Throwable $exception) {
            $warning = $exception->getMessage();
        }

        $this->google->forgetAccessToken($account);
        $row->delete();

        return $warning;
    }

    /**
     * Registra el resultado del ultimo envio a la agenda del doctor.
     */
    public function recordSync(int $doctorId, ?string $error = null): void
    {
        $row = $this->accountFor($doctorId);

        if ($row === null) {
            return;
        }

        if ($error === null) {
            $row->forceFill(['last_sync_at' => now(), 'last_error' => null])->save();

            return;
        }

        $row->forceFill(['last_error' => mb_substr($error, 0, 500)])->save();
    }

    /**
     * Correo de la agenda principal (null si no esta conectada).
     */
    private function principalEmail(): ?string
    {
        if (! Schema::hasTable('heal_google_calendar_states') || ! $this->google->isConfigured()) {
            return null;
        }

        $email = HealGoogleCalendarState::where('calendar_id', $this->google->calendarId())
            ->value('account_email');

        return is_string($email) && trim($email) !== '' ? trim($email) : null;
    }

    private function toGoogleAccount(HealDoctorGoogleAccount $row): GoogleCalendarAccount
    {
        return new GoogleCalendarAccount(
            (string) $row->refresh_token,
            (string) $row->calendar_id,
            (int) $row->doctor_id,
        );
    }

    private function limit(mixed $value, int $max): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : mb_substr($text, 0, $max);
    }
}
