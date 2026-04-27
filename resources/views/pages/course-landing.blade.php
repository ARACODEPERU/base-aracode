@extends('layouts.webpage')

@section('content')
@php
    // Validar que landing y course existan
    if (!isset($landing) || empty($landing) || !isset($landing->course) || empty($landing->course)) {
        echo '<div class="container-fluid py-5 text-center"><div class="alert alert-warning">Landing o curso no encontrado.</div></div>';
        return;
    }
@endphp

{{-- Ideally, this CSS should be in the <head> of your main layout file (e.g., layouts/webpage.blade.php) --}}
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        .transition-all {
            transition: all 0.3s ease-in-out !important;
            transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease !important;
        }

        .transition-all:hover {
            transform: translateY(-12px);
            box-shadow: 0 15px 30px rgba(0, 32, 96, 0.15) !important;
            box-shadow: 0 15px 30px rgba(0, 32, 96, 0.15);
            border-color: #ffc107 !important;
            /* Un sutil borde dorado al resaltar */
        }

        /* Estilos del Carrusel Infinito */
        .carousel-viewport {
            overflow: hidden;
            padding: 100px 0 50px 0; /* Espacio para que las imágenes y sombras no se corten */
            position: relative;
            width: 100%;
        }

        .carousel-track {
            display: flex;
            gap: 30px;
            width: max-content;
            animation: scroll-infinite 40s linear infinite;
        }

        .carousel-track:hover {
            animation-play-state: paused;
        }

        @keyframes scroll-infinite {
            0% { transform: translateX(0); }
            100% { transform: translateX(calc(-50%)); }
        }

        .teacher-carousel-item {
            width: 300px;
            flex-shrink: 0;
        }

        /* Estilos para FAQ Moderna */
        .faq-modern .accordion-item {
            border: 1px solid #edf2f7 !important;
            margin-bottom: 1rem;
            border-radius: 15px !important;
            overflow: hidden;
            transition: all 0.3s ease;
            /* background: #ffffff; */
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            background: #ffffff;
        }
        .faq-modern .accordion-item:has(.show) {
            border-color: #002060 !important;
            box-shadow: 0 15px 30px rgba(0, 32, 96, 0.08);
        }
        .faq-modern .accordion-button {
            padding: 1.5rem;
            font-weight: 600;
            /* background-color: white !important; */
            background-color: white !important;
            color: #002060;
            font-size: 1.05rem;
        }
        .faq-modern .accordion-button:focus {
            box-shadow: none;
            box-shadow: none !important;
            border-color: transparent;
        }
        .faq-modern .accordion-button:not(.collapsed) {
            background-color: rgba(0, 32, 96, 0.02) !important;
            color: #002060;
            border-bottom: 1px solid #f1f5f9;
        }
        .faq-modern .accordion-button:not(.collapsed) {
            color: #002060;
            box-shadow: none;
        }
        .faq-modern .accordion-button::after {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23002060'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
            transition: transform 0.3s ease;
        }

        /* Ajustes para Select2 y flags SVG */
        .select2-container--default .select2-selection--single {
            border: 1px solid #dee2e6 !important;
            border-left: 0 !important;
            height: 38px !important;
            display: flex;
            align-items: center;
        }
        .select2-container {
            width: 100% !important;
        }
        .select2-selection__arrow {
            top: 6px !important;
        }
    </style>

    <!-- tap on top starts-->
    <div class="tap-top"><i data-feather="chevrons-up"></i></div>
    <!-- tap on tap ends-->



    <!-- page-wrapper Start-->
    <div class="page-wrapper" id="pageWrapper">
        <!-- Page Header Start-->
        <x-header />
        <!-- Page Header Ends-->
        <!-- Page Body Start-->
        <div class="page-body-wrapper">
            <!-- Page Sidebar Start-->
            <x-sidebar />
            <!-- Page Sidebar Ends-->
            <div class="page-body">

                {{-- Hero Section --}}
                <x-courselanding.hero :landing="$landing" />


                {{-- Professional Development --}}
                <div class="container-fluid card aos-animate" data-aos="fade-up">
                    <div class="row">
                        @if (filled($landing->professional_section ?? null))
                            <div class="col-md-6">
                                <div class="card-body p-4 p-lg-5">
                                    <div class="mb-4">
                                        <span class="badge rounded-pill bg-light text-primary px-3 py-2 mb-3 shadow-sm border"
                                            style="color: #002060 !important;">
                                            <i class="fa fa-refresh me-1"></i> {{ $landing->professional_section['name'] }}
                                        </span>
                                        <h2 class="fw-bold display-6" style="color: #002060;">
                                            {{ $landing->professional_section['title'] }}
                                        </h2>
                                        <p class="text-muted fs-5">
                                            {{ $landing->professional_section['description'] }}
                                        </p>
                                    </div>

                                    @if ($landing->professional_section['items'][0])
                                        <div class="row g-4 mt-2">
                                            <div class="col-sm-6">
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-shrink-0 bg-warning-light p-3 rounded-circle me-3"
                                                        style="background-color: rgba(255, 193, 7, 0.1);">
                                                        <i class="fa {{ $landing->professional_section['items'][0]['icon']  }} text-warning fs-4"></i>
                                                    </div>
                                                    <div>
                                                        <h4 class="mb-0 fw-bold" style="color: #002060; font-size: 18px;">
                                                            {{ $landing->professional_section['items'][0]['title']  }}
                                                        </h4>
                                                        <p  style="font-size: 15px;">
                                                            {{ $landing->professional_section['items'][0]['description']  }}
                                                        </p>
                                                    </div>
                                                </div>
                                        </div>
                                    @endif

                                    @if ($landing->professional_section['items'][1])
                                        <div class="col-sm-6">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0 bg-info-light p-3 rounded-circle me-3"
                                                    style="background-color: rgba(0, 204, 255, 0.1);">
                                                    <i class="fa {{ $landing->professional_section['items'][1]['icon']  }} text-info fs-4"></i>
                                                </div>
                                                <div>
                                                    <h4 class="mb-0 fw-bold" style="color: #002060; font-size: 18px;">
                                                        {{ $landing->professional_section['items'][1]['title']  }}
                                                    </h4>
                                                    <p  style="font-size: 15px;">
                                                        {{ $landing->professional_section['items'][1]['description']  }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                        <div class="col-md-6">
                            <div class="card-body p-4 p-lg-5">
                                <div class="mb-4">
                                    <h2 class="fw-bold" style="color: #002060; font-size: 20px;">¿Listo para empezar?</h2>
                                    <p class="text-muted" style="font-size: 15px;">
                                        Completa tus datos para descargar el brochure detallado y recibir
                                        información sobre promociones exclusivas del programa.
                                    </p>
                                </div>

                                <form id="pageContactForm">
                                    @csrf
                                    <input type="hidden" name="subject" value="{{ $landing->course->description ?? 'Curso' }}">
                                    <input type="hidden" name="message" value="desde Landing - Descargué Brochure">

                                    <div class="mb-3">
                                        <label class="form-label fw-bold" style="color: #002060; font-size: 15px;">Nombres y Apellidos</label>
                                        <div class="input-group shadow-sm">
                                            <span class="input-group-text bg-white border-end-0">
                                                <i class="fa fa-user text-muted"></i>
                                            </span>
                                            <input type="text" name="full_name" class="form-control border-start-0 ps-0" placeholder="Ingresa tu nombre completo" required>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold" style="color: #002060; font-size: 15px;">País</label>
                                        <div class="input-group shadow-sm">
                                            <span class="input-group-text bg-white border-end-0">
                                                <i class="fa fa-flag text-muted"></i>
                                            </span>
                                            <select name="country_phone" id="countryPhoneSelect" class="form-select border-start-0 ps-0" required>
                                                <optgroup label="América del Sur">
                                                    <option value="+51" data-code="pe" selected>Perú (+51)</option>
                                                    <option value="+54" data-code="ar">Argentina (+54)</option>
                                                    <option value="+591" data-code="bo">Bolivia (+591)</option>
                                                    <option value="+55" data-code="br">Brasil (+55)</option>
                                                    <option value="+56" data-code="cl">Chile (+56)</option>
                                                    <option value="+57" data-code="co">Colombia (+57)</option>
                                                    <option value="+593" data-code="ec">Ecuador (+593)</option>
                                                    <option value="+592" data-code="gy">Guyana (+592)</option>
                                                    <option value="+595" data-code="py">Paraguay (+595)</option>
                                                    <option value="+597" data-code="sr">Surinam (+597)</option>
                                                    <option value="+598" data-code="uy">Uruguay (+598)</option>
                                                    <option value="+58" data-code="ve">Venezuela (+58)</option>
                                                </optgroup>

                                                <optgroup label="Norte y Centroamérica">
                                                    <option value="+1" data-code="us">Estados Unidos (+1)</option>
                                                    <option value="+1" data-code="ca">Canadá (+1)</option>
                                                    <option value="+52" data-code="mx">México (+52)</option>
                                                    <option value="+502" data-code="gt">Guatemala (+502)</option>
                                                    <option value="+503" data-code="sv">El Salvador (+503)</option>
                                                    <option value="+504" data-code="hn">Honduras (+504)</option>
                                                    <option value="+505" data-code="ni">Nicaragua (+505)</option>
                                                    <option value="+506" data-code="cr">Costa Rica (+506)</option>
                                                    <option value="+507" data-code="pa">Panamá (+507)</option>
                                                    <option value="+53" data-code="cu">Cuba (+53)</option>
                                                    <option value="+1" data-code="do">República Dominicana (+1)</option>
                                                    <option value="+1" data-code="pr">Puerto Rico (+1)</option>
                                                </optgroup>

                                                <optgroup label="Europa">
                                                    <option value="+34" data-code="es">España (+34)</option>
                                                    <option value="+49" data-code="de">Alemania (+49)</option>
                                                    <option value="+43" data-code="at">Austria (+43)</option>
                                                    <option value="+32" data-code="be">Bélgica (+32)</option>
                                                    <option value="+359" data-code="bg">Bulgaria (+359)</option>
                                                    <option value="+357" data-code="cy">Chipre (+357)</option>
                                                    <option value="+385" data-code="hr">Croacia (+385)</option>
                                                    <option value="+45" data-code="dk">Dinamarca (+45)</option>
                                                    <option value="+421" data-code="sk">Eslovaquia (+421)</option>
                                                    <option value="+386" data-code="si">Eslovenia (+386)</option>
                                                    <option value="+372" data-code="ee">Estonia (+372)</option>
                                                    <option value="+358" data-code="fi">Finlandia (+358)</option>
                                                    <option value="+33" data-code="fr">Francia (+33)</option>
                                                    <option value="+30" data-code="gr">Grecia (+30)</option>
                                                    <option value="+31" data-code="nl">Holanda (Países Bajos) (+31)</option>
                                                    <option value="+36" data-code="hu">Hungría (+36)</option>
                                                    <option value="+353" data-code="ie">Irlanda (+353)</option>
                                                    <option value="+39" data-code="it">Italia (+39)</option>
                                                    <option value="+371" data-code="lv">Letonia (+371)</option>
                                                    <option value="+370" data-code="lt">Lituania (+370)</option>
                                                    <option value="+352" data-code="lu">Luxemburgo (+352)</option>
                                                    <option value="+356" data-code="mt">Malta (+356)</option>
                                                    <option value="+47" data-code="no">Noruega (+47)</option>
                                                    <option value="+48" data-code="pl">Polonia (+48)</option>
                                                    <option value="+351" data-code="pt">Portugal (+351)</option>
                                                    <option value="+44" data-code="gb">Reino Unido (+44)</option>
                                                    <option value="+420" data-code="cz">República Checa (+420)</option>
                                                    <option value="+40" data-code="ro">Rumanía (+40)</option>
                                                    <option value="+46" data-code="se">Suecia (+46)</option>
                                                    <option value="+41" data-code="ch">Suiza (+41)</option>
                                                    <option value="+7" data-code="ru">Rusia (+7)</option>
                                                </optgroup>

                                                <optgroup label="Asia">
                                                    <option value="+86" data-code="cn">China (+86)</option>
                                                    <option value="+81" data-code="jp">Japón (+81)</option>
                                                </optgroup>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold" style="color: #002060; font-size: 15px;">WhatsApp</label>
                                            <div class="input-group shadow-sm">
                                                <span class="input-group-text bg-white border-end-0">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" style="width: 16px; height: 16px; fill: #6c757d;">
                                                        <path d="M380.9 97.1c-41.9-42-97.7-65.1-157-65.1-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480 117.7 449.1c32.4 17.7 68.9 27 106.1 27l.1 0c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3 18.6-68.1-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1s56.2 81.2 56.1 130.5c0 101.8-84.9 184.6-186.6 184.6zM325.1 300.5c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8s-14.3 18-17.6 21.8c-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7s-12.5-30.1-17.1-41.2c-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2s-9.7 1.4-14.8 6.9c-5.1 5.6-19.4 19-19.4 46.3s19.9 53.7 22.6 57.4c2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4s4.6-24.1 3.2-26.4c-1.3-2.5-5-3.9-10.5-6.6z"/>
                                                    </svg>
                                                </span>
                                                <input type="text" name="phone" class="form-control border-start-0 ps-0" placeholder="999 888 777" required>
                                            </div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold" style="color: #002060; font-size: 15px;">Correo Electrónico</label>
                                            <div class="input-group shadow-sm">
                                                <span class="input-group-text bg-white border-end-0">
                                                    <i class="fa fa-envelope text-muted"></i>
                                                </span>
                                                <input type="email" name="email" class="form-control border-start-0 ps-0" placeholder="ejemplo@correo.com" required>
                                            </div>
                                        </div>
                                    </div> <div class="mt-4">
                                        <button type="submit" id="submitPageContactButton" class="btn btn-warning btn-lg w-100 fw-bold shadow-sm py-3" style="color: #002060; border-radius: 12px;">
                                            <i class="fa fa-file-pdf-o me-2"></i> DESCARGAR BROCHURE
                                        </button>
                                    </div>
                                    <div id="messagePageContact" class="mt-3"></div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="container-fluid card aos-animate" data-aos="fade-up">
                    <div class="row">
                        @if (filled($landing->problem_section ?? null))
                            <div class="col-md-12">
                                <div class="card-body p-4 p-lg-5">
                                    <div class="text-center mb-5">
                                        <span class="badge rounded-pill px-3 py-2 mb-3 shadow-sm border"
                                            style="background-color: rgba(220, 53, 69, 0.1); color: #dc3545;">
                                            <i class="fa fa-exclamation-triangle me-1"></i> {{ $landing->problem_section['name'] }}
                                        </span>
                                        <h2 class="fw-bold display-6" style="color: #002060;">{{ $landing->problem_section['title'] }}</h2>
                                        <p class="text-muted fs-5 mx-auto" style="max-width: 800px;">{{ $landing->problem_section['description'] }}</p>
                                    </div>

                                    <div class="row g-4 justify-content-center">
                                        @if (filled($landing->problem_section['items'][0] ?? null))
                                            <div class="col-md-4" data-aos="zoom-in" data-aos-delay="100">
                                                <div
                                                    class="h-100 p-4 rounded-4 border shadow-sm text-center bg-white transition-all">
                                                    <div class="mb-4">
                                                        <i class="fa {{ $landing->problem_section['items'][0]['icon']  }} text-warning" style="font-size: 3.5rem;"></i>
                                                    </div>
                                                    <h4 class="fw-bold mb-3" style="color: #002060;">{{ $landing->problem_section['items'][0]['title']  }}</h4>
                                                    <p class="text-muted mb-0">{{ $landing->problem_section['items'][0]['description']  }}</p>
                                                </div>
                                            </div>
                                        @endif

                                        @if (filled($landing->problem_section['items'][1] ?? null))
                                            <div class="col-md-4" data-aos="zoom-in" data-aos-delay="200">
                                                <div
                                                    class="h-100 p-4 rounded-4 border shadow-sm text-center bg-white transition-all">
                                                    <div class="mb-4">
                                                        <i class="fa {{ $landing->problem_section['items'][1]['icon']  }} text-warning" style="font-size: 3.5rem;"></i>
                                                    </div>
                                                    <h4 class="fw-bold mb-3" style="color: #002060;">{{ $landing->problem_section['items'][1]['title']  }}
                                                    </h4>
                                                    <p class="text-muted mb-0">{{ $landing->problem_section['items'][1]['description']  }}</p>
                                                </div>
                                            </div>
                                        @endif

                                        @if (filled($landing->problem_section['items'][2] ?? null))
                                            <div class="col-md-4" data-aos="zoom-in" data-aos-delay="300">
                                                <div
                                                    class="h-100 p-4 rounded-4 border shadow-sm text-center bg-white transition-all">
                                                    <div class="mb-4">
                                                        <i class="fa {{ $landing->problem_section['items'][2]['icon']  }} text-warning" style="font-size: 3.5rem;"></i>
                                                    </div>
                                                    <h4 class="fw-bold mb-3" style="color: #002060;">{{ $landing->problem_section['items'][2]['title']  }}</h4>
                                                    <p class="text-muted mb-0">{{ $landing->problem_section['items'][2]['description']  }}</p>
                                                </div>
                                            </div>
                                        @endif

                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                @if (filled($landing->study_plan_section ?? null))
                    <div class="container-fluid card aos-animate" data-aos="fade-up">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card-body p-4 p-lg-5">
                                    <!-- Header de Sección -->
                                    <div class="text-center mb-5">
                                        <span class="badge rounded-pill px-3 py-2 mb-3 shadow-sm border"
                                            style="background-color: rgba(0, 32, 96, 0.1); color: #002060;">
                                            <i class="fa fa-list-ul me-1"></i> {{ $landing->study_plan_section['name'] }}
                                        </span>
                                        <h2 class="fw-bold display-6" style="color: #002060;">{{ $landing->study_plan_section['title'] }}</h2>
                                        <p class="text-muted fs-5 mx-auto" style="max-width: 800px;">{{ $landing->study_plan_section['description'] }}</p>
                                    </div>

                                    <div class="row align-items-center">
                                        <!-- Columna de Imagen -->
                                        <div class="col-lg-5 mb-4 mb-lg-0" data-aos="fade-right">
                                            <div class="position-relative">
                                                <img src="{{ asset("storage/".$landing->study_plan_section['image']) }}"
                                                    alt="Temario Especializado" class="img-fluid rounded-4 shadow-lg"
                                                    style="border: 8px solid #f8f9fa;">
                                                <div
                                                    class="position-absolute bottom-0 start-0 m-3 p-3 bg-warning rounded-3 shadow d-none d-sm-block">
                                                    <span class="fw-bold text-dark">Módulos 100% Actualizados</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Columna de Temas -->
                                        <div class="col-lg-7" data-aos="fade-left">
                                            <div class="ps-lg-4">

                                                @if (filled($landing->study_plan_section['items'] ?? null))
                                                    @foreach ($landing->study_plan_section['items'] as $item)
                                                        <div class="d-flex mb-4">
                                                            <div class="flex-shrink-0">
                                                                <span
                                                                    class="badge rounded-circle bg-primary d-flex align-items-center justify-content-center shadow"
                                                                    style="width: 45px; height: 45px; background-color: #002060 !important; font-size: 1.1rem;">{{ $loop->iteration }}</span>
                                                            </div>
                                                            <div class="ms-3">
                                                                <h5 class="fw-bold" style="color: #002060;">{{ $landing->study_plan_section['items'][$loop->index]['title'] }}</h5>
                                                                <p class="text-muted">{{ $landing->study_plan_section['items'][$loop->index]['description'] }}</p>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                @endif

                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif


                {{-- Propuesta de Diseño: Carrusel de Expertos Premium (Opción 5) --}}

                @if (filled($landing->staff_section ?? null))
                    <div class="container-fluid card aos-animate mt-5" data-aos="fade-up">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card-body p-4 p-lg-5">
                                    <!-- Header de Sección -->
                                    <div class="text-center mb-4">
                                        <span class="badge rounded-pill px-3 py-2 mb-3 shadow-sm border"
                                            style="background-color: rgba(0, 32, 96, 0.05); color: #002060;">
                                            <i class="fa fa-users me-1"></i> {{ $landing->staff_section['name'] }}
                                        </span>
                                        <h2 class="fw-bold display-6" style="color: #002060;">{{ $landing->staff_section['title'] }}</h2>
                                        <p class="text-muted fs-5 mx-auto" style="max-width: 800px;">
                                            {{ $landing->staff_section['description'] }}
                                        </p>
                                    </div>

                                    <div class="carousel-viewport" style="padding: 40px 0;">
                                        <div class="carousel-track" style="animation-duration: 50s;">

                                            {{-- Se duplica el contenido para el loop infinito --}}
                                            @if (filled($teachers_premium ?? null))
                                                 @foreach(array_merge($teachers_premium, $teachers_premium) as $index => $teacher)

                                                 <div class="teacher-carousel-item" style="width: 280px;">
                                                     <div class="card border-0 shadow-sm h-100 transition-all rounded-4 overflow-hidden bg-white mx-2" style="cursor: pointer;" onclick="openTeacherModal({{ $index }})">
                                                         <div style="height: 240px; overflow: hidden; position: relative;">
                                                             <img src="{{ $teacher['img'] }}"
                                                                 class="card-img-top h-100 w-100"
                                                                 style="object-fit: cover;"
                                                                 alt="{{ $teacher['name'] }}">
                                                             <div class="position-absolute bottom-0 start-0 w-100 p-3"
                                                                 style="background: linear-gradient(transparent, rgba(0,32,96,0.8));">
                                                                 <p class="text-white small mb-0 fw-light">Socio Consultor</p>
                                                             </div>
                                                         </div>
                                                         <div class="card-body text-center p-3">
                                                             <h5 class="fw-bold mb-1" style="color: #002060; font-size: 1.1rem;">{{ $teacher['name'] }}</h5>
                                                             <p class="text-warning small fw-bold mb-0" style="font-size: 0.8rem;">{{ $teacher['role'] }}</p>
                                                         </div>
                                                     </div>
                                                 </div>
                                                 @endforeach
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if (filled($landing->staff_section ?? null))
                    @if (filled($teachers_premium ?? null))
                        @foreach(array_merge($teachers_premium, $teachers_premium) as $index => $teacher)
                            <div id="teacher-modal-{{ $index }}" class="teacher-modal" onclick="closeTeacherModal({{ $index }})">
                                <div class="modal-content" onclick="event.stopPropagation()">
                                    <button type="button" onclick="closeTeacherModal({{ $index }})" style="position: absolute; top: 15px; right: 15px; border: none; background: none; font-size: 24px; cursor: pointer; z-index: 999999;">&times;</button>
                                    <h3 style="color: #002060; margin-bottom: 20px;">{{ $teacher['name'] }}</h3>

                                    <div class="image-container">
                                        <img src="{{ $teacher['img'] }}"
                                            alt="{{ $teacher['name'] }}">
                                    </div>

                                    <h5 style="color: #ffc107; margin-bottom: 20px;">{{ $teacher['role'] }}</h5>
                                    @if (filled($teacher['resumes'] ?? null))
                                        @foreach ($teacher['resumes'] as $resume)
                                            @if ($resume->type == 'work experience')
                                                <div style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #eee;">
                                                    <p style="color: #666; margin-bottom: 0;">{{ $resume->description }}</p>
                                                </div>
                                            @endif
                                        @endforeach
                                    @else
                                        <p style="color: #666;">No hay información de experiencia disponible.</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @endif
                @endif


<script>
    function openTeacherModal(index) {
    // Cerrar todos los modales primero
    document.querySelectorAll('.teacher-modal').forEach(modal => {
        modal.classList.remove('active');
    });

    // Abrir el modal específico
    const modal = document.getElementById(`teacher-modal-${index}`);
    if (modal) {
        modal.classList.add('active');
        // Prevenir scroll del body cuando el modal está abierto
        document.body.style.overflow = 'hidden';
    }
}

function closeTeacherModal(index) {
    const modal = document.getElementById(`teacher-modal-${index}`);
    if (modal) {
        modal.classList.remove('active');
        // Restaurar scroll del body
        document.body.style.overflow = '';
    }
}

// Cerrar modal con tecla ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const activeModal = document.querySelector('.teacher-modal.active');
        if (activeModal) {
            activeModal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }
});

