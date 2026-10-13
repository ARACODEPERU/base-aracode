<?php

/*
 * Escribe public/demos/supermercado-online-verdia/CREDITOS.md con la tabla de
 * fotos (archivo, medidas, dónde se usa, autor, licencia y ficha) tomada de
 * storage/tmp/verdia-fotos.json, que es el informe que deja el recorte.
 *
 * Uso: php storage/tmp/verdia-creditos.php
 */

const DESTINO = __DIR__.'/../../public/demos/supermercado-online-verdia/CREDITOS.md';

$fotos = json_decode(file_get_contents(__DIR__.'/verdia-fotos.json'), true);

if (! is_array($fotos)) {
    fwrite(STDERR, "Falta storage/tmp/verdia-fotos.json: corre primero fotos-verdia-preparar.php\n");
    exit(1);
}

/** Dónde se usa cada archivo dentro de la demo. */
$usos = [
    'portada-cesta.webp' => 'Portada (1.ª foto)',
    'portada-mercado.webp' => 'Portada (2.ª foto)',
    'portada-pan.webp' => 'Portada (3.ª foto)',
    'banda-frescos.webp' => 'Franja «Frescos del día»',
    'banda-abarrotes.webp' => 'Franja «Despensa de fondo»',
    'banda-saludable.webp' => 'Franja «Natural y saludable»',
    'bienvenida.webp' => 'Banner de bienvenida',
    'confianza-puesto.webp' => 'Bloque de confianza (vertical)',
    'confianza-mercado.webp' => 'Bloque de confianza',
    'rubro-frutas.webp' => 'Pasillo · Frutas y verduras',
    'rubro-lacteos.webp' => 'Pasillo · Lácteos y huevos',
    'rubro-carnes.webp' => 'Pasillo · Carnes y pescados',
    'rubro-panaderia.webp' => 'Pasillo · Panadería',
    'rubro-abarrotes.webp' => 'Pasillo · Abarrotes',
    'rubro-bebidas.webp' => 'Pasillo · Bebidas',
    'rubro-snacks.webp' => 'Pasillo · Snacks',
    'rubro-limpieza.webp' => 'Pasillo · Limpieza del hogar',
    'rubro-cuidado.webp' => 'Pasillo · Cuidado personal',
    'p-naranjas.webp' => 'Producto · Naranjas de jugo',
    'p-arandanos.webp' => 'Producto · Arándanos frescos',
    'p-platanos.webp' => 'Producto · Plátano de seda',
    'p-pepinos.webp' => 'Producto · Pepino criollo',
    'p-zanahorias.webp' => 'Producto · Zanahoria fresca',
    'p-tomates.webp' => 'Producto · Tomate italiano',
    'p-huevos.webp' => 'Producto · Huevos de campo',
    'p-yogur.webp' => 'Producto · Yogur natural con frutas',
    'p-queso.webp' => 'Producto · Queso semiduro al corte',
    'p-pescado.webp' => 'Producto · Filete de pescado fresco',
    'p-pan.webp' => 'Producto · Pan de campo integral',
    'p-hogaza.webp' => 'Producto · Hogaza artesana',
    'p-croissant.webp' => 'Producto · Croissants de mantequilla',
    'p-cafe.webp' => 'Producto · Café tostado en grano',
    'p-pasta.webp' => 'Producto · Pasta fresca artesanal',
    'p-galletas.webp' => 'Producto · Galletas de avena y canela',
    'p-cereal.webp' => 'Producto · Cereal de avena y miel',
    'o-judias.webp' => 'Oferta · Judías verdes',
    'o-fresas.webp' => 'Oferta · Fresas del valle',
    'o-filete.webp' => 'Oferta · Filete de res',
    'o-tomate.webp' => 'Oferta · Salsa de tomate casera',
];

$filas = '';

foreach ($fotos as $foto) {
    $archivo = $foto['archivo'];
    $autor = trim((string) $foto['autor']) !== '' ? trim(strip_tags($foto['autor'])) : 'sin firma en Commons';
    $titulo = str_replace('File:', '', $foto['titulo']);
    $titulo = preg_replace('/\.(jpg|jpeg|png)$/i', '', $titulo);

    $filas .= sprintf(
        "| `img/%s` | %s | %s | *%s* | %s | %s | [Commons](%s) |\n",
        $archivo,
        str_replace('x', ' × ', $foto['medidas']),
        $usos[$archivo] ?? '—',
        $titulo,
        $autor,
        $foto['licencia'],
        $foto['ficha']
    );
}

$texto = <<<MD
# Créditos y reemplazos de la demostración «Supermercado Online — VERDIA Market»

Esta es una plantilla de **formato tienda** del Banco de Plantillas: cuatro páginas enlazadas
(`index.html`, `tienda.html`, `producto.html` y `canasta.html`) con un solo `css/estilos.css` y un
solo `js/app.js`, más `js/datos.js` con el catálogo de la demostración. La lista obligatoria que
cumple está en [`docs/BANCO_PLANTILLAS.md`](../../../docs/BANCO_PLANTILLAS.md).

