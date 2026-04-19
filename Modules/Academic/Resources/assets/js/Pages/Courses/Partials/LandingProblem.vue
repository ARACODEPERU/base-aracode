<script setup>

import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';
import InputError from '@/Components/InputError.vue';
import IconSave from "@/Components/vristo/icon/icon-save.vue";
import * as fasIcons from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
    course: {
        type: Object,
        default: () => ({}),
    },
    landing: {
        type: Object,
        default: () => ({}),
    },
});

const iconCategories = {
    problemas: [
        { value: 'fa-clock', label: 'Tiempo' },
        { value: 'fa-money-bill-wave', label: 'Dinero' },
        { value: 'fa-chart-line', label: 'Sin Resultados' },
        { value: 'fa-exclamation-triangle', label: 'Problema' },
        { value: 'fa-user-clock', label: 'Perdida Tiempo' },
        { value: 'fa-coins', label: 'Gastos' },
        { value: 'fa-ban', label: 'Bloqueo' },
        { value: 'fa-times-circle', label: 'Fracaso' },
        { value: 'fa-search', label: 'Busqueda' },
    ],
    emociones: [
        { value: 'fa-face-frown', label: 'Frustración' },
        { value: 'fa-face-sad-tear', label: 'Tristeza' },
        { value: 'fa-person-running', label: 'Estrés' },
        { value: 'fa-brain', label: 'Confusión' },
        { value: 'fa-heart-crack', label: 'Desánimo' },
        { value: 'fa-person-harassing', label: 'Presión' },
    ],
    soluciones: [
        { value: 'fa-lightbulb', label: 'Solución' },
        { value: 'fa-rocket', label: 'Mejora' },
        { value: 'fa-arrow-trend-up', label: 'Crecimiento' },
        { value: 'fa-chart-pie', label: 'Éxito' },
        { value: 'fa-trophy', label: 'Logro' },
    ],
    basicos: [
        { value: 'fa-users', label: 'Usuarios' },
        { value: 'fa-comments', label: 'Comentarios' },
        { value: 'fa-clock', label: 'Reloj' },
        { value: 'fa-briefcase', label: 'Negocios' },
        { value: 'fa-globe', label: 'Global' },
        { value: 'fa-star', label: 'Estrella' },
        { value: 'fa-check', label: 'Check' },
    ],
};

const categoryLabels = {
    problemas: 'Problemas',
    emociones: 'Emociones',
    soluciones: 'Soluciones',
    basicos: 'Básicos',
};

const getInitialProblemData = () => {
    const data = props.landing.problem_section;
    if (data && typeof data === 'string') {
        try {
            return JSON.parse(data);
        } catch (e) {
            return null;
        }
    }
    return data || null;
};

const initialData = getInitialProblemData();

const getDefaultItems = () => {
    if (initialData?.items && initialData.items.length >= 3) {
        return initialData.items;
    }
    return [
        { icon: '', title: '', description: '' },
        { icon: '', title: '', description: '' },
        { icon: '', title: '', description: '' },
    ];
};

const formProblem = useForm({
    name: initialData?.name || 'El Problema',
    title: initialData?.title || '',
    description: initialData?.description || '',
    items: getDefaultItems(),
});

const formatIconForVue = (iconName) => {
    if (!iconName) return fasIcons.faExclamationCircle;
    const cleanName = iconName.replace(/^fa-/, '');
    const pascalName = 'fa' + cleanName
        .split('-')
        .map(part => part.charAt(0).toUpperCase() + part.slice(1))
        .join('');
    return fasIcons[pascalName] || fasIcons.faExclamationCircle;
};

const saveProblemSettings = () => {
    formProblem.put(route('aca_courses_landing_update_problem', props.course.id),{
        errorBag: 'saveProblemSettings',
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: 'Sección El Problema guardada correctamente',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        },
    });
};

