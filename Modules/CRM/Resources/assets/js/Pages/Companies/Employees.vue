<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import { Link } from '@inertiajs/vue3';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import EmployeesBox from "./Partials/EmployeesBox.vue";

const props = defineProps({
    empresa: {
        type: Object,
        default: () => ({})
    },
    employees: {
        type: Object,
        default: () => ({}),
    }
});

</script>

<template>
    <AppLayout title="Empleados">
        <Navigation :routeModule="route('crm_dashboard')" :titleModule="'CRM'">
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <Link :href="route('crm_companies_list')" class="text-primary hover:underline">Empresas</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>{{ empresa.full_name }}</span>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>Empleados</span>
            </li>
        </Navigation>
        <div class="pt-5">
            <EmployeesBox
                :empresa="empresa"
                :employees="employees"
            />
        </div>
    </AppLayout>
</template>
