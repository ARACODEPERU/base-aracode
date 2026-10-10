{{-- Product Card Component --}}
@props([
    'title',
    'description',
    'href' => '#',
    'image',
    'badge' => null,
    'features' => [],
    'delay' => 0,
])

<div class="ara-product-card reveal reveal-delay-{{ $delay }}">
    <img src="{{ $image }}" alt="{{ $title }}" class="ara-product-bg">
    
    <div class="ara-product-content">
        @if($badge)
            <span class="ara-badge ara-badge-green mb-3 inline-block">{{ $badge }}</span>
        @endif
        
        <h3 class="text-2xl lg:text-3xl font-bold text-white mb-2">{{ $title }}</h3>
        
        <p class="text-white/70 mb-4">{{ $description }}</p>
        
        @if(count($features) > 0)
            <ul class="space-y-2 mb-6">
                @foreach($features as $feature)
                    <li class="flex items-center gap-2 text-white/80 text-sm">
                        <svg class="w-4 h-4 text-ara-green flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        {{ $feature }}
                    </li>
                @endforeach
            </ul>
        @endif
        
        <a href="{{ $href }}" class="ara-btn ara-btn-primary">
            Conocer Más
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
            </svg>
        </a>
    </div>
</div>
