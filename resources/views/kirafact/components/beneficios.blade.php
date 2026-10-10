{{--
    Beneficios.

    Composición editorial: encabezado y acción a la izquierda, beneficios en
    filas a la derecha. Sin porcentajes, testimonios, clientes ni casos de
    éxito: solo resultados esperados descritos con palabras.
--}}
@php
    $beneficios = config('kirafact.beneficios');
@endphp

<section id="beneficios" class="kf-surface-light">
    <div class="kf-container kf-section">
        <div class="grid gap-12 lg:grid-cols-[0.85fr_1.15fr] lg:gap-16">
            {{-- Encabezado --}}
            <div class="kf-reveal lg:sticky lg:top-28 lg:self-start">
                <span class="kf-badge">Beneficios</span>
                <h2 class="kf-h2 mt-5">Lo que cambia en el día a día</h2>
                <p class="kf-lead kf-copy mt-5">
                    Facturar y ordenar la gestión del negocio son tareas que se repiten todos los
                    días. KIRAFACT las concentra para que la información quede disponible cuando
                    la necesites.
                </p>
                <a href="{{ route('kirafact.contacto') }}" class="kf-link mt-7 inline-flex items-center gap-2">
                    <span>Conversar con el equipo</span>
                    @include('kirafact.components.icon', ['icono' => 'flecha', 'iconoClase' => 'h-4 w-4'])
                </a>
            </div>

            {{-- Beneficios --}}
            <div>
                @foreach ($beneficios as $indice => $beneficio)
                    <div class="kf-benefit kf-reveal">
                        <div class="flex items-center gap-3 lg:block">
                            <span class="kf-icon-box">
                                @include('kirafact.components.icon', ['icono' => $beneficio['icono'], 'iconoClase' => 'h-6 w-6'])
                            </span>
                            {{-- El número se enciende cuando la fila pasa por el centro
                                 de la pantalla (animación ligada al scroll) --}}
                            <span class="kf-benefit-number text-xs font-bold tabular-nums lg:mt-3 lg:block">
                                {{ sprintf('%02d', $indice + 1) }}
                            </span>
                        </div>

                        <div>
                            <h3 class="kf-h3">{{ $beneficio['titulo'] }}</h3>
                            <p class="kf-slate mt-2 max-w-2xl text-sm leading-relaxed">{{ $beneficio['texto'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
