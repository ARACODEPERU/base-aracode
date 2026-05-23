<script setup>
import { computed } from 'vue';
import IconBook from '@/Components/vristo/icon/icon-book.vue';
import IconOpenBook from '@/Components/vristo/icon/icon-open-book.vue';
import IconFolderClassic from '@/Components/vristo/icon/icon-folder-classic.vue';
import IconFolderClassicOpen from '@/Components/vristo/icon/icon-folder-classic-open.vue';
import IconSquarePlus from '@/Components/vristo/icon/icon-square-plus.vue';
import IconSquareMinus from '@/Components/vristo/icon/icon-square-minus.vue';
import IconFile from '@/Components/vristo/icon/icon-file.vue';
import IconPencil from '@/Components/vristo/icon/icon-pencil.vue';
import IconTrashLines from '@/Components/vristo/icon/icon-trash-lines.vue';
import IconPlus from '@/Components/vristo/icon/icon-plus.vue';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';

defineOptions({ name: 'SectionTreeNode' });

const props = defineProps({
    section: { type: Object, required: true },
    depth: { type: Number, default: 0 },
    selectedPageId: { type: [Number, null], default: null },
    activeFolderIds: { type: Set, default: () => new Set() },
    sectionPagesCache: { type: Object, default: () => ({}) },
    isExpanded: { type: Function, required: true },
});

const emit = defineEmits([
    'toggle-expand',
    'select-page',
    'load-more-pages',
    'add-subsection',
    'edit-section',
    'delete-section',
    'add-page',
    'bulk-pages',
]);

const hasSubsections = computed(() => (props.section.children?.length ?? 0) > 0);
const hasPages = computed(() => (props.section.pages_count ?? 0) > 0);
const isExpandable = computed(() => hasSubsections.value || hasPages.value);
const expanded = computed(() => props.isExpanded(props.section.id));
const isChapter = computed(() => props.depth === 0);
const showOpenIcon = computed(() => expanded.value && isExpandable.value);

const sectionAriaLabel = computed(() => {
    const kind = isChapter.value ? 'Capítulo' : 'Sub-capítulo';
    const action = expanded.value ? 'Colapsar' : 'Expandir';
    return `${action} ${kind}: ${props.section.title}`;
});

const pagesState = computed(() => props.sectionPagesCache[props.section.id] ?? null);
const pages = computed(() => pagesState.value?.items ?? []);
const pagesLoading = computed(() => !!pagesState.value?.loading);
const hasMorePages = computed(() => {
    const s = pagesState.value;
    if (!s) return false;
    return s.currentPage < s.lastPage;
});

const indentStyle = computed(() => ({
    paddingLeft: `${props.depth * 1.25 + 0.625}rem`,
}));

const isPageSelected = (pageId) => props.selectedPageId === pageId;

const folderOnActivePath = computed(() => props.activeFolderIds.has(props.section.id));

const onToggle = () => {
    if (!isExpandable.value) return;
    emit('toggle-expand', props.section);
};

const pageLabel = (page) => {
    const n = page.page_number;
    const preview = page.preview && page.preview !== '(vacío)' ? page.preview : null;
    return preview ? `Página ${n} — ${preview}` : `Página ${n}`;
};
</script>

