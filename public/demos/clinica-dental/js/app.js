/*
   =============================================================================
   Clínica Dental Sonrisa — comportamiento de la demostración
   =============================================================================

   JavaScript propio, sin librerías. Hace cinco cosas:

     1. Menú desplegable de móvil.
     2. Aparición de bloques al hacer scroll.
     3. Contadores de las cifras de la portada.
     4. Sombra de la cabecera cuando la página baja.
     5. Validación del formulario de cita.

   Nada de esto es imprescindible para leer la página: sin JavaScript se ve todo
   —los bloques aparecen porque la clase que los oculta la pone este archivo— y
   el menú queda como una lista de enlaces.

   El formulario NO envía datos: es una demostración. Al enviarlo se explica qué
   haría en un sitio real, para que nadie crea que su cita quedó registrada.
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
       4. Cabecera pegada
       --------------------------------------------------------------------- */

    var cabecera = document.getElementById('cabecera');

    if (cabecera) {
        var sombra = function () {
            cabecera.classList.toggle('cabecera--pegada', window.scrollY > 12);
        };

        sombra();
        window.addEventListener('scroll', sombra, { passive: true });
    }

    /* ---------------------------------------------------------------------
       5. Formulario de cita
       --------------------------------------------------------------------- */

    var formulario = document.getElementById('formulario-cita');

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
                { campo: formulario.telefono, ok: function (v) { return v.replace(/\D/g, '').length === 9; } },
                { campo: formulario.tratamiento, ok: function (v) { return v !== ''; } },
                { campo: formulario.dia, ok: function (v) { return v !== ''; } },
                { campo: formulario.acepto, ok: function (_, campo) { return campo.checked; } }
            ];
            var valido = true;

            aRevisar.forEach(function (revisión) {
                var correcto = revisión.ok(revisión.campo.value, revisión.campo);

                marcarError(revisión.campo, !correcto);

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

            // Demostración: aquí no hay servidor que reciba la cita. Se dice
            // claro en vez de simular un envío que nunca ocurrió.
            if (formulario.dataset.demo === 'true') {
                if (aviso) {
                    aviso.textContent = 'Demostración: el formulario funciona, pero no envía datos. '
                        + 'En tu sitio la cita llega a tu WhatsApp o a tu correo, y podemos confirmarla automáticamente.';
                }

                return;
            }

            formulario.submit();
        });
    }

    /* ---------------------------------------------------------------------
       Año del pie
       --------------------------------------------------------------------- */

    var anio = document.getElementById('anio');

    if (anio) {
        anio.textContent = String(new Date().getFullYear());
    }
})();
