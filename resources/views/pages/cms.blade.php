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

        <x-cms-welcome />

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

        <x-cms-benefits />
        
        <x-cms-questions />

        {{-- <x-lms-plans-essentials />

        <x-lms-plans-professionals />
        
        <x-lms-plans-advanced /> --}}
        
        <x-bulletin />

    </div>

    <!-- footer begin -->
    <x-footer />
    <!-- footer end -->

@stop
