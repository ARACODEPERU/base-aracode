<?php

namespace App\Http\Controllers;

use App\Models\CompanyBilletera;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CompanyBilleteraController extends Controller
{
    public function storeOrUpdate(Request $request)
    {
        $this->validate($request, [
            'billetera_id' => ['required', 'integer', 'exists:billeteras_digitales,id'],
            'account_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:255'],
            'qr_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:3072'],
            'bank_account_id' => ['nullable', 'integer', 'exists:bank_accounts,id'],
        ]);

        $data = [
            'billetera_id' => $request->get('billetera_id'),
            'account_name' => $request->get('account_name'),
            'account_number' => $request->get('account_number'),
            'bank_account_id' => $request->get('bank_account_id') ?: null,
            'status' => $request->boolean('status'),
        ];

        if ($request->hasFile('qr_image')) {
            $file = $request->file('qr_image');
            $path = $file->store('company/billeteras', 'public');
            $data['qr_image'] = $path;
        }

        if ($request->get('id')) {
            $billetera = CompanyBilletera::findOrFail($request->get('id'));

            // Si se sube una nueva imagen, eliminamos la anterior.
            if (isset($data['qr_image']) && $billetera->qr_image) {
                Storage::disk('public')->delete($billetera->qr_image);
            }

            $billetera->update($data);
        } else {
            CompanyBilletera::create($data);
        }

        return redirect()->route('company_show');
    }

    public function destroy($id)
    {
        $message = null;
        $success = false;

        try {
            DB::transaction(function () use ($id, &$message) {
                $item = CompanyBilletera::findOrFail($id);

                if ($item->qr_image) {
                    Storage::disk('public')->delete($item->qr_image);
                }

                $item->delete();

                $message = 'Billetera digital eliminada correctamente';
                $success = true;
            });
        } catch (\Exception $e) {
            DB::rollBack();
            $success = false;
            $message = $e->getMessage();
        }

        return response()->json([
            'success' => $success,
            'message' => $message,
        ]);
    }
}
