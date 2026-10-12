# Banco de Plantillas Web — cómo se mantiene

La auditoría y el plan están en [BANCO_PLANTILLAS_FASE1.md](BANCO_PLANTILLAS_FASE1.md).
Este documento es la parte operativa: cómo agregar una plantilla, cómo agregar su
demostración y qué hacer el día que una demo se mude a su propio subdominio.

## Regla de portada (hero o slider)

Aclaración vigente del banco: **toda landing y todo sitio web abre con un hero o
un slider a pantalla completa, moderno y responsive**. Es el punto 1 de la lista
obligatoria de más abajo, y las siete demostraciones publicadas ya lo cumplen:
`clinica-dental`, `restaurante-carta` y `portafolio-creativo` (landings), y
`academia-cursos`, `estudio-contable`, `inmobiliaria-propiedades` y
`tienda-online` (sitios web; la última, del tipo `tienda`, de cuatro páginas)
abren con un slider de tres fotos cada una.
Antes de dar por buena una portada nueva: avanzar el slider a mano, revisarla a
1440 px y a 390 px y repetir la revisión con `prefers-reduced-motion` activado,
que debe apagar el paso automático sin romper los controles.
Esa segunda pasada se puede hacer de verdad en Chrome por el protocolo de
DevTools, que sí emula la preferencia: `storage/tmp/repaso-movimiento.mjs` abre la
demo con y sin `reduce`, espera más que el intervalo del slider y avisa de si el
paso automático se apagó, de si el control a mano sigue respondiendo y de si la
cinta y el latido del WhatsApp quedaron en `animation-name: none`.

## Regla de tema claro / oscuro

Aclaración vigente del banco: **toda landing y todo sitio web lleva modo claro y
modo oscuro, con un interruptor que funciona igual que el del sitio independiente
de Pichanguero**. No es un extra opcional ni una variante de la plantilla: es el
punto 10 de la lista obligatoria de más abajo y se comprueba antes de publicar.

La referencia es `resources/views/pichanguero/` y su documentación
(`resources/views/pichanguero/README.md`, apartado «Tema claro y oscuro»). Como
cada plantilla es un proyecto independiente que se copia a su propio dominio, el
interruptor **no se comparte**: se implementa dentro de cada una, igual que hacen
Pichanguero, KiraFact y Kapta. Lo que sí es idéntico es su interfaz y su
comportamiento:

| Pieza | Convención |
| --- | --- |
| Estado | atributo `data-tema` del `<html>`, con `claro` (valor con el que se sirve) y `oscuro` (solo si el visitante lo eligió) |
| Interruptor | botón en la cabecera, visible también en móvil; si hay panel de menú móvil se repite dentro, porque el panel tapa la cabecera |
| Icono | el del modo AL QUE SE VA: luna en claro, sol en oscuro. Los dos iconos van siempre en el DOM y se cruzan con una transición |
| Etiqueta | «Cambiar a modo oscuro» en claro y «Cambiar a modo claro» en oscuro, en `aria-label` y `title` |
| Accesibilidad | `aria-pressed` en `true` solo con el modo oscuro activo; el botón se trata por clase o atributo (`[data-tema-switch]`), nunca por `id`, porque se repite en la misma página |
| Antes de pintar | un script en línea en el `<head>` aplica la elección guardada antes de la primera pintada: al recargar en oscuro no se ve un destello claro |
| Memoria | `localStorage` con **una clave por plantilla**, `<slug>-tema` (p. ej. `clinica-dental-tema`), más el evento `storage` para que las otras pestañas se pongan al día. En una demo de varias páginas la misma clave hace que el tema elegido viaje de una a otra |
| Sin JavaScript | el interruptor no haría nada, así que se oculta (clase `no-js` en el `<html>`): el sitio se ve completo y en claro |
| Detalles nativos | en oscuro, `color-scheme: dark` para que barras de desplazamiento y campos acompañen |

