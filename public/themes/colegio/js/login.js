/**
 * Aula Virtual · Inicial — interactividad de la pantalla de bienvenida.
 *
 * Las animaciones usan Anime.js v4 (public/themes/colegio/js/vendor). Si la
 * librería no carga o el sistema pide menos movimiento, los paneles y el
 * formulario siguen funcionando: solo se omiten los efectos.
 */
(function () {
    'use strict';

    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var anime = window.anime;
    var canAnimate = !!anime && !reduceMotion;

    /* ------------------------------------------------------------------
       Anuncio dentro del formulario
       ------------------------------------------------------------------ */
    var noticeTimer = null;

    function showNotice(message) {
        var notice = document.getElementById('loginNotice');

        if (!notice) {
            return;
        }

        notice.textContent = message;
        notice.classList.remove('hidden');

        if (canAnimate) {
            anime.animate(notice, {
                opacity: [0, 1],
                translateY: [8, 0],
                duration: 380,
                ease: 'outCubic',
            });
        }

        window.clearTimeout(noticeTimer);
        noticeTimer = window.setTimeout(function () {
            notice.classList.add('hidden');
        }, 7000);
    }

    /* ------------------------------------------------------------------
       Paneles desplegables (Login / Comenzar)
       ------------------------------------------------------------------ */
    var btnLogin = document.getElementById('btnLogin');
    var btnStart = document.getElementById('btnStart');
    var panelLogin = document.getElementById('panelLogin');
    var panelStart = document.getElementById('panelStart');
    var codigoInput = document.getElementById('codigo');

    function closePanel(panel, button) {
        if (!panel || !button) {
            return;
        }

        panel.classList.remove('is-open');
        panel.setAttribute('aria-hidden', 'true');
        panel.inert = true;
        button.setAttribute('aria-expanded', 'false');
    }

    function openPanel(panel, button, otherPanel, otherButton, focusTarget) {
        if (!panel || !button) {
            return;
        }

        if (otherPanel && otherButton) {
            closePanel(otherPanel, otherButton);
        }

        panel.inert = false;
        panel.classList.add('is-open');
        panel.setAttribute('aria-hidden', 'false');
        button.setAttribute('aria-expanded', 'true');

        var fields = panel.querySelectorAll('.cole-input, button[type="submit"]');

        if (canAnimate && fields.length) {
            anime.animate(fields, {
                opacity: [0, 1],
                translateY: [10, 0],
                duration: 420,
                delay: anime.stagger(70, { start: 180 }),
                ease: 'outQuad',
            });
        }

        if (focusTarget) {
            window.setTimeout(function () {
                focusTarget.focus({ preventScroll: true });
            }, 400);
        }
    }

    // Mientras están cerrados, sus campos no se pueden enfocar con Tab.
    if (panelLogin) {
        panelLogin.inert = true;
    }
    if (panelStart) {
        panelStart.inert = true;
    }

    if (btnLogin && panelLogin) {
        btnLogin.addEventListener('click', function () {
            if (panelLogin.classList.contains('is-open')) {
                closePanel(panelLogin, btnLogin);
                return;
            }

            openPanel(panelLogin, btnLogin, panelStart, btnStart, codigoInput);
        });
    }

    if (btnStart && panelStart) {
        btnStart.addEventListener('click', function () {
            if (panelStart.classList.contains('is-open')) {
                closePanel(panelStart, btnStart);
                return;
            }

            openPanel(panelStart, btnStart, panelLogin, btnLogin, null);
        });
    }

    var startToLogin = document.getElementById('startToLogin');

    if (startToLogin) {
        startToLogin.addEventListener('click', function () {
            openPanel(panelLogin, btnLogin, panelStart, btnStart, codigoInput);
        });
    }

    /* ------------------------------------------------------------------
       Mostrar / ocultar contraseña
       ------------------------------------------------------------------ */
    var claveInput = document.getElementById('clave');
    var togglePassword = document.getElementById('togglePassword');

    if (claveInput && togglePassword) {
        togglePassword.addEventListener('click', function () {
            var visible = claveInput.type === 'text';

            claveInput.type = visible ? 'password' : 'text';
            togglePassword.setAttribute('aria-pressed', String(!visible));
            togglePassword.setAttribute('aria-label', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
            claveInput.focus({ preventScroll: true });
        });
    }

    /* ------------------------------------------------------------------
       Validación amigable y aviso de acceso
       ------------------------------------------------------------------ */
    function setFieldError(input, error, hasError) {
        if (!input || !error) {
            return;
        }

        error.classList.toggle('hidden', !hasError);
        input.setAttribute('aria-invalid', hasError ? 'true' : 'false');
    }

    function shakeCard() {
        if (!canAnimate) {
            return;
        }

        anime.animate('.cole-card', {
            translateX: [0, -7, 7, -5, 5, 0],
            duration: 460,
            ease: 'inOutQuad',
        });
    }

    var loginForm = document.getElementById('loginForm');
    var codigoError = document.getElementById('codigoError');
    var claveError = document.getElementById('claveError');

    if (loginForm && codigoInput && claveInput) {
        loginForm.addEventListener('submit', function (event) {
            var missingCode = codigoInput.value.trim() === '';
            var missingPassword = claveInput.value.trim() === '';

            setFieldError(codigoInput, codigoError, missingCode);
            setFieldError(claveInput, claveError, missingPassword);

            if (missingCode || missingPassword) {
                event.preventDefault();
                shakeCard();
                (missingCode ? codigoInput : claveInput).focus({ preventScroll: true });
                return;
            }

            // Datos completos: se envía de verdad y se bloquea el botón para
            // que nadie pulse dos veces mientras el servidor valida la sesión.
            var submitButton = document.getElementById('btnEntrar');

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.classList.add('opacity-80');
                submitButton.textContent = 'Entrando…';
            }
        });

        codigoInput.addEventListener('input', function () {
            setFieldError(codigoInput, codigoError, false);
        });

        claveInput.addEventListener('input', function () {
            setFieldError(claveInput, claveError, false);
        });
    }

    var forgotPassword = document.getElementById('forgotPassword');

    if (forgotPassword) {
        forgotPassword.addEventListener('click', function () {
            showNotice('Pídele a tu profesora que restablezca tu contraseña. Ella te entregará una nueva.');
        });
    }

    /* ------------------------------------------------------------------
       Si el servidor devolvió un error (o un aviso), el panel ya debe estar
       abierto y con el foco en el primer campo.
       ------------------------------------------------------------------ */
    var loginRoot = document.querySelector('[data-open-login="true"]');

    if (loginRoot && panelLogin && btnLogin) {
        openPanel(panelLogin, btnLogin, panelStart, btnStart, codigoInput);
    }

    /* ------------------------------------------------------------------
       Personajes: flotado continuo y parallax con el cursor
       ------------------------------------------------------------------ */
    if (canAnimate) {
        anime.animate('.cole-stage', {
            opacity: [0, 1],
            translateY: [22, 0],
            duration: 720,
            ease: 'outCubic',
        });

        document.querySelectorAll('[data-float]').forEach(function (element, index) {
            anime.animate(element, {
                translateY: [0, -10],
                rotate: index % 2 === 0 ? [-1.2, 1.2] : [1.2, -1.2],
                duration: 2200 + index * 160,
                delay: 260 + index * 140,
                ease: 'inOutSine',
                loop: true,
                alternate: true,
            });
        });
    }

    var loginScene = document.querySelector('.cole-login');
    var parallaxAnimals = document.querySelectorAll('[data-depth]');
    var parallaxButterflies = document.querySelectorAll('[data-butterfly]');
    var parallaxFrame = null;

    if (loginScene) {
        loginScene.addEventListener('mousemove', function (event) {
            if (!canAnimate || parallaxFrame) {
                return;
            }

            parallaxFrame = window.requestAnimationFrame(function () {
                parallaxFrame = null;

                var nx = (event.clientX / window.innerWidth - 0.5) * 2;
                var ny = (event.clientY / window.innerHeight - 0.5) * 2;

                parallaxAnimals.forEach(function (element) {
                    var depth = parseFloat(element.dataset.depth || '0.2');

                    anime.animate(element, {
                        translateX: nx * 20 * depth,
                        translateY: ny * 14 * depth,
                        duration: 760,
                        ease: 'outQuad',
                    });
                });

                parallaxButterflies.forEach(function (element, index) {
                    var svg = element.querySelector('svg');

                    if (!svg) {
                        return;
                    }

                    anime.animate(svg, {
                        translateX: nx * (18 + index * 6),
                        translateY: ny * (12 + index * 4),
                        duration: 900,
                        ease: 'outQuad',
                    });
                });
            });
        }, { passive: true });
    }

    /* ------------------------------------------------------------------
       Rebote elástico de los botones
       ------------------------------------------------------------------ */
    document.querySelectorAll('[data-bounce]').forEach(function (button) {
        button.addEventListener('mouseenter', function () {
            if (!canAnimate) {
                return;
            }

            anime.animate(button, {
                scale: 1.055,
                duration: 340,
                ease: 'outElastic(1, 0.55)',
            });
        });

        button.addEventListener('mouseleave', function () {
            if (!canAnimate) {
                return;
            }

            anime.animate(button, {
                scale: 1,
                duration: 260,
                ease: 'outQuad',
            });
        });

        button.addEventListener('pointerdown', function () {
            if (!canAnimate) {
                return;
            }

            anime.animate(button, { scale: 0.95, duration: 120, ease: 'outQuad' });
        });

        button.addEventListener('pointerup', function () {
            if (!canAnimate) {
                return;
            }

            anime.animate(button, { scale: 1.03, duration: 220, ease: 'outQuad' });
        });
    });
})();
