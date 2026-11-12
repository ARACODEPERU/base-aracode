{{--
    CTA «Cuéntanos qué quieres automatizar» (solo portada)
    ---------------------------------------------------------------------------
    Reproduce la sección del prototipo A: tarjeta de vidrio con un degradado
    cónico girando en el fondo y titular con la última palabra en degradado.

    La acción es un solo botón centrado: no pide el correo aquí porque el modal
    de asesoría ya trae el formulario completo (nombre, email, teléfono, empresa,
    servicio y mensaje). El botón lleva `data-asesoria`, igual que los demás del
    sitio, así que el modal lo intercepta y abre con el formulario. Sin
    JavaScript sigue siendo un enlace a /contacto, así que nunca queda un botón
    muerto.
--}}
<section class="ara-cta-auto" id="empezar">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="ara-cta-auto__panel reveal">
            <span class="ara-cta-auto__eyebrow">Empecemos</span>

            <h2 class="ara-cta-auto__titulo">
                Cuéntanos qué quieres <span class="text-gradient">automatizar</span>
            </h2>

            <p class="ara-cta-auto__lead">
                Te respondemos con una propuesta concreta: alcance, tiempos y precio. Sin compromiso.
            </p>

            <div class="ara-cta-auto__acciones">
                <a href="{{ route('contacto') }}" data-asesoria class="ara-btn ara-btn-primary ara-cta-auto__btn">
                    Solicitar asesoría
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</section>
