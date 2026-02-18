<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Academic\Entities\AcaContent;
use Modules\Academic\Entities\AcaExam;
use Modules\Academic\Entities\AcaExamQuestion;
use Inertia\Inertia;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Auth;
use Modules\Academic\Entities\AcaStudent;
use Modules\Academic\Entities\AcaStudentExam;
use Illuminate\Support\Facades\Storage;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaExamAnswer;
use DataTables;
use Barryvdh\DomPDF\Facade\Pdf;

class AcaExamController extends Controller
{
    use ValidatesRequests;

    public function store(Request $request)
    {
        $id = $request->get('id');
        $description = $request->get('description');
        $dateStart = $request->get('date_start');
        $dateEnd = $request->get('date_end');
        $status = $request->get('status');


        $this->validate(
            $request,
            [
                'description' => 'required',
                'date_start' => 'required',
                'date_end' => 'required'
            ]
        );


        $origin = AcaContent::with('theme.module.course')
            ->where('id', $request->get('content_id'))
            ->first();
        $idExam =  null;

        $msg = null;
        $title  = 'Enhorabuena';

        if ($id) {
            if (AcaExam::where('id', $id)->exists()) {
                AcaExam::where('id', $id)
                    ->update([
                        'course_id' => $origin->theme->module->course->id,
                        'module_id' => $origin->theme->module->id,
                        'theme_id' => $origin->theme->id,
                        'content_id' => $origin->id,
                        'description' => $description,
                        'date_start' => $dateStart,
                        'date_end' => $dateEnd,
                        'status' => $status ? true : false,
                    ]);

                $msg = 'Se actualizo correctamente';
            } else {
                $msg = 'No existe el examen con id: ' . $id;
                $title  = 'Importante';
            }
            $idExam = $id;
        } else {
            $exam = AcaExam::create([
                'course_id' => $origin->theme->module->course->id,
                'module_id' => $origin->theme->module->id,
                'theme_id' => $origin->theme->id,
                'content_id' => $origin->id,
                'description' => $description,
                'date_start' => $dateStart,
                'date_end' => $dateEnd,
                'status' => $status ? true : false,
            ]);
            $idExam = $exam->id;
            $msg = 'Se registro correctamente';
        }

        return response()->json([
            'message' => $msg,
            'idExam' => $idExam,
            'title' => $title
        ]);
    }


    public function solve($id)
    {
        $content = AcaContent::with('exam.questions.answers')->findOrFail($id);

        // Verificar si la fecha actual está dentro del rango permitido
        $now = now();
        $dateStart = $content->exam->date_start;
        $dateEnd = $content->exam->date_end;

        $isAvailable = $now->between($dateStart, $dateEnd);

        // Barajar preguntas y respuestas
        $shuffledQuestions = $content->exam->questions->map(function ($question) {
            $answers = $question->answers->shuffle()->values()->toArray();

            // Contar cuántas respuestas son correctas
            $maxCorrectAnswers = $question->type_answers === 'Varias respuestas'
                ? collect($question->answers)->where('correct', true)->count()
                : null;

            return [
                'id' => $question->id,
                'description' => $question->description,
                'answers' => $answers,
                'type_answers' => $question->type_answers,
                'score' => $question->score,
                'max_correct_answers' => $maxCorrectAnswers
            ];
        })->shuffle()
        ->values()
        ->toArray();

        // Preparar el examen como array
        $exam = $content->exam->toArray();
        $exam['questions'] = $shuffledQuestions;

        // Convertir el objeto AcaContent en array y sobrescribir el examen
        $contentArray = $content->toArray();
        $contentArray['exam'] = $exam;

        $student = AcaStudent::where('person_id',Auth::user()->person_id)->first();
        $examStudent = AcaStudentExam::where('exam_id', $content->exam->id)
            ->where('student_id',$student->id)
            ->first();

        if(is_null($examStudent)){
            $examStudent = AcaStudentExam::create([
                'exam_id' => $content->exam->id,
                'student_id' => $student->id,
                'date_start' => Carbon::now(),
                'status' => 'pendiente',
                'punctuation' => 0
            ]);
        }


        // true si aún está dentro del rango, false si ya expiró
        return Inertia::render('Academic::Students/Exam', [
            'content' => $contentArray,
            'isSuccess' => $isAvailable,
            'examStudent' => $examStudent
        ]);
    }

