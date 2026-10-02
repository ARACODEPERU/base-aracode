<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaSchoolEnrollment;
use Modules\Academic\Entities\AcaSchoolGrade;
use Modules\Academic\Entities\AcaSchoolLevel;
use Modules\Academic\Entities\AcaSchoolSection;
use Modules\Academic\Entities\AcaTeacher;
use Modules\Academic\Services\SchoolContextService;

class AcaSchoolStructureController extends Controller
{
    public function __construct(private SchoolContextService $context)
    {
    }

    /**
     * Respuesta unificada para store/update de la estructura. Los formularios
     * se envian por axios (XHR): devolver redirect() hace que axios siga el
     * 302 con el mismo metodo (PUT/POST) contra la ruta indice y provoque
     * 405 MethodNotAllowed. Para XHR respondemos JSON; el redirect queda
     * solo como fallback para submits no-XHR.
     */
    private function response(Request $request, string $message, array $errors = [])
    {
        if ($errors !== []) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => reset($errors), 'errors' => $errors], 422);
            }

            return back()->withErrors($errors);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->route('aca_school_structure')->with('message', $message);
    }

    /**
     * Panel unico: arbol nivel -> grado -> seccion con vacantes ocupadas.
     */
    public function index()
    {
        $school = $this->context->currentSchool();

        if (! $school) {
            return Inertia::render('Academic::School/Structure/Index', [
                'tree' => [],
                'shifts' => [],
            ]);
        }

        $activeTaken = AcaSchoolEnrollment::selectRaw('section_id, COUNT(*) as total')
            ->where('school_id', $school->id)
            ->where('status', AcaSchoolEnrollment::STATUS_ACTIVO)
            ->groupBy('section_id')
            ->pluck('total', 'section_id');

        $levels = AcaSchoolLevel::where('school_id', $school->id)
            ->with([
                'grades.sections' => function ($q) {
                    $q->withCount(['enrollments as enrollments_active_count' => function ($q2) {
                        $q2->where('status', AcaSchoolEnrollment::STATUS_ACTIVO);
                    }]);
                },
                'grades.sections.tutor',
                'grades.sections.auxiliary',
            ])
            ->orderBy('sort_order')
            ->get();

        $tree = $levels->map(function ($level) use ($activeTaken) {
            return [
                'id' => $level->id,
                'code' => $level->code,
                'name' => $level->name,
                'sort_order' => $level->sort_order,
                'status' => (bool) $level->status,
                'grades' => $level->grades->map(function ($grade) use ($activeTaken) {
                    return [
                        'id' => $grade->id,
                        'name' => $grade->name,
                        'sort_order' => $grade->sort_order,
                        'status' => (bool) $grade->status,
                        'sections' => $grade->sections->map(function ($section) use ($activeTaken) {
                            return [
                                'id' => $section->id,
                                'grade_id' => $section->grade_id,
                                'name' => $section->name,
                                'capacity' => $section->capacity,
                                'shift' => $section->shift,
                                'shift_label' => AcaSchoolSection::shiftLabels()[$section->shift] ?? $section->shift,
                                'tutor_person_id' => $section->tutor_person_id,
                                'tutor_name' => $section->tutor?->full_name,
                                'auxiliary_person_id' => $section->auxiliary_person_id,
                                'auxiliary_name' => $section->auxiliary?->full_name,
                                'status' => (bool) $section->status,
                                'taken' => (int) ($activeTaken[$section->id] ?? 0),
                                'available' => max(0, $section->capacity - (int) ($activeTaken[$section->id] ?? 0)),
                            ];
                        })->values(),
                    ];
                })->values(),
            ];
        })->values();

        return Inertia::render('Academic::School/Structure/Index', [
            'tree' => $tree,
            'shifts' => AcaSchoolSection::shiftLabels(),
        ]);
    }

    /* ------------------------- NIVELES ------------------------- */

    public function storeLevel(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:100',
            'code' => 'nullable|max:20',
        ]);

        $school = $this->context->currentSchool();

        if (! $school) {
            return back()->withErrors(['name' => 'No hay un colegio configurado.']);
        }

        $code = $request->get('code') ?: \Str::slug($request->get('name'), '_');

        $exists = AcaSchoolLevel::where('school_id', $school->id)->where('code', $code)->exists();
        if ($exists) {
            return back()->withErrors(['name' => 'Ya existe un nivel con ese código.']);
        }

        AcaSchoolLevel::create([
            'school_id' => $school->id,
            'code' => $code,
            'name' => $request->get('name'),
            'sort_order' => ((int) AcaSchoolLevel::where('school_id', $school->id)->max('sort_order')) + 1,
            'status' => true,
        ]);

        return $this->response($request, __('Nivel creado con éxito'));
    }

    public function updateLevel(Request $request, int $id)
    {
        $this->validate($request, ['name' => 'required|max:100']);

        AcaSchoolLevel::findOrFail($id)->update([
            'name' => $request->get('name'),
            'status' => $request->boolean('status'),
        ]);

        return $this->response($request, __('Nivel actualizado con éxito'));
    }

    public function destroyLevel(int $id)
    {
        $level = AcaSchoolLevel::findOrFail($id);

        if ($level->grades()->whereHas('sections.enrollments')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'El nivel tiene matrículas registradas, no puede eliminarse.',
            ]);
        }

        $level->delete();

        return response()->json(['success' => true, 'message' => 'Nivel eliminado correctamente']);
    }

    /* ------------------------- GRADOS ------------------------- */

    public function storeGrade(Request $request)
    {
        $this->validate($request, ['level_id' => 'required|integer', 'name' => 'required|max:100']);

        AcaSchoolGrade::create([
            'school_id' => $this->context->currentSchoolId(),
            'level_id' => $request->get('level_id'),
            'name' => $request->get('name'),
            'sort_order' => ((int) AcaSchoolGrade::where('level_id', $request->get('level_id'))->max('sort_order')) + 1,
            'status' => true,
        ]);

        return $this->response($request, __('Grado creado con éxito'));
    }

    public function updateGrade(Request $request, int $id)
    {
        $this->validate($request, ['name' => 'required|max:100']);

        AcaSchoolGrade::findOrFail($id)->update([
            'name' => $request->get('name'),
            'status' => $request->boolean('status'),
        ]);

        return $this->response($request, __('Grado actualizado con éxito'));
    }

    public function destroyGrade(int $id)
    {
        $grade = AcaSchoolGrade::findOrFail($id);

        if ($grade->sections()->whereHas('enrollments')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'El grado tiene matrículas registradas, no puede eliminarse.',
            ]);
        }

        $grade->delete();

        return response()->json(['success' => true, 'message' => 'Grado eliminado correctamente']);
    }

    /* ------------------------- SECCIONES ------------------------- */

    public function storeSection(Request $request)
    {
        $this->validate($request, [
            'grade_id' => 'required|integer',
            'name' => 'required|max:20',
            'capacity' => 'required|integer|min:1|max:80',
            'shift' => 'required|in:manana,tarde,noche,jornada',
            'tutor_person_id' => 'nullable|integer|exists:people,id',
            'auxiliary_person_id' => 'nullable|integer|exists:people,id',
        ]);

        AcaSchoolSection::create([
            'school_id' => $this->context->currentSchoolId(),
            'grade_id' => $request->get('grade_id'),
            'name' => $request->get('name'),
            'capacity' => $request->get('capacity'),
            'shift' => $request->get('shift'),
            'tutor_person_id' => $request->get('tutor_person_id'),
            'auxiliary_person_id' => $request->get('auxiliary_person_id'),
            'status' => true,
        ]);

        return $this->response($request, __('Sección creada con éxito'));
    }

    public function updateSection(Request $request, int $id)
    {
        $this->validate($request, [
            'name' => 'required|max:20',
            'capacity' => 'required|integer|min:1|max:80',
            'shift' => 'required|in:manana,tarde,noche,jornada',
            'tutor_person_id' => 'nullable|integer|exists:people,id',
            'auxiliary_person_id' => 'nullable|integer|exists:people,id',
        ]);

        $section = AcaSchoolSection::findOrFail($id);

        if ($request->get('capacity') < $section->enrollments()->where('status', AcaSchoolEnrollment::STATUS_ACTIVO)->count()) {
            return $this->response($request, null, ['capacity' => 'La capacidad no puede ser menor a las matrículas activas de la sección.']);
        }

        $section->update([
            'name' => $request->get('name'),
            'capacity' => $request->get('capacity'),
            'shift' => $request->get('shift'),
            'tutor_person_id' => $request->get('tutor_person_id'),
            'auxiliary_person_id' => $request->get('auxiliary_person_id'),
            'status' => $request->boolean('status'),
        ]);

        return $this->response($request, __('Sección actualizada con éxito'));
    }

    public function destroySection(int $id)
    {
        $section = AcaSchoolSection::findOrFail($id);

        if ($section->enrollments()->where('status', AcaSchoolEnrollment::STATUS_ACTIVO)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'La sección tiene matrículas activas, no puede eliminarse.',
            ]);
        }

        $section->delete();

        return response()->json(['success' => true, 'message' => 'Sección eliminada correctamente']);
    }

    /**
     * Buscador de docentes: personas registradas en aca_teachers.
     */
    public function searchTeachers(Request $request)
    {
        $search = $request->get('search');

        $teachers = AcaTeacher::query()
            ->join('people', 'people.id', '=', 'aca_teachers.person_id')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('people.full_name', 'like', '%'.$search.'%')
                        ->orWhere('people.number', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('people.full_name')
            ->limit(20)
            ->get([
                'people.id',
                'people.full_name',
                'people.number',
                'aca_teachers.teacher_code',
            ]);

        return response()->json($teachers);
    }
}
