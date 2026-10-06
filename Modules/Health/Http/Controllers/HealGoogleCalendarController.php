<?php

namespace Modules\Health\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Health\Entities\HealDoctor;
use Modules\Health\Entities\HealGoogleCalendarEvent;
use Modules\Health\Entities\HealPatient;
use Modules\Health\Jobs\PullGoogleCalendarChanges;
use Modules\Health\Services\GoogleCalendarService;
use Modules\Health\Services\GoogleCalendarSyncService;

/**
 * Pantalla Salud > Google Calendar.
 *
 * Muestra el estado del canal (interruptor Activo SC-00010, credenciales,
 * calendario y canal de notificaciones), los ultimos movimientos de la
 * sincronizacion y la bandeja "Por revisar" con los eventos que llegaron de
 * Google y no se pudieron resolver solos (falta paciente o doctor).
 *
 * Desde aqui se conecta y desconecta la cuenta de Google, se encola una
 * sincronizacion manual y se renueva el canal de notificaciones push.
 */
class HealGoogleCalendarController extends Controller
{
    /** Clave de sesion donde viaja el `state` de OAuth. */
    private const STATE_SESSION_KEY = 'health.google_calendar.oauth_state';

    public function __construct(
        private readonly GoogleCalendarSyncService $sync,
        private readonly GoogleCalendarService $google,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('Health::GoogleCalendar/Index', [
            'channel' => $this->sync->status(),
            'reviewItems' => $this->sync->reviewItems()
                ->map(fn (HealGoogleCalendarEvent $item) => $this->reviewItem($item))
                ->values(),
            'movements' => $this->sync->recentMovements()
                ->map(fn (HealGoogleCalendarEvent $item) => $this->movement($item))
                ->values(),
            'patients' => $this->patientOptions(),
            'doctors' => $this->doctorOptions(),
            'parametersUrl' => route('parameters'),
            'reconcileMinutes' => (int) config('health.google_calendar.reconcile_minutes', 5),
        ]);
    }

    /**
     * Inicia el consentimiento de Google (OAuth 2.0).
     */
    public function connect(): RedirectResponse
    {
        if ($this->google->clientId() === null || $this->google->clientSecret() === null) {
            return back()->with('error', 'Registra el Client ID y el Client Secret (una sola vez) en Parámetros del sistema antes de conectar.');
        }

        $state = Str::random(40);

        session([self::STATE_SESSION_KEY => $state]);

        return redirect()->away($this->google->authorizationUrl($state));
    }

    /**
     * Recibe el codigo de autorizacion y guarda el refresh token.
     */
    public function callback(Request $request): RedirectResponse
    {
        $expectedState = (string) session(self::STATE_SESSION_KEY);
        $receivedState = (string) $request->query('state', '');

        session()->forget(self::STATE_SESSION_KEY);

        if ($expectedState === '' || ! hash_equals($expectedState, $receivedState)) {
            return redirect()->route('heal_google_calendar')
                ->with('error', 'La conexión con Google expiró o el origen no coincide. Inténtalo de nuevo.');
        }

        $error = trim((string) $request->query('error', ''));

        if ($error !== '') {
            return redirect()->route('heal_google_calendar')
                ->with('error', 'Google rechazó la conexión: ' . $error);
        }

        $code = trim((string) $request->query('code', ''));

        if ($code === '') {
            return redirect()->route('heal_google_calendar')
                ->with('error', 'Google no devolvió el código de autorización.');
        }

        try {
            $tokenData = $this->google->exchangeCode($code);
        } catch (\Throwable $exception) {
            return redirect()->route('heal_google_calendar')
                ->with('error', 'No se pudo conectar con Google: ' . $exception->getMessage());
        }

        // El id_token que pide el scope `openid email` permite mostrar con que
        // cuenta quedo conectado el calendario del consultorio.
        try {
            $this->sync->storeAccount($this->google->accountFromToken($tokenData));
        } catch (\Throwable) {
            // Solo es un dato de pantalla: si falla, la conexion sigue en pie.
        }

        $email = $this->sync->account()['email'];

        return redirect()->route('heal_google_calendar')
            ->with('message', 'Cuenta de Google conectada' . ($email !== null ? ' (' . $email . ')' : '') . '. Enciende el interruptor Activo y pulsa "Sincronizar ahora".');
    }

    /**
     * Revoca el permiso en Google y olvida la cuenta conectada.
     */
    public function disconnect(): RedirectResponse
    {
        $revokeError = null;

        // La revocacion va primero: necesita el refresh token que se borra abajo.
        try {
            $this->google->revoke();
        } catch (\Throwable $exception) {
            $revokeError = $exception->getMessage();
        }

        $this->google->disconnect();
        $this->sync->forgetAccount();

        if ($revokeError !== null) {
            return redirect()->route('heal_google_calendar')->with(
                'message',
                'Cuenta desconectada en el sistema. Aviso: Google no confirmo la revocacion del permiso (' . $revokeError . '); puedes retirarlo desde tu cuenta de Google.'
            );
        }

        return redirect()->route('heal_google_calendar')
            ->with('message', 'Cuenta de Google desconectada y permiso revocado en tu cuenta.');
    }

