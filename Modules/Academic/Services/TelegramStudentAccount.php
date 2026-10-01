<?php

namespace Modules\Academic\Services;

use App\Models\Person;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Academic\Entities\AcaCapRegistration;
use Modules\Academic\Entities\AcaCertificate;
use Modules\Academic\Entities\AcaStudent;
use Modules\Academic\Entities\AcaStudentSubscription;
use Modules\Academic\Entities\AcaSubscriptionType;
use Modules\Integrationhub\Contracts\TelegramAccountResolver;

/**
 * Padron de consulta que valida el correo y el documento escritos en el chat del
 * bot de Telegram.
 *
 * Es la implementacion academica del contrato de consulta: el webhook no conoce
 * alumnos ni matricula, solo pide un correo y un documento y espera la ficha de
 * quien los posee. Se exige que el correo coincida con el de la persona para no
 * responder con datos privados a quien solo adivino un documento.
 *
 * Los cursos que devuelve son los de pago vigentes (matricula activa, precio
 * mayor a cero y sin vencer); la suscripcion activa y los certificados los
 * resuelve con las mismas reglas que el portal del alumno.
 */
class TelegramStudentAccount implements TelegramAccountResolver
{
    /**
     * @return array{
     *     person_id: int,
     *     name: string,
     *     courses: array<int, array{description: string, type: string|null, time_limit: string|null}>,
     *     subscription: array{vip: bool, ends_at: string|null}|null,
     *     certificates: array<int, array{id: int, course: string, module: string|null}>,
     *     platform_url: string
     * }|null
     */
    public function resolveAccount(string $document, string $email): ?array
    {
        $document = trim($document);
        $email = $this->normalizeEmail($email);

        if ($document === '' || $email === '') {
            return null;
        }

        $person = $this->person($document);

        // Documento desconocido o correo que no le pertenece: el mismo null para
        // no revelar si el documento esta en el padron.
        if ($person === null || $this->normalizeEmail((string) $person->email) !== $email) {
            return null;
        }

        $student = AcaStudent::where('person_id', $person->id)->first();

        // Persona del sistema que no es alumno: existe, pero no tiene ficha.
        if ($student === null) {
            return [
                'person_id' => (int) $person->id,
                'name' => $this->name($person),
                'courses' => [],
                'subscription' => null,
                'certificates' => [],
                'platform_url' => $this->platformUrl(),
            ];
        }

        $studentId = (int) $student->id;

        return [
            'person_id' => (int) $person->id,
            'name' => $this->name($person),
            'courses' => $this->paidCourses($studentId),
            'subscription' => $this->subscription($studentId),
            'certificates' => $this->certificates($studentId),
            'platform_url' => $this->platformUrl(),
        ];
    }

    /**
     * Persona dueña del documento (misma comparacion que el registro).
     */
    private function person(string $document): ?Person
    {
        return Person::query()
            ->where('number', $document)
            ->first(['id', 'short_name', 'full_name', 'number', 'email'])
            ?? Person::query()
                ->where('number', mb_strtolower($document))
                ->first(['id', 'short_name', 'full_name', 'number', 'email']);
    }

    /**
     * Cursos de pago que la persona tiene disponibles: matricula activa, precio
     * mayor a cero y vigente (ilimitada o sin vencer), sin repetir el curso.
     *
     * @return array<int, array{description: string, type: string|null, time_limit: string|null}>
     */
    private function paidCourses(int $studentId): array
    {
        $today = Carbon::today();

        $registrations = AcaCapRegistration::query()
            ->join('aca_courses', 'aca_courses.id', '=', 'aca_cap_registrations.course_id')
            ->where('aca_cap_registrations.student_id', $studentId)
            ->where('aca_cap_registrations.status', true)
            ->where('aca_courses.price', '>', 0)
            ->where(function ($query) use ($today) {
                $query->where('aca_cap_registrations.unlimited', true)
                    ->orWhere(function ($q) use ($today) {
                        $q->where('aca_cap_registrations.unlimited', false)
                            ->whereDate('aca_cap_registrations.date_end', '>=', $today);
                    });
            })
            ->orderBy('aca_courses.type_description')
            ->orderBy('aca_courses.description')
            ->get([
                'aca_cap_registrations.id',
                'aca_cap_registrations.course_id',
                'aca_cap_registrations.unlimited',
                'aca_cap_registrations.date_end',
                'aca_courses.description',
                'aca_courses.type_description',
            ]);

        $courses = [];

        // Varias filas para el mismo curso (reintentos de pago) se muestran una
        // sola vez: la mas ilimitada y luego la que vence mas tarde.
        foreach ($this->latestRegistrations($registrations) as $registration) {
            $description = trim((string) $registration->description);

            if ($description === '') {
                continue;
            }

            $type = trim((string) $registration->type_description);

            $courses[] = [
                'description' => $description,
                'type' => $type !== '' ? $type : null,
                'time_limit' => $this->timeLimit($registration),
            ];
        }

        return $courses;
    }

