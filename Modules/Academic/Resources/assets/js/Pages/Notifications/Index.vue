<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import InputError from '@/Components/InputError.vue';
import Swal2 from 'sweetalert2';
import axios from 'axios';

/**
 * Notificaciones masivas de un programa de especializacion.
 *
 * El envio real ocurre en la cola (un mensaje cada 280 ms), asi que aqui solo
 * se prepara la campana y se sigue su avance por sondeo: si se cierra el aviso
 * o se sale de la pantalla, el proceso continua y al volver se retoma la
 * campana que quedo en curso.
 */
const props = defineProps({
    courses: { type: Array, default: () => [] },
    channels: { type: Object, default: () => ({ vonage: false, whatsapp: false, telegram: false }) },
    // Usuario publico del bot de Telegram (@) cuando el canal esta habilitado.
    telegramBotUsername: { type: String, default: null },
    // Enlace unico de registro del bot (el mismo para todos los alumnos).
    telegramLink: { type: String, default: null },
    // Textos configurables del bot (catalogo con su valor vigente y default).
    telegramMessages: { type: Array, default: () => [] },
    timeSuggestions: { type: Array, default: () => [] },
    countryCode: { type: String, default: '51' },
    intervalMs: { type: Number, default: 280 },
    // Costo por SMS a Peru (USD) configurado en el modulo.
    smsPricePeru: { type: Number, default: 0 },
    activeCampaign: { type: Object, default: null },
});

/** Frecuencia del sondeo del avance (ms). */
const POLL_MS = 1500;

const form = ref({
    course_id: '',
    channel: props.channels.vonage
        ? 'sms'
        : props.channels.whatsapp
          ? 'whatsapp'
          : props.channels.telegram
            ? 'telegram'
            : '',
    is_test: false,
    message: '',
    time_label: '',
    test_numbers: '',
});

const errors = ref({});
const audience = ref(null);
const loadingAudience = ref(false);
const submitting = ref(false);

const campaign = ref(props.activeCampaign);
const progressVisible = ref(Boolean(props.activeCampaign));
const widgetHidden = ref(false);

let pollTimer = null;

const availableChannels = computed(() => {
    const list = [];

    if (props.channels.vonage) {
        list.push({
            value: 'sms',
            label: 'SMS vía Vonage',
            description: 'Mensaje de texto por la Messages API de Vonage (parametro SC-00001).',
        });
    }

    if (props.channels.whatsapp) {
        list.push({
            value: 'whatsapp',
            label: 'WhatsApp',
            description: 'Inicia el flujo configurado en Plantillas / Flujos.',
        });
    }

    if (props.channels.telegram) {
        list.push({
            value: 'telegram',
            label: 'Telegram',
            description: 'Bot de Telegram (parámetro SC-00002): llega solo a los alumnos que registraron su chat_id.',
        });
    }

    return list;
});

/** Nombre visible del canal de una campana. */
const channelNames = { sms: 'SMS vía Vonage', whatsapp: 'WhatsApp', telegram: 'Telegram' };
const channelName = (value) => channelNames[value] ?? value;

const hasChannel = computed(() => availableChannels.value.length > 0);

const channelLabel = computed(
    () => availableChannels.value.find((channel) => channel.value === form.value.channel)?.label ?? '—'
);

const selectedCourse = computed(
    () => props.courses.find((course) => String(course.id) === String(form.value.course_id))?.description ?? ''
);

/**
 * Numeros del modo prueba detectados en el input, con la misma regla que el
 * backend: cada uno debe venir completo con su codigo de pais.
 */
const testParsed = computed(() => {
    const valid = [];
    const invalid = [];

    // Igual que el backend: se separa por coma y los espacios internos no
    // parten la entrada ("+51 944 614 034" es un solo numero).
    (form.value.test_numbers.split(/[,;\n\r]+/) ?? []).forEach((raw) => {
        const token = raw.trim();

        if (token === '') {
            return;
        }

        let clean = token.replace(/\D+/g, '');

        if (clean.startsWith('00')) {
            clean = clean.substring(2);
        }

        if (clean.length < 8 || clean.length > 15) {
            invalid.push(token);
            return;
        }

        if (!valid.includes(clean)) {
            valid.push(clean);
        }
    });

    return { valid, invalid };
});

/** Total estimado de mensajes: numeros de prueba o audiencia del programa. */
const estimatedTotal = computed(() =>
    form.value.is_test ? testParsed.value.valid.length : audience.value?.total ?? 0
);

/** Tarifa unitaria del SMS en Peru, con los decimales que publica Vonage. */
const smsPriceLabel = computed(() => Number(props.smsPricePeru || 0).toFixed(5));

/** Costo referencial de la campana con la tarifa de Peru. */
const estimatedCost = computed(() => (estimatedTotal.value * Number(props.smsPricePeru || 0)).toFixed(2));

/** Texto exacto que saldria por SMS (mensaje + curso + tiempo). */
const smsPreview = computed(() =>
    [
        form.value.message.trim(),
        selectedCourse.value ? `Curso: ${selectedCourse.value}` : null,
        form.value.time_label.trim() ? `Tiempo: ${form.value.time_label.trim()}` : null,
    ]
        .filter(Boolean)
        .join('\n')
);

const smsLength = computed(() => smsPreview.value.length);

const smsTooLong = computed(() => form.value.channel === 'sms' && smsLength.value > 160);

const campaignActive = computed(() => campaign.value && !['completed', 'failed'].includes(campaign.value.status));

