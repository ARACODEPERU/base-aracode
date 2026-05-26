<script setup>
import { computed, onMounted } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import iconPlus from '@/Components/vristo/icon/icon-plus.vue';
import iconTrash from '@/Components/vristo/icon/icon-trash.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    modelValue: {
        type: Object,
        default: null,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);

const defaultCanal = () => ({
    name: null,
    length: null,
    supported_on: null,
    initial_file: null,
    working_file: null,
    master_file: null,
});

const normalizeData = (value = null) => {
    const canals = Array.isArray(value?.canals) && value.canals.length
        ? value.canals.map((canal) => ({ ...defaultCanal(), ...canal }))
        : [defaultCanal()];

    return {
        tooth: value?.tooth || null,
        diagnosis: value?.diagnosis || null,
        session_number: value?.session_number ? Number.parseInt(value.session_number, 10) : null,
        status: value?.status || 'proceso',
        is_final_session: Boolean(value?.is_final_session || value?.status === 'finalizado'),
        ldr: value?.ldr || null,
        lt: value?.lt || null,
        canals,
    };
};

const endodonticData = computed(() => normalizeData(props.modelValue));

const fileColors = {
    6: '#f472b6',
    8: '#9ca3af',
    10: '#a855f7',
    15: '#ffffff',
    20: '#facc15',
    25: '#ef4444',
    30: '#3b82f6',
    35: '#22c55e',
    40: '#111827',
    45: '#ffffff',
    50: '#facc15',
    55: '#ef4444',
    60: '#3b82f6',
    70: '#22c55e',
    80: '#111827',
    90: '#ffffff',
    100: '#facc15',
    110: '#ef4444',
    120: '#3b82f6',
    130: '#22c55e',
    140: '#111827',
};

const fileInputStyle = (value) => {
    const fileNumber = Number.parseInt(value, 10);
    const color = fileColors[fileNumber];

    if (!color || String(value ?? '').trim() !== String(fileNumber)) {
        return {};
    }

    return {
        background: color === '#ffffff'
            ? 'linear-gradient(#ffffff, #ffffff) padding-box, repeating-linear-gradient(90deg, #111827 0 1px, #ffffff 1px 4px) border-box'
            : undefined,
        borderColor: color === '#ffffff' ? 'transparent' : color,
        borderWidth: '1px',
        boxShadow: color === '#ffffff' ? '0 0 0 1px #111827' : `0 0 0 2px ${color}`,
    };
};

const canalTitle = (canal, index) => `Conducto ${String(canal.name || '').trim() || index + 1}`;

const updateData = (patch) => {
    emit('update:modelValue', {
        ...endodonticData.value,
        ...patch,
    });
};

const updateCanal = (index, field, value) => {
    const canals = endodonticData.value.canals.map((canal, canalIndex) => (
        canalIndex === index ? { ...canal, [field]: value } : canal
    ));

    updateData({ canals });
};

const addCanal = () => {
    updateData({
        canals: [
            ...endodonticData.value.canals,
            defaultCanal(),
        ],
    });
};

