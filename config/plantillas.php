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

    ],

    'plantillas' => [

        'clinica-dental' => [
            'nombre' => 'Clínica Dental',
            'categoria' => 'salud',
            'tipo' => 'landing',
            'etiquetas' => ['odontología', 'citas', 'tratamientos', 'landing'],
            'resumen' => 'Landing para consultorio odontológico: tratamientos, equipo, horarios y reserva de cita.',
            'incluye' => [
                'Portada con el tratamiento principal y llamada a reservar',
                'Seis tratamientos con su descripción',
                'Bloque de confianza: años, pacientes y especialidades',
                'Horarios de atención y datos de contacto',
                'Menú móvil y desplazamiento suave entre secciones',
            ],
            'tecnologias' => ['HTML5', 'CSS3', 'JavaScript'],
            'imagen' => null,
            'demo' => 'clinica-dental',
            'destacada' => true,
            'publicada' => true,
            'actualizada' => '2026-10-10',
        ],

        'restaurante-carta' => [
            'nombre' => 'Restaurante y carta digital',
            'categoria' => 'gastronomia',
            'tipo' => 'landing',
            'etiquetas' => ['restaurante', 'carta', 'menú', 'reservas'],
            'resumen' => 'Carta digital con platos destacados, secciones por tipo de comida y reserva por WhatsApp.',
            'incluye' => [
                'Portada con el plato estrella',
                'Carta dividida en entradas, fondos, postres y bebidas',
                'Bloque de promociones del día',
                'Reserva y pedido por WhatsApp',
            ],
            'tecnologias' => ['HTML5', 'CSS3', 'JavaScript'],
            'imagen' => null,
            'demo' => null,
            'destacada' => false,
            'publicada' => true,
            'actualizada' => '2026-10-10',
        ],

        'estudio-contable' => [
            'nombre' => 'Estudio Contable',
            'categoria' => 'servicios',
            'tipo' => 'sitio',
            'etiquetas' => ['contabilidad', 'impuestos', 'asesoría', 'estudio'],
            'resumen' => 'Sitio para estudio contable: servicios tributarios, planes mensuales y contacto directo.',
            'incluye' => [
                'Servicios tributarios, laborales y contables',
                'Comparativa de planes mensuales',
                'Formulario de contacto con validación',
                'Preguntas frecuentes',
            ],
            'tecnologias' => ['HTML5', 'CSS3', 'JavaScript'],
            'imagen' => null,
            'demo' => null,
            'destacada' => false,
            'publicada' => true,
            'actualizada' => '2026-10-10',
        ],

        'academia-cursos' => [
            'nombre' => 'Academia de Cursos',
            'categoria' => 'educacion',
            'tipo' => 'sitio',
            'etiquetas' => ['academia', 'cursos', 'matrícula', 'certificado'],
            'resumen' => 'Sitio de academia con catálogo de cursos, temario por módulos, docentes, certificación y matrícula en línea.',
            'incluye' => [
                'Portada con áreas, cursos destacados y cifras de confianza',
                'Catálogo de ocho cursos con precios, duración y fechas de inicio',
                'Filtros por área, modalidad y búsqueda por texto',
                'Ficha de curso con temario por módulos en acordeones',
                'Docentes, certificación con código de verificación y testimonios',
                'Formulario de matrícula con validación en el navegador',
                'Formas de pago, ubicación con plano y enlace a Google Maps',
                'Preguntas frecuentes repartidas en las páginas del sitio',
            ],
            'tecnologias' => ['HTML5', 'CSS3', 'JavaScript'],
            'imagen' => null,
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
            'resumen' => 'Buscador de propiedades con filtros por distrito, precio y dormitorios, y ficha de inmueble.',
            'incluye' => [
                'Buscador con filtros por operación, distrito y precio',
                'Rejilla de propiedades destacadas',
                'Ficha de inmueble con galería y datos',
                'Contacto del asesor asignado',
            ],
            'tecnologias' => ['HTML5', 'CSS3', 'JavaScript'],
            'imagen' => null,
            'demo' => null,
            'destacada' => false,
            'publicada' => true,
            'actualizada' => '2026-10-10',
        ],

        'portafolio-creativo' => [
            'nombre' => 'Portafolio Creativo',
            'categoria' => 'portafolio',
            'tipo' => 'landing',
            'etiquetas' => ['portafolio', 'diseño', 'proyectos', 'estudio'],
            'resumen' => 'Portafolio para estudio de diseño: proyectos, servicios y contacto, con foco en las piezas.',
            'incluye' => [
                'Portada a pantalla completa con el trabajo principal',
                'Rejilla de proyectos filtrable',
                'Servicios y forma de trabajo',
                'Contacto y enlaces a redes',
            ],
            'tecnologias' => ['HTML5', 'CSS3', 'JavaScript'],
            'imagen' => null,
            'demo' => null,
            'destacada' => true,
            'publicada' => true,
            'actualizada' => '2026-10-10',
        ],

    ],

];
