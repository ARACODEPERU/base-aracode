<?php

namespace Modules\Academic\Services;

use App\Models\Parameter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\Academic\Entities\AcaSchoolEnrollment;
use Modules\Academic\Entities\AcaSchoolGateAttendance;
use Modules\Academic\Entities\AcaSchoolNoticeDelivery;
use Modules\Academic\Entities\AcaSchoolStudent;
use Modules\Academic\Entities\AcaSchoolStudentGuardian;
use Modules\Academic\Jobs\SendSchoolGateNotice;
use Modules\Academic\Support\GateNoticeMessage;
use Modules\Academic\Support\PhoneNumberFormatter;
use Modules\Integrationhub\Services\SmsgateService;

/**
 * Avisos de asistencia del colegio al padre o apoderado.
 *
 * Reúne lo que necesitan la portería, el job y la pantalla de configuración:
 * el estado del canal SMSGate propio del módulo escolar (parámetros SC-00020 a
 * SC-00032), los destinatarios de cada alumno, la plantilla del mensaje y el
 * encolado del envío.
 *
 * El envío real lo hace SendSchoolGateNotice en la cola: la portería registra
 * el escaneo y sigue; si el servidor de SMSGate tarda o falla, la puerta no se
 * detiene.
 */
class SchoolGateNoticeService
{
    /** Evento de portería: entrada al colegio (mismo valor que la asistencia). */
    public const EVENT_ENTRY = AcaSchoolGateAttendance::EVENT_ENTRADA;

    /** Evento de portería: salida del colegio (mismo valor que la asistencia). */
    public const EVENT_EXIT = AcaSchoolGateAttendance::EVENT_SALIDA;

    private SmsgateService $smsgate;

    private SchoolTelegramNoticeService $telegramNotices;

    public function __construct(?SchoolTelegramNoticeService $telegramNotices = null)
    {
        // Credenciales propias del módulo escolar (SC-00020, SC-00021 y SC-00022).
        $this->smsgate = new SmsgateService('academic.school_notices.smsgate');
        $this->telegramNotices = $telegramNotices ?? new SchoolTelegramNoticeService();
    }

    /**
     * Servicio de avisos por Telegram del colegio.
     */
    public function telegram(): SchoolTelegramNoticeService
    {
        return $this->telegramNotices;
    }

    /**
     * Códigos de los parámetros que consume el canal.
     *
     * @return array<string, string>
     */
    public function parameterCodes(): array
    {
        return [
            'url' => $this->parameterCode('url_parameter', 'SC-00020'),
            'username' => $this->parameterCode('username_parameter', 'SC-00021'),
            'password' => $this->parameterCode('password_parameter', 'SC-00022'),
            'enabled' => $this->parameterCode('enabled_parameter', 'SC-00023'),
            'entry' => $this->parameterCode('entry_parameter', 'SC-00024'),
            'exit' => $this->parameterCode('exit_parameter', 'SC-00025'),
            'late' => $this->parameterCode('late_parameter', 'SC-00026'),
            'early_exit' => $this->parameterCode('early_exit_parameter', 'SC-00027'),
            'entry_message' => $this->parameterCode('entry_message_parameter', 'SC-00028'),
            'exit_message' => $this->parameterCode('exit_message_parameter', 'SC-00029'),
            'primary_only' => $this->parameterCode('primary_only_parameter', 'SC-00030'),
            'daily_limit' => $this->parameterCode('daily_limit_parameter', 'SC-00031'),
            'interval_ms' => $this->parameterCode('interval_ms_parameter', 'SC-00032'),
        ];
    }

    /** true solo si están la URL, el usuario y la contraseña de SMSGate. */
    public function isConfigured(): bool
    {
        return $this->smsgate->isConfigured();
    }

    /** true solo si el interruptor general (SC-00023) está encendido. */
    public function isChannelActive(): bool
    {
        return $this->flag('enabled_parameter', 'SC-00023');
    }

    /** URL del servidor de SMSGate (la misma que usa la aplicación móvil). */
    public function gateUrl(): string
    {
        return $this->smsgate->url();
    }

