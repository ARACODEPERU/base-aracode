<div>
    <header class="transparent">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="de-flex">
                        <div class="de-flex-col">
                            <!-- logo begin -->
                            <div id="logo">
                                <a href="{{ route('index_main') }}">
                                    <img class="logo-main" src="{{ asset('themes/webpage/images/logo.png') }}" alt="">
                                    <img class="logo-scroll" src="{{ asset('themes/webpage/images/logo.png') }}" alt="">
                                    <img class="logo-mobile" src="{{ asset('themes/webpage/images/logo.png') }}" alt="">
                                </a>
                            </div>
                            <!-- logo close -->
                        </div>

                        <div class="de-flex-col">
                            <div class="de-flex-col header-col-mid">
                                <ul id="mainmenu">
                                    <li><a class="menu-item active" href="{{ route('index_main') }}">Home</a></li>
                                    {{-- <li><a class="menu-item active" href="{{ route('index_main') }}">Home</a>
                                        <ul class="mega">
                                            <li>
                                                <div class="container">
                                                    <div class="sb-menu p-4">
                                                        <div class="row g-3">
                                                            <div class="col-lg-2 col-md-4 col-sm-6 text-center">
                                                                <div
                                                                    class="relative hover text-center overflow-hidden soft-shadow rounded-1">
                                                                    <a class="p-0" href="index.html">
                                                                        <img src="images/demo/homepage-1.webp"
                                                                            class="w-100 relative hover-scale-1-1"
                                                                            alt="">
                                                                    </a>
                                                                </div>
                                                                <h6 class="mt-3">Demo 1</h6>
                                                            </div>

                                                            <div class="col-lg-2 col-md-4 col-sm-6 text-center">
                                                                <div
                                                                    class="relative hover text-center overflow-hidden soft-shadow rounded-1">
                                                                    <a class="p-0" href="index-slider.html">
                                                                        <img src="images/demo/homepage-2.webp"
                                                                            class="w-100 relative hover-scale-1-1"
                                                                            alt="">
                                                                    </a>
                                                                </div>
                                                                <h6 class="mt-3">Demo 2</h6>
                                                            </div>

                                                            <div class="col-lg-2 col-md-4 col-sm-6 text-center">
                                                                <div
                                                                    class="relative hover text-center overflow-hidden soft-shadow rounded-1">
                                                                    <a class="p-0"
                                                                        href="index-static-background.html">
                                                                        <img src="images/demo/homepage-3.webp"
                                                                            class="w-100 relative hover-scale-1-1"
                                                                            alt="">
                                                                    </a>
                                                                </div>
                                                                <h6 class="mt-3">Demo 3</h6>
                                                            </div>

                                                            <div class="col-lg-2 col-md-4 col-sm-6 text-center">
                                                                <div
                                                                    class="relative hover text-center overflow-hidden soft-shadow rounded-1">
                                                                    <a class="p-0" href="index-slider-text.html">
                                                                        <img src="images/demo/homepage-4.webp"
                                                                            class="w-100 relative hover-scale-1-1"
                                                                            alt="">
                                                                    </a>
                                                                </div>
                                                                <h6 class="mt-3">Demo 4</h6>
                                                            </div>

                                                            <div class="col-lg-2 col-md-4 col-sm-6 text-center">
                                                                <div
                                                                    class="relative hover text-center overflow-hidden soft-shadow rounded-1">
                                                                    <a class="p-0" href="index-countdown.html">
                                                                        <img src="images/demo/homepage-5.webp"
                                                                            class="w-100 relative hover-scale-1-1"
                                                                            alt="">
                                                                    </a>
                                                                </div>
                                                                <h6 class="mt-3">Demo 5</h6>
                                                            </div>

                                                            <div class="col-lg-2 col-md-4 col-sm-6 text-center">
                                                                <div
                                                                    class="relative hover text-center overflow-hidden soft-shadow rounded-1">
                                                                    <a class="p-0" href="index-6.html">
                                                                        <img src="images/demo/homepage-6.webp"
                                                                            class="w-100 relative hover-scale-1-1"
                                                                            alt="">
                                                                    </a>
                                                                </div>
                                                                <h6 class="mt-3">Demo 6</h6>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>
                                        </ul>
                                    </li> --}}
                                    <li><a class="menu-item" href="{{ route('web_about') }}">Nosotros</a></li>
                                    <li><a class="menu-item" href="{{ route('web_about') }}#valor">Propuesta de Valor</a></li>
                                    <li><a class="menu-item" href="{{ route('web_about') }}#visionarios">Visionarios</a></li>
                                    {{-- <li><a class="menu-item" href="#section-schedule">Schedule</a></li> --}}
                                    <li>
                                        <a class="menu-item" href="{{ route('index_main') }}#productos">Productos</a>
                                        <ul>
                                            <li><a class="menu-item" href="{{ route('web_lms') }}">Plataforma E-Learning</a></li>
                                            <li><a class="menu-item" href="{{ route('web_billing') }}">Facturación Electrónica</a></li>
                                        </ul>
                                    </li>
                                    <li><a class="menu-item" href="{{ route('index_main') }}#ubicación">Ubicación</a></li>
                                    {{-- <li><a class="menu-item" href="#section-faq">FAQ</a></li> --}}
                                    {{-- <li>
                                        <a class="menu-item" href="">Pages</a>
                                        <ul>
                                            <li><a class="menu-item" href="tickets.html">Tickets Style 1</a></li>
                                            <li><a class="menu-item" href="tickets-2.html">Tickets Style 2</a></li>
                                            <li><a class="menu-item" href="news.html">News</a></li>
                                            <li><a class="menu-item" href="news-single.html">News Single</a></li>
                                            <li><a class="menu-item" href="contact.html">Contact</a>
                                        </ul>
                                    </li> --}}
                                </ul>
                            </div>
                        </div>

                        <div class="de-flex-col">
                            <a class="btn-main fx-slide w-100" href="{{ route('web_contact') }}"><span>Contactanos</span></a>

                            <div class="menu_side_area">
                                <span id="menu-btn"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
</div>
