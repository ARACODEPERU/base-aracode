{{-- Stats Counter Component --}}
@props([
    'number',
    'suffix' => '',
    'label',
    'delay' => 0,
])

<div class="ara-stat reveal reveal-delay-{{ $delay }}">
    <div class="ara-stat-number" data-counter data-target="{{ $number }}" data-suffix="{{ $suffix }}">
        0{{ $suffix }}
    </div>
    <div class="ara-stat-label">{{ $label }}</div>
</div>