    /**
     * Estado del canal y de cada interruptor, para la pantalla.
     *
     * @return array<string, bool>
     */
    public function channelStatus(): array
    {
        return [
            'url' => $this->parameterValue('url_parameter', 'SC-00020') !== null,
            'username' => $this->parameterValue('username_parameter', 'SC-00021') !== null,
            'password' => $this->parameterValue('password_parameter', 'SC-00022') !== null,
            'configured' => $this->isConfigured(),
            'active' => $this->isChannelActive(),
            'entry' => $this->flag('entry_parameter', 'SC-00024'),
            'exit' => $this->flag('exit_parameter', 'SC-00025'),
            'late' => $this->flag('late_parameter', 'SC-00026'),
            'early_exit' => $this->flag('early_exit_parameter', 'SC-00027'),
            'primary_only' => $this->flag('primary_only_parameter', 'SC-00030'),
        ];
    }

    /**
     * Valores editables del canal, para rellenar el formulario.
     *
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        return [
            'url' => (string) ($this->parameterValue('url_parameter', 'SC-00020') ?? $this->config('default_url', 'https://api.sms-gate.app/mobile/v1')),
            'username' => (string) ($this->parameterValue('username_parameter', 'SC-00021') ?? ''),
            'entry_message' => $this->messageTemplate(self::EVENT_ENTRY),
            'exit_message' => $this->messageTemplate(self::EVENT_EXIT),
            'daily_limit' => $this->dailyLimit(),
            'interval_ms' => $this->intervalMs(),
        ];
    }

    /** Plantilla configurada del evento (o el texto por defecto). */
    public function messageTemplate(string $event): string
    {
        $value = $this->parameterValue(
            $event === self::EVENT_EXIT ? 'exit_message_parameter' : 'entry_message_parameter',
            $event === self::EVENT_EXIT ? 'SC-00029' : 'SC-00028'
        );

        if ($value !== null) {
            return $value;
        }

        return (string) $this->config(
            $event === self::EVENT_EXIT ? 'default_exit_message' : 'default_entry_message',
            ''
        );
    }

    /** Máximo de avisos por alumno y día (0 = sin límite). */
    public function dailyLimit(): int
    {
        $value = (int) ($this->parameterValue('daily_limit_parameter', 'SC-00031') ?? 0);

        return max(0, min(999, $value));
    }

    /**
     * Pausa mínima entre cada mensaje SMS, en milisegundos (anti-SPAM de los
     * operadores). Por defecto 2000 ms. Se limita a 0-60000 para que un valor
     * mal cargado no bloquee la cola por horas.
     */
    public function intervalMs(): int
    {
        $raw = $this->parameterValue('interval_ms_parameter', 'SC-00032');

        if ($raw === null) {
            return max(0, min(60000, (int) $this->config('interval_ms', 2000)));
        }

        if (! is_numeric(trim($raw))) {
            return 2000;
        }

        return max(0, min(60000, (int) $raw));
    }

    /** true si los avisos se envían solo al apoderado marcado como principal. */
    public function primaryOnly(): bool
    {
        return $this->flag('primary_only_parameter', 'SC-00030');
    }

