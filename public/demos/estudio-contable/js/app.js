/*
   Estudio Contable Vega — JavaScript de la demostración
   =============================================================================

   Un solo archivo para las cinco páginas del sitio, sin librerías y sin nada
   externo. Cada bloque se activa solo si la página trae sus elementos, así que
   el mismo archivo sirve para el inicio, el listado de servicios, la ficha, los
   planes y el contacto.

   Bloques:
     1. Utilidades y atributos del documento
     2. Cabecera pegajosa y menú del celular
     3. Portada: slider de tres fotos
     4. Aparición al hacer scroll
     5. Cifras que cuentan al entrar en pantalla
     6. Filtros del libro de servicios
     7. Formulario de consulta (validación en el navegador)
     8. Tema claro / oscuro
     9. Año del pie
*/

(function () {
    'use strict';

    /* =======================================================================
       1. Utilidades y atributos del documento
       ======================================================================= */

    // El CSS esconde el slider y el interruptor hasta que hay JavaScript: así
    // ninguna persona sin JS ve controles que no hacen nada.
    document.documentElement.classList.add('js');

    var movimientoReducido = window.matchMedia('(prefers-reduced-motion: reduce)');

    function crear(tag, atributos) {
        var elemento = document.createElement(tag);

        Object.keys(atributos || {}).forEach(function (clave) {
            elemento.setAttribute(clave, atributos[clave]);
        });

        return elemento;
    }

    /* =======================================================================
       2. Cabecera pegajosa y menú del celular
       ======================================================================= */

    var cabecera = document.getElementById('cabecera');

    if (cabecera) {
        var ultimoEstado = null;

        var ajustarCabecera = function () {
            var compacta = window.scrollY > 24;

            if (compacta !== ultimoEstado) {
                cabecera.classList.toggle('cabecera--compacta', compacta);
                ultimoEstado = compacta;
            }
        };

        ajustarCabecera();

        window.addEventListener('scroll', ajustarCabecera, { passive: true });
    }

    var botonMenu = document.getElementById('menu-boton');
    var menu = document.getElementById('menu-principal');

    if (botonMenu && menu) {
        var cerrarMenu = function () {
            menu.classList.remove('menu--abierto');
            botonMenu.setAttribute('aria-expanded', 'false');
            botonMenu.querySelector('.sr-solo').textContent = 'Abrir menú';
        };

        var abrirMenu = function () {
            menu.classList.add('menu--abierto');
            botonMenu.setAttribute('aria-expanded', 'true');
            botonMenu.querySelector('.sr-solo').textContent = 'Cerrar menú';
        };

        botonMenu.addEventListener('click', function () {
            if (botonMenu.getAttribute('aria-expanded') === 'true') {
                cerrarMenu();
            } else {
                abrirMenu();
            }
        });

        // Al elegir un enlace el panel estorba: se cierra solo.
        menu.addEventListener('click', function (evento) {
            if (evento.target.closest('a')) {
                cerrarMenu();
            }
        });

        window.addEventListener('keydown', function (evento) {
            if (evento.key === 'Escape' && botonMenu.getAttribute('aria-expanded') === 'true') {
                cerrarMenu();
                botonMenu.focus();
            }
        });

        // Si la ventana crece, el panel de móvil deja de tener sentido.
        window.addEventListener('resize', function () {
            if (window.innerWidth > 899) {
                cerrarMenu();
            }
        });
    }

    /* =======================================================================
       3. Portada: slider de tres fotos

       La pista es una fila de diapositivas que se desplaza con `transform`. El
       paso automático se detiene al pasar el mouse, al enfocar los controles
       con el teclado, al arrastrar con el dedo y cuando la pestaña queda en
       segundo plano. Con movimiento reducido el paso automático no arranca,
       pero las flechas, los puntos y el teclado siguen funcionando.
       ======================================================================= */

    var portada = document.querySelector('.portada');

    if (portada) {
        var pista = portada.querySelector('.portada__pista');
        var diapositivas = [].slice.call(portada.querySelectorAll('.portada__diapositiva'));
        var puntos = [].slice.call(portada.querySelectorAll('[data-carrusel-punto]'));
        var flechaAnterior = portada.querySelector('[data-carrusel-anterior]');
        var flechaSiguiente = portada.querySelector('[data-carrusel-siguiente]');
        var cuenta = portada.querySelector('.carrusel__cuenta strong');

        var PASO = 6000;
        var activa = 0;
        var temporizador = null;
        var arrastre = null;

        var pintar = function (indice, conTransicion) {
            activa = (indice + diapositivas.length) % diapositivas.length;

            if (conTransicion === false) {
                pista.style.transition = 'none';
            }

            pista.style.transform = 'translate3d(' + (-100 * activa) + '%, 0, 0)';

            if (conTransicion === false) {
                // Se fuerza un recálculo para que el navegador no agrupe los dos
                // cambios de estilo y la siguiente transición sí se vea.
                void pista.offsetWidth;
                pista.style.transition = '';
            }

            diapositivas.forEach(function (diapositiva, posicion) {
                diapositiva.setAttribute('aria-hidden', posicion === activa ? 'false' : 'true');
            });

            puntos.forEach(function (punto, posicion) {
                if (posicion === activa) {
                    punto.setAttribute('aria-current', 'true');
                } else {
                    punto.removeAttribute('aria-current');
                }
            });

            if (cuenta) {
                cuenta.textContent = ('0' + (activa + 1)).slice(-2);
            }
        };

        var detener = function () {
            if (temporizador !== null) {
                window.clearInterval(temporizador);
                temporizador = null;
            }
        };

        var arrancar = function () {
            detener();

            if (!movimientoReducido.matches && diapositivas.length > 1) {
                temporizador = window.setInterval(function () {
                    pintar(activa + 1);
                }, PASO);
            }
        };

        var ir = function (indice, manual) {
            pintar(indice);

            if (manual) {
                arrancar();
            }
        };

        if (flechaAnterior) {
            flechaAnterior.addEventListener('click', function () {
                ir(activa - 1, true);
            });
        }

        if (flechaSiguiente) {
            flechaSiguiente.addEventListener('click', function () {
                ir(activa + 1, true);
            });
        }

        puntos.forEach(function (punto, posicion) {
            punto.addEventListener('click', function () {
                ir(posicion, true);
            });
        });

        // Teclado: las flechas laterales mueven el slider cuando la portada
        // tiene el foco o cuando no hay ningún campo de texto activo.
        window.addEventListener('keydown', function (evento) {
            if (evento.key !== 'ArrowLeft' && evento.key !== 'ArrowRight') {
                return;
            }

            var destino = evento.target;

            if (destino && (destino.tagName === 'INPUT' || destino.tagName === 'TEXTAREA' || destino.tagName === 'SELECT')) {
                return;
            }

            ir(activa + (evento.key === 'ArrowRight' ? 1 : -1), true);
        });

        // Pausa mientras se mira de cerca.
        portada.addEventListener('mouseenter', detener);
        portada.addEventListener('mouseleave', arrancar);
        portada.addEventListener('focusin', detener);
        portada.addEventListener('focusout', arrancar);

        // Arrastre con el dedo (y con el mouse) sobre la foto.
        var inicioArrastre = function (x) {
            arrastre = { x: x, movido: 0 };
            detener();
        };

        var finArrastre = function (x) {
            if (!arrastre) {
                return;
            }

            var recorrido = x - arrastre.x;
            arrastre = null;

            if (Math.abs(recorrido) > 45) {
                ir(activa + (recorrido < 0 ? 1 : -1));
            }

            arrancar();
        };

        portada.addEventListener('pointerdown', function (evento) {
            inicioArrastre(evento.clientX);
        });

        portada.addEventListener('pointerup', function (evento) {
            finArrastre(evento.clientX);
        });

        portada.addEventListener('pointercancel', function () {
            arrastre = null;
            arrancar();
        });

        // Con la pestaña en segundo plano no tiene sentido seguir cambiando de
        // foto: se pausa y se retoma al volver.
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                detener();
            } else {
                arrancar();
            }
        });

        // Si el visitante cambia la preferencia de movimiento, se respeta en el
        // acto, sin recargar.
        movimientoReducido.addEventListener('change', arrancar);

        pintar(0, false);
        arrancar();
    }

    /* =======================================================================
       4. Aparición al hacer scroll

       Los elementos con la clase `.revelar` entran con un retraso escalonado
       según su posición en el grupo. Sin JavaScript el CSS ya los muestra, así
       que aquí solo se les pone el estado final.
       ======================================================================= */

    var revelables = [].slice.call(document.querySelectorAll('.revelar'));

    if (revelables.length && 'IntersectionObserver' in window) {
        var observadorRevelado = new IntersectionObserver(function (entradas) {
            entradas.forEach(function (entrada) {
                if (!entrada.isIntersecting) {
                    return;
                }

                var grupo = entrada.target.parentElement;
                var hermanos = grupo ? [].slice.call(grupo.children).filter(function (hijo) {
                    return hijo.classList.contains('revelar');
                }) : [];
                var posicion = hermanos.indexOf(entrada.target);

                entrada.target.style.setProperty('--retraso', Math.min(Math.max(posicion, 0), 4) * 0.08 + 's');
                entrada.target.classList.add('revelar--visible');

                observadorRevelado.unobserve(entrada.target);
            });
        }, { rootMargin: '0px 0px -10% 0px', threshold: 0.15 });

        revelables.forEach(function (elemento) {
            observadorRevelado.observe(elemento);
        });
    } else {
        revelables.forEach(function (elemento) {
            elemento.classList.add('revelar--visible');
        });
    }

    /* =======================================================================
       5. Cifras que cuentan al entrar en pantalla

       Cada cifra lleva su valor final en `data-contador`. El conteo es un
       adorno: el número correcto ya está en el HTML, así que sin JavaScript (o
       con movimiento reducido) se lee igual.
       ======================================================================= */

    var contadores = [].slice.call(document.querySelectorAll('[data-contador]'));

    var formatear = function (numero, miles) {
        var texto = String(numero);

        if (miles) {
            texto = texto.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        return texto;
    };

    if (contadores.length && 'IntersectionObserver' in window && !movimientoReducido.matches) {
        var observadorCifras = new IntersectionObserver(function (entradas) {
            entradas.forEach(function (entrada) {
                if (!entrada.isIntersecting) {
                    return;
                }

                var elemento = entrada.target;
                var destino = parseInt(elemento.getAttribute('data-contador'), 10);
                var prefijo = elemento.getAttribute('data-prefijo') || '';
                var miles = elemento.getAttribute('data-miles') === '1';
                var inicio = window.performance.now();
                var duracion = 1100;

                var paso = function (ahora) {
                    var avance = Math.min((ahora - inicio) / duracion, 1);
                    // Curva suave: arranca rápido y frena al final.
                    var suavizado = 1 - Math.pow(1 - avance, 3);
                    var valor = Math.round(destino * suavizado);

                    elemento.textContent = prefijo + formatear(valor, miles);

                    if (avance < 1) {
                        window.requestAnimationFrame(paso);
                    }
                };

                if (!isNaN(destino)) {
                    window.requestAnimationFrame(paso);
                }

                observadorCifras.unobserve(elemento);
            });
        }, { threshold: 0.4 });

        contadores.forEach(function (elemento) {
            observadorCifras.observe(elemento);
        });
    }

    /* =======================================================================
       6. Filtros del libro de servicios

       Los filtros viven solo en el listado. Cada fila lleva `data-area` y
       `data-atencion`, y el texto buscable se arma una vez con el contenido de
       la fila: nombre, resumen, plazo y precio.
       ======================================================================= */

    var listaServicios = document.getElementById('lista-servicios');

    if (listaServicios) {
        var filtroTexto = document.getElementById('filtro-texto');
        var filtroArea = document.getElementById('filtro-area');
        var filtroAtencion = document.getElementById('filtro-atencion');
        var resumen = document.getElementById('filtro-resumen');
        var vacio = document.getElementById('servicios-vacio');
        var filas = [].slice.call(listaServicios.querySelectorAll('.servicio'));

        // Se normalizan las tildes para que «declaracion» encuentre
        // «Declaración» y «regimen» encuentre «Régimen».
        var sinTildes = function (texto) {
            return texto
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '');
        };

        var textoDe = {};

        filas.forEach(function (fila, posicion) {
            textoDe[posicion] = sinTildes(fila.textContent);
        });

        var aplicar = function () {
            var buscado = sinTildes((filtroTexto && filtroTexto.value ? filtroTexto.value : '').trim());
            var area = filtroArea ? filtroArea.value : '';
            var atencion = filtroAtencion ? filtroAtencion.value : '';
            var visibles = 0;

            filas.forEach(function (fila, posicion) {
                var coincide = (buscado === '' || textoDe[posicion].indexOf(buscado) !== -1)
                    && (area === '' || fila.getAttribute('data-area') === area)
                    && (atencion === '' || fila.getAttribute('data-atencion') === atencion);

                fila.hidden = !coincide;

                if (coincide) {
                    visibles++;
                }
            });

            if (resumen) {
                resumen.textContent = visibles === 1
                    ? 'Un servicio encontrado'
                    : visibles + ' servicios encontrados';
            }

            if (vacio) {
                vacio.hidden = visibles !== 0;
            }
        };

        // Los filtros se pueden traer desde la URL (`?area=laboral`), que es lo
        // que usan los enlaces de área del inicio y del pie.
        var parametros = new URLSearchParams(window.location.search);
        var areaPedida = parametros.get('area');
        var atencionPedida = parametros.get('atencion');

        if (areaPedida && filtroArea && filtroArea.querySelector('option[value="' + areaPedida + '"]')) {
            filtroArea.value = areaPedida;
        }

        if (atencionPedida && filtroAtencion && filtroAtencion.querySelector('option[value="' + atencionPedida + '"]')) {
            filtroAtencion.value = atencionPedida;
        }

        [filtroTexto, filtroArea, filtroAtencion].forEach(function (control) {
            if (!control) {
                return;
            }

            control.addEventListener('input', aplicar);
            control.addEventListener('change', aplicar);
        });

        aplicar();
    }

    /* =======================================================================
       7. Formulario de consulta

       Se valida campo por campo en el navegador. Con `data-demo="true"` no se
       envía nada: se avisa de que es una demostración. Al publicar el sitio del
       cliente se quita ese atributo y el envío queda habilitado.
       ======================================================================= */

    var formulario = document.getElementById('formulario-contacto');

    if (formulario) {
        var aviso = document.getElementById('aviso-formulario');

        var limpiar = function (texto) {
            return (texto || '').replace(/\s+/g, ' ').trim();
        };

        var soloDigitos = function (texto) {
            return (texto || '').replace(/\D/g, '');
        };

        // Un campo es válido si cumple su regla. El contenedor que se marca es
        // el del campo (`p.campo`) o el del grupo/la casilla.
        var reglas = [
            { campo: formulario.nombre, ok: function (valor) { return limpiar(valor).length >= 3; } },
            { campo: formulario.telefono, ok: function (valor) { return soloDigitos(valor).length >= 9; } },
            { campo: formulario.correo, ok: function (valor) { return /^[^\s@]+@[^\s@]+\.[a-z]{2,}$/i.test(limpiar(valor)); } },
            { campo: formulario.servicio, ok: function () { return formulario.servicio.value !== ''; } },
            {
                campo: formulario.querySelector('input[name="regimen"]'),
                // Un grupo de radios no puede marcarse a sí mismo: se marca el
                // fieldset, que es el elemento que lleva el aviso al lado.
                contenedor: formulario.querySelector('.grupo-radio'),
                ok: function () { return formulario.querySelector('input[name="regimen"]:checked') !== null; }
            },
            { campo: formulario.acepto, ok: function () { return formulario.acepto.checked; } }
        ];

        var marcar = function (regla, conError) {
            var contenedor = regla.contenedor
                || regla.campo.closest('.campo')
                || regla.campo.closest('.casilla');

            if (!contenedor) {
                return;
            }

            var clase = contenedor.classList.contains('grupo-radio')
                ? 'grupo-radio--error'
                : (contenedor.classList.contains('casilla') ? 'casilla--error' : 'campo--error');

            contenedor.classList.toggle(clase, conError);

            if (regla.campo.id) {
                if (conError) {
                    regla.campo.setAttribute('aria-invalid', 'true');
                } else {
                    regla.campo.removeAttribute('aria-invalid');
                }
            }
        };

        var revisar = function (avisar) {
            var invalidos = [];

            reglas.forEach(function (regla) {
                var valor = regla.campo.value;
                var valido = valor === undefined ? regla.ok() : regla.ok(valor);

                marcar(regla, !valido);

                if (!valido) {
                    invalidos.push(regla);
                }
            });

            if (avisar && aviso) {
                aviso.textContent = invalidos.length
                    ? 'Revisa los campos marcados para continuar.'
                    : '';
                aviso.classList.toggle('formulario__aviso--error', invalidos.length > 0);
            }

            return invalidos;
        };

        // Al salir de un campo ya se avisa si quedó mal, en vez de esperar al
        // envío: es lo que hace menos frustrante el formulario.
        reglas.forEach(function (regla) {
            var elemento = regla.campo;

            if (elemento.type === 'radio' || elemento.type === 'checkbox') {
                elemento.addEventListener('change', function () {
                    revisar(false);
                });

                return;
            }

            elemento.addEventListener('blur', function () {
                revisar(false);
            });

            elemento.addEventListener('input', function () {
                var contenedor = elemento.closest('.campo');

                if (contenedor && contenedor.classList.contains('campo--error')) {
                    revisar(false);
                }
            });
        });

        // Si se llega desde la ficha de un servicio (`contacto.html?servicio=…`),
        // ese servicio aparece ya elegido. El parámetro se limpia antes de usarlo.
        var servicioPedido = (new URLSearchParams(window.location.search).get('servicio') || '')
            .replace(/[^a-z0-9-]/gi, '');

        if (servicioPedido && formulario.servicio.querySelector('option[value="' + servicioPedido + '"]')) {
            formulario.servicio.value = servicioPedido;
        }

        // Y si se llega desde la hoja de precios (`contacto.html?plan=emprende`),
        // el mensaje viene empezado con el plan que estaba mirando.
        var planPedido = (new URLSearchParams(window.location.search).get('plan') || '').toLowerCase();
        var planes = { basico: 'Básico', emprende: 'Emprende', empresa: 'Empresa' };

        if (planes[planPedido] && formulario.mensaje && formulario.mensaje.value === '') {
            formulario.mensaje.value = 'Me interesa el plan ' + planes[planPedido] + '. ';
        }

        formulario.addEventListener('submit', function (evento) {
            evento.preventDefault();

            var invalidos = revisar(true);

            if (invalidos.length) {
                var primero = invalidos[0].campo;

                primero.focus({ preventScroll: true });
                (invalidos[0].contenedor || primero).scrollIntoView({ block: 'center' });

                return;
            }

            // La trampa para robots no se rellena sola: si trae algo, no se
            // procesa y no se le dice por qué.
            if (formulario.sitio_web && formulario.sitio_web.value !== '') {
                return;
            }

            if (formulario.getAttribute('data-demo') === 'true') {
                if (aviso) {
                    aviso.classList.remove('formulario__aviso--error');
                    aviso.textContent = 'Demostración: el formulario funciona, pero no envía datos. '
                        + 'En tu sitio la solicitud llega a tu correo o a tu WhatsApp, con copia a quien la envió.';
                }

                return;
            }

            formulario.submit();
        });
    }

    /* =======================================================================
       8. Tema claro / oscuro

       El atributo `data-tema` del <html> manda y el CSS hace el resto. El script
       del <head> ya aplicó la elección guardada, así que aquí solo se conecta el
       interruptor, se recuerda la elección en localStorage y se escucha a las
       otras pestañas para que el tema no se pelee entre ellas.
       ======================================================================= */

    var CLAVE_TEMA = 'estudio-contable-tema';
    var interruptores = [].slice.call(document.querySelectorAll('[data-tema-switch]'));

    var esOscuro = function () {
        return document.documentElement.getAttribute('data-tema') === 'oscuro';
    };

    var pintarInterruptor = function () {
        var oscuro = esOscuro();

        interruptores.forEach(function (boton) {
            boton.setAttribute('aria-pressed', oscuro ? 'true' : 'false');
            boton.setAttribute('aria-label', oscuro ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
            boton.setAttribute('title', oscuro ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
        });
    };

    var aplicarTema = function (oscuro, guardar) {
        document.documentElement.setAttribute('data-tema', oscuro ? 'oscuro' : 'claro');
        pintarInterruptor();

        if (!guardar) {
            return;
        }

        try {
            localStorage.setItem(CLAVE_TEMA, oscuro ? 'oscuro' : 'claro');
        } catch (error) {
            /* Sin almacenamiento el tema se aplica igual, solo no se recuerda. */
        }
    };

    interruptores.forEach(function (boton) {
        boton.addEventListener('click', function () {
            aplicarTema(!esOscuro(), true);
        });
    });

    // Otra pestaña con la misma demo abierta: se pone al día sin recargar.
    window.addEventListener('storage', function (evento) {
        if (evento.key === CLAVE_TEMA) {
            aplicarTema(evento.newValue === 'oscuro', false);
        }
    });

    pintarInterruptor();

    /* =======================================================================
       9. Año del pie
       ======================================================================= */

    var anio = document.getElementById('anio');

    if (anio) {
        anio.textContent = String(new Date().getFullYear());
    }
})();
