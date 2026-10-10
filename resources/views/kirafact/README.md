# Sitio web de KIRAFACT

Web comercial del producto **KIRAFACT** (facturación electrónica y gestión empresarial),
desarrollada por ARACODE SMART SOLUTIONS. Está hecha en **Blade puro**: sin Inertia, sin Vue,
sin controladores y sin `webpage-v2.css`. Se puede copiar completa a su propio dominio.

La web comercial es una cosa y el **sistema KIRAFACT** es otra: esta web presenta el producto y su
única acción es pedir una **demo** por WhatsApp. No reconstruye el sistema, no crea pantallas de
login, no pide credenciales y no ofrece ningún acceso de clientes.

## Qué contiene

| Zona | Contenido |
| --- | --- |
| `resources/views/kirafact/` | `layouts/`, `components/` y `pages/` del sitio |
| `resources/css/kirafact/` | Fuente de estilos (`kirafact.css` + `tailwind.config.js` propio) |
| `public/themes/kirafact/` | `css/` compilado, `js/app.js` e `images/` que sirve el navegador |
| `resources/og/` | Fuente editable de la imagen para compartir (no se publica) |
| `config/kirafact.php` | Contenido comercial, marca, enlaces y datos pendientes |
| `routes/kirafact.php` | Rutas del sitio (Blade puro, sin controladores) |

## Rutas

| URL en este repositorio | Vista |
| --- | --- |
| `/site/kirafact` | `kirafact.pages.home` |
| `/site/kirafact/planes` | `kirafact.pages.planes` |
| `/site/kirafact/contacto` | `kirafact.pages.contacto` |

El prefijo `site/kirafact` existe solo para convivir con el sitio ARACODE dentro de este
repositorio.

## Estructura de la portada

Una sola página, por secciones, cada una en su parcial:

| Sección | Parcial | Ancla |
| --- | --- | --- |
| Hero y mensaje principal | `components/hero.blade.php` | `#inicio` |
| Qué es KIRAFACT | `components/producto.blade.php` | `#producto` |
| Funcionalidades publicadas | `components/funcionalidades.blade.php` | `#funcionalidades` |
| Beneficios | `components/beneficios.blade.php` | `#beneficios` |
| Recorrido del software | `components/software.blade.php` | `#software` |
| Solicitud de demo | `components/demo.blade.php` | `#demo` |
| Preguntas frecuentes | `components/preguntas.blade.php` | `#preguntas` |
| Cierre y contacto | `components/cta-final.blade.php` | — |

Piezas reutilizables: `components/navbar.blade.php`, `components/footer.blade.php`,
`components/marca.blade.php` (marca y logotipo), `components/icon.blade.php` (iconos lineales),
`components/btn-demo.blade.php` (botón «Solicitar una demo»),
`components/tema-switch.blade.php` (interruptor de tema claro / oscuro),
`components/mock.blade.php` (composición del software) y `components/encabezado.blade.php`
(cabecera de páginas internas).

## Compilar el CSS

```bash
npm run build:kirafact
```

Genera `public/themes/kirafact/css/kirafact.css` a partir de `resources/css/kirafact/kirafact.css`
y de su propia config de Tailwind. El JS (`public/themes/kirafact/js/app.js`) es vanilla y se sirve
tal cual, sin bundler.

## Configuración: qué se cambia y dónde

Todo el contenido comercial está en `config/kirafact.php`. Los colores están en **dos** sitios que
hay que cambiar juntos: `resources/css/kirafact/tailwind.config.js` y el bloque `:root` de
`resources/css/kirafact/kirafact.css`.

### 1. Solicitud de demo (acción principal)

El sitio entero apunta a una sola acción: **«Solicitar una demo»**. Ese es el texto de los seis
puntos donde aparece el botón (cabecera, menú móvil, hero, sección de demo, cierre y pie) y sale de
un único sitio: `config('kirafact.demo.boton')`.

El destino tampoco se duplica: `components/btn-demo.blade.php` abre WhatsApp (`contacto.whatsapp_url`)
con el mensaje `mensajes.demo_whatsapp` ya escrito. Es un enlace real, así que funciona sin backend
y sin JavaScript.

