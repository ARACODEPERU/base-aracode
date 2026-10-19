<?php

namespace Modules\Academic\Services;

use App\Models\Person;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Entities\AcaSchool;
use Modules\Academic\Entities\AcaSchoolEnrollment;
use Modules\Academic\Entities\AcaSchoolJourney;
use Modules\Academic\Entities\AcaSchoolSection;
use Modules\Academic\Entities\AcaSchoolStudent;
use Modules\Academic\Entities\AcaSchoolStudentGuardian;
use Modules\Academic\Entities\AcaSchoolTelegramSession;
use Modules\Academic\Entities\AcaSchoolTelegramSubscription;
use Modules\Academic\Entities\AcaSchoolYear;
use RuntimeException;

/**
 * Registro conversacional y padrón del bot de Telegram para asistencia escolar.
 *
 * Flujo de registro:
 *   1. El padre, madre o apoderado elige /registrar (o /start): se abre una
 *      sesión en el paso STEP_GUARDIAN_DNI pidiendo su DNI.
 *   2. Se valida que el DNI pertenezca a un padre, madre o apoderado del
 *      colegio (en aca_school_student_guardians o en aca_school_enrollments) y
 *      se avanza al paso STEP_STUDENT_DNI pidiendo el DNI de su hijo estudiante.
 *   3. Se valida que el DNI del estudiante corresponda a un hijo suyo en el
 *      colegio, se registra la suscripción en aca_school_telegram_subscriptions
 *      y se avanza al paso STEP_ANOTHER_CHILD preguntando si tiene otro hijo
 *      en el colegio o escuela.
 *   4. Si responde "Sí", vuelve al paso STEP_STUDENT_DNI para pedir el DNI del
 *      siguiente hijo y, tras registrarlo, vuelve a preguntar si hay otro hijo.
 *      Si responde "No", se cierra la sesión mostrando el resumen de hijos
 *      vinculados.
 */
class SchoolTelegramRegistrationService
{
    /** Paso 1: esperando el DNI del padre, madre o apoderado. */
    public const STEP_GUARDIAN_DNI = 'awaiting_guardian_dni';

    /** Paso 2: esperando el DNI del hijo estudiante del colegio. */
    public const STEP_STUDENT_DNI = 'awaiting_student_dni';

    /** Paso 3: esperando confirmación (Sí / No) de si tiene otro hijo por registrar. */
    public const STEP_ANOTHER_CHILD = 'awaiting_another_child';

    /**
     * Abre o reinicia la conversación de registro pidiendo el DNI del apoderado.
     */
    public function beginRegistration(string $chatId, ?string $username = null, ?string $firstName = null): AcaSchoolTelegramSession
    {
        return $this->openStep($chatId, self::STEP_GUARDIAN_DNI, [
            'registered_students' => [],
        ], $username, $firstName);
    }

    /**
     * Abre una conversación en el paso indicado con su contexto.
     *
     * @param array<string, mixed> $context
     */
    public function openStep(
        string $chatId,
        string $step,
        array $context = [],
        ?string $username = null,
        ?string $firstName = null
    ): AcaSchoolTelegramSession {
        $chatId = trim($chatId);

        if ($chatId === '') {
            throw new RuntimeException('No se puede iniciar el registro sin el chat_id de Telegram.');
        }

        $session = AcaSchoolTelegramSession::firstOrNew(['chat_id' => $chatId]);

        $session->fill([
            'telegram_username' => $this->clean($username),
            'telegram_first_name' => $this->clean($firstName),
            'step' => $step,
            'context' => $context,
            'attempts' => 0,
            'expires_at' => now()->addMinutes($this->sessionMinutes()),
        ])->save();

        return $session->refresh();
    }

