@extends('aracode.layouts.webpage')

@section('meta_title', 'SALUD | Historia clínica y agenda de pacientes — ARACODE')
@section('meta_description', 'SALUD es el módulo de ARACODE para consultorios, clínicas y centros odontológicos: agenda de pacientes por doctor, historia clínica con CIE-10, recordatorios por SMS, Google Calendar y cobros con comprobante electrónico.')
@section('og_title', 'SALUD — Historia clínica y agenda de pacientes | ARACODE')
@section('og_description', 'Agenda por doctor, historia clínica firmada con PIN, diagnóstico CIE-10, odontograma, recordatorios por SMS y cobros con comprobante electrónico en un solo sistema.')

@php
    /*
        Portada del módulo Salud.

        Se publica en la raíz del dominio cuando en Parámetros → «Pagina web
        principal» se elige la opción SALUD (value 6 de config/pagina_principal.php).

        Todo lo que la página promete es lo que el módulo Salud hace hoy: agenda
        y disponibilidad (HealAgendaController), avisos por SMS
        (AppointmentNoticeService), sincronización con Google Calendar
        (GoogleCalendarSyncService), atención con CIE-10, signos vitales,
        odontograma y firma con PIN (HealAttentionController) y cobros que se
        preparan para su comprobante electrónico (HealProcedureChargeController).
        Las fotos son de Unsplash y están guardadas en
        public/themes/salud/images para no depender de un servicio externo.

        El texto de las promociones es comercial y se edita aquí: si cambian las
        condiciones vigentes, se cambian en este arreglo y nada más.

        Fotos gratuitas (licencia Unsplash / Pexels), copiadas al proyecto para
        no depender de internet:
          - hero.jpg              images.unsplash.com/photo-1666214280557-f1b5022eb634
          - agenda.jpg            images.unsplash.com/photo-1576091160399-112ba8d25d1d
          - historia-clinica.jpg  images.pexels.com/photos/40568/
          - consultorio.jpg       images.unsplash.com/photo-1519494026892-80bbd2d6fd0d
    */

    // Enlaces de contacto que comparten los botones de la página.
    $whatsapp = 'https://wa.me/51917295856?text=' . rawurlencode('Hola, quiero información y las promociones del módulo SALUD.');
    $whatsappPromo = 'https://wa.me/51917295856?text=' . rawurlencode('Hola, quiero aprovechar la promoción de SALUD (12 meses al precio de 10).');

    // Cifras del módulo: cada una se puede sostener con una función real.
    $cifras = [
        ['valor' => '25', 'texto' => 'especialidades médicas y odontológicas'],
        ['valor' => '3', 'texto' => 'avisos por cita, enviados por SMS'],
        ['valor' => 'CIE-10', 'texto' => 'catálogo de diagnóstico en la atención'],
        ['valor' => '100%', 'texto' => 'en el navegador, sin instalar nada'],
    ];

    // Agenda de pacientes.
    $agenda = [
        'Agenda del día por doctor y calendario general de citas.',
        'Espacios libres calculados según la duración del servicio y los cruces ya ocupados.',
        'Estado de llegada del paciente (a tiempo o tarde) y resumen de puntualidad del día.',
        'La atención se inicia desde la cita de la agenda, con el paciente y el doctor ya cargados.',
        'Sincronización en dos vías con Google Calendar: crear, mover o cancelar una cita actualiza el evento.',
    ];

    // Historia clínica.
    $historia = [
        'Ficha del paciente con código propio, documento de identidad y edad calculada.',
        'Antecedentes patológicos, alergias y últimos signos vitales a la vista al atender.',
        'Diagnóstico con el catálogo CIE-10, con búsqueda por código o descripción.',
        'Exámenes con tipo, resultado y archivo adjunto.',
        'Odontograma por pieza dental y endodoncia por sesión.',
        'Firma de la atención con el PIN del doctor, con aviso de las que quedan pendientes.',
        'Historia clínica y receta en PDF con el logo y los datos del consultorio.',
    ];

    // Características del módulo, con su icono.
    $caracteristicas = [
        [
            'icon' => '<svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
            'title' => 'Agenda y citas',
            'description' => 'Calendario por doctor, espacios libres según la duración del servicio, control de llegada y reprogramación que mueve los avisos.',
        ],
        [
            'icon' => '<svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
            'title' => 'Historia clínica',
            'description' => 'Historia numerada por paciente con antecedentes, alergias, signos vitales y un panel con el resumen de su atención.',
        ],
        [
            'icon' => '<svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5.414 5.414a1.5 1.5 0 01-1.06 2.56H4.06a1.5 1.5 0 01-1.06-2.56l5.414-5.414A2 2 0 009 10.172V5L8 4z"/></svg>',
            'title' => 'Atención de consulta',
            'description' => 'Motivo, examen clínico, plan de tratamiento, observaciones y diagnóstico CIE-10 en una sola pantalla.',
        ],
        [
            'icon' => '<svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M12 3c2.755 0 5 1.79 5 4 0 1.06-.578 2.03-1.5 2.75V13c0 2.21-1.79 4-4 4v3m5.5-12.5c.52-.44.84-1.02.84-1.66C17.84 4.28 15.23 3 12 3S6.16 4.28 6.16 5.84c0 .64.32 1.22.84 1.66"/></svg>',
            'title' => 'Odontología',
            'description' => 'Odontograma por pieza dental, endodoncia por sesión y procedimientos dentales con su propio precio.',
        ],
        [
            'icon' => '<svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M8 10h.01M12 10h.01M16 10h.01M9 16h6m-9 5h12a2 2 0 002-2V5a2 2 0 00-2-2H6a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>',
            'title' => 'Recordatorios por SMS',
            'description' => 'Tres avisos automáticos por cita, mensajes con variables editables, historial de entregas y reintento del que falló.',
        ],
        [
            'icon' => '<svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>',
            'title' => 'Google Calendar',
            'description' => 'Lo que se crea, mueve o cancela en el sistema se actualiza en Google, y lo agendado en Google vuelve a la agenda del consultorio.',
        ],
        [
            'icon' => '<svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
            'title' => 'Cobros y facturación',
            'description' => 'Procedimientos con precio, cobro ligado a la atención y preparación del comprobante electrónico en el módulo de ventas.',
        ],
        [
            'icon' => '<svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
            'title' => 'Doctores y permisos',
            'description' => 'Colegiatura, especialidad y usuario propio: cada doctor ve su agenda y sus pacientes; el administrador ve todo.',
        ],
    ];

    // Promociones vigentes. El texto es comercial: se edita aquí.
    $promociones = [
        [
            'tag' => 'Gratis',
            'title' => 'Demo guiada sin costo',
            'description' => 'Treinta minutos con tus propios casos: agenda, atención, historia clínica y cobros, sin compromiso.',
            'items' => [
                'Recorrido con un especialista de ARACODE',
                'Preguntas sobre tu forma de trabajar',
                'Sin instalación ni tarjeta',
            ],
            'destacada' => false,
            'cta' => 'Agendar la demo',
            'url' => 'https://wa.me/51917295856?text=' . rawurlencode('Hola, quiero agendar la demo gratuita de SALUD.'),
        ],
        [
            'tag' => 'Promoción del mes',
            'title' => '12 meses al precio de 10',
            'description' => 'Contrata el plan anual y recibe dos meses de regalo. Incluye la migración de tus pacientes, doctores y citas.',
            'items' => [
                'Agenda, historia clínica y cobros',
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
                'Atención, firma con PIN y documentos',
                'Cobros y comprobantes',
            ],
            'destacada' => false,
            'cta' => 'Consultar condiciones',
            'url' => $whatsapp,
        ],
    ];

    $preguntas = [
        [
            'pregunta' => '¿Puedo pasar mi agenda y mis pacientes actuales?',
            'respuesta' => 'Sí. En la puesta en marcha revisamos tu información actual y te acompañamos en la carga de pacientes, doctores y citas, para que empieces con la agenda del día siguiente. La migración está incluida en la promoción del plan anual.',
        ],
        [
            'pregunta' => '¿Sirve para un consultorio odontológico?',
            'respuesta' => 'Sí. El módulo incluye odontograma por pieza dental, endodoncia por sesión y procedimientos dentales con su propio precio, además de la consulta médica y las especialidades.',
        ],
        [
            'pregunta' => '¿Necesito instalar algo en la computadora?',
            'respuesta' => 'No. El sistema se usa desde el navegador, así que funciona en computadora, tablet y celular. Lo único que necesitas es conexión a internet y tu usuario.',
        ],
        [
            'pregunta' => '¿Cómo se protege la historia clínica?',
            'respuesta' => 'Cada doctor entra con su propio usuario y firma cada atención con su PIN. El sistema guarda el autor y la hora de la firma, y el administrador cuenta con el registro de auditoría para saber quién cambió qué, cuándo y desde qué dirección.',
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
    "name": "SALUD — Gestión de Clínica",
    "applicationCategory": "BusinessApplication",
    "operatingSystem": "Web",
    "inLanguage": "es-PE",
    "description": "Módulo de salud de ARACODE para consultorios y clínicas: agenda de pacientes por doctor, historia clínica con CIE-10, odontograma, firma con PIN, recordatorios por SMS, sincronización con Google Calendar y cobros con comprobante electrónico.",
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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v8m-4-4h8M4 6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/>
                            </svg>
                            Módulo Salud · ARACODE Smart Solutions
                        </span>

                        <h1>
                            Historia clínica y agenda de pacientes <em>en un solo sistema</em>
                        </h1>

                        <p class="sl-hero__lead">
                            SALUD reúne la agenda de cada doctor, la historia clínica del paciente, los
                            recordatorios por SMS y los cobros del consultorio. Menos cuadernos, menos
                            llamadas y toda la información a la mano al momento de atender.
                        </p>

                        <ul class="sl-chips">
                            <li>Agenda por doctor</li>
                            <li>Historia clínica numerada</li>
                            <li>Diagnóstico CIE-10</li>
                            <li>Recordatorios por SMS</li>
                            <li>Google Calendar</li>
                            <li>Odontograma</li>
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
                            Historia firmada con PIN · 09:52 a. m.
                        </span>

                        <img src="{{ asset('themes/salud/images/hero.jpg') }}"
                             alt="Doctora revisando la historia clínica de un paciente en el consultorio"
                             loading="eager">

                        <div class="sl-float">
                            <div class="sl-float__head">
                                <span>Agenda de hoy</span>
                                <span>Dra. Ramos</span>
                            </div>
                            <div class="sl-appointment">
                                <time>09:30</time>
                                <p><strong>María R.</strong> Control · Medicina general</p>
                                <span class="sl-pill">Confirmada</span>
                            </div>
                            <div class="sl-appointment">
                                <time>10:15</time>
                                <p><strong>Jorge P.</strong> Odontología · Endodoncia</p>
                                <span class="sl-pill sl-pill--wait">En sala</span>
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

        {{-- Agenda e historia clínica --}}
        <section class="sl-section sl-section--white" id="agenda">
            <div class="sl-wrap">
                <div class="sl-split">
                    <div>
                        <span class="sl-kicker">Agenda de pacientes</span>
                        <h2>Una agenda que se calcula sola y avisa a tiempo</h2>
                        <p class="sl-lead">
                            El horario de atención y la duración de cada servicio definen los espacios
                            libres del día. Al agendar, el sistema cruza las citas y las atenciones ya
                            registradas y no deja superponer a un doctor.
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
                        <img src="{{ asset('themes/salud/images/agenda.jpg') }}"
                             alt="Doctor revisando la agenda de pacientes desde el celular"
                             loading="lazy">

                        <div class="sl-tip">
                            <span class="sl-tip__icon">
                                <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M8 10h.01M12 10h.01M16 10h.01M9 16h6m-9 5h12a2 2 0 002-2V5a2 2 0 00-2-2H6a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                            </span>
                            <span>
                                <strong>Recordatorio enviado por SMS</strong>
                                María R. · mañana 08:00 · con el nombre del doctor y el consultorio.
                            </span>
                        </div>
                    </div>
                </div>

                <div class="sl-split">
                    <div class="sl-split__media">
                        <img src="{{ asset('themes/salud/images/historia-clinica.jpg') }}"
                             alt="Estetoscopio sobre la historia clínica del paciente"
                             loading="lazy">
                    </div>

                    <div>
                        <span class="sl-kicker">Historia clínica</span>
                        <h2>La historia del paciente, completa y trazable</h2>
                        <p class="sl-lead">
                            Cada atención guarda motivo, examen clínico, diagnóstico CIE-10, signos
                            vitales, exámenes y tratamientos. El doctor cierra la consulta con su PIN y
                            el sistema registra quién cambió qué, cuándo y desde dónde.
                        </p>

                        <ul class="sl-checks">
                            @foreach($historia as $punto)
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
                    <h2>Todo lo que el módulo Salud hace hoy</h2>
                    <p class="sl-lead">
                        Funciones que ya están en la plataforma ARACODE, no una lista de deseos:
                        agenda, historia clínica, atención, odontología, avisos y cobros.
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
                        Para consultorios y clínicas que quieren dejar el cuaderno y ordenar la agenda
                        sin frenar la atención de sus pacientes.
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
            <div class="sl-cta__bg" style="background-image: url('{{ asset('themes/salud/images/consultorio.jpg') }}')"></div>
            <div class="sl-wrap">
                <div class="sl-cta__inner">
                    <h2>Ordena la agenda y la historia clínica de tu consultorio</h2>
                    <p>
                        Cuéntanos cuántos doctores atienden y cómo trabajan hoy; te mostramos SALUD
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
