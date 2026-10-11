{{-- Section Heading Component --}}
@props([
    'badge' => null,
    'title',
    'subtitle' => null,
    'align' => 'center',
    'light' => true,
])

<div @class([
    'mb-12 lg:mb-16',
    'text-center' => $align === 'center',
    'text-left' => $align === 'left',
])>
    @if($badge)
        <span class="ara-badge ara-badge-blue mb-4 inline-block reveal">{{ $badge }}</span>
    @endif
    
    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold mb-4 reveal reveal-delay-1
        {{ $light ? 'text-white' : 'text-ara-slate-700' }}">
        {{ $title }}
    </h2>
    
    @if($subtitle)
        <p class="text-lg max-w-2xl mx-auto reveal reveal-delay-2
            {{ $align === 'center' ? 'mx-auto' : '' }}
            {{ $light ? 'text-white/70' : 'text-ara-slate-400' }}">
            {{ $subtitle }}
        </p>
    @endif
</div>
