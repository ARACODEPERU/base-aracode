<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import Swal2 from 'sweetalert2';
import { computed, ref } from 'vue';
import { faPencil, faTrash, faUserPlus, faUsers, faMoneyBillWave, faIdCard, faPrint } from '@fortawesome/free-solid-svg-icons';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

const permissions = computed(() => page.props.auth?.permissions || []);

const hasPermission = (permission) => {
    if (!permission) {
        return true;
    }

    return permissions.value.includes(permission);
};

const props = defineProps({
    students: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
    filterOptions: { type: Array, default: () => [] },
});

const form = useForm({
    search: props.filters.search ?? '',
    level_id: props.filters.level_id ?? '',
    grade_id: props.filters.grade_id ?? '',
    section_id: props.filters.section_id ?? '',
});

/* ---------- Filtros en cascada Nivel -> Grado -> Sección ---------- */
const levelOptions = computed(() => props.filterOptions ?? []);
const gradeOptions = computed(
    () => levelOptions.value.find((l) => String(l.id) === String(form.level_id))?.grades ?? []
);
const sectionOptions = computed(
    () => gradeOptions.value.find((g) => String(g.id) === String(form.grade_id))?.sections ?? []
);

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

const applyFilters = () => {
    selectedIds.value = [];
    form.get(route('aca_school_students_list'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const onLevelChange = () => {
    form.grade_id = '';
    form.section_id = '';
    applyFilters();
};

const onGradeChange = () => {
    form.section_id = '';
    applyFilters();
};

/* ---------- Selección para impresión masiva de carnés ---------- */
const selectedIds = ref([]);

const isSelected = (id) => selectedIds.value.includes(id);

const toggleRow = (item) => {
    if (!item.active_enrollment_id) {
        showMessage('El alumno no tiene matrícula activa con sección, no puede imprimirse su carné.', 'warning');
        return;
    }
    const i = selectedIds.value.indexOf(item.id);
    if (i >= 0) {
        selectedIds.value.splice(i, 1);
    } else {
        selectedIds.value.push(item.id);
    }
};

// Solo alumnos con matrícula activa son seleccionables en la página visible.
const selectableRows = computed(() => (props.students?.data ?? []).filter((s) => s.active_enrollment_id));

const pageSelected = computed(
    () => selectableRows.value.length > 0 && selectableRows.value.every((s) => isSelected(s.id))
);
const someSelected = computed(() => selectableRows.value.some((s) => isSelected(s.id)));

const togglePage = () => {
    if (pageSelected.value) {
        const pageIds = selectableRows.value.map((s) => s.id);
        selectedIds.value = selectedIds.value.filter((id) => !pageIds.includes(id));
    } else {
        selectableRows.value.forEach((s) => {
            if (!isSelected(s.id)) selectedIds.value.push(s.id);
        });
    }
};

// Selecciona todos los alumnos que coinciden con los filtros actuales (no solo la página visible).
const selectAllFiltered = async () => {
    try {
        const res = await axios.get(route('aca_school_students_bulk_ids'), {
            params: {
                search: form.search || undefined,
                level_id: form.level_id || undefined,
                grade_id: form.grade_id || undefined,
                section_id: form.section_id || undefined,
            },
        });
        const { ids, total } = res.data;
        if (!ids || ids.length === 0) {
            showMessage('No hay alumnos con matrícula activa en ese filtro.', 'warning');
            return;
        }
        if (total > 200) {
            showMessage(`Hay ${total} alumnos en el filtro; el máximo de selección es 200. Aplica un filtro más específico.`, 'error');
            return;
        }
        if (total > 100) {
            showMessage(`Se seleccionaron ${total}, pero se imprimen máximo 100 carnés por tanda. Desmarca los excedentes.`, 'warning');
        }
        selectedIds.value = ids;
    } catch {
        showMessage('No se pudo obtener la selección completa.', 'error');
    }
};

const printBulk = () => {
    if (selectedIds.value.length === 0) {
        return;
    }
    if (selectedIds.value.length > 100) {
        showMessage(`Seleccionaste ${selectedIds.value.length}; el máximo es 100 carnés por tanda.`, 'error');
        return;
    }
    window.open(route('aca_school_students_cards_bulk', { ids: selectedIds.value.join(',') }), '_blank');
};

const destroyStudent = (id) => {
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
        text: '¡No podrás revertir esto!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '¡Sí, eliminar!',
        cancelButtonText: 'Cancelar',
        showLoaderOnConfirm: true,
        reverseButtons: true,
        padding: '2em',
        customClass: 'sweet-alerts',
        preConfirm: () => {
            return axios.delete(route('aca_school_students_destroy', id)).then((res) => {
                if (!res.data.success) {
                    Swal2.showValidationMessage(res.data.message);
                }
                return res;
            });
        },
        allowOutsideClick: () => !Swal2.isLoading(),
    }).then((result) => {
        if (result.isConfirmed) {
            showMessage('Alumno eliminado correctamente.');
            selectedIds.value = selectedIds.value.filter((sid) => sid !== id);
            router.visit(route('aca_school_students_list'), {
                replace: true,
                method: 'get',
                preserveState: true,
                preserveScroll: true,
            });
        }
    });
};
</script>

<template>
    <AppLayout title="Alumnos Escolares">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                { route: route('aca_school_years_list'), title: 'Colegio' },
                { title: 'Alumnos' },
            ]"
        />
        <div class="pt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl">Alumnos Escolares</h2>
                <div class="flex sm:flex-row flex-col sm:items-center sm:gap-3 gap-4 w-full sm:w-auto">
                    <div class="flex gap-3">
                        <div>
                            <Link :href="route('aca_school_students_create')" type="button" class="btn btn-primary">
                                <font-awesome-icon :icon="faUserPlus" class="ltr:mr-2 rtl:ml-2" />
                                Nuevo
                            </Link>
                        </div>
                    </div>
                    <div class="relative">
                        <input
                            type="text"
                            placeholder="Buscar por nombre, DNI o código"
                            class="form-input py-2 ltr:pr-11 rtl:pl-11 peer"
                            v-model="form.search"
                            @keyup.enter="applyFilters"
                        />
                    </div>
                </div>
            </div>

            <!-- Filtros por estructura académica -->
            <div class="mt-4 panel p-4 flex flex-wrap items-end gap-3">
                <div>
                    <label class="form-label text-xs">Nivel</label>
                    <select v-model="form.level_id" @change="onLevelChange" class="form-select w-44 py-2">
                        <option value="">Todos</option>
                        <option v-for="level in levelOptions" :key="level.id" :value="level.id">{{ level.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="form-label text-xs">Grado</label>
                    <select v-model="form.grade_id" @change="onGradeChange" class="form-select w-44 py-2" :disabled="!form.level_id">
                        <option value="">Todos</option>
                        <option v-for="grade in gradeOptions" :key="grade.id" :value="grade.id">{{ grade.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="form-label text-xs">Sección</label>
                    <select v-model="form.section_id" @change="applyFilters" class="form-select w-44 py-2" :disabled="!form.grade_id">
                        <option value="">Todas</option>
                        <option v-for="section in sectionOptions" :key="section.id" :value="section.id">{{ section.name }}</option>
                    </select>
                </div>

                <!-- Acciones de impresión masiva -->
                <div class="flex items-center gap-2 ltr:ml-auto rtl:mr-auto">
                    <button type="button" class="btn btn-outline-secondary" @click="selectAllFiltered">
                        Seleccionar todo el filtro
                    </button>
                    <button
                        type="button"
                        class="btn btn-primary"
                        :disabled="selectedIds.length === 0"
                        @click="printBulk"
                    >
                        <font-awesome-icon :icon="faPrint" class="ltr:mr-2 rtl:ml-2" />
                        Imprimir carnés en masa ({{ selectedIds.length }})
                    </button>
                </div>
            </div>

            <div class="mt-5 panel p-0 border-0 overflow-hidden">
                <div class="table-responsive">
                    <table class="table-striped table-hover">
                        <thead>
                            <tr>
                                <th class="!text-center w-10">
                                    <input
                                        type="checkbox"
                                        class="form-checkbox"
                                        :checked="pageSelected"
                                        :indeterminate.prop="someSelected && !pageSelected"
                                        @change="togglePage"
                                    />
                                </th>
                                <th class="!text-center">Acciones</th>
                                <th>Código</th>
                                <th>DNI</th>
                                <th>Alumno</th>
                                <th>Teléfono</th>
                                <th>Email</th>
                                <th class="!text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!students.data || students.data.length === 0">
                                <td colspan="8" class="text-center text-white-dark py-6">Sin alumnos registrados</td>
                            </tr>
                            <tr v-for="item in students.data" :key="item.id">
                                <td class="text-center">
                                    <input
                                        type="checkbox"
                                        class="form-checkbox"
                                        :disabled="!item.active_enrollment_id"
                                        :checked="isSelected(item.id)"
                                        @change="toggleRow(item)"
                                    />
                                </td>
                                <td>
                                    <div class="flex gap-1 items-center justify-center">
                                        <Link v-tippy:bottom :href="route('aca_school_students_edit', item.id)" type="button" class="btn btn-sm btn-outline-primary">
                                            <font-awesome-icon :icon="faPencil" class="m-0" />
                                        </Link>
                                        <tippy target="bottom" placement="bottom">Editar</tippy>
                                        <Link v-if="hasPermission('aca_school_alumno_apoderados')" v-tippy:bottom :href="route('aca_school_students_edit', item.id)" type="button" class="btn btn-sm btn-outline-success">
                                            <font-awesome-icon :icon="faUsers" class="m-0" />
                                        </Link>
                                        <tippy target="bottom" placement="bottom">Apoderados</tippy>
                                        <Link v-if="item.active_enrollment_id" v-tippy:bottom :href="route('aca_school_charges_by_student', item.id)" type="button" class="btn btn-sm btn-outline-info">
                                            <font-awesome-icon :icon="faMoneyBillWave" class="m-0" />
                                        </Link>
                                        <tippy target="bottom" placement="bottom">Cobrar matrícula/mensualidad</tippy>
                                        <a v-if="item.active_enrollment_id" v-tippy:bottom :href="route('aca_school_students_card', item.id)" target="_blank" class="btn btn-sm btn-outline-secondary">
                                            <font-awesome-icon :icon="faIdCard" class="m-0" />
                                        </a>
                                        <tippy target="bottom" placement="bottom">Imprimir carné del alumno</tippy>
                                        <button v-tippy:bottom type="button" class="btn btn-sm btn-outline-danger" @click="destroyStudent(item.id)">
                                            <font-awesome-icon :icon="faTrash" />
                                        </button>
                                        <tippy target="bottom" placement="bottom">Eliminar</tippy>
                                    </div>
                                </td>
                                <td>{{ item.student_code }}</td>
                                <td>{{ item.person?.number }}</td>
                                <td>{{ item.person?.full_name }}</td>
                                <td>{{ item.person?.telephone }}</td>
                                <td>{{ item.person?.email }}</td>
                                <td class="text-center">
                                    <span v-if="item.status" class="badge bg-success">Activo</span>
                                    <span v-else class="badge bg-danger">Inactivo</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
