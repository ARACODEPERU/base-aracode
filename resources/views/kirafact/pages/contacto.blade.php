@extends('kirafact.layouts.app')

@section('title', 'Contacto | KiraFact')
@section('description', 'Escríbenos para conocer KiraFact, agendar una demostración o resolver dudas sobre facturación electrónica y cumplimiento SUNAT.')

@section('content')
    @include('kirafact.components.navbar')

    {{-- ===================== ENCABEZADO ===================== --}}
    <section class="relative overflow-hidden pt-32 pb-16">
        <div class="absolute inset-0 -z-10">
            <img src="{{ asset('themes/kirafact/images/bg-page.jpg') }}" alt=""
                 class="h-full w-full object-cover opacity-25">
            <div class="absolute inset-0 bg-gradient-to-b from-kf-dark/85 via-kf-dark/90 to-kf-dark"></div>
        </div>

        <div class="kf-container">
            <span class="kf-badge kf-badge-teal">Contacto</span>
            <h1 class="mt-6 text-4xl font-extrabold text-white sm:text-5xl">
                Hablemos de tu <span class="kf-gradient-text">facturación</span>
            </h1>
            <p class="mt-5 max-w-2xl text-lg text-kf-muted">
                Cuéntanos cómo emites comprobantes hoy y te mostramos cómo quedaría con KiraFact.
                Te respondemos lo antes posible.
            </p>
        </div>
    </section>

    {{-- ===================== VÍAS DE CONTACTO ===================== --}}
    <section class="pb-20">
        <div class="kf-container">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <a href="https://wa.me/51917295856?text=Hola%2C%20quiero%20informaci%C3%B3n%20sobre%20KiraFact"
                   target="_blank" rel="noopener" class="kf-card group">
                    <div class="kf-icon-box">
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.347-.347.52-.52.174-.174.232-.298.347-.497.115-.198.057-.371-.06-.52-.115-.148-.66-1.59-.904-2.176-.238-.573-.48-.494-.66-.503-.171-.008-.367-.01-.563-.01-.196 0-.513.073-.782.372-.269.298-1.026 1.002-1.026 2.44s1.05 2.83 1.196 3.024c.149.199 2.066 3.155 5.006 4.425.699.302 1.245.482 1.672.616.702.223 1.34.192 1.845.116.563-.084 1.73-.707 1.974-1.39.244-.682.244-1.267.171-1.39-.073-.124-.269-.198-.563-.347zM12.05 21.785h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884a9.82 9.82 0 016.988 2.898 9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884zm8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                    </div>
                    <h2 class="mt-5 text-lg font-bold text-white transition-colors group-hover:text-kf-teal">WhatsApp</h2>
                    <p class="mt-2 text-kf-muted">(+51) 917 295 856</p>
                    <p class="mt-1 text-xs text-kf-muted">La vía más rápida para coordinar una demostración.</p>
                </a>

                <a href="mailto:contacto@aracodeperu.com?subject=Informaci%C3%B3n%20sobre%20KiraFact" class="kf-card group">
                    <div class="kf-icon-box">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                                  d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h2 class="mt-5 text-lg font-bold text-white transition-colors group-hover:text-kf-teal">Correo</h2>
                    <p class="mt-2 text-kf-muted">contacto@aracodeperu.com</p>
                    <p class="mt-1 text-xs text-kf-muted">Para requerimientos y propuestas detalladas.</p>
                </a>

                <div class="kf-card">
                    <div class="kf-icon-box">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <h2 class="mt-5 text-lg font-bold text-white">Ubicación</h2>
                    <p class="mt-2 text-kf-muted">Nuevo Chimbote, Perú</p>
                    <p class="mt-1 text-xs text-kf-muted">Atendemos a todo el país de forma remota.</p>
                </div>
            </div>

            {{-- CTA de WhatsApp --}}
            <div class="relative mt-12 overflow-hidden rounded-3xl border border-kf-line bg-gradient-to-br from-kf-panel to-kf-panel2 p-10 text-center lg:p-14">
                <div class="pointer-events-none absolute -left-16 -bottom-16 h-48 w-48 rounded-full bg-kf-teal/20 blur-3xl"></div>

                <h2 class="relative text-2xl font-extrabold text-white sm:text-3xl">
                    Solicita una demostración de KiraFact
                </h2>
                <p class="relative mx-auto mt-4 max-w-2xl text-kf-muted">
                    Te mostramos la emisión de comprobantes, el envío a SUNAT y los reportes de ventas.
                </p>

                <div class="relative mt-8 flex flex-col justify-center gap-4 sm:flex-row">
                    <a href="https://wa.me/51917295856?text=Hola%2C%20quiero%20una%20demo%20de%20KiraFact"
                       target="_blank" rel="noopener" class="kf-btn kf-btn-primary kf-btn-lg">
                        Escribir por WhatsApp
                    </a>
                    <a href="{{ route('kirafact.planes') }}" class="kf-btn kf-btn-ghost kf-btn-lg">
                        Ver planes
                    </a>
                </div>
            </div>
        </div>
    </section>

    @include('kirafact.components.footer')
@endsection
