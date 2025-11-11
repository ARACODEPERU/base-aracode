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

        <section id="section-hero" class="section-dark no-top no-bottom text-light jarallax relative mh-500 jarallax">
            <img src="{{ asset('themes/webpage/images/background/4.webp') }}" class="jarallax-img" alt="">
            <div class="gradient-edge-bottom h-50"></div>
            <div class="sw-overlay op-5"></div>
            <div class="abs w-80 bottom-10 z-2 w-100">
                <div class="container">
                    <div class="row align-items-center justify-content-between gx-5">
                        <div class="col-lg-6">
                            <div class="relative wow mask-right">
                                <div class="text-start">
                                    <h1 class="fs-96 text-uppercase fs-sm-10vw mb-0 lh-1">Sobre Nosotros</h1>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4 wow fadeInRight" data-wow-delay=".3s">
                            <p class="mb-0">
                                Somos una empresa peruana especializada en el desarrollo de software a medida y productos
                                digitales innovadores. Nuestro enfoque combina tecnología moderna + inteligencia artificial
                                para crear soluciones eficientes, escalables y alineadas a las necesidades reales de
                                empresas, instituciones educativas y organizaciones en crecimiento.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section-dark relative">
            <div class="container">
                <div class="row align-items-center justify-content-center">
                    <div class="col-lg-6 wow fadeInLeft" data-wow-delay=".4s">
                        <h2>Visión</h2>
                        <p>
                            Ser la empresa líder en soluciones digitales inteligentes en Perú y Latinoamérica, reconocida
                            por transformar procesos empresariales mediante tecnología moderna, desarrollo de software de
                            alta calidad e integración avanzada de inteligencia artificial
                        </p>
                    </div>

                    <div class="col-lg-6 wow fadeInRight" data-wow-delay=".4s">
                        <h2>Misión</h2>
                        <p>
                            Desarrollar productos y sistemas innovadores que potencien la gestión, productividad y
                            crecimiento de empresas e instituciones, integrando herramientas inteligentes, automatización y
                            software escalable adaptado a cada cliente.
                        </p>
                    </div>
                </div>
                <br>
                <div class="row align-items-center justify-content-center">
                    <div class="col-md-12 wow fadeInUp" data-wow-delay=".5s">
                        <h2>Valores Corporativos</h2>
                        <div class="row">
                            <div class="col-md-4 wow fadeInUp" data-wow-delay=".5s">
                                <h3>Innovación constante</h3>
                                <p>
                                    Buscamos mejorar continuamente nuestras soluciones aplicando tecnologías emergentes y
                                    modelos de IA.
                                </p>
                            </div>
                            <div class="col-md-4 wow fadeInUp" data-wow-delay=".6s">
                                <h3>Calidad y excelencia</h3>
                                <p>
                                    Cada desarrollo es diseñado pensando en estabilidad, seguridad, escalabilidad y buena
                                    experiencia de usuario.
                                </p>
                            </div>
                            <div class="col-md-4 wow fadeInUp" data-wow-delay=".7s">
                                <h3>Compromiso con el cliente</h3>
                                <p>
                                    Trabajamos como aliados, no solo proveedores. Guiamos, acompañamos y buscamos
                                    resultados.
                                </p>
                            </div>
                            <div class="col-md-4 wow fadeInUp" data-wow-delay=".8s">
                                <h3>Transparencia y honestidad</h3>
                                <p>
                                    Comunicación clara, presupuestos reales y procesos colaborativos.
                                </p>
                            </div>
                            <div class="col-md-4 wow fadeInUp" data-wow-delay=".9s">
                                <h3>Eficiencia y productividad</h3>
                                <p>
                                    Creamos soluciones que realmente simplifican procesos y generan impacto.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <x-value-proposition />

        <x-visionaries />

    </div>

    <!-- footer begin -->
    <x-footer />
    <!-- footer end -->

@stop
