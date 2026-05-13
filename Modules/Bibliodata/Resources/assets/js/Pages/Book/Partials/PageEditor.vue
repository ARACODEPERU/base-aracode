<script setup>
import { ref, watch } from 'vue';
import ModalLargeXX from '@/Components/ModalLargeXX.vue';
import EditorAracode from '@/Components/EditorAracode.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    pageTitle: { type: String, default: '' },
    content: { type: String, default: '' },
    saving: { type: Boolean, default: false },
    imageUploadUrl: { type: String, default: '' },
});

const emit = defineEmits(['close', 'save']);

const editedContent = ref(props.content || '');

watch(() => props.show, (val) => {
    if (val) editedContent.value = props.content || '';
});

watch(() => props.content, (val) => {
    editedContent.value = val || '';
});

const handleSave = () => {
    emit('save', editedContent.value);
};

const handleClose = () => {
    emit('close');
};
</script>

<template>
    <ModalLargeXX :show="show" :onClose="handleClose" :icon="'/img/lista.png'">
        <template #title>
            {{ pageTitle || 'Editar contenido' }}
        </template>
        <template #message>
            Utiliza las herramientas de formato para dar estilo a tu contenido
        </template>
        <template #content>
            <EditorAracode
                :key="'editor-' + pageTitle"
                v-model="editedContent"
                :minHeight="'400px'"
                placeholder="Escribe el contenido de la página aquí..."
                :imageUploadUrl="imageUploadUrl"
            />
        </template>
        <template #buttons>
            <PrimaryButton @click="handleSave" :disabled="saving">
                <IconLoader v-if="saving" class="w-4 h-4 mr-2 animate-spin" />
                {{ saving ? 'Guardando...' : 'Guardar cambios' }}
            </PrimaryButton>
        </template>
    </ModalLargeXX>
</template>
