<script setup>
import { computed, nextTick, reactive, ref, watch } from 'vue';
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
    // Plantillas del evento (título y descripción) y variables disponibles.
    eventTemplate: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();

const channelActive = ref(Boolean(props.channel.active));
const savedChannelActive = ref(Boolean(props.channel.active));
const inboundActive = ref(Boolean(props.channel.inbound));
const savedInbound = ref(Boolean(props.channel.inbound));
const busy = ref(false);

// Diagnóstico del botón "Probar conexión". Llega como flash y se refresca con
// un vigilante: Inertia reutiliza el componente en las peticiones POST, así que
// una lectura única al montar nunca vería el resultado.
const diagnostics = ref([]);
const diagnosticsAt = ref('');
const diagnosticsPanel = ref(null);

// Plantillas del evento: se editan aquí y se guardan sin recargar la pantalla.
const templates = reactive({
    title: String(props.eventTemplate?.title ?? ''),
    description: String(props.eventTemplate?.description ?? ''),
});
const titleInput = ref(null);
const descriptionInput = ref(null);
const preview = ref(null);
const previewSource = ref('');
const templateBusy = ref(false);
const templateMessage = ref('');

const variables = computed(() => props.eventTemplate?.variables ?? []);
const templateDefaults = computed(() => ({
    title: String(props.eventTemplate?.defaultTitle ?? ''),
    description: String(props.eventTemplate?.defaultDescription ?? ''),
}));
const variableGroups = computed(() => {
    const groups = new Map();

    variables.value.forEach((item) => {
        const group = item.group ?? 'Variables';
        groups.set(group, [...(groups.get(group) ?? []), item]);
    });

    return Array.from(groups, ([group, items]) => ({ group, items }));
});

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
const accountLabel = computed(() => props.channel.accountEmail || 'Cuenta de Google conectada');
const redirectUri = computed(() => props.channel.redirectUri || '');

// Un error de credenciales caducadas (modo "Prueba" de Google: 7 días) se
// explica en la pantalla en vez de dejar solo el mensaje de Google.
const expiredGrant = computed(() => {
    const lastError = String(props.channel.lastError ?? '').toLowerCase();

    return lastError.includes('invalid_grant') || lastError.includes('expired or revoked');
});

const flash = (icon, title, text) => {
    Swal.fire({
        icon,
        title,
        text,
        customClass: { popup: 'sweet-alerts', confirmButton: icon === 'error' ? 'btn btn-danger' : 'btn btn-success' },
        buttonsStyling: false,
    });
};

