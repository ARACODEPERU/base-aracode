<?php

namespace Modules\Academic\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Academic\Entities\AcaCapRegistration;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaStudent;
use Modules\Academic\Entities\AcaStudentSubscription;
use Modules\Academic\Support\PhoneNumberFormatter;
use Modules\Integrationhub\Entities\IntegrationTelegramContact;

/**
 * Arma el publico de una campana de notificaciones de un programa de
 * especializacion:
 *
 *   - alumnos matriculados en ese programa, MAS
 *   - alumnos con suscripcion activa y vigente ese dia.
 *
 * Se deduplica por persona (gana la fila del programa) y por telefono ya
 * normalizado; quien no tiene un telefono utilizable queda fuera del envio.
 *
 * Telegram es la excepcion: no se envia por telefono sino por el chat_id que el
 * alumno registro con el bot, asi que ese canal tiene sus propios metodos
 * (resolveTelegram / countsTelegram).
 */
class NotificationAudienceResolver
{
    /**
     * Padron a notificar.
     *
     * @return Collection<int, array{student_id: int|null, person_id: int|null, name: string, phone: string, source: string}>
     */
    public function resolve(AcaCourse $course): Collection
    {
        return $this->normalize(
            $this->programRows($course)->concat($this->subscriptionRows())
        );
    }

    /**
     * Conteos para la previsualizacion previa al envio.
     *
     * @return array{program: int, subscriptions: int, total: int, skipped: int}
     */
    public function counts(AcaCourse $course): array
    {
        $program = $this->programRows($course);
        $subscriptions = $this->subscriptionRows();
        $recipients = $this->normalize($program->concat($subscriptions));

        $persons = $program->concat($subscriptions)
            ->pluck('person_id')
            ->filter()
            ->unique()
            ->count();

        return [
            'program' => $program->pluck('person_id')->filter()->unique()->count(),
            'subscriptions' => $subscriptions->pluck('person_id')->filter()->unique()->count(),
            'total' => $recipients->count(),
            // Sin telefono valido, con telefono repetido o personas que ya
            // venian en el otro grupo.
            'skipped' => max(0, $persons - $recipients->count()),
        ];
    }

    /**
     * Padron de una campana de Telegram.
     *
     * Son los mismos alumnos del programa mas los de suscripcion vigente, pero
     * en lugar del telefono se entrega el chat_id que registraron con el bot.
     * Quien no tiene chat activo queda fuera (por eso Telegram suele alcanzar a
     * menos personas que el SMS o el WhatsApp).
     *
     * @return Collection<int, array{student_id: int|null, person_id: int|null, name: string, phone: null, chat_id: string, source: string}>
     */
    public function resolveTelegram(AcaCourse $course): Collection
    {
        return $this->normalizeTelegram(
            $this->telegramProgramRows($course)->concat($this->telegramSubscriptionRows())
        );
    }

    /**
     * Conteos de Telegram para la previsualizacion previa al envio.
     *
     * "skipped" es la diferencia entre el padron del curso y quienes tienen
     * chat_id: esas personas quedan fuera del envio hasta que se registren.
     *
     * @return array{program: int, subscriptions: int, total: int, skipped: int}
     */
    public function countsTelegram(AcaCourse $course): array
    {
        $program = $this->telegramProgramRows($course);
        $subscriptions = $this->telegramSubscriptionRows();

        $recipients = $this->normalizeTelegram($program->concat($subscriptions));

        $programPersons = $program->pluck('person_id')->filter()->unique();
        $subscriptionPersons = $subscriptions->pluck('person_id')->filter()->unique();
        $allPersons = $programPersons->concat($subscriptionPersons)->unique();

        $subscribed = $this->subscribedChatsByPerson($allPersons->all());

        return [
            'program' => $programPersons->filter(fn ($id) => isset($subscribed[(int) $id]))->count(),
            'subscriptions' => $subscriptionPersons->filter(fn ($id) => isset($subscribed[(int) $id]))->count(),
            'total' => $recipients->count(),
            'skipped' => max(0, $allPersons->count() - $recipients->count()),
        ];
    }

