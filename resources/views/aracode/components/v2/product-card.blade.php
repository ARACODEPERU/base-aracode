{{--
    Tarjeta de producto del catálogo (/soluciones y home)
    ---------------------------------------------------------------------------
    La imagen diseñada del producto es lo que se ve primero: va arriba, entera,
    con su proporción reservada por CSS (sin saltos al cargar) y sin ningún velo
    encima. Debajo, sobre el navy de la tarjeta, va la información escaneable:
    categoría, nombre, prestaciones y el enlace.

    Antes la imagen era el fondo de TODA la tarjeta y dos degradados navy la
    cubrían para que el texto se leyera: el diseño apenas se apreciaba. Por eso
    la frase corta de apoyo ya no se pinta —repetía el nombre, las prestaciones
    y lo que la propia imagen comunica— y sobrevive como texto alternativo de la
    imagen (ver abajo), así que no se pierde ni para lectores de pantalla ni para
    los buscadores.

    Propiedades:
    - `image`: URL de la imagen diseñada del producto. La proporción la fija
      `.ara-product-card` en webpage-v2.css (16/9; 3/2 en móvil).
    - `description`: frase corta del producto. No se pinta: es el texto
      alternativo de la imagen cuando no se pasa `imageAlt`.
    - `imageAlt`: texto alternativo explícito, para cuando el diseño de la
      imagen necesite una descripción distinta de la frase comercial.
    - `nuevaPestana`: el destino es el sitio del producto (otra web). Se avisa
      con texto solo para lectores de pantalla: quien navega con lector no
      debería descubrir que cambió de pestaña al perder la página anterior.
--}}
@props([
    'title',
    'description' => null,
    'imageAlt' => null,
    'href' => '#',
    'image' => null,
    'badge' => null,
    'features' => [],
    'delay' => 0,
    'nuevaPestana' => false,
    'etiqueta' => 'Conocer Más',
])

@php
    $textoAlternativo = $imageAlt ?? $description ?? $title;
@endphp

<article class="ara-product-card reveal reveal-delay-{{ $delay }}">
    @if ($image)
        <div class="ara-product-media">
            <img src="{{ $image }}" alt="{{ $textoAlternativo }}" loading="lazy" decoding="async">

            @if ($badge)
                <span class="ara-product-badge">{{ $badge }}</span>
            @endif
        </div>
    @endif

    <div class="ara-product-content">
        {{-- Sin imagen no hay dónde superponer la categoría: se muestra como
             distintivo normal, sobre el fondo de la tarjeta. --}}
        @if ($badge && ! $image)
            <span class="ara-badge ara-badge-green mb-3 inline-block">{{ $badge }}</span>
        @endif

        <h3 class="ara-product-title">{{ $title }}</h3>

        @if (count($features) > 0)
            <ul class="ara-product-features">
                @foreach ($features as $feature)
                    <li>
                        <svg fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span>{{ $feature }}</span>
                    </li>
                @endforeach
            </ul>
        @endif

        <a href="{{ $href }}" class="ara-btn ara-btn-primary ara-product-cta"
           @if ($nuevaPestana) target="_blank" rel="noopener" @endif>
            {{ $etiqueta }}
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
            </svg>
            @if ($nuevaPestana)
                <span class="sr-only">(se abre en una pestaña nueva)</span>
            @endif
        </a>
    </div>
</article>