    /**
     * Prueba la conexion con Google y deja el diagnostico en la pantalla.
     *
     * Es el boton "Probar conexion": revisa credenciales, cuenta conectada,
     * token, acceso al calendario y URL de notificaciones, y explica en
     * espanol que corregir cuando algo falla.
     */
    public function test(): RedirectResponse
    {
        $results = $this->sync->diagnose();

        $failed = array_values(array_filter($results, fn (array $check) => ! $check['ok']));

        $redirect = back()->with('diagnostics', $results);

        if ($failed === []) {
            return $redirect->with('message', 'Conexion con Google verificada: todo en orden.');
        }

        return $redirect->with('error', 'Revisa el diagnostico: ' . $failed[0]['label'] . ' — ' . $failed[0]['message']);
    }

    /**
     * Encola una lectura de los cambios de Google.
     */
    public function syncNow(): RedirectResponse
    {
        if (! $this->sync->ready()) {
            return back()->with('error', 'La sincronización no está lista: revisa el interruptor Activo y las credenciales de Google.');
        }

        PullGoogleCalendarChanges::dispatch();

        return back()->with('message', 'Sincronización encolada. Los cambios aparecen al terminar el worker de la cola.');
    }

    /**
     * Registra o renueva el canal de notificaciones push.
     */
    public function refreshChannel(): RedirectResponse
    {
        $result = $this->sync->refreshChannel();

        return $result['ok']
            ? back()->with('message', $result['message'] . ' Vence el ' . ($result['expiresAt'] ?? 'sin fecha') . '.')
            : back()->with('error', $result['message']);
    }

    /**
     * Trae a la Agenda un evento de la bandeja "Por revisar".
     */
    public function review(Request $request, int $mapping): RedirectResponse
    {
        $data = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:heal_patients,id'],
            'doctor_id' => ['nullable', 'integer', 'exists:heal_doctors,id'],
        ]);

        try {
            $appointment = $this->sync->review((int) $mapping, (int) $data['patient_id'], isset($data['doctor_id']) ? (int) $data['doctor_id'] : null);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            return back()->with('error', 'No se pudo traer el evento: ' . $exception->getMessage());
        }

        return back()->with('message', 'Cita creada desde Google Calendar: #' . ($appointment->correlative ?: $appointment->id) . '.');
    }

    /**
     * Descarta un evento de la bandeja (no se trae al sistema).
     */
    public function discard(int $mapping): RedirectResponse
    {
        $this->sync->discard($mapping);

        return back()->with('message', 'Evento descartado. No se volverá a ofrecer.');
    }

    /**
     * Evento de la bandeja con la sugerencia de paciente ya resuelta.
     *
     * @return array<string, mixed>
     */
    private function reviewItem(HealGoogleCalendarEvent $item): array
    {
        $payload = is_array($item->payload) ? $item->payload : [];
        $suggested = $this->suggestedPatientName((string) ($item->summary ?? ''));

        return [
            'id' => $item->id,
            'summary' => $item->summary,
            'starts_at' => $item->starts_at?->format('Y-m-d H:i'),
            'ends_at' => $item->ends_at?->format('Y-m-d H:i'),
            'location' => $item->location,
            'description' => (string) ($payload['description'] ?? ''),
            'suggested_patient' => $suggested,
            'updated_at' => $item->updated_at?->format('Y-m-d H:i'),
        ];
    }

    /**
     * Movimiento de la sincronizacion.
     *
     * @return array<string, mixed>
     */
    private function movement(HealGoogleCalendarEvent $item): array
    {
        return [
            'id' => $item->id,
            'state' => $item->sync_state,
            'origin' => $item->origin,
            'summary' => $item->summary,
            'appointment_id' => $item->appointment_id,
            'correlative' => $item->appointment?->correlative,
            'patient' => $item->appointment?->patient?->full_name,
            'error' => $item->error_message,
            'updated_at' => $item->updated_at?->format('Y-m-d H:i'),
        ];
    }

    /**
     * Nombre del paciente que abre el titulo del evento (sugerencia de la UI).
     */
    private function suggestedPatientName(string $summary): ?string
    {
        $summary = trim($summary);

        if ($summary === '') {
            return null;
        }

        $parts = preg_split('/\s+[—–-]\s+/u', $summary, 2);
        $candidate = trim((string) ($parts[0] ?? ''));

        return $candidate === '' ? null : $candidate;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function patientOptions(): array
    {
        return HealPatient::with('person')
            ->orderBy('id')
            ->get()
            ->map(fn (HealPatient $patient) => [
                'code' => $patient->id,
                'name' => $patient->person?->full_name,
                'telephone' => $patient->person?->telephone,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function doctorOptions(): array
    {
        return HealDoctor::with('person')
            ->orderBy('id')
            ->get()
            ->map(fn (HealDoctor $doctor) => [
                'code' => $doctor->id,
                'name' => $doctor->person?->full_name,
                'specialty' => $doctor->specialty,
            ])
            ->values()
            ->all();
    }
}
