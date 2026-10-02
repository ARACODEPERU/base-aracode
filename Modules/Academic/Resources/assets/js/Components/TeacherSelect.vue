<script setup>
import { ref, watch } from 'vue';
import { faXmark, faMagnifyingGlass } from '@fortawesome/free-solid-svg-icons';

/**
 * Combo con buscador activo de docentes.
 * Carga personas de la tabla people vinculadas a aca_teachers
 * via el endpoint aca_school_structure_search_teachers.
 *
 * Uso:
 *   <TeacherSelect v-model="form.tutor_person_id" v-model:label="tutorLabel" placeholder="Buscar docente por nombre o DNI" />
 */
const props = defineProps({
    modelValue: { type: Number, default: null },
    label: { type: String, default: '' },
    placeholder: { type: String, default: 'Buscar docente por nombre o DNI' },
});

const emit = defineEmits(['update:modelValue', 'update:label']);

const query = ref('');
const results = ref([]);
const searching = ref(false);
let searchTimer = null;

watch(query, (q) => {
    if (searchTimer) clearTimeout(searchTimer);
    if (!q || q.trim().length < 2) {
        results.value = [];
        return;
    }
    searchTimer = setTimeout(async () => {
        searching.value = true;
        try {
            const res = await axios.post(route('aca_school_structure_search_teachers'), { search: q.trim() });
            results.value = res.data;
        } finally {
            searching.value = false;
        }
    }, 300);
});

const pick = (t) => {
    emit('update:modelValue', t.id);
    emit('update:label', t.full_name);
    query.value = '';
    results.value = [];
};

const clear = () => {
    emit('update:modelValue', null);
    emit('update:label', '');
};

const closeList = () => {
    // Pequeño delay para permitir el click en las opciones
    setTimeout(() => {
        results.value = [];
    }, 150);
};
</script>

<template>
    <div>
        <!-- Docente seleccionado -->
        <div v-if="modelValue && label" class="flex items-center gap-2">
            <input type="text" class="form-input" :value="label" disabled />
            <button type="button" class="btn btn-outline-danger btn-sm shrink-0" @click="clear" title="Quitar">
                <font-awesome-icon :icon="faXmark" />
            </button>
        </div>

        <!-- Buscador -->
        <div v-else class="relative">
            <div class="relative">
                <font-awesome-icon :icon="faMagnifyingGlass" class="absolute ltr:left-3 rtl:right-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-white-dark" />
                <input
                    v-model="query"
                    type="text"
                    class="form-input ltr:pl-9 rtl:pr-9"
                    :placeholder="placeholder"
                    @blur="closeList"
                />
            </div>

            <div v-if="results.length" class="absolute z-50 w-full panel p-2 max-h-60 overflow-y-auto">
                <button
                    type="button"
                    v-for="t in results"
                    :key="t.id"
                    class="w-full text-left px-3 py-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center justify-between gap-2"
                    @mousedown.prevent="pick(t)"
                >
                    <span class="truncate">{{ t.full_name }}</span>
                    <span class="text-xs text-white-dark shrink-0">{{ t.number }}<template v-if="t.teacher_code && t.teacher_code !== t.number"> · {{ t.teacher_code }}</template></span>
                </button>
            </div>
            <div v-else-if="searching" class="absolute z-50 w-full panel p-2 text-xs text-white-dark">
                Buscando...
            </div>
        </div>
    </div>
</template>
