{{--
    Funcionalidades.

    Solo se renderizan las entradas marcadas con 'publicado' => true en
    config('kirafact.funcionalidades'). Las que siguen en revisión no se
    muestran como disponibles: se activan cambiando ese valor cuando estén
    confirmadas contra el sistema real.
--}}
@php
    $funcionalidades = collect(config('kirafact.funcionalidades'))
        ->filter(fn ($item) => $item['publicado'] ?? false)
        ->values();
@endphp

<section id="funcionalidades" class="kf-surface-mist">
    <div class="kf-container kf-section">
        <div class="max-w-3xl kf-reveal">
            <span class="kf-badge">Funcionalidades</span>
            <h2 class="kf-h2 mt-5">Lo que puedes hacer con KIRAFACT</h2>
            <p class="kf-lead kf-copy mt-5">
                Estas son las funciones que ARACODE comunica hoy para KIRAFACT. Cada una se
                trabaja dentro del mismo sistema, sin cambiar de herramienta.
            </p>
        </div>

        <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($funcionalidades as $item)
                <article class="kf-card kf-card-interactive kf-reveal">
                    <span class="kf-icon-box">
                        @include('kirafact.components.icon', ['icono' => $item['icono'], 'iconoClase' => 'h-6 w-6'])
                    </span>
                    <h3 class="kf-h3 mt-5">{{ $item['titulo'] }}</h3>
                    <p class="kf-slate mt-3 text-sm leading-relaxed">{{ $item['texto'] }}</p>
                </article>
            @endforeach
        </div>

        <p class="kf-note kf-reveal mt-10">
            Estamos revisando otros módulos antes de publicarlos aquí. Si necesitas un control
            que no aparece en esta lista, escríbenos y lo revisamos con tu caso.
        </p>
    </div>
</section>
