<script setup>
import { computed, reactive, ref } from 'vue';
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    notices: {
        type: Array,
        default: () => [],
    },
    channel: {
        type: Object,
        default: () => ({}),
    },
    variables: {
        type: Array,
        default: () => [],
    },
    timeOptions: {
        type: Array,
        default: () => [],
    },
    parametersUrl: {
        type: String,
        default: '',
    },
    clinicName: {
        type: String,
        default: '',
    },
});

const blocks = ref(props.notices.map((notice) => ({ ...notice })));
const channelActive = ref(Boolean(props.channel.active));
const savedChannelActive = ref(Boolean(props.channel.active));
const saving = ref(false);
const previews = ref({});
const testForm = reactive({ phone: '', key: 'first' });

const titles = {
    first: 'Primer aviso antes de la cita',
    second: 'Segundo aviso antes de la cita',
    day_before: 'Aviso un día antes',
};

// Bloques "minutos antes" que no usan un preset: muestran el input personalizado.
const customMinutes = ref(
    Object.fromEntries(
        props.notices
            .filter((notice) => notice.key !== 'day_before' && !props.timeOptions.some((option) => Number(option.value) === Number(notice.minutes_before)))
            .map((notice) => [notice.key, true])
    )
);

const isDayBefore = (block) => block.key === 'day_before';
const titleFor = (block) => titles[block.key] || block.key;
const usesCustom = (block) => Boolean(customMinutes.value[block.key]);

const onTimeChange = (block, event) => {
    const value = event.target.value;

    if (value === 'custom') {
        customMinutes.value[block.key] = true;
        return;
    }

    customMinutes.value[block.key] = false;
    block.minutes_before = Number(value);
};

const addVariable = (block, code) => {
    block.message = `${block.message || ''} ${code}`.trim();
};

const preview = async (block) => {
    try {
        const { data } = await axios.post(route('heal_appointment_notices_preview'), {
            message: block.message,
        });

        previews.value = { ...previews.value, [block.key]: data.text };
    } catch (error) {
        previews.value = { ...previews.value, [block.key]: '' };
    }
};

const save = async () => {
    saving.value = true;

    try {
        const { data } = await axios.post(route('heal_appointment_notices_update'), {
            notices: blocks.value,
        });

        Swal.fire({
            icon: 'success',
            title: 'Avisos guardados',
            text: data.message,
            customClass: { popup: 'sweet-alerts', confirmButton: 'btn btn-success' },
            buttonsStyling: false,
        });
    } catch (error) {
        const messages = error.response?.data?.errors || {};
        const firstError = Object.values(messages).flat().join(' ');

        Swal.fire({
            icon: 'error',
            title: 'No se pudo guardar',
            text: firstError || error.response?.data?.message || 'Revisa los datos e inténtalo de nuevo.',
            customClass: { popup: 'sweet-alerts', confirmButton: 'btn btn-danger' },
            buttonsStyling: false,
        });
    } finally {
        saving.value = false;
    }
};

const toggleChannel = async () => {
    if (!props.channel.parameterId) {
        return;
    }

    const value = channelActive.value ? '1' : '0';

    try {
        await axios.post(route('parameters_update_default_value', [props.channel.parameterId]), {
            value_default: value,
        });

        savedChannelActive.value = channelActive.value;
    } catch (error) {
        channelActive.value = savedChannelActive.value;
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo cambiar el estado del canal.',
            customClass: { popup: 'sweet-alerts', confirmButton: 'btn btn-danger' },
            buttonsStyling: false,
        });
    }
};

const sendTest = async () => {
    try {
        const { data } = await axios.post(route('heal_appointment_notices_test'), {
            phone: testForm.phone,
            key: testForm.key,
        });

        Swal.fire({
            icon: 'success',
            title: 'Prueba encolada',
            text: data.message,
            customClass: { popup: 'sweet-alerts', confirmButton: 'btn btn-success' },
            buttonsStyling: false,
        });
    } catch (error) {
        const messages = error.response?.data?.errors || {};
        const firstError = Object.values(messages).flat().join(' ');

        Swal.fire({
            icon: 'error',
            title: 'No se pudo enviar',
            text: firstError || error.response?.data?.message || 'Revisa el número e inténtalo de nuevo.',
            customClass: { popup: 'sweet-alerts', confirmButton: 'btn btn-danger' },
            buttonsStyling: false,
        });
    }
};

const channelReady = computed(() => Boolean(props.channel.configured) && channelActive.value);
</script>

