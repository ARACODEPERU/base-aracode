<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Keypad from '@/Components/Keypad.vue';
import DoctorProfessionalFields from '@/Components/Health/DoctorProfessionalFields.vue';
import CropperImage from '@/Components/CropperImage.vue';
import Swal2 from 'sweetalert2';
import { ref, watch } from 'vue';

const props = defineProps({
    identityDocumentTypes: {
        type: Object,
        default: () => ({}),
    },
    ubigeo: {
        type: Object,
        default: () => ({})
    },
    doctor: {
        type: Object,
        default: () => ({})
    }
});

const baseUrl = assetUrl;

const getImage = (image_preview) => {
    if (!image_preview || String(image_preview).startsWith('data:') || String(image_preview).startsWith('http')) {
        return image_preview;
    }

    return assetUrl + 'storage/'+ image_preview;
    
}

const form = useForm({
    id: props.doctor.id,
    document_type_id: props.doctor.document_type_id,
    number: props.doctor.number,
    telephone: props.doctor.telephone,
    email: props.doctor.email,
    image: null,
    image_preview: props.doctor.image ?? null,
    address: props.doctor.address,
    ubigeo: props.doctor.ubigeo,
    birthdate: props.doctor.birthdate,
    names: props.doctor.names,
    father_lastname: props.doctor.father_lastname,
    mother_lastname: props.doctor.mother_lastname,
    ubigeo_description: props.doctor.city,
    gender: props.doctor.gender,
    colegiatura: props.doctor.colegiatura,
    profession: props.doctor.profession || 'Médico cirujano',
    specialty: props.doctor.specialty,
    attention_service_type: props.doctor.attention_service_type || 'general',
    user_id: props.doctor.user_id,
    generate_user: false,
    user_email: null,
    user_password: null,
});

const showUserAccountModal = ref(false);

const openUserAccountModal = () => {
    form.user_email = form.user_email || form.email;
    form.user_password = form.user_password || form.number;
    showUserAccountModal.value = true;
};

const confirmUserAccount = () => {
    form.generate_user = true;
    showUserAccountModal.value = false;
};

const cancelUserAccount = () => {
    form.generate_user = false;
    form.user_email = null;
    form.user_password = null;
    showUserAccountModal.value = false;
};

const updatePatient = () => {
    form.post(route('heal_doctors_update'), {
        forceFormData: true,
        errorBag: 'updatePatient',
        preserveScroll: true,
        onSuccess: () => {
            Swal2.fire({
                title: 'Enhorabuena',
                text: 'Se Actualizó correctamente',
                icon: 'success',
            });
            //form.reset()
        },
    });
}

const searchUbigeos = ref([]);

