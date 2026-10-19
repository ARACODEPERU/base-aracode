<script setup>
import { computed, reactive, ref } from 'vue';
import axios from 'axios';
import { router, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';

/**
 * Parámetros Telegram: canal de avisos de asistencia del colegio vía Bot de Telegram.
 *
 * Exclusivo para el rol admin. Permite configurar el Token / ID del bot de
 * Telegram (entregado por @BotFather), registrar el webhook con los comandos
 * (/registrar, /baja, /chatid, /info), ajustar los interruptores y plantillas
 * de entrada y salida, consultar la guía paso a paso y probar el envío.
 */
const props = defineProps({
    channel: { type: Object, default: () => ({}) },
    settings: { type: Object, default: () => ({}) },
    bot: { type: Object, default: () => ({}) },
    variables: { type: Array, default: () => [] },
    deliveries: { type: Array, default: () => [] },
    subscriptions: { type: Array, default: () => [] },
    parametersUrl: { type: String, default: '' },
});

const codes = computed(() => props.channel.parameterCodes || {});

const form = useForm({
    token: '',
    active: !!props.channel.active,
    notify_entry: !!props.channel.entry,
    notify_exit: !!props.channel.exit,
    notify_late: !!props.channel.late,
    notify_early_exit: !!props.channel.early_exit,
    primary_only: !!props.channel.primary_only,
    daily_limit: Number(props.settings.daily_limit || 0),
    interval_ms: Number(props.settings.interval_ms ?? 500),
    entry_message: props.settings.entry_message || '',
    exit_message: props.settings.exit_message || '',
});

const botUsername = ref(props.bot.username || null);
const botLink = ref(props.bot.link || null);
const webhookUrl = computed(() => props.bot.webhookUrl || '');
const botCommands = computed(() => props.bot.commands || []);

const showGuideModal = ref(false);
const registeringWebhook = ref(false);
const loadingLink = ref(false);
const previews = reactive({ in: '', out: '' });
const testing = ref(false);
const testForm = reactive({ chat_id: '', event: 'in' });

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

const copyToClipboard = async (text, title = 'Copiado al portapapeles') => {
    if (!text) return;

    try {
        await navigator.clipboard.writeText(text);
        Swal.fire({
            icon: 'success',
            title,
            timer: 1800,
            showConfirmButton: false,
            customClass: { popup: 'sweet-alerts' },
        });
    } catch {
        Swal.fire({
            icon: 'warning',
            title: 'No se pudo copiar automáticamente',
            text: 'Selecciona y copia el texto manualmente.',
            customClass: { popup: 'sweet-alerts', confirmButton: 'btn btn-primary' },
            buttonsStyling: false,
        });
    }
};

const preview = async (event, field) => {
    try {
        const { data } = await axios.post(route('aca_school_telegram_parameters_preview'), {
            event,
            message: form[field],
        });

        previews[event] = data.text;
    } catch {
        previews[event] = '';
    }
};

const save = async () => {
    form.post(route('aca_school_telegram_parameters_update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.token = '';
            Swal.fire({
                icon: 'success',
                title: 'Parámetros guardados',
                text: 'La configuración de Telegram se aplicará a los próximos avisos de asistencia.',
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

const registerWebhook = async () => {
    registeringWebhook.value = true;

    try {
        const { data } = await axios.post(route('aca_school_telegram_parameters_webhook'));
        if (data.username) botUsername.value = data.username;
        if (data.link) botLink.value = data.link;

        Swal.fire({
            icon: 'success',
            title: 'Webhook y comandos activados',
            text: `${data.message} URL: ${data.url}`,
            customClass: { popup: 'sweet-alerts', confirmButton: 'btn btn-success' },
            buttonsStyling: false,
        });
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'No se pudo registrar el webhook',
            text: firstError(error) || 'Verifica que el Token del bot esté guardado y que el servidor tenga HTTPS público.',
            customClass: { popup: 'sweet-alerts', confirmButton: 'btn btn-danger' },
            buttonsStyling: false,
        });
    } finally {
        registeringWebhook.value = false;
    }
};

const fetchBotLink = async () => {
    loadingLink.value = true;

    try {
        const { data } = await axios.get(route('aca_school_telegram_parameters_link'));
        botUsername.value = data.username || botUsername.value;
        botLink.value = data.link || botLink.value;

        Swal.fire({
            icon: 'success',
            title: 'Enlace del bot listo',
            text: data.message,
            timer: 2000,
            showConfirmButton: false,
            customClass: { popup: 'sweet-alerts' },
        });
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'No se pudo consultar el bot',
            text: firstError(error) || 'Revisa el Token del bot en el parámetro SC-00033.',
            customClass: { popup: 'sweet-alerts', confirmButton: 'btn btn-danger' },
            buttonsStyling: false,
        });
    } finally {
        loadingLink.value = false;
    }
};

const sendTest = async () => {
    testing.value = true;

    try {
        const { data } = await axios.post(route('aca_school_telegram_parameters_test'), {
            chat_id: testForm.chat_id,
            event: testForm.event,
        });

        Swal.fire({
            icon: 'success',
            title: 'Mensaje de prueba enviado',
            text: data.message,
            customClass: { popup: 'sweet-alerts', confirmButton: 'btn btn-success' },
            buttonsStyling: false,
        });
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'No se pudo enviar',
            text: firstError(error) || 'Revisa el Chat ID y la configuración del canal.',
            customClass: { popup: 'sweet-alerts', confirmButton: 'btn btn-danger' },
            buttonsStyling: false,
        });
    } finally {
        testing.value = false;
    }
};

