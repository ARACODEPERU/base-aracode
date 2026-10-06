<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import Swal2 from 'sweetalert2';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import iconLoader from '@/Components/vristo/icon/icon-loader.vue';
import { faTriangleExclamation, faChalkboardUser } from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
    section: { type: Object, required: true },
    students: { type: Array, default: () => [] },
});

// Formulario reactivo: por alumno, un valor por bimester (1..4).
// Cada celda: { number: string, letter: string, observations: string }
const buildForm = () => {
    const map = {};
    props.students.forEach((student) => {
        map[student.enrollment_id] = {};
        props.section.bimesters.forEach((bim) => {
            const existing = student.grades?.[area.value]?.[bim];
            map[student.enrollment_id][bim] = {
                number: existing?.number ?? '',
                letter: existing?.letter ?? '',
                observations: existing?.observations ?? '',
            };
        });
    });
    return map;
};

const area = ref(props.section.areas[0] ?? '');
const form = ref({});
const saving = ref(false);

// Al cambiar de area se recarga el form con las notas guardadas de esa area.
const changeArea = () => {
    form.value = buildForm();
};
form.value = buildForm();

const isLiteral = computed(() => props.section.scale === 'literal');
const LETTERS = ['AD', 'A', 'B', 'C'];

const cellValue = (enrollmentId, bimester) => form.value[enrollmentId]?.[bimester] ?? { number: '', letter: '', observations: '' };

const setCell = (enrollmentId, bimester, patch) => {
    if (! form.value[enrollmentId]) return;
    form.value[enrollmentId][bimester] = { ...cellValue(enrollmentId, bimester), ...patch };
};

const onNumberInput = (enrollmentId, bimester, event) => {
    let value = event.target.value.replace(/[^0-9]/g, '');
    if (value !== '') {
        value = String(Math.min(20, Math.max(0, parseInt(value, 10))));
    }
    setCell(enrollmentId, bimester, { number: value });
    event.target.value = value;
};

// Promedio anual del alumno en el area seleccionada (solo notas puestas).
const averageOf = (enrollmentId) => {
    const values = props.section.bimesters
        .map((bim) => parseFloat(cellValue(enrollmentId, bim).number))
        .filter((v) => ! Number.isNaN(v));
    if (values.length === 0) return null;
    return (values.reduce((a, b) => a + b, 0) / values.length).toFixed(1);
};

const filledCount = computed(() => {
    let total = 0;
    props.students.forEach((student) => {
        props.section.bimesters.forEach((bim) => {
            const cell = cellValue(student.enrollment_id, bim);
            if (isLiteral.value ? cell.letter !== '' : cell.number !== '') total++;
        });
    });
    return total;
});

