<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import AracoditoSettings from './Partials/AracoditoSettings.vue';
    import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <AppLayout title="Asistente">
        <Head>
            <meta name="aracodito-view" content="new" />
        </Head>

        <ul class="flex space-x-2 rtl:space-x-reverse">
            <li>
                <Link :href="route('dashboard')" class="text-primary hover:underline">Dashboard</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Asistente</span>
            </li>
        </ul>

        <div class="pt-5">
            <AracoditoSettings />
        </div>
    </AppLayout>
</template>
