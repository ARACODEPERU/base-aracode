<script setup>
    import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
    import EditForm from './Partials/EditForm.vue';
    import { Link, useForm } from '@inertiajs/vue3';
    import { faTrashAlt } from "@fortawesome/free-solid-svg-icons";
    import IconPlus from '@/Components/vristo/icon/icon-plus.vue';
    import IconSearch from '@/Components/vristo/icon/icon-search.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { ConfigProvider, Dropdown, Menu, MenuItem, Button } from 'ant-design-vue';
    import Pagination from '@/Components/Pagination.vue';

    defineProps({
        certificate:{
            type: Object,
            default : () => ({})
        }
    });

</script>

<template>
    <AppLayout title="Certificados">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                {
                    route: route('aca_certificate_list'),
                    title: 'Certificados',
                },
                {title: 'Editar'}
            ]"
        />
        <div class="pt-5">
            <EditForm :certificate="certificate" />
        </div>
    </AppLayout>
</template>
