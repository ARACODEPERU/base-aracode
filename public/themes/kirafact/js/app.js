/**
 * JS del sitio KIRAFACT.
 * Vanilla, sin dependencias ni bundler: se sirve tal cual desde el tema.
 *
 * Todo lo de aquí es mejora progresiva: sin JavaScript el sitio se ve completo,
 * el menú móvil no se abre (y no hace falta), los acordeones siguen
 * funcionando porque son <details> nativos y los botones son enlaces reales.
 */
(function () {
    'use strict';

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

    /* ---------- 4) Aparición de bloques al desplazar ---------- */
    var revealItems = document.querySelectorAll('.kf-reveal');

    if (revealItems.length) {
        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(
                function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');
                            observer.unobserve(entry.target);
                        }
                    });
                },
                { threshold: 0.12, rootMargin: '0px 0px -60px' }
            );

            revealItems.forEach(function (item) {
                observer.observe(item);
            });
        } else {
            revealItems.forEach(function (item) {
                item.classList.add('is-visible');
            });
        }
    }
})();
