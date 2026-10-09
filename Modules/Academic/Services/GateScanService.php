<?php

namespace Modules\Academic\Services;

use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Modules\Academic\Entities\AcaSchoolAttendance;
use Modules\Academic\Entities\AcaSchoolEnrollment;
use Modules\Academic\Entities\AcaSchoolGateAttendance;
use Modules\Academic\Entities\AcaSchoolJourney;
use Modules\Academic\Entities\AcaSchoolSection;
use Modules\Academic\Entities\AcaSchoolStudent;
use Modules\Academic\Entities\AcaSchoolYear;

/**
 * Registro de la asistencia institucional (porteria): resuelve el codigo leido
 * del carné, decide si la entrada es asistencia o tardanza segun la jornada del
 * nivel, y deja el paso por la puerta en aca_school_gate_attendances.
 *
 * La asistencia de aula (aca_school_attendances) solo se toca para crear la
 * marca A/T de la entrada y con firstOrCreate: si el docente ya marco ese dia
 * (por ejemplo una justificada), la porteria no se la pisa.
 *
 * Vive aparte del controlador para poder probarlo sin HTTP y para que la
 * pantalla de porteria responda con una sola consulta indexada por escaneo.
 */
class GateScanService
{
    /** Resultados posibles de un escaneo. */
    public const RESULT_OK = 'ok';
    public const RESULT_DUPLICATE = 'duplicate';
    public const RESULT_NOT_FOUND = 'not_found';
    public const RESULT_NO_ENROLLMENT = 'no_enrollment';
    public const RESULT_ERROR = 'error';

    public function __construct(private SchoolContextService $context)
    {
    }

    /**
     * Registra un escaneo de la puerta.
     *
     * @param  string      $rawCode lo que devolvio la camara o la pistola
     * @param  string      $mode    'in' (entrada) o 'out' (salida)
     * @param  Carbon|null $at      momento del escaneo (por defecto, ahora)
     * @param  int|null    $userId  quien opera la porteria (por defecto, el usuario en sesion)
     */
    public function scan(string $rawCode, string $mode = AcaSchoolGateAttendance::EVENT_ENTRADA, ?Carbon $at = null, ?int $userId = null): array
    {
        $at = ($at ?? now())->copy();
        $mode = $mode === AcaSchoolGateAttendance::EVENT_SALIDA
            ? AcaSchoolGateAttendance::EVENT_SALIDA
            : AcaSchoolGateAttendance::EVENT_ENTRADA;
        $userId = $userId ?? Auth::id();

        $school = $this->context->currentSchool();

        if (! $school) {
            return $this->fail(self::RESULT_ERROR, 'No hay un colegio configurado. Registre uno primero.');
        }

        $student = $this->findStudent((int) $school->id, $rawCode);

        if (! $student) {
            return $this->fail(self::RESULT_NOT_FOUND, 'Código no encontrado', $rawCode);
        }

        if (! $student->status) {
            return $this->fail(self::RESULT_NOT_FOUND, 'El alumno está inactivo en el colegio', $student->student_code, $student);
        }

        $year = $this->activeYear();
        if (! $year) {
            return $this->fail(self::RESULT_ERROR, 'No hay un año escolar activo.', $student->student_code, $student);
        }

        $enrollment = $this->activeEnrollment($student, $year);

        if (! $enrollment || ! $enrollment->section) {
            return $this->fail(
                self::RESULT_NO_ENROLLMENT,
                'El alumno no tiene matrícula activa en '.$year->year.'.',
                $student->student_code,
                $student
            );
        }

        $section = $enrollment->section;
        $date = $at->toDateString();

        $gate = AcaSchoolGateAttendance::query()
            ->where('enrollment_id', $enrollment->id)
            ->where('attendance_date', $date)
            ->first();

        return $mode === AcaSchoolGateAttendance::EVENT_ENTRADA
            ? $this->registerEntry($gate, $enrollment, $section, $student, $year, $at, $userId, (int) $school->id)
            : $this->registerExit($gate, $enrollment, $section, $student, $year, $at, $userId, (int) $school->id);
    }

    /**
     * Contadores del dia y ultimos escaneos, para pintar la pantalla de porteria
     * sin consultar la base en cada lectura.
     */
    public function dayPayload(?Carbon $at = null, int $limit = 12): array
    {
        $school = $this->context->currentSchool();
        $date = ($at ?? now())->toDateString();

        if (! $school) {
            return ['counters' => $this->emptyCounters(), 'recent' => []];
        }

        $recent = $this->rowsForDay((int) $school->id, $date, $limit)
            ->map(fn (AcaSchoolGateAttendance $row) => $this->rowPayload($row))
            ->all();

        return [
            'counters' => $this->counters((int) $school->id, $date),
            'recent' => $recent,
        ];
    }

