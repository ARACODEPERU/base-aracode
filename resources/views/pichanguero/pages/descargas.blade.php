@extends('pichanguero.layouts.app')

@section('title', 'Descargar la app | Pichanguero')
@section('description', 'Descarga la app Pichanguero para Android y sigue el fixture, los resultados y las estadísticas de tu torneo desde el celular.')

@section('content')
    @include('pichanguero.components.navbar')

    {{-- ===================== ENCABEZADO ===================== --}}
    <section class="pg-surface-dark relative overflow-hidden pb-16 pt-28 sm:pt-32 lg:pt-40">
        <div class="absolute inset-0 -z-10">
            <img src="{{ asset('themes/pichanguero/images/bg-page.jpg') }}" alt=""
                 class="pg-photo opacity-20" width="1920" height="558">
            <div class="absolute inset-0 bg-gradient-to-b from-pg-navy/90 via-pg-navy/95 to-pg-navy"></div>
        </div>
        <div class="pg-slash left-[-6%] top-20 h-[380px] w-2 rotate-[14deg] opacity-40" aria-hidden="true"></div>

        <div class="pg-container relative">
            <span class="pg-badge pg-badge-accent">App Android</span>
            <h1 class="mt-6 text-3xl font-extrabold text-white sm:text-5xl">
                Descarga <span class="pg-accent">Pichanguero</span>
            </h1>
            <p class="pg-lead mt-5">
                Instala la app y sigue tu campeonato desde el celular: fixture, tabla de posiciones,
                goleadores y resultados.
            </p>
        </div>
    </section>

    {{-- ===================== DESCARGA ===================== --}}
    <section class="pg-surface-light py-16 sm:py-20">
        <div class="pg-container">
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                {{-- Tarjeta de descarga --}}
                <div class="pg-card lg:col-span-2">
                    <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-4">
                            <span class="pg-icon-box h-14 w-14 shrink-0 !bg-white">
                                <img src="{{ asset('themes/pichanguero/images/app-keeper.webp') }}"
                                     alt="Ilustración de la aplicación Pichanguero"
                                     class="h-11 w-11" width="600" height="600" loading="lazy" decoding="async">
                            </span>
                            <div>
                                <p class="pg-display pg-heading text-lg font-bold">Pichanguero</p>
                                <p class="text-sm pg-body">Versión 1.0.0 · Android · APK</p>
                            </div>
                        </div>

                        <a href="{{ asset('downloads/pichanguero.apk') }}" class="pg-btn pg-btn-primary" download>
                            Descargar APK
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                        </a>
                    </div>

                    <div class="mt-8 border-t pg-hairline pt-8">
                        <h2 class="pg-heading text-base font-bold">Cómo instalarla</h2>
                        <ol class="mt-6 space-y-5">
                            @php
                                $steps = [
                                    'Descarga el archivo APK desde el botón de arriba.',
                                    'Si Android lo solicita, habilita la instalación desde esta fuente para tu navegador.',
                                    'Abre el archivo descargado y confirma la instalación.',
                                ];
                            @endphp

                            @foreach($steps as $index => $step)
                                <li class="flex gap-4">
                                    <span class="pg-step-number">{{ $index + 1 }}</span>
                                    <p class="pt-2 pg-body">{{ $step }}</p>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </div>

                {{-- Panel lateral --}}
                <div class="space-y-6">
                    <div class="pg-card">
                        <h3 class="pg-heading text-base font-bold">Qué incluye la app</h3>
                        <ul class="mt-4 space-y-3 text-sm pg-body">
                            @foreach([
                                'Fixture y próximos partidos',
                                'Tabla de posiciones actualizada',
                                'Goleadores y estadísticas',
                                'Resultados y marcadores en vivo',
                            ] as $feature)
                                <li class="flex items-start gap-3">
                                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-pg-green" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ $feature }}
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="pg-card">
                        <h3 class="pg-heading text-base font-bold">¿Problemas para instalar?</h3>
                        <p class="mt-2 text-sm pg-body">
                            Escríbenos y te acompañamos en la instalación y en la configuración de tu torneo.
                        </p>
                        <a href="{{ route('pichanguero.contacto') }}" class="pg-btn pg-btn-green pg-btn-sm mt-5">
                            Contactar soporte
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('pichanguero.components.footer')
@endsection
