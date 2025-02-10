<script setup>
    import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
    import CertificateForm from './Partials/StudentCertificateForm.vue';
    import { Link, useForm } from '@inertiajs/vue3';
    import { faTrashAlt } from "@fortawesome/free-solid-svg-icons";
    import IconPlus from '@/Components/vristo/icon/icon-plus.vue';
    import IconSearch from '@/Components/vristo/icon/icon-search.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { ConfigProvider, Dropdown, Menu, MenuItem, Button } from 'ant-design-vue';
    import Pagination from '@/Components/Pagination.vue';
    import iconEdit from "@/Components/vristo/icon/icon-edit.vue";

    const props = defineProps({
        certificates:{
            type: Object,
            default : () => ({})
        },
        filters: {
            type: Object,
            default: () => ({}),
        },
    });
    const form = useForm({
        search: props.filters.search,
    });
</script>

<template>
    <AppLayout title="Certificados">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'">
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>Certificados</span>
            </li>
        </Navigation>
        <div class="pt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl">Certificados</h2>
                <div class="flex sm:flex-row flex-col sm:items-center sm:gap-3 gap-4 w-full sm:w-auto">
                    <div class="flex gap-3">
                        <div>
                            <Link :href="route('aca_certificate_create')" type="button" class="btn btn-primary">
                                <icon-plus class="ltr:mr-2 rtl:ml-2" />
                                Nuevo
                            </Link>
                        </div>
                    </div>

                    <div class="relative">
                        <input
                            type="text"
                            placeholder="Buscar"
                            class="form-input py-2 ltr:pr-11 rtl:pl-11 peer"
                            v-model="form.search"
                            @keyup.enter="form.get(route('aca_courses_list'))"
                        />
                        <div class="absolute ltr:right-[11px] rtl:left-[11px] top-1/2 -translate-y-1/2 peer-focus:text-primary">
                            <icon-search class="mx-auto" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-5 panel p-0 border-0 overflow-hidden">
                <div class="table-responsive">
                    <ConfigProvider>
                        <table class="table-striped table-hover">
                            <thead>
                                <tr class="!text-center">
                                    <th>
                                        Acciones
                                    </th>
                                    <th>
                                        Nombre
                                    </th>
                                    <th>
                                        Fecha creacion
                                    </th>
                                    <th>
                                        Estado
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-for="(certificate, index) in certificates.data" :key="certificate.id">
                                    <tr>
                                        <td class="text-center">
                                            <div class="flex items-center">
                                                <Link :href="route('aca_certificate_edit',certificate.id)" class="btn btn-info btn-sm">
                                                    <icon-edit class="w-4 h-4" />
                                                </Link>
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap">
                                            {{ certificate.name_certificate }}
                                        </td>
                                        <td class="whitespace-nowrap">
                                            {{ certificate.formatted_date }}
                                        </td>
                                        <td class="whitespace-nowrap">
                                            <span v-if="certificate.state" class="bg-blue-100 text-blue-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-blue-400 border border-blue-400">Activo</span>
                                            <span v-else class="bg-red-100 text-red-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-red-400 border border-red-400">Inactivo</span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        <Pagination :data="certificates" />
                    </ConfigProvider>
                </div>
                    
            </div>
        </div>
    </AppLayout>
</template>
