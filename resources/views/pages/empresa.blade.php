@extends('layouts.webpage')

@section('meta_title', 'Empresa | ARACODE Smart Solutions')
@section('meta_description', 'Conoce a ARACODE Smart Solutions. Empresa peruana de tecnología especializada en desarrollo de software, automatización e IA.')

@section('content')
    @include('components.v2.navbar')

    {{-- Hero --}}
    <section class="pt-32 pb-20 bg-ara-navy relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-ara-blue/10 rounded-full filter blur-3xl pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="max-w-3xl mx-auto text-center">
                <span class="ara-badge ara-badge-blue mb-6 inline-block reveal">Sobre Nosotros</span>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white mb-6 reveal reveal-delay-1">
                    Conoce a <span class="text-gradient">ARACODE</span>
                </h1>
                <p class="text-lg text-white/70 reveal reveal-delay-2">
                    Empresa peruana especializada en desarrollo de software empresarial, automatización de procesos e inteligencia artificial.
                </p>
            </div>
        </div>
    </section>

    {{-- About --}}
    <section class="py-20 lg:py-28 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div>
                    <x-v2.section-heading
                        badge="Quiénes Somos"
                        title="Tecnología que impulsa negocios"
                        subtitle="Somos una empresa peruana especializada en el desarrollo de software a medida y productos digitales innovadores."
                        align="left"
                        :light="false"
                    />

                    <div class="space-y-6 mt-8">
                        <p class="text-ara-slate-500 leading-relaxed reveal reveal-delay-1">
                            Nuestro enfoque combina tecnología moderna + inteligencia artificial para crear soluciones eficientes, escalables y alineadas a las necesidades reales de empresas, instituciones educativas y organizaciones en crecimiento.
                        </p>
                        
                        <ul class="space-y-4 reveal reveal-delay-2">
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-ara-green mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-ara-slate-500">Optimice sus procesos internos</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-ara-green mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-ara-slate-500">Reduzca errores y tiempos de gestión</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-ara-green mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-ara-slate-500">Mejore la experiencia de sus usuarios</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-ara-green mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-ara-slate-500">Crezca de manera escalable y eficiente</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="reveal reveal-delay-3">
                    <img src="{{ asset('themes/webpage/images/about.jpg') }}" alt="ARACODE Smart Solutions" class="rounded-2xl shadow-2xl w-full">
                </div>
            </div>
        </div>
    </section>

    {{-- Mission & Vision --}}
    <section class="py-20 lg:py-28 bg-ara-slate-50" id="mision">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="ara-card reveal">
                    <div class="ara-icon-box ara-icon-box-green mb-6">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-ara-slate-700 mb-4">Nuestra Misión</h3>
                    <p class="text-ara-slate-500 leading-relaxed">
                        Desarrollar soluciones de software innovadoras y eficientes que impulsen la transformación digital de empresas e instituciones, potenciadas con inteligencia artificial y tecnología moderna.
                    </p>
                </div>

                <div class="ara-card reveal reveal-delay-1">
                    <div class="ara-icon-box mb-6">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-ara-slate-700 mb-4">Nuestra Visión</h3>
                    <p class="text-ara-slate-500 leading-relaxed">
                        Ser reconocidos como una empresa líder en desarrollo de software y soluciones digitales en Perú y Latinoamérica, destacando por nuestra innovación, calidad y compromiso con el éxito de nuestros clientes.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- Values --}}
    <section class="py-20 lg:py-28 bg-ara-navy" id="valores">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-v2.section-heading
                badge="Valores"
                title="Nuestros valores"
                subtitle="Los principios que guían nuestro trabajo y relación con los clientes."
            />

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                @php
                    $values = [
                        [
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>',
                            'title' => 'Innovación',
                            'description' => 'Buscamos constantemente nuevas formas de resolver problemas con tecnología.',
                        ],
                        [
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>',
                            'title' => 'Confianza',
                            'description' => 'Construimos relaciones sólidas basadas en la transparencia y el cumplimiento.',
                        ],
                        [
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>',
                            'title' => 'Calidad',
                            'description' => 'Aplicamos estándares de excelencia en cada línea de código que escribimos.',
                        ],
                        [
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
                            'title' => 'Compromiso',
                            'description' => 'Nos comprometemos con el éxito de cada proyecto y cada cliente.',
                        ],
                    ];
                @endphp

                @foreach($values as $index => $value)
                    <div class="text-center reveal reveal-delay-{{ $index + 1 }}">
                        <div class="ara-icon-box mx-auto mb-6">
                            {!! $value['icon'] !!}
                        </div>
                        <h4 class="text-xl font-bold text-white mb-3">{{ $value['title'] }}</h4>
                        <p class="text-white/70 leading-relaxed">{{ $value['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Clients --}}
    <section class="py-20 lg:py-28 bg-white" id="clientes">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-v2.section-heading
                badge="Clientes"
                title="Empresas que confían en nosotros"
                subtitle="Instituciones y empresas que han elegido nuestras soluciones digitales."
                :light="false"
            />

            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-8">
                @php
                    $clients = ['brise', 'iprase', 'cpa', 'horizonte', 'orbe', 'cprod', 'jrrss', 'zoelife', 'celmovil', 'cap', 'kentha'];
                @endphp

                @foreach($clients as $index => $client)
                    <div class="flex items-center justify-center p-4 rounded-lg border border-ara-slate-100 hover:border-ara-blue/30 transition-colors reveal reveal-delay-{{ ($index % 6) + 1 }}">
                        <img 
                            src="{{ asset('themes/webpage/images/customers/' . $client . '.png') }}" 
                            alt="Cliente {{ ucfirst($client) }}" 
                            class="ara-client-logo max-h-12 object-contain"
                            loading="lazy"
                        >
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <x-v2.cta-section />

    @include('components.v2.footer')
@endsection
