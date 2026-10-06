<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import { computed, onMounted, ref, watch } from 'vue';
    import { TransitionRoot, TransitionChild, Dialog, DialogPanel, DialogOverlay } from '@headlessui/vue';
    import { useForm, router } from '@inertiajs/vue3';

    import FullCalendar from '@fullcalendar/vue3';
    import dayGridPlugin from '@fullcalendar/daygrid';
    import timeGridPlugin from '@fullcalendar/timegrid';
    import interactionPlugin from '@fullcalendar/interaction';
    import momentTimezonePlugin from '@fullcalendar/moment-timezone';
    import esLocale from '@fullcalendar/core/locales/es';

    import Swal from 'sweetalert2';

    import Multiselect from '@suadelabs/vue3-multiselect';
    import '@suadelabs/vue3-multiselect/dist/vue3-multiselect.css';

    import IconPlus from '@/Components/vristo/icon/icon-plus.vue';
    import IconX from '@/Components/vristo/icon/icon-x.vue';
    import InputError from '@/Components/InputError.vue';


    const props = defineProps({
        eventsDB: {
            type: Array,
            default: () => ([]),
        },
        patients: {
            type: Array,
            default: () => ([]),
        },
        doctors: {
            type: Array,
            default: () => ([]),
        },
        currentDoctor: {
            type: Object,
            default: null,
        },
        canChooseDoctor: {
            type: Boolean,
            default: false,
        },
    });

    // Estatus de la cita tal como se guarda en dent_appointments.
    const STATUS_LABELS = {
        '1': 'Pendiente',
        '2': 'Atendido',
        '0': 'Cancelado',
        '3': 'No concretada',
    };
    const STATUS_CLASSES = {
        '1': 'success',
        '2': 'primary',
        '0': 'danger',
        '3': 'info',
    };

    const statusLabel = (status) => STATUS_LABELS[String(status)] || 'Sin estado';
    const statusClass = (status) => STATUS_CLASSES[String(status)] || 'info';
    const statusBadgeClass = (status) => 'badge bg-' + statusClass(status);

    const calendar = ref(null);
    const events = ref([]);

    const calendarOptions = computed(() => {
        return {
            plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin, momentTimezonePlugin],
            timeZone: 'America/Lima',
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay',
            },
            locale: esLocale,
            editable: true,
            dayMaxEvents: true,
            selectable: true,
            droppable: true,
            buttonText:{
                today:    'Hoy',
                month:    'Mes',
                week:     'Semana',
                day:      'Día',
                list:     'lista'
            },
            eventClick: (event) => {
                openDetailModal(event);
            },
            events: events.value,
        };
    });

    onMounted(() => {
        getEvents();
    });

    // La cita se registra en el servidor y el calendario se refresca con la
    // lista que devuelve el controlador (misma consulta y mismo filtro).
    watch(() => props.eventsDB, () => {
        getEvents();
    });

    const getEvents = () => {
        events.value = props.eventsDB.map(appointment => {
            const patientName = appointment.patient?.full_name || 'Paciente no registrado';
            const doctorName = appointment.doctor?.full_name || 'Sin doctor';

            return {
                id: appointment.id,
                title: `${doctorName} - atiende a: ${patientName}`,
                start: appointment.date_appointmen + 'T' + appointment.time_appointmen,
                end: appointment.date_end_appointmen && appointment.time_end_appointmen
                    ? appointment.date_end_appointmen + 'T' + appointment.time_end_appointmen
                    : null,
                className: statusClass(appointment.status),
                correlative: appointment.correlative,
                status: appointment.status,
                description: appointment.description || '',
                details: appointment.details || '',
                message: appointment.message || '',
                telephone: appointment.telephone || '',
                email: appointment.email || '',
                date_appointmen: appointment.date_appointmen,
                time_appointmen: appointment.time_appointmen,
                date_end_appointmen: appointment.date_end_appointmen,
                time_end_appointmen: appointment.time_end_appointmen,
                patient_name: patientName,
                doctor_name: doctorName,
            };
        });
    };

    const dateFormat = (dt) => {
        dt = new Date(dt);
        const month = dt.getMonth() + 1 < 10 ? '0' + (dt.getMonth() + 1) : dt.getMonth() + 1;
        const date = dt.getDate() < 10 ? '0' + dt.getDate() : dt.getDate();
        const hours = dt.getHours() < 10 ? '0' + dt.getHours() : dt.getHours();
        const mins = dt.getMinutes() < 10 ? '0' + dt.getMinutes() : dt.getMinutes();
        dt = dt.getFullYear() + '-' + month + '-' + date + 'T' + hours + ':' + mins;
        return dt;
    };

    // Se muestran las fechas tal como estan guardadas para no arrastrar
    // desfases de zona horaria al abrir el modal.
    const displayDate = (value) => {
        if (!value) {
            return '—';
        }

        const parts = String(value).slice(0, 10).split('-');

        if (parts.length !== 3) {
            return String(value);
        }

        return `${parts[2]}/${parts[1]}/${parts[0]}`;
    };

    const displayTime = (value) => (value ? String(value).slice(0, 5) : '—');

    const todayString = () => {
        const dt = new Date();
        const pad = (number) => String(number).padStart(2, '0');

        return `${dt.getFullYear()}-${pad(dt.getMonth() + 1)}-${pad(dt.getDate())}`;
    };

    /* ------------------------------ Ver cita ------------------------------ */

    const isDetailModal = ref(false);
    const detail = ref(null);

    const openDetailModal = (data) => {
        if (!data?.event) {
            return;
        }

        const event = JSON.parse(JSON.stringify(data.event));
        const extended = event.extendedProps || {};

        detail.value = {
            id: event.id,
            correlative: extended.correlative,
            status: extended.status,
            patient_name: extended.patient_name,
            doctor_name: extended.doctor_name,
            date: extended.date_appointmen,
            start: extended.time_appointmen,
            end: extended.time_end_appointmen,
            description: extended.description,
            details: extended.details,
            message: extended.message,
            telephone: extended.telephone,
            email: extended.email,
        };
        isDetailModal.value = true;
    };

    const closeDetailModal = () => {
        isDetailModal.value = false;
    };

    /* ---------------------------- Nueva cita ----------------------------- */

    const isCreateModal = ref(false);
    const durationOptions = [
        { value: '15', label: '15 minutos' },
        { value: '30', label: '30 minutos' },
        { value: '45', label: '45 minutos' },
        { value: '60', label: '1 hora' },
        { value: '90', label: '1:30 horas' },
        { value: '120', label: '2 horas' },
    ];

    // Quien no administra agendas solo puede elegir su propio doctor.
    const doctorChoices = computed(() => {
        if (props.canChooseDoctor) {
            return props.doctors;
        }

        return props.currentDoctor ? [props.currentDoctor] : [];
    });

    const canCreateAppointment = computed(() => props.canChooseDoctor || !!props.currentDoctor);

    const form = useForm({
        patient_id: null,
        doctor_id: null,
        date_appointmen: null,
        time_appointmen: null,
        duration_minutes: 30,
        description: null,
        details: null,
        message: null,
    });

    const openCreateModal = () => {
        form.clearErrors();
        form.reset();
        form.duration_minutes = 30;
        form.date_appointmen = todayString();
        form.doctor_id = props.canChooseDoctor ? null : props.currentDoctor;
        isCreateModal.value = true;
    };

    const closeCreateModal = () => {
        form.clearErrors();
        form.reset();
        isCreateModal.value = false;
    };

    const submitAppointment = () => {
        form.clearErrors();

        // El endpoint de la Agenda valida el horario, el rol del usuario y
        // registra la cita en dent_appointments, que es la tabla de este calendario.
        axios.post(route('heal_agendas_appointments_store'), form.data()).then(() => {
            closeCreateModal();
            showMessage('La cita se registró correctamente.');
            router.reload({ only: ['eventsDB'] });
        }).catch((error) => {
            if (error.response?.status === 422) {
                form.setError(error.response.data.errors || {});
                const message = Object.values(error.response.data.errors || {}).flat().join(' ');
                showMessage(message || 'Revisa los datos de la cita.', 'error');
                return;
            }

            showMessage('No se pudo registrar la cita.', 'error');
        });
    };

    const showMessage = (msg = '', type = 'success') => {
        const toast = Swal.mixin({
            toast: true,
            position: 'top',
            showConfirmButton: false,
            timer: 3000,
            customClass: { container: 'toast' },
        });
        toast.fire({
            icon: type,
            title: msg,
            padding: '10px 20px',
        });
    };
