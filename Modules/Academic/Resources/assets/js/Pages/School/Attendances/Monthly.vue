<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import Swal2 from 'sweetalert2';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import iconLoader from '@/Components/vristo/icon/icon-loader.vue';
import { faTriangleExclamation, faUserCheck } from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
    section: { type: Object, required: true },
    calendar: { type: Object, required: true },
    months: { type: Array, default: () => [] },
    students: { type: Array, default: () => [] },
});

// Estados estilo SIAGIE: A=asistencia, T=tardanza, J=justificada, F=falta.
// Click en la celda cicla: (vacio) -> A -> T -> J -> F -> (vacio).
const CYCLE = ['', 'A', 'T', 'J', 'F'];
const STATUS_STYLE = {
    A: 'bg-success text-white',
    T: 'bg-warning text-white',
    J: 'bg-info text-white',
    F: 'bg-danger text-white',
};

const month = ref(props.calendar.month);
const form = ref({});
const saving = ref(false);
const completing = ref(false);

// Reconstruye la grilla desde lo guardado (o del mes recargado).
const buildForm = () => {
    const map = {};
    props.students.forEach((student) => {
        map[student.enrollment_id] = { ...student.attendance };
    });
    return map;
};
form.value = buildForm();

const reloadMonth = () => {
    router.visit(route('aca_school_attendance_show', props.section.id), {
        data: { month: month.value },
        preserveState: true,
        preserveScroll: true,
        only: ['calendar', 'students', 'months'],
        onSuccess: () => {
            form.value = buildForm();
        },
    });
};

const cellStatus = (enrollmentId, day) => form.value[enrollmentId]?.[day] ?? '';

const setCell = (enrollmentId, day, value) => {
    if (! form.value[enrollmentId]) return;
    if (value === '') {
        delete form.value[enrollmentId][day];
    } else {
        form.value[enrollmentId][day] = value;
    }
};

const nextStatus = (current) => {
    const index = CYCLE.indexOf(current);
    return CYCLE[(index + 1) % CYCLE.length];
};

const onCellClick = (enrollmentId, day) => {
    if (dayDisabled(day)) return;
    setCell(enrollmentId, day, nextStatus(cellStatus(enrollmentId, day)));
};

// Solo los fines de semana se bloquean; los dias futuros se permiten
// porque el docente puede pre-registrar (como "Completar hasta dia" en SIAGIE).
const dayDisabled = (day) => day.weekend;

const dayClass = (day) => ({
    // Gris neutro, sin tinte azul/purpura, en modo dark
    'bg-gray-200/60 dark:bg-white/[0.04]': dayDisabled(day),
    'cursor-pointer hover:bg-primary/5': !dayDisabled(day),
});

// "Completar asistencias" estilo SIAGIE: marca 'A' en todos los dias
// vacios hasta el dia elegido (sin tocar los ya registrados).
const completeUntilDay = ref(props.calendar.days.length);
const completeAttendances = () => {
    completing.value = true;
    try {
        props.students.forEach((student) => {
            if (! form.value[student.enrollment_id]) return;
            props.calendar.days.forEach((day) => {
                if (day.day > completeUntilDay.value || dayDisabled(day)) return;
                if (! form.value[student.enrollment_id][day.day]) {
                    form.value[student.enrollment_id][day.day] = 'A';
                }
            });
        });
    } finally {
        completing.value = false;
    }
};

// "Limpiar" restaura la grilla a lo guardado en BD.
const clearChanges = () => {
    form.value = buildForm();
};

// Cambios pendientes: celdas distintas a lo guardado.
const changedEntries = () => {
    const entries = [];
    props.students.forEach((student) => {
        props.calendar.days.forEach((day) => {
            const current = cellStatus(student.enrollment_id, day.day);
            const saved = student.attendance[day.day] ?? '';
            if (current !== saved) {
                entries.push({
                    enrollment_id: student.enrollment_id,
                    day: day.day,
                    status: current || null,
                });
            }
        });
    });
    return entries;
};

const pendingCount = computed(() => changedEntries().length);

