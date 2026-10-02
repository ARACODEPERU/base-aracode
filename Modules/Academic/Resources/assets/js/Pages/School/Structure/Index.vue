<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { useForm, router } from '@inertiajs/vue3';
import Swal2 from 'sweetalert2';
import { ref, computed } from 'vue';
import { faPlus, faPencil, faTrash, faChalkboardUser } from '@fortawesome/free-solid-svg-icons';
import TeacherSelect from './../../../Components/TeacherSelect.vue';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';
import ModalSmall from '@/Components/ModalSmall.vue';
import ModalLarge from '@/Components/ModalLarge.vue';

const props = defineProps({
    tree: { type: Array, default: () => [] },
    shifts: { type: Object, default: () => ({}) },
});

const levelModal = ref(false);
const gradeModal = ref(false);
const sectionModal = ref(false);

const levelForm = useForm({ id: null, name: '', status: true });
const gradeForm = useForm({ id: null, level_id: null, name: '', status: true });
const sectionForm = useForm({ id: null, grade_id: null, name: '', capacity: 30, shift: 'manana', tutor_person_id: null, auxiliary_person_id: null, status: true });
const tutorLabel = ref('');
const auxiliaryLabel = ref('');
const savingLevel = ref(false);
const savingGrade = ref(false);
const savingSection = ref(false);

const shiftOptions = [
    { value: 'manana', label: 'Mañana' },
    { value: 'tarde', label: 'Tarde' },
    { value: 'noche', label: 'Noche' },
    { value: 'jornada', label: 'Jornada completa' },
];

const showMessage = (msg = '', type = 'success') => {
    const toast = Swal2.mixin({
        toast: true,
        position: 'top',
        showConfirmButton: false,
        timer: 3000,
        customClass: { container: 'toast' },
    });
    toast.fire({ icon: type, title: msg, padding: '10px 20px' });
};

const confirmDelete = (url, title) => {
    const swalConfirm = Swal2.mixin({
        customClass: {
            popup: 'sweet-alerts',
            confirmButton: 'btn btn-secondary',
            cancelButton: 'btn btn-dark ltr:mr-3 rtl:ml-3',
        },
        buttonsStyling: false,
    });
    swalConfirm.fire({
        title: '¿Estás seguro?',
        text: title,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '¡Sí, eliminar!',
        cancelButtonText: 'Cancelar',
        showLoaderOnConfirm: true,
        reverseButtons: true,
        padding: '2em',
        customClass: 'sweet-alerts',
        preConfirm: () => {
            return axios.delete(url).then((res) => {
                if (!res.data.success) {
                    Swal2.showValidationMessage(res.data.message);
                }
                return res;
            });
        },
        allowOutsideClick: () => !Swal2.isLoading(),
    }).then((result) => {
        if (result.isConfirmed) {
            showMessage('Eliminado correctamente.');
            router.visit(route('aca_school_structure'), { replace: true, preserveState: true, preserveScroll: true });
        }
    });
};

/* ---------------------- NIVELES ---------------------- */
const openLevelCreate = () => {
    levelForm.id = null;
    levelForm.name = '';
    levelForm.status = true;
    levelModal.value = true;
};
const openLevelEdit = (level) => {
    levelForm.id = level.id;
    levelForm.name = level.name;
    levelForm.status = level.status;
    levelModal.value = true;
};
const saveLevel = () => {
    savingLevel.value = true;
    const url = levelForm.id
        ? route('aca_school_structure_level_update', levelForm.id)
        : route('aca_school_structure_level_store');
    const method = levelForm.id ? 'put' : 'post';
    axios[method](url, { name: levelForm.name, status: levelForm.status }).then(() => {
        levelModal.value = false;
        showMessage('Nivel guardado correctamente');
        router.visit(route('aca_school_structure'), { replace: true, preserveState: true, preserveScroll: true });
    }).catch((err) => {
        showMessage(err.response?.data?.errors?.name?.[0] ?? 'Error al guardar', 'error');
    }).finally(() => {
        savingLevel.value = false;
    });
};
const deleteLevel = (level) => confirmDelete(route('aca_school_structure_level_destroy', level.id), 'Se eliminará el nivel y sus grados/secciones sin matrículas.');

