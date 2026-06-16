<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import Keypad from '@/Components/Keypad.vue';
import SectionTree from './components/SectionTree.vue';
import SectionFormModal from './components/SectionFormModal.vue';
import PageToolbar from './components/PageToolbar.vue';
import PageEditorPanel from './components/PageEditorPanel.vue';
import BulkPagesModal from './components/BulkPagesModal.vue';
import PracticalCasesWorkspace from './components/PracticalCasesWorkspace.vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import { setCsrfToken, withCsrfPayload } from '@/utils/csrf';
import {
    useBookContentLabels,
    CONTENT_STRUCTURE_OPTIONS,
} from '../../composables/useBookContentLabels';

const props = defineProps({
    book: { type: Object, required: true },
    can_change_structure: { type: Boolean, default: true },
});

const page = usePage();

watch(
    () => page.props.csrf_token,
    (token) => {
        if (token) setCsrfToken(token);
    },
    { immediate: true }
);

const bookContentStructure = ref(props.book.content_structure || 'chapter_subchapter');
const canChangeStructure = ref(props.can_change_structure);
const structureSaving = ref(false);
const labels = useBookContentLabels(bookContentStructure);
const structureOptions = CONTENT_STRUCTURE_OPTIONS;

const sections = ref([]);
const totalPages = ref(0);
const treeLoading = ref(false);
const expandTrigger = ref(null);

const selectedSection = ref(null);
const selectedPageId = ref(null);
const sectionPagesCache = ref({});
const sectionTreeRef = ref(null);
const currentPage = ref(null);
const pageContent = ref('');
const pageLoading = ref(false);
const pageSaving = ref(false);
const showPracticalCasesWorkspace = ref(false);
const showBulkModal = ref(false);
const bulkProcessing = ref(false);

const showSectionModal = ref(false);
const sectionModalMode = ref('create');
const sectionModalParentId = ref(null);
const sectionModalEditId = ref(null);
const sectionModalIsSubsection = ref(false);
const sectionModalInitialTitle = ref('');
const sectionSaving = ref(false);

const selectedSectionPagesCount = computed(() => selectedSection.value?.pages_count ?? 0);

const findSectionPathIds = (list, targetId, path = []) => {
    for (const s of list) {
        if (s.id === targetId) return [...path, s.id];
        if (s.children?.length) {
            const found = findSectionPathIds(s.children, targetId, [...path, s.id]);
            if (found) return found;
        }
    }
    return null;
};

const activeFolderIds = computed(() => {
    const sid = currentPage.value?.section_id;
    if (!sid) return new Set();
    const path = findSectionPathIds(sections.value, sid);
    return new Set(path || []);
});

const pageLabel = computed(() => {
    if (!currentPage.value) return '';
    const title = selectedSection.value?.title || currentPage.value.section_title || '';
    return labels.formatPageHeader(title, currentPage.value.page_number);
});

const totalPagesLabel = computed(() => {
    const n = totalPages.value;
    const unit = labels.pageLabelPlural.value.toLowerCase();
    return `${n} ${unit} en total`;
});

const structureSelectHint = computed(() =>
    canChangeStructure.value
        ? 'Cambie el formato mientras no existan sub-capítulos.'
        : 'Elimine los sub-capítulos para poder cambiar el formato.'
);

const hasUnsavedChanges = () =>
    currentPage.value && pageContent.value !== (currentPage.value.content || '');

