<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { bibSwal } from '../../../utils/bibSwal.js';
import axios from 'axios';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Pagination from '@/Components/Pagination.vue';
import IconEdit from '@/Components/vristo/icon/icon-edit.vue';
import IconXCircle from '@/Components/vristo/icon/icon-x-circle.vue';

const props = defineProps({
    subscriptions: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
    plans: { type: Array, default: () => [] },
    books: { type: Array, default: () => [] },
});

const form = useForm({
    search: props.filters.search || '',
    status: props.filters.status || '',
    plan_id: props.filters.plan_id || '',
    book_id: props.filters.book_id || '',
    subscriber_type: props.filters.subscriber_type || '',
});

const applyFilters = () => {
    router.visit(route('bib_subscriptions', { ...form.data() }));
};

const statusBadge = (sub) => {
    const s = sub.effective_status || sub.status;
    const map = {
        active: 'bg-success',
        pending: 'bg-warning',
        expired: 'bg-secondary',
        cancelled: 'bg-danger',
        suspended: 'bg-dark',
    };
    return map[s] || 'bg-secondary';
};

const statusLabel = (sub) => {
    const labels = {
        active: 'Activa',
        pending: 'Pendiente',
        expired: 'Expirada',
        cancelled: 'Cancelada',
        suspended: 'Suspendida',
    };
    return labels[sub.effective_status || sub.status] || sub.status;
};

const subscriberName = (sub) => {
    if (sub.subscriber_type === 'individual') {
        return sub.user?.name || '—';
    }
    return sub.organization?.name || '—';
};

const cancelSub = (id) => {
    bibSwal({
        title: '¿Cancelar suscripción?',
        text: 'La suscripción quedará en estado cancelada.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, cancelar',
        cancelButtonText: 'Volver',
        showLoaderOnConfirm: true,
        preConfirm: () =>
            axios.post(route('bib_subscriptions_cancel', id)).then((res) => {
                if (res.data && !res.data.success) {
                    Swal.showValidationMessage(res.data.message || 'Error');
                }
                return res;
            }),
    }).then((result) => {
        if (result.isConfirmed) {
            router.visit(route('bib_subscriptions'), {
                replace: false,
                preserveState: true,
                preserveScroll: true,
                only: ['subscriptions']
            });
        }
    });
};
</script>

<template>
    <AppLayout title="Suscripciones">
        <Navigation
            :routeModule="route('bib_dashboard')"
            :titleModule="'Biblio Data'"
            :data="[{ title: 'Suscripciones' }]"
        />
        <div class="pt-5 space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h2 class="text-xl font-semibold">Suscripciones</h2>
                <Link :href="route('bib_subscriptions_create')">
                    <PrimaryButton>Nueva suscripción</PrimaryButton>
                </Link>
            </div>

            <form class="panel grid gap-3 md:grid-cols-5" @submit.prevent="applyFilters">
                <input v-model="form.search" type="text" placeholder="Buscar..." class="form-input" />
                <select v-model="form.status" class="form-select">
                    <option value="">Todos los estados</option>
                    <option value="active">Activa</option>
                    <option value="pending">Pendiente</option>
                    <option value="expired">Expirada</option>
                    <option value="cancelled">Cancelada</option>
                    <option value="suspended">Suspendida</option>
                </select>
                <select v-model="form.plan_id" class="form-select">
                    <option value="">Todos los planes</option>
                    <option v-for="p in plans" :key="p.id" :value="p.id">{{ p.name }}</option>
                </select>
                <select v-model="form.book_id" class="form-select">
                    <option value="">Todos los libros</option>
                    <option v-for="b in books" :key="b.id" :value="b.id">{{ b.title }}</option>
                </select>
                <select v-model="form.subscriber_type" class="form-select">
                    <option value="">Todos los tipos</option>
                    <option value="individual">Individual</option>
                    <option value="organization">Organización</option>
                </select>
                <div class="md:col-span-5">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                </div>
            </form>

            <div class="panel overflow-x-auto">
                <table class="table-hover min-w-full text-sm">
                    <thead>
                        <tr>
                            <th class="text-center">Acciones</th>
                            <th>Suscriptor</th>
                            <th>Plan</th>
                            <th>Libro</th>
                            <th>Inicio</th>
                            <th>Fin</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="sub in subscriptions.data" :key="sub.id">
                            <td class="text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <Link
                                        :href="route('bib_subscriptions_edit', sub.id)"
                                        class="text-primary hover:underline"
                                        title="Editar"
                                    >
                                        <IconEdit class="w-4 h-4" />
                                    </Link>
                                    <button
                                        v-if="sub.status !== 'cancelled'"
                                        type="button"
                                        class="text-danger hover:underline"
                                        title="Cancelar suscripción"
                                        @click="cancelSub(sub.id)"
                                    >
                                        <IconXCircle class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                            <td>
                                <span class="block font-medium">{{ subscriberName(sub) }}</span>
                                <span class="text-xs text-gray-500">
                                    {{ sub.subscriber_type === 'individual' ? 'Individual' : 'Organización' }}
                                </span>
                            </td>
                            <td>{{ sub.plan?.name }}</td>
                            <td>{{ sub.book?.title || '—' }}</td>
                            <td>{{ sub.starts_at }}</td>
                            <td>{{ sub.ends_at || 'Vitalicia' }}</td>
                            <td>
                                <span class="badge" :class="statusBadge(sub)">{{ statusLabel(sub) }}</span>
                            </td>
                        </tr>
                        <tr v-if="!subscriptions.data?.length">
                            <td colspan="7" class="text-center py-8 text-gray-500">No hay suscripciones.</td>
                        </tr>
                    </tbody>
                </table>
                <Pagination :data="subscriptions" />
            </div>
        </div>
    </AppLayout>
</template>
