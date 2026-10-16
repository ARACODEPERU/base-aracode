{{--
    Modal de asesoría («Solicitar Asesoría»)
    ---------------------------------------------------------------------------
    Los botones que piden asesoría abren este modal en vez de saltar a
    /contacto: el visitante no pierde la página que estaba leyendo. Son los
    marcados con `data-asesoria` (navbar, hero, CTA de las páginas y las
    tarjetas del ecosistema).

    El formulario es el mismo de /contacto y envía al mismo destino
    (`contacto_store`): el mensaje se guarda en `contact_messages` —que es lo
    que lista /cms/contact-messages— y dispara el mismo aviso por correo al
    admin.

    Sin JavaScript nada de esto carga: cada botón conserva su `href` a
    /contacto, así que el formulario completo sigue a un clic de distancia.
--}}
@php
    // Mismo captcha que /contacto, leído de la misma config: el día que las
    // claves estén en el .env, el modal lo pide igual que la página.
    $recaptchaSiteKey = config('services.recaptcha.site_key');
    $recaptchaSecretKey = config('services.recaptcha.secret_key');
    $recaptchaActivo = filled($recaptchaSiteKey) && filled($recaptchaSecretKey);
    $recaptchaVersion = config('services.recaptcha.version', 'v3');
@endphp

