<script setup>
    import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
    import CreateForm from './Partials/CreateForm.vue';
    import { Link, useForm } from '@inertiajs/vue3';
    import { faTrashAlt } from "@fortawesome/free-solid-svg-icons";
    import IconPlus from '@/Components/vristo/icon/icon-plus.vue';
    import IconSearch from '@/Components/vristo/icon/icon-search.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { ConfigProvider, Dropdown, Menu, MenuItem, Button } from 'ant-design-vue';
    import Pagination from '@/Components/Pagination.vue';

    defineProps({
        courses:{
            type: Object,
            default : () => ({})
        }
    });

</script>

<template>
    <AppLayout title="Certificados">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'">
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <Link :href="route('aca_certificate_list')" class="text-primary hover:underline">Certificados</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>Nuevo</span>
            </li>
        </Navigation>
        <div class="pt-5">
            <CreateForm :courses="courses" />
        </div>
    </AppLayout>
</template>
