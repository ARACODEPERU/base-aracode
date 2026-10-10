{{--
    Composición ilustrativa de la interfaz de KAPTA LMS.

    Es HTML y CSS propios: NO es una captura del sistema (todavía no hay capturas
    auténticas) y por eso no muestra cifras reales, ni porcentajes, ni datos de
    ninguna institución: solo la estructura de las pantallas. Lleva la etiqueta
    "Ilustrativo" dentro del marco y una nota debajo para no confundirla con una
    captura real.

    Variables:
        $variant  'panel' (panel administrativo) | 'alumno' (campus del alumno)
                  | 'certificado' | 'compacto'. Por defecto 'panel'.
        $titulo   texto de la barra superior.
--}}
@php
    $variant = $variant ?? 'panel';
    $titulo = $titulo ?? 'Panel administrativo';
@endphp

<div class="ka-mock">
    <div class="ka-mock-bar">
        <span class="ka-mock-dot"></span>
        <span class="ka-mock-dot"></span>
        <span class="ka-mock-dot"></span>
        <span class="ka-mock-title">{{ $titulo }}</span>
        <span class="ml-auto rounded-full border px-2 py-0.5 text-[0.625rem] font-bold uppercase tracking-wide"
              style="border-color: var(--ka-mock-line); color: var(--ka-surface-slate)">Ilustrativo</span>
    </div>

    <div class="ka-mock-body">
        @if ($variant === 'panel')
            {{-- Panel administrativo: indicadores y cursos en marcha --}}
            <div class="grid grid-cols-3 gap-2 sm:gap-3">
                @foreach (['Cursos', 'Matrículas', 'Certificados'] as $etiqueta)
                    <div class="ka-mock-panel">
                        <p class="text-[0.625rem] font-semibold uppercase tracking-wide ka-slate">{{ $etiqueta }}</p>
                        <div class="ka-bar mt-2.5"><span class="w-[62%]"></span></div>
                    </div>
                @endforeach
            </div>

            <div class="ka-mock-panel mt-3">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold ka-ink">Cursos en dictado</p>
                    <span class="text-[0.625rem] font-semibold uppercase tracking-wide ka-slate">Avance</span>
                </div>
                <ul class="mt-3 space-y-2.5">
                    @foreach ([78, 54, 41] as $avance)
                        <li class="flex items-center gap-3">
                            <span class="ka-mock-dot"></span>
                            <span class="ka-bar w-full"><span class="w-[{{ $avance }}%]"></span></span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="mt-3 grid grid-cols-2 gap-2 sm:gap-3">
                <div class="ka-mock-panel">
                    <p class="text-[0.625rem] font-semibold uppercase tracking-wide ka-slate">Alumnos</p>
                    <div class="mt-2 flex items-end gap-1.5" aria-hidden="true">
                        @foreach ([40, 62, 48, 76, 58] as $alto)
                            <span class="w-3 rounded-t" style="height: {{ $alto }}px; background-image: linear-gradient(180deg, #42c5f5, #0188ee); opacity: .85"></span>
                        @endforeach
                    </div>
                </div>
                <div class="ka-mock-panel">
                    <p class="text-[0.625rem] font-semibold uppercase tracking-wide ka-slate">Certificado</p>
                    <div class="mt-2 space-y-2">
                        <div class="ka-bar"><span class="w-[86%]"></span></div>
                        <div class="ka-bar"><span class="w-[70%]"></span></div>
                        <div class="ka-bar"><span class="w-[52%]"></span></div>
                    </div>
                </div>
            </div>
        @elseif ($variant === 'alumno')
            {{-- Campus del alumno: módulos y progreso del curso --}}
            <div class="flex items-start gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl"
                     style="background-image: linear-gradient(135deg, #0188ee, #42c5f5)">
                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M12 14l9-5-9-5-9 5 9 5zm0 0v7m0-7l6.16-3.42A12 12 0 0112 21a12 12 0 01-6.16-10.42L12 14z"/>
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-bold ka-ink">Tu curso en marcha</p>
                    <p class="text-[0.6875rem] ka-slate">Módulos, materiales y evaluaciones por sesión</p>
                    <div class="ka-bar mt-3"><span class="w-[64%]"></span></div>
                </div>
            </div>

            <ul class="mt-4 space-y-2">
                @foreach (['Módulo 1 · Materiales y video', 'Módulo 2 · Lecturas y enlaces', 'Módulo 3 · Evaluación final'] as $modulo)
                    <li class="ka-mock-panel flex items-center gap-3 py-2.5">
                        <span class="ka-mock-dot"></span>
                        <span class="text-xs ka-slate">{{ $modulo }}</span>
                        <span class="ml-auto text-[0.625rem] font-bold uppercase tracking-wide" style="color: var(--ka-accent)">Ver</span>
                    </li>
                @endforeach
            </ul>
        @elseif ($variant === 'certificado')
            {{-- Certificado emitido: composición del PDF, sin datos de nadie --}}
            <div class="ka-mock-panel px-5 py-6 text-center">
                <p class="text-[0.625rem] font-bold uppercase tracking-[0.2em] ka-slate">Certificado</p>
                <div class="ka-bar mx-auto mt-4 w-[72%]"><span class="w-full"></span></div>
                <div class="ka-bar mx-auto mt-2 w-[54%]"><span class="w-full"></span></div>
                <div class="mt-5 flex items-center justify-center gap-2">
                    <span class="ka-mock-dot"></span>
                    <span class="text-[0.625rem] ka-slate">Código de verificación</span>
                </div>
                <div class="ka-bar mx-auto mt-3 w-[38%]"><span class="w-full"></span></div>
            </div>
        @else
            {{-- Versión compacta: para la columna estrecha del hero --}}
            <div class="space-y-2.5">
                @foreach ([72, 58, 44, 66] as $avance)
                    <div class="ka-mock-panel flex items-center gap-3 py-2.5">
                        <span class="h-6 w-6 shrink-0 rounded-lg" style="background-color: var(--ka-icon-bg)"></span>
                        <span class="ka-bar"><span class="w-[{{ $avance }}%]"></span></span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<p class="ka-mock-note flex items-start gap-2">
    <svg class="mt-0.5 h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <span>Composición ilustrativa de la interfaz. No es una captura del sistema ni muestra datos reales.</span>
</p>
