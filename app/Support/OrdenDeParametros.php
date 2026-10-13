<?php

namespace App\Support;

/*
|--------------------------------------------------------------------------
| Orden de los parámetros por prefijo
|--------------------------------------------------------------------------
|
| Los códigos de los parámetros empiezan por letras que dicen a qué grupo
| pertenecen (P de generales, PW de página web, SC de credenciales, PC, PN,
| PTM…) y siguen con un número. Ordenados como texto, «P000023» y «PW00001» se
| mezclan por culpa de los dígitos; aquí el prefijo manda: primero las letras,
| después el número dentro de cada prefijo.
|
| Lo consume la pantalla de Parámetros del sistema (ParametersController), que
| también publica el prefijo de cada fila para las pestañas de la vista.
|
*/

class OrdenDeParametros
{
    /**
     * Prefijo del código: las letras del principio, en mayúsculas.
     *
     * 'P000023' → 'P', 'PW00001' → 'PW', 'SC-00001' → 'SC', 'pc00004' → 'PC'.
     * Un código sin letras al principio devuelve cadena vacía y se ordena antes
     * que los demás (es lo que se ve como «sin prefijo»).
     */
    public static function prefijo(?string $codigo): string
    {
        preg_match('/^[A-Za-z]+/', trim((string) $codigo), $coincidencias);

        return strtoupper($coincidencias[0] ?? '');
    }

    /**
     * Número del código: el primer grupo de dígitos, para ordenar dentro de un
     * mismo prefijo ('P000004' antes que 'P000026'). Sin dígitos devuelve 0.
     */
    public static function numero(?string $codigo): int
    {
        preg_match('/\d+/', trim((string) $codigo), $coincidencias);

        return (int) ($coincidencias[0] ?? 0);
    }

    /**
     * Devuelve las filas ordenadas por prefijo, luego por número y, para
     * desempatar, por el código completo.
     *
     * Vale tanto para arrays como para modelos (lee `parameter_code` con
     * data_get), así que sirve antes y después de formatear los parámetros.
     *
     * @param  iterable<mixed>  $parametros
     * @return array<int, mixed>
     */
    public static function ordenar(iterable $parametros, string $campo = 'parameter_code'): array
    {
        $filas = is_array($parametros) ? array_values($parametros) : iterator_to_array($parametros, false);

        usort($filas, function ($a, $b) use ($campo) {
            $codigoA = (string) data_get($a, $campo);
            $codigoB = (string) data_get($b, $campo);

            return [
                self::prefijo($codigoA), self::numero($codigoA), $codigoA,
            ] <=> [
                self::prefijo($codigoB), self::numero($codigoB), $codigoB,
            ];
        });

        return $filas;
    }
}
