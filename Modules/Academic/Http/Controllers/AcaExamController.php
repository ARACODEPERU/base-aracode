<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Academic\Entities\AcaContent;
use Modules\Academic\Entities\AcaExam;
use Inertia\Inertia;
use Illuminate\Foundation\Validation\ValidatesRequests;

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

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('academic::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('academic::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
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

            return [
                'id' => $question->id,
                'description' => $question->description,
                'answers' => $answers,
                'type_answers' => $question->type_answers,
            ];
        })->shuffle()->values()->toArray();

        // Preparar el examen como array
        $exam = $content->exam->toArray();
        $exam['questions'] = $shuffledQuestions;

        // Convertir el objeto AcaContent en array y sobrescribir el examen
        $contentArray = $content->toArray();
        $contentArray['exam'] = $exam;

        return Inertia::render('Academic::Students/Exam', [
            'content' => $contentArray,
            'isSuccess' => $isAvailable, // true si aún está dentro del rango, false si ya expiró
        ]);
    }
}
