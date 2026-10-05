<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaSchoolFee;
use Modules\Academic\Entities\AcaSchoolFeeType;
use Modules\Academic\Entities\AcaSchoolGrade;
use Modules\Academic\Entities\AcaSchoolLevel;
use Modules\Academic\Entities\AcaSchoolSection;
use Modules\Academic\Entities\AcaSchoolYear;
use Modules\Academic\Services\SchoolContextService;

class AcaSchoolFeeController extends Controller
{
    public function __construct(private SchoolContextService $context)
    {
    }

    /**
     * Mantenedor de tarifas: monto por concepto con alcance jerarquico
     * opcional (nivel > grado > seccion) dentro del año escolar.
     */
    public function index(Request $request)
    {
        $school = $this->context->currentSchool();

        if (! $school) {
            return redirect()->route('aca_school_years_list')
                ->with('error', 'No hay un colegio configurado.');
        }

        // Conceptos base del colegio (firstOrCreate, idempotente).
        AcaSchoolFeeType::ensureDefaults();

        $years = AcaSchoolYear::where('school_id', $school->id)->orderByDesc('year')->get(['id', 'year', 'status']);
        $levels = AcaSchoolLevel::where('school_id', $school->id)->where('status', true)->orderBy('sort_order')->get(['id', 'name']);
        $grades = AcaSchoolGrade::whereIn('level_id', $levels->pluck('id'))->orderBy('sort_order')->get(['id', 'level_id', 'name']);
        $sections = AcaSchoolSection::whereIn('grade_id', $grades->pluck('id'))->where('status', true)->orderBy('name')->get(['id', 'grade_id', 'name', 'shift']);
        $feeTypes = AcaSchoolFeeType::orderBy('id')->get();

        $yearId = (int) ($request->query('year_id') ?: ($years->firstWhere('status', 'active')->id ?? ($years->first()->id ?? 0)));

        $fees = AcaSchoolFee::query()
            ->where('year_id', $yearId)
            ->with(['feeType:id,code,name,is_recurring', 'level:id,name', 'grade:id,name', 'section:id,name,shift'])
            ->orderBy('fee_type_id')
            ->orderByRaw('(section_id IS NOT NULL) DESC, (grade_id IS NOT NULL) DESC, (level_id IS NOT NULL) DESC')
            ->get();

        return Inertia::render('Academic::School/Fees/Index', [
            'schoolName' => $school->name,
            'years' => $years,
            'levels' => $levels,
            'grades' => $grades,
            'sections' => $sections,
            'feeTypes' => $feeTypes,
            'fees' => $fees,
            'filters' => ['year_id' => $yearId],
        ]);
    }

    public function store(Request $request)
    {
        return $this->save($request, null);
    }

    public function update(Request $request, int $id)
    {
        return $this->save($request, $id);
    }

    /**
     * Los formularios se envian por axios: JSON siempre (evita el 405 del
     * redirect seguido por axios con el mismo metodo).
     */
    public function destroy(int $id)
    {
        $fee = AcaSchoolFee::findOrFail($id);
        $fee->delete();

        return response()->json(['success' => true, 'message' => 'Tarifa eliminada correctamente']);
    }

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
        return response()->json(
            AcaSchoolSection::where('grade_id', $request->get('grade_id'))
                ->where('status', true)
                ->orderBy('name')
                ->get(['id', 'name', 'shift'])
        );
    }

    private function save(Request $request, ?int $id)
    {
        $validated = $this->validate($request, [
            'year_id' => 'required|integer|exists:aca_school_years,id',
            'fee_type_id' => 'required|integer|exists:aca_school_fee_types,id',
            'level_id' => 'nullable|integer|exists:aca_school_levels,id',
            'grade_id' => 'nullable|integer|exists:aca_school_grades,id',
            'section_id' => 'nullable|integer|exists:aca_school_sections,id',
            'amount' => 'required|numeric|min:0',
            'status' => 'nullable|boolean',
        ]);

        $duplicate = AcaSchoolFee::where('year_id', $validated['year_id'])
            ->where('fee_type_id', $validated['fee_type_id'])
            ->where('level_id', $validated['level_id'] ?? null)
            ->where('grade_id', $validated['grade_id'] ?? null)
            ->where('section_id', $validated['section_id'] ?? null)
            ->when($id, fn ($q) => $q->where('id', '!=', $id))
            ->exists();

        if ($duplicate) {
            return $this->response($request, 'Ya existe una tarifa para ese concepto con el mismo alcance.', [
                'fee_type_id' => ['Ya existe una tarifa para ese concepto con el mismo alcance.'],
            ]);
        }

        $payload = [
            'year_id' => $validated['year_id'],
            'fee_type_id' => $validated['fee_type_id'],
            'level_id' => $validated['level_id'] ?? null,
            'grade_id' => $validated['grade_id'] ?? null,
            'section_id' => $validated['section_id'] ?? null,
            'amount' => $validated['amount'],
            'status' => (bool) ($validated['status'] ?? true),
        ];

        if ($id) {
            AcaSchoolFee::findOrFail($id)->update($payload);
            $message = 'Tarifa actualizada correctamente';
        } else {
            AcaSchoolFee::create($payload);
            $message = 'Tarifa registrada correctamente';
        }

        return $this->response($request, $message);
    }

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

        return redirect()->route('aca_school_fees_list')->with('message', $message);
    }
}
