<?php

namespace Modules\Academic\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Academic\Emails\SubscriptionChargeFailed;
use Modules\Academic\Emails\SubscriptionGraceExpired;
use Modules\Academic\Emails\SubscriptionRenewed;
use Modules\Academic\Entities\AcaSubscriptionPayment;
use Modules\Academic\Entities\AcaSubscriptionPreapproval;

/**
 * Correos de los cobros recurrentes mensuales.
 *
 * Lo llama el manejador de cobros de MercadoPago (Fase 3) al registrar un cobro
 * aprobado, el primer rechazo y el fin de los días de gracia. Cada aviso se
 * marca para no repetirse cuando llega la misma notificación otra vez.
 *
 * Un fallo de correo nunca debe tumbar el cobro: se registra y se devuelve false.
 */
class SubscriptionRecurringNotifier
{
    /**
     * Avisa al alumno que su suscripción se renovó y limpia las marcas de fallo
     * para que el próximo incidente vuelva a notificarse.
     */
    public function chargeApproved(AcaSubscriptionPreapproval $preapproval, AcaSubscriptionPayment $payment): bool
    {
        $preapproval->forceFill([
            'status' => 'authorized',
            'first_failure_notified_at' => null,
            'grace_expired_notified_at' => null,
        ])->save();

        if ($payment->renewal_notified_at) {
            return false;
        }

        $enviado = $this->send(
            $preapproval,
            fn (string $email) => Mail::to($email)->queue(new SubscriptionRenewed(
                $preapproval,
                $payment,
                $this->studentName($preapproval),
            )),
        );

        if ($enviado) {
            $payment->forceFill(['renewal_notified_at' => now()])->save();
        }

        return $enviado;
    }

    /**
     * Avisa del primer intento de cobro rechazado. Los reintentos posteriores
     * no vuelven a enviar este correo.
     */
    public function chargeFailed(AcaSubscriptionPreapproval $preapproval): bool
    {
        if ($preapproval->first_failure_notified_at) {
            return false;
        }

        $enviado = $this->send(
            $preapproval,
            fn (string $email) => Mail::to($email)->queue(new SubscriptionChargeFailed(
                $preapproval,
                $this->studentName($preapproval),
                $this->graceDays(),
            )),
        );

        if ($enviado) {
            $preapproval->forceFill(['first_failure_notified_at' => now()])->save();
        }

        return $enviado;
    }

    /**
     * Avisa que terminaron los días de gracia y la suscripción quedó suspendida.
     */
    public function graceExpired(AcaSubscriptionPreapproval $preapproval): bool
    {
        if ($preapproval->grace_expired_notified_at) {
            return false;
        }

        $enviado = $this->send(
            $preapproval,
            fn (string $email) => Mail::to($email)->queue(new SubscriptionGraceExpired(
                $preapproval,
                $this->studentName($preapproval),
                $this->graceDays(),
            )),
        );

        if ($enviado) {
            $preapproval->forceFill(['grace_expired_notified_at' => now()])->save();
        }

        return $enviado;
    }

    /**
     * Días de reintento configurados (3 por defecto).
     */
    public function graceDays(): int
    {
        return max(0, (int) config('services.mercadopago.subscription_grace_days', 3));
    }

    /**
     * Ejecuta el envío con el destinatario resuelto y captura cualquier fallo.
     */
    private function send(AcaSubscriptionPreapproval $preapproval, callable $enviar): bool
    {
        $email = $this->recipient($preapproval);

        if ($email === null) {
            Log::warning('Suscripción recurrente sin correo de alumno para notificar.', [
                'preapproval_id' => $preapproval->preapproval_id,
                'student_id' => $preapproval->student_id,
            ]);

            return false;
        }

        try {
            $enviar($email);

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    /**
     * Correo del alumno; si no lo tiene, el correo del pagador en MercadoPago.
     */
    private function recipient(AcaSubscriptionPreapproval $preapproval): ?string
    {
        $candidatos = [
            $preapproval->student?->person?->email,
            $preapproval->payer_email,
        ];

        foreach ($candidatos as $candidato) {
            $candidato = trim((string) $candidato);

            if (filter_var($candidato, FILTER_VALIDATE_EMAIL)) {
                return $candidato;
            }
        }

        return null;
    }

    private function studentName(AcaSubscriptionPreapproval $preapproval): string
    {
        return $preapproval->student?->person?->full_name ?: 'Estimado estudiante';
    }
}
