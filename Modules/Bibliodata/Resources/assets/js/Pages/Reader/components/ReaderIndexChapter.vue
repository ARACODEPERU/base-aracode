<script setup>
import { computed } from 'vue';
import VueCollapsible from 'vue-height-collapsible/vue3';
import IconBook from '@/Components/vristo/icon/icon-book.vue';
import IconOpenBook from '@/Components/vristo/icon/icon-open-book.vue';
import IconFile from '@/Components/vristo/icon/icon-file.vue';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';

const SECTIONS_COLLAPSE_TRANSITION = 'height 0.28s cubic-bezier(0.4, 0, 0.2, 1)';

defineOptions({ name: 'ReaderIndexChapter' });

const props = defineProps({
    section: { type: Object, required: true },
    depth: { type: Number, default: 0 },
    selectedPageId: { type: [Number, null], default: null },
    expandedIds: { type: Object, required: true },
    pagesCache: { type: Object, required: true },
    bookId: { type: Number, required: true },
});

const emit = defineEmits(['toggle-expand', 'select-page']);

const hasSubsections = computed(() => (props.section.children?.length ?? 0) > 0);
const hasPages = computed(() => (props.section.pages_count ?? 0) > 0);
const isExpandable = computed(() => hasSubsections.value || hasPages.value);
const expanded = computed(() => !!props.expandedIds[props.section.id]);
const isChapter = computed(() => props.depth === 0);

const pagesState = computed(() => props.pagesCache[props.section.id] ?? null);
const pages = computed(() => pagesState.value?.items ?? []);
const pagesLoading = computed(() => !!pagesState.value?.loading);

const indentStyle = computed(() => ({
    paddingLeft: `${props.depth * 0.85 + 0.35}rem`,
}));

const onToggle = () => {
    if (!isExpandable.value) return;
    emit('toggle-expand', props.section);
};

const onSelectPage = (page) => {
    emit('select-page', page);
};
</script>

<template>
    <li class="list-none">
        <button
            type="button"
            class="bib-reader-index-btn"
            :class="{ 'is-active': expanded && isExpandable }"
            :style="indentStyle"
            @click="onToggle"
        >
            <IconOpenBook v-if="expanded && isChapter && isExpandable" class="h-4 w-4 shrink-0 text-cyan-600" />
            <IconBook v-else-if="isChapter" class="h-4 w-4 shrink-0 text-cyan-600" />
            <IconOpenBook
                v-else-if="expanded && isExpandable"
                class="h-4 w-4 shrink-0 text-amber-500"
            />
            <IconBook v-else class="h-4 w-4 shrink-0 text-amber-500" />
            <span class="min-w-0 flex-1 truncate">{{ section.title }}</span>
            <span v-if="section.pages_count" class="shrink-0 text-xs text-slate-400">
                {{ section.pages_count }}
            </span>
        </button>

        <VueCollapsible
            v-if="hasSubsections"
            :is-open="expanded"
            :transition="SECTIONS_COLLAPSE_TRANSITION"
            class="bib-reader-index-sections"
        >
            <ul class="mt-0.5 space-y-0.5">
                <ReaderIndexChapter
                    v-for="child in section.children"
                    :key="child.id"
                    :section="child"
                    :depth="depth + 1"
                    :selected-page-id="selectedPageId"
                    :expanded-ids="expandedIds"
                    :pages-cache="pagesCache"
                    :book-id="bookId"
                    @toggle-expand="$emit('toggle-expand', $event)"
                    @select-page="$emit('select-page', $event)"
                />
            </ul>
        </VueCollapsible>

        <ul v-if="expanded && (pagesLoading || pages.length)" class="bib-reader-index-pages mt-0.5 space-y-0.5">
            <li v-if="pagesLoading" class="flex items-center gap-2 py-2 pl-8 text-xs text-slate-400">
                <IconLoader class="h-4 w-4 animate-spin" />
                Cargando páginas...
            </li>
            <li v-for="page in pages" :key="page.id" class="list-none">
                <button
                    type="button"
                    class="bib-reader-index-btn bib-reader-index-page w-full"
                    :class="{ 'is-selected': selectedPageId === page.id }"
                    :style="{ paddingLeft: `${(depth + 1) * 0.85 + 1.25}rem` }"
                    @click="onSelectPage(page)"
                >
                    <IconFile class="h-3.5 w-3.5 shrink-0 text-slate-400" />
                    <span class="min-w-0 flex-1 truncate text-left text-sm">
                        Pág. {{ page.page_number }}
                        <span v-if="page.preview && page.preview !== '(vacío)'" class="text-slate-400">
                            — {{ page.preview }}
                        </span>
                    </span>
                </button>
            </li>
        </ul>
    </li>
</template>