    public function storeStudent(Request $request){
        $examId = $request->input('exam_id');
        $questions = $request->input('questions');
        $student = AcaStudent::where('person_id',Auth::user()->person_id)->first();

        $examStudent = AcaStudentExam::where('exam_id', $examId)
            ->where('student_id',$student->id)
            ->first();


        $examStudent->date_End = Carbon::now();
        $arrayAnswers = [];

        $punctuation = 0;
        $status = 'calificado';

        foreach ($questions as $index => $question) {
            $type = $question['type'];
            $individualScore = 0;

            if ($type === 'Subir Archivo' && $request->hasFile("questions.$index.answers")) {
                $file = $request->file("questions.$index.answers");
                $path = null;
                if ($file) {
                    $original_name = strtolower(trim($file->getClientOriginalName()));
                    $original_name = str_replace(" ", "_", $original_name);
                    $extension = $file->getClientOriginalExtension();

                    $file_name = time() . rand(100, 999) . '.' . $extension;
                    $destination = 'uploads/students/'.$student->id;
                    $path = Storage::disk('public')->putFileAs($destination, $file, $file_name);
                }
                array_push($arrayAnswers, array(
                    "id" => $question['id'],
                    "type" => $question['type'],
                    "answers" => $path,
                    "punctuation" => "X"
                ));
                $status = 'terminado';
            } else {
                if($type === 'Alternativas'){
                    $answersExam = AcaExamAnswer::where('id', $question['answers'])
                        ->where('correct',true)
                        ->first();
                    $individualScore = $answersExam->score;

                    if($answersExam){
                        $punctuation += $answersExam->score;
                    }
                }
                if($type === 'Varias respuestas'){
                    foreach($question['answers'] as $itemAnswer){
                        $answersExam = AcaExamAnswer::where('id', $itemAnswer)
                            ->where('correct',true)
                            ->first();

                        if($answersExam){
                            $individualScore += $answersExam->score;
                            $punctuation += $answersExam->score;
                        }
                    }
                }

                array_push($arrayAnswers, array(
                    "id" => $question['id'],
                    "type" => $question['type'],
                    "answers" => $question['answers'],
                    "punctuation" => (int) $individualScore
                ));
            }
        }

        $examStudent->details = json_encode($arrayAnswers);
        $examStudent->punctuation = $punctuation;
        $examStudent->status = $status;

        $examStudent->save();

        return response()->json([
            'message' => 'Examen guardado correctamente.',
            'examStudent' => $examStudent
        ]);
    }

    public function reviewExams(){
        $courses = AcaCourse::select('id','description')->get();

        return Inertia::render('Academic::Courses/ExamsStudents',[
            'courses' => $courses
        ]);
    }

    public function getAlumnsExam(){
        $studentExams = AcaStudentExam::with(['student.person', 'exam.course', 'exam.questions.answers']);
        $course_id = request()->get('course_id');
        if (request()->has('course_id') && $course_id != '') {

            $studentExams->whereHas('exam.course', function ($q) use ($course_id) {
                $q->where('id', $course_id);
            });
        }

        return DataTables::of($studentExams)
        // … tus otras addColumn() para nombre, curso, date_start, date_end …
        // Columna con tiempo transcurrido
        ->addColumn('elapsed_time', function ($row) {
            $start = Carbon::parse($row->date_start);
            // si ya existe date_end, lo tomamos; si no, comparamos con ahora
            $end = $row->date_end
                ? Carbon::parse($row->date_end)
                : Carbon::now();

            // diferencia como DateInterval
            $diff = $start->diff($end);

            // formatear: X días HH:MM:SS
            $days    = $diff->d;
            $hours   = str_pad($diff->h, 2, '0', STR_PAD_LEFT);
            $minutes = str_pad($diff->i, 2, '0', STR_PAD_LEFT);
            $seconds = str_pad($diff->s, 2, '0', STR_PAD_LEFT);

            return "{$days} días {$hours}:{$minutes}:{$seconds}";
        })->toJson();
    }

    public function questionAnswerPanelModule($cId, $mId, $eId){
        $exam = AcaExam::with([
            'course',
            'module',
            'questions.answers'
        ])->where('id', $eId)
        ->where('course_id', $cId)
        ->where('module_id', $mId)
        ->first();

        return Inertia::render('Academic::Courses/ModuleExam',[
            'exam' => $exam
        ]);
    }


