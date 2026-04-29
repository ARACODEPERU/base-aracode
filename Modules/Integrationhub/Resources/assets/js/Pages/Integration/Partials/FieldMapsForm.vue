<script setup>
import { ref, computed, watch } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import Swal2 from 'sweetalert2';
import axios from 'axios';
import ModalLarge from '@/Components/ModalLarge.vue';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';
import { useForm, router } from '@inertiajs/vue3';
import Multiselect from "@suadelabs/vue3-multiselect";
import "@suadelabs/vue3-multiselect/dist/vue3-multiselect.css";
import ModalLargeX from '@/Components/ModalLargeX.vue';

const props = defineProps({
    integrationId: {
        type: Number,
        required: true
    },
    endpoints: {
        type: Array,
        default: () => []
    },
    fieldMaps: {
        type: Array,
        default: () => []
    }
});

const fieldTypes = [
    { value: 'static', label: 'Estático' },
    { value: 'table_field', label: 'Campo de Tabla' },
    { value: 'query', label: 'Consulta' },
    { value: 'computed', label: 'Calculado' },
    { value: 'custom', label: 'Personalizado' },
];

const sourceTypes = [
    { value: 'static', label: 'Estático' },
    { value: 'database', label: 'Base de Datos' },
    { value: 'query', label: 'Consulta SQL' },
    { value: 'function', label: 'Función' },
];

const newFieldMap = useForm({
    field_map_id: null,
    endpoint_id: null,
    field_key: '',
    field_value: '',
    field_type: 'table_field',
    source_type: 'database',
    source_table: '',
    source_field: '',
    default_value: '',
    is_enabled: true,
    has_subitems: false,
    subitems: null,
    structure_type: 'object',
    array_query: '',
    default_type: 'none',
    default_table: '',
    default_field: ''
});

const showModal = ref(false);
const showSubitemsModal = ref(false);
const showSubitemsListModal = ref(false);
const saving = ref(false);
const savingSubitem = ref(false);
const savingSubitems = ref(false);
const togglingId = ref(null);
const togglingSubitemId = ref(null);
const selectedEndpoint = ref(null);
const editingFieldMap = ref(null);
const editingStructureType = ref('object');

// Subitems
const subitemsList = ref([]);
const editingSubitem = ref(null);
const subitemTables = ref([]);
const subitemColumns = ref([]);
const loadingSubitemTables = ref(false);
const loadingSubitemColumns = ref(false);

// Array mode tables
const arrayDefaultTables = ref([]);
const arrayDefaultColumns = ref([]);
const loadingArrayDefaultTables = ref(false);
const loadingArrayDefaultColumns = ref(false);

// Multiselect states
const tables = ref([]);
const columns = ref([]);
const loadingTables = ref(false);
const loadingColumns = ref(false);

// Cargar tablas de la BD
const loadTables = async () => {
    loadingTables.value = true;
    try {
        const response = await axios.get(route('integrationhub_get_tables'));
        tables.value = response.data.tables.sort((a, b) => a.localeCompare(b));
    } finally {
        loadingTables.value = false;
    }
};

// Cargar columnas de una tabla
const loadColumns = async (table) => {
    if (!table) {
        columns.value = [];
        return;
    }
    loadingColumns.value = true;
    try {
        const response = await axios.get(route('integrationhub_get_table_columns'), {
            params: { table }
        });
        columns.value = response.data.columns.map(c => ({
            label: c.name + (c.comment ? ` - ${c.comment}` : ''),
            value: c.name,
            originalName: c.name
        }));
    } finally {
        loadingColumns.value = false;
    }
};

// Watch para cargar tablas cuando cambia source_type
watch(() => newFieldMap.source_type, (val) => {
    if (val === 'database') {
        loadTables();
    } else {
        tables.value = [];
        columns.value = [];
    }
});

// Watch para cargar columnas cuando cambia source_table
watch(() => newFieldMap.source_table, (val) => {
    loadColumns(val);
});

// Watch para cargar columnas cuando cambia la tabla del subitem
watch(() => editingSubitem.value?.source_table, (val) => {
    if (editingSubitem.value && val) {
        loadSubitemColumns(typeof val === 'object' ? val.value : val);
    }
});

// Watch para detectar cuando se activa subitems
watch(() => newFieldMap.has_subitems, (val) => {
    if (val && newFieldMap.structure_type === 'array') {
        loadArrayDefaultTables();
    }
});

// Watch para cargar columnas cuando cambia la tabla de valor por defecto en modo array
watch(() => newFieldMap.default_table, (val) => {
    if (newFieldMap.has_subitems && newFieldMap.structure_type === 'array' && newFieldMap.default_type === 'variable' && val) {
        loadArrayDefaultColumns(typeof val === 'object' ? val.value : val);
    }
});

// Watch para cargar tablas cuando cambia a modo array
watch(() => newFieldMap.structure_type, (val) => {
    if (newFieldMap.has_subitems && val === 'array') {
        loadArrayDefaultTables();
    }
});

// Watch para transformar default_field a objeto cuando las columnas terminen de cargar
watch(() => arrayDefaultColumns.value, (columns) => {
    if (columns.length > 0 && newFieldMap.default_field && newFieldMap.has_subitems && newFieldMap.structure_type === 'array') {
        const currentValue = typeof newFieldMap.default_field === 'object'
            ? newFieldMap.default_field.value
            : newFieldMap.default_field;
        const foundColumn = columns.find(c => c.value === currentValue);
        if (foundColumn) {
            newFieldMap.default_field = foundColumn;
        }
    }
});

// Propiedades computadas para visibilidad
const showSourceFields = computed(() => {
    return newFieldMap.field_type !== 'static' && newFieldMap.source_type === 'database';
});

const showQueryField = computed(() => {
    return newFieldMap.source_type === 'query';
});

