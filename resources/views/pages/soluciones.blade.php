@extends('layouts.webpage')

@section('meta_title', 'Soluciones | ARACODE Smart Solutions')
@section('meta_description', 'Descubre todas nuestras soluciones tecnológicas: KAPTA LMS, Facturación Electrónica, Desarrollo a Medida y más.')

@section('content')
    @include('components.v2.navbar')

    {{-- Hero --}}
    <section class="pt-32 pb-20 bg-ara-navy relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-ara-blue/10 rounded-full filter blur-3xl pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="max-w-3xl mx-auto text-center">
                <span class="ara-badge ara-badge-blue mb-6 inline-block reveal">Nuestras Soluciones</span>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white mb-6 reveal reveal-delay-1">
                    Soluciones que <span class="text-gradient">impulsan</span> tu negocio
                </h1>
                <p class="text-lg text-white/70 reveal reveal-delay-2">
                    Ofrecemos un ecosistema completo de productos y servicios tecnológicos diseñados para la transformación digital de tu empresa.
                </p>
            </div>
        </div>
    </section>

    {{-- Products --}}
    <section class="py-20 lg:py-28 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                {{-- KAPTA LMS --}}
                <x-v2.product-card
                    title="KAPTA LMS"
                    description="Plataforma SaaS para gestión y formación educativa completa."
                    :href="route('solucion_kapta')"
                    image="{{ asset('themes/webpage/images/misc/s1.jpg') }}"
                    badge="Plataforma SaaS"
                    :features="[
                        'Gestión completa de cursos y módulos',
                        'Aulas virtuales con contenido multimedia',
                        'Certificación automática personalizada',
                        'Control de matrículas y seguimiento',
                        'Integración con pasarelas de pago',
                        'Panel administrativo con métricas IA'
                    ]"
                    :delay="1"
                />

                {{-- Facturación --}}
                <x-v2.product-card
                    title="Facturación Electrónica"
                    description="Solución completa para empresas que necesitan gestionar ventas, facturación y procesos comerciales."
                    :href="route('solucion_facturacion')"
                    image="{{ asset('themes/webpage/images/misc/s2.jpg') }}"
                    badge="SUNAT"
                    :features="[
                        'Facturas, boletas y notas de crédito/débito',
                        'Integración directa con SUNAT',
                        'Panel comercial con reportes de ventas',
                        'Generación automática de XML y PDF',
                        'Alertas y validaciones avanzadas',
                        'Seguridad y cumplimiento normativo'
                    ]"
                    :delay="2"
                />

                {{-- Desarrollo --}}
                <x-v2.product-card
                    title="Desarrollo a Medida"
                    description="Creamos soluciones de software personalizadas para las necesidades específicas de tu empresa."
                    :href="route('solucion_desarrollo')"
                    image="{{ asset('themes/webpage/images/misc/s3.jpg') }}"
                    badge="Personalizado"
                    :features="[
                        'Análisis y diseño de soluciones',
                        'Arquitectura moderna y escalable',
                        'Integraciones con sistemas existentes',
                        'Testing y garantía de calidad',
                        'Soporte y mantenimiento continuo',
                        'Documentación técnica completa'
                    ]"
                    :delay="3"
                />

                {{-- Automatización --}}
                <div class="ara-product-card relative overflow-hidden rounded-2xl bg-gradient-to-br from-ara-navy to-ara-blue min-h-[400px] flex flex-col justify-end p-8 reveal reveal-delay-4">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-ara-blue/20 rounded-full filter blur-3xl pointer-events-none"></div>
                    
                    <div class="relative z-10">
                        <span class="ara-badge ara-badge-green mb-4 inline-block">Servicios</span>
                        <h3 class="text-2xl lg:text-3xl font-bold text-white mb-3">Automatización de Procesos</h3>
                        <p class="text-white/70 mb-6">
                            Optimiza y automatiza los procesos manuales de tu empresa para reducir errores, ahorrar tiempo y aumentar la productividad de tu equipo.
                        </p>
                        <a href="{{ route('contacto') }}" class="ara-btn ara-btn-primary">
                            Solicitar Asesoría
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </a>
                    </div>
                </div>

                {{-- IA --}}
                <div class="ara-product-card relative overflow-hidden rounded-2xl bg-gradient-to-br from-ara-navy to-ara-green min-h-[400px] flex flex-col justify-end p-8 reveal reveal-delay-5">
                    <div class="absolute top-0 left-0 w-64 h-64 bg-ara-green/20 rounded-full filter blur-3xl pointer-events-none"></div>
                    
                    <div class="relative z-10">
                        <span class="ara-badge ara-badge-green mb-4 inline-block">Innovación</span>
                        <h3 class="text-2xl lg:text-3xl font-bold text-white mb-3">Inteligencia Artificial</h3>
                        <p class="text-white/70 mb-6">
                            Integramos IA en cada una de nuestras soluciones para analizar datos de forma inteligente y brindar una toma de decisiones asistida.
                        </p>
                        <a href="{{ route('contacto') }}" class="ara-btn ara-btn-green">
                            Conocer Más
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <x-v2.cta-section 
        title="¿Necesitas una solución personalizada?"
        subtitle="Cuéntanos sobre los retos de tu empresa y te diseñaremos una solución a medida."
        buttonText="Hablar con un Especialista"
    />

    @include('components.v2.footer')
@endsection
