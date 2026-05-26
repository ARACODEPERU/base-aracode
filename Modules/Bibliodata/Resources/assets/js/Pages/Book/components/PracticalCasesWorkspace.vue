<script setup>
import { computed, ref, watch } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import EditorAracode from '@/Components/EditorAracode.vue';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    pageId: { type: [Number, null], default: null },
    pageLabel: { type: String, default: '' },
    imageUploadUrl: { type: String, default: '' },
});

const emit = defineEmits(['close', 'count-changed']);

const loading = ref(false);
const saving = ref(false);
const deletingId = ref(null);
const cases = ref([]);
const selectedCaseKey = ref('new');
const selectedFile = ref(null);
const fileInputKey = ref(0);
const form = ref(emptyCase());
const snapshot = ref('');

function emptyCase(nextSort = 1) {
    return {
        id: null,
        title: '',
        type: 'editor',
        content_html: '',
        file_url: null,
        file_name: null,
        file_mime: null,
        sort_order: nextSort,
        status: true,
    };
}

const currentCase = computed(() => {
    if (selectedCaseKey.value === 'new') {
        return null;
    }

    return cases.value.find((item) => item.id === selectedCaseKey.value) || null;
});

const isNewCase = computed(() => selectedCaseKey.value === 'new');
const isImageType = computed(() => form.value.type === 'image');
const isDocumentType = computed(() => form.value.type === 'document');
const hasExistingFile = computed(() => !!form.value.file_url);
const currentCount = computed(() => cases.value.length);
const canDelete = computed(() => !isNewCase.value && !!currentCase.value);
const hasUnsavedChanges = computed(() => snapshot.value !== buildSnapshot() || !!selectedFile.value);
const hasSelectedReplacementFile = computed(() => !!selectedFile.value);

const csrfHeaders = () => ({
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
    },
});

function buildSnapshot() {
    return JSON.stringify({
        id: form.value.id,
        title: form.value.title,
        type: form.value.type,
        content_html: form.value.content_html,
        file_url: form.value.file_url,
        file_name: form.value.file_name,
        file_mime: form.value.file_mime,
        sort_order: Number(form.value.sort_order || 1),
        status: !!form.value.status,
    });
}

function resetFileInput() {
    selectedFile.value = null;
    fileInputKey.value += 1;
}

function sortCases(items) {
    return [...items].sort((first, second) => {
        const sortOrderDiff = Number(first.sort_order || 0) - Number(second.sort_order || 0);
        if (sortOrderDiff !== 0) {
            return sortOrderDiff;
        }

        return Number(first.id || 0) - Number(second.id || 0);
    });
}

function syncCasesCount() {
    emit('count-changed', cases.value.length);
}

function fillForm(sourceCase = null) {
    if (!sourceCase) {
        form.value = emptyCase(currentCount.value + 1);
        selectedCaseKey.value = 'new';
        resetFileInput();
        snapshot.value = buildSnapshot();
        return;
    }

    form.value = {
        id: sourceCase.id,
        title: sourceCase.title || '',
        type: sourceCase.type || 'editor',
        content_html: sourceCase.content_html || '',
        file_url: sourceCase.file_url || null,
        file_name: sourceCase.file_name || null,
        file_mime: sourceCase.file_mime || null,
        sort_order: sourceCase.sort_order || 1,
        status: sourceCase.status ?? true,
    };
    selectedCaseKey.value = sourceCase.id;
    resetFileInput();
    snapshot.value = buildSnapshot();
}

async function loadCases(preferredCaseId = null) {
    if (!props.pageId) {
        cases.value = [];
        fillForm();
        emit('count-changed', 0);
        return;
    }

    loading.value = true;
    try {
        const { data } = await axios.get(route('bib_books_practical_cases_index', props.pageId), csrfHeaders());
        if (data.success) {
            cases.value = sortCases(data.cases || []);
            syncCasesCount();

            const selected = preferredCaseId
                ? cases.value.find((item) => item.id === preferredCaseId)
                : currentCase.value && cases.value.find((item) => item.id === currentCase.value.id);

            if (selected) {
                fillForm(selected);
            } else if (cases.value.length) {
                fillForm(cases.value[0]);
            } else {
                fillForm();
            }
        }
    } catch (error) {
        await Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'No se pudieron cargar los casos prácticos.',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    } finally {
        loading.value = false;
    }
}

