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

        <x-lms-welcome />

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

        <x-lms-benefits />

        <x-lms-plans-essentials />

        <x-lms-plans-professionals />
        
        <x-lms-plans-advanced />

        {{-- <section id="section-tickets" class="bg-dark section-dark text-light pt-80 relative" aria-label="section"
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
        </section> --}}

    </div>

    <!-- footer begin -->
    <x-footer />
    <!-- footer end -->

@stop
