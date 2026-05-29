<script setup>
import { useForm } from '@inertiajs/vue3';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Swal2 from 'sweetalert2';
import iconLoader from '@/Components/vristo/icon/icon-loader.vue';

const props = defineProps({
    rentalId: { type: Number, required: true },
    disabled: { type: Boolean, default: false },
});

const form = useForm({
    description: '',
    amount: '',
    reason: 'damage',
    notes: '',
});

const submit = () => {
    form.post(route('even_alquiler_local_store_extra', props.rentalId), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            Swal2.fire({
                title: 'Enhorabuena',
                text: 'El cargo se registró correctamente',
                icon: 'success',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        },
    });
};
</script>

<template>
    <FormSection @submitted="submit">
        <template #title>Agregar cargo durante el evento</template>
        <template #description>Registre roturas, servicios adicionales u otros cargos.</template>

        <template #form>
            <div class="col-span-6 sm:col-span-3">
                <InputLabel value="Descripción *" />
                <TextInput v-model="form.description" type="text" :disabled="disabled" />
                <InputError :message="form.errors.description" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-3">
                <InputLabel value="Monto *" />
                <TextInput v-model="form.amount" type="number" min="0.01" step="0.01" :disabled="disabled" />
                <InputError :message="form.errors.amount" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-3">
                <InputLabel value="Motivo *" />
                <select v-model="form.reason" class="form-select w-full" :disabled="disabled">
                    <option value="damage">Rotura / daño</option>
                    <option value="additional_service">Servicio adicional</option>
                    <option value="other">Otro</option>
                </select>
                <InputError :message="form.errors.reason" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-3">
                <InputLabel value="Notas" />
                <textarea v-model="form.notes" class="form-textarea w-full" rows="2" :disabled="disabled" />
            </div>
        </template>

        <template #actions>
            <PrimaryButton :disabled="form.processing || disabled" :class="{ 'opacity-25': form.processing }">
                <icon-loader v-show="form.processing" class="w-4 h-4 animate-spin mr-1" />
                Agregar cargo
            </PrimaryButton>
        </template>
    </FormSection>
</template>