const save = () => {
    const entries = changedEntries();

    if (entries.length === 0) {
        Swal2.fire({
            icon: 'info',
            text: 'No hay cambios por registrar.',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
        return;
    }

    saving.value = true;
    axios.post(route('aca_school_attendance_store', props.section.id), {
        month: month.value,
        entries,
    }).then((res) => {
        Swal2.fire({
            icon: 'success',
            title: 'Enhorabuena',
            text: res.data?.message ?? 'Asistencias registradas correctamente.',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
        router.reload({
            only: ['students'],
            preserveScroll: true,
            onFinish: () => {
                form.value = buildForm();
            },
        });
    }).catch((error) => {
        Swal2.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message ?? 'No se pudo registrar la asistencia.',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    }).finally(() => {
        saving.value = false;
    });
};

const downloadPdf = () => {
    window.open(route('aca_school_attendance_pdf', { sectionId: props.section.id, month: month.value }), '_blank');
};

const back = () => {
    router.visit(route('aca_school_attendance_index'));
};
</script>

<template>
    <AppLayout :title="'Asistencia · ' + section.grade + ' ' + section.name">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                { title: 'Registro de Asistencias', url: route('aca_school_attendance_index') },
                { title: section.grade + ' · ' + section.name },
            ]" />

        <div class="panel mt-5">
            <div class="p-5 border-b border-[#ebedf2] dark:border-[#191e3a] flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold">Registro de Asistencia mensual</h3>
                    <p class="text-sm text-white-dark mt-1">
                        {{ section.level }} · {{ section.grade }} · Sección {{ section.name }} — {{ calendar.year }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <label class="form-label mb-0 font-semibold">Mes:</label>
                    <select v-model.number="month" @change="reloadMonth" class="form-select max-w-[160px]">
                        <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
                    </select>
                </div>
            </div>

            <!-- Leyenda de estados estilo SIAGIE -->
            <div class="px-5 pt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm">
                <span class="font-semibold">Leyenda:</span>
                <span class="flex items-center gap-1.5"><span class="inline-flex items-center justify-center w-6 h-6 rounded border border-[#ebedf2] dark:border-[#1b2e4b]"></span> Sin registrar</span>
                <span class="flex items-center gap-1.5"><span class="badge bg-success !pl-2 !pr-2">A</span> Asistencia</span>
                <span class="flex items-center gap-1.5"><span class="badge bg-warning !pl-2 !pr-2">T</span> Tardanza</span>
                <span class="flex items-center gap-1.5"><span class="badge bg-info !pl-2 !pr-2">J</span> Justificada</span>
                <span class="flex items-center gap-1.5"><span class="badge bg-danger !pl-2 !pr-2">F</span> Falta</span>
            </div>

            <div class="p-5">
                <div v-if="students.length === 0" class="text-center py-10">
                    <font-awesome-icon :icon="faTriangleExclamation" class="text-3xl text-warning" />
                    <p class="text-sm text-white-dark mt-3">No hay alumnos matriculados en esta sección para el año activo.</p>
                </div>

                <template v-else>
                    <!-- Barra de acciones estilo SIAGIE -->
                    <div class="flex flex-wrap items-center gap-3 mb-4">
                        <label class="text-sm font-semibold">Completar hasta día</label>
                        <select v-model.number="completeUntilDay" class="form-select !w-24">
                            <option v-for="day in calendar.days" :key="day.day" :value="day.day" :disabled="dayDisabled(day)">{{ day.day }}</option>
                        </select>
                        <button type="button" class="btn btn-success" @click="completeAttendances" :disabled="completing">
                            <font-awesome-icon :icon="faUserCheck" class="ltr:mr-1 rtl:ml-1" />
                            Completar asistencias
                        </button>
                        <button type="button" class="btn btn-secondary" @click="clearChanges">Limpiar</button>

                        <div class="flex-1"></div>

                        <button type="button" class="btn btn-outline-secondary" @click="downloadPdf">PDF</button>
                        <PrimaryButton type="button" @click="save" :disabled="saving">
                            <icon-loader v-if="saving" class="w-4 h-4 mr-1" />
                            {{ saving ? 'Guardando...' : 'Grabar' }}
                        </PrimaryButton>
                        <SecondaryButton type="button" @click="back">Cancelar</SecondaryButton>
                    </div>

                    <div class="table-responsive border rounded-md border-[#ebedf2] dark:border-[#191e3a]">
                        <table class="whitespace-nowrap">
                            <thead>
                                <tr>
                                    <th rowspan="2" class="!text-center !w-10">#</th>
                                    <th rowspan="2" class="!text-center min-w-[240px]">APELLIDOS Y NOMBRES</th>
                                    <th v-for="day in calendar.days" :key="day.day" class="!px-1 !text-center"
                                        :class="dayDisabled(day) ? 'bg-gray-200 dark:bg-white/[0.06]' : ''">
                                        {{ day.day }}
                                    </th>
                                </tr>
                                <tr>
                                    <th v-for="day in calendar.days" :key="'l' + day.day" class="!px-1 !text-center !font-normal"
                                        :class="dayDisabled(day) ? 'bg-gray-200 dark:bg-white/[0.06]' : ''">
                                        {{ day.letter }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(student, index) in students" :key="student.enrollment_id">
                                    <td class="!text-center text-white-dark">{{ index + 1 }}</td>
                                    <td class="font-semibold">{{ student.full_name }}</td>
                                    <td v-for="day in calendar.days" :key="day.day"
                                        class="!p-0 !text-center"
                                        :class="dayClass(day)">
                                        <button
                                            v-if="!dayDisabled(day)"
                                            type="button"
                                            class="w-full h-8 min-w-[34px] flex items-center justify-center text-xs font-bold"
                                            :title="calendar.year + '-' + String(calendar.month).padStart(2, '0') + '-' + String(day.day).padStart(2, '0')"
                                            @click="onCellClick(student.enrollment_id, day.day)"
                                        >
                                            <span
                                                v-if="cellStatus(student.enrollment_id, day.day)"
                                                class="inline-flex items-center justify-center w-6 h-6 rounded"
                                                :class="STATUS_STYLE[cellStatus(student.enrollment_id, day.day)]"
                                            >{{ cellStatus(student.enrollment_id, day.day) }}</span>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <p class="text-xs text-white-dark mt-3">
                        Haz clic en cada celda para marcar: <strong>A</strong> → <strong>T</strong> → <strong>J</strong> → <strong>F</strong> → (vacío).
                        <span v-if="pendingCount" class="ml-1 text-warning font-semibold">{{ pendingCount }} cambio(s) sin grabar.</span>
                    </p>
                </template>
            </div>
        </div>
    </AppLayout>
</template>
