<script setup>
import ModalLarge from '@/Components/ModalLarge.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import DangerButton from '@/Components/DangerButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { computed, onUnmounted, ref, watch } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
    show: { type: Boolean, default: false },
    establishments: { type: Array, default: () => [] },
});

const emit = defineEmits(['close']);

const tenantDatabase = ref('');
const loadingPreview = ref(false);
const previewError = ref(null);
const summary = ref(null);
const f3Establishments = ref([]);
const mapForm = ref({});
const chunkSize = ref(1000);

const importWithStock = ref(true);
const importWithoutStock = ref(true);
const minStock = ref('');
const showAdvanced = ref(false);

const searchQuery = ref('');
const searchResults = ref([]);
const searching = ref(false);
const excludedItems = ref([]);

const processing = ref(false);
const currentJobId = ref(null);
const jobStatus = ref(null);
const progressPercent = ref(0);
let pollingInterval = null;

const canProcess = computed(() => {
    return summary.value
        && Object.values(mapForm.value).some((v) => v)
        && (importWithStock.value || importWithoutStock.value)
        && !processing.value;
});

const excludedIds = computed(() => excludedItems.value.map((item) => item.id));

const resetState = () => {
    previewError.value = null;
    summary.value = null;
    f3Establishments.value = [];
    mapForm.value = {};
    searchQuery.value = '';
    searchResults.value = [];
    excludedItems.value = [];
    currentJobId.value = null;
    jobStatus.value = null;
    progressPercent.value = 0;
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
};

watch(() => props.show, (visible) => {
    if (!visible) {
        resetState();
    }
});

const close = () => emit('close');

const buildMapForm = (establishments, savedMap) => {
    const initial = { ...savedMap };
    establishments.forEach((est) => {
        const key = est.id;
        initial[key] = initial[key] ?? initial[String(key)] ?? null;
    });
    return initial;
};

const consultPreview = async () => {
    if (!tenantDatabase.value.trim()) {
        Swal.fire({ icon: 'warning', title: 'Indique la base de datos del tenant' });
        return;
    }

    loadingPreview.value = true;
    previewError.value = null;

    try {
        const { data } = await axios.post(route('facturador3_import_preview'), {
            tenant_database: tenantDatabase.value.trim(),
            import_with_stock: importWithStock.value,
            import_without_stock: importWithoutStock.value,
            min_stock: minStock.value !== '' ? Number(minStock.value) : null,
        });

        summary.value = data.summary;
        f3Establishments.value = data.establishments ?? [];
        mapForm.value = buildMapForm(f3Establishments.value, data.establishment_map ?? {});
        chunkSize.value = data.chunk_size ?? 1000;

        if (Array.isArray(data.exclusions) && data.exclusions.length) {
            excludedItems.value = data.exclusions.map((id) => ({ id, description: `Item #${id}` }));
        }
    } catch (e) {
        previewError.value = e.response?.data?.message ?? 'No se pudo consultar la base de datos.';
        summary.value = null;
    } finally {
        loadingPreview.value = false;
    }
};

const refreshSummary = async () => {
    if (!summary.value) return;
    try {
        const { data } = await axios.post(route('facturador3_import_preview'), {
            tenant_database: tenantDatabase.value.trim(),
            import_with_stock: importWithStock.value,
            import_without_stock: importWithoutStock.value,
            min_stock: minStock.value !== '' ? Number(minStock.value) : null,
        });
        summary.value = data.summary;
    } catch {
        // ignore silent refresh errors
    }
};

watch([importWithStock, importWithoutStock, minStock], () => {
    if (summary.value) {
        refreshSummary();
    }
});

const searchProducts = async () => {
    if (searchQuery.value.trim().length < 2) return;

    searching.value = true;
    try {
        const { data } = await axios.post(route('facturador3_import_search'), {
            tenant_database: tenantDatabase.value.trim(),
            search: searchQuery.value.trim(),
        });
        searchResults.value = data.results ?? [];
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'Error', text: e.response?.data?.message ?? 'Búsqueda fallida.' });
    } finally {
        searching.value = false;
    }
};

const addExclusion = (item) => {
    if (excludedIds.value.includes(item.id)) return;
    excludedItems.value.push(item);
    refreshSummary();
};

const removeExclusion = async (item) => {
    excludedItems.value = excludedItems.value.filter((row) => row.id !== item.id);

    try {
        await axios.delete(route('facturador3_import_remove_exclusion'), {
            data: {
                tenant_database: tenantDatabase.value.trim(),
                f3_item_id: item.id,
            },
        });
    } catch {
        // local list already updated
    }

    refreshSummary();
};

