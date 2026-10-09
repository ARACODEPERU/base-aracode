<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Person;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaSchoolAttendance;
use Modules\Academic\Entities\AcaSchoolEnrollment;
use Modules\Academic\Entities\AcaSchoolSection;
use Modules\Academic\Entities\AcaSchoolYear;
use Modules\Academic\Services\SchoolContextService;

/**
 * Registro de asistencia mensual del colegio, con la misma dinamica de
 * SIAGIE (MINEDU): el docente elige su seccion y un mes, marca cada alumno
 * por dia (A/T/J/F) con la leyenda familiar, "Completa asistencias" hasta
 * un dia dado, y graba o exporta a PDF. Solo ve sus secciones como tutor
 * o auxiliar, igual que en el registro de notas.
 */
class AcaSchoolAttendanceController extends Controller
{
    /** Meses del ano escolar peruano (marzo a diciembre). */
    private const MONTHS = [3, 4, 5, 6, 7, 8, 9, 10, 11, 12];

    /** Letra del dia de la semana en espanol (ISO: 1=lunes ... 7=domingo). */
    private const WEEKDAY_LETTERS = [1 => 'L', 2 => 'M', 3 => 'X', 4 => 'J', 5 => 'V', 6 => 'S', 7 => 'D'];

    public function __construct(private SchoolContextService $context)
    {
    }

    private function currentYear(): ?AcaSchoolYear
    {
        return AcaSchoolYear::where('status', AcaSchoolYear::STATUS_ACTIVE)->orderBy('id')->first()
            ?? AcaSchoolYear::orderBy('id')->first();
    }

    private function currentPerson(): ?Person
    {
        $personId = Auth::user()?->person_id;

        return $personId ? Person::find($personId) : null;
    }

