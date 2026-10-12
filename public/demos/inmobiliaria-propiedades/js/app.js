/*
   =============================================================================
   Alameda Propiedades — JavaScript de la demostración
   =============================================================================

   Un solo archivo, sin dependencias y sin nada del sitio corporativo. Cubre las
   seis piezas con JavaScript del sitio:

     1. Tema claro / oscuro            (interruptor que guarda la elección)
     2. Cabecera y menú móvil          (sombra al bajar y panel con aria-expanded)
     3. Portada                        (slider: paso automático, flechas, puntos,
                                        teclado y arrastre; se apaga con
                                        prefers-reduced-motion)
     4. Buscador y filtros             (la portada manda a propiedades.html con
                                        los filtros en la URL y el listado los
                                        lee de vuelta: operación, distrito, tipo,
                                        dormitorios, precio, orden y favoritos)
     5. Favoritos                      (corazón guardado en localStorage, con
                                        contador en la cabecera)
     6. Ficha de inmueble              (galería con visor y simulador de cuota)
     7. Formularios                    (validación campo por campo; la demo no
                                        envía nada)
     8. Movimiento                     (aparición al hacer scroll y contadores)

   Todo lo que se ve sin JavaScript sigue funcionando: el slider muestra la
   primera foto y oculta sus controles, la galería se ve completa con la primera
   imagen, y los formularios no avisan de nada.
   ============================================================================= */

