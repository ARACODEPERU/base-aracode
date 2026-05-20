<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import Navigation from "@/Components/vristo/layout/Navigation.vue";
import Pagination from "@/Components/Pagination.vue";
import Keypad from "@/Components/Keypad.vue";
import { Link, router, useForm } from "@inertiajs/vue3";
import { faPencilAlt, faTrashAlt } from "@fortawesome/free-solid-svg-icons";
import Swal2 from "sweetalert2";

const props = defineProps({
    clients: {
        type: Object,
        default: () => ({}),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const form = useForm({
    search: props.filters.search,
});

const clearSearch = () => {
    form.search = null;
    form.get(route("comm_clients"), {
        preserveState: true,
        preserveScroll: true,
    });
};

const destroy = (client) => {
    Swal2.fire({
        title: "Estas seguro?",
        text: `Esta accion eliminara permanentemente a ${client.full_name}.`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Eliminar",
        cancelButtonText: "Cancelar",
        showLoaderOnConfirm: true,
        padding: "2em",
        customClass: "sweet-alerts",
        preConfirm: () => {
            return axios.delete(route("comm_clients_destroy", client.id)).then((res) => {
                if (res.data && !res.data.success) {
                    Swal2.showValidationMessage(res.data.message || "Error al eliminar");
                }

                return res;
            }).catch((error) => {
                Swal2.showValidationMessage(error.response?.data?.message || "Error de conexion");
            });
        },
        allowOutsideClick: () => !Swal2.isLoading(),
    }).then((result) => {
        if (!result.isConfirmed) return;

        Swal2.fire({
            title: "Enhorabuena",
            text: "Se elimino correctamente",
            icon: "success",
            padding: "2em",
            customClass: "sweet-alerts",
        });

        router.visit(route("comm_clients"), {
            replace: false,
            method: "get",
            preserveState: true,
            preserveScroll: true,
            only: ["clients"],
        });
    });
};
</script>

<template>
    <AppLayout title="Clientes">
        <Navigation :routeModule="route('comm_dashboard')" titleModule="Comercial"
            :data="[
                {title: 'Clientes'}
            ]"
        />

        <div class="mt-5 panel p-0">
            <div class="w-full p-4 border-b border-gray-200 dark:border-gray-700">
                <div class="grid grid-cols-3 gap-4">
                    <div class="col-span-3 sm:col-span-1">
                        <form @submit.prevent="form.get(route('comm_clients'), { preserveState: true })">
                            <label for="table-search-clients" class="sr-only">Buscar</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <input
                                    id="table-search-clients"
                                    v-model="form.search"
                                    type="text"
                                    class="form-input ltr:pl-10 ltr:pr-10 rtl:pr-10 rtl:pl-10"
                                    placeholder="Buscar por cliente, documento, telefono o email"
                                />
                                <button
                                    v-if="form.search"
                                    type="button"
                                    class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500 hover:text-red-500 dark:text-gray-400 dark:hover:text-red-400"
                                    v-tippy="{ content: 'Limpiar busqueda', placement: 'bottom'}"
                                    @click="clearSearch"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="col-span-3 sm:col-span-2">
                        <Keypad>
                            <template #botones>
                                <Link v-can="'comm_clientes_nuevo'" :href="route('comm_clients_create')" class="btn btn-primary">
                                    Nuevo
                                </Link>
                            </template>
                        </Keypad>
                    </div>
                </div>
            </div>
            <Pagination :data="clients">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Num.</th>
                            <th class="text-center">Accion</th>
                            <th>Cliente</th>
                            <th>DNI/RUC</th>
                            <th>Telefono</th>
                            <th>Email</th>
                            <th>Direccion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(client, index) in clients.data" :key="client.id">
                            <td>{{ clients.from + index }}</td>
                            <td class="text-center">
                                <div class="flex gap-2 items-center justify-center">
                                    <Link v-can="'comm_clientes_editar'" :href="route('comm_clients_edit', client.id)" class="btn btn-success btn-sm">
                                        <font-awesome-icon :icon="faPencilAlt" />
                                    </Link>
                                    <button v-can="'comm_clientes_eliminar'" type="button" class="btn btn-danger btn-sm" @click="destroy(client)">
                                        <font-awesome-icon :icon="faTrashAlt" />
                                    </button>
                                </div>
                            </td>
                            <td>
                                <p class="font-semibold">{{ client.full_name }}</p>
                                <small class="text-xs text-gray-500">{{ client.ubigeo_description }}</small>
                            </td>
                            <td>
                                <small class="text-xs text-gray-600">{{ client.document_type_id == 1 ? "DNI" : client.document_type_id == 6 ? "RUC" : "OTROS" }}</small>
                                <p>{{ client.number }}</p>
                            </td>
                            <td>{{ client.telephone }}</td>
                            <td>{{ client.email }}</td>
                            <td>{{ client.address }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            </Pagination>
        </div>
    </AppLayout>
</template>
