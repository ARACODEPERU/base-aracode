/**
 * Aula Virtual · Inicial — interactividad del dashboard y de las vistas de
 * curso y juegos.
 *
 * Todo funciona sin Anime.js y sin síntesis de voz: las animaciones y la
 * lectura en voz alta son mejoras progresivas.
 */
(function () {
    'use strict';

    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var anime = window.anime;
    var canAnimate = !!anime && !reduceMotion;
    var canSpeak = 'speechSynthesis' in window && typeof window.SpeechSynthesisUtterance === 'function';
    var speakingButton = null;

    /* ------------------------------------------------------------------
       Voz: leer un texto y marcar el botón que la inició
       ------------------------------------------------------------------ */
    function stopSpeaking() {
        if (canSpeak) {
            window.speechSynthesis.cancel();
        }

        if (speakingButton) {
            speakingButton.classList.remove('is-speaking');
            speakingButton = null;
        }
    }

    function speak(text, button) {
        if (!canSpeak || !text) {
            return;
        }

        stopSpeaking();

        var utterance = new window.SpeechSynthesisUtterance(text);
        utterance.lang = 'es-PE';
        utterance.rate = 0.95;
        utterance.pitch = 1.1;

        utterance.onend = utterance.onerror = function () {
            if (speakingButton === button) {
                button.classList.remove('is-speaking');
                speakingButton = null;
            }
        };

        if (button) {
            button.classList.add('is-speaking');
            speakingButton = button;
        }

        window.speechSynthesis.speak(utterance);
    }

    if (!canSpeak) {
        document.querySelectorAll('#btnEscuchar, #btnMascotaPlay, #btnMascotaPause').forEach(function (button) {
            button.setAttribute('aria-disabled', 'true');
            button.title = 'Este navegador no puede leer en voz alta';
            button.classList.add('opacity-50');
        });
    }

    /* ------------------------------------------------------------------
       Botón de audio de la cabecera: lee el saludo de la pantalla
       ------------------------------------------------------------------ */
    var listenButton = document.getElementById('btnEscuchar');

    if (listenButton) {
        listenButton.addEventListener('click', function () {
            if (listenButton.classList.contains('is-speaking')) {
                stopSpeaking();
                return;
            }

            var target = document.querySelector(listenButton.dataset.listenTarget || '[data-listen]');
            var text = target ? target.innerText.replace(/\s+/g, ' ').trim() : document.title;

            speak(text, listenButton);
        });
    }

    /* ------------------------------------------------------------------
       Mascota: bocadillo con varias frases y sus controles
       ------------------------------------------------------------------ */
    var bubble = document.getElementById('mascotaBubble');
    var playButton = document.getElementById('btnMascotaPlay');
    var pauseButton = document.getElementById('btnMascotaPause');
    var nextButton = document.getElementById('btnMascotaNext');
    var messages = [];
    var messageIndex = 0;

    if (bubble) {
        try {
            messages = JSON.parse(bubble.dataset.messages || '[]');
        } catch (error) {
            messages = [];
        }

        if (!Array.isArray(messages) || messages.length === 0) {
            messages = [bubble.textContent.trim()];
        }
    }

    function showMessage(index) {
        if (!bubble || messages.length === 0) {
            return;
        }

        messageIndex = (index + messages.length) % messages.length;
        bubble.textContent = messages[messageIndex];

        if (canAnimate) {
            anime.animate(bubble, {
                opacity: [0.3, 1],
                translateY: [6, 0],
                duration: 320,
                ease: 'outQuad',
            });
        }
    }

    if (nextButton) {
        nextButton.addEventListener('click', function () {
            stopSpeaking();
            showMessage(messageIndex + 1);
        });
    }

    if (playButton) {
        playButton.addEventListener('click', function () {
            speak(messages[messageIndex] || '', playButton);
        });
    }

    if (pauseButton) {
        pauseButton.addEventListener('click', stopSpeaking);
    }

    /* ------------------------------------------------------------------
       Diálogo de ayuda
       ------------------------------------------------------------------ */
    var helpDialog = document.getElementById('ayudaDialog');

    if (helpDialog && typeof helpDialog.showModal === 'function') {
        document.querySelectorAll('[data-help-open]').forEach(function (button) {
            button.addEventListener('click', function () {
                helpDialog.showModal();
            });
        });

        document.querySelectorAll('[data-help-close]').forEach(function (button) {
            button.addEventListener('click', function () {
                helpDialog.close();
            });
        });

        // Clic fuera de la tarjeta: cerrar.
        helpDialog.addEventListener('click', function (event) {
            if (event.target === helpDialog) {
                helpDialog.close();
            }
        });
    }

    /* ------------------------------------------------------------------
       Mi perfil: datos del alumno y cambio de contraseña
       ------------------------------------------------------------------ */
    var profileDialog = document.getElementById('perfilDialog');

    if (profileDialog && typeof profileDialog.showModal === 'function') {
        document.querySelectorAll('[data-profile-open]').forEach(function (button) {
            button.addEventListener('click', function () {
                profileDialog.showModal();
            });
        });

        document.querySelectorAll('[data-profile-close]').forEach(function (button) {
            button.addEventListener('click', function () {
                profileDialog.close();
            });
        });

        profileDialog.addEventListener('click', function (event) {
            if (event.target === profileDialog) {
                profileDialog.close();
            }
        });

        // Si el servidor devolvió un error o el aviso de contraseña guardada,
        // el diálogo se abre solo para que el alumno vea el resultado.
        if (profileDialog.dataset.autoOpen === 'true') {
            profileDialog.showModal();
        }
    }

    /* ------------------------------------------------------------------
       Entrada suave de las tarjetas
       ------------------------------------------------------------------ */
    if (canAnimate) {
        anime.animate('.aula-hero', {
            opacity: [0, 1],
            translateY: [16, 0],
            duration: 520,
            ease: 'outCubic',
        });

        // Se pasa una lista real de elementos: con un selector combinado,
        // anime.js avisa cuando alguna de las clases no existe en la página.
        var cards = document.querySelectorAll('.course-card, .game-card, .unit-card');

        if (cards.length) {
            anime.animate(cards, {
                opacity: [0, 1],
                translateY: [18, 0],
                delay: anime.stagger(70, { start: 140 }),
                duration: 560,
                ease: 'outCubic',
            });
        }

        anime.animate('.aula-mascot', {
            opacity: [0, 1],
            scale: [0.97, 1],
            duration: 620,
            delay: 280,
            ease: 'outCubic',
        });
    }

    /* ------------------------------------------------------------------
       Parallax muy leve de las nubes (solo puntero fino)
       ------------------------------------------------------------------ */
    var clouds = document.querySelectorAll('.aula-cloud');

    if (canAnimate && clouds.length && window.matchMedia('(pointer: fine)').matches) {
        var frame = null;

        window.addEventListener('mousemove', function (event) {
            if (frame) {
                return;
            }

            frame = window.requestAnimationFrame(function () {
                frame = null;

                var nx = (event.clientX / window.innerWidth - 0.5) * 2;

                clouds.forEach(function (cloud, index) {
                    anime.animate(cloud, {
                        translateX: nx * (8 + index * 5),
                        duration: 900,
                        ease: 'outQuad',
                    });
                });
            });
        }, { passive: true });
    }
})();