(function () {
    'use strict';

    document.documentElement.classList.add('js');

    var movimientoReducido = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    window.matchMedia('(prefers-reduced-motion: reduce)').addEventListener('change', function (evento) {
        movimientoReducido = evento.matches;
    });

    var formatoSoles = new Intl.NumberFormat('es-PE', {
        style: 'currency',
        currency: 'PEN',
        maximumFractionDigits: 0
    });

    var formatoNumero = new Intl.NumberFormat('es-PE', { maximumFractionDigits: 0 });

    function leerNumero(valor) {
        var numero = parseInt(String(valor).replace(/[^0-9]/g, ''), 10);

        return isNaN(numero) ? 0 : numero;
    }

    /* =========================================================================
       1. Tema claro / oscuro

       El atributo ya viene aplicado desde el <head>; aquí solo se atiende el
       interruptor, se dejan las etiquetas en consonancia y se escucha el evento
       `storage` para que las otras pestañas se pongan al día.
       ========================================================================= */

    (function tema() {
        var CLAVE = 'inmobiliaria-propiedades-tema';
        var raiz = document.documentElement;
        var botones = document.querySelectorAll('[data-tema-switch]');

        if (!botones.length) {
            return;
        }

        function aplicar(oscuro) {
            if (oscuro) {
                raiz.setAttribute('data-tema', 'oscuro');
            } else {
                raiz.setAttribute('data-tema', 'claro');
            }

            botones.forEach(function (boton) {
                boton.setAttribute('aria-pressed', oscuro ? 'true' : 'false');
                boton.setAttribute('aria-label', oscuro ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
                boton.setAttribute('title', oscuro ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
            });
        }

        botones.forEach(function (boton) {
            boton.addEventListener('click', function () {
                var oscuro = raiz.getAttribute('data-tema') !== 'oscuro';

                try {
                    localStorage.setItem(CLAVE, oscuro ? 'oscuro' : 'claro');
                } catch (error) {
                    /* Almacenamiento bloqueado: el tema vale solo en esta página. */
                }

                aplicar(oscuro);
            });
        });

        window.addEventListener('storage', function (evento) {
            if (evento.key === CLAVE) {
                aplicar(evento.newValue === 'oscuro');
            }
        });

        aplicar(raiz.getAttribute('data-tema') === 'oscuro');
    })();

    /* =========================================================================
       2. Cabecera y menú móvil
       ========================================================================= */

    (function cabecera() {
        var cabecera = document.getElementById('cabecera');
        var boton = document.getElementById('menu-boton');
        var menu = document.getElementById('menu-principal');

        if (cabecera) {
            var sombra = function () {
                cabecera.classList.toggle('cabecera--pegada', window.scrollY > 12);
            };

            sombra();
            window.addEventListener('scroll', sombra, { passive: true });
        }

        if (!boton || !menu) {
            return;
        }

        function abrir(abierto) {
            document.body.classList.toggle('menu-abierto', abierto);
            boton.setAttribute('aria-expanded', abierto ? 'true' : 'false');

            var etiqueta = boton.querySelector('.menu-boton__etiqueta');

            if (etiqueta) {
                etiqueta.textContent = abierto ? 'Cerrar menú' : 'Abrir menú';
            }
        }

        boton.addEventListener('click', function () {
            abrir(document.body.classList.contains('menu-abierto') === false);
        });

        // Al elegir una sección el panel se cierra solo, y con el teclado se
        // cierra con Escape sin sacar el foco de la cabecera.
        menu.addEventListener('click', function (evento) {
            if (evento.target.closest('a')) {
                abrir(false);
            }
        });

        document.addEventListener('keydown', function (evento) {
            if (evento.key === 'Escape' && document.body.classList.contains('menu-abierto')) {
                abrir(false);
                boton.focus();
            }
        });
    })();

    /* =========================================================================
       3. Portada: slider de tres fotos
       ========================================================================= */

    (function portada() {
        var contenedor = document.querySelector('[data-carrusel]');

        if (!contenedor) {
            return;
        }

        var diapositivas = Array.prototype.slice.call(contenedor.querySelectorAll('[data-diapositiva]'));
        var puntos = Array.prototype.slice.call(contenedor.querySelectorAll('[data-carrusel-punto]'));
        var anterior = contenedor.querySelector('[data-carrusel-anterior]');
        var siguiente = contenedor.querySelector('[data-carrusel-siguiente]');

        if (diapositivas.length < 2) {
            return;
        }

        var actual = 0;
        var temporizador = null;
        var pausado = false;
        var arrastre = null;

        function mostrar(indice) {
            actual = (indice + diapositivas.length) % diapositivas.length;

            diapositivas.forEach(function (diapositiva, posicion) {
                if (posicion === actual) {
                    diapositiva.setAttribute('data-activa', '');
                } else {
                    diapositiva.removeAttribute('data-activa');
                }
            });

            puntos.forEach(function (punto, posicion) {
                if (posicion === actual) {
                    punto.setAttribute('aria-current', 'true');
                } else {
                    punto.removeAttribute('aria-current');
                }
            });
        }

        function avanzar(paso) {
            mostrar(actual + paso);
        }

        function arrancar() {
            if (temporizador !== null || movimientoReducido) {
                return;
            }

            temporizador = setInterval(function () {
                if (!pausado) {
                    avanzar(1);
                }
            }, 6000);
        }

        function parar() {
            if (temporizador !== null) {
                clearInterval(temporizador);
                temporizador = null;
            }
        }

        if (anterior) {
            anterior.addEventListener('click', function () {
                avanzar(-1);
            });
        }

        if (siguiente) {
            siguiente.addEventListener('click', function () {
                avanzar(1);
            });
        }

        puntos.forEach(function (punto, posicion) {
            punto.addEventListener('click', function () {
                mostrar(posicion);
            });
        });

        // Paso automático: se pausa al pasar el ratón, al enfocar un control y
        // cuando la pestaña queda en segundo plano.
        contenedor.addEventListener('mouseenter', function () {
            pausado = true;
        });

        contenedor.addEventListener('mouseleave', function () {
            pausado = false;
        });

        contenedor.addEventListener('focusin', function () {
            pausado = true;
        });

        contenedor.addEventListener('focusout', function () {
            pausado = false;
        });

        document.addEventListener('visibilitychange', function () {
            pausado = document.hidden;
        });

        // Con el teclado: flechas izquierda y derecha cuando el foco está dentro.
        contenedor.addEventListener('keydown', function (evento) {
            if (evento.key === 'ArrowLeft') {
                avanzar(-1);
            }

            if (evento.key === 'ArrowRight') {
                avanzar(1);
            }
        });

        // Y con el dedo: el arrastre horizontal cambia de foto solo si supera un
        // umbral, para no confundirse con el scroll vertical.
        contenedor.addEventListener('pointerdown', function (evento) {
            if (evento.pointerType === 'mouse' && evento.button !== 0) {
                return;
            }

            arrastre = { x: evento.clientX, y: evento.clientY };
        });

        contenedor.addEventListener('pointerup', function (evento) {
            if (arrastre === null) {
                return;
            }

            var dx = evento.clientX - arrastre.x;
            var dy = evento.clientY - arrastre.y;

            arrastre = null;

            if (Math.abs(dx) > 48 && Math.abs(dx) > Math.abs(dy)) {
                avanzar(dx < 0 ? 1 : -1);
            }
        });

        contenedor.addEventListener('pointercancel', function () {
            arrastre = null;
        });

        // `prefers-reduced-motion` cambia en caliente: si se activa, se detiene
        // el paso automático y los controles siguen funcionando a mano.
        window.matchMedia('(prefers-reduced-motion: reduce)').addEventListener('change', function (evento) {
            if (evento.matches) {
                parar();
            } else {
                arrancar();
            }
        });

        mostrar(0);
        arrancar();
    })();

    /* =========================================================================
       4. Favoritos

       Se guardan como lista de códigos de inmueble en localStorage, así que
       viajan entre las páginas del sitio. El contador de la cabecera y los
       corazones se pintan desde aquí.
       ========================================================================= */

    var FAVORITOS = 'inmobiliaria-propiedades-favoritos';

    function favoritosLeidos() {
        try {
            var guardado = JSON.parse(localStorage.getItem(FAVORITOS) || '[]');

            return Array.isArray(guardado) ? guardado : [];
        } catch (error) {
            return [];
        }
    }

    function favoritosGuardados(lista) {
        try {
            localStorage.setItem(FAVORITOS, JSON.stringify(lista));
        } catch (error) {
            /* Almacenamiento bloqueado: los favoritos valen solo en esta página. */
        }
    }

    var favoritos = favoritosLeidos();

    function esFavorito(codigo) {
        return favoritos.indexOf(codigo) !== -1;
    }

    function pintarContador() {
        document.querySelectorAll('[data-favoritos-conteo]').forEach(function (contador) {
            contador.textContent = String(favoritos.length);
            contador.hidden = favoritos.length === 0;
        });
    }

    function pintarCorazones() {
        document.querySelectorAll('[data-favorito]').forEach(function (boton) {
            var marcado = esFavorito(boton.getAttribute('data-favorito'));

            boton.setAttribute('aria-pressed', marcado ? 'true' : 'false');

            var etiqueta = boton.getAttribute('data-etiqueta') || 'esta propiedad';

            boton.setAttribute('aria-label', (marcado ? 'Quitar de favoritos ' : 'Guardar en favoritos ') + etiqueta);
            boton.setAttribute('title', marcado ? 'Quitar de favoritos' : 'Guardar en favoritos');
        });
    }

    document.addEventListener('click', function (evento) {
        var boton = evento.target.closest('[data-favorito]');

        if (!boton) {
            return;
        }

        var codigo = boton.getAttribute('data-favorito');

        if (esFavorito(codigo)) {
            favoritos = favoritos.filter(function (guardado) {
                return guardado !== codigo;
            });
        } else {
            favoritos = favoritos.concat(codigo);
        }

        favoritosGuardados(favoritos);
        pintarCorazones();
        pintarContador();

        // Si el listado está filtrado por favoritos, el cambio se nota al instante.
        var listado = document.querySelector('[data-listado]');

        if (listado && document.querySelector('[data-solo-favoritos][aria-pressed="true"]')) {
            filtrarListado();
        }
    });

    window.addEventListener('storage', function (evento) {
        if (evento.key === FAVORITOS) {
            favoritos = favoritosLeidos();
            pintarCorazones();
            pintarContador();
        }
    });

    pintarCorazones();
    pintarContador();

    /* =========================================================================
       5. Buscador de la portada

       No se inventa resultados: lo que hace es llevar los filtros escritos a la
       URL del listado, que es quien los aplica al llegar.
       ========================================================================= */

    (function buscador() {
        var formulario = document.querySelector('[data-buscador]');

        if (!formulario) {
            return;
        }

        var pestanas = Array.prototype.slice.call(formulario.querySelectorAll('[data-operacion]'));
        var operacion = formulario.querySelector('input[name="operacion"]');

        pestanas.forEach(function (pestana) {
            pestana.addEventListener('click', function () {
                pestanas.forEach(function (otra) {
                    otra.setAttribute('aria-pressed', otra === pestana ? 'true' : 'false');
                });

                if (operacion) {
                    operacion.value = pestana.getAttribute('data-operacion');
                }
            });
        });

        formulario.addEventListener('submit', function (evento) {
            evento.preventDefault();

            var datos = new FormData(formulario);
            var partes = [];

            datos.forEach(function (valor, clave) {
                if (String(valor) !== '' && clave !== 'operacion') {
                    partes.push(encodeURIComponent(clave) + '=' + encodeURIComponent(valor));
                }
            });

            if (operacion && operacion.value) {
                partes.unshift('operacion=' + encodeURIComponent(operacion.value));
            }

            window.location.href = formulario.getAttribute('action') + (partes.length ? '?' + partes.join('&') : '');
        });
    })();

    /* =========================================================================
       6. Listado: filtros, orden y favoritos

       Cada ficha del HTML lleva sus datos en atributos, así que el filtro no
       depende de ningún archivo de datos: lo que se ve es lo que se filtra.
       ========================================================================= */

    var filtros = {
        operacion: 'todas',
        distrito: '',
        tipo: '',
        dormitorios: '',
        precio: '',
        texto: '',
        orden: 'destacadas',
        soloFavoritos: false
    };

    function filtrarListado() {
        var listado = document.querySelector('[data-listado]');

        if (!listado) {
            return;
        }

        var fichas = Array.prototype.slice.call(listado.querySelectorAll('[data-propiedad]'));
        var visibles = [];

        fichas.forEach(function (ficha) {
            var cumple = true;

            if (filtros.operacion !== 'todas' && ficha.getAttribute('data-operacion') !== filtros.operacion) {
                cumple = false;
            }

            if (cumple && filtros.distrito && ficha.getAttribute('data-distrito') !== filtros.distrito) {
                cumple = false;
            }

            if (cumple && filtros.tipo && ficha.getAttribute('data-tipo') !== filtros.tipo) {
                cumple = false;
            }

            if (cumple && filtros.dormitorios) {
                var dormitorios = leerNumero(ficha.getAttribute('data-dormitorios'));

                cumple = filtros.dormitorios === '4+' ? dormitorios >= 4 : dormitorios === parseInt(filtros.dormitorios, 10);
            }

            if (cumple && filtros.precio) {
                // El tope se compara contra el precio en soles de la ficha, para
                // que el mismo control sirva en venta y en alquiler.
                cumple = leerNumero(ficha.getAttribute('data-precio')) <= leerNumero(filtros.precio);
            }

            if (cumple && filtros.soloFavoritos && !esFavorito(ficha.getAttribute('data-codigo'))) {
                cumple = false;
            }

            if (cumple && filtros.texto) {
                var texto = (ficha.getAttribute('data-busqueda') || '').toLowerCase();
                var buscado = filtros.texto.toLowerCase();
                var palabras = buscado.split(/\s+/).filter(Boolean);

                cumple = palabras.every(function (palabra) {
                    return texto.indexOf(palabra) !== -1;
                });
            }

            if (cumple) {
                visibles.push(ficha);
            }

            ficha.hidden = !cumple;
        });

        // Orden: precio de menor a mayor, de mayor a menor, área o el orden del
        // HTML (destacadas primero), que es el que se ve sin JavaScript.
        if (filtros.orden !== 'destacadas') {
            visibles.sort(function (a, b) {
                if (filtros.orden === 'precio-asc') {
                    return leerNumero(a.getAttribute('data-precio')) - leerNumero(b.getAttribute('data-precio'));
                }

                if (filtros.orden === 'precio-desc') {
                    return leerNumero(b.getAttribute('data-precio')) - leerNumero(a.getAttribute('data-precio'));
                }

                return leerNumero(b.getAttribute('data-area')) - leerNumero(a.getAttribute('data-area'));
            });

            visibles.forEach(function (ficha) {
                listado.appendChild(ficha);
            });
        }

        var resumen = document.querySelector('[data-resumen]');
        var vacio = document.querySelector('[data-vacio]');

        if (resumen) {
            resumen.textContent = visibles.length === 1
                ? '1 propiedad encontrada'
                : formatoNumero.format(visibles.length) + ' propiedades encontradas';
        }

        if (vacio) {
            vacio.classList.toggle('listado__vacio--visible', visibles.length === 0);
        }

        var pie = document.querySelector('[data-listado-pie]');

        if (pie) {
            pie.hidden = visibles.length === 0;
        }
    }

    (function listado() {
        var listado = document.querySelector('[data-listado]');

        if (!listado) {
            return;
        }

        var parametros = new URLSearchParams(window.location.search);

        var asignarSelect = function (nombre, valor) {
            var campo = document.querySelector('[data-filtro="' + nombre + '"]');

            if (campo && valor) {
                campo.value = valor;
            }
        };

        var operacion = (parametros.get('operacion') || '').replace(/[^a-z]/g, '');
        var distrito = (parametros.get('distrito') || '').replace(/[^a-z-]/g, '');
        var tipo = (parametros.get('tipo') || '').replace(/[^a-z-]/g, '');
        var dormitorios = (parametros.get('dormitorios') || '').replace(/[^0-9+]/g, '');
        var precio = (parametros.get('precio') || '').replace(/[^0-9]/g, '');
        var texto = (parametros.get('texto') || '').slice(0, 60);
        var orden = (parametros.get('orden') || '').replace(/[^a-z-]/g, '');

        if (['venta', 'alquiler'].indexOf(operacion) !== -1) {
            filtros.operacion = operacion;
        }

        if (['lima', 'miraflores', 'san-isidro', 'barranco', 'surco', 'san-borja', 'la-molina'].indexOf(distrito) !== -1) {
            filtros.distrito = distrito;
        }

        if (['departamento', 'casa'].indexOf(tipo) !== -1) {
            filtros.tipo = tipo;
        }

        if (['1', '2', '3', '4+'].indexOf(dormitorios) !== -1) {
            filtros.dormitorios = dormitorios;
        }

        if (precio) {
            filtros.precio = precio;
        }

        if (texto) {
            filtros.texto = texto;
        }

        if (['precio-asc', 'precio-desc', 'area', 'destacadas'].indexOf(orden) !== -1) {
            filtros.orden = orden;
        }

        // El botón del contador de la cabecera abre el listado con ?favoritos=1:
        // llega ya filtrado por los inmuebles guardados en este navegador.
        if (parametros.get('favoritos') === '1') {
            filtros.soloFavoritos = true;

            var botonFavoritos = document.querySelector('[data-solo-favoritos]');

            if (botonFavoritos) {
                botonFavoritos.setAttribute('aria-pressed', 'true');
            }
        }

        var chips = Array.prototype.slice.call(document.querySelectorAll('[data-operacion-filtro]'));

        function pintarChips() {
            chips.forEach(function (chip) {
                chip.setAttribute('aria-pressed', chip.getAttribute('data-operacion-filtro') === filtros.operacion ? 'true' : 'false');
            });
        }

        chips.forEach(function (chip) {
            chip.addEventListener('click', function () {
                filtros.operacion = chip.getAttribute('data-operacion-filtro');
                pintarChips();
                filtrarListado();
            });
        });

        pintarChips();

        ['distrito', 'tipo', 'dormitorios', 'precio', 'texto', 'orden'].forEach(function (nombre) {
            var campo = document.querySelector('[data-filtro="' + nombre + '"]');

            if (!campo) {
                return;
            }

            if (campo.value !== '') {
                filtros[nombre] = campo.value;
            }

            var evento = campo.tagName === 'SELECT' || campo.type === 'range' ? 'change' : 'input';

            campo.addEventListener(evento, function () {
                filtros[nombre] = campo.value;

                if (nombre === 'texto' || nombre === 'precio') {
                    return;
                }

                filtrarListado();
            });
        });

        // La búsqueda por texto y el precio esperan a que se termine de escribir
        // o de arrastrar el control: filtrar en cada tecla hace parpadear la
        // rejilla y no aporta nada.
        var espera = null;
        var programar = function () {
            window.clearTimeout(espera);
            espera = window.setTimeout(filtrarListado, 220);
        };

        ['texto', 'precio'].forEach(function (nombre) {
            var campo = document.querySelector('[data-filtro="' + nombre + '"]');

            if (campo) {
                campo.addEventListener('input', programar);
            }
        });

        var soloFavoritos = document.querySelector('[data-solo-favoritos]');

        if (soloFavoritos) {
            soloFavoritos.addEventListener('click', function () {
                filtros.soloFavoritos = soloFavoritos.getAttribute('aria-pressed') !== 'true';
                soloFavoritos.setAttribute('aria-pressed', filtros.soloFavoritos ? 'true' : 'false');
                filtrarListado();
            });
        }

        // Hay dos botones de limpieza: el de la barra y el del estado vacío.
        document.querySelectorAll('[data-limpiar]').forEach(function (limpiar) {
            limpiar.addEventListener('click', function () {
                filtros = {
                    operacion: 'todas',
                    distrito: '',
                    tipo: '',
                    dormitorios: '',
                    precio: '',
                    texto: '',
                    orden: 'destacadas',
                    soloFavoritos: false
                };

                document.querySelectorAll('[data-filtro]').forEach(function (campo) {
                    if (campo.tagName === 'SELECT' || campo.type === 'range') {
                        campo.value = '';
                    } else {
                        campo.value = '';
                    }
                });

                if (soloFavoritos) {
                    soloFavoritos.setAttribute('aria-pressed', 'false');
                }

                if (window.history.replaceState) {
                    window.history.replaceState(null, '', window.location.pathname);
                }

                pintarChips();
                filtrarListado();
            });
        });

        var contarFavoritos = document.querySelector('[data-solo-favoritos]');

        if (contarFavoritos && favoritos.length === 0) {
            contarFavoritos.setAttribute('title', 'Todavía no guardaste ninguna propiedad');
        }

        filtrarListado();
    })();

    /* =========================================================================
       7. Ficha de inmueble: galería, visor y simulador
       ========================================================================= */

    (function galeria() {
        var galeria = document.querySelector('[data-galeria]');

        if (!galeria) {
            return;
        }

        var tiras = Array.prototype.slice.call(galeria.querySelectorAll('[data-tira]'));
        var principal = galeria.querySelector('[data-principal]');
        var visor = document.querySelector('[data-visor]');
        var visorImagen = visor ? visor.querySelector('[data-visor-imagen]') : null;
        var visorPie = visor ? visor.querySelector('[data-visor-pie]') : null;
        var actual = 0;
        var ultimoFoco = null;

        function fotoDe(indice) {
            var tira = tiras[(indice + tiras.length) % tiras.length];

            return {
                src: tira.getAttribute('data-imagen'),
                alt: tira.getAttribute('data-alt') || '',
                posicion: tira.getAttribute('data-posicion') || ''
            };
        }

        function pintar(indice) {
            actual = (indice + tiras.length) % tiras.length;

            var foto = fotoDe(actual);

            if (principal) {
                principal.src = foto.src;
                principal.alt = foto.alt;
            }

            tiras.forEach(function (tira, posicion) {
                if (posicion === actual) {
                    tira.setAttribute('aria-current', 'true');
                } else {
                    tira.removeAttribute('aria-current');
                }
            });

            if (visorImagen) {
                visorImagen.src = foto.src;
                visorImagen.alt = foto.alt;
            }

            if (visorPie) {
                visorPie.textContent = foto.posicion;
            }
        }

        tiras.forEach(function (tira, posicion) {
            tira.addEventListener('click', function () {
                pintar(posicion);
            });
        });

        function mostrarVisor(abierto) {
            if (!visor) {
                return;
            }

            visor.hidden = !abierto;
            document.body.classList.toggle('visor-abierto', abierto);

            var cerrar = visor.querySelector('[data-visor-cerrar]');

            if (abierto) {
                ultimoFoco = document.activeElement;

                if (cerrar) {
                    cerrar.focus();
                }
            } else if (ultimoFoco) {
                ultimoFoco.focus();
            }
        }

        var abrir = galeria.querySelector('[data-visor-abrir]');

        if (abrir) {
            abrir.addEventListener('click', function () {
                mostrarVisor(true);
            });
        }

        if (visor) {
            visor.addEventListener('click', function (evento) {
                if (evento.target === visor || evento.target.closest('[data-visor-cerrar]')) {
                    mostrarVisor(false);
                }

                if (evento.target.closest('[data-visor-siguiente]')) {
                    pintar(actual + 1);
                }

                if (evento.target.closest('[data-visor-anterior]')) {
                    pintar(actual - 1);
                }
            });

            document.addEventListener('keydown', function (evento) {
                if (visor.hidden) {
                    return;
                }

                if (evento.key === 'Escape') {
                    mostrarVisor(false);
                }

                if (evento.key === 'ArrowLeft') {
                    pintar(actual - 1);
                }

                if (evento.key === 'ArrowRight') {
                    pintar(actual + 1);
                }
            });
        }

        pintar(0);
    })();

    (function simulador() {
        var simulador = document.querySelector('[data-simulador]');

        if (!simulador) {
            return;
        }

        var precio = leerNumero(simulador.getAttribute('data-precio'));
        var inicial = simulador.querySelector('[data-inicial]');
        var plazo = simulador.querySelector('[data-plazo]');
        var tasa = simulador.querySelector('[data-tasa]');
        var salidaInicial = simulador.querySelector('[data-salida-inicial]');
        var salidaPlazo = simulador.querySelector('[data-salida-plazo]');
        var cuota = simulador.querySelector('[data-cuota]');
        var monto = simulador.querySelector('[data-monto]');
        var intereses = simulador.querySelector('[data-intereses]');

        function calcular() {
            var porcentaje = inicial ? parseFloat(inicial.value) : 20;
            var anios = plazo ? parseFloat(plazo.value) : 20;
            var anual = tasa ? parseFloat(tasa.value) : 9.5;

            var pie = Math.round(precio * porcentaje / 100);
            var financiado = Math.max(precio - pie, 0);
            var meses = Math.max(Math.round(anios * 12), 1);
            var mensual = anual / 100 / 12;

            // Cuota francesa: interés compuesto sobre el saldo. Con tasa 0 se
            // reparte el monto en partes iguales en vez de dividir entre cero.
            var pago = mensual === 0
                ? financiado / meses
                : financiado * mensual / (1 - Math.pow(1 + mensual, -meses));

            if (salidaInicial) {
                salidaInicial.textContent = formatoNumero.format(porcentaje) + ' % · ' + formatoSoles.format(pie);
            }

            if (salidaPlazo) {
                salidaPlazo.textContent = formatoNumero.format(anios) + (anios === 1 ? ' año' : ' años');
            }

            if (cuota) {
                cuota.textContent = formatoSoles.format(Math.round(pago)) + ' / mes';
            }

            if (monto) {
                monto.textContent = formatoSoles.format(financiado);
            }

            if (intereses) {
                intereses.textContent = formatoSoles.format(Math.max(Math.round(pago * meses - financiado), 0));
            }
        }

        [inicial, plazo, tasa].forEach(function (control) {
            if (control) {
                control.addEventListener('input', calcular);
                control.addEventListener('change', calcular);
            }
        });

        calcular();
    })();

    /* =========================================================================
       8. Formularios de contacto y de valoración

       Validan campo por campo, avisan de lo que falta y, al enviarse bien,
       declaran que es una demostración en vez de fingir un envío. La validación
       se apaga sola el día que el formulario tenga un destino real: basta con
       quitar el atributo `data-demo`.
       ========================================================================= */

    document.querySelectorAll('form[data-formulario]').forEach(function (formulario) {
        var aviso = formulario.querySelector('[data-aviso]');
        var esDemo = formulario.getAttribute('data-demo') === 'true';

        // Si se llega desde una ficha (`contacto.html?propiedad=AL-2401`), esa
        // propiedad aparece ya elegida, igual que el servicio en la plantilla
        // del estudio contable.
        var pedido = (new URLSearchParams(window.location.search).get('propiedad') || '').replace(/[^A-Za-z0-9-]/g, '');

        if (pedido && formulario.propiedad) {
            var opcion = formulario.propiedad.querySelector('option[value="' + pedido + '"]');

            if (opcion) {
                formulario.propiedad.value = pedido;
            }
        }

        function contenedorDe(campo) {
            return campo.closest('.campo') || campo.closest('.casilla') || campo.closest('.grupo-radio');
        }

        function marcar(campo, conError) {
            var contenedor = contenedorDe(campo);

            if (!contenedor) {
                return;
            }

            if (contenedor.classList.contains('grupo-radio')) {
                contenedor.classList.toggle('grupo-radio--error', conError);

                return;
            }

            contenedor.classList.toggle(contenedor.classList.contains('casilla') ? 'casilla--error' : 'campo--error', conError);
        }

        function valido(campo) {
            if (campo.type === 'radio') {
                return formulario.querySelector('input[name="' + campo.name + '"]:checked') !== null;
            }

            if (campo.type === 'checkbox') {
                return campo.checked;
            }

            if (campo.required && campo.value.trim() === '') {
                return false;
            }

            if (campo.type === 'email' && campo.value.trim() !== '') {
                return /^[^\s@]+@[^\s@]+\.[a-z]{2,}$/i.test(campo.value.trim());
            }

            if (campo.type === 'tel' && campo.value.trim() !== '') {
                return campo.value.replace(/[^0-9]/g, '').length >= 6;
            }

            return true;
        }

        var campos = Array.prototype.slice.call(formulario.querySelectorAll('input, select, textarea'));

        campos.forEach(function (campo) {
            var evento = campo.tagName === 'SELECT' ? 'change' : 'blur';

            campo.addEventListener(evento, function () {
                if (campo.required || campo.value.trim() !== '') {
                    marcar(campo, !valido(campo));
                }
            });

            campo.addEventListener('input', function () {
                if (contenedorDe(campo) && contenedorDe(campo).classList.contains('campo--error')) {
                    marcar(campo, !valido(campo));
                }
            });
        });

        formulario.addEventListener('submit', function (evento) {
            evento.preventDefault();

            var primerFallo = null;
            var fallos = 0;

            // Los radios se marcan una vez por grupo aunque haya varios.
            var vistos = [];

            campos.forEach(function (campo) {
                if (!campo.required || (campo.type === 'radio' && vistos.indexOf(campo.name) !== -1)) {
                    return;
                }

                if (campo.type === 'radio') {
                    vistos.push(campo.name);
                }

                var correcto = valido(campo);

                marcar(campo, !correcto);

                if (!correcto) {
                    fallos++;

                    if (primerFallo === null) {
                        primerFallo = campo;
                    }
                }
            });

            if (fallos > 0) {
                if (aviso) {
                    aviso.classList.add('aviso--visible');
                    aviso.textContent = 'Revisa los campos marcados: falta completar ' + fallos + (fallos === 1 ? ' campo.' : ' campos.');
                }

                if (primerFallo) {
                    primerFallo.focus();
                }

                return;
            }

            if (esDemo) {
                if (aviso) {
                    aviso.classList.add('aviso--visible');
                    aviso.textContent = 'Gracias. Esto es una demostración: el formulario no envía datos y nadie recibirá este mensaje.';
                }

                formulario.reset();

                return;
            }

            formulario.submit();
        });
    });

    /* =========================================================================
       9. Movimiento: aparición al hacer scroll y contadores
       ========================================================================= */

    (function movimiento() {
        var elementos = document.querySelectorAll('.js-revela');

        function mostrarTodo() {
            elementos.forEach(function (elemento) {
                elemento.classList.add('js-revela--visible');
            });
        }

        if (movimientoReducido || !('IntersectionObserver' in window)) {
            mostrarTodo();
        } else {
            var observador = new IntersectionObserver(function (entradas) {
                entradas.forEach(function (entrada) {
                    if (entrada.isIntersecting) {
                        entrada.target.classList.add('js-revela--visible');
                        observador.unobserve(entrada.target);
                    }
                });
            }, { rootMargin: '0px 0px -10% 0px', threshold: 0.1 });

            elementos.forEach(function (elemento) {
                observador.observe(elemento);
            });
        }

        var contadores = document.querySelectorAll('[data-contador]');

        function animar(contador) {
            var destino = leerNumero(contador.getAttribute('data-contador'));
            var sufijo = contador.getAttribute('data-sufijo') || '';

            if (movimientoReducido) {
                contador.textContent = formatoNumero.format(destino) + sufijo;

                return;
            }

            var inicio = null;

            function paso(momento) {
                if (inicio === null) {
                    inicio = momento;
                }

                var avance = Math.min((momento - inicio) / 1200, 1);
                var suavizado = 1 - Math.pow(1 - avance, 3);

                contador.textContent = formatoNumero.format(Math.round(destino * suavizado)) + sufijo;

                if (avance < 1) {
                    window.requestAnimationFrame(paso);
                }
            }

            window.requestAnimationFrame(paso);
        }

        if (contadores.length) {
            if (!('IntersectionObserver' in window)) {
                contadores.forEach(animar);
            } else {
                var observadorContadores = new IntersectionObserver(function (entradas) {
                    entradas.forEach(function (entrada) {
                        if (entrada.isIntersecting) {
                            animar(entrada.target);
                            observadorContadores.unobserve(entrada.target);
                        }
                    });
                }, { threshold: 0.4 });

                contadores.forEach(function (contador) {
                    observadorContadores.observe(contador);
                });
            }
        }
    })();
})();
