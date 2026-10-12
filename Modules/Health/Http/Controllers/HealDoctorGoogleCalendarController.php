<?php

namespace Modules\Health\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Health\Entities\HealDoctor;
use Modules\Health\Services\DoctorGoogleCalendarService;
use Modules\Health\Services\GoogleCalendarService;
use Modules\Health\Support\GoogleCalendarOptions;
use Modules\Health\Support\ResolvesDoctorContext;
use Throwable;

/**
 * Enlace de la agenda de Google Calendar de cada doctor.
 *
 * Un doctor enlaza SU cuenta para que sus citas se escriban en su propio
 * calendario y no en la agenda principal del consultorio. Para enlazar hay que
 * confirmar la contraseña del usuario del sistema (por seguridad); la
 * confirmacion dura pocos minutos y queda atada a un doctor concreto.
 *
 * Reglas de acceso:
 *   - El doctor solo gestiona SU agenda.
 *   - El administrador gestiona la de cualquier doctor.
 *   - Un doctor solo enlaza si el administrador dejo encendida la opcion
 *     `allow_doctor_connection`.
 */
class HealDoctorGoogleCalendarController extends Controller
{
    use ResolvesDoctorContext;

    /** Clave de sesion con la confirmacion de contrasena vigente. */
    public const CONFIRM_SESSION_KEY = 'health.google_calendar.doctor_confirm';

    /** Clave de sesion del flujo OAuth de un doctor (lo lee el callback compartido). */
    public const FLOW_SESSION_KEY = 'health.google_calendar.oauth_flow';

    public function __construct(
        private readonly DoctorGoogleCalendarService $doctors,
        private readonly GoogleCalendarService $google,
    ) {
    }

    /**
     * Confirma la contrasena del usuario y abre una ventana corta para enlazar.
     */
    public function confirm(Request $request): JsonResponse
    {
        $data = $request->validate([
            'doctor_id' => ['required', 'integer', 'exists:heal_doctors,id'],
            'password' => ['required', 'string'],
        ]);

        $doctorId = (int) $data['doctor_id'];

        if (! $this->canManage($doctorId)) {
            return response()->json(['message' => 'Solo puedes enlazar tu propia agenda.'], 403);
        }

        if (! $this->canEnlace()) {
            return response()->json([
                'message' => 'El administrador desactivó que los doctores enlacen su agenda de Google.',
            ], 403);
        }

        // Sin esto, el doctor acaba en la pantalla "Acceso bloqueado" de Google
        // sin entender por qué. Mejor explicarlo aquí, antes de salir.
        if (($problem = $this->google->redirectUriProblem()) !== null) {
            return response()->json(['message' => $problem], 409);
        }

        $user = $request->user();

        if (! Hash::check($data['password'], (string) $user->password)) {
            return response()->json(['message' => 'La contraseña no coincide con la de tu usuario del sistema.'], 422);
        }

        $minutes = GoogleCalendarOptions::read()['confirm_minutes'];

        session([self::CONFIRM_SESSION_KEY => [
            'doctor_id' => $doctorId,
            'user_id' => (int) $user->id,
            'until' => now()->addMinutes($minutes)->timestamp,
        ]]);

        return response()->json([
            'ok' => true,
            'connectUrl' => route('heal_doctor_google_connect', ['doctor' => $doctorId]),
        ]);
    }

    /**
     * Manda al doctor al consentimiento de Google, solo con la confirmacion vigente.
     */
    public function connect(Request $request, int $doctor): RedirectResponse
    {
        if (! $this->canManage($doctor)) {
            abort(403);
        }

        if (! $this->canEnlace()) {
            return back()->with('error', 'El administrador desactivó que los doctores enlacen su agenda de Google.');
        }

        if (($problem = $this->google->redirectUriProblem()) !== null) {
            return back()->with('error', $problem);
        }

        if (! $this->confirmationValid($request, $doctor)) {
            return back()->with('error', 'Confirma tu contraseña para enlazar la agenda. Vuelve a intentarlo desde el botón.');
        }

        if (! $this->google->hasClientCredentials()) {
            return back()->with('error', 'Falta registrar el Client ID y el Client Secret de Google (lo hace el administrador en Parámetros del sistema).');
        }

        $state = Str::random(40);

        session([self::FLOW_SESSION_KEY => [
            'type' => 'doctor',
            'doctor_id' => $doctor,
            'user_id' => (int) $request->user()->id,
            'state' => $state,
            // Volvemos a la pantalla desde la que se inicio el enlace.
            'return' => url()->previous(),
        ]]);

        return redirect()->away($this->google->authorizationUrl($state));
    }