<div class="ara-modal" id="asesoriaModal" hidden
     data-captcha-version="{{ $recaptchaActivo ? $recaptchaVersion : '' }}"
     data-captcha-sitekey="{{ $recaptchaActivo ? $recaptchaSiteKey : '' }}">
    {{-- Fondo: cubre la página y también cierra el modal al pulsarlo. --}}
    <div class="ara-modal-fondo" data-asesoria-cerrar></div>

    {{-- El nombre de la clase evita la palabra «panel»: /paginas-web promete
         justo lo contrario (que el panel del cliente todavía no existe) y su
         prueba rechaza esa palabra en toda la página. --}}
    <div class="ara-modal-ventana" role="dialog" aria-modal="true"
         aria-labelledby="asesoriaTitulo" aria-describedby="asesoriaAyuda">
        <button type="button" class="ara-modal-cerrar" data-asesoria-cerrar aria-label="Cerrar el formulario">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

        <h2 class="ara-modal-titulo" id="asesoriaTitulo">Solicitar asesoría</h2>
        <p class="ara-modal-ayuda" id="asesoriaAyuda">
            Cuéntanos qué necesitas y te contactamos. Respondemos en menos de 24 horas.
        </p>

        <form id="asesoriaForm" action="{{ route('contacto_store') }}" method="POST">
            @csrf

            {{-- Trampa antispam: invisible para las personas —fuera de pantalla,
                 sin foco y escondida a los lectores de pantalla— pero presente en
                 el HTML, que es justo lo que rellenan los bots. El controlador
                 descarta en silencio los envíos que traigan algo aquí. --}}
            <div class="ara-trampa" aria-hidden="true">
                <label for="asesoriaSitioWeb">Sitio web</label>
                <input type="text" id="asesoriaSitioWeb" name="website" tabindex="-1"
                       autocomplete="off" class="ara-input" value="">
            </div>

            <div class="ara-modal-campos">
                <div>
                    <label class="ara-modal-etiqueta" for="asesoriaNombre">Nombre *</label>
                    <input type="text" id="asesoriaNombre" name="name" required maxlength="255"
                           class="ara-input" placeholder="Tu nombre" autocomplete="name">
                </div>
                <div>
                    <label class="ara-modal-etiqueta" for="asesoriaEmail">Email *</label>
                    <input type="email" id="asesoriaEmail" name="email" required maxlength="255"
                           class="ara-input" placeholder="tu@email.com" autocomplete="email">
                </div>
                <div>
                    <label class="ara-modal-etiqueta" for="asesoriaTelefono">Teléfono</label>
                    <input type="tel" id="asesoriaTelefono" name="phone" maxlength="20"
                           class="ara-input" placeholder="+51 999 999 999" autocomplete="tel">
                </div>
                <div>
                    <label class="ara-modal-etiqueta" for="asesoriaEmpresa">Empresa</label>
                    <input type="text" id="asesoriaEmpresa" name="company" maxlength="255"
                           class="ara-input" placeholder="Nombre de tu empresa" autocomplete="organization">
                </div>
                <div class="ara-modal-ancho">
                    <label class="ara-modal-etiqueta" for="asesoriaServicio">¿En qué podemos ayudarte? *</label>
                    <select id="asesoriaServicio" name="service" required class="ara-input">
                        <option value="">Selecciona un servicio</option>
                        <option value="kapta">KAPTA LMS</option>
                        <option value="facturacion">Facturación Electrónica</option>
                        <option value="desarrollo">Desarrollo a Medida</option>
                        <option value="automatizacion">Automatización de Procesos</option>
                        <option value="consultoria">Consultoría Tecnológica</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
                <div class="ara-modal-ancho">
                    <label class="ara-modal-etiqueta" for="asesoriaMensaje">Mensaje *</label>
                    <textarea id="asesoriaMensaje" name="message" required maxlength="2000" rows="4"
                              class="ara-input ara-textarea" placeholder="Cuéntanos sobre tu proyecto o necesidad..."></textarea>
                </div>
            </div>

            @if ($recaptchaActivo)
                @if ($recaptchaVersion === 'v2')
                    {{-- v2: casilla visible. El widget se dibuja al abrir el modal
                         (`grecaptcha.render`), porque al cargar la página todavía
                         está oculto y el script de Google no lo vería. --}}
                    <div class="ara-modal-captcha" id="asesoriaCaptcha" data-sitekey="{{ $recaptchaSiteKey }}"></div>
                @else
                    {{-- v3: invisible. El JS escribe aquí el token justo antes de enviar. --}}
                    <input type="hidden" name="g-recaptcha-response" id="asesoriaRecaptcha"
                           data-sitekey="{{ $recaptchaSiteKey }}" value="">
                @endif
            @endif

            {{-- Consentimiento del titular: el modal envia al mismo destino que
                 /contacto, asi que el servidor tambien exige esta aceptacion
                 (regla "acepto" accepted). --}}
            <div class="flex items-start gap-3 text-sm">
                <input type="checkbox" id="asesoriaAcepto" name="acepto" value="1" required class="mt-1">
                <label for="asesoriaAcepto">
                    He leído y acepto la
                    <a href="{{ route('politica_privacidad') }}" target="_blank" rel="noopener" class="text-ara-blue hover:underline">Política de Privacidad</a>
                    para que ARACODE trate mis datos con la finalidad de atender esta solicitud.
                </label>
            </div>

            <p class="ara-modal-error" id="asesoriaError" role="alert" hidden></p>

            <button type="submit" class="ara-btn ara-btn-primary ara-btn-lg ara-modal-enviar">
                Enviar solicitud
                <svg class="ara-modal-enviar-icono" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
            </button>
        </form>

        {{-- Confirmación: al enviar bien se oculta el formulario y se muestra
             esto, así el visitante no pierde la página ni el contexto. --}}
        <div class="ara-modal-exito" id="asesoriaExito" hidden>
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h3>¡Solicitud enviada!</h3>
            <p id="asesoriaExitoTexto">Nos pondremos en contacto contigo pronto.</p>
            <button type="button" class="ara-btn ara-btn-primary ara-btn-lg" data-asesoria-cerrar>Cerrar</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('asesoriaModal');
    var formulario = document.getElementById('asesoriaForm');

    if (!modal || !formulario) {
        return;
    }

    var ventana = modal.querySelector('.ara-modal-ventana');
    var alerta = document.getElementById('asesoriaError');
    var exito = document.getElementById('asesoriaExito');
    var exitoTexto = document.getElementById('asesoriaExitoTexto');
    var botones = document.querySelectorAll('[data-asesoria]');
    var botonDeOrigen = null;
    var captchaV3 = formulario.querySelector('#asesoriaRecaptcha');
    var captchaV2 = document.getElementById('asesoriaCaptcha');
    var versionCaptcha = modal.dataset.captchaVersion || '';
    var sitekeyCaptcha = modal.dataset.captchaSitekey || '';
    var widgetCaptcha = null;
    var apiCaptcha = null;

    // ---------------------------------------------------------------- captcha
    // Se carga el script de Google solo si la página no lo trae ya (en
    // /contacto lo trae su propio formulario) y solo si hay claves.
    function cargarApiCaptcha() {
        if (window.grecaptcha) {
            return Promise.resolve();
        }

        if (apiCaptcha) {
            return apiCaptcha;
        }

        apiCaptcha = new Promise(function (resolver) {
            var existente = document.querySelector('script[src*="recaptcha/api.js"]');

            if (existente) {
                existente.addEventListener('load', resolver);
                existente.addEventListener('error', resolver);
                return;
            }

            // v3 necesita la clave EN LA URL: sin ella, `grecaptcha.execute`
            // responde «Invalid site key or not loaded in api.js». v2, en
            // cambio, se dibuja a mano y pide el modo explícito.
            var script = document.createElement('script');
            script.src = versionCaptcha === 'v2'
                ? 'https://www.google.com/recaptcha/api.js?render=explicit'
                : 'https://www.google.com/recaptcha/api.js?render=' + encodeURIComponent(sitekeyCaptcha);
            script.async = true;
            script.defer = true;
            script.onload = resolver;
            script.onerror = resolver;
            document.head.appendChild(script);
        });

        return apiCaptcha;
    }

    // v3: el token es invisible y se pide justo antes de enviar, porque cada
    // token sirve una sola vez. Si Google no responde a tiempo, lanza o devuelve
    // un token vacío, se envía igual y el servidor contesta con su mensaje de
    // captcha: el botón nunca se queda en «Enviando...» para siempre.
    function tokenCaptcha() {
        if (!captchaV3) {
            return Promise.resolve('');
        }

        return cargarApiCaptcha().then(function () {
            return new Promise(function (resolver) {
                var resuelto = false;

                function terminar(token) {
                    if (resuelto) {
                        return;
                    }

                    resuelto = true;
                    resolver(token || '');
                }

                setTimeout(function () { terminar(''); }, 8000);

                if (!window.grecaptcha) {
                    return terminar('');
                }

                try {
                    window.grecaptcha.ready(function () {
                        var ejecucion;

                        try {
                            ejecucion = window.grecaptcha.execute(sitekeyCaptcha, { action: 'contacto_submit' });
                        } catch (error) {
                            return terminar('');
                        }

                        ejecucion.then(terminar).catch(function () { terminar(''); });
                    });
                } catch (error) {
                    terminar('');
                }
            });
        });
    }

    // v2: la casilla se dibuja al abrir el modal (oculto no se puede dibujar).
    function prepararCaptchaV2() {
        if (!captchaV2 || widgetCaptcha !== null) {
            return;
        }

        cargarApiCaptcha().then(function () {
            if (!window.grecaptcha || widgetCaptcha !== null) {
                return;
            }

            try {
                widgetCaptcha = window.grecaptcha.render(captchaV2, { sitekey: captchaV2.dataset.sitekey });
            } catch (error) {
                widgetCaptcha = 0;
            }
        });
    }

    // Un token ya usado no vale dos veces: la casilla se reinicia después de
    // cada intento para que el siguiente envío no viaje con uno gastado.
    function reiniciarCaptcha() {
        if (widgetCaptcha !== null && window.grecaptcha && typeof window.grecaptcha.reset === 'function') {
            try { window.grecaptcha.reset(widgetCaptcha); } catch (error) { /* el widget ya no está */ }
        }
    }

    // ------------------------------------------------------------ abrir cerrar
    function ocultarError() {
        alerta.hidden = true;
        alerta.innerHTML = '';
    }

    function abrirModal(disparador) {
        botonDeOrigen = disparador || null;

        // El menú móvil tapa el modal cuando está abierto: se cierra antes.
        var menu = document.querySelector('.ara-mobile-menu');
        if (menu) {
            menu.classList.remove('active');
        }

        ocultarError();
        modal.hidden = false;
        document.body.style.overflow = 'hidden';

        // El foco entra en el modal: es un diálogo, no un bloque más del fondo.
        // Se busca dentro de los campos a la vista: el campo trampa es invisible,
        // así que enfocarlo dejaría el foco perdido fuera de la pantalla.
        var primero = formulario.querySelector('.ara-modal-campos input:not([type="hidden"]), .ara-modal-campos select, .ara-modal-campos textarea');
        if (primero) {
            primero.focus();
        }

        prepararCaptchaV2();
    }

    function cerrarModal() {
        modal.hidden = true;
        document.body.style.overflow = '';

        // Si se envió bien, al volver a abrir se muestra otra vez el formulario.
        if (!exito.hidden) {
            exito.hidden = true;
            formulario.hidden = false;
        }

        ocultarError();
        reiniciarCaptcha();

        if (botonDeOrigen) {
            botonDeOrigen.focus();
        }
    }

    // Cada botón «Solicitar Asesoría» del sitio abre el modal. El `href` sigue
    // puesto: sin JavaScript el clic lleva a /contacto como siempre.
    botones.forEach(function (boton) {
        boton.addEventListener('click', function (evento) {
            evento.preventDefault();
            abrirModal(boton);
        });
    });

    modal.querySelectorAll('[data-asesoria-cerrar]').forEach(function (cerrar) {
        cerrar.addEventListener('click', cerrarModal);
    });

    // Escape cierra y el tabulador se queda dentro del diálogo.
    modal.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
            cerrarModal();
            return;
        }

        if (evento.key !== 'Tab') {
            return;
        }

        // `tabIndex >= 0` deja fuera lo que no se puede alcanzar con el teclado:
        // sin ese filtro, el ciclo pasaría por el campo trampa.
        var enfocables = Array.prototype.filter.call(
            ventana.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled])'),
            function (elemento) { return elemento.offsetParent !== null && elemento.tabIndex >= 0; }
        );

        if (enfocables.length === 0) {
            return;
        }

        var primero = enfocables[0];
        var ultimo = enfocables[enfocables.length - 1];

        if (evento.shiftKey && document.activeElement === primero) {
            evento.preventDefault();
            ultimo.focus();
        } else if (!evento.shiftKey && document.activeElement === ultimo) {
            evento.preventDefault();
            primero.focus();
        }
    });

    // -------------------------------------------------------------- enviar
    function mostrarError(mensajes) {
        var titulo = document.createElement('strong');
        titulo.textContent = mensajes.length > 1 ? 'Revisa estos datos:' : 'No pudimos enviar la solicitud:';

        alerta.innerHTML = '';
        alerta.appendChild(titulo);

        mensajes.forEach(function (mensaje) {
            var linea = document.createElement('p');
            linea.textContent = mensaje;
            alerta.appendChild(linea);
        });

        alerta.hidden = false;
        alerta.scrollIntoView({ block: 'nearest' });
    }

    function mensajesDeError(errores) {
        var mensajes = [];

        Object.keys(errores || {}).forEach(function (campo) {
            (errores[campo] || []).forEach(function (mensaje) {
                mensajes.push(mensaje);
            });
        });

        return mensajes.length > 0 ? mensajes : ['Revisa los datos del formulario e intenta nuevamente.'];
    }

    formulario.addEventListener('submit', function (evento) {
        evento.preventDefault();
        ocultarError();

        var boton = formulario.querySelector('button[type="submit"]');
        var textoOriginal = boton.innerHTML;
        boton.disabled = true;
        boton.innerHTML = '<svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Enviando...';

        tokenCaptcha().then(function (token) {
            if (captchaV3 && token) {
                captchaV3.value = token;
            }

            return fetch(formulario.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': formulario.querySelector('input[name="_token"]').value,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: new FormData(formulario)
            }).then(function (respuesta) {
                return respuesta.json()
                    .then(function (datos) { return { estado: respuesta.status, datos: datos }; })
                    .catch(function () { return { estado: respuesta.status, datos: {} }; });
            });
        }).then(function (resultado) {
            boton.disabled = false;
            boton.innerHTML = textoOriginal;

            if (resultado.estado === 200 && resultado.datos.success) {
                exitoTexto.textContent = resultado.datos.message || 'Nos pondremos en contacto contigo pronto.';
                formulario.reset();
                formulario.hidden = true;
                exito.hidden = false;
                exito.querySelector('button').focus();
                reiniciarCaptcha();

                return;
            }

            if (resultado.estado === 422) {
                mostrarError(mensajesDeError(resultado.datos.errors));
                reiniciarCaptcha();

                return;
            }

            mostrarError([resultado.datos.message || 'No se pudo enviar la solicitud. Intenta nuevamente.']);
            reiniciarCaptcha();
        }).catch(function () {
            boton.disabled = false;
            boton.innerHTML = textoOriginal;
            mostrarError(['No se pudo enviar la solicitud. Revisa tu conexión e intenta nuevamente.']);
            reiniciarCaptcha();
        });
    });
});
</script>
