<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import EditForm from './Partials/EditForm.vue';
import { Link } from '@inertiajs/vue3';
import Navigation from '@/Components/vristo/layout/Navigation.vue';

const props = defineProps({
    identityDocumentTypes: {
        type: Object,
        default: () => ({}),
    },
    ubigeo: {
        type: Object,
        default: () => ({})
    },
    industry: {
        type: Object,
        default: () => ({})
    },
    empresa: {
        type: Object,
        default: () => ({})
    }
});

</script>

<template>
    <AppLayout title="Editar empresa">
        <Navigation :routeModule="route('crm_dashboard')" :titleModule="'CRM'">
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <Link :href="route('crm_companies_list')" class="text-primary hover:underline">Empresas</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>Editar</span>
            </li>
        </Navigation>
        <div class="pt-5">
            <EditForm 
                :identityDocumentTypes="identityDocumentTypes" 
                :ubigeo="ubigeo"
                :industry="industry"
                :empresa="empresa"
            /> 
        </div>
    </AppLayout>
</template>
