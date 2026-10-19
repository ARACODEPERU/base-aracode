<?php

namespace Modules\Socialevents\Services;

use Illuminate\Support\Facades\DB;

class OrganizationTopUpService
{
    /**
     * Aplica una respuesta consultada directamente a Mercado Pago. Solo un pago
     * aprobado y plenamente coincidente incrementa el saldo de la organización.
     */
    public function creditApprovedPayment(array $payment): bool
    {
        if (($payment['status'] ?? null) !== 'approved') return false;

        return DB::transaction(function () use ($payment) {
            $reference = (string) ($payment['external_reference'] ?? '');
            if ($reference === '') return false;

            $topUp = DB::table('socialevent_organization_topups')
                ->where('external_reference', $reference)
                ->lockForUpdate()
                ->first();
            if (! $topUp || $topUp->status === 'credited') return false;
            if (strtoupper((string) ($payment['currency_id'] ?? '')) !== 'PEN') return false;
            if (abs((float) ($payment['transaction_amount'] ?? 0) - (float) $topUp->amount) > 0.01) return false;
            $providerPaymentType = (string) ($payment['payment_type_id'] ?? '');
            $providerMethod = (string) ($payment['payment_method_id'] ?? '');
            if ($topUp->payment_method === 'yape' && $providerMethod !== 'yape') return false;
            if ($topUp->payment_method === 'card' && ! in_array($providerPaymentType, ['credit_card', 'debit_card', 'prepaid_card'], true)) return false;

            $providerPaymentId = (string) ($payment['id'] ?? '');
            if ($providerPaymentId === '') return false;
            $duplicate = DB::table('socialevent_organization_topups')
                ->where('provider_payment_id', $providerPaymentId)
                ->where('id', '!=', $topUp->id)
                ->exists();
            if ($duplicate) return false;

            $organization = DB::table('socialevent_organizations')
                ->where('id', $topUp->organization_id)
                ->lockForUpdate()
                ->first();
            if (! $organization) return false;

            $balance = round((float) $organization->prepaid_balance + (float) $topUp->amount, 2);
            DB::table('socialevent_organizations')->where('id', $organization->id)->update([
                'prepaid_balance' => $balance,
                'updated_at' => now(),
            ]);
            DB::table('socialevent_organization_ledger')->insert([
                'organization_id' => $organization->id,
                'user_id' => $topUp->user_id,
                'entry_type' => 'balance_topup',
                'amount' => $topUp->amount,
                'balance_after' => $balance,
                'payment_method' => $topUp->payment_method,
                'reference' => $providerPaymentId,
                'description' => 'Recarga de saldo aprobada por Mercado Pago.',
                'metadata' => json_encode(['topup_id' => $topUp->id, 'external_reference' => $topUp->external_reference]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('socialevent_organization_topups')->where('id', $topUp->id)->update([
                'provider_payment_id' => $providerPaymentId,
                'status' => 'credited',
                'credited_at' => now(),
                'updated_at' => now(),
            ]);

            return true;
        });
    }
}
