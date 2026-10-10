{{--
    Acceso al sistema existente.

    Reutiliza la misma URL que el botón de la cabecera: no hay una segunda
    configuración que se pueda quedar desincronizada.

    Aquí no se pide ninguna credencial ni se imita una pantalla de login: si la
    URL todavía no está configurada, el botón queda en estado no disponible con
    su indicación.
--}}
@php
    $acceso = config('kirafact.acceso');
@endphp

<section id="acceso" class="kf-surface-light">
    <div class="kf-container kf-section-tight">
        <div class="kf-card kf-reveal items-center gap-8 text-center lg:flex-row lg:justify-between lg:text-left">
            <div>
                <h2 class="kf-h2">{{ $acceso['titulo'] }}</h2>
                <p class="kf-lead kf-copy mt-3">{{ $acceso['texto'] }}</p>
            </div>

            <div class="shrink-0">
                @include('kirafact.components.btn-ingresar', ['class' => 'kf-btn-primary kf-btn-lg'])

                @unless (config('kirafact.login_url'))
                    <p class="kf-slate mt-4 max-w-sm text-xs leading-relaxed">
                        {{ config('kirafact.mensajes.acceso_pendiente') }}
                    </p>
                @endunless
            </div>
        </div>
    </div>
</section>
