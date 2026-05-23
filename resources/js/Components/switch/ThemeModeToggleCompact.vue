<script setup>
import { computed } from 'vue';
import { useAppStore } from '@/stores/index';
import IconSun from '@/Components/vristo/icon/icon-sun.vue';
import IconMoon from '@/Components/vristo/icon/icon-moon.vue';

const props = defineProps({
    onToggleTheme: { type: Function, default: null },
});

const store = useAppStore();
const inputId = `theme-compact-${Math.random().toString(36).slice(2, 9)}`;

const isDark = computed(() => store.theme === 'dark');

const onChange = (event) => {
    const theme = event.target.checked ? 'dark' : 'light';
    if (props.onToggleTheme) {
        props.onToggleTheme(event.target.checked);
        return;
    }
    store.toggleTheme(theme);
};
</script>

<template>
    <div class="theme-toggle-compact">
        <input
            :id="inputId"
            type="checkbox"
            class="theme-toggle-compact__input"
            :checked="isDark"
            aria-label="Alternar modo claro u oscuro"
            @change="onChange"
        />
        <label :for="inputId" class="theme-toggle-compact__btn">
            <IconSun class="theme-toggle-compact__icon theme-toggle-compact__icon--sun" />
            <IconMoon class="theme-toggle-compact__icon theme-toggle-compact__icon--moon" />
        </label>
    </div>
</template>

<style scoped>
.theme-toggle-compact {
    display: inline-flex;
}

@media (min-width: 768px) {
    .theme-toggle-compact {
        display: none;
    }
}

.theme-toggle-compact * {
    transition: all 0.25s ease;
}

.theme-toggle-compact__input {
    position: absolute;
    width: 0;
    height: 0;
    opacity: 0;
    pointer-events: none;
}

.theme-toggle-compact__btn {
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    width: 2.75rem;
    height: 2.75rem;
    margin: 0;
    border: 1px solid rgb(186 230 253);
    border-radius: 0.65rem;
    background: rgb(255 255 255 / 0.92);
    box-shadow: 0 1px 4px rgb(14 116 144 / 0.12);
    cursor: pointer;
}

.theme-toggle-compact__btn:active {
    border-radius: 0.45rem;
    transform: scale(0.96);
}

.theme-toggle-compact__icon {
    position: absolute;
    width: 1.15rem;
    height: 1.15rem;
    z-index: 1;
    transition: opacity 0.25s ease, transform 0.25s ease;
}

.theme-toggle-compact__icon--sun {
    color: #fff;
    opacity: 0;
    transform: scale(0.6);
}

.theme-toggle-compact__icon--moon {
    color: #0e7490;
    opacity: 1;
    transform: scale(1);
}

.theme-toggle-compact__input:checked + .theme-toggle-compact__btn {
    border-color: rgb(103 232 249);
    background: #0e7490;
    box-shadow: 0 2px 8px rgb(14 116 144 / 0.35);
}

.theme-toggle-compact__input:checked + .theme-toggle-compact__btn .theme-toggle-compact__icon--sun {
    opacity: 1;
    transform: scale(1);
}

.theme-toggle-compact__input:checked + .theme-toggle-compact__btn .theme-toggle-compact__icon--moon {
    opacity: 0;
    transform: scale(0.6);
}

/* Header lector en modo oscuro (fondo del header oscuro) */
.dark .theme-toggle-compact__btn {
    border-color: rgb(71 85 105);
    background: rgb(30 41 59 / 0.85);
    box-shadow: 0 1px 4px rgb(0 0 0 / 0.25);
}

.dark .theme-toggle-compact__btn .theme-toggle-compact__icon--moon {
    color: #94a3b8;
}

.dark .theme-toggle-compact__input:checked + .theme-toggle-compact__btn {
    border-color: rgb(34 211 238);
    background: #0891b2;
}

.dark .theme-toggle-compact__input:checked + .theme-toggle-compact__btn .theme-toggle-compact__icon--sun {
    color: #fff;
}
</style>