    /**
     * Deja una sola matricula por curso, priorizando la mas conveniente.
     *
     * @param  Collection<int, AcaCapRegistration> $registrations
     * @return Collection<int, AcaCapRegistration>
     */
    private function latestRegistrations(Collection $registrations): Collection
    {
        return $registrations
            ->sortByDesc(fn ($registration) => sprintf(
                '%d|%s|%010d',
                (int) (bool) $registration->unlimited,
                (string) $registration->date_end,
                (int) $registration->id
            ))
            ->unique('course_id')
            ->values();
    }

    /**
     * Texto de vigencia de la matricula, o null si no hay dato que mostrar.
     */
    private function timeLimit(AcaCapRegistration $registration): ?string
    {
        if ((bool) $registration->unlimited) {
            return 'Acceso ilimitado';
        }

        $raw = trim((string) $registration->date_end);

        if ($raw === '') {
            return null;
        }

        try {
            return 'Vigente hasta ' . Carbon::parse($raw)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Suscripcion activa o vigente por fechas, con la marca de Premium VIP.
     *
     * @return array{vip: bool, ends_at: string|null}|null
     */
    private function subscription(int $studentId): ?array
    {
        $today = Carbon::today();

        $subscriptions = AcaStudentSubscription::query()
            ->where('student_id', $studentId)
            ->where(function ($query) use ($today) {
                $query->where('status', true)
                    ->orWhere(function ($q) use ($today) {
                        $q->whereDate('date_start', '<=', $today)
                            ->whereDate('date_end', '>=', $today);
                    });
            })
            ->get(['subscription_id', 'date_end']);

        if ($subscriptions->isEmpty()) {
            return null;
        }

        $subscriptionIds = $subscriptions->pluck('subscription_id')
            ->filter()
            ->unique()
            ->all();

        // El plan Premium VIP es el unico que incluye los programas de
        // especializacion: mismo criterio que el portal del alumno.
        $vip = $subscriptionIds !== []
            && AcaSubscriptionType::query()
                ->whereIn('id', $subscriptionIds)
                ->where('title', 'LIKE', '%Premium VIP%')
                ->exists();

        $endsAt = $subscriptions->pluck('date_end')
            ->filter(fn ($date) => trim((string) $date) !== '')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->sort()
            ->last();

        return [
            'vip' => $vip,
            'ends_at' => $endsAt ?: null,
        ];
    }

    /**
     * Certificados del alumno: solo el curso y el modulo al que pertenecen.
     *
     * La descarga no la hace el bot: la persona entra a la plataforma (ver
     * platformUrl) para bajar el archivo.
     *
     * @return array<int, array{id: int, course: string, module: string|null}>
     */
    private function certificates(int $studentId): array
    {
        return AcaCertificate::query()
            ->leftJoin('aca_courses', 'aca_courses.id', '=', 'aca_certificates.course_id')
            ->leftJoin('aca_modules', 'aca_modules.id', '=', 'aca_certificates.module_id')
            ->where('aca_certificates.student_id', $studentId)
            ->orderBy('aca_courses.description')
            ->orderBy('aca_certificates.id')
            ->get([
                'aca_certificates.id',
                'aca_certificates.module_id',
                'aca_courses.description as course_description',
                'aca_modules.description as module_description',
            ])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'course' => trim((string) $row->course_description) ?: 'Curso',
                'module' => trim((string) $row->module_description) ?: null,
            ])
            ->all();
    }

    /**
     * Enlace publico a la plataforma, donde la persona inicia sesion y descarga
     * lo que el bot solo le informa (hoy: los certificados).
     */
    private function platformUrl(): string
    {
        return url('/login');
    }

    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    private function name(Person $person): string
    {
        $name = trim((string) ($person->short_name ?? ''));

        if ($name === '') {
            $name = trim((string) ($person->full_name ?? ''));
        }

        return $name !== '' ? $name : 'Alumno ' . $person->id;
    }
}
