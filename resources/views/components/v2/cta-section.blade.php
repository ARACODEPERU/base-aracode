{{-- CTA Section Component --}}
@props([
    'title' => '¿Listo para digitalizar tu empresa?',
    'subtitle' => 'Contáctanos hoy y descubre cómo nuestras soluciones pueden impulsar el crecimiento de tu negocio.',
    'buttonText' => 'Solicitar Asesoría',
    'buttonHref' => null,
])

<section class="ara-cta py-20 lg:py-28">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white mb-6 reveal">
            {{ $title }}
        </h2>
        
        <p class="text-lg text-white/70 mb-10 max-w-2xl mx-auto reveal reveal-delay-1">
            {{ $subtitle }}
        </p>
        
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 reveal reveal-delay-2">
            <a href="{{ $buttonHref ?? route('contacto') }}" class="ara-btn ara-btn-primary ara-btn-lg">
                {{ $buttonText }}
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </a>
            <a href="{{ route('soluciones') }}" class="ara-btn ara-btn-secondary ara-btn-lg">
                Ver Soluciones
            </a>
        </div>
    </div>
</section>
