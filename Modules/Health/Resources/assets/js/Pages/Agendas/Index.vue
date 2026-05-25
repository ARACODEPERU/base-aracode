<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import { computed, onMounted, ref, watch } from 'vue';
import { Dialog, DialogOverlay, DialogPanel, TransitionChild, TransitionRoot } from '@headlessui/vue';
import { router, useForm } from '@inertiajs/vue3';
import Multiselect from '@suadelabs/vue3-multiselect';
import '@suadelabs/vue3-multiselect/dist/vue3-multiselect.css';
import Swal from 'sweetalert2';
import InputError from '@/Components/InputError.vue';
import IconCalendar from '@/Components/vristo/icon/icon-calendar.vue';
import IconClock from '@/Components/vristo/icon/icon-clock.vue';
import IconPlus from '@/Components/vristo/icon/icon-plus.vue';
import IconX from '@/Components/vristo/icon/icon-x.vue';
import PendingSignatureReminder from '../../Components/PendingSignatureReminder.vue';

const props = defineProps({
    doctors: {
        type: Array,
        default: () => [],
    },
    patients: {
        type: Array,
        default: () => [],
    },
    currentDoctor: {
        type: Object,
        default: null,
    },
    canChooseAppointmentDoctor: {
        type: Boolean,
        default: true,
    },
    selectedDoctorId: {
        type: Number,
        default: null,
    },
    selectedDate: {
        type: String,
        default: '',
    },
    preselectedPatientId: {
        type: Number,
        default: null,
    },
    normalStart: {
        type: String,
        default: '08:00',
    },
    normalEnd: {
        type: String,
        default: '20:00',
    },
    slotMinutes: {
        type: Number,
        default: 15,
    },
    attentionBlockMinutes: {
        type: Number,
        default: 60,
    },
    pendingSignatures: {
        type: Array,
        default: () => [],
    },
});

const selectedDate = ref(props.selectedDate || new Date().toISOString().slice(0, 10));
const selectedDoctor = ref(props.doctors.find((doctor) => doctor.code === props.selectedDoctorId) || props.currentDoctor || props.doctors[0] || null);
const events = ref([]);
const freeSlots = ref([]);
const punctuality = ref({
    early: [],
    on_time: [],
    late: [],
    grace_minutes: 5,
});
const loadingAgenda = ref(false);
const appointmentModal = ref(false);
const availabilityLoading = ref(false);
const modalFreeSlots = ref([]);
const durationMode = ref('15');
const durationOptions = [
    { value: '15', label: '15 min' },
    { value: '30', label: '30 min' },
    { value: '45', label: '45 min' },
    { value: '60', label: '1 hora' },
    { value: 'custom', label: 'Más tiempo' },
];

const preselectedPatient = computed(() => props.patients.find((patient) => patient.code === props.preselectedPatientId) || null);
const appointmentDoctor = computed(() => props.canChooseAppointmentDoctor ? selectedDoctor.value : props.currentDoctor);

const form = useForm({
    patient_id: preselectedPatient.value,
    doctor_id: appointmentDoctor.value,
    date_appointmen: selectedDate.value,
    time_appointmen: null,
    duration_minutes: props.slotMinutes,
    description: null,
    details: null,
    message: null,
});

const selectedDoctorName = computed(() => selectedDoctor.value?.name || 'Sin doctor');

const layoutedEvents = computed(() => {
    const sortedEvents = events.value
        .map((event) => ({
            ...event,
            start_minutes: minutesFromTime(timeFromDate(event.start)),
            end_minutes: minutesFromTime(timeFromDate(event.end)),
            lane: 0,
            lane_count: 1,
        }))
        .sort((a, b) => a.start_minutes - b.start_minutes || a.end_minutes - b.end_minutes);
    const groups = [];

    sortedEvents.forEach((event) => {
        const lastGroup = groups[groups.length - 1];

        if (!lastGroup || event.start_minutes >= lastGroup.end) {
            groups.push({ end: event.end_minutes, events: [event] });
            return;
        }

        lastGroup.events.push(event);
        lastGroup.end = Math.max(lastGroup.end, event.end_minutes);
    });

    groups.forEach((group) => {
        const laneEnds = [];

        group.events.forEach((event) => {
            let lane = laneEnds.findIndex((laneEnd) => event.start_minutes >= laneEnd);

            if (lane === -1) {
                lane = laneEnds.length;
            }

            laneEnds[lane] = event.end_minutes;
            event.lane = lane;
        });

        const laneCount = Math.max(1, laneEnds.length);
        group.events.forEach((event) => {
            event.lane_count = laneCount;
        });
    });

    return sortedEvents;
});

