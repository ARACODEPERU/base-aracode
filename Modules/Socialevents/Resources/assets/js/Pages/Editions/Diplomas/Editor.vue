<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import Swal2 from 'sweetalert2';
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import DiplomaStage from './Partials/DiplomaStage.vue';
import DiplomaInspector from './Partials/DiplomaInspector.vue';
import DiplomaPreviewModal from './Partials/DiplomaPreviewModal.vue';
import {
    buildSquadItems,
    cloneLayout,
    createElement,
    elementLabel,
    resolveDiplomaTokens,
} from '../../../utils/diplomaLayout';
import { downloadPdf, downloadPng } from '../../../utils/diplomaRender';

/**
 * Editor del diploma.
 *
 * El servidor entrega el layout y un payload de muestra ya resuelto con datos
 * reales; aquí sólo se mueven elementos y se guarda el JSON. El tamaño del
 * lienzo lo define la imagen de fondo.
 */
const props = defineProps({
    sportsMode: { type: Boolean, default: false },
    edition: { type: Object, required: true },
    template: { type: Object, required: true },
    award: { type: Object, default: null },
    samplePayload: { type: Object, default: null },
    tokens: { type: Object, default: () => ({}) },
    fonts: { type: Array, default: () => [] },
    // El mismo editor sirve a la edición y a la biblioteca del torneo: el
    // servidor decide las rutas para no duplicar el componente.
    endpoints: { type: Object, default: () => ({}) },
    breadcrumbs: { type: Array, default: () => [] },
});

const api = computed(() => ({
    update:
        props.endpoints?.update ||
        route('even_ediciones_diplomas_update', [props.edition.id, props.template.id]),
    background:
        props.endpoints?.background ||
        route('even_ediciones_diplomas_fondo', [props.edition.id, props.template.id]),
    asset:
        props.endpoints?.asset ||
        route('even_ediciones_diplomas_asset', [props.edition.id, props.template.id]),
    back: props.endpoints?.back || route('even_ediciones_diplomas', props.edition.id),
}));

const crumbs = computed(() =>
    props.breadcrumbs?.length
        ? props.breadcrumbs
        : [
              { route: route('even_ediciones_listado'), title: 'Torneos' },
              { route: route('even_ediciones_diplomas', props.edition.id), title: 'Diplomas' },
              { title: props.template.award_label },
          ]
);

const assetBase = typeof assetUrl !== 'undefined' ? assetUrl : '/';

const layout = ref(cloneLayout(props.template.layout));
const name = ref(props.template.name);
const canvas = ref({
    width: Number(props.template.width || 1550),
    height: Number(props.template.height || 1096),
});
const backgroundUrl = ref(props.template.background_url);

const selectedId = ref(null);
const zoom = ref(null);
const wrapWidth = ref(900);
const saving = ref(false);
const dirty = ref(false);
const revision = ref(0);
const lastSaved = ref(null);
const previewOpen = ref(false);
const uploading = ref(false);

const stageWrap = ref(null);
const backgroundInput = ref(null);
const assetInput = ref(null);
const autosaveTimer = ref(null);

const elements = computed(() => layout.value.elements || []);

const selectedElement = computed(() => elements.value.find((element) => element.id === selectedId.value) || null);

const sampleById = computed(() => {
    const map = {};
    (props.samplePayload?.side?.elements || []).forEach((element) => {
        map[element.id] = element;
    });

    return map;
});

/** Valores reales de cada variable, resueltos por el servidor. */
const tokenValues = computed(() => props.samplePayload?.token_values || {});

const resolveSrc = (src) => {
    if (!src) return null;
    if (String(src).startsWith('http')) return src;

    return `${assetBase}storage/${String(src).replace(/^\/+/, '')}`;
};

/**
 * Elementos que se dibujan: la geometría y los textos salen del layout (lo que
 * el usuario edita). Del payload sólo se toman los datos que el navegador no
 * puede calcular: el texto del QR y los jugadores del plantel.
 */
