<script setup>
defineProps({
    primary: { type: String, default: '#06b6d4' },
    accent: { type: String, default: '#8b5cf6' },
});
</script>

<template>
    <svg
        class="bib-reader-blocked-art"
        viewBox="0 0 400 280"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        role="img"
        aria-hidden="true"
    >
        <defs>
            <linearGradient id="bibBookLeft" x1="80" y1="60" x2="200" y2="220" gradientUnits="userSpaceOnUse">
                <stop stop-color="#0891b2" />
                <stop offset="1" stop-color="#6366f1" />
            </linearGradient>
            <linearGradient id="bibBookRight" x1="200" y1="60" x2="320" y2="220" gradientUnits="userSpaceOnUse">
                <stop stop-color="#22d3ee" />
                <stop offset="1" stop-color="#8b5cf6" />
            </linearGradient>
            <linearGradient id="bibLockGrad" x1="160" y1="90" x2="240" y2="170" gradientUnits="userSpaceOnUse">
                <stop stop-color="#a78bfa" />
                <stop offset="1" stop-color="#7c3aed" />
            </linearGradient>
            <filter id="bibGlow" x="-20%" y="-20%" width="140%" height="140%">
                <feGaussianBlur stdDeviation="8" result="blur" />
                <feMerge>
                    <feMergeNode in="blur" />
                    <feMergeNode in="SourceGraphic" />
                </feMerge>
            </filter>
        </defs>

        <!-- Suelo / sombra -->
        <ellipse cx="200" cy="248" rx="130" ry="14" fill="rgb(148 163 184 / 0.2)" />

        <!-- Libro abierto -->
        <g class="bib-reader-blocked-art__book">
            <!-- lomo -->
            <path
                d="M188 72 L200 64 L212 72 L212 210 L200 218 L188 210 Z"
                fill="#0e7490"
            />
            <!-- página izquierda -->
            <path
                d="M72 88 C72 72 88 64 188 72 L188 210 C88 218 72 210 72 194 Z"
                fill="url(#bibBookLeft)"
                opacity="0.95"
            />
            <!-- página derecha -->
            <path
                d="M212 72 C312 64 328 72 328 88 L328 194 C328 210 312 218 212 210 Z"
                fill="url(#bibBookRight)"
                opacity="0.95"
            />
            <!-- líneas de texto -->
            <g opacity="0.35">
                <rect x="100" y="110" width="70" height="5" rx="2.5" fill="white" />
                <rect x="100" y="124" width="58" height="5" rx="2.5" fill="white" />
                <rect x="100" y="138" width="64" height="5" rx="2.5" fill="white" />
                <rect x="100" y="152" width="48" height="5" rx="2.5" fill="white" />
                <rect x="230" y="110" width="70" height="5" rx="2.5" fill="white" />
                <rect x="230" y="124" width="58" height="5" rx="2.5" fill="white" />
                <rect x="230" y="138" width="64" height="5" rx="2.5" fill="white" />
                <rect x="230" y="152" width="48" height="5" rx="2.5" fill="white" />
            </g>
        </g>

        <!-- Escudo + candado -->
        <g class="bib-reader-blocked-art__lock" filter="url(#bibGlow)">
            <circle cx="200" cy="128" r="52" fill="rgb(15 23 42 / 0.55)" stroke="rgb(167 139 250 / 0.5)" stroke-width="2" />
            <path
                d="M200 98c-11 0-20 9-20 20v8h-6c-3.3 0-6 2.7-6 6v34c0 3.3 2.7 6 6 6h52c3.3 0 6-2.7 6-6v-34c0-3.3-2.7-6-6-6h-6v-8c0-11-9-20-20-20zm0 10c5.5 0 10 4.5 10 10v8h-20v-8c0-5.5 4.5-10 10-10z"
                fill="url(#bibLockGrad)"
            />
            <circle cx="200" cy="142" r="5" fill="rgb(255 255 255 / 0.9)" />
        </g>

        <!-- Elementos decorativos -->
        <circle class="bib-reader-blocked-art__orb bib-reader-blocked-art__orb--1" cx="48" cy="56" r="6" fill="#22d3ee" opacity="0.6" />
        <circle class="bib-reader-blocked-art__orb bib-reader-blocked-art__orb--2" cx="352" cy="48" r="8" fill="#a78bfa" opacity="0.5" />
        <circle class="bib-reader-blocked-art__orb bib-reader-blocked-art__orb--3" cx="340" cy="200" r="5" fill="#06b6d4" opacity="0.45" />
        <path
            d="M56 200 L68 188 L80 200"
            stroke="#67e8f9"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            opacity="0.5"
        />
        <path
            d="M320 168 L332 156 L344 168"
            stroke="#c4b5fd"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            opacity="0.5"
        />
    </svg>
</template>
