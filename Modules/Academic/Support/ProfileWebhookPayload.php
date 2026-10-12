<?php

namespace Modules\Academic\Support;

use App\Models\Person;
use App\Models\User;
use Carbon\Carbon;
use Modules\Academic\Entities\AcaCapRegistration;
use Modules\Academic\Entities\AcaStudent;
use Modules\Integrationhub\Support\PersonWebhookPayload;

/**
 * JSON que Academic envia a n8n por el endpoint n8n_post_negociacion.
 *
 * Usa el mismo contrato que el ultimo paso del proceso de negociacion de Commercial
 * (ver NegotiationWebhookPayload) para que n8n reciba siempre los mismos campos:
 * los bloques persona, usuario y estudiante salen del mismo builder compartido y
 * las claves del contrato se mantienen completas, con negociacion y comprobante en
 * null porque este aviso no nace de una negociacion.
 *
 * Se dispara en dos momentos:
 *  - perfil_completado: la primera vez que la persona completa su perfil obligatorio.
 *  - compra_completada: cuando termina una compra (pago aprobado, curso gratis o
 *    webinar, o suscripcion gratis).
 *
 * En ambos casos viaja el bloque 'cursos' con todas las matriculas del alumno al
 * momento del envio: curso comprado, curso gratis y webinar con la misma forma.
 */
class ProfileWebhookPayload
{
    /** Primera vez que la persona completa su perfil obligatorio. */
    public const PERFIL_COMPLETADO = 'perfil_completado';

    /** Termino una compra: pago aprobado, curso gratis, webinar o suscripcion gratis. */
    public const COMPRA_COMPLETADA = 'compra_completada';

    /**
     * Payload a partir del id de la persona. Devuelve null cuando la persona no
     * existe: el job que lo llama simplemente no envia nada.
     *
     * @return array<string, mixed>|null
     */
    public static function forPersonId(?int $personId, string $evento = self::PERFIL_COMPLETADO): ?array
    {
        $person = $personId ? Person::find($personId) : null;

        if (! $person) {
            return null;
        }

        return self::forPerson($person, $evento);
    }

    /**
     * Arma el payload completo: la persona, su usuario, su ficha de alumno y sus cursos.
     *
     * @return array<string, mixed>
     */
    public static function forPerson(Person $person, string $evento = self::PERFIL_COMPLETADO): array
    {
        $user = User::where('person_id', $person->id)->first();
        $student = AcaStudent::where('person_id', $person->id)->first();

        return [
            'evento' => $evento,
            'fecha' => Carbon::now()->toIso8601String(),
            'origen' => self::origin($evento),
            // El contrato se mantiene completo aunque este aviso no sea una
            // negociacion: n8n lee las mismas claves en todos los eventos.
            'negociacion' => null,
            // Los declara el cliente en su perfil, asi que no hay client_data previo.
            'persona' => PersonWebhookPayload::person($person),
            'usuario' => PersonWebhookPayload::user($user),
            'estudiante' => PersonWebhookPayload::student($student),
            'comprobante' => null,
            'items' => [],
            'cursos' => self::courses($student),
        ];
    }

    /**
     * Cursos del alumno: sus matriculas (aca_cap_registrations) con el curso que
     * apuntan (aca_courses). Un curso es gratuito cuando no tiene precio o es cero,
     * y es webinar cuando su tipo es 'Webinar'; el mismo registro sirve para el
     * curso comprado, el gratis y el webinar.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function courses(?AcaStudent $student): array
    {
        if (! $student) {
            return [];
        }

        return AcaCapRegistration::query()
            ->with('course')
            ->where('student_id', $student->id)
            ->orderBy('id')
            ->get()
            ->map(fn (AcaCapRegistration $registration) => self::courseData($registration))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private static function courseData(AcaCapRegistration $registration): array
    {
        $course = $registration->course;
        $price = $course?->price;

        return [
            'registration_id' => $registration->id,
            'course_id' => $registration->course_id,
            'titulo' => $course?->description,
            'tipo' => $course?->type_description,
            'es_webinar' => $course?->type_description === 'Webinar',
            'es_gratuito' => $price === null || (float) $price <= 0,
            'precio' => $price !== null ? (float) $price : null,
            'modalidad_id' => $registration->modality_id,
            'estado' => (bool) $registration->status,
            'sale_note_id' => $registration->sale_note_id,
            'fecha_matricula' => $registration->created_at?->toIso8601String(),
            'imagen' => $course?->image,
        ];
    }

    /**
     * Origen que declara el evento, para que n8n distinga el flujo de perfil del de compra.
     */
    private static function origin(string $evento): string
    {
        return $evento === self::COMPRA_COMPLETADA ? 'academic_compra' : 'academic_profile';
    }
}
