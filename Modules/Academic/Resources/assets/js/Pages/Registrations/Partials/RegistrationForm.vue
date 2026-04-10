<script setup>
import { ref, onMounted, reactive } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Select } from 'ant-design-vue';

import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Swal2 from 'sweetalert2';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';
import axios from 'axios';
import IconX from '@/Components/vristo/icon/icon-x.vue';

const props = defineProps({
    student:{
        type: Object,
        default : () => ({})
    },
    courses:{
        type: Object,
        default : () => ({})
    },
    registrations:{
        type: Object,
        default : () => ({})
    },
    faTrashAlt:{
        type: Object,
        default : () => ({})
    },
    faXmark:{
        type: Object,
        default : () => ({})
    }
});

const dataCourses = ref([]);

onMounted(() => {
    dataCourses.value = props.courses.map((obj) => ({
        value: obj.id,
        label: obj.description,
    }));
});

const form = useForm({
    course_id: null,
    student_id: props.student.id
})

// Estado de procesamiento para cada registro
const processingStates = reactive({});

const saveRegistration = () => {
    form.post(route('aca_students_registrations_store'), {
        errorBag: 'saveRegistration',
        preserveScroll: true,
        onSuccess: () => {
            Swal2.fire({
                title: 'Enhorabuena',
                text: 'Se registró correctamente',
                icon: 'success',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
            form.reset()
        },
    });
}

const filterOption = (input, option) => {
  return option.label.toLowerCase().indexOf(input.toLowerCase()) >= 0;
};

const destroyCertificate = (id) => {
    Swal2.fire({
        title: '¿Estas seguro?',
        text: "¡No podrás revertir esto!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: '¡Sí, Eliminar!',
        cancelButtonText: 'Cancelar',
        showLoaderOnConfirm: true,
        padding: '2em',
        customClass: 'sweet-alerts',
        preConfirm: () => {
            return axios.delete(route('aca_students_registrations_destroy', id)).then((res) => {
                if (!res.data.success) {
                    Swal2.showValidationMessage(res.data.message)
                }
                return res
            });
        },
        allowOutsideClick: () => !Swal2.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            Swal2.fire({
                title: 'Enhorabuena',
                text: 'Se Eliminó correctamente',
                icon: 'success',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
            router.visit(route('aca_students_registrations_create', props.student.id), { replace: true, method: 'get' });
        }
    });
};

const baseUrl = assetUrl;

const getImage = (path) => {
    return baseUrl + 'storage/'+ path;
}

const updateRegistration = (registration) => {
    processingStates[registration.id] = true;

    axios.put(route('aca_students_registrations_update', registration.id), {
        date_start: registration.date_start,
        date_end: registration.date_end,
        unlimited: registration.unlimited ? true : false,
    })
    .then((response) => {
        if (response.data.success) {
            Swal2.fire({
                title: 'Enhorabuena',
                text: 'Se actualizó correctamente',
                icon: 'success',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        } else {
            Swal2.fire({
                title: 'Error',
                text: response.data.message || 'Error al actualizar',
                icon: 'error',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        }
    })
    .catch((error) => {
        Swal2.fire({
            title: 'Error',
            text: error.response?.data?.message || 'Error al actualizar',
            icon: 'error',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    })
    .finally(() => {
        processingStates[registration.id] = false;
    });
};

</script>
<template>
    <div class="panel">
        <form @submit.prevent="saveRegistration">
            <div class="space-y-6 mb-4">
                <div class="mb-2">
                    <InputLabel for="course_date" value="Curso *" />
                    <Select
                        show-search
                        v-model:value="form.course_id"
                        class="w-full mb-2"
                        placeholder="Seleccionar"
                        :options="dataCourses"
                        :filter-option="filterOption"
                    />
                    <InputError :message="form.errors.course_id" class="mt-1" />
                    <InputError :message="form.errors.student_id" class="mt-1" />
                </div>
            </div>


            <div class="flex items-center justify-end">
                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    <svg v-show="form.processing" aria-hidden="true" role="status" class="inline w-4 h-4 mr-3 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                        <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                    </svg>
                    Guardar
                </PrimaryButton>
            </div>
        </form>
    </div>
    <div
        v-for="registration in registrations"
        class="panel relative"
    >
        <div class="flex items-start gap-4">
            <img class="w-20 h-20" :src="getImage(registration.course.image)" alt="">
            <div class="flex-1 font-medium dark:text-primary-200">
                <div class="font-bold mb-2">{{ registration.course.description }}</div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <InputLabel for="date_start" value="Fecha Inicio *" class="text-xs" />
                        <input
                            type="date"
                            v-model="registration.date_start"
                            class="form-input w-full text-sm"
                        />
                    </div>
                    <div>
                        <InputLabel for="date_end" value="Fecha Fin" class="text-xs" />
                        <div class="relative">
                            <input
                                type="date"
                                v-model="registration.date_end"
                                class="form-input w-full text-sm"
                                :class="registration.date_end ? 'pr-8' : ''"
                            />
                            <button
                                v-if="registration.date_end"
                                type="button"
                                @click="registration.date_end = null; registration.unlimited = false"
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                            >
                                <IconX class="w-5 h-5" />
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 pt-6">
                        <input
                            type="checkbox"
                            id="'unlimited-' + registration.id"
                            v-model="registration.unlimited"
                            :disabled="!!registration.date_end"
                            class="form-checkbox rounded text-primary-600"
                        />
                        <InputLabel :for="'unlimited-' + registration.id" value="Sin límite" class="text-sm mb-0" />
                    </div>
                </div>

                <div class="mt-3 flex justify-end gap-2">
                    <button
                        @click="updateRegistration(registration)"
                        :class="{ 'opacity-25': processingStates[registration.id] }"
                        :disabled="processingStates[registration.id]"
                        type="button"
                        class="btn btn-primary"
                    >
                        <IconLoader class="w-4 h-4 mr-2" v-if="processingStates[registration.id]" />
                        {{ processingStates[registration.id] ? 'Guardando...' : 'Guardar Cambios' }}
                    </button>
                    <button
                        @click="destroyCertificate(registration.id)"
                        type="button"
                        class="btn btn-danger"
                    >
                        <font-awesome-icon :icon="faTrashAlt" class="w-4 h-4 mr-2" />
                        Eliminar Matrícula
                    </button>
                </div>
            </div>
        </div>
    </div>

</template>
