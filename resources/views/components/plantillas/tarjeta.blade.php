{{--
    Tarjeta de plantilla del catálogo
    ---------------------------------------------------------------------------
    El bloque de arriba es la captura de la plantilla (16/10, la proporción en
    que se preparan) y el de abajo la información que se compara de un vistazo:
    el nombre, el resumen, una sola fila de etiquetas —formato, rubro y palabras
    clave— y los dos accesos (ficha y demostración).

    El nombre abre el cuerpo, pegado a la captura. Antes lo precedía una fila de
    píldoras con el formato y el rubro que lo empujaba ~40 px hacia abajo y
    dejaba dos rejillas de etiquetas en la misma tarjeta; ahora todo va en una
    sola, encima de los botones.

    El formato va primero en esa fila a propósito: una landing, un sitio web y una
    tienda en línea son planes distintos, y la etiqueta dice cuál es el de la
    ficha (`etiqueta` en config/plantillas.php). El tooltip lleva la frase que
    explica el formato completo (`tipos`).

    Mientras una plantilla no tenga captura, en su lugar se pinta un mosaico con
    el nombre: nunca un hueco gris ni una imagen rota. La ruta de la captura se
    escribe en config/plantillas.php (`imagen`).

    La demostración se abre con el abridor `/demo/{slug}`, que redirige a la
    carpeta de la demo, y en pestaña nueva: la demo es otro proyecto web con su
    propia navegación y quien compara plantillas no debería perder el catálogo.
--}}
@props(['plantilla'])

@php
    $imagen = \App\Support\CatalogoPlantillas::urlImagen($plantilla);
    $urlDemo = \App\Support\CatalogoPlantillas::urlDemo($plantilla);
    $urlFicha = route('plantillas.ficha', $plantilla['slug']);
@endphp

<article class="plt-card reveal">
    <div class="plt-card__media">
        @if ($imagen)
            <img src="{{ $imagen }}" alt="Vista previa de la plantilla {{ $plantilla['nombre'] }}"
                 loading="lazy" decoding="async">
        @else
            {{-- Sin captura todavía: se anuncia como tal, no se disfraza de imagen --}}
            <span class="plt-card__sin-captura">Captura en preparación</span>
        @endif
    </div>

    <div class="plt-card__cuerpo">
        <h3 class="plt-card__nombre">
            <a href="{{ $urlFicha }}">{{ $plantilla['nombre'] }}</a>
        </h3>

        <p class="plt-card__resumen">{{ $plantilla['resumen'] }}</p>

        {{-- Formato, rubro y palabras clave en la misma fila: al no haber otra
             rejilla de píldoras encima, el nombre arranca justo debajo de la
             captura. El formato se lee sin la almohadilla del buscador y dice
             Landing, SitioWeb o Tienda; «Destacada» tampoco va aquí: lo son siete
             de nueve fichas y el distintivo ya se nota en el orden del
             listado. --}}
        <ul class="plt-card__etiquetas">
            @if ($plantilla['tipo_etiqueta'] !== '')
                <li class="plt-tipo" title="{{ $plantilla['tipo_descripcion'] }}">{{ $plantilla['tipo_etiqueta'] }}</li>
            @endif

            <li class="plt-card__rubro">{{ $plantilla['categoria_nombre'] }}</li>

            @foreach (array_slice($plantilla['etiquetas'], 0, 3) as $etiqueta)
                <li>{{ $etiqueta }}</li>
            @endforeach
        </ul>

        <div class="plt-card__acciones">
            <a href="{{ $urlFicha }}" class="ara-btn ara-btn-primary">
                Ver ficha
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </a>

            @if ($urlDemo)
                <a href="{{ route('plantillas.demo', $plantilla['slug']) }}" class="ara-btn ara-btn-ghost"
                   target="_blank" rel="noopener">
                    Demostración
                    <span class="sr-only">(se abre en una pestaña nueva)</span>
                </a>
            @endif
        </div>
    </div>
</article>
