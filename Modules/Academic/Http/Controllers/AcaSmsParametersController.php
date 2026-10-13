<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Modules\Academic\Services\SchoolGateNoticeService;
use Modules\Academic\Support\PhoneNumberFormatter;
use Modules\Integrationhub\Exceptions\SmsgateRejectedException;

/**
 * Pantalla Academico > Parametros / SMS.
 *
 * Configura el canal propio de los avisos de asistencia del colegio: la URL,
 * el usuario y la contraseña de SMSGate (parametros SC-00020, SC-00021 y
 * SC-00022), el interruptor general, los avisos por entrada, salida, tardanza
 * y salida anticipada, las plantillas del mensaje y el limite diario.
 *
 * La contraseña nunca viaja al navegador: si el campo llega vacio o con la
 * mascara se conserva la guardada. La pantalla tambien permite ver la vista
 * previa del mensaje y enviar un SMS de prueba con datos de ejemplo.
 */
class AcaSmsParametersController extends Controller
{
    /** Centinela de la mascara: si llega este valor no se pisa la contraseña. */
    private const SECRET_PLACEHOLDER = '********';

    private const EVENTS = [
        SchoolGateNoticeService::EVENT_ENTRY,
        SchoolGateNoticeService::EVENT_EXIT,
    ];

    public function __construct(
        private readonly SchoolGateNoticeService $notices,
    ) {
    }

    public function index()
    {
        return Inertia::render('Academic::Parametros/Sms', [
            'channel' => array_merge($this->notices->channelStatus(), [
                'parameterCodes' => $this->notices->parameterCodes(),
            ]),
            'settings' => $this->notices->settings(),
            'variables' => $this->notices->variables(),
            'deliveries' => Schema::hasTable('aca_school_notice_deliveries')
                ? $this->notices->recentDeliveries()
                : [],
            'countryCode' => (string) config('academic.school_notices.smsgate.country_code', '51'),
            'parametersUrl' => route('parameters'),
        ]);
    }

    /**
     * Guarda la configuracion del canal y sus avisos.
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'url' => ['nullable', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'active' => ['required', 'boolean'],
            'notify_entry' => ['required', 'boolean'],
            'notify_exit' => ['required', 'boolean'],
            'notify_late' => ['required', 'boolean'],
            'notify_early_exit' => ['required', 'boolean'],
            'primary_only' => ['required', 'boolean'],
            'daily_limit' => ['nullable', 'integer', 'min:0', 'max:999'],
            'entry_message' => ['nullable', 'string', 'max:480'],
            'exit_message' => ['nullable', 'string', 'max:480'],
        ]);

        $this->notices->saveParameter('url_parameter', 'SC-00020', $this->blankToNull($data['url'] ?? null));
        $this->notices->saveParameter('username_parameter', 'SC-00021', $this->blankToNull($data['username'] ?? null));

        // La contraseña solo se reemplaza cuando llega un valor nuevo de verdad.
        if ($this->isNewSecretValue($data['password'] ?? null)) {
            $this->notices->saveParameter('password_parameter', 'SC-00022', trim((string) $data['password']));
        }

        $this->notices->saveParameter('enabled_parameter', 'SC-00023', $this->flagValue($data['active']));
        $this->notices->saveParameter('entry_parameter', 'SC-00024', $this->flagValue($data['notify_entry']));
        $this->notices->saveParameter('exit_parameter', 'SC-00025', $this->flagValue($data['notify_exit']));
        $this->notices->saveParameter('late_parameter', 'SC-00026', $this->flagValue($data['notify_late']));
        $this->notices->saveParameter('early_exit_parameter', 'SC-00027', $this->flagValue($data['notify_early_exit']));
        $this->notices->saveParameter('primary_only_parameter', 'SC-00030', $this->flagValue($data['primary_only']));

        $this->notices->saveParameter('entry_message_parameter', 'SC-00028', $this->blankToNull($data['entry_message'] ?? null));
        $this->notices->saveParameter('exit_message_parameter', 'SC-00029', $this->blankToNull($data['exit_message'] ?? null));
        $this->notices->saveParameter('daily_limit_parameter', 'SC-00031', (string) max(0, (int) ($data['daily_limit'] ?? 0)));

        return back()->with('message', 'Parámetros de los avisos por SMS guardados.');
    }

    /**
     * Texto final (plano) de un mensaje con datos de ejemplo.
     */
    public function preview(Request $request)
    {
        $data = $request->validate([
            'event' => ['nullable', Rule::in(self::EVENTS)],
            'message' => ['nullable', 'string', 'max:480'],
        ]);

        return response()->json([
            'text' => $this->notices->renderSampleText(
                $data['event'] ?? SchoolGateNoticeService::EVENT_ENTRY,
                $data['message'] ?? null
            ),
        ]);
    }

    /**
     * Envia un SMS de prueba con el mensaje del evento elegido (datos de ejemplo).
     */
    public function test(Request $request)
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'event' => ['nullable', Rule::in(self::EVENTS)],
            'message' => ['nullable', 'string', 'max:480'],
        ]);

        if (! $this->notices->isConfigured()) {
            throw ValidationException::withMessages([
                'phone' => 'Falta configurar SMSGate: revisa la URL (SC-00020), el usuario (SC-00021) y la contraseña (SC-00022).',
            ]);
        }

        if (! $this->notices->isChannelActive()) {
            throw ValidationException::withMessages([
                'phone' => 'El canal está desactivado: enciende el interruptor general (SC-00023) para enviar avisos.',
            ]);
        }

        $event = $data['event'] ?? SchoolGateNoticeService::EVENT_ENTRY;
        $number = PhoneNumberFormatter::toE164(
            $data['phone'],
            null,
            (string) config('academic.school_notices.smsgate.country_code', '51')
        );

        if ($number === null) {
            throw ValidationException::withMessages([
                'phone' => 'El teléfono no es utilizable para SMS: escribe un celular de 9 dígitos o con su código de país.',
            ]);
        }

        $text = $this->notices->renderSampleText($event, $data['message'] ?? null);

        try {
            $this->notices->sendTo($number, $text);
        } catch (SmsgateRejectedException $exception) {
            throw ValidationException::withMessages([
                'phone' => 'SMSGate rechazó el envío: ' . $exception->getMessage(),
            ]);
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages([
                'phone' => 'No se pudo enviar el SMS: ' . $exception->getMessage(),
            ]);
        }

        return response()->json([
            'message' => 'SMS de prueba enviado a ' . PhoneNumberFormatter::toDisplay($number) . '.',
            'text' => $text,
        ]);
    }

    /** Valor de interruptor ('1'/'0') a partir de un booleano. */
    private function flagValue(mixed $value): string
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
    }

    /** Cadena con contenido o null (para no guardar espacios vacíos). */
    private function blankToNull(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /** true si la contraseña recibida es un valor nuevo de verdad. */
    private function isNewSecretValue(?string $value): bool
    {
        $value = trim((string) $value);

        return $value !== '' && $value !== self::SECRET_PLACEHOLDER;
    }
}
