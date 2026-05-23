<script setup>
import { ref, watch } from 'vue';
import ModalSmall from '@/Components/ModalSmall.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    mode: { type: String, default: 'create' },
    isSubsection: { type: Boolean, default: false },
    initialTitle: { type: String, default: '' },
    saving: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'submit']);

const title = ref('');

watch(
    () => props.show,
    (val) => {
        if (val) {
            title.value = props.initialTitle || '';
        }
    }
);

const modalTitle = () => {
    if (props.mode === 'edit') return 'Editar título';
    return props.isSubsection ? 'Nueva sub-sección' : 'Nuevo capítulo';
};

const placeholder = () => {
    if (props.mode === 'edit') return 'Título del capítulo';
    return props.isSubsection ? 'Ej: 1.1 Introducción' : 'Ej: Capítulo 1';
};

const submit = () => {
    const t = title.value?.trim();
    if (!t) return;
    emit('submit', t);
};
</script>

<template>
    <ModalSmall :show="show" :onClose="() => !saving && emit('close')">
        <template #title>{{ modalTitle() }}</template>
        <template #message>Los campos con * son obligatorios</template>
        <template #content>
            <div class="space-y-4" :class="{ 'opacity-60 pointer-events-none': saving }">
                <div>
                    <InputLabel value="Título *" />
                    <TextInput
                        v-model="title"
                        type="text"
                        class="form-input w-full"
                        :placeholder="placeholder()"
                        @keyup.enter="submit"
                    />
                </div>
            </div>
        </template>
        <template #buttons>
            <PrimaryButton type="button" :disabled="saving || !title.trim()" @click="submit">
                <IconLoader v-if="saving" class="w-4 h-4 mr-2 animate-spin" />
                {{ mode === 'edit' ? 'Guardar' : 'Agregar' }}
            </PrimaryButton>
        </template>
    </ModalSmall>
</template>
