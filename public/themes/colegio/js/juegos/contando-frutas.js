/**
 * Aula Virtual · Zona de juegos — Contando frutas.
 *
 * La partida vive entera en el navegador: no se envía nada al servidor. Diez
 * niveles combinan tres mecánicas reutilizables:
 *
 *   - count:  contar un grupo de frutas y elegir el número.
 *   - basket: llenar la cesta con la cantidad pedida.
 *   - match:  unir cada grupo de frutas con su número.
 *
 * Para agregar un nivel basta con sumar una entrada a LEVELS; para agregar una
 * fruta, una entrada a FRUITS y su SVG en
 * public/themes/colegio/img/juegos/frutas.
 */
(function () {
    'use strict';

    var root = document.querySelector('[data-juego="contando-frutas"]');

    if (!root) {
        return;
    }

    /* ------------------------------------------------------------------
       Frutas y niveles
       ------------------------------------------------------------------ */

    var FRUITS = {
        manzana: { one: 'manzana', many: 'manzanas', file: 'manzana.svg' },
        banana: { one: 'banana', many: 'bananas', file: 'banana.svg' },
        naranja: { one: 'naranja', many: 'naranjas', file: 'naranja.svg' },
        fresa: { one: 'fresa', many: 'fresas', file: 'fresa.svg' },
        pera: { one: 'pera', many: 'peras', file: 'pera.svg' },
        uvas: { one: 'racimo de uvas', many: 'racimos de uvas', file: 'uvas.svg' },
    };

    var FRUIT_KEYS = Object.keys(FRUITS);

    var LEVELS = [
        {
            id: '1',
            name: '¿Cuántas frutas hay?',
            focus: 'Del 1 al 3 · contar y elegir',
            min: 1,
            max: 3,
            hint: 'Cuenta las frutas del grupo y toca el número que dice cuántas hay.',
            activities: [{ type: 'count', rounds: 3 }],
        },
        {
            id: '2',
            name: 'Contamos hasta 5',
            focus: 'Del 1 al 5 · contar y elegir',
            min: 1,
            max: 5,
            hint: 'Ahora hay más frutas. Cuéntalas una por una y busca su número.',
            activities: [{ type: 'count', rounds: 4 }],
        },
        {
            id: '3',
            name: 'Llenamos la cesta',
            focus: 'Del 1 al 3 · llenar la cesta',
            min: 1,
            max: 3,
            hint: 'Arrastra las frutas hasta la cesta o toca una fruta y después la cesta.',
            activities: [{ type: 'basket', rounds: 3 }],
        },
        {
            id: '4',
            name: 'Una cesta con más frutas',
            focus: 'Del 1 al 5 · llenar la cesta',
            min: 1,
            max: 5,
            hint: 'Cada cesta pide una cantidad distinta. Mira cuántas faltan mientras juegas.',
            activities: [{ type: 'basket', rounds: 4 }],
        },
        {
            id: '5',
            name: 'Une las frutas con su número',
            focus: 'Del 1 al 5 · unir grupos y números',
            min: 1,
            max: 5,
            hint: 'Toca un grupo de frutas y luego el número que le toca. También puedes arrastrarlo.',
            activities: [{ type: 'match', rounds: 3, pairs: 3 }],
        },
        {
            id: '6',
            name: 'Contamos hasta 7',
            focus: 'Del 1 al 7 · contar y elegir',
            min: 1,
            max: 7,
            hint: 'Cuenta sin prisa: puedes señalar cada fruta con el dedo mientras cuentas.',
            activities: [{ type: 'count', rounds: 4 }],
        },
        {
            id: '7',
            name: 'Cestas hasta 7',
            focus: 'Del 1 al 7 · llenar la cesta',
            min: 1,
            max: 7,
            hint: 'Las cestas piden más frutas. Las casillas vacías te dicen cuántas faltan.',
            activities: [{ type: 'basket', rounds: 4 }],
        },
        {
            id: '8',
            name: 'Cada grupo con su número',
            focus: 'Del 1 al 10 · unir grupos y números',
            min: 1,
            max: 10,
            hint: 'Cada grupo tiene su número. Cuenta bien antes de tocar.',
            activities: [{ type: 'match', rounds: 3, pairs: 4 }],
        },
        {
            id: '9',
            name: 'El mercado de frutas',
            focus: 'Del 1 al 10 · las tres mecánicas',
            min: 1,
            max: 10,
            hint: 'En el mercado hay de todo: cuenta, llena cestas y une grupos con números.',
            activities: [
                { type: 'count', rounds: 2, values: [6, 9] },
                { type: 'basket', rounds: 2, targets: [4, 8] },
                { type: 'match', rounds: 1, pairs: 4 },
            ],
        },
        {
            id: '10',
            name: '¡Campeones del conteo!',
            focus: 'Del 1 al 10 · desafío final',
            min: 1,
            max: 10,
            hint: 'El último desafío: cuenta, llena cestas y une números hasta llegar al 10.',
            activities: [
                { type: 'count', rounds: 2, values: [10, 7] },
                { type: 'basket', rounds: 2, targets: [9, 6] },
                { type: 'match', rounds: 2, pairs: 5 },
            ],
        },
    ];

    var MECHANICS = {
        count: { label: 'Contar', hint: 'Cuenta las frutas y toca el número.' },
        basket: { label: 'Cesta', hint: 'Coloca la cantidad pedida dentro de la cesta.' },
        match: { label: 'Relacionar', hint: 'Une cada grupo con su número.' },
    };

    var STORAGE_SOUND = 'colegio.frutas.sonido';
    var STORAGE_LEVELS = 'colegio.frutas.niveles';
    var DRAG_THRESHOLD = 8;
    var ADVANCE_DELAY = 1300;
    var imageBase = (root.getAttribute('data-image-base') || '').replace(/\/+$/, '');
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

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
        selectedFruit: null,
        selectedGroup: null,
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

    function pickFruit(exclude) {
        var key = FRUIT_KEYS[Math.floor(Math.random() * FRUIT_KEYS.length)];
        var attempts = 0;

        while (exclude && key === exclude && FRUIT_KEYS.length > 1 && attempts < 8) {
            key = FRUIT_KEYS[Math.floor(Math.random() * FRUIT_KEYS.length)];
            attempts += 1;
        }

        return key;
    }

    function randInt(min, max) {
        return min + Math.floor(Math.random() * (max - min + 1));
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

    function fruitImg(key, className) {
        var fruit = FRUITS[key];

        return '<img class="fruta' + (className ? ' ' + className : '') + '" src="' +
            imageBase + '/' + fruit.file + '" alt="" width="64" height="64" draggable="false">';
    }

    function fruitCountLabel(amount, key) {
        return amount + ' ' + (amount === 1 ? FRUITS[key].one : FRUITS[key].many);
    }

    function setMessage(text) {
        messageBox.textContent = text;
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

    function pickCounts(amount, min, max) {
        var pool = [];

        for (var value = min; value <= max; value += 1) {
            pool.push(value);
        }

        return shuffle(pool).slice(0, Math.min(amount, pool.length));
    }

    function ascendingTargets(min, max, rounds) {
        return pickCounts(rounds, min, max).sort(function (a, b) {
            return a - b;
        });
    }

    function buildOptions(answer, min, max, limit) {
        var near = [];
        var far = [];

        for (var value = min; value <= max; value += 1) {
            if (value === answer) {
                continue;
            }

            if (Math.abs(value - answer) <= 2) {
                near.push(value);
            } else {
                far.push(value);
            }
        }

        var pool = shuffle(near).concat(shuffle(far));
        var options = [answer];

        for (var i = 0; i < pool.length && options.length < limit; i += 1) {
            options.push(pool[i]);
        }

        return shuffle(options);
    }

    function makeCount(level, previous, forced) {
        var fruit = pickFruit(previous && previous.fruit);
        var count = forced || randInt(level.min, level.max);

        if (!forced && previous && previous.type === 'count' && previous.count === count && level.max > level.min) {
            count = count === level.max ? count - 1 : count + 1;
        }

        var optionCount = Math.min(4, level.max - level.min + 1);

        return {
            type: 'count',
            fruit: fruit,
            count: count,
            options: buildOptions(count, level.min, level.max, optionCount),
        };
    }

    function makeBasket(level, target, previous) {
        var fruit = pickFruit(previous && previous.fruit);
        var poolSize = Math.min(10, Math.max(target + 2, target));

        return {
            type: 'basket',
            fruit: fruit,
            target: target,
            pool: Array.apply(null, Array(poolSize)).map(function (_, index) {
                return index;
            }),
            placed: [],
        };
    }

    function makeMatch(level, pairs) {
        var counts = pickCounts(pairs, level.min, level.max);
        var groups = counts.map(function (count, index) {
            return {
                id: 'g' + index,
                count: count,
                fruit: pickFruit(),
            };
        });

        return {
            type: 'match',
            groups: shuffle(groups),
            numbers: shuffle(counts),
            matched: 0,
        };
    }

    function buildActivities(level) {
        var list = [];
        var previous = null;

        level.activities.forEach(function (spec) {
            var targets = spec.targets ? spec.targets.slice() : null;

            if (!targets && spec.type === 'basket') {
                targets = ascendingTargets(level.min, level.max, spec.rounds);
            }

            for (var round = 0; round < spec.rounds; round += 1) {
                var activity;

                if (spec.type === 'count') {
                    activity = makeCount(level, previous, spec.values ? spec.values[round] : null);
                } else if (spec.type === 'basket') {
                    activity = makeBasket(level, targets[round], previous);
                } else {
                    activity = makeMatch(level, spec.pairs || 3);
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
        state.selectedFruit = null;
        state.selectedGroup = null;

        stage.querySelectorAll('.is-selected').forEach(function (element) {
            element.classList.remove('is-selected');
        });
        stage.querySelectorAll('[aria-pressed="true"]').forEach(function (element) {
            element.setAttribute('aria-pressed', 'false');
        });

        var basket = stage.querySelector('[data-basket]');

        if (basket) {
            basket.classList.remove('is-ready');
        }
    }

    function renderCount(activity) {
        var chips = '';

        for (var i = 0; i < activity.count; i += 1) {
            chips += '<span class="fruta-chip">' + fruitImg(activity.fruit) + '</span>';
        }

        var options = activity.options.map(function (number) {
            return '<button type="button" class="frutas-numero" data-option="' + number +
                '" aria-label="Número ' + number + '">' + number + '</button>';
        }).join('');

        stage.innerHTML =
            '<p class="frutas-consigna">¿Cuántas <strong>' + FRUITS[activity.fruit].many + '</strong> hay?</p>' +
            '<div class="frutas-grupo" role="img" aria-label="' + fruitCountLabel(activity.count, activity.fruit) + '">' +
                chips +
            '</div>' +
            '<p class="frutas-pista">Cuéntalas una por una y toca el número.</p>' +
            '<div class="frutas-opciones" role="group" aria-label="Elige un número">' + options + '</div>';
    }

    function renderBasket(activity) {
        var pool = activity.pool.map(function (id) {
            return '<button type="button" class="frutas-pool__item" data-pool-fruit="' + id +
                '" aria-label="' + FRUITS[activity.fruit].one + '" aria-pressed="false">' +
                fruitImg(activity.fruit) +
            '</button>';
        }).join('');

        activity.placed = [];

        stage.innerHTML =
            '<p class="frutas-consigna">Coloca <strong>' + fruitCountLabel(activity.target, activity.fruit) +
                '</strong> en la cesta.</p>' +
            '<div class="frutas-cesta-wrap">' +
                '<div class="frutas-cesta" data-basket role="group" tabindex="0" aria-label="Cesta de frutas">' +
                    '<span class="frutas-cesta__label">Cesta ' +
                        '<span class="frutas-cesta__count" data-basket-count>0 de ' + activity.target + '</span>' +
                    '</span>' +
                    '<div class="frutas-cesta__slots" data-basket-slots></div>' +
                '</div>' +
            '</div>' +
            '<div class="frutas-pool" data-pool>' + pool + '</div>' +
            '<p class="frutas-pista" data-basket-hint>Arrastra una fruta o tócala y después toca la cesta.</p>';

        refreshBasket();
    }

    function renderMatch(activity) {
        var groups = activity.groups.map(function (group) {
            var chips = '';

            for (var i = 0; i < group.count; i += 1) {
                chips += '<span class="fruta-chip">' + fruitImg(group.fruit) + '</span>';
            }

            return '<button type="button" class="frutas-match__grupo" data-group="' + group.id +
                '" data-count="' + group.count +
                '" aria-label="Grupo con ' + fruitCountLabel(group.count, group.fruit) + '" aria-pressed="false">' +
                '<span class="frutas-match__fruits">' + chips + '</span>' +
                '<span class="frutas-match__badge" data-badge aria-hidden="true">?</span>' +
            '</button>';
        }).join('');

        var numbers = activity.numbers.map(function (number) {
            return '<button type="button" class="frutas-numero frutas-numero--match" data-number="' + number +
                '" aria-label="Número ' + number + '">' + number + '</button>';
        }).join('');

        activity.matched = 0;

        stage.innerHTML =
            '<p class="frutas-consigna">Cuenta cada grupo y únelo con su número.</p>' +
            '<div class="frutas-match">' +
                '<div class="frutas-match__col">' +
                    '<p class="frutas-match__head">Grupos</p>' + groups +
                '</div>' +
                '<div class="frutas-match__col frutas-match__col--nums">' +
                    '<p class="frutas-match__head">Números</p>' + numbers +
                '</div>' +
            '</div>' +
            '<p class="frutas-pista">Toca un grupo y luego su número, o arrastra el grupo hasta el número.</p>';
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

        if (activity.type === 'count') {
            renderCount(activity);
        } else if (activity.type === 'basket') {
            renderBasket(activity);
        } else {
            renderMatch(activity);
        }

        stage.inert = false;
        updateProgress();
        setMessage(MECHANICS[activity.type].hint);
    }

    function refreshBasket() {
        var activity = current();

        if (!activity || activity.type !== 'basket') {
            return;
        }

        var slotsBox = stage.querySelector('[data-basket-slots]');
        var poolBox = stage.querySelector('[data-pool]');
        var counter = stage.querySelector('[data-basket-count]');
        var hint = stage.querySelector('[data-basket-hint]');
        var basket = stage.querySelector('[data-basket]');

        if (!slotsBox || !poolBox) {
            return;
        }

        var slots = '';

        for (var i = 0; i < activity.target; i += 1) {
            var id = activity.placed[i];

            if (typeof id === 'number') {
                slots += '<button type="button" class="frutas-slot is-filled" data-placed="' + id +
                    '" aria-label="Quitar de la cesta">' + fruitImg(activity.fruit) + '</button>';
            } else {
                slots += '<span class="frutas-slot" aria-hidden="true"></span>';
            }
        }

        slotsBox.innerHTML = slots;

        var pool = '';

        activity.pool.forEach(function (fruitId) {
            if (activity.placed.indexOf(fruitId) !== -1) {
                return;
            }

            var selected = state.selectedFruit === fruitId;

            pool += '<button type="button" class="frutas-pool__item' + (selected ? ' is-selected' : '') +
                '" data-pool-fruit="' + fruitId + '" aria-label="' + FRUITS[activity.fruit].one +
                '" aria-pressed="' + (selected ? 'true' : 'false') + '">' +
                fruitImg(activity.fruit) +
            '</button>';
        });

        poolBox.innerHTML = pool;

        if (counter) {
            counter.textContent = activity.placed.length + ' de ' + activity.target;
        }

        if (basket) {
            basket.classList.toggle('is-full', activity.placed.length === activity.target);
            basket.classList.toggle('is-ready', state.selectedFruit !== null);
        }

        if (hint) {
            var left = activity.target - activity.placed.length;

            if (left <= 0) {
                hint.textContent = '¡Cesta completa!';
            } else if (left === 1) {
                hint.textContent = '¡Falta 1!';
            } else {
                hint.textContent = '¡Faltan ' + left + '!';
            }
        }

        state.partial = activity.target ? activity.placed.length / activity.target : 0;
        updateProgress();
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

    function answerCount(button) {
        var activity = current();

        if (!activity || activity.type !== 'count') {
            return;
        }

        var value = Number(button.getAttribute('data-option'));

        if (value === activity.count) {
            button.classList.add('is-correct');
            stage.querySelectorAll('[data-option]').forEach(function (option) {
                option.disabled = true;
            });
            setMessage('¡Muy bien! Hay ' + fruitCountLabel(activity.count, activity.fruit) + '.');
            playCorrect();
            completeActivity();

            return;
        }

        button.classList.add('is-wrong');

        window.setTimeout(function () {
            button.classList.remove('is-wrong');
        }, 560);

        setMessage('Casi. Cuenta otra vez y toca otro número.');
        playWrong();
    }

    function selectFruit(button) {
        var id = Number(button.getAttribute('data-pool-fruit'));

        if (state.selectedFruit === id) {
            state.selectedFruit = null;
            refreshBasket();
            focusPoolFruit(id);
            setMessage('Fruta guardada otra vez.');
            return;
        }

        state.selectedFruit = id;
        refreshBasket();
        setMessage('Ahora toca la cesta para ponerla.');
        focusPoolFruit(id);
        playPick();
    }

    /**
     * La bandeja se vuelve a dibujar al seleccionar o colocar una fruta; el
     * foco se devuelve a la fruta equivalente para no perder el teclado.
     */
    function focusPoolFruit(id) {
        var again = stage.querySelector('[data-pool-fruit="' + id + '"]');

        if (again) {
            again.focus({ preventScroll: true });
            return;
        }

        var basket = stage.querySelector('[data-basket]');

        if (basket) {
            basket.focus({ preventScroll: true });
        }
    }

    function placeFruit(id) {
        var activity = current();

        if (!activity || activity.type !== 'basket' || state.locked) {
            return;
        }

        if (activity.placed.indexOf(id) !== -1 || activity.placed.length >= activity.target) {
            return;
        }

        activity.placed.push(id);
        state.selectedFruit = null;
        refreshBasket();
        focusPoolFruit(id);
        playPlace();

        var left = activity.target - activity.placed.length;

        if (left === 0) {
            setMessage('¡Lo lograste! La cesta tiene ' + fruitCountLabel(activity.target, activity.fruit) + '.');
            completeActivity();
        } else if (left === 1) {
            setMessage('¡Una fruta más!');
        } else {
            setMessage('¡Muy bien! Faltan ' + left + '.');
        }
    }

    function removeFruit(id) {
        var activity = current();

        if (!activity || activity.type !== 'basket' || state.locked) {
            return;
        }

        var index = activity.placed.indexOf(id);

        if (index === -1) {
            return;
        }

        activity.placed.splice(index, 1);
        refreshBasket();

        var basket = stage.querySelector('[data-basket]');

        if (basket) {
            basket.focus({ preventScroll: true });
        }

        setMessage('Puedes cambiarla por otra fruta.');
    }

    function selectGroup(group) {
        if (!group || group.classList.contains('is-matched') || state.locked) {
            return;
        }

        if (state.selectedGroup === group) {
            state.selectedGroup = null;
            group.classList.remove('is-selected');
            group.setAttribute('aria-pressed', 'false');
            setMessage('Grupo guardado otra vez.');
            return;
        }

        clearSelections();
        state.selectedGroup = group;
        group.classList.add('is-selected');
        group.setAttribute('aria-pressed', 'true');
        setMessage('Ahora toca el número que le toca.');
        playPick();
    }

    function attemptMatch(group, numberButton) {
        var activity = current();

        if (!activity || activity.type !== 'match' || state.locked || !numberButton) {
            return;
        }

        if (!group) {
            setMessage('Primero toca un grupo de frutas.');
            return;
        }

        if (group.classList.contains('is-matched') || numberButton.classList.contains('is-matched')) {
            return;
        }

        var count = Number(group.getAttribute('data-count'));
        var number = Number(numberButton.getAttribute('data-number'));

        if (count === number) {
            group.classList.add('is-matched');
            group.classList.remove('is-selected');
            group.disabled = true;
            group.setAttribute('aria-pressed', 'false');

            var badge = group.querySelector('[data-badge]');

            if (badge) {
                badge.textContent = String(number);
            }

            numberButton.classList.add('is-matched');
            numberButton.disabled = true;
            state.selectedGroup = null;
            activity.matched += 1;
            state.partial = activity.matched / activity.groups.length;
            updateProgress();
            setMessage('¡Muy bien! ' + count + ' con el número ' + number + '.');
            playCorrect();

            if (activity.matched === activity.groups.length) {
                completeActivity();
            }

            return;
        }

        group.classList.add('is-wrong');
        numberButton.classList.add('is-wrong');

        window.setTimeout(function () {
            group.classList.remove('is-wrong');
            numberButton.classList.remove('is-wrong');
        }, 560);

        setMessage('Ese número no es. Cuenta otra vez.');
        playWrong();
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

        var element = event.target.closest ? event.target.closest('[data-pool-fruit], [data-group]') : null;

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
            isGroup: element.hasAttribute('data-group'),
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
            drag.ghost.classList.add('frutas-fantasma');
            drag.ghost.removeAttribute('data-pool-fruit');
            drag.ghost.removeAttribute('data-group');
            drag.ghost.removeAttribute('aria-pressed');
            document.body.appendChild(drag.ghost);
            drag.element.classList.add('is-dragging');
        }

        if (event.cancelable) {
            event.preventDefault();
        }

        drag.ghost.style.left = event.clientX + 'px';
        drag.ghost.style.top = event.clientY + 'px';

        var over = hitTest(event.clientX, event.clientY, drag.isGroup ? '[data-number]' : '[data-basket]');

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

        if (currentDrag.isGroup) {
            var numberButton = hitTest(event.clientX, event.clientY, '[data-number]');

            if (numberButton) {
                attemptMatch(currentDrag.element, numberButton);
            } else {
                setMessage('Suelta el grupo sobre un número.');
            }

            return;
        }

        var basket = hitTest(event.clientX, event.clientY, '[data-basket]');

        if (basket) {
            placeFruit(Number(currentDrag.element.getAttribute('data-pool-fruit')));
        } else {
            setMessage('Suelta la fruta dentro de la cesta.');
        }
    }

    /**
     * Corta un arrastre en curso (al reiniciar o cambiar de nivel) y deja
     * limpios el documento y el fantasma.
     */
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
            answerCount(option);
            return;
        }

        var fruit = event.target.closest ? event.target.closest('[data-pool-fruit]') : null;

        if (fruit) {
            selectFruit(fruit);
            return;
        }

        var placed = event.target.closest ? event.target.closest('[data-placed]') : null;

        if (placed && state.selectedFruit === null) {
            removeFruit(Number(placed.getAttribute('data-placed')));
            return;
        }

        var basket = event.target.closest ? event.target.closest('[data-basket]') : null;

        if (basket) {
            if (state.selectedFruit === null) {
                setMessage('Primero toca una fruta y luego la cesta.');
            } else {
                placeFruit(state.selectedFruit);
            }

            return;
        }

        var group = event.target.closest ? event.target.closest('[data-group]') : null;

        if (group) {
            selectGroup(group);
            return;
        }

        var numberButton = event.target.closest ? event.target.closest('[data-number]') : null;

        if (numberButton) {
            attemptMatch(state.selectedGroup, numberButton);
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

        victoryTitle.textContent = last ? '¡Excelente! Ya sabes contar hasta 10' : '¡Nivel completado!';
        victoryText.textContent = last
            ? 'Contaste, llenaste cestas y uniste números del 1 al 10. ¡Eres un campeón del conteo!'
            : 'Completaste las ' + state.activities.length + ' actividades del nivel. ¡Sigue así!';

        nextButton.hidden = last;
        victory.hidden = false;
        victory.classList.add('is-visible');
        setMessage(last ? '¡Terminaste los diez niveles!' : '¡Nivel completado! Puedes seguir con el siguiente.');
        playWin();

        (last ? replayButton : nextButton).focus({ preventScroll: true });
    }

    function focusStage() {
        var first = stage.querySelector('[data-option], [data-pool-fruit], [data-group]');

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
        state.selectedFruit = null;
        state.selectedGroup = null;

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
        if (state.solved) {
            loadLevel(state.levelIndex);
            return;
        }

        cancelDrag();
        renderActivity();
        setMessage('Actividad reiniciada. ¡Vamos otra vez!');
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

    // La cesta recibe el foco con el teclado: Enter o espacio ponen la fruta
    // seleccionada, igual que un toque en la pantalla táctil.
    stage.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter' && event.key !== ' ') {
            return;
        }

        if (!event.target.hasAttribute('data-basket') || state.selectedFruit === null) {
            return;
        }

        event.preventDefault();
        placeFruit(state.selectedFruit);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && (state.selectedFruit !== null || state.selectedGroup !== null)) {
            clearSelections();
            setMessage('Selección quitada.');
        }
    });

    /* ------------------------------------------------------------------
       Arranque
       ------------------------------------------------------------------ */

    applySoundState();
    loadLevel(0);
})();