const save = () => {
    const entries = [];
    props.students.forEach((student) => {
        props.section.bimesters.forEach((bim) => {
            const cell = cellValue(student.enrollment_id, bim);
            const hasValue = isLiteral.value ? cell.letter !== '' : cell.number !== '';
            if (! hasValue) return;
            entries.push({
                enrollment_id: student.enrollment_id,
                bimester: bim,
                score_number: isLiteral.value ? null : parseInt(cell.number, 10),
                score_letter: isLiteral.value ? cell.letter : null,
                observations: cell.observations || null,
            });
        });
    });

    if (entries.length === 0) {
        Swal2.fire({
            icon: 'warning',
            text: 'Ingresa al menos una nota antes de guardar.',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
        return;
    }

    saving.value = true;
    axios.post(route('aca_school_teacher_grades_store', props.section.id), {
        area: area.value,
        entries,
    }).then((res) => {
        Swal2.fire({
            icon: 'success',
            title: 'Enhorabuena',
            text: res.data?.message ?? 'Notas guardadas correctamente.',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
        router.reload({ only: ['students'], preserveScroll: true });
    }).catch((error) => {
        Swal2.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message ?? 'No se pudieron guardar las notas.',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    }).finally(() => {
        saving.value = false;
    });
};
</script>

<template>
    <AppLayout :title="'Registro de Notas · ' + section.name">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                { title: 'Registro de Notas', url: route('aca_school_teacher_grades') },
                { title: section.grade + ' · ' + section.name },
            ]" />

        <div class="panel mt-5">
            <div class="p-5 border-b border-[#ebedf2] dark:border-[#191e3a] flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold">{{ section.grade }} · {{ section.name }}</h3>
                    <p class="text-sm text-white-dark mt-1">
                        {{ section.level }} · Escala <span class="badge align-middle" :class="isLiteral ? 'bg-info' : 'bg-primary'">{{ isLiteral ? 'AD / A / B / C' : '0 a 20' }}</span>
                        <span v-if="!isLiteral" class="ml-2 text-xs">Aprobado con nota 11 o superior</span>
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <label class="form-label mb-0 font-semibold">Área curricular:</label>
                    <select v-model="area" @change="changeArea" class="form-select max-w-[320px]">
                        <option v-for="a in section.areas" :key="a" :value="a">{{ a }}</option>
                    </select>
                </div>
            </div>

            <div class="p-5">
                <div v-if="students.length === 0" class="text-center py-10">
                    <font-awesome-icon :icon="faTriangleExclamation" class="text-3xl text-warning" />
                    <p class="text-sm text-white-dark mt-3">No hay alumnos matriculados en esta sección para el año activo.</p>
                </div>

                <template v-else>
                    <div class="table-responsive">
                        <table class="table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Alumno</th>
                                    <th v-for="bim in section.bimesters" :key="bim" class="!text-center">
                                        {{ ['I', 'II', 'III', 'IV'][bim - 1] }} Bim.
                                    </th>
                                    <th class="!text-center">Promedio</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="student in students" :key="student.enrollment_id">
                                    <td>
                                        <div class="font-semibold">{{ student.full_name }}</div>
                                        <div class="text-xs text-white-dark">{{ student.student_code }}</div>
                                    </td>
                                    <td v-for="bim in section.bimesters" :key="bim" class="!text-center">
                                        <select
                                            v-if="isLiteral"
                                            :value="cellValue(student.enrollment_id, bim).letter"
                                            @change="setCell(student.enrollment_id, bim, { letter: $event.target.value })"
                                            class="form-select form-select-sm !w-20 mx-auto"
                                        >
                                            <option value="">-</option>
                                            <option v-for="l in LETTERS" :key="l" :value="l">{{ l }}</option>
                                        </select>
                                        <input
                                            v-else
                                            :value="cellValue(student.enrollment_id, bim).number"
                                            @input="onNumberInput(student.enrollment_id, bim, $event)"
                                            inputmode="numeric"
                                            placeholder="-"
                                            class="form-input form-input-sm !w-20 text-center mx-auto"
                                        />
                                    </td>
                                    <td class="!text-center">
                                        <template v-if="!isLiteral && averageOf(student.enrollment_id) !== null">
                                            <span class="badge" :class="parseFloat(averageOf(student.enrollment_id)) >= 11 ? 'bg-success' : 'bg-danger'">
                                                {{ averageOf(student.enrollment_id) }}
                                            </span>
                                        </template>
                                        <span v-else class="text-xs text-white-dark">-</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <p class="text-xs text-white-dark mt-3">
                        {{ filledCount }} nota(s) ingresada(s) en el área <strong>{{ area }}</strong>.
                        El guardado es por área: cambia de área y guarda nuevamente.
                    </p>

                    <div class="flex justify-end gap-3 mt-4">
                        <SecondaryButton type="button" @click="router.visit(route('aca_school_teacher_grades'))">
                            Volver
                        </SecondaryButton>
                        <PrimaryButton type="button" @click="save" :disabled="saving">
                            <icon-loader v-if="saving" class="w-4 h-4 mr-1" />
                            {{ saving ? 'Guardando...' : 'Guardar notas' }}
                        </PrimaryButton>
                    </div>
                </template>
            </div>
        </div>
    </AppLayout>
</template>
