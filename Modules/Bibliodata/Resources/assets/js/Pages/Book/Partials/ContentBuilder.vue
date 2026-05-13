<script setup>
import { ref } from 'vue';
import PageEditor from './PageEditor.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:modelValue']);

const sections = ref(props.modelValue.length ? JSON.parse(JSON.stringify(props.modelValue)) : []);

let tempIdCounter = 0;
const nextTempId = () => 'sec_' + (++tempIdCounter);

const sync = () => {
    emit('update:modelValue', JSON.parse(JSON.stringify(sections.value)));
};

const addSection = (parentId = null) => {
    const parent = parentId ? findSection(parentId) : null;
    const siblings = parentId ? (parent?.children || sections.value) : sections.value;
    const order = siblings.length;

    Swal.fire({
        title: parentId ? 'Nueva Sub-sección' : 'Nuevo Capítulo',
        input: 'text',
        inputLabel: 'Título',
        inputPlaceholder: parentId ? 'Ej: 1.1 Antecedentes' : 'Ej: Capítulo 1: Introducción',
        showCancelButton: true,
        confirmButtonText: 'Agregar',
        cancelButtonText: 'Cancelar',
        padding: '2em',
        customClass: 'sweet-alerts',
        preConfirm: (title) => {
            if (!title) { Swal.showValidationMessage('El título es requerido'); return; }
            return title;
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            const newSec = {
                temp_id: nextTempId(),
                title: result.value,
                parent_id: parentId,
                order: order,
                expanded: true,
                children: [],
                pages: [],
            };
            siblings.push(newSec);
            sync();
        }
    });
};

const findSection = (tempId, list = sections.value) => {
    for (const sec of list) {
        if (sec.temp_id === tempId) return sec;
        if (sec.children?.length) {
            const found = findSection(tempId, sec.children);
            if (found) return found;
        }
    }
    return null;
};

const removeSection = (tempId) => {
    const removeRecursive = (list) => {
        const idx = list.findIndex(s => s.temp_id === tempId);
        if (idx >= 0) {
            list.splice(idx, 1);
            return true;
        }
        for (const sec of list) {
            if (sec.children?.length && removeRecursive(sec.children)) return true;
        }
        return false;
    };

    Swal.fire({
        title: '¿Eliminar sección?',
        text: 'Se eliminarán también todas sus sub-secciones y páginas.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Eliminar',
        cancelButtonText: 'Cancelar',
        padding: '2em',
        customClass: 'sweet-alerts',
    }).then((result) => {
        if (result.isConfirmed) {
            removeRecursive(sections.value);
            sync();
        }
    });
};

const editSectionTitle = (sec) => {
    Swal.fire({
        title: 'Editar título',
        input: 'text',
        inputValue: sec.title,
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        cancelButtonText: 'Cancelar',
        padding: '2em',
        customClass: 'sweet-alerts',
        preConfirm: (title) => {
            if (!title) { Swal.showValidationMessage('El título es requerido'); return; }
            return title;
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            sec.title = result.value;
            sync();
        }
    });
};

const moveUp = (tempId, list = sections.value) => {
    const idx = list.findIndex(s => s.temp_id === tempId);
    if (idx > 0) {
        [list[idx - 1], list[idx]] = [list[idx], list[idx - 1]];
        list.forEach((s, i) => s.order = i);
        sync();
    } else {
        for (const sec of list) {
            if (sec.children?.length) {
                moveUp(tempId, sec.children);
                return;
            }
        }
    }
};

const moveDown = (tempId, list = sections.value) => {
    const idx = list.findIndex(s => s.temp_id === tempId);
    if (idx >= 0 && idx < list.length - 1) {
        [list[idx], list[idx + 1]] = [list[idx + 1], list[idx]];
        list.forEach((s, i) => s.order = i);
        sync();
    } else {
        for (const sec of list) {
            if (sec.children?.length) {
                moveDown(tempId, sec.children);
                return;
            }
        }
    }
};

const removePage = (sec, pageTempId) => {
    Swal.fire({
        title: '¿Eliminar página?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Eliminar',
        cancelButtonText: 'Cancelar',
        padding: '2em',
        customClass: 'sweet-alerts',
    }).then((result) => {
        if (result.isConfirmed) {
            const idx = sec.pages.findIndex(p => p.temp_id === pageTempId);
            if (idx >= 0) {
                sec.pages.splice(idx, 1);
                sec.pages.forEach((p, i) => p.page_number = i + 1);
                sync();
            }
        }
    });
};

const hasContent = () => sections.value.length > 0;

// Page Editor state
const showPageEditor = ref(false);
const editingSec = ref(null);
const editingPage = ref(null);
const pageEditorTitle = ref('');
const pageEditorContent = ref('');
const pageEditorSaving = ref(false);

