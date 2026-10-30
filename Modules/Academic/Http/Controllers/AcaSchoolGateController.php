<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaSchoolGateAttendance;
use Modules\Academic\Entities\AcaSchoolSection;
use Modules\Academic\Services\GateScanService;
use Modules\Academic\Services\SchoolContextService;

/**
 * Porteria del colegio: la pantalla donde se escanea el QR del carné para
 * registrar la asistencia institucional (entrada y salida), el escaneo en si y
 * el reporte del dia.
 *
 * La logica vive en GateScanService: aqui solo se valida, se delega y se
 * responde. La hora que se guarda es la del servidor, no la del equipo de la
 * puerta, para que todas las lecturas sean comparables entre si.
 */
class AcaSchoolGateController extends Controller
{
    public function __construct(
        private GateScanService $gate,
        private SchoolContextService $context
    ) {
    }

    /**
     * Pantalla del escáner (cámara, pistola o código a mano).
     */
    public function scanner(Request $request)
    {
        $school = $this->context->currentSchool();
        $day = $this->gate->dayPayload();

        return Inertia::render('Academic::School/Gate/Scanner', [
            'schoolName' => $school?->name,
            'schoolId' => $school?->id,
            'counters' => $day['counters'],
            'recent' => $day['recent'],
            'today' => now()->toDateString(),
            'canReport' => (bool) $request->user()?->can('aca_school_porteria_reporte'),
        ]);
    }

    /**
     * Registra un escaneo. Responde siempre 200 con el detalle del resultado
     * (ok, duplicate, not_found, no_enrollment) para que la pantalla pueda
     * mostrar el aviso correcto; solo una falla real devuelve 422.
     */
    public function scan(Request $request)
    {
        $validated = $this->validate($request, [
            'code' => 'required|string|max:40',
            'mode' => 'nullable|in:in,out',
        ]);

        $result = $this->gate->scan(
            $validated['code'],
            $validated['mode'] ?? AcaSchoolGateAttendance::EVENT_ENTRADA
        );

        $httpStatus = $result['result'] === GateScanService::RESULT_ERROR ? 422 : 200;

        return response()->json($result, $httpStatus);
    }

    /**
     * Contadores y últimas lecturas del día (para la pantalla).
     */
    public function day(Request $request)
    {
        return response()->json($this->gate->dayPayload());
    }

