@extends('colegio.layouts.base')

@section('title', 'Contando frutas · Juegos del aula · ' . $portal['school']['name'])
@section('description', 'Juego para contar frutas del 1 al 10: cuenta grupos, llena cestas y une cada grupo con su número, en diez niveles para el aula de Inicial.')
@section('body_class', 'font-body')

@section('content')
    {{-- data-image-base: las frutas viven en public/themes/colegio/img/juegos/frutas --}}
    <div class="aula juego-frutas" data-juego="contando-frutas"
         data-image-base="{{ asset('themes/colegio/img/juegos/frutas') }}">
        @include('colegio.components.aula.scene')
        @include('colegio.inicial.partials.topbar')

        <main class="aula-main">
            <a href="{{ route('colegio.inicial.games') }}" class="aula-back">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5M11 6l-6 6 6 6"/>
                </svg>
                Volver a los juegos
            </a>

            {{-- El héroe se compacta en el celular para que la actividad quede a
                 la vista sin empujar el botón de inicio fuera de la pantalla. --}}
            <section class="aula-hero aula-hero--juego aula-pop mt-4" data-listen>
                <div>
                    <p class="aula-eyebrow">Zona de juegos</p>
                    <h1 class="aula-title">
                        Contando frutas
                        <span aria-hidden="true">🍎</span>
                    </h1>
                    <p class="aula-subtitle">
                        Cuenta las frutas, llena la cesta y une cada grupo con su número.
                        Del 1 al 10, sin prisa y sin perder: si algo no sale, lo intentas otra vez.
                    </p>
                </div>

                <div class="aula-hero__stats">
                    <div class="aula-stat">
                        <span class="aula-stat__value">
                            <span data-activity-current>1</span><span aria-hidden="true">/</span><span data-activity-total>3</span>
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
                @include('colegio.juegos.partials.frutas-tablero')

                <aside class="juego-side">
                    <section class="juego-card" aria-labelledby="frutasComoTitle">
                        <h2 id="frutasComoTitle" class="juego-card__title">Cómo se juega</h2>
                        <ul class="juego-pasos mt-2">
                            <li>
                                <span class="juego-pasos__num">1</span>
                                Cuenta las frutas y toca el número.
                            </li>
                            <li>
                                <span class="juego-pasos__num">2</span>
                                Arrastra una fruta a la cesta o tócala y después toca la cesta.
                            </li>
                            <li>
                                <span class="juego-pasos__num">3</span>
                                Une cada grupo con el número que le toca.
                            </li>
                        </ul>
                        <p class="juego-card__text">
                            Las frutas nunca pasan de diez y ningún error quita puntos.
                        </p>
                    </section>

                    <section class="juego-card" aria-labelledby="frutasNivelesTitle">
                        <h2 id="frutasNivelesTitle" class="juego-card__title">Niveles</h2>
                        <p class="juego-card__text">Diez niveles, del 1 al 10. Puedes jugar el que quieras.</p>
                        <div class="juego-niveles" data-levels></div>
                    </section>

                    <div class="aula-hint">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="12" cy="12" r="8.6"/>
                            <path stroke-linecap="round" d="M12 8h.01M11 12h1v4h1"/>
                        </svg>
                        <span>Si te equivocas, la fruta solo te pide otro intento. Nada se pierde.</span>
                    </div>
                </aside>
            </div>
        </main>

        @include('colegio.inicial.partials.dock')
        @include('colegio.inicial.partials.help-dialog')
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('themes/colegio/js/aula.js') }}"></script>
    <script src="{{ asset('themes/colegio/js/juegos/contando-frutas.js') }}"></script>
@endpush
