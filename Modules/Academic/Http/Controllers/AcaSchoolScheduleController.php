<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaSchoolArea;
use Modules\Academic\Entities\AcaSchoolEnrollment;
use Modules\Academic\Entities\AcaSchoolJourney;
use Modules\Academic\Entities\AcaSchoolLevel;
use Modules\Academic\Entities\AcaSchoolSchedule;
use Modules\Academic\Entities\AcaSchoolSection;
use Modules\Academic\Entities\AcaSchoolYear;
use Modules\Academic\Entities\AcaTeacher;
use Modules\Academic\Services\SchoolContextService;

/**
 * Horario del colegio.
 *
 * Cubre las tres necesidades del horario escolar:
 *  1. La jornada: hora oficial de entrada y salida por nivel y turno.
 *  2. El horario de cada seccion: que cursos lleva el alumno, dia por dia.
 *  3. Quien dicta cada curso: cada bloque lleva su docente.
 *
 * Es ademas la base del futuro registro de asistencia por hora del docente:
 * sin horario no se sabe a quien le toca que seccion en cada bloque.
 * Los formularios se envian por axios (XHR): se responde JSON y el redirect
 * queda solo como fallback para submits no-XHR.
 */
class AcaSchoolScheduleController extends Controller
{
    public function __construct(private SchoolContextService $context)
    {
    }

    /* ------------------------- RESPUESTAS ------------------------- */

