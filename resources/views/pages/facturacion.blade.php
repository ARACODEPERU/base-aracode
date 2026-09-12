@extends('layouts.webpage')

@section('meta_title', 'Facturación Electrónica | ARACODE Smart Solutions')
@section('meta_description', 'Sistema de gestión y facturación electrónica. Emisión de facturas, boletas, notas y más. Integración directa con SUNAT.')

@section('content')
    @include('components.v2.navbar')

    {{-- Hero --}}
    <section class="pt-32 pb-20 bg-ara-navy relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-ara-blue/10 rounded-full filter blur-3xl pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <span class="ara-badge ara-badge-blue mb-6 inline-block reveal">SUNAT</span>
                    <h1 class="text-4xl sm:text-5xl font-bold text-white mb-6 reveal reveal-delay-1">
                        Facturación <span class="text-gradient">Electrónica</span>
                    </h1>
                    <p class="text-lg text-white/70 mb-8 reveal reveal-delay-2">
                        Solución completa para empresas que necesitan gestionar ventas, facturación, inventario y procesos comerciales. Cumplimiento total con SUNAT.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 reveal reveal-delay-3">
                        <a href="{{ route('contacto') }}" class="ara-btn ara-btn-primary ara-btn-lg">
                            Solicitar Asesoría
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </a>
                        <a href="{{ route('contacto') }}" class="ara-btn ara-btn-secondary ara-btn-lg">
                            Ver Planes
                        </a>
                    </div>
                </div>
                <div class="hidden lg:block reveal reveal-delay-4">
                    <img src="{{ asset('themes/webpage/images/misc/s2.jpg') }}" alt="Facturación Electrónica" class="rounded-2xl shadow-2xl w-full">
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

    {{-- Pricing --}}
    <section class="py-20 lg:py-28 bg-ara-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-v2.section-heading
                badge="Planes"
                title="Planes flexibles para tu empresa"
                subtitle="Elige el plan que mejor se adapte a las necesidades de tu negocio."
                :light="false"
            />

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @php
                    $plans = [
                        [
                            'name' => 'Emprendedor',
                            'price' => 'S/ 35',
                            'period' => '/mes',
                            'annual' => 'S/ 350 /año',
                            'features' => ['Módulo de ventas y compras', 'Control de inventario', 'Facturas y boletas electrónicas', 'Reportes básicos', '2 usuarios', 'Soporte 24/7'],
                            'highlighted' => false,
                        ],
                        [
                            'name' => 'PYME',
                            'price' => 'S/ 50',
                            'period' => '/mes',
                            'annual' => 'S/ 500 /año',
                            'features' => ['Todo lo del plan Emprendedor', 'Cotizaciones y notas de venta', 'Kardex y movimientos', 'Punto de venta', '5 usuarios', 'Soporte prioritario'],
                            'highlighted' => true,
                        ],
                        [
                            'name' => 'PRO',
                            'price' => 'S/ 80',
                            'period' => '/mes',
                            'annual' => 'S/ 800 /año',
                            'features' => ['Todo lo del plan PYME', 'Guías de remisión', 'Reportes avanzados', '10 usuarios', 'Soporte dedicado', 'Capacitación incluida'],
                            'highlighted' => false,
                        ],
                    ];
                @endphp

                @foreach($plans as $index => $plan)
                    <div class="ara-card relative reveal reveal-delay-{{ $index + 1 }} {{ $plan['highlighted'] ? 'ring-2 ring-ara-blue shadow-lg' : '' }}">
                        @if($plan['highlighted'])
                            <span class="absolute -top-3 left-1/2 -translate-x-1/2 ara-badge ara-badge-blue">Más Popular</span>
                        @endif
                        
                        <h3 class="text-xl font-bold text-ara-slate-700 mb-2">{{ $plan['name'] }}</h3>
                        <div class="mb-6">
                            <span class="text-4xl font-bold text-ara-blue">{{ $plan['price'] }}</span>
                            <span class="text-ara-slate-400">{{ $plan['period'] }}</span>
                            @if($plan['annual'])
                                <div class="text-sm text-ara-slate-400 mt-1">{{ $plan['annual'] }}</div>
                            @endif
                        </div>
                        
                        <ul class="space-y-3 mb-8">
                            @foreach($plan['features'] as $feature)
                                <li class="flex items-center gap-3 text-ara-slate-500">
                                    <svg class="w-5 h-5 text-ara-green flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ $feature }}
                                </li>
                            @endforeach
                        </ul>
                        
                        <a href="{{ route('contacto') }}" class="ara-btn w-full {{ $plan['highlighted'] ? 'ara-btn-primary' : 'ara-btn-ghost' }}">
                            Lo Quiero
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <x-v2.cta-section 
        title="¿Necesitas facturar electrónicamente?"
        subtitle="Contáctanos hoy y comienza a emitir comprobantes electrónicos en cumplimiento con SUNAT."
        buttonText="Solicitar Asesoría"
    />

    @include('components.v2.footer')
@endsection
