# Fase 1 — Auditoría y plan del Banco de Plantillas Web

Fecha: 10 de octubre de 2026. Estado: **auditoría terminada, sin cambios estructurales
aplicados**. Este documento es el entregable de la Fase 1 y el plan a aprobar antes de la
Fase 3.

## 1. Hallazgos

### 1.1 Framework y stack

| Pieza | Lo que hay hoy |
| --- | --- |
| Framework | Laravel. El sitio corre con **11.23.5** instalado, pero `composer.lock` fija **v12.69.2** y `composer.json` pide `^12.0`: **vendor está desincronizado respecto al lock**. No se toca (un `composer install` arrastraría el framework y otros paquetes). Toda la Fase 3 se escribe compatible con 11 y 12. |
| Módulos | `nwidart/laravel-modules` 10.0.6, 19 módulos en `Modules/` (`CMS`, `Blog`, `Sales`, `Academic`, `Socialevents`, `Integrationhub`…). Cada módulo trae sus `Routes/web.php` y se cargan solos, con el grupo `web`. |
| Vistas | Blade. Sitio corporativo en `resources/views/aracode/**`; cada producto tiene su árbol propio (`resources/views/{kapta,kirafact,pichanguero}/**`). |
| CSS | `public/css/website.css` (build de Tailwind, **7,6 MB, versionado**) + `public/css/webpage-v2.css` (mantenido a mano, **duplicado** en `resources/css/webpage-v2.css`). Los sitios de producto tienen su propio Tailwind: `resources/css/{kapta,kirafact,pichanguero}/tailwind.config.js` + `npm run build:kapta|build:kirafact|build:pichanguero`. |
| JS | **No hay bundle ni framework**: el layout resuelve con un bloque de JS propio embebido el tema claro/oscuro (`localStorage['aracode-theme']`), el menú móvil, el scroll reveal y los contadores. Algunas páginas sueltas cargan librerías por CDN cuando las usan (`/contacto`: reCAPTCHA + SweetAlert2; `/blog`: SweetAlert2); `/` y `/soluciones` no cargan **ningún** `<script src>`. El layout declara `x-data` / `$store.theme.init()` en `<html>` pero **Alpine no está cargado**: esas directivas están inertes (el tema funciona por el JS embebido, no por Alpine). Consecuencia para el plan: los filtros y el buscador del catálogo **no pueden depender de Alpine**; van con formulario GET resuelto en el servidor (y, si se quiere, un realce pequeño con JS propio, sin añadir dependencias). |
| `public/build` | No existe (los assets Vite del panel no se compilan en esta copia). El sitio corporativo no usa Vite: apunta directo a `public/css/*.css` y `public/themes/**`. |

### 1.2 Rutas

`app/Providers/RouteServiceProvider.php` monta, todos con middleware `web`:

- `routes/api.php` (prefijo `api`), `routes/aracode.php` (**sin prefijo**: es el sitio
  corporativo y conserva las URLs originales), `routes/web.php` (panel), y los sitios de
  producto `routes/{pichanguero,kapta,kirafact}.php`, cada uno declarando su prefijo en una
  línea (`$prefix = 'site/pichanguero';`).

Total registrado: **1383 rutas**. Verificado con `php artisan route:list --json`:

- **No existe** ninguna ruta `/plantillas`, `/plantillas/{slug}` ni `/demo/{slug}`, ni nada
  parecido en `config/*.php` o en `Modules/*/Routes/`.
- **No hay catch-all**: el único `fallback()` es el comodín del blog (`/blog/{url}`), acotado a
  `/blog`.
- **Cero rutas con raíz paramétrica** (ninguna empieza por `{…}`) y ningún `prefix('{…}')`: nada
  puede capturar `/plantillas` ni `/demo/{slug}` antes que la sección nueva.
- Tampoco hay `Route::any` ni `Route::match` sueltos que interfieran.
- Por tanto **las tres URLs son viables tal cual**, sin colisiones.

### 1.3 Cómo se publican hoy las páginas del corporativo

Ruta → método de `WebPageController` → `view('aracode.pages.*')`, con el layout
`resources/views/aracode/layouts/webpage.blade.php`. Ese layout ya trae lo que hace falta para
la nueva sección sin tocarlo: `<title>`/description por `@section`, `<link rel="canonical">`
(sin query string), JSON-LD de `Organization`, los stacks `@stack('head')` y
`@stack('scripts')`, y el navbar/pie compartidos como componentes (`x-v2.navbar`, `x-v2.footer`).

Nota: `WebPageController` consulta el CMS (`cms_sections`/`cms_items`) **en su constructor**,
así que cualquier página del corporativo necesita esas tablas para renderizar (ver 1.6).

### 1.4 Recursos estáticos e imágenes

- Convención por sitio: `public/themes/<sitio>/{css,fonts,images,js,video}/`
  (hay `webpage`, `kapta`, `kirafact`, `pichanguero`, `torneo`, `blog`, `invoice`…).
- Se referencian con `asset('themes/...')`, nunca con rutas absolutas.
- `.gitignore` excluye `/public/build`, `/public/hot`, `/public/storage`,
  `/public/EditorAracode`, `public/robots.txt` y `public/llms.txt` (estos dos se despliegan a mano).

### 1.5 Servidor: cómo pueden servirse las demostraciones

Entorno local real (no solo el `artisan serve` de desarrollo), comprobado contra
`http://base-aracode.test`:

```
/                                   200
/css/webpage-v2.css                 200
/css/                               403   (sin listado de directorios)
/img/                               403
/themes/webpage/images/misc/s1.jpg  200
```

Configuración de Apache (Laragon) leída en `etc/apache2/sites-enabled/auto.base-aracode.test.conf`
y en el `httpd.conf`:

- `DocumentRoot "C:/laragon/www/base-aracode/public"`, `AllowOverride All`.
- `DirectoryIndex index.html index.php` → **una carpeta con `index.html` se sirve sola**.
- `Options -Indexes` (vía `public/.htaccess`) y `mod_rewrite` + `mod_headers` cargados.
- `public/.htaccess` solo manda a `index.php` lo que **no** es archivo ni carpeta reales: un
  archivo estático nuevo se sirve directo, sin pasar por Laravel.
- `ServerAlias *.base-aracode.test` (comodín): hoy todos los subdominios apuntan al mismo
  `DocumentRoot`; un aislamiento por subdominio exigiría un `VirtualHost` propio por demo.
- Producción es **nginx sobre Linux** (lo dice `docs/SECURITY_PHASE1.md`), donde el equivalente
  es `try_files $uri $uri/ /index.php?$query_string;` para el sitio y un `server` block por
  subdominio si algún día se separan.

**Conclusión técnica:** cada demostración puede ser un **proyecto estático independiente en su
propia carpeta** (`public/demos/<slug>/`, con su `index.html`, su CSS, su JS y su navegación),
servido por el servidor web **sin pasar por Laravel ni compartir una sola hoja de estilos con el
sitio corporativo**. Eso es aislamiento real, no simulado: no hay Blade, ni componentes, ni CSS
corporativo en juego. La separación por subdominio es la evolución natural cuando una demo
necesite su propio dominio, y queda documentada como paso pendiente (DNS + `VirtualHost` /
`server` block).

### 1.6 Pruebas y comandos disponibles

- `composer test` → `vendor/bin/pest` con `pdo_sqlite`. Hoy: **246 pruebas Unit en verde**.
- La suite **Feature no arranca** en sqlite: migraciones de otros módulos usan SQL solo de MySQL
  (`ALTER … MODIFY` en `Modules/Socialevents/…`, `information_schema` en `Modules/Academic/…`) y
  abortan la creación del esquema → 23 fallos idénticos antes y después de cualquier cambio.
  Está documentado en `docs/SEO_ECOSISTEMA_PRODUCTOS.md`.
- Compilación de CSS por sitio: `npm run build:kapta` / `build:kirafact` / `build:pichanguero`.
  `npm run build` es el build de Vite del panel (`public/build`, no usado por el corporativo).
- `public/css/website.css` es regenerable con Tailwind
  (`resources/css/website.css` es la entrada con las tres directivas), pero **es un archivo
  versionado de 7,6 MB**: reconstruirlo produciría un diff enorme. Por eso la sección nueva
  **no depende de él** (ver 2.2).

### 1.7 Riesgo de efectos secundarios

| Riesgo | Mitigación del plan |
| --- | --- |
| `website.css` no contiene las utilidades Tailwind nuevas que se escriban | La sección lleva su **propio** Tailwind (config + entrada + salida en `public/themes/plantillas/css/`), como los sitios de producto |
| Tocar `routes/aracode.php` (147 líneas, rutas del corporativo) | La sección va en **`routes/plantillas.php`**, montada como los sitios de producto: una línea en `RouteServiceProvider` |
| `WebPageController` es un archivo de 2300 líneas compartido | El catálogo usa su **propio controlador**; no se añaden métodos ahí |
| El navbar y el pie son compartidos | Se **reutilizan** sin editarlos. El enlace «Plantillas» en el menú es el único punto que necesita permiso expreso |
| Nombres de clases CSS chocando con las del corporativo | Prefijo propio `plt-` y un contenedor `.plt-app`; nada de retocar `webpage-v2.css` |
| Demos que pudieran ejecutar PHP (contenido de terceros dentro de `public/`) | `public/demos/.htaccess` que desactiva el motor PHP en esa rama |
| `vendor` desincronizado con `composer.lock` | No se ejecuta `composer install/update` |

## 2. Plan de implementación

### 2.1 Archivos nuevos (todo aislado)

```
routes/plantillas.php                                  rutas de la sección (una línea para montarla)
app/Http/Controllers/PlantillasController.php          index / ficha / demo
app/Support/CatalogoPlantillas.php                     único lector del catálogo (filtros, búsqueda)
config/plantillas.php                                  datos: categorías + plantillas (fuente única)
resources/views/plantillas/{catalogo,ficha}.blade.php  páginas, `@extends('aracode.layouts.webpage')`
resources/views/plantillas/components/*.blade.php      tarjeta, filtros, vacío, migas
resources/css/plantillas/plantillas.css                entrada Tailwind de la sección
resources/css/plantillas/tailwind.config.js            contenido acotado a resources/views/plantillas/**
public/themes/plantillas/css/plantillas.css            salida compilada (npm run build:plantillas)
public/themes/plantillas/images/…                      capturas de las plantillas
public/demos/.htaccess                                 sirve demos y bloquea PHP dentro de ellas
public/demos/<slug>/…                                  cada demo: proyecto estático independiente
docs/BANCO_PLANTILLAS.md                               convención de demos y día del subdominio
tests/Unit/CatalogoPlantillasTest.php                  contrato del catálogo (patrón ya usado en el repo)
```