const agendaSlots = computed(() => {
    const slots = [];
    const normalStart = minutesFromTime(props.normalStart);
    const normalEnd = minutesFromTime(props.normalEnd);
    const eventStarts = layoutedEvents.value.map((event) => floorToSlot(event.start_minutes));
    const eventEnds = layoutedEvents.value.map((event) => ceilToSlot(event.end_minutes));
    const start = Math.min(normalStart, ...eventStarts);
    const end = Math.max(normalEnd, ...eventEnds);
    const freeKeys = new Set(freeSlots.value.map((slot) => slot.start));

    for (let minute = start; minute < end; minute += props.slotMinutes) {
        const time = timeFromMinutes(minute);
        slots.push({
            time,
            withinNormalHours: minute >= normalStart && minute < normalEnd,
            available: freeKeys.has(time),
            hasEventOverlap: layoutedEvents.value.some((event) => eventOverlapsSlot(event, minute)),
            startingEvents: layoutedEvents.value.filter((event) => slotStartForEvent(event.start) === time),
        });
    }

    return slots;
});

const statusLabel = (event) => {
    if (event.type === 'attention') {
        return event.status === 'signed' ? 'Atención firmada' : 'Atención registrada';
    }

    if (event.status === '2') {
        return 'Cita atendida';
    }

    if (event.status === '0') {
        return 'Cita cancelada';
    }

    if (event.status === '3') {
        return 'Cita no concretada';
    }

    return 'Cita pendiente';
};

const eventClass = (event) => {
    if (event.type === 'attention') {
        return 'border-info bg-info/10 text-info';
    }

    if (event.status === '0' || event.status === '3') {
        return 'border-danger bg-danger/10 text-danger';
    }

    if (event.status === '2') {
        return 'border-primary bg-primary/10 text-primary';
    }

    return 'border-success bg-success/10 text-success';
};

const canOpenAttention = (event) => event.type === 'appointment' && event.can_start_attention;

const arrivalLabel = (event) => {
    if (!event.arrival_status || event.arrival_minutes === null || event.arrival_minutes === undefined) {
        return null;
    }

    const minutes = Math.abs(Number(event.arrival_minutes || 0));

    if (event.arrival_status === 'early') {
        return `Llegó ${minutes} min antes`;
    }

    if (event.arrival_status === 'on_time') {
        return Number(event.arrival_minutes || 0) === 0 ? 'Llegó a la hora' : `Llegó ${minutes} min dentro de tolerancia`;
    }

    return `Llegó ${minutes} min tarde`;
};

const punctualityTitle = (key) => ({
    early: 'Llegaron antes',
    on_time: `Puntuales (0-${punctuality.value.grace_minutes} min)`,
    late: 'Llegaron tarde',
}[key]);

const punctualityClass = (key) => ({
    early: 'border-info bg-info/10 text-info',
    on_time: 'border-success bg-success/10 text-success',
    late: 'border-danger bg-danger/10 text-danger',
}[key]);

const openAttentionFromAppointment = (event) => {
    if (!canOpenAttention(event)) {
        return;
    }

    router.get(route('heal_attentions_create'), {
        appointment_id: event.source_id,
        patient_id: event.patient_id,
    });
};

