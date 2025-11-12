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

        <x-about-welcome />

        <x-about-vision-mission-values />

        <x-value-proposition />

        <x-visionaries />

    </div>

    <!-- footer begin -->
    <x-footer />
    <!-- footer end -->

@stop
