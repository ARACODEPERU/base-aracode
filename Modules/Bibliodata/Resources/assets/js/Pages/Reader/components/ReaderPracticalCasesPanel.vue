<script setup>
import { computed } from 'vue';
import ReaderPracticalCaseContent from './ReaderPracticalCaseContent.vue';

const props = defineProps({
    cases: { type: Array, default: () => [] },
    selectedCaseId: { type: [Number, null], default: null },
    viewMode: { type: String, default: 'list' },
});

const emit = defineEmits(['select-case', 'back-to-list']);

const selectedCase = computed(() => {
    if (!props.cases.length) {
        return null;
    }

    return props.cases.find((item) => item.id === props.selectedCaseId) || props.cases[0];
});

const resolveTypeLabel = (type) => {
    if (type === 'editor') {
        return 'Texto';
    }

    if (type === 'image') {
        return 'Imagen';
    }

    return 'Documento';
};

</script>

<template>
    <aside class="bib-reader-cases-panel">
        <div class="bib-reader-cases-panel__header">
            <div>
                <p class="bib-reader-cases-panel__eyebrow">Apoyo de lectura</p>
                <h3 class="bib-reader-cases-panel__title">Casos prácticos</h3>
            </div>
            <span class="bib-reader-cases-panel__count">{{ cases.length }}</span>
        </div>

        <div v-if="cases.length && viewMode === 'list'" class="bib-reader-cases-panel__body">
            <div class="bib-reader-cases-panel__list">
                <button
                    v-for="caseItem in cases"
                    :key="caseItem.id"
                    type="button"
                    class="bib-reader-cases-panel__item"
                    :class="{ 'is-active': selectedCase?.id === caseItem.id }"
                    @click="emit('select-case', caseItem.id)"
                >
                    <span class="bib-reader-cases-panel__item-title">{{ caseItem.title }}</span>
                    <span class="bib-reader-cases-panel__item-type">{{ resolveTypeLabel(caseItem.type) }}</span>
                </button>
            </div>
        </div>

        <div v-else-if="cases.length && selectedCase" class="bib-reader-cases-panel__viewer">
            <div class="bib-reader-cases-panel__viewer-topbar">
                <button
                    type="button"
                    class="bib-reader-cases-panel__viewer-back"
                    @click="emit('back-to-list')"
                >
                    Volver a la lista
                </button>
            </div>

            <ReaderPracticalCaseContent :case-item="selectedCase" />
        </div>

        <div v-else class="bib-reader-cases-panel__empty">
            <p class="bib-reader-cases-panel__empty-title">Sin casos prácticos visibles</p>
            <p class="bib-reader-cases-panel__empty-text">
                Esta página aún no tiene ejemplos o archivos de apoyo para el lector.
            </p>
        </div>
    </aside>
</template>
