<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BankAccountController extends Controller
{
    public function storeOrUpdate(Request $request)
    {
        $this->validate($request, [
            'bank_id' => 'required',
            'description' => 'required|max:255',
            'number' => 'required|max:255',
            'cci'  => 'required|max:255',
            'currency_type_id'  => 'required|string',
        ]);

        if ($request->get('id')) {
            BankAccount::find($request->get('id'))->update([
                'bank_id'               => $request->get('bank_id'),
                'description'           => $request->get('description'),
                'number'                => $request->get('number'),
                'cci'                   => $request->get('cci'),
                'currency_type_id'      => $request->get('currency_type_id'),
                'status'                => $request->get('status') ? true : false,
                'invoice_show'          => $request->get('invoice_show') ? true : false,
            ]);
        } else {
            BankAccount::create([
                'bank_id'               => $request->get('bank_id'),
                'description'           => $request->get('description'),
                'number'                => $request->get('number'),
                'cci'                   => $request->get('cci'),
                'currency_type_id'      => $request->get('currency_type_id'),
                'status'                => true,
                'invoice_show'          => $request->get('invoice_show') ? true : false,
            ]);
        }
    }

    public function destroy($id)
    {

        $message = null;
        $success = false;
        try {
            // Usamos una transacción para asegurarnos de que la operación se realice de manera segura.
            DB::beginTransaction();

            // Verificamos si existe.
            $item = BankAccount::findOrFail($id);

            // Si no hay detalles asociados, eliminamos.
            $item->delete();

            // Si todo ha sido exitoso, confirmamos la transacción.
            DB::commit();

            $message =  'Cuenta eliminada correctamente';
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
