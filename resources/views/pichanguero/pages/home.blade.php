@extends('pichanguero.layouts.app')

@section('title', 'Pichanguero — Organiza tu torneo de fútbol sin Excel')
@section('description', 'Pichanguero organiza tu campeonato de fútbol: fixture automático, tabla de posiciones, estadísticas de jugadores y resultados en vivo desde el navegador.')

@section('content')
    @php
        // La app Android aún no está publicada. Mientras `app_disponible` sea
        // false la portada no ofrece ninguna descarga y la acción principal es
        // «Crear cuenta» en la plataforma de torneos.
        $appDisponible = (bool) config('pichanguero.app_disponible', false);
        $registroUrl = config('pichanguero.registro_url');
    @endphp

    @include('pichanguero.components.navbar')

    {{-- ===================== HERO ===================== --}}
    <section class="pg-hero">
        {{-- Fondo: fotografía real de fútbol con velo azul noche --}}
        <div class="pg-hero-photo" aria-hidden="true">
            <img src="{{ asset('themes/pichanguero/images/hero-bg.jpg') }}"
                 alt="" width="1920" height="950" fetchpriority="high">
        </div>

        {{-- Franjas diagonales de la publicidad --}}
        <div class="pg-hero-slash" aria-hidden="true"></div>
        <div class="pg-hero-slash is-thin" aria-hidden="true"></div>

        <div class="pg-container">
            <div class="pg-hero-grid">
                <div>
                    <p class="pg-eyebrow">Torneos de fútbol y más</p>

                    <h1 class="pg-hero-title">
                        Tu torneo,<br>
                        <span class="pg-hero-accent">en la palma.
                            <svg class="pg-hero-stroke" viewBox="0 0 300 18" preserveAspectRatio="none" aria-hidden="true">
                                <path d="M3 12c60-7 118-9 186-6 39 2 74 5 108 3" fill="none"
                                      stroke="currentColor" stroke-width="5" stroke-linecap="round"/>
                            </svg>
                        </span><br>
                        de tu mano
                    </h1>

                    <p class="pg-hero-lead">
                        Sigue cada partido, resultado y novedad desde el navegador. Fixture, tabla de
                        posiciones y estadísticas de tu campeonato, siempre al día.
                    </p>

                    <div class="pg-hero-actions">
                        @if ($appDisponible)
                            <a href="{{ route('pichanguero.descargas') }}" class="pg-btn pg-btn-primary pg-btn-lg">
                                Descargar la app
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                            </a>
                        @else
                            <a href="{{ $registroUrl }}" class="pg-btn pg-btn-primary pg-btn-lg" target="_blank" rel="noopener">
                                Crear cuenta
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </a>
                        @endif
                        <a href="#como-funciona" class="pg-btn pg-btn-ghost pg-btn-lg">Ver cómo funciona</a>
                    </div>

                    <ul class="pg-hero-facts">
                        <li>
                            <strong>1 clic</strong>
                            <span>para generar el fixture</span>
                        </li>
                        <li>
                            <strong>0</strong>
                            <span>hojas de cálculo</span>
                        </li>
                        <li>
                            <strong>100 %</strong>
                            <span>desde el navegador</span>
                        </li>
                    </ul>
                </div>

                {{-- Panel de producto: datos de demostración --}}
                <div class="pg-panel" aria-label="Vista de demostración de la plataforma">
                    <div class="pg-panel-bar">
                        <span class="pg-panel-dot" aria-hidden="true"></span>
                        <span class="pg-panel-name">Pichanguero</span>
                        <span class="pg-panel-live">En vivo</span>
                    </div>

                    <div class="pg-panel-tabs" role="tablist" aria-label="Vistas del panel" data-pg-tabs>
                        <button type="button" role="tab" class="pg-panel-tab" id="pg-tab-partidos"
                                aria-selected="true" aria-controls="pg-panel-partidos">Próximos partidos</button>
                        <button type="button" role="tab" class="pg-panel-tab" id="pg-tab-posiciones"
                                aria-selected="false" aria-controls="pg-panel-posiciones" tabindex="-1">Posiciones</button>
                    </div>

                    <div class="pg-panel-body" role="tabpanel" id="pg-panel-partidos" aria-labelledby="pg-tab-partidos">
                        <p class="pg-panel-label">Copa de los Barrios <span>Ver todos</span></p>

                        <article class="pg-match">
                            <span class="pg-match-date">Sáb 12 Abr · 16:00</span>
                            <span class="pg-match-team"><span class="pg-crest">LH</span> Los Halcones</span>
                            <span class="pg-match-vs">VS</span>
                            <span class="pg-match-team is-right">FC Amigos <span class="pg-crest is-lime">FA</span></span>
                        </article>

                        <article class="pg-match">
                            <span class="pg-match-date">Dom 13 Abr · 10:00</span>
                            <span class="pg-match-team"><span class="pg-crest is-blue">SF</span> Sporting FC</span>
                            <span class="pg-match-vs">VS</span>
                            <span class="pg-match-team is-right">Real 7 <span class="pg-crest is-slate">R7</span></span>
                        </article>

                        <article class="pg-match">
                            <span class="pg-match-date">Dom 20 Abr · 12:30</span>
                            <span class="pg-match-team"><span class="pg-crest is-slate">LP</span> La Peña</span>
                            <span class="pg-match-vs">VS</span>
                            <span class="pg-match-team is-right">Tigres <span class="pg-crest is-blue">TG</span></span>
                        </article>

                        <p class="pg-panel-note">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 8v5m0 3h.01M12 3a9 9 0 100 18 9 9 0 000-18z"/>
                            </svg>
                            Datos de demostración
                        </p>
                    </div>

                    <div class="pg-panel-body" role="tabpanel" id="pg-panel-posiciones" aria-labelledby="pg-tab-posiciones" hidden>
                        <table class="pg-standings">
                            <caption>Tabla de posiciones</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Equipo</th>
                                    <th scope="col">PJ</th>
                                    <th scope="col">DG</th>
                                    <th scope="col">Pts</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="pg-standings-team"><span class="pg-crest">LH</span> Los Halcones</td>
                                    <td>6</td>
                                    <td>+12</td>
                                    <td class="pg-standings-pts">16</td>
                                </tr>
                                <tr>
                                    <td class="pg-standings-team"><span class="pg-crest is-slate">R7</span> Real 7</td>
                                    <td>6</td>
                                    <td>+8</td>
                                    <td class="pg-standings-pts">13</td>
                                </tr>
                                <tr>
                                    <td class="pg-standings-team"><span class="pg-crest is-slate">LP</span> La Peña</td>
                                    <td>6</td>
                                    <td>+5</td>
                                    <td class="pg-standings-pts">11</td>
                                </tr>
                                <tr>
                                    <td class="pg-standings-team"><span class="pg-crest is-blue">SF</span> Sporting FC</td>
                                    <td>6</td>
                                    <td>+3</td>
                                    <td class="pg-standings-pts">10</td>
                                </tr>
                                <tr>
                                    <td class="pg-standings-team"><span class="pg-crest is-blue">TG</span> Tigres</td>
                                    <td>6</td>
                                    <td>-2</td>
                                    <td class="pg-standings-pts">7</td>
                                </tr>
                            </tbody>
                        </table>

                        <p class="pg-panel-note">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 8v5m0 3h.01M12 3a9 9 0 100 18 9 9 0 000-18z"/>
                            </svg>
                            Datos de demostración
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== CAPACIDADES ===================== --}}
    <section class="pg-strip" aria-labelledby="titulo-capacidades">
        <div class="pg-container">
            <h2 class="sr-only" id="titulo-capacidades">Lo que incluye Pichanguero</h2>
            <ul class="pg-strip-list">
                <li class="pg-strip-item">
                    <span class="pg-strip-icon" aria-hidden="true">
                        <svg fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M3 11h18M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </span>
                    <div>
                        <p class="pg-strip-title">Fixture y resultados</p>
                        <p class="pg-strip-text">Fechas y cruces listos</p>
                    </div>
                </li>
                <li class="pg-strip-item">
                    <span class="pg-strip-icon" aria-hidden="true">
                        <svg fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </span>
                    <div>
                        <p class="pg-strip-title">Equipos y jugadores</p>
                        <p class="pg-strip-text">Planteles ordenados</p>
                    </div>
                </li>
                <li class="pg-strip-item">
                    <span class="pg-strip-icon" aria-hidden="true">
                        <svg fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 21h8m-4-4v4M7 4h10v4a5 5 0 01-10 0V4zM7 5H4v2a3 3 0 003 3M17 5h3v2a3 3 0 01-3 3"/>
                        </svg>
                    </span>
                    <div>
                        <p class="pg-strip-title">Tabla de posiciones</p>
                        <p class="pg-strip-text">Calculada sola</p>
                    </div>
                </li>
                <li class="pg-strip-item">
                    <span class="pg-strip-icon" aria-hidden="true">
                        <svg fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 3L4 14h6l-1 7 9-11h-6l1-7z"/>
                        </svg>
                    </span>
                    <div>
                        <p class="pg-strip-title">Novedades y avisos</p>
                        <p class="pg-strip-text">Información al día</p>
                    </div>
                </li>
                <li class="pg-strip-item">
                    <span class="pg-strip-icon" aria-hidden="true">
                        <svg fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7a2 2 0 012-2h9a2 2 0 012 2v8a2 2 0 01-2 2h-6l-3 3v-3H4a2 2 0 01-2-2V9a2 2 0 012-2"/>
                        </svg>
                    </span>
                    <div>
                        <p class="pg-strip-title">Desde la web</p>
                        <p class="pg-strip-text">Sin instalar nada</p>
                    </div>
                </li>
            </ul>
        </div>
    </section>

    {{-- ===================== PRODUCTO ===================== --}}
    <section class="pg-surface-light pg-surface-plain pg-section scroll-mt-24" id="producto">
        <div class="pg-container">
            <div class="pg-product">
                <div>
                    <p class="pg-kicker">Así se ve por dentro</p>
                    <h2 class="text-3xl pg-heading sm:text-4xl">
                        El campeonato <em class="pg-accent">ordenado</em>, sin planillas
                    </h2>
                    <p class="mt-4 pg-lead">
                        Carga los resultados y la plataforma hace el resto: posiciones, diferencia de goles
                        y rankings se actualizan solos.
                    </p>

                    <ul class="pg-checks">
                        <li>
                            <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            Fixture por grupos, llaves o liga, sin armar cruces a mano.
                        </li>
                        <li>
                            <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            Goleadores, asistencias y vallas invictas siempre al día.
                        </li>
                        <li>
                            <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            Inscripciones, cupos y estado de cada equipo en un solo lugar.
                        </li>
                    </ul>

                    <p class="mt-7">
                        <a href="{{ $registroUrl }}" target="_blank" rel="noopener" class="pg-btn pg-btn-green">
                            Probar la plataforma
                        </a>
                    </p>
                </div>

                <div class="pg-window" aria-label="Vista de demostración del panel de gestión">
                    <div class="pg-window-bar">
                        <span class="pg-window-dots" aria-hidden="true"><span></span><span></span><span></span></span>
                        <span class="pg-window-url">avc.pichanguero.com/torneos/copa-de-los-barrios</span>
                    </div>
                    <div class="pg-window-body">
                        <div class="pg-window-panel">
                            <h3>Próxima fecha</h3>

                            <article class="pg-match">
                                <span class="pg-match-date">Sáb 12 Abr · 16:00</span>
                                <span class="pg-match-team"><span class="pg-crest">LH</span> Los Halcones</span>
                                <span class="pg-match-vs">VS</span>
                                <span class="pg-match-team is-right">FC Amigos <span class="pg-crest is-lime">FA</span></span>
                            </article>

                            <article class="pg-match">
                                <span class="pg-match-date">Dom 13 Abr · 10:00</span>
                                <span class="pg-match-team"><span class="pg-crest is-blue">SF</span> Sporting FC</span>
                                <span class="pg-match-vs">VS</span>
                                <span class="pg-match-team is-right">Real 7 <span class="pg-crest is-slate">R7</span></span>
                            </article>

                            <div class="pg-bars">
                                <div>
                                    <div class="pg-bar-label"><span>Fecha completada</span><span>3 de 4</span></div>
                                    <div class="pg-bar-track"><div class="pg-bar-fill" style="width: 75%"></div></div>
                                </div>
                                <div>
                                    <div class="pg-bar-label"><span>Equipos inscritos</span><span>12 de 16</span></div>
                                    <div class="pg-bar-track"><div class="pg-bar-fill" style="width: 75%"></div></div>
                                </div>
                            </div>
                        </div>

                        <div class="pg-window-panel">
                            <h3>Goleadores</h3>
                            <ul class="pg-scorers">
                                <li><span class="pg-crest">LH</span> M. Quispe <b>9</b></li>
                                <li><span class="pg-crest is-lime">FA</span> J. Rojas <b>7</b></li>
                                <li><span class="pg-crest is-blue">SF</span> D. Ayala <b>6</b></li>
                                <li><span class="pg-crest is-slate">LP</span> C. Medina <b>5</b></li>
                            </ul>
                            <p class="pg-panel-note">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M12 8v5m0 3h.01M12 3a9 9 0 100 18 9 9 0 000-18z"/>
                                </svg>
                                Datos de demostración
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== PASOS ===================== --}}
    <section class="pg-surface-dark pg-section scroll-mt-24" id="como-funciona">
        <div class="pg-container">
            <div class="max-w-2xl">
                <p class="pg-kicker">Cómo funciona</p>
                <h2 class="text-3xl pg-heading sm:text-4xl">
                    De la idea al fixture en <em class="pg-accent">cuatro pasos</em>
                </h2>
                <p class="mt-4 pg-body">
                    Sin configuraciones complicadas: creas el torneo una vez y la plataforma se encarga del resto.
                </p>
            </div>

            <ol class="pg-steps">
                <li class="pg-step">
                    <span class="pg-step-num" aria-hidden="true">1</span>
                    <h3>Crea tu torneo</h3>
                    <p>Define nombre, formato y reglas del campeonato.</p>
                </li>
                <li class="pg-step">
                    <span class="pg-step-num" aria-hidden="true">2</span>
                    <h3>Inscribe a los equipos</h3>
                    <p>Registra clubes, planteles y responsables.</p>
                </li>
                <li class="pg-step">
                    <span class="pg-step-num" aria-hidden="true">3</span>
                    <h3>Genera el fixture</h3>
                    <p>La plataforma arma las fechas y los cruces.</p>
                </li>
                <li class="pg-step">
                    <span class="pg-step-num" aria-hidden="true">4</span>
                    <h3>Publica los resultados</h3>
                    <p>Carga los marcadores y la tabla se actualiza sola.</p>
                </li>
            </ol>
        </div>
    </section>

    {{-- ===================== PÚBLICO ===================== --}}
    <section class="pg-surface-light pg-surface-plain pg-section scroll-mt-24" id="para-quien">
        <div class="pg-container">
            <div class="mx-auto max-w-2xl text-center">
                <p class="pg-kicker">Hecho para</p>
                <h2 class="text-3xl pg-heading sm:text-4xl">¿Para quién es Pichanguero?</h2>
                <p class="mt-4 pg-body">
                    Desde una liga barrial hasta academias que manejan varias categorías durante el año.
                </p>
            </div>

            <div class="pg-audience">
                <article class="pg-card">
                    <div class="pg-icon-box">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                  d="M8 21h8m-4-4v4M7 4h10v4a5 5 0 01-10 0V4zM7 5H4v2a3 3 0 003 3M17 5h3v2a3 3 0 01-3 3"/>
                        </svg>
                    </div>
                    <h3 class="mt-4 text-lg pg-heading">Ligas y campeonatos</h3>
                    <p class="mt-2 leading-relaxed pg-body">
                        Organiza fechas, sanciones y posiciones de toda la temporada con reglas claras.
                    </p>
                </article>

                <article class="pg-card">
                    <div class="pg-icon-box">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                  d="M12 3l8 4v6c0 4.5-3.2 7.5-8 8.5-4.8-1-8-4-8-8.5V7l8-4z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4"/>
                        </svg>
                    </div>
                    <h3 class="mt-4 text-lg pg-heading">Academias y clubes</h3>
                    <p class="mt-2 leading-relaxed pg-body">
                        Lleva el control de tus categorías, planteles y estadísticas de cada jugador.
                    </p>
                </article>

                <article class="pg-card">
                    <div class="pg-icon-box">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <h3 class="mt-4 text-lg pg-heading">Organizadores independientes</h3>
                    <p class="mt-2 leading-relaxed pg-body">
                        Monta torneos relámpago o de fin de semana y comparte la información al instante.
                    </p>
                </article>
            </div>
        </div>
    </section>

    {{-- ===================== PREGUNTAS FRECUENTES ===================== --}}
    <section class="pg-surface-light pg-section scroll-mt-24" id="preguntas">
        <div class="pg-container">
            <div class="mx-auto max-w-3xl">
                <div class="text-center">
                    <p class="pg-kicker">Preguntas frecuentes</p>
                    <h2 class="text-3xl pg-heading sm:text-4xl">Dudas habituales</h2>
                </div>

                <div class="mt-10 space-y-3">
                    <details class="pg-faq">
                        <summary>¿Necesito instalar algo en la computadora?</summary>
                        <p class="leading-relaxed">
                            No. Todo funciona desde el navegador, también desde el celular: no hay que instalar
                            nada para gestionar o consultar el torneo.
                        </p>
                    </details>
                    <details class="pg-faq">
                        <summary>¿Cómo empiezo a usar Pichanguero?</summary>
                        <p class="leading-relaxed">
                            Crea tu cuenta, registra tu organización y arma tu primer torneo. Desde ahí puedes
                            publicar la información para los equipos y jugadores.
                        </p>
                    </details>
                    <details class="pg-faq">
                        <summary>¿Sirve para alquiler de canchas?</summary>
                        <p class="leading-relaxed">
                            Sí. Además de campeonatos, Pichanguero se usa para gestionar reservas y adelantos
                            de alquiler de local.
                        </p>
                    </details>
                    <details class="pg-faq">
                        <summary>¿Cuánto cuesta?</summary>
                        <p class="leading-relaxed">
                            Depende del tamaño del torneo y de los módulos que necesites. Escríbenos y te
                            pasamos los planes según tu caso.
                        </p>
                    </details>
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== CIERRE ===================== --}}
    <section class="pg-closing" id="contacto">
        <div class="pg-container pg-closing-grid">
            <div>
                <p class="pg-closing-slogan">Organiza.<br>Compite.<br>Vive el fútbol.</p>
            </div>
            <div>
                <h2>¿Listo para ordenar tu torneo?</h2>
                <p class="pg-closing-lead">
                    Cuéntanos cuántos equipos manejas y te mostramos Pichanguero funcionando con tu campeonato.
                </p>
                <div class="pg-closing-actions">
                    <a href="{{ $registroUrl }}" target="_blank" rel="noopener" class="pg-btn pg-btn-primary pg-btn-lg">
                        Crear cuenta
                    </a>
                    <a href="{{ route('pichanguero.contacto') }}" class="pg-btn pg-btn-ghost pg-btn-lg">
                        Solicitar demo
                    </a>
                </div>
                <p class="pg-closing-note">Los equipos, marcadores y estadísticas de la interfaz son de demostración.</p>
            </div>
        </div>
    </section>

    @include('pichanguero.components.footer')
@endsection