El pie deja el correo real como alternativa, y la sección de demo lo repite por si alguien prefiere
escribir antes de que le contestemos por WhatsApp.

> Nota: aquí **no** hay acceso de clientes. Si algún día existe el portal, se añade como una página
o un enlace aparte; el botón de demo no debe reconvertirse en botón de ingreso, porque el sitio
> no tiene forma de saber si un visitante ya es cliente.

### 2. Logotipo oficial

El repositorio **no contiene** ninguna versión dibujada, recoloreada ni reinterpretada del
logotipo. Donde iría el logotipo hay un hueco reservado con el nombre como texto. Para publicarlo:

1. Deja los archivos en `public/themes/kirafact/images/`.
2. Declara sus rutas en `config('kirafact.marca')`:
   `'logo' => 'themes/kirafact/images/kirafact-logo.svg'` (fondos claros) y
   `'logo_dark' => 'themes/kirafact/images/kirafact-logo-dark.svg'` (fondos azul marino).
3. Si solo existe una versión, usa la misma ruta en las dos claves.

La cabecera alterna sola entre las dos versiones según su fondo (transparente sobre el hero, blanco
al desplazar). El **favicon** actual (`images/favicon.svg`) es una marca de color provisional sin
símbolo de marca: reemplázalo por el favicon derivado del logotipo oficial.

### 3. Capturas reales del sistema

`config('kirafact.software.capturas')` es una lista de rutas. Hoy está vacía y la sección muestra
una composición hecha en HTML y CSS, rotulada como **«Imagen referencial»**, que no finge ser una
captura y no contiene RUC, nombres de clientes ni importes. Al declarar capturas reales, la
composición desaparece sola.

### 4. Funcionalidades

Se publican solo las marcadas con `'publicado' => true`, que son las que ARACODE ya comunica hoy.
Las áreas candidatas (inventario y kardex, compras y proveedores, punto de venta, cotizaciones y
guías de remisión, información de rentabilidad) están en la misma lista con
`'publicado' => false`: **no se renderizan** hasta confirmarlas contra el sistema real.

### 5. Planes y precios

`config('kirafact.planes.publicar')` está en `false`, así que `/site/kirafact/planes` **no publica
importes**: explica que la tarifa se confirma con el equipo comercial y ofrece el contacto real.
Las tarifas de referencia que maneja ARACODE (Inicio S/ 39 mensuales y S/ 390 anuales, Profesional
S/ 69 mensuales, Empresarial S/ 120 mensuales) quedan en config, sin publicar, porque todavía no se
confirmó su vigencia ni las prestaciones de cada plan. Al poner `publicar` en `true` la página
muestra las tarjetas con los datos de config, sin tocar la vista.

### 6. Contacto, páginas legales y metadatos

- `config('kirafact.contacto')`: WhatsApp, correo, ciudad y web. Son los datos reales de ARACODE
  (verificados contra el pie del sitio corporativo); no se inventó ninguno.
- `config('kirafact.legal')`: solo se enlazan las páginas que existan. Hoy están en `null` y no se
  muestra ningún enlace.
- `config('kirafact.seo')`: título, descripción, imagen para compartir y `KIRAFACT_CANONICAL_URL`
  (vacío; mientras tanto se usa la URL actual como canónica).

### 7. Imagen para compartir

`public/themes/kirafact/images/og-image.png` es la tarjeta de 1200×630 que se ve al compartir el
enlace (Open Graph y Twitter). Lleva el nombre, el descriptor y la firma **como texto**, más una
composición abstracta de las pantallas: no incluye ningún símbolo de marca inventado.

Su fuente editable es `resources/og/kirafact-social.html` (1200×630 exactos, colores del sitio). Se
vuelve a generar con Chrome headless:

