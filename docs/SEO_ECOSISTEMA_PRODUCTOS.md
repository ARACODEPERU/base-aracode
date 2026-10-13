# SEO del ecosistema de productos: `/kapta`, `/kirafact` y `/pichanguero`

Decisión tomada el 10 de octubre de 2026. Aplica a las tres páginas de producto
del sitio corporativo y a sus sitios independientes (`resources/views/kapta`,
`kirafact` y `pichanguero`).

## Decisión

Las tres URLs del sitio corporativo **se mantienen publicadas** como ficha de
producto de ARACODE. No se retiran (410/404) ni se redirigen (301) al sitio de
cada producto mientras cada producto no viva en su propio dominio.

Cada producto tiene dos páginas y cada una cubre una intención distinta:

| Página | Intención que cubre | Precios |
| --- | --- | --- |
| `aracodeperu.com/kapta`, `/kirafact`, `/pichanguero` (ficha) | **Vendedor**: quién desarrolla el producto, para quién es, con quién coordinar una demo o una asesoría. Consultas de marca + empresa. | No publica importes: enlaza al sitio oficial. |
| `site/kapta`, `site/kirafact`, `site/pichanguero` (sitio oficial del producto) | **Producto**: funcionalidades, planes y precios vigentes, descargas, condiciones. | Los publica solo cuando están confirmados (KAPTA sí; KIRAFACT los confirma con el equipo comercial). |

## Por qué se mantienen

1. Están indexadas, figuran en `public/sitemap.xml` y son el **destino de los 301
   internos** (`/e-learning` y `/sitios-webs` → `/kapta`; `/facturador` →
   `/kirafact`). Retirarlas tira esa autoridad y rompe esos enlaces; redirigir la
   ficha añade un salto más a la cadena.
2. Mientras los sitios de producto viven bajo `site/*` del mismo dominio, un 301
   dejaría la ficha apuntando a otra página **del mismo host**, no al dominio del
   producto: la redirección habría que repetirla el día del dominio propio (doble
   salto y un movimiento de URL que ya no se puede deshacer).
3. El contenido de la ficha es propio (no copia el del sitio de producto) y
   aporta algo que el sitio del producto no tiene: la relación con ARACODE
   (autoría, soporte, contacto corporativo).
4. Redirigir la ficha a otro dominio dejaría al visitante que ya está en
   aracodeperu.com sin página del producto dentro del sitio, y a ARACODE sin su
   página de vendedor.

## Reglas que sostienen la decisión

1. **Los precios viven en un solo lugar.** La ficha corporativa no publica
   importes; enlaza a la página de planes del producto. Llegó a haber tres tablas
   distintas para KAPTA (S/ 149 y S/ 299 en la ficha frente a los seis planes de
   `config/kapta.php`) y para KIRAFACT la ficha publicaba S/ 35-80 mientras su
   config las declaraba «sin publicar». Dos listas del mismo producto confunden
   al visitante y reparten la autoridad entre dos URLs del mismo dominio.
2. **La ficha no compite por las consultas del producto.** Sus títulos y textos
   hablan de autoría, puesta en marcha y contacto (ARACODE), no de «planes»,
   «precios» o «descargar».
3. **Un solo mapa de URLs**: `config/productos.php`, resuelto por
   `App\Support\SitiosProducto`. Lo consumen `/soluciones` y las tres fichas;
   ninguna vista escribe la URL de un producto.
4. **Enlaces salientes seguidos** (sin `nofollow`), con ancla descriptiva («Ir al
   sitio de KAPTA») y `target="_blank" rel="noopener"` más el aviso para lectores
   de pantalla, igual que en `/soluciones`. El destino es otra web, con su propia
   navegación y su propio logotipo.
5. **Canónica propia** en cada ficha (`<link rel="canonical">` del layout, con
   `url()->current()`).
6. **Datos estructurados**: cada ficha publica un `SoftwareApplication` en JSON-LD
   con `name` tomado del mapa de productos, `author` y `publisher` = ARACODE y
   `mainEntityOfPage` = la ficha. Refuerza la relación marca ↔ producto, que es
   justo lo que la ficha aporta.
7. **Sitemap**: las tres URLs siguen dentro de `public/sitemap.xml` con
   `changefreq` mensual. Se actualiza su `lastmod` cuando cambie el contenido.
8. **La descarga vigente la publica el producto.** El botón de la app en
   `/pichanguero` manda a la página de descargas del sitio oficial, que es la que
   mantiene el APK al día y explica cómo instalarlo.