// Opcional: Cerrar modal al hacer clic fuera (ya lo tienes en el div principal)
// El onclick del div principal con clase .teacher-modal ya maneja el cierre
</script>
                <style>

/* Fondo oscuro del modal - cerrado por defecto */
.teacher-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    z-index: 999999;
}

/* Cuando el modal está abierto, lo mostramos */
.teacher-modal.active {
    display: block;
}

/* Contenedor del contenido del modal */
.teacher-modal .modal-content {
    background: white;
    max-width: 1500px;
    width: 90%;
    max-height: 90vh;
    border-radius: 15px;
    padding: 30px;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 999999;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    overflow-y: auto;
}

/* Contenedor de la imagen */
.teacher-modal .image-container {
    width: 100%;
    margin: 20px 0;
    position: relative;
}

/* Estilos para la imagen - alto = 1/4 del ancho del modal-content */
.teacher-modal .modal-content img {
    width: 100%;
    height: auto;
    aspect-ratio: 4 / 1;
    object-fit: cover;
    border-radius: 10px;
    display: block;
}

                </style>


                @if (filled($landing->results_section ?? null))
                    <div class="container-fluid card aos-animate" data-aos="fade-up">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card-body p-4 p-lg-5">
                                    <div class="text-center mb-5">
                                        <span class="badge rounded-pill px-3 py-2 mb-3 shadow-sm border"
                                            style="background-color: rgba(40, 167, 69, 0.1); color: #28a745;">
                                            <i class="fa fa-check-circle me-1"></i> {{ $landing->results_section['name'] }}
                                        </span>
                                        <h2 class="fw-bold display-6" style="color: #002060;">
                                            {{ $landing->results_section['title'] }}
                                        </h2>
                                        <p class="text-muted fs-5 mx-auto" style="max-width: 800px;">
                                            {{ $landing->results_section['description'] }}
                                        </p>
                                    </div>

                                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4 justify-center">

                                       @if (filled($landing->results_section['items'] ?? null))
                                            @foreach($landing->results_section['items'] as $item)
                                            <div class="col" data-aos="fade-up" data-aos-delay="{{ 100 * ($loop->index + 1) }}">
                                                <div class="h-100 p-4 rounded-4 border-0 shadow-sm text-center bg-white transition-all position-relative overflow-hidden"
                                                    style="border-top: 5px solid {{ $colors[$loop->index] }} !important;">

                                                    {{-- Círculo decorativo de fondo --}}
                                                    <div class="position-absolute opacity-0 transition-all"
                                                        style="width: 100px; height: 100px; background-color: {{ $colors[$loop->index] }}; border-radius: 50%; top: -20px; right: -20px; opacity: 0.05 !important;">
                                                    </div>

                                                    <div class="mb-4 d-inline-flex align-items-center justify-content-center rounded-circle shadow-sm"
                                                        style="width: 70px; height: 70px; background-color: white; border: 1px solid #f1f1f1;">
                                                        <i class="fa {{ $item['icon'] }}" style="font-size: 2rem; color: {{ $colors[$loop->index] }};"></i>
                                                    </div>

                                                    <h4 class="fw-bold mb-3" style="color: #002060;">{{ $item['title'] }}</h4>
                                                    <p class="text-muted mb-0 small" style="line-height: 1.6;">
                                                        {{ $item['description'] }}
                                                    </p>
                                                </div>
                                            </div>
                                            @endforeach
                                       @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif



    {{-- Nueva Propuesta: Sección de Testimonios en Carrusel (Opción 6) --}}
                @if (filled($landing->testimonials_section ?? null))
                    <div class="container-fluid card aos-animate mt-5" data-aos="fade-up">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card-body p-4 p-lg-5">
                                    <!-- Header de Sección -->
                                    <div class="text-center mb-4">
                                        <span class="badge rounded-pill px-3 py-2 mb-3 shadow-sm border"
                                            style="background-color: rgba(0, 123, 255, 0.05); color: #007bff;">
                                            <i class="fa fa-quote-right me-1"></i> {{ $landing->testimonials_section['name'] }}
                                        </span>
                                        <h2 class="fw-bold display-6" style="color: #002060;">{{ $landing->testimonials_section['title'] }}</h2>
                                        <p class="text-muted fs-5 mx-auto" style="max-width: 800px;">
                                            {{ $landing->testimonials_section['description'] }}
                                        </p>
                                    </div>

                                    <style>
                                        .testimonial-carousel-viewport {
                                            overflow: hidden;
                                            padding: 20px 0; /* Espacio para sombras */
                                            position: relative;
                                            width: 100%;
                                        }

                                        .testimonial-carousel-track {
                                            display: flex;
                                            gap: 30px;
                                            width: max-content;
                                            animation: scroll-infinite-testimonials 60s linear infinite; /* Velocidad ajustada */
                                        }

                                        .testimonial-carousel-track:hover {
                                            animation-play-state: paused;
                                        }

                                        @keyframes scroll-infinite-testimonials {
                                            0% { transform: translateX(0); }
                                            50% { transform: translateX(-50%); }
                                        }

                                        .testimonial-card {
                                            width: 350px; /* Ancho fijo para las tarjetas de testimonio */
                                            flex-shrink: 0;
                                            background: #fff;
                                            border-radius: 15px;
                                            padding: 30px;
                                            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
                                            transition: all 0.3s ease;
                                            display: flex;
                                            flex-direction: column;
                                            justify-content: space-between;
                                            min-height: 250px; /* Altura mínima para uniformidad */
                                        }

                                        .testimonial-card:hover {
                                            transform: translateY(-8px);
                                            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
                                        }

                                        .testimonial-quote {
                                            font-size: 1.1rem;
                                            line-height: 1.6;
                                            color: #343a40;
                                            margin-bottom: 20px;
                                            font-style: italic;
                                        }

                                        .testimonial-author-info {
                                            display: flex;
                                            align-items: center;
                                            margin-top: auto; /* Empuja la info del autor hacia abajo */
                                        }

                                        .testimonial-avatar {
                                            width: 60px;
                                            height: 60px;
                                            border-radius: 50%;
                                            object-fit: cover;
                                            margin-right: 15px;
                                            border: 3px solid #007bff;
                                        }

                                        .testimonial-name {
                                            font-weight: 700;
                                            color: #002060;
                                            font-size: 1rem;
                                        }

                                        .testimonial-title {
                                            font-size: 0.85rem;
                                            color: #6c757d;
                                        }
                                    </style>

                                    <div class="testimonial-carousel-viewport">
                                        <div class="testimonial-carousel-track">

                                            {{-- Duplicamos los testimonios para el efecto de carrusel infinito --}}
                                            @if (filled($landing->testimonials_section['items'] ?? null))
                                                @foreach($landing->testimonials_section['items'] as $testimonial)
                                                <div class="testimonial-carousel-item">
                                                    <div class="testimonial-card mx-2">
                                                        <p class="testimonial-quote">"{{ $testimonial['description'] ?? '' }}"</p>
                                                        <div class="testimonial-author-info">
                                                            @if(isset($testimonial['image']) && !empty($testimonial['image']))
                                                            <img src="{{ asset("storage/".$testimonial['image']) }}" alt="{{ $testimonial['name'] ?? 'Usuario' }}" class="testimonial-avatar">
                                                            @endif
                                                            <div>
                                                                <h5 class="testimonial-name">{{ $testimonial['name'] ?? '' }}</h5>
                                                                <p class="testimonial-title">{{ $testimonial['presentation'] ?? '' }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endforeach

                                                {{-- Duplicado para efecto infinito --}}
                                                @foreach($landing->testimonials_section['items'] as $testimonial)
                                                <div class="testimonial-carousel-item">
                                                    <div class="testimonial-card mx-2">
                                                        <p class="testimonial-quote">"{{ $testimonial['description'] ?? '' }}"</p>
                                                        <div class="testimonial-author-info">
                                                            @if(isset($testimonial['image']) && !empty($testimonial['image']))
                                                            <img src="{{ asset("storage/".$testimonial['image']) }}" alt="{{ $testimonial['name'] ?? 'Usuario' }}" class="testimonial-avatar">
                                                            @endif
                                                            <div>
                                                                <h5 class="testimonial-name">{{ $testimonial['name'] ?? '' }}</h5>
                                                                <p class="testimonial-title">{{ $testimonial['presentation'] ?? '' }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif


                {{-- Nueva Sección: Planes de Inversión --}}

                @if (filled($landing->investment_section ?? null))
                    <div class="container-fluid card aos-animate mt-5" data-aos="fade-up">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card-body p-4 p-lg-5">
                                    <div class="text-center mb-5">
                                        <span class="badge rounded-pill px-3 py-2 mb-3 shadow-sm border"
                                            style="background-color: rgba(255, 193, 7, 0.1); color: #002060;">
                                            <i class="fa fa-money me-1"></i> {{ $landing->investment_section['name']  }}
                                        </span>
                                        <h2 class="fw-bold display-6" style="color: #002060;">{{ $landing->investment_section['title']  }}</h2>
                                        <p class="text-muted fs-5 mx-auto" style="max-width: 800px;">
                                            {{ $landing->investment_section['description']  }}
                                        </p>
                                    </div>

                                    <div class="row g-4 justify-content-center">
                                        {{-- <!-- Plan Pronto Pago -->
                                        <div class="col-md-4" data-aos="zoom-in" data-aos-delay="100">
                                            <div class="card h-100 border-0 shadow-sm transition-all rounded-4 overflow-hidden bg-white text-center">
                                                <div class="p-4 border-bottom bg-light">
                                                    <h4 class="fw-bold mb-0" style="color: #002060;">Pronto Pago</h4>
                                                    <small class="text-muted">Válido hasta el 15 de abril</small>
                                                </div>
                                                <div class="card-body p-4">
                                                    <div class="mb-4">
                                                        <span class="display-4 fw-bold" style="color: #002060;">S/ 450</span>
                                                        <span class="text-muted">/ pago único</span>
                                                    </div>
                                                    <ul class="list-unstyled text-start mb-4">
                                                        <li class="mb-2"><i class="fa fa-check text-success me-2"></i> Acceso total al programa</li>
                                                        <li class="mb-2"><i class="fa fa-check text-success me-2"></i> Material digital descargable</li>
                                                        <li class="mb-2"><i class="fa fa-check text-success me-2"></i> Certificado de participación</li>
                                                    </ul>
                                                    <a href="#pageContactForm" class="btn btn-outline-warning w-100 fw-bold py-2" style="color: #002060; border-radius: 10px;">Inscribirse ahora</a>
                                                </div>
                                            </div>
                                        </div> --}}

                                        <!-- Plan Regular (Pronto pago)  [0] -->
                                        @if (filled($landing->investment_section['items'][0] ?? null))
                                            @if ($landing->investment_section['items'][0]['price_before_visible'])
                                                <div class="col-md-4" data-aos="zoom-in" data-aos-delay="200">
                                                    <div class="card h-100 border-0 shadow-lg transition-all rounded-4 overflow-hidden bg-white border-top border-warning border-5" style="border-top: 5px solid #ffc107 !important;">
                                                        <div class="p-4 border-bottom" style="background-color: rgba(255, 193, 7, 0.05);">
                                                            <span class="badge bg-warning text-dark mb-2">{{ $landing->investment_section['items'][0]['tag'] }}</span>
                                                            <h4 class="fw-bold mb-0" style="color: #002060;">{{ $landing->investment_section['items'][0]['title'] }}</h4>
                                                            <small class="text-muted">La mas recomendada</small>
                                                        </div>
                                                        <div class="card-body p-4 text-center">
                                                            <div class="mb-2">
                                                                <span class="display-4 fw-bold" style="color: #002060;">S/ {{ $landing->investment_section['items'][0]['price_now'] }}</span>
                                                                <span class="text-muted">/ {{ $landing->investment_section['items'][0]['price_now_text'] ?? "" }}</span>
                                                            </div>

                                                            <div class="mb-4">
                                                                <del class="text-muted fs-4 fw-semibold" style="color: #2d374b;">S/ {{ $landing->investment_section['items'][0]['price_before'] }}</del>
                                                                <span class="text-muted small">/ {{ $landing->investment_section['items'][0]['price_before_text'] ?? "" }}</span>
                                                            </div>
                                                            <ul class="list-unstyled text-start mb-4">
                                                                @if (filled($landing->investment_section['items'][0]['features'] ?? null))
                                                                    @foreach ($landing->investment_section['items'][0]['features'] as $feature)
                                                                    <li class="mb-2"><i class="fa fa-check text-success me-2"></i> <b>{{ $feature }}</b></li>
                                                                    @endforeach
                                                                @endif
                                                            </ul>
                                                            <a href="javascript:void(0)" onclick="procederInscripcion()" class="btn btn-warning w-100 fw-bold py-2 shadow-sm" style="color: #002060; border-radius: 10px;">Inscribirse ahora</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        @endif

                                        <!-- Plan Corporativo -->
                                        @if (filled($landing->investment_section['items'][1] ?? null))
                                            @if ($landing->investment_section['items'][1]['price_before_visible'])
                                                <div class="col-md-4" data-aos="zoom-in" data-aos-delay="300">
                                                    <div class="card h-100 border-0 shadow-sm transition-all rounded-4 overflow-hidden bg-white text-center">
                                                        <div class="p-4 border-bottom bg-light">
                                                            <h4 class="fw-bold mb-0" style="color: #002060;">{{ $landing->investment_section['items'][1]['tag'] }}</h4>
                                                            <small class="text-muted">{{ $landing->investment_section['items'][1]['title'] }}</small>
                                                        </div>
                                                        <div class="card-body p-4">
                                                            <div class="mb-4">
                                                                <span class="display-4 fw-bold" style="color: #002060;">S/ {{ $landing->investment_section['items'][1]['price_now'] }}</span>
                                                                <span class="text-muted">/ {{ $landing->investment_section['items'][1]['price_now_text'] }}</span>
                                                            </div>
                                                            <ul class="list-unstyled text-start mb-4">
                                                                @if (filled($landing->investment_section['items'][1]['features'] ?? null))
                                                                    @foreach ($landing->investment_section['items'][1]['features'] as $feature)
                                                                    <li class="mb-2"><i class="fa fa-check text-success me-2"></i> <b>{{ $feature }}</b></li>
                                                                    @endforeach
                                                                @endif
                                                            </ul>
                                                            <a href="#pageContactForm" class="btn btn-outline-warning w-100 fw-bold py-2" style="color: #002060; border-radius: 10px;">Contactar ventas</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Propuesta 2: Preguntas Frecuentes (Diseño Moderno de Tarjetas) --}}
                @if (filled($landing->faq_section ?? null))
                    <div class="container-fluid card aos-animate mt-5" data-aos="fade-up">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card-body p-4 p-lg-5">
                                    <div class="text-center mb-5">
                                        <span class="badge rounded-pill px-3 py-2 mb-3 shadow-sm border"
                                            style="background-color: rgba(111, 66, 193, 0.1); color: #6f42c1;">
                                            <i class="fa fa-magic me-1"></i> {{ $landing->faq_section['name'] }}
                                        </span>
                                        <h2 class="fw-bold display-6" style="color: #002060;">{{ $landing->faq_section['title'] }}</h2>
                                        <p class="text-muted fs-5 mx-auto" style="max-width: 700px;">
                                            {{ $landing->faq_section['description'] }}
                                        </p>
                                    </div>

                                    <div class="row justify-content-center">
                                        <div class="col-lg-10">
                                            <div class="faq-manual" id="faqManual">
                                                @if (filled($landing->faq_section['items'] ?? null))
                                                        @foreach ($landing->faq_section['items'] as $faq)
                                                            @if ($faq['visible'])
                                                                <div class="faq-item-card shadow-sm mb-3" style="border: 1px solid #edf2f7; border-radius: 15px; overflow: hidden;" onmouseenter="toggleFaq({{ $loop->index }})" onmouseleave="toggleFaq({{ $loop->index }})">
                                                                    <div class="faq-question p-3" style="cursor: pointer; background: #fff; color: #002060; font-weight: 600; display: flex; justify-content: space-between; align-items: center;">
                                                                        <span>{{ $faq['question'] }}</span>
                                                                        <i id="faq-icon-{{ $loop->index }}" class="fa fa-chevron-down" style="transition: transform 0.3s;"></i>
                                                                    </div>
                                                                    <div id="faq-answer-{{ $loop->index }}" class="faq-answer p-3" style="display: none; color: #334155; line-height: 1.7; font-size: 0.95rem; background: #f8f9fa;">
                                                                        {!! $faq['answer'] !!}
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                @endif
                                            </div>

                                            <div class="text-center mt-5 p-4 rounded-4" style="background-color: #f8f9fa; border: 1px dashed #dee2e6;">
                                                <p class="mb-3 fw-bold" style="color: #002060;">¿Aún tienes dudas específicas?</p>
                                                <a href="{{ $landing->whatsapp_link }}" class="btn btn-success rounded-pill px-4 shadow-sm d-inline-flex align-items-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" style="width: 18px; height: 18px; fill: white;" class="me-2">
                                                        <path d="M380.9 97.1c-41.9-42-97.7-65.1-157-65.1-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480 117.7 449.1c32.4 17.7 68.9 27 106.1 27l.1 0c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3 18.6-68.1-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1s56.2 81.2 56.1 130.5c0 101.8-84.9 184.6-186.6 184.6zM325.1 300.5c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8s-14.3 18-17.6 21.8c-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7s-12.5-30.1-17.1-41.2c-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2s-9.7 1.4-14.8 6.9c-5.1 5.6-19.4 19-19.4 46.3s19.9 53.7 22.6 57.4c2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4s4.6-24.1 3.2-26.4c-1.3-2.5-5-3.9-10.5-6.6z"/>
                                                    </svg>
                                                    Hablar con un Asesor
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <x-courselanding.certificate-template />

            </div>
        </div>
        <!-- footer start-->
        <x-footer />
    </div>

    {{-- Ideally, this JS should be at the end of your <body> in the main layout file --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        AOS.init({
            mirror: true, // This makes animations trigger on scroll up as well
            duration: 800, // Animation duration
            once: false // Ensures animation happens every time you scroll to it
        });
    </script>



    <script>
        const myModal = document.getElementById('myModal')
        const myInput = document.getElementById('myInput')

        myModal.addEventListener('shown.bs.modal', () => {
            myInput.focus()
        })
    </script>

    <script>
        function procederInscripcion() {
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
                        id: {{ $onli_item_id ?? 0 }},
                        nombre: "{{ $landing->course->description ?? 'Curso' }}",
                        precio: {{ isset($landing->investment_section['items'][0]['price_now']) ? $landing->investment_section['items'][0]['price_now'] : 0 }},
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

        // Funciones para el modal de teachers
        function openTeacherModal(index) {
            document.getElementById('teacher-modal-' + index).style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeTeacherModal(index) {
            document.getElementById('teacher-modal-' + index).style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    </script>

    <script>
        let form = document.getElementById('pageContactForm');
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            var formulario = document.getElementById('pageContactForm');
            var formData = new FormData(formulario);

            // Obtener el código del país del select y concatenarlo al número sin punto
            const countrySelect = document.getElementById('countryPhoneSelect');
            const prefix = countrySelect.value;
            const phoneValue = formData.get('phone');
            // Concatenar sin punto: +51943781600
            formData.set('phone', prefix + phoneValue);

            var submitButton = document.getElementById('submitPageContactButton');
            submitButton.disabled = true;
            submitButton.style.opacity = 0.25;

            var xhr = new XMLHttpRequest();

            xhr.open('POST', "{{ route('apisubscriber') }}", true);

            xhr.onload = function() {
                submitButton.disabled = false;
                submitButton.style.opacity = 1;
                if (xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    Swal.fire({
                        icon: 'success',
                        title: 'Enhorabuena',
                        text: response.message,
                        customClass: {
                            container: 'sweet-modal-zindex'
                        }
                    });
                    formulario.reset();
                } else if (xhr.status === 422) {
                    var errorResponse = JSON.parse(xhr.responseText);
                    var errorMessages = errorResponse.errors;
                    var errorMessageContainer = document.getElementById('messagePageContact');
                    errorMessageContainer.innerHTML = 'Errores de validación:<br>';
                    for (var field in errorMessages) {
                        if (errorMessages.hasOwnProperty(field)) {
                            errorMessageContainer.innerHTML += field + ': ' + errorMessages[field].join(', ') +
                                '<br>';
                        }
                    }
                } else {
                    console.error('Error en la solicitud: ' + xhr.status);
                }
                const downloadUrl = "{{ isset($landing->course->brochure) ? ($landing->course->brochure->path_file ?? '') : '' }}";
                window.open(downloadUrl, '_blank');

            };

            xhr.send(formData);
        });
    </script>

<script>
        $(document).ready(function() {
            function formatCountry(country) {
                if (!country.id) { return country.text; }
                var code = $(country.element).data('code');
                var $country = $(
                    '<span><img src="https://flagcdn.com/w20/' + code.toLowerCase() + '.png" class="me-2" style="vertical-align: middle; border: 1px solid #eee; width: 20px;">' + country.text + '</span>'
                );
                return $country;
            };

            $('#countryPhoneSelect').select2({
                templateResult: formatCountry,
                templateSelection: formatCountry,
                dropdownParent: $('#countryPhoneSelect').parent()
            });
        });

        // Toggle FAQ Manual - abrir al entrar, cerrar al salir
        function toggleFaq(index) {
            var answer = document.getElementById('faq-answer-' + index);
            var icon = document.getElementById('faq-icon-' + index);

            // Verificar si está oculto o tiene display vacío
            var isHidden = answer.style.display === 'none' || answer.style.display === '';

            if (isHidden) {
                answer.style.display = 'block';
                icon.style.transform = 'rotate(180deg)';
            } else {
                answer.style.display = 'none';
                icon.style.transform = 'rotate(0deg)';
            }
        }
    </script>


@stop
