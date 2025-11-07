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
                        <div class="subtitle s2 mb-3 wow fadeInUp" data-wow-delay=".0s">Simplifica tu Negocio</div>
                        <h1 class="wow fadeInUp" data-wow-delay=".2s">
                            Facturación Electrónica Segura y Eficiente
                        </h1>
                        <p class="col-lg-10 wow fadeInUp" data-wow-delay=".4s">
                            Implementa nuestra solución de facturación electrónica y lleva el control de tus ventas de forma
                            rápida, segura y cumpliendo con la normativa vigente. Optimiza tu gestión y ahorra tiempo en
                            cada transacción.
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

        <section id="section-faq" class="bg-dark section-dark text-light">
            <div class="container">
                
            <div class="row g-4">
                <div class="col-lg-6 offset-lg-3 text-center">
                    <div class="subtitle wow fadeInUp mb-3">Información de valor</div>
                        <h2 class="wow fadeInUp" data-wow-delay=".2s">
                            PREGUNTAS FRECUENTES
                        </h2>
                    <p class="lead mb-0 wow fadeInUp">
                        Te ayudamos a obtener la información que necesitas <br> de forma clara y sencilla.
                    </p>
                </div>
            </div>
                <div class="row g-4 mt-4">
                    <div class="col-lg-5">
                        <div class="hover">
                            <div class="bg-dark-2 relative rounded-1 overflow-hidden hover-bg-color hover-text-light wow scale-in-mask"
                                data-wow-delay=".3s">

                                <div class="gradient-edge-bottom h-100"></div>
                                <img src="{{ asset('themes/webpage/images/questions.jpg') }}" class="w-100 hover-scale-1-1"
                                    alt="">
                                <div class="abs w-100 h-100 start-0 top-0 hover-op-1 radial-gradient-color"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-7">
                        <br>
                        <div class="accordion s2 wow fadeInUp">
                            <div class="accordion-section">
                                <div class="accordion-section-title" data-tab="#accordion-a1">
                                    ¿Qué es una factura electrónica?
                                </div>
                                <div class="accordion-section-content" id="accordion-a1">
                                    Una factura electrónica es un comprobante de pago en formato digital que sirve para
                                    sustentar la compraventa de bienes o servicios. Si bien este tipo facturación tiene la
                                    misma validez legal que la facturación tradicional (en papel), termina siendo más
                                    atractiva para pequeñas, medianas y grandes empresas debido a los múltiples beneficios
                                    que ofrece.
                                </div>

                                <div class="accordion-section-title" data-tab="#accordion-a2">
                                    ¿Para qué sirve la facturación electrónica?
                                </div>
                                <div class="accordion-section-content" id="accordion-a2">
                                    La facturación electrónica sirve para sustentar y validar las transacciones comerciales
                                    que se generan entre empresas y clientes. Además, gracias a la digitalización de
                                    comprobantes, con este tipo de facturación puedes simplificar procesos y reducir costos
                                    asociados a la emisión y almacenamiento de facturas en papel.
                                </div>

                                <div class="accordion-section-title" data-tab="#accordion-a3">
                                    ¿Qué beneficios tengo al emitir facturas electrónicas con Aracode?
                                </div>
                                <div class="accordion-section-content" id="accordion-a3">
                                    Entre los beneficios de emitir facturas electrónicas con Aracode tenemos:
                                    <ul>
                                        <li>Ahorro tiempo tus facturas de forma automática</li>
                                        <li>Mayor tranquilidad al cumplir fácil con la SUNAT</li>
                                        <li>Eficiencia en procesos al reducir errores humanos</li>
                                        <li>
                                            Ahorro de dinero al reducir costos de impresión y almacenamiento
                                            Y lo mejor es que también puedes emitir boletas y guías de remisión electrónicas
                                            de forma ilimitada y desde cualquier lugar.
                                        </li>
                                    </ul>
                                    Conoce más en nuestra guía sobre cómo llevar tu
                                    Facturación Electrónica en Alegra. [Paso a paso] te explicamos cómo hacerlo. (Y en solo
                                    4 simples pasos)
                                </div>

                                <div class="accordion-section-title" data-tab="#accordion-a4">
                                    ¿Cuáles son los pasos para habilitarme como facturador electrónico?
                                </div>
                                <div class="accordion-section-content" id="accordion-a4">
                                    Habilitarse como facturador electrónico a la SUNAT puede sonar como una tarea
                                    complicada, sin embargo, es más fácil de lo que crees y en nuestra
                                </div>

                                <div class="accordion-section-title" data-tab="#accordion-a5">
                                    ¿Cómo emitir factura electrónica con Aracode?
                                </div>
                                <div class="accordion-section-content" id="accordion-a5">
                                    Para emitir tus facturas electrónicas con Aracode solo basta con elegir la fecha, el
                                    nombre de tu cliente, el producto vendido y más campos de llenado rápido que son 100%
                                    necesarios para correcta validación ante la SUNAT.

                                    No importa el lugar en donde estés ni la cantidad de comprobantes que tengas que emitir,
                                    aprende cómo en nuestra guía de facturación electrónica para pymes con Aracode.
                                </div>

                                <div class="accordion-section-title" data-tab="#accordion-a6">
                                    ¿Cuál es la diferencia entre boleta y factura electrónica?
                                </div>
                                <div class="accordion-section-content" id="accordion-a6">
                                    La principal diferencia entre una boleta y factura electrónica radica en su utilidad,
                                    mientras que la primera no tiene derecho para sustentar tributariamente costos y gastos,
                                    con la segunda sí puedes acreditarlos para efectos tributarios relacionados al Impuesto
                                    a la Renta. Conoce 3 diferencias más en nuestro blog: ¿Boleta y Factura? ¿Cuál debo
                                    emitir y por qué?
                                </div>

                                <div class="accordion-section-title" data-tab="#accordion-a6">
                                    ¿Cómo puedo ver una factura electrónica recibida?
                                </div>
                                <div class="accordion-section-content" id="accordion-a6">
                                    Consulta fácilmente una factura electrónica recibida y/o aprobada ante la SUNAT desde el
                                    menú “Ventas” de tu cuenta de Alegra. Ahí también podrás consultar, en tiempo real, cuál
                                    es el estado de cada comprobante emitido. Si deseas saber más, consulta nuestra guía
                                    para aprender a cómo ver el estado de emisión de tus documentos electrónicos.
                                </div>

                                <div class="accordion-section-title" data-tab="#accordion-a6">
                                    ¿Cómo eliminar una factura electrónica?
                                </div>
                                <div class="accordion-section-content" id="accordion-a6">
                                    Puedes eliminar fácilmente una factura electrónica desde el menú “Ventas” de tu cuenta
                                    de Alegra. Posterior a ello, tu solicitud llegará a la SUNAT para su pronta aprobación.
                                    Conoce cómo hacerlo y más detalles de la funcionalidad en nuestro artículo sobre la
                                    eliminación de comprobantes de pago electrónicos.
                                </div>

                                <div class="accordion-section-title" data-tab="#accordion-a6">
                                    ¿Es obligatorio emitir factura electrónica en Perú?
                                </div>
                                <div class="accordion-section-content" id="accordion-a6">
                                    Sí, según la Resolución de Superintendencia N° 128-2021/Sunat, la emisión de factura
                                    electrónica ya es obligatoria en todo el Perú. Esta clasificación se divide
                                    principalmente en el monto de facturación anual, clasificando a pequeñas, medianas y
                                    grandes empresas.
                                </div>

                                <div class="accordion-section-title" data-tab="#accordion-a6">
                                    ¿Cuál es el plazo para rechazar una factura electrónica?
                                </div>
                                <div class="accordion-section-content" id="accordion-a6">
                                    Según la SUNAT, una factura electrónica puede ser rechazada hasta el noveno día hábil
                                    del mes siguiente de su emisión. Esto puede ocurrir por 2 principales motivos: quien
                                    recibe no es el adquiriente o se consignó una descripción que no corresponde a la
                                    operación.
                                </div>
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
                            Facturación Electrónica</div>
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
