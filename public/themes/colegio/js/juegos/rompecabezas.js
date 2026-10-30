/**
 * Aula Virtual · Zona de juegos — Rompecabezas.
 *
 * La partida vive entera en el navegador: no se envía nada al servidor por
 * cada movimiento. Las piezas se recortan con background-position sobre la
 * ilustración completa, así que cada nivel solo necesita su imagen y su
 * cantidad de columnas y filas.
 *
 * Para agregar un nivel basta con sumar una entrada a LEVELS y su SVG en
 * public/themes/colegio/img/juegos. Los niveles más difíciles también pueden
 * apagar la guía (guide: false) para que las casillas no muestren la pista.
 */
(function () {
    'use strict';

    var root = document.querySelector('[data-juego="rompecabezas"]');

    if (!root) {
        return;
    }

    /* ------------------------------------------------------------------
       Configuración de niveles
       ------------------------------------------------------------------ */

    var LEVELS = [
        {
            id: '1',
            name: 'Mis primeras piezas',
            figure: 'Sol sonriente',
            cols: 2,
            rows: 1,
            image: 'sol.svg',
            guide: true,
            hint: 'Arrastra las dos piezas del sol hasta su lugar. Las casillas te muestran dónde va cada una.',
        },
        {
            id: '2',
            name: 'Pequeños exploradores',
            figure: 'Gato naranja',
            cols: 2,
            rows: 2,
            image: 'gato.svg',
            guide: false,
            hint: 'Cuatro piezas forman este gato. Mira la imagen de referencia y busca la parte que falta.',
        },
        {
            id: '3',
            name: 'Armo y descubro',
            figure: 'Granja',
            cols: 3,
            rows: 2,
            image: 'granja.svg',
            guide: false,
            hint: 'La granja tiene seis piezas: el establo, el árbol, la gallina y el cielo.',
        },
        {
            id: '4',
            name: 'Súper armadores',
            figure: 'Parque',
            cols: 3,
            rows: 3,
            image: 'parque.svg',
            guide: false,
            hint: 'Nueve piezas para el parque. Tómate tu tiempo y mira bien cada detalle.',
        },
    ];

    var STORAGE_SOUND = 'colegio.rompecabezas.sonido';
    var STORAGE_LEVELS = 'colegio.rompecabezas.niveles';
    var DRAG_THRESHOLD = 8;
    var imageBase = (root.getAttribute('data-image-base') || '').replace(/\/+$/, '');
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ------------------------------------------------------------------
       Referencias del DOM
       ------------------------------------------------------------------ */

    var board = root.querySelector('[data-board]');
    var tray = root.querySelector('[data-tray]');
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
    var references = root.querySelectorAll('[data-reference]');

    /* ------------------------------------------------------------------
       Estado de la partida
       ------------------------------------------------------------------ */

    var state = {
        levelIndex: 0,
        placed: 0,
        selected: null,
        started: false,
        solved: false,
    };

    var drag = null;
    var audioContext = null;
    var soundOn = readSound();

    /* ------------------------------------------------------------------
       Utilidades
       ------------------------------------------------------------------ */

    function totalOf(level) {
        return level.cols * level.rows;
    }

    function imageUrl(level) {
        return imageBase + '/' + level.image;
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
            // Sin almacenamiento: el sonido vale solo para esta visita.
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

    function setMessage(text) {
        messageBox.textContent = text;
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
        gain.gain.exponentialRampToValueAtTime(0.16, start + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, start + duration);

        oscillator.connect(gain);
        gain.connect(context.destination);
        oscillator.start(start);
        oscillator.stop(start + duration + 0.03);
    }

    function playCorrect() {
        tone(660, 0, 0.12, 'sine');
        tone(990, 0.1, 0.16, 'sine');
    }

    function playWrong() {
        tone(262, 0, 0.18, 'triangle');
        tone(196, 0.12, 0.22, 'triangle');
    }

    function playWin() {
        [523, 659, 784, 1046].forEach(function (frequency, index) {
            tone(frequency, index * 0.13, 0.2, 'sine');
        });
    }

    function applySoundState() {
        soundOn = !!soundOn;
        soundLabel.textContent = soundOn ? 'Sonido: sí' : 'Sonido: no';
        soundButton.setAttribute('aria-pressed', soundOn ? 'true' : 'false');
        soundButton.classList.toggle('is-off', !soundOn);
    }

    /* ------------------------------------------------------------------
       Pintura de piezas
       ------------------------------------------------------------------ */

    function paintPiece(element, level, index) {
        var column = index % level.cols;
        var row = Math.floor(index / level.cols);
        var x = level.cols > 1 ? (column * 100) / (level.cols - 1) : 0;
        var y = level.rows > 1 ? (row * 100) / (level.rows - 1) : 0;

        element.style.backgroundImage = 'url("' + imageUrl(level) + '")';
        element.style.backgroundSize = (level.cols * 100) + '% ' + (level.rows * 100) + '%';
        element.style.backgroundRepeat = 'no-repeat';
        element.style.backgroundPosition = x + '% ' + y + '%';
    }

    /* ------------------------------------------------------------------
       Construcción del tablero y de la bandeja
       ------------------------------------------------------------------ */

    function buildBoard(level) {
        board.innerHTML = '';
        board.style.setProperty('--cols', String(level.cols));
        board.style.setProperty('--rows', String(level.rows));

        for (var index = 0; index < totalOf(level); index++) {
            var slot = document.createElement('button');
            slot.type = 'button';
            slot.className = 'juego-casilla';
            slot.dataset.slot = String(index);
            slot.setAttribute('aria-label', 'Casilla ' + (index + 1) + ', vacía');
            slot.addEventListener('click', onSlotClick);

            if (level.guide) {
                var guide = document.createElement('span');
                guide.className = 'juego-casilla__guia';
                guide.setAttribute('aria-hidden', 'true');
                paintPiece(guide, level, index);
                slot.appendChild(guide);
                slot.classList.add('juego-casilla--guia');
            }

            board.appendChild(slot);
        }
    }

    function shuffled(total) {
        var order = [];

        for (var index = 0; index < total; index++) {
            order.push(index);
        }

        for (var position = order.length - 1; position > 0; position--) {
            var swap = Math.floor(Math.random() * (position + 1));
            var current = order[position];
            order[position] = order[swap];
            order[swap] = current;
        }

        // Una pieza que ya empieza en su sitio hace el juego demasiado fácil.
        if (order.length > 1 && order.every(function (value, position) { return value === position; })) {
            order.push(order.shift());
        }

        return order;
    }

    function buildTray(level) {
        tray.innerHTML = '';

        var total = totalOf(level);

        shuffled(total).forEach(function (index) {
            var piece = document.createElement('button');
            piece.type = 'button';
            piece.className = 'juego-pieza';
            piece.dataset.piece = String(index);
            piece.setAttribute('aria-pressed', 'false');
            piece.setAttribute('aria-label', 'Pieza ' + (index + 1) + ' de ' + total);
            paintPiece(piece, level, index);

            piece.addEventListener('pointerdown', onPointerDown);
            piece.addEventListener('click', onPieceClick);

            tray.appendChild(piece);
        });
    }

    function renderLevels() {
        levelsBox.innerHTML = '';

        var completed = readCompleted();

        LEVELS.forEach(function (level, index) {
            var button = document.createElement('button');
            var done = completed.indexOf(level.id) !== -1;

            button.type = 'button';
            button.className = 'juego-nivel';
            button.dataset.level = String(index);
            button.innerHTML =
                '<span class="juego-nivel__num">' + (done ? '★' : String(index + 1)) + '</span>' +
                '<span class="juego-nivel__info">' +
                    '<span class="juego-nivel__name">' + level.name + '</span>' +
                    '<span class="juego-nivel__meta">' + totalOf(level) + ' piezas</span>' +
                '</span>';

            if (done) {
                button.classList.add('is-complete');
                button.setAttribute('aria-label', level.name + ', completado');
            }

            if (index === state.levelIndex) {
                button.classList.add('is-active');
                button.setAttribute('aria-current', 'true');
            }

            button.addEventListener('click', function () {
                if (index === state.levelIndex) {
                    return;
                }

                loadLevel(index, false);
            });

            levelsBox.appendChild(button);
        });
    }

    function updateProgress() {
        var level = LEVELS[state.levelIndex];
        var total = totalOf(level);
        var percent = total ? Math.round((state.placed / total) * 100) : 0;

        progressFill.style.width = percent + '%';
        progressBar.setAttribute('aria-valuenow', String(percent));

        root.querySelectorAll('[data-progress-count]').forEach(function (node) {
            node.textContent = String(state.placed);
        });

        root.querySelectorAll('[data-progress-total]').forEach(function (node) {
            node.textContent = String(total);
        });
    }

    /* ------------------------------------------------------------------
       Carga de nivel
       ------------------------------------------------------------------ */

    function loadLevel(index, startNow) {
        var safeIndex = Math.max(0, Math.min(index, LEVELS.length - 1));
        var level = LEVELS[safeIndex];

        state.levelIndex = safeIndex;
        state.placed = 0;
        state.selected = null;
        state.started = false;
        state.solved = false;
        cancelDrag();

        board.innerHTML = '';
        tray.innerHTML = '';
        clearSelection();

        root.classList.remove('is-playing');

        root.querySelectorAll('[data-level-name]').forEach(function (node) {
            node.textContent = level.name;
        });

        root.querySelectorAll('[data-level-figure]').forEach(function (node) {
            node.textContent = level.figure;
        });

        root.querySelectorAll('[data-level-hint]').forEach(function (node) {
            node.textContent = level.hint;
        });

        root.querySelectorAll('[data-level-number]').forEach(function (node) {
            node.textContent = String(safeIndex + 1);
        });

        references.forEach(function (image) {
            image.src = imageUrl(level);
            image.alt = 'Imagen de referencia: ' + level.figure;
        });

        buildBoard(level);
        buildTray(level);
        updateProgress();
        renderLevels();

        victory.hidden = true;
        nextButton.hidden = true;
        intro.hidden = startNow;

        if (startNow) {
            begin();
        } else {
            setMessage('Pulsa «¡Vamos a jugar!» para empezar.');
        }
    }

    function begin() {
        state.started = true;
        intro.hidden = true;
        root.classList.add('is-playing');
        ensureAudio();
        setMessage('Arrastra una pieza a su casilla o toca una pieza y luego la casilla.');
    }

    /* ------------------------------------------------------------------
       Selección y colocación
       ------------------------------------------------------------------ */

    function clearSelection() {
        tray.querySelectorAll('.juego-pieza.is-selected').forEach(function (piece) {
            piece.classList.remove('is-selected');
            piece.setAttribute('aria-pressed', 'false');
        });

        state.selected = null;
    }

    function toggleSelection(piece) {
        if (!state.started || state.solved) {
            return;
        }

        var index = Number(piece.dataset.piece);

        if (state.selected === index) {
            clearSelection();
            setMessage('Pieza guardada otra vez en la bandeja.');
            return;
        }

        clearSelection();
        state.selected = index;
        piece.classList.add('is-selected');
        piece.setAttribute('aria-pressed', 'true');
        setMessage('Ahora toca la casilla donde va esta pieza.');
    }

    function slotFromPoint(x, y) {
        var element = document.elementFromPoint(x, y);

        return element ? element.closest('[data-slot]') : null;
    }

    function attempt(pieceIndex, slotIndex) {
        if (!state.started || state.solved) {
            return;
        }

        var level = LEVELS[state.levelIndex];
        var piece = tray.querySelector('[data-piece="' + pieceIndex + '"]');
        var slot = board.querySelector('[data-slot="' + slotIndex + '"]');

        if (!piece || !slot || slot.classList.contains('is-filled')) {
            return;
        }

        if (pieceIndex === slotIndex) {
            placePiece(piece, slot, level);
        } else {
            rejectPiece(piece, slot);
        }
    }

    function placePiece(piece, slot, level) {
        var index = Number(piece.dataset.piece);
        var placed = document.createElement('span');

        placed.className = 'juego-pieza-colocada';
        placed.setAttribute('aria-hidden', 'true');
        paintPiece(placed, level, index);

        slot.innerHTML = '';
        slot.appendChild(placed);
        slot.classList.add('is-filled');
        slot.disabled = true;
        slot.setAttribute('aria-label', 'Casilla ' + (index + 1) + ', pieza colocada');

        piece.remove();
        clearSelection();

        state.placed += 1;
        updateProgress();
        playCorrect();

        if (state.placed >= totalOf(level)) {
            finish();
            return;
        }

        var messages = ['¡Muy bien!', '¡Una pieza más!', '¡Excelente!', '¡Casi listo!'];
        setMessage(messages[(state.placed - 1) % messages.length]);
    }

    function rejectPiece(piece, slot) {
        piece.classList.remove('is-wrong');
        slot.classList.remove('is-wrong');

        // Reinicia la animación aunque el niño toque dos veces seguidas.
        void piece.offsetWidth;

        piece.classList.add('is-wrong');
        slot.classList.add('is-wrong');

        window.setTimeout(function () {
            piece.classList.remove('is-wrong');
            slot.classList.remove('is-wrong');
        }, 420);

        playWrong();
        setMessage('¡Casi! Esa pieza va en otra casilla. Prueba de nuevo.');
    }

    function finish() {
        var level = LEVELS[state.levelIndex];
        var total = totalOf(level);

        state.solved = true;
        state.selected = null;
        clearSelection();
        markCompleted(level.id);
        renderLevels();

        victoryTitle.textContent = '¡Excelente trabajo!';
        victoryText.textContent = 'Armaste «' + level.figure + '» con ' + total +
            (total === 1 ? ' pieza.' : ' piezas.');

        nextButton.hidden = state.levelIndex >= LEVELS.length - 1;
        victory.hidden = false;
        setMessage('¡Lo lograste! Completaste el rompecabezas.');

        // Con teclado, el foco entra en la celebración en lugar de quedarse en
        // el tablero, que ya está completo.
        (nextButton.hidden ? replayButton : nextButton).focus({ preventScroll: true });

        playWin();

        if (!reduceMotion) {
            victory.classList.remove('is-visible');
            void victory.offsetWidth;
            victory.classList.add('is-visible');
        }
    }

    /* ------------------------------------------------------------------
       Puntero: arrastre con mouse, lápiz o dedo, y toque para seleccionar
       ------------------------------------------------------------------ */

    function createGhost(piece) {
        var ghost = piece.cloneNode(true);

        ghost.className = 'juego-pieza juego-fantasma';
        ghost.removeAttribute('data-piece');
        document.body.appendChild(ghost);

        piece.classList.add('is-dragging');

        return ghost;
    }

    function moveGhost(x, y) {
        if (!drag || !drag.ghost) {
            return;
        }

        drag.ghost.style.left = x + 'px';
        drag.ghost.style.top = y + 'px';
    }

    function highlightSlot(slot) {
        if (!drag) {
            return;
        }

        if (drag.slot && drag.slot !== slot) {
            drag.slot.classList.remove('is-over');
        }

        drag.slot = slot;

        if (slot) {
            slot.classList.add('is-over');
        }
    }

    function onPointerDown(event) {
        if (!state.started || state.solved) {
            return;
        }

        if (event.pointerType === 'mouse' && event.button !== 0) {
            return;
        }

        drag = {
            piece: event.currentTarget,
            pointerId: event.pointerId,
            startX: event.clientX,
            startY: event.clientY,
            moved: false,
            ghost: null,
            slot: null,
        };

        try {
            drag.piece.setPointerCapture(event.pointerId);
        } catch (error) {
            // Sin captura el arrastre sigue funcionando con los eventos del documento.
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
            drag.ghost = createGhost(drag.piece);
        }

        if (event.cancelable) {
            event.preventDefault();
        }

        moveGhost(event.clientX, event.clientY);
        highlightSlot(slotFromPoint(event.clientX, event.clientY));
    }

    function onPointerUp(event) {
        if (!drag || event.pointerId !== drag.pointerId) {
            return;
        }

        document.removeEventListener('pointermove', onPointerMove);
        document.removeEventListener('pointerup', onPointerUp);
        document.removeEventListener('pointercancel', onPointerUp);

        var current = drag;
        drag = null;

        if (current.ghost) {
            current.ghost.remove();
        }

        current.piece.classList.remove('is-dragging');

        if (current.slot) {
            current.slot.classList.remove('is-over');
        }

        if (current.moved) {
            var slot = slotFromPoint(event.clientX, event.clientY);

            if (slot) {
                attempt(Number(current.piece.dataset.piece), Number(slot.dataset.slot));
            } else {
                setMessage('Suelta la pieza sobre una casilla del tablero.');
            }

            return;
        }

        toggleSelection(current.piece);
    }

    /**
     * Corta un arrastre en curso (por ejemplo al reiniciar o cambiar de nivel
     * con otro dedo) y deja limpio el documento y el fantasma.
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

        if (drag.piece && drag.piece.isConnected) {
            drag.piece.classList.remove('is-dragging');
        }

        if (drag.slot) {
            drag.slot.classList.remove('is-over');
        }

        drag = null;
    }

    function onPieceClick(event) {
        // Los punteros ya se resuelven en pointerdown/pointerup; este camino
        // deja jugar con el teclado y con lectores de pantalla.
        if (event.detail === 0) {
            toggleSelection(event.currentTarget);
        }
    }

    function onSlotClick(event) {
        if (!state.started || state.solved) {
            return;
        }

        var slot = event.currentTarget;

        if (slot.classList.contains('is-filled')) {
            return;
        }

        if (state.selected === null) {
            setMessage('Primero toca una pieza de la bandeja.');
            return;
        }

        attempt(state.selected, Number(slot.dataset.slot));
    }

    /* ------------------------------------------------------------------
       Controles
       ------------------------------------------------------------------ */

    startButton.addEventListener('click', begin);

    restartButton.addEventListener('click', function () {
        loadLevel(state.levelIndex, true);
        setMessage('Tablero reiniciado. ¡Vamos otra vez!');
    });

    replayButton.addEventListener('click', function () {
        loadLevel(state.levelIndex, true);
    });

    nextButton.addEventListener('click', function () {
        loadLevel(state.levelIndex + 1, false);
    });

    soundButton.addEventListener('click', function () {
        soundOn = !soundOn;
        saveSound();
        applySoundState();

        if (soundOn) {
            tone(880, 0, 0.12, 'sine');
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && state.selected !== null) {
            clearSelection();
            setMessage('Pieza guardada otra vez en la bandeja.');
        }
    });

    /* ------------------------------------------------------------------
       Arranque
       ------------------------------------------------------------------ */

    applySoundState();
    loadLevel(0, false);
})();
