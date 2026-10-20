<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaSchoolPeriod;
use Modules\Academic\Entities\AcaSchoolYear;
use Modules\Academic\Services\SchoolContextService;

class AcaSchoolPeriodController extends Controller
{
    public function __construct(private SchoolContextService $context)
    {
    }

    public function index(Request $request)
    {
        $school = $this->context->currentSchool();
        $activeYear = $school
            ? AcaSchoolYear::where('school_id', $school->id)
                ->where('status', AcaSchoolYear::STATUS_ACTIVE)
                ->first(['id', 'year'])
            : null;

        $periods = collect();
        if ($school && $activeYear) {
            $periods = AcaSchoolPeriod::where('school_id', $school->id)
                ->where('year_id', $activeYear->id)
                ->when($request->query('search'), function ($query, $search) {
                    $query->where('name', 'like', '%'.$search.'%');
                })
                ->orderBy('number')
                ->paginate(20)
                ->onEachSide(2)
                ->withQueryString();
        }

        return Inertia::render('Academic::School/Periods/List', [
            'periods' => $periods,
            'school' => $school,
            'activeYear' => $activeYear,
            'filters' => $request->query('search') ? ['search' => $request->query('search')] : [],
        ]);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'year_id' => 'required|integer|exists:aca_school_years,id',
            'organization_type' => 'required|in:bimestral,trimestral,cuatrimestral,semestral,personalizada',
            'periods' => 'required|array|min:1',
            'periods.*.name' => 'required|string|max:100',
            'periods.*.number' => 'required|integer|min:1|max:255',
            'periods.*.start_date' => 'required|date',
            'periods.*.end_date' => 'required|date|after_or_equal:periods.*.start_date',
        ]);

        $school = $this->context->currentSchool();
        if (! $school) {
            return back()->withErrors(['year_id' => 'No hay un colegio configurado. Registre uno primero.']);
        }

        $year = AcaSchoolYear::find($request->get('year_id'));
        if (! $year || $year->school_id !== $school->id) {
            return back()->withErrors(['year_id' => 'El año escolar no pertenece al colegio actual.']);
        }

        $periodsData = $request->get('periods');
        $type = $request->get('organization_type');

        $exists = AcaSchoolPeriod::where('school_id', $school->id)
            ->where('year_id', $year->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['periods' => 'Ya existen períodos registrados para el año '.$year->year.'. Edite o elimine los existentes.']);
        }

        // Verificar duplicados dentro del propio envío
        $numbers = array_column($periodsData, 'number');
        $names = array_map(fn ($p) => mb_strtolower(trim($p['name'])), $periodsData);
        if (count($numbers) !== count(array_unique($numbers))) {
            return back()->withErrors(['periods' => 'Hay números de período duplicados en el envío.']);
        }
        if (count($names) !== count(array_unique($names))) {
            return back()->withErrors(['periods' => 'Hay nombres de período duplicados en el envío.']);
        }

        try {
            DB::transaction(function () use ($school, $year, $type, $periodsData) {
                foreach ($periodsData as $p) {
                    AcaSchoolPeriod::create([
                        'school_id' => $school->id,
                        'year_id' => $year->id,
                        'organization_type' => $type,
                        'name' => trim($p['name']),
                        'number' => (int) $p['number'],
                        'start_date' => $p['start_date'],
                        'end_date' => $p['end_date'],
                        'status' => true,
                    ]);
                }
            });
        } catch (\Illuminate\Database\QueryException $e) {
            $msg = str_contains($e->getMessage(), 'Duplicate')
                ? 'Hay un período con nombre o número duplicado. Verifique los datos.'
                : 'No se pudieron registrar los períodos: '.$e->getMessage();
            return back()->withErrors(['periods' => $msg]);
        }

        return redirect()->route('aca_school_periods_list')
            ->with('message', __('Períodos académicos registrados con éxito'));
    }

    public function update(Request $request, int $id)
    {
        $this->validate($request, [
            'name' => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'observations' => 'nullable|max:1000',
        ]);

        $period = AcaSchoolPeriod::findOrFail($id);

        $duplicate = AcaSchoolPeriod::where('school_id', $period->school_id)
            ->where('year_id', $period->year_id)
            ->where('id', '!=', $period->id)
            ->where('name', trim($request->get('name')))
            ->exists();

        if ($duplicate) {
            return back()->withErrors(['name' => 'Ya existe otro período con ese nombre en este año.']);
        }

        $period->update([
            'name' => trim($request->get('name')),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
            'observations' => $request->get('observations'),
        ]);

        return redirect()->route('aca_school_periods_list')
            ->with('message', __('Período actualizado con éxito'));
    }

    public function destroy(int $id)
    {
        $period = AcaSchoolPeriod::findOrFail($id);

        if ($this->hasRelatedRecords($period)) {
            return response()->json([
                'success' => false,
                'message' => 'El período tiene registros asociados (notas, asistencias u otros), no puede eliminarse.',
            ]);
        }

        $period->delete();

        return response()->json(['success' => true, 'message' => 'Período eliminado correctamente']);
    }

    /**
     * Verifica si el período tiene registros asociados en tablas que lo referencien.
     * Actualmente aca_school_periods no tiene tablas hijas con FK period_id,
     * por lo que siempre retorna false. El patrón queda preparado para cuando
     * se integre el período con notas, asistencias, etc.
     */
    private function hasRelatedRecords(AcaSchoolPeriod $period): bool
    {
        // Ejemplo futuro: return $period->gradeRecords()->exists();
        return false;
    }
}
