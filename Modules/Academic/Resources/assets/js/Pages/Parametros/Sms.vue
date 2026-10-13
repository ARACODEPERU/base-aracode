<script setup>
import { computed, reactive, ref } from 'vue';
import axios from 'axios';
import { router, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';

/**
 * Parametros / SMS: canal propio de los avisos de asistencia del colegio.
 *
 * La porteria registra la entrada o la salida del alumno escaneando su carné y
 * este canal avisa al padre o apoderado por SMS. La contrasena de SMSGate no
 * viaja al navegador: si se deja vacia se conserva la guardada.
 */
const props = defineProps({
    channel: { type: Object, default: () => ({}) },
    settings: { type: Object, default: () => ({}) },
    variables: { type: Array, default: () => [] },
    deliveries: { type: Array, default: () => [] },
    countryCode: { type: String, default: '51' },
    parametersUrl: { type: String, default: '' },
});

const codes = computed(() => props.channel.parameterCodes || {});

const form = useForm({
    url: props.settings.url || '',
    username: props.settings.username || '',
    password: '',
    active: !!props.channel.active,
    notify_entry: !!props.channel.entry,
    notify_exit: !!props.channel.exit,
    notify_late: !!props.channel.late,
    notify_early_exit: !!props.channel.early_exit,
    primary_only: !!props.channel.primary_only,
    daily_limit: Number(props.settings.daily_limit || 0),
    entry_message: props.settings.entry_message || '',
    exit_message: props.settings.exit_message || '',
});

const previews = reactive({ in: '', out: '' });
const testing = ref(false);
const testForm = reactive({ phone: '', event: 'in' });

const deliveryStatusLabels = {
    pending: 'Encolada',
    processing: 'Enviando',
    sent: 'Enviada',
    skipped: 'Omitida',
    failed: 'Fallida',
};

const deliveryStatusClasses = {
    pending: 'badge bg-info',
    processing: 'badge bg-warning',
    sent: 'badge bg-success',
    skipped: 'badge bg-secondary',
    failed: 'badge bg-danger',
};

const deliveryStatusLabel = (status) => deliveryStatusLabels[status] || status;
const deliveryStatusClass = (status) => deliveryStatusClasses[status] || 'badge bg-secondary';

const channelReady = computed(() => !!props.channel.configured && form.active);

const addVariable = (field, code) => {
    form[field] = `${form[field] || ''} ${code}`.trim();
};

const firstError = (error) => {
    const messages = error.response?.data?.errors || {};

    return Object.values(messages).flat().join(' ') || error.response?.data?.message || '';
};

const preview = async (event, field) => {
    try {
        const { data } = await axios.post(route('aca_school_sms_parameters_preview'), {
            event,
            message: form[field],
        });

        previews[event] = data.text;
    } catch (error) {
        previews[event] = '';
    }
};

const save = async () => {
    form.post(route('aca_school_sms_parameters_update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.password = '';
            Swal.fire({
                icon: 'success',
                title: 'Parámetros guardados',
                text: 'Se aplican a los próximos avisos de asistencia.',
                customClass: { popup: 'sweet-alerts', confirmButton: 'btn btn-success' },
                buttonsStyling: false,
            });
        },
        onError: (errors) => {
            const message = Object.values(errors).flat().join(' ');

            Swal.fire({
                icon: 'error',
                title: 'No se pudo guardar',
                text: message || 'Revisa los datos e inténtalo de nuevo.',
                customClass: { popup: 'sweet-alerts', confirmButton: 'btn btn-danger' },
                buttonsStyling: false,
            });
        },
    });
};

const sendTest = async () => {
    testing.value = true;

    try {
        const { data } = await axios.post(route('aca_school_sms_parameters_test'), {
            phone: testForm.phone,
            event: testForm.event,
        });

        Swal.fire({
            icon: 'success',
            title: 'SMS de prueba enviado',
            text: data.message,
            customClass: { popup: 'sweet-alerts', confirmButton: 'btn btn-success' },
            buttonsStyling: false,
        });
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'No se pudo enviar',
            text: firstError(error) || 'Revisa el número y la configuración del canal.',
            customClass: { popup: 'sweet-alerts', confirmButton: 'btn btn-danger' },
            buttonsStyling: false,
        });
    } finally {
        testing.value = false;
    }
};

