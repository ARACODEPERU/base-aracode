<script setup>
import { computed, reactive, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    channel: {
        type: Object,
        default: () => ({}),
    },
    reviewItems: {
        type: Array,
        default: () => [],
    },
    movements: {
        type: Array,
        default: () => [],
    },
    patients: {
        type: Array,
        default: () => [],
    },
    doctors: {
        type: Array,
        default: () => [],
    },
    parametersUrl: {
        type: String,
        default: '',
    },
    reconcileMinutes: {
        type: Number,
        default: 5,
    },
});

const page = usePage();

const channelActive = ref(Boolean(props.channel.active));
const savedChannelActive = ref(Boolean(props.channel.active));
const inboundActive = ref(Boolean(props.channel.inbound));
const savedInbound = ref(Boolean(props.channel.inbound));
const busy = ref(false);

// Formularios de la bandeja "Por revisar": uno por evento, con el paciente
// sugerido preseleccionado cuando el título coincide con alguien del padrón.
const reviewForms = reactive(
    Object.fromEntries(
        props.reviewItems.map((item) => [
            item.id,
            {
                patient_id: String(matchPatientId(item.suggested_patient) ?? ''),
                doctor_id: props.doctors.length === 1 ? String(props.doctors[0].code) : '',
            },
        ])
    )
);

const stateLabels = {
    linked: 'Enlazado',
    pending_review: 'Por revisar',
    deleted: 'Eliminado',
    ignored: 'Descartado',
    error: 'Error',
};

function matchPatientId(name) {
    if (!name) {
        return null;
    }

    const target = String(name).trim().toLowerCase();
    const found = props.patients.find((patient) => String(patient.name ?? '').trim().toLowerCase() === target)
        || props.patients.find((patient) => String(patient.name ?? '').trim().toLowerCase().startsWith(target));

    return found ? found.code : null;
}

const ready = computed(() => Boolean(props.channel.ready));
const connected = computed(() => Boolean(props.channel.refreshToken));
const credentialsReady = computed(() => Boolean(props.channel.clientId) && Boolean(props.channel.clientSecret));

const flash = (icon, title, text) => {
    Swal.fire({
        icon,
        title,
        text,
        customClass: { popup: 'sweet-alerts', confirmButton: icon === 'error' ? 'btn btn-danger' : 'btn btn-success' },
        buttonsStyling: false,
    });
};

const showFlash = () => {
    if (page.props.flash?.message) {
        flash('success', 'Listo', page.props.flash.message);
    }

    if (page.props.flash?.error) {
        flash('error', 'No se pudo completar', page.props.flash.error);
    }
};

// Al guardar un interruptor se usa el mismo endpoint de Parámetros del sistema
// que usa Salud > Avisos: el switch queda persistido en el parámetro.
const saveParameter = async (parameterId, value) => {
    if (!parameterId) {
        return;
    }

    await axios.post(route('parameters_update_default_value', [parameterId]), {
        value_default: value ? '1' : '0',
    });
};

const toggleChannel = async () => {
    try {
        await saveParameter(props.channel.enabledParameterId, channelActive.value);
        savedChannelActive.value = channelActive.value;
        flash('success', 'Cambio guardado', channelActive.value
            ? 'La sincronización con Google Calendar quedó activa.'
            : 'La sincronización con Google Calendar quedó apagada.');
    } catch (error) {
        channelActive.value = savedChannelActive.value;
        flash('error', 'No se pudo guardar', 'Revisa el parámetro del interruptor Activo.');
    }
};

const toggleInbound = async () => {
    try {
        await saveParameter(props.channel.inboundParameterId, inboundActive.value);
        savedInbound.value = inboundActive.value;
        flash('success', 'Cambio guardado', inboundActive.value
            ? 'Los cambios hechos en Google volverán al sistema.'
            : 'El sistema solo enviará cambios hacia Google.');
    } catch (error) {
        inboundActive.value = savedInbound.value;
        flash('error', 'No se pudo guardar', 'Revisa el parámetro del interruptor de recepción.');
    }
};

