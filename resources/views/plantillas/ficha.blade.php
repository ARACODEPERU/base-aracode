@extends('aracode.layouts.webpage')

@section('meta_title', $plantilla['nombre'].' — Plantilla web | ARACODE Smart Solutions')
@section('meta_description', $plantilla['resumen'])

@php
    /*
        Ficha de una plantilla del Banco de Plantillas Web.

        La ruta entrega la plantilla ya resuelta (CatalogoPlantillas::porSlug) y
        `$urlDemo` ya comprobado: si la carpeta de la demostración todavía no
        existe, aquí llega null y la ficha avisa en vez de ofrecer un enlace roto.
    */
    $imagen = \App\Support\CatalogoPlantillas::urlImagen($plantilla);
    $urlCatalogo = route('plantillas.catalogo');
@endphp

{{--
    Tarjeta social. El título y la descripción son los mismos que ya usa la
    página (`meta_title` y `resumen`), para que el enlace compartido no diga algo
    distinto de lo que se lee al abrirlo, y la imagen es la captura 16/10 de la
    plantilla, que es la proporción de la tarjeta grande. Se declara solo cuando
    la captura existe: sin ella manda la imagen social del sitio, y no se
    anuncian medidas que no son. El 1600 × 1000 no es un dato suelto: es la
    medida en que se preparan las capturas (docs/BANCO_PLANTILLAS.md) y la
    prueba del catálogo la exige.
--}}
@if ($imagen)
    @section('og_title', $plantilla['nombre'].' — Plantilla web | ARACODE Smart Solutions')
    @section('og_description', $plantilla['resumen'])
    @section('og_image', $imagen)

    @push('head')
        <meta property="og:image:type" content="image/jpeg">
        <meta property="og:image:width" content="1600">
        <meta property="og:image:height" content="1000">
        <meta property="og:image:alt" content="Vista previa de la plantilla {{ $plantilla['nombre'] }}">
    @endpush
@endif

@push('head')
    <link rel="stylesheet" href="{{ asset('themes/plantillas/css/plantillas.css') }}">
@endpush

@push('json-ld')
{{-- `@@` escapa la directiva @context de Blade, igual que en el layout. --}}
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@graph": [
        {
            "@@type": "CreativeWork",
            "name": "{{ $plantilla['nombre'] }}",
            "description": "{{ $plantilla['resumen'] }}",
            "url": "{{ route('plantillas.ficha', $plantilla['slug']) }}",
            "inLanguage": "es-PE",
            "genre": "Plantilla web",
            "keywords": "{{ implode(', ', $plantilla['etiquetas']) }}",
            @if ($plantilla['actualizada'] !== '')
                "dateModified": "{{ $plantilla['actualizada'] }}",
            @endif
            "creator": {
                "@@type": "Organization",
                "name": "ARACODE Smart Solutions",
                "url": "{{ url('/') }}"
            }@if ($urlDemo),
            "workExample": {
                "@@type": "WebSite",
                "name": "Demostración de {{ $plantilla['nombre'] }}",
                "url": "{{ $urlDemo }}"
            }@endif
        },
        {
            "@@type": "BreadcrumbList",
            "itemListElement": [
                {
                    "@@type": "ListItem",
                    "position": 1,
                    "name": "Inicio",
                    "item": "{{ url('/') }}"
                },
                {
                    "@@type": "ListItem",
                    "position": 2,
                    "name": "Plantillas",
                    "item": "{{ $urlCatalogo }}"
                },
                {
                    "@@type": "ListItem",
                    "position": 3,
                    "name": "{{ $plantilla['nombre'] }}",
                    "item": "{{ route('plantillas.ficha', $plantilla['slug']) }}"
                }
            ]
        }
    ]
}
</script>
@endpush

