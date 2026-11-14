@extends('aracode.layouts.webpage')

@section('meta_title', 'Empresa | ARACODE Smart Solutions')
@section('meta_description', 'Conoce a ARACODE Smart Solutions. Empresa peruana de tecnología especializada en desarrollo de software, automatización e IA.')

@section('content')
    @include('aracode.components.v2.navbar')

    {{-- Hero (formato común de páginas internas) --}}
    <x-v2.hero-pagina
        etiqueta="Sobre Nosotros"
        titulo="Conoce a"
        resaltado="ARACODE"
        bajada="Empresa peruana especializada en desarrollo de software empresarial, automatización de procesos e inteligencia artificial."
    />

    {{-- Sobre la empresa
         Antes el texto se centraba contra una foto de 1080×1578: el bloque
         quedaba flotando 300 px por debajo del borde de la imagen y sobraba
         medio lienzo vacío. Ahora las dos columnas arrancan arriba, la foto
         se recorta a un cuadrado y los beneficios van en una rejilla de dos
         por dos, no como una lista de promesas suelta. El badge «Quiénes
         Somos» se quita: el hero ya dice «Sobre Nosotros». --}}
    <section class="ara-about" id="nosotros">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="ara-about__grid">
                <div class="ara-about__texto">
                    <h2 class="ara-about__titulo reveal">Tecnología que impulsa negocios</h2>

                    <p class="ara-about__lead reveal reveal-delay-1">
                        Somos una empresa peruana especializada en el desarrollo de software a medida y productos digitales innovadores.
                    </p>

                    <p class="ara-about__parrafo reveal reveal-delay-2">
                        Nuestro enfoque combina tecnología moderna + inteligencia artificial para crear soluciones eficientes, escalables y alineadas a las necesidades reales de empresas, instituciones educativas y organizaciones en crecimiento.
                    </p>

                    @php
                        $pilares = [
                            [
                                'titulo' => 'Procesos internos',
                                'texto' => 'Menos tareas manuales y más tiempo para lo que sí importa.',
                                'icono' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
                            ],
                            [
                                'titulo' => 'Menos errores',
                                'texto' => 'Flujos controlados y los datos en un solo lugar.',
                                'icono' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>',
                            ],
                            [
                                'titulo' => 'Mejor experiencia',
                                'texto' => 'Interfaces claras para tu equipo y para tus clientes.',
                                'icono' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/></svg>',
                            ],
                            [
                                'titulo' => 'Crecimiento',
                                'texto' => 'Arquitectura modular: sumamos funciones sin rehacer nada.',
                                'icono' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>',
                            ],
                        ];
                    @endphp

                    <ul class="ara-about__pilares reveal reveal-delay-3">
                        @foreach ($pilares as $pilar)
                            <li class="ara-about__pilar">
                                <span class="ara-about__pilar-icono" aria-hidden="true">{!! $pilar['icono'] !!}</span>

                                <span class="ara-about__pilar-texto">
                                    <strong>{{ $pilar['titulo'] }}</strong>
                                    <span>{{ $pilar['texto'] }}</span>
                                </span>
                            </li>
                        @endforeach
                    </ul>

                    <a href="{{ route('equipo') }}" class="ara-about__enlace reveal reveal-delay-4">
                        Conoce al equipo
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </a>
                </div>

                {{-- El lugar de la foto lo ocupa un dibujo propio: el recorrido de
                     un dato por el sistema, en tres etapas. Se encienden en orden
                     con public/js/aracode-evolucion.js (`[data-flujo]`). Sin JS
                     queda la primera etapa marcada y se leen las tres. --}}
                <figure class="ara-about__media reveal reveal-delay-2">
                    <div class="ara-flujo" data-flujo>
                        <div class="ara-flujo__cabecera">
                            <span class="ara-flujo__punto" aria-hidden="true"></span>
                            <span class="ara-flujo__titulo">Proceso automatizado</span>
                            <span class="ara-flujo__marca">ARACODE · IA</span>
                        </div>

                        <ol class="ara-flujo__pasos">
                            <li class="ara-flujo__paso is-activo" data-flujo-paso>
                                <span class="ara-flujo__icono" aria-hidden="true">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </span>
                                <span class="ara-flujo__texto">
                                    <span class="ara-flujo__etapa">Entrada</span>
                                    <span class="ara-flujo__nombre">Formulario web</span>
                                </span>
                            </li>

                            <li class="ara-flujo__paso" data-flujo-paso>
                                <span class="ara-flujo__icono" aria-hidden="true">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                                </span>
                                <span class="ara-flujo__texto">
                                    <span class="ara-flujo__etapa">Motor</span>
                                    <span class="ara-flujo__nombre">IA + reglas del negocio</span>
                                </span>
                            </li>

                            <li class="ara-flujo__paso" data-flujo-paso>
                                <span class="ara-flujo__icono" aria-hidden="true">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </span>
                                <span class="ara-flujo__texto">
                                    <span class="ara-flujo__etapa">Salida</span>
                                    <span class="ara-flujo__nombre">Documento listo</span>
                                </span>
                            </li>
                        </ol>

                        <p class="ara-flujo__nota">Sin intervención manual, con registro de cada paso.</p>
                    </div>

                    <figcaption class="ara-about__pie">
                        Entra el dato, la IA lo clasifica y sale el documento.
                    </figcaption>
                </figure>
            </div>
        </div>
    </section>

    {{-- Misión y visión
         La sección no tenía titular: empezaba directamente con dos tarjetas.
         Ahora abre con su cabecera, y las tarjetas usan la escala del sitio
         (título de tarjeta 20 px, texto 16) y un icono más chico, que en móvil
         dejaba la tarjeta demasiado alta. --}}
    <section class="ara-mv" id="mision">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="ara-mv__titulo reveal">Misión y visión</h2>

            <p class="ara-mv__lead reveal reveal-delay-1">
                Dos ideas fijan nuestro rumbo: por qué existimos y hacia dónde vamos.
            </p>

            <div class="ara-mv__rejilla">
                <article class="ara-mv__tarjeta reveal">
                    <span class="ara-mv__icono ara-mv__icono--verde" aria-hidden="true">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </span>

                    <h3 class="ara-mv__nombre">Nuestra misión</h3>

                    <p class="ara-mv__texto">
                        Desarrollar soluciones de software innovadoras y eficientes que impulsen la transformación digital de empresas e instituciones, potenciadas con inteligencia artificial y tecnología moderna.
                    </p>
                </article>

                <article class="ara-mv__tarjeta reveal reveal-delay-1">
                    <span class="ara-mv__icono" aria-hidden="true">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                        </svg>
                    </span>

                    <h3 class="ara-mv__nombre">Nuestra visión</h3>

                    <p class="ara-mv__texto">
                        Ser reconocidos como una empresa líder en desarrollo de software y soluciones digitales en Perú y Latinoamérica, destacando por nuestra innovación, calidad y compromiso con el éxito de nuestros clientes.
                    </p>
                </article>
            </div>
        </div>
    </section>

    {{-- Values --}}
    <section class="py-20 lg:py-28 bg-ara-navy" id="valores">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-v2.section-heading
                badge="Valores"
                title="Nuestros valores"
                subtitle="Los principios que guían nuestro trabajo y relación con los clientes."
            />

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                @php
                    $values = [
                        [
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>',
                            'title' => 'Innovación',
                            'description' => 'Buscamos constantemente nuevas formas de resolver problemas con tecnología.',
                        ],
                        [
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>',
                            'title' => 'Confianza',
                            'description' => 'Construimos relaciones sólidas basadas en la transparencia y el cumplimiento.',
                        ],
                        [
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>',
                            'title' => 'Calidad',
                            'description' => 'Aplicamos estándares de excelencia en cada línea de código que escribimos.',
                        ],
                        [
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
                            'title' => 'Compromiso',
                            'description' => 'Nos comprometemos con el éxito de cada proyecto y cada cliente.',
                        ],
                    ];
                @endphp

                @foreach($values as $index => $value)
                    <div class="text-center reveal reveal-delay-{{ $index + 1 }}">
                        <div class="ara-icon-box mx-auto mb-6">
                            {!! $value['icon'] !!}
                        </div>
                        <h4 class="text-xl font-bold text-white mb-3">{{ $value['title'] }}</h4>
                        <p class="text-white/70 leading-relaxed">{{ $value['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Clients --}}
    <section class="py-20 lg:py-28 bg-white" id="clientes">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-v2.section-heading
                badge="Clientes"
                title="Empresas que confían en nosotros"
                subtitle="Instituciones y empresas que han elegido nuestras soluciones digitales."
                :light="false"
            />

            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-8">
                @php
                    $clients = ['brise', 'iprase', 'cpa', 'horizonte', 'orbe', 'cprod', 'jrrss', 'zoelife', 'celmovil', 'cap', 'kentha'];
                @endphp

                @foreach($clients as $index => $client)
                    <div class="flex items-center justify-center p-4 rounded-lg border border-ara-slate-100 hover:border-ara-blue/30 transition-colors reveal reveal-delay-{{ ($index % 6) + 1 }}">
                        <img 
                            src="{{ asset('themes/webpage/images/customers/' . $client . '.png') }}" 
                            alt="Cliente {{ ucfirst($client) }}" 
                            class="ara-client-logo max-h-12 object-contain"
                            loading="lazy"
                        >
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <x-v2.cta-section />

    @include('aracode.components.v2.footer')
@endsection
