<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    modelValue: { type: Number, default: 100 },
});

const emit = defineEmits(['update:modelValue']);

const panelOpen = ref(false);
const isMobile = ref(false);

const MIN_ZOOM = 75;
const MAX_ZOOM = 150;
const STEP = 5;

const zoom = computed({
    get: () => props.modelValue,
    set: (v) => emit('update:modelValue', v),
});

const updateMobile = () => {
    isMobile.value = window.matchMedia('(max-width: 767px)').matches;
};

onMounted(() => {
    updateMobile();
    window.addEventListener('resize', updateMobile);
});

onUnmounted(() => {
    window.removeEventListener('resize', updateMobile);
});

const togglePanel = () => {
    panelOpen.value = !panelOpen.value;
};

const stepZoom = (delta) => {
    const next = Math.min(MAX_ZOOM, Math.max(MIN_ZOOM, zoom.value + delta));
    zoom.value = next;
};
</script>

<template>
    <div class="bib-page-zoom">
        <div class="bib-page-zoom__bar">
            <Transition name="bib-zoom-panel">
                <div v-if="panelOpen" class="bib-page-zoom__panel" role="group" aria-label="Zoom de la página">
                    <button type="button" class="bib-page-zoom__step" aria-label="Reducir zoom" @click="stepZoom(-STEP)">
                        <span aria-hidden="true">−</span>
                    </button>

                    <div class="bib-page-zoom__track-wrap">
                        <div class="bib-page-zoom__track-line" aria-hidden="true">
                            <span class="bib-page-zoom__tick" />
                        </div>
                        <input
                            v-model.number="zoom"
                            type="range"
                            class="bib-page-zoom__range"
                            :min="MIN_ZOOM"
                            :max="MAX_ZOOM"
                            :step="STEP"
                            aria-label="Nivel de zoom"
                        />
                    </div>

                    <button type="button" class="bib-page-zoom__step" aria-label="Aumentar zoom" @click="stepZoom(STEP)">
                        <span aria-hidden="true">+</span>
                    </button>
                </div>
            </Transition>

            <button
                type="button"
                class="bib-page-zoom__fab"
                :class="{ 'is-active': panelOpen }"
                :aria-expanded="panelOpen"
                aria-label="Ajustar zoom de la página"
                @click="togglePanel"
            >
                <svg
                    class="bib-page-zoom__fab-icon"
                    viewBox="0 0 24 24"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                    aria-hidden="true"
                >
                    <circle cx="10.5" cy="10.5" r="6.5" stroke="currentColor" stroke-width="2" />
                    <path d="M16 16L20 20" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    <path d="M10.5 7.5V13.5M7.5 10.5H13.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" />
                </svg>
            </button>
        </div>
    </div>
</template>

<style scoped>
.bib-page-zoom {
    position: fixed;
    right: 1.25rem;
    bottom: 1.25rem;
    z-index: 40;
}

.bib-page-zoom__bar {
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: flex-end;
    gap: 0.5rem;
}

.bib-page-zoom__fab {
    display: flex;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    width: 3rem;
    height: 3rem;
    border-radius: 9999px;
    border: none;
    background: #1a3a5c;
    color: #fff;
    box-shadow: 0 4px 14px rgba(26, 58, 92, 0.4);
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s, background 0.2s;
}

.bib-page-zoom__fab-icon {
    width: 1.4rem;
    height: 1.4rem;
}

.bib-page-zoom__fab:hover {
    transform: scale(1.04);
    box-shadow: 0 6px 20px rgba(37, 99, 235, 0.55);
}

.bib-page-zoom__fab.is-active {
    background: #234a6e;
}

.bib-page-zoom__panel {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    height: 3rem;
    padding: 0 0.75rem;
    border-radius: 0.4rem;
    background: #1a3a5c;
    box-shadow: 0 6px 20px rgba(15, 23, 42, 0.3);
    flex-shrink: 0;
}

.bib-page-zoom__step {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 1.5rem;
    height: 100%;
    padding: 0;
    border: none;
    background: transparent;
    color: #fff;
    font-size: 1.25rem;
    font-weight: 300;
    line-height: 1;
    cursor: pointer;
    opacity: 0.95;
}

.bib-page-zoom__step:hover {
    opacity: 1;
}

.bib-page-zoom__track-wrap {
    position: relative;
    width: 11rem;
    height: 1.5rem;
    display: flex;
    align-items: center;
}

.bib-page-zoom__track-line {
    position: absolute;
    left: 0;
    right: 0;
    top: 50%;
    height: 2px;
    margin-top: -1px;
    background: #fff;
    pointer-events: none;
}

.bib-page-zoom__tick {
    position: absolute;
    left: 50%;
    top: 50%;
    width: 2px;
    height: 10px;
    margin-left: -1px;
    margin-top: -5px;
    background: #fff;
}

.bib-page-zoom__range {
    position: relative;
    z-index: 1;
    width: 100%;
    height: 1.5rem;
    margin: 0;
    background: transparent;
    cursor: pointer;
    -webkit-appearance: none;
    appearance: none;
}

.bib-page-zoom__range::-webkit-slider-runnable-track {
    height: 2px;
    background: transparent;
}

.bib-page-zoom__range::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 5px;
    height: 20px;
    margin-top: -9px;
    border-radius: 1px;
    background: #fff;
    cursor: grab;
}

.bib-page-zoom__range::-moz-range-track {
    height: 2px;
    background: transparent;
}

.bib-page-zoom__range::-moz-range-thumb {
    width: 5px;
    height: 20px;
    border: none;
    border-radius: 1px;
    background: #fff;
    cursor: grab;
}

.bib-zoom-panel-enter-active,
.bib-zoom-panel-leave-active {
    transition: opacity 0.22s ease, transform 0.22s ease;
}

.bib-zoom-panel-enter-from,
.bib-zoom-panel-leave-to {
    opacity: 0;
    transform: translateX(10px);
}
</style>