</script>
<template>
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
            <i class="fa fa-exclamation-triangle mr-2 text-blue-600"></i>
            Sección El Problema
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Define los problemas que tu curso resuelve (3 items固定).
        </p>
    </div>

    <div class="space-y-6">
        <!-- Datos Principales -->
        <div class="">
            <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4 flex items-center">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-400 mr-2">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 192 512">
                        <path fill="currentColor" d="M48 48a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zM0 192c0-17.7 14.3-32 32-32l64 0c17.7 0 32 14.3 32 32l0 256 32 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 512c-17.7 0-32-14.3-32-32s14.3-32 32-32l32 0 0-224-32 0c-17.7 0-32-14.3-32-32z"/>
                    </svg>
                </span>
                Datos Principales
            </h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Nombre
                    </label>
                    <input
                        type="text"
                        v-model="formProblem.name"
                        class="form-input"
                        placeholder="El Problema"
                    >
                    <InputError :message="formProblem.errors.name" class="mt-2" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Título
                    </label>
                    <input
                        type="text"
                        v-model="formProblem.title"
                        class="form-input"
                        placeholder="Título de la sección"
                    >
                    <InputError :message="formProblem.errors.title" class="mt-2" />
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Descripción
                    </label>
                    <textarea
                        v-model="formProblem.description"
                        rows="3"
                        class="form-textarea"
                        placeholder="Descripción de la sección..."
                    ></textarea>
                    <InputError :message="formProblem.errors.description" class="mt-2" />
                </div>
            </div>
        </div>

        <!-- Items (3 Fijos) -->
        <div class="">
            <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4 flex items-center">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-red-200 text-red-600 dark:bg-red-900/50 dark:text-red-400 mr-2">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                        <path fill="currentColor" d="M256 48C141.31 48 48 141.31 48 256s93.31 208 208 208 208-93.31 208-208S370.69 48 256 48zm75.31 260.69a16 16 0 1 1-22.62 22.62L256 278.63l-52.69 52.68a16 16 0 1 1-22.62-22.62L233.37 256l-52.68-52.69a16 16 0 1 1 22.62-22.62L256 233.37l52.69-52.68a16 16 0 1 1 22.62 22.62L278.63 256z"/>
                    </svg>
                </span>
                Los 3 Problemas
            </h4>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div 
                    v-for="(item, index) in formProblem.items" 
                    :key="index"
                    class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600 space-y-4"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-red-600 dark:text-red-400 uppercase">
                            Problema {{ index + 1 }}
                        </span>
                    </div>

                    <!-- Icono -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Icono
                        </label>
                        <div class="flex items-center gap-2 mt-1">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-400 shrink-0">
                                <font-awesome-icon v-if="item.icon" :icon="formatIconForVue(item.icon)" class="w-5 h-5" />
                                <font-awesome-icon v-else :icon="['fas', 'exclamation-circle']" class="w-5 h-5" />
                            </div>
                            <select
                                v-model="item.icon"
                                class="form-select flex-1 text-sm"
                            >
                                <option value="">Seleccionar icono</option>
                                <optgroup v-for="(icons, category) in iconCategories" :key="category" :label="categoryLabels[category]">
                                    <option v-for="icon in icons" :key="icon.value" :value="icon.value">
                                        {{ icon.label }}
                                    </option>
                                </optgroup>
                            </select>
                        </div>
                    </div>

                    <!-- Título -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Título
                        </label>
                        <input
                            type="text"
                            v-model="item.title"
                            class="form-input text-sm mt-1"
                            :placeholder="'Problema ' + (index + 1)"
                        >
                    </div>

                    <!-- Descripción -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Descripción
                        </label>
                        <textarea
                            v-model="item.description"
                            rows="2"
                            class="form-textarea text-sm mt-1"
                            :placeholder="'Descripción del problema ' + (index + 1)"
                        ></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex justify-end">
            <button
                @click="saveProblemSettings"
                :disabled="formProblem.processing"
                :class="{ 'opacity-25': formProblem.processing }"
                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <IconLoader v-if="formProblem.processing" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" />
                <IconSave v-else class="mr-2" />
                {{ formProblem.processing ? 'Guardando...' : 'Guardar Sección El Problema' }}
            </button>
        </div>
    </div>
</template>