<?php

namespace Modules\Security\Services;

use App\Models\ContactMessage;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\Log;
use Modules\Security\Jobs\SendContactMessageTelegram;
use Throwable;

/**
 * Avisa por Telegram cuando alguien envía el formulario de contacto del sitio.
 *
 * Usa el mismo bot que las alertas del sistema (integración Telegram_bot) y los
 * destinatarios activos de Seguridad → Alertas, de modo que quien ya recibe los
 * avisos del sistema recibe también los contactos. No depende del interruptor
 * de alertas de error: ese silencia los avisos de fallos, no los contactos.
 *
 * Reglas:
 *   - el mensaje se encola: el formulario responde sin esperar a Telegram;
 *   - nunca lanza: el contacto ya quedó guardado y con su correo, así que un
 *     fallo de Telegram no puede hacer fallar el envío;
 *   - escapa el texto de la persona: en parse_mode HTML una etiqueta suelta
 *     rompería el mensaje (o lo formatearía a su antojo);
 *   - no avisa si no hay destinatarios activos.
 */
class ContactMessageTelegramNotifier
{
    public function __construct(private readonly ErrorAlertService $alerts)
    {
    }

    /**
     * Encola el aviso del mensaje a los destinatarios activos.
     */
    public function notify(ContactMessage $message): void
    {
        try {
            $chatIds = $this->alerts->activeChatIds();

            if ($chatIds === []) {
                return;
            }

            $text = $this->text($message);

            if (trim($text) === '') {
                return;
            }

            // Se encola por el bus y no con dispatch(): el helper entrega el job
            // al destruirse el PendingDispatch, fuera de este try/catch.
            app(Dispatcher::class)->dispatch(new SendContactMessageTelegram($chatIds, $text));
        } catch (Throwable $exception) {
            $this->logFailure($exception);
        }
    }

    /**
     * Texto del aviso, con los datos de la persona escapados para HTML.
     */
    public function text(ContactMessage $message): string
    {
        $lines = [
            '📩 <b>Nuevo mensaje de contacto</b>',
            '',
            '<b>Nombre:</b> ' . $this->escape($message->name),
            '<b>Email:</b> ' . $this->escape($message->email),
        ];

        // Teléfono y empresa son opcionales: si no llegaron, la línea no se pinta.
        foreach (['Teléfono' => $message->phone, 'Empresa' => $message->company] as $label => $value) {
            $value = trim((string) $value);

            if ($value !== '') {
                $lines[] = "<b>{$label}:</b> " . $this->escape($value);
            }
        }

        // El formulario guarda la clave del servicio ("automatizacion"); se
        // muestra con mayúscula inicial, igual que el correo al admin.
        $lines[] = '<b>Servicio:</b> ' . $this->escape(ucfirst((string) $message->service));
        $lines[] = '<b>Fecha:</b> ' . ($message->created_at?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i'));
        $lines[] = '';
        $lines[] = '<b>Mensaje:</b>';
        $lines[] = $this->escape((string) $message->message);

        return implode("\n", $lines);
    }

    /**
     * Escapa el texto de la persona sin romper las etiquetas del propio aviso.
     */
    private function escape(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Deja constancia sin disparar una alerta de error (marcador interno).
     */
    private function logFailure(Throwable $exception): void
    {
        try {
            Log::warning('Contacto: no se pudo avisar por Telegram', [
                ErrorAlertService::CONTEXT_MARKER => true,
                'error' => $exception->getMessage(),
            ]);
        } catch (Throwable) {
            // Sin log disponible el formulario sigue funcionando igual.
        }
    }
}