function onFileSelected(event) {
    const file = event.target.files?.[0] || null;
    selectedFile.value = file;

    if (!file) {
        return;
    }

    form.value.file_name = file.name;
    form.value.file_mime = file.type || null;

    if (form.value.type === 'image') {
        form.value.file_url = URL.createObjectURL(file);
    } else {
        form.value.file_url = null;
    }
}

function removeSelectedFile() {
    const activeCase = currentCase.value;
    resetFileInput();

    if (activeCase) {
        form.value.file_url = activeCase.file_url || null;
        form.value.file_name = activeCase.file_name || null;
        form.value.file_mime = activeCase.file_mime || null;
        return;
    }

    form.value.file_url = null;
    form.value.file_name = null;
    form.value.file_mime = null;
}

async function confirmDiscardOrSave(action) {
    if (!hasUnsavedChanges.value) {
        action();
        return true;
    }

    const result = await Swal.fire({
        title: '¿Guardar cambios?',
        text: 'Hay cambios sin guardar en el caso práctico actual.',
        icon: 'question',
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonText: 'Guardar',
        denyButtonText: 'Descartar',
        cancelButtonText: 'Cancelar',
        padding: '2em',
        customClass: 'sweet-alerts',
    });

    if (result.isDismissed) {
        return false;
    }

    if (result.isConfirmed) {
        const saved = await saveCurrentCase();
        if (!saved) {
            return false;
        }
    }

    action();
    return true;
}

async function openExistingCase(caseId) {
    const target = cases.value.find((item) => item.id === caseId);
    if (!target || target.id === selectedCaseKey.value) {
        return;
    }

    await confirmDiscardOrSave(() => fillForm(target));
}

async function startNewCase() {
    await confirmDiscardOrSave(() => fillForm());
}

async function closeWorkspace() {
    const allowed = await confirmDiscardOrSave(() => {
        emit('close');
    });

    return allowed;
}

function buildFormData() {
    const payload = new FormData();
    payload.append('title', form.value.title || '');
    payload.append('type', form.value.type);
    payload.append('sort_order', String(form.value.sort_order || 1));
    payload.append('status', form.value.status ? '1' : '0');

    if (form.value.type === 'editor') {
        payload.append('content_html', form.value.content_html || '');
    }

    if (selectedFile.value) {
        payload.append('file', selectedFile.value);
    }

    return payload;
}

function upsertLocalCase(savedCase) {
    if (!savedCase) {
        return;
    }

    const existingIndex = cases.value.findIndex((item) => item.id === savedCase.id);
    if (existingIndex >= 0) {
        cases.value.splice(existingIndex, 1, savedCase);
    } else {
        cases.value.push(savedCase);
    }

    cases.value = sortCases(cases.value);
    syncCasesCount();
    fillForm(savedCase);
}

function removeLocalCase(caseId) {
    const nextCases = cases.value.filter((item) => item.id !== caseId);
    cases.value = sortCases(nextCases);
    syncCasesCount();

    if (cases.value.length) {
        fillForm(cases.value[0]);
        return;
    }

    fillForm();
}

async function saveCurrentCase() {
    if (!props.pageId) {
        return false;
    }

    saving.value = true;
    try {
        const creating = isNewCase.value;
        const payload = buildFormData();
        let response;

        if (isNewCase.value) {
            response = await axios.post(
                route('bib_books_practical_cases_store', props.pageId),
                payload,
                {
                    ...csrfHeaders(),
                    headers: {
                        ...csrfHeaders().headers,
                        'Content-Type': 'multipart/form-data',
                    },
                }
            );
        } else {
            payload.append('_method', 'PATCH');
            response = await axios.post(
                route('bib_books_practical_cases_update', form.value.id),
                payload,
                {
                    ...csrfHeaders(),
                    headers: {
                        ...csrfHeaders().headers,
                        'Content-Type': 'multipart/form-data',
                    },
                }
            );
        }

        if (response.data?.success) {
            upsertLocalCase(response.data.case || null);
            await Swal.fire({
                icon: 'success',
                title: creating ? 'Caso registrado' : 'Caso actualizado',
                text: response.data?.message || (creating ? 'El caso práctico se registró correctamente.' : 'El caso práctico se actualizó correctamente.'),
                padding: '2em',
                customClass: 'sweet-alerts',
                timer: 1600,
                showConfirmButton: false,
            });
            return true;
        }

        return false;
    } catch (error) {
        await Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'No se pudo guardar el caso práctico.',
            padding: '2em',
            customClass: 'sweet-alerts',
        });

        return false;
    } finally {
        saving.value = false;
    }
}