    public function moduleExamSolve($id) {
        // 1. Cargar el examen con sus relaciones
        $exam = AcaExam::with([
            'questions.answers',
            'module.course'
        ])->findOrFail($id);

        // 2. Verificar disponibilidad de fechas
        $now = now();
        $isAvailable = $now->between($exam->date_start, $exam->date_end);

        // 3. Preparar preguntas (Shuffle y mapeo de datos necesarios para el Front)
        $shuffledQuestions = $exam->questions->map(function ($question) {
            // Barajamos las respuestas
            $answers = $question->answers->shuffle()->values()->toArray();

            // Calculamos el límite de respuestas correctas para "Varias respuestas"
            $maxCorrectAnswers = $question->type_answers === 'Varias respuestas'
                ? collect($question->answers)->where('correct', true)->count()
                : null;

            return [
                'id' => $question->id,
                'description' => $question->description,
                'answers' => $answers,
                'type_answers' => $question->type_answers,
                'score' => $question->score,
                'max_correct_answers' => $maxCorrectAnswers
            ];
        })->shuffle()->values()->toArray();

        $examArray = $exam->toArray();
        $examArray['questions'] = $shuffledQuestions;

        // 4. Buscar o crear el intento del estudiante
        $student = AcaStudent::where('person_id', Auth::user()->person_id)->first();

        $examStudent = AcaStudentExam::firstOrCreate(
            [
                'exam_id' => $exam->id,
                'student_id' => $student->id,
            ],
            [
                'date_start' => now(),
                'started_at' => now(), // Hora clave para el cronómetro
                'status' => 'pendiente',
                'punctuation' => 0,
                'details' => [] // Inicializar JSON vacío
            ]
        );

        // Asegurar que started_at existe (por si es un registro viejo que no lo tenía)
        if (!$examStudent->started_at) {
            $examStudent->started_at = $examStudent->created_at;
        }

        // 5. Reestructurar "details" para que Vue lo maneje fácilmente
        // Gracias al $casts en el modelo, $examStudent->details ya es un array
        $rawDetails = $examStudent->details ?? [];
        $detailsByQuestion = [];

        foreach ($rawDetails as $detail) {
            if (isset($detail['id'])) {
                // Indexamos por ID de pregunta para acceso instantáneo en JS: details[id]
                $detailsByQuestion[$detail['id']] = $detail;
            }
        }

        // 6. Preparar objeto de respuesta para Inertia
        // Convertimos a array para evitar que el Accessor del modelo interfiera
        $examStudentData = $examStudent->toArray();
        $examStudentData['details'] = $detailsByQuestion;
        $examStudentData['started_at'] = Carbon::parse($examStudent->started_at)->toDateTimeString();
        //dd($examStudentData);
        return Inertia::render('Academic::Students/ModuleExamSolve', [
            'exam' => $examArray,
            'isSuccess' => $isAvailable,
            'examStudent' => $examStudentData
        ]);
    }

    public function moduleStoreAnswer(Request $request) {
        // 1. Obtener datos directamente del request
        $questionId = $request->input('question_id');
        $type = $request->input('question_type');
        $studentExamId = $request->input('student_exam_id');

        // 2. Localizar registros
        $student = AcaStudent::where('person_id', Auth::user()->person_id)->first();
        $examStudent = AcaStudentExam::findOrFail($studentExamId);

        // 3. Preparar el manejo de detalles
        // Gracias al $casts en el modelo, esto ya es un array de PHP
        $currentDetails = $examStudent->details ?? [];

        $individualScore = 0;
        $answerToStore = null;

        // 4. Lógica según tipo de pregunta
        if ($type === 'Subir Archivo' && $request->hasFile("file_answer")) {
            $file = $request->file("file_answer");
            $file_name = time() . rand(100, 999) . '.' . $file->getClientOriginalExtension();
            $destination = 'uploads/students/'.$student->id;

            $path = Storage::disk('public')->putFileAs($destination, $file, $file_name);
            $answerToStore = $path;
            $individualScore = 0;
        }
        elseif ($type === 'Alternativas') {
            $answerId = $request->input('answers');
            $answerOption = AcaExamAnswer::where('id', $answerId)
                ->where('correct', true)
                ->first();

            if ($answerOption) {
                $individualScore = $answerOption->score;
            }
            $answerToStore = $answerId;
        }
        elseif ($type === 'Varias respuestas') {
            $selectedIds = $request->input('answers', []);
            if(!is_array($selectedIds)) $selectedIds = [$selectedIds];

            foreach ($selectedIds as $idAnswer) {
                $answerOption = AcaExamAnswer::where('id', $idAnswer)
                    ->where('correct', true)
                    ->first();
                if ($answerOption) {
                    $individualScore += $answerOption->score;
                }
            }
            $answerToStore = $selectedIds;
        }
        elseif ($type === 'Escribir') {
            $answerToStore = $request->input('answers');
            $individualScore = 0;
        }

        // 5. Actualizar el set de respuestas
        $newQuestionEntry = [
            "id" => (int) $questionId,
            "type" => $type,
            "answers" => $answerToStore,
            "punctuation" => (float) $individualScore,
            "updated_at" => now()->toDateTimeString()
        ];

        // Usamos colecciones para filtrar duplicados y agregar la nueva
        $filteredDetails = collect($currentDetails)
            ->filter(fn($item) => (int)$item['id'] !== (int)$questionId)
            ->values();

        $filteredDetails->push($newQuestionEntry);

        // 6. Guardar cambios
        // Al asignar un array, Laravel hace el json_encode automáticamente por el Cast
        $examStudent->details = $filteredDetails->toArray();
        $examStudent->punctuation = $filteredDetails->sum('punctuation');
        $examStudent->date_end = now();
        $examStudent->save();

        return response()->json([
            'success' => true,
            'message' => 'Respuesta guardada correctamente',
            'current_punctuation' => $examStudent->punctuation,
            'total_answered' => $filteredDetails->count()
        ]);
    }

