<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { useForm, Link } from '@inertiajs/vue3';
import Swal2 from 'sweetalert2';
import { ref, watch, computed } from 'vue';
import iconLoader from '@/Components/vristo/icon/icon-loader.vue';
import { faCircleInfo, faTriangleExclamation } from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
    years: { type: Array, default: () => [] },
    levels: { type: Array, default: () => [] },
    typeLabels: { type: Object, default: () => ({}) },
    preselect: { type: Object, default: () => ({}) },
    guardianRelationships: { type: Object, default: () => ({}) },
});

const form = useForm({
    year_id: props.preselect.year_id ?? (props.years.find((y) => y.status === 'active')?.id ?? null),
    student_id: props.preselect.student_id ?? null,
    student_label: null,
    section_id: null,
    type: 'nueva',
    enrollment_date: new Date().toISOString().slice(0, 10),
    guardian_person_id: null,
    guardian_label: null,
    guardian_relationship: null,
    guardian_phone: null,
    observations: null,
});

/* ------------- Búsqueda de alumno ------------- */
const studentQuery = ref('');
const studentResults = ref([]);
const studentSearching = ref(false);

watch(studentQuery, async (q) => {
    if (! q || q.length < 3) {
        studentResults.value = [];
        return;
    }
    studentSearching.value = true;
    try {
        const res = await axios.post(route('aca_school_enrollments_search_students'), { search: q });
        studentResults.value = res.data;
    } finally {
        studentSearching.value = false;
    }
});

const pickStudent = (s) => {
    form.student_id = s.id;
    form.student_label = `${s.full_name} (${s.number})`;
    studentQuery.value = '';
    studentResults.value = [];
    clearGuardian();
    loadStudentGuardians(s.id);
};

const clearStudent = () => {
    form.student_id = null;
    form.student_label = null;
    studentGuardians.value = [];
    clearGuardian();
};

/* ------------- Cascada nivel -> grado -> sección ------------- */
const grades = ref([]);
const sections = ref([]);
const gradesLoading = ref(false);
const sectionsLoading = ref(false);
const cascadeLoading = computed(() => gradesLoading.value || sectionsLoading.value);

watch(() => form.year_id, () => {
    form.section_id = null;
    if (gradeId.value) {
        loadSections();
    } else {
        sections.value = [];
    }
});

const loadGrades = async () => {
    grades.value = [];
    sections.value = [];
    form.section_id = null;
    if (! levelId.value) return;
    gradesLoading.value = true;
    try {
        const res = await axios.post(route('aca_school_enrollments_grades'), { level_id: levelId.value });
        grades.value = res.data;
    } finally {
        gradesLoading.value = false;
    }
};

const levelId = ref(null);

const onLevelChange = () => loadGrades();

const loadSections = async () => {
    sections.value = [];
    form.section_id = null;
    if (! gradeId.value || ! form.year_id) return;
    sectionsLoading.value = true;
    try {
        const res = await axios.post(route('aca_school_enrollments_sections'), {
            grade_id: gradeId.value,
            year_id: form.year_id,
        });
        sections.value = res.data;
    } finally {
        sectionsLoading.value = false;
    }
};

/* ------------- Validación: alumno ya matriculado en el año ------------- */
const alreadyEnrolled = ref(false);
const enrolledMessage = ref('');

const checkEnrollment = async () => {
    alreadyEnrolled.value = false;
    enrolledMessage.value = '';
    if (! form.student_id || ! form.year_id) return;
    try {
        const res = await axios.post(route('aca_school_enrollments_check'), {
            student_id: form.student_id,
            year_id: form.year_id,
        });
        alreadyEnrolled.value = !!res.data.enrolled;
        enrolledMessage.value = res.data.message ?? '';
    } catch (e) {
        alreadyEnrolled.value = false;
        enrolledMessage.value = '';
    }
};

watch([() => form.student_id, () => form.year_id], checkEnrollment);

const gradeId = ref(null);

const sectionHint = computed(() => {
    if (! form.year_id) return 'Seleccione el año escolar para ver vacantes.';
    if (! gradeId.value) return 'Seleccione el nivel y el grado para ver las secciones disponibles.';
    return 'No hay secciones activas para el grado seleccionado en este año.';
});