```bash
# Desde la raíz del proyecto, en Git Bash (las rutas deben ser absolutas:
# Chrome headless resuelve las relativas contra su propio directorio y falla)
"/c/Program Files/Google/Chrome/Application/chrome.exe" --headless=new --disable-gpu \
  --hide-scrollbars --force-device-scale-factor=1 --window-size=1200,630 \
  --virtual-time-budget=6000 \
  --screenshot="$(pwd -W)/public/themes/kirafact/images/og-image.png" \
  "file:///$(pwd -W)/resources/og/kirafact-social.html"
```

La composición usa Inter desde Google Fonts; si no hay red, cae en la tipografía del sistema y el
resultado cambia un poco. El lienzo debe medir exactamente 1200×630 y no llevar scroll: si se
cambia el texto, hay que revisar que nada quede cortado antes de regenerar.

Los metadatos se publican desde `config('kirafact.seo')` (`og_image`, ancho, alto y tipo). Cuando
exista el logotipo oficial, esta tarjeta se rehace con él: es el único recurso gráfico del sitio
que hoy lleva la marca escrita con texto.

## Movimiento y animaciones

El sitio usa dos mecanismos, cada uno dueño de sus propiedades para que no se pisen:

| Mecanismo | Qué mueve | Dónde vive |
| --- | --- | --- |
| Revelado escalonado (transición + IntersectionObserver) | Bloques `.kf-reveal`: entran con opacidad, desplazamiento y retardo creciente | `public/themes/kirafact/js/app.js` + `.js .kf-reveal` en el CSS |
| Animaciones nativas ligadas al scroll (`animation-timeline`) | Barra de progreso de lectura, deriva del hero, riel de los pasos, números que se encienden y halo del cierre | `resources/css/kirafact/kirafact.css`, sección «Movimiento ligado al scroll» |

No hay librerías de animación: el movimiento lo resuelve el compositor del navegador. El JS solo
calcula el retardo de cada bloque (`--kf-delay`, 70 ms por vecino dentro de la misma sección) y, si
el navegador no soporta `animation-timeline: scroll()`, mueve la barra de progreso con `scaleX`.

**Todo el movimiento se apaga solo.** Las animaciones están dentro de
`@media (prefers-reduced-motion: no-preference)`: quien pide menos movimiento no recibe ninguna de
ellas y la barra de progreso ni se muestra. El contenido nunca depende de una animación para verse
(verificado: con el movimiento reducido activo no queda ningún bloque invisible).

### Cosas que conviene saber antes de tocarlo

- **El atajo `animation` reinicia `animation-timeline` a `auto`.** Por eso la línea de tiempo va
  siempre *después* del atajo, en la misma regla. Si se escribe antes, la animación se queda sin
  línea y salta directamente a su estado final.
- **Un elemento dentro de un contenedor con `overflow: hidden` no puede tener línea de tiempo
  propia**: su contenedor de scroll no se desplaza y el progreso se queda congelado. Por eso el
  hero y el cierre declaran una línea con nombre (`view-timeline-name: --kf-hero` / `--kf-cierre`)
  que sus elementos decorativos comparten.
- **El minificador convierte `0%` en `0`** dentro de `animation-range`, que es inválido ahí. Los
  rangos se escriben como `cover` (rango completo) o con porcentajes distintos de cero.
- Para ver el movimiento en Windows hay que tener activados los «efectos de animación» del sistema;
  si están apagados, el navegador pide movimiento reducido y el sitio se muestra quieto (que es lo
  correcto). En ese caso se puede comprobar igual midiendo estilos computados, no capturas.

## Tema claro y oscuro

La cabecera lleva un interruptor que cambia entre **modo claro y modo oscuro**. Muestra el icono
del modo al que se va: con el sitio en claro se ve una **luna** (pasa a oscuro) y con el sitio en
oscuro se ve un **sol** (vuelve a claro).

| Pieza | Dónde vive |
| --- | --- |
| Interruptor | `resources/views/kirafact/components/tema-switch.blade.php` (dos instancias: cabecera y menú móvil) |
| Colores del tema oscuro | `resources/css/kirafact/kirafact.css`, sección «Tema claro y oscuro» |
| Cambio de tema y memoria | `public/themes/kirafact/js/app.js`, apartado 6 |
| Aplicación antes de pintar | `resources/views/kirafact/layouts/app.blade.php` (script en línea del `<head>`) |

