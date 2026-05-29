<script setup>
import { ref, watch } from 'vue';
import EditorAracode from '@/Components/EditorAracode.vue';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';

const props = defineProps({
    content: { type: String, default: '' },
    loading: { type: Boolean, default: false },
    pageLabel: { type: String, default: '' },
    imageUploadUrl: { type: String, default: '' },
    emptyHint: {
        type: String,
        default: 'Expanda un capítulo y seleccione una página del árbol para editar su contenido.',
    },
    editorPlaceholder: { type: String, default: 'Escribe el contenido de la página...' },
});

const emit = defineEmits(['update:content']);

const localContent = ref(props.content || '');

watch(
    () => props.content,
    (val) => {
        localContent.value = val || '';
    }
);

watch(localContent, (val) => {
    emit('update:content', val);
});
</script>

<template>
    <div class="flex-1 flex flex-col min-h-0 relative">
        <div
            v-if="loading"
            class="absolute inset-0 z-10 flex items-center justify-center bg-white/70 dark:bg-zinc-900/70"
        >
            <IconLoader class="w-8 h-8 animate-spin text-primary" />
        </div>

        <div v-if="!pageLabel && !loading" class="flex-1 flex items-center justify-center text-gray-400 text-sm p-8">
            {{ emptyHint }}
        </div>

        <div v-else-if="pageLabel" class="flex-1 p-4 overflow-hidden flex flex-col">
            <div class="flex-1 min-h-[400px]">
                <EditorAracode
                    :key="'page-editor-' + pageLabel"
                    v-model="localContent"
                    minHeight="450px"
                    :placeholder="editorPlaceholder"
                    :imageUploadUrl="imageUploadUrl"
                />
            </div>
        </div>
    </div>
</template>
