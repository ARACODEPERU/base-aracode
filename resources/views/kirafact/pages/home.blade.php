{{--
    Portada del sitio KIRAFACT.

    Una sola página con las secciones acordadas, en este orden:
    hero, presentación del producto, funcionalidades, beneficios, recorrido del
    software, solicitud de demo, preguntas frecuentes y llamada a la acción
    final. La cabecera y el pie los pone el layout.

    Cada sección es un parcial independiente: se puede reordenar, quitar o
    editar sin tocar las demás.
--}}
@extends('kirafact.layouts.app')

@section('content')
    @include('kirafact.components.hero')
    @include('kirafact.components.producto')
    @include('kirafact.components.funcionalidades')
    @include('kirafact.components.beneficios')
    @include('kirafact.components.software')
    @include('kirafact.components.demo')
    @include('kirafact.components.preguntas')
    @include('kirafact.components.cta-final')
@endsection