<template>
    <li class="list-none">
        <div
            class="explorer-row group"
            :class="{
                'explorer-row--folder-open': expanded && isExpandable,
                'explorer-row--active-folder': folderOnActivePath && !expanded,
            }"
            :style="indentStyle"
            :aria-label="isExpandable ? sectionAriaLabel : undefined"
            @click="onToggle"
        >
            <button
                v-if="isExpandable"
                type="button"
                class="explorer-toggle"
                :aria-expanded="expanded"
                :aria-label="expanded ? 'Colapsar' : 'Expandir'"
                @click.stop="onToggle"
            >
                <icon-square-minus v-if="expanded" class="w-4 h-4 text-gray-500 dark:text-gray-400" />
                <icon-square-plus v-else class="w-4 h-4 text-gray-500 dark:text-gray-400" />
            </button>
            <span v-else class="explorer-toggle explorer-toggle--spacer" aria-hidden="true" />

            <span class="explorer-icon shrink-0">
                <template v-if="isChapter">
                    <icon-open-book v-if="showOpenIcon" class="w-5 h-5 text-primary" />
                    <icon-book v-else class="w-5 h-5 text-primary" />
                </template>
                <template v-else>
                    <icon-open-book v-if="showOpenIcon" class="w-5 h-5 text-amber-500" />
                    <icon-book v-else class="w-5 h-5 text-amber-500" />
                </template>
            </span>

            <span
                class="explorer-label truncate"
                v-tippy="{ content: section.title, placement: 'right' }"
            >
                {{ section.title }}
            </span>

            <div class="explorer-actions" @click.stop>
                <button
                    v-if="depth === 0"
                    type="button"
                    class="explorer-action-btn"
                    v-tippy="{ content: 'Añadir sub-sección', placement: 'bottom' }"
                    @click="emit('add-subsection', section)"
                >
                    <icon-plus class="w-4 h-4" />
                </button>
                <button
                    type="button"
                    class="explorer-action-btn"
                    v-tippy="{ content: 'Añadir una página', placement: 'bottom' }"
                    @click="emit('add-page', section)"
                >
                    <span class="text-[11px] font-bold leading-none">P+</span>
                </button>
                <button
                    type="button"
                    class="explorer-action-btn"
                    v-tippy="{ content: 'Generar páginas en lote', placement: 'bottom' }"
                    @click="emit('bulk-pages', section)"
                >
                    <span class="text-[11px] font-bold leading-none">···</span>
                </button>
                <button
                    type="button"
                    class="explorer-action-btn"
                    v-tippy="{ content: depth === 0 ? 'Editar capítulo' : 'Editar sub-sección', placement: 'bottom' }"
                    @click="emit('edit-section', section)"
                >
                    <icon-pencil class="w-4 h-4" />
                </button>
                <button
                    type="button"
                    class="explorer-action-btn explorer-action-btn--danger"
                    v-tippy="{ content: depth === 0 ? 'Eliminar capítulo' : 'Eliminar sub-sección', placement: 'bottom' }"
                    @click="emit('delete-section', section)"
                >
                    <icon-trash-lines class="w-4 h-4" />
                </button>
            </div>
        </div>

        <template v-if="expanded && isExpandable">
            <SectionTreeNode
                v-for="child in section.children"
                :key="'sec-' + child.id"
                :section="child"
                :depth="depth + 1"
                :selected-page-id="selectedPageId"
                :active-folder-ids="activeFolderIds"
                :section-pages-cache="sectionPagesCache"
                :is-expanded="isExpanded"
                @toggle-expand="emit('toggle-expand', $event)"
                @select-page="emit('select-page', $event)"
                @load-more-pages="emit('load-more-pages', $event)"
                @add-subsection="emit('add-subsection', $event)"
                @edit-section="emit('edit-section', $event)"
                @delete-section="emit('delete-section', $event)"
                @add-page="emit('add-page', $event)"
                @bulk-pages="emit('bulk-pages', $event)"
            />

            <li
                v-if="hasPages && pagesLoading && !pages.length"
                class="explorer-row explorer-row--page explorer-row--muted list-none"
                :style="{ paddingLeft: `${(depth + 1) * 1.25 + 0.625}rem` }"
            >
                <span class="explorer-toggle explorer-toggle--spacer" />
                <IconLoader class="w-4 h-4 animate-spin text-gray-400" />
                <span class="text-sm text-gray-400">Cargando páginas...</span>
            </li>

            <li
                v-for="page in pages"
                :key="'page-' + page.id"
                class="explorer-row explorer-row--page list-none"
                :class="{ 'explorer-row--selected': isPageSelected(page.id) }"
                :style="{ paddingLeft: `${(depth + 1) * 1.25 + 0.625}rem` }"
                @click="emit('select-page', page)"
            >
                <span class="explorer-toggle explorer-toggle--spacer" aria-hidden="true" />
                <span class="explorer-icon shrink-0">
                    <icon-file class="w-5 h-5 text-slate-500 dark:text-slate-400" />
                </span>
                <span
                    class="explorer-label truncate"
                    v-tippy="{ content: pageLabel(page), placement: 'right' }"
                >
                    {{ pageLabel(page) }}
                </span>
            </li>

            <li
                v-if="hasMorePages"
                class="list-none"
                :style="{ paddingLeft: `${(depth + 1) * 1.25 + 0.625}rem` }"
            >
                <button
                    type="button"
                    class="explorer-load-more"
                    :disabled="pagesLoading"
                    @click="emit('load-more-pages', section)"
                >
                    <IconLoader v-if="pagesLoading" class="w-3 h-3 animate-spin inline mr-1" />
                    Cargar más páginas...
                </button>
            </li>
        </template>
    </li>
