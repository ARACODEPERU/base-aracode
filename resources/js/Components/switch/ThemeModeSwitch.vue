<script setup>
import { computed } from 'vue';
import { useAppStore } from '@/stores/index';
import IconSun from '@/Components/vristo/icon/icon-sun.vue';
import IconMoon from '@/Components/vristo/icon/icon-moon.vue';

const props = defineProps({
    onToggleTheme: { type: Function, default: null },
});

const store = useAppStore();
const inputId = `theme-mode-switch-${Math.random().toString(36).slice(2, 9)}`;

const isDark = computed(() => store.theme === 'dark');

const onToggle = (event) => {
    const theme = event.target.checked ? 'dark' : 'light';
    if (props.onToggleTheme) {
        props.onToggleTheme(event.target.checked);
        return;
    }
    store.toggleTheme(theme);
};
</script>

<template>
    <div class="theme-mode-switch" role="group" aria-label="Tema de la interfaz">
        <span class="theme-mode-switch__icon theme-mode-switch__icon--sun" aria-hidden="true">
            <IconSun class="h-[1.35rem] w-[1.35rem] text-amber-500" />
        </span>

        <div class="btn-color-mode-switch">
            <input
                :id="inputId"
                type="checkbox"
                class="btn-color-mode-switch__input"
                :checked="isDark"
                aria-label="Alternar entre modo claro y oscuro"
                @change="onToggle"
            />
            <label
                :for="inputId"
                class="btn-color-mode-switch-inner"
                data-off="Claro"
                data-on="Oscuro"
            />
        </div>

        <span class="theme-mode-switch__icon theme-mode-switch__icon--moon" aria-hidden="true">
            <IconMoon class="h-[1.35rem] w-[1.35rem] text-amber-400" />
        </span>
    </div>
</template>

<style scoped>
.theme-mode-switch {
    display: none;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

@media (min-width: 768px) {
    .theme-mode-switch {
        display: inline-flex;
    }
}

.theme-mode-switch__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    line-height: 0;
}

.btn-color-mode-switch {
    display: inline-block;
    position: relative;
    margin: 0;
}

.btn-color-mode-switch__input {
    cursor: pointer;
    width: 100%;
    height: 100%;
    min-width: 9.5rem;
    min-height: 2rem;
    opacity: 0;
    position: absolute;
    inset: 0;
    z-index: 2;
    margin: 0;
}

.btn-color-mode-switch-inner {
    margin: 0;
    width: 9.5rem;
    height: 2rem;
    background: #e5e7eb;
    border-radius: 9999px;
    overflow: hidden;
    position: relative;
    display: block;
    transition: background 0.3s ease;
    cursor: pointer;
}

.btn-color-mode-switch-inner::before {
    content: attr(data-on);
    position: absolute;
    font-size: 0.75rem;
    font-weight: 600;
    line-height: 1;
    top: 50%;
    right: 1.15rem;
    transform: translateY(-50%);
    color: #64748b;
    transition: color 0.3s ease;
    pointer-events: none;
}

.btn-color-mode-switch-inner::after {
    content: attr(data-off);
    width: 4.85rem;
    height: 1.65rem;
    background: #fff;
    border-radius: 9999px;
    position: absolute;
    left: 0.2rem;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 600;
    line-height: 1;
    color: #334155;
    transition: all 0.3s ease;
    box-shadow: 0 1px 4px rgba(15, 23, 42, 0.18);
    pointer-events: none;
}

.btn-color-mode-switch__input:checked + .btn-color-mode-switch-inner {
    background: #1e293b;
}

.btn-color-mode-switch__input:checked + .btn-color-mode-switch-inner::before {
    content: attr(data-off);
    color: #94a3b8;
    right: auto;
    left: 1.15rem;
}

.btn-color-mode-switch__input:checked + .btn-color-mode-switch-inner::after {
    content: attr(data-on);
    left: calc(100% - 5.05rem);
    background: #475569;
    color: #f8fafc;
    box-shadow: 0 1px 5px rgba(0, 0, 0, 0.35);
}

.dark .theme-mode-switch .btn-color-mode-switch-inner {
    background: #334155;
}

.dark .theme-mode-switch .btn-color-mode-switch-inner::before {
    color: #94a3b8;
}

.dark .theme-mode-switch .btn-color-mode-switch__input:not(:checked) + .btn-color-mode-switch-inner::after {
    background: #f1f5f9;
    color: #1e293b;
}
</style>
