@extends('colegio.layouts.base')

@section('title', $school['name'] . ' · Sitio institucional')
@section('description', 'Página institucional de ' . $school['name'] . ': propuesta educativa, niveles Inicial, Primaria y Secundaria, y acceso al aula virtual.')
@section('body_class', 'bg-cole-cream font-body text-cole-ink antialiased')

@section('content')
    @php
        $levelOptions = collect($levels);

        $contactRows = collect([
            ['label' => 'Dirección', 'value' => $school['address'], 'icon' => 'pin', 'href' => null],
            ['label' => 'Teléfono', 'value' => $school['phone'], 'icon' => 'phone', 'href' => $school['phone_digits'] ? 'tel:' . $school['phone_digits'] : null],
            ['label' => 'Correo', 'value' => $school['email'], 'icon' => 'mail', 'href' => $school['email'] ? 'mailto:' . $school['email'] : null],
            ['label' => 'Código modular', 'value' => $school['modular_code'], 'icon' => 'code', 'href' => null],
            ['label' => 'Horario de atención', 'value' => 'Lunes a viernes · 7:45 a.m. a 3:30 p.m.', 'icon' => 'clock', 'href' => null],
        ])->filter(fn ($row) => filled($row['value']));
    @endphp

    {{-- ===================== Cabecera ===================== --}}
    <header class="sticky top-0 z-40 border-b border-cole-sand/70 bg-cole-cream/85 backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 md:px-6">
            <a href="{{ route('colegio.home') }}" class="flex min-w-0 items-center gap-3">
                <span class="grid h-11 w-11 shrink-0 place-items-center overflow-hidden rounded-2xl bg-gradient-to-br from-cole-mint to-[#FFEFC9] text-lg font-black text-cole-emerald ring-1 ring-white">
                    @if ($school['logo_url'])
                        <img src="{{ $school['logo_url'] }}" alt="{{ $school['name'] }}" class="h-full w-full object-cover">
                    @else
                        {{ $school['initial'] }}
                    @endif
                </span>
                <span class="min-w-0">
                    <span class="block truncate font-display text-base font-semibold leading-tight text-cole-forest md:text-lg">{{ $school['name'] }}</span>
                    <span class="block text-[10.5px] font-extrabold uppercase tracking-[0.18em] text-cole-emerald/70">Institución educativa</span>
                </span>
            </a>

            <nav class="hidden items-center gap-7 md:flex" aria-label="Secciones del sitio">
                <a href="#inicio" class="cole-nav-link">Inicio</a>
                <a href="#institucion" class="cole-nav-link">Institución</a>
                <a href="#niveles" class="cole-nav-link">Niveles</a>
                <a href="#contacto" class="cole-nav-link">Contacto</a>
            </nav>

            <a href="#niveles" class="cole-btn cole-btn-primary hidden px-5 py-2.5 text-sm md:inline-flex">
                Aula virtual
            </a>

            {{-- Menú móvil sin JavaScript --}}
            <details class="relative md:hidden">
                <summary class="grid h-11 w-11 cursor-pointer list-none place-items-center rounded-2xl border border-cole-sand bg-white/80 text-cole-forest" aria-label="Abrir menú">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/>
                    </svg>
                </summary>
                <nav class="absolute right-0 top-14 z-50 w-60 rounded-3xl border border-cole-sand bg-white p-3 shadow-cole" aria-label="Secciones del sitio">
                    <a href="#inicio" class="block rounded-2xl px-4 py-2.5 text-sm font-bold text-cole-forest hover:bg-cole-mint">Inicio</a>
                    <a href="#institucion" class="block rounded-2xl px-4 py-2.5 text-sm font-bold text-cole-forest hover:bg-cole-mint">Institución</a>
                    <a href="#niveles" class="block rounded-2xl px-4 py-2.5 text-sm font-bold text-cole-forest hover:bg-cole-mint">Niveles</a>
                    <a href="#contacto" class="block rounded-2xl px-4 py-2.5 text-sm font-bold text-cole-forest hover:bg-cole-mint">Contacto</a>
                    <a href="#niveles" class="cole-btn cole-btn-primary mt-2 w-full py-3 text-sm">Aula virtual</a>
                </nav>
            </details>
        </div>
    </header>

    {{-- ===================== Portada ===================== --}}
    <section id="inicio" class="relative overflow-hidden">
        <div class="pointer-events-none absolute -left-24 -top-20 h-72 w-72 rounded-full bg-cole-mint blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -right-16 top-24 h-64 w-64 rounded-full bg-[#FFEFC9] blur-3xl" aria-hidden="true"></div>

        <div class="relative mx-auto grid max-w-6xl items-center gap-12 px-4 pb-16 pt-12 md:grid-cols-[1.15fr_0.85fr] md:px-6 md:pb-24 md:pt-16">
            <div>
                <span class="cole-chip">Año escolar {{ $year }}</span>

                <h1 class="mt-5 font-display text-4xl leading-[1.08] text-cole-forest sm:text-5xl md:text-[3.4rem]">
                    {{ $school['name'] }}
                </h1>
                <p class="mt-4 max-w-xl font-display text-xl leading-snug text-cole-emerald sm:text-2xl">
                    Aprender con alegría, crecer con valores.
                </p>
                <p class="mt-4 max-w-xl text-[15px] leading-relaxed text-cole-ink/70">
                    Acompañamos a cada estudiante desde sus primeros pasos hasta la secundaria, con una
                    formación cercana que combina exigencia académica, juego y bienestar emocional.
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="#niveles" class="cole-btn cole-btn-primary">
                        Ingresar al aula virtual
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/>
                        </svg>
                    </a>
                    <a href="#institucion" class="cole-btn cole-btn-ghost">Conócenos</a>
                </div>

                <dl class="mt-10 flex flex-wrap gap-x-10 gap-y-5">
                    <div>
                        <dt class="text-[10.5px] font-extrabold uppercase tracking-[0.16em] text-cole-emerald/70">Niveles</dt>
                        <dd class="mt-1 font-display text-xl text-cole-forest">{{ $levelOptions->count() }}</dd>
                    </div>
                    @if ($school['type'])
                        <div>
                            <dt class="text-[10.5px] font-extrabold uppercase tracking-[0.16em] text-cole-emerald/70">Gestión</dt>
                            <dd class="mt-1 font-display text-xl text-cole-forest">{{ $school['type'] }}</dd>
                        </div>
                    @endif
                    @if ($school['modular_code'])
                        <div>
                            <dt class="text-[10.5px] font-extrabold uppercase tracking-[0.16em] text-cole-emerald/70">Código modular</dt>
                            <dd class="mt-1 font-display text-xl text-cole-forest">{{ $school['modular_code'] }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- Emblema del colegio con accesos flotantes --}}
            <div class="relative mx-auto flex w-full max-w-sm items-center justify-center py-4">
                <div class="absolute h-64 w-64 rounded-full bg-cole-mint/80 blur-2xl md:h-72 md:w-72" aria-hidden="true"></div>

                <div class="relative grid h-60 w-60 place-items-center rounded-full border-[10px] border-white bg-gradient-to-br from-[#EAF8EF] via-[#FFFDF6] to-[#FFEFC9] text-6xl font-black text-cole-emerald shadow-cole md:h-72 md:w-72">
                    @if ($school['logo_url'])
                        <img src="{{ $school['logo_url'] }}" alt="Escudo de {{ $school['name'] }}" class="h-full w-full rounded-full object-cover">
                    @else
                        {{ $school['initial'] }}
                    @endif
                </div>

                @foreach ($levelOptions as $index => $level)
                    @php
                        $positions = [
                            'inicial' => 'left-0 top-2',
                            'primaria' => 'right-0 top-1/3',
                            'secundaria' => 'bottom-0 left-6',
                        ];
                    @endphp
                    <span class="absolute {{ $positions[$level['key']] ?? 'left-0' }} inline-flex items-center gap-2 rounded-2xl border border-white bg-white/95 px-4 py-2 text-xs font-extrabold text-cole-forest shadow-cole-soft">
                        <span class="h-2 w-2 rounded-full {{ $level['available'] ? 'bg-cole-emerald' : 'bg-cole-amber' }}"></span>
                        {{ $level['name'] }}
                    </span>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===================== Nuestra institución ===================== --}}
    <section id="institucion" class="border-y border-cole-sand/70 bg-white/60 py-16 md:py-20">
        <div class="mx-auto max-w-6xl px-4 md:px-6">
            <div class="max-w-2xl">
                <span class="cole-chip">Nuestra institución</span>
                <h2 class="mt-4 font-display text-3xl leading-tight text-cole-forest md:text-4xl">
                    Una comunidad que aprende y se acompaña
                </h2>
                <p class="mt-3 text-[15px] leading-relaxed text-cole-ink/70">
                    Creemos en una educación que mira a la persona completa: lo académico, lo emocional y lo
                    social. Docentes cercanos, familias presentes y estudiantes protagonistas de su aprendizaje.
                </p>
            </div>

            <div class="mt-10 grid gap-5 md:grid-cols-3">
                <article class="cole-card-soft">
                    <span class="grid h-11 w-11 place-items-center rounded-2xl bg-cole-mint text-cole-emerald">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-4.4-7-10a4 4 0 017-2.6A4 4 0 0119 11c0 5.6-7 10-7 10z"/>
                        </svg>
                    </span>
                    <h3 class="mt-4 font-display text-xl text-cole-forest">Misión</h3>
                    <p class="mt-2 text-sm leading-relaxed text-cole-ink/70">
                        Formar estudiantes autónomos, curiosos y felices, capaces de aprender durante toda la
                        vida y de aportar a su comunidad.
                    </p>
                </article>

                <article class="cole-card-soft">
                    <span class="grid h-11 w-11 place-items-center rounded-2xl bg-[#FFEFC9] text-cole-amberdeep">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12s3.5-6.5 9.5-6.5S21.5 12 21.5 12s-3.5 6.5-9.5 6.5S2.5 12 2.5 12z"/>
                            <circle cx="12" cy="12" r="2.6"/>
                        </svg>
                    </span>
                    <h3 class="mt-4 font-display text-xl text-cole-forest">Visión</h3>
                    <p class="mt-2 text-sm leading-relaxed text-cole-ink/70">
                        Ser una institución referente por su calidad humana y académica, donde cada estudiante
                        descubra y desarrolle su potencial.
                    </p>
                </article>

                <article class="cole-card-soft">
                    <span class="grid h-11 w-11 place-items-center rounded-2xl bg-cole-blush text-[#A4572F]">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3l2.6 5.5 6 .8-4.4 4.2 1.1 6-5.3-3-5.3 3 1.1-6L3.4 9.3l6-.8L12 3z"/>
                        </svg>
                    </span>
                    <h3 class="mt-4 font-display text-xl text-cole-forest">Valores</h3>
                    <ul class="mt-2 space-y-1.5 text-sm leading-relaxed text-cole-ink/70">
                        <li class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-cole-emerald"></span>Respeto</li>
                        <li class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-cole-emerald"></span>Responsabilidad</li>
                        <li class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-cole-emerald"></span>Creatividad</li>
                    </ul>
                </article>
            </div>
        </div>
    </section>

    {{-- ===================== Niveles y aula virtual ===================== --}}
    <section id="niveles" class="py-16 md:py-24">
        <div class="mx-auto max-w-6xl px-4 md:px-6">
            <div class="flex flex-col gap-5 md:flex-row md:items-end md:justify-between">
                <div class="max-w-2xl">
                    <span class="cole-chip">Aula virtual</span>
                    <h2 class="mt-4 font-display text-3xl leading-tight text-cole-forest md:text-4xl">
                        Elige el nivel de tu estudiante
                    </h2>
                    <p class="mt-3 text-[15px] leading-relaxed text-cole-ink/70">
                        Cada nivel tiene su propio espacio de bienvenida. Ingresa con el usuario que entregó
                        la institución para ver clases, materiales y actividades.
                    </p>
                </div>
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-cole-emerald/60">
                    {{ $levelOptions->count() }} niveles educativos
                </p>
            </div>

            <div class="mt-10 grid gap-5 md:grid-cols-3">
                @foreach ($levelOptions as $level)
                    <article class="group relative flex flex-col rounded-4xl border border-cole-sand bg-white p-7 shadow-cole-soft transition duration-300 hover:-translate-y-1 hover:shadow-cole">
                        <div class="flex items-start justify-between gap-3">
                            <span class="grid h-12 w-12 place-items-center rounded-2xl bg-cole-mint text-cole-emerald">
                                @include('colegio.components.level-icon', ['icon' => $level['icon']])
                            </span>

                            @if ($level['available'])
                                <span class="rounded-full bg-cole-mint px-3 py-1 text-[10.5px] font-extrabold uppercase tracking-[0.14em] text-cole-emerald">Habilitado</span>
                            @else
                                <span class="rounded-full bg-cole-sand/70 px-3 py-1 text-[10.5px] font-extrabold uppercase tracking-[0.14em] text-cole-amberdeep">Próximamente</span>
                            @endif
                        </div>

                        <h3 class="mt-5 font-display text-2xl text-cole-forest">{{ $level['name'] }}</h3>
                        <p class="mt-1 text-[11px] font-extrabold uppercase tracking-[0.14em] text-cole-emerald/70">{{ $level['ages'] }}</p>
                        <p class="mt-3 flex-1 text-sm leading-relaxed text-cole-ink/70">{{ $level['description'] }}</p>

                        @if ($level['available'])
                            <a href="{{ $level['url'] }}" class="cole-btn cole-btn-primary mt-7 w-full">
                                Ingresar {{ $level['name'] }}
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/>
                                </svg>
                            </a>
                        @else
                            <button type="button" disabled class="cole-btn mt-7 w-full cursor-not-allowed bg-cole-sand/60 py-3.5 text-cole-forest/35">
                                Ingresar {{ $level['name'] }}
                            </button>
                        @endif
                    </article>
                @endforeach
            </div>

            <p class="mt-6 text-center text-xs font-semibold text-cole-ink/45">
                Los tres niveles ya tienen aula virtual. Ingresa con el código de alumno que entregó la institución.
            </p>
        </div>
    </section>

    {{-- ===================== Información y contacto ===================== --}}
    <section id="contacto" class="pb-16 md:pb-24">
        <div class="mx-auto max-w-6xl px-4 md:px-6">
            <div class="relative overflow-hidden rounded-4xl bg-cole-forest px-6 py-10 text-white shadow-cole md:px-12 md:py-14">
                <div class="pointer-events-none absolute -right-20 -top-24 h-64 w-64 rounded-full bg-cole-emerald/50 blur-2xl" aria-hidden="true"></div>
                <div class="pointer-events-none absolute -bottom-24 left-1/3 h-56 w-56 rounded-full bg-[#F7C45C]/20 blur-3xl" aria-hidden="true"></div>

                <div class="relative grid gap-10 md:grid-cols-2 md:items-start">
                    <div>
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-1.5 text-[10.5px] font-extrabold uppercase tracking-[0.18em] text-[#FFEFC9]">
                            Información institucional
                        </span>
                        <h2 class="mt-4 font-display text-3xl leading-tight md:text-4xl">Visítanos o escríbenos</h2>
                        <p class="mt-3 max-w-md text-sm leading-relaxed text-white/70">
                            Estamos disponibles para responder consultas sobre admisión, niveles y el uso del
                            aula virtual. También puedes acercarte a secretaría en el horario de atención.
                        </p>

                        @if ($school['email'])
                            <a href="mailto:{{ $school['email'] }}" class="cole-btn mt-7 bg-[#FFEFC9] px-6 py-3.5 text-cole-forest hover:shadow-cole">
                                Escribir a secretaría
                            </a>
                        @endif
                    </div>

                    <dl class="grid gap-4 sm:grid-cols-2">
                        @forelse ($contactRows as $row)
                            <div class="rounded-3xl border border-white/10 bg-white/[0.07] p-4">
                                <dt class="flex items-center gap-2 text-[10.5px] font-extrabold uppercase tracking-[0.16em] text-[#FFEFC9]/80">
                                    @include('colegio.components.contact-icon', ['icon' => $row['icon']])
                                    {{ $row['label'] }}
                                </dt>
                                <dd class="mt-2 break-words text-sm font-bold text-white/90">
                                    @if ($row['href'])
                                        <a href="{{ $row['href'] }}" class="hover:text-[#FFEFC9] hover:underline">{{ $row['value'] }}</a>
                                    @else
                                        {{ $row['value'] }}
                                    @endif
                                </dd>
                            </div>
                        @empty
                            <div class="rounded-3xl border border-white/10 bg-white/[0.07] p-4 sm:col-span-2">
                                <p class="text-sm text-white/75">Los datos de contacto se publicarán desde la configuración del colegio.</p>
                            </div>
                        @endforelse
                    </dl>
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== Pie de página ===================== --}}
    <footer class="border-t border-cole-sand/70 bg-cole-cream">
        <div class="mx-auto flex max-w-6xl flex-col gap-5 px-4 py-8 md:flex-row md:items-center md:justify-between md:px-6">
            <div class="flex items-center gap-3">
                <span class="grid h-9 w-9 place-items-center overflow-hidden rounded-xl bg-cole-mint text-sm font-black text-cole-emerald">
                    @if ($school['logo_url'])
                        <img src="{{ $school['logo_url'] }}" alt="" class="h-full w-full object-cover">
                    @else
                        {{ $school['initial'] }}
                    @endif
                </span>
                <div>
                    <p class="text-sm font-extrabold text-cole-forest">{{ $school['name'] }}</p>
                    <p class="text-[11px] font-semibold text-cole-ink/50">© {{ $year }} · Sitio institucional</p>
                </div>
            </div>

            <nav class="flex flex-wrap items-center gap-x-6 gap-y-2 text-xs font-bold text-cole-ink/60" aria-label="Enlaces del pie de página">
                <a href="#institucion" class="hover:text-cole-emerald">Institución</a>
                <a href="#niveles" class="hover:text-cole-emerald">Niveles</a>
                <a href="#contacto" class="hover:text-cole-emerald">Contacto</a>
                <a href="{{ route('colegio.aula.login') }}" class="hover:text-cole-emerald">Aula virtual · Inicial, Primaria y Secundaria</a>
            </nav>
        </div>
    </footer>
@endsection

@push('scripts')
    {{-- El menú móvil es un <details> nativo: al elegir una sección se cierra
         para no tapar el contenido al que se acaba de saltar. --}}
    <script>
        document.querySelectorAll('header details nav a').forEach(function (link) {
            link.addEventListener('click', function () {
                link.closest('details')?.removeAttribute('open');
            });
        });
    </script>
@endpush
