{{-- Icono pequeño para los datos de contacto. Recibe $icon: pin | phone | mail | code | clock --}}
@if ($icon === 'pin')
    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-6.5-4.6-6.5-10a6.5 6.5 0 1113 0c0 5.4-6.5 10-6.5 10z"/>
        <circle cx="12" cy="11" r="2.4"/>
    </svg>
@elseif ($icon === 'phone')
    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6.6 3.5h2.2l1.5 3.8-1.8 1.4a12.5 12.5 0 006.8 6.8l1.4-1.8 3.8 1.5v2.2a2.1 2.1 0 01-2.3 2.1A17.5 17.5 0 014.5 5.8 2.1 2.1 0 016.6 3.5z"/>
    </svg>
@elseif ($icon === 'mail')
    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
        <rect x="3" y="5" width="18" height="14" rx="2.5"/>
        <path stroke-linecap="round" stroke-linejoin="round" d="M3.5 7l8.5 6 8.5-6"/>
    </svg>
@elseif ($icon === 'code')
    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M8.5 7.5L4 12l4.5 4.5M15.5 7.5L20 12l-4.5 4.5"/>
    </svg>
@else
    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
        <circle cx="12" cy="12" r="8.5"/>
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5V12l3 1.8"/>
    </svg>
@endif