9. **Una sola grafía por marca.** El nombre comercial de cada producto sale de
   `config/productos.php` (`SitiosProducto::nombre()`): lo usan el título, el
   JSON-LD y el cierre de la ficha. KIRAFACT se escribe así, en mayúsculas, como
   lo publica el producto en su propio `<title>` y en `config/kirafact.php`
   (`titulo_marca`); KAPTA LMS y Pichanguero, como están en el mapa. Dos grafías
   del mismo producto (llegó a haber «KiraFact» en la home, `/soluciones`, el pie
   y la ficha, frente a «KIRAFACT» en el sitio del producto) parten en dos la
   entidad de marca que Google asocia a las páginas.

## El día que un producto pase a su propio dominio

1. En `config/productos.php`, cambiar la ruta por la URL absoluta:
   `'sitio' => 'site/kapta'` → `'sitio' => 'https://kapta.pe'` (y `'planes'`).
2. `php artisan config:clear` y comprobar con curl que `/soluciones`, `/kapta`,
   `/kirafact` y `/pichanguero` enlazan al dominio nuevo.
3. En el sitio del producto: publicar allí su propio `sitemap.xml`, su canónica y
   sus redirecciones internas.
4. **No** redirigir la ficha al dominio nuevo. La ficha se queda donde está: es la
   página de ARACODE como vendedor. Solo se considera consolidar cuando haya
   duplicidad real de intención, es decir, si la ficha empieza a posicionar por
   las mismas consultas de marca que el dominio del producto y Google elige la
   equivocada. Criterio de revisión: Search Console, 8-12 semanas después del
   lanzamiento.
5. **Redirigir las URLs viejas del producto.** Mientras el producto vive bajo
   `aracodeperu.com/site/kapta` se indexa en ese dominio: sus páginas son
   canónicas de sí mismas (`url()->current()`, con `index, follow`) y Google las
   conoce con esa URL. El día del dominio propio hay que añadir en
   `routes/aracode.php` un 301 por cada URL publicada
   (`Route::redirect('/site/kapta', 'https://kapta.pe', 301)` y una por página:
   planes, descargas, contacto) y mantenerlo al menos un año, hasta que Google
   reindexe. Sin ese paso quedan dos copias del producto, una en cada dominio.
   La alternativa, si se prefiere que el producto no compita con su ficha
   mientras sea una subpágina, es publicar `noindex` en `site/*`; entonces no hay
   nada que redirigir, pero el producto tampoco aparece en Google hasta que viva
   en su dominio.
6. Si algún día se decide redirigir (mudanza de marca, retirada del producto),
   hacerlo sin cadenas:
   - Actualizar los 301 de entrada para que apunten al destino final
     (`/e-learning` y `/sitios-webs` al dominio del producto, no a `/kapta`).
   - Añadir `Route::redirect('/kapta', 'https://kapta.pe', 301)` en
     `routes/aracode.php` y retirar la vista.
   - Quitar la URL del `sitemap.xml` y actualizar `config/productos.php`.
   - Mantener el 301 de forma permanente (nunca 302 temporal).
   - Revisar los enlaces internos a la ficha (pie, home y fichas hermanas) y
     apuntarlos al destino.

## Verificación

`tests/Unit/FichasProductoSeoTest.php` comprueba lo que promete esta decisión:
las tres fichas responden 200, se declaran canónicas de sí mismas, publican un
solo `SoftwareApplication` con `name` desde el mapa de productos, no contienen
importes, su `<title>` nombra a ARACODE y enlazan al sitio oficial; y las URLs
retiradas (`/e-learning`, `/sitios-webs`, `/facturador`, `/soluciones/kapta`,
`/soluciones/facturacion`) siguen llegando con 301. Esa prueba crea solo las tres
tablas del CMS que pide `WebPageController` y evita `RefreshDatabase` porque la
suite Feature de este repositorio no arranca en sqlite: hay migraciones de otros
módulos que solo funcionan en MySQL (`ALTER ... MODIFY`,
`information_schema`) y abortan la creación del esquema.

Comprobación manual, equivalente a la anterior y útil contra un servidor real:

- `curl` a las tres fichas: 200, canónica propia, un único JSON-LD
  `SoftwareApplication` válido (`name` desde el mapa de productos), sin importes
  en el HTML y con el enlace al sitio oficial.
- `curl -I` a `/e-learning`, `/sitios-webs`, `/facturador`,
  `/soluciones/kapta` y `/soluciones/facturacion`: 301 y destino final, sin
  cadenas.
- La prueba de que no hay URLs escritas a mano en las vistas: cambiar la ruta en
  `config/productos.php`, limpiar la config y ver que cambian todos los enlaces
  (catálogo y fichas) a la vez.
