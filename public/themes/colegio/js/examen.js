/**
 * Examen en línea: bloqueo inicial, cuenta regresiva y navegación de una
 * pregunta a la vez. El tiempo se calcula con la hora de cierre que envía el
 * servidor, así que recargar la página no lo reinicia.
 */
(function () {
    'use strict';

    var lock = document.querySelector('.quiz-lock');

    if (lock) {
        document.body.classList.add('quiz-locked');

        // Evita que un doble clic abra dos intentos seguidos.
        var startForm = lock.querySelector('form');

        if (startForm) {
            startForm.addEventListener('submit', function () {
                var button = startForm.querySelector('button[type="submit"]');

                if (button) {
                    button.disabled = true;
                }
            });
        }

        // Mantiene el foco dentro del aviso: el examen no se puede cerrar.
        lock.addEventListener('keydown', function (event) {
            if (event.key !== 'Tab') {
                return;
            }

            var focusables = lock.querySelectorAll(
                'a[href], button:not([disabled]), input, select, textarea, [tabindex]:not([tabindex="-1"])'
            );

            if (!focusables.length) {
                return;
            }

            var first = focusables[0];
            var last = focusables[focusables.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        });

        return;
    }

    var form = document.querySelector('[data-quiz-runner]');

    if (!form) {
        return;
    }

    var questions = Array.prototype.slice.call(
        form.querySelectorAll('[data-quiz-question]')
    );

    if (!questions.length) {
        return;
    }

    var timeValue = form.querySelector('[data-quiz-time]');
    var timerBox = form.querySelector('[data-quiz-timer]');
    var position = form.querySelector('[data-quiz-position]');
    var counter = form.querySelector('[data-quiz-counter]');
    var progress = form.querySelector('[data-quiz-progress]');
    var previous = form.querySelector('[data-quiz-prev]');
    var next = form.querySelector('[data-quiz-next]');
    var finish = form.querySelector('[data-quiz-finish]');
    var autoInput = form.querySelector('[data-quiz-auto]');
    var confirmBox = form.querySelector('[data-quiz-confirm]');
    var confirmText = form.querySelector('[data-quiz-confirm-text]');
    var confirmAccept = form.querySelector('[data-quiz-accept]');
    var confirmCancel = form.querySelector('[data-quiz-cancel]');

    var deadline = parseDate(form.dataset.expiresAt);
    var current = 0;
    var submitted = false;
    var confirmOpen = false;

    function parseDate(value) {
        if (!value) {
            return null;
        }

        var time = Date.parse(value);

        return isNaN(time) ? null : time;
    }

    function formatTime(seconds) {
        seconds = Math.max(0, Math.floor(seconds));

        var hours = Math.floor(seconds / 3600);
        var minutes = Math.floor((seconds % 3600) / 60);
        var rest = seconds % 60;

        if (hours > 0) {
            return hours + ':' + pad(minutes) + ':' + pad(rest);
        }

        return pad(minutes) + ':' + pad(rest);
    }

    function pad(value) {
        return value < 10 ? '0' + value : String(value);
    }

    function secondsLeft() {
        if (deadline === null) {
            return null;
        }

        return Math.max(0, Math.ceil((deadline - Date.now()) / 1000));
    }

    function updateTimer(submitWhenFinished) {
        if (!timerBox || !timeValue) {
            return;
        }

        var left = secondsLeft();

        if (left === null) {
            timeValue.textContent = 'Sin límite';
            timerBox.classList.remove('is-warning', 'is-danger');
            return;
        }

        timeValue.textContent = formatTime(left);
        timerBox.classList.toggle('is-warning', left > 60 && left <= 300);
        timerBox.classList.toggle('is-danger', left <= 60);

        if (left <= 0 && submitWhenFinished !== false && !submitted) {
            submitNow(true);
        }
    }

    function showQuestion(index) {
        current = Math.max(0, Math.min(index, questions.length - 1));

        questions.forEach(function (question, positionIndex) {
            var active = positionIndex === current;
            question.hidden = !active;

            var number = question.querySelector('[data-quiz-number]');

            if (number) {
                number.textContent = positionIndex + 1;
            }
        });

        if (position) {
            position.textContent = 'Pregunta ' + (current + 1) + ' de ' + questions.length;
        }

        if (counter) {
            counter.textContent = (current + 1) + ' / ' + questions.length;
        }

        if (progress) {
            progress.style.width = ((current + 1) / questions.length * 100) + '%';
        }

        if (previous) {
            previous.disabled = current === 0;
        }

        if (next) {
            next.disabled = current === questions.length - 1;
        }
    }

    function unansweredCount() {
        return questions.filter(function (question) {
            var checked = question.querySelector('input:checked');
            var text = question.querySelector('textarea');

            if (text) {
                return text.value.trim() === '';
            }

            return !checked;
        }).length;
    }

    function openConfirm() {
        if (!confirmBox) {
            submitNow(false);
            return;
        }

        var left = secondsLeft();
        var pending = unansweredCount();
        var message = left === null
            ? 'Todavía puedes seguir respondiendo. Si terminas ahora, ya no podrás cambiar tus respuestas.'
            : 'Aún te ' + (left === 1 ? 'queda' : 'quedan') + ' ' + formatTime(left) +
                ' de examen. Si terminas ahora, ya no podrás cambiar tus respuestas.';

        if (pending > 0) {
            message += ' Tienes ' + pending + ' ' +
                (pending === 1 ? 'pregunta sin responder' : 'preguntas sin responder') + '.';
        }

        if (confirmText) {
            confirmText.textContent = message;
        }

        confirmOpen = true;
        confirmBox.hidden = false;

        if (confirmAccept) {
            confirmAccept.focus();
        }
    }

    function closeConfirm() {
        confirmOpen = false;

        if (confirmBox) {
            confirmBox.hidden = true;
        }

        if (finish) {
            finish.focus();
        }
    }

    function submitNow(automatic) {
        if (submitted) {
            return;
        }

        submitted = true;

        if (autoInput) {
            autoInput.value = automatic ? '1' : '0';
        }

        // Se deshabilitan solo los botones: las respuestas deben viajar en el
        // formulario para que el servidor pueda calificarlas.
        form.querySelectorAll('button').forEach(function (button) {
            button.disabled = true;
        });

        form.submit();
    }

    if (previous) {
        previous.addEventListener('click', function () {
            showQuestion(current - 1);
        });
    }

    if (next) {
        next.addEventListener('click', function () {
            showQuestion(current + 1);
        });
    }

    if (finish) {
        finish.addEventListener('click', function (event) {
            event.preventDefault();

            if (confirmOpen) {
                return;
            }

            if (secondsLeft() === 0) {
                submitNow(false);
                return;
            }

            openConfirm();
        });
    }

    // Enter dentro de una respuesta también pide confirmación. El envío
    // automático usa form.submit(), que no dispara este evento.
    form.addEventListener('submit', function (event) {
        if (submitted) {
            return;
        }

        event.preventDefault();

        if (!confirmOpen) {
            openConfirm();
        }
    });

    if (confirmAccept) {
        confirmAccept.addEventListener('click', function () {
            submitNow(false);
        });
    }

    if (confirmCancel) {
        confirmCancel.addEventListener('click', closeConfirm);
    }

    if (confirmBox) {
        confirmBox.addEventListener('click', function (event) {
            if (event.target === confirmBox) {
                closeConfirm();
            }
        });
    }

    document.addEventListener('keydown', function (event) {
        if (confirmOpen && event.key === 'Escape') {
            closeConfirm();
            return;
        }

        if (confirmOpen || submitted || !deadline || secondsLeft() <= 0) {
            return;
        }

        var tag = (document.activeElement && document.activeElement.tagName) || '';

        if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') {
            return;
        }

        if (event.key === 'ArrowRight') {
            showQuestion(current + 1);
        } else if (event.key === 'ArrowLeft') {
            showQuestion(current - 1);
        }
    });

    window.addEventListener('beforeunload', function (event) {
        if (submitted || !deadline || secondsLeft() <= 0) {
            return;
        }

        event.preventDefault();
        event.returnValue = '';
    });

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            updateTimer(!submitted);
        }
    });

    form.classList.add('is-ready');
    showQuestion(0);
    updateTimer(!submitted);

    if (deadline !== null) {
        window.setInterval(function () {
            updateTimer(!submitted);
        }, 1000);
    }
})();
