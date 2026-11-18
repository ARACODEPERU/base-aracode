<?php

namespace Modules\Academic\Services;

use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Modules\Academic\Entities\AcaStudent;
use Modules\Academic\Entities\AcaSubscriptionPreapproval;
use Modules\Academic\Entities\AcaSubscriptionType;
use Modules\Academic\Services\MercadoPago\SubscriptionPreapprovalGateway;

/**
 * Alta de la suscripción recurrente en MercadoPago (mensual, trimestral o anual).
 *
 * Usa el token de tarjeta que genera el Brick del checkout (de un solo uso) y
 * crea el preapproval con cobro inmediato. Guarda el estado local y el
 * snapshot fiscal con el que se emitirán los comprobantes de los periodos
 * siguientes.
 */
class SubscriptionPreapprovalService
{
    public function __construct(private SubscriptionPreapprovalGateway $gateway)
    {
    }

    /**
     * Suscripción recurrente vigente del alumno para el plan, si ya existe.
     */
    public function activeFor(int $studentId, int $subscriptionId): ?AcaSubscriptionPreapproval
    {
        return AcaSubscriptionPreapproval::query()
            ->where('student_id', $studentId)
            ->where('subscription_id', $subscriptionId)
            ->whereIn('status', ['authorized', 'paused'])
            ->latest('id')
            ->first();
    }

    /**
     * Crea la suscripción en MercadoPago y su registro local.
     *
     * @param  array<string, mixed>  $datos  Datos del checkout (token, payer, personInvoice).
     *
     * @throws InvalidArgumentException Cuando el plan o el token no sirven para recurrencia.
     */
    public function create(AcaStudent $student, AcaSubscriptionType $plan, array $datos): AcaSubscriptionPreapproval
    {
        if (! $plan->recurring_enabled) {
            throw new InvalidArgumentException('El plan no tiene habilitado el cobro recurrente.');
        }

        $months = $plan->recurringMonths();

        if ($months === null) {
            throw new InvalidArgumentException('Solo se admiten suscripciones mensuales, trimestrales o anuales.');
        }

        $token = trim((string) ($datos['token'] ?? ''));

        if ($token === '') {
            throw new InvalidArgumentException('MercadoPago no generó el token de la tarjeta.');
        }

        // Nunca se cobra dos veces al mismo alumno por el mismo plan.
        $activa = $this->activeFor($student->id, $plan->id);

        if ($activa) {
            return $activa;
        }

        $amount = $this->amount($plan);

        if ($amount <= 0) {
            throw new InvalidArgumentException('El plan no tiene un precio en soles configurado.');
        }

        $email = $this->payerEmail($student, $datos);
        $start = Carbon::now();
        $billingDay = min(28, (int) $start->format('j'));
        $personInvoice = (array) ($datos['personInvoice'] ?? []);
        $externalReference = 'aca:student:' . $student->id . ':plan:' . $plan->id;

        $respuesta = $this->gateway->create([
            'reason' => mb_substr(trim((string) ($plan->title ?: 'Suscripción académica')), 0, 250),
            'external_reference' => $externalReference,
            'payer_email' => $email,
            'card_token_id' => $token,
            'back_url' => rtrim((string) config('app.url'), '/'),
            'status' => 'authorized',
            'auto_recurring' => [
                'frequency' => $months,
                'frequency_type' => 'months',
                'transaction_amount' => round($amount, 2),
                'currency_id' => 'PEN',
                'start_date' => $start->copy()->utc()->format('Y-m-d\TH:i:s.000\Z'),
            ],
        ]);

        return AcaSubscriptionPreapproval::create([
            'student_id' => $student->id,
            'subscription_id' => $plan->id,
            'preapproval_id' => $respuesta['id'],
            'status' => $respuesta['status'],
            'amount' => round($amount, 2),
            'currency_id' => 'PEN',
            'frequency' => $months,
            'frequency_type' => 'months',
            'billing_day' => $billingDay,
            'start_date' => $start->toDateString(),
            'next_payment_date' => $this->nextPaymentDate($respuesta['next_payment_date'] ?? null, $start, $months),
            'payer_email' => $email,
            'external_reference' => $externalReference,
            'invoice_data' => [
                'document_type' => $personInvoice['document_type'] ?? null,
                'razon_social' => $personInvoice['razonSocial'] ?? null,
                'ruc' => $personInvoice['ruc'] ?? null,
                'payer_email' => $email,
                'origen' => 'checkout',
                'capturado_at' => $start->toDateTimeString(),
            ],
            'raw' => $respuesta['raw'] ?? [],
            'last_synced_at' => $start,
        ]);
    }

    /**
     * Precio en soles del plan, venga como arreglo o como JSON.
     */
    public function amount(AcaSubscriptionType $plan): float
    {
        $precios = $plan->prices;

        if (is_string($precios)) {
            $precios = json_decode($precios, true) ?: [];
        }

        $amount = 0.0;

        foreach ((array) $precios as $precio) {
            $currency = is_array($precio) ? ($precio['currency'] ?? null) : ($precio->currency ?? null);
            $monto = is_array($precio) ? ($precio['amount'] ?? 0) : ($precio->amount ?? 0);

            if ($currency === 'PEN') {
                $amount = (float) $monto;
            }
        }

        return $amount;
    }

    /**
     * Fecha del próximo cobro informada por MercadoPago o, si falta, un periodo después.
     */
    private function nextPaymentDate(?string $fecha, Carbon $inicio, int $months): string
    {
        if ($fecha) {
            try {
                return Carbon::parse($fecha)->toDateString();
            } catch (\Throwable $e) {
                // Se usa el cálculo local si MercadoPago devuelve una fecha inválida.
            }
        }

        return $inicio->copy()->addMonths($months)->toDateString();
    }

    /**
     * Correo del pagador: el del checkout o, en su defecto, el de la persona.
     */
    private function payerEmail(AcaStudent $student, array $datos): string
    {
        $candidatos = [
            data_get($datos, 'payer.email'),
            $datos['payer_email'] ?? null,
            $student->person?->email,
        ];

        foreach ($candidatos as $candidato) {
            $candidato = trim((string) $candidato);

            if (filter_var($candidato, FILTER_VALIDATE_EMAIL)) {
                return $candidato;
            }
        }

        throw new InvalidArgumentException('El alumno no tiene un correo válido para la suscripción.');
    }
}
