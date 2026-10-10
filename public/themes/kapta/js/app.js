/**
 * JS del sitio KAPTA LMS.
 * Vanilla, sin dependencias ni bundler: se sirve tal cual desde el tema.
 */
(function () {
    'use strict';

    /* ---------- 1) Cabecera: transparente arriba, blanca al desplazar ---------- */
    var nav = document.getElementById('kaNav');

    function updateNav() {
        if (!nav) {
            return;
        }
        nav.classList.toggle('is-scrolled', window.scrollY > 24);
    }

    window.addEventListener('scroll', updateNav, { passive: true });
    updateNav();

    /* ---------- 2) Menú móvil ---------- */
    var menu = document.getElementById('kaMobileMenu');
    var openBtn = document.getElementById('kaMenuBtn');
    var closeBtn = document.getElementById('kaMenuClose');

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
    var revealItems = document.querySelectorAll('.ka-reveal');

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

    /* ---------- 4) Conmutador mensual / anual de la página de planes ---------- */
    /* Solo cambia qué precio se muestra; no altera datos ni envía nada. */
    document.querySelectorAll('.ka-switch').forEach(function (group) {
        var buttons = Array.prototype.slice.call(group.querySelectorAll('button[data-periodo]'));

        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                var periodo = button.getAttribute('data-periodo');

                buttons.forEach(function (other) {
                    other.setAttribute('aria-pressed', other === button ? 'true' : 'false');
                });

                document.querySelectorAll('[data-precio]').forEach(function (precio) {
                    precio.classList.toggle('is-active', precio.getAttribute('data-precio') === periodo);
                });
            });
        });
    });

    /* ---------- 5) Año actual donde se pida ---------- */
    document.querySelectorAll('[data-year]').forEach(function (element) {
        element.textContent = String(new Date().getFullYear());
    });
})();