/* ---------------------- GRADOS ---------------------- */
const openGradeCreate = (levelId) => {
    gradeForm.id = null;
    gradeForm.level_id = levelId;
    gradeForm.name = '';
    gradeForm.status = true;
    gradeModal.value = true;
};
const openGradeEdit = (grade) => {
    gradeForm.id = grade.id;
    gradeForm.level_id = grade.level_id ?? null;
    gradeForm.name = grade.name;
    gradeForm.status = grade.status;
    gradeModal.value = true;
};
const saveGrade = () => {
    savingGrade.value = true;
    const url = gradeForm.id
        ? route('aca_school_structure_grade_update', gradeForm.id)
        : route('aca_school_structure_grade_store');
    const method = gradeForm.id ? 'put' : 'post';
    axios[method](url, { level_id: gradeForm.level_id, name: gradeForm.name, status: gradeForm.status }).then(() => {
        gradeModal.value = false;
        showMessage('Grado guardado correctamente');
        router.visit(route('aca_school_structure'), { replace: true, preserveState: true, preserveScroll: true });
    }).catch((err) => {
        showMessage(err.response?.data?.errors?.name?.[0] ?? 'Error al guardar', 'error');
    }).finally(() => {
        savingGrade.value = false;
    });
};
const deleteGrade = (grade) => confirmDelete(route('aca_school_structure_grade_destroy', grade.id), 'Se eliminará el grado y sus secciones sin matrículas.');

/* ---------------------- SECCIONES ---------------------- */
const openSectionCreate = (gradeId) => {
    sectionForm.id = null;
    sectionForm.grade_id = gradeId;
    sectionForm.name = '';
    sectionForm.capacity = 30;
    sectionForm.shift = 'manana';
    sectionForm.tutor_person_id = null;
    sectionForm.auxiliary_person_id = null;
    tutorLabel.value = '';
    auxiliaryLabel.value = '';
    sectionForm.status = true;
    sectionModal.value = true;
};
const openSectionEdit = (section) => {
    sectionForm.id = section.id;
    sectionForm.grade_id = section.grade_id;
    sectionForm.name = section.name;
    sectionForm.capacity = section.capacity;
    sectionForm.shift = section.shift;
    sectionForm.tutor_person_id = section.tutor_person_id;
    sectionForm.auxiliary_person_id = section.auxiliary_person_id;
    tutorLabel.value = section.tutor_name ?? '';
    auxiliaryLabel.value = section.auxiliary_name ?? '';
    sectionForm.status = section.status;
    sectionModal.value = true;
};
const saveSection = () => {
    savingSection.value = true;
    const url = sectionForm.id
        ? route('aca_school_structure_section_update', sectionForm.id)
        : route('aca_school_structure_section_store');
    const method = sectionForm.id ? 'put' : 'post';
    axios[method](url, {
        grade_id: sectionForm.grade_id,
        name: sectionForm.name,
        capacity: sectionForm.capacity,
        shift: sectionForm.shift,
        tutor_person_id: sectionForm.tutor_person_id,
        auxiliary_person_id: sectionForm.auxiliary_person_id,
        status: sectionForm.status,
    }).then(() => {
        sectionModal.value = false;
        showMessage('Sección guardada correctamente');
        router.visit(route('aca_school_structure'), { replace: true, preserveState: true, preserveScroll: true });
    }).catch((err) => {
        const errors = err.response?.data?.errors ?? {};
        showMessage(Object.values(errors)[0]?.[0] ?? 'Error al guardar', 'error');
    }).finally(() => {
        savingSection.value = false;
    });
};
const deleteSection = (section) => confirmDelete(route('aca_school_structure_section_destroy', section.id), 'Se eliminará la sección (no debe tener matrículas activas).');

const shiftLabel = (v) => shiftOptions.find((s) => s.value === v)?.label ?? v;

