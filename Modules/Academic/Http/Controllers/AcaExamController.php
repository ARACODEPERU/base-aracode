<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Academic\Entities\AcaContent;
use Modules\Academic\Entities\AcaExam;
use Inertia\Inertia;

class AcaExamController extends Controller
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
        $id = $request->get('id');
        $description = $request->get('description');
        $dateStart = $request->get('date_start');
        $dateEnd = $request->get('date_end');
        $status = $request->get('status');
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

    public function solve($id){

        $content = AcaContent::with('exam.questions.answers')->where('id',$id)->first();
        return Inertia::render('Academic::Students/Exam',[
            'content' => $content
        ]);
    }
}
