<script setup>
import { computed, ref, watch } from 'vue';
import UserAvatar from '../../../components/UserAvatar.vue';
import ReaderPageSheet from './ReaderPageSheet.vue';
import ReaderPracticalCasesPanel from './ReaderPracticalCasesPanel.vue';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';
import IconX from '@/Components/vristo/icon/icon-x.vue';

const props = defineProps({
    book: { type: Object, required: true },
    user: { type: Object, required: true },
    welcomeMessage: { type: String, default: '' },
    currentPage: { type: Object, default: null },
    pageLoading: { type: Boolean, default: false },
    pageError: { type: String, default: null },
    pageZoom: { type: Number, default: 100 },
    isMobileView: { type: Boolean, default: false },
    isDesktopRailView: { type: Boolean, default: false },
});

const emit = defineEmits(['update:pageZoom']);

const selectedPracticalCaseId = ref(null);
const practicalCasesOpen = ref(false);
const practicalCasesViewMode = ref('list');

const practicalCases = computed(() => props.currentPage?.practical_cases ?? []);
const showPracticalCasesPanel = computed(() => !!props.currentPage);
const showPracticalCasesRail = computed(() => practicalCasesOpen.value && props.isDesktopRailView);
const showPracticalCasesFloating = computed(() => practicalCasesOpen.value && !props.isDesktopRailView);

watch(
    () => props.currentPage,
    (page) => {
        selectedPracticalCaseId.value = page?.practical_cases?.[0]?.id ?? null;
        practicalCasesOpen.value = false;
        practicalCasesViewMode.value = 'list';
    }
);

const onSelectPracticalCase = (caseId) => {
    selectedPracticalCaseId.value = caseId;
    practicalCasesViewMode.value = 'detail';
};

const togglePracticalCases = () => {
    if (!showPracticalCasesPanel.value) return;
    if (!practicalCasesOpen.value) {
        practicalCasesViewMode.value = 'list';
    }
    practicalCasesOpen.value = !practicalCasesOpen.value;
};

const closePracticalCases = () => {
    practicalCasesOpen.value = false;
    practicalCasesViewMode.value = 'list';
};

const backToPracticalCasesList = () => {
    practicalCasesViewMode.value = 'list';
};
</script>

<template>
    <main class="bib-reader-main relative">
        <div v-if="pageLoading" class="flex h-full items-center justify-center py-24">
            <IconLoader class="h-10 w-10 animate-spin text-cyan-500" />
        </div>

        <div v-else-if="pageError" class="bib-reader-welcome">
            <p class="text-red-500">{{ pageError }}</p>
        </div>

        <div
            v-else-if="currentPage"
            class="bib-reader-reading-layout"
            :class="{ 'bib-reader-reading-layout--with-rail': showPracticalCasesRail }"
        >
            <ReaderPageSheet
                :current-page="currentPage"
                :page-zoom="pageZoom"
                :is-mobile-view="isMobileView"
                page-number-label="Página"
                @update:page-zoom="emit('update:pageZoom', $event)"
            />

            <aside v-if="showPracticalCasesRail" class="bib-reader-reading-layout__rail">
                <div class="bib-reader-reading-layout__rail-shell">
                    <div class="bib-reader-reading-layout__rail-topbar">
                        <button
                            type="button"
                            class="bib-reader-reading-layout__rail-close"
                            aria-label="Cerrar casos prácticos"
                            @click="closePracticalCases"
                        >
                            <IconX class="h-5 w-5" />
                        </button>
                    </div>
                    <ReaderPracticalCasesPanel
                        :cases="practicalCases"
                        :selected-case-id="selectedPracticalCaseId"
                        :view-mode="practicalCasesViewMode"
                        @select-case="onSelectPracticalCase"
                        @back-to-list="backToPracticalCasesList"
                    />
                </div>
            </aside>
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

        <button
            v-if="showPracticalCasesPanel && !practicalCasesOpen"
            type="button"
            class="bib-reader-cases-fab"
            aria-label="Casos prácticos"
            data-tooltip="Casos prácticos"
            @click="togglePracticalCases"
        >
            <svg
                class="h-5 w-5"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
                aria-hidden="true"
            >
                <rect x="5" y="4" width="14" height="16" rx="2.5" stroke="currentColor" stroke-width="1.8" />
                <path d="M8 9H16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                <path d="M8 13H16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                <path d="M8 17H13" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
            </svg>
        </button>

        <Transition name="bib-reader-cases-float">
            <div v-if="showPracticalCasesFloating" class="bib-reader-cases-float">
                <div class="bib-reader-cases-float__topbar">
                    <button
                        type="button"
                        class="bib-reader-cases-float__close"
                        aria-label="Cerrar casos prácticos"
                        @click="closePracticalCases"
                    >
                        <IconX class="h-5 w-5" />
                    </button>
                </div>
                <ReaderPracticalCasesPanel
                    :cases="practicalCases"
                    :selected-case-id="selectedPracticalCaseId"
                    :view-mode="practicalCasesViewMode"
                    @select-case="onSelectPracticalCase"
                    @back-to-list="backToPracticalCasesList"
                />
            </div>
        </Transition>
    </main>
</template>
