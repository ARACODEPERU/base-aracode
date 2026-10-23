{{--
    Fondo del aula virtual de Inicial: cielo con nubes, sol, pajaritos y un
    paisaje de granja (colinas, césped, valla, camino de tierra y granero).
    Es decorativo: no recibe clics ni se anuncia a lectores de pantalla.
--}}
<div class="aula-scene" aria-hidden="true">
    <div class="aula-sun"></div>

    <div class="aula-cloud aula-cloud--a"></div>
    <div class="aula-cloud aula-cloud--b"></div>
    <div class="aula-cloud aula-cloud--c"></div>

    <svg class="aula-bird aula-bird--a" width="46" height="18" viewBox="0 0 46 18" fill="none" aria-hidden="true">
        <path d="M2 12c6-8 12-8 18 0M24 12c6-8 12-8 18 0" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/>
    </svg>
    <svg class="aula-bird aula-bird--b" width="34" height="14" viewBox="0 0 46 18" fill="none" aria-hidden="true">
        <path d="M2 12c6-8 12-8 18 0M24 12c6-8 12-8 18 0" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/>
    </svg>

    <svg class="aula-ground" viewBox="0 0 1440 300" preserveAspectRatio="xMidYMax slice" fill="none" xmlns="http://www.w3.org/2000/svg">
        {{-- Colinas lejanas --}}
        <path d="M0 148C170 96 330 158 520 132 700 106 860 168 1040 134 1210 102 1330 152 1440 120V300H0Z" fill="#CBEFBB"/>
        <path d="M0 196C200 152 380 212 560 184 760 152 940 214 1120 182 1280 154 1360 190 1440 172V300H0Z" fill="#ADE49C"/>

        {{-- Árboles --}}
        <g>
            <rect x="148" y="182" width="14" height="48" rx="6" fill="#8C6239"/>
            <circle cx="155" cy="176" r="30" fill="#7CCB6F"/>
            <circle cx="130" cy="188" r="22" fill="#6FBF63"/>
            <circle cx="180" cy="188" r="22" fill="#6FBF63"/>
        </g>

        {{-- Granero con elementos de granja --}}
        <g>
            <path d="M1180 196 1252 152l72 44z" fill="#C9695A"/>
            <rect x="1190" y="196" width="124" height="64" rx="6" fill="#F0A08C"/>
            <rect x="1210" y="214" width="34" height="46" rx="4" fill="#FFF3DA"/>
            <path d="M1210 214l34 46M1244 214l-34 46" stroke="#E4B79B" stroke-width="2.4"/>
            <rect x="1254" y="212" width="18" height="18" rx="3" fill="#FFF3DA"/>
            <path d="M1188 252h128" stroke="#FFF3DA" stroke-width="4"/>
        </g>

        {{-- Valla de madera --}}
        <g stroke="#FFF0D2" stroke-width="7" stroke-linecap="round">
            <path d="M352 236v34M392 232v38M432 230v40M472 232v38M512 236v34"/>
            <path d="M344 246h178M344 258h178"/>
        </g>

        {{-- Césped frontal --}}
        <path d="M0 250C230 218 470 270 720 244 970 218 1210 272 1440 236V300H0Z" fill="#8FD881"/>

        {{-- Camino de tierra --}}
        <path d="M598 300c64-34 106-60 156-88l22-16 44-10c-42 34-74 58-96 84-16 18-26 26-34 30z" fill="#F4DDAF"/>
        <path d="M634 300c58-30 96-52 140-76" stroke="#E7C88E" stroke-width="4" stroke-linecap="round" stroke-dasharray="10 14"/>

        {{-- Arbustos --}}
        <g fill="#6FC46A">
            <circle cx="286" cy="286" r="26"/>
            <circle cx="316" cy="292" r="20"/>
            <circle cx="1110" cy="290" r="24"/>
            <circle cx="1082" cy="296" r="18"/>
        </g>

        {{-- Florecillas --}}
        <g>
            <circle cx="380" cy="288" r="4" fill="#FFF8D8"/>
            <circle cx="392" cy="284" r="3.4" fill="#FFD3DF"/>
            <circle cx="860" cy="282" r="4" fill="#FFF8D8"/>
            <circle cx="884" cy="290" r="3.4" fill="#FFD3DF"/>
            <circle cx="170" cy="286" r="3.6" fill="#FFF8D8"/>
        </g>
    </svg>
</div>
