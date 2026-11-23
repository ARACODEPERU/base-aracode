@extends('aracode.layouts.webpage')

@section('meta_title', ($landing->title ?? 'Curso gratis') . ' | ARACODE')
@section('meta_description', $landing->description_short ?? 'Regístrate y accede a un curso gratis del Académico ARACODE.')

@push('head')
    <style>
        /* Landing de cursos gratis: estilos propios para no depender de las
           hojas del panel y mantener el formulario legible en móvil. */
        .fcl-wrap { max-width: 1080px; margin: 0 auto; padding: 0 1rem; }
        .fcl-hero {
            background: linear-gradient(135deg, #012a52 0%, #014b8f 55%, #0188ee 100%);
            /* La navbar pública va fija: el hero arranca debajo de ella. */
            padding-top: 7.5rem;
        }
        .fcl-title { font-size: clamp(1.75rem, 4vw, 2.75rem); line-height: 1.15; letter-spacing: -0.02em; }
        .fcl-card { background: #fff; border: 1px solid #e6edf5; border-radius: 20px; box-shadow: 0 24px 60px -30px rgba(2, 42, 82, .35); }
        .fcl-label { display: block; font-size: .8rem; font-weight: 600; color: #334155; margin-bottom: .35rem; }
        .fcl-input {
            width: 100%; border: 1px solid #cbd5e1; border-radius: 12px; padding: .7rem .85rem;
            font-size: .95rem; color: #0f172a; background: #fff; transition: border-color .15s, box-shadow .15s;
        }
        .fcl-input:focus-visible { outline: none; border-color: #0188ee; box-shadow: 0 0 0 4px rgba(1, 136, 238, .16); }
        .fcl-paso {
            display: inline-flex; align-items: center; gap: .5rem; font-size: .8rem; font-weight: 700;
            color: #64748b; padding: .4rem .75rem; border-radius: 999px; background: #f1f5f9;
        }
        .fcl-paso[data-activo="1"] { background: rgba(1, 136, 238, .12); color: #0165ab; }
        .fcl-paso-num {
            display: inline-flex; align-items: center; justify-content: center; width: 1.4rem; height: 1.4rem;
            border-radius: 999px; background: #fff; color: inherit; font-size: .75rem; font-weight: 800;
        }
        .fcl-tema {
            display: flex; gap: .7rem; align-items: flex-start; padding: .8rem .9rem;
            border: 1px solid #dbe4ee; border-radius: 14px; cursor: pointer; background: #fff;
            transition: border-color .15s, background-color .15s, box-shadow .15s;
        }
        .fcl-tema:hover { border-color: #9ec9ec; background: #f8fbff; }
        .fcl-tema:has(input:checked) { border-color: #0188ee; background: #f1f8ff; box-shadow: 0 0 0 1px #0188ee inset; }
        .fcl-tema input { margin-top: .15rem; width: 1.05rem; height: 1.05rem; accent-color: #0188ee; flex: none; }
        /* Al llegar al cupo, las casillas que sobran se ven apagadas. */
        .fcl-tema:has(input:disabled) { opacity: .5; cursor: not-allowed; }
        .fcl-tema span { font-size: .9rem; color: #1e293b; line-height: 1.35; }
        .fcl-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
            border-radius: 12px; padding: .8rem 1.25rem; font-weight: 700; font-size: .9rem;
            transition: transform .12s, box-shadow .15s, background-color .15s;
        }
        .fcl-btn-primary { background: #0188ee; color: #fff; box-shadow: 0 12px 24px -12px rgba(1, 136, 238, .8); }
        .fcl-btn-primary:hover { background: #0175ce; }
        .fcl-btn-primary:active { transform: translateY(1px); }
        .fcl-btn-ghost { background: #fff; color: #334155; border: 1px solid #cbd5e1; }
        .fcl-btn-ghost:hover { background: #f8fafc; }
        .fcl-btn:focus-visible { outline: 3px solid rgba(1, 136, 238, .45); outline-offset: 2px; }
        .fcl-alerta { border-radius: 14px; padding: .85rem 1rem; font-size: .875rem; }
        .fcl-alerta-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        @media (prefers-reduced-motion: reduce) {
            .fcl-btn, .fcl-input, .fcl-tema { transition: none; }
        }
    </style>
@endpush

@section('content')
    @include('aracode.components.v2.navbar')

                <section class="fcl-hero pb-14 pt-28 text-white sm:pb-20 sm:pt-32">
                    <div class="fcl-wrap grid gap-10 lg:grid-cols-2 lg:items-center">
                        <div>
                            <p class="mb-3 inline-flex rounded-full bg-white/15 px-3 py-1 text-xs font-bold uppercase tracking-wider">
                                Curso gratis
                            </p>
                            <h1 class="fcl-title font-extrabold">{{ $landing->title ?? 'Aprende con nosotros' }}</h1>

                            @if (!empty($landing->description_short))
                                <p class="mt-4 max-w-xl text-base text-white/90">{{ $landing->description_short }}</p>
                            @endif

                            @if (!empty($landing->date_start) && !empty($landing->date_end))
                                <p class="mt-5 inline-flex rounded-lg bg-white/15 px-3 py-2 text-sm font-semibold">
                                    Promoción válida del {{ \Illuminate\Support\Carbon::parse($landing->date_start)->format('d/m/Y') }}
                                    al {{ \Illuminate\Support\Carbon::parse($landing->date_end)->format('d/m/Y') }}
                                </p>
                            @endif
                        </div>

                        @if (!empty($landing->main_image))
                            <div class="lg:justify-self-end">
                                <img
                                    src="{{ asset('storage/' . $landing->main_image) }}"
                                    alt="{{ $landing->title ?? 'Curso gratis' }}"
                                    class="w-full max-w-md rounded-2xl border border-white/20 shadow-2xl"
                                    loading="lazy"
                                >
                            </div>
                        @endif
                    </div>
                </section>

                <section class="bg-[#f4f8fd] py-12">
                    <div class="fcl-wrap">
                        @if (!empty($landing->description_long))
                            <div class="mx-auto mb-10 max-w-3xl text-[15px] leading-relaxed text-slate-700">
                                {!! $landing->description_long !!}
                            </div>
                        @endif

                        <div class="fcl-card mx-auto max-w-3xl p-5 sm:p-8">
                            @if (!empty($preview))
                                {{-- Aviso de vista previa: el formulario no guarda nada. --}}
                                <div
                                    class="mb-5 rounded-xl border px-4 py-3 text-sm"
                                    style="background:#eef6ff;border-color:#bfdcff;color:#0b4a80"
                                    role="status"
                                >
                                    <strong>Vista previa.</strong>
                                    Así verá el visitante este landing. El formulario no guarda datos desde aquí.
                                </div>
                            @endif

                            @if ($enviado)
                                {{-- Confirmación después de enviar el formulario --}}
                                <div class="text-center">
                                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-[#4eae33]/15">
                                        <svg class="h-7 w-7 text-[#3f8f27]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <h2 class="text-xl font-extrabold text-slate-900 sm:text-2xl">¡Listo, ya estás registrado!</h2>
                                    <p class="mx-auto mt-2 max-w-lg text-sm text-slate-600">
                                        Guardamos tu curso gratis y los temas que elegiste. Te contactaremos a
                                        <strong>{{ old('email', 'tu correo') }}</strong> con el acceso.
                                    </p>
                                    <a href="{{ route('cms_free_course_landing', $landing->menu_id) }}" class="fcl-btn fcl-btn-ghost mt-6">
                                        Registrar a otra persona
                                    </a>
                                </div>
                            @else
                                @if ($errors->any())
                                    <div class="fcl-alerta fcl-alerta-error mb-6" role="alert">
                                        <p class="font-bold">Revisa el formulario:</p>
                                        <ul class="mt-1 list-disc pl-5">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if ($cursos->isEmpty())
                                    <div class="fcl-alerta fcl-alerta-error" role="alert">
                                        Este landing todavía no tiene cursos gratis configurados. Vuelve pronto.
                                    </div>
                                @else
                                    <div class="mb-6 flex flex-wrap items-center gap-2">
                                        <span class="fcl-paso" data-paso-indicador="1" data-activo="1">
                                            <span class="fcl-paso-num">1</span> Tus datos
                                        </span>
                                        <span class="fcl-paso" data-paso-indicador="2">
                                            <span class="fcl-paso-num">2</span> Tus temas
                                        </span>
                                    </div>

                                    <form
                                        id="fclForm"
                                        method="POST"
                                        action="{{ route('cms_free_course_landing_store', $landing->menu_id) }}"
                                        data-preview="{{ !empty($preview) ? '1' : '0' }}"
                                        novalidate
                                    >
                                        @csrf

                                        {{-- Atribución de los enlaces UTM (los rellena traffic-tracking.js) --}}
                                        {{-- landing_url no se toma del tracking: se guarda la url de este landing. --}}
                                        @foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'utm_id', 'fbclid', 'gclid', 'referer', 'traffic_source'] as $campo)
                                            <input type="hidden" name="{{ $campo }}" data-utm="{{ $campo }}" value="{{ old($campo) }}">
                                        @endforeach

                                        {{-- PASO 1: datos y curso gratis elegido --}}
                                        <fieldset data-paso="1" class="space-y-4">
                                            <legend class="mb-3 text-lg font-bold text-slate-900">
                                                {{ $landing->additional_field1 ?: 'Paso 1: déjanos tus datos' }}
                                            </legend>

                                            <div>
                                                <label class="fcl-label" for="full_name">Nombre completo</label>
                                                <input
                                                    id="full_name"
                                                    name="full_name"
                                                    type="text"
                                                    class="fcl-input"
                                                    value="{{ old('full_name') }}"
                                                    autocomplete="name"
                                                    required
                                                >
                                            </div>

                                            <div class="grid gap-4 sm:grid-cols-2">
                                                <div>
                                                    <label class="fcl-label" for="email">Correo electrónico</label>
                                                    <input
                                                        id="email"
                                                        name="email"
                                                        type="email"
                                                        class="fcl-input"
                                                        value="{{ old('email') }}"
                                                        autocomplete="email"
                                                        required
                                                    >
                                                </div>
                                                <div>
                                                    <label class="fcl-label" for="phone">Teléfono / WhatsApp</label>
                                                    <input
                                                        id="phone"
                                                        name="phone"
                                                        type="tel"
                                                        class="fcl-input"
                                                        value="{{ old('phone') }}"
                                                        autocomplete="tel"
                                                        inputmode="tel"
                                                        placeholder="999 999 999"
                                                        required
                                                    >
                                                </div>
                                            </div>

                                            <div>
                                                <p class="fcl-label">Cursos gratis que quieres llevar</p>
                                                <p class="mb-2 text-sm text-slate-600">
                                                    @if ($cupo === 1)
                                                        Elige 1 curso de la lista.
                                                    @else
                                                        Puedes elegir hasta {{ $cupo }} cursos de la lista.
                                                    @endif
                                                </p>

                                                <div
                                                    id="fclCursos"
                                                    class="grid gap-2.5 sm:grid-cols-2"
                                                    data-cupo="{{ $cupo }}"
                                                >
                                                    @foreach ($cursos as $curso)
                                                        <label class="fcl-tema" for="curso_{{ $curso->id }}">
                                                            <input
                                                                id="curso_{{ $curso->id }}"
                                                                type="checkbox"
                                                                name="courses[]"
                                                                value="{{ $curso->id }}"
                                                                data-curso
                                                                @checked(in_array((string) $curso->id, array_map('strval', old('courses', [])), true))
                                                            >
                                                            <span>{{ $curso->description }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>

                                                <p id="fclCupoTexto" class="mt-2 text-xs text-slate-500"></p>
                                                <p id="fclCursosError" class="fcl-alerta fcl-alerta-error mt-2" role="alert" hidden>
                                                    Elige al menos un curso gratis.
                                                </p>
                                            </div>

                                            <div class="flex justify-end pt-2">
                                                <button type="button" class="fcl-btn fcl-btn-primary" data-siguiente>
                                                    Continuar
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </fieldset>

                                        {{-- PASO 2: temas de interés (se puede marcar más de uno) --}}
                                        <fieldset data-paso="2" class="space-y-4" hidden>
                                            <legend class="mb-1 text-lg font-bold text-slate-900">
                                                {{ $landing->additional_field2 ?: 'Paso 2: ¿qué temas te interesan?' }}
                                            </legend>
                                            <p class="text-sm text-slate-600">Puedes marcar uno o varios temas.</p>

                                            <div class="grid gap-2.5 sm:grid-cols-2">
                                                @foreach ($temas as $tema)
                                                    <label class="fcl-tema" for="tema_{{ $tema['clave'] }}">
                                                        <input
                                                            id="tema_{{ $tema['clave'] }}"
                                                            type="checkbox"
                                                            name="interests[]"
                                                            value="{{ $tema['clave'] }}"
                                                            @checked(in_array($tema['clave'], old('interests', []), true))
                                                        >
                                                        <span>{{ $tema['etiqueta'] }}</span>
                                                    </label>
                                                @endforeach
                                            </div>

                                            <p id="fclTemasError" class="fcl-alerta fcl-alerta-error" role="alert" hidden>
                                                Marca al menos un tema de tu interés.
                                            </p>

                                            <p
                                                id="fclPreviewAviso"
                                                class="fcl-alerta"
                                                style="background:#eef6ff;border:1px solid #bfdcff;color:#0b4a80"
                                                role="status"
                                                hidden
                                            >
                                                Vista previa: no se guardó ningún dato.
                                            </p>

                                            <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                                                <button type="button" class="fcl-btn fcl-btn-ghost" data-anterior>
                                                    Atrás
                                                </button>
                                                <button type="submit" class="fcl-btn fcl-btn-primary">
                                                    Quiero mi curso gratis
                                                </button>
                                            </div>
                                        </fieldset>
                                    </form>
                                @endif
                            @endif
                        </div>
                    </div>
                </section>

    @include('aracode.components.v2.footer')
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const formulario = document.getElementById('fclForm');

            if (!formulario) {
                return;
            }

            const pasos = formulario.querySelectorAll('[data-paso]');
            const indicadores = document.querySelectorAll('[data-paso-indicador]');
            const errorTemas = document.getElementById('fclTemasError');

            const cursos = formulario.querySelectorAll('[data-curso]');
            const cajaCursos = document.getElementById('fclCursos');
            const cupo = Number(cajaCursos?.dataset.cupo || 1);
            const errorCursos = document.getElementById('fclCursosError');
            const textoCupo = document.getElementById('fclCupoTexto');

            // El cupo es el maximo de cursos gratis por persona: al llegar al
            // limite se bloquean las casillas que sobran, pero la lista sigue
            // mostrando todos los cursos configurados.
            const refrescarCursos = function () {
                const marcados = Array.from(cursos).filter(function (curso) { return curso.checked; }).length;

                cursos.forEach(function (curso) {
                    curso.disabled = !curso.checked && marcados >= cupo;
                });

                if (textoCupo) {
                    textoCupo.textContent = 'Elegiste ' + marcados + ' de ' + cupo + ' curso(s) gratis.';
                }

                if (marcados > 0 && errorCursos) {
                    errorCursos.hidden = true;
                }
            };

            cursos.forEach(function (curso) {
                curso.addEventListener('change', refrescarCursos);
            });

            refrescarCursos();

            // Muestra un paso y apaga el otro.
            const mostrarPaso = function (numero) {
                pasos.forEach(function (paso) {
                    paso.hidden = paso.dataset.paso !== String(numero);
                });

                indicadores.forEach(function (indicador) {
                    if (indicador.dataset.pasoIndicador === String(numero)) {
                        indicador.dataset.activo = '1';
                    } else {
                        delete indicador.dataset.activo;
                    }
                });

                const primero = formulario.querySelector('[data-paso="' + numero + '"] input, [data-paso="' + numero + '"] select');

                if (primero) {
                    primero.focus({ preventScroll: true });
                }
            };

            // El paso 2 solo se abre si el paso 1 está completo.
            formulario.querySelector('[data-siguiente]')?.addEventListener('click', function () {
                const paso1 = formulario.querySelector('[data-paso="1"]');
                const campos = paso1.querySelectorAll('input[required], select[required]');
                let valido = true;

                if (Array.from(cursos).filter(function (curso) { return curso.checked; }).length === 0) {
                    errorCursos.hidden = false;
                    cursos[0]?.focus();
                    valido = false;
                }

                campos.forEach(function (campo) {
                    if (!campo.checkValidity()) {
                        campo.reportValidity();
                        valido = false;
                    }
                });

                if (valido) {
                    mostrarPaso(2);
                }
            });

            formulario.querySelector('[data-anterior]')?.addEventListener('click', function () {
                mostrarPaso(1);
            });

            // En vista previa nunca se envia: el formulario es solo para mirar.
            // Sin temas marcados tampoco se envia: el aviso queda junto a la lista.
            formulario.addEventListener('submit', function (evento) {
                if (formulario.dataset.preview === '1') {
                    evento.preventDefault();
                    document.getElementById('fclPreviewAviso').hidden = false;
                    return;
                }

                const marcados = formulario.querySelectorAll('input[name="interests[]"]:checked');

                if (marcados.length === 0) {
                    evento.preventDefault();
                    errorTemas.hidden = false;
                    formulario.querySelector('input[name="interests[]"]')?.focus();
                }
            });

            formulario.querySelectorAll('input[name="interests[]"]').forEach(function (check) {
                check.addEventListener('change', function () {
                    errorTemas.hidden = true;
                });
            });

            // Si el servidor devolvió un error de temas, se abre el paso 2.
            if (@json($errors->has('interests'))) {
                mostrarPaso(2);
            }

            // Atribución: lo que traffic-tracking.js guardó en el navegador.
            try {
                const tracking = JSON.parse(localStorage.getItem('traffic_tracking') || '{}');

                Object.keys(tracking).forEach(function (clave) {
                    const campo = formulario.querySelector('[data-utm="' + clave + '"]');

                    if (campo && !campo.value && tracking[clave]) {
                        campo.value = tracking[clave];
                    }
                });
            } catch (e) {
                // Sin datos de atribución el formulario funciona igual.
            }
        });
    </script>
@endpush
