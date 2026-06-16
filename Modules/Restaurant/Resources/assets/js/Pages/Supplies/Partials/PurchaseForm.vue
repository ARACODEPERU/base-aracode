<script setup>
import FormSection from '@/Components/FormSection.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Keypad from '@/Components/Keypad.vue';
import ModalLarge from '@/Components/ModalLarge.vue';
import Swal2 from 'sweetalert2';
import { Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Select } from 'ant-design-vue';

const props = defineProps({
    supplies: { type: Array, default: () => [] },
});

const globalNotes = ref('');
const lines = ref([]);
const search = ref('');
const selectedSupplyId = ref(null);
const quantity = ref(1);
const lineNotes = ref('');
const saving = ref(false);
const showAddModal = ref(false);

const filteredSupplies = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.supplies;
    return props.supplies.filter((s) => s.name.toLowerCase().includes(q));
});

const selectedSupply = computed(() =>
    props.supplies.find((s) => s.id === selectedSupplyId.value)
);

const supplyOptions = computed(() =>
    filteredSupplies.value.map((s) => ({
        value: s.id,
        label: `${s.name} (${s.unit}) — stock: ${Number(s.stock).toFixed(2)}`,
    }))
);

const openAddModal = () => {
    selectedSupplyId.value = null;
    quantity.value = 1;
    lineNotes.value = '';
    search.value = '';
    showAddModal.value = true;
};

const closeAddModal = () => {
    showAddModal.value = false;
};

const addLine = () => {
    const supply = selectedSupply.value;
    if (!supply || !quantity.value || quantity.value <= 0) {
        Swal2.fire('Atención', 'Seleccione un insumo y cantidad válida', 'warning');
        return;
    }
    const existing = lines.value.find((l) => l.supply_id === supply.id);
    if (existing) {
        existing.quantity = Number(existing.quantity) + Number(quantity.value);
        if (lineNotes.value) existing.notes = lineNotes.value;
    } else {
        lines.value.push({
            supply_id: supply.id,
            name: supply.name,
            unit: supply.unit,
            quantity: Number(quantity.value),
            notes: lineNotes.value || '',
        });
    }
    closeAddModal();
};

const removeLine = (index) => lines.value.splice(index, 1);

const savePurchase = async () => {
    if (!lines.value.length) {
        Swal2.fire('Atención', 'Agregue al menos un insumo a la compra', 'warning');
        return;
    }
    saving.value = true;
    try {
        const res = await axios.post(route('res_supplies_purchase_store'), {
            notes: globalNotes.value,
            items: lines.value.map((l) => ({
                supply_id: l.supply_id,
                quantity: l.quantity,
                notes: l.notes || undefined,
            })),
        });
        if (res.data.success) {
            Swal2.fire('Enhorabuena', res.data.message, 'success');
            router.visit(route('res_supplies_list'));
        } else {
            Swal2.fire('Error', res.data.message, 'error');
        }
    } catch (e) {
        Swal2.fire('Error', e.response?.data?.message || 'No se pudo registrar la compra', 'error');
    } finally {
        saving.value = false;
    }
};
</script>

<template>
    <FormSection @submitted="savePurchase">
        <template #title>
            Registrar compra
        </template>

        <template #description>
            Ingrese los insumos que compró en el mercado. Puede agregar varias líneas antes de guardar.
        </template>

        <template #aside>
            <button type="button" class="btn btn-primary" @click="openAddModal">
                Agregar insumo
            </button>
        </template>

        <template #form>
            <div class="col-span-6">
                <InputLabel for="global_notes" value="Nota general (opcional)" class="mb-1" />
                <TextInput id="global_notes" v-model="globalNotes" placeholder="Ej. Mercado Central, 28/05" />
            </div>
            <div class="col-span-6">
                <div class="rounded-sm border border-stroke overflow-hidden">
                    <table class="w-full table-auto text-sm">
                        <thead class="border-b border-stroke bg-gray-50 dark:bg-meta-4">
                            <tr>
                                <th class="py-2 px-4 text-left font-medium text-black dark:text-white">Insumo</th>
                                <th class="py-2 px-4 text-right font-medium text-black dark:text-white">Cantidad</th>
                                <th class="py-2 px-4 text-center font-medium text-black dark:text-white w-24">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(line, idx) in lines" :key="idx" class="border-b border-stroke">
                                <td class="py-2 px-4">
                                    {{ line.name }}
                                    <span class="text-gray-400">({{ line.unit }})</span>
                                </td>
                                <td class="py-2 px-4 text-right">{{ line.quantity }}</td>
                                <td class="py-2 px-4 text-center">
                                    <button type="button" class="text-red-600 text-xs font-medium" @click="removeLine(idx)">
                                        Quitar
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!lines.length">
                                <td colspan="3" class="py-8 text-center text-gray-500">
                                    Sin líneas. Use «Agregar insumo» para comenzar.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>

        <template #actions>
            <Keypad>
                <template #botones>
                    <PrimaryButton type="button" :disabled="saving || !lines.length" @click="savePurchase">
                        {{ saving ? 'Guardando...' : 'Guardar compra' }}
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

    <ModalLarge :show="showAddModal" :on-close="closeAddModal" icon="/img/cubiertos.png">
        <template #title>
            Agregar insumo a la compra
        </template>
        <template #message>
            Seleccione el insumo y la cantidad comprada
        </template>
        <template #content>
            <div class="space-y-4">
                <div>
                    <InputLabel value="Buscar" class="mb-1" />
                    <TextInput v-model="search" placeholder="Buscar insumo..." />
                </div>
                <div>
                    <InputLabel value="Insumo *" class="mb-1" />
                    <Select
                        v-model:value="selectedSupplyId"
                        show-search
                        class="w-full"
                        placeholder="Seleccionar insumo"
                        :options="supplyOptions"
                        :filter-option="(input, option) => option.label.toLowerCase().includes(input.toLowerCase())"
                    />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Cantidad *" class="mb-1" />
                        <TextInput v-model="quantity" type="number" min="0.0001" step="0.0001" />
                        <p v-if="selectedSupply" class="text-xs text-gray-500 mt-1">Unidad: {{ selectedSupply.unit }}</p>
                    </div>
                    <div>
                        <InputLabel value="Nota línea" class="mb-1" />
                        <TextInput v-model="lineNotes" placeholder="Ej. bolsa 5 kg" />
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="btn btn-outline-danger" @click="closeAddModal">Cancelar</button>
                    <button type="button" class="btn btn-primary" @click="addLine">Agregar</button>
                </div>
            </div>
        </template>
    </ModalLarge>
</template>