const loadAgenda = () => {
    if (!selectedDoctor.value?.code || !selectedDate.value) {
        return;
    }

    loadingAgenda.value = true;
    axios.get(route('heal_agendas_day'), {
        params: {
            doctor_id: selectedDoctor.value.code,
            date: selectedDate.value,
        },
    }).then((response) => {
        events.value = response.data.events || [];
        freeSlots.value = response.data.free_slots || [];
        punctuality.value = response.data.punctuality || punctuality.value;
    }).finally(() => {
        loadingAgenda.value = false;
    });
};

const loadAvailability = () => {
    const doctor = form.doctor_id || appointmentDoctor.value;
    if (!doctor?.code || !form.date_appointmen) {
        modalFreeSlots.value = [];
        return;
    }

    availabilityLoading.value = true;
    axios.get(route('heal_agendas_availability'), {
        params: {
            doctor_id: doctor.code,
            date: form.date_appointmen,
            duration_minutes: form.duration_minutes,
        },
    }).then((response) => {
        modalFreeSlots.value = response.data.free_slots || [];
    }).finally(() => {
        availabilityLoading.value = false;
    });
};

const openAppointmentModal = (time = null) => {
    form.clearErrors();
    form.patient_id = preselectedPatient.value || form.patient_id;
    form.doctor_id = appointmentDoctor.value;
    form.date_appointmen = selectedDate.value;
    form.time_appointmen = time;
    form.duration_minutes = props.slotMinutes;
    durationMode.value = String(props.slotMinutes);
    appointmentModal.value = true;
    loadAvailability();
};

const closeAppointmentModal = () => {
    appointmentModal.value = false;
};

const selectTime = (slot) => {
    form.time_appointmen = slot.start;
};

const submitWithAxios = () => {
    form.clearErrors();
    axios.post(route('heal_agendas_appointments_store'), form.data()).then((response) => {
        const savedDoctorId = form.doctor_id?.code;
        const savedDate = form.date_appointmen;
        const mustMoveAgenda = selectedDoctor.value?.code !== savedDoctorId || selectedDate.value !== savedDate;

        if (mustMoveAgenda) {
            selectedDoctor.value = props.doctors.find((doctor) => doctor.code === savedDoctorId) || selectedDoctor.value;
            selectedDate.value = savedDate;
        } else {
            events.value = response.data.events || [];
            freeSlots.value = response.data.free_slots || [];
            punctuality.value = response.data.punctuality || punctuality.value;
        }

        closeAppointmentModal();
        showMessage('La cita se registró correctamente.');
    }).catch((error) => {
        if (error.response?.status === 422) {
            form.setError(error.response.data.errors || {});
            const message = Object.values(error.response.data.errors || {}).flat().join(' ');
            showMessage(message || 'Revisa los datos de la cita.', 'error');
            loadAvailability();
        }
    });
};

