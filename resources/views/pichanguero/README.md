# Sitio web de Pichanguero

Sitio independiente del producto **Pichanguero**, hecho en **Blade puro** (sin Inertia, sin Vue y sin
assets de ARACODE). Está pensado para copiarse a su propio dominio junto con todo su ecosistema.

## Qué contiene

| Zona | Contenido |
| --- | --- |
| `resources/views/pichanguero/` | `layouts/`, `components/` y `pages/` del sitio |
| `resources/css/pichanguero/` | Fuente de estilos (`pichanguero.css` + `tailwind.config.js` propio) |
| `public/themes/pichanguero/` | `css/` compilado, `js/app.js` y `images/` que sirve el navegador |
| `routes/pichanguero.php` | Rutas del sitio (Blade puro, sin controladores) |
| `public/downloads/pichanguero.apk` | App Android que ofrece la página de descargas (93 MB) |

## Rutas

| URL en este repositorio | Vista |
| --- | --- |
| `/site/pichanguero` | `pichanguero.pages.home` |
| `/site/pichanguero/descargas` | `pichanguero.pages.descargas` |
| `/site/pichanguero/contacto` | `pichanguero.pages.contacto` |

El prefijo `site/pichanguero` existe solo para convivir con el sitio ARACODE en este repositorio.

## Compilar el CSS

```bash
npm run build:pichanguero
```

Genera `public/themes/pichanguero/css/pichanguero.css` a partir de
`resources/css/pichanguero/pichanguero.css` y de su propia config de Tailwind.

## Copiar el sitio a su propio dominio

1. Copia estas cuatro zonas juntas:
   - `resources/views/pichanguero/`
   - `resources/css/pichanguero/`
   - `public/themes/pichanguero/`
   - y el archivo de rutas `routes/pichanguero.php`
2. Copia el APK a `public/downloads/pichanguero.apk` (no viaja en el tema por su tamaño).
3. En `routes/pichanguero.php` cambia `$prefix` a `''` para que el sitio quede en la raíz.
4. Ajusta el dominio en los enlaces absolutos del `README` y en la meta `canonical` (se genera sola).
5. Vuelve a compilar el CSS si tocas estilos: `npm run build:pichanguero`.

## Identidad visual

### Paleta

Los tokens viven en `resources/css/pichanguero/tailwind.config.js` y se publican como variables CSS
en `resources/css/pichanguero/pichanguero.css` (`--pg-*`), de modo que el diseño se puede reutilizar
fuera de Tailwind.

| Token | Valor | Uso |
| --- | --- | --- |
| `pg.navy` | `#071F36` | Fondos de impacto (portada, secciones destacadas, pie) |
| `pg.navy2` / `pg.navy3` | `#0A2A47` / `#0E3557` | Paneles y realces sobre azul marino |
| `pg.green` | `#36B82E` | Iconos y acentos que conectan con el logotipo |
| `pg.forest` | `#00834B` | Botones sólidos y acentos sobre fondo claro |
| `pg.lime` | `#B5FF27` | Llamadas a la acción y elementos seleccionados |
| `pg.light` | `#F3F7FA` | Fondo de las secciones informativas |
| `pg.ink` / `pg.slate` | `#0B2239` / `#55697D` | Texto principal y secundario sobre claro |
| `pg.muted` | `#9AB0C4` | Texto secundario sobre oscuro |
| `pg.line` / `pg.line-light` | `#1B4067` / `#DCE6EE` | Bordes sobre oscuro y sobre claro |

### Tipografía

- **Montserrat** (600/700/800) para titulares: `font-display` o la clase `.pg-display`.
- **Inter** (400–800) para interfaz, párrafos, tablas y datos: es la fuente base del `body`.

Ambas se cargan en una sola petición a Google Fonts desde `layouts/app.blade.php`.

### Superficies clara y oscura

Los componentes leen variables CSS, así que una misma tarjeta, botón o badge funciona igual sobre
los dos fondos. Basta con marcar el contenedor de la sección:

```html
<section class="pg-surface-dark">  <!-- azul marino: impacto -->
<section class="pg-surface-light"> <!-- blanco y gris azulado: información -->
```

Clases propias disponibles: `pg-container`, `pg-section`, `pg-card`, `pg-card-title`, `pg-icon-box`,
`pg-btn` (`-primary`, `-green`, `-ghost`, `-sm`, `-lg`), `pg-badge` (`-accent`), `pg-faq`,
`pg-step-number`, `pg-rail`, `pg-slash`, `pg-diagonal-edge`, `pg-reveal`, `pg-theme-switch` y las
que leen la superficie en la que están: `pg-heading` (titulares), `pg-body` (texto corriente) y
`pg-hairline` (borde/separador).

Conviene usar esas tres últimas en lugar de las clases de color fijas de Tailwind
(`text-pg-ink`, `text-pg-slate`, `border-pg-line-light`): dan el mismo color en claro y se adaptan
solas cuando la sección se invierte en oscuro (`text-white` y `text-pg-muted` son correctas dentro
de las secciones `pg-surface-dark`, que no cambian de tema).

### Tema claro y oscuro

