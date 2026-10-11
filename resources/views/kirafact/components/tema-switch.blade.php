{{--
    Interruptor de tema claro / oscuro.

    Muestra el icono del modo AL QUE SE VA al pulsar: en modo claro se ve una
    luna (pasar a oscuro) y en modo oscuro se ve un sol (volver a claro). No hay
    nada que resolver en el servidor: el estado vive en el atributo data-tema del
    <html> y el CSS decide qué icono se ve. El JS solo cambia ese atributo y deja
    el texto del botón en consonancia.

    Reutiliza el aspecto del botón de la cabecera (.kf-nav-toggle) para no
    introducir un control nuevo de la nada. Se puede repetir varias veces en la
    misma página (cabecera y menú móvil): por eso no lleva id y el JS los trata
    a todos por igual.

    Sin JavaScript no haría nada, así que se oculta con .no-js.
--}}
<button type="button" class="kf-nav-toggle kf-tema-switch" data-tema-switch
        aria-label="Cambiar a modo oscuro" aria-pressed="false" title="Cambiar a modo oscuro">
    <span class="kf-tema-icono kf-tema-icono-luna" aria-hidden="true">
        @include('kirafact.components.icon', ['icono' => 'luna'])
    </span>
    <span class="kf-tema-icono kf-tema-icono-sol" aria-hidden="true">
        @include('kirafact.components.icon', ['icono' => 'sol'])
    </span>
</button>
