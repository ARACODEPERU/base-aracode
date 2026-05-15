@extends('layouts.webpage')

@section('content')

    <div id="wrapper">

        <x-show-scroll />

        <!-- page preloader begin -->
        <div id="de-loader"></div>
        <!-- page preloader close -->

        <x-header />

        <x-store-online.store-online-welcome />

        <x-store-online.store-online-benefits />
        
        <x-cms.cms-questions />

        {{-- <x-lms-plans-essentials />

        <x-lms-plans-professionals />
        
        <x-lms-plans-advanced /> --}}
        
        <x-bulletin />

    </div>

    <!-- footer begin -->
    <x-footer />
    <!-- footer end -->

@stop
