<script setup>
import IconBook from '@/Components/vristo/icon/icon-book.vue';
import IconOpenBook from '@/Components/vristo/icon/icon-open-book.vue';
import IconFile from '@/Components/vristo/icon/icon-file.vue';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';

defineOptions({ name: 'ReaderIndexLevelContent' });

const props = defineProps({
    sections: { type: Array, default: () => [] },
    selectedPageId: { type: [Number, null], default: null },
    selectedCaseId: { type: [Number, null], default: null },
    expandedIds: { type: Object, required: true },
    pagesCache: { type: Object, required: true },
});

const emit = defineEmits(['toggle-expand', 'select-page', 'select-case']);

const contentIndent = '1.6rem';
const caseIndent = '2.45rem';
</script>

<template>
    <ul class="space-y-0.5">
        <li v-for="section in sections" :key="section.id" class="list-none">
            <button
                type="button"
                class="bib-reader-index-btn w-full"
                :class="{ 'is-active': expandedIds[section.id] }"
                @click="emit('toggle-expand', section)"
            >
                <IconOpenBook
                    v-if="expandedIds[section.id]"
                    class="h-4 w-4 shrink-0 text-cyan-600"
                />
                <IconBook v-else class="h-4 w-4 shrink-0 text-cyan-600" />
                <span class="min-w-0 flex-1 truncate text-left">{{ section.title }}</span>
                <span v-if="section.pages_count" class="shrink-0 text-xs text-slate-400">
                    {{ section.pages_count }}
                </span>
            </button>

            <ul
                v-if="expandedIds[section.id]"
                class="bib-reader-index-level-pages mt-0.5 space-y-0.5"
            >
                <li
                    v-if="pagesCache[section.id]?.loading"
                    class="flex items-center gap-2 py-2 text-xs text-slate-400"
                    :style="{ paddingLeft: contentIndent }"
                >
                    <IconLoader class="h-4 w-4 animate-spin" />
                    Cargando contenidos...
                </li>

                <template v-for="page in pagesCache[section.id]?.items ?? []" :key="page.id">
                    <li class="list-none">
                        <button
                            type="button"
                            class="bib-reader-index-btn bib-reader-index-page w-full"
                            :class="{ 'is-selected': selectedPageId === page.id && !selectedCaseId }"
                            :style="{ paddingLeft: contentIndent }"
                            @click="emit('select-page', page)"
                        >
                            <IconFile class="h-3.5 w-3.5 shrink-0 text-slate-400" />
                            <span class="min-w-0 flex-1 truncate text-left text-sm">
                                Contenido {{ page.page_number }}
                                <span
                                    v-if="page.preview && page.preview !== '(vacío)'"
                                    class="text-slate-400"
                                >
                                    — {{ page.preview }}
                                </span>
                            </span>
                        </button>
                    </li>
                    <li
                        v-for="caseItem in page.practical_cases ?? []"
                        :key="'case-' + caseItem.id"
                        class="list-none"
                    >
                        <button
                            type="button"
                            class="bib-reader-index-btn bib-reader-index-case w-full"
                            :class="{
                                'is-selected': selectedCaseId === caseItem.id,
                            }"
                            :style="{ paddingLeft: caseIndent }"
                            @click="emit('select-case', { page, caseItem })"
                        >
                            <span class="bib-reader-index-case__dot" aria-hidden="true" />
                            <span class="min-w-0 flex-1 truncate text-left text-sm">
                                {{ caseItem.title }}
                            </span>
                        </button>
                    </li>
                </template>
            </ul>
        </li>
    </ul>
</template>