    /**
     * Listado histórico de la asistencia institucional.
     *
     * Es la vista de consulta de portería: todas las filas registradas, con
     * filtros por movimiento (entrada o salida), fecha, nivel, grado, sección
     * y búsqueda por nombre completo, DNI o código de alumno. El CSV del día
     * sigue existiendo aparte; este listado no lo reemplaza.
     */
    public function records(Request $request)
    {
        $school = $this->context->currentSchool();

        if (! $school) {
            abort(404, 'No hay un colegio configurado.');
        }

        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'event' => ['nullable', 'in:in,out'],
            'level_id' => ['nullable', 'integer'],
            'grade_id' => ['nullable', 'integer'],
            'section_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'in:10,20,50,100'],
        ]);

        // Sin fechas se consulta todo el historial: el listado es la vista
        // completa y las fechas solo acotan cuando el usuario las elige.
        $filters = [
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
            'event' => $validated['event'] ?? null,
            'level_id' => $validated['level_id'] ?? null,
            'grade_id' => $validated['grade_id'] ?? null,
            'section_id' => $validated['section_id'] ?? null,
            'search' => $validated['search'] ?? null,
        ];

        $perPage = (int) ($validated['per_page'] ?? 20);

        $records = $this->gate->recordsQuery((int) $school->id, $filters)
            ->paginate($perPage)
            ->onEachSide(2)
            ->withQueryString()
            ->through(fn (AcaSchoolGateAttendance $row) => $this->recordRow($row));

        return Inertia::render('Academic::School/Gate/Records', [
            'schoolName' => $school->name,
            'records' => $records,
            'summary' => $this->gate->recordsSummary((int) $school->id, $filters),
            'filters' => $filters + ['per_page' => $perPage],
            'filterOptions' => $this->recordFilterOptions((int) $school->id),
            'canScan' => (bool) $request->user()?->can('aca_school_porteria_escaner'),
        ]);
    }

    /**
     * Fila del listado: alumno, estructura, horas y situación del día.
     *
     * @return array<string, mixed>
     */
    private function recordRow(AcaSchoolGateAttendance $row): array
    {
        return [
            'id' => $row->id,
            'attendance_date' => $row->attendance_date?->format('d/m/Y'),
            'date_iso' => $row->attendance_date?->toDateString(),
            'student' => $row->student?->person?->full_name ?? 'N/D',
            'code' => $row->student?->student_code,
            'dni' => $row->student?->person?->number,
            'level' => $row->section?->grade?->level?->name,
            'grade' => $row->section?->grade?->name,
            'section' => $row->section?->name,
            'entry_time' => AcaSchoolGateAttendance::shortTime($row->entry_at),
            'entry_status' => $row->entry_status,
            'entry_status_label' => $row->entryStatusLabel(),
            'exit_time' => AcaSchoolGateAttendance::shortTime($row->exit_at),
            'early_exit' => (bool) $row->early_exit,
            'inside' => $row->isInside(),
            'scans_count' => (int) $row->scans_count,
            'observations' => $row->observations,
        ];
    }

    /**
     * Opciones de Nivel → Grado → Sección para los filtros.
     *
     * Se arman con las secciones que aparecen en los registros de portería,
     * no con la estructura del año activo: así el filtro también encuentra
     * secciones de años anteriores que ya no están en la estructura vigente.
     *
     * @return array<int, array<string, mixed>>
     */
    private function recordFilterOptions(int $schoolId): array
    {
        $sections = AcaSchoolSection::query()
            ->with('grade.level')
            ->whereIn('id', AcaSchoolGateAttendance::query()
                ->where('school_id', $schoolId)
                ->select('section_id')
                ->distinct())
            ->get()
            ->filter(fn (AcaSchoolSection $section) => $section->grade && $section->grade->level);

        return $sections
            ->groupBy(fn (AcaSchoolSection $section) => $section->grade->level->id)
            ->map(function ($levelSections) {
                $level = $levelSections->first()->grade->level;

                $grades = $levelSections
                    ->groupBy(fn (AcaSchoolSection $section) => $section->grade->id)
                    ->map(function ($gradeSections) {
                        $grade = $gradeSections->first()->grade;

                        return [
                            'id' => $grade->id,
                            'name' => $grade->name,
                            'sections' => $gradeSections
                                ->unique('id')
                                ->sortBy(fn (AcaSchoolSection $section) => strtolower((string) $section->name))
                                ->map(fn (AcaSchoolSection $section) => [
                                    'id' => $section->id,
                                    'name' => $section->name,
                                ])
                                ->values()
                                ->all(),
                        ];
                    })
                    ->sortBy('name')
                    ->values()
                    ->all();

                return [
                    'id' => $level->id,
                    'name' => $level->name,
                    'grades' => $grades,
                ];
            })
            ->sortBy('name')
            ->values()
            ->all();
    }

    /**
     * Reporte del día en CSV (entradas, salidas y tardanzas por alumno).
     */
    public function report(Request $request)
    {
        $school = $this->context->currentSchool();

        if (! $school) {
            abort(404, 'No hay un colegio configurado.');
        }

        $date = $request->query('date') ?: now()->toDateString();
        $date = Carbon::parse($date)->toDateString();

        $rows = $this->gate->rowsForDay((int) $school->id, $date);
        $fileName = 'porteria_'.$date.'.csv';

        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');

            // BOM para que Excel abra el CSV con acentos.
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Codigo', 'Alumno', 'Seccion', 'Entrada', 'Estado entrada',
                'Salida', 'Salida anticipada', 'Escaneos',
            ]);

            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->student?->student_code,
                    $row->student?->person?->full_name,
                    trim(($row->section?->grade?->name ?? '').' '.($row->section?->name ?? '')),
                    AcaSchoolGateAttendance::shortTime($row->entry_at),
                    $row->entryStatusLabel(),
                    AcaSchoolGateAttendance::shortTime($row->exit_at),
                    $row->early_exit ? 'Si' : 'No',
                    $row->scans_count,
                ]);
            }

            fclose($out);
        };

        return response()->streamDownload($callback, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
