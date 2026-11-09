{{-- Copa del campeón: SVG inline. El movimiento vive en el SCSS de la landing
     y se desactiva con «prefers-reduced-motion». --}}
<svg
    class="se-cup"
    viewBox="0 0 240 270"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    role="img"
    aria-label="Copa del campeón"
>
    <defs>
        <linearGradient id="se-cup-metal" x1="78" y1="36" x2="168" y2="212" gradientUnits="userSpaceOnUse">
            <stop offset="0" stop-color="#fff3c4"/>
            <stop offset="0.32" stop-color="#ffd966"/>
            <stop offset="0.62" stop-color="#f0ad24"/>
            <stop offset="1" stop-color="#c47f08"/>
        </linearGradient>
        <linearGradient id="se-cup-metal-soft" x1="96" y1="150" x2="152" y2="226" gradientUnits="userSpaceOnUse">
            <stop offset="0" stop-color="#ffdf7d"/>
            <stop offset="1" stop-color="#d99416"/>
        </linearGradient>
        <linearGradient id="se-cup-rim" x1="60" y1="34" x2="186" y2="56" gradientUnits="userSpaceOnUse">
            <stop offset="0" stop-color="#fff8d9"/>
            <stop offset="0.5" stop-color="#ffd558"/>
            <stop offset="1" stop-color="#d99416"/>
        </linearGradient>
        <radialGradient id="se-cup-halo" cx="0" cy="0" r="1" gradientTransform="translate(120 122) rotate(90) scale(98)" gradientUnits="userSpaceOnUse">
            <stop stop-color="#ffd447" stop-opacity=".34"/>
            <stop offset="1" stop-color="#ffd447" stop-opacity="0"/>
        </radialGradient>
        <linearGradient id="se-cup-shine" x1="0" y1="0" x2="1" y2="0">
            <stop stop-color="#fff" stop-opacity="0"/>
            <stop offset=".5" stop-color="#fff" stop-opacity=".7"/>
            <stop offset="1" stop-color="#fff" stop-opacity="0"/>
        </linearGradient>
        <clipPath id="se-cup-bowl-clip">
            <path d="M66 46h108v32c0 43-24 72-54 72S66 121 66 78V46Z"/>
        </clipPath>
    </defs>

    <ellipse class="se-cup__shadow" cx="120" cy="246" rx="60" ry="10" fill="#040a12" opacity=".55"/>
    <circle class="se-cup__halo" cx="120" cy="122" r="98" fill="url(#se-cup-halo)"/>

    {{-- Asas --}}
    <path d="M66 64C43 60 27 76 29 96c2 21 19 34 42 31" stroke="url(#se-cup-metal)" stroke-width="13" stroke-linecap="round"/>
    <path d="M174 64c23-4 39 12 37 32-2 21-19 34-42 31" stroke="url(#se-cup-metal)" stroke-width="13" stroke-linecap="round"/>

    {{-- Copa --}}
    <path d="M66 46h108v32c0 43-24 72-54 72S66 121 66 78V46Z" fill="url(#se-cup-metal)"/>
    <path d="M80 50h80v28c0 34-18 58-40 58s-40-24-40-58V50Z" fill="#8a5300" opacity=".16"/>
    <g clip-path="url(#se-cup-bowl-clip)">
        <g transform="rotate(18 120 100)">
            <rect class="se-cup__shine" x="-24" y="24" width="34" height="156" fill="url(#se-cup-shine)"/>
        </g>
    </g>
    <rect x="54" y="34" width="132" height="22" rx="11" fill="url(#se-cup-rim)"/>

    {{-- Estrella del campeón --}}
    <path class="se-cup__star" d="M120 64l8.2 17 18.8 2.7-13.6 13.2 3.2 18.7L120 106.9l-16.6 8.7 3.2-18.7-13.6-13.2 18.8-2.7L120 64Z" fill="#fff6d0"/>

    {{-- Cuello y base --}}
    <path d="M108 148h24v20h-24z" fill="url(#se-cup-metal-soft)"/>
    <path d="M94 168h52l10 16H84l10-16Z" fill="url(#se-cup-metal-soft)"/>
    <rect x="72" y="184" width="96" height="20" rx="10" fill="url(#se-cup-metal)"/>
    <rect x="60" y="204" width="120" height="22" rx="11" fill="url(#se-cup-metal-soft)"/>
    <rect x="60" y="204" width="120" height="7" rx="3.5" fill="#fff1b0" opacity=".55"/>

    {{-- Destellos --}}
    <g fill="#ffe89a">
        <path class="se-cup__sparkle se-cup__sparkle--1" d="M46 40l3.2 7.8 7.8 3.2-7.8 3.2L46 62l-3.2-7.8L35 51l7.8-3.2L46 40Z"/>
        <path class="se-cup__sparkle se-cup__sparkle--2" d="M192 92l2.4 5.8 5.8 2.4-5.8 2.4-2.4 5.8-2.4-5.8-5.8-2.4 5.8-2.4 2.4-5.8Z"/>
        <path class="se-cup__sparkle se-cup__sparkle--3" d="M74 188l2 4.9 4.9 2-4.9 2-2 4.9-2-4.9-4.9-2 4.9-2 2-4.9Z"/>
    </g>
</svg>