    /**
     * Padron del modo prueba: los numeros que escribe el administrador, ya
     * completos con su codigo de pais.
     *
     * @param  array<int, string> $numbers
     * @return Collection<int, array{student_id: null, person_id: null, name: string, phone: string, source: string}>
     */
    public function resolveTestNumbers(array $numbers): Collection
    {
        $recipients = collect();

        foreach (array_values($numbers) as $index => $number) {
            $recipients->push([
                'student_id' => null,
                'person_id' => null,
                'name' => 'Número de prueba ' . ($index + 1),
                'phone' => $number,
                'source' => 'prueba',
            ]);
        }

        return $recipients;
    }

    /**
     * Alumnos matriculados en el programa (aca_cap_registrations activas).
     */
    private function programRows(AcaCourse $course): Collection
    {
        $studentIds = AcaCapRegistration::query()
            ->where('course_id', $course->id)
            ->where('status', true)
            ->pluck('student_id')
            ->unique()
            ->all();

        if ($studentIds === []) {
            return collect();
        }

        return $this->peopleRows($studentIds)
            ->map(fn ($row) => $this->withSource($row, 'programa'));
    }

    /**
     * Alumnos con suscripcion activa (status true) o vigente por fechas,
     * replicando la regla de App\Services\JobOffersAccess::hasActiveSubscription().
     */
    private function subscriptionRows(): Collection
    {
        $studentIds = $this->subscriptionStudentIds();

        if ($studentIds === []) {
            return collect();
        }

        return $this->peopleRows($studentIds)
            ->map(fn ($row) => $this->withSource($row, 'suscripcion'));
    }

    /**
     * Alumnos con suscripcion activa (status true) o vigente por fechas,
     * replicando la regla de App\Services\JobOffersAccess::hasActiveSubscription().
     *
     * @return array<int, int>
     */
    private function subscriptionStudentIds(): array
    {
        return AcaStudentSubscription::query()
            ->where($this->activeSubscriptionFilter())
            ->pluck('student_id')
            ->unique()
            ->all();
    }

    /**
     * Misma regla de suscripcion, para una sola persona.
     *
     * La usa el registro del bot de Telegram, que valida a un alumno a la vez.
     */
    public function studentHasActiveSubscription(int $studentId): bool
    {
        return AcaStudentSubscription::query()
            ->where('student_id', $studentId)
            ->where($this->activeSubscriptionFilter())
            ->exists();
    }

    /**
     * Condicion compartida de suscripcion vigente: activa por estado o por
     * fechas.
     */
    private function activeSubscriptionFilter(): \Closure
    {
        $today = Carbon::today();

        return function ($query) use ($today) {
            $query->where('status', true)
                ->orWhere(function ($q) use ($today) {
                    $q->whereDate('date_start', '<=', $today)
                        ->whereDate('date_end', '>=', $today);
                });
        };
    }

    /**
     * Datos de contacto de los alumnos indicados (solo quienes tienen telefono).
     *
     * Se trae el codigo telefonico de los dos paises posibles de la persona:
     * country_id (pais local) y foreign_country_id (pais del alumno
     * extranjero), para poder anteponer el correcto a su telefono.
     */
    private function peopleRows(array $studentIds): Collection
    {
        return AcaStudent::query()
            ->join('people', 'people.id', '=', 'aca_students.person_id')
            ->leftJoin('countries', 'countries.id', '=', 'people.country_id')
            ->leftJoin('countries as foreign_countries', 'foreign_countries.id', '=', 'people.foreign_country_id')
            ->whereIn('aca_students.id', $studentIds)
            ->whereNotNull('people.telephone')
            ->where('people.telephone', '<>', '')
            ->get([
                'aca_students.id as student_id',
                'aca_students.person_id',
                'people.short_name',
                'people.full_name',
                'people.telephone',
                'countries.country_code_phone',
                'foreign_countries.country_code_phone as foreign_country_code_phone',
            ]);
    }

    private function withSource(object $row, string $source): object
    {
        $row->source = $source;

        return $row;
    }

