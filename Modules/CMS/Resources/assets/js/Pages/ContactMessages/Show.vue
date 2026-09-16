<script setup>
import { useForm } from "@inertiajs/vue3";
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { Link } from '@inertiajs/vue3';
import { faArrowLeft, faSave } from "@fortawesome/free-solid-svg-icons";
import Swal2 from 'sweetalert2';

const props = defineProps({
    contactMessage: {
        type: Object,
        required: true
    },
    success: {
        type: String,
        default: null
    }
});

const form = useForm({
    status: props.contactMessage.status,
    admin_notes: props.contactMessage.admin_notes || '',
});

const formatDateTime = (dateTimeString) => {
    const date = new Date(dateTimeString);
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

const submit = () => {
    form.put(route('cms_contact_messages_update', props.contactMessage.id), {
        preserveState: true,
        onSuccess: () => {
            Swal2.fire({
                icon: 'success',
                title: 'Guardado',
                text: 'Los cambios se guardaron correctamente.',
                timer: 1500,
                showConfirmButton: false,
            });
        }
    });
};

if (props.success) {
    Swal2.fire({
        icon: 'success',
        title: 'Éxito',
        text: props.success,
        timer: 2000,
        showConfirmButton: false,
    });
}
</script>

<template>
    <AppLayout title="Detalle de Mensaje">
        <Navigation :routeModule="route('cms_dashboard')" :titleModule="'CMS'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <Link :href="route('cms_contact_messages_list')" class="hover:text-blue-600 dark:hover:text-white">
                    Mensajes de Contacto
                </Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Detalle</span>
            </li>
        </Navigation>

        <div class="pt-5">
            <div class="flex items-center gap-4 mb-6">
                <Link :href="route('cms_contact_messages_list')"
                      class="inline-flex items-center justify-center px-4 py-2 bg-gray-600 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-gray-700 hover:shadow-lg focus:bg-gray-700 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-gray-800 active:shadow-lg transition duration-150 ease-in-out">
                    <font-awesome-icon :icon="faArrowLeft" class="mr-2" />
                    Volver
                </Link>
                <h2 class="text-xl font-bold text-black dark:text-white">Mensaje de {{ contactMessage.name }}</h2>
                <span class="text-xs font-medium px-2.5 py-0.5 rounded border"
                      :class="statusBadgeClass(contactMessage.status)">
                    {{ statusText(contactMessage.status) }}
                </span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Message Info -->
                <div class="lg:col-span-2">
                    <div class="rounded-sm border border-stroke bg-white p-6 shadow-default dark:border-strokedark dark:bg-boxdark">
                        <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Información del Mensaje</h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre</label>
                                <p class="text-sm text-gray-900 dark:text-white">{{ contactMessage.name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                                <p class="text-sm text-gray-900 dark:text-white">{{ contactMessage.email }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Teléfono</label>
                                <p class="text-sm text-gray-900 dark:text-white">{{ contactMessage.phone || 'No proporcionado' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Empresa</label>
                                <p class="text-sm text-gray-900 dark:text-white">{{ contactMessage.company || 'No proporcionada' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Servicio</label>
                                <p class="text-sm text-gray-900 dark:text-white">{{ contactMessage.service }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha</label>
                                <p class="text-sm text-gray-900 dark:text-white">{{ formatDateTime(contactMessage.created_at) }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mensaje</label>
                            <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                <p class="text-sm text-gray-900 dark:text-white whitespace-pre-line">{{ contactMessage.message }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar - Status Form -->
                <div class="lg:col-span-1">
                    <div class="rounded-sm border border-stroke bg-white p-6 shadow-default dark:border-strokedark dark:bg-boxdark">
                        <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Actualizar Estado</h3>

                        <form @submit.prevent="submit">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Estado</label>
                                <select v-model="form.status" class="form-input w-full">
                                    <option value="pending">Pendiente</option>
                                    <option value="read">Leído</option>
                                    <option value="replied">Respondido</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Notas Internas</label>
                                <textarea v-model="form.admin_notes"
                                          rows="4"
                                          class="form-input w-full"
                                          placeholder="Agregar notas internas sobre este mensaje..."></textarea>
                            </div>

                            <button type="submit"
                                    :disabled="form.processing"
                                    class="w-full inline-flex items-center justify-center px-6 py-2.5 bg-blue-900 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-blue-700 hover:shadow-lg focus:bg-blue-700 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-blue-800 active:shadow-lg transition duration-150 ease-in-out disabled:opacity-50">
                                <font-awesome-icon :icon="faSave" class="mr-2" />
                                Guardar Cambios
                            </button>
                        </form>
                    </div>

                    <!-- Quick Actions -->
                    <div class="rounded-sm border border-stroke bg-white p-6 shadow-default dark:border-strokedark dark:bg-boxdark mt-4">
                        <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Acciones Rápidas</h3>
                        <div class="space-y-2">
                            <a :href="'mailto:' + contactMessage.email"
                               class="w-full inline-flex items-center justify-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium text-xs leading-tight uppercase rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition duration-150 ease-in-out">
                                Responder por Email
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
