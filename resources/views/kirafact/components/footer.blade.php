{{--
    Pie de página del sitio KIRAFACT.

    - La marca KIRAFACT va con su descriptor y la firma de ARACODE.
    - El logotipo de ARACODE es el archivo oficial del proyecto y se usa tal
      cual, sin recolorear, sobre el fondo azul marino.
    - Solo se enlazan los datos reales de contacto y las páginas legales que
      existan en config('kirafact.legal').
--}}
@php
    $marca = config('kirafact.marca');
    $contacto = config('kirafact.contacto');
    $legal = config('kirafact.legal');
    $inicio = route('kirafact.home');
    $anio = date('Y');
@endphp

<footer class="kf-surface-dark border-t" style="border-color: var(--kf-surface-line)">
    <div class="kf-container py-14 lg:py-16">
        <div class="grid grid-cols-1 gap-10 md:grid-cols-2 lg:grid-cols-[1.5fr_1fr_1fr_1.1fr]">

            {{-- Marca --}}
            <div>
                <a href="{{ $inicio }}" class="kf-brand" aria-label="KIRAFACT — inicio">
                    @include('kirafact.components.marca', ['superficie' => 'oscura', 'conDescriptor' => true])
                </a>
                <p class="kf-slate mt-5 max-w-sm text-sm leading-relaxed">
                    KIRAFACT es un software de facturación electrónica y gestión empresarial
                    desarrollado por ARACODE Smart Solutions para empresas del Perú.
                </p>
                <p class="kf-slate mt-5 text-xs font-semibold uppercase tracking-wide">
                    {{ $marca['firma'] }}
                </p>
                <a href="{{ $marca['web_aracode'] }}" target="_blank" rel="noopener"
                   class="mt-5 inline-flex items-center gap-3">
                    <img src="{{ asset($marca['logo_aracode']) }}"
                         alt="{{ $marca['empresa'] }}"
                         style="height: {{ $marca['logo_aracode_alto'] }}px"
                         height="{{ $marca['logo_aracode_alto'] }}"
                         width="{{ (int) round($marca['logo_aracode_alto'] * 5.4) }}"
                         loading="lazy" decoding="async">
                </a>
            </div>

            {{-- Enlaces internos --}}
            <nav aria-label="Enlaces del sitio">
                <h2 class="text-sm font-bold uppercase tracking-wide">Sitio</h2>
                <ul class="kf-footer-links mt-4 space-y-1.5 text-sm">
                    <li><a href="{{ $inicio }}" class="kf-slate transition-colors hover:text-white">Inicio</a></li>
                    <li><a href="{{ $inicio }}#funcionalidades" class="kf-slate transition-colors hover:text-white">Funcionalidades</a></li>
                    <li><a href="{{ $inicio }}#beneficios" class="kf-slate transition-colors hover:text-white">Beneficios</a></li>
                    <li><a href="{{ $inicio }}#preguntas" class="kf-slate transition-colors hover:text-white">Preguntas frecuentes</a></li>
                    <li><a href="{{ route('kirafact.planes') }}" class="kf-slate transition-colors hover:text-white">Planes</a></li>
                    <li><a href="{{ route('kirafact.contacto') }}" class="kf-slate transition-colors hover:text-white">Contacto</a></li>
                </ul>
            </nav>

            {{-- Producto --}}
            <div>
                <h2 class="text-sm font-bold uppercase tracking-wide">Producto</h2>
                <ul class="kf-footer-links mt-4 space-y-1.5 text-sm">
                    <li>
                        <a href="{{ $contacto['whatsapp_url'] }}?text={{ rawurlencode(config('kirafact.mensajes.demo_whatsapp')) }}"
                           target="_blank" rel="noopener"
                           class="kf-slate inline-flex items-center gap-2 transition-colors hover:text-white">
                            @include('kirafact.components.icon', ['icono' => 'whatsapp', 'iconoClase' => 'h-4 w-4'])
                            <span>{{ config('kirafact.demo.boton') }}</span>
                        </a>
                    </li>
                    <li><a href="{{ $inicio }}#software" class="kf-slate transition-colors hover:text-white">Cómo se trabaja</a></li>
                    <li><a href="{{ $inicio }}#demo" class="kf-slate transition-colors hover:text-white">Cómo se coordina la demo</a></li>
                </ul>
            </div>

            {{-- Contacto (solo datos reales y verificados) --}}
            <div>
                <h2 class="text-sm font-bold uppercase tracking-wide">Contacto</h2>
                <ul class="kf-footer-links mt-4 space-y-1.5 text-sm">
                    <li>
                        <a href="{{ $contacto['whatsapp_url'] }}?text={{ rawurlencode(config('kirafact.mensajes.info_whatsapp')) }}"
                           target="_blank" rel="noopener"
                           class="kf-slate inline-flex items-center gap-2 transition-colors hover:text-white">
                            @include('kirafact.components.icon', ['icono' => 'whatsapp', 'iconoClase' => 'h-4 w-4'])
                            <span>{{ $contacto['whatsapp'] }}</span>
                        </a>
                    </li>
                    <li>
                        <a href="mailto:{{ $contacto['email'] }}"
                           class="kf-slate inline-flex items-center gap-2 transition-colors hover:text-white">
                            @include('kirafact.components.icon', ['icono' => 'correo', 'iconoClase' => 'h-4 w-4'])
                            <span>{{ $contacto['email'] }}</span>
                        </a>
                    </li>
                    <li class="kf-slate flex items-center gap-2">
                        @include('kirafact.components.icon', ['icono' => 'ubicacion', 'iconoClase' => 'h-4 w-4'])
                        <span>{{ $contacto['ciudad'] }}</span>
                    </li>
                    <li>
                        <a href="{{ $contacto['web'] }}" target="_blank" rel="noopener"
                           class="kf-slate inline-flex items-center gap-2 transition-colors hover:text-white">
                            @include('kirafact.components.icon', ['icono' => 'externo', 'iconoClase' => 'h-4 w-4'])
                            <span>aracodeperu.com</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="border-t" style="border-color: var(--kf-surface-line)">
        <div class="kf-container flex flex-col items-start justify-between gap-3 py-5 text-xs sm:flex-row sm:items-center">
            <p class="kf-slate">
                &copy; {{ $anio }} KIRAFACT. Todos los derechos reservados.
                Desarrollado por {{ $marca['empresa'] }}.
            </p>

            @if ($legal['privacidad'] || $legal['terminos'])
                <div class="flex items-center gap-5">
                    @if ($legal['privacidad'])
                        <a href="{{ $legal['privacidad'] }}" class="kf-slate transition-colors hover:text-white">Política de privacidad</a>
                    @endif
                    @if ($legal['terminos'])
                        <a href="{{ $legal['terminos'] }}" class="kf-slate transition-colors hover:text-white">Términos legales</a>
                    @endif
                </div>
            @endif
        </div>
    </div>
</footer>