const startPolling = () => {
    if (pollingInterval) clearInterval(pollingInterval);
    pollingInterval = setInterval(async () => {
        if (!currentJobId.value) return;
        try {
            const { data } = await axios.get(route('facturador3_import_status', currentJobId.value));
            jobStatus.value = data;
            progressPercent.value = data.progress ?? 0;
            if (data.status === 'completed' || data.status === 'failed') {
                clearInterval(pollingInterval);
                pollingInterval = null;
                processing.value = false;
            }
        } catch {
            clearInterval(pollingInterval);
            pollingInterval = null;
            processing.value = false;
        }
    }, 3000);
};

const processImport = async () => {
    if (!canProcess.value) return;

    processing.value = true;

    try {
        if (excludedIds.value.length) {
            await axios.post(route('facturador3_import_save_exclusions'), {
                tenant_database: tenantDatabase.value.trim(),
                f3_item_ids: excludedIds.value,
            });
        }

        const { data } = await axios.post(route('facturador3_import_process'), {
            tenant_database: tenantDatabase.value.trim(),
            establishment_map: mapForm.value,
            import_with_stock: importWithStock.value,
            import_without_stock: importWithoutStock.value,
            min_stock: minStock.value !== '' ? Number(minStock.value) : null,
            excluded_item_ids: excludedIds.value,
            chunk_size: chunkSize.value,
        });

        currentJobId.value = data.job_id;
        jobStatus.value = { status: 'processing', phase: 'products', progress: 0 };
        progressPercent.value = 0;
        startPolling();

        Swal.fire({
            toast: true,
            position: 'top',
            icon: 'info',
            title: 'Importación en cola (productos + stock)',
            timer: 2500,
            showConfirmButton: false,
        });
    } catch (e) {
        processing.value = false;
        Swal.fire({ icon: 'error', title: 'Error', text: e.response?.data?.message ?? 'No se pudo iniciar.' });
    }
};

onUnmounted(() => {
    if (pollingInterval) clearInterval(pollingInterval);
});
</script>

