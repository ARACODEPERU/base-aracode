{{--
    Iconos lineales del sitio KIRAFACT.

    Una sola familia visual (trazo de 1.7 px, esquinas redondeadas, rejilla 24x24)
    para no mezclar estilos. Se dibujan en línea: no se cargan bibliotecas de
    iconos solo por decoración.

    Uso:  @include('kirafact.components.icon', ['icono' => 'sunat', 'iconoClase' => 'h-5 w-5'])

    Los parámetros se llaman 'icono' e 'iconoClase' a propósito: con nombres
    genéricos como 'name' o 'class' el parcial heredaba esas variables del
    archivo que lo incluye y el icono acababa con las clases del botón.

    Los iconos son decorativos: el texto que los acompaña siempre está presente,
    por eso van con aria-hidden.
--}}
@php
    $icono = $icono ?? 'check';
    $iconoClase = $iconoClase ?? 'h-5 w-5';

    $trazo = [
        'comprobante' => [
            '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/>',
            '<path d="M14 3v5h5"/>',
            '<path d="M9 13h6M9 17h4"/>',
        ],
        'documento' => [
            '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/>',
            '<path d="M14 3v5h5"/>',
            '<path d="m9 14 1.8 1.8L14.6 12"/>',
        ],
        'sunat' => [
            '<path d="M12 21s7-3.6 7-9.2V6l-7-3-7 3v5.8C5 17.4 12 21 12 21z"/>',
            '<path d="m9 12 2 2 4-4"/>',
        ],
        'reportes' => [
            '<path d="M4 4v16h16"/>',
            '<path d="M8 16v-4M12 16V8M16 16v-6"/>',
        ],
        'usuarios' => [
            '<path d="M16 20v-1.6a3.4 3.4 0 0 0-3.4-3.4H8.4A3.4 3.4 0 0 0 5 18.4V20"/>',
            '<path d="M10.5 12a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"/>',
            '<path d="M19 20v-1.6a3.4 3.4 0 0 0-2.6-3.3"/>',
            '<path d="M15.5 5.3a3.4 3.4 0 0 1 0 6.4"/>',
        ],
        'respaldo' => [
            '<path d="M3.5 12a8.5 8.5 0 1 0 2.9-6.4"/>',
            '<path d="M3.5 4v4.5H8"/>',
            '<path d="M12 8v4.4l3.3 1.9"/>',
        ],
        'inventario' => [
            '<path d="m12 3 8.5 4.6L12 12.2 3.5 7.6z"/>',
            '<path d="M3.5 7.6v8.8L12 21l8.5-4.6V7.6"/>',
            '<path d="M12 12.2V21"/>',
        ],
        'carrito' => [
            '<path d="M3 4h2.2l2.3 10.4a1.8 1.8 0 0 0 1.8 1.4h7.9a1.8 1.8 0 0 0 1.8-1.4L20.5 7H6"/>',
            '<path d="M10 20h.01M17 20h.01"/>',
        ],
        'punto-venta' => [
            '<path d="M3 5.5h18v10H3z"/>',
            '<path d="M8 20h8M12 15.5V20"/>',
        ],
        'cotizacion' => [
            '<path d="M5 4.5h14v15l-3-1.6-2 1.6-2-1.6-2 1.6-3-1.6z"/>',
            '<path d="M9 9h6M9 12.5h4"/>',
        ],
        'rentabilidad' => [
            '<path d="M3 17.5 9.5 11l3.5 3.5L21 6.5"/>',
            '<path d="M15.5 6.5H21V12"/>',
        ],
        'orden' => [
            '<path d="M4 4.5h6v6H4zM14 4.5h6v6h-6zM4 13.5h6v6H4zM14 13.5h6v6h-6z"/>',
        ],
        'flujo' => [
            '<path d="M3 5.5h6v4H3zM15 14.5h6v4h-6z"/>',
            '<path d="M9 7.5h3.2a2 2 0 0 1 2 2v3a2 2 0 0 0 2 2"/>',
        ],
        'central' => [
            '<path d="m12 3 8.5 4.5L12 12 3.5 7.5z"/>',
            '<path d="m3.5 12 8.5 4.5 8.5-4.5"/>',
            '<path d="m3.5 16.5 8.5 4.5 8.5-4.5"/>',
        ],
        'visibilidad' => [
            '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/>',
            '<path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>',
        ],
        'check' => ['<path d="m5 12.8 4.4 4.4L19 6.4"/>'],
        'check-circle' => [
            '<path d="M21 12a9 9 0 1 1-3.4-7"/>',
            '<path d="m8.5 12 2.4 2.4L21 4.6"/>',
        ],
        'menu' => ['<path d="M4 7h16M4 12h16M4 17h16"/>'],
        'cerrar' => ['<path d="M6 6l12 12M18 6 6 18"/>'],
        'flecha' => ['<path d="M4.5 12h14"/><path d="m13 6.5 6 5.5-6 5.5"/>'],
        'sol' => [
            '<circle cx="12" cy="12" r="4"/>',
            '<path d="M12 2.6v2.2M12 19.2v2.2M2.6 12h2.2M19.2 12h2.2"/>',
            '<path d="m5.5 5.5 1.6 1.6M16.9 16.9l1.6 1.6M18.5 5.5l-1.6 1.6M7.1 16.9l-1.6 1.6"/>',
        ],
        'luna' => [
            '<path d="M20.5 14.7A8.6 8.6 0 0 1 9.3 3.5a8.6 8.6 0 1 0 11.2 11.2z"/>',
        ],
        'aviso' => [
            '<path d="M12 4.5 21 19.5H3z"/>',
            '<path d="M12 10v4M12 17h.01"/>',
        ],
        'correo' => [
            '<path d="M3.5 6h17v12h-17z"/>',
            '<path d="m3.5 7 8.5 6 8.5-6"/>',
        ],
        'ubicacion' => [
            '<path d="M19 10.5c0 5.5-7 11-7 11s-7-5.5-7-11a7 7 0 0 1 14 0z"/>',
            '<path d="M12 13a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/>',
        ],
        'externo' => [
            '<path d="M14 4h6v6"/>',
            '<path d="M20 4 11 13"/>',
            '<path d="M18 14v5a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 4 19V8a1.5 1.5 0 0 1 1.5-1.5H10"/>',
        ],
        'candado' => [
            '<path d="M5.5 10.5h13V20h-13z"/>',
            '<path d="M8.5 10.5V8a3.5 3.5 0 1 1 7 0v2.5"/>',
        ],
    ];
