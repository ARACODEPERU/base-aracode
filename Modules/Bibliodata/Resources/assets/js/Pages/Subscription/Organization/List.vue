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
import IconTrash from '@/Components/vristo/icon/icon-trash.vue';

const props = defineProps({
    organizations: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const form = useForm({ search: props.filters.search || '' });

const deleteOrg = (id) => {
    bibSwal({
        title: '¿Eliminar organización?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Eliminar',
        cancelButtonText: 'Cancelar',
        showLoaderOnConfirm: true,
        preConfirm: () =>
            axios.delete(route('bib_organizations_destroy', id)).then((res) => {
                if (res.data && !res.data.success) {
                    Swal.showValidationMessage(res.data.message || 'Error');
                }
                return res;
            }),
    }).then((result) => {
        if (result.isConfirmed) {
            router.visit(route('bib_organizations'), {
                replace: false,
                preserveState: true,
                preserveScroll: true,
                only: ['organizations']
            });
        }
    });
};
</script>

<template>
    <AppLayout title="Organizaciones">
        <Navigation
            :routeModule="route('bib_dashboard')"
            :titleModule="'Biblio Data'"
            :data="[{ title: 'Organizaciones' }]"
        />
        <div class="pt-5 space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h2 class="text-xl font-semibold">Organizaciones</h2>
                <div class="flex gap-3">
                    <form @submit.prevent="router.visit(route('bib_organizations', { search: form.search }))">
                        <input v-model="form.search" type="text" placeholder="Buscar..." class="form-input" />
                    </form>
                    <Link :href="route('bib_organizations_create')">
                        <PrimaryButton>Nueva organización</PrimaryButton>
                    </Link>
                </div>
            </div>

            <div class="panel overflow-x-auto">
                <table class="table-hover min-w-full text-sm">
                    <thead>
                        <tr>
                            <th class="text-center">Acciones</th>
                            <th>Nombre</th>
                            <th>Documento</th>
                            <th>Miembros</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="org in organizations.data" :key="org.id">
                            <td class="text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <Link
                                        :href="route('bib_organizations_edit', org.id)"
                                        class="text-primary hover:underline"
                                    >
                                        <IconEdit class="w-4 h-4" />
                                    </Link>
                                    <button
                                        type="button"
                                        class="text-danger hover:underline"
                                        @click="deleteOrg(org.id)"
                                    >
                                        <IconTrash class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                            <td class="font-medium">{{ org.name }}</td>
                            <td>{{ org.document_number || '—' }}</td>
                            <td>{{ org.organization_users_count }}</td>
                            <td>
                                <span class="badge" :class="org.status === 'active' ? 'bg-success' : 'bg-secondary'">
                                    {{ org.status === 'active' ? 'Activa' : 'Inactiva' }}
                                </span>
                            </td>
                        </tr>
                        <tr v-if="!organizations.data?.length">
                            <td colspan="5" class="text-center py-8 text-gray-500">No hay organizaciones.</td>
                        </tr>
                    </tbody>
                </table>
                <Pagination :data="organizations" />
            </div>
        </div>
    </AppLayout>
</template>
