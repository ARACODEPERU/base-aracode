<script setup>
import { ref, watch } from 'vue';
import SectionTreeNode from './SectionTreeNode.vue';

const props = defineProps({
    sections: { type: Array, default: () => [] },
    selectedPageId: { type: [Number, null], default: null },
    activeFolderIds: { type: Set, default: () => new Set() },
    sectionPagesCache: { type: Object, default: () => ({}) },
    loading: { type: Boolean, default: false },
    expandTrigger: { type: [Number, null], default: null },
});

const emit = defineEmits([
    'toggle-expand',
    'select-page',
    'load-more-pages',
    'add-chapter',
    'add-subsection',
    'edit-section',
    'delete-section',
    'add-page',
    'bulk-pages',
]);

/** Por defecto todo colapsado; solo true significa desplegado. */
const expanded = ref({});

const isExpanded = (id) => expanded.value[id] === true;

const toggleExpand = (section) => {
    const id = section.id;
    const willExpand = !isExpanded(id);
    expanded.value = { ...expanded.value, [id]: willExpand };
    if (willExpand) {
        emit('toggle-expand', section);
    }
};

const ensureExpanded = (id) => {
    if (!isExpanded(id)) {
        expanded.value = { ...expanded.value, [id]: true };
    }
};

const findSectionById = (list, id) => {
    for (const s of list) {
        if (s.id === id) return s;
        if (s.children?.length) {
            const found = findSectionById(s.children, id);
            if (found) return found;
        }
    }
    return null;
};

watch(
    () => props.expandTrigger,
    (id) => {
        if (id == null) return;
        const wasExpanded = isExpanded(id);
        ensureExpanded(id);
        if (!wasExpanded) {
            const section = findSectionById(props.sections, id);
            if (section) {
                emit('toggle-expand', section);
            }
        }
    }
);

defineExpose({ ensureExpanded });
</script>

<template>
    <div class="flex flex-col h-full min-h-0 bib-section-tree">
        <div
            class="bib-workspace-bar flex items-center justify-between gap-2 px-3 border-b border-gray-200 dark:border-zinc-700 shrink-0 box-border"
        >
            <span class="text-sm font-semibold text-gray-700 dark:text-neutral-200">Contenido</span>
            <button
                type="button"
                class="btn btn-primary btn-sm text-xs py-1.5 px-2.5"
                v-tippy="{ content: 'Nuevo capítulo', placement: 'bottom' }"
                @click="emit('add-chapter')"
            >
                + Capítulo
            </button>
        </div>

        <div v-if="loading" class="p-4 text-center text-sm text-gray-400 shrink-0">Cargando...</div>

        <div v-else-if="!sections.length" class="p-6 text-center shrink-0">
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Aún no hay capítulos</p>
            <button type="button" class="btn btn-primary btn-sm" @click="emit('add-chapter')">
                Crear primer capítulo
            </button>
        </div>

        <perfect-scrollbar
            v-else
            :options="{ swipeEasing: true, wheelPropagation: false }"
            class="relative flex-1 min-h-0 ltr:pr-3.5 rtl:pl-3.5 ltr:-mr-3.5 rtl:-ml-3.5"
        >
            <ul class="explorer-tree list-none m-0 py-2.5 px-0.5">
                <SectionTreeNode
                    v-for="sec in sections"
                    :key="sec.id"
                    :section="sec"
                    :depth="0"
                    :selected-page-id="selectedPageId"
                    :active-folder-ids="activeFolderIds"
                    :section-pages-cache="sectionPagesCache"
                    :is-expanded="isExpanded"
                    @toggle-expand="toggleExpand"
                    @select-page="emit('select-page', $event)"
                    @load-more-pages="emit('load-more-pages', $event)"
                    @add-subsection="emit('add-subsection', $event)"
                    @edit-section="emit('edit-section', $event)"
                    @delete-section="emit('delete-section', $event)"
                    @add-page="emit('add-page', $event)"
                    @bulk-pages="emit('bulk-pages', $event)"
                />
            </ul>
        </perfect-scrollbar>
    </div>
</template>

<style scoped>
.bib-workspace-bar {
    height: 3rem;
    min-height: 3rem;
}

.explorer-tree {
    font-family: inherit;
}
</style>