const renderElements = computed(() =>
    elements.value.map((element) => {
        const sample = sampleById.value[element.id];

        if (element.type === 'text') {
            const raw = element.text ?? sample?.text ?? '';

            return { ...element, text: resolveDiplomaTokens(raw, tokenValues.value) };
        }

        if (element.type === 'squad') {
            return {
                ...element,
                title: resolveDiplomaTokens(element.title ?? sample?.title ?? '', tokenValues.value),
                items: buildSquadItems(element, sample?.items),
            };
        }

        if (element.type === 'qr') {
            return { ...element, qr_text: sample?.qr_text || element.qr_text || '' };
        }

        if (element.type === 'image') {
            return { ...element, src: element.src ? resolveSrc(element.src) : resolveSrc(sample?.src) };
        }

        return element;
    })
);

const side = computed(() => ({
    key: 'front',
    width: canvas.value.width,
    height: canvas.value.height,
    background_url: backgroundUrl.value,
    elements: renderElements.value,
}));

const fitScale = computed(() => {
    const available = Math.max(320, wrapWidth.value - 24);

    return Math.min(available / canvas.value.width, 640 / canvas.value.height, 1);
});

const effectiveScale = computed(() => (zoom.value === null ? fitScale.value : zoom.value));

const previewPayload = computed(() => ({
    side: side.value,
    file_name: `diploma_${props.template.award_key}`,
}));

// ── Historial (deshacer / rehacer) ─────────────────────────────────────────
const history = ref([JSON.stringify(layout.value)]);
const historyIndex = ref(0);
let lastRecordAt = 0;

const record = (throttle = 0) => {
    const snapshot = JSON.stringify(layout.value);
    if (history.value[historyIndex.value] === snapshot) return;

    // Al escribir, no guardamos una entrada de historial por cada carácter.
    if (throttle && Date.now() - lastRecordAt < throttle) return;

    history.value = history.value.slice(0, historyIndex.value + 1);
    history.value.push(snapshot);

    if (history.value.length > 30) history.value.shift();

    historyIndex.value = history.value.length - 1;
    lastRecordAt = Date.now();
};

const undo = () => {
    if (historyIndex.value <= 0) return;

    historyIndex.value -= 1;
    layout.value = JSON.parse(history.value[historyIndex.value]);
    selectedId.value = null;
    markDirty();
};

const redo = () => {
    if (historyIndex.value >= history.value.length - 1) return;

    historyIndex.value += 1;
    layout.value = JSON.parse(history.value[historyIndex.value]);
    selectedId.value = null;
    markDirty();
};

// ── Cambios ────────────────────────────────────────────────────────────────
const markDirty = () => {
    dirty.value = true;
    revision.value += 1;
    scheduleAutosave();
};

const scheduleAutosave = () => {
    clearTimeout(autosaveTimer.value);
    autosaveTimer.value = setTimeout(() => save(false), 1800);
};

const patchElement = (id, patch) => {
    const element = layout.value.elements.find((row) => row.id === id);
    if (!element) return;

    Object.assign(element, patch);
    markDirty();
};

const onElementUpdate = ({ id, changes }) => {
    record();
    patchElement(id, changes);
};

const patchSelected = (patch) => {
    if (!selectedElement.value) return;

    record(600);
    patchElement(selectedElement.value.id, patch);
};

const selectElement = (id) => {
    selectedId.value = id || null;
};

const addElement = (type, options = {}) => {
    record();
    const element = createElement(type, canvas.value, options);

    // Marcos y líneas son decorativos: van al fondo para no tapar los textos y
    // seguir pudiendo seleccionarlos con un clic. Se pueden subir de capa.
    if (type === 'rect' || type === 'line') {
        layout.value.elements.unshift(element);
    } else {
        layout.value.elements.push(element);
    }

    selectedId.value = element.id;
    markDirty();
};

