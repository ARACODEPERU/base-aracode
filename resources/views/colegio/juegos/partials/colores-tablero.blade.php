{{--
    Panel de «Colores y formas»: cabecera del nivel, progreso, escenario
    (lo llena public/themes/colegio/js/juegos/colores-formas.js según la
    mecánica), controles y celebración.
--}}
<section class="juego-panel juego-panel--formas" aria-labelledby="formasTableroTitle">
    <div class="juego-panel__head">
        <div>
            <p class="juego-panel__eyebrow">
                Nivel <span data-level-number>1</span>
                <span aria-hidden="true">·</span>
                <span data-activity-label>Actividad 1 de 4</span>
            </p>
            <h2 id="formasTableroTitle" class="juego-panel__title" data-level-name>Mis primeros colores</h2>
            <p class="juego-panel__figure" data-level-focus>Rojo, azul y amarillo</p>
        </div>

        <span class="formas-badge" data-mechanic>Colores</span>
    </div>

    <div class="juego-progreso" role="progressbar" aria-label="Progreso del nivel"
         aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" data-progress-bar>
        <span class="juego-progreso__fill" data-progress-fill></span>
    </div>

    <div class="juego-escenario">
        {{-- Aquí se dibuja la actividad actual --}}
        <div class="formas-stage" data-stage inert></div>

        {{-- Portada: presentación antes de empezar --}}
        <div class="juego-portada" data-intro>
            @include('colegio.components.aula.fox', ['class' => 'juego-portada__fox'])

            <p class="juego-portada__eyebrow">Nivel <span data-level-number>1</span></p>
            <h3 class="juego-portada__title">¡Vamos a jugar con colores y formas!</h3>
            <p class="juego-portada__text" data-level-hint>
                Mira el color grande y toca la tarjeta que tiene el mismo color.
            </p>

            <ul class="juego-pasos">
                <li>
                    <span class="juego-pasos__num">1</span>
                    Mira el color o la figura con calma.
                </li>
                <li>
                    <span class="juego-pasos__num">2</span>
                    Toca la respuesta o lleva la figura a su caja.
                </li>
                <li>
                    <span class="juego-pasos__num">3</span>
                    Si algo no sale, lo intentas otra vez.
                </li>
            </ul>

            <button type="button" class="juego-btn juego-btn--primary" data-start>
                ¡Vamos a jugar!
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h12M12 6l6 6-6 6"/>
                </svg>
            </button>
        </div>

        @include('colegio.juegos.partials.colores-victoria')
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
