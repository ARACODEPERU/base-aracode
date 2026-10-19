<?php

/*
|--------------------------------------------------------------------------
| Banco de Plantillas Web — catálogo
|--------------------------------------------------------------------------
|
| Fuente única del catálogo. Lo lee App\Support\CatalogoPlantillas, que es lo
| único que consume la sección: las vistas nunca leen este archivo directo.
| Ver docs/BANCO_PLANTILLAS_FASE1.md (plan) y docs/BANCO_PLANTILLAS.md
| (convención de las demostraciones).
|
| Categorías
| ----------
| La clave es la que viaja en la URL (`/plantillas?categoria=salud`). Cambiar
| una clave rompe los enlaces guardados; para renombrar se cambia solo el
| `nombre`.
|
| Tipos (formato)
| ---------------
| Es la primera pregunta de una venta: ¿una landing de una sola página o un sitio
| web de varias? Se muestran con su hashtag en la tarjeta para que el visitante
| reconozca de un vistazo lo que va con su plan.
|
|  - `nombre`       Cómo se llama el formato ante el cliente («Landing»).
|  - `hashtag`      Etiqueta corta que se pinta en la tarjeta (`#Landing`).
|  - `descripcion`  Una frase: qué significa ese formato y en qué se nota.
|                   Explica la etiqueta en la ficha y en el tooltip de la tarjeta.
|
| Plantillas
| ----------
| Cada plantilla declara:
|
|  - `nombre`       Nombre comercial que se ve en la tarjeta y en la ficha.
|  - `categoria`    Clave de una de las categorías de arriba.
|  - `tipo`         Clave de uno de los `tipos`: el formato del proyecto (landing o
|                   sitio web). Es lo que decide el plan que se contrata, así que se
|                   muestra como etiqueta en la tarjeta, en la ficha y en el
|                   buscador (`?q=landing` lista las landings).
|  - `etiquetas`    Palabras del buscador además del nombre y el resumen
|                   (`?q=`); también alimentan el JSON-LD de la ficha.
|  - `resumen`      Una frase: es el texto de la tarjeta y la descripción de la
|                   ficha. Si la tarjeta crece, esto es lo primero que se corta.
|  - `incluye`      Puntos de la ficha (qué trae la plantilla).
|  - `tecnologias`  HTML/CSS/JS y librerías que usa la demostración.
|  - `imagen`       Ruta relativa del archivo en `public/` o URL absoluta. El
|                   día que exista la captura real se escribe aquí: se preparan
|                   en 16/10 (1600 × 1000 px) y viven en
|                   `themes/plantillas/images/<slug>.jpg`. Mientras sea `null`
|                   la tarjeta muestra un mosaico de color que avisa «Captura
|                   en preparación» —el nombre se lee igual en el cuerpo de la
|                   tarjeta—, nunca una imagen rota.
|  - `demo`         Carpeta de la demostración dentro de `public/demos/`
|                   (`'clinica-dental'` → `/demos/clinica-dental/`) o URL
|                   absoluta el día que la demo viva en su propio subdominio
|                   (`'https://clinica-dental.aracodeperu.com'`). Si es `null`
|                   la ficha avisa de que la demostración está en preparación.
|  - `destacada`    Aparece en el bloque de destacadas del catálogo.
|  - `publicada`    `false` la esconde del catálogo y de su ficha (404), sin
|                   borrar sus datos.
|  - `actualizada`  Fecha (Y-m-d) que se muestra en la ficha y que conviene
|                   copiar al sitemap cuando cambie el contenido.
|
| Recordatorio: ejecutar `php artisan config:clear` (o `config:cache` en
| producción) después de editar este archivo.
|
*/

