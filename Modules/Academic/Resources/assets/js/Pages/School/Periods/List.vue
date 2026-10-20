<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { useForm, router } from '@inertiajs/vue3';
import Swal2 from 'sweetalert2';
import { ref, computed } from 'vue';
import { faPlus, faPencil, faTrash, faCalendarWeek, faWandMagicSparkles } from '@fortawesome/free-solid-svg-icons';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';
import ModalSmall from '@/Components/ModalSmall.vue';
import ModalLarge from '@/Components/ModalLarge.vue';

const props = defineProps({
    periods: { type: Object, default: () => ({}) },
    school: { type: Object, default: null },
    activeYear: { type: Object, default: null },
    filters: { type: Object, default: () => ({}) },
});

const createModal = ref(false);
const editModal = ref(false);
const saving = ref(false);
const preview = ref([]);

const typeOptions = [
    { value: 'bimestral', label: 'Bimestral', defaultCount: 6, prefix: 'Bimestre' },
    { value: 'trimestral', label: 'Trimestral', defaultCount: 4, prefix: 'Trimestre' },
    { value: 'cuatrimestral', label: 'Cuatrimestral', defaultCount: 3, prefix: 'Cuatrimestre' },
    { value: 'semestral', label: 'Semestral', defaultCount: 2, prefix: 'Semestre' },
    { value: 'personalizada', label: 'Personalizada', defaultCount: 1, prefix: 'Periodo' },
];

const createForm = useForm({
    year_id: null,
    organization_type: 'bimestral',
    count: 4,
    cycle_start: '',
    cycle_end: '',
    periods: [],
});

const editForm = useForm({
    id: null,
    name: '',
    start_date: '',
    end_date: '',
    observations: null,
});

const selectedType = computed(() => typeOptions.find((t) => t.value === createForm.organization_type));

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

const formatDate = (date) => {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
};

const onTypeChange = () => {
    createForm.count = selectedType.value?.defaultCount ?? 1;
    preview.value = [];
};

const generatePreview = () => {
    if (!createForm.cycle_start || !createForm.cycle_end || createForm.count < 1) {
        showMessage('Complete cantidad y fechas del ciclo para generar la vista previa', 'warning');
        return;
    }
    const start = new Date(createForm.cycle_start + 'T00:00:00');
    const end = new Date(createForm.cycle_end + 'T00:00:00');
    if (end < start) {
        showMessage('La fecha de fin del ciclo debe ser mayor o igual a la de inicio', 'warning');
        return;
    }
    const totalDays = Math.round((end - start) / (1000 * 60 * 60 * 24)) + 1;
    const segmentDays = Math.floor(totalDays / createForm.count);
    const prefix = selectedType.value?.prefix ?? 'Periodo';

    const rows = [];
    let currentStart = new Date(start);
    for (let i = 0; i < createForm.count; i++) {
        let currentEnd;
        if (i === createForm.count - 1) {
            currentEnd = new Date(end);
        } else {
            currentEnd = new Date(currentStart);
            currentEnd.setDate(currentEnd.getDate() + segmentDays - 1);
        }
        rows.push({
            name: `${prefix} ${i + 1}`,
            number: i + 1,
            start_date: formatDate(currentStart),
            end_date: formatDate(currentEnd),
        });
        currentStart = new Date(currentEnd);
        currentStart.setDate(currentStart.getDate() + 1);
    }
    preview.value = rows;
};

const openCreate = () => {
    createForm.reset();
    createForm.clearErrors();
    createForm.year_id = props.activeYear?.id ?? null;
    createForm.organization_type = 'bimestral';
    createForm.count = 4;
    createForm.cycle_start = '';
    createForm.cycle_end = '';
    preview.value = [];
    createModal.value = true;
};

