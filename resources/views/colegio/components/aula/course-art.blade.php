{{--
    Ilustración de la tarjeta de curso. El controlador decide el tipo según el
    área curricular (numbers, book, leaf, ball, pencil, globe, heart o star).
--}}
@php($art = $art ?? 'star')

<svg viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    @switch($art)
        @case('numbers')
            <g>
                <rect x="12" y="34" width="34" height="48" rx="12" fill="#FFF4DC" transform="rotate(-8 29 58)"/>
                <rect x="43" y="26" width="34" height="56" rx="12" fill="#FFFFFF"/>
                <rect x="74" y="36" width="34" height="46" rx="12" fill="#FFE1B8" transform="rotate(8 91 59)"/>
                <text x="29" y="68" text-anchor="middle" font-family="Fredoka, Nunito, sans-serif" font-size="30" font-weight="700" fill="#E8731F" transform="rotate(-8 29 58)">1</text>
                <text x="60" y="68" text-anchor="middle" font-family="Fredoka, Nunito, sans-serif" font-size="34" font-weight="700" fill="#2B86BD">2</text>
                <text x="91" y="68" text-anchor="middle" font-family="Fredoka, Nunito, sans-serif" font-size="30" font-weight="700" fill="#4E9E38" transform="rotate(8 91 59)">3</text>
                <circle cx="46" cy="86" r="2.6" fill="#4A3226"/>
                <circle cx="74" cy="86" r="2.6" fill="#4A3226"/>
                <path d="M56 90c2.6 3 7.4 3 10 0" stroke="#4A3226" stroke-width="2.4" stroke-linecap="round"/>
            </g>
            @break

        @case('book')
            <g transform="rotate(-6 60 60)">
                <path d="M18 40c12-8 26-8 38 0v46c-12-8-26-8-38 0z" fill="#E86A5C"/>
                <path d="M64 40c12-8 26-8 38 0v46c-12-8-26-8-38 0z" fill="#D9584B"/>
                <path d="M56 40c3-2 6-2 8 0v46c-2-2-5-2-8 0z" fill="#FFF4E6"/>
                <path d="M26 56h22M26 66h18" stroke="#FFD9C6" stroke-width="3" stroke-linecap="round"/>
                <circle cx="48" cy="70" r="2.6" fill="#FFF6EA"/>
                <circle cx="66" cy="70" r="2.6" fill="#FFF6EA"/>
                <path d="M53 75c3 3 8 3 11 0" stroke="#FFF6EA" stroke-width="2.4" stroke-linecap="round"/>
                <ellipse cx="40" cy="78" rx="4" ry="2.6" fill="#FFC0B4" opacity=".8"/>
                <ellipse cx="76" cy="78" rx="4" ry="2.6" fill="#FFC0B4" opacity=".8"/>
            </g>
            @break

        @case('leaf')
            <g>
                <path d="M60 22c26 10 34 34 22 52-10 15-30 20-46 12-4-22 4-50 24-64z" fill="#79D267"/>
                <path d="M60 26c-8 22-10 40-6 56" stroke="#4E9E38" stroke-width="3.4" stroke-linecap="round"/>
                <path d="M58 46c8-2 14 0 18 4M55 60c8-2 13 0 17 4" stroke="#4E9E38" stroke-width="2.6" stroke-linecap="round"/>
                <circle cx="52" cy="52" r="3" fill="#22431F"/>
                <circle cx="70" cy="52" r="3" fill="#22431F"/>
                <path d="M58 60c3 3.4 8 3.4 11 0" stroke="#22431F" stroke-width="2.4" stroke-linecap="round"/>
                <ellipse cx="43" cy="60" rx="4.4" ry="2.8" fill="#FFC0B4" opacity=".75"/>
                <ellipse cx="79" cy="60" rx="4.4" ry="2.8" fill="#FFC0B4" opacity=".75"/>
                <path d="M92 78c6 2 8 8 4 12-5 0-8-4-4-12z" fill="#8FD6FF"/>
            </g>
            @break

        @case('ball')
            <g>
                <circle cx="58" cy="60" r="36" fill="#FFF6EA"/>
                <path d="M58 24a36 36 0 0 0 0 72c-14-16-14-56 0-72z" fill="#FFB259"/>
                <path d="M58 24a36 36 0 0 1 0 72c14-16 14-56 0-72z" fill="#8FD6FF"/>
                <circle cx="58" cy="60" r="36" stroke="#E39A3F" stroke-width="3"/>
                <circle cx="46" cy="58" r="3" fill="#4A3226"/>
                <circle cx="70" cy="58" r="3" fill="#4A3226"/>
                <path d="M52 66c3 3.4 9 3.4 12 0" stroke="#4A3226" stroke-width="2.4" stroke-linecap="round"/>
                <ellipse cx="38" cy="66" rx="4.4" ry="2.8" fill="#F79AA2" opacity=".7"/>
                <ellipse cx="78" cy="66" rx="4.4" ry="2.8" fill="#F79AA2" opacity=".7"/>
                <ellipse cx="24" cy="96" rx="10" ry="5" fill="#4E9E38" opacity=".35"/>
            </g>
            @break

        @case('pencil')
            <g transform="rotate(-12 60 60)">
                <rect x="48" y="16" width="24" height="70" rx="6" fill="#FFD15C"/>
                <path d="M48 86h24l-12 18z" fill="#F2C9A0"/>
                <path d="M55 98h10l-5 6z" fill="#4A3226"/>
                <rect x="48" y="16" width="24" height="12" rx="6" fill="#F79AA2"/>
                <circle cx="56" cy="56" r="2.8" fill="#4A3226"/>
                <circle cx="66" cy="56" r="2.8" fill="#4A3226"/>
                <path d="M57 63c2.4 2.6 6 2.6 8.4 0" stroke="#4A3226" stroke-width="2.2" stroke-linecap="round"/>
                <path d="M88 40l3 7 7 3-7 3-3 7-3-7-7-3 7-3z" fill="#FFF4DC"/>
                <path d="M26 52l2.4 5.4 5.4 2.4-5.4 2.4L26 68l-2.4-5.8-5.4-2.4 5.4-2.4z" fill="#FFF4DC"/>
            </g>
            @break

        @case('globe')
            <g>
                <circle cx="58" cy="60" r="36" fill="#8FD6FF"/>
                <path d="M34 44c8-6 16-2 18 6 2 10-8 12-8 20 0 8-10 8-12 0-4-10-4-20 2-26z" fill="#8AD97A"/>
                <path d="M72 34c8 4 12 12 12 20-6 4-14 2-16-4-2-6-2-12 4-16z" fill="#8AD97A"/>
                <path d="M66 78c6-2 12 2 12 8-4 4-12 4-16 0-2-4 0-6 4-8z" fill="#8AD97A"/>
                <circle cx="48" cy="58" r="3" fill="#22431F"/>
                <circle cx="70" cy="58" r="3" fill="#22431F"/>
                <path d="M53 66c3 3.4 9 3.4 12 0" stroke="#22431F" stroke-width="2.4" stroke-linecap="round"/>
                <ellipse cx="40" cy="66" rx="4.4" ry="2.8" fill="#FFC0B4" opacity=".8"/>
                <ellipse cx="78" cy="66" rx="4.4" ry="2.8" fill="#FFC0B4" opacity=".8"/>
            </g>
            @break

        @case('heart')
            <g>
                <path d="M60 94C34 78 22 66 22 50c0-12 10-20 21-20 8 0 14 4 17 10 3-6 9-10 17-10 11 0 21 8 21 20 0 16-12 28-38 44z" fill="#FF8FB1"/>
                <circle cx="48" cy="54" r="3" fill="#5A2436"/>
                <circle cx="72" cy="54" r="3" fill="#5A2436"/>
                <path d="M53 62c3 3.4 9 3.4 12 0" stroke="#5A2436" stroke-width="2.4" stroke-linecap="round"/>
                <ellipse cx="38" cy="62" rx="4.4" ry="2.8" fill="#FFD3DF"/>
                <ellipse cx="82" cy="62" rx="4.4" ry="2.8" fill="#FFD3DF"/>
            </g>
            @break

        @default
            <g>
                <path d="M60 20l12 24 27 4-19 19 4 27-24-13-24 13 4-27L21 48l27-4z" fill="#FFD15C"/>
                <circle cx="50" cy="58" r="3" fill="#5A4210"/>
                <circle cx="70" cy="58" r="3" fill="#5A4210"/>
                <path d="M54 66c3 3.4 9 3.4 12 0" stroke="#5A4210" stroke-width="2.4" stroke-linecap="round"/>
                <ellipse cx="41" cy="66" rx="4.4" ry="2.8" fill="#F79AA2" opacity=".7"/>
                <ellipse cx="79" cy="66" rx="4.4" ry="2.8" fill="#F79AA2" opacity=".7"/>
            </g>
    @endswitch
</svg>
