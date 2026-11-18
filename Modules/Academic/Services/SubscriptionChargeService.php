<?php

namespace Modules\Academic\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\Academic\Entities\AcaStudentSubscription;
use Modules\Academic\Entities\AcaSubscriptionPayment;
use Modules\Academic\Entities\AcaSubscriptionPreapproval;
use Modules\Academic\Entities\AcaSubscriptionTestLog;
use Modules\Onlineshop\Entities\OnliSale;
use Modules\Treasury\Services\TreasuryHooks;

/**
 * Cobros mensuales de una suscripción recurrente.
 *
 * Procesa el cobro autorizado que notifica MercadoPago: concilia el primer
 * cobro creado en el checkout, registra los meses siguientes (venta, ingreso
 * en Tesorería y periodo del alumno) y avisa al alumno. Un rechazo dispara el
 * correo de primer fallo y, si la gracia ya venció, suspende la suscripción.
 *
 * Es idempotente por `authorized_payment_id`: el webhook puede repetirse.
 */
class SubscriptionChargeService
{
    public function __construct(private SubscriptionRecurringNotifier $notifier)
    {
    }

    /**
     * @param  array<string, mixed>  $invoice  Cobro autorizado normalizado.
     * @return string reconciled|charged|already_processed|failed|suspended|pending|ignored
     */
    public function handleAuthorizedPayment(array $invoice): string
    {
        $preapprovalId = trim((string) ($invoice['preapproval_id'] ?? ''));

        if ($preapprovalId === '') {
            return 'ignored';
        }

        $preapproval = AcaSubscriptionPreapproval::where('preapproval_id', $preapprovalId)->first();

        if (! $preapproval) {
            Log::info('[Suscripciones] Cobro de una suscripción que no existe en el sistema.', [
                'preapproval_id' => $preapprovalId,
                'authorized_payment_id' => $invoice['id'] ?? null,
            ]);

            return 'ignored';
        }

        $preapproval->forceFill([
            'last_synced_at' => now(),
            'raw' => $invoice['raw'] ?? $preapproval->raw,
        ])->save();

        $status = strtolower((string) ($invoice['status'] ?? ''));
        $paymentStatus = strtolower((string) data_get($invoice, 'payment.status', ''));

        if ($status === 'approved' || $paymentStatus === 'approved') {
            $resultado = $this->approved($preapproval, $invoice);
        } elseif (in_array($status, ['rejected', 'cancelled'], true)
            || in_array($paymentStatus, ['rejected', 'cancelled'], true)) {
            $resultado = $this->rejected($preapproval, $invoice);
        } else {
            return 'pending';
        }

        // En modo prueba se deja rastro en el panel: así se ve llegar el webhook.
        if ($preapproval->is_test) {
            AcaSubscriptionTestLog::record(
                (int) $preapproval->subscription_id,
                'webhook',
                'Webhook procesado en modo prueba: ' . $resultado . '.',
                ['result' => $resultado, 'authorized_payment_id' => $invoice['id'] ?? null],
                $resultado === 'charged' || $resultado === 'reconciled' ? 'success' : 'warning',
                $preapproval->preapproval_id,
            );
        }

        return $resultado;
    }

    /**
     * Cobro aprobado: concilia el primero o registra un mes nuevo.
     */
    private function approved(AcaSubscriptionPreapproval $preapproval, array $invoice): string
    {
        $authorizedId = (string) ($invoice['id'] ?? '');
        $paidAt = $this->parseDate($invoice['debit_date'] ?? null) ?? now();
        $amount = (float) ($invoice['transaction_amount'] ?? $preapproval->amount);
        $paymentId = data_get($invoice, 'payment.id');

        if ($authorizedId !== '' && AcaSubscriptionPayment::where('authorized_payment_id', $authorizedId)->exists()) {
            $preapproval->forceFill([
                'status' => 'authorized',
                'last_charge_at' => $paidAt,
            ])->save();

            return 'already_processed';
        }

        // Primer cobro: el checkout ya dejó la fila pendiente de conciliar.
        $pendiente = AcaSubscriptionPayment::query()
            ->where('preapproval_id', $preapproval->preapproval_id)
            ->whereNull('authorized_payment_id')
            ->latest('id')
            ->first();

        if ($pendiente) {
            $pendiente->forceFill([
                'authorized_payment_id' => $authorizedId ?: null,
                'payment_id' => $paymentId ? (string) $paymentId : null,
                'status' => 'approved',
                'paid_at' => $paidAt,
                'amount' => $amount > 0 ? $amount : $pendiente->amount,
                'is_test' => (bool) $preapproval->is_test,
            ])->save();

            $preapproval->forceFill([
                'status' => 'authorized',
                'last_charge_at' => $paidAt,
            ])->save();

            return 'reconciled';
        }

        // Mes siguiente: periodo nuevo desde la fecha del cobro sin solapar.
        [$start, $end] = $this->periodFor($preapproval, $paidAt);

        $sale = $this->createSale($preapproval, $invoice, $amount, $paidAt);

        $payment = AcaSubscriptionPayment::create([
            'student_id' => $preapproval->student_id,
            'subscription_id' => $preapproval->subscription_id,
            'date_start' => $start->toDateString(),
            'date_end' => $end->toDateString(),
            'amount' => $amount,
            'authorized_payment_id' => $authorizedId ?: null,
            'payment_id' => $paymentId ? (string) $paymentId : null,
            'preapproval_id' => $preapproval->preapproval_id,
            'status' => 'approved',
            'source' => 'recurring',
            'paid_at' => $paidAt,
            'is_test' => (bool) $preapproval->is_test,
        ]);

        $this->extendStudentSubscription($preapproval, $start, $end);

        $preapproval->forceFill([
            'status' => 'authorized',
            'last_charge_at' => $paidAt,
            'next_payment_date' => $end->copy()->addDay()->toDateString(),
        ])->save();

        $this->notifier->chargeApproved($preapproval, $payment);

        return 'charged';
    }

