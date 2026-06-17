<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Keypad from '@/Components/Keypad.vue';
import Swal2 from 'sweetalert2';
import { Select } from 'ant-design-vue';

defineProps({
    units: { type: Array, default: () => [] },
});

const form = useForm({
    name: '',
    unit: 'unidad',
    stock: 0,
    stock_min: 1,
    status: true,
    notes: '',
});

const createSupply = () => {
    form.post(route('res_supplies_store'), {
        preserveScroll: true,
        onSuccess: () => {
            Swal2.fire({
                title: 'Enhorabuena',
                text: 'Insumo registrado correctamente',
                icon: 'success',
            });
        },
    });
};
</script>

<template>
    <FormSection @submitted="createSupply">
        <template #title>
            Nuevo insumo
        </template>

        <template #description>
            Registre un insumo para el inventario de cocina. Los campos con * son obligatorios.
        </template>

        <template #form>
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="name" value="Nombre del insumo *" class="mb-1" />
                <TextInput id="name" v-model="form.name" />
                <InputError :message="form.errors.name" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-2">
                <InputLabel for="unit" value="Unidad *" class="mb-1" />
                <Select
                    id="unit"
                    v-model:value="form.unit"
                    class="w-full"
                    placeholder="Seleccionar unidad"
                    :options="units.map((u) => ({ value: u, label: u }))"
                />
                <InputError :message="form.errors.unit" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-2">
                <InputLabel for="stock" value="Stock inicial" class="mb-1" />
                <TextInput id="stock" v-model="form.stock" type="number" step="0.0001" min="0" />
                <InputError :message="form.errors.stock" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-2">
                <InputLabel for="stock_min" value="Stock mínimo (alerta)" class="mb-1" />
                <TextInput id="stock_min" v-model="form.stock_min" type="number" step="0.0001" min="0" />
                <InputError :message="form.errors.stock_min" class="mt-2" />
            </div>
            <div class="col-span-6">
                <InputLabel for="notes" value="Notas (opcional)" class="mb-1" />
                <textarea
                    id="notes"
                    v-model="form.notes"
                    rows="3"
                    class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                />
                <InputError :message="form.errors.notes" class="mt-2" />
            </div>
            <div class="col-span-6">
                <div class="flex items-center">
                    <input
                        id="status"
                        v-model="form.status"
                        type="checkbox"
                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                    />
                    <label for="status" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Activo</label>
                </div>
            </div>
        </template>

        <template #actions>
            <Keypad>
                <template #botones>
                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Guardar
                    </PrimaryButton>
                    <Link
                        :href="route('res_supplies_list')"
                        class="ml-2 inline-block px-6 py-2.5 bg-green-500 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-green-600 transition duration-150 ease-in-out"
                    >
                        Ir al listado
                    </Link>
                </template>
            </Keypad>
        </template>
    </FormSection>
</template>
