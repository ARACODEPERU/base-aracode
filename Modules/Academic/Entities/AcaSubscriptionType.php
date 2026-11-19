<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Academic\Database\factories\AcaSubscriptionTypeFactory;

class AcaSubscriptionType extends Model
{
    use HasFactory;

    /**
     * Periodos que admiten cobro automático y su duración en meses.
     */
    public const RECURRING_PERIODS = [
        'Mensual' => 1,
        'Trimestral' => 3,
        'Anual' => 12,
    ];

    /**
     * Duración en meses del plan cuando su periodo es recurrente.
     */
    public function recurringMonths(): ?int
    {
        return self::RECURRING_PERIODS[trim((string) $this->period)] ?? null;
    }

    /**
     * Indica si el periodo del plan se puede cobrar con MercadoPago.
     */
    public function supportsRecurring(): bool
    {
        return $this->recurringMonths() !== null;
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'title',
        'usine',
        'description',
        'description_html',
        'details',
        'prices',
        'price_note',
        'price_note_below',
        'show_dollar_price',
        'status',
        'period',
        'order_number',
        'access_all_courses',
        'access_specialization_programs',
        'access_all_webinars',
        'can_view_specialization_zooms',
        'can_view_specialization_webinars',
        'recurring_enabled',
        'test_mode',
        'mercadopago_plan_id',
    ];

    protected $casts = [
        'details' => 'array',
        'prices' => 'array',
        'show_dollar_price' => 'boolean',
        'access_all_courses' => 'boolean',
        'access_specialization_programs' => 'boolean',
        'access_all_webinars' => 'boolean',
        'can_view_specialization_zooms' => 'boolean',
        'can_view_specialization_webinars' => 'boolean',
        'recurring_enabled' => 'boolean',
        'test_mode' => 'boolean',
    ];

    /**
     * Etiquetas y estilos que se conservan en los textos con formato.
     */
    private const RICH_TEXT_TAGS = '<b><strong><i><em><u><s><strike><del><br><span><small><sup><sub>';

    /**
     * Descripción con formato: se sanea antes de guardar.
     */
    public function setDescriptionHtmlAttribute($value): void
    {
        $this->attributes['description_html'] = static::sanitizeRichText($value);
    }

    /**
     * Texto encima del precio: se sanea antes de guardar.
     */
    public function setPriceNoteAttribute($value): void
    {
        $this->attributes['price_note'] = static::sanitizeRichText($value);
    }

    /**
     * Texto debajo del precio: se sanea antes de guardar.
     */
    public function setPriceNoteBelowAttribute($value): void
    {
        $this->attributes['price_note_below'] = static::sanitizeRichText($value);
    }

    /**
     * Sanea el HTML del editor: bloques simples, allowlist de etiquetas y
     * solo los estilos del texto permitidos.
     */
    public static function sanitizeRichText($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $html = trim((string) $value);

        if ($html === '') {
            return null;
        }

        // El contenido de <script> y <style> se descarta completo.
        $html = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $html);

        // Bloques del editor -> saltos simples.
        $html = preg_replace('#<(?:div|p|h[1-6]|li|blockquote)\b[^>]*>#i', '<br>', $html);
        $html = preg_replace('#</(?:div|p|h[1-6]|li|blockquote)>#i', '', (string) $html);

        // Saltos inútiles al inicio y al final; los dobles se conservan.
        $html = preg_replace('#^(?:\s*<br\s*/?>)+#i', '', (string) $html);
        $html = preg_replace('#(?:<br\s*/?>(?:\s|<br\s*/?>)*)+$#i', '', (string) $html);
        $html = preg_replace('#(?:<br\s*/?>\s*){3,}#i', '<br><br>', (string) $html);

        $html = strip_tags((string) $html, self::RICH_TEXT_TAGS);

        // Se descartan todos los atributos menos el style de <span>.
        $html = preg_replace_callback('#<(/?)([a-z]+)\b([^>]*)>#i', function ($matches) {
            $closing = $matches[1] === '/';
            $tag = strtolower($matches[2]);

            if ($closing || $tag === 'br') {
                return '<' . ($closing ? '/' : '') . $tag . '>';
            }

            $style = '';

            if ($tag === 'span' && preg_match('/style\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/i', $matches[3], $styleMatch)) {
                $raw = ($styleMatch[1] ?? '') !== '' ? $styleMatch[1] : ($styleMatch[2] ?? '');
                $style = static::sanitizePriceNoteStyle($raw);
            }

            return '<' . $tag . ($style !== '' ? ' style="' . e($style) . '"' : '') . '>';
        }, (string) $html);

        $html = trim((string) $html);

        return $html === '' ? null : $html;
    }

    /**
     * Filtra las declaraciones de estilo permitidas en un <span>.
     */
    private static function sanitizePriceNoteStyle(string $style): string
    {
        $allowed = [];

        foreach (explode(';', $style) as $declaration) {
            if (!str_contains($declaration, ':')) {
                continue;
            }

            [$property, $value] = array_map('trim', explode(':', $declaration, 2));
            $property = strtolower($property);

            if ($value === '') {
                continue;
            }

            $valid = match ($property) {
                'color' => (bool) preg_match('/^(#[0-9a-f]{3,8}|rgba?\([0-9.,%\s]+\)|[a-z]{3,20})$/i', $value),
                'text-decoration', 'text-decoration-line' => (bool) preg_match('/^(none|underline|line-through|overline)(\s+(underline|line-through|overline))*$/i', $value),
                'font-size' => (bool) preg_match('/^[0-9]+(\.[0-9]+)?(px|rem|em|pt|%)$/', $value),
                'font-weight' => (bool) preg_match('/^(normal|bold|[1-9]00)$/', $value),
                'font-style' => in_array(strtolower($value), ['normal', 'italic'], true),
                default => false,
            };

            if ($valid) {
                $allowed[] = $property . ': ' . $value;
            }
        }

        return implode('; ', $allowed);
    }
}
