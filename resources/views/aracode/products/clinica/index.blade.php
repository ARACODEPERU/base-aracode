@extends('aracode.layouts.webpage')

@section('meta_title', 'Gestión de Clínica | Software para consultorios y clínicas desarrollado por ARACODE')
@section('meta_description', 'Gestión de Clínica es el sistema de ARACODE Smart Solutions para consultorios y clínicas: agenda por doctor, historia clínica, atenciones firmadas, recordatorios por SMS y cobros con comprobante electrónico.')

@php
    /*
        Ficha de Gestión de Clínica en el sitio corporativo.

        A diferencia de KAPTA, KIRAFACT y Pichanguero, este producto todavía NO
        tiene sitio propio: el sistema vive dentro de la plataforma (módulo
        Salud) y su página pública es esta. Por eso aquí no hay segundo enlace
        ni componente de «sitio oficial» (x-v2.product-official): todo lo que la
        página promete es lo que el módulo hace hoy, y el cierre manda a
        contacto.

        Las funciones y los beneficios están tomados del módulo Salud, no de una
        lista de deseos: agenda y disponibilidad de HealAgendaController, avisos
        por SMS de AppointmentNoticeService, sincronización con Google Calendar
        de GoogleCalendarSyncService, atención con CIE-10, signos vitales,
        odontograma y firma con PIN de HealAttentionController, y cobros que se
        preparan para su comprobante electrónico en HealProcedureChargeController.

        El día que Gestión de Clínica tenga su propio dominio, se replica el
        procedimiento de docs/SEO_ECOSISTEMA_PRODUCTOS.md: mapa en
        config/productos.php, ficha enlazando a la web oficial y canónica propia.
    */

    // Beneficios: el «por qué» que ve quien decide, en frases cortas.
    $beneficios = [
        [
            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
            'title' => 'Una agenda que se calcula sola',
            'description' => 'El horario de atención y la duración de cada servicio definen los espacios libres del día. Al agendar, el sistema cruza las citas y las atenciones ya registradas y no deja superponer a un doctor.',
        ],
        [
            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>',
            'title' => 'Menos pacientes que no llegan',
            'description' => 'Recordatorios automáticos por SMS antes de la cita: uno media hora o una hora antes y otro el día anterior, con el nombre del paciente, la fecha, la hora, el doctor y el consultorio.',
        ],
        [
            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
            'title' => 'Historia clínica trazable',
            'description' => 'Cada atención guarda motivo, examen clínico, diagnóstico CIE-10, signos vitales, exámenes y tratamientos. Con el registro de auditoría, se sabe quién cambió qué, cuándo y desde qué dirección.',
        ],
        [
            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>',
            'title' => 'Atenciones firmadas y respaldo en PDF',
            'description' => 'El doctor cierra la consulta con su PIN. El sistema guarda la firma, el autor y la hora, avisa de las atenciones que siguen sin firmar y emite la historia clínica y la receta en PDF con el logo y los datos del consultorio.',
        ],
        [
            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
            'title' => 'Cobros que terminan en comprobante electrónico',
            'description' => 'Los procedimientos se cobran con su precio y quedan ligados a la atención; el sistema arma el borrador en el módulo de ventas para emitir la boleta o factura electrónica sin volver a escribir los datos del paciente.',
        ],
        [
            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2zm9-10l1.5 1.5L15 14"/></svg>',
            'title' => 'La agenda también vive en Google Calendar',
            'description' => 'Sincronización en las dos vías: crear, mover o cancelar una cita en el sistema actualiza el evento en Google, y lo que se agenda en Google vuelve a la agenda del consultorio y de cada doctor.',
        ],
    ];

    // Características: el «qué hace» por bloque, para quien ya quiere el detalle.
    $caracteristicas = [
        [
            'title' => 'Agenda y citas',
            'items' => [
                'Agenda del día por doctor y calendario general de citas.',
                'Espacios libres calculados según la duración del servicio y los cruces ya ocupados.',
                'Estado de llegada de cada paciente (a tiempo o tarde) y resumen de puntualidad del día.',
                'Las citas que nadie atiende se marcan como ausencia para no contarlas dos veces.',
                'Reprogramar una cita la mueve y regenera sus recordatorios en el horario nuevo.',
                'La próxima cita se registra desde la propia atención, sin salir del formulario.',
            ],
        ],
        [
            'title' => 'Pacientes e historia clínica',
            'items' => [
                'Ficha del paciente con código propio, documento de identidad y edad calculada.',
                'Historia clínica numerada por paciente y panel con su resumen.',
                'Antecedentes patológicos, alergias y últimos signos vitales a la vista al atender.',
                'Listado de historias clínicas con detalle por paciente.',
            ],
        ],
        [
            'title' => 'Atención de consulta',
            'items' => [
                'Más de veinte especialidades médicas y odontológicas por doctor.',
                'Motivo, examen clínico, plan de tratamiento y observaciones.',
                'Diagnóstico con el catálogo CIE-10, con búsqueda por código o descripción.',
                'Signos vitales con índice de masa corporal, frecuencia cardiaca y respiratoria.',
                'Exámenes con tipo, resultado y archivo adjunto.',
                'Tratamientos farmacológicos y no farmacológicos con sus indicaciones.',
            ],
        ],
        [
            'title' => 'Odontología',
            'items' => [
                'Odontograma por pieza dental y nota del estado de la boca.',
                'Endodoncia por sesión: conductos trabajados y número de la sesión siguiente.',
                'Procedimientos dentales con su propio precio en el catálogo.',
            ],
        ],
        [
            'title' => 'Firma y documentos',
            'items' => [
                'Firma de la atención con el PIN del doctor, que se cambia en el primer ingreso.',
                'Aviso permanente de las atenciones que quedaron pendientes de firma.',
                'Reposición del acceso del doctor por correo si olvida su PIN.',
                'Historia clínica y receta en PDF con el logo y los datos del consultorio.',
            ],
        ],
        [
            'title' => 'Recordatorios y Google Calendar',
            'items' => [
                'Tres bloques de aviso configurables: dos antes de la cita y uno el día anterior.',
                'Mensajes editables con variables como {paciente}, {hora_cita} o {nombre_dr}.',
                'Envío en cola, con historial de entregas por paciente y reintento del que falló.',
                'Prueba de envío desde la pantalla de avisos, sin esperar a la próxima cita.',
                'Conexión de la cuenta de Google del consultorio y de la agenda de cada doctor.',
                'Bandeja de eventos por revisar cuando un cambio de Google no se puede casar solo.',
            ],
        ],
        [
            'title' => 'Cobros y facturación',
            'items' => [
                'Catálogo de procedimientos con precio: consulta, endodoncia, extracción, restauración, profilaxis y los que agregue el consultorio.',
                'Cobro por paciente y por atención, con precio ajustable, cantidad y estado (pendiente o pagado).',
                'Regla de recobro de la consulta: se cobra la primera del día y no se duplica en los controles.',
                'El cobro conserva el nombre y el detalle del momento en que se hizo, aunque después cambie el catálogo.',
                'Preparación de los cobros pendientes para emitir su comprobante electrónico en el módulo de ventas.',
            ],
        ],
        [
            'title' => 'Doctores, permisos y configuración',
            'items' => [
                'Doctores con colegiatura, profesión, especialidad y usuario propio.',
                'Cada doctor ve su agenda, sus pacientes y sus atenciones; el administrador ve todo.',
                'Permisos por rol y registro de actividades del módulo para el administrador.',
                'Datos del consultorio (nombre, logo, representante, correo, teléfono, dirección y horario de atención) que salen en los documentos.',
                'Datos de prueba para capacitar al equipo en un entorno con información de ejemplo.',
            ],
        ],
    ];

    // A quién le sirve. El módulo cubre consulta médica y odontológica.
    $paraQuien = [
        [
            'title' => 'Consultorios médicos',
            'description' => 'Medicina general y especialidades que quieren dejar el cuaderno: agenda por doctor, historia clínica y receta en PDF.',
        ],
        [
            'title' => 'Clínicas y centros de salud',
            'description' => 'Varios doctores, varias agendas y varios cobros al día, con permisos por rol y registro de lo que hace cada usuario.',
        ],
        [
            'title' => 'Consultorios odontológicos',
            'description' => 'Odontograma por pieza, endodoncia por sesiones y procedimientos dentales con su precio, listos para facturar.',
        ],
    ];
