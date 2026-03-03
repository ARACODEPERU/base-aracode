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
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                {title: 'Certificados'}
            ]"
        />
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
                                        Para Módulo
                                    </th>
                                    <th>
                                        Reverso
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
                                        <td class="whitespace-nowrap text-center">
                                            <span v-if="certificate.for_module" class="text-green-600" title="Para módulos">
                                                <svg class="w-5 h-5 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                </svg>
                                            </span>
                                            <span v-else class="text-gray-400" title="Solo para curso">
                                                <svg class="w-5 h-5 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                </svg>
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap text-center">
                                            <span v-if="certificate.back_certificate_img" class="text-green-600" title="Con reverso">
                                                <svg class="w-5 h-5 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                </svg>
                                            </span>
                                            <span v-else class="text-gray-400" title="Sin reverso">
                                                <svg class="w-5 h-5 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                </svg>
                                            </span>
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
