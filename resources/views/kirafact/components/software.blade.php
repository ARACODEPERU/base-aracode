{{--
    Sección visual del software.

    Mientras no existan capturas oficiales del sistema se muestra la composición
    referencial de components/mock.blade.php, siempre rotulada como tal.

    Cuando haya capturas reales basta con declararlas en
    config('kirafact.software.capturas'): la sección las muestra en su lugar sin
    tocar el resto de la página.
--}}
@php
    $software = config('kirafact.software');
    $capturas = array_filter($software['capturas'] ?? []);
@endphp

<section id="software" class="kf-surface-mist">
    <div class="kf-container kf-section">
        <div class="grid gap-12 lg:grid-cols-2 lg:items-center lg:gap-16">
            {{-- Recorrido --}}
            <div class="kf-reveal">
                <span class="kf-badge">{{ $software['etiqueta'] }}</span>
                <h2 class="kf-h2 mt-5">{{ $software['titulo'] }}</h2>
                <p class="kf-lead kf-copy mt-5">{{ $software['descripcion'] }}</p>

                {{-- El riel de progreso (pseudo-elementos de .kf-steps) se rellena
                     a medida que la lista cruza la pantalla --}}
                <ol class="kf-steps mt-9 space-y-6">
                    @foreach ($software['pasos'] as $indice => $paso)
                        <li class="flex gap-5">
                            <span class="kf-step-number">{{ $indice + 1 }}</span>
                            <div>
                                <h3 class="kf-h3">{{ $paso['titulo'] }}</h3>
                                <p class="kf-slate mt-2 max-w-xl text-sm leading-relaxed">{{ $paso['texto'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>

            {{-- Pantallas --}}
            <div class="kf-reveal">
                @if ($capturas)
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ($capturas as $captura)
                            <div class="aspect-[16/10] overflow-hidden rounded-2xl border border-kf-line">
                                <img src="{{ asset($captura) }}"
                                     alt="Pantalla del sistema KIRAFACT"
                                     class="kf-photo" loading="lazy">
                            </div>
                        @endforeach
                    </div>
                @else
                    @include('kirafact.components.mock', ['titulo' => 'KIRAFACT · Operación del día', 'vista' => 'operacion'])

                    <p class="kf-mock-note">
                        @include('kirafact.components.icon', ['icono' => 'aviso', 'iconoClase' => 'mt-0.5 h-4 w-4 shrink-0'])
                        <span>{{ $software['nota'] }}</span>
                    </p>
                @endif
            </div>
        </div>
    </div>
</section>
