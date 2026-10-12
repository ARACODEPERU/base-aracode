# Servicio de páginas web: `/paginas-web`

Decisión tomada el 12 de octubre de 2026. Convierte el Banco de Plantillas
(`docs/BANCO_PLANTILLAS_FASE1.md` y `docs/BANCO_PLANTILLAS.md`) en un servicio
vendible: el emprendedor elige una plantilla del catálogo y ARACODE la publica
con su marca y su dominio, por una sola tarifa mensual o anual.

## La decisión

El catálogo dice **qué** se puede publicar; esta página dice **cómo y por
cuánto**. Se publica en una URL propia (`/paginas-web`) y no como una sección
más del catálogo, para que pueda posicionar por su cuenta («páginas web para
emprendedores», «página web con dominio incluido») y recibir enlaces internos
sin competir con `/plantillas`.

El precio es de **plan**, no de proyecto: hosting, dominio del primer año,
certificado de seguridad y cambios de contenido van dentro de la tarifa. Eso es
lo que lo separa del desarrollo a medida de `/precios` (que sigue publicando
«Sitios Web desde S/ 2,000») y lo que hace que un emprendedor pueda decidir sin
cotización previa.

| Formato del catálogo | Plan | Mensual | Año completo | Entrega |
| --- | --- | --- | --- | --- |
| `#Landing` | Landing | S/ 89 | S/ 890 | 3 a 5 días |
| `#SitioWeb` | Sitio web | S/ 149 | S/ 1,490 | 5 a 8 días |
| `#Tienda` | Tienda en línea | S/ 199 | S/ 1,990 | 7 a 10 días |

El año completo se vende como «dos meses gratis» porque son exactamente diez
mensualidades. La prueba `tests/Unit/ServicioPaginasWebTest.php` lo exige, así
que subir el mensual sin tocar el anual no puede dejar el mensaje mintiendo.

## De dónde salen los precios (una sola fuente)

Todo vive en **`config/servicio_web.php`** y se lee a través de
**`App\Support\ServicioWeb`**, que es lo único que consume la config:

- La página del servicio (`resources/views/aracode/pages/paginas-web.blade.php`),
  con su tabla de planes, su JSON-LD `Service` con las ofertas y su JSON-LD
  `FAQPage`.
- El bloque que cierra el catálogo en `/plantillas`: la tarjeta, el importe
  anual y el plazo salen del mismo dato.
- La tarjeta del servicio en `/soluciones`, junto a los productos del
  ecosistema y a los otros dos servicios (Automatización e IA). Es un servicio,
  así que lleva la franja con degradado morado e icono en vez de una imagen
  diseñada, y enlaza a su página en la misma pestaña porque es interna. Su
  «desde S/ 89 al mes» sale de `ServicioWeb::precioDesde()` (el plan más barato
  de los publicados), no de un importe escrito en la vista.
- La ficha de cada plantilla, que anuncia el plan **de su propio formato** («una
  landing publicada desde S/ 89/mes · entrega en 3 a 5 días»): una landing nunca
  debe mostrar el precio de la tienda.

Ninguna vista escribe un importe a mano. Cambiar un precio es editar una línea
del archivo, `php artisan config:clear` y listo; la tarjeta, el marcado de datos
estructurados y el bloque del catálogo cambian a la vez.

La clave de cada plan es la misma de un formato de `config/plantillas.php`
(`landing`, `sitio`, `tienda`). No es una coincidencia: el formato es la primera
pregunta de la venta, así que se llama igual en el catálogo, en la tarjeta de la
plantilla, en el plan y en el buscador (`/plantillas?q=#SitioWeb`). La prueba del
servicio exige que **todo formato publicado tenga su plan** (un formato sin
precio no se puede vender) y que todo plan tenga su formato.

## Lo que la página no promete

