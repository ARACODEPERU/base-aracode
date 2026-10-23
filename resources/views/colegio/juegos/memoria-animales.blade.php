@extends('colegio.layouts.base')

@section('title', 'Memoria de animales · Juegos del aula · ' . $portal['school']['name'])
@section('description', 'Juego con fotografías y sonidos reales de animales: reconoce quién hace cada sonido y encuentra las parejas de memoria, en diez niveles para el aula de Inicial.')
@section('body_class', 'font-body')

@section('content')
    <div class="aula juego-animales" data-juego="memoria-animales">
        @include('colegio.components.aula.scene')
        @include('colegio.inicial.partials.topbar')

        <main class="aula-main">
            <a href="{{ route('colegio.inicial.games') }}" class="aula-back">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5M11 6l-6 6 6 6"/>
                </svg>
                Volver a los juegos
            </a>

            <section class="aula-hero aula-hero--juego aula-pop mt-4" data-listen>
                <div>
                    <p class="aula-eyebrow">Zona de juegos</p>
                    <h1 class="aula-title">
                        Memoria de animales
                        <span aria-hidden="true">🎧</span>
                    </h1>
                    <p class="aula-subtitle">
                        Escucha sonidos reales, reconoce animales y encuentra parejas.
                        Diez niveles con fotografías reales, sin prisa y sin perder.
                    </p>
                </div>

                <div class="aula-hero__stats">
                    <div class="aula-stat">
                        <span class="aula-stat__value">
                            <span data-activity-current>1</span><span aria-hidden="true">/</span><span data-activity-total>4</span>
                        </span>
                        <span class="aula-stat__label">Actividad</span>
                    </div>
                    <div class="aula-stat">
                        <span class="aula-stat__value" data-level-number>1</span>
                        <span class="aula-stat__label">Nivel</span>
                    </div>
                    <div class="aula-stat">
                        <span class="aula-stat__value">15</span>
                        <span class="aula-stat__label">Animales</span>
                    </div>
                </div>
            </section>

            <div class="juego-layout">
                @include('colegio.juegos.partials.animales-tablero')

                <aside class="juego-side">
                    <section class="juego-card" aria-labelledby="animalesComoTitle">
                        <h2 id="animalesComoTitle" class="juego-card__title">Cómo se juega</h2>
                        <ul class="juego-pasos mt-2">
                            <li>
                                <span class="juego-pasos__num">1</span>
                                Toca el botón grande para escuchar al animal.
                            </li>
                            <li>
                                <span class="juego-pasos__num">2</span>
                                Busca su fotografía entre las opciones.
                            </li>
                            <li>
                                <span class="juego-pasos__num">3</span>
                                En las parejas, voltea dos fotos iguales.
                            </li>
                        </ul>
                        <p class="juego-card__text">
                            Puedes repetir el sonido todas las veces que quieras. Un error no quita nada: siempre puedes intentarlo otra vez.
                        </p>
                    </section>

                    <section class="juego-card" aria-labelledby="animalesNivelesTitle">
                        <h2 id="animalesNivelesTitle" class="juego-card__title">Niveles</h2>
                        <p class="juego-card__text">Diez niveles, de los sonidos de casa al reto final. Puedes jugar el que quieras.</p>
                        <div class="juego-niveles" data-levels></div>
                    </section>

                    <section class="juego-card" aria-labelledby="animalesCreditosTitle">
                        <h2 id="animalesCreditosTitle" class="juego-card__title">Fotos y sonidos reales</h2>
                        <p class="juego-card__text">
                            Las fotografías y los sonidos provienen de Wikimedia Commons y se guardan en el colegio: la partida no depende de internet.
                        </p>
                        <button type="button" class="juego-btn juego-btn--ghost mt-3 w-full" data-credits-open>
                            Ver créditos y licencias
                        </button>
                    </section>

                    <div class="aula-hint">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="12" cy="12" r="8.6"/>
                            <path stroke-linecap="round" d="M12 8h.01M11 12h1v4h1"/>
                        </svg>
                        <span>Si no escuchas nada, revisa el volumen del dispositivo. También puedes pedir una pista.</span>
                    </div>
                </aside>
            </div>
        </main>

        @include('colegio.inicial.partials.dock')
        @include('colegio.inicial.partials.help-dialog')
        @include('colegio.juegos.partials.animales-creditos')
    </div>
@endsection

@push('scripts')
    <script type="application/json" id="animalesConfig">@json(['animals' => $animals])</script>
    <script src="{{ asset('themes/colegio/js/aula.js') }}"></script>
    <script src="{{ asset('themes/colegio/js/juegos/memoria-animales.js') }}"></script>
@endpush
