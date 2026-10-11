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
                                    <li><a class="menu-item" href="{{ route('empresa') }}">Nosotros</a></li>
                                    <li><a class="menu-item" href="{{ route('empresa') }}#valor">Propuesta de Valor</a></li>
                                    <li><a class="menu-item" href="{{ route('empresa') }}#visionarios">Visionarios</a></li>
                                    <li>
                                        <a class="menu-item" href="{{ route('soluciones') }}">Productos</a>
                                        <ul>
                                            <li><a class="menu-item" href="{{ route('solucion_kapta') }}">Sitios Webs</a></li>
                                            <li><a class="menu-item" href="{{ route('soluciones') }}">Tiendas Online</a></li>
                                            <li><a class="menu-item" href="{{ route('solucion_kapta') }}">Plataforma E-Learning</a></li>
                                            <li><a class="menu-item" href="{{ route('solucion_facturacion') }}">Facturación Electrónica</a></li>
                                        </ul>
                                    </li>
                                    <li><a class="menu-item" href="{{ route('contacto') }}">Contacto</a></li>
                                </ul>
                            </div>
                        </div>

                        <div class="de-flex-col">
                            <a class="btn-main fx-slide w-100" href="{{ route('contacto') }}"><span>Contactanos</span></a>

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
