<?php

namespace Modules\Health\Support;

use Carbon\Carbon;
use Modules\Dental\Entities\DentAppointment;

/**
 * Traduce entre una cita de la Agenda y un evento de Google Calendar.
 *
 * La comparacion se apoya en una huella (`hash`) de los cuatro campos que se
 * mapean en las dos direcciones: titulo, descripcion, inicio y fin. El estado
 * del evento no entra en la huella a proposito: Google puede marcarlo como
 * `tentative` sin que eso signifique que la cita cambio, y compararlo
 * provocaria escrituras de ida y vuelta sin fin. El unico estado que se atiende
 * aparte es `cancelled` (el evento borrado), que se resuelve antes de comparar.
 */
class AppointmentEventMapper
{
    /**
     * Campos de la cita que, al cambiar, ameritan volver a enviar el evento.
     *
     * `updated_user_id`, `updated_at` o `no_show_at` no estan: cambiar solo eso
     * no es un cambio de la cita.
     *
     * @var array<int, string>
     */
    private const RELEVANT_FIELDS = [
        'patient_id',
        'patient_person_id',
        'doctor_id',
        'doctor_person_id',
        'date_appointmen',
        'time_appointmen',
        'date_end_appointmen',
        'time_end_appointmen',
        'telephone',
        'description',
        'details',
        'message',
        'status',
        'important',
    ];

    /** Plantillas configurables del titulo y la descripcion del evento. */
    private AppointmentEventTemplate $templates;

    public function __construct(?AppointmentEventTemplate $templates = null)
    {
        $this->templates = $templates ?? new AppointmentEventTemplate();
    }