const closeLevelModal = () => { levelModal.value = false; };
const closeGradeModal = () => { gradeModal.value = false; };
const closeSectionModal = () => { sectionModal.value = false; };
</script>

<template>
    <AppLayout title="Estructura Académica">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                { route: route('aca_school_years_list'), title: 'Colegio' },
                { title: 'Estructura Académica' },
            ]"
        />
        <div class="pt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl">Estructura Académica</h2>
                <button type="button" class="btn btn-primary" @click="openLevelCreate">
                    <font-awesome-icon :icon="faPlus" class="ltr:mr-2 rtl:ml-2" /> Nuevo Nivel
                </button>
            </div>

            <div v-if="tree.length === 0" class="mt-5 panel p-8 text-center text-white-dark">
                Sin estructura. Active un año escolar en el menú <b>Años Escolares</b> para precargar Inicial, Primaria y Secundaria, o cree los niveles manualmente.
            </div>

            <div v-for="level in tree" :key="level.id" class="mt-5 panel">
                <div class="flex items-center justify-between p-4 border-b border-[#ebedf2] dark:border-[#191e3a]">
                    <h3 class="text-lg font-semibold">{{ level.name }}</h3>
                    <div class="flex gap-1">
                        <button type="button" class="btn btn-sm btn-outline-primary" @click="openGradeCreate(level.id)">
                            <font-awesome-icon :icon="faPlus" class="ltr:mr-1 rtl:ml-1" /> Grado
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="openLevelEdit(level)">
                            <font-awesome-icon :icon="faPencil" />
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" @click="deleteLevel(level)">
                            <font-awesome-icon :icon="faTrash" />
                        </button>
                    </div>
                </div>

                <div v-for="grade in level.grades" :key="grade.id" class="p-4 border-b border-[#ebedf2] dark:border-[#191e3a] last:border-b-0">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="font-semibold text-base">{{ grade.name }}</h4>
                        <div class="flex gap-1">
                            <button type="button" class="btn btn-sm btn-outline-success" @click="openSectionCreate(grade.id)">
                                <font-awesome-icon :icon="faPlus" class="ltr:mr-1 rtl:ml-1" /> Sección
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" @click="openGradeEdit(grade)">
                                <font-awesome-icon :icon="faPencil" />
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" @click="deleteGrade(grade)">
                                <font-awesome-icon :icon="faTrash" />
                            </button>
                        </div>
                    </div>

                    <div v-if="grade.sections.length === 0" class="text-sm text-white-dark pl-2">Sin secciones</div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <div v-for="section in grade.sections" :key="section.id" class="border border-[#ebedf2] dark:border-[#191e3a] rounded-md p-3">
                            <div class="flex items-center justify-between mb-2">
                                <span class="badge bg-primary">{{ section.name }}</span>
                                <div class="flex gap-1">
                                    <button type="button" class="btn btn-xs btn-outline-secondary" @click="openSectionEdit(section)">
                                        <font-awesome-icon :icon="faPencil" />
                                    </button>
                                    <button type="button" class="btn btn-xs btn-outline-danger" @click="deleteSection(section)">
                                        <font-awesome-icon :icon="faTrash" />
                                    </button>
                                </div>
                            </div>
                            <div class="text-xs text-white-dark">{{ shiftLabel(section.shift) }}</div>
                            <div v-if="section.tutor_name" class="text-xs mt-1 truncate" title="Tutor">Tutor: {{ section.tutor_name }}</div>
                            <div v-if="section.auxiliary_name" class="text-xs text-white-dark truncate" title="Auxiliar">Aux.: {{ section.auxiliary_name }}</div>
                            <div class="mt-2 flex items-center justify-between">
                                <span class="text-sm font-semibold">{{ section.taken }} / {{ section.capacity }}</span>
                                <span class="text-xs" :class="section.available > 0 ? 'text-success' : 'text-danger'">
                                    {{ section.available > 0 ? section.available + ' vacantes' : 'Sin vacantes' }}
                                </span>
                            </div>
                            <div class="h-1.5 bg-[#ebedf2] dark:bg-[#191e3a] rounded-full mt-2 overflow-hidden">
                                <div
                                    class="h-full rounded-full"
                                    :class="section.available > 0 ? 'bg-primary' : 'bg-danger'"
                                    :style="{ width: Math.min(100, Math.round((section.taken / Math.max(1, section.capacity)) * 100)) + '%' }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Nivel -->
        <ModalSmall :onClose="closeLevelModal" :show="levelModal" :icon="'/img/aula.png'">
            <template #title>
                {{ levelForm.id ? 'Editar' : 'Nuevo' }} Nivel
            </template>
            <template #content>
                <label class="form-label">Nombre *</label>
                <input v-model="levelForm.name" type="text" class="form-input" placeholder="Ej: Primaria" />
                <div class="flex items-center mt-3">
                    <input id="level_status" v-model="levelForm.status" type="checkbox" class="form-checkbox" />
                    <label for="level_status" class="ml-2 text-sm">Activo</label>
                </div>
            </template>
            <template #buttons>
                <button type="button" class="btn btn-primary" @click="saveLevel" :disabled="savingLevel">
                    <IconLoader v-if="savingLevel" class="w-4 h-4 ltr:mr-2 rtl:ml-2" /> Guardar
                </button>
            </template>
        </ModalSmall>

        <!-- Modal Grado -->
        <ModalSmall :onClose="closeGradeModal" :show="gradeModal" :icon="'/img/aula.png'">
            <template #title>
                {{ gradeForm.id ? 'Editar' : 'Nuevo' }} Grado
            </template>
            <template #content>
                <label class="form-label">Nombre *</label>
                <input v-model="gradeForm.name" type="text" class="form-input" placeholder="Ej: 3°" />
                <div class="flex items-center mt-3">
                    <input id="grade_status" v-model="gradeForm.status" type="checkbox" class="form-checkbox" />
                    <label for="grade_status" class="ml-2 text-sm">Activo</label>
                </div>
            </template>
            <template #buttons>
                <button type="button" class="btn btn-primary" @click="saveGrade" :disabled="savingGrade">
                    <IconLoader v-if="savingGrade" class="w-4 h-4 ltr:mr-2 rtl:ml-2" /> Guardar
                </button>
            </template>
        </ModalSmall>

        <!-- Modal Sección -->
        <ModalLarge :onClose="closeSectionModal" :show="sectionModal" :icon="'/img/aula.png'">
            <template #title>
                {{ sectionForm.id ? 'Editar' : 'Nueva' }} Sección
            </template>
            <template #content>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Nombre *</label>
                        <input v-model="sectionForm.name" type="text" class="form-input" placeholder="Ej: A" />
                    </div>
                    <div>
                        <label class="form-label">Vacantes (capacidad) *</label>
                        <input v-model="sectionForm.capacity" type="number" min="1" max="80" class="form-input" />
                    </div>
                    <div>
                        <label class="form-label">Turno *</label>
                        <select v-model="sectionForm.shift" class="form-select">
                            <option v-for="s in shiftOptions" :key="s.value" :value="s.value">{{ s.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Tutor</label>
                        <TeacherSelect v-model="sectionForm.tutor_person_id" v-model:label="tutorLabel" placeholder="Buscar docente (nombre o DNI)" />
                    </div>
                    <div>
                        <label class="form-label">Docente auxiliar</label>
                        <TeacherSelect v-model="sectionForm.auxiliary_person_id" v-model:label="auxiliaryLabel" placeholder="Buscar auxiliar (nombre o DNI)" />
                    </div>
                </div>
                <div class="flex items-center mt-3">
                    <input id="section_status" v-model="sectionForm.status" type="checkbox" class="form-checkbox" />
                    <label for="section_status" class="ml-2 text-sm">Activo</label>
                </div>
            </template>
            <template #buttons>
                <button type="button" class="btn btn-primary" @click="saveSection" :disabled="savingSection">
                    <IconLoader v-if="savingSection" class="w-4 h-4 ltr:mr-2 rtl:ml-2" /> Guardar
                </button>
            </template>
        </ModalLarge>
    </AppLayout>
</template>
