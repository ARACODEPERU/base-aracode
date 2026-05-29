<script setup>
import ModalSmall from '@/Components/ModalSmall.vue';
import InputLabel from '@/Components/InputLabel.vue';
import GreenButton from '@/Components/GreenButton.vue';
import iconLoader from '@/Components/vristo/icon/icon-loader.vue';
import Swal2 from 'sweetalert2';
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    show: { type: Boolean, default: false },
    rental: { type: Object, default: null },
    reservationStatuses: { type: Array, default: () => [] },
    reloadOnly: { type: Array, default: () => ['rentals'] },
});

const emit = defineEmits(['close']);

const status = ref('pending');
const saving = ref(false);

watch(() => props.rental, (rental) => {
    if (rental) {
        status.value = rental.reservation_status;
    }
}, { immediate: true });

const close = () => emit('close');

const saveStatus = async () => {
    if (!props.rental) return;

    saving.value = true;

    try {
        await axios.put(route('even_alquiler_local_update_status', props.rental.id), {
            reservation_status: status.value,
        });

        close();

        Swal2.fire({
            title: 'Enhorabuena',
            text: 'Estado actualizado correctamente',
            icon: 'success',
            padding: '2em',
            customClass: 'sweet-alerts',
        });

        router.reload({ only: props.reloadOnly, preserveScroll: true });
    } catch (error) {
        Swal2.fire({
            icon: 'error',
            text: error.response?.data?.message || 'No se pudo actualizar el estado.',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    } finally {
        saving.value = false;
    }
};
</script>

<template>
    <ModalSmall :show="show" :on-close="close" :icon="'/img/calendario.png'">
        <template #title>Cambiar estado</template>
        <template #message>Reserva #{{ rental?.id }} — {{ rental?.customer?.full_name }}</template>
        <template #content>
            <InputLabel value="Estado de reserva" />
            <select v-model="status" class="form-select w-full">
                <option v-for="item in reservationStatuses" :key="item.value" :value="item.value">
                    {{ item.label }}
                </option>
            </select>
        </template>
        <template #buttons>
            <GreenButton :disabled="saving" @click="saveStatus">
                <icon-loader v-if="saving" class="w-4 h-4 animate-spin mr-1" />
                Guardar
            </GreenButton>
        </template>
    </ModalSmall>
</template>
