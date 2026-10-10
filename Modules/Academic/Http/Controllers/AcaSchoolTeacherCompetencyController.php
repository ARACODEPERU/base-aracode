<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaSchoolArea;
use Modules\Academic\Entities\AcaSchoolCompetency;
use Modules\Academic\Entities\AcaSchoolCompetencyPhrase;
use Modules\Academic\Entities\AcaSchoolEnrollment;
use Modules\Academic\Entities\AcaSchoolGradeCompetency;
use Modules\Academic\Entities\AcaSchoolLevel;
use Modules\Academic\Entities\AcaSchoolSection;
use Modules\Academic\Entities\AcaSchoolYear;
use Modules\Academic\Services\SchoolContextService;

/**
 * Registro de evaluacion por competencias CNEB para el docente (tutor o
 * auxiliar), con la misma dinamica del registro auxiliar SIAGIE: por cada
 * area, competencias con nivel de logro (AD/A/B/C) o nota vigesimal segun
 * la escala configurada, y conclusion descriptiva; 4 bimestres visibles y
 * exportacion a Excel identica al modelo SIAGIE.
 */
class AcaSchoolTeacherCompetencyController extends Controller
{
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
     * Escala de evaluacion del nivel de la seccion (configurable por el
     * colegio; default MINEDU: Inicial literal, resto vigesimal).
     */
    private function scaleForSection(AcaSchoolSection $section): string
    {
        return $section->grade?->level?->evaluationScale() ?? AcaSchoolGradeCompetency::SCALE_VIGESIMAL;
    }