    /**
     * Ids de secciones donde el docente es tutor o auxiliar.
     *
     * @return array<int, int>
     */
    private function teacherSectionIds(?Person $person): array
    {
        if (! $person) {
            return [];
        }

        return AcaSchoolSection::where('tutor_person_id', $person->id)
            ->orWhere('auxiliary_person_id', $person->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function sectionSummary(AcaSchoolSection $section, int $yearId, array $isTutorMap): array
    {
        $students = AcaSchoolEnrollment::where('section_id', $section->id)
            ->where('year_id', $yearId)
            ->where('status', AcaSchoolEnrollment::STATUS_ACTIVO)
            ->count();

        return [
            'id' => $section->id,
            'name' => $section->name,
            'shift' => $section->shift,
            'level' => $section->grade?->level?->name ?? 'N/D',
            'grade' => $section->grade?->name ?? 'N/D',
            'is_tutor' => $isTutorMap[$section->id] ?? false,
            'students' => (int) $students,
        ];
    }

    /**
     * Vista "Mis secciones" para el registro de asistencia.
     */
    public function index(Request $request)
    {
        $person = $this->currentPerson();
        $sectionIds = $this->teacherSectionIds($person);
        $school = $this->context->currentSchool();
        $year = $this->currentYear();

        $sections = collect();

        if ($sectionIds !== [] && $year) {
            $yearId = (int) ($request->get('year_id', $year->id));

            $sections = AcaSchoolSection::with('grade.level')
                ->whereIn('id', $sectionIds)
                ->orderBy('id')
                ->get()
                ->map(fn (AcaSchoolSection $section) => $this->sectionSummary($section, $yearId, [
                    $section->id => $section->tutor_person_id === $person?->id,
                ]));
        }

        return Inertia::render('Academic::School/Attendances/Index', [
            'sections' => $sections,
            'teacherName' => $person?->full_name,
            'schoolName' => $school?->name,
            'months' => $this->monthsForYear($year),
        ]);
    }

    /**
     * Meses del ano escolar con su etiqueta; el actual va primero marcado.
     */
    private function monthsForYear(?AcaSchoolYear $year): array
    {
        $currentMonth = (int) now()->format('n');

        return collect(self::MONTHS)
            ->map(fn ($month) => [
                'value' => $month,
                'label' => ucfirst(Carbon::create(null, $month, 1)->translatedFormat('F')),
                'current' => $month === $currentMonth,
            ])
            ->values()
            ->all();
    }

    /**
     * Grilla mensual de asistencia de una seccion: dias del mes con su letra
     * (L M X J V S D), fines de semana y dias futuros deshabilitados, y las
     * marcas ya guardadas por alumno.
     */
    public function show(Request $request, int $sectionId)
    {
        $person = $this->currentPerson();

        if (! in_array($sectionId, $this->teacherSectionIds($person), true)) {
            abort(403, 'No tienes asignada esta sección.');
        }

        $section = AcaSchoolSection::with('grade.level')->findOrFail($sectionId);
        $year = $this->currentYear();
        $yearId = $year?->id;
        $calendarYear = (int) ($year?->year ?? now()->format('Y'));

        $month = (int) ($request->query('month', now()->format('n')));
        if (! in_array($month, self::MONTHS, true)) {
            $month = in_array((int) now()->format('n'), self::MONTHS, true) ? (int) now()->format('n') : 3;
        }

        $today = now()->startOfDay();
        $firstDay = Carbon::create($calendarYear, $month, 1)->startOfDay();
        $daysInMonth = $firstDay->daysInMonth;

        $days = collect(range(1, $daysInMonth))
            ->map(function ($day) use ($firstDay, $today) {
                $date = $firstDay->copy()->setDay($day);

                return [
                    'day' => $day,
                    'letter' => self::WEEKDAY_LETTERS[$date->dayOfWeekIso],
                    'weekend' => $date->dayOfWeekIso >= 6,
                    'future' => $date->greaterThan($today),
                ];
            })
            ->all();

        $enrollments = AcaSchoolEnrollment::with('student.person')
            ->where('section_id', $sectionId)
            ->where('year_id', $yearId)
            ->where('status', AcaSchoolEnrollment::STATUS_ACTIVO)
            ->orderBy('id')
            ->get();

        $records = AcaSchoolAttendance::where('section_id', $sectionId)
            ->whereYear('attendance_date', $calendarYear)
            ->whereMonth('attendance_date', $month)
            ->orderBy('id')
            ->get()
            ->groupBy('enrollment_id');

        $students = $enrollments->map(function (AcaSchoolEnrollment $enrollment) use ($records) {
            $attendance = [];
            foreach ($records->get($enrollment->id, collect()) as $record) {
                $attendance[$record->attendance_date->format('j')] = $record->status;
            }

            return [
                'enrollment_id' => $enrollment->id,
                'student_code' => $enrollment->student?->student_code,
                'full_name' => $enrollment->student?->person?->full_name ?? 'N/D',
                'attendance' => $attendance,
            ];
        })->values();

        return Inertia::render('Academic::School/Attendances/Monthly', [
            'section' => [
                'id' => $section->id,
                'name' => $section->name,
                'shift' => $section->shift,
                'level' => $section->grade?->level?->name ?? 'N/D',
                'grade' => $section->grade?->name ?? 'N/D',
            ],
            'calendar' => [
                'year' => $calendarYear,
                'month' => $month,
                'days' => $days,
            ],
            'months' => $this->monthsForYear($year),
            'students' => $students,
        ]);
    }

    /**
     * Guarda (upsert) las marcas del mes.
     * Body: { month: int, entries: [{ enrollment_id, day, status: A|T|J|F }] }
     */
    public function store(Request $request, int $sectionId)
    {
        $person = $this->currentPerson();

        if (! in_array($sectionId, $this->teacherSectionIds($person), true)) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes asignada esta sección.',
            ], 403);
        }

        $validated = $this->validate($request, [
            'month' => 'required|integer|between:1,12',
            'entries' => 'required|array|min:1',
            'entries.*.enrollment_id' => 'required|integer|exists:aca_school_enrollments,id',
            'entries.*.day' => 'required|integer|between:1,31',
            // Estado vacio = quitar la marca guardada de ese dia
            'entries.*.status' => 'nullable|in:A,T,J,F',
        ]);

        $section = AcaSchoolSection::findOrFail($sectionId);
        $year = $this->currentYear();
        if (! $year) {
            return response()->json([
                'success' => false,
                'message' => 'No hay un año escolar activo.',
            ], 422);
        }