const deleteSelected = () => {
    if (!selectedElement.value) return;

    record();
    layout.value.elements = layout.value.elements.filter((element) => element.id !== selectedId.value);
    selectedId.value = null;
    markDirty();
};

const duplicateSelected = () => {
    if (!selectedElement.value) return;

    record();
    const copy = JSON.parse(JSON.stringify(selectedElement.value));
    copy.id = `el_${Date.now()}_${Math.floor(Math.random() * 1000)}`;
    copy.name = `${copy.name || 'Elemento'} (copia)`;
    copy.x += 24;
    copy.y += 24;
    layout.value.elements.push(copy);
    selectedId.value = copy.id;
    markDirty();
};

const moveSelected = (direction) => {
    if (!selectedElement.value) return;

    const index = layout.value.elements.findIndex((element) => element.id === selectedId.value);
    const target = index + direction;

    if (index < 0 || target < 0 || target >= layout.value.elements.length) return;

    record();
    const [moved] = layout.value.elements.splice(index, 1);
    layout.value.elements.splice(target, 0, moved);
    markDirty();
};

const toggleVisible = (element) => {
    record();
    element.visible = element.visible === false;
    markDirty();
};

const toggleLock = (element) => {
    record();
    element.locked = !element.locked;
    markDirty();
};

// ── Guardado ───────────────────────────────────────────────────────────────
const save = async (manual = false) => {
    if (saving.value) {
        // Un guardado en curso: reintentamos al terminar para no perder cambios.
        scheduleAutosave();
        return;
    }

    saving.value = true;
    const snapshot = revision.value;

    try {
        const response = await axios.put(
            api.value.update,
            {
                name: name.value,
                layout: {
                    version: layout.value.version || 1,
                    background: { width: canvas.value.width, height: canvas.value.height },
                    elements: layout.value.elements,
                },
            }
        );

        lastSaved.value = response.data?.updated_at || new Date().toLocaleTimeString('es-PE');

        if (revision.value === snapshot) {
            dirty.value = false;
        } else {
            // El usuario siguió editando mientras se guardaba: otra vuelta.
            scheduleAutosave();
        }

        if (manual) {
            Swal2.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Diseño guardado',
                showConfirmButton: false,
                timer: 1800,
            });
        }
    } catch (error) {
        const message = error.response?.data?.message || 'No se pudo guardar el diseño.';
        Swal2.fire({ icon: 'error', title: 'Error al guardar', text: message });
    } finally {
        saving.value = false;
    }
};

// ── Fondo e imágenes ───────────────────────────────────────────────────────
const resizeLayoutToCanvas = (oldWidth, oldHeight, newWidth, newHeight) => {
    const ratioX = newWidth / oldWidth;
    const ratioY = newHeight / oldHeight;
    const ratio = Math.min(ratioX, ratioY);

    layout.value.elements = layout.value.elements.map((element) => {
        const scaled = { ...element, x: Math.round(element.x * ratioX), y: Math.round(element.y * ratioY) };

        if (element.width) scaled.width = Math.round(element.width * ratioX);
        if (element.height) scaled.height = Math.round(element.height * ratioY);
        if (element.fontSize) scaled.fontSize = Math.round(element.fontSize * ratio);
        if (element.titleFontSize) scaled.titleFontSize = Math.round(element.titleFontSize * ratio);
        if (element.size) scaled.size = Math.round(element.size * ratio);

        return scaled;
    });
};

