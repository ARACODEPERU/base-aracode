@extends('layouts.webpage')

@section('content')

    @if(!isset($landing) || empty($landing) || !isset($landing->course) || empty($landing->course))
        <div class="container-fluid py-5 text-center"><div class="alert alert-warning">Landing o curso no encontrado.</div></div>
    @else

    <style>
        /* Estilos para el Loader con Logotipo */
        .loader-wrapper {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #ffffff; /* Fondo blanco para que resalte el logo */
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            z-index: 999999;
        }
        .loader-logo {
            width: 220px; /* Tamaño ajustable del logo */
            height: auto;
            animation: pulse-logo 1.5s infinite ease-in-out;
        }
        .loader-text {
            margin-top: 20px;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            color: #002060;
            letter-spacing: 1px;
            text-transform: uppercase;
            display: flex;
            align-items: center;
        }
        .loader-text::after {
            content: '';
            animation: typing-dots 1.5s infinite;
            width: 15px;
            text-align: left;
        }
        @keyframes typing-dots {
            0%, 100% { content: ''; }
            25% { content: '.'; }
            50% { content: '..'; }
            75% { content: '...'; }
        }
        @keyframes pulse-logo {
            0% { transform: scale(0.9); opacity: 0.8; }
            50% { transform: scale(1.05); opacity: 1; }
            100% { transform: scale(0.9); opacity: 0.8; }
        }
    </style>

    <!-- Loader starts-->
    <div class="loader-wrapper">
        <img src="{{ asset('themes/webpage/images/Logo_cpa_modificado.png') }}" alt="CPA Logo" class="loader-logo">
        <p class="loader-text">Cargando</p>
    </div>
    <!-- Loader ends-->

    <!-- page-wrapper Start-->
    <div class="page-wrapper" id="pageWrapper">
        <!-- Page Header Start-->
        <x-header />

        <!-- Page Body Start-->
        <div class="page-body-wrapper">
            <!-- Page Sidebar Start-->
            <x-sidebar />
            <!-- Page Sidebar Ends-->
            <div class="page-body dark:bg-[#111c2d] transition-colors duration-300">

                {{-- Hero Section --}}
                <x-courselanding.hero :landing="$landing" />

                {{-- Professional Development - ORIGINAL --}}
                {{-- <x-courselanding.professional-development :landing="$landing" /> --}}
                
                {{-- Professional Development - INFORMACIÓN --}}
                <x-courselanding.professional-development-info :landing="$landing" />

                {{-- The Problem --}}
                <x-courselanding.the-problem :landing="$landing" />

                {{-- Study Plan --}}
                <x-courselanding.study-plan :landing="$landing" />

                {{-- Carrusel de Expertos Premium (Opción 5) --}}
                <x-courselanding.staff :landing="$landing" :teachers-premium="$teachers_premium" />

                {{-- Results --}}
                <x-courselanding.results :landing="$landing" :colors="$colors" />

                {{-- Testimonials --}}
                <x-courselanding.testimonials :landing="$landing" />

                {{-- Nueva Sección: Planes de Inversión --}}
                <x-courselanding.investment :landing="$landing" />

                {{-- Propuesta 2: Preguntas Frecuentes (Diseño Moderno de Tarjetas) --}}
                <x-courselanding.faq :landing="$landing" />

                {{-- Professional Development - FORMULARIO --}}
                <x-courselanding.professional-development-form :landing="$landing" />

                <x-courselanding.certificate-template />

                {{-- Testimonios de los alumnos de este curso (al final) + schema markup --}}
                <x-courselanding.course-testimonials :testimonials="$course_testimonials ?? []"
                    :course="$landing->course"
                    :schema="$course_schema ?? null" />


            </div>
        </div>
        <!-- footer start-->
        <x-footer />
    </div>

    @endif

