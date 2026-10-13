# Créditos y reemplazos de la demostración «Portafolio Creativo»

Esta es una plantilla de **formato landing** del Banco de Plantillas: una sola página
(`index.html`) con su CSS y su JavaScript dentro de la carpeta. La lista obligatoria que cumple
está en [`docs/BANCO_PLANTILLAS.md`](../../../docs/BANCO_PLANTILLAS.md); los otros ejemplos del
formato landing son `public/demos/clinica-dental/` y `public/demos/restaurante-carta/`.

El diseño es propio y no comparte hoja de estilos ni JavaScript con ninguna otra demo del banco
(regla de diferenciación): **«pliego de taller»**. Papel frío y tinta casi negra con un solo
acento magenta, titulares en sans muy pesada y en mayúsculas con el cuerpo en serif —al revés que
el expediente contable—, etiquetas en monoespaciada, la retícula de doce columnas dibujada como
filetes, el número de sección en el margen, los proyectos como **índice numerado de catálogo** (no
como rejilla de tarjetas) y las tarifas como tabla de trabajos. Cero esquinas redondeadas, cero
sombras: el hover no levanta la pieza, la pinta de magenta.

Al copiar la carpeta para el sitio de un cliente, lo primero que conviene revisar son las
variables de color y las tres pilas tipográficas del principio de `css/estilos.css`.

## Fotografías

Las doce fotos se descargaron de [Wikimedia Commons](https://commons.wikimedia.org) —lotes del
banco de imágenes Unsplash subidos a Commons— con el filtro de licencias **sin obligaciones**
(CC0), así que se pueden usar en una demostración comercial sin pedir permiso ni citar al autor.
Se buscan con `storage/tmp/fotos-portafolio-commons.php` (términos en
`storage/tmp/portafolio-temas4.json`), se revisan de un vistazo con
`storage/tmp/portafolio-rejilla.php` y con `storage/tmp/portafolio-finalistas.php` —a tamaño
real—, y se recortan y pasan a WebP con `storage/tmp/fotos-portafolio-preparar.php`.

Ninguna pesa más de 250 KB y ninguna lleva marca de agua: se miraron a tamaño real antes de
publicarlas. Los originales se recortaron a las proporciones de la plantilla (16/9 en la portada,
4/3 en las piezas del índice, 3/2 en las bandas y 3/4 en la vertical del estudio) y el paso a
WebP borra además los metadatos EXIF.

| Archivo | Tamaño | Dónde se usa | Foto original | Autor | Licencia | Ficha |
| --- | --- | --- | --- | --- | --- | --- |
| `img/pliego.webp` | 1024 × 576 | Portada (1.ª foto) | *Man in front of multiple art prints* | Beata Ratuszniak | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Man_in_front_of_multiple_art_prints_(Unsplash).jpg) |
| `img/mural.webp` | 1280 × 720 | Portada (2.ª foto) | *Chair in front of graffiti* | Alfonso Cenname | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Chair_in_front_of_graffiti_(Unsplash).jpg) |
| `img/taller.webp` | 1280 × 720 | Portada (3.ª foto) | *Artist spraypaints on cardboard* | Alex Blăjan | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Artist_spraypaints_on_cardboard_(Unsplash).jpg) |
| `img/marca.webp` | 1000 × 750 | Índice 01 · identidad | *London bike* | Blubel | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:London_bike_(Unsplash).jpg) |
| `img/editorial.webp` | 1000 × 750 | Índice 02 · editorial | *New Delhi, India* | Hans Vivek | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:New_Delhi,_India_(Unsplash_8WCMVvEWRqk).jpg) |
| `img/cartel.webp` | 1000 × 750 | Índice 03 · cartelería | *Layered* | Alice Donovan Rouse | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Layered_(Unsplash).jpg) |
| `img/mural-rosa.webp` | 1000 × 750 | Índice 04 · mural | *Artist in full flow* | Clem Onojeghuo | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Artist_in_full_flow_(Unsplash).jpg) |
| `img/ilustracion.webp` | 1000 × 750 | Índice 05 · ilustración | *Watercolor Flowers* | Victoria Bilsborough | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Watercolor_Flowers_(Unsplash).jpg) |
| `img/encuadernacion.webp` | 1000 × 750 | Índice 06 · edición de arte | *Beekeeper's workshop* | Roman Kraft | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Beekeeper%27s_workshop_(Unsplash).jpg) |
| `img/estudio.webp` | 900 × 1200 | Vertical del bloque de estudio | *Photographer on a dirt road* | Ezra Jeffrey | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Photographer_on_a_dirt_road_(Unsplash).jpg) |
| `img/estudio-taller.webp` | 1200 × 800 | Banda del proceso | *Art supplies clutter* | Khara Woods | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Art_supplies_clutter_(Unsplash).jpg) |
| `img/taller-detalle.webp` | 1200 × 800 | Banda del proceso · pared de herramientas | *Road to Mỹ Sơn* | Arthur Lambillotte | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Road_to_M%E1%BB%B9_S%C6%A1n_(Unsplash).jpg) |

