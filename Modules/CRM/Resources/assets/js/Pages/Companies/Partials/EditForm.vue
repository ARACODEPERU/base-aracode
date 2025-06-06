<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Keypad from '@/Components/Keypad.vue';
import Swal2 from 'sweetalert2';
import { ref, watch, onMounted } from 'vue';
import Multiselect from "@suadelabs/vue3-multiselect";
import "@suadelabs/vue3-multiselect/dist/vue3-multiselect.css";

const props = defineProps({
    identityDocumentTypes: {
        type: Object,
        default: () => ({}),
    },
    ubigeo: {
        type: Object,
        default: () => ({})
    },
    industry: {
        type: Object,
        default: () => ({})
    },
    empresa: {
        type: Object,
        default: () => ({})
    }
});

const baseUrl = assetUrl;

const getImage = (path) => {
    return baseUrl + 'storage/'+ path;
}

const form = useForm({
    id: props.empresa.id,
    document_type_id: 6,
    number: props.empresa.number,
    telephone: props.empresa.telephone,
    email: props.empresa.email,
    image: null,
    image_preview: props.empresa.image ? getImage(props.empresa.image) : null,
    address: props.empresa.address,
    ubigeo: props.empresa.ubigeo,
    short_name: props.empresa.short_name,
    names: props.empresa.names,
    full_name: props.empresa.full_name,
    industry: {
        id: props.empresa.industry_id,
        description: props.empresa.industry,
        icon: null
    },
    ubigeo_description: props.empresa.city,
    contact_telephone: props.empresa.contact_telephone,
    contact_name: props.empresa.contact_name,
    contact_email: props.empresa.contact_email
});

const updateCompany = () => {
    form.post(route('crm_companies_update'), {
        forceFormData: true,
        errorBag: 'updateCompany',
        preserveScroll: true,
        onSuccess: () => {
            Swal2.fire({
                title: 'Enhorabuena',
                text: 'Se Actualizo correctamente',
                icon: 'success',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
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


</script>

<template>
    <FormSection @submitted="updateCompany" enctype="multipart/form-data">
        <template #title>
            Empresa Detalles
        </template>

        <template #description>
            Crear editar empresa, Los campos con * son obligatorios
        </template>

        <template #form>
            <div class="col-span-6 sm:col-span-3 ">
                <InputLabel for="short_name" value="Nombre comercial*" />
                <TextInput
                    id="short_name"
                    v-model="form.short_name"
                    type="text"
                    class="block w-full mt-1"
                    
                />
                <InputError :message="form.errors.short_name" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-3 ">
                <InputLabel for="full_name" value="Nombre completo *" />
                <TextInput
                    id="full_name"
                    v-model="form.full_name"
                    type="text"
                    class="block w-full mt-1"
                    
                />
                <InputError :message="form.errors.full_name" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-3 ">
                <InputLabel for="number" value="RUC *" />
                <TextInput
                    id="number"
                    v-model="form.number"
                    type="text"
                    class="block w-full mt-1"
                    
                />
                <InputError :message="form.errors.number" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-3">
                <InputLabel for="industry" value="Industria*" />
                <multiselect
                    v-model="form.industry"
                    :options="industry"
                    class="custom-multiselect"
                    :searchable="true"
                    placeholder="Seleccione una opción"
                    selected-label="seleccionado"
                    select-label="Elegir"
                    deselect-label="Quitar"
                    label="description"
                    track-by="id"
                ></multiselect>
                <InputError :message="form.errors.industry" class="mt-2" />
            </div>
            <div class="col-span-6">
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
                    <ul v-if="searchUbigeos && searchUbigeos.length > 0" style="max-height: 200px; overflow-y: auto;" class="text-sm mt-2 absolute z-50 w-full font-medium text-gray-900 bg-white border border-gray-200 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <li v-for="item in searchUbigeos" 
                            :key="item.id" 
                            @click="selectCity(item)"
                            class="w-full px-4 py-2 font-medium text-left rtl:text-right border-b border-gray-200 cursor-pointer hover:bg-gray-100 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:border-gray-600 dark:hover:bg-gray-600 dark:hover:text-white dark:focus:ring-gray-500 dark:focus:text-white"
                        >
                            {{ item.department_name+'-'+item.province_name+'-'+item.district_name }}
                        </li>
                    </ul>
                </div>
                <InputError :message="form.errors.ubigeo" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-3 ">
                <InputLabel for="telephone" value="Teléfono empresa*" />
                <TextInput
                    id="telephone"
                    v-model="form.telephone"
                    type="text"
                    class="block w-full mt-1"
                    
                />
                <InputError :message="form.errors.telephone" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-3">
                <InputLabel for="email" value="Email empresa*" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="text"
                    class="block w-full mt-1"
                    
                />
                <InputError :message="form.errors.email" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-3 ">
                <InputLabel for="contact_name" value="Nombre del contacto*" />
                <TextInput
                    id="contact_name"
                    v-model="form.contact_name"
                    type="text"
                    class="block w-full mt-1"
                    
                />
                <InputError :message="form.errors.contact_name" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-3">
                <InputLabel for="contact_email" value="Email del contacto*" />
                <TextInput
                    id="contact_email"
                    v-model="form.contact_email"
                    type="text"
                    class="block w-full mt-1"
                    
                />
                <InputError :message="form.errors.contact_email" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-3">
                <InputLabel for="contact_telephone" value="Telefono del contacto*" />
                <TextInput
                    id="contact_telephone"
                    v-model="form.contact_telephone"
                    type="text"
                    class="block w-full mt-1"
                />
                <InputError :message="form.errors.contact_telephone" class="mt-2" />
            </div>
            
            <div class="col-span-6 sm:col-span-6 ">
                <div class="flex items-center space-x-6">
                    <div v-show="form.image_preview" class="shrink-0">
                        <img id='preview_img' class="h-16 w-16 object-cover rounded-full" :src="form.image_preview" alt="Current profile photo" />
                    </div>
                    <label class="block ml-1">
                        <input @change="loadFile" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400" 
                        id="file_input" 
                        type="file"
                        >
                    </label>
                </div>
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
                    <Link :href="route('crm_companies_list')"  class="ml-2 inline-block px-6 py-2.5 bg-green-500 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-green-600 hover:shadow-lg focus:bg-green-600 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-green-700 active:shadow-lg transition duration-150 ease-in-out">Ir al Listado</Link>
                </template>
            </Keypad>
        </template>
    </FormSection>
</template>