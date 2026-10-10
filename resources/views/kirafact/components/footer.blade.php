{{-- Footer del sitio KiraFact --}}
<footer class="border-t border-kf-line bg-kf-panel/40">
    <div class="kf-container py-14">
        <div class="grid grid-cols-1 gap-10 md:grid-cols-2 lg:grid-cols-4">
            {{-- Marca --}}
            <div class="lg:col-span-2">
                <a href="{{ route('kirafact.home') }}" class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-kf-teal to-kf-emerald">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <rect x="5" y="3" width="14" height="18" rx="2.5" stroke="#062018" stroke-width="1.8"/>
                            <path d="M9.5 16.2l1.7 1.7 3.3-3.4" stroke="#062018" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <span class="text-lg font-extrabold text-white">Kira<span class="kf-gradient-text">Fact</span></span>
                </a>
                <p class="mt-4 max-w-md text-sm leading-relaxed text-kf-muted">
                    Facturación electrónica y gestión comercial para empresas: comprobantes, inventario,
                    ventas y reportes con cumplimiento ante SUNAT.
                </p>
                <a href="https://aracodeperu.com" target="_blank" rel="noopener" class="kf-badge kf-badge-teal mt-5">
                    Un producto de ARACODE Smart Solutions
                </a>
            </div>

            {{-- Sitio --}}
            <div>
                <h4 class="text-sm font-semibold uppercase tracking-wide text-white">Sitio</h4>
                <ul class="mt-4 space-y-3 text-sm">
                    <li><a href="{{ route('kirafact.home') }}#caracteristicas" class="text-kf-muted transition-colors hover:text-kf-teal">Características</a></li>
                    <li><a href="{{ route('kirafact.home') }}#como-funciona" class="text-kf-muted transition-colors hover:text-kf-teal">Cómo funciona</a></li>
                    <li><a href="{{ route('kirafact.planes') }}" class="text-kf-muted transition-colors hover:text-kf-teal">Planes</a></li>
                    <li><a href="{{ route('kirafact.contacto') }}" class="text-kf-muted transition-colors hover:text-kf-teal">Contacto</a></li>
                </ul>
            </div>

            {{-- Contacto --}}
            <div>
                <h4 class="text-sm font-semibold uppercase tracking-wide text-white">Contacto</h4>
                <ul class="mt-4 space-y-3 text-sm text-kf-muted">
                    <li>
                        <a href="https://wa.me/51917295856?text=Hola%2C%20quiero%20informaci%C3%B3n%20sobre%20KiraFact"
                           target="_blank" rel="noopener" class="transition-colors hover:text-kf-teal">
                            WhatsApp (+51) 917 295 856
                        </a>
                    </li>
                    <li>
                        <a href="mailto:contacto@aracodeperu.com" class="transition-colors hover:text-kf-teal">
                            contacto@aracodeperu.com
                        </a>
                    </li>
                    <li>Nuevo Chimbote, Perú</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="border-t border-kf-line">
        <div class="kf-container flex flex-col items-center justify-between gap-3 py-5 text-xs text-kf-muted sm:flex-row">
            <p>&copy; {{ date('Y') }} KiraFact. Todos los derechos reservados.</p>
            <div class="flex items-center gap-5">
                <a href="{{ route('kirafact.contacto') }}" class="transition-colors hover:text-kf-teal">Solicitar asesoría</a>
                <a href="https://aracodeperu.com" target="_blank" rel="noopener" class="transition-colors hover:text-kf-teal">aracodeperu.com</a>
            </div>
        </div>
    </div>
</footer>
