<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
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

const conditions = [
    { code: 'caries', label: 'Caries', class: 'btn-outline-danger' },
    { code: 'obturado', label: 'Obturado', class: 'btn-outline-primary' },
    { code: 'filtracion', label: 'Filtracion', class: 'btn-outline-warning' },
    { code: 'extraido', label: 'Extraido', class: 'btn-outline-primary' },
    { code: 'extraccion', label: 'Extracción', class: 'btn-outline-danger' },
    { code: 'endodontic', label: 'Endodoncia', class: 'btn-outline-dark' },
    { code: 'endodontic_required', label: 'Necesita endodoncia', class: 'btn-outline-danger' },
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
    get: () => props.modelValue || { teeth: {}, notes: null, condition: 'caries' },
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

const updateTooth = (number, value) => {
    if (props.disabled) {
        return;
    }

    state.value = {
        ...state.value,
        teeth: {
            ...(state.value.teeth || {}),
            [number]: value,
        },
    };
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

onMounted(() => {
    nextTick(() => {
        resizeOdontogram();

        if (window.ResizeObserver && odontogramContainer.value) {
            resizeObserver = new ResizeObserver(resizeOdontogram);
            resizeObserver.observe(odontogramContainer.value);
        }

        window.addEventListener('resize', resizeOdontogram);
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
            <div>
                <h3 class="text-lg font-semibold dark:text-white">Odontograma</h3>
                <p class="text-sm text-white-dark">Selecciona una condicion y marca las superficies de cada pieza.</p>
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

        <div class="flex flex-wrap gap-3 mb-5 text-xs text-slate-700 dark:text-slate-200">
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
                Filtracion / mal estado
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
        </div>

        <div ref="odontogramContainer" class="overflow-hidden">
            <div :style="odontogramViewportStyle" class="relative w-full">
            <div ref="odontogramContent" :style="odontogramContentStyle" class="absolute left-0 top-0 w-[1320px] space-y-6">
                <div class="flex justify-center gap-8">
                    <div class="flex gap-2 pr-8 border-r border-slate-300 dark:border-slate-600">
                        <OdontogramTooth
                            v-for="number in visualUpperLeft"
                            :key="number"
                            :tooth="toothData(number)"
                            :condition="selectedCondition"
                            :disabled="disabled"
                            :model-value="state.teeth?.[number] || {}"
                            @update:model-value="updateTooth(number, $event)"
                        />
                    </div>
                    <div class="flex gap-2">
                        <OdontogramTooth
                            v-for="number in visualUpperRight"
                            :key="number"
                            :tooth="toothData(number)"
                            :condition="selectedCondition"
                            :disabled="disabled"
                            :model-value="state.teeth?.[number] || {}"
                            @update:model-value="updateTooth(number, $event)"
                        />
                    </div>
                </div>

                <div class="flex justify-center gap-8">
                    <div class="flex gap-2 pr-8 border-r border-slate-300 dark:border-slate-600">
                        <OdontogramTooth
                            v-for="number in visualChildUpperLeft"
                            :key="number"
                            :tooth="toothData(number)"
                            :condition="selectedCondition"
                            :disabled="disabled"
                            :model-value="state.teeth?.[number] || {}"
                            @update:model-value="updateTooth(number, $event)"
                        />
                    </div>
                    <div class="flex gap-2">
                        <OdontogramTooth
                            v-for="number in visualChildUpperRight"
                            :key="number"
                            :tooth="toothData(number)"
                            :condition="selectedCondition"
                            :disabled="disabled"
                            :model-value="state.teeth?.[number] || {}"
                            @update:model-value="updateTooth(number, $event)"
                        />
                    </div>
                </div>

                <div class="h-px bg-slate-300 dark:bg-slate-600"></div>

                <div class="flex justify-center gap-8">
                    <div class="flex gap-2 pr-8 border-r border-slate-300 dark:border-slate-600">
                        <OdontogramTooth
                            v-for="number in visualChildLowerLeft"
                            :key="number"
                            :tooth="toothData(number)"
                            :condition="selectedCondition"
                            :disabled="disabled"
                            :model-value="state.teeth?.[number] || {}"
                            @update:model-value="updateTooth(number, $event)"
                        />
                    </div>
                    <div class="flex gap-2">
                        <OdontogramTooth
                            v-for="number in visualChildLowerRight"
                            :key="number"
                            :tooth="toothData(number)"
                            :condition="selectedCondition"
                            :disabled="disabled"
                            :model-value="state.teeth?.[number] || {}"
                            @update:model-value="updateTooth(number, $event)"
                        />
                    </div>
                </div>

                <div class="flex justify-center gap-8">
                    <div class="flex gap-2 pr-8 border-r border-slate-300 dark:border-slate-600">
                        <OdontogramTooth
                            v-for="number in visualLowerLeft"
                            :key="number"
                            :tooth="toothData(number)"
                            :condition="selectedCondition"
                            :disabled="disabled"
                            :model-value="state.teeth?.[number] || {}"
                            @update:model-value="updateTooth(number, $event)"
                        />
                    </div>
                    <div class="flex gap-2">
                        <OdontogramTooth
                            v-for="number in visualLowerRight"
                            :key="number"
                            :tooth="toothData(number)"
                            :condition="selectedCondition"
                            :disabled="disabled"
                            :model-value="state.teeth?.[number] || {}"
                            @update:model-value="updateTooth(number, $event)"
                        />
                    </div>
                </div>
            </div>
            </div>
        </div>

        <div class="mt-5">
            <InputLabel value="Notas del odontograma" />
            <textarea :value="state.notes" class="form-textarea" rows="3" :disabled="disabled" @input="updateNotes"></textarea>
        </div>
    </div>
</template>
