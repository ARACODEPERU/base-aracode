<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { Link, router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ComandasGrid from './Partials/ComandasGrid.vue';
import { ConfigProvider } from 'ant-design-vue';
import esES from 'ant-design-vue/es/locale/es_ES';

const props = defineProps({
    comandas: {
        type: Object,
        default: () => ({}),
    }
});

const refreshKitchen = () => {
    router.reload({ only: ['comandas'] });
};
</script>
<template>
    <AppLayout title="Cocina">
        <Navigation
            :routeModule="route('res_dashboard')"
            titleModule="Restaurante"
            :data="[{ title: 'Ventas', route: route('res_sales_list') }, { title: 'Cocina' }]"
        />
        <div class="pt-5 space-y-8">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl">Vista cocina</h2>
                <PrimaryButton type="button" @click="refreshKitchen">Actualizar</PrimaryButton>
            </div>
            <ConfigProvider :locale="esES">
                <ComandasGrid :comandas="comandas" />
            </ConfigProvider>
        </div>
    </AppLayout>
</template>
