/* ============================================================
   ARACODE · Evolución UX/UI — Fase 1
   Entrada del titular, botones magnéticos y chips de estadísticas.
   Sin librerías. Respeta «prefers-reduced-motion».
   ============================================================ */
(function () {
    'use strict';

    // ?motion=1 permite revisar la vista completa en equipos con
    // movimiento reducido activado; no se usa en producción.
    var forceMotion = /(?:\?|&)motion=1(?:&|$)/.test(window.location.search);
    var reduce = !forceMotion && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;

    if (forceMotion) {
        document.documentElement.classList.add('ara-motion');
    }

    /* ------------------------------------------------------------
       Titular del hero: entrada palabra por palabra.
       Los tramos con degradado se animan como bloque: si se parten,
       el recorte del degradado deja el texto invisible.
       ------------------------------------------------------------ */
    var title = document.querySelector('.ara-hero h1');

    if (title && !reduce && !title.classList.contains('ara-split')) {
        var original = Array.prototype.slice.call(title.childNodes);
        var fragment = document.createDocumentFragment();
        var index = 0;

        original.forEach(function (node) {
            if (node.nodeType === 3) {
                node.textContent.split(/(\s+)/).forEach(function (token) {
                    if (!token) return;
                    if (/^\s+$/.test(token)) {
                        fragment.appendChild(document.createTextNode(' '));
                        return;
                    }
                    var word = document.createElement('span');
                    word.className = 'ara-w';
                    var inner = document.createElement('i');
                    inner.textContent = token;
                    inner.style.setProperty('--i', index++);
                    word.appendChild(inner);
                    fragment.appendChild(word);
                });
            } else {
                var clone = node.cloneNode(true);
                clone.classList.add('ara-grad');
                fragment.appendChild(clone);
                fragment.appendChild(document.createTextNode(' '));
            }
        });

        title.innerHTML = '';
        title.appendChild(fragment);
        title.classList.add('ara-split');

        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                title.classList.add('is-ready');
            });
        });
    }

    /* ------------------------------------------------------------
       Botones magnéticos (solo puntero fino y sin movimiento reducido)
       ------------------------------------------------------------ */
    if (!reduce && finePointer) {
        document.querySelectorAll('.ara-nav [data-asesoria].ara-btn, .ara-hero [data-asesoria].ara-btn, .ara-cta-evo [data-asesoria].ara-btn').forEach(function (btn) {
            btn.addEventListener('pointermove', function (event) {
                var box = btn.getBoundingClientRect();
                var dx = (event.clientX - (box.left + box.width / 2)) / box.width;
                var dy = (event.clientY - (box.top + box.height / 2)) / box.height;
                btn.style.transform = 'translate(' + (dx * 6).toFixed(2) + 'px,' + (dy * 4).toFixed(2) + 'px)';
            });

            btn.addEventListener('pointerleave', function () {
                btn.style.transform = '';
            });
        });
    }

    /* ------------------------------------------------------------
       Chips de estadísticas: aparición escalonada al entrar en vista
       ------------------------------------------------------------ */
    var stats = document.querySelectorAll('.ara-hero .ara-hero-stat');

    if (stats.length && !reduce) {
        stats.forEach(function (stat, i) {
            stat.style.transitionDelay = (i * 70) + 'ms';
        });
    }

    /* ------------------------------------------------------------
       Fase 2 · Tarjetas de servicio: inclinación suave y halo
       que sigue al puntero (--mx / --my los consume el CSS)
       ------------------------------------------------------------ */
    if (!reduce && finePointer) {
        document.querySelectorAll('[data-tilt]').forEach(function (card) {
            card.addEventListener('pointermove', function (event) {
                var box = card.getBoundingClientRect();
                var x = (event.clientX - box.left) / box.width;
                var y = (event.clientY - box.top) / box.height;

                card.style.setProperty('--mx', (x * 100).toFixed(1) + '%');
                card.style.setProperty('--my', (y * 100).toFixed(1) + '%');
                card.style.transform = 'perspective(900px) rotateX(' +
                    ((0.5 - y) * 5).toFixed(2) + 'deg) rotateY(' +
                    ((x - 0.5) * 6).toFixed(2) + 'deg) translateY(-4px)';
            });

            card.addEventListener('pointerleave', function () {
                card.style.transform = '';
            });
        });
    }

    /* ------------------------------------------------------------
       Fase 2 · Panel de estadísticas: la línea verde se completa
       la primera vez que entra en pantalla
       ------------------------------------------------------------ */
    var statsPanel = document.querySelector('.ara-stats-panel');

    if (statsPanel) {
        if ('IntersectionObserver' in window && !reduce) {
            var panelObserver = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;
                    statsPanel.classList.add('is-in');
                    panelObserver.unobserve(statsPanel);
                });
            }, { threshold: 0.35 });

            panelObserver.observe(statsPanel);
        } else {
            statsPanel.classList.add('is-in');
        }
    }
})();

/* ============================================================
   Barra de progreso de lectura
   Se dibuja en el borde superior de la ventana, por encima de la
   barra flotante, y avanza según el recorrido de la página.
   ============================================================ */
(function barraDeProgreso() {
    var barra = document.getElementById('araScrollProgress');
    if (!barra) return;

    var pendiente = false;

    function pintar() {
        var recorrido = document.documentElement.scrollHeight - window.innerHeight;
        var avance = recorrido > 0 ? Math.min(1, Math.max(0, window.scrollY / recorrido)) : 0;
        barra.style.width = (avance * 100).toFixed(2) + '%';
        barra.classList.toggle('is-active', avance > 0.004);
        pendiente = false;
    }

    function pedir() {
        // Con la pestaña oculta requestAnimationFrame no corre: se pinta al momento.
        if (document.hidden) { pintar(); return; }
        if (pendiente) return;
        pendiente = true;
        requestAnimationFrame(pintar);
    }

    window.addEventListener('scroll', pedir, { passive: true });
    window.addEventListener('resize', pedir, { passive: true });
    pintar();
})();

/* ============================================================
   CTA de portada: el correo abre el modal de asesoría ya escrito.
   Sin modal disponible, el formulario sigue siendo un GET a /contacto.
   ============================================================ */
(function ctaDePortada() {
    var formulario = document.getElementById('ctaEmail');
    if (!formulario) return;

    var campo = formulario.querySelector('input[type="email"]');
    var boton = formulario.querySelector('button[type="submit"]');
    var modal = document.getElementById('asesoriaModal');
    var destino = document.getElementById('asesoriaEmail');

    // Copia el correo al modal solo si es válido.
    function precargarCorreo() {
        if (!campo || !destino || !campo.checkValidity()) return false;
        destino.value = campo.value.trim();
        return true;
    }

    // El botón lleva `data-asesoria`, así que el modal intercepta el clic y
    // abre el diálogo: el correo se copia antes de que eso ocurra.
    if (boton) {
        boton.addEventListener('click', precargarCorreo);
    }

    // Enviar con Enter no dispara el clic del botón: el mismo camino, a mano.
    formulario.addEventListener('submit', function (evento) {
        if (!campo || !campo.checkValidity()) {
            if (campo) campo.reportValidity();
            evento.preventDefault();
            return;
        }

        // Sin modal en la página, el GET a /contacto sigue su curso.
        if (!modal || !destino) return;

        evento.preventDefault();
        precargarCorreo();

        if (!modal.hidden) return;

        var disparador = document.querySelector('[data-asesoria]');
        if (!disparador) return;

        disparador.click();
        setTimeout(function () { destino.focus(); }, 80);
    });
})();