    /**
     * true si el evento debe generar aviso: el interruptor general encendido,
     * el del evento encendido y (cuando corresponde) el de tardanza o el de
     * salida anticipada.
     */
    public function isEventEnabled(string $event, ?string $entryStatus, bool $earlyExit): bool
    {
        if ($event === self::EVENT_ENTRY) {
            if (! $this->flag('entry_parameter', 'SC-00024')) {
                return false;
            }

            if ($entryStatus === AcaSchoolGateAttendance::ENTRY_TARDANZA && ! $this->flag('late_parameter', 'SC-00026')) {
                return false;
            }

            return true;
        }

        if ($event === self::EVENT_EXIT) {
            if (! $this->flag('exit_parameter', 'SC-00025')) {
                return false;
            }

            if ($earlyExit && ! $this->flag('early_exit_parameter', 'SC-00027')) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Destinatarios del aviso: los apoderados activos del alumno con teléfono
     * utilizable y, si ninguno tiene, el contacto de la matrícula.
     *
     * @return array<int, array{key: string, person_id: int|null, name: string, phone: string}>
     */
    public function recipients(AcaSchoolGateAttendance $gate): array
    {
        $recipients = [];
        $primaryOnly = $this->primaryOnly();

        $guardians = AcaSchoolStudentGuardian::query()
            ->where('student_id', $gate->student_id)
            ->where('status', true)
            ->with('person')
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get();

        $withPhone = [];

        foreach ($guardians as $guardian) {
            $phone = $this->normalizedPhone($guardian->person?->telephone);

            if ($phone === null) {
                continue;
            }

            $withPhone[] = [
                'key' => 'g' . $guardian->id,
                'person_id' => (int) $guardian->guardian_person_id,
                'name' => trim((string) ($guardian->person?->full_name ?? '')) ?: 'apoderado',
                'phone' => $phone,
                'is_primary' => (bool) $guardian->is_primary,
            ];
        }

        if ($withPhone !== []) {
            // "Solo al principal": si nadie está marcado como principal se usa el
            // primero con teléfono, para no quedarse sin avisar a nadie.
            $selected = $primaryOnly
                ? array_values(array_filter($withPhone, fn (array $item) => $item['is_primary']))
                : $withPhone;

            if ($selected === []) {
                $selected = [$withPhone[0]];
            }

            foreach ($selected as $item) {
                unset($item['is_primary']);
                $recipients[] = $item;
            }

            return $recipients;
        }

        // Sin apoderados con teléfono: se usa el contacto de la matrícula.
        $enrollment = AcaSchoolEnrollment::with('guardian')->find($gate->enrollment_id);
        $phone = $this->normalizedPhone($enrollment?->guardian_phone)
            ?? $this->normalizedPhone($enrollment?->guardian?->telephone);

        if ($enrollment && $phone !== null) {
            $recipients[] = [
                'key' => 'e' . $enrollment->id,
                'person_id' => $enrollment->guardian_person_id !== null ? (int) $enrollment->guardian_person_id : null,
                'name' => trim((string) ($enrollment->guardian?->full_name ?? '')) ?: 'apoderado',
                'phone' => $phone,
            ];
        }

        return $recipients;
    }

    /**
     * Encola el aviso del evento a cada destinatario.
     *
     * Devuelve cuántos avisos se encolaron (0 si el canal está apagado, sin
     * credenciales, el evento está desactivado o el alumno alcanzó el límite
     * diario). Es idempotente: repetir el mismo evento y destinatario no crea
     * otra entrega. Cada envío se separa al menos el intervalo anti-SPAM
     * (SC-00032) del anterior para que los operadores no lo marquen como SPAM.
     */
    public function notify(AcaSchoolGateAttendance $gate, string $event): int
    {
        $smsQueued = $this->notifySms($gate, $event);
        $telegramQueued = $this->telegramNotices->notify($gate, $event);

        return $smsQueued + $telegramQueued;
    }

    /**
     * Encola los avisos del evento por el canal SMSGate.
     */
    private function notifySms(AcaSchoolGateAttendance $gate, string $event): int
    {
        if (! $this->isConfigured() || ! $this->isChannelActive()) {
            return 0;
        }

        if (! $this->isEventEnabled($event, $gate->entry_status, (bool) $gate->early_exit)) {
            return 0;
        }

        $recipients = $this->recipients($gate);

        if ($recipients === []) {
            return 0;
        }

        $limit = $this->dailyLimit();
        $intervalMs = $this->intervalMs();
        $queued = 0;

        foreach ($recipients as $recipient) {
            if ($limit > 0 && $this->deliveriesToday((int) $gate->student_id) >= $limit) {
                break;
            }

            try {
                $delivery = AcaSchoolNoticeDelivery::firstOrCreate(
                    [
                        'gate_attendance_id' => $gate->id,
                        'event' => $event,
                        'recipient_key' => $recipient['key'],
                    ],
                    [
                        'student_id' => $gate->student_id,
                        'guardian_person_id' => $recipient['person_id'],
                        'phone' => $recipient['phone'],
                        'status' => 'pending',
                    ]
                );
            } catch (\Throwable $exception) {
                // Otra portería encoló la misma entrega en paralelo: no es un error.
                Log::warning('No se pudo registrar la entrega del aviso de asistencia', [
                    'gate_attendance_id' => $gate->id,
                    'event' => $event,
                    'recipient_key' => $recipient['key'],
                    'error' => $exception->getMessage(),
                ]);

                continue;
            }

            if (! $delivery->wasRecentlyCreated) {
                continue;
            }

            $pending = SendSchoolGateNotice::dispatch($delivery->id);

            if ($intervalMs > 0 && $queued > 0) {
                $pending->delay(now()->addMilliseconds($queued * $intervalMs));
            }

            $queued++;
        }

        return $queued;
    }

    /**
     * Variables del mensaje con los datos reales de la asistencia.
     *
     * @return array<string, string>
     */
    public function noticeVariables(AcaSchoolGateAttendance $gate, string $event, ?string $guardianName): array
    {
        $at = $event === self::EVENT_EXIT ? $gate->exit_at : $gate->entry_at;
        $at = $at instanceof Carbon ? $at : ($gate->exit_at ?? $gate->entry_at ?? now());

        return [
            'alumno' => $this->studentName($gate) ?: 'el alumno',
            'apoderado' => trim((string) $guardianName) ?: 'apoderado',
            'colegio' => $this->schoolName($gate),
            'seccion' => $this->sectionLabel($gate),
            'fecha' => $at->format('d/m/Y'),
            'hora' => $at->format('H:i'),
            'estado' => $this->eventStatusLabel($gate, $event),
        ];
    }

    /**
     * Texto final (plano) del aviso, listo para el SMS.
     *
     * @param array<string, string> $values
     */
    public function renderNoticeText(string $event, array $values): string
    {
        return GateNoticeMessage::render($this->messageTemplate($event), $values);
    }

    /** Texto de ejemplo para la vista previa y la prueba. */
    public function renderSampleText(string $event, ?string $template = null): string
    {
        $template = $template !== null && trim($template) !== ''
            ? $template
            : $this->messageTemplate($event);

        return GateNoticeMessage::render($template, GateNoticeMessage::sampleValues());
    }

    /**
     * Envía un texto ya armado por SMSGate (prueba desde la pantalla).
     *
     * @throws \Modules\Integrationhub\Exceptions\SmsgateRejectedException cuando el teléfono no sirve.
     * @throws \RuntimeException cuando falta configuración o el fallo es transitorio.
     */
    public function sendTo(string $phone, string $text): void
    {
        $this->smsgate->send($phone, $text);
    }

    /**
     * Guarda el valor de un parámetro del canal (por su clave de configuración).
     */
    public function saveParameter(string $key, string $defaultCode, ?string $value): void
    {
        $code = $this->parameterCode($key, $defaultCode);

        if ($code === '') {
            return;
        }

        $parameter = Parameter::firstOrCreate(
            ['parameter_code' => $code],
            [
                'description' => 'Avisos de asistencia (Colegio): ' . $key,
                'control_type' => 'tx',
                'json_query_data' => null,
                'value_default' => $value,
            ]
        );

        if (! $parameter->wasRecentlyCreated) {
            $parameter->update(['value_default' => $value]);
        }
    }

    /**
     * Últimas entregas de avisos, con su estado y motivo.
     *
     * @return array<int, array<string, mixed>>
     */
    public function recentDeliveries(int $limit = 50): array
    {
        $query = AcaSchoolNoticeDelivery::query()
            ->with(['student.person', 'guardian'])
            ->orderByDesc('id')
            ->limit($limit);

        if (\Illuminate\Support\Facades\Schema::hasColumn('aca_school_notice_deliveries', 'channel')) {
            $query->where('channel', AcaSchoolNoticeDelivery::CHANNEL_SMS);
        }

        return $query->get()
            ->map(fn (AcaSchoolNoticeDelivery $delivery) => [
                'id' => $delivery->id,
                'student' => $delivery->student?->person?->full_name,
                'guardian' => $delivery->guardian?->full_name,
                'event' => $delivery->event,
                'event_label' => $delivery->eventLabel(),
                'phone' => PhoneNumberFormatter::toDisplay($delivery->phone),
                'status' => $delivery->status,
                'error_message' => $delivery->error_message,
                'sent_at' => $delivery->sent_at?->format('d/m/Y H:i'),
                'created_at' => $delivery->created_at?->format('d/m/Y H:i'),
            ])
            ->values()
            ->all();
    }

    /**
     * Variables disponibles del mensaje (para la pantalla).
     *
     * @return array<int, array{code: string, label: string, example: string}>
     */
    public function variables(): array
    {
        return GateNoticeMessage::variables();
    }

    /** Avisos ya encolados hoy para el alumno (límite diario). */
    private function deliveriesToday(int $studentId): int
    {
        $query = AcaSchoolNoticeDelivery::query()
            ->where('student_id', $studentId)
            ->whereDate('created_at', now()->toDateString());

        if (\Illuminate\Support\Facades\Schema::hasColumn('aca_school_notice_deliveries', 'channel')) {
            $query->where('channel', AcaSchoolNoticeDelivery::CHANNEL_SMS);
        }

        return $query->count();
    }

    /** Nombre completo del alumno de la asistencia. */
    private function studentName(AcaSchoolGateAttendance $gate): string
    {
        if ($gate->relationLoaded('student')) {
            return trim((string) ($gate->student?->person?->full_name ?? ''));
        }

        return trim((string) (AcaSchoolStudent::with('person')->find($gate->student_id)?->person?->full_name ?? ''));
    }

    /** Nombre del colegio de la asistencia. */
    private function schoolName(AcaSchoolGateAttendance $gate): string
    {
        return trim((string) ($gate->school?->name ?? ''));
    }

    /** Grado y sección del alumno (ej: "Primaria 3° A"). */
    private function sectionLabel(AcaSchoolGateAttendance $gate): string
    {
        $section = $gate->section;

        if (! $section) {
            return '';
        }

        return trim(($section->grade?->name ?? '') . ' ' . $section->name);
    }

    /** Estado legible del evento (Asistencia, Tardanza, Salida, Salida anticipada). */
    private function eventStatusLabel(AcaSchoolGateAttendance $gate, string $event): string
    {
        if ($event === self::EVENT_EXIT) {
            return $gate->early_exit ? 'Salida anticipada' : 'Salida';
        }

        return $gate->entryStatusLabel();
    }

    /** Teléfono normalizado a E.164 sin «+», o null si no es utilizable. */
    private function normalizedPhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        return PhoneNumberFormatter::toE164($phone, null, $this->countryCode());
    }

    /** Código de país que se antepone a los números guardados sin él. */
    private function countryCode(): string
    {
        $code = preg_replace('/\D+/', '', (string) $this->config('country_code', '51')) ?? '';

        return $code === '' ? '51' : $code;
    }

    /** Código del parámetro asociado a una clave de configuración. */
    private function parameterCode(string $key, string $default): string
    {
        return trim((string) $this->config($key, $default));
    }

    /** Valor de un parámetro del sistema (null si no existe o está vacío). */
    private function parameterValue(string $key, string $default): ?string
    {
        $code = $this->parameterCode($key, $default);

        if ($code === '') {
            return null;
        }

        $value = trim((string) Parameter::where('parameter_code', $code)->value('value_default'));

        return $value === '' ? null : $value;
    }

    /** Valor de un interruptor (chx) del canal. */
    private function flag(string $key, string $default): bool
    {
        $value = strtolower(trim((string) $this->parameterValue($key, $default)));

        return in_array($value, ['1', 'true'], true);
    }

    /** Valor de configuración del canal (con el prefijo del módulo escolar). */
    private function config(string $key, mixed $default = null): mixed
    {
        return config('academic.school_notices.smsgate.' . $key, $default);
    }
}