    /**
     * Filas de la asistencia institucional de un dia (del mas reciente al mas
     * antiguo). Lo usan la pantalla de porteria y el reporte.
     */
    public function rowsForDay(int $schoolId, string $date, ?int $limit = null)
    {
        return AcaSchoolGateAttendance::query()
            ->where('school_id', $schoolId)
            ->where('attendance_date', $date)
            ->with(['student.person', 'section.grade.level'])
            ->orderByDesc('updated_at')
            ->when($limit, fn ($query, $limit) => $query->limit($limit))
            ->get();
    }

    /**
     * Contadores del dia en una sola consulta.
     */
    public function counters(int $schoolId, string $date): array
    {
        $row = AcaSchoolGateAttendance::query()
            ->where('school_id', $schoolId)
            ->where('attendance_date', $date)
            ->selectRaw('COUNT(entry_at) as total')
            ->selectRaw("SUM(CASE WHEN entry_status = ? THEN 1 ELSE 0 END) as attended", [AcaSchoolGateAttendance::ENTRY_ASISTENCIA])
            ->selectRaw("SUM(CASE WHEN entry_status = ? THEN 1 ELSE 0 END) as late", [AcaSchoolGateAttendance::ENTRY_TARDANZA])
            ->selectRaw('COUNT(exit_at) as exited')
            ->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'attended' => (int) ($row->attended ?? 0),
            'late' => (int) ($row->late ?? 0),
            'exited' => (int) ($row->exited ?? 0),
        ];
    }

    private function emptyCounters(): array
    {
        return ['total' => 0, 'attended' => 0, 'late' => 0, 'exited' => 0];
    }

    /**
     * Entrada al colegio: crea la asistencia institucional del dia y, si el
     * docente no marco nada todavia, la marca A/T de aula.
     */
    private function registerEntry(
        ?AcaSchoolGateAttendance $gate,
        AcaSchoolEnrollment $enrollment,
        AcaSchoolSection $section,
        AcaSchoolStudent $student,
        AcaSchoolYear $year,
        Carbon $at,
        ?int $userId,
        int $schoolId
    ): array {
        // Ya registro su entrada hoy: se avisa sin reescribir nada.
        if ($gate && $gate->entry_at) {
            $gate->update([
                'scans_count' => $gate->scans_count + 1,
                'last_event' => AcaSchoolGateAttendance::EVENT_ENTRADA,
            ]);

            return $this->response(self::RESULT_DUPLICATE, $gate->fresh(['student.person', 'section.grade.level']), [
                'duplicate' => true,
                'message' => 'Ya registró su entrada a las '.AcaSchoolGateAttendance::shortTime($gate->entry_at).'.',
                'counters' => $this->counters($schoolId, $at->toDateString()),
            ]);
        }

        $entryStatus = $this->entryStatus($section, $at);

        // La marca de aula se crea solo si ese dia no tiene ninguna: la palabra
        // final sobre la asistencia del aula la tiene el docente.
        $classroom = AcaSchoolAttendance::firstOrCreate(
            [
                'enrollment_id' => $enrollment->id,
                'attendance_date' => $at->toDateString(),
            ],
            [
                'school_id' => $schoolId,
                'year_id' => $year->id,
                'section_id' => $section->id,
                'status' => $entryStatus,
                'user_id_registers' => $userId,
            ]
        );

        $values = [
            'school_id' => $schoolId,
            'year_id' => $year->id,
            'section_id' => $section->id,
            'student_id' => $student->id,
            'entry_at' => $at,
            'entry_status' => $entryStatus,
            'last_event' => AcaSchoolGateAttendance::EVENT_ENTRADA,
            'classroom_attendance_id' => $classroom->id,
            'user_id_registers' => $userId,
        ];

        if ($gate) {
            // La fila existia sin entrada (por ejemplo una salida suelta): se completa.
            $gate->update($values + ['scans_count' => $gate->scans_count + 1]);
        } else {
            $gate = $this->createGateRow($enrollment->id, $at->toDateString(), $values);

            if (! $gate) {
                // Otra porteria gano la carrera del mismo escaneo: para esta
                // lectura es un duplicado, nunca un error 500.
                $existing = $this->findGateRow($enrollment->id, $at->toDateString());

                if (! $existing) {
                    return $this->fail(self::RESULT_ERROR, 'No se pudo registrar el escaneo. Vuelva a intentarlo.', $student->student_code, $student);
                }

                return $this->response(self::RESULT_DUPLICATE, $existing->fresh(['student.person', 'section.grade.level']), [
                    'duplicate' => true,
                    'message' => 'Ya registrado a las '.AcaSchoolGateAttendance::shortTime($existing->entry_at ?? $existing->exit_at).'.',
                    'counters' => $this->counters($schoolId, $at->toDateString()),
                ]);
            }
        }

        $gate = $gate->fresh(['student.person', 'section.grade.level']);

        return $this->response(self::RESULT_OK, $gate, [
            'duplicate' => false,
            'message' => 'Entrada registrada'.($entryStatus === AcaSchoolGateAttendance::ENTRY_TARDANZA ? ' con tardanza' : '').'.',
            'counters' => $this->counters($schoolId, $at->toDateString()),
        ]);
    }

    /**
     * Salida del colegio: no toca la marca de aula. Si nadie escanea la salida,
     * el dia queda con exit_at nulo y no pasa nada.
     */
    private function registerExit(
        ?AcaSchoolGateAttendance $gate,
        AcaSchoolEnrollment $enrollment,
        AcaSchoolSection $section,
        AcaSchoolStudent $student,
        AcaSchoolYear $year,
        Carbon $at,
        ?int $userId,
        int $schoolId
    ): array {
        $earlyExit = $this->isEarlyExit($section, $at);

        $values = [
            'school_id' => $schoolId,
            'year_id' => $year->id,
            'section_id' => $section->id,
            'student_id' => $student->id,
            'exit_at' => $at,
            'early_exit' => $earlyExit,
            'last_event' => AcaSchoolGateAttendance::EVENT_SALIDA,
            'user_id_registers' => $userId,
        ];

        if ($gate) {
            // La ultima salida del dia es la que vale.
            $gate->update($values + ['scans_count' => $gate->scans_count + 1]);
        } else {
            $gate = $this->createGateRow($enrollment->id, $at->toDateString(), $values);

            if (! $gate) {
                // Otra porteria registro a la vez: se responde con la fila real.
                $gate = $this->findGateRow($enrollment->id, $at->toDateString());

                if (! $gate) {
                    return $this->fail(self::RESULT_ERROR, 'No se pudo registrar la salida. Vuelva a intentarlo.', $student->student_code, $student);
                }
            }
        }

        $message = $earlyExit
            ? 'Salida registrada antes de la hora oficial.'
            : 'Salida registrada.';

        return $this->response(self::RESULT_OK, $gate->fresh(['student.person', 'section.grade.level']), [
            'duplicate' => false,
            'message' => $message,
            'early_exit' => $earlyExit,
            'counters' => $this->counters($schoolId, $at->toDateString()),
        ]);
    }

    /**
     * Crea la fila del dia. Devuelve null si otra porteria inserto la misma
     * (enrollment, fecha) en paralelo: el indice unico lo bloqueo.
     */
    private function createGateRow(int $enrollmentId, string $date, array $values): ?AcaSchoolGateAttendance
    {
        try {
            return AcaSchoolGateAttendance::create(
                $values + ['enrollment_id' => $enrollmentId, 'attendance_date' => $date, 'scans_count' => 1]
            );
        } catch (QueryException $e) {
            if (! $this->isDuplicateKey($e)) {
                throw $e;
            }

            return null;
        }
    }

    private function findGateRow(int $enrollmentId, string $date): ?AcaSchoolGateAttendance
    {
        return AcaSchoolGateAttendance::query()
            ->where('enrollment_id', $enrollmentId)
            ->where('attendance_date', $date)
            ->first();
    }

    /** El error es una clave duplicada, no una falla de la base. */
    private function isDuplicateKey(QueryException $e): bool
    {
        return in_array((string) $e->getCode(), ['23000', '23505'], true);
    }

    /**
     * A / T segun la jornada del nivel y turno de la seccion. Sin jornada
     * configurada se registra asistencia: la tardanza no se inventa.
     */
    public function entryStatus(AcaSchoolSection $section, Carbon $at): string
    {
        $journey = AcaSchoolJourney::forSection($section);

        if (! $journey || ! $journey->entry_time) {
            return AcaSchoolGateAttendance::ENTRY_ASISTENCIA;
        }

        $limit = Carbon::parse($at->toDateString().' '.$journey->entry_time)
            ->addMinutes((int) $journey->tolerance_minutes);

        return $at->greaterThan($limit)
            ? AcaSchoolGateAttendance::ENTRY_TARDANZA
            : AcaSchoolGateAttendance::ENTRY_ASISTENCIA;
    }

    /** Salida antes de la hora oficial (con la tolerancia como margen). */
    private function isEarlyExit(AcaSchoolSection $section, Carbon $at): bool
    {
        $journey = AcaSchoolJourney::forSection($section);

        if (! $journey || ! $journey->exit_time) {
            return false;
        }

        $limit = Carbon::parse($at->toDateString().' '.$journey->exit_time)
            ->subMinutes((int) $journey->tolerance_minutes);

        return $at->lessThan($limit);
    }

    /**
     * Busca al alumno por el codigo del carné. Intenta con el codigo
     * normalizado (lo que teclea la pistola suele traer un retorno de carro) y,
     * si no aparece, con el valor tal cual llego.
     */
    private function findStudent(int $schoolId, string $rawCode): ?AcaSchoolStudent
    {
        $normalized = StudentCardQr::normalize($rawCode);

        if ($normalized === '') {
            return null;
        }

        $student = AcaSchoolStudent::query()
            ->where('school_id', $schoolId)
            ->where('student_code', $normalized)
            ->with('person')
            ->first();

        if ($student) {
            return $student;
        }

        $raw = trim($rawCode);

        if ($raw === $normalized) {
            return null;
        }

        return AcaSchoolStudent::query()
            ->where('school_id', $schoolId)
            ->where('student_code', $raw)
            ->with('person')
            ->first();
    }

    private function activeYear(): ?AcaSchoolYear
    {
        return AcaSchoolYear::where('status', AcaSchoolYear::STATUS_ACTIVE)->orderBy('id')->first()
            ?? AcaSchoolYear::orderBy('id')->first();
    }

    private function activeEnrollment(AcaSchoolStudent $student, AcaSchoolYear $year): ?AcaSchoolEnrollment
    {
        return AcaSchoolEnrollment::query()
            ->where('student_id', $student->id)
            ->where('year_id', $year->id)
            ->where('status', AcaSchoolEnrollment::STATUS_ACTIVO)
            ->with('section.grade.level')
            ->orderByDesc('id')
            ->first();
    }

    /** Respuesta uniforme del escaneo. */
    private function response(string $result, ?AcaSchoolGateAttendance $gate, array $extra = []): array
    {
        return array_merge([
            'result' => $result,
            'ok' => $result === self::RESULT_OK || $result === self::RESULT_DUPLICATE,
            'code' => $gate?->student?->student_code,
            'student' => $gate?->student?->person?->full_name,
            'section' => $this->sectionLabel($gate?->section),
            'status' => $gate?->entry_status,
            'status_label' => $gate?->entryStatusLabel(),
            // La hora que se muestra es la del evento que se acaba de registrar:
            // en una salida debe verse la hora de salida, no la de entrada.
            'time' => AcaSchoolGateAttendance::shortTime(
                $gate?->last_event === AcaSchoolGateAttendance::EVENT_SALIDA ? $gate?->exit_at : $gate?->entry_at
            ),
            'entry_time' => AcaSchoolGateAttendance::shortTime($gate?->entry_at),
            'exit_time' => AcaSchoolGateAttendance::shortTime($gate?->exit_at),
            'mode' => $gate?->last_event,
            'duplicate' => false,
            'message' => '',
        ], $extra);
    }

    private function fail(string $result, string $message, ?string $code = null, ?AcaSchoolStudent $student = null): array
    {
        return [
            'result' => $result,
            'ok' => false,
            'code' => $code,
            'student' => $student?->person?->full_name,
            'section' => null,
            'status' => null,
            'status_label' => null,
            'time' => null,
            'mode' => null,
            'duplicate' => false,
            'message' => $message,
            'counters' => null,
        ];
    }

    /** Fila del listado de la pantalla de porteria. */
    private function rowPayload(AcaSchoolGateAttendance $row): array
    {
        return [
            'id' => $row->id,
            'student' => $row->student?->person?->full_name ?? 'N/D',
            'code' => $row->student?->student_code,
            'section' => $this->sectionLabel($row->section),
            'entry_time' => AcaSchoolGateAttendance::shortTime($row->entry_at),
            'exit_time' => AcaSchoolGateAttendance::shortTime($row->exit_at),
            'status' => $row->entry_status,
            'status_label' => $row->entryStatusLabel(),
            'early_exit' => (bool) $row->early_exit,
            'scans_count' => (int) $row->scans_count,
            'inside' => $row->isInside(),
        ];
    }

    private function sectionLabel(?AcaSchoolSection $section): ?string
    {
        if (! $section) {
            return null;
        }

        return trim(($section->grade?->name ?? '').' '.$section->name);
    }
}
