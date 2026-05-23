<script setup>
import { ref } from 'vue';
import { useReaderAccessBlockedAnimation } from '../../../composables/useReaderAccessBlockedAnimation.js';
import ReaderAccessBlockedIllustration from './ReaderAccessBlockedIllustration.vue';

const emit = defineEmits(['close']);

const rootRef = ref(null);
useReaderAccessBlockedAnimation(rootRef);
</script>

<template>
    <div
        ref="rootRef"
        class="bib-reader-access-blocked"
        role="dialog"
        aria-modal="true"
        aria-labelledby="bib-access-blocked-title"
    >
        <div class="bib-reader-access-blocked__backdrop" />

        <div class="bib-reader-access-blocked__fx" aria-hidden="true" />

        <div class="bib-reader-access-blocked__content">
            <div class="bib-reader-access-blocked__rings" aria-hidden="true" />

            <div class="bib-reader-access-blocked__hero-wrap">
                <div class="bib-reader-access-blocked__glow" aria-hidden="true" />
                <div class="bib-reader-access-blocked__platform" aria-hidden="true" />
                <div class="bib-reader-access-blocked__hero-card">
                    <ReaderAccessBlockedIllustration class="bib-reader-access-blocked__hero" />
                </div>
            </div>

            <h2 id="bib-access-blocked-title" class="bib-reader-access-blocked__title">
                Biblioteca virtual — Acceso bloqueado
            </h2>
            <p class="bib-reader-access-blocked__subtitle">
                Ya usaste tu página de vista previa. Para seguir leyendo necesitas una suscripción activa.
                Contacta al administrador de tu organización o solicita la activación de tu plan.
            </p>

            <button type="button" class="bib-reader-access-blocked__btn" @click="emit('close')">
                Entendido
            </button>
        </div>
    </div>
</template>