/* ------------- Búsqueda de apoderado ------------- */
const guardianQuery = ref('');
const guardianResults = ref([]);

watch(guardianQuery, async (q) => {
    if (! q || q.length < 3) {
        guardianResults.value = [];
        return;
    }
    const res = await axios.post(route('aca_school_enrollments_search_guardians'), { search: q });
    guardianResults.value = res.data;
});

const pickGuardian = (p) => {
    form.guardian_person_id = p.id;
    form.guardian_label = `${p.full_name} (${p.number})`;
    guardianQuery.value = '';
    guardianResults.value = [];
};

const clearGuardian = () => {
    form.guardian_person_id = null;
    form.guardian_label = null;
    form.guardian_relationship = null;
    form.guardian_phone = null;
};

/* ------------- Apoderados ya vinculados al alumno ------------- */
const studentGuardians = ref([]);

const loadStudentGuardians = async (studentId) => {
    studentGuardians.value = [];
    if (! studentId) return;
    try {
        const res = await axios.get(route('aca_school_students_guardians_list', studentId));
        if (res.data.success) {
            studentGuardians.value = res.data.guardians;
        }
    } catch (e) {
        studentGuardians.value = [];
    }
};

const pickLinkedGuardian = (g) => {
    form.guardian_person_id = g.person?.id ?? null;
    form.guardian_label = g.person?.full_name ? `${g.person.full_name} (${g.person.number})` : null;
    form.guardian_relationship = props.guardianRelationships[g.relationship] ?? g.relationship;
    form.guardian_phone = g.person?.telephone ?? null;
};

