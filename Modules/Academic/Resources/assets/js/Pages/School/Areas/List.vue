<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import ModalMedium from '@/Components/ModalMedium.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import iconLoader from '@/Components/vristo/icon/icon-loader.vue';
import { ref, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import Swal2 from 'sweetalert2';
import { faPencil, faTrash, faPlus, faWandMagicSparkles } from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
    areas: { type: Array, default: () => [] },
    school: { type: Object, default: null },
    filters: { type: Object, default: () => ({}) },
});

const levelLabels = {
    inicial: 'Inicial',
    primaria: 'Primaria',
    secundaria: 'Secundaria',
};

const levelBadges = {
    inicial: 'bg-info',
    primaria: 'bg-warning',
    secundaria: 'bg-secondary',
};

const levelFilter = ref(props.filters?.level ?? '');

const filteredAreas = computed(() => {
    if (! levelFilter.value) {
        return props.areas;
    }

    return props.areas.filter((area) => area.level === levelFilter.value);
});

/* ------------- Modal área curricular ------------- */
const showModal = ref(false);
const editingId = ref(null);

const areaForm = useForm({
    id: null,
    name: '',
    level: 'primaria',
    sort_order: null,
    status: true,
});

const openCreate = () => {
    editingId.value = null;
    areaForm.reset();
    areaForm.clearErrors();
    areaForm.level = levelFilter.value || 'primaria';
    showModal.value = true;
};

const openEdit = (area) => {
    editingId.value = area.id;
    areaForm.clearErrors();
    areaForm.id = area.id;
    areaForm.name = area.name;
    areaForm.level = area.level;
    areaForm.sort_order = area.sort_order;
    areaForm.status = !! area.status;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    areaForm.reset();
    areaForm.clearErrors();
};

const saveArea = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            Swal2.fire({
                title: 'Enhorabuena',
                text: editingId.value
                    ? 'Área curricular actualizada correctamente'
                    : 'Área curricular registrada correctamente',
                icon: 'success',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        },
    };

    if (editingId.value) {
        areaForm.put(route('aca_school_areas_update'), options);
    } else {
        areaForm.post(route('aca_school_areas_store'), options);
    }
};

