{{--
    Tarjeta de plantilla del catálogo
    ---------------------------------------------------------------------------
    El bloque de arriba es la captura de la plantilla (16/10, la proporción en
    que se preparan) y el de abajo la información que se compara de un vistazo:
    formato (landing o sitio web, con su hashtag), nombre, resumen, etiquetas y
    los dos accesos (ficha y demostración).

    El formato va primero a propósito: una landing y un sitio web son planes
    distintos, así que el visitante tiene que reconocer de un vistazo cuál va con
    lo que quiere contratar antes de leer nada más. El tooltip lleva la frase que
    explica ese formato (config/plantillas.php, `tipos`).

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

        <span class="plt-card__etiqueta">{{ $plantilla['categoria_nombre'] }}</span>

        @if ($plantilla['destacada'])
            <span class="plt-card__destacada">Destacada</span>
        @endif
    </div>

    <div class="plt-card__cuerpo">
        @if ($plantilla['tipo_hashtag'] !== '')
            <div class="plt-card__formato">
                <span class="plt-tipo" title="{{ $plantilla['tipo_descripcion'] }}">{{ $plantilla['tipo_hashtag'] }}</span>
            </div>
        @endif

        <h3 class="plt-card__nombre">
            <a href="{{ $urlFicha }}">{{ $plantilla['nombre'] }}</a>
        </h3>

        <p class="plt-card__resumen">{{ $plantilla['resumen'] }}</p>

        @if (count($plantilla['etiquetas']) > 0)
            <ul class="plt-card__etiquetas">
                @foreach (array_slice($plantilla['etiquetas'], 0, 3) as $etiqueta)
                    <li>{{ $etiqueta }}</li>
                @endforeach
            </ul>
        @endif

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
