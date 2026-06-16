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

const props = defineProps({
    supply: { type: Object, default: () => ({}) },
    units: { type: Array, default: () => [] },
});

const form = useForm({
    id: props.supply.id,
    name: props.supply.name,
    unit: props.supply.unit,
    stock_min: props.supply.stock_min,
    status: !!props.supply.status,
    notes: props.supply.notes || '',
    adjust_qty: '',
    adjust_notes: '',
});

const updateSupply = () => {
    form.post(route('res_supplies_update'), {
        preserveScroll: true,
        onSuccess: () => {
            Swal2.fire({
                title: 'Enhorabuena',
                text: 'Insumo actualizado correctamente',
                icon: 'success',
            });
        },
    });
};
</script>

<template>
    <FormSection @submitted="updateSupply">
        <template #title>
            Editar insumo
        </template>

        <template #description>
            Stock actual: <strong>{{ Number(supply.stock).toFixed(2) }} {{ supply.unit }}</strong>.
            Los campos con * son obligatorios.
        </template>

        <template #form>
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="name" value="Nombre *" class="mb-1" />
                <TextInput id="name" v-model="form.name" />
                <InputError :message="form.errors.name" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-2">
                <InputLabel for="unit" value="Unidad *" class="mb-1" />
                <Select
                    id="unit"
                    v-model:value="form.unit"
                    class="w-full"
                    :options="units.map((u) => ({ value: u, label: u }))"
                />
                <InputError :message="form.errors.unit" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-2">
                <InputLabel for="stock_min" value="Stock mínimo" class="mb-1" />
                <TextInput id="stock_min" v-model="form.stock_min" type="number" step="0.0001" min="0" />
                <InputError :message="form.errors.stock_min" class="mt-2" />
            </div>
            <div class="col-span-6">
                <InputLabel for="notes" value="Notas" class="mb-1" />
                <textarea
                    id="notes"
                    v-model="form.notes"
                    rows="3"
                    class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                />
            </div>
            <div class="col-span-6">
                <div class="flex items-center">
                    <input id="status" v-model="form.status" type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500" />
                    <label for="status" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Activo</label>
                </div>
            </div>
            <div class="col-span-6 border-t border-gray-200 dark:border-gray-700 pt-4">
                <h4 class="font-medium mb-2">Ajuste manual de stock (opcional)</h4>
                <p class="text-sm text-gray-500 mb-3">Cantidad positiva suma; negativa resta.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="adjust_qty" value="Cantidad (+/-)" class="mb-1" />
                        <TextInput id="adjust_qty" v-model="form.adjust_qty" type="number" step="0.0001" />
                    </div>
                    <div>
                        <InputLabel for="adjust_notes" value="Motivo" class="mb-1" />
                        <TextInput id="adjust_notes" v-model="form.adjust_notes" placeholder="Ej. conteo físico" />
                    </div>
                </div>
            </div>
        </template>

        <template #actions>
            <Keypad>
                <template #botones>
                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Guardar cambios
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