</template>

<style scoped>
.explorer-row {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    min-height: 2.125rem;
    padding-right: 0.5rem;
    cursor: default;
    border-radius: 0.25rem;
    margin: 0 0.25rem;
}

.explorer-row--page {
    cursor: pointer;
}

.explorer-row--page:hover {
    background-color: #e8f4fc;
}

.explorer-row--selected {
    background-color: #cce8ff;
}

.explorer-row--folder-open:hover,
.explorer-row:not(.explorer-row--page):hover {
    background-color: #f3f4f6;
}

.explorer-row--active-folder {
    background-color: #f0f9ff;
}

.explorer-row--muted {
    cursor: default;
}

.dark .explorer-row--page:hover {
    background-color: #1e3a5f;
}

.dark .explorer-row--selected {
    background-color: #1e4976;
}

.dark .explorer-row--folder-open:hover,
.dark .explorer-row:not(.explorer-row--page):hover {
    background-color: #27272a;
}

.dark .explorer-row--active-folder {
    background-color: #1e293b;
}

.explorer-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 1.125rem;
    height: 1.125rem;
    flex-shrink: 0;
    padding: 0;
    border: none;
    background: transparent;
    cursor: pointer;
    border-radius: 0.2rem;
}

.explorer-toggle:hover {
    background-color: #f3f4f6;
}

.dark .explorer-toggle:hover {
    background-color: #3f3f46;
}

.explorer-toggle--spacer {
    pointer-events: none;
}

.explorer-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 1.25rem;
    height: 1.25rem;
}

.explorer-label {
    flex: 1;
    min-width: 0;
    font-size: 0.9375rem;
    line-height: 1.375rem;
    color: #111827;
}

.dark .explorer-label {
    color: #e5e7eb;
}

.explorer-row--selected .explorer-label {
    font-weight: 500;
}

.explorer-actions {
    display: flex;
    align-items: center;
    gap: 0.125rem;
    opacity: 0;
    flex-shrink: 0;
    transition: opacity 0.12s;
}

.group:hover .explorer-actions {
    opacity: 1;
}

.explorer-action-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 1.5rem;
    height: 1.5rem;
    padding: 0 0.25rem;
    border: none;
    background: transparent;
    color: #64748b;
    border-radius: 0.2rem;
    cursor: pointer;
}

.explorer-action-btn:hover {
    color: #4361ee;
    background: #eef2ff;
}

.explorer-action-btn--danger:hover {
    color: #dc2626;
    background: #fef2f2;
}

.explorer-load-more {
    display: block;
    width: 100%;
    text-align: left;
    padding: 0.375rem 0.5rem 0.375rem 1.875rem;
    font-size: 0.8125rem;
    color: #4361ee;
    background: transparent;
    border: none;
    cursor: pointer;
}

.explorer-load-more:hover:not(:disabled) {
    text-decoration: underline;
}

.explorer-load-more:disabled {
    opacity: 0.6;
    cursor: wait;
}
</style>