@endphp

@push('json-ld')
{{-- `@@` escapa la directiva @context de Blade, igual que en el layout. --}}
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "SoftwareApplication",
    "name": "Gestión de Clínica",
    "applicationCategory": "BusinessApplication",
    "operatingSystem": "Web",
    "inLanguage": "es-PE",
    "description": "Sistema de gestión para consultorios y clínicas: agenda por doctor, historia clínica, atenciones con diagnóstico CIE-10, odontograma, firma con PIN, recordatorios por SMS, sincronización con Google Calendar y cobros con comprobante electrónico.",
    "url": "{{ route('solucion_clinica') }}",
    "mainEntityOfPage": "{{ route('solucion_clinica') }}",
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

    {{-- Hero --}}
    <section class="pt-32 pb-20 bg-ara-navy relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-ara-blue/10 rounded-full filter blur-3xl pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <span class="ara-badge ara-badge-blue mb-6 inline-block reveal">Producto de ARACODE</span>
                    <h1 class="text-4xl sm:text-5xl font-bold text-white mb-6 reveal reveal-delay-1">
                        Gestión de <span class="text-gradient">Clínica</span>
                    </h1>
                    <p class="text-lg text-white/70 mb-8 reveal reveal-delay-2">
                        Gestión de Clínica es el sistema que ARACODE Smart Solutions desarrolla para consultorios y clínicas: agenda por doctor, historia clínica, atenciones firmadas, recordatorios por SMS y cobros que terminan en comprobante electrónico.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 reveal reveal-delay-3">
                        <a href="{{ route('contacto') }}" data-asesoria class="ara-btn ara-btn-primary ara-btn-lg">
                            Solicitar Demo
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </a>
                        <a href="{{ route('soluciones') }}" class="ara-btn ara-btn-secondary ara-btn-lg">
                            Ver Todas las Soluciones
                        </a>
                    </div>
                </div>
                <div class="hidden lg:block reveal reveal-delay-4">
                    <img src="{{ asset('themes/webpage/images/misc/s6.jpg') }}" alt="Gestión de Clínica" class="rounded-2xl shadow-2xl w-full" loading="lazy">
                </div>
            </div>
        </div>
    </section>

    {{-- Beneficios --}}
    <section class="py-20 lg:py-28 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-v2.section-heading
                badge="Beneficios"
                title="Lo que cambia en el día a día"
                subtitle="Menos cuadernos, menos llamadas de recordatorio y menos datos escritos dos veces: el trabajo del consultorio queda en un solo sistema."
                :light="false"
            />

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($beneficios as $index => $beneficio)
                    <div class="ara-card reveal reveal-delay-{{ $index + 1 }}">
                        <div class="ara-icon-box mb-4">
                            {!! $beneficio['icon'] !!}
                        </div>
                        <h3 class="text-xl font-bold text-ara-slate-700 mb-3">{{ $beneficio['title'] }}</h3>
                        <p class="text-ara-slate-400 leading-relaxed">{{ $beneficio['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Características --}}
    <section class="py-20 lg:py-28 bg-ara-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-v2.section-heading
                badge="Características"
                title="Todo lo que el sistema ya hace"
                subtitle="El detalle por módulo, tal como funciona hoy en la plataforma ARACODE."
                :light="false"
            />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                @foreach($caracteristicas as $index => $bloque)
                    <div class="ara-card reveal reveal-delay-{{ ($index % 3) + 1 }}">
                        <h3 class="text-xl font-bold text-ara-slate-700 mb-4">{{ $bloque['title'] }}</h3>

                        <ul class="space-y-3">
                            @foreach($bloque['items'] as $item)
                                <li class="flex items-start gap-2 text-ara-slate-500">
                                    <svg class="w-5 h-5 text-ara-blue flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="leading-relaxed">{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Para quién --}}
    <section class="py-20 lg:py-28 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-v2.section-heading
                badge="Hecho para"
                title="¿Para quién es Gestión de Clínica?"
                subtitle="Sirve tanto para la consulta médica como para la odontológica: el sistema trae las dos."
                :light="false"
            />

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @foreach($paraQuien as $index => $publico)
                    <div class="ara-card reveal reveal-delay-{{ $index + 1 }}">
                        <h3 class="text-xl font-bold text-ara-slate-700 mb-3">{{ $publico['title'] }}</h3>
                        <p class="text-ara-slate-400 leading-relaxed">{{ $publico['description'] }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Cierre del bloque: lo que comparten los tres casos. --}}
            <div class="mt-12 ara-card bg-ara-navy text-center reveal">
                <h3 class="text-2xl font-bold text-white mb-3">Un solo sistema para todo el consultorio</h3>
                <p class="text-white/70 mb-6 max-w-3xl mx-auto">
                    La agenda, la historia clínica, los cobros y los recordatorios comparten los mismos datos: lo que se registra al agendar la cita ya está disponible al atender y al cobrar.
                </p>
                <a href="{{ route('contacto') }}" data-asesoria class="ara-btn ara-btn-primary ara-btn-lg">
                    Solicitar Demo
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </a>
            </div>
        </div>
    </section>

    <x-v2.cta-section
        title="¿Quieres verlo con los datos de tu consultorio?"
        subtitle="Cuéntanos cómo trabajan hoy tu agenda y tus historias clínicas, y te mostramos Gestión de Clínica funcionando con ese caso."
        buttonText="Solicitar Demo"
    />

    @include('aracode.components.v2.footer')
@endsection
