<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    rentals: { type: Object, required: true },
    eventTypes: { type: Array, default: () => [] },
    reservationStatuses: { type: Array, default: () => [] },
});

defineEmits(['change-status', 'view-payments', 'delete-rental']);

const eventTypeMap = computed(() =>
    Object.fromEntries(props.eventTypes.map((item) => [item.value, item.label]))
);

const paymentStatusLabel = {
    pending: { text: 'Pendiente', class: 'bg-red-100 text-red-800 dark:bg-red-800/30 dark:text-red-400' },
    partial_20: { text: 'Adelanto 20%', class: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800/30 dark:text-yellow-400' },
    paid_full: { text: 'Pagado', class: 'bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-400' },
};

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
    const date = new Date(value);
    return date.toLocaleDateString('es-PE');
};
const formatTime = (value) => (value ? String(value).substring(0, 5) : '-');

const formatSchedule = (rental) => {
    const startDate = formatDate(rental.event_date);
    const endDate = formatDate(rental.event_end_date || rental.event_date);
    const startTime = formatTime(rental.start_time);
    const endTime = formatTime(rental.end_time);

    if (startDate === endDate) {
        return `${startDate} ${startTime} - ${endTime}`;
    }

    return `${startDate} ${startTime} → ${endDate} ${endTime}`;
};
</script>

<template>
    <div class="mt-5 panel p-0 border-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table-striped table-hover">
                <thead>
                    <tr>
                        <th>Acciones</th>
                        <th>Fecha</th>
                        <th>Local</th>
                        <th>Cliente</th>
                        <th>Horario</th>
                        <th>Evento</th>
                        <th>Total</th>
                        <th>Pagado</th>
                        <th>Saldo</th>
                        <th>Pago</th>
                        <th>Reserva</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="rental in rentals.data" :key="rental.id">
                        <td>
                            <div class="flex flex-wrap gap-1">
                                <Link
                                    :href="route('even_alquiler_local_show', rental.id)"
                                    class="btn btn-sm btn-primary"
                                >
                                    Ver
                                </Link>
                                <Link
                                    v-can="'even_alquiler_local_editar'"
                                    :href="route('even_alquiler_local_edit', rental.id)"
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    Editar
                                </Link>
                                <button
                                    v-can="'even_alquiler_local_editar'"
                                    type="button"
                                    class="btn btn-sm btn-outline-warning"
                                    @click="$emit('change-status', rental)"
                                >
                                    Estado
                                </button>
                                <button
                                    v-can="'even_alquiler_local_pagos'"
                                    type="button"
                                    class="btn btn-sm btn-success"
                                    @click="$emit('view-payments', rental)"
                                >
                                    Ver pagos
                                </button>
                                <button
                                    v-can="'even_alquiler_local_eliminar'"
                                    type="button"
                                    class="btn btn-sm btn-outline-danger"
                                    @click="$emit('delete-rental', rental)"
                                >
                                    Eliminar
                                </button>
                            </div>
                        </td>
                        <td>{{ formatDate(rental.event_date) }}</td>
                        <td>{{ rental.local?.description || '-' }}</td>
                        <td>{{ rental.customer?.full_name || '-' }}</td>
                        <td>{{ formatSchedule(rental) }}</td>
                        <td>{{ eventTypeMap[rental.event_type] || rental.event_type }}</td>
                        <td>{{ formatMoney(rental.total_price) }}</td>
                        <td>{{ formatMoney(rental.paid_amount) }}</td>
                        <td>{{ formatMoney(rental.balance_amount) }}</td>
                        <td>
                            <span
                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                :class="paymentStatusLabel[rental.payment_status]?.class"
                            >
                                {{ paymentStatusLabel[rental.payment_status]?.text || rental.payment_status }}
                            </span>
                        </td>
                        <td>
                            <span
                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                :class="reservationStatusLabel[rental.reservation_status]?.class"
                            >
                                {{ reservationStatusLabel[rental.reservation_status]?.text || rental.reservation_status }}
                            </span>
                        </td>
                    </tr>
                    <tr v-if="!rentals.data?.length">
                        <td colspan="11" class="text-center py-6 text-gray-500">
                            No hay reservas registradas.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
