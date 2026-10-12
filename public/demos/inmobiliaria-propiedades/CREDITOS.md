# Créditos y reemplazos de la demostración «Inmobiliaria»

Esta es la segunda plantilla de formato **sitio web** del Banco de Plantillas: cinco páginas
enlazadas (`index.html`, `propiedades.html`, la ficha `propiedad-los-alisos.html`,
`vende-tu-propiedad.html` y `contacto.html`) con un solo CSS y un solo JavaScript dentro de la
carpeta. El modelo de referencia para este formato es
[`public/demos/academia-cursos/`](../academia-cursos/CREDITOS.md) y, para el anterior por servicios
con planes, [`public/demos/estudio-contable/`](../estudio-contable/CREDITOS.md). La lista obligatoria
que cumple está en [`docs/BANCO_PLANTILLAS.md`](../../../docs/BANCO_PLANTILLAS.md).

## Fotografías

Las diecisiete fotos son del dominio público de [Wikimedia Commons](https://commons.wikimedia.org)
—la mayoría llegaron desde Unsplash y Pexels a Commons como **CC0**—, así que se pueden usar en una
demostración comercial sin pedir permiso ni citar al autor. Están reescaladas (1200 px de ancho como
máximo) y convertidas a WebP, lo que además borra los metadatos EXIF de los originales. Ninguna lleva
marca de agua ni caras de personas identificables: se revisaron a tamaño real antes de publicarlas.

Las tres primeras son las del **slider del hero**, que se ve a pantalla completa y por eso son
horizontales; `sala-vertical` es la única vertical (bloque «Nuestro compromiso») y el resto son
horizontales de detalle, dentro de las fichas.

| Archivo | Tamaño | Dónde se usa | Foto original | Autor | Licencia |
| --- | --- | --- | --- | --- | --- |
| `img/ciudad.webp` | 1024 × 704 | Hero (1.ª foto) y `preload` de la portada | [*High-rise buildings of Manhattan during sunset*](https://commons.wikimedia.org/wiki/File:High-rise_buildings_of_Manhattan_during_sunset.jpg) | Ben o'bro | CC0 |
| `img/sala.webp` | 1024 × 704 | Hero (2.ª foto) | [*Living room (Unsplash)*](https://commons.wikimedia.org/wiki/File:Living_room_(Unsplash).jpg) | Jarosław Ceborski | CC0 |
| `img/casa.webp` | 1024 × 704 | Hero (3.ª foto) | [*Cozy white house (Unsplash)*](https://commons.wikimedia.org/wiki/File:Cozy_white_house_(Unsplash).jpg) | Gus Ruballo | CC0 |
| `img/sala-vertical.webp` | 819 × 1024 | Bloque «Nuestro compromiso» (única vertical) | [*Green living room corner (Unsplash)*](https://commons.wikimedia.org/wiki/File:Green_living_room_corner_(Unsplash).jpg) | Brina Blum | CC0 |
| `img/comedor.webp` | 1200 × 800 | Destacada AL-2401, listado y galería de la ficha (foto 1) | [*Coffee table corner in a London apartment (Unsplash)*](https://commons.wikimedia.org/wiki/File:Coffee_table_corner_in_a_London_apartment_(Unsplash).jpg) | Alexander Pemberton | CC0 |
| `img/cocina.webp` | 1200 × 800 | Galería de la ficha (foto 2) | [*Blue white kitchen interior (Unsplash)*](https://commons.wikimedia.org/wiki/File:Blue_white_kitchen_interior_(Unsplash).jpg) | NeONBRAND | CC0 |
| `img/dormitorio.webp` | 1200 × 800 | Galería de la ficha (foto 3) | [*NY loft bedroom (Unsplash)*](https://commons.wikimedia.org/wiki/File:NY_loft_bedroom_(Unsplash).jpg) | Gabriel Beaudry | CC0 |
| `img/balcon.webp` | 1200 × 800 | Galería de la ficha (foto 4) | [*Balcony in Kyiv (Unsplash)*](https://commons.wikimedia.org/wiki/File:Balcony_in_Kyiv_(Unsplash).jpg) | Artur Aleksanian | CC0 |
| `img/edificio.webp` | 1024 × 704 | Ficha: banda «El edificio» | [*Brown apartment building (Unsplash)*](https://commons.wikimedia.org/wiki/File:Brown_apartment_building_(Unsplash).jpg) | Rene Bieder | CC0 |
| `img/casa-jardin.webp` | 1200 × 800 | «Vende tu propiedad»: valoración | [*White Wooden Building (Pexels 53610)*](https://commons.wikimedia.org/wiki/File:White_Wooden_Building_(Pexels_53610).jpg) | GregoryButler | CC0 |
| `img/casa-blanca.webp` | 900 × 600 | Destacadas y listado | [*White house (Unsplash)*](https://commons.wikimedia.org/wiki/File:White_house_(Unsplash).jpg) | Frøy Hamstad | CC0 |
| `img/comedor-luminoso.webp` | 900 × 600 | Destacadas, listado y «otras fichas» | [*Round table in a bright room (Unsplash)*](https://commons.wikimedia.org/wiki/File:Round_table_in_a_bright_room_(Unsplash).jpg) | Nirzar Pangarkar | CC0 |
| `img/dormitorio-ventanal.webp` | 900 × 600 | Listado y «otras fichas» | [*Hotel bedroom windows (Unsplash)*](https://commons.wikimedia.org/wiki/File:Hotel_bedroom_windows_(Unsplash).jpg) | Markus Spiske | CC0 |
| `img/ventana.webp` | 900 × 600 | Listado y «otras fichas» | [*Open bedroom window in Paris*](https://commons.wikimedia.org/wiki/File:Open_bedroom_window_in_Paris.jpg) | John Towner | CC0 |
| `img/casa-tejado.webp` | 900 × 600 | Listado | [*An old house with brick roof (Unsplash)*](https://commons.wikimedia.org/wiki/File:An_old_house_with_brick_roof_(Unsplash).jpg) | Roya Ann Miller | CC0 |
| `img/sala-cozy.webp` | 900 × 600 | Listado | [*Cozy interior with a sofa (Unsplash)*](https://commons.wikimedia.org/wiki/File:Cozy_interior_with_a_sofa_(Unsplash).jpg) | Breather | CC0 |
| `img/cocina-pequena.webp` | 900 × 600 | Listado | [*Kitchen-apartment-home-room*](https://commons.wikimedia.org/wiki/File:Kitchen-apartment-home-room_(24300002976).jpg) | Pixel.la Free Stock Photos | CC0 |

Trazabilidad: las rondas de búsqueda quedaron en `storage/tmp/candidatas-inmobiliaria*/` (con su
`datos.json` y su hoja de contactos), el listado de las elegidas y su ficha de Commons en
`storage/tmp/inmobiliaria-fotos.json`, y el recorte y la conversión a WebP en
`storage/tmp/fotos-inmobiliaria-preparar.php`. Para revisarlas sin depender del panel de vista
previa están `storage/tmp/rejilla-candidatas.php` (rejilla de todas las candidatas en una imagen) y
`storage/tmp/auditar-fotos.php` (tamaño, brillo y mapa de luminancia en ASCII).

Los tres gráficos **no** son fotos ni mapas de terceros: son SVG dibujados a mano para la demo y no
piden ninguna atribución —

- `img/plano.svg`: plano del departamento AL-2401, con las medidas de eje a eje y la mancha de cada
  ambiente. Es la pieza que ninguna otra demo del banco tiene y **no está dibujado a ojo**: lo
genera `storage/tmp/plano-inmobiliaria.php` desde la lista de ambientes en metros, y el guion se
niega a escribir el archivo si la planta no cierra (los 14 ambientes suman 120,00 m², el fondo de
cada columna da 10,00 m y el ancho de cada franja 12,00 m) o si dos ambientes se solapan. Las
medidas de la ficha salen de ahí, no al revés.
- `img/mapa.svg`: manzana de la oficina de Miraflores, con las calles rotuladas.
- `img/mapa-oficina.svg`: el mismo plano en su versión para la página de contacto.

El botón «Abrir en Google Maps» del HTML sí lleva a la dirección del negocio (de ejemplo).

## Qué se reemplaza al publicar el sitio de un cliente

1. **Las diecisiete fotos** por las del inmueble real. Las tres del slider del hero se cambian
   primero: se ven a pantalla completa, así que piden horizontales de 1600 px de ancho o más, y son
   las que más canta que sean de departamentos en el extranjero. Basta reemplazar los archivos con
   los mismos nombres (`ciudad`, `sala`, `casa`); el HTML no se toca.
2. **El plano y los mapas** (`img/plano.svg`, `img/mapa.svg`, `img/mapa-oficina.svg`) por los del
   inmueble y la oficina, o por el mapa embebido que prefiera el cliente. El plano es el que da
   sentido a las medidas de la ficha: si se cambia, hay que cuadrarlo con la tabla «Datos técnicos».
3. **Los datos de ejemplo**: nombre de la inmobiliaria («Alameda Propiedades»), dirección, teléfonos,
   correo, asesores, testimonios, cifras, horarios, tarifas y precios. Conviene revisarlos con una
   búsqueda de «S/» y de «Alameda».
4. **Las ocho propiedades**: cada tarjeta tiene sus atributos `data-operacion`, `data-distrito`,
   `data-tipo`, `data-dormitorios`, `data-area`, `data-precio` y `data-busqueda`, que son los que
   alimentan el filtro del listado y el buscador de la portada. Si se cambia el catálogo hay que
   tocar **a la vez**: las tarjetas de `index.html` (destacadas), las de `propiedades.html` (listado
   completo), las de «Parecidas a esta» de `propiedad-los-alisos.html`, las opciones de distrito del
   buscador de la portada y del filtro del listado, y el desplegable de inmuebles del formulario de
   `contacto.html`.
5. **La ficha de inmueble**: `propiedad-los-alisos.html` es el modelo para las demás. Se copia y se
   cambian el código (AL-2401), los datos técnicos, el plano, el simulador y los textos. El enlace
   `contacto.html?propiedad=` de cada tarjeta lleva el código al formulario, así que si se cambia el
   código hay que cambiarlo en los dos sitios.
6. **El simulador de cuota**: la tasa, el plazo y el porcentaje de inicial están declarados en los
   valores de los controles y en el JavaScript (`js/app.js`); los números que se muestran son de
   referencia y no constituyen una oferta de crédito.
7. **Los enlaces de redes sociales**: hoy apuntan a la plataforma (`instagram.com`, `facebook.com`,
   `tiktok.com`, `wa.me`) porque son de demostración; van a los perfiles del negocio.
8. **El número de WhatsApp**: en la demo es `+51 900 000 000`, un número que no existe, para que
   nadie reciba mensajes de prueba por error.
9. **El formulario de contacto y el de valoración**: validan en el navegador y avisan de lo que
   falta, pero no envían nada. Al publicarlos se conectan al correo del negocio o a un servicio de
   formularios, y entonces se quita `data-demo="true"` del `<form>` (el JavaScript envía solo cuando
   ese atributo no está).

Las fotos de inmuebles y de personas que aparezcan en las fichas definitivas deben ser del propio
negocio o tener permiso de uso: es la diferencia entre una demo y el sitio de un cliente. En
inmobiliaria, además, la foto de la fachada y la del plano suelen venir del propietario, así que
conviene pedirle también por escrito que autoriza su publicación.