const progressPercent = computed(() => campaign.value?.percent ?? 0);

const showWidget = computed(() => Boolean(campaign.value) && !widgetHidden.value && !progressVisible.value);

const notify = (title, text, icon) => {
    Swal2.fire({ title, text, icon, padding: '2em', customClass: 'sweet-alerts' });
};

/**
 * Enlace unico de registro del bot (t.me/<bot>): es el mismo para todos, lo que
 * cambia es el documento que cada alumno escribe en el chat.
 */
const registrationLink = ref(props.telegramLink);
const loadingTelegramLink = ref(false);
const registeringWebhook = ref(false);

/** Copia editable de los textos del bot (se guarda al pulsar Guardar). */
const messagesDraft = ref([]);
const messagesVisible = ref(false);
const savingMessages = ref(false);
const messageInputs = {};

/** Vista previa del aviso de Telegram, armada en el servidor. */
const telegramPreview = ref('');
const telegramPreviewHtml = ref(true);
let previewTimer = null;

const fetchAudience = async () => {
    audience.value = null;

    // En modo prueba no se consulta la audiencia del programa: se envia
    // unicamente a los numeros escritos a mano.
    if (form.value.is_test || !form.value.course_id) {
        return;
    }

    loadingAudience.value = true;

    try {
        // El canal cambia el padron: Telegram cuenta por chat_id registrado.
        const { data } = await axios.post(route('aca_notifications_audience'), {
            course_id: form.value.course_id,
            channel: form.value.channel,
        });
        audience.value = data;
    } catch (error) {
        errors.value.course_id = error.response?.data?.message || 'No se pudo calcular la audiencia.';
    } finally {
        loadingAudience.value = false;
    }
};

/**
 * Abre el modal de textos: usa el catalogo que ya vino con la pantalla y, si
 * por algun motivo viene vacio, lo consulta al servidor.
 */
const openTelegramMessages = async () => {
    messagesDraft.value = props.telegramMessages.map((message) => ({ ...message }));
    messagesVisible.value = true;

    if (!messagesDraft.value.length) {
        await loadTelegramMessages();
    }
};

const applyMessages = (data) => {
    messagesDraft.value = (data.messages ?? []).map((message) => ({ ...message }));
};

const loadTelegramMessages = async () => {
    try {
        const { data } = await axios.get(route('aca_notifications_telegram_messages'));
        applyMessages(data);
    } catch (error) {
        notify('Error', error.response?.data?.message || 'No se pudieron cargar los textos del bot.', 'error');
    }
};

const saveTelegramMessages = async () => {
    savingMessages.value = true;

    try {
        const { data } = await axios.post(route('aca_notifications_telegram_messages_save'), {
            messages: messagesDraft.value.map(({ code, body, format }) => ({ code, body, format })),
        });

        applyMessages(data);
        notify('Textos guardados', data.message, 'success');
        scheduleTelegramPreview();
    } catch (error) {
        if (error.response?.status === 422) {
            const first = Object.values(error.response.data.errors ?? {})[0];
            notify('Revisa los textos', first?.[0] ?? 'Hay textos con problemas.', 'warning');
        } else {
            notify('Error', error.response?.data?.message || 'No se pudieron guardar los textos.', 'error');
        }
    } finally {
        savingMessages.value = false;
    }
};

/** Restaura un texto al valor de fabrica. */
const resetTelegramMessage = async (code) => {
    try {
        const { data } = await axios.post(route('aca_notifications_telegram_messages_reset'), { code });

        applyMessages(data);
        notify('Texto restaurado', data.message, 'success');
        scheduleTelegramPreview();
    } catch (error) {
        notify('Error', error.response?.data?.message || 'No se pudo restaurar el texto.', 'error');
    }
};

/** Restaura todos los textos al valor de fabrica. */
const resetTelegramMessages = async () => {
    const confirmation = await Swal2.fire({
        title: 'Restaurar todos los textos',
        text: 'Todos los mensajes del bot volverán al texto de fábrica.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, restaurar',
        cancelButtonText: 'Cancelar',
        padding: '2em',
        customClass: 'sweet-alerts',
    });

    if (!confirmation.isConfirmed) {
        return;
    }

    try {
        const { data } = await axios.post(route('aca_notifications_telegram_messages_reset'), {});

        applyMessages(data);
        notify('Textos restaurados', data.message, 'success');
        scheduleTelegramPreview();
    } catch (error) {
        notify('Error', error.response?.data?.message || 'No se pudieron restaurar los textos.', 'error');
    }
};

const registerMessageInput = (code, element) => {
    if (element) {
        messageInputs[code] = element;
    }
};

/** Inserta una variable en la posicion del cursor del textarea. */
const insertVariable = (code, variable) => {
    const message = messagesDraft.value.find((item) => item.code === code);

    if (!message) {
        return;
    }

    const input = messageInputs[code];
    const start = input && typeof input.selectionStart === 'number' ? input.selectionStart : message.body.length;
    const end = input && typeof input.selectionEnd === 'number' ? input.selectionEnd : start;

    message.body = message.body.slice(0, start) + variable + message.body.slice(end);

    nextTick(() => {
        if (!input) {
            return;
        }

        input.focus();
        input.setSelectionRange(start + variable.length, start + variable.length);
    });
};

/**
 * Vista previa del mensaje de campana por Telegram.
 *
 * Se pide al servidor porque el texto sale de una plantilla configurable: asi lo
 * que se ve es exactamente lo que recibira el alumno (el nombre es de ejemplo).
 */