return [

    'tipos' => [

        'landing' => [
            'nombre' => 'Landing',
            'hashtag' => '#Landing',
            'descripcion' => 'Una sola página con todos los contenidos en secciones y enlaces internos.',
        ],

        'sitio' => [
            'nombre' => 'Sitio web',
            'hashtag' => '#SitioWeb',
            'descripcion' => 'Varias páginas enlazadas (inicio, servicios, fichas o listados) con menú de navegación.',
        ],

        'tienda' => [
            'nombre' => 'Tienda en línea',
            'hashtag' => '#Tienda',
            'descripcion' => 'Catálogo con filtros, ficha de producto, canasta que recuerda lo elegido y pedido por WhatsApp.',
        ],

    ],

    'categorias' => [

        'salud' => [
            'nombre' => 'Salud y bienestar',
            'descripcion' => 'Consultorios, clínicas y centros que necesitan explicar sus servicios y recibir citas.',
        ],

        'gastronomia' => [
            'nombre' => 'Gastronomía',
            'descripcion' => 'Restaurantes, cafés y cartas digitales pensadas para el celular.',
        ],

        'servicios' => [
            'nombre' => 'Servicios profesionales',
            'descripcion' => 'Estudios, consultoras y oficios que venden confianza antes que precio.',
        ],

        'educacion' => [
            'nombre' => 'Educación',
            'descripcion' => 'Academias, institutos y cursos con matrícula en línea.',
        ],

        'inmobiliaria' => [
            'nombre' => 'Inmobiliaria',
            'descripcion' => 'Venta y alquiler de propiedades con buscador y fichas de inmueble.',
        ],

        'portafolio' => [
            'nombre' => 'Portafolios y creativos',
            'descripcion' => 'Estudios de diseño, fotografía y profesionales que muestran su trabajo.',
        ],

        'comercio' => [
            'nombre' => 'Comercio y tiendas',
            'descripcion' => 'Tiendas y marcas que venden productos: catálogo, precios, canasta y pedido en línea.',
        ],

    ],

    'plantillas' => [

        'clinica-dental' => [
            'nombre' => 'Clínica Dental',
            'categoria' => 'salud',
            'tipo' => 'landing',
            'etiquetas' => ['odontología', 'citas', 'tratamientos', 'landing'],
            'resumen' => 'Landing para consultorio odontológico: tratamientos, equipo, horarios y reserva de cita.',
            'incluye' => [
                'Hero a pantalla completa con slider de tres fotos y llamada a reservar',
                'Modo claro y oscuro con interruptor en la cabecera',
                'Seis tratamientos con su descripción',
                'Bloque de confianza: años, pacientes y especialidades',
                'Horarios de atención y datos de contacto',
                'Menú móvil y desplazamiento suave entre secciones',
            ],
            'tecnologias' => ['HTML5', 'CSS3', 'JavaScript'],
            'imagen' => 'themes/plantillas/images/clinica-dental.jpg',
            'demo' => 'clinica-dental',
            'destacada' => true,
            'publicada' => true,
            'actualizada' => '2026-10-11',
        ],

        'restaurante-carta' => [
            'nombre' => 'Restaurante y carta digital',
            'categoria' => 'gastronomia',
            'tipo' => 'landing',
            'etiquetas' => ['restaurante', 'carta', 'menú', 'reservas'],
            'resumen' => 'Carta digital con platos destacados, secciones por tipo de comida y reserva por WhatsApp.',
            'incluye' => [
                'Hero a pantalla completa con slider de tres fotos: plato estrella, parrilla y comedor',
                'Modo claro y oscuro con interruptor en la cabecera',
                'Carta dividida en entradas, fondos y parrilla, postres y bebidas, con precios de referencia',
                'Plato estrella con foto, precio y pedido directo por WhatsApp',
                'Tres promociones del día entre semana',
                'Historia de la cocina con el retrato del chef y detalles de los platos',
                'Opiniones de comensales con valoración',
                'Reserva de mesa con validación en el navegador, ubicación con plano y enlace a Google Maps',
                'Preguntas frecuentes y menú móvil',
            ],
            'tecnologias' => ['HTML5', 'CSS3', 'JavaScript'],
            'imagen' => 'themes/plantillas/images/restaurante-carta.jpg',
            'demo' => 'restaurante-carta',
            'destacada' => true,
            'publicada' => true,
            'actualizada' => '2026-10-11',
        ],

        'estudio-contable' => [
            'nombre' => 'Estudio Contable',
            'categoria' => 'servicios',
            'tipo' => 'sitio',
            'etiquetas' => ['contabilidad', 'impuestos', 'asesoría', 'estudio'],
            'resumen' => 'Sitio para estudio contable: servicios tributarios, planes mensuales y contacto directo.',
            'incluye' => [
                'Diseño «expediente contable»: papel y tinta, titulares en serif, filetes en vez de tarjetas y secciones con margen numerado',
                'Hero a pantalla completa con slider de tres fotos, cifras de confianza y controles con contador de foto',
                'Modo claro y oscuro con interruptor, recordado entre las páginas del sitio',
                'Catálogo de ocho servicios en forma de libro de entradas, con precios, plazos y filtro por área, atención y texto',
                'Ficha de servicio con alcance, entregables, plazos y preguntas propias',
                'Tres planes mensuales en hoja de precios y en tabla comparativa punto por punto',
                'Equipo, testimonios con valoración y preguntas frecuentes',
                'Formulario de consulta con validación en el navegador',
                'Ubicación con plano y enlace a Google Maps',
            ],
            'tecnologias' => ['HTML5', 'CSS3', 'JavaScript'],
            'imagen' => 'themes/plantillas/images/estudio-contable.jpg',
            'demo' => 'estudio-contable',
            'destacada' => true,
            'publicada' => true,
            'actualizada' => '2026-10-11',
        ],

        'academia-cursos' => [
            'nombre' => 'Academia de Cursos',
            'categoria' => 'educacion',
            'tipo' => 'sitio',
            'etiquetas' => ['academia', 'cursos', 'matrícula', 'certificado'],
            'resumen' => 'Sitio de academia con catálogo de cursos, temario por módulos, docentes, certificación y matrícula en línea.',
            'incluye' => [
                'Hero a pantalla completa con slider de tres fotos, áreas y cifras de confianza',
                'Modo claro y oscuro con interruptor, recordado entre las páginas del sitio',
                'Catálogo de ocho cursos con precios, duración y fechas de inicio',
                'Filtros por área, modalidad y búsqueda por texto',
                'Ficha de curso con temario por módulos en acordeones',
                'Docentes, certificación con código de verificación y testimonios',
                'Formulario de matrícula con validación en el navegador',
                'Formas de pago, ubicación con plano y enlace a Google Maps',
                'Preguntas frecuentes repartidas en las páginas del sitio',
            ],
            'tecnologias' => ['HTML5', 'CSS3', 'JavaScript'],
            'imagen' => 'themes/plantillas/images/academia-cursos.jpg',
            'demo' => 'academia-cursos',
            'destacada' => true,
            'publicada' => true,
            'actualizada' => '2026-10-11',
        ],

        'inmobiliaria-propiedades' => [
            'nombre' => 'Inmobiliaria',
            'categoria' => 'inmobiliaria',
            'tipo' => 'sitio',
            'etiquetas' => ['propiedades', 'alquiler', 'venta', 'departamentos'],
            'resumen' => 'Buscador de propiedades con filtros por operación, distrito, tipo, dormitorios y precio, fichas de inmueble con plano y simulador de cuota, y página para vender.',
            'incluye' => [
                'Buscador dentro de la portada con pestañas de compra y alquiler',
                'Listado con filtros, orden y favoritos guardados en el navegador',
                'Ficha de inmueble con galería, datos técnicos, plano con medidas y simulador de cuota',
                'Página para vender con valoración, proceso y tarifas de referencia',
                'Contacto del asesor asignado y visitas con cita',
            ],
            'tecnologias' => ['HTML5', 'CSS3', 'JavaScript'],
            'imagen' => 'themes/plantillas/images/inmobiliaria-propiedades.jpg',
            'demo' => 'inmobiliaria-propiedades',
            'destacada' => true,
            'publicada' => true,
            'actualizada' => '2026-10-11',
        ],

        'portafolio-creativo' => [
            'nombre' => 'Portafolio Creativo',
            'categoria' => 'portafolio',
            'tipo' => 'landing',
            'etiquetas' => ['portafolio', 'diseño', 'proyectos', 'estudio'],
            'resumen' => 'Portafolio para estudio de diseño: proyectos, servicios y contacto, con foco en las piezas.',
            'incluye' => [
                'Diseño «pliego de taller»: retícula de doce columnas dibujada como filetes, titulares en sans mayúscula y cuerpo en serif con un solo acento magenta',
                'Hero a pantalla completa con slider de tres fotos y cinta de servicios en movimiento',
                'Modo claro y oscuro con interruptor en la cabecera',
                'Índice de seis proyectos filtrable por tipo, con la pieza al pasar el ratón',
                'Tarifas de referencia en tabla de trabajos, con presupuesto por WhatsApp en cada fila',
                'Tres planes con tabla comparativa punto por punto',
                'Bloque del estudio con la cita del equipo y dos bandas del taller',
                'Tres opiniones con valoración, ubicación con plano propio y preguntas frecuentes',
                'Formulario de encargo con validación en el navegador',
            ],
            'tecnologias' => ['HTML5', 'CSS3', 'JavaScript'],
            'imagen' => 'themes/plantillas/images/portafolio-creativo.jpg',
            'demo' => 'portafolio-creativo',
            'destacada' => true,
            'publicada' => true,
            'actualizada' => '2026-10-12',
        ],

        'tienda-online' => [
            'nombre' => 'Tienda en línea',
            'categoria' => 'comercio',
            'tipo' => 'tienda',
            'etiquetas' => ['tienda', 'catálogo', 'carrito', 'canasta', 'productos', 'envíos'],
            'resumen' => 'Tienda de productos para el hogar: catálogo con filtros por ambiente, ficha con variantes, canasta que recuerda lo elegido y pedido por WhatsApp.',
            'incluye' => [
                'Diseño «tienda de casa moderna»: lienzo casi blanco con un solo acento naranja teja, precio en el texto —no una pegatina—, descuentos en porcentaje sobre la foto y botón de añadir sólido',
                'Hero a pantalla completa con slider de tres fotos, buscador de productos dentro de la portada y cinta de promesas en movimiento',
                'Modo claro y oscuro con interruptor en la cabecera, recordado entre las páginas de la tienda',
                'Catálogo de doce productos con filtros por ambiente y precio máximo, orden por precio o nombre y el estado del filtro en la URL',
                'Ficha de producto con galería de tiras, variantes de color y tamaño, cantidad y productos relacionados',
                'Canasta que recuerda lo elegido entre páginas, con cantidades, barra de envío gratis y pedido por WhatsApp con su detalle y su total',
                'Envíos, cambios y pagos de referencia, y preguntas frecuentes',
                'Tres opiniones con valoración, el equipo de la tienda y ubicación con plano propio y enlace a Google Maps',
                'Pedido con validación campo por campo en el navegador y recibo que se copia tal cual al WhatsApp',
            ],
            'tecnologias' => ['HTML5', 'CSS3', 'JavaScript'],
            'imagen' => 'themes/plantillas/images/tienda-online.jpg',
            'demo' => 'tienda-online',
            'destacada' => true,
            'publicada' => true,
            'actualizada' => '2026-10-12',
        ],

        // Supermercado en línea: la segunda tienda del banco. Vive en el mismo
        // formato (`tienda`) y la misma categoría (`comercio`) que Tienda en
        // línea, pero con identidad, estructura y demostración propias: pasillos
        // de supermercado, ofertas con precio anterior y canasta con simulación
        // de pedido. La subcategoría «Supermercados y minimarkets» del encargo no
        // existe en el catálogo y no se crea una nueva: el catálogo tiene una
        // sola categoría de comercio y las etiquetas de esta plantilla son las
        // que hacen que el buscador la encuentre por «supermercado» o
        // «minimarket».
        'supermercado-online-verdia' => [
            'nombre' => 'Supermercado Online — VERDIA Market',
            'categoria' => 'comercio',
            'tipo' => 'tienda',
            'etiquetas' => ['supermercado', 'minimarket', 'abarrotes', 'frutas y verduras', 'tienda de alimentos', 'ecommerce', 'canasta'],
            'resumen' => 'Tienda de supermercado con buscador de productos, filtros por pasillo y por necesidad, ofertas del día, ficha de producto y canasta que simula el pedido.',
            'incluye' => [
                'Diseño «supermercado de barrio contemporáneo»: marfil de fondo, superficies blancas, verde bosque de la marca para la acción y naranja reservado a las ofertas',
                'Aviso de demostración en la cabecera y en el pie: marca, productos, precios, promociones y contacto ficticios',
                'Hero a pantalla completa con slider de tres fotos, tarjetas de sugerencia y controles con flechas y puntos',
                'Modo claro y oscuro con interruptor, recordado entre las cuatro páginas de la tienda',
                'Buscador de productos con sugerencias mientras se escribe, disponible en la cabecera de todas las páginas',
                'Nueve pasillos (frutas y verduras, lácteos y huevos, carnes y pescados, panadería, abarrotes, bebidas, snacks, limpieza y cuidado personal)',
                'Veintiún productos de demostración con presentación, precio de referencia y precio anterior cuando corresponde',
                'Catálogo con filtros por pasillo y por necesidad, búsqueda por nombre, orden por precio o nombre y el filtro guardado en la URL',
                'Secciones de productos populares, ofertas del día con etiqueta estática, lo más vendido con pestañas que cambian los productos y compra por necesidad',
                'Ficha de producto con precio anterior, ahorro, datos de entrega y productos del mismo pasillo',
                'Canasta guardada en el navegador con cantidades, subtotales, total y simulación de pedido que no cobra ni envía nada',
                'Estados vacíos en el catálogo y en la canasta, boletín con validación de correo y bloque de confianza sin certificados inventados',
            ],
            'tecnologias' => ['HTML5', 'CSS3', 'JavaScript'],
            'imagen' => 'themes/plantillas/images/supermercado-online-verdia.jpg',
            'demo' => 'supermercado-online-verdia',
            'destacada' => false,
            'publicada' => true,
            'actualizada' => '2026-10-13',
        ],

        // Tienda de escaparate amplio: la tercera del formato `tienda` y la
        // segunda de la categoría `comercio`. Comparte formato con Tienda en
        // línea y Supermercado VERDIA, pero ni la identidad ni la arquitectura:
        // cabecera azul noche a sangre, mega panel de categorías con sus
        // colecciones, tarjetas de producto con valoración y precio anterior,
        // marcas ficticias, opiniones de ejemplo, guías de compra con su propia
        // página y canasta lateral en las cinco páginas con pedido simulado.
        // La subcategoría «Tiendas online y comercio electrónico» del encargo no
        // existe en el catálogo y no se crea una nueva: dentro de `comercio`, las
        // etiquetas son las que hacen que el buscador la encuentre por
        // «marketplace», «carrito» o «favoritos».
        'nexora-market' => [
            'nombre' => 'NÉXORA MARKET',
            'categoria' => 'comercio',
            'tipo' => 'tienda',
            'etiquetas' => ['tienda', 'ecommerce', 'marketplace', 'catálogo', 'carrito', 'canasta', 'productos', 'favoritos', 'mega menú'],
            'resumen' => 'Tienda de escaparate amplio: buscador con sugerencias, mega panel de seis categorías, catálogo con filtros, ficha de producto, favoritos y canasta que simula el pedido.',
            'incluye' => [
                'Diseño «escaparate de gran almacén»: azul noche estructural en cabecera, franjas y pie, papel blanco para el producto y un solo naranja de acción, con rejilla ancha y filetes finos en vez de sombras altas',
                'Aviso de demostración en la cabecera y en el pie: marca, productos, precios, promociones, opiniones y datos de contacto ficticios',
                'Hero a pantalla completa con slider de tres fotos, garantías y tarjeta de oferta de la semana',
                'Modo claro y oscuro con interruptor, recordado entre las cinco páginas',
                'Cabecera con buscador de productos con sugerencias mientras se escribe, favoritos y canasta con contador de unidades',
                'Mega panel de categorías con las seis categorías y sus colecciones, más dos ofertas destacadas',
                'Seis categorías y veintisiete productos de demostración, con precio de referencia y precio anterior cuando corresponde',
                'Catálogo con filtros por categoría, colección, solo ofertas, solo favoritos y precio máximo, búsqueda por texto, cuatro órdenes y el filtro guardado en la URL',
                'Ficha de producto con precio anterior, ahorro, disponibilidad, plazo de entrega, tabla de especificaciones y hasta cuatro relacionados de la misma categoría',
                'Canasta lateral en las cinco páginas, con cantidades, subtotal y total, más página de canasta con entrega de ejemplo y pedido simulado',
                'Canasta y favoritos guardados en el navegador, con la cantidad y el estado visible en toda la tienda',
                'Seis marcas ficticias, dos bloques editoriales, tres opiniones de ejemplo y una página de guías de compra con tres artículos',
                'Boletín con validación de correo, ubicación con plano propio, enlace a Google Maps y botón flotante de WhatsApp',
                'Sin pasarela de pago, sin pedidos reales, sin datos personales y sin peticiones a terceros: todo el JavaScript vive en un solo archivo',
            ],
            'tecnologias' => ['HTML5', 'CSS3', 'JavaScript'],
            'imagen' => 'themes/plantillas/images/nexora-market.jpg',
            'demo' => 'nexora-market',
            'destacada' => false,
            'publicada' => true,
            'actualizada' => '2026-10-14',
        ],

    ],

];
