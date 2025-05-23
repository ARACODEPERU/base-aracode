<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Keypad from '@/Components/Keypad.vue';
import Swal2 from 'sweetalert2';
import { ref, watch } from 'vue';
import Multiselect from '@suadelabs/vue3-multiselect';
import '@suadelabs/vue3-multiselect/dist/vue3-multiselect.css';

const props = defineProps({
    identityDocumentTypes: {
        type: Object,
        default: () => ({}),
    },
    ubigeo: {
        type: Object,
        default: () => ({})
    },
    student: {
        type: Object,
        default: () => ({})
    },
    industrias: {
        type: Object,
        default: () => ({})
    },
    professions: {
        type: Object,
        default: () => ({})
    },
    occupations: {
        type: Object,
        default: () => ({})
    }
});

const baseUrl = assetUrl;

const getImage = (path) => {
    return baseUrl + 'storage/'+ path;
}

const ubigeoSelected = ref({
    district_id: props.student.ubigeo,
    ubigeo_description: props.student.ubigeo_description
})

const form = useForm({
    id: props.student.id,
    student_id: props.student.student_id,
    document_type_id: props.student.document_type_id,
    number: props.student.number,
    telephone: props.student.telephone,
    email: props.student.email,
    image: props.student.image,
    image_preview: props.student.image ? getImage(props.student.image) : null,
    address: props.student.address,
    ubigeo: props.student.ubigeo,
    birthdate: props.student.birthdate,
    names: props.student.names,
    father_lastname: props.student.father_lastname,
    mother_lastname: props.student.mother_lastname,
    ubigeo_description: props.student.ubigeo_description,
    gender: props.student.gender,
    industry_id: {
        id: props.student.industry_id,
        description: props.student.industry
    },
    profession_id: {
        id: props.student.profession_id,
        description: props.student.profession
    },
    occupation_id: {
        id: props.student.occupation_id,
        description: props.student.ocupacion
    }
});

