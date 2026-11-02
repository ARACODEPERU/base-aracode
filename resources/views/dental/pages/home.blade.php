@extends('aracode.layouts.webpage')

@section('meta_title', 'Dental | Agenda, atenciones y odontograma para clínicas dentales — ARACODE')
@section('meta_description', 'Dental es el módulo de ARACODE para clínicas y consultorios odontológicos: calendario de citas por doctor, horas ocupadas, atenciones con historia clínica, odontograma por pieza dental, próxima cita desde la atención y cobros con comprobante electrónico.')
@section('og_title', 'Dental — Agenda, atenciones y odontograma | ARACODE')
@section('og_description', 'Agenda odontológica por doctor, horas ocupadas, atenciones con historia clínica, odontograma por pieza dental y próxima cita programada desde la propia atención.')

@php
    /*
        Portada del módulo Dental.

        Se publica en la raíz del dominio cuando en Parámetros → «Pagina web
        principal» se elige la opción Dental (value 7 de
        config/pagina_principal.php).

        Lo que la página promete es lo que el módulo Dental hace hoy:
        calendario de citas y horas ocupadas por doctor
        (AppointmentController::calendar y getBusyHours), estados e importancia
        de la cita (updateStatus / updateImportant), atenciones odontológicas
        (DentAttentionController) con la próxima cita programada desde la propia
        atención (next_appointment_id) y la historia clínica compartida con el
        módulo Salud, que incluye el odontograma por pieza dental
        (HealAttentionController). Los cobros con comprobante electrónico son
        los de Salud (HealProcedureChargeController).

        La piel visual es la misma de la portada de salud
        (public/themes/salud/css/salud.css): la familia de clases `sl-` se
        comparte para no duplicar la hoja de estilos.

        Fotos gratuitas (Unsplash), copiadas al proyecto en
        public/themes/dental/images para no depender de internet:
          - hero.jpg          odontóloga atendiendo a una paciente
          - consultorio.jpg   consultorio dental con sillón y equipo
          - diagnostico.jpg   odontólogo revisando radiografías

        Promociones: el texto es comercial y se edita en este arreglo.
    */

    $whatsapp = 'https://wa.me/51917295856?text=' . rawurlencode('Hola, quiero información y las promociones del módulo Dental.');
    $whatsappPromo = 'https://wa.me/51917295856?text=' . rawurlencode('Hola, quiero aprovechar la promoción de Dental (12 meses al precio de 10).');

    $cifras = [
        ['valor' => '32', 'texto' => 'piezas en el odontograma'],
        ['valor' => '1 clic', 'texto' => 'para programar la próxima cita'],
        ['valor' => 'Por doctor', 'texto' => 'calendario y horas ocupadas'],
        ['valor' => '100%', 'texto' => 'en el navegador, sin instalar nada'],
    ];

    $agenda = [
        'Calendario general del consultorio y agenda de cada doctor, con vista por día, semana y mes.',
        'Horas ocupadas consultadas por fecha y doctor antes de agendar, para no cruzar citas.',
        'Duración configurable por cita, según el tratamiento que se va a realizar.',
        'Estados de la cita: confirmada, en sala, atendida o ausente, y marcado de las citas importantes.',
        'Reprogramar o eliminar una cita desde el calendario, sin salir de la agenda.',
    ];

    $atencion = [
        'Ficha del paciente con sus antecedentes, alergias y últimas atenciones.',
        'Motivo de consulta, enfermedad actual, examen físico y tratamiento de cada sesión.',
        'Odontograma por pieza dental, con las condiciones y tratamientos de cada diente.',
        'Próxima cita programada desde la propia atención, con fecha, hora y doctor.',
        'Exámenes auxiliares con descripción, lugar y motivo, adjuntos a la atención.',
        'Historia clínica en PDF con el logo y los datos del consultorio.',
    ];

    $caracteristicas = [
        [
            'icon' => '<svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
            'title' => 'Calendario de citas',
            'description' => 'Vista por día, semana y mes, con el calendario general y el de cada doctor, y la duración de cada cita.',
        ],
        [
            'icon' => '<svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
            'title' => 'Horas ocupadas',
            'description' => 'Antes de agendar se consultan las horas ocupadas del doctor en esa fecha, para no superponer pacientes.',
        ],
        [
            'icon' => '<svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
            'title' => 'Estados e importancia',
            'description' => 'Cada cita guarda su estado (confirmada, en sala, atendida o ausente) y se puede marcar como importante.',
        ],
        [
            'icon' => '<svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
            'title' => 'Pacientes compartidos',
            'description' => 'La misma ficha de paciente y los mismos doctores del módulo Salud, sin duplicar registros entre áreas.',
        ],
        [
            'icon' => '<svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
            'title' => 'Atenciones odontológicas',
            'description' => 'Motivo de consulta, enfermedad actual, examen físico y tratamiento de cada sesión, con el historial del paciente a la mano.',
        ],
        [
            'icon' => '<svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M12 3c2.755 0 5 1.79 5 4 0 1.06-.578 2.03-1.5 2.75V13c0 2.21-1.79 4-4 4v3m5.5-12.5c.52-.44.84-1.02.84-1.66C17.84 4.28 15.23 3 12 3S6.16 4.28 6.16 5.84c0 .64.32 1.22.84 1.66"/></svg>',
            'title' => 'Odontograma',
            'description' => 'Registro de condiciones y tratamientos en cada diente, guardado en la historia clínica del paciente.',
        ],
        [
            'icon' => '<svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2zm9-10l1.5 1.5L15 14"/></svg>',
            'title' => 'Próxima cita desde la atención',
            'description' => 'Al cerrar la sesión se agenda el control con fecha, hora y doctor; la cita queda ligada a esa atención.',
        ],
        [
            'icon' => '<svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
            'title' => 'Cobros y comprobantes',
            'description' => 'Procedimientos dentales con su precio, cobro ligado a la atención y preparación del comprobante electrónico.',
        ],
    ];

    $promociones = [
        [
            'tag' => 'Gratis',
            'title' => 'Demo guiada sin costo',
            'description' => 'Treinta minutos con los casos de tu consultorio: agenda, atención, odontograma y próxima cita.',
            'items' => [
                'Recorrido con un especialista de ARACODE',
                'Preguntas sobre tu forma de trabajar',
                'Sin instalación ni tarjeta',
            ],
            'destacada' => false,
            'cta' => 'Agendar la demo',
            'url' => 'https://wa.me/51917295856?text=' . rawurlencode('Hola, quiero agendar la demo gratuita de Dental.'),
        ],
        [
            'tag' => 'Promoción del mes',
            'title' => '12 meses al precio de 10',
            'description' => 'Contrata el plan anual y recibe dos meses de regalo. Incluye la carga inicial de pacientes y doctores.',
            'items' => [
                'Agenda, atenciones y odontograma',
                'Usuarios para todo el consultorio',
                'Soporte y actualizaciones del sistema',
            ],
            'destacada' => true,
            'cta' => 'Quiero esta promoción',
            'url' => $whatsappPromo,
        ],
        [
            'tag' => 'Incluida',
            'title' => 'Capacitación para tu equipo',
            'description' => 'Sesión para doctores y recepción, con acompañamiento durante la primera semana de uso.',
            'items' => [
                'Agenda y registro de pacientes',
                'Atención, odontograma y próxima cita',
                'Cobros y comprobantes',
            ],
            'destacada' => false,
            'cta' => 'Consultar condiciones',
            'url' => $whatsapp,
        ],
    ];

    $preguntas = [
        [
            'pregunta' => '¿Puedo ver la agenda de cada doctor por separado?',
            'respuesta' => 'Sí. El calendario muestra la agenda general del consultorio y la de cada doctor. Antes de agendar se consultan las horas ocupadas de ese doctor en la fecha elegida, para no cruzar dos pacientes.',
        ],
        [
            'pregunta' => '¿La próxima cita se agenda desde la atención?',
            'respuesta' => 'Sí. Al registrar la atención se indica la fecha, la hora y el doctor del control; el sistema crea la cita y la deja ligada a esa sesión, así el seguimiento no queda en un papel aparte.',
        ],
        [
            'pregunta' => '¿El odontograma es por pieza dental?',
            'respuesta' => 'Sí. Cada atención guarda el odontograma por pieza dental y una nota del estado de la boca, y queda en la historia clínica del paciente.',
        ],
        [
            'pregunta' => '¿Necesito instalar algo en la computadora?',
            'respuesta' => 'No. El sistema se usa desde el navegador, así que funciona en computadora, tablet y celular. Solo necesitas conexión a internet y tu usuario.',
        ],
    ];
