<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import Swal2 from 'sweetalert2';
import { faPencil, faTrash, faLandmarkFlag } from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
    schools: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const form = useForm({
    search: props.filters.search ?? '',
});

const showMessage = (msg = '', type = 'success') => {
    const toast = Swal2.mixin({
        toast: true,
        position: 'top',
        showConfirmButton: false,
        timer: 3000,
        customClass: { container: 'toast' },
    });
    toast.fire({ icon: type, title: msg, padding: '10px 20px' });
};

const destroySchool = (id) => {
    const swalConfirm = Swal2.mixin({
        customClass: {
            popup: 'sweet-alerts',
            confirmButton: 'btn btn-secondary',
            cancelButton: 'btn btn-dark ltr:mr-3 rtl:ml-3',
        },
        buttonsStyling: false,
    });
    swalConfirm.fire({
        title: '¿Estás seguro?',
        text: '¡No podrás revertir esto!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '¡Sí, eliminar!',
        cancelButtonText: 'Cancelar',
        showLoaderOnConfirm: true,
        reverseButtons: true,
        padding: '2em',
        customClass: 'sweet-alerts',
        preConfirm: () => {
            return axios.delete(route('aca_schools_destroy', id)).then((res) => {
                if (!res.data.success) {
                    Swal2.showValidationMessage(res.data.message);
                }
                return res;
            });
        },
        allowOutsideClick: () => !Swal2.isLoading(),
    }).then((result) => {
        if (result.isConfirmed) {
            showMessage('Colegio eliminado correctamente.');
            router.visit(route('aca_schools_list'), { replace: true, preserveState: true, preserveScroll: true });
        }
    });
};
</script>

<template>
    <AppLayout title="Colegios">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                { route: route('aca_schools_list'), title: 'Colegios' },
            ]"
        />
        <div class="pt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl">Colegios</h2>
                <div class="flex sm:flex-row flex-col sm:items-center sm:gap-3 gap-4 w-full sm:w-auto">
                    <Link :href="route('aca_schools_create')" type="button" class="btn btn-primary">
                        <font-awesome-icon :icon="faLandmarkFlag" class="ltr:mr-2 rtl:ml-2" />
                        Nuevo
                    </Link>
                    <div class="relative">
                        <input
                            type="text"
                            placeholder="Buscar"
                            class="form-input py-2 ltr:pr-11 rtl:pl-11"
                            v-model="form.search"
                            @keyup.enter="form.get(route('aca_schools_list'))"
                        />
                    </div>
                </div>
            </div>

            <div class="mt-5 panel p-0 border-0 overflow-hidden">
                <div class="table-responsive">
                    <table class="table-striped table-hover">
                        <thead>
                            <tr>
                                <th class="!text-center">Acciones</th>
                                <th>Nombre</th>
                                <th>Código modular</th>
                                <th>Dirección</th>
                                <th>Teléfono</th>
                                <th class="!text-center">Por defecto</th>
                                <th class="!text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!schools.data || schools.data.length === 0">
                                <td colspan="7" class="text-center text-white-dark py-6">Sin colegios registrados</td>
                            </tr>
                            <tr v-for="item in schools.data" :key="item.id">
                                <td>
                                    <div class="flex gap-1 items-center justify-center">
                                        <Link v-tippy:bottom :href="route('aca_schools_edit', item.id)" type="button" class="btn btn-sm btn-outline-primary">
                                            <font-awesome-icon :icon="faPencil" class="m-0" />
                                        </Link>
                                        <tippy target="bottom" placement="bottom">Editar</tippy>
                                        <button v-tippy:bottom type="button" class="btn btn-sm btn-outline-danger" @click="destroySchool(item.id)">
                                            <font-awesome-icon :icon="faTrash" />
                                        </button>
                                        <tippy target="bottom" placement="bottom">Eliminar</tippy>
                                    </div>
                                </td>
                                <td class="font-semibold">{{ item.name }}</td>
                                <td>{{ item.modular_code }}</td>
                                <td>{{ item.address }}</td>
                                <td>{{ item.phone }}</td>
                                <td class="text-center">
                                    <span v-if="item.is_default" class="badge bg-primary">Por defecto</span>
                                </td>
                                <td class="text-center">
                                    <span v-if="item.status" class="badge bg-success">Activo</span>
                                    <span v-else class="badge bg-danger">Inactivo</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
