<script setup>
import { computed, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import ModalLarge from '@/Components/ModalLarge.vue';
import InputError from '@/Components/InputError.vue';
import Swal2 from 'sweetalert2';

/**
 * Mantenedor de Categorias / Tipo / Sector / Modalidad de cursos.
 *
 * Cada pestaña lista sus opciones con el numero de cursos que las usan. Al
 * eliminar una opcion en uso, el backend redirige de vuelta con el prop
 * `inUse` y este aviso permite reasignarles otra opcion a los cursos desde el
 * mismo modal o abrirlos en el editor completo.
 */
const props = defineProps({
    categories: { type: Array, default: () => [] },
    types: { type: Array, default: () => [] },
    sectors: { type: Array, default: () => [] },
    modalities: { type: Array, default: () => [] },
    inUse: { type: Object, default: null },
});

const page = usePage();

const notify = (title, text, icon) => {
    Swal2.fire({ title, text, icon, padding: '2em', customClass: 'sweet-alerts' });
};

// Mensajes flash de las acciones (exitos y errores de validacion suave).
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.message) {
            notify('Enhorabuena', flash.message, 'success');
        }
        if (flash?.error) {
            notify('Aviso', flash.error, 'warning');
        }
    },
    { immediate: true }
);

const activeTab = ref('categories');

const tabs = [
    { key: 'categories', label: 'Categorías' },
    { key: 'types', label: 'Tipos' },
    { key: 'sectors', label: 'Sectores' },
    { key: 'modalities', label: 'Modalidades' },
];

/** Configuracion por pestaña: tipo de opcion, campo de curso, textos y rutas. */
const tabConfig = {
    categories: {
        kind: 'category',
        field: 'category_id',
        label: 'Categoría',
        hasId: true,
        values: () => props.categories,
    },
    types: {
        kind: 'type',
        field: 'type_description',
        label: 'Tipo',
        hasId: false,
        values: () => props.types,
    },
    sectors: {
        kind: 'sector',
        field: 'sector_description',
        label: 'Sector',
        hasId: false,
        values: () => props.sectors,
    },
    modalities: {
        kind: 'modality',
        field: 'modality_id',
        label: 'Modalidad',
        hasId: true,
        values: () => props.modalities,
    },
};

const currentConfig = computed(() => tabConfig[activeTab.value]);
const currentValues = computed(() => currentConfig.value.values());

// ---------------------------------------------------------------- formularios
const newValue = ref('');
const newValueError = ref('');
const saving = ref(false);

const editing = ref(null); // opcion en edicion inline
const editingValue = ref('');
const editingError = ref('');

const addOption = () => {
    newValueError.value = '';

    const description = newValue.value.trim();

    if (description === '') {
        newValueError.value = 'La descripción es obligatoria.';
        return;
    }

    if (currentValues.value.some((option) => option.description.toLowerCase() === description.toLowerCase())) {
        newValueError.value = 'Esa opción ya existe.';
        return;
    }

    saving.value = true;

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            newValue.value = '';
        },
        onFinish: () => {
            saving.value = false;
        },
        onError: (errors) => {
            newValueError.value = errors?.description ?? 'No se pudo guardar la opción.';
        },
    };

    if (currentConfig.value.hasId) {
        router.post(route(`aca_course_options_${currentConfig.value.kind}_store`), { description }, options);
    } else {
        router.post(route('aca_course_options_enum_store'), { field: currentConfig.value.field, description }, options);
    }
};

const startEdit = (option) => {
    editing.value = option;
    editingValue.value = option.description;
    editingError.value = '';
};

const cancelEdit = () => {
    editing.value = null;
    editingValue.value = '';
    editingError.value = '';
};

const updateOption = () => {
    editingError.value = '';

    const description = editingValue.value.trim();

    if (description === '') {
        editingError.value = 'La descripción es obligatoria.';
        return;
    }

    if (
        currentValues.value.some(
            (option) => option !== editing.value && option.description.toLowerCase() === description.toLowerCase()
        )
    ) {
        editingError.value = 'Esa opción ya existe.';
        return;
    }

    saving.value = true;

    const options = {
        preserveScroll: true,
        onFinish: () => {
            saving.value = false;
            cancelEdit();
        },
        onError: (errors) => {
            editingError.value = errors?.description ?? 'No se pudo actualizar la opción.';
        },
    };

    if (currentConfig.value.hasId) {
        router.put(route(`aca_course_options_${currentConfig.value.kind}_update`, editing.value.id), { description }, options);
    } else {
        // Renombrar un valor ENUM mueve a los cursos que lo usaban hacia el
        // nuevo valor (transaccion en el controlador).
        router.put(
            route('aca_course_options_enum_update'),
            { field: currentConfig.value.field, value: editing.value.description, description },
            options
        );
    }
};

// ------------------------------------------------------------------ eliminar
const confirming = ref(null); // opcion sin uso pendiente de confirmacion
const confirmText = ref('');

