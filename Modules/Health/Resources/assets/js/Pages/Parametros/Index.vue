<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import DoctorGoogleLinkModal from '../../Components/DoctorGoogleLinkModal.vue';

const props = defineProps({
    doctors: {
        type: Array,
        default: () => [],
    },
    canManageAll: {
        type: Boolean,
        default: false,
    },
    canSelfConnect: {
        type: Boolean,
        default: false,
    },
    options: {
        type: Object,
        default: () => ({ allow_doctor_connection: true, principal_fallback: true, confirm_minutes: 5 }),
    },
    principal: {
        type: Object,
        default: () => ({ connected: false, email: null }),
    },
});

// Doctor cuya agenda se esta enlazando (abre el modal de contraseña).
const linkDoctor = ref(null);
const linkOpen = ref(false);

const openLink = (doctor) => {
    linkDoctor.value = doctor;
    linkOpen.value = true;
};

// El doctor solo gestiona su propia fila; el administrador, todas.
const canOperate = (doctor) => props.canManageAll || doctor.isOwn;

const disconnect = async (doctor) => {
    const result = await Swal.fire({
        icon: 'warning',
        title: 'Desenlazar la agenda de Google',
        text: `Las citas que ya están en el calendario de ${doctor.name} se quedan ahí, pero ya no se actualizarán.`,
        showCancelButton: true,
        confirmButtonText: 'Desenlazar',
        cancelButtonText: 'Cancelar',
        customClass: { popup: 'sweet-alerts', confirmButton: 'btn btn-danger', cancelButton: 'btn btn-outline-secondary' },
        buttonsStyling: false,
    });

    if (result.isConfirmed) {
        router.post(route('heal_doctor_google_disconnect', { doctor: doctor.code }), {}, { preserveScroll: true });
    }
};

// Opciones de configuración del enlace (solo el administrador las guarda).
const optionsForm = useForm({
    allow_doctor_connection: !!props.options.allow_doctor_connection,
    principal_fallback: !!props.options.principal_fallback,
    confirm_minutes: props.options.confirm_minutes,
});

const saveOptions = () => {
    optionsForm.post(route('heal_parameters_google_options'), { preserveScroll: true });
};
</script>