async function deleteCurrentCase() {
    if (!currentCase.value) {
        return;
    }

    const result = await Swal.fire({
        title: '¿Eliminar caso práctico?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
        padding: '2em',
        customClass: 'sweet-alerts',
    });

    if (!result.isConfirmed) {
        return;
    }

    deletingId.value = currentCase.value.id;
    try {
        const { data } = await axios.delete(route('bib_books_practical_cases_destroy', currentCase.value.id), csrfHeaders());
        if (data.success) {
            removeLocalCase(currentCase.value.id);
        }
    } catch (error) {
        await Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'No se pudo eliminar el caso práctico.',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    } finally {
        deletingId.value = null;
    }
}

watch(
    () => props.show,
    async (visible) => {
        if (visible && props.pageId) {
            await loadCases();
        }
    }
);

watch(
    () => props.pageId,
    async (pageId, oldPageId) => {
        if (!props.show || pageId === oldPageId) {
            return;
        }

        if (pageId) {
            await loadCases();
        }
    }
);

watch(
    () => form.value.type,
    (nextType, previousType) => {
        if (!previousType || nextType === previousType) {
            return;
        }

        if (selectedFile.value) {
            removeSelectedFile();
        }

        if (nextType === 'editor') {
            return;
        }

        if (currentCase.value?.type === nextType) {
            form.value.file_url = currentCase.value.file_url || null;
            form.value.file_name = currentCase.value.file_name || null;
            form.value.file_mime = currentCase.value.file_mime || null;
            return;
        }

        form.value.file_url = null;
        form.value.file_name = null;
        form.value.file_mime = null;
    }
);
</script>

