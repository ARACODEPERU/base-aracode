<script setup>
import { computed } from 'vue';
import { Image } from 'ant-design-vue';

const props = defineProps({
    caseItem: { type: Object, required: true },
});

const typeLabel = computed(() => {
    if (props.caseItem.type === 'editor') {
        return 'Texto';
    }
    if (props.caseItem.type === 'image') {
        return 'Imagen';
    }
    return 'Documento';
});
</script>

<template>
    <div class="py-3 px-4 sm:py-4 sm:px-6">
        <!-- <div class="bib-reader-cases-panel__detail-head">
            <span class="bib-reader-cases-panel__detail-badge">{{ typeLabel }}</span>
            <h4 class="bib-reader-cases-panel__detail-title">{{ caseItem.title }}</h4>
        </div> -->

        <div
            v-if="caseItem.type === 'editor'"
            class="bib-reader-case-content"
            v-html="caseItem.content_html"
        />

        <div v-else-if="caseItem.type === 'image'" class="bib-reader-cases-panel__media-wrap">
            <Image
                :src="caseItem.file_url"
                :alt="caseItem.title"
                class="bib-reader-cases-panel__media"
            />
        </div>

        <div v-else class="bib-reader-cases-panel__document">
            <p class="bib-reader-cases-panel__document-name">
                {{ caseItem.file_name || 'Documento adjunto' }}
            </p>
            <p class="bib-reader-cases-panel__document-meta">
                {{ caseItem.file_mime || 'Archivo disponible para consulta' }}
            </p>
            <a
                v-if="caseItem.file_url"
                :href="caseItem.file_url"
                target="_blank"
                rel="noopener noreferrer"
                class="bib-reader-cases-panel__document-link"
            >
                Abrir documento
            </a>
        </div>
    </div>
</template>
