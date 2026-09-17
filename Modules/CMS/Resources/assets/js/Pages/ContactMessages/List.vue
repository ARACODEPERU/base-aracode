<script setup>
import { ref } from 'vue';
import { useForm } from "@inertiajs/vue3";
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import ModalLargeX from '@/Components/ModalLargeX.vue';
import { faTimes, faEye, faCheck, faReply, faClock, faPaperPlane, faEnvelope, faPhone, faBuilding, faTag, faCalendarAlt, faArrowLeft, faExternalLinkAlt } from "@fortawesome/free-solid-svg-icons";
import Keypad from '@/Components/Keypad.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import FlatPickr from 'vue-flatpickr-component';
import 'flatpickr/dist/flatpickr.css';
import { Spanish } from "flatpickr/dist/l10n/es.js";
import Swal2 from 'sweetalert2';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    messages: {
        type: Object,
        default: () => ({})
    },
    stats: {
        type: Object,
        default: () => ({})
    },
    filters: {
        type: Object,
        default: () => ({})
    }
});

const form = useForm({
    search: props.filters.search,
    status: props.filters.status || null,
    dates: props.filters.dates || null,
});

const configFlatPickr = {
    dateFormat: 'Y-m-d',
    mode: 'range',
    locale: Spanish,
};

const formatDateTime = (dateTimeString) => {
    if (!dateTimeString) return '—';

    const date = new Date(dateTimeString);
    if (isNaN(date.getTime())) return '—';

    const formattedDate = date.toISOString().slice(0, 10);
    const formattedTime = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    return `${formattedDate} ${formattedTime}`;
};

const statusBadgeClass = (status) => {
    if (status === 'pending') {
        return 'bg-amber-100 text-amber-800 border-amber-400 dark:bg-gray-700 dark:text-amber-400';
    }
    if (status === 'read') {
        return 'bg-blue-100 text-blue-800 border-blue-400 dark:bg-gray-700 dark:text-blue-400';
    }
    if (status === 'replied') {
        return 'bg-emerald-100 text-emerald-800 border-emerald-400 dark:bg-gray-700 dark:text-emerald-400';
    }
    return 'bg-gray-100 text-gray-800 border-gray-400 dark:bg-gray-700 dark:text-gray-400';
};

const statusText = (status) => {
    if (status === 'pending') return 'Pendiente';
    if (status === 'read') return 'Leído';
    if (status === 'replied') return 'Respondido';
    return status;
};

const statusIcon = (status) => {
    if (status === 'pending') return faClock;
    if (status === 'read') return faEye;
    if (status === 'replied') return faReply;
    return faEye;
};

// Etiquetas legibles del servicio que eligio la persona en el formulario publico.
const servicios = {
    kapta: 'KAPTA LMS',
    facturacion: 'Facturación Electrónica',
    desarrollo: 'Desarrollo a Medida',
    automatizacion: 'Automatización de Procesos',
    consultoria: 'Consultoría Tecnológica',
    otro: 'Otro',
};

const serviceText = (service) => servicios[service] || service || '—';

// Leer la consulta y responderla ocurre en el mismo modal: primero el detalle
// ordenado y, desde ahi, el formulario de respuesta.
const showModal = ref(false);
const mode = ref('detail');
const selected = ref(null);

const replyForm = useForm({
    subject: '',
    message: '',
});

const openDetail = (message) => {
    selected.value = message;
    mode.value = 'detail';
    showModal.value = true;
};

const openReply = (message) => {
    selected.value = message;
    startReply();
};

const startReply = () => {
    const message = selected.value;

    replyForm.clearErrors();
    replyForm.subject = `Respuesta a tu consulta - ${serviceText(message.service)}`;
    replyForm.message = [
        `Hola ${message.name},`,
        '',
        'Gracias por escribirnos. ',
        '',
        '',
        '---',
        `Tu consulta original (${formatDateTime(message.created_at)}):`,
        message.message,
    ].join('\n');

    mode.value = 'reply';
};

const backToDetail = () => {
    mode.value = 'detail';
    replyForm.clearErrors();
};

const closeModal = () => {
    showModal.value = false;
    mode.value = 'detail';
    replyForm.clearErrors();
};

const sendReply = () => {
    const email = selected.value?.email;

    replyForm.post(route('cms_contact_messages_reply', selected.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            Swal2.fire({
                icon: 'success',
                title: 'Respuesta enviada',
                text: `El correo salió hacia ${email} y el mensaje quedó como Respondido.`,
                timer: 2600,
                showConfirmButton: false,
            });
        },
        onError: () => {
            // El servidor tambien cae aqui si el correo no pudo salir: en ese caso
            // el mensaje NO se marca como respondido.
            Swal2.fire({
                icon: 'error',
                title: 'No se pudo enviar',
                text: 'El correo no salió. Revisa los campos marcados e inténtalo otra vez.',
                confirmButtonText: 'Entendido',
            });
        },
    });
};

