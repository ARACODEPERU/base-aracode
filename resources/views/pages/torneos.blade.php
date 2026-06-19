<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aracode Torneos — Torneos Deportivos</title>
    <meta name="description" content="Aracode Torneos — Gestión de torneos deportivos. Fixture, posiciones, estadísticas y más.">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/4.0.0/anime.min.js" integrity="sha512-PP2nFtP1yPy4mB0i8K6SFC7OX5eKmeV5pXHrH3HqLdOmZ1I95+29x6qR7I9U5N1KZN7OLffPJ2jVTHUT6PQx+Q==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #070b14; color: #f1f5f9; }
        .hero-gradient { background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%); }
        .card-gradient { background: linear-gradient(180deg, rgba(30,41,59,0.8) 0%, rgba(15,23,42,0.9) 100%); }
        .glow { box-shadow: 0 0 40px rgba(59,130,246,0.15); }
        .glow-card:hover { box-shadow: 0 0 60px rgba(59,130,246,0.25); }
        .badge-pending { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .badge-in_progress { background: linear-gradient(135deg, #22c55e, #16a34a); }
        .badge-finished { background: linear-gradient(135deg, #6b7280, #4b5563); }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="fixed top-0 left-0 right-0 z-50 hero-gradient border-b border-white/5">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-400 flex items-center justify-center text-white font-bold text-lg shadow-lg">T</div>
                    <span class="text-xl font-bold text-white">Aracode <span class="text-blue-400">Torneos</span></span>
                </div>
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#inicio" class="text-gray-300 hover:text-white transition-colors text-sm font-medium">Inicio</a>
                    <a href="#torneos" class="text-gray-300 hover:text-white transition-colors text-sm font-medium">Torneos</a>
                    <a href="#contacto" class="text-gray-300 hover:text-white transition-colors text-sm font-medium">Contacto</a>
                    @if ($eventos->isNotEmpty())
                        @php $first = $eventos->first()->editions->first(); @endphp
                        @if ($first)
                            <a href="{{ route('socialevents_torneos_landing', $first->public_slug) }}" class="inline-flex items-center px-4 py-2 rounded-xl bg-gradient-to-r from-blue-600 to-cyan-500 text-white text-sm font-semibold hover:from-blue-500 hover:to-cyan-400 transition-all shadow-lg">Ver torneo activo</a>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <section id="inicio" class="relative min-h-screen flex items-center hero-gradient overflow-hidden">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-blue-500 rounded-full blur-[128px]"></div>
            <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-cyan-500 rounded-full blur-[128px]"></div>
        </div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-32">
            <div class="max-w-3xl">
                <div class="inline-flex items-center px-3 py-1 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-sm font-medium mb-6 se-badge">Gestión deportiva inteligente</div>
                <h1 class="text-4xl sm:text-5xl lg:text-7xl font-black text-white leading-tight mb-6 se-title">
                    Aracode
                    <span class="bg-gradient-to-r from-blue-400 to-cyan-300 bg-clip-text text-transparent">Torneos</span>
                </h1>
                <p class="text-lg sm:text-xl text-gray-400 leading-relaxed mb-8 se-desc">
                    Plataforma integral para la gestión de torneos deportivos. Fixture automático, tabla de posiciones, estadísticas de jugadores y resultados en tiempo real.
                </p>
                <div class="flex flex-wrap gap-4 se-cta">
                    <a href="#torneos" class="inline-flex items-center px-6 py-3 rounded-xl bg-gradient-to-r from-blue-600 to-cyan-500 text-white font-semibold hover:from-blue-500 hover:to-cyan-400 transition-all shadow-lg shadow-blue-500/25">Ver torneos vigentes</a>
                    <a href="#contacto" class="inline-flex items-center px-6 py-3 rounded-xl border border-gray-700 text-gray-300 font-semibold hover:border-gray-500 hover:text-white transition-all">Contacto</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Torneos Vigentes -->
    <section id="torneos" class="py-24 relative">
        <div class="absolute inset-0 bg-gradient-to-b from-[#070b14] via-[#0d1321] to-[#070b14]"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <div class="inline-flex items-center px-3 py-1 rounded-full bg-green-500/10 border border-green-500/20 text-green-400 text-sm font-medium mb-4 se-section-badge">En competencia</div>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black text-white mb-4 se-section-title">Torneos <span class="bg-gradient-to-r from-blue-400 to-cyan-300 bg-clip-text text-transparent">Vigentes</span></h2>
                <p class="text-gray-400 text-lg max-w-2xl mx-auto se-section-desc">Explora las ediciones activas, equipos participantes y seguimiento completo de cada competencia.</p>
            </div>

            @if ($eventos->isNotEmpty())
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($eventos as $evento)
                        @foreach ($evento->editions as $edition)
                            <a href="{{ route('socialevents_torneos_landing', $edition->public_slug) }}"
                               class="block group relative rounded-2xl card-gradient border border-white/5 overflow-hidden hover:border-blue-500/30 transition-all duration-500 glow-card se-card">
                                <div class="p-6">
                                    <div class="flex items-center justify-between mb-4">
                                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $evento->title }}</span>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                                            {{ $edition->status === 'in_progress' ? 'badge-in_progress text-white' : '' }}
                                            {{ $edition->status === 'pending' ? 'badge-pending text-white' : '' }}
                                            {{ $edition->status === 'finished' ? 'badge-finished text-white' : '' }}">
                                            {{ $edition->status === 'in_progress' ? 'En curso' : ($edition->status === 'pending' ? 'Próximo' : 'Finalizado') }}
                                        </span>
                                    </div>
                                    <h3 class="text-xl font-bold text-white mb-2 group-hover:text-blue-400 transition-colors">{{ $edition->name }}</h3>
                                    @if ($edition->start_date)
                                        <p class="text-gray-400 text-sm mb-4">
                                            {{ \Carbon\Carbon::parse($edition->start_date)->format('d M Y') }}
                                            @if ($edition->end_date)
                                                — {{ \Carbon\Carbon::parse($edition->end_date)->format('d M Y') }}
                                            @endif
                                        </p>
                                    @endif
                                    <div class="flex items-center text-gray-400 text-sm">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <span>{{ $edition->equipos_count }} equipos</span>
                                    </div>
                                </div>
                                <div class="absolute inset-0 bg-gradient-to-t from-blue-600/0 via-transparent to-transparent group-hover:from-blue-600/5 transition-all duration-500 pointer-events-none"></div>
                            </a>
                        @endforeach
                    @endforeach
                </div>
            @else
                <div class="text-center py-20">
                    <div class="w-20 h-20 mx-auto mb-6 rounded-2xl bg-gray-800/50 flex items-center justify-center">
                        <svg class="w-10 h-10 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-400 mb-2">No hay torneos disponibles</h3>
                    <p class="text-gray-600">Actualmente no hay ediciones publicadas. Vuelve pronto.</p>
                </div>
            @endif
        </div>
    </section>

    <!-- Contacto -->
    <section id="contacto" class="py-24 relative">
        <div class="absolute inset-0 bg-gradient-to-b from-[#070b14] via-[#0a0f1e] to-[#070b14]"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl mx-auto text-center">
                <div class="inline-flex items-center px-3 py-1 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-sm font-medium mb-4">Contáctanos</div>
                <h2 class="text-3xl sm:text-4xl font-black text-white mb-4">¿Interesado en <span class="bg-gradient-to-r from-blue-400 to-cyan-300 bg-clip-text text-transparent">organizar un torneo</span>?</h2>
                <p class="text-gray-400 text-lg mb-8">Gestiona tus competencias deportivas con nuestra plataforma. Fixture automático, estadísticas y más.</p>
                <a href="https://wa.me/51999999999" target="_blank" rel="noopener" class="inline-flex items-center px-6 py-3 rounded-xl bg-gradient-to-r from-green-500 to-emerald-500 text-white font-semibold hover:from-green-400 hover:to-emerald-400 transition-all shadow-lg shadow-green-500/25">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.84.985 5.45 2.623 7.505L1.82 24l4.688-2.754A11.94 11.94 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.85 0-3.58-.504-5.07-1.38l-.36-.21-3.2 1.88.83-3.17-.23-.39A9.95 9.95 0 012 12c0-5.523 4.477-10 10-10s10 4.477 10 10-4.477 10-10 10z"/></svg>
                    Escribir por WhatsApp
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="relative border-t border-white/5 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500 to-cyan-400 flex items-center justify-center text-white font-bold text-sm">T</div>
                    <span class="text-lg font-bold text-white">Aracode <span class="text-blue-400">Torneos</span></span>
                </div>
                <p class="text-gray-500 text-sm">&copy; {{ date('Y') }} Aracode Torneos. Todos los derechos reservados.</p>
                <div class="flex items-center space-x-4">
                    <a href="#" class="text-gray-500 hover:text-blue-400 transition-colors" aria-label="Facebook">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/></svg>
                    </a>
                    <a href="#" class="text-gray-500 hover:text-blue-400 transition-colors" aria-label="Instagram">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (prefersReduced || typeof anime === 'undefined') return;

        const badge = document.querySelector('.se-badge');
        if (badge) anime({ targets: badge, translateY: [20, 0], opacity: [0, 1], duration: 600, easing: 'easeOutQuad' });

        const title = document.querySelector('.se-title');
        if (title) {
            const chars = title.textContent.trim().split('');
            title.innerHTML = chars.map(c => c === ' ' ? ' ' : `<span class="char">${c}</span>`).join('');
            anime({ targets: '.se-title .char', translateY: [60, 0], opacity: [0, 1], duration: 800, delay: anime.stagger(40, { start: 400 }), easing: 'easeOutExpo' });
        }

        const desc = document.querySelector('.se-desc');
        if (desc) anime({ targets: desc, translateY: [30, 0], opacity: [0, 1], duration: 700, delay: 1000, easing: 'easeOutQuad' });

        const cta = document.querySelector('.se-cta');
        if (cta) anime({ targets: cta.children, translateY: [20, 0], opacity: [0, 1], duration: 600, delay: anime.stagger(150, { start: 1200 }), easing: 'easeOutQuad' });
    });

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const el = entry.target;
                const sectionBadge = el.querySelector('.se-section-badge');
                const sectionTitle = el.querySelector('.se-section-title');
                const sectionDesc = el.querySelector('.se-section-desc');
                const cards = el.querySelectorAll('.se-card');

                if (typeof anime !== 'undefined') {
                    if (sectionBadge) anime({ targets: sectionBadge, translateY: [15, 0], opacity: [0, 1], duration: 500, easing: 'easeOutQuad' });
                    if (sectionTitle) anime({ targets: sectionTitle, translateY: [20, 0], opacity: [0, 1], duration: 600, delay: 200, easing: 'easeOutQuad' });
                    if (sectionDesc) anime({ targets: sectionDesc, translateY: [20, 0], opacity: [0, 1], duration: 600, delay: 400, easing: 'easeOutQuad' });
                    if (cards.length) anime({ targets: cards, translateY: [40, 0], opacity: [0, 1], duration: 700, delay: anime.stagger(120, { start: 600 }), easing: 'easeOutQuad' });
                } else {
                    if (sectionBadge) sectionBadge.style.cssText = 'transform:translateY(0);opacity:1;';
                    if (sectionTitle) sectionTitle.style.cssText = 'transform:translateY(0);opacity:1;';
                    if (sectionDesc) sectionDesc.style.cssText = 'transform:translateY(0);opacity:1;';
                    cards.forEach(c => c.style.cssText = 'transform:translateY(0);opacity:1;');
                }
                observer.unobserve(el);
            }
        });
    }, { threshold: 0.15 });

    document.addEventListener('DOMContentLoaded', () => {
        const section = document.querySelector('#torneos');
        if (section) observer.observe(section);
    });
    </script>
</body>
</html>