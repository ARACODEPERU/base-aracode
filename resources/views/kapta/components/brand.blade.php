{{--
    Marca del sitio.

    REGLA DE LA IDENTIDAD: el logotipo oficial no se redibuja, no se recolorea
    ni se sustituye. Mientras no exista el archivo, aquí se reserva su espacio
    con un hueco claramente identificable y se escribe el nombre como texto
    normal (tipografía del sitio, no una recreación del logotipo).

    Para publicar el logotipo real basta con definir en config/kapta.php:
        'logo'      => 'themes/kapta/images/<archivo para fondos claros>'
        'logo_dark' => 'themes/kapta/images/<archivo para fondos azul marino>'

    Variables:
        $variant  'dark' (sobre azul marino) | 'light' (sobre blanco). Por defecto 'light'.
        $toggle   true cuando la cabecera puede pasar de oscuro a claro al desplazar.
--}}
@php
    $variant = $variant ?? 'light';
    $toggle = $toggle ?? false;
    $logoClaro = config('kapta.logo');
    $logoOscuro = config('kapta.logo_dark') ?: $logoClaro;
    $alto = (int) config('kapta.logo_alto', 40);
@endphp

@if ($logoClaro)
    <span class="ka-brand-logos">
        @if ($toggle)
            <img src="{{ asset($logoOscuro) }}" alt="KAPTA LMS"
                 class="ka-brand-img ka-logo-on-dark" height="{{ $alto }}" decoding="async">
            <img src="{{ asset($logoClaro) }}" alt="" aria-hidden="true"
                 class="ka-brand-img ka-logo-on-light" height="{{ $alto }}" decoding="async">
        @else
            <img src="{{ $variant === 'dark' ? $logoOscuro : $logoClaro }}" alt="KAPTA LMS"
                 class="ka-brand-img" height="{{ $alto }}" decoding="async">
        @endif
    </span>
@else
    {{--
        Hueco reservado: no es el logotipo, es su sitio.
        Va oculto a los lectores de pantalla porque no aporta información al
        navegar: el enlace de la marca ya se anuncia con su propio nombre.
    --}}
    <span class="ka-slot" aria-hidden="true"
          title="Hueco reservado para el logotipo oficial de KAPTA LMS">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                  d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M14 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <span>Logotipo oficial</span>
    </span>

    <span class="ka-brand-name">KAPTA <span class="ka-brand-sub">LMS</span></span>
@endif
