<?php

namespace Modules\Sales\Support;

use App\Models\Parameter;

final class SalesA4Template
{
    public const PARAMETER_CODE = 'P000026';

    public const CURRENT = '1';

    public const MODERN = '2';

    public const TEMPLATE_THREE = '3';

    public const TEMPLATE_FOUR = '4';

    public static function current(): string
    {
        return self::normalize(
            Parameter::where('parameter_code', self::PARAMETER_CODE)->value('value_default')
        );
    }

    public static function normalize(mixed $value): string
    {
        $normalized = (string) $value;

        return in_array($normalized, [
            self::CURRENT,
            self::MODERN,
            self::TEMPLATE_THREE,
            self::TEMPLATE_FOUR,
        ], true)
            ? $normalized
            : self::CURRENT;
    }

    public static function noteSaleView(?string $template = null): string
    {
        return match (self::resolveTemplate($template)) {
            self::MODERN => 'sales::sales.A4_pdf_v2',
            self::TEMPLATE_THREE => 'sales::sales.A4_pdf_v3',
            self::TEMPLATE_FOUR => 'sales::sales.A4_pdf_v4',
            default => 'sales::sales.A4_pdf',
        };
    }

    public static function electronicView(string $documentType, ?string $template = null): string
    {
        $isCreditOrDebitNote = in_array($documentType, ['07', '08'], true);
        $resolvedTemplate = self::resolveTemplate($template);

        return match ($resolvedTemplate) {
            self::MODERN => $isCreditOrDebitNote ? 'sales::sales.notas_a4_v2' : 'sales::sales.invoice_a4_v2',
            self::TEMPLATE_THREE => $isCreditOrDebitNote ? 'sales::sales.notas_a4_v3' : 'sales::sales.invoice_a4_v3',
            self::TEMPLATE_FOUR => $isCreditOrDebitNote ? 'sales::sales.notas_a4_v4' : 'sales::sales.invoice_a4_v4',
            default => $isCreditOrDebitNote ? 'sales::sales.notas_a4' : 'sales::sales.invoice_a4',
        };
    }

    /**
     * @return array<int, array<string, string>>
     */
    public static function options(): array
    {
        return [
            [
                'value' => self::CURRENT,
                'label' => 'Formato plantilla 1',
                'description' => 'Mantiene el diseño A4 clásico que ya usa el sistema.',
            ],
            [
                'value' => self::MODERN,
                'label' => 'Formato plantilla 2',
                'description' => 'Usa el diseño A4 renovado, manteniendo el mismo contenido.',
            ],
            [
                'value' => self::TEMPLATE_THREE,
                'label' => 'Formato plantilla 3',
                'description' => 'Usa un diseño A4 alternativo basado en la plantilla 3.',
            ],
            [
                'value' => self::TEMPLATE_FOUR,
                'label' => 'Formato plantilla 4',
                'description' => 'Usa un diseño A4 alternativo basado en la plantilla 4.',
            ],
        ];
    }

    private static function resolveTemplate(mixed $template): string
    {
        return $template === null
            ? self::current()
            : self::normalize($template);
    }
}
