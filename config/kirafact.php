<?php

/*
|--------------------------------------------------------------------------
| KIRAFACT — contenido y configuración del sitio web
|--------------------------------------------------------------------------
|
| Aquí vive todo lo que decide el negocio y no debe estar repartido por las
| vistas: la URL del sistema, los logotipos, el contenido comercial, las
| funcionalidades aprobadas, los planes (todavía sin publicar), las preguntas
| frecuentes y el contacto.
|
| ESTADO DE LOS DATOS (importante):
| El sitio publica únicamente información que se puede sostener. Todo lo que
| todavía necesita confirmación está marcado con 'publicado' => false y NO se
| renderiza, pero queda listo para activarse cambiando ese valor.
|
| Los colores de marca viven en dos sitios que hay que cambiar juntos:
| resources/css/kirafact/tailwind.config.js y el bloque :root de
| resources/css/kirafact/kirafact.css. Son provisionales hasta que exista el
| manual de marca: no están tomados del logotipo oficial porque ese archivo
| todavía no está en el repositorio.
|
*/

return [

    /*
    | Solicitud de demo: la acción principal del sitio.
    |--------------------------------------------------------------------------
    | Es el texto de TODOS los botones de conversión (cabecera, menú móvil,
    | hero, sección de demo, cierre y pie). El destino no se configura aquí:
    | el botón abre WhatsApp con 'mensajes.demo_whatsapp' ya escrito, usando el
    | número real de 'contacto'.
    |
    | Esta web no ofrece acceso de clientes ni pide credenciales: no hay portal
    | dentro de ella, así que no se publica ningún botón de ingreso.
    */
    'demo' => [
        'boton' => 'Solicitar una demo',
    ],

    /*
    | Marca.
    |--------------------------------------------------------------------------
    | El sitio NO incluye ninguna versión dibujada, recoloreada ni
    | reinterpretada del logotipo de KIRAFACT. Mientras no exista el archivo
    | oficial, la cabecera y el pie muestran un hueco reservado con el nombre
    | como texto.
    |
    | Para publicarlo: deja el archivo en public/themes/kirafact/images/ y
    | escribe aquí su ruta relativa a public/:
    |
    |     'logo'      => 'themes/kirafact/images/kirafact-logo.svg',      // fondos claros
    |     'logo_dark' => 'themes/kirafact/images/kirafact-logo-dark.svg', // fondos azul marino
    |
    | Si solo existe una versión, se usa la misma ruta en las dos claves.
    */
    'marca' => [
        'nombre' => 'KIRAFACT',
        'descriptor' => 'Facturación electrónica y gestión empresarial',
        'firma' => 'by ARACODE SMART SOLUTIONS',
        'logo' => null,
        'logo_dark' => null,
        'logo_alto' => 38,
        'slot' => 'Logotipo por definir',

        /* ARACODE sí tiene logotipo oficial en el proyecto y se usa tal cual,
           sin recolorear: su versión lleva el nombre en blanco, así que está
           pensada para fondos oscuros y se coloca sobre el pie azul marino. */
        'empresa' => 'ARACODE SMART SOLUTIONS',
        'logo_aracode' => 'themes/webpage/images/logo.png',
        'logo_aracode_alto' => 28,
        'web_aracode' => 'https://aracodeperu.com',
    ],

    /*
    | SEO y metadatos para compartir.
    | 'canonical' queda vacío hasta que exista el dominio oficial; mientras
    | tanto el layout usa la URL actual.
    */
    'seo' => [
        'title' => 'KIRAFACT — Facturación electrónica y gestión empresarial',
        'description' => 'KIRAFACT es un software de facturación electrónica y gestión empresarial de ARACODE Smart Solutions: emisión de comprobantes electrónicos, envío a SUNAT, reportes de ventas y consulta del histórico.',
        'og_title' => 'KIRAFACT — Facturación electrónica y gestión empresarial',
        'og_description' => 'Emite comprobantes electrónicos y sigue la operación de tu negocio desde un mismo sistema. Un producto de ARACODE Smart Solutions.',
        /*
        | Imagen para compartir: 1200x630, medida que piden las redes sociales.
        | Su fuente editable está en resources/og/kirafact-social.html y se
        | vuelve a generar con Chrome headless (ver README). Lleva el nombre y
        | el descriptor como texto, sin ningún símbolo de marca: cuando exista
        | el logotipo oficial, hay que rehacerla con él.
        */
        'og_image' => 'themes/kirafact/images/og-image.png',
        'og_image_ancho' => 1200,
        'og_image_alto' => 630,
        'og_image_tipo' => 'image/png',
        'canonical' => env('KIRAFACT_CANONICAL_URL'),
        'color_tema' => '#0B1B3A',
    ],

    /*
    | Datos de contacto comercial reales (los de ARACODE Smart Solutions).
    | Se verifican contra el pie del sitio corporativo; no se inventa ninguno.
    */
    'contacto' => [
        'whatsapp' => '+51 917 295 856',
        'whatsapp_url' => 'https://wa.me/51917295856',
        'email' => 'contacto@aracodeperu.com',
        'ciudad' => 'Nuevo Chimbote, Perú',
        'web' => 'https://aracodeperu.com',
    ],

    /*
    | Páginas legales propias del sitio. Solo se enlazan las que existan.
    */
    'legal' => [
        'privacidad' => null,
        'terminos' => null,
    ],

    /*
    | Mensajes reutilizados por las vistas.
    */
    'mensajes' => [
        'info_whatsapp' => 'Hola, quiero información sobre KIRAFACT.',
        'demo_whatsapp' => 'Hola, quiero solicitar una demo de KIRAFACT.',
    ],

    /*
    | Hero principal.
    |--------------------------------------------------------------------------
    | Sin cifras, testimonios ni promesas: describe lo que el producto hace.
    */
    'hero' => [
        'etiqueta' => 'Facturación electrónica y gestión empresarial',
        'titulo_antes' => 'Tu negocio, mejor organizado con',
        'titulo_marca' => 'KIRAFACT',
        'descripcion' => 'Una solución de software para facilitar la facturación electrónica y ayudarte a gestionar mejor las operaciones de tu negocio.',
        'puntos' => [
            ['icono' => 'comprobante', 'texto' => 'Facturas, boletas y notas electrónicas'],
            ['icono' => 'sunat', 'texto' => 'Envío y estado de cada comprobante'],
            ['icono' => 'reportes', 'texto' => 'Información de ventas del negocio'],
        ],
        'cta_principal' => 'Conoce KIRAFACT',
    ],

    /*
    | Presentación del producto.
    */
    'producto' => [
        'etiqueta' => 'El producto',
        'titulo' => '¿Qué es KIRAFACT?',
        'parrafos' => [
            'KIRAFACT es un software de facturación electrónica y gestión empresarial desarrollado por ARACODE Smart Solutions para el mercado peruano.',
            'Con KIRAFACT, la emisión de comprobantes y la información de la operación dejan de estar repartidas entre documentos sueltos: se trabajan desde un mismo sistema.',
        ],
        'ficha' => [
            ['dato' => 'Producto', 'valor' => 'KIRAFACT'],
            ['dato' => 'Alcance', 'valor' => 'Facturación electrónica y gestión empresarial'],
            ['dato' => 'Desarrollado por', 'valor' => 'ARACODE SMART SOLUTIONS'],
            ['dato' => 'Mercado', 'valor' => 'Empresas del Perú'],
            ['dato' => 'Uso', 'valor' => 'Sistema independiente, con acceso propio'],
        ],
    ],

    /*
    | Composición visual del software.
    |--------------------------------------------------------------------------
    | No hay capturas reales del sistema en el repositorio. Mientras falten, la
    | sección muestra una composición hecha en HTML y CSS, rotulada como
    | «Imagen referencial»: no finge ser una captura y no incluye RUC, nombres
    | de clientes ni importes reales.
    |
    | Cuando existan las capturas oficiales: deja los archivos en
    | public/themes/kirafact/images/, escribe su ruta en 'capturas' (una entrada
    | por imagen) y la sección reemplaza la composición automáticamente.
    */
    'software' => [
        'etiqueta' => 'El sistema',
        'titulo' => 'Cómo se trabaja en KIRAFACT',
        'descripcion' => 'El recorrido de un comprobante dentro del sistema, desde que registras la venta hasta que queda consultable.',
        'pasos' => [
            [
                'titulo' => 'Registra la operación',
                'texto' => 'La venta se registra en el sistema, junto con los datos del cliente y el detalle de lo que se factura.',
            ],
            [
                'titulo' => 'Emite el comprobante',
                'texto' => 'KIRAFACT genera el comprobante electrónico con su XML y su PDF.',
            ],
            [
                'titulo' => 'Sigue el estado',
                'texto' => 'El estado del envío a SUNAT queda registrado, con el motivo cuando hay un rechazo, y el comprobante queda disponible para consultarlo después.',
            ],
        ],
        'nota' => 'Imagen referencial. La composición representa la organización de las pantallas del sistema; todavía no es una captura real ni contiene datos de clientes.',
        'capturas' => [],
    ],

    /*
    | Sección de solicitud de demo.
    |--------------------------------------------------------------------------
    | Ocupa el lugar que antes tenía el acceso de clientes. No promete prueba
    | gratuita, descuento ni plazo: describe lo que se muestra en la demo.
    */
    'demo_seccion' => [
        'titulo' => 'Conoce KIRAFACT en una demo',
        'texto' => 'Te mostramos el sistema con los casos de tu negocio: emisión de comprobantes, envío a SUNAT y reportes de ventas. La demo la coordina el equipo de ARACODE Smart Solutions.',
        'nota' => 'La demo se agenda por WhatsApp. ¿Prefieres el correo?',
    ],

    /*
    | Llamada a la acción final.
    */
    'cta' => [
        'titulo' => 'Ordena la facturación y la gestión de tu negocio',
        'texto' => 'Cuéntanos cómo factura hoy tu empresa y revisamos con nuestro equipo si KIRAFACT encaja con tu operación.',
        'boton' => 'Hablar con el equipo',
    ],

    /*
    | Funcionalidades.
    |--------------------------------------------------------------------------
    | Alcance comercial planteado para KIRAFACT. Se publican solo las marcadas
    | con 'publicado' => true, que son las que ARACODE ya comunica hoy en sus
    | páginas del producto.
    |
    | Las marcadas con 'publicado' => false son áreas candidatas que todavía
    | necesitan validación técnica contra el sistema real (inventario, compras,
    | kardex, punto de venta, rentabilidad). No se muestran al público: se
    | activan poniendo 'publicado' => true cuando estén confirmadas.
    |
    | 'icono' se resuelve con resources/views/kirafact/components/icon.blade.php
    */
    'funcionalidades' => [
        [
            'icono' => 'comprobante',
            'titulo' => 'Comprobantes electrónicos',
            'texto' => 'Emisión de facturas, boletas y notas de crédito y débito en formato electrónico.',
            'publicado' => true,
        ],
        [
            'icono' => 'sunat',
            'titulo' => 'Envío y validación con SUNAT',
            'texto' => 'El comprobante se envía desde el sistema y su estado queda registrado, con el motivo cuando hay un rechazo.',
            'publicado' => true,
        ],
        [
            'icono' => 'documento',
            'titulo' => 'Generación de XML y PDF',
            'texto' => 'Cada comprobante genera su representación electrónica y la versión imprimible en PDF.',
            'publicado' => true,
        ],
        [
            'icono' => 'reportes',
            'titulo' => 'Reportes de ventas',
            'texto' => 'Consulta la venta del periodo y el detalle de los comprobantes emitidos desde un mismo panel.',
            'publicado' => true,
        ],
        [
            'icono' => 'usuarios',
            'titulo' => 'Trabajo por usuarios',
            'texto' => 'Accesos separados para las personas que emiten y revisan comprobantes.',
            'publicado' => true,
        ],
        [
            'icono' => 'respaldo',
            'titulo' => 'Consulta del histórico',
            'texto' => 'Los comprobantes quedan disponibles en el sistema para consultarlos y descargarlos después.',
            'publicado' => true,
        ],
        [
            'icono' => 'inventario',
            'titulo' => 'Inventario y kardex',
            'texto' => 'Control de stock y movimientos de productos.',
            'publicado' => false,
            'nota' => 'Pendiente de validar contra el sistema real antes de publicarlo.',
        ],
        [
            'icono' => 'carrito',
            'titulo' => 'Compras y proveedores',
            'texto' => 'Registro de compras y control de proveedores.',
            'publicado' => false,
            'nota' => 'Pendiente de validar contra el sistema real antes de publicarlo.',
        ],
        [
            'icono' => 'punto-venta',
            'titulo' => 'Punto de venta',
            'texto' => 'Venta directa en mostrador.',
            'publicado' => false,
            'nota' => 'Pendiente de validar contra el sistema real antes de publicarlo.',
        ],
        [
            'icono' => 'cotizacion',
            'titulo' => 'Cotizaciones y guías de remisión',
            'texto' => 'Documentos previos y de traslado.',
            'publicado' => false,
            'nota' => 'Pendiente de validar contra el sistema real antes de publicarlo.',
        ],
        [
            'icono' => 'rentabilidad',
            'titulo' => 'Información de rentabilidad',
            'texto' => 'Indicadores de margen por producto o periodo.',
            'publicado' => false,
            'nota' => 'Pendiente de validar contra el sistema real antes de publicarlo.',
        ],
    ],

    /*
    | Beneficios. Sin cifras, porcentajes, testimonios ni casos de éxito.
    */
    'beneficios' => [
        [
            'icono' => 'orden',
            'titulo' => 'La información del negocio, en orden',
            'texto' => 'Los comprobantes, clientes y ventas dejan de vivir en carpetas y hojas sueltas: quedan dentro del sistema y en un mismo lugar.',
        ],
        [
            'icono' => 'flujo',
            'titulo' => 'Menos pasos para facturar',
            'texto' => 'Emitir un comprobante deja de ser un trámite aparte: se hace dentro del mismo flujo con el que registras la venta.',
        ],
        [
            'icono' => 'reportes',
            'titulo' => 'Información útil para decidir',
            'texto' => 'Los reportes muestran qué se vendió y cuándo, para revisar el periodo con datos en lugar de estimaciones.',
        ],
        [
            'icono' => 'central',
            'titulo' => 'Procesos centralizados',
            'texto' => 'Facturación, ventas y reportes comparten la misma base, así el área administrativa no rehace trabajo.',
        ],
        [
            'icono' => 'visibilidad',
            'titulo' => 'Visibilidad de la operación',
            'texto' => 'Sabes en qué estado está cada comprobante y qué quedó pendiente, sin tener que preguntarlo.',
        ],
    ],

    /*
    | Planes y precios.
    |--------------------------------------------------------------------------
    | TARIFAS DE REFERENCIA, SIN PUBLICAR. Los importes de abajo son los que
    | ARACODE maneja hoy como referencia de KIRAFACT, pero todavía no están
    | confirmados ni su vigencia ni las prestaciones exactas de cada plan.
    |
    | La página de planes existe, pero con 'publicar' => false solo explica que
    | la tarifa se confirma con el equipo comercial y ofrece el contacto real.
    | Para publicar las tarjetas: revisa importes y prestaciones, y cambia
    | 'publicar' a true.
    */
    'planes' => [
        'publicar' => false,
        'nota' => 'Tarifas de referencia pendientes de confirmación de vigencia y de las prestaciones incluidas en cada plan.',
        'nota_publicada' => 'Precios en soles. Escríbenos para confirmar disponibilidad y condiciones vigentes.',
        'titulo_pendiente' => 'Planes de KIRAFACT',
        'texto_pendiente' => 'Las tarifas de KIRAFACT se confirman con el equipo comercial según el uso que le dará tu empresa. Escríbenos y te enviamos la propuesta vigente.',
        'lista' => [
            [
                'nombre' => 'Inicio',
                'mensual' => 'S/ 39',
                'anual' => 'S/ 390',
                'resumen' => 'Para empezar a facturar electrónicamente.',
                'incluye' => [
                    'Emisión de comprobantes electrónicos',
                    'Envío a SUNAT',
                    'Generación de XML y PDF',
                    'Reportes de ventas',
                ],
            ],
            [
                'nombre' => 'Profesional',
                'mensual' => 'S/ 69',
                'anual' => null,
                'resumen' => 'Para negocios con más movimiento diario.',
                'incluye' => [
                    'Todo lo del plan Inicio',
                    'Más usuarios de trabajo',
                    'Reportes ampliados',
                ],
            ],
            [
                'nombre' => 'Empresarial',
                'mensual' => 'S/ 120',
                'anual' => null,
                'resumen' => 'Para operaciones con mayor volumen.',
                'incluye' => [
                    'Todo lo del plan Profesional',
                    'Acompañamiento en la puesta en marcha',
                ],
            ],
        ],
    ],

    /*
    | Preguntas frecuentes.
    |--------------------------------------------------------------------------
    | Solo se publican las que se pueden responder con información confirmada.
    | Si una respuesta deja de ser cierta, se edita aquí o se quita la pregunta.
    | 'publicado' => false la oculta sin borrarla.
    */
    'faqs' => [
        [
            'pregunta' => '¿Qué es KIRAFACT?',
            'respuesta' => 'Es un software de facturación electrónica y gestión empresarial desarrollado por ARACODE Smart Solutions. Permite emitir comprobantes electrónicos y consultar la información de la operación desde un mismo sistema.',
            'publicado' => true,
        ],
        [
            'pregunta' => '¿Para qué tipo de negocio está pensado?',
            'respuesta' => 'Está pensado para empresas que necesitan emitir comprobantes electrónicos y ordenar su gestión comercial. Cuéntanos cómo factura tu negocio y lo revisamos contigo.',
            'publicado' => true,
        ],
        [
            'pregunta' => '¿Qué funcionalidades incluye?',
            'respuesta' => 'Emisión de facturas, boletas y notas electrónicas; envío y validación con SUNAT con registro del estado de cada comprobante; generación de XML y PDF; reportes de ventas; y consulta del histórico. El resto de módulos se revisa contigo según tu operación.',
            'publicado' => true,
        ],
        [
            'pregunta' => '¿Cómo funciona la facturación electrónica en el sistema?',
            'respuesta' => 'Registras la venta en el sistema, KIRAFACT genera el XML y el PDF del comprobante y lo envía a SUNAT. El estado del envío queda registrado, y si hay un rechazo puedes ver el motivo para corregirlo y volver a enviarlo.',
            'publicado' => true,
        ],
        [
            'pregunta' => '¿Cómo se relaciona con SUNAT?',
            'respuesta' => 'KIRAFACT se encarga del envío de los comprobantes a SUNAT y de mostrarte el estado de cada uno dentro del sistema, incluidos los rechazos. Si necesitas el detalle del proceso para tu tipo de empresa, escríbenos y lo revisamos contigo.',
            'publicado' => true,
        ],
        [
            'pregunta' => '¿Cómo puedo solicitar una demo?',
            'respuesta' => 'Con el botón «Solicitar una demo», que abre WhatsApp con el mensaje ya escrito. Si prefieres el correo, escríbenos a contacto@aracodeperu.com y coordinamos día y hora para mostrarte el sistema.',
            'publicado' => true,
        ],
        [
            'pregunta' => '¿Cómo puedo solicitar información comercial?',
            'respuesta' => 'Escríbenos por WhatsApp o al correo comercial. Cuéntanos cómo factura hoy tu empresa y coordinamos una conversación sobre tu caso.',
            'publicado' => true,
        ],
    ],
];
