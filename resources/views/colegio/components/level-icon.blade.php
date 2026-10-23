{{-- Icono del nivel educativo. Recibe $icon: sun | book | star --}}
@if ($icon === 'sun')
    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
        <circle cx="12" cy="12" r="4"/>
        <path stroke-linecap="round" d="M12 2.5v2M12 19.5v2M2.5 12h2M19.5 12h2M5.2 5.2l1.4 1.4M17.4 17.4l1.4 1.4M18.8 5.2l-1.4 1.4M6.6 17.4l-1.4 1.4"/>
    </svg>
@elseif ($icon === 'book')
    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.5C10.4 5.3 8.4 4.8 5 4.8v13.4c3.4 0 5.4.5 7 1.7 1.6-1.2 3.6-1.7 7-1.7V4.8c-3.4 0-5.4.5-7 1.7z"/>
        <path stroke-linecap="round" d="M12 6.5V19.9"/>
    </svg>
@else
    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3.2l2.7 5.6 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1-4.4-4.3 6.1-.9L12 3.2z"/>
    </svg>
@endif
