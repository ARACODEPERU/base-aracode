{{-- Ayuda del aula: instrucciones cortas y datos de contacto reales del colegio. --}}
<dialog id="ayudaDialog" class="aula-dialog" aria-labelledby="ayudaTitle">
    <div class="aula-dialog__card">
        <div class="flex items-start justify-between gap-4">
            <h2 id="ayudaTitle" class="font-display text-xl text-cole-forest">¿Necesitas ayuda?</h2>
            <button type="button" class="aula-icon-btn" data-help-close aria-label="Cerrar la ayuda">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                    <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/>
                </svg>
            </button>
        </div>

        <p class="mt-2 text-xs font-semibold leading-relaxed text-cole-ink/65">
            Este es tu espacio para aprender en el nivel Inicial. Todo lo que ves lo prepara tu profesora.
        </p>

        <div class="aula-dialog__step">
            <span>1</span>
            <span>Elige una tarjeta de curso para ver sus unidades y sesiones.</span>
        </div>
        <div class="aula-dialog__step">
            <span>2</span>
            <span>Abre cada sesión para leer lo que aprenderás y ver sus materiales.</span>
        </div>
        <div class="aula-dialog__step">
            <span>3</span>
            <span>Si tu matrícula cambió de nivel, pide en secretaría que actualicen tus datos.</span>
        </div>

        @if ($portal['school']['phone'] || $portal['school']['email'])
            <div class="mt-4 rounded-2xl border border-cole-sand bg-white p-3">
                <p class="text-[10px] font-black uppercase tracking-[0.14em] text-cole-emerald">Habla con el colegio</p>

                @if ($portal['school']['phone'])
                    <a href="tel:{{ $portal['school']['phone_digits'] }}"
                       class="mt-2 flex items-center gap-2 text-sm font-extrabold text-cole-forest hover:text-cole-emerald">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 4.8A2 2 0 0 1 6.4 3h2.2c.8 0 1.4.5 1.6 1.3l.7 3a1.7 1.7 0 0 1-.6 1.7L9 10.3a12 12 0 0 0 4.7 4.7l1.3-1.3c.4-.5 1.1-.7 1.7-.6l3 .7c.8.2 1.3.8 1.3 1.6v2.2a2 2 0 0 1-1.8 1.9C10.6 19.9 4.1 13.4 4.5 4.8z"/>
                        </svg>
                        {{ $portal['school']['phone'] }}
                    </a>
                @endif

                @if ($portal['school']['email'])
                    <a href="mailto:{{ $portal['school']['email'] }}"
                       class="mt-2 flex items-center gap-2 text-sm font-extrabold text-cole-forest hover:text-cole-emerald">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <rect x="3" y="5.5" width="18" height="13" rx="3"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4 7 8 6 8-6"/>
                        </svg>
                        {{ $portal['school']['email'] }}
                    </a>
                @endif
            </div>
        @endif

        <button type="button" class="cole-btn cole-btn-primary mt-4 h-12 w-full text-sm" data-help-close>
            Entendido
        </button>
    </div>
</dialog>
