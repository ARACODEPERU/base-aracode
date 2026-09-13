@extends('layouts.webpage')

@section('meta_title', 'Trabaja con Nosotros | ARACODE Smart Solutions')
@section('meta_description', 'Únete al equipo de ARACODE Smart Solutions. Buscamos talento apasionado por la tecnología, la innovación y la transformación digital.')

@section('content')
    @include('components.v2.navbar')

    {{-- Hero --}}
    <section class="pt-32 pb-20 bg-ara-navy relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-ara-blue/10 rounded-full filter blur-3xl pointer-events-none"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 bg-ara-green/10 rounded-full filter blur-3xl pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="max-w-3xl mx-auto text-center">
                <span class="ara-badge ara-badge-green mb-6 inline-block reveal">Carreras</span>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white mb-6 reveal reveal-delay-1">
                    Trabaja con <span class="text-gradient">Nosotros</span>
                </h1>
                <p class="text-lg text-white/70 reveal reveal-delay-2">
                    Únete a un equipo de profesionales apasionados por la tecnología y la innovación. Buscamos talento que quiera hacer la diferencia.
                </p>
            </div>
        </div>
    </section>

    {{-- Benefits --}}
    <section class="py-20 lg:py-28 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-v2.section-heading
                badge="Beneficios"
                title="¿Por qué trabajar en ARACODE?"
                subtitle="Ofrecemos un ambiente de trabajo innovador donde podrás crecer profesionalmente."
                :light="false"
            />

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                @php
                    $benefits = [
                        [
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>',
                            'title' => 'Tecnología de Vanguardia',
                            'description' => 'Trabaja con las últimas tecnologías: IA, Cloud, SaaS y más.',
                        ],
                        [
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>',
                            'title' => 'Capacitación Constante',
                            'description' => 'Acceso a cursos, certificaciones y desarrollo profesional continuo.',
                        ],
                        [
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
                            'title' => 'Equipo Joven',
                            'description' => 'Ambiente dinámico, colaborativo y lleno de energía.',
                        ],
                        [
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>',
                            'title' => 'Crecimiento Real',
                            'description' => 'Oportunidades de ascenso y liderazgo según tu desempeño.',
                        ],
                    ];
                @endphp

                @foreach($benefits as $index => $benefit)
                    <div class="text-center reveal reveal-delay-{{ $index + 1 }}">
                        <div class="ara-icon-box mx-auto mb-6">
                            {!! $benefit['icon'] !!}
                        </div>
                        <h4 class="text-lg font-bold text-ara-slate-700 mb-2">{{ $benefit['title'] }}</h4>
                        <p class="text-ara-slate-500 text-sm leading-relaxed">{{ $benefit['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Open Positions --}}
    <section class="py-20 lg:py-28 bg-ara-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-v2.section-heading
                badge="Vacantes"
                title="Posiciones Abiertas"
                subtitle="Únete a nuestro equipo y forma parte del futuro de la tecnología."
                :light="false"
            />

            <div class="max-w-3xl mx-auto space-y-4">

                {{-- Position 1 --}}
                <div class="ara-card reveal cursor-pointer" onclick="this.querySelector('.position-details').classList.toggle('hidden')">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-ara-slate-700">Desarrollador Full Stack</h3>
                            <div class="flex items-center gap-4 mt-2 text-sm text-ara-slate-400">
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                    Remoto / Híbrido
                                </span>
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Tiempo Completo
                                </span>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-ara-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                    <div class="position-details hidden mt-6 pt-6 border-t border-ara-slate-100">
                        <p class="text-ara-slate-500 mb-4">Buscamos un desarrollador Full Stack con experiencia en Laravel, Vue.js y bases de datos. Participarás en el desarrollo de nuestras plataformas SaaS y soluciones a medida.</p>
                        <h4 class="font-semibold text-ara-slate-700 mb-2">Requisitos:</h4>
                        <ul class="text-ara-slate-500 text-sm space-y-1 mb-4">
                            <li>• 2+ años de experiencia en desarrollo web</li>
                            <li>• PHP/Laravel, JavaScript/Vue.js</li>
                            <li>• MySQL o PostgreSQL</li>
                            <li>• Git y metodologías ágiles</li>
                        </ul>
                        <a href="mailto:rrhh@aracodeperu.com?subject=Candidatura: Desarrollador Full Stack" class="ara-btn ara-btn-primary ara-btn-sm">
                            Aplicar Ahora
                        </a>
                    </div>
                </div>

                {{-- Position 2 --}}
                <div class="ara-card reveal reveal-delay-1 cursor-pointer" onclick="this.querySelector('.position-details').classList.toggle('hidden')">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-ara-slate-700">Diseñador UI/UX</h3>
                            <div class="flex items-center gap-4 mt-2 text-sm text-ara-slate-400">
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                    Remoto / Híbrido
                                </span>
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Tiempo Completo
                                </span>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-ara-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                    <div class="position-details hidden mt-6 pt-6 border-t border-ara-slate-100">
                        <p class="text-ara-slate-500 mb-4">Buscamos un diseñador UI/UX creativo para diseñar interfaces de usuario modernas, intuitivas y visualmente atractivas para nuestras plataformas.</p>
                        <h4 class="font-semibold text-ara-slate-700 mb-2">Requisitos:</h4>
                        <ul class="text-ara-slate-500 text-sm space-y-1 mb-4">
                            <li>• 2+ años de experiencia en diseño UI/UX</li>
                            <li>• Figma, Adobe XD o similares</li>
                            <li>• Conocimiento de Tailwind CSS</li>
                            <li>• Portafolio de trabajos</li>
                        </ul>
                        <a href="mailto:rrhh@aracodeperu.com?subject=Candidatura: Diseñador UI/UX" class="ara-btn ara-btn-primary ara-btn-sm">
                            Aplicar Ahora
                        </a>
                    </div>
                </div>

                {{-- Position 3 --}}
                <div class="ara-card reveal reveal-delay-2 cursor-pointer" onclick="this.querySelector('.position-details').classList.toggle('hidden')">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-ara-slate-700">Ingeniero de IA / Machine Learning</h3>
                            <div class="flex items-center gap-4 mt-2 text-sm text-ara-slate-400">
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                    Remoto
                                </span>
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Tiempo Completo
                                </span>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-ara-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                    <div class="position-details hidden mt-6 pt-6 border-t border-ara-slate-100">
                        <p class="text-ara-slate-500 mb-4">Buscamos un especialista en inteligencia artificial para desarrollar modelos de ML, chatbots y soluciones de automatización inteligente.</p>
                        <h4 class="font-semibold text-ara-slate-700 mb-2">Requisitos:</h4>
                        <ul class="text-ara-slate-500 text-sm space-y-1 mb-4">
                            <li>• 3+ años en IA/ML</li>
                            <li>• Python, TensorFlow o PyTorch</li>
                            <li>• Procesamiento de lenguaje natural (NLP)</li>
                            <li>• Experiencia con APIs de IA</li>
                        </ul>
                        <a href="mailto:rrhh@aracodeperu.com?subject=Candidatura: Ingeniero de IA" class="ara-btn ara-btn-primary ara-btn-sm">
                            Aplicar Ahora
                        </a>
                    </div>
                </div>

            </div>

            {{-- Spontaneous --}}
            <div class="text-center mt-12 reveal">
                <p class="text-ara-slate-500 mb-4">¿No ves una posición que se ajuste a tu perfil?</p>
                <a href="mailto:rrhh@aracodeperu.com?subject=Candidatura Espontánea" class="ara-btn ara-btn-ghost">
                    Envía tu CV de todas formas
                </a>
            </div>
        </div>
    </section>

    @include('components.v2.footer')
@endsection
