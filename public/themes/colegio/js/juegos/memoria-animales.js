/**
 * Aula Virtual · Zona de juegos — Memoria de animales.
 *
 * La partida vive en el navegador: no se envía nada al servidor y el avance se
 * recuerda solo en este dispositivo. Dos mecánicas se combinan en diez niveles:
 *
 *   - sound:  escuchar un sonido real y elegir la fotografía del animal.
 *   - memory: voltear tarjetas y encontrar las parejas de fotografías.
 *
 * La configuración de los animales (fotos, audios y créditos) llega desde
 * config/colegio_animales.php. Aquí solo viven los niveles y la generación de
 * actividades, siempre con una respuesta correcta verificable.
 */
(function () {
    'use strict';

    var root = document.querySelector('[data-juego="memoria-animales"]');

    if (!root) {
        return;
    }

    var configNode = document.getElementById('animalesConfig');
    var config = {};

    try {
        config = JSON.parse(configNode.textContent || '{}');
    } catch (error) {
        config = {};
    }

    var ANIMALS = {};
    var ANIMAL_IDS = [];

    var configAnimals = Array.isArray(config) ? config : (config.animals || []);

    configAnimals.forEach(function (animal) {
        if (!animal || !animal.id) {
            return;
        }

        ANIMALS[animal.id] = animal;
        ANIMAL_IDS.push(animal.id);
    });

    var ALL = ANIMAL_IDS.slice();

    /* ------------------------------------------------------------------
       Niveles
       ------------------------------------------------------------------ */

    var LEVELS = [
        {
            id: 1,
            name: '¿Quién hace ese sonido?',
            focus: 'Perro y gato',
            hint: 'Escucha el sonido y toca la fotografía del animal que lo hace.',
            meta: '4 sonidos · 2 opciones',
            sound: { pool: ['perro', 'gato'], targets: ['perro', 'gato', 'perro', 'gato'], options: 2 },
        },
        {
            id: 2,
            name: 'Los animales de casa',
            focus: 'Perro, gato y gallina',
            hint: 'Tres animales conocidos. Escucha con atención y busca su fotografía.',
            meta: '4 sonidos · 3 animales',
            sound: { pool: ['perro', 'gato', 'gallina'], targets: ['perro', 'gato', 'gallina', 'perro'], options: 3 },
        },
        {
            id: 3,
            name: 'Sonidos de la granja',
            focus: 'Vaca, cerdo y oveja',
            hint: 'Llegan los animales de la granja. Puedes repetir cada sonido.',
            meta: '4 sonidos · granja',
            sound: {
                pool: ['perro', 'gato', 'gallina', 'vaca', 'cerdo', 'oveja'],
                targets: ['vaca', 'cerdo', 'oveja', 'gallina'],
                options: 3,
            },
        },
        {
            id: 4,
            name: 'Más amigos de la granja',
            focus: 'Pato, caballo y gallo',
            hint: 'Escucha al pato, al caballo y al gallo. Después busca su foto.',
            meta: '4 sonidos · 4 opciones',
            sound: {
                pool: ['perro', 'gato', 'gallina', 'vaca', 'cerdo', 'oveja', 'pato', 'caballo', 'gallo'],
                targets: ['pato', 'caballo', 'gallo', 'vaca'],
                options: 4,
            },
        },
        {
            id: 5,
            name: 'Aventuras en la selva',
            focus: 'León, elefante y mono',
            hint: 'Estos animales viven lejos. Escucha su sonido y encuéntralos.',
            meta: '4 sonidos · selva',
            sound: { pool: ['leon', 'elefante', 'mono'], targets: ['leon', 'elefante', 'mono', 'leon'], options: 3 },
        },
        {
            id: 6,
            name: 'Amigos del bosque',
            focus: 'Rana, pájaro y búho',
            hint: 'Sonidos del bosque: croar, cantar y ulular. ¿Quién será?',
            meta: '4 sonidos · bosque',
            sound: { pool: ['rana', 'pajaro', 'buho'], targets: ['rana', 'pajaro', 'buho', 'pajaro'], options: 3 },
        },
        {
            id: 7,
            name: 'Recordamos los animales',
            focus: 'Sonidos de todos los grupos',
            hint: 'Ahora se mezclan los animales que ya conoces. Escucha y elige.',
            meta: '5 sonidos · todos',
            sound: { pool: ALL, rounds: 5, options: 4 },
        },
        {
            id: 8,
            name: 'Encuentra las parejas',
            focus: 'Memoria visual',
            hint: 'Toca dos tarjetas para darles la vuelta. Si son el mismo animal, se quedan descubiertas.',
            meta: 'Parejas · memoria',
            memory: {
                pool: ['perro', 'gato', 'gallina', 'vaca', 'cerdo', 'oveja', 'pato', 'caballo'],
                pairs: 4,
            },
        },
        {
            id: 9,
            name: '¿Recuerdas quién sonó?',
            focus: 'Sonidos y memoria',
            hint: 'Primero reconoce tres sonidos y después encuentra una pareja de cada animal.',
            meta: 'Sonidos + memoria',
            sound: { pool: ALL, rounds: 3, options: 4 },
            memory: {
                pool: ['perro', 'gato', 'leon', 'elefante', 'mono', 'rana', 'pajaro', 'buho', 'pato', 'caballo'],
                pairs: 3,
            },
        },
        {
            id: 10,
            name: '¡Maestro de los animales!',
            focus: 'El desafío final',
            hint: 'Un poco de todo: sonidos de todos los grupos y un reto de memoria. Sin prisa.',
            meta: 'Reto final · 10 niveles',
            sound: { pool: ALL, rounds: 3, options: 4 },
            memory: { pool: ALL, pairs: 4 },
        },
    ];

    var STORAGE_LEVELS = 'colegio.animales.niveles';
    var ADVANCE_DELAY = 1400;
    var MEMORY_HIDE_DELAY = 950;
    var MEMORY_WIN_DELAY = 1200;

    /* ------------------------------------------------------------------
       Referencias del DOM
       ------------------------------------------------------------------ */

    var stage = root.querySelector('[data-stage]');
    var intro = root.querySelector('[data-intro]');
    var victory = root.querySelector('[data-victory]');
    var levelsBox = root.querySelector('[data-levels]');
    var progressBar = root.querySelector('[data-progress-bar]');
    var progressFill = root.querySelector('[data-progress-fill]');
    var messageBox = root.querySelector('[data-message]');
    var victoryTitle = root.querySelector('[data-victory-title]');
    var victoryText = root.querySelector('[data-victory-text]');
    var startButton = root.querySelector('[data-start]');
    var restartButton = root.querySelector('[data-restart]');
    var replayButton = root.querySelector('[data-replay]');
    var nextButton = root.querySelector('[data-next]');
    var stopAudioButton = root.querySelector('[data-stop-audio]');
    var levelNames = root.querySelectorAll('[data-level-name]');
    var levelFocus = root.querySelector('[data-level-focus]');
    var levelHint = root.querySelector('[data-level-hint]');
    var introTitle = root.querySelector('[data-intro-title]');
    var introSteps = root.querySelectorAll('[data-intro-step]');
    var levelNumbers = root.querySelectorAll('[data-level-number]');
    var activityLabel = root.querySelector('[data-activity-label]');
    var activityCurrent = root.querySelector('[data-activity-current]');
    var activityTotal = root.querySelector('[data-activity-total]');
    var mechanicLabel = root.querySelector('[data-mechanic]');
    var creditsDialog = document.getElementById('creditosAnimales');

    /* ------------------------------------------------------------------
       Estado
       ------------------------------------------------------------------ */

    var state = {
        levelIndex: 0,
        activities: [],
        index: 0,
        completed: 0,
        started: false,
        solved: false,
        locked: false,
        hasPlayed: false,
    };

    var advanceTimer = 0;
    var memoryTimer = 0;
    var audio = new Audio();

    audio.preload = 'metadata';

    /* ------------------------------------------------------------------
       Utilidades
       ------------------------------------------------------------------ */

    function pick(list) {
        return list[Math.floor(Math.random() * list.length)];
    }

    function shuffle(list) {
        var copy = list.slice();

        for (var i = copy.length - 1; i > 0; i -= 1) {
            var j = Math.floor(Math.random() * (i + 1));
            var temp = copy[i];
            copy[i] = copy[j];
            copy[j] = temp;
        }

        return copy;
    }

    function esc(text) {
        return String(text).replace(/[&<>"']/g, function (character) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
            }[character];
        });
    }

    function cap(text) {
        return text.charAt(0).toUpperCase() + text.slice(1);
    }

    function current() {
        return state.activities[state.index] || null;
    }

    function setMessage(text) {
        messageBox.textContent = text;
    }

    function clearTimer(timer) {
        if (timer) {
            window.clearTimeout(timer);
        }

        return 0;
    }

    function photosOfActivity(activity) {
        if (!activity) {
            return [];
        }

        if (activity.type === 'sound') {
            return activity.options;
        }

        return activity.cards.map(function (card) {
            return card.id;
        });
    }

    function preload(ids) {
        ids.forEach(function (id) {
            var animal = ANIMALS[id];
            if (!animal || !animal.photo_url) {
                return;
            }

            var image = new Image();
            image.src = animal.photo_url;
        });
    }

    function completedLevels() {
        try {
            var raw = window.localStorage.getItem(STORAGE_LEVELS);
            var list = raw ? JSON.parse(raw) : [];
            return Array.isArray(list) ? list : [];
        } catch (error) {
            return [];
        }
    }

    function saveCompletedLevel(id) {
        try {
            var list = completedLevels();

            if (list.indexOf(id) === -1) {
                list.push(id);
                window.localStorage.setItem(STORAGE_LEVELS, JSON.stringify(list));
            }
        } catch (error) {
            // Sin almacenamiento el avance vale solo para esta visita.
        }
    }

    /* ------------------------------------------------------------------
       Generación de actividades
       ------------------------------------------------------------------ */

    function soundPool(level) {
        return (level.sound.pool || []).filter(function (id) {
            return ANIMALS[id] && ANIMALS[id].audio_url;
        });
    }

    function memoryPool(level) {
        return (level.memory.pool || []).filter(function (id) {
            return ANIMALS[id] && ANIMALS[id].photo_url;
        });
    }

    function desiredPairs(level) {
        var base = level.memory.pairs || 4;
        var small = window.matchMedia && window.matchMedia('(max-width: 480px)').matches;

        return Math.max(2, Math.min(base, small ? 3 : base));
    }

    function buildSoundActivities(level) {
        var pool = soundPool(level);
        var settings = level.sound;
        var targets;
        var optionCount;

        if (!pool.length) {
            return [];
        }

        if (settings.targets && settings.targets.length) {
            targets = settings.targets.filter(function (id) {
                return pool.indexOf(id) !== -1;
            });
        } else {
            var rounds = Math.min(settings.rounds || 4, pool.length);
            targets = shuffle(pool).slice(0, rounds);
        }

        optionCount = Math.min(settings.options || 3, pool.length);

        return targets.map(function (target) {
            var distractors = shuffle(pool.filter(function (id) {
                return id !== target;
            })).slice(0, optionCount - 1);

            return {
                type: 'sound',
                target: target,
                options: shuffle([target].concat(distractors)),
                attempts: 0,
            };
        });
    }

    function buildMemoryActivity(level) {
        var pool = memoryPool(level);
        var count = Math.min(desiredPairs(level), pool.length);
        var chosen = shuffle(pool).slice(0, count);
        var deck = [];

        if (count < 2) {
            return null;
        }

        chosen.forEach(function (id, index) {
            deck.push({ key: 'par-' + index + '-a', id: id });
            deck.push({ key: 'par-' + index + '-b', id: id });
        });

        return {
            type: 'memory',
            pairs: count,
            cards: shuffle(deck),
            open: [],
            found: [],
            foundCount: 0,
            locked: false,
        };
    }

    function buildLevel(index) {
        var level = LEVELS[index];
        var activities = [];

        state.levelIndex = index;
        state.activities = [];
        state.index = 0;
        state.completed = 0;
        state.started = false;
        state.solved = false;
        state.locked = false;

        if (level.sound) {
            activities = activities.concat(buildSoundActivities(level));
        }

        if (level.memory) {
            var memory = buildMemoryActivity(level);

            if (memory) {
                activities.push(memory);
            }
        }

        state.activities = activities;
        preload(activities.reduce(function (ids, activity) {
            return ids.concat(photosOfActivity(activity));
        }, []));
    }

    /* ------------------------------------------------------------------
       Audio real de los animales
       ------------------------------------------------------------------ */

    function animalOf(id) {
        return ANIMALS[id] || null;
    }

    function playCurrent() {
        var activity = current();

        if (!activity || activity.type !== 'sound' || state.solved) {
            return;
        }

        var animal = animalOf(activity.target);

        if (!animal || !animal.audio_url) {
            showAudioAlert();
            return;
        }

        stopAudio();
        state.hasPlayed = true;
        audio.src = animal.audio_url;
        audio.currentTime = 0;

        var alert = stage.querySelector('[data-audio-alert]');

        if (alert) {
            alert.hidden = true;
        }

        try {
            var play = audio.play();

            if (play && typeof play.catch === 'function') {
                play.catch(function (error) {
                    if (error && error.name === 'NotAllowedError') {
                        setMessage('Toca el botón «Escuchar sonido» para oír al animal.');
                        return;
                    }

                    showAudioAlert();
                });
            }
        } catch (error) {
            showAudioAlert();
        }
    }

    function stopAudio() {
        try {
            audio.pause();
            audio.currentTime = 0;
        } catch (error) {
            // Un audio que aún no cargó no necesita detenerse.
        }

        var button = stage.querySelector('[data-play]');

        if (button) {
            button.classList.remove('is-playing');
        }

        var label = stage.querySelector('[data-play-label]');

        if (label) {
            label.textContent = state.hasPlayed ? 'Escuchar otra vez' : 'Escuchar sonido';
        }

        if (button) {
            button.setAttribute('aria-label', 'Escuchar el sonido del animal');
        }

        if (stopAudioButton) {
            stopAudioButton.hidden = true;
        }
    }

    function showAudioAlert() {
        var alert = stage.querySelector('[data-audio-alert]');

        if (alert) {
            alert.hidden = false;
        }

        var button = stage.querySelector('[data-play]');

        if (button) {
            button.classList.remove('is-playing');
        }

        setMessage('No pudimos reproducir el sonido. Puedes intentarlo otra vez o continuar con una pista.');
    }

    function audioStarted() {
        var button = stage.querySelector('[data-play]');

        var alert = stage.querySelector('[data-audio-alert]');

        if (alert) {
            alert.hidden = true;
        }


        if (button) {
            button.classList.add('is-playing');
            button.setAttribute('aria-label', 'Sonando. Puedes tocar para repetir.');
        }

        var label = stage.querySelector('[data-play-label]');

        if (label) {
            label.textContent = 'Sonando...';
        }

        if (stopAudioButton) {
            stopAudioButton.hidden = false;
        }

        setMessage('Escucha con atención. Puedes repetirlo las veces que quieras.');
    }

    function audioEnded() {
        var button = stage.querySelector('[data-play]');

        if (button) {
            button.classList.remove('is-playing');
        }

        var label = stage.querySelector('[data-play-label]');

        if (label) {
            label.textContent = 'Escuchar otra vez';
        }

        if (stopAudioButton) {
            stopAudioButton.hidden = true;
        }

        if (!state.solved) {
            setMessage('Toca la fotografía del animal que hace ese sonido.');
        }
    }

    audio.addEventListener('playing', audioStarted);
    audio.addEventListener('ended', audioEnded);
    audio.addEventListener('error', showAudioAlert);

    /* ------------------------------------------------------------------
       Tarjetas visuales
       ------------------------------------------------------------------ */

    function soundOption(animal) {
        return '<button type="button" class="animales-opcion" data-animal="' + esc(animal.id) + '" ' +
                'aria-label="' + esc(cap(animal.name)) + '">' +
            '<span class="animales-opcion__foto">' +
                '<img src="' + esc(animal.photo_url) + '" alt="" loading="eager" decoding="async">' +
            '</span>' +
            '<span class="animales-opcion__nombre">' + esc(cap(animal.name)) + '</span>' +
            '<span class="animales-opcion__marca" aria-hidden="true">' +
                '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" focusable="false">' +
                    '<path stroke-linecap="round" stroke-linejoin="round" d="m5 12.5 4.5 4.5L19 7"/>' +
                '</svg>' +
            '</span>' +
        '</button>';
    }

    function soundMarkup(activity) {
        var options = activity.options.map(function (id) {
            return animalOf(id) ? soundOption(animalOf(id)) : '';
        }).join('');

        return '<div class="animales-sonido">' +
            '<p class="animales-consigna">Escucha el sonido y toca la fotografía del animal que lo hace.</p>' +

            '<button type="button" class="animales-play" data-play aria-label="Escuchar el sonido del animal">' +
                '<span class="animales-play__icono" aria-hidden="true">' +
                    '<svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="2.4" focusable="false">' +
                        '<path stroke-linecap="round" stroke-linejoin="round" d="M14 8 8.5 13H4v6h4.5L14 24z"/>' +
                        '<path stroke-linecap="round" d="M19 12.5a5 5 0 0 1 0 7M22.5 9a10 10 0 0 1 0 14"/>' +
                    '</svg>' +
                '</span>' +
                '<span class="animales-play__texto" data-play-label>Escuchar sonido</span>' +
                '<span class="animales-play__ondas" aria-hidden="true"><i></i><i></i><i></i><i></i></span>' +
            '</button>' +

            '<div class="animales-audio-alert" data-audio-alert hidden role="alert">' +
                '<span>El sonido no se pudo reproducir.</span>' +
                '<button type="button" class="animales-audio-alert__btn" data-audio-hint>Continuar con una pista</button>' +
            '</div>' +

            '<div class="animales-opciones animales-opciones--' + activity.options.length + '" data-options>' +
                options +
            '</div>' +
        '</div>';
    }

    function memoryCard(card, index) {
        var animal = animalOf(card.id);

        if (!animal) {
            return '';
        }

        return '<button type="button" class="animales-carta" data-card="' + index + '" ' +
                'aria-label="Tarjeta boca abajo. Toca para voltear." aria-pressed="false">' +
            '<span class="animales-carta__inner">' +
                '<span class="animales-carta__reverso" aria-hidden="true">' +
                    '<svg viewBox="0 0 48 48" fill="none" focusable="false">' +
                        '<rect x="3" y="3" width="42" height="42" rx="12" fill="#EAF4EE"/>' +
                        '<circle cx="16" cy="16" r="5" fill="#86DC72" opacity=".8"/>' +
                        '<circle cx="32" cy="32" r="5" fill="#F7C45C" opacity=".9"/>' +
                        '<path d="M32 13 37 18 32 23 27 18z" fill="#6CC4F5" opacity=".8"/>' +
                        '<path d="M16 25 21 30 16 35 11 30z" fill="#F49AC1" opacity=".8"/>' +
                    '</svg>' +
                '</span>' +
                '<span class="animales-carta__frente" aria-hidden="true">' +
                    '<img src="' + esc(animal.photo_url) + '" alt="" loading="eager" decoding="async">' +
                '</span>' +
            '</span>' +
            '<span class="animales-carta__check" aria-hidden="true">' +
                '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" focusable="false">' +
                    '<path stroke-linecap="round" stroke-linejoin="round" d="m5 12.5 4.5 4.5L19 7"/>' +
                '</svg>' +
            '</span>' +
        '</button>';
    }

    function memoryMarkup(activity) {
        var cards = activity.cards.map(function (card, index) {
            return memoryCard(card, index);
        }).join('');

        var columns = activity.cards.length <= 6 ? 3 : 4;

        return '<div class="animales-memoria-wrap">' +
            '<div class="animales-memoria-head">' +
                '<p class="animales-consigna">Toca dos tarjetas y busca dos fotos del mismo animal.</p>' +
                '<span class="animales-pares" data-pairs>Parejas: 0 de ' + activity.pairs + '</span>' +
            '</div>' +
            '<div class="animales-memoria" data-memory style="--animales-cols:' + columns + '">' + cards + '</div>' +
        '</div>';
    }

    /* ------------------------------------------------------------------
       Progreso y cabecera
       ------------------------------------------------------------------ */

    function memoryFraction() {
        var activity = current();

        if (!activity || activity.type !== 'memory' || state.solved) {
            return 0;
        }

        return activity.pairs ? activity.foundCount / activity.pairs : 0;
    }

    function updateProgress() {
        var total = state.activities.length || 1;
        var percent = Math.round(((state.completed + memoryFraction()) / total) * 100);

        progressFill.style.width = percent + '%';
        progressBar.setAttribute('aria-valuenow', String(percent));
    }

    function updatePairs() {
        var activity = current();
        var chips = root.querySelectorAll('[data-pairs]');

        Array.prototype.forEach.call(chips, function (chip) {
            if (!activity || activity.type !== 'memory') {
                chip.hidden = true;
                return;
            }

            chip.hidden = false;
            chip.textContent = 'Parejas: ' + activity.foundCount + ' de ' + activity.pairs;
        });
    }

    function updateChrome() {
        var level = LEVELS[state.levelIndex];
        var total = state.activities.length || 1;

        Array.prototype.forEach.call(levelNumbers, function (element) {
            element.textContent = String(level.id);
        });

        Array.prototype.forEach.call(levelNames, function (element) {
            element.textContent = level.name;
        });

        levelFocus.textContent = level.focus;

        if (levelHint) {
            levelHint.textContent = level.hint;
        }

        updateIntroCopy(level);
        activityLabel.textContent = 'Actividad ' + Math.min(state.index + 1, total) + ' de ' + total;
        activityCurrent.textContent = String(Math.min(state.index + 1, total));
        activityTotal.textContent = String(total);
        mechanicLabel.textContent = level.sound && level.memory ? 'Sonidos + memoria' : (level.memory ? 'Memoria' : 'Sonidos');

        updateProgress();
        updatePairs();
        renderLevels();
    }

    function updateIntroCopy(level) {
        var mode = level.sound && level.memory ? 'mixed' : (level.memory ? 'memory' : 'sound');
        var titles = {
            sound: '¡Vamos a escuchar a los animales!',
            memory: '¡Vamos a encontrar parejas!',
            mixed: '¡Vamos a escuchar y recordar!',
        };
        var steps = {
            sound: [
                'Pulsa el botón grande para escuchar.',
                'Busca la fotografía del animal.',
                'Si algo no sale, lo intentas otra vez.',
            ],
            memory: [
                'Toca dos tarjetas y míralas bien.',
                'Si son el mismo animal, se quedan descubiertas.',
                'Si no, se ocultan y lo intentas otra vez.',
            ],
            mixed: [
                'Primero reconoce los sonidos.',
                'Después, encuentra las parejas.',
                'Puedes repetir el sonido cuando quieras.',
            ],
        };

        if (introTitle) {
            introTitle.textContent = titles[mode];
        }

        Array.prototype.forEach.call(introSteps, function (step) {
            var index = Number(step.getAttribute('data-intro-step')) - 1;
            step.textContent = steps[mode][index] || '';
        });
    }

    function renderLevels() {
        var done = completedLevels();

        levelsBox.innerHTML = LEVELS.map(function (level, index) {
            var isActive = index === state.levelIndex;
            var isComplete = done.indexOf(level.id) !== -1;

            return '<button type="button" class="juego-nivel' +
                    (isActive ? ' is-active' : '') + (isComplete ? ' is-complete' : '') + '" ' +
                    'data-level="' + index + '" aria-pressed="' + (isActive ? 'true' : 'false') + '">' +
                '<span class="juego-nivel__num">' + level.id + '</span>' +
                '<span class="juego-nivel__info">' +
                    '<span class="juego-nivel__name">' + esc(level.name) + '</span>' +
                    '<span class="juego-nivel__meta">' + esc(level.meta) + (isComplete ? ' · completado' : '') + '</span>' +
                '</span>' +
            '</button>';
        }).join('');
    }

    /* ------------------------------------------------------------------
       Partida
       ------------------------------------------------------------------ */

    function showIntro() {
        stopAudio();
        clearTimer(advanceTimer);
        clearTimer(memoryTimer);
        advanceTimer = 0;
        memoryTimer = 0;

        state.started = false;
        state.solved = false;
        state.locked = false;

        victory.hidden = true;
        victory.classList.remove('is-visible');
        intro.hidden = false;
        stage.innerHTML = '';
        stage.inert = true;

        progressFill.style.width = '0%';
        progressBar.setAttribute('aria-valuenow', '0');
        activityLabel.textContent = 'Actividad 1 de ' + (state.activities.length || 1);
        activityCurrent.textContent = '1';
        activityTotal.textContent = String(state.activities.length || 1);

        updatePairs();
        setMessage('Pulsa «¡Vamos a jugar!» para empezar.');

        Array.prototype.forEach.call(root.querySelectorAll('[data-pairs]'), function (chip) {
            chip.hidden = true;
        });
    }

    function startLevel() {
        clearTimer(advanceTimer);
        advanceTimer = 0;

        if (!state.activities.length) {
            setMessage('Este nivel todavía no tiene actividades disponibles.');
            return;
        }

        intro.hidden = true;
        victory.hidden = true;
        victory.classList.remove('is-visible');
        state.started = true;
        state.index = 0;
        state.completed = 0;
        state.solved = false;
        state.locked = false;

        renderActivity();
        scrollPanelIntoView();
    }

    function renderActivity() {
        var activity = current();

        if (!activity) {
            finishLevel();
            return;
        }

        state.solved = false;
        state.locked = false;
        state.hasPlayed = false;

        if (activity.type === 'memory') {
            activity.open = [];
            activity.found = [];
            activity.foundCount = 0;
            activity.locked = false;
            stage.innerHTML = memoryMarkup(activity);
            setMessage('Encuentra las parejas. Puedes voltear dos tarjetas por turno.');
        } else {
            stage.innerHTML = soundMarkup(activity);
            setMessage('Toca el botón para escuchar al animal.');
        }

        stage.inert = false;
        updateChrome();

        var focusTarget = stage.querySelector('[data-play]') ||
            stage.querySelector('[data-card]') ||
            stage.querySelector('[data-animal]');

        if (focusTarget) {
            try {
                focusTarget.focus({ preventScroll: true });
            } catch (error) {
                focusTarget.focus();
            }
        }

        if (activity.type === 'sound') {
            // El primer sonido se reproduce dentro del clic de inicio; en las
            // rondas siguientes el navegador ya tiene permiso de reproducción.
            playCurrent();
        }
    }

    function nextActivity() {
        clearTimer(advanceTimer);
        advanceTimer = 0;

        if (state.index + 1 >= state.activities.length) {
            finishLevel();
            return;
        }

        state.index += 1;
        state.solved = false;
        state.locked = false;
        renderActivity();
    }

    function finishLevel() {
        var level = LEVELS[state.levelIndex];
        var last = state.levelIndex === LEVELS.length - 1;

        stopAudio();
        clearTimer(advanceTimer);
        advanceTimer = 0;

        state.started = false;
        stage.inert = true;

        saveCompletedLevel(level.id);
        renderLevels();

        victoryTitle.textContent = last
            ? '¡Excelente! ¡Reconoces los sonidos de muchos animales!'
            : '¡Nivel completado!';

        victoryText.textContent = last
            ? 'Completaste los diez niveles. ¡Ya eres un maestro de los animales!'
            : (state.activities.length === 1
                ? 'Completaste la actividad del nivel ' + level.id + '.'
                : 'Completaste las ' + state.activities.length + ' actividades del nivel ' + level.id + '.');

        nextButton.hidden = last;
        victory.hidden = false;
        victory.classList.add('is-visible');
        progressFill.style.width = '100%';
        progressBar.setAttribute('aria-valuenow', '100');
        setMessage(last ? '¡Lo lograste! Terminaste todos los niveles.' : '¡Muy bien! Ya puedes pasar al siguiente nivel.');

        var focusTarget = last ? replayButton : nextButton;

        if (focusTarget) {
            try {
                focusTarget.focus({ preventScroll: true });
            } catch (error) {
                focusTarget.focus();
            }
        }
    }

    function scrollPanelIntoView() {
        var panel = root.querySelector('.juego-panel');

        if (!panel) {
            return;
        }

        try {
            panel.scrollIntoView({ block: 'start' });
        } catch (error) {
            panel.scrollIntoView();
        }
    }

    function chooseLevel(index) {
        if (index < 0 || index >= LEVELS.length) {
            return;
        }

        stopAudio();
        buildLevel(index);
        updateChrome();
        showIntro();
        scrollPanelIntoView();
    }

    function retryLevel() {
        stopAudio();
        buildLevel(state.levelIndex);
        updateChrome();
        intro.hidden = true;
        startLevel();
    }

    /* ------------------------------------------------------------------
       Interacciones
       ------------------------------------------------------------------ */

    function markWrong(button) {
        button.classList.add('is-wrong');

        window.setTimeout(function () {
            if (button) {
                button.classList.remove('is-wrong');
            }
        }, 550);
    }

    function showHint() {
        var activity = current();

        if (!activity || activity.type !== 'sound') {
            return;
        }

        var animal = animalOf(activity.target);

        if (!animal) {
            return;
        }

        setMessage('Pista: es ' + animal.article + ' ' + animal.name + '. Toca su fotografía.');
    }

    function answerSound(button) {
        var activity = current();

        if (!activity || activity.type !== 'sound' || state.solved || state.locked) {
            return;
        }

        var id = button.getAttribute('data-animal');
        var animal = animalOf(id);

        if (!animal) {
            return;
        }

        if (id === activity.target) {
            state.solved = true;
            state.locked = true;
            button.classList.add('is-correct');

            Array.prototype.forEach.call(stage.querySelectorAll('[data-animal]'), function (option) {
                option.disabled = true;
            });

            stopAudio();
            state.completed += 1;
            updateProgress();
            updatePairs();

            setMessage('¡Muy bien! Ese es ' + animal.article + ' ' + animal.name + '.');

            advanceTimer = window.setTimeout(function () {
                advanceTimer = 0;
                nextActivity();
            }, ADVANCE_DELAY);

            return;
        }

        markWrong(button);
        activity.attempts += 1;

        if (activity.attempts >= 2) {
            showHint();
        } else {
            setMessage('¡Casi! Vuelve a escuchar y prueba otra vez.');
        }
    }

    function memoryOpen(card, index) {
        var activity = current();

        if (!activity || activity.type !== 'memory' || activity.locked || state.solved) {
            return;
        }

        if (card.classList.contains('is-matched') || card.classList.contains('is-open')) {
            return;
        }

        card.classList.add('is-open');
        card.setAttribute('aria-pressed', 'true');
        card.setAttribute('aria-label', 'Foto de ' + cap(animalOf(activity.cards[index].id).name));
        activity.open.push(index);

        if (activity.open.length < 2) {
            setMessage('Muy bien. Ahora elige otra tarjeta.');
            return;
        }

        var firstIndex = activity.open[0];
        var secondIndex = activity.open[1];
        var first = activity.cards[firstIndex];
        var second = activity.cards[secondIndex];
        var firstButton = stage.querySelector('[data-card="' + firstIndex + '"]');
        var secondButton = stage.querySelector('[data-card="' + secondIndex + '"]');

        if (first.id === second.id) {
            firstButton.classList.add('is-matched');
            secondButton.classList.add('is-matched');
            firstButton.setAttribute('aria-label', 'Pareja encontrada: ' + cap(animalOf(first.id).name));
            secondButton.setAttribute('aria-label', 'Pareja encontrada: ' + cap(animalOf(first.id).name));
            firstButton.disabled = true;
            secondButton.disabled = true;

            activity.found.push(first.id);
            activity.foundCount += 1;
            activity.open = [];

            updatePairs();
            updateProgress();
            setMessage('¡Encontraste la pareja ' + (animalOf(first.id).article === 'el' ? 'del ' : 'de la ') + animalOf(first.id).name + '!');

            if (activity.foundCount === activity.pairs) {
                state.solved = true;
                state.completed += 1;
                updateProgress();
                updatePairs();
                setMessage('¡Excelente memoria! Encontraste todas las parejas.');
                memoryTimer = window.setTimeout(function () {
                    memoryTimer = 0;
                    nextActivity();
                }, MEMORY_WIN_DELAY);
            }

            return;
        }

        activity.locked = true;
        firstButton.classList.add('is-wrong');
        secondButton.classList.add('is-wrong');
        setMessage('Esas dos fotos no son iguales. ¡Inténtalo otra vez!');

        memoryTimer = window.setTimeout(function () {
            memoryTimer = 0;
            firstButton.classList.remove('is-open', 'is-wrong');
            secondButton.classList.remove('is-open', 'is-wrong');
            firstButton.setAttribute('aria-pressed', 'false');
            secondButton.setAttribute('aria-pressed', 'false');
            firstButton.setAttribute('aria-label', 'Tarjeta boca abajo. Toca para voltear.');
            secondButton.setAttribute('aria-label', 'Tarjeta boca abajo. Toca para voltear.');
            activity.open = [];
            activity.locked = false;
        }, MEMORY_HIDE_DELAY);
    }

    function openCredits() {
        if (!creditsDialog) {
            return;
        }

        if (typeof creditsDialog.showModal === 'function') {
            creditsDialog.showModal();
        } else {
            creditsDialog.setAttribute('open', 'open');
        }

        stopAudio();
    }

    function closeCredits() {
        if (!creditsDialog) {
            return;
        }

        if (typeof creditsDialog.close === 'function') {
            creditsDialog.close();
        } else {
            creditsDialog.removeAttribute('open');
        }
    }

    /* ------------------------------------------------------------------
       Eventos
       ------------------------------------------------------------------ */

    root.addEventListener('click', function (event) {
        var target = event.target;

        if (!(target instanceof Element)) {
            return;
        }

        var play = target.closest('[data-play]');
        var option = target.closest('[data-animal]');
        var card = target.closest('[data-card]');
        var hint = target.closest('[data-audio-hint]');
        var creditsOpen = target.closest('[data-credits-open]');
        var creditsClose = target.closest('[data-credits-close]');
        var levelButton = target.closest('[data-level]');

        if (creditsOpen) {
            openCredits();
            return;
        }

        if (creditsClose) {
            closeCredits();
            return;
        }

        if (levelButton) {
            chooseLevel(Number(levelButton.getAttribute('data-level')));
            return;
        }

        if (play) {
            playCurrent();
            return;
        }

        if (hint) {
            showHint();
            return;
        }

        if (option) {
            answerSound(option);
            return;
        }

        if (card) {
            memoryOpen(card, Number(card.getAttribute('data-card')));
        }
    });

    startButton.addEventListener('click', function () {
        startLevel();
    });

    restartButton.addEventListener('click', function () {
        retryLevel();
        setMessage('Nivel reiniciado. ¡Vamos otra vez!');
    });

    replayButton.addEventListener('click', function () {
        retryLevel();
    });

    nextButton.addEventListener('click', function () {
        chooseLevel(state.levelIndex + 1);
    });

    if (stopAudioButton) {
        stopAudioButton.addEventListener('click', function () {
            stopAudio();
            setMessage('Sonido detenido. Puedes escucharlo otra vez cuando quieras.');
        });
    }

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            stopAudio();
        }
    });

    window.addEventListener('pagehide', function () {
        stopAudio();
    });

    window.addEventListener('beforeunload', function () {
        stopAudio();
    });

    /* ------------------------------------------------------------------
       Arranque
       ------------------------------------------------------------------ */

    Array.prototype.forEach.call(root.querySelectorAll('[data-pairs]'), function (chip) {
        chip.hidden = true;
    });

    buildLevel(0);
    updateChrome();
    showIntro();

    window.__animalesDebug = {
        ANIMALS: ANIMALS,
        LEVELS: LEVELS,
        state: state,
        soundPool: soundPool,
        buildSoundActivities: buildSoundActivities,
        buildMemoryActivity: buildMemoryActivity,
        buildLevel: buildLevel,
    };
})();
