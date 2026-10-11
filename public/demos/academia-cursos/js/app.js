/*
   =============================================================================
   Academia Horizonte — comportamiento de la demostración
   =============================================================================

   JavaScript propio, sin librerías, compartido por las cuatro páginas del
   sitio. Cada bloque se activa solo si su elemento existe en la página, así que
   el mismo archivo sirve para el inicio, el catálogo, la ficha y la matrícula.

   Hace seis cosas:

     1. Menú desplegable de móvil.
     2. Aparición de bloques al hacer scroll.
     3. Contadores de las cifras.
     4. Sombra de la cabecera al bajar.
     5. Filtros del catálogo de cursos (buscador, área y modalidad).
     6. Validación del formulario de inscripción.

   Sin JavaScript la página se lee completa: los bloques aparecen porque el
   script es el que los oculta, y el catálogo muestra todos los cursos.

   El formulario NO envía datos: es una demostración y lo dice al enviarlo.
*/

(function () {
    'use strict';

    document.documentElement.classList.add('js');

    var movimientoReducido = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ---------------------------------------------------------------------
       1. Menú de móvil
       --------------------------------------------------------------------- */

    var botonMenu = document.getElementById('menu-boton');
    var menu = document.getElementById('menu-principal');

    function abrirMenu(abierto) {
        if (!menu || !botonMenu) {
            return;
        }

        menu.classList.toggle('menu--abierto', abierto);
        botonMenu.setAttribute('aria-expanded', abierto ? 'true' : 'false');
    }

    if (botonMenu && menu) {
        botonMenu.addEventListener('click', function () {
            abrirMenu(!menu.classList.contains('menu--abierto'));
        });

        menu.addEventListener('click', function (evento) {
            if (evento.target.closest('a')) {
                abrirMenu(false);
            }
        });

        document.addEventListener('keydown', function (evento) {
            if (evento.key === 'Escape') {
                abrirMenu(false);
            }
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth > 940) {
                abrirMenu(false);
            }
        });
    }

    /* ---------------------------------------------------------------------
       2. Aparición al hacer scroll
       --------------------------------------------------------------------- */

    var bloques = document.querySelectorAll('.revelar');

    if (!movimientoReducido && 'IntersectionObserver' in window) {
        var observador = new IntersectionObserver(function (entradas) {
            entradas.forEach(function (entrada) {
                if (entrada.isIntersecting) {
                    entrada.target.classList.add('visible');
                    observador.unobserve(entrada.target);
                }
            });
        }, { rootMargin: '0px 0px -10% 0px', threshold: 0.12 });

        bloques.forEach(function (bloque, indice) {
            bloque.style.transitionDelay = Math.min(indice * 60, 240) + 'ms';
            observador.observe(bloque);
        });
    } else {
        bloques.forEach(function (bloque) {
            bloque.classList.add('visible');
        });
    }

    /* ---------------------------------------------------------------------
       3. Contadores
       --------------------------------------------------------------------- */

    function valorFinal(elemento) {
        var objetivo = parseFloat(elemento.dataset.contador) || 0;
        var divisor = parseFloat(elemento.dataset.divisor) || 1;

        return divisor > 1
            ? (objetivo / divisor).toFixed(1)
            : Math.round(objetivo).toLocaleString('es-PE');
    }

    function animarContador(elemento) {
        var objetivo = parseFloat(elemento.dataset.contador) || 0;
        var prefijo = elemento.dataset.prefijo || '';
        var sufijo = elemento.dataset.sufijo || '';
        var divisor = parseFloat(elemento.dataset.divisor) || 1;
        var duracion = 1400;
        var inicio = null;

        function paso(marca) {
            if (inicio === null) {
                inicio = marca;
            }

            var avance = Math.min((marca - inicio) / duracion, 1);
            var suave = 1 - Math.pow(1 - avance, 3);
            var valor = (objetivo * suave) / divisor;

            elemento.textContent = prefijo
                + (divisor > 1 ? valor.toFixed(1) : Math.round(valor).toLocaleString('es-PE'))
                + sufijo;

            if (avance < 1) {
                window.requestAnimationFrame(paso);
            }
        }

        window.requestAnimationFrame(paso);
    }

    var contadores = document.querySelectorAll('[data-contador]');

    if (contadores.length) {
        if (movimientoReducido || !('IntersectionObserver' in window)) {
            contadores.forEach(function (elemento) {
                elemento.textContent = (elemento.dataset.prefijo || '') + valorFinal(elemento) + (elemento.dataset.sufijo || '');
            });
        } else {
            var observadorContadores = new IntersectionObserver(function (entradas) {
                entradas.forEach(function (entrada) {
                    if (entrada.isIntersecting) {
                        animarContador(entrada.target);
                        observadorContadores.unobserve(entrada.target);
                    }
                });
            }, { threshold: 0.5 });

            contadores.forEach(function (elemento) {
                observadorContadores.observe(elemento);
            });
        }
    }

    /* ---------------------------------------------------------------------
       4. Cabecera pegada
       --------------------------------------------------------------------- */

    var cabecera = document.getElementById('cabecera');

    if (cabecera) {
        var sombra = function () {
            cabecera.classList.toggle('cabecera--pegada', window.scrollY > 12);
        };

        sombra();
        window.addEventListener('scroll', sombra, { passive: true });
    }

    /* ---------------------------------------------------------------------
       5. Filtros del catálogo
       --------------------------------------------------------------------- */

    var listaCursos = document.getElementById('lista-cursos');

    if (listaCursos) {
        var buscador = document.getElementById('filtro-texto');
        var filtroArea = document.getElementById('filtro-area');
        var filtroModalidad = document.getElementById('filtro-modalidad');
        var resumen = document.getElementById('filtro-resumen');
        var vacio = document.getElementById('cursos-vacio');
        var cursos = [].slice.call(listaCursos.querySelectorAll('.curso'));

        // El texto buscable se arma una vez: nombre, resumen y datos del curso.
        var textoDe = {};

        cursos.forEach(function (curso) {
            textoDe[curso.dataset.slug] = curso.textContent.toLowerCase();
        });

        function filtrar() {
            var texto = (buscador && buscador.value ? buscador.value : '').trim().toLowerCase();
            var area = filtroArea ? filtroArea.value : '';
            var modalidad = filtroModalidad ? filtroModalidad.value : '';
            var visibles = 0;

            cursos.forEach(function (curso) {
                var coincide = (texto === '' || textoDe[curso.dataset.slug].indexOf(texto) !== -1)
                    && (area === '' || curso.dataset.area === area)
                    && (modalidad === '' || curso.dataset.modalidad === modalidad);

                curso.hidden = !coincide;

                if (coincide) {
                    visibles++;
                }
            });

            if (resumen) {
                resumen.textContent = visibles === 1
                    ? 'Un curso encontrado'
                    : visibles + ' cursos encontrados';
            }

            if (vacio) {
                vacio.hidden = visibles !== 0;
            }
        }

        [buscador, filtroArea, filtroModalidad].forEach(function (campo) {
            if (campo) {
                campo.addEventListener(campo.tagName === 'INPUT' ? 'input' : 'change', filtrar);
            }
        });

        // El buscador del catálogo acepta el formato «?area=tecnologia» o
        // «?q=excel» para que un enlace desde otra página llegue ya filtrado.
        var parametros = new URLSearchParams(window.location.search);
        var areaInicial = parametros.get('area');
        var textoInicial = parametros.get('q');

        if (areaInicial && filtroArea) {
            filtroArea.value = areaInicial;
        }

        if (textoInicial && buscador) {
            buscador.value = textoInicial;
        }

        filtrar();
    }

    /* ---------------------------------------------------------------------
       6. Formulario de inscripción
       --------------------------------------------------------------------- */

    var formulario = document.getElementById('formulario-matricula');

    if (formulario) {
        var aviso = document.getElementById('aviso-formulario');

        // Si se llega desde la ficha de un curso (`matricula.html?curso=…`), ese
        // curso aparece ya elegido en el formulario: el visitante no tiene que
        // volver a buscarlo en la lista. El parámetro se limpia antes de usarlo
        // como selector.
        var cursoPedido = (new URLSearchParams(window.location.search).get('curso') || '').replace(/[^a-z0-9-]/gi, '');

        if (cursoPedido && formulario.curso.querySelector('option[value="' + cursoPedido + '"]')) {
            formulario.curso.value = cursoPedido;
        }

        function marcarError(campo, conError) {
            var contenedor = campo.closest('.campo') || campo.closest('.casilla') || campo.closest('.grupo-radio');

            if (!contenedor) {
                return;
            }

            // Un grupo de radios sin marcar no llega como radio sino como su
            // fieldset (no hay ningún input al que marcar): se marca el grupo,
            // que es el elemento que lleva el aviso al lado.
            var esCasilla = campo.type === 'checkbox';
            var esGrupo = campo.tagName === 'FIELDSET' || campo.type === 'radio';

            contenedor.classList.toggle('campo--error', conError && !esCasilla && !esGrupo);
            contenedor.classList.toggle('casilla--error', conError && esCasilla);
            contenedor.classList.toggle('grupo-radio--error', conError && esGrupo);

            if (conError) {
                campo.setAttribute('aria-invalid', 'true');
            } else {
                campo.removeAttribute('aria-invalid');
            }
        }

        function validar() {
            var correo = formulario.correo.value.trim();
            var opcionModalidad = formulario.querySelector('input[name="modalidad"]');
            var modalidad = formulario.querySelector('input[name="modalidad"]:checked');
            var revisiones = [
                { campo: formulario.nombre, ok: function () { return formulario.nombre.value.trim().length >= 5; } },
                { campo: formulario.correo, ok: function () { return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(correo); } },
                { campo: formulario.telefono, ok: function () { return formulario.telefono.value.replace(/\D/g, '').length === 9; } },
                { campo: formulario.curso, ok: function () { return formulario.curso.value !== ''; } },
                {
                    // Si no hay ninguna marcada se señala el grupo completo: es
                    // lo que agrupa la pregunta y lleva su mensaje al costado.
                    campo: modalidad || (opcionModalidad ? opcionModalidad.closest('.grupo-radio') : null),
                    ok: function () {
                        return !!modalidad;
                    }
                },
                { campo: formulario.acepto, ok: function () { return formulario.acepto.checked; } }
            ];
            var valido = true;

            revisiones.forEach(function (revision) {
                var correcto = revision.campo ? revision.ok() : false;

                if (revision.campo) {
                    marcarError(revision.campo, !correcto);
                }

                if (!correcto) {
                    valido = false;
                }
            });

            return valido;
        }

        formulario.addEventListener('input', function (evento) {
            if (evento.target.matches('input, select, textarea')) {
                marcarError(evento.target, false);
            }
        });

        formulario.addEventListener('submit', function (evento) {
            evento.preventDefault();

            if (!validar()) {
                var primero = formulario.querySelector('[aria-invalid="true"]');

                if (primero) {
                    // El grupo señala el fieldset; el foco tiene que ir a su primer
                    // radio, porque un fieldset no recibe el foco.
                    var destino = primero.tagName === 'FIELDSET' ? primero.querySelector('input') : primero;

                    if (destino) {
                        destino.focus();
                    }
                }

                if (aviso) {
                    aviso.textContent = 'Revisa los campos marcados para continuar.';
                }

                return;
            }

            // Demostración: aquí no hay servidor. Se dice claro en lugar de
            // simular una matrícula que nunca se registró.
            if (formulario.dataset.demo === 'true') {
                if (aviso) {
                    aviso.textContent = 'Demostración: el formulario funciona, pero no envía datos. '
                        + 'En tu sitio la inscripción llega a tu correo o a tu WhatsApp, con copia al alumno.';
                }

                return;
            }

            formulario.submit();
        });
    }

    /* ---------------------------------------------------------------------
       Año del pie
       --------------------------------------------------------------------- */

    var anio = document.getElementById('anio');

    if (anio) {
        anio.textContent = String(new Date().getFullYear());
    }
})();
