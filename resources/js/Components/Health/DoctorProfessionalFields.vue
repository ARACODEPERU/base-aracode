<script setup>
import { watch } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { healthServiceTypes, normalizeHealthServiceType, professionOptions } from '@/Components/Health/healthOptions.js';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
});

watch(() => props.form.attention_service_type, (value) => {
    const normalizedValue = normalizeHealthServiceType(value);

    if (normalizedValue !== value) {
        props.form.attention_service_type = normalizedValue;
        return;
    }

    const option = healthServiceTypes.find((item) => item.value === normalizedValue);

    if (option && normalizedValue !== 'general' && normalizedValue !== 'odontologia_general') {
        props.form.specialty = option.label;
    }
}, { immediate: true });
</script>

<template>
    <div class="col-span-6 sm:col-span-3">
        <InputLabel for="profession" value="Profesión *" />
        <select v-model="form.profession" id="profession" class="form-select mt-1">
            <option v-for="profession in professionOptions" :key="profession" :value="profession">{{ profession }}</option>
        </select>
        <InputError :message="form.errors.profession" class="mt-2" />
    </div>
    <div class="col-span-6 sm:col-span-3">
        <InputLabel for="attention_service_type" value="Tipo de atención por defecto *" />
        <select v-model="form.attention_service_type" id="attention_service_type" class="form-select mt-1">
            <optgroup label="Medicas">
                <option
                    v-for="type in healthServiceTypes.filter((item) => item.group === 'Medica')"
                    :key="type.value"
                    :value="type.value"
                >
                    {{ type.label }}
                </option>
            </optgroup>
            <optgroup label="Odontologicas">
                <option
                    v-for="type in healthServiceTypes.filter((item) => item.group === 'Odontologica')"
                    :key="type.value"
                    :value="type.value"
                >
                    {{ type.label }}
                </option>
            </optgroup>
        </select>
        <InputError :message="form.errors.attention_service_type" class="mt-2" />
    </div>
    <div class="col-span-6 sm:col-span-3">
        <InputLabel for="colegiatura" value="Colegiatura" />
        <TextInput
            id="colegiatura"
            v-model="form.colegiatura"
            type="text"
            class="block w-full mt-1"
            placeholder="Ejemplo: CMP 12345"
        />
        <InputError :message="form.errors.colegiatura" class="mt-2" />
    </div>
    <div class="col-span-6 sm:col-span-6">
        <InputLabel for="specialty" value="Especialidad" />
        <TextInput
            id="specialty"
            v-model="form.specialty"
            type="text"
            class="block w-full mt-1"
            placeholder="Ejemplo: Endodoncia, Pediatria, Cardiologia"
        />
        <InputError :message="form.errors.specialty" class="mt-2" />
    </div>
</template>