    /**
     * Cobro rechazado: avisa el primer fallo y suspende al terminar la gracia.
     */
    private function rejected(AcaSubscriptionPreapproval $preapproval, array $invoice): string
    {
        // No se toca next_payment_date: es la fecha de vencimiento con la que se
        // calcula la gracia; el reintento de MercadoPago vive en el cobro.
        $preapproval->forceFill(['last_synced_at' => now()])->save();

        $this->notifier->chargeFailed($preapproval);

        if ($preapproval->hasGraceExpired()) {
            AcaStudentSubscription::query()
                ->where('student_id', $preapproval->student_id)
                ->where('subscription_id', $preapproval->subscription_id)
                ->update(['status' => false]);

            $this->notifier->graceExpired($preapproval);
            $preapproval->forceFill(['status' => 'paused'])->save();

            return 'suspended';
        }

        return 'failed';
    }

    /**
     * Periodo cubierto por el cobro, sin pisar meses ya pagados.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function periodFor(AcaSubscriptionPreapproval $preapproval, Carbon $paidAt): array
    {
        $start = $paidAt->copy()->startOfDay();

        $ultimo = AcaSubscriptionPayment::query()
            ->where('preapproval_id', $preapproval->preapproval_id)
            ->whereNotNull('date_end')
            ->orderByDesc('date_end')
            ->first();

        if ($ultimo && $ultimo->date_end && $ultimo->date_end->greaterThanOrEqualTo($start)) {
            $start = $ultimo->date_end->copy()->addDay()->startOfDay();
        }

        // Si el alumno aún tiene acceso cubierto, el mes nuevo arranca al terminar.
        $suscripcion = AcaStudentSubscription::query()
            ->where('student_id', $preapproval->student_id)
            ->where('subscription_id', $preapproval->subscription_id)
            ->first();

        if ($suscripcion && $suscripcion->date_end) {
            $finActual = Carbon::parse($suscripcion->date_end);

            if ($finActual->greaterThanOrEqualTo($start)) {
                $start = $finActual->copy()->addDay()->startOfDay();
            }
        }

        return [$start, $start->copy()->addMonth()];
    }

    /**
     * Venta online del mes cobrado, para el ingreso en Tesorería.
     */
    private function createSale(AcaSubscriptionPreapproval $preapproval, array $invoice, float $amount, Carbon $paidAt): OnliSale
    {
        $person = $preapproval->student?->person;

        $sale = OnliSale::create([
            'module_name' => 'Academic',
            'person_id' => $person?->id,
            'clie_full_name' => $person?->full_name ?? $preapproval->payer_email,
            'email' => $person?->email ?? $preapproval->payer_email,
            'total' => $amount,
            'response_status' => 'approved',
            'response_date_approved' => $paidAt->toDateString(),
            'response_payer' => json_encode($invoice['payment'] ?? []),
            'response_payment_method_id' => 'mercadopago',
        ]);

        // Campos fuera de $fillable: se asignan directo.
        $sale->mercado_payment_id = data_get($invoice, 'payment.id');
        $sale->mercado_payment = json_encode($invoice['raw'] ?? []);
        $sale->save();

        TreasuryHooks::recordOnlineSaleIncome(
            $sale,
            'Suscripción mensual · ' . mb_substr(trim((string) ($preapproval->subscription?->title ?: 'Plan')), 0, 200)
        );

        return $sale;
    }

    /**
     * Extiende el acceso del alumno hasta el fin del periodo cobrado.
     */
    private function extendStudentSubscription(AcaSubscriptionPreapproval $preapproval, Carbon $start, Carbon $end): void
    {
        // La tabla usa clave primaria compuesta: se actualiza con el query builder
        // porque save()/updateOrCreate() no la soportan.
        $datos = [
            'date_start' => $start->toDateString(),
            'date_end' => $end->toDateString(),
            'status' => true,
            'renewals' => true,
        ];

        $existe = AcaStudentSubscription::query()
            ->where('student_id', $preapproval->student_id)
            ->where('subscription_id', $preapproval->subscription_id)
            ->exists();

        if ($existe) {
            AcaStudentSubscription::query()
                ->where('student_id', $preapproval->student_id)
                ->where('subscription_id', $preapproval->subscription_id)
                ->update($datos);

            return;
        }

        AcaStudentSubscription::create($datos + [
            'student_id' => $preapproval->student_id,
            'subscription_id' => $preapproval->subscription_id,
        ]);
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
