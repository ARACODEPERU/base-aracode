<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import OdontogramTooth from './OdontogramTooth.vue';
import InputLabel from '@/Components/InputLabel.vue';

const props = defineProps({
    modelValue: {
        type: Object,
        default: () => ({ teeth: {}, notes: null }),
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);

const odontogramContainer = ref(null);
const odontogramContent = ref(null);
const odontogramScale = ref(1);
const odontogramHeight = ref(null);
let resizeObserver = null;

// Filtro de visualización (all: Todos, adult: Solo Adultos, child: Solo Niños)
const activeView = ref('all');

// Estado para la edición de notas de cada pieza dental
const editingToothNumber = ref(null);
const editingToothNotes = ref('');

const conditions = [
    { code: 'caries', label: 'Caries', class: 'btn-outline-danger' },
    { code: 'obturado', label: 'Obturado', class: 'btn-outline-primary' },
    { code: 'filtracion', label: 'Filtración/Mal estado', class: 'btn-outline-warning' },
    { code: 'extraido', label: 'Extraído', class: 'btn-outline-primary' },
    { code: 'extraccion', label: 'Requiere extracción', class: 'btn-outline-danger' },
    { code: 'endodontic', label: 'Endodoncia', class: 'btn-outline-dark' },
    { code: 'endodontic_required', label: 'Necesita endodoncia', class: 'btn-outline-danger' },
    { code: 'corona', label: 'Corona/Prótesis', class: 'btn-outline-primary' }, // Changed class to primary (blue)
    { code: 'ausente', label: 'Ausente', class: 'btn-outline-secondary' },
    { code: 'fractura', label: 'Fractura', class: 'btn-outline-danger' },
    { code: 'puente', label: 'Puente Dental', class: 'btn-outline-primary' }, // New tool
    { code: 'sano', label: 'Limpiar / Sano', class: 'btn-outline-success' },
];

const visualUpperLeft = [18, 17, 16, 15, 14, 13, 12, 11];
const visualUpperRight = [21, 22, 23, 24, 25, 26, 27, 28];
const visualLowerLeft = [48, 47, 46, 45, 44, 43, 42, 41];
const visualLowerRight = [31, 32, 33, 34, 35, 36, 37, 38];
const visualChildUpperLeft = [55, 54, 53, 52, 51];
const visualChildUpperRight = [61, 62, 63, 64, 65];
const visualChildLowerLeft = [85, 84, 83, 82, 81];
const visualChildLowerRight = [71, 72, 73, 74, 75];

const state = computed({
    get: () => props.modelValue || { teeth: {}, notes: null, condition: 'caries', bridges: [] },
    set: (value) => emit('update:modelValue', value),
});

const selectedCondition = computed({
    get: () => state.value.condition || 'caries',
    set: (condition) => {
        if (props.disabled) {
            return;
        }

        state.value = { ...state.value, condition };
    },
});

const toothData = (number) => {
    const n = Number(number);
    const quadrant = Math.floor(n / 10);
    const position = n % 10;

    return {
        number,
        arch: [1, 2, 5, 6].includes(quadrant) ? 'upper' : 'lower',
        group: [4, 5, 6, 7, 8].includes(position) ? 'posterior' : 'anterior',
    };
};

const bridgeStartTooth = ref(null);
const bridgeLines = ref([]);

const updateBridgeLines = () => {
    if (!odontogramContent.value) {
        return;
    }

    const list = [];
    const bridges = state.value.bridges || [];
    const scale = odontogramScale.value || 1;
    const contentRect = odontogramContent.value.getBoundingClientRect();

    bridges.forEach((bridge) => {
        const toothEl1 = odontogramContent.value.querySelector(`[data-tooth-number="${bridge.from}"]`);
        const toothEl2 = odontogramContent.value.querySelector(`[data-tooth-number="${bridge.to}"]`);

        if (toothEl1 && toothEl2) {
            const rect1 = toothEl1.getBoundingClientRect();
            const rect2 = toothEl2.getBoundingClientRect();

            const x1 = (rect1.left - contentRect.left + rect1.width / 2) / scale;
            const y1 = (rect1.top - contentRect.top + 36 * scale) / scale;

            const x2 = (rect2.left - contentRect.left + rect2.width / 2) / scale;
            const y2 = (rect2.top - contentRect.top + 36 * scale) / scale;

            list.push({
                from: bridge.from,
                to: bridge.to,
                x1,
                y1,
                x2,
                y2,
            });
        }
    });

    bridgeLines.value = list;
};

const handleBridgeClick = (number) => {
    if (bridgeStartTooth.value === null) {
        // Primer diente seleccionado como pilar
        bridgeStartTooth.value = number;
        
        // Lo marcamos visualmente como corona (azul)
        const toothVal = state.value.teeth?.[number] || {};
        state.value = {
            ...state.value,
            teeth: {
                ...(state.value.teeth || {}),
                [number]: {
                    ...toothVal,
                    status: 'corona',
                    surfaces: {},
                },
            },
        };
    } else {
        const start = bridgeStartTooth.value;
        if (start === number) {
            // Cancelar selección si hace clic en el mismo
            bridgeStartTooth.value = null;
            const toothVal = state.value.teeth?.[number] || {};
            state.value = {
                ...state.value,
                teeth: {
                    ...(state.value.teeth || {}),
                    [number]: {
                        ...toothVal,
                        status: null,
                    },
                },
            };
            return;
        }

        // Agregar el puente y marcar el segundo pilar como corona
        const toothVal = state.value.teeth?.[number] || {};
        state.value = {
            ...state.value,
            bridges: [
                ...(state.value.bridges || []),
                { from: start, to: number },
            ],
            teeth: {
                ...(state.value.teeth || {}),
                [start]: {
                    ...(state.value.teeth?.[start] || {}),
                    status: 'corona',
                },
                [number]: {
                    ...toothVal,
                    status: 'corona',
                    surfaces: {},
                },
            },
        };
        
        bridgeStartTooth.value = null;
        
        nextTick(() => {
            updateBridgeLines();
        });
    }
};

const updateTooth = (number, value) => {
    if (props.disabled) {
        return;
    }

    if (selectedCondition.value === 'puente') {
        handleBridgeClick(number);
        return;
    }

    let nextBridges = [...(state.value.bridges || [])];
    if (selectedCondition.value === 'sano') {
        // Si limpiamos la pieza, removemos cualquier puente asociado
        nextBridges = nextBridges.filter((b) => b.from !== number && b.to !== number);
    }

    state.value = {
        ...state.value,
        bridges: nextBridges,
        teeth: {
            ...(state.value.teeth || {}),
            [number]: value,
        },
    };
    
    nextTick(() => {
        updateBridgeLines();
    });
};

const openNotesModal = (number) => {
    if (props.disabled) {
        return;
    }
    editingToothNumber.value = number;
    editingToothNotes.value = state.value.teeth?.[number]?.notes || '';
};

const saveToothNotes = () => {
    const number = editingToothNumber.value;
    const toothVal = state.value.teeth?.[number] || {};
    
    state.value = {
        ...state.value,
        teeth: {
            ...(state.value.teeth || {}),
            [number]: {
                ...toothVal,
                notes: editingToothNotes.value ? editingToothNotes.value.trim() : null,
            },
        },
    };
    editingToothNumber.value = null;
    
    nextTick(() => {
        resizeOdontogram();
    });
};

const updateNotes = (event) => {
    if (props.disabled) {
        return;
    }

    state.value = {
        ...state.value,
        notes: event.target.value,
    };
};

const resizeOdontogram = () => {
    const containerWidth = odontogramContainer.value?.clientWidth || 0;
    const contentWidth = odontogramContent.value?.scrollWidth || 1320;
    const contentHeight = odontogramContent.value?.scrollHeight || 0;

    if (!containerWidth || !contentWidth) {
        return;
    }

    odontogramScale.value = containerWidth / contentWidth;
    odontogramHeight.value = contentHeight ? `${contentHeight * odontogramScale.value}px` : null;
};

const odontogramContentStyle = computed(() => ({
    transform: `scale(${odontogramScale.value})`,
    transformOrigin: 'top left',
}));

const odontogramViewportStyle = computed(() => ({
    height: odontogramHeight.value,
}));

watch(
    () => state.value.bridges,
    () => {
        nextTick(() => updateBridgeLines());
    },
    { deep: true }
);

watch(activeView, () => {
    nextTick(() => updateBridgeLines());
});

watch(odontogramScale, () => {
    nextTick(() => updateBridgeLines());
});

onMounted(() => {
    nextTick(() => {
        resizeOdontogram();

        if (window.ResizeObserver && odontogramContainer.value) {
            resizeObserver = new ResizeObserver(() => {
                resizeOdontogram();
                updateBridgeLines();
            });
            resizeObserver.observe(odontogramContainer.value);
        }

        window.addEventListener('resize', () => {
            resizeOdontogram();
            updateBridgeLines();
        });
        
        // Timeout para asegurar que el DOM y clases de Tailwind estén renderizados del todo
        setTimeout(() => {
            updateBridgeLines();
        }, 300);
    });
});

onBeforeUnmount(() => {
    resizeObserver?.disconnect();
    window.removeEventListener('resize', resizeOdontogram);
});
</script>

<template>
    <div class="panel">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
            <div class="flex flex-wrap items-center gap-4">
                <div>
                    <h3 class="text-lg font-semibold dark:text-white">Odontograma</h3>
                    <p class="text-sm text-white-dark">Selecciona una condición y marca las superficies de cada pieza dental.</p>
                </div>
                <!-- Selector de vista de dientes (Adulto / Niño) -->
                <div class="flex rounded-md shadow-sm border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-0.5">
                    <button
                        type="button"
                        class="px-3 py-1 text-xs font-semibold rounded-md transition-colors"
                        :class="activeView === 'all' ? 'bg-primary text-white' : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'"
                        @click="activeView = 'all'"
                    >
                        Todos
                    </button>
                    <button
                        type="button"
                        class="px-3 py-1 text-xs font-semibold rounded-md transition-colors"
                        :class="activeView === 'adult' ? 'bg-primary text-white' : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'"
                        @click="activeView = 'adult'"
                    >
                        Permanentes (Adulto)
                    </button>
                    <button
                        type="button"
                        class="px-3 py-1 text-xs font-semibold rounded-md transition-colors"
                        :class="activeView === 'child' ? 'bg-primary text-white' : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'"
                        @click="activeView = 'child'"
                    >
                        Temporales (Niño)
                    </button>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="condition in conditions"
                    :key="condition.code"
                    type="button"
                    class="btn btn-sm"
                    :class="[condition.class, selectedCondition === condition.code ? 'bg-primary text-white border-primary' : '']"
                    :disabled="disabled"
                    @click="selectedCondition = condition.code"
                >
                    {{ condition.label }}
                </button>
            </div>
        </div>

        <div class="flex flex-wrap gap-3 mb-5 text-xs text-slate-700 dark:text-slate-200 bg-slate-50 dark:bg-slate-800/40 p-3 rounded-lg border border-slate-100 dark:border-slate-700/50">
            <span class="inline-flex items-center gap-2">
                <span class="w-4 h-4 rounded-sm bg-[#e7515a] border border-slate-700"></span>
                Caries
            </span>
            <span class="inline-flex items-center gap-2">
                <span class="w-4 h-4 rounded-sm bg-[#4361ee] border border-slate-700"></span>
                Obturado
            </span>
            <span class="inline-flex items-center gap-2">
                <span class="w-4 h-4 rounded-sm border border-slate-700 bg-[linear-gradient(45deg,#e7515a_25%,#4361ee_25%,#4361ee_50%,#e7515a_50%,#e7515a_75%,#4361ee_75%,#4361ee_100%)] bg-[length:8px_8px]"></span>
                Filtración / mal estado
            </span>
            <span class="inline-flex items-center gap-2">
                <span class="relative w-4 h-4 rounded-sm border border-slate-700 bg-white dark:bg-slate-900">
                    <span class="absolute left-1/2 top-1/2 h-[2px] w-5 -translate-x-1/2 -translate-y-1/2 rotate-45 bg-[#4361ee]"></span>
                    <span class="absolute left-1/2 top-1/2 h-[2px] w-5 -translate-x-1/2 -translate-y-1/2 -rotate-45 bg-[#4361ee]"></span>
                </span>
                Extraido
            </span>
            <span class="inline-flex items-center gap-2">
                <span class="relative w-4 h-4 rounded-sm border border-slate-700 bg-white dark:bg-slate-900">
                    <span class="absolute left-1/2 top-1/2 h-[2px] w-5 -translate-x-1/2 -translate-y-1/2 rotate-45 bg-[#e7515a]"></span>
                    <span class="absolute left-1/2 top-1/2 h-[2px] w-5 -translate-x-1/2 -translate-y-1/2 -rotate-45 bg-[#e7515a]"></span>
                </span>
                Extracción
            </span>
            <span class="inline-flex items-center gap-2">
                <span class="relative h-6 w-4">
                    <span class="absolute left-1/2 top-0 h-6 w-3 -translate-x-1/2 rounded-b-full border-2 border-[#805dca] bg-[#805dca]/20"></span>
                    <span class="absolute left-1/2 top-1 h-5 w-[2px] -translate-x-1/2 rounded bg-[#805dca]"></span>
                </span>
                Endodoncia
            </span>
            <span class="inline-flex items-center gap-2">
                <span class="relative h-6 w-4">
                    <span class="absolute left-1/2 top-0 h-6 w-3 -translate-x-1/2 rounded-b-full border-2 border-[#e7515a] bg-[#e7515a]/20"></span>
                    <span class="absolute left-1/2 top-1 h-5 w-[2px] -translate-x-1/2 rounded bg-[#e7515a]"></span>
                </span>
                Necesita endodoncia
            </span>
            <!-- Nuevas Leyendas -->
            <span class="inline-flex items-center gap-2">
                <span class="w-4 h-4 rounded-full border-2 border-[#4361ee] bg-white dark:bg-slate-900"></span>
                Corona / Prótesis
            </span>
            <span class="inline-flex items-center gap-2">
                <span class="relative w-4 h-4 rounded-sm border border-slate-300 bg-white/20 dark:bg-slate-900/20">
                    <span class="absolute left-1/2 top-1/2 h-[2.5px] w-5 -translate-x-1/2 -translate-y-1/2 rotate-45 bg-[#64748b]"></span>
                    <span class="absolute left-1/2 top-1/2 h-[2.5px] w-5 -translate-x-1/2 -translate-y-1/2 -rotate-45 bg-[#64748b]"></span>
                </span>
                Ausente
            </span>
            <span class="inline-flex items-center gap-2">
                <span class="relative w-4 h-4 rounded-sm border border-slate-700 bg-white dark:bg-slate-900 overflow-hidden">
                    <span class="absolute left-1 w-2 h-4 border-r-2 border-red-500 rotate-12"></span>
                    <span class="absolute right-1 w-2 h-4 border-l-2 border-red-500 -rotate-12"></span>
                </span>
                Fractura (Múltiples clicks)
            </span>
            <span class="inline-flex items-center gap-2">
                <span class="relative w-6 h-4 flex items-center justify-between">
                    <span class="w-3.5 h-3.5 rounded-full border border-[#4361ee] bg-white dark:bg-slate-900"></span>
                    <span class="absolute left-1.5 right-1.5 h-[2px] bg-[#4361ee]"></span>
                    <span class="w-3.5 h-3.5 rounded-full border border-[#4361ee] bg-white dark:bg-slate-900"></span>
                </span>
                Puente Dental
            </span>
            <span class="inline-flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-[#10b981] animate-ping"></span>
                Nota específica (Icono al pasar el mouse)
            </span>
        </div>

        <div ref="odontogramContainer" class="overflow-hidden">
            <div :style="odontogramViewportStyle" class="relative w-full">
            <div ref="odontogramContent" :style="odontogramContentStyle" class="absolute left-0 top-0 w-[1320px] space-y-6">
                <!-- Fila 1: Adulto Superior -->
                <div v-if="activeView !== 'child'" class="flex justify-center gap-8">
                    <div class="flex gap-2 pr-8 border-r border-slate-300 dark:border-slate-600">
                        <OdontogramTooth
                            v-for="number in visualUpperLeft"
                            :key="number"
                            :data-tooth-number="number"
                            :tooth="toothData(number)"
                            :condition="selectedCondition"
                            :disabled="disabled"
                            :model-value="state.teeth?.[number] || {}"
                            @update:model-value="updateTooth(number, $event)"
                            @open-notes="openNotesModal(number)"
                        />
                    </div>
                    <div class="flex gap-2">
                        <OdontogramTooth
                            v-for="number in visualUpperRight"
                            :key="number"
                            :data-tooth-number="number"
                            :tooth="toothData(number)"
                            :condition="selectedCondition"
                            :disabled="disabled"
                            :model-value="state.teeth?.[number] || {}"
                            @update:model-value="updateTooth(number, $event)"
                            @open-notes="openNotesModal(number)"
                        />
                    </div>
                </div>

                <!-- Fila 2: Niño Superior -->
                <div v-if="activeView !== 'adult'" class="flex justify-center gap-8">
                    <div class="flex gap-2 pr-8 border-r border-slate-300 dark:border-slate-600">
                        <OdontogramTooth
                            v-for="number in visualChildUpperLeft"
                            :key="number"
                            :data-tooth-number="number"
                            :tooth="toothData(number)"
                            :condition="selectedCondition"
                            :disabled="disabled"
                            :model-value="state.teeth?.[number] || {}"
                            @update:model-value="updateTooth(number, $event)"
                            @open-notes="openNotesModal(number)"
                        />
                    </div>
                    <div class="flex gap-2">
                        <OdontogramTooth
                            v-for="number in visualChildUpperRight"
                            :key="number"
                            :data-tooth-number="number"
                            :tooth="toothData(number)"
                            :condition="selectedCondition"
                            :disabled="disabled"
                            :model-value="state.teeth?.[number] || {}"
                            @update:model-value="updateTooth(number, $event)"
                            @open-notes="openNotesModal(number)"
                        />
                    </div>
                </div>

                <div class="h-px bg-slate-300 dark:bg-slate-600"></div>

                <!-- Fila 3: Niño Inferior -->
                <div v-if="activeView !== 'adult'" class="flex justify-center gap-8">
                    <div class="flex gap-2 pr-8 border-r border-slate-300 dark:border-slate-600">
                        <OdontogramTooth
                            v-for="number in visualChildLowerLeft"
                            :key="number"
                            :data-tooth-number="number"
                            :tooth="toothData(number)"
                            :condition="selectedCondition"
                            :disabled="disabled"
                            :model-value="state.teeth?.[number] || {}"
                            @update:model-value="updateTooth(number, $event)"
                            @open-notes="openNotesModal(number)"
                        />
                    </div>
                    <div class="flex gap-2">
                        <OdontogramTooth
                            v-for="number in visualChildLowerRight"
                            :key="number"
                            :data-tooth-number="number"
                            :tooth="toothData(number)"
                            :condition="selectedCondition"
                            :disabled="disabled"
                            :model-value="state.teeth?.[number] || {}"
                            @update:model-value="updateTooth(number, $event)"
                            @open-notes="openNotesModal(number)"
                        />
                    </div>
                </div>

                <!-- Fila 4: Adulto Inferior -->
                <div v-if="activeView !== 'child'" class="flex justify-center gap-8">
                    <div class="flex gap-2 pr-8 border-r border-slate-300 dark:border-slate-600">
                        <OdontogramTooth
                            v-for="number in visualLowerLeft"
                            :key="number"
                            :data-tooth-number="number"
                            :tooth="toothData(number)"
                            :condition="selectedCondition"
                            :disabled="disabled"
                            :model-value="state.teeth?.[number] || {}"
                            @update:model-value="updateTooth(number, $event)"
                            @open-notes="openNotesModal(number)"
                        />
                    </div>
                    <div class="flex gap-2">
                        <OdontogramTooth
                            v-for="number in visualLowerRight"
                            :key="number"
                            :data-tooth-number="number"
                            :tooth="toothData(number)"
                            :condition="selectedCondition"
                            :disabled="disabled"
                            :model-value="state.teeth?.[number] || {}"
                            @update:model-value="updateTooth(number, $event)"
                            @open-notes="openNotesModal(number)"
                        />
                    </div>
                </div>

                <!-- Overlay SVG para trazar dinámicamente las líneas de los puentes -->
                <svg class="absolute inset-0 w-full h-full pointer-events-none z-[10]">
                    <g v-for="(line, idx) in bridgeLines" :key="idx">
                        <!-- Línea horizontal superior del puente -->
                        <line
                            :x1="line.x1"
                            :y1="line.y1 - 10"
                            :x2="line.x2"
                            :y2="line.y2 - 10"
                            stroke="#4361ee"
                            stroke-width="3"
                            stroke-linecap="round"
                        />
                        <!-- Línea horizontal inferior del puente -->
                        <line
                            :x1="line.x1"
                            :y1="line.y1"
                            :x2="line.x2"
                            :y2="line.y2"
                            stroke="#4361ee"
                            stroke-width="3"
                            stroke-linecap="round"
                        />
                    </g>
                </svg>
            </div>
            </div>
        </div>

        <div class="mt-5">
            <InputLabel value="Notas del odontograma" />
            <textarea :value="state.notes" class="form-textarea" rows="3" :disabled="disabled" @input="updateNotes"></textarea>
        </div>

        <!-- Modal flotante premium para notas específicas por pieza dental -->
        <div v-if="editingToothNumber !== null" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="bg-white dark:bg-[#1b2e4b] rounded-lg shadow-2xl max-w-md w-full border border-slate-200 dark:border-slate-700 overflow-hidden transform transition-all scale-100 flex flex-col">
                <!-- Header -->
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-800">
                    <h4 class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-[#10b981] animate-ping"></span>
                        Notas de la Pieza Dental #{{ editingToothNumber }}
                    </h4>
                    <button type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors" @click="editingToothNumber = null">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <!-- Body -->
                <div class="p-5 flex-1">
                    <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Observaciones Clínicas de la Pieza</label>
                    <textarea
                        v-model="editingToothNotes"
                        class="form-textarea w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 focus:ring-primary focus:border-primary"
                        rows="4"
                        placeholder="Escribe aquí observaciones específicas para esta pieza (ej. movilidad, fracturas de corona, tratamiento previo, etc.)..."
                    ></textarea>
                    <p class="text-[10px] text-slate-400 mt-2">Pasa el mouse sobre un diente y haz clic en el icono de notas para abrir este panel.</p>
                </div>
                <!-- Footer -->
                <div class="px-5 py-3 border-t border-slate-100 dark:border-slate-700 flex justify-end gap-2 bg-slate-50 dark:bg-slate-800/60">
                    <button type="button" class="btn btn-outline-danger btn-sm px-4" @click="editingToothNumber = null">
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-primary btn-sm px-4" @click="saveToothNotes">
                        Guardar Nota
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
