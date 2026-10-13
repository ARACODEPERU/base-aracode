/*
   =============================================================================
   Mesa Norte — comportamiento de la demostración
   =============================================================================

   JavaScript propio, sin librerías. Hace ocho cosas:

     1. Menú desplegable de móvil.
     2. Aparición de bloques al hacer scroll.
     3. Contadores de las cifras de la portada.
     4. Filtro del índice de proyectos.
     5. Cabecera con filete marcado al bajar.
     6. Validación del formulario de encargo.
     7. Slider de la portada: flechas, puntos, teclado y arrastre con el dedo.
     8. Interruptor de tema claro / oscuro.

   Nada de esto es imprescindible para leer la página: sin JavaScript se ve todo
   —los bloques aparecen porque la clase que los oculta la pone este archivo—, el
   índice muestra los seis proyectos y el menú queda como una lista de enlaces.

   El formulario NO envía datos: es una demostración. Al enviarlo se explica qué
   haría en un sitio real, para que nadie crea que su encargo quedó registrado.
*/

(function () {
    'use strict';

    // Marca que hay JavaScript: es lo que habilita las animaciones de aparición.
    document.documentElement.classList.add('js');

    var movimientoReducido = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ---------------------------------------------------------------------
       1. Menú de móvil
       --------------------------------------------------------------------- */

    var botonMenu = document.getElementById('menu-boton');
    var menu = document.getElementById('menu-principal');

    function abrirMenu(abierto) {
        if (!menu || !botonMenu) {
            return;
        }

        menu.classList.toggle('menu--abierto', abierto);
        botonMenu.setAttribute('aria-expanded', abierto ? 'true' : 'false');
    }

    if (botonMenu && menu) {
        botonMenu.addEventListener('click', function () {
            abrirMenu(!menu.classList.contains('menu--abierto'));
        });

        // Al elegir un destino el menú se cierra: si no, tapa la sección.
        menu.addEventListener('click', function (evento) {
            if (evento.target.closest('a')) {
                abrirMenu(false);
            }
        });

        document.addEventListener('keydown', function (evento) {
            if (evento.key === 'Escape') {
                abrirMenu(false);
            }
        });

        // Si la ventana pasa a escritorio con el menú abierto, se cierra.
        window.addEventListener('resize', function () {
            if (window.innerWidth > 900) {
                abrirMenu(false);
            }
        });
    }

    /* ---------------------------------------------------------------------
       2. Aparición al hacer scroll
       --------------------------------------------------------------------- */

    var bloques = document.querySelectorAll('.revelar');

    if (!movimientoReducido && 'IntersectionObserver' in window) {
        var observador = new IntersectionObserver(function (entradas) {
            entradas.forEach(function (entrada) {
                if (entrada.isIntersecting) {
                    entrada.target.classList.add('visible');
                    observador.unobserve(entrada.target);
                }
            });
        }, { rootMargin: '0px 0px -10% 0px', threshold: 0.12 });

        bloques.forEach(function (bloque, indice) {
            // Un retraso corto y creciente evita que todo aparezca de golpe.
            bloque.style.transitionDelay = Math.min(indice * 60, 240) + 'ms';
            observador.observe(bloque);
        });
    } else {
        bloques.forEach(function (bloque) {
            bloque.classList.add('visible');
        });
    }

    /* ---------------------------------------------------------------------
       3. Contadores
       --------------------------------------------------------------------- */

    function animarContador(elemento) {
        var objetivo = parseFloat(elemento.dataset.contador) || 0;
        var prefijo = elemento.dataset.prefijo || '';
        var divisor = parseFloat(elemento.dataset.divisor) || 1;
        var duracion = 1400;
        var inicio = null;

        function paso(marca) {
            if (inicio === null) {
                inicio = marca;
            }

            var avance = Math.min((marca - inicio) / duracion, 1);
            // Suavizado: arranca rápido y frena al final.
            var suave = 1 - Math.pow(1 - avance, 3);
            var valor = (objetivo * suave) / divisor;

            elemento.textContent = prefijo + (divisor > 1 ? valor.toFixed(1) : Math.round(valor).toLocaleString('es-PE'));

            if (avance < 1) {
                window.requestAnimationFrame(paso);
            }
        }

        window.requestAnimationFrame(paso);
    }

    var contadores = document.querySelectorAll('[data-contador]');

    if (contadores.length) {
        if (movimientoReducido || !('IntersectionObserver' in window)) {
            contadores.forEach(function (elemento) {
                var objetivo = parseFloat(elemento.dataset.contador) || 0;
                var divisor = parseFloat(elemento.dataset.divisor) || 1;

                elemento.textContent = (elemento.dataset.prefijo || '')
                    + (divisor > 1 ? (objetivo / divisor).toFixed(1) : Math.round(objetivo).toLocaleString('es-PE'));
            });
        } else {
            var observadorContadores = new IntersectionObserver(function (entradas) {
                entradas.forEach(function (entrada) {
                    if (entrada.isIntersecting) {
                        animarContador(entrada.target);
                        observadorContadores.unobserve(entrada.target);
                    }
                });
            }, { threshold: 0.5 });

            contadores.forEach(function (elemento) {
                observadorContadores.observe(elemento);
            });
        }
    }

    /* ---------------------------------------------------------------------
       4. Filtro del índice de proyectos

       Las piezas están todas en el HTML (así el buscador las lee y sin
       JavaScript se ven las seis); el filtro solo esconde las filas que no
       corresponden y avisa cuando no queda ninguna.
       --------------------------------------------------------------------- */

    var filtros = [].slice.call(document.querySelectorAll('[data-filtro]'));
    var filas = [].slice.call(document.querySelectorAll('[data-tipo]'));
    var avisoVacio = document.getElementById('indice-vacio');

    if (filtros.length && filas.length) {
        filtros.forEach(function (boton) {
            boton.addEventListener('click', function () {
                var tipo = boton.dataset.filtro;
                var visibles = 0;

                filtros.forEach(function (otro) {
                    var activo = otro === boton;

                    otro.classList.toggle('filtros__boton--activo', activo);
                    otro.setAttribute('aria-pressed', activo ? 'true' : 'false');
                });

                filas.forEach(function (fila) {
                    var coincide = tipo === 'todo' || fila.dataset.tipo === tipo;

                    fila.hidden = !coincide;

                    if (coincide) {
                        visibles++;
                    }
                });

                if (avisoVacio) {
                    avisoVacio.hidden = visibles > 0;
                }
            });
        });
    }

    /* ---------------------------------------------------------------------
       5. Cabecera pegada: no lleva sombra, engorda su filete inferior
       --------------------------------------------------------------------- */

    var cabecera = document.getElementById('cabecera');

    if (cabecera) {
        var filete = function () {
            cabecera.classList.toggle('cabecera--pegada', window.scrollY > 12);
        };

        filete();
        window.addEventListener('scroll', filete, { passive: true });
    }

    /* ---------------------------------------------------------------------
       6. Formulario de encargo
       --------------------------------------------------------------------- */

    var formulario = document.getElementById('formulario-encargo');

    if (formulario) {
        var aviso = document.getElementById('aviso-formulario');

        function marcarError(campo, conError) {
            var contenedor = campo.closest('.campo') || campo.closest('.casilla');

            if (contenedor) {
                contenedor.classList.toggle('campo--error', conError);
                contenedor.classList.toggle('casilla--error', conError);
            }

            if (conError) {
                campo.setAttribute('aria-invalid', 'true');
            } else {
                campo.removeAttribute('aria-invalid');
            }
        }

        function validar() {
            var aRevisar = [
                { campo: formulario.nombre, ok: function (v) { return v.trim().length >= 5; } },
                {
                    campo: formulario.correo,
                    ok: function (v) { return /^[^\s@]+@[^\s@]+\.[a-z]{2,}$/i.test(v.trim()); }
                },
                { campo: formulario.tipo, ok: function (v) { return v !== ''; } },
                { campo: formulario.presupuesto, ok: function (v) { return v !== ''; } },
                { campo: formulario.acepto, ok: function (_, campo) { return campo.checked; } }
            ];
            var valido = true;

            aRevisar.forEach(function (revision) {
                var correcto = revision.ok(revision.campo.value, revision.campo);

                marcarError(revision.campo, !correcto);

                if (!correcto) {
                    valido = false;
                }
            });

            return valido;
        }

        // Al corregir un campo se limpia su error, sin esperar al siguiente envío.
        formulario.addEventListener('input', function (evento) {
            if (evento.target.matches('input, select, textarea')) {
                marcarError(evento.target, false);
            }
        });

        formulario.addEventListener('submit', function (evento) {
            evento.preventDefault();

            if (!validar()) {
                var primero = formulario.querySelector('[aria-invalid="true"]');

                if (primero) {
                    primero.focus();
                }

                if (aviso) {
                    aviso.textContent = 'Revisa los campos marcados para continuar.';
                }

                return;
            }

            // Demostración: aquí no hay servidor que reciba el encargo. Se dice
            // claro en vez de simular un envío que nunca ocurrió.
            if (formulario.dataset.demo === 'true') {
                if (aviso) {
                    aviso.textContent = 'Demostración: el formulario funciona, pero no envía datos. '
                        + 'En tu sitio el encargo llega a tu WhatsApp o a tu correo, y queda registrado con su número.';
                }

                return;
            }

            formulario.submit();
        });
    }

    /* ---------------------------------------------------------------------
       7. Slider de la portada

       El hero rota la foto de fondo. Nada de esto es imprescindible: la primera
       foto se ve sin JavaScript y el texto no depende de la que esté puesta.
    */

    var carrusel = document.querySelector('[data-carrusel]');

    if (carrusel) {
        var pista = carrusel.querySelector('.portada__pista');
        var diapositivas = [].slice.call(carrusel.querySelectorAll('[data-diapositiva]'));
        var puntos = [].slice.call(carrusel.querySelectorAll('[data-carrusel-punto]'));
        var botonAnterior = carrusel.querySelector('[data-carrusel-anterior]');
        var botonSiguiente = carrusel.querySelector('[data-carrusel-siguiente]');
        var INTERVALO = 6000;
        var actual = 0;
        var temporizador = null;

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
                detener();
                irA(i);
                arrancar();
            });
        });

        // Teclado: con el foco dentro del hero, las flechas cambian de foto en
        // vez de hacer scroll.
        carrusel.addEventListener('keydown', function (evento) {
            if (evento.key !== 'ArrowLeft' && evento.key !== 'ArrowRight') {
                return;
            }

            manual(evento.key === 'ArrowRight' ? 1 : -1);
        });

        // Arrastre con el dedo. Solo táctil: con el ratón competiría con la
        // selección de texto, y para eso ya están las flechas, los puntos y el
        // teclado.
        var inicioX = null;

        carrusel.addEventListener('pointerdown', function (evento) {
            if (evento.pointerType === 'mouse') {
                return;
            }

            inicioX = evento.clientX;
            detener();
        });

        // El final del arrastre se escucha en la ventana: si el dedo suelta
        // fuera del hero, el carrusel no recibe el evento.
        window.addEventListener('pointerup', function (evento) {
            if (inicioX === null) {
                return;
            }

            var recorrido = evento.clientX - inicioX;
            inicioX = null;

            // Un cuarto del ancho ya cuenta como intención de pasar; si no, la
            // foto vuelve a su sitio sin cambiar nada.
            if (Math.abs(recorrido) > carrusel.clientWidth * 0.25) {
                avanzar(recorrido < 0 ? 1 : -1);
            } else {
                irA(actual);
            }

            arrancar();
        });

        window.addEventListener('pointercancel', function () {
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
    }

    /* ---------------------------------------------------------------------
       Año del pie
       --------------------------------------------------------------------- */

    var anio = document.getElementById('anio');

    if (anio) {
        anio.textContent = String(new Date().getFullYear());
    }

    /* ---------------------------------------------------------------------
       8. Tema claro / oscuro
       ---------------------------------------------------------------------
       El estado vive en el atributo data-tema del <html>, que el script del
       <head> ya dejó en 'claro' —o en 'oscuro' si había una elección guardada
       y la aplicó antes de pintar—. Aquí solo se cambia ese atributo y se
       pone al día el texto del botón para quien lo use con lector de pantalla.

       El interruptor muestra el icono del modo AL QUE SE VA: la luna en claro
       y el sol en oscuro. La elección se recuerda en localStorage bajo la
       clave 'portafolio-creativo-tema' (una por plantilla: en este
       repositorio todas las demos comparten origen).
    */
    var raiz = document.documentElement;
    var interruptoresTema = document.querySelectorAll('[data-tema-switch]');

    if (interruptoresTema.length) {
        var aplicarTema = function (tema, recordar) {
            var oscuro = tema === 'oscuro';
            var etiqueta = oscuro ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro';

            raiz.setAttribute('data-tema', oscuro ? 'oscuro' : 'claro');

            interruptoresTema.forEach(function (interruptor) {
                interruptor.setAttribute('aria-pressed', oscuro ? 'true' : 'false');
                interruptor.setAttribute('aria-label', etiqueta);
                interruptor.setAttribute('title', etiqueta);
            });

            if (recordar) {
                try {
                    localStorage.setItem('portafolio-creativo-tema', oscuro ? 'oscuro' : 'claro');
                } catch (error) {
                    /* Sin almacenamiento, la elección vale solo para esta página. */
                }
            }
        };

        var temaActual = function () {
            return raiz.getAttribute('data-tema') === 'oscuro' ? 'oscuro' : 'claro';
        };

        interruptoresTema.forEach(function (interruptor) {
            interruptor.addEventListener('click', function () {
                aplicarTema(temaActual() === 'oscuro' ? 'claro' : 'oscuro', true);
            });
        });

        /* Deja el botón en consonancia con el tema que ya trae el documento */
        aplicarTema(temaActual(), false);

        /* Si el tema se cambia en otra pestaña, esta se pone al día igual */
        window.addEventListener('storage', function (evento) {
            if (evento.key === 'portafolio-creativo-tema') {
                aplicarTema(evento.newValue === 'oscuro' ? 'oscuro' : 'claro', false);
            }
        });
    }
})();
