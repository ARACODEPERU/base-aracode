<script setup>
import { onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useAppStore } from '@/stores/index';
import UserAvatar from '../components/UserAvatar.vue';
import ThemeModeSwitch from '@/Components/switch/ThemeModeSwitch.vue';
import ThemeModeToggleCompact from '@/Components/switch/ThemeModeToggleCompact.vue';
import { useBibliotecaThemeTransition } from '../composables/useBibliotecaThemeTransition.js';
import '../../css/reader.css';

const store = useAppStore();
const { enableSmoothTheme, toggleFromCheckbox } = useBibliotecaThemeTransition();

onMounted(() => {
    enableSmoothTheme();
    const saved = localStorage.getItem('theme') || store.theme || 'light';
    store.toggleTheme(saved);
});

defineProps({
    bookTitle: { type: String, default: '' },
});

const logout = () => {
    router.post(route('bib_logout'));
};
</script>

<template>
    <div class="bib-reader-shell flex h-screen flex-col overflow-hidden bg-white dark:bg-slate-900">
        <header class="bib-reader-header px-0 py-3">
            <div class="bib-reader-header__grid">
                <div class="bib-reader-header__brand min-w-0 px-4 md:pl-4 md:pr-3">
                    <p class="bib-reader-header__eyebrow text-xs font-medium uppercase tracking-wider">
                        Mi biblioteca
                    </p>
                    <h1 class="bib-reader-header__title truncate text-lg font-bold">
                        {{ bookTitle || 'Biblioteca digital' }}
                    </h1>
                </div>

                <div class="bib-reader-header__switch-col">
                    <ThemeModeSwitch :on-toggle-theme="toggleFromCheckbox" />
                </div>

                <div class="bib-reader-header__actions flex items-center gap-2 px-4 sm:gap-3 md:pr-4">
                    <div class="bib-reader-header__switch-mobile shrink-0">
                        <ThemeModeToggleCompact :on-toggle-theme="toggleFromCheckbox" />
                    </div>
                    <div class="bib-reader-header__user-group flex items-center gap-2.5">
                        <UserAvatar
                            :size="100"
                            img-class="bib-reader-user-avatar h-9 w-9 shrink-0 rounded-full object-cover ring-2 ring-cyan-500/30"
                        />
                        <span class="bib-reader-header__user hidden max-w-[10rem] truncate text-sm font-medium sm:inline">
                            {{ $page.props.auth.user.name }}
                        </span>
                    </div>
                    <button type="button" class="bib-reader-header__logout rounded-lg px-3 py-1.5 text-sm" @click="logout">
                        Salir
                    </button>
                </div>
            </div>
        </header>

        <div class="flex min-h-0 flex-1 overflow-hidden">
            <slot />
        </div>
    </div>
</template>