    /**
     * true si alguno de los campos cambiados importa para el calendario.
     *
     * @param array<string, mixed> $changes
     */
    public function isRelevantChange(array $changes): bool
    {
        foreach (array_keys($changes) as $field) {
            if (in_array($field, self::RELEVANT_FIELDS, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cuerpo del evento que se envia a Google.
     *
     * Los recordatorios propios de Google se desactivan (useDefault = false y
     * sin overrides) porque los avisos al paciente ya los manda Salud por SMS:
     * dejarlos activos duplicaria el aviso.
     *
     * @return array<string, mixed>
     */
    public function payload(DentAppointment $appointment, string $timezone): array
    {
        $start = $this->startAt($appointment, $timezone);
        $end = $this->endAt($appointment, $start, $timezone);

        return [
            'summary' => $this->summaryText($appointment, $start, $end),
            'description' => $this->descriptionText($appointment, $start, $end),
            'start' => ['dateTime' => $start->format('c'), 'timeZone' => $timezone],
            'end' => ['dateTime' => $end->format('c'), 'timeZone' => $timezone],
            'status' => 'confirmed',
            'transparency' => 'opaque',
            'reminders' => ['useDefault' => false, 'overrides' => []],
            'extendedProperties' => [
                'private' => [
                    // Identidad del evento: es lo que permite reconocerlo aunque
                    // se pierda la fila de mapeo.
                    'healAppointmentId' => (string) $appointment->id,
                    'healPatientId' => (string) $appointment->patient_id,
                    'healDoctorId' => (string) $appointment->doctor_id,
                    'healOrigin' => 'health',
                ],
            ],
        ];
    }

    /**
     * Huella de los campos comparables.
     *
     * @param array<string, mixed> $fields
     */
    public function hash(array $fields): string
    {
        return sha1(json_encode($fields));
    }

    /**
     * Campos comparables de un cuerpo de evento que escribimos nosotros.
     *
     * @param array<string, mixed> $payload
     * @return array<string, string>
     */
    public function fieldsFromPayload(array $payload): array
    {
        return [
            'summary' => trim((string) ($payload['summary'] ?? '')),
            'description' => trim((string) ($payload['description'] ?? '')),
            'start' => $this->normalizedMoment($this->momentFromPayload($payload['start'] ?? null)),
            'end' => $this->normalizedMoment($this->momentFromPayload($payload['end'] ?? null)),
        ];
    }

    /**
     * Campos comparables de un evento tal como lo devuelve Google.
     *
     * @param array<string, mixed> $event
     * @return array<string, string>
     */
    public function fieldsFromEvent(array $event, string $timezone): array
    {
        return [
            'summary' => trim((string) ($event['summary'] ?? '')),
            'description' => trim((string) ($event['description'] ?? '')),
            'start' => $this->normalizedMoment($this->momentFromEvent($event['start'] ?? null, $timezone)),
            'end' => $this->normalizedMoment($this->momentFromEvent($event['end'] ?? null, $timezone)),
        ];
    }

    /**
     * Datos del evento que se guardan en la fila de mapeo.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function mappingAttributes(array $payload, string $timezone): array
    {
        return [
            'summary' => mb_substr((string) ($payload['summary'] ?? ''), 0, 255),
            'starts_at' => $this->momentFromPayload($payload['start'] ?? null),
            'ends_at' => $this->momentFromPayload($payload['end'] ?? null),
            'location' => null,
        ];
    }

    /**
     * Atributos de la cita que hay que actualizar segun el evento de Google.
     *
     * Solo devuelve los campos que de verdad cambian: asi una lectura sin
     * novedades no toca la cita (ni dispara el observador).
     *
     * @param array<string, mixed> $event
     * @return array<string, mixed>
     */
    public function appointmentChanges(array $event, DentAppointment $appointment, string $timezone): array
    {
        $changes = [];

        $start = $this->momentFromEvent($event['start'] ?? null, $timezone);

        if ($start) {
            $this->putIfDifferent($changes, $appointment, 'date_appointmen', $start->toDateString());
            $this->putIfDifferent($changes, $appointment, 'time_appointmen', $start->format('H:i:s'));
        }

        $end = $this->momentFromEvent($event['end'] ?? null, $timezone);

        if ($end) {
            $this->putIfDifferent($changes, $appointment, 'date_end_appointmen', $end->toDateString());
            $this->putIfDifferent($changes, $appointment, 'time_end_appointmen', $end->format('H:i:s'));
        }

        $description = $this->descriptionFromSummary((string) ($event['summary'] ?? ''), $appointment);

        if ($description !== null) {
            $this->putIfDifferent($changes, $appointment, 'description', $description);
        }

        // Si el evento no trae descripcion ni ubicacion se deja lo que ya tenia
        // la cita: no se borra informacion por un campo vacio.
        $details = trim((string) ($event['description'] ?? ''));

        // La descripcion que escribimos nosotros es un bloque armado con los
        // datos de la cita (correlativo, doctor, telefono, detalles y
        // consultorio). Si el evento trae exactamente ese bloque es nuestro: no
        // se vuelca al campo `details`, porque ahi iria el bloque repetido en
        // lugar del dato original y la lectura dejaria de ser idempotente.
        // Solo se baja la descripcion cuando el texto es distinto al nuestro
        // (es decir, cuando alguien la edito en Google).
        if ($details !== '' && $details !== $this->descriptionText($appointment, $start, $end)) {
            $this->putIfDifferent($changes, $appointment, 'details', mb_substr($details, 0, 255));
        }

        $location = trim((string) ($event['location'] ?? ''));

        if ($location !== '') {
            $this->putIfDifferent($changes, $appointment, 'message', mb_substr($location, 0, 500));
        }

        return $changes;
    }

    /**
     * Titulo del evento segun la plantilla configurada (SC-00018).
     */
    public function summaryText(DentAppointment $appointment, ?Carbon $start = null, ?Carbon $end = null): string
    {
        [$start, $end] = $this->moments($appointment, $start, $end);

        return $this->templates->title($appointment, $start, $end);
    }

    /**
     * Descripcion del evento segun la plantilla configurada (SC-00019).
     *
     * Nunca incluye informacion clinica: solo lo que el consultorio decide
     * mostrar en su calendario.
     *
     * Los momentos que llegan mandan sobre los de la cita: asi la comparacion
     * de ida y vuelta usa el mismo texto que el evento que se acaba de leer.
     */
    public function descriptionText(DentAppointment $appointment, ?Carbon $start = null, ?Carbon $end = null): string
    {
        [$start, $end] = $this->moments($appointment, $start, $end);

        return $this->templates->description($appointment, $start, $end);
    }

    /**
     * Plantillas del evento (las usa la pantalla de Google Calendar).
     */
    public function templates(): AppointmentEventTemplate
    {
        return $this->templates;
    }

    /**
     * Momentos de la cita: los que llegan (de un evento) o los de la cita.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function moments(DentAppointment $appointment, ?Carbon $start, ?Carbon $end): array
    {
        $timezone = (string) config('health.google_calendar.timezone', 'America/Lima');

        $start = $start ?? $this->startAt($appointment, $timezone);
        $end = $end ?? $this->endAt($appointment, $start, $timezone);

        return [$start, $end];
    }

    /**
     * Motivo de la cita a partir del titulo del evento.
     *
     * El titulo puede ser configurable, asi que el nombre del paciente se busca
     * en cualquier parte y se quita junto con los separadores que quedan
     * sueltos: de "Cita: Juan Perez - Limpieza" sale "Cita: Limpieza", y de
     * "Paciente — motivo" sale "motivo". Si no queda un texto con sentido se
     * devuelve null y el motivo guardado no se toca.
     */
    public function descriptionFromSummary(string $summary, DentAppointment $appointment): ?string
    {
        $summary = trim($summary);

        if ($summary === '') {
            return null;
        }

        $patient = trim((string) ($appointment->patient?->full_name ?? ''));
        $text = $summary;
        $position = $patient === '' ? false : mb_stripos($summary, $patient);

        if ($position !== false) {
            $text = mb_substr($summary, 0, $position)
                . ' '
                . mb_substr($summary, $position + mb_strlen($patient));

            // El separador que unia el nombre con el resto queda en medio.
            $text = (string) preg_replace('/\s+[—–|]\s+|\s+-\s+/u', ' ', $text);

            // Envoltorios que el nombre dejo vacios: "Limpieza (María) -> Limpieza ().".
            $text = (string) preg_replace('/[\(\[\{]\s*[\)\]\}]/u', '', $text);
        }

        // Separadores y signos que quedaron sueltos al principio o al final (una
        // llave de apertura al final, o de cierre al principio, no dicen nada).
        $text = (string) preg_replace('/^[\s—–|\-:,\)\]\}]+/u', '', $text);
        $text = (string) preg_replace('/[\s—–|\-:,\(\[\{]+$/u', '', $text);
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));

        // Sin un texto con sentido no se toca el motivo de la cita.
        if (mb_strlen($text) < 3) {
            return null;
        }

        return mb_substr($text, 0, 255);
    }

    /**
     * Separa un titulo en las partes que podrian traer el nombre del paciente.
     *
     * @return array<int, string>
     */
    public function summaryCandidates(string $summary): array
    {
        $summary = trim($summary);

        if ($summary === '') {
            return [];
        }

        $parts = preg_split('/\s*[—–|,:\/]\s*|\s+-\s+/u', $summary) ?: [];
        $candidates = [$summary];

        foreach ($parts as $part) {
            $part = trim($part);

            if (mb_strlen($part) >= 3) {
                $candidates[] = $part;
            }
        }

        return array_values(array_unique($candidates));
    }

    /**
     * Inicio de la cita en la zona horaria del calendario.
     */
    public function startAt(DentAppointment $appointment, string $timezone): Carbon
    {
        $date = $appointment->date_appointmen
            ? Carbon::parse($appointment->date_appointmen)->toDateString()
            : Carbon::now($timezone)->toDateString();

        $time = (string) ($appointment->time_appointmen ?: '00:00:00');

        return Carbon::parse($date . ' ' . $time, $timezone)->seconds(0);
    }

    /**
     * Fin de la cita: el guardado en la cita y, si falta o es invalido, 30
     * minutos despues del inicio.
     */
    public function endAt(DentAppointment $appointment, Carbon $start, string $timezone): Carbon
    {
        $time = (string) ($appointment->time_end_appointmen ?: '');

        if ($time !== '') {
            $date = $appointment->date_end_appointmen
                ? Carbon::parse($appointment->date_end_appointmen)->toDateString()
                : $start->toDateString();

            $end = Carbon::parse($date . ' ' . $time, $timezone)->seconds(0);

            if ($end->greaterThan($start)) {
                return $end;
            }
        }

        return $start->copy()->addMinutes(30);
    }

    /**
     * Momento de un campo `start`/`end` de un evento de Google.
     */
    public function momentFromEvent(mixed $moment, string $timezone): ?Carbon
    {
        if (! is_array($moment)) {
            return null;
        }

        $dateTime = $moment['dateTime'] ?? null;

        if (is_string($dateTime) && $dateTime !== '') {
            try {
                return Carbon::parse($dateTime)->setTimezone($timezone)->seconds(0);
            } catch (\Throwable) {
                return null;
            }
        }

        // Evento de dia completo: Google manda solo la fecha.
        $date = $moment['date'] ?? null;

        if (is_string($date) && $date !== '') {
            try {
                return Carbon::parse($date . ' 00:00:00', $timezone)->seconds(0);
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    /**
     * Momento de un campo `start`/`end` del cuerpo que enviamos nosotros.
     */
    private function momentFromPayload(mixed $moment): ?Carbon
    {
        if (! is_array($moment)) {
            return null;
        }

        $timezone = (string) ($moment['timeZone'] ?? config('health.google_calendar.timezone', 'America/Lima'));
        $dateTime = $moment['dateTime'] ?? null;

        if (! is_string($dateTime) || $dateTime === '') {
            return null;
        }

        try {
            return Carbon::parse($dateTime)->setTimezone($timezone)->seconds(0);
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizedMoment(?Carbon $moment): string
    {
        return $moment?->format('Y-m-d H:i') ?? '';
    }

    /**
     * Agrega el atributo solo si de verdad cambia el valor guardado.
     *
     * @param array<string, mixed> $changes
     */
    private function putIfDifferent(array &$changes, DentAppointment $appointment, string $field, string $value): void
    {
        $current = (string) ($appointment->getAttribute($field) ?? '');

        if (trim($current) === trim($value)) {
            return;
        }

        $changes[$field] = $value;
    }
}