<template>
    <AppLayout title="Parámetros">
        <ul class="flex space-x-2 rtl:space-x-reverse">
            <li>
                <a href="javascript:;" class="text-primary hover:underline">Salud</a>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>Parámetros</span>
            </li>
        </ul>

        <div class="space-y-5 pt-5">
            <!-- Agenda principal del consultorio (la que ven todos). -->
            <div class="panel">
                <h2 class="text-xl font-semibold">Agenda principal del consultorio</h2>
                <p class="mt-1 text-sm text-white-dark">
                    Es la agenda compartida: en ella se ven las citas de <strong>todos</strong> los doctores.
                </p>
                <div class="mt-4 rounded border border-gray-200 p-3 text-sm dark:border-gray-700">
                    <span v-if="principal.connected">
                        Conectada como <strong>{{ principal.email }}</strong>.
                    </span>
                    <span v-else>Sin conectar.</span>
                    <span v-if="canManageAll" class="ml-2">
                        Se gestiona desde
                        <a :href="route('heal_google_calendar')" class="text-primary hover:underline">Google Calendar</a>.
                    </span>
                </div>
            </div>

            <!-- Agenda de Google de cada doctor. -->
            <div class="panel">
                <h2 class="text-xl font-semibold">Agenda de Google de cada doctor</h2>
                <p class="mt-1 text-sm text-white-dark">
                    Cada doctor puede enlazar su propia cuenta de Google. Sus citas se escriben en su calendario, no en la agenda principal.
                </p>

                <p v-if="doctors.length === 0" class="mt-4 text-sm text-white-dark">
                    Tu usuario no está vinculado a un doctor, así que no hay agenda que enlazar.
                </p>

                <div v-else class="mt-4 space-y-3">
                    <div
                        v-for="doctor in doctors"
                        :key="doctor.code"
                        class="flex flex-col gap-3 rounded border border-gray-200 p-3 text-sm sm:flex-row sm:items-center sm:justify-between dark:border-gray-700"
                    >
                        <div>
                            <div class="font-semibold">
                                {{ doctor.name }}
                                <span v-if="doctor.isOwn" class="ml-1 text-xs text-primary">(tú)</span>
                            </div>
                            <div v-if="doctor.specialty" class="text-xs text-white-dark">{{ doctor.specialty }}</div>
                            <div class="mt-1" :class="doctor.google.connected ? 'text-success' : 'text-white-dark'">
                                <template v-if="doctor.google.connected">
                                    Enlazada con {{ doctor.google.email || 'cuenta de Google' }}
                                </template>
                                <template v-else>Sin enlazar</template>
                            </div>
                            <div v-if="doctor.google.connected && doctor.google.lastSyncAt" class="text-xs text-white-dark">
                                Última sincronización: {{ doctor.google.lastSyncAt }}
                            </div>
                            <div v-if="doctor.google.connected && doctor.google.lastError" class="text-xs text-danger">
                                Último error: {{ doctor.google.lastError }}
                            </div>
                        </div>

                        <div v-if="canOperate(doctor)" class="shrink-0">
                            <button
                                v-if="!doctor.google.connected && canSelfConnect"
                                type="button"
                                class="btn btn-primary btn-sm"
                                @click="openLink(doctor)"
                            >
                                Conectar Google Calendar
                            </button>
                            <button
                                v-else-if="doctor.google.connected"
                                type="button"
                                class="btn btn-outline-danger btn-sm"
                                @click="disconnect(doctor)"
                            >
                                Desconectar
                            </button>
                            <p v-else class="text-xs text-white-dark">El administrador desactivó que los doctores enlacen su agenda.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Opciones de configuración del enlace. -->
            <div class="panel">
                <h2 class="text-xl font-semibold">Opciones de Google Calendar</h2>

                <form v-if="canManageAll" class="mt-4 space-y-4" @submit.prevent="saveOptions">
                    <label class="flex items-start gap-3">
                        <input v-model="optionsForm.allow_doctor_connection" type="checkbox" class="form-checkbox mt-1" />
                        <span>
                            <span class="font-semibold">Permitir que los doctores enlacen su agenda</span>
                            <span class="block text-xs text-white-dark">Si se apaga, solo el administrador enlaza calendarios. Los enlaces ya existentes siguen funcionando.</span>
                        </span>
                    </label>

                    <label class="flex items-start gap-3">
                        <input v-model="optionsForm.principal_fallback" type="checkbox" class="form-checkbox mt-1" />
                        <span>
                            <span class="font-semibold">Enviar a la agenda principal las citas de doctores sin agenda propia</span>
                            <span class="block text-xs text-white-dark">Si se apaga, las citas de un doctor que no enlazó su agenda no se envían a Google.</span>
                        </span>
                    </label>

                    <div>
                        <label class="mb-1 block font-semibold">Minutos para terminar la conexión con Google</label>
                        <input v-model.number="optionsForm.confirm_minutes" type="number" min="1" max="30" class="form-input w-32" />
                        <span class="mt-1 block text-xs text-white-dark">Tiempo que dura la confirmación de contraseña antes de enlazar (1 a 30).</span>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary" :disabled="optionsForm.processing">Guardar opciones</button>
                    </div>
                </form>

                <ul v-else class="mt-4 list-inside list-disc space-y-1 text-sm">
                    <li>Los doctores {{ options.allow_doctor_connection ? 'pueden' : 'no pueden' }} enlazar su agenda.</li>
                    <li>
                        Las citas de doctores sin agenda propia {{ options.principal_fallback ? 'se envían' : 'no se envían' }} a la agenda principal.
                    </li>
                    <li>La confirmación para enlazar dura {{ options.confirm_minutes }} minutos.</li>
                </ul>
            </div>
        </div>

        <DoctorGoogleLinkModal
            :open="linkOpen"
            :doctor-id="linkDoctor?.code ?? null"
            :doctor-name="linkDoctor?.name || ''"
            :confirm-minutes="options.confirm_minutes"
            @close="linkOpen = false"
        />
    </AppLayout>
</template>
