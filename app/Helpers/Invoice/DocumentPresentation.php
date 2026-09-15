<?php

namespace App\Helpers\Invoice;

use App\Models\SaleDocument;
use Illuminate\Support\Collection;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaSubscriptionType;

final class DocumentPresentation
{
    public const ACADEMIC_ENTITIES = [
        AcaCourse::class,
        AcaSubscriptionType::class,
    ];

    public static function modeForCount(int $count): string
    {
        if ($count >= 2 && $count <= 5) {
            return 'list';
        }

        if ($count > 5) {
            return 'summary';
        }

        return 'default';
    }

    public static function names(iterable $items, string $field = 'title'): array
    {
        return collect($items)->pluck($field)->filter()->values()->all();
    }

    public static function descriptionForItems(iterable $items, string $field = 'title'): string
    {
        $names = self::names($items, $field);
        $count = count($names);

        if ($count <= 1) {
            return $names[0] ?? '';
        }

        if (self::modeForCount($count) === 'summary') {
            return 'Compra de Cursos de Capacitacion' . PHP_EOL . 'Cantidad de cursos: ' . $count;
        }

        return implode(PHP_EOL, $names);
    }

    /**
     * Etiqueta ordinal en espanol para cuotas: "1ra cuota", "2da cuota", "3ra cuota",
     * "4ta cuota", ... "11ma cuota", "12da cuota", "13ra cuota", "21ra cuota", "111ma cuota", ...
     */
    public static function installmentLabel(int $number): string
    {
        $suffixes = [
            1 => 'ra', 2 => 'da', 3 => 'ra', 4 => 'ta', 5 => 'ta',
            6 => 'ta', 7 => 'ma', 8 => 'va', 9 => 'na', 0 => 'ma',
        ];

        // Excepciones: undecima (11ma, 111ma), duodecima (12da, 112da), decimotercera (13ra, 113ra).
        $special = [11 => 'ma', 12 => 'da', 13 => 'ra'];

        // El sufijo depende de las dos ultimas cifras: 11ma, 12da, 13ra, 111ma, 211ma...
        $suffix = $special[$number % 100] ?? $suffixes[$number % 10];

        return $number.$suffix.' cuota';
    }

    public static function academicItemsForDocument($document): Collection
    {
        $dbDocument = SaleDocument::query()
            ->where('invoice_serie', $document->getSerie())
            ->where('invoice_correlative', $document->getCorrelativo())
            ->with('items')
            ->first();

        if (! $dbDocument) {
            return collect();
        }

        return $dbDocument->items
            ->filter(fn ($item) => in_array($item->entity_name_product, self::ACADEMIC_ENTITIES, true))
            ->values();
    }
}
