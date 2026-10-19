<?php

namespace Modules\Academic\Services;

use App\Models\Parameter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Academic\Entities\AcaSchoolEnrollment;
use Modules\Academic\Entities\AcaSchoolGateAttendance;
use Modules\Academic\Entities\AcaSchoolNoticeDelivery;
use Modules\Academic\Entities\AcaSchoolStudent;
use Modules\Academic\Entities\AcaSchoolStudentGuardian;
use Modules\Academic\Entities\AcaSchoolTelegramSubscription;
use Modules\Academic\Jobs\SendSchoolGateNotice;
use Modules\Academic\Support\GateNoticeMessage;

/**
 * Avisos de asistencia del colegio al padre, madre o apoderado vía Telegram.
 *
 * Administra los parámetros propios del canal Telegram escolar (SC-00033 a
 * SC-00043), resuelve los chats suscritos de cada alumno, arma el mensaje con
 * su plantilla y encola el envío en SendSchoolGateNotice sin detener la portería.
 */
class SchoolTelegramNoticeService
{
    public const EVENT_ENTRY = AcaSchoolGateAttendance::EVENT_ENTRADA;
    public const EVENT_EXIT = AcaSchoolGateAttendance::EVENT_SALIDA;

    private SchoolTelegramBotService $bot;

    public function __construct(?SchoolTelegramBotService $bot = null)
    {
        $this->bot = $bot ?? app(SchoolTelegramBotService::class);
    }

    public function bot(): SchoolTelegramBotService
    {
        return $this->bot;
    }

    /**
     * Códigos de los parámetros que consume el canal Telegram escolar.
     *
     * @return array<string, string>
     */
    public function parameterCodes(): array
    {
        return [
            'token' => $this->parameterCode('token_parameter', 'SC-00033'),
            'enabled' => $this->parameterCode('enabled_parameter', 'SC-00034'),
            'entry' => $this->parameterCode('entry_parameter', 'SC-00035'),
            'exit' => $this->parameterCode('exit_parameter', 'SC-00036'),
            'late' => $this->parameterCode('late_parameter', 'SC-00037'),
            'early_exit' => $this->parameterCode('early_exit_parameter', 'SC-00038'),
            'entry_message' => $this->parameterCode('entry_message_parameter', 'SC-00039'),
            'exit_message' => $this->parameterCode('exit_message_parameter', 'SC-00040'),
            'primary_only' => $this->parameterCode('primary_only_parameter', 'SC-00041'),
            'daily_limit' => $this->parameterCode('daily_limit_parameter', 'SC-00042'),
            'interval_ms' => $this->parameterCode('interval_ms_parameter', 'SC-00043'),
        ];
    }

    /** true solo si el token del bot de Telegram está configurado. */
    public function isConfigured(): bool
    {
        return $this->bot->isConfigured();
    }

    /** true solo si el interruptor general (SC-00034) está encendido. */
    public function isChannelActive(): bool
    {
        return $this->flag('enabled_parameter', 'SC-00034');
    }

    /**
     * Estado del canal y de cada interruptor para la pantalla de configuración.
     *
     * @return array<string, bool>
     */
    public function channelStatus(): array
    {
        return [
            'token' => $this->parameterValue('token_parameter', 'SC-00033') !== null,
            'configured' => $this->isConfigured(),
            'active' => $this->isChannelActive(),
            'entry' => $this->flag('entry_parameter', 'SC-00035'),
            'exit' => $this->flag('exit_parameter', 'SC-00036'),
            'late' => $this->flag('late_parameter', 'SC-00037'),
            'early_exit' => $this->flag('early_exit_parameter', 'SC-00038'),
            'primary_only' => $this->flag('primary_only_parameter', 'SC-00041'),
        ];
    }

    /**
     * Valores editables del canal para rellenar el formulario.
     *
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        return [
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
            $event === self::EVENT_EXIT ? 'SC-00040' : 'SC-00039'
        );

        if ($value !== null) {
            return $value;
        }

        return (string) $this->config(
            $event === self::EVENT_EXIT ? 'default_exit_message' : 'default_entry_message',
            ''
        );
    }

    /** Máximo de avisos de Telegram por alumno y día (0 = sin límite). */
    public function dailyLimit(): int
    {
        $value = (int) ($this->parameterValue('daily_limit_parameter', 'SC-00042') ?? 0);

        return max(0, min(999, $value));
    }

    /** Pausa mínima entre cada mensaje de Telegram, en milisegundos. */
    public function intervalMs(): int
    {
        $raw = $this->parameterValue('interval_ms_parameter', 'SC-00043');

        if ($raw === null) {
            return max(0, min(60000, (int) $this->config('interval_ms', 500)));
        }

        if (! is_numeric(trim($raw))) {
            return 500;
        }

        return max(0, min(60000, (int) $raw));
    }

    /** true si los avisos se envían solo al apoderado marcado como principal. */
    public function primaryOnly(): bool
    {
        return $this->flag('primary_only_parameter', 'SC-00041');
    }