const fetchTelegramPreview = async () => {
    if (form.value.channel !== 'telegram') {
        telegramPreview.value = '';
        return;
    }

    try {
        const { data } = await axios.post(route('aca_notifications_telegram_preview'), {
            course_id: form.value.course_id || null,
            message: form.value.message,
            time_label: form.value.time_label,
        });

        telegramPreview.value = data.text ?? '';
        telegramPreviewHtml.value = Boolean(data.html);
    } catch (error) {
        telegramPreview.value = '';
    }
};

/** La vista previa espera a que el administrador termine de escribir. */
const scheduleTelegramPreview = () => {
    if (previewTimer) {
        clearTimeout(previewTimer);
    }

    previewTimer = setTimeout(fetchTelegramPreview, 400);
};

/**
 * Trae el enlace unico de registro (refrescando el usuario del bot en Telegram).
 *
 * El enlace no depende del programa: se comparte el mismo con los alumnos,
 * tambien cuando la pantalla cambia de programa.
 */
const fetchTelegramLink = async () => {
    errors.value = {};
    loadingTelegramLink.value = true;

    try {
        const { data } = await axios.get(route('aca_notifications_telegram_link'), {
            params: { course_id: form.value.course_id || null },
        });

        registrationLink.value = data.link ?? registrationLink.value;
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors ?? {};
            notify('Revisa el formulario', Object.values(errors.value)[0]?.[0] ?? 'Hay datos incompletos.', 'warning');
        } else {
            notify('Error', error.response?.data?.message || 'No se pudo obtener el enlace de registro.', 'error');
        }
    } finally {
        loadingTelegramLink.value = false;
    }
};

/** Alta del webhook del bot en Telegram (apunta a este sistema). */
const registerTelegramWebhook = async () => {
    registeringWebhook.value = true;

    try {
        const { data } = await axios.post(route('aca_notifications_telegram_webhook'));
        notify('Webhook registrado', `Telegram enviará los mensajes al bot a: ${data.url}`, 'success');
    } catch (error) {
        notify(
            'Error',
            error.response?.data?.errors?.webhook?.[0] || error.response?.data?.message || 'No se pudo registrar el webhook.',
            'error'
        );
    } finally {
        registeringWebhook.value = false;
    }
};

/** Copia un texto al portapapeles (con aviso al usuario). */
const copyToClipboard = async (text, message) => {
    try {
        await navigator.clipboard.writeText(text);
        notify('Copiado', message, 'success');
    } catch (error) {
        notify('No se pudo copiar', 'Copia el texto manualmente desde el listado.', 'warning');
    }
};

const stopPolling = () => {
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
};

const poll = async () => {
    if (!campaign.value) {
        return;
    }

    try {
        const { data } = await axios.get(route('aca_notifications_progress', campaign.value.id));
        campaign.value = data;

        if (!campaignActive.value) {
            stopPolling();

            if (data.status === 'completed') {
                notify(
                    'Envío finalizado',
                    `${data.sent} enviado(s) y ${data.failed} fallido(s) de ${data.total}.`,
                    data.failed > 0 ? 'warning' : 'success'
                );
            } else {
                notify('Envío interrumpido', data.error_message || 'La campaña no pudo completarse.', 'error');
            }
        }
    } catch (error) {
        // Un fallo puntual del sondeo no cancela la campana: se reintenta luego.
    }
};

const startPolling = () => {
    stopPolling();
    poll();

    if (campaignActive.value) {
        pollTimer = setInterval(poll, POLL_MS);
    }
};

