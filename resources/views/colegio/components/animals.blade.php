{{-- Personajes del aula virtual de Inicial, dibujados en SVG para que no
     dependan de imágenes externas. Cada personaje tiene una capa interna
     (.cole-float) que anime.js usa para el flotado continuo, mientras el
     contenedor (.cole-animal) recibe el parallax del cursor. --}}
<div class="cole-animals" aria-hidden="true">
    {{-- Ciervo, a la izquierda --}}
    <div class="cole-animal cole-animal--deer" data-depth="0.26">
        <div class="cole-float" data-float>
            <svg viewBox="0 0 160 170" class="h-auto w-full" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M38 170c-4-46 12-63 42-63s46 17 42 63z" fill="#C98A5C"/>
                <ellipse cx="54" cy="152" rx="15" ry="12" fill="#B87A4E"/>
                <ellipse cx="106" cy="152" rx="15" ry="12" fill="#B87A4E"/>

                <ellipse cx="40" cy="60" rx="14" ry="23" transform="rotate(-30 40 60)" fill="#D69A6B"/>
                <ellipse cx="40" cy="60" rx="7" ry="14" transform="rotate(-30 40 60)" fill="#F7D3B4"/>
                <ellipse cx="120" cy="60" rx="14" ry="23" transform="rotate(30 120 60)" fill="#D69A6B"/>
                <ellipse cx="120" cy="60" rx="7" ry="14" transform="rotate(30 120 60)" fill="#F7D3B4"/>

                <g stroke="#A9744F" stroke-width="7" stroke-linecap="round">
                    <path d="M52 44C48 28 42 20 36 14"/>
                    <path d="M46 30c-8-2-14 0-18 4"/>
                    <path d="M108 44c4-16 10-24 16-30"/>
                    <path d="M114 30c8-2 14 0 18 4"/>
                </g>

                <ellipse cx="80" cy="84" rx="46" ry="40" fill="#D69A6B"/>
                <path d="M80 45c6-12 14-14 18-7-6 1-12 3-18 7z" fill="#C98A5C"/>

                <ellipse cx="80" cy="102" rx="27" ry="19" fill="#F7E0C8"/>
                <ellipse cx="80" cy="95" rx="9" ry="7" fill="#5F4030"/>
                <path d="M80 102v5M80 107c-3 5-9 5-11 1M80 107c3 5 9 5 11 1" stroke="#5F4030" stroke-width="3" stroke-linecap="round"/>

                <ellipse cx="61" cy="82" rx="6.5" ry="8" fill="#3E2F2A"/>
                <circle cx="63" cy="79" r="2.2" fill="#FFFFFF"/>
                <ellipse cx="99" cy="82" rx="6.5" ry="8" fill="#3E2F2A"/>
                <circle cx="101" cy="79" r="2.2" fill="#FFFFFF"/>

                <ellipse cx="48" cy="99" rx="9" ry="6" fill="#F79AA2" opacity=".55"/>
                <ellipse cx="112" cy="99" rx="9" ry="6" fill="#F79AA2" opacity=".55"/>
            </svg>
        </div>
    </div>

    {{-- Osa amarilla, en el centro --}}
    <div class="cole-animal cole-animal--bear" data-depth="0.1">
        <div class="cole-float" data-float>
            <svg viewBox="0 0 200 210" class="h-auto w-full" fill="none" xmlns="http://www.w3.org/2000/svg">
                <ellipse cx="44" cy="170" rx="24" ry="34" transform="rotate(18 44 170)" fill="#E9B03B"/>
                <ellipse cx="156" cy="170" rx="24" ry="34" transform="rotate(-18 156 170)" fill="#E9B03B"/>

                <path d="M30 210c0-54 30-84 70-84s70 30 70 84z" fill="#F2C14E"/>
                <ellipse cx="100" cy="198" rx="46" ry="42" fill="#FFE29A"/>

                <circle cx="54" cy="56" r="23" fill="#E9B03B"/>
                <circle cx="54" cy="56" r="12" fill="#F7D98B"/>
                <circle cx="146" cy="56" r="23" fill="#E9B03B"/>
                <circle cx="146" cy="56" r="12" fill="#F7D98B"/>

                <ellipse cx="100" cy="98" rx="68" ry="61" fill="#F2C14E"/>
                <path d="M98 38c4-12 12-16 18-8-6 2-12 5-18 8z" fill="#E9B03B"/>

                <ellipse cx="100" cy="124" rx="37" ry="27" fill="#FFE9B8"/>
                <ellipse cx="100" cy="114" rx="12" ry="9" fill="#6B4A33"/>
                <path d="M100 123v5M100 128c-4 6-12 6-14 1M100 128c4 6 12 6 14 1" stroke="#6B4A33" stroke-width="3.2" stroke-linecap="round"/>

                <ellipse cx="74" cy="94" rx="7.5" ry="9" fill="#3E2F2A"/>
                <circle cx="76.5" cy="90.5" r="2.6" fill="#FFFFFF"/>
                <ellipse cx="126" cy="94" rx="7.5" ry="9" fill="#3E2F2A"/>
                <circle cx="128.5" cy="90.5" r="2.6" fill="#FFFFFF"/>

                <ellipse cx="58" cy="116" rx="10" ry="6.5" fill="#F79AA2" opacity=".6"/>
                <ellipse cx="142" cy="116" rx="10" ry="6.5" fill="#F79AA2" opacity=".6"/>
            </svg>
        </div>
    </div>

    {{-- Ranita, encima de la osa --}}
    <div class="cole-animal cole-animal--frog" data-depth="0.16">
        <div class="cole-float" data-float>
            <svg viewBox="0 0 70 62" class="h-auto w-full" fill="none" xmlns="http://www.w3.org/2000/svg">
                <ellipse cx="14" cy="58" rx="10" ry="5" fill="#6BBE79"/>
                <ellipse cx="56" cy="58" rx="10" ry="5" fill="#6BBE79"/>

                <ellipse cx="35" cy="46" rx="25" ry="16" fill="#7CD08A"/>

                <circle cx="24" cy="22" r="12" fill="#7CD08A"/>
                <circle cx="24" cy="22" r="8.5" fill="#FFFFFF"/>
                <circle cx="25" cy="23" r="4.6" fill="#33503A"/>
                <circle cx="27" cy="21" r="1.6" fill="#FFFFFF"/>

                <circle cx="46" cy="22" r="12" fill="#7CD08A"/>
                <circle cx="46" cy="22" r="8.5" fill="#FFFFFF"/>
                <circle cx="47" cy="23" r="4.6" fill="#33503A"/>
                <circle cx="49" cy="21" r="1.6" fill="#FFFFFF"/>

                <path d="M27 45c4 5 12 5 16 0" stroke="#2E6B3E" stroke-width="2.6" stroke-linecap="round"/>

                <ellipse cx="13" cy="38" rx="6" ry="4" fill="#F79AA2" opacity=".6"/>
                <ellipse cx="57" cy="38" rx="6" ry="4" fill="#F79AA2" opacity=".6"/>
            </svg>
        </div>
    </div>

    {{-- Zorro saltando, arriba a la derecha --}}
    <div class="cole-animal cole-animal--fox" data-depth="0.34">
        <div class="cole-float" data-float>
            <svg viewBox="0 0 150 140" class="h-auto w-full" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M110 104c22 22 48 8 40-20-14 14-28 14-40 8z" fill="#F08A3C"/>
                <path d="M146 86c4 6 3 13-3 18 2-6 2-12 3-18z" fill="#FFF4E6"/>

                <path d="M38 68C28 44 26 26 34 18c10 4 20 18 24 34z" fill="#F49B4A"/>
                <path d="M43 61c-6-15-7-26-3-32 6 4 12 14 15 24z" fill="#6A4227"/>
                <path d="M112 68c10-24 12-42 4-50-10 4-20 18-24 34z" fill="#F49B4A"/>
                <path d="M107 61c6-15 7-26 3-32-6 4-12 14-15 24z" fill="#6A4227"/>

                <ellipse cx="75" cy="118" rx="34" ry="24" fill="#F08A3C"/>
                <ellipse cx="75" cy="82" rx="47" ry="41" fill="#F49B4A"/>

                <path d="M28 80c6-18 24-22 47-6 23-16 41-12 47 6-6 22-24 34-47 34S34 102 28 80z" fill="#FFF6EA"/>

                <ellipse cx="75" cy="92" rx="7" ry="5.5" fill="#4A3226"/>
                <path d="M75 97v4M75 101c-4 4-9 4-11 0M75 101c4 4 9 4 11 0" stroke="#4A3226" stroke-width="2.8" stroke-linecap="round"/>

                <path d="M52 78c4-5 10-5 14 0M84 78c4-5 10-5 14 0" stroke="#4A3226" stroke-width="3.6" stroke-linecap="round"/>

                <ellipse cx="42" cy="94" rx="8" ry="5" fill="#F79AA2" opacity=".6"/>
                <ellipse cx="108" cy="94" rx="8" ry="5" fill="#F79AA2" opacity=".6"/>

                <ellipse cx="28" cy="116" rx="12" ry="9" transform="rotate(-20 28 116)" fill="#F49B4A"/>
                <ellipse cx="122" cy="116" rx="12" ry="9" transform="rotate(20 122 116)" fill="#F49B4A"/>
            </svg>
        </div>
    </div>

    {{-- Coneja rosada, flotando a la derecha --}}
    <div class="cole-animal cole-animal--rabbit" data-depth="0.28">
        <div class="cole-float" data-float>
            <svg viewBox="0 0 130 165" class="h-auto w-full" fill="none" xmlns="http://www.w3.org/2000/svg">
                <ellipse cx="46" cy="34" rx="12" ry="31" transform="rotate(-12 46 34)" fill="#F9C6D3"/>
                <ellipse cx="46" cy="36" rx="6" ry="21" transform="rotate(-12 46 36)" fill="#FFE3EA"/>
                <ellipse cx="86" cy="30" rx="12" ry="31" transform="rotate(14 86 30)" fill="#F9C6D3"/>
                <ellipse cx="86" cy="32" rx="6" ry="21" transform="rotate(14 86 32)" fill="#FFE3EA"/>

                <ellipse cx="65" cy="126" rx="36" ry="34" fill="#F7B9C8"/>
                <ellipse cx="65" cy="134" rx="22" ry="22" fill="#FFD9E2"/>

                <ellipse cx="26" cy="106" rx="11" ry="16" transform="rotate(-28 26 106)" fill="#F7B9C8"/>
                <ellipse cx="104" cy="102" rx="11" ry="16" transform="rotate(28 104 102)" fill="#F7B9C8"/>

                <ellipse cx="65" cy="76" rx="40" ry="36" fill="#F9C6D3"/>
                <ellipse cx="65" cy="90" rx="21" ry="15" fill="#FFF1F4"/>

                <path d="M65 84c-4 0-6 2-6 4 0 3 2 5 6 5s6-2 6-5c0-2-2-4-6-4z" fill="#E77A93"/>
                <path d="M65 93v4M65 97c-3 4-7 4-9 0M65 97c3 4 7 4 9 0" stroke="#C9707F" stroke-width="2.6" stroke-linecap="round"/>

                <ellipse cx="50" cy="74" rx="5" ry="6.5" fill="#3E2F2A"/>
                <circle cx="51.5" cy="71.5" r="1.8" fill="#FFFFFF"/>
                <ellipse cx="80" cy="74" rx="5" ry="6.5" fill="#3E2F2A"/>
                <circle cx="81.5" cy="71.5" r="1.8" fill="#FFFFFF"/>

                <ellipse cx="36" cy="86" rx="8" ry="5" fill="#F79AA2" opacity=".6"/>
                <ellipse cx="94" cy="86" rx="8" ry="5" fill="#F79AA2" opacity=".6"/>

                <ellipse cx="44" cy="158" rx="13" ry="7" fill="#F7B9C8"/>
                <ellipse cx="86" cy="158" rx="13" ry="7" fill="#F7B9C8"/>
            </svg>
        </div>
    </div>
</div>