    public function moduleStoreFinish(Request $request) {
        $id = $request->get('student_exam_id');
        $examStudent = AcaStudentExam::findOrFail($id);
        $exam = AcaExam::with('questions')->findOrFail($examStudent->exam_id);

        if ($examStudent->status === 'terminado' || $examStudent->status === 'revision_pendiente') {

            return to_route('aca_student_module_exam_solve', $exam->id);
        }

        // 1. Calcular tiempos
        $startedAt = Carbon::parse($examStudent->started_at);
        $timeSpent = $startedAt->diffInSeconds(now());
        $maxSeconds = $exam->duration_minutes * 60;

        // 2. Verificar calificación manual
        $details = $examStudent->details ?? [];
        $hasManualGrading = collect($details)->contains(function ($item) {
            return in_array($item['type'], ['Escribir', 'Subir Archivo']);
        });
        //dd($timeSpent);
        // 3. Actualizar estado
        $examStudent->status = $hasManualGrading ? 'revision_pendiente' : 'terminado';
        $examStudent->finished_at = now();
        $examStudent->time_spent_seconds = $timeSpent;
        $examStudent->is_timed_out = $timeSpent > ($maxSeconds + 30);
        $examStudent->save();

        //return to_route('aca_student_module_exam_solve', $exam->id);
    }

    public function downloadPdf($id){

        // Cargar el examen del estudiante con todas las relaciones necesarias
        $examStudent = AcaStudentExam::with([
            'student.person',
            'exam.course',
            'exam.module',
            'exam.questions.answers'
        ])->findOrFail($id);
        //dd($examStudent);
        $company = Company::first();

        // Logo de la empresa
        $logo = '';
        if ($company->logo_document == '/img/logo176x32.png') {
            $logo = public_path($company->logo_document);
        } else {
            $logo = public_path('storage' . DIRECTORY_SEPARATOR . $company->logo_document);
        }

        // Marca de agua opcional (puedes crear este archivo o cambiar la ruta)
        $watermark = public_path('img/watermark.png');
        $hasWatermark = file_exists($watermark);

        // Detalles del examen (respuestas del estudiante)
        $details = $examStudent->details ?? [];

        // Crear un mapa de respuestas por question_id para fácil acceso
        $answersMap = [];
        foreach ($details as $detail) {
            $answersMap[$detail['id']] = $detail;
        }

        // Variable para mostrar/ocultar respuestas correctas
        $showCorrectAnswers = true; // Cambiar a false si no quieres mostrar las correctas

        // Preparar datos para la vista
        $data = [
            'company' => $company,
            'logo' => $logo,
            'watermark' => $hasWatermark ? $watermark : null,
            'examStudent' => $examStudent,
            'exam' => $examStudent->exam,
            'student' => $examStudent->student,
            'details' => $details,
            'answersMap' => $answersMap,
            'showCorrectAnswers' => $showCorrectAnswers,
            'generatedAt' => now()->format('d/m/Y H:i:s'),
        ];

        // Generar PDF
        $pdf = Pdf::loadView('academic::exams.student_exam_pdf', $data);
        $pdf->setPaper('A4', 'portrait');

        // Nombre del archivo
        $fileName = 'examen_' . $examStudent->student->person->full_name . '_' . $examStudent->id . '.pdf';
        $fileName = str_replace(' ', '_', $fileName);

        return $pdf->download($fileName);
    }
}