const uploadBackground = async (event) => {
    const file = event.target.files?.[0];
    if (!file) return;

    uploading.value = true;

    try {
        const data = new FormData();
        data.append('background', file);

        const response = await axios.post(
            api.value.background,
            data,
            { headers: { 'Content-Type': 'multipart/form-data' } }
        );

        const previous = { ...canvas.value };

        canvas.value = { width: response.data.width, height: response.data.height };
        backgroundUrl.value = `${response.data.url}?v=${Date.now()}`;

        const result = await Swal2.fire({
            icon: 'question',
            title: 'Fondo actualizado',
            text: '¿Quieres escalar los elementos al nuevo tamaño del lienzo?',
            showCancelButton: true,
            confirmButtonText: 'Escalar elementos',
            cancelButtonText: 'Mantener posiciones',
        });

        if (result.isConfirmed) {
            record();
            resizeLayoutToCanvas(previous.width, previous.height, canvas.value.width, canvas.value.height);
        }

        markDirty();
    } catch (error) {
        Swal2.fire({
            icon: 'error',
            title: 'No se pudo subir el fondo',
            text: error.response?.data?.message || 'Verifica que sea una imagen de hasta 8 MB.',
        });
    } finally {
        uploading.value = false;
        if (backgroundInput.value) backgroundInput.value.value = '';
    }
};

const uploadAsset = async (event) => {
    const file = event.target.files?.[0];
    if (!file) return;

    uploading.value = true;

    try {
        const data = new FormData();
        data.append('image', file);

        const response = await axios.post(
            api.value.asset,
            data,
            { headers: { 'Content-Type': 'multipart/form-data' } }
        );

        addElement('image', {
            src: response.data.path,
            width: Number(response.data.width || 160),
            height: Number(response.data.height || 160),
        });
    } catch (error) {
        Swal2.fire({
            icon: 'error',
            title: 'No se pudo subir la imagen',
            text: error.response?.data?.message || 'Inténtalo con otra imagen.',
        });
    } finally {
        uploading.value = false;
        if (assetInput.value) assetInput.value.value = '';
    }
};

// ── Utilidades de la interfaz ───────────────────────────────────────────────
const copyToken = async (token) => {
    const text = `{{${token}}}`;

    try {
        await navigator.clipboard.writeText(text);
        Swal2.fire({ toast: true, position: 'top-end', icon: 'success', title: `${text} copiado`, showConfirmButton: false, timer: 1400 });
    } catch {
        Swal2.fire({ icon: 'info', title: 'Copia la variable', text: `Escribe ${text} en el texto del elemento.` });
    }
};

const setZoom = (value) => {
    if (value === 'fit') {
        zoom.value = null;
        return;
    }

    zoom.value = Number(value);
};

const updateWrapWidth = () => {
    wrapWidth.value = stageWrap.value?.clientWidth || 900;
};

const onKeydown = (event) => {
    const tag = document.activeElement?.tagName;
    const typing = ['INPUT', 'TEXTAREA', 'SELECT'].includes(tag);

    if (typing) return;

    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'z') {
        event.preventDefault();
        event.shiftKey ? redo() : undo();
        return;
    }

    if (event.key === 'Delete' || event.key === 'Backspace') {
        if (selectedId.value) {
            event.preventDefault();
            deleteSelected();
        }
        return;
    }

    if (['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(event.key) && selectedElement.value) {
        event.preventDefault();
        const step = event.shiftKey ? 10 : 1;
        const patch = { x: selectedElement.value.x, y: selectedElement.value.y };

        if (event.key === 'ArrowLeft') patch.x -= step;
        if (event.key === 'ArrowRight') patch.x += step;
        if (event.key === 'ArrowUp') patch.y -= step;
        if (event.key === 'ArrowDown') patch.y += step;

        patchElement(selectedElement.value.id, patch);
    }
};

const warnUnsaved = (event) => {
    if (!dirty.value) return;
    event.preventDefault();
    event.returnValue = '';
};

const downloadCurrentPdf = () => downloadPdf(side.value, previewPayload.value.file_name);
const downloadCurrentPng = () => downloadPng(side.value, previewPayload.value.file_name);

const openPreview = () => {
    previewOpen.value = true;
};

