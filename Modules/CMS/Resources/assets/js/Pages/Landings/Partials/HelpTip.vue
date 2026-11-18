<script setup>
    // Ayuda breve que se muestra sola al pasar el mouse o al enfocar el icono.
    // Se usa un SVG inline: el tema no carga las clases de Font Awesome, asi que
    // un <i class="fa-solid ..."> quedaria invisible.
    defineProps({
        text: { type: String, required: true },
        width: { type: String, default: 'w-60' },
        position: { type: String, default: 'bottom' },
    });
</script>

<template>
    <span class="group relative ml-1 inline-flex cursor-help align-middle" tabindex="0" role="note" aria-label="Ayuda">
        <svg
            class="h-3.5 w-3.5 text-gray-400 transition-colors group-hover:text-blue-600 group-focus-within:text-blue-600"
            viewBox="0 0 20 20"
            fill="none"
            stroke="currentColor"
            stroke-width="1.6"
            aria-hidden="true"
        >
            <circle cx="10" cy="10" r="8" />
            <path d="M7.9 7.7a2.2 2.2 0 1 1 3.2 2c-.7.4-1.1 1-1.1 1.8" stroke-linecap="round" />
            <circle cx="10" cy="14.2" r=".9" fill="currentColor" stroke="none" />
        </svg>
        <span
            class="pointer-events-none absolute left-1/2 z-50 -translate-x-1/2 rounded-md bg-gray-900/95 px-3 py-2 text-xs font-normal leading-snug text-white opacity-0 shadow-lg transition-opacity duration-150 group-hover:opacity-100 group-focus-within:opacity-100 dark:bg-black"
            :class="[width, position === 'top' ? 'bottom-full mb-2' : 'top-full mt-2']"
        >
            {{ text }}
        </span>
    </span>
</template>
