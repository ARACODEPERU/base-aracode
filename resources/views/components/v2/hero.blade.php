{{-- Hero Section V2 - ARACODE --}}
<section class="ara-hero">
    {{-- Background elements --}}
    <div class="ara-hero-grid"></div>
    <div class="ara-orb ara-orb-blue w-[500px] h-[500px] top-[-10%] right-[-5%]"></div>
    <div class="ara-orb ara-orb-green w-[300px] h-[300px] bottom-[10%] left-[5%]"></div>

    <div class="relative z-10 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-32">
        <div class="max-w-4xl mx-auto text-center">
            {{-- Badge --}}
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/5 border border-white/10 mb-8 reveal">
                <span class="w-2 h-2 rounded-full bg-ara-green animate-pulse"></span>
                <span class="text-white/80 text-sm font-medium">Enterprise Software & AI Solutions</span>
            </div>

            {{-- Main Heading --}}
            <h1 class="text-4xl sm:text-5xl lg:text-7xl font-bold text-white mb-6 leading-tight reveal reveal-delay-1">
                Desarrollamos soluciones digitales
                <span class="text-gradient"> que impulsan</span>
                el crecimiento de tu empresa
            </h1>

            {{-- Subtitle --}}
            <p class="text-lg sm:text-xl text-white/70 max-w-2xl mx-auto mb-10 reveal reveal-delay-2">
                Software empresarial, automatización de procesos e inteligencia artificial diseñados para escalar tu negocio y optimizar cada operación.
            </p>

            {{-- CTAs --}}
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 reveal reveal-delay-3">
                <a href="{{ route('contacto') }}" class="ara-btn ara-btn-primary ara-btn-lg">
                    Solicitar Asesoría
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </a>
                <a href="{{ route('soluciones') }}" class="ara-btn ara-btn-secondary ara-btn-lg">
                    Ver Soluciones
                </a>
            </div>

            {{-- Stats Bar --}}
            <div class="mt-16 pt-8 border-t border-white/10 reveal reveal-delay-4">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                    <div class="text-center">
                        <div class="text-3xl lg:text-4xl font-bold text-white mb-1" data-counter data-target="100" data-suffix="+">0+</div>
                        <div class="text-white/60 text-sm">Empresas Atendidas</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl lg:text-4xl font-bold text-white mb-1" data-counter data-target="5" data-suffix="+">0+</div>
                        <div class="text-white/60 text-sm">Años de Experiencia</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl lg:text-4xl font-bold text-white mb-1" data-counter data-target="500" data-suffix="+">0+</div>
                        <div class="text-white/60 text-sm">Usuarios Activos</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl lg:text-4xl font-bold text-white mb-1" data-counter data-target="99" data-suffix="%">0%</div>
                        <div class="text-white/60 text-sm">Uptime Garantizado</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