const filterCities = () => {
    if (form.ubigeo_description.trim() === '') {
        searchUbigeos.value = [];
        return;
    }

    searchUbigeos.value = props.ubigeo.filter(row =>
        row.district_name.toLowerCase().includes(form.ubigeo_description.toLowerCase())
    );
}
const selectCity = (item) => {
    form.ubigeo_description = item.department_name+'-'+item.province_name+'-'+item.district_name;
    form.ubigeo = item.district_id;
    searchUbigeos.value = []; // Limpiar la lista de búsqueda después de seleccionar una ciudad
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

const cropImageAndSave = (res) => {
    form.image = res;
    form.image_preview = res;
};

</script>

<template>
    <FormSection @submitted="updatePatient" class="">
        <template #title>
            Paciente Detalles
        </template>

        <template #description>
            Crear editar Paciente, Los campos con * son obligatorios
        </template>

        <template #form>
            <div class="col-span-6 sm:col-span-2 ">
                <InputLabel for="document_type_id" value="Tipo *" />
                <select v-model="form.document_type_id" id="document_type_id" class="mt-1 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
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
                <div class="grid grid-cols-1 gap-4 md:grid-cols-[auto,1fr]">
                    <div v-show="form.image_preview" class="shrink-0">
                        <img id='preview_img' class="h-16 w-16 object-cover rounded-full" :src="getImage(form.image_preview)" alt="Current profile photo" />
                    </div>
                    <CropperImage ref="cropper" :aspect-ratio="1" @onCrop="cropImageAndSave" />
                </div>
            </div>
            <div class="col-span-6 sm:col-span-3 ">
                <InputLabel for="names" value="Nombres *" />
                <TextInput
                    id="names"
                    v-model="form.names"
                    type="text"
                    class="block w-full mt-1"
                    
                />
                <InputError :message="form.errors.names" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-3 ">
                <InputLabel for="father_lastname" value="Apellido Paterno *" />
                <TextInput
                    id="father_lastname"
                    v-model="form.father_lastname"
                    type="text"
                    class="block w-full mt-1"
                    
                />
                <InputError :message="form.errors.father_lastname" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-3 ">
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
            <div class="col-span-6 sm:col-span-6 ">
                <InputLabel for="ubigeo" value="Ciudad *" />
                <div class="relative">
                    <TextInput 
                    v-model="form.ubigeo_description" 
                    @input="filterCities"
                    placeholder="Buscar Distrito"
                    type="text" 
                    class="block w-full mt-1" />
                    <ul v-if="searchUbigeos && searchUbigeos.length > 0" style="max-height: 200px; overflow-y: auto;" class="list-disc list-inside absolute z-50 w-full bg-white border border-gray-300 rounded-md mt-1">
                        <li v-for="item in searchUbigeos" :key="item.id" class="px-4 cursor-pointer hover:bg-gray-100" @click="selectCity(item)">
                            {{ item.department_name+'-'+item.province_name+'-'+item.district_name }}
                        </li>
                    </ul>
                </div>
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
            <div v-if="!form.user_id" class="col-span-6">
                <div class="flex flex-wrap items-center gap-3 rounded border border-slate-200 p-3 dark:border-slate-700">
                    <button type="button" class="btn btn-outline-primary" @click="openUserAccountModal">
                        Generar cuenta
                    </button>
                    <div v-if="form.generate_user" class="text-sm text-success">
                        Se creará una cuenta para {{ form.user_email }} al guardar el doctor.
                    </div>
                    <InputError :message="form.errors.user_email || form.errors.user_password" class="mt-1" />
                </div>
            </div>
            <div v-else class="col-span-6">
                <div class="rounded border border-success/30 bg-success/10 p-3 text-sm text-success">
                    Este doctor ya tiene una cuenta vinculada.
                </div>
            </div>
            <DoctorProfessionalFields :form="form" />
            <div class="col-span-6 sm:col-span-6 space-x-2">
                <label class="inline-flex">
                    <input type="radio" v-model="form.gender" value="M" name="square_radio" class="form-radio text-success rounded-none" />
                    <span>Masculino</span>
                </label>
                <label class="inline-flex">
                    <input type="radio" v-model="form.gender" value="F" name="square_radio" class="form-radio rounded-none" />
                    <span>Femenino</span>
                </label>
            </div>
        </template>

        <template #actions>
            <progress v-if="form.progress" :value="form.progress.percentage" max="100" class="mr-2">
                {{ form.progress.percentage }}%
            </progress>
            <Keypad>
                <template #botones>
                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        <svg v-show="form.processing" aria-hidden="true" role="status" class="inline w-4 h-4 mr-3 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                            <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                        </svg>
                        Actualizar
                    </PrimaryButton>
                    <Link :href="route('heal_doctors_list')"  class="ml-2 inline-block px-6 py-2.5 bg-green-500 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-green-600 hover:shadow-lg focus:bg-green-600 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-green-700 active:shadow-lg transition duration-150 ease-in-out">Ir al Listado</Link>
                </template>
            </Keypad>
        </template>
    </FormSection>

    <div v-if="showUserAccountModal" class="fixed inset-0 z-[999] flex items-center justify-center bg-black/60 px-4">
        <div class="w-full max-w-md rounded bg-white p-5 shadow-lg dark:bg-slate-900">
            <h3 class="text-lg font-semibold">Generar cuenta</h3>
            <div class="mt-4 space-y-4">
                <div>
                    <InputLabel value="Email" />
                    <TextInput v-model="form.user_email" type="email" class="mt-1 block w-full" />
                </div>
                <div>
                    <InputLabel value="Contraseña" />
                    <TextInput v-model="form.user_password" type="text" class="mt-1 block w-full" />
                </div>
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" class="btn btn-outline-secondary" @click="cancelUserAccount">Cancelar</button>
                <button type="button" class="btn btn-primary" @click="confirmUserAccount">Aceptar</button>
            </div>
        </div>
    </div>
</template>
