<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Keypad from '@/Components/Keypad.vue';
import Swal2 from 'sweetalert2';
import { ref } from 'vue';
import { Switch, InputNumber } from 'ant-design-vue';

const props = defineProps({
    errors: {
        type: Object,
        default: () => ({}),
    },
});

const form = useForm({
    name: null,
    url_base: null,
    description: null,
    execution_type: 'manual',
    timeout: 30,
    retry_attempts: 3,
    is_active: true,
    config: {}
});

const executionTypes = [
    { value: 'manual', label: 'Manual' },
    { value: 'scheduled', label: 'Programada' },
    { value: 'webhook', label: 'Webhook' }
];


const storeIntegration = () => {
    form.post(route('integrationhub_store'), {
        preserveScroll: true,
        onSuccess: () => {
            Swal2.fire({
                title: 'Éxito',
                text: 'La integración ha sido creada correctamente.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false,
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        },
    });
};
</script>

<template>
    <FormSection @submitted="storeIntegration" title="Datos Generales" description="Configura los datos básicos de la integración">
        <template #form>
            <div class="col-span-6">
                <InputLabel for="name" value="Nombre de la Integración *" />
                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="mt-1 block w-full"
                    placeholder="Ej: CRM Salesforce, WhatsApp API, etc."
                />
                <InputError :message="form.errors.name" class="mt-2" />
            </div>

            <div class="col-span-6">
                <InputLabel for="url_base" value="URL Base de la API *" />
                <TextInput
                    id="url_base"
                    v-model="form.url_base"
                    type="url"
                    class="mt-1 block w-full"
                    placeholder="https://api.ejemplo.com"
                />
                <InputError :message="form.errors.url_base" class="mt-2" />
            </div>

            <div class="col-span-6">
                <InputLabel for="description" value="Descripción" />
                <textarea
                    id="description"
                    v-model="form.description"
                    rows="3"
                    class="form-textarea"
                    placeholder="Descripción opcional de la integración..."
                />
                <InputError :message="form.errors.description" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-3">
                <InputLabel for="execution_type" value="Tipo de Ejecución" />
                <select
                    id="execution_type"
                    v-model="form.execution_type"
                    class="form-select"
                >
                    <option v-for="type in executionTypes" :key="type.value" :value="type.value">
                        {{ type.label }}
                    </option>
                </select>
                <InputError :message="form.errors.execution_type" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-3">
                <InputLabel for="is_active" value="Estado" />
                <div class="mt-2 flex items-center">
                    <input
                        v-model="form.is_active"
                        id="is_active"
                        type="checkbox"
                        class="form-checkbox"
                    />
                    <label for="is_active" class="ml-2 text-sm text-gray-900 dark:text-gray-300">
                        {{ form.is_active ? 'Activo' : 'Inactivo' }}
                    </label>
                </div>
            </div>

            <div class="col-span-6 sm:col-span-3">
                <InputLabel for="timeout" value="Tiempo de espera (segundos)" />
                <input
                    id="timeout"
                    v-model="form.timeout"
                    type="number"
                    min="5"
                    max="300"
                    class="form-input"
                    placeholder="30"
                />
                <InputError :message="form.errors.timeout" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-3">
                <InputLabel for="retry_attempts" value="Intentos de reintento" />
                <input
                    id="retry_attempts"
                    v-model="form.retry_attempts"
                    type="number"
                    min="0"
                    max="10"
                    class="form-input"
                    placeholder="3"
                />
                <InputError :message="form.errors.retry_attempts" class="mt-2" />
            </div>
        </template>

        <template #actions>
            <div class="flex justify-end gap-3">
                <Link
                    :href="route('integrationhub_listado')"
                    class="px-4 py-2 border border-gray-300 dark:border-zinc-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-700 transition"
                >
                    Cancelar
                </Link>
                <PrimaryButton
                    type="submit"
                    :disabled="saving"
                    :class="{ 'opacity-50': saving }"
                >
                    <span v-if="form.processing">Guardando...</span>
                    <span v-else>Guardar Integración</span>
                </PrimaryButton>
            </div>
        </template>
    </FormSection>
</template>