const confirmDiscardOrSave = async () => {
    if (!hasUnsavedChanges()) return true;
    const save = await Swal.fire({
        title: '¿Guardar cambios?',
        text: 'Hay cambios sin guardar en la página actual.',
        icon: 'question',
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonText: 'Guardar',
        denyButtonText: 'Descartar',
        cancelButtonText: 'Cancelar',
        padding: '2em',
        customClass: 'sweet-alerts',
    });
    if (save.isDismissed) return false;
    if (save.isConfirmed) {
        await saveCurrentPage();
    }
    return true;
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

const appendSectionLocal = (parentId, section) => {
    const node = {
        ...section,
        pages_count: section.pages_count ?? 0,
        children: section.children ?? [],
    };
    if (!parentId) {
        sections.value.push(node);
        return;
    }
    const parent = findSectionById(sections.value, parentId);
    if (parent) {
        if (!parent.children) parent.children = [];
        parent.children.push(node);
        expandTrigger.value = null;
        nextTick(() => {
            expandTrigger.value = parentId;
        });
    }
};

const countPagesInBranch = (section) => {
    let n = section.pages_count || 0;
    for (const child of section.children || []) {
        n += countPagesInBranch(child);
    }
    return n;
};

const updateSectionTitleLocal = (id, title) => {
    const sec = findSectionById(sections.value, id);
    if (sec) {
        sec.title = title;
        if (selectedSection.value?.id === id) {
            selectedSection.value = { ...selectedSection.value, title };
        }
    }
};

const removeSectionLocal = (id) => {
    const removeFrom = (list) => {
        const idx = list.findIndex((s) => s.id === id);
        if (idx >= 0) {
            list.splice(idx, 1);
            return true;
        }
        for (const s of list) {
            if (s.children?.length && removeFrom(s.children)) return true;
        }
        return false;
    };
    removeFrom(sections.value);
};

const bumpSectionPageCount = (sectionId, delta) => {
    const sec = findSectionById(sections.value, sectionId);
    if (sec) {
        sec.pages_count = Math.max(0, (sec.pages_count || 0) + delta);
        if (selectedSection.value?.id === sectionId) {
            selectedSection.value = { ...selectedSection.value, pages_count: sec.pages_count };
        }
    }
    totalPages.value = Math.max(0, totalPages.value + delta);
};

const invalidateSectionPagesCache = (sectionId) => {
    if (sectionPagesCache.value[sectionId]) {
        const { loading } = sectionPagesCache.value[sectionId];
        sectionPagesCache.value[sectionId] = { items: [], currentPage: 0, lastPage: 0, total: 0, loading: !!loading };
    }
};

const appendPageToCache = (sectionId, page) => {
    const prev = sectionPagesCache.value[sectionId];
    if (!prev?.items) return;
    sectionPagesCache.value[sectionId] = {
        ...prev,
        items: [...prev.items, page],
        total: (prev.total || prev.items.length) + 1,
    };
};

const removePageFromCache = (sectionId, pageId) => {
    const prev = sectionPagesCache.value[sectionId];
    if (!prev?.items) return;
    sectionPagesCache.value[sectionId] = {
        ...prev,
        items: prev.items.filter((p) => p.id !== pageId),
    };
};

const loadSectionPages = async (sectionId, page = 1) => {
    const prev = sectionPagesCache.value[sectionId] || {
        items: [],
        currentPage: 0,
        lastPage: 1,
        total: 0,
        loading: false,
    };
    if (prev.loading) return;
    sectionPagesCache.value[sectionId] = { ...prev, loading: true };
    try {
        const { data } = await axios.get(route('bib_books_pages_index', sectionId), {
            params: { page, per_page: 50 },
        });
        if (!data.success) return;
        const paginated = data.pages;
        const merged = page === 1 ? paginated.data : [...prev.items, ...paginated.data];
        sectionPagesCache.value[sectionId] = {
            items: merged,
            currentPage: paginated.current_page,
            lastPage: paginated.last_page,
            total: paginated.total,
            loading: false,
        };
    } catch {
        sectionPagesCache.value[sectionId] = { ...prev, loading: false };
    }
};

const expandAncestorsForSection = async (sectionId) => {
    const path = findSectionPathIds(sections.value, sectionId);
    if (!path) return;
    for (const id of path) {
        sectionTreeRef.value?.ensureExpanded(id);
        const sec = findSectionById(sections.value, id);
        if (sec?.pages_count > 0) {
            const cache = sectionPagesCache.value[id];
            if (!cache?.items?.length && !cache?.loading) {
                await loadSectionPages(id, 1);
            }
        }
    }
};

const onTreeExpandSection = async (section) => {
    if (section.pages_count > 0) {
        const cache = sectionPagesCache.value[section.id];
        if (!cache?.items?.length && !cache?.loading) {
            await loadSectionPages(section.id, 1);
        }
    }
};

const onLoadMorePages = (section) => {
    const cache = sectionPagesCache.value[section.id];
    const next = (cache?.currentPage ?? 0) + 1;
    if (cache && next <= cache.lastPage) {
        loadSectionPages(section.id, next);
    }
};

const loadTree = async () => {
    treeLoading.value = true;
    try {
        const { data } = await axios.get(route('bib_books_sections_tree', props.book.id));
        if (data.success) {
            sections.value = data.sections;
            totalPages.value = data.total_pages;
            if (data.content_structure) {
                bookContentStructure.value = data.content_structure;
            }
            if (typeof data.can_change_structure === 'boolean') {
                canChangeStructure.value = data.can_change_structure;
            }
        }
    } finally {
        treeLoading.value = false;
    }
};

const openSectionModal = (mode, { parentId = null, section = null } = {}) => {
    sectionModalMode.value = mode;
    sectionModalParentId.value = parentId;
    sectionModalEditId.value = section?.id ?? null;
    sectionModalIsSubsection.value = !!parentId && mode === 'create';
    sectionModalInitialTitle.value = section?.title ?? '';
    showSectionModal.value = true;
};

const closeSectionModal = () => {
    if (sectionSaving.value) return;
    showSectionModal.value = false;
};

const onSectionModalSubmit = async (title) => {
    sectionSaving.value = true;
    try {
        if (sectionModalMode.value === 'create') {
            const { data } = await axios.post(
                route('bib_books_sections_store'),
                {
                    book_id: props.book.id,
                    parent_id: sectionModalParentId.value,
                    title,
                }
            );
            if (data.success) {
                showSectionModal.value = false;
                appendSectionLocal(sectionModalParentId.value, data.section);
                await Swal.fire({
                    icon: 'success',
                    title: 'Enhorabuena',
                    text: sectionModalParentId.value
                        ? labels.subsectionCreatedMessage.value
                        : labels.rootCreatedMessage.value,
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
            }
        } else {
            const { data } = await axios.patch(
                route('bib_books_sections_update', sectionModalEditId.value),
                { title }
            );
            if (data.success) {
                showSectionModal.value = false;
                updateSectionTitleLocal(sectionModalEditId.value, title);
                await Swal.fire({
                    icon: 'success',
                    title: 'Enhorabuena',
                    text: 'Título actualizado correctamente',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
            }
        }
    } catch (e) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: e.response?.data?.message || 'No se pudo guardar',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    } finally {
        sectionSaving.value = false;
    }
};

const loadPageById = async (pageId) => {
    pageLoading.value = true;
    try {
        const { data } = await axios.get(route('bib_books_pages_show', pageId));
        if (data.success && data.page) {
            currentPage.value = data.page;
            pageContent.value = data.page.content || '';
            selectedPageId.value = data.page.id;
            selectedSection.value = findSectionById(sections.value, data.page.section_id);
            return;
        }
        Swal.fire({
            icon: 'error',
            title: 'No se pudo cargar la página',
            text: data?.message || 'Respuesta inválida del servidor',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    } catch (e) {
        Swal.fire({
            icon: 'error',
            title: 'No se pudo cargar la página',
            text: e.response?.data?.message || e.message || 'Revise la consola o ejecute las migraciones pendientes.',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    } finally {
        pageLoading.value = false;
    }
};

const selectPage = async (pageSummary) => {
    if (selectedPageId.value === pageSummary.id && currentPage.value) return;
    if (!(await confirmDiscardOrSave())) return;

    await expandAncestorsForSection(pageSummary.section_id);
    await loadPageById(pageSummary.id);
};

const saveCurrentPage = async () => {
    if (!currentPage.value?.id) return;
    pageSaving.value = true;
    try {
        const { data } = await axios.patch(
            route('bib_books_pages_update', currentPage.value.id),
            { content: pageContent.value }
        );
        if (data.success) {
            currentPage.value = data.page;
            pageContent.value = data.page.content || '';
            const cached = sectionPagesCache.value[data.page.section_id];
            if (cached?.items) {
                sectionPagesCache.value[data.page.section_id] = {
                    ...cached,
                    items: cached.items.map((p) => (p.id === data.page.id ? { ...p, preview: data.page.preview } : p)),
                };
            }
        }
    } catch (e) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: e.response?.data?.message || 'No se pudo guardar',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    } finally {
        pageSaving.value = false;
    }
};

const openPracticalCasesWorkspace = () => {
    if (!currentPage.value?.id) return;
    showPracticalCasesWorkspace.value = true;
};

const closePracticalCasesWorkspace = () => {
    showPracticalCasesWorkspace.value = false;
};

const onPracticalCasesCountChanged = (count) => {
    if (currentPage.value) {
        currentPage.value = {
            ...currentPage.value,
            practical_cases_count: count,
        };
    }
};

const addChapter = (parentId = null) => {
    if (labels.isLevelContent.value && parentId) {
        return;
    }
    openSectionModal('create', { parentId });
};

const onContentStructureChange = async (event) => {
    const newValue = event.target.value;
    if (newValue === bookContentStructure.value) return;

    if (!canChangeStructure.value) {
        event.target.value = bookContentStructure.value;
        return;
    }

    const opt = structureOptions.find((o) => o.value === newValue);
    const result = await Swal.fire({
        title: '¿Cambiar formato de contenido?',
        html: `Se aplicará: <strong>${opt?.label ?? newValue}</strong>. Las secciones y contenidos existentes se mantienen.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Cambiar',
        cancelButtonText: 'Cancelar',
        padding: '2em',
        customClass: 'sweet-alerts',
    });

    if (!result.isConfirmed) {
        event.target.value = bookContentStructure.value;
        return;
    }

    structureSaving.value = true;
    try {
        const { data } = await axios.patch(
            route('bib_books_content_structure', props.book.id),
            { content_structure: newValue }
        );
        if (data.success) {
            bookContentStructure.value = data.book.content_structure;
            canChangeStructure.value = data.can_change_structure;
            await loadTree();
            await Swal.fire({
                icon: 'success',
                title: 'Formato actualizado',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        }
    } catch (e) {
        event.target.value = bookContentStructure.value;
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: e.response?.data?.message || 'No se pudo cambiar el formato',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    } finally {
        structureSaving.value = false;
    }
};

const editSection = (section) => {
    openSectionModal('edit', { section });
};

const deleteSection = (section) => {
    Swal.fire({
        title: '¿Eliminar sección?',
        text: 'Se eliminarán también sus sub-secciones y páginas.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Eliminar',
        padding: '2em',
        customClass: 'sweet-alerts',
    }).then(async (result) => {
        if (!result.isConfirmed) return;
        try {
            await axios.delete(route('bib_books_sections_destroy', section.id));
            const pagesRemoved = countPagesInBranch(section);
            removeSectionLocal(section.id);
            totalPages.value = Math.max(0, totalPages.value - pagesRemoved);
            const removedIds = new Set([section.id, ...(section.children || []).map((c) => c.id)]);
            removedIds.forEach((id) => delete sectionPagesCache.value[id]);
            if (currentPage.value && removedIds.has(currentPage.value.section_id)) {
                selectedSection.value = null;
                selectedPageId.value = null;
                currentPage.value = null;
                pageContent.value = '';
                showPracticalCasesWorkspace.value = false;
            }
            Swal.fire({
                icon: 'success',
                title: 'Eliminado',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        } catch (e) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: e.response?.data?.message || 'No se pudo eliminar',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        }
    });
};

const addSinglePage = async (section) => {
    const target = section || selectedSection.value;
    if (!target) return;
    pageLoading.value = true;
    try {
        const { data } = await axios.post(route('bib_books_pages_store', target.id), withCsrfPayload({ content: '' }));
        if (data.success) {
            bumpSectionPageCount(target.id, 1);
            expandTrigger.value = null;
            nextTick(() => {
                expandTrigger.value = target.id;
                sectionTreeRef.value?.ensureExpanded(target.id);
            });
            if (sectionPagesCache.value[target.id]?.items) {
                appendPageToCache(target.id, {
                    id: data.page.id,
                    section_id: data.page.section_id,
                    page_number: data.page.page_number,
                    preview: data.page.preview ?? '(vacío)',
                    has_content: false,
                });
            } else {
                await loadSectionPages(target.id, 1);
            }
            await selectPage({
                id: data.page.id,
                section_id: data.page.section_id,
                page_number: data.page.page_number,
            });
        }
    } catch (e) {
        const status = e.response?.status;
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: status === 419
                ? 'La sesión expiró. Recargue la página e intente de nuevo.'
                : (e.response?.data?.message || 'No se pudo crear la página'),
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    } finally {
        pageLoading.value = false;
    }
};

const openBulkForSection = (section) => {
    selectedSection.value = section;
    showBulkModal.value = true;
};

const bulkSubmit = async (payload) => {
    if (!selectedSection.value) return;
    bulkProcessing.value = true;
    try {
        const { data } = await axios.post(
            route('bib_books_pages_bulk', selectedSection.value.id),
            payload
        );
        if (data.success) {
            showBulkModal.value = false;
            const sid = selectedSection.value.id;
            bumpSectionPageCount(sid, data.created ?? 0);
            invalidateSectionPagesCache(sid);
            expandTrigger.value = sid;
            sectionTreeRef.value?.ensureExpanded(sid);
            await loadSectionPages(sid, 1);
            await Swal.fire({
                icon: 'success',
                title: 'Listo',
                text: data.message,
                padding: '2em',
                customClass: 'sweet-alerts',
            });
            const cache = sectionPagesCache.value[sid];
            const first = cache?.items?.[0];
            if (first) {
                await selectPage(first);
            }
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message,
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        }
    } catch (e) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: e.response?.data?.message || 'No se pudieron crear las páginas',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    } finally {
        bulkProcessing.value = false;
    }
};


onMounted(() => {
    loadTree();
});
</script>

<template>
    <AppLayout :title="'Contenido: ' + book.title">
        <Navigation
            :routeModule="route('bib_dashboard')"
            :titleModule="'Biblio Data'"
            :data="[
                { title: 'Libros', route: route('bib_books') },
                { title: book.title, route: route('bib_books_edit', book.id) },
                { title: 'Contenido' },
            ]"
        />

        <div class="pt-5">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-neutral-200">{{ book.title }}</h2>
                    <p class="text-sm text-gray-500">
                        {{ book.author?.person?.formatted_name || book.author?.person?.full_name || 'Sin autor' }}
                        · {{ totalPagesLabel }}
                    </p>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <label class="text-xs font-medium text-gray-600 dark:text-gray-400 shrink-0">
                            Formato:
                        </label>
                        <select
                            :value="bookContentStructure"
                            class="form-select form-select-sm text-xs py-1 max-w-md"
                            :disabled="!canChangeStructure || structureSaving"
                            v-tippy="{ content: structureSelectHint, placement: 'bottom' }"
                            @change="onContentStructureChange"
                        >
                            <option
                                v-for="opt in structureOptions"
                                :key="opt.value"
                                :value="opt.value"
                            >
                                {{ opt.label }}
                            </option>
                        </select>
                        <span
                            v-if="structureSaving"
                            class="text-xs text-gray-400"
                        >Guardando...</span>
                    </div>
                </div>
                <Keypad>
                    <template #botones>
                        <Link :href="route('bib_books_edit', book.id)" class="btn btn-outline-primary text-xs uppercase">
                            Editar metadatos
                        </Link>
                        <Link :href="route('bib_books')" class="btn btn-outline-danger text-xs uppercase">
                            Volver al listado
                        </Link>
                    </template>
                </Keypad>
            </div>

            <div
                class="panel relative p-0 overflow-hidden flex flex-col lg:flex-row min-h-[calc(100vh-220px)] border border-gray-200 dark:border-zinc-700 box-border"
            >
                <aside
                    class="w-full lg:w-80 shrink-0 border-b lg:border-b-0 lg:border-r border-gray-200 dark:border-zinc-700 flex flex-col min-h-0 max-h-[320px] lg:max-h-none box-border"
                >
                    <SectionTree
                        ref="sectionTreeRef"
                        :sections="sections"
                        :content-structure="bookContentStructure"
                        :selected-page-id="selectedPageId"
                        :active-folder-ids="activeFolderIds"
                        :section-pages-cache="sectionPagesCache"
                        :loading="treeLoading"
                        :expand-trigger="expandTrigger"
                        @toggle-expand="onTreeExpandSection"
                        @select-page="selectPage"
                        @load-more-pages="onLoadMorePages"
                        @add-chapter="addChapter()"
                        @add-subsection="(s) => addChapter(s.id)"
                        @edit-section="editSection"
                        @delete-section="deleteSection"
                        @add-page="addSinglePage"
                        @bulk-pages="openBulkForSection"
                    />
                </aside>

                <main class="flex-1 flex flex-col min-w-0 min-h-0 box-border">
                    <PageToolbar
                        :page-label="pageLabel"
                        :has-page="!!currentPage"
                        :saving="pageSaving"
                        :loading="pageLoading"
                        :practical-cases-count="currentPage?.practical_cases_count || 0"
                        :select-page-hint="`Seleccione un ${labels.pageLabelSingular.value.toLowerCase()} del árbol`"
                        @save="saveCurrentPage"
                        @open-practical-cases="openPracticalCasesWorkspace"
                    />
                    <PageEditorPanel
                        v-model:content="pageContent"
                        :loading="pageLoading"
                        :page-label="pageLabel"
                        :image-upload-url="route('bib_books_upload_image')"
                        :empty-hint="labels.isLevelContent.value
                            ? 'Expanda un nivel y seleccione un contenido del árbol para editar.'
                            : 'Expanda un capítulo y seleccione una página del árbol para editar su contenido.'"
                        :editor-placeholder="`Escribe el contenido del ${labels.pageLabelSingular.value.toLowerCase()}...`"
                    />
                </main>

                <PracticalCasesWorkspace
                    :show="showPracticalCasesWorkspace"
                    :page-id="currentPage?.id || null"
                    :page-label="pageLabel"
                    :image-upload-url="route('bib_books_upload_image')"
                    @close="closePracticalCasesWorkspace"
                    @count-changed="onPracticalCasesCountChanged"
                />
            </div>
        </div>

        <SectionFormModal
            :show="showSectionModal"
            :mode="sectionModalMode"
            :is-subsection="sectionModalIsSubsection"
            :initial-title="sectionModalInitialTitle"
            :saving="sectionSaving"
            :content-structure="bookContentStructure"
            @close="closeSectionModal"
            @submit="onSectionModalSubmit"
        />

        <BulkPagesModal
            :show="showBulkModal"
            :book-pages="book.pages || 0"
            :section-pages-count="selectedSectionPagesCount"
            :processing="bulkProcessing"
            :page-label-plural="labels.pageLabelPlural.value"
            :page-label-singular="labels.pageLabelSingular.value"
            @close="showBulkModal = false"
            @submit="bulkSubmit"
        />
    </AppLayout>
</template>
