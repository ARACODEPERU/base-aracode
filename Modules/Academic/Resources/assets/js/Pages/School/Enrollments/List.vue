<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import Pagination from '@/Components/Pagination.vue';
import { router, Link } from '@inertiajs/vue3';
import Swal2 from 'sweetalert2';
import { ref, watch } from 'vue';

const props = defineProps({
    enrollments: { type: Object, default: () => ({}) },
    years: { type: Array, default: () => [] },
    levels: { type: Array, default: () => [] },
    grades: { type: Array, default: () => [] },
    sections: { type: Array, default: () => [] },
    typeLabels: { type: Object, default: () => ({}) },
    statusLabels: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const filters = ref({
    year_id: props.filters.year_id ?? '',
    level_id: props.filters.level_id ?? '',
    grade_id: props.filters.grade_id ?? '',
    section_id: props.filters.section_id ?? '',
    status: props.filters.status ?? '',
    search: props.filters.search ?? '',
});

const statusBadges = {
    activo: 'bg-success',
    retirado: 'bg-secondary',
    traslado_salida: 'bg-warning',
    anulado: 'bg-danger',
};

const applyFilters = () => {
    router.get(route('aca_school_enrollments_list'), filters.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

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

/* ---------------- Cambio de estado ---------------- */
const changeStatus = (enrollment, newStatus) => {
    const questions = {
        retirado: 'El alumno dejará de ocupar vacante en la sección.',
        traslado_salida: 'Se registrará la salida del alumno por traslado a otro colegio.',
        anulado: 'La matrícula quedará anulada. Esta acción no se puede revertir.',
        activo: 'La matrícula volverá a estado activo (ocupa vacante).',
    };

    Swal2.fire({
        title: '¿Confirmar cambio de estado?',
        text: questions[newStatus] ?? '',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        padding: '2em',
        customClass: 'sweet-alerts',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return axios.put(route('aca_school_enrollments_status', enrollment.id), { status: newStatus }).catch((err) => {
                Swal2.showValidationMessage(err.response?.data?.errors?.status?.[0] ?? 'No se pudo cambiar el estado');
            });
        },
        allowOutsideClick: () => !Swal2.isLoading(),
    }).then((result) => {
        if (result.isConfirmed) {
            showMessage('Estado actualizado');
            router.visit(route('aca_school_enrollments_list'), { replace: true, preserveState: true, preserveScroll: true });
        }
    });
};

/* ---------------- Edición de matrícula ---------------- */
const editModal = ref(false);
const editSections = ref([]);
const editForm = ref({
    id: null,
    student_name: '',
    year_number: null,
    section_id: null,
    type: null,
    guardian_person_id: null,
    guardian_relationship: null,
    guardian_phone: null,
    observations: null,
    original_section_id: null,
});

const openEdit = (enrollment) => {
    editForm.value = {
        id: enrollment.id,
        student_name: enrollment.student_name,
        year_number: enrollment.year_number,
        section_id: enrollment.section_id,
        type: enrollment.type,
        guardian_person_id: null,
        guardian_relationship: enrollment.guardian_relationship,
        guardian_phone: enrollment.guardian_phone,
        observations: enrollment.observations,
        original_section_id: enrollment.section_id,
    };
    editModal.value = true;
};

const loadEditSections = async () => {
    const gradeId = props.sections.find((s) => s.id === Number(editForm.value.original_section_id))?.grade_id;
    if (! gradeId) return;
    const res = await axios.post(route('aca_school_enrollments_sections'), {
        grade_id: gradeId,
        year_id: props.enrollments.data.find((e) => e.id === editForm.value.id)?.year_id,
    });
    editSections.value = res.data;
};

watch(editModal, (open) => {
    if (open) loadEditSections();
});

const saveEdit = () => {
    axios.put(route('aca_school_enrollments_update', editForm.value.id), {
        section_id: editForm.value.section_id,
        type: editForm.value.type,
        guardian_relationship: editForm.value.guardian_relationship,
        guardian_phone: editForm.value.guardian_phone,
        observations: editForm.value.observations,
    }).then(() => {
        editModal.value = false;
        showMessage('Matrícula actualizada');
        router.visit(route('aca_school_enrollments_list'), { replace: true, preserveState: true, preserveScroll: true });
    }).catch((err) => {
        const errors = err.response?.data?.errors ?? {};
        showMessage(Object.values(errors)[0]?.[0] ?? 'Error al guardar', 'error');
    });
};
</script>

<template>
    <AppLayout title="Matrículas">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                { route: route('aca_school_years_list'), title: 'Colegio' },
                { title: 'Matrículas' },
            ]"
        />
        <div class="pt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl">Matrículas Escolares</h2>
                <Link :href="route('aca_school_enrollments_create')" class="btn btn-primary">+ Nueva Matrícula</Link>
            </div>

            <!-- Filtros -->
            <div class="mt-5 panel p-5">
                <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <div>
                        <label class="form-label">Año</label>
                        <select v-model="filters.year_id" class="form-select" @change="applyFilters">
                            <option value="">Todos</option>
                            <option v-for="y in years" :key="y.id" :value="y.id">{{ y.year }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Nivel</label>
                        <select v-model="filters.level_id" class="form-select" @change="filters.grade_id = ''; filters.section_id = ''; applyFilters()">
                            <option value="">Todos</option>
                            <option v-for="l in levels" :key="l.id" :value="l.id">{{ l.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Grado</label>
                        <select v-model="filters.grade_id" class="form-select" @change="filters.section_id = ''; applyFilters()">
                            <option value="">Todos</option>
                            <option v-for="g in grades" :key="g.id" :value="g.id" :disabled="filters.level_id && g.level_id !== Number(filters.level_id)">{{ g.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Sección</label>
                        <select v-model="filters.section_id" class="form-select" @change="applyFilters">
                            <option value="">Todas</option>
                            <option v-for="s in sections" :key="s.id" :value="s.id" :disabled="filters.grade_id && s.grade_id !== Number(filters.grade_id)">{{ s.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Estado</label>
                        <select v-model="filters.status" class="form-select" @change="applyFilters">
                            <option value="">Todos</option>
                            <option v-for="(label, key) in statusLabels" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Buscar</label>
                        <input v-model="filters.search" type="text" class="form-input" placeholder="Nombre o DNI" @keyup.enter="applyFilters" />
                    </div>
                </div>
            </div>

            <!-- Tabla -->
            <div class="mt-5 panel p-0 border-0 overflow-hidden">
                <div class="table-responsive">
                    <table class="table-striped table-hover">
                        <thead>
                            <tr>
                                <th class="!text-center">Acciones</th>
                                <th>Año</th>
                                <th>Alumno</th>
                                <th>Sección</th>
                                <th>Tipo</th>
                                <th>Fecha</th>
                                <th class="!text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!enrollments.data || enrollments.data.length === 0">
                                <td colspan="7" class="text-center text-white-dark py-6">Sin matrículas con los filtros actuales</td>
                            </tr>
                            <tr v-for="item in enrollments.data" :key="item.id">
                                <td>
                                    <div class="flex gap-1 items-center justify-center">
                                        <button v-if="item.status === 'activo'" v-tippy:bottom type="button" class="btn btn-sm btn-outline-warning" @click="changeStatus(item, 'retirado')">
                                            Retirar
                                        </button>
                                        <tippy target="bottom" placement="bottom">Retirar</tippy>
                                        <button v-if="item.status === 'activo'" v-tippy:bottom type="button" class="btn btn-sm btn-outline-secondary" @click="changeStatus(item, 'traslado_salida')">
                                            Traslado
                                        </button>
                                        <tippy target="bottom" placement="bottom">Traslado de salida</tippy>
                                        <button v-if="item.status !== 'anulado'" v-tippy:bottom type="button" class="btn btn-sm btn-outline-danger" @click="changeStatus(item, 'anulado')">
                                            Anular
                                        </button>
                                        <tippy target="bottom" placement="bottom">Anular</tippy>
                                        <button v-if="item.status !== 'activo' && item.status !== 'anulado'" v-tippy:bottom type="button" class="btn btn-sm btn-outline-success" @click="changeStatus(item, 'activo')">
                                            Reactivar
                                        </button>
                                        <tippy target="bottom" placement="bottom">Reactivar (ocupa vacante)</tippy>
                                        <button v-tippy:bottom type="button" class="btn btn-sm btn-outline-primary" @click="openEdit(item)">
                                            Editar
                                        </button>
                                        <tippy target="bottom" placement="bottom">Editar matrícula</tippy>
                                        <Link v-if="item.status === 'activo'" v-tippy:bottom :href="route('aca_school_charges_show', item.id)" type="button" class="btn btn-sm btn-outline-info">
                                            Cobrar
                                        </Link>
                                        <tippy target="bottom" placement="bottom">Cobrar matrícula/mensualidad</tippy>
                                    </div>
                                </td>
                                <td>{{ item.year_number }}</td>
                                <td>
                                    <div class="font-semibold">{{ item.student_name }}</div>
                                    <div class="text-xs text-white-dark">{{ item.student_document }} · {{ item.student_code }}</div>
                                </td>
                                <td class="whitespace-nowrap">
                                    {{ item.level_name }} · {{ item.grade_name }} · {{ item.section_name }}
                                </td>
                                <td class="text-center"><span class="badge bg-info">{{ typeLabels[item.type] ?? item.type }}</span></td>
                                <td>{{ item.enrollment_date }}</td>
                                <td class="text-center">
                                    <span class="badge" :class="statusBadges[item.status] ?? 'bg-secondary'">
                                        {{ statusLabels[item.status] ?? item.status }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="enrollments.links" class="p-4">
                    <Pagination :data="enrollments" />
                </div>
            </div>
        </div>

        <!-- Modal edición -->
        <div v-if="editModal" class="fixed inset-0 z-[999] bg-black/40 flex items-center justify-center p-4" @click.self="editModal = false">
            <div class="panel w-full max-w-lg p-6">
                <h3 class="text-lg font-semibold mb-1">Editar Matrícula</h3>
                <p class="text-sm text-white-dark mb-4">{{ editForm.student_name }} · Año {{ editForm.year_number }}</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Sección *</label>
                        <select v-model="editForm.section_id" class="form-select">
                            <option v-for="s in editSections" :key="s.id" :value="s.id">
                                {{ s.name }} ({{ s.shift_label }}) — {{ s.available }} vacante(s)
                            </option>
                        </select>
                        <p class="text-xs text-white-dark mt-1">Se valida la vacante al guardar.</p>
                    </div>
                    <div>
                        <label class="form-label">Tipo *</label>
                        <select v-model="editForm.type" class="form-select">
                            <option v-for="(label, key) in typeLabels" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Parentesco del apoderado</label>
                        <input v-model="editForm.guardian_relationship" type="text" class="form-input" placeholder="Madre, Padre, Apoderado..." />
                    </div>
                    <div>
                        <label class="form-label">Teléfono del apoderado</label>
                        <input v-model="editForm.guardian_phone" type="text" class="form-input" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="form-label">Observaciones</label>
                        <textarea v-model="editForm.observations" rows="2" class="form-textarea"></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-5">
                    <button type="button" class="btn btn-outline-danger" @click="editModal = false">Cancelar</button>
                    <button type="button" class="btn btn-primary" @click="saveEdit">Guardar</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
