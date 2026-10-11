@extends('kapta.layouts.app')

@section('title', 'Contacto | KAPTA LMS')
@section('description', 'Escríbenos por WhatsApp o correo para agendar una demostración de KAPTA LMS, resolver dudas sobre los planes o coordinar la puesta en marcha de tu campus virtual.')
@section('og_title', 'Contacto comercial de KAPTA LMS')
@section('og_description', 'Agenda una demostración de KAPTA LMS o resuelve tus dudas sobre los planes por WhatsApp o correo.')

@php
    $contacto = config('kapta.contacto');
    $campusUrl = config('kapta.campus_url');
    $demoHref = $contacto['whatsapp_url'] . '?text=' . urlencode(config('kapta.mensajes.demo_whatsapp'));
    $infoHref = $contacto['whatsapp_url'] . '?text=' . urlencode(config('kapta.mensajes.info_whatsapp'));
    $soporteHref = $contacto['whatsapp_url'] . '?text=' . urlencode('Hola, necesito soporte de KAPTA LMS.');
@endphp

@section('content')
    @include('kapta.components.navbar')

    {{-- ==========================================================
         ENCABEZADO
         ========================================================== --}}
    <section class="ka-surface-dark relative overflow-hidden pb-16 pt-28 sm:pt-32 lg:pt-40">
        <div class="absolute inset-0 -z-10" aria-hidden="true">
            <img src="{{ asset('themes/kapta/images/bg-hero.webp') }}" alt=""
                 class="ka-photo opacity-[0.2]" width="2000" height="1271">
            <div class="absolute inset-0"
                 style="background-image: linear-gradient(180deg, rgba(11,23,64,.84) 0%, rgba(11,23,64,.94) 52%, #0b1740 100%)"></div>
        </div>
        <div class="ka-grid-lines absolute inset-0 -z-10" aria-hidden="true"></div>

        <div class="ka-container relative">
            <div class="max-w-3xl">
                <span class="ka-badge">Contacto</span>
                <h1 class="ka-h1 ka-reveal mt-6 text-white">
                    Hablemos de tu <span class="ka-accent">formación</span>
                </h1>
                <p class="ka-lead ka-reveal mt-5 text-ka-muted">
                    Cuéntanos qué programas dictas hoy y te mostramos cómo se verían dentro de KAPTA LMS.
                    Sin formularios largos: escribes y te responde una persona del equipo.
                </p>
            </div>
        </div>
    </section>

    {{-- ==========================================================
         VÍAS DE CONTACTO
         ========================================================== --}}
    <section class="ka-surface-light ka-section">
        <div class="ka-container">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <a href="{{ $demoHref }}" target="_blank" rel="noopener" class="ka-card ka-card-interactive ka-reveal">
                    <span class="ka-icon-box">
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.347-.347.52-.52.174-.174.232-.298.347-.497.115-.198.057-.371-.06-.52-.115-.148-.66-1.59-.904-2.176-.238-.573-.48-.494-.66-.503-.171-.008-.367-.01-.563-.01-.196 0-.513.073-.782.372-.269.298-1.026 1.002-1.026 2.44s1.05 2.83 1.196 3.024c.149.199 2.066 3.155 5.006 4.425.699.302 1.245.482 1.672.616.702.223 1.34.192 1.845.116.563-.084 1.73-.707 1.974-1.39.244-.682.244-1.267.171-1.39-.073-.124-.269-.198-.563-.347zM12.05 21.785h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884a9.82 9.82 0 016.988 2.898 9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884zm8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                    </span>
                    <h2 class="ka-h3 mt-5 ka-ink">WhatsApp</h2>
                    <p class="mt-2 text-sm ka-slate">{{ $contacto['whatsapp'] }}</p>
                    <p class="mt-2 text-xs ka-slate">La vía más rápida para coordinar una demostración.</p>
                    <span class="ka-link mt-4 text-sm">Escribir ahora</span>
                </a>

                <a href="mailto:{{ $contacto['email'] }}?subject={{ rawurlencode('Información sobre KAPTA LMS') }}"
                   class="ka-card ka-card-interactive ka-reveal">
                    <span class="ka-icon-box">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                                  d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </span>
                    <h2 class="ka-h3 mt-5 ka-ink">Correo comercial</h2>
                    <p class="mt-2 text-sm ka-slate">{{ $contacto['email'] }}</p>
                    <p class="mt-2 text-xs ka-slate">Para propuestas y requerimientos detallados.</p>
                    <span class="ka-link mt-4 text-sm">Escribir un correo</span>
                </a>

                <div class="ka-card ka-reveal">
                    <span class="ka-icon-box">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </span>
                    <h2 class="ka-h3 mt-5 ka-ink">Ubicación</h2>
                    <p class="mt-2 text-sm ka-slate">{{ $contacto['ciudad'] }}</p>
                    <p class="mt-2 text-xs ka-slate">Atendemos a todo el país de forma remota.</p>
                    @if ($campusUrl)
                        <a href="{{ $campusUrl }}" target="_blank" rel="noopener" class="ka-link mt-4 text-sm">Ir al campus virtual</a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- ==========================================================
         MOTIVOS DE CONTACTO
         ========================================================== --}}
    <section class="ka-surface-mist ka-section">
        <div class="ka-container">
            <div class="mx-auto max-w-2xl text-center">
                <span class="ka-badge">Cuéntanos qué necesitas</span>
                <h2 class="ka-h2 ka-reveal mt-5 ka-ink">Tres motivos habituales para escribirnos</h2>
            </div>

            <div class="mt-12 grid grid-cols-1 gap-6 md:grid-cols-3">
                @php
                    $motivos = [
                        [
                            'titulo' => 'Quiero una demostración',
                            'texto' => 'Revisamos la plataforma con tus cursos, tus módulos y tus certificados para que veas el flujo completo.',
                            'cta' => 'Agendar una demo',
                            'href' => $demoHref,
                        ],
                        [
                            'titulo' => 'Necesito saber qué plan me toca',
                            'texto' => 'Cuéntanos cuántos alumnos cursan a la vez y qué módulos necesitas; te indicamos el plan que corresponde.',
                            'cta' => 'Consultar los planes',
                            'href' => $infoHref,
                        ],
                        [
                            'titulo' => 'Ya soy cliente y necesito soporte',
                            'texto' => 'Escríbenos por WhatsApp con el detalle del caso y el equipo técnico de ARACODE lo revisa.',
                            'cta' => 'Escribir a soporte',
                            'href' => $soporteHref,
                        ],
                    ];
                @endphp

                @foreach ($motivos as $motivo)
                    <div class="ka-card ka-reveal">
                        <h3 class="ka-h3 ka-ink">{{ $motivo['titulo'] }}</h3>
                        <p class="mt-2 text-sm leading-relaxed ka-slate">{{ $motivo['texto'] }}</p>
                        <a href="{{ $motivo['href'] }}" target="_blank" rel="noopener"
                           class="ka-btn ka-btn-primary ka-btn-sm mt-6 self-start">
                            {{ $motivo['cta'] }}
                        </a>
                    </div>
                @endforeach
            </div>

            {{--
                Nota para quien mantenga el sitio: esta página no incluye formulario
                a propósito, porque no hay aún un buzón o endpoint conectado y no se
                simula un envío de datos. Cuando exista el servicio real (controlador,
                ruta y validación), se añade el formulario aquí.
            --}}

            <div class="ka-reveal ka-note mx-auto mt-10 max-w-3xl">
                <span class="font-semibold ka-ink">Sobre los pagos y la facturación:</span>
                KAPTA LMS se cobra en soles y cada plan incluye dominio, SSL, hosting y mantenimiento.
                Escríbenos para confirmar disponibilidad y condiciones vigentes antes de contratar.
            </div>
        </div>
    </section>

    {{-- ==========================================================
         CTA FINAL
         ========================================================== --}}
    <section class="ka-surface-dark relative overflow-hidden pb-20 pt-4 lg:pb-24">
        <div class="ka-container">
            <div class="relative overflow-hidden rounded-[1.5rem] border px-8 py-12 text-center lg:px-14 lg:py-16"
                 style="border-color: var(--ka-surface-line); background-image: linear-gradient(135deg, #122459 0%, #0b1740 60%, #162d6b 100%)">
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
