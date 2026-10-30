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
       Materiales de apoyo: modal con visor de imágenes y PDF
       ------------------------------------------------------------------ */
    var materialDialog = document.getElementById('materialDialog');

    if (materialDialog && typeof materialDialog.showModal === 'function') {
        var materialItems = Array.prototype.slice.call(
            materialDialog.querySelectorAll('[data-material-item]')
        );
        var materialImage = materialDialog.querySelector('[data-material-image]');
        var materialFrame = materialDialog.querySelector('[data-material-frame]');
        var materialFallback = materialDialog.querySelector('[data-material-fallback]');
        var materialFallbackTitle = materialDialog.querySelector('[data-material-fallback-title]');
        var materialTitle = materialDialog.querySelector('[data-material-title]');
        var materialOrigin = materialDialog.querySelector('[data-material-origin]');
        var materialDownload = materialDialog.querySelector('[data-material-download]');
        var materialExternal = materialDialog.querySelector('[data-material-external]');
        var materialPrev = materialDialog.querySelector('[data-material-prev]');
        var materialNext = materialDialog.querySelector('[data-material-next]');
        var materialPosition = materialDialog.querySelector('[data-material-position]');
        var materialCurrent = 0;

        var resetMaterialViewer = function () {
            materialImage.hidden = true;
            materialImage.removeAttribute('src');
            materialFrame.hidden = true;
            materialFrame.removeAttribute('src');
            materialFrame.removeAttribute('srcdoc');
            materialFallback.hidden = true;
        };

        var showMaterial = function (index) {
            if (!materialItems.length) {
                return;
            }

            materialCurrent = (index + materialItems.length) % materialItems.length;

            var item = materialItems[materialCurrent];
            var kind = item.dataset.kind;
            var preview = item.dataset.preview || '';
            var download = item.dataset.download || '';
            var external = item.dataset.external || '';
            var embedTemplate = materialDialog.querySelector(
                '[data-material-embed="' + item.dataset.materialId + '"]'
            );

            materialItems.forEach(function (other, position) {
                var active = position === materialCurrent;
                other.classList.toggle('is-active', active);
                other.setAttribute('aria-pressed', active ? 'true' : 'false');
            });

            materialTitle.textContent = item.dataset.title || 'Material';
            materialOrigin.textContent = item.dataset.origin || '';
            resetMaterialViewer();

            if (kind === 'image' && preview) {
                materialImage.src = preview;
                materialImage.alt = item.dataset.title || 'Material';
                materialImage.hidden = false;
            } else if (kind === 'pdf' && preview) {
                materialFrame.src = preview;
                materialFrame.title = 'Vista previa de ' + (item.dataset.title || 'el material');
                materialFrame.hidden = false;
            } else if (kind === 'embed' && embedTemplate) {
                materialFrame.srcdoc = embedTemplate.innerHTML;
                materialFrame.title = item.dataset.title || 'Contenido';
                materialFrame.hidden = false;
            } else {
                materialFallbackTitle.textContent = kind === 'link' && external
                    ? 'Este material es un enlace'
                    : 'Este material se descarga para abrirlo';
                materialFallback.hidden = false;
            }

            if (download) {
                materialDownload.href = download;
                materialDownload.hidden = false;
            } else {
                materialDownload.hidden = true;
                materialDownload.removeAttribute('href');
            }

            if (external) {
                materialExternal.href = external;
                materialExternal.hidden = false;
            } else {
                materialExternal.hidden = true;
                materialExternal.removeAttribute('href');
            }

            materialPosition.textContent = (materialCurrent + 1) + ' de ' + materialItems.length;
            materialPrev.disabled = materialItems.length < 2;
            materialNext.disabled = materialItems.length < 2;
        };

        var openMaterials = function (id) {
            var index = 0;

            if (id) {
                materialItems.forEach(function (item, position) {
                    if (String(item.dataset.materialId) === String(id)) {
                        index = position;
                    }
                });
            }

            showMaterial(index);
            materialDialog.showModal();
        };

        document.querySelectorAll('[data-material-open]').forEach(function (button) {
            button.addEventListener('click', function () {
                openMaterials(button.dataset.materialId || null);
            });
        });

        materialDialog.querySelectorAll('[data-material-close]').forEach(function (button) {
            button.addEventListener('click', function () {
                materialDialog.close();
            });
        });

        materialItems.forEach(function (item, position) {
            item.addEventListener('click', function () {
                showMaterial(position);
            });
        });

        materialPrev.addEventListener('click', function () {
            showMaterial(materialCurrent - 1);
        });

        materialNext.addEventListener('click', function () {
            showMaterial(materialCurrent + 1);
        });

        document.addEventListener('keydown', function (event) {
            if (!materialDialog.open || materialItems.length < 2) {
                return;
            }

            if (event.key === 'ArrowRight') {
                showMaterial(materialCurrent + 1);
            } else if (event.key === 'ArrowLeft') {
                showMaterial(materialCurrent - 1);
            }
        });

        // Cerrar al tocar fuera de la tarjeta y liberar el visor al salir.
        materialDialog.addEventListener('click', function (event) {
            if (event.target === materialDialog) {
                materialDialog.close();
            }
        });

        materialDialog.addEventListener('close', resetMaterialViewer);
    }

    /* ------------------------------------------------------------------
       Entrada suave de las tarjetas
       ------------------------------------------------------------------ */
    if (canAnimate) {
        // Las vistas de Secundaria reutilizan este archivo solo por el visor de
        // materiales, así que las animaciones del aula infantil son opcionales.
        var hero = document.querySelector('.aula-hero');

        if (hero) {
            anime.animate(hero, {
                opacity: [0, 1],
                translateY: [16, 0],
                duration: 520,
                ease: 'outCubic',
            });
        }

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

        var mascot = document.querySelector('.aula-mascot');

        if (mascot) {
            anime.animate(mascot, {
                opacity: [0, 1],
                scale: [0.97, 1],
                duration: 620,
                delay: 280,
                ease: 'outCubic',
            });
        }
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
