/*
 * VERDIA Market — JavaScript de la demostración «Supermercado Online».
 *
 * Un solo archivo para las cuatro páginas, sin dependencias ni peticiones a
 * terceros. Lo que hace, por partes:
 *
 *   1. El tema claro / oscuro, con la misma interfaz del resto del banco: el
 *      atributo `data-tema` del <html>, una clave de `localStorage` propia de
 *      esta plantilla y aviso entre pestañas.
 *   2. La cabecera de la tienda: barra de aviso que se cierra, categorías
 *      desplegables en móvil y buscador con sugerencias.
 *   3. El slider de la portada, manejable con el ratón, el teclado y el dedo, y
 *      con el paso automático apagado si el visitante pide menos movimiento.
 *   4. La canasta: se guarda en el navegador, se cuenta en el icono del carrito,
 *      se revisa en un panel lateral y se cierra con una simulación de pedido
 *      que dice, con todas sus letras, que no se envía nada.
 *   5. El catálogo: buscador, filtros por pasillo y por necesidad, y orden,
 *      todos leídos de la URL para que el enlace se pueda compartir.
 *   6. La ficha de producto, que se arma según `?p=`, y la página de la canasta
 *      con sus totales.
 *
 * Los datos del catálogo viven en `datos.js`, porque la demostración no tiene
 * servidor: en una tienda real llegan de la base de datos del negocio.
 */