const askDelete = (option) => {
    if (option.use_count > 0) {
        // El conteo local ya la marca en uso: un DELETE basta, el backend
        // redirige con el prop inUse y el aviso se abre con los cursos.
        deleteOption(option);

        return;
    }

    confirming.value = option;
    confirmText.value = '';
};

const confirmTextMatches = computed(
    () => confirming.value !== null && confirmText.value.trim().toLowerCase() === confirming.value.description.toLowerCase()
);

const deleteOption = (option) => {
    saving.value = true;
    const config = currentConfig.value;

    const options = {
        preserveScroll: true,
        onFinish: () => {
            saving.value = false;
            confirming.value = null;
            confirmText.value = '';
        },
        onError: (errors) => {
            notify('Aviso', errors?.value ?? 'No se pudo eliminar la opción.', 'warning');
        },
    };

    if (config.hasId) {
        router.delete(route(`aca_course_options_${config.kind}_destroy`, option.id), options);
    } else {
        router.delete(route('aca_course_options_enum_destroy'), {
            data: { field: config.field, value: option.description },
            ...options,
        });
    }
};

// Si el backend redirige con in_use (opcion en uso), el prop llega y el aviso
// se abre solo; aqui se sincroniza la pestaña activa con la opcion afectada.
const tabKeyByKind = {
    category: 'categories',
    type: 'types',
    sector: 'sectors',
    modality: 'modalities',
};

watch(
    () => props.inUse,
    (payload) => {
        if (payload) {
            activeTab.value = tabKeyByKind[payload.kind] ?? activeTab.value;
            assignSelections.value = {};
        }
    },
    { immediate: true }
);

// ---------------------------------------------------------------- reasignar
const assignSelections = ref({});
const assignSavingId = ref(false);

const inUseOptions = computed(() => {
    if (!props.inUse) {
        return [];
    }

    if (props.inUse.kind === 'category') {
        return props.categories;
    }
    if (props.inUse.kind === 'modality') {
        return props.modalities;
    }
    if (props.inUse.kind === 'type') {
        return props.types;
    }

    return props.sectors;
});

const alternativesFor = (course) => {
    return inUseOptions.value
        .filter((option) => String(option.id ?? option.description) !== String(course.current_value))
        .map((option) => ({ value: option.id ?? option.description, label: option.description }));
};

const assignToCourse = (course) => {
    const value = assignSelections.value[course.id];

    if (value === undefined || value === null || value === '') {
        notify('Aviso', 'Elige la opción nueva para el curso.', 'warning');
        return;
    }

    assignSavingId.value = true;

    // La respuesta re-renderiza el mantenedor conservando los parametros del
    // aviso, asi que la lista de cursos queda actualizada sin trabajo extra.
    router.put(
        route('aca_course_options_assign'),
        { course_id: course.id, field: props.inUse.field, value },
        {
            preserveScroll: true,
            onFinish: () => {
                assignSavingId.value = false;
                assignSelections.value = { ...assignSelections.value, [course.id]: undefined };
            },
        }
    );
};

const openCourse = (course) => {
    router.visit(course.edit_url);
};

const closeInUse = () => {
    // Quitar los parametros del aviso recarga el mantenedor limpio.
    router.get(route('aca_course_options'), {}, { preserveScroll: true, preserveState: true });
};

const inUseTabLabel = computed(() => {
    if (!props.inUse) {
        return '';
    }

    return tabConfig[tabKeyByKind[props.inUse.kind]]?.label ?? props.inUse.section;
});
</script>

