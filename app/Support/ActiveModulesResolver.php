<?php

namespace App\Support;

use App\Models\Modulo;
use App\Models\Parameter;

class ActiveModulesResolver
{
    /**
     * @return array<int, array{identifier: string, description: string, icon: ?string}>
     */
    public static function forLogin(): array
    {
        $parameter = Parameter::query()
            ->where('parameter_code', 'P000027')
            ->first();

        if (! $parameter) {
            return [];
        }

        $identifiers = self::parseMultiValue($parameter->value_default);

        if ($identifiers === []) {
            return [];
        }

        return Modulo::query()
            ->whereIn('identifier', $identifiers)
            ->where('status', true)
            ->orderBy('description')
            ->get(['identifier', 'description', 'icon'])
            ->map(fn (Modulo $module) => [
                'identifier' => $module->identifier,
                'description' => $module->description,
                'icon' => $module->icon,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function parseMultiValue(?string $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            return array_values(array_map('strval', $decoded));
        }

        if (preg_match_all("/['\"]([^'\"]+)['\"]/", $value, $matches)) {
            return array_values($matches[1]);
        }

        return [(string) $value];
    }
}
