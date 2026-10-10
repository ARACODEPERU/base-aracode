<?php

/*
|--------------------------------------------------------------------------
| KAPTA LMS — contenido comercial del sitio web
|--------------------------------------------------------------------------
|
| Aquí vive todo lo que cambia por decisión de negocio y no debe estar
| repartido por las vistas: la tabla de planes, los datos de contacto y los
| recursos que todavía no existen.
|
| IMPORTANTE sobre los precios: los importes y las prestaciones de abajo son
| los que ARACODE Smart Solutions publica hoy en su página del producto
| (ARACODE › LMS › planes). No se han inventado ni se han estimado. Si la
| tabla aprobada cambia, se edita SOLO este archivo y el sitio entero queda
| coherente. Recuerda volver a ejecutar `php artisan config:clear` (o
| `config:cache` en producción) después de editarlo.
|
*/

return [

    /*
    | Logotipo oficial.
    |--------------------------------------------------------------------------
    | El sitio NO incluye ninguna versión dibujada, recolorida ni reinterpretada
    | del logotipo: mientras no exista el archivo oficial, la cabecera y el pie
    | muestran un hueco reservado y claramente identificable.
    |
    | Para publicar el logotipo real, deja los archivos en
    | public/themes/kapta/images/ y escribe aquí su ruta relativa a public/:
    |
    |   'logo'      => 'themes/kapta/images/kapta-logo.svg',   // para fondos claros
    |   'logo_dark' => 'themes/kapta/images/kapta-logo-dark.svg', // para fondos azul marino
    |
    | Si solo hay una versión, usa la misma ruta en las dos claves.
    | 'alto' es la altura en píxeles; el ancho se calcula solo para no deformar.
    */
    'logo' => null,
    'logo_dark' => null,
    'logo_alto' => 40,

    /*
    | Enlace al campus virtual.
    |--------------------------------------------------------------------------
    | Se muestra en la cabecera y en el pie SOLO cuando tenga una URL real.
    | Mientras esté en null, el enlace no se renderiza (no se inventa).
    */
    'campus_url' => null,

    /*
    | Páginas legales propias del sitio.
    |--------------------------------------------------------------------------
    | El pie solo enlaza las que existan. Si el sitio todavía no tiene sus
    | propias páginas de privacidad o términos, se dejan en null.
    */
    'legal' => [
        'privacidad' => null,
        'terminos' => null,
    ],

    /*
    | Datos de contacto comercial (los reales de ARACODE Smart Solutions).
    */
    'contacto' => [
        'whatsapp' => '+51 917 295 856',
        'whatsapp_url' => 'https://wa.me/51917295856',
        'email' => 'contacto@aracodeperu.com',
        'ciudad' => 'Nuevo Chimbote, Perú',
        'web' => 'https://aracodeperu.com',
    ],

    /*
    | Mensajes de la web. Se centralizan para no repetirlos en las vistas.
    */
    'mensajes' => [
        'demo_whatsapp' => 'Hola, quiero una demostración de KAPTA LMS.',
        'info_whatsapp' => 'Hola, quiero información sobre KAPTA LMS.',
        'nota_precios' => 'Precios en soles publicados por ARACODE Smart Solutions. Escríbenos para confirmar disponibilidad y condiciones vigentes.',
    ],

    /*
    | Agrupaciones comerciales de los planes.
    */
    'niveles' => [
        'esenciales' => [
            'nombre' => 'Planes esenciales',
            'resumen' => 'Para instituciones pequeñas que inician su transformación digital.',
        ],
        'profesionales' => [
            'nombre' => 'Planes profesionales',
            'resumen' => 'Para instituciones en crecimiento que necesitan vender cursos y automatizar procesos.',
        ],
        'avanzados' => [
            'nombre' => 'Planes avanzados',
            'resumen' => 'Para instituciones grandes que necesitan escalabilidad total.',
        ],
    ],

    /*
    | Filas de la tabla comparativa: clave interna => etiqueta visible.
    | El orden de este arreglo es el orden de las filas.
    */
    'comparativa' => [
        'alumnos' => 'Alumnos activos',
        'usuarios' => 'Usuarios administrativos',
        'correos' => 'Correos corporativos',
        'almacenamiento' => 'Almacenamiento',
        'web' => 'Página web incluida',
        'tienda' => 'Tienda online de cursos',
        'pagos' => 'Pasarela de pagos',
        'certificados' => 'Certificados',
        'matricula' => 'Matrícula',
        'facturacion' => 'Facturación electrónica',
        'dominio' => 'Dominio, SSL y hosting',
        'actualizaciones' => 'Actualizaciones progresivas',
        'mantenimiento' => 'Mantenimiento',
    ],

    /*
    | Tabla de planes.
    |--------------------------------------------------------------------------
    | 'destacado' solo resalta visualmente una tarjeta; no implica condiciones
    | distintas a las publicadas.
    */
    'planes' => [
        [
            'slug' => 'inicial',
            'nivel' => 'esenciales',
            'nombre' => 'Inicial',
            'resumen' => 'La entrada al campus virtual con la gestión académica básica.',
            'capacidad' => 'Hasta 150 alumnos activos',
            'mensual' => 'S/ 149',
            'anual' => 'S/ 1,490',
            'destacado' => false,
            'whatsapp' => 'https://wa.link/livatn',
            'incluye' => [
                'Dominio + SSL + hosting',
                'Landing page',
                'Campus virtual: cursos, docentes, alumnos y matrículas',
                'Almacenamiento de 2 GB + enlaces de Google Drive',
                '1 usuario administrativo',
                'Hasta 150 alumnos activos',
                '1 correo corporativo',
                'Mantenimiento del sistema',
            ],
            'ficha' => [
                'alumnos' => 'Hasta 150',
                'usuarios' => '1',
                'correos' => '1',
                'almacenamiento' => '2 GB + Google Drive',
                'web' => 'Landing page',
                'tienda' => 'No incluida',
                'pagos' => 'No incluida',
                'certificados' => 'No incluidos',
                'matricula' => 'Manual en el campus',
                'facturacion' => 'No incluida',
                'dominio' => 'Incluido',
                'actualizaciones' => 'No incluidas',
                'mantenimiento' => 'Sistema',
            ],
        ],
        [
            'slug' => 'emprendedor',
            'nivel' => 'esenciales',
            'nombre' => 'Emprendedor',
            'resumen' => 'Suma página web propia y certificados en PDF para empezar a matricular.',
            'capacidad' => 'Hasta 250 alumnos activos',
            'mensual' => 'S/ 229',
            'anual' => 'S/ 2,290',
            'destacado' => false,
            'whatsapp' => 'https://wa.link/0ifidk',
            'incluye' => [
                'Dominio + SSL + hosting',
                'Página web estándar: Inicio, Nosotros, Cursos y Contacto',
                'CMS para gestionar los contenidos de la web',
                'Campus virtual: cursos, docentes, alumnos, matrículas y certificados en PDF',
                'Almacenamiento de 3 GB + enlaces de Google Drive',
                '2 usuarios administrativos',
                'Hasta 250 alumnos activos',
                '1 correo corporativo',
                'Mantenimiento del sistema y de la web',
            ],
            'ficha' => [
                'alumnos' => 'Hasta 250',
                'usuarios' => '2',
                'correos' => '1',
                'almacenamiento' => '3 GB + Google Drive',
                'web' => 'Estándar',
                'tienda' => 'No incluida',
                'pagos' => 'No incluida',
                'certificados' => 'Subidos en PDF',
                'matricula' => 'Manual en el campus',
                'facturacion' => 'No incluida',
                'dominio' => 'Incluido',
                'actualizaciones' => 'No incluidas',
                'mantenimiento' => 'Sistema y web',
            ],
        ],
        [
            'slug' => 'profesional',
            'nivel' => 'profesionales',
            'nombre' => 'Profesional',
            'resumen' => 'Abre la tienda online, la pasarela de pagos y la matrícula automática.',
            'capacidad' => 'Hasta 400 alumnos activos',
            'mensual' => 'S/ 329',
            'anual' => 'S/ 3,290',
            'destacado' => true,
            'whatsapp' => 'https://wa.link/pwjmp7',
            'incluye' => [
                'Dominio + SSL + hosting',
                'Página web estándar: Inicio, Nosotros, Cursos, Blog y Contacto',
                'Carrito de compras y tienda online para los cursos',
                'Pasarela de pagos Mercado Pago (Perú)',
                'CMS para gestionar los contenidos de la web',
                'Sistema de ventas en línea de los cursos',
                'Campus virtual: cursos, convenios con instituciones, docentes, alumnos, matrículas y certificados automáticos',
                'Matrícula automática al comprar en la tienda y manual a través del campus',
                'Almacenamiento de 5 GB + enlaces de Google Drive',
                '4 usuarios administrativos',
                'Hasta 400 alumnos activos',
                '3 correos corporativos',
                'Mantenimiento del sistema y de la web',
            ],
            'ficha' => [
                'alumnos' => 'Hasta 400',
                'usuarios' => '4',
                'correos' => '3',
                'almacenamiento' => '5 GB + Google Drive',
                'web' => 'Estándar',
                'tienda' => 'Incluida',
                'pagos' => 'Mercado Pago (Perú)',
                'certificados' => 'Automáticos',
                'matricula' => 'Automática y manual',
                'facturacion' => 'No incluida',
                'dominio' => 'Incluido',
                'actualizaciones' => 'No incluidas',
                'mantenimiento' => 'Sistema y web',
            ],
        ],
        [
            'slug' => 'academico',
            'nivel' => 'profesionales',
            'nombre' => 'Académico',
            'resumen' => 'Web personalizada, más capacidad y actualizaciones progresivas.',
            'capacidad' => 'Hasta 500 alumnos activos',
            'mensual' => 'S/ 449',
            'anual' => 'S/ 4,490',
            'destacado' => false,
            'whatsapp' => 'https://wa.link/o2wzls',
            'incluye' => [
                'Dominio + SSL + hosting',
                'Página web personalizada: Inicio, Nosotros, Cursos, Servicios, Blog y Contacto',
                'Carrito de compras y tienda online para los cursos',
                'Pasarela de pagos Mercado Pago (Perú) y/o PayPal',
                'CMS para gestionar los contenidos de la web',
                'Campus virtual: cursos, convenios con instituciones, docentes, alumnos, matrículas y certificados automáticos',
                'Matrícula automática al comprar en la tienda y manual a través del campus',
                'Almacenamiento de 8 GB + enlaces de Google Drive',
                '6 usuarios administrativos',
                'Hasta 500 alumnos activos',
                '4 correos corporativos',
                'Mantenimiento del sistema y de la web',
                'Actualizaciones progresivas',
            ],
            'ficha' => [
                'alumnos' => 'Hasta 500',
                'usuarios' => '6',
                'correos' => '4',
                'almacenamiento' => '8 GB + Google Drive',
                'web' => 'Personalizada',
                'tienda' => 'Incluida',
                'pagos' => 'Mercado Pago y/o PayPal',
                'certificados' => 'Automáticos',
                'matricula' => 'Automática y manual',
                'facturacion' => 'No incluida',
                'dominio' => 'Incluido',
                'actualizaciones' => 'Incluidas',
                'mantenimiento' => 'Sistema y web',
            ],
        ],
        [
            'slug' => 'empresarial',
            'nivel' => 'avanzados',
            'nombre' => 'Empresarial',
            'resumen' => 'Blog, más almacenamiento y más alumnos activos simultáneos.',
            'capacidad' => 'Hasta 800 alumnos activos',
            'mensual' => 'S/ 599',
            'anual' => 'S/ 5,990',
            'destacado' => false,
            'whatsapp' => 'https://wa.link/1lubng',
            'incluye' => [
                'Dominio + SSL + hosting',
                'Página web personalizada: Inicio, Nosotros, Cursos, Servicios, Blog y Contacto',
                'Carrito de compras y tienda online para los cursos',
                'Pasarela de pagos Mercado Pago (Perú) y/o PayPal',
                'CMS para gestionar los contenidos de la web',
                'Sistema de ventas en línea de los cursos',
                'Sistema de blog para los artículos publicados en la web',
                'Campus virtual: cursos, convenios con instituciones, docentes, alumnos, matrículas y certificados automáticos',
                'Matrícula automática al comprar en la tienda y manual a través del campus',
                'Almacenamiento de 12 GB + enlaces de Google Drive',
                '8 usuarios administrativos',
                'Hasta 800 alumnos activos',
                '5 correos corporativos',
                'Mantenimiento del sistema y de la web',
                'Actualizaciones progresivas',
            ],
            'ficha' => [
                'alumnos' => 'Hasta 800',
                'usuarios' => '8',
                'correos' => '5',
                'almacenamiento' => '12 GB + Google Drive',
                'web' => 'Personalizada',
                'tienda' => 'Incluida',
                'pagos' => 'Mercado Pago y/o PayPal',
                'certificados' => 'Automáticos',
                'matricula' => 'Automática y manual',
                'facturacion' => 'No incluida',
                'dominio' => 'Incluido',
                'actualizaciones' => 'Incluidas',
                'mantenimiento' => 'Sistema y web',
            ],
        ],
        [
            'slug' => 'corporativo',
            'nivel' => 'avanzados',
            'nombre' => 'Corporativo',
            'resumen' => 'El plan más completo: facturación electrónica y alumnos ilimitados.',
            'capacidad' => 'Alumnos activos ilimitados',
            'mensual' => 'S/ 799',
            'anual' => 'S/ 7,990',
            'destacado' => false,
            'whatsapp' => 'https://wa.link/7c6phm',
            'incluye' => [
                'Dominio + SSL + hosting',
                'Facturación electrónica',
                'Página web personalizada: Inicio, Nosotros, Cursos, Servicios, Blog y Contacto',
                'Carrito de compras y tienda online para los cursos',
                'Pasarela de pagos Mercado Pago (Perú) y/o PayPal',
                'CMS para gestionar los contenidos de la web',
                'Sistema de ventas en línea de los cursos',
                'Sistema de blog para los artículos publicados en la web',
                'Campus virtual: cursos, convenios con instituciones, docentes, alumnos, matrículas y certificados automáticos',
                'Matrícula automática al comprar en la tienda y manual a través del campus',
                'Almacenamiento de 12 GB + enlaces de Google Drive',
                '12 usuarios administrativos',
                'Alumnos activos ilimitados',
                '8 correos corporativos',
                'Mantenimiento del sistema y de la web',
                'Actualizaciones progresivas',
            ],
            'ficha' => [
                'alumnos' => 'Ilimitados',
                'usuarios' => '12',
                'correos' => '8',
                'almacenamiento' => '12 GB + Google Drive',
                'web' => 'Personalizada',
                'tienda' => 'Incluida',
                'pagos' => 'Mercado Pago y/o PayPal',
                'certificados' => 'Automáticos',
                'matricula' => 'Automática y manual',
                'facturacion' => 'Incluida',
                'dominio' => 'Incluido',
                'actualizaciones' => 'Incluidas',
                'mantenimiento' => 'Sistema y web',
            ],
        ],
    ],
];
