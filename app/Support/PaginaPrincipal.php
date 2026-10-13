<?php

namespace App\Support;

use App\Models\Parameter;

/*
|--------------------------------------------------------------------------
| Página principal del dominio
|--------------------------------------------------------------------------
|
| Resuelve qué portada se publica en la raíz del dominio («/»). El catálogo de
| portadas disponibles vive en config/pagina_principal.php y la elección del
| usuario en el parámetro PW00001 («Pagina web principal»), que se edita desde
| Parámetros.
|
| Aquí solo está la lectura de esa elección y sus accesos: qué vista toca, qué
| opciones ofrece el desplegable y en qué URL se puede ver la portada entera
| antes de publicarla. Las vistas y el controlador nunca leen config ni la base
| de datos por su cuenta.
|
| Un valor guardado que no exista en el catálogo (por ejemplo, una portada que
| se retiró) NO rompe el sitio: se cae a la portada por defecto en vez de
| devolver un error.
|
*/

class PaginaPrincipal
{
    /**
     * Parámetro que guarda la portada elegida.
     */
    public const PARAMETRO = 'PW00001';

    /**
     * Portada que se publica cuando no hay elección válida.
     */
    public const POR_DEFECTO = '1';

    /**
     * Portada de torneos: es la única que necesita datos de la base de datos
     * para renderizarse, así que el controlador la reconoce por su valor.
     */
    public const TORNEOS = '2';

    /**
     * Catálogo de portadas disponibles, indexado por el valor que se guarda.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function paginas(): array
    {
        $paginas = config('pagina_principal.paginas');

        return is_array($paginas) ? $paginas : [];
    }

    /**
     * Indicador de la portada publicándose ahora mismo en la raíz.
     */
    public static function seleccionada(): string
    {
        $valor = trim((string) Parameter::where('parameter_code', self::PARAMETRO)
            ->value('value_default'));

        return self::existe($valor) ? $valor : self::POR_DEFECTO;
    }

    /**
     * Datos de una portada del catálogo. Sin argumento usa la publicada, para
     * no volver a leer el parámetro cuando ya se tiene el valor.
     *
     * @return array<string, mixed>
     */
    public static function pagina(?string $valor = null): array
    {
        return self::paginas()[$valor ?? self::seleccionada()] ?? [];
    }

    /**
     * Vista Blade que responde en la raíz del dominio.
     */
    public static function vista(?string $valor = null): string
    {
        return (string) (self::pagina($valor)['vista'] ?? '');
    }

    /**
     * true cuando la portada publicada es la de torneos (necesita datos).
     */
    public static function esTorneos(): bool
    {
        return self::seleccionada() === self::TORNEOS;
    }

    /**
     * Opciones del desplegable, en el formato que espera la lista de parámetros
     * (control_type «sa»): value es el valor guardado y label lo que se lee.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function opciones(): array
    {
        $opciones = [];

        foreach (self::paginas() as $valor => $pagina) {
            $opciones[] = [
                'value' => (string) $valor,
                'label' => self::nombre((string) $valor),
            ];
        }

        return $opciones;
    }

    /**
     * Las opciones ya codificadas como las guarda el parámetro PW00001.
     */
    public static function opcionesJson(): string
    {
        return json_encode(self::opciones(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '[]';
    }

    /**
     * Nombre publicable de una portada (cadena vacía si el valor no existe).
     */
    public static function nombre(string $valor): string
    {
        return (string) (self::paginas()[$valor]['nombre'] ?? '');
    }

    /**
     * URL donde se puede ver la portada completa antes de publicarla en la raíz.
     *
     * Las portadas del sitio corporativo solo existen en la raíz, así que no
     * tienen URL propia y devuelven null (no se inventa un enlace). Las de
     * producto apuntan al sitio del producto, resuelto con SitiosProducto para
     * que el día del dominio propio el enlace siga siendo correcto.
     */
    public static function url(string $valor): ?string
    {
        $producto = self::paginas()[$valor]['producto'] ?? null;

        return is_string($producto) ? SitiosProducto::url($producto) : null;
    }

    /**
     * true cuando el valor guardado corresponde a una portada del catálogo.
     */
    private static function existe(string $valor): bool
    {
        return $valor !== '' && isset(self::paginas()[$valor]);
    }
}