const removeCanal = (index) => {
    Swal.fire({
        title: 'Confirmar eliminacion',
        html: `
            <div class="flex flex-col items-center gap-3">
                <div class="flex h-20 w-20 items-center justify-center rounded-full bg-danger/10 text-danger">
                    <svg width="52" height="52" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M20.5 6H3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        <path d="M18.833 8.5L18.373 15.399C18.197 18.054 18.108 19.382 17.243 20.191C16.378 21 15.048 21 12.387 21H11.613C8.953 21 7.622 21 6.757 20.191C5.892 19.382 5.804 18.054 5.627 15.399L5.167 8.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        <path opacity="0.5" d="M6.5 6C7.432 5.978 8.159 5.455 8.439 4.68L8.571 4.286C8.654 4.037 8.696 3.913 8.751 3.807C8.97 3.386 9.376 3.094 9.845 3.019C9.962 3 10.093 3 10.355 3H13.645C13.907 3 14.038 3 14.155 3.019C14.624 3.094 15.03 3.386 15.249 3.807C15.304 3.913 15.346 4.037 15.429 4.286L15.561 4.68C15.841 5.455 16.568 5.978 17.5 6" stroke="currentColor" stroke-width="1.8" />
                    </svg>
                </div>
                <p class="m-0 text-sm text-white-dark">Se eliminara este conducto.</p>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Eliminar',
        cancelButtonText: 'Cancelar',
        customClass: {
            popup: 'sweet-alerts',
            confirmButton: 'btn btn-danger',
            cancelButton: 'btn btn-dark ltr:mr-3 rtl:ml-3',
        },
        buttonsStyling: false,
        reverseButtons: true,
        padding: '2em',
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }

        const canals = endodonticData.value.canals.filter((canal, canalIndex) => canalIndex !== index);

        updateData({
            canals: canals.length ? canals : [defaultCanal()],
        });
    });
};

onMounted(() => {
    if (!props.modelValue || !Array.isArray(props.modelValue.canals) || props.modelValue.canals.length === 0) {
        emit('update:modelValue', normalizeData(props.modelValue));
    }
});
</script>

<template>
    <div class="md:col-span-12 rounded border border-primary/20 bg-primary/5 p-3">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h4 class="font-semibold text-primary">Datos endodonticos</h4>
                <p class="text-xs text-white-dark">Registro clínico del tratamiento endodóntico.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
            <div class="md:col-span-2">
                <InputLabel value="Pieza dental" />
                <TextInput
                    :model-value="endodonticData.tooth"
                    :disabled="disabled"
                    @update:model-value="updateData({ tooth: $event })"
                />
            </div>
            <div class="md:col-span-4">
                <InputLabel value="Diagnóstico" />
                <TextInput
                    :model-value="endodonticData.diagnosis"
                    :disabled="disabled"
                    @update:model-value="updateData({ diagnosis: $event })"
                />
            </div>
            <div class="md:col-span-2">
                <InputLabel value="Sesion N" />
                <input
                    type="number"
                    min="1"
                    step="1"
                    inputmode="numeric"
                    class="form-input"
                    :value="endodonticData.session_number"
                    :disabled="disabled"
                    @input="updateData({ session_number: $event.target.value ? Number.parseInt($event.target.value, 10) : null })"
                />
            </div>
            <div class="md:col-span-2">
                <InputLabel value="Estado" />
                <div
                    class="flex h-[38px] items-center rounded border px-3 text-sm font-semibold"
                    :class="endodonticData.status === 'finalizado'
                        ? 'border-success/30 bg-success/10 text-success'
                        : 'border-warning/30 bg-warning/10 text-warning'"
                >
                    {{ endodonticData.status === 'finalizado' ? 'Finalizado' : 'En proceso' }}
                </div>
            </div>
            <div class="md:col-span-2">
                <InputLabel value="LDR" />
                <TextInput
                    title="Longitud Real del diente"
                    :model-value="endodonticData.ldr"
                    :disabled="disabled"
                    @update:model-value="updateData({ ldr: $event })"
                />
            </div>
            <div class="md:col-span-2">
                <InputLabel value="LT" />
                <TextInput
                    title="Longitud de trabajo"
                    :model-value="endodonticData.lt"
                    :disabled="disabled"
                    @update:model-value="updateData({ lt: $event })"
                />
            </div>
            <label
                class="md:col-span-4 flex cursor-pointer items-center gap-3 rounded border-2 p-3 text-base font-bold shadow-sm transition"
                :class="endodonticData.is_final_session
                    ? 'border-success bg-success/15 text-success'
                    : 'border-warning/60 bg-warning/10 text-warning hover:bg-warning/20'"
            >
                <input
                    type="checkbox"
                    class="form-checkbox h-6 w-6 shrink-0"
                    :checked="endodonticData.is_final_session"
                    :disabled="disabled"
                    @change="updateData({
                        is_final_session: $event.target.checked,
                        status: $event.target.checked ? 'finalizado' : 'proceso',
                    })"
                />
                <span>
                    Última sesión
                    <span class="block text-xs font-semibold opacity-80">Marca aqui si el tratamiento termina hoy</span>
                </span>
            </label>
        </div>

        <div class="mt-4 space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h5 class="text-sm font-semibold dark:text-white">Conductos</h5>
                <button v-if="!disabled" type="button" class="btn btn-outline-primary btn-sm" @click="addCanal">
                    <icon-plus class="mr-1 h-4 w-4" />
                    Agregar conducto
                </button>
            </div>

            <div
                v-for="(canal, index) in endodonticData.canals"
                :key="index"
                class="grid grid-cols-1 gap-3 rounded border border-slate-200 p-3 dark:border-slate-700 md:grid-cols-12"
                :class="index % 2 === 0 ? 'bg-white dark:bg-slate-900' : 'bg-slate-50 dark:bg-slate-800/60'"
            >
                <div class="md:col-span-12 text-sm font-semibold text-primary">
                    {{ canalTitle(canal, index) }}
                </div>
                <div class="md:col-span-4">
                    <InputLabel value="Nombre" />
                    <TextInput
                        :model-value="canal.name"
                        :disabled="disabled"
                        @update:model-value="updateCanal(index, 'name', $event)"
                    />
                </div>
                <div class="md:col-span-4">
                    <InputLabel value="Longitud" />
                    <TextInput
                        :model-value="canal.length"
                        :disabled="disabled"
                        @update:model-value="updateCanal(index, 'length', $event)"
                    />
                </div>
                <div class="md:col-span-4">
                    <InputLabel value="Apoyado en" />
                    <TextInput
                        :model-value="canal.supported_on"
                        :disabled="disabled"
                        @update:model-value="updateCanal(index, 'supported_on', $event)"
                    />
                </div>
                <div class="md:col-span-4">
                    <InputLabel value="Lima inicial" />
                    <TextInput
                        :model-value="canal.initial_file"
                        :style="fileInputStyle(canal.initial_file)"
                        :disabled="disabled"
                        @update:model-value="updateCanal(index, 'initial_file', $event)"
                    />
                </div>
                <div class="md:col-span-4">
                    <InputLabel value="Lima de trabajo" />
                    <TextInput
                        :model-value="canal.working_file"
                        :style="fileInputStyle(canal.working_file)"
                        :disabled="disabled"
                        @update:model-value="updateCanal(index, 'working_file', $event)"
                    />
                </div>
                <div class="md:col-span-4">
                    <InputLabel value="Lima maestra" />
                    <TextInput
                        :model-value="canal.master_file"
                        :style="fileInputStyle(canal.master_file)"
                        :disabled="disabled"
                        @update:model-value="updateCanal(index, 'master_file', $event)"
                    />
                </div>
                <div v-if="!disabled" class="flex items-end md:col-span-12">
                    <button type="button" class="btn btn-outline-danger w-full" @click="removeCanal(index)">
                        <icon-trash class="mx-auto h-4 w-4" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
