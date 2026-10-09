<script setup>
/**
 * Panel lateral de la portería: contadores del día y últimas lecturas.
 *
 * Los contadores no se recalculan en el cliente: vienen de la respuesta del
 * endpoint de escaneo, así que reflejan lo que quedó guardado en la base.
 */
defineProps({
    counters: { type: Object, default: () => ({ total: 0, attended: 0, late: 0, exited: 0 }) },
    rows: { type: Array, default: () => [] },
});

const statusClass = (status) => {
    if (status === 'A') {
        return 'badge bg-success';
    }

    if (status === 'T') {
        return 'badge bg-warning';
    }

    return 'badge bg-secondary';
};
</script>

<template>
    <div class="space-y-4">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="border rounded-md p-3 border-[#ebedf2] dark:border-[#1b2e4b]">
                <p class="text-xs text-white-dark">Entradas</p>
                <p class="text-2xl font-semibold">{{ counters.total }}</p>
            </div>
            <div class="border rounded-md p-3 border-[#ebedf2] dark:border-[#1b2e4b]">
                <p class="text-xs text-white-dark">Asistencia</p>
                <p class="text-2xl font-semibold text-success">{{ counters.attended }}</p>
            </div>
            <div class="border rounded-md p-3 border-[#ebedf2] dark:border-[#1b2e4b]">
                <p class="text-xs text-white-dark">Tardanzas</p>
                <p class="text-2xl font-semibold text-warning">{{ counters.late }}</p>
            </div>
            <div class="border rounded-md p-3 border-[#ebedf2] dark:border-[#1b2e4b]">
                <p class="text-xs text-white-dark">Salidas</p>
                <p class="text-2xl font-semibold text-info">{{ counters.exited }}</p>
            </div>
        </div>

        <div class="border rounded-md border-[#ebedf2] dark:border-[#1b2e4b] overflow-hidden">
            <div class="px-4 py-2 border-b border-[#ebedf2] dark:border-[#1b2e4b]">
                <h4 class="font-semibold text-sm">Últimas lecturas</h4>
            </div>

            <ul v-if="rows.length" class="divide-y divide-[#ebedf2] dark:divide-[#1b2e4b] max-h-72 overflow-y-auto">
                <li v-for="row in rows" :key="row.id" class="px-4 py-2 flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-medium truncate">{{ row.student }}</p>
                        <p class="text-xs text-white-dark truncate">
                            {{ row.section }} · {{ row.code }}
                            <span v-if="row.exit_time"> · salida {{ row.exit_time }}</span>
                        </p>
                    </div>
                    <div class="text-right shrink-0">
                        <span :class="statusClass(row.status)">{{ row.status_label }}</span>
                        <p class="text-xs text-white-dark mt-1">{{ row.entry_time ?? '—' }}</p>
                    </div>
                </li>
            </ul>

            <p v-else class="px-4 py-6 text-sm text-white-dark text-center">
                Todavía no hay lecturas registradas hoy.
            </p>
        </div>
    </div>
</template>
