<script setup>
import { ref, watch, toRef, computed } from 'vue';
import ModalSmall from '@/Components/ModalSmall.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';
import { useBookContentLabels } from '../../../composables/useBookContentLabels';

const props = defineProps({
    show: { type: Boolean, default: false },
    mode: { type: String, default: 'create' },
    isSubsection: { type: Boolean, default: false },
    initialTitle: { type: String, default: '' },
    saving: { type: Boolean, default: false },
    contentStructure: { type: String, default: 'chapter_subchapter' },
});

const emit = defineEmits(['close', 'submit']);

const title = ref('');
const labels = useBookContentLabels(toRef(props, 'contentStructure'));

watch(
    () => props.show,
    (val) => {
        if (val) {
            title.value = props.initialTitle || '';
        }
    }
);

const modalTitle = computed(() => {
    if (props.mode === 'edit') {
        return `Editar ${props.isSubsection ? labels.childSectionLabel.value : labels.rootSectionLabel.value}`;
    }
    if (props.isSubsection) {
        return `Nueva ${labels.childSectionLabel.value?.toLowerCase() ?? 'sub-sección'}`;
    }
    return `Nuevo ${labels.rootSectionLabel.value.toLowerCase()}`;
});

const placeholder = computed(() => {
    if (props.mode === 'edit') {
        return `Título del ${labels.rootSectionLabel.value.toLowerCase()}`;
    }
    if (props.isSubsection) {
        return 'Ej: 1.1 Introducción';
    }
    return labels.isLevelContent.value ? 'Ej: Nivel 1' : 'Ej: Capítulo 1';
});

const submit = () => {
    const t = title.value?.trim();
    if (!t) return;
    emit('submit', t);
};
</script>

<template>
    <ModalSmall :show="show" :onClose="() => !saving && emit('close')">
        <template #title>{{ modalTitle }}</template>
        <template #message>Los campos con * son obligatorios</template>
        <template #content>
            <div>
                <InputLabel value="Título *" />
                <TextInput v-model="title" type="text" class="w-full" :placeholder="placeholder" />
            </div>
        </template>
        <template #buttons>
            <PrimaryButton :disabled="saving || !title?.trim()" @click="submit">
                <IconLoader v-if="saving" class="w-4 h-4 mr-2 animate-spin inline" />
                Guardar
            </PrimaryButton>
        </template>
    </ModalSmall>
</template>
