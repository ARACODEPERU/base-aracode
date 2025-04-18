<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\CRM\Entities\CrmInformationBank;

class CrmInformationBankController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $ibanks = CrmInformationBank::get();
        return Inertia::render('CRM::InformationBank/Index', [
            'ibanks' => $ibanks
        ]);
    }

    public function edit($id)
    {
        $ibank = CrmInformationBank::find($id);
        return Inertia::render('CRM::InformationBank/Edit', [
            'ibank' => $ibank
        ]);
    }
    public function update(Request $request, $id)
    {
        CrmInformationBank::find($id)->update([
            'question_text' => $request->get('question_text') ?? 'Sin pregunta',
            'response_text' => $request->get('response_text') ?? 'Sin respuesta',
            'status' => $request->get('status') ? true : false,
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
            $item = CrmInformationBank::findOrFail($id);

            // Si no hay detalles asociados, eliminamos.
            $item->delete();

            // Si todo ha sido exitoso, confirmamos la transacción.
            DB::commit();

            $message =  'Información eliminado correctamente';
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
