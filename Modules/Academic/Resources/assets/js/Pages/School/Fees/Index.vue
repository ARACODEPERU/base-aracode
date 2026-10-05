<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { ref, reactive, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import Swal2 from 'sweetalert2';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ModalMedium from '@/Components/ModalMedium.vue';
import iconLoader from '@/Components/vristo/icon/icon-loader.vue';
import { faPencil, faTrash } from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
    schoolName: { type: String, default: '' },
    years: { type: Array, default: () => [] },
    levels: { type: Array, default: () => [] },
    grades: { type: Array, default: () => [] },
    sections: { type: Array, default: () => [] },
    feeTypes: { type: Array, default: () => [] },
    fees: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const permissions = computed(() => page.props.auth?.permissions || []);
const hasPermission = (permission) => ! permission || permissions.value.includes(permission);

const yearFilter = ref(props.filters.year_id);

const changeYearFilter = () => {
    router.get(route('aca_school_fees_list'), { year_id: yearFilter.value }, {
        preserveState: true,
        preserveScroll: true,
    });
};

/* ------------- Modal tarifa ------------- */
const showFeeModal = ref(false);
const savingFee = ref(false);
const editingFeeId = ref(null);

const emptyForm = () => ({
    fee_type_id: '',
    level_id: '',
    grade_id: '',
    section_id: '',
    amount: '',
    status: true,
});

const feeForm = reactive(emptyForm());
const modalGrades = ref([...props.grades]);
const modalSections = ref([...props.sections]);

const openCreate = () => {
    editingFeeId.value = null;
    Object.assign(feeForm, emptyForm());
    feeForm.year_id = yearFilter.value;
    modalGrades.value = [...props.grades];
    modalSections.value = [];
    showFeeModal.value = true;
};

const openEdit = (fee) => {
    editingFeeId.value = fee.id;
    Object.assign(feeForm, {
        fee_type_id: fee.fee_type_id,
        level_id: fee.level_id ?? '',
        grade_id: fee.grade_id ?? '',
        section_id: fee.section_id ?? '',
        amount: fee.amount,
        status: !!fee.status,
    });
    modalGrades.value = fee.level_id ? props.grades.filter((g) => g.level_id === fee.level_id) : [...props.grades];
    modalSections.value = fee.grade_id ? props.sections.filter((s) => s.grade_id === fee.grade_id) : [];
    showFeeModal.value = true;
};

const closeFeeModal = () => {
    showFeeModal.value = false;
};

const onLevelChange = async () => {
    feeForm.grade_id = '';
    feeForm.section_id = '';
    if (! feeForm.level_id) {
        modalGrades.value = [...props.grades];
        modalSections.value = [];
        return;
    }
    const res = await axios.post(route('aca_school_fees_grades'), { level_id: feeForm.level_id });
    modalGrades.value = res.data;
};

const onGradeChange = async () => {
    feeForm.section_id = '';
    if (! feeForm.grade_id) {
        modalSections.value = [];
        return;
    }
    const res = await axios.post(route('aca_school_fees_sections'), { grade_id: feeForm.grade_id });
    modalSections.value = res.data;
};

const saveFee = () => {
    savingFee.value = true;
    const url = editingFeeId.value
        ? route('aca_school_fees_update', editingFeeId.value)
        : route('aca_school_fees_store');

    axios.post(url, {
        year_id: yearFilter.value,
        ...feeForm,
        level_id: feeForm.level_id || null,
        grade_id: feeForm.grade_id || null,
        section_id: feeForm.section_id || null,
    }).then((res) => {
        Swal2.fire({
            title: 'Enhorabuena',
            text: res.data.message ?? 'Tarifa guardada correctamente',
            icon: 'success',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
        showFeeModal.value = false;
        router.reload({ only: ['fees'], preserveScroll: true });
    }).catch((error) => {
        Swal2.fire({
            icon: 'error',
            text: error.response?.data?.message ?? 'No se pudo guardar la tarifa',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    }).finally(() => {
        savingFee.value = false;
    });
};

const deleteFee = (fee) => {
    Swal2.fire({
        title: '¿Eliminar tarifa?',
        text: 'Se eliminará la tarifa seleccionada.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        padding: '2em',
        customClass: 'sweet-alerts',
    }).then((result) => {
        if (result.isConfirmed) {
            axios.delete(route('aca_school_fees_destroy', fee.id)).then(() => {
                router.reload({ only: ['fees'], preserveScroll: true });
            });
        }
    });
};

const scopeLabel = (fee) => {
    if (fee.section) return `Sección ${fee.section.name}`;
    if (fee.grade) return `Grado ${fee.grade.name}`;
    if (fee.level) return `Nivel ${fee.level.name}`;
    return 'General (todo el colegio)';
};
</script>

<template>
    <AppLayout title="Tarifas del Colegio">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                { route: route('aca_school_years_list'), title: 'Colegio' },
                { title: 'Tarifas' },
            ]"
        />
        <div class="pt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl">Tarifas de Cobros - {{ schoolName }}</h2>
                <div class="flex sm:flex-row flex-col sm:items-center sm:gap-3 gap-4">
                    <select v-model="yearFilter" @change="changeYearFilter" class="form-select w-40">
                        <option v-for="y in years" :key="y.id" :value="y.id">
                            {{ y.year }} {{ y.status === 'active' ? '(activo)' : '' }}
                        </option>
                    </select>
                    <PrimaryButton type="button" @click="openCreate">+ Nueva tarifa</PrimaryButton>
                </div>
            </div>

            <div class="mt-5 panel p-0 border-0 overflow-hidden">
                <div class="table-responsive">
                    <table class="table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Concepto</th>
                                <th>Alcance</th>
                                <th>Monto</th>
                                <th class="!text-center">Estado</th>
                                <th class="!text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="fees.length === 0">
                                <td colspan="5" class="text-center text-white-dark py-6">
                                    Sin tarifas configuradas para este año. Agrega la primera con el botón "Nueva tarifa".
                                </td>
                            </tr>
                            <tr v-for="fee in fees" :key="fee.id">
                                <td>
                                    <div class="font-semibold">{{ fee.fee_type?.name }}</div>
                                    <div class="text-xs text-white-dark" v-if="fee.fee_type?.is_recurring">Concepto mensual (cronograma)</div>
                                </td>
                                <td>{{ scopeLabel(fee) }}</td>
                                <td class="font-semibold">S/ {{ fee.amount }}</td>
                                <td class="text-center">
                                    <span v-if="fee.status" class="badge bg-success">Activa</span>
                                    <span v-else class="badge bg-danger">Inactiva</span>
                                </td>
                                <td class="text-center whitespace-nowrap">
                                    <button v-if="hasPermission('aca_school_tarifa_editar')" v-tippy:bottom type="button" class="btn btn-sm btn-outline-primary" @click="openEdit(fee)">
                                        <font-awesome-icon :icon="faPencil" class="m-0" />
                                    </button>
                                    <tippy target="bottom" placement="bottom">Editar</tippy>
                                    <button v-if="hasPermission('aca_school_tarifa_eliminar')" v-tippy:bottom type="button" class="btn btn-sm btn-outline-danger" @click="deleteFee(fee)">
                                        <font-awesome-icon :icon="faTrash" class="m-0" />
                                    </button>
                                    <tippy target="bottom" placement="bottom">Eliminar</tippy>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <ModalMedium :show="showFeeModal" :onClose="closeFeeModal" :icon="'/img/aula.png'">
            <template #title>
                {{ editingFeeId ? 'Editar tarifa' : 'Nueva tarifa' }}
            </template>
            <template #message>
                Monto por concepto. Deja nivel/grado/sección en "Todos" para aplicar al colegio completo.
            </template>
            <template #content>
                <div class="space-y-3">
                    <div>
                        <label class="form-label">Concepto *</label>
                        <select v-model="feeForm.fee_type_id" class="form-select">
                            <option value="">Seleccionar</option>
                            <option v-for="t in feeTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Nivel</label>
                        <select v-model="feeForm.level_id" @change="onLevelChange" class="form-select">
                            <option value="">Todos</option>
                            <option v-for="l in levels" :key="l.id" :value="l.id">{{ l.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Grado</label>
                        <select v-model="feeForm.grade_id" @change="onGradeChange" class="form-select" :disabled="! feeForm.level_id">
                            <option value="">Todos</option>
                            <option v-for="g in modalGrades" :key="g.id" :value="g.id">{{ g.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Sección</label>
                        <select v-model="feeForm.section_id" class="form-select" :disabled="! feeForm.grade_id">
                            <option value="">Todas</option>
                            <option v-for="s in modalSections" :key="s.id" :value="s.id">Sección {{ s.name }} ({{ s.shift }})</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Monto (S/) *</label>
                        <input v-model="feeForm.amount" type="number" step="0.01" min="0" class="form-input" />
                    </div>
                    <div class="flex items-center">
                        <input id="fee_status" v-model="feeForm.status" type="checkbox" class="form-checkbox" />
                        <label for="fee_status" class="ml-2 text-sm">Activa</label>
                    </div>
                </div>
            </template>
            <template #buttons>
                <PrimaryButton type="button" @click="saveFee" :disabled="savingFee">
                    <icon-loader v-if="savingFee" class="w-4 h-4 mr-1" />
                    Guardar
                </PrimaryButton>
            </template>
        </ModalMedium>
    </AppLayout>
</template>