@section('javascripts')
    <script>
        $(document).ready(function() {
            // 1. Inicializar AOS
            if (window.AOS !== undefined) {
                AOS.init({
                    mirror: false,
                    duration: 800,
                    once: true
                });
            }

            // 2. Ocultar el loader y refrescar AOS
            setTimeout(function() {
                $('.loader-wrapper').fadeOut('slow', function() {
                    $(this).remove(); // Eliminar del DOM para evitar interferencias
                    if (window.AOS !== undefined) {
                        AOS.refresh();
                    }
                });
            }, 2500);
        });

        function procederInscripcion() {
            if (window.Swal === undefined) {
                console.error("SweetAlert2 (Swal) no está cargado.");
                return;
            }

            Swal.fire({
                title: '¿Confirmar inscripción?',
                text: '¿Estás seguro de que deseas proceder con la compra?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, continuar',
                cancelButtonText: 'Cancelar',
            }).then((result) => {
                if (result.isConfirmed) {
                    // 1. Limpiar el carrito
                    localStorage.removeItem('carrito');

                    // 2. Crear objeto del producto
                    var producto = {
                        id: @json($onli_item_id ?? 0),
                        nombre: @json($landing->course->description ?? 'Curso'),
                        precio: @json($landing->investment_section['items'][0]['price_now'] ?? 0),
                        image: "{{ $landing->course->image ?? '' }}"
                    };

                    // 3. Agregar directamente al localStorage
                    var carrito = [];
                    carrito.push(producto);
                    localStorage.setItem('carrito', JSON.stringify(carrito));

                    // 4. Redireccionar
                    window.location.href = "{{ route('web_carrito') }}";
                }
            });
        }

        function initContactForm(formId, buttonId, messageId, countrySelectId) {
            let formElement = document.getElementById(formId);
            if(!formElement) return;

            formElement.addEventListener('submit', function(e) {
                e.preventDefault();

                // --- Validación Frontend ---
                var errorMessageContainer = document.getElementById(messageId);
                errorMessageContainer.innerHTML = '';

                var phoneInput = formElement.querySelector('input[name="phone"]');
                var emailInput = formElement.querySelector('input[name="email"]');
                var errors = [];

                // Validar teléfono: solo dígitos y mínimo 8
                if (phoneInput) {
                    var phoneValue = phoneInput.value.replace(/\s/g, '');
                    if (phoneValue && !/^[0-9]+$/.test(phoneValue)) {
                        errors.push('El teléfono solo debe contener números.');
                    } else if (phoneValue.length < 8) {
                        errors.push('El teléfono debe tener al menos 8 dígitos.');
                    }
                }

                // Validar correo electrónico
                if (emailInput) {
                    var emailValue = emailInput.value.trim();
                    if (emailValue && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailValue)) {
                        errors.push('Ingresa un correo electrónico válido.');
                    }
                }

                if (errors.length > 0) {
                    errorMessageContainer.innerHTML = '<div class="alert alert-danger py-2">' + errors.join('<br>') + '</div>';
                    return;
                }
                // --- Fin Validación Frontend ---

                var formData = new FormData(formElement);
                const countrySelect = document.getElementById(countrySelectId);
                const prefix = countrySelect.value; // Ej: "+51"

                // Obtener el valor del teléfono del input
                var rawPhone = formData.get('phone') || '';

                // Extraer el código numérico del prefijo (ej: "+51" → "51", "+591" → "591")
                var numericCode = prefix.replace(/[^0-9]/g, '');

                // Limpiar el número: eliminar todo lo que no sean dígitos
                var cleanedPhone = rawPhone.replace(/[^0-9]/g, '');

                // Si el número ya contiene el código de país al inicio, quitarlo para evitar duplicación
                while (cleanedPhone.startsWith(numericCode)) {
                    cleanedPhone = cleanedPhone.substring(numericCode.length);
                }

                // Argentina: si el número no empieza con 9, agregarlo automáticamente
                if (numericCode === '54' && cleanedPhone.length > 0 && !cleanedPhone.startsWith('9')) {
                    cleanedPhone = '9' + cleanedPhone;
                }

                // Asignar el teléfono completo: prefijo + número limpio (sin código duplicado)
                formData.set('phone', prefix + cleanedPhone);

                var submitButton = document.getElementById(buttonId);
                submitButton.disabled = true;
                submitButton.style.opacity = 0.25;

                var xhr = new XMLHttpRequest();
                xhr.open('POST', "{{ route('apisubscriber') }}", true);

                xhr.onload = function() {
                    submitButton.disabled = false;
                    submitButton.style.opacity = 1;

                    if (xhr.status === 200) {
                        var response = JSON.parse(xhr.responseText);

                        // Define la función que muestra SweetAlert y las acciones posteriores
                        const showSweetAlertAndContinue = () => {
                            if (window.Swal === undefined) return;
                            Swal.fire({
                                icon: 'success',
                                title: 'Registro exitoso, estas a un paso de asegurar tu vacante',
                                text: 'Hemos recibido tu información y enviado el brochure a tu Whatsapp. Estamos en etapa final de preventa con condiciones preferenciales activas. Elige como deseas continuar:',
                            }).then(() =>{
                                    //Solo descargar brochure si NO hay flow_id (si está vacío, descarga directa)
                                const flowIdInput = formElement.querySelector('input[name="flow_id"]');
                                const flowIdValue = flowIdInput ? flowIdInput.value.trim() : '';
                                if (!flowIdValue) {
                                    const downloadUrl = "{{ isset($landing->course->brochure) ? $landing->course->brochure->path_file ?? '' : '' }}";
                                    if (downloadUrl) {
                                        window.open(downloadUrl, '_blank');
                                    }
                                }

                            });
                        };

                        // 1. Intentar cerrar el modal de Bootstrap de forma segura
                        const modalElement = document.getElementById('modalFinanciamiento');
                        if (modalElement && window.bootstrap !== undefined) {
                            const modalInstance = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
                            modalInstance.hide();
                        }

                        // 2. LIMPIEZA FORZADA: Eliminamos manualmente el fondo negro y desbloqueamos el scroll
                        // Esto elimina cualquier "backdrop" huérfano que Bootstrap haya dejado
                        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                        document.body.classList.remove('modal-open');
                        document.body.style.overflow = '';
                        document.body.style.paddingRight = '';

                        // 3. Mostrar la alerta
                        showSweetAlertAndContinue();



                    } else if (xhr.status === 422) {
                        var errorResponse = JSON.parse(xhr.responseText);
                        var errorMessageContainer = document.getElementById(messageId);
                        errorMessageContainer.innerHTML = ''; // Limpiar mensajes anteriores
                        errorMessageContainer.innerHTML = 'Errores de validación:<br>';
                        for (var field in errorResponse.errors) {
                            errorMessageContainer.innerHTML += field + ': ' + errorResponse.errors[field].join(', ') + '<br>';
                        }
                    }
                };
                var _t = JSON.parse(localStorage.getItem('traffic_tracking') || '{}');
                ['utm_source','utm_medium','utm_campaign','utm_term','utm_content','utm_id','fbclid','gclid','referer','landing_url','traffic_source'].forEach(function(k){ if(_t[k]) formData.set(k, _t[k]); });
                xhr.send(formData);
            });
        }

        // Inicializar ambos formularios
        initContactForm('pageContactForm', 'submitPageContactButton', 'messagePageContact', 'countryPhoneSelect');
        initContactForm('modalContactForm', 'submitModalContactButton', 'messageModalContact', 'modalCountryPhoneSelect');
    </script>
    <script>
        $(document).ready(function() {
            if ($.fn.select2) {
                function formatCountry(country) {
                    if (!country.id) {
                        return country.text;
                    }
                    var code = $(country.element).data('code');
                    var $country = $(
                        '<span><img src="https://flagcdn.com/w20/' + (code ? code.toLowerCase() : 'pe') +
                        '.png" class="me-2" style="vertical-align: middle; border: 1px solid #eee; width: 20px;">' +
                        country.text + '</span>'
                    );
                    return $country;
                };

                $('#countryPhoneSelect').select2({
                    templateResult: formatCountry,
                    templateSelection: formatCountry,
                    dropdownParent: $('#countryPhoneSelect').parent()
                });

                $('#modalCountryPhoneSelect').select2({
                    templateResult: formatCountry,
                    templateSelection: formatCountry,
                    dropdownParent: $('#modalFinanciamiento')
                });
            }
        });

        // Toggle FAQ Manual - abrir al entrar, cerrar al salir
        window.toggleFaq = function(index) {
            const $answer = $('#faq-answer-' + index);
            const $icon = $('#faq-icon-' + index);

            if (!$answer.is(':visible')) {
                $answer.slideDown(300);
                $icon.css('transform', 'rotate(180deg)');
            } else {
                $answer.slideUp(300);
                $icon.css('transform', 'rotate(0deg)');
            }
        };
    </script>
@endsection
