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
   Propuesta de valor: las cuatro ventajas como pestañas.
   La ventaja activa abre su descripción y cambia la imagen, el
   chip y el contador del panel de la derecha. Patrón ARIA: una
   sola pestaña activa y un panel compartido (role="tabpanel").
   ============================================================ */
(function valorEnPestanas() {
    var lista = document.querySelector('.ara-valor__lista');

    if (!lista) {
        return;
    }

    var tabs = Array.prototype.slice.call(lista.querySelectorAll('[role="tab"]'));
    var panel = document.getElementById('valor-panel');

    if (!tabs.length || !panel) {
        return;
    }

    var imagenes = Array.prototype.slice.call(panel.querySelectorAll('img[data-valor]'));
    var chipNumero = document.getElementById('valor-chip-num');
    var chipTexto = document.getElementById('valor-chip-texto');
    var contador = document.getElementById('valor-actual');

    function dosDigitos(numero) {
        return (numero < 10 ? '0' : '') + numero;
    }

    function activar(indice, enfocar) {
        tabs.forEach(function (tab, posicion) {
            var activa = posicion === indice;
            tab.setAttribute('aria-selected', activa ? 'true' : 'false');
            tab.tabIndex = activa ? 0 : -1;
        });

        imagenes.forEach(function (imagen, posicion) {
            var activa = posicion === indice;
            imagen.classList.toggle('is-activa', activa);

            // La imagen oculta no se anuncia; la visible sí.
            if (activa) {
                imagen.removeAttribute('aria-hidden');
            } else {
                imagen.setAttribute('aria-hidden', 'true');
            }
        });

        // El panel anuncia qué ventaja está mostrando.
        panel.setAttribute('aria-labelledby', tabs[indice].id);

        if (chipNumero) {
            chipNumero.textContent = dosDigitos(indice + 1);
        }

        if (chipTexto) {
            chipTexto.textContent = tabs[indice].getAttribute('data-chip') || '';
        }

        if (contador) {
            contador.textContent = dosDigitos(indice + 1);
        }

        if (enfocar) {
            tabs[indice].focus();
        }
    }

    tabs.forEach(function (tab, indice) {
        tab.addEventListener('click', function () {
            activar(indice, false);
        });

        tab.addEventListener('keydown', function (evento) {
            var destino = null;

            if (evento.key === 'ArrowDown' || evento.key === 'ArrowRight') {
                destino = (indice + 1) % tabs.length;
            } else if (evento.key === 'ArrowUp' || evento.key === 'ArrowLeft') {
                destino = (indice - 1 + tabs.length) % tabs.length;
            } else if (evento.key === 'Home') {
                destino = 0;
            } else if (evento.key === 'End') {
                destino = tabs.length - 1;
            }

            if (destino === null) {
                return;
            }

            // Sin `preventDefault` la flecha haría scroll de la página.
            evento.preventDefault();
            activar(destino, true);
        });
    });
})();
