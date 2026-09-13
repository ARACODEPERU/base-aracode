@extends('layouts.webpage')

@section('meta_title', 'Libro de Reclamaciones | ARACODE Smart Solutions')
@section('meta_description', 'Libro de Reclamaciones virtual de ARACODE Smart Solutions. Registra tu queja o reclamo según las normas de INDECOPI.')

@section('content')
    @include('components.v2.navbar')

    {{-- Hero --}}
    <section class="pt-32 pb-16 bg-ara-navy relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-ara-blue/10 rounded-full filter blur-3xl pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="max-w-3xl mx-auto text-center">
                <span class="ara-badge ara-badge-blue mb-6 inline-block reveal">Legal</span>
                <h1 class="text-4xl sm:text-5xl font-bold text-white mb-6 reveal reveal-delay-1">
                    Libro de <span class="text-gradient">Reclamaciones</span>
                </h1>
                <p class="text-lg text-white/70 reveal reveal-delay-2">
                    En cumplimiento de la normativa de INDECOPI
                </p>
            </div>
        </div>
    </section>

    {{-- Content --}}
    <section class="py-20 lg:py-28 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Info Box --}}
            <div class="bg-ara-blue/5 border border-ara-blue/20 rounded-xl p-6 mb-12 reveal">
                <div class="flex items-start gap-4">
                    <div class="ara-icon-box flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-ara-slate-700 mb-2">¿Qué es el Libro de Reclamaciones?</h3>
                        <p class="text-ara-slate-500 text-sm leading-relaxed">
                            Es un mecanismo establecido por INDECOPI que permite a los consumidores registrar quejas o reclamos sobre productos y servicios. En ARACODE, nos comprometemos a atender y resolver cada reclamo de manera oportuna.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Form --}}
            <div class="reveal reveal-delay-1">
                <h2 class="text-2xl font-bold text-ara-slate-700 mb-8">Registrar Reclamo</h2>

                <form action="#" method="POST" class="space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-ara-slate-700 mb-2">Tipo de Documento *</label>
                            <select name="document_type" required class="ara-input">
                                <option value="">Selecciona</option>
                                <option value="dni">DNI</option>
                                <option value="ce">Carnet de Extranjería</option>
                                <option value="pasaporte">Pasaporte</option>
                                <option value="ruc">RUC</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ara-slate-700 mb-2">Nro. de Documento *</label>
                            <input type="text" name="document_number" required class="ara-input" placeholder="Ingrese su número de documento">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-ara-slate-700 mb-2">Nombres Completos *</label>
                            <input type="text" name="full_name" required class="ara-input" placeholder="Nombres y apellidos">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ara-slate-700 mb-2">Teléfono *</label>
                            <input type="tel" name="phone" required class="ara-input" placeholder="+51 999 999 999">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-ara-slate-700 mb-2">Correo Electrónico *</label>
                        <input type="email" name="email" required class="ara-input" placeholder="tu@email.com">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-ara-slate-700 mb-2">Dirección</label>
                        <input type="text" name="address" class="ara-input" placeholder="Dirección completa">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-ara-slate-700 mb-2">Tipo de Reclamo *</label>
                        <select name="claim_type" required class="ara-input">
                            <option value="">Selecciona</option>
                            <option value="queja">Queja</option>
                            <option value="reclamo">Reclamo</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-ara-slate-700 mb-2">Producto o Servicio *</label>
                        <select name="product_service" required class="ara-input">
                            <option value="">Selecciona</option>
                            <option value="kapta">KAPTA LMS</option>
                            <option value="facturacion">Facturación Electrónica</option>
                            <option value="desarrollo">Desarrollo de Software</option>
                            <option value="consultoria">Consultoría Tecnológica</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-ara-slate-700 mb-2">Detalle del Reclamo *</label>
                        <textarea name="details" required class="ara-input ara-textarea" rows="6" placeholder="Describa detalladamente su queja o reclamo..."></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-ara-slate-700 mb-2">Monto Reclamado (S/)</label>
                        <input type="number" name="amount" step="0.01" class="ara-input" placeholder="0.00">
                    </div>

                    <div class="flex items-start gap-3">
                        <input type="checkbox" name="terms" required class="mt-1" id="terms">
                        <label for="terms" class="text-sm text-ara-slate-500">
                            He leído y acepto la <a href="#" class="text-ara-blue hover:underline">Política de Privacidad</a> y los <a href="#" class="text-ara-blue hover:underline">Términos y Condiciones</a>.
                        </label>
                    </div>

                    <button type="submit" class="ara-btn ara-btn-primary ara-btn-lg w-full">
                        Registrar Reclamo
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </button>
                </form>
            </div>

            {{-- Company Info --}}
            <div class="mt-16 pt-12 border-t border-ara-slate-100 reveal reveal-delay-2">
                <h3 class="text-xl font-bold text-ara-slate-700 mb-6">Datos de la Empresa</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-ara-slate-400 mb-1">Razón Social</p>
                        <p class="font-medium text-ara-slate-700">ARACODE Smart Solutions S.A.C.</p>
                    </div>
                    <div>
                        <p class="text-sm text-ara-slate-400 mb-1">RUC</p>
                        <p class="font-medium text-ara-slate-700">20611376031</p>
                    </div>
                    <div>
                        <p class="text-sm text-ara-slate-400 mb-1">Dirección</p>
                        <p class="font-medium text-ara-slate-700">Nuevo Chimbote, Perú</p>
                    </div>
                    <div>
                        <p class="text-sm text-ara-slate-400 mb-1">Correo Electrónico</p>
                        <p class="font-medium text-ara-slate-700">contacto@aracodeperu.com</p>
                    </div>
                </div>
            </div>

        </div>
    </section>

    @include('components.v2.footer')
@endsection
