{{--
    Presentación del producto: qué es KIRAFACT.

    Fondo blanco y composición de dos columnas: el texto a la izquierda y una
    ficha con los datos verificables del producto a la derecha. Sin párrafos
    largos ni afirmaciones que no se puedan sostener.
--}}
@php
    $producto = config('kirafact.producto');
@endphp

<section id="producto" class="kf-surface-light">
    <div class="kf-container kf-section">
        <div class="grid gap-12 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)] lg:items-start lg:gap-16">
            {{-- Texto --}}
            <div class="kf-reveal">
                <span class="kf-badge">{{ $producto['etiqueta'] }}</span>
                <h2 class="kf-h2 mt-5">{{ $producto['titulo'] }}</h2>

                <div class="kf-lead kf-copy mt-5 space-y-4">
                    @foreach ($producto['parrafos'] as $parrafo)
                        <p>{{ $parrafo }}</p>
                    @endforeach
                </div>

                <a href="#funcionalidades" class="kf-link mt-7 inline-flex items-center gap-2">
                    <span>Ver las funcionalidades</span>
                    @include('kirafact.components.icon', ['icono' => 'flecha', 'iconoClase' => 'h-4 w-4'])
                </a>
            </div>

            {{-- Ficha del producto --}}
            <div class="kf-card kf-reveal">
                <div class="flex items-center gap-3">
                    @include('kirafact.components.icon', ['icono' => 'comprobante'])
                    <span class="kf-h3">Ficha del producto</span>
                </div>

                <dl class="mt-6 divide-y" style="border-color: var(--kf-surface-line)">
                    @foreach ($producto['ficha'] as $fila)
                        <div class="flex flex-col gap-1 py-4 first:pt-0 last:pb-0 sm:flex-row sm:items-baseline sm:gap-6">
                            <dt class="kf-slate w-40 shrink-0 text-xs font-bold uppercase tracking-wide">
                                {{ $fila['dato'] }}
                            </dt>
                            <dd class="text-sm font-medium">{{ $fila['valor'] }}</dd>
                        </div>
                    @endforeach
                </dl>

                <p class="kf-slate mt-6 text-xs leading-relaxed">
                    Esta ficha describe el alcance del producto tal como se comunica hoy.
                    Los módulos que todavía están en revisión no se publican aquí.
                </p>
            </div>
        </div>
    </div>
</section>
