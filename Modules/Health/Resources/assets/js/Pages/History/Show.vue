<script setup>
import { computed, reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import flatPickr from 'vue-flatpickr-component';
import 'flatpickr/dist/flatpickr.css';
import { Spanish } from 'flatpickr/dist/l10n/es.js';
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import IconCalendar from '@/Components/vristo/icon/icon-calendar.vue';
import IconCaretDown from '@/Components/vristo/icon/icon-caret-down.vue';
import IconClipboardText from '@/Components/vristo/icon/icon-clipboard-text.vue';
import IconFile from '@/Components/vristo/icon/icon-file.vue';
import IconPlusCircle from '@/Components/vristo/icon/icon-plus-circle.vue';
import { serviceTypeOption } from '@/Components/Health/healthOptions.js';

const props = defineProps({
    patient: {
        type: Object,
        default: () => ({}),
    },
    attentions: {
        type: Object,
        default: () => ({}),
    },
    attentionDates: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const openTreatments = reactive({});
const openExams = reactive({});
const baseUrl = typeof window !== 'undefined' ? (window.assetUrl || '/') : '/';

const selectedDate = computed({
    get: () => props.filters.date || '',
    set: () => {},
});

const attentionDateSet = computed(() => new Set(props.attentionDates || []));

const localDate = (date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
};

const calendarConfig = computed(() => ({
    locale: Spanish,
    dateFormat: 'Y-m-d',
    allowInput: true,
    enable: props.attentionDates || [],
    onDayCreate: (selectedDates, dateStr, instance, dayElem) => {
        if (attentionDateSet.value.has(localDate(dayElem.dateObj))) {
            dayElem.classList.add('attention-day');
        }
    },
}));

const selectDate = (dates, dateStr) => {
    router.get(route('heal_clinical_records_show', props.patient.id), { date: dateStr || null }, {
        preserveScroll: false,
        replace: true,
    });
};

const clearDate = () => {
    router.get(route('heal_clinical_records_show', props.patient.id), {}, {
        preserveScroll: false,
        replace: true,
    });
};

const createAttentionUrl = computed(() => route('heal_attentions_create', { patient_id: props.patient.id }));

const toggleTreatments = (id) => {
    openTreatments[id] = !openTreatments[id];
};

const toggleExams = (id) => {
    openExams[id] = !openExams[id];
};

const calculateAge = (birthdate) => {
    if (!birthdate) {
        return 'Sin fecha';
    }

    const today = new Date();
    const born = new Date(birthdate);
    let age = today.getFullYear() - born.getFullYear();
    const month = today.getMonth() - born.getMonth();

    if (month < 0 || (month === 0 && today.getDate() < born.getDate())) {
        age -= 1;
    }

    return `${age} años`;
};

const genderLabel = (gender) => ({
    M: 'Masculino',
    F: 'Femenino',
}[gender] || 'No registrado');

const formatDate = (value) => {
    if (!value) {
        return '';
    }

    return new Intl.DateTimeFormat('es-PE', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};

const serviceTypeLabel = (value) => serviceTypeOption(value)?.label || value || 'Sin tipo';

const examTypeLabel = (value) => ({
    radiografia: 'Radiografía',
    laboratorio: 'Laboratorio',
    imagen: 'Imagen',
    otro: 'Otro',
}[value] || value || 'Examen');

const treatmentTypeLabel = (value) => ({
    farmacologica: 'Terapia farmacológica',
    endodontico: 'Endodoncia',
    extraccion_dental: 'Extracción dental',
    operatoria: 'Operatoria',
    rehabilitacion: 'Rehabilitación',
    otros: 'Otros',
}[value] || value || 'Tratamiento');

const endodonticStatusLabel = (status) => ({
    proceso: 'En proceso',
    finalizado: 'Finalizado',
}[status] || status || 'En proceso');

const canalTitle = (canal, index) => `Conducto ${canal?.name || index + 1}`;

const patientImageUrl = computed(() => {
    if (props.patient.person?.image) {
        return `${baseUrl}storage/${props.patient.person.image}`;
    }

    return `https://ui-avatars.com/api/?name=${props.patient.person?.full_name || 'Paciente'}&size=160&rounded=true`;
});

const doctorSignatureName = (attention) => {
    const doctor = attention.signed_by_doctor || attention.doctor;
    const person = doctor?.person;

    if (!person) {
        return null;
    }

    return [person.father_lastname, person.names].filter(Boolean).join(' ') || person.full_name;
};

const doctorSignatureColegiatura = (attention) => {
    const doctor = attention.signed_by_doctor || attention.doctor;

    return doctor?.colegiatura || null;
};

const hasVitalSigns = (attention) => Boolean(
    attention.blood_pressure_systolic
    || attention.blood_pressure_diastolic
    || attention.heart_rate
    || attention.respiratory_rate
    || attention.height
    || attention.weight
    || attention.body_mass_index
);
</script>

<template>
    <AppLayout title="Historias Clínicas">
        <Navigation :routeModule="route('health_dashboard')" :titleModule="'Salud'">
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <Link :href="route('heal_clinical_records_list')" class="text-primary hover:underline">Historias Clínicas</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>{{ patient.person?.full_name }}</span>
            </li>
        </Navigation>

        <div class="pt-5">
            <div class="mb-5 grid grid-cols-1 gap-5 xl:grid-cols-12">
                <div class="panel xl:col-span-4">
                    <div class="flex items-start gap-4">
                        <img
                            :src="patientImageUrl"
                            class="h-16 w-16 rounded-full object-cover"
                            :alt="patient.person?.full_name"
                        />
                        <div>
                            <h2 class="text-xl font-semibold">{{ patient.person?.full_name }}</h2>
                            <div class="mt-2 grid grid-cols-1 gap-1 text-sm text-white-dark sm:grid-cols-2">
                                <div>DNI: {{ patient.person?.number || 'Sin DNI' }}</div>
                                <div>Edad: {{ calculateAge(patient.person?.birthdate) }}</div>
                                <div>Género: {{ genderLabel(patient.person?.gender) }}</div>
                                <div>Código: {{ patient.patient_code || 'Sin código' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="panel xl:col-span-8">
                    <div class="flex flex-wrap items-end gap-3">
                        <div class="w-full sm:w-72">
                            <label class="mb-1 block text-sm font-semibold">Buscar por fecha de atención</label>
                            <div class="relative">
                                <flat-pickr
                                    :model-value="selectedDate"
                                    class="form-input pr-10"
                                    :config="calendarConfig"
                                    placeholder="Elegir fecha"
                                    @on-change="selectDate"
                                />
                                <IconCalendar class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-white-dark" />
                            </div>
                        </div>
                        <button v-if="filters.date" type="button" class="btn btn-outline-secondary" @click="clearDate">
                            Ver todas
                        </button>
                        <Link :href="createAttentionUrl" class="btn btn-danger">
                            <IconPlusCircle class="h-4 w-4" />
                            Nueva Atención
                        </Link>
                        <div class="text-sm text-white-dark">
                            Los días con atención aparecen marcados en el calendario.
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-0">
                <article
                    v-for="attention in attentions.data"
                    :key="attention.id"
                    class="relative border-b border-slate-200 py-6 last:border-b-0 dark:border-slate-700"
                >
                    <div class="panel relative overflow-hidden pb-16">
                        <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2 text-primary">
                                    <IconClipboardText class="h-5 w-5" />
                                    <h3 class="text-lg font-semibold">Atención - {{ formatDate(attention.attention_at) }}</h3>
                                </div>
                            </div>
                            <div class="rounded border border-slate-200 px-3 py-2 text-sm dark:border-slate-700">
                                {{ serviceTypeLabel(attention.service_type) }}
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                            <div>
                                <h4 class="mb-1 font-semibold">Relato del paciente</h4>
                                <p class="whitespace-pre-line text-sm text-white-dark">{{ attention.patient_story || 'Sin registro.' }}</p>
                            </div>
                            <div>
                                <h4 class="mb-1 font-semibold">Observación del doctor</h4>
                                <p class="whitespace-pre-line text-sm text-white-dark">{{ attention.doctor_observation || 'Sin registro.' }}</p>
                            </div>
                            <div>
                                <h4 class="mb-1 font-semibold">Hallazgos clínicos</h4>
                                <p class="whitespace-pre-line text-sm text-white-dark">{{ attention.clinical_findings || 'Sin registro.' }}</p>
                            </div>
                            <div>
                                <h4 class="mb-1 font-semibold">Diagnóstico</h4>
                                <p class="whitespace-pre-line text-sm text-white-dark">{{ attention.diagnosis || attention.cie10?.description || attention.cie10?.cie10 || 'Sin registro.' }}</p>
                                <div v-if="attention.cie10" class="mt-1 text-xs text-primary">
                                    CIE10: {{ attention.cie10.cie10x }} - {{ attention.cie10.description || attention.cie10.cie10 }}
                                </div>
                            </div>
                            <div>
                                <h4 class="mb-1 font-semibold">Plan de tratamiento</h4>
                                <p class="whitespace-pre-line text-sm text-white-dark">{{ attention.treatment_plan || 'Sin registro.' }}</p>
                            </div>
                            <div>
                                <h4 class="mb-1 font-semibold">Observaciones</h4>
                                <p class="whitespace-pre-line text-sm text-white-dark">{{ attention.observations || 'Sin registro.' }}</p>
                            </div>
                        </div>

                        <div v-if="hasVitalSigns(attention)" class="mt-5 grid grid-cols-2 gap-3 rounded bg-slate-50 p-4 text-sm dark:bg-slate-800 md:grid-cols-4">
                            <div v-if="attention.blood_pressure_systolic || attention.blood_pressure_diastolic">
                                <div class="text-white-dark">Presión arterial</div>
                                <div class="font-semibold">{{ attention.blood_pressure_systolic || '-' }}/{{ attention.blood_pressure_diastolic || '-' }}</div>
                            </div>
                            <div v-if="attention.heart_rate">
                                <div class="text-white-dark">Frecuencia cardiaca</div>
                                <div class="font-semibold">{{ attention.heart_rate }}</div>
                            </div>
                            <div v-if="attention.respiratory_rate">
                                <div class="text-white-dark">Frecuencia respiratoria</div>
                                <div class="font-semibold">{{ attention.respiratory_rate }}</div>
                            </div>
                            <div v-if="attention.height || attention.weight || attention.body_mass_index">
                                <div class="text-white-dark">Talla / Peso / IMC</div>
                                <div class="font-semibold">{{ attention.height || '-' }} / {{ attention.weight || '-' }} / {{ attention.body_mass_index || '-' }}</div>
                            </div>
                        </div>

                        <div class="mt-5 rounded border border-slate-200 dark:border-slate-700">
                            <button
                                type="button"
                                class="flex w-full items-center justify-between px-4 py-3 text-left font-semibold"
                                @click="toggleTreatments(attention.id)"
                            >
                                <span>Tratamientos ({{ attention.treatments?.length || 0 }})</span>
                                <IconCaretDown class="h-4 w-4 transition" :class="{ 'rotate-180': openTreatments[attention.id] }" />
                            </button>
                            <div v-if="openTreatments[attention.id]" class="border-t border-slate-200 p-4 dark:border-slate-700">
                                <div v-if="attention.treatments?.length" class="space-y-3">
                                    <div v-for="treatment in attention.treatments" :key="treatment.id" class="rounded bg-slate-50 p-3 dark:bg-slate-800">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <div class="font-semibold">{{ treatment.name || treatmentTypeLabel(treatment.treatment_type) }}</div>
                                            <span class="badge bg-primary text-xs">{{ treatmentTypeLabel(treatment.treatment_type) }}</span>
                                        </div>
                                        <p class="mt-1 whitespace-pre-line text-sm text-white-dark">{{ treatment.description || 'Sin descripción.' }}</p>
                                        <p v-if="treatment.indications" class="mt-2 whitespace-pre-line text-sm">
                                            <span class="font-semibold">Indicaciones:</span> {{ treatment.indications }}
                                        </p>
                                        <div v-if="treatment.treatment_type === 'endodontico' && treatment.endodontic_data" class="mt-3 rounded border border-primary/20 bg-white p-3 dark:bg-slate-900">
                                            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                                                <div class="font-semibold text-primary">Detalle de endodoncia</div>
                                                <span class="badge" :class="treatment.endodontic_data.status === 'finalizado' ? 'bg-success' : 'bg-warning'">
                                                    {{ endodonticStatusLabel(treatment.endodontic_data.status) }}
                                                </span>
                                            </div>
                                            <div class="grid grid-cols-1 gap-3 text-sm md:grid-cols-4">
                                                <div>
                                                    <div class="text-white-dark">Pieza</div>
                                                    <div class="font-semibold">{{ treatment.endodontic_data.tooth || '-' }}</div>
                                                </div>
                                                <div>
                                                    <div class="text-white-dark">Sesión</div>
                                                    <div class="font-semibold">{{ treatment.endodontic_data.session_number || '-' }}</div>
                                                </div>
                                                <div>
                                                    <div class="text-white-dark">LDR</div>
                                                    <div class="font-semibold">{{ treatment.endodontic_data.ldr || '-' }}</div>
                                                </div>
                                                <div>
                                                    <div class="text-white-dark">LT</div>
                                                    <div class="font-semibold">{{ treatment.endodontic_data.lt || '-' }}</div>
                                                </div>
                                            </div>
                                            <div v-if="treatment.endodontic_data.diagnosis" class="mt-3 text-sm">
                                                <span class="font-semibold">Diagnóstico endodóntico:</span>
                                                {{ treatment.endodontic_data.diagnosis }}
                                            </div>
                                            <div v-if="treatment.endodontic_data.canals?.length" class="mt-3 grid grid-cols-1 gap-2 md:grid-cols-2">
                                                <div
                                                    v-for="(canal, canalIndex) in treatment.endodontic_data.canals"
                                                    :key="canalIndex"
                                                    class="rounded border border-slate-200 p-3 text-sm dark:border-slate-700"
                                                >
                                                    <div class="mb-2 font-semibold">{{ canalTitle(canal, canalIndex) }}</div>
                                                    <div class="grid grid-cols-2 gap-2">
                                                        <div><span class="text-white-dark">Longitud:</span> {{ canal.length || '-' }}</div>
                                                        <div><span class="text-white-dark">Apoyado en:</span> {{ canal.supported_on || '-' }}</div>
                                                        <div><span class="text-white-dark">Lima inicial:</span> {{ canal.initial_file || '-' }}</div>
                                                        <div><span class="text-white-dark">Lima trabajo:</span> {{ canal.working_file || '-' }}</div>
                                                        <div><span class="text-white-dark">Lima maestra:</span> {{ canal.master_file || '-' }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-sm text-white-dark">No hay tratamientos registrados.</div>
                            </div>
                        </div>

                        <div class="mt-3 rounded border border-slate-200 dark:border-slate-700">
                            <button
                                type="button"
                                class="flex w-full items-center justify-between px-4 py-3 text-left font-semibold"
                                @click="toggleExams(attention.id)"
                            >
                                <span>Exámenes complementarios ({{ attention.exams?.length || 0 }})</span>
                                <IconCaretDown class="h-4 w-4 transition" :class="{ 'rotate-180': openExams[attention.id] }" />
                            </button>
                            <div v-if="openExams[attention.id]" class="border-t border-slate-200 p-4 dark:border-slate-700">
                                <div v-if="attention.exams?.length" class="space-y-3">
                                    <div v-for="exam in attention.exams" :key="exam.id" class="rounded bg-slate-50 p-3 dark:bg-slate-800">
                                        <div class="font-semibold">{{ exam.name || examTypeLabel(exam.exam_type) }}</div>
                                        <div class="text-xs text-primary">{{ examTypeLabel(exam.exam_type) }}</div>
                                        <p class="mt-1 whitespace-pre-line text-sm text-white-dark">{{ exam.description || 'Sin descripción.' }}</p>
                                        <p v-if="exam.result" class="mt-2 whitespace-pre-line text-sm">
                                            <span class="font-semibold">Resultado:</span> {{ exam.result }}
                                        </p>
                                    </div>
                                </div>
                                <div v-else class="text-sm text-white-dark">No hay exámenes registrados.</div>
                            </div>
                        </div>

                        <div class="absolute bottom-4 right-4 rotate-[-4deg] rounded border-2 border-success px-4 py-2 text-xs font-bold uppercase tracking-wide text-success opacity-80">
                            <template v-if="attention.signed_at">
                                <div>Firmado: {{ doctorSignatureName(attention) || 'Doctor no registrado' }}</div>
                                <div v-if="doctorSignatureColegiatura(attention)" class="text-center">
                                    {{ doctorSignatureColegiatura(attention) }}
                                </div>
                            </template>
                            <template v-else>
                                No firmado
                            </template>
                        </div>
                    </div>
                </article>

                <div v-if="!attentions.data?.length" class="panel py-10 text-center text-white-dark">
                    No hay atenciones registradas para esta fecha.
                </div>
            </div>

            <div
                v-if="attentions.prev_page_url || attentions.next_page_url"
                class="mt-6 flex flex-wrap justify-center gap-3"
            >
                <Link v-if="attentions.prev_page_url" :href="attentions.prev_page_url" class="btn btn-outline-primary">
                    <IconFile class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                    Ir más adelante
                </Link>
                <Link v-if="attentions.next_page_url" :href="attentions.next_page_url" class="btn btn-outline-primary">
                    <IconFile class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                    Ir más atrás
                </Link>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
:deep(.flatpickr-day.attention-day) {
    border: 2px solid #4361ee;
    border-radius: 8px;
    color: #4361ee;
    font-weight: 700;
}

:deep(.flatpickr-day.attention-day.selected),
:deep(.flatpickr-day.attention-day:hover) {
    background: #4361ee;
    color: #fff;
}
</style>
