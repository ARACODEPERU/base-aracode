<script setup>
import { useForm } from "@inertiajs/vue3";
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { faTimes, faEye, faCheck, faReply, faClock } from "@fortawesome/free-solid-svg-icons";
import Keypad from '@/Components/Keypad.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import FlatPickr from 'vue-flatpickr-component';
import 'flatpickr/dist/flatpickr.css';
import { Spanish } from "flatpickr/dist/l10n/es.js";
import Swal2 from 'sweetalert2';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    messages: {
        type: Object,
        default: () => ({})
    },
    stats: {
        type: Object,
        default: () => ({})
    },
    filters: {
        type: Object,
        default: () => ({})
    }
});

const form = useForm({
    search: props.filters.search,
    status: props.filters.status || null,
    dates: props.filters.dates || null,
});

const configFlatPickr = {
    dateFormat: 'Y-m-d',
    mode: 'range',
    locale: Spanish,
};

const formatDateTime = (dateTimeString) => {
    const date = new Date(dateTimeString);
    const formattedDate = date.toISOString().slice(0, 10);
    const formattedTime = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    return `${formattedDate} ${formattedTime}`;
};

const statusBadgeClass = (status) => {
    if (status === 'pending') {
        return 'bg-amber-100 text-amber-800 border-amber-400 dark:bg-gray-700 dark:text-amber-400';
    }
    if (status === 'read') {
        return 'bg-blue-100 text-blue-800 border-blue-400 dark:bg-gray-700 dark:text-blue-400';
    }
    if (status === 'replied') {
        return 'bg-emerald-100 text-emerald-800 border-emerald-400 dark:bg-gray-700 dark:text-emerald-400';
    }
    return 'bg-gray-100 text-gray-800 border-gray-400 dark:bg-gray-700 dark:text-gray-400';
};

const statusText = (status) => {
    if (status === 'pending') return 'Pendiente';
    if (status === 'read') return 'Leído';
    if (status === 'replied') return 'Respondido';
    return status;
};

const statusIcon = (status) => {
    if (status === 'pending') return faClock;
    if (status === 'read') return faEye;
    if (status === 'replied') return faReply;
    return faEye;
};

