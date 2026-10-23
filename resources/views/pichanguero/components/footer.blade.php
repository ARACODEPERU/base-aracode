{{-- Footer del sitio Pichanguero --}}
<footer class="pg-surface-dark border-t border-pg-line">
    <div class="pg-container py-14 lg:py-16">
        <div class="grid grid-cols-1 gap-10 md:grid-cols-2 lg:grid-cols-4">
            {{-- Marca --}}
            <div class="lg:col-span-2">
                <a href="{{ route('pichanguero.home') }}" class="inline-flex items-center">
                    <img src="{{ asset('themes/pichanguero/images/Logo-vertical.png') }}" alt="Pichanguero"
                         class="pg-brand-mark" width="260" height="48">
                </a>
                <p class="mt-5 max-w-md text-sm leading-relaxed text-pg-muted">
                    Plataforma para organizar campeonatos de fútbol: fixture automático, tabla de posiciones,
                    estadísticas de jugadores y resultados en vivo para tu liga, academia o torneo de fin de semana.
                </p>
                <a href="https://aracodeperu.com" target="_blank" rel="noopener"
                   class="pg-badge pg-badge-accent mt-5">
                    Un producto de ARACODE Smart Solutions
                </a>
            </div>

            {{-- Producto --}}
            <div>
                <h4 class="pg-display text-sm font-bold uppercase tracking-wide text-white">Producto</h4>
                <ul class="mt-4 space-y-3 text-sm">
                    <li><a href="{{ route('pichanguero.home') }}#producto" class="pg-link">Qué incluye</a></li>
                    <li><a href="{{ route('pichanguero.home') }}#como-funciona" class="pg-link">Cómo funciona</a></li>
                    <li><a href="{{ route('pichanguero.home') }}#para-quien" class="pg-link">Para quién</a></li>
                    <li><a href="{{ route('pichanguero.home') }}#preguntas" class="pg-link">Preguntas frecuentes</a></li>
                    {{-- La app Android aún no está publicada (ver config/pichanguero.php). --}}
                    @if (config('pichanguero.app_disponible', false))
                        <li><a href="{{ route('pichanguero.descargas') }}" class="pg-link">Descargas</a></li>
                    @endif
                </ul>
            </div>

            {{-- Atención --}}
            <div>
                <h4 class="pg-display text-sm font-bold uppercase tracking-wide text-white">Atención</h4>
                <ul class="mt-4 space-y-3 text-sm text-pg-muted">
                    <li>
                        <a href="{{ route('pichanguero.contacto') }}" class="pg-link">Contacto y demo</a>
                    </li>
                    <li>
                        <a href="https://wa.me/51917295856?text=Hola%2C%20quiero%20informaci%C3%B3n%20sobre%20Pichanguero"
                           target="_blank" rel="noopener" class="pg-link">
                            WhatsApp (+51) 917 295 856
                        </a>
                    </li>
                    <li>
                        <a href="mailto:contacto@aracodeperu.com" class="pg-link">
                            contacto@aracodeperu.com
                        </a>
                    </li>
                    <li>Nuevo Chimbote, Perú</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="border-t border-pg-line">
        <div class="pg-container flex flex-col items-center justify-between gap-3 py-5 text-xs text-pg-muted sm:flex-row">
            <p>&copy; {{ date('Y') }} Pichanguero. Todos los derechos reservados.</p>
            <div class="flex items-center gap-5">
                <a href="{{ route('pichanguero.contacto') }}" class="pg-link">Solicitar información</a>
                <a href="https://aracodeperu.com" target="_blank" rel="noopener" class="pg-link">aracodeperu.com</a>
            </div>
        </div>
    </div>
</footer>
