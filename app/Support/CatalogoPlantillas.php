<?php

namespace App\Support;

use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Banco de Plantillas Web — catálogo
|--------------------------------------------------------------------------
|
| Único lector de config/plantillas.php. La sección (controlador y vistas) no
| lee la config por su cuenta: así el día que el catálogo venga de la base de
| datos solo cambia esta clase, igual que SitiosProducto con los sitios de los
| productos.
|
| Resuelve tres cosas:
|
|  - Los datos publicados de cada plantilla (`todas`, `porSlug`).
|  - El filtrado del catálogo (categoría y búsqueda) en memoria: el catálogo es
|    de decenas de plantillas, no de miles.
|  - Las URLs (imagen, ficha y demostración), respetando las rutas relativas
|    mientras la demo vive en este repositorio y las URLs absolutas el día que
|    se mude a su propio subdominio.
|
*/

class CatalogoPlantillas
{
    /**
     * Categorías publicadas, con el total de plantillas de cada una.
     *
     * @return array<string, array{nombre: string, descripcion: string, total: int}>
     */
    public static function categorias(): array
    {
        $categorias = [];
        $plantillas = self::todas();

        foreach ((array) config('plantillas.categorias', []) as $slug => $categoria) {
            $categorias[(string) $slug] = [
                'nombre' => (string) ($categoria['nombre'] ?? $slug),
                'descripcion' => (string) ($categoria['descripcion'] ?? ''),
                'total' => count(array_filter(
                    $plantillas,
                    fn (array $plantilla) => $plantilla['categoria'] === (string) $slug
                )),
            ];
        }

        return $categorias;
    }

    /**
     * Tipos (formatos) publicados, con el total de plantillas de cada uno.
     *
     * `landing` o `sitio web`: es el dato con el que el equipo arma el plan del
     * cliente, así que el catálogo lo muestra en cada tarjeta. Como en
     * categorias(), los totales salen de todas(), y cada campo se lee de la
     * config con datoTipo(): encadenarlo con un método que volviera a llamar a
     * todas() sería la recursión infinita que ya tuvo nombreCategoria().
     *
     * @return array<string, array{nombre: string, hashtag: string, descripcion: string, total: int}>
     */
    public static function tipos(): array
    {
        $tipos = [];
        $plantillas = self::todas();

        foreach (array_keys((array) config('plantillas.tipos', [])) as $slug) {
            $clave = (string) $slug;

            $tipos[$clave] = [
                'nombre' => self::datoTipo($clave, 'nombre'),
                'hashtag' => self::datoTipo($clave, 'hashtag'),
                'descripcion' => self::datoTipo($clave, 'descripcion'),
                'total' => count(array_filter(
                    $plantillas,
                    fn (array $plantilla) => $plantilla['tipo'] === $clave
                )),
            ];
        }

        return $tipos;
    }

    /**
     * ¿Existe la categoría? Se comprueba contra la config para que una URL con
     * una categoría retirada no rompa la página (simplemente no filtra).
     */
    public static function existeCategoria(string $slug): bool
    {
        return array_key_exists($slug, self::categorias());
    }

    /**
     * Nombre publicable de una categoría.
     *
     * Lee la config directo y no pasa por categorias(): esa lista se arma con
     * todas(), que a su vez rellena cada plantilla llamando a este método, así
     * que encadenarlos era una recursión infinita (agotaba la memoria y el
     * catálogo respondía 500 sin mensaje).
     */
    public static function nombreCategoria(string $slug): string
    {
        return (string) config('plantillas.categorias.'.$slug.'.nombre', $slug);
    }

    /**
     * Todas las plantillas publicadas, con sus URLs ya resueltas y las
     * destacadas primero.
     *
     * Las destacadas se ordenan, no se repiten en un bloque aparte: repetir la
     * misma tarjeta dos veces en la página alarga el catálogo y compite consigo
     * mismo. El orden dentro de cada grupo es el de config/plantillas.php, que
     * es el que decide quien mantiene el catálogo.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function todas(): array
    {
        $destacadas = [];
        $resto = [];

        foreach ((array) config('plantillas.plantillas', []) as $slug => $datos) {
            if (($datos['publicada'] ?? true) !== true) {
                continue;
            }

            $plantilla = self::completar((string) $slug, (array) $datos);

            // Dos listas y luego una unión: el orden de la config se conserva
            // dentro de cada grupo, sin depender de que usort sea estable.
            if ($plantilla['destacada']) {
                $destacadas[] = $plantilla;
            } else {
                $resto[] = $plantilla;
            }
        }

        return array_merge($destacadas, $resto);
    }

    /**
     * Catálogo filtrado. Ambos filtros son opcionales y se combinan.
     *
     * La búsqueda ignora mayúsculas y tildes: «clinica» encuentra «Clínica»,
     * que es como se busca de verdad. Si la categoría no existe se devuelve el
     * catálogo completo en vez de una página vacía.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function filtrar(?string $categoria = null, ?string $busqueda = null): array
    {
        $plantillas = self::todas();

        if ($categoria !== null && $categoria !== '' && self::existeCategoria($categoria)) {
            $plantillas = array_values(array_filter(
                $plantillas,
                fn (array $plantilla) => $plantilla['categoria'] === $categoria
            ));
        }

        $busqueda = trim((string) $busqueda);

        if ($busqueda !== '') {
            $aguja = self::normalizar($busqueda);

            $plantillas = array_values(array_filter(
                $plantillas,
                function (array $plantilla) use ($aguja) {
                    // Se busca sobre lo que el visitante ve: nombre, resumen,
                    // etiquetas, categoría y formato. Incluir aquí el formato es
                    // lo que permite escribir «landing» en el buscador y ver solo
                    // las landings, que es como busca el cliente que ya sabe qué
                    // plan quiere.
                    $heno = self::normalizar(implode(' ', array_merge(
                        [
                            $plantilla['nombre'],
                            $plantilla['resumen'],
                            $plantilla['categoria_nombre'],
                            $plantilla['tipo_nombre'],
                            $plantilla['tipo_hashtag'],
                        ],
                        $plantilla['etiquetas']
                    )));

                    return str_contains($heno, $aguja);
                }
            ));
        }

        return $plantillas;
    }

    /**
     * Plantilla publicada por su slug (la de la URL de la ficha).
     *
     * @return array<string, mixed>|null
     */
    public static function porSlug(string $slug): ?array
    {
        foreach (self::todas() as $plantilla) {
            if ($plantilla['slug'] === $slug) {
                return $plantilla;
            }
        }

        return null;
    }

