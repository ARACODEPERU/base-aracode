{{-- Service Card Component --}}
@props([
    'icon',
    'title',
    'description',
    'href' => null,
    'delay' => 0,
])

<div class="ara-card group reveal reveal-delay-{{ $delay }}">
    <div class="ara-icon-box mb-5">
        {!! $icon !!}
    </div>
    
    <h3 class="text-xl font-bold text-ara-slate-700 mb-3 group-hover:text-ara-blue transition-colors">
        {{ $title }}
    </h3>
    
    <p class="text-ara-slate-400 mb-4 leading-relaxed">
        {{ $description }}
    </p>
    
    @if($href)
        <a href="{{ $href }}" class="inline-flex items-center gap-2 text-ara-blue font-medium group-hover:gap-3 transition-all">
            Conocer más
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
            </svg>
        </a>
    @endif
</div>
