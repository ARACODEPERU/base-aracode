{{--
    Celebración al completar el nivel. El script decide si hay siguiente nivel
    y cambia el mensaje en el nivel 10; los botones son reales.
--}}
<div class="juego-victoria" data-victory hidden>
    @include('colegio.components.aula.fox', ['class' => 'juego-victoria__fox'])

    <p class="juego-victoria__eyebrow">¡Lo lograste!</p>
    <h3 class="juego-victoria__title" data-victory-title>¡Nivel completado!</h3>
    <p class="juego-victoria__text" data-victory-text>Reconociste todas las letras de este nivel.</p>

    <div class="juego-victoria__actions">
        <button type="button" class="juego-btn juego-btn--primary" data-next hidden>
            Siguiente nivel
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h12M12 6l6 6-6 6"/>
            </svg>
        </button>

        <button type="button" class="juego-btn juego-btn--ghost" data-replay>Jugar otra vez</button>

        <a href="{{ route('colegio.aula.games') }}" class="juego-btn juego-btn--ghost">Volver a los juegos</a>
    </div>
</div>
