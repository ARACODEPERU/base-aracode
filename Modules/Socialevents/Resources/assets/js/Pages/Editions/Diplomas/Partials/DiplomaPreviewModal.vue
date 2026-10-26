<script setup>
import { computed, ref } from 'vue';
import DiplomaStage from './DiplomaStage.vue';
import { downloadPdf, downloadPng } from '../../../../utils/diplomaRender';

/**
 * Vista previa del diploma ya resuelto por el servidor.
 *
 * Muestra exactamente lo que se exporta: mismo lienzo, mismo tamaño de fondo.
 */
const props = defineProps({
    open: { type: Boolean, default: false },
    payload: { type: Object, default: null },
    busy: { type: Boolean, default: false },
});

const emit = defineEmits(['close']);
const working = ref(false);
const error = ref(null);

const side = computed(() => props.payload?.side || null);
const fileName = computed(() => props.payload?.file_name || 'diploma');
// Variables usadas en el diseño que el servidor no pudo completar.
const missingTokens = computed(() => props.payload?.missing_tokens || []);

const scale = computed(() => {
    if (!side.value) return 1;

    const width = Number(side.value.width || 1550);
    const height = Number(side.value.height || 1096);

    return Math.min(920 / width, 540 / height, 1);
});

const run = async (task) => {
    if (!side.value) return;

    working.value = true;
    error.value = null;

    try {
        await task();
    } catch (exception) {
        error.value = 'No se pudo generar el archivo. Inténtalo de nuevo.';
    } finally {
        working.value = false;
    }
};

const downloadPngFile = () => run(() => downloadPng(side.value, fileName.value));
const downloadPdfFile = () => run(() => downloadPdf(side.value, fileName.value));
</script>

<template>
    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4" @click.self="emit('close')">
        <div class="flex max-h-[94vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
            <header class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                <div>
                    <h2 class="text-lg font-black text-slate-900">Vista previa del diploma</h2>
                    <p class="text-sm text-slate-500">Revisa el resultado antes de descargarlo.</p>
                </div>
                <button type="button" class="rounded-full p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900" @click="emit('close')">
                    ✕
                </button>
            </header>

            <div class="flex-1 overflow-auto bg-slate-100 p-4">
                <p
                    v-if="missingTokens.length"
                    class="mx-auto mb-3 max-w-3xl rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-800"
                >
                    Estas variables no tienen dato para este premiado y saldrán vacías:
                    <strong>{{ missingTokens.join(', ') }}</strong>.
                    Completa el dato o quita la variable del modelo.
                </p>

                <div v-if="busy || !side" class="flex h-72 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-white text-sm font-semibold text-slate-500">
                    Preparando el diploma…
                </div>
                <div v-else class="mx-auto w-fit overflow-hidden rounded-lg bg-white shadow-lg">
                    <DiplomaStage :side="side" :scale="scale" />
                </div>

                <p v-if="error" class="mt-3 rounded-lg bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-700">{{ error }}</p>
            </div>

            <footer class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 px-5 py-4">
                <button type="button" class="diploma-btn diploma-btn--ghost" :disabled="!side || working" @click="emit('close')">
                    Cerrar
                </button>
                <button type="button" class="diploma-btn diploma-btn--ghost" :disabled="!side || working" @click="downloadPngFile">
                    Descargar PNG
                </button>
                <button type="button" class="diploma-btn diploma-btn--primary" :disabled="!side || working" @click="downloadPdfFile">
                    {{ working ? 'Generando…' : 'Descargar PDF' }}
                </button>
            </footer>
        </div>
    </div>
</template>

<style scoped>
.diploma-btn {
    display: inline-flex;
    min-height: 2.6rem;
    align-items: center;
    justify-content: center;
    border-radius: 0.75rem;
    padding: 0 1rem;
    font-size: 0.85rem;
    font-weight: 800;
    transition: all 0.15s ease;
}

.diploma-btn:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

.diploma-btn--primary {
    background: #0b1f2b;
    color: #d9f99d;
    border: 1px solid #2b4b5d;
}

.diploma-btn--primary:hover:not(:disabled) {
    background: #17394a;
    border-color: #84cc16;
}

.diploma-btn--ghost {
    border: 1px solid #cbd5e1;
    background: #fff;
    color: #334155;
}

.diploma-btn--ghost:hover:not(:disabled) {
    border-color: #84cc16;
    color: #3f6212;
}
</style>
