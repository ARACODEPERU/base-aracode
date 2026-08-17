<script setup>
import { ref, computed, onBeforeUnmount } from 'vue';
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import Swal2 from 'sweetalert2';

const props = defineProps({
    edition: { type: Object, default: () => ({}) },
    matches: { type: Array, default: () => [] },
    media: { type: Array, default: () => [] },
});

// ------- Estado de subida -------
const selectedDate = ref('');
const selectedMatchId = ref('');
const files = ref([]);
const uploading = ref(false);
const dragOver = ref(false);
const fileInput = ref(null);

// Agrupamos los partidos disponibles por fecha para el selector
const matchesByDate = computed(() => {
    const map = {};
    for (const group of props.matches) {
        if (!map[group.date]) map[group.date] = [];
        for (const m of group.matches) map[group.date].push(m);
    }
    return map;
});

const availableDates = computed(() => props.matches.map((g) => g.date));

// Fechas ordenadas con su etiqueta
const dateOptions = computed(() =>
    props.matches.map((g) => ({ value: g.date, label: g.label }))
);

const availableMatchesForDate = computed(() => {
    if (!selectedDate.value) return [];
    return matchesByDate.value[selectedDate.value] || [];
});

const canUpload = computed(() => selectedDate.value && files.value.length > 0);

// ------- Selección de archivos -------
const MAX_VIDEO_SECONDS = 60;

