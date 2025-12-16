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

    import iconLoader from '@/Components/vristo/icon/icon-loader.vue';
    import SuccessButton from '@/Components/SuccessButton.vue';

    const props = defineProps({
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
        searchBy: 1,
        gender: 'F'
    });

    const disabledBtnSelect = ref(true);
    const persona = ref({});
    const searchResults = ref([]);
    const loadingSearch = ref(false);

    const modalNewSearchClient = () => {
        form.clearErrors();
        searchResults.value = [];
        loadingSearch.value = true;
        axios.post(route('search_person_number'), form).then((res) => {
            if (form.searchBy == 1) {
                if (res.data.status) {
                    fillForm(res.data.person, res.data.ubigeo);
                    form.ubigeo = {
                        district_id: res.data.person.ubigeo,
                        ubigeo_description: res.data.person.ubigeo_description
                    };

                } else {
                    showNoResults(res.data);
                }
                loadingSearch.value = false;
                return;
            }

            // Si búsqueda por NOMBRES (2)
            if (form.searchBy == 2) {
                loadingSearch.value = false;
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
            loadingSearch.value = false;
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
        form.ubigeo_description = person.city;
        form.ubigeo = {
            district_id: person.ubigeo,
            ubigeo_description: person.ubigeo_description
        };
        form.gender = person.gender
        persona.value = person
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
        form.processing = true;
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
            form.processing = false;
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
                        ubigeo_description: res.data.person['departamento'] + '-' + res.data.person['provincia'] + '-'+ res.data.person['distrito']
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

    const clearForm = () => {
        form.reset();
        persona.value = [];
        disabledBtnSelect.value = true;
    }

    const ubigeoSelected = ref(null);
    const selectCity = () => {
        form.ubigeo_description = ubigeoSelected.value.ubigeo_description;
        form.ubigeo = ubigeoSelected.value.district_id;
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
                        <select class="form-select text-white-dark"
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
                            <input id="number" v-model="form.number" type="text" placeholder="Buscar por dni" class="form-input ltr:rounded-l-none rtl:rounded-r-none" autofocus />
                        </div>
                        <InputError :message="form.errors.number" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-2">
                        <InputLabel for="full_name" value="Nombres" />
                        <div>
                            <div class="flex">
                                <input id="full_name" v-model="form.full_name" type="text" placeholder="Buscar por nombres" class="form-input ltr:rounded-r-none rtl:rounded-l-none" />
                                <div
                                    class="bg-[#f1f2f3] dark:bg-[#1b2e4b] flex justify-center items-center ltr:rounded-r-md rtl:rounded-l-md px-3 font-semibold border ltr:border-l-0 rtl:border-r-0 border-[#e0e6ed] dark:border-[#17263c]"
                                    v-tippy="{ content: 'Activa busqueda por nombres', placement: 'bottom' }"
                                >
                                    <input v-model="form.searchBy" :value="2" name="searchBy" type="radio" class="form-radio text-warning border-[#e0e6ed] dark:border-white-dark ltr:mr-0 rtl:ml-0" />
                                </div>
                            </div>
                            <div v-if="searchResults.length"
                                class="mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-md shadow-lg max-h-60 overflow-auto
                                        transition-all duration-200 ease-out animate-fadeIn">

                                <div v-for="(item, index) in searchResults" :key="index"
                                    @click="fillForm(item, { district_id: item.district_id, city_name: item.city })"
                                    class="px-3 py-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">

                                    <p class="font-semibold text-gray-700 dark:text-gray-200">
                                        {{ item.full_name }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        DNI: {{ item.number }} — {{ item.city }}
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
                            v-model="ubigeoSelected"
                            :options="ubigeo"
                            class="custom-multiselect"
                            :searchable="true"
                            placeholder="Buscar ciudad"
                            selected-label="seleccionado"
                            select-label="Elegir"
                            deselect-label="Quitar"
                            label="ubigeo_description"
                            track-by="district_id"
                            @update:model-value="selectCity"
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
                    <div class="col-span-6 sm:col-span-2">
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
                </div>
            </template>
            <template #buttons>
                <SuccessButton @click="clearForm" >
                    <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512">
                        <path fill="currentColor" d="M566.6 54.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-192 192-34.7-34.7c-4.2-4.2-10-6.6-16-6.6-12.5 0-22.6 10.1-22.6 22.6l0 29.1 108.3 108.3 29.1 0c12.5 0 22.6-10.1 22.6-22.6 0-6-2.4-11.8-6.6-16l-34.7-34.7 192-192zM341.1 353.4L222.6 234.9c-42.7-3.7-85.2 11.7-115.8 42.3l-8 8c-22.3 22.3-34.8 52.5-34.8 84 0 6.8 7.1 11.2 13.2 8.2l51.1-25.5c5-2.5 9.5 4.1 5.4 7.9L7.3 473.4C2.7 477.6 0 483.6 0 489.9 0 502.1 9.9 512 22.1 512l173.3 0c38.8 0 75.9-15.4 103.4-42.8 30.6-30.6 45.9-73.1 42.3-115.8z"/>
                    </svg>
                    Limpiar
                </SuccessButton>
                <button @click="searchApispe" v-if="form.document_type != 0" type="button" class="btn btn-primary text-xs uppercase">
                    <icon-loader v-if="apiesLoading" class="w-4 h-4 animate-spin mr-1" />
                    <svg v-else class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                        <path fill="currentColor" d="M480 272C480 317.9 465.1 360.3 440 394.7L566.6 521.4C579.1 533.9 579.1 554.2 566.6 566.7C554.1 579.2 533.8 579.2 521.3 566.7L394.7 440C360.3 465.1 317.9 480 272 480C157.1 480 64 386.9 64 272C64 157.1 157.1 64 272 64C386.9 64 480 157.1 480 272zM369 289C378.4 279.6 378.4 264.4 369 255.1L297 183.1C287.6 173.7 272.4 173.7 263.1 183.1C253.8 192.5 253.7 207.7 263.1 217L294.1 248L192 248C178.7 248 168 258.7 168 272C168 285.3 178.7 296 192 296L294.1 296L263.1 327C253.7 336.4 253.7 351.6 263.1 360.9C272.5 370.2 287.7 370.3 297 360.9L369 288.9z"/>
                    </svg>
                    <span v-if="form.document_type == 6">SUNAT</span>
                    <span v-else-if="form.document_type == 1">RENIEC</span>
                </button>
                <RedButton @click="modalNewSearchClient()" >
                    <icon-loader v-if="loadingSearch" class="w-4 h-4 animate-spin mr-1" />
                    <iconDatabaseSearch v-else class="w-4 h-4 mr-1" />
                    Buscar
                </RedButton>
                <PrimaryButton @click="saveNewSearchClient()" >
                    <icon-loader v-if="form.processing" class="w-4 h-4 animate-spin mr-1" />
                    <svg v-else class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                        <path fill="currentColor" d="M160 144C151.2 144 144 151.2 144 160L144 480C144 488.8 151.2 496 160 496L480 496C488.8 496 496 488.8 496 480L496 237.3C496 233.1 494.3 229 491.3 226L416 150.6L416 240C416 257.7 401.7 272 384 272L224 272C206.3 272 192 257.7 192 240L192 144L160 144zM240 144L240 224L368 224L368 144L240 144zM96 160C96 124.7 124.7 96 160 96L402.7 96C419.7 96 436 102.7 448 114.7L525.3 192C537.3 204 544 220.3 544 237.3L544 480C544 515.3 515.3 544 480 544L160 544C124.7 544 96 515.3 96 480L96 160zM256 384C256 348.7 284.7 320 320 320C355.3 320 384 348.7 384 384C384 419.3 355.3 448 320 448C284.7 448 256 419.3 256 384z"/>
                    </svg>
                    Guardar
                </PrimaryButton>
                <GreenButton
                    :disabled="disabledBtnSelect"
                    @click="selectPersonNew"
                >
                    <font-awesome-icon :icon="faShareFromSquare" class="w-4 h-4 mr-1" />
                    Seleccionar
                </GreenButton>
            </template>
        </ModalLargeX>
    </div>
</template>
