{{--
    Acciones de un material. Recibe $material con preview_url, download_url y
    external_url ya resueltos por el controlador para las rutas de Secundaria.
    El visor es el mismo modal del aula: el botón abre el material en su sitio.
--}}
<span class="sec-material-links">
    @if (! empty($material['preview_url']) || ! empty($material['embed_html']))
        <button type="button" class="sec-link" data-material-open data-material-id="{{ $material['id'] }}">
            Ver aquí
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14 4h6v6M20 4l-8.5 8.5"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 14v4.5A1.5 1.5 0 0 1 16.5 20h-11A1.5 1.5 0 0 1 4 18.5v-11A1.5 1.5 0 0 1 5.5 6H10"/>
            </svg>
        </button>
    @endif

    @if (! empty($material['download_url']))
        <a href="{{ $material['download_url'] }}" download class="sec-link sec-link--soft">
            Descargar
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v10m0 0 4-4m-4 4-4-4"/>
                <path stroke-linecap="round" d="M5 18.5h14"/>
            </svg>
        </a>
    @endif

    @if (! empty($material['external_url']))
        <a href="{{ $material['external_url'] }}" target="_blank" rel="noopener noreferrer" class="sec-link">
            Ir al enlace
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14 4h6v6M20 4l-8.5 8.5"/>
            </svg>
        </a>
    @endif
</span>
