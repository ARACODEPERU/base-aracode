{{--
    Encabezado de las páginas internas.

    Banda azul marino que hace de fondo para la cabecera transparente y presenta
    el título de la página.

    Variables: $etiqueta (opcional), $titulo, $texto (opcional).
--}}
<section class="kf-surface-dark relative overflow-hidden pt-28 pb-14 sm:pt-32 lg:pb-16">
    <div class="kf-grid-lines absolute inset-0" aria-hidden="true"></div>
    <div class="kf-halo -right-40 -top-40 h-[24rem] w-[24rem]" aria-hidden="true"></div>

    <div class="kf-container relative">
        <div class="max-w-3xl kf-reveal">
            @isset($etiqueta)
                <span class="kf-badge">{{ $etiqueta }}</span>
            @endisset

            <h1 class="kf-h1 {{ isset($etiqueta) ? 'mt-6' : '' }}">{{ $titulo }}</h1>

            @isset($texto)
                <p class="kf-lead kf-copy mt-5">{{ $texto }}</p>
            @endisset
        </div>
    </div>
</section>
