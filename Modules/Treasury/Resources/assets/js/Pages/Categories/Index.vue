<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { Head, router } from '@inertiajs/vue3';
    import { Dropdown, Menu, MenuItem } from 'ant-design-vue';
    import Swal2 from 'sweetalert2';
    import ModalMedium from '@/Components/ModalMedium.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import InputError from '@/Components/InputError.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import { ref } from 'vue';
    import { faPencil, faTrash, faPlus } from '@fortawesome/free-solid-svg-icons';

    const props = defineProps({
        categories: { type: Array, default: () => [] },
    });

    const showModal = ref(false);
    const editingId = ref(null);

    // Cierre del modal del núcleo (reset form)
    const closeModal = () => {
        showModal.value = false;
        form.value = { name: '', applies_to: 'both', color: '#6366f1' };
        editingId.value = null;
    };

    const form = ref({
        name: '',
        applies_to: 'both',
        color: '#6366f1',
    });

    const appliesLabels = {
        income: 'Ingresos',
        expense: 'Egresos',
        both: 'Ambos',
    };

    // Toast de éxito (convención del proyecto)
    const toast = Swal2.mixin({
        toast: true,
        position: 'bottom-end',
        showConfirmButton: false,
        timer: 3000,
        padding: '2em',
        customClass: { container: 'toast' },
    });

    const appliesColors = {
        income: 'bg-success/10 text-success',
        expense: 'bg-danger/10 text-danger',
        both: 'bg-primary/10 text-primary',
    };

    const openCreate = () => {
        editingId.value = null;
        form.value = { name: '', applies_to: 'both', color: '#6366f1' };
        showModal.value = true;
    };

    const openEdit = (category) => {
        editingId.value = category.id;
        form.value = {
            name: category.name,
            applies_to: category.applies_to,
            color: category.color || '#6366f1',
        };
        showModal.value = true;
    };

    const save = () => {
        const url = editingId.value
            ? route('treasury_categories_update', editingId.value)
            : route('treasury_categories_store');

        router.post(url, form.value, {
            preserveScroll: true,
            onSuccess: () => {
                toast.fire({ icon: 'success', title: 'Categoría guardada' });
                showModal.value = false;
            },
            onError: () => Swal2.fire({ title: 'Error', text: 'Revisa los datos', icon: 'error', padding: '2em', customClass: 'sweet-alerts' }),
        });
    };

    const remove = (category) => {
        Swal2.fire({
            title: '¿Eliminar categoría?',
            text: 'No se pueden eliminar las categorías del sistema ni las que tienen movimientos.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '¡Sí, Eliminar!',
            cancelButtonText: 'Cancelar',
            padding: '2em',
            customClass: 'sweet-alerts',
            allowOutsideClick: () => !Swal2.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                router.delete(route('treasury_categories_destroy', category.id), {
                    preserveScroll: true,
                    onSuccess: () => toast.fire({ icon: 'success', title: 'Categoría eliminada' }),
                    onError: () => Swal2.fire({ title: 'Error', text: 'No se pudo eliminar la categoría', icon: 'error', padding: '2em', customClass: 'sweet-alerts' }),
                });
            }
        });
    };
</script>

<template>
    <AppLayout title="Categorías de Tesorería">
        <Head title="Tesorería · Categorías" />
        <Navigation>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Tesorería</span>
            </li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Categorías</span>
            </li>
        </Navigation>

        <div class="mt-5 space-y-5">
            <div class="panel flex flex-col gap-4 p-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Categorías de movimientos</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Clasifica tus ingresos y egresos (cobros de venta, planillas, honorarios, comisiones bancarias…)
                    </p>
                </div>
                <button type="button" class="btn btn-primary" @click="openCreate">
                    <font-awesome-icon :icon="faPlus" class="mr-1" /> Nueva categoría
                </button>
            </div>

            <div class="panel overflow-hidden p-0">
                <div class="table-responsive">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/60">
                                <th class="w-16 text-center">Acciones</th>
                                <th class="w-12"></th>
                                <th class="min-w-[220px]">Nombre</th>
                                <th class="w-32">Aplica a</th>
                                <th class="w-32 text-center">Movimientos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="category in categories"
                                :key="category.id"
                                class="hover:bg-gray-50/70 dark:hover:bg-gray-800/30"
                            >
                                <td class="text-center">
                                    <Dropdown placement="bottomLeft" arrow>
                                        <button
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700"
                                            type="button"
                                        >
                                            <font-awesome-icon :icon="faPencil" />
                                        </button>
                                        <template #overlay>
                                            <Menu>
                                                <MenuItem key="edit" @click="openEdit(category)">
                                                    {{ category.is_system ? 'Editar color' : 'Editar' }}
                                                </MenuItem>
                                                <MenuItem v-if="!category.is_system" key="delete" danger @click="remove(category)">
                                                    <span class="inline-flex items-center gap-2">
                                                        <font-awesome-icon :icon="faTrash" /> Eliminar
                                                    </span>
                                                </MenuItem>
                                            </Menu>
                                        </template>
                                    </Dropdown>
                                </td>
                                <td>
                                    <span
                                        class="block h-6 w-6 rounded-full border border-white/50 shadow"
                                        :style="{ backgroundColor: category.color || '#94a3b8' }"
                                    ></span>
                                </td>
                                <td class="font-medium text-gray-800 dark:text-gray-100">
                                    {{ category.name }}
                                    <span
                                        v-if="category.is_system"
                                        class="ml-2 rounded-md bg-gray-100 px-2 py-0.5 text-xs text-gray-500 dark:bg-gray-700 dark:text-gray-300"
                                    >
                                        Sistema
                                    </span>
                                </td>
                                <td>
                                    <span class="rounded-md px-2 py-1 text-xs font-medium" :class="appliesColors[category.applies_to]">
                                        {{ appliesLabels[category.applies_to] }}
                                    </span>
                                </td>
                                <td class="text-center text-gray-600 dark:text-gray-400">
                                    {{ category.transactions_count }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <ModalMedium :show="showModal" :on-close="closeModal" :icon="'/img/cuenta-bancaria.png'">
            <template #title>
                {{ editingId ? 'Editar categoría' : 'Nueva categoría' }}
            </template>
            <template #message>
                <span>Categorías para clasificar ingresos y egresos</span>
            </template>
            <template #content>
            <div class="space-y-4">
                <div>
                    <InputLabel value="Nombre *" class="mb-1.5" />
                    <input v-model="form.name" type="text" class="form-input w-full" placeholder="Ej: Publicidad, Mantenimiento…" />
                </div>
                <div>
                    <InputLabel value="Aplica a" class="mb-1.5" />
                    <select v-model="form.applies_to" class="form-select w-full">
                        <option value="both">Ingresos y egresos</option>
                        <option value="income">Solo ingresos</option>
                        <option value="expense">Solo egresos</option>
                    </select>
                </div>
                <div>
                    <InputLabel value="Color" class="mb-1.5" />
                    <div class="flex items-center gap-3">
                        <input v-model="form.color" type="color" class="h-10 w-16 cursor-pointer rounded border" />
                        <span class="font-mono text-sm text-gray-500">{{ form.color }}</span>
                    </div>
                </div>
            </div>
            </template>
            <template #buttons>
                <PrimaryButton @click="save">Guardar</PrimaryButton>
            </template>
        </ModalMedium>
    </AppLayout>
</template>
