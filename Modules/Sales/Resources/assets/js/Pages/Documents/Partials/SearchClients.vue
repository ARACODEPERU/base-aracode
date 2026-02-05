<script setup>
    import { useForm } from '@inertiajs/vue3';
    import ModalLargeX from '@/Components/ModalLargeX.vue';
    import { ref, watch } from 'vue';
    import InputError from '@/Components/InputError.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import GreenButton from '@/Components/GreenButton.vue';
    import RedButton from '@/Components/RedButton.vue';
    import TextInput from '@/Components/TextInput.vue';
    import { faShareFromSquare } from "@fortawesome/free-solid-svg-icons";
    import Swal2 from 'sweetalert2';
    import Multiselect from "@suadelabs/vue3-multiselect";
    import "@suadelabs/vue3-multiselect/dist/vue3-multiselect.css";
    import iconDatabaseSearch from '@/Components/vristo/icon/icon-database-search.vue';
    import iconCompany from '@/Components/vristo/icon/icon-company.vue';
    import iconLoader from '@/Components/vristo/icon/icon-loader.vue';

    const props = defineProps({
        clientDefault: {
            type: Object,
            default: () => ({})
        },
        documentTypes: {
            type: Object,
            default: () => ({})
        },
        display:{
            type: Boolean,
            default: () => ({})
        },
        closeModalClient: {
            type: Function,
            default: () => ({})
        },
        saleDocumentTypes: {
            type: null,
            default: () => ({})
        },
        ubigeo: {
            type: Object,
            default: () => ({})
        },
    })

    const form = useForm({
        id: '',
        document_type: 1,
        number: '',
        telephone: '',
        full_name: '',
        email: '',
        address: '',
        ubigeo: '',
        ubigeo_description: '',
        presentations: false,
        is_client: true,
        estado: null,
        condicion: null,
        searchBy: 1
    });

    const disabledBtnSelect = ref(true);
    const persona = ref({});
    const searchResults = ref([]); // resultados múltiples

    const modalNewSearchClient = () => {
        form.clearErrors();
        searchResults.value = [];
        axios.post(route('search_person_number'), form).then((res) => {
            if (form.searchBy == 1) {
                if (res.data.status) {
                    fillForm(res.data.person, res.data.ubigeo);
                } else {
                    showNoResults(res.data);
                }
                return;
            }

            // Si búsqueda por NOMBRES (2)
            if (form.searchBy == 2) {

                // Si vienen múltiples
                if (Array.isArray(res.data.person) && res.data.person.length > 1) {
                    searchResults.value = res.data.person;
                    disabledBtnSelect.value = true;
                    return;
                }

                // Si solo viene uno
                if (Array.isArray(res.data.person) && res.data.person.length === 1) {
                    fillForm(res.data.person[0], res.data.ubigeo);
                    return;
                }

                // Si no existe nada
                showNoResults(res.data);
            }

        }).catch(error => {
            setErrorFormSearch(error.response.data.errors);
            form.processing = false;
        });
    }

    // Rellenar formulario con el item seleccionado
    const fillForm = (person, ubigeo) => {
        form.id = person.id;
        form.document_type = person.document_type_id;
        form.number = person.number;
        form.telephone = person.telephone;
        form.full_name = person.full_name;
        form.email = person.email;
        form.address = person.address;
        form.ubigeo_description = person.ubigeo_description;
        //form.ubigeo = ubigeo;
        form.ubigeo = {
            district_id: person.ubigeo,
            city_name: person.ubigeo_description
        };
        persona.value = person;
        disabledBtnSelect.value = false;
        searchResults.value = []; // cerrar dropdown
    };


    // Mostrar alerta de "sin resultados"
    const showNoResults = (data) => {
        Swal2.fire({
            title: 'Búsqueda sin resultados',
            text: data.alert,
            icon: 'warning',
            padding: '2em',
            customClass: 'sweet-alerts',
        });

        disabledBtnSelect.value = true;
        searchResults.value = [];
    };


    const emit = defineEmits(['clientId']);

    const saveNewSearchClient = () => {
        axios.post(route('save_person_update_create'), form).then((res) => {
            disabledBtnSelect.value = false;
            Swal2.fire({
                title: 'Enhorabuena',
                text: 'Se registró correctamente',
                icon: 'success',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
            persona.value = res.data;
        }).catch(error => {
            setErrorFormSearch(error.response.data.errors);
            form.processing = false;
        });
    }

    const setErrorFormSearch = (validationErrors) => {
        if (validationErrors && validationErrors.number) {
            const numberErrors = validationErrors.number;

            for (let i = 0; i < numberErrors.length; i++) {
                form.setError('number', numberErrors[i]);
            }
        }
        if (validationErrors && validationErrors.telephone) {
            const telephoneErrors = validationErrors.telephone;

            for (let i = 0; i < telephoneErrors.length; i++) {
                form.setError('telephone', telephoneErrors[i]);
            }
        }
        if (validationErrors && validationErrors.full_name) {
            const fullNameErrors = validationErrors.full_name;

            for (let i = 0; i < fullNameErrors.length; i++) {
                form.setError('full_name', fullNameErrors[i]);
            }
        }
        if (validationErrors && validationErrors.email) {
            const emailErrors = validationErrors.email;

            for (let i = 0; i < emailErrors.length; i++) {
                form.setError('email', emailErrors[i]);
            }
        }
        if (validationErrors && validationErrors.address) {
            const addressErrors = validationErrors.address;

            for (let i = 0; i < addressErrors.length; i++) {
                form.setError('address', addressErrors[i]);
            }
        }
        if (validationErrors && validationErrors.ubigeo) {
            const ubigeoErrors = validationErrors.ubigeo;

            for (let i = 0; i < ubigeoErrors.length; i++) {
                form.setError('ubigeo', ubigeoErrors[i]);
            }
        }
        if (validationErrors && validationErrors.ubigeo_description) {
            const ubigeoDescriptionErrors = validationErrors.ubigeo_description;

            for (let i = 0; i < ubigeoDescriptionErrors.length; i++) {
                form.setError('ubigeo_description', ubigeoDescriptionErrors[i]);
            }
        }
    }


    watch(() => props.saleDocumentTypes, (id) => {
        if(id == 1){
            form.document_type = 6
        }else{
            form.document_type = 1
        }
        disabledBtnSelect.value = true;
    });
    const selectPersonNew = () => {
        form.reset();
        emit('clientId', persona.value);
    }

    const apiesLoading = ref(false);

    const searchApispe = () => {
        apiesLoading.value = true;
        axios.post(route('sales_search_person_apies'), form).then((res) => {

            if(res.data.success){
                if(form.document_type == 6){
                    form.full_name =  res.data.person['razon_social'];
                    form.email = null;
                    form.address = res.data.person['direccion'] == '-' ? null : res.data.person['direccion'];
                    form.ubigeo = {
                        district_id: res.data.person['ubigeo'],
                        city_name: res.data.person['departamento'] + '-' + res.data.person['provincia'] + '-'+ res.data.person['distrito']
                    };
                    form.ubigeo_description = res.data.person['departamento'] + '-' + res.data.person['provincia'] + '-'+ res.data.person['distrito'];
                    form.estado = res.data.person['estado'];
                    form.condicion = res.data.person['condicion'];
                }else{
                    form.full_name =  res.data.person['razon_social'];
                    form.estado = null;
                    form.condicion = null;
                }
            }else{
                Swal2.fire({
                    icon: 'error',
                    text: res.data.error,
                    padding: '2em',
                    customClass: 'sweet-alerts',
                })
            }

        }).finally(()=> {
            apiesLoading.value = false;
        });
    }
</script>

<template>
    <div>
        <ModalLargeX :show="display" :onClose="closeModalClient" :icon="'/img/comunidad.png'">
            <template #title>
                Cliente
            </template>
            <template #message>
                Registrar nuevo o editar buscando por su numero de documento
            </template>
            <template #content>
                <div class="grid grid-cols-4 gap-4">
                    <div class="col-span-6 sm:col-span-1">
                        <InputLabel value="Tipo de Documento" />
                        <select :disabled="saleDocumentTypes == 1 ? true : false"
                            class="form-select text-white-dark"
                            v-model="form.document_type">
                            <option value="">Seleccionar</option>
                            <template v-for="(documentType, index) in documentTypes">
                                <option :value="documentType.id">{{ documentType.description }}</option>
                            </template>
                        </select>
                        <InputError :message="form.errors.document_type" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-1">
                        <InputLabel for="number" value="Número de Doc." />
                        <div class="flex">
                            <div
                                class="bg-[#f1f2f3] dark:bg-[#1b2e4b] flex justify-center items-center ltr:rounded-l-md rtl:rounded-r-md px-3 font-semibold border ltr:border-r-0 rtl:border-l-0 border-[#e0e6ed] dark:border-[#17263c]"
                                v-tippy="{ content: 'Activa busqueda por numero', placement: 'bottom' }"
                            >
                                <input v-model="form.searchBy" :value="1" name="searchBy" type="radio" class="form-radio border-[#e0e6ed] dark:border-white-dark ltr:mr-0 rtl:ml-0" />
                            </div>
                            <input id="number" v-model="form.number" type="text" placeholder="Buscar por ruc o dni" class="form-input ltr:rounded-l-none rtl:rounded-r-none" autofocus />
                        </div>
                        <InputError :message="form.errors.number" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-2">
                        <InputLabel v-if="form.document_type == 6" for="full_name" value="Razón Social" />
                        <InputLabel v-else for="full_name" value="Nombres" />
                        <div class="w-full relative">
                            <div class="flex">
                                <input id="full_name" v-model="form.full_name" type="text" placeholder="Buscar por razon social o nombres" class="form-input ltr:rounded-r-none rtl:rounded-l-none" />
                                <div
                                    class="bg-[#f1f2f3] dark:bg-[#1b2e4b] flex justify-center items-center ltr:rounded-r-md rtl:rounded-l-md px-3 font-semibold border ltr:border-l-0 rtl:border-r-0 border-[#e0e6ed] dark:border-[#17263c]"
                                    v-tippy="{ content: 'Activa busqueda por nombres', placement: 'bottom' }"
                                >
                                    <input v-model="form.searchBy" :value="2" name="searchBy" type="radio" class="form-radio text-warning border-[#e0e6ed] dark:border-white-dark ltr:mr-0 rtl:ml-0" />
                                </div>
                            </div>
                            <div v-if="searchResults.length"
                                class="mt-1 absolute z-40 w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-md shadow-lg max-h-60 overflow-auto
                                        transition-all duration-200 ease-out animate-fadeIn">

                                <div v-for="(item, index) in searchResults" :key="index"
                                    @click="fillForm(item, { district_id: item.ubigeo, city_name: item.ubigeo_description })"
                                    class="px-3 py-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">

                                    <p class="font-semibold text-gray-700 dark:text-gray-200">
                                        {{ item.full_name }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        DNI: {{ item.number }} — {{ item.ubigeo_description }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <InputError :message="form.errors.full_name" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-1">
                        <InputLabel for="telephone" value="Teléfono" />
                        <TextInput id="telephone" v-model="form.telephone" type="text" />
                        <InputError :message="form.errors.telephone" class="mt-2" />
                    </div>

                    <div class="col-span-6 sm:col-span-1">
                        <InputLabel for="email" value="Email" />
                        <TextInput id="email" v-model="form.email" type="email" />
                        <InputError :message="form.errors.email" class="mt-2" />
                    </div>

                    <div class="col-span-6 sm:col-span-2">
                        <InputLabel for="city" value="Ciudad" />
                        <multiselect
                            id="city"
                            v-model="form.ubigeo"
                            :options="ubigeo"
                            class="custom-multiselect"
                            :searchable="true"
                            placeholder="Buscar ciudad"
                            selected-label="seleccionado"
                            select-label="Elegir"
                            deselect-label="Quitar"
                            label="city_name"
                            track-by="district_id"
                        ></multiselect>
                        <div>
                            <InputError :message="form.errors.ubigeo" class="mt-2" />
                        </div>
                    </div>
                    <div class="col-span-6 sm:col-span-2">
                        <InputLabel for="address" value="Dirección" />
                        <TextInput id="address" v-model="form.address" type="text" />
                        <InputError :message="form.errors.address" class="mt-2" />
                    </div>
                    <div v-if="form.condicion && form.estado" class="col-span-6 sm:col-span-2">
                        <div class="flex items-center gap-6">
                            <div>
                                <InputLabel for="condicion" value="Condicion" />
                                <span class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800/30 dark:text-yellow-500">{{ form.condicion }}</span>
                            </div>
                            <div>
                                <InputLabel for="estado" value="Estado" />
                                <span class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800/30 dark:text-yellow-500">{{ form.estado }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <template #buttons>
                <button @click="searchApispe" v-if="form.document_type != 0" type="button" class="btn btn-primary text-xs uppercase">
                    <icon-loader v-if="apiesLoading" class="w-4 h-4 animate-spin mr-1" />
                    <icon-company v-else class="w-4 h-4 mr-1" />
                    <span v-if="form.document_type == 6">SUNAT</span>
                    <span v-else-if="form.document_type == 1">RENIEC</span>
                </button>
                <RedButton @click="modalNewSearchClient()" >Buscar</RedButton>
                <PrimaryButton @click="saveNewSearchClient()" >
                    Guardar
                </PrimaryButton>
                <GreenButton
                    :disabled="disabledBtnSelect"
                    @click="selectPersonNew"
                >
                    <font-awesome-icon :icon="faShareFromSquare" />
                    Seleccionar
                </GreenButton>
            </template>
        </ModalLargeX>
    </div>
</template>
