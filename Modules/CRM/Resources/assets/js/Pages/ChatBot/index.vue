<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import FormIA from './Partials/FormIA.vue'

    import { ref, computed, onMounted } from 'vue';
</script>
<template>
    <AppLayout title="ChatBot">
        <Navigation :routeModule="route('crm_dashboard')" :titleModule="'CRM'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Chatbot</span>
            </li>
        </Navigation>
        <div class="mt-6 relative overflow-hidden panel">
            <FormIA />
        </div>
    </AppLayout>
</template>