    /**
     * Avanza la sesión al siguiente paso reiniciando el contador de intentos.
     *
     * @param array<string, mixed> $context
     */
    public function advanceStep(
        AcaSchoolTelegramSession $session,
        string $step,
        array $context = []
    ): AcaSchoolTelegramSession {
        $session->fill([
            'step' => $step,
            'context' => $context,
            'attempts' => 0,
            'expires_at' => now()->addMinutes($this->sessionMinutes()),
        ])->save();

        return $session->refresh();
    }

    /**
     * Sesión vigente de un chat, o null si no existe o ya venció.
     */
    public function sessionFor(string $chatId): ?AcaSchoolTelegramSession
    {
        $session = AcaSchoolTelegramSession::where('chat_id', trim($chatId))->first();

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
     * Contexto de la sesión en curso.
     *
     * @return array<string, mixed>
     */
    public function context(AcaSchoolTelegramSession $session): array
    {
        $context = $session->context;

        return is_array($context) ? $context : [];
    }

    /**
     * Incrementa el número de intentos fallidos en el paso actual.
     */
    public function countAttempt(AcaSchoolTelegramSession $session): int
    {
        $session->increment('attempts');

        return (int) $session->refresh()->attempts;
    }

    /**
     * Máximo de intentos fallidos permitidos antes de cerrar la sesión.
     */
    public function maxAttempts(): int
    {
        return max(1, (int) config('academic.school_notices.telegram.max_attempts', 5));
    }

    /**
     * Cierra la sesión conversacional del chat.
     */
    public function closeSession(string $chatId): void
    {
        AcaSchoolTelegramSession::where('chat_id', trim($chatId))->delete();
    }

    /**
     * Actualiza la fecha de última actividad de las suscripciones de ese chat.
     */
    public function touch(string $chatId): void
    {
        AcaSchoolTelegramSubscription::where('chat_id', trim($chatId))
            ->update(['last_seen_at' => now()]);
    }

    /**
     * Busca y valida al padre, madre o apoderado por su número de documento (DNI).
     *
     * Devuelve la ficha del apoderado solo si la persona existe y figura como
     * apoderado activo de algún estudiante del colegio (en aca_school_student_guardians
     * o como apoderado en una matrícula de aca_school_enrollments).
     *
     * @return array{person_id: int, name: string, document: string}|null
     */
    public function resolveGuardianByDocument(string $document): ?array
    {
        $person = $this->findPersonByDocument($document);

        if ($person === null) {
            return null;
        }

        $isGuardian = AcaSchoolStudentGuardian::query()
            ->where('guardian_person_id', $person->id)
            ->where('status', true)
            ->exists();

        if (! $isGuardian) {
            $isGuardian = AcaSchoolEnrollment::query()
                ->where('guardian_person_id', $person->id)
                ->where('status', AcaSchoolEnrollment::STATUS_ACTIVO)
                ->exists();
        }

        if (! $isGuardian) {
            return null;
        }

        return [
            'person_id' => (int) $person->id,
            'name' => $this->personName($person),
            'document' => (string) $person->number,
        ];
    }

    /**
     * Valida el DNI del hijo estudiante y verifica que esté vinculado al
     * padre, madre o apoderado indicado.
     *
     * @return array{
     *     status: 'ok'|'not_found'|'not_linked',
     *     student_id?: int,
     *     school_id?: int|null,
     *     name?: string,
     *     document?: string,
     *     section?: string
     * }
     */
    public function resolveStudentForGuardian(int $guardianPersonId, string $studentDocument): array
    {
        $studentPerson = $this->findPersonByDocument($studentDocument);

        if ($studentPerson === null) {
            return ['status' => 'not_found'];
        }

        $students = AcaSchoolStudent::query()
            ->where('person_id', $studentPerson->id)
            ->where('status', true)
            ->orderByDesc('id')
            ->get();

        if ($students->isEmpty()) {
            return ['status' => 'not_found'];
        }

        foreach ($students as $student) {
            $linked = AcaSchoolStudentGuardian::query()
                ->where('student_id', $student->id)
                ->where('guardian_person_id', $guardianPersonId)
                ->where('status', true)
                ->exists();

            if (! $linked) {
                $linked = AcaSchoolEnrollment::query()
                    ->where('student_id', $student->id)
                    ->where('guardian_person_id', $guardianPersonId)
                    ->where('status', AcaSchoolEnrollment::STATUS_ACTIVO)
                    ->exists();
            }

            if ($linked) {
                return [
                    'status' => 'ok',
                    'student_id' => (int) $student->id,
                    'school_id' => $student->school_id ? (int) $student->school_id : null,
                    'name' => $this->personName($studentPerson),
                    'document' => (string) $studentPerson->number,
                    'section' => $this->studentSectionLabel((int) $student->id),
                ];
            }
        }

        return [
            'status' => 'not_linked',
            'name' => $this->personName($studentPerson),
        ];
    }

    /**
     * Registra (o reactiva) la suscripción del chat para recibir los avisos de
     * entrada y salida del estudiante indicado.
     */
    public function subscribe(
        int $guardianPersonId,
        int $studentId,
        ?int $schoolId,
        string $chatId,
        ?string $username = null,
        ?string $firstName = null
    ): AcaSchoolTelegramSubscription {
        $chatId = trim($chatId);

        if ($guardianPersonId <= 0 || $studentId <= 0 || $chatId === '') {
            throw new RuntimeException('Datos incompletos para registrar la suscripción de Telegram.');
        }

        return DB::transaction(function () use ($guardianPersonId, $studentId, $schoolId, $chatId, $username, $firstName) {
            $subscription = AcaSchoolTelegramSubscription::firstOrNew([
                'student_id' => $studentId,
                'chat_id' => $chatId,
            ]);

            $subscription->fill([
                'school_id' => $schoolId,
                'guardian_person_id' => $guardianPersonId,
                'telegram_username' => $this->clean($username),
                'telegram_first_name' => $this->clean($firstName),
                'status' => AcaSchoolTelegramSubscription::STATUS_ACTIVE,
                'registered_at' => $subscription->registered_at ?? now(),
                'last_seen_at' => now(),
            ])->save();

            return $subscription->refresh();
        });
    }

    /**
     * Da de baja todas las suscripciones activas del chat para que deje de
     * recibir mensajes.
     *
     * @return int Cantidad de suscripciones dadas de baja
     */
    public function unsubscribeAll(string $chatId): int
    {
        $chatId = trim($chatId);

        if ($chatId === '') {
            return 0;
        }

        return AcaSchoolTelegramSubscription::query()
            ->where('chat_id', $chatId)
            ->where('status', AcaSchoolTelegramSubscription::STATUS_ACTIVE)
            ->update([
                'status' => AcaSchoolTelegramSubscription::STATUS_INACTIVE,
                'last_seen_at' => now(),
            ]);
    }

    /**
     * Lista los hijos actualmente suscritos a este chat.
     *
     * @return array<int, array{student_id: int, name: string, section: string}>
     */
    public function activeSubscriptionsForChat(string $chatId): array
    {
        return AcaSchoolTelegramSubscription::query()
            ->with('student.person')
            ->where('chat_id', trim($chatId))
            ->where('status', AcaSchoolTelegramSubscription::STATUS_ACTIVE)
            ->orderBy('id')
            ->get()
            ->map(fn (AcaSchoolTelegramSubscription $sub) => [
                'student_id' => (int) $sub->student_id,
                'name' => trim((string) ($sub->student?->person?->full_name ?? '')) ?: ('Estudiante #' . $sub->student_id),
                'section' => $this->studentSectionLabel((int) $sub->student_id),
            ])
            ->values()
            ->all();
    }

    /**
     * Información general del colegio para responder al comando /info.
     *
     * @return array{
     *     name: string,
     *     modular_code: string,
     *     type: string,
     *     address: string,
     *     phone: string,
     *     email: string,
     *     year: string,
     *     journeys: array<int, string>
     * }
     */
    public function schoolInfo(): array
    {
        try {
            $school = \Illuminate\Support\Facades\Schema::hasColumn('aca_schools', 'is_default')
                ? AcaSchool::defaultSchool()
                : AcaSchool::orderBy('id')->first();
        } catch (\Throwable) {
            $school = null;
        }

        if (! $school) {
            return [
                'name' => (string) config('app.name', 'Institución Educativa'),
                'modular_code' => '',
                'type' => '',
                'address' => '',
                'phone' => '',
                'email' => '',
                'year' => '',
                'journeys' => [],
            ];
        }

        $activeYear = null;

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('aca_school_years')) {
                $activeYear = AcaSchoolYear::where('school_id', $school->id)
                    ->where('status', AcaSchoolYear::STATUS_ACTIVE)
                    ->orderByDesc('year')
                    ->first()
                    ?? AcaSchoolYear::where('school_id', $school->id)->orderByDesc('year')->first();
            }
        } catch (\Throwable) {
            $activeYear = null;
        }

