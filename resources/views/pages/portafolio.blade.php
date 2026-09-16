@extends('layouts.webpage')

@section('meta_title', 'Portafolio | ARACODE Smart Solutions')
@section('meta_description', 'Conoce los proyectos exitosos de ARACODE Smart Solutions. Soluciones de software, automatización e IA implementadas para empresas.')

@section('content')
    @include('components.v2.navbar')

    {{-- Hero --}}
    <section class="pt-32 pb-20 bg-ara-navy relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-ara-blue/10 rounded-full filter blur-3xl pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="max-w-3xl mx-auto text-center">
                <span class="ara-badge ara-badge-blue mb-6 inline-block reveal">Nuestros Trabajos</span>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white mb-6 reveal reveal-delay-1">
                    <span class="text-gradient">Portafolio</span> de Proyectos
                </h1>
                <p class="text-lg text-white/70 reveal reveal-delay-2">
                    Conoce los proyectos que hemos desarrollado para empresas e instituciones en todo Perú.
                </p>
            </div>
        </div>
    </section>

    {{-- Stats --}}
    <section class="py-12 bg-white border-b border-ara-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div class="reveal">
                    <div class="text-3xl lg:text-4xl font-bold text-ara-blue mb-1" data-counter data-target="100" data-suffix="+">0+</div>
                    <div class="text-ara-slate-500 text-sm">Proyectos Entregados</div>
                </div>
                <div class="reveal reveal-delay-1">
                    <div class="text-3xl lg:text-4xl font-bold text-ara-blue mb-1" data-counter data-target="50" data-suffix="+">0+</div>
                    <div class="text-ara-slate-500 text-sm">Clientes Satisfechos</div>
                </div>
                <div class="reveal reveal-delay-2">
                    <div class="text-3xl lg:text-4xl font-bold text-ara-blue mb-1" data-counter data-target="5" data-suffix="+">0+</div>
                    <div class="text-ara-slate-500 text-sm">Años de Experiencia</div>
                </div>
                <div class="reveal reveal-delay-3">
                    <div class="text-3xl lg:text-4xl font-bold text-ara-blue mb-1" data-counter data-target="99" data-suffix="%">0%</div>
                    <div class="text-ara-slate-500 text-sm">Satisfacción</div>
                </div>
            </div>
        </div>
    </section>

    {{-- Filter Tabs --}}
    <section class="py-20 lg:py-28 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="flex flex-wrap justify-center gap-3 mb-12 reveal">
                <button class="px-5 py-2.5 rounded-full text-sm font-medium bg-ara-blue text-white transition-all" data-filter="all">Todos</button>
                <button class="px-5 py-2.5 rounded-full text-sm font-medium bg-ara-slate-100 text-ara-slate-600 hover:bg-ara-slate-200 transition-all" data-filter="saas">SaaS</button>
                <button class="px-5 py-2.5 rounded-full text-sm font-medium bg-ara-slate-100 text-ara-slate-600 hover:bg-ara-slate-200 transition-all" data-filter="web">Sitios Web</button>
                <button class="px-5 py-2.5 rounded-full text-sm font-medium bg-ara-slate-100 text-ara-slate-600 hover:bg-ara-slate-200 transition-all" data-filter="ai">IA / Automatización</button>
                <button class="px-5 py-2.5 rounded-full text-sm font-medium bg-ara-slate-100 text-ara-slate-600 hover:bg-ara-slate-200 transition-all" data-filter="facturacion">Facturación</button>
            </div>

            {{-- Projects Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8" id="portfolio-grid">

                {{-- Project 1 --}}
                <div class="portfolio-item reveal" data-category="saas">
                    <div class="ara-card overflow-hidden group">
                        <div class="h-56 bg-gradient-to-br from-ara-blue to-blue-700 flex items-center justify-center p-8 relative overflow-hidden">
                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all duration-500"></div>
                            <div class="text-center relative z-10">
                                <svg class="w-16 h-16 text-white/90 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                                <span class="text-white/60 text-sm font-medium">Plataforma SaaS</span>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-ara-blue/10 text-ara-blue">SaaS</span>
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-ara-green/10 text-ara-green">Activo</span>
                            </div>
                            <h3 class="text-xl font-bold text-ara-slate-700 mb-2 group-hover:text-ara-blue transition-colors">KAPTA LMS</h3>
                            <p class="text-ara-slate-500 text-sm leading-relaxed mb-4">Plataforma de aprendizaje electrónico para instituciones educativas. Gestión de cursos, aulas virtuales, certificaciones automáticas y panel administrativo.</p>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-2 py-1 rounded text-xs bg-ara-slate-100 text-ara-slate-500">Laravel</span>
                                <span class="px-2 py-1 rounded text-xs bg-ara-slate-100 text-ara-slate-500">Vue.js</span>
                                <span class="px-2 py-1 rounded text-xs bg-ara-slate-100 text-ara-slate-500">MySQL</span>
                                <span class="px-2 py-1 rounded text-xs bg-ara-slate-100 text-ara-slate-500">WebRTC</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Project 2 --}}
                <div class="portfolio-item reveal reveal-delay-1" data-category="facturacion">
                    <div class="ara-card overflow-hidden group">
                        <div class="h-56 bg-gradient-to-br from-ara-green to-green-700 flex items-center justify-center p-8 relative overflow-hidden">
                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all duration-500"></div>
                            <div class="text-center relative z-10">
                                <svg class="w-16 h-16 text-white/90 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                                </svg>
                                <span class="text-white/60 text-sm font-medium">Sistema de Facturación</span>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-ara-green/10 text-ara-green">Facturación</span>
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-ara-green/10 text-ara-green">Activo</span>
                            </div>
                            <h3 class="text-xl font-bold text-ara-slate-700 mb-2 group-hover:text-ara-blue transition-colors">Facturador Electrónico</h3>
                            <p class="text-ara-slate-500 text-sm leading-relaxed mb-4">Sistema de facturación electrónica homologado por SUNAT. Emisión de facturas, boletas, notas de crédito/débito con integración directa a SUNAT.</p>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-2 py-1 rounded text-xs bg-ara-slate-100 text-ara-slate-500">Laravel</span>
                                <span class="px-2 py-1 rounded text-xs bg-ara-slate-100 text-ara-slate-500">API SUNAT</span>
                                <span class="px-2 py-1 rounded text-xs bg-ara-slate-100 text-ara-slate-500">XML/PDF</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Project 3 --}}
                <div class="portfolio-item reveal reveal-delay-2" data-category="web">
                    <div class="ara-card overflow-hidden group">
                        <div class="h-56 bg-gradient-to-br from-purple-500 to-purple-700 flex items-center justify-center p-8 relative overflow-hidden">
                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all duration-500"></div>
                            <div class="text-center relative z-10">
                                <svg class="w-16 h-16 text-white/90 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                </svg>
                                <span class="text-white/60 text-sm font-medium">Sitio Web</span>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-600">Sitio Web</span>
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-ara-green/10 text-ara-green">Activo</span>
                            </div>
                            <h3 class="text-xl font-bold text-ara-slate-700 mb-2 group-hover:text-ara-blue transition-colors">Portal Corporativo</h3>
                            <p class="text-ara-slate-500 text-sm leading-relaxed mb-4">Desarrollo de sitio web corporativo moderno con diseño responsive, modo oscuro, blog integrado y optimización SEO completa.</p>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-2 py-1 rounded text-xs bg-ara-slate-100 text-ara-slate-500">Laravel</span>
                                <span class="px-2 py-1 rounded text-xs bg-ara-slate-100 text-ara-slate-500">Blade</span>
                                <span class="px-2 py-1 rounded text-xs bg-ara-slate-100 text-ara-slate-500">Tailwind CSS</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Project 4 --}}
                <div class="portfolio-item reveal" data-category="ai">
                    <div class="ara-card overflow-hidden group">
                        <div class="h-56 bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center p-8 relative overflow-hidden">
                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all duration-500"></div>
                            <div class="text-center relative z-10">
                                <svg class="w-16 h-16 text-white/90 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <span class="text-white/60 text-sm font-medium">IA / Automatización</span>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-600">IA</span>
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-ara-green/10 text-ara-green">Activo</span>
                            </div>
                            <h3 class="text-xl font-bold text-ara-slate-700 mb-2 group-hover:text-ara-blue transition-colors">Chatbot Inteligente</h3>
                            <p class="text-ara-slate-500 text-sm leading-relaxed mb-4">Asistente virtual con inteligencia artificial para atención al cliente 24/7. Integración con WhatsApp, web y redes sociales.</p>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-2 py-1 rounded text-xs bg-ara-slate-100 text-ara-slate-500">Python</span>
                                <span class="px-2 py-1 rounded text-xs bg-ara-slate-100 text-ara-slate-500">OpenAI</span>
                                <span class="px-2 py-1 rounded text-xs bg-ara-slate-100 text-ara-slate-500">WhatsApp API</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Project 5 --}}
                <div class="portfolio-item reveal reveal-delay-1" data-category="saas">
                    <div class="ara-card overflow-hidden group">
                        <div class="h-56 bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center p-8 relative overflow-hidden">
                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all duration-500"></div>
                            <div class="text-center relative z-10">
                                <svg class="w-16 h-16 text-white/90 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span class="text-white/60 text-sm font-medium">Plataforma SaaS</span>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-cyan-100 text-cyan-600">SaaS</span>
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-ara-green/10 text-ara-green">Activo</span>
                            </div>
                            <h3 class="text-xl font-bold text-ara-slate-700 mb-2 group-hover:text-ara-blue transition-colors">CRM Empresarial</h3>
                            <p class="text-ara-slate-500 text-sm leading-relaxed mb-4">Sistema de gestión de relaciones con clientes para seguimiento de ventas, pipeline, reportes y automatización de marketing.</p>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-2 py-1 rounded text-xs bg-ara-slate-100 text-ara-slate-500">Laravel</span>
                                <span class="px-2 py-1 rounded text-xs bg-ara-slate-100 text-ara-slate-500">Vue.js</span>
                                <span class="px-2 py-1 rounded text-xs bg-ara-slate-100 text-ara-slate-500">Inertia.js</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Project 6 --}}
                <div class="portfolio-item reveal reveal-delay-2" data-category="web">
                    <div class="ara-card overflow-hidden group">
                        <div class="h-56 bg-gradient-to-br from-rose-500 to-red-600 flex items-center justify-center p-8 relative overflow-hidden">
                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all duration-500"></div>
                            <div class="text-center relative z-10">
                                <svg class="w-16 h-16 text-white/90 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                                <span class="text-white/60 text-sm font-medium">E-Commerce</span>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-rose-100 text-rose-600">E-Commerce</span>
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-ara-green/10 text-ara-green">Activo</span>
                            </div>
                            <h3 class="text-xl font-bold text-ara-slate-700 mb-2 group-hover:text-ara-blue transition-colors">Tienda Online</h3>
                            <p class="text-ara-slate-500 text-sm leading-relaxed mb-4">Plataforma de comercio electrónico con catálogo de productos, carrito de compras, pasarela de pagos y panel de administración.</p>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-2 py-1 rounded text-xs bg-ara-slate-100 text-ara-slate-500">Laravel</span>
                                <span class="px-2 py-1 rounded text-xs bg-ara-slate-100 text-ara-slate-500">Livewire</span>
                                <span class="px-2 py-1 rounded text-xs bg-ara-slate-100 text-ara-slate-500">Stripe</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </section>

    {{-- CTA --}}
    <section class="py-20 lg:py-28 bg-ara-navy">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl lg:text-4xl font-bold text-white mb-4 reveal">¿Tienes un proyecto en mente?</h2>
            <p class="text-white/70 max-w-2xl mx-auto mb-8 reveal reveal-delay-1">Cuéntanos tu idea y te ayudaremos a hacerla realidad con tecnología moderna y eficiente.</p>
            <a href="{{ route('contacto') }}" class="ara-btn ara-btn-primary ara-btn-lg reveal reveal-delay-2">
                Solicitar Asesoría
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </a>
        </div>
    </section>

    @include('components.v2.footer')

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const filterBtns = document.querySelectorAll('[data-filter]');
        const items = document.querySelectorAll('.portfolio-item');

        filterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const filter = this.dataset.filter;

                // Update active button
                filterBtns.forEach(b => {
                    b.classList.remove('bg-ara-blue', 'text-white');
                    b.classList.add('bg-ara-slate-100', 'text-ara-slate-600');
                });
                this.classList.remove('bg-ara-slate-100', 'text-ara-slate-600');
                this.classList.add('bg-ara-blue', 'text-white');

                // Filter items
                items.forEach(item => {
                    if (filter === 'all' || item.dataset.category === filter) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });
    });
    </script>
@endsection
