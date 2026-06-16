<script setup>
import FormSection from '@/Components/FormSection.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Keypad from '@/Components/Keypad.vue';
import ModalLarge from '@/Components/ModalLarge.vue';
import ModalLargeX from '@/Components/ModalLargeX.vue';
import Swal2 from 'sweetalert2';
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Select } from 'ant-design-vue';

const props = defineProps({
    comanda: { type: Object, default: () => ({}) },
    supplies: { type: Array, default: () => [] },
    otherComandas: { type: Array, default: () => [] },
    initialItems: { type: Array, default: () => [] },
});

const items = ref([...props.initialItems]);

const search = ref('');
const selectedSupplyId = ref(null);
const quantity = ref(1);
const copyFromId = ref(null);
const saving = ref(false);
const showAddModal = ref(false);
const showCopyModal = ref(false);

const filteredSupplies = computed(() => {
    const used = new Set(items.value.map((i) => i.supply_id));
    const q = search.value.trim().toLowerCase();
    return props.supplies.filter((s) => {
        if (used.has(s.id)) return false;
        if (!q) return true;
        return s.name.toLowerCase().includes(q);
    });
});

const supplyOptions = computed(() =>
    filteredSupplies.value.map((s) => ({ value: s.id, label: `${s.name} (${s.unit})` }))
);

const summary = computed(() =>
    items.value.map((i) => `${i.quantity} ${i.unit} de ${i.name}`).join(', ')
);

const openAddModal = () => {
    selectedSupplyId.value = null;
    quantity.value = 1;
    search.value = '';
    showAddModal.value = true;
};

const closeAddModal = () => {
    showAddModal.value = false;
};

const openCopyModal = () => {
    copyFromId.value = null;
    showCopyModal.value = true;
};

const closeCopyModal = () => {
    showCopyModal.value = false;
};

const addItem = () => {
    const supply = props.supplies.find((s) => s.id === selectedSupplyId.value);
    if (!supply || !quantity.value || quantity.value <= 0) {
        Swal2.fire('Atención', 'Seleccione insumo y cantidad', 'warning');
        return;
    }
    items.value.push({
        supply_id: supply.id,
        quantity: Number(quantity.value),
        name: supply.name,
        unit: supply.unit,
    });
    closeAddModal();
};

const removeItem = (index) => items.value.splice(index, 1);

const copyRecipe = async () => {
    if (!copyFromId.value) return;
    try {
        const res = await axios.post(route('res_comandas_recipe_copy', props.comanda.id), {
            source_comanda_id: copyFromId.value,
        });
        if (res.data.success) {
            items.value = (res.data.recipe.items || []).map((i) => ({
                supply_id: i.supply_id,
                quantity: Number(i.quantity),
                name: i.supply?.name,
                unit: i.supply?.unit,
            }));
            Swal2.fire('Enhorabuena', res.data.message, 'success');
            closeCopyModal();
        }
    } catch (e) {
        Swal2.fire('Error', e.response?.data?.message || 'No se pudo copiar', 'error');
    }
};

const saveRecipe = async () => {
    saving.value = true;
    try {
        const res = await axios.put(route('res_comandas_recipe_update', props.comanda.id), {
            items: items.value.map((i) => ({ supply_id: i.supply_id, quantity: i.quantity })),
        });
        if (res.data.success) {
            Swal2.fire('Enhorabuena', res.data.message, 'success');
        }
    } catch (e) {
        Swal2.fire('Error', e.response?.data?.message || 'No se pudo guardar', 'error');
    } finally {
        saving.value = false;
    }
};
</script>

<template>
    <FormSection @submitted="saveRecipe">
        <template #title>
            Receta — {{ comanda.name }}
        </template>

        <template #description>
            Configure qué insumos usa <strong>1 porción</strong> de este plato.
            <span v-if="summary" class="block mt-2 text-gray-600 dark:text-gray-400">{{ summary }}</span>
            <span v-else class="block mt-2 text-gray-500">Sin ingredientes configurados.</span>
        </template>

        <template #aside>
            <div class="flex flex-col gap-2">
                <button type="button" class="btn btn-primary" @click="openAddModal">
                    Agregar ingrediente
                </button>
                <button
                    v-if="otherComandas.length"
                    type="button"
                    class="btn btn-outline-primary"
                    @click="openCopyModal"
                >
                    Copiar de otro plato
                </button>
            </div>
        </template>

        <template #form>
            <div class="col-span-6">
                <div class="rounded-sm border border-stroke overflow-hidden">
                    <table class="w-full table-auto text-sm">
                        <thead class="border-b border-stroke bg-gray-50 dark:bg-meta-4">
                            <tr>
                                <th class="py-2 px-4 text-left font-medium text-black dark:text-white">Insumo</th>
                                <th class="py-2 px-4 text-right font-medium text-black dark:text-white">Cantidad por porción</th>
                                <th class="py-2 px-4 text-center font-medium text-black dark:text-white w-24">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, idx) in items" :key="idx" class="border-b border-stroke">
                                <td class="py-2 px-4">{{ item.name }}</td>
                                <td class="py-2 px-4 text-right">{{ item.quantity }} {{ item.unit }}</td>
                                <td class="py-2 px-4 text-center">
                                    <button type="button" class="text-red-600 text-xs font-medium" @click="removeItem(idx)">
                                        Quitar
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!items.length">
                                <td colspan="3" class="py-8 text-center text-gray-500">
                                    Agregue ingredientes o copie la receta de otro plato.
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
                    <PrimaryButton type="button" :disabled="saving" @click="saveRecipe">
                        {{ saving ? 'Guardando...' : 'Guardar receta' }}
                    </PrimaryButton>
                    <Link
                        :href="route('res_comandas_edit', comanda.id)"
                        class="ml-2 inline-block px-6 py-2.5 bg-green-500 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-green-600 transition duration-150 ease-in-out"
                    >
                        Volver a comanda
                    </Link>
                </template>
            </Keypad>
        </template>
    </FormSection>

    <ModalLarge :show="showAddModal" :on-close="closeAddModal" icon="/img/cubiertos.png">
        <template #title>Agregar ingrediente</template>
        <template #message>Cantidad usada por 1 porción de {{ comanda.name }}</template>
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
                    />
                </div>
                <div>
                    <InputLabel value="Cantidad *" class="mb-1" />
                    <TextInput v-model="quantity" type="number" min="0.0001" step="0.0001" />
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="btn btn-outline-danger" @click="closeAddModal">Cancelar</button>
                    <button type="button" class="btn btn-primary" @click="addItem">Agregar</button>
                </div>
            </div>
        </template>
    </ModalLarge>

    <ModalLargeX :show="showCopyModal" :on-close="closeCopyModal" icon="/img/cubiertos.png">
        <template #title>Copiar receta de otro plato</template>
        <template #message>Se reemplazarán los ingredientes actuales</template>
        <template #content>
            <div class="space-y-4">
                <div>
                    <InputLabel value="Plato origen *" class="mb-1" />
                    <Select
                        v-model:value="copyFromId"
                        class="w-full"
                        placeholder="Seleccionar plato..."
                        :options="otherComandas.map((c) => ({ value: c.id, label: c.name }))"
                    />
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="btn btn-outline-danger" @click="closeCopyModal">Cancelar</button>
                    <button type="button" class="btn btn-primary" :disabled="!copyFromId" @click="copyRecipe">
                        Copiar receta
                    </button>
                </div>
            </div>
        </template>
    </ModalLargeX>
</template>
