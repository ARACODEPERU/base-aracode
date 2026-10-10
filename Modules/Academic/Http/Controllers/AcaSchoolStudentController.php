<?php

namespace Modules\Academic\Http\Controllers;

use App\Models\District;
use App\Models\Person;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaSchool;
use Modules\Academic\Entities\AcaSchoolEnrollment;
use Modules\Academic\Entities\AcaSchoolLevel;
use Modules\Academic\Entities\AcaSchoolStudent;
use Modules\Academic\Entities\AcaSchoolStudentGuardian;
use Modules\Academic\Entities\AcaSchoolYear;
use Modules\Academic\Services\SchoolContextService;
use Modules\Academic\Services\StudentCardQr;

class AcaSchoolStudentController extends Controller
{
    public function __construct(private SchoolContextService $context)
    {
    }

    public function index(Request $request)
    {
        $school = $this->context->currentSchool();

        $students = collect();
        $filterOptions = collect();
        if ($school) {
            $students = $this->filteredStudentsQuery($school, $request)
                ->selectRaw('(SELECT ae.id FROM aca_school_enrollments ae
                    WHERE ae.student_id = aca_school_students.id AND ae.status = ?
                    ORDER BY ae.id DESC LIMIT 1) AS active_enrollment_id', [AcaSchoolEnrollment::STATUS_ACTIVO])
                ->with('person')
                ->paginate(20)
                ->onEachSide(2)
                ->withQueryString();

            // Opciones para los filtros en cascada Nivel -> Grado -> Sección.
            $filterOptions = AcaSchoolLevel::query()
                ->where('school_id', $school->id)
                ->where('status', true)
                ->orderBy('sort_order')
                ->with(['grades' => fn ($q) => $q->orderBy('sort_order'), 'grades.sections' => fn ($q) => $q->orderBy('id')])
                ->get(['id', 'name']);
        }

        return Inertia::render('Academic::School/Students/List', [
            'students' => $students,
            'filters' => $request->only(['search', 'status', 'level_id', 'grade_id', 'section_id']),
            'filterOptions' => $filterOptions,
        ]);
    }

    /**
     * Query de alumnos del colegio con búsqueda y filtros por estructura
     * académica (nivel/grado/sección según la matrícula activa).
     */
    private function filteredStudentsQuery(AcaSchool $school, Request $request)
    {
        return AcaSchoolStudent::query()
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
            ->when($request->query('section_id'), function ($query, $sectionId) {
                $query->whereHas('enrollments', fn ($e) => $e
                    ->where('status', AcaSchoolEnrollment::STATUS_ACTIVO)
                    ->where('section_id', $sectionId));
            })
            ->when($request->query('grade_id'), function ($query, $gradeId) {
                $query->whereHas('enrollments', fn ($e) => $e
                    ->where('status', AcaSchoolEnrollment::STATUS_ACTIVO)
                    ->whereHas('section', fn ($s) => $s->where('grade_id', $gradeId)));
            })
            ->when($request->query('level_id'), function ($query, $levelId) {
                $query->whereHas('enrollments', fn ($e) => $e
                    ->where('status', AcaSchoolEnrollment::STATUS_ACTIVO)
                    ->whereHas('section.grade', fn ($g) => $g->where('level_id', $levelId)));
            })
            ->orderBy('people.full_name')
            ->select('aca_school_students.*');
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
     * Carné escolar del alumno (PDF para imprimir).
     */
    public function card(int $id)
    {
        $school = $this->context->currentSchool();

        $student = AcaSchoolStudent::query()
            ->where('school_id', $school?->id ?? 0)
            ->with('person')
            ->findOrFail($id);

        // Nivel/grado/sección provienen de la matrícula activa más reciente.
        $enrollment = AcaSchoolEnrollment::query()
            ->where('student_id', $student->id)
            ->where('status', AcaSchoolEnrollment::STATUS_ACTIVO)
            ->with(['section.grade.level', 'year'])
            ->orderByDesc('id')
            ->first();

        if (! $enrollment || ! $enrollment->section) {
            abort(404, 'El alumno no tiene una matrícula activa con sección asignada para generar el carné.');
        }

        // Logo del colegio embebido en base64 para DomPDF.
        $logoDataUri = null;
        if ($school?->logo && Storage::disk('public')->exists($school->logo)) {
            $mime = Storage::disk('public')->mimeType($school->logo);
            $logoDataUri = 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($school->logo));
        }

        // QR con el código del alumno: lo lee el escáner de portería.
        $qrDataUri = StudentCardQr::dataUri($student->student_code);

        $pdf = Pdf::loadView('academic::cards.student_card', [
            'school' => $school,
            'student' => $student,
            'enrollment' => $enrollment,
            'logoDataUri' => $logoDataUri,
            'qrDataUri' => $qrDataUri,
        ]);
        $pdf->setPaper('a4', 'portrait');

        $fileName = 'carnet_'.$student->student_code.'_'.$student->person?->full_name.'.pdf';

        return $pdf->stream(str_replace(' ', '_', $fileName));
    }