const toggleGuardianStatus = (g) => {
    const newStatus = ! g.status;
    axios.put(route('aca_school_students_guardians_update', g.id), {
        relationship: g.relationship,
        is_primary: g.is_primary,
        status: newStatus,
    }).then(() => {
        g.status = newStatus;
        if (! newStatus && form.guardian_person_id === g.person?.id) {
            clearGuardian();
        }
    }).catch((error) => {
        Swal2.fire({
            icon: 'error',
            text: error.response?.data?.message ?? 'No se pudo actualizar el apoderado',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    });
};

if (form.student_id) {
    loadStudentGuardians(form.student_id);
}

const submitEnrollment = () => {
    if (alreadyEnrolled.value) return;
    form.post(route('aca_school_enrollments_store'), {
        preserveScroll: true,
        onSuccess: () => {
            Swal2.fire({
                title: 'Enhorabuena',
                text: 'Matrícula registrada correctamente',
                icon: 'success',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        },
    });
};
</script>

<template>
    <AppLayout title="Nueva Matrícula">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                { route: route('aca_school_enrollments_list'), title: 'Matrículas' },
                { title: 'Nueva' },
            ]"
        />
        <div class="pt-5">
            <div class="panel p-6">
                <h3 class="text-lg font-semibold mb-4">Nueva Matrícula</h3>
                <form @submit.prevent class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Año escolar -->
                    <div>
                        <label class="form-label">Año escolar *</label>
                        <select v-model="form.year_id" class="form-select" :class="{ 'border-danger': form.errors.year_id }">
                            <option value="" disabled>Seleccione...</option>
                            <option v-for="y in years" :key="y.id" :value="y.id">
                                {{ y.year }} {{ y.status === 'active' ? '(activo)' : y.status === 'finished' ? '(cerrado)' : '' }}
                            </option>
                        </select>
                        <p v-if="form.errors.year_id" class="text-danger text-xs mt-1">{{ form.errors.year_id }}</p>
                    </div>

                    <!-- Alumno -->
                    <div>
                        <label class="form-label">Alumno *</label>
                        <div v-if="form.student_id" class="flex items-center gap-2">
                            <input type="text" class="form-input" :value="form.student_label" disabled />
                            <button type="button" class="btn btn-sm btn-outline-danger" @click="clearStudent">X</button>
                        </div>
                        <div v-else class="relative">
                            <div class="relative">
                                <input v-model="studentQuery" type="text" class="form-input ltr:pr-10 rtl:pl-10" placeholder="Mínimo 3 letras o DNI..." />
                                <icon-loader v-if="studentSearching" class="w-4 h-4 text-primary absolute ltr:right-3 rtl:left-3 top-1/2 -translate-y-1/2" />
                            </div>
                            <div v-if="studentResults.length" class="absolute z-50 w-full panel p-2 max-h-60 overflow-y-auto">
                                <button
                                    type="button"
                                    v-for="s in studentResults"
                                    :key="s.id"
                                    class="w-full text-left px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700"
                                    @click="pickStudent(s)"
                                >
                                    <div class="text-sm">{{ s.full_name }}</div>
                                    <div class="text-xs text-white-dark">{{ s.number }} · {{ s.student_code }}</div>
                                </button>
                            </div>
                            <p class="text-xs text-white-dark mt-1">¿No aparece? Regístrelo primero en Alumnos.</p>
                        </div>
                        <p v-if="form.errors.student_id" class="text-danger text-xs mt-1">{{ form.errors.student_id }}</p>
                        <p v-if="alreadyEnrolled" class="text-danger text-xs mt-1 flex items-center gap-1">
                            <font-awesome-icon :icon="faTriangleExclamation" class="w-3 h-3 shrink-0" />
                            {{ enrolledMessage }}
                        </p>
                    </div>

                    <!-- Nivel -->
                    <div>
                        <label class="form-label">Nivel *</label>
                        <select v-model="levelId" class="form-select" @change="onLevelChange">
                            <option value="" disabled>Seleccione...</option>
                            <option v-for="l in levels" :key="l.id" :value="l.id">{{ l.name }}</option>
                        </select>
                    </div>

                    <!-- Grado -->
                    <div>
                        <label class="form-label">Grado *</label>
                        <select v-model="gradeId" class="form-select" :disabled="! grades.length" @change="loadSections">
                            <option value="" disabled>Seleccione...</option>
                            <option v-for="g in grades" :key="g.id" :value="g.id">{{ g.name }}</option>
                        </select>
                    </div>

                    <!-- Sección -->
                    <div class="sm:col-span-2">
                        <label class="form-label">Sección *</label>
                        <div v-if="cascadeLoading" class="flex items-center gap-2 text-sm text-white-dark py-3">
                            <icon-loader class="w-4 h-4 text-primary" />
                            Cargando secciones disponibles...
                        </div>
                        <div v-else-if="sections.length" class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                            <button
                                type="button"
                                v-for="s in sections"
                                :key="s.id"
                                class="border rounded-md p-3 text-left transition"
                                :class="form.section_id === s.id
                                    ? 'border-primary bg-primary/10'
                                    : s.available > 0
                                        ? 'border-[#ebedf2] dark:border-[#191e3a] hover:border-primary'
                                        : 'border-danger/40 opacity-60 cursor-not-allowed'"
                                :disabled="s.available === 0"
                                @click="form.section_id = s.id"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold">Sección {{ s.name }}</span>
                                    <span class="text-xs" :class="s.available > 0 ? 'text-success' : 'text-danger'">
                                        {{ s.available > 0 ? s.available + ' vacantes' : 'Sin vacantes' }}
                                    </span>
                                </div>
                                <div class="text-xs text-white-dark mt-1">{{ s.shift_label }} · {{ s.taken }}/{{ s.capacity }} ocupadas</div>
                            </button>
                        </div>
                        <p v-else class="text-sm text-white-dark italic flex items-center gap-2">
                            <font-awesome-icon :icon="faCircleInfo" class="w-4 h-4 shrink-0" />
                            {{ sectionHint }}
                        </p>
                        <p v-if="form.errors.section_id" class="text-danger text-xs mt-1">{{ form.errors.section_id }}</p>
                    </div>

                    <!-- Tipo -->
                    <div>
                        <label class="form-label">Tipo de matrícula *</label>
                        <select v-model="form.type" class="form-select">
                            <option v-for="(label, key) in typeLabels" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>

                    <!-- Fecha -->
                    <div>
                        <label class="form-label">Fecha de matrícula *</label>
                        <input v-model="form.enrollment_date" type="date" class="form-input" />
                        <p v-if="form.errors.enrollment_date" class="text-danger text-xs mt-1">{{ form.errors.enrollment_date }}</p>
                    </div>

                    <!-- Apoderado -->
                    <div class="sm:col-span-2">
                        <div class="flex items-center justify-between">
                            <label class="form-label">Apoderado</label>
                            <button v-if="studentGuardians.length && form.guardian_person_id" type="button" class="text-xs text-danger hover:underline" @click="clearGuardian">Quitar selección</button>
                        </div>
                        <div v-if="studentGuardians.length" class="space-y-2">
                            <p class="text-xs text-white-dark">Apoderados vinculados al alumno. Marque el apoderado de esta matrícula.</p>
                            <div
                                v-for="g in studentGuardians"
                                :key="g.id"
                                class="flex items-center gap-3 border rounded-md p-3 transition"
                                :class="form.guardian_person_id === g.person?.id
                                    ? 'border-primary bg-primary/10'
                                    : g.status
                                        ? 'border-[#ebedf2] dark:border-[#191e3a]'
                                        : 'border-danger/40 opacity-60'"
                            >
                                <input
                                    type="radio"
                                    name="guardian_pick"
                                    class="form-radio shrink-0"
                                    :checked="form.guardian_person_id === g.person?.id"
                                    :disabled="! g.status"
                                    @change="pickLinkedGuardian(g)"
                                />
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-semibold truncate">{{ g.person?.full_name }}</div>
                                    <div class="text-xs text-white-dark">{{ g.person?.number }} · {{ guardianRelationships[g.relationship] ?? g.relationship }}{{ g.person?.telephone ? ' · ' + g.person.telephone : '' }}</div>
                                </div>
                                <label class="flex items-center gap-2 text-sm whitespace-nowrap cursor-pointer">
                                    <input type="checkbox" class="form-checkbox" :checked="g.status" @change="toggleGuardianStatus(g)" />
                                    Activo
                                </label>
                            </div>
                        </div>
                        <div v-else-if="form.guardian_person_id" class="flex items-center gap-2">
                            <input type="text" class="form-input" :value="form.guardian_label" disabled />
                            <button type="button" class="btn btn-sm btn-outline-danger" @click="clearGuardian">X</button>
                        </div>
                        <div v-else class="relative">
                            <input v-model="guardianQuery" type="text" class="form-input" placeholder="Buscar por nombre o DNI (opcional)" />
                            <div v-if="guardianResults.length" class="absolute z-50 w-full panel p-2 max-h-60 overflow-y-auto">
                                <button
                                    type="button"
                                    v-for="p in guardianResults"
                                    :key="p.id"
                                    class="w-full text-left px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700"
                                    @click="pickGuardian(p)"
                                >
                                    <div class="text-sm">{{ p.full_name }}</div>
                                    <div class="text-xs text-white-dark">{{ p.number }} {{ p.telephone ? '· ' + p.telephone : '' }}</div>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="! studentGuardians.length">
                        <label class="form-label">Parentesco</label>
                        <input v-model="form.guardian_relationship" type="text" class="form-input" placeholder="Madre, Padre, Tío..." />
                    </div>

                    <div v-if="! studentGuardians.length">
                        <label class="form-label">Teléfono del apoderado</label>
                        <input v-model="form.guardian_phone" type="text" class="form-input" />
                    </div>

                    <div class="sm:col-span-2">
                        <label class="form-label">Observaciones</label>
                        <textarea v-model="form.observations" rows="2" class="form-textarea"></textarea>
                    </div>

                    <div class="sm:col-span-2 flex justify-end gap-2">
                        <Link :href="route('aca_school_enrollments_list')" class="btn btn-outline-danger">Cancelar</Link>
                        <button type="button" class="btn btn-primary" :disabled="form.processing || ! form.section_id || alreadyEnrolled" @click="submitEnrollment">
                            <icon-loader v-if="form.processing" class="w-4 h-4 mr-1" />
                            {{ form.processing ? 'Matriculando...' : 'Matricular' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