const confirmAndSave = async () => {
    if (isOutsideNormalHours(form.time_appointmen)) {
        const result = await Swal.fire({
            icon: 'warning',
            title: 'Fuera del horario normal',
            text: `Se está agendando fuera del horario normal (${formatTimeLabel(props.normalStart)} a ${formatTimeLabel(props.normalEnd)}).`,
            showCancelButton: true,
            confirmButtonText: 'Agendar de todos modos',
            cancelButtonText: 'Cancelar',
            customClass: {
                popup: 'sweet-alerts',
                confirmButton: 'btn btn-warning',
                cancelButton: 'btn btn-dark ltr:mr-3 rtl:ml-3',
            },
            buttonsStyling: false,
            reverseButtons: true,
            padding: '2em',
        });

        if (!result.isConfirmed) {
            return;
        }
    }

    submitWithAxios();
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

const minutesFromTime = (time) => {
    const [hour, minute] = String(time || '00:00').slice(0, 5).split(':').map(Number);

    return (hour * 60) + minute;
};

const timeFromMinutes = (minutes) => {
    const hour = Math.floor(minutes / 60);
    const minute = minutes % 60;

    return `${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}`;
};

const timeFromDate = (value) => String(value || '').slice(11, 16);

const floorToSlot = (minutes) => Math.floor(minutes / props.slotMinutes) * props.slotMinutes;

const ceilToSlot = (minutes) => Math.ceil(minutes / props.slotMinutes) * props.slotMinutes;

const slotStartForEvent = (value) => timeFromMinutes(floorToSlot(minutesFromTime(timeFromDate(value))));

const eventOverlapsSlot = (event, slotStartMinutes) => {
    const slotEndMinutes = slotStartMinutes + props.slotMinutes;
    const eventStartMinutes = minutesFromTime(timeFromDate(event.start));
    const eventEndMinutes = minutesFromTime(timeFromDate(event.end));

    return eventStartMinutes < slotEndMinutes && eventEndMinutes > slotStartMinutes;
};

const formatTimeLabel = (time) => {
    const minutes = minutesFromTime(time);
    const hour24 = Math.floor(minutes / 60);
    const minute = minutes % 60;
    const period = hour24 >= 12 ? 'PM' : 'AM';
    const hour12 = hour24 % 12 || 12;

    return `${hour12}:${String(minute).padStart(2, '0')} ${period}`;
};

const formatEventTime = (event) => `${formatTimeLabel(timeFromDate(event.start))} - ${formatTimeLabel(timeFromDate(event.end))}`;

const eventDurationMinutes = (event) => {
    const start = minutesFromTime(timeFromDate(event.start));
    const end = minutesFromTime(timeFromDate(event.end));

    return Math.max(props.slotMinutes, end - start);
};

const eventCardStyle = (event) => {
    const slotHeight = 58;
    const rowGap = 8;
    const laneGap = 8;
    const slotStart = floorToSlot(event.start_minutes ?? minutesFromTime(timeFromDate(event.start)));
    const minuteOffset = Math.max(0, (event.start_minutes ?? minutesFromTime(timeFromDate(event.start))) - slotStart);
    const top = 4 + ((minuteOffset / props.slotMinutes) * slotHeight);
    const height = Math.max(slotHeight - rowGap, (eventDurationMinutes(event) / props.slotMinutes) * slotHeight - rowGap);
    const laneCount = Math.max(1, Number(event.lane_count || 1));
    const lane = Math.min(Number(event.lane || 0), laneCount - 1);
    const laneWidth = `(100% - 2rem - ${laneGap * (laneCount - 1)}px) / ${laneCount}`;

    return {
        top: `${top}px`,
        minHeight: `${height}px`,
        left: `calc(1rem + (${laneWidth} + ${laneGap}px) * ${lane})`,
        width: `calc(${laneWidth})`,
    };
};

const isOutsideNormalHours = (time) => {
    if (!time) {
        return false;
    }

    const start = minutesFromTime(time);
    const end = start + Number(form.duration_minutes || props.slotMinutes);

    return start < minutesFromTime(props.normalStart) || end > minutesFromTime(props.normalEnd);
};

watch([selectedDoctor, selectedDate], () => {
    loadAgenda();
});

watch(() => form.doctor_id, loadAvailability);
watch(() => form.date_appointmen, loadAvailability);
watch(() => form.duration_minutes, loadAvailability);
watch(durationMode, (value) => {
    if (value === 'custom') {
        form.duration_minutes = Math.max(Number(form.duration_minutes || 60), 75);
        return;
    }

    form.duration_minutes = Number(value);
});

onMounted(loadAgenda);
</script>

<template>
    <AppLayout title="Agendas">
        <PendingSignatureReminder :items="pendingSignatures" />

        <ul class="flex space-x-2 rtl:space-x-reverse">
            <li>
                <a href="javascript:;" class="text-primary hover:underline">Salud</a>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>Agendas</span>
            </li>
        </ul>

        <div class="pt-5 space-y-5">
            <div class="panel">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h2 class="text-xl font-semibold">Agendas</h2>
                        <p class="mt-1 text-sm text-white-dark">
                            Agenda diaria de {{ selectedDoctorName }}. Las atenciones bloquean {{ attentionBlockMinutes }} minutos.
                        </p>
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-[minmax(220px,320px)_180px_auto] sm:items-end">
                        <div>
                            <label class="mb-1 block text-sm font-semibold">Doctor</label>
                            <Multiselect
                                v-model="selectedDoctor"
                                :options="doctors"
                                class="custom-multiselect"
                                :searchable="true"
                                placeholder="Buscar doctor"
                                selected-label="seleccionado"
                                select-label="Elegir"
                                deselect-label="Quitar"
                                label="name"
                                track-by="code"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-semibold">Fecha</label>
                            <input v-model="selectedDate" type="date" class="form-input" />
                        </div>
                        <button type="button" class="btn btn-primary" @click="openAppointmentModal()">
                            <IconPlus class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                            Nueva cita
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 xl:grid-cols-[1fr_320px]">
                <div class="panel p-0 overflow-hidden">
                    <div class="flex items-center justify-between border-b border-[#e0e6ed] px-4 py-3 dark:border-[#1b2e4b]">
                        <div class="flex items-center gap-2 font-semibold">
                            <IconCalendar class="h-5 w-5 text-primary" />
                            {{ selectedDate }}
                        </div>
                        <div class="flex flex-wrap items-center gap-3 text-xs text-white-dark">
                            <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-sm bg-success"></span>Cita pendiente</span>
                            <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-sm bg-primary"></span>Cita atendida</span>
                            <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-sm bg-danger"></span>No concretada</span>
                            <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-sm bg-info"></span>Atención</span>
                        </div>
                    </div>

                    <div v-if="loadingAgenda" class="p-8 text-center text-white-dark">
                        Cargando agenda...
                    </div>
                    <div v-else class="divide-y divide-[#e0e6ed] dark:divide-[#1b2e4b]">
                        <div
                            v-for="slot in agendaSlots"
                            :key="slot.time"
                            class="grid min-h-[58px] grid-cols-[96px_1fr] hover:bg-gray-50 dark:hover:bg-white/5"
                            :class="{ 'bg-warning/5': !slot.withinNormalHours }"
                        >
                            <div class="border-r border-[#e0e6ed] px-4 py-3 text-sm font-semibold text-white-dark dark:border-[#1b2e4b]">
                                <div>{{ formatTimeLabel(slot.time) }}</div>
                                <div v-if="!slot.withinNormalHours" class="mt-1 text-[10px] font-normal text-warning">Fuera de horario</div>
                            </div>
                            <div class="relative flex flex-col gap-2 px-4 py-2 sm:flex-row sm:items-center">
                                <button
                                    v-if="slot.withinNormalHours && slot.available && !slot.hasEventOverlap"
                                    type="button"
                                    class="inline-flex w-max items-center rounded border border-dashed border-primary px-3 py-1.5 text-xs font-semibold text-primary hover:bg-primary hover:text-white"
                                    @click="openAppointmentModal(slot.time)"
                                >
                                    <IconPlus class="h-3.5 w-3.5 ltr:mr-1 rtl:ml-1" />
                                    Agendar
                                </button>
                                <span v-else-if="!slot.hasEventOverlap" class="text-xs text-white-dark">
                                    {{ slot.withinNormalHours ? 'No disponible' : 'Sin citas fuera de horario' }}
                                </span>

                                <div
                                    v-for="event in slot.startingEvents"
                                    :key="event.id"
                                    class="absolute z-10 overflow-hidden rounded border px-3 py-2 shadow-sm"
                                    :class="[eventClass(event), { 'cursor-pointer hover:ring-2 hover:ring-primary/40': canOpenAttention(event) }]"
                                    :style="eventCardStyle(event)"
                                    @click="openAttentionFromAppointment(event)"
                                >
                                    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="font-semibold">{{ event.title }} - {{ event.patient || 'Paciente no registrado' }}</div>
                                        <div class="text-xs">{{ formatEventTime(event) }}</div>
                                    </div>
                                    <div class="mt-1 text-xs opacity-90">{{ statusLabel(event) }}</div>
                                    <div v-if="arrivalLabel(event)" class="mt-1 text-xs opacity-90">{{ arrivalLabel(event) }}</div>
                                    <div v-if="event.no_show_at" class="mt-1 text-xs opacity-80">Marcada no concretada: {{ formatTimeLabel(timeFromDate(event.no_show_at)) }}</div>
                                    <div v-if="event.details" class="mt-1 text-xs opacity-80">{{ event.details }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-5">
                    <div class="panel">
                        <h3 class="mb-3 font-semibold">Disponibilidad</h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-white-dark">Horario normal</span>
                                <span class="font-semibold">{{ formatTimeLabel(normalStart) }} - {{ formatTimeLabel(normalEnd) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-white-dark">Duración de cita</span>
                                <span class="font-semibold">Desde 15 min</span>
                            </div>
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-2">
                            <button
                                v-for="slot in freeSlots.slice(0, 12)"
                                :key="slot.start"
                                type="button"
                                class="btn btn-outline-primary btn-sm"
                                @click="openAppointmentModal(slot.start)"
                            >
                                {{ formatTimeLabel(slot.start) }}
                            </button>
                        </div>
                        <p v-if="freeSlots.length === 0" class="mt-4 text-sm text-white-dark">
                            No hay espacios libres dentro del horario normal.
                        </p>
                    </div>

                    <div class="panel">
                        <h3 class="mb-3 font-semibold">Puntualidad del día</h3>
                        <div class="space-y-4">
                            <div v-for="key in ['early', 'on_time', 'late']" :key="key">
                                <div class="mb-2 flex items-center justify-between text-sm font-semibold">
                                    <span>{{ punctualityTitle(key) }}</span>
                                    <span>{{ punctuality[key]?.length || 0 }}</span>
                                </div>
                                <div v-if="punctuality[key]?.length" class="space-y-2">
                                    <div
                                        v-for="item in punctuality[key]"
                                        :key="`${key}-${item.patient_id}-${item.attention_at}`"
                                        class="rounded border px-3 py-2 text-xs"
                                        :class="punctualityClass(key)"
                                    >
                                        <div class="font-semibold">{{ item.patient || 'Paciente no registrado' }}</div>
                                        <div>Cita: {{ formatTimeLabel(timeFromDate(item.appointment_at)) }} | Atención: {{ formatTimeLabel(timeFromDate(item.attention_at)) }}</div>
                                        <div v-if="item.minutes < 0">{{ Math.abs(item.minutes) }} min antes</div>
                                        <div v-else-if="item.minutes === 0">A la hora exacta</div>
                                        <div v-else>{{ item.minutes }} min después</div>
                                    </div>
                                </div>
                                <p v-else class="text-xs text-white-dark">Sin pacientes en este grupo.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <TransitionRoot appear :show="appointmentModal" as="template">
            <Dialog as="div" class="relative z-[51]" @close="closeAppointmentModal">
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
                            <DialogPanel class="panel w-full max-w-5xl overflow-hidden rounded-lg border-0 p-0 text-black dark:text-white-dark">
                                <button
                                    type="button"
                                    class="absolute top-4 text-gray-400 outline-none hover:text-gray-800 ltr:right-4 rtl:left-4 dark:hover:text-gray-600"
                                    @click="closeAppointmentModal"
                                >
                                    <IconX />
                                </button>
                                <div class="bg-[#fbfbfb] py-3 text-lg font-medium ltr:pl-5 ltr:pr-[50px] rtl:pl-[50px] rtl:pr-5 dark:bg-[#121c2c]">
                                    Nueva cita
                                </div>
                                <form class="grid grid-cols-1 gap-5 p-5 lg:grid-cols-[1fr_320px]" @submit.prevent="confirmAndSave">
                                    <div class="space-y-4">
                                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                            <div>
                                                <label class="mb-1 block font-semibold">Paciente</label>
                                                <Multiselect
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
                                                <label class="mb-1 block font-semibold">Doctor</label>
                                                <Multiselect
                                                    v-model="form.doctor_id"
                                                    :options="canChooseAppointmentDoctor ? doctors : [currentDoctor]"
                                                    class="custom-multiselect"
                                                    :disabled="!canChooseAppointmentDoctor"
                                                    :searchable="true"
                                                    placeholder="Buscar doctor"
                                                    selected-label="seleccionado"
                                                    select-label="Elegir"
                                                    deselect-label="Quitar"
                                                    label="name"
                                                    track-by="code"
                                                />
                                                <p v-if="!canChooseAppointmentDoctor" class="mt-1 text-xs text-white-dark">
                                                    Tu usuario solo puede agendar en su propia agenda.
                                                </p>
                                                <InputError :message="form.errors.doctor_id_value || form.errors.doctor_id" class="mt-1" />
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                            <div>
                                                <label class="mb-1 block font-semibold">Día</label>
                                                <input v-model="form.date_appointmen" type="date" class="form-input" />
                                                <InputError :message="form.errors.date_appointmen" class="mt-1" />
                                            </div>
                                            <div>
                                                <label class="mb-1 block font-semibold">Hora</label>
                                                <input v-model="form.time_appointmen" type="time" class="form-input" step="900" />
                                                <InputError :message="form.errors.time_appointmen" class="mt-1" />
                                            </div>
                                            <div>
                                                <label class="mb-1 block font-semibold">Duración</label>
                                                <select v-model="durationMode" class="form-select">
                                                    <option
                                                        v-for="option in durationOptions"
                                                        :key="option.value"
                                                        :value="option.value"
                                                    >
                                                        {{ option.label }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div v-if="durationMode === 'custom'" class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                            <div>
                                                <label class="mb-1 block font-semibold">Minutos</label>
                                                <input
                                                    v-model.number="form.duration_minutes"
                                                    type="number"
                                                    min="75"
                                                    max="240"
                                                    step="15"
                                                    class="form-input"
                                                />
                                                <p class="mt-1 text-xs text-white-dark">Usa múltiplos de 15 minutos. Máximo 240 minutos.</p>
                                            </div>
                                        </div>

                                        <div>
                                            <label class="mb-1 block font-semibold">Motivo</label>
                                            <input v-model="form.description" type="text" class="form-input" placeholder="Motivo de la cita" />
                                            <InputError :message="form.errors.description" class="mt-1" />
                                        </div>

                                        <div>
                                            <label class="mb-1 block font-semibold">Detalle</label>
                                            <textarea v-model="form.details" rows="3" class="form-textarea" placeholder="Notas opcionales"></textarea>
                                            <InputError :message="form.errors.details" class="mt-1" />
                                        </div>
                                    </div>

                                    <div class="rounded border border-[#e0e6ed] p-4 dark:border-[#1b2e4b]">
                                        <div class="mb-3 flex items-center gap-2 font-semibold">
                                            <IconClock class="h-5 w-5 text-primary" />
                                            Horarios libres
                                        </div>
                                        <div v-if="availabilityLoading" class="text-sm text-white-dark">Consultando agenda...</div>
                                        <div v-else class="grid grid-cols-2 gap-2">
                                            <button
                                                v-for="slot in modalFreeSlots"
                                                :key="slot.start"
                                                type="button"
                                                class="btn btn-outline-primary btn-sm"
                                                :class="{ 'bg-primary text-white': form.time_appointmen === slot.start }"
                                                @click="selectTime(slot)"
                                            >
                                                {{ formatTimeLabel(slot.start) }}
                                            </button>
                                        </div>
                                        <p v-if="!availabilityLoading && modalFreeSlots.length === 0" class="text-sm text-white-dark">
                                            No hay espacios libres dentro del horario normal. Puedes escribir una hora manual y se avisará si está fuera del horario normal.
                                        </p>
                                    </div>

                                    <div class="flex justify-end gap-3 lg:col-span-2">
                                        <button type="button" class="btn btn-outline-danger" @click="closeAppointmentModal">Cancelar</button>
                                        <button type="submit" class="btn btn-primary">Guardar cita</button>
                                    </div>
                                </form>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
    </AppLayout>
</template>
