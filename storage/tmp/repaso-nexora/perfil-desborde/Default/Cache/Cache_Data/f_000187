/*
|--------------------------------------------------------------------------
| NÉXORA MARKET — JavaScript de la demostración
|--------------------------------------------------------------------------
|
| Un solo archivo, sin dependencias y sin peticiones a terceros, como pide el
| estándar del banco. Se ocupa de:
|
|   - el interruptor de tema claro / oscuro (el estado inicial lo aplica el
|     script del <head>, aquí solo se atiende el clic);
|   - la canasta guardada en el navegador, con su panel lateral y su página;
|   - los favoritos, también en el navegador;
|   - el buscador de la cabecera con sugerencias mientras se escribe;
|   - el catálogo: filtros por categoría, búsqueda, precio y favoritos, más el
|     orden, con el estado del filtro escrito en la URL;
|   - la ficha de producto, que se arma desde el parámetro ?p=;
|   - el pedido simulado de la página de canasta: valida el formulario, calcula
|     el total y arma el mensaje del pedido. No cobra, no envía y no guarda
|     ningún dato personal: todo se queda en la página.
|
| Los productos viven en PRODUCTOS (aquí abajo) y las secciones se pintan desde
| esos datos: no hay una segunda copia escrita en el HTML que se pueda quedar
| desfasada.
|
*/

