<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import Keypad from '@/Components/Keypad.vue';
import Pagination from '@/Components/Pagination.vue';
import RentalsTable from './Partials/RentalsTable.vue';
import RentalStatusModal from './Partials/RentalStatusModal.vue';
import RentalPaymentsModal from './Partials/RentalPaymentsModal.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Swal2 from 'sweetalert2';

const props = defineProps({
    rentals: { type: Object, default: () => ({}) },
    locals: { type: Array, default: () => [] },
    activeRates: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    eventTypes: { type: Array, default: () => [] },
    reservationStatuses: { type: Array, default: () => [] },
});

const form = useForm({
    search: props.filters.search || '',
    local_id: props.filters.local_id || '',
    event_date: props.filters.event_date || '',
});

const showStatusModal = ref(false);
const showPaymentsModal = ref(false);
const selectedRental = ref(null);
const selectedRentalId = ref(null);

const applyFilters = () => {
    form.get(route('even_alquiler_local_index'), {
        preserveState: true,
        preserveScroll: true,
        only: ['rentals', 'filters'],
    });
};

watch(() => [form.local_id, form.event_date], () => {
    applyFilters();
});

const openStatusModal = (rental) => {
    selectedRental.value = rental;
    showStatusModal.value = true;
};

const closeStatusModal = () => {
    showStatusModal.value = false;
    selectedRental.value = null;
};

const openPaymentsModal = (rental) => {
    selectedRentalId.value = rental.id;
    selectedRental.value = rental;
    showPaymentsModal.value = true;
};

const closePaymentsModal = () => {
    showPaymentsModal.value = false;
    selectedRentalId.value = null;
    selectedRental.value = null;
};

const deleteRental = (rental) => {
    Swal2.fire({
        title: '¿Eliminar reserva?',
        text: `Reserva #${rental.id} — ${rental.customer?.full_name || ''}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        padding: '2em',
        customClass: 'sweet-alerts',
        showLoaderOnConfirm: true,
        preConfirm: () => axios.delete(route('even_alquiler_local_destroy', rental.id)).then((res) => {
            if (!res.data.success) {
                Swal2.showValidationMessage(res.data.message);
            }
            return res;
        }),
        allowOutsideClick: () => !Swal2.isLoading(),
    }).then((result) => {
        if (result.isConfirmed) {
            Swal2.fire({
                title: 'Enhorabuena',
                text: 'Reserva eliminada correctamente',
                icon: 'success',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
            router.reload({ only: ['rentals'], preserveScroll: true });
        }
    });
};
</script>

<template>
    <AppLayout title="Alquiler de local">
        <Navigation
            :routeModule="route('even_dashboard')"
            :titleModule="'Eventos sociales'"
            :data="[{ title: 'Alquiler de local' }]"
        />
        <div class="mt-6 w-full">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 w-full">
                    <div>
                        <form @submit.prevent="applyFilters">
                            <label class="sr-only">Buscar</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <input
                                    v-model="form.search"
                                    type="text"
                                    class="block pl-10 form-input w-full"
                                    placeholder="Buscar por cliente o local"
                                />
                            </div>
                        </form>
                    </div>
                    <div>
                        <select v-model="form.local_id" class="form-select w-full">
                            <option value="">Todos los locales</option>
                            <option v-for="local in locals" :key="local.id" :value="local.id">
                                {{ local.description }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <input v-model="form.event_date" type="date" class="form-input w-full" />
                    </div>
                    <div class="flex justify-end">
                        <Keypad>
                            <template #botones>
                                <Link
                                    v-can="'even_alquiler_local_nuevo'"
                                    :href="route('even_alquiler_local_create')"
                                    class="flex items-center justify-center inline-block px-6 py-2.5 bg-blue-900 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-blue-700"
                                >
                                    Nueva reserva
                                </Link>
                            </template>
                        </Keypad>
                    </div>
                </div>
            </div>

            <RentalsTable
                :rentals="rentals"
                :event-types="eventTypes"
                :reservation-statuses="reservationStatuses"
                @change-status="openStatusModal"
                @view-payments="openPaymentsModal"
                @delete-rental="deleteRental"
            />

            <div class="mt-4">
                <Pagination :links="rentals.links" />
            </div>
        </div>

        <RentalStatusModal
            :show="showStatusModal"
            :rental="selectedRental"
            :reservation-statuses="reservationStatuses"
            @close="closeStatusModal"
        />

        <RentalPaymentsModal
            :show="showPaymentsModal"
            :rental-id="selectedRentalId"
            @close="closePaymentsModal"
        />
    </AppLayout>
</template>