### 2.2 Archivos existentes que se tocan (mínimos y justificados)

```
app/Providers/RouteServiceProvider.php   +1 línea: Route::middleware('web')->group(base_path('routes/plantillas.php'));
package.json                             +1 script: build:plantillas (igual que build:kapta)
public/sitemap.xml                       +1 URL: /plantillas (las fichas entran cuando existan)
```

Nada más. **No** se modifica: `routes/aracode.php`, `WebPageController`, el layout corporativo,
`resources/css/webpage-v2.css`, `public/css/website.css`, el logotipo, ni las vistas de home,
soluciones, productos, empresa, blog, contacto o legales.

Pendiente de permiso: enlace «Plantillas» en `resources/views/aracode/components/v2/navbar.blade.php`
y `…/v2/footer.blade.php` (2 líneas por archivo, sin rediseño).

### 2.3 Rutas

| URL | Qué hace |
| --- | --- |
| `GET /plantillas` | Página maestra: categorías, buscador y filtros (GET: `?categoria=…&q=…`), rejilla de tarjetas |
| `GET /plantillas/{slug}` | Ficha: capturas, qué incluye, tecnologías, categorías, botón «Ver demostración» |
| `GET /demo/{slug}` | Abre la demo: **redirección 302** a `/demos/{slug}/` (302 y no 301 porque el destino puede moverse a un subdominio) |

Los filtros funcionan sin JavaScript (formulario GET + resolución en el servidor) y la canónica
del layout ya ignora la query string, así que las variantes filtradas no se indexan por separado.

### 2.4 Demostraciones independientes

- Cada demo es **su propia carpeta** con su `index.html`, sus estilos, sus scripts y su
  navegación interna. No comparte Blade, ni CSS, ni componentes con el catálogo.
- `public/demos/.htaccess` desactiva la ejecución de PHP en esa rama (las demos son contenido de
  terceros: solo estáticos).
- El catálogo guarda el `slug` y resuelve la URL de la demo en `CatalogoPlantillas` (igual que
  `SitiosProducto` resuelve los sitios de producto): el día que una demo se mude a
  `demo-x.aracodeperu.com`, se cambia **una línea** en `config/plantillas.php`.
- Paso pendiente de infraestructura (documentado, no ejecutado): DNS del subdominio y su
  `VirtualHost`/`server` block, más HTTPS.

### 2.5 Datos

`config/plantillas.php` es la fuente única (categorías y plantillas: `slug`, `nombre`,
`categoria`, `etiquetas`, `descripcion`, `imagen`, `demo`, `destacada`, `estado`).
`CatalogoPlantillas` es el único que lo lee, y hace el filtrado y la búsqueda en memoria
(decenas de plantillas: sobra). Es el patrón que el repo ya usa y prueba
(`config/productos.php` + `App\Support\SitiosProducto` + `tests/Unit/SitiosProductoTest.php`).
Ventaja: sin migraciones, sin panel nuevo, todo versionado y sin dependencias añadidas.
Alternativa si se prefiere editar desde el panel: módulo `Modules/Plantillas` con migración,
modelo y CRUD con los patrones del panel (`auth`, `verified`, `permission:*`, datatables). Es
bastante más trabajo y toca el menú del panel; el acceso queda igual gracias a
`CatalogoPlantillas`.

### 2.6 Fase 5 — verificaciones previstas

1. `php artisan route:list` (`/plantillas`, `/plantillas/{slug}`, `/demo/{slug}`) y `view:clear`.
2. `curl` a las tres URLs: 200/302 y destino 200; `/demo/{slug}` termina en la demo correcta.
3. Navegación y filtros: por categoría, por búsqueda, combinados, y caso «sin resultados».
4. Rutas directas y «atrás» del navegador (historial con query params).
5. Móvil y escritorio: comprobación de la rejilla y de la ficha en dos anchos.
6. Regresión del sitio: `/`, `/soluciones`, `/kapta`, `/kirafact`, `/pichanguero`, `/empresa`,
   `/contacto`, `/blog` siguen en 200 y con su HTML intacto (comparación antes/después).
7. Aislamiento: el CSS/JS de la sección no aparece en las páginas corporativas y viceversa.
8. `composer test` (suite Unit) y `npm run build:plantillas`.
9. Pendientes de infraestructura por escrito: subdominio/DNS/HTTPS si se separan demos,
   `robots.txt`/`sitemap.xml` en el servidor (`public/robots.txt` no está en git) y si las demos
   deben ir con `noindex`.

## 3. Decisiones que necesito antes de la Fase 3

1. **Datos del catálogo**: `config/plantillas.php` + `CatalogoPlantillas` (recomendado) o módulo
   con base de datos y CRUD en el panel.
2. **Enlace en la navegación**: añadir «Plantillas» al navbar y al pie, o dejar la sección
   accesible solo por URL.
3. **Demo de arranque**: construir el mecanismo con una demo de ejemplo independiente (para
   probar el circuito completo) o esperar a que estén las plantillas reales.
4. **Indexación de las demos**: `noindex` en las demos (recomendado: la página que posiciona es
   la ficha) o dejarlas indexables.

Las cuatro se resolvieron en la opción recomendada (config + `CatalogoPlantillas`, enlaces en
navbar y pie, demo de arranque independiente y `noindex` en las demos).

