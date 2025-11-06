<div>
    <section id="productos" class="bg-dark section-dark text-light pt-80 relative jarallax" aria-label="section">
        <img src="{{ asset('themes/webpage/images/background/7.webp') }}" class="jarallax-img" alt="">
        <div class="gradient-edge-top"></div>
        <div class="gradient-edge-bottom"></div>
        <div class="sw-overlay op-7"></div>

        <div class="container relative z-2">
            <div class="row g-4 gx-5 justify-content-center">
                <div class="col-lg-6 text-center">
                    <div class="subtitle s2 mb-3 wow fadeInUp" data-wow-delay=".0s">Productos</div>
                    <h2 class="wow fadeInUp" data-wow-delay=".2s">Productos principales</h2>
                    <p class="lead wow fadeInUp" data-wow-delay=".4s">
                        Descubre nuestras soluciones listas para usar, diseñadas para impulsar la digitalización de tu
                        empresa.
                    </p>
                </div>
            </div>

            <div class="row g-4 justify-content-center">
                <div class="col-lg-12">
                    <div class="owl-carousel owl-theme owl-3-dots wow mask-right">
                        
                        <!-- ticket item begin -->
                        <div class="item">
                            <div class="d-ticket s2">
                                {{-- <img src="{{ asset('themes/webpage/images/logo.webp') }}" class="w-80px mb-4"
                                    alt=""> --}}
                                <img src="{{ asset('themes/webpage/images/misc/barcode.webp') }}"
                                    class="w-20 p-2 abs abs-middle end-0 me-2" alt="img-producto-3">
                                <img src="{{ asset('themes/webpage/images/icono-white.png') }}"
                                    class="w-40 abs abs-centered me-4 op-2" alt="">
                                <h2>Campus Virtual</h2>
                                <h4 class="mb-4">Plataforma E-Learning (LMS)</h4>
                                <div class="fs-14">Características principales</div>
                            </div>
                            <div class="relative">
                                <div class="py-4 z-2">
                                    <ul class="ul-check mb-4">
                                        <li>Gestión completa de cursos, módulos y evaluaciones.</li>
                                        <li>Aulas virtuales con contenido multimedia (videos, documentos, SCORM, PDF,
                                            enlaces).</li>
                                        <li>Certificación automática personalizada.</li>
                                        <li>Control de matrículas y seguimiento académico.</li>
                                        <li>Integración con pasarelas de pago.</li>
                                        <li>Panel administrativo con métricas de rendimiento.</li>
                                        <li>Optimizado con IA para análisis de progreso y generación de reportes.</li>
                                    </ul>
                                </div>
                            </div>

                            <a class="btn-main fx-slide w-100" href=""><span>Más Información</span></a>
                        </div>
                        <!-- ticket item end -->
                        
                        <!-- ticket item begin -->
                        <div class="item">
                            <div class="d-ticket">
                                {{-- <img src="{{ asset('themes/webpage/images/logo.webp') }}" class="w-80px mb-4"
                                    alt=""> --}}
                                <img src="{{ asset('themes/webpage/images/misc/barcode.webp') }}"
                                    class="w-20 p-2 abs abs-middle end-0 me-2" alt="">
                                <img src="{{ asset('themes/webpage/images/icono-white.png') }}"
                                    class="w-40 abs abs-centered me-4 op-2" alt="">
                                <h2>Sitios Webs</h2>
                                <h4 class="mb-4">Gestión de Contenidos</h4>
                                <div class="fs-14">Características principales</div>
                            </div>

                            <div class="relative overflow-hidden">
                                <div class="py-4 z-2">
                                    <ul class="ul-check mb-4">
                                        <li>Editor visual intuitivo.</li>
                                        <li>Gestión de páginas, menús, categorías y contenido multimedia.</li>
                                        <li>Módulos personalizables (noticias, eventos, recursos, galería, documentos).
                                        </li>
                                        <li>Optimizado para SEO y velocidad de carga.</li>
                                        <li>Multiusuario con roles y permisos.</li>
                                        <li>Integración con IA para redacción, optimización y análisis de contenido.
                                        </li>
                                    </ul>
                                </div>

                                <a class="btn-main fx-slide w-100" href=""><span>Más Información</span></a>

                            </div>
                        </div>
                        <!-- ticket item end -->

                        <!-- ticket item begin -->
                        <div class="item">
                            <div class="d-ticket">
                                {{-- <img src="{{ asset('themes/webpage/images/logo.webp') }}" class="w-80px mb-4"
                                    alt=""> --}}
                                <img src="{{ asset('themes/webpage/images/misc/barcode.webp') }}"
                                    class="w-20 p-2 abs abs-middle end-0 me-2" alt="">
                                <img src="{{ asset('themes/webpage/images/icono-white.png') }}"
                                    class="w-40 abs abs-centered me-4 op-2" alt="">
                                <h2>Facturación</h2>
                                <h4 class="mb-4"> Electrónica / Sunat / Perú</h4>
                                <div class="fs-14">Características principales</div>
                            </div>

                            <div class="relative overflow-hidden">
                                <div class="py-4 z-2">
                                    <ul class="ul-check mb-4">
                                        <li>Emisión de facturas, boletas, notas de crédito, débito y más.</li>
                                        {{-- <li>Integración API para conectar sistemas externos.</li> --}}
                                        <li>Panel comercial con reportes de ventas y estadísticas.</li>
                                        <li>Generación automática de XML y PDF.</li>
                                        <li>Alertas, validaciones y seguridad avanzada.</li>
                                    </ul>
                                </div>

                                <a class="btn-main fx-slide w-100" href="{{ route('web_billing') }}"><span>Más Información</span></a>

                            </div>
                        </div>
                        <!-- ticket item end -->



                        <!-- ticket item begin -->
                        {{-- <div class="item">
                            <div class="d-ticket s2">
                                <img src="images/logo.webp" class="w-80px mb-4" alt="">
                                <img src="images/misc/barcode.webp" class="w-20 p-2 abs abs-middle end-0 me-2"
                                    alt="">
                                <img src="images/logo-big-white.webp" class="w-40 abs abs-centered me-4 op-2"
                                    alt="">
                                <h2>Exclusive Access</h2>
                                <h4 class="mb-4">$2499</h4>
                                <div class="fs-14">October 1 to 5 - 10:00 AM</div>
                            </div>
                            <div class="relative">
                                <div class="py-4 z-2">
                                    <ul class="ul-check mb-4">
                                        <li>All Full Access Pass benefits.</li>
                                        <li>Private one-on-one sessions with speakers.</li>
                                        <li>Priority access to all events and workshops.</li>
                                        <li>Exclusive VIP gala and after-party invitations.</li>
                                    </ul>
                                </div>
                            </div>

                            <a class="btn-main fx-slide w-100" href="tickets.html"><span>Buy Ticket</span></a>
                        </div> --}}
                        <!-- ticket item end -->

                        <!-- ticket item begin -->
                        {{-- <div class="item">
                            <div class="d-ticket s3">
                                <img src="images/logo.webp" class="w-80px mb-4" alt="">
                                <img src="images/misc/barcode.webp" class="w-20 p-2 abs abs-middle end-0 me-2"
                                    alt="">
                                <img src="images/logo-big-white.webp" class="w-40 abs abs-centered me-4 op-2"
                                    alt="">
                                <h2>Student</h2>
                                <h4 class="mb-4">$149</h4>
                                <div class="fs-14">October 1 to 5 - 10:00 AM</div>
                            </div>
                            <div class="relative">
                                <div class="py-4 z-2">
                                    <ul class="ul-check mb-4">
                                        <li>Access to keynotes and workshops.</li>
                                        <li>Student-specific networking events.</li>
                                        <li>Discounted online resources post-event.</li>
                                        <li>Special student meetups for networking.</li>
                                    </ul>
                                </div>
                            </div>

                            <a class="btn-main fx-slide w-100" href="tickets.html"><span>Buy Ticket</span></a>
                        </div> --}}
                        <!-- ticket item end -->

                        <!-- ticket item begin -->
                        {{-- <div class="item">
                            <div class="d-ticket s3">
                                <img src="images/logo.webp" class="w-80px mb-4" alt="">
                                <img src="images/misc/barcode.webp" class="w-20 p-2 abs abs-middle end-0 me-2"
                                    alt="">
                                <img src="images/logo-big-white.webp" class="w-40 abs abs-centered me-4 op-2"
                                    alt="">
                                <h2>Virtual</h2>
                                <h4 class="mb-4">$99</h4>
                                <div class="fs-14">October 1 to 5 - 10:00 AM</div>
                            </div>
                            <div class="relative">
                                <div class="py-4 z-2">
                                    <ul class="ul-check mb-4">
                                        <li>Live-streamed keynotes and workshops.</li>
                                        <li>On-demand access to recorded sessions.</li>
                                        <li>Interactive Q&A with speakers.</li>
                                        <li>Virtual networking and digital swag.</li>
                                    </ul>
                                </div>
                            </div>

                            <a class="btn-main fx-slide w-100" href="tickets.html"><span>Buy Ticket</span></a>
                        </div> --}}
                        <!-- ticket item end -->
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
