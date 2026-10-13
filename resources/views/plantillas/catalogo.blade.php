@extends('aracode.layouts.webpage')

@section('meta_title', 'Banco de Plantillas Web | ARACODE Smart Solutions')
@section('meta_description', 'Plantillas web listas para personalizar: salud, gastronomía, servicios, educación, inmobiliaria y portafolios. Mira la demostración de cada una y pide la tuya a ARACODE.')

@php
    /*
        Catálogo del Banco de Plantillas Web.

        Los datos llegan resueltos del controlador (CatalogoPlantillas): aquí no
        se lee config/plantillas.php ni se decide nada del catálogo.

        Los filtros son un formulario GET: funcionan sin JavaScript y la canónica
        del layout no arrastra la query string, así que las combinaciones
        filtradas no se indexan como páginas distintas.
    */
    $categoriaActual = $categoria !== '' && isset($categorias[$categoria]) ? $categorias[$categoria] : null;
    $conteo = count($plantillas);
@endphp

@push('head')
    {{-- Estilos de la sección: se cargan solo aquí (ver el encabezado del archivo) --}}
    <link rel="stylesheet" href="{{ asset('themes/plantillas/css/plantillas.css') }}">
@endpush

@push('json-ld')
{{-- `@@` escapa la directiva @context de Blade, igual que en el layout. --}}
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "CollectionPage",
    "name": "Banco de Plantillas Web de ARACODE",
    "description": "Catálogo de plantillas web listas para personalizar, con demostración de cada una.",
    "url": "{{ route('plantillas.catalogo') }}",
    "inLanguage": "es-PE",
    "isPartOf": {
        "@@type": "WebSite",
        "name": "ARACODE Smart Solutions",
        "url": "{{ url('/') }}"
    },
    "mainEntity": {
        "@@type": "ItemList",
        "numberOfItems": {{ $conteo }},
        "itemListElement": [
            @foreach ($plantillas as $indice => $plantilla)
                {
                    "@@type": "ListItem",
                    "position": {{ $indice + 1 }},
                    "name": "{{ $plantilla['nombre'] }}",
                    "url": "{{ route('plantillas.ficha', $plantilla['slug']) }}"
                }@if (! $loop->last),@endif
            @endforeach
        ]
    }
}
</script>
@endpush