La cabecera lleva un interruptor que cambia entre **modo claro y modo oscuro**. Muestra el icono
del modo al que se va: con el sitio en claro se ve una **luna** (pasa a oscuro) y con el sitio en
oscuro se ve un **sol** (vuelve a claro).

| Pieza | Dónde vive |
| --- | --- |
| Interruptor | `resources/views/pichanguero/components/theme-switch.blade.php` (dos instancias: cabecera y menú móvil) |
| Colores del tema oscuro | `resources/css/pichanguero/pichanguero.css`, sección «Tema claro y oscuro» |
| Cambio de tema y memoria | `public/themes/pichanguero/js/app.js`, apartado 5 |
| Aplicación antes de pintar | `resources/views/pichanguero/layouts/app.blade.php` (script en línea del `<head>`) |

**Cómo funciona.** El tema es el atributo `data-tema` del `<html>`: `claro` (valor con el que se
sirve la página) u `oscuro`, que solo se pone si el visitante lo eligió antes. La elección se guarda
en `localStorage` bajo la clave `pg-tema` (distinta de la de KiraFact, porque en este repositorio
los dos sitios comparten origen) y el script del `<head>` la aplica antes de la primera pintada, así
que al recargar en oscuro no se ve un destello claro. En claro el sitio se sirve tal cual estaba:
nadie que no toque el interruptor recibe el tema oscuro del sistema operativo.

**Qué cambia exactamente.** Las secciones de impacto (`pg-surface-dark`: portada, pasos, descarga y
pie) ya son azul marino y **no cambian**. Lo que se invierte son las secciones informativas
(`pg-surface-light`), que pasan a azul marino medio (`--pg-navy-2`) redefiniendo sus mismas
variables: por eso el ritmo claro/oscuro del diseño se mantiene en los dos temas, solo que en
oscuro las dos superficies son azules y las de impacto siguen siendo las más profundas. Ningún
componente cambia de forma ni de tamaño.

**Sin JavaScript** el interruptor no puede hacer nada, así que no se muestra: el sitio se ve
completo y en claro, igual que antes.

#### Convención del interruptor (Pichanguero, KiraFact y Kapta)

Los tres sitios del repositorio son **independientes**: cada uno se copia a su dominio con sus
vistas, su CSS y su JS. Por eso el interruptor **no se comparte** entre ellos, se implementa dentro
de cada sitio. Lo que sí se mantiene igual es su interfaz, para que pasar de uno a otro se sienta
como el mismo producto:

| Pieza | Convención |
| --- | --- |
| Estado | atributo `data-tema` del `<html>`, con los valores `claro` (por defecto) y `oscuro` |
| Icono | el del modo AL QUE SE VA: luna en claro, sol en oscuro |
| Etiqueta | «Cambiar a modo oscuro» en claro y «Cambiar a modo claro» en oscuro, en `aria-label` y `title` |
| Accesibilidad | `aria-pressed` en `true` solo cuando el modo oscuro está activo; sin JS el botón no se muestra |
| Almacenamiento | una clave por sitio, con el prefijo de sus clases: **`pg-tema`** aquí, `kf-tema` en KiraFact y `ka-tema` reservada en Kapta |

La clave de `localStorage` es lo único que **no** puede repetirse: en este repositorio los tres
sitios comparten origen y, aunque en producción cada uno tenga su dominio, una clave propia evita
que un ajuste de un sitio arrastre al otro. El nombre del atributo y sus valores sí se mantienen,
porque son justo lo que comparten.

### Recursos gráficos

Todo lo que sirve el navegador está en `public/themes/pichanguero/images/`:

| Archivo | Origen |
| --- | --- |
| `logo.svg`, `favicon.svg` | Logotipo del proyecto (se usa tal cual, sin redibujar ni recolorear) |
| `hero-bg.jpg`, `bg-page.jpg`, `og-image.jpg` | Fotografías de fútbol que ya usaba el sitio |
| `app-player.webp`, `app-keeper.webp` | Ilustraciones propias de la app, extraídas del APK que se distribuye y optimizadas a WebP |

## Notas

- La identidad visual (colores, tipografía, componentes) es propia de Pichanguero: no depende del
  `tailwind.config.js` de la raíz ni de `webpage-v2.css`.
- `js/app.js` es vanilla, sin bundler: se sirve tal cual desde el tema.
- El contenido de las páginas es la base editable del producto (beneficios, pasos, FAQ y descargas).
- Dos detalles de Tailwind que conviene respetar al añadir clases: las opacidades deben salir de su
  escala (`/70`, `/90`, `/95`…) y las claves de color van en kebab-case (`line-light`, no `lineLight`),
  porque Tailwind usa la clave tal cual para el nombre de la clase.
- Las ilustraciones se muestran como lo que son (recursos de la app). No hay capturas de interfaz
  generadas presentadas como si fueran el sistema real.
- El tema claro/oscuro no se apoya en la opción `darkMode: 'class'` de Tailwind (que hoy no se usa):
  el interruptor solo cambia el atributo `data-tema` del `<html>` y el CSS reacciona a él. Si algún
  día se quieren variantes `dark:` de utilidades, la config ya está lista para la clase `dark`, pero
  entonces habría que añadir esa clase al `<html>` junto con el atributo.
