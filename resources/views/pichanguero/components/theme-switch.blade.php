{{--
    Interruptor de tema claro / oscuro.

    Muestra el icono del modo AL QUE SE VA al pulsar: con el sitio en claro se ve
    una luna (pasar a oscuro) y con el sitio en oscuro se ve un sol (volver a
    claro). No hay nada que resolver aquí en el servidor: el estado vive en el
    atributo data-tema del <html> y el CSS decide qué icono se ve. El JS solo
    cambia ese atributo y deja el texto del botón en consonancia.

    Se puede repetir en la misma página (cabecera y menú móvil): por eso no lleva
    id y el JS los trata a todos por igual.

    Sin JavaScript no haría nada, así que se oculta con .no-js.
--}}
<button type="button"
        class="pg-theme-switch inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-pg-line text-white transition-colors hover:border-pg-green"
        data-tema-switch aria-label="Cambiar a modo oscuro" aria-pressed="false" title="Cambiar a modo oscuro">
    <span class="pg-theme-icon pg-theme-icon-moon" aria-hidden="true">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
             stroke-linejoin="round" viewBox="0 0 24 24" focusable="false">
            <path d="M20.5 14.7A8.6 8.6 0 0 1 9.3 3.5a8.6 8.6 0 1 0 11.2 11.2z"/>
        </svg>
    </span>
    <span class="pg-theme-icon pg-theme-icon-sun" aria-hidden="true">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
             stroke-linejoin="round" viewBox="0 0 24 24" focusable="false">
            <circle cx="12" cy="12" r="4"/>
            <path d="M12 2.6v2.2M12 19.2v2.2M2.6 12h2.2M19.2 12h2.2M5.5 5.5l1.6 1.6M16.9 16.9l1.6 1.6M18.5 5.5l-1.6 1.6M7.1 16.9l-1.6 1.6"/>
        </svg>
    </span>
</button>
