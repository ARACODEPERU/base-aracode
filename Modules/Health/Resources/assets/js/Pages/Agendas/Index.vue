<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
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
import EstablishmentBadge from '../../Components/EstablishmentBadge.vue';

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
    workingHoursRanges: {
        type: Array,
        default: () => [
            { start: '08:00', end: '20:00' },
        ],
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
const daysData = ref({});
const dayCount = ref(3);
const loadingAgenda = ref(false);
const draggedEvent = ref(null);
const dragOverTarget = ref(null);
const dragSource = ref(null);
const dropSuccessTarget = ref(null);
const moveLoading = ref(false);
const updateDayCount = () => {
    dayCount.value = window.innerWidth >= 1400 ? 5 : 3;
};

const displayDates = computed(() => {
    if (!selectedDate.value) return [];
    const dates = [];
    const start = new Date(selectedDate.value + 'T12:00:00');
    for (let i = 0; i < dayCount.value; i++) {
        const d = new Date(start);
        d.setDate(d.getDate() + i);
        dates.push(d.toISOString().slice(0, 10));
    }
    return dates;
});

const selectedDayData = computed(() => daysData.value[selectedDate.value] || { events: [], free_slots: [], punctuality: { early: [], on_time: [], late: [], grace_minutes: 5 } });

const globalAgendaRange = computed(() => {
    const normalStart = minutesFromTime(props.normalStart);
    const normalEnd = minutesFromTime(props.normalEnd);
    let globalStart = normalStart;
    let globalEnd = normalEnd;

    displayDates.value.forEach((dateStr) => {
        const dayData = daysData.value[dateStr];
        if (!dayData?.events?.length) return;
        dayData.events.forEach((event) => {
            const eventStart = minutesFromTime(timeFromDate(event.start));
            const eventEnd = minutesFromTime(timeFromDate(event.end));
            if (eventStart < globalStart) globalStart = floorToSlot(eventStart);
            if (eventEnd > globalEnd) globalEnd = ceilToSlot(eventEnd);
        });
    });

    return { start: globalStart, end: globalEnd };
});

const toLocalDateStr = (date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;

const formatDayHeader = (dateStr) => {
    const d = new Date(dateStr + 'T12:00:00');
    const today = new Date();
    const todayStr = toLocalDateStr(today);
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    const tomorrowStr = toLocalDateStr(tomorrow);
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    const yesterdayStr = toLocalDateStr(yesterday);

    const dayName = d.toLocaleDateString('es-ES', { weekday: 'long' });
    const dayNameCapitalized = dayName.charAt(0).toUpperCase() + dayName.slice(1);

    if (dateStr === todayStr) {
        return 'Hoy';
    }
    if (dateStr === tomorrowStr) {
        return 'Mañana';
    }
    if (dateStr === yesterdayStr) {
        return 'Ayer';
    }
    return dayNameCapitalized;
};
const appointmentModal = ref(false);
const availabilityLoading = ref(false);
const modalFreeSlots = ref([]);
const durationMode = ref('15');
const durationOptions = [
    { value: '15', label: '15 min' },
    { value: '30', label: '30 min' },
    { value: '45', label: '45 min' },
    { value: '60', label: '1 hora' },
    { value: '90', label: '1:30 h' },
    { value: '120', label: '2 horas' },
    { value: '150', label: '2:30 h' },
    { value: '180', label: '3 horas' },
    { value: '240', label: '4 horas' },
    { value: 'custom', label: 'Más tiempo' },
];

const sidebarDuration = ref(props.slotMinutes);
const sidebarFreeSlots = ref([]);
const sidebarAvailabilityLoading = ref(false);

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

const getDayLayoutedEvents = (dayData) => {
    if (!dayData?.events?.length) return [];
    const sortedEvents = dayData.events
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
};

const getDaySlots = (dayData, globalStart, globalEnd) => {
    if (!dayData?.free_slots) return [];
    const events = dayData.events || [];
    const freeSlots = dayData.free_slots || [];
    const layoutedEvents = getDayLayoutedEvents(dayData);
    const slots = [];
    const normalStart = minutesFromTime(props.normalStart);
    const normalEnd = minutesFromTime(props.normalEnd);
    const start = globalStart ?? Math.min(normalStart, ...layoutedEvents.map((event) => floorToSlot(event.start_minutes)));
    const end = globalEnd ?? Math.max(normalEnd, ...layoutedEvents.map((event) => ceilToSlot(event.end_minutes)));
    const freeKeys = new Set(freeSlots.map((slot) => slot.start));

    for (let minute = start; minute < end; minute += props.slotMinutes) {
        const time = timeFromMinutes(minute);
        slots.push({
            time,
            withinNormalHours: isWithinWorkingHours(minute),
            available: freeKeys.has(time),
            hasEventOverlap: layoutedEvents.some((event) => eventOverlapsSlot(event, minute)),
            startingEvents: layoutedEvents.filter((event) => slotStartForEvent(event.start) === time),
            overlappingEventNames: layoutedEvents
                .filter((event) => eventOverlapsSlot(event, minute))
                .slice(0, 3)
                .map((e) => e.patient || 'Paciente'),
            totalOverlapping: layoutedEvents.filter((event) => eventOverlapsSlot(event, minute)).length,
        });
    }

    return slots;
};

const statusLabel = (event) => {
    if (event.type === 'attention' || event.status === '2') {
        return event.status === 'signed' ? 'Atención firmada' : 'Atención registrada';
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
    if (event.type === 'attention' || event.status === '2') {
        return 'border-info bg-info/10 text-info';
    }

    if (event.status === '0' || event.status === '3') {
        return 'border-danger bg-danger/10 text-danger';
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
    on_time: `Puntuales (0-${selectedDayData.value.punctuality.grace_minutes} min)`,
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

const loadAgenda = ({ silent = false } = {}) => {
    if (!selectedDoctor.value?.code || !selectedDate.value) {
        return Promise.resolve();
    }

    if (!silent) {
        loadingAgenda.value = true;
    }
    return axios.get(route('heal_agendas_day'), {
        params: {
            doctor_id: selectedDoctor.value.code,
            date: selectedDate.value,
            days: dayCount.value,
        },
    }).then((response) => {
        daysData.value = response.data || {};
    }).finally(() => {
        if (!silent) {
            loadingAgenda.value = false;
        }
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

const loadSidebarAvailability = () => {
    if (!selectedDoctor.value?.code || !selectedDate.value) {
        sidebarFreeSlots.value = [];
        return;
    }

    sidebarAvailabilityLoading.value = true;
    axios.get(route('heal_agendas_availability'), {
        params: {
            doctor_id: selectedDoctor.value.code,
            date: selectedDate.value,
            duration_minutes: sidebarDuration.value,
        },
    }).then((response) => {
        sidebarFreeSlots.value = response.data.free_slots || [];
    }).finally(() => {
        sidebarAvailabilityLoading.value = false;
    });
};

const openAppointmentModal = (time = null, duration = null) => {
    const dur = duration ?? props.slotMinutes;
    form.clearErrors();
    form.patient_id = preselectedPatient.value || form.patient_id;
    form.doctor_id = appointmentDoctor.value;
    form.date_appointmen = selectedDate.value;
    form.time_appointmen = time;
    form.duration_minutes = dur;
    durationMode.value = String(dur);
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
            loadAgenda();
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

const isWithinWorkingHours = (timeMinutes) => {
    const ranges = props.workingHoursRanges?.length ? props.workingHoursRanges : [{ start: props.normalStart, end: props.normalEnd }];
    return ranges.some((range) => {
        const start = minutesFromTime(range.start);
        const end = minutesFromTime(range.end);
        return timeMinutes >= start && timeMinutes < end;
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
        height: `${height}px`,
        left: `calc(1rem + (${laneWidth} + ${laneGap}px) * ${lane})`,
        width: `calc(${laneWidth})`,
    };
};

const dayCounts = (events = []) => {
    let attentions = 0;
    let appointments = 0;
    events.forEach((event) => {
        if (event.type === 'attention' || (event.type === 'appointment' && event.status === '2')) {
            attentions++;
        } else {
            appointments++;
        }
    });
    return { attentions, appointments };
};

const isDraggableEvent = (event) => event.type === 'appointment' && event.status === '1';

const onDragStart = (event, evt) => {
    if (!isDraggableEvent(event)) {
        evt.preventDefault();
        return;
    }
    draggedEvent.value = event;
    evt.dataTransfer.effectAllowed = 'move';
    evt.dataTransfer.setData('text/plain', event.id);

    // Guardar slot de origen para resaltarlo
    const srcDate = String(event.start || '').slice(0, 10);
    const srcTime = slotStartForEvent(event.start);
    dragSource.value = { dateStr: srcDate, time: srcTime };

    // Crear un ghost minimalista en vez de clonar toda la tarjeta
    const ghost = document.createElement('div');
    const duration = eventDurationMinutes(event);
    ghost.textContent = `${event.patient || 'Paciente'} · ${formatTimeLabel(timeFromDate(event.start))} - ${formatTimeLabel(timeFromDate(event.end))} (${duration} min)`;
    ghost.style.position = 'absolute';
    ghost.style.top = '-9999px';
    ghost.style.left = '-9999px';
    ghost.style.padding = '6px 14px';
    ghost.style.background = 'rgba(16, 185, 129, 0.92)';
    ghost.style.color = '#fff';
    ghost.style.fontSize = '13px';
    ghost.style.fontWeight = '600';
    ghost.style.fontFamily = 'Inter, system-ui, sans-serif';
    ghost.style.borderRadius = '8px';
    ghost.style.whiteSpace = 'nowrap';
    ghost.style.boxShadow = '0 4px 16px rgba(0,0,0,0.18)';
    ghost.style.letterSpacing = '0.01em';
    ghost.style.pointerEvents = 'none';
    document.body.appendChild(ghost);
    evt.dataTransfer.setDragImage(ghost, evt.offsetX ?? 100, evt.offsetY ?? 40);
    requestAnimationFrame(() => {
        if (ghost.parentNode) document.body.removeChild(ghost);
    });
};

const onDragEnd = () => {
    draggedEvent.value = null;
    dragOverTarget.value = null;
    dragSource.value = null;
};

const onDragEnter = (dateStr, time) => {
    if (!draggedEvent.value) return;
    dragOverTarget.value = { dateStr, time };
};

const onDragOver = (dateStr, time) => {
    if (!draggedEvent.value) return;
    if (!dragOverTarget.value || dragOverTarget.value.dateStr !== dateStr || dragOverTarget.value.time !== time) {
        dragOverTarget.value = { dateStr, time };
    }
};

const onDragLeave = (evt, rowEl) => {
    if (!draggedEvent.value) return;
    const related = evt.relatedTarget;
    if (rowEl && related && rowEl.contains(related)) return;
    dragOverTarget.value = null;
};

const draggingDurationMinutes = () => {
    if (!draggedEvent.value) return props.slotMinutes;
    return eventDurationMinutes(draggedEvent.value);
};

const dragPreviewStyle = computed(() => {
    if (!draggedEvent.value || !dragOverTarget.value) return null;
    const duration = draggingDurationMinutes();
    const slotHeight = 58;
    const rowGap = 8;
    const laneGap = 8;
    const height = Math.max(slotHeight - rowGap, (duration / props.slotMinutes) * slotHeight - rowGap);
    const laneWidth = `(100% - 2rem - ${laneGap * 0}px) / 1`;
    return {
        top: '4px',
        height: `${height}px`,
        left: `calc(1rem + (${laneWidth} + ${laneGap}px) * 0)`,
        width: `calc(${laneWidth})`,
    };
});

const maxAvailableDuration = (dateStr, startTime) => {
    const dayData = daysData.value[dateStr];
    if (!dayData?.events) return null;
    const startMin = minutesFromTime(startTime);
    const maxEnd = minutesFromTime(props.normalEnd);
    const sourceId = draggedEvent.value?.source_id;

    let availableEnd = startMin;
    for (let m = startMin; m < maxEnd; m += props.slotMinutes) {
        const hasConflict = dayData.events.some((event) => {
            if (event.source_id === sourceId && event.type === 'appointment') return false;
            const eventStart = minutesFromTime(timeFromDate(event.start));
            const eventEnd = minutesFromTime(timeFromDate(event.end));
            return m < eventEnd && (m + props.slotMinutes) > eventStart;
        });
        if (hasConflict) break;
        availableEnd = m + props.slotMinutes;
    }
    return Math.max(props.slotMinutes, availableEnd - startMin);
};

const onDrop = (dateStr, time) => {
    if (!draggedEvent.value) return;
    const sourceId = draggedEvent.value.source_id;
    if (!sourceId) {
        draggedEvent.value = null;
        dragOverTarget.value = null;
        return;
    }

    const originalDuration = draggingDurationMinutes();
    const dayData = daysData.value[dateStr];
    const targetMinutes = minutesFromTime(time);
    const maxAvail = maxAvailableDuration(dateStr, time);
    const finalDuration = maxAvail !== null ? Math.min(originalDuration, maxAvail) : originalDuration;
    const durationWillReduce = finalDuration < originalDuration;

    const hasOccupiedSlot = dayData?.events?.some((event) => {
        if (event.source_id === sourceId && event.type === 'appointment') return false;
        return eventOverlapsSlot(event, targetMinutes);
    });

    let swalTitle = '';
    let swalHtml = '';
    let swalIcon = '';

    if (hasOccupiedSlot && durationWillReduce) {
        swalIcon = 'warning';
        swalTitle = 'Atención';
        swalHtml = `
            El horario <strong>${formatTimeLabel(time)}</strong> del <strong>${dateStr}</strong> ya tiene un evento y solo hay espacio para <strong>${finalDuration} min</strong> (la cita original dura ${originalDuration} min).
            <br><br><small class="text-white-dark">La cita se reubicará con ${finalDuration} min de duración, reemplazando la existente.</small>
        `;
    } else if (hasOccupiedSlot) {
        swalIcon = 'warning';
        swalTitle = 'Slot ocupado';
        swalHtml = `El horario <strong>${formatTimeLabel(time)}</strong> del <strong>${dateStr}</strong> ya tiene un evento. ¿Reagendar de todos modos?<br><br><small class="text-white-dark">La cita existente se reemplazará.</small>`;
    } else if (durationWillReduce) {
        swalIcon = 'info';
        swalTitle = 'Duración reducida';
        swalHtml = `La cita original dura <strong>${originalDuration} min</strong>, pero en ese horario solo hay espacio para <strong>${finalDuration} min</strong>. La duración se reducirá automáticamente.`;
    }

    if (swalTitle) {
        Swal.fire({
            icon: swalIcon,
            title: swalTitle,
            html: swalHtml,
            showCancelButton: true,
            confirmButtonText: hasOccupiedSlot ? 'Sí, reagendar' : 'Reducir y reagendar',
            cancelButtonText: 'Cancelar',
            customClass: {
                popup: 'sweet-alerts',
                confirmButton: 'btn btn-warning',
                cancelButton: 'btn btn-dark ltr:mr-3 rtl:ml-3',
            },
            buttonsStyling: false,
            reverseButtons: true,
            padding: '2em',
        }).then((result) => {
            if (result.isConfirmed) {
                moveAppointment(sourceId, dateStr, time, finalDuration);
            } else {
                draggedEvent.value = null;
                dragOverTarget.value = null;
            }
        });
    } else {
        moveAppointment(sourceId, dateStr, time, finalDuration);
    }
};

const cloneDaysData = () => JSON.parse(JSON.stringify(daysData.value || {}));

const dateTimeValue = (dateStr, time) => `${dateStr} ${String(time).slice(0, 5)}:00`;

const addMinutesToTime = (time, minutes) => {
    return timeFromMinutes(minutesFromTime(time) + minutes);
};

const moveAppointmentLocally = (appointmentId, targetDate, targetTime, durationMinutes) => {
    const nextDaysData = cloneDaysData();
    let movedEvent = null;
    let sourceDate = null;

    Object.entries(nextDaysData).some(([dateStr, dayData]) => {
        const events = dayData?.events || [];
        const index = events.findIndex((event) => event.type === 'appointment' && Number(event.source_id) === Number(appointmentId));

        if (index === -1) {
            return false;
        }

        sourceDate = dateStr;
        movedEvent = { ...events[index] };
        events.splice(index, 1);
        nextDaysData[dateStr] = { ...dayData, events };

        return true;
    });

    if (!movedEvent) {
        return false;
    }

    const targetEndTime = addMinutesToTime(targetTime, durationMinutes);
    movedEvent.start = dateTimeValue(targetDate, targetTime);
    movedEvent.end = dateTimeValue(targetDate, targetEndTime);
    movedEvent.doctor_id = selectedDoctor.value?.code;

    const targetDayData = nextDaysData[targetDate] || {
        events: [],
        free_slots: [],
        punctuality: { early: [], on_time: [], late: [], grace_minutes: 5 },
        working_hours_ranges: props.workingHoursRanges,
    };

    nextDaysData[targetDate] = {
        ...targetDayData,
        events: [
            ...(targetDayData.events || []),
            movedEvent,
        ],
    };

    if (sourceDate && sourceDate !== targetDate && !nextDaysData[sourceDate]?.events?.length) {
        nextDaysData[sourceDate] = {
            ...(nextDaysData[sourceDate] || {}),
            events: [],
        };
    }

    daysData.value = nextDaysData;

    return true;
};

const moveAppointment = (appointmentId, targetDate, targetTime, durationMinutes) => {
    const previousDaysData = cloneDaysData();

    moveAppointmentLocally(appointmentId, targetDate, targetTime, durationMinutes);
    triggerDropSuccess(targetDate, targetTime);
    requestAnimationFrame(() => scrollToSlot(targetDate, targetTime));

    moveLoading.value = true;
    axios.post(route('heal_agendas_appointments_move'), {
        appointment_id: appointmentId,
        doctor_id: selectedDoctor.value?.code,
        date: targetDate,
        time: targetTime,
        duration_minutes: durationMinutes,
    }).then(() => {
        showMessage('Cita reagendada correctamente.');
        loadAgenda({ silent: true });
    }).catch((error) => {
        daysData.value = previousDaysData;
        const errors = error.response?.data?.errors || {};
        const message = Object.values(errors).flat().join(' ');
        showMessage(message || 'No se pudo reagendar la cita.', 'error');
    }).finally(() => {
        moveLoading.value = false;
        draggedEvent.value = null;
        dragOverTarget.value = null;
        dragSource.value = null;
    });
};

const playDropSound = () => {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        // Tono ascendente agradable (ding)
        osc.type = 'sine';
        osc.frequency.setValueAtTime(523, ctx.currentTime);       // C5
        osc.frequency.exponentialRampToValueAtTime(784, ctx.currentTime + 0.08);  // G5
        osc.frequency.exponentialRampToValueAtTime(1047, ctx.currentTime + 0.16); // C6
        gain.gain.setValueAtTime(0.18, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.35);
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + 0.35);
    } catch (e) {
        // Silently fail — audio no esencial
    }
};

const scrollToSlot = (dateStr, time) => {
    const selector = `[data-slot="${dateStr}-${time}"]`;
    const el = document.querySelector(selector);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
};

const triggerDropSuccess = (dateStr, time) => {
    dropSuccessTarget.value = { dateStr, time };
    playDropSound();
    setTimeout(() => {
        if (dropSuccessTarget.value && dropSuccessTarget.value.dateStr === dateStr && dropSuccessTarget.value.time === time) {
            dropSuccessTarget.value = null;
        }
    }, 800);
};

const isOutsideNormalHours = (time) => {
    if (!time) {
        return false;
    }

    const timeMinutes = minutesFromTime(time);

    // Check if the ENTIRE appointment fits within any single working hours range
    const duration = Number(form.duration_minutes || props.slotMinutes);
    const ranges = props.workingHoursRanges?.length ? props.workingHoursRanges : [{ start: props.normalStart, end: props.normalEnd }];

    return !ranges.some((range) => {
        const rangeStart = minutesFromTime(range.start);
        const rangeEnd = minutesFromTime(range.end);
        return timeMinutes >= rangeStart && (timeMinutes + duration) <= rangeEnd;
    });
};

watch([selectedDoctor, selectedDate], () => {
    loadAgenda();
    loadSidebarAvailability();
});

watch(dayCount, () => {
    loadAgenda();
});

watch(sidebarDuration, () => {
    loadSidebarAvailability();
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

let resizeTimer;
const onResize = () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
        updateDayCount();
    }, 250);
};

onMounted(() => {
    updateDayCount();
    window.addEventListener('resize', onResize);
    loadAgenda();
    loadSidebarAvailability();
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', onResize);
    clearTimeout(resizeTimer);
});
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
                            Agenda de {{ selectedDoctorName }}. Las atenciones bloquean {{ attentionBlockMinutes }} minutos.
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
                            {{ displayDates[0] }} - {{ displayDates[displayDates.length - 1] || displayDates[0] }}
                        </div>
                        <div class="flex flex-wrap items-center gap-3 text-xs text-white-dark">
                            <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-sm bg-success"></span>Cita pendiente</span>
                            <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-sm bg-info"></span>Atención registrada</span>
                            <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-sm bg-danger"></span>No concretada</span>
                        </div>
                        <div class="flex items-center">
                            <EstablishmentBadge />
                        </div>
                    </div>

                    <div v-if="loadingAgenda" class="p-8 text-center text-white-dark">
                        Cargando agenda...
                    </div>
                    <div v-else-if="Object.keys(daysData).length === 0" class="p-8 text-center text-white-dark">
                        Selecciona un doctor y fecha para ver la agenda.
                    </div>                        <div v-else class="flex overflow-x-auto">
                        <div
                            v-for="(dateStr, index) in displayDates"
                            :key="dateStr"
                            class="min-w-[300px] flex-1 border-r border-[#e0e6ed] dark:border-[#1b2e4b] last:border-r-0"
                            :class="{ 'hidden xl:block': index >= 3 }"
                        >
                            <div class="sticky top-0 z-10 border-b border-[#e0e6ed] bg-[#fbfbfb] px-3 py-2 text-center dark:border-[#1b2e4b] dark:bg-[#121c2c]">
                                <div class="font-semibold text-sm" :class="{ 'text-primary': index === 0 }">{{ formatDayHeader(dateStr) }}</div>
                                <div class="text-xs text-white-dark">{{ dateStr }}</div>
                                <div class="mt-1 flex items-center justify-center gap-2 text-xs">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-info/10 px-2 py-0.5 font-medium text-info">
                                        {{ dayCounts(daysData[dateStr]?.events || []).attentions }} atenciones
                                    </span>
                                    <span class="inline-flex items-center gap-1 rounded-full bg-success/10 px-2 py-0.5 font-medium text-success">
                                        {{ dayCounts(daysData[dateStr]?.events || []).appointments }} citas
                                    </span>
                                </div>
                            </div>
                            <div class="divide-y divide-[#e0e6ed] dark:divide-[#1b2e4b]">
                                <div
                                    v-for="slot in getDaySlots(daysData[dateStr], globalAgendaRange.start, globalAgendaRange.end)"
                                    :key="dateStr + '-' + slot.time"
                                    :data-slot="`${dateStr}-${slot.time}`"
                                    class="grid min-h-[58px] grid-cols-[80px_1fr] hover:bg-gray-50 dark:hover:bg-white/5"
                                    :class="[
                                        { 'bg-warning/5': !slot.withinNormalHours },
                                        { 'bg-primary/5 ring-1 ring-inset ring-primary/30': dragOverTarget?.dateStr === dateStr && dragOverTarget?.time === slot.time },
                                        { 'bg-success/5 ring-1 ring-inset ring-success/40': draggedEvent && slot.available && !slot.hasEventOverlap },
                                        { 'bg-danger/5 ring-1 ring-inset ring-danger/30': draggedEvent && slot.hasEventOverlap },
                                        { 'bg-warning/10 ring-1 ring-inset ring-warning/40': dragSource?.dateStr === dateStr && dragSource?.time === slot.time && draggedEvent && !(dragOverTarget?.dateStr === dateStr && dragOverTarget?.time === slot.time) },
                                        { 'drop-success-pulse': dropSuccessTarget?.dateStr === dateStr && dropSuccessTarget?.time === slot.time },
                                    ]"
                        @dragover.prevent="onDragOver(dateStr, slot.time)"
                        @dragenter="onDragEnter(dateStr, slot.time)"
                        @dragleave="onDragLeave($event, $event.currentTarget)"
                        @drop.prevent="onDrop(dateStr, slot.time)"
                                >
                                    <div class="group relative border-r border-[#e0e6ed] px-2 py-3 text-xs font-semibold text-white-dark dark:border-[#1b2e4b]">
                                        <div>{{ formatTimeLabel(slot.time) }}</div>
                                        <div v-if="!slot.withinNormalHours" class="mt-1 text-[10px] font-normal text-warning">F. hora</div>

                                        <!-- Tooltip en slots ocupados -->
                                        <div
                                            v-if="slot.hasEventOverlap && slot.overlappingEventNames?.length"
                                            class="invisible absolute left-1/2 z-20 -translate-x-1/2 rounded bg-gray-900 px-2.5 py-1.5 text-[10px] font-normal text-white shadow-lg opacity-0 transition-all duration-200 group-hover:visible group-hover:opacity-100 dark:bg-gray-700"
                                            style="bottom: calc(100% + 6px); white-space: nowrap;"
                                        >
                                            <div class="flex flex-col gap-0.5 text-left">
                                                <div class="mb-1 border-b border-white/15 pb-1 text-center text-[11px] font-bold">
                                                    {{ slot.totalOverlapping }} {{ slot.totalOverlapping === 1 ? 'paciente en este horario' : 'pacientes en este horario' }}
                                                </div>
                                                <div v-for="(name, i) in slot.overlappingEventNames" :key="i" class="flex items-center gap-1">
                                                    <span class="inline-block h-1.5 w-1.5 rounded-full bg-danger"></span>
                                                    {{ name }}
                                                </div>
                                                <div v-if="slot.totalOverlapping > 3" class="text-white-dark">+ {{ slot.totalOverlapping - 3 }} más...</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="relative flex flex-col gap-2 px-4 py-2 sm:flex-row sm:items-center" :class="{ 'pointer-events-none': !!draggedEvent }">
                                        <button
                                            v-if="slot.withinNormalHours && slot.available && !slot.hasEventOverlap"
                                            type="button"
                                            class="inline-flex w-max items-center rounded border border-dashed border-primary px-2 py-1 text-[10px] font-semibold text-primary hover:bg-primary hover:text-white"
                                            @click="openAppointmentModal(slot.time)"
                                        >
                                            <IconPlus class="h-3 w-3 ltr:mr-1 rtl:ml-1" />
                                            Agendar
                                        </button>
                                        <span v-else-if="!slot.hasEventOverlap" class="text-[10px] text-white-dark">
                                            {{ slot.withinNormalHours ? 'No disponible' : 'Sin citas' }}
                                        </span>

                                        <div
                                            v-for="event in slot.startingEvents"
                                            :key="event.id"
                                            :draggable="isDraggableEvent(event)"
                                            class="group absolute z-10 rounded border px-2 py-1.5 shadow-sm transition-all duration-300 ease-in-out"
                                            :class="[
                                                eventClass(event),
                                                { 'cursor-pointer hover:ring-2 hover:ring-primary/40': canOpenAttention(event) },
                                                { 'cursor-grab active:cursor-grabbing': isDraggableEvent(event) },
                                                { 'ring-2 ring-primary/60 opacity-20 scale-[0.97] shadow-none': draggedEvent?.id === event.id },
                                                { 'z-20': draggedEvent?.id !== event.id },
                                            ]"
                                            :style="eventCardStyle(event)"
                                            @click="openAttentionFromAppointment(event)"
                                            @dragstart="onDragStart(event, $event)"
                                            @dragend="onDragEnd($event)"
                                        >
                                            <div class="flex flex-col gap-0.5 overflow-hidden">
                                                <div class="font-semibold text-xs leading-tight">{{ event.patient || 'Paciente' }}</div>
                                                <div class="text-[10px] opacity-90">{{ formatEventTime(event) }}</div>
                                                <div class="text-[10px] opacity-90">{{ event.title }}</div>
                                                <div class="mt-0.5 text-[10px] opacity-90">{{ statusLabel(event) }}</div>
                                                <div v-if="arrivalLabel(event)" class="text-[10px] opacity-80">{{ arrivalLabel(event) }}</div>
                                            </div>

                                            <!-- Tooltip para citas de 15 min o menos -->
                                            <div
                                                v-if="eventDurationMinutes(event) <= 15"
                                                class="invisible absolute left-1/2 z-30 -translate-x-1/2 rounded-lg bg-gray-900 px-3 py-2 text-[10px] text-white shadow-xl opacity-0 transition-all duration-200 group-hover:visible group-hover:opacity-100 dark:bg-gray-700 pointer-events-none"
                                                style="bottom: calc(100% + 6px); min-width: max-content;"
                                            >
                                                <div class="flex flex-col gap-1 text-left whitespace-nowrap">
                                                    <div class="font-semibold">{{ event.patient || 'Paciente' }}</div>
                                                    <div>{{ formatEventTime(event) }}</div>
                                                    <div>{{ event.title }}</div>
                                                    <div>{{ statusLabel(event) }}</div>
                                                    <div v-if="arrivalLabel(event)">{{ arrivalLabel(event) }}</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div
                                            v-if="dragOverTarget?.dateStr === dateStr && dragOverTarget?.time === slot.time && draggedEvent"
                                            class="absolute z-20 overflow-hidden rounded border-2 border-dashed border-primary/50 bg-primary/5 px-2 py-1.5 backdrop-blur-sm"
                                            :style="dragPreviewStyle"
                                        >
                                            <div class="flex flex-col gap-0.5">
                                                <div class="font-semibold text-xs leading-tight text-primary/60">
                                                    {{ draggedEvent.patient || 'Paciente' }}
                                                </div>
                                                <div class="text-[10px] text-primary/50">
                                                    {{ formatTimeLabel(dragOverTarget.time) }}
                                                    -
                                                    {{ formatTimeLabel(timeFromMinutes(minutesFromTime(dragOverTarget.time) + draggingDurationMinutes())) }}
                                                </div>
                                                <div class="mt-0.5 flex items-center gap-1 text-[10px] text-primary/50">
                                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M5 9l7 7 7-7" />
                                                    </svg>
                                                    Soltar aquí
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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
                                <select v-model.number="sidebarDuration" class="form-select form-select-sm w-[110px] text-xs">
                                    <option
                                        v-for="opt in [15, 30, 45, 60, 90, 120, 150, 180, 240]"
                                        :key="opt"
                                        :value="opt"
                                    >
                                        {{ opt === 60 ? '1 hora' : opt === 90 ? '1:30 h' : opt === 120 ? '2 horas' : opt === 150 ? '2:30 h' : opt === 180 ? '3 horas' : opt === 240 ? '4 horas' : opt + ' min' }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div v-if="sidebarAvailabilityLoading" class="text-sm text-center py-2 text-white-dark">
                                Consultando agenda...
                            </div>
                            <div v-else-if="sidebarFreeSlots.length === 0" class="text-sm text-center py-2 text-white-dark">
                                No hay espacios libres de {{ sidebarDuration }} min dentro del horario normal.
                            </div>
                            <div v-else class="flex max-h-[440px] flex-col gap-1 overflow-y-auto pr-1">
                                <button
                                    v-for="slot in sidebarFreeSlots"
                                    :key="slot.start"
                                    type="button"
                                    class="btn btn-outline-primary btn-sm w-full"
                                    @click="openAppointmentModal(slot.start, sidebarDuration)"
                                >
                                    {{ formatTimeLabel(slot.start) }} - {{ formatTimeLabel(slot.end) }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="panel">
                        <h3 class="mb-3 font-semibold">Puntualidad del día</h3>
                        <div class="space-y-4">
                            <div v-for="key in ['early', 'on_time', 'late']" :key="key">
                                <div class="mb-2 flex items-center justify-between text-sm font-semibold">
                                    <span>{{ punctualityTitle(key) }}</span>
                                    <span>{{ selectedDayData.punctuality?.[key]?.length || 0 }}</span>
                                </div>
                                <div v-if="selectedDayData.punctuality?.[key]?.length" class="space-y-2">
                                    <div
                                        v-for="item in selectedDayData.punctuality[key]"
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

<style scoped>
.drop-success-pulse {
    animation: dropPulse 0.8s ease-out;
}
@keyframes dropPulse {
    0% {
        background-color: rgba(16, 185, 129, 0.25);
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4);
    }
    50% {
        background-color: rgba(16, 185, 129, 0.15);
        box-shadow: 0 0 0 8px rgba(16, 185, 129, 0);
    }
    100% {
        background-color: transparent;
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
    }
}
</style>