<template>
    <div
        v-if="show"
        class="absolute inset-0 z-30 flex flex-col bg-white dark:bg-zinc-900"
    >
        <div class="flex items-center justify-between gap-3 border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800/80">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-primary">Casos prácticos</p>
                <h3 class="truncate text-base font-semibold text-gray-800 dark:text-neutral-100">
                    {{ pageLabel || 'Página actual' }}
                </h3>
            </div>
            <div class="flex items-center gap-2">
                <span class="rounded-full bg-primary/10 px-2.5 py-1 text-xs font-semibold text-primary">
                    {{ currentCount }} registrado(s)
                </span>
                <button type="button" class="btn btn-outline-danger btn-sm" @click="closeWorkspace">
                    Cerrar
                </button>
            </div>
        </div>

        <div class="flex min-h-0 flex-1 flex-col lg:flex-row">
            <aside class="w-full shrink-0 overflow-y-auto border-b border-gray-200 bg-gray-50/70 dark:border-zinc-700 dark:bg-zinc-900/70 lg:w-80 lg:border-b-0 lg:border-r">
                <div class="flex items-center justify-between gap-2 px-4 py-3">
                    <div>
                        <p class="text-sm font-semibold text-gray-800 dark:text-neutral-100">Listado</p>
                        <p class="text-xs text-gray-500 dark:text-neutral-400">Ejemplos, ejercicios o archivos reales.</p>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" @click="startNewCase">
                        Nuevo
                    </button>
                </div>

                <div class="px-3 pb-3">
                    <div v-if="loading" class="flex items-center justify-center py-8 text-sm text-gray-500">
                        <IconLoader class="mr-2 h-5 w-5 animate-spin text-primary" />
                        Cargando casos...
                    </div>

                    <div v-else class="space-y-2">
                        <button
                            type="button"
                            class="w-full rounded-lg border border-dashed px-3 py-3 text-left transition"
                            :class="isNewCase ? 'border-primary bg-primary/5 text-primary' : 'border-gray-300 bg-white text-gray-600 hover:border-primary/40 hover:bg-primary/5 dark:border-zinc-700 dark:bg-zinc-800 dark:text-neutral-300'"
                            @click="startNewCase"
                        >
                            <p class="text-sm font-semibold">Nuevo caso práctico</p>
                            <p class="mt-1 text-xs opacity-80">Registrar un nuevo ejemplo para esta página.</p>
                        </button>

                        <button
                            v-for="caseItem in cases"
                            :key="caseItem.id"
                            type="button"
                            class="w-full rounded-lg border px-3 py-3 text-left transition"
                            :class="selectedCaseKey === caseItem.id ? 'border-primary bg-primary/5 text-primary' : 'border-gray-200 bg-white text-gray-700 hover:border-primary/40 hover:bg-primary/5 dark:border-zinc-700 dark:bg-zinc-800 dark:text-neutral-200'"
                            @click="openExistingCase(caseItem.id)"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold">{{ caseItem.title }}</p>
                                    <p class="mt-1 text-xs uppercase tracking-[0.1em] opacity-70">{{ caseItem.type }}</p>
                                </div>
                                <span
                                    class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                    :class="caseItem.status ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-slate-200 text-slate-700 dark:bg-zinc-700 dark:text-neutral-300'"
                                >
                                    {{ caseItem.status ? 'Visible' : 'Oculto' }}
                                </span>
                            </div>
                        </button>
                    </div>
                </div>
            </aside>

            <div class="flex min-h-0 flex-1 flex-col">
                <div class="border-b border-gray-200 px-4 py-3 dark:border-zinc-700">
                    <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-800 dark:text-neutral-100">
                                {{ isNewCase ? 'Nuevo caso práctico' : form.title || 'Editar caso práctico' }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-neutral-400">
                                Puedes registrar el caso con editor, imagen o documento adjunto.
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                v-if="canDelete"
                                type="button"
                                class="btn btn-outline-danger btn-sm"
                                :disabled="saving || deletingId === form.id"
                                @click="deleteCurrentCase"
                            >
                                <IconLoader v-if="deletingId === form.id" class="mr-1 h-4 w-4 animate-spin" />
                                Eliminar
                            </button>
                            <button
                                type="button"
                                class="btn btn-primary btn-sm"
                                :disabled="saving"
                                @click="saveCurrentCase"
                            >
                                <IconLoader v-if="saving" class="mr-1 h-4 w-4 animate-spin" />
                                Guardar caso
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-4">
                    <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                        <div class="xl:col-span-2 space-y-4">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-neutral-200">Título</label>
                                <input v-model="form.title" type="text" class="form-input" placeholder="Ejemplo: Caso real de aplicación" />
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-neutral-200">Tipo</label>
                                    <select v-model="form.type" class="form-select">
                                        <option value="editor">EditorAracode</option>
                                        <option value="image">Imagen</option>
                                        <option value="document">Documento</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-neutral-200">Orden</label>
                                    <input v-model.number="form.sort_order" type="number" min="1" class="form-input" />
                                </div>

                                <div class="flex items-end">
                                    <label class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 dark:border-zinc-700">
                                        <input v-model="form.status" type="checkbox" class="form-checkbox" />
                                        <span class="text-sm text-gray-700 dark:text-neutral-200">Visible</span>
                                    </label>
                                </div>
                            </div>

                            <div v-if="form.type === 'editor'" class="space-y-2">
                                <p class="text-sm font-medium text-gray-700 dark:text-neutral-200">Contenido del caso</p>
                                <EditorAracode
                                    :key="`practical-case-editor-${form.id || 'new'}-${form.type}`"
                                    v-model="form.content_html"
                                    minHeight="360px"
                                    placeholder="Redacta el caso práctico, ejemplo real o ejercicio práctico..."
                                    :imageUploadUrl="imageUploadUrl"
                                />
                            </div>

                            <div v-else class="space-y-3">
                                <div class="space-y-3">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-neutral-200">
                                        {{ isImageType ? 'Subir imagen' : 'Subir documento' }}
                                    </label>

                                    <input
                                        id="practical-case-file-input"
                                        :key="fileInputKey"
                                        type="file"
                                        class="sr-only"
                                        :accept="isImageType ? '.jpg,.jpeg,.png,.gif,.webp' : '.pdf,.doc,.docx,.xls,.xlsx'"
                                        @change="onFileSelected"
                                    />

                                    <label
                                        for="practical-case-file-input"
                                        class="group relative block cursor-pointer overflow-hidden rounded-2xl border border-dashed p-5 transition"
                                        :class="form.file_name
                                            ? 'border-primary/40 bg-primary/[0.04] shadow-sm dark:border-primary/40 dark:bg-primary/10'
                                            : 'border-gray-300 bg-gradient-to-br from-white to-gray-50 hover:border-primary/40 hover:bg-primary/[0.03] dark:border-zinc-700 dark:from-zinc-900 dark:to-zinc-800/80 dark:hover:border-primary/40 dark:hover:bg-primary/10'"
                                    >
                                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                            <div class="min-w-0">
                                                <div class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-primary/10 text-primary ring-1 ring-primary/15">
                                                    <span class="text-lg font-semibold">{{ isImageType ? 'IMG' : 'DOC' }}</span>
                                                </div>
                                                <p class="mt-3 text-sm font-semibold text-gray-800 dark:text-neutral-100">
                                                    {{ form.file_name ? 'Cambiar archivo seleccionado' : (isImageType ? 'Selecciona una imagen' : 'Selecciona un documento') }}
                                                </p>
                                                <p class="mt-1 text-xs leading-5 text-gray-500 dark:text-neutral-400">
                                                    <template v-if="isImageType">
                                                        Formatos permitidos: jpg, jpeg, png, gif, webp.
                                                    </template>
                                                    <template v-else>
                                                        Formatos permitidos: pdf, doc, docx, xls, xlsx.
                                                    </template>
                                                </p>
                                            </div>

                                            <div class="shrink-0">
                                                <span class="inline-flex items-center rounded-xl bg-gray-900 px-4 py-2 text-xs font-semibold uppercase tracking-[0.12em] text-white transition group-hover:bg-primary dark:bg-white dark:text-zinc-900 dark:group-hover:bg-primary dark:group-hover:text-white">
                                                    Explorar archivo
                                                </span>
                                            </div>
                                        </div>
                                    </label>
                                </div>

                                <div
                                    v-if="form.file_name"
                                    class="rounded-2xl border border-gray-200 bg-white/90 p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
                                >
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="flex min-w-0 items-start gap-3">
                                            <div class="mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-xs font-bold text-slate-700 dark:bg-zinc-800 dark:text-neutral-200">
                                                {{ isImageType ? 'IMG' : 'DOC' }}
                                            </div>
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <p class="truncate text-sm font-semibold text-gray-800 dark:text-neutral-100">{{ form.file_name }}</p>
                                                    <span
                                                        class="rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.12em]"
                                                        :class="hasSelectedReplacementFile
                                                            ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300'
                                                            : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300'"
                                                    >
                                                        {{ hasSelectedReplacementFile ? 'Nuevo archivo' : 'Actual' }}
                                                    </span>
                                                </div>
                                                <p class="mt-1 text-xs text-gray-500 dark:text-neutral-400">{{ form.file_mime || 'Archivo adjunto' }}</p>
                                            </div>
                                        </div>
                                        <button
                                            v-if="hasSelectedReplacementFile"
                                            type="button"
                                            class="btn btn-outline-danger btn-sm"
                                            @click="removeSelectedFile"
                                        >
                                            Quitar cambio
                                        </button>
                                    </div>
                                </div>

                                <div v-if="isImageType && form.file_url" class="flex justify-center rounded-lg border border-gray-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">
                                    <img :src="form.file_url" alt="Vista previa del caso práctico" class="mx-auto max-h-[320px] rounded-lg object-contain" />
                                </div>

                                <div v-else-if="isDocumentType && hasExistingFile" class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                                    <a
                                        :href="form.file_url"
                                        target="_blank"
                                        class="inline-flex items-center rounded-xl bg-primary/10 px-3 py-2 text-sm font-medium text-primary transition hover:bg-primary/15"
                                    >
                                        Abrir documento actual
                                    </a>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-gray-200 bg-white/90 p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end">
                                    <button
                                        v-if="canDelete"
                                        type="button"
                                        class="btn btn-outline-danger btn-sm"
                                        :disabled="saving || deletingId === form.id"
                                        @click="deleteCurrentCase"
                                    >
                                        <IconLoader v-if="deletingId === form.id" class="mr-1 h-4 w-4 animate-spin" />
                                        Eliminar
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-primary btn-sm"
                                        :disabled="saving"
                                        @click="saveCurrentCase"
                                    >
                                        <IconLoader v-if="saving" class="mr-1 h-4 w-4 animate-spin" />
                                        Guardar caso
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/60">
                                <p class="text-sm font-semibold text-gray-800 dark:text-neutral-100">Recomendación</p>
                                <p class="mt-2 text-sm text-gray-500 dark:text-neutral-400">
                                    Usa <strong>EditorAracode</strong> para ejemplos desarrollados y archivos para evidencias reales, plantillas o ejercicios anexos.
                                </p>
                            </div>

                            <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/60">
                                <p class="text-sm font-semibold text-gray-800 dark:text-neutral-100">Contexto preservado</p>
                                <p class="mt-2 text-sm text-gray-500 dark:text-neutral-400">
                                    Al cerrar este panel volverás exactamente a la misma página y al mismo índice del libro.
                                </p>
                            </div>

                            <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/60">
                                <p class="text-sm font-semibold text-gray-800 dark:text-neutral-100">Estado actual</p>
                                <p class="mt-2 text-sm text-gray-500 dark:text-neutral-400">
                                    {{ hasUnsavedChanges ? 'Hay cambios sin guardar en este caso práctico.' : 'No hay cambios pendientes en este caso práctico.' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
