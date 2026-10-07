<?php

namespace Modules\Health\Services;

use App\Models\Parameter;
use Carbon\Carbon;
use Modules\Dental\Entities\DentAppointment;
use Modules\Health\Entities\HealAppointmentNotice;
use Modules\Health\Entities\HealSetting;
use Modules\Health\Support\AppointmentNoticeMessage;
use Modules\Health\Support\HealthPhoneNumber;
use Modules\Integrationhub\Exceptions\SmsgateRejectedException;
use Modules\Integrationhub\Services\SmsgateService;
use RuntimeException;

/**
 * Avisos de citas del modulo Salud.
 *
 * Reune lo que necesitan el comando, el job y la pantalla de configuracion:
 * el estado del canal (parametro SC-00009 y credenciales propias de Salud), el
 * telefono del paciente, el texto final del aviso y el envio por SMSGate.
 *
 * El envio real lo hace el job en la cola; este servicio no encola nada.
 *
 * El telefono del destinatario se valida con la regla de Salud (celular del
 * Peru: 9 digitos que empiezan en 9) antes de llamar al API: un numero que no
 * la cumple lanza SmsgateRejectedException, de modo que la entrega queda
 * fallida con un motivo legible en lugar de viajar como "+987987987" y volver
 * rechazada por SMSGate.
 */
class AppointmentNoticeService
{
    private SmsgateService $smsgate;

    /** Nombre del consultorio ya resuelto (se usa en cada mensaje). */
    private ?string $clinicName = null;

    private bool $clinicResolved = false;

    public function __construct()
    {
        // Credenciales propias de Salud (SC-00006, SC-00007 y SC-00008).
        $this->smsgate = new SmsgateService('health.notifications.smsgate');
    }

    /**
     * Codigo del parametro que enciende el canal (SC-00009 por defecto).
     */
    public function channelParameterCode(): string
    {
        return trim((string) config('health.notifications.smsgate.enabled_parameter', 'SC-00009'));
    }

    /**
     * Codigos de los parametros que consume el canal.
     *
     * @return array{url: string, username: string, password: string, enabled: string}
     */
    public function parameterCodes(): array
    {
        return [
            'url' => (string) config('health.notifications.smsgate.url_parameter', 'SC-00006'),
            'username' => (string) config('health.notifications.smsgate.username_parameter', 'SC-00007'),
            'password' => (string) config('health.notifications.smsgate.password_parameter', 'SC-00008'),
            'enabled' => $this->channelParameterCode(),
        ];
    }

    /**
     * true solo si el interruptor Activo (SC-00009) esta encendido.
     */
    public function isChannelActive(): bool
    {
        $value = strtolower(trim((string) $this->parameterValue($this->channelParameterCode())));

        return in_array($value, ['1', 'true'], true);
    }

    /**
     * true solo si estan la URL, el usuario y la contrasena de SMSGate (Salud).
     */
    public function isConfigured(): bool
    {
        return $this->smsgate->isConfigured();
    }

    /**
     * Estado de cada credencial, para los avisos de la pantalla.
     *
     * @return array{url: bool, username: bool, password: bool, configured: bool, active: bool}
     */
    public function channelStatus(): array
    {
        $codes = $this->parameterCodes();

        return [
            'url' => $this->parameterValue($codes['url']) !== null,
            'username' => $this->parameterValue($codes['username']) !== null,
            'password' => $this->parameterValue($codes['password']) !== null,
            'configured' => $this->isConfigured(),
            'active' => $this->isChannelActive(),
        ];
    }

