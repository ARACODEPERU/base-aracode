@extends('colegio.layouts.base')

@section('title', 'Juegos · Aula Inicial · ' . $portal['school']['name'])
@section('description', 'Zona de juegos del aula virtual de Inicial: memoria, números, sonidos y más actividades para aprender jugando.')
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
                        Estamos preparando juegos cortitos para practicar números, sonidos, colores y memoria.
                        Muy pronto podrás jugarlos aquí.
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
                        <span class="aula-stat__value">0</span>
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
                        Próximos juegos
                    </h2>

                    <div class="game-grid">
                        {{-- Memoria --}}
                        <div class="game-card game-card--orange">
                            <span class="game-card__art" aria-hidden="true">
                                <svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect x="7" y="12" width="21" height="28" rx="6" fill="currentColor" opacity=".25" transform="rotate(-8 17 26)"/>
                                    <rect x="20" y="16" width="21" height="28" rx="6" fill="#FFFFFF"/>
                                    <circle cx="31" cy="28" r="2.6" fill="currentColor"/>
                                    <circle cx="75" cy="28" r="2.6" fill="currentColor" transform="translate(-44 0)"/>
                                    <path d="M26 34c3 3.4 7 3.4 10 0" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
                                    <rect x="36" y="26" width="17" height="22" rx="5" fill="currentColor" opacity=".55" transform="rotate(10 44 37)"/>
                                </svg>
                            </span>
                            <p class="game-card__name">Memoria de animales</p>
                            <span class="game-card__soon">Próximamente</span>
                        </div>

                        {{-- Números --}}
                        <div class="game-card game-card--blue">
                            <span class="game-card__art" aria-hidden="true">
                                <svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="19" cy="32" r="12" fill="#FFFFFF"/>
                                    <circle cx="42" cy="32" r="12" fill="currentColor" opacity=".55"/>
                                    <text x="19" y="38" text-anchor="middle" font-family="Fredoka, Nunito, sans-serif" font-size="15" font-weight="700" fill="currentColor">1</text>
                                    <text x="42" y="38" text-anchor="middle" font-family="Fredoka, Nunito, sans-serif" font-size="15" font-weight="700" fill="#FFFFFF">2</text>
                                </svg>
                            </span>
                            <p class="game-card__name">Contando frutas</p>
                            <span class="game-card__soon">Próximamente</span>
                        </div>

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

                        {{-- Rompecabezas --}}
                        <div class="game-card game-card--green">
                            <span class="game-card__art" aria-hidden="true">
                                <svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M24 11h15a4 4 0 0 1 4 4v9h5a4 4 0 0 1 0 8h-5v13a4 4 0 0 1-4 4H26v-6a4 4 0 1 0-8 0v6h-6a4 4 0 0 1-4-4V30h5a4 4 0 0 0 0-8h-5v-7a4 4 0 0 1 4-4h12z" fill="currentColor" opacity=".55"/>
                                </svg>
                            </span>
                            <p class="game-card__name">Rompecabezas</p>
                            <span class="game-card__soon">Próximamente</span>
                        </div>

                        {{-- Colores --}}
                        <div class="game-card game-card--yellow">
                            <span class="game-card__art" aria-hidden="true">
                                <svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M30 10c11 0 20 8 20 18 0 6-5 9-10 9h-4c-3 0-5 2-5 5 0 4 2 6 2 8H30C19 50 10 41 10 30S19 10 30 10z" fill="#FFFFFF"/>
                                    <circle cx="21" cy="24" r="4" fill="#FF8A65"/>
                                    <circle cx="31" cy="19" r="4" fill="#FFD15C"/>
                                    <circle cx="41" cy="25" r="4" fill="#6CC4F5"/>
                                    <circle cx="22" cy="36" r="4" fill="#86DC72"/>
                                </svg>
                            </span>
                            <p class="game-card__name">Colores y formas</p>
                            <span class="game-card__soon">Próximamente</span>
                        </div>

                        {{-- Trazos --}}
                        <div class="game-card game-card--blue">
                            <span class="game-card__art" aria-hidden="true">
                                <svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 44c8-2 12-10 10-16" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-dasharray="4 5"/>
                                    <rect x="30" y="12" width="12" height="30" rx="4" fill="currentColor" transform="rotate(18 36 27)"/>
                                    <path d="M46 38l-6 9 10-3z" fill="#F2C9A0" transform="rotate(18 43 42)"/>
                                </svg>
                            </span>
                            <p class="game-card__name">Traza las letras</p>
                            <span class="game-card__soon">Próximamente</span>
                        </div>
                    </div>

                    <p class="mt-4 text-[11px] font-semibold leading-relaxed text-cole-forest/55">
                        Esta es una vista previa. Los juegos se activarán cuando terminemos de prepararlos con el equipo del colegio.
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
