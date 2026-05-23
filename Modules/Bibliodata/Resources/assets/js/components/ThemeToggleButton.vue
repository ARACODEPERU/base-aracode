<script setup>
import ThemeModeSwitch from '@/Components/switch/ThemeModeSwitch.vue';
import ThemeModeToggleCompact from '@/Components/switch/ThemeModeToggleCompact.vue';
import { useBibliotecaThemeTransition } from '../composables/useBibliotecaThemeTransition.js';

const { toggleFromCheckbox } = useBibliotecaThemeTransition();
</script>

<template>
    <div class="bib-theme-toggle">
        <ThemeModeToggleCompact :on-toggle-theme="toggleFromCheckbox" />
        <ThemeModeSwitch :on-toggle-theme="toggleFromCheckbox" />
    </div>
</template>

<style scoped>
.bib-theme-toggle {
    display: contents;
}
</style>
