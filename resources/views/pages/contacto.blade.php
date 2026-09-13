@extends('layouts.webpage')

@section('meta_title', 'Contacto | ARACODE Smart Solutions')
@section('meta_description', 'Contáctanos para recibir asesoría personalizada sobre nuestras soluciones de software, automatización e inteligencia artificial.')

@section('content')
    @include('components.v2.navbar')

    {{-- Hero --}}
    <section class="pt-32 pb-20 bg-ara-navy relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-ara-blue/10 rounded-full filter blur-3xl pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="max-w-3xl mx-auto text-center">
                <span class="ara-badge ara-badge-blue mb-6 inline-block reveal">Contacto</span>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white mb-6 reveal reveal-delay-1">
                    Hablemos de tu <span class="text-gradient">proyecto</span>
                </h1>
                <p class="text-lg text-white/70 reveal reveal-delay-2">
                    Contáctanos y recibe asesoría personalizada para encontrar la solución ideal para tu empresa.
                </p>
            </div>
        </div>
    </section>

    {{-- Contact Form & Info --}}
    <section class="py-20 lg:py-28 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16">
                {{-- Form --}}
                <div class="reveal">
                    <h2 class="text-2xl font-bold text-ara-slate-700 mb-6">Envíanos un mensaje</h2>
                    
                    @if(session('success'))
                        <div class="p-4 mb-6 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <p class="text-green-700 font-medium">{{ session('success') }}</p>
                            </div>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="p-4 mb-6 bg-red-50 border border-red-200 rounded-lg">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                <p class="text-red-700 font-medium">{{ session('error') }}</p>
                            </div>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="p-4 mb-6 bg-red-50 border border-red-200 rounded-lg">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                <div>
                                    <p class="text-red-700 font-medium">Por favor corrige los siguientes errores:</p>
                                    <ul class="mt-1 list-disc list-inside text-red-600 text-sm">
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif

                    <form action="{{ route('contacto_store') }}" method="POST" class="space-y-6">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-ara-slate-700 mb-2">Nombre *</label>
                                <input type="text" name="name" required class="ara-input @error('name') border-red-500 @enderror" placeholder="Tu nombre" value="{{ old('name') }}">
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-ara-slate-700 mb-2">Email *</label>
                                <input type="email" name="email" required class="ara-input @error('email') border-red-500 @enderror" placeholder="tu@email.com" value="{{ old('email') }}">
                                @error('email')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-ara-slate-700 mb-2">Teléfono</label>
                            <input type="tel" name="phone" class="ara-input" placeholder="+51 999 999 999" value="{{ old('phone') }}">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-ara-slate-700 mb-2">Empresa</label>
                            <input type="text" name="company" class="ara-input" placeholder="Nombre de tu empresa" value="{{ old('company') }}">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-ara-slate-700 mb-2">¿En qué podemos ayudarte? *</label>
                            <select name="service" required class="ara-input @error('service') border-red-500 @enderror">
                                <option value="">Selecciona un servicio</option>
                                <option value="kapta" {{ old('service') == 'kapta' ? 'selected' : '' }}>KAPTA LMS</option>
                                <option value="facturacion" {{ old('service') == 'facturacion' ? 'selected' : '' }}>Facturación Electrónica</option>
                                <option value="desarrollo" {{ old('service') == 'desarrollo' ? 'selected' : '' }}>Desarrollo a Medida</option>
                                <option value="automatizacion" {{ old('service') == 'automatizacion' ? 'selected' : '' }}>Automatización de Procesos</option>
                                <option value="consultoria" {{ old('service') == 'consultoria' ? 'selected' : '' }}>Consultoría Tecnológica</option>
                                <option value="otro" {{ old('service') == 'otro' ? 'selected' : '' }}>Otro</option>
                            </select>
                            @error('service')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-ara-slate-700 mb-2">Mensaje *</label>
                            <textarea name="message" required class="ara-input ara-textarea @error('message') border-red-500 @enderror" rows="5" placeholder="Cuéntanos sobre tu proyecto o necesidad...">{{ old('message') }}</textarea>
                            @error('message')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <button type="submit" class="ara-btn ara-btn-primary ara-btn-lg w-full">
                            Enviar Mensaje
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                        </button>
                    </form>
                </div>

                {{-- Contact Info --}}
                <div class="reveal reveal-delay-2">
                    <h2 class="text-2xl font-bold text-ara-slate-700 mb-6">Información de contacto</h2>
                    
                    <div class="space-y-8">
                        <div class="flex items-start gap-4">
                            <div class="ara-icon-box flex-shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-ara-slate-700 mb-1">Ubicación</h4>
                                <p class="text-ara-slate-500">Nuevo Chimbote, Perú</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="ara-icon-box flex-shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-ara-slate-700 mb-1">Teléfono</h4>
                                <p class="text-ara-slate-500">(+51) 917 295 856</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="ara-icon-box flex-shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-ara-slate-700 mb-1">Email</h4>
                                <p class="text-ara-slate-500">contacto@aracodeperu.com</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="ara-icon-box flex-shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-ara-slate-700 mb-1">RUC</h4>
                                <p class="text-ara-slate-500">20611376031</p>
                            </div>
                        </div>
                    </div>

                    {{-- Social Links --}}
                    <div class="mt-10">
                        <h4 class="font-semibold text-ara-slate-700 mb-4">Síguenos</h4>
                        <div class="flex items-center gap-4">
                            <a href="https://www.facebook.com/aracodeperu" target="_blank" rel="noopener" class="w-10 h-10 rounded-full bg-ara-slate-100 flex items-center justify-center hover:bg-ara-blue hover:text-white transition-colors text-ara-slate-500" aria-label="Facebook">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.77 7.46H14.5v-1.9c0-.9.6-1.1 1-1.1h3V.5h-4.33C10.24.5 9.5 3.44 9.5 5.32v2.15h-3v4h3v12h5v-12h3.85l.42-4z"/></svg>
                            </a>
                            <a href="https://www.instagram.com/aracode_peru/" target="_blank" rel="noopener" class="w-10 h-10 rounded-full bg-ara-slate-100 flex items-center justify-center hover:bg-ara-blue hover:text-white transition-colors text-ara-slate-500" aria-label="Instagram">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                            </a>
                            <a href="https://www.linkedin.com/in/aracode-smart-solution-0b3663365" target="_blank" rel="noopener" class="w-10 h-10 rounded-full bg-ara-slate-100 flex items-center justify-center hover:bg-ara-blue hover:text-white transition-colors text-ara-slate-500" aria-label="LinkedIn">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                            </a>
                            <a href="https://www.youtube.com/@AracodePeru" target="_blank" rel="noopener" class="w-10 h-10 rounded-full bg-ara-slate-100 flex items-center justify-center hover:bg-ara-blue hover:text-white transition-colors text-ara-slate-500" aria-label="YouTube">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('components.v2.footer')
@endsection