## 4. Fase 3 — qué se implementó y qué se verificó

### 4.1 Archivos

Nuevos:

```
config/plantillas.php
app/Support/CatalogoPlantillas.php
app/Http/Controllers/PlantillasController.php
routes/plantillas.php
resources/views/plantillas/catalogo.blade.php
resources/views/plantillas/ficha.blade.php
resources/views/components/plantillas/tarjeta.blade.php
resources/css/plantillas/plantillas.css                (CSS propio de la sección, sin Tailwind)
public/themes/plantillas/css/plantillas.css        (compilado con npm run build:plantillas)
public/demos/.htaccess
public/demos/clinica-dental/{index.html,css/estilos.css,js/app.js}
tests/Unit/CatalogoPlantillasTest.php
docs/BANCO_PLANTILLAS.md
```

Modificados (mínimos, sin rediseño):

```
app/Providers/RouteServiceProvider.php                  carga routes/plantillas.php
resources/views/aracode/components/v2/navbar.blade.php  +1 enlace «Plantillas» (escritorio y móvil)
resources/views/aracode/components/v2/footer.blade.php  +1 enlace en la columna de soluciones
package.json                                            +1 script build:plantillas
public/sitemap.xml                                      +2 URLs (/plantillas y la ficha con demo)
```

El enlace de navegación hace `request()->routeIs('plantillas.*')`, así que no cambia nada del
estado activo de las páginas existentes. La sección reutiliza el layout, el navbar y el pie
corporativos: no se duplica ninguno.

### 4.2 Defecto encontrado y corregido

`CatalogoPlantillas::categorias()` arma los totales con `todas()`, que rellena cada plantilla
llamando a `nombreCategoria()`; cuando ese método leía el nombre desde el propio `categorias()`,
la cadena se llamaba a sí misma sin fin: la petición agotaba los 512 MB de memoria y respondía
**500 con el cuerpo vacío, sin entrada en `storage/logs/laravel.log`** (el error real, «Allowed
memory size … exhausted at Container.php», aparecía recién al pasarlo por el kernel HTTP).

Corrección: `nombreCategoria()` lee `config('plantillas.categorias.<slug>.nombre')` directo. No se
toca la interfaz pública de la clase, así que el controlador y las vistas no cambian.

Segundo defecto, detectado al comprobar el enlace de la demo con `curl`: `urlDemo()` resolvía la
carpeta de la config contra la raíz (`/clinica-dental/`) en vez de contra `public/demos/`
(`/demos/clinica-dental/`), porque no aplicaba el prefijo que sí usa `tieneDemo()`. Corregido en
el mismo método.

Ambos quedan cubiertos por `tests/Unit/CatalogoPlantillasTest.php`: la primera prueba de ese
archivo (nombres de categoría resueltos sin recursión) falla si vuelve la cadena infinita, y la
tercera exige que la URL de la demo termine en `/demos/clinica-dental/`.

### 4.2.1 Tercer defecto: el menú de escritorio desaparecía en /plantillas

La primera versión de `resources/css/plantillas/plantillas.css` terminaba en `@tailwind utilities;`
y se compilaba con su propia config de Tailwind. Como las páginas de la sección se sirven con el
layout corporativo, la hoja se cargaba **después** de `public/css/website.css` (que ya trae el
preflight y todas las utilidades del sitio, porque el config de la raíz escanea
`resources/views/**`).

Duplicar utilidades no es inocuo: la cascada se decide por orden de aparición, no por archivo, y
la especificidad era la misma. El build de la sección emitía `.hidden{display:none}` —Tailwind
lee también `type="hidden"`, presente en el formulario del catálogo como candidato— y esa regla,
al ir después, le ganaba al `.lg\:flex` de `website.css`: el navbar mostraba la hamburguesa en
escritorio en `/plantillas` y sus fichas, con el menú completo intacto en el resto del sitio.

Corrección: la hoja de la sección es CSS propio y no emite nada de Tailwind (se eliminó
`@tailwind utilities;` y `resources/css/plantillas/tailwind.config.js`); las utilidades
(`max-w-7xl`, `px-4`, `text-ara-slate-700`…) las sirve el CSS corporativo, que ya las tiene todas
—se comprobó clase por clase que las 85 que usa la sección existen allí—. `npm run
build:plantillas` queda solo como paso de minificado.

Regla para el futuro: **esta hoja no declara ninguna clase sin prefijo `plt-`**. Lo vigila la
prueba `compila su hoja de estilos sin declarar clases del sitio`, que falla en cuanto el archivo
declara un selector ajeno (se comprobó reinyectando `.hidden{display:none}` a mano: la prueba
falla, y vuelve a pasar al restaurar el archivo).

### 4.3 Verificación ejecutada