const selectFiles = (fileList) => {
    const incoming = Array.from(fileList || []);
    const valid = [];
    const rejected = [];

    for (const file of incoming) {
        if (file.type.startsWith('image/')) {
            valid.push(file);
            continue;
        }

        if (file.type.startsWith('video/')) {
            // Marcamos para validar duración luego (al cargar metadata)
            valid.push(file);
            continue;
        }

        rejected.push(`${file.name} (solo fotos o videos)`);
    }

    if (rejected.length) {
        Swal2.fire({
            title: 'Archivos no permitidos',
            text: rejected.join(' · '),
            icon: 'warning',
            confirmButtonText: 'Entendido',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    }

    files.value = [...files.value, ...valid];
    validateVideosDuration();
};

const validateVideosDuration = async () => {
    for (const file of files.value) {
        if (!file.type.startsWith('video/') || file.__durationChecked) continue;

        file.__durationChecked = true;

        const duration = await readVideoDuration(file);

        if (duration !== null && duration > MAX_VIDEO_SECONDS) {
            file.__invalid = true;
            file.__invalidReason = `El video dura ${Math.round(duration)}s (máximo ${MAX_VIDEO_SECONDS}s).`;
        }
    }
};

const readVideoDuration = (file) =>
    new Promise((resolve) => {
        const url = URL.createObjectURL(file);
        const video = document.createElement('video');
        video.preload = 'metadata';
        video.onloadedmetadata = () => {
            URL.revokeObjectURL(url);
            resolve(video.duration);
        };
        video.onerror = () => {
            URL.revokeObjectURL(url);
            resolve(null);
        };
        video.src = url;
    });

const previewUrl = (file) => URL.createObjectURL(file);

const removeFile = (index) => {
    files.value.splice(index, 1);
};

const clearFiles = () => {
    files.value = [];
    selectedMatchId.value = '';
};

const onDrop = (event) => {
    dragOver.value = false;
    selectFiles(event.dataTransfer?.files);
};

// ------- Subida -------
const upload = async () => {
    if (!canUpload.value || uploading.value) return;

    const invalid = files.value.filter((f) => f.__invalid);
    if (invalid.length) {
        Swal2.fire({
            title: 'Videos demasiado largos',
            text: 'Revisa los archivos marcados en rojo. Los videos deben durar máximo 1 minuto.',
            icon: 'warning',
            confirmButtonText: 'Entendido',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
        return;
    }

    uploading.value = true;

    const formData = new FormData();
    formData.append('media_date', selectedDate.value);
    if (selectedMatchId.value) formData.append('match_id', selectedMatchId.value);
    files.value.forEach((file) => formData.append('files[]', file));

    try {
        const res = await axios.post(
            route('even_ediciones_galeria_store', props.edition.id),
            formData,
            { headers: { 'Content-Type': 'multipart/form-data' } }
        );

        Swal2.fire({
            title: '¡Listo!',
            text: res.data?.message || 'Archivos subidos correctamente.',
            icon: res.data?.success ? 'success' : 'warning',
            confirmButtonText: 'Perfecto',
            padding: '2em',
            customClass: 'sweet-alerts',
        });

        clearFiles();
        refreshMedia();
    } catch (error) {
        const message =
            error.response?.data?.message ||
            error.response?.data?.errors?.media_date?.[0] ||
            error.message ||
            'No se pudieron subir los archivos.';
        Swal2.fire({
            title: 'Error',
            text: message,
            icon: 'error',
            confirmButtonText: 'Entendido',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    } finally {
        uploading.value = false;
    }
};

const refreshMedia = () => {
    // Recarga la página para mostrar los nuevos archivos
    window.location.reload();
};

// ------- Galería existente -------
const groupedMedia = computed(() => {
    const groups = {};
    for (const item of props.media) {
        if (!groups[item.media_date]) {
            groups[item.media_date] = { label: item.media_date_label, items: [] };
        }
        groups[item.media_date].items.push(item);
    }
    return Object.values(groups);
});

const destroyMedia = async (item) => {
    const result = await Swal2.fire({
        title: '¿Eliminar este archivo?',
        text: 'Se eliminará de la galería y de la landing. Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        padding: '2em',
        customClass: 'sweet-alerts',
    });

    if (!result.isConfirmed) return;

    try {
        await axios.delete(route('even_ediciones_galeria_destroy', [props.edition.id, item.id]));
        Swal2.fire({
            title: 'Eliminado',
            text: 'El archivo fue eliminado.',
            icon: 'success',
            timer: 1800,
            showConfirmButton: false,
            padding: '2em',
            customClass: 'sweet-alerts',
        });
        refreshMedia();
    } catch {
        Swal2.fire({
            title: 'Error',
            text: 'No se pudo eliminar el archivo.',
            icon: 'error',
            confirmButtonText: 'Entendido',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    }
};

const openMedia = (item) => {
    window.open(item.url, '_blank');
};

onBeforeUnmount(() => {
    files.value.forEach((file) => {
        if (file.__previewUrl) URL.revokeObjectURL(file.__previewUrl);
    });
});
</script>

<template>
    <AppLayout :title="`Galería - ${edition.name}`">
        <Navigation :routeModule="route('even_dashboard')" :titleModule="'Eventos sociales'"
            :data="[
                {route: route('even_ediciones_listado'), title: 'Ediciones'},
                {title: 'Galería de fotos'}
            ]"
        />

        <div class="mt-6 space-y-6">
            <!-- Encabezado -->
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h2 class="text-xl font-semibold dark:text-white">Galería de fotos y videos</h2>
                    <p class="text-sm text-gray-500">
                        {{ edition.name }} · {{ edition.evento || 'Evento' }}
                    </p>
                    <p class="mt-1 text-sm text-gray-500">
                        Sube fotos o videos cortos (máximo 1 minuto) por fecha y partido. Se mostrarán en la landing del torneo.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a
                        v-if="edition.landing_published"
                        :href="edition.landing_url"
                        target="_blank"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition-colors"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                        Ver landing
                    </a>
                    <a
                        :href="route('even_ediciones_listado')"
                        class="inline-flex items-center px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-md hover:bg-gray-700 transition-colors"
                    >
                        Volver a ediciones
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Columna: Subir archivos -->
                <div class="lg:col-span-1">
                    <div class="panel p-6">
                        <h3 class="mb-1 font-semibold dark:text-white">Subir fotos y videos</h3>
                        <p class="mb-5 text-sm text-gray-500">Sigue los 3 pasos para publicar tus archivos.</p>

                        <!-- Paso 1: Fecha -->
                        <div class="mb-5">
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                <span class="mr-1 inline-flex h-5 w-5 items-center justify-center rounded-full bg-primary text-xs font-bold text-white">1</span>
                                Elige la fecha
                            </label>
                            <select
                                v-model="selectedDate"
                                class="form-select w-full"
                            >
                                <option value="">Selecciona una fecha...</option>
                                <option v-for="opt in dateOptions" :key="opt.value" :value="opt.value">
                                    {{ opt.label }}
                                </option>
                            </select>
                            <p v-if="!availableDates.length" class="mt-2 text-xs text-amber-600">
                                Aún no hay partidos con fecha programada en esta edición.
                            </p>
                        </div>

                        <!-- Paso 2: Partido (opcional) -->
                        <div class="mb-5">
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                <span class="mr-1 inline-flex h-5 w-5 items-center justify-center rounded-full bg-primary text-xs font-bold text-white">2</span>
                                Elige el partido
                                <span class="text-xs font-normal text-gray-400">(opcional)</span>
                            </label>
                            <select
                                v-model="selectedMatchId"
                                :disabled="!selectedDate"
                                class="form-select w-full"
                            >
                                <option value="">Sin partido específico</option>
                                <option
                                    v-for="match in availableMatchesForDate"
                                    :key="match.id"
                                    :value="match.id"
                                >
                                    {{ match.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Paso 3: Archivos -->
                        <div class="mb-5">
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                <span class="mr-1 inline-flex h-5 w-5 items-center justify-center rounded-full bg-primary text-xs font-bold text-white">3</span>
                                Sube las fotos y videos
                            </label>

                            <div
                                class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed p-6 text-center transition-colors cursor-pointer"
                                :class="dragOver ? 'border-primary bg-primary/10' : 'border-gray-300 dark:border-gray-600'"
                                @dragover.prevent="dragOver = true"
                                @dragleave="dragOver = false"
                                @drop.prevent="onDrop"
                                @click="fileInput?.click()"
                            >
                                <svg class="mb-2 h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Arrastra tus archivos aquí
                                </p>
                                <p class="mt-1 text-xs text-gray-400">
                                    o haz clic para elegir · Fotos y videos (máx. 1 min)
                                </p>
                                <input
                                    ref="fileInput"
                                    type="file"
                                    accept="image/*,video/*"
                                    multiple
                                    class="hidden"
                                    @change="selectFiles($event.target.files); $event.target.value = ''"
                                />
                            </div>

                            <!-- Archivos seleccionados -->
                            <div v-if="files.length" class="mt-3 space-y-2">
                                <div
                                    v-for="(file, index) in files"
                                    :key="index"
                                    class="flex items-center gap-3 rounded-md border p-2"
                                    :class="file.__invalid ? 'border-red-400 bg-red-50 dark:bg-red-900/20' : 'border-gray-200 dark:border-gray-700'"
                                >
                                    <div class="h-12 w-12 shrink-0 overflow-hidden rounded-md bg-gray-100 dark:bg-gray-800">
                                        <img
                                            v-if="file.type.startsWith('image/')"
                                            :src="previewUrl(file)"
                                            class="h-full w-full object-cover"
                                            alt=""
                                        />
                                        <video
                                            v-else-if="file.type.startsWith('video/')"
                                            :src="previewUrl(file)"
                                            class="h-full w-full object-cover"
                                            muted
                                            preload="metadata"
                                        ></video>
                                        <div v-else class="flex h-full items-center justify-center text-gray-400">
                                            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M9 3v4M15 3v4M4 7h16v13a1 1 0 01-1 1H5a1 1 0 01-1-1V7z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-gray-700 dark:text-gray-300">{{ file.name }}</p>
                                        <p v-if="file.__invalid" class="text-xs font-semibold text-red-600">{{ file.__invalidReason }}</p>
                                        <p v-else class="text-xs text-gray-400">
                                            {{ file.type.startsWith('video/') ? 'Video' : 'Foto' }} · {{ (file.size / 1024 / 1024).toFixed(1) }} MB
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        class="text-gray-400 hover:text-red-600"
                                        @click="removeFile(index)"
                                        title="Quitar archivo"
                                    >
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M6 6l12 12M6 18L18 6"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="btn btn-primary w-full"
                            :disabled="!canUpload || uploading"
                            @click="upload"
                        >
                            <svg v-if="uploading" class="mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                            <svg v-else class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M16 8l-4-4m0 0l-4 4m4-4v12"></path>
                            </svg>
                            {{ uploading ? 'Subiendo archivos...' : 'Subir archivos' }}
                        </button>
                    </div>
                </div>

                <!-- Columna: Galería existente -->
                <div class="lg:col-span-2">
                    <div class="panel p-6">
                        <h3 class="mb-1 font-semibold dark:text-white">Galería publicada</h3>
                        <p class="mb-5 text-sm text-gray-500">
                            {{ props.media.length }} archivo(s) organizados por fecha. Así se verán en la landing.
                        </p>

                        <div v-if="!props.media.length" class="py-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">Aún no hay archivos en la galería. Sube los primeros usando el panel de la izquierda.</p>
                        </div>

                        <div v-else class="space-y-8">
                            <div v-for="group in groupedMedia" :key="group.label">
                                <div class="mb-3 flex items-center gap-2">
                                    <span class="inline-flex h-2 w-2 rounded-full bg-primary"></span>
                                    <h4 class="font-semibold capitalize text-gray-800 dark:text-white">{{ group.label }}</h4>
                                    <span class="text-xs text-gray-400">({{ group.items.length }})</span>
                                </div>

                                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
                                    <div
                                        v-for="item in group.items"
                                        :key="item.id"
                                        class="group relative overflow-hidden rounded-lg border border-gray-200 bg-gray-100 dark:border-gray-700 dark:bg-gray-800"
                                    >
                                        <button
                                            type="button"
                                            class="block w-full cursor-pointer"
                                            @click="openMedia(item)"
                                        >
                                            <div class="aspect-square w-full overflow-hidden">
                                                <img
                                                    v-if="item.type === 'image'"
                                                    :src="item.url"
                                                    :alt="item.file_name"
                                                    class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                                    loading="lazy"
                                                />
                                                <video
                                                    v-else
                                                    :src="item.url"
                                                    class="h-full w-full object-cover"
                                                    preload="metadata"
                                                    muted
                                                    playsinline
                                                ></video>
                                            </div>
                                        </button>

                                        <!-- Overlay de video -->
                                        <div v-if="item.type === 'video'" class="pointer-events-none absolute inset-0 flex items-center justify-center">
                                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-black/50 text-white backdrop-blur">
                                                <svg class="ml-0.5 h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M8 5v14l11-7z"></path>
                                                </svg>
                                            </span>
                                        </div>

                                        <!-- Etiqueta del partido -->
                                        <div v-if="item.match" class="absolute left-2 top-2 rounded bg-black/60 px-2 py-0.5 text-xs font-medium text-white backdrop-blur">
                                            {{ item.match.label }}
                                        </div>

                                        <!-- Botón eliminar -->
                                        <button
                                            v-can="'even_ediciones_galeria_eliminar'"
                                            type="button"
                                            class="absolute right-2 top-2 flex h-7 w-7 items-center justify-center rounded-full bg-white/90 text-red-600 opacity-0 shadow transition-opacity hover:bg-white group-hover:opacity-100"
                                            title="Eliminar archivo"
                                            @click="destroyMedia(item)"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M9 3v4M15 3v4M5 7h14v14a1 1 0 01-1 1H6a1 1 0 01-1-1V7zM10 11v6M14 11v6"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
