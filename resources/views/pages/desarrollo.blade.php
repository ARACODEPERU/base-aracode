@extends('layouts.webpage')

@section('meta_title', 'Desarrollo de Software a Medida | ARACODE Smart Solutions')
@section('meta_description', 'Desarrollo de software personalizado para empresas. Soluciones a medida, escalables y modernas para tus necesidades específicas.')

@section('content')
    @include('components.v2.navbar')

    {{-- Hero --}}
    <section class="pt-32 pb-20 bg-ara-navy relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-ara-blue/10 rounded-full filter blur-3xl pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <span class="ara-badge ara-badge-blue mb-6 inline-block reveal">Personalizado</span>
                    <h1 class="text-4xl sm:text-5xl font-bold text-white mb-6 reveal reveal-delay-1">
                        Desarrollo de Software <span class="text-gradient">a Medida</span>
                    </h1>
                    <p class="text-lg text-white/70 mb-8 reveal reveal-delay-2">
                        Creamos soluciones de software diseñadas específicamente para las necesidades de tu empresa. Desde la idea hasta la implementación.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 reveal reveal-delay-3">
                        <a href="{{ route('contacto') }}" class="ara-btn ara-btn-primary ara-btn-lg">
                            Solicitar Presupuesto
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </a>
                        <a href="{{ route('soluciones') }}" class="ara-btn ara-btn-secondary ara-btn-lg">
                            Ver Otros Servicios
                        </a>
                    </div>
                </div>
                <div class="hidden lg:block reveal reveal-delay-4">
                    <img src="{{ asset('themes/webpage/images/misc/s3.jpg') }}" alt="Desarrollo a Medida" class="rounded-2xl shadow-2xl w-full">
                </div>
            </div>
        </div>
    </section>

    {{-- Process --}}
    <section class="py-20 lg:py-28 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-v2.section-heading
                badge="Nuestro Proceso"
                title="Cómo trabajamos"
                subtitle="Un proceso claro y profesional para garantizar el éxito de tu proyecto."
                :light="false"
            />

            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                @php
                    $steps = [
                        [
                            'step' => '01',
                            'title' => 'Análisis',
                            'description' => 'Entendemos tus necesidades, objetivos y desafíos para diseñar la solución ideal.',
                        ],
                        [
                            'step' => '02',
                            'title' => 'Diseño',
                            'description' => 'Diseñamos la arquitectura y la interfaz de usuario con un enfoque en usabilidad.',
                        ],
                        [
                            'step' => '03',
                            'title' => 'Desarrollo',
                            'description' => 'Construimos la solución con tecnologías modernas y estándares de calidad.',
                        ],
                        [
                            'step' => '04',
                            'title' => 'Implementación',
                            'description' => 'Implementamos, capacitamos y brindamos soporte continuo para garantizar el éxito.',
                        ],
                    ];
                @endphp

                @foreach($steps as $index => $step)
                    <div class="text-center reveal reveal-delay-{{ $index + 1 }}">
                        <div class="w-16 h-16 rounded-full bg-ara-blue text-white text-xl font-bold flex items-center justify-center mx-auto mb-6">
                            {{ $step['step'] }}
                        </div>
                        <h3 class="text-xl font-bold text-ara-slate-700 mb-3">{{ $step['title'] }}</h3>
                        <p class="text-ara-slate-400 leading-relaxed">{{ $step['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Services --}}
    <section class="py-20 lg:py-28 bg-ara-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-v2.section-heading
                badge="Servicios"
                title="¿Qué desarrollamos?"
                subtitle="Creamos soluciones para una amplia variedad de necesidades empresariales."
                :light="false"
            />

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @php
                    $services = [
                        [
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>',
                            'title' => 'Sistemas Web',
                            'description' => 'Aplicaciones web completas para gestión empresarial,CRM, ERP y más.',
                        ],
                        [
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>',
                            'title' => 'Apps Móviles',
                            'description' => 'Aplicaciones móviles nativas e híbridas para iOS y Android.',
                        ],
                        [
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>',
                            'title' => 'APIs e Integraciones',
                            'description' => 'Desarrollo de APIs RESTful e integraciones con sistemas existentes.',
                        ],
                        [
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>',
                            'title' => 'Plataformas SaaS',
                            'description' => 'Plataformas escalables en la nube para múltiples clientes.',
                        ],
                        [
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>',
                            'title' => 'Soluciones IA',
                            'description' => 'Integración de inteligencia artificial para automatización y análisis.',
                        ],
                        [
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>',
                            'title' => 'Consultoría',
                            'description' => 'Asesoría técnica para arquitectura de software y estrategia digital.',
                        ],
                    ];
                @endphp

                @foreach($services as $index => $service)
                    <div class="ara-card reveal reveal-delay-{{ $index + 1 }}">
                        <div class="ara-icon-box mb-4">
                            {!! $service['icon'] !!}
                        </div>
                        <h3 class="text-xl font-bold text-ara-slate-700 mb-3">{{ $service['title'] }}</h3>
                        <p class="text-ara-slate-400 leading-relaxed">{{ $service['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <x-v2.cta-section 
        title="¿Tienes un proyecto en mente?"
        subtitle="Cuéntanos sobre tu idea y te ayudaremos a convertirla en una solución digital real."
        buttonText="Solicitar Presupuesto"
    />

    @include('components.v2.footer')
@endsection
