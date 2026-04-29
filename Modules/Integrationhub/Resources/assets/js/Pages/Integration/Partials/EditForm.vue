<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Keypad from '@/Components/Keypad.vue';
import Swal2 from 'sweetalert2';
import { ref, computed } from 'vue';
import { Switch } from 'ant-design-vue';
import AuthenticationForm from './AuthenticationForm.vue';
import EndpointsForm from './EndpointsForm.vue';
import FieldMapsForm from './FieldMapsForm.vue';
import DataSourcesForm from './DataSourcesForm.vue';
import SchedulesForm from './SchedulesForm.vue';
import ExecuteForm from './ExecuteForm.vue';
import LogsViewer from './LogsViewer.vue';

const props = defineProps({
    integration: {
        type: Object,
        default: () => ({}),
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
});

const activeTab = ref('general');

const executionTypes = [
    { value: 'manual', label: 'Manual' },
    { value: 'scheduled', label: 'Programada' },
    { value: 'webhook', label: 'Webhook' }
];

const form = useForm({
    id: props.integration.id,
    name: props.integration.name,
    url_base: props.integration.url_base,
    description: props.integration.description,
    execution_type: props.integration.execution_type || 'manual',
    is_active: props.integration.is_active,
    timeout: props.integration.config?.timeout || 30,
    retry_attempts: props.integration.config?.retry_attempts || 3,
    config: props.integration.config || {}
});


const updateIntegration = () => {
    form.put(route('integrationhub_update', props.integration.id), {
        preserveScroll: true,
        onSuccess: () => {
            Swal2.fire({
                title: 'Éxito',
                text: 'La integración ha sido actualizada correctamente.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false,
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        },
    });
};

const tabs = [
    { key: 'general', label: 'Datos Generales', icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.8 2.866 2.249a3.666 3.666 0 00-.12 1.026c-.41.764 1.043 1.447 1.889 1.39a1.624 1.624 0 001.232-1.067c.38-.622-.574-1.862-1.478-1.54a1.724 1.724 0 00-2.573-1.066c-1.756.426-1.756 2.924 0 3.35a1.724 1.724 0 001.066 2.573c.94 1.543-.8 3.31-2.249 2.866a3.666 3.666 0 00.12 1.026c-.764.41-1.447-1.043-1.39-1.889a1.624 1.624 0 00-1.067-1.232c.622-.38 1.862.574 1.54 1.478a1.724 1.724 0 001.066 2.573' },
    { key: 'auth', label: 'Autenticación', icon: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z' },
    { key: 'endpoints', label: 'Endpoints', icon: 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1' },
    { key: 'fields', label: 'Mapeo Campos', icon: 'M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4M3 10h18M3 14h18' },
    { key: 'data', label: 'Origen Datos', icon: 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4' },
    { key: 'schedule', label: 'Programación', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
    { key: 'execute', label: 'Ejecutar', icon: 'M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.133a1 1 0 000-1.664z M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
    { key: 'logs', label: 'Historial', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01m9.01 9h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z' }
];
</script>

<template>
    <div class="panel mb-6">
        <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-3 justify-items-center">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                @click="activeTab = tab.key"
                :class="[
                    activeTab === tab.key
                        ? 'bg-primary text-white shadow-lg'
                        : 'bg-white dark:bg-zinc-800 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-700 border-gray-200 dark:border-zinc-600'
                ]"
                class="flex flex-col items-center justify-center w-24 h-20 rounded-xl border-2 transition-all duration-200 hover:shadow-md"
                type="button"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.icon" />
                </svg>
                <span class="text-xs font-medium text-center leading-tight px-1">{{ tab.label }}</span>
            </button>
        </div>
    </div>

    <!-- Datos Generales -->
    <div v-show="activeTab === 'general'">
        <FormSection @submitted="updateIntegration" title="Datos Generales" description="Configura los datos básicos de la integración">
            <template #form>
                <div class="col-span-6">
                    <InputLabel for="name" value="Nombre de la Integración *" />
                    <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" placeholder="Ej: CRM Salesforce, WhatsApp API, etc." />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>
                <div class="col-span-6">
                    <InputLabel for="url_base" value="URL Base de la API *" />
                    <TextInput id="url_base" v-model="form.url_base" type="url" class="mt-1 block w-full" placeholder="https://api.ejemplo.com" />
                    <InputError :message="form.errors.url_base" class="mt-2" />
                </div>
                <div class="col-span-6">
                    <InputLabel for="description" value="Descripción" />
                    <textarea id="description" v-model="form.description" rows="3" class="form-textarea" placeholder="Descripción opcional..." />
                    <InputError :message="form.errors.description" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-3">
                    <InputLabel for="execution_type" value="Tipo de Ejecución" />
                    <select id="execution_type" v-model="form.execution_type" class="form-select">
                        <option v-for="type in executionTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                    </select>
                    <InputError :message="form.errors.execution_type" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-3">
                    <InputLabel for="is_active" value="Estado" />
                    <div class="mt-2 flex items-center">
                        <input v-model="form.is_active" id="is_active" type="checkbox" class="form-checkbox" />
                        <label for="is_active" class="ml-2 text-sm text-gray-900 dark:text-gray-300">{{ form.is_active ? 'Activo' : 'Inactivo' }}</label>
                    </div>
                </div>
                <div class="col-span-6 sm:col-span-3">
                    <InputLabel for="timeout" value="Tiempo de espera (segundos)" />
                    <input id="timeout" v-model="form.timeout" type="number" min="5" max="300" class="form-input" placeholder="30" />
                    <InputError :message="form.errors.timeout" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-3">
                    <InputLabel for="retry_attempts" value="Intentos de reintento" />
                    <input id="retry_attempts" v-model="form.retry_attempts" type="number" min="0" max="10" class="form-input" placeholder="3" />
                    <InputError :message="form.errors.retry_attempts" class="mt-2" />
                </div>
            </template>
            <template #actions>
                <div class="flex justify-end gap-3">
                    <Link :href="route('integrationhub_listado')" class="px-4 py-2 border border-gray-300 dark:border-zinc-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-700 transition">Cancelar</Link>
                    <PrimaryButton type="submit" :disabled="form.processing" :class="{ 'opacity-50': form.processing }">
                        <span v-if="form.processing">Guardando...</span>
                        <span v-else>Guardar Cambios</span>
                    </PrimaryButton>
                </div>
            </template>
        </FormSection>
    </div>

    <!-- Autenticación -->
    <div v-show="activeTab === 'auth'" class="panel min-h-[400px]">
        <AuthenticationForm 
            :integration-id="integration.id" 
            :auths="integration.auths || []" 
        />
    </div>

    <!-- Endpoints -->
    <div v-show="activeTab === 'endpoints'" class="panel min-h-[400px]">
        <EndpointsForm 
            :integration-id="integration.id" 
            :endpoints="integration.endpoints || []" 
        />
    </div>

    <!-- Mapeo Campos -->
    <div v-show="activeTab === 'fields'" class="panel min-h-[400px]">
        <FieldMapsForm 
            :integration-id="integration.id" 
            :endpoints="integration.endpoints || []"
            :field-maps="(integration.endpoints || []).flatMap(ep => ep.field_maps || [])"
        />
    </div>

    <!-- Origen Datos -->
    <div v-show="activeTab === 'data'" class="panel min-h-[400px]">
        <DataSourcesForm 
            :integration-id="integration.id" 
            :queries="integration.queries || []" 
        />
    </div>

    <!-- Programación -->
    <div v-show="activeTab === 'schedule'" class="panel min-h-[400px]">
        <SchedulesForm 
            :integration-id="integration.id" 
            :schedules="integration.schedules || []" 
        />
    </div>

    <!-- Ejecutar -->
    <div v-show="activeTab === 'execute'" class="panel min-h-[400px]">
        <ExecuteForm 
            :integration-id="integration.id" 
            :integration="integration"
            :endpoints="integration.endpoints || []" 
        />
    </div>

    <!-- Historial -->
    <div v-show="activeTab === 'logs'" class="panel min-h-[400px]">
        <LogsViewer 
            :integration-id="integration.id" 
            :logs="integration.logs || []"
            :endpoints="integration.endpoints || []"
        />
    </div>

</template>
