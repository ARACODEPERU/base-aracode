<template>
    <div v-if="show" class="fixed inset-0 z-[999] flex items-center justify-center bg-black/60 px-4 py-6" @click.self="close">
        <div class="w-full max-w-3xl rounded-lg bg-white shadow-xl dark:bg-[#0e1726] animate__animated animate__zoomIn">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-700">
                <div>
                    <h3 class="text-lg font-semibold dark:text-white">Detalle de Actividad</h3>
                    <p class="text-sm text-white-dark">{{ activity?.activity_label }} · {{ activity?.subject_label }}</p>
                </div>
                <button type="button" class="btn btn-sm btn-outline-dark" @click="close">
                    <IconX class="h-4 w-4" />
                </button>
            </div>

            <div class="max-h-[70vh] overflow-y-auto p-5">
                <div v-if="loading" class="flex items-center justify-center py-10">
                    <svg class="h-8 w-8 animate-spin text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </div>

                <div v-else-if="activity" class="space-y-5">
                    <!-- Tarjeta de información general -->
                    <div class="rounded-lg border border-slate-200 dark:border-slate-700">
                        <div class="border-b border-slate-200 bg-slate-50 px-4 py-2 dark:border-slate-700 dark:bg-slate-800">
                            <span class="text-sm font-semibold dark:text-white">Información General</span>
                        </div>
                        <div class="grid grid-cols-2 gap-4 p-4">
                            <div>
                                <span class="text-xs text-white-dark">ID</span>
                                <p class="font-semibold dark:text-white">#{{ activity.id }}</p>
                            </div>
                            <div>
                                <span class="text-xs text-white-dark">Tipo de Actividad</span>
                                <p class="font-semibold dark:text-white">
                                    <span class="badge" :class="badgeClass">{{ activity.activity_label }}</span>
                                </p>
                            </div>
                            <div>
                                <span class="text-xs text-white-dark">Realizado por</span>
                                <p class="font-semibold dark:text-white">{{ activity.actor_display || 'Sistema' }}</p>
                            </div>
                            <div>
                                <span class="text-xs text-white-dark">Persona ID</span>
                                <p class="font-semibold dark:text-white">{{ activity.actor_person_id ? `#${activity.actor_person_id}` : '-' }}</p>
                            </div>
                            <div>
                                <span class="text-xs text-white-dark">Fecha y Hora</span>
                                <p class="font-semibold dark:text-white">{{ formatDateTime(activity.created_at) }}</p>
                            </div>
                            <div>
                                <span class="text-xs text-white-dark">Entidad Afectada</span>
                                <p class="font-semibold dark:text-white">{{ activity.subject_label }} #{{ activity.subject_id }}</p>
                            </div>
                            <div v-if="activity.patient_display">
                                <span class="text-xs text-white-dark">Paciente</span>
                                <p class="font-semibold dark:text-white">{{ activity.patient_display }}</p>
                            </div>
                            <div v-if="activity.ip">
                                <span class="text-xs text-white-dark">Dirección IP</span>
                                <p class="font-semibold dark:text-white">{{ activity.ip }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Metadatos / Cambios -->
                    <div v-if="activity.metadata" class="rounded-lg border border-slate-200 dark:border-slate-700">
                        <div class="border-b border-slate-200 bg-slate-50 px-4 py-2 dark:border-slate-700 dark:bg-slate-800">
                            <span class="text-sm font-semibold dark:text-white">Detalle de Cambios</span>
                        </div>
                        <div class="p-4">
                            <template v-if="activity.activity_type === 'created'">
                                <div class="rounded bg-success/10 p-3 text-sm text-success">
                                    Se creó el registro exitosamente.
                                </div>
                                <div v-if="hasData(activity.metadata)" class="mt-3">
                                    <h4 class="mb-2 text-sm font-semibold dark:text-white">Datos creados:</h4>
                                    <pre class="max-h-64 overflow-auto rounded bg-slate-100 p-3 text-xs dark:bg-slate-800">{{ formatJSON(activity.metadata) }}</pre>
                                </div>
                            </template>

                            <template v-else-if="activity.activity_type === 'updated'">
                                <div v-if="activity.metadata.changed_fields?.length" class="space-y-3">
                                    <div class="rounded bg-info/10 p-3 text-sm text-info">
                                        Se modificaron {{ activity.metadata.changed_fields.length }} campo(s).
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table class="table-striped w-full text-sm">
                                            <thead>
                                                <tr>
                                                    <th class="p-2 text-left">Campo</th>
                                                    <th class="p-2 text-left">Valor Anterior</th>
                                                    <th class="p-2 text-left">Valor Nuevo</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="field in activity.metadata.changed_fields" :key="field">
                                                    <td class="p-2 font-semibold">{{ field }}</td>
                                                    <td class="p-2">
                                                        <span class="rounded bg-danger/10 px-2 py-0.5 text-danger">{{ formatValue(activity.metadata.before?.[field]) }}</span>
                                                    </td>
                                                    <td class="p-2">
                                                        <span class="rounded bg-success/10 px-2 py-0.5 text-success">{{ formatValue(activity.metadata.after?.[field]) }}</span>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div v-else class="rounded bg-info/10 p-3 text-sm text-info">
                                    No se registraron cambios de campos específicos en esta modificación.
                                </div>
                            </template>

                            <template v-else-if="activity.activity_type === 'deleted'">
                                <div class="rounded bg-danger/10 p-3 text-sm text-danger">
                                    Se eliminó el registro. Los datos eliminados se muestran a continuación.
                                </div>
                                <div v-if="activity.metadata?.deleted_data" class="mt-3">
                                    <h4 class="mb-2 text-sm font-semibold dark:text-white">Datos eliminados:</h4>
                                    <pre class="max-h-64 overflow-auto rounded bg-slate-100 p-3 text-xs dark:bg-slate-800">{{ formatJSON(activity.metadata.deleted_data) }}</pre>
                                </div>
                            </template>

                            <template v-else-if="activity.activity_type === 'signed'">
                                <div class="rounded bg-success/10 p-3 text-sm text-success">
                                    <svg class="mb-2 inline-block h-6 w-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M3 17c3.333-3.333 5-6 5-8 0-3-1-3-2-3s-2.032 1.085-2 3c.034 2.048 1.658 2.877 2.5 4C9 17 11 19 15 19c4 0 6-4 6-4"/>
                                    </svg>
                                    <p>Atención firmada digitalmente.</p>
                                </div>
                            </template>

                            <template v-else-if="activity.activity_type === 'cancelled'">
                                <div class="rounded bg-warning/10 p-3 text-sm text-warning">
                                    Se anuló el registro.
                                </div>
                            </template>

                            <template v-else>
                                <div class="rounded bg-slate-100 p-3 text-sm dark:bg-slate-800">
                                    <pre class="max-h-64 overflow-auto text-xs">{{ formatJSON(activity.metadata) }}</pre>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- User Agent -->
                    <div v-if="activity.user_agent" class="rounded-lg border border-slate-200 dark:border-slate-700">
                        <div class="border-b border-slate-200 bg-slate-50 px-4 py-2 dark:border-slate-700 dark:bg-slate-800">
                            <span class="text-sm font-semibold dark:text-white">Información del Navegador</span>
                        </div>
                        <div class="p-4">
                            <p class="break-all text-xs text-white-dark">{{ activity.user_agent }}</p>
                        </div>
                    </div>
                </div>

                <div v-else class="flex items-center justify-center py-10 text-white-dark">
                    No se pudo cargar el detalle de la actividad.
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-700">
                <button type="button" class="btn btn-outline-dark" @click="close">Cerrar</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';
import IconX from '@/Components/vristo/icon/icon-x.vue';

const props = defineProps({
    activityId: {
        type: [Number, null],
        default: null,
    },
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);

const loading = ref(false);
const activity = ref(null);

const close = () => {
    activity.value = null;
    emit('close');
};

const badgeClass = {
    created: 'bg-success',
    updated: 'bg-info',
    deleted: 'bg-danger',
    signed: 'bg-primary',
    cancelled: 'bg-warning',
    restored: 'bg-secondary',
};

const formatDateTime = (value) => {
    if (!value) return '-';
    return new Intl.DateTimeFormat('es-PE', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    }).format(new Date(value));
};

const formatValue = (value) => {
    if (value === null || value === undefined) return 'null';
    if (typeof value === 'boolean') return value ? 'true' : 'false';
    if (typeof value === 'object') return JSON.stringify(value);
    return String(value);
};

const hasData = (metadata) => {
    if (!metadata) return false;
    const ignoreKeys = ['subject_name', 'changed_fields', 'before', 'after', 'deleted_data'];
    return Object.keys(metadata).some((key) => !ignoreKeys.includes(key));
};

const formatJSON = (data) => {
    try {
        return JSON.stringify(data, null, 2);
    } catch {
        return String(data);
    }
};

const loadActivity = async () => {
    if (!props.activityId) return;

    loading.value = true;
    try {
        const response = await axios.get(route('heal_activities_show', props.activityId));
        activity.value = response.data.activity;
    } catch (error) {
        console.error('Error loading activity:', error);
        activity.value = null;
    } finally {
        loading.value = false;
    }
};

watch(() => props.show, (newVal) => {
    if (newVal && props.activityId) {
        loadActivity();
    }
});

watch(() => props.activityId, (newVal) => {
    if (newVal && props.show) {
        loadActivity();
    }
});
</script>
