<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import RecipeForm from './Partials/RecipeForm.vue';

const props = defineProps({
    comanda: { type: Object, default: () => ({}) },
    recipe: { type: Object, default: () => ({}) },
    supplies: { type: Array, default: () => [] },
    otherComandas: { type: Array, default: () => [] },
});

const initialItems = (props.recipe.items || []).map((i) => ({
    supply_id: i.supply_id,
    quantity: Number(i.quantity),
    name: i.supply?.name,
    unit: i.supply?.unit,
}));
</script>

<template>
    <AppLayout :title="`Receta: ${comanda.name}`">
        <Navigation
            :routeModule="route('res_dashboard')"
            titleModule="Restaurante"
            :data="[
                { title: 'Comandas', route: route('res_comandas_list') },
                { title: comanda.name, route: route('res_comandas_edit', comanda.id) },
                { title: 'Receta' },
            ]"
        />
        <div class="pt-5">
            <RecipeForm
                :comanda="comanda"
                :supplies="supplies"
                :other-comandas="otherComandas"
                :initial-items="initialItems"
            />
        </div>
    </AppLayout>
</template>
