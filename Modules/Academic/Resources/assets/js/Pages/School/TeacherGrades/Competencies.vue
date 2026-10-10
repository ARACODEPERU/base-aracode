<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import Swal2 from 'sweetalert2';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import iconLoader from '@/Components/vristo/icon/icon-loader.vue';
import { faTriangleExclamation, faPenToSquare, faFileExcel, faXmark } from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
    section: { type: Object, required: true },
    bimesters: { type: Array, default: () => [1, 2, 3, 4] },
    areas: { type: Array, default: () => [] },
    competencyByArea: { type: Object, default: () => ({}) },
    phrases: { type: Object, default: () => ({}) },
    students: { type: Array, default: () => [] },
    letters: { type: Array, default: () => ['AD', 'A', 'B', 'C'] },
});

const isLiteral = computed(() => props.section.scale === 'literal');
const BIMESTER_LABELS = { 1: 'I Bimestre', 2: 'II Bimestre', 3: 'III Bimestre', 4: 'IV Bimestre' };

const selectedArea = ref(props.areas[0]?.name ?? '');
const competencies = computed(() => props.competencyByArea[selectedArea.value] ?? []);

// form[enrollmentId][competencyId][bimester] = { score, conclusion }
// score = letra (AD/A/B/C) en escala literal, numero (0-20) en vigesimal
const form = ref({});
const saving = ref(false);

const buildForm = () => {
    const map = {};
    props.students.forEach((student) => {
        map[student.enrollment_id] = {};
        props.areas.forEach((area) => {
            (props.competencyByArea[area.name] ?? []).forEach((competency) => {
                map[student.enrollment_id][competency.id] = {};
                props.bimesters.forEach((bim) => {
                    const saved = student.grades?.[competency.id]?.[bim];
                    map[student.enrollment_id][competency.id][bim] = {
                        score: isLiteral.value ? (saved?.letter ?? '') : (saved?.number ?? ''),
                        conclusion: saved?.conclusion ?? '',
                    };
                });
            });
        });
    });
    return map;
};
form.value = buildForm();

const cell = (enrollmentId, competencyId, bimester) =>
    form.value[enrollmentId]?.[competencyId]?.[bimester] ?? { score: '', conclusion: '' };

const setCell = (enrollmentId, competencyId, bimester, patch) => {
    const target = form.value[enrollmentId]?.[competencyId]?.[bimester];
    if (target) Object.assign(target, patch);
};

const onScoreInput = (enrollmentId, competencyId, bimester, event) => {
    let value = event.target.value.replace(/[^0-9]/g, '');
    if (value !== '') value = String(Math.min(20, Math.max(0, parseInt(value, 10))));
    setCell(enrollmentId, competencyId, bimester, { score: value });
    event.target.value = value;
};

// Conclusion descriptiva obligatoria cuando el alumno no logra lo esperado
// (NL = B o C en literal; nota <= 13 en vigesimal), igual que en SIAGIE.
const conclusionRequired = (entry) => {
    if (isLiteral.value) return ['B', 'C'].includes(entry.score);
    return entry.score !== '' && parseInt(entry.score, 10) <= 13;
};

const savedCell = (enrollmentId, competencyId, bimester) => {
    const student = props.students.find(s => s.enrollment_id === enrollmentId);
    return student?.grades?.[competencyId]?.[bimester] ?? null;
};

const changedEntries = () => {
    const entries = [];
    props.students.forEach((student) => {
        props.areas.forEach((area) => {
            (props.competencyByArea[area.name] ?? []).forEach((competency) => {
                props.bimesters.forEach((bim) => {
                    const current = cell(student.enrollment_id, competency.id, bim);
                    const saved = savedCell(student.enrollment_id, competency.id, bim);
                    const savedScore = isLiteral.value ? (saved?.letter ?? '') : (saved?.number ?? '');
                    const savedConclusion = saved?.conclusion ?? '';
                    if (String(current.score) !== String(savedScore) || (current.conclusion ?? '') !== savedConclusion) {
                        entries.push({
                            enrollment_id: student.enrollment_id,
                            competency_id: competency.id,
                            bimester: bim,
                            score_letter: isLiteral.value ? (current.score || null) : null,
                            score_number: !isLiteral.value && current.score !== '' ? parseInt(current.score, 10) : null,
                            conclusion: current.conclusion || null,
                        });
                    }
                });
            });
        });
    });
    return entries;
};

const pendingCount = computed(() => changedEntries().length);

