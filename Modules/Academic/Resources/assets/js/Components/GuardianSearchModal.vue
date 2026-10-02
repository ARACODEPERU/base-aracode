<script setup>
    import { useForm } from '@inertiajs/vue3';
    import ModalLargeX from '@/Components/ModalLargeX.vue';
    import { ref } from 'vue';
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
    import iconCompany from '@/Components/vristo/icon/icon-company.vue';
    import iconLoader from '@/Components/vristo/icon/icon-loader.vue';

    const props = defineProps({
        documentTypes: {
            type: Array,
            default: () => []
        },
        ubigeo: {
            type: Array,
            default: () => []
        },
        display: {
            type: Boolean,
            default: false
        },
        onClose: {
            type: Function,
            default: () => ({})
        }
    })

    const emit = defineEmits(['selected']);

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
        is_client: true,
        estado: null,
        condicion: null
    });

    const disabledBtnSelect = ref(true);
    const person = ref({});
    const saving = ref(false);

    const resetForm = () => {
        form.reset();
        form.document_type = 1;
        disabledBtnSelect.value = true;
        person.value = {};
    };

    const closeGuardianModal = () => {
        resetForm();
        props.onClose();
    }

    const searchPerson = () => {
        if (!form.number) {
            Swal2.fire({
                title: 'Información Importante',
                text: 'Ingrese un número de documento',
                icon: 'info',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
            return;
        }
        axios.post(route('search_person_number'), form).then((res) => {
            if (res.data.status) {
                form.id = res.data.person.id;
                form.number = res.data.person.number;
                form.telephone = res.data.person.telephone;
                form.full_name = res.data.person.full_name;
                form.email = res.data.person.email;
                form.address = res.data.person.address;
                form.ubigeo_description = res.data.person.city;
                form.ubigeo = res.data.ubigeo;
                disabledBtnSelect.value = false;
                person.value = res.data.person;
            } else {
                form.errors.number = res.data.number;
                Swal2.fire({
                    title: 'Información Importante',
                    text: res.data.alert,
                    icon: 'info',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                disabledBtnSelect.value = true;
            }
        });
    }

    const savePerson = () => {
        saving.value = true;
        axios.post(route('save_person_update_create'), form).then((res) => {
            disabledBtnSelect.value = false;
            Swal2.fire({
                title: 'Enhorabuena',
                text: 'Se registró correctamente',
                icon: 'success',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
            person.value = res.data;
        }).catch(error => {
            const validationErrors = error.response.data.errors;
            if (validationErrors && validationErrors.number) {
                form.setError('number', validationErrors.number[0]);
            }
            if (validationErrors && validationErrors.telephone) {
                form.setError('telephone', validationErrors.telephone[0]);
            }
            if (validationErrors && validationErrors.full_name) {
                form.setError('full_name', validationErrors.full_name[0]);
            }
            if (validationErrors && validationErrors.email) {
                form.setError('email', validationErrors.email[0]);
            }
            if (validationErrors && validationErrors.address) {
                form.setError('address', validationErrors.address[0]);
            }
            if (validationErrors && validationErrors.ubigeo) {
                form.setError('ubigeo', validationErrors.ubigeo[0]);
            }
        }).finally(() => {
            saving.value = false;
        });
    }

    const selectPerson = () => {
        emit('selected', person.value);
        resetForm();
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
        <ModalLargeX :show="display" :onClose="closeGuardianModal" :icon="'/img/comunidad.png'">
            <template #title>
                Apoderado
            </template>
            <template #message>
                Buscar por número de documento (RENIEC/SUNAT) o registrar nuevo
            </template>
            <template #content>
                <div class="grid grid-cols-4 gap-4">
                    <div class="col-span-6 sm:col-span-1">
                        <InputLabel value="Tipo de Documento" />
                        <select class="form-select text-white-dark" v-model="form.document_type">
                            <option value="">Seleccionar</option>
                            <template v-for="(documentType, index) in documentTypes" :key="index">
                                <option :value="documentType.id">{{ documentType.description }}</option>
                            </template>
                        </select>
                        <InputError :message="form.errors.document_type" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-1">
                        <InputLabel for="guardian_number" value="Número de Doc." />
                        <TextInput id="guardian_number" v-model="form.number" type="number" autofocus />
                        <InputError :message="form.errors.number" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-2">
                        <InputLabel v-if="form.document_type == 6" for="guardian_full_name" value="Razón Social" />
                        <InputLabel v-else for="guardian_full_name" value="Nombres" />
                        <TextInput id="guardian_full_name" v-model="form.full_name" type="text" />
                        <InputError :message="form.errors.full_name" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-1">
                        <InputLabel for="guardian_telephone" value="Teléfono (Opcional)" />
                        <TextInput id="guardian_telephone" v-model="form.telephone" type="text" />
                        <InputError :message="form.errors.telephone" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-1">
                        <InputLabel for="guardian_email" value="Email (Opcional)" />
                        <TextInput id="guardian_email" v-model="form.email" type="email" />
                        <InputError :message="form.errors.email" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-2">
                        <InputLabel for="guardian_city" value="Ciudad" />
                        <multiselect
                            id="guardian_city"
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
                        <InputLabel for="guardian_address" value="Dirección" />
                        <TextInput id="guardian_address" v-model="form.address" type="text" />
                        <InputError :message="form.errors.address" class="mt-2" />
                    </div>
                    <div v-if="form.condicion && form.estado" class="col-span-6 sm:col-span-2">
                        <div class="flex items-center gap-6">
                            <div>
                                <InputLabel value="Condicion" />
                                <span class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800/30 dark:text-yellow-500">{{ form.condicion }}</span>
                            </div>
                            <div>
                                <InputLabel value="Estado" />
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
                <RedButton @click="searchPerson()">Buscar</RedButton>
                <PrimaryButton @click="savePerson()" :disabled="saving">
                    <icon-loader v-if="saving" class="w-4 h-4 animate-spin mr-1" />
                    Guardar
                </PrimaryButton>
                <GreenButton
                    :disabled="disabledBtnSelect"
                    @click="selectPerson"
                >
                    <font-awesome-icon :icon="faShareFromSquare" />
                    Seleccionar
                </GreenButton>
            </template>
        </ModalLargeX>
    </div>
</template>
