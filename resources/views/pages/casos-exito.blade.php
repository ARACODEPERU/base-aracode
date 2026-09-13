@extends('layouts.webpage')

@section('meta_title', 'Casos de Éxito | ARACODE Smart Solutions')
@section('meta_description', 'Conoce los proyectos exitosos de ARACODE Smart Solutions. Soluciones de software, automatización e IA implementadas para empresas.')

@section('content')
    @include('components.v2.navbar')

    {{-- Hero --}}
    <section class="pt-32 pb-20 bg-ara-navy relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-ara-blue/10 rounded-full filter blur-3xl pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="max-w-3xl mx-auto text-center">
                <span class="ara-badge ara-badge-blue mb-6 inline-block reveal">Portfolio</span>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white mb-6 reveal reveal-delay-1">
                    Casos de <span class="text-gradient">Éxito</span>
                </h1>
                <p class="text-lg text-white/70 reveal reveal-delay-2">
                    Conoce los proyectos que hemos desarrollado y los resultados obtenidos para nuestros clientes.
                </p>
            </div>
        </div>
    </section>

    {{-- Stats --}}
    <section class="py-12 bg-white border-y border-ara-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <x-v2.stats-counter number="100" suffix="+" label="Proyectos Entregados" :delay="1" />
                <x-v2.stats-counter number="50" suffix="+" label="Clientes Activos" :delay="2" />
                <x-v2.stats-counter number="99" suffix="%" label="Satisfacción" :delay="3" />
                <x-v2.stats-counter number="5" suffix="+" label="Años de Experiencia" :delay="4" />
            </div>
        </div>
    </section>

    {{-- Cases --}}
    <section class="py-20 lg:py-28 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Filter --}}
            <div class="flex flex-wrap justify-center gap-3 mb-12 reveal">
                <button class="px-5 py-2.5 rounded-full text-sm font-medium transition-all bg-ara-blue text-white" data-filter="all">
                    Todos
                </button>
                <button class="px-5 py-2.5 rounded-full text-sm font-medium transition-all bg-ara-slate-100 text-ara-slate-500 hover:bg-ara-blue/10 hover:text-ara-blue" data-filter="lms">
                    KAPTA LMS
                </button>
                <button class="px-5 py-2.5 rounded-full text-sm font-medium transition-all bg-ara-slate-100 text-ara-slate-500 hover:bg-ara-blue/10 hover:text-ara-blue" data-filter="facturacion">
                    Facturación
                </button>
                <button class="px-5 py-2.5 rounded-full text-sm font-medium transition-all bg-ara-slate-100 text-ara-slate-500 hover:bg-ara-blue/10 hover:text-ara-blue" data-filter="desarrollo">
                    Desarrollo
                </button>
                <button class="px-5 py-2.5 rounded-full text-sm font-medium transition-all bg-ara-slate-100 text-ara-slate-500 hover:bg-ara-blue/10 hover:text-ara-blue" data-filter="ia">
                    Inteligencia Artificial
                </button>
            </div>

            {{-- Cases Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

                {{-- Case 1 --}}
                <div class="ara-card reveal" data-category="lms">
                    <div class="relative h-48 rounded-xl overflow-hidden mb-6">
                        <img src="{{ asset('themes/webpage/images/misc/s1.jpg') }}" alt="KAPTA LMS" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                        <span class="absolute bottom-4 left-4 px-3 py-1 bg-ara-blue text-white text-xs font-medium rounded-full">KAPTA LMS</span>
                    </div>
                    <h3 class="text-xl font-bold text-ara-slate-700 mb-2">Plataforma E-Learning para Instituto Educativo</h3>
                    <p class="text-ara-slate-500 text-sm mb-4">Implementación completa de KAPTA LMS para gestión de 200+ cursos, aulas virtuales y certificaciones automáticas.</p>
                    <div class="flex items-center gap-4 text-sm">
                        <span class="text-ara-green font-medium">✓ 500+ estudiantes activos</span>
                    </div>
                </div>

                {{-- Case 2 --}}
                <div class="ara-card reveal reveal-delay-1" data-category="facturacion">
                    <div class="relative h-48 rounded-xl overflow-hidden mb-6">
                        <img src="{{ asset('themes/webpage/images/misc/s2.jpg') }}" alt="Facturación Electrónica" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                        <span class="absolute bottom-4 left-4 px-3 py-1 bg-ara-blue text-white text-xs font-medium rounded-full">Facturación</span>
                    </div>
                    <h3 class="text-xl font-bold text-ara-slate-700 mb-2">Sistema de Facturación para Cadena Comercial</h3>
                    <p class="text-ara-slate-500 text-sm mb-4">Sistema integrado de facturación electrónica con SUNAT para cadena de 15 establecimientos.</p>
                    <div class="flex items-center gap-4 text-sm">
                        <span class="text-ara-green font-medium">✓ 10,000+ comprobantes/mes</span>
                    </div>
                </div>

                {{-- Case 3 --}}
                <div class="ara-card reveal reveal-delay-2" data-category="desarrollo">
                    <div class="relative h-48 rounded-xl overflow-hidden mb-6">
                        <img src="{{ asset('themes/webpage/images/about.jpg') }}" alt="Software a Medida" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                        <span class="absolute bottom-4 left-4 px-3 py-1 bg-ara-blue text-white text-xs font-medium rounded-full">Desarrollo</span>
                    </div>
                    <h3 class="text-xl font-bold text-ara-slate-700 mb-2">ERP a Medida para Empresa Agroindustrial</h3>
                    <p class="text-ara-slate-500 text-sm mb-4">Desarrollo de sistema ERP personalizado para gestión de inventario, producción y distribución.</p>
                    <div class="flex items-center gap-4 text-sm">
                        <span class="text-ara-green font-medium">✓ 40% reducción en tiempos</span>
                    </div>
                </div>

                {{-- Case 4 --}}
                <div class="ara-card reveal" data-category="ia">
                    <div class="relative h-48 rounded-xl overflow-hidden mb-6 bg-gradient-to-br from-ara-blue/20 to-ara-green/20 flex items-center justify-center">
                        <svg class="w-16 h-16 text-ara-blue/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                        <span class="absolute bottom-4 left-4 px-3 py-1 bg-ara-blue text-white text-xs font-medium rounded-full">IA</span>
                    </div>
                    <h3 class="text-xl font-bold text-ara-slate-700 mb-2">Chatbot IA para Atención al Cliente</h3>
                    <p class="text-ara-slate-500 text-sm mb-4">Desarrollo de chatbot con inteligencia artificial para automatizar atención al cliente 24/7.</p>
                    <div class="flex items-center gap-4 text-sm">
                        <span class="text-ara-green font-medium">✓ 70% reducción en consultas</span>
                    </div>
                </div>

                {{-- Case 5 --}}
                <div class="ara-card reveal reveal-delay-1" data-category="desarrollo">
                    <div class="relative h-48 rounded-xl overflow-hidden mb-6">
                        <img src="{{ asset('themes/webpage/images/misc/s1.jpg') }}" alt="Plataforma Digital" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                        <span class="absolute bottom-4 left-4 px-3 py-1 bg-ara-blue text-white text-xs font-medium rounded-full">Desarrollo</span>
                    </div>
                    <h3 class="text-xl font-bold text-ara-slate-700 mb-2">Marketplace para Productos Agrícolas</h3>
                    <p class="text-ara-slate-500 text-sm mb-4">Plataforma digital que conecta productores agrícolas con compradores, con pagos integrados.</p>
                    <div class="flex items-center gap-4 text-sm">
                        <span class="text-ara-green font-medium">✓ 200+ productores conectados</span>
                    </div>
                </div>

                {{-- Case 6 --}}
                <div class="ara-card reveal reveal-delay-2" data-category="ia">
                    <div class="relative h-48 rounded-xl overflow-hidden mb-6 bg-gradient-to-br from-ara-green/20 to-ara-blue/20 flex items-center justify-center">
                        <svg class="w-16 h-16 text-ara-green/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span class="absolute bottom-4 left-4 px-3 py-1 bg-ara-green text-white text-xs font-medium rounded-full">Automatización</span>
                    </div>
                    <h3 class="text-xl font-bold text-ara-slate-700 mb-2">Automatización de Procesos RRHH</h3>
                    <p class="text-ara-slate-500 text-sm mb-4">Sistema automatizado para gestión de Nómina, Asistencia y Evaluación de Desempeño.</p>
                    <div class="flex items-center gap-4 text-sm">
                        <span class="text-ara-green font-medium">✓ 60% menos tiempo administrativo</span>
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- CTA --}}
    <x-v2.cta-section />

    @include('components.v2.footer')

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const filterBtns = document.querySelectorAll('[data-filter]');
        const cases = document.querySelectorAll('[data-category]');

        filterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const filter = this.dataset.filter;

                filterBtns.forEach(b => {
                    b.classList.remove('bg-ara-blue', 'text-white');
                    b.classList.add('bg-ara-slate-100', 'text-ara-slate-500');
                });
                this.classList.remove('bg-ara-slate-100', 'text-ara-slate-500');
                this.classList.add('bg-ara-blue', 'text-white');

                cases.forEach(c => {
                    if (filter === 'all' || c.dataset.category === filter) {
                        c.style.display = 'block';
                        c.style.opacity = '0';
                        setTimeout(() => c.style.opacity = '1', 50);
                    } else {
                        c.style.display = 'none';
                    }
                });
            });
        });
    });
    </script>
@endsection