    /**
     * Id del parametro del interruptor Activo (para la pantalla).
     */
    public function channelParameterId(): ?int
    {
        $id = Parameter::where('parameter_code', $this->channelParameterCode())->value('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * Variables del mensaje con los datos reales de la cita.
     *
     * @return array<string, string>
     */
    public function noticeVariables(DentAppointment $appointment): array
    {
        $appointmentAt = $this->appointmentAt($appointment);

        return [
            'paciente' => trim((string) ($appointment->patient?->full_name ?? '')) ?: 'paciente',
            'hora_cita' => $appointmentAt->format('H:i'),
            'fecha_cita' => $appointmentAt->format('d/m/Y'),
            'nombre_dr' => trim((string) ($appointment->doctor?->full_name ?? '')),
            'clinica' => $this->clinicName(),
        ];
    }

    /**
     * Texto final (plano, listo para SMS) del aviso para una cita.
     */
    public function renderNoticeText(HealAppointmentNotice $notice, DentAppointment $appointment): string
    {
        return AppointmentNoticeMessage::toPlainText(
            AppointmentNoticeMessage::render($notice->message, $this->noticeVariables($appointment))
        );
    }

    /**
     * Texto de ejemplo para la vista previa y la prueba (sin cita real).
     */
    public function renderSampleText(?string $message): string
    {
        return AppointmentNoticeMessage::toPlainText(
            AppointmentNoticeMessage::render($message, AppointmentNoticeMessage::sampleValues())
        );
    }

    /**
     * Telefono del paciente: el guardado en la cita y, si falta, el de la ficha.
     */
    public function recipientPhone(DentAppointment $appointment): ?string
    {
        $phone = trim((string) ($appointment->telephone ?? ''));

        if ($phone === '') {
            $phone = trim((string) ($appointment->patient?->telephone ?? ''));
        }

        return $phone === '' ? null : $phone;
    }

    /**
     * Envia el aviso de una cita al telefono indicado.
     *
     * @throws SmsgateRejectedException cuando el telefono no es un celular del Peru.
     * @throws RuntimeException cuando falta configuracion o el fallo es transitorio.
     */
    public function sendTo(string $phone, HealAppointmentNotice $notice, DentAppointment $appointment): void
    {
        $this->smsgate->send($this->smsDestination($phone), $this->renderNoticeText($notice, $appointment));
    }

    /**
     * Envia un texto ya armado (prueba desde la pantalla).
     *
     * @throws SmsgateRejectedException cuando el telefono no es un celular del Peru.
     * @throws RuntimeException cuando falta configuracion o el fallo es transitorio.
     */
    public function sendTest(string $phone, string $text): void
    {
        $this->smsgate->send($this->smsDestination($phone), $text);
    }

    /**
     * Telefono de envio en E.164 sin "+" (51 + los 9 digitos).
     *
     * @throws SmsgateRejectedException cuando el valor no es un celular del Peru.
     */
    private function smsDestination(string $phone): string
    {
        $number = HealthPhoneNumber::toE164($phone);

        if ($number === null) {
            throw new SmsgateRejectedException(
                'El telefono "' . mb_substr(trim($phone), 0, 30) . '" no es un celular del Peru utilizable '
                . '(9 digitos que empiezan en 9, sin el +51).'
            );
        }

        return $number;
    }

    /**
     * Momento de la cita (fecha + hora) normalizado.
     */
    public function appointmentAt(DentAppointment $appointment): Carbon
    {
        $date = $appointment->date_appointmen
            ? Carbon::parse($appointment->date_appointmen)->toDateString()
            : now()->toDateString();
        $time = (string) ($appointment->time_appointmen ?: '00:00:00');

        return Carbon::parse($date . ' ' . $time)->seconds(0);
    }

    /**
     * Nombre del consultorio (una sola consulta por instancia).
     */
    private function clinicName(): string
    {
        if (! $this->clinicResolved) {
            $this->clinicResolved = true;
            $this->clinicName = trim((string) (HealSetting::first()?->establishment_name ?? ''));
        }

        return (string) $this->clinicName;
    }

    /**
     * Valor de un parametro del sistema (null si no existe o esta vacio).
     */
    private function parameterValue(string $code): ?string
    {
        $code = trim($code);

        if ($code === '') {
            return null;
        }

        $value = trim((string) Parameter::where('parameter_code', $code)->value('value_default'));

        return $value === '' ? null : $value;
    }
}