const goTo = (params = {}) => {
    router.get(
        route('cms_contact_messages_list'),
        { ...props.filters, ...params },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};
</script>

<template>
    <AppLayout title="Mensajes de Contacto">
        <Navigation :routeModule="route('cms_dashboard')" :titleModule="'CMS'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Mensajes de Contacto</span>
            </li>
        </Navigation>

        <div class="pt-5">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="rounded-sm border border-stroke bg-white p-5 shadow-default dark:border-strokedark dark:bg-boxdark">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-2xl font-bold text-black dark:text-white">{{ stats.total }}</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Total</p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
                            <font-awesome-icon :icon="faEye" class="text-gray-600 dark:text-gray-300" />
                        </div>
                    </div>
                </div>
                <div class="rounded-sm border border-stroke bg-white p-5 shadow-default dark:border-strokedark dark:bg-boxdark">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-2xl font-bold text-black dark:text-white">{{ stats.pending }}</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Pendientes</p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30">
                            <font-awesome-icon :icon="faClock" class="text-amber-600 dark:text-amber-400" />
                        </div>
                    </div>
                </div>
                <div class="rounded-sm border border-stroke bg-white p-5 shadow-default dark:border-strokedark dark:bg-boxdark">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-2xl font-bold text-black dark:text-white">{{ stats.read }}</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Leídos</p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                            <font-awesome-icon :icon="faEye" class="text-blue-600 dark:text-blue-400" />
                        </div>
                    </div>
                </div>
                <div class="rounded-sm border border-stroke bg-white p-5 shadow-default dark:border-strokedark dark:bg-boxdark">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-2xl font-bold text-black dark:text-white">{{ stats.replied }}</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Respondidos</p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30">
                            <font-awesome-icon :icon="faCheck" class="text-emerald-600 dark:text-emerald-400" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex items-center justify-between flex-wrap gap-4 mb-4">
                <div class="grid grid-cols-8 w-full gap-4">
                    <div class="col-span-6 sm:col-span-5">
                        <div class="flex items-center gap-4">
                            <div class="relative w-60">
                                <FlatPickr v-model="form.dates" :config="configFlatPickr" class="form-input w-full" placeholder="Selecciona rango de fechas" />
                                <button v-if="form.dates" @click="form.dates = null" type="button" class="absolute right-3 top-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none" aria-label="Limpiar fechas">
                                    <font-awesome-icon :icon="faTimes" class="w-4 h-4" />
                                </button>
                            </div>
                            <div>
                                <select v-model="form.status" class="form-input w-full">
                                    <option value="">Todos los estados</option>
                                    <option value="pending">Pendiente</option>
                                    <option value="read">Leído</option>
                                    <option value="replied">Respondido</option>
                                </select>
                            </div>
                            <div>
                                <input v-model="form.search" type="text" id="table-search-users" class="form-input pl-10" placeholder="Buscar por nombre o email">
                            </div>
                        </div>
                    </div>
                    <div class="col-span-8 sm:col-span-3">
                        <div class="flex items-center justify-end gap-4">
                            <button @click="goTo({ search: form.search, status: form.status, dates: form.dates })" class="btn btn-primary text-xs uppercase">Buscar</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="mt-5">
                <div class="mt-5 panel p-0 border-0 overflow-hidden">
                    <Pagination :data="messages">
                        <div class="table-responsive">
                            <table class="table-striped table-hover" id="table_export">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <th>Teléfono</th>
                                        <th>Servicio</th>
                                        <th>Mensaje</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(message, index) in messages.data" :key="message.id" class="bg-white border dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                        <td>{{ formatDateTime(message.created_at) }}</td>
                                        <td class="font-medium">{{ message.name }}</td>
                                        <td>{{ message.email }}</td>
                                        <td>{{ message.phone || '—' }}</td>
                                        <td>
                                            <span class="text-xs font-medium px-2 py-0.5 rounded border bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600">
                                                {{ message.service }}
                                            </span>
                                        </td>
                                        <td class="max-w-xs truncate" :title="message.message">{{ message.message }}</td>
                                        <td>
                                            <span class="text-xs font-medium mr-2 px-2.5 py-0.5 rounded border"
                                                  :class="statusBadgeClass(message.status)">
                                                {{ statusText(message.status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-1">
                                                <button type="button"
                                                        @click="openDetail(message)"
                                                        title="Ver detalle"
                                                        class="text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:outline-none focus:ring-indigo-300 font-medium rounded-full text-sm p-2.5 text-center inline-flex items-center dark:bg-indigo-600 dark:hover:bg-indigo-700">
                                                    <font-awesome-icon :icon="faEye" />
                                                </button>
                                                <button type="button"
                                                        @click="openReply(message)"
                                                        title="Responder"
                                                        class="text-white bg-emerald-600 hover:bg-emerald-700 focus:ring-4 focus:outline-none focus:ring-emerald-300 font-medium rounded-full text-sm p-2.5 text-center inline-flex items-center dark:bg-emerald-600 dark:hover:bg-emerald-700">
                                                    <font-awesome-icon :icon="faReply" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </Pagination>
                </div>
            </div>
        </div>

        <!-- Modal: detalle ordenado de la consulta y respuesta por correo -->
        <ModalLargeX :show="showModal"
                     :on-close="closeModal"
                     :loading="replyForm.processing"
                     loading-text="Enviando la respuesta, espera un momento...">
            <template #title>
                <span v-if="mode === 'reply'">Responder a {{ selected?.name }}</span>
                <span v-else>Consulta de {{ selected?.name }}</span>
            </template>

            <template #message>
                <span v-if="mode === 'reply'">
                    El correo saldrá hacia <strong>{{ selected?.email }}</strong> y el mensaje quedará marcado como Respondido.
                </span>
                <span v-else>
                    {{ serviceText(selected?.service) }} · recibido el {{ formatDateTime(selected?.created_at) }}
                </span>
            </template>

            <template #content>
                <!-- Detalle de la consulta -->
                <div v-if="mode === 'detail'" class="space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="flex items-start gap-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 p-4">
                            <font-awesome-icon :icon="faEnvelope" class="mt-1 text-indigo-500" />
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Email</p>
                                <a :href="'mailto:' + (selected?.email || '')"
                                   class="text-sm break-all text-indigo-600 dark:text-indigo-400 hover:underline">{{ selected?.email }}</a>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 p-4">
                            <font-awesome-icon :icon="faPhone" class="mt-1 text-emerald-500" />
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Teléfono</p>
                                <a v-if="selected?.phone" :href="'tel:' + selected.phone"
                                   class="text-sm text-emerald-600 dark:text-emerald-400 hover:underline">{{ selected.phone }}</a>
                                <p v-else class="text-sm text-gray-500 dark:text-gray-400">No proporcionado</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 p-4">
                            <font-awesome-icon :icon="faBuilding" class="mt-1 text-amber-500" />
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Empresa</p>
                                <p class="text-sm text-gray-900 dark:text-white">{{ selected?.company || 'No proporcionada' }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 p-4">
                            <font-awesome-icon :icon="faTag" class="mt-1 text-blue-500" />
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Servicio</p>
                                <p class="text-sm text-gray-900 dark:text-white">{{ serviceText(selected?.service) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <span class="text-xs font-medium px-2.5 py-0.5 rounded border"
                              :class="statusBadgeClass(selected?.status)">
                            {{ statusText(selected?.status) }}
                        </span>
                        <span class="inline-flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                            <font-awesome-icon :icon="faCalendarAlt" /> {{ formatDateTime(selected?.created_at) }}
                        </span>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 mb-2">Consulta</p>
                        <div class="max-h-72 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 p-4">
                            <p class="text-sm leading-relaxed text-gray-800 dark:text-gray-100 whitespace-pre-line">{{ selected?.message }}</p>
                        </div>
                    </div>

                    <div v-if="selected" class="text-right">
                        <Link :href="route('cms_contact_messages_show', selected.id)"
                              class="inline-flex items-center text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                            <font-awesome-icon :icon="faExternalLinkAlt" class="mr-2" /> Abrir ficha completa
                        </Link>
                    </div>
                </div>

                <!-- Formulario de respuesta -->
                <form v-else @submit.prevent="sendReply" class="space-y-4">
                    <div class="rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 p-4">
                        <p class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Para</p>
                        <p class="text-sm text-gray-900 dark:text-white">{{ selected?.name }} · {{ selected?.email }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Asunto</label>
                        <input v-model="replyForm.subject"
                               type="text"
                               maxlength="255"
                               class="form-input w-full"
                               :class="replyForm.errors.subject ? 'border-red-500' : ''">
                        <p v-if="replyForm.errors.subject" class="mt-1 text-sm text-red-600">{{ replyForm.errors.subject }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Respuesta</label>
                        <textarea v-model="replyForm.message"
                                  rows="9"
                                  maxlength="5000"
                                  class="form-input w-full"
                                  :class="replyForm.errors.message ? 'border-red-500' : ''"></textarea>
                        <p v-if="replyForm.errors.message" class="mt-1 text-sm text-red-600">{{ replyForm.errors.message }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            La consulta original de la persona se cita al final del correo.
                        </p>
                    </div>
                </form>
            </template>

            <template #buttons>
                <button v-if="mode === 'detail'"
                        type="button"
                        @click="startReply"
                        class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-emerald-700 focus:outline-none transition duration-150 ease-in-out">
                    <font-awesome-icon :icon="faReply" class="mr-2" /> Responder
                </button>
                <template v-else>
                    <button type="button"
                            @click="backToDetail"
                            class="inline-flex items-center px-4 py-2 bg-gray-600 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-gray-700 focus:outline-none transition duration-150 ease-in-out">
                        <font-awesome-icon :icon="faArrowLeft" class="mr-2" /> Volver
                    </button>
                    <button type="button"
                            @click="sendReply"
                            :disabled="replyForm.processing"
                            class="inline-flex items-center px-4 py-2 bg-blue-900 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-blue-700 focus:outline-none transition duration-150 ease-in-out disabled:opacity-50">
                        <font-awesome-icon :icon="faPaperPlane" class="mr-2" /> Enviar respuesta
                    </button>
                </template>
            </template>
        </ModalLargeX>
    </AppLayout>
</template>
