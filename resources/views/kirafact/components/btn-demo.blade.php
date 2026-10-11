{{--
    Botón «Solicitar una demo».

    Es la acción de conversión del sitio y el único sitio donde se decide su
    texto y su destino: el texto sale de config('kirafact.demo.boton') y la URL
    se arma con el número real de 'contacto' y el mensaje de
    'mensajes.demo_whatsapp'. Todas las apariciones (cabecera, menú móvil,
    hero, sección de demo, cierre y pie) pasan por aquí, así que no hay copias
    que se puedan desincronizar.

    Es un enlace real a WhatsApp: funciona sin backend y sin JavaScript, y no
    pide ninguna credencial (esta web no tiene portal de clientes).

    Variables:
    - $class: clases del botón (por defecto 'kf-btn-primary').
    - $texto: texto del botón (por defecto el de config).
--}}
@php
    $texto = $texto ?? config('kirafact.demo.boton');
    $class = $class ?? 'kf-btn-primary';
    $url = config('kirafact.contacto.whatsapp_url').'?text='.rawurlencode(config('kirafact.mensajes.demo_whatsapp'));
@endphp

<a href="{{ $url }}" target="_blank" rel="noopener" class="kf-btn {{ $class }}">
    @include('kirafact.components.icon', ['icono' => 'whatsapp'])
    <span>{{ $texto }}</span>
    <span class="sr-only">(continúa la conversación en WhatsApp, en una pestaña nueva)</span>
</a>
