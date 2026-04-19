<script setup>

import { ref, computed, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';
import InputError from '@/Components/InputError.vue';
import IconSave from "@/Components/vristo/icon/icon-save.vue";


const props = defineProps({
    course: {
        type: Object,
        default: () => ({}),
    },
    landing: {
        type: Object,
        default: () => ({}),
    },
    languageOptions: {
        type: Object,
        default: () => ({}),
    },
});


const formBanner = useForm({
    banner_start_date: props.landing.banner_start_date || '',
    banner_end_date: props.landing.banner_end_date || '',
    banner_duration: props.landing.banner_duration || 0,
    banner_language: props.landing.banner_language || 'es',
});

const calculatedDuration = computed(() => {
    if (formBanner.banner_start_date && formBanner.banner_end_date) {
        const start = new Date(formBanner.banner_start_date);
        const end = new Date(formBanner.banner_end_date);
        const diffTime = end - start;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
        return diffDays > 0 ? diffDays : 0;
    }
    return 0;
});

watch(calculatedDuration, (newVal) => {
    formBanner.banner_duration = newVal;
});

const saveBannerSettings = () => {
    formBanner.put(route('aca_courses_landing_update_banner', props.course.id),{
        errorBag: 'saveBannerSettings',
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: 'Sección Banner guardada correctamente',
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
            <i class="fa fa-image mr-2 text-blue-600"></i>
            Sección Banner
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Configura la información principal que se mostrará en el banner de la landing page.
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Información del Curso (Solo lectura) -->
        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider flex items-center">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-400 mr-2">
                        ℹ️
                    </span>
                    Información del Curso
                </h4>
                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                    🔒 Solo lectura
                </span>
            </div>

            <!-- Nota importante -->
            <div class="mb-4 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                <p class="text-xs text-amber-800 dark:text-amber-200">
                    <strong>📝 Nota:</strong> Esta información se sincroniza automáticamente desde el curso.
                    Para modificar estos datos, ve a <strong class="text-blue-600 dark:text-blue-400">Cursos → Editar</strong> y actualiza la información del curso allí.
                </p>
                <div class="mt-2">
                    <Link
                        :href="route('aca_courses_edit', course.id)"
                        class="inline-flex items-center gap-1 text-xs font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300"
                    >
                        <i class="fa fa-external-link"></i>
                        Ir a editar curso
                    </Link>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Categoría</label>
                    <div class="mt-1 flex items-center gap-2">
                        <span class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                            {{ course.category?.description || 'Sin categoría' }}
                        </span>
                        <i class="fa fa-check-circle text-green-500" title="Del curso"></i>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Título del Curso</label>
                    <div class="mt-1 flex items-center gap-2">
                        <span class="text-gray-900 dark:text-white font-medium">{{ course.description }}</span>
                        <i class="fa fa-check-circle text-green-500" title="Del curso"></i>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Imagen</label>
                    <div class="mt-2">
                        <div v-if="course.image" class="relative inline-block">
                            <img
                                :src="'/storage/' + course.image"
                                :alt="course.description"
                                class="h-32 w-48 object-cover rounded-lg border-2 border-gray-200 dark:border-gray-600"
                            >
                            <span class="absolute -top-2 -right-2 bg-green-500 text-white text-xs px-2 py-1 rounded-full">
                                <i class="fa fa-check"></i> Del curso
                            </span>
                        </div>
                        <span v-else class="text-gray-400 text-sm">Sin imagen</span>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Modalidad</label>
                    <div class="mt-1 flex items-center gap-2">
                        <span class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400">
                            {{ course.modality?.description || 'Sin modalidad' }}
                        </span>
                        <i class="fa fa-check-circle text-green-500" title="Del curso"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Configuración de Landing (Editable) -->
        <div class="space-y-6">
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4 flex items-center">
                    <i class="fa fa-cog mr-2"></i>
                    Configuración de Landing
                </h4>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Fecha de Inicio <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="date"
                            v-model="formBanner.banner_start_date"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 border"
                        >
                        <InputError :message="formBanner.errors.banner_start_date" class="mt-2" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Fecha de Fin <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="date"
                            v-model="formBanner.banner_end_date"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 border"
                        >
                        <InputError :message="formBanner.errors.banner_end_date" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Duración (días)
                        </label>
                        <div class="mt-1 flex items-center gap-2">
                            <span class="inline-flex items-center px-4 py-2 rounded-md text-sm font-bold bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                                {{ calculatedDuration }} días
                            </span>
                            <span class="text-xs text-gray-500">Calculado automáticamente</span>
                        </div>
                        <InputError :message="formBanner.errors.banner_duration" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Idioma <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="formBanner.banner_language"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 border"
                        >
                            <option v-for="(label, value) in languageOptions" :key="value" :value="value">
                                {{ label }}
                            </option>
                        </select>
                        <InputError :message="formBanner.errors.banner_language" class="mt-2" />
                    </div>
                </div>
            </div>
            <!-- Save Button (Solo para Banner) -->
            <div class="flex justify-end">
                <button
                    @click="saveBannerSettings"
                    :disabled="formBanner.processing"
                    :class="{ 'opacity-25': formBanner.processing }"
                    class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <IconLoader v-if="formBanner.processing" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" />
                    <IconSave v-else class="mr-2" />
                    {{ formBanner.processing ? 'Guardando...' : 'Guardar Sección Banner' }}
                </button>
            </div>
        </div>

    </div>
</template>
