/*
 * VERDIA Market — Datos de la demostración «Supermercado Online».
 *
 * Este archivo es el catálogo. En una tienda real los productos, los precios y
 * las existencias llegan de la base de datos y del sistema del negocio; aquí
 * viven en un archivo para que la demostración funcione sin servidor.
 *
 * Todo lo que sale de aquí es ficticio y así está declarado en la barra de
 * aviso de cada página y en el pie.
 *
 * Los montos son soles y no incluyen IGV: son de referencia, para que se vea el
 * diseño con cifras razonables de un minimarket peruano. La presentación se
 * escribe como se lee en la góndola («1 kg», «docena», «pack 4»).
 *
 * `grupo` es la familia con la que se arman las pestañas de «Lo más vendido»;
 * `rubro` es el pasillo de la tienda (el mismo de las nueve tarjetas de
 * categoría) y es lo que filtra el catálogo. Un producto puede compartir rubro
 * y grupo, pero no es obligatorio: los huevos son de Lácteos y del grupo
 * Lácteos y huevos, mientras las fresas son del rubro Frutas y verduras y del
 * grupo Frutas.
 *
 * `antes` solo se escribe cuando hay precio anterior que tachar, y eso es lo
 * que marca la etiqueta promocional. No hay descuentos calculados ni cuentas
 * atrás: la demostración no simula urgencia comercial.
 */

var VERDIA_TIENDA = {
    nombre: 'VERDIA Market',
    lema: 'Productos frescos y ofertas para todos los días',
    whatsapp: '51900000000',
    correo: 'hola@verdiamarket.pe',
    direccion: 'Av. Los Fresnos 245, Nuevo Chimbote, Áncash',
    horario: 'Lunes a domingo, 7:00 a 22:00',
    envioGratis: 79,
    aviso: 'VERDIA Market es una demostración de plantilla. Los productos, precios, promociones, datos de contacto y pedidos son ficticios.',
    claves: {
        tema: 'supermercado-online-verdia-tema',
        canasta: 'supermercado-online-verdia-canasta'
    }
};

/* Los nueve pasillos. `foto` es la imagen de la tarjeta de categoría. */
var VERDIA_RUBROS = [
    {
        clave: 'frutas',
        nombre: 'Frutas y verduras',
        foto: 'img/rubro-frutas.webp',
        alt: 'Puesto de frutas con cajas de manzanas, plátanos y naranjas alineadas',
        resumen: 'Lo que entra cada mañana: fruta de estación y verdura de huerta.'
    },
    {
        clave: 'lacteos',
        nombre: 'Lácteos y huevos',
        foto: 'img/rubro-lacteos.webp',
        alt: 'Huevos frescos de cáscara clara y marrón dentro de un cuenco de madera',
        resumen: 'Huevos de campo, quesos al corte y yogures de la semana.'
    },
    {
        clave: 'carnes',
        nombre: 'Carnes y pescados',
        foto: 'img/rubro-carnes.webp',
        alt: 'Mostrador de carnicería con cortes de res y cerdo sobre bandejas',
        resumen: 'Cortes al momento y pescado del día sobre hielo.'
    },
    {
        clave: 'panaderia',
        nombre: 'Panadería',
        foto: 'img/rubro-panaderia.webp',
        alt: 'Pan de campo rebanado sobre una tabla con mantequilla',
        resumen: 'Pan de masa madre y bollería horneada en tienda.'
    },
    {
        clave: 'abarrotes',
        nombre: 'Abarrotes',
        foto: 'img/rubro-abarrotes.webp',
        alt: 'Estantes de despensa con frascos y conservas ordenadas',
        resumen: 'Despensa de fondo: conservas, café, pastas y granos.'
    },
    {
        clave: 'bebidas',
        nombre: 'Bebidas',
        foto: 'img/rubro-bebidas.webp',
        alt: 'Mesa servida con vasos de jugo de naranja y panecillos',
        resumen: 'Jugos, aguas y bebidas para la semana.'
    },
    {
        clave: 'snacks',
        nombre: 'Snacks',
        foto: 'img/rubro-snacks.webp',
        alt: 'Galletas con especias y semillas repartidas sobre una superficie clara',
        resumen: 'Galletas, frutos secos y algo dulce para la lonchera.'
    },
    {
        clave: 'limpieza',
        nombre: 'Limpieza del hogar',
        foto: 'img/rubro-limpieza.webp',
        alt: 'Estante con botellas y frascos de productos de limpieza ordenados por color',
        resumen: 'Detergentes, lavavajillas y todo para dejar la casa lista.'
    },
    {
        clave: 'cuidado',
        nombre: 'Cuidado personal',
        foto: 'img/rubro-cuidado.webp',
        alt: 'Pastillas de jabón artesanal apiladas sobre una mesa de madera',
        resumen: 'Jabones, cuidado del cabello y lo de todos los días.'
    }
];

