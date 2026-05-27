<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import AracodeEditor from '@elmerrodriguez/editor-aracode';
import '@editor-aracode-styles';

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
    const options = {
        value: props.modelValue || '',
        placeholder: props.placeholder,
        height: parseHeight(props.minHeight),
        readOnly: props.readonly,
        locale: 'es',
        imageUploadUrl: props.imageUploadUrl || null,
        imageUploadHeaders: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        },
        imageUploadParams: {
            '_token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        },
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
    <div ref="containerRef" class="editor-aracode-wrapper"></div>
</template>

<style scoped>
.editor-aracode-wrapper {
    width: 100%;
}
</style>
