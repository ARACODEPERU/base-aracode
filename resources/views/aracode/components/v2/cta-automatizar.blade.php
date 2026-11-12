{{--
    CTA «Cuéntanos qué quieres automatizar» (solo portada)
    ---------------------------------------------------------------------------
    Reproduce la sección del prototipo A: tarjeta de vidrio con un degradado
    cónico girando en el fondo, titular con la última palabra en degradado y
    un formulario de correo.

    El formulario no inventa un envío: «Enviar» abre el modal de asesoría con
    el correo ya escrito (ver components/v2/asesoria-modal.blade.php). Por eso
    el botón lleva `data-asesoria`, igual que los demás del sitio. Sin
    JavaScript sigue siendo un GET a /contacto, así que nunca queda un botón
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

            <form class="ara-cta-auto__form" id="ctaEmail" method="get" action="{{ route('contacto') }}">
                <input id="ctaEmailInput" class="ara-cta-auto__input" type="email" name="email"
                       placeholder="tu@empresa.com" aria-label="Tu correo de empresa"
                       autocomplete="email" maxlength="255" required>

                <button type="submit" data-asesoria class="ara-btn ara-btn-primary ara-cta-auto__btn">
                    Enviar
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</section>
