{{-- Button Component --}}
@props([
    'href' => null,
    'variant' => 'primary',
    'size' => 'md',
    'icon' => false,
    'iconRight' => false,
])

@php
    $classes = 'ara-btn';
    
    $classes .= match($variant) {
        'primary' => ' ara-btn-primary',
        'secondary' => ' ara-btn-secondary',
        'ghost' => ' ara-btn-ghost',
        'green' => ' ara-btn-green',
        default => ' ara-btn-primary',
    };
    
    $classes .= match($size) {
        'sm' => ' ara-btn-sm',
        'lg' => ' ara-btn-lg',
        default => '',
    };
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)
            {{ $icon }}
        @endif
        {{ $slot }}
        @if($iconRight)
            {{ $iconRight }}
        @endif
    </a>
@else
    <button {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)
            {{ $icon }}
        @endif
        {{ $slot }}
        @if($iconRight)
            {{ $iconRight }}
        @endif
    </button>
@endif