const reloadData = () => router.reload({ only: ['deliveries', 'subscriptions'] });
</script>

<template>
    <AppLayout title="Parámetros Telegram">
        <Navigation
            :routeModule="route('aca_dashboard')"
            :titleModule="'Académico'"
            :data="[{ title: 'Colegio' }, { title: 'Parámetros Telegram' }]"
        />

        <div class="mt-5 flex flex-col gap-6">
            <!-- Encabezado y Estado del canal -->
            <div class="panel">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h5 class="text-lg font-semibold dark:text-white-light">
                                Avisos de asistencia por Telegram
                            </h5>
                            <span class="badge bg-primary/15 text-primary">Solo rol admin</span>
                        </div>
                        <p class="mt-1 text-sm text-gray-500">
                            Cuando la portería registra la entrada o la salida del alumno, el bot de Telegram avisa a los
                            padres o apoderados que hayan vinculado su DNI y el de su(s) hijo(s).
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-4">
                        <button
                            type="button"
                            class="btn btn-outline-info btn-sm"
                            id="btn-telegram-guide"
                            @click="showGuideModal = true"
                        >
                            📖 Guía de configuración
                        </button>

                        <div class="flex items-center gap-3">
                            <span class="text-sm font-medium dark:text-white-light">Activo</span>
                            <label class="w-12 h-6 relative">
                                <input
                                    v-model="form.active"
                                    type="checkbox"
                                    class="custom_switch absolute w-full h-full opacity-0 z-10 cursor-pointer peer"
                                    id="school-telegram-active"
                                />
                                <span class="bg-[#ebedf2] dark:bg-dark block h-full before:absolute before:left-1 before:bg-white dark:before:bg-white-dark before:bottom-1 before:w-4 before:h-4 peer-checked:before:left-7 peer-checked:bg-primary before:transition-all before:duration-300"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="rounded border border-gray-200 p-3 text-sm dark:border-gray-700">
                        <span class="block text-xs uppercase text-gray-400">Token / ID del Bot ({{ codes.token }})</span>
                        <span :class="channel.token ? 'text-success font-medium' : 'text-danger font-medium'">
                            {{ channel.token ? 'Configurado' : 'Falta configurar' }}
                        </span>
                    </div>
                    <div class="rounded border border-gray-200 p-3 text-sm dark:border-gray-700">
                        <span class="block text-xs uppercase text-gray-400">Usuario del Bot</span>
                        <span :class="botUsername ? 'text-primary font-medium' : 'text-gray-500'">
                            {{ botUsername ? `@${botUsername}` : 'Pulsa "Obtener enlace del bot"' }}
                        </span>
                    </div>
                    <div class="rounded border border-gray-200 p-3 text-sm dark:border-gray-700">
                        <span class="block text-xs uppercase text-gray-400">Padres / Hijos suscritos</span>
                        <span class="font-medium dark:text-white-light">
                            {{ subscriptions.filter((s) => s.status === 'active').length }} vínculo(s) activo(s)
                        </span>
                    </div>
                </div>

                <p v-if="!channelReady" class="mt-4 rounded bg-warning/10 px-3 py-2 text-sm text-warning">
                    El canal de Telegram no enviará avisos todavía: guarda el <b>Token / ID del Bot</b> ({{ codes.token }}),
                    enciende el interruptor <b>Activo</b> ({{ codes.enabled }}) y pulsa <b>Activar Webhook y Comandos</b>.
                    Si no tienes un bot creado, abre la
                    <button type="button" class="underline font-semibold" @click="showGuideModal = true">
                        Guía de configuración
                    </button>.
                </p>
            </div>

            <!-- Credenciales y Conexión del Bot -->
            <div class="panel">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h6 class="text-base font-semibold dark:text-white-light">
                            Configuración del Bot de Telegram (Asistencia Escolar)
                        </h6>
                        <p class="mt-1 text-sm text-gray-500">
                            Ingresa el Token / ID del bot entregado por <b>@BotFather</b> y conecta el webhook para que el bot responda a los padres.
                        </p>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm self-start" @click="showGuideModal = true">
                        ¿Cómo obtener el ID / Token en BotFather?
                    </button>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="form-label">
                            ID / Token del Bot de Telegram <span class="text-xs text-gray-400">({{ codes.token }})</span>
                        </label>
                        <input
                            v-model="form.token"
                            type="password"
                            class="form-input"
                            autocomplete="new-password"
                            :placeholder="channel.token ? 'Token guardado — escribe uno nuevo solo si deseas cambiarlo' : 'Ej: 1234567890:AAHxyz_token_de_botfather'"
                        />
                        <p class="mt-1 text-xs text-gray-500">
                            Por seguridad, el token guardado no se muestra en texto claro. Si lo dejas en blanco al guardar, se conserva el actual.
                        </p>
                        <p v-if="form.errors.token" class="mt-1 text-xs text-danger">{{ form.errors.token }}</p>
                    </div>

                    <div>
                        <label class="form-label">URL del Webhook (automática)</label>
                        <div class="flex items-center gap-2">
                            <input :value="webhookUrl" type="text" readonly class="form-input bg-gray-50 dark:bg-gray-800 text-xs" />
                            <button type="button" class="btn btn-outline-secondary btn-sm shrink-0" @click="copyToClipboard(webhookUrl, 'URL del webhook copiada')">
                                Copiar
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            Dirección HTTPS a la que Telegram enviará los mensajes cuando los padres escriban al bot.
                        </p>
                    </div>
                </div>

                <!-- Acciones del Bot: Webhook y Enlace para compartir con los padres -->
                <div class="mt-5 rounded-lg border border-sky-200 bg-sky-50/70 p-4 dark:border-sky-800 dark:bg-sky-900/20">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                Conexión con Telegram y enlace para los padres de familia
                            </p>
                            <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-300">
                                Una vez guardado el Token, activa el webhook para publicar automáticamente los comandos del bot y comparte el enlace único con los apoderados.
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                class="btn btn-primary btn-sm"
                                :disabled="registeringWebhook"
                                @click="registerWebhook"
                            >
                                {{ registeringWebhook ? 'Activando...' : '🔗 Activar Webhook y Comandos' }}
                            </button>

                            <button
                                type="button"
                                class="btn btn-outline-primary btn-sm"
                                :disabled="loadingLink"
                                @click="fetchBotLink"
                            >
                                {{ loadingLink ? 'Consultando...' : '🔄 Obtener enlace del bot' }}
                            </button>
                        </div>
                    </div>

                    <div v-if="botLink" class="mt-3 flex flex-wrap items-center gap-2">
                        <input
                            :value="botLink"
                            type="text"
                            readonly
                            class="form-input w-full max-w-sm bg-white text-sm dark:bg-zinc-900"
                        />
                        <button
                            type="button"
                            class="btn btn-secondary btn-sm"
                            @click="copyToClipboard(botLink, 'Enlace del bot copiado')"
                        >
                            Copiar enlace para padres
                        </button>
                    </div>

                    <!-- Resumen de comandos del bot -->
                    <div class="mt-4 border-t border-sky-200/80 pt-3 dark:border-sky-800/80">
                        <p class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                            Comandos que ofrece el bot a los padres / apoderados:
                        </p>
                        <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-4">
                            <div
                                v-for="cmd in botCommands"
                                :key="cmd.command"
                                class="rounded border border-white/80 bg-white/90 p-2.5 text-xs dark:border-gray-700 dark:bg-gray-800"
                            >
                                <code class="font-bold text-primary">/{{ cmd.command }}</code>
                                <p class="mt-1 text-gray-600 dark:text-gray-300">{{ cmd.description }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Interruptores y Reglas de envío -->
            <div class="panel">
                <h6 class="text-base font-semibold dark:text-white-light">Opciones de configuración y eventos</h6>

                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <label class="flex items-start gap-3">
                        <input v-model="form.notify_entry" type="checkbox" class="form-checkbox mt-1" />
                        <span>
                            <span class="font-semibold">Avisar las entradas <span class="text-xs text-gray-400">({{ codes.entry }})</span></span>
                            <span class="block text-xs text-gray-500">El apoderado recibe un mensaje en Telegram cuando el alumno ingresa al colegio.</span>
                        </span>
                    </label>

                    <label class="flex items-start gap-3">
                        <input v-model="form.notify_exit" type="checkbox" class="form-checkbox mt-1" />
                        <span>
                            <span class="font-semibold">Avisar las salidas <span class="text-xs text-gray-400">({{ codes.exit }})</span></span>
                            <span class="block text-xs text-gray-500">El apoderado recibe un mensaje en Telegram cuando el alumno sale del colegio.</span>
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
                            <span class="block text-xs text-gray-500">Si se apaga, reciben el aviso todos los apoderados del alumno que hayan registrado su chat.</span>
                        </span>
                    </label>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="form-label">Máximo por alumno y día <span class="text-xs text-gray-400">({{ codes.daily_limit }})</span></label>
                            <input v-model.number="form.daily_limit" type="number" min="0" max="999" class="form-input w-32" />
                            <span class="mt-1 block text-xs text-gray-500">0 = sin límite.</span>
                        </div>

                        <div>
                            <label class="form-label">Pausa entre mensajes (ms) <span class="text-xs text-gray-400">({{ codes.interval_ms }})</span></label>
                            <input v-model.number="form.interval_ms" type="number" min="0" max="60000" step="100" class="form-input w-36" />
                            <span class="mt-1 block text-xs text-gray-500">500 ms recomendado para respetar límites de Telegram.</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Plantillas de Mensajes -->
            <div class="panel">
                <h6 class="text-base font-semibold dark:text-white-light">Plantillas de mensajes</h6>
                <p class="mt-1 text-sm text-gray-500">
                    Personaliza el mensaje que recibirá el padre o apoderado cuando su hijo(a) registre su entrada o salida.
                </p>

                <div class="mt-4 space-y-5">
                    <div
                        v-for="block in [
                            { event: 'in', field: 'entry_message', code: codes.entry_message, title: 'Mensaje de entrada al colegio' },
                            { event: 'out', field: 'exit_message', code: codes.exit_message, title: 'Mensaje de salida del colegio' },
                        ]"
                        :key="block.event"
                    >
                        <label class="form-label">
                            {{ block.title }} <span class="text-xs text-gray-400">({{ block.code }})</span>
                        </label>
                        <textarea
                            v-model="form[block.field]"
                            rows="3"
                            maxlength="480"
                            class="form-textarea"
                            placeholder="Escribe el mensaje del aviso..."
                        ></textarea>

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
                            <button
                                type="button"
                                class="btn btn-outline-secondary btn-sm"
                                @click="preview(block.event, block.field)"
                            >
                                Vista previa
                            </button>
                            <span class="text-xs text-gray-500">La vista previa usa datos de ejemplo.</span>
                        </div>

                        <pre
                            v-if="previews[block.event]"
                            class="mt-2 whitespace-pre-wrap rounded bg-gray-50 p-3 text-sm dark:bg-gray-800"
                        >{{ previews[block.event] }}</pre>
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap items-center justify-end gap-3">
                    <p class="mr-auto text-sm text-gray-500">
                        Los cambios se aplican inmediatamente a los próximos avisos de asistencia.
                    </p>
                    <button type="button" class="btn btn-primary" :disabled="form.processing" @click="save">
                        {{ form.processing ? 'Guardando...' : 'Guardar parámetros' }}
                    </button>
                </div>
            </div>

            <!-- Enviar mensaje de prueba -->
            <div class="panel">
                <h6 class="text-base font-semibold dark:text-white-light">Enviar mensaje de prueba por Telegram</h6>
                <p class="mt-1 text-sm text-gray-500">
                    Envía un mensaje de ejemplo a un Chat ID (puedes obtener tu Chat ID enviando <code>/chatid</code> al bot). Requiere el canal configurado y activo.
                </p>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <label class="form-label">Chat ID de destino</label>
                        <input
                            v-model="testForm.chat_id"
                            type="text"
                            maxlength="64"
                            class="form-input"
                            placeholder="Ej: 123456789"
                        />
                        <p class="mt-1 text-xs text-gray-500">Escribe <code>/chatid</code> en el bot para conocer tu identificador.</p>
                    </div>
                    <div>
                        <label class="form-label">Evento de prueba</label>
                        <select v-model="testForm.event" class="form-select">
                            <option value="in">Entrada al colegio</option>
                            <option value="out">Salida del colegio</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="button" class="btn btn-secondary" :disabled="testing" @click="sendTest">
                            {{ testing ? 'Enviando...' : 'Enviar prueba' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Padres e hijos registrados en el bot -->
            <div class="panel">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h6 class="text-base font-semibold dark:text-white-light">
                            Padres / Apoderados e hijos registrados en el bot
                        </h6>
                        <p class="mt-1 text-sm text-gray-500">
                            Vínculos creados cuando el apoderado escribe <code>/registrar</code> en el bot y valida su DNI y el de su(s) hijo(s).
                        </p>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm" @click="reloadData">
                        Actualizar tablas
                    </button>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full table-auto text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-400 dark:border-gray-700">
                                <th class="px-2 py-2">Estudiante</th>
                                <th class="px-2 py-2">DNI Estudiante</th>
                                <th class="px-2 py-2">Padre / Apoderado</th>
                                <th class="px-2 py-2">DNI Apoderado</th>
                                <th class="px-2 py-2">Chat ID / Usuario</th>
                                <th class="px-2 py-2">Estado</th>
                                <th class="px-2 py-2">Fecha Registro</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="sub in subscriptions"
                                :key="sub.id"
                                class="border-b border-gray-100 dark:border-gray-800"
                            >
                                <td class="px-2 py-2 font-medium">{{ sub.student || '—' }}</td>
                                <td class="px-2 py-2">{{ sub.student_document || '—' }}</td>
                                <td class="px-2 py-2">{{ sub.guardian || '—' }}</td>
                                <td class="px-2 py-2">{{ sub.guardian_document || '—' }}</td>
                                <td class="px-2 py-2">
                                    <code>{{ sub.chat_id }}</code>
                                    <span v-if="sub.telegram_username" class="ml-1 text-xs text-gray-400">
                                        (@{{ sub.telegram_username }})
                                    </span>
                                </td>
                                <td class="px-2 py-2">
                                    <span :class="sub.status === 'active' ? 'badge bg-success' : 'badge bg-secondary'">
                                        {{ sub.status === 'active' ? 'Activo' : 'De baja' }}
                                    </span>
                                </td>
                                <td class="px-2 py-2 text-xs text-gray-500">{{ sub.registered_at || '—' }}</td>
                            </tr>
                            <tr v-if="!subscriptions.length">
                                <td colspan="7" class="px-2 py-4 text-center text-gray-500">
                                    Todavía ningún apoderado ha registrado su chat con el bot.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Últimos avisos enviados por Telegram -->
            <div class="panel">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h6 class="text-base font-semibold dark:text-white-light">Últimos avisos enviados por Telegram</h6>
                        <p class="mt-1 text-sm text-gray-500">
                            Resultado de los últimos 50 avisos generados por la portería para el canal Telegram.
                        </p>
                    </div>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full table-auto text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-400 dark:border-gray-700">
                                <th class="px-2 py-2">Alumno</th>
                                <th class="px-2 py-2">Apoderado</th>
                                <th class="px-2 py-2">Evento</th>
                                <th class="px-2 py-2">Chat ID</th>
                                <th class="px-2 py-2">Estado</th>
                                <th class="px-2 py-2">Motivo</th>
                                <th class="px-2 py-2">Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="delivery in deliveries"
                                :key="delivery.id"
                                class="border-b border-gray-100 dark:border-gray-800"
                            >
                                <td class="px-2 py-2">{{ delivery.student || '—' }}</td>
                                <td class="px-2 py-2">{{ delivery.guardian || '—' }}</td>
                                <td class="px-2 py-2">{{ delivery.event_label }}</td>
                                <td class="px-2 py-2"><code>{{ delivery.chat_id || '—' }}</code></td>
                                <td class="px-2 py-2">
                                    <span :class="deliveryStatusClass(delivery.status)">
                                        {{ deliveryStatusLabel(delivery.status) }}
                                    </span>
                                </td>
                                <td class="px-2 py-2 text-xs text-gray-500">{{ delivery.error_message || '—' }}</td>
                                <td class="px-2 py-2 text-xs text-gray-500">{{ delivery.created_at || '—' }}</td>
                            </tr>
                            <tr v-if="!deliveries.length">
                                <td colspan="7" class="px-2 py-4 text-center text-gray-500">
                                    Todavía no hay avisos de Telegram registrados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal: Guía de configuración paso a paso en BotFather -->
        <div
            v-if="showGuideModal"
            class="fixed inset-0 z-[999] flex items-center justify-center bg-black/60 p-4 overflow-y-auto"
        >
            <div class="w-full max-w-3xl rounded-lg bg-white p-6 shadow-xl dark:bg-[#0e1726] max-h-[90vh] overflow-y-auto">
                <div class="flex items-start justify-between border-b border-gray-200 pb-4 dark:border-gray-700">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            📖 Guía paso a paso: Cómo crear y configurar el Bot de Telegram en @BotFather
                        </h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Sigue estos pasos para obtener el ID / Token del bot y dejar funcionando las notificaciones de entrada y salida del colegio.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="text-2xl leading-none text-gray-400 hover:text-gray-600"
                        @click="showGuideModal = false"
                    >
                        &times;
                    </button>
                </div>

                <div class="mt-4 space-y-5 text-sm text-gray-700 dark:text-gray-300">
                    <!-- Paso 1 -->
                    <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                        <h4 class="font-semibold text-primary">
                            Paso 1: Crear el Bot en Telegram con @BotFather y obtener el ID / Token
                        </h4>
                        <ol class="mt-2 list-decimal space-y-1.5 pl-5">
                            <li>
                                Abre tu aplicación de <b>Telegram</b> (en el celular o PC) y busca en el buscador a
                                <b><code>@BotFather</code></b> (verifica que tenga el check azul oficial de verificación).
                            </li>
                            <li>
                                Pulsa <b>Iniciar</b> (o escribe <code>/start</code>) y luego envía el comando
                                <b><code>/newbot</code></b>.
                            </li>
                            <li>
                                <b>Nombre visible del bot:</b> BotFather te pedirá un nombre para mostrar. Escribe el nombre de tu colegio, por ejemplo:
                                <code>Avisos Asistencia I.E. San Martín</code>.
                            </li>
                            <li>
                                <b>Usuario único del bot (username):</b> Ahora te pedirá un nombre de usuario que <b>debe terminar obligatoriamente en <code>bot</code></b> (sin espacios), por ejemplo:
                                <code>iesanmartin_asistencia_bot</code>.
                            </li>
                            <li>
                                <b>Copia el Token / ID del bot:</b> ¡Listo! BotFather te responderá con un mensaje que dice
                                <i>"Use this token to access the HTTP API:"</i> seguido de un código similar a:
                                <code class="block mt-1 rounded bg-gray-100 px-2 py-1 text-xs dark:bg-gray-800">
                                    7123456789:AAH1234567890abcdefGHIJKLMNOPQRSTUV
                                </code>
                                Ese texto completo es el <b>ID / Token del Bot</b> que debes pegar en esta pantalla.
                            </li>
                        </ol>
                    </div>

                    <!-- Paso 2 -->
                    <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                        <h4 class="font-semibold text-primary">
                            Paso 2: Configurar el Token y activar el Webhook en esta pantalla
                        </h4>
                        <ol class="mt-2 list-decimal space-y-1.5 pl-5">
                            <li>
                                Pega el Token obtenido en el campo <b>ID / Token del Bot de Telegram ({{ codes.token }})</b> de esta pantalla.
                            </li>
                            <li>
                                Enciende el interruptor <b>Activo</b> arriba a la derecha, revisa qué avisos deseas enviar (entradas, salidas, tardanzas, salidas anticipadas) y haz clic en <b>Guardar parámetros</b>.
                            </li>
                            <li>
                                Una vez guardado, haz clic en el botón <b>🔗 Activar Webhook y Comandos</b>. Esto conectará tu bot con este sistema ({{ webhookUrl }}) y publicará automáticamente el menú de comandos en Telegram:
                                <ul class="mt-1 list-disc pl-5 text-xs text-gray-500">
                                    <li><code>/registrar</code> – Registrar DNI del padre/madre/apoderado y DNI de su(s) hijo(s)</li>
                                    <li><code>/baja</code> – Dar de baja el registro para no recibir más mensajes</li>
                                    <li><code>/chatid</code> – Consultar Mi Chat ID</li>
                                    <li><code>/info</code> – Ver información del colegio, horarios e hijos vinculados</li>
                                </ul>
                            </li>
                            <li>
                                <b>Requisito importante:</b> Para que Telegram entregue los mensajes al webhook, el dominio del sistema debe contar con <b>HTTPS público</b> y para que las notificaciones salgan en segundo plano debe estar activo el worker de colas (<code>php artisan queue:work</code>).
                            </li>
                        </ol>
                    </div>

                    <!-- Paso 3 -->
                    <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                        <h4 class="font-semibold text-primary">
                            Paso 3: ¿Cómo se registran los padres o apoderados (con 1, 2 o más hijos)?
                        </h4>
                        <ol class="mt-2 list-decimal space-y-1.5 pl-5">
                            <li>
                                Asegúrate de que en <b>Académico &gt; Colegio &gt; Estudiantes</b> cada alumno tenga su <b>DNI</b> registrado y tenga asociado a su padre, madre o apoderado con su respectivo <b>DNI</b>.
                            </li>
                            <li>
                                Comparte con los padres de familia el enlace del bot (puedes copiarlo desde el botón <b>Obtener enlace del bot</b>).
                            </li>
                            <li>
                                El padre o madre abre el bot en Telegram y elige <b><code>/registrar</code></b>:
                                <ul class="mt-1 list-disc space-y-1 pl-5">
                                    <li>El bot le pedirá primero su <b>DNI de padre, madre o apoderado</b> y verificará que exista en el colegio.</li>
                                    <li>Luego le pedirá el <b>DNI de su hijo(a) estudiante</b> y validará que esté vinculado a ese apoderado.</li>
                                    <li>Al confirmarse el registro del primer hijo, el bot le preguntará: <i>"¿Tienes otro hijo o hija en el colegio o escuela que quieras registrar?"</i> con dos botones: <b>✅ Sí, tengo otro hijo(a)</b> y <b>🏁 No, finalizar</b> (también puede responder escribiendo SÍ o NO).</li>
                                    <li>Si elige <b>Sí</b>, el bot le pedirá el DNI del segundo hijo y, tras registrarlo, volverá a preguntar si tiene otro hijo más (permitiendo registrar 2, 3 o más hijos en el mismo chat).</li>
                                    <li>Si elige <b>No</b>, finaliza el proceso y desde ese instante recibirá avisos automáticos cuando cualquiera de sus hijos entre o salga del colegio.</li>
                                    <li>Además, los mensajes del bot incluyen botones para ejecutar los comandos principales (<b>Registrarme</b>, <b>Info del colegio</b>, <b>Mi Chat ID</b> y <b>Darme de baja</b>) sin necesidad de escribir el comando; la baja siempre pide confirmación antes de desvincular el chat.</li>
                                </ul>
                            </li>
                        </ol>
                    </div>
                </div>

                <div class="mt-6 flex justify-end border-t border-gray-200 pt-4 dark:border-gray-700">
                    <button type="button" class="btn btn-primary" @click="showGuideModal = false">
                        Entendido, cerrar guía
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
