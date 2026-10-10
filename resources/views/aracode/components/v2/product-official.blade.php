{{--
    Ficha del vendedor: cierre de las páginas de producto del sitio corporativo
    --------------------------------------------------------------------------
    /kapta, /kirafact y /pichanguero NO son la web del producto: son la ficha
    que ARACODE publica en su propio sitio. Esta sección cierra la página con
    lo que solo el corporativo puede decir —quién desarrolla el producto y
    dónde está su web oficial— y manda al visitante al sitio del producto, que
    es quien publica funcionalidades, planes y descargas.

    Reparto de intenciones y procedimiento del día que un producto pase a su
    propio dominio: docs/SEO_ECOSISTEMA_PRODUCTOS.md.

    Las URLs no se escriben aquí: salen de config/productos.php.

    El enlace saliente abre en pestaña nueva, igual que en /soluciones: el
    destino es otra web, con su propia navegación y su propio logotipo, y quien
    navega no debería perder la página desde la que salió. Se avisa con texto
    solo para lectores de pantalla.
--}}
@use('App\Support\SitiosProducto')

@props([
    'producto',
    'fondo' => 'gris',
    'nota' => null,
    // Segunda página del producto que la ficha quiera ofrecer (planes,
    // descargas...). Llega ya resuelta desde config/productos.php.
    'paginaSecundaria' => null,
    'etiquetaSecundaria' => null,
])

@php
    $nombre = SitiosProducto::nombre($producto);
    $sitio = SitiosProducto::url($producto);
    $fondoClase = $fondo === 'claro' ? 'bg-white' : 'bg-ara-slate-50';
@endphp

@if ($sitio)
    <section class="py-20 lg:py-28 {{ $fondoClase }}">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">

                {{-- Sitio oficial del producto --}}
                <div class="reveal">
                    <span class="ara-badge ara-badge-blue mb-6 inline-block">Sitio oficial</span>
                    <h2 class="text-3xl sm:text-4xl font-bold text-ara-slate-700 mb-4">
                        {{ $nombre }} tiene su <span class="text-gradient">web propia</span>
                    </h2>
                    <p class="text-ara-slate-400 leading-relaxed mb-8">
                        {{ $nota ?? 'La información vigente del producto —funcionalidades, condiciones y descargas— vive en su propio sitio web.' }}
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4">
                        {{-- flex-shrink-0: sin esto el botón se comprime en la fila
                             de sm en adelante y su texto se parte en dos líneas. --}}
                        <a href="{{ $sitio }}" class="ara-btn ara-btn-primary ara-btn-lg flex-shrink-0" target="_blank" rel="noopener">
                            Ir al sitio de {{ $nombre }}
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                            <span class="sr-only">(se abre en una pestaña nueva)</span>
                        </a>

                        @if ($paginaSecundaria && $etiquetaSecundaria)
                            <a href="{{ $paginaSecundaria }}" class="ara-btn ara-btn-ghost ara-btn-lg flex-shrink-0" target="_blank" rel="noopener">
                                {{ $etiquetaSecundaria }}
                                <span class="sr-only">(se abre en una pestaña nueva)</span>
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Quién está detrás del producto --}}
                <div class="ara-card reveal reveal-delay-1">
                    <span class="ara-badge ara-badge-green mb-6 inline-block">Producto de ARACODE</span>
                    <h3 class="text-xl font-bold text-ara-slate-700 mb-4">
                        Detrás de {{ $nombre }} está ARACODE Smart Solutions
                    </h3>

                    @php
                        $compromisos = [
                            'Es un producto de ARACODE, la empresa peruana que lo desarrolla y lo mantiene al día.',
                            'Las demostraciones, las condiciones comerciales y el soporte los atiende el equipo de ARACODE.',
                            'Forma parte del ecosistema de soluciones que ARACODE integra para las empresas del Perú.',
                        ];
                    @endphp

                    <ul class="space-y-3 mb-8">
                        @foreach ($compromisos as $compromiso)
                            <li class="flex items-start gap-2 text-ara-slate-500">
                                <svg class="w-5 h-5 text-ara-green flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span>{{ $compromiso }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <div class="flex flex-wrap gap-x-6 gap-y-2">
                        <a href="{{ route('soluciones') }}" class="text-ara-blue font-semibold hover:underline transition-colors">
                            Ver todo el ecosistema
                        </a>
                        <a href="{{ route('empresa') }}" class="text-ara-blue font-semibold hover:underline transition-colors">
                            Sobre ARACODE
                        </a>
                        <a href="{{ route('contacto') }}" class="text-ara-blue font-semibold hover:underline transition-colors">
                            Hablar con el equipo
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </section>
@endif
