{{--
    Modal de registro de lectores del blog
    ---------------------------------------------------------------------------
    Lo abren los botones marcados con `data-blog-register` en el listado y en
    el artículo. Envía al alta de lectores (`blog.register`), que crea la
    cuenta con teléfono y país, le asigna el rol Alumno y avisa al
    Integrationhub.

    Los mensajes se muestran dentro del propio modal: el sitio público no
    carga SweetAlert ni el puente de alertas del panel.
--}}
@php
    $paises = isset($countries) ? $countries : collect();
@endphp

<div class="ara-modal" id="blogRegisterModal" hidden>
    <div class="ara-modal-fondo" data-blog-register-cerrar></div>

    <div class="ara-modal-ventana" role="dialog" aria-modal="true"
         aria-labelledby="blogRegisterTitulo" aria-describedby="blogRegisterAyuda">
        <button type="button" class="ara-modal-cerrar" data-blog-register-cerrar aria-label="Cerrar el formulario">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

        <h2 class="ara-modal-titulo" id="blogRegisterTitulo">Crea tu cuenta</h2>
        <p class="ara-modal-ayuda" id="blogRegisterAyuda">
            Regístrate para descargar el material de los artículos y recibir contenido de valor.
        </p>

        <form id="blogRegisterForm" action="{{ route('blog.register') }}" method="POST" novalidate>
            @csrf

            <div class="ara-modal-campos">
                <div class="ara-modal-ancho">
                    <label class="ara-modal-etiqueta" for="blogRegisterNombre">Nombre completo *</label>
                    <input type="text" id="blogRegisterNombre" name="name" required maxlength="255"
                           class="ara-input" placeholder="Tu nombre y apellidos" autocomplete="name">
                </div>

                <div class="ara-modal-ancho">
                    <label class="ara-modal-etiqueta" for="blogRegisterEmail">Correo electrónico *</label>
                    <input type="email" id="blogRegisterEmail" name="email" required maxlength="255"
                           class="ara-input" placeholder="tu@email.com" autocomplete="email">
                </div>

                <div>
                    <label class="ara-modal-etiqueta" for="blogRegisterTelefono">Teléfono / celular *</label>
                    <input type="tel" id="blogRegisterTelefono" name="telefono" required maxlength="20"
                           class="ara-input" placeholder="+51 999 999 999" autocomplete="tel">
                </div>

                <div>
                    <label class="ara-modal-etiqueta" for="blogRegisterPais">País *</label>
                    <select id="blogRegisterPais" name="country_id" required class="ara-input">
                        <option value="">Selecciona tu país</option>
                        @foreach ($paises as $pais)
                            <option value="{{ $pais->id }}">{{ $pais->description }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="ara-modal-etiqueta" for="blogRegisterPassword">Contraseña *</label>
                    <input type="password" id="blogRegisterPassword" name="password" required minlength="8"
                           class="ara-input" placeholder="Mínimo 8 caracteres" autocomplete="new-password">
                </div>

                <div>
                    <label class="ara-modal-etiqueta" for="blogRegisterPasswordConfirm">Confirmar contraseña *</label>
                    <input type="password" id="blogRegisterPasswordConfirm" name="password_confirmation" required minlength="8"
                           class="ara-input" placeholder="Repite la contraseña" autocomplete="new-password">
                </div>
            </div>

            <div class="flex items-start gap-3 text-sm mt-4">
                <input type="checkbox" id="blogRegisterAcepto" name="acepto" value="1" required class="mt-1">
                <label for="blogRegisterAcepto">
                    He leído y acepto los
                    <a href="{{ route('terminos_condiciones') }}" target="_blank" rel="noopener" class="text-ara-blue hover:underline">Términos y Condiciones</a>
                    y la
                    <a href="{{ route('politica_privacidad') }}" target="_blank" rel="noopener" class="text-ara-blue hover:underline">Política de Privacidad</a>.
                </label>
            </div>

            <p class="ara-modal-error" id="blogRegisterError" role="alert" hidden></p>

            <button type="submit" class="ara-btn ara-btn-primary ara-btn-lg ara-modal-enviar mt-4">
                Crear cuenta
                <svg class="ara-modal-enviar-icono" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
            </button>
        </form>

        <div class="ara-modal-exito" id="blogRegisterExito" hidden>
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h3>¡Cuenta creada!</h3>
            <p id="blogRegisterExitoTexto">Ya formas parte del blog de ARACODE.</p>
        </div>

        <p class="text-center text-sm mt-4">
            ¿Ya tienes cuenta?
            <a href="{{ route('login') }}" class="text-ara-blue hover:underline">Inicia sesión</a>
        </p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('blogRegisterModal');
    var formulario = document.getElementById('blogRegisterForm');

    if (!modal || !formulario) {
        return;
    }

    var ventana = modal.querySelector('.ara-modal-ventana');
    var alerta = document.getElementById('blogRegisterError');
    var exito = document.getElementById('blogRegisterExito');
    var exitoTexto = document.getElementById('blogRegisterExitoTexto');
    var botones = document.querySelectorAll('[data-blog-register]');
    var botonDeOrigen = null;

    function ocultarError() {
        alerta.hidden = true;
        alerta.innerHTML = '';
    }

    function abrirModal(disparador) {
        botonDeOrigen = disparador || null;

        var menu = document.querySelector('.ara-mobile-menu');
        if (menu) {
            menu.classList.remove('active');
        }

        ocultarError();
        modal.hidden = false;
        document.body.style.overflow = 'hidden';

        var primero = formulario.querySelector('.ara-modal-campos input, .ara-modal-campos select');
        if (primero) {
            primero.focus();
        }
    }

    function cerrarModal() {
        modal.hidden = true;
        document.body.style.overflow = '';
        ocultarError();

        if (botonDeOrigen) {
            botonDeOrigen.focus();
        }
    }

    botones.forEach(function (boton) {
        boton.addEventListener('click', function (evento) {
            evento.preventDefault();
            abrirModal(boton);
        });
    });

    modal.querySelectorAll('[data-blog-register-cerrar]').forEach(function (cerrar) {
        cerrar.addEventListener('click', cerrarModal);
    });

    modal.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
            cerrarModal();
            return;
        }

        if (evento.key !== 'Tab') {
            return;
        }

        var enfocables = Array.prototype.filter.call(
            ventana.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled])'),
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

    function mostrarError(mensajes) {
        var titulo = document.createElement('strong');
        titulo.textContent = mensajes.length > 1 ? 'Revisa estos datos:' : 'No pudimos crear tu cuenta:';

        alerta.innerHTML = '';
        alerta.appendChild(titulo);

        mensajes.forEach(function (mensaje) {
            var linea = document.createElement('p');
            linea.textContent = mensaje;
            alerta.appendChild(linea);
        });

        alerta.hidden = false;
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
        boton.textContent = 'Creando cuenta...';

        fetch(formulario.action, {
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
        }).then(function (resultado) {
            boton.disabled = false;
            boton.innerHTML = textoOriginal;

            if (resultado.estado === 200 && resultado.datos.success) {
                exitoTexto.textContent = resultado.datos.message || 'Ya formas parte del blog de ARACODE.';
                formulario.hidden = true;
                exito.hidden = false;

                // La página se recarga con la sesión iniciada para que el
                // artículo muestre el botón de descarga del PDF.
                setTimeout(function () { window.location.reload(); }, 1200);

                return;
            }

            if (resultado.estado === 422) {
                mostrarError(mensajesDeError(resultado.datos.errors));

                return;
            }

            mostrarError([resultado.datos.message || 'No pudimos crear tu cuenta. Intenta nuevamente.']);
        }).catch(function () {
            boton.disabled = false;
            boton.innerHTML = textoOriginal;
            mostrarError(['No pudimos crear tu cuenta. Revisa tu conexión e intenta nuevamente.']);
        });
    });
});
</script>