**Qué cambia y qué no.** El tema es un atributo del documento y los colores los
pone el CSS: el JavaScript solo cambia ese atributo y actualiza el texto del botón.
Lo que se invierte son las **superficies claras** (el fondo del cuerpo y las
secciones de información), que pasan a una versión oscura de la propia paleta;
las secciones que ya son oscuras por diseño —el hero con su velo, la barra
superior, la cabecera, el pie y el botón de WhatsApp— se quedan igual. Ningún
componente cambia de forma, de tamaño ni de posición entre los dos temas, y nadie
que no toque el interruptor recibe el tema oscuro del sistema operativo.

**Sin destello, sin salto.** El guardado se lee en el `<head>`, antes del CSS
de la página. El error típico —aplicar la clase al final del cuerpo— produce el
flash blanco al recargar en oscuro.

**Estado.** Las siete demostraciones publicadas lo traen: `clinica-dental`,
`restaurante-carta` y `portafolio-creativo` (landings) y `academia-cursos` (sitio
de cuatro páginas), `estudio-contable` (sitio de cinco páginas),
`inmobiliaria-propiedades` (sitio de cinco) y `tienda-online` (tienda de cuatro
páginas), en los cuatro sitios con la elección viajando de una página a otra
porque todas comparten la misma clave. No queda ninguna plantilla publicada sin
demostración: la octava que llegue nace con el interruptor, que es obligatorio
para publicar. Lo vigila la prueba
`tests/Unit/CatalogoPlantillasTest.php` → «trae el interruptor de tema claro /
oscuro en toda demostración publicada», que además exige una clave de
`localStorage` distinta por plantilla.

Antes de dar por buena una plantilla nueva, la revisión del tema es: alternar el
interruptor en la cabecera y en el panel de móvil, recargar y comprobar que no
hay destello y que gana la elección guardada, abrir una segunda pestaña y
comprobar que se sincroniza, mirar que ningún texto quede sobre fondo del mismo
color, que el interruptor siga visible con el menú móvil abierto y que sin
JavaScript el interruptor no se muestre y la página se lea completa.

**Los gráficos con fondo propio.** Una fotografía no se toca nunca. Un gráfico
dibujado —como el plano de la ubicación, que es un SVG con la paleta clara— sí
aparece como un rectángulo claro sobre la página oscura: en las dos demos
publicadas se invierte con un filtro (`invert` + giro de tono) y se comprueba a
tamaño real que su detalle y su marca siguen leyéndose. Si un gráfico nuevo
invirtiera mal, es mejor dejarlo tal cual que publicar una mancha oscura.

## Regla de diferenciación (cada plantilla es distinta)

Aclaración vigente del banco: **todas las plantillas son diferentes entre sí**. La lista
obligatoria de más abajo es el mínimo común —lo que el cliente espera encontrar—, no una plantilla
compartida que se rellena cambiando textos y colores. Una demostración que se construye copiando la
hoja de estilos y el JavaScript de la anterior y ajustando detalles (paleta, tipografías de sistema
y textos) **no es una plantilla nueva**: es la misma con otro traje, y se nota al poner las dos
demos a la vez.

Lo que tiene que cambiar de una plantilla a otra son los cuatro ejes del diseño, y no solo el
primero:

| Eje | Qué se decide ahí |
| --- | --- |
| Color | La paleta **y su reparto**: cuánto papel, cuánta tinta, dónde va el acento y qué superficie se lleva el contraste. Dos plantillas pueden compartir el azul y aun así no parecerse. |
| Tipografía | Familias (se admiten pilas del sistema: no hay CDN), tamaños y jerarquía. Serif en titulares y sans en el cuerpo se lee muy distinto de sans en todo. |
| Arquitectura | Cómo se recorre la página: dónde vive el título, si hay margen numerado, si el resumen va en columna, cómo se listan los productos o servicios (tarjetas, tabla, acordeón). |
| Forma y movimiento | Esquinas rectas o redondeadas, filetes o sombras, precio en tarjeta o en hoja de precios, y qué se anima (aparición, contadores, cabecera). |