(function () {
    'use strict';

    /* ---------------------------------------------------------------------
     * Datos de la tienda (productos, categorías y marcas de demostración)
     * ------------------------------------------------------------------- */

    var CATEGORIAS = [
        {
            clave: 'tecnologia',
            nombre: 'Tecnología',
            foto: 'img/rubro-tecnologia.webp',
            resumen: 'Audio, imagen y accesorios para el día a día.',
            colecciones: ['Audio', 'Cámaras y drones', 'Accesorios de computadora', 'Telefonía'],
        },
        {
            clave: 'moda',
            nombre: 'Moda',
            foto: 'img/rubro-moda.webp',
            resumen: 'Calzado, accesorios y básicos de temporada.',
            colecciones: ['Calzado', 'Bolsos y mochilas', 'Gafas'],
        },
        {
            clave: 'hogar',
            nombre: 'Hogar',
            foto: 'img/rubro-hogar.webp',
            resumen: 'Cocina, organización y detalles para la casa.',
            colecciones: ['Cocina', 'Decoración'],
        },
        {
            clave: 'belleza',
            nombre: 'Belleza',
            foto: 'img/rubro-belleza.webp',
            resumen: 'Cuidado personal, perfumería y belleza.',
            colecciones: ['Perfumería', 'Cabello', 'Cuidado bucal'],
        },
        {
            clave: 'deportes',
            nombre: 'Deportes',
            foto: 'img/rubro-deportes.webp',
            resumen: 'Entrenamiento en casa y deporte de fin de semana.',
            colecciones: ['Entrenamiento en casa', 'Fútbol', 'Yoga y estiramiento'],
        },
        {
            clave: 'juguetes',
            nombre: 'Juguetes y entretenimiento',
            foto: 'img/rubro-juguetes.webp',
            resumen: 'Juego, aprendizaje y regalos para los más chicos.',
            colecciones: ['Didácticos', 'Vehículos', 'Peluches'],
        },
    ];

    var PRODUCTOS = [
        {
            clave: 'auriculares-nexora-studio',
            coleccion: 'Audio',
            nombre: 'Auriculares NEXORA Studio con cancelación de ruido',
            categoria: 'tecnologia',
            precio: 349.9,
            antes: 429.9,
            etiqueta: 'Oferta',
            valoracion: 4.8,
            opiniones: 214,
            stock: 'En stock',
            envio: 'Envío gratis',
            foto: 'img/p-auriculares.webp',
            resumen: 'Diadema de perfil bajo, almohadillas de espuma viscoelástica y hasta 38 horas de batería con la cancelación activa encendida.',
            detalles: [
                ['Conexión', 'Bluetooth 5.3 y cable de 3.5 mm'],
                ['Autonomía', '38 h con cancelación · 52 h sin ella'],
                ['Peso', '265 g'],
                ['Incluye', 'Estuche rígido y cable de carga USB-C'],
            ],
        },
        {
            clave: 'auriculares-nexora-buds',
            coleccion: 'Audio',
            nombre: 'Auriculares inalámbricos NEXORA Buds',
            categoria: 'tecnologia',
            precio: 199.9,
            etiqueta: 'Nuevo',
            valoracion: 4.6,
            opiniones: 158,
            stock: 'En stock',
            envio: 'Envío gratis',
            foto: 'img/p-earbuds.webp',
            resumen: 'Formato compacto con estuche de carga, controles táctiles y resistencia al sudor para entrenar sin cables.',
            detalles: [
                ['Conexión', 'Bluetooth 5.2'],
                ['Autonomía', '7 h en el auricular + 21 h en el estuche'],
                ['Resistencia', 'IPX5 (salpicaduras y sudor)'],
                ['Incluye', 'Estuche de carga y tres pares de almohadillas'],
            ],
        },
        {
            clave: 'reloj-nexora-fit-2',
            coleccion: 'Telefonía',
            nombre: 'Reloj inteligente NEXORA Fit 2',
            categoria: 'tecnologia',
            precio: 429,
            antes: 499,
            etiqueta: 'Oferta',
            valoracion: 4.7,
            opiniones: 96,
            stock: 'En stock',
            envio: 'Envío gratis',
            foto: 'img/p-reloj.webp',
            resumen: 'Pantalla cuadrada de 1.8", medición de sueño y entrenamiento, y correa intercambiable en dos tonos.',
            detalles: [
                ['Pantalla', '1.8" con brillo automático'],
                ['Batería', 'Hasta 10 días de uso normal'],
                ['Sensores', 'Ritmo cardíaco, oxígeno y podómetro'],
                ['Incluye', 'Correa adicional y cable de carga'],
            ],
        },
        {
            clave: 'altavoz-nexora-go',
            coleccion: 'Audio',
            nombre: 'Altavoz portátil NEXORA Go',
            categoria: 'tecnologia',
            precio: 249.9,
            valoracion: 4.5,
            opiniones: 132,
            stock: 'En stock',
            envio: 'Envío gratis',
            foto: 'img/p-altavoz.webp',
            resumen: 'Altavoz resistente al agua, con asa de transporte y hasta 20 horas de música lejos del enchufe.',
            detalles: [
                ['Potencia', '30 W'],
                ['Batería', 'Hasta 20 h de reproducción'],
                ['Resistencia', 'IPX7 (sumergible)'],
                ['Extras', 'Emparejamiento entre dos unidades'],
            ],
        },
        {
            clave: 'altavoz-nexora-home',
            coleccion: 'Audio',
            nombre: 'Altavoz inteligente NEXORA Home Mini',
            categoria: 'tecnologia',
            precio: 189.9,
            valoracion: 4.4,
            opiniones: 87,
            stock: 'Últimas unidades',
            envio: 'Envío gratis',
            foto: 'img/p-altavoz-inteligente.webp',
            resumen: 'Parlante de escritorio para música y recordatorios, con controles táctiles y luz de estado.',
            detalles: [
                ['Conexión', 'Wi-Fi 2.4 GHz y Bluetooth'],
                ['Audio', 'Altavoz de 1.6" con radiador pasivo'],
                ['Alimentación', 'Cable USB-C incluido'],
                ['Acabado', 'Tela tejida en tono arena'],
            ],
        },
        {
            clave: 'camara-nexora-m50',
            coleccion: 'Cámaras y drones',
            nombre: 'Cámara mirrorless NEXORA M50',
            categoria: 'tecnologia',
            precio: 2890,
            etiqueta: 'Nuevo',
            valoracion: 4.9,
            opiniones: 64,
            stock: 'En stock',
            envio: 'Envío gratis',
            foto: 'img/p-camara.webp',
            resumen: 'Cuerpo compacto con lente de 28 mm, grabación en 4K y pantalla abatible para video y fotografía de viaje.',
            detalles: [
                ['Sensor', 'APS-C de 24 MP'],
                ['Video', '4K a 30 fps'],
                ['Lente', '28 mm f/2.8 incluido'],
                ['Incluye', 'Batería, cargador y correa'],
            ],
        },
        {
            clave: 'raton-nexora-pro',
            coleccion: 'Accesorios de computadora',
            nombre: 'Ratón inalámbrico NEXORA Pro',
            categoria: 'tecnologia',
            precio: 99.9,
            antes: 129.9,
            etiqueta: 'Oferta',
            valoracion: 4.6,
            opiniones: 301,
            stock: 'En stock',
            envio: 'Envío gratis',
            foto: 'img/p-raton.webp',
            resumen: 'Sensor de alta precisión, siete botones configurables y batería que dura semanas con una sola carga.',
            detalles: [
                ['Sensor', 'Hasta 26 000 DPI'],
                ['Botones', '7 programables'],
                ['Batería', 'Hasta 70 días'],
                ['Conexión', 'USB inalámbrico de baja latencia'],
            ],
        },
        {
            clave: 'dron-nexora-air',
            coleccion: 'Cámaras y drones',
            nombre: 'Dron NEXORA Air con cámara',
            categoria: 'tecnologia',
            precio: 1490,
            valoracion: 4.7,
            opiniones: 45,
            stock: 'Por encargo',
            envio: 'Entrega en 5 días',
            foto: 'img/p-dron.webp',
            resumen: 'Cuadricóptero plegable con cámara estabilizada, retorno automático y tres baterías para una jornada de vuelo.',
            detalles: [
                ['Vuelo', 'Hasta 31 minutos por batería'],
                ['Cámara', '4K con estabilizador de 3 ejes'],
                ['Alcance', 'Hasta 8 km de transmisión'],
                ['Incluye', 'Control, tres baterías y maletín'],
            ],
        },
        {
            clave: 'smartphone-nexora-one',
            coleccion: 'Telefonía',
            nombre: 'Smartphone NEXORA One 128 GB',
            categoria: 'tecnologia',
            precio: 1190,
            antes: 1390,
            etiqueta: 'Oferta',
            valoracion: 4.5,
            opiniones: 212,
            stock: 'En stock',
            envio: 'Envío gratis',
            foto: 'img/p-smartphone.webp',
            resumen: 'Pantalla de 6.4", carga rápida y almacenamiento de 128 GB para el día completo.',
            detalles: [
                ['Pantalla', '6.4" AMOLED'],
                ['Memoria', '8 GB RAM · 128 GB'],
                ['Batería', '5000 mAh con carga rápida'],
                ['Cámara', 'Triple de 50 MP'],
            ],
        },
        {
            clave: 'zapatillas-nexora-runner',
            coleccion: 'Calzado',
            nombre: 'Zapatillas NEXORA Runner',
            categoria: 'moda',
            precio: 279.9,
            valoracion: 4.6,
            opiniones: 178,
            stock: 'En stock',
            envio: 'Envío gratis',
            foto: 'img/p-zapatillas.webp',
            resumen: 'Entresuela amortiguada y malla transpirable, pensadas para caminatas largas y trote suave.',
            detalles: [
                ['Tallas', 'Del 36 al 45'],
                ['Material', 'Malla textil y suela de caucho'],
                ['Peso', '280 g (talla 42)'],
                ['Uso', 'Caminata y trote urbano'],
            ],
        },
        {
            clave: 'gafas-nexora-sun',
            coleccion: 'Gafas',
            nombre: 'Gafas de sol NEXORA Sun',
            categoria: 'moda',
            precio: 129.9,
            antes: 159.9,
            etiqueta: 'Oferta',
            valoracion: 4.3,
            opiniones: 64,
            stock: 'En stock',
            envio: 'Envío gratis',
            foto: 'img/p-gafas.webp',
            resumen: 'Montura liviana con lentes polarizadas y estuche de tela, en un corte que combina con todo.',
            detalles: [
                ['Lentes', 'Polarizadas, categoría 3'],
                ['Montura', 'Acetato liviano'],
                ['Incluye', 'Estuche y paño de limpieza'],
                ['Protección', 'UV400'],
            ],
        },
        {
            clave: 'bolso-nexora-cuero',
            coleccion: 'Bolsos y mochilas',
            nombre: 'Bolso NEXORA Cuero Urbano',
            categoria: 'moda',
            precio: 359,
            etiqueta: 'Nuevo',
            valoracion: 4.8,
            opiniones: 41,
            stock: 'En stock',
            envio: 'Envío gratis',
            foto: 'img/p-bolso.webp',
            resumen: 'Bolso de mano con asa corta, correa larga desmontable y forro interior con bolsillo para el celular.',
            detalles: [
                ['Material', 'Cuero sintético de grano fino'],
                ['Medidas', '32 × 22 × 12 cm'],
                ['Interior', 'Dos compartimentos y bolsillo con cierre'],
                ['Incluye', 'Correa larga regulable'],
            ],
        },
        {
            clave: 'cafetera-nexora-express',
            coleccion: 'Cocina',
            nombre: 'Cafetera NEXORA Express',
            categoria: 'hogar',
            precio: 549.9,
            valoracion: 4.6,
            opiniones: 203,
            stock: 'En stock',
            envio: 'Envío gratis',
            foto: 'img/p-cafetera.webp',
            resumen: 'Prepara espresso y lungo con cápsulas o café molido; el depósito de agua se llena por delante.',
            detalles: [
                ['Presión', '15 bares'],
                ['Depósito', '1.2 L'],
                ['Bandeja', 'Regulable en altura'],
                ['Incluye', 'Filtro para café molido'],
            ],
        },
        {
            clave: 'hervidor-nexora-clean',
            coleccion: 'Cocina',
            nombre: 'Hervidor eléctrico NEXORA Clean 1.7 L',
            categoria: 'hogar',
            precio: 149.9,
            antes: 189.9,
            etiqueta: 'Oferta',
            valoracion: 4.4,
            opiniones: 118,
            stock: 'En stock',
            envio: 'Envío gratis',
            foto: 'img/p-hervidor.webp',
            resumen: 'Hervidor de acero con apagado automático, base giratoria 360° y filtro antical desmontable.',
            detalles: [
                ['Capacidad', '1.7 L'],
                ['Potencia', '1800 W'],
                ['Seguridad', 'Apagado automático y protección en seco'],
                ['Base', 'Giratoria 360°'],
            ],
        },
        {
            clave: 'licuadora-nexora-fresh',
            coleccion: 'Cocina',
            nombre: 'Licuadora NEXORA Fresh 2 L',
            categoria: 'hogar',
            precio: 219.9,
            valoracion: 4.5,
            opiniones: 96,
            stock: 'En stock',
            envio: 'Envío gratis',
            foto: 'img/p-licuadora.webp',
            resumen: 'Vaso de dos litros, cuchillas de acero y dos velocidades con pulso para triturar hielo sin esfuerzo.',
            detalles: [
                ['Capacidad', '2 L'],
                ['Potencia', '600 W'],
                ['Velocidades', 'Dos más pulso'],
                ['Vaso', 'Libre de BPA, apto para lavavajillas'],
            ],
        },
        {
            clave: 'ollas-nexora-steel',
            coleccion: 'Cocina',
            nombre: 'Juego de ollas NEXORA Steel 7 piezas',
            categoria: 'hogar',
            precio: 399,
            valoracion: 4.7,
            opiniones: 72,
            stock: 'En stock',
            envio: 'Envío gratis',
            foto: 'img/p-ollas.webp',
            resumen: 'Cinco ollas y dos sartenes de acero inoxidable con triple fondo, aptas para toda cocina.',
            detalles: [
                ['Piezas', '5 ollas y 2 sartenes'],
                ['Material', 'Acero inoxidable con triple fondo'],
                ['Compatibilidad', 'Gas, eléctrica, vitrocerámica e inducción'],
                ['Tapa', 'De vidrio templado con válvula'],
            ],
        },
        {
            clave: 'jarron-nexora-ceramica',
            coleccion: 'Decoración',
            nombre: 'Jarrón NEXORA Cerámica mate',
            categoria: 'hogar',
            precio: 89.9,
            etiqueta: 'Nuevo',
            valoracion: 4.5,
            opiniones: 33,
            stock: 'En stock',
            envio: 'Envío gratis',
            foto: 'img/p-jarron.webp',
            resumen: 'Jarrón torneado a mano con acabado mate, pensado para flores secas o como pieza sola.',
            detalles: [
                ['Medidas', '24 cm de alto'],
                ['Material', 'Cerámica esmaltada'],
                ['Acabado', 'Mate, tono arena'],
                ['Cuidado', 'Limpiar con paño húmedo'],
            ],
        },
        {
            clave: 'cepillo-nexora-smile',
            coleccion: 'Cuidado bucal',
            nombre: 'Cepillo dental eléctrico NEXORA Smile',
            categoria: 'belleza',
            precio: 249.9,
            valoracion: 4.6,
            opiniones: 145,
            stock: 'En stock',
            envio: 'Envío gratis',
            foto: 'img/p-cepillo.webp',
            resumen: 'Tres modos de limpieza, temporizador de dos minutos y estuche de viaje con carga por USB.',
            detalles: [
                ['Modos', 'Limpieza, suave y blanqueamiento'],
                ['Autonomía', 'Hasta 14 días por carga'],
                ['Temporizador', 'Aviso cada 30 segundos'],
                ['Incluye', 'Dos cabezales y estuche de viaje'],
            ],
        },
        {
            clave: 'perfume-nexora-noir',
            coleccion: 'Perfumería',
            nombre: 'Perfume NEXORA Noir 100 ml',
            categoria: 'belleza',
            precio: 279,
            antes: 320,
            etiqueta: 'Oferta',
            valoracion: 4.7,
            opiniones: 88,
            stock: 'En stock',
            envio: 'Envío gratis',
            foto: 'img/p-perfume.webp',
            resumen: 'Fragancia amaderada con salida cítrica, frasco de vidrio con atomizador y estuche de regalo.',
            detalles: [
                ['Contenido', '100 ml'],
                ['Familia', 'Amaderada con salida cítrica'],
                ['Presentación', 'Frasco de vidrio con atomizador'],
                ['Incluye', 'Estuche de regalo'],
            ],
        },
        {
            clave: 'secador-nexora-air',
            coleccion: 'Cabello',
            nombre: 'Secador NEXORA Air 2000 W',
            categoria: 'belleza',
            precio: 189.9,
            valoracion: 4.3,
            opiniones: 127,
            stock: 'En stock',
            envio: 'Envío gratis',
            foto: 'img/p-secador.webp',
            resumen: 'Tres temperaturas, dos velocidades y boquilla concentradora, con función de aire frío para fijar el peinado.',
            detalles: [
                ['Potencia', '2000 W'],
                ['Temperaturas', 'Tres, con aire frío'],
                ['Incluye', 'Boquilla concentradora y difusor'],
                ['Cable', '2 m con argolla para colgar'],
            ],
        },
        {
            clave: 'mancuernas-nexora-8kg',
            coleccion: 'Entrenamiento en casa',
            nombre: 'Par de mancuernas NEXORA 8 kg',
            categoria: 'deportes',
            precio: 159.9,
            valoracion: 4.5,
            opiniones: 64,
            stock: 'En stock',
            envio: 'Entrega en 3 días',
            foto: 'img/p-mancuernas.webp',
            resumen: 'Dos mancuernas de 8 kg con recubrimiento de neopreno, pensadas para entrenar en casa sin marcar el piso.',
            detalles: [
                ['Peso', '8 kg cada una'],
                ['Recubrimiento', 'Neopreno antideslizante'],
                ['Mango', 'Agarre ergonómico'],
                ['Incluye', 'Par de mancuernas'],
            ],
        },
        {
            clave: 'balon-nexora-match',
            coleccion: 'Fútbol',
            nombre: 'Balón NEXORA Match número 5',
            categoria: 'deportes',
            precio: 79.9,
            antes: 99.9,
            etiqueta: 'Oferta',
            valoracion: 4.4,
            opiniones: 151,
            stock: 'En stock',
            envio: 'Envío gratis',
            foto: 'img/p-balon.webp',
            resumen: 'Balón cosido a máquina con cámara de látex, listo para césped y losa.',
            detalles: [
                ['Talla', 'Número 5'],
                ['Construcción', '32 paneles cosidos'],
                ['Cámara', 'De látex, retiene el aire más tiempo'],
                ['Uso', 'Césped, losa y sintético'],
            ],
        },
        {
            clave: 'esterilla-nexora-yoga',
            coleccion: 'Yoga y estiramiento',
            nombre: 'Esterilla NEXORA Yoga 6 mm',
            categoria: 'deportes',
            precio: 109.9,
            valoracion: 4.6,
            opiniones: 82,
            stock: 'En stock',
            envio: 'Envío gratis',
            foto: 'img/p-yoga.webp',
            resumen: 'Esterilla antideslizante de doble cara con correa de transporte, para yoga, estiramiento o abdominales.',
            detalles: [
                ['Espesor', '6 mm'],
                ['Medidas', '183 × 61 cm'],
                ['Material', 'TPE libre de látex'],
                ['Incluye', 'Correa de transporte'],
            ],
        },
        {
            clave: 'bloques-nexora-madera',
            coleccion: 'Didácticos',
            nombre: 'Bloques NEXORA Madera 60 piezas',
            categoria: 'juguetes',
            precio: 119.9,
            valoracion: 4.8,
            opiniones: 57,
            stock: 'En stock',
            envio: 'Envío gratis',
            foto: 'img/p-bloques.webp',
            resumen: 'Set de bloques de madera con letras y números, lijados a mano y pintados con tintes al agua.',
            detalles: [
                ['Piezas', '60 bloques'],
                ['Material', 'Madera de pino lijada'],
                ['Pintura', 'Tintes al agua'],
                ['Edad sugerida', 'Desde 3 años'],
            ],
        },
        {
            clave: 'coche-nexora-rc',
            coleccion: 'Vehículos',
            nombre: 'Coche teledirigido NEXORA RC',
            categoria: 'juguetes',
            precio: 179.9,
            antes: 219.9,
            etiqueta: 'Oferta',
            valoracion: 4.5,
            opiniones: 101,
            stock: 'En stock',
            envio: 'Envío gratis',
            foto: 'img/p-coche.webp',
            resumen: 'Vehículo de tracción 4×4 con control de 2.4 GHz, llantas de goma y batería recargable.',
            detalles: [
                ['Tracción', '4×4'],
                ['Control', '2.4 GHz, hasta 40 m'],
                ['Batería', 'Recargable por USB, 25 min de juego'],
                ['Escala', '1:16'],
            ],
        },
        {
            clave: 'peluche-nexora-oso',
            coleccion: 'Peluches',
            nombre: 'Peluche NEXORA Oso mediano',
            categoria: 'juguetes',
            precio: 69.9,
            valoracion: 4.7,
            opiniones: 39,
            stock: 'En stock',
            envio: 'Envío gratis',
            foto: 'img/p-peluche.webp',
            resumen: 'Peluche de felpa suave con relleno de fibra siliconada, cosido a mano y lavable.',
            detalles: [
                ['Medidas', '38 cm de alto'],
                ['Material', 'Felpa hipoalergénica'],
                ['Relleno', 'Fibra siliconada'],
                ['Lavado', 'A mano con agua fría'],
            ],
        },
        {
            clave: 'tren-nexora-madera',
            coleccion: 'Didácticos',
            nombre: 'Tren de madera NEXORA Vía',
            categoria: 'juguetes',
            precio: 149.9,
            etiqueta: 'Nuevo',
            valoracion: 4.6,
            opiniones: 28,
            stock: 'En stock',
            envio: 'Envío gratis',
            foto: 'img/p-tren.webp',
            resumen: 'Circuito de vías de madera con puente, locomotora y dos vagones de carga.',
            detalles: [
                ['Piezas', '22 piezas de vía, puente, locomotora y 2 vagones'],
                ['Material', 'Madera maciza con imanes'],
                ['Medidas armado', '110 × 70 cm'],
                ['Edad sugerida', 'Desde 3 años'],
            ],
        },
    ];

    var MARCAS = [
        { nombre: 'VELTRA', rubro: 'Audio' },
        { nombre: 'KUMA', rubro: 'Textiles' },
        { nombre: 'AUREO', rubro: 'Accesorios' },
        { nombre: 'LUMEN', rubro: 'Iluminación' },
        { nombre: 'TREVO', rubro: 'Juguetería' },
        { nombre: 'MIRAI', rubro: 'Cocina' },
    ];

    var WHATSAPP = '51900000000'; // Número de demostración: no existe.
    var CLAVE_CANASTA = 'nexora-market-canasta';
    var CLAVE_FAVORITOS = 'nexora-market-favoritos';
    var CLAVE_TEMA = 'nexora-market-tema';

    /* ---------------------------------------------------------------------
     * Utilidades
     * ------------------------------------------------------------------- */

    var $ = function (selector, raiz) {
        return (raiz || document).querySelector(selector);
    };

    var $$ = function (selector, raiz) {
        return Array.prototype.slice.call((raiz || document).querySelectorAll(selector));
    };

    function soles(valor) {
        return 'S/ ' + valor.toFixed(2);
    }

    /** Redondeo a dos decimales: sumar precios con decimales deja colas. */
    function redondear(valor) {
        return Math.round(valor * 100) / 100;
    }

    function buscarProducto(clave) {
        for (var i = 0; i < PRODUCTOS.length; i++) {
            if (PRODUCTOS[i].clave === clave) {
                return PRODUCTOS[i];
            }
        }

        return null;
    }

    function buscarCategoria(clave) {
        for (var i = 0; i < CATEGORIAS.length; i++) {
            if (CATEGORIAS[i].clave === clave) {
                return CATEGORIAS[i];
            }
        }

        return null;
    }

    function deLaCategoria(clave) {
        return PRODUCTOS.filter(function (producto) {
            return producto.categoria === clave;
        });
    }

    /** Lector tolerante: en navegación privada localStorage puede fallar. */
    function leer(clave, porDefecto) {
        try {
            var crudo = window.localStorage.getItem(clave);

            return crudo === null ? porDefecto : JSON.parse(crudo);
        } catch (error) {
            return porDefecto;
        }
    }

    function guardar(clave, valor) {
        try {
            window.localStorage.setItem(clave, JSON.stringify(valor));
        } catch (error) {
            /* Sin almacenamiento la demo sigue funcionando en memoria. */
        }
    }

    /** Etiqueta segura: nunca se inyecta HTML de los datos en la página. */
    function texto(valor) {
        return String(valor === null || valor === undefined ? '' : valor)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function estrellas(valoracion) {
        var llenas = Math.round(valoracion);
        var salida = '';

        for (var i = 1; i <= 5; i++) {
            salida += '<svg class="estrella' + (i <= llenas ? ' esta-llena' : '') + '" viewBox="0 0 24 24" aria-hidden="true">'
                + '<path d="m12 3.6 2.6 5.4 5.9.8-4.3 4.1 1 5.9-5.2-2.8-5.2 2.8 1-5.9L3.5 9.8l5.9-.8Z"/></svg>';
        }

        return salida;
    }

    /* ---------------------------------------------------------------------
     * Tema claro / oscuro
     * ------------------------------------------------------------------- */

    function temaActual() {
        return document.documentElement.getAttribute('data-tema') === 'oscuro' ? 'oscuro' : 'claro';
    }

    function pintarInterruptor() {
        var oscuro = temaActual() === 'oscuro';

        $$('[data-tema-switch]').forEach(function (boton) {
            boton.setAttribute('aria-pressed', oscuro ? 'true' : 'false');
            boton.setAttribute('aria-label', oscuro ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
            boton.setAttribute('title', oscuro ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
        });
    }

    function ponerTema(tema) {
        var valor = tema === 'oscuro' ? 'oscuro' : 'claro';

        document.documentElement.setAttribute('data-tema', valor);

        // El tema se guarda como texto plano, no como JSON: el script del <head>
        // lo lee antes de que exista este archivo y compara con 'oscuro' a
        // secas, así que un valor entrecomillado no lo pondría nunca.
        try {
            window.localStorage.setItem(CLAVE_TEMA, valor);
        } catch (error) {
            /* Sin almacenamiento la elección vive solo en esta página. */
        }

        pintarInterruptor();
    }

    $$('[data-tema-switch]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            ponerTema(temaActual() === 'oscuro' ? 'claro' : 'oscuro');
        });
    });

    pintarInterruptor();

    // Otra pestaña cambió el tema: esta se pone al día sin recargar.
    window.addEventListener('storage', function (evento) {
        if (evento.key === CLAVE_TEMA) {
            document.documentElement.setAttribute('data-tema', evento.newValue === 'oscuro' ? 'oscuro' : 'claro');
            pintarInterruptor();
        } else if (evento.key === CLAVE_CANASTA) {
            canasta = normalizarCanasta(leer(CLAVE_CANASTA, []));
            pintarCanasta();
        } else if (evento.key === CLAVE_FAVORITOS) {
            favoritos = leer(CLAVE_FAVORITOS, []);
            pintarFavoritos();
        }
    });

    /* ---------------------------------------------------------------------
     * Canasta
     * ------------------------------------------------------------------- */

    function normalizarCanasta(crudo) {
        if (!Array.isArray(crudo)) {
            return [];
        }

        return crudo
            .filter(function (linea) {
                return linea && buscarProducto(linea.clave);
            })
            .map(function (linea) {
                return { clave: linea.clave, cantidad: Math.max(1, Math.min(99, parseInt(linea.cantidad, 10) || 1)) };
            });
    }

    var canasta = normalizarCanasta(leer(CLAVE_CANASTA, []));
    var favoritos = leer(CLAVE_FAVORITOS, []);

    if (!Array.isArray(favoritos)) {
        favoritos = [];
    }

    function guardarCanasta() {
        guardar(CLAVE_CANASTA, canasta);
        pintarCanasta();
    }

    function unidadesEnCanasta() {
        return canasta.reduce(function (total, linea) {
            return total + linea.cantidad;
        }, 0);
    }

    function subtotalCanasta() {
        return redondear(canasta.reduce(function (total, linea) {
            var producto = buscarProducto(linea.clave);

            return total + producto.precio * linea.cantidad;
        }, 0));
    }

    function anadir(clave, cantidad) {
        var linea = canasta.filter(function (item) {
            return item.clave === clave;
        })[0];

        if (linea) {
            linea.cantidad = Math.min(99, linea.cantidad + (cantidad || 1));
        } else {
            canasta.push({ clave: clave, cantidad: Math.max(1, cantidad || 1) });
        }

        guardarCanasta();
        avisarCanasta(clave);
    }

    function cambiarCantidad(clave, cantidad) {
        if (cantidad <= 0) {
            quitar(clave);

            return;
        }

        canasta.forEach(function (linea) {
            if (linea.clave === clave) {
                linea.cantidad = Math.min(99, cantidad);
            }
        });

        guardarCanasta();
    }

    function quitar(clave) {
        canasta = canasta.filter(function (linea) {
            return linea.clave !== clave;
        });

        guardarCanasta();
    }

    function vaciar() {
        canasta = [];
        guardarCanasta();
    }

    /** Aviso breve al añadir, para que el clic tenga respuesta visible. */
    var temporizadorAviso = null;

    function avisarCanasta(clave) {
        var producto = buscarProducto(clave);
        var aviso = $('[data-aviso-canasta]');

        if (!producto || !aviso) {
            return;
        }

        aviso.textContent = 'Añadido a la canasta: ' + producto.nombre;
        aviso.hidden = false;
        aviso.classList.add('esta-visible');

        window.clearTimeout(temporizadorAviso);
        temporizadorAviso = window.setTimeout(function () {
            aviso.classList.remove('esta-visible');
            aviso.hidden = true;
        }, 3200);
    }

    function lineas() {
        return canasta.map(function (linea) {
            return { producto: buscarProducto(linea.clave), cantidad: linea.cantidad };
        });
    }

    function pintarCanasta() {
        var unidades = unidadesEnCanasta();
        var subtotal = subtotalCanasta();

        $$('[data-canasta-cuenta]').forEach(function (nodo) {
            nodo.textContent = unidades;
            nodo.classList.toggle('esta-vacia', unidades === 0);
        });

        $$('[data-canasta-total]').forEach(function (nodo) {
            nodo.textContent = soles(subtotal);
        });

        var lista = $('[data-canasta-lista]');

        if (lista) {
            lista.innerHTML = lineas().map(function (linea) {
                return '<li class="linea" data-linea="' + texto(linea.producto.clave) + '">'
                    + '<img class="linea__foto" src="' + texto(linea.producto.foto) + '" alt="" width="72" height="72" loading="lazy">'
                    + '<div class="linea__datos">'
                    + '<a class="linea__nombre" href="producto.html?p=' + texto(linea.producto.clave) + '">' + texto(linea.producto.nombre) + '</a>'
                    + '<p class="linea__precio">' + soles(linea.producto.precio) + ' <span>· ' + soles(redondear(linea.producto.precio * linea.cantidad)) + '</span></p>'
                    + '<div class="cantidad">'
                    + '<button type="button" data-cantidad-menos aria-label="Quitar una unidad de ' + texto(linea.producto.nombre) + '">−</button>'
                    + '<input type="text" inputmode="numeric" value="' + linea.cantidad + '" data-cantidad-valor aria-label="Unidades de ' + texto(linea.producto.nombre) + '">'
                    + '<button type="button" data-cantidad-mas aria-label="Añadir una unidad de ' + texto(linea.producto.nombre) + '">+</button>'
                    + '</div>'
                    + '</div>'
                    + '<button class="linea__quitar" type="button" data-linea-quitar aria-label="Quitar ' + texto(linea.producto.nombre) + ' de la canasta">×</button>'
                    + '</li>';
            }).join('');
        }

        var vacia = $('[data-canasta-vacia]');
        var pie = $('[data-canasta-pie]');
        var piePagina = $('[data-carrito-vacio]');

        if (vacia) {
            vacia.hidden = canasta.length > 0;
        }

        if (piePagina) {
            piePagina.hidden = canasta.length > 0;
        }

        if (pie) {
            pie.hidden = canasta.length === 0;
        }

        pintarPaginaCanasta();

        // La página de canasta y el panel comparten los mismos datos: si el
        // carrito se vacía, el resumen de la página desaparece con él.
        $$('[data-canasta-resumen]').forEach(function (nodo) {
            nodo.hidden = canasta.length === 0;
        });

        $$('[data-canasta-lleno]').forEach(function (nodo) {
            nodo.hidden = canasta.length === 0;
        });
    }

    function pintarFavoritos() {
        guardar(CLAVE_FAVORITOS, favoritos);

        $$('[data-favorito]').forEach(function (boton) {
            var activo = favoritos.indexOf(boton.getAttribute('data-favorito')) !== -1;

            boton.classList.toggle('esta-activo', activo);
            boton.setAttribute('aria-pressed', activo ? 'true' : 'false');
            boton.setAttribute('aria-label', (activo ? 'Quitar de favoritos: ' : 'Añadir a favoritos: ') + boton.getAttribute('data-favorito-nombre'));
        });

        $$('[data-favoritos-cuenta]').forEach(function (nodo) {
            nodo.textContent = favoritos.length;
            nodo.classList.toggle('esta-vacia', favoritos.length === 0);
        });
    }

    document.addEventListener('click', function (evento) {
        var anadirBoton = evento.target.closest('[data-anadir]');

        if (anadirBoton) {
            evento.preventDefault();
            anadir(anadirBoton.getAttribute('data-anadir'), 1);

            return;
        }

        var favorito = evento.target.closest('[data-favorito]');

        if (favorito) {
            evento.preventDefault();

            var clave = favorito.getAttribute('data-favorito');
            var indice = favoritos.indexOf(clave);

            if (indice === -1) {
                favoritos.push(clave);
            } else {
                favoritos.splice(indice, 1);
            }

            pintarFavoritos();

            if ($('[data-catalogo]')) {
                pintarCatalogo();
            }

            return;
        }

        var menos = evento.target.closest('[data-cantidad-menos]');
        var mas = evento.target.closest('[data-cantidad-mas]');
        var quitarLinea = evento.target.closest('[data-linea-quitar]');

        if (menos || mas || quitarLinea) {
            var contenedor = evento.target.closest('[data-linea]');
            var nombreClave = contenedor ? contenedor.getAttribute('data-linea') : null;
            var linea = canasta.filter(function (item) { return item.clave === nombreClave; })[0];

            if (!nombreClave || !linea) {
                return;
            }

            if (menos) {
                cambiarCantidad(nombreClave, linea.cantidad - 1);
            } else if (mas) {
                cambiarCantidad(nombreClave, linea.cantidad + 1);
            } else {
                quitar(nombreClave);
            }

            return;
        }

        if (evento.target.closest('[data-canasta-abrir]')) {
            abrirPanel();
        }

        if (evento.target.closest('[data-canasta-cerrar]') || evento.target.closest('[data-panel-fondo]')) {
            cerrarPanel();
        }
    });

    // Cambio de cantidad escrito a mano en el panel.
    document.addEventListener('change', function (evento) {
        var campo = evento.target.closest('[data-cantidad-valor]');

        if (!campo) {
            return;
        }

        var contenedor = evento.target.closest('[data-linea]');

        if (contenedor) {
            cambiarCantidad(contenedor.getAttribute('data-linea'), parseInt(campo.value, 10) || 0);
        }
    });

    function abrirPanel() {
        var panel = $('[data-canasta-panel]');
        var fondo = $('[data-panel-fondo]');

        if (!panel) {
            return;
        }

        panel.hidden = false;

        if (fondo) {
            fondo.hidden = false;
        }

        document.body.classList.add('sin-desplazamiento');

        // Un fotograma de espera: con la clase puesta en el mismo fotograma que
        // el `hidden = false` la transición de entrada no se llega a ver.
        window.requestAnimationFrame(function () {
            panel.classList.add('esta-abierto');
        });

        var cerrar = $('[data-canasta-cerrar]', panel);

        if (cerrar) {
            cerrar.focus();
        }
    }

    function cerrarPanel() {
        var panel = $('[data-canasta-panel]');

        if (!panel || panel.hidden) {
            return;
        }

        panel.classList.remove('esta-abierto');
        document.body.classList.remove('sin-desplazamiento');

        var fondo = $('[data-panel-fondo]');

        if (fondo) {
            fondo.hidden = true;
        }

        window.setTimeout(function () {
            if (!panel.classList.contains('esta-abierto')) {
                panel.hidden = true;
            }
        }, 300);
    }

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
            cerrarPanel();
            cerrarMega();
        }
    });

    /* ---------------------------------------------------------------------
     * Tarjeta de producto
     * ------------------------------------------------------------------- */

    function tarjetaProducto(producto) {
        var categoria = buscarCategoria(producto.categoria);
        var etiquetas = '';

        if (producto.etiqueta) {
            etiquetas += '<span class="producto__etiqueta producto__etiqueta--' + (producto.etiqueta === 'Oferta' ? 'oferta' : 'nuevo') + '">' + texto(producto.etiqueta) + '</span>';
        }

        if (producto.antes) {
            var ahorro = Math.round((1 - producto.precio / producto.antes) * 100);
            etiquetas += '<span class="producto__etiqueta producto__etiqueta--ahorro">−' + ahorro + '%</span>';
        }

        return '<article class="producto" data-producto="' + texto(producto.clave) + '">'
            + '<div class="producto__media">'
            + '<a class="producto__enlace" href="producto.html?p=' + texto(producto.clave) + '" aria-label="Ver la ficha de ' + texto(producto.nombre) + '">'
            + '<img src="' + texto(producto.foto) + '" alt="' + texto(producto.nombre) + '" width="800" height="800" loading="lazy">'
            + '</a>'
            + '<div class="producto__etiquetas">' + etiquetas + '</div>'
            + '<button class="producto__favorito" type="button" data-favorito="' + texto(producto.clave) + '" data-favorito-nombre="' + texto(producto.nombre) + '" aria-pressed="false" aria-label="Añadir a favoritos: ' + texto(producto.nombre) + '">'
            + '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20.5s-7.5-4.3-7.5-9.4A4.1 4.1 0 0 1 12 8.4a4.1 4.1 0 0 1 7.5 2.7c0 5.1-7.5 9.4-7.5 9.4Z"/></svg>'
            + '</button>'
            + '</div>'
            + '<div class="producto__cuerpo">'
            + '<p class="producto__categoria">' + texto(categoria ? categoria.nombre : '') + '</p>'
            + '<h3 class="producto__nombre"><a href="producto.html?p=' + texto(producto.clave) + '">' + texto(producto.nombre) + '</a></h3>'
            + '<p class="producto__valoracion">' + estrellas(producto.valoracion) + '<span>' + producto.valoracion.toFixed(1) + ' · ' + producto.opiniones + ' opiniones de ejemplo</span></p>'
            + '<p class="producto__precio">'
            + '<strong>' + soles(producto.precio) + '</strong>'
            + (producto.antes ? '<s>' + soles(producto.antes) + '</s>' : '')
            + '</p>'
            + '<p class="producto__estado"><span class="punto ' + (producto.stock === 'En stock' ? 'punto--verde' : 'punto--naranja') + '"></span>' + texto(producto.stock) + ' · ' + texto(producto.envio) + '</p>'
            + '<button class="boton boton--principal producto__anadir" type="button" data-anadir="' + texto(producto.clave) + '">Añadir a la canasta</button>'
            + '</div>'
            + '</article>';
    }

    function pintarProductos(contenedor, lista) {
        if (!contenedor) {
            return;
        }

        contenedor.innerHTML = lista.map(tarjetaProducto).join('');
        pintarFavoritos();
    }

    /* ---------------------------------------------------------------------
     * Portada
     * ------------------------------------------------------------------- */

    var escaparate = $('[data-escaparate]');

    if (escaparate) {
        var destacados = ['auriculares-nexora-studio', 'reloj-nexora-fit-2', 'altavoz-nexora-go', 'zapatillas-nexora-runner', 'cafetera-nexora-express', 'cepillo-nexora-smile', 'esterilla-nexora-yoga', 'bloques-nexora-madera'];
        pintarProductos(escaparate, destacados.map(buscarProducto).filter(Boolean));
    }

    var ofertas = $('[data-ofertas]');

    if (ofertas) {
        pintarProductos(ofertas, PRODUCTOS.filter(function (producto) {
            return !!producto.antes;
        }).slice(0, 4));
    }

    var rubros = $('[data-rubros]');

    if (rubros) {
        rubros.innerHTML = CATEGORIAS.map(function (categoria) {
            var total = deLaCategoria(categoria.clave).length;

            return '<a class="rubro" href="catalogo.html?categoria=' + texto(categoria.clave) + '">'
                + '<img src="' + texto(categoria.foto) + '" alt="' + texto(categoria.nombre) + '" width="800" height="600" loading="lazy">'
                + '<span class="rubro__cuerpo">'
                + '<strong>' + texto(categoria.nombre) + '</strong>'
                + '<small>' + total + ' productos de demostración</small>'
                + '</span>'
                + '</a>';
        }).join('');
    }

    var marcas = $('[data-marcas]');

    if (marcas) {
        marcas.innerHTML = MARCAS.map(function (marca) {
            return '<li class="marca-aliada"><strong>' + texto(marca.nombre) + '</strong><span>' + texto(marca.rubro) + '</span></li>';
        }).join('');
    }

    /* ---------------------------------------------------------------------
     * Buscador de la cabecera (con sugerencias y limpieza)
     * ------------------------------------------------------------------- */

    function pintarSugerencias(formulario, consulta) {
        var lista = $('[data-sugerencias]', formulario);

        if (!lista) {
            return;
        }

        var aguja = consulta.trim().toLowerCase();

        if (aguja.length < 2) {
            lista.hidden = true;
            lista.innerHTML = '';

            return;
        }

        var encontrados = PRODUCTOS.filter(function (producto) {
            var categoria = buscarCategoria(producto.categoria);

            return (producto.nombre + ' ' + (categoria ? categoria.nombre : '')).toLowerCase().indexOf(aguja) !== -1;
        }).slice(0, 6);

        if (encontrados.length === 0) {
            lista.innerHTML = '<li class="sugerencia sugerencia--vacia">Sin coincidencias para «' + texto(consulta) + '». Prueba con «audio», «cocina» o «juguetes».</li>';
        } else {
            lista.innerHTML = encontrados.map(function (producto) {
                return '<li class="sugerencia"><a href="producto.html?p=' + texto(producto.clave) + '">'
                    + '<img src="' + texto(producto.foto) + '" alt="" width="48" height="48" loading="lazy">'
                    + '<span><strong>' + texto(producto.nombre) + '</strong><small>' + soles(producto.precio) + '</small></span>'
                    + '</a></li>';
            }).join('');
        }

        lista.hidden = false;
    }

    $$('[data-buscador]').forEach(function (formulario) {
        var campo = $('input[type="search"]', formulario);

        if (!campo) {
            return;
        }

        campo.addEventListener('input', function () {
            pintarSugerencias(formulario, campo.value);
        });

        campo.addEventListener('focus', function () {
            if (campo.value.trim().length >= 2) {
                pintarSugerencias(formulario, campo.value);
            }
        });

        formulario.addEventListener('submit', function (evento) {
            // Con JavaScript el buscador lleva al catálogo ya filtrado; el
            // formulario sin JavaScript hace lo mismo por GET.
            evento.preventDefault();
            window.location.href = 'catalogo.html?q=' + encodeURIComponent(campo.value.trim());
        });
    });

    document.addEventListener('click', function (evento) {
        if (!evento.target.closest('[data-buscador]')) {
            $$('[data-sugerencias]').forEach(function (lista) {
                lista.hidden = true;
            });
        }
    });

    /* ---------------------------------------------------------------------
     * Catálogo: filtros, orden, estado vacío
     * ------------------------------------------------------------------- */

    var catalogo = $('[data-catalogo]');

    function parametros() {
        return new URLSearchParams(window.location.search);
    }

    var ORDENES = {
        relevancia: 'Relevancia',
        'precio-asc': 'Precio: de menor a mayor',
        'precio-desc': 'Precio: de mayor a menor',
        nombre: 'Nombre: de la A a la Z',
        valoracion: 'Mejor valorados',
    };

    function pintarCatalogo() {
        if (!catalogo) {
            return;
        }

        var params = parametros();
        var categoria = params.get('categoria') || '';
        var coleccion = params.get('coleccion') || '';
        var consulta = (params.get('q') || '').trim();
        var orden = params.get('orden') || 'relevancia';
        var maximo = parseFloat(params.get('max')) || 0;
        var soloFavoritos = params.get('favoritos') === '1';
        var soloOfertas = params.get('oferta') === '1';

        var lista = PRODUCTOS.filter(function (producto) {
            var nombreCategoria = buscarCategoria(producto.categoria);

            if (categoria && producto.categoria !== categoria) {
                return false;
            }

            if (coleccion && producto.coleccion !== coleccion) {
                return false;
            }

            if (soloOfertas && !producto.antes) {
                return false;
            }

            if (consulta) {
                var heno = (producto.nombre + ' ' + producto.resumen + ' ' + (nombreCategoria ? nombreCategoria.nombre : '')).toLowerCase();

                if (heno.indexOf(consulta.toLowerCase()) === -1) {
                    return false;
                }
            }

            if (maximo > 0 && producto.precio > maximo) {
                return false;
            }

            if (soloFavoritos && favoritos.indexOf(producto.clave) === -1) {
                return false;
            }

            return true;
        });

        var comparadores = {
            'precio-asc': function (a, b) { return a.precio - b.precio; },
            'precio-desc': function (a, b) { return b.precio - a.precio; },
            nombre: function (a, b) { return a.nombre.localeCompare(b.nombre, 'es'); },
            valoracion: function (a, b) { return b.valoracion - a.valoracion; },
        };

        if (comparadores[orden]) {
            lista = lista.slice().sort(comparadores[orden]);
        } else {
            // «Relevancia» en esta demostración: primero lo que está en oferta.
            lista = lista.slice().sort(function (a, b) {
                return (b.antes ? 1 : 0) - (a.antes ? 1 : 0);
            });
        }

        pintarProductos($('[data-catalogo-rejilla]'), lista);

        var titulo = categoria ? buscarCategoria(categoria).nombre : 'Todo el catálogo';

        if (coleccion) {
            titulo = coleccion;
        }

        if (soloOfertas) {
            titulo = 'Ofertas de demostración';
        }

        if (soloFavoritos) {
            titulo = 'Mis favoritos';
        }

        var cuenta = $('[data-catalogo-cuenta]');

        if (cuenta) {
            cuenta.textContent = lista.length === 0
                ? 'Ningún producto coincide con el filtro'
                : lista.length + (lista.length === 1 ? ' producto' : ' productos') + ' de demostración' + (consulta ? ' para «' + consulta + '»' : '');
        }

        var encabezado = $('[data-catalogo-titulo]');

        if (encabezado) {
            encabezado.textContent = titulo;
        }

        var vacio = $('[data-catalogo-vacio]');

        if (vacio) {
            vacio.hidden = lista.length !== 0;
        }

        // Controles en consonancia con la URL: así se puede compartir el enlace
        // y al volver atrás la página se ve como estaba.
        $$('[data-filtro-categoria]').forEach(function (enlace) {
            var valor = enlace.getAttribute('data-filtro-categoria');
            var activo = valor === categoria;

            enlace.classList.toggle('esta-activo', activo);
            enlace.setAttribute('aria-current', activo ? 'true' : 'false');
        });

        $$('[data-filtro-campo]').forEach(function (nodo) {
            nodo.value = consulta;
        });

        var selectorOrden = $('[data-filtro-orden]');

        if (selectorOrden) {
            selectorOrden.value = orden;
        }

        var campoMaximo = $('[data-filtro-max]');

        if (campoMaximo) {
            campoMaximo.value = maximo > 0 ? String(maximo) : '';
        }

        var botonFavoritos = $('[data-filtro-favoritos]');

        if (botonFavoritos) {
            botonFavoritos.setAttribute('aria-pressed', soloFavoritos ? 'true' : 'false');
            botonFavoritos.classList.toggle('esta-activo', soloFavoritos);
        }

        var botonOfertas = $('[data-filtro-oferta]');

        if (botonOfertas) {
            botonOfertas.setAttribute('aria-pressed', soloOfertas ? 'true' : 'false');
            botonOfertas.classList.toggle('esta-activo', soloOfertas);
        }

        var limpiar = $('[data-filtros-limpiar]');

        if (limpiar) {
            limpiar.hidden = !categoria && !coleccion && !consulta && !maximo && !soloFavoritos && !soloOfertas;
        }

        var avisoFiltro = $('[data-catalogo-coleccion]');

        if (avisoFiltro) {
            avisoFiltro.hidden = !coleccion;
            avisoFiltro.textContent = coleccion ? 'Colección: ' + coleccion : '';
        }

        var migaja = $('[data-migaja-categoria]');

        if (migaja) {
            migaja.textContent = categoria ? buscarCategoria(categoria).nombre : 'Catálogo';
        }
    }

    function navegarCon(cambios) {
        var params = parametros();

        Object.keys(cambios).forEach(function (llave) {
            var valor = cambios[llave];

            if (valor === null || valor === '') {
                params.delete(llave);
            } else {
                params.set(llave, valor);
            }
        });

        var cadena = params.toString();
        window.location.href = 'catalogo.html' + (cadena ? '?' + cadena : '');
    }

    if (catalogo) {
        var selectorOrden = $('[data-filtro-orden]');

        if (selectorOrden) {
            selectorOrden.addEventListener('change', function () {
                navegarCon({ orden: selectorOrden.value });
            });
        }

        var campoMaximo = $('[data-filtro-max]');

        if (campoMaximo) {
            campoMaximo.addEventListener('change', function () {
                navegarCon({ max: campoMaximo.value });
            });
        }

        var botonFavoritos = $('[data-filtro-favoritos]');

        if (botonFavoritos) {
            botonFavoritos.addEventListener('click', function () {
                var activo = parametros().get('favoritos') === '1';
                navegarCon({ favoritos: activo ? null : '1' });
            });
        }

        var botonOfertas = $('[data-filtro-oferta]');

        if (botonOfertas) {
            botonOfertas.addEventListener('click', function () {
                var activo = parametros().get('oferta') === '1';
                navegarCon({ oferta: activo ? null : '1' });
            });
        }

        // Los enlaces de colección del mega panel limpian la categoría: una
        // colección pertenece a una sola categoría y dejar las dos puestas puede
        // dar una búsqueda sin resultados. Idem al revés.
        $$('[data-filtro-coleccion]').forEach(function (enlace) {
            enlace.addEventListener('click', function (evento) {
                evento.preventDefault();
                navegarCon({
                    categoria: enlace.getAttribute('data-filtro-categoria-padre') || null,
                    coleccion: enlace.getAttribute('data-filtro-coleccion'),
                    q: null,
                });
            });
        });

        // Cualquier cambio de categoría deja la colección fuera: si no, el
        // filtro de arriba y el de abajo se contradicen.
        $$('[data-filtro-categoria]').forEach(function (enlace) {
            enlace.addEventListener('click', function (evento) {
                evento.preventDefault();
                navegarCon({ categoria: enlace.getAttribute('data-filtro-categoria'), coleccion: null });
            });
        });

        var campoBusqueda = $('[data-filtro-busqueda]');

        if (campoBusqueda) {
            campoBusqueda.addEventListener('submit', function (evento) {
                evento.preventDefault();

                var campo = $('input', campoBusqueda);
                navegarCon({ q: campo.value.trim() });
            });
        }

        var limpiar = $('[data-filtros-limpiar]');

        if (limpiar) {
            limpiar.addEventListener('click', function (evento) {
                evento.preventDefault();
                window.location.href = 'catalogo.html';
            });
        }
    }

    /* ---------------------------------------------------------------------
     * Ficha de producto
     * ------------------------------------------------------------------- */

    var ficha = $('[data-ficha]');

    if (ficha) {
        var clavePedida = parametros().get('p') || (PRODUCTOS[0] ? PRODUCTOS[0].clave : '');
        var producto = buscarProducto(clavePedida);

        if (!producto) {
            $('#contenido').innerHTML = '<section class="seccion"><div class="contenedor">'
                + '<h1>Ese producto no está en la demostración</h1>'
                + '<p>Vuelve al <a href="catalogo.html">catálogo</a> para ver los productos de ejemplo.</p>'
                + '</div></section>';
        } else {
            document.title = producto.nombre + ' — NÉXORA Market · Demostración de plantilla';

            var categoria = buscarCategoria(producto.categoria);
            var ahorro = producto.antes ? redondear(producto.antes - producto.precio) : 0;

            $('[data-ficha-migaja]').innerHTML = '<a href="index.html">Inicio</a>'
                + '<span aria-hidden="true">/</span>'
                + '<a href="catalogo.html?categoria=' + texto(producto.categoria) + '">' + texto(categoria.nombre) + '</a>'
                + '<span aria-hidden="true">/</span>'
                + '<span>' + texto(producto.nombre) + '</span>';

            $('[data-ficha-foto]').src = producto.foto;
            $('[data-ficha-foto]').alt = producto.nombre;
            $('[data-ficha-categoria]').textContent = categoria.nombre;
            $('[data-ficha-categoria]').href = 'catalogo.html?categoria=' + producto.categoria;
            $('[data-ficha-nombre]').textContent = producto.nombre;
            $('[data-ficha-valoracion]').innerHTML = estrellas(producto.valoracion)
                + '<span>' + producto.valoracion.toFixed(1) + ' · ' + producto.opiniones + ' opiniones de ejemplo</span>';
            $('[data-ficha-resumen]').textContent = producto.resumen;
            $('[data-ficha-precio]').textContent = soles(producto.precio);
            $('[data-ficha-estado]').innerHTML = '<span class="punto ' + (producto.stock === 'En stock' ? 'punto--verde' : 'punto--naranja') + '"></span>'
                + texto(producto.stock) + ' · ' + texto(producto.envio);

            var antes = $('[data-ficha-antes]');

            if (producto.antes) {
                antes.innerHTML = 'Antes ' + soles(producto.antes) + ' <em>Ahorras ' + soles(ahorro) + '</em>';
                antes.hidden = false;
            }

            var etiqueta = $('[data-ficha-etiqueta]');

            if (producto.etiqueta) {
                etiqueta.textContent = producto.etiqueta === 'Oferta' ? 'Precio de demostración rebajado' : 'Novedad de demostración';
                etiqueta.hidden = false;
            }

            $('[data-ficha-detalles]').innerHTML = producto.detalles.map(function (fila) {
                return '<tr><th scope="row">' + texto(fila[0]) + '</th><td>' + texto(fila[1]) + '</td></tr>';
            }).join('');

            // Ojo: el botón de la ficha NO lleva `data-anadir`. El gestor global
            // de la página añade una unidad a cualquier `[data-anadir]`, así que
            // con el atributo puesto y su propio manejador se añadirían dos
            // veces (una con la cantidad escrita y otra con una unidad).
            $('[data-ficha-favorito]').setAttribute('data-favorito', producto.clave);
            $('[data-ficha-favorito]').setAttribute('data-favorito-nombre', producto.nombre);
            $('[data-ficha-whatsapp]').href = 'https://wa.me/' + WHATSAPP + '?text=' + encodeURIComponent('Hola NÉXORA Market (demostración): quiero consultar por ' + producto.nombre + '.');

            var cantidad = $('[data-ficha-cantidad]');

            $('[data-ficha-menos]').addEventListener('click', function () {
                cantidad.value = String(Math.max(1, parseInt(cantidad.value, 10) - 1 || 1));
            });

            $('[data-ficha-mas]').addEventListener('click', function () {
                cantidad.value = String(Math.min(99, (parseInt(cantidad.value, 10) || 1) + 1));
            });

            $('[data-ficha-anadir]').addEventListener('click', function (evento) {
                evento.preventDefault();
                anadir(producto.clave, parseInt(cantidad.value, 10) || 1);
            });

            var relacionadas = PRODUCTOS.filter(function (otro) {
                return otro.categoria === producto.categoria && otro.clave !== producto.clave;
            }).slice(0, 4);

            pintarProductos($('[data-ficha-relacionados]'), relacionadas);
        }
    }

    /* ---------------------------------------------------------------------
     * Página de canasta: líneas, resumen y pedido simulado
     * ------------------------------------------------------------------- */

    var envioElegido = 'recojo';

    function pintarPaginaCanasta() {
        var lista = $('[data-carrito-lineas]');

        if (!lista) {
            return;
        }

        lista.innerHTML = lineas().map(function (linea) {
            return '<li class="carrito-linea" data-linea="' + texto(linea.producto.clave) + '">'
                + '<img src="' + texto(linea.producto.foto) + '" alt="' + texto(linea.producto.nombre) + '" width="800" height="800" loading="lazy">'
                + '<div class="carrito-linea__datos">'
                + '<p class="carrito-linea__categoria">' + texto(buscarCategoria(linea.producto.categoria).nombre) + '</p>'
                + '<h2 class="carrito-linea__nombre"><a href="producto.html?p=' + texto(linea.producto.clave) + '">' + texto(linea.producto.nombre) + '</a></h2>'
                + '<p class="carrito-linea__estado">' + texto(linea.producto.envio) + ' · ' + texto(linea.producto.stock) + '</p>'
                + '<button class="enlace-boton" type="button" data-linea-quitar>Quitar de la canasta</button>'
                + '</div>'
                + '<div class="carrito-linea__importe">'
                + '<p class="carrito-linea__precio">' + soles(linea.producto.precio) + '</p>'
                + '<div class="cantidad">'
                + '<button type="button" data-cantidad-menos aria-label="Quitar una unidad de ' + texto(linea.producto.nombre) + '">−</button>'
                + '<input type="text" inputmode="numeric" value="' + linea.cantidad + '" data-cantidad-valor aria-label="Unidades de ' + texto(linea.producto.nombre) + '">'
                + '<button type="button" data-cantidad-mas aria-label="Añadir una unidad de ' + texto(linea.producto.nombre) + '">+</button>'
                + '</div>'
                + '<p class="carrito-linea__total">' + soles(redondear(linea.producto.precio * linea.cantidad)) + '</p>'
                + '</div>'
                + '</li>';
        }).join('');

        var subtotal = subtotalCanasta();
        var envio = envioElegido === 'recojo' ? 0 : (subtotal >= 199 ? 0 : 15);
        var total = redondear(subtotal + envio);

        var nodoSubtotal = $('[data-resumen-subtotal]');

        if (nodoSubtotal) {
            nodoSubtotal.textContent = soles(subtotal);
        }

        var nodoEnvio = $('[data-resumen-envio]');

        if (nodoEnvio) {
            nodoEnvio.textContent = envio === 0 ? (envioElegido === 'recojo' ? 'Recojo en tienda' : 'Gratis') : soles(envio);
        }

        var nodoTotal = $('[data-resumen-total]');

        if (nodoTotal) {
            nodoTotal.textContent = soles(total);
        }

        var nodoUnidades = $('[data-resumen-unidades]');

        if (nodoUnidades) {
            var unidades = unidadesEnCanasta();
            nodoUnidades.textContent = unidades + (unidades === 1 ? ' artículo' : ' artículos');
        }

        var nota = $('[data-resumen-nota]');

        if (nota) {
            nota.hidden = !(envioElegido === 'envio' && subtotal > 0 && subtotal < 199);
        }
    }

    $$('[data-envio]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            envioElegido = radio.value;
            pintarPaginaCanasta();
        });
    });

    var pedido = $('[data-pedido]');

    if (pedido) {
        pedido.addEventListener('submit', function (evento) {
            evento.preventDefault();

            var campos = [
                { nodo: $('[data-campo-nombre]'), validar: function (valor) { return valor.trim().length >= 3; }, mensaje: 'Escribe tu nombre completo.' },
                { nodo: $('[data-campo-correo]'), validar: function (valor) { return /^[^\s@]+@[^\s@]+\.[a-z]{2,}$/i.test(valor.trim()); }, mensaje: 'Revisa el formato del correo.' },
                { nodo: $('[data-campo-telefono]'), validar: function (valor) { return /^[0-9\s-]{6,15}$/.test(valor.trim()); }, mensaje: 'Escribe un teléfono de 6 a 15 dígitos.' },
            ];

            var correcto = true;

            campos.forEach(function (campo) {
                var error = $('[data-error-' + campo.nodo.getAttribute('data-campo') + ']', pedido);
                var valido = campo.validar(campo.nodo.value);

                campo.nodo.classList.toggle('esta-error', !valido);
                campo.nodo.setAttribute('aria-invalid', valido ? 'false' : 'true');

                if (error) {
                    error.textContent = valido ? '' : campo.mensaje;
                    error.hidden = valido;
                }

                if (!valido && correcto) {
                    campo.nodo.focus();
                }

                correcto = correcto && valido;
            });

            if (!correcto || canasta.length === 0) {
                return;
            }

            var subtotal = subtotalCanasta();
            var envio = envioElegido === 'recojo' ? 0 : (subtotal >= 199 ? 0 : 15);
            var total = redondear(subtotal + envio);
            var nombre = $('[data-campo-nombre]').value.trim();
            var detalle = lineas().map(function (linea) {
                return '· ' + linea.cantidad + ' × ' + linea.producto.nombre + ' — ' + soles(redondear(linea.producto.precio * linea.cantidad));
            }).join('\n');

            var mensaje = 'Pedido simulado — NÉXORA Market (demostración)\n'
                + 'Cliente: ' + nombre + '\n'
                + 'Entrega: ' + (envioElegido === 'recojo' ? 'Recojo en tienda' : 'Envío a domicilio') + '\n\n'
                + detalle + '\n\n'
                + 'Subtotal: ' + soles(subtotal) + '\n'
                + 'Envío: ' + (envio === 0 ? 'sin costo (ejemplo)' : soles(envio)) + '\n'
                + 'Total de demostración: ' + soles(total);

            $('[data-comprobante]').hidden = false;
            $('[data-comprobante-detalle]').textContent = mensaje;
            $('[data-comprobante-whatsapp]').href = 'https://wa.me/' + WHATSAPP + '?text=' + encodeURIComponent(mensaje);
            $('[data-comprobante]').scrollIntoView({ behavior: 'smooth', block: 'center' });
        });

        var reiniciar = $('[data-comprobante-cerrar]');

        if (reiniciar) {
            reiniciar.addEventListener('click', function () {
                $('[data-comprobante]').hidden = true;
            });
        }
    }

    var vaciarBoton = $('[data-canasta-vaciar]');

    if (vaciarBoton) {
        vaciarBoton.addEventListener('click', function () {
            vaciar();
        });
    }

    /* ---------------------------------------------------------------------
     * Boletín y pie
     * ------------------------------------------------------------------- */

    var boletin = $('[data-boletin]');

    if (boletin) {
        boletin.addEventListener('submit', function (evento) {
            evento.preventDefault();

            var campo = $('input[type="email"]', boletin);
            var mensaje = $('[data-boletin-mensaje]');
            var valido = /^[^\s@]+@[^\s@]+\.[a-z]{2,}$/i.test(campo.value.trim());

            campo.classList.toggle('esta-error', !valido);
            campo.setAttribute('aria-invalid', valido ? 'false' : 'true');
            mensaje.classList.toggle('esta-error', !valido);
            mensaje.textContent = valido
                ? 'Listo: ' + campo.value.trim() + ' quedaría suscrito en la tienda real. Esta demostración no envía ni guarda correos.'
                : 'Revisa el correo: falta el arroba o el dominio.';

            if (valido) {
                campo.value = '';
            }
        });
    }

    $$('[data-anio]').forEach(function (nodo) {
        nodo.textContent = String(new Date().getFullYear());
    });

    var cerrarAviso = $('[data-aviso-cerrar]');

    if (cerrarAviso) {
        cerrarAviso.addEventListener('click', function () {
            $('[data-aviso]').hidden = true;
        });
    }

    /* ---------------------------------------------------------------------
     * Cabecera: menú móvil, mega panel y sombra al bajar
     * ------------------------------------------------------------------- */

    var menuBoton = $('[data-menu-boton]');
    var menu = $('[data-nav]');

    if (menuBoton && menu) {
        menuBoton.addEventListener('click', function () {
            var abierto = menuBoton.getAttribute('aria-expanded') === 'true';

            menuBoton.setAttribute('aria-expanded', abierto ? 'false' : 'true');
            menu.classList.toggle('esta-abierto', !abierto);
        });
    }

    var megaBoton = $('[data-mega-boton]');
    var megaPanel = $('[data-mega]');

    function cerrarMega() {
        if (megaPanel && megaBoton) {
            megaPanel.classList.remove('esta-abierto');
            megaBoton.setAttribute('aria-expanded', 'false');
        }
    }

    if (megaBoton && megaPanel) {
        megaBoton.addEventListener('click', function () {
            var abierto = megaPanel.classList.contains('esta-abierto');

            megaPanel.classList.toggle('esta-abierto', !abierto);
            megaBoton.setAttribute('aria-expanded', abierto ? 'false' : 'true');
        });
    }

    var cabecera = $('[data-cabecera]');

    function sombraCabecera() {
        if (cabecera) {
            cabecera.classList.toggle('esta-fija', window.scrollY > 12);
        }
    }

    window.addEventListener('scroll', sombraCabecera, { passive: true });
    sombraCabecera();

    /* ---------------------------------------------------------------------
     * Aparición al hacer scroll (respeta prefers-reduced-motion)
     * ------------------------------------------------------------------- */

    var movimientoReducido = window.matchMedia('(prefers-reduced-motion: reduce)');

    function mostrarTodo() {
        $$('[data-aparece]').forEach(function (nodo) {
            nodo.classList.add('esta-visible');
        });
    }

    if (movimientoReducido.matches || !('IntersectionObserver' in window)) {
        mostrarTodo();
    } else {
        var observador = new IntersectionObserver(function (entradas) {
            entradas.forEach(function (entrada) {
                if (entrada.isIntersecting) {
                    entrada.target.classList.add('esta-visible');
                    observador.unobserve(entrada.target);
                }
            });
        }, { rootMargin: '0px 0px -12% 0px' });

        $$('[data-aparece]').forEach(function (nodo) {
            observador.observe(nodo);
        });
    }

    movimientoReducido.addEventListener('change', function () {
        if (movimientoReducido.matches) {
            mostrarTodo();
        }
    });

    /* ---------------------------------------------------------------------
     * Carrusel de la portada
     * ------------------------------------------------------------------- */

    var carrusel = $('[data-carrusel]');

    if (carrusel) {
        var diapositivas = $$('[data-diapositiva]', carrusel);
        var puntos = $$('[data-carrusel-punto]', carrusel);
        var actual = 0;
        var temporizador = null;
        var pausado = false;

        function mostrar(indice) {
            actual = (indice + diapositivas.length) % diapositivas.length;

            diapositivas.forEach(function (diapositiva, i) {
                diapositiva.classList.toggle('esta-activa', i === actual);
                diapositiva.setAttribute('aria-hidden', i === actual ? 'false' : 'true');
            });

            puntos.forEach(function (punto, i) {
                punto.classList.toggle('esta-activo', i === actual);
                punto.setAttribute('aria-current', i === actual ? 'true' : 'false');
            });
        }

        function avanzar(paso) {
            mostrar(actual + paso);
        }

        function arrancar() {
            // El paso automático se apaga con `prefers-reduced-motion`: a mano
            // el carrusel sigue funcionando igual.
            if (movimientoReducido.matches || temporizador !== null) {
                return;
            }

            temporizador = window.setInterval(function () {
                if (!pausado) {
                    avanzar(1);
                }
            }, 7000);
        }

        function detener() {
            window.clearInterval(temporizador);
            temporizador = null;
        }

        $$('[data-carrusel-anterior]', carrusel).forEach(function (boton) {
            boton.addEventListener('click', function () {
                avanzar(-1);
            });
        });

        $$('[data-carrusel-siguiente]', carrusel).forEach(function (boton) {
            boton.addEventListener('click', function () {
                avanzar(1);
            });
        });

        puntos.forEach(function (punto) {
            punto.addEventListener('click', function () {
                mostrar(parseInt(punto.getAttribute('data-carrusel-punto'), 10) || 0);
            });
        });

        carrusel.addEventListener('keydown', function (evento) {
            if (evento.key === 'ArrowLeft') {
                avanzar(-1);
            } else if (evento.key === 'ArrowRight') {
                avanzar(1);
            }
        });

        // Arrastre con el dedo.
        var inicioX = null;

        carrusel.addEventListener('pointerdown', function (evento) {
            inicioX = evento.clientX;
        });

        carrusel.addEventListener('pointerup', function (evento) {
            if (inicioX === null) {
                return;
            }

            var distancia = evento.clientX - inicioX;
            inicioX = null;

            if (Math.abs(distancia) > 48) {
                avanzar(distancia < 0 ? 1 : -1);
            }
        });

        ['mouseenter', 'focusin'].forEach(function (evento) {
            carrusel.addEventListener(evento, function () {
                pausado = true;
            });
        });

        ['mouseleave', 'focusout'].forEach(function (evento) {
            carrusel.addEventListener(evento, function () {
                pausado = false;
            });
        });

        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                detener();
            } else {
                arrancar();
            }
        });

        window.addEventListener('resize', function () {
            if (movimientoReducido.matches) {
                detener();
            } else {
                arrancar();
            }
        });

        mostrar(0);
        arrancar();
    }

    /* ---------------------------------------------------------------------
     * Arranque
     * ------------------------------------------------------------------- */

    document.documentElement.classList.add('js');

    pintarFavoritos();
    pintarCanasta();
    pintarCatalogo();
})();
