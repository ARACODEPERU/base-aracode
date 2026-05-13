<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import AracodeEditor from '@elmerrodriguez/editor-aracode';
import '@elmerrodriguez/editor-aracode/dist/aracode-editor.css';

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Escribe aquí...' },
    minHeight: { type: String, default: '300px' },
    readonly: { type: Boolean, default: false },
    imageUploadUrl: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

const containerRef = ref(null);
let editor = null;

onMounted(() => {
    editor = new AracodeEditor(containerRef.value, {
        value: props.modelValue || '',
        placeholder: props.placeholder,
        height: parseInt(props.minHeight) || 300,
        readOnly: props.readonly,
        locale: 'es',
        imageUploadUrl: props.imageUploadUrl || null,
        imageUploadHeaders: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        },
        imageUploadParams: {
            '_token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        },
    });

    editor.on('change', (html) => {
        emit('update:modelValue', html);
    });
});

watch(() => props.modelValue, (val) => {
    if (editor && editor.getHTML() !== (val || '')) {
        editor.setHTML(val || '');
    }
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
