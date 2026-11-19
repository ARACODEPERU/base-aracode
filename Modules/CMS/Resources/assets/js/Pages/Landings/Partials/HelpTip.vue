<script setup>
    import { nextTick, ref } from 'vue';

    // Ayuda breve que se muestra sola al pasar el mouse o al enfocar el icono.
    // La burbuja se posiciona con coordenadas calculadas para que nunca se salga
    // de la pantalla, ni siquiera en moviles angostos. El icono es un SVG inline
    // porque el tema no carga las clases de Font Awesome.
    defineProps({
        text: { type: String, required: true },
        maxWidth: { type: Number, default: 288 },
    });

    const wrapper = ref(null);
    const bubble = ref(null);
    const visible = ref(false);
    const coords = ref({ top: 0, left: 0 });

    // Se usa nextTick y no requestAnimationFrame: el navegador congela los
    // frames cuando la pestaña esta en segundo plano y la burbuja quedaria
    // pegada en la esquina superior izquierda.
    const show = async () => {
        visible.value = true;

        await nextTick();

        if (!wrapper.value || !bubble.value) return;

        const icon = wrapper.value.getBoundingClientRect();
        const box = bubble.value.getBoundingClientRect();
        const margin = 8;

        let left = icon.left + icon.width / 2 - box.width / 2;
        left = Math.max(margin, Math.min(left, window.innerWidth - box.width - margin));

        // Si no entra abajo, se muestra encima del icono.
        let top = icon.bottom + margin;

        if (top + box.height > window.innerHeight - margin) {
            top = Math.max(margin, icon.top - box.height - margin);
        }

        coords.value = { top, left };
    };

    const hide = () => {
        visible.value = false;
    };
</script>

<template>
    <span
        ref="wrapper"
        class="relative ml-1 inline-flex cursor-help align-middle"
        tabindex="0"
        role="note"
        aria-label="Ayuda"
        @mouseenter="show"
        @mouseleave="hide"
        @focus="show"
        @blur="hide"
    >
        <svg
            class="h-3.5 w-3.5 text-gray-400 transition-colors"
            :class="visible ? 'text-blue-600' : 'hover:text-blue-600'"
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
            v-show="visible"
            ref="bubble"
            class="pointer-events-none fixed z-[9999] rounded-md bg-gray-900/95 px-3 py-2 text-left text-xs font-normal leading-snug text-white shadow-lg dark:bg-black"
            :style="{ top: coords.top + 'px', left: coords.left + 'px', width: 'min(' + maxWidth + 'px, calc(100vw - 16px))' }"
        >
            {{ text }}
        </span>
    </span>
</template>
