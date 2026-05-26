<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { ref } from 'vue';
import axios from 'axios';
import Swal2 from 'sweetalert2';

const props = defineProps({
    currentTemplate: {
        type: String,
        default: '1',
    },
    templateOptions: {
        type: Array,
        default: () => [],
    },
    affectedDocuments: {
        type: Array,
        default: () => [],
    },
});

const selectedTemplate = ref(props.currentTemplate);
const saving = ref(false);

const saveTemplate = async () => {
    saving.value = true;

    try {
        const { data } = await axios.post(route('sales_print_templates_update'), {
            template: selectedTemplate.value,
        });

        await Swal2.fire({
            icon: 'success',
            title: 'Plantilla guardada',
            text: data.message,
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    } catch (error) {
        const message = error.response?.data?.message ?? 'No se pudo guardar la plantilla.';
        await Swal2.fire({
            icon: 'error',
            title: 'Error',
            text: message,
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    } finally {
        saving.value = false;
    }
};
</script>

<template>
    <AppLayout title="Plantillas A4">
        <Navigation :routeModule="route('sales_dashboard')" :titleModule="'Ventas'"
            :data="[
                { title: 'Administración' },
                { title: 'Plantillas A4' },
            ]"
        />

        <div class="mt-5 space-y-5">
            <div class="panel">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="space-y-2">
                        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Plantillas de impresión A4</h1>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            Elija el diseño global para documentos A4 del módulo de ventas.
                        </p>
                    </div>
                    <button
                        class="btn btn-primary"
                        type="button"
                        :disabled="saving"
                        @click="saveTemplate"
                    >
                        {{ saving ? 'Guardando...' : 'Guardar plantilla' }}
                    </button>
                </div>
            </div>

            <div class="panel border border-amber-300/80 bg-amber-50/80 dark:border-amber-500/40 dark:bg-amber-500/10">
                <p class="text-sm font-medium text-amber-900 dark:text-amber-200">
                    Esta configuración solo afecta documentos A4. Los tickets no cambian.
                </p>
            </div>

            <div class="panel">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-200">
                    Documentos afectados
                </h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    <div
                        v-for="doc in affectedDocuments"
                        :key="doc"
                        class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-700 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                    >
                        {{ doc }}
                    </div>
                </div>
            </div>

            <div class="grid gap-5 xl:grid-cols-2">
                <label
                    v-for="option in templateOptions"
                    :key="option.value"
                    class="panel cursor-pointer border-2 transition"
                    :class="selectedTemplate === option.value
                        ? 'border-primary shadow-[0_0_0_1px_rgba(67,97,238,0.2)]'
                        : 'border-gray-200 dark:border-gray-700'"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-3">
                                <input
                                    v-model="selectedTemplate"
                                    class="form-radio"
                                    type="radio"
                                    name="sales-a4-template"
                                    :value="option.value"
                                >
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ option.label }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ option.description }}</p>
                                </div>
                            </div>
                        </div>
                        <span
                            class="rounded-full px-3 py-1 text-xs font-semibold"
                            :class="selectedTemplate === option.value
                                ? 'bg-primary/10 text-primary'
                                : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-300'"
                        >
                            {{ selectedTemplate === option.value ? 'Seleccionado' : 'Disponible' }}
                        </span>
                    </div>

                    <div class="mt-5 rounded-2xl border bg-white p-4 dark:bg-[#0f172a]"
                        :class="option.value === '1'
                            ? 'border-gray-200 dark:border-gray-700'
                            : 'border-primary/30 bg-slate-50 dark:border-primary/40 dark:bg-slate-950'"
                    >
                        <div v-if="option.value === '1'" class="space-y-4">
                            <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="space-y-2">
                                        <div class="h-8 w-24 rounded bg-slate-300 dark:bg-slate-600"></div>
                                        <div class="h-3 w-44 rounded bg-slate-400 dark:bg-slate-500"></div>
                                        <div class="h-3 w-56 rounded bg-slate-200 dark:bg-slate-700"></div>
                                        <div class="h-3 w-40 rounded bg-slate-200 dark:bg-slate-700"></div>
                                    </div>
                                    <div class="w-44 rounded-lg border-2 border-primary/70 p-3 text-center">
                                        <div class="mx-auto h-3 w-20 rounded bg-slate-300 dark:bg-slate-600"></div>
                                        <div class="mx-auto mt-2 h-3 w-24 rounded bg-slate-200 dark:bg-slate-700"></div>
                                        <div class="mx-auto mt-2 h-3 w-28 rounded bg-slate-200 dark:bg-slate-700"></div>
                                        <div class="mx-auto mt-2 h-4 w-24 rounded bg-primary/20"></div>
                                    </div>
                                </div>
                                <div class="mt-4 rounded-lg bg-slate-100 p-4 dark:bg-slate-800/70">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="space-y-2">
                                            <div class="h-3 w-14 rounded bg-slate-400 dark:bg-slate-500"></div>
                                            <div class="h-4 w-44 rounded bg-slate-200 dark:bg-slate-700"></div>
                                            <div class="h-3 w-28 rounded bg-slate-200 dark:bg-slate-700"></div>
                                            <div class="h-3 w-40 rounded bg-slate-200 dark:bg-slate-700"></div>
                                        </div>
                                        <div class="space-y-2 text-right">
                                            <div class="ml-auto h-3 w-28 rounded bg-slate-300 dark:bg-slate-600"></div>
                                            <div class="ml-auto h-3 w-36 rounded bg-slate-200 dark:bg-slate-700"></div>
                                            <div class="ml-auto h-3 w-32 rounded bg-slate-200 dark:bg-slate-700"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 rounded-lg overflow-hidden ring-1 ring-slate-200 dark:ring-slate-700">
                                    <div class="grid grid-cols-[60px_1fr_80px_70px_90px] gap-px bg-slate-200 dark:bg-slate-700">
                                        <div class="h-7 bg-slate-100 dark:bg-slate-800"></div>
                                        <div class="h-7 bg-slate-100 dark:bg-slate-800"></div>
                                        <div class="h-7 bg-slate-100 dark:bg-slate-800"></div>
                                        <div class="h-7 bg-slate-100 dark:bg-slate-800"></div>
                                        <div class="h-7 bg-slate-100 dark:bg-slate-800"></div>
                                    </div>
                                    <div class="space-y-px bg-slate-200 dark:bg-slate-700">
                                        <div class="grid grid-cols-[60px_1fr_80px_70px_90px] gap-px">
                                            <div class="h-9 bg-white dark:bg-slate-900"></div>
                                            <div class="h-9 bg-white dark:bg-slate-900"></div>
                                            <div class="h-9 bg-slate-50 dark:bg-slate-800"></div>
                                            <div class="h-9 bg-white dark:bg-slate-900"></div>
                                            <div class="h-9 bg-white dark:bg-slate-900"></div>
                                        </div>
                                        <div class="grid grid-cols-[60px_1fr_80px_70px_90px] gap-px">
                                            <div class="h-9 bg-white dark:bg-slate-900"></div>
                                            <div class="h-9 bg-white dark:bg-slate-900"></div>
                                            <div class="h-9 bg-slate-50 dark:bg-slate-800"></div>
                                            <div class="h-9 bg-white dark:bg-slate-900"></div>
                                            <div class="h-9 bg-white dark:bg-slate-900"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 grid gap-4 lg:grid-cols-[1fr_120px]">
                                    <div class="rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900">
                                        <div class="h-3 w-24 rounded bg-slate-300 dark:bg-slate-600"></div>
                                        <div class="mt-3 h-3 w-full rounded bg-slate-200 dark:bg-slate-700"></div>
                                        <div class="mt-2 h-3 w-11/12 rounded bg-slate-200 dark:bg-slate-700"></div>
                                        <div class="mt-2 h-3 w-10/12 rounded bg-slate-200 dark:bg-slate-700"></div>
                                    </div>
                                    <div class="rounded-lg border border-slate-200 bg-white p-3 text-center dark:border-slate-700 dark:bg-slate-900">
                                        <div class="mx-auto h-3 w-12 rounded bg-slate-300 dark:bg-slate-600"></div>
                                        <div class="mx-auto mt-3 h-16 w-16 rounded bg-slate-200 dark:bg-slate-700"></div>
                                        <div class="mx-auto mt-3 h-3 w-16 rounded bg-slate-200 dark:bg-slate-700"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="option.value === '2'" class="space-y-4">
                            <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
                                <div class="flex items-start justify-between gap-4 border-b border-slate-300 pb-3 dark:border-slate-700">
                                    <div class="mt-1 h-10 w-24 rounded bg-slate-300 dark:bg-slate-600"></div>
                                    <div class="space-y-2 text-right">
                                        <div class="ml-auto h-5 w-44 rounded bg-sky-200 dark:bg-sky-500/30"></div>
                                        <div class="ml-auto h-3 w-28 rounded bg-slate-200 dark:bg-slate-700"></div>
                                        <div class="ml-auto h-3 w-36 rounded bg-slate-200 dark:bg-slate-700"></div>
                                        <div class="ml-auto h-3 w-24 rounded bg-slate-200 dark:bg-slate-700"></div>
                                    </div>
                                </div>
                                <div class="mt-4 grid gap-4 lg:grid-cols-[1.1fr_0.9fr]">
                                    <div class="rounded-lg border-l-4 border-sky-500 bg-white p-3 dark:bg-slate-900">
                                        <div class="h-3 w-14 rounded bg-slate-300 dark:bg-slate-600"></div>
                                        <div class="mt-2 h-4 w-40 rounded bg-slate-200 dark:bg-slate-700"></div>
                                        <div class="mt-2 h-3 w-28 rounded bg-slate-200 dark:bg-slate-700"></div>
                                        <div class="mt-2 h-3 w-36 rounded bg-slate-200 dark:bg-slate-700"></div>
                                    </div>
                                    <div class="space-y-2 text-right">
                                        <div class="ml-auto h-6 w-28 rounded bg-sky-100 dark:bg-sky-500/20"></div>
                                        <div class="ml-auto h-3 w-32 rounded bg-slate-200 dark:bg-slate-700"></div>
                                        <div class="ml-auto h-3 w-36 rounded bg-slate-200 dark:bg-slate-700"></div>
                                        <div class="ml-auto h-3 w-24 rounded bg-slate-200 dark:bg-slate-700"></div>
                                        <div class="ml-auto h-3 w-20 rounded bg-slate-200 dark:bg-slate-700"></div>
                                    </div>
                                </div>
                                <div class="mt-4 rounded-lg overflow-hidden ring-1 ring-slate-200 dark:ring-slate-700">
                                    <div class="grid grid-cols-[50px_1fr_80px_60px_90px] gap-px bg-slate-200 dark:bg-slate-700">
                                        <div class="h-8 bg-slate-200 dark:bg-slate-700"></div>
                                        <div class="h-8 bg-slate-200 dark:bg-slate-700"></div>
                                        <div class="h-8 bg-slate-200 dark:bg-slate-700"></div>
                                        <div class="h-8 bg-slate-200 dark:bg-slate-700"></div>
                                        <div class="h-8 bg-slate-200 dark:bg-slate-700"></div>
                                    </div>
                                    <div class="space-y-px bg-slate-200 dark:bg-slate-700">
                                        <div class="grid grid-cols-[50px_1fr_80px_60px_90px] gap-px">
                                            <div class="h-10 bg-slate-100 dark:bg-slate-800"></div>
                                            <div class="h-10 bg-white dark:bg-slate-900"></div>
                                            <div class="h-10 bg-slate-100 dark:bg-slate-800"></div>
                                            <div class="h-10 bg-white dark:bg-slate-900"></div>
                                            <div class="h-10 bg-white dark:bg-slate-900"></div>
                                        </div>
                                        <div class="grid grid-cols-[50px_1fr_80px_60px_90px] gap-px">
                                            <div class="h-10 bg-slate-100 dark:bg-slate-800"></div>
                                            <div class="h-10 bg-white dark:bg-slate-900"></div>
                                            <div class="h-10 bg-slate-100 dark:bg-slate-800"></div>
                                            <div class="h-10 bg-white dark:bg-slate-900"></div>
                                            <div class="h-10 bg-white dark:bg-slate-900"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 grid gap-4 lg:grid-cols-[1fr_160px]">
                                    <div class="rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900">
                                        <div class="h-3 w-20 rounded bg-slate-300 dark:bg-slate-600"></div>
                                        <div class="mt-3 h-3 w-full rounded bg-slate-200 dark:bg-slate-700"></div>
                                        <div class="mt-2 h-3 w-11/12 rounded bg-slate-200 dark:bg-slate-700"></div>
                                        <div class="mt-2 h-3 w-10/12 rounded bg-slate-200 dark:bg-slate-700"></div>
                                    </div>
                                    <div class="rounded-lg border border-slate-200 bg-white p-3 text-center dark:border-slate-700 dark:bg-slate-900">
                                        <div class="mx-auto h-3 w-16 rounded bg-slate-300 dark:bg-slate-600"></div>
                                        <div class="mx-auto mt-3 h-20 w-20 rounded bg-slate-200 dark:bg-slate-700"></div>
                                        <div class="mx-auto mt-3 h-3 w-24 rounded bg-slate-200 dark:bg-slate-700"></div>
                                    </div>
                                </div>
                                <div class="mt-4 border-t border-slate-200 pt-3 dark:border-slate-700">
                                    <div class="h-3 w-full rounded bg-slate-200 dark:bg-slate-700"></div>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="option.value === '3'" class="space-y-4">
                            <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
                                <div class="rounded-lg py-3">
                                    <div class="flex items-center justify-between gap-4">
                                        <div class="flex h-14 w-40 items-center">
                                            <div class="h-8 w-28 rounded bg-yellow-300"></div>
                                        </div>
                                        <div class="ml-auto space-y-2 text-right">
                                            <div class="ml-auto h-5 w-32 rounded bg-slate-400 dark:bg-slate-500"></div>
                                            <div class="ml-auto grid w-28 grid-cols-[auto_1fr] gap-x-2 gap-y-1">
                                                <div class="h-2.5 w-10 rounded bg-slate-300 dark:bg-slate-600"></div>
                                                <div class="h-2.5 w-full rounded bg-slate-200 dark:bg-slate-700"></div>
                                                <div class="h-2.5 w-10 rounded bg-slate-300 dark:bg-slate-600"></div>
                                                <div class="h-2.5 w-full rounded bg-slate-200 dark:bg-slate-700"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-0.5">
                                            <div class="h-7 w-2 border-y-[12px] border-l-0 border-r-[10px] border-transparent border-r-orange-500"></div>
                                            <div class="h-7 w-44 rounded-r bg-orange-500/90"></div>
                                        </div>
                                        <div class="flex items-center gap-0.5">
                                            <div class="h-7 w-2 border-y-[12px] border-l-0 border-r-[10px] border-transparent border-r-orange-500"></div>
                                            <div class="h-7 w-40 rounded-r bg-orange-500/90"></div>
                                        </div>
                                        <div class="flex items-center gap-0.5">
                                            <div class="h-7 w-2 border-y-[12px] border-l-0 border-r-[10px] border-transparent border-r-orange-500"></div>
                                            <div class="h-7 w-36 rounded-r bg-orange-500/90"></div>
                                        </div>
                                    </div>
                                    <div class="space-y-2 text-right">
                                        <div class="flex items-center justify-end gap-0.5">
                                            <div class="h-7 w-40 rounded-l bg-yellow-300"></div>
                                            <div class="h-7 w-2 border-y-[12px] border-l-[10px] border-r-0 border-transparent border-l-yellow-300"></div>
                                        </div>
                                        <div class="flex items-center justify-end gap-0.5">
                                            <div class="h-7 w-44 rounded-l bg-yellow-300"></div>
                                            <div class="h-7 w-2 border-y-[12px] border-l-[10px] border-r-0 border-transparent border-l-yellow-300"></div>
                                        </div>
                                        <div class="flex items-center justify-end gap-0.5">
                                            <div class="h-7 w-40 rounded-l bg-yellow-300"></div>
                                            <div class="h-7 w-2 border-y-[12px] border-l-[10px] border-r-0 border-transparent border-l-yellow-300"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 rounded-lg overflow-hidden ring-1 ring-slate-200 dark:ring-slate-700">
                                    <div class="grid grid-cols-[80px_1fr_70px_50px_80px] gap-px bg-slate-200 dark:bg-slate-700">
                                        <div class="h-8 bg-white dark:bg-slate-900"></div>
                                        <div class="h-8 bg-white dark:bg-slate-900"></div>
                                        <div class="h-8 bg-white dark:bg-slate-900"></div>
                                        <div class="h-8 bg-white dark:bg-slate-900"></div>
                                        <div class="h-8 bg-white dark:bg-slate-900"></div>
                                    </div>
                                    <div class="space-y-px bg-slate-200 dark:bg-slate-700">
                                        <div class="grid grid-cols-[80px_1fr_70px_50px_80px] gap-px">
                                            <div class="h-10 bg-slate-100 dark:bg-slate-800"></div>
                                            <div class="h-10 bg-slate-100 dark:bg-slate-800"></div>
                                            <div class="h-10 bg-slate-100 dark:bg-slate-800"></div>
                                            <div class="h-10 bg-slate-100 dark:bg-slate-800"></div>
                                            <div class="h-10 bg-slate-100 dark:bg-slate-800"></div>
                                        </div>
                                        <div class="grid grid-cols-[80px_1fr_70px_50px_80px] gap-px">
                                            <div class="h-10 bg-white dark:bg-slate-900"></div>
                                            <div class="h-10 bg-white dark:bg-slate-900"></div>
                                            <div class="h-10 bg-white dark:bg-slate-900"></div>
                                            <div class="h-10 bg-white dark:bg-slate-900"></div>
                                            <div class="h-10 bg-white dark:bg-slate-900"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 rounded-lg border-l-4 border-sky-500 bg-slate-50 p-3 dark:bg-slate-800/70">
                                    <div class="h-3 w-16 rounded bg-slate-300 dark:bg-slate-600"></div>
                                    <div class="mt-2 h-3 w-full rounded bg-slate-200 dark:bg-slate-700"></div>
                                    <div class="mt-2 h-3 w-10/12 rounded bg-slate-200 dark:bg-slate-700"></div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="space-y-4">
                            <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
                                <div class="flex justify-center">
                                    <div class="h-12 w-24 rounded bg-slate-300 dark:bg-slate-600"></div>
                                </div>
                                <div class="mt-4 rounded-md border-y border-slate-300 bg-slate-100 py-3 text-center dark:border-slate-600 dark:bg-slate-800/70">
                                    <div class="mx-auto h-5 w-44 rounded bg-slate-300 dark:bg-slate-600"></div>
                                </div>

                                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                                    <div class="space-y-2">
                                        <div class="h-3 w-20 rounded bg-slate-300 dark:bg-slate-600"></div>
                                        <div class="h-3 w-40 rounded bg-slate-200 dark:bg-slate-700"></div>
                                        <div class="h-3 w-32 rounded bg-slate-200 dark:bg-slate-700"></div>
                                        <div class="h-3 w-36 rounded bg-slate-200 dark:bg-slate-700"></div>
                                    </div>
                                    <div class="space-y-2 text-right">
                                        <div class="ml-auto h-3 w-24 rounded bg-slate-300 dark:bg-slate-600"></div>
                                        <div class="ml-auto h-3 w-24 rounded bg-slate-200 dark:bg-slate-700"></div>
                                        <div class="ml-auto h-3 w-28 rounded bg-slate-200 dark:bg-slate-700"></div>
                                        <div class="ml-auto h-3 w-20 rounded bg-slate-200 dark:bg-slate-700"></div>
                                    </div>
                                </div>

                                <div class="mt-4 rounded-lg overflow-hidden ring-1 ring-slate-200 dark:ring-slate-700">
                                    <div class="grid grid-cols-[80px_1fr_70px_50px_80px] gap-px bg-slate-200 dark:bg-slate-700">
                                        <div class="h-8 bg-white dark:bg-slate-900"></div>
                                        <div class="h-8 bg-white dark:bg-slate-900"></div>
                                        <div class="h-8 bg-white dark:bg-slate-900"></div>
                                        <div class="h-8 bg-white dark:bg-slate-900"></div>
                                        <div class="h-8 bg-white dark:bg-slate-900"></div>
                                    </div>
                                    <div class="space-y-px bg-slate-200 dark:bg-slate-700">
                                        <div class="grid grid-cols-[80px_1fr_70px_50px_80px] gap-px">
                                            <div class="h-10 bg-slate-100 dark:bg-slate-800"></div>
                                            <div class="h-10 bg-slate-100 dark:bg-slate-800"></div>
                                            <div class="h-10 bg-slate-100 dark:bg-slate-800"></div>
                                            <div class="h-10 bg-slate-100 dark:bg-slate-800"></div>
                                            <div class="h-10 bg-slate-100 dark:bg-slate-800"></div>
                                        </div>
                                        <div class="grid grid-cols-[80px_1fr_70px_50px_80px] gap-px">
                                            <div class="h-10 bg-white dark:bg-slate-900"></div>
                                            <div class="h-10 bg-white dark:bg-slate-900"></div>
                                            <div class="h-10 bg-white dark:bg-slate-900"></div>
                                            <div class="h-10 bg-white dark:bg-slate-900"></div>
                                            <div class="h-10 bg-white dark:bg-slate-900"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 grid gap-4 lg:grid-cols-[1fr_180px]">
                                    <div class="space-y-3">
                                        <div class="rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900">
                                            <div class="h-3 w-20 rounded bg-slate-300 dark:bg-slate-600"></div>
                                            <div class="mt-3 h-3 w-full rounded bg-slate-200 dark:bg-slate-700"></div>
                                            <div class="mt-2 h-3 w-11/12 rounded bg-slate-200 dark:bg-slate-700"></div>
                                            <div class="mt-2 h-3 w-9/12 rounded bg-slate-200 dark:bg-slate-700"></div>
                                        </div>
                                        <div class="rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900">
                                            <div class="h-3 w-20 rounded bg-slate-300 dark:bg-slate-600"></div>
                                            <div class="mt-3 grid grid-cols-4 gap-px bg-slate-200 dark:bg-slate-700">
                                                <div class="h-7 bg-slate-100 dark:bg-slate-800"></div>
                                                <div class="h-7 bg-slate-100 dark:bg-slate-800"></div>
                                                <div class="h-7 bg-slate-100 dark:bg-slate-800"></div>
                                                <div class="h-7 bg-slate-100 dark:bg-slate-800"></div>
                                                <div class="h-8 bg-white dark:bg-slate-900"></div>
                                                <div class="h-8 bg-white dark:bg-slate-900"></div>
                                                <div class="h-8 bg-white dark:bg-slate-900"></div>
                                                <div class="h-8 bg-white dark:bg-slate-900"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="rounded-lg border border-slate-200 bg-white p-3 text-center dark:border-slate-700 dark:bg-slate-900">
                                        <div class="mx-auto h-3 w-16 rounded bg-slate-300 dark:bg-slate-600"></div>
                                        <div class="mx-auto mt-3 h-16 w-16 rounded bg-slate-200 dark:bg-slate-700"></div>
                                        <div class="mx-auto mt-3 h-3 w-20 rounded bg-slate-200 dark:bg-slate-700"></div>
                                        <div class="mx-auto mt-2 h-3 w-16 rounded bg-slate-200 dark:bg-slate-700"></div>
                                    </div>
                                </div>
                                <div class="mt-4 border-t border-slate-200 pt-3 dark:border-slate-700">
                                    <div class="h-3 w-full rounded bg-slate-200 dark:bg-slate-700"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </label>
            </div>
        </div>
    </AppLayout>
</template>
