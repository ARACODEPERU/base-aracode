<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    defaults: { type: Object, required: true },
    rates: { type: Array, default: () => [] },
});

const defaultsForm = useForm({ ...props.defaults });
const rateForm = useForm({ name: '', amount: null, description: '' });

const saveDefaults = () => defaultsForm.put(route('even_organizations.defaults'), { preserveScroll: true });
const createRate = () => rateForm.post(route('even_organizations.rates.store'), {
    preserveScroll: true,
    onSuccess: () => rateForm.reset(),
});
</script>

<template>
    <AppLayout title="Tarifas deportivas">
        <Navigation :routeModule="route('even_dashboard')" titleModule="GESTIÓN DEPORTIVA" :data="[{ title: 'Tarifas' }]" />
        <main class="mx-auto max-w-7xl space-y-6 p-4 md:p-6">
            <section class="panel space-y-4">
                <h1 class="text-lg font-semibold">Tarifas por equipo y partido</h1>
                <p class="text-sm text-gray-500">Cargo que se cobra a cada organización por equipo y partido. Las cuentas se administran en la vista de cuentas.</p>

                <div class="overflow-x-auto">
                    <table class="table-hover w-full text-sm">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th class="text-right">Monto (S/)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="rate in rates" :key="rate.id">
                                <td class="font-semibold">{{ rate.name }}</td>
                                <td>{{ rate.description || '—' }}</td>
                                <td class="text-right">{{ Number(rate.amount).toFixed(2) }}</td>
                            </tr>
                            <tr v-if="!rates.length">
                                <td colspan="3" class="text-center text-gray-500">Todavía no hay tarifas creadas.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <form class="grid gap-4 md:grid-cols-4" @submit.prevent="createRate">
                    <label class="space-y-1 text-sm">Nombre
                        <input v-model="rateForm.name" class="form-input" type="text" maxlength="120" required />
                    </label>
                    <label class="space-y-1 text-sm">Monto por equipo y partido (S/)
                        <input v-model.number="rateForm.amount" class="form-input" type="number" min="0" step="0.01" required />
                    </label>
                    <label class="space-y-1 text-sm">Descripción
                        <input v-model="rateForm.description" class="form-input" type="text" maxlength="255" />
                    </label>
                    <button class="btn btn-primary self-end" :disabled="rateForm.processing">Crear tarifa</button>
                </form>
                <p v-if="Object.keys(rateForm.errors).length" class="text-sm text-red-600">{{ Object.values(rateForm.errors).join(' ') }}</p>
            </section>

            <section class="panel space-y-4">
                <h2 class="text-lg font-semibold">Valores predeterminados</h2>
                <form class="grid gap-4 md:grid-cols-4" @submit.prevent="saveDefaults">
                    <label class="space-y-1 text-sm">Días de prueba
                        <input v-model.number="defaultsForm.trial_days" class="form-input" type="number" min="1" max="3660" required />
                    </label>
                    <label class="space-y-1 text-sm">Tarifa por equipo y partido (S/)
                        <input v-model.number="defaultsForm.default_match_rate" class="form-input" type="number" min="0" step="0.01" required />
                    </label>
                    <label class="space-y-1 text-sm">Precio por usuario adicional (S/)
                        <input v-model.number="defaultsForm.seat_price" class="form-input" type="number" min="0" step="0.01" required />
                        <span class="block text-xs text-gray-500">Es el precio que paga la organización por cada espacio nuevo.</span>
                    </label>
                    <button class="btn btn-primary self-end" :disabled="defaultsForm.processing">Guardar valores</button>
                </form>
                <p v-if="defaultsForm.recentlySuccessful" class="text-sm text-green-600">Valores guardados correctamente.</p>
                <p v-if="Object.keys(defaultsForm.errors).length" class="text-sm text-red-600">{{ Object.values(defaultsForm.errors).join(' ') }}</p>
            </section>
        </main>
    </AppLayout>
</template>