@endphp

@if ($icono === 'whatsapp')
    {{-- Marca de WhatsApp (relleno): identifica el canal de contacto --}}
    <svg class="{{ $iconoClase }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">
        <path d="M12.04 2C6.6 2 2.2 6.4 2.2 11.84c0 1.74.46 3.44 1.32 4.94L2 22l5.36-1.4a9.9 9.9 0 0 0 4.68 1.19h.01c5.43 0 9.84-4.4 9.84-9.84C21.89 6.4 17.48 2 12.04 2Zm5.75 14.06c-.24.68-1.4 1.29-1.94 1.34-.54.06-1.03.15-2.98-.65-2.35-.96-3.83-3.4-3.95-3.56-.11-.16-.94-1.28-.94-2.44 0-1.16.6-1.73.82-1.96.21-.24.47-.29.63-.29l.45.01c.14 0 .34-.05.53.4.2.48.66 1.66.72 1.78.05.11.09.25.01.4-.08.16-.36.52-.5.66-.11.12-.24.25-.1.5.14.24.62 1.03 1.34 1.67.92.82 1.7 1.08 1.94 1.2.24.13.38.11.53-.06.14-.16.6-.7.76-.94.16-.24.32-.2.53-.12.22.08 1.38.65 1.62.77.24.12.4.18.45.28.06.1.06.6-.18 1.28Z"/>
    </svg>
@else
    <svg class="{{ $iconoClase }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
        {!! implode('', $trazo[$icono] ?? $trazo['check']) !!}
    </svg>
@endif
