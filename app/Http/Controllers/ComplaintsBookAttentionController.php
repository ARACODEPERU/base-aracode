<?php

namespace App\Http\Controllers;

use App\Models\ComplaintsBook;
use App\Models\ComplaintsBookAttention;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ComplaintsBookAttentionController extends Controller
{
    public function store(Request $request){

        $this->validate($request, [
            'status' => 'required|string|max:2',
            'date_start' => 'required',
            'date_end' => 'required',
            'priority' => 'required',
            'description' => 'required',
            'response' => 'required',
            'means_communication' => 'required'
        ]);

        try {
            DB::transaction(function () use ($request) {
                $file = $request->file('attached_file');

                $attention = ComplaintsBookAttention::create([
                    'complaints_book_id' => $request->get('book_id'),
                    'user_id' => Auth::id(),
                    'status' => $request->get('status'),
                    'date_start' => $request->get('date_start'),
                    'date_end' => $request->get('date_end'),
                    'priority' => $request->get('priority'),
                    'internal_management_notes' => $request->get('description'),
                    'consumer_response' => $request->get('response'),
                ]);

                if($file){
                    $destination = 'uploads' . DIRECTORY_SEPARATOR . 'complaintsbookattentions'.DIRECTORY_SEPARATOR . $attention->id;
                    $original_name = strtolower(trim($file->getClientOriginalName()));
                    $original_name = str_replace(" ", "_", $original_name);
                    $extension = $file->getClientOriginalExtension();
                    $file_name = time() . rand(100, 999) . '.' . $extension;
                    $path_attached_file = Storage::disk('public')->putFileAs($destination, $file, $file_name);

                    $attention->attached_file = $path_attached_file;
                    $attention->save();
                }


                ComplaintsBook::find($attention->complaints_book_id)
                ->update([
                    'status' => $request->get('status')
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Error al registrar: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->withInput()->with('error', 'Hubo un problema al procesar.');
        }
    }

    public function destroy ($id){
        $message = null;
        $success = false;
        try {
            // Usamos una transacción para asegurarnos de que la operación se realice de manera segura.
            DB::beginTransaction();

            // Verificamos si existe.
            $item = ComplaintsBookAttention::findOrFail($id);

            // Si no hay detalles asociados, eliminamos.
            $item->delete();

            // Si todo ha sido exitoso, confirmamos la transacción.
            DB::commit();

            $message =  'eliminada correctamente';
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