Sí puede repetirse, porque es el contrato del banco y no su diseño: el hero con slider a pantalla
completa, el botón de WhatsApp, el mapa con enlace, la prueba social, los precios de referencia, las
redes sociales, las anclas de sección, y los nombres que vigilan las pruebas
(`.portada`/`data-carrusel`/`.portada__diapositiva`, `[data-carrusel-anterior]`,
`[data-carrusel-siguiente]`, `[data-carrusel-punto]`, `.carrusel__punto`, `.tema-switch`,
`<slug>-tema`, `html.no-js`). Son las piezas que el estándar exige, no la piel.

**Comprobación antes de publicar:** con las dos demos abiertas a 1440 px y a 390 px, describir la
diferencia sin hablar de color ni de logo. Si la única frase posible es «cambian los colores y las
fotos», no cumple esta regla.

Las siete demostraciones publicadas hoy son distintas entre sí y sirven de ejemplo de siete
lenguajes: `restaurante-carta` (carta impresa, cálido y fotográfico), `clinica-dental` (landing
clara y cercana), `academia-cursos` (institucional con tarjetas y azul profundo),
`estudio-contable` (expediente contable: papel y tinta, titulares en serif, filetes en vez de
tarjetas, listados como libro de entradas y secciones con margen numerado),
`portafolio-creativo` (pliego de taller: papel frío con un solo acento magenta, titulares en sans
mayúscula y cuerpo en serif —el par opuesto al del expediente contable—, retícula de doce columnas
dibujada como filetes y los proyectos como índice numerado de catálogo, no como rejilla de
tarjetas) e
`inmobiliaria-propiedades` (tablero de fichas técnicas: retícula de plano en las secciones de
datos, todo lo que es dato en monoespaciada, la propiedad como ficha con sello de precio
inclinado, franja de color por operación y buscador dentro de la portada) y
`tienda-online` (tienda de casa moderna: lienzo casi blanco para que el color lo ponga la foto
del producto, un solo acento naranja teja para lo que se toca, el precio en el texto —número
grande con el anterior tachado al lado— y sobre la foto solo el descuento en porcentaje y la
etiqueta del producto, y la venta resuelta en dos gestos, la canasta y el pedido por WhatsApp).

## Piezas de la sección

| Pieza | Archivo | Qué hace |
| --- | --- | --- |
| Datos | `config/plantillas.php` | Tipos (formato), categorías y plantillas. **Único** archivo que se edita para publicar algo. |
| Acceso a los datos | `app/Support/CatalogoPlantillas.php` | Filtros, búsqueda y resolución de URLs. Nadie más lee la config. |
| Rutas | `routes/plantillas.php` | `/plantillas`, `/plantillas/{slug}` y `/demo/{slug}`. |
| Controlador | `app/Http/Controllers/PlantillasController.php` | `index`, `ficha`, `demo`. Sin base de datos. |
| Vistas | `resources/views/plantillas/**` y `resources/views/components/plantillas/**` | Catálogo, ficha y tarjeta. Extienden el layout corporativo y reutilizan el navbar y el pie sin modificarlos. |
| Estilos | `resources/css/plantillas/plantillas.css` → `public/themes/plantillas/css/plantillas.css` | CSS propio de la sección (sin Tailwind). Se minifica con `npm run build:plantillas` y solo se carga en las páginas de la sección. |
| Demostraciones | `public/demos/<slug>/` | Cada una es un proyecto estático independiente. |

## Regla de estilos (importante)

La hoja de la sección **no declara ninguna clase sin prefijo `plt-`** ni directivas de Tailwind.
Las utilidades (`max-w-7xl`, `px-4`, `text-ara-slate-700`…) las sirve `public/css/website.css`, que
ya se carga en estas páginas porque comparten el layout corporativo; el config de la raíz escanea
`resources/views/**`, así que cualquier utilidad nueva que se use aquí entra en el CSS del sitio al
compilar el proyecto (`npm run build`).

