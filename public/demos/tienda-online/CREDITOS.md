# Créditos y reemplazos de la demostración «Tienda en línea»

Esta es una plantilla de **formato tienda** del Banco de Plantillas: cuatro páginas enlazadas
(`index.html`, `tienda.html`, `producto.html` y `canasta.html`) con un solo `css/estilos.css` y un
solo `js/app.js`. La lista obligatoria que cumple está en
[`docs/BANCO_PLANTILLAS.md`](../../../docs/BANCO_PLANTILLAS.md); las otras demos del banco son
`public/demos/{clinica-dental,restaurante-carta,academia-cursos,estudio-contable,inmobiliaria-propiedades,portafolio-creativo}/`.

El diseño es propio y no comparte hoja de estilos ni JavaScript con ninguna otra demo (regla de
diferenciación): **«tienda de casa moderna»**. Lienzo casi blanco —el color lo pone la foto del
producto—, tinta carbón, un solo acento naranja teja para lo que se toca (botones, precio, descuento)
y verde profundo para las categorías y los sellos. En las tarjetas el **precio va en el texto**, en
número grande y en negrita, con el anterior tachado al lado; sobre la foto solo quedan el descuento
en porcentaje y la etiqueta del producto, y el botón de añadir es sólido. La tipografía es la sans
del sistema con titulares muy juntos, esquinas suaves y sombras bajas: el contraste lo llevan el
tamaño, el aire y el precio, no los filetes.

La marca de la demo es **Casa Lume** (casa y objetos, Miraflores). Al copiar la carpeta para el
sitio de un cliente, lo primero que conviene revisar son las variables de color y las dos pilas
tipográficas del principio de `css/estilos.css`.

## Fotografías

Las dieciocho fotos se descargaron de [Wikimedia Commons](https://commons.wikimedia.org) —lotes del
banco de imágenes Unsplash subidos a Commons y fotos sueltas de dominio público— con el filtro de
licencias **sin obligaciones** (CC0 o dominio público), así que se pueden usar en una demostración
comercial sin pedir permiso ni citar al autor.

Se buscan con `storage/tmp/fotos-tienda-commons.php` (términos en `storage/tmp/casa-temas*.json`),
se revisan con `storage/tmp/casa-rejilla.php`, que arma una hoja de contactos, y —cuando el panel de
vista previa no compone fotogramas, como pasa en esta máquina— con `storage/tmp/casa-auditar.php`,
que imprime de cada imagen su brillo, su contraste, una medida de nitidez, sus colores dominantes y
un mapa de luz en ASCII. Lo elegido se recorta y se pasa a WebP con
`storage/tmp/casa-fotos-preparar.php`, que además borra de la carpeta las fotos que ya no se usan.

Ninguna pesa más de 250 KB y ninguna lleva marca de agua. Los recortes son 16/9 en la portada, 4/3
en los productos del catálogo, 3/2 en las franjas y 3/4 en la vertical de la tienda; el paso a WebP
borra además los metadatos EXIF.

| Archivo | Tamaño | Dónde se usa | Foto original | Autor | Licencia | Ficha |
| --- | --- | --- | --- | --- | --- | --- |
| `img/portada-salon.webp` | 1280 × 720 | Portada (1.ª foto) | *At Home* | Naomi Hébert | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:At_Home_(Unsplash).jpg) |
| `img/portada-cocina.webp` | 1280 × 720 | Portada (2.ª foto) | *Blue white kitchen interior* | NeONBRAND | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Blue_white_kitchen_interior_(Unsplash).jpg) |
| `img/portada-comedor.webp` | 1280 × 720 | Portada (3.ª foto) | *Round table in a bright room* | Nirzar Pangarkar | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Round_table_in_a_bright_room_(Unsplash).jpg) |
| `img/banda-sala.webp` | 1200 × 800 | Franja «Menos cosas, mejores cosas» | *Home Sweet Home Pt. 4* | Kari Shea | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Home_Sweet_Home_Pt._4_(Unsplash).jpg) |
| `img/banda-detalle.webp` | 1200 × 800 | Franja «Stock real» | *Handmade Terracotta Ceramics* | Igor Ovsyannykov | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Handmade_Terracotta_Ceramics_(Unsplash).jpg) |
| `img/estudio-vertical.webp` | 900 × 1200 | Vertical del bloque de la tienda | *Roberto Nickson 2016* | Roberto Nickson | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Roberto_Nickson_2016_(Unsplash).jpg) |
| `img/pieza-taza.webp` | 900 × 675 | Producto · Taza de gres | *Bucharest mug* | Alexandru Stavrică | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Bucharest_mug_(Unsplash).jpg) |
| `img/pieza-jarron.webp` | 900 × 675 | Producto · Jarrón de gres mate | *Rustic flower vase* | Alisa Anton | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Rustic_flower_vase_(Unsplash).jpg) |
| `img/pieza-chal.webp` | 900 × 675 | Producto · Manta tejida de algodón | *Blanket Scarf* | Ruthie Martin | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Blanket_Scarf_(Unsplash).jpg) |
| `img/pieza-tabla.webp` | 900 × 675 | Producto · Tabla de cortar | *Herbs and spice on board* | Chinh Le Duc | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Herbs_and_spice_on_board_(Unsplash).jpg) |
| `img/pieza-cucharones.webp` | 900 × 675 | Producto · Cucharones de madera | *Salt and peppers mix in wooden spoons* | Mira Bozhko | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Salt_and_peppers_mix_in_wooden_spoons_(Unsplash).jpg) |
| `img/pieza-jabon.webp` | 900 × 675 | Producto · Jabón de manos de oliva | *Hand crafted soap* | Kristina Balić | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Hand_crafted_soap_(Unsplash).jpg) |
| `img/pieza-canasta.webp` | 900 × 675 | Producto · Cesta de fibra con tapa | *Picnic basket* | Bonnie Kittle | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Picnic_basket_(Unsplash).jpg) |
| `img/producto-sarten-hierro.webp` | 900 × 675 | Producto · Sartén de hierro fundido | *Чугунная сковородка* | Макс13 | Dominio público | [Commons](https://commons.wikimedia.org/wiki/File:%D0%A7%D1%83%D0%B3%D1%83%D0%BD%D0%BD%D0%B0%D1%8F_%D1%81%D0%BA%D0%BE%D0%B2%D0%BE%D1%80%D0%BE%D0%B4%D0%BA%D0%B0.JPG) |
| `img/producto-maceta-ceramica.webp` | 900 × 675 | Producto · Maceta de cerámica con plato | *Plectranthus verticillatus in a ceramic pot* | W.carter | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Plectranthus_verticillatus_in_a_ceramic_pot.jpg) |
| `img/producto-vaso-vidrio.webp` | 900 × 675 | Producto · Vaso de vidrio soplado | *Glass of water ouside 2* | Kurt Kaiser | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Glass_of_water_ouside_2.jpg) |
| `img/producto-sabanas-lino.webp` | 900 × 675 | Producto · Juego de sábanas de lino | *NY loft bedroom* | Gabriel Beaudry | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:NY_loft_bedroom_(Unsplash).jpg) |
| `img/producto-organizador-cajon.webp` | 900 × 675 | Producto · Organizador de cajón | *Kitchen drawer* | Jarosław Ceborski | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Kitchen_drawer_(Unsplash).jpg) |

