@extends('layouts.webpage')

@section('meta_title', 'ARACODE Smart Solutions | Software Empresarial, IA y Automatización')
@section('meta_description', 'Empresa peruana especializada en desarrollo de software empresarial, automatización de procesos, inteligencia artificial y soluciones SaaS.')

@section('content')
    {{-- Navbar --}}
    @include('components.v2.navbar')

    {{-- Hero --}}
    @include('components.v2.hero')

    {{-- Client Logos --}}
    <section class="py-12 bg-ara-slate-50 border-y border-ara-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <p class="text-center text-ara-slate-400 text-sm font-medium mb-8 reveal">Empresas que confían en nosotros</p>
            <div class="overflow-hidden">
                <div class="flex items-center justify-center flex-wrap gap-8 md:gap-12">
                    @php
                        $clients = ['brise', 'iprase', 'cpa', 'horizonte', 'orbe', 'cprod', 'jrrss', 'zoelife', 'celmovil', 'cap', 'kentha'];
                    @endphp
                    @foreach($clients as $client)
                        <img 
                            src="{{ asset('themes/webpage/images/customers/' . $client . '.png') }}" 
                            alt="Cliente {{ ucfirst($client) }}" 
                            class="ara-client-logo h-10 md:h-12 object-contain"
                            loading="lazy"
                        >
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- Services Section --}}
    <section class="py-20 lg:py-28 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-v2.section-heading
                badge="Nuestros Servicios"
                title="Soluciones que transforman negocios"
                subtitle="Ofrecemos un ecosistema completo de productos y servicios tecnológicos diseñados para impulsar la digitalización de tu empresa."
                :light="false"
            />

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @php
                    $services = [
                        [
                            'icon' => '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>',
                            'title' => 'Desarrollo de Software',
                            'description' => 'Creamos soluciones de software a medida optimizadas para las necesidades específicas de tu empresa.',
                            'href' => route('solucion_desarrollo'),
                        ],
                        [
                            'icon' => '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>',
                            'title' => 'Automatización',
                            'description' => 'Automatizamos procesos repetitivos para reducir errores, ahorrar tiempo y aumentar la productividad.',
                            'href' => route('soluciones'),
                        ],
                        [
                            'icon' => '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>',
                            'title' => 'Inteligencia Artificial',
                            'description' => 'Integramos IA en cada solución para analizar datos, automatizar decisiones y optimizar operaciones.',
                            'href' => route('soluciones'),
                        ],
                        [
                            'icon' => '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>',
                            'title' => 'Plataformas Digitales',
                            'description' => 'Desarrollamos plataformas SaaS escalables que conectan procesos, usuarios y datos en un solo ecosistema.',
                            'href' => route('soluciones'),
                        ],
                        [
                            'icon' => '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>',
                            'title' => 'Facturación Electrónica',
                            'description' => 'Solución completa para la emisión de comprobantes electrónicos, integración con SUNAT y gestión comercial.',
                            'href' => route('solucion_facturacion'),
                        ],
                        [
                            'icon' => '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>',
                            'title' => 'Transformación Digital',
                            'description' => 'Te acompañamos en todo el proceso de digitalización, desde la estrategia hasta la implementación.',
                            'href' => route('soluciones'),
                        ],
                    ];
                @endphp

                @foreach($services as $index => $service)
                    <x-v2.service-card
                        :icon="$service['icon']"
                        :title="$service['title']"
                        :description="$service['description']"
                        :href="$service['href']"
                        :delay="$index + 1"
                    />
                @endforeach
            </div>
        </div>
    </section>

    {{-- Featured Products --}}
    <section class="py-20 lg:py-28 bg-ara-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-v2.section-heading
                badge="Productos Destacados"
                title="Soluciones listas para usar"
                subtitle="Descubre nuestros productos diseñados para impulsar la digitalización de tu empresa."
                :light="false"
            />

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                {{-- KAPTA LMS --}}
                <x-v2.product-card
                    title="KAPTA LMS"
                    description="Plataforma SaaS para gestión y formación educativa."
                    :href="route('solucion_kapta')"
                    image="{{ asset('themes/webpage/images/misc/s1.jpg') }}"
                    badge="Plataforma SaaS"
                    :features="['Gestión de cursos y módulos', 'Aulas virtuales con contenido multimedia', 'Certificación automática', 'Panel administrativo con métricas']"
                    :delay="1"
                />

                {{-- Facturación --}}
                <x-v2.product-card
                    title="Facturación Electrónica"
                    description="Solución completa para empresas que necesitan gestionar ventas, facturación y procesos comerciales."
                    :href="route('solucion_facturacion')"
                    image="{{ asset('themes/webpage/images/misc/s2.jpg') }}"
                    badge="SUNAT"
                    :features="['Facturas, boletas y notas de crédito', 'Integración directa con SUNAT', 'Panel comercial con reportes', 'XML y PDF automáticos']"
                    :delay="2"
                />
            </div>
        </div>
    </section>

    {{-- Value Proposition --}}
    <section class="py-20 lg:py-28 bg-ara-navy relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-ara-blue/10 rounded-full filter blur-3xl pointer-events-none"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 bg-ara-green/10 rounded-full filter blur-3xl pointer-events-none"></div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div>
                    <x-v2.section-heading
                        badge="Propuesta de Valor"
                        title="¿Qué nos hace diferentes?"
                        subtitle="En ARACODE, ofrecemos soluciones digitales potenciadas con IA para tu empresa o institución."
                        align="left"
                    />

                    <div class="space-y-8 mt-12">
                        <x-v2.feature-item
                            icon='<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>'
                            title="IA integrada en cada solución"
                            description="Integramos inteligencia artificial en cada una de nuestras soluciones para automatizar procesos y optimizar decisiones."
                            :delay="1"
                        />

                        <x-v2.feature-item
                            icon='<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>'
                            title="Ecosistema de productos + servicios"
                            description="Ofrecemos productos listos como LMS, CMS y Facturación Electrónica, junto con servicios a medida."
                            :delay="2"
                        />

                        <x-v2.feature-item
                            icon='<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>'
                            title="Escalabilidad y adaptabilidad"
                            description="Software que se adapta a tu empresa, con arquitectura modular que permite añadir funciones conforme evolucionen tus necesidades."
                            :delay="3"
                        />

                        <x-v2.feature-item
                            icon='<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>'
                            title="Acompañamiento y soporte constante"
                            description="Brindamos capacitación y soporte post-implementación, con una relación cercana y profesional con cada cliente."
                            :delay="4"
                        />
                    </div>
                </div>

                <div class="hidden lg:block">
                    <div class="relative">
                        <div class="absolute inset-0 bg-gradient-to-r from-ara-blue/20 to-ara-green/20 rounded-2xl transform rotate-3"></div>
                        <img 
                            src="{{ asset('themes/webpage/images/about.jpg') }}" 
                            alt="ARACODE Smart Solutions" 
                            class="relative rounded-2xl shadow-2xl w-full object-cover"
                            loading="lazy"
                        >
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Stats Section --}}
    <section class="py-16 bg-white border-y border-ara-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <x-v2.stats-counter number="100" suffix="+" label="Empresas Atendidas" :delay="1" />
                <x-v2.stats-counter number="5" suffix="+" label="Años de Experiencia" :delay="2" />
                <x-v2.stats-counter number="500" suffix="+" label="Usuarios Activos" :delay="3" />
                <x-v2.stats-counter number="99" suffix="%" label="Uptime Garantizado" :delay="4" />
            </div>
        </div>
    </section>

    {{-- CTA Section --}}
    <x-v2.cta-section />

    {{-- Footer --}}
    @include('components.v2.footer')
@endsection
