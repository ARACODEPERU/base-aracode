<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import axios from 'axios';
import ReaderLayout from '../../layouts/ReaderLayout.vue';
import ReaderAccessBlockedOverlay from './components/ReaderAccessBlockedOverlay.vue';
import ReaderIndexChapter from './components/ReaderIndexChapter.vue';
import ReaderIndexLevelContent from './components/ReaderIndexLevelContent.vue';
import ReaderExperienceChapter from './components/ReaderExperienceChapter.vue';
import ReaderExperienceLevelContent from './components/ReaderExperienceLevelContent.vue';
import ReaderBookSwitcher from './components/ReaderBookSwitcher.vue';
import UserAvatar from '../../components/UserAvatar.vue';
import IconMenu from '@/Components/vristo/icon/icon-menu.vue';
import IconX from '@/Components/vristo/icon/icon-x.vue';
import { CONTENT_STRUCTURE_LEVEL } from '../../composables/useBookContentLabels';
import { useReaderPageLoader } from '../../composables/useReaderPageLoader';

const props = defineProps({
    user: { type: Object, required: true },
    book: { type: Object, default: null },
    books: { type: Array, default: () => [] },
    canAccessAllBooks: { type: Boolean, default: false },
    openedBooks: { type: Array, default: () => [] },
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
    window.addEventListener('pagehide', syncProgressOnExit);
});

onUnmounted(() => {
    window.removeEventListener('resize', updateViewportState);
    window.removeEventListener('pagehide', syncProgressOnExit);
    clearTimeout(progressTimer);
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

const csrfHeaders = () => ({
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
    Accept: 'application/json',
});

let progressTimer = null;

const currentProgressPayload = () => {
    const page = currentPage.value;
    if (!page || !props.book) {
        return null;
    }
    return {
        book_id: props.book.id,
        current_page: page.id,
        chapter: page.section_id ?? null,
        progress: page.progress?.percent ?? 0,
    };
};

const postCurrentProgress = async () => {
    const payload = currentProgressPayload();
    if (!payload) {
        return;
    }
    try {
        await axios.post(route('bib_reader_progress'), payload, { headers: csrfHeaders() });
    } catch {
        /* el siguiente cambio de página reintentará */
    }
};

watch(
    () => currentPage.value?.id,
    () => {
        if (!currentPage.value || !props.book) {
            return;
        }
        clearTimeout(progressTimer);
        progressTimer = setTimeout(() => postCurrentProgress(), 2500);
    }
);

const markBookOpened = async (bookId) => {
    try {
        await axios.post(route('bib_reader_book_opened', { id: bookId }), null, { headers: csrfHeaders() });
    } catch {
        /* no bloquea la lectura */
    }
};

watch(
    () => props.book?.id,
    (id) => {
        if (id) {
            markBookOpened(id);
        }
    },
    { immediate: true }
);

const syncProgressOnExit = () => {
    if (!props.book) {
        return;
    }
    clearTimeout(progressTimer);
    const data = new URLSearchParams({ book_id: String(props.book.id) });
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (token) {
        data.append('_token', token);
    }
    if (typeof navigator.sendBeacon === 'function') {
        navigator.sendBeacon(route('bib_reader_progress_sync'), data);
    }
};

const flushCurrentBook = async () => {
    clearTimeout(progressTimer);
    if (!props.book) {
        return;
    }
    await postCurrentProgress();
    try {
        await axios.post(route('bib_reader_progress_sync'), { book_id: props.book.id }, { headers: csrfHeaders() });
    } catch {
        /* no bloquea la navegación */
    }
};

const goToBook = (id) => {
    if (Number(id) === Number(props.book?.id)) {
        return;
    }
    flushCurrentBook().finally(() => {
        router.get(route('bib_reader_book_open', { id }));
    });
};

const goToShelf = () => {
    flushCurrentBook().finally(() => {
        router.get(route('bib_reader_home'));
    });
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
            v-if="book"
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
                <ReaderIndexLevelContent
                    v-if="isLevelContent"
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
            v-if="book"
            type="button"
            class="fixed bottom-4 left-4 z-20 flex items-center gap-2 rounded-full bg-cyan-600 px-4 py-2 text-sm font-medium text-white shadow-lg md:hidden"
            @click="mobileIndexOpen = true"
        >
            <IconMenu class="h-5 w-5" />
            Índice
        </button>

        <template v-if="!book">
            <main v-if="canAccessAllBooks" class="bib-reader-main bib-reader-shelf">
                <div class="bib-reader-shelf__header">
                    <span class="bib-reader-shelf__badge">Mi biblioteca</span>
                    <h2 class="bib-reader-shelf__title">Elige un libro</h2>
                    <p class="bib-reader-shelf__subtitle">
                        {{ welcomeMessage || 'Selecciona un libro para comenzar a leer.' }}
                    </p>
                </div>

                <div v-if="books.length" class="bib-reader-shelf__grid">
                    <button
                        v-for="(item, idx) in books"
                        :key="item.id"
                        type="button"
                        class="bib-reader-shelf__card"
                        :style="{ animationDelay: `${Math.min(idx * 70, 700)}ms` }"
                        @click="goToBook(item.id)"
                    >
                        <span class="bib-reader-shelf__cover">
                            <img
                                v-if="item.coverUrl"
                                :src="item.coverUrl"
                                :alt="item.title"
                                loading="lazy"
                                class="h-full w-full object-cover"
                            />
                            <span v-else class="bib-reader-shelf__cover-fallback">
                                {{ item.title.charAt(0) }}
                            </span>
                        </span>
                        <span class="bib-reader-shelf__meta">
                            <span class="bib-reader-shelf__book-title">{{ item.title }}</span>
                            <span v-if="item.description" class="bib-reader-shelf__book-desc">
                                {{ item.description }}
                            </span>
                            <span class="bib-reader-shelf__cta">Leer</span>
                        </span>
                    </button>
                </div>

                <div v-else class="bib-reader-welcome">
                    <p class="text-slate-600 dark:text-slate-400">
                        Aún no hay libros activos disponibles.
                    </p>
                </div>
            </main>

            <main v-else class="bib-reader-main relative">
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

        <ReaderBookSwitcher
            v-if="book && canAccessAllBooks"
            :opened-books="openedBooks"
            :current-book-id="book.id"
            @open="goToBook"
            @shelf="goToShelf"
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
