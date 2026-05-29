<script setup>
import { computed } from 'vue';

const props = defineProps({
    charges: { type: Array, default: () => [] },
    phase: { type: String, required: true },
    title: { type: String, required: true },
});

const filteredCharges = computed(() =>
    props.charges.filter((charge) => charge.phase === props.phase)
);

const reasonLabel = {
    planned: 'Planificado',
    damage: 'Rotura / daño',
    additional_service: 'Servicio adicional',
    other: 'Otro',
};

const formatMoney = (value) => `S/ ${Number(value || 0).toFixed(2)}`;
</script>

<template>
    <div class="panel p-4">
        <h3 class="text-lg font-semibold mb-4">{{ title }}</h3>
        <div v-if="filteredCharges.length" class="table-responsive">
            <table class="table-striped table-hover">
                <thead>
                    <tr>
                        <th>Descripción</th>
                        <th>Motivo</th>
                        <th>Monto</th>
                        <th>Notas</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="charge in filteredCharges" :key="charge.id">
                        <td>{{ charge.description }}</td>
                        <td>{{ reasonLabel[charge.reason] || charge.reason }}</td>
                        <td>{{ formatMoney(charge.amount) }}</td>
                        <td>{{ charge.notes || '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p v-else class="text-gray-500">Sin cargos registrados.</p>
    </div>
</template>
