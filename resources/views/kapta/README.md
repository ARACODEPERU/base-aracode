# Sitio web de KAPTA LMS

Página comercial del producto **KAPTA LMS**, hecha en **Blade puro** (sin Inertia, sin Vue y sin
assets de ARACODE). Está pensada para copiarse a su propio dominio junto con todo su ecosistema.

## Qué contiene

| Zona | Contenido |
| --- | --- |
| `resources/views/kapta/` | `layouts/`, `components/` y `pages/` del sitio |
| `resources/css/kapta/` | Fuente de estilos (`kapta.css` + `tailwind.config.js` propio) |
| `public/themes/kapta/` | `css/` compilado, `js/app.js` y `images/` que sirve el navegador |
| `routes/kapta.php` | Rutas del sitio (Blade puro, sin controladores) |
| `config/kapta.php` | Planes, precios, contacto y recursos pendientes |

## Rutas

| URL en este repositorio | Vista |
| --- | --- |
| `/site/kapta` | `kapta.pages.home` |
| `/site/kapta/planes` | `kapta.pages.planes` |
| `/site/kapta/contacto` | `kapta.pages.contacto` |

El prefijo `site/kapta` existe solo para convivir con el sitio ARACODE en este repositorio.

## Compilar el CSS

```bash
npm run build:kapta
```

Genera `public/themes/kapta/css/kapta.css` a partir de `resources/css/kapta/kapta.css`
y de su propia config de Tailwind.

## Identidad visual

La paleta sale del logotipo y se declara dos veces: como tokens de Tailwind
(`ka-*`, en `resources/css/kapta/tailwind.config.js`) y como variables CSS (`--ka-*`,
en `kapta.css`), para que las piezas se puedan reutilizar fuera de Tailwind.

| Uso | Color |
| --- | --- |
| Azul marino: hero, bandas destacadas, pie | `#0B1740` |
| Azul principal: botones, enlaces, elementos interactivos | `#0188EE` |
| Azul para superficies con texto (cumple AA con blanco) | `#0173CE` |
| Celeste: acentos, indicadores, degradados discretos | `#42C5F5` |
| Gris claro: fondo de secciones informativas | `#F3F6FA` |
| Azul grisáceo: textos sobre fondo claro | `#26344F` |

Nota de contraste: el azul de marca `#0188EE` con texto blanco encima se queda en 3.6:1, así que
las superficies que llevan texto usan `#0173CE` (4.8:1) y el azul de marca queda para acentos,
bordes y estados hover. El celeste nunca se usa como color de texto sobre blanco (1.99:1).

Tipografía: **Plus Jakarta Sans** para titulares y **Inter** para interfaz, textos y datos
(cargadas desde Google Fonts en `layouts/app.blade.php`).

Arquitectura de estilos: cada sección declara una superficie (`ka-surface-light`,
`ka-surface-mist`, `ka-surface-dark`) que redefine variables CSS. Los componentes (tarjetas,
botones, etiquetas, mockups) leen esas variables, así que la misma pieza funciona sobre blanco,
sobre gris y sobre azul marino sin duplicar estilos.

### Tema claro y oscuro

La cabecera lleva un interruptor que cambia entre **modo claro y modo oscuro**. Muestra el icono
del modo al que se va: con el sitio en claro se ve una **luna** (pasa a oscuro) y con el sitio en
oscuro se ve un **sol** (vuelve a claro).

| Pieza | Dónde vive |
| --- | --- |
| Interruptor | `resources/views/kapta/components/theme-switch.blade.php` (dos instancias: cabecera y menú móvil) |
| Colores del tema oscuro | `resources/css/kapta/kapta.css`, sección 9 («Tema claro y oscuro») |
| Cambio de tema y memoria | `public/themes/kapta/js/app.js`, apartado 6 |
| Aplicación antes de pintar | `resources/views/kapta/layouts/app.blade.php` (script en línea del `<head>`) |

**Cómo funciona.** El tema es el atributo `data-tema` del `<html>`: `claro` (valor con el que se
sirve la página) u `oscuro`, que solo se pone si el visitante lo eligió antes. La elección se guarda
en `localStorage` bajo la clave `ka-tema` —una por sitio, porque en este repositorio los tres
comparten origen— y el script del `<head>` la aplica antes de la primera pintada, así que al
recargar en oscuro no se ve un destello claro. En claro el sitio se sirve tal cual estaba: nadie que
no toque el interruptor recibe el tema oscuro del sistema operativo.

**Qué cambia exactamente.** Las bandas de impacto (`ka-surface-dark`: hero, bandas destacadas y
pie) ya son azul marino y **no cambian**. Lo que se invierte son las secciones informativas
(`ka-surface-light` y `ka-surface-mist`), que pasan a azul profundo redefiniendo sus mismas
variables. Ningún componente cambia de forma ni de tamaño: los colores salen de las variables que
cada superficie ya declaraba.

**Sin JavaScript** el interruptor no puede hacer nada, así que no se muestra: el sitio se ve
completo y en claro, igual que antes.

#### Convención del interruptor (Kapta, KiraFact y Pichanguero)