    /**
     * Ids de alumnos que coinciden con los filtros actuales (para "seleccionar
     * todo" en la impresión masiva de carnés).
     */
    public function bulkIds(Request $request)
    {
        $school = $this->context->currentSchool();

        if (! $school) {
            return response()->json(['ids' => [], 'total' => 0]);
        }

        $query = $this->filteredStudentsQuery($school, $request);
        $total = (clone $query)->count();
        $ids = $query->limit(200)->pluck('aca_school_students.id');

        return response()->json(['ids' => $ids, 'total' => $total]);
    }

    /**
     * Impresión masiva de carnés (varios por hoja A4, 3x3, tamaño original).
     */
    public function cardsBulk(Request $request)
    {
        $raw = $request->input('ids', []);
        $ids = collect(is_string($raw) ? explode(',', $raw) : (array) $raw)
            ->map(fn ($v) => (int) $v)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($ids)) {
            abort(400, 'No se seleccionaron alumnos para imprimir.');
        }

        if (count($ids) > 100) {
            abort(400, 'Máximo 100 carnés por tanda de impresión.');
        }

        $school = $this->context->currentSchool();

        $students = AcaSchoolStudent::query()
            ->where('school_id', $school?->id ?? 0)
            ->whereIn('id', $ids)
            ->with('person')
            ->get()
            ->keyBy('id');

        // Matrícula activa más reciente de cada alumno (igual que el carné individual).
        $enrollments = AcaSchoolEnrollment::query()
            ->whereIn('student_id', $ids)
            ->where('status', AcaSchoolEnrollment::STATUS_ACTIVO)
            ->with(['section.grade.level', 'year'])
            ->orderByDesc('id')
            ->get()
            ->groupBy('student_id');

        // Logo del colegio embebido en base64 para DomPDF (una sola vez).
        $logoDataUri = null;
        if ($school?->logo && Storage::disk('public')->exists($school->logo)) {
            $mime = Storage::disk('public')->mimeType($school->logo);
            $logoDataUri = 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($school->logo));
        }

        $cards = [];
        foreach ($ids as $id) {
            $student = $students->get($id);
            $enrollment = $enrollments->get($id)?->first();

            // Se omiten los alumnos sin matrícula activa con sección asignada.
            if (! $student || ! $enrollment || ! $enrollment->section) {
                continue;
            }

            $cards[] = [
                'student' => $student,
                'enrollment' => $enrollment,
                'qrDataUri' => StudentCardQr::dataUri($student->student_code),
            ];
        }

        if (empty($cards)) {
            abort(404, 'Los alumnos seleccionados no tienen matrícula activa con sección asignada.');
        }

        // Orden alfabético para una mejor presentación al recortar.
        $cards = collect($cards)
            ->sortBy(fn ($c) => $c['student']->person?->full_name)
            ->values()
            ->all();

        $pdf = Pdf::loadView('academic::cards.students_cards_bulk', [
            'school' => $school,
            'cards' => $cards,
            'logoDataUri' => $logoDataUri,
        ]);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('carnes_alumnos_'.date('YmdHis').'.pdf');
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