const post = (name, options = {}) => {
    busy.value = true;

    router.post(route(name, options.routeParams ?? []), options.data ?? {}, {
        preserveScroll: true,
        onFinish: () => {
            busy.value = false;
        },
    });
};

const syncNow = () => {
    if (!ready.value) {
        flash('error', 'Sincronización no disponible', 'Activa el canal y completa las credenciales de Google.');
        return;
    }

    post('heal_google_calendar_sync');
};

const refreshChannel = () => {
    post('heal_google_calendar_channel');
};

const disconnect = () => {
    Swal.fire({
        icon: 'warning',
        title: '¿Desconectar la cuenta de Google?',
        text: 'Se olvida el refresh token. Las citas ya sincronizadas no se tocan.',
        showCancelButton: true,
        confirmButtonText: 'Desconectar',
        cancelButtonText: 'Cancelar',
        customClass: { popup: 'sweet-alerts', confirmButton: 'btn btn-danger', cancelButton: 'btn btn-secondary' },
        buttonsStyling: false,
    }).then((result) => {
        if (result.isConfirmed) {
            post('heal_google_calendar_disconnect');
        }
    });
};

const review = (item) => {
    const form = reviewForms[item.id] ?? {};

    if (!form.patient_id) {
        flash('error', 'Falta el paciente', 'Elige el paciente de la cita para traer el evento.');
        return;
    }

    post('heal_google_calendar_review', {
        routeParams: [item.id],
        data: {
            patient_id: Number(form.patient_id),
            doctor_id: form.doctor_id ? Number(form.doctor_id) : null,
        },
    });
};

const discard = (item) => {
    post('heal_google_calendar_review_discard', { routeParams: [item.id] });
};

showFlash();
</script>

