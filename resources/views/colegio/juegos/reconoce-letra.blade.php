@extends('colegio.layouts.base')

@section('title', 'Reconoce la letra · Juegos del aula · ' . $portal['school']['name'])
@section('description', 'Juego para reconocer las letras del abecedario español con la voz o el teclado, en diez niveles para el aula de Inicial.')
@section('body_class', 'font-body')

@section('content')
    <div class="aula juego-letras" data-juego="reconoce-letra">
        @include('colegio.components.aula.scene')
        @include('colegio.aula.partials.topbar')

        <main class="aula-main">
            <a href="{{ route('colegio.aula.games') }}" class="aula-back">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5M11 6l-6 6 6 6"/>
                </svg>
                Volver a los juegos
            </a>

            <section class="aula-hero aula-hero--juego aula-pop mt-4" data-listen>
                <div>
                    <p class="aula-eyebrow">Zona de juegos</p>
                    <h1 class="aula-title">
                        Reconoce la letra
                        <span aria-hidden="true">🔤</span>
                    </h1>
                    <p class="aula-subtitle">
                        Mira la letra, di su nombre o escríbela. Puedes usar el micrófono o el teclado,
                        y si algo no sale bien, lo intentas otra vez.
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
                        <span class="aula-stat__value">10</span>
                        <span class="aula-stat__label">Niveles</span>
                    </div>
                </div>
            </section>

            <div class="juego-layout">
                @include('colegio.juegos.partials.letras-tablero')

                <aside class="juego-side">
                    <section class="juego-card" aria-labelledby="letrasComoTitle">
                        <h2 id="letrasComoTitle" class="juego-card__title">Cómo se juega</h2>
                        <ul class="juego-pasos mt-2">
                            <li>
                                <span class="juego-pasos__num">1</span>
                                Mira la letra grande y piensa cómo se llama.
                            </li>
                            <li>
                                <span class="juego-pasos__num">2</span>
                                Toca «Hablar» y di su nombre, o escríbela con el teclado.
                            </li>
                            <li>
                                <span class="juego-pasos__num">3</span>
                                Si hay varias letras, diles en orden.
                            </li>
                        </ul>
                        <p class="juego-card__text">
                            Ningún error quita puntos y el teclado siempre está disponible.
                        </p>
                    </section>

                    <section class="juego-card" aria-labelledby="letrasNivelesTitle">
                        <h2 id="letrasNivelesTitle" class="juego-card__title">Niveles</h2>
                        <p class="juego-card__text">Diez niveles, desde las vocales hasta las secuencias de letras. Puedes jugar el que quieras.</p>
                        <div class="juego-niveles" data-levels></div>
                    </section>

                    <div class="aula-hint">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15a3 3 0 0 0 3-3V6a3 3 0 0 0-6 0v6a3 3 0 0 0 3 3z"/>
                            <path stroke-linecap="round" d="M6 11a6 6 0 0 0 12 0M12 17v4"/>
                        </svg>
                        <span>El micrófono pide permiso y necesita un navegador compatible. Si no está disponible, juega con el teclado.</span>
                    </div>
                </aside>
            </div>
        </main>

        @include('colegio.aula.partials.dock')
        @include('colegio.aula.partials.help-dialog')
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('themes/colegio/js/aula.js') }}"></script>
    <script src="{{ asset('themes/colegio/js/juegos/reconoce-letra.js') }}"></script>
@endpush
