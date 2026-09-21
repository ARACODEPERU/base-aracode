<?php

namespace App\Jobs;

use App\Mail\CommercialNegotiationConfirmedMail;
use App\Models\Parameter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Commercial\Entities\CommercialNegotiation;
use App\Models\Person;

/**
 * Notifica por correo la confirmacion de una negociacion.
 *
 * Envia UN SOLO correo: el asesor (creador de la negociacion) va en "Para" y
 * todos los demas destinatarios (administradores, vendedores) en copia oculta
 * (BCC). Antes de existir este job, enviar varios correos seguidos en la misma
 * peticion activaba la proteccion anti-relay del servidor SMTP y la mayoria de
 * los correos se perdia. Con una sola transaccion SMTP ese problema desaparece
 * y la lista de destinatarios puede crecer sin riesgo.
 *
 * La lista de destinatarios adicionales se configura en el parametro PN00001
 * (correos separados por coma, editable desde Configuracion > Parametros).
 *
 * El envio queda en cola (tabla jobs) para que la respuesta al cliente no
 * dependa del servidor de correos.
 */
class SendNegotiationConfirmedNotification implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** Reintentos ante fallos temporales del servidor de correos. */
    public int $tries = 3;

    /** Segundos de espera entre reintentos. */
    public array $backoff = [60, 300];

    public function __construct(
        public int $negotiationId,
        public int $clientId,
        public ?string $asesorEmail,
    ) {
    }

    public function handle(): void
    {
        $negotiation = CommercialNegotiation::find($this->negotiationId);
        $client = Person::find($this->clientId);

        if (! $negotiation || ! $client) {
            return;
        }

        $bcc = $this->bccRecipients();

        $primary = $this->asesorEmail;

        if ($primary === null || ! filter_var($primary, FILTER_VALIDATE_EMAIL)) {
            // Sin asesor con correo valido, el primer destinatario del parametro
            // pasa a "Para" para que el correo nunca salga sin destinatario.
            $primary = array_shift($bcc);
        } else {
            // El asesor no debe repetirse en la copia oculta.
            $bcc = array_values(array_diff($bcc, [strtolower($primary)]));
        }

        if ($primary === null && $bcc === []) {
            Log::warning('Negociacion confirmada sin destinatarios de correo', [
                'negotiation_id' => $this->negotiationId,
            ]);

            return;
        }

        Mail::to($primary)
            ->bcc($bcc)
            ->send(new CommercialNegotiationConfirmedMail($negotiation, $client));
    }

    /**
     * Correos del parametro PN00001: separados por coma, validados y sin duplicados.
     *
     * @return list<string>
     */
    private function bccRecipients(): array
    {
        $raw = Parameter::where('parameter_code', 'PN00001')->value('value_default');

        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $emails = [];

        foreach (explode(',', $raw) as $email) {
            $email = strtolower(trim($email));

            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emails[$email] = true;
            }
        }

        return array_keys($emails);
    }
}
