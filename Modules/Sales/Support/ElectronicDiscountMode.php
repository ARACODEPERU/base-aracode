<?php

namespace Modules\Sales\Support;

final class ElectronicDiscountMode
{
    public const PARAMETER_CODE = 'P000025';

    public const EMBEDDED_PRICE = 'embedded_price';

    public const ITEM_DISCOUNT = 'item_discount';

    public static function resolve(mixed $value): string
    {
        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            '2', 'true', 'item_discount', 'send_discount', 'sunat_discount' => self::ITEM_DISCOUNT,
            default => self::EMBEDDED_PRICE,
        };
    }

    public static function usesItemDiscount(mixed $value): bool
    {
        return self::resolve($value) === self::ITEM_DISCOUNT;
    }
}
