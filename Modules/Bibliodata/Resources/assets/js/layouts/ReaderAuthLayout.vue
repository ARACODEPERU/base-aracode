<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { useAppStore } from '@/stores/index';
import { useBibliotecaAuthBackground } from '../composables/useBibliotecaAuthBackground.js';
import ThemeToggleButton from '../components/ThemeToggleButton.vue';
import '../../css/reader-auth.css';

const store = useAppStore();
const backgroundRef = ref(null);
useBibliotecaAuthBackground(backgroundRef);

onMounted(() => {
    document.documentElement.classList.add('bib-auth-smooth-theme');
    const saved = localStorage.getItem('theme') || store.theme || 'light';
    store.toggleTheme(saved);
});

onUnmounted(() => {
    document.documentElement.classList.remove('bib-auth-smooth-theme');
});
</script>

<template>
    <div class="bib-reader-auth relative min-h-screen overflow-hidden">
        <div class="absolute right-4 top-4 z-20">
            <ThemeToggleButton />
        </div>
        <div class="bib-auth-bg-layer bib-auth-bg-layer--base" aria-hidden="true" />
        <div ref="backgroundRef" class="bib-auth-bg-layer bib-auth-bg-layer--motion" aria-hidden="true">
            <div class="bib-auth-grid" aria-hidden="true" />
        </div>
        <div class="bib-auth-bg-layer bib-auth-bg-layer--mesh" aria-hidden="true" />
        <div class="bib-auth-vignette pointer-events-none absolute inset-0 z-[3]" aria-hidden="true" />
        <div class="relative z-10 flex min-h-screen flex-col">
            <slot />
        </div>
    </div>
</template>
