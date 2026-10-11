# Banco de Plantillas Web — cómo se mantiene

La auditoría y el plan están en [BANCO_PLANTILLAS_FASE1.md](BANCO_PLANTILLAS_FASE1.md).
Este documento es la parte operativa: cómo agregar una plantilla, cómo agregar su
demostración y qué hacer el día que una demo se mude a su propio subdominio.

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
3. `php artisan config:clear` (o `config:cache` en producción).
4. Añadir su ficha a `public/sitemap.xml` **cuando la plantilla tenga
   demostración publicada** y copiar ahí el mismo `lastmod` que se escribió en
   `actualizada`: mientras no haya demo, la ficha se enlaza desde el catálogo
   pero no se empuja desde el sitemap.

## Agregar una demostración

La demostración **no** es una variación del catálogo: es un proyecto web propio.
La referencia terminada y usable como modelo es
`public/demos/clinica-dental/` (con sus créditos de fotos en `CREDITOS.md`).

### Lista obligatoria de toda demostración

Lo que sigue es el estándar del banco: una plantilla nueva no se publica sin
cumplirlo. Los 9 puntos se comprueban antes de enlazarla desde la ficha.

1. **Fotografía real.** Como mínimo una foto horizontal en la portada, una
   vertical en la sección de confianza y una horizontal de detalle. Locales, en
   `img/`, reescaladas (máximo 1600 px de ancho) y en WebP, con `alt`
   descriptivo, `loading="lazy"` y `width`/`height`. La procedencia se anota en
   `CREDITOS.md`: fotos de banco sin obligaciones (CC0 o dominio público) o con
   la atribución incluida. Nunca fotos de personas sin permiso.
2. **WhatsApp y ubicación.** Botón flotante en toda la página, enlaces `wa.me`
   con mensaje ya escrito, y una forma real de llegar: dirección, horarios y
   mapa (propio o embebido) con enlace para abrir en Google Maps.
3. **Prueba social.** Tres testimonios como mínimo, con valoración y autor
   identificable —en la demo se usan iniciales, no caras de terceros— y las
   cifras de confianza (pacientes, años, especialidades) en la portada.
4. **Precios de referencia.** Tarifas visibles en tarjetas o tabla, con una nota
   de que son referenciales y una llamada a la acción en cada una.
5. **Redes sociales.** Iconos de Instagram, Facebook, TikTok y WhatsApp en la
   barra superior y/o el pie; en la demo de referencia están en los dos. Cada
   icono lleva `aria-label`, `target="_blank"` y `rel="noopener"`, y apunta a
   los perfiles del negocio.
6. **Diseño y movimiento.** Aparición al hacer scroll, contadores animados donde
   haya cifras, cabecera con sombra al bajar, hover en tarjetas y botones,
   `:focus-visible` visible y respeto de `prefers-reduced-motion`.
7. **Honestidad de la demo.** Todo dato inventado (teléfonos, precios,
   testimonios, dirección) es de ejemplo, el WhatsApp usa un número que no
   existe y el formulario avisa de que no envía datos.
8. **Rendimiento y aislamiento.** Sin frameworks, sin CDN y sin scripts de
   terceros (mapas o analítica): todo local. Cada imagen por debajo de unos
   250 KB y el JavaScript del sitio en un solo archivo.
9. **Comprobación final.** 200 en la carpeta y en cada archivo (CSS, JS,
   imágenes), consola sin errores, revisión a 1400 px y a 390 px, y la ficha del
   catálogo enlazando la demo.

### Reglas de convivencia con el sitio corporativo

La demostración **no** es una variación del catálogo: es un proyecto web propio.
Reglas:

1. Vive en su carpeta, una por plantilla: `public/demos/<slug>/`.
2. Todo suyo: su `index.html`, su CSS, su JS y sus imágenes. **No** usa Blade, ni
   componentes, ni estilos, ni fuentes del sitio corporativo, y el sitio
   corporativo no usa nada suyo.
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
(fotos, datos de ejemplo, perfiles de redes y número de WhatsApp).

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
