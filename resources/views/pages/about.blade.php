@extends('layouts.webpage')

@section('content')

    <div id="wrapper">

        <x-show-scroll />

        <!-- page preloader begin -->
        <div id="de-loader"></div>
        <!-- page preloader close -->

        <x-header />

        <x-about-welcome />

        <x-about-vision-mission-values />

        <x-value-proposition />

        <x-about-visionaries />
        
        <x-bulletin />

    </div>

    <!-- footer begin -->
    <x-footer />
    <!-- footer end -->

@stop
