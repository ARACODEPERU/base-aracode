<article class="sec-material">
    <span class="sec-material__icon sec-material__icon--{{ $material['kind'] }}" aria-hidden="true">
        @switch($material['kind'])
            @case('image')
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3.5" y="4.5" width="17" height="15" rx="3"/>
                    <circle cx="9" cy="10" r="1.6"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="m5 17 4.5-4.5 3.5 3.5 2.5-2.5L20 17"/>
                </svg>
                @break

            @case('pdf')
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 3.5h7l5 5v12H6z"/>
                    <path stroke-linecap="round" d="M13 3.5v5h5M9 14h6M9 17h4"/>
                </svg>
                @break

            @default
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 3.5h7l5 5v12H6z"/>
                    <path stroke-linecap="round" d="M13 3.5v5h5"/>
                </svg>
        @endswitch
    </span>

    <div class="sec-material__body">
        <strong class="sec-material__title">{{ $material['title'] }}</strong>
        <span class="sec-material__meta">
            {{ $material['origin'] }}@if ($material['size']) · {{ $material['size'] }}@endif
        </span>
        @include('colegio.secundaria.partials.material-links', ['material' => $material])
    </div>
</article>
