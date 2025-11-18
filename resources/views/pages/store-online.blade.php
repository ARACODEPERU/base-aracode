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

        <x-store-online-welcome />

        <x-store-online-benefits />
        
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
