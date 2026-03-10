@extends('layouts.webpage')

@section('content')

    <div id="wrapper">

        
        <x-show-scroll />

        <!-- page preloader begin -->
        <div id="de-loader"></div>
        <!-- page preloader close -->

        <x-header />

        <x-contact-welcome />

        <x-contact />

    </div>

    <!-- footer begin -->
    <x-footer />
    <!-- footer end -->

@stop
