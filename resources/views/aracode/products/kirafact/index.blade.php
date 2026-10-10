@extends('aracode.layouts.webpage')

@section('meta_title', 'KiraFact | Facturación electrónica desarrollada por ARACODE')
@section('meta_description', 'KiraFact es el sistema de facturación electrónica y gestión comercial de ARACODE Smart Solutions. Conoce qué resuelve en tu empresa y coordina una asesoría.')

@php
    /*
        Ficha de KIRAFACT en el sitio corporativo.

        Reparto de intenciones: esta página habla del PRODUCTO COMO PRODUCTO DE
        ARACODE (quién lo desarrolla, qué resuelve en una empresa, con quién se
        coordina una asesoría). El detalle funcional y las condiciones vigentes
        los publica el sitio del producto, que es su web oficial.

        Los enlaces al sitio oficial no se escriben aquí: salen de
        config/productos.php, el único archivo que se edita el día que KIRAFACT
        pase a su propio dominio. Procedimiento completo en
        docs/SEO_ECOSISTEMA_PRODUCTOS.md.
    */
    $sitioKirafact = \App\Support\SitiosProducto::url('kirafact');
    $planesKirafact = \App\Support\SitiosProducto::planes('kirafact');
@endphp

@push('json-ld')
{{-- `@@` escapa la directiva @context de Blade, igual que en el layout. --}}
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "SoftwareApplication",
    "name": "KiraFact",
    "applicationCategory": "BusinessApplication",
    "operatingSystem": "Web",
    "inLanguage": "es-PE",
    "description": "Sistema de facturación electrónica y gestión comercial para empresas: emisión de comprobantes, integración con SUNAT, inventario, ventas y reportes.",
    "url": "{{ $sitioKirafact }}",
    "mainEntityOfPage": "{{ route('solucion_facturacion') }}",
    "author": {
        "@@type": "Organization",
        "name": "ARACODE Smart Solutions",
        "url": "{{ url('/') }}"
    },
    "publisher": {
        "@@type": "Organization",
        "name": "ARACODE Smart Solutions",
        "url": "{{ url('/') }}"
    }
}
</script>
@endpush

@section('content')
    @include('aracode.components.v2.navbar')

    {{-- Hero --}}
    <section class="pt-32 pb-20 bg-ara-navy relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-ara-blue/10 rounded-full filter blur-3xl pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <span class="ara-badge ara-badge-blue mb-6 inline-block reveal">Producto de ARACODE</span>
                    <h1 class="text-4xl sm:text-5xl font-bold text-white mb-6 reveal reveal-delay-1">
                        Kira<span class="text-gradient">Fact</span>
                    </h1>
                    <p class="text-lg text-white/70 mb-8 reveal reveal-delay-2">
                        KiraFact es el sistema de facturación electrónica y gestión comercial que ARACODE Smart Solutions desarrolla para las empresas del Perú: ventas, inventario y comprobantes electrónicos con cumplimiento ante SUNAT.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 reveal reveal-delay-3">
                        <a href="{{ route('contacto') }}" class="ara-btn ara-btn-primary ara-btn-lg">
                            Solicitar Asesoría
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </a>
                        @if ($planesKirafact)
                            <a href="{{ $planesKirafact }}" class="ara-btn ara-btn-secondary ara-btn-lg" target="_blank" rel="noopener">
                                Ver Planes
                                <span class="sr-only">(se abre en una pestaña nueva)</span>
                            </a>
                        @endif
                    </div>
                </div>
                <div class="hidden lg:block reveal reveal-delay-4">
                    <img src="{{ asset('themes/webpage/images/misc/s2.jpg') }}" alt="KiraFact" class="rounded-2xl shadow-2xl w-full" loading="lazy">
                </div>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section class="py-20 lg:py-28 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-v2.section-heading
                badge="Características"
                title="Todo lo que necesitas para facturar"
                subtitle="Sistema completo, seguro y en cumplimiento con la normativa SUNAT."
                :light="false"
            />

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @php
                    $features = [
                        [
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>',
                            'title' => 'Comprobantes Electrónicos',
                            'description' => 'Emisión de facturas, boletas, notas de crédito y débito en formato electrónico.',
                        ],
                        [
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>',
                            'title' => 'Integración SUNAT',
                            'description' => 'Conexión directa con SUNAT para validación y envío automático de comprobantes.',
                        ],
                        [
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>',
                            'title' => 'Panel Comercial',
                            'description' => 'Dashboard con reportes de ventas, estadísticas y análisis de rendimiento.',
                        ],
                        [
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
                            'title' => 'Automatización',
                            'description' => 'Generación automática de XML y PDF, validaciones y alertas inteligentes.',
                        ],
                        [
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>',
                            'title' => 'Seguridad',
                            'description' => 'Cifrado de datos, respaldos automáticos y cumplimiento normativo.',
                        ],
                        [
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
                            'title' => 'Multiusuario',
                            'description' => 'Gestión de usuarios con roles y permisos para tu equipo comercial.',
                        ],
                    ];
                @endphp

                @foreach($features as $index => $feature)
                    <div class="ara-card reveal reveal-delay-{{ $index + 1 }}">
                        <div class="ara-icon-box mb-4">
                            {!! $feature['icon'] !!}
                        </div>
                        <h3 class="text-xl font-bold text-ara-slate-700 mb-3">{{ $feature['title'] }}</h3>
                        <p class="text-ara-slate-400 leading-relaxed">{{ $feature['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{--
        Antes aquí vivían tres tarjetas de planes con importes (S/ 35, S/ 50 y
        S/ 80) que config/kirafact.php declara como TARIFAS DE REFERENCIA, SIN
        PUBLICAR: la ficha corporativa publicaba precios que el propio producto
        todavía no confirma. Ahora KiraFact no publica importes en ninguna de sus
        dos páginas: las condiciones se confirman con el equipo comercial, y para
        eso enlaza al sitio oficial.
    --}}
    <x-v2.product-official
        producto="kirafact"
        :paginaSecundaria="$planesKirafact"
        etiquetaSecundaria="Planes y condiciones"
        nota="Las funcionalidades y las condiciones vigentes están en la web de KIRAFACT. Las tarifas se confirman con el equipo comercial según el uso que le dará tu empresa, así que escríbenos y te enviamos la propuesta."
    />

    <x-v2.cta-section 
        title="¿Quieres probar KiraFact?"
        subtitle="Contáctanos hoy y comienza a emitir comprobantes electrónicos en cumplimiento con SUNAT."
        buttonText="Solicitar Asesoría"
    />

    @include('aracode.components.v2.footer')
@endsection
