{{--
    Marca KIRAFACT.

    Mientras no exista el archivo oficial del logotipo, aquí no se dibuja ningún
    sustituto: se reserva el espacio con una marca discreta y el nombre se
    muestra como texto. Así nunca se presenta una aproximación como si fuera el
    logotipo original.

    Variables:
    - $superficie: 'oscura' (por defecto), 'clara' o 'auto'. Decide qué versión
      del logotipo se usa cuando existan los archivos. 'auto' es para la
      cabecera, que cambia de fondo al desplazar: el CSS alterna las dos.
    - $conDescriptor: muestra el descriptor bajo el nombre (por defecto false).
--}}
@php
    $marca = config('kirafact.marca');
    $superficie = $superficie ?? 'oscura';
    $conDescriptor = $conDescriptor ?? false;
    $rutaLogo = $superficie === 'oscura' ? ($marca['logo_dark'] ?: $marca['logo']) : $marca['logo'];
    $alto = $marca['logo_alto'];
@endphp

<span class="kf-brand-logos">
    @if ($superficie === 'auto' && $marca['logo'] && $marca['logo_dark'])
        {{-- Dos versiones del mismo logotipo: el CSS muestra una u otra según el
             fondo de la cabecera. Las dimensiones van declaradas para que el
             cambio no desplace el contenido. --}}
        <img src="{{ asset($marca['logo_dark']) }}" alt="{{ $marca['nombre'] }} — {{ $marca['descriptor'] }}"
             class="kf-brand-img kf-logo-on-dark" style="height: {{ $alto }}px"
             width="{{ (int) round($alto * 5) }}" height="{{ $alto }}">
        <img src="{{ asset($marca['logo']) }}" alt="" aria-hidden="true"
             class="kf-brand-img kf-logo-on-light" style="height: {{ $alto }}px"
             width="{{ (int) round($alto * 5) }}" height="{{ $alto }}">
    @elseif ($rutaLogo)
        <img src="{{ asset($rutaLogo) }}"
             alt="{{ $marca['nombre'] }} — {{ $marca['descriptor'] }}"
             class="kf-brand-img"
             style="height: {{ $alto }}px"
             width="{{ $alto * 5 }}"
             height="{{ $alto }}">
    @else
        {{-- Hueco reservado: no es un logotipo, es el espacio del logotipo oficial --}}
        <span class="kf-slot" title="{{ $marca['slot'] }}">
            @include('kirafact.components.icon', ['icono' => 'candado', 'iconoClase' => 'h-3.5 w-3.5'])
            <span>{{ $marca['slot'] }}</span>
        </span>
    @endif
</span>

<span class="min-w-0">
    <span class="kf-brand-name block">{{ $marca['nombre'] }}</span>
    @if ($conDescriptor)
        <span class="kf-slate block text-[0.6875rem] leading-tight">{{ $marca['descriptor'] }}</span>
    @endif
</span>
