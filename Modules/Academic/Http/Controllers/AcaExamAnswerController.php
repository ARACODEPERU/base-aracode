<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Academic\Entities\AcaExamAnswer;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Entities\AcaStudentExam;

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
                'question_id' => $question_id,
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

    public function gradeExamResponse(Request $request)
    {
        $examStudent = AcaStudentExam::find($request->get('exam_id'));

        // Obtener y asegurarnos de que sea un array
        $details = $examStudent->details;
        if (!is_array($details)) {
            $details = json_decode($details, true);
        }

        // Obtener datos del frontend
        $questionId = (string) $request->input('qualifies.question_id');
        $newScore = $request->input('qualifies.punctuation');
        $total_score = 0;
        // Actualizar puntuación de la pregunta correspondiente
        foreach ($details as &$item) {
            if ((string) $item['id'] === $questionId) {
                $item['punctuation'] = (int) $newScore;
            }
            $total_score += is_numeric($item['punctuation']) ? (int) $item['punctuation'] : 0;
        }

        // Verificar si todas las preguntas ya están calificadas (todas con puntuación numérica)
        $allGraded = collect($details)->every(function ($item) {
            return is_numeric($item['punctuation']);
        });

        // Cambiar estado si ya está completamente calificado
        if ($allGraded) {
            $examStudent->status = 'calificado'; // Puedes cambiar a otro valor según tu lógica
        }

        // Guardar los cambios
        $examStudent->details = $details;
        $examStudent->punctuation = $total_score;
        $examStudent->save();

        return response()->json([
            'message' => 'Puntuación actualizada correctamente.',
            'status' => $examStudent->status,
            'punctuation' => $examStudent->punctuation,
        ]);
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
            $item = AcaExamAnswer::findOrFail($id);

            // Si no hay detalles asociados, eliminamos.
            $item->delete();

            // Si todo ha sido exitoso, confirmamos la transacción.
            DB::commit();

            $message =  'Respuesta eliminada correctamente';
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