**Cómo funciona.** El tema es el atributo `data-tema` del `<html>`: `claro` (valor con el que se
sirve la página) u `oscuro`, que solo se pone si el visitante lo eligió antes. La elección se guarda
en `localStorage` bajo la clave `kf-tema` y el script del `<head>` la aplica antes de la primera
pintada, así que al recargar en oscuro no se ve un destello blanco. En claro el sitio se sirve tal
cual estaba: nadie que no toque el interruptor recibe el tema oscuro del sistema operativo.

**El modo oscuro no es una hoja aparte.** El sitio ya estaba construido sobre variables por
superficie, así que oscurecerlo es redefinir esas mismas variables bajo `html[data-tema='oscuro']`.
Solo se reescribe lo que no lee variables: el fondo del documento, la cabecera pegada al scroll y
el panel del menú móvil. La paleta vive en un único bloque (`--kf-noche-*`), así que ajustar un tono
es cambiar una línea. La jerarquía se conserva invertida: en claro el blanco es la superficie más
clara y el azul marino la más profunda; en oscuro las secciones claras suben a azul profundo y el
azul marino baja a casi negro, de modo que la página mantiene relieve en lugar de ser un plano.

**Sin JavaScript** el interruptor no puede hacer nada, así que no se muestra: el sitio se ve
completo y en claro, igual que antes.

## Copiar el sitio a su propio dominio

1. Copia estas cuatro zonas juntas: `resources/views/kirafact/`, `resources/css/kirafact/`,
   `public/themes/kirafact/` y `routes/kirafact.php` — más `config/kirafact.php` y el script
   `build:kirafact` de `package.json`.
2. En `routes/kirafact.php` cambia `$prefix` a `''` para que el sitio quede en la raíz.
3. Define `KIRAFACT_CANONICAL_URL` en el entorno.
4. Vuelve a compilar el CSS: `npm run build:kirafact`.

## Contenido pendiente de confirmación

Nada de esta lista se publica por aproximación; el sitio funciona sin ello:

- Logotipo oficial de KIRAFACT (versiones clara y oscura) y favicon derivado.
- Capturas reales del sistema.
- Vigencia de precios y prestaciones de cada plan.
- **Rehacer la imagen para compartir con el logotipo oficial**: hoy hay una tarjeta de 1200×630 que
  lleva el nombre como texto (ver «Imagen para compartir»).
- Páginas de privacidad y términos legales.
- Validación técnica de los módulos marcados con `'publicado' => false`.
- Datos de soporte, disponibilidad y respaldo: por eso no se afirman.

## Decisiones de diseño

- **Paleta**: azul marino `#0B1B3A` para profundidad, azul tecnológico `#168CF0` para acentos,
  blanco y gris claro `#F3F6FA` para el contenido. Los tonos son provisionales hasta que exista el
  manual de marca: **no** están tomados del logotipo oficial (ese archivo no está en el repositorio).
  Para textos y enlaces sobre fondo claro se usa un azul más profundo (`#0E6FC4` / `#0B5A9E`),
  porque el azul de marca con texto no alcanza contraste AA.
- **Superficies**: cada sección declara su superficie (`kf-surface-light`, `kf-surface-mist`,
  `kf-surface-dark`) y los componentes leen variables CSS. La misma tarjeta, etiqueta o botón
  funciona sobre blanco, gris y azul marino sin duplicar estilos. El tema oscuro se apoya
  exactamente en eso (ver «Tema claro y oscuro»).
- **Sin recursos prestados**: no se usan fotos de stock ni capturas de otras plataformas. La única
  imagen del tema es la provisional para compartir en redes.
- **Mejora progresiva**: sin JavaScript el sitio se ve completo (los bloques que aparecen al
  desplazar solo se ocultan cuando hay JS), los acordeones son `<details>` nativos, los botones son
  enlaces reales y el interruptor de tema simplemente no aparece.
