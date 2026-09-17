@extends('layouts.webpage')

@section('meta_title', 'Política de Cookies | ARACODE Smart Solutions')
@section('meta_description', 'Conoce cómo ARACODE Smart Solutions utiliza las cookies y tecnologías similares en su sitio web.')

@section('content')
    @include('components.v2.navbar')

    {{-- Hero --}}
    <section class="pt-32 pb-20 bg-ara-navy relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-ara-blue/10 rounded-full filter blur-3xl pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="max-w-3xl mx-auto text-center">
                <span class="ara-badge ara-badge-blue mb-6 inline-block reveal">Legal</span>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white mb-6 reveal reveal-delay-1">
                    Política de <span class="text-gradient">Cookies</span>
                </h1>
                <p class="text-lg text-white/70 reveal reveal-delay-2">
                    Última actualización: {{ date('d/m/Y') }}
                </p>
            </div>
        </div>
    </section>

    {{-- Content --}}
    <section class="py-20 lg:py-28 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- 1. ¿Qué son las cookies? --}}
            <div class="mb-12 reveal">
                <h2 class="text-2xl font-bold text-ara-slate-700 mb-4">1. ¿Qué son las cookies?</h2>
                <p class="text-ara-slate-500 leading-relaxed mb-4">
                    Las cookies son pequeños archivos de texto que se almacenan en tu dispositivo (computadora, tablet o móvil) cuando visitas un sitio web. Permiten que el sitio recuerde tus acciones y preferencias durante un período de tiempo, para que no tengas que volver a configurarlas cada vez que visites la página.
                </p>
                <p class="text-ara-slate-500 leading-relaxed">
                    Las cookies no dañan tu dispositivo ni contienen virus. Son herramientas estándar utilizadas por los sitios web para mejorar la experiencia del usuario.
                </p>
            </div>

            {{-- 2. ¿Qué cookies utilizamos? --}}
            <div class="mb-12 reveal">
                <h2 class="text-2xl font-bold text-ara-slate-700 mb-4">2. ¿Qué cookies utilizamos?</h2>

                <div class="space-y-6">
                    <div class="ara-card p-6">
                        <h3 class="text-lg font-semibold text-ara-slate-700 mb-2">Cookies Esenciales</h3>
                        <p class="text-ara-slate-500 text-sm leading-relaxed mb-2">
                            Son necesarias para el funcionamiento básico del sitio web. Sin estas cookies, el sitio no funcionaría correctamente.
                        </p>
                        <ul class="list-disc list-inside text-ara-slate-500 text-sm space-y-1">
                            <li><strong>PHPSESSID:</strong> Sesión del usuario en el servidor.</li>
                            <li><strong>XSRF-TOKEN:</strong> Protección contra ataques CSRF.</li>
                            <li><strong>Cookie Consent:</strong> Registro de tu aceptación de cookies.</li>
                        </ul>
                    </div>

                    <div class="ara-card p-6">
                        <h3 class="text-lg font-semibold text-ara-slate-700 mb-2">Cookies de Rendimiento</h3>
                        <p class="text-ara-slate-500 text-sm leading-relaxed mb-2">
                            Recopilan información anónima sobre cómo se utiliza el sitio web (páginas visitadas, tiempo de permanencia, errores encontrados). Nos ayudan a mejorar el rendimiento del sitio.
                        </p>
                        <ul class="list-disc list-inside text-ara-slate-500 text-sm space-y-1">
                            <li><strong>Google Analytics (_ga, _gid):</strong> Análisis del tráfico web.</li>
                        </ul>
                    </div>

                    <div class="ara-card p-6">
                        <h3 class="text-lg font-semibold text-ara-slate-700 mb-2">Cookies de Funcionalidad</h3>
                        <p class="text-ara-slate-500 text-sm leading-relaxed mb-2">
                            Permiten recordar tus preferencias (idioma, modo oscuro/claro, región) para ofrecerte una experiencia personalizada.
                        </p>
                        <ul class="list-disc list-inside text-ara-slate-500 text-sm space-y-1">
                            <li><strong>theme_preference:</strong> Preferencia de modo claro/oscuro.</li>
                        </ul>
                    </div>

                    <div class="ara-card p-6">
                        <h3 class="text-lg font-semibold text-ara-slate-700 mb-2">Cookies de Marketing</h3>
                        <p class="text-ara-slate-500 text-sm leading-relaxed mb-2">
                            Se utilizan para rastrear a los visitantes en los sitios web con el fin de mostrar anuncios relevantes y atractivos para el usuario individual.
                        </p>
                        <p class="text-ara-slate-500 text-sm italic">
                            Actualmente ARACODE no utiliza cookies de marketing de terceros.
                        </p>
                    </div>
                </div>
            </div>

            {{-- 3. Cómo gestionar las cookies --}}
            <div class="mb-12 reveal">
                <h2 class="text-2xl font-bold text-ara-slate-700 mb-4">3. ¿Cómo gestionar las cookies?</h2>
                <p class="text-ara-slate-500 leading-relaxed mb-4">
                    Puedes configurar tu navegador para aceptar o rechazar todas las cookies, o para recibir una notificación cuando se envíe una cookie. Ten en cuenta que si rechazas las cookies, algunas funcionalidades del sitio pueden no estar disponibles.
                </p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="ara-card p-4">
                        <h4 class="font-semibold text-ara-slate-700 mb-1">Google Chrome</h4>
                        <p class="text-ara-slate-500 text-sm">Configuración → Privacidad y seguridad → Cookies</p>
                    </div>
                    <div class="ara-card p-4">
                        <h4 class="font-semibold text-ara-slate-700 mb-1">Mozilla Firefox</h4>
                        <p class="text-ara-slate-500 text-sm">Opciones → Privacidad y seguridad → Cookies</p>
                    </div>
                    <div class="ara-card p-4">
                        <h4 class="font-semibold text-ara-slate-700 mb-1">Safari</h4>
                        <p class="text-ara-slate-500 text-sm">Preferencias → Privacidad → Cookies</p>
                    </div>
                    <div class="ara-card p-4">
                        <h4 class="font-semibold text-ara-slate-700 mb-1">Microsoft Edge</h4>
                        <p class="text-ara-slate-500 text-sm">Configuración → Privacidad → Cookies</p>
                    </div>
                </div>
            </div>

            {{-- 4. Cookies de terceros --}}
            <div class="mb-12 reveal">
                <h2 class="text-2xl font-bold text-ara-slate-700 mb-4">4. Cookies de terceros</h2>
                <p class="text-ara-slate-500 leading-relaxed mb-4">
                    Nuestro sitio web puede contener enlaces a otros sitios web. ARACODE no es responsable de las políticas de cookies de sitios de terceros. Te recomendamos revisar las políticas de privacidad de cada sitio que visites.
                </p>
                <p class="text-ara-slate-500 leading-relaxed">
                    Google Analytics es el principal servicio de terceros que utilizamos. Puedes consultar su política de privacidad en: <a href="https://policies.google.com/privacy" target="_blank" rel="noopener" class="text-ara-blue hover:underline">https://policies.google.com/privacy</a>
                </p>
            </div>

            {{-- 5. Cambios en esta política --}}
            <div class="mb-12 reveal">
                <h2 class="text-2xl font-bold text-ara-slate-700 mb-4">5. Cambios en esta política</h2>
                <p class="text-ara-slate-500 leading-relaxed">
                    ARACODE se reserva el derecho de modificar esta Política de Cookies en cualquier momento. Los cambios serán publicados en esta página con la fecha de la última actualización. Te recomendamos revisar esta página periódicamente.
                </p>
            </div>

            {{-- 6. Contacto --}}
            <div class="bg-ara-slate-50 rounded-2xl p-8 reveal">
                <h2 class="text-2xl font-bold text-ara-slate-700 mb-4">6. Contacto</h2>
                <p class="text-ara-slate-500 leading-relaxed mb-6">
                    Si tienes dudas sobre esta Política de Cookies, puedes contactarnos:
                </p>
                <ul class="space-y-2 text-ara-slate-500">
                    <li class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-ara-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span>contacto@aracodeperu.com</span>
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-ara-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <span>(+51) 917 295 856</span>
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-ara-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>Nuevo Chimbote, Perú</span>
                    </li>
                </ul>
            </div>

        </div>
    </section>

    @include('components.v2.footer')
@endsection