@section('content')
    @include('aracode.components.v2.navbar')

    <div class="plt-app">
        {{-- ===================== PORTADA ===================== --}}
        <section class="plt-hero">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <nav aria-label="Ruta de navegación" class="mb-6">
                    <ol class="plt-breadcrumb">
                        <li><a href="{{ route('index_main') }}">Inicio</a></li>
                        <li aria-hidden="true">/</li>
                        <li><a href="{{ $urlCatalogo }}">Plantillas</a></li>
                        <li aria-hidden="true">/</li>
                        <li aria-current="page">{{ $plantilla['nombre'] }}</li>
                    </ol>
                </nav>

                {{-- Categoría y formato: el formato es lo que se compara con el plan --}}
                <div class="plt-hero__etiquetas">
                    <span class="ara-badge ara-badge-blue">{{ $plantilla['categoria_nombre'] }}</span>

                    @if ($plantilla['tipo_hashtag'] !== '')
                        <span class="plt-tipo plt-tipo--claro">{{ $plantilla['tipo_hashtag'] }}</span>
                    @endif
                </div>

                <h1 class="plt-hero__titulo">{{ $plantilla['nombre'] }}</h1>

                <p class="plt-hero__lead">{{ $plantilla['resumen'] }}</p>

                <div class="flex flex-col sm:flex-row gap-3 mt-8">
                    @if ($urlDemo)
                        <a href="{{ route('plantillas.demo', $plantilla['slug']) }}"
                           class="ara-btn ara-btn-primary ara-btn-lg" target="_blank" rel="noopener">
                            Ver la demostración
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                            <span class="sr-only">(se abre en una pestaña nueva)</span>
                        </a>
                    @endif

                    <a href="{{ route('contacto') }}" class="ara-btn ara-btn-secondary ara-btn-lg">
                        Pedir esta plantilla
                    </a>
                </div>
            </div>
        </section>

        {{-- ===================== CONTENIDO ===================== --}}
        <section class="py-14 lg:py-20 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="plt-ficha">
                    {{-- Columna principal --}}
                    <div>
                        {{-- Captura grande (o el aviso de que todavía no la hay) --}}
                        <div class="plt-captura">
                            @if ($imagen)
                                <img src="{{ $imagen }}"
                                     alt="Vista previa de la plantilla {{ $plantilla['nombre'] }}"
                                     decoding="async">
                            @else
                                <span class="plt-card__sin-captura">Captura en preparación</span>
                            @endif
                        </div>

                        <div class="plt-bloque">
                            <h2 class="plt-bloque__titulo">Qué incluye</h2>

                            <ul class="plt-lista">
                                @foreach ($plantilla['incluye'] as $punto)
                                    <li>
                                        <svg fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>{{ $punto }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="plt-bloque">
                            <h2 class="plt-bloque__titulo">Tecnologías</h2>

                            <ul class="plt-tech">
                                @foreach ($plantilla['tecnologias'] as $tecnologia)
                                    <li>{{ $tecnologia }}</li>
                                @endforeach
                            </ul>
                        </div>

                        @if (count($plantilla['etiquetas']) > 0)
                            <div class="plt-bloque">
                                <h2 class="plt-bloque__titulo">También sirve para</h2>

                                <ul class="plt-tech">
                                    @foreach ($plantilla['etiquetas'] as $etiqueta)
                                        <li>{{ $etiqueta }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>

                    {{-- Panel lateral: acciones y datos --}}
                    <aside class="plt-panel" aria-label="Datos de la plantilla">
                        <div class="plt-panel__acciones">
                            @if ($urlDemo)
                                <a href="{{ route('plantillas.demo', $plantilla['slug']) }}"
                                   class="ara-btn ara-btn-primary" target="_blank" rel="noopener">
                                    Ver la demostración
                                    <span class="sr-only">(se abre en una pestaña nueva)</span>
                                </a>
                            @else
                                <div class="plt-aviso">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span>
                                        La demostración de esta plantilla está en preparación.
                                        Podemos mostrártela en una reunión.
                                    </span>
                                </div>
                            @endif

                            <a href="{{ route('contacto') }}" class="ara-btn ara-btn-ghost">
                                Pedir esta plantilla
                            </a>
                        </div>

                        <dl class="plt-datos">
                            <div>
                                <dt>Categoría</dt>
                                <dd>
                                    <a href="{{ route('plantillas.catalogo', ['categoria' => $plantilla['categoria']]) }}"
                                       class="text-ara-blue hover:underline">
                                        {{ $plantilla['categoria_nombre'] }}
                                    </a>
                                </dd>
                            </div>

                            <div>
                                <dt>Formato</dt>
                                <dd>{{ $plantilla['tipo_nombre'] !== '' ? $plantilla['tipo_nombre'] : 'Plantilla web' }}</dd>
                            </div>

                            <div>
                                <dt>Demostración</dt>
                                <dd>{{ $urlDemo ? 'Disponible' : 'En preparación' }}</dd>
                            </div>

                            @if ($plantilla['actualizada'] !== '')
                                <div>
                                    <dt>Actualizada</dt>
                                    {{-- locale('es') explícito: el idioma de la app puede ser 'en' --}}
                                    <dd>{{ \Illuminate\Support\Carbon::parse($plantilla['actualizada'])->locale('es')->translatedFormat('F Y') }}</dd>
                                </div>
                            @endif
                        </dl>

                        {{-- El formato decide el plan: se explica aquí, no solo se nombra --}}
                        @if ($plantilla['tipo_descripcion'] !== '')
                            <p class="plt-nota">
                                <strong class="plt-nota__etiqueta">{{ $plantilla['tipo_hashtag'] }}</strong>
                                {{ $plantilla['tipo_descripcion'] }}
                            </p>
                        @endif
                    </aside>
                </div>
            </div>
        </section>

        {{-- ===================== RELACIONADAS ===================== --}}
        @if (count($relacionadas) > 0)
            <section class="py-14 lg:py-16 bg-ara-slate-50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl lg:text-3xl font-bold text-ara-slate-700 mb-8">
                        Más plantillas de {{ $plantilla['categoria_nombre'] }}
                    </h2>

                    <div class="plt-rejilla">
                        @foreach ($relacionadas as $relacionada)
                            <x-plantillas.tarjeta :plantilla="$relacionada" />
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        {{-- ===================== CTA ===================== --}}
        <x-v2.cta-section
            title="¿Adaptamos esta plantilla a tu negocio?"
            subtitle="Cuéntanos qué necesitas cambiar y publicamos tu sitio con tu dominio, tus textos y tus imágenes."
            buttonText="Hablar con un Especialista"
        />
    </div>

    @include('aracode.components.v2.footer')
@endsection
