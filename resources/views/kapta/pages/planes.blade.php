@extends('kapta.layouts.app')

@section('title', 'Planes y precios | KAPTA LMS')
@section('description', 'Seis planes de KAPTA LMS en tres niveles, con dominio, SSL, hosting y mantenimiento incluidos. Compara alumnos activos, usuarios, almacenamiento y módulos en soles.')
@section('og_title', 'Planes y precios de KAPTA LMS')
@section('og_description', 'Compara los seis planes de KAPTA LMS: alumnos activos, usuarios administrativos, almacenamiento, tienda online y certificación.')

@php
    $contacto = config('kapta.contacto');
    $planes = config('kapta.planes');
    $niveles = config('kapta.niveles');
    $comparativa = config('kapta.comparativa');
    $demoHref = $contacto['whatsapp_url'] . '?text=' . urlencode(config('kapta.mensajes.demo_whatsapp'));
@endphp

@section('content')
    @include('kapta.components.navbar')

    {{-- ==========================================================
         ENCABEZADO
         ========================================================== --}}
    <section class="ka-surface-dark relative overflow-hidden pb-16 pt-28 sm:pt-32 lg:pt-40">
        <div class="absolute inset-0 -z-10" aria-hidden="true">
            <img src="{{ asset('themes/kapta/images/bg-page.webp') }}" alt=""
                 class="ka-photo opacity-[0.2]" width="2000" height="1271">
            <div class="absolute inset-0"
                 style="background-image: linear-gradient(180deg, rgba(11,23,64,.84) 0%, rgba(11,23,64,.94) 52%, #0b1740 100%)"></div>
        </div>
        <div class="ka-grid-lines absolute inset-0 -z-10" aria-hidden="true"></div>

        <div class="ka-container relative">
            <div class="mx-auto max-w-3xl text-center">
                <span class="ka-badge">Planes y precios</span>
                <h1 class="ka-h1 ka-reveal mt-6 text-white">
                    Un plan para cada <span class="ka-accent">tamaño de institución</span>
                </h1>
                <p class="ka-lead ka-reveal mt-5 text-ka-muted">
                    Seis planes en tres niveles: desde una academia que dicta sus primeros cursos
                    hasta una organización que forma a cientos de alumnos a la vez.
                </p>
            </div>

            {{-- Conmutador de facturación --}}
            <div class="ka-reveal mt-10 flex flex-col items-center gap-4">
                <div class="ka-switch" role="group" aria-label="Periodo de facturación">
                    <button type="button" data-periodo="mensual" aria-pressed="true">Mensual</button>
                    <button type="button" data-periodo="anual" aria-pressed="false">Anual</button>
                </div>
                <p class="text-xs text-ka-muted">
                    Todos los planes incluyen dominio, SSL, hosting y mantenimiento del sistema.
                </p>
            </div>
        </div>
    </section>

    {{-- ==========================================================
         PLANES POR NIVEL
         ========================================================== --}}
    <section class="ka-surface-light ka-section">
        <div class="ka-container space-y-16 lg:space-y-20">
            @foreach ($niveles as $clave => $nivel)
                <div id="{{ $clave }}" class="scroll-mt-28">
                    <div class="mx-auto max-w-2xl text-center">
                        <h2 class="ka-h2 ka-reveal ka-ink">{{ $nivel['nombre'] }}</h2>
                        <p class="ka-lead ka-reveal mt-3 text-base">{{ $nivel['resumen'] }}</p>
                    </div>

                    <div class="mt-10 grid grid-cols-1 gap-6 md:grid-cols-2">
                        @foreach (collect($planes)->where('nivel', $clave) as $plan)
                            <article class="ka-plan ka-reveal {{ $plan['destacado'] ? 'ka-plan-featured' : '' }}">
                                @if ($plan['destacado'])
                                    <span class="ka-badge absolute -top-3 left-6">El más elegido</span>
                                @endif

                                <h3 class="ka-plan-name">{{ $plan['nombre'] }}</h3>
                                <p class="mt-2 text-sm leading-relaxed ka-slate">{{ $plan['resumen'] }}</p>

                                <div class="mt-6">
                                    <span data-precio="mensual" class="is-active">
                                        <span class="ka-plan-price">{{ $plan['mensual'] }}</span>
                                        <span class="ka-plan-period">/ mes</span>
                                    </span>
                                    <span data-precio="anual">
                                        <span class="ka-plan-price">{{ $plan['anual'] }}</span>
                                        <span class="ka-plan-period">/ año</span>
                                    </span>
                                </div>

                                <p class="mt-3 text-xs font-bold uppercase tracking-wide" style="color: var(--ka-accent)">
                                    {{ $plan['capacidad'] }}
                                </p>

                                <ul class="ka-plan-list">
                                    @foreach ($plan['incluye'] as $prestacion)
                                        <li>
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            {{ $prestacion }}
                                        </li>
                                    @endforeach
                                </ul>

                                <a href="{{ $plan['whatsapp'] }}" target="_blank" rel="noopener"
                                   class="ka-btn mt-8 {{ $plan['destacado'] ? 'ka-btn-primary' : 'ka-btn-secondary' }}">
                                    Solicitar el plan {{ $plan['nombre'] }}
                                </a>
                            </article>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ==========================================================
         COMPARATIVA
         ========================================================== --}}
    <section class="ka-surface-mist ka-section">
        <div class="ka-container ka-container-wide">
            <div class="mx-auto max-w-2xl text-center">
                <span class="ka-badge">Comparativa</span>
                <h2 class="ka-h2 ka-reveal mt-5 ka-ink">Los seis planes, prestación por prestación</h2>
                <p class="ka-lead ka-reveal mt-4">
                    Desplaza la tabla para ver todas las columnas. Si tu institución necesita algo
                    distinto, lo conversamos y armamos un plan a la medida.
                </p>
            </div>

            <div class="ka-reveal ka-table-wrap mt-10">
                <table class="ka-table">
                    <caption class="sr-only">Comparación de prestaciones de los planes de KAPTA LMS</caption>
                    <thead>
                        <tr>
                            <th scope="col">Prestación</th>
                            @foreach ($planes as $plan)
                                <th scope="col">{{ $plan['nombre'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($comparativa as $clave => $etiqueta)
                            <tr>
                                <th scope="row">{{ $etiqueta }}</th>
                                @foreach ($planes as $plan)
                                    <td>{{ $plan['ficha'][$clave] ?? '—' }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="ka-reveal mt-8 grid grid-cols-1 gap-4 lg:grid-cols-2">
                <div class="ka-note">
                    <span class="font-semibold ka-ink">La capacidad se mide en alumnos activos:</span>
                    los que cursan de forma simultánea. El total de alumnos registrados en el campus no
                    determina el plan.
                </div>
                <div class="ka-note">
                    {{ config('kapta.mensajes.nota_precios') }}
                </div>
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

                <h2 class="ka-h2 relative text-white">Te mostramos KAPTA con tus propios cursos</h2>
                <p class="relative mx-auto mt-4 max-w-2xl text-ka-muted">
                    Agenda una demostración y revisa cómo quedarían tus módulos, tus matrículas
                    y tus certificados dentro de la plataforma.
                </p>
                <div class="relative mt-8 flex flex-col justify-center gap-4 sm:flex-row">
                    <a href="{{ $demoHref }}" target="_blank" rel="noopener" class="ka-btn ka-btn-primary ka-btn-lg">
                        Solicitar una demo
                    </a>
                    <a href="{{ route('kapta.home') }}#funcionalidades" class="ka-btn ka-btn-secondary ka-btn-lg">
                        Ver funcionalidades
                    </a>
                </div>
            </div>
        </div>
    </section>

    @include('kapta.components.footer')
@endsection