| Comprobación | Resultado |
| --- | --- |
| `/plantillas` | 200, 6 tarjetas, canónica sin query string |
| `/plantillas?categoria=salud` y `?q=dental` | 200, subconjunto correcto |
| `/plantillas?categoria=nada&q=` | 200 con el catálogo completo (no página vacía) |
| `/plantillas/clinica-dental` | 200, JSON-LD `CreativeWork` + `BreadcrumbList` |
| `/plantillas/no-existe` | 404 |
| `/demo/clinica-dental` | 302 → `http://127.0.0.1:8123/demos/clinica-dental/` |
| `/demo/restaurante-carta` (sin carpeta) | 404 en vez de enlace roto |
| `/demos/clinica-dental/{,css/estilos.css,js/app.js}` | 200 |
| HTML de la demo | `<meta name="robots" content="noindex, follow">` |
| CSS de la sección | `themes/plantillas/css/plantillas.css` solo en las páginas de la sección |
| Suite Unit | 257 pruebas, 1348 aserciones, en verde (incluye las 11 de la sección) |
| Navbar a 1440 px | menú de escritorio visible (Inicio, Soluciones, Empresa, Plantillas, Blog, Contacto + CTA) como en el resto del sitio |
| Navbar a 390 px | hamburguesa visible y menú de escritorio oculto, sin desbordamiento horizontal |
| Rejilla y ficha | 3 columnas a 1440 px, 1 a 390 px; ficha a 2 columnas con captura 16/10 |
| Suite Feature | 32 fallos, **todos** `QueryException` de migraciones que solo existen en MySQL (`ALTER … MODIFY`, `information_schema`), incluido `ExampleTest`, que no toca la sección. Limitación de entorno ya documentada, no de este cambio |
| `php -l` de los 8 archivos PHP tocados | sin errores |
| `public/sitemap.xml` | XML válido, 23 URLs (las de antes + 2) |
| Regresión del corporativo | `/`, `/soluciones`, `/empresa`, `/contacto`, `/blog`, `/precios`, `/kirafact`, `/kapta`, `/pichanguero` en 200 |
| Aislamiento de estilos | el CSS de la sección se carga **solo** en `/plantillas` y `/plantillas/{slug}`; ninguna página corporativa lo incluye |
| Diff del sitio existente | 20 líneas añadidas y **0 borradas** (`RouteServiceProvider`, navbar, pie, `package.json`, `sitemap.xml`) |

No se ejecutó ninguna prueba contra producción, ni se publicó nada, ni se tocó DNS, HTTPS o
`robots.txt`: eso sigue pendiente y requiere autorización expresa.

### 4.4 Pendientes que no dependen de código

1. **Despliegue**: subir `public/themes/plantillas/`, `public/demos/` y `public/sitemap.xml` junto
   con el resto, y ejecutar `php artisan config:cache` en el servidor (el catálogo se lee de
   config).
2. **`robots.txt` del servidor**: `public/robots.txt` no está en git. Si existe, conviene que
   `/demos/` no se rastree; hoy la protección real es el `noindex` de cada demo.
3. **Capturas reales**: cuando estén, escribirlas en `config/plantillas.php` (`imagen`) y
   comprobar que se ve la franja 16/10.
4. **Sitemap**: cada ficha entra cuando su plantilla tenga demo publicada (regla en
   [BANCO_PLANTILLAS.md](BANCO_PLANTILLAS.md)). Hoy solo `/plantillas` y
   `/plantillas/clinica-dental` están empujadas.
5. **Subdominio por demo**: si algún día se separan, ver el procedimiento en
   [BANCO_PLANTILLAS.md](BANCO_PLANTILLAS.md); requiere DNS, `VirtualHost` y HTTPS.
6. **Clasificación landing/sitio**: la de las seis plantillas iniciales es una propuesta hecha a
   partir de su estructura (una página frente a varias). Ventas debería confirmarla, porque de
   ella depende el plan que se ofrece: se cambia con una palabra (`'tipo' => 'landing'` o
   `'sitio'`) en cada plantilla de `config/plantillas.php`.

### 4.5 Formato a la vista: landing o sitio web

El formato es la primera pregunta de una venta —una landing y un sitio web son planes
distintos—, así que el catálogo tenía que responderlo sin abrir la ficha.

- `config/plantillas.php` gana un bloque `tipos` (`landing`, `sitio`) con `nombre`, `hashtag` y
  `descripcion`, y cada plantilla declara su `tipo`. Un tipo nuevo (por ejemplo `tienda`) se agrega
  ahí sin tocar código.
- `CatalogoPlantillas::completar()` resuelve `tipo`, `tipo_nombre`, `tipo_hashtag` y
  `tipo_descripcion`, y `tipos()` devuelve los formatos con su total. El nombre y el hashtag se leen
  de la config con `datoTipo()`, directo, para no repetir la recursión que tuvo
  `nombreCategoria()` con `categorias()`.
- La **tarjeta** pinta el hashtag (`#Landing`, `#SitioWeb`) en un chip oscuro justo encima del
  nombre, con la explicación del formato en el `title` —el único texto de la tarjeta que va antes
  del nombre, porque es lo que el visitante compara con su plan—. En la **ficha** aparece en la
  portada junto a la categoría, en la fila «Formato» del panel y en una nota que explica qué
  significa ese formato.
- El formato entra en el texto que busca el buscador, así que escribir `landing` (o `#sitio web`)
  en el campo del catálogo lista exactamente las de ese formato: es la búsqueda de quien ya sabe
  qué plan quiere.

Verificación: `tipos()` da 3 landings y 3 sitios que suman las 6 plantillas publicadas; el catálogo
sirve 6 chips `plt-tipo` (uno por tarjeta, comprobado también en el navegador y en el HTML de la
ficha); `?q=landing` devuelve solo las landings y `?q=#SitioWeb` solo los sitios. Las tres pruebas
nuevas fallan si una plantilla se queda sin formato o si el buscador deja de reconocerlo.

### 4.6 La demo de Clínica Dental como plantilla de referencia