@section('content')
    @include('aracode.components.v2.navbar')

    <div class="plt-app">
        {{-- ===================== PORTADA ===================== --}}
        <section class="plt-hero">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <span class="ara-badge ara-badge-blue mb-6 inline-block reveal">Banco de Plantillas</span>

                <h1 class="plt-hero__titulo reveal reveal-delay-1">
                    Plantillas web listas para <span class="text-gradient">personalizar</span>
                </h1>

                <p class="plt-hero__lead reveal reveal-delay-2">
                    Elige el rubro, mira la demostración funcionando y la adaptamos con tu marca,
                    tus textos y tus datos. Cada plantilla es un sitio web completo, no una imagen.
                </p>

                {{-- Buscador: nombre, rubro o etiqueta --}}
                <form class="plt-buscador reveal reveal-delay-3" method="get"
                      action="{{ route('plantillas.catalogo') }}" role="search">
                    <label class="plt-buscador__label" for="plt-q">¿Qué rubro buscas?</label>

                    <div class="plt-buscador__campo">
                        <span class="plt-buscador__icono" aria-hidden="true">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                            </svg>
                        </span>

                        <input id="plt-q" type="search" name="q" value="{{ $busqueda }}"
                               placeholder="dental, carta, propiedades, cursos…"
                               aria-label="Buscar plantillas por nombre, rubro o etiqueta">

                        @if ($categoria !== '')
                            <input type="hidden" name="categoria" value="{{ $categoria }}">
                        @endif

                        <button type="submit" class="ara-btn ara-btn-primary">Buscar</button>
                    </div>
                </form>
            </div>
        </section>

        {{-- ===================== CATEGORÍAS Y REJILLA ===================== --}}
        <section class="py-12 lg:py-16 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                {{-- Categorías: enlaces, no botones: son URLs compartibles --}}
                <ul class="plt-categorias mb-10 reveal">
                    <li>
                        <a href="{{ route('plantillas.catalogo', array_filter(['q' => $busqueda])) }}"
                           class="plt-chip {{ $categoria === '' ? 'is-activa' : '' }}">
                            Todas
                            <span class="plt-chip__num">{{ $total }}</span>
                        </a>
                    </li>

                    @foreach ($categorias as $slug => $datos)
                        <li>
                            <a href="{{ route('plantillas.catalogo', array_filter(['categoria' => $slug, 'q' => $busqueda])) }}"
                               class="plt-chip {{ $categoria === $slug ? 'is-activa' : '' }}">
                                {{ $datos['nombre'] }}
                                <span class="plt-chip__num">{{ $datos['total'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                {{-- Encabezado de resultados: dice qué se está viendo --}}
                <div class="mb-8">
                    <h2 class="text-2xl lg:text-3xl font-bold text-ara-slate-700">
                        @if ($categoriaActual !== null)
                            Plantillas de {{ $categoriaActual['nombre'] }}
                        @elseif ($busqueda !== '')
                            Resultados para «{{ $busqueda }}»
                        @else
                            Todas las plantillas
                        @endif
                    </h2>

                    <p class="mt-2 text-ara-slate-400">
                        @if ($conteo === 1)
                            Una plantilla disponible
                        @else
                            {{ $conteo }} plantillas disponibles
                        @endif
                        @if ($categoriaActual !== null && $categoriaActual['descripcion'] !== '')
                            · {{ $categoriaActual['descripcion'] }}
                        @endif
                    </p>

                    @if ($categoria !== '' || $busqueda !== '')
                        <a href="{{ route('plantillas.catalogo') }}"
                           class="inline-block mt-3 text-sm font-semibold text-ara-blue hover:underline">
                            Quitar los filtros
                        </a>
                    @endif
                </div>

                <div class="plt-rejilla">
                    @forelse ($plantillas as $plantilla)
                        <x-plantillas.tarjeta :plantilla="$plantilla" />
                    @empty
                        <div class="plt-vacio">
                            <p class="plt-vacio__titulo">No hay plantillas con esos filtros</p>
                            <p class="plt-vacio__texto">
                                Prueba con otro rubro o escríbenos: si necesitas algo que no está en el
                                catálogo, lo desarrollamos a medida.
                            </p>
                            <a href="{{ route('contacto') }}" class="ara-btn ara-btn-primary">
                                Pedir una propuesta
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        {{-- ===================== EL SERVICIO =====================
             El visitante que ya vio la plantilla que le gusta se entera aquí de
             que publicarla es un plan mensual o anual, sin salir del catálogo:
             los precios salen de config/servicio_web.php (los mismos de la
             página del servicio), no se escriben en esta vista.
        --}}
        <section class="py-16 lg:py-20 bg-ara-slate-50 border-t border-ara-slate-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl mb-10 reveal">
                    <span class="ara-badge ara-badge-blue mb-4 inline-block">Publicación</span>

                    <h2 class="text-2xl lg:text-3xl font-bold text-ara-slate-700">
                        ¿Y después de elegir la plantilla?
                    </h2>

                    <p class="mt-3 text-ara-slate-400">
                        La publicamos nosotros con tu marca y tu dominio. Es un plan mensual o anual
                        —dominio, hosting y cambios incluidos—, no un proyecto por cotizar.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @foreach ($planesServicio as $plan)
                        <div class="ara-card reveal reveal-delay-{{ $loop->index + 1 }}">
                            <h3 class="text-lg font-bold text-ara-slate-700 mb-2">{{ $plan['nombre'] }}</h3>

                            <p class="text-ara-slate-400 text-sm leading-relaxed mb-4">{{ $plan['resumen'] }}</p>

                            <p class="text-ara-slate-500 text-sm">
                                <span class="text-2xl font-bold text-ara-blue">{{ $plan['precio_mensual'] }}</span>/mes
                                <span class="block text-ara-slate-400 mt-1">
                                    o {{ $plan['precio_anual'] }} al año · entrega en {{ $plan['entrega'] }}
                                </span>
                            </p>
                        </div>
                    @endforeach
                </div>

                <div class="mt-10 reveal">
                    <a href="{{ $urlServicio }}" class="ara-btn ara-btn-primary ara-btn-lg">
                        Ver los planes del servicio
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </a>
                </div>
            </div>
        </section>

        {{-- ===================== CTA ===================== --}}
        <x-v2.cta-section
            title="¿Quieres una plantilla con tu marca?"
            subtitle="Nos dices el rubro, la plantilla que te gustó y qué quieres cambiar: publicamos tu sitio con tu dominio."
            buttonText="Hablar con un Especialista"
        />
    </div>

    @include('aracode.components.v2.footer')
@endsection
