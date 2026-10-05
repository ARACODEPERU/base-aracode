<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaSchool;

class AcaSchoolController extends Controller
{
    public function index(Request $request)
    {
        $schools = AcaSchool::query()
            ->when($request->query('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%')
                        ->orWhere('modular_code', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->onEachSide(2)
            ->withQueryString();

        return Inertia::render('Academic::School/Schools/List', [
            'schools' => $schools,
            'filters' => $request->query('search') ? ['search' => $request->query('search')] : [],
        ]);
    }

    public function create()
    {
        return Inertia::render('Academic::School/Schools/Create');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:300',
            'modular_code' => 'nullable|max:20',
            'type' => 'required|in:privado,nacional',
        ]);

        AcaSchool::create($this->payload($request, true));

        return redirect()->route('aca_schools_list')
            ->with('message', __('Colegio registrado con éxito'));
    }

    public function edit(int $id)
    {
        return Inertia::render('Academic::School/Schools/Edit', [
            'school' => AcaSchool::findOrFail($id),
        ]);
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:300',
            'modular_code' => 'nullable|max:20',
            'type' => 'required|in:privado,nacional',
        ]);

        $school = AcaSchool::findOrFail($request->get('id'));
        $school->update($this->payload($request, false));

        return redirect()->route('aca_schools_list')
            ->with('message', __('Colegio actualizado con éxito'));
    }

    public function destroy(int $id)
    {
        $message = null;
        $success = false;

        try {
            DB::beginTransaction();

            $school = AcaSchool::findOrFail($id);

            if ($school->enrollments()->exists()) {
                throw new \Exception('El colegio tiene matrículas registradas, no puede eliminarse.');
            }

            $school->delete();
            DB::commit();

            $message = 'Colegio eliminado correctamente';
            $success = true;
        } catch (\Exception $e) {
            DB::rollBack();
            $success = false;
            $message = $e->getMessage();
        }

        return response()->json([
            'success' => $success,
            'message' => $message,
        ]);
    }

    private function payload(Request $request, bool $creating): array
    {
        $path = null;
        $file = $request->file('logo');
        if ($file) {
            $file_name = date('YmdHis').'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs('uploads/schools', $file_name, 'public');
        }

        $data = [
            'name' => $request->get('name'),
            'modular_code' => $request->get('modular_code'),
            'address' => $request->get('address'),
            'phone' => $request->get('phone'),
            'email' => $request->get('email'),
            'type' => $request->get('type', AcaSchool::TYPE_PRIVADO),
            'status' => $request->boolean('status'),
        ];

        if ($path) {
            $data['logo'] = $path;
        }

        // Un solo colegio por defecto: al marcar uno se desmarca el resto.
        if ($request->boolean('is_default')) {
            AcaSchool::where('is_default', true)->update(['is_default' => false]);
            $data['is_default'] = true;
        } elseif ($creating) {
            $data['is_default'] = false;
        }

        return $data;
    }
}
