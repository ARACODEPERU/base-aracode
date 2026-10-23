@extends('colegio.layouts.base')

@section('title', 'Aula Virtual Inicial · ' . $school['name'])
@section('description', 'Bienvenida al aula virtual del nivel Inicial de ' . $school['name'] . '. Ingresa con tu usuario para ver tus clases y juegos.')
@section('body_class', 'font-body')

@section('content')
    <div class="cole-login flex flex-col" @if ($openLogin ?? false) data-open-login="true" @endif>
        @include('colegio.components.scene')

        {{-- ===================== Cabecera flotante ===================== --}}
        <header class="relative z-30 flex items-center justify-between gap-3 px-4 py-4 md:px-8 md:py-6">
            <a href="{{ route('colegio.home') }}"
               class="inline-flex items-center gap-2 rounded-full border border-white/80 bg-white/80 px-4 py-2 text-xs font-extrabold text-cole-forest shadow-cole-soft backdrop-blur transition hover:bg-white">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5M11 6l-6 6 6 6"/>
                </svg>
                Volver al colegio
            </a>

            <span class="hidden items-center gap-2 rounded-full border border-white/70 bg-white/70 px-4 py-2 text-xs font-extrabold text-cole-forest backdrop-blur sm:inline-flex">
                <span class="h-2 w-2 rounded-full bg-cole-emerald"></span>
                {{ $school['name'] }}
            </span>
        </header>

        {{-- ===================== Escenario y tarjeta ===================== --}}
        <main class="relative z-10 flex flex-1 items-center justify-center px-4 pb-8 pt-1 md:pb-12">
            <div class="cole-stage">
                @include('colegio.components.animals')

                <section class="cole-card" aria-labelledby="tituloBienvenida">
                    <div class="flex flex-col items-center text-center">
                        <div class="cole-logo">
                            @if ($school['logo_url'])
                                <img src="{{ $school['logo_url'] }}" alt="{{ $school['name'] }}" class="h-full w-full object-cover">
                            @else
                                <span class="font-display text-2xl font-semibold leading-none">{{ $school['initial'] }}</span>
                            @endif
                        </div>

                        <span class="mt-4 inline-flex items-center gap-1.5 rounded-full bg-cole-mint px-3 py-1 text-[10px] font-extrabold uppercase tracking-[0.16em] text-cole-emerald">
                            <span class="h-1.5 w-1.5 rounded-full bg-cole-emerald"></span>
                            Aula Virtual · Inicial
                        </span>

                        <h1 id="tituloBienvenida" class="mt-3 font-display text-[26px] leading-[1.15] text-cole-forest sm:text-[28px]">
                            ¡Hola! Vamos a aprender
                        </h1>
                        <p class="mt-2 max-w-[19rem] text-sm font-semibold leading-relaxed text-cole-ink/60">
                            Entra con tu código de alumno y tu contraseña para descubrir tus clases, cuentos y juegos.
                        </p>
                    </div>

                    {{-- Acciones principales --}}
                    <div class="mt-6 grid grid-cols-2 gap-3">
                        <button type="button" id="btnLogin"
                                class="cole-btn cole-btn-login h-14 text-base"
                                aria-expanded="false" aria-controls="panelLogin" data-bounce>
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 4h3a2 2 0 012 2v12a2 2 0 01-2 2h-3M10 8l4 4-4 4M14 12H3"/>
                            </svg>
                            Login
                        </button>

                        <button type="button" id="btnStart"
                                class="cole-btn cole-btn-start h-14 text-base"
                                aria-expanded="false" aria-controls="panelStart" data-bounce>
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v3M12 18v3M4.2 7.2l2.1 2.1M17.7 14.7l2.1 2.1M3 12h3M18 12h3M4.2 16.8l2.1-2.1M17.7 9.3l2.1-2.1"/>
                                <circle cx="12" cy="12" r="3.4"/>
                            </svg>
                            Comenzar
                        </button>
                    </div>

                    {{-- Formulario de acceso --}}
                    <div id="panelLogin" class="cole-collapse" aria-hidden="true">
                        <div class="cole-collapse__inner">
                            <form id="loginForm" method="POST" action="{{ route('colegio.inicial.login.store') }}"
                                  class="rounded-3xl border border-cole-sand/80 bg-white/75 p-4 text-left" novalidate>
                                @csrf

                                @if ($errors->any())
                                    <p class="mb-3 rounded-2xl border border-[#F3B9B9] bg-[#FFF0F0] px-3.5 py-2.5 text-xs font-bold leading-snug text-[#A83B45]" role="alert">
                                        {{ $errors->first('codigo') }}
                                    </p>
                                @endif

                                @if (session('status'))
                                    <p class="mb-3 rounded-2xl border border-cole-emerald/30 bg-cole-mint px-3.5 py-2.5 text-xs font-bold leading-snug text-cole-emerald" role="status">
                                        {{ session('status') }}
                                    </p>
                                @endif

                                <div class="space-y-3">
                                    <div>
                                        <label for="codigo" class="cole-label">Tu código de alumno</label>
                                        <div class="relative">
                                            <span class="cole-field-icon">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                                    <rect x="4" y="5.5" width="16" height="13" rx="3"/>
                                                    <path stroke-linecap="round" d="M8 10h4M8 14h8"/>
                                                </svg>
                                            </span>
                                            <input id="codigo" name="codigo" type="text" inputmode="text"
                                                   value="{{ old('codigo') }}"
                                                   autocomplete="username" class="cole-input"
                                                   placeholder="Ej. 2026-0001" aria-describedby="codigoError"
                                                   @if ($errors->has('codigo')) aria-invalid="true" @endif>
                                        </div>
                                        <p id="codigoError" class="mt-1.5 hidden text-xs font-bold text-[#C9525B]">Escribe tu código de alumno.</p>
                                    </div>

                                    <div>
                                        <label for="clave" class="cole-label">Tu contraseña</label>
                                        <div class="relative">
                                            <span class="cole-field-icon">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                                    <rect x="4.5" y="10" width="15" height="10" rx="2.6"/>
                                                    <path stroke-linecap="round" d="M8 10V7.8a4 4 0 018 0V10"/>
                                                </svg>
                                            </span>
                                            <input id="clave" name="clave" type="password" autocomplete="current-password"
                                                   class="cole-input pr-14" placeholder="Tu DNI la primera vez" aria-describedby="claveError"
                                                   @if ($errors->has('clave')) aria-invalid="true" @endif>
                                            <button type="button" id="togglePassword"
                                                    class="absolute right-2.5 top-1/2 grid h-9 w-9 -translate-y-1/2 place-items-center rounded-xl text-cole-forest/45 transition hover:bg-cole-mint hover:text-cole-emerald"
                                                    aria-label="Mostrar contraseña" aria-pressed="false">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.8 12S6.2 5.8 12 5.8 21.2 12 21.2 12 17.8 18.2 12 18.2 2.8 12 2.8 12z"/>
                                                    <circle cx="12" cy="12" r="2.7"/>
                                                </svg>
                                            </button>
                                        </div>
                                        <p id="claveError" class="mt-1.5 hidden text-xs font-bold text-[#C9525B]">Escribe tu contraseña.</p>
                                    </div>
                                </div>

                                <div class="mt-3.5 flex items-center justify-between gap-2">
                                    <label class="inline-flex cursor-pointer items-center gap-2 text-xs font-bold text-cole-forest/70">
                                        <input type="checkbox" name="recordar" value="1" @checked(old('recordar'))
                                               class="h-4 w-4 rounded border-cole-sand text-cole-emerald focus:ring-2 focus:ring-cole-amber">
                                        Recordarme
                                    </label>
                                    <button type="button" id="forgotPassword"
                                            class="text-xs font-extrabold text-cole-emerald hover:underline">
                                        ¿Olvidaste tu contraseña?
                                    </button>
                                </div>

                                <button type="submit" id="btnEntrar" class="cole-btn cole-submit mt-4 h-14 w-full text-base" data-bounce>
                                    Entrar
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 12h15M13 6l6 6-6 6"/>
                                    </svg>
                                </button>

                                <p id="loginNotice" class="mt-3 hidden rounded-2xl border border-cole-amber/50 bg-[#FFF6DC] px-3.5 py-2.5 text-xs font-bold leading-snug text-cole-amberdeep" role="status"></p>

                                <p class="cole-help mt-3 text-center">
                                    Tu contraseña inicial es tu número de DNI. Si ya la cambiaste y no la recuerdas,
                                    pídele ayuda a tu profesora.
                                </p>
                            </form>
                        </div>
                    </div>

                    {{-- Guía para quien entra por primera vez --}}
                    <div id="panelStart" class="cole-collapse" aria-hidden="true">
                        <div class="cole-collapse__inner">
                            <div class="rounded-3xl border border-cole-sand/80 bg-white/75 p-4 text-left">
                                <p class="text-sm font-extrabold text-cole-forest">¿Cómo entro al aula?</p>

                                <ol class="mt-3 space-y-3">
                                    <li class="flex gap-3">
                                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-cole-mint text-[11px] font-black text-cole-emerald">1</span>
                                        <span class="text-xs leading-relaxed text-cole-ink/65">
                                            <strong class="block text-[13px] font-extrabold text-cole-forest">Recibe tu código</strong>
                                            La profesora te entrega tu código de alumno. Tu contraseña inicial es tu DNI.
                                        </span>
                                    </li>
                                    <li class="flex gap-3">
                                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-cole-mint text-[11px] font-black text-cole-emerald">2</span>
                                        <span class="text-xs leading-relaxed text-cole-ink/65">
                                            <strong class="block text-[13px] font-extrabold text-cole-forest">Entra desde «Login»</strong>
                                            Escribe tu código, tu contraseña y pulsa Entrar.
                                        </span>
                                    </li>
                                    <li class="flex gap-3">
                                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-cole-mint text-[11px] font-black text-cole-emerald">3</span>
                                        <span class="text-xs leading-relaxed text-cole-ink/65">
                                            <strong class="block text-[13px] font-extrabold text-cole-forest">Elige tu avatar</strong>
                                            Personaliza tu perfil cuando llegues a tu espacio.
                                        </span>
                                    </li>
                                </ol>

                                <button type="button" id="startToLogin" class="cole-btn cole-btn-primary mt-4 h-12 w-full text-sm" data-bounce>
                                    Ya tengo mi código
                                </button>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </main>

        <footer class="relative z-10 pb-5 text-center text-[11px] font-bold text-cole-forest/45">
            © {{ $year }} {{ $school['name'] }} · Aula Virtual Inicial
        </footer>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('themes/colegio/js/vendor/anime.umd.min.js') }}" defer></script>
    <script src="{{ asset('themes/colegio/js/login.js') }}" defer></script>
@endpush
