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
        return props.paymentMethods.find(item => item.id === idToFind);
    };


</script>
<template>
    <AppLayout title="Reportes">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Academic'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <Link :href="route('aca_reports_dashboard')" class="text-primary hover:underline">Reportes</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Reporte de pagos de alumnos por banco</span>
            </li>
        </Navigation>
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
            <div class="mt-6">
                <div class="panel">
                    <table>
                        <thead>
                            <tr>
                                <th>FECHA</th>
                                <th>NOMBRE O RAZON SOCIAL</th>
                                <th>CURSOS</th>
                                <th>CELULAR</th>
                                <th>ALUMNO</th>
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
                                        <span v-if="item.document.forma_pago == 'Credito'" class="relative inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-gray-500 text-white z-10">
                                            Al crédito
                                        </span>
                                        <span v-else class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-yellow-500 text-white">Al contado</span>
                                    </td>
                                    <td>
                                        {{ item.total}}
                                    </td>
                                    <td>
                                        <div class="ps relative mb-4 max-h-[290px] ltr:pr-3 rtl:pl-3 ltr:-mr-3 rtl:-ml-3 ps--active-y">
                                            <div class="text-sm cursor-pointer">
                                                <div v-for="pay in item.payments" class="flex items-center py-1.5 relative group">
                                                    <div class="bg-success w-1.5 h-1.5 rounded-full ltr:mr-1 rtl:ml-1.5"></div>
                                                    <div class="flex-1">
                                                        {{ findObjectById(pay.type).description }}
                                                    </div>
                                                    <div class="ltr:ml-auto rtl:mr-auto text-xs text-white-dark dark:text-gray-500">{{ pay.amount }}</div>
                                                    <span v-if="pay.reference" class="badge badge-outline-success absolute ltr:right-0 rtl:left-0 text-xs bg-success-light dark:bg-[#0e1726] opacity-0 group-hover:opacity-100">CÓDIGO: {{ pay.reference }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <template v-else>
                                <tr>
                                    <td colspan="7" class="text-center">
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
