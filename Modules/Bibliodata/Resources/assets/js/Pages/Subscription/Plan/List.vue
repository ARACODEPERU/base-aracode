<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { bibSwal } from '../../../utils/bibSwal.js';
import axios from 'axios';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Pagination from '@/Components/Pagination.vue';
import { useForm as useSearchForm } from '@inertiajs/vue3';
import IconEdit from '@/Components/vristo/icon/icon-edit.vue';
import IconTrash from '@/Components/vristo/icon/icon-trash.vue';

const props = defineProps({
    plans: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const form = useSearchForm({ search: props.filters.search || '' });

const durationLabel = (plan) => {
    if (plan.duration_type === 'lifetime') return 'Vitalicia';
    if (plan.duration_type === 'monthly') return `${plan.duration_value || 1} mes(es)`;
    if (plan.duration_type === 'yearly') return `${plan.duration_value || 1} año(s)`;
    return plan.duration_type;
};

const deletePlan = (id) => {
    bibSwal({
        title: '¿Eliminar plan?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Eliminar',
        cancelButtonText: 'Cancelar',
        showLoaderOnConfirm: true,
        preConfirm: () =>
            axios.delete(route('bib_subscription_plans_destroy', id)).then((res) => {
                if (res.data && !res.data.success) {
                    Swal.showValidationMessage(res.data.message || 'Error');
                }
                return res;
            }),
    }).then((result) => {
        if (result.isConfirmed) {
            router.visit(route('bib_subscription_plans'), {
                replace: false,
                preserveState: true,
                preserveScroll: true,
                only: ['plans'],
            });
        }
    });
};
</script>

<template>
    <AppLayout title="Planes de suscripción">
        <Navigation
            :routeModule="route('bib_dashboard')"
            :titleModule="'Biblio Data'"
            :data="[{ title: 'Planes de suscripción' }]"
        />
        <div class="pt-5 space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h2 class="text-xl font-semibold">Planes de suscripción</h2>
                <div class="flex gap-3">
                    <form @submit.prevent="router.visit(route('bib_subscription_plans', { search: form.search }))">
                        <input
                            v-model="form.search"
                            type="text"
                            placeholder="Buscar..."
                            class="form-input"
                        />
                    </form>
                    <Link :href="route('bib_subscription_plans_create')">
                        <PrimaryButton>Nuevo plan</PrimaryButton>
                    </Link>
                </div>
            </div>

            <div class="panel overflow-x-auto">
                <table class="table-hover min-w-full text-sm">
                    <thead>
                        <tr>
                            <th class="text-center">Acciones</th>
                            <th>Nombre</th>
                            <th>Código</th>
                            <th>Libro</th>
                            <th>Duración</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="plan in plans.data" :key="plan.id">
                            <td class="text-center space-x-2">
                                <div class="flex items-center justify-center gap-2">
                                    <Link
                                        :href="route('bib_subscription_plans_edit', plan.id)"
                                        v-tippy="{ content: 'Editar', placement: 'bottom' }"
                                        class="text-primary hover:underline"
                                    >
                                        <IconEdit class="w-4 h-4" />
                                    </Link>
                                    <button
                                        type="button"
                                        v-tippy="{ content: 'Eliminar', placement: 'bottom' }"
                                        class="text-danger hover:underline"
                                        @click="deletePlan(plan.id)"
                                    >
                                        <IconTrash class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                            <td class="font-medium">{{ plan.name }}</td>
                            <td>{{ plan.code }}</td>
                            <td>{{ plan.books?.[0]?.title || '—' }}</td>
                            <td>{{ durationLabel(plan) }}</td>
                            <td>
                                <span
                                    class="badge"
                                    :class="plan.is_active ? 'bg-success' : 'bg-secondary'"
                                >
                                    {{ plan.is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>

                        </tr>
                        <tr v-if="!plans.data?.length">
                            <td colspan="6" class="text-center py-8 text-gray-500">No hay planes registrados.</td>
                        </tr>
                    </tbody>
                </table>
                <Pagination :data="plans" />
            </div>
        </div>
    </AppLayout>
</template>