const reloadDeliveries = () => router.reload({ only: ['deliveries'] });
</script>

<template>
    <AppLayout title="Parámetros SMS">
        <Navigation
            :routeModule="route('aca_dashboard')"
            :titleModule="'Académico'"
            :data="[{ title: 'Colegio' }, { title: 'Parámetros SMS' }]"
        />

        <div class="mt-5 flex flex-col gap-6">
            <!-- Estado del canal -->
            <div class="panel">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h5 class="text-lg font-semibold dark:text-white-light">Avisos de asistencia por SMS</h5>
                        <p class="mt-1 text-sm text-gray-500">
                            Cuando la portería registra la entrada o la salida del alumno, se avisa al padre o apoderado.
                            Los envíos se procesan en segundo plano con <code>php artisan queue:work</code>.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="text-sm font-medium dark:text-white-light">Activo</span>
                        <label class="w-12 h-6 relative">
                            <input
                                v-model="form.active"
                                type="checkbox"
                                class="custom_switch absolute w-full h-full opacity-0 z-10 cursor-pointer peer"
                                id="school-sms-active"
                            />
                            <span class="bg-[#ebedf2] dark:bg-dark block h-full before:absolute before:left-1 before:bg-white dark:before:bg-white-dark before:bottom-1 before:w-4 before:h-4 peer-checked:before:left-7 peer-checked:bg-primary before:transition-all before:duration-300"></span>
                        </label>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="rounded border border-gray-200 p-3 text-sm dark:border-gray-700">
                        <span class="block text-xs uppercase text-gray-400">URL ({{ codes.url }})</span>
                        <span :class="channel.url ? 'text-success' : 'text-danger'">
                            {{ channel.url ? 'Configurada' : 'Falta configurar' }}
                        </span>
                    </div>
                    <div class="rounded border border-gray-200 p-3 text-sm dark:border-gray-700">
                        <span class="block text-xs uppercase text-gray-400">Usuario ({{ codes.username }})</span>
                        <span :class="channel.username ? 'text-success' : 'text-danger'">
                            {{ channel.username ? 'Configurado' : 'Falta configurar' }}
                        </span>
                    </div>
                    <div class="rounded border border-gray-200 p-3 text-sm dark:border-gray-700">
                        <span class="block text-xs uppercase text-gray-400">Contraseña ({{ codes.password }})</span>
                        <span :class="channel.password ? 'text-success' : 'text-danger'">
                            {{ channel.password ? 'Configurada' : 'Falta configurar' }}
                        </span>
                    </div>
                </div>

                <p v-if="! channelReady" class="mt-4 rounded bg-warning/10 px-3 py-2 text-sm text-warning">
                    El canal no enviará avisos todavía: completa las credenciales de SMSGate y enciende el interruptor Activo.
                    También puedes revisarlas en
                    <a :href="parametersUrl" class="underline">Parámetros del sistema</a>.
                </p>
            </div>

            <!-- Credenciales propias -->
            <div class="panel">
                <h6 class="text-base font-semibold dark:text-white-light">Credenciales de SMSGate (asistencia)</h6>
                <p class="mt-1 text-sm text-gray-500">
                    Son propias de este canal: no comparten la cuenta de las campañas de especialización ni la de Salud.
                </p>

                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="form-label">URL del servidor <span class="text-xs text-gray-400">({{ codes.url }})</span></label>
                        <input v-model="form.url" type="text" class="form-input" placeholder="https://api.sms-gate.app/mobile/v1" />
                        <p v-if="form.errors.url" class="mt-1 text-xs text-danger">{{ form.errors.url }}</p>
                    </div>
                    <div>
                        <label class="form-label">Usuario <span class="text-xs text-gray-400">({{ codes.username }})</span></label>
                        <input v-model="form.username" type="text" class="form-input" autocomplete="off" />
                        <p v-if="form.errors.username" class="mt-1 text-xs text-danger">{{ form.errors.username }}</p>
                    </div>
                    <div>
                        <label class="form-label">Contraseña <span class="text-xs text-gray-400">({{ codes.password }})</span></label>
                        <input
                            v-model="form.password"
                            type="password"
                            class="form-input"
                            autocomplete="new-password"
                            :placeholder="channel.password ? 'Guardada — escribe una nueva para cambiarla' : 'Contraseña de la cuenta'"
                        />
                        <p v-if="form.errors.password" class="mt-1 text-xs text-danger">{{ form.errors.password }}</p>
                    </div>
                </div>
            </div>

            <!-- Interruptores -->
            <div class="panel">
                <h6 class="text-base font-semibold dark:text-white-light">Qué avisos se envían</h6>

                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <label class="flex items-start gap-3">
                        <input v-model="form.notify_entry" type="checkbox" class="form-checkbox mt-1" />
                        <span>
                            <span class="font-semibold">Avisar las entradas <span class="text-xs text-gray-400">({{ codes.entry }})</span></span>
                            <span class="block text-xs text-gray-500">El apoderado recibe un SMS cuando el alumno ingresa al colegio.</span>
                        </span>
                    </label>

                    <label class="flex items-start gap-3">
                        <input v-model="form.notify_exit" type="checkbox" class="form-checkbox mt-1" />
                        <span>
                            <span class="font-semibold">Avisar las salidas <span class="text-xs text-gray-400">({{ codes.exit }})</span></span>
                            <span class="block text-xs text-gray-500">El apoderado recibe un SMS cuando el alumno sale del colegio.</span>
                        </span>
                    </label>

                    <label class="flex items-start gap-3">
                        <input v-model="form.notify_late" type="checkbox" class="form-checkbox mt-1" />
                        <span>
                            <span class="font-semibold">Incluir las tardanzas <span class="text-xs text-gray-400">({{ codes.late }})</span></span>
                            <span class="block text-xs text-gray-500">Si se apaga, la entrada con tardanza no genera aviso.</span>
                        </span>
                    </label>

                    <label class="flex items-start gap-3">
                        <input v-model="form.notify_early_exit" type="checkbox" class="form-checkbox mt-1" />
                        <span>
                            <span class="font-semibold">Incluir las salidas anticipadas <span class="text-xs text-gray-400">({{ codes.early_exit }})</span></span>
                            <span class="block text-xs text-gray-500">Si se apaga, la salida antes de la hora oficial no genera aviso.</span>
                        </span>
                    </label>

                    <label class="flex items-start gap-3">
                        <input v-model="form.primary_only" type="checkbox" class="form-checkbox mt-1" />
                        <span>
                            <span class="font-semibold">Solo al apoderado principal <span class="text-xs text-gray-400">({{ codes.primary_only }})</span></span>
                            <span class="block text-xs text-gray-500">Si se apaga, se avisa a todos los apoderados con teléfono.</span>
                        </span>
                    </label>

                    <div>
                        <label class="form-label">Máximo de avisos por alumno y día <span class="text-xs text-gray-400">({{ codes.daily_limit }})</span></label>
                        <input v-model.number="form.daily_limit" type="number" min="0" max="999" class="form-input w-32" />
                        <span class="mt-1 block text-xs text-gray-500">0 = sin límite.</span>
                        <p v-if="form.errors.daily_limit" class="mt-1 text-xs text-danger">{{ form.errors.daily_limit }}</p>
                    </div>
                </div>
            </div>

            <!-- Plantillas -->
            <div class="panel">
                <h6 class="text-base font-semibold dark:text-white-light">Mensajes</h6>
                <p class="mt-1 text-sm text-gray-500">
                    Escribe el texto con variables; se reemplazan con los datos reales de cada aviso.
                </p>

                <div class="mt-4 space-y-5">
                    <div v-for="block in [{ event: 'in', field: 'entry_message', code: codes.entry_message, title: 'Mensaje de entrada' }, { event: 'out', field: 'exit_message', code: codes.exit_message, title: 'Mensaje de salida' }]" :key="block.event">
                        <label class="form-label">{{ block.title }} <span class="text-xs text-gray-400">({{ block.code }})</span></label>
                        <textarea v-model="form[block.field]" rows="3" maxlength="480" class="form-textarea" placeholder="Escribe el mensaje del aviso..."></textarea>

                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <span class="text-xs text-gray-500">Insertar variable:</span>
                            <button
                                v-for="variable in variables"
                                :key="variable.code"
                                type="button"
                                class="btn btn-outline-primary btn-xs"
                                :title="variable.label"
                                @click="addVariable(block.field, variable.code)"
                            >
                                {{ variable.code }}
                            </button>
                        </div>

                        <div class="mt-2 flex items-center gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" @click="preview(block.event, block.field)">
                                Vista previa
                            </button>
                            <span class="text-xs text-gray-500">La vista previa usa datos de ejemplo.</span>
                        </div>

                        <pre v-if="previews[block.event]" class="mt-2 whitespace-pre-wrap rounded bg-gray-50 p-3 text-sm dark:bg-gray-800">{{ previews[block.event] }}</pre>
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap items-center justify-end gap-3">
                    <p class="mr-auto text-sm text-gray-500">
                        Los cambios se aplican a los próximos avisos de asistencia.
                    </p>
                    <button type="button" class="btn btn-primary" :disabled="form.processing" @click="save">
                        {{ form.processing ? 'Guardando...' : 'Guardar parámetros' }}
                    </button>
                </div>
            </div>

            <!-- SMS de prueba -->
            <div class="panel">
                <h6 class="text-base font-semibold dark:text-white-light">Enviar SMS de prueba</h6>
                <p class="mt-1 text-sm text-gray-500">
                    Envía el mensaje elegido con datos de ejemplo. Requiere el canal configurado y activo.
                </p>
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <label class="form-label">Teléfono</label>
                        <input v-model="testForm.phone" type="text" inputmode="numeric" maxlength="15" class="form-input" placeholder="Ej: 987987987" />
                        <p class="mt-1 text-xs text-gray-500">Celular de {{ countryCode }}: 9 dígitos o con su código de país.</p>
                    </div>
                    <div>
                        <label class="form-label">Mensaje</label>
                        <select v-model="testForm.event" class="form-select">
                            <option value="in">Entrada</option>
                            <option value="out">Salida</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="button" class="btn btn-secondary" :disabled="testing" @click="sendTest">
                            {{ testing ? 'Enviando...' : 'Enviar prueba' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Entregas recientes -->
            <div class="panel">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h6 class="text-base font-semibold dark:text-white-light">Últimos avisos</h6>
                        <p class="mt-1 text-sm text-gray-500">
                            Resultado de los últimos 50 avisos generados por la portería.
                        </p>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm" @click="reloadDeliveries">Actualizar</button>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full table-auto text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-400 dark:border-gray-700">
                                <th class="px-2 py-2">Alumno</th>
                                <th class="px-2 py-2">Apoderado</th>
                                <th class="px-2 py-2">Evento</th>
                                <th class="px-2 py-2">Teléfono</th>
                                <th class="px-2 py-2">Estado</th>
                                <th class="px-2 py-2">Motivo</th>
                                <th class="px-2 py-2">Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="delivery in deliveries" :key="delivery.id" class="border-b border-gray-100 dark:border-gray-800">
                                <td class="px-2 py-2">{{ delivery.student || '—' }}</td>
                                <td class="px-2 py-2">{{ delivery.guardian || '—' }}</td>
                                <td class="px-2 py-2">{{ delivery.event_label }}</td>
                                <td class="px-2 py-2">{{ delivery.phone || '—' }}</td>
                                <td class="px-2 py-2">
                                    <span :class="deliveryStatusClass(delivery.status)">{{ deliveryStatusLabel(delivery.status) }}</span>
                                </td>
                                <td class="px-2 py-2 text-xs text-gray-500">{{ delivery.error_message || '—' }}</td>
                                <td class="px-2 py-2 text-xs text-gray-500">{{ delivery.created_at || '—' }}</td>
                            </tr>
                            <tr v-if="! deliveries.length">
                                <td colspan="7" class="px-2 py-4 text-center text-gray-500">
                                    Todavía no hay avisos registrados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
