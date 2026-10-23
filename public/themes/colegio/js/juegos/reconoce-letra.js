/**
 * Aula Virtual · Zona de juegos — Reconoce la letra.
 *
 * La partida vive entera en el navegador. Se puede responder con el teclado
 * (físico o en pantalla) y, si el navegador lo permite, con la voz mediante
 * la Web Speech API. Ninguna respuesta se envía al servidor.
 *
 * Estructura:
 *   - ALPHABET / NAMES: abecedario español y nombres aceptados por letra.
 *   - LEVELS: progresión de diez niveles; cada uno define su repertorio.
 *   - parseSpoken / checkSpoken: normalización y comparación de la voz.
 *   - La interfaz se dibuja en [data-stage] y se actualiza con delegación.
 *
 * Para agregar un nivel basta con sumar una entrada a LEVELS. Para ampliar
 * las formas de decir una letra, se agrega el alias a NAMES.
 *
 * Se exponen helpers puros en window.ColegioLetras para poder verificar la
 * validación oral sin micrófono y reutilizarla en el futuro.
 */
(function () {
    'use strict';

    var root = document.querySelector('[data-juego="reconoce-letra"]');

    if (!root) {
        return;
    }

    /* ------------------------------------------------------------------
       Abecedario y nombres de las letras
       ------------------------------------------------------------------ */

    var ALPHABET = 'ABCDEFGHIJKLMNÑOPQRSTUVWXYZ'.split('');
    var VOWELS = ['A', 'E', 'I', 'O', 'U'];

    var NAMES = {
        A: { name: 'a', aliases: ['a'] },
        B: { name: 'be', aliases: ['be', 'be alta', 'be larga', 'b'] },
        C: { name: 'ce', aliases: ['ce', 'c'] },
        D: { name: 'de', aliases: ['de', 'd'] },
        E: { name: 'e', aliases: ['e'] },
        F: { name: 'efe', aliases: ['efe', 'f'] },
        G: { name: 'ge', aliases: ['ge', 'g'] },
        H: { name: 'hache', aliases: ['hache', 'h'] },
        I: { name: 'i', aliases: ['i', 'i latina'] },
        J: { name: 'jota', aliases: ['jota', 'j'] },
        K: { name: 'ka', aliases: ['ka', 'k'] },
        L: { name: 'ele', aliases: ['ele', 'l'] },
        M: { name: 'eme', aliases: ['eme', 'm'] },
        N: { name: 'ene', aliases: ['ene', 'n'] },
        'Ñ': { name: 'eñe', aliases: ['eñe', 'enie', 'ñ'] },
        O: { name: 'o', aliases: ['o'] },
        P: { name: 'pe', aliases: ['pe', 'p'] },
        Q: { name: 'cu', aliases: ['cu', 'q'] },
        R: { name: 'erre', aliases: ['erre', 'ere', 'r'] },
        S: { name: 'ese', aliases: ['ese', 's'] },
        T: { name: 'te', aliases: ['te', 't'] },
        U: { name: 'u', aliases: ['u'] },
        V: { name: 'uve', aliases: ['uve', 've', 've baja', 've corta', 'v'] },
        W: { name: 'uve doble', aliases: ['uve doble', 've doble', 'doble u', 'doble ve', 'w'] },
        X: { name: 'equis', aliases: ['equis', 'x'] },
        Y: { name: 'ye', aliases: ['ye', 'i griega', 'y'] },
        Z: { name: 'zeta', aliases: ['zeta', 'ceta', 'z'] }
    };

    /* Letras que el reconocimiento del navegador suele confundir entre sí. */
    var CONFUSABLE = {
        B: ['V'],
        V: ['B'],
        C: ['S', 'Z'],
        S: ['C', 'Z'],
        Z: ['C', 'S'],
        G: ['J'],
        J: ['G'],
        I: ['Y'],
        Y: ['I'],
        N: ['Ñ'],
        'Ñ': ['N']
    };

    var LEVELS = [
        {
            id: '1',
            name: 'Mis primeras letras',
            focus: 'Las vocales · A E I O U',
            hint: 'Mira cada vocal y di su nombre. Si el micrófono no está disponible, escríbela con el teclado.',
            pool: VOWELS.slice(),
            rounds: 5,
            lengths: [1],
        },
        {
            id: '2',
            name: 'Letras conocidas',
            focus: 'M P S L T y las vocales',
            hint: 'Estas letras se parecen a las de tu nombre. Dilo despacio y con calma.',
            pool: VOWELS.concat(['M', 'P', 'S', 'L', 'T']),
            rounds: 4,
            lengths: [1],
        },
        {
            id: '3',
            name: 'Más letras',
            focus: 'B D F N R',
            hint: 'Ahora aparecen letras nuevas. Puedes usar la voz o el teclado cuando quieras.',
            pool: VOWELS.concat(['M', 'P', 'S', 'L', 'T', 'B', 'D', 'F', 'N', 'R']),
            rounds: 4,
            lengths: [1],
        },
        {
            id: '4',
            name: 'Exploradores del abecedario',
            focus: 'C G H J Ñ',
            hint: 'La Ñ es una letra del abecedario español. Mira bien cada letra antes de responder.',
            pool: VOWELS.concat(['M', 'P', 'S', 'L', 'T', 'B', 'D', 'F', 'N', 'R', 'C', 'G', 'H', 'J', 'Ñ']),
            rounds: 4,
            lengths: [1],
        },
        {
            id: '5',
            name: 'Letras mezcladas',
            focus: 'Vocales y consonantes',
            hint: 'Aparecen letras de los niveles anteriores en distinto orden. Tómate tu tiempo.',
            pool: VOWELS.concat(['M', 'P', 'S', 'L', 'T', 'B', 'D', 'F', 'N', 'R', 'C', 'G', 'H', 'J', 'Ñ']),
            rounds: 5,
            lengths: [1],
        },
        {
            id: '6',
            name: 'Letras especiales',
            focus: 'Ñ Q V X Y',
            hint: 'Recuerda: CH y LL son combinaciones de dos letras, no letras del abecedario. Aquí practicamos las letras que más cuestan.',
            pool: ['Ñ', 'Q', 'V', 'X', 'Y', 'C', 'G', 'H', 'J', 'R'],
            rounds: 5,
            lengths: [1],
        },
        {
            id: '7',
            name: 'Repasamos el abecedario',
            focus: 'Las 27 letras',
            hint: 'Todo el abecedario español: 27 letras, desde la A hasta la Z, con la Ñ en su lugar.',
            pool: ALPHABET.slice(),
            rounds: 7,
            lengths: [1],
        },
        {
            id: '8',
            name: 'Dos letras',
            focus: 'Dos letras en orden',
            hint: 'Ahora hay dos letras. Diles o escríbelas en el mismo orden: primero la de la izquierda.',
            pool: ALPHABET.slice(),
            rounds: 3,
            lengths: [2],
        },
        {
            id: '9',
            name: 'Reto de letras',
            focus: 'Secuencias de 2 a 4',
            hint: 'Aparecen hasta cuatro letras. Respira, míralas de izquierda a derecha y dilas en orden.',
            pool: ALPHABET.slice(),
            rounds: 4,
            lengths: [2, 3, 4],
            alternate: true,
        },
        {
            id: '10',
            name: '¡Campeones del abecedario!',
            focus: 'Letras y secuencias',
            hint: 'El último desafío mezcla letras sueltas y secuencias cortas de todo el abecedario.',
            pool: ALPHABET.slice(),
            rounds: 6,
            lengths: [1, 2, 3],
        },
    ];

    var STORAGE_SOUND = 'colegio.letras.sonido';
    var STORAGE_LEVELS = 'colegio.letras.niveles';
    var STORAGE_MODE = 'colegio.letras.metodo';
    var ADVANCE_DELAY = 1250;
    var LISTEN_TIMEOUT = 10000;

    var SpeechRecognitionCtor = window.SpeechRecognition || window.webkitSpeechRecognition;

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

    /* ------------------------------------------------------------------
       Estado de la partida
       ------------------------------------------------------------------ */

    var state = {
        levelIndex: 0,
        activities: [],
        index: 0,
        completed: 0,
        started: false,
        solved: false,
        locked: false,
        mode: 'teclado',
        typed: [],
        listening: false,
    };

    var dragTimer = 0;
    var advanceTimer = 0;
    var speechSession = 0;
    var voiceTimer = 0;
    var recognition = null;
    var voiceNotice = '';
    var voiceTone = '';
    var audioContext = null;
    var soundOn = readSound();

    /* ------------------------------------------------------------------
       Utilidades generales
       ------------------------------------------------------------------ */

    function current() {
        return state.activities[state.index] || null;
    }

    function isVowel(letter) {
        return VOWELS.indexOf(letter) !== -1;
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

    function setMessage(text) {
        messageBox.textContent = text;
    }

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

    function readMode() {
        try {
            var saved = window.localStorage.getItem(STORAGE_MODE);
            return saved === 'voz' || saved === 'teclado' ? saved : null;
        } catch (error) {
            return null;
        }
    }

    function saveMode() {
        try {
            window.localStorage.setItem(STORAGE_MODE, state.mode);
        } catch (error) {
            // La preferencia vale solo para esta visita si no hay almacenamiento.
        }
    }

    /* ------------------------------------------------------------------
       Sonidos breves (sin archivos externos)
       ------------------------------------------------------------------ */

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
        tone(540, 0, 0.08, 'sine');
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
       Voz: normalización y comparación
       ------------------------------------------------------------------ */

    function normalize(value) {
        return String(value || '')
            .toLowerCase()
            .replace(/[.,;:!?¿¡"'()[\]{}<>/\\|@#$%^&*_+=~`–—-]/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    /**
     * Quita tildes y diéresis sin perder la ñ (la tilde de la ñ es parte de
     * la letra y no un acento que se pueda ignorar).
     */
    function fold(value) {
        return value
            .replace(/ñ/g, '\u0001')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/\u0001/g, 'ñ');
    }

    function keyOf(value) {
        return fold(normalize(value));
    }

    var SPOKEN = {};

    Object.keys(NAMES).forEach(function (letter) {
        NAMES[letter].aliases.forEach(function (alias) {
            SPOKEN[keyOf(alias)] = letter;
        });
    });

    function tokenize(text) {
        var tokens = keyOf(text).split(' ').filter(Boolean);

        // Artículos y la palabra «letra» no aportan y sí aparecen en frases
        // como «la letra a». Se descartan antes de comparar.
        tokens = tokens.filter(function (token) {
            return ['la', 'el', 'las', 'los', 'letra', 'letras'].indexOf(token) === -1;
        });

        while (tokens.length && (tokens[0] === 'es' || tokens[0] === 'son')) {
            tokens.shift();
        }

        return tokens;
    }

    function parseTokens(tokens, expected) {
        if (!tokens.length) {
            return null;
        }

        var letters = [];

        for (var i = 0; i < tokens.length;) {
            // En una secuencia, «y» puede ser la conjunción («a y eme») y no
            // la letra Y. Solo se lee como letra si es la que toca.
            if (tokens[i] === 'y' && expected &&
                (letters.length >= expected.length || expected[letters.length] !== 'Y')) {
                i += 1;
                continue;
            }

            var matched = false;

            for (var size = 3; size >= 1; size -= 1) {
                if (i + size > tokens.length) {
                    continue;
                }

                var phrase = tokens.slice(i, i + size).join(' ');

                if (Object.prototype.hasOwnProperty.call(SPOKEN, phrase)) {
                    letters.push(SPOKEN[phrase]);
                    i += size;
                    matched = true;
                    break;
                }
            }

            if (!matched) {
                return null;
            }
        }

        return letters.length ? letters : null;
    }

    function parseSpoken(text) {
        return parseTokens(tokenize(text), null);
    }

    function normalizedExpected(expected) {
        if (Array.isArray(expected)) {
            return expected.map(function (letter) {
                return String(letter).toUpperCase();
            });
        }

        return String(expected || '').toUpperCase().split('').filter(function (letter) {
            return ALPHABET.indexOf(letter) !== -1;
        });
    }

    function isConfusable(expected, received) {
        return !!CONFUSABLE[expected] && CONFUSABLE[expected].indexOf(received) !== -1;
    }

    /**
     * Compara la transcripción con las letras esperadas.
     *
     * @returns {{status: 'correct'|'incorrect'|'unclear'|'ambiguous', letters: string[]}}
     */
    function checkSpoken(transcript, expected) {
        var target = normalizedExpected(expected);
        var tokens = tokenize(transcript);
        var parsed = parseTokens(tokens, target.length > 1 ? target : null);

        if (!parsed || !parsed.length) {
            return { status: 'unclear', letters: [] };
        }

        if (parsed.join('') === target.join('')) {
            return { status: 'correct', letters: parsed };
        }

        if (target.length > 1 && parsed.length !== target.length) {
            return { status: 'unclear', letters: parsed };
        }

        if (target.length === 1 && parsed.length === 1 && isConfusable(target[0], parsed[0])) {
            return { status: 'ambiguous', letters: parsed };
        }

        return { status: 'incorrect', letters: parsed };
    }

    /* ------------------------------------------------------------------
       Niveles y generación de ejercicios
       ------------------------------------------------------------------ */

    function makeSequence(pool, length, alternate) {
        var chosen = [];
        var used = {};
        var startVowel = Math.random() < 0.5;

        for (var i = 0; i < length; i += 1) {
            var wantVowel = alternate ? ((i % 2 === 0) === startVowel) : null;

            var candidates = pool.filter(function (letter) {
                if (used[letter]) {
                    return false;
                }

                return wantVowel === null || isVowel(letter) === wantVowel;
            });

            if (!candidates.length) {
                candidates = pool.filter(function (letter) {
                    return !used[letter];
                });
            }

            if (!candidates.length) {
                candidates = pool.slice();
            }

            var picked = candidates[Math.floor(Math.random() * candidates.length)];
            chosen.push(picked);
            used[picked] = true;
        }

        return chosen;
    }

    function buildActivities(level) {
        var activities = [];
        var deck = shuffle(level.pool.slice());
        var previous = null;

        for (var round = 0; round < level.rounds; round += 1) {
            var length = level.lengths[round % level.lengths.length];
            var letters;

            if (length === 1) {
                if (!deck.length) {
                    deck = shuffle(level.pool.slice());
                }

                var letter = deck.pop();

                if (previous && previous.length === 1 && previous[0] === letter && deck.length) {
                    deck.unshift(letter);
                    letter = deck.pop();
                }

                letters = [letter];
            } else {
                var attempts = 0;

                do {
                    letters = makeSequence(level.pool, length, !!level.alternate);
                    attempts += 1;
                } while (previous && letters.join('') === previous.join('') && attempts < 12);
            }

            activities.push({ letters: letters });
            previous = letters;
        }

        return activities;
    }

    /* ------------------------------------------------------------------
       Progreso
       ------------------------------------------------------------------ */

    function updateProgress() {
        var total = state.activities.length || 1;
        var value = (state.completed + (state.locked ? 1 : 0)) / total;
        value = Math.max(0, Math.min(1, value));

        progressFill.style.width = Math.round(value * 100) + '%';
        progressBar.setAttribute('aria-valuenow', String(Math.round(value * 100)));

        var shown = Math.min(state.index + 1, total);
        activityCurrent.textContent = String(shown);
        activityTotal.textContent = String(total);
        activityLabel.textContent = 'Actividad ' + shown + ' de ' + total;
    }

    /* ------------------------------------------------------------------
       Interfaz del ejercicio
       ------------------------------------------------------------------ */

    function speechUnavailableReason() {
        if (!SpeechRecognitionCtor) {
            return 'Este navegador no puede escuchar el micrófono. Puedes jugar con el teclado.';
        }

        if (window.isSecureContext === false) {
            return 'El micrófono necesita una conexión segura (HTTPS). Mientras tanto, juega con el teclado.';
        }

        return '';
    }

    function supportsSpeech() {
        return speechUnavailableReason() === '';
    }

    function promptText(letters) {
        return letters.length > 1
            ? 'Toca «Hablar» y di las letras en orden.'
            : 'Toca «Hablar» y di el nombre de la letra.';
    }

    function keyboardHint(letters) {
        return letters.length > 1
            ? 'Escribe con tu teclado o toca las letras, en el mismo orden.'
            : 'Escribe con tu teclado o toca la letra.';
    }

    function modeHint(letters) {
        return state.mode === 'voz' && supportsSpeech()
            ? promptText(letters)
            : keyboardHint(letters);
    }

    function micIcon() {
        return '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">' +
            '<path stroke-linecap="round" stroke-linejoin="round" d="M12 15a3 3 0 0 0 3-3V6a3 3 0 0 0-6 0v6a3 3 0 0 0 3 3z"/>' +
            '<path stroke-linecap="round" d="M6 11a6 6 0 0 0 12 0M12 17v4M9 21h6"/>' +
        '</svg>';
    }

    function voicePanelHtml(letters) {
        if (!supportsSpeech()) {
            return '<p class="letras-aviso">' + speechUnavailableReason() + '</p>' +
                '<button type="button" class="juego-btn juego-btn--ghost" data-use-keyboard>Jugar con el teclado</button>';
        }

        return '<button type="button" class="letras-mic" data-mic aria-pressed="false">' +
                '<span class="letras-mic__icon">' + micIcon() + '</span>' +
                '<span data-mic-label>Hablar</span>' +
            '</button>' +
            '<p class="letras-estado" data-voice-status role="status">' + promptText(letters) + '</p>' +
            '<button type="button" class="letras-link" data-use-keyboard>Prefiero el teclado</button>';
    }

    function keysHtml() {
        var level = LEVELS[state.levelIndex];
        var pool = ALPHABET.filter(function (letter) {
            return level.pool.indexOf(letter) !== -1;
        });

        return pool.map(function (letter) {
            return '<button type="button" class="letras-key" data-letter="' + letter +
                '" aria-label="Letra ' + letter + '">' + letter + '</button>';
        }).join('');
    }

    function tickerHtml(letters) {
        if (letters.length === 1) {
            return '';
        }

        var slots = '';

        for (var i = 0; i < letters.length; i += 1) {
            slots += '<span class="letra-slot" data-slot="' + i + '"></span>';
        }

        return '<div class="letras-answer" data-answer-group aria-label="Tu respuesta">' + slots + '</div>' +
            '<div class="letras-actions" data-key-actions>' +
                '<button type="button" class="letras-action" data-backspace>Borrar</button>' +
                '<button type="button" class="juego-btn juego-btn--primary letras-check" data-check disabled>Comprobar</button>' +
            '</div>';
    }

    function renderActivity() {
        var activity = current();

        if (!activity) {
            return;
        }

        clearTimeout(advanceTimer);
        clearTimeout(dragTimer);
        stopListening(true);

        state.typed = [];
        state.locked = false;
        voiceNotice = '';
        voiceTone = '';

        var letters = activity.letters;
        var isVoice = state.mode === 'voz';
        var tiles = letters.map(function (letter) {
            return '<span class="letras-tile' + (letters.length > 1 ? ' letras-tile--small' : '') + '">' +
                letter + '</span>';
        }).join('');

        var label = letters.length > 1
            ? 'Letras ' + letters.join(', ')
            : 'Letra ' + letters[0];

        stage.innerHTML =
            '<div class="letras-ejercicio">' +
                '<div class="letras-top">' +
                    '<p class="letras-consigna">' + (letters.length > 1 ? '¿Qué letras son?' : '¿Qué letra es?') + '</p>' +
                    '<button type="button" class="letras-repeat" data-repeat>' +
                        '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">' +
                            '<path stroke-linecap="round" stroke-linejoin="round" d="M20 11a8 8 0 1 0-2.3 5.7M20 5v6h-6"/>' +
                        '</svg>' +
                        'Repetir' +
                    '</button>' +
                '</div>' +
                '<div class="letras-display" role="img" aria-label="' + label + '">' + tiles + '</div>' +
                '<p class="letras-orden">' + (letters.length > 1 ? 'Dilas o escríbelas de izquierda a derecha.' : 'Di su nombre o escríbela.') + '</p>' +
                '<div class="letras-metodos">' +
                    '<div class="letras-tabs" role="tablist" aria-label="Forma de responder">' +
                        '<button type="button" class="letras-tab' + (isVoice ? ' is-active' : '') + '" data-method="voz" role="tab" aria-selected="' + (isVoice ? 'true' : 'false') + '">' +
                            micIcon() + 'Voz' +
                        '</button>' +
                        '<button type="button" class="letras-tab' + (!isVoice ? ' is-active' : '') + '" data-method="teclado" role="tab" aria-selected="' + (!isVoice ? 'true' : 'false') + '">' +
                            '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">' +
                                '<rect x="3" y="6" width="18" height="12" rx="2.5"/><path stroke-linecap="round" d="M7 10h.01M11 10h.01M15 10h.01M17 10h.01M7 14h10"/>' +
                            '</svg>' +
                            'Teclado' +
                        '</button>' +
                    '</div>' +
                    '<div class="letras-panel" data-panel="voz" role="tabpanel" aria-label="Responder con la voz"' + (isVoice ? '' : ' hidden') + '>' +
                        voicePanelHtml(letters) +
                    '</div>' +
                    '<div class="letras-panel" data-panel="teclado" role="tabpanel" aria-label="Responder con el teclado"' + (isVoice ? ' hidden' : '') + '>' +
                        tickerHtml(letters) +
                        '<div class="letras-keys" data-keys>' + keysHtml() + '</div>' +
                        '<p class="letras-pista">' + keyboardHint(letters) + '</p>' +
                    '</div>' +
                '</div>' +
            '</div>';

        stage.inert = false;
        updateModeUi();
        renderAnswerSlots();
        updateVoiceUi();
        updateProgress();
        setMessage(modeHint(letters));
    }

    function updateModeUi() {
        stage.querySelectorAll('[data-method]').forEach(function (button) {
            var active = button.getAttribute('data-method') === state.mode;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');

            if (button.getAttribute('data-method') === 'voz' && !supportsSpeech()) {
                button.classList.add('is-unavailable');
                button.setAttribute('title', 'El micrófono no está disponible en este navegador');
            }
        });

        stage.querySelectorAll('[data-panel]').forEach(function (panel) {
            panel.hidden = panel.getAttribute('data-panel') !== state.mode;
        });
    }

    function renderAnswerSlots() {
        var activity = current();
        var group = stage.querySelector('[data-answer-group]');

        if (!activity || !group) {
            return;
        }

        var html = '';

        for (var i = 0; i < activity.letters.length; i += 1) {
            var value = state.typed[i] || '';
            html += '<span class="letra-slot' + (value ? ' is-filled' : '') + '">' + value + '</span>';
        }

        group.innerHTML = html;
        group.setAttribute('aria-label', 'Tu respuesta: ' + (state.typed.join(' ') || 'vacía'));

        var back = stage.querySelector('[data-backspace]');
        var check = stage.querySelector('[data-check]');

        if (back) {
            back.disabled = state.typed.length === 0 || state.locked;
        }

        if (check) {
            check.disabled = state.typed.length !== activity.letters.length || state.locked;
        }
    }

    function setVoiceStatus(text, tone) {
        var status = stage.querySelector('[data-voice-status]');

        if (!status) {
            return;
        }

        status.textContent = text;
        status.classList.toggle('is-error', tone === 'error');
        status.classList.toggle('is-ok', tone === 'ok');
    }

    function updateVoiceUi() {
        var mic = stage.querySelector('[data-mic]');

        if (!mic) {
            return;
        }

        mic.classList.toggle('is-listening', state.listening);
        mic.setAttribute('aria-pressed', state.listening ? 'true' : 'false');
        mic.disabled = state.locked;

        var label = mic.querySelector('[data-mic-label]');

        if (label) {
            label.textContent = state.listening ? 'Detener' : 'Hablar';
        }

        var activity = current();

        if (!activity) {
            return;
        }

        if (state.listening) {
            setVoiceStatus('Te escucho… ' + (activity.letters.length > 1 ? 'di las letras en orden.' : 'di el nombre de la letra.'), '');
        } else if (voiceNotice) {
            setVoiceStatus(voiceNotice, voiceTone);
        } else {
            setVoiceStatus(promptText(activity.letters), '');
        }
    }

    /* ------------------------------------------------------------------
       Sonido de la voz (Web Speech API)
       ------------------------------------------------------------------ */

    function resetRecognition() {
        speechSession += 1;
        clearTimeout(voiceTimer);
        voiceTimer = 0;

        if (recognition) {
            try {
                recognition.abort();
            } catch (error) {
                // Algunos navegadores lanzan si la sesión ya terminó.
            }

            recognition = null;
        }

        state.listening = false;
    }

    function stopListening(silent) {
        var wasListening = state.listening;
        resetRecognition();

        if (!silent && wasListening) {
            voiceNotice = 'Escucha detenida. Toca «Hablar» cuando quieras.';
            voiceTone = '';
            updateVoiceUi();
        } else {
            updateVoiceUi();
        }
    }

    function speechErrorText(code) {
        switch (code) {
            case 'not-allowed':
            case 'service-not-allowed':
                return 'No hay permiso para usar el micrófono. Actívalo en la barra del navegador o juega con el teclado.';
            case 'no-speech':
                return 'No te escuché. Acércate al micrófono y vuelve a intentarlo.';
            case 'audio-capture':
                return 'No encontramos un micrófono. Puedes jugar con el teclado.';
            case 'network':
                return 'El reconocimiento de voz necesita conexión. Prueba otra vez o usa el teclado.';
            case 'aborted':
                return '';
            default:
                return 'No se pudo escuchar la respuesta. Inténtalo otra vez o usa el teclado.';
        }
    }

    function startListening() {
        var activity = current();

        if (!state.started || state.solved || state.locked || state.listening || !activity) {
            return;
        }

        if (!supportsSpeech()) {
            voiceNotice = speechUnavailableReason();
            voiceTone = 'error';
            updateVoiceUi();
            return;
        }

        resetRecognition();
        speechSession += 1;

        var session = speechSession;

        try {
            recognition = new SpeechRecognitionCtor();
        } catch (error) {
            voiceNotice = 'El navegador no pudo abrir el micrófono. Juega con el teclado.';
            voiceTone = 'error';
            updateVoiceUi();
            return;
        }

        recognition.lang = 'es-ES';
        recognition.continuous = false;
        recognition.interimResults = false;
        recognition.maxAlternatives = 4;

        recognition.onstart = function () {
            if (session !== speechSession) {
                return;
            }

            voiceNotice = '';
            updateVoiceUi();
        };

        recognition.onresult = function (event) {
            if (session !== speechSession) {
                return;
            }

            handleSpeechResult(event);
        };

        recognition.onerror = function (event) {
            if (session !== speechSession) {
                return;
            }

            var text = speechErrorText(event.error);
            resetRecognition();

            if (text) {
                voiceNotice = text;
                voiceTone = 'error';
                updateVoiceUi();

                if (event.error === 'audio-capture' || event.error === 'not-allowed') {
                    setMessage(text);
                }
            }
        };

        recognition.onend = function () {
            if (session !== speechSession) {
                return;
            }

            resetRecognition();
            voiceNotice = 'No te escuché ninguna letra. Toca «Hablar» e inténtalo otra vez.';
            voiceTone = 'error';
            updateVoiceUi();
        };

        state.listening = true;
        voiceNotice = '';
        updateVoiceUi();

        try {
            recognition.start();
        } catch (error) {
            resetRecognition();
            voiceNotice = 'No se pudo iniciar la escucha. Inténtalo otra vez o juega con el teclado.';
            voiceTone = 'error';
            updateVoiceUi();
            return;
        }

        voiceTimer = window.setTimeout(function () {
            if (session !== speechSession) {
                return;
            }

            resetRecognition();
            voiceNotice = 'Dejé de escuchar. Toca «Hablar» para intentarlo otra vez.';
            voiceTone = 'error';
            updateVoiceUi();
        }, LISTEN_TIMEOUT);
    }

    function handleSpeechResult(event) {
        var activity = current();

        if (!activity) {
            resetRecognition();
            return;
        }

        var result = event.results && event.results[0];
        var outcomes = [];
        var transcripts = [];

        if (result) {
            for (var i = 0; i < result.length; i += 1) {
                var transcript = result[i].transcript;
                transcripts.push(transcript);
                outcomes.push(checkSpoken(transcript, activity.letters));
            }
        }

        resetRecognition();

        var correct = outcomes.filter(function (outcome) { return outcome.status === 'correct'; })[0];
        var ambiguous = outcomes.filter(function (outcome) { return outcome.status === 'ambiguous'; })[0];
        var incorrect = outcomes.filter(function (outcome) { return outcome.status === 'incorrect'; })[0];

        if (correct) {
            succeed();
            return;
        }

        if (ambiguous) {
            voiceNotice = 'El micrófono puede haber entendido otra letra. Repítelo o usa el teclado.';
            voiceTone = 'error';
            updateVoiceUi();
            setMessage(voiceNotice);
            return;
        }

        if (incorrect) {
            voiceNotice = 'Esa no es. Mira otra vez la letra y vuelve a intentarlo.';
            voiceTone = 'error';
            updateVoiceUi();
            setMessage(voiceNotice);
            markWrong();
            playWrong();
            return;
        }

        voiceNotice = transcripts.length
            ? 'No te escuché bien. Di el nombre de la letra, por ejemplo «a», «eme» o «eñe».'
            : 'No te escuché. Toca «Hablar» e inténtalo otra vez.';
        voiceTone = 'error';
        updateVoiceUi();
        setMessage(voiceNotice);
    }

    /* ------------------------------------------------------------------
       Respuestas
       ------------------------------------------------------------------ */

    function markWrong() {
        var slots = stage.querySelectorAll('.letra-slot');

        slots.forEach(function (slot) {
            slot.classList.add('is-wrong');

            window.setTimeout(function () {
                if (slot.isConnected) {
                    slot.classList.remove('is-wrong');
                }
            }, 650);
        });
    }

    function succeed() {
        if (state.locked) {
            return;
        }

        state.locked = true;
        state.typed = current().letters.slice();
        stopListening(true);
        renderAnswerSlots();
        updateVoiceUi();

        stage.querySelectorAll('.letras-tile').forEach(function (tile) {
            tile.classList.add('is-correct');
        });
        stage.querySelectorAll('.letra-slot').forEach(function (slot) {
            slot.classList.add('is-correct');
        });
        stage.querySelectorAll('[data-letter]').forEach(function (key) {
            key.disabled = true;
        });

        var letters = current().letters;
        var text = letters.length > 1
            ? 'Son las letras ' + letters.join(', ') + '.'
            : 'Es la letra ' + letters[0] + '.';

        setMessage('¡Muy bien! ' + text);
        playCorrect();
        updateProgress();

        advanceTimer = window.setTimeout(advance, ADVANCE_DELAY);
    }

    function validateTyped(attempt) {
        var activity = current();

        if (!activity || state.locked) {
            return;
        }

        if (attempt.join('') === activity.letters.join('')) {
            succeed();
            return;
        }

        if (activity.letters.length > 1) {
            var signature = attempt.join('');
            markWrong();
            playWrong();
            setMessage('Casi. Fíjate en el orden de las letras.');

            dragTimer = window.setTimeout(function () {
                if (!state.locked && state.typed.join('') === signature) {
                    state.typed = [];
                    renderAnswerSlots();
                }
            }, 760);

            return;
        }

        var pressed = stage.querySelector('[data-letter="' + attempt[0] + '"]');

        if (pressed) {
            pressed.classList.add('is-wrong');
            window.setTimeout(function () {
                if (pressed.isConnected) {
                    pressed.classList.remove('is-wrong');
                }
            }, 650);
        }

        markWrong();
        playWrong();
        setMessage('Casi. Vuelve a mirar la letra.');
    }

    function handleLetter(letter) {
        var activity = current();

        if (!activity || !state.started || state.solved || state.locked) {
            return;
        }

        if (state.mode !== 'teclado') {
            setMode('teclado');
        }

        if (activity.letters.length === 1) {
            playPick();
            validateTyped([letter]);
            return;
        }

        if (state.typed.length >= activity.letters.length) {
            setMessage('Ya están todas las letras. Toca «Comprobar» o «Borrar».');
            return;
        }

        state.typed.push(letter);
        renderAnswerSlots();
        playPick();

        if (state.typed.length === activity.letters.length) {
            setMessage('Toca «Comprobar» para revisar tu respuesta.');
        } else {
            setMessage('Sigue con la siguiente letra.');
        }
    }

    function removeLastLetter() {
        if (state.locked || !state.typed.length) {
            return;
        }

        state.typed.pop();
        renderAnswerSlots();
        setMessage('Puedes corregir y volver a escribir.');
    }

    function checkTyped() {
        var activity = current();

        if (!activity || state.locked) {
            return;
        }

        if (state.typed.length !== activity.letters.length) {
            setMessage('Todavía faltan letras por escribir.');
            return;
        }

        validateTyped(state.typed.slice());
    }

    function advance() {
        state.completed += 1;
        state.index += 1;
        state.typed = [];
        state.locked = false;

        if (state.index >= state.activities.length) {
            showVictory();
        } else {
            renderActivity();
        }
    }

    /* ------------------------------------------------------------------
       Modo de respuesta
       ------------------------------------------------------------------ */

    function setMode(mode) {
        if (mode !== 'voz' && mode !== 'teclado') {
            return;
        }

        if (mode === state.mode) {
            updateModeUi();
            return;
        }

        stopListening(true);
        state.mode = mode;
        state.typed = [];
        saveMode();
        updateModeUi();
        renderAnswerSlots();
        updateVoiceUi();

        var activity = current();

        if (!activity) {
            return;
        }

        if (mode === 'voz' && !supportsSpeech()) {
            setMessage('El micrófono no está disponible en este navegador. Puedes jugar con el teclado.');
        } else {
            setMessage(modeHint(activity.letters));
        }
    }

    function focusAnswer() {
        var target = state.mode === 'voz' && supportsSpeech()
            ? stage.querySelector('[data-mic]')
            : stage.querySelector('[data-letter]');

        if (target) {
            target.focus({ preventScroll: true });
        }
    }

    /* ------------------------------------------------------------------
       Niveles, celebración y controles
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
        state.locked = true;
        stopListening(true);
        stage.inert = true;
        markCompleted(level.id);
        renderLevels();
        updateProgress();

        victoryTitle.textContent = last ? '¡Eres campeón del abecedario!' : '¡Nivel completado!';
        victoryText.textContent = last
            ? 'Reconociste letras sueltas y secuencias de las 27 letras. ¡Excelente trabajo!'
            : 'Reconociste todas las letras de este nivel. ¡Sigue así!';

        nextButton.hidden = last;
        victory.hidden = false;
        victory.classList.add('is-visible');
        setMessage(last ? '¡Terminaste los diez niveles!' : '¡Nivel completado! Puedes seguir con el siguiente.');
        playWin();

        (last ? replayButton : nextButton).focus({ preventScroll: true });
    }

    function loadLevel(index, autostart) {
        clearTimeout(advanceTimer);
        clearTimeout(dragTimer);
        stopListening(true);

        state.levelIndex = Math.max(0, Math.min(LEVELS.length - 1, index));
        state.activities = buildActivities(LEVELS[state.levelIndex]);
        state.index = 0;
        state.completed = 0;
        state.locked = false;
        state.solved = false;
        state.started = false;
        state.typed = [];
        voiceNotice = '';
        voiceTone = '';

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

    function startLevel() {
        var activity = current();

        if (!activity) {
            return;
        }

        state.started = true;
        state.solved = false;
        stage.inert = false;
        intro.hidden = true;
        root.classList.add('is-playing');
        setMessage(modeHint(activity.letters));
        focusAnswer();
    }

    function repeatExercise() {
        if (!state.started || state.solved || state.locked) {
            return;
        }

        stopListening(true);
        state.typed = [];
        voiceNotice = '';
        voiceTone = '';
        renderActivity();
        setMessage('Repetimos el ejercicio. ' + modeHint(current().letters));
        focusAnswer();
    }

    /* ------------------------------------------------------------------
       Eventos de la interfaz
       ------------------------------------------------------------------ */

    function onStageClick(event) {
        var target = event.target.closest
            ? event.target.closest('[data-method], [data-mic], [data-use-keyboard], [data-repeat], [data-letter], [data-backspace], [data-check]')
            : null;

        if (!target || !stage.contains(target)) {
            return;
        }

        if (target.hasAttribute('data-method')) {
            setMode(target.getAttribute('data-method'));
            return;
        }

        if (target.hasAttribute('data-mic')) {
            if (state.listening) {
                stopListening(false);
            } else {
                startListening();
            }

            return;
        }

        if (target.hasAttribute('data-use-keyboard')) {
            setMode('teclado');
            var firstKey = stage.querySelector('[data-letter]');

            if (firstKey) {
                firstKey.focus({ preventScroll: true });
            }

            return;
        }

        if (target.hasAttribute('data-repeat')) {
            repeatExercise();
            return;
        }

        if (target.hasAttribute('data-letter')) {
            handleLetter(target.getAttribute('data-letter'));
            return;
        }

        if (target.hasAttribute('data-backspace')) {
            removeLastLetter();
            return;
        }

        if (target.hasAttribute('data-check')) {
            checkTyped();
        }
    }

    function keyToLetter(key) {
        if (!key || key.length !== 1) {
            return null;
        }

        var upper = key.toUpperCase();

        return upper === 'Ñ' || /^[A-Z]$/.test(upper) ? upper : null;
    }

    function onKeyDown(event) {
        if (event.defaultPrevented || event.ctrlKey || event.metaKey || event.altKey || event.repeat) {
            return;
        }

        if (!state.started || state.solved) {
            return;
        }

        if (document.querySelector('dialog[open]')) {
            return;
        }

        var tag = event.target && event.target.tagName;

        if (tag === 'INPUT' || tag === 'TEXTAREA' || (event.target && event.target.isContentEditable)) {
            return;
        }

        var onButton = event.target && event.target.closest && event.target.closest('button');

        if (event.key === 'Backspace' && state.mode === 'teclado' && state.typed.length) {
            event.preventDefault();
            removeLastLetter();
            return;
        }

        if (event.key === 'Escape' && state.mode === 'teclado' && state.typed.length) {
            event.preventDefault();
            state.typed = [];
            renderAnswerSlots();
            setMessage('Respuesta borrada. Vuelve a escribir las letras.');
            return;
        }

        if (event.key === 'Enter') {
            if (onButton || state.mode !== 'teclado') {
                return;
            }

            var activity = current();

            if (activity && state.typed.length === activity.letters.length) {
                event.preventDefault();
                checkTyped();
            }

            return;
        }

        var letter = keyToLetter(event.key);

        if (!letter) {
            return;
        }

        event.preventDefault();
        handleLetter(letter);
    }

    /* ------------------------------------------------------------------
       Arranque
       ------------------------------------------------------------------ */

    startButton.addEventListener('click', startLevel);

    restartButton.addEventListener('click', function () {
        if (state.solved) {
            loadLevel(state.levelIndex);
            return;
        }

        stopListening(true);
        renderActivity();
        setMessage('Ejercicio reiniciado. ' + modeHint(current().letters));
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
    document.addEventListener('keydown', onKeyDown);

    window.addEventListener('pagehide', function () {
        stopListening(true);
    });

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            stopListening(true);
        }
    });

    state.mode = (function () {
        var saved = readMode();

        if (saved === 'teclado') {
            return 'teclado';
        }

        return supportsSpeech() ? 'voz' : 'teclado';
    })();

    // Helpers puros para pruebas y reutilización futura.
    window.ColegioLetras = Object.freeze({
        alphabet: ALPHABET.slice(),
        levels: LEVELS.map(function (level) {
            return {
                id: level.id,
                name: level.name,
                pool: level.pool.slice(),
                rounds: level.rounds,
                lengths: level.lengths.slice(),
            };
        }),
        names: Object.keys(NAMES).reduce(function (map, letter) {
            map[letter] = NAMES[letter].name;
            return map;
        }, {}),
        parseSpoken: parseSpoken,
        checkSpoken: checkSpoken,
        speech: {
            supported: supportsSpeech(),
            secure: window.isSecureContext !== false,
        },
    });

    applySoundState();
    loadLevel(0);
})();
