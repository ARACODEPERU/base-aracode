<?php

namespace Modules\Integrationhub\Services;

use Modules\Integrationhub\Entities\IntegrationTelegramMessage;
use Modules\Integrationhub\Support\TelegramMessages;
use RuntimeException;

/**
 * Textos del bot de Telegram: valores por defecto, reemplazos y render.
 *
 * Los textos que se envian salen siempre de aqui, de modo que el webhook y las
 * campanas no tengan copy escrito en el codigo. El catalogo
 * (Support\TelegramMessages) aporta el texto de fabrica y la tabla
 * integration_telegram_messages guarda los cambios del administrador: si no hay
 * fila, se usa el de fabrica.
 */
class TelegramMessageService
{
    /** @var array<string, IntegrationTelegramMessage|null> */
    private array $overrides = [];

    /** true cuando ya se leyo la tabla de reemplazos. */
    private bool $loaded = false;

    /**
     * Texto vigente de un mensaje (el reemplazo o el de fabrica).
     */
    public function body(string $code): string
    {
        $definition = $this->definition($code);
        $override = $this->override($code);

        $body = trim((string) ($override?->body ?? ''));

        return $body !== '' ? $body : (string) $definition['body'];
    }

    /**
     * Formato vigente: 'html' o 'text'.
     */
    public function format(string $code): string
    {
        $definition = $this->definition($code);
        $override = $this->override($code);

        $format = trim((string) ($override?->format ?? ''));

        return $format !== ''
            ? (string) TelegramMessages::normalizeFormat($format)
            : (string) $definition['format'];
    }

    public function isHtml(string $code): bool
    {
        return $this->format($code) === TelegramMessages::FORMAT_HTML;
    }

    /**
     * Texto final de un mensaje, con sus variables reemplazadas.
     *
     * Las lineas que dependen de una variable vacia se descartan: asi el bloque
     * de programas desaparece para quien se registro por suscripcion y el de
     * tiempo cuando la campana no lo lleva. Una variable desconocida se deja
     * escrita, para que el error se note en lugar de pasar en silencio.
     *
     * @param array<string, string|null> $variables
     */
    public function render(string $code, array $variables = []): string
    {
        $values = [];

        foreach ($variables as $name => $value) {
            $values[strtolower((string) $name)] = trim((string) $value);
        }

        $lines = preg_split('/\R/', $this->body($code)) ?: [];
        $kept = [];

        foreach ($lines as $line) {
            if ($this->dependsOnEmptyVariable($line, $values)) {
                continue;
            }

            $kept[] = (string) preg_replace_callback(
                '/\{([a-z0-9_]+)\}/i',
                fn (array $match) => $values[strtolower($match[1])] ?? $match[0],
                $line
            );
        }

        return trim(implode("\n", $kept));
    }

    /**
     * Catalogo completo para la pantalla de configuracion.
     *
     * @return array<int, array<string, mixed>>
     */
    public function catalog(): array
    {
        $catalog = [];

        foreach (TelegramMessages::all() as $code => $definition) {
            $override = $this->override($code);

            $catalog[] = [
                'code' => $code,
                'name' => $definition['name'],
                'description' => $definition['description'],
                'variables' => $definition['variables'],
                'body' => $this->body($code),
                'default_body' => $definition['body'],
                'format' => $this->format($code),
                'default_format' => $definition['format'],
                'is_customized' => $override !== null,
                'updated_at' => $override?->updated_at?->toIso8601String(),
            ];
        }

        return $catalog;
    }

    /**
     * Guarda el texto de un mensaje (body vacio = volver al de fabrica).
     *
     * @throws RuntimeException cuando el codigo no existe.
     */
    public function save(string $code, ?string $body, ?string $format = null, ?int $userId = null): void
    {
        $this->definition($code);

        $body = trim((string) $body);

        IntegrationTelegramMessage::updateOrCreate(
            ['code' => $code],
            [
                'body' => $body === '' ? null : $body,
                'format' => TelegramMessages::normalizeFormat($format),
                'updated_by' => $userId,
            ]
        );

        $this->flush();
    }

    /**
     * Restaura el texto de fabrica de un mensaje.
     */
    public function reset(string $code): void
    {
        IntegrationTelegramMessage::where('code', $code)->delete();

        $this->flush();
    }

    /**
     * Restaura todos los textos de fabrica.
     */
    public function resetAll(): void
    {
        IntegrationTelegramMessage::query()->delete();

        $this->flush();
    }

    /**
     * Cuerpo del mensaje segun la variable vacia de la que dependa la linea.
     *
     * @param array<string, string> $values
     */
    private function dependsOnEmptyVariable(string $line, array $values): bool
    {
        preg_match_all('/\{([a-z0-9_]+)\}/i', $line, $matches);

        foreach ($matches[1] ?? [] as $name) {
            $key = strtolower($name);

            if (array_key_exists($key, $values) && $values[$key] === '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{name: string, description: string, variables: array<int, string>, format: string, body: string}
     *
     * @throws RuntimeException cuando el codigo no existe en el catalogo.
     */
    private function definition(string $code): array
    {
        $definition = TelegramMessages::definition($code);

        if ($definition === null) {
            throw new RuntimeException("El mensaje de Telegram '{$code}' no existe en el catálogo.");
        }

        return $definition;
    }

    private function override(string $code): ?IntegrationTelegramMessage
    {
        if (! $this->loaded) {
            $this->loadOverrides();
        }

        return $this->overrides[$code] ?? null;
    }

    private function loadOverrides(): void
    {
        $this->loaded = true;
        $this->overrides = [];

        foreach (IntegrationTelegramMessage::query()->get() as $message) {
            $this->overrides[(string) $message->code] = $message;
        }
    }

    /**
     * Olvida los reemplazos leidos: el siguiente envio vuelve a consultarlos.
     */
    private function flush(): void
    {
        $this->loaded = false;
        $this->overrides = [];
    }
}