    /**
     * Recibe el codigo de Google para el enlace de un doctor.
     *
     * Lo llama HealGoogleCalendarController::callback cuando la sesion tiene un
     * flujo de doctor (el redirect URI es el mismo que el del consultorio).
     */
    public function callback(Request $request): RedirectResponse
    {
        $flow = session(self::FLOW_SESSION_KEY);

        session()->forget([self::FLOW_SESSION_KEY, self::CONFIRM_SESSION_KEY]);

        $return = $this->returnUrl($flow);

        if (! is_array($flow) || ($flow['type'] ?? '') !== 'doctor') {
            return redirect()->to($return)->with('error', 'La conexión con Google expiró. Inténtalo de nuevo.');
        }

        $expectedState = (string) ($flow['state'] ?? '');
        $receivedState = (string) $request->query('state', '');

        if ($expectedState === '' || ! hash_equals($expectedState, $receivedState)) {
            return redirect()->to($return)->with('error', 'La conexión con Google expiró o el origen no coincide. Inténtalo de nuevo.');
        }

        // Quien termina el enlace tiene que ser quien lo empezo.
        if ((int) ($flow['user_id'] ?? 0) !== (int) $request->user()->id) {
            return redirect()->to($return)->with('error', 'La conexión con Google fue iniciada por otra cuenta.');
        }

        $error = trim((string) $request->query('error', ''));

        if ($error !== '') {
            return redirect()->to($return)->with('error', 'Google rechazó la conexión: ' . $error);
        }

        $code = trim((string) $request->query('code', ''));

        if ($code === '') {
            return redirect()->to($return)->with('error', 'Google no devolvió el código de autorización.');
        }

        try {
            $row = $this->doctors->completeConnection(
                (int) $flow['doctor_id'],
                (int) $request->user()->id,
                $code,
            );
        } catch (Throwable $exception) {
            return redirect()->to($return)->with('error', 'No se pudo enlazar la agenda: ' . $exception->getMessage());
        }

        return redirect()->to($return)->with(
            'message',
            'Agenda de Google enlazada (' . ($row->account_email ?: 'cuenta de Google') . '). '
            . 'Las citas de este doctor se enviarán a su propio calendario.'
        );
    }

    /**
     * Desvincula la agenda del doctor (el doctor o el administrador).
     */
    public function disconnect(int $doctor): RedirectResponse
    {
        if (! $this->canManage($doctor)) {
            abort(403);
        }

        $warning = $this->doctors->disconnect($doctor);

        $message = 'Agenda de Google desenlazada. Las citas que ya están en su calendario se quedan ahí, pero ya no se actualizan.';

        if ($warning !== null) {
            return back()->with('message', $message . ' Aviso: Google no confirmó la revocación del permiso (' . $warning . ').');
        }

        return back()->with('message', $message);
    }

    /**
     * true si el usuario puede gestionar la agenda de ese doctor.
     */
    private function canManage(int $doctorId): bool
    {
        if ($this->canChooseDoctor()) {
            return HealDoctor::whereKey($doctorId)->exists();
        }

        return (int) ($this->currentDoctor()?->id ?? 0) === $doctorId;
    }

    /**
     * true si el enlace esta permitido para este usuario (el administrador siempre puede).
     */
    private function canEnlace(): bool
    {
        return $this->canChooseDoctor() || $this->doctors->allowSelfConnect();
    }

    /**
     * true si la confirmacion de contrasena sigue vigente para este doctor y usuario.
     */
    private function confirmationValid(Request $request, int $doctor): bool
    {
        $confirmed = session(self::CONFIRM_SESSION_KEY);

        return is_array($confirmed)
            && (int) ($confirmed['doctor_id'] ?? 0) === $doctor
            && (int) ($confirmed['user_id'] ?? 0) === (int) $request->user()->id
            && (int) ($confirmed['until'] ?? 0) >= now()->timestamp;
    }

    /**
     * Pantalla a la que se vuelve al terminar el flujo (solo rutas internas de la app).
     *
     * @param mixed $flow
     */
    private function returnUrl(mixed $flow): string
    {
        $return = is_array($flow) ? (string) ($flow['return'] ?? '') : '';

        // Solo se acepta una URL de este mismo host; si no, la agenda.
        if ($return !== '' && str_starts_with($return, url('/'))) {
            return $return;
        }

        return route('heal_agendas_list');
    }
}
