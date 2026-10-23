@extends('colegio.layouts.base')

@section('title', 'Colores y formas · Juegos del aula · ' . $portal['school']['name'])
@section('description', 'Juego para reconocer colores y figuras geométricas: combina forma y color, encuentra parejas, clasifica y completa secuencias, en diez niveles para el aula de Inicial.')
@section('body_class', 'font-body')

@section('content')
    <div class="aula juego-formas" data-juego="colores-formas">
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
                        Colores y formas
                        <span aria-hidden="true">🎨</span>
                    </h1>
                    <p class="aula-subtitle">
                        Descubre colores, reconoce figuras, encuentra parejas y completa secuencias.
                        Diez niveles para mirar con atención, sin prisa y sin perder.
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
                @include('colegio.juegos.partials.colores-tablero')

                <aside class="juego-side">
                    <section class="juego-card" aria-labelledby="formasComoTitle">
                        <h2 id="formasComoTitle" class="juego-card__title">Cómo se juega</h2>
                        <ul class="juego-pasos mt-2">
                            <li>
                                <span class="juego-pasos__num">1</span>
                                Mira el color o la figura grande.
                            </li>
                            <li>
                                <span class="juego-pasos__num">2</span>
                                Toca la respuesta o lleva la figura a su caja.
                            </li>
                            <li>
                                <span class="juego-pasos__num">3</span>
                                En las secuencias, elige qué figura sigue.
                            </li>
                        </ul>
                        <p class="juego-card__text">
                            Ningún error quita puntos y siempre puedes intentarlo otra vez.
                        </p>
                    </section>

                    <section class="juego-card" aria-labelledby="formasNivelesTitle">
                        <h2 id="formasNivelesTitle" class="juego-card__title">Niveles</h2>
                        <p class="juego-card__text">Diez niveles, de los primeros colores al desafío final. Puedes jugar el que quieras.</p>
                        <div class="juego-niveles" data-levels></div>
                    </section>

                    <div class="aula-hint">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="12" cy="12" r="8.6"/>
                            <path stroke-linecap="round" d="M12 8h.01M11 12h1v4h1"/>
                        </svg>
                        <span>Puedes tocar o arrastrar las figuras. En el celular, tocar y luego tocar la caja es más fácil.</span>
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
    <script src="{{ asset('themes/colegio/js/juegos/colores-formas.js') }}"></script>
@endpush
