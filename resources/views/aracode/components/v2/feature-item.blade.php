{{-- Feature Item Component --}}
@props([
    'icon',
    'title',
    'description',
    'delay' => 0,
])

<div class="flex gap-4 reveal reveal-delay-{{ $delay }}">
    <div class="ara-icon-box flex-shrink-0">
        {!! $icon !!}
    </div>
    <div>
        {{-- h3: cuelga de un h2 de sección. Con h4 se saltaba un nivel y eso
             rompe el orden de encabezados que usan los lectores de pantalla. --}}
        <h3 class="text-lg font-bold text-white mb-2">{{ $title }}</h3>
        <p class="text-white/70 leading-relaxed">{{ $description }}</p>
    </div>
</div>
