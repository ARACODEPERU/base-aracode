<script setup>
import { ref, watch } from 'vue';
import ModalSmall from '@/Components/ModalSmall.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    bookPages: { type: Number, default: 0 },
    sectionPagesCount: { type: Number, default: 0 },
    processing: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'submit']);

const mode = ref('count');
const count = ref(10);
const from = ref(1);
const to = ref(10);

watch(
    () => props.show,
    (val) => {
        if (val) {
            const start = (props.sectionPagesCount || 0) + 1;
            from.value = start;
            to.value = start + 9;
            count.value = props.bookPages > 0 ? Math.min(props.bookPages, 100) : 10;
        }
    }
);

const useBookTotal = () => {
    if (props.bookPages > 0) {
        const start = (props.sectionPagesCount || 0) + 1;
        from.value = start;
        to.value = start + props.bookPages - 1;
        mode.value = 'range';
    }
};

const submit = () => {
    if (mode.value === 'count') {
        emit('submit', { count: parseInt(count.value, 10) });
    } else {
        emit('submit', { from: parseInt(from.value, 10), to: parseInt(to.value, 10) });
    }
};
</script>

<template>
    <ModalSmall :show="show" :onClose="() => emit('close')">
        <template #title>Generar páginas vacías</template>
        <template #content>
            <p class="text-sm text-gray-500 mb-4">
                Se crearán páginas sin contenido. Máximo 2000 por operación. Puedes repetir para libros muy largos.
            </p>

            <div class="flex gap-2 mb-4">
                <button
                    type="button"
                    class="px-3 py-1 text-xs rounded-full border"
                    :class="mode === 'count' ? 'bg-primary text-white border-primary' : 'border-gray-300'"
                    @click="mode = 'count'"
                >
                    Por cantidad
                </button>
                <button
                    type="button"
                    class="px-3 py-1 text-xs rounded-full border"
                    :class="mode === 'range' ? 'bg-primary text-white border-primary' : 'border-gray-300'"
                    @click="mode = 'range'"
                >
                    Por rango
                </button>
            </div>

            <div v-if="mode === 'count'" class="space-y-3">
                <div>
                    <InputLabel value="Cantidad de páginas a crear" />
                    <TextInput v-model="count" type="number" class="w-full" min="1" max="2000" />
                </div>
                <button
                    v-if="bookPages > 0"
                    type="button"
                    class="text-xs text-primary hover:underline"
                    @click="count = bookPages"
                >
                    Usar N° del libro ({{ bookPages }} páginas)
                </button>
            </div>

            <div v-else class="grid grid-cols-2 gap-3">
                <div>
                    <InputLabel value="Desde página" />
                    <TextInput v-model="from" type="number" class="w-full" min="1" />
                </div>
                <div>
                    <InputLabel value="Hasta página" />
                    <TextInput v-model="to" type="number" class="w-full" min="1" />
                </div>
                <button
                    v-if="bookPages > 0"
                    type="button"
                    class="col-span-2 text-xs text-primary hover:underline text-left"
                    @click="useBookTotal"
                >
                    Rango según N° de páginas del libro ({{ bookPages }})
                </button>
            </div>
        </template>
        <template #buttons>
            <PrimaryButton :disabled="processing" @click="submit">
                <IconLoader v-if="processing" class="w-4 h-4 animate-spin inline mr-1" />
                Generar
            </PrimaryButton>
        </template>
    </ModalSmall>
</template>
