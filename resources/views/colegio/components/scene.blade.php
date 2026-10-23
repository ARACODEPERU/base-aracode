{{-- Escenario de fondo del aula virtual de Inicial: cielo, nubes, colinas,
     árboles cálidos, arbustos, setas y mariposas. Todo es SVG/CSS, sin imágenes
     externas, para que la vista se pueda publicar sin assets adicionales. --}}
<div class="cole-scene" aria-hidden="true">
    <div class="cole-sun"></div>
    <div class="cole-cloud cole-cloud--a"></div>
    <div class="cole-cloud cole-cloud--b"></div>
    <div class="cole-cloud cole-cloud--c"></div>

    <svg class="cole-hills" viewBox="0 0 1440 900" preserveAspectRatio="xMidYMax slice" fill="none" xmlns="http://www.w3.org/2000/svg">
        {{-- Colinas lejanas --}}
        <path d="M0 520c170-74 300-52 470 4s300 40 470 0 330-40 500 6v370H0z" fill="#CDEBD8"/>
        <path d="M0 606c190-84 330-40 520 10s320 30 480-14 300-20 440 26v272H0z" fill="#B4E3C5"/>

        {{-- Árbol lejano izquierdo --}}
        <g opacity=".9">
            <path d="M64 720c-2-56-1-104 8-148" stroke="#B98A63" stroke-width="20" stroke-linecap="round"/>
            <circle cx="76" cy="520" r="62" fill="#F5C978"/>
            <circle cx="30" cy="566" r="42" fill="#F2A97C"/>
            <circle cx="122" cy="566" r="40" fill="#EFA0BD"/>
        </g>

        {{-- Árbol principal izquierdo --}}
        <g>
            <path d="M180 760c-4-72-2-134 10-192" stroke="#A9744F" stroke-width="30" stroke-linecap="round"/>
            <circle cx="196" cy="500" r="96" fill="#F4B860"/>
            <circle cx="126" cy="570" r="66" fill="#F49E6B"/>
            <circle cx="268" cy="566" r="62" fill="#E98BB0"/>
            <circle cx="206" cy="446" r="50" fill="#FFD37E" opacity=".6"/>
            <circle cx="148" cy="498" r="26" fill="#FFE2A6" opacity=".5"/>
        </g>

        {{-- Árbol mediano izquierdo --}}
        <g opacity=".95">
            <path d="M352 748c-2-46 0-84 8-118" stroke="#B98A63" stroke-width="20" stroke-linecap="round"/>
            <circle cx="362" cy="596" r="56" fill="#EFA6C2"/>
            <circle cx="320" cy="644" r="40" fill="#F6C87E"/>
            <circle cx="408" cy="642" r="38" fill="#F3A78A"/>
        </g>

        {{-- Árbol principal derecho --}}
        <g>
            <path d="M1268 766c4-74 2-136-10-194" stroke="#A9744F" stroke-width="30" stroke-linecap="round"/>
            <circle cx="1252" cy="502" r="96" fill="#F6C87E"/>
            <circle cx="1324" cy="572" r="66" fill="#EFA0BD"/>
            <circle cx="1178" cy="568" r="60" fill="#F49E6B"/>
            <circle cx="1244" cy="448" r="50" fill="#FFE0A0" opacity=".65"/>
            <circle cx="1310" cy="502" r="26" fill="#FFF0C9" opacity=".5"/>
        </g>

        {{-- Árbol lejano derecho --}}
        <g opacity=".9">
            <path d="M1372 720c2-52 0-96-8-138" stroke="#B98A63" stroke-width="18" stroke-linecap="round"/>
            <circle cx="1362" cy="524" r="58" fill="#F2A97C"/>
            <circle cx="1408" cy="566" r="40" fill="#F5C978"/>
        </g>

        {{-- Colina de césped --}}
        <path d="M0 690c210-76 380-44 540 6s340 34 500-12 280-24 400 20v196H0z" fill="#8FD6A8"/>
        <path d="M0 804c200-46 380-24 560 10s360 14 520-14 260-6 360 10v90H0z" fill="#79CB98"/>

        {{-- Arbustos --}}
        <g fill="#5FBF83">
            <circle cx="96" cy="852" r="52"/>
            <circle cx="44" cy="882" r="42"/>
            <circle cx="150" cy="884" r="40"/>
        </g>
        <g fill="#6FCD92">
            <circle cx="1344" cy="846" r="56"/>
            <circle cx="1400" cy="880" r="42"/>
            <circle cx="1288" cy="884" r="38"/>
        </g>
        <g fill="#69C88C" opacity=".9">
            <circle cx="470" cy="884" r="46"/>
            <circle cx="530" cy="900" r="38"/>
            <circle cx="986" cy="880" r="44"/>
            <circle cx="1040" cy="900" r="36"/>
        </g>

        {{-- Setas --}}
        <g>
            <rect x="236" y="836" width="26" height="42" rx="12" fill="#FFF1DC"/>
            <path d="M212 842c0-26 18-44 40-44s40 18 40 44z" fill="#F17C8B"/>
            <circle cx="236" cy="822" r="5" fill="#FFE2E6"/>
            <circle cx="262" cy="832" r="4" fill="#FFE2E6"/>
            <circle cx="248" cy="808" r="3.4" fill="#FFE2E6"/>
        </g>
        <g>
            <rect x="1178" y="812" width="22" height="36" rx="10" fill="#FFF1DC"/>
            <path d="M1158 818c0-22 15-37 33-37s33 15 33 37z" fill="#F2959F"/>
            <circle cx="1178" cy="800" r="4.4" fill="#FFE2E6"/>
            <circle cx="1200" cy="808" r="3.4" fill="#FFE2E6"/>
        </g>

        {{-- Manchas de césped y florecillas --}}
        <g stroke="#4FB57A" stroke-width="5" stroke-linecap="round">
            <path d="M400 872v-22M410 874v-16M390 874v-14"/>
            <path d="M860 886v-20M870 888v-14M850 888v-12"/>
        </g>
        <g fill="#FFF3C4">
            <circle cx="620" cy="852" r="6"/>
            <circle cx="700" cy="884" r="5"/>
            <circle cx="1120" cy="866" r="6"/>
            <circle cx="1080" cy="892" r="4.5"/>
            <circle cx="356" cy="898" r="5"/>
        </g>
    </svg>

    {{-- Mariposas --}}
    @foreach (['a', 'b', 'c', 'd'] as $variant)
        <div class="cole-butterfly cole-butterfly--{{ $variant }}" data-butterfly>
            <svg viewBox="0 0 40 34" class="h-full w-full" xmlns="http://www.w3.org/2000/svg">
                <g class="cole-wing cole-wing--left" fill="#FFB3C7">
                    <path d="M20 17C16 7 6 4 4 11s4 14 16 6z"/>
                </g>
                <g class="cole-wing cole-wing--right" fill="#FFC9D8">
                    <path d="M20 17c4-10 14-13 16-6s-4 14-16 6z"/>
                </g>
                <ellipse cx="20" cy="17" rx="2.2" ry="6.6" fill="#5B4A63"/>
                <path d="M19 11c-1-3-3-4-4-5M21 11c1-3 3-4 4-5" stroke="#5B4A63" stroke-width="1.4" stroke-linecap="round"/>
            </svg>
        </div>
    @endforeach

    {{-- Destellos --}}
    <div class="cole-sparkle cole-sparkle--a">
        <svg viewBox="0 0 24 24" class="h-6 w-6" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 1.8l1.9 6.6 6.3 1.8-6.3 1.8L12 18.6l-1.9-6.6L3.8 10.2l6.3-1.8L12 1.8z"/>
        </svg>
    </div>
    <div class="cole-sparkle cole-sparkle--b">
        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 2l1.7 5.9L19.5 9.6l-5.8 1.7L12 17.2l-1.7-5.9L4.5 9.6l5.8-1.7L12 2z"/>
        </svg>
    </div>
</div>
