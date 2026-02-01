<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import EditForm from './Pertials/editForm.vue';

    const props = defineProps({
        roles: {
            type: Object,
            default: () => ({}),
        },
        permission: {
            type: Object,
            default: () => ({}),
        },
        roleHasPermissions: {
            type: Object,
            default: () => ({}),
        },
        modulos: {
            type: Object,
            default: () => ({}),
        },
        moduleIds: {
            type: Array,
            default: () => ([]),
        }
    });
</script>

<template>
    <AppLayout title="Editar Rol">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Editar Permiso
            </h2>
        </template>

        <div class="mt-6">

            <EditForm
                :roles="roles"
                :permission="permission"
                :roleHasPermissions="roleHasPermissions"
                :modulos="modulos"
                :moduleIds="moduleIds"
            >
            </EditForm>

        </div>
    </AppLayout>
</template>
