<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaSchoolEnrollment;
use Modules\Academic\Entities\AcaSchoolGrade;
use Modules\Academic\Entities\AcaSchoolLevel;
use Modules\Academic\Entities\AcaSchoolSection;
use Modules\Academic\Entities\AcaSchoolStudent;
use Modules\Academic\Entities\AcaSchoolYear;
use Modules\Academic\Services\SchoolContextService;

class AcaSchoolEnrollmentController extends Controller
{
    public function __construct(private SchoolContextService $context)
    {
    }

    /**
     * Listado de matriculas filtrable por año/nivel/grado/seccion/estado.
     */
    public function index(Request $request)
    {
        $school = $this->context->currentSchool();

        $data = $this->filterOptions($school);

        $enrollments = collect();
        if ($school) {
            $enrollments = AcaSchoolEnrollment::query()
                ->where('aca_school_enrollments.school_id', $school->id)
                ->join('aca_school_students as ss', 'ss.id', '=', 'aca_school_enrollments.student_id')
                ->join('people as p', 'p.id', '=', 'ss.person_id')
                ->join('aca_school_sections as sec', 'sec.id', '=', 'aca_school_enrollments.section_id')
                ->join('aca_school_grades as g', 'g.id', '=', 'sec.grade_id')
                ->join('aca_school_levels as lv', 'lv.id', '=', 'g.level_id')
                ->join('aca_school_years as y', 'y.id', '=', 'aca_school_enrollments.year_id')
                ->when($request->query('year_id'), fn ($q, $v) => $q->where('aca_school_enrollments.year_id', $v))
                ->when($request->query('level_id'), fn ($q, $v) => $q->where('lv.id', $v))
                ->when($request->query('grade_id'), fn ($q, $v) => $q->where('g.id', $v))
                ->when($request->query('section_id'), fn ($q, $v) => $q->where('sec.id', $v))
                ->when($request->query('status'), fn ($q, $v) => $q->where('aca_school_enrollments.status', $v))
                ->when($request->query('search'), function ($q, $search) {
                    $q->where(function ($q2) use ($search) {
                        $q2->where('p.full_name', 'like', '%'.$search.'%')
                            ->orWhere('p.number', 'like', '%'.$search.'%')
                            ->orWhere('ss.student_code', 'like', '%'.$search.'%');
                    });
                })
                ->orderByDesc('aca_school_enrollments.id')
                ->select(
                    'aca_school_enrollments.*',
                    'p.full_name as student_name',
                    'p.number as student_document',
                    'ss.student_code',
                    'sec.name as section_name',
                    'sec.shift as section_shift',
                    'g.name as grade_name',
                    'lv.name as level_name',
                    'y.year as year_number'
                )
                ->paginate(20)
                ->onEachSide(2)
                ->withQueryString();
        }

        return Inertia::render('Academic::School/Enrollments/List', [
            'enrollments' => $enrollments,
            'years' => $data['years'],
            'levels' => $data['levels'],
            'grades' => $data['grades'],
            'sections' => $data['sections'],
            'typeLabels' => AcaSchoolEnrollment::typeLabels(),
            'statusLabels' => AcaSchoolEnrollment::statusLabels(),
            'filters' => $request->only(['year_id', 'level_id', 'grade_id', 'section_id', 'status', 'search']),
        ]);
    }

    /**
     * Formulario de matricula: cascada nivel -> grado -> seccion.
     */
    public function create(Request $request)
    {
        $school = $this->context->currentSchool();

        if (! $school) {
            return redirect()->route('aca_school_years_list')
                ->with('error', 'No hay un colegio configurado.');
        }

        $data = $this->filterOptions($school);

        return Inertia::render('Academic::School/Enrollments/Create', [
            'years' => $data['years'],
            'levels' => $data['levels'],
            'typeLabels' => AcaSchoolEnrollment::typeLabels(),
            'preselect' => [
                'year_id' => $request->query('year_id'),
                'student_id' => $request->query('student_id'),
            ],
        ]);
    }

    /* ---------------- Cascadas para el formulario ---------------- */

    public function gradesByLevel(Request $request)
    {
        return response()->json(
            AcaSchoolGrade::where('level_id', $request->get('level_id'))
                ->where('status', true)
                ->orderBy('sort_order')
                ->get(['id', 'name'])
        );
    }