@endphp

@push('head')
    <link rel="stylesheet" href="{{ asset('themes/salud/css/salud.css') }}">
@endpush

@push('json-ld')
{{-- `@@` escapa la directiva @context de Blade, igual que en el layout. --}}
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "SoftwareApplication",
    "name": "Dental — Gestión para clínicas dentales",
    "applicationCategory": "BusinessApplication",
    "operatingSystem": "Web",
    "inLanguage": "es-PE",
    "description": "Módulo dental de ARACODE para clínicas y consultorios odontológicos: calendario de citas por doctor, horas ocupadas, atenciones con historia clínica, odontograma por pieza dental, próxima cita desde la atención y cobros con comprobante electrónico.",
    "url": "{{ url('/') }}",
    "mainEntityOfPage": "{{ url('/') }}",
    "author": {
        "@@type": "Organization",
        "name": "ARACODE Smart Solutions",
        "url": "{{ url('/') }}"
    },
    "publisher": {
        "@@type": "Organization",
        "name": "ARACODE Smart Solutions",
        "url": "{{ url('/') }}"
    }
}
</script>
@endpush

@section('content')
    @include('aracode.components.v2.navbar')

    <div class="sl-page">

        {{-- Portada --}}
        <section class="sl-hero">
            <div class="sl-wrap">
                <div class="sl-hero__grid">
                    <div>
                        <span class="sl-eyebrow">
                            <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3c2.755 0 5 1.79 5 4 0 1.06-.578 2.03-1.5 2.75V13c0 2.21-1.79 4-4 4v3"/>
                            </svg>
                            Módulo Dental · ARACODE Smart Solutions
                        </span>

                        <h1>
                            Clínicas dentales con agenda, atenciones y odontograma <em>en un solo sistema</em>
                        </h1>

                        <p class="sl-hero__lead">
                            DENTAL organiza el día del consultorio odontológico: calendario por doctor,
                            horas ocupadas, atenciones con historia clínica, odontograma por pieza dental
                            y la próxima cita programada desde la propia atención.
                        </p>

                        <ul class="sl-chips">
                            <li>Agenda por doctor</li>
                            <li>Horas ocupadas</li>
                            <li>Odontograma</li>
                            <li>Historia clínica</li>
                            <li>Próxima cita desde la atención</li>
                            <li>Cobros y comprobantes</li>
                        </ul>

                        <div class="sl-actions">
                            <a href="{{ route('contacto') }}" data-asesoria class="sl-btn sl-btn--primary">
                                Solicitar una demo
                                <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                </svg>
                            </a>
                            <a href="#caracteristicas" class="sl-btn sl-btn--ghost">
                                Ver características
                            </a>
                        </div>

                        <p class="sl-hero__note">
                            <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Implementación guiada y capacitación para tu equipo.
                        </p>
                    </div>

                    <div class="sl-hero__media">
                        <span class="sl-sign">
                            <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3l7 3v6c0 4.4-3 7.6-7 9-4-1.4-7-4.6-7-9V6l7-3z"/>
                            </svg>
                            Atención firmada · Dra. Paredes
                        </span>

                        <img src="{{ asset('themes/dental/images/hero.jpg') }}"
                             alt="Odontóloga atendiendo a una paciente en el consultorio dental"
                             loading="eager">

                        <div class="sl-float">
                            <div class="sl-float__head">
                                <span>Agenda de hoy</span>
                                <span>Dra. Paredes</span>
                            </div>
                            <div class="sl-appointment">
                                <time>09:00</time>
                                <p><strong>María R.</strong> Endodoncia · Sesión 2</p>
                                <span class="sl-pill sl-pill--wait">En sala</span>
                            </div>
                            <div class="sl-appointment">
                                <time>10:30</time>
                                <p><strong>Jorge P.</strong> Control · Próxima cita</p>
                                <span class="sl-pill">Confirmada</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Cifras del módulo --}}
        <div class="sl-stats">
            <div class="sl-wrap">
                <div class="sl-stats__box">
                    @foreach($cifras as $cifra)
                        <div class="sl-stat">
                            <strong>{{ $cifra['valor'] }}</strong>
                            <span>{{ $cifra['texto'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Agenda y atención --}}
        <section class="sl-section sl-section--white" id="agenda">
            <div class="sl-wrap">
                <div class="sl-split">
                    <div>
                        <span class="sl-kicker">Agenda odontológica</span>
                        <h2>El calendario del consultorio, sin citas cruzadas</h2>
                        <p class="sl-lead">
                            El calendario general y el de cada doctor comparten los mismos datos. Antes
                            de agendar se consultan las horas ocupadas del doctor en esa fecha y la
                            duración del tratamiento define el espacio que ocupa la cita.
                        </p>

                        <ul class="sl-checks">
                            @foreach($agenda as $punto)
                                <li>
                                    <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span>{{ $punto }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="sl-split__media">
                        <img src="{{ asset('themes/dental/images/consultorio.jpg') }}"
                             alt="Consultorio dental con sillón y equipo"
                             loading="lazy">

                        <div class="sl-tip">
                            <span class="sl-tip__icon">
                                <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </span>
                            <span>
                                <strong>Horas ocupadas del doctor</strong>
                                09:00 a 10:00 · la agenda no ofrece ese espacio al agendar.
                            </span>
                        </div>
                    </div>
                </div>

                <div class="sl-split">
                    <div class="sl-split__media">
                        <img src="{{ asset('themes/dental/images/diagnostico.jpg') }}"
                             alt="Odontólogo revisando radiografías dentales"
                             loading="lazy">
                    </div>

                    <div>
                        <span class="sl-kicker">Atención e historia clínica</span>
                        <h2>La sesión de hoy y el control del mes que viene</h2>
                        <p class="sl-lead">
                            Cada atención guarda el motivo, la enfermedad actual, el examen físico y el
                            tratamiento de la sesión. Al cerrarla se indica la próxima cita y el sistema
                            la crea en la agenda, ligada a esa atención.
                        </p>

                        <ul class="sl-checks">
                            @foreach($atencion as $punto)
                                <li>
                                    <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span>{{ $punto }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        {{-- Características --}}
        <section class="sl-section sl-section--mint" id="caracteristicas">
            <div class="sl-wrap">
                <div class="sl-head">
                    <span class="sl-kicker">Características del sistema</span>
                    <h2>Todo lo que Dental hace hoy</h2>
                    <p class="sl-lead">
                        Funciones que ya están en la plataforma ARACODE para el trabajo diario de
                        una clínica odontológica, no una lista de deseos.
                    </p>
                </div>

                <div class="sl-grid">
                    @foreach($caracteristicas as $caracteristica)
                        <article class="sl-card">
                            <span class="sl-card__icon">{!! $caracteristica['icon'] !!}</span>
                            <h3>{{ $caracteristica['title'] }}</h3>
                            <p>{{ $caracteristica['description'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Promociones --}}
        <section class="sl-section sl-section--white" id="promociones">
            <div class="sl-wrap">
                <div class="sl-head">
                    <span class="sl-kicker">Promociones</span>
                    <h2>Promociones para empezar este mes</h2>
                    <p class="sl-lead">
                        Para consultorios odontológicos que quieren ordenar la agenda y dejar de
                        llevar las citas en un cuaderno.
                    </p>
                </div>

                <div class="sl-promos">
                    @foreach($promociones as $promocion)
                        <article class="sl-promo {{ $promocion['destacada'] ? 'sl-promo--featured' : '' }}">
                            <span class="sl-promo__tag">{{ $promocion['tag'] }}</span>
                            <h3>{{ $promocion['title'] }}</h3>
                            <p>{{ $promocion['description'] }}</p>

                            <ul>
                                @foreach($promocion['items'] as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>

                            <div class="sl-promo__action">
                                <a href="{{ $promocion['url'] }}"
                                   target="_blank"
                                   rel="noopener"
                                   class="sl-btn {{ $promocion['destacada'] ? '' : 'sl-btn--solid' }}">
                                    {{ $promocion['cta'] }}
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>

                <p class="sl-promo-note">
                    Promociones válidas para nuevas contrataciones. Consulta condiciones y vigencia
                    por WhatsApp al 917 295 856.
                </p>
            </div>
        </section>

        {{-- Preguntas frecuentes --}}
        <section class="sl-section sl-section--mint">
            <div class="sl-wrap">
                <div class="sl-head">
                    <span class="sl-kicker">Preguntas frecuentes</span>
                    <h2>Lo que suelen preguntarnos</h2>
                </div>

                <div class="sl-faq">
                    @foreach($preguntas as $pregunta)
                        <details>
                            <summary>{{ $pregunta['pregunta'] }}</summary>
                            <p>{{ $pregunta['respuesta'] }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Cierre --}}
        <section class="sl-cta">
            <div class="sl-cta__bg" style="background-image: url('{{ asset('themes/dental/images/consultorio.jpg') }}')"></div>
            <div class="sl-wrap">
                <div class="sl-cta__inner">
                    <h2>Ordena la agenda y las atenciones de tu clínica dental</h2>
                    <p>
                        Cuéntanos cuántos doctores atienden y cómo trabajan hoy; te mostramos DENTAL
                        funcionando con tu propio caso.
                    </p>
                    <div class="sl-actions">
                        <a href="{{ route('contacto') }}" data-asesoria class="sl-btn sl-btn--primary">
                            Solicitar una demo
                            <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </a>
                        <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="sl-btn sl-btn--ghost">
                            Escribir por WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </section>

    </div>

    @include('aracode.components.v2.footer')
@endsection