const createPatient = () => {
    form.post(route('aca_students_update'), {
        forceFormData: true,
        errorBag: 'createPatient',
        preserveScroll: true,
        onSuccess: () => {
            Swal2.fire({
                title: 'Enhorabuena',
                text: 'Se Actualizó correctamente',
                icon: 'success',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        },
    });
}


const selectCity = () => {
    form.ubigeo_description = ubigeoSelected.value.ubigeo_description;
    form.ubigeo = ubigeoSelected.value.district_id;
}

const loadFile = (event) => {
    const input = event.target;
    const file = input.files[0];
    const type = file.type;

    // Obtén una referencia al elemento de imagen a través de Vue.js
    const imagePreview = document.getElementById('preview_img');

    // Crea un objeto de archivo de imagen y asigna la URL al formulario
    const imageFile = URL.createObjectURL(event.target.files[0]);
    form.image_preview = imageFile;
    // Asigna el archivo a form.image
    form.image = file;
    // Libera la URL del objeto una vez que la imagen se haya cargado
    imagePreview.onload = function() {
        URL.revokeObjectURL(imageFile); // libera memoria
    }
};

</script>

<template>
    <FormSection @submitted="createPatient" class="">
        <template #title>
            Estudiante Detalles
        </template>

        <template #description>
            Crear nuevo Estudiante, Los campos con * son obligatorios
        </template>

        <template #form>
            <div class="col-span-6 sm:col-span-2 ">
                <InputLabel for="document_type_id" value="Tipo *" />
                <select v-model="form.document_type_id" id="document_type_id" class="form-select">
                    <option value="" selected>Seleccionar</option>
                    <option v-for="(identityDocumentType) in identityDocumentTypes" :value="identityDocumentType.id">{{ identityDocumentType.description }}</option>
                </select>
                <InputError :message="form.errors.document_type_id" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-2 ">
                <InputLabel for="number" value="Número *" />
                <TextInput
                    id="number"
                    v-model="form.number"
                    type="text"
                    class="block w-full mt-1"

                />
                <InputError :message="form.errors.number" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-2 ">
                <InputLabel for="birthdate" value="Fecha de nacimiento *" />
                <TextInput
                    id="birthdate"
                    v-model="form.birthdate"
                    type="date"
                    class="block w-full mt-1"

                />
                <InputError :message="form.errors.birthdate" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6 ">
                <div class="flex items-center space-x-6">
                    <div v-show="form.image_preview" class="shrink-0">
                        <img id='preview_img' class="h-16 w-16 object-cover rounded-full" :src="form.image_preview" alt="Current profile photo" />
                    </div>
                    <label class="block ml-1">
                        <input @change="loadFile" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400" id="file_input" type="file">
                    </label>
                </div>
            </div>
            <div class="col-span-6 sm:col-span-2 ">
                <InputLabel for="names" value="Nombres *" />
                <TextInput
                    id="names"
                    v-model="form.names"
                    type="text"
                    class="block w-full mt-1"

                />
                <InputError :message="form.errors.names" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-2">
                <InputLabel for="father_lastname" value="Apellido Paterno *" />
                <TextInput
                    id="father_lastname"
                    v-model="form.father_lastname"
                    type="text"
                    class="block w-full mt-1"

                />
                <InputError :message="form.errors.father_lastname" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-2">
                <InputLabel for="mother_lastname" value="Apellido Materno *" />
                <TextInput
                    id="mother_lastname"
                    v-model="form.mother_lastname"
                    type="text"
                    class="block w-full mt-1"

                />
                <InputError :message="form.errors.mother_lastname" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-3 ">
                <InputLabel for="address" value="Dirección *" />
                <TextInput
                    id="address"
                    v-model="form.address"
                    type="text"
                    class="block w-full mt-1"

                />
                <InputError :message="form.errors.address" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-3">
                <InputLabel for="ubigeo" value="Ciudad *" />
                <multiselect
                    id="industry_id"
                    :model-value="ubigeoSelected"
                    v-model="ubigeoSelected"
                    :options="ubigeo"
                    class="custom-multiselect"
                    :searchable="true"
                    placeholder="Buscar"
                    selected-label="seleccionado"
                    select-label="Elegir"
                    deselect-label="Quitar"
                    label="ubigeo_description"
                    track-by="district_id"
                    @update:model-value="selectCity"
                ></multiselect>
                <InputError :message="form.errors.ubigeo" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-3 ">
                <InputLabel for="telephone" value="Teléfono *" />
                <TextInput
                    id="telephone"
                    v-model="form.telephone"
                    type="text"
                    class="block w-full mt-1"

                />
                <InputError :message="form.errors.telephone" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-3">
                <InputLabel for="email" value="Email *" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="text"
                    class="block w-full mt-1"

                />
                <InputError :message="form.errors.email" class="mt-2" />
            </div>
            <div class="col-span-2">
                <InputLabel for="industry_id" value="Industria" />
                <multiselect
                    id="industry_id"
                    :model-value="form.industry_id"
                    v-model="form.industry_id"
                    :options="industrias"
                    class="custom-multiselect"
                    :searchable="true"
                    placeholder="Buscar"
                    selected-label="seleccionado"
                    select-label="Elegir"
                    deselect-label="Quitar"
                    label="description"
                    track-by="id"
                ></multiselect>
                <InputError :message="form.errors.industry_id" class="mt-1" />
            </div>
            <div class="col-span-2">
                <InputLabel for="profession_id" value="Profesión" />
                <multiselect
                    id="profession_id"
                    :model-value="form.profession_id"
                    v-model="form.profession_id"
                    :options="professions"
                    class="custom-multiselect"
                    :searchable="true"
                    placeholder="Buscar"
                    selected-label="seleccionado"
                    select-label="Elegir"
                    deselect-label="Quitar"
                    label="description"
                    track-by="id"
                ></multiselect>
                <InputError :message="form.errors.profession_id" class="mt-1" />
            </div>
            <div class="col-span-2">
                <InputLabel for="occupation_id" value="Cargo/ocupacion" />
                <multiselect
                    id="occupation_id"
                    :model-value="form.occupation_id"
                    v-model="form.occupation_id"
                    :options="occupations"
                    class="custom-multiselect"
                    :searchable="true"
                    placeholder="Buscar"
                    selected-label="seleccionado"
                    select-label="Elegir"
                    deselect-label="Quitar"
                    label="description"
                    track-by="id"
                ></multiselect>
                <InputError :message="form.errors.occupation_id" class="mt-1" />
            </div>
            <div class="col-span-6 sm:col-span-3">
                <InputLabel for="gender" value="Genero *" />
                <div class="space-x-4">
                    <label class="inline-flex">
                        <input v-model="form.gender" type="radio" value="M" name="square_radio_g" class="form-radio rounded-none" checked />
                        <span>Masculino</span>
                    </label>
                    <label class="inline-flex">
                        <input v-model="form.gender" type="radio" value="F" name="square_radio_g" class="form-radio text-success rounded-none" />
                        <span>Femenino</span>
                    </label>
                </div>
                <InputError :message="form.errors.gender" class="mt-2" />
            </div>
        </template>

        <template #actions>

            <Keypad>
                <template #botones>
                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        <svg v-show="form.processing" aria-hidden="true" role="status" class="inline w-4 h-4 mr-3 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                            <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                        </svg>
                        Actualizar
                    </PrimaryButton>
                    <Link :href="route('aca_students_list')"  class="ml-2 inline-block px-6 py-2.5 bg-green-500 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-green-600 hover:shadow-lg focus:bg-green-600 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-green-700 active:shadow-lg transition duration-150 ease-in-out">Ir al Listado</Link>
                </template>
            </Keypad>
        </template>
    </FormSection>
</template>