// Conteo de conclusiones obligatorias pendientes en TODA la grilla del area
const missingConclusions = computed(() => {
    let count = 0;
    props.students.forEach((student) => {
        (props.competencyByArea[selectedArea.value] ?? []).forEach((competency) => {
            props.bimesters.forEach((bim) => {
                const entry = cell(student.enrollment_id, competency.id, bim);
                if (entry.score !== '' && conclusionRequired(entry) && !entry.conclusion) count++;
            });
        });
    });
    return count;
});

// Modal de conclusion descriptiva
const modal = ref(null); // { enrollmentId, competencyId, bimester }
const modalText = ref('');
const modalStudent = computed(() => props.students.find(s => s.enrollment_id === modal.value?.enrollment_id));
const modalCompetency = computed(() => (props.competencyByArea[selectedArea.value] ?? []).find(c => c.id === modal.value?.competencyId));
const modalSuggestions = computed(() => {
    if (! modal.value) return [];
    const entry = cell(modal.value.enrollmentId, modal.value.competencyId, modal.value.bimester);
    let letterKey = 'C';
    if (isLiteral.value) {
        letterKey = entry.score || 'C';
    } else if (entry.score !== '') {
        const n = parseInt(entry.score, 10);
        letterKey = n >= 18 ? 'AD' : n >= 14 ? 'A' : n >= 11 ? 'B' : 'C';
    }
    return props.phrases[modal.value.competencyId]?.[letterKey] ?? [];
});

const openModal = (enrollmentId, competencyId, bimester) => {
    modal.value = { enrollmentId, competencyId, bimester };
    modalText.value = cell(enrollmentId, competencyId, bimester).conclusion ?? '';
};

const closeModal = () => {
    modal.value = null;
};