<template>
    <AppLayout title="Avisos">
        <Navigation>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Salud</span>
            </li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Avisos</span>
            </li>
        </Navigation>

        <div class="mt-5 flex flex-col gap-6">
            <!-- Estado del canal SMS Gateway -->
            <div class="panel">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h5 class="text-lg font-semibold dark:text-white-light">Avisos de citas por SMS</h5>
                        <p class="mt-1 text-sm text-gray-500">
                            Recordatorios automáticos a los pacientes de la Agenda. Los envíos se procesan en segundo plano
                            con <code>php artisan queue:work</code>.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="text-sm font-medium dark:text-white-light">Activo</span>
                        <label class="w-12 h-6 relative">
                            <input
                                v-model="channelActive"
                                type="checkbox"
                                class="custom_switch absolute w-full h-full opacity-0 z-10 cursor-pointer peer"
                                id="notice-channel-active"
                                @change="toggleChannel"
                            />
                            <span class="bg-[#ebedf2] dark:bg-dark block h-full before:absolute before:left-1 before:bg-white dark:before:bg-white-dark before:bottom-1 before:w-4 before:h-4 peer-checked:before:left-7 peer-checked:bg-primary before:transition-all before:duration-300"></span>
                        </label>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="rounded border border-gray-200 p-3 text-sm dark:border-gray-700">
                        <span class="block text-xs uppercase text-gray-400">URL ({{ channel.parameterCodes?.url }})</span>
                        <span :class="channel.url ? 'text-success' : 'text-danger'">
                            {{ channel.url ? 'Configurada' : 'Falta configurar' }}
                        </span>
                    </div>
                    <div class="rounded border border-gray-200 p-3 text-sm dark:border-gray-700">
                        <span class="block text-xs uppercase text-gray-400">Usuario ({{ channel.parameterCodes?.username }})</span>
                        <span :class="channel.username ? 'text-success' : 'text-danger'">
                            {{ channel.username ? 'Configurado' : 'Falta configurar' }}
                        </span>
                    </div>
                    <div class="rounded border border-gray-200 p-3 text-sm dark:border-gray-700">
                        <span class="block text-xs uppercase text-gray-400">Contraseña ({{ channel.parameterCodes?.password }})</span>
                        <span :class="channel.password ? 'text-success' : 'text-danger'">
                            {{ channel.password ? 'Configurada' : 'Falta configurar' }}
                        </span>
                    </div>
                </div>

                <p v-if="!channelReady" class="mt-4 rounded bg-warning/10 px-3 py-2 text-sm text-warning">
                    El canal no enviará avisos todavía: completa las credenciales de SMSGate (Salud) en
                    <a :href="parametersUrl" class="underline">Parámetros del sistema</a> y enciende el interruptor Activo.
                </p>
            </div>

            <!-- Bloques de aviso -->
            <div v-for="block in blocks" :key="block.key" class="panel">
                <div class="flex items-center justify-between">
                    <h6 class="text-base font-semibold dark:text-white-light">{{ titleFor(block) }}</h6>
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-gray-500">Activo</span>
                        <label class="w-12 h-6 relative">
                            <input
                                v-model="block.active"
                                type="checkbox"
                                class="custom_switch absolute w-full h-full opacity-0 z-10 cursor-pointer peer"
                                :id="`notice-active-${block.key}`"
                            />
                            <span class="bg-[#ebedf2] dark:bg-dark block h-full before:absolute before:left-1 before:bg-white dark:before:bg-white-dark before:bottom-1 before:w-4 before:h-4 peer-checked:before:left-7 peer-checked:bg-primary before:transition-all before:duration-300"></span>
                        </label>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <!-- Aviso "minutos antes" -->
                    <template v-if="!isDayBefore(block)">
                        <div>
                            <label class="form-label">Tiempo antes de la cita</label>
                            <select class="form-select" :value="usesCustom(block) ? 'custom' : block.minutes_before" @change="onTimeChange(block, $event)">
                                <option v-for="option in timeOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                                <option value="custom">Personalizado (minutos)</option>
                            </select>
                        </div>
                        <div v-if="usesCustom(block)">
                            <label class="form-label">Minutos antes</label>
                            <input v-model.number="block.minutes_before" type="number" min="5" max="10080" class="form-input" placeholder="Ej: 45" />
                        </div>
                    </template>

                    <!-- Aviso "un día antes" -->
                    <div v-else>
                        <label class="form-label">Hora del día anterior</label>
                        <input v-model="block.send_time" type="time" class="form-input w-40" />
                        <p class="mt-1 text-xs text-gray-500">
                            Si la cita es el 14, el paciente recibe el aviso el 13 a esta hora.
                        </p>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="form-label">Mensaje (admite HTML y emoticonos)</label>
                    <textarea v-model="block.message" rows="4" maxlength="1000" class="form-textarea" placeholder="Escribe el mensaje del aviso..."></textarea>

                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <span class="text-xs text-gray-500">Insertar variable:</span>
                        <button
                            v-for="variable in variables"
                            :key="variable.code"
                            type="button"
                            class="btn btn-outline-primary btn-xs"
                            :title="variable.label"
                            @click="addVariable(block, variable.code)"
                        >
                            {{ variable.code }}
                        </button>
                    </div>
                </div>

                <div class="mt-3 flex items-center gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" @click="preview(block)">
                        Vista previa
                    </button>
                    <span v-if="block.key === 'day_before'" class="text-xs text-gray-500">
                        La vista previa usa datos de ejemplo.
                    </span>
                </div>

                <pre v-if="previews[block.key]" class="mt-3 whitespace-pre-wrap rounded bg-gray-50 p-3 text-sm dark:bg-gray-800">{{ previews[block.key] }}</pre>
            </div>

            <!-- Guardar -->
            <div class="panel">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm text-gray-500">
                        Los cambios se aplican a los próximos recordatorios que detecte el planificador (cada minuto).
                    </p>
                    <button type="button" class="btn btn-primary" :disabled="saving" @click="save">
                        {{ saving ? 'Guardando...' : 'Guardar avisos' }}
                    </button>
                </div>
            </div>

            <!-- Enviar prueba -->
            <div class="panel">
                <h6 class="text-base font-semibold dark:text-white-light">Enviar SMS de prueba</h6>
                <p class="mt-1 text-sm text-gray-500">
                    Encola un SMS con el mensaje del bloque elegido (datos de ejemplo). Requiere el canal configurado y activo.
                </p>
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <label class="form-label">Teléfono (con código de país)</label>
                        <input v-model="testForm.phone" type="text" class="form-input" placeholder="Ej: +51999888777" />
                    </div>
                    <div>
                        <label class="form-label">Bloque</label>
                        <select v-model="testForm.key" class="form-select">
                            <option v-for="block in blocks" :key="block.key" :value="block.key">{{ titleFor(block) }}</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="button" class="btn btn-secondary" @click="sendTest">Enviar prueba</button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