const openPageEditor = (sec, page = null) => {
    editingSec.value = sec;
    if (page) {
        editingPage.value = page;
        pageEditorTitle.value = `Editando: ${sec.title} > Página ${page.page_number}`;
        pageEditorContent.value = page.content || '';
    } else {
        const pageNum = sec.pages.length + 1;
        const newPage = {
            temp_id: 'page_' + Date.now(),
            page_number: pageNum,
            content: '',
        };
        sec.pages.push(newPage);
        editingPage.value = newPage;
        pageEditorTitle.value = `Nueva página: ${sec.title} > Página ${pageNum}`;
        pageEditorContent.value = '';
    }
    showPageEditor.value = true;
};

const savePageContent = (content) => {
    if (editingPage.value) {
        editingPage.value.content = content;
        sync();
    }
    showPageEditor.value = false;
    editingSec.value = null;
    editingPage.value = null;
};

const closePageEditor = () => {
    showPageEditor.value = false;
    editingSec.value = null;
    editingPage.value = null;
};
</script>

<template>
    <div class="panel">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold flex items-center gap-2">
                <svg class="w-5 h-5 text-primary" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M40 48C26.7 48 16 58.7 16 72l0 48c0 13.3 10.7 24 24 24l88 0c13.3 0 24-10.7 24-24l0-48c0-13.3-10.7-24-24-24L40 48zM192 64c-17.7 0-32 14.3-32 32s14.3 32 32 32l288 0c17.7 0 32-14.3 32-32s-14.3-32-32-32L192 64zm0 160c-17.7 0-32 14.3-32 32s14.3 32 32 32l288 0c17.7 0 32-14.3 32-32s-14.3-32-32-32L192 224zm0 160c-17.7 0-32 14.3-32 32s14.3 32 32 32l288 0c17.7 0 32-14.3 32-32s-14.3-32-32-32L192 384zM16 232l0 48c0 13.3 10.7 24 24 24l88 0c13.3 0 24-10.7 24-24l0-48c0-13.3-10.7-24-24-24l-88 0c-13.3 0-24 10.7-24 24zm24 136c-13.3 0-24 10.7-24 24l0 48c0 13.3 10.7 24 24 24l88 0c13.3 0 24-10.7 24-24l0-48c0-13.3-10.7-24-24-24l-88 0z"/></svg>
                Contenido del Libro
            </h3>
            <div class="flex gap-2">
                <button @click="addSection(null)" type="button"
                    class="px-3 py-1.5 text-xs font-medium bg-primary text-white rounded-lg hover:bg-primary/90 transition flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M256 80c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 144L48 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l144 0 0 144c0 17.7 14.3 32 32 32s32-14.3 32-32l0-144 144 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-144 0L256 80z"/></svg>
                    Capítulo
                </button>
            </div>
        </div>

        <div v-if="!hasContent()" class="text-center py-8 text-gray-400 dark:text-gray-500">
            <p class="mb-2">Aún no hay capítulos agregados</p>
            <p class="text-xs">Haz clic en "Capítulo" para comenzar a estructurar el contenido del libro</p>
        </div>
        <div v-else class="space-y-2">
            <template v-for="sec in sections" :key="sec.temp_id">
                <!-- Sección de nivel 0 (capítulo) -->
                <div class="border border-gray-200 dark:border-zinc-700 rounded-lg overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-2.5 bg-gray-50 dark:bg-zinc-800/50 group">
                        <div class="flex items-center gap-2 min-w-0 flex-1">
                            <svg class="w-4 h-4 shrink-0 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M0 96C0 43 43 0 96 0l96 0 0 190.4c0 13.5 8.5 25.5 21.3 30.1c11.9 4.3 25.1 1.3 33.9-7.5L288 172l40.8 40.9c8.8 8.9 22 11.8 33.9 7.5c12.8-4.6 21.3-16.6 21.3-30.1L384 0l32 0c53 0 96 43 96 96l0 320c0 53-43 96-96 96L96 512c-53 0-96-43-96-96L0 96z"/></svg>
                            <span class="text-sm font-medium text-gray-800 dark:text-neutral-200 truncate">{{ sec.title }}</span>
                        </div>
                        <div class="flex items-center gap-1 lg:opacity-100 transition shrink-0">
                            <button @click="editSectionTitle(sec)" type="button" class="p-1 text-blue-500 hover:text-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded" title="Editar título">
                                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M471.6 21.7c-21.9-21.9-57.3-21.9-79.2 0L362.3 51.7l97.9 97.9 30.1-30.1c21.9-21.9 21.9-57.3 0-79.2L471.6 21.7zm-299.2 220c-6.1 6.1-10.8 13.6-13.5 21.9l-29.6 88.8c-2.9 8.6-.6 18.1 5.8 24.6s15.9 8.7 24.6 5.8l88.8-29.6c8.2-2.7 15.7-7.4 21.9-13.5L437.7 172.3 339.7 74.3 172.4 241.7zM96 64C43 64 0 107 0 160L0 416c0 53 43 96 96 96l256 0c53 0 96-43 96-96l0-96c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 96c0 17.7-14.3 32-32 32L96 448c-17.7 0-32-14.3-32-32l0-256c0-17.7 14.3-32 32-32l96 0c17.7 0 32-14.3 32-32s-14.3-32-32-32L96 64z"/></svg>
                            </button>
                            <button @click="moveUp(sec.temp_id)" type="button" class="p-1 text-gray-500 hover:text-gray-700 hover:bg-gray-200 dark:hover:bg-zinc-600 rounded" title="Subir">
                                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M233.4 105.4c12.5-12.5 32.8-12.5 45.3 0l192 192c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L256 173.3 86.6 342.6c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3l192-192z"/></svg>
                            </button>
                            <button @click="moveDown(sec.temp_id)" type="button" class="p-1 text-gray-500 hover:text-gray-700 hover:bg-gray-200 dark:hover:bg-zinc-600 rounded" title="Bajar">
                                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M233.4 406.6c12.5 12.5 32.8 12.5 45.3 0l192-192c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L256 338.7 86.6 169.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l192 192z"/></svg>
                            </button>
                            <button @click="removeSection(sec.temp_id)" type="button" class="p-1 text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/30 rounded" title="Eliminar">
                                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z"/></svg>
                            </button>
                            <button @click="addSection(sec.temp_id)" type="button" class="p-1 text-green-500 hover:text-green-700 hover:bg-green-50 dark:hover:bg-green-900/30 rounded" title="Agregar sub-sección">
                                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M256 80c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 144L48 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l144 0 0 144c0 17.7 14.3 32 32 32s32-14.3 32-32l0-144 144 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-144 0L256 80z"/></svg>
                            </button>
                            <button @click="openPageEditor(sec)" type="button" class="p-1 text-purple-500 hover:text-purple-700 hover:bg-purple-50 dark:hover:bg-purple-900/30 rounded" title="Agregar página">
                                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path fill="currentColor" d="M0 64C0 28.7 28.7 0 64 0H224V128c0 17.7 14.3 32 32 32H384V448c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V64zm384 64H256V0L384 128z"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Sub-secciones -->
                    <div v-if="sec.children?.length" class="ml-6 border-l-2 border-blue-200 dark:border-blue-800 pl-3 my-2 space-y-1">
                        <div v-for="child in sec.children" :key="child.temp_id"
                            class="flex items-center justify-between px-3 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-800/50 group">
                            <div class="flex items-center gap-2 min-w-0 flex-1">
                                <svg class="w-3.5 h-3.5 shrink-0 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M40 48C26.7 48 16 58.7 16 72l0 48c0 13.3 10.7 24 24 24l88 0c13.3 0 24-10.7 24-24l0-48c0-13.3-10.7-24-24-24L40 48zM192 64c-17.7 0-32 14.3-32 32s14.3 32 32 32l288 0c17.7 0 32-14.3 32-32s-14.3-32-32-32L192 64z"/></svg>
                                <span class="text-sm text-gray-700 dark:text-neutral-300 truncate">{{ child.title }}</span>
                            </div>
                            <div class="flex items-center gap-1 lg:opacity-100 transition shrink-0">
                                <button @click="editSectionTitle(child)" type="button" class="p-1 text-blue-500 hover:text-blue-700 rounded" title="Editar">
                                    <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M471.6 21.7c-21.9-21.9-57.3-21.9-79.2 0L362.3 51.7l97.9 97.9 30.1-30.1c21.9-21.9 21.9-57.3 0-79.2L471.6 21.7zm-299.2 220c-6.1 6.1-10.8 13.6-13.5 21.9l-29.6 88.8c-2.9 8.6-.6 18.1 5.8 24.6s15.9 8.7 24.6 5.8l88.8-29.6c8.2-2.7 15.7-7.4 21.9-13.5L437.7 172.3 339.7 74.3 172.4 241.7zM96 64C43 64 0 107 0 160L0 416c0 53 43 96 96 96l256 0c53 0 96-43 96-96l0-96c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 96c0 17.7-14.3 32-32 32L96 448c-17.7 0-32-14.3-32-32l0-256c0-17.7 14.3-32 32-32l96 0c17.7 0 32-14.3 32-32s-14.3-32-32-32L96 64z"/></svg>
                                </button>
                                <button @click="removeSection(child.temp_id)" type="button" class="p-1 text-red-500 hover:text-red-700 rounded" title="Eliminar">
                                    <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z"/></svg>
                                </button>
                                <button @click="openPageEditor(child)" type="button" class="p-1 text-purple-500 hover:text-purple-700 rounded" title="Página">
                                    <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path fill="currentColor" d="M0 64C0 28.7 28.7 0 64 0H224V128c0 17.7 14.3 32 32 32H384V448c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V64zm384 64H256V0L384 128z"/></svg>
                            </button>
                            </div>
                            <!-- Páginas de la sub-sección -->
                            <div v-if="child?.pages?.length" class="ml-6 space-y-1 my-1">
                                <div v-for="page in child.pages" :key="page.temp_id"
                                    class="flex items-start gap-2 px-3 py-1.5 rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-800/30 group">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-medium text-gray-400 shrink-0">Pág. {{ page.page_number }}</span>
                                            <span class="text-xs text-gray-600 dark:text-gray-400 line-clamp-1">{{ page.content ? page.content.substring(0, 80) + '...' : '(vacío)' }}</span>
                                        </div>
                                    </div>
                                     <div class="flex items-center gap-1 lg:opacity-100 transition shrink-0">
                                        <button @click="openPageEditor(child, page)" type="button" class="p-1.5 text-blue-500 hover:text-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded" title="Editar contenido">
                                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M471.6 21.7c-21.9-21.9-57.3-21.9-79.2 0L362.3 51.7l97.9 97.9 30.1-30.1c21.9-21.9 21.9-57.3 0-79.2L471.6 21.7zm-299.2 220c-6.1 6.1-10.8 13.6-13.5 21.9l-29.6 88.8c-2.9 8.6-.6 18.1 5.8 24.6s15.9 8.7 24.6 5.8l88.8-29.6c8.2-2.7 15.7-7.4 21.9-13.5L437.7 172.3 339.7 74.3 172.4 241.7z"/></svg>
                                        </button>
                                        <button @click="removePage(child, page.temp_id)" type="button" class="p-1.5 text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/30 rounded" title="Eliminar página">
                                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div v-if="!child?.pages?.length" class="ml-6 px-3 py-1 text-xs text-gray-400 italic">
                                Sin páginas aún. <button @click="openPageEditor(child)" type="button" class="text-primary hover:underline">Agregar página</button>
                            </div>
                        </div>
                    </div>

                    <!-- Páginas de la sección -->
                    <div v-if="sec.pages?.length" class="ml-6 space-y-1 mb-2">
                        <div v-for="page in sec.pages" :key="page.temp_id"
                            class="flex items-start gap-2 px-3 py-1.5 rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-800/30 group">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-medium text-gray-400 shrink-0">Pág. {{ page.page_number }}</span>
                                    <span class="text-xs text-gray-600 dark:text-gray-400 line-clamp-1">{{ page.content ? page.content.substring(0, 80) + '...' : '(vacío)' }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-1 lg:opacity-100 transition shrink-0">
                                <button @click="openPageEditor(sec, page)" type="button" class="p-1.5 text-blue-500 hover:text-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded" title="Editar contenido">
                                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M471.6 21.7c-21.9-21.9-57.3-21.9-79.2 0L362.3 51.7l97.9 97.9 30.1-30.1c21.9-21.9 21.9-57.3 0-79.2L471.6 21.7zm-299.2 220c-6.1 6.1-10.8 13.6-13.5 21.9l-29.6 88.8c-2.9 8.6-.6 18.1 5.8 24.6s15.9 8.7 24.6 5.8l88.8-29.6c8.2-2.7 15.7-7.4 21.9-13.5L437.7 172.3 339.7 74.3 172.4 241.7zM96 64C43 64 0 107 0 160L0 416c0 53 43 96 96 96l256 0c53 0 96-43 96-96l0-96c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 96c0 17.7-14.3 32-32 32L96 448c-17.7 0-32-14.3-32-32l0-256c0-17.7 14.3-32 32-32l96 0c17.7 0 32-14.3 32-32s-14.3-32-32-32L96 64z"/></svg>
                                </button>
                                <button @click="removePage(sec, page.temp_id)" type="button" class="p-1.5 text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/30 rounded" title="Eliminar página">
                                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Sin páginas -->
                    <div v-if="!sec.pages?.length && !sec.children?.length" class="px-4 py-2 text-xs text-gray-400 italic">
                        Sin páginas aún. <button @click="openPageEditor(sec)" type="button" class="text-primary hover:underline">Agregar página</button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <PageEditor
        :show="showPageEditor"
        :pageTitle="pageEditorTitle"
        :content="pageEditorContent"
        :saving="pageEditorSaving"
        :imageUploadUrl="route('bib_books_upload_image')"
        @close="closePageEditor"
        @save="savePageContent"
    />
</template>
