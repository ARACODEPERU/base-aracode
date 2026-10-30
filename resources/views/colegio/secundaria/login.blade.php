@extends('colegio.layouts.secundaria')

@section('title', 'Acceso Secundaria · ' . $school['name'])
@section('description', 'Ingresa al aula virtual de Secundaria de ' . $school['name'] . ' con tu código de alumno.')

@section('content')
    <div class="sec2 sec2--login">
        <div class="sec2-aurora" aria-hidden="true">
            <span class="sec2-aurora__orb sec2-aurora__orb--purple"></span>
            <span class="sec2-aurora__orb sec2-aurora__orb--cyan"></span>
            <span class="sec2-aurora__orb sec2-aurora__orb--coral"></span>
        </div>

        <header class="sec-login__bar">
            <a href="{{ route('colegio.home') }}" class="sec-back">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5M11 6l-6 6 6 6"/>
                </svg>
                Volver al colegio
            </a>

            <span class="sec-pill sec-pill--level">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3.5 20 8l-8 4.5L4 8z"/>
                    <path stroke-linecap="round" d="M6.5 10.4v4.8c0 1.7 2.5 3.3 5.5 3.3s5.5-1.6 5.5-3.3v-4.8"/>
                </svg>
                Aula Virtual · Secundaria
            </span>
        </header>

        <main class="sec-login">
            {{-- Panel ilustrado --}}
            <section class="sec-login__visual">
                <span class="sec-eyebrow">
                    <span class="sec-eyebrow__dot"></span>
                    Ciclo {{ $year }} · Secundaria
                </span>

                <h1 class="sec-login__title">Tu siguiente nivel empieza aquí.</h1>

                <p class="sec-login__subtitle">
                    Tus cursos, tus materiales y tu progreso te esperan en un solo lugar.
                </p>

                <ul class="sec-login__points">
                    <li>
                        <span class="sec-login__point-icon" aria-hidden="true">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 5.5A1.5 1.5 0 0 1 5.5 4H10l2 2h6.5A1.5 1.5 0 0 1 20 7.5v11A1.5 1.5 0 0 1 18.5 20h-13A1.5 1.5 0 0 1 4 18.5z"/>
                            </svg>
                        </span>
                        Contenido publicado por tus docentes
                    </li>
                    <li>
                        <span class="sec-login__point-icon" aria-hidden="true">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 13h5l2 5h2l2-5h5"/>
                                <path stroke-linecap="round" d="M4 13V8.5A1.5 1.5 0 0 1 5.5 7H10l2 2h6.5A1.5 1.5 0 0 1 20 10.5V13"/>
                            </svg>
                        </span>
                        Tus entregas y avance al día
                    </li>
                    <li>
                        <span class="sec-login__point-icon" aria-hidden="true">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m12 3 2.6 5.8 6.4.7-4.8 4.3 1.4 6.2-5.6-3.3-5.6 3.3 1.4-6.2L3 9.5l6.4-.7z"/>
                            </svg>
                        </span>
                        Retos y logros mientras aprendes
                    </li>
                </ul>

                <div class="sec-login__art">
                    @include('colegio.secundaria.partials.hero-character')
                </div>
            </section>

            {{-- Formulario --}}
            <section class="sec-login__card" aria-labelledby="secLoginTitle">
                <div class="sec-login__brand">
                    <span class="sec-brand__logo">
                        @if ($school['logo_url'])
                            <img src="{{ $school['logo_url'] }}" alt="{{ $school['name'] }}" class="h-full w-full object-cover">
                        @else
                            <span>{{ $school['initial'] }}</span>
                        @endif
                    </span>
                    <div>
                        <span class="sec-brand__eyebrow">Aula Virtual</span>
                        <span class="sec-brand__name">{{ $school['name'] }}</span>
                    </div>
                </div>

                <span class="sec-kicker">Acceso de estudiantes</span>
                <h2 id="secLoginTitle" class="sec-login__card-title">Entra a tu aula</h2>
                <p class="sec-login__card-text">
                    Usa el código de alumno que te entregó el colegio. La primera vez, tu contraseña es tu DNI.
                </p>

                @if (session('status'))
                    <div class="sec-login__alert sec-login__alert--ok" role="status">
                        {{ session('status') }}
                    </div>
                @endif

                @error('codigo')
                    <div class="sec-login__alert sec-login__alert--error" role="alert">
                        {{ $message }}
                    </div>
                @enderror

                <form method="POST" action="{{ route('colegio.secundaria.login.store') }}" class="sec-login__form">
                    @csrf

                    <label class="sec-field" for="secCodigo">
                        <span>Código de alumno</span>
                        <input id="secCodigo"
                               name="codigo"
                               type="text"
                               value="{{ old('codigo') }}"
                               inputmode="text"
                               autocomplete="username"
                               placeholder="Ej. 2026-0001"
                               required
                               autofocus>
                    </label>

                    <label class="sec-field" for="secClave">
                        <span>Contraseña</span>
                        <input id="secClave"
                               name="clave"
                               type="password"
                               autocomplete="current-password"
                               placeholder="Tu DNI la primera vez"
                               required>
                    </label>
                    @error('clave')
                        <span class="sec-field__error">{{ $message }}</span>
                    @enderror

                    <label class="sec-check">
                        <input type="checkbox" name="recordar" value="1" @checked(old('recordar'))>
                        <span>Mantener mi sesión en este dispositivo</span>
                    </label>

                    <button type="submit" class="sec-login__submit">
                        Entrar a mi aula
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h12M12 6l6 6-6 6"/>
                        </svg>
                    </button>
                </form>

                <p class="sec-login__help">
                    ¿Olvidaste tu contraseña? Pide apoyo en secretaría para reponerla.
                </p>

                <a href="{{ route('colegio.aula.login') }}" class="sec-login__switch">
                    ¿Eres de Inicial o Primaria? Ingresa por el acceso general
                </a>
            </section>
        </main>
    </div>
@endsection
