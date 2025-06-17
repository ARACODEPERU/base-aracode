<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Academic\Entities\AcaContent;
use Modules\Academic\Entities\AcaExam;
use Inertia\Inertia;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Auth;
use Modules\Academic\Entities\AcaStudent;
use Modules\Academic\Entities\AcaStudentExam;
use Illuminate\Support\Facades\Storage;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaExamAnswer;
use DataTables;

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
        //dd($request->all());
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
}