(function () {
    'use strict';

    document.documentElement.classList.add('js');

    var movimientoReducido = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    var TIENDA = window.VERDIA_TIENDA || {};
    var RUBROS = window.VERDIA_RUBROS || [];
    var PRODUCTOS = window.VERDIA_PRODUCTOS || [];
    var BENEFICIOS = window.VERDIA_BENEFICIOS || [];
    var NECESIDADES = window.VERDIA_NECESIDADES || [];
    var GRUPOS = window.VERDIA_GRUPOS || [];

    var CLAVES = TIENDA.claves || { tema: 'supermercado-online-verdia-tema', canasta: 'supermercado-online-verdia-canasta' };

    /* =====================================================================
       Utilidades
       ===================================================================== */

    function $(selector, raiz) {
        return (raiz || document).querySelector(selector);
    }

    function $$(selector, raiz) {
        return Array.prototype.slice.call((raiz || document).querySelectorAll(selector));
    }

    /** Texto sin tildes ni mayúsculas, para buscar como se escribe de verdad. */
    function llano(texto) {
        return String(texto == null ? '' : texto)
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    /** Monto con el formato de la tienda: S/ 12.90. */
    function soles(monto) {
        return 'S/ ' + Number(monto).toFixed(2);
    }

    function porSlug(slug) {
        for (var i = 0; i < PRODUCTOS.length; i++) {
            if (PRODUCTOS[i].slug === slug) {
                return PRODUCTOS[i];
            }
        }

        return null;
    }

    function porRubro(clave) {
        for (var i = 0; i < RUBROS.length; i++) {
            if (RUBROS[i].clave === clave) {
                return RUBROS[i];
            }
        }

        return null;
    }

    function guardar(clave, valor) {
        try {
            localStorage.setItem(clave, JSON.stringify(valor));
        } catch (error) {
            /* Almacenamiento bloqueado (navegación privada): la canasta vive
               solo en memoria mientras dure la página. */
        }
    }

    function leer(clave, porDefecto) {
        try {
            var crudo = localStorage.getItem(clave);

            return crudo === null ? porDefecto : JSON.parse(crudo);
        } catch (error) {
            return porDefecto;
        }
    }

    /* =====================================================================
       1. Tema claro / oscuro
       ===================================================================== */

    function temaActual() {
        return document.documentElement.getAttribute('data-tema') === 'oscuro' ? 'oscuro' : 'claro';
    }

    function pintarTema() {
        var oscuro = temaActual() === 'oscuro';

        $$('[data-tema-switch]').forEach(function (boton) {
            boton.setAttribute('aria-pressed', oscuro ? 'true' : 'false');
            boton.setAttribute('aria-label', oscuro ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
        });
    }

    function aplicarTema(tema) {
        document.documentElement.setAttribute('data-tema', tema);
        guardar(CLAVES.tema, tema);
        pintarTema();
    }

    $$('[data-tema-switch]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            aplicarTema(temaActual() === 'oscuro' ? 'claro' : 'oscuro');
        });
    });

    pintarTema();

    window.addEventListener('storage', function (evento) {
        if (evento.key === CLAVES.tema) {
            document.documentElement.setAttribute('data-tema', evento.newValue === '"oscuro"' || evento.newValue === 'oscuro' ? 'oscuro' : 'claro');
            pintarTema();
        }

        if (evento.key === CLAVES.canasta) {
            canasta = normalizarCanasta(leer(CLAVES.canasta, []));
            pintarCuenta();
            pintarPanel();
        }
    });

    /* =====================================================================
       2. Cabecera: aviso, menú de categorías y buscador
       ===================================================================== */

    $$('[data-aviso-cerrar]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            var aviso = boton.closest('[data-aviso]');

            if (aviso) {
                aviso.remove();
            }
        });
    });

    /* El menú de pasillos se despliega en móvil: en escritorio la lista se ve
       siempre y el botón está oculto por CSS. */
    $$('[data-menu-boton]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            var menu = $('[data-menu]');

            if (!menu) {
                return;
            }

            var abierto = menu.classList.toggle('esta-abierto');

            boton.setAttribute('aria-expanded', abierto ? 'true' : 'false');
        });
    });

    /* Buscador con sugerencias: se llenan con los productos que coinciden. */
    $$('[data-buscador]').forEach(function (formulario) {
        var campo = $('input[type="search"]', formulario);
        var caja = $('[data-sugerencias]', formulario);

        if (!campo || !caja) {
            return;
        }

        function pintarSugerencias() {
            var aguja = llano(campo.value.trim());

            if (aguja.length < 2) {
                caja.setAttribute('hidden', '');
                caja.innerHTML = '';

                return;
            }

            var encontrados = PRODUCTOS.filter(function (producto) {
                return llano(producto.nombre + ' ' + producto.presentacion + ' ' + producto.resumen).indexOf(aguja) !== -1;
            }).slice(0, 6);

            if (!encontrados.length) {
                caja.innerHTML = '<li class="buscador__vacio">Sin resultados para «' + campo.value.trim() + '». Prueba con «pan» o «huevos».</li>';
                caja.removeAttribute('hidden');

                return;
            }

            caja.innerHTML = encontrados.map(function (producto) {
                return '<li><a href="producto.html?p=' + producto.slug + '">'
                    + '<img src="' + producto.foto + '" alt="" width="48" height="48" loading="lazy">'
                    + '<span><strong>' + producto.nombre + '</strong><small>' + producto.presentacion + ' · ' + soles(producto.precio) + '</small></span>'
                    + '</a></li>';
            }).join('');

            caja.removeAttribute('hidden');
        }

        campo.addEventListener('input', pintarSugerencias);

        campo.addEventListener('focus', function () {
            if (campo.value.trim().length >= 2) {
                pintarSugerencias();
            }
        });

        document.addEventListener('click', function (evento) {
            if (!formulario.contains(evento.target)) {
                caja.setAttribute('hidden', '');
            }
        });

        formulario.addEventListener('submit', function (evento) {
            evento.preventDefault();
            caja.setAttribute('hidden', '');

            var destino = 'tienda.html';

            if (campo.value.trim() !== '') {
                destino += '?q=' + encodeURIComponent(campo.value.trim());
            }

            window.location.href = destino;
        });
    });

    /* =====================================================================
       3. Slider de la portada
       ===================================================================== */

    $$('[data-carrusel]').forEach(function (carrusel) {
        var diapositivas = $$('[data-diapositiva]', carrusel);
        var puntos = $$('[data-carrusel-punto]', carrusel);
        var anterior = $('[data-carrusel-anterior]', carrusel);
        var siguiente = $('[data-carrusel-siguiente]', carrusel);

        if (diapositivas.length < 2) {
            return;
        }

        var actual = 0;
        var temporizador = null;
        var inicioArrastre = null;

        function mostrar(indice) {
            actual = (indice + diapositivas.length) % diapositivas.length;

            diapositivas.forEach(function (diapositiva, i) {
                diapositiva.toggleAttribute('data-activa', i === actual);
            });

            puntos.forEach(function (punto, i) {
                var activo = i === actual;

                punto.classList.toggle('esta-activo', activo);
                punto.setAttribute('aria-current', activo ? 'true' : 'false');
            });
        }

        function parar() {
            if (temporizador) {
                window.clearInterval(temporizador);
                temporizador = null;
            }
        }

        function arrancar() {
            parar();

            if (movimientoReducido) {
                return;
            }

            temporizador = window.setInterval(function () {
                mostrar(actual + 1);
            }, 7000);
        }

        anterior && anterior.addEventListener('click', function () {
            mostrar(actual - 1);
            arrancar();
        });

        siguiente && siguiente.addEventListener('click', function () {
            mostrar(actual + 1);
            arrancar();
        });

        puntos.forEach(function (punto, i) {
            punto.addEventListener('click', function () {
                mostrar(i);
                arrancar();
            });
        });

        carrusel.addEventListener('keydown', function (evento) {
            if (evento.key === 'ArrowLeft') {
                mostrar(actual - 1);
                arrancar();
            }

            if (evento.key === 'ArrowRight') {
                mostrar(actual + 1);
                arrancar();
            }
        });

        carrusel.addEventListener('pointerdown', function (evento) {
            inicioArrastre = evento.clientX;
        });

        carrusel.addEventListener('pointerup', function (evento) {
            if (inicioArrastre === null) {
                return;
            }

            var salto = evento.clientX - inicioArrastre;

            inicioArrastre = null;

            if (Math.abs(salto) < 40) {
                return;
            }

            mostrar(salto < 0 ? actual + 1 : actual - 1);
            arrancar();
        });

        carrusel.addEventListener('mouseenter', parar);
        carrusel.addEventListener('mouseleave', arrancar);
        carrusel.addEventListener('focusin', parar);
        carrusel.addEventListener('focusout', arrancar);

        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                parar();
            } else {
                arrancar();
            }
        });

        mostrar(0);
        arrancar();
    });

    /* =====================================================================
       4. La canasta
       ===================================================================== */

    var canasta = normalizarCanasta(leer(CLAVES.canasta, []));

    function normalizarCanasta(valor) {
        if (!Array.isArray(valor)) {
            return [];
        }

        return valor
            .map(function (linea) {
                var producto = porSlug(linea && linea.slug);

                return producto ? { slug: producto.slug, cantidad: Math.max(1, Math.min(99, parseInt(linea.cantidad, 10) || 1)) } : null;
            })
            .filter(Boolean);
    }

    function guardarCanasta() {
        guardar(CLAVES.canasta, canasta);
        pintarCuenta();
        pintarPanel();

        if ($('[data-canasta-pagina]')) {
            pintarPaginaCanasta();
        }
    }

    function lineaDe(slug) {
        for (var i = 0; i < canasta.length; i++) {
            if (canasta[i].slug === slug) {
                return canasta[i];
            }
        }

        return null;
    }

    function agregar(slug, cantidad) {
        var linea = lineaDe(slug);

        if (linea) {
            linea.cantidad = Math.min(99, linea.cantidad + (cantidad || 1));
        } else {
            canasta.push({ slug: slug, cantidad: cantidad || 1 });
        }

        guardarCanasta();
    }

    function poner(slug, cantidad) {
        var linea = lineaDe(slug);

        if (!linea) {
            return;
        }

        linea.cantidad = Math.max(1, Math.min(99, cantidad));
        guardarCanasta();
    }

    function quitar(slug) {
        canasta = canasta.filter(function (linea) {
            return linea.slug !== slug;
        });

        guardarCanasta();
    }

    function vaciar() {
        canasta = [];
        guardarCanasta();
    }

    function contar() {
        return canasta.reduce(function (total, linea) {
            return total + linea.cantidad;
        }, 0);
    }

    function subtotal() {
        return canasta.reduce(function (total, linea) {
            var producto = porSlug(linea.slug);

            return producto ? total + producto.precio * linea.cantidad : total;
        }, 0);
    }

    function pintarCuenta() {
        var total = contar();

        $$('[data-canasta-cuenta]').forEach(function (nodo) {
            nodo.textContent = String(total);
            nodo.classList.toggle('esta-vacia', total === 0);
        });
    }

    /** Una tarjeta de producto, la misma en el catálogo, la portada y las ofertas. */
    function tarjeta(producto) {
        var rubro = porRubro(producto.rubro);
        var insignia = producto.antes
            ? (producto.etiqueta || 'Oferta')
            : producto.etiqueta;
        var descuento = producto.antes ? Math.round((1 - producto.precio / producto.antes) * 100) : 0;

        return '<article class="pieza" data-pieza="' + producto.slug + '">'
            + '<a class="pieza__foto" href="producto.html?p=' + producto.slug + '">'
            + '<img src="' + producto.foto + '" alt="' + producto.alt + '" width="700" height="700" loading="lazy">'
            + (insignia ? '<span class="pieza__insignia' + (producto.antes ? ' pieza__insignia--oferta' : '') + '">' + insignia + '</span>' : '')
            + (descuento ? '<span class="pieza__descuento">−' + descuento + '%</span>' : '')
            + '</a>'
            + '<div class="pieza__cuerpo">'
            + '<p class="pieza__rubro">' + (rubro ? rubro.nombre : '') + '</p>'
            + '<h3 class="pieza__nombre"><a href="producto.html?p=' + producto.slug + '">' + producto.nombre + '</a></h3>'
            + '<p class="pieza__presentacion">' + producto.presentacion + '</p>'
            + '<p class="pieza__precio">'
            + '<span class="pieza__monto">' + soles(producto.precio) + '</span>'
            + (producto.antes ? '<span class="pieza__antes">' + soles(producto.antes) + '</span>' : '')
            + '</p>'
            + '</div>'
            + '<div class="pieza__acciones">'
            + '<button class="pieza__agregar" type="button" data-agregar="' + producto.slug + '">'
            + '<span class="pieza__agregar-texto">Añadir al carrito</span>'
            + '<span class="pieza__agregar-hecho" hidden>Agregado</span>'
            + '</button>'
            + '</div>'
            + '</article>';
    }

    function rejilla(destino, productos, mensajeVacio) {
        if (!destino) {
            return;
        }

        if (!productos.length) {
            destino.innerHTML = '<p class="vacio">' + (mensajeVacio || 'No hay productos que coincidan con esta selección.') + '</p>';

            return;
        }

        destino.innerHTML = productos.map(tarjeta).join('');
    }

    /* Añadir desde cualquier tarjeta de la página. */
    document.addEventListener('click', function (evento) {
        var boton = evento.target.closest('[data-agregar]');

        if (!boton) {
            return;
        }

        agregar(boton.getAttribute('data-agregar'), 1);

        var hecho = $('.pieza__agregar-hecho', boton);
        var texto = $('.pieza__agregar-texto', boton);

        if (hecho && texto) {
            hecho.removeAttribute('hidden');
            texto.setAttribute('hidden', '');
            window.setTimeout(function () {
                hecho.setAttribute('hidden', '');
                texto.removeAttribute('hidden');
            }, 1400);
        }
    });

    /* Panel lateral de la canasta */
    function pintarPanel() {
        var lista = $('[data-canasta-lista]');
        var total = $('[data-canasta-total]');
        var pie = $('[data-canasta-pie]');
        var vacio = $('[data-canasta-vacia]');

        if (!lista) {
            return;
        }

        if (!canasta.length) {
            lista.innerHTML = '';
            vacio && vacio.removeAttribute('hidden');
            pie && pie.setAttribute('hidden', '');
        } else {
            vacio && vacio.setAttribute('hidden', '');
            pie && pie.removeAttribute('hidden');

            lista.innerHTML = canasta.map(function (linea) {
                var producto = porSlug(linea.slug);

                return '<li class="linea" data-linea="' + producto.slug + '">'
                    + '<img src="' + producto.foto + '" alt="" width="72" height="72" loading="lazy">'
                    + '<div class="linea__datos">'
                    + '<p class="linea__nombre">' + producto.nombre + '</p>'
                    + '<p class="linea__presentacion">' + producto.presentacion + '</p>'
                    + '<div class="cantidad">'
                    + '<button type="button" data-cantidad="menos" data-slug="' + producto.slug + '" aria-label="Quitar una unidad de ' + producto.nombre + '">−</button>'
                    + '<input type="number" min="1" max="99" value="' + linea.cantidad + '" data-cantidad-campo data-slug="' + producto.slug + '" aria-label="Cantidad de ' + producto.nombre + '">'
                    + '<button type="button" data-cantidad="mas" data-slug="' + producto.slug + '" aria-label="Añadir una unidad de ' + producto.nombre + '">+</button>'
                    + '</div>'
                    + '</div>'
                    + '<div class="linea__cierre">'
                    + '<p class="linea__importe">' + soles(producto.precio * linea.cantidad) + '</p>'
                    + '<button class="linea__quitar" type="button" data-quitar="' + producto.slug + '" aria-label="Quitar ' + producto.nombre + ' de la canasta">Quitar</button>'
                    + '</div>'
                    + '</li>';
            }).join('');
        }

        if (total) {
            total.textContent = soles(subtotal());
        }
    }

    $$('[data-canasta-abrir]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            var panel = $('[data-canasta-panel]');

            if (!panel) {
                window.location.href = 'canasta.html';

                return;
            }

            panel.removeAttribute('hidden');
            document.body.classList.add('con-panel');
            $('[data-canasta-cerrar]') && $('[data-canasta-cerrar]').focus();
        });
    });

    $$('[data-canasta-cerrar]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            var panel = $('[data-canasta-panel]');

            panel && panel.setAttribute('hidden', '');
            document.body.classList.remove('con-panel');
        });
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key !== 'Escape') {
            return;
        }

        var panel = $('[data-canasta-panel]');

        if (panel && !panel.hasAttribute('hidden')) {
            panel.setAttribute('hidden', '');
            document.body.classList.remove('con-panel');
        }
    });

    document.addEventListener('click', function (evento) {
        var menos = evento.target.closest('[data-cantidad="menos"]');
        var mas = evento.target.closest('[data-cantidad="mas"]');
        var quitarBoton = evento.target.closest('[data-quitar]');
        var vaciarBoton = evento.target.closest('[data-vaciar]');

        if (menos) {
            var slugMenos = menos.getAttribute('data-slug');
            var lineaMenos = lineaDe(slugMenos);

            if (lineaMenos) {
                lineaMenos.cantidad > 1 ? poner(slugMenos, lineaMenos.cantidad - 1) : quitar(slugMenos);
            }
        }

        if (mas) {
            var slugMas = mas.getAttribute('data-slug');
            var lineaMas = lineaDe(slugMas);

            poner(slugMas, (lineaMas ? lineaMas.cantidad : 0) + 1);
        }

        if (quitarBoton) {
            quitar(quitarBoton.getAttribute('data-quitar'));
        }

        if (vaciarBoton) {
            vaciar();
        }
    });

    document.addEventListener('change', function (evento) {
        var campo = evento.target.closest('[data-cantidad-campo]');

        if (campo) {
            poner(campo.getAttribute('data-slug'), parseInt(campo.value, 10) || 1);
        }
    });

    /* =====================================================================
       5. La portada: rubros, populares, ofertas y lo más vendido
       ===================================================================== */

    (function () {
        var destino = $('[data-rubros]');

        if (destino) {
            destino.innerHTML = RUBROS.map(function (rubro) {
                var total = PRODUCTOS.filter(function (producto) {
                    return producto.rubro === rubro.clave;
                }).length;

                return '<a class="rubro" href="tienda.html?rubro=' + rubro.clave + '">'
                    + '<img src="' + rubro.foto + '" alt="' + rubro.alt + '" width="800" height="600" loading="lazy">'
                    + '<span class="rubro__cuerpo">'
                    + '<strong>' + rubro.nombre + '</strong>'
                    + '<small>' + rubro.resumen + '</small>'
                    + '<em>' + total + ' productos de demostración</em>'
                    + '</span>'
                    + '</a>';
            }).join('');
        }

        var populares = $('[data-populares]');

        if (populares) {
            var destacados = PRODUCTOS.filter(function (producto) {
                return producto.destacado;
            }).slice(0, 4);

            rejilla(populares, destacados);
        }

        var ofertas = $('[data-ofertas]');

        if (ofertas) {
            var enOferta = PRODUCTOS.filter(function (producto) {
                return producto.antes;
            }).slice(0, 4);

            rejilla(ofertas, enOferta);
        }

        var necesidades = $('[data-necesidades]');

        if (necesidades) {
            necesidades.innerHTML = NECESIDADES.map(function (necesidad) {
                return '<a class="necesidad" href="tienda.html?necesidad=' + necesidad.clave + '">'
                    + '<img src="' + necesidad.foto + '" alt="" width="700" height="700" loading="lazy">'
                    + '<span><strong>' + necesidad.nombre + '</strong><small>' + necesidad.texto + '</small></span>'
                    + '</a>';
            }).join('');
        }

        /* Pestañas de «Lo más vendido»: cambian los productos de verdad. */
        var pestanas = $('[data-grupos]');
        var grupoPanel = $('[data-grupos-panel]');

        if (pestanas && grupoPanel) {
            pestanas.innerHTML = GRUPOS.map(function (grupo, indice) {
                return '<button type="button" role="tab" data-grupo="' + grupo.clave + '" aria-selected="' + (indice === 0 ? 'true' : 'false') + '">' + grupo.nombre + '</button>';
            }).join('');

            function abrirGrupo(clave) {
                $$('[data-grupo]', pestanas).forEach(function (boton) {
                    boton.setAttribute('aria-selected', boton.getAttribute('data-grupo') === clave ? 'true' : 'false');
                });

                var delGrupo = PRODUCTOS.filter(function (producto) {
                    return producto.grupo === clave;
                }).slice(0, 4);

                rejilla(grupoPanel, delGrupo, 'En este pasillo la demostración todavía no tiene productos cargados.');
            }

            pestanas.addEventListener('click', function (evento) {
                var boton = evento.target.closest('[data-grupo]');

                if (boton) {
                    abrirGrupo(boton.getAttribute('data-grupo'));
                }
            });

            abrirGrupo(GRUPOS.length ? GRUPOS[0].clave : '');
        }
    })();

    /* =====================================================================
       6. El catálogo (tienda.html)
       ===================================================================== */

    (function () {
        var catalogo = $('[data-catalogo]');

        if (!catalogo) {
            return;
        }

        var campo = $('[data-catalogo-buscador]');
        var orden = $('[data-catalogo-orden]');
        var filtros = $('[data-catalogo-filtros]');
        var resumen = $('[data-catalogo-resumen]');
        var estado = estadoDeLaUrl();

        function estadoDeLaUrl() {
            var parametros = new URLSearchParams(window.location.search);

            return {
                rubro: parametros.get('rubro') || '',
                necesidad: parametros.get('necesidad') || '',
                q: parametros.get('q') || '',
                orden: parametros.get('orden') || 'relevancia'
            };
        }

        function escribirUrl() {
            var parametros = new URLSearchParams();

            if (estado.rubro) {
                parametros.set('rubro', estado.rubro);
            }

            if (estado.necesidad) {
                parametros.set('necesidad', estado.necesidad);
            }

            if (estado.q) {
                parametros.set('q', estado.q);
            }

            if (estado.orden && estado.orden !== 'relevancia') {
                parametros.set('orden', estado.orden);
            }

            var consulta = parametros.toString();

            window.history.replaceState({}, '', window.location.pathname + (consulta ? '?' + consulta : ''));
        }

        function filtrar() {
            var lista = PRODUCTOS.slice();

            if (estado.rubro) {
                lista = lista.filter(function (producto) {
                    return producto.rubro === estado.rubro;
                });
            }

            if (estado.necesidad) {
                var necesidad = null;

                for (var i = 0; i < NECESIDADES.length; i++) {
                    if (NECESIDADES[i].clave === estado.necesidad) {
                        necesidad = NECESIDADES[i];
                    }
                }

                if (necesidad) {
                    lista = lista.filter(function (producto) {
                        if (necesidad.promos && !producto.antes) {
                            return false;
                        }

                        return !necesidad.rubros.length || necesidad.rubros.indexOf(producto.rubro) !== -1;
                    });
                }
            }

            if (estado.q) {
                var aguja = llano(estado.q);

                lista = lista.filter(function (producto) {
                    var heno = llano(producto.nombre + ' ' + producto.resumen + ' ' + producto.presentacion + ' ' + (porRubro(producto.rubro) || {}).nombre);

                    return heno.indexOf(aguja) !== -1;
                });
            }

            if (estado.orden === 'precio-asc') {
                lista.sort(function (a, b) {
                    return a.precio - b.precio;
                });
            }

            if (estado.orden === 'precio-desc') {
                lista.sort(function (a, b) {
                    return b.precio - a.precio;
                });
            }

            if (estado.orden === 'nombre') {
                lista.sort(function (a, b) {
                    return a.nombre.localeCompare(b.nombre, 'es');
                });
            }

            return lista;
        }

        function pintarFiltros() {
            if (!filtros) {
                return;
            }

            filtros.innerHTML = '<button type="button" data-rubro="" class="' + (estado.rubro === '' && !estado.necesidad ? 'esta-activo' : '') + '">Todo el catálogo</button>'
                + RUBROS.map(function (rubro) {
                    return '<button type="button" data-rubro="' + rubro.clave + '" class="' + (estado.rubro === rubro.clave ? 'esta-activo' : '') + '">' + rubro.nombre + '</button>';
                }).join('')
                + NECESIDADES.map(function (necesidad) {
                    return '<button type="button" data-necesidad="' + necesidad.clave + '" class="' + (estado.necesidad === necesidad.clave ? 'esta-activo' : '') + '">' + necesidad.nombre + '</button>';
                }).join('');
        }

        function pintar() {
            var lista = filtrar();

            rejilla(catalogo, lista, 'No encontramos productos con esa búsqueda. Prueba con otra palabra o quita los filtros.');

            if (resumen) {
                var partes = [];

                if (estado.rubro && porRubro(estado.rubro)) {
                    partes.push(porRubro(estado.rubro).nombre);
                }

                for (var i = 0; i < NECESIDADES.length; i++) {
                    if (NECESIDADES[i].clave === estado.necesidad) {
                        partes.push(NECESIDADES[i].nombre);
                    }
                }

                if (estado.q) {
                    partes.push('«' + estado.q + '»');
                }

                resumen.textContent = lista.length + (lista.length === 1 ? ' producto' : ' productos')
                    + (partes.length ? ' en ' + partes.join(' · ') : ' en toda la tienda')
                    + ' · precios y existencias ficticios';
            }

            pintarFiltros();
            escribirUrl();
        }

        if (campo) {
            campo.value = estado.q;

            campo.addEventListener('input', function () {
                estado.q = campo.value.trim();
                pintar();
            });
        }

        if (orden) {
            orden.value = estado.orden;

            orden.addEventListener('change', function () {
                estado.orden = orden.value;
                pintar();
            });
        }

        filtros && filtros.addEventListener('click', function (evento) {
            var boton = evento.target.closest('button');

            if (!boton) {
                return;
            }

            if (boton.hasAttribute('data-rubro')) {
                estado.rubro = boton.getAttribute('data-rubro');
                estado.necesidad = '';
            } else if (boton.hasAttribute('data-necesidad')) {
                estado.necesidad = boton.getAttribute('data-necesidad');
                estado.rubro = '';
            }

            pintar();
        });

        pintar();
    })();

    /* =====================================================================
       7. La ficha de producto (producto.html) y la canasta (canasta.html)
       ===================================================================== */

    (function () {
        var ficha = $('[data-ficha]');

        if (ficha) {
            var parametros = new URLSearchParams(window.location.search);
            var producto = porSlug(parametros.get('p') || '') || PRODUCTOS[0];

            if (!producto) {
                return;
            }

            document.title = producto.nombre + ' — VERDIA Market · Demostración de plantilla';

            var rubro = porRubro(producto.rubro) || { nombre: '', clave: '' };

            $('[data-ficha-rubro]').innerHTML = '<a href="tienda.html?rubro=' + rubro.clave + '">' + rubro.nombre + '</a>';
            $('[data-ficha-nombre]').textContent = producto.nombre;
            $('[data-ficha-resumen]').textContent = producto.resumen;
            $('[data-ficha-detalle]').textContent = producto.detalle;
            $('[data-ficha-presentacion]').textContent = producto.presentacion;
            $('[data-ficha-precio]').textContent = soles(producto.precio);
            $('[data-ficha-precio-antes]').innerHTML = producto.antes
                ? '<span class="ficha__antes">' + soles(producto.antes) + '</span><span class="ficha__ahorro">Ahorras ' + soles(producto.antes - producto.precio) + '</span>'
                : '';
            $('[data-ficha-insignia]').innerHTML = producto.etiqueta
                ? '<span class="pieza__insignia' + (producto.antes ? ' pieza__insignia--oferta' : '') + '">' + producto.etiqueta + '</span>'
                : '';

            var imagen = $('[data-ficha-imagen]');
            imagen.setAttribute('src', producto.foto);
            imagen.setAttribute('alt', producto.alt);

            $('[data-ficha-agregar]').setAttribute('data-agregar', producto.slug);

            var relacionados = PRODUCTOS.filter(function (otro) {
                return otro.rubro === producto.rubro && otro.slug !== producto.slug;
            }).slice(0, 4);

            rejilla($('[data-ficha-relacionados]'), relacionados, 'Es el único producto de este pasillo en la demostración.');
        }

        var pagina = $('[data-canasta-pagina]');

        if (pagina) {
            pintarPaginaCanasta();
        }
    })();

    function pintarPaginaCanasta() {
        var lineas = $('[data-canasta-lineas]');
        var vacia = $('[data-canasta-vacia]');
        var resumen = $('[data-canasta-resumen]');
        var pie = $('[data-canasta-pie]');

        if (!lineas) {
            return;
        }

        if (!canasta.length) {
            lineas.innerHTML = '';
            vacia && vacia.removeAttribute('hidden');
            resumen && resumen.setAttribute('hidden', '');
            pie && pie.setAttribute('hidden', '');
            pintarTotales();

            return;
        }

        vacia && vacia.setAttribute('hidden', '');
        resumen && resumen.removeAttribute('hidden');
        pie && pie.removeAttribute('hidden');

        lineas.innerHTML = canasta.map(function (linea) {
            var producto = porSlug(linea.slug);

            return '<tr data-linea="' + producto.slug + '">'
                + '<td class="tabla__producto">'
                + '<img src="' + producto.foto + '" alt="" width="72" height="72" loading="lazy">'
                + '<span><strong>' + producto.nombre + '</strong><small>' + producto.presentacion + '</small></span>'
                + '</td>'
                + '<td>' + soles(producto.precio) + '</td>'
                + '<td>'
                + '<div class="cantidad">'
                + '<button type="button" data-cantidad="menos" data-slug="' + producto.slug + '" aria-label="Quitar una unidad de ' + producto.nombre + '">−</button>'
                + '<input type="number" min="1" max="99" value="' + linea.cantidad + '" data-cantidad-campo data-slug="' + producto.slug + '" aria-label="Cantidad de ' + producto.nombre + '">'
                + '<button type="button" data-cantidad="mas" data-slug="' + producto.slug + '" aria-label="Añadir una unidad de ' + producto.nombre + '">+</button>'
                + '</div>'
                + '</td>'
                + '<td class="tabla__importe">' + soles(producto.precio * linea.cantidad) + '</td>'
                + '<td><button class="tabla__quitar" type="button" data-quitar="' + producto.slug + '">Quitar</button></td>'
                + '</tr>';
        }).join('');

        pintarTotales();
    }

    function pintarTotales() {
        var sub = subtotal();
        var nodoSub = $('[data-total-subtotal]');
        var nodoEnvio = $('[data-total-envio]');
        var nodoTotal = $('[data-total-total]');
        var nodoContar = $('[data-total-unidades]');

        nodoSub && (nodoSub.textContent = soles(sub));
        nodoEnvio && (nodoEnvio.textContent = 'Se calcula al confirmar');
        nodoTotal && (nodoTotal.textContent = soles(sub));
        nodoContar && (nodoContar.textContent = String(contar()));
    }

    /* Simulación de pedido: valida y explica, sin enviar ni cobrar nada. */
    (function () {
        var formulario = $('[data-pedido]');

        if (!formulario) {
            return;
        }

        var recibo = $('[data-pedido-recibo]');
        var error = $('[data-pedido-error]');

        formulario.addEventListener('submit', function (evento) {
            evento.preventDefault();

            var nombre = $('#pedido-nombre');
            var telefono = $('#pedido-telefono');
            var entrega = $('#pedido-entrega');
            var fallos = [];

            if (nombre.value.trim().length < 3) {
                fallos.push('Escribe un nombre de al menos tres letras.');
            }

            if (telefono.value.replace(/\D/g, '').length < 6) {
                fallos.push('Escribe un teléfono de contacto de prueba.');
            }

            if (entrega.value === '') {
                fallos.push('Elige una forma de entrega.');
            }

            if (!canasta.length) {
                fallos.push('La canasta está vacía: añade algún producto antes de confirmar.');
            }

            if (fallos.length) {
                error.innerHTML = '<strong>Falta completar el pedido de prueba:</strong><ul><li>' + fallos.join('</li><li>') + '</li></ul>';
                error.removeAttribute('hidden');

                return;
            }

            error.setAttribute('hidden', '');

            var detalle = canasta.map(function (linea) {
                var producto = porSlug(linea.slug);

                return linea.cantidad + ' × ' + producto.nombre + ' (' + producto.presentacion + ') — ' + soles(producto.precio * linea.cantidad);
            }).join('<br>');

            recibo.innerHTML = '<h3>Pedido de prueba ' + 'V-' + String(Date.now()).slice(-6) + '</h3>'
                + '<p class="recibo__aviso">Esta es una simulación de la plantilla: no se envió ningún pedido, no se guardó ningún dato personal y no se cobró nada. En la tienda real, este es el detalle que viaja al negocio.</p>'
                + '<p><strong>Cliente:</strong> ' + nombre.value.trim() + ' · <strong>Teléfono:</strong> ' + telefono.value.trim() + ' · <strong>Entrega:</strong> ' + entrega.value + '</p>'
                + '<p class="recibo__lineas">' + detalle + '</p>'
                + '<p class="recibo__total"><strong>Total de referencia:</strong> ' + soles(subtotal()) + '</p>';

            recibo.removeAttribute('hidden');
            recibo.scrollIntoView({ behavior: movimientoReducido ? 'auto' : 'smooth', block: 'center' });
        });
    })();

    /* Boletín de novedades: valida el correo y aclara que no envía nada. */
    (function () {
        var formulario = $('[data-boletin]');

        if (!formulario) {
            return;
        }

        formulario.addEventListener('submit', function (evento) {
            evento.preventDefault();

            var campo = $('input[type="email"]', formulario);
            var salida = $('[data-boletin-mensaje]', formulario);
            var correo = campo.value.trim();

            if (!/^[^\s@]+@[^\s@]+\.[a-z]{2,}$/i.test(correo)) {
                salida.textContent = 'Revisa el correo: falta el arroba o la terminación.';
                salida.classList.add('esta-error');

                return;
            }

            salida.classList.remove('esta-error');
            salida.textContent = 'Listo: en la demostración tu correo no se guarda ni se envía ningún mensaje. En la tienda real aquí empezaría la suscripción.';
            campo.value = '';
        });
    })();

    /* Aparición suave de las secciones al bajar, si el visitante no pide menos movimiento. */
    (function () {
        if (movimientoReducido || !('IntersectionObserver' in window)) {
            return;
        }

        var observador = new IntersectionObserver(function (entradas) {
            entradas.forEach(function (entrada) {
                if (entrada.isIntersecting) {
                    entrada.target.classList.add('esta-visible');
                    observador.unobserve(entrada.target);
                }
            });
        }, { rootMargin: '0px 0px -60px' });

        $$('[data-aparece]').forEach(function (nodo) {
            observador.observe(nodo);
        });
    })();

    /* Año del pie, para no dejar una fecha escrita a mano. */
    $$('[data-anio]').forEach(function (nodo) {
        nodo.textContent = String(new Date().getFullYear());
    });

    pintarCuenta();
    pintarPanel();
})();