<template>
    <ModalLarge :show="show" :onClose="close" :icon="'/img/base-de-datos.png'">
        <template #title>
            Importar desde Facturador3
        </template>
        <template #message>
            Importa catálogo y stock actual (sin historial de kardex). Requiere cola
        </template>
        <template #content>
            <div class="space-y-4 text-sm">
                <p class="text-gray-500">
                    Esta herramienta permite importar productos y stock desde una base de datos de Facturador3, para lo cual se requiere el nombre de la base de datos del tenant a importar. El proceso se realiza en segundo plano y puede tomar varios minutos dependiendo del tamaño de los datos a importar. Se recomienda revisar el resumen previo para ajustar las opciones de importación antes de procesar. Para más detalles sobre el proceso, revisar la documentación y los logs de                    <code class="text-xs">imports</code>.
                </p>

                <div>
                    <InputLabel value="Base de datos tenant (Facturador3)" />
                    <div class="flex gap-2 mt-1">
                        <TextInput
                            v-model="tenantDatabase"
                            class="w-full"
                            placeholder="tenant_tubasededatos"
                            @keyup.enter="consultPreview"
                        />
                        <SecondaryButton type="button" :disabled="loadingPreview" @click="consultPreview">
                            {{ loadingPreview ? 'Consultando…' : 'Consultar' }}
                        </SecondaryButton>
                    </div>
                </div>

                <div v-if="previewError" class="p-3 rounded bg-danger/10 text-danger">
                    {{ previewError }}
                </div>

                <div v-if="summary" class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div class="p-3 rounded bg-gray-50 dark:bg-gray-800">
                        <div class="text-gray-500">Total productos</div>
                        <div class="text-xl font-bold">{{ summary.total }}</div>
                    </div>
                    <div class="p-3 rounded bg-gray-50 dark:bg-gray-800">
                        <div class="text-gray-500">Con stock (≥1)</div>
                        <div class="text-xl font-bold">{{ summary.with_stock }}</div>
                    </div>
                    <div class="p-3 rounded bg-gray-50 dark:bg-gray-800">
                        <div class="text-gray-500">Sin stock (=0)</div>
                        <div class="text-xl font-bold">{{ summary.without_stock }}</div>
                    </div>
                    <div class="p-3 rounded bg-gray-50 dark:bg-gray-800">
                        <div class="text-gray-500">A importar</div>
                        <div class="text-xl font-bold text-primary">{{ summary.importable }}</div>
                    </div>
                </div>

                <div v-if="summary" class="space-y-2">
                    <label class="flex items-center gap-2">
                        <input v-model="importWithStock" type="checkbox" class="form-checkbox" />
                        Importar productos con stock (suma todos los almacenes ≥ 1)
                    </label>
                    <label class="flex items-center gap-2">
                        <input v-model="importWithoutStock" type="checkbox" class="form-checkbox" />
                        Importar productos sin stock (= 0)
                    </label>
                </div>

                <div v-if="summary && f3Establishments.length" class="border-t pt-4">
                    <h4 class="font-semibold mb-2">Mapeo de establecimientos</h4>
                    <div class="space-y-2 max-h-48 overflow-y-auto">
                        <div
                            v-for="est in f3Establishments"
                            :key="est.id"
                            class="grid grid-cols-1 md:grid-cols-2 gap-2 items-center"
                        >
                            <div>
                                <span class="font-medium">[{{ est.id }}] {{ est.description }}</span>
                            </div>
                            <select v-model.number="mapForm[est.id]" class="form-select">
                                <option :value="null">— Sin mapear —</option>
                                <option v-for="local in establishments" :key="local.id" :value="local.id">
                                    [{{ local.id }}] {{ local.description }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <div v-if="summary">
                    <button type="button" class="text-primary text-xs underline" @click="showAdvanced = !showAdvanced">
                        {{ showAdvanced ? 'Ocultar opciones avanzadas' : 'Opciones avanzadas' }}
                    </button>

                    <div v-if="showAdvanced" class="mt-3 space-y-3 border rounded p-3">
                        <div>
                            <InputLabel value="Stock mínimo (solo productos con stock)" />
                            <TextInput v-model="minStock" type="number" min="0" step="0.01" class="mt-1 w-40" />
                        </div>

                        <div>
                            <InputLabel value="Excluir productos (buscar en F3)" />
                            <div class="flex gap-2 mt-1">
                                <TextInput
                                    v-model="searchQuery"
                                    class="w-full"
                                    placeholder="Descripción, código interno o código"
                                    @keyup.enter="searchProducts"
                                />
                                <SecondaryButton type="button" :disabled="searching" @click="searchProducts">
                                    Buscar
                                </SecondaryButton>
                            </div>

                            <ul v-if="searchResults.length" class="mt-2 max-h-32 overflow-y-auto border rounded divide-y">
                                <li
                                    v-for="item in searchResults"
                                    :key="item.id"
                                    class="flex justify-between items-center px-2 py-1"
                                >
                                    <span class="truncate pr-2">
                                        [{{ item.id }}] {{ item.description }}
                                        <span class="text-gray-400">(stock: {{ item.stock_sum }})</span>
                                    </span>
                                    <button
                                        type="button"
                                        class="text-xs text-danger shrink-0"
                                        :disabled="excludedIds.includes(item.id)"
                                        @click="addExclusion(item)"
                                    >
                                        Excluir
                                    </button>
                                </li>
                            </ul>

                            <div v-if="excludedItems.length" class="mt-2">
                                <div class="text-xs text-gray-500 mb-1">Excluidos ({{ excludedItems.length }})</div>
                                <div class="flex flex-wrap gap-1">
                                    <span
                                        v-for="item in excludedItems"
                                        :key="item.id"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-xs"
                                    >
                                        #{{ item.id }}
                                        <button type="button" class="text-danger" @click="removeExclusion(item)">×</button>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="jobStatus" class="border-t pt-4">
                    <div class="text-gray-500 mb-2">
                        Job #{{ currentJobId }} — Fase: {{ jobStatus.phase }} — {{ jobStatus.status }}
                        <span v-if="jobStatus.processed_count != null">
                            ({{ jobStatus.processed_count }} / {{ jobStatus.total_count }})
                        </span>
                    </div>
                    <div class="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                        <div class="h-full bg-primary transition-all duration-500" :style="{ width: progressPercent + '%' }" />
                    </div>
                    <p v-if="jobStatus.error_message" class="text-danger mt-2">{{ jobStatus.error_message }}</p>
                </div>
            </div>
        </template>
        <template #buttons>
            <PrimaryButton type="button" :disabled="!canProcess" @click="processImport">
                {{ processing ? 'Procesando…' : 'Procesar' }}
            </PrimaryButton>
        </template>
    </ModalLarge>
</template>
