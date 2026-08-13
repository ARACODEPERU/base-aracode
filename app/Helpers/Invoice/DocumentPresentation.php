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
