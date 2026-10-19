<?php

/*
 * Escribe public/demos/nexora-market/CREDITOS.md con la tabla de fotos
 * (archivo, medidas, dónde se usa, foto original, autor, licencia y ficha). Los
 * datos salen de storage/tmp/nexora-fotos.json —el informe que deja el recorte—
 * cruzados con los datos.json de cada lote de búsqueda, que es donde viven el
 * autor y la licencia que devolvieron las fuentes.
 *
 * Uso: php storage/tmp/nexora-creditos.php
 */

const DESTINO = __DIR__.'/../../public/demos/nexora-market/CREDITOS.md';

$fotos = json_decode(file_get_contents(__DIR__.'/nexora-fotos.json'), true);

if (! is_array($fotos)) {
    fwrite(STDERR, "Falta storage/tmp/nexora-fotos.json: corre primero fotos-nexora-preparar.php\n");
    exit(1);
}

// Índice de candidatas: "carpeta#n" => fila de datos.json.
$candidatas = [];

foreach (glob(__DIR__.'/candidatas-nexora*/datos.json') as $ruta) {
    $carpeta = basename(dirname($ruta));

    foreach (json_decode(file_get_contents($ruta), true) ?: [] as $fila) {
        $candidatas[$carpeta.'#'.sprintf('%03d', $fila['n'])] = $fila;
    }
}

$filas = '';
$sinDatos = [];

foreach ($fotos as $foto) {
    $datos = $candidatas[$foto['candidata']] ?? null;

    if ($datos === null) {
        $sinDatos[] = $foto['candidata'];
    }

    $titulo = preg_replace('/^File:/', '', (string) ($datos['titulo'] ?? ''));
    $titulo = preg_replace('/\.(jpg|jpeg|png|webp)$/i', '', $titulo);

    if (mb_strlen($titulo) > 58) {
        $titulo = rtrim(mb_substr($titulo, 0, 57)).'…';
    }

    // Openverse devuelve el código corto de la licencia: se escribe como se lee.
    $licencia = strtoupper(trim((string) ($datos['licencia'] ?? '')));
    $licencia = match (true) {
        $licencia === 'BY' => 'CC BY',
        $licencia === 'BY-SA' => 'CC BY-SA',
        $licencia === 'PDM' => 'Dominio público (PDM)',
        $licencia === 'CC0' => 'CC0',
        $licencia === '' => '—',
        default => (string) $datos['licencia'],
    };

    $filas .= sprintf(
        "| `img/%s` | %s | %s | *%s* | %s | %s | [%s](%s) |\n",
        $foto['archivo'],
        str_replace('x', ' × ', $foto['medidas']),
        $foto['uso'],
        $titulo !== '' ? $titulo : 'sin título en la fuente',
        trim((string) ($datos['autor'] ?? '')) !== '' ? $datos['autor'] : 'sin firma en el origen',
        $licencia,
        $datos['fuente'] ?? 'origen',
        $datos['ficha'] ?? ''
    );
}

$conAtribucion = count(array_filter($fotos, function (array $foto) use ($candidatas) {
    $licencia = $candidatas[$foto['candidata']]['licencia'] ?? '';

    return (bool) preg_match('/CC BY/i', $licencia);
}));

$total = count($fotos);

$texto = <<<MD
# Créditos y reemplazos de la demostración «NÉXORA MARKET»

Esta es una plantilla de **formato tienda** del Banco de Plantillas: cinco páginas enlazadas
(`index.html`, `catalogo.html`, `producto.html`, `carrito.html` y `revista.html`) con un solo
`css/estilos.css` y un solo `js/app.js`. La lista obligatoria que cumple está en
[`docs/BANCO_PLANTILLAS.md`](../../../docs/BANCO_PLANTILLAS.md).

**NÉXORA MARKET es una marca ficticia.** El nombre, el logotipo (`img/logo.svg`, dibujo vectorial
propio), el eslogan «Tu mundo, tus productos, a un clic», los productos, los precios, las
promociones, los códigos, las opiniones, las seis marcas del bloque multimarca, la dirección y el
teléfono son inventados, y así se declara en la franja de aviso de las cinco páginas y en el pie.

El diseño es propio y no comparte hoja de estilos ni JavaScript con ninguna otra demo (regla de
diferenciación): **«escaparate de gran almacén»**. El azul noche `#101D30` es el color estructural
(cabecera a sangre, franja de beneficios, panel de canasta y pie), el blanco y el gris `#F2F5F8` son
el papel donde vive el producto, el naranja `#F56A2A` es un único color de acción —lo que se pulsa,
lo rebajado y lo que hay que mirar— y el verde `#209765` solo dice cosas positivas (disponibilidad y
envío sin costo). La arquitectura también es distinta de las otras dos tiendas del banco: la
navegación vive en un **mega panel** con las seis categorías y sus colecciones, el catálogo filtra
por categoría, colección, oferta, favoritos y precio máximo con el estado escrito en la URL, y la
canasta es un panel lateral presente en las cinco páginas. La tipografía es la sans del sistema, con
etiquetas en mayúscula espaciada, titulares de seguimiento cerrado y precios con cifras tabulares.

