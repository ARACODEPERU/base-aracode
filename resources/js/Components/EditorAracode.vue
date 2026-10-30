<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import AracodeEditor, * as editorAracodeModulo from '@elmerrodriguez/editor-aracode';
import '@editor-aracode-styles';
import { getCsrfToken } from '@/utils/csrf';

// El sustituto de vite.config.js exporta false cuando el paquete local del
// editor no está instalado; el paquete real no exporta esta bandera.
const editorHabilitado = editorAracodeModulo.editorAracodeDisponible !== false;

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Escribe aquí...' },
    minHeight: { type: String, default: '300px' },
    readonly: { type: Boolean, default: false },
    imageUploadUrl: { type: String, default: '' },
    toolbar: { type: Array, default: null },
    imageUploadHandler: { type: Function, default: null },
});

const emit = defineEmits(['update:modelValue']);

const containerRef = ref(null);
let editor = null;

function parseHeight(value) {
    const parsed = parseInt(String(value), 10);
    return Number.isFinite(parsed) && parsed > 0 ? parsed : 300;
}

function buildOptions() {
    const csrfToken = getCsrfToken();
    const options = {
        value: props.modelValue || '',
        placeholder: props.placeholder,
        height: parseHeight(props.minHeight),
        readOnly: props.readonly,
        locale: 'es',
        imageUploadUrl: props.imageUploadUrl || null,
        pasteImageUpload: true,
        imageUploadHeaders: csrfToken ? {
            'X-CSRF-TOKEN': csrfToken,
        } : {},
        imageUploadParams: csrfToken ? {
            '_token': csrfToken,
        } : {},
    };

    if (props.toolbar) {
        options.toolbar = props.toolbar;
    }
    if (typeof props.imageUploadHandler === 'function') {
        options.imageUploadHandler = props.imageUploadHandler;
    }

    return options;
}

onMounted(() => {
    if (!editorHabilitado) return;

    editor = new AracodeEditor(containerRef.value, buildOptions());

    editor.on('change', (html) => {
        emit('update:modelValue', html);
    });
});

watch(() => props.modelValue, (val) => {
    if (!editor) return;
    const next = val || '';
    if (editor.getHTML() === next) return;
    const hasFocus = editor.container?.contains(document.activeElement);
    if (hasFocus) return;
    editor.setHTML(next);
});

watch(() => props.readonly, (val) => {
    if (editor) editor.setReadOnly(val);
});

onUnmounted(() => {
    if (editor) editor.destroy();
});
</script>

<template>
    <div v-if="editorHabilitado" ref="containerRef" class="editor-aracode-wrapper"></div>
    <div v-else class="editor-aracode-fallback">
        <textarea
            :value="modelValue"
            :placeholder="placeholder"
            :readonly="readonly"
            :style="{ minHeight }"
            class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
            @input="emit('update:modelValue', $event.target.value)"
        ></textarea>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
            Editor simplificado: el editor enriquecido no está instalado en este entorno.
        </p>
    </div>
</template>

<style scoped>
.editor-aracode-wrapper {
    width: 100%;
}
</style>
