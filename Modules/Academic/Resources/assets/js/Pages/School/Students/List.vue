<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import Swal2 from 'sweetalert2';
import { computed } from 'vue';
import { faPencil, faTrash, faUserPlus, faUsers, faMoneyBillWave } from '@fortawesome/free-solid-svg-icons';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

const permissions = computed(() => page.props.auth?.permissions || []);

const hasPermission = (permission) => {
    if (!permission) {
        return true;
    }

    return permissions.value.includes(permission);
};

const props = defineProps({
    students: { type: Object, default: () => ({}) },
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

const destroyStudent = (id) => {
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
            return axios.delete(route('aca_school_students_destroy', id)).then((res) => {
                if (!res.data.success) {
                    Swal2.showValidationMessage(res.data.message);
                }
                return res;
            });
        },
        allowOutsideClick: () => !Swal2.isLoading(),
    }).then((result) => {
        if (result.isConfirmed) {
            showMessage('Alumno eliminado correctamente.');
            router.visit(route('aca_school_students_list'), {
                replace: true,
                method: 'get',
                preserveState: true,
                preserveScroll: true,
            });
        }
    });
};
</script>

<template>
    <AppLayout title="Alumnos Escolares">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                { route: route('aca_school_years_list'), title: 'Colegio' },
                { title: 'Alumnos' },
            ]"
        />
        <div class="pt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl">Alumnos Escolares</h2>
                <div class="flex sm:flex-row flex-col sm:items-center sm:gap-3 gap-4 w-full sm:w-auto">
                    <div class="flex gap-3">
                        <div>
                            <Link :href="route('aca_school_students_create')" type="button" class="btn btn-primary">
                                <font-awesome-icon :icon="faUserPlus" class="ltr:mr-2 rtl:ml-2" />
                                Nuevo
                            </Link>
                        </div>
                    </div>
                    <div class="relative">
                        <input
                            type="text"
                            placeholder="Buscar por nombre, DNI o código"
                            class="form-input py-2 ltr:pr-11 rtl:pl-11 peer"
                            v-model="form.search"
                            @keyup.enter="form.get(route('aca_school_students_list'))"
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
                                <th>Código</th>
                                <th>DNI</th>
                                <th>Alumno</th>
                                <th>Teléfono</th>
                                <th>Email</th>
                                <th class="!text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!students.data || students.data.length === 0">
                                <td colspan="7" class="text-center text-white-dark py-6">Sin alumnos registrados</td>
                            </tr>
                            <tr v-for="item in students.data" :key="item.id">
                                <td>
                                    <div class="flex gap-1 items-center justify-center">
                                        <Link v-tippy:bottom :href="route('aca_school_students_edit', item.id)" type="button" class="btn btn-sm btn-outline-primary">
                                            <font-awesome-icon :icon="faPencil" class="m-0" />
                                        </Link>
                                        <tippy target="bottom" placement="bottom">Editar</tippy>
                                        <Link v-if="hasPermission('aca_school_alumno_apoderados')" v-tippy:bottom :href="route('aca_school_students_edit', item.id)" type="button" class="btn btn-sm btn-outline-success">
                                            <font-awesome-icon :icon="faUsers" class="m-0" />
                                        </Link>
                                        <tippy target="bottom" placement="bottom">Apoderados</tippy>
                                        <Link v-if="item.active_enrollment_id" v-tippy:bottom :href="route('aca_school_charges_by_student', item.id)" type="button" class="btn btn-sm btn-outline-info">
                                            <font-awesome-icon :icon="faMoneyBillWave" class="m-0" />
                                        </Link>
                                        <tippy target="bottom" placement="bottom">Cobrar matrícula/mensualidad</tippy>
                                        <button v-tippy:bottom type="button" class="btn btn-sm btn-outline-danger" @click="destroyStudent(item.id)">
                                            <font-awesome-icon :icon="faTrash" />
                                        </button>
                                        <tippy target="bottom" placement="bottom">Eliminar</tippy>
                                    </div>
                                </td>
                                <td>{{ item.student_code }}</td>
                                <td>{{ item.person?.number }}</td>
                                <td>{{ item.person?.full_name }}</td>
                                <td>{{ item.person?.telephone }}</td>
                                <td>{{ item.person?.email }}</td>
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
