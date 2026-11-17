<script setup>
/**
 * Tooltip de ayuda.
 *
 * Se muestra al pasar el ratón o al enfocar con el teclado. El elemento padre
 * debe llevar la clase "group relative". No usa librerías externas.
 */
defineProps({
    texto: { type: String, required: true },
    // Coloca la burbuja debajo del elemento en vez de encima.
    abajo: { type: Boolean, default: false },
    // Alinea la burbuja a la izquierda para no salirse del panel.
    izquierda: { type: Boolean, default: false },
});
</script>

<template>
    <span
        role="tooltip"
        class="pointer-events-none absolute z-50 w-64 rounded-lg bg-gray-900 px-3 py-2 text-left text-xs font-normal leading-snug text-white opacity-0 shadow-lg transition-opacity duration-150 group-hover:opacity-100 group-focus-within:opacity-100 dark:bg-black"
        :class="[
            abajo ? 'top-full mt-2' : 'bottom-full mb-2',
            izquierda ? 'left-0' : 'left-1/2 -translate-x-1/2',
        ]"
    >
        {{ texto }}
    </span>
</template>
