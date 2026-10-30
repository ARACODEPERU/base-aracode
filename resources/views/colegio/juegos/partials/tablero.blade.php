{{--
    Panel del rompecabezas: cabecera del nivel, progreso, tablero, piezas y
    controles. El tablero y la bandeja se llenan desde
    public/themes/colegio/js/juegos/rompecabezas.js.
--}}
<section class="juego-panel" aria-labelledby="juegoTableroTitle">
    <div class="juego-panel__head">
        <div>
            <p class="juego-panel__eyebrow">Nivel <span data-level-number>1</span></p>
            <h2 id="juegoTableroTitle" class="juego-panel__title" data-level-name>Mis primeras piezas</h2>
            <p class="juego-panel__figure" data-level-figure>Sol sonriente</p>
        </div>

        <img class="juego-mini-referencia" data-reference
             src="{{ asset('themes/colegio/img/juegos/sol.svg') }}"
             alt="Miniatura de la imagen por armar" width="64" height="64">
    </div>

    <div class="juego-progreso" role="progressbar" aria-label="Piezas colocadas"
         aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" data-progress-bar>
        <span class="juego-progreso__fill" data-progress-fill></span>
    </div>

    <div class="juego-escenario">
        <div class="juego-tablero" data-board aria-label="Tablero del rompecabezas"></div>

        {{-- Portada: presentación antes de empezar --}}
        <div class="juego-portada" data-intro>
            @include('colegio.components.aula.fox', ['class' => 'juego-portada__fox'])

            <p class="juego-portada__eyebrow">Nivel <span data-level-number>1</span></p>
            <h3 class="juego-portada__title">¡Vamos a jugar!</h3>
            <p class="juego-portada__text" data-level-hint>
                Arrastra las dos piezas del sol hasta su lugar. Las casillas te muestran dónde van.
            </p>

            <ul class="juego-pasos">
                <li>
                    <span class="juego-pasos__num">1</span>
                    Toca o arrastra una pieza.
                </li>
                <li>
                    <span class="juego-pasos__num">2</span>
                    Llévala a la casilla que le toca.
                </li>
                <li>
                    <span class="juego-pasos__num">3</span>
                    ¡Completa la imagen y celebra!
                </li>
            </ul>

            <button type="button" class="juego-btn juego-btn--primary" data-start>
                ¡Vamos a jugar!
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h12M12 6l6 6-6 6"/>
                </svg>
            </button>
        </div>

        @include('colegio.juegos.partials.victoria')
    </div>

    <div class="juego-bandeja-wrap">
        <div class="juego-bandeja-head">
            <p class="juego-bandeja-title">
                Piezas
                <span class="juego-bandeja-count">
                    <span data-progress-count>0</span><span aria-hidden="true">/</span><span data-progress-total>2</span>
                </span>
            </p>
            <p class="juego-bandeja-hint">Toca una pieza y luego su casilla</p>
        </div>
        <div class="juego-bandeja" data-tray aria-label="Piezas disponibles para colocar"></div>
    </div>

    <div class="juego-controles">
        <button type="button" class="juego-btn juego-btn--ghost" data-restart>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 11a8 8 0 1 0-2.3 5.7M20 5v6h-6"/>
            </svg>
            Reiniciar
        </button>

        <button type="button" class="juego-btn juego-btn--ghost" data-sound aria-pressed="true">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5 6.5 9H3v6h3.5L11 19z"/>
                <path stroke-linecap="round" d="M15 9.2a4 4 0 010 5.6M17.8 6.4a8 8 0 010 11.2"/>
            </svg>
            <span data-sound-label>Sonido: sí</span>
        </button>

        <p class="juego-mensaje" data-message role="status" aria-live="polite">Pulsa «¡Vamos a jugar!» para empezar.</p>
    </div>
</section>
