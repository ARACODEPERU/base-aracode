{{--
    Asistente virtual del aula. El bocadillo cambia de frase con los botones
    (o con anime.js) y el botón verde lo lee en voz alta cuando el navegador
    tiene síntesis de voz.
--}}
<aside class="aula-mascot" aria-label="Zorrito, el asistente del aula">
    <p class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-[0.16em] text-cole-emerald">
        <span class="h-1.5 w-1.5 rounded-full bg-cole-emerald"></span>
        Tu amigo Zorrito
    </p>

    <div id="mascotaBubble" class="aula-bubble mt-2" role="status" aria-live="polite"
         data-messages='@json($messages)'>
        {{ $messages[0] ?? '¡Hola! Estoy feliz de verte por aquí.' }}
    </div>

    <div class="aula-mascot__stage">
        <span class="aula-mascot__halo"></span>
        @include('colegio.components.aula.fox', ['class' => 'aula-mascot__fox'])
    </div>

    <div class="aula-mascot__controls">
        <button type="button" id="btnMascotaPlay" class="aula-round-btn aula-round-btn--green"
                aria-label="Escuchar el mensaje del zorrito" title="Escuchar">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5 6.5 9H3v6h3.5L11 19z"/>
                <path stroke-linecap="round" d="M15 9.2a4 4 0 010 5.6"/>
            </svg>
        </button>

        <button type="button" id="btnMascotaPause" class="aula-round-btn aula-round-btn--blue"
                aria-label="Pausar la voz" title="Pausar">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <rect x="7" y="5" width="4" height="14" rx="1.6"/>
                <rect x="13" y="5" width="4" height="14" rx="1.6"/>
            </svg>
        </button>

        <button type="button" id="btnMascotaNext" class="aula-round-btn aula-round-btn--lilac"
                aria-label="Ver otro mensaje" title="Otro mensaje">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 12h14M13 6l6 6-6 6"/>
            </svg>
        </button>
    </div>

    <p class="aula-mascot__hint">Pulsa los botones para escucharme</p>
</aside>