**VERDIA Market es una marca ficticia.** El nombre, el logotipo (`img/logo.svg`, dibujo vectorial
propio), los productos, los precios, el código de bienvenida `VERDIA10`, el teléfono, el correo y la
dirección son inventados y así se declara en la barra de aviso de las cuatro páginas y en el pie.

El diseño es propio y no comparte hoja de estilos ni JavaScript con ninguna otra demo (regla de
diferenciación): **«supermercado de barrio contemporáneo»**. Fondo marfil, superficies blancas,
tinta verde oscura para el texto, **verde bosque** `#17633D` para la acción y **naranja** `#F39A35`
reservado a las ofertas y a los precios rebajados, con verde claro `#EAF4E7` en los fondos suaves.
Las tarjetas de producto son cuadradas con la foto grande, el precio en número grande y el botón de
añadir sólido; las esquinas son redondeadas y las sombras bajas, para que el color lo pongan las
fotos de comida. La tipografía es la sans del sistema, con titulares juntos y precios en negrita.

Al copiar la carpeta para la tienda de un cliente, lo primero que conviene revisar son las variables
de color del principio de `css/estilos.css` y el objeto `VERDIA_TIENDA` de `js/datos.js`.

## Fotografías

Las {total} fotos se descargaron de [Wikimedia Commons](https://commons.wikimedia.org) —lotes del
banco de imágenes Unsplash subidos a Commons y fotos sueltas de dominio público— con el filtro de
licencias **sin obligaciones** (CC0 o dominio público), así que se pueden usar en una demostración
comercial sin pedir permiso ni citar al autor.

Se buscan con `storage/tmp/fotos-verdia-commons.php` (términos en `storage/tmp/verdia-temas*.json`),
se revisan con `storage/tmp/verdia-revisar.php`, que arma hojas de contactos para mirarlas antes de
recortarlas, y lo elegido se recorta y se pasa a WebP con `storage/tmp/fotos-verdia-preparar.php`.
Este informe, la tabla de abajo y el `CREDITOS.md` se generan desde `storage/tmp/verdia-creditos.php`.

Ninguna pesa más de 250 KB y ninguna lleva marca de agua. Los recortes son 16/10 en la portada, 3/2
en las franjas y el bloque de confianza, 4/3 en los pasillos, 1/1 en las tarjetas de producto y 16/9
en el banner de bienvenida; el paso a WebP borra además los metadatos EXIF.

| Archivo | Tamaño | Dónde se usa | Foto original | Autor | Licencia | Ficha |
| --- | --- | --- | --- | --- | --- | --- |
{tabla}
El **logotipo** (`img/logo.svg`) y el **plano** no existen: no hay plano en esta demo, así que no se
carga ningún script de mapas ni se muestra una dirección sobre un mapa. La dirección del pie es
ficticia y está marcada como tal.

## Qué reemplazar en un supermercado real

1. **Fotos de producto.** Las del catálogo son de banco: al publicar se cambian por las del producto
   que se vende, con el mismo nombre de archivo o actualizando `foto` en `VERDIA_PRODUCTOS`.
2. **Datos del negocio.** Nombre, razón social, teléfono, WhatsApp, correo, dirección y horarios
   están en el objeto `VERDIA_TIENDA` de `js/datos.js` y, copiados, en el pie de las cuatro páginas;
   están marcados con comentarios para reemplazarlos de una vez.
3. **Catálogo y precios.** Los veintiún productos, sus presentaciones, precios y precios anteriores
   viven en `VERDIA_PRODUCTOS` (`js/datos.js`): en una tienda real llegan de la base de datos y del
   sistema del negocio. Los montos de la demostración son de referencia y no incluyen IGV.
4. **Pasillos y necesidades.** Los nueve pasillos y las cuatro selecciones de «Compra por necesidad»
   están en `VERDIA_RUBROS` y `VERDIA_NECESIDADES`; se ajustan a la góndola del cliente.
5. **El cierre del pedido.** Hoy el botón arma un recibo de prueba y avisa de que no se envió nada.
   En producción se conecta a WhatsApp (enlace `wa.me` con el mensaje ya escrito), a un correo o a la
   pasarela de pago; el detalle que se muestra en el recibo es el mismo texto que viajaría.
6. **La entrega.** Las opciones del formulario («Recojo en tienda», «Entrega a domicilio») y el
   monto de envío gratis (`VERDIA_TIENDA.envioGratis`) son del medio peruano y se ajustan al negocio.
7. **Textos legales.** No hay política de privacidad ni términos: en un sitio real se agregan, y el
   aviso de la casilla de consentimiento se ajusta a la ley aplicable.

MD;

$texto = str_replace(['{total}', '{tabla}'], [count($fotos), $filas], $texto);

file_put_contents(DESTINO, $texto);

printf("%s escrito con %d fotos\n", realpath(DESTINO), count($fotos));
