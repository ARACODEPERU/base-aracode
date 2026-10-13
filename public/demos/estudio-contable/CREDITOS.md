# Créditos y reemplazos de la demostración «Estudio Contable»

Esta es una plantilla de **formato sitio web** del Banco de Plantillas: cinco páginas enlazadas
(`index.html`, `servicios.html`, la ficha de un servicio, `planes.html` y `contacto.html`) con su
CSS y su JavaScript compartidos dentro de la carpeta. La lista obligatoria que cumple está en
[`docs/BANCO_PLANTILLAS.md`](../../../docs/BANCO_PLANTILLAS.md); el otro ejemplo del mismo formato es
`public/demos/academia-cursos/` y, para el formato landing, `public/demos/clinica-dental/`.

El diseño es propio y no comparte hoja de estilos ni JavaScript con ninguna otra demo del banco
(regla de diferenciación): **«expediente contable»**, papel cálido y tinta, titulares en serif,
filetes de un píxel en vez de tarjetas con sombra, esquinas casi rectas, cifras tabulares, secciones
con margen numerado, el listado de servicios como libro de entradas y los planes como hoja de
precios. Al copiar la carpeta para el sitio de un cliente, lo primero que conviene revisar son las
variables de color y la pila serif del principio de `css/estilos.css`.

## Fotografías

Las siete fotos se descargaron de [Wikimedia Commons](https://commons.wikimedia.org) con el filtro
de licencias **sin obligaciones** (CC0), así que se pueden usar en una demostración comercial sin
pedir permiso ni citar al autor. Están recortadas a las proporciones de la plantilla, reescaladas
(1200 px de ancho como máximo) y convertidas a WebP, lo que además borra los metadatos EXIF de los
originales. Ninguna lleva marca de agua: se revisaron a tamaño real antes de publicarlas.

Las tres primeras son las del **slider del hero**, a pantalla completa y por eso horizontales; la
siguiente es la **vertical** de la sección de confianza y las tres últimas son las horizontales de
detalle, en bloques y tarjetas.

| Archivo | Tamaño | Dónde se usa | Foto original | Autor | Licencia | Ficha |
| --- | --- | --- | --- | --- | --- | --- |
| `img/reunion.webp` | 1024 × 704 | Hero (1.ª foto) | *Large meeting room* | Breather | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Large_meeting_room_(Unsplash).jpg) |
| `img/oficina.webp` | 1024 × 704 | Hero (2.ª foto) | *Office* | Alesia Kazantceva | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Office_(Unsplash_VWcPlbHglYc).jpg) |
| `img/escritorio.webp` | 1024 × 704 | Hero (3.ª foto) | *Apple-camera-desk-office* | Pixel.la Free Stock Photos | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Apple-camera-desk-office_(24031346580).jpg) |
| `img/equipo.webp` | 819 × 1024 | Sección de confianza (vertical) | *Desks in an open office space* | Crew | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Desks_in_an_open_office_space_(Unsplash).jpg) |
| `img/asistencia.webp` | 1200 × 800 | «Cómo trabajamos»: detalle | *Apple-desk-office-technology* | Pixel.la Free Stock Photos | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Apple-desk-office-technology_(24218133962).jpg) |
| `img/archivo.webp` | 1200 × 800 | «Cómo trabajamos»: archivo | *My working space* | Vadim Sherbakov | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:My_working_space_(Unsplash).jpg) |
| `img/revision.webp` | 1200 × 800 | Revisión de declaraciones | *Quiet meeting room* | Breather | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Quiet_meeting_room_(Unsplash).jpg) |

Trazabilidad: la búsqueda y la descarga se hicieron con `storage/tmp/fotos-estudio-commons.php` y el
recorte con `storage/tmp/fotos-estudio-preparar.php`; en `storage/tmp/estudio-fotos.json` quedó la
ficha de cada archivo (título original, autor, licencia, medidas y peso). El recorte de las siete
está hecho con la misma proporción que usa el CSS, así que la foto no se deforma al entrar en su
hueco.

