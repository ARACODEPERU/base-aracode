@extends('aracode.layouts.webpage')

@section('meta_title', 'KAPTA LMS | Plataforma e-learning desarrollada por ARACODE')
@section('meta_description', 'KAPTA LMS es un producto de ARACODE Smart Solutions: conoce qué resuelve en una institución, cómo se implementa y con quién coordinar una demostración.')

@php
    /*
        Ficha de KAPTA en el sitio corporativo.

        Reparto de intenciones: esta página habla del PRODUCTO COMO PRODUCTO DE
        ARACODE (quién lo desarrolla, cómo se implementa, con quién se coordina
        una demo). Los planes, los precios y las funcionalidades al detalle los
        publica el sitio del producto, que es su web oficial.

        El nombre comercial y los enlaces al sitio oficial no se escriben aquí:
        salen de config/productos.php, el único archivo que se edita el día que
        KAPTA pase a su propio dominio. Procedimiento completo en
        docs/SEO_ECOSISTEMA_PRODUCTOS.md.
    */
    $nombreKapta = \App\Support\SitiosProducto::nombre('kapta');
    $sitioKapta = \App\Support\SitiosProducto::url('kapta');
    $planesKapta = \App\Support\SitiosProducto::planes('kapta');
@endphp

@push('json-ld')
{{-- `@@` escapa la directiva @context de Blade, igual que en el layout. --}}
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "SoftwareApplication",
    "name": "{{ $nombreKapta }}",
    "applicationCategory": "EducationalApplication",
    "operatingSystem": "Web",
    "inLanguage": "es-PE",
    "description": "Plataforma de gestión del aprendizaje para instituciones, academias y empresas: cursos, aulas virtuales, matrículas, evaluaciones y certificados.",
    "url": "{{ $sitioKapta }}",
    "mainEntityOfPage": "{{ route('solucion_kapta') }}",
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
        <div class="absolute top-0 right-0 w-96 h-96 bg-ara-green/10 rounded-full filter blur-3xl pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <span class="ara-badge ara-badge-green mb-6 inline-block reveal">Producto de ARACODE</span>
                    <h1 class="text-4xl sm:text-5xl font-bold text-white mb-6 reveal reveal-delay-1">
                        KAPTA <span class="text-gradient">LMS</span>
                    </h1>
                    <p class="text-lg text-white/70 mb-8 reveal reveal-delay-2">
                        KAPTA LMS es la plataforma SaaS de gestión y formación educativa que ARACODE Smart Solutions desarrolla para instituciones, academias y empresas.                        Aquí te contamos quién está detrás del producto y cómo llevarlo a tu institución.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 reveal reveal-delay-3">
                        <a href="{{ route('contacto') }}" class="ara-btn ara-btn-primary ara-btn-lg">
                            Solicitar Demo
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </a>
                        @if ($planesKapta)
                            <a href="{{ $planesKapta }}" class="ara-btn ara-btn-secondary ara-btn-lg" target="_blank" rel="noopener">
                                Ver Planes
                                <span class="sr-only">(se abre en una pestaña nueva)</span>
                            </a>
                        @endif
                    </div>
                </div>
                <div class="hidden lg:block reveal reveal-delay-4">
                    <img src="{{ asset('themes/webpage/images/misc/s1.jpg') }}" alt="KAPTA LMS" class="rounded-2xl shadow-2xl w-full" loading="lazy">
                </div>
            </div>
        </div>
    </section>

    {{-- Benefits --}}
    <section class="py-20 lg:py-28 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-v2.section-heading
                badge="Beneficios"
                title="¿Por qué elegir KAPTA LMS?"
                subtitle="Una plataforma diseñada para simplificar la gestión educativa y mejorar la experiencia de aprendizaje."
                :light="false"
            />

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @php
                    $benefits = [
                        [
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>',
                            'title' => 'Gestión Completa',
                            'description' => 'Administra cursos, módulos, evaluaciones y certificaciones desde un solo panel.',
                        ],
                        [
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>',
                            'title' => 'Aulas Virtuales',
                            'description' => 'Aulas con contenido multimedia: videos, documentos, SCORM, PDF y enlaces.',
                        ],
                        [
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>',
                            'title' => 'Certificación Automática',
                            'description' => 'Genera certificados personalizados automáticamente al completar un curso.',
                        ],
                        [
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
                            'title' => 'Control de Matrículas',
                            'description' => 'Gestiona matrículas, seguimiento académico y progreso de estudiantes.',
                        ],
                        [
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>',
                            'title' => 'Pasarelas de Pago',
                            'description' => 'Integra pasarelas de pago para la venta de cursos y certificaciones.',
                        ],
                        [
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>',
                            'title' => 'Panel con Métricas IA',
                            'description' => 'Dashboard con métricas de rendimiento y análisis de progreso potenciado con IA.',
                        ],
                    ];
                @endphp

                @foreach($benefits as $index => $benefit)
                    <div class="ara-card reveal reveal-delay-{{ $index + 1 }}">
                        <div class="ara-icon-box ara-icon-box-green mb-4">
                            {!! $benefit['icon'] !!}
                        </div>
                        <h3 class="text-xl font-bold text-ara-slate-700 mb-3">{{ $benefit['title'] }}</h3>
                        <p class="text-ara-slate-400 leading-relaxed">{{ $benefit['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{--
        Antes aquí vivía una tabla de planes con importes propios (S/ 149, S/ 299,
        «Personalizado») que ya no coincidía con la tabla vigente del producto.
        Publicar dos listas de precios del mismo producto confunde al visitante y
        reparte la autoridad de la página entre dos URLs: ahora los planes y sus
        precios los publica solo el sitio oficial, y esta ficha enlaza ahí.
    --}}
    <x-v2.product-official
        producto="kapta"
        :paginaSecundaria="$planesKapta"
        etiquetaSecundaria="Ver planes y precios"
        nota="Las funcionalidades, los planes vigentes con sus precios y el acceso al producto están en la web de KAPTA. Para una demostración o una propuesta para tu institución, escríbenos: la atiende el equipo de ARACODE."
    />

    <x-v2.cta-section 
        title="¿Listo para transformar la educación?"
        subtitle="Comienza hoy con KAPTA LMS y lleva la formación de tu institución al siguiente nivel."
        buttonText="Solicitar Demo"
    />

    @include('aracode.components.v2.footer')
@endsection
