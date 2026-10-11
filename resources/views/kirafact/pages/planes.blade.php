{{--
    Planes.

    Las tarifas de KIRAFACT todavía no están confirmadas, así que la página NO
    publica importes: explica que la propuesta se confirma con el equipo
    comercial y ofrece el contacto real.

    Cuando el propietario confirme importes y prestaciones, basta con poner
    'publicar' => true en config('kirafact.planes') y esta página muestra las
    tarjetas con la lista de config, sin tocar la vista.
--}}
@extends('kirafact.layouts.app')

@section('title', 'Planes — KIRAFACT')
@section('description', 'Planes de KIRAFACT, software de facturación electrónica y gestión empresarial. Consulta con el equipo comercial la propuesta vigente para tu empresa.')

@php
    $planes = config('kirafact.planes');
    $contacto = config('kirafact.contacto');
@endphp

@section('content')
    @include('kirafact.components.encabezado', [
        'etiqueta' => 'Planes',
        'titulo' => $planes['publicar'] ? $planes['titulo_pendiente'] : 'Confirmemos tu plan',
        'texto' => $planes['publicar']
            ? 'Elige la modalidad que mejor se ajuste al movimiento de tu negocio.'
            : $planes['texto_pendiente'],
    ])

    <section class="kf-surface-light">
        <div class="kf-container kf-section">
            @if ($planes['publicar'])
                {{-- Tarifas publicadas: solo se llega aquí con los importes confirmados --}}
                <div class="grid gap-6 lg:grid-cols-3">
                    @foreach ($planes['lista'] as $plan)
                        <article class="kf-plan kf-reveal">
                            <h2 class="kf-plan-name">{{ $plan['nombre'] }}</h2>
                            <p class="kf-slate mt-2 text-sm">{{ $plan['resumen'] }}</p>

                            <p class="mt-6 flex items-baseline gap-1">
                                <span class="kf-plan-price">{{ $plan['mensual'] }}</span>
                                <span class="kf-slate text-sm">/mes</span>
                            </p>
                            @if ($plan['anual'])
                                <p class="kf-slate mt-1 text-xs">o {{ $plan['anual'] }} al año</p>
                            @endif

                            <ul class="kf-plan-list">
                                @foreach ($plan['incluye'] as $incluye)
                                    <li>
                                        @include('kirafact.components.icon', ['icono' => 'check', 'iconoClase' => 'h-4 w-4'])
                                        <span>{{ $incluye }}</span>
                                    </li>
                                @endforeach
                            </ul>

                            <a href="{{ route('kirafact.contacto') }}" class="kf-btn kf-btn-primary kf-btn-block mt-7">
                                Quiero este plan
                            </a>
                        </article>
                    @endforeach
                </div>

                <p class="kf-note kf-reveal mt-8">{{ $planes['nota_publicada'] }}</p>
            @else
                {{-- Tarifas sin confirmar: no se publica ningún importe --}}
                <div class="kf-card kf-reveal mx-auto max-w-2xl text-center">
                    <span class="kf-icon-box mx-auto">
                        @include('kirafact.components.icon', ['icono' => 'candado', 'iconoClase' => 'h-6 w-6'])
                    </span>

                    <h2 class="kf-h2 mt-6">Tarifas en confirmación</h2>
                    <p class="kf-lead mt-4">
                        Preferimos no publicar una lista de precios que todavía no está confirmada.
                        Escríbenos y el equipo comercial te envía la propuesta vigente para el uso
                        que le dará tu empresa.
                    </p>

                    <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <a href="{{ $contacto['whatsapp_url'] }}?text={{ rawurlencode(config('kirafact.mensajes.demo_whatsapp')) }}"
                           target="_blank" rel="noopener" class="kf-btn kf-btn-primary w-full sm:w-auto">
                            @include('kirafact.components.icon', ['icono' => 'whatsapp', 'iconoClase' => 'h-4 w-4'])
                            <span>Escríbenos por WhatsApp</span>
                        </a>
                        <a href="mailto:{{ $contacto['email'] }}" class="kf-btn kf-btn-secondary w-full sm:w-auto">
                            @include('kirafact.components.icon', ['icono' => 'correo', 'iconoClase' => 'h-4 w-4'])
                            <span>Escríbenos por correo</span>
                        </a>
                    </div>

                    <p class="kf-slate mt-6 text-xs leading-relaxed">{{ $planes['nota'] }}</p>
                </div>

                <p class="kf-note kf-reveal mx-auto mt-8 max-w-2xl">
                    Mientras tanto, puedes revisar qué incluye el producto en la
                    <a href="{{ route('kirafact.home') }}#funcionalidades" class="kf-link">página de funcionalidades</a>.
                </p>
            @endif
        </div>
    </section>

    @include('kirafact.components.cta-final')
@endsection
