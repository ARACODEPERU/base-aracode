<script setup>
import { computed } from 'vue';
import ReaderPageZoom from './ReaderPageZoom.vue';

const props = defineProps({
    currentPage: { type: Object, default: null },
    pageZoom: { type: Number, default: 100 },
    isMobileView: { type: Boolean, default: false },
    pageNumberLabel: { type: String, default: 'Página' },
});

const emit = defineEmits(['update:pageZoom']);

const sheetScalerStyle = computed(() => {
    if (props.isMobileView) {
        return {};
    }
    const scale = props.pageZoom / 100;
    return {
        transform: `scale(${scale})`,
        transformOrigin: 'top center',
    };
});
</script>

<template>
    <section class="bib-reader-reading-layout__sheet">
        <div class="bib-reader-page-stage">
            <div class="bib-reader-page-sheet-scaler" :style="sheetScalerStyle">
                <article class="bib-reader-page-sheet bib-reader-page-content">
                    <header class="bib-reader-page-sheet__header">
                        <p v-if="currentPage.section_title" class="bib-reader-page-sheet__section">
                            {{ currentPage.section_title }}
                        </p>
                        <h2 class="bib-reader-page-sheet__title">
                            {{ pageNumberLabel }} {{ currentPage.page_number }}
                        </h2>
                    </header>
                    <div class="bib-reader-page-sheet__body" v-html="currentPage.content" />
                </article>
            </div>
            <ReaderPageZoom :model-value="pageZoom" @update:model-value="emit('update:pageZoom', $event)" />
        </div>
    </section>
</template>
