<?php

namespace Modules\Integrationhub\Support;

use App\Models\Person;
use App\Models\User;
use Carbon\Carbon;
use Modules\Academic\Entities\AcaCapRegistration;
use Modules\Academic\Entities\AcaStudent;

/**
 * JSON que se envía a n8n (endpoint n8n_post_negociacion) cuando un alumno
 * completa su perfil por primera vez: sus datos personales y los cursos que
 * acaba de adquirir (gratis o de pago), o ninguno si es un simple registro.
 *
 * Al ser el único lugar donde se define la forma del payload, cualquier cambio
 * de contrato se hace aquí y no en el controlador que lo dispara. Los bloques
 * persona/estudiante/carrera se comparten con el evento de compra a través de
 * WebhookPersonPayload para que n8n reciba siempre las mismas claves.
 */
class StudentProfileWebhookPayload
{
    public const EVENT = 'perfil_completado';

    /**
     * @return array<string, mixed>|null null si la persona no existe.
     */
    public static function forPerson(int $personId): ?array
    {
        $person = Person::find($personId);

        if (! $person) {
            return null;
        }

        $user = User::where('person_id', $person->id)->first();
        $student = AcaStudent::where('person_id', $person->id)->first();

        return [
            'evento' => self::EVENT,
            'fecha' => Carbon::now()->toIso8601String(),
            'origen' => 'person_update_information',
            'persona' => WebhookPersonPayload::person($person),
            'usuario' => [
                'id' => $user?->id,
                'email' => $user?->email,
            ],
            'estudiante' => [
                'id' => $student?->id,
                'codigo' => $student?->student_code,
                'carrera' => WebhookPersonPayload::career($student),
            ],
            'cursos' => self::courses($student, $user),
        ];
    }

    /**
     * Cursos que la persona acaba de adquirir: sus matrículas
     * (aca_cap_registrations) creadas desde que nació su cuenta. Es la única
     * tabla que escriben tanto el registro gratis (WebPageController@storeCourseFree)
     * como la compra de pago (OnliSaleController / MercadopagoController), así
     * que cubre ambos casos sin depender del carrito.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function courses(?AcaStudent $student, ?User $user): array
    {
        if (! $student) {
            return [];
        }

        return AcaCapRegistration::query()
            ->with('course')
            ->where('student_id', $student->id)
            ->when($user?->created_at, function ($query, $since) {
                $query->where('created_at', '>=', $since);
            })
            ->orderBy('created_at')
            ->get()
            ->map(function (AcaCapRegistration $registration) {
                $course = $registration->course;

                return [
                    'registro_id' => $registration->id,
                    'course_id' => $registration->course_id,
                    'nombre' => $course?->description,
                    'tipo' => $course?->type_description,
                    'precio' => $course?->price !== null ? (float) $course->price : null,
                    // status=true: matrícula activa; false: compra aún no confirmada.
                    'estado' => $registration->status ? 'activo' : 'pendiente',
                    // Con nota de venta asociada vino de una compra de pago.
                    'pagado' => $registration->sale_note_id !== null,
                    'sale_note_id' => $registration->sale_note_id,
                    'registrado_en' => $registration->created_at?->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }
}