    public function sectionsByGrade(Request $request)
    {
        $school = $this->context->currentSchool();
        $yearId = (int) $request->get('year_id');

        $sections = AcaSchoolSection::where('aca_school_sections.grade_id', $request->get('grade_id'))
            ->where('aca_school_sections.status', true)
            ->select('aca_school_sections.*')
            ->get()
            ->map(function ($section) use ($yearId) {
                $taken = $yearId
                    ? AcaSchoolEnrollment::where('section_id', $section->id)
                        ->where('year_id', $yearId)
                        ->where('status', AcaSchoolEnrollment::STATUS_ACTIVO)
                        ->count()
                    : 0;

                return [
                    'id' => $section->id,
                    'name' => $section->name,
                    'shift' => $section->shift,
                    'shift_label' => AcaSchoolSection::shiftLabels()[$section->shift] ?? $section->shift,
                    'capacity' => $section->capacity,
                    'taken' => $taken,
                    'available' => max(0, $section->capacity - $taken),
                ];
            })
            ->values();

        return response()->json($sections);
    }

    public function searchStudents(Request $request)
    {
        $school = $this->context->currentSchool();

        $search = $request->get('search');

        $students = AcaSchoolStudent::query()
            ->where('aca_school_students.school_id', $school->id)
            ->where('aca_school_students.status', true)
            ->join('people', 'people.id', '=', 'aca_school_students.person_id')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('people.full_name', 'like', '%'.$search.'%')
                        ->orWhere('people.number', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('people.full_name')
            ->limit(20)
            ->get([
                'aca_school_students.id',
                'aca_school_students.student_code',
                'people.full_name',
                'people.number',
            ]);

        return response()->json($students);
    }

    public function searchGuardians(Request $request)
    {
        $search = $request->get('search');

        $people = Person::query()
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('full_name', 'like', '%'.$search.'%')
                        ->orWhere('number', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('full_name')
            ->limit(20)
            ->get(['id', 'full_name', 'number', 'telephone']);

        return response()->json($people);
    }

    /**
     * Registra la matricula validando vacantes y unicidad por año.
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'year_id' => 'required|integer|exists:aca_school_years,id',
            'student_id' => 'required|integer|exists:aca_school_students,id',
            'section_id' => 'required|integer|exists:aca_school_sections,id',
            'type' => 'required|in:nueva,promovida,repitente,traslado,reingreso',
            'enrollment_date' => 'required|date',
            'guardian_person_id' => 'nullable|integer|exists:people,id',
            'guardian_relationship' => 'nullable|max:60',
            'guardian_phone' => 'nullable|max:30',
            'observations' => 'nullable|max:1000',
        ]);

        $school = $this->context->currentSchool();

        try {
            DB::transaction(function () use ($request, $school) {
                $year = AcaSchoolYear::lockForUpdate()->findOrFail($request->get('year_id'));
                $student = AcaSchoolStudent::findOrFail($request->get('student_id'));
                $section = AcaSchoolSection::findOrFail($request->get('section_id'));

                if ($year->school_id !== $school->id || $student->school_id !== $school->id) {
                    throw new \Exception('El alumno o el año no pertenecen al colegio activo.');
                }

                $already = AcaSchoolEnrollment::where('year_id', $year->id)
                    ->where('student_id', $student->id)
                    ->exists();

                if ($already) {
                    throw new \Exception('El alumno ya tiene una matrícula registrada en el año '.$year->year.'.');
                }

                $taken = AcaSchoolEnrollment::where('section_id', $section->id)
                    ->where('year_id', $year->id)
                    ->where('status', AcaSchoolEnrollment::STATUS_ACTIVO)
                    ->lockForUpdate()
                    ->count();

                if ($taken >= $section->capacity) {
                    throw new \Exception('La sección '.$section->name.' no tiene vacantes disponibles.');
                }

                AcaSchoolEnrollment::create([
                    'school_id' => $school->id,
                    'year_id' => $year->id,
                    'student_id' => $student->id,
                    'section_id' => $section->id,
                    'type' => $request->get('type'),
                    'status' => AcaSchoolEnrollment::STATUS_ACTIVO,
                    'enrollment_date' => $request->get('enrollment_date'),
                    'guardian_person_id' => $request->get('guardian_person_id'),
                    'guardian_relationship' => $request->get('guardian_relationship'),
                    'guardian_phone' => $request->get('guardian_phone'),
                    'observations' => $request->get('observations'),
                ]);
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['student_id' => $e->getMessage()]);
        }

        return redirect()->route('aca_school_enrollments_list', ['year_id' => $request->get('year_id')])
            ->with('message', __('Matrícula registrada con éxito'));
    }

    /**
     * Cambios de estado de la matricula: retirar, traslado, anular.
     * Los estados no activos liberan la vacante de la seccion.
     */
    public function changeStatus(Request $request, int $id)
    {
        $this->validate($request, [
            'status' => 'required|in:activo,retirado,traslado_salida,anulado',
            'observations' => 'nullable|max:1000',
        ]);

        $enrollment = AcaSchoolEnrollment::findOrFail($id);

        if ($enrollment->status === AcaSchoolEnrollment::STATUS_ANULADO) {
            return back()->withErrors(['status' => 'Una matrícula anulada no puede cambiar de estado.']);
        }

        $enrollment->update([
            'status' => $request->get('status'),
            'observations' => $request->get('observations') ?: $enrollment->observations,
        ]);

        $labels = AcaSchoolEnrollment::statusLabels();

        return redirect()->route('aca_school_enrollments_list')
            ->with('message', __('Matrícula marcada como: '.$labels[$request->get('status')]));
    }

    /**
     * Edicion ligera de la matricula (seccion, tipo, apoderado, observacion).
     * Valida vacantes solo si cambia la seccion.
     */
    public function update(Request $request, int $id)
    {
        $this->validate($request, [
            'section_id' => 'required|integer|exists:aca_school_sections,id',
            'type' => 'required|in:nueva,promovida,repitente,traslado,reingreso',
            'guardian_person_id' => 'nullable|integer|exists:people,id',
            'guardian_relationship' => 'nullable|max:60',
            'guardian_phone' => 'nullable|max:30',
            'observations' => 'nullable|max:1000',
        ]);

        $enrollment = AcaSchoolEnrollment::findOrFail($id);

        try {
            DB::transaction(function () use ($request, $enrollment) {
                if ((int) $enrollment->section_id !== (int) $request->get('section_id')) {
                    $section = AcaSchoolSection::findOrFail($request->get('section_id'));

                    $taken = AcaSchoolEnrollment::where('section_id', $section->id)
                        ->where('year_id', $enrollment->year_id)
                        ->where('status', AcaSchoolEnrollment::STATUS_ACTIVO)
                        ->where('id', '!=', $enrollment->id)
                        ->lockForUpdate()
                        ->count();

                    if ($taken >= $section->capacity) {
                        throw new \Exception('La sección '.$section->name.' no tiene vacantes disponibles.');
                    }
                }

                $enrollment->update([
                    'section_id' => $request->get('section_id'),
                    'type' => $request->get('type'),
                    'guardian_person_id' => $request->get('guardian_person_id'),
                    'guardian_relationship' => $request->get('guardian_relationship'),
                    'guardian_phone' => $request->get('guardian_phone'),
                    'observations' => $request->get('observations') ?: $enrollment->observations,
                ]);
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['section_id' => $e->getMessage()]);
        }

        return redirect()->route('aca_school_enrollments_list')
            ->with('message', __('Matrícula actualizada con éxito'));
    }

    /**
     * Opciones de los filtros en cascada (solo del colegio activo).
     */
    private function filterOptions($school): array
    {
        if (! $school) {
            return ['years' => [], 'levels' => [], 'grades' => [], 'sections' => []];
        }

        return [
            'years' => AcaSchoolYear::where('school_id', $school->id)->orderByDesc('year')->get(['id', 'year', 'status']),
            'levels' => AcaSchoolLevel::where('school_id', $school->id)->where('status', true)->orderBy('sort_order')->get(['id', 'name']),
            'grades' => AcaSchoolGrade::whereIn('level_id', fn ($q) => $q->select('id')->from('aca_school_levels')->where('school_id', $school->id))
                ->orderBy('sort_order')->get(['id', 'level_id', 'name']),
            'sections' => AcaSchoolSection::where('school_id', $school->id)->where('status', true)->orderBy('name')->get(['id', 'grade_id', 'name', 'shift']),
        ];
    }
}
