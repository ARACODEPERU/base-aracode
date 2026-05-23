<script setup>
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue';
import ReaderLayout from '../../layouts/ReaderLayout.vue';
import UserAvatar from '../../components/UserAvatar.vue';
import ReaderIndexNode from './components/ReaderIndexNode.vue';
import ReaderPageZoom from './components/ReaderPageZoom.vue';
import IconMenu from '@/Components/vristo/icon/icon-menu.vue';
import IconX from '@/Components/vristo/icon/icon-x.vue';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';

const props = defineProps({
    user: { type: Object, required: true },
    book: { type: Object, default: null },
    sections: { type: Array, default: () => [] },
    welcomeMessage: { type: String, default: '' },
});

const mobileIndexOpen = ref(false);
const expandedIds = reactive({});
const pagesCache = reactive({});
const selectedPageId = ref(null);
const currentPage = ref(null);
const pageLoading = ref(false);
const pageError = ref(null);
const pageZoom = ref(100);
const isMobileView = ref(false);

const updateMobileView = () => {
    isMobileView.value = window.matchMedia('(max-width: 767px)').matches;
};

onMounted(() => {
    updateMobileView();
    window.addEventListener('resize', updateMobileView);
});

onUnmounted(() => {
    window.removeEventListener('resize', updateMobileView);
});

const sheetScalerStyle = computed(() => {
    if (isMobileView.value) {
        return {};
    }
    const scale = pageZoom.value / 100;
    return {
        transform: `scale(${scale})`,
        transformOrigin: 'top center',
    };
});

const csrfHeaders = () => ({
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
    Accept: 'application/json',
});

const loadSectionPages = async (sectionId) => {
    if (pagesCache[sectionId]?.items?.length) {
        return;
    }

    pagesCache[sectionId] = { loading: true, items: [] };

    try {
        const { data } = await axios.get(route('bib_reader_section_pages', sectionId), {
            params: { per_page: 200 },
            headers: csrfHeaders(),
        });
        pagesCache[sectionId] = {
            loading: false,
            items: data.pages?.data ?? [],
        };
    } catch {
        pagesCache[sectionId] = { loading: false, items: [], error: true };
    }
};

const onToggleExpand = async (section) => {
    const id = section.id;
    if (expandedIds[id]) {
        delete expandedIds[id];
        return;
    }
    expandedIds[id] = true;
    if (section.pages_count > 0) {
        await loadSectionPages(id);
    }
};

const onSelectPage = async (page) => {
    selectedPageId.value = page.id;
    mobileIndexOpen.value = false;
    pageLoading.value = true;
    pageError.value = null;
    currentPage.value = null;

    try {
        const { data } = await axios.get(route('bib_reader_page_show', page.id), {
            headers: csrfHeaders(),
        });
        if (data.success) {
            currentPage.value = data.page;
        }
    } catch {
        pageError.value = 'No se pudo cargar el contenido de la página.';
    } finally {
        pageLoading.value = false;
    }
};
</script>

<template>
    <ReaderLayout :book-title="book?.title ?? ''">
        <Head :title="book ? `Leer — ${book.title}` : 'Mi biblioteca'" />

        <aside
            class="bib-reader-sidebar hidden flex-col overflow-hidden md:flex"
            :class="{ '!flex': mobileIndexOpen }"
        >
            <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3 dark:border-slate-700">
                <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Índice del libro</h2>
                <button
                    type="button"
                    class="md:hidden text-slate-500"
                    @click="mobileIndexOpen = false"
                >
                    <IconX class="h-5 w-5" />
                </button>
            </div>
            <nav class="flex-1 overflow-y-auto p-3">
                <p v-if="!book" class="px-2 text-sm text-slate-500">No hay libro asignado.</p>
                <ul v-else class="space-y-0.5">
                    <ReaderIndexNode
                        v-for="section in sections"
                        :key="section.id"
                        :section="section"
                        :selected-page-id="selectedPageId"
                        :expanded-ids="expandedIds"
                        :pages-cache="pagesCache"
                        :book-id="book.id"
                        @toggle-expand="onToggleExpand"
                        @select-page="onSelectPage"
                    />
                </ul>
            </nav>
        </aside>

        <button
            type="button"
            class="fixed bottom-4 left-4 z-20 flex items-center gap-2 rounded-full bg-cyan-600 px-4 py-2 text-sm font-medium text-white shadow-lg md:hidden"
            @click="mobileIndexOpen = true"
        >
            <IconMenu class="h-5 w-5" />
            Índice
        </button>

        <main class="bib-reader-main relative">
            <div v-if="!book" class="bib-reader-welcome">
                <UserAvatar
                    :size="150"
                    :rounded="true"
                    img-class="bib-reader-user-avatar mx-auto mb-5 h-20 w-20 rounded-full object-cover shadow-lg ring-4 ring-cyan-500/20"
                />
                <h2 class="text-2xl font-bold text-slate-800 dark:text-slate-100">
                    ¡Hola, {{ user.name }}!
                </h2>
                <p class="mt-4 text-slate-600 dark:text-slate-400">{{ welcomeMessage }}</p>
            </div>

            <div v-else-if="pageLoading" class="flex h-full items-center justify-center py-24">
                <IconLoader class="h-10 w-10 animate-spin text-cyan-500" />
            </div>

            <div v-else-if="pageError" class="bib-reader-welcome">
                <p class="text-red-500">{{ pageError }}</p>
            </div>

            <div v-else-if="currentPage" class="bib-reader-page-stage">
                <div class="bib-reader-page-sheet-scaler" :style="sheetScalerStyle">
                    <article class="bib-reader-page-sheet bib-reader-page-content">
                        <header class="bib-reader-page-sheet__header">
                            <p v-if="currentPage.section_title" class="bib-reader-page-sheet__section">
                                {{ currentPage.section_title }}
                            </p>
                            <h2 class="bib-reader-page-sheet__title">
                                Página {{ currentPage.page_number }}
                            </h2>
                        </header>
                        <div class="bib-reader-page-sheet__body" v-html="currentPage.content" />
                    </article>
                </div>
                <ReaderPageZoom v-model="pageZoom" />
            </div>

            <div v-else class="bib-reader-welcome">
                <UserAvatar
                    :size="150"
                    :rounded="true"
                    img-class="bib-reader-user-avatar mx-auto mb-5 h-20 w-20 rounded-full object-cover shadow-lg ring-4 ring-cyan-500/20"
                />
                <div
                    v-if="book.coverUrl"
                    class="mx-auto mb-6 h-36 w-28 overflow-hidden rounded-lg shadow-xl ring-1 ring-slate-200 dark:ring-slate-600"
                >
                    <img :src="book.coverUrl" :alt="book.title" class="h-full w-full object-cover" />
                </div>
                <h2 class="text-3xl font-bold text-slate-800 dark:text-slate-100">
                    ¡Hola, {{ user.name }}!
                </h2>
                <p class="mt-4 text-lg text-slate-600 dark:text-slate-300">
                    {{ welcomeMessage }}
                </p>
                <p v-if="book.author" class="mt-2 text-sm text-slate-500">Autor: {{ book.author }}</p>
                <p class="mt-8 text-sm text-slate-400">
                    Abre el <strong class="text-slate-600 dark:text-slate-300">índice</strong> y elige una página para leer.
                </p>
            </div>
        </main>
    </ReaderLayout>
</template>

<style scoped>
@media (max-width: 767px) {
    .bib-reader-sidebar {
        position: fixed;
        inset: 0;
        top: 3.5rem;
        z-index: 30;
        max-width: none;
    }
}
</style>