    /**
     * Deduplica y normaliza el padron completo.
     */
    private function normalize(Collection $rows): Collection
    {
        $seenPersons = [];
        $seenPhones = [];
        $recipients = collect();

        foreach ($rows as $row) {
            $personId = (int) $row->person_id;

            if ($personId !== 0 && isset($seenPersons[$personId])) {
                continue;
            }

            // El codigo de pais sale del pais del alumno (el de extranjeros
            // primero); el 51 es solo el ultimo recurso para quien no tiene
            // pais registrado.
            $countryCode = $row->foreign_country_code_phone
                ?: ($row->country_code_phone ?? null);

            $phone = PhoneNumberFormatter::toE164(
                $row->telephone ?? null,
                $countryCode,
                (string) config('academic.notifications.country_code', '51')
            );

            if ($phone === null || isset($seenPhones[$phone])) {
                continue;
            }

            if ($personId !== 0) {
                $seenPersons[$personId] = true;
            }

            $seenPhones[$phone] = true;

            $recipients->push([
                'student_id' => ((int) $row->student_id) ?: null,
                'person_id' => $personId ?: null,
                'name' => $this->name($row, $personId),
                'phone' => $phone,
                'source' => (string) ($row->source ?? 'programa'),
            ]);
        }

        return $recipients->values();
    }

    private function name(object $row, int $personId): string
    {
        $name = trim((string) ($row->short_name ?? ''));

        if ($name === '') {
            $name = trim((string) ($row->full_name ?? ''));
        }

        return $name !== '' ? $name : 'Alumno ' . $personId;
    }

    /**
     * Alumnos matriculados en el programa (aca_cap_registrations activas), sin
     * exigir telefono: para Telegram lo que importa es el chat_id.
     */
    private function telegramProgramRows(AcaCourse $course): Collection
    {
        $studentIds = AcaCapRegistration::query()
            ->where('course_id', $course->id)
            ->where('status', true)
            ->pluck('student_id')
            ->unique()
            ->all();

        if ($studentIds === []) {
            return collect();
        }

        return $this->telegramRows($studentIds)
            ->map(fn ($row) => $this->withSource($row, 'programa'));
    }

    /**
     * Alumnos con suscripcion activa o vigente, sin exigir telefono.
     */
    private function telegramSubscriptionRows(): Collection
    {
        $studentIds = $this->subscriptionStudentIds();

        if ($studentIds === []) {
            return collect();
        }

        return $this->telegramRows($studentIds)
            ->map(fn ($row) => $this->withSource($row, 'suscripcion'));
    }

    /**
     * Datos basicos de los alumnos (sin el telefono, que en Telegram es opcional).
     */
    private function telegramRows(array $studentIds): Collection
    {
        return AcaStudent::query()
            ->join('people', 'people.id', '=', 'aca_students.person_id')
            ->whereIn('aca_students.id', $studentIds)
            ->get([
                'aca_students.id as student_id',
                'aca_students.person_id',
                'people.short_name',
                'people.full_name',
            ]);
    }

    /**
     * Chats activos por persona ({person_id: chat_id}) para las personas dadas.
     *
     * @param  array<int, int|string> $personIds
     * @return array<int, string>
     */
    private function subscribedChatsByPerson(array $personIds): array
    {
        $personIds = array_values(array_unique(array_filter(array_map('intval', $personIds))));

        if ($personIds === []) {
            return [];
        }

        return IntegrationTelegramContact::query()
            ->subscribed()
            ->whereIn('person_id', $personIds)
            ->pluck('chat_id', 'person_id')
            ->mapWithKeys(fn ($chatId, $personId) => [(int) $personId => (string) $chatId])
            ->all();
    }

    /**
     * Deduplica por persona y por chat, y descarta a quien no tiene chat activo.
     */
    private function normalizeTelegram(Collection $rows): Collection
    {
        $personIds = $rows->pluck('person_id')->filter()->unique()->all();
        $chats = $this->subscribedChatsByPerson($personIds);

        $seenPersons = [];
        $seenChats = [];
        $recipients = collect();

        foreach ($rows as $row) {
            $personId = (int) $row->person_id;

            if ($personId === 0 || isset($seenPersons[$personId])) {
                continue;
            }

            $chatId = trim((string) ($chats[$personId] ?? ''));

            if ($chatId === '' || isset($seenChats[$chatId])) {
                continue;
            }

            $seenPersons[$personId] = true;
            $seenChats[$chatId] = true;

            $recipients->push([
                'student_id' => ((int) $row->student_id) ?: null,
                'person_id' => $personId,
                'name' => $this->name($row, $personId),
                'phone' => null,
                'chat_id' => $chatId,
                'source' => (string) ($row->source ?? 'programa'),
            ]);
        }

        return $recipients->values();
    }
}