        $journeys = [];

        try {
            $journeyRows = AcaSchoolJourney::query()
                ->with('level')
                ->where('school_id', $school->id)
                ->where('status', true)
                ->orderBy('level_id')
                ->get();

            $shiftLabels = AcaSchoolSection::shiftLabels();

            foreach ($journeyRows as $journey) {
                $levelName = trim((string) ($journey->level?->name ?? 'Nivel'));
                $shiftLabel = $shiftLabels[$journey->shift] ?? ucfirst((string) $journey->shift);
                $entry = substr((string) $journey->entry_time, 0, 5);
                $exit = substr((string) $journey->exit_time, 0, 5);

                if ($entry !== '' || $exit !== '') {
                    $journeys[] = "{$levelName} ({$shiftLabel}): Entrada {$entry}" . ($exit !== '' ? " · Salida {$exit}" : '');
                }
            }
        } catch (\Throwable) {
            $journeys = [];
        }

        $typeLabels = AcaSchool::typeLabels();

        return [
            'name' => trim((string) $school->name) ?: 'Institución Educativa',
            'modular_code' => trim((string) ($school->modular_code ?? '')),
            'type' => $typeLabels[$school->type] ?? ucfirst((string) ($school->type ?? '')),
            'address' => trim((string) ($school->address ?? '')),
            'phone' => trim((string) ($school->phone ?? '')),
            'email' => trim((string) ($school->email ?? '')),
            'year' => $activeYear ? (string) $activeYear->year : '',
            'journeys' => $journeys,
        ];
    }

    private function findPersonByDocument(string $document): ?Person
    {
        $document = trim($document);

        if ($document === '') {
            return null;
        }

        return Person::query()
            ->where('number', $document)
            ->first(['id', 'short_name', 'full_name', 'number'])
            ?? Person::query()
                ->where('number', mb_strtolower($document))
                ->first(['id', 'short_name', 'full_name', 'number']);
    }

    private function studentSectionLabel(int $studentId): string
    {
        $enrollment = AcaSchoolEnrollment::query()
            ->with('section.grade.level')
            ->where('student_id', $studentId)
            ->where('status', AcaSchoolEnrollment::STATUS_ACTIVO)
            ->orderByDesc('id')
            ->first();

        if (! $enrollment || ! $enrollment->section) {
            return '';
        }

        $level = trim((string) ($enrollment->section->grade?->level?->name ?? ''));
        $grade = trim((string) ($enrollment->section->grade?->name ?? ''));
        $section = trim((string) ($enrollment->section->name ?? ''));

        return trim(implode(' ', array_filter([$level, $grade, $section])));
    }

    private function personName(Person $person): string
    {
        $name = trim((string) ($person->full_name ?? ''));

        if ($name === '') {
            $name = trim((string) ($person->short_name ?? ''));
        }

        return $name !== '' ? $name : 'Persona #' . $person->id;
    }

    private function sessionMinutes(): int
    {
        return max(1, (int) config('academic.school_notices.telegram.session_minutes', 30));
    }

    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, 150);
    }
}
