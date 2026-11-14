{{--
    Hero de páginas internas
    ---------------------------------------------------------------------------
    El mismo formato que el hero del catálogo, sin ilustración: etiqueta,
    titular con la parte final en degradado y bajada, todo centrado en el
    hero. La portada usa su propio hero.

    Props:
      etiqueta   Texto del badge superior.
      titulo     Titular, tal cual se escribe antes de la parte resaltada.
      resaltado  Parte del titular que va en degradado.
      resto      Texto que sigue al degradado, si lo hay.
      bajada     Párrafo descriptivo.
    Slots: acciones (botones) y extra (listas u otro contenido bajo la bajada).
--}}
@props([
    'etiqueta' => null,
    'titulo' => '',
    'resaltado' => '',
    'resto' => '',
    'bajada' => '',
])

<section class="ara-ph">
    <span class="ara-ph__halo" aria-hidden="true"></span>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="ara-ph__texto">
            @if ($etiqueta)
                <span class="ara-badge ara-badge-blue ara-ph__badge reveal">{{ $etiqueta }}</span>
            @endif

            <h1 class="ara-ph__titulo reveal reveal-delay-1">
                {{ $titulo }}@if ($resaltado) <span class="text-gradient">{{ $resaltado }}</span>@endif{{ $resto }}
            </h1>

            @if ($bajada)
                <p class="ara-ph__lead reveal reveal-delay-2">{{ $bajada }}</p>
            @endif

            @if (isset($acciones) && trim($acciones) !== '')
                <div class="ara-ph__acciones reveal reveal-delay-3">{{ $acciones }}</div>
            @endif

            @if (isset($extra) && trim($extra) !== '')
                <div class="ara-ph__extra reveal reveal-delay-4">{{ $extra }}</div>
            @endif
        </div>
    </div>
</section>