Los tres sitios del repositorio son **independientes**: cada uno se copia a su dominio con sus
vistas, su CSS y su JS. Por eso el interruptor **no se comparte** entre ellos, se implementa dentro
de cada sitio, y lo que sí se mantiene igual es su interfaz, para que pasar de uno a otro se sienta
como el mismo producto:

| Pieza | Convención |
| --- | --- |
| Estado | atributo `data-tema` del `<html>`, con los valores `claro` (por defecto) y `oscuro` |
| Icono | el del modo AL QUE SE VA: luna en claro, sol en oscuro |
| Etiqueta | «Cambiar a modo oscuro» en claro y «Cambiar a modo claro» en oscuro, en `aria-label` y `title` |
| Accesibilidad | `aria-pressed` en `true` solo cuando el modo oscuro está activo; sin JS el botón no se muestra |
| Almacenamiento | una clave por sitio, con el prefijo de sus clases: **`ka-tema`** aquí, `kf-tema` en KiraFact y `pg-tema` en Pichanguero |

La clave de `localStorage` es lo único que **no** puede repetirse: en este repositorio los tres
sitios comparten origen y, aunque en producción cada uno tenga su dominio, una clave propia evita
que un ajuste de un sitio arrastre al otro. El nombre del atributo y sus valores sí se mantienen,
porque son justo lo que comparten.

## Contenido configurable

Todo lo comercial vive en `config/kapta.php`:

- **`planes`**: los seis planes en tres niveles (esenciales, profesionales, avanzados) con importes
  mensuales y anuales, capacidad, prestaciones, ficha comparativa y enlace de solicitud.
  Esta es la **única tabla de precios pública de KAPTA**: la ficha que ARACODE publica en su sitio
  corporativo (`/kapta`) no tiene importes propios y enlaza aquí (ver
  `docs/SEO_ECOSISTEMA_PRODUCTOS.md`). No se inventaron ni se estimaron. Si la tabla aprobada
  cambia, se edita **solo** este archivo.
  Recuerda ejecutar `php artisan config:clear` (o `config:cache` en producción) después.
- **`niveles`** y **`comparativa`**: agrupaciones y filas de la tabla comparativa.
- **`contacto`** y **`mensajes`**: WhatsApp, correo, ubicación y textos de los enlaces.
- **`logo`**, **`logo_dark`**, **`logo_alto`**: logotipo oficial (ver abajo).
- **`campus_url`**: enlace al campus virtual; si está en `null` no se renderiza.
- **`legal`**: páginas de privacidad y términos; solo se enlazan las que existan.

## Recursos pendientes (no se han inventado)

1. **Logotipo oficial.** El sitio no incluye ninguna versión dibujada, recoloreada ni
   reinterpretada del logotipo. Mientras no exista el archivo, la cabecera y el pie muestran un
   **hueco reservado** (`components/brand.blade.php`) con el nombre escrito como texto normal.
   Para publicarlo: deja los archivos en `public/themes/kapta/images/` y define en
   `config/kapta.php` las claves `logo` (para fondos claros) y `logo_dark` (para fondos azul
   marino). La cabecera alterna las dos versiones sola al desplazar.
   El favicon actual es una **marca de posición** neutra, no el logotipo.
2. **Capturas reales del sistema.** La home y la página de planes muestran **composiciones
   ilustrativas** hechas en HTML y CSS (`components/mock.blade.php`). Llevan la etiqueta
   *Ilustrativo* dentro del marco y una nota debajo, y no contienen cifras ni datos de ninguna
   institución. Cuando existan capturas auténticas, se sustituye el bloque marcado en
   `pages/home.blade.php` (sección *Presentación visual del producto*).
3. **Imagen social (Open Graph).** Hoy apunta a la fotografía de la portada. Falta una versión
   de 1200×630 con la marca aplicada.
4. **Campus virtual.** No hay URL pública confirmada, así que el enlace no se muestra.
5. **Páginas legales.** El sitio todavía no tiene sus propias páginas de privacidad y términos.
6. **Formulario de contacto.** La página de contacto no incluye formulario a propósito: no hay
   un buzón o endpoint conectado y no se simula un envío de datos. Cuando exista el servicio real
   (controlador, ruta y validación), se añade ahí.

## Notas

- `js/app.js` es vanilla, sin bundler: cabecera que cambia al desplazar, menú móvil, aparición de
  bloques al hacer scroll, el conmutador mensual/anual de la página de planes y el interruptor de
  tema claro / oscuro (ver «Tema claro y oscuro»).
- Las animaciones se desactivan con `prefers-reduced-motion: reduce`.
- Sin JavaScript el contenido se ve igual: los bloques animados solo se ocultan cuando hay JS.
- Todas las bandas de encabezado son azul marino para que la cabecera transparente tenga siempre
  el mismo fondo detrás.

## Copiar el sitio a su propio dominio

1. Copia estas cinco zonas juntas:
   - `resources/views/kapta/`
   - `resources/css/kapta/`
   - `public/themes/kapta/`
   - `config/kapta.php`
   - y el archivo de rutas `routes/kapta.php`
2. En `routes/kapta.php` cambia `$prefix` a `''` para que el sitio quede en la raíz.
3. Sube el `bg-hero.webp` y `bg-page.webp` que usa el tema (ya viajan en `public/themes/kapta/images/`).
4. Vuelve a compilar el CSS si tocas estilos: `npm run build:kapta`.