Pedido: que la primera landing sirviera de ejemplo para las siguientes. Decisión de alcance: el
estándar se aplica **a todas las plantillas**, y queda escrito como lista obligatoria de 9 puntos en
[BANCO_PLANTILLAS.md](BANCO_PLANTILLAS.md) (fotografía real, WhatsApp y ubicación, prueba social,
precios, redes sociales, diseño con movimiento, honestidad de la demo, rendimiento y comprobación
final).

Qué se rehízo en `public/demos/clinica-dental/` (proyecto independiente, sin tocar el resto del
banco):

- **Fotografía real.** Tres fotos locales en WebP (portada 16/11, atención vertical 4/5, detalle
  16/10), reescaladas a un máximo de 1600 px de ancho. Vienen de Wikimedia Commons vía Openverse,
  filtradas por licencias sin obligaciones (CC0 / dominio público); su procedencia está en
  `CREDITOS.md` junto con la lista de reemplazos al publicar un sitio real.
- **WhatsApp y ubicación.** Botón flotante en toda la página, seis enlaces `wa.me` con mensaje
  pre-escrito, dirección y horarios, y un **plano propio en SVG** más un botón a Google Maps: la
  demo no carga scripts de mapas de terceros.
- **Prueba social y precios.** Cuatro cifras animadas en la portada, tres testimonios con valoración
  y avatares de iniciales (no se usan caras de terceros) y tres tarjetas de tarifas con nota de que
  son referenciales.
- **Redes sociales.** Instagram, Facebook, TikTok y WhatsApp con `aria-label` y `rel="noopener"`,
  en la barra superior y en el pie. Apuntan a la plataforma porque son de demostración.
- **Diseño y movimiento.** Aparición al hacer scroll, contadores, cabecera con sombra al bajar, hover
  en tarjetas y botones, `:focus-visible` y soporte de `prefers-reduced-motion`. El JavaScript de
  aparición solo existe si hay JS: sin él la página se ve completa.
- **Honestidad.** El número de WhatsApp es uno que no existe, todos los datos están marcados como de
  ejemplo y el formulario, al enviarlo, avisa de que no manda datos en vez de simular una cita
  reservada.

Verificado: 200 en la carpeta y en cada archivo (CSS, JS, tres WebP y el SVG) con su tipo MIME
correcto; sin recursos de terceros cargados (solo enlaces salientes); menú móvil que abre y cierra
(`aria-expanded` correcto) sin desbordamiento a 390 px; validación del formulario comprobada en el
navegador (envío vacío → 5 errores visibles y ningún envío; envío completo → aviso de demostración);
revisión visual a 1400 px de portada, «la clínica», opiniones, precios, visita, preguntas y pie; y la
suite Unit sigue en verde (254 pruebas antes de este cambio; la sección no depende de estos archivos).

### 4.7 La demo de Academia de Cursos: la primera de formato sitio web

Pedido: la siguiente plantilla del banco después de la clínica. Elegida **Academia de Cursos**
(categoría educación, `tipo: sitio`) porque es la única de las propuestas con catálogo, ficha de
producto y matrícula en línea: obliga a resolver el formato que faltaba —varias páginas enlazadas—
en lugar de repetir una landing de una sola página.

`public/demos/academia-cursos/` es un proyecto independiente de **cuatro páginas** (`index.html`,
`cursos.html`, `curso-desarrollo-web.html` y `matricula.html`) con **un solo** `css/estilos.css` y
**un solo** `js/app.js` compartidos, todos con rutas relativas. Cinco fotos locales en WebP (CC0),
plano propio en SVG, botón flotante de WhatsApp, precios, testimonios, docentes y cifras animadas:
cumple la lista obligatoria de 9 puntos de [BANCO_PLANTILLAS.md](BANCO_PLANTILLAS.md).

Lo que aporta al banco, además de la plantilla:

- **Una demo que se navega.** Menú con estado activo, migajas en las páginas interiores, enlaces
  cruzados entre inicio, catálogo, ficha y matrícula, y un tercer nivel de profundidad
  (inicio → listado → ficha) que las landings no tienen.
- **Catálogo con filtros** por área, modalidad y búsqueda de texto, con el estado en la URL
  (`cursos.html?area=tecnologia`), de modo que un enlace desde otra página llega ya filtrado. El
  filtrado es del navegador; sin JavaScript el listado se lee completo y un aviso lo dice.
- **Formulario de matrícula con validación propia** (nombre, correo, celular de 9 dígitos, curso,
  modalidad y autorización) que avisa campo por campo y, al enviarse bien, declara que es una
  demostración y no manda datos. El curso elegido llega preseleccionado desde la ficha
  (`matricula.html?curso=desarrollo-web`).

### 4.8 La demo de Restaurante y carta digital: la categoría de gastronomía

Pedido: la siguiente plantilla del banco. Elegida **Restaurante y carta digital** (categoría
gastronomía, `tipo: landing`), que hasta ahora era la única categoría del catálogo sin ninguna
temática cubierta y además el caso más frecuente del rubro: carta con precios y reserva por
WhatsApp, todo pensado para el celular.

`public/demos/restaurante-carta/` es un proyecto independiente de una sola página (`index.html`) con
su `css/estilos.css`, su `js/app.js`, seis fotos locales en WebP (dominio público y CC0), plano
propio en SVG y `CREDITOS.md` con la procedencia de cada foto. Cumple la lista obligatoria de 11
puntos de [BANCO_PLANTILLAS.md](BANCO_PLANTILLAS.md).

