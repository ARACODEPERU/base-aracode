<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import CreateForm from './Pertials/createForm.vue';

const props = defineProps({
    roles: {
        type: Object,
        default: () => ({}),
    },
    modulos: {
        type: Object,
        default: () => ({}),
    }
});
</script>

<template>
    <AppLayout title="Crear Permisos">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Nuevo Permiso
            </h2>
        </template>

        <div class="mt-6">

            <CreateForm :roles="roles" :modulos="modulos"></CreateForm>

        </div>
    </AppLayout>
</template>