`img/mapa.svg` no es una foto: es un dibujo vectorial propio del banco (la esquina del estudio
trazada a escala de manzana), sin script ni mapa embebido de terceros. En modo oscuro se invierte
con un filtro y el marcador magenta vuelve a leerse.

Las dos bandas del taller (`estudio-taller.webp` y `taller-detalle.webp`) van juntas en el bloque
del proceso: si el cliente aporta una sola foto del taller, hay que decidir cuál de las dos sale y
borrar la que quede sin uso, en vez de dejar el archivo huérfano en `img/`.

## Nombres, textos y datos que hay que reemplazar

Todos los datos son inventados. Como es una landing, todo vive en `index.html`:

1. **Nombre y logotipo.** «Mesa Norte» aparece en la marca de la cabecera (`.marca`), en el pie y
   en el `<title>`. El logotipo es un bloque de color y texto, no una imagen.
2. **Lema y textos.** El titular de la portada, los resúmenes de sección, las cuatro etapas del
   proceso y las cuatro preguntas frecuentes.
3. **Proyectos (sección `#proyectos`).** Los seis del índice son inventados: hay que cambiar
   título, meta (tipo, cliente y año), alcance y la pieza de `img/` de cada fila. El `data-tipo` de
   cada `<li>` es el que usa el filtro: si se agrega un proyecto de otro tipo, hay que agregar su
   botón en `.filtros` con el mismo valor.
4. **Tarifas y planes.** La tabla de `#servicios` y los tres planes de `#planes`, con los mismos
   precios repetidos en la tabla comparativa. Los precios son de referencia, como dice su nota.
5. **Testimonios (`#opiniones`).** Tres, con iniciales y cargo. En la demo no hay caras de
   terceros: si el cliente aporta fotos de sus clientes, hacen falta permisos por escrito.
6. **Ubicación (`#visitanos`).** Dirección, horarios, teléfono, correo, referencia de transporte y
   el enlace a Google Maps. El plano de `img/mapa.svg` hay que volver a dibujarlo si el local no
   está en esa esquina.
7. **Redes sociales.** Los cuatro iconos (Instagram, Facebook, TikTok y WhatsApp) de la barra
   superior y del pie apuntan a la plataforma, no a un perfil: en el sitio real van al perfil del
   negocio.
8. **WhatsApp.** Todos los enlaces `wa.me` usan `+51900000000`, que no existe. Se cambia el número
   **y** el mensaje precargado (`?text=`) de cada enlace: hay siete repartidos entre la cabecera,
   la portada, las tarifas, el contacto, el cierre, el pie y el botón flotante.
9. **Formulario.** `#formulario-encargo` lleva `data-demo="true"` y avisa de que no envía datos:
   en un sitio real se quita ese atributo, se pone el `action` del servidor y se dejan las mismas
   validaciones del navegador que ya trae el JavaScript.
10. **Modo oscuro.** Si se cambia la identidad de color, hay que tocar los dos bloques de
    variables (`:root` y `html[data-tema='oscuro']`) y comprobar que ningún texto quede sobre un
    fondo del mismo color. La clave de `localStorage` es `portafolio-creativo-tema`: si el sitio
    del cliente va a convivir con otra demo en el mismo dominio, conviene renombrarla.