onMounted(async () => {
    updateWrapWidth();
    window.addEventListener('resize', updateWrapWidth);
    window.addEventListener('keydown', onKeydown);
    window.addEventListener('beforeunload', warnUnsaved);

    const families = (props.fonts || []).filter((font) => font.file);
    if (document.fonts?.load) {
        await Promise.all(
            families.map((font) => document.fonts.load(`16px "${font.family}"`).catch(() => null))
        );
    }

    if (props.samplePayload?.side?.width && props.samplePayload?.side?.height) {
        canvas.value = {
            width: Number(props.samplePayload.side.width),
            height: Number(props.samplePayload.side.height),
        };
    }
});

onBeforeUnmount(() => {
    clearTimeout(autosaveTimer.value);
    window.removeEventListener('resize', updateWrapWidth);
    window.removeEventListener('keydown', onKeydown);
    window.removeEventListener('beforeunload', warnUnsaved);
});
</script>

<template>
    <AppLayout :title="`Diplomas · ${edition.name}`">
        <Navigation
            :routeModule="route('even_dashboard')"
            :titleModule="sportsMode ? 'GESTIÓN DEPORTIVA' : 'Eventos sociales'"
            :data="crumbs"
        />

        <main class="mx-auto mt-5 max-w-[1600px] px-3 pb-10 sm:px-5">
            <section class="diploma-hero">
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-lime-300/80">Editor de diplomas</p>
                    <div class="mt-2 flex flex-wrap items-center gap-3">
                        <input
                            v-model="name"
                            type="text"
                            maxlength="120"
                            class="diploma-name-input"
                            @change="markDirty"
                        />
                        <span class="diploma-badge">{{ template.award_label }}</span>
                        <span v-if="template.is_active" class="diploma-badge diploma-badge--active">Vigente</span>
                    </div>
                    <p class="mt-2 text-sm text-slate-300">
                        {{ edition.evento }} · {{ edition.name }} · {{ canvas.width }} × {{ canvas.height }} px
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <Link :href="api.back" class="diploma-btn diploma-btn--ghost">
                        ← Volver
                    </Link>
                    <button type="button" class="diploma-btn diploma-btn--ghost" :disabled="uploading" @click="backgroundInput?.click()">
                        Cambiar fondo
                    </button>
                    <button type="button" class="diploma-btn diploma-btn--ghost" :disabled="uploading" @click="assetInput?.click()">
                        Subir imagen
                    </button>
                    <button type="button" class="diploma-btn diploma-btn--ghost" @click="openPreview">Vista previa</button>
                    <button type="button" class="diploma-btn diploma-btn--primary" :disabled="saving" @click="save(true)">
                        {{ saving ? 'Guardando…' : 'Guardar' }}
                    </button>
                </div>

                <input ref="backgroundInput" type="file" accept="image/png,image/jpeg,image/webp" class="hidden" @change="uploadBackground" />
                <input ref="assetInput" type="file" accept="image/png,image/jpeg,image/webp" class="hidden" @change="uploadAsset" />
            </section>

            <div class="mt-4 grid gap-4 xl:grid-cols-[260px_minmax(0,1fr)_320px]">
                <!-- Capas -->
                <aside class="diploma-panel">
                    <h2 class="diploma-panel__title">Agregar</h2>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" class="diploma-add" @click="addElement('text', { text: '{{premio}}' })">Texto</button>
                        <button type="button" class="diploma-add" @click="addElement('text', { text: '{{premio_ganador}}' })">Ganador</button>
                        <button type="button" class="diploma-add" @click="addElement('squad')">Plantel</button>
                        <button type="button" class="diploma-add" @click="addElement('rect')">Marco</button>
                        <button type="button" class="diploma-add" @click="addElement('line')">Línea</button>
                        <button type="button" class="diploma-add" @click="addElement('qr')">QR</button>
                    </div>

                    <h2 class="diploma-panel__title mt-5">Capas</h2>
                    <ul class="space-y-1">
                        <li
                            v-for="element in [...elements].reverse()"
                            :key="element.id"
                            class="diploma-layer"
                            :class="{ 'diploma-layer--active': element.id === selectedId }"
                        >
                            <button type="button" class="min-w-0 flex-1 truncate text-left" @click="selectElement(element.id)">
                                {{ elementLabel(element) }}
                            </button>
                            <button type="button" class="diploma-layer__action" :title="element.visible === false ? 'Mostrar' : 'Ocultar'" @click="toggleVisible(element)">
                                {{ element.visible === false ? '🚫' : '👁' }}
                            </button>
                            <button type="button" class="diploma-layer__action" :title="element.locked ? 'Desbloquear' : 'Bloquear'" @click="toggleLock(element)">
                                {{ element.locked ? '🔒' : '🔓' }}
                            </button>
                        </li>
                    </ul>

                    <h2 class="diploma-panel__title mt-5">Variables</h2>
                    <p class="mb-2 text-xs text-slate-500">Toca una variable para copiarla y pégala en el texto de un elemento.</p>
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            v-for="(description, token) in tokens"
                            :key="token"
                            type="button"
                            class="diploma-token"
                            :title="description"
                            @click="copyToken(token)"
                        >
                            {{ token }}
                        </button>
                    </div>
                </aside>

                <!-- Lienzo -->
                <section class="diploma-canvas">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2 text-xs font-bold text-slate-500">
                            <span>{{ Math.round(effectiveScale * 100) }}%</span>
                            <span v-if="dirty" class="text-amber-600">Cambios sin guardar</span>
                            <span v-else-if="lastSaved" class="text-emerald-600">Guardado {{ lastSaved }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <button type="button" class="diploma-btn diploma-btn--tiny" @click="setZoom('fit')">Ajustar</button>
                            <button type="button" class="diploma-btn diploma-btn--tiny" @click="setZoom(0.5)">50%</button>
                            <button type="button" class="diploma-btn diploma-btn--tiny" @click="setZoom(1)">100%</button>
                            <button type="button" class="diploma-btn diploma-btn--tiny" :disabled="historyIndex <= 0" @click="undo">↶</button>
                            <button type="button" class="diploma-btn diploma-btn--tiny" :disabled="historyIndex >= history.length - 1" @click="redo">↷</button>
                        </div>
                    </div>

                    <div ref="stageWrap" class="overflow-auto rounded-xl border border-[#234052] bg-[#0b1f2b] p-2">
                        <div class="mx-auto w-fit bg-white shadow-2xl">
                            <DiplomaStage
                                :side="side"
                                :scale="effectiveScale"
                                interactive
                                :selected-id="selectedId"
                                @select="selectElement"
                                @update="onElementUpdate"
                            />
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center justify-end gap-2">
                        <button type="button" class="diploma-btn diploma-btn--ghost" @click="downloadCurrentPng">PNG</button>
                        <button type="button" class="diploma-btn diploma-btn--ghost" @click="downloadCurrentPdf">PDF</button>
                    </div>
                </section>

                <!-- Inspector -->
                <aside class="diploma-panel">
                    <DiplomaInspector
                        :element="selectedElement"
                        :fonts="fonts"
                        @patch="patchSelected"
                        @delete="deleteSelected"
                        @duplicate="duplicateSelected"
                        @move="moveSelected"
                    />
                </aside>
            </div>
        </main>

        <DiplomaPreviewModal
            :open="previewOpen"
            :payload="previewPayload"
            @close="previewOpen = false"
        />
    </AppLayout>
</template>

<style scoped>
@font-face {
    font-family: 'Poppins Light';
    src: url('/fonts/Poppins-Light.ttf') format('truetype');
    font-display: swap;
}

@font-face {
    font-family: 'Pacifico Regular';
    src: url('/fonts/Pacifico-Regular.ttf') format('truetype');
    font-display: swap;
}

@font-face {
    font-family: 'Intro Headr';
    src: url('/fonts/Intro-Headr.ttf') format('truetype');
    font-display: swap;
}

@font-face {
    font-family: 'Old English';
    src: url('/fonts/OLDENGL.TTF') format('truetype');
    font-display: swap;
}

@font-face {
    font-family: 'Playwrite IN';
    src: url('/fonts/PlaywriteIN-Regular.ttf') format('truetype');
    font-display: swap;
}

.diploma-hero {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    border-radius: 1rem;
    border: 1px solid #234052;
    background: linear-gradient(135deg, #0b1f2b 0%, #0e2532 55%, #122c3a 100%);
    padding: 1.25rem 1.5rem;
    color: #e9f4f2;
    box-shadow: 0 20px 50px rgb(0 0 0 / 28%);
}

.diploma-name-input {
    min-width: 16rem;
    border: 1px solid #2b4b5d;
    border-radius: 0.6rem;
    background: #122c3a;
    padding: 0.4rem 0.7rem;
    font-size: 1.1rem;
    font-weight: 800;
    color: #fff;
}

.diploma-name-input:focus {
    outline: 2px solid #84cc16;
    outline-offset: 1px;
}

.diploma-badge {
    border-radius: 999px;
    border: 1px solid #2b4b5d;
    background: #17394a;
    padding: 0.2rem 0.7rem;
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #cfe2e4;
}

.diploma-badge--active {
    border-color: rgb(132 204 22 / 45%);
    background: rgb(132 204 22 / 15%);
    color: #d9f99d;
}

.diploma-panel {
    border-radius: 1rem;
    border: 1px solid #e2e8f0;
    background: #fff;
    padding: 1rem;
    box-shadow: 0 10px 30px rgb(15 23 42 / 6%);
    align-self: start;
}

.diploma-panel__title {
    margin-bottom: 0.5rem;
    font-size: 0.7rem;
    font-weight: 900;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #64748b;
}

.diploma-add {
    border-radius: 0.6rem;
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    padding: 0.5rem 0.4rem;
    font-size: 0.78rem;
    font-weight: 800;
    color: #334155;
    transition: all 0.15s ease;
}

.diploma-add:hover {
    border-color: #84cc16;
    background: #f7fee7;
    color: #3f6212;
}

.diploma-layer {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    border-radius: 0.55rem;
    padding: 0.3rem 0.4rem;
    font-size: 0.8rem;
    font-weight: 700;
    color: #334155;
}

.diploma-layer:hover {
    background: #f1f5f9;
}

.diploma-layer--active {
    background: #ecfccb;
    color: #3f6212;
}

.diploma-layer__action {
    font-size: 0.8rem;
    opacity: 0.7;
}

.diploma-layer__action:hover {
    opacity: 1;
}

.diploma-token {
    border-radius: 999px;
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    padding: 0.15rem 0.5rem;
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    font-size: 0.68rem;
    color: #334155;
}

.diploma-token:hover {
    border-color: #84cc16;
    color: #3f6212;
}

.diploma-canvas {
    min-width: 0;
}

.diploma-btn {
    display: inline-flex;
    min-height: 2.4rem;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    border-radius: 0.7rem;
    padding: 0 0.85rem;
    font-size: 0.8rem;
    font-weight: 800;
    transition: all 0.15s ease;
}

.diploma-btn:disabled {
    cursor: not-allowed;
    opacity: 0.45;
}

.diploma-btn--primary {
    border: 1px solid #84cc16;
    background: #c8f169;
    color: #08251a;
}

.diploma-btn--primary:hover:not(:disabled) {
    background: #d9fb8d;
}

.diploma-btn--ghost {
    border: 1px solid #2b4b5d;
    background: #122c3a;
    color: #e9f4f2;
}

.diploma-btn--ghost:hover:not(:disabled) {
    border-color: rgb(132 204 22 / 60%);
    color: #d9f99d;
}

.diploma-btn--tiny {
    min-height: 2rem;
    padding: 0 0.6rem;
    font-size: 0.72rem;
}
</style>
