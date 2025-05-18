<?php

use Illuminate\Support\Facades\DB;

if (!function_exists('getEnumValues')) {
    function getEnumValues($table, $column, $sortAlphabetically = false)
    {
        $result = DB::select("SHOW COLUMNS FROM {$table} WHERE Field = '{$column}'");

        if (empty($result)) {
            return [];
        }

        $type = $result[0]->Type;

        preg_match('/^enum\((.*)\)$/', $type, $matches);

        $enum = [];

        foreach (explode(',', $matches[1]) as $value) {
            $enum[] = trim($value, "'");
        }

        if ($sortAlphabetically) {
            sort($enum); // Ordena alfabéticamente
        }

        return $enum;
    }
}
