<?php

namespace Modules\Academic\Services;

use App\Models\Person;
use App\Services\JobOffersAccess;
use Modules\Academic\Entities\AcaCapRegistration;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaStudent;
use Modules\Integrationhub\Contracts\TelegramRegistrantResolver;

/**
 * Padron que valida el documento que una persona escribe en el bot de Telegram.
 *
 * Es la implementacion academica del contrato de Integrationhub: el webhook del
 * bot no conoce alumnos ni matricula, solo sabe pedir un documento y esperar la
 * ficha de quien lo posee.
 *
 * Se registra quien tenga matricula activa en un programa de especializacion o
 * una suscripcion vigente (la misma regla con la que las campanas arman su
 * padron), porque Telegram no usa telefono: sin chat_id registrado no hay
 * aviso posible.
 */
class TelegramStudentDirectory implements TelegramRegistrantResolver
{
    public function __construct(
        private readonly NotificationAudienceResolver $audienceResolver,
    ) {
    }

    /**
     * @return array{person_id: int, name: string, programs: array<int, string>, subscription: bool}|null
     */
    public function resolveByDocument(string $document): ?array
    {
        $document = trim($document);

        if ($document === '') {
            return null;
        }

        // El numero se guarda tal cual lo registro la persona, asi que la
        // comparacion no distingue mayusculas (un carnet de extranjeria puede
        // llevar letras): se buscan las dos formas, siempre por el indice unico
        // del documento, sin recorrer la tabla entera.
        $person = Person::query()
            ->where('number', $document)
            ->first(['id', 'short_name', 'full_name', 'number'])
            ?? Person::query()
                ->where('number', mb_strtolower($document))
                ->first(['id', 'short_name', 'full_name', 'number']);

        if ($person === null) {
            return null;
        }

        $name = $this->name($person);
        $student = AcaStudent::where('person_id', $person->id)->first();

        // Persona del sistema que no es alumno: existe, pero no tiene acceso.
        if ($student === null) {
            return [
                'person_id' => (int) $person->id,
                'name' => $name,
                'programs' => [],
                'subscription' => false,
            ];
        }

        return [
            'person_id' => (int) $person->id,
            'name' => $name,
            'programs' => $this->specializationPrograms((int) $student->id),
            'subscription' => $this->audienceResolver->studentHasActiveSubscription((int) $student->id),
        ];
    }

    /**
     * Programas de especializacion en los que el alumno tiene matricula activa.
     *
     * @return array<int, string>
     */
    private function specializationPrograms(int $studentId): array
    {
        return AcaCourse::query()
            ->join('aca_cap_registrations', 'aca_cap_registrations.course_id', '=', 'aca_courses.id')
            ->where('aca_cap_registrations.student_id', $studentId)
            ->where('aca_cap_registrations.status', true)
            ->where('aca_courses.type_description', JobOffersAccess::SPECIALIZATION_TYPE)
            ->orderBy('aca_courses.description')
            ->pluck('aca_courses.description')
            ->filter(fn ($description) => trim((string) $description) !== '')
            ->unique()
            ->values()
            ->all();
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
