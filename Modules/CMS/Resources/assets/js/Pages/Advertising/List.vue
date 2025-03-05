<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import IconChatDot from '@/Components/vristo/icon/icon-chat-dots.vue';
    import Keypad from '@/Components/Keypad.vue';
    import DataTable from 'datatables.net-vue3';
    import DataTablesCore from 'datatables.net';
    import 'datatables.net-responsive';
    import '@/Components/vristo/datatables/datatables.css'
    import '@/Components/vristo/datatables/style.css'
    import 'datatables.net-buttons'
    import 'datatables.net-buttons/js/buttons.html5';
    import es_PE from '@/Components/vristo/datatables/datatables-es.js'

    import Swal2 from "sweetalert2";
    import { Link, router } from '@inertiajs/vue3';
</script>
<template>
    <AppLayout title="Contactos">
        <Navigation :routeModule="route('cms_dashboard')" :titleModule="'CMS'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Publicidad</span>
            </li>
        </Navigation>
        <div class="mt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl">Publicaciones </h2>
                <div class="flex sm:flex-row flex-col sm:items-center sm:gap-3 gap-4 w-full sm:w-auto">
                    <div class="flex gap-3">
                        <Keypad>
                            <template #botones>
                                <Link v-can="'cms_publicidad'" :href="route('cms_advertising_create')" class="btn btn-primary text-xs">NUEVO</Link>
                            </template>
                        </Keypad>

                    </div>
                </div>
            </div>
            <div class="panel pb-1.5 mt-6">
            </div>
        </div>
    </AppLayout>
</template>
