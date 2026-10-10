# Sitio web de KIRAFACT

Web comercial del producto **KIRAFACT** (facturación electrónica y gestión empresarial),
desarrollada por ARACODE SMART SOLUTIONS. Está hecha en **Blade puro**: sin Inertia, sin Vue,
sin controladores y sin `webpage-v2.css`. Se puede copiar completa a su propio dominio.

La web comercial es una cosa y el **sistema KIRAFACT** es otra: esta web presenta el producto y
enlaza al sistema cuando exista su URL. No reconstruye el sistema, no crea pantallas de login y
no pide credenciales.

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
| Acceso al sistema | `components/acceso.blade.php` | `#acceso` |
| Preguntas frecuentes | `components/preguntas.blade.php` | `#preguntas` |
| Cierre y contacto | `components/cta-final.blade.php` | — |

Piezas reutilizables: `components/navbar.blade.php`, `components/footer.blade.php`,
`components/marca.blade.php` (marca y logotipo), `components/icon.blade.php` (iconos lineales),
`components/btn-ingresar.blade.php` (botón de acceso al sistema), `components/mock.blade.php`
(composición del software), `components/encabezado.blade.php` (cabecera de páginas internas).

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

### 1. URL del sistema (acceso de clientes)

```env
KIRAFACT_LOGIN_URL=https://...
```

Mientras esté vacía, los botones «Ingresar al sistema» (cabecera, menú móvil, hero, sección de
acceso y cierre) se muestran como **no disponibles**, con una indicación discreta, y no enlazan a
ninguna parte. No hay ningún dominio inventado.

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

## Copiar el sitio a su propio dominio

1. Copia estas cuatro zonas juntas: `resources/views/kirafact/`, `resources/css/kirafact/`,
   `public/themes/kirafact/` y `routes/kirafact.php` — más `config/kirafact.php` y el script
   `build:kirafact` de `package.json`.
2. En `routes/kirafact.php` cambia `$prefix` a `''` para que el sitio quede en la raíz.
3. Define `KIRAFACT_LOGIN_URL` y `KIRAFACT_CANONICAL_URL` en el entorno.
4. Vuelve a compilar el CSS: `npm run build:kirafact`.

## Contenido pendiente de confirmación

Nada de esta lista se publica por aproximación; el sitio funciona sin ello:

- Logotipo oficial de KIRAFACT (versiones clara y oscura) y favicon derivado.
- Capturas reales del sistema.
- URL del sistema para el acceso de clientes.
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
  funciona sobre blanco, gris y azul marino sin duplicar estilos.
- **Sin recursos prestados**: no se usan fotos de stock ni capturas de otras plataformas. La única
  imagen del tema es la provisional para compartir en redes.
- **Mejora progresiva**: sin JavaScript el sitio se ve completo (los bloques que aparecen al
  desplazar solo se ocultan cuando hay JS), los acordeones son `<details>` nativos y los botones son
  enlaces reales o estados no disponibles, nunca decoración.
