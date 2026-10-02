<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaSchool;
use Modules\Academic\Entities\AcaSchoolEnrollment;
use Modules\Academic\Entities\AcaSchoolGrade;
use Modules\Academic\Entities\AcaSchoolLevel;
use Modules\Academic\Services\SchoolContextService;
use Modules\Academic\Entities\AcaSchoolYear;

class AcaSchoolYearController extends Controller
{
    public function __construct(private SchoolContextService $context)
    {
    }

    public function index(Request $request)
    {
        $school = $this->context->currentSchool();

        $years = collect();
        if ($school) {
            $years = AcaSchoolYear::where('school_id', $school->id)
                ->when($request->query('search'), function ($query, $search) {
                    $query->where('year', 'like', '%'.$search.'%');
                })
                ->withCount(['enrollments' => function ($q) {
                    $q->where('status', AcaSchoolEnrollment::STATUS_ACTIVO);
                }])
                ->orderByDesc('year')
                ->paginate(20)
                ->onEachSide(2)
                ->withQueryString();
        }

        return Inertia::render('Academic::School/Years/List', [
            'years' => $years,
            'school' => $school,
            'filters' => $request->query('search') ? ['search' => $request->query('search')] : [],
        ]);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'year' => 'required|integer|digits:4|min:2000|max:2100',
            'observations' => 'nullable|max:1000',
        ]);

        $school = $this->context->currentSchool();

        if (! $school) {
            return back()->withErrors(['year' => 'No hay un colegio configurado. Registre uno primero.']);
        }

        $exists = AcaSchoolYear::where('school_id', $school->id)
            ->where('year', $request->get('year'))
            ->exists();

        if ($exists) {
            return back()->withErrors(['year' => 'El año escolar '.$request->get('year').' ya existe para este colegio.']);
        }

        AcaSchoolYear::create([
            'school_id' => $school->id,
            'year' => $request->get('year'),
            'status' => AcaSchoolYear::STATUS_ANNOUNCED,
            'observations' => $request->get('observations'),
        ]);

        return redirect()->route('aca_school_years_list')
            ->with('message', __('Año escolar creado con éxito'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|integer',
            'observations' => 'nullable|max:1000',
        ]);

        $year = AcaSchoolYear::findOrFail($request->get('id'));
        $year->update([
            'observations' => $request->get('observations'),
        ]);

        return redirect()->route('aca_school_years_list')
            ->with('message', __('Año escolar actualizado con éxito'));
    }

    /**
     * Activa el año escolar y anuncia/cierra los demas del colegio.
     * Si el colegio no tiene estructura academica (niveles/grados/secciones),
     * la precarga con la estructura estandar de Peru.
     */
    public function activate(Request $request, int $id)
    {
        $year = AcaSchoolYear::findOrFail($id);

        try {
            DB::transaction(function () use ($year) {
                AcaSchoolYear::where('school_id', $year->school_id)
                    ->where('id', '!=', $year->id)
                    ->where('status', AcaSchoolYear::STATUS_ACTIVE)
                    ->update(['status' => AcaSchoolYear::STATUS_FINISHED]);

                $year->update(['status' => AcaSchoolYear::STATUS_ACTIVE]);

                $hasStructure = AcaSchoolLevel::where('school_id', $year->school_id)->exists();

                if (! $hasStructure) {
                    $this->seedStandardStructure($year->school_id);
                }
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['year' => 'No se pudo activar el año: '.$e->getMessage()]);
        }

        return redirect()->route('aca_school_years_list')
            ->with('message', __('Año escolar '.$year->year.' activado'));
    }

    public function close(Request $request, int $id)
    {
        $year = AcaSchoolYear::findOrFail($id);
        $year->update(['status' => AcaSchoolYear::STATUS_FINISHED]);

        return redirect()->route('aca_school_years_list')
            ->with('message', __('Año escolar '.$year->year.' cerrado'));
    }

    /**
     * Crea los niveles y grados estandar (Inicial 3-4-5, Primaria 1°-6°,
     * Secundaria 1°-5°) para el colegio. Las secciones se crean aparte.
     */
    private function seedStandardStructure(int $schoolId): void
    {
        foreach (AcaSchoolLevel::standardPeruStructure() as $i => $levelData) {
            $level = AcaSchoolLevel::firstOrCreate(
                ['school_id' => $schoolId, 'code' => $levelData['code']],
                ['name' => $levelData['name'], 'sort_order' => $i + 1, 'status' => true]
            );

            foreach ($levelData['grades'] as $j => $gradeName) {
                AcaSchoolGrade::firstOrCreate(
                    ['school_id' => $schoolId, 'level_id' => $level->id, 'name' => $gradeName],
                    ['sort_order' => $j + 1, 'status' => true]
                );
            }
        }
    }
}
