<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import { Link, useForm } from '@inertiajs/vue3';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import iconSearch from '@/Components/vristo/icon/icon-search.vue';
    import { Empty } from 'ant-design-vue';
    import { ref } from 'vue';
    import DropdownExports from '@/Components/DropdownExports.vue';
    import flatPickr from 'vue-flatpickr-component';
    import 'flatpickr/dist/flatpickr.css';
    import { Spanish } from "flatpickr/dist/l10n/es.js"
    import iconLoader from '@/Components/vristo/icon/icon-loader.vue';


    const props = defineProps({
        paymentMethods: {
            type: Object,
            default: () => ({})
        }
    });

    const form = useForm({
        search: null,
        paymentMethod_id: null,
        issue_date: null
    });

    const dataPayments = ref([]);
    const loaderSearch = ref(false);

    const studentPaymentBankTable =  () => {
        loaderSearch.value = true;
        axios({
            method: 'post',
            url: route('aca_student_payment_report_bank_table'),
            data: form
        }).then(function (response) {
            dataPayments.value = response.data.items
        }).finally(() => {
            loaderSearch.value = false;
        });
    }

    // Función auxiliar para formatear la fecha a 'YYYY-MM-DD'
    const formatDateToYYYYMMDD = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0'); // Meses son 0-11
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    const today = new Date();
    const todayFormatted = formatDateToYYYYMMDD(today);

    const basic = ref({
        dateFormat: 'Y-m-d',
        mode: 'range',
        locale: Spanish,
        defaultDate: [todayFormatted, todayFormatted]
    });

    const findObjectById = (idToFind) => {
        if (!idToFind) return { description: 'no se realizaron pagos' };

        return props.paymentMethods.find(item => item.id === idToFind)
            ?? { description: 'Desconocido' };
    };

    const parsePayments = (payments) => {
        if (!payments) return [];
        if (typeof payments === 'string') {
            try {
                return JSON.parse(payments);
            } catch (e) {
                return [];
            }
        }
        return payments;
    };
