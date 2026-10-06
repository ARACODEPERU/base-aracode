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
use Modules\Academic\Entities\AcaSchoolGradeRecord;
use Modules\Academic\Entities\AcaSchoolLevel;
use Modules\Academic\Entities\AcaSchoolSection;
use Modules\Academic\Entities\AcaSchoolYear;
use Modules\Academic\Services\SchoolContextService;

/**
 * Registro de notas del colegio para el rol Docente.
 * El docente accede solo a las secciones donde es tutor o auxiliar y
 * registra notas por area curricular y bimestre, en escala vigesimal
 * (0-20, Primaria/Secundaria) o literal (AD/A/B/C, Inicial) segun MINEDU.
 */
class AcaSchoolTeacherGradesController extends Controller
{
    public function __construct(private SchoolContextService $context)
    {
    }

    /**
     * Areas curriculares activas de un nivel: primero el catalogo del colegio
     * (aca_school_areas, mantenido desde el CRUD "Areas Curriculares") y, si
     * el colegio no ha cargado el suyo, el estandar CNEB para que el docente
     * nunca quede sin lista de areas.
     */
    private function areasForLevel(?AcaSchoolLevel $level): array
    {
        $code = strtolower(trim((string) ($level?->code ?? $level?->name ?? AcaSchoolArea::LEVEL_PRIMARIA)));

        if (! in_array($code, AcaSchoolArea::LEVELS, true)) {
            $code = AcaSchoolArea::LEVEL_PRIMARIA;
        }

        $school = $this->context->currentSchool();
        $areas = [];

        if ($school) {
            $areas = AcaSchoolArea::where('school_id', $school->id)
                ->where('level', $code)
                ->where('status', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->pluck('name')
                ->all();
        }

        return $areas !== [] ? $areas : AcaSchoolArea::standardCnebAreas()[$code];
    }

    /**
     * Ano escolar activo (status=active); fallback al primero registrado.
     */
    private function currentYearId(): ?int
    {
        $year = AcaSchoolYear::where('status', AcaSchoolYear::STATUS_ACTIVE)->orderBy('id')->first()
            ?? AcaSchoolYear::orderBy('id')->first();

        return $year?->id;
    }

    /**
     * Persona vinculada al usuario autenticado.
     */
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

    /**
     * Vista "Mis secciones": tarjetas con las secciones asignadas al docente.
     */
    public function index(Request $request)
    {
        $person = $this->currentPerson();
        $sectionIds = $this->teacherSectionIds($person);

        $school = $this->context->currentSchool();
        $currentYearId = $this->currentYearId();

        $sections = collect();

        if ($sectionIds !== []) {
            $yearId = (int) ($request->get('year_id', $currentYearId));

            $counts = AcaSchoolEnrollment::whereIn('section_id', $sectionIds)
                ->where('year_id', $yearId)
                ->where('status', 'activo')
                ->selectRaw('section_id, COUNT(*) as total')
                ->groupBy('section_id')
                ->pluck('total', 'section_id');

            $sections = AcaSchoolSection::with('grade.level')
                ->whereIn('id', $sectionIds)
                ->orderBy('id')
                ->get()
                ->map(function (AcaSchoolSection $section) use ($counts) {
                    $levelName = $section->grade?->level?->name ?? 'N/D';

                    return [
                        'id' => $section->id,
                        'name' => $section->name,
                        'shift' => $section->shift,
                        'level' => $levelName,
                        'grade' => $section->grade?->name ?? 'N/D',
                        'is_tutor' => $section->tutor_person_id === $this->currentPerson()?->id,
                        'students' => (int) ($counts[$section->id] ?? 0),
                        'scale' => $levelName === 'Inicial' ? 'literal' : 'vigesimal',
                    ];
                });
        }

        return Inertia::render('Academic::School/TeacherGrades/Index', [
            'sections' => $sections,
            'teacherName' => $person?->full_name,
            'schoolName' => $school?->name,
        ]);
    }

    /**
     * Vista de registro de notas de una seccion del docente.
     */
    public function show(Request $request, int $sectionId)
    {
        $person = $this->currentPerson();

        if (! in_array($sectionId, $this->teacherSectionIds($person), true)) {
            abort(403, 'No tienes asignada esta sección.');
        }

        $section = AcaSchoolSection::with('grade.level')->findOrFail($sectionId);
        $levelName = $section->grade?->level?->name ?? 'Primaria';
        $scale = $levelName === 'Inicial' ? 'literal' : 'vigesimal';
        $yearId = $this->currentYearId();

        $enrollments = AcaSchoolEnrollment::with('student.person')
            ->where('section_id', $sectionId)
            ->where('year_id', $yearId)
            ->where('status', 'activo')
            ->orderBy('id')
            ->get();

        $records = AcaSchoolGradeRecord::whereIn('enrollment_id', $enrollments->pluck('id'))
            ->orderBy('id')
            ->get()
            ->groupBy('enrollment_id');

        $students = $enrollments->map(function (AcaSchoolEnrollment $enrollment) use ($records) {
            $personStudent = $enrollment->student?->person;

            $grades = [];
            foreach ($records->get($enrollment->id, collect()) as $record) {
                $grades[$record->area][$record->bimester] = [
                    'scale' => $record->scale_type,
                    'number' => $record->score_number,
                    'letter' => $record->score_letter,
                    'observations' => $record->observations,
                ];
            }

            return [
                'enrollment_id' => $enrollment->id,
                'student_code' => $enrollment->student?->student_code,
                'full_name' => $personStudent?->full_name ?? 'N/D',
                'grades' => $grades,
            ];
        })->values();

        return Inertia::render('Academic::School/TeacherGrades/Show', [
            'section' => [
                'id' => $section->id,
                'name' => $section->name,
                'shift' => $section->shift,
                'level' => $levelName,
                'grade' => $section->grade?->name ?? 'N/D',
                'scale' => $scale,
                'areas' => $this->areasForLevel($section->grade?->level),
                'bimesters' => [1, 2, 3, 4],
            ],
            'students' => $students,
        ]);
    }

    /**
     * Guarda (upsert) las notas de una seccion del docente.
     * Body: { area: string, entries: [{ enrollment_id, bimester, score_number?,
     * score_letter?, observations? }] }
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
            'area' => 'required|string|max:120',
            'entries' => 'required|array|min:1',
            'entries.*.enrollment_id' => 'required|integer|exists:aca_school_enrollments,id',
            'entries.*.bimester' => 'required|integer|between:1,4',
            'entries.*.score_number' => 'nullable|integer|between:0,20',
            'entries.*.score_letter' => 'nullable|string|in:AD,A,B,C',
            'entries.*.observations' => 'nullable|string|max:500',
        ]);

        $section = AcaSchoolSection::with('grade.level')->findOrFail($sectionId);
        $levelName = $section->grade?->level?->name ?? 'Primaria';
        $scale = $levelName === 'Inicial' ? 'literal' : 'vigesimal';
        $yearId = $this->currentYearId();
        $userId = Auth::id();

        $enrollmentIds = AcaSchoolEnrollment::where('section_id', $sectionId)
            ->where('year_id', $yearId)
            ->pluck('id')
            ->all();

        foreach ($validated['entries'] as $entry) {
            if (! in_array((int) $entry['enrollment_id'], $enrollmentIds, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Una de las matrículas no pertenece a esta sección.',
                ], 422);
            }
            if ($scale === 'vigesimal' && $entry['score_number'] === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'En escala vigesimal todas las notas deben ser de 0 a 20.',
                ], 422);
            }
            if ($scale === 'literal' && empty($entry['score_letter'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'En escala literal todas las notas deben ser AD, A, B o C.',
                ], 422);
            }
        }

        try {
            $saved = DB::transaction(function () use ($validated, $sectionId, $scale, $yearId, $userId) {
                foreach ($validated['entries'] as $entry) {
                    AcaSchoolGradeRecord::updateOrCreate(
                        [
                            'enrollment_id' => $entry['enrollment_id'],
                            'area' => $validated['area'],
                            'bimester' => $entry['bimester'],
                        ],
                        [
                            'section_id' => $sectionId,
                            'year_id' => $yearId,
                            'scale_type' => $scale,
                            'score_number' => $scale === 'vigesimal' ? $entry['score_number'] : null,
                            'score_letter' => $scale === 'literal' ? $entry['score_letter'] : null,
                            'observations' => $entry['observations'] ?? null,
                            'updated_by' => $userId,
                        ]
                    );
                }

                return count($validated['entries']);
            });
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "Notas guardadas correctamente ({$saved} registro/s).",
        ]);
    }
}