</script>

<template>
    <AppLayout title="Calendario de citas">
        <div>
            <div class="panel">
                <div class="mb-5">
                    <div class="mb-4 flex items-center sm:flex-row flex-col sm:justify-between justify-center">
                        <div class="sm:mb-0 mb-4">
                            <div class="text-lg font-semibold ltr:sm:text-left rtl:sm:text-right text-center">Calendario de citas</div>
                            <div class="flex items-center mt-2 flex-wrap sm:justify-start justify-center">
                                <div class="flex items-center ltr:mr-4 rtl:ml-4">
                                    <div class="h-2.5 w-2.5 rounded-sm ltr:mr-2 rtl:ml-2 bg-primary"></div>
                                    <div>Atendido</div>
                                </div>
                                <div class="flex items-center ltr:mr-4 rtl:ml-4">
                                    <div class="h-2.5 w-2.5 rounded-sm ltr:mr-2 rtl:ml-2 bg-success"></div>
                                    <div>Pendiente</div>
                                </div>
                                <div class="flex items-center ltr:mr-4 rtl:ml-4">
                                    <div class="h-2.5 w-2.5 rounded-sm ltr:mr-2 rtl:ml-2 bg-info"></div>
                                    <div>No concretada</div>
                                </div>
                                <div class="flex items-center">
                                    <div class="h-2.5 w-2.5 rounded-sm ltr:mr-2 rtl:ml-2 bg-danger"></div>
                                    <div>Cancelado</div>
                                </div>
                            </div>
                        </div>
                        <button
                            v-if="canCreateAppointment"
                            v-can="'heal_citas_nuevo'"
                            type="button"
                            class="btn btn-primary"
                            @click="openCreateModal()"
                        >
                            <icon-plus class="ltr:mr-2 rtl:ml-2" />
                            Agregar cita
                        </button>
                    </div>

                    <div v-if="!canChooseDoctor && !currentDoctor" class="mb-4 rounded-md bg-danger/10 px-4 py-3 text-sm text-danger">
                        Tu usuario no está vinculado a un doctor, por eso no se muestran citas.
                    </div>

                    <div class="calendar-wrapper">
                        <FullCalendar ref="calendar" :options="calendarOptions">
                            <template v-slot:eventContent="arg">
                                <div class="fc-event-main-frame flex items-center px-1 py-0.5 text-white">
                                    <div class="fc-event-time font-semibold px-0.5">
                                        {{ arg.timeText }}
                                    </div>
                                    <div class="fc-event-title-container">
                                        <div class="fc-event-title fc-sticky !font-medium px-0.5">
                                            {{ arg.event.title }}
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </FullCalendar>
                    </div>
                </div>
            </div>

            <!-- Modal de detalle de la cita -->
            <TransitionRoot appear :show="isDetailModal" as="template">
                <Dialog as="div" @close="closeDetailModal" class="relative z-[51]">
                    <TransitionChild
                        as="template"
                        enter="duration-300 ease-out"
                        enter-from="opacity-0"
                        enter-to="opacity-100"
                        leave="duration-200 ease-in"
                        leave-from="opacity-100"
                        leave-to="opacity-0"
                    >
                        <DialogOverlay class="fixed inset-0 bg-[black]/60" />
                    </TransitionChild>

                    <div class="fixed inset-0 overflow-y-auto">
                        <div class="flex min-h-full items-center justify-center px-4 py-8">
                            <TransitionChild
                                as="template"
                                enter="duration-300 ease-out"
                                enter-from="opacity-0 scale-95"
                                enter-to="opacity-100 scale-100"
                                leave="duration-200 ease-in"
                                leave-from="opacity-100 scale-100"
                                leave-to="opacity-0 scale-95"
                            >
                                <DialogPanel
                                    class="panel border-0 p-0 rounded-lg overflow-hidden w-full max-w-lg text-black dark:text-white-dark"
                                >
                                    <button
                                        type="button"
                                        class="absolute top-4 ltr:right-4 rtl:left-4 text-gray-400 hover:text-gray-800 dark:hover:text-gray-600 outline-none"
                                        @click="closeDetailModal"
                                    >
                                        <icon-x />
                                    </button>
                                    <div class="text-lg font-medium bg-[#fbfbfb] dark:bg-[#121c2c] ltr:pl-5 rtl:pr-5 py-3 ltr:pr-[50px] rtl:pl-[50px]">
                                        Detalle de la cita
                                    </div>
                                    <div v-if="detail" class="p-5">
                                        <div class="mb-4 flex items-center justify-between">
                                            <span class="text-sm font-semibold text-white-dark">
                                                Cita #{{ detail.correlative || detail.id }}
                                            </span>
                                            <span :class="statusBadgeClass(detail.status)">{{ statusLabel(detail.status) }}</span>
                                        </div>

                                        <dl class="space-y-3 text-sm">
                                            <div class="flex justify-between gap-4">
                                                <dt class="font-semibold">Paciente</dt>
                                                <dd class="text-right">{{ detail.patient_name || '—' }}</dd>
                                            </div>
                                            <div class="flex justify-between gap-4">
                                                <dt class="font-semibold">Doctor</dt>
                                                <dd class="text-right">{{ detail.doctor_name || '—' }}</dd>
                                            </div>
                                            <div class="flex justify-between gap-4">
                                                <dt class="font-semibold">Fecha</dt>
                                                <dd class="text-right">{{ displayDate(detail.date) }}</dd>
                                            </div>
                                            <div class="flex justify-between gap-4">
                                                <dt class="font-semibold">Horario</dt>
                                                <dd class="text-right">{{ displayTime(detail.start) }} - {{ displayTime(detail.end) }}</dd>
                                            </div>
                                            <div class="flex justify-between gap-4">
                                                <dt class="font-semibold">Motivo</dt>
                                                <dd class="text-right">{{ detail.description || '—' }}</dd>
                                            </div>
                                            <div v-if="detail.details" class="flex justify-between gap-4">
                                                <dt class="font-semibold">Detalle</dt>
                                                <dd class="text-right">{{ detail.details }}</dd>
                                            </div>
                                            <div v-if="detail.message" class="flex justify-between gap-4">
                                                <dt class="font-semibold">Mensaje</dt>
                                                <dd class="text-right">{{ detail.message }}</dd>
                                            </div>
                                            <div v-if="detail.telephone" class="flex justify-between gap-4">
                                                <dt class="font-semibold">Teléfono</dt>
                                                <dd class="text-right">{{ detail.telephone }}</dd>
                                            </div>
                                            <div v-if="detail.email" class="flex justify-between gap-4">
                                                <dt class="font-semibold">Correo</dt>
                                                <dd class="text-right">{{ detail.email }}</dd>
                                            </div>
                                        </dl>

                                        <div class="mt-8 flex justify-end items-center">
                                            <button type="button" class="btn btn-outline-danger" @click="closeDetailModal">Cerrar</button>
                                        </div>
                                    </div>
                                </DialogPanel>
                            </TransitionChild>
                        </div>
                    </div>
                </Dialog>
            </TransitionRoot>

            <!-- Modal para agregar una cita -->
            <TransitionRoot appear :show="isCreateModal" as="template">
                <Dialog as="div" @close="closeCreateModal" class="relative z-[51]">
                    <TransitionChild
                        as="template"
                        enter="duration-300 ease-out"
                        enter-from="opacity-0"
                        enter-to="opacity-100"
                        leave="duration-200 ease-in"
                        leave-from="opacity-100"
                        leave-to="opacity-0"
                    >
                        <DialogOverlay class="fixed inset-0 bg-[black]/60" />
                    </TransitionChild>

                    <div class="fixed inset-0 overflow-y-auto">
                        <div class="flex min-h-full items-center justify-center px-4 py-8">
                            <TransitionChild
                                as="template"
                                enter="duration-300 ease-out"
                                enter-from="opacity-0 scale-95"
                                enter-to="opacity-100 scale-100"
                                leave="duration-200 ease-in"
                                leave-from="opacity-100 scale-100"
                                leave-to="opacity-0 scale-95"
                            >
                                <DialogPanel
                                    class="panel border-0 p-0 rounded-lg overflow-hidden w-full max-w-2xl text-black dark:text-white-dark"
                                >
                                    <button
                                        type="button"
                                        class="absolute top-4 ltr:right-4 rtl:left-4 text-gray-400 hover:text-gray-800 dark:hover:text-gray-600 outline-none"
                                        @click="closeCreateModal"
                                    >
                                        <icon-x />
                                    </button>
                                    <div class="text-lg font-medium bg-[#fbfbfb] dark:bg-[#121c2c] ltr:pl-5 rtl:pr-5 py-3 ltr:pr-[50px] rtl:pl-[50px]">
                                        Nueva cita
                                    </div>
                                    <div class="p-5">
                                        <form @submit.prevent="submitAppointment">
                                            <div class="mb-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                                                <div>
                                                    <label class="mb-1 block font-semibold" for="patient_id">Paciente :</label>
                                                    <Multiselect
                                                        id="patient_id"
                                                        v-model="form.patient_id"
                                                        :options="patients"
                                                        class="custom-multiselect"
                                                        :searchable="true"
                                                        placeholder="Buscar paciente"
                                                        selected-label="seleccionado"
                                                        select-label="Elegir"
                                                        deselect-label="Quitar"
                                                        label="name"
                                                        track-by="code"
                                                    />
                                                    <InputError :message="form.errors.patient_id_value || form.errors.patient_id" class="mt-1" />
                                                </div>
                                                <div>
                                                    <label class="mb-1 block font-semibold" for="doctor_id">Doctor :</label>
                                                    <Multiselect
                                                        id="doctor_id"
                                                        v-model="form.doctor_id"
                                                        :options="doctorChoices"
                                                        class="custom-multiselect"
                                                        :disabled="!canChooseDoctor"
                                                        :searchable="true"
                                                        placeholder="Buscar doctor"
                                                        selected-label="seleccionado"
                                                        select-label="Elegir"
                                                        deselect-label="Quitar"
                                                        label="name"
                                                        track-by="code"
                                                    />
                                                    <p v-if="!canChooseDoctor" class="mt-1 text-xs text-white-dark">
                                                        Tu usuario solo puede agendar en su propia agenda.
                                                    </p>
                                                    <InputError :message="form.errors.doctor_id_value || form.errors.doctor_id" class="mt-1" />
                                                </div>
                                            </div>

                                            <div class="mb-5 grid grid-cols-1 gap-4 md:grid-cols-3">
                                                <div>
                                                    <label class="mb-1 block font-semibold" for="date_appointmen">Día :</label>
                                                    <input
                                                        id="date_appointmen"
                                                        v-model="form.date_appointmen"
                                                        type="date"
                                                        class="form-input"
                                                    />
                                                    <InputError :message="form.errors.date_appointmen" class="mt-1" />
                                                </div>
                                                <div>
                                                    <label class="mb-1 block font-semibold" for="time_appointmen">Hora :</label>
                                                    <input
                                                        id="time_appointmen"
                                                        v-model="form.time_appointmen"
                                                        type="time"
                                                        step="900"
                                                        class="form-input"
                                                    />
                                                    <InputError :message="form.errors.time_appointmen" class="mt-1" />
                                                </div>
                                                <div>
                                                    <label class="mb-1 block font-semibold" for="duration_minutes">Duración :</label>
                                                    <select id="duration_minutes" v-model="form.duration_minutes" class="form-select">
                                                        <option v-for="option in durationOptions" :key="option.value" :value="option.value">
                                                            {{ option.label }}
                                                        </option>
                                                    </select>
                                                    <InputError :message="form.errors.duration_minutes" class="mt-1" />
                                                </div>
                                            </div>

                                            <div class="mb-5">
                                                <label class="mb-1 block font-semibold" for="description">Motivo :</label>
                                                <input
                                                    id="description"
                                                    v-model="form.description"
                                                    type="text"
                                                    maxlength="255"
                                                    class="form-input"
                                                    placeholder="Motivo de la consulta"
                                                />
                                                <InputError :message="form.errors.description" class="mt-1" />
                                            </div>

                                            <div class="mb-5">
                                                <label class="mb-1 block font-semibold" for="details">Detalle :</label>
                                                <textarea
                                                    id="details"
                                                    v-model="form.details"
                                                    maxlength="255"
                                                    class="form-textarea min-h-[100px]"
                                                    placeholder=""
                                                ></textarea>
                                                <InputError :message="form.errors.details" class="mt-1" />
                                            </div>

                                            <div class="mb-5">
                                                <label class="mb-1 block font-semibold" for="message">Mensaje :</label>
                                                <textarea
                                                    id="message"
                                                    v-model="form.message"
                                                    maxlength="500"
                                                    class="form-textarea min-h-[100px]"
                                                    placeholder=""
                                                ></textarea>
                                                <InputError :message="form.errors.message" class="mt-1" />
                                            </div>

                                            <div class="flex justify-end items-center mt-8">
                                                <button type="button" class="btn btn-outline-danger" @click="closeCreateModal">Cancelar</button>
                                                <button type="submit" class="btn btn-primary ltr:ml-4 rtl:mr-4" :disabled="form.processing">
                                                    Guardar
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </DialogPanel>
                            </TransitionChild>
                        </div>
                    </div>
                </Dialog>
            </TransitionRoot>
        </div>

    </AppLayout>
</template>
