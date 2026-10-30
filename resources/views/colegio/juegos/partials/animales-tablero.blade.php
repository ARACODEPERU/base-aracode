{{--
    Panel de «Memoria de animales»: cabecera del nivel, progreso, escenario
    (lo llena public/themes/colegio/js/juegos/memoria-animales.js según la
    mecánica), controles y celebración.
--}}
<section class="juego-panel juego-panel--animales" aria-labelledby="animalesTableroTitle">
    <div class="juego-panel__head">
        <div>
            <p class="juego-panel__eyebrow">
                Nivel <span data-level-number>1</span>
                <span aria-hidden="true">·</span>
                <span data-activity-label>Actividad 1 de 4</span>
            </p>
            <h2 id="animalesTableroTitle" class="juego-panel__title" data-level-name>¿Quién hace ese sonido?</h2>
            <p class="juego-panel__figure" data-level-focus>Perro y gato</p>
        </div>

        <div class="animales-badges">
            <span class="animales-badge" data-mechanic>Sonidos</span>
            <span class="animales-pares" data-pairs hidden>Parejas: 0 de 4</span>
        </div>
    </div>

    <div class="juego-progreso" role="progressbar" aria-label="Progreso del nivel"
         aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" data-progress-bar>
        <span class="juego-progreso__fill" data-progress-fill></span>
    </div>

    <div class="juego-escenario">
        {{-- Aquí se dibuja la actividad actual --}}
        <div class="animales-stage" data-stage inert></div>

        {{-- Portada: presentación antes de empezar --}}
        <div class="juego-portada animales-portada" data-intro>
            <span class="animales-portada__icono" aria-hidden="true">
                <svg viewBox="0 0 64 64" fill="none">
                    <circle cx="32" cy="32" r="30" fill="#E2F8D9"/>
                    <path d="M25 22h-5v20h5M39 22h5v20h-5" stroke="#2F7D63" stroke-width="3.4" stroke-linecap="round"/>
                    <path d="M28 20h8v24h-8z" fill="#86DC72" opacity=".45"/>
                    <path d="M15 27a6 6 0 0 0 0 10M49 27a6 6 0 0 1 0 10" stroke="#4E9E38" stroke-width="2.6" stroke-linecap="round"/>
                </svg>
            </span>

            <p class="juego-portada__eyebrow">Nivel <span data-level-number>1</span></p>
            <h3 class="juego-portada__title" data-intro-title>¡Vamos a escuchar a los animales!</h3>
            <p class="juego-portada__text" data-level-hint>
                Escucha el sonido y toca la fotografía del animal que lo hace.
            </p>

            <ul class="juego-pasos">
                <li>
                    <span class="juego-pasos__num">1</span>
                    <span data-intro-step="1">Pulsa el botón grande para escuchar.</span>
                </li>
                <li>
                    <span class="juego-pasos__num">2</span>
                    <span data-intro-step="2">Mira las fotos con calma y elige una.</span>
                </li>
                <li>
                    <span class="juego-pasos__num">3</span>
                    <span data-intro-step="3">Si algo no sale, lo intentas otra vez.</span>
                </li>
            </ul>

            <button type="button" class="juego-btn juego-btn--primary" data-start>
                ¡Vamos a jugar!
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h12M12 6l6 6-6 6"/>
                </svg>
            </button>
        </div>

        @include('colegio.juegos.partials.animales-victoria')
    </div>

    <div class="juego-controles">
        <button type="button" class="juego-btn juego-btn--ghost" data-restart>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 11a8 8 0 1 0-2.3 5.7M20 5v6h-6"/>
            </svg>
            Reiniciar
        </button>

        <button type="button" class="juego-btn juego-btn--ghost" data-stop-audio hidden>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                <rect x="7" y="7" width="10" height="10" rx="2"/>
            </svg>
            Detener sonido
        </button>

        <p class="juego-mensaje" data-message role="status" aria-live="polite">Pulsa «¡Vamos a jugar!» para empezar.</p>
    </div>
</section>