const submitCreate = () => {
    if (preview.value.length === 0) {
        showMessage('Primero genere la vista previa de los períodos', 'warning');
        return;
    }
    createForm.periods = preview.value;
    saving.value = true;
    createForm.post(route('aca_school_periods_store'), {
        preserveScroll: true,
        onSuccess: () => {
            createModal.value = false;
            Swal2.fire({
                title: 'Enhorabuena',
                text: 'Períodos académicos registrados correctamente',
                icon: 'success',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        },
        onError: (errors) => {
            const first = Object.values(errors)[0];
            showMessage(first ?? 'Error al registrar los períodos', 'error');
        },
        onFinish: () => {
            saving.value = false;
        },
    });
};

const openEdit = (period) => {
    editForm.id = period.id;
    editForm.name = period.name;
    editForm.start_date = period.start_date?.slice(0, 10) ?? '';
    editForm.end_date = period.end_date?.slice(0, 10) ?? '';
    editForm.observations = period.observations;
    editForm.clearErrors();
    editModal.value = true;
};

const submitEdit = () => {
    saving.value = true;
    editForm.put(route('aca_school_periods_update', editForm.id), {
        preserveScroll: true,
        onSuccess: () => {
            editModal.value = false;
            showMessage('Período actualizado correctamente');
        },
        onError: (errors) => {
            const first = Object.values(errors)[0];
            showMessage(first ?? 'Error al actualizar el período', 'error');
        },
        onFinish: () => {
            saving.value = false;
        },
    });
};

const deletePeriod = (period) => {
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
        text: `Se eliminará el período "${period.name}". No debe tener registros asociados.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '¡Sí, eliminar!',
        cancelButtonText: 'Cancelar',
        showLoaderOnConfirm: true,
        reverseButtons: true,
        padding: '2em',
        customClass: 'sweet-alerts',
        preConfirm: () => {
            return axios.delete(route('aca_school_periods_destroy', period.id)).then((res) => {
                if (!res.data.success) {
                    Swal2.showValidationMessage(res.data.message);
                }
                return res;
            });
        },
        allowOutsideClick: () => !Swal2.isLoading(),
    }).then((result) => {
        if (result.isConfirmed) {
            showMessage('Período eliminado correctamente.');
            router.visit(route('aca_school_periods_list'), { replace: true, preserveState: true, preserveScroll: true });
        }
    });
};

const typeLabel = (v) => typeOptions.find((t) => t.value === v)?.label ?? v;

const closeCreateModal = () => { createModal.value = false; };
const closeEditModal = () => { editModal.value = false; };
</script>

<template>
    <AppLayout title="Períodos Académicos">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                { route: route('aca_school_years_list'), title: 'Colegio' },
                { title: 'Períodos Académicos' },
            ]"
        />
        <div class="pt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl">
                    Períodos Académicos
                    <span class="text-white-dark text-base">( {{ activeYear?.year ?? '—' }} )</span>
                </h2>
                <button v-if="activeYear" type="button" class="btn btn-primary" @click="openCreate">
                    <font-awesome-icon :icon="faPlus" class="ltr:mr-2 rtl:ml-2" /> Nuevo Período
                </button>
            </div>

            <div v-if="!activeYear" class="mt-5 panel p-8 text-center text-white-dark">
                Active un año escolar primero para gestionar los períodos académicos.
                <br />
                <a :href="route('aca_school_years_list')" class="btn btn-primary mt-4">Ir a Años Escolares</a>
            </div>

            <template v-else>
                <div class="mt-5 panel p-0 border-0 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table-striped table-hover">
                            <thead>
                                <tr>
                                    <th class="!text-center">Acciones</th>
                                    <th>Nombre</th>
                                    <th class="!text-center">N°</th>
                                    <th>Tipo</th>
                                    <th>Inicio</th>
                                    <th>Fin</th>
                                    <th>Observaciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="!periods.data || periods.data.length === 0">
                                    <td colspan="7" class="text-center text-white-dark py-6">
                                        Aún no hay períodos registrados para el año {{ activeYear?.year }}.
                                        Haga clic en <b>Nuevo Período</b> para generarlos.
                                    </td>
                                </tr>
                                <tr v-for="item in periods.data" :key="item.id">
                                    <td>
                                        <div class="flex gap-1 items-center justify-center">
                                            <button
                                                v-tippy:bottom type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                @click="openEdit(item)"
                                            >
                                                <font-awesome-icon :icon="faPencil" />
                                            </button>
                                            <tippy target="bottom" placement="bottom">Editar</tippy>
                                            <button
                                                v-tippy:bottom type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                @click="deletePeriod(item)"
                                            >
                                                <font-awesome-icon :icon="faTrash" />
                                            </button>
                                            <tippy target="bottom" placement="bottom">Eliminar</tippy>
                                        </div>
                                    </td>
                                    <td class="font-semibold">{{ item.name }}</td>
                                    <td class="text-center">{{ item.number }}</td>
                                    <td>
                                        <span class="badge bg-primary">{{ typeLabel(item.organization_type) }}</span>
                                    </td>
                                    <td>{{ item.start_date?.slice(0, 10) }}</td>
                                    <td>{{ item.end_date?.slice(0, 10) }}</td>
                                    <td>{{ item.observations }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-if="periods.data && periods.data.length > 0" class="p-4 border-t border-[#ebedf2] dark:border-[#191e3a]">
                        <ul class="flex items-center justify-center gap-2 flex-wrap">
                            <li v-for="(link, i) in periods.links" :key="i">
                                <a
                                    v-if="link.url"
                                    :href="link.url"
                                    class="btn btn-sm"
                                    :class="link.active ? 'btn-primary' : 'btn-outline-primary'"
                                    v-html="link.label"
                                ></a>
                            </li>
                        </ul>
                    </div>
                </div>
            </template>
        </div>

        <!-- Modal Generación múltiple -->
        <ModalLarge :onClose="closeCreateModal" :show="createModal" :icon="'/img/aula.png'">
            <template #title>
                Nuevo Período Académico
            </template>
            <template #content>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Tipo de organización *</label>
                        <select v-model="createForm.organization_type" class="form-select" @change="onTypeChange">
                            <option v-for="t in typeOptions" :key="t.value" :value="t.value">{{ t.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Cantidad de períodos *</label>
                        <input v-model.number="createForm.count" type="number" min="1" max="20" class="form-input" />
                    </div>
                    <div>
                        <label class="form-label">Inicio del ciclo *</label>
                        <input v-model="createForm.cycle_start" type="date" class="form-input" />
                    </div>
                    <div>
                        <label class="form-label">Fin del ciclo *</label>
                        <input v-model="createForm.cycle_end" type="date" class="form-input" />
                    </div>
                </div>
                <div class="mt-4">
                    <button type="button" class="btn btn-outline-primary btn-sm" @click="generatePreview">
                        <font-awesome-icon :icon="faWandMagicSparkles" class="ltr:mr-2 rtl:ml-2" />
                        Generar vista previa
                    </button>
                </div>

                <div v-if="preview.length > 0" class="mt-4 border border-[#ebedf2] dark:border-[#191e3a] rounded-md p-3">
                    <h4 class="font-semibold mb-3">Períodos generados ({{ preview.length }})</h4>
                    <div class="table-responsive">
                        <table class="table-sm">
                            <thead>
                                <tr>
                                    <th>N°</th>
                                    <th>Nombre</th>
                                    <th>Inicio</th>
                                    <th>Fin</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(row, idx) in preview" :key="idx">
                                    <td class="text-center">{{ row.number }}</td>
                                    <td><input v-model="row.name" type="text" class="form-input form-input-sm" maxlength="100" /></td>
                                    <td><input v-model="row.start_date" type="date" class="form-input form-input-sm" /></td>
                                    <td><input v-model="row.end_date" type="date" class="form-input form-input-sm" /></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="text-xs text-white-dark mt-2">Puede editar el nombre y las fechas de cada período antes de registrar.</p>
                </div>

                <template v-if="createForm.errors.periods">
                    <p class="text-danger text-xs mt-2">{{ createForm.errors.periods }}</p>
                </template>
                <template v-if="createForm.errors.year_id">
                    <p class="text-danger text-xs mt-2">{{ createForm.errors.year_id }}</p>
                </template>
            </template>
            <template #buttons>
                <button type="button" class="btn btn-primary" @click="submitCreate" :disabled="saving || preview.length === 0">
                    <IconLoader v-if="saving" class="w-4 h-4 ltr:mr-2 rtl:ml-2" />
                    Registrar períodos
                </button>
            </template>
        </ModalLarge>

        <!-- Modal Editar -->
        <ModalSmall :onClose="closeEditModal" :show="editModal" :icon="'/img/aula.png'">
            <template #title>
                Editar Período
            </template>
            <template #content>
                <label class="form-label">Nombre *</label>
                <input v-model="editForm.name" type="text" class="form-input" maxlength="100" placeholder="Ej: Bimestre 1" />
                <template v-if="editForm.errors.name">
                    <p class="text-danger text-xs mt-1">{{ editForm.errors.name }}</p>
                </template>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-3">
                    <div>
                        <label class="form-label">Inicio *</label>
                        <input v-model="editForm.start_date" type="date" class="form-input" />
                    </div>
                    <div>
                        <label class="form-label">Fin *</label>
                        <input v-model="editForm.end_date" type="date" class="form-input" />
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label">Observaciones</label>
                    <input v-model="editForm.observations" type="text" class="form-input" maxlength="1000" placeholder="Opcional" />
                </div>
            </template>
            <template #buttons>
                <button type="button" class="btn btn-primary" @click="submitEdit" :disabled="saving">
                    <IconLoader v-if="saving" class="w-4 h-4 ltr:mr-2 rtl:ml-2" /> Guardar
                </button>
            </template>
        </ModalSmall>
    </AppLayout>
</template>