    /**
     * Otras plantillas de la misma categoría (para el «también te puede
     * interesar» de la ficha).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function deLaMismaCategoria(string $slug, int $limite = 3): array
    {
        $plantilla = self::porSlug($slug);

        if ($plantilla === null) {
            return [];
        }

        $hermanas = array_values(array_filter(
            self::filtrar($plantilla['categoria']),
            fn (array $otra) => $otra['slug'] !== $slug
        ));

        return array_slice($hermanas, 0, $limite);
    }

    /**
     * URL absoluta de la ficha de la plantilla.
     */
    public static function urlFicha(array $plantilla): string
    {
        return route('plantillas.ficha', $plantilla['slug']);
    }

    /**
     * URL de la imagen de la plantilla, o null si todavía no hay captura.
     */
    public static function urlImagen(array $plantilla): ?string
    {
        return self::absoluta($plantilla['imagen']);
    }

    /**
     * URL absoluta de la imagen de portada de una categoría (mosaico).
     */
    public static function urlCategoria(string $categoria): string
    {
        return url('/plantillas?categoria='.$categoria);
    }

    /**
     * ¿La demostración está disponible de verdad?
     *
     * Mientras la demo viva en este repositorio se comprueba que su archivo de
     * entrada exista: así una plantilla a medio preparar no publica un botón que
     * lleva a un 404. Con una URL absoluta (subdominio propio) se da por buena:
     * ese servidor no está al alcance de este código.
     */
    public static function tieneDemo(array $plantilla): bool
    {
        $demo = trim((string) ($plantilla['demo'] ?? ''));

        if ($demo === '') {
            return false;
        }

        if (str_starts_with($demo, 'http')) {
            return true;
        }

        return is_file(public_path('demos/'.$demo.'/index.html'));
    }

    /**
     * URL absoluta de la demostración, o null si no está disponible.
     *
     * La carpeta de la config es relativa a `public/demos/` (ver el encabezado
     * de config/plantillas.php), así que se compone con ese prefijo; una URL
     * absoluta se respeta tal cual porque ya apunta al sitio de la demo.
     */
    public static function urlDemo(array $plantilla): ?string
    {
        if (! self::tieneDemo($plantilla)) {
            return null;
        }

        $demo = trim((string) $plantilla['demo']);

        return str_starts_with($demo, 'http') ? $demo : url('demos/'.$demo).'/';
    }

    /**
     * Rellena una plantilla con lo que las vistas necesitan y la config no
     * guarda: slug, categoría resuelta y URLs.
     *
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    private static function completar(string $slug, array $datos): array
    {
        $categoria = (string) ($datos['categoria'] ?? '');
        $tipo = (string) ($datos['tipo'] ?? '');

        return [
            'slug' => $slug,
            'nombre' => (string) ($datos['nombre'] ?? $slug),
            'categoria' => $categoria,
            'categoria_nombre' => self::nombreCategoria($categoria),
            'tipo' => $tipo,
            'tipo_nombre' => self::datoTipo($tipo, 'nombre'),
            'tipo_hashtag' => self::datoTipo($tipo, 'hashtag'),
            'tipo_descripcion' => self::datoTipo($tipo, 'descripcion'),
            'etiquetas' => array_values((array) ($datos['etiquetas'] ?? [])),
            'resumen' => (string) ($datos['resumen'] ?? ''),
            'incluye' => array_values((array) ($datos['incluye'] ?? [])),
            'tecnologias' => array_values((array) ($datos['tecnologias'] ?? [])),
            'imagen' => $datos['imagen'] ?? null,
            'demo' => $datos['demo'] ?? null,
            'destacada' => ($datos['destacada'] ?? false) === true,
            'actualizada' => (string) ($datos['actualizada'] ?? ''),
        ];
    }

    /**
     * Un dato del formato, leído directo de la config.
     *
     * No pasa por tipos(): esa lista se arma con todas(), que rellena cada
     * plantilla llamando a este método, así que encadenarlos sería la misma
     * recursión infinita que tuvo nombreCategoria() con categorias(). Un `tipo`
     * que no está en la config devuelve vacío y la etiqueta simplemente no se
     * pinta, en vez de mostrar una clave interna al visitante.
     */
    private static function datoTipo(string $clave, string $campo): string
    {
        $valor = config('plantillas.tipos.'.$clave.'.'.$campo);

        return is_string($valor) ? $valor : '';
    }

    /**
     * Una ruta relativa se resuelve contra el host actual; una URL absoluta se
     * respeta tal cual (es lo que se escribe el día del subdominio propio).
     */
    private static function absoluta(?string $valor): ?string
    {
        $valor = trim((string) $valor);

        if ($valor === '') {
            return null;
        }

        return str_starts_with($valor, 'http') ? $valor : url($valor);
    }

    /**
     * Texto comparable: sin mayúsculas y sin tildes.
     */
    private static function normalizar(string $texto): string
    {
        return mb_strtolower(Str::ascii($texto));
    }
}
