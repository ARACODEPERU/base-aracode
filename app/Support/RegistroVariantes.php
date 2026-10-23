<?php

namespace App\Support;

/*
|--------------------------------------------------------------------------
| Variantes de la pantalla de registro
|--------------------------------------------------------------------------
|
| Resuelve, a partir de la portada publicada (PW00001), qué pantalla de
| registro se muestra. El catálogo vive en config/registro.php y la lógica de
| alta es única: todas las variantes envían el mismo formulario a /register.
|
| Si el valor de la portada no está en el catálogo (una portada retirada, por
| ejemplo), se cae en la variante corporativa en vez de romper la página.
|
*/

final class RegistroVariantes
{
    /**
     * Variante que se usa cuando la portada no tiene una asignada.
     */
    public const POR_DEFECTO = 'corporativo';

    /**
     * Catálogo de variantes, indexado por su clave.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function catalogo(): array
    {
        $variantes = config('registro.variantes');

        return is_array($variantes) ? $variantes : [];
    }

    /**
     * Clave de la variante que corresponde a una portada. Sin argumento usa la
     * portada publicada ahora mismo.
     */
    public static function clave(?string $portada = null): string
    {
        $portada ??= PaginaPrincipal::seleccionada();
        $mapa = config('registro.por_portada');
        $clave = is_array($mapa) ? ($mapa[$portada] ?? null) : null;

        return is_string($clave) && isset(self::catalogo()[$clave])
            ? $clave
            : self::POR_DEFECTO;
    }

    /**
     * Componente Inertia de la variante. Cadena vacía si el catálogo está mal
     * configurado; la prueba de integridad lo detecta antes de publicar.
     */
    public static function componente(?string $portada = null): string
    {
        return (string) (self::catalogo()[self::clave($portada)]['componente'] ?? '');
    }

    /**
     * true cuando la variante permite crear cuentas en esa portada.
     */
    public static function habilitado(?string $portada = null): bool
    {
        return (bool) (self::catalogo()[self::clave($portada)]['habilitado'] ?? false);
    }
}
