<script setup>
import { reactive, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import ModalMedium from '@/Components/ModalMedium.vue';
import { faPlus, faPen, faTrash } from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
    prices: { type: Array, default: () => [] },
    providers: { type: Array, default: () => [] },
});

const modalAbierto = ref(false);
const editando = ref(null);
const guardando = ref(false);

const form = reactive({
    provider: 'openai',
    model: '',
    input: 0,
    output: 0,
    active: true,
});

const usd = (valor) => `US$ ${Number(valor || 0).toFixed(4)}`;

const abrirNuevo = () => {
    editando.value = null;
    form.provider = 'openai';
    form.model = '';
    form.input = 0;
    form.output = 0;
    form.active = true;
    modalAbierto.value = true;
};

const abrirEdicion = (price) => {
    editando.value = price;
    form.provider = price.provider;
    form.model = price.model;
    form.input = price.input;
    form.output = price.output;
    form.active = price.active;
    modalAbierto.value = true;
};

const cerrar = () => {
    if (guardando.value) {
        return;
    }

    modalAbierto.value = false;
    editando.value = null;
};

const guardar = async () => {
    if (guardando.value) {
        return;
    }

    guardando.value = true;

    try {
        const url = editando.value
            ? route('ai_precios_update', editando.value.id)
            : route('ai_precios_store');

        const { data } = editando.value
            ? await axios.put(url, { ...form })
            : await axios.post(url, { ...form });

        modalAbierto.value = false;
        editando.value = null;
        window.alertaSistema('Precio guardado', data.message, 'success');
        router.reload({ only: ['prices'] });
    } catch (error) {
        const errores = error.response?.data?.errors || {};
        const primero = Object.values(errores).flat()[0];

        window.alertaSistema(
            'No se pudo guardar',
            primero || error.response?.data?.message || 'Revisa los datos e intenta nuevamente.',
            'error'
        );
    } finally {
        guardando.value = false;
    }
};

const eliminar = async (price) => {
    const borrar = async () => {
        const { data } = await axios.delete(route('ai_precios_destroy', price.id));
        router.reload({ only: ['prices'] });

        return { message: data.message, type: 'success' };
    };

    const result = await window.alertaSistema(
        'Eliminar precio',
        `Se quitará la tarifa de ${price.model}. Se usará la tarifa de respaldo hasta que cargues otra.`,
        'warning',
        {
            duration: 0,
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            loadingText: 'Eliminando precio...',
            errorMessage: 'No se pudo eliminar el precio.',
            showLoaderOnConfirm: true,
            onConfirm: borrar,
            preConfirm: borrar,
        }
    );

    if (!result?.isConfirmed) {
        return;
    }

    if (window.alertaSistema.enAracodito?.()) {
        return;
    }

    if (result.value) {
        window.alertaSistema('Precio eliminado', result.value.message, 'success');
    }
};
</script>

<template>
    <AppLayout title="Precios de IA">
        <Head title="Precios de IA">
            <meta name="aracodito-view" content="new" />
        </Head>

        <Navigation :routeModule="route('ai_dashboard')" :titleModule="'IA'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Precios</span>
            </li>
        </Navigation>

        <div class="mt-5 space-y-5">
            <div class="panel flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold">Tarifas por modelo</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        En dólares por millón de tokens. El costo que muestra el sistema es una estimación con estas tarifas.
                    </p>
                </div>
                <button type="button" class="btn btn-primary" @click="abrirNuevo">
                    <font-awesome-icon :icon="faPlus" class="mr-1" /> Nuevo precio
                </button>
            </div>

            <div class="panel">
                <div class="table-responsive">
                    <table class="table-hover w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-left dark:border-gray-700">
                                <th class="p-4 font-semibold">Proveedor</th>
                                <th class="p-4 font-semibold">Modelo</th>
                                <th class="p-4 text-right font-semibold">Entrada / millón</th>
                                <th class="p-4 text-right font-semibold">Salida / millón</th>
                                <th class="p-4 font-semibold">Estado</th>
                                <th class="p-4 text-right font-semibold">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="price in prices" :key="price.id" class="border-b border-gray-100 dark:border-gray-800">
                                <td class="p-4 capitalize">{{ price.provider }}</td>
                                <td class="p-4 font-mono">{{ price.model }}</td>
                                <td class="p-4 text-right">{{ usd(price.input) }}</td>
                                <td class="p-4 text-right">{{ usd(price.output) }}</td>
                                <td class="p-4">
                                    <span
                                        class="rounded px-2 py-1 text-xs"
                                        :class="price.active ? 'bg-success/20 text-success' : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300'"
                                    >
                                        {{ price.active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="p-4 text-right">
                                    <button type="button" class="btn btn-sm btn-outline-primary mr-1" @click="abrirEdicion(price)">
                                        <font-awesome-icon :icon="faPen" />
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" @click="eliminar(price)">
                                        <font-awesome-icon :icon="faTrash" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!prices.length">
                                <td colspan="6" class="p-6 text-center text-gray-500 dark:text-gray-400">
                                    No hay tarifas cargadas. Se usan las tarifas de respaldo del sistema.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <ModalMedium :show="modalAbierto" :on-close="cerrar">
            <template #title>{{ editando ? 'Editar precio' : 'Nuevo precio' }}</template>
            <template #message>
                <span>Tarifa en USD por millón de tokens.</span>
            </template>
            <template #content>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Proveedor</label>
                        <select v-model="form.provider" class="form-select w-full">
                            <option v-for="option in providers" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Modelo</label>
                        <input v-model="form.model" type="text" class="form-input w-full" placeholder="gpt-4.1-mini" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Entrada / millón (USD)</label>
                        <input v-model="form.input" type="number" min="0" step="0.0001" class="form-input w-full" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Salida / millón (USD)</label>
                        <input v-model="form.output" type="number" min="0" step="0.0001" class="form-input w-full" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="flex items-center gap-2 text-sm">
                            <input v-model="form.active" type="checkbox" class="form-checkbox" />
                            Tarifa activa
                        </label>
                    </div>
                </div>
            </template>
            <template #buttons>
                <button type="button" class="btn btn-outline-primary" :disabled="guardando" @click="cerrar">Cancelar</button>
                <button type="button" class="btn btn-primary" :disabled="guardando" @click="guardar">
                    <span v-if="guardando" class="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                    {{ guardando ? 'Guardando...' : 'Guardar' }}
                </button>
            </template>
        </ModalMedium>
    </AppLayout>
</template>