// Avisos y resultado del diagnóstico.
//
// Inertia reutiliza el componente de la página en las peticiones POST, así que
// no basta con leer los avisos al montar: se procesan al montar (por ejemplo al
// volver de Google) y en cada respuesta nueva.
const handleFlash = (flashProps) => {
    const payload = flashProps?.diagnostics;

    if (payload && Array.isArray(payload.checks)) {
        diagnostics.value = payload.checks;
        diagnosticsAt.value = payload.at ?? '';

        nextTick(() => {
            diagnosticsPanel.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }

    if (flashProps?.message) {
        flash('success', 'Listo', flashProps.message);
    }

    if (flashProps?.error) {
        flash('error', 'No se pudo completar', flashProps.error);
    }
};

handleFlash(page.props.flash);
watch(() => page.props.flash, handleFlash);

// El estado real lo manda el servidor: los interruptores se sincronizan con
// cada respuesta (así una acción fallida vuelve a su sitio).
watch(() => props.channel, (channel) => {
    channelActive.value = Boolean(channel.active);
    savedChannelActive.value = channelActive.value;
    inboundActive.value = Boolean(channel.inbound);
    savedInbound.value = inboundActive.value;
});

// La bandeja puede traer eventos nuevos: se les crea su formulario para que los
// desplegables siempre tengan dónde guardar.
watch(() => props.reviewItems, (items) => {
    items.forEach((item) => {
        if (!reviewForms[item.id]) {
            reviewForms[item.id] = {
                patient_id: String(matchPatientId(item.suggested_patient) ?? ''),
                doctor_id: props.doctors.length === 1 ? String(props.doctors[0].code) : '',
            };
        }
    });
});

// Textos del evento: insertar variables, ver la vista previa y guardar.
const savedTitle = ref(String(props.eventTemplate?.title ?? ''));
const savedDescription = ref(String(props.eventTemplate?.description ?? ''));

const templatesChanged = computed(
    () => templates.title !== savedTitle.value || templates.description !== savedDescription.value
);

const diagnosticsSummary = computed(() => {
    if (!diagnostics.value.length) {
        return null;
    }

    const failed = diagnostics.value.filter((check) => !check.ok);

    return {
        ok: failed.length === 0,
        total: diagnostics.value.length,
        passed: diagnostics.value.length - failed.length,
        failed,
    };
});

// El campo donde está el cursor es el que recibe la variable.
const activeField = ref('description');

const insertVariable = (token, target = activeField.value) => {
    const element = target === 'title' ? titleInput.value : descriptionInput.value;
    const snippet = `{${token}}`;

    if (!element) {
        templates[target] = `${templates[target]}${snippet}`;
        return;
    }

    const start = element.selectionStart ?? templates[target].length;
    const end = element.selectionEnd ?? start;

    templates[target] = `${templates[target].slice(0, start)}${snippet}${templates[target].slice(end)}`;

    nextTick(() => {
        element.focus();
        const cursor = start + snippet.length;
        element.setSelectionRange(cursor, cursor);
    });
};

const runPreview = async () => {
    templateBusy.value = true;
    templateMessage.value = '';

    try {
        const { data } = await axios.post(route('heal_google_calendar_templates_preview'), {
            title: templates.title,
            description: templates.description,
        });

        preview.value = data.preview;
        previewSource.value = data.source ?? '';
    } catch (error) {
        preview.value = null;
        templateMessage.value = 'No se pudo generar la vista previa. Revisa los textos e inténtalo otra vez.';
    } finally {
        templateBusy.value = false;
    }
};

const saveTemplates = async () => {
    templateBusy.value = true;
    templateMessage.value = '';

    try {
        const { data } = await axios.post(route('heal_google_calendar_templates'), {
            title: templates.title,
            description: templates.description,
        });

        savedTitle.value = templates.title;
        savedDescription.value = templates.description;
        templateMessage.value = data.message ?? 'Plantillas guardadas.';
        flash('success', 'Plantillas guardadas', templateMessage.value);
    } catch (error) {
        const errors = error?.response?.data?.errors;
        const firstError = errors ? Object.values(errors)[0]?.[0] : null;

        templateMessage.value = firstError ?? 'No se pudieron guardar las plantillas.';
        flash('error', 'No se pudo guardar', templateMessage.value);
    } finally {
        templateBusy.value = false;
    }
};

const restoreDefaults = () => {
    templates.title = templateDefaults.value.title;
    templates.description = templateDefaults.value.description;
    preview.value = null;
    templateMessage.value = 'Textos de fábrica restaurados: pulsa Guardar para aplicarlos.';
};

const previewLine = (value) => (value === '' ? '(vacío)' : value);

const copyRedirectUri = async () => {
    if (!redirectUri.value) {
        return;
    }

    try {
        await navigator.clipboard.writeText(redirectUri.value);
        flash('success', 'Copiado', 'Pega esta URL en Google Cloud Console como URI de redireccionamiento autorizado.');
    } catch (error) {
        flash('error', 'No se pudo copiar', 'Copia la URL manualmente desde el recuadro.');
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
        flash('error', 'Sincronización no disponible', 'Activa el canal y conecta la cuenta de Google.');
        return;
    }

    post('heal_google_calendar_sync');
};

const refreshChannel = () => {
    post('heal_google_calendar_channel');
};

const testConnection = () => {
    // El resultado anterior se limpia para que el aviso sea siempre de esta prueba.
    diagnostics.value = [];
    diagnosticsAt.value = '';
    post('heal_google_calendar_test');
};

const disconnect = () => {
    Swal.fire({
        icon: 'warning',
        title: '¿Desconectar la cuenta de Google?',
        text: 'Se revoca el permiso en tu cuenta de Google y se olvida la conexión. Las citas ya sincronizadas no se tocan.',
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
            <!-- Resultado de "Probar conexión": verde si todo respondió, rojo con el motivo si algo falló -->
            <div
                v-if="diagnosticsSummary"
                class="panel border-l-4"
                :class="diagnosticsSummary.ok ? 'border-success' : 'border-danger'"
            >
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-start gap-3">
                        <span class="text-2xl leading-none" :class="diagnosticsSummary.ok ? 'text-success' : 'text-danger'">
                            {{ diagnosticsSummary.ok ? '✓' : '✕' }}
                        </span>
                        <div>
                            <h6 class="text-base font-semibold dark:text-white-light">
                                {{ diagnosticsSummary.ok
                                    ? `Conexión verificada: ${diagnosticsSummary.passed} de ${diagnosticsSummary.total} comprobaciones correctas`
                                    : `La conexión falló en «${diagnosticsSummary.failed[0].label}»` }}
                            </h6>
                            <p class="text-sm text-gray-500">
                                {{ diagnosticsSummary.ok ? 'Google respondió bien y el calendario es accesible.' : diagnosticsSummary.failed[0].message }}
                                <span v-if="diagnosticsAt">Última prueba: {{ diagnosticsAt }}.</span>
                            </p>
                            <p
                                v-if="!diagnosticsSummary.ok && diagnosticsSummary.failed[0].hint"
                                class="mt-1 text-xs text-warning"
                            >
                                {{ diagnosticsSummary.failed[0].hint }}
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="btn btn-sm btn-outline-primary whitespace-nowrap"
                        @click="diagnosticsPanel?.scrollIntoView({ behavior: 'smooth', block: 'start' })"
                    >
                        Ver detalle
                    </button>
                </div>
            </div>

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

                <!-- Cuenta conectada (o el botón para conectarla) -->
                <div
                    class="mt-4 flex flex-col gap-4 rounded border border-gray-200 p-4 dark:border-gray-700 md:flex-row md:items-center md:justify-between"
                >
                    <div class="flex items-center gap-3">
                        <img
                            v-if="connected && channel.accountPicture"
                            :src="channel.accountPicture"
                            alt="Cuenta de Google"
                            class="h-10 w-10 rounded-full"
                        />
                        <div v-else class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-200 text-sm font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            {{ connected ? 'G' : '?' }}
                        </div>
                        <div>
                            <span class="block text-xs uppercase text-gray-400">Cuenta de Google</span>
                            <span :class="connected ? 'font-semibold text-success' : 'text-danger'">
                                {{ connected ? `Conectada como ${accountLabel}` : 'Sin conectar' }}
                            </span>
                            <span v-if="connected && channel.accountName" class="block text-xs text-gray-500">
                                {{ channel.accountName }}
                            </span>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <a
                            v-if="!connected && credentialsReady"
                            :href="route('heal_google_calendar_connect')"
                            class="btn btn-primary"
                        >
                            Continuar con Google
                        </a>
                        <span v-else-if="!connected" class="text-sm text-warning">
                            Registra la credencial (abajo) para poder conectar la cuenta.
                        </span>
                        <button v-else type="button" class="btn btn-outline-danger" :disabled="busy" @click="disconnect">
                            Desconectar
                        </button>
                        <button type="button" class="btn btn-secondary" :disabled="busy" @click="syncNow">Sincronizar ahora</button>
                        <button type="button" class="btn btn-outline-primary" :disabled="busy" @click="testConnection">
                            Probar conexión
                        </button>
                        <button type="button" class="btn btn-outline-primary" :disabled="busy" @click="refreshChannel">
                            Renovar canal
                        </button>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded border border-gray-200 p-3 text-sm dark:border-gray-700">
                        <span class="block text-xs uppercase text-gray-400">Credenciales</span>
                        <span :class="credentialsReady ? 'text-success' : 'text-danger'">
                            {{ credentialsReady ? 'Registradas' : 'Pendientes' }}
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

                <p v-if="channel.lastError" class="mt-4 rounded bg-danger/10 px-3 py-2 text-sm text-danger">
                    Último error: {{ channel.lastError }}
                </p>

                <p v-if="expiredGrant" class="mt-3 rounded bg-warning/10 px-3 py-2 text-sm text-warning">
                    La autorización caducó o fue revocada. Es lo normal cuando la app de Google está en modo
                    <strong>Prueba</strong> (el permiso dura 7 días): publícala en <em>En producción</em> o usa una app interna, y
                    vuelve a pulsar «Continuar con Google».
                </p>

                <span class="mt-3 block text-xs text-gray-500">
                    La reconciliación automática corre cada {{ reconcileMinutes }} minutos.
                </span>
            </div>

            <!-- Puesta en marcha (una sola vez) -->
            <div v-if="!credentialsReady" class="panel">
                <h6 class="text-base font-semibold dark:text-white-light">Registra la credencial una sola vez</h6>
                <p class="mt-1 text-sm text-gray-500">
                    Google exige que la aplicación tenga una credencial propia (Client ID y Client Secret) antes de poder
                    conectar un calendario. Se registra <strong>una sola vez</strong> en Parámetros del sistema
                    ({{ channel.parameterCodes?.client_id }} y {{ channel.parameterCodes?.client_secret }}) y no se vuelve a mostrar.
                    Después, cualquier administrador solo pulsa «Continuar con Google».
                </p>

                <ol class="mt-3 list-decimal pl-5 text-sm text-gray-600 dark:text-white-light">
                    <li>
                        En <strong>Google Cloud Console</strong> crea un proyecto y habilita <strong>Google Calendar API</strong>
                        (APIs y servicios &gt; Biblioteca).
                    </li>
                    <li>
                        En <strong>Credenciales</strong> crea un <em>ID de cliente de OAuth</em> de tipo
                        <strong>Aplicación web</strong>.
                    </li>
                    <li>
                        Agrega esta URL como <strong>URI de redireccionamiento autorizado</strong>:
                        <div class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-center">
                            <code class="break-all rounded bg-gray-100 px-2 py-1 dark:bg-gray-700">{{ redirectUri || '—' }}</code>
                            <button type="button" class="btn btn-sm btn-outline-primary" @click="copyRedirectUri">Copiar</button>
                        </div>
                    </li>
                    <li>Registra el <strong>Client ID</strong> y el <strong>Client Secret</strong> en Parámetros del sistema.</li>
                    <li>
                        Si la pantalla de consentimiento queda en modo <em>Prueba</em>, el permiso caduca a los 7 días:
                        publícala (<em>En producción</em>) o usa una app <em>Interna</em> para conectarla una sola vez.
                    </li>
                </ol>

                <a :href="parametersUrl" class="btn btn-primary mt-4 inline-block">Ir a Parámetros del sistema</a>
            </div>

            <div v-else class="panel">
                <h6 class="text-base font-semibold dark:text-white-light">Credenciales de la aplicación ✓</h6>
                <p class="mt-1 text-sm text-gray-500">
                    El Client ID y el Client Secret ({{ channel.parameterCodes?.client_id }} y
                    {{ channel.parameterCodes?.client_secret }}) ya están registrados y no se vuelven a mostrar. Si necesitas
                    reemplazarlos, hazlo desde
                    <a :href="parametersUrl" class="underline">Parámetros del sistema</a>.
                </p>
            </div>

            <!-- Diagnóstico -->
            <div v-if="diagnostics.length" ref="diagnosticsPanel" class="panel">
                <div class="flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
                    <h6 class="text-base font-semibold dark:text-white-light">Diagnóstico de la conexión</h6>
                    <span v-if="diagnosticsAt" class="text-xs text-gray-500">Última prueba: {{ diagnosticsAt }}</span>
                </div>

                <div class="mt-4 flex flex-col gap-3">
                    <div
                        v-for="check in diagnostics"
                        :key="check.code"
                        class="rounded border p-3 text-sm"
                        :class="check.ok ? 'border-success/40 bg-success/5' : 'border-danger/40 bg-danger/5'"
                    >
                        <span class="mr-2 font-semibold" :class="check.ok ? 'text-success' : 'text-danger'">
                            {{ check.ok ? '✓' : '✕' }} {{ check.label }}
                        </span>
                        <span class="text-gray-700 dark:text-white-light">{{ check.message }}</span>
                        <span v-if="check.hint" class="mt-1 block text-xs text-warning">{{ check.hint }}</span>
                    </div>
                </div>
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

            <!-- Textos del evento -->
            <div class="panel">
                <h6 class="text-base font-semibold dark:text-white-light">Cómo se verán los eventos</h6>
                <p class="mt-1 text-sm text-gray-500">
                    Arma el título y la descripción con los datos de la cita. Se guardan en los parámetros
                    {{ eventTemplate?.codes?.title }} y {{ eventTemplate?.codes?.description }}.
                    Google Calendar no interpreta HTML en el evento creado por API: el texto se envía plano (los saltos de línea sí
                    se respetan) y las etiquetas se quitan al enviar.
                </p>

                <div class="mt-4 grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="flex flex-col gap-4">
                        <div>
                            <div class="flex items-center justify-between">
                                <label class="text-sm font-medium dark:text-white-light" for="event-title-template">
                                    Título del evento
                                </label>
                                <span class="text-xs text-gray-500">
                                    {{ templates.title.length }} / {{ eventTemplate?.limits?.title ?? 255 }}
                                </span>
                            </div>
                            <input
                                id="event-title-template"
                                ref="titleInput"
                                v-model="templates.title"
                                type="text"
                                class="form-input mt-1"
                                @focus="activeField = 'title'"
                            />
                            <p class="mt-1 text-xs text-gray-500">
                                Una sola línea. Si queda vacío se envía el texto de fábrica.
                            </p>
                        </div>

                        <div>
                            <div class="flex items-center justify-between">
                                <label class="text-sm font-medium dark:text-white-light" for="event-description-template">
                                    Descripción del evento
                                </label>
                                <span class="text-xs text-gray-500">
                                    {{ templates.description.length }} / {{ eventTemplate?.limits?.template ?? 2000 }}
                                </span>
                            </div>
                            <textarea
                                id="event-description-template"
                                ref="descriptionInput"
                                v-model="templates.description"
                                rows="7"
                                class="form-textarea mt-1"
                                @focus="activeField = 'description'"
                            ></textarea>
                            <p class="mt-1 text-xs text-gray-500">
                                Una línea por dato: las líneas cuyos valores queden vacíos no se envían.
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" class="btn btn-outline-primary" :disabled="templateBusy" @click="runPreview">
                                Vista previa
                            </button>
                            <button
                                type="button"
                                class="btn btn-primary"
                                :disabled="templateBusy || !templatesChanged"
                                @click="saveTemplates"
                            >
                                Guardar
                            </button>
                            <button type="button" class="btn btn-outline-secondary" :disabled="templateBusy" @click="restoreDefaults">
                                Textos de fábrica
                            </button>
                        </div>

                        <p v-if="templateMessage" class="text-sm text-gray-600 dark:text-white-light">{{ templateMessage }}</p>
                    </div>

                    <div class="flex flex-col gap-4">
                        <div>
                            <span class="text-sm font-medium dark:text-white-light">Variables</span>
                            <p class="mt-1 text-xs text-gray-500">
                                Un clic inserta la variable en el campo donde tengas el cursor.
                            </p>

                            <div class="mt-2 flex flex-col gap-3">
                                <div v-for="group in variableGroups" :key="group.group">
                                    <span class="block text-xs uppercase text-gray-400">{{ group.group }}</span>
                                    <div class="mt-1 flex flex-wrap gap-1">
                                        <button
                                            v-for="variable in group.items"
                                            :key="variable.token"
                                            type="button"
                                            class="badge bg-primary/10 text-primary hover:bg-primary/20"
                                            :title="`${variable.label}. Ejemplo: ${variable.example}`"
                                            @click="insertVariable(variable.token)"
                                        >
                                            {{ '{' + variable.token + '}' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="preview" class="rounded border border-gray-200 p-3 dark:border-gray-600">
                            <span class="block text-xs uppercase text-gray-400">Vista previa ({{ previewSource }})</span>

                            <div class="mt-2 rounded bg-gray-100 p-3 dark:bg-gray-800">
                                <span class="block text-sm font-semibold dark:text-white-light">
                                    {{ previewLine(preview.title) }}
                                </span>
                                <pre class="mt-2 whitespace-pre-wrap text-xs text-gray-600 dark:text-gray-300">{{ previewLine(preview.description) }}</pre>
                            </div>

                            <ul v-if="preview.warnings?.length" class="mt-2 list-disc pl-5 text-xs text-warning">
                                <li v-for="(warning, index) in preview.warnings" :key="index">{{ warning }}</li>
                            </ul>
                        </div>
                    </div>
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
