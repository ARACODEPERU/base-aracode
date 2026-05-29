<script setup>
import { ref } from 'vue';
import UserAvatar from '../../../components/UserAvatar.vue';
import ReaderPageSheet from './ReaderPageSheet.vue';
import ReaderPracticalCaseModal from './ReaderPracticalCaseModal.vue';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';

const props = defineProps({
    book: { type: Object, required: true },
    user: { type: Object, required: true },
    welcomeMessage: { type: String, default: '' },
    currentPage: { type: Object, default: null },
    pageLoading: { type: Boolean, default: false },
    pageError: { type: String, default: null },
    pageZoom: { type: Number, default: 100 },
    isMobileView: { type: Boolean, default: false },
    fetchPracticalCase: { type: Function, required: true },
});

const emit = defineEmits(['update:pageZoom', 'case-selected']);

const caseModalOpen = ref(false);
const caseModalLoading = ref(false);
const modalCase = ref(null);

const openCaseModal = async ({ page, caseItem }) => {
    emit('case-selected', caseItem.id);
    caseModalOpen.value = true;
    caseModalLoading.value = true;
    modalCase.value = null;

    const data = await props.fetchPracticalCase(page.id, caseItem.id);
    modalCase.value = data;
    caseModalLoading.value = false;

    if (!data) {
        caseModalOpen.value = false;
    }
};

const closeCaseModal = () => {
    caseModalOpen.value = false;
    modalCase.value = null;
    emit('case-selected', null);
};

defineExpose({ openCaseModal, closeCaseModal });
</script>

<template>
    <main class="bib-reader-main relative">
        <div v-if="pageLoading" class="flex h-full items-center justify-center py-24">
            <IconLoader class="h-10 w-10 animate-spin text-cyan-500" />
        </div>

        <div v-else-if="pageError" class="bib-reader-welcome">
            <p class="text-red-500">{{ pageError }}</p>
        </div>

        <div v-else-if="currentPage" class="bib-reader-reading-layout">
            <ReaderPageSheet
                :current-page="currentPage"
                :page-zoom="pageZoom"
                :is-mobile-view="isMobileView"
                page-number-label="Contenido"
                @update:page-zoom="emit('update:pageZoom', $event)"
            />
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
                Abre el <strong class="text-slate-600 dark:text-slate-300">índice</strong> y elige un contenido para leer.
            </p>
        </div>

        <ReaderPracticalCaseModal
            :show="caseModalOpen"
            :loading="caseModalLoading"
            :case-item="modalCase"
            @close="closeCaseModal"
        />
    </main>
</template>
