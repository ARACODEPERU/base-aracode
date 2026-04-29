<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Entities\AcaStudent;
use Modules\Academic\Entities\AcaStudentCourseInterest;

class AcaStudentApiController extends Controller
{
    /**
     * GET /api/academic/student/{dni}
     * Consulta un estudiante por número de DNI
     */
    public function show($dni)
    {
        $person = Person::where('number', $dni)
            ->with('student')
            ->first();

        if (!$person) {
            return response()->json([
                'success' => false,
                'message' => 'Estudiante no encontrado',
                'data' => null
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Estudiante encontrado',
            'data' => [
                'person_id' => $person->id,
                'names' => $person->names,
                'father_lastname' => $person->father_lastname,
                'mother_lastname' => $person->mother_lastname,
                'number' => $person->number,
                'email' => $person->email,
                'gender' => $person->gender,
                'student_code' => $person->student->student_code ?? null,
                'student_id' => $person->student->id ?? null
            ]
        ]);
    }

    /**
     * POST /api/academic/students/register
     * Registra un nuevo estudiante
     */
    public function store(Request $request)
    {
        $request->validate([
            'names' => 'required|string|max:255',
            'father_lastname' => 'required|string|max:255',
            'mother_lastname' => 'required|string|max:255',
            'number' => 'required|string|max:20|unique:people,number',
            'email' => 'nullable|email|max:255|unique:people,email',
            'gender' => 'nullable|string|max:1|in:M,F',
            'course_interest_id' => 'nullable|exists:aca_courses,id'
        ]);

        try {
            DB::beginTransaction();

            $person = Person::create([
                'names' => $request->names,
                'father_lastname' => $request->father_lastname,
                'mother_lastname' => $request->mother_lastname,
                'number' => $request->number,
                'email' => $request->email,
                'gender' => $request->gender,
                'document_type_id' => '1',
                'full_name' => trim($request->father_lastname . ' ' . $request->mother_lastname . ' ' . $request->names)
            ]);

            $student = AcaStudent::create([
                'student_code' => $request->number,
                'person_id' => $person->id
            ]);

            $courseInterestId = $request->course_interest_id;
            if ($courseInterestId) {
                DB::table('aca_student_courses_interests')->insert([
                    'student_id' => $student->id,
                    'course_id' => $courseInterestId,
                    'status' => 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Estudiante registrado correctamente',
                'data' => [
                    'student_id' => $student->id,
                    'person_id' => $person->id,
                    'student_code' => $student->student_code
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al registrar estudiante: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}