/*
 * Los veintiún productos de la demostración. `etiqueta` es la insignia que se
 * pinta sobre la foto y `destacado` decide qué sale en «Productos populares».
 */
var VERDIA_PRODUCTOS = [
    {
        slug: 'naranjas',
        nombre: 'Naranjas de jugo',
        rubro: 'frutas',
        grupo: 'frutas',
        presentacion: '1 kg · bolsa',
        precio: 5.9,
        antes: 7.5,
        foto: 'img/p-naranjas.webp',
        alt: 'Naranjas cortadas por la mitad dentro de una canasta',
        resumen: 'Jugosas y de cáscara fina: rinden casi un litro por kilo.',
        etiqueta: 'Oferta',
        destacado: true,
        detalle: 'Naranjas de valle, cosechadas en la semana y guardadas en frío desde que llegan al almacén. Se venden por kilo y se despachan en bolsa de papel.'
    },
    {
        slug: 'arandanos',
        nombre: 'Arándanos frescos',
        rubro: 'frutas',
        grupo: 'frutas',
        presentacion: '250 g · bandeja',
        precio: 12.9,
        antes: null,
        foto: 'img/p-arandanos.webp',
        alt: 'Bandejas de arándanos frescos en la feria',
        resumen: 'Bien firmes, dulces y con el punto justo de acidez.',
        etiqueta: 'Nuevo',
        destacado: false,
        detalle: 'Llegan en bandeja de 250 g con tapa, listos para llevar. Se recomienda guardarlos en frío y lavarlos justo antes de comerlos.'
    },
    {
        slug: 'platanos',
        nombre: 'Plátano de seda',
        rubro: 'frutas',
        grupo: 'frutas',
        presentacion: '1 kg',
        precio: 3.9,
        antes: null,
        foto: 'img/p-platanos.webp',
        alt: 'Racimos de plátano de seda apilados en un puesto',
        resumen: 'De los que se comen en la lonchera y en el desayuno.',
        etiqueta: null,
        destacado: false,
        detalle: 'Racimos de plátano de seda en su punto de madurez, para comer en dos o tres días. También hay plátano de la selva para freír.'
    },
    {
        slug: 'fresas',
        nombre: 'Fresas del valle',
        rubro: 'frutas',
        grupo: 'frutas',
        presentacion: '500 g · caja',
        precio: 8.9,
        antes: 10.9,
        foto: 'img/o-fresas.webp',
        alt: 'Fresas rojas dentro de un cuenco de vidrio',
        resumen: 'Rojas por completo, sin partes verdes, listas para el jugo.',
        etiqueta: 'Oferta de demostración',
        destacado: false,
        detalle: 'Fresas grandes de valle en caja de medio kilo. Se conservan hasta cuatro días en la parte menos fría del refrigerador.'
    },
    {
        slug: 'pepinos',
        nombre: 'Pepino criollo',
        rubro: 'frutas',
        grupo: 'verduras',
        presentacion: '1 kg',
        precio: 3.5,
        antes: null,
        foto: 'img/p-pepinos.webp',
        alt: 'Pepinos verdes apilados con su textura marcada',
        resumen: 'Firmes y con la cáscara pareja, para ensalada y salsas.',
        etiqueta: null,
        destacado: false,
        detalle: 'Pepino criollo de cáscara oscura y pulpa firme. Se despacha por kilo; cada unidad pesa entre 250 y 350 g.'
    },
    {
        slug: 'zanahorias',
        nombre: 'Zanahoria fresca',
        rubro: 'frutas',
        grupo: 'verduras',
        presentacion: '1 kg',
        precio: 2.8,
        antes: null,
        foto: 'img/p-zanahorias.webp',
        alt: 'Zanahorias con sus hojas verdes sobre una mesa oscura',
        resumen: 'Dulce y crocante, de las que se usan todos los días.',
        etiqueta: null,
        destacado: false,
        detalle: 'Zanahoria de primera, lavada y sin hojas cuando se despacha por kilo. Apta para guisos, jugos y sopas.'
    },
    {
        slug: 'tomates',
        nombre: 'Tomate italiano',
        rubro: 'frutas',
        grupo: 'verduras',
        presentacion: '1 kg',
        precio: 4.9,
        antes: 5.9,
        foto: 'img/p-tomates.webp',
        alt: 'Tomates pequeños dentro de un cuenco de barro',
        resumen: 'Pulpa densa y poca agua: ideal para salsa.',
        etiqueta: 'Oferta',
        destacado: true,
        detalle: 'Tomate italiano de pulpa carnosa, el preferido para salsa de mesa. Se vende por kilo y se puede pedir medio kilo al llegar al mostrador.'
    },
    {
        slug: 'judias',
        nombre: 'Judías verdes',
        rubro: 'frutas',
        grupo: 'verduras',
        presentacion: '500 g · bolsa',
        precio: 4.2,
        antes: 5.6,
        foto: 'img/o-judias.webp',
        alt: 'Judías verdes frescas amontonadas',
        resumen: 'Delgadas y sin hilos, se cocinan en cinco minutos.',
        etiqueta: 'Oferta de demostración',
        destacado: false,
        detalle: 'Judías verdes seleccionadas, sin hilos y de corte fino, en bolsa de medio kilo. También se venden por kilo en el mostrador de verduras.'
    },
    {
        slug: 'huevos',
        nombre: 'Huevos de campo',
        rubro: 'lacteos',
        grupo: 'lacteos',
        presentacion: 'docena',
        precio: 9.9,
        antes: null,
        foto: 'img/p-huevos.webp',
        alt: 'Huevos de cáscara azul y marrón dentro de una canasta metálica',
        resumen: 'De gallinas libres, con yema de color intenso.',
        etiqueta: 'Más vendido',
        destacado: true,
        detalle: 'Docena de huevos de gallinas de corral, con cáscara limpia y sin grietas. Se guardan en frío y se consumen dentro de dos semanas.'
    },
    {
        slug: 'yogur',
        nombre: 'Yogur natural con frutas',
        rubro: 'lacteos',
        grupo: 'lacteos',
        presentacion: 'pack 4 vasos',
        precio: 7.5,
        antes: null,
        foto: 'img/p-yogur.webp',
        alt: 'Vasos de yogur natural con fruta y granola encima',
        resumen: 'Sin azúcar añadida, con fruta en trozos visible.',
        etiqueta: null,
        destacado: false,
        detalle: 'Pack de cuatro vasos de yogur natural con trozos de fruta. Se entrega en frío y se conserva hasta la fecha que marca cada vaso.'
    },
    {
        slug: 'queso',
        nombre: 'Queso semiduro al corte',
        rubro: 'lacteos',
        grupo: 'lacteos',
        presentacion: '500 g',
        precio: 18.9,
        antes: null,
        foto: 'img/p-queso.webp',
        alt: 'Queso semiduro en cuñas con galletas saladas al costado',
        resumen: 'Textura firme, se corta en láminas sin desarmarse.',
        etiqueta: null,
        destacado: false,
        detalle: 'Queso semiduro madurado, al corte en el mostrador de lácteos. Se despacha en porciones de 250 g, 500 g o un kilo.'
    },
    {
        slug: 'pescado',
        nombre: 'Filete de pescado fresco',
        rubro: 'carnes',
        grupo: 'carnes',
        presentacion: '1 kg',
        precio: 24.9,
        antes: null,
        foto: 'img/p-pescado.webp',
        alt: 'Pescados enteros frescos colocados sobre hielo en el mostrador',
        resumen: 'Del día, sobre hielo, limpio y listo para cocinar.',
        etiqueta: null,
        destacado: false,
        detalle: 'Pescado fresco del día, eviscerado y en filetes si se pide. Sale del mostrador de frío directamente a la bolsa térmica.'
    },
    {
        slug: 'filete',
        nombre: 'Filete de res',
        rubro: 'carnes',
        grupo: 'carnes',
        presentacion: '1 kg',
        precio: 32.9,
        antes: 39.9,
        foto: 'img/o-filete.webp',
        alt: 'Filete de res crudo con romero y granos de pimienta al costado',
        resumen: 'Corte limpio, sin nervio y del grosor que se pida.',
        etiqueta: 'Oferta de demostración',
        destacado: false,
        detalle: 'Filete de res al corte, desgrasado y porcionado según lo que pida el cliente. Se entrega en frío y se recomienda cocinar el mismo día.'
    },
    {
        slug: 'pan',
        nombre: 'Pan de campo integral',
        rubro: 'panaderia',
        grupo: 'panaderia',
        presentacion: '800 g · hogaza',
        precio: 6.5,
        antes: null,
        foto: 'img/p-pan.webp',
        alt: 'Hogaza de pan integral cortada por la mitad con mantequilla',
        resumen: 'Miga húmeda y corteza firme, del horno de la tienda.',
        etiqueta: null,
        destacado: false,
        detalle: 'Hogaza de masa madre con harina integral, horneada todas las mañanas. Se corta al pedido y se conserva bien fuera del refrigerador.'
    },
    {
        slug: 'hogaza',
        nombre: 'Hogaza artesana',
        rubro: 'panaderia',
        grupo: 'panaderia',
        presentacion: '600 g',
        precio: 7.2,
        antes: null,
        foto: 'img/p-hogaza.webp',
        alt: 'Hogazas de pan rústico con semillas en una cesta de mimbre',
        resumen: 'Con semillas en la corteza y fermentación larga.',
        etiqueta: null,
        destacado: false,
        detalle: 'Hogaza de fermentación larga con semillas de girasol y amapola. Cada pieza se hornea el mismo día en que se vende.'
    },
    {
        slug: 'croissant',
        nombre: 'Croissants de mantequilla',
        rubro: 'panaderia',
        grupo: 'panaderia',
        presentacion: 'pack 4',
        precio: 8.4,
        antes: null,
        foto: 'img/p-croissant.webp',
        alt: 'Croissant dorado junto a una taza de café sobre una mesa oscura',
        resumen: 'De hojaldre, para el desayuno de la casa.',
        etiqueta: 'Nuevo',
        destacado: false,
        detalle: 'Pack de cuatro croissants de hojaldre con mantequilla, horneados en la bollería de la tienda. Se calientan en horno o air fryer.'
    },
    {
        slug: 'cafe',
        nombre: 'Café tostado en grano',
        rubro: 'abarrotes',
        grupo: 'abarrotes',
        presentacion: '500 g · bolsa',
        precio: 24.9,
        antes: null,
        foto: 'img/p-cafe.webp',
        alt: 'Granos de café tostado cubriendo toda la superficie',
        resumen: 'Tueste medio, con aroma a chocolate y panela.',
        etiqueta: null,
        destacado: true,
        detalle: 'Café de selva, tostado medio y envasado con válvula para que respire. Se muele en tienda si el cliente lo pide.'
    },
    {
        slug: 'pasta',
        nombre: 'Pasta fresca artesanal',
        rubro: 'abarrotes',
        grupo: 'abarrotes',
        presentacion: '500 g',
        precio: 9.8,
        antes: null,
        foto: 'img/p-pasta.webp',
        alt: 'Fideos frescos amontonados sobre una superficie enharinada',
        resumen: 'De sémola y huevo, se cocina en tres minutos.',
        etiqueta: null,
        destacado: false,
        detalle: 'Pasta fresca de sémola y huevo, del taller de la tienda. Se guarda en frío y se cocina dentro de tres días.'
    },
    {
        slug: 'salsa',
        nombre: 'Salsa de tomate casera',
        rubro: 'abarrotes',
        grupo: 'abarrotes',
        presentacion: '400 g · frasco',
        precio: 4.5,
        antes: 5.4,
        foto: 'img/o-tomate.webp',
        alt: 'Frasco de salsa de tomate con tomates y granos de pimienta alrededor',
        resumen: 'Tomate, cebolla y sal: sin aditivos.',
        etiqueta: 'Oferta de demostración',
        destacado: false,
        detalle: 'Salsa de tomate cocida a fuego lento con cebolla y especias, en frasco de vidrio. Una vez abierta se guarda en frío.'
    },
    {
        slug: 'galletas',
        nombre: 'Galletas de avena y canela',
        rubro: 'snacks',
        grupo: 'snacks',
        presentacion: '300 g',
        precio: 5.2,
        antes: null,
        foto: 'img/p-galletas.webp',
        alt: 'Galletas de avena con palos de canela y anís sobre una superficie clara',
        resumen: 'De horno, con avena entera y poca azúcar.',
        etiqueta: null,
        destacado: false,
        detalle: 'Galletas horneadas con avena entera, canela y miel de caña. Se venden en bolsa resellable de 300 g.'
    },
    {
        slug: 'cereal',
        nombre: 'Cereal de avena y miel',
        rubro: 'snacks',
        grupo: 'snacks',
        presentacion: '500 g · caja',
        precio: 11.9,
        antes: null,
        foto: 'img/p-cereal.webp',
        alt: 'Cereal de avena servido en un cuenco con leche',
        resumen: 'Copos de avena con miel, sin azúcar añadida.',
        etiqueta: null,
        destacado: false,
        detalle: 'Copos de avena endulzados con miel y tostados en horno. Se combinan con leche, yogur o fruta cortada.'
    }
];

