/**
 * JS del sitio KIRAFACT.
 * Vanilla, sin dependencias ni bundler: se sirve tal cual desde el tema.
 *
 * Reparto del movimiento entre CSS y JS:
 *   - El JS solo hace lo que el CSS no puede: retardos escalonados, saber qué
 *     bloque ya entró en pantalla y marcar la sección activa.
 *   - Los efectos continuos (barra de progreso, deriva del hero, riel de los
 *     pasos, números que se encienden) los hace el CSS con animation-timeline.
 *     Aquí solo queda un respaldo para la barra de progreso, y únicamente si
 *     el navegador no soporta esa API.
 *
 * Fuera del movimiento, aquí vive el interruptor de tema claro / oscuro: los
 * colores los pone el CSS (sección 10 de kirafact.css) y este archivo solo
 * cambia el atributo data-tema del <html> y recuerda la elección.
 *
 * Todo es mejora progresiva: sin JavaScript el sitio se ve completo (el
 * revelado solo actúa bajo la clase .js), los acordeones son <details> nativos
 * y los botones son enlaces reales.
 */
(function () {
    'use strict';

    var prefiereMenosMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)');

    function soportaAnimacionLigadaAlScroll(consulta) {
        return !!(window.CSS && CSS.supports && CSS.supports(consulta));
    }

    /* ---------- 1) Cabecera con fondo al desplazar ---------- */
    var nav = document.getElementById('kfNav');

    function updateNav() {
        if (!nav) {
            return;
        }
        nav.classList.toggle('is-scrolled', window.scrollY > 24);
    }

    window.addEventListener('scroll', updateNav, { passive: true });
    updateNav();

    /* ---------- 2) Menú móvil ---------- */
    var menu = document.getElementById('kfMobileMenu');
    var openBtn = document.getElementById('kfMenuBtn');
    var closeBtn = document.getElementById('kfMenuClose');

    function setMenu(open) {
        if (!menu) {
            return;
        }

        menu.classList.toggle('is-open', open);
        menu.setAttribute('aria-hidden', open ? 'false' : 'true');

        if (openBtn) {
            openBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        document.body.style.overflow = open ? 'hidden' : '';
    }

    if (openBtn) {
        openBtn.addEventListener('click', function () {
            setMenu(true);
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            setMenu(false);
        });
    }

    if (menu) {
        menu.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                setMenu(false);
            });
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            setMenu(false);
        }
    });

    /* ---------- 3) Sección activa en la navegación ---------- */
    /* Los enlaces de la cabecera apuntan a secciones de la portada: aquí se
       marca la que está a la vista. Solo actúa en la portada. */
    var enlacesAncla = Array.prototype.slice.call(document.querySelectorAll('.kf-nav-link[data-ancla]'));

    if (enlacesAncla.length && 'IntersectionObserver' in window) {
        var secciones = enlacesAncla
            .map(function (enlace) {
                return document.getElementById(enlace.getAttribute('data-ancla'));
            })
            .filter(Boolean);

        if (secciones.length) {
            var visibles = new Set();

            var observador = new IntersectionObserver(
                function (entradas) {
                    entradas.forEach(function (entrada) {
                        if (entrada.isIntersecting) {
                            visibles.add(entrada.target.id);
                        } else {
                            visibles.delete(entrada.target.id);
                        }
                    });

                    enlacesAncla.forEach(function (enlace) {
                        var seccion = enlace.getAttribute('data-ancla');
                        var activo = visibles.has(seccion);
                        enlace.classList.toggle('is-active', activo);
                        if (activo) {
                            enlace.setAttribute('aria-current', 'true');
                        } else {
                            enlace.removeAttribute('aria-current');
                        }
                    });
                },
                { rootMargin: '-45% 0px -50% 0px' }
            );

            secciones.forEach(function (seccion) {
                observador.observe(seccion);
            });
        }
    }

    /* ---------- 4) Aparición de bloques, escalonada ---------- */
    var bloques = Array.prototype.slice.call(document.querySelectorAll('.kf-reveal'));

    if (bloques.length) {
        /* Retardo creciente dentro de cada sección: los bloques vecinos entran
           uno detrás de otro (70 ms) en lugar de todos a la vez. Se limita a
           cuatro pasos para que el último no llegue tarde.
           Con movimiento reducido no se escribe ningún retardo: el CSS que los
           usa ni siquiera está activo, así que sería un estilo de más. */
        if (!prefiereMenosMovimiento.matches) {
            var contadorPorSeccion = new Map();

            bloques.forEach(function (bloque) {
                var seccion = bloque.closest('section, footer, header') || document.body;
                var indice = contadorPorSeccion.get(seccion) || 0;

                if (indice > 0) {
                    bloque.style.setProperty('--kf-delay', Math.min(indice, 4) * 70 + 'ms');
                }

                contadorPorSeccion.set(seccion, indice + 1);
            });
        }

        if ('IntersectionObserver' in window) {
            var observadorBloques = new IntersectionObserver(
                function (entradas) {
                    entradas.forEach(function (entrada) {
                        if (entrada.isIntersecting) {
                            entrada.target.classList.add('is-visible');
                            observadorBloques.unobserve(entrada.target);
                        }
                    });
                },
                { threshold: 0.12, rootMargin: '0px 0px -60px' }
            );

            /* Un fotograma de espera para que la transición se vea también en
               los bloques que ya están en pantalla al cargar. */
            window.requestAnimationFrame(function () {
                bloques.forEach(function (bloque) {
                    observadorBloques.observe(bloque);
                });
            });
        } else {
            bloques.forEach(function (bloque) {
                bloque.classList.add('is-visible');
            });
        }
    }

    /* ---------- 5) Barra de progreso (solo como respaldo) ---------- */
    /* Con soporte de animation-timeline la barra la mueve el compositor y aquí
       no se hace nada. Si no lo hay, se mueve con scaleX en cada fotograma. */
    var barraProgreso = document.querySelector('.kf-progress > span');

    if (
        barraProgreso &&
        !prefiereMenosMovimiento.matches &&
        !soportaAnimacionLigadaAlScroll('animation-timeline: scroll()')
    ) {
        var pendiente = false;

        var pintarProgreso = function () {
            pendiente = false;
            var recorrido = document.documentElement.scrollHeight - window.innerHeight;
            var avance = recorrido > 0 ? Math.min(1, window.scrollY / recorrido) : 0;
            barraProgreso.style.transform = 'scaleX(' + avance + ')';
        };

        var pedirPintado = function () {
            if (pendiente) {
                return;
            }
            pendiente = true;
            window.requestAnimationFrame(pintarProgreso);
        };

        window.addEventListener('scroll', pedirPintado, { passive: true });
        window.addEventListener('resize', pedirPintado, { passive: true });
        pintarProgreso();
    }

    /* ---------- 6) Tema claro / oscuro ---------- */
    /* El estado vive en el atributo data-tema del <html>, que el layout dejó en
       'claro', o ya en 'oscuro' si había una elección guardada (lo aplica antes
       de pintar, así que aquí no hay nada que corregir a la vista).

       El interruptor muestra el icono del modo AL QUE SE VA: la luna en claro y
       el sol en oscuro. Aquí solo se cambia el atributo y se pone al día el
       texto del botón, para quien lo use con lector de pantalla. */
    var raiz = document.documentElement;
    var interruptores = Array.prototype.slice.call(document.querySelectorAll('[data-tema-switch]'));

    if (interruptores.length) {
        var aplicarTema = function (tema, recordar) {
            var oscuro = tema === 'oscuro';
            var etiqueta = oscuro ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro';

            raiz.setAttribute('data-tema', oscuro ? 'oscuro' : 'claro');

            interruptores.forEach(function (boton) {
                boton.setAttribute('aria-pressed', oscuro ? 'true' : 'false');
                boton.setAttribute('aria-label', etiqueta);
                boton.setAttribute('title', etiqueta);
            });

            if (recordar) {
                try {
                    window.localStorage.setItem('kf-tema', oscuro ? 'oscuro' : 'claro');
                } catch (e) {
                    /* Sin almacenamiento, la elección vale solo para esta página. */
                }
            }
        };

        var temaEnDocumento = function () {
            return raiz.getAttribute('data-tema') === 'oscuro' ? 'oscuro' : 'claro';
        };

        interruptores.forEach(function (boton) {
            boton.addEventListener('click', function () {
                aplicarTema(temaEnDocumento() === 'oscuro' ? 'claro' : 'oscuro', true);
            });
        });

        /* Deja el botón en consonancia con el tema que ya trae el documento */
        aplicarTema(temaEnDocumento(), false);

        /* Si el tema se cambia en otra pestaña, esta se pone al día igual */
        window.addEventListener('storage', function (evento) {
            if (evento.key === 'kf-tema') {
                aplicarTema(evento.newValue === 'oscuro' ? 'oscuro' : 'claro', false);
            }
        });
    }
})();
