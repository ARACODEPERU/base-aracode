@extends('layouts.webpage')

@section('meta_title', 'Preguntas Frecuentes | ARACODE Smart Solutions')
@section('meta_description', 'Resuelve tus dudas sobre nuestros servicios, productos, precios y soporte técnico.')

@section('content')
    @include('components.v2.navbar')

    {{-- Hero --}}
    <section class="pt-32 pb-20 bg-ara-navy relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-ara-blue/10 rounded-full filter blur-3xl pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="max-w-3xl mx-auto text-center">
                <span class="ara-badge ara-badge-blue mb-6 inline-block reveal">Soporte</span>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white mb-6 reveal reveal-delay-1">
                    Preguntas <span class="text-gradient">Frecuentes</span>
                </h1>
                <p class="text-lg text-white/70 reveal reveal-delay-2">
                    Encuentra respuestas a las dudas más comunes sobre nuestros servicios y productos.
                </p>
            </div>
        </div>
    </section>

    {{-- FAQ Content --}}
    <section class="py-20 lg:py-28 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- KAPTA LMS --}}
            <div class="mb-16 reveal">
                <h2 class="text-2xl font-bold text-ara-slate-700 mb-8 flex items-center gap-3">
                    <span class="w-10 h-10 rounded-lg bg-ara-blue/10 flex items-center justify-center">
                        <svg class="w-5 h-5 text-ara-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </span>
                    KAPTA LMS
                </h2>

                <div class="space-y-4">
                    <div class="faq-item border border-ara-slate-100 rounded-xl overflow-hidden">
                        <button class="faq-toggle w-full px-6 py-5 text-left flex items-center justify-between hover:bg-ara-slate-50 transition-colors">
                            <span class="font-semibold text-ara-slate-700">¿Qué es KAPTA LMS?</span>
                            <svg class="w-5 h-5 text-ara-slate-400 transition-transform faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div class="faq-content hidden px-6 pb-5">
                            <p class="text-ara-slate-500 leading-relaxed">KAPTA LMS es nuestra plataforma SaaS de aprendizaje electrónico diseñada para instituciones educativas y empresas. Permite crear cursos, aulas virtuales, emitir certificaciones automáticas y gestionar estudiantes con un panel administrativo completo.</p>
                        </div>
                    </div>

                    <div class="faq-item border border-ara-slate-100 rounded-xl overflow-hidden">
                        <button class="faq-toggle w-full px-6 py-5 text-left flex items-center justify-between hover:bg-ara-slate-50 transition-colors">
                            <span class="font-semibold text-ara-slate-700">¿Cuánto cuesta KAPTA LMS?</span>
                            <svg class="w-5 h-5 text-ara-slate-400 transition-transform faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div class="faq-content hidden px-6 pb-5">
                            <p class="text-ara-slate-500 leading-relaxed">KAPTA LMS ofrece planes desde S/ 35/mes (Emprendedor) hasta planes personalizados (Enterprise). Incluye soporte, actualizaciones y hosting. Visita nuestra <a href="{{ route('solucion_kapta') }}" class="text-ara-blue hover:underline">página de KAPTA</a> para ver todos los planes.</p>
                        </div>
                    </div>

                    <div class="faq-item border border-ara-slate-100 rounded-xl overflow-hidden">
                        <button class="faq-toggle w-full px-6 py-5 text-left flex items-center justify-between hover:bg-ara-slate-50 transition-colors">
                            <span class="font-semibold text-ara-slate-700">¿Puedo personalizar la plataforma?</span>
                            <svg class="w-5 h-5 text-ara-slate-400 transition-transform faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div class="faq-content hidden px-6 pb-5">
                            <p class="text-ara-slate-500 leading-relaxed">Sí. KAPTA LMS permite personalizar logos, colores, certificados y estructura de cursos. Los planes Enterprise incluyen personalización avanzada e integraciones API.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Facturación --}}
            <div class="mb-16 reveal reveal-delay-1">
                <h2 class="text-2xl font-bold text-ara-slate-700 mb-8 flex items-center gap-3">
                    <span class="w-10 h-10 rounded-lg bg-ara-blue/10 flex items-center justify-center">
                        <svg class="w-5 h-5 text-ara-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                        </svg>
                    </span>
                    Facturación Electrónica
                </h2>

                <div class="space-y-4">
                    <div class="faq-item border border-ara-slate-100 rounded-xl overflow-hidden">
                        <button class="faq-toggle w-full px-6 py-5 text-left flex items-center justify-between hover:bg-ara-slate-50 transition-colors">
                            <span class="font-semibold text-ara-slate-700">¿Está homologado por SUNAT?</span>
                            <svg class="w-5 h-5 text-ara-slate-400 transition-transform faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div class="faq-content hidden px-6 pb-5">
                            <p class="text-ara-slate-500 leading-relaxed">Sí, nuestro sistema de facturación electrónica está homologado por SUNAT y cumple con todas las normativas vigentes para emisión de facturas, boletas, notas de crédito y notas de débito.</p>
                        </div>
                    </div>

                    <div class="faq-item border border-ara-slate-100 rounded-xl overflow-hidden">
                        <button class="faq-toggle w-full px-6 py-5 text-left flex items-center justify-between hover:bg-ara-slate-50 transition-colors">
                            <span class="font-semibold text-ara-slate-700">¿Qué comprobantes puedo emitir?</span>
                            <svg class="w-5 h-5 text-ara-slate-400 transition-transform faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div class="faq-content hidden px-6 pb-5">
                            <p class="text-ara-slate-500 leading-relaxed">Puedes emitir facturas, boletas, notas de crédito, notas de débito y guías de remisión. El sistema genera automáticamente el XML y PDF, y envía los comprobantes a SUNAT en tiempo real.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- General --}}
            <div class="mb-16 reveal reveal-delay-2">
                <h2 class="text-2xl font-bold text-ara-slate-700 mb-8 flex items-center gap-3">
                    <span class="w-10 h-10 rounded-lg bg-ara-blue/10 flex items-center justify-center">
                        <svg class="w-5 h-5 text-ara-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </span>
                    General
                </h2>

                <div class="space-y-4">
                    <div class="faq-item border border-ara-slate-100 rounded-xl overflow-hidden">
                        <button class="faq-toggle w-full px-6 py-5 text-left flex items-center justify-between hover:bg-ara-slate-50 transition-colors">
                            <span class="font-semibold text-ara-slate-700">¿Qué servicios ofrece ARACODE?</span>
                            <svg class="w-5 h-5 text-ara-slate-400 transition-transform faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div class="faq-content hidden px-6 pb-5">
                            <p class="text-ara-slate-500 leading-relaxed">Ofrecemos desarrollo de software a medida, automatización de procesos, inteligencia artificial, plataformas SaaS, facturación electrónica y transformación digital. <a href="{{ route('soluciones') }}" class="text-ara-blue hover:underline">Ver todas las soluciones</a>.</p>
                        </div>
                    </div>

                    <div class="faq-item border border-ara-slate-100 rounded-xl overflow-hidden">
                        <button class="faq-toggle w-full px-6 py-5 text-left flex items-center justify-between hover:bg-ara-slate-50 transition-colors">
                            <span class="font-semibold text-ara-slate-700">¿Cómo solicito una cotización?</span>
                            <svg class="w-5 h-5 text-ara-slate-400 transition-transform faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div class="faq-content hidden px-6 pb-5">
                            <p class="text-ara-slate-500 leading-relaxed">Puedes solicitar una cotización a través de nuestro <a href="{{ route('contacto') }}" class="text-ara-blue hover:underline">formulario de contacto</a> o llamándonos al (+51) 917 295 856. Respondemos en menos de 24 horas.</p>
                        </div>
                    </div>

                    <div class="faq-item border border-ara-slate-100 rounded-xl overflow-hidden">
                        <button class="faq-toggle w-full px-6 py-5 text-left flex items-center justify-between hover:bg-ara-slate-50 transition-colors">
                            <span class="font-semibold text-ara-slate-700">¿Ofrecen soporte técnico?</span>
                            <svg class="w-5 h-5 text-ara-slate-400 transition-transform faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div class="faq-content hidden px-6 pb-5">
                            <p class="text-ara-slate-500 leading-relaxed">Sí, ofrecemos soporte técnico por email, teléfono y chat. Los planes Premium incluyen soporte prioritario con tiempo de respuesta garantizado.</p>
                        </div>
                    </div>

                    <div class="faq-item border border-ara-slate-100 rounded-xl overflow-hidden">
                        <button class="faq-toggle w-full px-6 py-5 text-left flex items-center justify-between hover:bg-ara-slate-50 transition-colors">
                            <span class="font-semibold text-ara-slate-700">¿Trabajan con empresas de todo Perú?</span>
                            <svg class="w-5 h-5 text-ara-slate-400 transition-transform faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div class="faq-content hidden px-6 pb-5">
                            <p class="text-ara-slate-500 leading-relaxed">Sí, trabajamos con empresas e instituciones de todo Perú. Nuestros servicios son 100% digitales, lo que nos permite atender clientes desde cualquier ubicación del país.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CTA --}}
            <div class="text-center bg-ara-slate-50 rounded-2xl p-12 reveal">
                <h3 class="text-2xl font-bold text-ara-slate-700 mb-4">¿No encontraste tu respuesta?</h3>
                <p class="text-ara-slate-500 mb-8">Nuestro equipo está listo para ayudarte con cualquier duda.</p>
                <a href="{{ route('contacto') }}" class="ara-btn ara-btn-primary">
                    Contáctanos
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </a>
            </div>

        </div>
    </section>

    @include('components.v2.footer')

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.faq-toggle').forEach(btn => {
            btn.addEventListener('click', function() {
                const content = this.nextElementSibling;
                const icon = this.querySelector('.faq-icon');
                const isOpen = !content.classList.contains('hidden');

                // Close all
                document.querySelectorAll('.faq-content').forEach(c => c.classList.add('hidden'));
                document.querySelectorAll('.faq-icon').forEach(i => i.style.transform = 'rotate(0deg)');

                // Open clicked if it was closed
                if (!isOpen) {
                    content.classList.remove('hidden');
                    icon.style.transform = 'rotate(180deg)';
                }
            });
        });
    });
    </script>
@endsection
