<script setup>

import { ref, computed, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';
import InputError from '@/Components/InputError.vue';
import IconSave from "@/Components/vristo/icon/icon-save.vue";
import IconUsers from '@/Components/vristo/icon/icon-users.vue';

const props = defineProps({
    course: {
        type: Object,
        default: () => ({}),
    },
    landing: {
        type: Object,
        default: () => ({}),
    },
    teachers: {
        type: Array,
        default: () => ([]),
    },
});

const getInitialStaffData = () => {
    const data = props.landing.staff_section;
    if (data && typeof data === 'string') {
        try {
            return JSON.parse(data);
        } catch (e) {
            return null;
        }
    }
    return data || null;
};

const initialData = getInitialStaffData();

const buildTeachersArray = () => {
    if (!initialData?.teachers || initialData.teachers.length === 0) {
        return [];
    }
    return initialData.teachers.map(t => ({
        teacher_id: t.teacher_id,
        selected: t.selected || false,
    }));
};

const formStaff = useForm({
    name: initialData?.name || 'Nuestro Staff',
    title: initialData?.title || '',
    description: initialData?.description || '',
    teachers: buildTeachersArray(),
});

const availableTeachers = computed(() => {
    return props.teachers.map(teacher => {
        const selected = formStaff.teachers.some(
            t => t.teacher_id === teacher.id && t.selected
        );

        return {
            teacher_id: teacher.id,
            person_id: teacher.person_id,
            full_name: teacher.person?.formatted_name || teacher.person?.name || 'Sin nombre',
            photo: teacher.person?.image || null,
            occupation: teacher.person?.ocupacion || 'Instructor',
            selected: selected,
        };
    });
});

const toggleTeacher = (teacherId) => {
    const formIndex = formStaff.teachers.findIndex(t => t.teacher_id === teacherId);

    if (formIndex >= 0) {
        formStaff.teachers[formIndex].selected = !formStaff.teachers[formIndex].selected;
    } else {
        formStaff.teachers.push({ teacher_id: teacherId, selected: true });
    }
};

const isTeacherSelected = (teacherId) => {
    const teacher = formStaff.teachers.find(t => t.teacher_id === teacherId);
    return teacher ? teacher.selected : false;
};

const saveStaffSettings = () => {
    formStaff.put(route('aca_courses_landing_update_staff', props.course.id),{
        errorBag: 'saveStaffSettings',
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: 'Sección Nuestro Staff guardada correctamente',
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
            <svg class="w-5 h-5 mr-2 text-blue-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512">
                <path fill="currentColor" d="M320 16a104 104 0 1 1 0 208 104 104 0 1 1 0-208zM96 88a72 72 0 1 1 0 144 72 72 0 1 1 0-144zM0 416c0-70.7 57.3-128 128-128 12.8 0 25.2 1.9 36.9 5.4-32.9 36.8-52.9 85.4-52.9 138.6l0 16c0 11.4 2.4 22.2 6.7 32L32 480c-17.7 0-32-14.3-32-32l0-32zm521.3 64c4.3-9.8 6.7-20.6 6.7-32l0-16c0-53.2-20-101.8-52.9-138.6 11.7-3.5 24.1-5.4 36.9-5.4 70.7 0 128 57.3 128 128l0 32c0 17.7-14.3 32-32 32l-86.7 0zM472 160a72 72 0 1 1 144 0 72 72 0 1 1 -144 0zM160 432c0-88.4 71.6-160 160-160s160 71.6 160 160l0 16c0 17.7-14.3 32-32 32l-256 0c-17.7 0-32-14.3-32-32l0-16z"/>
            </svg>
            Sección Nuestro Staff
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Configura la información del staff que se mostrará en la landing page.
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
                        v-model="formStaff.name"
                        class="form-input"
                        placeholder="Nuestro Staff"
                    >
                    <InputError :message="formStaff.errors.name" class="mt-2" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Título
                    </label>
                    <input
                        type="text"
                        v-model="formStaff.title"
                        class="form-input"
                        placeholder="Título de la sección"
                    >
                    <InputError :message="formStaff.errors.title" class="mt-2" />
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Descripción
                    </label>
                    <textarea
                        v-model="formStaff.description"
                        rows="3"
                        class="form-textarea"
                        placeholder="Descripción de la sección..."
                    ></textarea>
                    <InputError :message="formStaff.errors.description" class="mt-2" />
                </div>
            </div>
        </div>

        <!-- Docentes Disponibles -->
        <div class="">
            <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4 flex items-center">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-green-200 text-green-600 dark:bg-green-900/50 dark:text-green-400 mr-2">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                        <path fill="currentColor" d="M64 64C28.7 64 0 92.7 0 128L0 384c0 35.3 28.7 64 64 64l384 0c35.3 0 64-28.7 64-64l0-256c0-35.3-28.7-64-64-64L64 64zm96 256a32 32 0 1 1 -64 0 32 32 0 1 1 64 0zm-32-96a32 32 0 1 1 0-64 32 32 0 1 1 0 64zm120-56l144 0c13.3 0 24 10.7 24 24s-10.7 24-24 24l-144 0c-13.3 0-24-10.7-24-24s10.7-24 24-24zm0 128l144 0c13.3 0 24 10.7 24 24s-10.7 24-24 24l-144 0c-13.3 0-24-10.7-24-24s10.7-24 24-24z"/>
                    </svg>
                </span>
                Seleccionar Docentes
            </h4>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                Los docentes seleccionados se mostrarán en la landing del curso
            </p>

            <div v-if="availableTeachers.length === 0" class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                <p class="text-sm text-yellow-800 dark:text-yellow-200">
                    No hay docentes disponibles en el sistema. Agrega docentes en la sección de Docentes.
                </p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="teacher in availableTeachers"
                    :key="teacher.teacher_id"
                    class="p-4 rounded-lg border-2 transition-all duration-200"
                    :class="isTeacherSelected(teacher.teacher_id)
                        ? 'border-green-500 bg-green-50 dark:bg-green-900/20'
                        : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800'"
                >
                    <div class="flex items-start gap-3">
                        <div class="flex items-center h-5">
                            <input
                                type="checkbox"
                                :checked="isTeacherSelected(teacher.teacher_id)"
                                @change="toggleTeacher(teacher.teacher_id)"
                                class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700"
                            >
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3">
                                <div class="shrink-0">
                                    <img
                                        v-if="teacher.photo"
                                        :src="'/storage/' + teacher.photo"
                                        :alt="teacher.full_name"
                                        class="h-12 w-12 rounded-full object-cover"
                                    >
                                    <div v-else class="h-12 w-12 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                        <i class="fa fa-user text-gray-400"></i>
                                    </div>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ teacher.full_name }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                        {{ teacher.occupation }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex justify-end">
            <button
                @click="saveStaffSettings"
                :disabled="formStaff.processing"
                :class="{ 'opacity-25': formStaff.processing }"
                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <IconLoader v-if="formStaff.processing" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" />
                <IconSave v-else class="mr-2" />
                {{ formStaff.processing ? 'Guardando...' : 'Guardar Sección Nuestro Staff' }}
            </button>
        </div>
    </div>
</template>
