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
            'bank_transfer_days' => ['nullable', 'integer', 'min:0', 'max:30'],
        ]);

        $data = [
            'billetera_id' => $request->get('billetera_id'),
            'account_name' => $request->get('account_name'),
            'account_number' => $request->get('account_number'),
            'bank_account_id' => $request->get('bank_account_id') ?: null,
            // Sin banco vinculado no hay traslado que programar.
            'bank_transfer_days' => $request->get('bank_account_id')
                ? max(0, (int) $request->get('bank_transfer_days', 0))
                : 0,
            'has_payment_link' => $request->boolean('has_payment_link'),
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
            $billetera = CompanyBilletera::create($data);

            // Crea su cuenta de Tesorería; si falla, la billetera igual queda creada
            try {
                app(\Modules\Treasury\Services\TreasuryAccountSyncService::class)->ensureForWallet($billetera);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[Treasury] No se pudo crear la cuenta de tesorería de la billetera', ['error' => $e->getMessage()]);
            }
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
