@extends('colegio.layouts.base')

@section('title', 'Rompecabezas · Juegos del aula · ' . $portal['school']['name'])
@section('description', 'Juego de rompecabezas del aula virtual de Inicial: arma ilustraciones con piezas grandes, en cuatro niveles de dificultad.')
@section('body_class', 'font-body')

@section('content')
    {{-- data-image-base: las ilustraciones viven en public/themes/colegio/img/juegos --}}
    <div class="aula juego-rompecabezas" data-juego="rompecabezas"
         data-image-base="{{ asset('themes/colegio/img/juegos') }}">
        @include('colegio.components.aula.scene')
        @include('colegio.aula.partials.topbar')

        <main class="aula-main">
            <a href="{{ route('colegio.aula.games') }}" class="aula-back">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5M11 6l-6 6 6 6"/>
                </svg>
                Volver a los juegos
            </a>

            {{-- En el celular el héroe se compacta: el título del juego y el
                 tablero deben quedar a la vista sin empujar el botón de inicio. --}}
            <section class="aula-hero aula-hero--juego aula-pop mt-4" data-listen>
                <div>
                    <p class="aula-eyebrow">Zona de juegos</p>
                    <h1 class="aula-title">
                        Rompecabezas
                        <span aria-hidden="true">🧩</span>
                    </h1>
                    <p class="aula-subtitle">
                        Arrastra cada pieza hasta su casilla o toca una pieza y después la casilla.
                        Si no encaja, no pasa nada: puedes intentarlo otra vez.
                    </p>
                </div>

                <div class="aula-hero__stats">
                    <div class="aula-stat">
                        <span class="aula-stat__value">
                            <span data-progress-count>0</span><span aria-hidden="true">/</span><span data-progress-total>2</span>
                        </span>
                        <span class="aula-stat__label">Piezas</span>
                    </div>
                    <div class="aula-stat">
                        <span class="aula-stat__value" data-level-number>1</span>
                        <span class="aula-stat__label">Nivel</span>
                    </div>
                    <div class="aula-stat">
                        <span class="aula-stat__value">4</span>
                        <span class="aula-stat__label">Niveles</span>
                    </div>
                </div>
            </section>

            <div class="juego-layout">
                @include('colegio.juegos.partials.tablero')

                <aside class="juego-side">
                    <section class="juego-card" aria-labelledby="juegoReferenciaTitle">
                        <h2 id="juegoReferenciaTitle" class="juego-card__title">Imagen de referencia</h2>
                        <img class="juego-referencia" data-reference
                             src="{{ asset('themes/colegio/img/juegos/sol.svg') }}"
                             alt="Imagen de referencia del rompecabezas" width="240" height="180">
                        <p class="juego-card__text">Mírala para saber qué parte de la imagen va en cada casilla.</p>
                    </section>

                    <section class="juego-card" aria-labelledby="juegoNivelesTitle">
                        <h2 id="juegoNivelesTitle" class="juego-card__title">Niveles</h2>
                        <p class="juego-card__text">Cada nivel tiene más piezas. Puedes jugar el que quieras.</p>
                        <div class="juego-niveles" data-levels></div>
                    </section>

                    <div class="aula-hint">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="12" cy="12" r="8.6"/>
                            <path stroke-linecap="round" d="M12 8h.01M11 12h1v4h1"/>
                        </svg>
                        <span>Una pieza equivocada solo te pide otro intento. Nada se pierde.</span>
                    </div>
                </aside>
            </div>
        </main>

        @include('colegio.aula.partials.dock')
        @include('colegio.aula.partials.help-dialog')
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('themes/colegio/js/juegos/rompecabezas.js') }}"></script>
@endpush
