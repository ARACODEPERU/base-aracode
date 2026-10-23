@extends('colegio.layouts.base')

@section('title', 'Juegos · Aula Inicial · ' . $portal['school']['name'])
@section('description', 'Zona de juegos del aula virtual de Inicial: rompecabezas, conteo de frutas, reconocimiento de letras, colores y formas, memoria, sonidos y más actividades para aprender jugando.')
@section('body_class', 'font-body')

@section('content')
    <div class="aula">
        @include('colegio.components.aula.scene')
        @include('colegio.inicial.partials.topbar')

        <main class="aula-main">
            <a href="{{ route('colegio.inicial.dashboard') }}" class="aula-back">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5M11 6l-6 6 6 6"/>
                </svg>
                Volver a mi aula
            </a>

            <section class="aula-hero aula-pop mt-4" data-listen>
                <div>
                    <p class="aula-eyebrow">Zona de juegos</p>
                    <h1 class="aula-title">
                        Aprender jugando
                        <span aria-hidden="true">🎮</span>
                    </h1>
                    <p class="aula-subtitle">
                        Ya puedes armar el rompecabezas, contar frutas, reconocer letras, jugar con colores y formas
                        y escuchar a los animales. Los demás juegos se irán activando muy pronto.
                    </p>
                </div>

                <div class="aula-hero__stats">
                    <div class="aula-stat">
                        <span class="aula-stat__value">6</span>
                        <span class="aula-stat__label">Juegos</span>
                    </div>
                    <div class="aula-stat">
                        <span class="aula-stat__value">3-5</span>
                        <span class="aula-stat__label">Años</span>
                    </div>
                    <div class="aula-stat">
                        <span class="aula-stat__value">5</span>
                        <span class="aula-stat__label">Disponibles</span>
                    </div>
                </div>
            </section>

            <div class="aula-layout">
                <section aria-labelledby="juegosTitle">
                    <h2 id="juegosTitle" class="aula-section-title">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.4 7.5h9.2a5 5 0 0 1 4.9 4l.5 3.4a3 3 0 0 1-5.4 2.3l-1.2-1.7H8.6l-1.2 1.7a3 3 0 0 1-5.4-2.3l.5-3.4a5 5 0 0 1 4.9-4z"/>
                        </svg>
                        Juegos del aula
                    </h2>

                    <div class="game-grid">
                        {{-- Memoria de animales: quinto juego disponible --}}
                        <a href="{{ route('colegio.inicial.memory') }}" class="game-card game-card--orange game-card--link">
                            <span class="game-card__art game-card__art--photo" aria-hidden="true">
                                <img src="{{ asset('themes/colegio/img/juegos/animales/perro.jpg') }}"
                                     alt="" loading="lazy" decoding="async">
                            </span>
                            <p class="game-card__name">Memoria de animales</p>
                            <span class="game-card__meta">10 niveles · sonidos y parejas</span>
                            <span class="game-card__play">
                                Jugar
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h12M12 6l6 6-6 6"/>
                                </svg>
                            </span>
                        </a>

                        {{-- Contando frutas: segundo juego disponible --}}
                        <a href="{{ route('colegio.inicial.fruits') }}" class="game-card game-card--blue game-card--link">
                            <span class="game-card__art" aria-hidden="true">
                                <svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M21 24c-4-4-11-3-13 3-2 6 3 15 7 16 2 1 4-1 6-1s4 2 6 1c4-1 9-10 7-16-2-6-9-7-13-3z" fill="#FF7A6B"/>
                                    <path d="M21 24c0-3 1-5 3-6" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round"/>
                                    <circle cx="42" cy="37" r="11" fill="#FFC15C"/>
                                    <path d="M42 26c2-4 6-5 9-4-1 4-5 6-9 5z" fill="#FFFFFF"/>
                                    <circle cx="45" cy="16" r="9" fill="#FFFFFF"/>
                                    <text x="45" y="20" text-anchor="middle" font-family="Fredoka, Nunito, sans-serif" font-size="12" font-weight="700" fill="currentColor">3</text>
                                </svg>
                            </span>
                            <p class="game-card__name">Contando frutas</p>
                            <span class="game-card__meta">10 niveles · del 1 al 10</span>
                            <span class="game-card__play">
                                Jugar
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h12M12 6l6 6-6 6"/>
                                </svg>
                            </span>
                        </a>

                        {{-- Sonidos --}}
                        <div class="game-card game-card--lilac">
                            <span class="game-card__art" aria-hidden="true">
                                <svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M24 40V20l17-4v20" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <circle cx="20" cy="42" r="6" fill="currentColor"/>
                                    <circle cx="37" cy="38" r="6" fill="currentColor"/>
                                    <path d="M46 24c4 2 6 5 6 9" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" opacity=".6"/>
                                </svg>
                            </span>
                            <p class="game-card__name">Sonidos de la granja</p>
                            <span class="game-card__soon">Próximamente</span>
                        </div>

                        {{-- Rompecabezas: primer juego disponible --}}
                        <a href="{{ route('colegio.inicial.puzzle') }}" class="game-card game-card--green game-card--link">
                            <span class="game-card__art" aria-hidden="true">
                                <svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M24 11h15a4 4 0 0 1 4 4v9h5a4 4 0 0 1 0 8h-5v13a4 4 0 0 1-4 4H26v-6a4 4 0 1 0-8 0v6h-6a4 4 0 0 1-4-4V30h5a4 4 0 0 0 0-8h-5v-7a4 4 0 0 1 4-4h12z" fill="currentColor" opacity=".55"/>
                                </svg>
                            </span>
                            <p class="game-card__name">Rompecabezas</p>
                            <span class="game-card__meta">4 niveles · desde 2 piezas</span>
                            <span class="game-card__play">
                                Jugar
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h12M12 6l6 6-6 6"/>
                                </svg>
                            </span>
                        </a>

                        {{-- Colores y formas: cuarto juego disponible --}}
                        <a href="{{ route('colegio.inicial.colors') }}" class="game-card game-card--yellow game-card--link">
                            <span class="game-card__art" aria-hidden="true">
                                <svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="19" cy="22" r="12" fill="#FF8A65"/>
                                    <rect x="31" y="30" width="20" height="20" rx="5" fill="#6CC4F5"/>
                                    <path d="M23 53 29.5 41 36 53z" fill="#FFD15C"/>
                                    <path d="m45 11 3.2 6.6 7.3 1-5.2 5.1 1.2 7.2-6.5-3.4-6.5 3.4 1.2-7.2-5.2-5.1 7.3-1z" fill="#86DC72"/>
                                </svg>
                            </span>
                            <p class="game-card__name">Colores y formas</p>
                            <span class="game-card__meta">10 niveles · colores y figuras</span>
                            <span class="game-card__play">
                                Jugar
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h12M12 6l6 6-6 6"/>
                                </svg>
                            </span>
                        </a>

                        {{-- Letras: tercer juego disponible --}}
                        <a href="{{ route('colegio.inicial.letters') }}" class="game-card game-card--lilac game-card--link">
                            <span class="game-card__art" aria-hidden="true">
                                <svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect x="7" y="9" width="32" height="40" rx="9" fill="#FFFFFF"/>
                                    <text x="23" y="40" text-anchor="middle" font-family="Fredoka, Nunito, sans-serif" font-size="27" font-weight="700" fill="currentColor">A</text>
                                    <circle cx="44" cy="42" r="11" fill="currentColor"/>
                                    <path d="M44 36.5a2.6 2.6 0 0 0 2.6 2.6h.2v-6h-.2a2.6 2.6 0 0 0-2.6 2.6z" fill="#FFFFFF"/>
                                    <path d="M40.5 37.5a5.5 5.5 0 0 0 7 0M44 43v3M41.8 46h4.4" stroke="#FFFFFF" stroke-width="1.8" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <p class="game-card__name">Reconoce la letra</p>
                            <span class="game-card__meta">10 niveles · voz y teclado</span>
                            <span class="game-card__play">
                                Jugar
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h12M12 6l6 6-6 6"/>
                                </svg>
                            </span>
                        </a>
                    </div>

                    <p class="mt-4 text-[11px] font-semibold leading-relaxed text-cole-forest/55">
                        El rompecabezas, Contando frutas, Reconoce la letra, Colores y formas y Memoria de animales ya están listos. Los demás juegos se activarán cuando terminemos de prepararlos con el equipo del colegio.
                    </p>
                </section>

                <aside class="space-y-4">
                    @include('colegio.inicial.partials.mascot')

                    <div class="aula-hint">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="12" cy="12" r="8.6"/>
                            <path stroke-linecap="round" d="M12 8h.01M11 12h1v4h1"/>
                        </svg>
                        <span>Mientras llegan los juegos, puedes entrar a tus cursos y abrir sus sesiones.</span>
                    </div>
                </aside>
            </div>
        </main>

        @include('colegio.inicial.partials.dock')
        @include('colegio.inicial.partials.help-dialog')
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('themes/colegio/js/vendor/anime.umd.min.js') }}"></script>
    <script src="{{ asset('themes/colegio/js/aula.js') }}"></script>
@endpush
