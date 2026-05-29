<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import RentalExtraChargesPanel from './Partials/RentalExtraChargesPanel.vue';
import AddExtraChargeForm from './Partials/AddExtraChargeForm.vue';
import RentalStatusModal from './Partials/RentalStatusModal.vue';
import RentalPaymentsModal from './Partials/RentalPaymentsModal.vue';
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import Swal2 from 'sweetalert2';

const props = defineProps({
    rental: { type: Object, required: true },
    eventTypes: { type: Array, default: () => [] },
    reservationStatuses: { type: Array, default: () => [] },
});

const showStatusModal = ref(false);
const showPaymentsModal = ref(false);

const eventTypeMap = computed(() =>
    Object.fromEntries(props.eventTypes.map((item) => [item.value, item.label]))
);

const isCancelled = computed(() => props.rental.reservation_status === 'cancelled');

const reservationStatusLabel = {
    pending: { text: 'Pendiente', class: 'bg-gray-100 text-gray-800' },
    confirmed: { text: 'Confirmada', class: 'bg-blue-100 text-blue-800' },
    in_occupation: { text: 'En ocupación', class: 'bg-indigo-100 text-indigo-800' },
    cancelled: { text: 'Cancelada', class: 'bg-red-100 text-red-800' },
    completed: { text: 'Completada', class: 'bg-green-100 text-green-800' },
};

const formatMoney = (value) => `S/ ${Number(value || 0).toFixed(2)}`;
const formatDate = (value) => {
    if (!value) return '-';
    return new Date(value).toLocaleDateString('es-PE');
};
const formatTime = (value) => (value ? String(value).substring(0, 5) : '-');

const showBalanceAlert = computed(() => {
    if (Number(props.rental.balance_amount) <= 0) return false;
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const eventDate = new Date(props.rental.event_date);
    eventDate.setHours(0, 0, 0, 0);
    return eventDate <= today;
});

const deleteRental = () => {
    Swal2.fire({
        title: '¿Eliminar reserva?',
        text: `Reserva #${props.rental.id} — ${props.rental.customer?.full_name || ''}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        padding: '2em',
        customClass: 'sweet-alerts',
        showLoaderOnConfirm: true,
        preConfirm: () => axios.delete(route('even_alquiler_local_destroy', props.rental.id)).then((res) => {
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
            router.visit(route('even_alquiler_local_index'));
        }
    });
};
</script>

<template>
    <AppLayout :title="`Reserva #${rental.id}`">
        <Navigation
            :routeModule="route('even_dashboard')"
            :titleModule="'Eventos sociales'"
            :data="[
                { route: route('even_alquiler_local_index'), title: 'Alquiler de local' },
                { title: `Reserva #${rental.id}` },
            ]"
        />

        <div class="mt-6 w-full space-y-6">
            <div v-if="showBalanceAlert" class="panel p-4 border border-warning bg-warning/10">
                <p class="font-semibold text-warning">
                    Saldo pendiente: {{ formatMoney(rental.balance_amount) }}
                </p>
            </div>

            <div class="panel p-4">
                <div class="flex justify-between items-start flex-wrap gap-4 mb-4">
                    <div class="flex items-center gap-3 flex-wrap">
                        <h2 class="text-xl font-semibold">Resumen de la reserva</h2>
                        <span
                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                            :class="reservationStatusLabel[rental.reservation_status]?.class"
                        >
                            {{ reservationStatusLabel[rental.reservation_status]?.text || rental.reservation_status }}
                        </span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Link
                            v-can="'even_alquiler_local_editar'"
                            :href="route('even_alquiler_local_edit', rental.id)"
                            class="btn btn-outline-primary btn-sm"
                        >
                            Editar
                        </Link>
                        <button
                            v-can="'even_alquiler_local_editar'"
                            type="button"
                            class="btn btn-outline-warning btn-sm"
                            @click="showStatusModal = true"
                        >
                            Cambiar estado
                        </button>
                        <button
                            v-can="'even_alquiler_local_pagos'"
                            type="button"
                            class="btn btn-success btn-sm"
                            @click="showPaymentsModal = true"
                        >
                            Ver pagos
                        </button>
                        <button
                            v-can="'even_alquiler_local_eliminar'"
                            type="button"
                            class="btn btn-outline-danger btn-sm"
                            @click="deleteRental"
                        >
                            Eliminar
                        </button>
                        <Link :href="route('even_alquiler_local_index')" class="btn btn-outline-secondary btn-sm">
                            Volver al listado
                        </Link>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Local</p>
                        <p class="font-medium">{{ rental.local?.description || '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Cliente</p>
                        <p class="font-medium">{{ rental.customer?.full_name || '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Evento</p>
                        <p class="font-medium">{{ eventTypeMap[rental.event_type] || rental.event_type }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Inicio</p>
                        <p class="font-medium">{{ formatDate(rental.event_date) }} {{ formatTime(rental.start_time) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Fin</p>
                        <p class="font-medium">{{ formatDate(rental.event_end_date || rental.event_date) }} {{ formatTime(rental.end_time) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Horas</p>
                        <p class="font-medium">{{ rental.total_hours }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Monto base</p>
                        <p class="font-medium">{{ formatMoney(rental.base_amount) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Total</p>
                        <p class="font-medium">{{ formatMoney(rental.total_price) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Pagado / Saldo</p>
                        <p class="font-medium">
                            {{ formatMoney(rental.paid_amount) }} / {{ formatMoney(rental.balance_amount) }}
                        </p>
                    </div>
                </div>
            </div>

            <RentalExtraChargesPanel
                :charges="rental.extra_charges || []"
                phase="booking"
                title="Acordado al reservar"
            />

            <RentalExtraChargesPanel
                :charges="rental.extra_charges || []"
                phase="during_event"
                title="Durante el evento"
            />

            <AddExtraChargeForm
                v-can="'even_alquiler_local_nuevo'"
                :rental-id="rental.id"
                :disabled="isCancelled"
            />
        </div>

        <RentalStatusModal
            :show="showStatusModal"
            :rental="rental"
            :reservation-statuses="reservationStatuses"
            :reload-only="['rental']"
            @close="showStatusModal = false"
        />

        <RentalPaymentsModal
            :show="showPaymentsModal"
            :rental-id="rental.id"
            :reload-only="['rental']"
            @close="showPaymentsModal = false"
        />
    </AppLayout>
</template>
