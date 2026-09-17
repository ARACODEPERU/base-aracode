@extends('layouts.webpage')

@section('meta_title', 'Nuestro Equipo | ARACODE Smart Solutions')
@section('meta_description', 'Conoce al equipo de profesionales detrás de ARACODE Smart Solutions. Desarrolladores, diseñadores y expertos en tecnología.')

@section('content')
    @include('components.v2.navbar')

    {{-- Hero --}}
    <section class="pt-32 pb-20 bg-ara-navy relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-ara-blue/10 rounded-full filter blur-3xl pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="max-w-3xl mx-auto text-center">
                <span class="ara-badge ara-badge-blue mb-6 inline-block reveal">Nuestro Equipo</span>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white mb-6 reveal reveal-delay-1">
                    Las personas detrás de la <span class="text-gradient">tecnología</span>
                </h1>
                <p class="text-lg text-white/70 reveal reveal-delay-2">
                    Un equipo apasionado por la innovación y la excelencia en cada proyecto.
                </p>
            </div>
        </div>
    </section>

    {{-- Team Grid --}}
    <section class="py-20 lg:py-28 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Leadership --}}
            <div class="text-center mb-16 reveal">
                <h2 class="text-3xl font-bold text-ara-slate-700 mb-4">Equipo Directivo</h2>
                <p class="text-ara-slate-500 max-w-2xl mx-auto">Líderes con experiencia en tecnología y gestión empresarial.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-20">

                {{-- Member 1 --}}
                <div class="ara-card p-8 text-center group reveal">
                    <div class="w-28 h-28 rounded-full bg-gradient-to-br from-ara-blue to-blue-600 flex items-center justify-center mx-auto mb-6 text-white text-3xl font-bold group-hover:scale-105 transition-transform">
                        JD
                    </div>
                    <h3 class="text-xl font-bold text-ara-slate-700 mb-1">Juan Doe</h3>
                    <p class="text-ara-blue text-sm font-medium mb-4">CEO & Founder</p>
                    <p class="text-ara-slate-500 text-sm leading-relaxed mb-6">Ingeniero de sistemas con más de 10 años de experiencia en desarrollo de software y gestión de proyectos tecnológicos.</p>
                    <div class="flex justify-center gap-3">
                        <a href="https://www.linkedin.com/in/aracode-smart-solution-0b3663365" target="_blank" class="w-9 h-9 rounded-full bg-ara-slate-100 flex items-center justify-center hover:bg-ara-blue hover:text-white transition-all text-ara-slate-400">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                        </a>
                    </div>
                </div>

                {{-- Member 2 --}}
                <div class="ara-card p-8 text-center group reveal reveal-delay-1">
                    <div class="w-28 h-28 rounded-full bg-gradient-to-br from-ara-green to-green-600 flex items-center justify-center mx-auto mb-6 text-white text-3xl font-bold group-hover:scale-105 transition-transform">
                        MG
                    </div>
                    <h3 class="text-xl font-bold text-ara-slate-700 mb-1">María García</h3>
                    <p class="text-ara-green text-sm font-medium mb-4">CTO</p>
                    <p class="text-ara-slate-500 text-sm leading-relaxed mb-6">Especialista en arquitectura de software y desarrollo de plataformas SaaS. Experta en Laravel, Vue.js y nube.</p>
                    <div class="flex justify-center gap-3">
                        <a href="https://www.linkedin.com/in/aracode-smart-solution-0b3663365" target="_blank" class="w-9 h-9 rounded-full bg-ara-slate-100 flex items-center justify-center hover:bg-ara-blue hover:text-white transition-all text-ara-slate-400">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                        </a>
                    </div>
                </div>

                {{-- Member 3 --}}
                <div class="ara-card p-8 text-center group reveal reveal-delay-2">
                    <div class="w-28 h-28 rounded-full bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center mx-auto mb-6 text-white text-3xl font-bold group-hover:scale-105 transition-transform">
                        CL
                    </div>
                    <h3 class="text-xl font-bold text-ara-slate-700 mb-1">Carlos López</h3>
                    <p class="text-purple-500 text-sm font-medium mb-4">Director Comercial</p>
                    <p class="text-ara-slate-500 text-sm leading-relaxed mb-6">Especialista en estrategia comercial y desarrollo de negocio. Conecta las necesidades del cliente con soluciones tecnológicas efectivas.</p>
                    <div class="flex justify-center gap-3">
                        <a href="https://www.linkedin.com/in/aracode-smart-solution-0b3663365" target="_blank" class="w-9 h-9 rounded-full bg-ara-slate-100 flex items-center justify-center hover:bg-ara-blue hover:text-white transition-all text-ara-slate-400">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                        </a>
                    </div>
                </div>

            </div>

            {{-- Tech Team --}}
            <div class="text-center mb-16 reveal">
                <h2 class="text-3xl font-bold text-ara-slate-700 mb-4">Equipo Técnico</h2>
                <p class="text-ara-slate-500 max-w-2xl mx-auto">Desarrolladores y especialistas que hacen posible cada proyecto.</p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">

                @php
                    $team = [
                        ['initials' => 'AR', 'name' => 'Ana Rodríguez', 'role' => 'Frontend Developer', 'color' => 'from-cyan-400 to-cyan-600'],
                        ['initials' => 'RM', 'name' => 'Roberto Martínez', 'role' => 'Backend Developer', 'color' => 'from-amber-400 to-orange-500'],
                        ['initials' => 'LS', 'name' => 'Laura Sánchez', 'role' => 'UI/UX Designer', 'color' => 'from-rose-400 to-pink-500'],
                        ['initials' => 'DP', 'name' => 'Diego Pérez', 'role' => 'DevOps Engineer', 'color' => 'from-emerald-400 to-emerald-600'],
                        ['initials' => 'CM', 'name' => 'Camila Morales', 'role' => 'QA Engineer', 'color' => 'from-violet-400 to-violet-600'],
                        ['initials' => 'FH', 'name' => 'Fernando Herrera', 'role' => 'Mobile Developer', 'color' => 'from-blue-400 to-blue-600'],
                        ['initials' => 'VR', 'name' => 'Valentina Rojas', 'role' => 'Data Analyst', 'color' => 'from-teal-400 to-teal-600'],
                        ['initials' => 'SA', 'name' => 'Santiago Alvarado', 'role' => 'AI Engineer', 'color' => 'from-indigo-400 to-indigo-600'],
                    ];
                @endphp

                @foreach($team as $index => $member)
                    <div class="ara-card p-6 text-center group reveal reveal-delay-{{ ($index % 4) + 1 }}">
                        <div class="w-20 h-20 rounded-full bg-gradient-to-br {{ $member['color'] }} flex items-center justify-center mx-auto mb-4 text-white text-xl font-bold group-hover:scale-105 transition-transform">
                            {{ $member['initials'] }}
                        </div>
                        <h4 class="font-bold text-ara-slate-700 text-sm mb-1">{{ $member['name'] }}</h4>
                        <p class="text-ara-slate-500 text-xs">{{ $member['role'] }}</p>
                    </div>
                @endforeach

            </div>
        </div>
    </section>

    {{-- Why Us --}}
    <section class="py-20 lg:py-28 bg-ara-navy">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16 reveal">
                <h2 class="text-3xl font-bold text-white mb-4">¿Por qué trabajar con nosotros?</h2>
                <p class="text-white/70 max-w-2xl mx-auto">Más que proveedores, somos socios tecnológicos de tu negocio.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="text-center reveal">
                    <div class="w-16 h-16 rounded-2xl bg-ara-blue/20 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-ara-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <h4 class="text-white font-semibold mb-2">Metodología Ágil</h4>
                    <p class="text-white/60 text-sm">Trabajamos con Scrum para entregas rápidas y transparentes.</p>
                </div>

                <div class="text-center reveal reveal-delay-1">
                    <div class="w-16 h-16 rounded-2xl bg-ara-green/20 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-ara-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <h4 class="text-white font-semibold mb-2">Soporte Continuo</h4>
                    <p class="text-white/60 text-sm">Acompañamiento post-lanzamiento y mantenimiento.</p>
                </div>

                <div class="text-center reveal reveal-delay-2">
                    <div class="w-16 h-16 rounded-2xl bg-purple-500/20 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                    </div>
                    <h4 class="text-white font-semibold mb-2">Innovación</h4>
                    <p class="text-white/60 text-sm">Tecnología de punta: IA, automatización y cloud.</p>
                </div>

                <div class="text-center reveal reveal-delay-3">
                    <div class="w-16 h-16 rounded-2xl bg-amber-500/20 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <h4 class="text-white font-semibold mb-2">Cercanía</h4>
                    <p class="text-white/60 text-sm">Comunicación directa y atención personalizada.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <x-v2.cta-section />

    @include('components.v2.footer')
@endsection
