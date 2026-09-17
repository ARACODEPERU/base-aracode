@extends('layouts.webpage')

@section('meta_title', 'Precios | ARACODE Smart Solutions')
@section('meta_description', 'Conoce nuestros planes y precios para KAPTA LMS, Facturación Electrónica y Desarrollo a Medida. Soluciones accesibles para cada empresa.')

@section('content')
    @include('components.v2.navbar')

    {{-- Hero --}}
    <section class="pt-32 pb-20 bg-ara-navy relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-ara-blue/10 rounded-full filter blur-3xl pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="max-w-3xl mx-auto text-center">
                <span class="ara-badge ara-badge-blue mb-6 inline-block reveal">Precios</span>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white mb-6 reveal reveal-delay-1">
                    Planes <span class="text-gradient">Transparentes</span>
                </h1>
                <p class="text-lg text-white/70 reveal reveal-delay-2">
                    Elige el plan que mejor se adapte a tu negocio. Sin costos ocultos, sin sorpresas.
                </p>
            </div>
        </div>
    </section>

    {{-- KAPTA LMS Pricing --}}
    <section class="py-20 lg:py-28 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="text-center mb-12 reveal">
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-ara-blue/10 text-ara-blue text-sm font-medium mb-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    KAPTA LMS — Plataforma de Aprendizaje
                </span>
                <h2 class="text-3xl lg:text-4xl font-bold text-ara-slate-700">Planes KAPTA LMS</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

                {{-- Plan Emprendedor --}}
                <div class="ara-card p-8 border-2 border-transparent hover:border-ara-blue/30 transition-all reveal">
                    <div class="text-center mb-6">
                        <h3 class="text-xl font-bold text-ara-slate-700 mb-2">Emprendedor</h3>
                        <p class="text-ara-slate-500 text-sm">Ideal para empezar</p>
                    </div>
                    <div class="text-center mb-6">
                        <span class="text-4xl font-bold text-ara-blue">S/ 35</span>
                        <span class="text-ara-slate-500">/mes</span>
                    </div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-ara-green mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span class="text-ara-slate-500 text-sm">Hasta 50 estudiantes</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-ara-green mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span class="text-ara-slate-500 text-sm">10 cursos activos</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-ara-green mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span class="text-ara-slate-500 text-sm">Certificaciones básicas</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-ara-green mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span class="text-ara-slate-500 text-sm">Soporte por email</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-ara-green mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span class="text-ara-slate-500 text-sm">Hosting incluido</span>
                        </li>
                    </ul>
                    <a href="{{ route('contacto') }}" class="block text-center py-3 px-6 rounded-xl border-2 border-ara-blue text-ara-blue font-semibold hover:bg-ara-blue hover:text-white transition-all">
                        Comenzar
                    </a>
                </div>

                {{-- Plan Profesional --}}
                <div class="ara-card p-8 border-2 border-ara-blue relative reveal reveal-delay-1">
                    <div class="absolute -top-4 left-1/2 -translate-x-1/2 px-4 py-1 rounded-full bg-ara-blue text-white text-xs font-semibold">Más Popular</div>
                    <div class="text-center mb-6">
                        <h3 class="text-xl font-bold text-ara-slate-700 mb-2">Profesional</h3>
                        <p class="text-ara-slate-500 text-sm">Para instituciones en crecimiento</p>
                    </div>
                    <div class="text-center mb-6">
                        <span class="text-4xl font-bold text-ara-blue">S/ 99</span>
                        <span class="text-ara-slate-500">/mes</span>
                    </div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-ara-green mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span class="text-ara-slate-500 text-sm">Hasta 500 estudiantes</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-ara-green mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span class="text-ara-slate-500 text-sm">Cursos ilimitados</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-ara-green mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span class="text-ara-slate-500 text-sm">Certificaciones personalizadas</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-ara-green mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span class="text-ara-slate-500 text-sm">Aulas virtuales con video</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-ara-green mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span class="text-ara-slate-500 text-sm">Soporte prioritario</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-ara-green mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span class="text-ara-slate-500 text-sm">Dominio personalizado</span>
                        </li>
                    </ul>
                    <a href="{{ route('contacto') }}" class="block text-center py-3 px-6 rounded-xl bg-ara-blue text-white font-semibold hover:bg-blue-600 transition-all">
                        Comenzar
                    </a>
                </div>

                {{-- Plan Enterprise --}}
                <div class="ara-card p-8 border-2 border-transparent hover:border-ara-blue/30 transition-all reveal reveal-delay-2">
                    <div class="text-center mb-6">
                        <h3 class="text-xl font-bold text-ara-slate-700 mb-2">Enterprise</h3>
                        <p class="text-ara-slate-500 text-sm">Solución personalizada</p>
                    </div>
                    <div class="text-center mb-6">
                        <span class="text-4xl font-bold text-ara-blue">Custom</span>
                    </div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-ara-green mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span class="text-ara-slate-500 text-sm">Estudiantes ilimitados</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-ara-green mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span class="text-ara-slate-500 text-sm">Personalización completa</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-ara-green mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span class="text-ara-slate-500 text-sm">Integración API</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-ara-green mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span class="text-ara-slate-500 text-sm">Soporte 24/7 dedicado</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-ara-green mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span class="text-ara-slate-500 text-sm">SLA garantizado</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-ara-green mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span class="text-ara-slate-500 text-sm">Migración de datos incluida</span>
                        </li>
                    </ul>
                    <a href="{{ route('contacto') }}" class="block text-center py-3 px-6 rounded-xl border-2 border-ara-blue text-ara-blue font-semibold hover:bg-ara-blue hover:text-white transition-all">
                        Contactar
                    </a>
                </div>

            </div>
        </div>
    </section>

    {{-- Facturación Pricing --}}
    <section class="py-20 lg:py-28 bg-ara-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="text-center mb-12 reveal">
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-ara-green/10 text-ara-green text-sm font-medium mb-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                    Facturación Electrónica — SUNAT
                </span>
                <h2 class="text-3xl lg:text-4xl font-bold text-ara-slate-700">Sistema de Facturación</h2>
                <p class="text-ara-slate-500 mt-4 max-w-2xl mx-auto">Homologado por SUNAT. Emisión de facturas, boletas, notas de crédito/débito y guías de remisión.</p>
            </div>

            <div class="max-w-3xl mx-auto">
                <div class="ara-card p-8 border-2 border-ara-green/30 reveal">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <h3 class="text-xl font-bold text-ara-slate-700 mb-4">Incluye:</h3>
                            <ul class="space-y-3">
                                <li class="flex items-start gap-3">
                                    <svg class="w-5 h-5 text-ara-green mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    <span class="text-ara-slate-500 text-sm">Facturas, boletas y notas</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <svg class="w-5 h-5 text-ara-green mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    <span class="text-ara-slate-500 text-sm">Generación automática XML/PDF</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <svg class="w-5 h-5 text-ara-green mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    <span class="text-ara-slate-500 text-sm">Envío automático a SUNAT</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <svg class="w-5 h-5 text-ara-green mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    <span class="text-ara-slate-500 text-sm">Panel de reportes</span>
                                </li>
                            </ul>
                        </div>
                        <div class="flex flex-col justify-center">
                            <div class="text-center mb-6">
                                <span class="text-sm text-ara-slate-500">Desde</span>
                                <div class="text-4xl font-bold text-ara-green">S/ 49</div>
                                <span class="text-ara-slate-500">/mes</span>
                            </div>
                            <a href="{{ route('contacto') }}" class="block text-center py-3 px-6 rounded-xl bg-ara-green text-white font-semibold hover:bg-green-600 transition-all">
                                Solicitar Información
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Desarrollo a Medida --}}
    <section class="py-20 lg:py-28 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="text-center mb-12 reveal">
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-purple-100 text-purple-600 text-sm font-medium mb-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                    Desarrollo a Medida
                </span>
                <h2 class="text-3xl lg:text-4xl font-bold text-ara-slate-700">Software Personalizado</h2>
                <p class="text-ara-slate-500 mt-4 max-w-2xl mx-auto">Desarrollamos soluciones a la medida de tu negocio. Cotización según alcance y complejidad.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="ara-card p-6 text-center reveal">
                    <div class="w-14 h-14 rounded-xl bg-purple-100 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                    </div>
                    <h3 class="font-bold text-ara-slate-700 mb-2">Sitios Web</h3>
                    <p class="text-ara-slate-500 text-sm">Corporativos, institucionales, landing pages. Desde S/ 2,000</p>
                </div>

                <div class="ara-card p-6 text-center reveal reveal-delay-1">
                    <div class="w-14 h-14 rounded-xl bg-purple-100 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </div>
                    <h3 class="font-bold text-ara-slate-700 mb-2">Apps Móviles</h3>
                    <p class="text-ara-slate-500 text-sm">iOS, Android o multiplataforma. Desde S/ 8,000</p>
                </div>

                <div class="ara-card p-6 text-center reveal reveal-delay-2">
                    <div class="w-14 h-14 rounded-xl bg-purple-100 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <h3 class="font-bold text-ara-slate-700 mb-2">Sistemas a Medida</h3>
                    <p class="text-ara-slate-500 text-sm">ERP, CRM, plataformas SaaS. Cotización personalizada</p>
                </div>
            </div>

            <div class="text-center mt-12">
                <a href="{{ route('contacto') }}" class="ara-btn ara-btn-primary">
                    Solicitar Cotización
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </a>
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="py-20 lg:py-28 bg-ara-slate-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-ara-slate-700 mb-12 text-center reveal">Preguntas sobre Precios</h2>

            <div class="space-y-4">
                <div class="faq-item border border-ara-slate-100 rounded-xl overflow-hidden reveal">
                    <button class="faq-toggle w-full px-6 py-5 text-left flex items-center justify-between hover:bg-ara-slate-50 transition-colors">
                        <span class="font-semibold text-ara-slate-700">¿Hay costo de implementación?</span>
                        <svg class="w-5 h-5 text-ara-slate-400 transition-transform faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="faq-content hidden px-6 pb-5">
                        <p class="text-ara-slate-500 leading-relaxed">Para los planes de KAPTA LMS, no hay costo de implementación. El sistema está listo para usar. Para desarrollo a medida, el costo depende del alcance del proyecto.</p>
                    </div>
                </div>

                <div class="faq-item border border-ara-slate-100 rounded-xl overflow-hidden reveal reveal-delay-1">
                    <button class="faq-toggle w-full px-6 py-5 text-left flex items-center justify-between hover:bg-ara-slate-50 transition-colors">
                        <span class="font-semibold text-ara-slate-700">¿Puedo cambiar de plan después?</span>
                        <svg class="w-5 h-5 text-ara-slate-400 transition-transform faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="faq-content hidden px-6 pb-5">
                        <p class="text-ara-slate-500 leading-relaxed">Sí, puedes actualizar o reducir tu plan en cualquier momento. Los cambios se aplican en el siguiente ciclo de facturación.</p>
                    </div>
                </div>

                <div class="faq-item border border-ara-slate-100 rounded-xl overflow-hidden reveal reveal-delay-2">
                    <button class="faq-toggle w-full px-6 py-5 text-left flex items-center justify-between hover:bg-ara-slate-50 transition-colors">
                        <span class="font-semibold text-ara-slate-700">¿Qué formas de pago aceptan?</span>
                        <svg class="w-5 h-5 text-ara-slate-400 transition-transform faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="faq-content hidden px-6 pb-5">
                        <p class="text-ara-slate-500 leading-relaxed">Aceptamos transferencia bancaria, Yape, Plin y depósito. Para planes Enterprise, ofrecemos facturación con crédito.</p>
                    </div>
                </div>

                <div class="faq-item border border-ara-slate-100 rounded-xl overflow-hidden reveal reveal-delay-3">
                    <button class="faq-toggle w-full px-6 py-5 text-left flex items-center justify-between hover:bg-ara-slate-50 transition-colors">
                        <span class="font-semibold text-ara-slate-700">¿Ofrecen prueba gratuita?</span>
                        <svg class="w-5 h-5 text-ara-slate-400 transition-transform faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="faq-content hidden px-6 pb-5">
                        <p class="text-ara-slate-500 leading-relaxed">Sí, ofrecemos 14 días de prueba gratuita para KAPTA LMS. Sin tarjeta de crédito, sin compromiso.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('components.v2.footer')

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.faq-toggle').forEach(btn => {
            btn.addEventListener('click', function() {
                const content = this.nextElementSibling;
                const icon = this.querySelector('.faq-icon');
                const isOpen = !content.classList.contains('hidden');
                document.querySelectorAll('.faq-content').forEach(c => c.classList.add('hidden'));
                document.querySelectorAll('.faq-icon').forEach(i => i.style.transform = 'rotate(0deg)');
                if (!isOpen) {
                    content.classList.remove('hidden');
                    icon.style.transform = 'rotate(180deg)';
                }
            });
        });
    });
    </script>
@endsection
