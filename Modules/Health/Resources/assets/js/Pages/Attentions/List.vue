<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import Pagination from '@/Components/Pagination.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import IconSearch from '@/Components/vristo/icon/icon-search.vue';
import IconPlus from '@/Components/vristo/icon/icon-plus.vue';
import IconPencil from '@/Components/vristo/icon/icon-pencil.vue';
import IconTrash from '@/Components/vristo/icon/icon-trash.vue';
import IconTooth from '@/Components/vristo/icon/icon-tooth.vue';
import IconHeart from '@/Components/vristo/icon/icon-heart.vue';
import { serviceTypeOption } from '@/Components/Health/healthOptions.js';
import Swal from 'sweetalert2';
import PendingSignatureReminder from '../../Components/PendingSignatureReminder.vue';

const props = defineProps({
    attentions: {
        type: Object,
        default: () => ({}),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    pendingSignatures: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    search: props.filters.search,
});

const search = () => {
    form.get(route('heal_attentions_list'), {
        preserveState: true,
        replace: true,
    });
};

const formatDate = (value) => {
    if (!value) {
        return '';
    }

    return new Intl.DateTimeFormat('es-PE', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};

const deleteAttention = (attention) => {
    if (attention.signed_at) {
        Swal.fire({
            icon: 'info',
            title: 'Atención firmada',
            text: 'Esta atención ya fue firmada y no puede eliminarse.',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
        return;
    }

    Swal.fire({
        title: 'Eliminar atención',
        html: `
            <div class="flex flex-col items-center gap-3">
                <div class="flex h-20 w-20 items-center justify-center rounded-full bg-danger/10 text-danger">
                    <svg width="52" height="52" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M20.5 6H3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        <path d="M18.833 8.5L18.373 15.399C18.197 18.054 18.108 19.382 17.243 20.191C16.378 21 15.048 21 12.387 21H11.613C8.953 21 7.622 21 6.757 20.191C5.892 19.382 5.804 18.054 5.627 15.399L5.167 8.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        <path opacity="0.5" d="M6.5 6C7.432 5.978 8.159 5.455 8.439 4.68L8.571 4.286C8.654 4.037 8.696 3.913 8.751 3.807C8.97 3.386 9.376 3.094 9.845 3.019C9.962 3 10.093 3 10.355 3H13.645C13.907 3 14.038 3 14.155 3.019C14.624 3.094 15.03 3.386 15.249 3.807C15.304 3.913 15.346 4.037 15.429 4.286L15.561 4.68C15.841 5.455 16.568 5.978 17.5 6" stroke="currentColor" stroke-width="1.8" />
                    </svg>
                </div>
                <p class="m-0 text-sm text-white-dark">No podrás revertir esta acción.</p>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Eliminar',
        cancelButtonText: 'Cancelar',
        customClass: {
            popup: 'sweet-alerts',
            confirmButton: 'btn btn-danger',
            cancelButton: 'btn btn-dark ltr:mr-3 rtl:ml-3',
        },
        buttonsStyling: false,
        reverseButtons: true,
        padding: '2em',
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }

        axios.delete(route('heal_attentions_destroy', attention.id)).then(() => {
            router.reload({ only: ['attentions'] });
            Swal.fire({
                icon: 'success',
                title: 'Eliminado',
                text: 'La atención fue eliminada correctamente.',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        });
    });
};
</script>

<template>
    <AppLayout title="Atenciones">
        <PendingSignatureReminder :items="pendingSignatures" />

        <Navigation :routeModule="route('health_dashboard')" :titleModule="'Salud'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Atenciones</span>
            </li>
        </Navigation>

        <div class="pt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl">Atenciones</h2>
                <div class="flex sm:flex-row flex-col sm:items-center gap-3 w-full sm:w-auto">
                    <Link :href="route('heal_attentions_create')" class="btn btn-primary">
                        <IconPlus class="w-4 h-4 ltr:mr-2 rtl:ml-2" />
                        Nuevo
                    </Link>

                    <div class="relative">
                        <input
                            v-model="form.search"
                            type="text"
                            placeholder="Buscar paciente"
                            class="form-input py-2 ltr:pr-11 rtl:pl-11 peer"
                            @keyup.enter="search"
                        />
                        <button type="button" class="absolute ltr:right-[11px] rtl:left-[11px] top-1/2 -translate-y-1/2 peer-focus:text-primary" @click="search">
                            <IconSearch class="mx-auto" />
                        </button>
                    </div>
                </div>
            </div>

            <div class="panel p-0 border-0 overflow-hidden mt-5">
                <Pagination :data="attentions">
                    <div class="table-responsive">
                        <table class="table-striped table-hover">
                            <thead>
                                <tr>
                                    <th class="!text-center">Acciones</th>
                                    <th>Momento</th>
                                    <th>Tipo</th>
                                    <th>Firma</th>
                                    <th>Historia</th>
                                    <th>Paciente</th>
                                    <th>Doctor</th>
                                    <th>Relato</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="attention in attentions.data" :key="attention.id">
                                    <td>
                                        <div class="flex gap-1 items-center justify-center">
                                            <Link
                                                :href="route('heal_attentions_edit', attention.id)"
                                                class="btn btn-sm btn-outline-primary"
                                                v-tippy="{ content: 'Editar', placement: 'bottom' }"
                                            >
                                                <IconPencil class="w-4 h-4" />
                                            </Link>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                :disabled="Boolean(attention.signed_at)"
                                                v-tippy="{ content: 'Eliminar', placement: 'bottom' }"
                                                @click="deleteAttention(attention)"
                                            >
                                                <IconTrash class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>
                                    <td>{{ formatDate(attention.attention_at) }}</td>
                                    <td>
                                        <span v-if="serviceTypeOption(attention.service_type).dental" class="badge bg-info inline-flex items-center gap-1">
                                            <IconTooth class="w-4 h-4" />
                                            {{ serviceTypeOption(attention.service_type).label }}
                                        </span>
                                        <span v-else class="badge bg-primary inline-flex items-center gap-1">
                                            <IconHeart class="w-4 h-4" />
                                            {{ serviceTypeOption(attention.service_type).label }}
                                        </span>
                                    </td>
                                    <td>
                                        <span v-if="attention.signed_at" class="badge bg-success">Firmada</span>
                                        <span v-else class="badge bg-warning">Pendiente</span>
                                    </td>
                                    <td>{{ attention.history?.history_code }}</td>
                                    <td>{{ attention.patient?.person?.full_name }}</td>
                                    <td>{{ attention.doctor?.person?.full_name }}</td>
                                    <td class="max-w-[280px] truncate">{{ attention.patient_story }}</td>
                                </tr>
                                <tr v-if="!attentions.data?.length">
                                    <td colspan="8" class="text-center py-8 text-white-dark">
                                        No existen atenciones registradas.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Pagination>
            </div>
        </div>
    </AppLayout>
</template>