`img/mapa.svg` no es una foto: es un plano dibujado a mano para la demo (sin scripts de mapas de
terceros). El botón «Abrir en Google Maps» del HTML sí lleva a la dirección del negocio.

## Qué se reemplaza al publicar el sitio de un cliente

1. **Las siete fotos** por las del negocio. Las tres del slider del hero conviene cambiarlas
   primero: se ven a pantalla completa, así que piden horizontales de 1600 px de ancho o más, y son
   las que más canta que sean de una oficina genérica. Basta reemplazar los archivos con los mismos
   nombres (`reunion`, `oficina`, `escritorio`) y respetar las proporciones 16/11 (hero), 4/5
   (`equipo.webp`, la vertical) y 3/2 (las de detalle); el HTML no se toca.
2. **El plano** por el mapa embebido que prefiera el cliente, o se mantiene el plano propio.
3. **Los datos de ejemplo**: nombre del estudio, dirección, teléfonos, correo, horarios, cifras,
   testimonios, precios, plazos y regímenes tributarios. Están en las cinco páginas y conviene
   revisarlos con una búsqueda de «S/» y de «Vega».
4. **El catálogo de servicios**: los ocho servicios viven en **tres** sitios que deben mantenerse a
   la vez — las tarjetas de `index.html`, las de `servicios.html` (con sus atributos `data-slug`,
   `data-area` y `data-atencion`, que alimentan el filtro) y las opciones del `<select>` del
   formulario de `contacto.html`, cuyos `value` son justamente los `data-slug`. La ficha
   `servicio-asesoria-mensual.html` es el modelo para las fichas de los demás: se copia, se cambia
   el texto, el `<title>` y la `meta description`, y se ajustan el precio del panel lateral y los
   datos del encabezado (frecuencia, qué incluye, entrega y forma de atención). El nombre del
   archivo se escribe a mano, no lo genera nada.
5. **Los planes**: `planes.html` tiene las tres tarjetas **y** la tabla comparativa de abajo; si se
   cambia un plan hay que tocar las dos, para que no se contradigan.
6. **Los enlaces de redes sociales**: hoy apuntan a la plataforma (`instagram.com`, `facebook.com`,
   `tiktok.com`, `wa.me`) porque son de demostración; van a los perfiles del negocio.
7. **El número de WhatsApp**: en la demo es `+51 900 000 000`, un número que no existe, para que
   nadie reciba mensajes de prueba por error.
8. **El formulario de consulta**: valida en el navegador y avisa de lo que falta, pero no envía
   nada. Al publicarlo se conecta al correo del negocio o a un servicio de formularios, y entonces
   se quita `data-demo="true"` del `<form>` (el JavaScript envía solo cuando ese atributo no está).
9. **La privacidad**: el aviso «usamos tus datos solo para atender tu consulta» y la casilla de
   autorización son texto de la demo. Si se conecta el formulario a un servicio externo, hay que
   reemplazarlos por la política de privacidad real del cliente.

Las fotos de personas que aparezcan en las fichas definitivas deben ser del propio negocio o tener
permiso de uso: es la diferencia entre una demo y el sitio de un cliente. En esta demo seis de las
siete fotos son oficinas, escritorios y salas de reunión vacías; la séptima (`img/equipo.webp`, la
vertical de la banda de compromiso) muestra a una persona trabajando en su laptop y **se le ve el
rostro**. Su licencia es CC0 —el fotógrafo la liberó para cualquier uso, así que no hay problema
legal—, pero una cara ajena no debería ilustrar el equipo de un estudio que no es suyo: es la
primera foto que hay que reemplazar por una del equipo real del cliente. El texto alternativo se
escribió en consecuencia («persona trabajando en su laptop», no «la contadora del estudio»), porque
afirmar que es alguien del estudio sería falso.
