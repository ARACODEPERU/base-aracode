<script setup>
    import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
    import CertificateForm from './Partials/StudentCertificateForm.vue';
    import { Link, router, useForm } from '@inertiajs/vue3';
    import { faTrashAlt } from "@fortawesome/free-solid-svg-icons";
    import IconPlus from '@/Components/vristo/icon/icon-plus.vue';
    import IconSearch from '@/Components/vristo/icon/icon-search.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { ConfigProvider, Dropdown, Menu, MenuItem, Button } from 'ant-design-vue';
    import Pagination from '@/Components/Pagination.vue';
    import iconEdit from "@/Components/vristo/icon/icon-edit.vue";
    import Swal2 from 'sweetalert2';

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

    const courseTooltip = (certificate) => {
        return certificate.course?.description
            || certificate.course?.name
            || certificate.course?.title
            || 'General';
    };

    const certificateCourseKey = (certificate) => {
        return certificate.course_id || 'general';
    };

    const hasActiveBaseCertificate = (certificate) => {
        if (!certificate.state || !certificate.for_module) {
            return false;
        }

        const courseKey = certificateCourseKey(certificate);

        return props.certificates.data.some((item) => {
            return item.state
                && !item.for_module
                && certificateCourseKey(item) === courseKey;
        });
    };

    const hasActiveModuleCertificate = (certificate) => {
        if (!certificate.state || certificate.for_module) {
            return false;
        }

        const courseKey = certificateCourseKey(certificate);

        return props.certificates.data.some((item) => {
            return item.state
                && item.for_module
                && certificateCourseKey(item) === courseKey;
        });
    };

    const destroyCertificate = (certificate) => {
        Swal2.fire({
            title: '¿Eliminar certificado?',
            text: 'Lo que estás por hacer no se podrá revertir. Esta acción eliminará la configuración y sus archivos asociados.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d3021b',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            padding: '2em',
            customClass: 'sweet-alerts',
            backdrop: true,
        }).then((result) => {
            if (result.isConfirmed) {
                requestDeletePassword(certificate);
            }
        });
    };
    const requestDeletePassword = (certificate) => {
        Swal2.fire({
            title: 'Confirma tu contraseÃ±a',
            text: 'Por seguridad, ingresa tu contraseÃ±a para eliminar este certificado.',
            icon: 'warning',
            input: 'password',
            title: 'Confirma tu contrasena',
            text: 'Por seguridad, ingresa tu contrasena para eliminar este certificado.',
            inputPlaceholder: 'Contrasena',
            inputPlaceholder: 'ContraseÃ±a',
            inputPlaceholder: 'Contrasena',
            inputAttributes: {
                autocapitalize: 'off',
                autocomplete: 'current-password',
            },
            showCancelButton: true,
            confirmButtonColor: '#d3021b',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Validar y eliminar',
            cancelButtonText: 'Cancelar',
            showLoaderOnConfirm: true,
            padding: '2em',
            customClass: 'sweet-alerts',
            backdrop: true,
            preConfirm: (password) => {
                if (!password) {
                    Swal2.showValidationMessage('Ingresa tu contraseÃ±a.');
                    Swal2.showValidationMessage('Ingresa tu contrasena.');
                    return false;
                }

                return axios.delete(route('aca_certificate_destroy', certificate.id), {
                    data: { password },
                }).then((res) => {
                    if (!res.data.success) {
                        Swal2.showValidationMessage(res.data.message || 'No se pudo eliminar el certificado.');
                    }
                    return res;
                }).catch((error) => {
                    Swal2.showValidationMessage(error.response?.data?.message || 'No se pudo eliminar el certificado.');
                });
            },
            allowOutsideClick: () => !Swal2.isLoading(),
        }).then((result) => {
            if (result.isConfirmed) {
                Swal2.fire({
                    title: 'Eliminado',
                    text: result.value.data.message,
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                    backdrop: true,
                });
                router.reload({ only: ['certificates'] });
            }
        });
    };
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
                                    <th class="text-right">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-for="(certificate, index) in certificates.data" :key="certificate.id">
                                    <tr
                                        :class="{
                                            'certificate-base-row': hasActiveModuleCertificate(certificate),
                                            'certificate-module-row': hasActiveBaseCertificate(certificate),
                                        }"
                                    >
                                        <td class="certificate-name-cell">
                                            <span
                                                v-if="hasActiveBaseCertificate(certificate)"
                                                class="certificate-branch-connector"
                                                aria-hidden="true"
                                            ></span>
                                            <span
                                                class="certificate-name-tooltip"
                                                :class="{
                                                    'certificate-module-name': hasActiveBaseCertificate(certificate),
                                                    'certificate-name-tooltip-first': index === 0,
                                                }"
                                                tabindex="0"
                                            >
                                                <span class="certificate-name-text">{{ certificate.name_certificate }}</span>
                                                <span
                                                    v-if="!certificate.state"
                                                    class="certificate-inactive-icon"
                                                    title="Certificado inactivo"
                                                    aria-label="Certificado inactivo"
                                                >
                                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
                                                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                                                        <path d="M7 7L17 17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                    </svg>
                                                </span>
                                                <span class="certificate-course-popup" role="tooltip">
                                                    {{ courseTooltip(certificate) }}
                                                    <span v-if="certificate.for_module" class="certificate-module-badge">
                                                        MODULO
                                                    </span>
                                                </span>
                                            </span>
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
                                        <td class="text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <Link :href="route('aca_certificate_edit',certificate.id)" class="btn btn-info btn-sm">
                                                    <icon-edit class="w-4 h-4" />
                                                </Link>
                                                <button
                                                    type="button"
                                                    class="btn btn-danger btn-sm"
                                                    title="Eliminar certificado"
                                                    @click="destroyCertificate(certificate)"
                                                >
                                                    <font-awesome-icon :icon="faTrashAlt" class="w-4 h-4" />
                                                </button>
                                            </div>
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

