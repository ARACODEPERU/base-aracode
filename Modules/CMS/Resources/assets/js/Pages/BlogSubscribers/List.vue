<script setup>
import { useForm } from "@inertiajs/vue3";
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { faTimes, faTrashAlt, faDownload, faUsers, faUserCheck, faCalendarDays, faCalendarWeek } from "@fortawesome/free-solid-svg-icons";
import Keypad from '@/Components/Keypad.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import FlatPickr from 'vue-flatpickr-component';
import 'flatpickr/dist/flatpickr.css';
import { Spanish } from "flatpickr/dist/l10n/es.js";
import Swal2 from 'sweetalert2';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    subscribers: {
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
    if (status === 'active') {
        return 'bg-emerald-100 text-emerald-800 border-emerald-400 dark:bg-gray-700 dark:text-emerald-400';
    }
    return 'bg-red-100 text-red-800 border-red-400 dark:bg-gray-700 dark:text-red-400';
};

const statusText = (status) => {
    if (status === 'active') return 'Activo';
    return 'Inactivo';
};

const destroySubscriber = (subscriber) => {
    Swal2.fire({
        title: '¿Estás seguro?',
        text: "Este suscriptor será dado de baja.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, dar de baja',
        cancelButtonText: 'Cancelar',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return axios.delete(route('cms_blog_subscribers_destroy', subscriber.id)).then((res) => {
                if (!res.data.success) {
                    Swal2.showValidationMessage(res.data.message)
                }
                return res
            });
        },
        allowOutsideClick: () => !Swal2.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            Swal2.fire({
                title: 'Enhorabuena',
                text: 'Suscriptor dado de baja correctamente',
                icon: 'success',
            });
            router.visit(route('cms_blog_subscribers_list'), { replace: true, method: 'get' });
        }
    });
};

const goTo = (params = {}) => {
    router.get(
        route('cms_blog_subscribers_list'),
        { ...props.filters, ...params },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};

const exportCsv = () => {
    const params = new URLSearchParams();
    if (form.search) params.append('search', form.search);
    if (form.dates) params.append('dates', form.dates);

    window.open(route('cms_blog_subscribers_export') + '?' + params.toString(), '_blank');
};
</script>

<template>
    <AppLayout title="Suscriptores Blog">
        <Navigation :routeModule="route('cms_dashboard')" :titleModule="'CMS'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Suscriptores Blog</span>
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
                            <font-awesome-icon :icon="faUsers" class="text-gray-600 dark:text-gray-300" />
                        </div>
                    </div>
                </div>
                <div class="rounded-sm border border-stroke bg-white p-5 shadow-default dark:border-strokedark dark:bg-boxdark">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-2xl font-bold text-black dark:text-white">{{ stats.active }}</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Activos</p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30">
                            <font-awesome-icon :icon="faUserCheck" class="text-emerald-600 dark:text-emerald-400" />
                        </div>
                    </div>
                </div>
                <div class="rounded-sm border border-stroke bg-white p-5 shadow-default dark:border-strokedark dark:bg-boxdark">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-2xl font-bold text-black dark:text-white">{{ stats.this_month }}</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Este Mes</p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                            <font-awesome-icon :icon="faCalendarDays" class="text-blue-600 dark:text-blue-400" />
                        </div>
                    </div>
                </div>
                <div class="rounded-sm border border-stroke bg-white p-5 shadow-default dark:border-strokedark dark:bg-boxdark">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-2xl font-bold text-black dark:text-white">{{ stats.this_week }}</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Esta Semana</p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900/30">
                            <font-awesome-icon :icon="faCalendarWeek" class="text-purple-600 dark:text-purple-400" />
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
                                <input v-model="form.search" type="text" id="table-search-users" class="form-input pl-10" placeholder="Buscar por nombre o email">
                            </div>
                        </div>
                    </div>
                    <div class="col-span-8 sm:col-span-3">
                        <div class="flex items-center justify-end gap-4">
                            <button @click="goTo({ search: form.search, dates: form.dates })" class="btn btn-primary text-xs uppercase">Buscar</button>
                            <button v-can="'cms_blog_suscriptores_exportar'"
                                    @click="exportCsv"
                                    class="btn btn-warning text-xs uppercase">
                                <font-awesome-icon :icon="faDownload" class="mr-2" />
                                Exportar CSV
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="mt-5">
                <div class="mt-5 panel p-0 border-0 overflow-hidden">
                    <Pagination :data="subscribers">
                        <div class="table-responsive">
                            <table class="table-striped table-hover" id="table_export">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <th>Estado</th>
                                        <th>Fuente</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(subscriber, index) in subscribers.data" :key="subscriber.id" class="bg-white border dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                        <td>{{ formatDateTime(subscriber.created_at) }}</td>
                                        <td class="font-medium">{{ subscriber.name || '—' }}</td>
                                        <td>{{ subscriber.email }}</td>
                                        <td>
                                            <span class="text-xs font-medium mr-2 px-2.5 py-0.5 rounded border"
                                                  :class="statusBadgeClass(subscriber.status)">
                                                {{ statusText(subscriber.status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-xs font-medium px-2 py-0.5 rounded border bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600">
                                                {{ subscriber.source || 'blog' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-1">
                                                <button v-if="subscriber.status === 'active'"
                                                        v-can="'cms_blog_suscriptores'"
                                                        @click="destroySubscriber(subscriber)"
                                                        title="Dar de baja"
                                                        class="text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-full text-sm p-2.5 text-center inline-flex items-center dark:bg-red-600 dark:hover:bg-red-700">
                                                    <font-awesome-icon :icon="faTrashAlt" />
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
