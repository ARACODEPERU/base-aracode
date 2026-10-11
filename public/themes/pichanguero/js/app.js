/**
 * JS del sitio Pichanguero.
 * Vanilla, sin dependencias ni bundler: se sirve tal cual desde el tema.
 *
 * Aparte de lo que se mueve en pantalla, aquí vive el interruptor de tema claro
 * / oscuro: los colores los pone el CSS y este archivo solo cambia el atributo
 * data-tema del <html> y recuerda la elección.
 */
(function () {
    'use strict';

    /* ---------- 1) Navbar con fondo al hacer scroll ---------- */
    var nav = document.getElementById('pgNav');

    function updateNav() {
        if (!nav) {
            return;
        }
        nav.classList.toggle('is-scrolled', window.scrollY > 24);
    }

    window.addEventListener('scroll', updateNav, { passive: true });
    updateNav();

    /* ---------- 2) Menú móvil ---------- */
    var menu = document.getElementById('pgMobileMenu');
    var openBtn = document.getElementById('pgMenuBtn');
    var closeBtn = document.getElementById('pgMenuClose');

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

    /* ---------- 3) Aparición de bloques al hacer scroll ---------- */
    var revealItems = document.querySelectorAll('.pg-reveal');

    if (revealItems.length) {
        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -60px' });

            revealItems.forEach(function (item) {
                observer.observe(item);
            });
        } else {
            revealItems.forEach(function (item) {
                item.classList.add('is-visible');
            });
        }
    }

    /* ---------- 4) Año actual donde se pida ---------- */
    document.querySelectorAll('[data-year]').forEach(function (element) {
        element.textContent = String(new Date().getFullYear());
    });

    /* ---------- 5) Tema claro / oscuro ---------- */
    /* El estado vive en el atributo data-tema del <html>, que el layout dejó en
       'claro', o ya en 'oscuro' si había una elección guardada (lo aplica antes
       de pintar, así que aquí no hay nada que corregir a la vista).

       El interruptor muestra el icono del modo AL QUE SE VA: la luna en claro y
       el sol en oscuro. Aquí solo se cambia el atributo y se pone al día el
       texto del botón, para quien lo use con lector de pantalla. */
    var root = document.documentElement;
    var themeToggles = document.querySelectorAll('[data-tema-switch]');

    if (themeToggles.length) {
        var applyTheme = function (theme, remember) {
            var dark = theme === 'oscuro';
            var label = dark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro';

            root.setAttribute('data-tema', dark ? 'oscuro' : 'claro');

            themeToggles.forEach(function (toggle) {
                toggle.setAttribute('aria-pressed', dark ? 'true' : 'false');
                toggle.setAttribute('aria-label', label);
                toggle.setAttribute('title', label);
            });

            if (remember) {
                try {
                    localStorage.setItem('pg-tema', dark ? 'oscuro' : 'claro');
                } catch (e) {
                    /* Sin almacenamiento, la elección vale solo para esta página. */
                }
            }
        };

        var currentTheme = function () {
            return root.getAttribute('data-tema') === 'oscuro' ? 'oscuro' : 'claro';
        };

        themeToggles.forEach(function (toggle) {
            toggle.addEventListener('click', function () {
                applyTheme(currentTheme() === 'oscuro' ? 'claro' : 'oscuro', true);
            });
        });

        /* Deja el botón en consonancia con el tema que ya trae el documento */
        applyTheme(currentTheme(), false);

        /* Si el tema se cambia en otra pestaña, esta se pone al día igual */
        window.addEventListener('storage', function (event) {
            if (event.key === 'pg-tema') {
                applyTheme(event.newValue === 'oscuro' ? 'oscuro' : 'claro', false);
            }
        });
    }
})();
