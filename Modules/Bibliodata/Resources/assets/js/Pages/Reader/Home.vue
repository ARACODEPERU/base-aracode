<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import ReaderLayout from '../../layouts/ReaderLayout.vue';
import ReaderAccessBlockedOverlay from './components/ReaderAccessBlockedOverlay.vue';
import ReaderIndexChapter from './components/ReaderIndexChapter.vue';
import ReaderIndexLevelContent from './components/ReaderIndexLevelContent.vue';
import ReaderExperienceChapter from './components/ReaderExperienceChapter.vue';
import ReaderExperienceLevelContent from './components/ReaderExperienceLevelContent.vue';
import UserAvatar from '../../components/UserAvatar.vue';
import IconMenu from '@/Components/vristo/icon/icon-menu.vue';
import IconX from '@/Components/vristo/icon/icon-x.vue';
import { CONTENT_STRUCTURE_LEVEL } from '../../composables/useBookContentLabels';
import { useReaderPageLoader } from '../../composables/useReaderPageLoader';

const props = defineProps({
    user: { type: Object, required: true },
    book: { type: Object, default: null },
    sections: { type: Array, default: () => [] },
    access: {
        type: Object,
        default: () => ({ hasActiveSubscription: false, previewPageId: null }),
    },
    welcomeMessage: { type: String, default: '' },
});

const mobileIndexOpen = ref(false);
const isMobileView = ref(false);
const isDesktopRailView = ref(false);
const selectedCaseId = ref(null);
const chapterExperienceRef = ref(null);
const levelExperienceRef = ref(null);

const isLevelContent = computed(
    () => props.book?.content_structure === CONTENT_STRUCTURE_LEVEL
);

const closeMobileIndex = () => {
    mobileIndexOpen.value = false;
};

const loader = useReaderPageLoader(props, { onMobileIndexClose: closeMobileIndex });

const {
    expandedIds,
    pagesCache,
    selectedPageId,
    currentPage,
    pageLoading,
    pageError,
    pageZoom,
    showAccessBlocked,
    onToggleExpand,
    onSelectPage: loadPage,
    fetchPracticalCase,
} = loader;

const updateViewportState = () => {
    isMobileView.value = window.matchMedia('(max-width: 767px)').matches;
    isDesktopRailView.value = window.matchMedia('(min-width: 1536px)').matches;
};

onMounted(() => {
    updateViewportState();
    window.addEventListener('resize', updateViewportState);
});

onUnmounted(() => {
    window.removeEventListener('resize', updateViewportState);
});

const onSelectPage = async (page) => {
    selectedCaseId.value = null;
    await loadPage(page);
};

const onSelectCase = async (payload) => {
    await levelExperienceRef.value?.openCaseModal(payload);
};

const onCaseSelected = (caseId) => {
    selectedCaseId.value = caseId;
};
</script>

<template>
    <ReaderLayout :book-title="book?.title ?? ''">
        <Head :title="book ? `Leer — ${book.title}` : 'Mi biblioteca'" />

        <ReaderAccessBlockedOverlay
            v-if="showAccessBlocked"
            @close="showAccessBlocked = false"
        />

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
                <ReaderIndexLevelContent
                    v-else-if="isLevelContent"
                    :sections="sections"
                    :selected-page-id="selectedPageId"
                    :selected-case-id="selectedCaseId"
                    :expanded-ids="expandedIds"
                    :pages-cache="pagesCache"
                    @toggle-expand="onToggleExpand"
                    @select-page="onSelectPage"
                    @select-case="onSelectCase"
                />
                <ul v-else class="space-y-0.5">
                    <ReaderIndexChapter
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

        <template v-if="!book">
            <main class="bib-reader-main relative">
                <div class="bib-reader-welcome">
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
            </main>
        </template>

        <ReaderExperienceLevelContent
            v-else-if="isLevelContent"
            ref="levelExperienceRef"
            :book="book"
            :user="user"
            :welcome-message="welcomeMessage"
            :current-page="currentPage"
            :page-loading="pageLoading"
            :page-error="pageError"
            :page-zoom="pageZoom"
            :is-mobile-view="isMobileView"
            :fetch-practical-case="fetchPracticalCase"
            @update:page-zoom="pageZoom = $event"
            @case-selected="onCaseSelected"
        />

        <ReaderExperienceChapter
            v-else
            :book="book"
            :user="user"
            :welcome-message="welcomeMessage"
            :current-page="currentPage"
            :page-loading="pageLoading"
            :page-error="pageError"
            :page-zoom="pageZoom"
            :is-mobile-view="isMobileView"
            :is-desktop-rail-view="isDesktopRailView"
            @update:page-zoom="pageZoom = $event"
        />
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