Lo que aporta al banco, además de la plantilla:

- **La plantilla como documento comercial.** La carta es el corazón de la página: plato estrella con
  foto, precio y pedido directo por WhatsApp, y cuatro bloques de platos (entradas, fondos y
  parrilla, postres y bebidas) construidos con una línea de puntos entre el nombre y el precio, el
  mismo recurso de una carta impresa. Los tres avisos de «precios de referencia» y «promociones de
  ejemplo» dejan claro qué se reemplaza al publicar.
- **Un formulario que no es solo de contacto.** La reserva pide personas, hora y día con
  `select`/`date`, valida campo por campo en el navegador y, al enviarse bien, declara que es una
  demostración en vez de simular una mesa reservada.
- **La primera demo con dos fotos de detalle** en la misma sección (la causa y los postres), además
  de la vertical del chef: el hero de tres fotos y la sección oscura «Nuestra cocina» no repiten
  ninguna imagen.

Verificado: 200 y tipo MIME correcto en la carpeta y en cada archivo (HTML, CSS, JS, seis WebP, el
SVG del plano y el propio `CREDITOS.md`), sin peticiones de terceros —solo el documento, los dos
recursos de la página, las seis fotos, el plano y el favicon en `data:`— y con la consola sin
errores; `/demo/restaurante-carta` redirige 302 a `/demos/restaurante-carta/` y la ficha
`/plantillas/restaurante-carta` ya enlaza la demo, con la tarjeta marcada «Destacada».

Comprobado en el navegador a 1440 px y a 390 px: sin desbordamiento horizontal (1423 px de ancho de
documento en una ventana de 1440, y 373 en una de 390), el hero mide 88 unidades de viewport a
1440 px y crece en móvil en vez de recortar el texto, la pastilla de control baja debajo del texto,
los puntos miden 44 × 44 px, el botón del menú también y el menú móvil abre y cierra con
`aria-expanded` al día sin salirse de la pantalla ni tapar contenido cuando está cerrado. El slider
se avanzó a mano en las dos medidas (cambia a la segunda foto, la del pollo a la brasa) y mantiene
`aria-hidden` y `aria-current` en su sitio. El interruptor de tema se probó en los dos sentidos y
con recarga de por medio: en claro se ve la luna, en oscuro el sol, `aria-pressed` y la etiqueta
acompañan, `color-scheme` pasa a `dark`, el plano se invierte con su filtro y la elección se
guarda en `restaurante-carta-tema` (el script del tema sigue leyéndose antes del CSS, así que no hay
destello al recargar). El formulario de reserva, enviado vacío, marca sus seis campos y deja el foco
en el primero sin recargar; enviado completo, avisa de que la demo no manda datos. El salto a un
ancla no deja el título tapado por la cabecera pegada y el botón flotante de WhatsApp (56 × 56) no
se superpone al envío del formulario.

Limitación del entorno, no del cambio: en esta sesión el panel de vista previa no compuso fotogramas,
así que la revisión fue por medidas del DOM y estilos calculados en lugar de por capturas, y
`prefers-reduced-motion` no se pudo emular (esa parte queda cubierta por la prueba del banco sobre
los archivos y por ser la misma lógica ya verificada en la plantilla de referencia).

En esta entrega también se actualizaron las pruebas que daban por hecho que esta plantilla no tenía
demo (`tests/Unit/CatalogoPlantillasTest.php`), que ahora usan `estudio-contable` como el ejemplo
sin carpeta; la suite Unit queda en **262 pruebas y 1613 aserciones en verde** (16 pruebas y 340
aserciones las del catálogo). (Cuando `estudio-contable` publicó su demostración —ver 4.9— el
ejemplo sin carpeta pasó a ser `inmobiliaria-propiedades`.)

Pendiente de este estándar: **las tres plantillas restantes** (Portafolio Creativo, Estudio Contable
e Inmobiliaria) todavía no tienen demostración. Cada una es un proyecto web completo y se construye
con la lista obligatoria, una por entrega. (Desde la 4.9 quedan dos: Portafolio Creativo e
Inmobiliaria.)

### 4.9 La demo de Estudio Contable: el segundo sitio web, rediseñado de cero

La primera versión de `public/demos/estudio-contable/` se construyó sobre la demo de la academia
—mismo esqueleto de página, misma hoja de estilos, mismo JavaScript— cambiando la paleta a azul
marino y dorado y los textos. Funcionaba y cumplía la lista obligatoria, pero incumplía la regla de
diferenciación que quedó escrita en `docs/BANCO_PLANTILLAS.md`: al abrir las dos demos a la vez se
veía la misma plantilla con otro color. Esta entrega rehace el diseño completo.

**Lenguaje de la plantilla: «expediente contable».** El sitio se lee como el informe de un
despacho: papel cálido (`#F7F4ED`) y tinta (`#171C1D`), verde de sello para la acción y ocre para
los marcadores; titulares en serif (Georgia y equivalentes del sistema) con el cuerpo en sans;
filetes de un píxel en lugar de tarjetas con sombra y esquinas casi rectas (radio de 2 px); cifras
tabulares alineadas en columna en precios, plazos y datos. La arquitectura también cambia: cada
sección tiene un **margen numerado** (el número y el título viven en una columna a la izquierda,
pegajosa en escritorio, y el contenido ocupa la derecha), los servicios se leen como un **libro de
entradas** (tabla con asiento, nombre, área, plazo y precio) y los planes como una **hoja de
precios** en tres columnas separadas por filetes. En móvil el libro pasa a fichas apiladas y la
hoja de precios a una lista, con el plan recomendado sobre el papel más oscuro.