        $calendarYear = (int) $year->year;
        $month = (int) $validated['month'];
        $daysInMonth = Carbon::create($calendarYear, $month, 1)->daysInMonth;

        $enrollmentIds = AcaSchoolEnrollment::where('section_id', $sectionId)
            ->where('year_id', $year->id)
            ->pluck('id')
            ->all();

        foreach ($validated['entries'] as $entry) {
            if (! in_array((int) $entry['enrollment_id'], $enrollmentIds, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Una de las matrículas no pertenece a esta sección.',
                ], 422);
            }
            if ((int) $entry['day'] > $daysInMonth) {
                return response()->json([
                    'success' => false,
                    'message' => 'El mes seleccionado no tiene el día '.$entry['day'].'.',
                ], 422);
            }
        }

        $userId = Auth::id();
        $saved = 0;

        foreach ($validated['entries'] as $entry) {
            $date = Carbon::create($calendarYear, $month, (int) $entry['day'])->toDateString();

            // Celda vacia: elimina la marca guardada de ese dia
            if (empty($entry['status'])) {
                AcaSchoolAttendance::where('enrollment_id', $entry['enrollment_id'])
                    ->where('attendance_date', $date)
                    ->delete();
                continue;
            }

            AcaSchoolAttendance::updateOrCreate(
                [
                    'enrollment_id' => $entry['enrollment_id'],
                    'attendance_date' => $date,
                ],
                [
                    'school_id' => $section->school_id,
                    'year_id' => $year->id,
                    'section_id' => $sectionId,
                    'status' => $entry['status'],
                    'user_id_registers' => $userId,
                ]
            );
            $saved++;
        }

        return response()->json([
            'success' => true,
            'message' => "Asistencias registradas correctamente ({$saved} marca/s).",
        ]);
    }

    /**
     * PDF de la hoja mensual de asistencia (paisaje), estilo SIAGIE.
     */
    public function pdf(Request $request, int $sectionId)
    {
        $person = $this->currentPerson();

        if (! in_array($sectionId, $this->teacherSectionIds($person), true)) {
            abort(403, 'No tienes asignada esta sección.');
        }

        $section = AcaSchoolSection::with('grade.level', 'school')->findOrFail($sectionId);
        $year = $this->currentYear();
        $calendarYear = (int) ($year?->year ?? now()->format('Y'));
        $month = (int) ($request->query('month', now()->format('n')));

        $firstDay = Carbon::create($calendarYear, $month, 1);
        $days = collect(range(1, $firstDay->daysInMonth))
            ->map(fn ($day) => [
                'day' => $day,
                'letter' => self::WEEKDAY_LETTERS[$firstDay->copy()->setDay($day)->dayOfWeekIso],
                'weekend' => $firstDay->copy()->setDay($day)->dayOfWeekIso >= 6,
            ]);

        $enrollments = AcaSchoolEnrollment::with('student.person')
            ->where('section_id', $sectionId)
            ->where('year_id', $year?->id)
            ->where('status', AcaSchoolEnrollment::STATUS_ACTIVO)
            ->orderBy('id')
            ->get();

        $records = AcaSchoolAttendance::where('section_id', $sectionId)
            ->whereYear('attendance_date', $calendarYear)
            ->whereMonth('attendance_date', $month)
            ->get()
            ->groupBy('enrollment_id');

        $students = $enrollments->map(function (AcaSchoolEnrollment $enrollment, $index) use ($records) {
            $attendance = [];
            foreach ($records->get($enrollment->id, collect()) as $record) {
                $attendance[$record->attendance_date->format('j')] = $record->status;
            }

            return [
                'n' => $index + 1,
                'full_name' => $enrollment->student?->person?->full_name ?? 'N/D',
                'attendance' => $attendance,
            ];
        });

        $pdf = Pdf::loadView('academic::cards.attendances.monthly_report', [
            'school' => $section->school,
            'section' => $section,
            'monthLabel' => ucfirst($firstDay->translatedFormat('F Y')),
            'days' => $days,
            'students' => $students,
            'statuses' => AcaSchoolAttendance::statusLabels(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download("asistencia_{$section->id}_{$calendarYear}_{$month}.pdf");
    }
}
