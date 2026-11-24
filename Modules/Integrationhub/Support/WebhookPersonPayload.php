<?php

namespace Modules\Integrationhub\Support;

use App\Models\Person;
use App\Services\JobOffersAccess;
use Modules\Academic\Entities\AcaCapRegistration;
use Modules\Academic\Entities\AcaStudent;

/**
 * Bloques compartidos del contrato que viaja a n8n: la ficha de la persona, su
 * carrera de alumno y la URL pública de un comprobante. Los usan tanto el evento
 * de perfil completado como el de compra, para que ambos envíen las mismas claves.
 */
class WebhookPersonPayload
{
    /**
     * Datos de la ficha de la persona. Mismo contrato que el bloque `persona`
     * de Modules\Commercial\Support\NegotiationWebhookPayload.
     *
     * @return array<string, mixed>
     */
    public static function person(Person $person): array
    {
        return [
            'id' => $person->id,
            'nombre_completo' => $person->full_name ?: $person->short_name,
            'nombres' => $person->names,
            'apellido_paterno' => $person->father_lastname,
            'apellido_materno' => $person->mother_lastname,
            'tipo_documento' => $person->document_type_id,
            'numero_documento' => $person->number,
            'email' => $person->email,
            'telefono' => $person->telephone,
            'genero' => $person->gender,
            'ocupacion' => $person->ocupacion,
            'ocupacion_id' => $person->occupation_id,
            'profesion_id' => $person->profession_id,
            'profesion' => $person->profession,
            'profesion_texto' => $person->profession,
            'empresa' => $person->company,
            'industria' => $person->industry,
            'industria_id' => $person->industry_id,
            'fecha_nacimiento' => $person->birthdate,
            'direccion' => $person->address,
            'ubigeo' => $person->ubigeo,
            'ciudad' => $person->ubigeo_description,
            'pais_extranjero_id' => $person->foreign_country_id,
            'departamento_estado' => $person->foreign_state,
            'ciudad_extranjera' => $person->foreign_city,
        ];
    }

    /**
     * Carrera del alumno: los estudios de nivel programa en los que está
     * matriculado (aca_cap_registrations + aca_courses.type_description). Los
     * talleres y webinars de horas sueltas son cursos, no carrera, y quedan
     * fuera. Misma regla que NegotiationWebhookPayload::career().
     *
     * @return array<int, string>
     */
    public static function career(?AcaStudent $student): array
    {
        if (! $student) {
            return [];
        }

        return AcaCapRegistration::query()
            ->join('aca_courses', 'aca_courses.id', '=', 'aca_cap_registrations.course_id')
            ->where('aca_cap_registrations.student_id', $student->id)
            ->where('aca_courses.type_description', JobOffersAccess::SPECIALIZATION_TYPE)
            ->orderBy('aca_cap_registrations.id')
            ->pluck('aca_courses.description')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Convierte la ruta absoluta con la que se guarda el PDF del comprobante
     * (public/storage/invoice/20613668323-03-B001-247.pdf) en su URL pública
     * (.../storage/invoice/20613668323-03-B001-247.pdf), que es la que un
     * sistema externo como n8n puede descargar.
     */
    public static function publicInvoiceUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        // Si ya viene una URL (documentos antiguos) se devuelve tal cual.
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        $file = basename(str_replace('\\', '/', $path));

        return rtrim(config('app.url') ?: url('/'), '/').'/storage/invoice/'.$file;
    }
}