const applyModal = () => {
    if (! modal.value) return;
    setCell(modal.value.enrollmentId, modal.value.competencyId, modal.value.bimester, { conclusion: modalText.value });
    closeModal();
};

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

    const doSave = () => {
        saving.value = true;
        axios.post(route('aca_school_teacher_competencies_store', props.section.id), { entries })
            .then((res) => {
                Swal2.fire({
                    icon: 'success',
                    title: 'Enhorabuena',
                    text: res.data?.message ?? 'Notas guardadas correctamente.',
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
            })
            .catch((error) => {
                Swal2.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.response?.data?.message ?? 'No se pudieron guardar las notas.',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
            })
            .finally(() => {
                saving.value = false;
            });
    };

    if (missingConclusions.value > 0) {
        const swalConfirm = Swal2.mixin({
            customClass: {
                popup: 'sweet-alerts',
                confirmButton: 'btn btn-secondary',
                cancelButton: 'btn btn-dark ltr:mr-3 rtl:ml-3',
            },
            buttonsStyling: false,
        });
        swalConfirm.fire({
            title: 'Conclusiones pendientes',
            text: `Hay ${missingConclusions.value} conclusión(es) descriptiva(s) obligatoria(s) sin registrar (nivel B/C o nota baja). ¿Desea grabar de todos modos?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, grabar',
            cancelButtonText: 'Revisar',
            reverseButtons: true,
            padding: '2em',
        }).then((result) => {
            if (result.isConfirmed) doSave();
        });
        return;
    }

    doSave();
};

const exportBimester = ref(1);
const exportExcel = () => {
    window.open(route('aca_school_teacher_competencies_export', { sectionId: props.section.id, bimester: exportBimester.value }), '_blank');
};

const back = () => {
    router.visit(route('aca_school_teacher_grades'));
};

const hasScores = (enrollmentId) => {
    const map = form.value[enrollmentId] ?? {};
    return Object.values(map).some((byBim) => Object.values(byBim).some((e) => e.score !== '' || e.conclusion));
};
</script>

<template>
    <AppLayout :title="'Registro de Notas · ' + section.grade + ' ' + section.name">
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
                        {{ section.level }} · Evaluación por competencias
                        <span class="badge align-middle" :class="isLiteral ? 'bg-info' : 'bg-primary'">
                            {{ isLiteral ? 'Nivel de logro AD / A / B / C' : 'Escala vigesimal 0 a 20' }}
                        </span>
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <label class="form-label mb-0 font-semibold">Área curricular:</label>
                    <select v-model="selectedArea" class="form-select max-w-[340px]">
                        <option v-for="area in areas" :key="area.name" :value="area.name">{{ area.name }}</option>
                    </select>
                </div>
            </div>

            <div class="p-5">
                <div v-if="students.length === 0" class="text-center py-10">
                    <font-awesome-icon :icon="faTriangleExclamation" class="text-3xl text-warning" />
                    <p class="text-sm text-white-dark mt-3">No hay alumnos matriculados en esta sección para el año activo.</p>
                </div>

                <div v-else-if="competencies.length === 0" class="text-center py-10">
                    <font-awesome-icon :icon="faTriangleExclamation" class="text-3xl text-warning" />
                    <p class="text-sm text-white-dark mt-3">El área seleccionada no tiene competencias registradas.</p>
                </div>

                <template v-else>
                    <!-- Barra de acciones -->
                    <div class="flex flex-wrap items-center gap-3 mb-4">
                        <div class="flex items-center gap-2">
                            <label class="text-sm font-semibold">Exportar bimestre:</label>
                            <select v-model.number="exportBimester" class="form-select !w-40">
                                <option v-for="bim in bimesters" :key="bim" :value="bim">{{ BIMESTER_LABELS[bim] }}</option>
                            </select>
                            <button type="button" class="btn btn-outline-success" @click="exportExcel">
                                <font-awesome-icon :icon="faFileExcel" class="ltr:mr-1 rtl:ml-1" />
                                Exportar Excel
                            </button>
                        </div>

                        <div class="flex-1"></div>

                        <SecondaryButton type="button" @click="back">Cancelar</SecondaryButton>
                        <PrimaryButton type="button" @click="save" :disabled="saving">
                            <icon-loader v-if="saving" class="w-4 h-4 mr-1" />
                            {{ saving ? 'Guardando...' : 'Grabar' }}
                        </PrimaryButton>
                    </div>

                    <!-- Grilla: alumnos x competencias x 4 bimestres -->
                    <div class="table-responsive border rounded-md border-[#ebedf2] dark:border-[#191e3a]">
                        <table class="whitespace-nowrap">
                            <thead>
                                <tr>
                                    <th rowspan="3" class="!text-center !w-10">#</th>
                                    <th rowspan="3" class="min-w-[240px] text-left">APELLIDOS Y NOMBRES</th>
                                    <th v-for="bim in bimesters" :key="'t' + bim"
                                        :colspan="competencies.length" class="!text-center bg-primary/10">
                                        {{ BIMESTER_LABELS[bim] }}
                                    </th>
                                </tr>
                                <tr>
                                    <template v-for="bim in bimesters" :key="'c' + bim">
                                        <th v-for="competency in competencies" :key="competency.id + '-' + bim"
                                            class="!px-1 !text-center !font-normal"
                                            v-tippy:bottom>
                                            {{ competency.code }}
                                            <tippy target="bottom" placement="bottom">{{ competency.name }}</tippy>
                                        </th>
                                    </template>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(student, index) in students" :key="student.enrollment_id">
                                    <td class="!text-center text-white-dark">{{ index + 1 }}</td>
                                    <td class="font-semibold text-left">
                                        {{ student.full_name }}
                                        <div class="text-xs text-white-dark">{{ student.student_code }}</div>
                                    </td>
                                    <template v-for="bim in bimesters" :key="'g' + bim">
                                        <td v-for="competency in competencies" :key="competency.id + '-g' + bim"
                                            class="!p-1 !text-center">
                                            <div class="flex items-center justify-center gap-0.5">
                                                <select
                                                    v-if="isLiteral"
                                                    :value="cell(student.enrollment_id, competency.id, bim).score"
                                                    @change="setCell(student.enrollment_id, competency.id, bim, { score: $event.target.value })"
                                                    class="form-select form-select-sm !w-14 !px-1 text-center"
                                                    :class="cell(student.enrollment_id, competency.id, bim).score === '' ? 'opacity-50' : ''"
                                                >
                                                    <option value="">-</option>
                                                    <option v-for="l in letters" :key="l" :value="l">{{ l }}</option>
                                                </select>
                                                <input
                                                    v-else
                                                    :value="cell(student.enrollment_id, competency.id, bim).score"
                                                    @input="onScoreInput(student.enrollment_id, competency.id, bim, $event)"
                                                    inputmode="numeric"
                                                    placeholder="-"
                                                    class="form-input form-input-sm !w-14 text-center"
                                                />
                                                <button
                                                    type="button"
                                                    class="btn btn-sm !p-1"
                                                    :class="conclusionRequired(cell(student.enrollment_id, competency.id, bim)) && !cell(student.enrollment_id, competency.id, bim).conclusion && cell(student.enrollment_id, competency.id, bim).score !== '' ? 'btn-warning' : cell(student.enrollment_id, competency.id, bim).conclusion ? 'btn-success' : 'btn-white'"
                                                    :title="'Conclusión descriptiva — ' + competency.name"
                                                    @click="openModal(student.enrollment_id, competency.id, bim)"
                                                >
                                                    <font-awesome-icon :icon="faPenToSquare" class="!text-xs m-0" />
                                                </button>
                                            </div>
                                        </td>
                                    </template>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Leyenda de competencias (como el pie del archivo SIAGIE) -->
                    <div class="mt-4 p-4 border rounded-md border-[#ebedf2] dark:border-[#191e3a] bg-white-light/40 dark:bg-slate-800/40">
                        <p class="font-semibold text-sm mb-2">Leyenda — {{ selectedArea }}</p>
                        <ul class="space-y-1 text-sm text-white-dark">
                            <li v-if="isLiteral"><span class="badge bg-secondary !px-2">NL</span> Nivel de logro alcanzado (AD = logro destacado · A = logro esperado · B = en proceso · C = en inicio)</li>
                            <li v-if="isLiteral"><span class="badge bg-warning !px-2">📝</span> La conclusión descriptiva es obligatoria cuando el nivel es B o C (botón naranja = pendiente)</li>
                            <li v-if="!isLiteral"><span class="badge bg-warning !px-2">📝</span> La conclusión descriptiva es obligatoria con nota menor a 14 (botón naranja = pendiente)</li>
                            <li v-for="competency in competencies" :key="competency.id">
                                <span class="badge bg-primary !px-2">{{ competency.code }}</span> {{ competency.name }}
                            </li>
                        </ul>
                    </div>

                    <p class="text-xs text-white-dark mt-3">
                        <span v-if="pendingCount" class="text-warning font-semibold ltr:mr-3 rtl:ml-3">{{ pendingCount }} cambio(s) sin grabar.</span>
                        <span v-if="missingConclusions" class="text-danger font-semibold">{{ missingConclusions }} conclusión(es) obligatoria(s) pendiente(s) en esta área.</span>
                        <span v-if="!pendingCount && !missingConclusions">Todo registrado en el área <strong>{{ selectedArea }}</strong>.</span>
                    </p>
                </template>
            </div>
        </div>

        <!-- Modal de conclusion descriptiva -->
        <div v-if="modal" class="fixed inset-0 z-[999] flex items-center justify-center bg-black/50 p-4" @click.self="closeModal">
            <div class="bg-white dark:bg-slate-800 rounded-md w-full max-w-2xl p-5 shadow-lg">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold">Conclusión descriptiva de la competencia</h3>
                        <p class="text-sm text-white-dark mt-1">
                            {{ modalStudent?.full_name }} · {{ modalCompetency?.code }} {{ modalCompetency?.name }} · {{ BIMESTER_LABELS[modal?.bimester] }}
                        </p>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger" @click="closeModal">
                        <font-awesome-icon :icon="faXmark" />
                    </button>
                </div>

                <div class="mt-4">
                    <label class="form-label">Frases sugeridas — haga clic para usarlas y edítelas libremente:</label>
                    <div class="space-y-2 mt-2 max-h-52 overflow-y-auto">
                        <button
                            v-for="(phrase, i) in modalSuggestions"
                            :key="i"
                            type="button"
                            class="block w-full text-left text-sm p-3 rounded-md border border-[#ebedf2] dark:border-[#1b2e4b] hover:border-primary hover:bg-primary/5 transition"
                            @click="modalText = phrase"
                        >
                            {{ phrase }}
                        </button>
                        <p v-if="modalSuggestions.length === 0" class="text-sm text-white-dark">Sin sugerencias para este nivel de logro; escriba la conclusión libremente.</p>
                    </div>

                    <label class="form-label mt-4">Conclusión descriptiva:</label>
                    <textarea v-model="modalText" rows="4" class="form-textarea" placeholder="Escriba la conclusión descriptiva de la competencia para el alumno..."></textarea>
                </div>

                <div class="flex justify-end gap-3 mt-4">
                    <SecondaryButton type="button" @click="closeModal">Cancelar</SecondaryButton>
                    <PrimaryButton type="button" @click="applyModal">Aplicar</PrimaryButton>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
