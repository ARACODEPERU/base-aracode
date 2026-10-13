<?php

/*
|--------------------------------------------------------------------------
| Servicio de páginas web — planes
|--------------------------------------------------------------------------
|
| Es la oferta que convierte el Banco de Plantillas en un servicio vendible: el
| emprendedor elige una plantilla del catálogo y ARACODE la publica con su
| dominio, con una sola tarifa mensual o anual (hosting, dominio y cambios
| incluidos) en lugar de un proyecto a medida.
|
| Lo lee App\Support\ServicioWeb, que es lo único que consume la página del
| servicio (/paginas-web) y el bloque de planes del catálogo: las vistas nunca
| leen este archivo directo.
|
| Clave de cada plan
| ------------------
| Es la misma clave de un formato de `config/plantillas.php` (`tipos`): landing,
| sitio o tienda. No es casualidad: el formato es la primera pregunta de la
| venta (¿una landing de una página o un sitio de varias?), así que el plan se
| llama igual que lo que el visitante ya vio en la tarjeta de la plantilla.
| `tests/Unit/ServicioPaginasWebTest.php` exige que todo formato publicado tenga
| su plan: un formato sin precio no se puede vender.
|
| Cada plan declara:
|
|  - `nombre`      Cómo se llama el plan ante el cliente («Landing»).
|  - `resumen`     Una frase: qué es lo que se publica.
|  - `ideal_para`  A quién le toca ese plan.
|  - `entrega`     Plazo de publicación en días hábiles, contados desde que el
|                  cliente entrega textos, fotos y logo.
|  - `incluye`     Lo que trae el plan (se pinta como lista de la tarjeta).
|  - `mensual`     Importe en soles, sin IGV, por mes.
|  - `anual`       Importe en soles del año completo. Se publica como «dos meses
|                  gratis», así que tiene que ser exactamente diez mensualidades:
|                  la prueba del servicio lo comprueba, para que subir el mensual
|                  sin tocar el anual no deje el mensaje mintiendo.
|  - `destacado`   `true` en el plan que se resalta en la página («Más elegido»).
|
| Lo que incluyen todos los planes va en `incluye_siempre`, una sola lista que
| evita repetir seis líneas en cada plan y que se mantiene en un único sitio.
|
| Recordatorio: ejecutar `php artisan config:clear` (o `config:cache` en
| producción) después de editar este archivo.
|
*/

return [

    'planes' => [

        'landing' => [
            'nombre' => 'Landing',
            'resumen' => 'Una sola página con todo lo esencial: qué ofreces, cómo trabajas y cómo te contactan.',
            'ideal_para' => 'Profesionales, oficios y emprendimientos que necesitan estar en internet y recibir consultas ya.',
            'entrega' => '3 a 5 días',
            'incluye' => [
                'Una página con secciones: servicios, sobre ti, ubicación y contacto',
                'Formulario de contacto y botón de WhatsApp',
                'Adaptación con tu logo, tus colores, tus textos y tus fotos',
                'Publicación con tu dominio propio',
                'Hasta 4 cambios de contenido al año',
            ],
            'mensual' => 89,
            'anual' => 890,
        ],

        'sitio' => [
            'nombre' => 'Sitio web',
            'resumen' => 'Varias páginas enlazadas con menú: cada servicio, especialidad o producto en su propia página.',
            'ideal_para' => 'Negocios que ya venden y necesitan ordenar su oferta: consultoras, estudios, clínicas, academias e inmobiliarias.',
            'entrega' => '5 a 8 días',
            'incluye' => [
                'Hasta 5 páginas enlazadas con menú de navegación',
                'Una página por servicio o especialidad, más inicio y contacto',
                'Formulario de contacto, WhatsApp y mapa de ubicación',
                'Textos del rubro listos para reemplazar por los tuyos',
                'Publicación con tu dominio propio',
                'Hasta 8 cambios de contenido al año',
            ],
            'mensual' => 149,
            'anual' => 1490,
            'destacado' => true,
        ],

        'tienda' => [
            'nombre' => 'Tienda en línea',
            'resumen' => 'Catálogo con precios y pedido por WhatsApp: tus productos ordenados y siempre a la vista.',
            'ideal_para' => 'Comercios que hoy atienden por redes sociales y quieren un catálogo propio donde mostrar y recibir pedidos.',
            'entrega' => '7 a 10 días',
            'incluye' => [
                'Catálogo con categorías, buscador y filtros',
                'Ficha de cada producto con fotos, precio y descripción',
                'Canasta que recuerda lo elegido y pedido por WhatsApp',
                'Publicación con tu dominio propio',
                'Hasta 8 cambios de contenido al año',
            ],
            'mensual' => 199,
            'anual' => 1990,
        ],

    ],

    /*
     * Lo que trae cualquier plan, para no repetirlo seis veces por tarjeta.
     */
    'incluye_siempre' => [
        'Dominio propio (.com o .pe) el primer año',
        'Hosting y certificado de seguridad (SSL) incluidos',
        'Diseño adaptable a celular, tablet y computadora',
        'Alta en Google: mapa del sitio, Search Console y datos para compartir en redes',
        'Sin costo de implementación: se paga la primera mensualidad o el año completo',
        'Soporte por WhatsApp y correo mientras el plan esté vigente',
    ],

    /*
     * Cómo se paga. Va en la página del servicio, bajo la tabla de planes.
     */
    'nota_pago' => 'Se paga por transferencia, Yape o Plin. El año completo equivale a diez mensualidades: los dos últimos meses no se cobran.',

];