    /**
     * Respuesta unificada para store/update/copy/jornada.
     */
    private function response(Request $request, ?string $message, array $errors = [])
    {
        if ($errors !== []) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => reset($errors),
                    'errors' => $errors,
                ], 422);
            }

            return back()->withErrors($errors);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->route('aca_school_schedules_index')->with('message', $message);
    }

    private function currentYear(): ?AcaSchoolYear
    {
        return AcaSchoolYear::where('status', AcaSchoolYear::STATUS_ACTIVE)->orderBy('id')->first()
            ?? AcaSchoolYear::orderByDesc('year')->first();
    }

    /**
     * Normaliza una hora a HH:MM:SS (acepta H:MM y HH:MM).
     */
    private function normalizeTime(?string $time): ?string
    {
        if ($time === null || trim($time) === '') {
            return null;
        }

        $parts = explode(':', trim($time));

        if (count($parts) < 2) {
            return null;
        }

        return sprintf('%02d:%02d:00', (int) $parts[0], (int) $parts[1]);
    }

    /** Minutos entre dos horas HH:MM:SS. */
    private function minutesBetween(string $start, string $end): int
    {
        return (int) round((strtotime($end) - strtotime($start)) / 60);
    }

    /* ------------------------- PANEL ------------------------- */

    /**
     * Mantenedor: jornadas del colegio, selector de seccion y grilla semanal
     * del horario de la seccion elegida.
     */
    public function index(Request $request)
    {
        $school = $this->context->currentSchool();

        if (! $school) {
            return Inertia::render('Academic::School/Schedules/Index', $this->emptyPayload());
        }

        $year = $this->currentYear();
        $yearId = (int) ($request->query('year_id') ?: $year?->id);

        $sections = AcaSchoolSection::with('grade.level')
            ->where('school_id', $school->id)
            ->orderBy('grade_id')
            ->orderBy('name')
            ->get()
            ->map(fn (AcaSchoolSection $section) => [
                'id' => $section->id,
                'name' => $section->name,
                'label' => trim(($section->grade?->name ?? '').' '.$section->name),
                'level_id' => $section->grade?->level_id,
                'level_name' => $section->grade?->level?->name ?? 'N/D',
                'level_code' => $section->grade?->level?->code,
                'grade_name' => $section->grade?->name ?? 'N/D',
                'shift' => $section->shift,
                'shift_label' => AcaSchoolSection::shiftLabels()[$section->shift] ?? $section->shift,
            ])
            ->values();

        $sectionId = (int) ($request->query('section_id') ?: $sections->first()['id'] ?? 0);
        $section = $sections->firstWhere('id', $sectionId);

        if (! $section && $sections->isNotEmpty()) {
            $section = $sections->first();
            $sectionId = (int) $section['id'];
        }

        // Los alumnos matriculados se muestran en el encabezado de la seccion.
        $students = $sectionId
            ? AcaSchoolEnrollment::where('section_id', $sectionId)
                ->where('year_id', $yearId)
                ->where('status', AcaSchoolEnrollment::STATUS_ACTIVO)
                ->count()
            : 0;

        $blocks = $sectionId
            ? AcaSchoolSchedule::with(['area', 'teacher'])
                ->where('section_id', $sectionId)
                ->where('year_id', $yearId)
                ->where('status', true)
                ->orderBy('weekday')
                ->orderBy('start_time')
                ->get()
                ->map(fn (AcaSchoolSchedule $block) => [
                    'id' => $block->id,
                    'weekday' => $block->weekday,
                    'weekday_label' => AcaSchoolSchedule::weekdayLabel($block->weekday),
                    'start_time' => $block->startLabel(),
                    'end_time' => $block->endLabel(),
                    'minutes' => $block->start_time && $block->end_time
                        ? $this->minutesBetween($block->start_time, $block->end_time)
                        : null,
                    'area_id' => $block->area_id,
                    'area_name' => $block->area?->name,
                    'teacher_person_id' => $block->teacher_person_id,
                    'teacher_name' => $block->teacher?->full_name,
                    'room' => $block->room,
                ])
                ->values()
            : collect();

        $journey = AcaSchoolJourney::forSection($sectionId ? AcaSchoolSection::find($sectionId) : null);

        return Inertia::render('Academic::School/Schedules/Index', [
            'school' => ['id' => $school->id, 'name' => $school->name],
            'years' => AcaSchoolYear::where('school_id', $school->id)
                ->orderByDesc('year')
                ->get(['id', 'year', 'status'])
                ->map(fn (AcaSchoolYear $y) => [
                    'id' => $y->id,
                    'year' => $y->year,
                    'status' => $y->status,
                ]),
            'yearId' => $yearId ?: null,
            // La jornada se configura por nivel, existan o no secciones creadas.
            'levels' => $this->levelsPayload($school->id),
            'sections' => $sections,
            'sectionId' => $sectionId ?: null,
            'section' => $section ? array_merge($section, ['students' => $students]) : null,
            'blocks' => $blocks,
            'areas' => $section
                ? AcaSchoolArea::where('school_id', $school->id)
                    ->where('level', $section['level_code'])
                    ->where('status', true)
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get(['id', 'name'])
                : [],
            'teachers' => $this->teachersPayload($school->id),
            'journeys' => $this->journeysPayload($school->id),
            'journey' => $journey ? [
                'id' => $journey->id,
                'entry_time' => AcaSchoolJourney::shortTime($journey->entry_time),
                'exit_time' => AcaSchoolJourney::shortTime($journey->exit_time),
                'tolerance_minutes' => $journey->tolerance_minutes,
                'shift_label' => $journey->shiftLabel(),
                'is_general' => $journey->level_id === null,
            ] : null,
            'days' => $this->daysPayload(),
            'shifts' => AcaSchoolSection::shiftLabels(),
        ]);
    }

    /** Carga vacia cuando el colegio todavia no esta configurado. */
    private function emptyPayload(): array
    {
        return [
            'school' => null,
            'years' => [],
            'yearId' => null,
            'levels' => [],
            'sections' => [],
            'sectionId' => null,
            'section' => null,
            'blocks' => [],
            'areas' => [],
            'teachers' => [],
            'journeys' => [],
            'journey' => null,
            'days' => $this->daysPayload(),
            'shifts' => AcaSchoolSection::shiftLabels(),
        ];
    }

    /**
     * Niveles activos del colegio. Alimenta el combo del modal de jornada, que
     * debe listar Primaria y Secundaria aunque todavia no tengan secciones.
     */
    private function levelsPayload(int $schoolId): array
    {
        return AcaSchoolLevel::where('school_id', $schoolId)
            ->where('status', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'code', 'name'])
            ->map(fn (AcaSchoolLevel $level) => [
                'level_id' => $level->id,
                'level_name' => $level->name,
                'level_code' => $level->code,
            ])
            ->values()
            ->all();
    }

    /** @return array<int, array{value: int, label: string, short: string}> */
    private function daysPayload(): array
    {
        $labels = AcaSchoolSchedule::weekdayLabels();
        $short = AcaSchoolSchedule::weekdayShortLabels();

        return array_map(fn (int $day) => [
            'value' => $day,
            'label' => $labels[$day],
            'short' => $short[$day],
        ], AcaSchoolSchedule::WEEKDAYS);
    }

    /**
     * Docentes del colegio (personas registradas como docentes) con los
     * niveles y secciones donde ya tienen presencia: tutor/auxiliar de la
     * seccion o bloques de horario que ya dictan.
     *
     * El modal de bloque usa level_ids para ofrecer solo los docentes del
     * nivel de la seccion elegida (y no todo el plantel) y section_ids para
     * dejar primero a los vinculados a esa seccion.
     *
     * @return array<int, array<string, mixed>>
     */
    private function teachersPayload(int $schoolId): array
    {
        $levelIds = [];
        $sectionIds = [];

        $link = function (int $personId, ?int $levelId, ?int $sectionId) use (&$levelIds, &$sectionIds): void {
            if ($levelId) {
                $levelIds[$personId][$levelId] = true;
            }

            if ($sectionId) {
                $sectionIds[$personId][$sectionId] = true;
            }
        };

        // Tutor o auxiliar de una seccion del colegio.
        $staffedSections = DB::table('aca_school_sections as s')
            ->join('aca_school_grades as g', 'g.id', '=', 's.grade_id')
            ->where('s.school_id', $schoolId)
            ->where(function ($query) {
                $query->whereNotNull('s.tutor_person_id')
                    ->orWhereNotNull('s.auxiliary_person_id');
            })
            ->get(['s.id as section_id', 'g.level_id', 's.tutor_person_id', 's.auxiliary_person_id']);

        foreach ($staffedSections as $row) {
            $people = [
                (int) $row->tutor_person_id,
                (int) $row->auxiliary_person_id,
            ];

            foreach ($people as $personId) {
                if ($personId) {
                    $link($personId, (int) $row->level_id, (int) $row->section_id);
                }
            }
        }

        // Bloques ya dictados: el docente ya tiene presencia en ese nivel y seccion.
        $taughtSections = DB::table('aca_school_schedules as h')
            ->join('aca_school_sections as s', 's.id', '=', 'h.section_id')
            ->join('aca_school_grades as g', 'g.id', '=', 's.grade_id')
            ->where('h.school_id', $schoolId)
            ->whereNotNull('h.teacher_person_id')
            ->get(['h.teacher_person_id', 'h.section_id', 'g.level_id']);

        foreach ($taughtSections as $row) {
            $link((int) $row->teacher_person_id, (int) $row->level_id, (int) $row->section_id);
        }

        return AcaTeacher::query()
            ->join('people', 'people.id', '=', 'aca_teachers.person_id')
            ->orderBy('people.full_name')
            ->limit(500)
            ->get([
                'people.id as person_id',
                'people.full_name',
                'people.number',
                'aca_teachers.teacher_code',
            ])
            ->map(fn ($row) => [
                'person_id' => (int) $row->person_id,
                'full_name' => $row->full_name,
                'number' => $row->number,
                'teacher_code' => $row->teacher_code,
                'level_ids' => array_map('intval', array_keys($levelIds[(int) $row->person_id] ?? [])),
                'section_ids' => array_map('intval', array_keys($sectionIds[(int) $row->person_id] ?? [])),
            ])
            ->values()
            ->all();
    }

    /** Jornadas configuradas en el colegio. */
    private function journeysPayload(int $schoolId): array
    {
        return AcaSchoolJourney::with('level')
            ->where('school_id', $schoolId)
            ->orderBy('level_id')
            ->orderBy('shift')
            ->get()
            ->map(fn (AcaSchoolJourney $journey) => [
                'id' => $journey->id,
                'level_id' => $journey->level_id,
                'level_name' => $journey->level?->name ?? 'Todo el colegio',
                'shift' => $journey->shift,
                'shift_label' => $journey->shiftLabel(),
                'entry_time' => AcaSchoolJourney::shortTime($journey->entry_time),
                'exit_time' => AcaSchoolJourney::shortTime($journey->exit_time),
                'tolerance_minutes' => $journey->tolerance_minutes,
                'recess_start' => AcaSchoolJourney::shortTime($journey->recess_start),
                'recess_end' => AcaSchoolJourney::shortTime($journey->recess_end),
                'status' => (bool) $journey->status,
            ])
            ->values()
            ->all();
    }

    /* ------------------------- BLOQUES ------------------------- */

    public function store(Request $request)
    {
        return $this->save($request);
    }

    public function update(Request $request, int $id)
    {
        return $this->save($request, $id);
    }

    /**
     * Alta y edicion de un bloque del horario, con las validaciones que evitan
     * los dos errores clasicos: choques dentro de la misma seccion y un docente
     * en dos secciones a la misma hora.
     */
    private function save(Request $request, ?int $id = null)
    {
        $this->validate($request, [
            'section_id' => 'required|integer|exists:aca_school_sections,id',
            'weekday' => 'required|integer|between:1,7',
            'area_id' => 'nullable|integer|exists:aca_school_areas,id',
            // Solo docentes del colegio: el combo ya filtra por nivel, pero la
            // regla evita que se guarde cualquier persona de la base.
            'teacher_person_id' => 'nullable|integer|exists:aca_teachers,person_id',
            'start_time' => 'required|regex:/^\d{1,2}:\d{2}(:\d{2})?$/',
            'end_time' => 'required|regex:/^\d{1,2}:\d{2}(:\d{2})?$/',
            'room' => 'nullable|string|max:40',
            'status' => 'nullable|boolean',
        ], [
            'teacher_person_id.exists' => 'El docente elegido no está registrado como docente del colegio.',
            'area_id.exists' => 'El área curricular elegida no existe en el catálogo del colegio.',
        ]);

        $section = AcaSchoolSection::with('grade.level')->findOrFail($request->input('section_id'));
        $block = $id ? AcaSchoolSchedule::findOrFail($id) : new AcaSchoolSchedule();

        $start = $this->normalizeTime($request->input('start_time'));
        $end = $this->normalizeTime($request->input('end_time'));

        if (! $start || ! $end) {
            return $this->response($request, null, ['start_time' => 'Indique una hora de inicio y fin válidas.']);
        }

        if ($start >= $end) {
            return $this->response($request, null, ['end_time' => 'La hora de fin debe ser posterior a la hora de inicio.']);
        }

        // El area debe pertenecer al nivel de la seccion.
        $areaId = $request->filled('area_id') ? (int) $request->input('area_id') : null;

        if ($areaId) {
            $area = AcaSchoolArea::find($areaId);

            if ($area && ! $area->status) {
                return $this->response($request, null, [
                    'area_id' => 'El área '.$area->name.' está desactivada en Áreas Curriculares.',
                ]);
            }

            if ($area && $section->grade?->level?->code && $area->level !== $section->grade->level->code) {
                return $this->response($request, null, [
                    'area_id' => 'El área '.$area->name.' es de '.AcaSchoolArea::levelLabel($area->level)
                        .' y la sección es de '.($section->grade?->level?->name ?? 'otro nivel').'.',
                ]);
            }
        }

        // El bloque debe caer dentro de la jornada del nivel/turno (si existe).
        $journey = AcaSchoolJourney::forSection($section);

        if ($journey && $start < $journey->entry_time) {
            return $this->response($request, null, [
                'start_time' => 'La jornada de '.$section->name.' empieza a las '
                    .AcaSchoolJourney::shortTime($journey->entry_time).'.',
            ]);
        }

        if ($journey && $end > $journey->exit_time) {
            return $this->response($request, null, [
                'end_time' => 'La jornada de '.$section->name.' termina a las '
                    .AcaSchoolJourney::shortTime($journey->exit_time).'.',
            ]);
        }

        // Choque con otro bloque de la misma seccion el mismo dia.
        $overlap = AcaSchoolSchedule::where('section_id', $section->id)
            ->where('weekday', (int) $request->input('weekday'))
            ->where('status', true)
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->when($id, fn ($q) => $q->where('id', '!=', $id))
            ->first();

        if ($overlap) {
            return $this->response($request, null, [
                'start_time' => 'La sección ya tiene clase de '
                    .AcaSchoolJourney::shortTime($overlap->start_time).' a '
                    .AcaSchoolJourney::shortTime($overlap->end_time).' ese día.',
            ]);
        }

        // Choque del docente en otra seccion a la misma hora.
        $teacherId = $request->filled('teacher_person_id') ? (int) $request->input('teacher_person_id') : null;

        if ($teacherId) {
            $teacherConflict = AcaSchoolSchedule::with('section.grade')
                ->where('teacher_person_id', $teacherId)
                ->where('weekday', (int) $request->input('weekday'))
                ->where('status', true)
                ->where('start_time', '<', $end)
                ->where('end_time', '>', $start)
                ->when($id, fn ($q) => $q->where('id', '!=', $id))
                ->first();

            if ($teacherConflict) {
                $conflictSection = trim(($teacherConflict->section?->grade?->name ?? '').' '.($teacherConflict->section?->name ?? ''));
                $teacherName = Person::where('id', $teacherId)->value('full_name') ?? 'El docente';

                return $this->response($request, null, [
                    'teacher_person_id' => $teacherName.' ya tiene clase en '.$conflictSection.' de '
                        .AcaSchoolJourney::shortTime($teacherConflict->start_time).' a '
                        .AcaSchoolJourney::shortTime($teacherConflict->end_time).' ese día.',
                ]);
            }
        }

        $yearId = (int) ($request->input('year_id') ?: $this->currentYear()?->id);

        if (! $yearId) {
            return $this->response($request, null, ['year_id' => 'No hay un año escolar activo. Registre y active uno primero.']);
        }

        $block->fill([
            'school_id' => $section->school_id,
            'year_id' => $yearId,
            'section_id' => $section->id,
            'area_id' => $areaId,
            'teacher_person_id' => $teacherId,
            'weekday' => (int) $request->input('weekday'),
            'start_time' => $start,
            'end_time' => $end,
            'room' => $request->input('room'),
            'status' => $request->has('status') ? $request->boolean('status') : true,
        ]);

        $block->sort_order = (int) AcaSchoolSchedule::where('section_id', $section->id)
            ->where('weekday', (int) $request->input('weekday'))
            ->when($id, fn ($q) => $q->where('id', '!=', $id))
            ->count();

        $block->save();

        return $this->response($request, $id
            ? __('Bloque del horario actualizado con éxito')
            : __('Bloque del horario registrado con éxito'));
    }

    public function destroy(int $id)
    {
        $block = AcaSchoolSchedule::findOrFail($id);
        $block->delete();

        return response()->json(['success' => true, 'message' => 'Bloque del horario eliminado correctamente']);
    }

    /**
     * Copia los bloques de un dia a otros dias de la misma seccion.
     * Los bloques que ya existen (misma seccion, dia y hora) se omiten.
     */
    public function copyDay(Request $request)
    {
        $this->validate($request, [
            'section_id' => 'required|integer|exists:aca_school_sections,id',
            'from_weekday' => 'required|integer|between:1,7',
            'to_weekdays' => 'required|array|min:1',
            'to_weekdays.*' => 'integer|between:1,7',
            'replace' => 'nullable|boolean',
        ]);

        $section = AcaSchoolSection::findOrFail($request->input('section_id'));
        $from = (int) $request->input('from_weekday');
        $targets = collect($request->input('to_weekdays'))->map(fn ($d) => (int) $d)->reject(fn ($d) => $d === $from)->unique();
        $replace = $request->boolean('replace');

        $source = AcaSchoolSchedule::where('section_id', $section->id)
            ->where('weekday', $from)
            ->where('status', true)
            ->orderBy('start_time')
            ->get();

        if ($source->isEmpty()) {
            return $this->response($request, null, [
                'from_weekday' => 'El día de origen no tiene bloques para copiar.',
            ]);
        }

        if ($targets->isEmpty()) {
            return $this->response($request, null, ['to_weekdays' => 'Elija al menos un día de destino distinto al de origen.']);
        }

        $created = 0;
        $replaced = 0;

        foreach ($targets as $weekday) {
            if ($replace) {
                $replaced += AcaSchoolSchedule::where('section_id', $section->id)
                    ->where('weekday', $weekday)
                    ->delete();
            }

            foreach ($source as $block) {
                $exists = AcaSchoolSchedule::where('section_id', $section->id)
                    ->where('weekday', $weekday)
                    ->where('start_time', $block->start_time)
                    ->exists();

                if ($exists) {
                    continue;
                }

                AcaSchoolSchedule::create([
                    'school_id' => $block->school_id,
                    'year_id' => $block->year_id,
                    'section_id' => $block->section_id,
                    'area_id' => $block->area_id,
                    'teacher_person_id' => $block->teacher_person_id,
                    'weekday' => $weekday,
                    'start_time' => $block->start_time,
                    'end_time' => $block->end_time,
                    'room' => $block->room,
                    'sort_order' => $block->sort_order,
                    'status' => true,
                ]);

                $created++;
            }
        }

        return $this->response($request, __('Se copiaron '.$created.' bloques')
            .($replaced > 0 ? ' ('.$replaced.' reemplazados)' : ''));
    }

    /**
     * Copia el horario completo de otra seccion del mismo nivel/grado.
     */
    public function copySection(Request $request)
    {
        $this->validate($request, [
            'section_id' => 'required|integer|exists:aca_school_sections,id',
            'source_section_id' => 'required|integer|exists:aca_school_sections,id|different:section_id',
            'replace' => 'nullable|boolean',
        ]);

        $section = AcaSchoolSection::findOrFail($request->input('section_id'));
        $sourceSection = AcaSchoolSection::findOrFail($request->input('source_section_id'));

        if ($section->school_id !== $sourceSection->school_id) {
            return $this->response($request, null, ['source_section_id' => 'Las secciones pertenecen a colegios distintos.']);
        }

        $source = AcaSchoolSchedule::where('section_id', $sourceSection->id)
            ->where('status', true)
            ->orderBy('weekday')
            ->orderBy('start_time')
            ->get();

        if ($source->isEmpty()) {
            return $this->response($request, null, [
                'source_section_id' => 'La sección de origen no tiene horario registrado.',
            ]);
        }

        $replaced = 0;

        if ($request->boolean('replace')) {
            $replaced = AcaSchoolSchedule::where('section_id', $section->id)->delete();
        }

        $created = 0;

        foreach ($source as $block) {
            $exists = AcaSchoolSchedule::where('section_id', $section->id)
                ->where('weekday', $block->weekday)
                ->where('start_time', $block->start_time)
                ->exists();

            if ($exists) {
                continue;
            }

            AcaSchoolSchedule::create([
                'school_id' => $section->school_id,
                'year_id' => $block->year_id,
                'section_id' => $section->id,
                'area_id' => $block->area_id,
                'teacher_person_id' => $block->teacher_person_id,
                'weekday' => $block->weekday,
                'start_time' => $block->start_time,
                'end_time' => $block->end_time,
                'room' => $block->room,
                'sort_order' => $block->sort_order,
                'status' => true,
            ]);

            $created++;
        }

        return $this->response($request, __('Se copiaron '.$created.' bloques de la sección '.$sourceSection->name)
            .($replaced > 0 ? ' ('.$replaced.' reemplazados)' : ''));
    }

    /* ------------------------- JORNADA ------------------------- */

    /**
     * Jornada del colegio: hora de entrada y salida por nivel y turno.
     */
    public function storeJourney(Request $request)
    {
        $this->validate($request, [
            'level_id' => 'nullable|integer|exists:aca_school_levels,id',
            'shift' => 'required|in:manana,tarde,noche,jornada',
            'entry_time' => 'required|regex:/^\d{1,2}:\d{2}(:\d{2})?$/',
            'exit_time' => 'required|regex:/^\d{1,2}:\d{2}(:\d{2})?$/',
            'tolerance_minutes' => 'nullable|integer|min:0|max:60',
            'recess_start' => 'nullable|regex:/^\d{1,2}:\d{2}(:\d{2})?$/',
            'recess_end' => 'nullable|regex:/^\d{1,2}:\d{2}(:\d{2})?$/',
        ]);

        $school = $this->context->currentSchool();

        if (! $school) {
            return $this->response($request, null, ['shift' => 'No hay un colegio configurado. Registre uno primero.']);
        }

        $entry = $this->normalizeTime($request->input('entry_time'));
        $exit = $this->normalizeTime($request->input('exit_time'));

        if ($entry >= $exit) {
            return $this->response($request, null, ['exit_time' => 'La hora de salida debe ser posterior a la de entrada.']);
        }

        $levelId = $request->filled('level_id') ? (int) $request->input('level_id') : null;

        AcaSchoolJourney::updateOrCreate(
            ['school_id' => $school->id, 'level_id' => $levelId, 'shift' => $request->input('shift')],
            [
                'entry_time' => $entry,
                'exit_time' => $exit,
                'tolerance_minutes' => (int) ($request->input('tolerance_minutes') ?: 10),
                'recess_start' => $this->normalizeTime($request->input('recess_start')),
                'recess_end' => $this->normalizeTime($request->input('recess_end')),
                'status' => true,
            ]
        );

        return $this->response($request, __('Jornada guardada con éxito'));
    }

    public function destroyJourney(int $id)
    {
        $journey = AcaSchoolJourney::findOrFail($id);
        $journey->delete();

        return response()->json(['success' => true, 'message' => 'Jornada eliminada correctamente']);
    }

    /* ------------------------- DOCENTE ------------------------- */

    /**
     * "Mi horario": los bloques del docente autenticado, dia por dia. Es la
     * puerta de entrada al futuro registro de asistencia por hora.
     */
    public function mySchedule(Request $request)
    {
        $school = $this->context->currentSchool();
        $personId = Auth::user()?->person_id;
        $year = $this->currentYear();

        $blocks = collect();

        if ($personId) {
            $blocks = AcaSchoolSchedule::with(['section.grade.level', 'area'])
                ->where('teacher_person_id', $personId)
                ->where('status', true)
                ->when($year, fn ($q) => $q->where('year_id', $year->id))
                ->orderBy('weekday')
                ->orderBy('start_time')
                ->get()
                ->map(fn (AcaSchoolSchedule $block) => [
                    'id' => $block->id,
                    'weekday' => $block->weekday,
                    'weekday_label' => AcaSchoolSchedule::weekdayLabel($block->weekday),
                    'start_time' => $block->startLabel(),
                    'end_time' => $block->endLabel(),
                    'area_name' => $block->area?->name ?? 'Sin área asignada',
                    'section_id' => $block->section_id,
                    'section_label' => trim(($block->section?->grade?->name ?? '').' '.($block->section?->name ?? '')),
                    'level_name' => $block->section?->grade?->level?->name ?? 'N/D',
                    'room' => $block->room,
                ])
                ->values();
        }

        return Inertia::render('Academic::School/Schedules/MySchedule', [
            'school' => $school ? ['id' => $school->id, 'name' => $school->name] : null,
            'teacherName' => $personId ? Person::where('id', $personId)->value('full_name') : null,
            'year' => $year?->year,
            'blocks' => $blocks,
            'days' => $this->daysPayload(),
        ]);
    }
}
