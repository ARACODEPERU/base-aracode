<?php

namespace App\Support;

/*
|--------------------------------------------------------------------------
| Servicio de páginas web — planes
|--------------------------------------------------------------------------
|
| Único lector de config/servicio_web.php. La página del servicio
| (/paginas-web) y el bloque de planes del catálogo no leen la config por su
| cuenta: así el día que los precios vengan de la base de datos solo cambia esta
| clase, igual que hace CatalogoPlantillas con el catálogo.
|
| Resuelve los importes como texto para la web («S/ 1,490»), el ahorro del plan
| anual y lo que incluyen todos los planes.
|
*/

class ServicioWeb
{
    /**
     * Planes publicados, en el orden de la config, con los importes formateados.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function planes(): array
    {
        $planes = [];

        foreach ((array) config('servicio_web.planes', []) as $tipo => $plan) {
            $planes[] = self::completar((string) $tipo, (array) $plan);
        }

        return $planes;
    }

    /**
     * Plan de un formato del banco de plantillas (`landing`, `sitio`, `tienda`).
     *
     * Devuelve null cuando el formato no tiene plan: la vista simplemente no
     * publica precio de ese formato, en vez de mostrar una tarjeta vacía.
     *
     * @return array<string, mixed>|null
     */
    public static function plan(string $tipo): ?array
    {
        foreach (self::planes() as $plan) {
            if ($plan['tipo'] === $tipo) {
                return $plan;
            }
        }

        return null;
    }

    /**
     * Lo que trae cualquier plan, sea del formato que sea.
     *
     * @return array<int, string>
     */
    public static function incluyeSiempre(): array
    {
        $lineas = [];

        foreach ((array) config('servicio_web.incluye_siempre', []) as $linea) {
            if (is_string($linea) && trim($linea) !== '') {
                $lineas[] = trim($linea);
            }
        }

        return $lineas;
    }

    /**
     * Cómo se paga (transferencia, Yape o Plin) y qué significa el plan anual.
     */
    public static function notaPago(): string
    {
        $nota = config('servicio_web.nota_pago');

        return is_string($nota) ? trim($nota) : '';
    }

    /**
     * URL absoluta de la página del servicio.
     *
     * La escriben las dos vistas que enlazan a ella (el bloque del catálogo y el
     * cierre de la ficha de cada plantilla), así que vive en un solo lugar.
     */
    public static function url(): string
    {
        return route('plantillas.paginas_web');
    }

    /**
     * Precio de entrada del servicio, con el importe ya formateado.
     *
     * Lo usan el titular de la página del servicio y la tarjeta del ecosistema
     * en /soluciones: ninguna de las dos puede quedarse con el precio de un plan
     * concreto —el que cambie—, sino con el más bajo de los publicados.
     */
    public static function precioDesde(): string
    {
        $importes = array_map(fn (array $plan) => (int) $plan['mensual'], self::planes());
        $importes = array_filter($importes, fn (int $importe) => $importe > 0);

        if ($importes === []) {
            return '';
        }

        return self::precio(min($importes));
    }

    /**
     * Importe como se publica en la web: 1490 → «S/ 1,490».
     *
     * El separador de miles es la única diferencia con el número crudo de la
     * config, pero es lo que se lee en la tarjeta y también lo que viaja en el
     * JSON-LD, así que se resuelve una sola vez, aquí.
     */
    public static function precio(int $importe): string
    {
        return 'S/ '.number_format($importe, 0, '.', ',');
    }

    /**
     * Meses que no se pagan al elegir el año completo: «dos meses gratis».
     *
     * Sale de los importes, no de un texto aparte: si alguien cambia el precio
     * anual, el mensaje se corrige solo y nunca promete un ahorro que no existe.
     */
    public static function mesesGratis(array $plan): int
    {
        $mensual = (int) ($plan['mensual'] ?? 0);
        $anual = (int) ($plan['anual'] ?? 0);

        if ($mensual <= 0 || $anual <= 0) {
            return 0;
        }

        return max(0, 12 - (int) round($anual / $mensual));
    }

    /**
     * Rellena un plan con lo que las vistas necesitan y la config no guarda.
     *
     * @param  array<string, mixed>  $plan
     * @return array<string, mixed>
     */
    private static function completar(string $tipo, array $plan): array
    {
        $mensual = (int) ($plan['mensual'] ?? 0);
        $anual = (int) ($plan['anual'] ?? 0);

        return [
            'tipo' => $tipo,
            'nombre' => (string) ($plan['nombre'] ?? $tipo),
            'resumen' => (string) ($plan['resumen'] ?? ''),
            'ideal_para' => (string) ($plan['ideal_para'] ?? ''),
            'entrega' => (string) ($plan['entrega'] ?? ''),
            'incluye' => array_values((array) ($plan['incluye'] ?? [])),
            'mensual' => $mensual,
            'anual' => $anual,
            'precio_mensual' => self::precio($mensual),
            'precio_anual' => self::precio($anual),
            'destacado' => ($plan['destacado'] ?? false) === true,
            'meses_gratis' => self::mesesGratis(['mensual' => $mensual, 'anual' => $anual]),
        ];
    }
}