const submit = async () => {
    errors.value = {};

    if (!form.value.channel) {
        errors.value.channel = 'Elige el canal de envío.';
        return;
    }

    if (form.value.channel === 'whatsapp' && !form.value.time_label.trim()) {
        errors.value.time_label = 'Indica el tiempo para el flujo de WhatsApp.';
        return;
    }

    if (form.value.channel === 'telegram') {
        if (form.value.is_test) {
            errors.value.is_test = 'El modo prueba solo aplica a SMS y WhatsApp.';
            return;
        }

        if (audience.value && audience.value.total === 0) {
            errors.value.course_id =
                'Ningún alumno de este programa tiene su chat_id de Telegram registrado todavía. Genera los enlaces de registro y compártelos.';
            return;
        }
    }

    if (form.value.is_test) {
        if (testParsed.value.valid.length === 0 && testParsed.value.invalid.length === 0) {
            errors.value.test_numbers = 'Escribe al menos un número de prueba.';
            return;
        }

        if (testParsed.value.invalid.length > 0) {
            errors.value.test_numbers =
                'Revisa estos números, deben ir completos con el código de país: ' + testParsed.value.invalid.join(', ');
            return;
        }
    } else if (!form.value.course_id) {
        errors.value.course_id = 'Elige el programa de especialización.';
        return;
    }

    const confirmation = await Swal2.fire({
        title: form.value.is_test ? 'Enviar notificaciones de prueba' : 'Enviar notificaciones',
        html:
            `Se enviarán <b>${estimatedTotal.value}</b> mensaje(s) por <b>${channelLabel.value}</b>` +
            ` cada ${props.intervalMs} ms.` +
            (form.value.is_test
                ? '<br><br>Es un <b>envío de prueba</b>: solo llegarán a los números que escribiste.'
                : '') +
            '<br><br><b>Este proceso continuará aunque salgas.</b>',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, enviar',
        cancelButtonText: 'Cancelar',
        padding: '2em',
        customClass: 'sweet-alerts',
    });

    if (!confirmation.isConfirmed) {
        return;
    }

    submitting.value = true;

    try {
        const { data } = await axios.post(route('aca_notifications_store'), {
            course_id: form.value.course_id || null,
            channel: form.value.channel,
            is_test: form.value.is_test,
            message: form.value.message,
            time_label: form.value.time_label,
            test_numbers: form.value.test_numbers,
        });

        campaign.value = data.campaign;
        widgetHidden.value = false;
        progressVisible.value = true;
        startPolling();

        Swal2.fire({
            title: 'Envío encolado',
            text: data.message,
            icon: 'success',
            timer: 3000,
            showConfirmButton: false,
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors ?? {};
            notify('Revisa el formulario', Object.values(errors.value)[0]?.[0] ?? 'Hay datos incompletos.', 'warning');
        } else {
            notify('Error', error.response?.data?.message || 'No se pudo iniciar el envío.', 'error');
        }
    } finally {
        submitting.value = false;
    }
};

const closeProgress = () => {
    progressVisible.value = false;
};

const openProgress = () => {
    widgetHidden.value = false;
    progressVisible.value = true;
};

const hideWidget = () => {
    widgetHidden.value = true;
};

watch(
    () => form.value.course_id,
    () => fetchAudience()
);

// Al activar el modo prueba se ignora la audiencia del programa (y al
// desactivarlo se vuelve a calcular para el programa elegido).
watch(
    () => form.value.is_test,
    (isTest) => {
        audience.value = null;
        errors.value = {};

        if (!isTest) {
            fetchAudience();
        }
    }
);

// Cambiar de canal cambia el padron (Telegram cuenta por chat_id) y descarta
// el modo prueba, que no aplica a Telegram.
watch(
    () => form.value.channel,
    (channel) => {
        errors.value = {};

        if (channel === 'telegram' && form.value.is_test) {
            form.value.is_test = false;
        }

        if (channel === 'telegram' && !registrationLink.value) {
            fetchTelegramLink();
        }

        fetchAudience();
    }
);

// La vista previa de Telegram depende del mensaje, el curso y el tiempo: se
// recalcula (con una pequeña espera) cada vez que cambian.
watch(
    () => [form.value.channel, form.value.message, form.value.time_label, form.value.course_id],
    () => {
        if (form.value.channel === 'telegram') {
            scheduleTelegramPreview();
        } else {
            telegramPreview.value = '';
        }
    }
);

onMounted(() => {
    if (!availableChannels.value.some((channel) => channel.value === form.value.channel)) {
        form.value.channel = availableChannels.value[0]?.value ?? '';
    }

    if (form.value.channel === 'telegram') {
        if (!registrationLink.value) {
            fetchTelegramLink();
        }

        scheduleTelegramPreview();
    }

    if (campaign.value) {
        startPolling();
    }
});

onBeforeUnmount(() => {
    stopPolling();

    if (previewTimer) {
        clearTimeout(previewTimer);
        previewTimer = null;
    }
});
</script>

<template>
    <AppLayout title="Notificaciones">
        <Navigation
            :routeModule="route('aca_dashboard')"
            :titleModule="'Académico'"
            :data="[{ title: 'Notificaciones' }]"
        />

        <div class="pt-5">
            <div class="mb-5">
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Notificaciones masivas</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Envía un aviso a los alumnos matriculados en un programa de especialización y a quienes tienen
                    suscripción activa y vigente hoy. Los mensajes salen en segundo plano, uno cada
                    {{ intervalMs }} ms.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <!-- Formulario de la campaña -->
                <div class="panel lg:col-span-2">
                    <div class="mb-5">
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Programa de especialización
                            <span v-if="form.is_test" class="font-normal text-gray-400">(opcional en modo prueba)</span>
                        </label>
                        <select v-model="form.course_id" class="form-select w-full">
                            <option value="">Selecciona un programa...</option>
                            <option v-for="course in courses" :key="course.id" :value="course.id">
                                {{ course.description }}
                            </option>
                        </select>
                        <InputError :message="errors.course_id?.[0] ?? errors.course_id" class="mt-1" />
                        <p v-if="!courses.length" class="mt-2 text-xs text-amber-600 dark:text-amber-400">
                            No hay cursos de tipo "Programas de Especialización" registrados.
                        </p>
                    </div>

                    <!-- Modo prueba -->
                    <div class="mb-5 rounded-lg border border-dashed border-gray-300 p-4 dark:border-zinc-700">
                        <label
                            class="flex items-center gap-2 text-sm font-medium"
                            :class="form.channel === 'telegram' ? 'text-gray-400 dark:text-gray-500' : 'text-gray-700 dark:text-gray-300'"
                        >
                            <input
                                v-model="form.is_test"
                                type="checkbox"
                                class="form-checkbox"
                                :disabled="form.channel === 'telegram'"
                            />
                            <span>Modo prueba (enviar solo a números específicos)</span>
                        </label>
                        <p v-if="form.channel === 'telegram'" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            No aplica a Telegram: ese canal envía al chat_id que cada alumno registró con el bot.
                        </p>
                        <InputError :message="errors.is_test?.[0] ?? errors.is_test" class="mt-1" />

                        <div v-if="form.is_test" class="mt-3">
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Números de prueba
                            </label>
                            <input
                                v-model="form.test_numbers"
                                type="text"
                                class="form-input w-full"
                                placeholder="51944614034, 51943781122, 5298765432156"
                            />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Escribe los números completos, con su código de país, separados por coma. Para el envío se
                                usan tal cual: no se les agrega ni quita ningún código.
                            </p>

                            <p class="mt-2 text-xs font-medium text-primary">
                                {{ testParsed.valid.length }} número(s) detectado(s)
                            </p>
                            <p v-if="testParsed.invalid.length" class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                                Revisa: {{ testParsed.invalid.join(', ') }} (incompletos o sin código de país).
                            </p>

                            <InputError :message="errors.test_numbers?.[0] ?? errors.test_numbers" class="mt-1" />
                        </div>
                    </div>

                    <!-- Audiencia estimada -->
                    <div v-if="form.is_test" class="mb-5 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
                        <b>Modo prueba.</b> El mensaje se enviará únicamente a los
                        {{ testParsed.valid.length }} número(s) de prueba indicado(s), no a los alumnos del programa.
                        El programa, si lo eliges, solo aporta el nombre del curso.
                    </div>

                    <div v-else-if="loadingAudience" class="mb-5 text-sm text-gray-500 dark:text-gray-400">
                        Calculando la audiencia...
                    </div>

                    <div
                        v-else-if="audience"
                        class="mb-5 rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm dark:border-zinc-700 dark:bg-zinc-800"
                    >
                        <p class="font-medium text-gray-700 dark:text-gray-200">Audiencia de esta campaña</p>
                        <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-4">
                            <div>
                                <span class="block text-lg font-semibold text-gray-900 dark:text-white">{{ audience.program }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">Del programa</span>
                            </div>
                            <div>
                                <span class="block text-lg font-semibold text-gray-900 dark:text-white">{{ audience.subscriptions }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">Suscripción activa</span>
                            </div>
                            <div>
                                <span class="block text-lg font-semibold text-primary">{{ audience.total }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">Mensajes a enviar</span>
                            </div>
                            <div>
                                <span class="block text-lg font-semibold text-gray-900 dark:text-white">{{ audience.skipped }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">Quedan fuera</span>
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            <template v-if="form.channel === 'telegram'">
                                "Quedan fuera": alumnos que todavía no registraron su chat de Telegram con el bot.
                            </template>
                            <template v-else>
                                "Quedan fuera": sin teléfono válido, teléfono repetido o personas que ya estaban en el otro grupo.
                            </template>
                        </p>
                    </div>

                    <!-- Canal -->
                    <div class="mb-5">
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Canal de envío
                        </label>

                        <div v-if="hasChannel" class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <button
                                v-for="channel in availableChannels"
                                :key="channel.value"
                                type="button"
                                class="rounded-lg border p-4 text-left transition"
                                :class="
                                    form.channel === channel.value
                                        ? 'border-primary bg-primary/10 dark:bg-primary/20'
                                        : 'border-gray-200 hover:border-primary/60 dark:border-zinc-700'
                                "
                                @click="form.channel = channel.value"
                            >
                                <span class="block text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ channel.label }}
                                </span>
                                <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">
                                    {{ channel.description }}
                                </span>
                            </button>
                        </div>

                        <div
                            v-else
                            class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300"
                        >
                            No hay ningún canal disponible. Configura las credenciales de Vonage en el parámetro
                            <b>SC-00001</b> (Parámetros del sistema) para habilitar el SMS, el ID del flujo en
                            <b>Plantillas / Flujos</b> para habilitar WhatsApp, o el token del bot en el parámetro
                            <b>SC-00002</b> para habilitar Telegram.
                        </div>

                        <InputError :message="errors.channel?.[0] ?? errors.channel" class="mt-1" />
                    </div>

                    <!-- Registro de chat_id de Telegram -->
                    <div
                        v-if="form.channel === 'telegram'"
                        class="mb-5 rounded-lg border border-sky-300 bg-sky-50 p-4 text-sm dark:border-sky-700 dark:bg-sky-900/20"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="text-gray-700 dark:text-gray-200">
                                <p class="font-medium text-gray-900 dark:text-white">
                                    Registro de Telegram
                                    <span v-if="telegramBotUsername" class="font-normal text-gray-500 dark:text-gray-400">
                                        (bot @{{ telegramBotUsername }})
                                    </span>
                                </p>
                                <p class="mt-1">
                                    Solo reciben por Telegram los alumnos que registraron su chat con el bot.
                                    <template v-if="audience">
                                        <b>{{ audience.total }}</b> del padrón ya están registrados y
                                        <b>{{ audience.skipped }}</b> todavía no.
                                    </template>
                                    <b>El enlace es el mismo para todos</b>: compártelo y cada alumno escribe su número
                                    de documento en el chat.
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    class="btn btn-outline-primary"
                                    :disabled="loadingTelegramLink"
                                    @click="fetchTelegramLink"
                                >
                                    {{ loadingTelegramLink ? 'Consultando...' : 'Obtener enlace de registro' }}
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-outline-primary"
                                    :disabled="registeringWebhook"
                                    @click="registerTelegramWebhook"
                                >
                                    {{ registeringWebhook ? 'Registrando...' : 'Registrar webhook con Telegram' }}
                                </button>

                                <button type="button" class="btn btn-outline-primary" @click="openTelegramMessages">
                                    Configurar parámetros de Telegram
                                </button>
                            </div>
                        </div>

                        <div v-if="registrationLink" class="mt-3 flex flex-wrap items-center gap-2">
                            <input
                                :value="registrationLink"
                                type="text"
                                readonly
                                class="form-input w-full max-w-sm bg-white dark:bg-zinc-900"
                            />
                            <button
                                type="button"
                                class="btn btn-primary"
                                @click="copyToClipboard(registrationLink, 'Enlace de registro copiado. Es el mismo para todos los alumnos.')"
                            >
                                Copiar enlace
                            </button>
                        </div>

                        <p v-else class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                            Pulsa <b>Obtener enlace de registro</b> para consultar el usuario del bot en Telegram y armar
                            el enlace.
                        </p>

                        <ol class="mt-3 list-decimal space-y-1 pl-5 text-xs text-gray-500 dark:text-gray-400">
                            <li>Comparte el enlace con los alumnos: es el mismo para todos.</li>
                            <li>El alumno lo abre y pulsa <b>Iniciar</b>; el bot le pide su número de documento.</li>
                            <li>Si está en el padrón, el bot confirma y desde ahí recibe las notificaciones.</li>
                        </ol>

                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            El webhook necesita que este sistema sea accesible por HTTPS público: es la dirección a la que
                            Telegram entrega los mensajes que los alumnos le escriben al bot.
                        </p>
                        <InputError :message="errors.course_id?.[0] ?? errors.course_id" class="mt-1" />
                    </div>

                    <!-- Mensaje -->
                    <div class="mb-5">
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Mensaje
                        </label>
                        <textarea
                            v-model="form.message"
                            class="form-textarea w-full"
                            rows="4"
                            maxlength="480"
                            placeholder="Escribe el mensaje que recibirán los alumnos..."
                        ></textarea>
                        <div class="mt-1 flex items-center justify-between">
                            <InputError :message="errors.message?.[0] ?? errors.message" />
                            <span
                                class="text-xs"
                                :class="smsTooLong ? 'font-semibold text-amber-600 dark:text-amber-400' : 'text-gray-400'"
                            >
                                {{ form.message.length }}/480 caracteres
                            </span>
                        </div>
                    </div>

                    <!-- Tiempo -->
                    <div class="mb-5">
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Tiempo
                            <span v-if="form.channel === 'whatsapp'" class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.time_label"
                            type="text"
                            class="form-input w-full"
                            list="aca-notification-times"
                            maxlength="60"
                            placeholder="Ej: 15 minutos"
                        />
                        <datalist id="aca-notification-times">
                            <option v-for="suggestion in timeSuggestions" :key="suggestion" :value="suggestion"></option>
                        </datalist>
                        <InputError :message="errors.time_label?.[0] ?? errors.time_label" class="mt-1" />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Se envía como la variable <b>tiempo</b> del flujo de WhatsApp y se agrega al final del SMS y
                            del mensaje de Telegram.
                        </p>
                    </div>

                    <!-- Previsualización del SMS -->
                    <div
                        v-if="form.channel === 'sms' && smsPreview"
                        class="mb-5 rounded-lg border border-dashed border-gray-300 p-4 text-sm dark:border-zinc-700"
                    >
                        <p class="mb-1 text-xs font-medium text-gray-500 dark:text-gray-400">Vista previa del SMS</p>
                        <pre class="whitespace-pre-wrap font-sans text-gray-700 dark:text-gray-200">{{ smsPreview }}</pre>
                        <p v-if="smsTooLong" class="mt-2 text-xs text-amber-600 dark:text-amber-400">
                            Con {{ smsLength }} caracteres el SMS se enviará en varios segmentos.
                        </p>
                    </div>

                    <!-- Previsualización del Telegram: la arma el servidor con la plantilla configurable -->
                    <div
                        v-if="form.channel === 'telegram'"
                        class="mb-5 rounded-lg border border-dashed border-gray-300 p-4 text-sm dark:border-zinc-700"
                    >
                        <p class="mb-1 text-xs font-medium text-gray-500 dark:text-gray-400">
                            Vista previa del mensaje de Telegram
                            <span class="font-normal">(con la plantilla configurable y un nombre de ejemplo)</span>
                        </p>
                        <pre
                            v-if="telegramPreview"
                            class="whitespace-pre-wrap font-sans text-gray-700 dark:text-gray-200"
                        >{{ telegramPreview }}</pre>
                        <p v-else class="text-xs text-gray-400">Escribe el mensaje para ver la vista previa...</p>
                        <p v-if="telegramPreview && telegramPreviewHtml" class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            Las etiquetas como &lt;b&gt; se ven en negrita en Telegram; aquí se muestran escritas.
                        </p>
                    </div>

                    <p class="mb-4 text-xs text-gray-500 dark:text-gray-400">
                        El código de país de cada alumno se toma de su país registrado (incluido el país de los alumnos
                        extranjeros) y se evita duplicarlo cuando el número ya lo trae. Solo si el alumno no tiene país
                        registrado se asume {{ countryCode }}, y los números viajan siempre sin el símbolo "+".
                    </p>

                    <div class="flex flex-wrap items-center gap-3">
                        <button
                            type="button"
                            class="btn btn-primary"
                            :disabled="submitting || !hasChannel || campaignActive"
                            @click="submit"
                        >
                            {{ submitting ? 'Enviando...' : 'Enviar notificaciones' }}
                        </button>

                        <span v-if="campaignActive" class="text-sm text-gray-500 dark:text-gray-400">
                            Ya hay una campaña en curso (#{{ campaign.id }}): espera a que termine.
                        </span>
                    </div>
                </div>

                <!-- Aviso de continuidad -->
                <div class="panel">
                    <h2 class="mb-3 text-base font-semibold text-gray-900 dark:text-white">Cómo funciona</h2>
                    <ul class="space-y-3 text-sm text-gray-600 dark:text-gray-300">
                        <li>
                            Los mensajes se encolan y salen <b>uno cada {{ intervalMs }} ms</b> para no saturar el
                            servidor ni las APIs externas.
                        </li>
                        <li>
                            <b>Este proceso continuará aunque salgas</b> de la pantalla o cierres el aviso: se ejecuta en
                            la cola del servidor.
                        </li>
                        <li>Al volver a esta pantalla se retoma el avance de la campaña en curso.</li>
                        <li>Al finalizar se muestra cuántos mensajes se enviaron y cuáles fallaron.</li>
                    </ul>
                </div>
            </div>

            <!-- Pie de página: aviso de costos según el canal elegido -->
            <div v-if="hasChannel" class="panel mt-5">
                <div v-if="form.channel === 'sms'" class="flex items-start gap-3">
                    <span class="text-xl leading-none">💵</span>
                    <div class="text-sm">
                        <p class="font-medium text-gray-900 dark:text-white">Costo del SMS vía Vonage</p>
                        <p class="mt-1 text-gray-600 dark:text-gray-300">
                            Cada SMS enviado por Vonage cuesta <b>${{ smsPriceLabel }}</b> para números de Perú; en otros
                            países el precio varía según el destino.
                        </p>
                        <p v-if="estimatedTotal > 0" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Referencial con la tarifa de Perú (los números de otros países pueden costar distinto):
                            {{ estimatedTotal }} mensaje(s) ≈ <b>${{ estimatedCost }}</b>.
                        </p>
                    </div>
                </div>

                <div v-else-if="form.channel === 'whatsapp'" class="flex items-start gap-3">
                    <span class="text-xl leading-none">💬</span>
                    <div class="text-sm">
                        <p class="font-medium text-gray-900 dark:text-white">Costo del mensaje de WhatsApp</p>
                        <p class="mt-1 text-gray-600 dark:text-gray-300">
                            El costo por mensaje depende de tu proveedor de la API de WhatsApp: verifica la tarifa con tu
                            proveedor antes de enviar.
                        </p>
                    </div>
                </div>

                <div v-else-if="form.channel === 'telegram'" class="flex items-start gap-3">
                    <span class="text-xl leading-none">✈️</span>
                    <div class="text-sm">
                        <p class="font-medium text-gray-900 dark:text-white">Costo del mensaje de Telegram</p>
                        <p class="mt-1 text-gray-600 dark:text-gray-300">
                            La API de bots de Telegram no cobra por mensaje: solo hay límites de frecuencia de envío.
                        </p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Los alumnos que no registraron su chat con el bot no reciben el aviso.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de progreso -->
        <div v-if="progressVisible && campaign" class="fixed inset-0 z-[999] flex items-center justify-center bg-black/60 p-4">
            <div class="w-full max-w-xl rounded-lg bg-white p-6 shadow-lg dark:bg-[#0e1726]">
                <div class="mb-4 flex items-start justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ campaignActive ? 'Enviando notificaciones' : 'Resultado del envío' }}
                        </h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ channelName(campaign.channel) }} ·
                            {{ campaign.course || 'Sin programa seleccionado' }}
                        </p>
                        <span
                            v-if="campaign.is_test"
                            class="mt-2 inline-block rounded bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-300"
                        >
                            Modo prueba
                        </span>
                    </div>
                    <button type="button" class="text-2xl leading-none text-gray-400 hover:text-gray-600" @click="closeProgress">
                        &times;
                    </button>
                </div>

                <div
                    class="mb-4 rounded-lg border border-primary/30 bg-primary/10 p-3 text-sm font-medium text-primary dark:bg-primary/20"
                >
                    Este proceso continuará aunque salgas.
                </div>

                <div class="mb-2 flex items-center justify-between text-sm text-gray-600 dark:text-gray-300">
                    <span>Avance</span>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ progressPercent }}%</span>
                </div>
                <div class="mb-4 h-2.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-zinc-700">
                    <div
                        class="h-full rounded-full bg-primary transition-all duration-500"
                        :style="{ width: progressPercent + '%' }"
                    ></div>
                </div>

                <div class="mb-4 grid grid-cols-3 gap-3 text-center text-sm">
                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-zinc-800">
                        <span class="block text-lg font-semibold text-gray-900 dark:text-white">{{ campaign.sent }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Enviados</span>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-zinc-800">
                        <span class="block text-lg font-semibold text-red-500">{{ campaign.failed }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Fallidos</span>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-zinc-800">
                        <span class="block text-lg font-semibold text-gray-900 dark:text-white">{{ campaign.total }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Total</span>
                    </div>
                </div>

                <div v-if="campaignActive" class="mb-4 text-sm text-gray-600 dark:text-gray-300">
                    Enviando a:
                    <span class="font-semibold text-gray-900 dark:text-white">{{ campaign.current_phone || 'preparando el envío...' }}</span>
                </div>

                <div v-if="!campaignActive && campaign.errors?.length" class="mb-4 max-h-40 overflow-auto rounded-lg bg-gray-50 p-3 dark:bg-zinc-800">
                    <p class="mb-2 text-xs font-medium text-gray-500 dark:text-gray-400">Números con error</p>
                    <ul class="space-y-1 text-xs text-gray-600 dark:text-gray-300">
                        <li v-for="(item, index) in campaign.errors" :key="index">
                            <b>{{ item.phone || (item.chat_id ? 'Chat ' + item.chat_id : '—') }}</b> — {{ item.error }}
                        </li>
                    </ul>
                </div>

                <div class="flex flex-wrap justify-end gap-3">
                    <button v-if="campaignActive" type="button" class="btn btn-outline-primary" @click="closeProgress">
                        Cerrar aviso (continúa en segundo plano)
                    </button>
                    <button v-else type="button" class="btn btn-primary" @click="closeProgress">Cerrar</button>
                </div>
            </div>
        </div>

        <!-- Widget flotante: la campaña sigue viva aunque se cierre el aviso -->
        <div
            v-if="showWidget"
            class="fixed bottom-5 right-5 z-[998] w-72 rounded-lg border border-gray-200 bg-white p-4 shadow-lg dark:border-zinc-700 dark:bg-[#0e1726]"
        >
            <div class="mb-2 flex items-start justify-between gap-2">
                <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ campaignActive ? 'Envío en curso' : 'Envío finalizado' }}{{ campaign.is_test ? ' (prueba)' : '' }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ campaign.sent }} enviados · {{ campaign.failed }} fallidos de {{ campaign.total }}
                    </p>
                </div>
                <button type="button" class="text-lg leading-none text-gray-400 hover:text-gray-600" @click="hideWidget">
                    &times;
                </button>
            </div>

            <div class="mb-2 h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-zinc-700">
                <div class="h-full rounded-full bg-primary transition-all duration-500" :style="{ width: progressPercent + '%' }"></div>
            </div>

            <div class="flex items-center justify-between">
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ progressPercent }}%</span>
                <button type="button" class="text-xs font-medium text-primary hover:underline" @click="openProgress">
                    Ver detalle
                </button>
            </div>
        </div>

        <!-- Modal: textos configurables del bot de Telegram -->
        <div v-if="messagesVisible" class="fixed inset-0 z-[999] flex items-center justify-center bg-black/60 p-4">
            <div class="flex max-h-[88vh] w-full max-w-3xl flex-col rounded-lg bg-white p-6 shadow-lg dark:bg-[#0e1726]">
                <div class="mb-4 flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Configurar parámetros de Telegram
                        </h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Estos son los textos que envía el bot. Admiten emojis y las etiquetas de Telegram
                            (<b>&lt;b&gt;</b>, <b>&lt;i&gt;</b>, <b>&lt;u&gt;</b>, <b>&lt;s&gt;</b>, <b>&lt;code&gt;</b>,
                            <b>&lt;a href="..."&gt;</b>): puedes copiarlos directamente desde Telegram y pegarlos aquí.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="text-2xl leading-none text-gray-400 hover:text-gray-600"
                        @click="messagesVisible = false"
                    >
                        &times;
                    </button>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 pb-3 dark:border-zinc-800">
                    <p class="max-w-xl text-xs text-gray-500 dark:text-gray-400">
                        Una línea que usa una variable vacía no se envía: así desaparecen solos el curso, el tiempo o los
                        programas cuando no corresponden. Deja un texto vacío para volver al de fábrica.
                    </p>
                    <button type="button" class="btn btn-outline-primary" @click="resetTelegramMessages">
                        Restaurar todos
                    </button>
                </div>

                <div class="mt-4 max-h-[55vh] space-y-4 overflow-auto pr-1">
                    <div
                        v-for="message in messagesDraft"
                        :key="message.code"
                        class="rounded-lg border border-gray-200 p-4 dark:border-zinc-700"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ message.name }}
                                    <span
                                        v-if="message.is_customized"
                                        class="ml-1 rounded bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary"
                                    >
                                        Modificado
                                    </span>
                                </p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ message.description }}</p>
                            </div>

                            <button
                                type="button"
                                class="shrink-0 text-xs font-medium text-primary hover:underline"
                                @click="resetTelegramMessage(message.code)"
                            >
                                Restaurar
                            </button>
                        </div>

                        <div v-if="message.variables?.length" class="mt-2 flex flex-wrap items-center gap-1">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Variables:</span>
                            <button
                                v-for="variable in message.variables"
                                :key="variable"
                                type="button"
                                class="rounded bg-gray-100 px-2 py-0.5 font-mono text-xs text-gray-700 hover:bg-primary/10 dark:bg-zinc-800 dark:text-gray-200"
                                @click="insertVariable(message.code, variable)"
                            >
                                {{ variable }}
                            </button>
                        </div>

                        <textarea
                            :ref="(element) => registerMessageInput(message.code, element)"
                            v-model="message.body"
                            rows="6"
                            maxlength="4096"
                            class="form-textarea mt-2 w-full font-mono text-xs"
                        ></textarea>

                        <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
                            <select v-model="message.format" class="form-select w-auto text-xs">
                                <option value="html">HTML de Telegram (interpreta etiquetas)</option>
                                <option value="text">Texto plano (las etiquetas se ven escritas)</option>
                            </select>
                            <span class="text-xs text-gray-400">{{ (message.body || '').length }}/4096</span>
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap justify-end gap-3">
                    <button type="button" class="btn btn-outline-primary" @click="messagesVisible = false">Cerrar</button>
                    <button
                        type="button"
                        class="btn btn-primary"
                        :disabled="savingMessages || !messagesDraft.length"
                        @click="saveTelegramMessages"
                    >
                        {{ savingMessages ? 'Guardando...' : 'Guardar textos' }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
