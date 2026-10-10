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
        <h4 class="text-lg font-bold text-white mb-2">{{ $title }}</h4>
        <p class="text-white/70 leading-relaxed">{{ $description }}</p>
    </div>
</div>