El motivo no es estético: repetir una utilidad en la hoja de la sección rompe el comportamiento
responsivo de los componentes compartidos. La cascada se decide por orden de aparición y con la
misma especificidad, así que un `.hidden` reemitido aquí le gana al `.lg\:flex` corporativo y el
menú de escritorio del navbar desaparece. Lo vigila la prueba
`tests/Unit/CatalogoPlantillasTest.php` → «compila su hoja de estilos sin declarar clases del sitio».

## Agregar una plantilla

1. Copiar el bloque de una plantilla en `config/plantillas.php` y completarlo
   (los campos están explicados en el encabezado del archivo).
2. Elegir su `tipo`: `landing` o `sitio`. **No es un detalle estético**: es el
   formato que el cliente compara con el plan que le ofrece ventas, por eso se
   pinta como hashtag (`#Landing`, `#SitioWeb`) en la tarjeta y explica en la
   ficha. Si el proyecto no encaja en ninguno de los dos, se agrega un tipo nuevo
   en `tipos` (con `nombre`, `hashtag` y `descripcion`) en vez de dejarlo vacío.
3. Escribir su `imagen`: la captura 16/10 de la demostración en
   `public/themes/plantillas/images/<slug>.jpg` (ver «Capturas del catálogo»).
   Mientras sea `null`, la tarjeta muestra el mosaico «Captura en preparación»;
   eso es correcto solo mientras la plantilla **no** tenga demostración que
   capturar.
4. `php artisan config:clear` (o `config:cache` en producción).
5. Añadir su ficha a `public/sitemap.xml` **cuando la plantilla tenga
   demostración publicada** y copiar ahí el mismo `lastmod` que se escribió en
   `actualizada`: mientras no haya demo, la ficha se enlaza desde el catálogo
   pero no se empuja desde el sitemap.

## Capturas del catálogo

La tarjeta del catálogo y el encabezado de la ficha se comparan por la **captura** de la
demostración: es lo primero que mira quien elige plantilla. Por eso, apenas una demo se publica, se
captura y se escribe su ruta en `imagen` de `config/plantillas.php`.

- **Formato:** 16/10 exacto, 1600 × 1000 px, JPEG calidad 82 (entre 90 y 210 KB). Es la proporción
de `.plt-card__media`, que pinta la imagen con `object-fit: cover` y `object-position: top center`:
con otra medida se recortaría el diseño capturado.
- **Dónde vive:** `public/themes/plantillas/images/<slug>.jpg`, y en la config se escribe la ruta
relativa a `public/` (`'themes/plantillas/images/clinica-dental.jpg'`).
- **Qué se captura:** la parte de arriba de la demostración (su hero), que es lo que se ve en la
tarjeta. El tema de la captura es el claro, que es el que trae la demo de fábrica: el navegador de
la captura arranca sin `localStorage`.
- **Cómo se captura:** con Chrome en modo sin interfaz, a 1600 × 1000 y sin barra de desplazamiento.

```bash
chrome --headless=new --hide-scrollbars --force-device-scale-factor=1 \
       --window-size=1600,1000 --virtual-time-budget=8000 \
       --no-first-run --user-data-dir=<perfil temporal, no el del usuario> \
       --screenshot=<salida>.png http://127.0.0.1:8123/demos/<slug>/
```

El `--user-data-dir` es obligatorio: sin él Chrome puede reutilizar el perfil abierto del usuario en
vez de arrancar uno limpio. Los guiones del banco son `storage/tmp/capturas-preparar.php` (pasa los
PNG a JPEG en su carpeta final y **se niega** si alguna no es 16/10 o no se puede leer) y
`storage/tmp/capturas-hoja.php`, que arma una hoja de contactos para revisar las siete de un vistazo
antes de publicarlas.

Lo vigila la prueba `tests/Unit/CatalogoPlantillasTest.php` → «publica la captura 16/10 de cada
demostración»: exige que toda plantilla con demostración tenga captura, que exista, que sea 16/10
con al menos 1200 px de ancho, que pese menos de 300 KB, que no haya salido en blanco ni en negro y
que ninguna captura se repita; además comprueba que el catálogo pinte la imagen y que el mosaico
quede solo donde no hay demostración.

### En la tarjeta social