const showDefault = computed(() => {
    return newFieldMap.field_type === 'static' || newFieldMap.source_type !== 'database';
});

const isSubitemsActive = computed(() => {
    return newFieldMap.has_subitems === true;
});

const isArrayMode = computed(() => {
    return newFieldMap.has_subitems === true && newFieldMap.structure_type === 'array';
});

const showArrayDefaultTableField = computed(() => {
    return newFieldMap.default_type === 'variable';
});

const filteredFieldMaps = computed(() => {
    if (!selectedEndpoint.value) {
        return props.fieldMaps;
    }
    return props.fieldMaps.filter(fm => fm.endpoint_id === selectedEndpoint.value);
});

const addFieldMap = () => {
    newFieldMap.endpoint_id = selectedEndpoint.value || (props.endpoints[0]?.id || null);
    newFieldMap.field_key = '';
    newFieldMap.field_value = '';
    newFieldMap.field_type = 'table_field';
    newFieldMap.source_type = 'database';
    newFieldMap.source_table = '';
    newFieldMap.source_field = '';
    newFieldMap.default_value = '';
    newFieldMap.is_enabled = true;
    newFieldMap.has_subitems = false;
    newFieldMap.subitems = null;
    newFieldMap.structure_type = 'object';
    newFieldMap.array_query = '';
    newFieldMap.default_type = 'none';
    newFieldMap.default_table = '';
    newFieldMap.default_field = '';

    // Cargar tablas automáticamente
    tables.value = [];
    columns.value = [];
    loadTables();

    showModal.value = true;
};

const editFieldMap = (fieldMap) => {
    newFieldMap.field_map_id = fieldMap.id;
    newFieldMap.endpoint_id = fieldMap.endpoint_id;
    newFieldMap.field_key = fieldMap.field_key;
    newFieldMap.field_value = fieldMap.field_value || '';
    newFieldMap.field_type = fieldMap.field_type;
    newFieldMap.source_type = fieldMap.source_type;
    newFieldMap.source_table = fieldMap.source_table || '';
    newFieldMap.default_value = fieldMap.default_value || '';
    newFieldMap.is_enabled = fieldMap.is_enabled;
    newFieldMap.has_subitems = fieldMap.has_subitems || false;
    newFieldMap.subitems = fieldMap.subitems || null;
    newFieldMap.structure_type = fieldMap.structure_type || 'object';
    newFieldMap.array_query = fieldMap.array_query || '';
    newFieldMap.default_type = fieldMap.default_type || 'none';
    newFieldMap.default_table = fieldMap.default_table || '';
    newFieldMap.default_field = fieldMap.default_field || '';

    // Si es database, cargar tablas y columnas
    if (fieldMap.source_type === 'database') {
        loadTables().then(() => {
            newFieldMap.source_table = fieldMap.source_table;
            loadColumns(fieldMap.source_table).then(() => {
                // Buscar el objeto completo en columns
                const foundColumn = columns.value.find(c => c.value === fieldMap.source_field);
                newFieldMap.source_field = foundColumn || { label: fieldMap.source_field, value: fieldMap.source_field };
            });
        });
    } else {
        newFieldMap.source_field = '';
    }

    // Si es modo array, cargar tablas para valor por defecto
    if (fieldMap.has_subitems && fieldMap.structure_type === 'array') {
        loadArrayDefaultTables().then(() => {
            if (fieldMap.default_table) {
                loadArrayDefaultColumns(fieldMap.default_table);
            }
        });
    }

    showModal.value = true;
};

// Modal de Subitems
const openSubitemsModal = (fieldMap) => {
    editingFieldMap.value = fieldMap;
    showSubitemsModal.value = true;
};

const onFieldTypeChange = () => {
    // Resetear campos relacionados al cambiar tipo
    if (newFieldMap.field_type === 'static') {
        newFieldMap.source_type = 'static';
        newFieldMap.source_table = '';
        newFieldMap.source_field = '';
    } else if (newFieldMap.source_type === 'query') {
        // Ya queda visible el textarea
    }
};

const onStructureTypeChange = () => {
    // Cuando cambia el tipo de estructura, cargar tablas si es array
    if (newFieldMap.has_subitems && newFieldMap.structure_type === 'array') {
        loadArrayDefaultTables();
    }
};

