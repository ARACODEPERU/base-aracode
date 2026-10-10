{{--
    Botón «Ingresar al sistema».

    Reutiliza la URL configurada en config('kirafact.login_url'). Mientras esa
    URL no exista, no se inventa un enlace: el botón queda como estado no
    disponible, con el aviso en el atributo title y sin redirigir a ninguna
    parte.

    Variables:
    - $class: clases del botón (por defecto 'kf-btn-primary').
    - $texto: texto del botón (por defecto el de config).
--}}
@php
    $url = config('kirafact.login_url');
    $texto = $texto ?? config('kirafact.acceso.boton');
    $class = $class ?? 'kf-btn-primary';
@endphp

@if ($url)
    <a href="{{ $url }}" target="_blank" rel="noopener" class="kf-btn {{ $class }}">
        @include('kirafact.components.icon', ['icono' => 'ingresar'])
        <span>{{ $texto }}</span>
        <span class="sr-only">(abre el sistema KIRAFACT en una pestaña nueva)</span>
    </a>
@else
    <span class="kf-btn is-disabled {{ $class }}" aria-disabled="true"
          title="{{ config('kirafact.mensajes.acceso_pendiente') }}">
        @include('kirafact.components.icon', ['icono' => 'candado'])
        <span>{{ $texto }}</span>
    </span>
@endif
