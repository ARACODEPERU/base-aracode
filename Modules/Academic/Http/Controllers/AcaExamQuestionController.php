<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Entities\AcaExamQuestion;

class AcaExamQuestionController extends Controller
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
        $exam_id = $request->get('exam_id');
        $description = $request->get('description');
        $scord = $request->get('scord');
        $type = $request->get('type');
        $question = [];
        $title = 'Enhorabuena';

        if ($id) {
            $question = AcaExamQuestion::find($id);
            $question->update([
                'description' => $description,
                'score' => $scord,
                'type_answers' => $type
            ]);
            $message = 'Se actualizo correctamente';
        } else {
            $question = AcaExamQuestion::create([
                'exam_id' => $exam_id,
                'description' => $description,
                'score' => $scord,
                'type_answers' => $type
            ]);
            $message = 'Se registro correctamente';
        }

        return response()->json([
            'title' => $title,
            'message' => $message,
            'question' => $question
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

        $message = null;
        $success = false;
        try {
            // Usamos una transacción para asegurarnos de que la operación se realice de manera segura.
            DB::beginTransaction();

            // Verificamos si existe.
            $item = AcaExamQuestion::findOrFail($id);

            // Si no hay detalles asociados, eliminamos.
            $item->delete();

            // Si todo ha sido exitoso, confirmamos la transacción.
            DB::commit();

            $message =  'Pregunta eliminada correctamente';
            $success = true;
        } catch (\Exception $e) {
            // Si ocurre alguna excepción durante la transacción, hacemos rollback para deshacer cualquier cambio.
            DB::rollback();
            $success = false;
            $message = $e->getMessage();
        }

        return response()->json([
            'success' => $success,
            'message' => $message
        ]);
    }
}