/* ------------- Eliminar ------------- */
const destroyArea = (area) => {
    Swal2.fire({
        title: '¿Eliminar área curricular?',
        text: `Se retirará "${area.name}" del catálogo. Las notas ya registradas conservan el nombre del área.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        padding: '2em',
        customClass: 'sweet-alerts',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('aca_school_areas_destroy', area.id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal2.fire({
                        title: 'Enhorabuena',
                        text: 'Área curricular eliminada',
                        icon: 'success',
                        padding: '2em',
                        customClass: 'sweet-alerts',
                    });
                },
            });
        }
    });
};

/* ------------- Precargar áreas estándar CNEB ------------- */
const loadStandard = () => {
    Swal2.fire({
        title: 'Cargar áreas estándar CNEB',
        text: 'Se agregarán las áreas oficiales del nivel elegido. Las que ya existan no se duplican.',
        input: 'select',
        inputOptions: { inicial: 'Inicial', primaria: 'Primaria', secundaria: 'Secundaria' },
        inputValue: levelFilter.value || 'primaria',
        inputPlaceholder: 'Seleccionar nivel',
        showCancelButton: true,
        confirmButtonText: 'Cargar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        padding: '2em',
        customClass: 'sweet-alerts',
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            router.post(route('aca_school_areas_seed_standard'), { level: result.value }, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal2.fire({
                        title: 'Enhorabuena',
                        text: 'Áreas estándar cargadas correctamente',
                        icon: 'success',
                        padding: '2em',
                        customClass: 'sweet-alerts',
                    });
                },
            });
        }
    });
};
</script>

<template>
    <AppLayout title="Áreas Curriculares">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                { route: route('aca_school_years_list'), title: 'Colegio' },
                { title: 'Áreas Curriculares' },
            ]"
        />
        <div class="pt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl">Áreas Curriculares <span class="text-white-dark text-base">( {{ school?.name }} )</span></h2>
            </div>

            <div class="mt-5 panel">
                <div class="p-5 flex flex-wrap items-end gap-4">
                    <div class="w-52">
                        <label class="form-label">Filtrar por nivel</label>
                        <select v-model="levelFilter" class="form-select">
                            <option value="">Todos</option>
                            <option v-for="(label, code) in levelLabels" :key="code" :value="code">{{ label }}</option>
                        </select>
                    </div>
                    <div class="flex gap-2 ml-auto">
                        <button type="button" class="btn btn-outline-secondary" @click="loadStandard">
                            <font-awesome-icon :icon="faWandMagicSparkles" class="w-4 h-4 mr-1" />
                            Cargar áreas CNEB
                        </button>
                        <button type="button" class="btn btn-primary" @click="openCreate">
                            <font-awesome-icon :icon="faPlus" class="w-4 h-4 mr-1" />
                            Nueva área
                        </button>
                    </div>
                </div>
            </div>

            <div class="mt-5 panel p-0 border-0 overflow-hidden">
                <div class="table-responsive">
                    <table class="table-striped table-hover">
                        <thead>
                            <tr>
                                <th class="!text-center">Acciones</th>
                                <th>Área curricular</th>
                                <th>Nivel</th>
                                <th class="!text-center">Orden</th>
                                <th class="!text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="filteredAreas.length === 0">
                                <td colspan="5" class="text-center text-white-dark py-6">
                                    Aún no hay áreas curriculares. Registre la primera o use "Cargar áreas CNEB" para precargar las oficiales del MINEDU.
                                </td>
                            </tr>
                            <tr v-for="area in filteredAreas" :key="area.id">
                                <td>
                                    <div class="flex gap-1 items-center justify-center">
                                        <button type="button" class="btn btn-sm btn-outline-info" @click="openEdit(area)">
                                            <font-awesome-icon :icon="faPencil" />
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" @click="destroyArea(area)">
                                            <font-awesome-icon :icon="faTrash" />
                                        </button>
                                    </div>
                                </td>
                                <td class="font-semibold">{{ area.name }}</td>
                                <td>
                                    <span class="badge" :class="levelBadges[area.level]">{{ levelLabels[area.level] ?? area.level }}</span>
                                </td>
                                <td class="text-center">{{ area.sort_order }}</td>
                                <td class="text-center">
                                    <span class="badge" :class="area.status ? 'bg-success' : 'bg-secondary'">
                                        {{ area.status ? 'Activa' : 'Inactiva' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <ModalMedium :show="showModal" :onClose="closeModal" :icon="'/img/aula.png'">
            <template #title>
                {{ editingId ? 'Editar área curricular' : 'Nueva área curricular' }}
            </template>
            <template #message>
                Las áreas activas aparecen como opciones en el Registro de Notas del docente, según el nivel de la sección.
            </template>
            <template #content>
                <div class="space-y-3">
                    <div>
                        <label class="form-label">Nombre del área *</label>
                        <input v-model="areaForm.name" type="text" maxlength="120" class="form-input" placeholder="Ej. Matemática" />
                        <template v-if="areaForm.errors.name">
                            <p class="text-danger text-xs mt-1">{{ areaForm.errors.name }}</p>
                        </template>
                    </div>
                    <div>
                        <label class="form-label">Nivel *</label>
                        <select v-model="areaForm.level" class="form-select">
                            <option v-for="(label, code) in levelLabels" :key="code" :value="code">{{ label }}</option>
                        </select>
                        <template v-if="areaForm.errors.level">
                            <p class="text-danger text-xs mt-1">{{ areaForm.errors.level }}</p>
                        </template>
                    </div>
                    <div>
                        <label class="form-label">Orden de visualización</label>
                        <input v-model="areaForm.sort_order" type="number" min="0" class="form-input" placeholder="Automático" />
                        <template v-if="areaForm.errors.sort_order">
                            <p class="text-danger text-xs mt-1">{{ areaForm.errors.sort_order }}</p>
                        </template>
                    </div>
                    <div class="flex items-center">
                        <input id="area_status" v-model="areaForm.status" type="checkbox" class="form-checkbox" />
                        <label for="area_status" class="ml-2 text-sm">Activa</label>
                    </div>
                </div>
            </template>
            <template #buttons>
                <PrimaryButton type="button" @click="saveArea" :disabled="areaForm.processing">
                    <icon-loader v-if="areaForm.processing" class="w-4 h-4 mr-1" />
                    Guardar
                </PrimaryButton>
            </template>
        </ModalMedium>
    </AppLayout>
</template>