La misma captura es la imagen que se ve al compartir el enlace de la ficha (WhatsApp, LinkedIn, X):
16/10 es la proporción que esas tarjetas esperan, así que la captura no se recorta. La ficha declara
`og_image`, `og:image:width`/`height` en 1600 × 1000 y `og:image:alt`, y el layout del sitio
(`aracode/layouts/webpage.blade.php`) refleja ese dato en `twitter:image`: las dos familias salen de
la misma variable, así que no pueden contradecirse. El título y la descripción sociales son los
mismos que ya usa la página (`meta_title` y `resumen`), y la URL sale absoluta con `asset()`.

Una plantilla **sin** captura no anuncia imagen propia: hereda la del sitio y tampoco declara
medidas, que serían mentira. Lo vigila la prueba «comparte la captura como imagen social de la
ficha».

## Agregar una demostración

La demostración **no** es una variación del catálogo: es un proyecto web propio.
La referencia terminada y usable como modelo es `public/demos/clinica-dental/` cuando la
plantilla es una **landing** —y `public/demos/portafolio-creativo/` cuando esa landing es un
**portafolio de piezas**, con el trabajo mostrado como índice numerado en vez de tarjetas— y
`public/demos/academia-cursos/` cuando es un **sitio web** (varias
páginas); `public/demos/estudio-contable/` es el segundo sitio web y sirve de ejemplo cuando el
negocio vende **servicios con planes** en vez de un catálogo de productos (precios en tarjetas y en
tabla comparativa, y un formulario de consulta en lugar de uno de matrícula), y
`public/demos/inmobiliaria-propiedades/` es el tercero y sirve de ejemplo cuando el sitio es un
**catálogo con buscador**: filtros que leen los datos de atributos en el HTML, orden, favoritos en
`localStorage`, ficha de un elemento con galería y simulador, y —solo ahí— un **plano con
medidas**. Ese plano no se dibuja a mano: lo genera `storage/tmp/plano-inmobiliaria.php`, que
declara cada ambiente en metros y **falla si la planta no cierra** (suma de superficies distinta a
la de la planta, o dos ambientes que se solapan).

En una demo de **tienda** hay una copia más que en las demás: el catálogo y los destacados están
escritos en el HTML —los filtros leen los atributos `data-` de cada tarjeta, no la memoria del
JavaScript— y los mismos datos viven en `PRODUCTOS` (`js/app.js`). Las dos copias las mantiene
`node storage/tmp/casa-tarjetas.mjs`, que reescribe desde esos datos las rejillas del catálogo y del
escaparate, los atajos de ambiente y las colecciones; es lo que hay que volver a correr después de
tocar un producto, un precio o un recuento. El guion abre y cierra cada bloque contando `<div>`,
así que no depende de cómo quedó la sangría la vez anterior.

Las fotos de una demo se eligen en `storage/tmp/candidatas-<slug>*/` (una carpeta por ronda de
búsqueda, con `datos.json` y una hoja de contactos), se revisan de un vistazo con
`rejilla-candidatas.php` y —cuando el panel de vista previa no compone fotogramas— con
`auditar-fotos.php`, que imprime de cada imagen su tamaño, su brillo y un mapa de luminancia en
ASCII; lo elegido se recorta y se pasa a WebP en `fotos-<slug>-preparar.php`. Todas llevan sus
créditos de fotos y su lista de reemplazos en `CREDITOS.md`.

### Lista obligatoria de toda demostración

Lo que sigue es el estándar del banco: una plantilla nueva no se publica sin
cumplirlo. Los 11 puntos se comprueban antes de enlazarla desde la ficha.

