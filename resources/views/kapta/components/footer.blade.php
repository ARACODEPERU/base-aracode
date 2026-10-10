{{-- Footer del sitio KAPTA --}}
<footer class="border-t border-ka-line bg-ka-panel/40">
    <div class="ka-container py-14">
        <div class="grid grid-cols-1 gap-10 md:grid-cols-2 lg:grid-cols-4">
            {{-- Marca --}}
            <div class="lg:col-span-2">
                <a href="{{ route('kapta.home') }}" class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-ka-indigo to-ka-violet">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 4l9 4.5-9 4.5-9-4.5L12 4z" fill="#fff"/>
                        </svg>
                    </span>
                    <span class="text-lg font-extrabold text-white">KAPTA <span class="ka-gradient-text">LMS</span></span>
                </a>
                <p class="mt-4 max-w-md text-sm leading-relaxed text-ka-muted">
                    Plataforma de gestión y formación educativa: cursos, aulas virtuales, evaluaciones,
                    matrículas y certificados automáticos para instituciones, academias y empresas.
                </p>
                <a href="https://aracodeperu.com" target="_blank" rel="noopener" class="ka-badge ka-badge-violet mt-5">
                    Un producto de ARACODE Smart Solutions
                </a>
            </div>

            {{-- Sitio --}}
            <div>
                <h4 class="text-sm font-semibold uppercase tracking-wide text-white">Sitio</h4>
                <ul class="mt-4 space-y-3 text-sm">
                    <li><a href="{{ route('kapta.home') }}#beneficios" class="text-ka-muted transition-colors hover:text-ka-cyan">Beneficios</a></li>
                    <li><a href="{{ route('kapta.home') }}#como-funciona" class="text-ka-muted transition-colors hover:text-ka-cyan">Cómo funciona</a></li>
                    <li><a href="{{ route('kapta.planes') }}" class="text-ka-muted transition-colors hover:text-ka-cyan">Planes</a></li>
                    <li><a href="{{ route('kapta.contacto') }}" class="text-ka-muted transition-colors hover:text-ka-cyan">Contacto</a></li>
                </ul>
            </div>

            {{-- Contacto --}}
            <div>
                <h4 class="text-sm font-semibold uppercase tracking-wide text-white">Contacto</h4>
                <ul class="mt-4 space-y-3 text-sm text-ka-muted">
                    <li>
                        <a href="https://wa.me/51917295856?text=Hola%2C%20quiero%20informaci%C3%B3n%20sobre%20KAPTA%20LMS"
                           target="_blank" rel="noopener" class="transition-colors hover:text-ka-cyan">
                            WhatsApp (+51) 917 295 856
                        </a>
                    </li>
                    <li>
                        <a href="mailto:contacto@aracodeperu.com" class="transition-colors hover:text-ka-cyan">
                            contacto@aracodeperu.com
                        </a>
                    </li>
                    <li>Nuevo Chimbote, Perú</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="border-t border-ka-line">
        <div class="ka-container flex flex-col items-center justify-between gap-3 py-5 text-xs text-ka-muted sm:flex-row">
            <p>&copy; {{ date('Y') }} KAPTA LMS. Todos los derechos reservados.</p>
            <div class="flex items-center gap-5">
                <a href="{{ route('kapta.contacto') }}" class="transition-colors hover:text-ka-cyan">Solicitar información</a>
                <a href="https://aracodeperu.com" target="_blank" rel="noopener" class="transition-colors hover:text-ka-cyan">aracodeperu.com</a>
            </div>
        </div>
    </div>
</footer>