<template>
    <AppLayout title="Categorías/Tipo/Sector">
        <Navigation
            :routeModule="route('aca_dashboard')"
            :titleModule="'Académico'"
            :data="[{ title: 'Categorías/Tipo/Sector' }]"
        />

        <div class="pt-5">
            <div class="panel">
                <div class="mb-4">
                    <h1 class="text-xl font-semibold">Categorías / Tipo / Sector / Modalidades</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Opciones que aparecen en los selects del formulario de cursos. No se puede eliminar una opción
                        que algún curso esté usando.
                    </p>
                </div>

                <!-- Pestañas -->
                <div class="mb-4 flex flex-wrap gap-2">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        type="button"
                        class="btn"
                        :class="activeTab === tab.key ? 'btn-primary' : 'btn-outline-primary'"
                        @click="activeTab = tab.key; cancelEdit();"
                    >
                        {{ tab.label }}
                    </button>
                </div>

                <!-- Alta -->
                <div class="mb-5 flex flex-wrap items-start gap-2">
                    <div class="w-full sm:w-72">
                        <input
                            v-model="newValue"
                            type="text"
                            class="form-input"
                            :placeholder="`Nueva ${currentConfig.label.toLowerCase()}...`"
                            @keyup.enter="addOption"
                        />
                        <InputError :message="newValueError" class="mt-1" />
                    </div>
                    <button type="button" class="btn btn-primary" :disabled="saving" @click="addOption">
                        Agregar
                    </button>
                </div>

                <!-- Listado -->
                <div class="overflow-x-auto">
                    <table class="w-full table-auto">
                        <thead>
                            <tr class="bg-gray-100 text-left dark:bg-gray-700">
                                <th class="px-4 py-3 font-semibold">Descripción</th>
                                <th class="px-4 py-3 font-semibold">Cursos que la usan</th>
                                <th class="px-4 py-3 font-semibold">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="option in currentValues"
                                :key="String(option.id ?? option.description)"
                                class="border-b border-gray-200 dark:border-gray-700"
                            >
                                <td class="px-4 py-3">
                                    <template v-if="editing && (editing.id ?? editing.description) === (option.id ?? option.description)">
                                        <div class="w-full sm:w-72">
                                            <input
                                                v-model="editingValue"
                                                type="text"
                                                class="form-input"
                                                @keyup.enter="updateOption"
                                            />
                                            <InputError :message="editingError" class="mt-1" />
                                        </div>
                                    </template>
                                    <template v-else>{{ option.description }}</template>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                        :class="option.use_count > 0 ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-200'"
                                    >
                                        {{ option.use_count }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <template v-if="editing && (editing.id ?? editing.description) === (option.id ?? option.description)">
                                        <button type="button" class="btn btn-primary btn-sm mr-2" :disabled="saving" @click="updateOption">Guardar</button>
                                        <button type="button" class="btn btn-outline-danger btn-sm" @click="cancelEdit">Cancelar</button>
                                    </template>
                                    <template v-else>
                                        <button type="button" class="btn btn-outline-primary btn-sm mr-2" @click="startEdit(option)">Editar</button>
                                        <button type="button" class="btn btn-outline-danger btn-sm" @click="askDelete(option)">Eliminar</button>
                                    </template>
                                </td>
                            </tr>
                            <tr v-if="currentValues.length === 0">
                                <td colspan="3" class="px-4 py-6 text-center text-sm text-gray-500">No hay opciones registradas.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Confirmación de eliminación (opción sin uso) -->
        <ConfirmationModal :show="confirming !== null" @close="confirming = null">
            <template #title>Eliminar {{ currentConfig.label.toLowerCase() }}</template>
            <template #content>
                <p>
                    ¿Seguro que deseas eliminar <strong>{{ confirming?.description }}</strong>? Ningún curso la está
                    usando.
                </p>
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                    Escribe <strong>{{ confirming?.description }}</strong> para confirmar:
                </p>
                <input v-model="confirmText" type="text" class="form-input mt-1" />
            </template>
            <template #footer>
                <button type="button" class="btn btn-outline-danger mr-2" @click="confirming = null">Cancelar</button>
                <button type="button" class="btn btn-danger" :disabled="!confirmTextMatches || saving" @click="deleteOption(confirming)">
                    Eliminar
                </button>
            </template>
        </ConfirmationModal>

        <!-- Aviso: opción en uso -->
        <ModalLarge :show="inUse !== null" :onClose="closeInUse">
            <template #title>
                No se puede eliminar "{{ inUse?.label }}"
            </template>
            <template #message>
                {{ inUse?.message }}
            </template>
            <template #content>
                <p class="mb-3 text-sm text-gray-600 dark:text-gray-300">
                    Reasigna cada curso a otra {{ (inUseTabLabel || '').toLowerCase() }} y vuelve a intentar la
                    eliminación, o abre el curso para editarlo completo.
                </p>

                <div class="max-h-80 space-y-3 overflow-y-auto pr-1">
                    <div
                        v-for="course in inUse?.courses"
                        :key="course.id"
                        class="flex flex-wrap items-center gap-2 rounded-lg border border-gray-200 p-3 dark:border-gray-600"
                    >
                        <div class="min-w-40 flex-1">
                            <p class="text-sm font-medium">{{ course.description }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Usa: {{ inUse?.label }}</p>
                        </div>

                        <select v-model="assignSelections[course.id]" class="form-select w-52 text-sm">
                            <option :value="undefined" disabled>Reasignar a...</option>
                            <option v-for="alt in alternativesFor(course)" :key="String(alt.value)" :value="alt.value">
                                {{ alt.label }}
                            </option>
                        </select>

                        <button
                            type="button"
                            class="btn btn-primary btn-sm"
                            :disabled="assignSavingId"
                            @click="assignToCourse(course)"
                        >
                            Reasignar
                        </button>

                        <button type="button" class="btn btn-outline-primary btn-sm" @click="openCourse(course)">
                            Abrir curso
                        </button>
                    </div>

                    <p v-if="inUse?.courses.length === 0" class="text-sm text-gray-500">
                        Esta opción no puede eliminarse porque tiene inscripciones históricas asociadas.
                    </p>
                </div>
            </template>
        </ModalLarge>
    </AppLayout>
</template>
