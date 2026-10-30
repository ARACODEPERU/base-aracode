/**
 * Aula Virtual · Zona de juegos — Colores y formas.
 *
 * La partida vive entera en el navegador: no se envía nada al servidor y el
 * progreso del nivel solo se recuerda en este dispositivo. Diez niveles
 * combinan seis mecánicas reutilizables:
 *
 *   - color:    reconocer una muestra de color entre varias tarjetas.
 *   - shape:    reconocer una figura geométrica entre varias opciones.
 *   - combo:    encontrar la figura que coincide en forma y color.
 *   - pair:     tocar las dos figuras iguales (misma forma o forma y color).
 *   - sort:     llevar cada figura a la caja de su forma.
 *   - sequence: elegir la figura que continúa un patrón sencillo.
 *
 * Para agregar un nivel basta con sumar una entrada a LEVELS; para agregar una
 * figura o un color, una entrada a SHAPES o COLORS.
 */
(function () {
    'use strict';

    var root = document.querySelector('[data-juego="colores-formas"]');

    if (!root) {
        return;
    }

    /* ------------------------------------------------------------------
       Colores y figuras
       ------------------------------------------------------------------ */

    var COLORS = {
        rojo: { name: 'rojo', fem: 'roja', fill: '#ef5350', ink: '#ffffff' },
        azul: { name: 'azul', fem: 'azul', fill: '#3f8fd6', ink: '#ffffff' },
        amarillo: { name: 'amarillo', fem: 'amarilla', fill: '#f6c945', ink: '#5b4104' },
        verde: { name: 'verde', fem: 'verde', fill: '#5cbc63', ink: '#ffffff' },
        naranja: { name: 'naranja', fem: 'naranja', fill: '#f5913e', ink: '#ffffff' },
        morado: { name: 'morado', fem: 'morada', fill: '#9a72d6', ink: '#ffffff' },
        rosa: { name: 'rosa', fem: 'rosa', fill: '#f28fb8', ink: '#ffffff' },
    };

    var SHAPES = {
        circulo: { name: 'círculo', article: 'el', indefinite: 'un', plural: 'círculos' },
        cuadrado: { name: 'cuadrado', article: 'el', indefinite: 'un', plural: 'cuadrados' },
        triangulo: { name: 'triángulo', article: 'el', indefinite: 'un', plural: 'triángulos' },
        rectangulo: { name: 'rectángulo', article: 'el', indefinite: 'un', plural: 'rectángulos' },
        ovalo: { name: 'óvalo', article: 'el', indefinite: 'un', plural: 'óvalos' },
        estrella: { name: 'estrella', article: 'la', indefinite: 'una', plural: 'estrellas' },
    };

    var BASE_COLORS = ['rojo', 'azul', 'amarillo'];
    var NEW_COLORS = ['verde', 'naranja', 'morado', 'rosa'];
    var BASE_SHAPES = ['circulo', 'cuadrado', 'triangulo'];
    var NEW_SHAPES = ['rectangulo', 'ovalo', 'estrella'];

    var LEVELS = [
        {
            id: '1',
            name: 'Mis primeros colores',
            focus: 'Rojo, azul y amarillo',
            hint: 'Mira el color grande y toca la tarjeta que tiene el mismo color.',
            colors: BASE_COLORS,
            shapes: BASE_SHAPES,
            options: 3,
            activities: [{ type: 'color', rounds: 4 }],
        },
        {
            id: '2',
            name: 'Conocemos las formas',
            focus: 'Círculo, cuadrado y triángulo',
            hint: 'Mira la figura grande y busca otra figura con la misma forma.',
            colors: BASE_COLORS,
            shapes: BASE_SHAPES,
            options: 3,
            activities: [{ type: 'shape', rounds: 4 }],
        },
        {
            id: '3',
            name: 'Colores y formas juntos',
            focus: 'Forma y color a la vez',
            hint: 'Escucha la instrucción y elige la figura que tiene la forma y el color pedidos.',
            colors: BASE_COLORS,
            shapes: BASE_SHAPES,
            options: 3,
            growOptions: true,
            activities: [{ type: 'combo', rounds: 4 }],
        },
        {
            id: '4',
            name: 'Descubrimos nuevas formas',
            focus: 'Rectángulo, óvalo y estrella',
            hint: 'Aparecen tres formas nuevas. Míralas con calma y busca la que es igual.',
            colors: BASE_COLORS,
            shapes: BASE_SHAPES.concat(NEW_SHAPES),
            options: 4,
            targets: ['rectangulo', 'ovalo', 'estrella'],
            activities: [{ type: 'shape', rounds: 4 }],
        },
        {
            id: '5',
            name: 'Encuentra la pareja',
            focus: 'Dos figuras con la misma forma',
            hint: 'Toca dos figuras que tengan la misma forma. Pueden ser de colores distintos.',
            colors: BASE_COLORS,
            shapes: BASE_SHAPES,
            figures: 4,
            activities: [{ type: 'pair', rounds: 3, rule: 'shape' }],
        },
        {
            id: '6',
            name: 'Más colores para descubrir',
            focus: 'Verde, naranja, morado y rosa',
            hint: 'Llegan cuatro colores nuevos. Toca la tarjeta que coincide con la muestra.',
            colors: BASE_COLORS.concat(NEW_COLORS),
            shapes: BASE_SHAPES,
            options: 4,
            activities: [{ type: 'color', rounds: 4 }],
        },
        {
            id: '7',
            name: 'Cada figura en su lugar',
            focus: 'Clasificar por forma',
            hint: 'Toca una figura y luego su caja, o arrástrala. Cada caja guarda una forma.',
            colors: BASE_COLORS.concat(NEW_COLORS),
            shapes: BASE_SHAPES,
            activities: [{ type: 'sort', rounds: 2, bins: 3, perBin: 2 }],
        },
        {
            id: '8',
            name: 'Encuentra la figura correcta',
            focus: 'Forma y color con más opciones',
            hint: 'Lee o escucha la instrucción: solo una figura tiene la forma y el color pedidos.',
            colors: BASE_COLORS.concat(NEW_COLORS),
            shapes: BASE_SHAPES.concat(NEW_SHAPES),
            options: 4,
            activities: [{ type: 'combo', rounds: 4 }],
        },
        {
            id: '9',
            name: 'Completa la secuencia',
            focus: 'Patrones de dos figuras',
            hint: 'Mira cómo se repiten las figuras y elige la que sigue en el patrón.',
            colors: BASE_COLORS.concat(NEW_COLORS),
            shapes: BASE_SHAPES.concat(NEW_SHAPES),
            activities: [{ type: 'sequence', rounds: 4, pattern: 'AB' }],
        },
        {
            id: '10',
            name: '¡Maestro de colores y formas!',
            focus: 'El desafío final',
            hint: 'Un poco de todo: colores, formas, parejas, cajas y secuencias. Sin prisa.',
            colors: BASE_COLORS.concat(NEW_COLORS),
            shapes: BASE_SHAPES.concat(NEW_SHAPES),
            options: 4,
            figures: 6,
            activities: [
                { type: 'color', rounds: 1 },
                { type: 'shape', rounds: 1 },
                { type: 'combo', rounds: 1 },
                { type: 'pair', rounds: 1, rule: 'exact' },
                { type: 'sort', rounds: 1, bins: 3, perBin: 2 },
                { type: 'sequence', rounds: 1, pattern: 'AAB' },
            ],
        },
    ];

    var MECHANICS = {
        color: { label: 'Colores', hint: 'Mira el color y toca una tarjeta.' },
        shape: { label: 'Formas', hint: 'Mira la figura y busca la misma forma.' },
        combo: { label: 'Forma y color', hint: 'Busca la figura que cumple las dos pistas.' },
        pair: { label: 'Parejas', hint: 'Toca dos figuras que sean pareja.' },
        sort: { label: 'Clasificar', hint: 'Lleva cada figura a su caja.' },
        sequence: { label: 'Secuencia', hint: 'Mira el patrón y elige qué sigue.' },
    };

    var STORAGE_SOUND = 'colegio.formas.sonido';
    var STORAGE_LEVELS = 'colegio.formas.niveles';
    var DRAG_THRESHOLD = 8;
    var ADVANCE_DELAY = 1200;

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
    var soundButton = root.querySelector('[data-sound]');
    var soundLabel = root.querySelector('[data-sound-label]');
    var levelHint = root.querySelector('[data-level-hint]');
    var levelNames = root.querySelectorAll('[data-level-name]');
    var levelFocus = root.querySelector('[data-level-focus]');
    var levelNumbers = root.querySelectorAll('[data-level-number]');
    var activityLabel = root.querySelector('[data-activity-label]');
    var activityCurrent = root.querySelector('[data-activity-current]');
    var activityTotal = root.querySelector('[data-activity-total]');
    var mechanicLabel = root.querySelector('[data-mechanic]');

    /* ------------------------------------------------------------------
       Estado de la partida
       ------------------------------------------------------------------ */

    var state = {
        levelIndex: 0,
        activities: [],
        index: 0,
        completed: 0,
        partial: 0,
        started: false,
        solved: false,
        locked: false,
        selected: null,
    };

    var drag = null;
    var suppressClick = 0;
    var advanceTimer = 0;
    var audioContext = null;
    var soundOn = readSound();

    /* ------------------------------------------------------------------
       Utilidades
       ------------------------------------------------------------------ */

    function current() {
        return state.activities[state.index] || null;
    }

    function pick(list) {
        return list[Math.floor(Math.random() * list.length)];
    }

    function pickDifferent(list, avoid) {
        var pool = list.filter(function (value) {
            return value !== avoid;
        });

        return pick(pool.length ? pool : list);
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

    function cap(text) {
        return text.charAt(0).toUpperCase() + text.slice(1);
    }

    function comboKey(shape, color) {
        return shape + ':' + color;
    }

    function setMessage(text) {
        messageBox.textContent = text;
    }

    /**
     * Dibuja una figura geométrica en SVG. Todas las figuras comparten el
     * mismo lienzo de 100 × 100 para que el tamaño cambie solo con CSS.
     */
    function shapeMarkup(shape, color) {
        var fill = COLORS[color].fill;
        var stroke = ' stroke="' + fill + '" stroke-width="5" stroke-linejoin="round"';

        if (shape === 'circulo') {
            return '<circle cx="50" cy="50" r="36" fill="' + fill + '"/>';
        }

        if (shape === 'cuadrado') {
            return '<rect x="15" y="15" width="70" height="70" rx="9" fill="' + fill + '"/>';
        }

        if (shape === 'triangulo') {
            return '<polygon points="50,13 87,82 13,82" fill="' + fill + '"' + stroke + '/>';
        }

        if (shape === 'rectangulo') {
            return '<rect x="10" y="32" width="80" height="36" rx="9" fill="' + fill + '"/>';
        }

        if (shape === 'ovalo') {
            return '<ellipse cx="50" cy="50" rx="42" ry="27" fill="' + fill + '"/>';
        }

        return '<polygon points="50,10 61.8,38 92,40.6 68.8,60.4 76.2,90 50,73.6 23.8,90 31.2,60.4 8,40.6 38.2,38" fill="' +
            fill + '"' + stroke + '/>';
    }

    function figure(shape, color, className) {
        return '<span class="formas-figura' + (className ? ' ' + className : '') + '">' +
            '<svg viewBox="0 0 100 100" aria-hidden="true" focusable="false">' +
                shapeMarkup(shape, color) +
            '</svg>' +
        '</span>';
    }

    /**
     * El nombre del color concuerda con la figura: «la estrella morada».
     */
    function colorLabel(color, shape) {
        return shape === 'estrella' ? COLORS[color].fem : COLORS[color].name;
    }

    function comboLabel(shape, color) {
        return SHAPES[shape].name + ' ' + colorLabel(color, shape);
    }

    /* ------------------------------------------------------------------
       Sonidos breves (sin archivos externos)
       ------------------------------------------------------------------ */

    function readSound() {
        try {
            return window.localStorage.getItem(STORAGE_SOUND) !== 'off';
        } catch (error) {
            return true;
        }
    }

    function saveSound() {
        try {
            window.localStorage.setItem(STORAGE_SOUND, soundOn ? 'on' : 'off');
        } catch (error) {
            // Sin almacenamiento el sonido vale solo para esta visita.
        }
    }

    function ensureAudio() {
        if (!soundOn) {
            return null;
        }

        var AudioCtor = window.AudioContext || window.webkitAudioContext;

        if (!AudioCtor) {
            return null;
        }

        if (!audioContext) {
            try {
                audioContext = new AudioCtor();
            } catch (error) {
                audioContext = null;
            }
        }

        if (audioContext && audioContext.state === 'suspended') {
            audioContext.resume();
        }

        return audioContext;
    }

    function tone(frequency, delay, duration, type) {
        var context = ensureAudio();

        if (!context) {
            return;
        }

        var start = context.currentTime + delay;
        var oscillator = context.createOscillator();
        var gain = context.createGain();

        oscillator.type = type || 'sine';
        oscillator.frequency.setValueAtTime(frequency, start);
        gain.gain.setValueAtTime(0.0001, start);
        gain.gain.exponentialRampToValueAtTime(0.15, start + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, start + duration);

        oscillator.connect(gain);
        gain.connect(context.destination);
        oscillator.start(start);
        oscillator.stop(start + duration + 0.03);
    }

    function playPick() {
        tone(540, 0, 0.09, 'sine');
    }

    function playPlace() {
        tone(660, 0, 0.1, 'sine');
    }

    function playCorrect() {
        tone(660, 0, 0.12, 'sine');
        tone(990, 0.1, 0.16, 'sine');
    }

    function playWrong() {
        tone(262, 0, 0.16, 'triangle');
        tone(196, 0.11, 0.2, 'triangle');
    }

    function playWin() {
        [523, 659, 784, 1046].forEach(function (frequency, index) {
            tone(frequency, index * 0.12, 0.2, 'sine');
        });
    }

    function applySoundState() {
        soundOn = !!soundOn;
        soundLabel.textContent = soundOn ? 'Sonido: sí' : 'Sonido: no';
        soundButton.setAttribute('aria-pressed', soundOn ? 'true' : 'false');
        soundButton.classList.toggle('is-off', !soundOn);
    }

    /* ------------------------------------------------------------------
       Niveles completados (solo en este navegador)
       ------------------------------------------------------------------ */

    function readCompleted() {
        try {
            var saved = JSON.parse(window.localStorage.getItem(STORAGE_LEVELS) || '[]');
            return Array.isArray(saved) ? saved : [];
        } catch (error) {
            return [];
        }
    }

    function markCompleted(id) {
        var completed = readCompleted();

        if (completed.indexOf(id) === -1) {
            completed.push(id);
        }

        try {
            window.localStorage.setItem(STORAGE_LEVELS, JSON.stringify(completed));
        } catch (error) {
            // Si el navegador no guarda, el nivel simplemente no queda marcado.
        }
    }

    /* ------------------------------------------------------------------
       Generación de actividades
       ------------------------------------------------------------------ */

    function makeColor(level, previous) {
        var avoid = previous && previous.type === 'color' ? previous.answer : null;
        var answer = pickDifferent(level.colors, avoid);
        var options = shuffle(shuffle(level.colors.filter(function (color) {
            return color !== answer;
        })).slice(0, Math.min(level.options, level.colors.length) - 1).concat([answer]));

        return { type: 'color', answer: answer, options: options };
    }

    function makeShape(level, previous, forced) {
        var avoid = previous && previous.type === 'shape' ? previous.answer : null;
        var answer = forced || pickDifferent(level.shapes, avoid);
        var others = shuffle(level.shapes.filter(function (shape) {
            return shape !== answer;
        })).slice(0, Math.min(level.options, level.shapes.length) - 1);

        return { type: 'shape', answer: answer, options: shuffle(others.concat([answer])) };
    }

    function makeCombo(level, previous, optionCount) {
        var colors = level.colors;
        var shapes = level.shapes;
        var avoid = previous && previous.type === 'combo' ? comboKey(previous.answer.shape, previous.answer.color) : null;
        var answer;

        do {
            answer = { shape: pick(shapes), color: pick(colors) };
        } while (avoid && comboKey(answer.shape, answer.color) === avoid);

        var pool = [];

        shapes.forEach(function (shape) {
            colors.forEach(function (color) {
                if (comboKey(shape, color) !== comboKey(answer.shape, answer.color)) {
                    pool.push({ shape: shape, color: color });
                }
            });
        });

        var options = shuffle(pool).slice(0, optionCount - 1).concat([answer]);

        return { type: 'combo', answer: answer, options: shuffle(options) };
    }

    function makePair(level, rule) {
        var pairShape = pick(level.shapes);
        var pairColor = pick(level.colors);
        var figures = [];
        var usedKeys = [];

        if (rule === 'exact') {
            figures.push({ shape: pairShape, color: pairColor });
            figures.push({ shape: pairShape, color: pairColor });
            usedKeys.push(comboKey(pairShape, pairColor));

            var guard = 0;

            while (figures.length < level.figures && guard < 80) {
                guard += 1;

                var shape = pick(level.shapes);
                var color = pick(level.colors);
                var key = comboKey(shape, color);

                if (usedKeys.indexOf(key) !== -1) {
                    continue;
                }

                usedKeys.push(key);
                figures.push({ shape: shape, color: color });
            }

        } else {
            var otherColors = shuffle(level.colors.filter(function (color) {
                return color !== pairColor;
            }));

            figures.push({ shape: pairShape, color: pairColor });
            figures.push({ shape: pairShape, color: otherColors[0] || pick(level.colors) });

            // Una sola figura por cada forma restante: así la única pareja por
            // forma es la que el niño debe encontrar.
            level.shapes.forEach(function (shape) {
                if (shape === pairShape || figures.length >= level.figures) {
                    return;
                }

                figures.push({ shape: shape, color: pick(level.colors) });
            });

            // Con muchas formas disponibles no se necesitan más figuras: una
            // sola pareja por forma mantiene la respuesta única.
        }

        return {
            type: 'pair',
            rule: rule,
            figures: figures.map(function (figure, index) {
                return { id: 'f' + index, shape: figure.shape, color: figure.color };
            }),
            matched: false,
        };
    }

    function makeSort(level, binsCount, perBin) {
        var shapes = shuffle(level.shapes).slice(0, Math.min(binsCount, level.shapes.length));
        var items = [];

        shapes.forEach(function (shape) {
            for (var i = 0; i < perBin; i += 1) {
                items.push({
                    id: 'i' + items.length,
                    shape: shape,
                    color: pick(level.colors),
                });
            }
        });

        return {
            type: 'sort',
            bins: shuffle(shapes),
            items: shuffle(items),
            placed: [],
        };
    }

    function makeSequence(level, pattern) {
        var shapeA = pick(level.shapes);
        var shapeB = pickDifferent(level.shapes, shapeA);
        var colorA = pick(level.colors);
        var colorB = pickDifferent(level.colors, colorA);
        var first = { shape: shapeA, color: colorA };
        // La segunda figura cambia de forma y de color para que la diferencia
        // se vea a simple vista en cualquier patrón.
        var second = { shape: shapeB, color: colorB };

        var unit = pattern === 'AAB'
            ? [first, first, second]
            : [first, second];

        var prefix = [];

        for (var i = 0; i < 5; i += 1) {
            prefix.push(unit[i % unit.length]);
        }

        var answer = unit[prefix.length % unit.length];
        var pool = [];

        level.shapes.forEach(function (shape) {
            level.colors.forEach(function (color) {
                if (comboKey(shape, color) !== comboKey(answer.shape, answer.color)) {
                    pool.push({ shape: shape, color: color });
                }
            });
        });

        return {
            type: 'sequence',
            prefix: prefix,
            answer: answer,
            options: shuffle(shuffle(pool).slice(0, 3).concat([answer])),
        };
    }

    function buildActivities(level) {
        var list = [];
        var previous = null;

        level.activities.forEach(function (spec) {
            for (var round = 0; round < spec.rounds; round += 1) {
                var activity;

                if (spec.type === 'color') {
                    activity = makeColor(level, previous);
                } else if (spec.type === 'shape') {
                    var forced = level.targets && round < level.targets.length ? level.targets[round] : null;
                    activity = makeShape(level, previous, forced);
                } else if (spec.type === 'combo') {
                    var optionCount = level.growOptions && round >= 2
                        ? Math.min(4, level.options + 1)
                        : level.options;

                    activity = makeCombo(level, previous, optionCount);
                } else if (spec.type === 'pair') {
                    activity = makePair(level, spec.rule || 'shape');
                } else if (spec.type === 'sort') {
                    activity = makeSort(level, spec.bins || 3, spec.perBin || 2);
                } else {
                    activity = makeSequence(level, spec.pattern || 'AB');
                }

                list.push(activity);
                previous = activity;
            }
        });

        return list;
    }

    /* ------------------------------------------------------------------
       Progreso
       ------------------------------------------------------------------ */

    function updateProgress() {
        var total = state.activities.length || 1;
        var value = (state.completed + state.partial) / total;
        value = Math.max(0, Math.min(1, value));

        progressFill.style.width = Math.round(value * 100) + '%';
        progressBar.setAttribute('aria-valuenow', String(Math.round(value * 100)));

        var shown = Math.min(state.index + 1, total);
        activityCurrent.textContent = String(shown);
        activityTotal.textContent = String(total);
        activityLabel.textContent = 'Actividad ' + shown + ' de ' + total;
    }

    /* ------------------------------------------------------------------
       Dibujo de la actividad
       ------------------------------------------------------------------ */

    function clearSelections() {
        state.selected = null;

        stage.querySelectorAll('.is-selected').forEach(function (element) {
            element.classList.remove('is-selected');
        });
        stage.querySelectorAll('[aria-pressed="true"]').forEach(function (element) {
            element.setAttribute('aria-pressed', 'false');
        });
    }

    function optionsHtml(activity, kind) {
        var shapeColors = ['rojo', 'azul', 'amarillo', 'verde', 'naranja', 'rosa'];

        return activity.options.map(function (option, index) {
            if (kind === 'color') {
                return '<button type="button" class="formas-option formas-option--color" data-option="' + option +
                    '" aria-label="' + cap(COLORS[option].name) + '" aria-pressed="false">' +
                    '<span class="formas-option__swatch" style="background:' + COLORS[option].fill + '"></span>' +
                    '<span class="formas-option__label">' + cap(COLORS[option].name) + '</span>' +
                '</button>';
            }

            if (kind === 'combo') {
                return '<button type="button" class="formas-option" data-option="' + comboKey(option.shape, option.color) +
                    '" aria-label="' + cap(comboLabel(option.shape, option.color)) + '" aria-pressed="false">' +
                    figure(option.shape, option.color) +
                    '<span class="formas-option__label">' + cap(comboLabel(option.shape, option.color)) + '</span>' +
                '</button>';
            }

            return '<button type="button" class="formas-option" data-option="' + option +
                '" aria-label="' + cap(SHAPES[option].name) + '" aria-pressed="false">' +
                figure(option, shapeColors[index % shapeColors.length], '') +
                '<span class="formas-option__label">' + cap(SHAPES[option].name) + '</span>' +
            '</button>';
        }).join('');
    }

    function renderColor(activity) {
        stage.innerHTML =
            '<p class="formas-consigna">¿Cuál es este color?</p>' +
            '<div class="formas-color-target" role="img" aria-label="Muestra de color para reconocer">' +
                '<span class="formas-color-target__blob" style="background:' + COLORS[activity.answer].fill + '"></span>' +
            '</div>' +
            '<p class="formas-pista">Toca la tarjeta que tiene el mismo color.</p>' +
            '<div class="formas-options" role="group" aria-label="Elige un color">' +
                optionsHtml(activity, 'color') +
            '</div>';
    }

    function renderShape(activity) {
        stage.innerHTML =
            '<p class="formas-consigna">¿Cuál es esta forma?</p>' +
            '<div class="formas-target" role="img" aria-label="Figura para reconocer">' +
                figure(activity.answer, 'morado') +
            '</div>' +
            '<p class="formas-pista">Toca la figura que tiene la misma forma.</p>' +
            '<div class="formas-options" role="group" aria-label="Elige una forma">' +
                optionsHtml(activity, 'shape') +
            '</div>';
    }

    function renderCombo(activity) {
        var article = SHAPES[activity.answer.shape].article;

        stage.innerHTML =
            '<p class="formas-consigna">Encuentra ' + article + ' <strong>' +
                SHAPES[activity.answer.shape].name + ' ' +
                colorLabel(activity.answer.color, activity.answer.shape) +
            '</strong></p>' +
            '<p class="formas-pista">Solo una figura tiene esa forma y ese color.</p>' +
            '<div class="formas-options" role="group" aria-label="Elige una figura">' +
                optionsHtml(activity, 'combo') +
            '</div>';
    }

    function renderPair(activity) {
        var instruction = activity.rule === 'exact'
            ? 'Toca las dos figuras <strong>iguales</strong>'
            : 'Toca las dos figuras con la <strong>misma forma</strong>';
        var hint = activity.rule === 'exact'
            ? 'Deben tener la misma forma y el mismo color.'
            : 'Pueden tener colores distintos.';

        stage.innerHTML =
            '<p class="formas-consigna">' + instruction + '</p>' +
            '<p class="formas-pista">' + hint + '</p>' +
            '<div class="formas-pair" role="group" aria-label="Elige dos figuras">' +
                activity.figures.map(function (item) {
                    return '<button type="button" class="formas-pair__item" data-figure="' + item.id +
                        '" data-shape="' + item.shape + '" data-color="' + item.color +
                        '" aria-label="' + cap(comboLabel(item.shape, item.color)) + '" aria-pressed="false">' +
                        figure(item.shape, item.color) +
                    '</button>';
                }).join('') +
            '</div>';
    }

    function renderSort(activity) {
        var bins = activity.bins.map(function (shape) {
            return '<div class="formas-bin" data-bin="' + shape + '" tabindex="0" role="button" ' +
                'aria-label="Caja de ' + SHAPES[shape].plural + '">' +
                '<span class="formas-bin__head">' +
                    figure(shape, 'verde', 'formas-bin__icon') +
                    '<span class="formas-bin__name">' + cap(SHAPES[shape].plural) + '</span>' +
                '</span>' +
                '<span class="formas-bin__drop" data-bin-drop="' + shape + '"></span>' +
            '</div>';
        }).join('');

        var pool = activity.items.map(function (item) {
            return '<button type="button" class="formas-pool__item" data-item="' + item.id +
                '" data-shape="' + item.shape + '" data-color="' + item.color +
                '" aria-label="' + cap(comboLabel(item.shape, item.color)) + '" aria-pressed="false">' +
                figure(item.shape, item.color) +
            '</button>';
        }).join('');

        activity.placed = [];

        stage.innerHTML =
            '<p class="formas-consigna">Lleva cada figura a su caja</p>' +
            '<p class="formas-pista">Toca una figura y luego su caja. También puedes arrastrarla.</p>' +
            '<div class="formas-sort">' +
                '<div class="formas-bins" role="group" aria-label="Cajas por forma">' + bins + '</div>' +
                '<div class="formas-pool" data-pool role="group" aria-label="Figuras por clasificar">' + pool + '</div>' +
            '</div>';
    }

    function renderSequence(activity) {
        var sequence = activity.prefix.map(function (item, index) {
            return '<span class="formas-seq__item" aria-label="' + (index + 1) + ': ' +
                cap(comboLabel(item.shape, item.color)) + '">' +
                figure(item.shape, item.color) +
            '</span>';
        }).join('');

        stage.innerHTML =
            '<p class="formas-consigna">¿Qué figura sigue?</p>' +
            '<div class="formas-seq" role="img" aria-label="Secuencia de figuras para continuar">' +
                sequence +
                '<span class="formas-seq__item formas-seq__item--next" aria-hidden="true">?</span>' +
            '</div>' +
            '<p class="formas-pista">Mira cómo se repiten y elige la que continúa.</p>' +
            '<div class="formas-options" role="group" aria-label="Elige la figura que sigue">' +
                optionsHtml(activity, 'combo') +
            '</div>';
    }

    function renderActivity() {
        var activity = current();

        if (!activity) {
            return;
        }

        clearTimeout(advanceTimer);
        clearSelections();

        state.partial = 0;
        state.locked = false;

        mechanicLabel.textContent = MECHANICS[activity.type].label;

        if (activity.type === 'color') {
            renderColor(activity);
        } else if (activity.type === 'shape') {
            renderShape(activity);
        } else if (activity.type === 'combo') {
            renderCombo(activity);
        } else if (activity.type === 'pair') {
            renderPair(activity);
        } else if (activity.type === 'sort') {
            renderSort(activity);
        } else {
            renderSequence(activity);
        }

        stage.inert = false;
        updateProgress();
        setMessage(MECHANICS[activity.type].hint);
    }

    /* ------------------------------------------------------------------
       Acciones de la partida
       ------------------------------------------------------------------ */

    function completeActivity() {
        if (state.locked) {
            return;
        }

        state.locked = true;
        state.partial = 1;
        updateProgress();

        advanceTimer = window.setTimeout(function () {
            state.completed += 1;
            state.index += 1;
            state.partial = 0;

            if (state.index >= state.activities.length) {
                showVictory();
            } else {
                renderActivity();
            }
        }, ADVANCE_DELAY);
    }

    function disableOptions() {
        stage.querySelectorAll('[data-option]').forEach(function (option) {
            option.disabled = true;
        });
    }

    function answerOption(button) {
        var activity = current();

        if (!activity || state.locked) {
            return;
        }

        var value = button.getAttribute('data-option');
        var expected = activity.type === 'combo' || activity.type === 'sequence'
            ? comboKey(activity.answer.shape, activity.answer.color)
            : activity.answer;

        if (value === expected) {
            button.classList.add('is-correct');
            disableOptions();
            playCorrect();

            if (activity.type === 'color') {
                setMessage('¡Muy bien! Es el color ' + COLORS[activity.answer].name + '.');
            } else if (activity.type === 'shape') {
                setMessage('¡Esa es la forma correcta! Es ' + SHAPES[activity.answer].article + ' ' +
                    SHAPES[activity.answer].name + '.');
            } else if (activity.type === 'sequence') {
                setMessage('¡Excelente observación! La secuencia sigue con ' +
                    SHAPES[activity.answer.shape].article + ' ' +
                    comboLabel(activity.answer.shape, activity.answer.color) + '.');
            } else {
                setMessage('¡Muy bien! Es ' + comboLabel(activity.answer.shape, activity.answer.color) + '.');
            }

            completeActivity();

            return;
        }

        button.classList.add('is-wrong');

        window.setTimeout(function () {
            button.classList.remove('is-wrong');
        }, 560);

        if (activity.type === 'color') {
            setMessage('Casi. Mira otra vez el color y toca otra tarjeta.');
        } else if (activity.type === 'shape') {
            setMessage('Casi. Fíjate en los lados de la figura e inténtalo otra vez.');
        } else {
            setMessage('Esa figura no es. Vuelve a mirar la pista e inténtalo otra vez.');
        }

        playWrong();
    }

    function selectPairFigure(button) {
        var activity = current();

        if (!activity || activity.type !== 'pair' || state.locked) {
            return;
        }

        if (button.classList.contains('is-matched')) {
            return;
        }

        if (state.selected === button) {
            button.classList.remove('is-selected');
            button.setAttribute('aria-pressed', 'false');
            state.selected = null;
            setMessage('Figura guardada otra vez.');
            return;
        }

        if (!state.selected) {
            state.selected = button;
            button.classList.add('is-selected');
            button.setAttribute('aria-pressed', 'true');
            setMessage('Ahora toca su pareja.');
            playPick();

            return;
        }

        var first = state.selected;
        var sameShape = first.getAttribute('data-shape') === button.getAttribute('data-shape');
        var sameColor = first.getAttribute('data-color') === button.getAttribute('data-color');
        var correct = activity.rule === 'exact' ? sameShape && sameColor : sameShape;

        if (correct) {
            first.classList.remove('is-selected');
            first.setAttribute('aria-pressed', 'false');
            button.setAttribute('aria-pressed', 'false');
            first.classList.add('is-matched');
            button.classList.add('is-matched');
            first.disabled = true;
            button.disabled = true;
            state.selected = null;
            activity.matched = true;
            playCorrect();
            setMessage(activity.rule === 'exact'
                ? '¡Encontraste la pareja! Son iguales en forma y color.'
                : '¡Encontraste la pareja! Las dos son ' +
                    SHAPES[first.getAttribute('data-shape')].plural + '.');
            completeActivity();

            return;
        }

        first.classList.add('is-wrong');
        button.classList.add('is-wrong');
        playWrong();
        setMessage(activity.rule === 'exact'
            ? 'No son iguales. Busca dos con la misma forma y el mismo color.'
            : 'No tienen la misma forma. Prueba con otras dos.');

        window.setTimeout(function () {
            first.classList.remove('is-wrong', 'is-selected');
            first.setAttribute('aria-pressed', 'false');
            button.classList.remove('is-wrong');
        }, 600);

        state.selected = null;
    }

    function selectSortItem(button) {
        var activity = current();

        if (!activity || activity.type !== 'sort' || state.locked) {
            return;
        }

        if (state.selected === button) {
            button.classList.remove('is-selected');
            button.setAttribute('aria-pressed', 'false');
            state.selected = null;
            setMessage('Figura guardada otra vez.');
            return;
        }

        clearSelections();
        state.selected = button;
        button.classList.add('is-selected');
        button.setAttribute('aria-pressed', 'true');
        setMessage('Ahora toca la caja de su forma.');
        playPick();
    }

    function placeItem(button, bin) {
        var activity = current();

        if (!activity || activity.type !== 'sort' || state.locked) {
            return;
        }

        if (!button || !bin) {
            setMessage('Primero toca una figura y después su caja.');
            return;
        }

        var shape = button.getAttribute('data-shape');

        if (bin.getAttribute('data-bin') !== shape) {
            bin.classList.add('is-wrong');
            button.classList.add('is-wrong');
            playWrong();
            setMessage('Esa caja es para ' + SHAPES[bin.getAttribute('data-bin')].plural +
                '. Esta figura es ' + SHAPES[shape].indefinite + ' ' + SHAPES[shape].name + '.');

            window.setTimeout(function () {
                bin.classList.remove('is-wrong');
                button.classList.remove('is-wrong');
            }, 620);

            return;
        }

        var drop = bin.querySelector('[data-bin-drop]');

        if (drop) {
            drop.insertAdjacentHTML('beforeend',
                '<span class="formas-bin__placed">' + figure(shape, button.getAttribute('data-color')) + '</span>');
        }

        activity.placed.push(button.getAttribute('data-item'));
        button.remove();
        state.selected = null;
        state.partial = activity.placed.length / activity.items.length;
        updateProgress();
        playPlace();

        var left = activity.items.length - activity.placed.length;

        if (left === 0) {
            setMessage('¡Lo lograste! Cada figura está en su caja.');
            completeActivity();
        } else if (left === 1) {
            setMessage('¡Muy bien! Falta una figura.');
        } else {
            setMessage('¡Excelente observación! Faltan ' + left + ' figuras.');
        }

        var next = stage.querySelector('[data-item]');

        if (next) {
            next.focus({ preventScroll: true });
        }
    }

    /* ------------------------------------------------------------------
       Arrastre con puntero (mouse, lápiz y dedo)
       ------------------------------------------------------------------ */

    function hitTest(x, y, selector) {
        var element = document.elementFromPoint(x, y);

        return element && element.closest ? element.closest(selector) : null;
    }

    function onPointerDown(event) {
        if (!state.started || state.solved || state.locked || drag) {
            return;
        }

        var element = event.target.closest ? event.target.closest('[data-item]') : null;

        if (!element || element.disabled) {
            return;
        }

        if (event.pointerType === 'mouse' && event.button !== 0) {
            return;
        }

        drag = {
            element: element,
            pointerId: event.pointerId,
            startX: event.clientX,
            startY: event.clientY,
            moved: false,
            ghost: null,
            over: null,
        };

        try {
            element.setPointerCapture(event.pointerId);
        } catch (error) {
            // Sin captura el arrastre sigue con los eventos del documento.
        }

        document.addEventListener('pointermove', onPointerMove, { passive: false });
        document.addEventListener('pointerup', onPointerUp);
        document.addEventListener('pointercancel', onPointerUp);
    }

    function onPointerMove(event) {
        if (!drag || event.pointerId !== drag.pointerId) {
            return;
        }

        var deltaX = event.clientX - drag.startX;
        var deltaY = event.clientY - drag.startY;

        if (!drag.moved && Math.abs(deltaX) + Math.abs(deltaY) < DRAG_THRESHOLD) {
            return;
        }

        if (!drag.moved) {
            drag.moved = true;
            drag.ghost = drag.element.cloneNode(true);
            drag.ghost.classList.add('formas-ghost');
            drag.ghost.removeAttribute('data-item');
            drag.ghost.removeAttribute('aria-pressed');
            document.body.appendChild(drag.ghost);
            drag.element.classList.add('is-dragging');
        }

        if (event.cancelable) {
            event.preventDefault();
        }

        drag.ghost.style.left = event.clientX + 'px';
        drag.ghost.style.top = event.clientY + 'px';

        var over = hitTest(event.clientX, event.clientY, '[data-bin]');

        if (over !== drag.over) {
            if (drag.over) {
                drag.over.classList.remove('is-over');
            }

            drag.over = over;

            if (over) {
                over.classList.add('is-over');
            }
        }
    }

    function onPointerUp(event) {
        if (!drag || event.pointerId !== drag.pointerId) {
            return;
        }

        document.removeEventListener('pointermove', onPointerMove);
        document.removeEventListener('pointerup', onPointerUp);
        document.removeEventListener('pointercancel', onPointerUp);

        var currentDrag = drag;
        drag = null;

        if (currentDrag.ghost) {
            currentDrag.ghost.remove();
        }

        currentDrag.element.classList.remove('is-dragging');

        if (currentDrag.over) {
            currentDrag.over.classList.remove('is-over');
        }

        if (!currentDrag.moved) {
            // El toque sin desplazamiento lo resuelve el evento click.
            return;
        }

        suppressClick = Date.now() + 320;

        var bin = hitTest(event.clientX, event.clientY, '[data-bin]');

        if (bin) {
            placeItem(currentDrag.element, bin);
        } else {
            setMessage('Suelta la figura dentro de una caja.');
        }
    }

    function cancelDrag() {
        document.removeEventListener('pointermove', onPointerMove);
        document.removeEventListener('pointerup', onPointerUp);
        document.removeEventListener('pointercancel', onPointerUp);

        if (!drag) {
            return;
        }

        if (drag.ghost) {
            drag.ghost.remove();
        }

        if (drag.element && drag.element.isConnected) {
            drag.element.classList.remove('is-dragging');
        }

        if (drag.over) {
            drag.over.classList.remove('is-over');
        }

        drag = null;
    }

    /* ------------------------------------------------------------------
       Eventos de la actividad
       ------------------------------------------------------------------ */

    function onStageClick(event) {
        if (!state.started || state.solved || state.locked) {
            return;
        }

        if (Date.now() < suppressClick) {
            return;
        }

        var option = event.target.closest ? event.target.closest('[data-option]') : null;

        if (option) {
            answerOption(option);
            return;
        }

        var pairFigure = event.target.closest ? event.target.closest('[data-figure]') : null;

        if (pairFigure) {
            selectPairFigure(pairFigure);
            return;
        }

        var item = event.target.closest ? event.target.closest('[data-item]') : null;

        if (item) {
            selectSortItem(item);
            return;
        }

        var bin = event.target.closest ? event.target.closest('[data-bin]') : null;

        if (bin) {
            if (!state.selected) {
                setMessage('Primero toca una figura y luego su caja.');
                return;
            }

            placeItem(state.selected, bin);
        }
    }

    /* ------------------------------------------------------------------
       Niveles y celebración
       ------------------------------------------------------------------ */

    function renderLevels() {
        var completed = readCompleted();

        levelsBox.innerHTML = LEVELS.map(function (level, index) {
            var active = index === state.levelIndex;
            var done = completed.indexOf(level.id) !== -1;

            return '<button type="button" class="juego-nivel' + (active ? ' is-active' : '') +
                (done ? ' is-complete' : '') + '" data-level="' + index +
                '" aria-pressed="' + (active ? 'true' : 'false') + '">' +
                '<span class="juego-nivel__num">' + (index + 1) + '</span>' +
                '<span class="juego-nivel__info">' +
                    '<span class="juego-nivel__name">' + level.name + '</span>' +
                    '<span class="juego-nivel__meta">' + level.focus + '</span>' +
                '</span>' +
            '</button>';
        }).join('');
    }

    function renderLevelInfo() {
        var level = LEVELS[state.levelIndex];

        levelNumbers.forEach(function (element) {
            element.textContent = String(state.levelIndex + 1);
        });
        levelNames.forEach(function (element) {
            element.textContent = level.name;
        });

        if (levelFocus) {
            levelFocus.textContent = level.focus;
        }

        levelHint.textContent = level.hint;
    }

    function showVictory() {
        var level = LEVELS[state.levelIndex];
        var last = state.levelIndex === LEVELS.length - 1;

        state.solved = true;
        stage.inert = true;
        markCompleted(level.id);
        renderLevels();
        updateProgress();

        victoryTitle.textContent = last
            ? '¡Excelente! ¡Ya eres un maestro de los colores y las formas!'
            : '¡Nivel completado!';
        victoryText.textContent = last
            ? 'Reconociste colores, encontraste formas, armaste parejas y completaste secuencias. ¡Gran trabajo!'
            : 'Completaste las ' + state.activities.length + ' actividades del nivel. ¡Sigue así!';

        nextButton.hidden = last;
        victory.hidden = false;
        victory.classList.add('is-visible');
        setMessage(last ? '¡Terminaste los diez niveles!' : '¡Nivel completado! Puedes seguir con el siguiente.');
        playWin();

        (last ? replayButton : nextButton).focus({ preventScroll: true });
    }

    function focusStage() {
        var first = stage.querySelector('[data-option], [data-figure], [data-item]');

        if (first) {
            first.focus({ preventScroll: true });
        }
    }

    function startLevel() {
        state.started = true;
        stage.inert = false;
        intro.hidden = true;
        victory.hidden = true;
        victory.classList.remove('is-visible');
        root.classList.add('is-playing');
        setMessage(MECHANICS[current().type].hint);
        focusStage();
    }

    function loadLevel(index, autostart) {
        clearTimeout(advanceTimer);
        cancelDrag();

        state.levelIndex = Math.max(0, Math.min(LEVELS.length - 1, index));
        state.activities = buildActivities(LEVELS[state.levelIndex]);
        state.index = 0;
        state.completed = 0;
        state.partial = 0;
        state.started = false;
        state.solved = false;
        state.locked = false;
        state.selected = null;

        root.classList.remove('is-playing');
        victory.hidden = true;
        victory.classList.remove('is-visible');
        intro.hidden = false;

        renderLevelInfo();
        renderLevels();
        renderActivity();
        stage.inert = true;
        setMessage('Pulsa «¡Vamos a jugar!» para empezar.');

        if (autostart) {
            startLevel();
        }
    }

    /* ------------------------------------------------------------------
       Controles
       ------------------------------------------------------------------ */

    startButton.addEventListener('click', startLevel);

    restartButton.addEventListener('click', function () {
        loadLevel(state.levelIndex, true);
    });

    replayButton.addEventListener('click', function () {
        loadLevel(state.levelIndex, true);
    });

    nextButton.addEventListener('click', function () {
        loadLevel(state.levelIndex + 1, true);
    });

    levelsBox.addEventListener('click', function (event) {
        var button = event.target.closest('[data-level]');

        if (button) {
            loadLevel(Number(button.getAttribute('data-level')));
        }
    });

    soundButton.addEventListener('click', function () {
        soundOn = !soundOn;
        saveSound();
        applySoundState();

        if (soundOn) {
            tone(880, 0, 0.12, 'sine');
        }
    });

    stage.addEventListener('click', onStageClick);
    stage.addEventListener('pointerdown', onPointerDown);

    // Las cajas también se abren con el teclado: Enter o espacio colocan la
    // figura seleccionada, igual que un toque en la pantalla táctil.
    stage.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter' && event.key !== ' ') {
            return;
        }

        if (!event.target.hasAttribute || !event.target.hasAttribute('data-bin') || !state.selected) {
            return;
        }

        event.preventDefault();
        placeItem(state.selected, event.target);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && state.selected) {
            clearSelections();
            setMessage('Selección quitada.');
        }
    });

    // Al ocultar la pestaña se corta cualquier arrastre a medias.
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            cancelDrag();
        }
    });

    /* ------------------------------------------------------------------
       Arranque
       ------------------------------------------------------------------ */

    applySoundState();
    loadLevel(0);
})();