El **panel de autogestión del cliente** (que el emprendedor edite textos, fotos
y precios por su cuenta) **no existe todavía**: las demostraciones del banco son
HTML estático. Por eso la página no lo menciona: hoy se promete lo que sí hay
—los cambios de contenido los hace ARACODE, con una cantidad incluida al año— y
el marco de la oferta está listo para añadirlo el día que se construya. La
prueba `no promete el panel de autogestión mientras no exista` es el recordatorio:
si algún día falla, o hay una promesa que no se puede cumplir, o el panel ya
existe y toca actualizar la prueba y este documento.

Ese panel es el siguiente paso natural del servicio (login del cliente, edición
de contenido y republicación), pero es un proyecto en sí: merece su propio
documento antes de tocarlo.

## Cómo se cobra

Manual, coordinado por el equipo: transferencia, Yape o Plin
(`nota_pago` en la config). No hay pasarela ni cobro recurrente: mientras el
volumen no lo justifique, integrar una suscripción automática agrega trámite y
comisión sin resolver el problema del cliente. El día que se quiera cobrar en
línea, la tarjeta del plan ya apunta a `/contacto`, así que no hay nada que
deshacer.

## Dónde está cada cosa

| Pieza | Archivo |
| --- | --- |
| Precios, plazos y lo que incluye cada plan | `config/servicio_web.php` |
| Resolución de importes, ahorro y plan por formato | `app/Support/ServicioWeb.php` |
| Página del servicio | `resources/views/aracode/pages/paginas-web.blade.php` |
| Tarjeta del servicio en el ecosistema | `resources/views/aracode/pages/soluciones.blade.php` |
| Franja morada de la tarjeta | `resources/css/webpage-v2.css` (y su copia en `public/css/webpage-v2.css`) |
| Bloque de planes del catálogo | `resources/views/plantillas/catalogo.blade.php` |
| Plan del formato en la ficha | `resources/views/plantillas/ficha.blade.php` |
| Ruta (`/paginas-web`) y controlador | `routes/plantillas.php`, `PlantillasController::paginasWeb` |
| Mapa del sitio | `public/sitemap.xml` |
| Pruebas | `tests/Unit/ServicioPaginasWebTest.php` |

La ruta va a nivel de raíz y no colgando de `/plantillas`: el comodín
`/plantillas/{slug}` está registrado antes y se comería cualquier página hija
(respondería 404 «no existe esa plantilla»). El nombre de ruta
(`plantillas.paginas_web`) sí empieza por `plantillas.` a propósito: el enlace
del navbar se marca activo con `routeIs('plantillas.*')` y el servicio forma
parte de esa sección.

## Lo que no se toca

`/sitios-webs` sigue siendo un 301 a `/kapta` y no se convierte en la URL del
servicio. Está cubierto por `tests/Unit/FichasProductoSeoTest.php` y cambiar esa
redirección es una decisión de marca (¿KAPTA es «sitios webs»?) que no
corresponde a esta página. Si algún día se decide que `/sitios-webs` es la URL
del servicio, hay que quitar el 301 en `routes/aracode.php`, actualizar esa
prueba **con motivo escrito** y trasladar el `loc` del sitemap.

## Verificación

`php vendor/bin/pest tests/Unit/ServicioPaginasWebTest.php` — precios, formato
por plan, la página (canónica, importes publicados, datos estructurados con una
oferta mensual y otra anual por plan, FAQPage), la ausencia del panel y los
enlaces del catálogo y de la ficha.

Y contra un servidor real, con Chrome: `node storage/tmp/revisar-paginas-web.mjs`
comprueba a 1440 y a 390 px que no hay errores de consola ni peticiones
fallidas, que cada plan publica sus dos importes, que el acordeón abre y cierra,
que no hay desborde lateral, que el bloque del catálogo aparece debajo de la
rejilla, que la tarjeta de `/soluciones` lleva a `/paginas-web` con su degradado
morado, y deja capturas en `storage/tmp/repaso-paginas-web/`. El guion borra el
perfil de Chrome antes de cada pasada: con la caché de la anterior mediría la
hoja de estilos vieja.

`tests/Unit/CatalogoProductosTest.php` cuenta las tarjetas del ecosistema: pasó
de seis a siete al sumarse este servicio, y ahora fija que las tres últimas son
servicios (franja con degradado, sin imagen diseñada).
