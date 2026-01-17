<div>
    <section class="bg-dark section-dark text-light pt-80 relative jarallax" aria-label="section">
        <img src="{{ asset('themes/webpage/images/background/2.webp') }}" class="jarallax-img" alt="">
        <div class="gradient-edge-top"></div>
        <div class="gradient-edge-bottom"></div>
        <div class="sw-overlay op-8"></div>
            <div class="container relative z-2">
                <div class="row g-4 justify-content-center">
                    <div class="col-lg-8 text-center">
                        <div class="subtitle wow fadeInUp" data-wow-delay=".0s">Nuestros clientes</div>
                        <h2 class="wow fadeInUp" data-wow-delay=".2s">Empresas que confían en nosotros</h2>
                        <p class="wow fadeInUp" data-wow-delay=".4s">
                            Instituciones y empresas que han elegido nuestras soluciones digitales para optimizar sus procesos <br>
                            y crecer de forma sostenible.
                        </p>
                    </div>
                </div>
                <div class="row row-cols-2 row-cols-md-3 row-cols-lg-5 g-4">
                    {{-- @for ($i = 1; $i <= 15; $i++)
                        <div class="col wow fadeInUp" data-wow-delay="{{ $i * 0.1 }}s">
                            <div class="h-100 d-flex align-items-center justify-content-center p-4 rounded-1" style="background: rgba(255,255,255,0.05);">
                                @php $imgIndex = (($i - 1) % 10) + 1; @endphp
                                <a href="">
                                    <img src="{{ asset('themes/webpage/images/logo-light/' . $imgIndex . '.webp') }}" class="img-fluid" style="max-height: 60px; opacity: 0.7;" alt="Cliente {{ $i }}">
                                </a>
                            </div>
                        </div>
                    @endfor --}}
                        <div class="col wow fadeInUp" data-wow-delay="0.1s">
                            <div class="h-100 d-flex align-items-center justify-content-center p-4 rounded-1" style="background: rgba(255,255,255,0.05);">
                                
                                <a href="">
                                    <img src="{{ asset('themes/webpage/images/customers/kentha.png') }}" class="img-fluid" style="max-height: 60px; opacity: 0.7;" alt="Cliente">
                                </a>
                            </div>
                        </div>
                </div>
            </div>
    </section>
</div>