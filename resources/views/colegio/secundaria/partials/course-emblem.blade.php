{{--
    Emblema de la tarjeta según el área curricular.
    Recibe $theme: math, language, science, history, art, english, sport o discover.
--}}
@php($emblem = $theme ?? 'discover')

<span class="sec-emblem sec-emblem--{{ $emblem }}" aria-hidden="true">
    @switch($emblem)
        @case('math')
            <svg viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                <rect x="8" y="7" width="24" height="26" rx="5"/>
                <path d="M14 15h5M14 21h5M14 27h5M24 15h2M24 21h2M24 27h2"/>
            </svg>
            @break

        @case('language')
            <svg viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10 9h20a3 3 0 0 1 3 3v12a3 3 0 0 1-3 3H18l-6 5v-5h-2a3 3 0 0 1-3-3V12a3 3 0 0 1 3-3Z"/>
                <path d="M14 15h12M14 20h8"/>
            </svg>
            @break

        @case('science')
            <svg viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="20" cy="20" r="4"/>
                <ellipse cx="20" cy="20" rx="14" ry="6.5"/>
                <ellipse cx="20" cy="20" rx="14" ry="6.5" transform="rotate(60 20 20)"/>
                <ellipse cx="20" cy="20" rx="14" ry="6.5" transform="rotate(120 20 20)"/>
            </svg>
            @break

        @case('history')
            <svg viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="20" cy="20" r="13"/>
                <path d="M7 20h26M20 7c4 4 6 8 6 13s-2 9-6 13c-4-4-6-8-6-13s2-9 6-13Z"/>
            </svg>
            @break

        @case('art')
            <svg viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 7c7 0 13 5 13 12 0 4-3 6-6 6h-2c-2 0-3 1-3 3 0 3-2 5-5 5C11 33 7 27 7 20 7 12 13 7 20 7Z"/>
                <circle cx="15" cy="15" r="1.7" fill="currentColor"/>
                <circle cx="23" cy="13" r="1.7" fill="currentColor"/>
                <circle cx="13" cy="23" r="1.7" fill="currentColor"/>
            </svg>
            @break

        @case('english')
            <svg viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                <path d="M8 10h24a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H19l-7 6v-6H8a2 2 0 0 1-2-2V12a2 2 0 0 1 2-2Z"/>
                <path d="M15 17h10M15 22h6"/>
            </svg>
            @break

        @case('sport')
            <svg viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="20" cy="20" r="13"/>
                <path d="M20 7v26M7 20h26M11 12c5 4 13 4 18 0M11 28c5-4 13-4 18 0"/>
            </svg>
            @break

        @default
            <svg viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                <path d="m20 7 4 9 9 1-7 6 2 10-8-5-8 5 2-10-7-6 9-1z"/>
            </svg>
    @endswitch
</span>