    /**
     * true si el evento debe generar aviso en Telegram según los interruptores.
     */
    public function isEventEnabled(string $event, ?string $entryStatus, bool $earlyExit): bool
    {
        if ($event === self::EVENT_ENTRY) {
            if (! $this->flag('entry_parameter', 'SC-00035')) {
                return false;
            }

            if ($entryStatus === AcaSchoolGateAttendance::ENTRY_TARDANZA && ! $this->flag('late_parameter', 'SC-00037')) {
                return false;
            }

            return true;
        }

        if ($event === self::EVENT_EXIT) {
            if (! $this->flag('exit_parameter', 'SC-00036')) {
                return false;
            }

            if ($earlyExit && ! $this->flag('early_exit_parameter', 'SC-00038')) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Destinatarios de Telegram del alumno: suscripciones activas en
     * aca_school_telegram_subscriptions cuyos apoderados siguen vinculados al alumno.
     *
     * @return array<int, array{key: string, person_id: int|null, name: string, chat_id: string}>
     */
    public function recipients(AcaSchoolGateAttendance $gate): array
    {
        if (! Schema::hasTable('aca_school_telegram_subscriptions')) {
            return [];
        }

        $subscriptions = AcaSchoolTelegramSubscription::query()
            ->with('guardian')
            ->where('student_id', $gate->student_id)
            ->where('status', AcaSchoolTelegramSubscription::STATUS_ACTIVE)
            ->orderBy('id')
            ->get();

        if ($subscriptions->isEmpty()) {
            return [];
        }

        $guardians = AcaSchoolStudentGuardian::query()
            ->where('student_id', $gate->student_id)
            ->where('status', true)
            ->get()
            ->keyBy('guardian_person_id');

        $enrollment = AcaSchoolEnrollment::find($gate->enrollment_id);
        $primaryOnly = $this->primaryOnly();

        $candidates = [];

        foreach ($subscriptions as $sub) {
            $chatId = trim((string) $sub->chat_id);

            if ($chatId === '') {
                continue;
            }

            $guardianLink = $guardians->get($sub->guardian_person_id);
            $isEnrollmentGuardian = $enrollment && (int) $enrollment->guardian_person_id === (int) $sub->guardian_person_id;

            // Solo recibe si sigue siendo apoderado activo del alumno o apoderado de su matrícula.
            if (! $guardianLink && ! $isEnrollmentGuardian) {
                continue;
            }

            $isPrimary = $guardianLink ? (bool) $guardianLink->is_primary : $isEnrollmentGuardian;

            $candidates[] = [
                'key' => 'tg' . $sub->id,
                'person_id' => (int) $sub->guardian_person_id,
                'name' => trim((string) ($sub->guardian?->full_name ?? $sub->telegram_first_name ?? '')) ?: 'apoderado',
                'chat_id' => $chatId,
                'is_primary' => $isPrimary,
            ];
        }

        if ($candidates === []) {
            return [];
        }

        $selected = $primaryOnly
            ? array_values(array_filter($candidates, fn (array $item) => $item['is_primary']))
            : $candidates;

        if ($selected === []) {
            $selected = [$candidates[0]];
        }

        $recipients = [];

        foreach ($selected as $item) {
            unset($item['is_primary']);
            $recipients[] = $item;
        }

        return $recipients;
    }

    /**
     * Encola el aviso de Telegram del evento a cada chat suscrito del alumno.
     */
    public function notify(AcaSchoolGateAttendance $gate, string $event): int
    {
        if (! $this->isChannelActive() || ! $this->isConfigured()) {
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
                        'channel' => AcaSchoolNoticeDelivery::CHANNEL_TELEGRAM,
                        'phone' => '',
                        'chat_id' => $recipient['chat_id'],
                        'status' => 'pending',
                    ]
                );
            } catch (\Throwable $exception) {
                Log::warning('No se pudo registrar la entrega del aviso de asistencia por Telegram', [
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
            'colegio' => trim((string) ($gate->school?->name ?? '')),
            'seccion' => $this->sectionLabel($gate),
            'fecha' => $at->format('d/m/Y'),
            'hora' => $at->format('H:i'),
            'estado' => $event === self::EVENT_EXIT
                ? ($gate->early_exit ? 'Salida anticipada' : 'Salida')
                : $gate->entryStatusLabel(),
        ];
    }

    /**
     * Texto final del aviso de Telegram con las variables reemplazadas.
     *
     * @param array<string, string> $values
     */
    public function renderNoticeText(string $event, array $values): string
    {
        return GateNoticeMessage::render($this->messageTemplate($event), $values);
    }

    /** Texto de ejemplo para la vista previa y el mensaje de prueba. */
    public function renderSampleText(string $event, ?string $template = null): string
    {
        $template = $template !== null && trim($template) !== ''
            ? $template
            : $this->messageTemplate($event);

        return GateNoticeMessage::render($template, GateNoticeMessage::sampleValues());
    }

    /**
     * Envía el mensaje a un chat_id usando el bot escolar.
     */
    public function sendTo(string $chatId, string $text): void
    {
        $this->bot->sendFormatted($chatId, $text, true);
    }

    /**
     * Guarda el valor de un parámetro del canal Telegram escolar.
     */
    public function saveParameter(string $key, string $defaultCode, ?string $value, string $controlType = 'tx'): void
    {
        $code = $this->parameterCode($key, $defaultCode);

        if ($code === '') {
            return;
        }

        $parameter = Parameter::firstOrCreate(
            ['parameter_code' => $code],
            [
                'description' => 'Avisos de asistencia Telegram (Colegio): ' . $key,
                'control_type' => $controlType,
                'json_query_data' => null,
                'value_default' => $value,
            ]
        );

        if (! $parameter->wasRecentlyCreated) {
            $parameter->update(['value_default' => $value]);
        }

        if ($key === 'token_parameter') {
            $this->bot->flushToken();
        }
    }

    /**
     * Últimas entregas de avisos por Telegram, con su estado y motivo.
     *
     * @return array<int, array<string, mixed>>
     */
    public function recentDeliveries(int $limit = 50): array
    {
        if (! Schema::hasTable('aca_school_notice_deliveries')) {
            return [];
        }

        $query = AcaSchoolNoticeDelivery::query()
            ->with(['student.person', 'guardian'])
            ->orderByDesc('id')
            ->limit($limit);

        if (Schema::hasColumn('aca_school_notice_deliveries', 'channel')) {
            $query->where('channel', AcaSchoolNoticeDelivery::CHANNEL_TELEGRAM);
        }

        return $query->get()
            ->map(fn (AcaSchoolNoticeDelivery $delivery) => [
                'id' => $delivery->id,
                'student' => $delivery->student?->person?->full_name,
                'guardian' => $delivery->guardian?->full_name,
                'event' => $delivery->event,
                'event_label' => $delivery->eventLabel(),
                'chat_id' => $delivery->chat_id,
                'status' => $delivery->status,
                'error_message' => $delivery->error_message,
                'sent_at' => $delivery->sent_at?->format('d/m/Y H:i'),
                'created_at' => $delivery->created_at?->format('d/m/Y H:i'),
            ])
            ->values()
            ->all();
    }

    /**
     * Lista de suscripciones registradas con el bot escolar.
     *
     * @return array<int, array<string, mixed>>
     */
    public function recentSubscriptions(int $limit = 100): array
    {
        if (! Schema::hasTable('aca_school_telegram_subscriptions')) {
            return [];
        }

        return AcaSchoolTelegramSubscription::query()
            ->with(['student.person', 'guardian'])
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get()
            ->map(fn (AcaSchoolTelegramSubscription $sub) => [
                'id' => $sub->id,
                'student' => $sub->student?->person?->full_name,
                'student_document' => $sub->student?->person?->number,
                'guardian' => $sub->guardian?->full_name,
                'guardian_document' => $sub->guardian?->number,
                'chat_id' => $sub->chat_id,
                'telegram_username' => $sub->telegram_username,
                'status' => $sub->status,
                'registered_at' => $sub->registered_at?->format('d/m/Y H:i'),
                'last_seen_at' => $sub->last_seen_at?->format('d/m/Y H:i'),
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

    private function deliveriesToday(int $studentId): int
    {
        $query = AcaSchoolNoticeDelivery::query()
            ->where('student_id', $studentId)
            ->whereDate('created_at', now()->toDateString());

        if (Schema::hasColumn('aca_school_notice_deliveries', 'channel')) {
            $query->where('channel', AcaSchoolNoticeDelivery::CHANNEL_TELEGRAM);
        }

        return $query->count();
    }

    private function studentName(AcaSchoolGateAttendance $gate): string
    {
        if ($gate->relationLoaded('student')) {
            return trim((string) ($gate->student?->person?->full_name ?? ''));
        }

        return trim((string) (AcaSchoolStudent::with('person')->find($gate->student_id)?->person?->full_name ?? ''));
    }

    private function sectionLabel(AcaSchoolGateAttendance $gate): string
    {
        $section = $gate->section;

        if (! $section) {
            return '';
        }

        return trim(($section->grade?->name ?? '') . ' ' . $section->name);
    }

    private function parameterCode(string $key, string $default): string
    {
        return trim((string) $this->config($key, $default));
    }

    private function parameterValue(string $key, string $default): ?string
    {
        $code = $this->parameterCode($key, $default);

        if ($code === '') {
            return null;
        }

        $value = trim((string) Parameter::where('parameter_code', $code)->value('value_default'));

        return $value === '' ? null : $value;
    }

    private function flag(string $key, string $default): bool
    {
        $value = strtolower(trim((string) $this->parameterValue($key, $default)));

        return in_array($value, ['1', 'true'], true);
    }

    private function config(string $key, mixed $default = null): mixed
    {
        return config('academic.school_notices.telegram.' . $key, $default);
    }
}
