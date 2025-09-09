<?php

use Illuminate\Support\Facades\DB;

if (!function_exists('getEnumValues')) {
    /**
     * Obtiene los valores de una columna ENUM de una tabla.
     *
     * @param string $table La tabla de la base de datos.
     * @param string $column La columna ENUM.
     * @param bool $sortAlphabetically Opcional. Si es true, ordena los valores alfabéticamente.
     * @param bool $availables Opcional. Si es true, devuelve solo los valores ENUM que existen en las filas de la columna.
     * @return array
     */
    function getEnumValues($table, $column, $sortAlphabetically = false, $availables = false)
    {
        if ($availables) {
            $result = DB::table($table)
                        ->select(DB::raw("DISTINCT {$column}"))
                        ->get()
                        ->pluck($column)
                        ->toArray();
        } else {
            $result = DB::select("SHOW COLUMNS FROM {$table} WHERE Field = '{$column}'");

            if (empty($result)) {
                return [];
            }

            $type = $result[0]->Type;

            preg_match('/^enum\((.*)\)$/', $type, $matches);

            if (!isset($matches[1])) {
                return [];
            }

            $result = array_map(function ($value) {
                return trim($value, "'");
            }, explode(',', $matches[1]));
        }

        if ($sortAlphabetically) {
            sort($result);
        }

        return $result;
    }
}
