<div>
    <meta name="csrf-token" content="{{ csrf_token() }}">
        <section class="section-dark relative">
            <div class="container">
                <div class="row align-items-center justify-content-center">
                    <div class="col-lg-6">
                        <div class="subtitle">Ponte en contacto</div>
                        <h2 class="wow fadeInUp">Estamos aquí para responder a sus preguntas.</h2>

                        <p class="col-lg-8">
                            Completa el formulario y nuestro equipo se comunicará contigo lo antes posible para brindarte la
                            información o asistencia que necesites.
                        </p>

                        <div class="spacer-single"></div>

                        <div class="row">
                            <div class="col-lg-12">
                                <div class="relative mb-4">
                                    <i class="abs fs-28 p-3 bg-color text-light rounded-1 icofont-location-pin"></i>
                                    <div class="ms-80px">
                                        <h4 class="mb-0">Dirección</h4>
                                        Nuevo Chimbote, Perú.
                                    </div>
                                </div>

                                <div class="relative mb-4">
                                    <i class="abs fs-28 p-3 bg-color text-light rounded-1 icofont-envelope"></i>
                                    <div class="ms-80px">
                                        <h4 class="mb-0">Enviar mensaje</h4>
                                        contacto@aracodeperu.com
                                    </div>
                                </div>

                                <div class="relative mb-4">
                                    <i class="abs fs-28 p-3 bg-color text-light rounded-1 icofont-phone"></i>
                                    <div class="ms-80px">
                                        <h4 class="mb-0">Llamanos al:</h4>
                                        (+51) 917 295 856
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>

                    <div class="col-lg-6">
                        <div class="bg-dark-2 rounded-1 p-60 relative">
                            <form name="contactForm" id="pageContactForm" method="post" action="#">
                                <div class="row g-4">
                                    <div class="col-lg-12">
                                        <h3>Ponte en contacto</h3>
                                        <p>
                                            Rellena el formulario a continuación y te responderemos pronto.
                                        </p>

                                        <div class="field-set">
                                            <input type="text" name="full_name" id="full_name" class="form-underline"
                                                placeholder="Nombre Completo" required>
                                        </div>

                                        <div class="field-set">
                                            <input type="text" name="email" id="email" class="form-underline"
                                                placeholder="Correo Electrónico" required>
                                        </div>

                                        <div class="field-set">
                                            <input type="text" name="phone" id="phone" class="form-underline"
                                                placeholder="Teléfono" required>
                                        </div>

                                        <div class="field-set">
                                            <input type="text" name=" " id=" " class="form-underline"
                                                placeholder="Asunto" required>
                                        </div>

                                        <div class="field-set">
                                            <textarea name="message" id="message" class="form-underline h-100px" placeholder="Tu Mensaje" required></textarea>
                                        </div>
                                    </div>
                                </div>


                                <div id='submit' class="mt-3">
                                    <button type='submit' id='submitPageContactButton' class="btn-main">
                                        <i class="fa fa-envelope"></i> &nbsp;
                                        <span>Enviar Ahora</span>
                                    </button>
                                </div>

                                <div id="success_message" class='success'>
                                    Tu mensaje se ha enviado correctamente. Actualiza esta página si quieres enviar más
                                    mensajes.
                                </div>
                                <div id="error_message" class='error'>
                                    Lo sentimos, hubo un error al enviar su formulario.
                                </div>
                            </form>
                            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    let form = document.getElementById('pageContactForm');
                                    form.addEventListener('submit', function(e) {
                                        e.preventDefault();

                                        var formulario = document.getElementById('pageContactForm');
                                        var formData = new FormData(formulario);

                                        // Deshabilitar el botón
                                        var submitButton = document.getElementById('submitPageContactButton');
                                        submitButton.disabled = true;
                                        submitButton.style.opacity = 0.25;

                                        // Crear una nueva solicitud XMLHttpRequest
                                        var xhr = new XMLHttpRequest();

                                        // Configurar la solicitud POST al servidor
                                        xhr.open('POST', "{{ route('apisubscriber') }}", true);

                                        // 🌟 INICIO DE LA CORRECCIÓN: Agregar Token CSRF 🌟
                                        // 1. Obtener el token de la etiqueta meta (asumiendo que está definida en tu HTML)
                                        var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                                        // 2. Establecer el encabezado X-CSRF-TOKEN
                                        xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
                                        // 🌟 FIN DE LA CORRECCIÓN 🌟

                                        // Configurar la función de callback para manejar la respuesta
                                        xhr.onload = function() {
                                            // Habilitar nuevamente el botón
                                            submitButton.disabled = false;
                                            submitButton.style.opacity = 1;
                                            if (xhr.status === 200) {
                                                var response = JSON.parse(xhr.responseText);
                                                Swal.fire({
                                                    icon: 'success',
                                                    title: 'Enhorabuena',
                                                    text: response.message,
                                                    customClass: {
                                                        container: 'sweet-modal-zindex' // Clase personalizada para controlar el z-index
                                                    }
                                                });
                                                formulario.reset();
                                            } else if (xhr.status === 422) {
                                                var errorResponse = JSON.parse(xhr.responseText);
                                                // Maneja los errores de validación aquí, por ejemplo, mostrando los mensajes de error en algún lugar de tu página.
                                                var errorMessages = errorResponse.errors;
                                                var errorMessageContainer = document.getElementById('messagePageContact');
                                                errorMessageContainer.innerHTML = 'Errores de validación:<br>';
                                                for (var field in errorMessages) {
                                                    if (errorMessages.hasOwnProperty(field)) {
                                                        errorMessageContainer.innerHTML += field + ': ' + errorMessages[field]
                                                            .join(', ') +
                                                            '<br>';
                                                    }
                                                }
                                            } else {
                                                console.error('Error en la solicitud: ' + xhr.status);
                                            }


                                        };

                                        // Enviar la solicitud al servidor
                                        xhr.send(formData);
                                    });
                                });
                            </script>
                        </div>
                    </div>
                </div>
            </div>
        </section>
</div>