    /**
     * Areas activas del nivel (catalogo del colegio o estandar CNEB).
     */
    private function areasForLevel(AcaSchoolSection $section): array
    {
        $school = $this->context->currentSchool();
        $levelCode = strtolower(trim((string) ($section->grade?->level?->code ?? AcaSchoolArea::LEVEL_PRIMARIA)));
        if (! in_array($levelCode, AcaSchoolArea::LEVELS, true)) {
            $levelCode = AcaSchoolArea::LEVEL_PRIMARIA;
        }

        $areas = AcaSchoolArea::where('school_id', $school?->id)
            ->where('level', $levelCode)
            ->where('status', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($areas->isEmpty()) {
            // Fallback: catalogo CNEB estandar
            return collect(AcaSchoolArea::standardCnebAreas()[$levelCode])
                ->values()
                ->map(fn ($name) => ['id' => null, 'name' => $name])
                ->all();
        }

        return $areas->map(fn ($area) => ['id' => $area->id, 'name' => $area->name])->all();
    }

    /**
     * Precarga el catalogo estandar CNEB de competencias para las areas del
     * nivel que aun no tengan competencias (firstOrCreate, no duplica).
     */
    private function ensureStandardCompetencies(AcaSchoolSection $section, array $areas): void
    {
        $school = $this->context->currentSchool();
        if (! $school) {
            return;
        }

        $levelCode = strtolower(trim((string) ($section->grade?->level?->code ?? AcaSchoolArea::LEVEL_PRIMARIA)));
        $standard = AcaSchoolCompetency::standardCnebCompetencies()[$levelCode] ?? [];

        foreach ($areas as $area) {
            $areaName = $area['name'];
            if (! isset($standard[$areaName])) {
                continue;
            }

            foreach ($standard[$areaName] as $i => $competency) {
                $model = AcaSchoolCompetency::firstOrCreate(
                    [
                        'school_id' => $school->id,
                        'area_name' => $areaName,
                        'code' => $competency['code'],
                    ],
                    [
                        'area_id' => $area['id'],
                        'level' => $levelCode,
                        'name' => $competency['name'],
                        'sort_order' => $i + 1,
                        'status' => true,
                    ]
                );

                // Banco de frases sugeridas generico por competencia
                if ($model->wasRecentlyCreated && $model->phrases()->count() === 0) {
                    foreach (AcaSchoolGradeCompetency::LETTERS as $sort => $letter) {
                        AcaSchoolCompetencyPhrase::create([
                            'competency_id' => $model->id,
                            'score' => $letter,
                            'phrase' => $this->standardPhrase($letter, $competency['name']),
                            'sort_order' => $sort + 1,
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Frases genericas del banco de sugerencias por nivel de logro.
     */
    private function standardPhrase(string $letter, string $competencyName): string
    {
        return match ($letter) {
            AcaSchoolGradeCompetency::LETTER_AD => 'Sobresale en "'.$competencyName.'", demostrando dominio destacado y autonomía en las situaciones trabajadas durante el bimestre.',
            AcaSchoolGradeCompetency::LETTER_A => 'Logra de manera satisfactoria "'.$competencyName.'", cumpliendo con lo esperado para su grado.',
            AcaSchoolGradeCompetency::LETTER_B => 'Está en proceso de lograr "'.$competencyName.'"; requiere acompañamiento en algunas situaciones del bimestre.',
            default => 'Presenta dificultades en "'.$competencyName.'"; necesita acompañamiento permanente del docente y de la familia.',
        };
    }

    /**
     * Grilla de evaluacion por competencias de la seccion: areas con sus
     * competencias, alumnos matriculados, registros guardados y banco de
     * frases sugeridas.
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
        $scale = $this->scaleForSection($section);

        $areas = $this->areasForLevel($section);
        $this->ensureStandardCompetencies($section, $areas);

        $levelCode = strtolower(trim((string) ($section->grade?->level?->code ?? AcaSchoolArea::LEVEL_PRIMARIA)));

        $competencies = AcaSchoolCompetency::where('school_id', $this->context->currentSchoolId())
            ->where('level', $levelCode)
            ->where('status', true)
            ->orderBy('sort_order')
            ->get(['id', 'area_id', 'area_name', 'code', 'name']);

        $competencyByArea = [];
        foreach ($areas as $area) {
            $competencyByArea[$area['name']] = $competencies
                ->filter(fn ($c) => $c->area_name === $area['name'])
                ->map(fn ($c) => ['id' => $c->id, 'code' => $c->code, 'name' => $c->name])
                ->values()
                ->all();
        }

        $enrollments = AcaSchoolEnrollment::with('student.person')
            ->where('section_id', $sectionId)
            ->where('year_id', $yearId)
            ->where('status', AcaSchoolEnrollment::STATUS_ACTIVO)
            ->orderBy('id')
            ->get();

        $records = AcaSchoolGradeCompetency::whereIn('enrollment_id', $enrollments->pluck('id'))
            ->orderBy('id')
            ->get();

        $students = $enrollments->map(function (AcaSchoolEnrollment $enrollment) use ($records) {
            $grades = [];
            foreach ($records->where('enrollment_id', $enrollment->id) as $record) {
                $grades[$record->competency_id][$record->bimester] = [
                    'letter' => $record->score_letter,
                    'number' => $record->score_number,
                    'conclusion' => $record->conclusion,
                ];
            }

            return [
                'enrollment_id' => $enrollment->id,
                'student_code' => $enrollment->student?->student_code,
                'full_name' => $enrollment->student?->person?->full_name ?? 'N/D',
                'grades' => $grades,
            ];
        })->values();

        // Banco de frases sugeridas por competencia y nivel de logro
        $phrases = [];
        $allCompetencyIds = $competencies->pluck('id');
        foreach (AcaSchoolCompetencyPhrase::whereIn('competency_id', $allCompetencyIds)->orderBy('sort_order')->get() as $phrase) {
            $phrases[$phrase->competency_id][$phrase->score][] = $phrase->phrase;
        }

        return Inertia::render('Academic::School/TeacherGrades/Competencies', [
            'section' => [
                'id' => $section->id,
                'name' => $section->name,
                'shift' => $section->shift,
                'level' => $section->grade?->level?->name ?? 'N/D',
                'grade' => $section->grade?->name ?? 'N/D',
                'scale' => $scale,
            ],
            'bimesters' => AcaSchoolGradeCompetency::BIMESTERS,
            'areas' => $areas,
            'competencyByArea' => $competencyByArea,
            'phrases' => $phrases,
            'students' => $students,
            'letters' => AcaSchoolGradeCompetency::LETTERS,
        ]);
    }

    /**
     * Guarda (upsert) las notas por competencia.
     * Body: { entries: [{ enrollment_id, competency_id, bimester,
     * score_letter?, score_number?, conclusion? }] }. Entrada sin nota ni
     * conclusion elimina el registro.
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
            'entries' => 'required|array|min:1',
            'entries.*.enrollment_id' => 'required|integer|exists:aca_school_enrollments,id',
            'entries.*.competency_id' => 'required|integer|exists:aca_school_competencies,id',
            'entries.*.bimester' => 'required|integer|between:1,4',
            'entries.*.score_letter' => 'nullable|in:'.implode(',', AcaSchoolGradeCompetency::LETTERS),
            'entries.*.score_number' => 'nullable|integer|between:0,20',
            'entries.*.conclusion' => 'nullable|string|max:600',
        ]);

        $section = AcaSchoolSection::with('grade.level')->findOrFail($sectionId);
        $year = $this->currentYear();
        if (! $year) {
            return response()->json([
                'success' => false,
                'message' => 'No hay un año escolar activo.',
            ], 422);
        }

        $scale = $this->scaleForSection($section);

        $enrollmentIds = AcaSchoolEnrollment::where('section_id', $sectionId)
            ->where('year_id', $year->id)
            ->pluck('id')
            ->all();

        $schoolCompetencyIds = AcaSchoolCompetency::where('school_id', $section->school_id)
            ->pluck('id')
            ->all();

        foreach ($validated['entries'] as $entry) {
            if (! in_array((int) $entry['enrollment_id'], $enrollmentIds, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Una de las matrículas no pertenece a esta sección.',
                ], 422);
            }
            if (! in_array((int) $entry['competency_id'], $schoolCompetencyIds, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Una de las competencias no pertenece al colegio.',
                ], 422);
            }
            if ($scale === AcaSchoolGradeCompetency::SCALE_LITERAL && empty($entry['score_letter']) && $entry['score_number'] !== null) {
                return response()->json([
                    'success' => false,
                    'message' => 'La escala del nivel es literal: use AD, A, B o C.',
                ], 422);
            }
            if ($scale === AcaSchoolGradeCompetency::SCALE_VIGESIMAL && $entry['score_letter'] !== null && empty($entry['score_number'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'La escala del nivel es vigesimal: use notas de 0 a 20.',
                ], 422);
            }
        }

        $userId = Auth::id();
        $saved = 0;

        foreach ($validated['entries'] as $entry) {
            $hasScore = $scale === AcaSchoolGradeCompetency::SCALE_LITERAL
                ? ! empty($entry['score_letter'])
                : isset($entry['score_number']);
            $hasConclusion = ! empty($entry['conclusion']);

            // Sin nota y sin conclusion: se elimina el registro
            if (! $hasScore && ! $hasConclusion) {
                AcaSchoolGradeCompetency::where('enrollment_id', $entry['enrollment_id'])
                    ->where('competency_id', $entry['competency_id'])
                    ->where('bimester', $entry['bimester'])
                    ->delete();
                continue;
            }

            AcaSchoolGradeCompetency::updateOrCreate(
                [
                    'enrollment_id' => $entry['enrollment_id'],
                    'competency_id' => $entry['competency_id'],
                    'bimester' => $entry['bimester'],
                ],
                [
                    'school_id' => $section->school_id,
                    'year_id' => $year->id,
                    'section_id' => $sectionId,
                    'scale_type' => $scale,
                    'score_letter' => $scale === AcaSchoolGradeCompetency::SCALE_LITERAL ? ($entry['score_letter'] ?? null) : null,
                    'score_number' => $scale === AcaSchoolGradeCompetency::SCALE_VIGESIMAL ? ($entry['score_number'] ?? null) : null,
                    'conclusion' => $entry['conclusion'] ?? null,
                    'user_id_registers' => $userId,
                ]
            );
            $saved++;
        }

        return response()->json([
            'success' => true,
            'message' => "Notas guardadas correctamente ({$saved} registro/s).",
        ]);
    }

    /**
     * Exporta el registro auxiliar de evaluacion en Excel, con la misma
     * estructura del archivo SIAGIE de ejemplo (Generalidades, Parametros y
     * una hoja por area con NL + conclusion descriptiva y leyenda).
     */
    public function export(Request $request, int $sectionId)
    {
        $person = $this->currentPerson();

        if (! in_array($sectionId, $this->teacherSectionIds($person), true)) {
            abort(403, 'No tienes asignada esta sección.');
        }

        $bimester = (int) ($request->query('bimester', 1));
        if (! in_array($bimester, AcaSchoolGradeCompetency::BIMESTERS, true)) {
            $bimester = 1;
        }

        $export = new \Modules\Academic\Services\CompetencyGradesExcelExport($sectionId, $bimester);

        return $export->download();
    }
}
