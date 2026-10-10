<?php

namespace App\Support;

/*
|--------------------------------------------------------------------------
| Sitios web de los productos ARACODE
|--------------------------------------------------------------------------
|
| Los datos viven en config/productos.php (nombre, sitio y página de planes de
| cada producto). Aquí solo está la resolución a URL absoluta y los accesos que
| usan las páginas del sitio corporativo.
|
| El día que un producto se copie a su propio dominio, en config/productos.php
| se escribe su URL absoluta y nada más: esta clase respeta las URLs absolutas
| tal cual y resuelve las rutas relativas contra el host actual, así que el
| mismo código funciona en local, en el host de pruebas y en producción.
|
*/

class SitiosProducto
{
    /**
     * Nombre comercial publicable del producto (cadena vacía si no existe).
     */
    public static function nombre(string $producto): string
    {
        return (string) (self::dato($producto, 'nombre') ?? '');
    }

    /**
     * URL absoluta del sitio del producto, o null si no hay ruta configurada.
     */
    public static function url(string $producto): ?string
    {
        return self::absoluta(self::dato($producto, 'sitio'));
    }

    /**
     * URL absoluta de la página de planes del producto.
     *
     * Devuelve null cuando el producto no publica página de planes (por ejemplo
     * Pichanguero), para que el enlace no se renderice.
     */
    public static function planes(string $producto): ?string
    {
        return self::absoluta(self::dato($producto, 'planes'));
    }

    /**
     * URL absoluta de la página de descargas del producto (APK, instaladores).
     *
     * Devuelve null cuando el producto no la tiene configurada.
     */
    public static function descargas(string $producto): ?string
    {
        return self::absoluta(self::dato($producto, 'descargas'));
    }

    /**
     * Dato crudo del producto desde config/productos.php.
     */
    private static function dato(string $producto, string $clave): ?string
    {
        $valor = config("productos.{$producto}.{$clave}");

        return is_string($valor) ? $valor : null;
    }

    /**
     * Una ruta relativa se resuelve contra el host actual; una URL absoluta se
     * respeta tal cual (es lo que se escribe el día del dominio propio).
     */
    private static function absoluta(?string $valor): ?string
    {
        $valor = trim((string) $valor);

        if ($valor === '') {
            return null;
        }

        return str_starts_with($valor, 'http') ? $valor : url($valor);
    }
}
