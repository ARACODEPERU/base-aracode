<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaSchoolArea;
use Modules\Academic\Services\SchoolContextService;

/**
 * Mantenedor de areas curriculares del colegio (CRUD completo). El catalogo
 * alimenta el registro de notas del docente por nivel CNEB e incluye la
 * precarga de las areas estandar del CNEB con un clic.
 */
class AcaSchoolAreaController extends Controller
{
    public function __construct(private SchoolContextService $context)
    {
    }

    public function index(Request $request)
    {
        $school = $this->context->currentSchool();

        $areas = collect();
        if ($school) {
            $areas = AcaSchoolArea::where('school_id', $school->id)
                ->when($request->query('level'), function ($query, $level) {
                    $query->where('level', $level);
                })
                ->orderBy('level')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        }

        return Inertia::render('Academic::School/Areas/List', [
            'areas' => $areas,
            'school' => $school,
            'filters' => $request->query('level') ? ['level' => $request->query('level')] : [],
        ]);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string|max:120',
            'level' => 'required|in:'.implode(',', AcaSchoolArea::LEVELS),
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $school = $this->context->currentSchool();
        if (! $school) {
            return back()->withErrors(['name' => 'No hay un colegio configurado. Registre uno primero.']);
        }

        $name = trim($request->get('name'));
        $level = $request->get('level');

        if ($this->duplicateExists($school->id, $level, $name)) {
            return back()->withErrors([
                'name' => 'El área '.$name.' ya existe para el nivel '.AcaSchoolArea::levelLabel($level).'.',
            ]);
        }

        AcaSchoolArea::create([
            'school_id' => $school->id,
            'name' => $name,
            'level' => $level,
            'sort_order' => $request->filled('sort_order')
                ? (int) $request->get('sort_order')
                : $this->nextSortOrder($school->id, $level),
            'status' => true,
        ]);

        return redirect()->route('aca_school_areas_list')
            ->with('message', __('Área curricular creada con éxito'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|integer',
            'name' => 'required|string|max:120',
            'level' => 'required|in:'.implode(',', AcaSchoolArea::LEVELS),
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'nullable|boolean',
        ]);

        $area = AcaSchoolArea::findOrFail($request->get('id'));

        $name = trim($request->get('name'));
        $level = $request->get('level');

        if ($this->duplicateExists($area->school_id, $level, $name, $area->id)) {
            return back()->withErrors([
                'name' => 'El área '.$name.' ya existe para el nivel '.AcaSchoolArea::levelLabel($level).'.',
            ]);
        }

        $area->update([
            'name' => $name,
            'level' => $level,
            'sort_order' => (int) ($request->get('sort_order') ?: $area->sort_order),
            'status' => $request->boolean('status'),
        ]);

        return redirect()->route('aca_school_areas_list')
            ->with('message', __('Área curricular actualizada con éxito'));
    }

    public function destroy(Request $request, int $id)
    {
        $area = AcaSchoolArea::findOrFail($id);
        $area->delete();

        // Las notas guardadas conservan el texto del area; solo se retira del
        // catalogo para el registro futuro.
        return redirect()->route('aca_school_areas_list')
            ->with('message', __('Área curricular eliminada'));
    }

    /**
     * Precarga las areas curriculares estandar del CNEB para un nivel.
     * firstOrCreate: no duplica las areas que ya existan en el colegio.
     */
    public function seedStandard(Request $request)
    {
        $this->validate($request, [
            'level' => 'required|in:'.implode(',', AcaSchoolArea::LEVELS),
        ]);

        $school = $this->context->currentSchool();
        if (! $school) {
            return back()->withErrors(['level' => 'No hay un colegio configurado. Registre uno primero.']);
        }

        $level = $request->get('level');
        $created = 0;

        foreach (AcaSchoolArea::standardCnebAreas()[$level] as $i => $name) {
            $area = AcaSchoolArea::firstOrCreate(
                ['school_id' => $school->id, 'level' => $level, 'name' => $name],
                ['sort_order' => $i + 1, 'status' => true]
            );

            if ($area->wasRecentlyCreated) {
                $created++;
            }
        }

        return redirect()->route('aca_school_areas_list')
            ->with('message', __('Se cargaron '.$created.' áreas estándar del CNEB para '.AcaSchoolArea::levelLabel($level)));
    }

    private function duplicateExists(int $schoolId, string $level, string $name, ?int $exceptId = null): bool
    {
        return AcaSchoolArea::where('school_id', $schoolId)
            ->where('level', $level)
            ->where('name', $name)
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->exists();
    }

    private function nextSortOrder(int $schoolId, string $level): int
    {
        return (int) AcaSchoolArea::where('school_id', $schoolId)
            ->where('level', $level)
            ->max('sort_order') + 1;
    }
}
