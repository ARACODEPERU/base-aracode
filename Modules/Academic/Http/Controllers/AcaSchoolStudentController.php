<?php

namespace Modules\Academic\Http\Controllers;

use App\Models\District;
use App\Models\Person;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaSchoolEnrollment;
use Modules\Academic\Entities\AcaSchoolStudent;
use Modules\Academic\Entities\AcaSchoolStudentGuardian;
use Modules\Academic\Entities\AcaSchoolYear;
use Modules\Academic\Services\SchoolContextService;

class AcaSchoolStudentController extends Controller
{
    public function __construct(private SchoolContextService $context)
    {
    }

    public function index(Request $request)
    {
        $school = $this->context->currentSchool();

        $students = collect();
        if ($school) {
            $students = AcaSchoolStudent::query()
                ->where('aca_school_students.school_id', $school->id)
                ->leftJoin('people', 'people.id', '=', 'aca_school_students.person_id')
                ->when($request->query('search'), function ($query, $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('people.full_name', 'like', '%'.$search.'%')
                            ->orWhere('people.number', 'like', '%'.$search.'%')
                            ->orWhere('aca_school_students.student_code', 'like', '%'.$search.'%');
                    });
                })
                ->when($request->query('status') !== null && $request->query('status') !== '', function ($query) use ($request) {
                    $query->where('aca_school_students.status', $request->boolean('status'));
                })
                ->orderBy('people.full_name')
                ->select('aca_school_students.*')
                ->selectRaw('(SELECT ae.id FROM aca_school_enrollments ae
                    WHERE ae.student_id = aca_school_students.id AND ae.status = ?
                    ORDER BY ae.id DESC LIMIT 1) AS active_enrollment_id', [AcaSchoolEnrollment::STATUS_ACTIVO])
                ->with('person')
                ->paginate(20)
                ->onEachSide(2)
                ->withQueryString();
        }

        return Inertia::render('Academic::School/Students/List', [
            'students' => $students,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Academic::School/Students/Create', [
            'documentTypes' => $this->documentTypes(),
        ]);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'document_type_id' => 'required',
            'number' => 'required|max:12|unique:people,number',
            'names' => 'required|max:255',
            'father_lastname' => 'required|max:255',
            'mother_lastname' => 'required|max:255',
            'gender' => 'nullable|in:M,F',
            'birthdate' => 'nullable|date',
            'telephone' => 'nullable|max:12',
            'email' => 'nullable|email|max:255|unique:people,email',
            'address' => 'nullable|max:255',
        ]);

        $school = $this->context->currentSchool();

        if (! $school) {
            return back()->withErrors(['number' => 'No hay un colegio configurado.']);
        }

        try {
            $newStudentId = null;
            DB::transaction(function () use ($request, $school, &$newStudentId) {
                $person = Person::create([
                    'document_type_id' => $request->get('document_type_id'),
                    'number' => $request->get('number'),
                    'names' => $request->get('names'),
                    'father_lastname' => $request->get('father_lastname'),
                    'mother_lastname' => $request->get('mother_lastname'),
                    'short_name' => $request->get('names'),
                    'full_name' => trim($request->get('father_lastname').' '.$request->get('mother_lastname').' '.$request->get('names')),
                    'gender' => $request->get('gender'),
                    'birthdate' => $request->get('birthdate'),
                    'telephone' => $request->get('telephone'),
                    'email' => $request->get('email'),
                    'address' => $request->get('address'),
                    'is_client' => true,
                    'is_provider' => false,
                ]);

                $newStudent = AcaSchoolStudent::create([
                    'school_id' => $school->id,
                    'person_id' => $person->id,
                    'student_code' => AcaSchoolStudent::nextStudentCode($school->id),
                    'status' => true,
                ]);
                $newStudentId = $newStudent->id;
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['number' => 'No se pudo registrar el alumno: '.$e->getMessage()]);
        }

        return redirect()->route('aca_school_students_list')
            ->with('message', __('Alumno registrado con éxito'));
    }

    public function edit(int $id)
    {
        $student = AcaSchoolStudent::with(['person', 'guardians.person'])->findOrFail($id);

        return Inertia::render('Academic::School/Students/Edit', [
            'student' => $student,
            'documentTypes' => $this->documentTypes(),
            'guardianRelationships' => AcaSchoolStudentGuardian::relationshipLabels(),
            'ubigeo' => $this->ubigeoOptions(),
        ]);
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|integer',
            'document_type_id' => 'required',
            'number' => 'required|max:12',
            'names' => 'required|max:255',
            'father_lastname' => 'required|max:255',
            'mother_lastname' => 'required|max:255',
            'email' => 'nullable|email|max:255',
        ]);

        $student = AcaSchoolStudent::with('person')->findOrFail($request->get('id'));

        $exists = Person::where('number', $request->get('number'))
            ->where('id', '!=', $student->person_id)
            ->where('document_type_id', $request->get('document_type_id'))
            ->exists();

        if ($exists) {
            return back()->withErrors(['number' => 'Ese número de documento ya pertenece a otra persona.']);
        }

        try {
            DB::transaction(function () use ($request, $student) {
                $student->person->update([
                    'document_type_id' => $request->get('document_type_id'),
                    'number' => $request->get('number'),
                    'names' => $request->get('names'),
                    'father_lastname' => $request->get('father_lastname'),
                    'mother_lastname' => $request->get('mother_lastname'),
                    'short_name' => $request->get('names'),
                    'full_name' => trim($request->get('father_lastname').' '.$request->get('mother_lastname').' '.$request->get('names')),
                    'gender' => $request->get('gender'),
                    'birthdate' => $request->get('birthdate'),
                    'telephone' => $request->get('telephone'),
                    'email' => $request->get('email'),
                    'address' => $request->get('address') ?: $student->person->address,
                    'status' => $request->boolean('status'),
                ]);

                $student->update([
                    'student_code' => $request->get('student_code') ?: $student->student_code,
                    'status' => $request->boolean('status'),
                ]);
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['number' => 'No se pudo actualizar el alumno: '.$e->getMessage()]);
        }

        return redirect()->route('aca_school_students_list')
            ->with('message', __('Alumno actualizado con éxito'));
    }

    public function destroy(int $id)
    {
        $student = AcaSchoolStudent::findOrFail($id);

        if ($student->enrollments()->where('status', AcaSchoolEnrollment::STATUS_ACTIVO)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'El alumno tiene matrículas activas, no puede eliminarse.',
            ]);
        }

        $student->delete();

        return response()->json(['success' => true, 'message' => 'Alumno eliminado correctamente']);
    }

    /**
     * Apoderados del alumno (panel de la ficha).
     */
    public function listGuardians(int $studentId)
    {
        $guardians = AcaSchoolStudentGuardian::query()
            ->where('student_id', $studentId)
            ->with('person:id,document_type_id,number,names,father_lastname,mother_lastname,full_name,telephone,email,address')
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get();

        return response()->json(['success' => true, 'guardians' => $guardians]);
    }

    /**
     * Vincula una persona como apoderada del alumno (buscada en el modal).
     */
    public function storeGuardian(Request $request, int $studentId)
    {
        $validated = $this->validate($request, [
            'person_id' => 'required|integer|exists:people,id',
            'relationship' => 'required|in:'.implode(',', array_keys(AcaSchoolStudentGuardian::relationshipLabels())),
            'is_primary' => 'nullable|boolean',
        ]);

        $student = AcaSchoolStudent::findOrFail($studentId);
        $school = $this->context->currentSchool();

        $guardian = AcaSchoolStudentGuardian::updateOrCreate(
            ['student_id' => $student->id, 'guardian_person_id' => $validated['person_id']],
            [
                'school_id' => $school->id ?? $student->school_id,
                'relationship' => $validated['relationship'],
                'is_primary' => (bool) ($validated['is_primary'] ?? false),
                'status' => true,
            ]
        );

        if ($guardian->is_primary) {
            AcaSchoolStudentGuardian::where('student_id', $student->id)
                ->where('id', '!=', $guardian->id)
                ->update(['is_primary' => false]);
        }

        return response()->json(['success' => true, 'message' => 'Apoderado guardado correctamente']);
    }

    /**
     * Actualiza parentesco / principal / estado de un apoderado.
     */
    public function updateGuardian(Request $request, int $guardianId)
    {
        $validated = $this->validate($request, [
            'relationship' => 'required|in:'.implode(',', array_keys(AcaSchoolStudentGuardian::relationshipLabels())),
            'is_primary' => 'nullable|boolean',
            'status' => 'nullable|boolean',
        ]);

        $guardian = AcaSchoolStudentGuardian::findOrFail($guardianId);
        $guardian->update([
            'relationship' => $validated['relationship'],
            'is_primary' => (bool) ($validated['is_primary'] ?? false),
            'status' => $validated['status'] ?? true,
        ]);

        if ($guardian->is_primary) {
            AcaSchoolStudentGuardian::where('student_id', $guardian->student_id)
                ->where('id', '!=', $guardian->id)
                ->update(['is_primary' => false]);
        }

        return response()->json(['success' => true, 'message' => 'Apoderado actualizado correctamente']);
    }

    /**
     * Desvincula un apoderado del alumno.
     */
    public function destroyGuardian(int $guardianId)
    {
        $guardian = AcaSchoolStudentGuardian::findOrFail($guardianId);
        $guardian->delete();

        return response()->json(['success' => true, 'message' => 'Apoderado eliminado correctamente']);
    }

    private function documentTypes(): array
    {
        return DB::table('identity_document_type')->get(['id', 'description'])->toArray();
    }

    private function ubigeoOptions(): array
    {
        return District::query()
            ->join('provinces', 'province_id', 'provinces.id')
            ->join('departments', 'provinces.department_id', 'departments.id')
            ->get([
                'districts.id AS district_id',
                'districts.name AS district_name',
                DB::raw("CONCAT(departments.name,'-',provinces.name,'-',districts.name) AS city_name"),
            ])
            ->toArray();
    }
}
