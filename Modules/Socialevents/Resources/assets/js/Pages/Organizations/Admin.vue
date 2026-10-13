<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    defaults: { type: Object, required: true },
    organizations: { type: Array, default: () => [] },
});

const defaultsForm = useForm({ ...props.defaults });
const forms = Object.fromEntries(props.organizations.map((organization) => [organization.id, useForm({
    match_rate: organization.match_rate,
    status: organization.status,
    trial_days: organization.trial_days || props.defaults.trial_days,
    access_until: organization.access_until?.slice(0, 10) || '',
    credit_amount: null,
    reason: '',
})]));

const saveDefaults = () => defaultsForm.put(route('even_organizations.defaults'), { preserveScroll: true });
const saveOrganization = (organizationId) => forms[organizationId].put(route('even_organizations.update', organizationId), { preserveScroll: true });
</script>

<template>
    <AppLayout title="Administración deportiva">
        <Navigation :routeModule="route('even_dashboard')" titleModule="GESTIÓN DEPORTIVA" :data="[{ title: 'Organizaciones' }]" />
        <main class="mx-auto max-w-7xl space-y-6 p-4 md:p-6">
            <section class="panel space-y-4">
                <h1 class="text-lg font-semibold">Valores predeterminados</h1>
                <form class="grid gap-4 md:grid-cols-3" @submit.prevent="saveDefaults">
                    <label class="space-y-1 text-sm">Días de prueba
                        <input v-model.number="defaultsForm.trial_days" class="form-input" type="number" min="1" max="3660" required />
                    </label>
                    <label class="space-y-1 text-sm">Tarifa por equipo y partido (S/)
                        <input v-model.number="defaultsForm.default_match_rate" class="form-input" type="number" min="0" step="0.01" required />
                    </label>
                    <button class="btn btn-primary self-end" :disabled="defaultsForm.processing">Guardar valores</button>
                </form>
            </section>

            <section class="space-y-4">
                <article v-for="organization in organizations.filter((item) => !item.is_legacy)" :key="organization.id" class="panel space-y-4">
                    <header class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h2 class="font-semibold">{{ organization.name }}</h2>
                            <p class="text-sm text-gray-500">{{ organization.owner_name || 'Sin titular' }} · {{ organization.owner_email || '' }}</p>
                        </div>
                        <span class="rounded-full bg-primary/10 px-3 py-1 text-sm">Saldo: S/ {{ Number(organization.prepaid_balance).toFixed(2) }}</span>
                    </header>
                    <form class="grid gap-4 md:grid-cols-3" @submit.prevent="saveOrganization(organization.id)">
                        <label class="space-y-1 text-sm">Estado
                            <select v-model="forms[organization.id].status" class="form-select">
                                <option value="trial">Prueba</option>
                                <option value="active">Activo</option>
                                <option value="sponsored">Cortesía / patrocinio</option>
                                <option value="suspended">Suspendido</option>
                            </select>
                        </label>
                        <label class="space-y-1 text-sm">Tarifa por equipo y partido (S/)
                            <input v-model.number="forms[organization.id].match_rate" class="form-input" type="number" min="0" step="0.01" required />
                        </label>
                        <label class="space-y-1 text-sm">Días de prueba
                            <input v-model.number="forms[organization.id].trial_days" class="form-input" type="number" min="1" max="3660" required />
                        </label>
                        <label class="space-y-1 text-sm">Acceso hasta
                            <input v-model="forms[organization.id].access_until" class="form-input" type="date" />
                        </label>
                        <label class="space-y-1 text-sm">Crédito manual (S/)
                            <input v-model.number="forms[organization.id].credit_amount" class="form-input" type="number" min="0" step="0.01" placeholder="0.00" />
                        </label>
                        <label class="space-y-1 text-sm md:col-span-2">Motivo obligatorio para auditoría
                            <input v-model="forms[organization.id].reason" class="form-input" maxlength="1000" required />
                        </label>
                        <button class="btn btn-primary self-end" :disabled="forms[organization.id].processing">Guardar y auditar</button>
                    </form>
                    <p class="text-xs text-gray-500">Prueba hasta: {{ organization.trial_ends_at || 'No configurada' }} · Acceso hasta: {{ organization.access_until || 'No configurado' }}</p>
                </article>
                <div v-if="!organizations.some((item) => !item.is_legacy)" class="panel text-sm text-gray-500">Todavía no hay organizaciones registradas.</div>
            </section>
        </main>
    </AppLayout>
</template>