1. **Portada: hero o slider, a pantalla completa.** Es lo primero que se mira y
   lo que decide si el visitante sigue: ninguna plantilla se publica con una
   portada discreta. El hero ocupa la pantalla (`min-height` de unas 88 unidades
   de viewport, nunca menos), lleva una foto real a sangre y un velo oscuro que
   garantice el contraste; el titular, la propuesta, los botones y las garantías
   van encima. Cuando hay tres o más fotos horizontales —lo normal— el fondo
   rota como **slider**: flechas y puntos en una pastilla de control, paso
   automático que se detiene al pasar el ratón o al enfocar, arrastre con el
   dedo y `prefers-reduced-motion` que apaga solo el paso automático (a mano
   sigue funcionando). Responsive de verdad: en móvil la pastilla baja debajo
   del texto en vez de taparlo, los puntos tienen área táctil de 44 px y ningún
   texto depende de un borde de la foto. Sin JavaScript se ve la primera foto y
   la página se lee completa; el hero nunca es la única forma de leer algo.
2. **Fotografía real.** Como mínimo tres horizontales en la portada (el hero),
   una vertical en la sección de confianza y una horizontal de detalle. Locales,
   en `img/`, reescaladas (máximo 1600 px de ancho) y en WebP, con `alt`
   descriptivo, `loading="lazy"` y `width`/`height`. La procedencia se anota en
   `CREDITOS.md`: fotos de banco sin obligaciones (CC0 o dominio público) o con
   la atribución incluida. Nunca fotos de personas sin permiso, y ninguna foto
   con marca de agua de un banco de imágenes: se mira a tamaño real antes de
   publicarla.
3. **WhatsApp y ubicación.** Botón flotante en toda la página, enlaces `wa.me`
   con mensaje ya escrito, y una forma real de llegar: dirección, horarios y
   mapa (propio o embebido) con enlace para abrir en Google Maps.
4. **Prueba social.** Tres testimonios como mínimo, con valoración y autor
   identificable —en la demo se usan iniciales, no caras de terceros— y las
   cifras de confianza (pacientes, años, especialidades) en la portada.
5. **Precios de referencia.** Tarifas visibles en tarjetas o tabla, con una nota
   de que son referenciales y una llamada a la acción en cada una.
6. **Redes sociales.** Iconos de Instagram, Facebook, TikTok y WhatsApp en la
   barra superior y/o el pie; en la demo de referencia están en los dos. Cada
   icono lleva `aria-label`, `target="_blank"` y `rel="noopener"`, y apunta a
   los perfiles del negocio.
7. **Diseño y movimiento.** Aparición al hacer scroll, contadores animados donde
   haya cifras, cabecera con sombra al bajar, hover en tarjetas y botones,
   `:focus-visible` visible y respeto de `prefers-reduced-motion`. Si hay una
   tabla comparativa (planes, precios, características), se desplaza en
   horizontal dentro de su contenedor en el celular en vez de apretar las
   columnas, y el `body` no gana scroll lateral.
8. **Honestidad de la demo.** Todo dato inventado (teléfonos, precios,
   testimonios, dirección) es de ejemplo, el WhatsApp usa un número que no
   existe y el formulario avisa de que no envía datos.
9. **Rendimiento y aislamiento.** Sin frameworks, sin CDN y sin scripts de
   terceros (mapas o analítica): todo local. Cada imagen por debajo de unos
   250 KB y el JavaScript del sitio en un solo archivo.
10. **Modo claro y oscuro.** Interruptor en la cabecera (y en el panel de móvil)
   que cambia el atributo `data-tema` del `<html>` entre `claro` y `oscuro`, con
   el mismo comportamiento y las mismas etiquetas que el de Pichanguero: icono
   del modo al que se va (luna en claro, sol en oscuro), `aria-pressed`, elección
   recordada en `localStorage` bajo `<slug>-tema`, aplicación antes de pintar
   (sin destello al recargar) y oculto sin JavaScript. Las secciones oscuras por
   diseño no cambian: lo que se invierte son las superficies claras. El detalle
   completo está en «Regla de tema claro / oscuro».
11. **Comprobación final.** 200 en la carpeta y en cada archivo (CSS, JS,
   imágenes), consola sin errores, revisión a 1400 px y a 390 px —con el slider
   avanzado a mano para ver la segunda foto en las dos medidas—, el interruptor
   probado en los dos temas con recarga de por medio, y la ficha del catálogo
   enlazando la demo.

### Reglas de convivencia con el sitio corporativo

