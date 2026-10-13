<?php

namespace Modules\Integrationhub\Support;

use App\Models\Person;
use App\Models\User;
use App\Services\JobOffersAccess;
use Modules\Academic\Entities\AcaCapRegistration;
use Modules\Academic\Entities\AcaStudent;

/**
 * Bloques de persona, usuario y ficha de alumno que viajan a n8n.
 *
 * Vive en Integrationhub porque es el modulo dueno de las integraciones y es el
 * unico lugar donde se define la forma de cada clave. Los payloads de Commercial
 * (negociacion) y de Academic (perfil completado / compra) lo consumen, de modo
 * que ambos envian exactamente los mismos campos y no hay dos definiciones que
 * puedan separarse con el tiempo.
 */
class PersonWebhookPayload
{
    /**
     * Bloque 'persona': los datos de contacto de la ficha.
     *
     * Los datos declarados en el formulario que origina el envio (el client_data de
     * la negociacion) manda sobre la ficha, y la ficha solo rellena lo que el
     * formulario dejo vacio, para que ningun campo viaje en null cuando el sistema
     * ya conoce el dato de la persona.
     *
     * @param  array<string, mixed>  $profile  Datos declarados en el formulario (puede venir vacio).
     * @return array<string, mixed>
     */
    public static function person(Person $person, array $profile = []): array
    {
        $occupation = ($profile['ocupacion'] ?? null) ?: $person->ocupacion;
        $occupationId = ($profile['occupation_id'] ?? null) ?: $person->occupation_id;
        $profession = ($profile['profesion'] ?? null) ?: $person->profession;
        $professionId = ($profile['profession_id'] ?? null) ?: $person->profession_id;
        $company = ($profile['company'] ?? null) ?: $person->company;
        $industry = ($profile['industry'] ?? null) ?: $person->industry;
        $industryId = ($profile['industry_id'] ?? null) ?: $person->industry_id;

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
            'ocupacion' => $occupation,
            'ocupacion_id' => $occupationId,
            // Los tres campos de profesion llevan la profesion declarada por el
            // cliente en el formulario (mismo id y mismo texto). El cargo viaja
            // aparte en ocupacion/ocupacion_id; ya no se mezclan.
            'profesion_id' => $professionId,
            'profesion' => $profession,
            'profesion_texto' => $profession,
            'empresa' => $company,
            'industria' => $industry,
            'industria_id' => $industryId,
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
     * Bloque 'usuario': la cuenta con la que la persona entra al sistema.
     *
     * @return array<string, mixed>
     */
    public static function user(?User $user): array
    {
        return [
            'id' => $user?->id,
            'email' => $user?->email,
        ];
    }

    /**
     * Bloque 'estudiante': la ficha de alumno y los programas en los que esta
     * matriculado. Una persona que no es alumno viaja con los campos en null.
     *
     * @return array<string, mixed>
     */
    public static function student(?AcaStudent $student): array
    {
        return [
            'id' => $student?->id,
            'codigo' => $student?->student_code,
            'carrera' => self::career($student),
        ];
    }

    /**
     * Carrera del alumno: los estudios de nivel programa en los que esta matriculado
     * (aca_cap_registrations + aca_courses.type_description). Los talleres y webinars
     * de horas sueltas son cursos, no carrera, y quedan fuera. El tipo que identifica
     * un programa es el mismo que usa JobOffersAccess para habilitar Ofertas Laborales.
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
}