Al copiar la carpeta para la tienda de un cliente, lo primero que conviene tocar son las variables
del principio de `css/estilos.css` y los arreglos `CATEGORIAS` y `PRODUCTOS` de `js/app.js`.

## Fotografías

Las {$total} fotos se descargaron de dos fuentes de material libre —[Wikimedia
Commons](https://commons.wikimedia.org) y la API de [Openverse](https://openverse.org)— con el
filtro de licencias del banco: **sin obligaciones** (CC0 o dominio público) o **con atribución
simple** (CC BY). Quedan fuera las que añaden condiciones que una plantilla que se revende no puede
cumplir: compartir igual (SA), no comercial (NC) y sin obra derivada (ND); el recorte y el reescalado
a WebP son obra derivada.

De las {$total}, **{$conAtribucion} son CC BY** y su atribución obligatoria es la de la tabla de
abajo (autor y enlace a la ficha original). Las demás son CC0 o de dominio público y no exigen
crédito, pero se anotan igual para poder rastrear el origen.

Se buscan con `storage/tmp/fotos-nexora-fuentes.php` (términos en `storage/tmp/nexora-temas*.json`),
se revisan con `storage/tmp/nexora-finalistas.php` y `storage/tmp/nexora-auditar.php`, y lo elegido
se recorta y se pasa a WebP con `storage/tmp/fotos-nexora-preparar.php`, que lee la receta de
`storage/tmp/nexora-seleccion.json`. Este informe y la tabla se generan con
`storage/tmp/nexora-creditos.php`.

Ninguna pesa más de 250 KB y ninguna lleva marca de agua. Los recortes son 16/10 en la portada, 4/3
en las tarjetas de categoría, 3/2 en la franja promocional y en las guías de compra, 11/8 en los dos
bloques editoriales, 3/4 en la foto vertical del bloque de confianza y 1/1 en las tarjetas de
producto; el paso a WebP borra además los metadatos EXIF.

| Archivo | Tamaño | Dónde se usa | Foto original | Autor | Licencia | Ficha |
| --- | --- | --- | --- | --- | --- | --- |
{$filas}
El **logotipo** (`img/logo.svg`) y el **plano** (`img/mapa.svg`) son dibujos vectoriales propios de
esta plantilla: no vienen de ninguna fuente externa. El plano es una ilustración de ejemplo, no una
captura de un servicio de mapas, y el enlace del bloque de ubicación abre Google Maps con la
dirección ficticia.

## Qué reemplazar en una tienda real

1. **Fotos de producto.** Las de la tabla son de banco o de dominio público: al publicar se cambian
   por las del producto que se vende, con el mismo nombre de archivo o actualizando `foto` en
   `PRODUCTOS` (si son CC BY de otra fuente, la atribución nueva va aquí).
2. **Datos del negocio.** Nombre, eslogan, WhatsApp, teléfono, correo, dirección y horarios están en
   la constante `WHATSAPP` de `js/app.js` y en la cabecera y el pie de las cinco páginas, marcados
   con comentarios para reemplazarlos de una vez.
3. **Catálogo y precios.** Las seis categorías (`CATEGORIAS`), sus colecciones y los veintisiete
   productos (`PRODUCTOS`) viven en `js/app.js`: en una tienda real llegan de la base de datos y del
   sistema del negocio. Los montos son de referencia, no incluyen impuestos y no hay stock real.
4. **Marcas.** Las seis marcas del bloque multimarca (`MARCAS`) son inventadas: se reemplazan por las
   del catálogo real o se quita el bloque.
5. **Opiniones.** Las tres opiniones de la portada son de ejemplo y están rotuladas como tales
   («contenido ficticio»). Se reemplazan por opiniones verificables o se quitan.
6. **El cierre del pedido.** El formulario de `carrito.html` valida en el navegador y muestra un
   comprobante: no cobra, no genera pedidos y no envía datos. El mismo texto del comprobante es el
   que viajaría por WhatsApp (`wa.me` con el mensaje ya escrito) el día que se conecte.
7. **Pagos, envíos y políticas.** Los medios de pago del pie son solo una representación visual, las
   opciones de entrega y el envío sin costo desde S/ 199 son de ejemplo, y no hay política de
   privacidad ni términos: en un sitio real se redactan.
8. **Buscador.** En la demostración busca dentro del catálogo local. En producción se conecta al
   buscador del sistema del negocio.

MD;

file_put_contents(DESTINO, $texto);

printf("%s escrito con %d fotos (%d con atribución)%s\n", realpath(DESTINO), $total, $conAtribucion, $sinDatos ? "\nSin datos de origen: ".implode(', ', $sinDatos) : '');
