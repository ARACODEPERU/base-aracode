/*
 * Casa Lume — JavaScript de la demostración «Tienda en línea».
 *
 * Un solo archivo para todo el sitio, sin dependencias y sin peticiones de
 * terceros. Lo que hace, por partes:
 *
 *   1. Tema claro / oscuro (misma interfaz que Pichanguero: atributo
 *      `data-tema`, `localStorage` con clave propia y aviso entre pestañas).
 *   2. Cabecera pegada, menú de móvil, aparición al bajar y contadores.
 *   3. Slider de la portada, manejable a mano, con teclado, con el dedo y
 *      apagado —el paso automático— cuando el visitante pide menos movimiento.
 *   4. La canasta: se guarda en el navegador, se cuenta en la cabecera, se
 *      revisa en un panel lateral y se cierra por WhatsApp con su detalle.
 *   5. El catálogo: filtros por categoría, precio y orden leídos de la URL.
 *   6. La ficha de producto, que se arma según `?p=` y comparte los datos de
 *      aquí abajo, y la página de la canasta con su formulario de pedido.
 *
 * Los datos del catálogo viven en este archivo porque la demostración no tiene
 * servidor: en una tienda real llegan de la base de datos del negocio.
 */

(function () {
    'use strict';

    document.documentElement.classList.add('js');

    var movimientoReducido = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ======================================================================
       Datos de la demostración
       ====================================================================== */

    var TIENDA = {
        nombre: 'Casa Lume',
        whatsapp: '51900000000',
        correo: 'hola@casalume.pe',
        direccion: 'Av. Santa Cruz 780, Miraflores, Lima',
        horario: 'Lunes a sábado, 10:00 a 21:00',
        envioGratis: 199,
        claves: {
            tema: 'tienda-online-tema',
            canasta: 'tienda-online-canasta'
        }
    };

    var CATEGORIAS = [
        { clave: 'cocina', nombre: 'Cocina' },
        { clave: 'mesa', nombre: 'Mesa' },
        { clave: 'textil', nombre: 'Textil' },
        { clave: 'orden', nombre: 'Orden' },
        { clave: 'deco', nombre: 'Deco' },
        { clave: 'bano', nombre: 'Baño' }
    ];

    var PRODUCTOS = [
        {
            slug: 'sarten-hierro',
            nombre: 'Sartén de hierro fundido',
            categoria: 'cocina',
            precio: 189,
            foto: 'img/producto-sarten-hierro.webp',
            alt: 'Sartén de hierro fundido con el mango hacia un lado sobre una superficie oscura',
            resumen: '26 cm. Se cura una vez y dura toda la vida.',
            descripcion: 'Hierro fundido de pared gruesa que reparte el calor parejo y aguanta el fuego alto sin deformarse. Viene curado de fábrica, así que se puede usar el mismo día: se lava, se seca y se le pasa una gota de aceite.',
            medidas: '26 cm de diámetro · 2,1 kg',
            materiales: 'Hierro fundido con mango de acero',
            cuidado: 'Lavar a mano con agua caliente y secar enseguida. Apta para horno y para todo tipo de cocina',
            hecho: 'Línea Cocina · Casa Lume',
            colores: [],
            tamanos: ['24 cm', '26 cm', '30 cm'],
            etiqueta: 'Más vendido',
            valoracion: 4.8,
            opiniones: 37,
            relacionados: ['tabla-cortar', 'cucharones-madera', 'organizador-cajon']
        },
        {
            slug: 'tabla-cortar',
            nombre: 'Tabla de cortar de madera',
            categoria: 'cocina',
            precio: 95,
            foto: 'img/pieza-tabla.webp',
            alt: 'Tabla de cortar de madera con hierbas encima',
            resumen: 'Madera aceitada, con canaleta para los jugos.',
            descripcion: 'Cortada de una sola pieza y aceitada con aceite de linaza, con la veta a la vista y una canaleta perimetral para que los jugos no lleguen a la mesa. Por un lado es tabla de cortar y por el otro, tabla de servir.',
            medidas: 'Mediana 34 × 20 cm · Grande 44 × 26 cm',
            materiales: 'Madera maciza, aceite de linaza',
            cuidado: 'Lavar a mano y secar de pie. Aceitar cada dos meses',
            hecho: 'Línea Cocina · Casa Lume',
            colores: [],
            tamanos: ['Mediana', 'Grande'],
            etiqueta: '',
            valoracion: 4.9,
            opiniones: 27,
            relacionados: ['cucharones-madera', 'sarten-hierro', 'taza-gres']
        },
        {
            slug: 'cucharones-madera',
            nombre: 'Cucharones de madera · juego de 3',
            categoria: 'cocina',
            precio: 59,
            foto: 'img/pieza-cucharones.webp',
            alt: 'Tres cucharones de madera dentro de un cuenco',
            resumen: 'Tres medidas: plano, hondo y de probar.',
            descripcion: 'Tres cucharones de madera de aliso: uno plano para el arroz, uno hondo para la olla y uno pequeño para probar. La madera no raya los recubrimientos de las ollas ni se calienta, y el mango llega a la base de la olla.',
            medidas: '30, 26 y 20 cm de largo',
            materiales: 'Madera de aliso, aceite de linaza',
            cuidado: 'Lavar a mano y secar enseguida',
            hecho: 'Línea Cocina · Casa Lume',
            colores: [],
            tamanos: [],
            etiqueta: '',
            valoracion: 4.8,
            opiniones: 15,
            relacionados: ['tabla-cortar', 'sarten-hierro', 'organizador-cajon']
        },
        {
            slug: 'taza-gres',
            nombre: 'Taza de gres · 320 ml',
            categoria: 'mesa',
            precio: 48,
            foto: 'img/pieza-taza.webp',
            alt: 'Taza de cerámica esmaltada sobre una mesa de madera',
            resumen: 'Gres esmaltado, con el asa ancha y el borde grueso.',
            descripcion: 'Gres cocido a alta temperatura, con el asa ancha para los dedos y el borde grueso que mantiene el calor. El esmalte es mate por fuera y brillante por dentro, y no se mancha con el café.',
            medidas: '9 cm de alto × 8 cm de diámetro · 320 ml',
            materiales: 'Gres con esmalte sin plomo',
            cuidado: 'Apta para lavavajillas. No usar en microondas ni en horno',
            hecho: 'Línea Mesa · Casa Lume',
            colores: ['Hueso', 'Nogal', 'Verde salvia'],
            tamanos: [],
            etiqueta: '',
            valoracion: 4.8,
            opiniones: 24,
            relacionados: ['vaso-vidrio', 'jarron-gres', 'tabla-cortar']
        },
        {
            slug: 'vaso-vidrio',
            nombre: 'Vaso de vidrio soplado',
            categoria: 'mesa',
            precio: 34,
            foto: 'img/producto-vaso-vidrio.webp',
            alt: 'Vaso de vidrio soplado con agua sobre una mesa',
            resumen: 'Soplado a boca, de pared fina y base gruesa. Se vende por unidad.',
            descripcion: 'Vidrio soplado a boca: cada vaso sale con su propia burbuja, así que la mesa se ve viva y no repetida. La pared es fina pero la base tiene peso, y se apilan para ocupar poco en el estante.',
            medidas: '350 ml · 10 cm de alto',
            materiales: 'Vidrio soplado a boca',
            cuidado: 'Lavar a mano con agua tibia. Apto para bebidas frías y calientes',
            hecho: 'Línea Mesa · Casa Lume',
            colores: [],
            tamanos: ['250 ml', '350 ml'],
            etiqueta: 'Se vende por unidad',
            valoracion: 4.6,
            opiniones: 31,
            relacionados: ['taza-gres', 'jarron-gres', 'sarten-hierro']
        },
        {
            slug: 'sabanas-lino',
            nombre: 'Juego de sábanas de lino',
            categoria: 'textil',
            precio: 459,
            antes: 549,
            foto: 'img/producto-sabanas-lino.webp',
            alt: 'Cama vestida con sábanas de lino claras junto a una ventana',
            resumen: 'Lino lavado: fresco en verano, templado en invierno y sin plancha.',
            descripcion: 'Lino europeo lavado a la piedra, que desde el primer día cae suelto y no necesita plancha. Regula la humedad mejor que el algodón y cada lavado lo deja más suave, no al revés.',
            medidas: 'Sábana bajera, encimera y dos fundas',
            materiales: '100 % lino europeo lavado a la piedra',
            cuidado: 'Lavar a 40 °C, secar a la sombra. No usar secadora',
            hecho: 'Línea Textil · Casa Lume',
            colores: ['Crudo', 'Arena', 'Verde oliva', 'Terracota'],
            tamanos: ['1½ plazas', '2 plazas', 'King'],
            etiqueta: 'Oferta de temporada',
            valoracion: 4.8,
            opiniones: 19,
            relacionados: ['manta-algodon', 'organizador-cajon', 'jarron-gres']
        },
        {
            slug: 'manta-algodon',
            nombre: 'Manta tejida de algodón',
            categoria: 'textil',
            precio: 149,
            foto: 'img/pieza-chal.webp',
            alt: 'Manta tejida de algodón doblada sobre una silla',
            resumen: 'Algodón peinado de hilo grueso, para el sofá y para la cama.',
            descripcion: 'Tejida en telar con algodón peinado de hilo grueso: ligera, pero con cuerpo. Va bien en el respaldo del sofá, al pie de la cama o como manta de verano, y no suelta pelusa ni se estira al lavarla.',
            medidas: '130 × 170 cm · 150 × 200 cm',
            materiales: '100 % algodón peinado',
            cuidado: 'Lavar a máquina en frío y secar en plano',
            hecho: 'Línea Textil · Casa Lume',
            colores: ['Crudo', 'Gris niebla', 'Verde monte'],
            tamanos: ['130 × 170 cm', '150 × 200 cm'],
            etiqueta: '',
            valoracion: 4.7,
            opiniones: 23,
            relacionados: ['sabanas-lino', 'cesta-fibra', 'taza-gres']
        },
        {
            slug: 'cesta-fibra',
            nombre: 'Cesta de fibra con tapa',
            categoria: 'orden',
            precio: 139,
            foto: 'img/pieza-canasta.webp',
            alt: 'Cesta de fibra tejida con tapa y asas',
            resumen: 'Con tapa y asas forradas: guarda sin que se vea lo guardado.',
            descripcion: 'Trenzada en fibra natural sobre una estructura firme, con la tapa a ras y las asas forradas para que no marquen las manos. Entra en el estante de abajo del aparador y aguanta mantas, juguetes o la compra.',
            medidas: '42 × 32 × 30 cm · 38 × 28 × 26 cm',
            materiales: 'Fibra natural trenzada y algodón',
            cuidado: 'Limpiar con paño seco. No dejar a la intemperie',
            hecho: 'Línea Orden · Casa Lume',
            colores: ['Natural', 'Crudo'],
            tamanos: ['Mediana', 'Grande'],
            etiqueta: 'Últimas unidades',
            valoracion: 4.8,
            opiniones: 16,
            relacionados: ['organizador-cajon', 'manta-algodon', 'sabanas-lino']
        },
        {
            slug: 'organizador-cajon',
            nombre: 'Organizador de cajón',
            categoria: 'orden',
            precio: 89,
            foto: 'img/producto-organizador-cajon.webp',
            alt: 'Cajón de cocina abierto con los cubiertos ordenados en sus divisiones',
            resumen: 'Con divisiones móviles: se rearma según lo que haya que guardar.',
            descripcion: 'Bambú macizo con divisiones que se mueven de sitio, del cajón de los cubiertos al de los cables del escritorio. Se apila sin tambalearse y el frente lleva un tirador para sacarlo de un tirón.',
            medidas: '30 × 20 × 12 cm',
            materiales: 'Bambú macizo y cuero curtido',
            cuidado: 'Limpiar con paño húmedo y secar. No sumergir',
            hecho: 'Línea Orden · Casa Lume',
            colores: [],
            tamanos: ['Pequeño', 'Mediano', 'Grande'],
            etiqueta: '',
            valoracion: 4.7,
            opiniones: 25,
            relacionados: ['cesta-fibra', 'cucharones-madera', 'sarten-hierro']
        },
        {
            slug: 'jarron-gres',
            nombre: 'Jarrón de gres mate',
            categoria: 'deco',
            precio: 119,
            foto: 'img/pieza-jarron.webp',
            alt: 'Jarrón de gres con ramas secas dentro',
            resumen: 'Acabado mate al tacto, para ramas secas o flores del día.',
            descripcion: 'Gres con esmalte mate, del tipo que se acaricia sin querer. El cuello estrecho sostiene las ramas largas sin relleno y el interior va vidriado, así que también aguanta agua con flores frescas.',
            medidas: '28 cm de alto × 15 cm de diámetro',
            materiales: 'Gres con exterior mate e interior vidriado',
            cuidado: 'Lavar a mano con agua tibia. No apto para lavavajillas',
            hecho: 'Línea Deco · Casa Lume',
            colores: ['Arena', 'Pizarra', 'Terracota'],
            tamanos: ['22 cm', '28 cm'],
            etiqueta: '',
            valoracion: 4.8,
            opiniones: 13,
            relacionados: ['maceta-ceramica', 'taza-gres', 'vaso-vidrio']
        },
        {
            slug: 'maceta-ceramica',
            nombre: 'Maceta de cerámica con plato',
            categoria: 'deco',
            precio: 79,
            foto: 'img/producto-maceta-ceramica.webp',
            alt: 'Maceta de cerámica con una planta verde dentro',
            resumen: 'Con agujero de drenaje y plato a juego, dentro y fuera de casa.',
            descripcion: 'Cerámica cocida a alta temperatura, con agujero de drenaje y plato encajado para que no quede agua en el mueble. El esmalte resiste el sol, así que sirve igual en la repisa del salón que en el balcón.',
            medidas: '18 cm de alto × 20 cm de diámetro · plato incluido',
            materiales: 'Cerámica esmaltada cocida a alta temperatura',
            cuidado: 'Se puede dejar en exterior. Vaciar el plato después de regar',
            hecho: 'Línea Deco · Casa Lume',
            colores: ['Blanco', 'Terracota', 'Verde salvia'],
            tamanos: ['14 cm', '18 cm', '24 cm'],
            etiqueta: 'Más vendido',
            valoracion: 4.9,
            opiniones: 31,
            relacionados: ['jarron-gres', 'cesta-fibra', 'taza-gres']
        },
        {
            slug: 'jabon-manos',
            nombre: 'Jabón de manos de oliva',
            categoria: 'bano',
            precio: 29,
            foto: 'img/pieza-jabon.webp',
            alt: 'Pastilla de jabón de oliva sobre una tabla',
            resumen: 'Saponificado en frío y curado cuatro semanas. Rinde el doble.',
            descripcion: 'Hecho por saponificación en frío y curado cuatro semanas. Con aceite de oliva y manteca de cacao, hace espuma cremosa y no reseca las manos, así que una pastilla dura bastante más que un jabón industrial.',
            medidas: '120 g por pastilla',
            materiales: 'Aceite de oliva, manteca de cacao, agua',
            cuidado: 'Guardar en lugar seco y dejar escurrir entre usos',
            hecho: 'Línea Baño · Casa Lume',
            colores: [],
            tamanos: [],
            aromas: ['Lavanda', 'Eucalipto', 'Neutro'],
            etiqueta: '',
            valoracion: 4.9,
            opiniones: 58,
            relacionados: ['taza-gres', 'jarron-gres', 'organizador-cajon']
        }
    ];

    // Entrega: las tres formas de recibir el pedido, con su tarifa de referencia.
    var ENTREGAS = [
        {
            clave: 'recojo',
            nombre: 'Recojo en la tienda',
            detalle: 'Av. Santa Cruz 780, Miraflores · desde mañana',
            tarifa: 0
        },
        {
            clave: 'lima',
            nombre: 'Delivery en Lima',
            detalle: '24 a 48 horas · gratis desde S/ 199',
            tarifa: 12
        },
        {
            clave: 'provincias',
            nombre: 'Envío a provincias',
            detalle: '3 a 5 días hábiles · agencia Olva o Shalom',
            tarifa: 22
        }
    ];

    var PAGOS = ['Yape', 'Plin', 'Transferencia', 'Tarjeta', 'Contra entrega en Lima'];

    /* ======================================================================
       Ayudas
       ====================================================================== */

    var qs = function (selector, raiz) {
        return (raiz || document).querySelector(selector);
    };

    var qsa = function (selector, raiz) {
        return [].slice.call((raiz || document).querySelectorAll(selector));
    };

    /** Precio con el formato de la tienda: S/ 1 250, sin decimales de relleno. */
    function precio(valor) {
        var numero = Number(valor) || 0;

        return 'S/ ' + numero.toLocaleString('es-PE', { maximumFractionDigits: 2 });
    }

    function escapar(texto) {
        return String(texto == null ? '' : texto)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    /**
     * Texto para buscar: en minúsculas y sin tildes. Quien escribe «ceramica»
     * tiene que encontrar «Cerámica», y quien escribe «Cerámica» también.
     */
    function normalizar(texto) {
        return String(texto == null ? '' : texto)
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    function producto(slug) {
        for (var i = 0; i < PRODUCTOS.length; i++) {
            if (PRODUCTOS[i].slug === slug) {
                return PRODUCTOS[i];
            }
        }

        return null;
    }

    function categoriaNombre(clave) {
        for (var i = 0; i < CATEGORIAS.length; i++) {
            if (CATEGORIAS[i].clave === clave) {
                return CATEGORIAS[i].nombre;
            }
        }

        return clave;
    }

    function leerGuardado(clave, porDefecto) {
        try {
            var bruto = localStorage.getItem(clave);

            return bruto === null ? porDefecto : bruto;
        } catch (error) {
            /* Almacenamiento bloqueado (navegación privada): sigue sin memoria. */
            return porDefecto;
        }
    }

    function guardar(clave, valor) {
        try {
            localStorage.setItem(clave, valor);
        } catch (error) {
            /* Igual que arriba: la página funciona, solo no recuerda. */
        }
    }

    /* ======================================================================
       1. Tema claro / oscuro
       ====================================================================== */

    (function tema() {
        var interruptores = qsa('[data-tema-switch]');

        if (! interruptores.length) {
            return;
        }

        function pinta(oscuro) {
            document.documentElement.setAttribute('data-tema', oscuro ? 'oscuro' : 'claro');

            interruptores.forEach(function (boton) {
                boton.setAttribute('aria-pressed', oscuro ? 'true' : 'false');
                boton.setAttribute('aria-label', oscuro ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
                boton.setAttribute('title', oscuro ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
            });
        }

        pinta(document.documentElement.getAttribute('data-tema') === 'oscuro');

        interruptores.forEach(function (boton) {
            boton.addEventListener('click', function () {
                var oscuro = document.documentElement.getAttribute('data-tema') !== 'oscuro';

                pinta(oscuro);
                guardar(TIENDA.claves.tema, oscuro ? 'oscuro' : 'claro');
            });
        });

        // Si el tema se cambia en otra pestaña, esta se pone al día sola.
        window.addEventListener('storage', function (evento) {
            if (evento.key === TIENDA.claves.tema) {
                pinta(evento.newValue === 'oscuro');
            }
        });
    })();

    /* ======================================================================
       2. Cabecera, menú de móvil, aparición y contadores
       ====================================================================== */

    (function cabecera() {
        var cabecera = qs('#cabecera');

        if (! cabecera) {
            return;
        }

        var alBajar = function () {
            cabecera.classList.toggle('cabecera--pegada', window.scrollY > 8);
        };

        alBajar();
        window.addEventListener('scroll', alBajar, { passive: true });
    })();

    (function menuMovil() {
        var boton = qs('#menu-boton');
        var menu = qs('#menu-principal');

        if (! boton || ! menu) {
            return;
        }

        function abrir(abierto) {
            menu.classList.toggle('menu--abierto', abierto);
            boton.setAttribute('aria-expanded', abierto ? 'true' : 'false');
        }

        boton.addEventListener('click', function () {
            abrir(! menu.classList.contains('menu--abierto'));
        });

        // Al elegir una sección el panel se cierra: si no, tapa el contenido.
        qsa('a', menu).forEach(function (enlace) {
            enlace.addEventListener('click', function () {
                abrir(false);
            });
        });

        document.addEventListener('keydown', function (evento) {
            if (evento.key === 'Escape' && menu.classList.contains('menu--abierto')) {
                abrir(false);
                boton.focus();
            }
        });
    })();

    (function revelar() {
        var bloques = qsa('.revelar');

        if (! bloques.length) {
            return;
        }

        if (! ('IntersectionObserver' in window) || movimientoReducido) {
            bloques.forEach(function (bloque) {
                bloque.classList.add('revelar--visible');
            });

            return;
        }

        var observador = new IntersectionObserver(function (entradas) {
            entradas.forEach(function (entrada) {
                if (entrada.isIntersecting) {
                    entrada.target.classList.add('revelar--visible');
                    observador.unobserve(entrada.target);
                }
            });
        }, { rootMargin: '0px 0px -12% 0px', threshold: 0.05 });

        bloques.forEach(function (bloque) {
            observador.observe(bloque);
        });
    })();

    (function contadores() {
        var cifras = qsa('[data-contar]');

        if (! cifras.length || movimientoReducido || ! ('IntersectionObserver' in window)) {
            cifras.forEach(function (cifra) {
                cifra.textContent = cifra.getAttribute('data-contar');
            });

            return;
        }

        var observador = new IntersectionObserver(function (entradas) {
            entradas.forEach(function (entrada) {
                if (! entrada.isIntersecting) {
                    return;
                }

                var nodo = entrada.target;
                var destino = Number(nodo.getAttribute('data-contar')) || 0;
                var desde = 0;
                var inicio = null;

                var paso = function (marca) {
                    if (inicio === null) {
                        inicio = marca;
                    }

                    var avance = Math.min(1, (marca - inicio) / 1100);
                    var suavizado = 1 - Math.pow(1 - avance, 3);

                    nodo.textContent = String(Math.round(desde + (destino - desde) * suavizado));

                    if (avance < 1) {
                        window.requestAnimationFrame(paso);
                    } else {
                        nodo.textContent = String(destino);
                    }
                };

                window.requestAnimationFrame(paso);
                observador.unobserve(nodo);
            });
        }, { threshold: 0.4 });

        cifras.forEach(function (cifra) {
            cifra.textContent = '0';
            observador.observe(cifra);
        });
    })();

    /* ======================================================================
       3. Slider de la portada
       ====================================================================== */

    (function slider() {
        var carrusel = qs('[data-carrusel]');

        if (! carrusel) {
            return;
        }

        var pista = qs('.portada__pista', carrusel);
        var diapositivas = qsa('[data-diapositiva]', carrusel);
        var puntos = qsa('[data-carrusel-punto]', carrusel);
        var botonAnterior = qs('[data-carrusel-anterior]', carrusel);
        var botonSiguiente = qs('[data-carrusel-siguiente]', carrusel);
        var INTERVALO = 6000;
        var actual = 0;
        var temporizador = null;
        var inicioX = null;

        if (! pista || diapositivas.length < 2) {
            return;
        }

        function irA(indice) {
            // El módulo hace que el slider dé la vuelta por los dos extremos.
            actual = (indice + diapositivas.length) % diapositivas.length;

            pista.style.transform = 'translateX(-' + (actual * 100) + '%)';

            // Solo la foto que se ve se anuncia: las otras son ruido para quien
            // navega con lector de pantalla.
            diapositivas.forEach(function (diapositiva, i) {
                diapositiva.setAttribute('aria-hidden', i === actual ? 'false' : 'true');
            });

            puntos.forEach(function (punto, i) {
                if (i === actual) {
                    punto.setAttribute('aria-current', 'true');
                } else {
                    punto.removeAttribute('aria-current');
                }
            });
        }

        function avanzar(paso) {
            irA(actual + paso);
        }

        function detener() {
            window.clearInterval(temporizador);
            temporizador = null;
        }

        function arrancar() {
            // Con movimiento reducido el slider sigue funcionando a mano: lo
            // único que no ocurre es el paso automático.
            if (movimientoReducido || temporizador) {
                return;
            }

            temporizador = window.setInterval(function () {
                avanzar(1);
            }, INTERVALO);
        }

        function manual(paso) {
            detener();
            avanzar(paso);
            arrancar();
        }

        if (botonAnterior) {
            botonAnterior.addEventListener('click', function () {
                manual(-1);
            });
        }

        if (botonSiguiente) {
            botonSiguiente.addEventListener('click', function () {
                manual(1);
            });
        }

        puntos.forEach(function (punto, i) {
            punto.addEventListener('click', function () {
                manual(i - actual);
            });
        });

        // Teclado: la portada responde a las flechas cuando tiene el foco.
        carrusel.setAttribute('tabindex', carrusel.getAttribute('tabindex') || '0');

        carrusel.addEventListener('keydown', function (evento) {
            if (evento.key === 'ArrowLeft') {
                evento.preventDefault();
                manual(-1);
            } else if (evento.key === 'ArrowRight') {
                evento.preventDefault();
                manual(1);
            }
        });

        // Arrastre con el dedo (o el ratón) sobre la foto.
        carrusel.addEventListener('pointerdown', function (evento) {
            if (evento.pointerType === 'mouse' && evento.button !== 0) {
                return;
            }

            inicioX = evento.clientX;
            detener();
        });

        carrusel.addEventListener('pointerup', function (evento) {
            if (inicioX === null) {
                return;
            }

            var recorrido = evento.clientX - inicioX;

            inicioX = null;

            if (Math.abs(recorrido) > 40) {
                avanzar(recorrido < 0 ? 1 : -1);
            }

            arrancar();
        });

        carrusel.addEventListener('pointercancel', function () {
            inicioX = null;
            arrancar();
        });

        // Se pausa mientras se mira de cerca y al cambiar de pestaña; al volver
        // se retoma solo si el movimiento no está reducido.
        carrusel.addEventListener('mouseenter', detener);
        carrusel.addEventListener('mouseleave', arrancar);
        carrusel.addEventListener('focusin', detener);
        carrusel.addEventListener('focusout', arrancar);

        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                detener();
            } else {
                arrancar();
            }
        });

        irA(0);
        arrancar();
    })();

    /* ======================================================================
       4. Buscador de la portada
       ====================================================================== */

    (function buscador() {
        var formularios = qsa('[data-buscar]');

        formularios.forEach(function (formulario) {
            formulario.addEventListener('submit', function (evento) {
                var campo = qs('input', formulario);
                var texto = campo ? campo.value.trim() : '';

                // La búsqueda vive en el catálogo: aquí solo se viaja con el
                // término puesto.
                evento.preventDefault();

                window.location.href = 'tienda.html' + (texto ? '?q=' + encodeURIComponent(texto) : '');
            });
        });
    })();

    /* ======================================================================
       5. La canasta
       ====================================================================== */

    var canasta = (function () {
        var lineas = [];

        try {
            var bruto = JSON.parse(leerGuardado(TIENDA.claves.canasta, '[]'));

            if (Object.prototype.toString.call(bruto) === '[object Array]') {
                lineas = bruto.filter(function (linea) {
                    return linea && producto(linea.slug) && Number(linea.cantidad) > 0;
                });
            }
        } catch (error) {
            lineas = [];
        }

        var escuchas = [];

        function clave(linea) {
            return linea.slug + '|' + (linea.variante || '');
        }

        function avisar() {
            guardar(TIENDA.claves.canasta, JSON.stringify(lineas));
            escuchas.forEach(function (escucha) {
                escucha();
            });
        }

        return {
            lineas: function () {
                return lineas;
            },

            /** Añade una pieza y devuelve cuántas quedaron de esa línea. */
            agregar: function (slug, variante, cantidad) {
                var pieza = producto(slug);

                if (! pieza) {
                    return 0;
                }

                var buscada = { slug: slug, variante: variante || '', cantidad: Math.max(1, Number(cantidad) || 1) };
                var encontrada = null;

                for (var i = 0; i < lineas.length; i++) {
                    if (clave(lineas[i]) === clave(buscada)) {
                        encontrada = lineas[i];
                        break;
                    }
                }

                if (encontrada) {
                    encontrada.cantidad += buscada.cantidad;
                } else {
                    lineas.push(buscada);
                }

                avisar();

                return (encontrada || buscada).cantidad;
            },

            quitar: function (indice) {
                lineas.splice(indice, 1);
                avisar();
            },

            /** Suma o resta una unidad a la línea indicada; a cero, la quita. */
            mover: function (indice, paso) {
                if (! lineas[indice]) {
                    return;
                }

                lineas[indice].cantidad += paso;

                if (lineas[indice].cantidad < 1) {
                    lineas.splice(indice, 1);
                }

                avisar();
            },

            vaciar: function () {
                lineas = [];
                avisar();
            },

            piezas: function () {
                return lineas.reduce(function (suma, linea) {
                    return suma + Number(linea.cantidad);
                }, 0);
            },

            subtotal: function () {
                return lineas.reduce(function (suma, linea) {
                    var pieza = producto(linea.slug);

                    return suma + (pieza ? pieza.precio * Number(linea.cantidad) : 0);
                }, 0);
            },

            /** Tarifa de entrega: en Lima el envío es gratis pasando el umbral. */
            entrega: function (claveEntrega) {
                var opcion = null;

                for (var i = 0; i < ENTREGAS.length; i++) {
                    if (ENTREGAS[i].clave === claveEntrega) {
                        opcion = ENTREGAS[i];
                    }
                }

                if (! opcion || opcion.tarifa === 0) {
                    return 0;
                }

                if (opcion.clave === 'lima' && this.subtotal() >= TIENDA.envioGratis) {
                    return 0;
                }

                return opcion.tarifa;
            },

            total: function (claveEntrega) {
                return this.subtotal() + this.entrega(claveEntrega);
            },

            /** Al cambiar la canasta: la cuenta de la cabecera, el panel y la página. */
            alCambiar: function (escucha) {
                escuchas.push(escucha);
                escucha();
            },

            estaVacia: function () {
                return lineas.length === 0;
            }
        };
    })();

    /** Markup de una línea de la canasta, compartido por el panel y la página. */
    function marcadoLinea(linea, indice, opciones) {
        var pieza = producto(linea.slug);
        var ajustes = opciones || {};
        var solo = ajustes.solo === true;

        return '<article class="canasta-linea"' + (solo ? '' : ' data-linea="' + indice + '"') + '>'
            + '<img src="' + escapar(pieza.foto) + '" alt="' + escapar(pieza.alt) + '" width="900" height="675" loading="lazy" decoding="async">'
            + '<div class="canasta-linea__datos">'
            + '<h3 class="canasta-linea__nombre">' + escapar(pieza.nombre) + '</h3>'
            + (linea.variante ? '<p class="canasta-linea__variante">' + escapar(linea.variante) + '</p>' : '')
            + '<div class="cantidad" aria-label="Cantidad de ' + escapar(pieza.nombre) + '">'
            + '<button type="button" data-canasta-menos="' + indice + '" aria-label="Quitar una unidad">−</button>'
            + '<input type="text" inputmode="numeric" value="' + Number(linea.cantidad) + '" readonly aria-label="Unidades">'
            + '<button type="button" data-canasta-mas="' + indice + '" aria-label="Añadir una unidad">+</button>'
            + '</div>'
            + '</div>'
            + '<div class="canasta-linea__cierre">'
            + '<p class="canasta-linea__precio">' + precio(pieza.precio * Number(linea.cantidad)) + '</p>'
            + '<button type="button" class="canasta-linea__quitar" data-canasta-quitar="' + indice + '">Quitar</button>'
            + '</div>'
            + '</article>';
    }

    /** Botones de contar y quitar: los usan el panel y la página. */
    function engancharLineas(contenedor) {
        qsa('[data-canasta-mas]', contenedor).forEach(function (boton) {
            boton.addEventListener('click', function () {
                canasta.mover(Number(boton.getAttribute('data-canasta-mas')), 1);
            });
        });

        qsa('[data-canasta-menos]', contenedor).forEach(function (boton) {
            boton.addEventListener('click', function () {
                canasta.mover(Number(boton.getAttribute('data-canasta-menos')), -1);
            });
        });

        qsa('[data-canasta-quitar]', contenedor).forEach(function (boton) {
            boton.addEventListener('click', function () {
                canasta.quitar(Number(boton.getAttribute('data-canasta-quitar')));
            });
        });
    }

    // Cuenta de piezas en la cabecera, en todas las páginas.
    var cuentas = qsa('[data-canasta-cuenta]');

    function pintarCuentas() {
        var total = canasta.piezas();

        cuentas.forEach(function (cuenta) {
            cuenta.textContent = total > 99 ? '99+' : String(total);
            cuenta.hidden = total === 0;
        });
    }

    // El aviso entre pestañas deja la canasta al día sin recargar.
    window.addEventListener('storage', function (evento) {
        if (evento.key !== TIENDA.claves.canasta) {
            return;
        }

        window.location.reload();
    });

    /* --------------------------------------------------- Panel de la canasta */

    (function panel() {
        var panel = qs('#panel-canasta');
        var velo = qs('#velo-canasta');

        if (! panel || ! velo) {
            return;
        }

        var cuerpo = qs('[data-panel-lineas]', panel);
        var resumen = qs('[data-panel-resumen]', panel);

        function abierto(esta) {
            panel.classList.toggle('canasta-panel--abierto', esta);
            velo.classList.toggle('velo-panel--visible', esta);
            panel.setAttribute('aria-hidden', esta ? 'false' : 'true');
            document.body.style.overflow = esta ? 'hidden' : '';

            if (esta) {
                var cerrar = qs('.canasta-panel__cerrar', panel);

                if (cerrar) {
                    cerrar.focus();
                }
            }
        }

        qsa('[data-canasta-abrir]').forEach(function (boton) {
            boton.addEventListener('click', function () {
                abierto(true);
            });
        });

        qsa('[data-canasta-cerrar]', panel).forEach(function (boton) {
            boton.addEventListener('click', function () {
                abierto(false);
            });
        });

        velo.addEventListener('click', function () {
            abierto(false);
        });

        document.addEventListener('keydown', function (evento) {
            if (evento.key === 'Escape' && panel.classList.contains('canasta-panel--abierto')) {
                abierto(false);
            }
        });

        // Cualquier botón «añadir» del sitio pasa por aquí. Va delegado a
        // propósito: las piezas relacionadas de una ficha se pintan después de
        // que este archivo se cargue y sus botones también tienen que contar.
        document.addEventListener('click', function (evento) {
            var boton = evento.target.closest('[data-agregar]');

            if (! boton) {
                return;
            }

            evento.preventDefault();

            var cuantas = canasta.agregar(
                boton.getAttribute('data-agregar'),
                boton.getAttribute('data-variante') || '',
                Number(boton.getAttribute('data-cantidad') || 1)
            );

            boton.classList.add('pieza__agregar--hecho');

            window.setTimeout(function () {
                boton.classList.remove('pieza__agregar--hecho');
            }, 1400);

            // En el catálogo se confirma sin abrir el panel; en la ficha, donde
            // se acaba de elegir, se abre para revisar el pedido.
            if (boton.getAttribute('data-abrir-panel') === 'si') {
                abierto(true);
            }

            cuentas.forEach(function (cuenta) {
                var caja = cuenta.closest('.canasta-boton');

                if (! caja) {
                    return;
                }

                caja.classList.remove('canasta-boton--nueva');
                void caja.offsetWidth;
                caja.classList.add('canasta-boton--nueva');
            });

            if (cuantas > 0 && boton.getAttribute('data-aviso')) {
                var aviso = qs('#' + boton.getAttribute('data-aviso'));

                if (aviso) {
                    aviso.textContent = 'Añadido a la canasta: ' + cuantas + ' unidad(es).';
                    aviso.hidden = false;
                }
            }
        });

        canasta.alCambiar(function () {
            pintarCuentas();

            if (! cuerpo) {
                return;
            }

            if (canasta.estaVacia()) {
                cuerpo.innerHTML = '<div class="canasta-vacia">'
                    + '<p><strong>Tu canasta está vacía.</strong></p>'
                    + '<p>Lo que agregues queda guardado en este navegador hasta que cierres el pedido.</p>'
                    + '<a class="boton boton--fantasma" href="tienda.html">Ver la tienda</a>'
                    + '</div>';
            } else {
                cuerpo.innerHTML = canasta.lineas().map(function (linea, indice) {
                    return marcadoLinea(linea, indice);
                }).join('');

                engancharLineas(cuerpo);
            }

            if (resumen) {
                resumen.innerHTML = '<div class="canasta-totales">'
                    + '<div><span>Productos</span><span>' + canasta.piezas() + '</span></div>'
                    + '<div><span>Subtotal</span><span>' + precio(canasta.subtotal()) + '</span></div>'
                    + '<div><span>Envío</span><span>se calcula al cerrar el pedido</span></div>'
                    + '<div class="canasta-totales__total"><span>Total</span><span>' + precio(canasta.subtotal()) + '</span></div>'
                    + '</div>';
            }
        });
    })();

    /* ======================================================================
       6. Catálogo: filtros con el estado en la URL
       ====================================================================== */

    (function catalogo() {
        var rejilla = qs('[data-rejilla]');

        if (! rejilla) {
            return;
        }

        var piezas = qsa('[data-pieza]', rejilla);
        var formulario = qs('[data-filtros]');
        var resumen = qs('[data-resumen]');
        var vacio = qs('[data-vacio]');
        var chips = qsa('[data-chip-categoria]');

        var campos = {
            categoria: formulario ? qs('[name="categoria"]', formulario) : null,
            hasta: formulario ? qs('[name="hasta"]', formulario) : null,
            orden: formulario ? qs('[name="orden"]', formulario) : null,
            q: formulario ? qs('[name="q"]', formulario) : null
        };

        var parametros = new URLSearchParams(window.location.search);

        function valorDe(clave) {
            return (parametros.get(clave) || '').trim();
        }

        function aplicar() {
            var categoria = valorDe('categoria');
            var hasta = valorDe('hasta');
            var texto = normalizar(valorDe('q'));
            var orden = valorDe('orden') || 'recomendado';
            var visibles = [];

            // Los campos del formulario reflejan lo que pide la URL: así el
            // enlace del pie de una ficha llega con el filtro ya puesto.
            if (campos.categoria) {
                campos.categoria.value = categoria;
            }

            if (campos.hasta) {
                campos.hasta.value = hasta;
            }

            if (campos.orden) {
                campos.orden.value = orden;
            }

            if (campos.q && texto) {
                campos.q.value = valorDe('q');
            }

            chips.forEach(function (chip) {
                var suya = chip.getAttribute('data-chip-categoria');
                var activo = (categoria === '' && suya === '') || suya === categoria;

                chip.classList.toggle('chip--activo', activo);
            });

            piezas.forEach(function (pieza) {
                var coincide = true;
                var precioPieza = Number(pieza.getAttribute('data-precio'));
                var buscable = normalizar(pieza.getAttribute('data-buscar'));

                if (categoria && pieza.getAttribute('data-categoria') !== categoria) {
                    coincide = false;
                }

                if (hasta && precioPieza > Number(hasta)) {
                    coincide = false;
                }

                if (texto && buscable.indexOf(texto) === -1) {
                    coincide = false;
                }

                pieza.hidden = ! coincide;

                if (coincide) {
                    visibles.push(pieza);
                }
            });

            // El orden se resuelve sobre las visibles: ordenar y después filtrar
            // daría el mismo resultado, pero así no se reordena lo escondido.
            var ordenar = {
                precio: function (a, b) {
                    return Number(a.getAttribute('data-precio')) - Number(b.getAttribute('data-precio'));
                },
                precio_desc: function (a, b) {
                    return Number(b.getAttribute('data-precio')) - Number(a.getAttribute('data-precio'));
                },
                nombre: function (a, b) {
                    return (a.getAttribute('data-nombre') || '').localeCompare(b.getAttribute('data-nombre') || '', 'es');
                }
            };

            visibles.slice().sort(ordenar[orden] || ordenar.precio).forEach(function (pieza) {
                rejilla.appendChild(pieza);
            });

            if (resumen) {
                var frases = [];

                if (categoria) {
                    frases.push('en ' + categoriaNombre(categoria));
                }

                if (hasta) {
                    frases.push('de hasta ' + precio(hasta));
                }

                if (texto) {
                    frases.push('que dicen «' + valorDe('q') + '»');
                }

                resumen.innerHTML = '<span><strong>' + visibles.length + '</strong> de '
                    + piezas.length + ' productos' + (frases.length ? ' ' + frases.join(', ') : ' en toda la tienda')
                    + '</span>'
                    + (frases.length ? '<a class="enlace-flecha" href="tienda.html">Ver todo</a>' : '');
            }

            if (vacio) {
                vacio.classList.toggle('catalogo-vacio--visible', visibles.length === 0);
            }
        }

        aplicar();

        if (formulario) {
            formulario.addEventListener('submit', function (evento) {
                // El filtrado es del navegador: no se recarga la página, pero la
                // URL guarda el filtro para poder compartirlo.
                evento.preventDefault();

                var nuevos = new URLSearchParams();

                Object.keys(campos).forEach(function (clave) {
                    var campo = campos[clave];
                    var valor = campo ? campo.value.trim() : '';

                    if (valor) {
                        nuevos.set(clave, valor);
                    }
                });

                parametros = nuevos;
                window.history.replaceState({}, '', 'tienda.html' + (nuevos.toString() ? '?' + nuevos.toString() : ''));
                aplicar();
            });
        }
    })();

    /* ======================================================================
       7. Ficha de producto
       ====================================================================== */

    (function ficha() {
        var raiz = qs('[data-ficha]');

        if (! raiz) {
            return;
        }

        var parametros = new URLSearchParams(window.location.search);
        var pedido = parametros.get('p');
        var pieza = producto(pedido) || PRODUCTOS[0];
        var elegido = { color: pieza.colores && pieza.colores.length ? pieza.colores[0] : '',
            aroma: pieza.aromas && pieza.aromas.length ? pieza.aromas[0] : '',
            tamano: pieza.tamanos && pieza.tamanos.length ? pieza.tamanos[0] : '' };

        var variantes = [];

        if (elegido.color) {
            variantes.push({ clave: 'color', rotulo: 'Color', opciones: pieza.colores });
        }

        if (elegido.aroma) {
            variantes.push({ clave: 'aroma', rotulo: 'Aroma', opciones: pieza.aromas });
        }

        if (elegido.tamano) {
            variantes.push({ clave: 'tamano', rotulo: 'Tamaño', opciones: pieza.tamanos });
        }

        function varianteElegida() {
            var partes = [];

            if (elegido.color) {
                partes.push(elegido.color);
            }

            if (elegido.aroma) {
                partes.push(elegido.aroma);
            }

            if (elegido.tamano) {
                partes.push(elegido.tamano);
            }

            return partes.join(' · ');
        }

        function pintarVariantes() {
            var caja = qs('[data-variantes]', raiz);

            if (! caja) {
                return;
            }

            caja.innerHTML = variantes.map(function (grupo) {
                return '<div class="variantes__grupo">'
                    + '<span class="variantes__rotulo">' + escapar(grupo.rotulo) + '</span>'
                    + grupo.opciones.map(function (opcion) {
                        return '<button type="button" class="variante" data-variante-clave="' + grupo.clave
                            + '" data-variante-valor="' + escapar(opcion) + '" aria-pressed="'
                            + (elegido[grupo.clave] === opcion ? 'true' : 'false') + '">' + escapar(opcion) + '</button>';
                    }).join('')
                    + '</div>';
            }).join('');

            qsa('[data-variante-clave]', caja).forEach(function (boton) {
                boton.addEventListener('click', function () {
                    elegido[boton.getAttribute('data-variante-clave')] = boton.getAttribute('data-variante-valor');
                    pintarVariantes();
                    pintarAcciones();
                });
            });
        }

        function pintarAcciones() {
            var agregar = qs('[data-agregar]', raiz);
            var whatsapp = qs('[data-whatsapp-pieza]', raiz);
            var mensaje = 'Hola, quiero consultar por «' + pieza.nombre + '»'
                + (varianteElegida() ? ' (' + varianteElegida() + ')' : '') + '.';

            if (agregar) {
                // El botón lleva la pieza, la variante y las unidades: es todo lo
                // que necesita la canasta para armar la línea.
                agregar.setAttribute('data-agregar', pieza.slug);
                agregar.setAttribute('data-variante', varianteElegida());
                agregar.setAttribute('aria-label', 'Añadir ' + pieza.nombre + ' a la canasta');
            }

            if (whatsapp) {
                whatsapp.setAttribute('href', 'https://wa.me/' + TIENDA.whatsapp + '?text=' + encodeURIComponent(mensaje));
            }
        }

        function pintarGaleria() {
            var principal = qs('[data-galeria-principal]', raiz);
            var tiras = qsa('[data-galeria-tira]', raiz);

            tiras.forEach(function (tira) {
                tira.addEventListener('click', function () {
                    var fuente = qs('img', tira);

                    principal.src = fuente.getAttribute('src');
                    principal.alt = fuente.getAttribute('alt');

                    tiras.forEach(function (otra) {
                        otra.setAttribute('aria-current', otra === tira ? 'true' : 'false');
                    });
                });
            });
        }

        function pintarRelacionadas() {
            // La rejilla de relacionadas vive fuera de la ficha: se busca en todo
            // el documento, no dentro del bloque de la pieza.
            var caja = qs('[data-relacionadas]');

            if (! caja) {
                return;
            }

            caja.innerHTML = pieza.relacionados.map(function (slug) {
                return tarjeta(producto(slug));
            }).join('');
        }

        // Título, migajas y todo el texto que depende de la pieza elegida.
        document.title = pieza.nombre + ' — ' + TIENDA.nombre + ' · Demostración de plantilla';

        var textos = {
            '[data-ficha-nombre]': pieza.nombre,
            '[data-ficha-categoria]': categoriaNombre(pieza.categoria),
            '[data-ficha-descripcion]': pieza.descripcion,
            '[data-ficha-medidas]': pieza.medidas,
            '[data-ficha-materiales]': pieza.materiales,
            '[data-ficha-cuidado]': pieza.cuidado,
            '[data-ficha-hecho]': pieza.hecho,
            '[data-ficha-opiniones]': pieza.opiniones + ' opiniones de clientes'
        };

        Object.keys(textos).forEach(function (selector) {
            var nodo = qs(selector, raiz);

            if (nodo) {
                nodo.textContent = textos[selector];
            }
        });

        // La última migaja es el nombre de la pieza, y también queda fuera de la
        // ficha: se pinta desde el documento.
        var migaja = qs('[data-ficha-migaja]');

        if (migaja) {
            migaja.textContent = pieza.nombre;
        }

        var categoriaEnlace = qs('[data-ficha-categoria-enlace]', raiz);

        if (categoriaEnlace) {
            categoriaEnlace.setAttribute('href', 'tienda.html?categoria=' + pieza.categoria);
        }

        var precioCaja = qs('[data-ficha-precio]', raiz);

        if (precioCaja) {
            precioCaja.innerHTML = '<span class="etiqueta__moneda">S/</span>'
                + pieza.precio.toLocaleString('es-PE')
                + (pieza.antes ? ' <span class="etiqueta__antes">' + precio(pieza.antes) + '</span>' : '');
        }

        var foto = qs('[data-galeria-principal]', raiz);

        if (foto) {
            foto.src = pieza.foto;
            foto.alt = pieza.alt;
        }

        var tiraPieza = qs('[data-tira-pieza]', raiz);

        if (tiraPieza) {
            var imagen = qs('img', tiraPieza);

            imagen.src = pieza.foto;
            imagen.alt = pieza.alt;
        }

        var insignia = qs('[data-ficha-insignia]', raiz);

        if (insignia) {
            insignia.textContent = pieza.etiqueta || 'Hecho a mano';
        }

        // Cantidad: el botón de agregar lleva siempre las unidades elegidas.
        (function cantidad() {
            var campo = qs('[data-pieza-cantidad]', raiz);

            if (! campo) {
                return;
            }

            function pintarCantidad() {
                var valor = Math.max(1, Math.min(20, Number(campo.value) || 1));
                var agregar = qs('[data-agregar]', raiz);

                campo.value = valor;

                if (agregar) {
                    agregar.setAttribute('data-cantidad', String(valor));
                }
            }

            var menos = qs('[data-pieza-menos]', raiz);
            var mas = qs('[data-pieza-mas]', raiz);

            if (menos) {
                menos.addEventListener('click', function () {
                    campo.value = (Number(campo.value) || 1) - 1;
                    pintarCantidad();
                });
            }

            if (mas) {
                mas.addEventListener('click', function () {
                    campo.value = (Number(campo.value) || 1) + 1;
                    pintarCantidad();
                });
            }

            pintarCantidad();
        })();

        pintarVariantes();
        pintarAcciones();
        pintarGaleria();
        pintarRelacionadas();
    })();

    /* ======================================================================
       8. Página de la canasta: líneas, totales y pedido
       ====================================================================== */

    (function paginaCanasta() {
        var raiz = qs('[data-canasta-pagina]');

        if (! raiz) {
            return;
        }

        var lista = qs('[data-canasta-lista]', raiz);
        var vacia = qs('[data-canasta-vacia]', raiz);
        var bloque = qs('[data-canasta-bloque]', raiz);
        var totales = qs('[data-canasta-totales]', raiz);
        var entregas = qsa('[name="entrega"]', raiz);
        var formulario = qs('[data-pedido]', raiz);
        var recibo = qs('[data-recibo]', raiz);
        var aviso = qs('#aviso-pedido');

        function entregaElegida() {
            for (var i = 0; i < entregas.length; i++) {
                if (entregas[i].checked) {
                    return entregas[i].value;
                }
            }

            return 'recojo';
        }

        function nombreEntrega(claveEntrega) {
            for (var i = 0; i < ENTREGAS.length; i++) {
                if (ENTREGAS[i].clave === claveEntrega) {
                    return ENTREGAS[i];
                }
            }

            return ENTREGAS[0];
        }

        function pintar() {
            var hayPiezas = ! canasta.estaVacia();

            if (vacia) {
                vacia.hidden = hayPiezas;
            }

            if (bloque) {
                bloque.hidden = ! hayPiezas;
            }

            if (lista) {
                lista.innerHTML = canasta.lineas().map(function (linea, indice) {
                    return marcadoLinea(linea, indice);
                }).join('');

                engancharLineas(lista);
            }

            if (totales) {
                var entrega = nombreEntrega(entregaElegida());
                var subtotal = canasta.subtotal();
                var envio = canasta.entrega(entrega.clave);

                var unidades = canasta.piezas();

                totales.innerHTML = '<div><span>Subtotal (' + unidades + (unidades === 1 ? ' producto)' : ' productos)') + '</span><span>'
                    + precio(subtotal) + '</span></div>'
                    + '<div><span>' + escapar(entrega.nombre) + '</span><span>'
                    + (envio === 0 ? 'sin costo' : precio(envio)) + '</span></div>'
                    + '<div class="canasta-totales__total"><span>Total</span><span>'
                    + precio(subtotal + envio) + '</span></div>'
                    + (entrega.clave === 'lima' && subtotal > 0 && subtotal < TIENDA.envioGratis
                        ? '<p class="nota-precios">Te faltan ' + precio(TIENDA.envioGratis - subtotal)
                            + ' para el delivery sin costo en Lima.</p>'
                        : '');
            }
        }

        /** El detalle del pedido, escrito como saldría en el recibo del estudio. */
        function detallePedido() {
            var entrega = nombreEntrega(entregaElegida());
            var lineas = canasta.lineas().map(function (linea) {
                var pieza = producto(linea.slug);
                var unidades = Number(linea.cantidad);

                return unidades + ' × ' + pieza.nombre
                    + (linea.variante ? ' (' + linea.variante + ')' : '')
                    + ' — ' + precio(pieza.precio * unidades);
            });

            return [
                'PEDIDO · ' + TIENDA.nombre.toUpperCase(),
                '------------------------------',
                lineas.join('\n'),
                '------------------------------',
                'Subtotal: ' + precio(canasta.subtotal()),
                entrega.nombre + ': ' + (canasta.entrega(entrega.clave) === 0 ? 'sin costo' : precio(canasta.entrega(entrega.clave))),
                'TOTAL: ' + precio(canasta.total(entrega.clave))
            ].join('\n');
        }

        function actualizarRecibo() {
            if (recibo) {
                recibo.textContent = detallePedido();
            }
        }

        entregas.forEach(function (radio) {
            radio.addEventListener('change', function () {
                pintar();
                actualizarRecibo();
                // El distrito solo se pide cuando el pedido viaja.
                qsa('[data-solo-si-entrega]', raiz).forEach(function (campo) {
                    var caja = campo.closest('.campo');

                    if (caja) {
                        caja.hidden = campo.getAttribute('data-solo-si-entrega').indexOf(entregaElegida()) === -1;
                    }
                });
            });
        });

        canasta.alCambiar(function () {
            pintar();
            actualizarRecibo();
        });

        qsa('[data-solo-si-entrega]', raiz).forEach(function (campo) {
            var caja = campo.closest('.campo');

            if (caja && campo.getAttribute('data-solo-si-entrega').indexOf(entregaElegida()) === -1) {
                caja.hidden = true;
            }
        });

        var vaciar = qs('[data-canasta-vaciar]', raiz);

        if (vaciar) {
            vaciar.addEventListener('click', function () {
                canasta.vaciar();
            });
        }

        if (formulario) {
            formulario.addEventListener('submit', function (evento) {
                evento.preventDefault();

                var errores = validar(formulario);

                if (errores > 0) {
                    if (aviso) {
                        aviso.className = 'aviso-formulario aviso-formulario--visible aviso-formulario--error';
                        aviso.textContent = 'Revisa los ' + errores + ' campos marcados para cerrar el pedido.';
                    }

                    return;
                }

                // La demostración no manda nada: arma el pedido y lo enseña,
                // que es justo lo que haría el mensaje de la tienda real.
                actualizarRecibo();

                if (aviso) {
                    aviso.className = 'aviso-formulario aviso-formulario--visible';
                    aviso.innerHTML = '<strong>Demostración: el pedido no se envía.</strong> '
                        + 'En tu tienda este botón abre tu WhatsApp con el detalle de abajo, y el pedido queda registrado con su número.';
                }

                if (recibo) {
                    recibo.hidden = false;
                }
            });
        }
    })();

    /* ======================================================================
       9. Formularios: reglas declaradas en el HTML
       ====================================================================== */

    /**
     * Valida los campos con `data-regla` de un formulario y marca los que
     * fallan. Devuelve cuántos errores encontró. Las reglas viven en el HTML
     * para que se lean junto al campo.
     */
    function validar(formulario) {
        var campos = qsa('[data-regla]', formulario);
        var errores = [];
        var gruposVistos = [];

        campos.forEach(function (campo) {
            var regla = campo.getAttribute('data-regla');
            var cajaCampo = campo.closest('.campo');
            var cajaCasilla = campo.closest('.casilla');
            var valor = (campo.value || '').trim();
            var condicion = campo.getAttribute('data-solo-si-entrega');
            var salta = false;

            if (condicion) {
                // Las formas de entrega viven fuera del formulario, en su propio
                // bloque: se buscan en la página, no dentro del formulario.
                var elegida = qs('[name="entrega"]:checked');
                var claveElegida = elegida ? elegida.value : '';

                // Sin entrega elegida todavía no se sabe si el pedido viaja: el
                // distrito y la dirección esperan. `indexOf('')` daría 0 y los
                // reclamaría de más.
                salta = claveElegida === '' || condicion.indexOf(claveElegida) === -1;
            }

            var malo = false;

            if (! salta) {
                if (regla === 'texto') {
                    malo = valor.length < Number(campo.getAttribute('data-min') || 3);
                } else if (regla === 'correo') {
                    malo = ! /^[^\s@]+@[^\s@]+\.[a-zA-Z]{2,}$/.test(valor);
                } else if (regla === 'celular') {
                    malo = ! /^9\d{8}$/.test(valor.replace(/[\s-]/g, ''));
                } else if (regla === 'elegido') {
                    malo = valor === '';
                } else if (regla === 'casilla') {
                    malo = ! campo.checked;
                } else if (regla === 'radio') {
                    // Un grupo de radios se valida una sola vez: si cada opción
                    // contara su error, el aviso diría «revisa los 5 campos»
                    // cuando en realidad falta una sola elección. Las opciones
                    // siguientes se saltan del todo —no basta con no sumar el
                    // error— porque compartirían la marca del grupo y la
                    // borrarían al ocurrírseles la segunda vuelta.
                    var grupo = campo.name;

                    if (gruposVistos.indexOf(grupo) !== -1) {
                        return;
                    }

                    gruposVistos.push(grupo);
                    malo = ! qs('input[name="' + grupo + '"]:checked', formulario);
                }
            }

            if (cajaCampo) {
                cajaCampo.classList.toggle('campo--error', malo);
            }

            if (cajaCasilla) {
                cajaCasilla.classList.toggle('casilla--error', malo);
            }

            if (malo) {
                errores.push(campo);
            }
        });

        if (errores.length) {
            errores[0].focus({ preventScroll: false });
        }

        return errores.length;
    }

    (function formulariosSimples() {
        qsa('[data-formulario-simple]').forEach(function (formulario) {
            var aviso = qs('[data-aviso-formulario]', formulario);

            formulario.addEventListener('submit', function (evento) {
                evento.preventDefault();

                var errores = validar(formulario);

                if (! aviso) {
                    return;
                }

                if (errores > 0) {
                    aviso.className = 'aviso-formulario aviso-formulario--visible aviso-formulario--error';
                    aviso.textContent = 'Revisa los ' + errores + ' campos marcados para continuar.';

                    return;
                }

                aviso.className = 'aviso-formulario aviso-formulario--visible';
                aviso.innerHTML = '<strong>Demostración: el formulario funciona, pero no envía datos.</strong> '
                    + 'En tu tienda la consulta llega a tu correo y queda registrada en el panel.';
            });
        });
    })();

    /* ======================================================================
       10. Tarjetas de producto reutilizables
       ====================================================================== */

    /** Markup de una tarjeta del catálogo: la usan la ficha y las secciones. */
    function tarjeta(pieza) {
        if (! pieza) {
            return '';
        }

        return '<article class="pieza" data-pieza data-producto="' + escapar(pieza.slug) + '"'
            + ' data-categoria="' + escapar(pieza.categoria) + '"'
            + ' data-precio="' + Number(pieza.precio) + '"'
            + ' data-nombre="' + escapar(pieza.nombre) + '"'
            + ' data-buscar="' + escapar(normalizar(pieza.nombre + ' ' + pieza.resumen + ' ' + categoriaNombre(pieza.categoria) + ' ' + pieza.slug)) + '">'
            + '<a class="pieza__marco" href="producto.html?p=' + escapar(pieza.slug) + '">'
            + '<img src="' + escapar(pieza.foto) + '" alt="' + escapar(pieza.alt) + '" width="900" height="675" loading="lazy" decoding="async">'
            // Sobre la foto solo el descuento y la etiqueta; el precio va con el texto.
            + (pieza.antes ? '<span class="pieza__oferta">−' + descuento(pieza) + ' %</span>' : '')
            + (pieza.etiqueta ? '<span class="insignia pieza__insignia">' + escapar(pieza.etiqueta) + '</span>' : '')
            + '</a>'
            + '<div class="pieza__cuerpo">'
            + '<p class="pieza__categoria">' + escapar(categoriaNombre(pieza.categoria)) + '</p>'
            + '<h3 class="pieza__nombre"><a href="producto.html?p=' + escapar(pieza.slug) + '">' + escapar(pieza.nombre) + '</a></h3>'
            + '<p class="pieza__detalle">' + escapar(pieza.resumen) + '</p>'
            + '<div class="pieza__pie">'
            + '<p class="pieza__precio"><strong>' + escapar(precio(pieza.precio)) + '</strong>'
            + (pieza.antes ? '<s>' + escapar(precio(pieza.antes)) + '</s>' : '')
            + '</p>'
            + '<div class="pieza__acciones">'
            + '<span class="estrellas" role="img" aria-label="' + escapar(pieza.valoracion) + ' de 5 estrellas">'
            + estrella() + estrella() + estrella() + estrella() + estrella()
            + '</span>'
            + '<button type="button" class="pieza__agregar" data-agregar="' + escapar(pieza.slug) + '"'
            + ' aria-label="Añadir ' + escapar(pieza.nombre) + ' a la canasta">' + icono('bolsa') + '</button>'
            + '</div>'
            + '</div>'
            + '</div>'
            + '</article>';
    }

    /** Descuento redondeado, para la insignia de la rejilla. */
    function descuento(pieza) {
        return pieza.antes ? Math.round((1 - pieza.precio / pieza.antes) * 100) : 0;
    }

    function estrella() {
        return '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="m12 3 2.6 5.6 6.1.8-4.5 4.2 1.2 6-5.4-3-5.4 3 1.2-6L3.3 9.4l6.1-.8Z"/></svg>';
    }

    function icono(nombre) {
        var trazos = {
            bolsa: '<path d="M6 8h12l-1 11H7L6 8Z"/><path d="M9.5 8V6.5a2.5 2.5 0 0 1 5 0V8"/>'
        };

        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
            + (trazos[nombre] || '') + '</svg>';
    }
})();
