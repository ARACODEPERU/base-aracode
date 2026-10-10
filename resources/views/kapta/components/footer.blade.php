{{-- Pie del sitio KAPTA LMS --}}
@php
    $contacto = config('kapta.contacto');
    $campusUrl = config('kapta.campus_url');
    $legal = config('kapta.legal', []);
    $demoHref = $contacto['whatsapp_url'] . '?text=' . urlencode(config('kapta.mensajes.info_whatsapp'));
@endphp

<footer class="ka-surface-dark border-t" style="border-color: var(--ka-surface-line)">
    <div class="ka-container py-14 lg:py-16">
        <div class="grid grid-cols-1 gap-10 md:grid-cols-2 lg:grid-cols-4">
            {{-- Marca --}}
            <div class="lg:col-span-2">
                <a href="{{ route('kapta.home') }}" class="ka-brand" aria-label="KAPTA LMS, ir al inicio">
                    @include('kapta.components.brand', ['variant' => 'dark'])
                </a>

                <p class="ka-copy mt-5 text-sm leading-relaxed text-ka-muted">
                    Plataforma SaaS de gestión del aprendizaje: cursos, aulas virtuales, matrículas,
                    evaluaciones y certificados en un solo lugar, para academias, institutos y
                    organizaciones que capacitan a sus equipos.
                </p>

                <p class="mt-5 text-xs font-semibold uppercase tracking-wide text-ka-muted">
                    KAPTA LMS es un producto de
                    <a href="{{ $contacto['web'] }}" target="_blank" rel="noopener" class="ka-link">ARACODE Smart Solutions</a>
                </p>
            </div>

            {{-- Navegación --}}
            <div>
                <h4 class="text-sm font-bold uppercase tracking-wide text-white">Producto</h4>
                <ul class="ka-footer-links mt-4 space-y-2 text-sm">
                    <li><a href="{{ route('kapta.home') }}#funcionalidades" class="transition-colors hover:text-ka-cyan text-ka-muted">Funcionalidades</a></li>
                    <li><a href="{{ route('kapta.home') }}#producto" class="transition-colors hover:text-ka-cyan text-ka-muted">Presentación del producto</a></li>
                    <li><a href="{{ route('kapta.home') }}#soluciones" class="transition-colors hover:text-ka-cyan text-ka-muted">Soluciones</a></li>
                    <li><a href="{{ route('kapta.planes') }}" class="transition-colors hover:text-ka-cyan text-ka-muted">Planes y precios</a></li>
                    <li><a href="{{ route('kapta.home') }}#faq" class="transition-colors hover:text-ka-cyan text-ka-muted">Preguntas frecuentes</a></li>
                </ul>
            </div>

            {{-- Contacto --}}
            <div>
                <h4 class="text-sm font-bold uppercase tracking-wide text-white">Contacto comercial</h4>
                <ul class="ka-footer-links mt-4 space-y-2 text-sm text-ka-muted">
                    <li>
                        <a href="{{ $demoHref }}" target="_blank" rel="noopener" class="transition-colors hover:text-ka-cyan">
                            WhatsApp {{ $contacto['whatsapp'] }}
                        </a>
                    </li>
                    <li>
                        <a href="mailto:{{ $contacto['email'] }}" class="transition-colors hover:text-ka-cyan">
                            {{ $contacto['email'] }}
                        </a>
                    </li>
                    <li>{{ $contacto['ciudad'] }}</li>
                    @if ($campusUrl)
                        <li>
                            <a href="{{ $campusUrl }}" target="_blank" rel="noopener" class="transition-colors hover:text-ka-cyan">
                                Campus virtual
                            </a>
                        </li>
                    @endif
                </ul>

                <a href="{{ route('kapta.contacto') }}" class="ka-btn ka-btn-primary ka-btn-sm mt-5">
                    Solicitar una demo
                </a>
            </div>
        </div>
    </div>

    <div class="border-t" style="border-color: var(--ka-surface-line)">
        <div class="ka-container flex flex-col items-center justify-between gap-3 py-5 text-xs text-ka-muted sm:flex-row">
            <p>&copy; {{ date('Y') }} KAPTA LMS. Todos los derechos reservados.</p>

            <div class="ka-footer-links flex flex-wrap items-center justify-center gap-x-5 gap-y-1">
                @if (! empty($legal['privacidad']))
                    <a href="{{ $legal['privacidad'] }}" class="transition-colors hover:text-ka-cyan">Política de privacidad</a>
                @endif
                @if (! empty($legal['terminos']))
                    <a href="{{ $legal['terminos'] }}" class="transition-colors hover:text-ka-cyan">Términos y condiciones</a>
                @endif
                <a href="{{ route('kapta.contacto') }}" class="transition-colors hover:text-ka-cyan">Contacto</a>
                <a href="{{ $contacto['web'] }}" target="_blank" rel="noopener" class="transition-colors hover:text-ka-cyan">
                    aracodeperu.com
                </a>
            </div>
        </div>
    </div>
</footer>
