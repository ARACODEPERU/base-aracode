<script setup>
    import { useForm, Link } from '@inertiajs/vue3';
    import FormSection from '@/Components/FormSection.vue';
    import InputError from '@/Components/InputError.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import SuccessButton from '@/Components/SuccessButton.vue';
    import DarkButton from '@/Components/DarkButton.vue';
    import TextInput from '@/Components/TextInput.vue';
    import Keypad from '@/Components/Keypad.vue';
    import Swal2 from 'sweetalert2';
    import { ref, watch, onMounted } from 'vue';
    import { Empty } from 'ant-design-vue';
    import 'cropperjs/dist/cropper.css';
    import CropperImage from '@/Components/CropperImage.vue';
    import flatPickr from 'vue-flatpickr-component';
    import 'flatpickr/dist/flatpickr.css';
    import { Spanish } from "flatpickr/dist/l10n/es.js"
    import Multiselect from '@suadelabs/vue3-multiselect';
    import '@suadelabs/vue3-multiselect/dist/vue3-multiselect.css';
    import relationships from '@/Components/vristo/selects/relationships.vue';
    import ModalLarge from '@/Components/ModalLarge.vue';
    import iconTrash from '@/Components/vristo/icon/icon-trash.vue';
    import iconPlus from '@/Components/vristo/icon/icon-plus.vue';
    import VueCollapsible from 'vue-height-collapsible/vue3';
    import iconMinus from '@/Components/vristo/icon/icon-minus.vue';
    import iconLoader from '@/Components/vristo/icon/icon-loader.vue';

    const accordians3 = ref(1);

    const props = defineProps({
        sedes: {
            type: Object,
            default: () => ({})
        },
        identityDocumentTypes: {
            type: Object,
            default: () => ({}),
        },
        ubigeo: {
            type: Object,
            default: () => ({})
        },
        schedules: {
            type: Object,
            default: () => ({})
        },
        evangelizations: {
            type: Object,
            default: () => ({})
        }
    });

    const ubigeoSelected = ref({
        ubigeo_description: null,
        district_id: null
    });

    const form = useForm({
        id: null,
        member_id: null,
        believer_id: null,
        sede_id: null,
        date_statement: null,
        document_type_id: null,
        number: null,
        birthdate: null,
        full_name: null,
        names: null,
        father_lastname: null,
        mother_lastname: null,
        image_preview: null,
        address: null,
        ubigeo_description: null,
        ubigeo: null,
        telephone: null,
        email: null,
        status: true,
        gender: 'M',
        relatives: [],
        accept_Christ: 'SI',
        schedule_id: 1,
        name_red: null,
        prayer_reason: null,
        soul_won: null,
        well_known_place: [],
        contact: false,
        contact_full_names: null,
        contact_person_id: null
    });

    const childrenForm = useForm({
        birthdate: null,
        document_type_id: null,
        number: null,
        names: null,
        father_lastname: null,
        mother_lastname: null,
        gender: 'M',
        relationship: null
    });

    const displayModalChildren = ref(false);

    const opemModalChildren = () => {
        displayModalChildren.value = true;
    };
    const closeModalChildren = () => {
        displayModalChildren.value = false;
    };

    const selectCity = () => {
        form.ubigeo_description = ubigeoSelected.value.ubigeo_description;
        form.ubigeo = ubigeoSelected.value.district_id;
    }

    const createBeliever = () => {
        form.post(route('cigle_member_believing_store'), {
            forceFormData: true,
            errorBag: 'createBeliever',
            preserveScroll: true,
            onSuccess: () => {
                Swal2.fire({
                    title: 'Enhorabuena',
                    text: 'Se registró correctamente',
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                form.reset();
                formIsDisabled.value = true;
            },
        });
    }

    const addRelationships = () => {
        form.relatives.push({
            birthdate: childrenForm.birthdate,
            document_type_id: childrenForm.document_type_id,
            number: childrenForm.number,
            names: childrenForm.names,
            father_lastname: childrenForm.father_lastname,
            mother_lastname: childrenForm.mother_lastname,
            gender: childrenForm.gender,
            relationship: childrenForm.relationship
        });

        childrenForm.reset();
        showAlertToast('Se agrego correctamente','success')
    }

    const removeRelationships = (index) => {
        form.relatives.splice(index, 1);
    }

    const configFlatPickr = {
        dateFormat: 'Y-m-d',
        locale: Spanish
    };

    const displayLoaderSearchBtn = ref(false);
    const formIsDisabled = ref(true); // El formulario está deshabilitado por defecto
    const inputNumberRef = ref(null);
    // Función que se activa cuando la búsqueda es exitosa
    const handleBasedatosSearch = () => {
        displayLoaderSearchBtn.value = true;
        axios({
            method: "post",
            url: route('cigle_member_search'),
            data: {
                from: 'believers',
                documentType: form.document_type_id,
                number: form.number,
            },
        }).then((result) => {
            if(result.data.success){
                form.relatives = [];
                form.id = result.data.person.id;
                form.believer_id = result.data.person.believer?.id;
                form.member_id = result.data.person.member?.id;
                form.full_name = result.data.person.full_name;
                form.birthdate = result.data.person.birthdate;
                form.names = result.data.person.names;
                form.father_lastname = result.data.person.father_lastname;
                form.mother_lastname = result.data.person.mother_lastname;
                form.address = result.data.person.address;
                form.ubigeo_description = result.data.person.ubigeo_description;
                form.ubigeo = result.data.person.ubigeo;
                form.telephone = result.data.person.telephone;
                form.email = result.data.person.email;
                form.status = result.data.person.status;
                form.gender = result.data.person.gender;

                ubigeoSelected.value.ubigeo_description = result.data.person.ubigeo_description;
                ubigeoSelected.value.district_id = result.data.person.ubigeo;

                let personRelatives = result.data.person.relatives;

                for (const relative of personRelatives) {
                    form.relatives.push({
                        birthdate: relative.person.birthdate,
                        document_type_id: relative.person.document_type_id,
                        number: relative.person.number,
                        names: relative.person.names,
                        father_lastname: relative.person.father_lastname,
                        mother_lastname: relative.person.mother_lastname,
                        gender: relative.person.gender,
                        relationship: relative.relationship
                    });
                }

                form.sede_id = result.data.person.member?.sede_id;
                form.date_statement = result.data.person.member?.date_statement;

                form.accept_Christ = result.data.person.believer?.accept_Christ;
                form.schedule_id = result.data.person.believer?.schedule_id;
                form.name_red = result.data.person.believer?.name_red;
                form.prayer_reason = result.data.person.believer?.prayer_reason;
                form.soul_won = result.data.person.believer?.evangelization_id;

                form.well_known_place = result.data.person.believer?.well_known_place ?? [];
                form.contact = result.data.person.believer?.contact_full_names ? false : true;
                form.contact_full_names = result.data.person.believer?.contact_full_names;
                form.contact_person_id = result.data.person.believer?.contact_person_id;

                Swal2.fire({
                    icon: 'success',
                    title: '¡Enhorabuena!',
                    text: 'La persona existe en los registro, ¿desea continuar?',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                    showDenyButton: true,
                    confirmButtonText: "Continuar",
                    denyButtonText: `Cancelar`,
                    allowOutsideClick: false, // 👈 no cerrar al hacer clic fuera
                    allowEscapeKey: false,    // 👈 no cerrar con la tecla ESC
                    allowEnterKey: false
                }).then((result) => {
                    /* Read more about isConfirmed, isDenied below */
                    if (result.isDenied) {
                        Swal2.fire({
                            title: "Registrar nuevo creyente",
                            icon: "info",
                            padding: '2em',
                            customClass: 'sweet-alerts',
                            allowOutsideClick: false, // 👈 no cerrar al hacer clic fuera
                            allowEscapeKey: false,    // 👈 no cerrar con la tecla ESC
                            allowEnterKey: false
                        }).then(() => {
                            form.reset();
                            inputNumberRef.value?.focus();
                            formIsDisabled.value = true;
                        });
                    }
                });

            }else{
                showAlertToast('No existe registros para: ' + form.number, 'error')
            }
        }).finally(() => {
            formIsDisabled.value = false;
            displayLoaderSearchBtn.value = false;
        });

    };

    const apiesLoading = ref(false);

    const searchApispe = () => {
        apiesLoading.value = true;

        form.clearErrors();
        if(form.document_type_id && form.number){
            axios.post(route('sales_search_person_apies'), {
                document_type: form.document_type_id,
                number: form.number
            }).then((res) => {

                if(res.data.success){
                    form.full_name =  res.data.person['razonSocial'];
                    form.names = res.data.person['names'];
                    form.father_lastname = res.data.person['father_lastname'];
                    form.mother_lastname = res.data.person['mother_lastname'];
                    form.email = null;
                    form.address = null;
                }else{
                    showAlertToast(res.data.error, 'error')
                    form.reset();
                }

            }).finally(()=> {
                apiesLoading.value = false;
                formIsDisabled.value = false;
            });
        }else{
            apiesLoading.value = false;
            form.setError('document_type_id', 'Campo requerido')
            form.setError('number', 'Campo requerido')
        }

    }

    const showAlertToast = (text , type = 'success') => {
        const toast = Swal2.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            padding: '2em',
            customClass: 'sweet-alerts',
        });
        toast.fire({
            icon: type,
            title: text,
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    }

    const formFamilyIsDisabled = ref(true); // El formulario está deshabilitado por defecto
    const displayLoaderBtnSearchFamily = ref(false);
    // Función que se activa cuando la búsqueda es exitosa
    const handleBasedatosFamilySearch = () => {
        displayLoaderBtnSearchFamily.value = true;
        axios({
            method: "post",
            url: route('cigle_member_search'),
            data: {
                from: 'believers',
                documentType: childrenForm.document_type_id,
                number: childrenForm.number,
            },
        }).then((result) => {
            if(result.data.success){
                childrenForm.full_name = result.data.person.full_name;
                childrenForm.birthdate = result.data.person.birthdate;
                childrenForm.names = result.data.person.names;
                childrenForm.father_lastname = result.data.person.father_lastname;
                childrenForm.mother_lastname = result.data.person.mother_lastname;
                childrenForm.gender = result.data.person.gender;
            }else{
                showAlertToast('No existe registros para: ' + form.number, 'error')
            }
        }).finally(() => {
            formFamilyIsDisabled.value = false;
            displayLoaderBtnSearchFamily.value = false;
        });

    };

    const apiesFamilyLoading = ref(false);

    const searchFamilyApispe = () => {
        apiesFamilyLoading.value = true;
        childrenForm.clearErrors();
        if(childrenForm.document_type_id && childrenForm.number){
            axios.post(route('sales_search_person_apies'), {
                document_type: childrenForm.document_type_id,
                number: childrenForm.number
            }).then((res) => {

                if(res.data.success){
                    childrenForm.full_name =  res.data.person['razonSocial'];
                    childrenForm.names = res.data.person['names'];
                    childrenForm.father_lastname = res.data.person['father_lastname'];
                    childrenForm.mother_lastname = res.data.person['mother_lastname'];
                }else{
                    showAlertToast(res.data.error, 'error')
                }

            }).finally(()=> {
                apiesFamilyLoading.value = false;
                formIsDisabled.value = false;
            });
        }else{
            apiesFamilyLoading.value = false;
            childrenForm.setError('document_type_id', 'Campo requerido')
            childrenForm.setError('number', 'Campo requerido')
        }

    }
</script>

<template>
    <FormSection @submitted="createBeliever" class="">
        <template #title>
            Creyente Detalles
        </template>

        <template #description>
            Crear nuevo creyente, Los campos con * son obligatorios
        </template>

        <template #form>
            <div class="lg:col-span-4">
                <div class="grid lg:grid-cols-6 gap-6">
                    <div class="sm:col-span-2">
                        <InputLabel for="document_type_id" value="Tipo documento" />
                        <select v-model="form.document_type_id" id="document_type_id" class="form-select">
                            <option :value="null">Por favor seleccione</option>
                            <option v-for="(identityDocumentType) in identityDocumentTypes" :value="identityDocumentType.id">{{ identityDocumentType.description }}</option>
                        </select>
                        <InputError :message="form.errors.document_type_id" class="mt-2" />
                    </div>
                    <div class="sm:col-span-4 flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
                        <div class="flex-grow">
                            <InputLabel for="number" value="Número" />
                            <TextInput
                                id="number"
                                ref="inputNumberRef"
                                v-model="form.number"
                                type="number"
                            />
                            <InputError :message="form.errors.number" class="mt-2" />
                        </div>
                        <div class="flex justify-end gap-x-2 mt-4 lg:mt-0 flex-shrink-0">
                            <DarkButton @click="searchApispe" type="button" class="py-2.5">
                               <icon-loader v-show="apiesLoading" class="w-4 h-4 mr-2" />
                                RENIEC
                            </DarkButton>
                            <SuccessButton @click="handleBasedatosSearch" class="py-2.5">
                                <icon-loader v-show="displayLoaderSearchBtn" class="w-4 h-4 mr-2" />
                                Buscar
                            </SuccessButton>
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel value="Sede *" />
                        <select v-model="form.sede_id" class="form-select" :disabled="formIsDisabled" :class="formIsDisabled ? 'bg-gray-300/50 cursor-not-allowed' : ''">
                            <option :value="null" selected>Por favor seleccione</option>
                            <template v-for="sede in sedes">
                                <option :value="sede.id">{{ sede.description }}</option>
                            </template>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel for="date_statement" value="Fecha de afirmación *" />
                        <flat-pickr
                            v-model="form.date_statement"
                            :config="configFlatPickr"
                            class="form-input"
                            placeholder="Selecciona fecha"
                            id="date_statement"
                            :disabled="formIsDisabled" :class="formIsDisabled ? 'bg-gray-300/50 cursor-not-allowed' : ''"
                        />
                        <InputError :message="form.errors.next_appointmen_doctor_id" class="mt-1" />
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel for="birthdate" value="Fecha de nacimiento *" />
                        <flat-pickr
                            v-model="form.birthdate"
                            :config="configFlatPickr"
                            class="form-input"
                            placeholder="Selecciona fecha"
                            id="birthdate"
                            :disabled="formIsDisabled" :class="formIsDisabled ? 'bg-gray-300/50 cursor-not-allowed' : ''"
                        />
                        <InputError :message="form.errors.birthdate" class="mt-2" />
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel for="names" value="Nombres *" />
                        <TextInput
                            id="names"
                            v-model="form.names"
                            type="text"
                            :disabled="formIsDisabled" :class="formIsDisabled ? 'bg-gray-300/50 cursor-not-allowed' : ''"
                        />
                        <InputError :message="form.errors.names" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-2 ">
                        <InputLabel for="father_lastname" value="Apellido Paterno *" />
                        <TextInput
                            id="father_lastname"
                            v-model="form.father_lastname"
                            type="text"
                            :disabled="formIsDisabled" :class="formIsDisabled ? 'bg-gray-300/50 cursor-not-allowed' : ''"
                        />
                        <InputError :message="form.errors.father_lastname" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-2 ">
                        <InputLabel for="mother_lastname" value="Apellido Materno *" />
                        <TextInput
                            id="mother_lastname"
                            v-model="form.mother_lastname"
                            type="text"
                            :disabled="formIsDisabled" :class="formIsDisabled ? 'bg-gray-300/50 cursor-not-allowed' : ''"
                        />
                        <InputError :message="form.errors.mother_lastname" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-3 ">
                        <InputLabel for="address" value="Dirección *" />
                        <TextInput
                            id="address"
                            v-model="form.address"
                            type="text"
                            :disabled="formIsDisabled" :class="formIsDisabled ? 'bg-gray-300/50 cursor-not-allowed' : ''"
                        />
                        <InputError :message="form.errors.address" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-3 ">
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
                            :disabled="formIsDisabled" :class="formIsDisabled ? 'bg-gray-300/50 cursor-not-allowed' : ''"
                        ></multiselect>
                        <InputError :message="form.errors.ubigeo" class="mt-2" />
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel for="telephone" value="Teléfono *" />
                        <TextInput
                            id="telephone"
                            v-model="form.telephone"
                            type="text"
                            class="block w-full mt-1"
                            :disabled="formIsDisabled" :class="formIsDisabled ? 'bg-gray-300/50 cursor-not-allowed' : ''"
                        />
                        <InputError :message="form.errors.telephone" class="mt-2" />
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel for="email" value="Email *" />
                        <TextInput
                            id="email"
                            v-model="form.email"
                            type="text"
                            class="block w-full mt-1"
                            :disabled="formIsDisabled" :class="formIsDisabled ? 'bg-gray-300/50 cursor-not-allowed' : ''"
                        />
                        <InputError :message="form.errors.email" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-3">
                        <InputLabel for="gender" value="Genero *" />
                        <div class="space-x-4">
                            <label class="inline-flex">
                                <input v-model="form.gender" type="radio" value="M" name="square_radio_g" class="form-radio rounded-none" checked :disabled="formIsDisabled" />
                                <span>Masculino</span>
                            </label>
                            <label class="inline-flex">
                                <input v-model="form.gender" type="radio" value="F" name="square_radio_g" class="form-radio text-success rounded-none" :disabled="formIsDisabled" />
                                <span>Femenino</span>
                            </label>
                        </div>
                        <InputError :message="form.errors.gender" class="mt-2" />
                    </div>
                </div>
            </div>
            <div class="lg:col-span-2">
                <!-- icons -->
                <div class="space-y-2 font-semibold">
                    <div class="border border-[#d3d3d3] dark:border-[#1b2e4b] rounded">
                        <div
                            class="p-4 w-full flex items-center justity-between text-white-dark dark:bg-[#1b2e4b]"
                            :class="[
                                { '!text-primary': accordians3 === 1 },
                                { 'bg-gray-300/50 cursor-not-allowed' : formIsDisabled}
                            ]"
                        >
                            <div class="flex items-center">
                                <img :src="'/img/familia.png'" class="ltr:mr-2 rtl:ml-2 text-primary shrink-0 w-6 h-6" />
                                <h4 class="text-sm font-bold text-gray-800 dark:text-white">
                                    FAMILIARES
                                    <button @click="opemModalChildren" type="button" class="px-2 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent text-blue-600 hover:text-blue-800 focus:outline-hidden focus:text-blue-800 disabled:opacity-50 disabled:pointer-events-none dark:text-blue-500 dark:hover:text-blue-400 dark:focus:text-blue-400">
                                        Agregar (+)
                                    </button>
                                </h4>
                            </div>
                            <button
                                type="button"
                                class="ltr:ml-auto rtl:mr-auto"
                                @click="accordians3 === 1 ? (accordians3 = null) : (accordians3 = 1)"
                                >
                                    <icon-minus v-if="accordians3 === 1" class="w-5 h-5" />
                                    <icon-plus v-else class="w-5 h-5" />

                            </button>
                        </div>
                        <vue-collapsible :isOpen="accordians3 === 1 && !formIsDisabled">
                            <div class="space-y-2 p-4 text-white-dark text-[13px] border-t border-[#d3d3d3] dark:border-[#1b2e4b]">
                                <div v-if="form.relatives.length > 0">
                                    <ol class="flex flex-col">
                                        <template v-for="(children, index) in form.relatives">
                                            <li class="inline-flex items-center gap-x-2 py-3 px-4 text-sm font-medium text-gray-800 -mt-px first:rounded-t-lg first:mt-0 dark:text-white">
                                                <div>
                                                    <button @click="removeRelationships(index)" type="button" v-tippy:delete>
                                                        <icon-trash class="w-5 h-5" />
                                                    </button>
                                                    <tippy target="delete">Eliminar</tippy>
                                                </div>
                                                <div class="flex justify-between w-full items-center">
                                                    <div>
                                                        {{ children.names +' '+ children.father_lastname +' '+ children.mother_lastname }}
                                                    </div>
                                                    <div class="flex-shrink-0">
                                                        <span class="inline-flex items-center gap-x-1.5 py-1 px-3 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800/30 dark:text-blue-500">
                                                            <span class="size-1.5 inline-block rounded-full bg-blue-800 dark:bg-blue-500"></span>
                                                            {{ children.relationship }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </li>
                                        </template>
                                    </ol>
                                </div>
                                <template v-else>
                                    <empty />
                                </template>
                            </div>
                        </vue-collapsible>
                    </div>

                    <div class="border border-[#d3d3d3] dark:border-[#1b2e4b] rounded">
                        <div
                            class="p-4 w-full flex items-center justity-between text-white-dark dark:bg-[#1b2e4b]"
                            :class="[{ '!text-primary': accordians3 === 2 },{ 'bg-gray-300/50 cursor-not-allowed' : formIsDisabled}]"
                        >
                            <div class="flex items-center">
                                <img :src="'/img/amor.png'" class="ltr:mr-2 rtl:ml-2 text-primary shrink-0 w-6 h-6" />
                                <h4 class="text-sm font-bold text-gray-800 dark:text-white">
                                    ¿ACEPTÓ A JESÚS? *
                                </h4>
                            </div>
                            <button
                                type="button"
                                class="ltr:ml-auto rtl:mr-auto"
                                @click="accordians3 === 2 ? (accordians3 = null) : (accordians3 = 2)"
                                >
                                    <icon-minus v-if="accordians3 === 2" class="w-5 h-5" />
                                    <icon-plus v-else class="w-5 h-5" />

                            </button>
                        </div>
                        <vue-collapsible :isOpen="accordians3 === 2 && !formIsDisabled">
                        <div class="p-4 text-[13px] border-t border-[#d3d3d3] dark:border-[#1b2e4b]">
                            <div class="flex items-center gap-6">
                                <label class="inline-flex">
                                    <input v-model="form.accept_Christ" value="SI" type="radio" name="defaultAc" class="form-radio peer" />
                                    <span class="peer-checked:text-primary">SI</span>
                                </label>
                                <label class="inline-flex">
                                    <input v-model="form.accept_Christ" value="NO" type="radio" name="defaultAc" class="form-radio peer" />
                                    <span class="peer-checked:text-primary">NO</span>
                                </label>
                                <label class="inline-flex">
                                    <input v-model="form.accept_Christ" value="Reconciliación" type="radio" name="defaultAc" class="form-radio peer" />
                                    <span class="peer-checked:text-primary">Reconciliación</span>
                                </label>
                            </div>
                        </div>
                        </vue-collapsible>
                    </div>

                    <div class="border border-[#d3d3d3] dark:border-[#1b2e4b] rounded">
                        <div
                            class="p-4 w-full flex items-center justity-between text-white-dark dark:bg-[#1b2e4b]"
                            :class="[{ '!text-primary': accordians3 === 3 },{ 'bg-gray-300/50 cursor-not-allowed' : formIsDisabled}]"
                        >
                            <div class="flex items-center">
                                <img :src="'/img/gestion-del-tiempo.png'" class="ltr:mr-2 rtl:ml-2 text-primary shrink-0 w-6 h-6" />
                                <h4 class="text-sm font-bold text-gray-800 dark:text-white">
                                    HORARIO DEL SERVICIO *
                                </h4>
                            </div>
                            <button
                                type="button"
                                class="ltr:ml-auto rtl:mr-auto"
                                @click="accordians3 === 3 ? (accordians3 = null) : (accordians3 = 3)"
                                >
                                    <icon-minus v-if="accordians3 === 3" class="w-5 h-5" />
                                    <icon-plus v-else class="w-5 h-5" />

                            </button>
                        </div>
                        <vue-collapsible :isOpen="accordians3 === 3 && !formIsDisabled">
                            <div class="p-4 text-[13px] border-t border-[#d3d3d3] dark:border-[#1b2e4b]">
                                <div class="space-y-4 flex flex-col">
                                    <template v-for="schedule in schedules">
                                        <label class="inline-flex">
                                            <input v-model="form.schedule_id" :value="schedule.id" type="radio" name="defaultHc" class="form-radio peer" />
                                            <span class="peer-checked:text-primary">{{ schedule.description }}</span>
                                        </label>
                                    </template>
                                </div>
                            </div>
                        </vue-collapsible>
                    </div>
                    <div class="border border-[#d3d3d3] dark:border-[#1b2e4b] rounded">
                        <div
                            class="p-4 w-full flex items-center justity-between text-white-dark dark:bg-[#1b2e4b]"
                            :class="[{ '!text-primary': accordians3 === 4 },{ 'bg-gray-300/50 cursor-not-allowed' : formIsDisabled}]"
                        >
                            <div class="flex items-center">
                                <img :src="'/img/redes.png'" class="ltr:mr-2 rtl:ml-2 text-primary shrink-0 w-6 h-6" />
                                <h4 class="text-sm font-bold text-gray-800 dark:text-white">
                                    ¿PERTENECE A UNA RED? *
                                </h4>
                            </div>
                            <button
                                type="button"
                                class="ltr:ml-auto rtl:mr-auto"
                                @click="accordians3 === 4 ? (accordians3 = null) : (accordians3 = 4)"
                                >
                                    <icon-minus v-if="accordians3 === 4" class="w-5 h-5" />
                                    <icon-plus v-else class="w-5 h-5" />

                            </button>
                        </div>
                        <vue-collapsible :isOpen="accordians3 === 4 && !formIsDisabled">
                            <div class="p-4 text-[13px] border-t border-[#d3d3d3] dark:border-[#1b2e4b]">
                                <div>
                                    <label for="name_red">Nombre de red</label>
                                    <input v-model="form.name_red" id="name_red" type="text" class="form-input" />
                                    <span class="text-white-dark text-[11px] inline-block mt-1">Sí aún no pertenece a una red, coloque: Ninguna</span>
                                </div>
                            </div>
                        </vue-collapsible>
                    </div>
                    <div class="border border-[#d3d3d3] dark:border-[#1b2e4b] rounded">
                        <div
                            class="p-4 w-full flex items-center justity-between text-white-dark dark:bg-[#1b2e4b]"
                            :class="[{ '!text-primary': accordians3 === 5 },{ 'bg-gray-300/50 cursor-not-allowed' : formIsDisabled}]"
                        >
                            <div class="flex items-center">
                                <img :src="'/img/victoria.png'" class="ltr:mr-2 rtl:ml-2 text-primary shrink-0 w-6 h-6" />
                                <h4 class="text-sm font-bold text-gray-800 dark:text-white">
                                    MOTIVOS DE ORACIÓN *
                                </h4>
                            </div>
                            <button
                                type="button"
                                class="ltr:ml-auto rtl:mr-auto"
                                @click="accordians3 === 5 ? (accordians3 = null) : (accordians3 = 5)"
                                >
                                    <icon-minus v-if="accordians3 === 5" class="w-5 h-5" />
                                    <icon-plus v-else class="w-5 h-5" />

                            </button>
                        </div>
                        <vue-collapsible :isOpen="accordians3 === 5 && !formIsDisabled">
                            <div class="p-4 text-[13px] border-t border-[#d3d3d3] dark:border-[#1b2e4b]">
                                <div>
                                    <label for="prayer_reason">Descripción</label>
                                    <textarea v-model="form.prayer_reason" id="prayer_reason" class="form-textarea"></textarea>
                                    <span class="text-red text-[11px] inline-block mt-1">Esta pregunta es obligatoria</span>
                                </div>
                            </div>
                        </vue-collapsible>
                    </div>
                    <div class="border border-[#d3d3d3] dark:border-[#1b2e4b] rounded">
                        <div
                            class="p-4 w-full flex items-center justity-between text-white-dark dark:bg-[#1b2e4b]"
                            :class="[{ '!text-primary': accordians3 === 6 },{ 'bg-gray-300/50 cursor-not-allowed' : formIsDisabled}]"
                        >
                            <div class="flex items-center">
                                <img :src="'/img/feliz.png'" class="ltr:mr-2 rtl:ml-2 text-primary shrink-0 w-6 h-6" />
                                <h4 class="text-sm font-bold text-gray-800 dark:text-white">
                                    ALMA GANADA EN *
                                </h4>
                            </div>
                            <button
                                type="button"
                                class="ltr:ml-auto rtl:mr-auto"
                                @click="accordians3 === 6 ? (accordians3 = null) : (accordians3 = 6)"
                                >
                                    <icon-minus v-if="accordians3 === 6" class="w-5 h-5" />
                                    <icon-plus v-else class="w-5 h-5" />

                            </button>
                        </div>
                        <vue-collapsible :isOpen="accordians3 === 6 && !formIsDisabled">
                            <div class="p-4 text-[13px] border-t border-[#d3d3d3] dark:border-[#1b2e4b]">
                                <div class="space-y-4 flex flex-col">
                                    <template v-for="evangelization in evangelizations">
                                        <label class="inline-flex">
                                            <input v-model="form.soul_won" :value="evangelization.id" type="radio" name="defaultAg" class="form-radio peer" />
                                            <span class="peer-checked:text-primary">{{ evangelization.description }}</span>
                                        </label>
                                    </template>
                                </div>
                            </div>
                        </vue-collapsible>
                    </div>
                    <div class="border border-[#d3d3d3] dark:border-[#1b2e4b] rounded">
                        <div
                            class="p-4 w-full flex items-center justity-between text-white-dark dark:bg-[#1b2e4b]"
                            :class="[{ '!text-primary': accordians3 === 7 },{ 'bg-gray-300/50 cursor-not-allowed' : formIsDisabled}]"
                        >
                            <div class="flex items-center">
                                <img :src="'/img/megafono.png'" class="ltr:mr-2 rtl:ml-2 text-primary shrink-0 w-6 h-6" />
                                <h4 class="text-sm font-bold text-gray-800 dark:text-white">
                                    ¿COMO SUPO DE LA IGLESIA? *
                                </h4>
                            </div>
                            <button
                                type="button"
                                class="ltr:ml-auto rtl:mr-auto"
                                @click="accordians3 === 7 ? (accordians3 = null) : (accordians3 = 7)"
                                >
                                    <icon-minus v-if="accordians3 === 7" class="w-5 h-5" />
                                    <icon-plus v-else class="w-5 h-5" />

                            </button>
                        </div>
                        <vue-collapsible :isOpen="accordians3 === 7 && !formIsDisabled">
                            <div class="p-4 text-[13px] border-t border-[#d3d3d3] dark:border-[#1b2e4b]">
                                <div class="space-y-4 flex flex-col">
                                    <label class="inline-flex" for="amigo">
                                        <input v-model="form.well_known_place" id="amigo" :value="'Por una amistad'" type="checkbox" class="form-checkbox peer" name="well_known_place[]"  />
                                        <span class="peer-checked:text-primary">Por una amistad</span>
                                    </label>
                                    <label class="inline-flex" for="familiar">
                                        <input v-model="form.well_known_place" id="familiar" :value="'Por un familiar'" type="checkbox" class="form-checkbox peer" name="well_known_place[]"  />
                                        <span class="peer-checked:text-primary">Por un familiar</span>
                                    </label>
                                    <label class="inline-flex" for="evangelismo">
                                        <input v-model="form.well_known_place" id="evangelismo" :value="'Evangelismo'" type="checkbox" class="form-checkbox peer" name="well_known_place[]"  />
                                        <span class="peer-checked:text-primary">Evangelismo</span>
                                    </label>
                                    <label class="inline-flex" for="casa">
                                        <input v-model="form.well_known_place" id="casa" :value="'Casa de Paz'" type="checkbox" class="form-checkbox peer" name="well_known_place[]" />
                                        <span class="peer-checked:text-primary">Casa de Paz</span>
                                    </label>
                                    <label class="inline-flex" for="youtube">
                                        <input v-model="form.well_known_place" id="youtube" :value="'Youtube'" type="checkbox" class="form-checkbox peer" name="well_known_place[]" />
                                        <span class="peer-checked:text-primary">Youtube</span>
                                    </label>
                                    <label class="inline-flex" for="facebook">
                                        <input v-model="form.well_known_place" id="facebook" :value="'Facebook'" type="checkbox" class="form-checkbox peer" name="well_known_place[]"  />
                                        <span class="peer-checked:text-primary">Facebook</span>
                                    </label>
                                    <label class="inline-flex" for="tiktok">
                                        <input v-model="form.well_known_place" id="tiktok" :value="'Tik tok'" type="checkbox" class="form-checkbox peer" name="well_known_place[]"  />
                                        <span class="peer-checked:text-primary">Tik tok</span>
                                    </label>
                                    <label class="inline-flex" for="instagran">
                                        <input v-model="form.well_known_place" id="instagran" :value="'Instagram'" type="checkbox" class="form-checkbox peer" name="well_known_place[]"  />
                                        <span class="peer-checked:text-primary">Instagram</span>
                                    </label>
                                    <label class="inline-flex" for="cobertura">
                                        <input v-model="form.well_known_place" id="cobertura" :value="'Busque Cobertura'" type="checkbox" class="form-checkbox peer" name="well_known_place[]"  />
                                        <span class="peer-checked:text-primary">Busque Cobertura</span>
                                    </label>
                                    <label class="inline-flex" for="otros">
                                        <input v-model="form.well_known_place" id="otros" :value="'Otros'" type="checkbox" class="form-checkbox peer" name="well_known_place[]" />
                                        <span class="peer-checked:text-primary">Otros</span>
                                    </label>
                                </div>
                            </div>
                        </vue-collapsible>
                    </div>
                    <div class="border border-[#d3d3d3] dark:border-[#1b2e4b] rounded">
                        <div
                            class="p-4 w-full flex items-center justity-between text-white-dark dark:bg-[#1b2e4b]"
                            :class="[{ '!text-primary': accordians3 === 8 },{ 'bg-gray-300/50 cursor-not-allowed' : formIsDisabled}]"
                        >
                            <div class="flex items-center">
                                <img :src="'/img/dar-la-mano.png'" class="ltr:mr-2 rtl:ml-2 text-primary shrink-0 w-6 h-6" />
                                <h4 class="text-sm font-bold text-gray-800 dark:text-white">
                                    PERSONA QUE LE INVITO
                                </h4>
                            </div>
                            <button
                                type="button"
                                class="ltr:ml-auto rtl:mr-auto"
                                @click="accordians3 === 8 ? (accordians3 = null) : (accordians3 = 8)"
                                >
                                    <icon-minus v-if="accordians3 === 8" class="w-5 h-5" />
                                    <icon-plus v-else class="w-5 h-5" />

                            </button>
                        </div>
                        <vue-collapsible :isOpen="accordians3 === 8 && !formIsDisabled">
                            <div class="p-4 text-[13px] border-t border-[#d3d3d3] dark:border-[#1b2e4b]">
                                <label class="inline-flex">
                                    <input v-model="form.contact" type="checkbox" class="form-checkbox text-danger peer" />
                                    <span class="peer-checked:text-danger">Ninguna</span>
                                </label>
                                 <div class="mt-4">
                                    <label for="contact_full_names">Nombre completo:</label>
                                    <input v-model="form.contact_full_names" :disabled="form.contact" id="contact_full_names" type="text" class="form-input" />
                                    <span class="text-red text-[11px] inline-block mt-1">Sí ninguna persona lo invito y llegó solo, coloqué: Ninguna</span>
                                </div>
                            </div>
                        </vue-collapsible>
                    </div>
                </div>
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
                        Guardar
                    </PrimaryButton>
                    <Link :href="route('cigle_member_list')"  class="ml-2 inline-block px-6 py-2.5 bg-green-500 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-green-600 hover:shadow-lg focus:bg-green-600 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-green-700 active:shadow-lg transition duration-150 ease-in-out">Ir al Listado</Link>
                </template>
            </Keypad>
        </template>
    </FormSection>
    <ModalLarge :show="displayModalChildren" :onClose="closeModalChildren" :icon="'/img/comunidad.png'">
        <template #title>Agregar un familiar</template>
        <template #message>Los campos con * son obligatorios</template>
        <template #content>
            <div class="grid lg:grid-cols-6 gap-6">
                <div class="sm:col-span-2">
                    <InputLabel for="ch_document_type_id" value="Tipo documento" />
                    <select v-model="childrenForm.document_type_id" id="ch_document_type_id" class="form-select">
                        <option :value="null" selected>Por favor seleccione</option>
                        <option v-for="(identityDocumentType) in identityDocumentTypes" :value="identityDocumentType.id">{{ identityDocumentType.description }}</option>
                    </select>
                    <InputError :message="childrenForm.errors.document_type_id" class="mt-2" />
                </div>
                <div class="sm:col-span-4 flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
                    <div class="flex-grow">
                        <InputLabel for="ch_number" value="Número" />
                        <TextInput
                            id="ch_number"
                            v-model="childrenForm.number"
                            type="number"
                            focus
                        />
                        <InputError :message="childrenForm.errors.number" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-x-2 mt-4 lg:mt-0 flex-shrink-0">
                        <DarkButton @click="searchFamilyApispe" type="button" class="py-2.5">
                            <icon-loader v-show="apiesFamilyLoading" class="w-4 h-4 mr-2" />
                            RENIEC
                        </DarkButton>
                        <SuccessButton @click="handleBasedatosFamilySearch" class="py-2.5">
                            <icon-loader v-show="displayLoaderBtnSearchFamily" class="w-4 h-4 mr-2" />
                            Buscar
                        </SuccessButton>
                    </div>
                </div>
                <div class="sm:col-span-2">
                    <InputLabel value="Parentesco *" />
                    <relationships v-model="childrenForm.relationship" />
                </div>
                <div class="sm:col-span-2">
                    <InputLabel for="ch_birthdate" value="Fecha de nacimiento *" />
                    <flat-pickr
                        v-model="childrenForm.birthdate"
                        :config="configFlatPickr"
                        class="form-input"
                        placeholder="Selecciona fecha"
                        id="ch_birthdate"
                    />
                    <InputError :message="childrenForm.errors.birthdate" class="mt-2" />
                </div>
                <div class="sm:col-span-2">
                    <InputLabel for="ch_names" value="Nombres *" />
                    <TextInput
                        id="ch_names"
                        v-model="childrenForm.names"
                        type="text"

                    />
                    <InputError :message="childrenForm.errors.names" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-2 ">
                    <InputLabel for="ch_father_lastname" value="Apellido Paterno *" />
                    <TextInput
                        id="ch_father_lastname"
                        v-model="childrenForm.father_lastname"
                        type="text"
                    />
                    <InputError :message="childrenForm.errors.father_lastname" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-2 ">
                    <InputLabel for="ch_mother_lastname" value="Apellido Materno *" />
                    <TextInput
                        id="ch_mother_lastname"
                        v-model="childrenForm.mother_lastname"
                        type="text"
                    />
                    <InputError :message="childrenForm.errors.mother_lastname" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-3">
                    <InputLabel for="ch_gender" value="Genero *" />
                    <div class="space-x-4">
                        <label class="inline-flex">
                            <input v-model="childrenForm.gender" type="radio" value="M" name="ch_gender_radio_g" class="form-radio rounded-none" />
                            <span>Masculino</span>
                        </label>
                        <label class="inline-flex">
                            <input v-model="childrenForm.gender" type="radio" value="F" name="ch_gender_radio_g" class="form-radio text-success rounded-none" />
                            <span>Femenino</span>
                        </label>
                    </div>
                    <InputError :message="childrenForm.errors.gender" class="mt-2" />
                </div>
            </div>
        </template>
        <template #buttons>
            <PrimaryButton @click="addRelationships" type="button">Agregar</PrimaryButton>
        </template>
    </ModalLarge>
</template>