</script>
<template>
    <AppLayout title="Reportes">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                {route: route('aca_reports_dashboard'), title: 'Reportes'},
                {title: 'Reporte de pagos de alumnos por banco'}
            ]"
        />
        <div class="mt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl">Filtros de Búsqueda</h2>
                <div class="flex flex-1 sm:flex-row flex-col sm:items-center sm:gap-3 gap-4 w-full sm:w-auto">
                    <div>
                        <flat-pickr v-model="form.issue_date" class="form-input" :config="basic"></flat-pickr>
                    </div>
                    <div class="">
                        <select v-model="form.paymentMethod_id" class="form-select">
                            <option :value="null">Todos</option>
                            <template v-for="paymentMethod in paymentMethods">
                                <option :value="paymentMethod.id">{{ paymentMethod.description }}</option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <input
                            type="text"
                            placeholder="Buscar"
                            class="form-input"
                            v-model="form.search"
                            @keyup.enter="studentPaymentBankTable"
                        />
                    </div>
                    <button @click="studentPaymentBankTable" :class="{ 'opacity-25': loaderSearch }" :disabled="loaderSearch" type="buttton" class="btn btn-primary flex gap-4">
                        <icon-loader v-if="loaderSearch" class="w-4 h-4" />
                        <icon-search v-else class="w-4 h-4" />
                        Buscar
                    </button>
                    <DropdownExports

                        :showExcel="true"

                        :actionUrl="route('aca_student_payment_report_bank_export')"
                        :data="form"
                    />
                </div>
            </div>
            <div class="panel mt-6 pt-0 px-0">
                <div class="table-responsive">
                    <table class="rounded-t-xl">
                        <thead>
                            <tr>
                                <th>FECHA</th>
                                <th>NOMBRE O RAZON SOCIAL</th>
                                <th>CURSOS</th>
                                <th>CELULAR</th>
                                <th>ALUMNO</th>
                                <th>DOCUMENTO</th>
                                <th>FORMA DE PAGO</th>
                                <th>IMPORTE DE COBRANZA</th>
                                <th>NRO. DE OPERACIÓN</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-if="dataPayments.length > 0">
                                <tr v-for="(item, key) in dataPayments ">
                                    <td>
                                        {{ item.sale_date }}
                                    </td>
                                    <td>
                                        <template v-if="item.document && item.document.client_rzn_social">
                                            <span v-html="item.document?.client_rzn_social"></span>
                                        </template>
                                        <template v-else>
                                            <span v-html="item.client.full_name"></span>
                                        </template>
                                    </td>
                                    <td>
                                        <div class="space-y-2">
                                            <template v-for="product in item.sale_product">
                                                <p class="text-secondary">
                                                    {{ JSON.parse(product.saleProduct).title ? JSON.parse(product.saleProduct).title : JSON.parse(product.saleProduct).name ? JSON.parse(product.saleProduct).name : JSON.parse(product.saleProduct).title ? JSON.parse(product.saleProduct).title : JSON.parse(product.saleProduct).description }}
                                                </p>
                                            </template>
                                        </div>
                                    </td>
                                    <td>
                                        {{ item.client.telephone }}
                                    </td>
                                    <td>
                                        {{ item.client.full_name }}
                                    </td>
                                    <td>
                                        {{ item.document.invoice_serie }}-{{ item.document.invoice_correlative }}
                                    </td>
                                    <td>
                                        <span v-if="item.document && item.document.forma_pago == 'Credito'" class="relative inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-gray-500 text-white z-10">
                                            Al crédito
                                        </span>
                                        <span v-else class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-yellow-500 text-white">Al contado</span>
                                    </td>
                                    <td>
                                        {{ item.total}}
                                    </td>
                                    <td class="p-3 align-center min-w-[200px]">

                                        <div class="ps relative mb-2 max-h-[200px] ltr:pr-2 rtl:pl-2 overflow-y-auto">
                                            <div class="text-sm">
                                                <template v-if="item.payments && item.payments.length > 0">
                                                    <div v-for="(pay, index) in parsePayments(item.payments)" :key="index"
                                                        class="flex flex-col mb-3 pb-2 border-b border-gray-100 dark:border-gray-700 last:border-0 last:mb-0 group">

                                                        <div class="flex items-center justify-between w-full">
                                                            <div class="flex items-center overflow-hidden">
                                                                <div class="bg-success w-2 h-2 rounded-full shrink-0 ltr:mr-2 rtl:ml-2 shadow-[0_0_5px_rgba(16,196,105,0.5)]"></div>

                                                                <span class="font-semibold text-gray-800 dark:text-gray-200 truncate">
                                                                    {{ findObjectById(pay.type)?.description }}
                                                                </span>
                                                            </div>

                                                            <div class="ltr:ml-2 rtl:mr-2 font-bold text-indigo-600 dark:text-indigo-400">
                                                                {{ pay.amount }}
                                                            </div>
                                                        </div>

                                                        <div class="flex items-center mt-1 ltr:pl-4 rtl:pr-4">
                                                            <span v-if="pay.reference"
                                                                class="text-[10px] px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-500 border border-gray-200 dark:border-gray-600">
                                                                Ref: {{ pay.reference }}
                                                            </span>
                                                            </div>
                                                    </div>
                                                </template>

                                                <div v-else class="flex items-center justify-center py-2 px-3 border border-dashed border-gray-200 dark:border-gray-700 rounded-md bg-gray-50/50 dark:bg-transparent">
                                                    <svg class="w-4 h-4 text-gray-400 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                                    </svg>
                                                    <span class="text-[13px] text-gray-400 italic leading-none">
                                                        {{ findObjectById(null).description }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <template v-else>
                                <tr>
                                    <td colspan="9" class="text-center">
                                        <div class="flex justify-center">
                                            <Empty :description="'Tabla vacía'" :image="'/img/empty-box.png'" />
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </AppLayout>
</template>
