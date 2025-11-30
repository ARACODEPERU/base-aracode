<script setup>
    import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
    import CreateForm from './Partials/CreateForm.vue';
    import { Link } from '@inertiajs/vue3';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';


    defineProps({
        courses: {
            type: Object,
            default: () => ({})
        },
        types: {
            type: Object,
            default: () => ({})
        },
        identityDocumentTypes: {
            type: Object,
            default: () => ({})
        },
        countries: {
            type: Object,
            default: () => ({})
        },
        ubigeo: {
            type: Object,
            default: () => ({})
        },
        saleDocumentTypes: {
            type: Object,
            default: () => ({})
        },
        payments: {
            type: Object,
            default: () => ({})
        },
        igv: {
            type: Number,
            default: 18 // Valor por defecto si no se pasa desde el padre
        },
        subscriptionTypes: {
            type: Object,
            default: () => ({})
        }
    });

</script>

<template>
    <AppLayout title="Crear Curso">
        <Navigation :routeModule="route('sales_dashboard')" :titleModule="'Cuentas por cobrar'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <Link :href="route('acco_sales_special_rates')" class="text-primary hover:underline">Gestión Ventas en Cuotas</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Nuevo</span>
            </li>
        </Navigation>
        <div class="pt-5">
            <div class="panel">
                <CreateForm
                    :courses="courses"
                    :types="types"
                    :identityDocumentTypes="identityDocumentTypes"
                    :countries="countries"
                    :ubigeo="ubigeo"
                    :saleDocumentTypes="saleDocumentTypes"
                    :payments="payments"
                    :igv="igv"
                    :subscriptionTypes="subscriptionTypes"
                />
            </div>
        </div>
    </AppLayout>
</template>