const changeStatus = (message, newStatus) => {
    const labels = {
        'pending': 'Pendiente',
        'read': 'Leído',
        'replied': 'Respondido'
    };

    Swal2.fire({
        title: `¿Cambiar estado a "${labels[newStatus]}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, cambiar',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (result.isConfirmed) {
            router.put(route('cms_contact_messages_update', message.id), {
                status: newStatus,
            }, {
                preserveState: true,
                onSuccess: () => {
                    Swal2.fire({
                        icon: 'success',
                        title: 'Estado actualizado',
                        timer: 1500,
                        showConfirmButton: false,
                    });
                }
            });
        }
    });
};

const goTo = (params = {}) => {
    router.get(
        route('cms_contact_messages_list'),
        { ...props.filters, ...params },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};
</script>

<template>
    <AppLayout title="Mensajes de Contacto">
        <Navigation :routeModule="route('cms_dashboard')" :titleModule="'CMS'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Mensajes de Contacto</span>
            </li>
        </Navigation>

        <div class="pt-5">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="rounded-sm border border-stroke bg-white p-5 shadow-default dark:border-strokedark dark:bg-boxdark">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-2xl font-bold text-black dark:text-white">{{ stats.total }}</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Total</p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
                            <font-awesome-icon :icon="faEye" class="text-gray-600 dark:text-gray-300" />
                        </div>
                    </div>
                </div>
                <div class="rounded-sm border border-stroke bg-white p-5 shadow-default dark:border-strokedark dark:bg-boxdark">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-2xl font-bold text-black dark:text-white">{{ stats.pending }}</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Pendientes</p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30">
                            <font-awesome-icon :icon="faClock" class="text-amber-600 dark:text-amber-400" />
                        </div>
                    </div>
                </div>
                <div class="rounded-sm border border-stroke bg-white p-5 shadow-default dark:border-strokedark dark:bg-boxdark">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-2xl font-bold text-black dark:text-white">{{ stats.read }}</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Leídos</p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                            <font-awesome-icon :icon="faEye" class="text-blue-600 dark:text-blue-400" />
                        </div>
                    </div>
                </div>
                <div class="rounded-sm border border-stroke bg-white p-5 shadow-default dark:border-strokedark dark:bg-boxdark">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-2xl font-bold text-black dark:text-white">{{ stats.replied }}</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Respondidos</p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30">
                            <font-awesome-icon :icon="faCheck" class="text-emerald-600 dark:text-emerald-400" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex items-center justify-between flex-wrap gap-4 mb-4">
                <div class="grid grid-cols-8 w-full gap-4">
                    <div class="col-span-6 sm:col-span-5">
                        <div class="flex items-center gap-4">
                            <div class="relative w-60">
                                <FlatPickr v-model="form.dates" :config="configFlatPickr" class="form-input w-full" placeholder="Selecciona rango de fechas" />
                                <button v-if="form.dates" @click="form.dates = null" type="button" class="absolute right-3 top-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none" aria-label="Limpiar fechas">
                                    <font-awesome-icon :icon="faTimes" class="w-4 h-4" />
                                </button>
                            </div>
                            <div>
                                <select v-model="form.status" class="form-input w-full">
                                    <option value="">Todos los estados</option>
                                    <option value="pending">Pendiente</option>
                                    <option value="read">Leído</option>
                                    <option value="replied">Respondido</option>
                                </select>
                            </div>
                            <div>
                                <input v-model="form.search" type="text" id="table-search-users" class="form-input pl-10" placeholder="Buscar por nombre o email">
                            </div>
                        </div>
                    </div>
                    <div class="col-span-8 sm:col-span-3">
                        <div class="flex items-center justify-end gap-4">
                            <button @click="goTo({ search: form.search, status: form.status, dates: form.dates })" class="btn btn-primary text-xs uppercase">Buscar</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="mt-5">
                <div class="mt-5 panel p-0 border-0 overflow-hidden">
                    <Pagination :data="messages">
                        <div class="table-responsive">
                            <table class="table-striped table-hover" id="table_export">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <th>Teléfono</th>
                                        <th>Servicio</th>
                                        <th>Mensaje</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(message, index) in messages.data" :key="message.id" class="bg-white border dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                        <td>{{ formatDateTime(message.created_at) }}</td>
                                        <td class="font-medium">{{ message.name }}</td>
                                        <td>{{ message.email }}</td>
                                        <td>{{ message.phone || '—' }}</td>
                                        <td>
                                            <span class="text-xs font-medium px-2 py-0.5 rounded border bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600">
                                                {{ message.service }}
                                            </span>
                                        </td>
                                        <td class="max-w-xs truncate" :title="message.message">{{ message.message }}</td>
                                        <td>
                                            <span class="text-xs font-medium mr-2 px-2.5 py-0.5 rounded border"
                                                  :class="statusBadgeClass(message.status)">
                                                {{ statusText(message.status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-1">
                                                <Link :href="route('cms_contact_messages_show', message.id)"
                                                      title="Ver detalle"
                                                      class="text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:outline-none focus:ring-indigo-300 font-medium rounded-full text-sm p-2.5 text-center inline-flex items-center dark:bg-indigo-600 dark:hover:bg-indigo-700">
                                                    <font-awesome-icon :icon="faEye" />
                                                </Link>
                                                <button v-if="message.status !== 'replied'"
                                                        @click="changeStatus(message, 'replied')"
                                                        title="Marcar como respondido"
                                                        class="text-white bg-emerald-600 hover:bg-emerald-700 focus:ring-4 focus:outline-none focus:ring-emerald-300 font-medium rounded-full text-sm p-2.5 text-center inline-flex items-center dark:bg-emerald-600 dark:hover:bg-emerald-700">
                                                    <font-awesome-icon :icon="faCheck" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </Pagination>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