<template>
    <AppLayout title="Google Calendar">
        <Navigation>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Salud</span>
            </li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Google Calendar</span>
            </li>
        </Navigation>

        <div class="mt-5 flex flex-col gap-6">
            <!-- Estado del canal -->
            <div class="panel">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h5 class="text-lg font-semibold dark:text-white-light">Sincronización con Google Calendar</h5>
                        <p class="mt-1 text-sm text-gray-500">
                            Cada cita creada, movida o cancelada en la Agenda se refleja en el calendario del consultorio, y los
                            eventos creados o editados en Google vuelven al sistema. El trabajo pesado corre en la cola
                            (<code>php artisan queue:work</code>).
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="text-sm font-medium dark:text-white-light">Activo</span>
                        <label class="w-12 h-6 relative">
                            <input
                                v-model="channelActive"
                                type="checkbox"
                                class="custom_switch absolute w-full h-full opacity-0 z-10 cursor-pointer peer"
                                id="google-calendar-active"
                                @change="toggleChannel"
                            />
                            <span class="bg-[#ebedf2] dark:bg-dark block h-full before:absolute before:left-1 before:bg-white dark:before:bg-white-dark before:bottom-1 before:w-4 before:h-4 peer-checked:before:left-7 peer-checked:bg-primary before:transition-all before:duration-300"></span>
                        </label>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded border border-gray-200 p-3 text-sm dark:border-gray-700">
                        <span class="block text-xs uppercase text-gray-400">Cuenta de Google</span>
                        <span :class="connected ? 'text-success' : 'text-danger'">
                            {{ connected ? 'Conectada' : 'Sin conectar' }}
                        </span>
                    </div>
                    <div class="rounded border border-gray-200 p-3 text-sm dark:border-gray-700">
                        <span class="block text-xs uppercase text-gray-400">Calendar ID ({{ channel.parameterCodes?.calendar_id }})</span>
                        <span class="text-gray-700 dark:text-white-light">{{ channel.calendarId || '—' }}</span>
                    </div>
                    <div class="rounded border border-gray-200 p-3 text-sm dark:border-gray-700">
                        <span class="block text-xs uppercase text-gray-400">Última lectura</span>
                        <span class="text-gray-700 dark:text-white-light">{{ channel.lastSyncAt || 'Nunca' }}</span>
                    </div>
                    <div class="rounded border border-gray-200 p-3 text-sm dark:border-gray-700">
                        <span class="block text-xs uppercase text-gray-400">Canal de avisos push</span>
                        <span :class="channel.channelFresh ? 'text-success' : 'text-warning'">
                            {{ channel.channelExpiresAt ? `Vence ${channel.channelExpiresAt}` : 'Sin registrar' }}
                        </span>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <a v-if="!connected" :href="route('heal_google_calendar_connect')" class="btn btn-primary">
                        Conectar con Google
                    </a>
                    <button v-else type="button" class="btn btn-outline-danger" :disabled="busy" @click="disconnect">
                        Desconectar
                    </button>
                    <button type="button" class="btn btn-secondary" :disabled="busy" @click="syncNow">Sincronizar ahora</button>
                    <button type="button" class="btn btn-outline-primary" :disabled="busy" @click="refreshChannel">
                        Renovar canal
                    </button>
                    <span class="text-xs text-gray-500">
                        La reconciliación automática corre cada {{ reconcileMinutes }} minutos.
                    </span>
                </div>

                <p v-if="!credentialsReady" class="mt-4 rounded bg-warning/10 px-3 py-2 text-sm text-warning">
                    Faltan las credenciales de Google: completa el Client ID y el Client Secret ({{ channel.parameterCodes?.client_id }} y
                    {{ channel.parameterCodes?.client_secret }}) en
                    <a :href="parametersUrl" class="underline">Parámetros del sistema</a>.
                </p>

                <p v-else-if="!connected" class="mt-4 rounded bg-warning/10 px-3 py-2 text-sm text-warning">
                    Pulsa «Conectar con Google» para autorizar la cuenta del consultorio: sin el refresh token
                    ({{ channel.parameterCodes?.refresh_token }}) no se sincroniza nada.
                </p>

                <p v-if="channel.lastError" class="mt-4 rounded bg-danger/10 px-3 py-2 text-sm text-danger">
                    Último error: {{ channel.lastError }}
                </p>
            </div>

            <!-- Dirección Google -> sistema -->
            <div class="panel">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h6 class="text-base font-semibold dark:text-white-light">Recibir cambios de Google</h6>
                        <p class="mt-1 text-sm text-gray-500">
                            Google avisa por notificaciones push y, además,
                            el sistema lee el calendario cada {{ reconcileMinutes }} minutos. Apaga este interruptor si solo quieres
                            enviar de la Agenda hacia Google.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="text-sm font-medium dark:text-white-light">Activo</span>
                        <label class="w-12 h-6 relative">
                            <input
                                v-model="inboundActive"
                                type="checkbox"
                                class="custom_switch absolute w-full h-full opacity-0 z-10 cursor-pointer peer"
                                id="google-calendar-inbound"
                                @change="toggleInbound"
                            />
                            <span class="bg-[#ebedf2] dark:bg-dark block h-full before:absolute before:left-1 before:bg-white dark:before:bg-white-dark before:bottom-1 before:w-4 before:h-4 peer-checked:before:left-7 peer-checked:bg-primary before:transition-all before:duration-300"></span>
                        </label>
                    </div>
                </div>

                <div class="mt-4 rounded border border-gray-200 p-3 text-xs dark:border-gray-700">
                    <span class="block uppercase text-gray-400">URL de notificaciones</span>
                    <code class="break-all">{{ channel.webhookUrl || channel.routeUrl || '—' }}</code>
                    <p v-if="!channel.publicWebhook" class="mt-2 text-warning">
                        Google solo entrega notificaciones a una URL HTTPS pública. En local no hay push: los cambios llegan por la
                        reconciliación programada cada {{ reconcileMinutes }} minutos.
                    </p>
                </div>
            </div>

            <!-- Bandeja "Por revisar" -->
            <div class="panel">
                <h6 class="text-base font-semibold dark:text-white-light">Por revisar ({{ reviewItems.length }})</h6>
                <p class="mt-1 text-sm text-gray-500">
                    Eventos que existen en Google y no se pudieron enlazar solos (falta el paciente en el padrón o hay varios
                    doctores). Asigna paciente y doctor para traerlos a la Agenda.
                </p>

                <p v-if="!reviewItems.length" class="mt-4 text-sm text-gray-500">
                    No hay eventos pendientes. Los eventos creados en Google se traen solos cuando el título empieza con el nombre
                    exacto de un paciente y hay un solo doctor registrado.
                </p>

                <div v-else class="mt-4 table-responsive">
                    <table class="table-hover w-full table-auto">
                        <thead>
                            <tr>
                                <th>Evento</th>
                                <th>Inicio</th>
                                <th>Paciente</th>
                                <th>Doctor</th>
                                <th class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in reviewItems" :key="item.id">
                                <td>
                                    <span class="font-semibold dark:text-white-light">{{ item.summary || '(sin título)' }}</span>
                                    <span v-if="item.description" class="block max-w-md truncate text-xs text-gray-500">{{ item.description }}</span>
                                </td>
                                <td class="whitespace-nowrap">{{ item.starts_at || '—' }}</td>
                                <td>
                                    <select v-model="reviewForms[item.id].patient_id" class="form-select w-56">
                                        <option value="">Selecciona paciente</option>
                                        <option v-for="patient in patients" :key="patient.code" :value="String(patient.code)">
                                            {{ patient.name }}
                                        </option>
                                    </select>
                                </td>
                                <td>
                                    <select v-model="reviewForms[item.id].doctor_id" class="form-select w-48">
                                        <option value="">Elige doctor</option>
                                        <option v-for="doctor in doctors" :key="doctor.code" :value="String(doctor.code)">
                                            {{ doctor.name }}
                                        </option>
                                    </select>
                                </td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button" class="btn btn-sm btn-primary" :disabled="busy" @click="review(item)">
                                            Traer a la Agenda
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" :disabled="busy" @click="discard(item)">
                                            Descartar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Últimos movimientos -->
            <div class="panel">
                <h6 class="text-base font-semibold dark:text-white-light">Últimos movimientos</h6>

                <p v-if="!movements.length" class="mt-3 text-sm text-gray-500">Todavía no hay movimientos registrados.</p>

                <div v-else class="mt-4 table-responsive">
                    <table class="table-hover w-full table-auto">
                        <thead>
                            <tr>
                                <th>Estado</th>
                                <th>Origen</th>
                                <th>Evento</th>
                                <th>Cita</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="movement in movements" :key="movement.id">
                                <td>
                                    <span
                                        class="badge"
                                        :class="{
                                            'bg-success/20 text-success': movement.state === 'linked',
                                            'bg-danger/20 text-danger': movement.state === 'error',
                                            'bg-warning/20 text-warning': movement.state === 'deleted',
                                            'bg-gray-200 text-gray-600': movement.state === 'ignored',
                                        }"
                                    >
                                        {{ stateLabels[movement.state] || movement.state }}
                                    </span>
                                </td>
                                <td>{{ movement.origin === 'google' ? 'Google' : 'Salud' }}</td>
                                <td>
                                    <span class="dark:text-white-light">{{ movement.summary || '(sin título)' }}</span>
                                    <span v-if="movement.error" class="block text-xs text-danger">{{ movement.error }}</span>
                                </td>
                                <td>
                                    <span v-if="movement.correlative">#{{ movement.correlative }}</span>
                                    <span v-if="movement.patient" class="block text-xs text-gray-500">{{ movement.patient }}</span>
                                    <span v-if="!movement.correlative" class="text-gray-400">—</span>
                                </td>
                                <td class="whitespace-nowrap">{{ movement.updated_at }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
