<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import Navigation from "@/Components/vristo/layout/Navigation.vue";
import Pagination from "@/Components/Pagination.vue";
import Keypad from "@/Components/Keypad.vue";
import { Link, router, useForm } from "@inertiajs/vue3";
import { faEye, faLink, faPencilAlt, faTrashAlt } from "@fortawesome/free-solid-svg-icons";
import Swal2 from "sweetalert2";

const props = defineProps({
    negotiations: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
    statuses: { type: Array, default: () => [] },
    paymentMethods: { type: Array, default: () => [] },
});

const form = useForm({
    search: props.filters.search,
});

const statusByValue = (value) => props.statuses.find((item) => item.value === value);

const paymentMethodLabel = (value) => props.paymentMethods.find((item) => item.value === value)?.label || value;

const clientName = (negotiation) => negotiation.client_data?.full_name || negotiation.client?.full_name || "Sin cliente";

const clientDocument = (negotiation) => negotiation.client_data?.number || negotiation.client?.number || "";

const fullUrl = (token) => {
    const url = route("comm_negotiations_public_show", token);
    return url.startsWith("http") ? url : window.location.origin + url;
};

const clearSearch = () => {
    form.search = null;
    form.get(route("comm_negotiations"), {
        preserveState: true,
        preserveScroll: true,
    });
};

const copyLink = (negotiation) => {
    navigator.clipboard.writeText(fullUrl(negotiation.token)).then(() => {
        Swal2.fire({
            title: "Enlace copiado",
            text: "El enlace publico de la negociacion fue copiado al portapapeles.",
            icon: "success",
            padding: "2em",
            customClass: "sweet-alerts",
        });
    }).catch(() => {
        Swal2.fire({
            title: "Error",
            text: "No se pudo copiar el enlace.",
            icon: "error",
            padding: "2em",
            customClass: "sweet-alerts",
        });
    });
};

const destroy = (negotiation) => {
    Swal2.fire({
        title: "Estas seguro?",
        text: `Esta accion eliminara permanentemente la negociacion ${negotiation.title}.`,
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
            return axios.delete(route("comm_negotiations_destroy", negotiation.id)).then((res) => {
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

        router.visit(route("comm_negotiations"), {
            replace: false,
            method: "get",
            preserveState: true,
            preserveScroll: true,
            only: ["negotiations"],
        });
    });
};
</script>

<template>
    <AppLayout title="Negociaciones">
        <Navigation :routeModule="route('comm_dashboard')" titleModule="Comercial"
            :data="[
                {title: 'Negociaciones'}
            ]"
        />

        <div class="mt-5 panel p-0">
            <div class="w-full p-4 border-b border-gray-200 dark:border-gray-700">
                <div class="grid grid-cols-3 gap-4">
                    <div class="col-span-3 sm:col-span-1">
                        <form @submit.prevent="form.get(route('comm_negotiations'), { preserveState: true })">
                            <label for="table-search-negotiations" class="sr-only">Buscar</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <input
                                    id="table-search-negotiations"
                                    v-model="form.search"
                                    type="text"
                                    class="form-input ltr:pl-10 ltr:pr-10 rtl:pr-10 rtl:pl-10"
                                    placeholder="Buscar por titulo, cliente o documento"
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
                                <Link v-can="'comm_negociaciones_nuevo'" :href="route('comm_negotiations_create')" class="btn btn-primary">
                                    Nuevo
                                </Link>
                            </template>
                        </Keypad>
                    </div>
                </div>
            </div>

            <Pagination :data="negotiations">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Num.</th>
                                <th class="text-center">Accion</th>
                                <th>Negociacion</th>
                                <th>Cliente</th>
                                <th>Items</th>
                                <th>Monto</th>
                                <th>Medio de pago</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(negotiation, index) in negotiations.data" :key="negotiation.id">
                                <td>{{ negotiations.from + index }}</td>
                                <td class="text-center">
                                    <div class="flex gap-2 items-center justify-center">
                                        <Link v-can="'comm_negociaciones_listado'" :href="route('comm_negotiations_show', negotiation.id)" class="btn btn-info btn-sm" v-tippy="{ content: 'Ver detalle', placement: 'bottom'}">
                                            <font-awesome-icon :icon="faEye" />
                                        </Link>
                                        <button type="button" class="btn btn-secondary btn-sm" v-tippy="{ content: 'Copiar enlace publico', placement: 'bottom'}" @click="copyLink(negotiation)">
                                            <font-awesome-icon :icon="faLink" />
                                        </button>
                                        <Link v-can="'comm_negociaciones_editar'" :href="route('comm_negotiations_edit', negotiation.id)" class="btn btn-success btn-sm">
                                            <font-awesome-icon :icon="faPencilAlt" />
                                        </Link>
                                        <button
                                            v-can="'comm_negociaciones_eliminar'"
                                            type="button"
                                            class="btn btn-danger btn-sm"
                                            :disabled="negotiation.status === 'confirmada'"
                                            v-tippy="{ content: negotiation.status === 'confirmada' ? 'No se puede eliminar: el alumno confirmo sus datos' : 'Eliminar', placement: 'bottom'}"
                                            @click="destroy(negotiation)"
                                        >
                                            <font-awesome-icon :icon="faTrashAlt" />
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <p class="font-semibold">{{ negotiation.title }}</p>
                                    <small class="text-xs text-gray-500">
                                        {{ negotiation.contact_channel ? `${negotiation.contact_channel} ${negotiation.contact_detail || ''}` : 'Sin canal de contacto' }}
                                    </small>
                                </td>
                                <td>
                                    <p>{{ clientName(negotiation) }}</p>
                                    <small class="text-xs text-gray-500">{{ clientDocument(negotiation) }}</small>
                                </td>
                                <td>
                                    <small class="text-xs text-gray-500">{{ negotiation.items.length }} item(s)</small>
                                </td>
                                <td>{{ negotiation.currency }} {{ negotiation.total_price }}</td>
                                <td>{{ paymentMethodLabel(negotiation.payment_method) }}</td>
                                <td>
                                    <span class="badge" :class="`bg-${statusByValue(negotiation.status)?.color || 'secondary'}`">
                                        {{ statusByValue(negotiation.status)?.label || negotiation.status }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Pagination>
        </div>
    </AppLayout>
</template>
