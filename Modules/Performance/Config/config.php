<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Permisos
    |--------------------------------------------------------------------------
    |
    | Coinciden con los que exige Routes/web.php y con los que declara el menú
    | lateral. Se crean y se conceden a los roles de administración en la
    | migración y en el seeder del módulo.
    |
    */

    'permissions' => [
        'performance_images',
        'performance_weight',
        'performance_queue',
    ],

    /*
    |--------------------------------------------------------------------------
    | Inventario
    |--------------------------------------------------------------------------
    |
    | Segundos que se conserva el inventario en caché. El botón «Actualizar
    | inventario» de la pantalla fuerza un escaneo nuevo.
    |
    */

    'cache_seconds' => 600,

    'page_size' => 20,

    /*
    |--------------------------------------------------------------------------
    | Carpetas de imágenes
    |--------------------------------------------------------------------------
    |
    | `tab` es la pestaña por defecto cuando la imagen no se detecta en uso
    | dentro de un curso, el blog o la tienda. Las imágenes referenciadas se
    | colocan en su pestaña real independientemente de la carpeta.
    |
    */

    'directories' => [
        ['path' => public_path('img'), 'url' => '/img', 'tab' => 'paginas'],
        ['path' => public_path('themes'), 'url' => '/themes', 'tab' => 'paginas'],
        ['path' => public_path('images'), 'url' => '/images', 'tab' => 'paginas'],
        ['path' => storage_path('app/public/uploads'), 'url' => '/storage/uploads', 'tab' => 'otros'],
        ['path' => storage_path('app/public/company'), 'url' => '/storage/company', 'tab' => 'otros'],
        ['path' => storage_path('app/public/health'), 'url' => '/storage/health', 'tab' => 'otros'],
        ['path' => storage_path('app/public/students'), 'url' => '/storage/students', 'tab' => 'otros'],
        ['path' => storage_path('app/public/myclients'), 'url' => '/storage/myclients', 'tab' => 'otros'],
        ['path' => storage_path('app/public/negotiations'), 'url' => '/storage/negotiations', 'tab' => 'otros'],
        ['path' => storage_path('app/public/invoice'), 'url' => '/storage/invoice', 'tab' => 'otros'],
    ],

    'extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],

    // Carpetas que nunca se recorren aunque estén dentro de una carpeta escaneada.
    'ignore_directories' => ['node_modules', 'vendor', '.git', 'cache', 'tmp'],

    /*
    |--------------------------------------------------------------------------
    | Pestañas
    |--------------------------------------------------------------------------
    */

    'tabs' => [
        'paginas'    => 'Páginas',
        'cursos'     => 'Cursos',
        'blog'       => 'Blog',
        'productos'  => 'Productos',
        'otros'      => 'Otros',
        'orfanas'    => 'Huérfanas',
        'duplicadas' => 'Repetidas',
        'gif'        => 'GIF animados',
        'cuarentena' => 'Cuarentena',
    ],

    /*
    |--------------------------------------------------------------------------
    | Umbral de «pesada»
    |--------------------------------------------------------------------------
    |
    | Tamaño en KB a partir del cual una imagen se marca como candidata a
    | revisión en el resumen.
    |
    */

    'oversized_kb' => 300,

    /*
    |--------------------------------------------------------------------------
    | Nuevas imágenes y avisos
    |--------------------------------------------------------------------------
    |
    | Las imágenes modificadas en los últimos `new_days` días que superen
    | `oversized_kb` se marcan como nuevas y el comando `performance:audit`
    | crea una alerta para el administrador.
    |
    */

    'new_days' => 7,

    // Días que se conservan las copias de seguridad antes de borrarlas.
    'backup_days' => 30,

    /*
    |--------------------------------------------------------------------------
    | Variantes WebP
    |--------------------------------------------------------------------------
    */

    'webp_widths' => [0, 1280, 800],

    'webp_quality' => 82,

    /*
    |--------------------------------------------------------------------------
    | Peso por página
    |--------------------------------------------------------------------------
    |
    | Presupuesto de peso por página y recursos que se cargan en todas las
    | páginas públicas. Se suman al peso de las imágenes de cada página.
    |
    */

    'page_budget_kb' => 1500,

    'global_assets' => [
        'css/website-public.css',
    ],

    /*
    |--------------------------------------------------------------------------
    | Vistas y su URL pública
    |--------------------------------------------------------------------------
    |
    | Cuando una imagen solo aparece en una vista Blade, se muestra el archivo
    | y, si está en este mapa, la ruta real de la página. Las claves son rutas
    | relativas a resources/views sin la extensión .blade.php.
    |
    */

    'view_urls' => [
        'aracode/pages/home'           => '/',
        'aracode/pages/soluciones'     => '/soluciones',
        'aracode/pages/precios'        => '/precios',
        'aracode/pages/portafolio'     => '/portafolio',
        'aracode/pages/empresa'        => '/empresa',
        'aracode/pages/contacto'       => '/contacto',
        'aracode/pages/faq'            => '/faq',
        'aracode/pages/blog'           => '/blog',
        'aracode/pages/casos-exito'    => '/casos-exito',
        'aracode/pages/equipo'         => '/equipo',
        'aracode/pages/kirafact'       => '/kirafact',
        'aracode/pages/kapta'          => '/kapta',
        'aracode/pages/clinica'        => '/clinica',
        'aracode/pages/gracias'        => '/gracias',
        'aracode/pages/blog-articulo'  => '/blog',
    ],

    'view_directories' => [
        resource_path('views/aracode'),
        resource_path('views'),
    ],
];