const confirmDelete = (fieldMap) => {
    const fieldName = fieldMap.field_key;

    Swal2.fire({
        title: '¿Eliminar mapeo?',
        text: `¿Estás seguro de eliminar el mapeo "${fieldName}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Eliminar',
        cancelButtonText: 'Cancelar',
        padding: '2em',
        customClass: 'sweet-alerts',
    }).then((result) => {
        if (result.isConfirmed) {
            destroyServer(fieldMap.id);
        }
    });
};

const toggleEnabled = (fieldMap) => {
    togglingId.value = fieldMap.id;
    let lisActive = !fieldMap.is_enabled;

    axios.put(route('integrationhub_update_status_fieldmap', props.integrationId), {
        field_map_id: fieldMap.id,
        is_active: lisActive
    }).then(() => {
        fieldMap.is_enabled = lisActive;
        togglingId.value = null;
        Swal2.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: lisActive ? 'Mapeo activado' : 'Mapeo desactivado',
            showConfirmButton: false,
            timer: 2000,
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    }).catch(() => {
        togglingId.value = null;
        Swal2.fire({
            title: 'Error',
            text: 'No se pudo actualizar el estado.',
            icon: 'error',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    });
};

const destroyServer = async (id) => {
    try {
        await axios.delete(route('integrationhub_destroy_fieldmap', id)).then(() => {
            Swal2.fire({
                title: 'Enhorabuena',
                text: 'Se eliminó correctamente',
                icon: 'success',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
            refreshFieldMaps();
        });
    } catch (error) {
        Swal2.fire({
            title: 'Error',
            text: 'No se pudo eliminar el mapeo.',
            icon: 'error',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    }
};

const storeToServer = async () => {
    // Si tiene subitems, manejar según el tipo de estructura
    if (newFieldMap.has_subitems) {
        newFieldMap.default_value = null;
        newFieldMap.field_type = 'table_field';
        newFieldMap.source_type = 'database';
        newFieldMap.source_table = null;
        newFieldMap.source_field = null;
    }

    // Extraer valores de objetos antes de enviar
    const dataToSave = {
        ...newFieldMap,
        source_field: newFieldMap.source_field && typeof newFieldMap.source_field === 'object' ? newFieldMap.source_field.value : newFieldMap.source_field,
        source_table: newFieldMap.source_table && typeof newFieldMap.source_table === 'object' ? newFieldMap.source_table.value : newFieldMap.source_table,
        default_table: newFieldMap.default_table && typeof newFieldMap.default_table === 'object' ? newFieldMap.default_table.value : newFieldMap.default_table,
        default_field: newFieldMap.default_field && typeof newFieldMap.default_field === 'object' ? newFieldMap.default_field.value : newFieldMap.default_field,
        subitems: newFieldMap.subitems ? JSON.stringify(newFieldMap.subitems) : null,
    };

    // Activar estado de guardado
    saving.value = true;

    // Usar axios directamente para tener control total
    try {
        await axios.put(route('integrationhub_update_fieldmap', props.integrationId), dataToSave);
        saving.value = false;
        Swal2.fire({
            title: 'Enhorabuena',
            text: 'Se registró correctamente',
            icon: 'success',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
        showModal.value = false;
        newFieldMap.reset();
        refreshFieldMaps();
    } catch (error) {
        saving.value = false;
        Swal2.fire({
            title: 'Error',
            text: error.response?.data?.message || 'No se pudo guardar el mapeo.',
            icon: 'error',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    }
};

const refreshFieldMaps = async () => {
    router.visit(route('integrationhub_editar', props.integrationId), {
        method: "get",
        replace: true,
        preserveState: true,
        preserveScroll: true,
        only: ['integration'],
    });
};

const getEndpointName = (endpointId) => {
    const endpoint = props.endpoints.find(e => e.id === endpointId);
    return endpoint ? endpoint.name : `Endpoint #${endpointId}`;
};

// Funciones de Subitems
const loadSubitems = (fieldMap) => {
    if (fieldMap.subitems && Array.isArray(fieldMap.subitems)) {
        subitemsList.value = fieldMap.subitems;
    } else {
        subitemsList.value = [];
    }
};

const openSubitemsListModal = (fieldMap) => {
    editingFieldMap.value = fieldMap;
    editingStructureType.value = fieldMap.structure_type || 'object';

    // Cargar subitems - verificar si viene de newFieldMap o del fieldMap de props
    if (fieldMap.subitems && Array.isArray(fieldMap.subitems)) {
        subitemsList.value = fieldMap.subitems;
    } else if (fieldMap.id && fieldMap.id.toString().startsWith('new_')) {
        // Es un field map nuevo desde el modal principal
        subitemsList.value = newFieldMap.subitems || [];
    } else {
        // Buscar en props.fieldMaps para obtener los subitems actualizados
        const foundFieldMap = props.fieldMaps.find(fm => fm.id === fieldMap.id);
        if (foundFieldMap && foundFieldMap.subitems) {
            subitemsList.value = foundFieldMap.subitems;
        } else {
            subitemsList.value = [];
        }
    }

    showSubitemsListModal.value = true;
};

const saveSubitemsList = async () => {
    const fieldMapId = editingFieldMap.value.id;

    // Si es un field map nuevo (sin id válido), no guardar en backend
    if (!fieldMapId || fieldMapId.toString().startsWith('new_')) {
        // Solo actualizar en el estado local
        newFieldMap.subitems = subitemsList.value;

        Swal2.fire({
            title: 'Enhorabuena',
            text: 'Subitems guardados localmente. Guarde el field map para persistir los cambios.',
            icon: 'success',
            padding: '2em',
            customClass: 'sweet-alerts',
        });

        showSubitemsListModal.value = false;
        return;
    }
    
    savingSubitems.value = true;
    
    try {
        // Enviar al backend
        await axios.put(route('integrationhub_update_subitems', props.integrationId), {
            field_map_id: fieldMapId,
            subitems: JSON.stringify(subitemsList.value)
        });

        // Actualizar también en props.fieldMaps
        const fieldMapIndex = props.fieldMaps.findIndex(fm => fm.id === fieldMapId);
        if (fieldMapIndex >= 0) {
            props.fieldMaps[fieldMapIndex].subitems = subitemsList.value;
        }
        
        savingSubitems.value = false;

        Swal2.fire({
            title: 'Enhorabuena',
            text: 'Subitems guardados correctamente',
            icon: 'success',
            padding: '2em',
            customClass: 'sweet-alerts',
        });

        showSubitemsListModal.value = false;
    } catch (error) {
        savingSubitems.value = false;
        Swal2.fire({
            title: 'Error',
            text: 'No se pudieron guardar los subitems',
            icon: 'error',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    }
};

const openAddSubitemModal = () => {
    editingSubitem.value = {
        id: 'sub_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
        field_key: '',
        field_value: '',
        field_type: 'table_field',
        source_type: 'database',
        source_table: '',
        source_field: '',
        default_value: '',
        is_enabled: true,
        sort_order: subitemsList.value.length
    };
    subitemTables.value = [];
    subitemColumns.value = [];
    loadSubitemTables();
    showSubitemsModal.value = true;
};

const loadSubitemTables = async () => {
    loadingSubitemTables.value = true;
    try {
        const response = await axios.get(route('integrationhub_get_tables'));
        subitemTables.value = response.data.tables.sort((a, b) => a.localeCompare(b));
    } finally {
        loadingSubitemTables.value = false;
    }
};

const loadSubitemColumns = async (table) => {
    if (!table) {
        subitemColumns.value = [];
        return;
    }
    loadingSubitemColumns.value = true;
    try {
        const response = await axios.get(route('integrationhub_get_table_columns'), {
            params: { table }
        });
        subitemColumns.value = response.data.columns.map(c => ({
            label: c.name + (c.comment ? ` - ${c.comment}` : ''),
            value: c.name,
            originalName: c.name
        }));
    } finally {
        loadingSubitemColumns.value = false;
    }
};

const editSubitem = (subitem) => {
    editingSubitem.value = { ...subitem };
    loadSubitemTables().then(() => {
        if (subitem.source_table) {
            const tableObj = subitemTables.value.find(t => t === subitem.source_table || t.value === subitem.source_table);
            if (tableObj) {
                const tableVal = typeof tableObj === 'object' ? tableObj.value : tableObj;
                loadSubitemColumns(tableVal).then(() => {
                    const foundColumn = subitemColumns.value.find(c => c.value === subitem.source_field);
                    editingSubitem.value.source_field = foundColumn || { label: subitem.source_field, value: subitem.source_field };
                });
            }
        }
    });
    showSubitemsListModal.value = false;
    setTimeout(() => {
        showSubitemsModal.value = true;
    }, 150);
};

const saveSubitem = async () => {
    if (!editingSubitem.value.field_key) {
        Swal2.fire({
            title: 'Error',
            text: 'El campo clave es requerido',
            icon: 'error',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
        return;
    }

    savingSubitem.value = true;

    try {
        // Extraer valores de objetos
        const subitemData = {
            ...editingSubitem.value,
            source_field: editingSubitem.value.source_field && typeof editingSubitem.value.source_field === 'object'
                ? editingSubitem.value.source_field.value
                : editingSubitem.value.source_field,
            source_table: editingSubitem.value.source_table && typeof editingSubitem.value.source_table === 'object'
                ? editingSubitem.value.source_table.value
                : editingSubitem.value.source_table,
        };

        const existingIndex = subitemsList.value.findIndex(s => s.id === subitemData.id);
        if (existingIndex >= 0) {
            subitemsList.value[existingIndex] = subitemData;
        } else {
            subitemData.sort_order = subitemsList.value.length;
            subitemsList.value.push(subitemData);
        }

        // Actualizar en el field map padre
        const fieldMapIndex = props.fieldMaps.findIndex(fm => fm.id === editingFieldMap.value.id);
        if (fieldMapIndex >= 0) {
            props.fieldMaps[fieldMapIndex].subitems = subitemsList.value;
        }

        savingSubitem.value = false;
        showSubitemsModal.value = false;
        showSubitemsListModal.value = true;

        Swal2.fire({
            title: 'Enhorabuena',
            text: 'Subitem guardado correctamente',
            icon: 'success',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    } catch (error) {
        savingSubitem.value = false;
        Swal2.fire({
            title: 'Error',
            text: 'No se pudo guardar el subitem',
            icon: 'error',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    }
};

const confirmDeleteSubitem = (subitem) => {
    Swal2.fire({
        title: '¿Eliminar subitem?',
        text: `¿Estás seguro de eliminar el subitem "${subitem.field_key}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Eliminar',
        cancelButtonText: 'Cancelar',
        padding: '2em',
        customClass: 'sweet-alerts',
    }).then((result) => {
        if (result.isConfirmed) {
            deleteSubitem(subitem);
        }
    });
};

const deleteSubitem = (subitem) => {
    subitemsList.value = subitemsList.value.filter(s => s.id !== subitem.id);

    // Reordenar
    subitemsList.value.forEach((s, index) => {
        s.sort_order = index;
    });

    // Actualizar en el field map padre
    const fieldMapIndex = props.fieldMaps.findIndex(fm => fm.id === editingFieldMap.value.id);
    if (fieldMapIndex >= 0) {
        props.fieldMaps[fieldMapIndex].subitems = subitemsList.value;
    }

    Swal2.fire({
        title: 'Enhorabuena',
        text: 'Subitem eliminado correctamente',
        icon: 'success',
        padding: '2em',
        customClass: 'sweet-alerts',
    });
};

const toggleSubitemEnabled = (subitem) => {
    const index = subitemsList.value.findIndex(s => s.id === subitem.id);
    if (index >= 0) {
        subitemsList.value[index].is_enabled = !subitemsList.value[index].is_enabled;

        // Actualizar en el field map padre
        const fieldMapIndex = props.fieldMaps.findIndex(fm => fm.id === editingFieldMap.value.id);
        if (fieldMapIndex >= 0) {
            props.fieldMaps[fieldMapIndex].subitems = subitemsList.value;
        }
    }
};

// Cargar tablas para valor por defecto en modo array
const loadArrayDefaultTables = async () => {
    loadingArrayDefaultTables.value = true;
    try {
        const response = await axios.get(route('integrationhub_get_tables'));
        arrayDefaultTables.value = response.data.tables.sort((a, b) => a.localeCompare(b));
    } finally {
        loadingArrayDefaultTables.value = false;
    }
};

const loadArrayDefaultColumns = async (table) => {
    if (!table) {
        arrayDefaultColumns.value = [];
        return;
    }
    loadingArrayDefaultColumns.value = true;
    try {
        const response = await axios.get(route('integrationhub_get_table_columns'), {
            params: { table }
        });
        arrayDefaultColumns.value = response.data.columns.map(c => ({
            label: c.name + (c.comment ? ` - ${c.comment}` : ''),
            value: c.name,
            originalName: c.name
        }));
    } finally {
        loadingArrayDefaultColumns.value = false;
    }
};
</script>

<template>
    <div class="mb-4">
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
            Mapea los campos de tu sistema a los campos que envía la API externa.
        </p>
        <div class="flex items-center gap-3">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Filtrar por Endpoint:</label>
            <select v-model="selectedEndpoint" class="form-select w-auto">
                <option :value="null">Todos los endpoints</option>
                <option v-for="ep in endpoints" :key="ep.id" :value="ep.id">{{ ep.name }}</option>
            </select>
        </div>
    </div>

    <div class="mb-4 flex justify-end">
        <button @click="addFieldMap" :disabled="!endpoints.length" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition flex items-center gap-2 disabled:opacity-50">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Agregar Mapeo
        </button>
    </div>

    <div v-if="filteredFieldMaps.length === 0" class="text-center py-8">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m-4 10v-5m4-5v5m-4-10v-5" />
        </svg>
        <p class="text-gray-500 dark:text-gray-400">No hay mapeos configurados.</p>
        <p class="text-sm text-gray-400 mt-1">Haz clic en "Agregar Mapeo" para comenzar.</p>
    </div>

    <div v-else class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-zinc-700 dark:text-gray-400">
                <tr>
                    <th class="px-4 py-3 w-24">Activo</th>
                    <th class="px-4 py-3">Endpoint</th>
                    <th class="px-4 py-3">Campo Clave</th>
                    <th class="px-4 py-3">Campo Valor</th>
                    <th class="px-4 py-3">Tipo</th>
                    <th class="px-4 py-3">Origen</th>
                    <th class="px-4 py-3 w-24">Subitems</th>
                    <th class="px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(fm, index) in filteredFieldMaps" :key="index" class="border-b dark:border-zinc-600 hover:bg-gray-50 dark:hover:bg-zinc-700/50">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div v-if="togglingId === fm.id" class="flex items-center justify-center h-6 w-11">
                                <svg class="animate-spin h-5 w-5 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                            <button v-else @click="toggleEnabled(fm)"
                                :class="fm.is_enabled ? 'bg-green-500' : 'bg-gray-300 dark:bg-zinc-600'"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200">
                                <span :class="fm.is_enabled ? 'translate-x-6' : 'translate-x-1'"
                                    class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform duration-200 shadow-sm"></span>
                            </button>
                            <span :class="fm.is_enabled ? 'text-green-600 dark:text-green-400 font-medium' : 'text-gray-400'"
                                class="text-xs font-medium">
                                {{ fm.is_enabled ? 'Activo' : 'Inactivo' }}
                            </span>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-700 dark:bg-zinc-700 dark:text-gray-300">
                            {{ getEndpointName(fm.endpoint_id) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ fm.field_key }}</td>
                    <td class="px-4 py-3">
                        <code class="text-xs text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-zinc-700 px-2 py-1 rounded">
                            {{ fm.field_value || fm.default_value || '-' }}
                        </code>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                            {{ fm.field_type }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300">
                            {{ fm.source_type }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <button v-if="fm.has_subitems" @click="openSubitemsListModal(fm)"
                            class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-amber-100 text-amber-700 hover:bg-amber-200 dark:bg-amber-900 dark:text-amber-300 dark:hover:bg-amber-800 transition"
                            title="Ver Subitems">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        <span v-else class="text-gray-400">-</span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <button @click="editFieldMap(fm)" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                        <button @click="confirmDelete(fm)" class="text-red-600 hover:text-red-800 dark:text-red-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
        <h4 class="font-medium text-blue-900 dark:text-blue-300 mb-2">Tipos de Campo:</h4>
        <ul class="text-sm text-blue-700 dark:text-blue-400 space-y-1">
            <li><strong>Estático:</strong> Valor fijo que no cambia</li>
            <li><strong>Campo de Tabla:</strong> Valor de un campo de la base de datos</li>
            <li><strong>Consulta:</strong> Resultado de una consulta SQL</li>
            <li><strong>Calculado:</strong> Valor procesado con funciones</li>
            <li><strong>Personalizado:</strong> Lógica personalizada</li>
        </ul>
    </div>

    <!-- Modal Field Map -->
    <ModalLarge :show="showModal" :onClose="() => showModal = false" :icon="'/img/base-de-datos.png'">
        <template #title>
            {{ newFieldMap.field_map_id ? 'Editar Mapeo' : 'Agregar Mapeo' }}
        </template>
        <template #message>
            Configura el mapeo de campo entre tu sistema y la API
        </template>
        <template #content>
            <div class="space-y-4">
                <!-- Endpoint -->
                <div>
                    <InputLabel for="endpoint_id" value="Endpoint *" />
                    <select id="endpoint_id" v-model="newFieldMap.endpoint_id" class="form-select">
                        <option v-for="ep in endpoints" :key="ep.id" :value="ep.id">{{ ep.name }}</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Selecciona el endpoint destino</p>
                    <InputError :message="newFieldMap.errors.endpoint_id" class="mt-2" />
                </div>

                <!-- Toggle Contiene Subitems -->
                <div class="flex items-center gap-3 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                    <input
                        type="checkbox"
                        id="has_subitems"
                        :checked="newFieldMap.has_subitems"
                        @change="newFieldMap.has_subitems = $event.target.checked"
                        class="w-4 h-4 text-amber-600 bg-white border-amber-300 rounded focus:ring-amber-500"
                    />
                    <label for="has_subitems" class="text-sm font-medium text-amber-800 dark:text-amber-300">
                        Este campo contiene subitems (estructura jerárquica como items[], datos_cliente{})
                    </label>
                </div>

                <!-- Tipo de Estructura (solo si tiene subitems) -->
                <div v-if="newFieldMap.has_subitems" class="p-3 bg-gray-50 dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 block">Tipo de Estructura:</label>
                    <div class="flex gap-6">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" v-model="newFieldMap.structure_type" value="object" class="text-primary" @change="onStructureTypeChange" />
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Objeto (simple)</span>
                        </label>
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" v-model="newFieldMap.structure_type" value="array" class="text-primary" @change="onStructureTypeChange" />
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Array de Objetos</span>
                        </label>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">
                        <span v-if="newFieldMap.structure_type === 'object'">
                            Los subitems se agregan manualmente como propiedades del objeto
                        </span>
                        <span v-else>
                            Los datos vienen de una consulta SQL que retorna múltiples registros
                        </span>
                    </p>
                </div>

                <!-- Campos para Array de Objetos -->
                <div v-if="newFieldMap.has_subitems && newFieldMap.structure_type === 'array'" class="space-y-4 p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg border border-indigo-200 dark:border-indigo-800">
                    <h4 class="font-medium text-indigo-900 dark:text-indigo-300">Configuración del Array</h4>

                    <!-- Consulta SQL -->
                    <div>
                        <InputLabel for="array_query" value="Consulta SQL *" />
                        <textarea
                            id="array_query"
                            v-model="newFieldMap.array_query"
                            rows="4"
                            class="form-textarea"
                            placeholder="SELECT codigo, descripcion, precio FROM productos WHERE..."
                        ></textarea>
                        <p class="mt-1 text-xs text-gray-500">Esta consulta debe retornar los datos para el array. Los campos del SELECT serán las propiedades de cada objeto.</p>
                    </div>

                    <!-- Valor por Defecto -->
                    <div>
                        <InputLabel for="default_type" value="Si la consulta no retorna datos" />
                        <select id="default_type" v-model="newFieldMap.default_type" class="form-select">
                            <option value="none">No enviar nada</option>
                            <option value="static">Valor estático</option>
                            <option value="variable">De una tabla/campo</option>
                        </select>
                    </div>

                    <!-- Valor Estático -->
                    <div v-if="newFieldMap.default_type === 'static'">
                        <InputLabel for="array_default_value" value="Valor por Defecto" />
                        <input
                            id="array_default_value"
                            v-model="newFieldMap.default_value"
                            type="text"
                            class="form-input"
                            placeholder="Valor a enviar si no hay datos"
                        />
                    </div>

                    <!-- Valor Variable (Tabla + Campo) -->
                    <div v-if="newFieldMap.default_type === 'variable'" class="grid grid-cols-2 gap-3">
                        <div>
                            <InputLabel for="array_default_table" value="Tabla" />
                            <Multiselect
                                v-model="newFieldMap.default_table"
                                :options="arrayDefaultTables"
                                :searchable="true"
                                :loading="loadingArrayDefaultTables"
                                placeholder="Buscar tabla..."
                                class="w-full"
                            />
                        </div>
                        <div>
                            <InputLabel for="array_default_field" value="Campo" />
                            <Multiselect
                                v-model="newFieldMap.default_field"
                                :options="arrayDefaultColumns"
                                :searchable="true"
                                :loading="loadingArrayDefaultColumns"
                                placeholder="Seleccionar campo..."
                                label="label"
                                track-by="value"
                                class="w-full"
                            />
                        </div>
                    </div>
                </div>

                <!-- Botón gestionar subitems (solo modo objeto) -->
                <div v-if="newFieldMap.has_subitems && newFieldMap.structure_type === 'object'" class="flex justify-end">
                    <button @click="openSubitemsListModal({ ...newFieldMap, id: newFieldMap.field_map_id || 'new_' + Date.now() })"
                        class="px-4 py-2 bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300 rounded-lg hover:bg-amber-200 dark:hover:bg-amber-800 transition flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        Gestionar Subitems ({{ newFieldMap.subitems?.length || 0 }})
                    </button>
                </div>

                <!-- Tipo de Campo + Tipo de Origen (misma fila) -->
                <div class="grid grid-cols-2 gap-3" :class="{'opacity-50 pointer-events-none': newFieldMap.has_subitems}">
                    <div>
                        <InputLabel for="field_type" value="Tipo de Campo" />
                        <select id="field_type" v-model="newFieldMap.field_type" class="form-select" @change="onFieldTypeChange">
                            <option v-for="type in fieldTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                        </select>
                        <InputError :message="newFieldMap.errors.field_type" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="source_type" value="Tipo de Origen" />
                        <select id="source_type" v-model="newFieldMap.source_type" class="form-select">
                            <option v-for="type in sourceTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                        </select>
                        <InputError :message="newFieldMap.errors.source_type" class="mt-2" />
                    </div>
                </div>

                <!-- Tabla de Origen + Campo de Origen (solo si source_type = database) -->
                <div v-if="showSourceFields" class="grid grid-cols-2 gap-3" :class="{'opacity-50 pointer-events-none': newFieldMap.has_subitems}">
                    <div>
                        <InputLabel for="source_table" value="Tabla de Origen" />
                        <Multiselect
                            v-model="newFieldMap.source_table"
                            :options="tables"
                            :searchable="true"
                            :loading="loadingTables"
                            placeholder="Buscar tabla..."
                            class="w-full"
                            :disabled="newFieldMap.has_subitems"
                        />
                        <InputError :message="newFieldMap.errors.source_table" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="source_field" value="Campo de Origen" />
                        <Multiselect
                            v-model="newFieldMap.source_field"
                            :options="columns"
                            :searchable="true"
                            :loading="loadingColumns"
                            placeholder="Seleccionar campo..."
                            label="label"
                            track-by="value"
                            class="w-full"
                            :disabled="newFieldMap.has_subitems"
                        />
                        <InputError :message="newFieldMap.errors.source_field" class="mt-2" />
                    </div>
                </div>

                <!-- SQL Query (solo si source_type = query) -->
                <div v-if="showQueryField">
                    <InputLabel for="field_value" value="Consulta SQL" />
                    <textarea id="field_value" v-model="newFieldMap.field_value" rows="4" class="form-textarea"
                        placeholder="SELECT campo FROM tabla WHERE..."></textarea>
                    <p class="mt-1 text-xs text-gray-500">Escribe tu consulta SQL (SELECT, UPDATE, DELETE o CALL para stored procedure)</p>
                    <InputError :message="newFieldMap.errors.field_value" class="mt-2" />
                </div>

                <!-- Valor por Defecto (último campo visible) -->
                <div v-if="showDefault" :class="{'opacity-50 pointer-events-none': newFieldMap.has_subitems}">
                    <InputLabel for="default_value" value="Valor por Defecto" />
                    <input id="default_value" v-model="newFieldMap.default_value" type="text" class="form-input" placeholder="Valor si el campo está vacío" :disabled="newFieldMap.has_subitems" />
                    <p class="mt-1 text-xs text-gray-500">Valor por defecto cuando el campo no tiene datos</p>
                    <InputError :message="newFieldMap.errors.default_value" class="mt-2" />
                </div>

                <!-- Campo Clave + Campo Valor (al final) -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <InputLabel for="field_key" value="Campo Clave *" />
                        <input id="field_key" v-model="newFieldMap.field_key" type="text" class="form-input" placeholder="Ej: customer_name" />
                        <p class="mt-1 text-xs text-gray-500">Nombre del campo en el cuerpo de la petición (JSON)</p>
                        <InputError :message="newFieldMap.errors.field_key" class="mt-2" />
                    </div>
                    <div v-if="!showQueryField" :class="{'opacity-50 pointer-events-none': newFieldMap.has_subitems}">
                        <InputLabel for="field_value" value="Campo Valor" />
                        <input id="field_value" v-model="newFieldMap.field_value" type="text" class="form-input" placeholder="Ej: {{name}} o valor estático" :disabled="newFieldMap.has_subitems" />
                        <p class="mt-1 text-xs text-gray-500">Usa {{campo}} para referencias a campos de tu sistema</p>
                        <InputError :message="newFieldMap.errors.field_value" class="mt-2" />
                    </div>
                </div>
            </div>
        </template>
        <template #buttons>
            <PrimaryButton @click="storeToServer" :disabled="saving" class="mr-3">
                <IconLoader v-if="saving" class="w-4 h-4 mr-2 animate-spin" />
                {{ saving ? 'Guardando...' : 'Guardar' }}
            </PrimaryButton>
        </template>
    </ModalLarge>

    <!-- Modal Lista de Subitems -->
    <ModalLargeX :show="showSubitemsListModal" :onClose="() => showSubitemsListModal = false" :icon="'/img/base-de-datos.png'">
        <template #title>
            {{ editingStructureType === 'array' ? 'Configuración de Array' : 'Lista de Subitems' }}
        </template>
        <template #message>
            {{ editingStructureType === 'array' ? 'Array de Objetos' : 'Subitems' }} de: <strong>{{ editingFieldMap?.field_key }}</strong>
            <span class="ml-2 px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-700 dark:bg-zinc-700 dark:text-gray-300">
                {{ getEndpointName(editingFieldMap?.endpoint_id) }}
            </span>
        </template>
        <template #content>
            <div class="space-y-4">
                <!-- Modo Objeto: mostrar botones y tabla de subitems -->
                <template v-if="editingStructureType === 'object'">
                    <div class="flex justify-end">
                        <button @click="openAddSubitemModal" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Agregar Subitem
                        </button>
                    </div>

                    <div v-if="subitemsList.length === 0" class="text-center py-8">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <p class="text-gray-500 dark:text-gray-400">No hay subitems configurados.</p>
                        <p class="text-sm text-gray-400 mt-1">Haz clic en "Agregar Subitem" para comenzar.</p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-zinc-700 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-3 w-24">Activo</th>
                                    <th class="px-4 py-3">Campo Clave</th>
                                    <th class="px-4 py-3">Campo Valor</th>
                                    <th class="px-4 py-3">Tipo</th>
                                    <th class="px-4 py-3">Origen</th>
                                    <th class="px-4 py-3 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(subitem, index) in subitemsList" :key="index" class="border-b dark:border-zinc-600 hover:bg-gray-50 dark:hover:bg-zinc-700/50">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <div v-if="togglingSubitemId === subitem.id" class="flex items-center justify-center h-6 w-11">
                                                <svg class="animate-spin h-5 w-5 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                            </div>
                                            <button v-else @click="toggleSubitemEnabled(subitem)"
                                                :class="subitem.is_enabled ? 'bg-green-500' : 'bg-gray-300 dark:bg-zinc-600'"
                                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200">
                                                <span :class="subitem.is_enabled ? 'translate-x-6' : 'translate-x-1'"
                                                    class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform duration-200 shadow-sm"></span>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ subitem.field_key }}</td>
                                    <td class="px-4 py-3">
                                        <code class="text-xs text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-zinc-700 px-2 py-1 rounded">
                                            {{ subitem.field_value || subitem.default_value || '-' }}
                                        </code>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                            {{ subitem.field_type }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300">
                                            {{ subitem.source_type }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <button @click="editSubitem(subitem)" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 mr-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button @click="confirmDeleteSubitem(subitem)" class="text-red-600 hover:text-red-800 dark:text-red-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>

                <!-- Modo Array de Objetos: mostrar configuración en modo solo lectura -->
                <template v-else-if="editingStructureType === 'array'">
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <p class="text-sm text-blue-700 dark:text-blue-400">
                            Los datos de este array provienen de una consulta SQL. Los campos se generan automáticamente según las columnas del SELECT.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4">
                        <!-- Consulta SQL -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Consulta SQL:</label>
                            <div class="mt-1 p-3 bg-gray-100 dark:bg-zinc-800 rounded-lg">
                                <code class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap">{{ editingFieldMap?.array_query || 'No configurado' }}</code>
                            </div>
                        </div>

                        <!-- Tipo de Valor por Defecto -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Si la consulta no retorna datos:</label>
                            <div class="mt-2">
                                <span v-if="editingFieldMap?.default_type === 'none'" class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-700 dark:bg-zinc-700 dark:text-gray-300">
                                    No enviar nada
                                </span>
                                <span v-else-if="editingFieldMap?.default_type === 'static'" class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300">
                                    Valor estático
                                </span>
                                <span v-else-if="editingFieldMap?.default_type === 'variable'" class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300">
                                    De una tabla/campo
                                </span>
                            </div>
                        </div>

                        <!-- Valor Estático -->
                        <div v-if="editingFieldMap?.default_type === 'static'">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Valor por Defecto:</label>
                            <div class="mt-1 p-2 bg-gray-100 dark:bg-zinc-800 rounded-lg">
                                <code class="text-sm text-gray-800 dark:text-gray-200">{{ editingFieldMap?.default_value || 'No configurado' }}</code>
                            </div>
                        </div>

                        <!-- Tabla y Campo -->
                        <div v-if="editingFieldMap?.default_type === 'variable'" class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Tabla:</label>
                                <div class="mt-1 p-2 bg-gray-100 dark:bg-zinc-800 rounded-lg">
                                    <code class="text-sm text-gray-800 dark:text-gray-200">{{ editingFieldMap?.default_table || 'No configurado' }}</code>
                                </div>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Campo:</label>
                                <div class="mt-1 p-2 bg-gray-100 dark:bg-zinc-800 rounded-lg">
                                    <code class="text-sm text-gray-800 dark:text-gray-200">{{ editingFieldMap?.default_field || 'No configurado' }}</code>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </template>
        <template #buttons>
            <PrimaryButton v-if="editingStructureType === 'object'" @click="saveSubitemsList" :disabled="savingSubitems" class="mr-3">
                <IconLoader v-if="savingSubitems" class="w-4 h-4 mr-2 animate-spin" />
                {{ savingSubitems ? 'Guardando...' : 'Guardar Subitems' }}
            </PrimaryButton>
        </template>
    </ModalLargeX>

    <!-- Modal Gestionar Subitem -->
    <ModalLarge :show="showSubitemsModal" :onClose="() => showSubitemsModal = false" :icon="'/img/base-de-datos.png'">
        <template #title>
            {{ editingSubitem?.field_key && subitemsList.some(s => s.id === editingSubitem?.id) ? 'Editar Subitem' : 'Agregar Subitem' }}
        </template>
        <template #message>
            Subitem de: <strong>{{ editingFieldMap?.field_key }}</strong>
            <span class="ml-2 px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-700 dark:bg-zinc-700 dark:text-gray-300">
                {{ getEndpointName(editingFieldMap?.endpoint_id) }}
            </span>
        </template>
        <template #content>
            <div class="space-y-4">
                <!-- Tipo de Campo + Tipo de Origen -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <InputLabel for="subitem_field_type" value="Tipo de Campo" />
                        <select id="subitem_field_type" v-model="editingSubitem.field_type" class="form-select">
                            <option v-for="type in fieldTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel for="subitem_source_type" value="Tipo de Origen" />
                        <select id="subitem_source_type" v-model="editingSubitem.source_type" class="form-select">
                            <option v-for="type in sourceTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                        </select>
                    </div>
                </div>

                <!-- Tabla de Origen + Campo de Origen -->
                <div v-if="editingSubitem.field_type !== 'static' && editingSubitem.source_type === 'database'" class="grid grid-cols-2 gap-3">
                    <div>
                        <InputLabel for="subitem_source_table" value="Tabla de Origen" />
                        <Multiselect
                            v-model="editingSubitem.source_table"
                            :options="subitemTables"
                            :searchable="true"
                            :loading="loadingSubitemTables"
                            placeholder="Buscar tabla..."
                            class="w-full"
                        />
                    </div>
                    <div>
                        <InputLabel for="subitem_source_field" value="Campo de Origen" />
                        <Multiselect
                            v-model="editingSubitem.source_field"
                            :options="subitemColumns"
                            :searchable="true"
                            :loading="loadingSubitemColumns"
                            placeholder="Seleccionar campo..."
                            label="label"
                            track-by="value"
                            class="w-full"
                        />
                    </div>
                </div>

                <!-- SQL Query -->
                <div v-if="editingSubitem.source_type === 'query'">
                    <InputLabel for="subitem_field_value" value="Consulta SQL" />
                    <textarea id="subitem_field_value" v-model="editingSubitem.field_value" rows="4" class="form-textarea"
                        placeholder="SELECT campo FROM tabla WHERE..."></textarea>
                </div>

                <!-- Valor por Defecto -->
                <div v-if="editingSubitem.field_type === 'static' || editingSubitem.source_type !== 'database'">
                    <InputLabel for="subitem_default_value" value="Valor por Defecto" />
                    <input id="subitem_default_value" v-model="editingSubitem.default_value" type="text" class="form-input" placeholder="Valor si el campo está vacío" />
                </div>

                <!-- Campo Clave + Campo Valor -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <InputLabel for="subitem_field_key" value="Campo Clave *" />
                        <input id="subitem_field_key" v-model="editingSubitem.field_key" type="text" class="form-input" placeholder="Ej: codigo_interno" />
                        <p class="mt-1 text-xs text-gray-500">Nombre del campo en el JSON</p>
                    </div>
                    <div v-if="editingSubitem.source_type !== 'query'">
                        <InputLabel for="subitem_field_value" value="Campo Valor" />
                        <input id="subitem_field_value" v-model="editingSubitem.field_value" type="text" class="form-input" placeholder="Ej: {{codigo}} o valor estático" />
                    </div>
                </div>
            </div>
        </template>
        <template #buttons>
            <PrimaryButton @click="saveSubitem" :disabled="savingSubitem" class="mr-3">
                <IconLoader v-if="savingSubitem" class="w-4 h-4 mr-2 animate-spin" />
                {{ savingSubitem ? 'Guardando...' : 'Hecho' }}
            </PrimaryButton>
        </template>
    </ModalLarge>
</template>