El **plano** de la sección de ubicación (`img/mapa.svg`) es un dibujo vectorial propio, hecho con la
paleta clara y con la tinta oscura a propósito: el modo oscuro lo invierte con un filtro y, si los
trazos fueran claros, quedarían casi negros sobre el fondo negro. No carga ningún script de mapas.

## Qué reemplazar en una tienda real

1. **Fotos de producto.** Las del catálogo son de banco: al publicar se cambian por las del producto
   que se vende, con el mismo nombre de archivo o actualizando `foto` en el arreglo `PRODUCTOS` de
   `js/app.js`.
2. **Datos del negocio.** Razón social, teléfono, WhatsApp (`TIENDA.whatsapp`, hoy uno que no
   existe), correo, dirección, horarios y redes. Están repartidos entre el HTML y el objeto
   `TIENDA` del JavaScript, marcados con comentarios.
3. **Catálogo y precios.** Los doce productos, sus variantes, medidas, cuidados y precios viven en
   `PRODUCTOS` (`js/app.js`) y, copiados, en las tarjetas del HTML: en una tienda de verdad llegan
   de la base de datos y de la pasarela de pago. Los dos sitios se mantienen sincronizados con
   `node storage/tmp/casa-tarjetas.mjs`, que reescribe las rejillas —catálogo, destacados, atajos de
   ambiente y colecciones— desde esos datos. Los precios son de referencia y no incluyen impuestos.
4. **Entrega y pagos.** Las tres formas de entrega con sus tarifas (`ENTREGAS`), el umbral de envío
   gratis (`TIENDA.envioGratis`) y la lista de pagos (`PAGOS`) son del medio peruano: se ajustan al
   negocio.
5. **El cierre del pedido.** Aquí el botón arma el recibo y avisa de que la demostración no envía
   nada. En producción se conecta a WhatsApp (enlace `wa.me` con el mensaje ya escrito), a un correo
   o a la pasarela de pago; el detalle que hoy se muestra en el recibo es el mismo texto que
   viajaría.
6. **Textos legales.** No hay política de privacidad ni términos: en un sitio real se agregan, y el
   aviso de la casilla de consentimiento se ajusta a la ley aplicable.
