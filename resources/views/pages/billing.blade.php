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

        <x-billing-welcome />

        <x-billing-questions />

        <x-billing-plans />

    </div>

    <!-- footer begin -->
    <x-footer />
    <!-- footer end -->

@stop
