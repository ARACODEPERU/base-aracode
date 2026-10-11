@extends('kapta.layouts.app')

@section('title', 'KAPTA LMS — Gestiona tu formación. Impulsa tu crecimiento.')
@section('description', 'KAPTA LMS administra cursos, alumnos, matrículas, evaluaciones y certificados desde una sola plataforma. Dominio, SSL, hosting y página web incluidos según el plan.')

@php
    $contacto = config('kapta.contacto');
    $demoHref = $contacto['whatsapp_url'] . '?text=' . urlencode(config('kapta.mensajes.demo_whatsapp'));
    $planes = config('kapta.planes');
    $niveles = config('kapta.niveles');
@endphp

@section('content')
    @include('kapta.components.navbar')

    {{-- ==========================================================
         A · HERO
         ========================================================== --}}
    <section class="ka-surface-dark relative overflow-hidden pb-20 pt-28 sm:pt-32 lg:pb-24 lg:pt-40">
        {{-- Fondo: fotografía real del proyecto con velo azul marino --}}
        <div class="absolute inset-0 -z-10" aria-hidden="true">
            <img src="{{ asset('themes/kapta/images/bg-hero.webp') }}" alt=""
                 class="ka-photo opacity-[0.22]" width="2000" height="1271">
            <div class="absolute inset-0"
                 style="background-image: linear-gradient(180deg, rgba(11,23,64,.84) 0%, rgba(11,23,64,.94) 52%, #0b1740 100%)"></div>
        </div>
        <div class="ka-grid-lines absolute inset-0 -z-10" aria-hidden="true"></div>
        <div class="ka-halo right-[-12%] top-[-14%] h-[440px] w-[440px]" aria-hidden="true"></div>

        <div class="ka-container relative">
            <div class="grid grid-cols-1 items-center gap-12 lg:grid-cols-[1.02fr_0.98fr] lg:gap-16">
                {{-- Columna izquierda: mensaje --}}
                <div>
                    <span class="ka-badge ka-reveal">Plataforma LMS</span>

                    <h1 class="ka-h1 ka-reveal mt-6 text-white">
                        Gestiona tu formación.<br>
                        <span class="ka-accent">Impulsa tu crecimiento.</span>
                    </h1>

                    <p class="ka-lead ka-reveal mt-6 max-w-xl text-ka-muted">
                        Administra cursos, alumnos, evaluaciones y certificados desde una plataforma
                        diseñada para simplificar la gestión de tu institución educativa.
                    </p>

                    <div class="ka-reveal mt-9 flex flex-col gap-4 sm:flex-row">
                        <a href="{{ $demoHref }}" target="_blank" rel="noopener"
                           class="ka-btn ka-btn-primary ka-btn-lg">
                            Solicitar una demo
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </a>
                        <a href="#funcionalidades" class="ka-btn ka-btn-secondary ka-btn-lg">
                            Explorar funcionalidades
                        </a>
                    </div>

                    <ul class="ka-reveal mt-10 grid grid-cols-1 gap-3 text-sm text-ka-muted sm:grid-cols-3">
                        @foreach (['Campus virtual con aulas', 'Matrículas y usuarios', 'Certificados en PDF'] as $punto)
                            <li class="flex items-center gap-2">
                                <svg class="h-4 w-4 shrink-0 text-ka-cyan" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ $punto }}
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Columna derecha: composición ilustrativa de la plataforma --}}
                {{-- Cuando existan capturas auténticas, se cambia este bloque por las imágenes reales. --}}
                <div class="ka-reveal">
                    @include('kapta.components.mock', ['variant' => 'panel', 'titulo' => 'Panel de KAPTA LMS'])
                </div>
            </div>
        </div>
    </section>

    {{-- ==========================================================
         C · FRANJA DE VALOR
         ========================================================== --}}
    <section class="ka-surface-light border-b" style="border-color: var(--ka-surface-line)">
        <div class="ka-container ka-section-tight">
            <div class="ka-rail">
                @php
                    $atributos = [
                        [
                            'titulo' => 'Gestión centralizada',
                            'texto' => 'Cursos, docentes, alumnos y matrículas en un mismo panel.',
                            'icono' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M4 6h16M4 12h16M4 18h10"/>',
                        ],
                        [
                            'titulo' => 'Acceso multidispositivo',
                            'texto' => 'El campus se abre desde computadora, tablet y celular.',
                            'icono' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>',
                        ],
                        [
                            'titulo' => 'Evaluaciones y certificados',
                            'texto' => 'Exámenes con nota y certificados en PDF o automáticos.',
                            'icono' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 12l2 2 4-4M12 3l7 4v5c0 4.5-3 8-7 9-4-1-7-4.5-7-9V7l7-4z"/>',
                        ],
                        [
                            'titulo' => 'Infraestructura incluida',
                            'texto' => 'Dominio, SSL y hosting, más página web según el plan.',
                            'icono' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M5 12h14M5 12a7 7 0 0114 0M5 12a7 7 0 0014 0M12 4v16"/>',
                        ],
                    ];
                @endphp

                @foreach ($atributos as $atributo)
                    <div class="ka-rail-item">
                        <span class="ka-icon-box h-10 w-10">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                {!! $atributo['icono'] !!}
                            </svg>
                        </span>
                        <div>
                            <p class="font-display text-sm font-bold ka-ink">{{ $atributo['titulo'] }}</p>
                            <p class="mt-1 text-xs leading-relaxed ka-slate">{{ $atributo['texto'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ==========================================================
         D · BENEFICIOS
         ========================================================== --}}
    <section class="ka-surface-mist ka-section">
        <div class="ka-container">
            <div class="mx-auto max-w-2xl text-center">
                <span class="ka-badge">Beneficios</span>
                <h2 class="ka-h2 ka-reveal mt-5 ka-ink">
                    Más organización para tu institución. Más tiempo para crecer.
                </h2>
                <p class="ka-lead ka-reveal mt-4">
                    KAPTA ordena el trabajo administrativo que hoy se reparte entre hojas de cálculo,
                    correos y grupos de mensajes.
                </p>
            </div>

            <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @php
                    $beneficios = [
                        [
                            'titulo' => 'Todo en un solo lugar',
                            'texto' => 'Dejas de saltar entre herramientas: alumnos, docentes, cursos, matrículas, pagos, evaluaciones y certificados viven en la misma plataforma.',
                            'icono' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M4 7v10a2 2 0 002 2h12a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H6a2 2 0 00-2 2z"/>',
                        ],
                        [
                            'titulo' => 'Tu institución, tu imagen',
                            'texto' => 'El campus lleva tu logo, tus colores y tu dominio propio, para que el alumno reconozca a tu institución y no a un portal ajeno.',
                            'icono' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M7 21h10M12 17v4M4 5h16v10a2 2 0 01-2 2H6a2 2 0 01-2-2V5z"/>',
                        ],
                        [
                            'titulo' => 'Vende cursos sin fricción',
                            'texto' => 'La tienda online y la pasarela de pagos permiten cobrar la matrícula de forma automática, sin perseguir comprobantes.',
                            'icono' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M3 10h18M7 15h1m4 0h1M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',
                        ],
                        [
                            'titulo' => 'Decisiones con datos',
                            'texto' => 'Los reportes muestran avance, notas y actividad, para detectar a tiempo quién necesita acompañamiento.',
                            'icono' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
                        ],
                    ];
                @endphp

                @foreach ($beneficios as $beneficio)
                    <div class="ka-card ka-card-interactive ka-reveal">
                        <span class="ka-icon-box">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                {!! $beneficio['icono'] !!}
                            </svg>
                        </span>
                        <h3 class="ka-h3 mt-5 ka-ink">{{ $beneficio['titulo'] }}</h3>
                        <p class="mt-2 text-sm leading-relaxed ka-slate">{{ $beneficio['texto'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ==========================================================
         E · FUNCIONALIDADES
         ========================================================== --}}
    <section id="funcionalidades" class="ka-surface-light ka-section scroll-mt-24">
        <div class="ka-container">
            <div class="mx-auto max-w-2xl text-center">
                <span class="ka-badge">Funcionalidades</span>
                <h2 class="ka-h2 ka-reveal mt-5 ka-ink">Lo que resuelve KAPTA en el día a día</h2>
                <p class="ka-lead ka-reveal mt-4">
                    Cada pieza está pensada para el trabajo real de un área académica: publicar contenido,
                    matricular, evaluar y certificar.
                </p>
            </div>

            {{-- Fila 1 · contenido del curso --}}
            <div class="mt-14 grid grid-cols-1 items-center gap-10 lg:grid-cols-2 lg:gap-14">
                <div class="ka-reveal">
                    <span class="ka-badge ka-badge-neutral">Campus virtual</span>
                    <h3 class="ka-h2 mt-5 text-2xl ka-ink sm:text-3xl">Cursos y contenidos ordenados por módulos</h3>
                    <p class="ka-lead mt-4 text-base">
                        Arma tus programas por módulos y sesiones con video, documentos, PDFs y enlaces.
                        Lo que antes vivía en carpetas compartidas queda dentro del campus, disponible
                        para cada alumno matriculado.
                    </p>
                    <ul class="mt-6 space-y-3">
                        @foreach ([
                            'Módulos y sesiones con materiales en video, PDF y enlaces',
                            'Almacenamiento propio más enlaces de Google Drive para crecer',
                            'Certificados subidos en PDF o emitidos automáticamente, según el plan',
                        ] as $punto)
                            <li class="ka-check-item">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ $punto }}
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="ka-reveal ka-feature-media">
                    <img src="{{ asset('themes/webpage/images/misc/s4.webp') }}"
                         alt="Persona estudiando en línea desde una tablet y una computadora portátil"
                         class="ka-photo aspect-[4/3]" width="1271" height="1271" loading="lazy" decoding="async">
                </div>
            </div>

            {{-- Fila 2 · gestión y seguimiento --}}
            <div class="mt-16 grid grid-cols-1 items-center gap-10 lg:grid-cols-2 lg:gap-14">
                <div class="ka-reveal ka-feature-media lg:order-2">
                    <img src="{{ asset('themes/webpage/images/misc/s6.jpg') }}"
                         alt="Escritorio de trabajo con reportes de avance en pantalla"
                         class="ka-photo aspect-[4/3]" width="1080" height="1080" loading="lazy" decoding="async">
                </div>
                <div class="ka-reveal lg:order-1">
                    <span class="ka-badge ka-badge-neutral">Matrículas y pagos</span>
                    <h3 class="ka-h2 mt-5 text-2xl ka-ink sm:text-3xl">De la matrícula al certificado, sin planillas sueltas</h3>
                    <p class="ka-lead mt-4 text-base">
                        Registra alumnos y docentes, controla los usuarios administrativos y deja que la
                        matrícula se genere sola cuando alguien compra un curso en la tienda online.
                    </p>
                    <ul class="mt-6 space-y-3">
                        @foreach ([
                            'Matrícula automática al comprar en la tienda y manual desde el campus',
                            'Pasarela de pagos Mercado Pago (Perú) y/o PayPal, según el plan',
                            'Reportes de avance, notas y actividad por alumno',
                        ] as $punto)
                            <li class="ka-check-item">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ $punto }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- Mosaico de capacidades --}}
            <div class="mt-16 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @php
                    $mosaico = [
                        [
                            'titulo' => 'Alumnos y docentes',
                            'texto' => 'Registra personas, asigna docentes a cada curso y controla los usuarios administrativos del sistema.',
                            'icono' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M16 19v-1a4 4 0 00-4-4H7a4 4 0 00-4 4v1M9.5 10a3.5 3.5 0 100-7 3.5 3.5 0 000 7zM21 19v-1a4 4 0 00-3-3.87M16.5 3.13a4 4 0 010 7.75"/>',
                        ],
                        [
                            'titulo' => 'Exámenes y evaluaciones',
                            'texto' => 'Carga actividades y evaluaciones, registra notas y sigue el avance de cada participante.',
                            'icono' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 5h6M9 5a2 2 0 00-2 2v12a2 2 0 002 2h6a2 2 0 002-2V7a2 2 0 00-2-2M9 5V4a1 1 0 011-1h4a1 1 0 011 1v1M9 12h6M9 16h4"/>',
                        ],
                        [
                            'titulo' => 'Certificación',
                            'texto' => 'Entrega certificados en PDF o emítelos automáticamente al completar el curso o la evaluación final.',
                            'icono' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 15a5 5 0 100-10 5 5 0 000 10zM8.5 13.5L7 22l5-2.5L17 22l-1.5-8.5"/>',
                        ],
                        [
                            'titulo' => 'Reportes y seguimiento',
                            'texto' => 'Paneles con progreso, resultados de exámenes y actividad, para acompañar a quien lo necesita.',
                            'icono' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
                        ],
                        [
                            'titulo' => 'Enlaces de Google Drive',
                            'texto' => 'Suma almacenamiento enlazando archivos de Drive sin cargar todo dentro de la plataforma.',
                            'icono' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M10 13a5 5 0 007.07 0l1.42-1.42a5 5 0 00-7.07-7.07L10.7 5.23M14 11a5 5 0 00-7.07 0L5.5 12.42a5 5 0 007.07 7.07l.72-.72"/>',
                        ],
                        [
                            'titulo' => 'Desde cualquier dispositivo',
                            'texto' => 'El campus abre en navegador de escritorio, tablet y celular, sin instalar nada.',
                            'icono' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 17h6M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>',
                        ],
                    ];
                @endphp

                @foreach ($mosaico as $pieza)
                    <div class="ka-tile ka-card-interactive ka-reveal">
                        <span class="ka-icon-box h-10 w-10">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                {!! $pieza['icono'] !!}
                            </svg>
                        </span>
                        <h3 class="mt-4 font-display text-base font-bold ka-ink">{{ $pieza['titulo'] }}</h3>
                        <p class="mt-2 text-sm leading-relaxed ka-slate">{{ $pieza['texto'] }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Infraestructura incluida, según el plan --}}
            <div class="ka-reveal mt-6 flex flex-col items-start justify-between gap-6 rounded-[1.25rem] border p-7 sm:flex-row sm:items-center"
                 style="border-color: var(--ka-surface-line); background-color: rgba(1,136,238,.05)">
                <div class="max-w-2xl">
                    <p class="font-display text-lg font-extrabold ka-ink">Dominio, SSL y hosting, incluidos</p>
                    <p class="mt-2 text-sm leading-relaxed ka-slate">
                        Todos los planes incluyen dominio, certificado SSL y hosting, además del mantenimiento
                        del sistema. Según el plan se suma página web estándar o personalizada, landing page,
                        CMS para editarla y facturación electrónica en el plan más completo.
                    </p>
                </div>
                <a href="{{ route('kapta.planes') }}" class="ka-btn ka-btn-secondary shrink-0">
                    Ver qué incluye cada plan
                </a>
            </div>
        </div>
    </section>

    {{-- ==========================================================
         F · PRESENTACIÓN VISUAL DEL PRODUCTO
         ========================================================== --}}
    <section id="producto" class="ka-surface-dark ka-section relative scroll-mt-24 overflow-hidden">
        <div class="ka-grid-lines absolute inset-0" aria-hidden="true"></div>
        <div class="ka-halo left-[-12%] top-[10%] h-[380px] w-[380px]" aria-hidden="true"></div>

        <div class="ka-container relative">
            <div class="mx-auto max-w-2xl text-center">
                <span class="ka-badge">Así funciona</span>
                <h2 class="ka-h2 ka-reveal mt-5 text-white">Tres pantallas que resumen la plataforma</h2>
                <p class="ka-lead ka-reveal mt-4">
                    El panel administrativo, el campus del alumno y la emisión de certificados.
                    Cada rol ve solo lo que necesita.
                </p>
            </div>

            {{-- Marcos preparados para recibir las capturas auténticas cuando estén disponibles --}}
            <div class="mt-14 grid grid-cols-1 gap-10 lg:grid-cols-[1.15fr_0.85fr] lg:items-start">
                <div class="ka-reveal">
                    @include('kapta.components.mock', ['variant' => 'panel', 'titulo' => 'Panel administrativo'])
                </div>

                <div class="grid grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-1">
                    <div class="ka-reveal">
                        @include('kapta.components.mock', ['variant' => 'alumno', 'titulo' => 'Campus del alumno'])
                    </div>
                    <div class="ka-reveal">
                        @include('kapta.components.mock', ['variant' => 'certificado', 'titulo' => 'Certificado'])
                    </div>
                </div>
            </div>

            <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-3">
                @php
                    $roles = [
                        ['titulo' => 'Administración', 'texto' => 'Cursos, docentes, matrículas, usuarios y reportes.'],
                        ['titulo' => 'Docentes', 'texto' => 'Materiales por sesión, evaluaciones y notas del grupo.'],
                        ['titulo' => 'Alumnos', 'texto' => 'Su curso, su avance y su certificado en un mismo lugar.'],
                    ];
                @endphp

                @foreach ($roles as $rol)
                    <div class="ka-reveal rounded-2xl border p-5" style="border-color: var(--ka-surface-line)">
                        <p class="font-display text-sm font-bold text-white">{{ $rol['titulo'] }}</p>
                        <p class="mt-2 text-sm leading-relaxed text-ka-muted">{{ $rol['texto'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ==========================================================
         G · SOLUCIONES POR TIPO DE CLIENTE
         ========================================================== --}}
    <section id="soluciones" class="ka-surface-light ka-section scroll-mt-24">
        <div class="ka-container">
            <div class="grid grid-cols-1 gap-12 lg:grid-cols-2 lg:gap-16">
                <div>
                    <span class="ka-badge">Soluciones</span>
                    <h2 class="ka-h2 ka-reveal mt-5 ka-ink">¿Para quién trabaja KAPTA LMS?</h2>
                    <p class="ka-lead ka-reveal mt-4">
                        La misma plataforma se adapta al tamaño y al modelo de cada organización:
                        desde quien dicta su primer curso hasta quien capacita equipos completos.
                    </p>

                    <ul class="mt-8 space-y-5">
                        @php
                            $perfiles = [
                                ['titulo' => 'Academias', 'texto' => 'Cursos cortos y ciclos con matrícula en línea y certificado al terminar.'],
                                ['titulo' => 'Institutos', 'texto' => 'Programas por módulos, docentes asignados y control académico del avance.'],
                                ['titulo' => 'Centros de capacitación', 'texto' => 'Varias sedes y grupos simultáneos con usuarios administrativos por área.'],
                                ['titulo' => 'Emprendedores que venden cursos', 'texto' => 'Página web, tienda online y pasarela de pagos para cobrar sin intermediarios.'],
                                ['titulo' => 'Organizaciones que capacitan a sus equipos', 'texto' => 'Formación interna con seguimiento por colaborador y certificación al cierre.'],
                            ];
                        @endphp

                        @foreach ($perfiles as $perfil)
                            <li class="ka-reveal flex gap-4">
                                <span class="ka-step-number">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </span>
                                <div>
                                    <p class="font-display font-bold ka-ink">{{ $perfil['titulo'] }}</p>
                                    <p class="mt-1 text-sm leading-relaxed ka-slate">{{ $perfil['texto'] }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="grid grid-cols-2 gap-4 lg:gap-6">
                    {{-- Fotografía real del proyecto: un aula moderna, apropiada para
                         academias, institutos y centros de capacitación. --}}
                    <div class="ka-reveal ka-feature-media col-span-2">
                        <img src="{{ asset('themes/kapta/images/bg-hero.webp') }}"
                             alt="Aula moderna con butacas listas para una clase"
                             class="ka-photo aspect-[16/10]" width="2000" height="1271" loading="lazy" decoding="async">
                    </div>
                    <div class="ka-reveal col-span-2 rounded-[1.25rem] border p-6"
                         style="border-color: var(--ka-surface-line); background-color: var(--ka-mist)">
                        <p class="font-display text-sm font-bold ka-ink">Un mismo campus, varias audiencias</p>
                        <p class="mt-2 text-sm leading-relaxed ka-slate">
                            Cada perfil entra con su propio rol: administración gestiona, el docente dicta
                            y publica, el alumno cursa. Sin instalar nada y desde cualquier dispositivo.
                        </p>
                        <a href="{{ $demoHref }}" target="_blank" rel="noopener" class="ka-btn ka-btn-primary ka-btn-sm mt-5">
                            Verlo con mis cursos
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ==========================================================
         H · PLANES Y PRECIOS (resumen)
         ========================================================== --}}
    <section id="planes" class="ka-surface-mist ka-section scroll-mt-24">
        <div class="ka-container">
            <div class="mx-auto max-w-2xl text-center">
                <span class="ka-badge">Planes</span>
                <h2 class="ka-h2 ka-reveal mt-5 ka-ink">Un plan para cada tamaño de institución</h2>
                <p class="ka-lead ka-reveal mt-4">
                    Seis planes agrupados en tres niveles. Todos incluyen dominio, SSL, hosting,
                    mantenimiento y certificación; cambian la capacidad y los módulos.
                </p>
            </div>

            <div class="mt-12 grid grid-cols-1 gap-6 lg:grid-cols-3">
                @foreach ($niveles as $clave => $nivel)
                    @php
                        $delNivel = collect($planes)->where('nivel', $clave);
                        $primero = $delNivel->first();
                    @endphp

                    <div class="ka-card ka-card-interactive ka-reveal">
                        <p class="font-display text-lg font-extrabold ka-ink">{{ $nivel['nombre'] }}</p>
                        <p class="mt-2 text-sm leading-relaxed ka-slate">{{ $nivel['resumen'] }}</p>

                        <p class="mt-6 flex items-baseline gap-2">
                            <span class="text-xs font-bold uppercase tracking-wide ka-slate">Desde</span>
                            <span class="ka-plan-price">{{ $primero['mensual'] }}</span>
                            <span class="ka-plan-period">/mes</span>
                        </p>

                        <ul class="mt-6 space-y-3">
                            @foreach ($delNivel as $plan)
                                <li class="ka-check-item">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>
                                        <span class="font-semibold ka-ink">{{ $plan['nombre'] }}</span>
                                        · {{ $plan['capacidad'] }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>

                        <a href="{{ route('kapta.planes') }}#{{ $clave }}" class="ka-btn ka-btn-secondary mt-7">
                            Ver el detalle
                        </a>
                    </div>
                @endforeach
            </div>

            <div class="ka-reveal mt-8 grid grid-cols-1 gap-4 lg:grid-cols-2">
                <div class="ka-note">
                    <span class="font-semibold ka-ink">Alumnos activos:</span>
                    los planes se miden por los alumnos que cursan de forma simultánea, no por el total
                    de alumnos registrados en el campus.
                </div>
                <div class="ka-note">
                    {{ config('kapta.mensajes.nota_precios') }}
                </div>
            </div>

            <div class="mt-8 text-center">
                <a href="{{ route('kapta.planes') }}" class="ka-btn ka-btn-primary ka-btn-lg">
                    Comparar los seis planes
                </a>
            </div>
        </div>
    </section>

    {{-- ==========================================================
         I · PREGUNTAS FRECUENTES
         ========================================================== --}}
    <section id="faq" class="ka-surface-light ka-section scroll-mt-24">
        <div class="ka-container">
            <div class="mx-auto max-w-3xl">
                <div class="text-center">
                    <span class="ka-badge">Preguntas frecuentes</span>
                    <h2 class="ka-h2 ka-reveal mt-5 ka-ink">Dudas habituales antes de empezar</h2>
                </div>

                <div class="mt-10 space-y-3">
                    @php
                        $faqs = [
                            [
                                'p' => '¿Qué es KAPTA LMS?',
                                'r' => 'Es una plataforma de gestión del aprendizaje (LMS): reúne el campus virtual con cursos y materiales, la administración de alumnos y docentes, las matrículas, las evaluaciones y la emisión de certificados. Es un producto de ARACODE Smart Solutions.',
                            ],
                            [
                                'p' => '¿Para quién está diseñado?',
                                'r' => 'Para academias, institutos, centros de capacitación, emprendedores que comercializan sus cursos y organizaciones que capacitan a sus equipos.',
                            ],
                            [
                                'p' => '¿Qué funcionalidades incluye?',
                                'r' => 'Campus virtual con cursos y aulas, administración de alumnos y docentes, matrículas y usuarios, exámenes y evaluaciones, certificados en PDF o automáticos, pagos en línea, reportes y seguimiento, almacenamiento con enlaces de Google Drive, y página web o landing page con dominio propio según el plan.',
                            ],
                            [
                                'p' => '¿Cómo funciona la contratación?',
                                'r' => 'Eliges el plan según tu cantidad de alumnos activos y solicitas una demo. Revisamos la plataforma con tus cursos y, si decides avanzar, coordinamos la puesta en marcha por WhatsApp o correo.',
                            ],
                            [
                                'p' => '¿Qué incluye cada plan?',
                                'r' => 'Todos los planes incluyen dominio, SSL, hosting, mantenimiento del sistema y el campus virtual. Cambian la cantidad de alumnos activos, los usuarios administrativos, los correos corporativos, el almacenamiento, la página web incluida, la tienda online, la pasarela de pagos y el tipo de certificación. La comparación completa está en la página de planes.',
                            ],
                            [
                                'p' => '¿Qué diferencia hay entre alumnos registrados y alumnos activos?',
                                'r' => 'Los planes se miden por alumnos activos: quienes están cursando de forma simultánea. No se mide por el total de alumnos registrados en el campus. Si necesitas el detalle exacto para tu caso, escríbenos y te lo confirmamos.',
                            ],
                            [
                                'p' => '¿Cómo se gestionan las matrículas y los certificados?',
                                'r' => 'La matrícula puede ser manual desde el campus o automática cuando el alumno compra el curso en la tienda online, según el plan. Los certificados se entregan como PDF cargado o se emiten automáticamente al completar el curso o la evaluación final, también según el plan.',
                            ],
                            [
                                'p' => '¿Qué opciones de pago están disponibles?',
                                'r' => 'Según el plan se integra Mercado Pago (Perú) y/o PayPal para cobrar los cursos en línea. El plan Corporativo añade además facturación electrónica.',
                            ],
                            [
                                'p' => '¿Cómo solicito una demostración o soporte?',
                                'r' => 'Escríbenos por WhatsApp o al correo comercial y coordinamos una demostración con tus propios cursos, tu estructura de módulos y tus certificados.',
                            ],
                        ];
                    @endphp

                    @foreach ($faqs as $faq)
                        <details class="ka-faq ka-reveal">
                            <summary>{{ $faq['p'] }}</summary>
                            <p class="ka-faq-body">{{ $faq['r'] }}</p>
                        </details>
                    @endforeach
                </div>

                <p class="mt-8 text-center text-sm ka-slate">
                    ¿Te queda otra duda?
                    <a href="{{ route('kapta.contacto') }}" class="ka-link">Escríbenos y la resolvemos</a>.
                </p>
            </div>
        </div>
    </section>

    {{-- ==========================================================
         J · LLAMADA A LA ACCIÓN FINAL
         ========================================================== --}}
    <section class="ka-surface-dark relative overflow-hidden pb-20 pt-4 lg:pb-24">
        <div class="ka-container">
            <div class="relative overflow-hidden rounded-[1.5rem] border px-8 py-12 text-center lg:px-14 lg:py-16"
                 style="border-color: var(--ka-surface-line); background-image: linear-gradient(135deg, #122459 0%, #0b1740 60%, #162d6b 100%)">
                <div class="absolute inset-0 -z-10" aria-hidden="true">
                    <img src="{{ asset('themes/kapta/images/bg-page.webp') }}" alt=""
                         class="ka-photo opacity-[0.16]" width="2000" height="1271" loading="lazy" decoding="async">
                </div>
                <div class="ka-grid-lines absolute inset-0 -z-10" aria-hidden="true"></div>

                <h2 class="ka-h2 relative text-white">Tu próxima etapa de crecimiento comienza aquí</h2>
                <p class="relative mx-auto mt-4 max-w-2xl text-ka-muted">
                    Descubre cómo KAPTA LMS puede ayudarte a gestionar tu formación de manera más organizada.
                </p>
                <div class="relative mt-8 flex flex-col justify-center gap-4 sm:flex-row">
                    <a href="{{ $demoHref }}" target="_blank" rel="noopener" class="ka-btn ka-btn-primary ka-btn-lg">
                        Solicitar una demo
                    </a>
                    <a href="{{ route('kapta.planes') }}" class="ka-btn ka-btn-secondary ka-btn-lg">
                        Ver planes y precios
                    </a>
                </div>
            </div>
        </div>
    </section>

    @include('kapta.components.footer')
@endsection
