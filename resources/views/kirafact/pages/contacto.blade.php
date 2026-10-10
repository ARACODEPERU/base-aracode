{{--
    Contacto comercial.

    Solo canales reales y verificados. No hay formulario propio porque esta web
    no tiene backend que reciba mensajes: se atiende por WhatsApp y correo, que
    son los canales que ARACODE ya usa.
--}}
@extends('kirafact.layouts.app')

@section('title', 'Contacto comercial — KIRAFACT')
@section('description', 'Escríbenos para conocer KIRAFACT: facturación electrónica y gestión empresarial para empresas del Perú. Atención por WhatsApp y correo.')
@section('og_title', 'Contacto comercial — KIRAFACT')
@section('og_description', 'Conversa con el equipo de ARACODE Smart Solutions sobre KIRAFACT y la facturación electrónica de tu empresa.')

@php
    $contacto = config('kirafact.contacto');
@endphp

@section('content')
    @include('kirafact.components.encabezado', [
        'etiqueta' => 'Contacto',
        'titulo' => 'Hablemos de la facturación de tu negocio',
        'texto' => 'Cuéntanos cómo factura hoy tu empresa y revisamos contigo si KIRAFACT encaja en tu operación. Atiende el equipo de ARACODE Smart Solutions.',
    ])

    <section class="kf-surface-light">
        <div class="kf-container kf-section">
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <a href="{{ $contacto['whatsapp_url'] }}?text={{ rawurlencode(config('kirafact.mensajes.info_whatsapp')) }}"
                   target="_blank" rel="noopener" class="kf-card kf-card-interactive kf-reveal">
                    <span class="kf-icon-box">
                        @include('kirafact.components.icon', ['icono' => 'whatsapp', 'iconoClase' => 'h-6 w-6'])
                    </span>
                    <h2 class="kf-h3 mt-5">WhatsApp</h2>
                    <p class="kf-slate mt-2 text-sm leading-relaxed">{{ $contacto['whatsapp'] }}</p>
                    <span class="kf-link mt-4 inline-flex items-center gap-2 text-sm">
                        <span>Abrir conversación</span>
                        @include('kirafact.components.icon', ['icono' => 'externo', 'iconoClase' => 'h-4 w-4'])
                    </span>
                </a>

                <a href="mailto:{{ $contacto['email'] }}" class="kf-card kf-card-interactive kf-reveal">
                    <span class="kf-icon-box">
                        @include('kirafact.components.icon', ['icono' => 'correo', 'iconoClase' => 'h-6 w-6'])
                    </span>
                    <h2 class="kf-h3 mt-5">Correo</h2>
                    <p class="kf-slate mt-2 text-sm leading-relaxed">{{ $contacto['email'] }}</p>
                    <span class="kf-link mt-4 inline-flex items-center gap-2 text-sm">
                        <span>Escribir un correo</span>
                        @include('kirafact.components.icon', ['icono' => 'externo', 'iconoClase' => 'h-4 w-4'])
                    </span>
                </a>

                <div class="kf-card kf-reveal">
                    <span class="kf-icon-box">
                        @include('kirafact.components.icon', ['icono' => 'ubicacion', 'iconoClase' => 'h-6 w-6'])
                    </span>
                    <h2 class="kf-h3 mt-5">Oficina</h2>
                    <p class="kf-slate mt-2 text-sm leading-relaxed">{{ $contacto['ciudad'] }}</p>
                </div>

                <div class="kf-card kf-reveal">
                    <span class="kf-icon-box">
                        @include('kirafact.components.icon', ['icono' => 'visibilidad', 'iconoClase' => 'h-6 w-6'])
                    </span>
                    <h2 class="kf-h3 mt-5">Ver el sistema</h2>
                    <p class="kf-slate mt-2 text-sm leading-relaxed">
                        Si prefieres verlo antes de conversar, pide una demo y te mostramos cómo se
                        emite un comprobante y cómo se sigue su estado.
                    </p>
                    <span class="mt-4">
                        @include('kirafact.components.btn-demo', ['class' => 'kf-btn-secondary kf-btn-sm'])
                    </span>
                </div>
            </div>

            <p class="kf-note kf-reveal mt-10">
                ¿Quieres ver el detalle del producto antes de escribirnos? Revisa las
                <a href="{{ route('kirafact.home') }}#funcionalidades" class="kf-link">funcionalidades</a>
                y las
                <a href="{{ route('kirafact.home') }}#preguntas" class="kf-link">preguntas frecuentes</a>.
            </p>
        </div>
    </section>

    @include('kirafact.components.cta-final')
@endsection