<style scoped>
    .certificate-name-tooltip {
        position: relative;
        display: inline-flex;
        align-items: center;
        max-width: 360px;
        cursor: help;
        white-space: normal;
        overflow-wrap: anywhere;
        word-break: break-word;
        line-height: 1.35;
    }

    .certificate-name-cell {
        position: relative;
        max-width: 380px;
        white-space: normal;
    }

    .certificate-name-text {
        min-width: 0;
    }

    .certificate-inactive-icon {
        position: relative;
        top: 1px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        width: 18px;
        height: 18px;
        margin-left: 6px;
        color: #dc2626;
    }

    .certificate-inactive-icon svg {
        width: 18px;
        height: 18px;
    }

    .certificate-module-name {
        margin-left: 28px;
    }

    .certificate-base-row .certificate-name-cell::before {
        content: "";
        position: absolute;
        left: 12px;
        top: 50%;
        width: 2px;
        height: calc(100% + 18px);
        border-radius: 999px;
        background: linear-gradient(180deg, #0ea5e9, #22c55e);
        opacity: 0.75;
    }

    .certificate-base-row .certificate-name-cell::after {
        content: "";
        position: absolute;
        left: 12px;
        top: calc(100% + 16px);
        width: 22px;
        height: 2px;
        border-radius: 999px;
        background: #22c55e;
        opacity: 0.75;
    }

    .certificate-branch-connector {
        position: absolute;
        left: 12px;
        top: 0;
        width: 22px;
        height: 50%;
        border-left: 2px solid rgba(14, 165, 233, 0.75);
        border-bottom: 2px solid rgba(34, 197, 94, 0.85);
        border-bottom-left-radius: 10px;
        pointer-events: none;
    }

    .certificate-course-popup {
        position: absolute;
        left: 0;
        bottom: calc(100% + 10px);
        z-index: 50;
        display: none;
        min-width: 220px;
        max-width: 360px;
        white-space: normal;
        border-radius: 6px;
        background: #111827;
        color: #fff;
        padding: 8px 10px;
        font-size: 12px;
        font-weight: 600;
        line-height: 1.35;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.22);
    }

    .certificate-course-popup::after {
        content: "";
        position: absolute;
        left: 14px;
        top: 100%;
        border: 6px solid transparent;
        border-top-color: #111827;
    }

    .certificate-name-tooltip-first .certificate-course-popup {
        top: calc(100% + 10px);
        bottom: auto;
    }

    .certificate-name-tooltip-first .certificate-course-popup::after {
        top: auto;
        bottom: 100%;
        border-top-color: transparent;
        border-bottom-color: #111827;
    }

    .certificate-name-tooltip:hover .certificate-course-popup,
    .certificate-name-tooltip:focus .certificate-course-popup {
        display: block;
    }

    .certificate-module-badge {
        display: inline-flex;
        align-items: center;
        margin-left: 8px;
        padding: 3px 8px;
        border-radius: 999px;
        background: linear-gradient(135deg, #f97316, #dc2626);
        color: #fff;
        font-size: 10px;
        font-weight: 900;
        letter-spacing: 0.04em;
        box-shadow: 0 6px 16px rgba(220, 38, 38, 0.35);
        animation: certificate-module-sway 1.8s ease-in-out infinite;
        transform-origin: center;
    }

    @keyframes certificate-module-sway {
        0%, 100% {
            transform: translateX(0) rotate(0deg);
        }
        25% {
            transform: translateX(2px) rotate(1deg);
        }
        75% {
            transform: translateX(-2px) rotate(-1deg);
        }
    }
</style>