/*
 * Los cuatro beneficios de la franja de compra. Son características de la
 * plantilla demostrativa, no servicios contratados.
 */
var VERDIA_BENEFICIOS = [
    {
        icono: 'cesta',
        titulo: 'Variedad de productos',
        texto: 'Nueve pasillos y más de mil ochocientos productos en un mismo sitio.'
    },
    {
        icono: 'rayo',
        titulo: 'Compra fácil y rápida',
        texto: 'Buscador con sugerencias, filtros por pasillo y lista de repetidos.'
    },
    {
        icono: 'moto',
        titulo: 'Opciones de entrega',
        texto: 'Recojo en tienda o entrega a domicilio con franjas horarias de ejemplo.'
    },
    {
        icono: 'escudo',
        titulo: 'Compra segura',
        texto: 'Cuenta con historial de pedidos y comprobantes guardados.'
    }
];

/*
 * Las cuatro selecciones de «Compra por necesidad». `rubros` filtra los
 * pasillos y `promos` deja solo lo que tiene precio anterior.
 */
var VERDIA_NECESIDADES = [
    {
        clave: 'comidas-rapidas',
        nombre: 'Comidas rápidas',
        texto: 'Algo listo para la cena de hoy.',
        foto: 'img/p-pan.webp',
        rubros: ['panaderia', 'snacks', 'abarrotes'],
        promos: false
    },
    {
        clave: 'saludable',
        nombre: 'Alimentación saludable',
        texto: 'Fruta, verdura y granos enteros.',
        foto: 'img/rubro-frutas.webp',
        rubros: ['frutas'],
        promos: false
    },
    {
        clave: 'ahorro',
        nombre: 'Ahorro familiar',
        texto: 'Lo que está en oferta esta semana.',
        foto: 'img/o-judias.webp',
        rubros: [],
        promos: true
    },
    {
        clave: 'familia',
        nombre: 'Para toda la familia',
        texto: 'Packs y formatos grandes rendidores.',
        foto: 'img/p-huevos.webp',
        rubros: ['lacteos', 'panaderia', 'snacks'],
        promos: false
    }
];

/* Las seis pestañas de «Lo más vendido», en el orden en que se pintan. */
var VERDIA_GRUPOS = [
    { clave: 'frutas', nombre: 'Frutas' },
    { clave: 'verduras', nombre: 'Verduras' },
    { clave: 'lacteos', nombre: 'Lácteos y huevos' },
    { clave: 'panaderia', nombre: 'Panadería' },
    { clave: 'carnes', nombre: 'Carnes y pescados' },
    { clave: 'abarrotes', nombre: 'Abarrotes' },
    { clave: 'snacks', nombre: 'Snacks' }
];
