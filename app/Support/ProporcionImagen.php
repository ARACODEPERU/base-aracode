<?php

namespace App\Support;

/**
 * Proporción de una imagen del sitio, lista para CSS.
 *
 * Las tarjetas de producto reservan el alto de su franja con `aspect-ratio`, y
 * desde que los diseños del catálogo son banners (960 × 600) la franja tiene que
 * medir EXACTAMENTE lo que mide la imagen: ni un hueco de más —que se veía como
 * una banda de relleno sobre y bajo el diseño— ni un salto al cargar, que es lo
 * que pasa cuando la proporción se deja que la decida el navegador.
 *
 * Devuelve el valor tal como lo espera la hoja de estilos (`960 / 600`), o null
 * cuando el archivo no se puede medir: una URL externa, o un archivo que no está
 * en esta copia del proyecto. En ese caso la franja cae a su proporción por
 * defecto y `object-fit: contain` se encarga de no recortar nada.
 */
class ProporcionImagen
{
    /**
     * Proporción de la imagen que hay detrás de una URL del sitio.
     *
     * @param  string|null  $url  URL absoluta o ruta del sitio (la que produce `asset()`).
     * @return string|null  `960 / 600` para CSS, o null si no se puede medir.
     */
    public static function de(?string $url): ?string
    {
        $archivo = self::archivo($url);

        if (! $archivo) {
            return null;
        }

        $medidas = @getimagesize($archivo);

        if (! $medidas || $medidas[0] < 1 || $medidas[1] < 1) {
            return null;
        }

        return $medidas[0].' / '.$medidas[1];
    }

    /**
     * Archivo de disco de una URL del sitio, o null si la URL es externa o el
     * archivo no existe.
     *
     * Se descuenta la raíz de los assets (`asset('')`) antes de resolver contra
     * `public/`, para que siga funcionando si algún día el sitio se sirve desde
     * un subdirectorio. Las URL externas se ignoran a propósito: medirlas
     * obligaría a pedirlas por red en cada render.
     */
    private static function archivo(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $ruta = parse_url($url, PHP_URL_PATH);

        if (! is_string($ruta) || $ruta === '') {
            return null;
        }

        $raiz = rtrim((string) parse_url(asset(''), PHP_URL_PATH), '/');

        if ($raiz !== '' && str_starts_with($ruta, $raiz)) {
            $ruta = substr($ruta, strlen($raiz));
        }

        $archivo = public_path(ltrim($ruta, '/'));

        return is_file($archivo) ? $archivo : null;
    }
}
