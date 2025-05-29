<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Academic\Entities\AcaExamAnswer;

class AcaExamAnswerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('academic::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('academic::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //dd($request->all());
        $id = $request->get('id');
        $question_id = $request->get('question_id');
        $description = $request->get('description');
        $score = $request->get('score');
        $correct = $request->get('correct');
        $answer = [];
        $title = 'Enhorabuena';

        if ($id) {
            $answer = AcaExamAnswer::find($id);
            $answer->update([
                'description' => $description,
                'score' => $score,
                'correct' => $correct
            ]);
            $message = 'Se actualizo correctamente';
        } else {
            $answer = AcaExamAnswer::create([
                'exam_id' => $question_id,
                'description' => $description,
                'score' => $score,
                'correct' => $correct
            ]);
            $message = 'Se registro correctamente';
        }

        return response()->json([
            'title' => $title,
            'message' => $message,
            'answer' => $answer
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
}
