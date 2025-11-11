@extends('layouts.webpage')

@section('content')

    <div id="wrapper">

        <div class="float-text show-on-scroll">
            <span><a href="#">Somos Aracode</a></span>
        </div>
        <div class="scrollbar-v show-on-scroll"></div>

        <!-- page preloader begin -->
        <div id="de-loader"></div>
        <!-- page preloader close -->

        <x-header />

        <section id="section-hero" class="section-dark text-light pt-80 pb-0 jarallax relative overflow-hidden jarallax">
            <img src="{{ asset('themes/webpage/images/background/8.webp') }}" class="jarallax-img" alt="">
            <div class="gradient-edge-bottom h-10"></div>
            <div class="sw-overlay op-5"></div>

            <div class="container">
                <div class="row g-4 gx-5 align-items-center">
                    <div class="col-lg-6">
                        <div class="relative">
                            <img src="{{ asset('themes/webpage/images/product/p1.png') }}" class="w-100 wow fadeInUp"
                                data-wow-delay=".3s" alt="">
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="subtitle s2 mb-3 wow fadeInUp" data-wow-delay=".0s">KAPTA (LMS)</div>
                        <h1 class="wow fadeInUp" data-wow-delay=".2s">
                            Plataforma de Educación Virtual
                        </h1>
                        <p class="col-lg-10 wow fadeInUp" data-wow-delay=".4s">
                            <b>KAPTA</b> es un sistema de aprendizaje online diseñado para instituciones educativas,
                            empresas de
                            capacitación y organizaciones que requieren gestionar formación a gran escala. La plataforma
                            permite crear cursos, administrar estudiantes, evaluar el progreso y automatizar procesos
                            académicos con total eficiencia.
                        </p>
                        <a class="btn-main fx-slide mb10 mb-3 wow fadeInUp" data-wow-delay=".6s"
                            href="#planes"><span>Planes</span></a>
                    </div>
                </div>
            </div>

            <div class="spacer-single"></div>
            <div class="spacer-double"></div>


            {{-- <div class="abs w-100 start-0 bottom-0 z-3">
                <div class="container">
                    <div
                        class="sm-hide border-white-op-3 p-40 py-4 rounded-1 bg-blur relative overflow-hidden wow fadeInUp">
                        <div class="gradient-edge-bottom color start-0 h-50 op-5"></div>
                        <div class="row g-4 justify-content-between align-items-center relative z-2">
                            <div class="col-lg-3">
                                <h2 class="mb-0">Hurry Up!</h2>
                                <h4 class="mb-0">Book Your Seat Now</h4>
                            </div>
                            <div class="col-lg-4">
                                <div id="defaultCountdown" class="pt-2"></div>
                            </div>
                            <div class="col-lg-4">
                                <div class="d-flex">
                                    <i class="fs-60 icofont-google-map id-color"></i>
                                    <div class="ms-3">
                                        <h4 class="mb-0">121 AI Blvd,<br>San Francisco BCA 94107</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> --}}

        </section>

        {{-- <section id="section-about" class="bg-dark section-dark text-light">
            <div class="container">
                <div class="row  gx-5 align-items-center">
                    <div class="col-lg-6">
                          <div class="me-lg-5 pe-lg-5 py-5 my-5">
                              <div class="subtitle wow fadeInUp" data-wow-delay=".2s">About the Event</div>
                              <h2 class="wow fadeInUp" data-wow-delay=".4s">A Global Gathering of AI Innovators</h2>
                              <p class="wow fadeInUp" data-wow-delay=".6s">Join thought leaders, developers, researchers, and founders as we explore how artificial intelligence is reshaping industries, creativity, and the future
                              of work.</p>

                              <ul class="ul-check">
                                  <li class="wow fadeInUp" data-wow-delay=".8s">5 days of keynotes, workshops, and networking</li>
                                  <li class="wow fadeInUp" data-wow-delay=".9s">50 world-class speakers</li>
                                  <li class="wow fadeInUp" data-wow-delay="1s">Startup showcase and live demos</li>
                              </ul>

                          </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="row g-4">
                            <div class="col-6">
                                <div class="relative overflow-hidden rounded-1 wow scale-in-mask mb-4">
                                    <img src="{{ asset('themes/webpage/images/misc/s1.webp') }}" class="img-fluid" alt="">
                                    <div class="gradient-edge-bottom h-50"></div>
                                </div>
                                <div class="col-12 text-center">
                                    <div class="bg-color text-light p-30 rounded-1 wow scale-in-mask">
                                        <div class="de_count">
                                            <h2 class="mb-0"><span class="timer" data-to="50" data-speed="3000"></span></h2>
                                            <div>World-class Speakers</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="spacer-double sm-hide"></div>
                                <div class="col-12 text-center">
                                    <div class="bg-color-2 text-light p-30 rounded-1 wow scale-in-mask">
                                        <div class="de_count">
                                            <h2 class="mb-0"><span class="timer" data-to="5" data-speed="3000"></span></h2>
                                            <div>Days of Event</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="relative overflow-hidden rounded-1 wow scale-in-mask mt-4">
                                    <img src="images/misc/s2.webp" class="img-fluid" alt="">
                                    <div class="gradient-edge-bottom h-50"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section> --}}

        <section id="valor" class="bg-dark section-dark text-light">
            <div class="container-fluid">
                <div class="row g-4">
                    <div class="col-lg-6 offset-lg-3 text-center">
                        <div class="subtitle wow fadeInUp mb-3">Tecnología que impulsa</div>
                        <h2 class="wow fadeInUp" data-wow-delay=".2s">BENEFICIOS - KAPTA LMS</h2>
                        <p class="lead mb-0 wow fadeInUp">
                            Una plataforma E-Learning robusta, moderna y <br> personalizable.
                        </p>
                    </div>
                </div>

                <div class="spacer-single"></div>

                <div class="row g-4">
                    <div class="col-lg-4 col-md-6">
                        <div class="hover">
                            <div
                                class="bg-dark-2 relative rounded-1 overflow-hidden hover-bg-color hover-text-light wow scale-in-mask">
                                <div class="abs p-40 bottom-0 z-2">
                                    <div class="relative wow fadeInUp">
                                        <h4>Todo en un solo lugar</h4>
                                        <p class="mb-0">
                                            Administre toda su operación académica desde una única plataforma: alumnos,
                                            instructores, cursos, matrículas, pagos, evaluaciones y certificados.
                                            Todo integrado, automatizado y listo para escalar.
                                        </p>
                                    </div>
                                </div>
                                <div class="gradient-edge-bottom h-100"></div>
                                <img src="{{ asset('themes/webpage/images/misc/s1.jpg') }}" class="w-100 hover-scale-1-1"
                                    alt="">
                                <div class="abs w-100 h-100 start-0 top-0 hover-op-1 radial-gradient-color"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <div class="hover">
                            <div
                                class="bg-dark-2 relative rounded-1 overflow-hidden hover-bg-color hover-text-light wow scale-in-mask">
                                <div class="abs p-40 bottom-0 z-2">
                                    <div class="relative wow fadeInUp">
                                        <h4>Una imagen profesional para su institución</h4>
                                        <p class="mb-0">
                                            Refuerce su identidad con un entorno 100% personalizado: logo, colores y dominio
                                            propio.
                                            Proyecte confianza, orden y un nivel corporativo superior frente a sus
                                            estudiantes.
                                        </p>
                                    </div>
                                </div>
                                <div class="gradient-edge-bottom h-100"></div>
                                <img src="{{ asset('themes/webpage/images/misc/s4.webp') }}" class="w-100 hover-scale-1-1"
                                    alt="">
                                <div class="abs w-100 h-100 start-0 top-0 hover-op-1 radial-gradient-color"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <div class="hover">
                            <div
                                class="bg-dark-2 relative rounded-1 overflow-hidden hover-bg-color hover-text-light wow scale-in-mask">
                                <div class="abs p-40 bottom-0 z-2">
                                    <div class="relative wow fadeInUp">
                                        <h4>Venda cursos online sin complicaciones</h4>
                                        <p class="mb-0">
                                            Active pasarelas de pago como Culqi, Mercado Pago o Stripe y venda sus programas
                                            de forma automática.
                                            Cobros instantáneos, seguros y sin intervención manual.
                                        </p>
                                    </div>
                                </div>
                                <div class="gradient-edge-bottom h-100"></div>
                                <img src="{{ asset('themes/webpage/images/misc/s3.jpg') }}" class="w-100 hover-scale-1-1"
                                    alt="">
                                <div class="abs w-100 h-100 start-0 top-0 hover-op-1 radial-gradient-color"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <div class="hover">
                            <div
                                class="bg-dark-2 relative rounded-1 overflow-hidden hover-bg-color hover-text-light wow scale-in-mask">
                                <div class="abs p-40 bottom-0 z-2">
                                    <div class="relative wow fadeInUp">
                                        <h4>Seguimiento real y decisiones inteligentes</h4>
                                        <p class="mb-0">
                                            Acceda a paneles de métricas completas: progreso del alumno, resultados de
                                            exámenes, actividad, tasas de finalización y más.
                                            Con datos precisos, su institución puede mejorar continuamente.
                                        </p>
                                    </div>
                                </div>
                                <div class="gradient-edge-bottom h-100"></div>
                                <img src="{{ asset('themes/webpage/images/misc/s6.webp') }}" class="w-100 hover-scale-1-1"
                                    alt="">
                                <div class="abs w-100 h-100 start-0 top-0 hover-op-1 radial-gradient-color"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <div class="hover">
                            <div
                                class="bg-dark-2 relative rounded-1 overflow-hidden hover-bg-color hover-text-light wow scale-in-mask">
                                <div class="abs p-40 bottom-0 z-2">
                                    <div class="relative wow fadeInUp">
                                        <h4>Certificados digitales automáticos</h4>
                                        <p class="mb-0">
                                            Emita certificados profesionales con código de verificación y validación online.
                                            Automatice el proceso y entregue valor inmediato a sus estudiantes.
                                        </p>
                                    </div>
                                </div>
                                <div class="gradient-edge-bottom h-100"></div>
                                <img src="{{ asset('themes/webpage/images/misc/s7.webp') }}" class="w-100 hover-scale-1-1"
                                    alt="">
                                <div class="abs w-100 h-100 start-0 top-0 hover-op-1 radial-gradient-color"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <div class="hover">
                            <div
                                class="bg-dark-2 relative rounded-1 overflow-hidden hover-bg-color hover-text-light wow scale-in-mask">
                                <div class="abs p-40 bottom-0 z-2">
                                    <div class="relative wow fadeInUp">
                                        <h4>Acceso desde cualquier dispositivo</h4>
                                        <p class="mb-0">
                                            Su campus virtual está disponible 24/7 desde computadoras, tablets y
                                            smartphones.
                                            Una experiencia fluida para aprender en cualquier momento y lugar.
                                        </p>
                                    </div>
                                </div>
                                <div class="gradient-edge-bottom h-100"></div>
                                <img src="{{ asset('themes/webpage/images/misc/s6.jpg') }}" class="w-100 hover-scale-1-1"
                                    alt="">
                                <div class="abs w-100 h-100 start-0 top-0 hover-op-1 radial-gradient-color"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <div class="hover">
                            <div
                                class="bg-dark-2 relative rounded-1 overflow-hidden hover-bg-color hover-text-light wow scale-in-mask">
                                <div class="abs p-40 bottom-0 z-2">
                                    <div class="relative wow fadeInUp">
                                        <h4>Optimización con IA: Automatice procesos y ahorre tiempo</h4>
                                        <p class="mb-0">
                                            Su plataforma aprovecha inteligencia artificial para sugerir rutas de
                                            aprendizaje, calificar automáticamente, detectar riesgo de deserción,
                                            personalizar experiencias y reducir carga operativa del equipo académico. Más
                                            eficiencia, mejores resultados, menos trabajo manual.
                                        </p>
                                    </div>
                                </div>
                                <div class="gradient-edge-bottom h-100"></div>
                                <img src="{{ asset('themes/webpage/images/misc/s6.jpg') }}" class="w-100 hover-scale-1-1"
                                    alt="">
                                <div class="abs w-100 h-100 start-0 top-0 hover-op-1 radial-gradient-color"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <div class="hover">
                            <div
                                class="bg-dark-2 relative rounded-1 overflow-hidden hover-bg-color hover-text-light wow scale-in-mask">
                                <div class="abs p-40 bottom-0 z-2">
                                    <div class="relative wow fadeInUp">
                                        <h4>Escalabilidad garantizada: Crezca sin límites ni complicaciones</h4>
                                        <p class="mb-0">
                                            Diseñada para instituciones que buscan expandirse, la plataforma soporta miles
                                            de alumnos, múltiples sedes, nuevos programas, docentes y certificaciones sin
                                            afectar el rendimiento. Añada cursos, usuarios y funcionalidades según lo
                                            necesite.
                                        </p>
                                    </div>
                                </div>
                                <div class="gradient-edge-bottom h-100"></div>
                                <img src="{{ asset('themes/webpage/images/misc/s6.jpg') }}" class="w-100 hover-scale-1-1"
                                    alt="">
                                <div class="abs w-100 h-100 start-0 top-0 hover-op-1 radial-gradient-color"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <div class="hover">
                            <div
                                class="bg-dark-2 relative rounded-1 overflow-hidden hover-bg-color hover-text-light wow scale-in-mask">
                                <div class="abs p-40 bottom-0 z-2">
                                    <div class="relative wow fadeInUp">
                                        <h4>Seguridad, estabilidad y soporte experto</h4>
                                        <p class="mb-0">
                                            Protección avanzada, copias de seguridad constantes y mantenimiento continuo.
                                            Además, tendrá soporte técnico especializado para resolver cualquier necesidad
                                            rápidamente.
                                        </p>
                                    </div>
                                </div>
                                <div class="gradient-edge-bottom h-100"></div>
                                <img src="{{ asset('themes/webpage/images/misc/s6.jpg') }}" class="w-100 hover-scale-1-1"
                                    alt="">
                                <div class="abs w-100 h-100 start-0 top-0 hover-op-1 radial-gradient-color"></div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <section id="section-tickets" class="bg-dark section-dark text-light pt-80 relative" aria-label="section"
            style="background-size: cover; background-repeat: no-repeat;">
            <div class="container relative z-2" style="background-size: cover; background-repeat: no-repeat;">
                <div class="row g-4 justify-content-center" style="background-size: cover; background-repeat: no-repeat;">
                    <div class="col-lg-6 text-center" style="background-size: cover; background-repeat: no-repeat;">
                        <div class="subtitle wow fadeInUp animated" data-wow-delay=".0s"
                            style="background-size: cover; background-repeat: no-repeat; visibility: visible; animation-delay: 0s; animation-name: fadeInUp;">
                            Kapta, Plataforma E-Learning</div>
                        <h2 class="wow fadeInUp animated" data-wow-delay=".2s"
                            style="visibility: visible; animation-delay: 0.2s; animation-name: fadeInUp;">
                            NUESTROS PLANES
                        </h2>
                        <p class="lead wow fadeInUp animated" data-wow-delay=".6s"
                            style="visibility: visible; animation-delay: 0.6s; animation-name: fadeInUp;">
                            Planes por mes o al año, envía tus facturas y boletas electrónicas a SUNAT fácilmente
                        </p>
                    </div>
                </div>
                <div class="row gy-4 gx-5 justify-content-center"
                    style="background-size: cover; background-repeat: no-repeat;">
                    <div class="col-lg-12" style="background-size: cover; background-repeat: no-repeat;">
                        <div class="row g-4" style="background-size: cover; background-repeat: no-repeat;">
                            <!-- ticket item begin -->
                            <div class="col-md-4" style="background-size: cover; background-repeat: no-repeat;">
                                <div class="relative overflow-hidden h-100 border-white-op-3 rounded-1 bg-blur"
                                    style="background-size: cover; background-repeat: no-repeat;">
                                    <div class="gradient-edge-bottom color op-5"
                                        style="background-size: cover; background-repeat: no-repeat;"></div>
                                    <div class="p-40 pb-80 z-2"
                                        style="background-size: cover; background-repeat: no-repeat;">
                                        <div class="text-center"
                                            style="background-size: cover; background-repeat: no-repeat;">
                                            <h2 class="fs-40 mb-0">EMPRENDEDOR</h2>
                                            <h3 class="id-color mb-4">S/ 35.00 | Mensual</h3>
                                            <h4>S/ 350.00 | Anual</h4>
                                        </div>

                                        <div class="border-white-bottom-op-2 mb-4"
                                            style="background-size: cover; background-repeat: no-repeat;"></div>

                                        <ul class="ul-check mb-4">
                                            <li>Modulo de ventas y compras.</li>
                                            <li>Control de inventario.</li>
                                            <li>Cotizaciones, Notas de venta, Guias de remision.</li>
                                            <li>Reportes.</li>
                                            <li>Punto de Venta.</li>
                                            <li>Kardex.</li>
                                            <li>Movimientos de productos.</li>
                                            <li>Facturas y Boletas Electrónicas.</li>
                                            <li>Todos los comprobantes electrónicos SUNAT.</li>
                                            <li>2 usuarios.</li>
                                            <li>Soporte 24/7.</li>
                                        </ul>
                                    </div>

                                    <div class="abs abs-center p-40 pb-30 bottom-0 z-2 w-100 text-center"
                                        style="background-size: cover; background-repeat: no-repeat;">
                                        <a class="btn-main fx-slide w-100" href=""><span>Lo Quiero</span></a>
                                    </div>

                                </div>
                            </div>
                            <!-- ticket item end -->

                            <!-- ticket item begin -->
                            <div class="col-md-4" style="background-size: cover; background-repeat: no-repeat;">
                                <div class="relative overflow-hidden h-100 border-white-op-3 rounded-1 bg-blur"
                                    style="background-size: cover; background-repeat: no-repeat;">
                                    <div class="gradient-edge-bottom color op-5"
                                        style="background-size: cover; background-repeat: no-repeat;"></div>
                                    <div class="p-40 pb-80 z-2"
                                        style="background-size: cover; background-repeat: no-repeat;">
                                        <div class="text-center"
                                            style="background-size: cover; background-repeat: no-repeat;">
                                            <h2 class="fs-40 mb-0">PYME</h2>
                                            <h3 class="id-color mb-4">S/ 50.00 | Mensual</h3>
                                            <h4>S/ 500.00 | Anual</h4>
                                        </div>

                                        <div class="border-white-bottom-op-2 mb-4"
                                            style="background-size: cover; background-repeat: no-repeat;"></div>

                                        <ul class="ul-check mb-4">
                                            <li>Modulo de ventas y compras.</li>
                                            <li>Control de inventario.</li>
                                            <li>Cotizaciones, Notas de venta, Guias de remision.</li>
                                            <li>Reportes.</li>
                                            <li>Punto de Venta.</li>
                                            <li>Kardex.</li>
                                            <li>Movimientos de productos.</li>
                                            <li>Facturas y Boletas Electrónicas.</li>
                                            <li>Todos los comprobantes electrónicos SUNAT.</li>
                                            <li>5 usuarios.</li>
                                            <li>Soporte 24/7.</li>
                                        </ul>
                                    </div>

                                    <div class="abs abs-center p-40 pb-30 bottom-0 z-2 w-100 text-center"
                                        style="background-size: cover; background-repeat: no-repeat;">
                                        <a class="btn-main fx-slide w-100" href=""><span>Lo Quiero</span></a>
                                    </div>

                                </div>
                            </div>
                            <!-- ticket item end -->

                            <!-- ticket item begin -->
                            <div class="col-md-4" style="background-size: cover; background-repeat: no-repeat;">
                                <div class="relative overflow-hidden h-100 border-white-op-3 rounded-1 bg-blur"
                                    style="background-size: cover; background-repeat: no-repeat;">
                                    <div class="gradient-edge-bottom color op-5"
                                        style="background-size: cover; background-repeat: no-repeat;"></div>
                                    <div class="p-40 pb-80 z-2"
                                        style="background-size: cover; background-repeat: no-repeat;">
                                        <div class="text-center"
                                            style="background-size: cover; background-repeat: no-repeat;">
                                            <h2 class="fs-40 mb-0">PRO</h2>
                                            <h3 class="id-color mb-4">S/ 80.00 | Mensual</h3>
                                            <h4>S/ 800.00 | Anual</h4>
                                        </div>

                                        <div class="border-white-bottom-op-2 mb-4"
                                            style="background-size: cover; background-repeat: no-repeat;"></div>

                                        <ul class="ul-check mb-4">
                                            <li>Modulo de ventas y compras.</li>
                                            <li>Control de inventario.</li>
                                            <li>Cotizaciones, Notas de venta, Guias de remision.</li>
                                            <li>Reportes.</li>
                                            <li>Punto de Venta.</li>
                                            <li>Kardex.</li>
                                            <li>Movimientos de productos.</li>
                                            <li>Facturas y Boletas Electrónicas.</li>
                                            <li>Todos los comprobantes electrónicos SUNAT.</li>
                                            <li>10 usuarios.</li>
                                            <li>Soporte 24/7.</li>
                                        </ul>
                                    </div>

                                    <div class="abs abs-center p-40 pb-30 bottom-0 z-2 w-100 text-center"
                                        style="background-size: cover; background-repeat: no-repeat;">
                                        <a class="btn-main fx-slide w-100" href=""><span>Lo Quiero</span></a>
                                    </div>

                                </div>
                            </div>
                            <!-- ticket item end -->
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </div>

    <!-- footer begin -->
    <x-footer />
    <!-- footer end -->

@stop