La demostración **no** es una variación del catálogo: es un proyecto web propio.
Reglas:

1. Vive en su carpeta, una por plantilla: `public/demos/<slug>/`.
2. Todo suyo: sus páginas (`index.html` y las demás si es de tipo sitio), su CSS,
   su JS y sus imágenes. **No** usa Blade, ni componentes, ni estilos, ni fuentes
   del sitio corporativo, y el sitio corporativo no usa nada suyo.
   - Una demo de **landing** es `index.html` y ya.
   - Una demo de **sitio** son varias páginas enlazadas con rutas relativas
     (inicio, listado, ficha y contacto o matrícula) y con **un solo** CSS y **un
     solo** JS compartidos: el modelo es `public/demos/academia-cursos/`.
   - El nombre del archivo de una ficha interior no se genera: se escribe a mano
     (`curso-desarrollo-web.html`) y se copia para el siguiente, porque el
     catálogo solo guarda la carpeta de la demo, no sus páginas.
3. **Rutas relativas** (`css/estilos.css`, `js/app.js`, `img/…`). Así la carpeta
   se puede copiar a otro servidor o a su subdominio sin tocar el HTML.
4. `noindex` en su propio HTML. Lo que posiciona es la ficha de la plantilla; la
   demo es para verla funcionando.
5. Nada de PHP. `public/demos/.htaccess` bloquea la ejecución de scripts en toda
   la rama, así que una plantilla descargada no puede traer sorpresas.
6. En `config/plantillas.php`, `demo` = nombre de la carpeta (`'clinica-dental'`).
   La ficha solo ofrece «Ver la demostración» si `public/demos/<slug>/index.html`
   existe de verdad: no se puede publicar un enlace roto.

La URL pública es `/demo/<slug>` (redirección **302** a `/demos/<slug>/`, que es
la carpeta que sirve el propio servidor web). Es 302 y no 301 porque el destino
puede mudarse; ver más abajo.

`CREDITOS.md` vive dentro de la carpeta de la demo: anota la licencia de las
fotos y la lista de lo que hay que reemplazar al publicar el sitio de un cliente
(fotos, datos de ejemplo, perfiles de redes y número de WhatsApp). En una demo de
tipo sitio añade, además, qué archivos hay que tocar a la vez al cambiar el
catálogo de productos o servicios (en la academia: las tarjetas de la lista y las
opciones del formulario de matrícula).

Sirve sin tocar nada de configuración del servidor porque el `DocumentRoot` es
`public/` y el `.htaccess` del sitio solo manda a Laravel lo que **no** es un
archivo o carpeta reales (comprobado en
[BANCO_PLANTILLAS_FASE1.md](BANCO_PLANTILLAS_FASE1.md)). En producción (nginx) el
equivalente es que `try_files` deje pasar los archivos existentes, que ya es el
caso.

## El día que una demo pase a su propio subdominio

1. Copiar la carpeta `public/demos/<slug>/` al servidor del subdominio (o al
   repositorio de la plantilla). Es autocontenida: no hay nada que adaptar.
2. En `config/plantillas.php`, cambiar el nombre de carpeta por la URL absoluta:
   `'demo' => 'https://clinica-dental.aracodeperu.com'`. `CatalogoPlantillas`
   respeta las URLs absolutas tal cual, así que el catálogo y la ficha apuntan
   al subdominio sin tocar ninguna vista.
3. `php artisan config:clear` y comprobar con `curl` que `/plantillas/<slug>`
   enlaza al subdominio.
4. Pendiente de infraestructura en ese momento: DNS del subdominio, su
   `VirtualHost`/`server` block y HTTPS. **No** hace falta tocar el `.htaccess`
   del sitio ni las rutas de Laravel.

Mientras eso no ocurra, el aislamiento es real pero comparte servidor: no se debe
afirmar que cada demo es un despliegue independiente, porque no lo es. Lo que sí
es independiente desde ahora es el **proyecto** (código, estilos, recursos y
navegación), que es lo que permite la mudanza sin trabajo extra.