| Pieza | Archivos |
| --- | --- |
| Páginas | `index.html`, `servicios.html`, `servicio-asesoria-mensual.html`, `planes.html`, `contacto.html` |
| Estilos | `css/estilos.css` (hoja nueva, 2.900 líneas) |
| JavaScript | `js/app.js` (reescrito: pista con `transform`, revelado escalonado, filtros del libro, validación por reglas) |
| Fotografías | `img/` (las siete CC0 de Commons, ya documentadas en `CREDITOS.md`) |

**Lo que se conserva, y por qué.** La lista obligatoria (portada con slider de tres fotos, fotos
reales, WhatsApp y ubicación, prueba social, precios, redes, movimiento, tema claro/oscuro) y los
nombres que vigilan las pruebas del banco: `.portada`, `data-carrusel`, `portada__fondo`,
`portada__velo`, `portada__contenido`, `.portada__diapositiva`, `.carrusel__punto`,
`[data-carrusel-anterior|siguiente|punto]`, `.tema-switch`, `html.js`/`html.no-js` y la clave
`estudio-contable-tema`. Son la interfaz mínima del banco, no el diseño: el slider sigue siendo
obligatorio, pero aquí la pista se mueve con `transform`, los controles son un contador `01 / 03`
con flechas y rayas, y el velo es un degradado lateral en vez de un oscurecido plano.

**Verificado.** `php artisan test --filter=CatalogoPlantillas`: **16 pruebas y 458 aserciones en
verde**, incluidas las que ahora corren sobre esta demo (hero con tres fotos distintas por huella
de píxeles, slider usable a mano y sin JavaScript, interruptor de tema en las cinco páginas con
clave propia, enlaces internos sin roturas y ninguna página huérfana). La suite Unit completa queda
en **262 pruebas y 1731 aserciones**.

Por HTTP, con `php artisan serve` en 8123: 200 en la carpeta y en cada archivo (las cinco páginas,
la hoja de estilos, el JavaScript, las siete fotos, el plano y el `CREDITOS.md`), 302 de
`/demo/estudio-contable` a `/demos/estudio-contable/`, y la ficha `/plantillas/estudio-contable`
enlazando la demostración. Sin peticiones a terceros: solo el documento, los dos recursos de la
página, las fotos, el plano y el favicon en `data:`.

**Un defecto encontrado y corregido.** En el celular la tabla comparativa (42rem de ancho mínimo)
estiraba la rejilla de la sección: el documento medía **692 px en una ventana de 312 px**, con scroll
lateral en toda la página. La causa es la conocida de CSS: un hijo de grid tiene `min-width: auto`,
así que el contenido ancho agranda la columna aunque el contenedor declare `overflow-x: auto`. Se
corrigió con `min-width: 0` en las columnas de `.seccion__rejilla`, `.ficha`, `.visitanos` y
`.encabezado__rejilla`; ahora el documento mide 373 px en una ventana de 390 y la tabla se desplaza
dentro de su contenedor (672 px dentro de 333 px). En la misma pasada se corrigió el contraste del
autor en oscuro (el verde fijo del sello pasó a la variable de acento en el menú activo, los bordes
del botón secundario, la pestaña de servicio activa y las tablas), se dejó el menú del celular
utilizable sin JavaScript (los enlaces se muestran envueltos y el botón se oculta) y se llevaron los
campos y las casillas del formulario a 44 px de alto.

Comprobado además en el navegador a 1440 px y a 390 px: sin desbordamiento horizontal en ninguna de
las cinco páginas; el hero mide 88 unidades de viewport (792 px de 900) y crece en móvil en vez de
recortar el texto; los puntos del slider y el botón del menú miden 44 × 44 px; la pastilla de
control (contador, flechas y puntos) queda dentro de la portada debajo del texto en móvil; el
margen numerado se pega al hacer scroll solo a partir de 1000 px; el panel de la ficha es
pegajoso; el formulario, enviado vacío, marca sus seis reglas y deja el foco en el primer campo, y
enviado completo avisa de que es una demostración; y el tema elegido viaja entre las cinco páginas
(localStorage) con `aria-pressed`, la etiqueta del botón y `color-scheme: dark` en consonancia.

Limitación del entorno, no del cambio: durante buena parte de la sesión el panel de vista previa
dejó de componer fotogramas («no frames»), así que la revisión final fue por medidas del DOM y
estilos calculados en lugar de por capturas —el hero, el libro de servicios, la hoja de precios, el
banda con la foto vertical y el modo oscuro del inicio sí se alcanzaron a ver en captura antes de
que el panel se detuviera— y `prefers-reduced-motion` no se pudo emular (queda cubierto por la
prueba del banco sobre los archivos y por el respeto de la preferencia en el propio JavaScript).

Queda pendiente, como en las otras demos, lo que no depende de código: que un diseñador valide la
hoja a ojo en un monitor grande, y reemplazar la foto vertical (`img/equipo.webp`, una persona con
el rostro visible) por una del equipo real del cliente cuando esta plantilla se venda.
