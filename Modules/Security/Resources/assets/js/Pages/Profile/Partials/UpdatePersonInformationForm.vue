<script setup>
import { Link, useForm, usePage, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import flatPickr from 'vue-flatpickr-component';
import 'flatpickr/dist/flatpickr.css';
import { Spanish } from 'flatpickr/dist/l10n/es.js';
import InputError from '@/Components/InputError.vue';
import Multiselect from '@suadelabs/vue3-multiselect';
import '@suadelabs/vue3-multiselect/dist/vue3-multiselect.css';
import IconLinkedin from '@/Components/vristo/icon/icon-linkedin.vue';
import IconTwitter from '@/Components/vristo/icon/icon-twitter.vue';
import IconFacebook from '@/Components/vristo/icon/icon-facebook.vue';
import IconInstagram from '@/Components/vristo/icon/icon-instagram.vue';
import Swal2 from 'sweetalert2';

    const props = defineProps({
        person: {
            type: Object,
            default: () => ({})
        },
        document_types: {
            type: Object,
            default: () => ({})
        },
        ubigeo: {
            type: Object,
            default: () => ({})
        },
        countries: {
            type: [Array, Object],
            default: () => []
        }
    });

    const user_email = usePage().props.auth.user.email;

    const form = useForm({
        id: props.person ? props.person.id : null,
        document_type_id: props.person ? props.person.document_type_id : 99,
        short_name: props.person ? props.person.short_name : null,
        full_name: props.person ? props.person.full_name : null,
        description: props.person ? props.person.description : null,
        number: props.person ? props.person.number : null,
        telephone: props.person ? props.person.telephone : null,
        email: (props.person && props.person.email) ? props.person.email : user_email,
        image: props.person ? props.person.image : null,
        address: props.person ? props.person.address : null,
        ubigeo: props.person ? {"district_id" : props.person.ubigeo, "name_city" : props.person.ubigeo_description} : null,
        birthdate: props.person ? props.person.birthdate : null,
        names: props.person ? props.person.names : null,
        father_lastname: props.person ? props.person.father_lastname : null,
        mother_lastname: props.person ? props.person.mother_lastname : null,
        ocupacion: props.person ? props.person.ocupacion : null,
        presentacion: props.person ? props.person.presentacion : null,
        gender: props.person ? props.person.gender : 'M',
        status: props.person ? props.person.status : null,
        social_networks: props.person ? props.person.social_networks : null,
        foreign_country_id: props.person ? props.person.foreign_country_id : null,
        foreign_state: props.person ? props.person.foreign_state : '',
        foreign_city: props.person ? props.person.foreign_city : '',
    });
    const socialData = ref({});

    const isForeignDocument = computed(() => {
        return String(form.document_type_id) === '0';
    });

    const countryOptions = computed(() => {
        const list = Array.isArray(props.countries)
            ? props.countries
            : Object.values(props.countries || {});
        return list.map((c) => ({
            value: c?.id,
            label: c?.description ?? '',
        }));
    });
    const basic = ref({
        dateFormat: 'Y-m-d',
        locale: Spanish,
    });

    onMounted(()=>{
        if(props.person){
            socialData.value = {
                facebook: props.person.social_networks ? JSON.parse(props.person.social_networks).facebook : null,
                instagram: props.person.social_networks ? JSON.parse(props.person.social_networks).instagram : null,
                linkedin: props.person.social_networks ? JSON.parse(props.person.social_networks).linkedin : null,
                twitter: props.person.social_networks ? JSON.parse(props.person.social_networks).twitter : null,
            }
        }

    });



  const submitForm = async () => {
    if (!isForeignDocument.value) {
      savePerson();
      return;
    }

    const countryLabel = countryOptions.value.find(c => c.value === form.foreign_country_id)?.label || 'No seleccionado';
    const stateLabel = form.foreign_state || 'No ingresado';
    const cityLabel = form.foreign_city || 'No ingresado';

    const result = await Swal2.fire({
        title: 'Confirmar ubicación',
        html: `
            <p style="text-align:left; margin-bottom: 8px;">¿Los datos de ubicación son correctos?</p>
            <div style="text-align:left; background: #f8f9fa; padding: 12px; border-radius: 8px;">
                <p style="margin: 4px 0;"><strong>País:</strong> ${countryLabel}</p>
                <p style="margin: 4px 0;"><strong>Departamento/Estado:</strong> ${stateLabel}</p>
                <p style="margin: 4px 0;"><strong>Ciudad:</strong> ${cityLabel}</p>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, guardar',
        cancelButtonText: 'Cancelar',
        customClass: 'sweet-alerts',
    });

    if (result.isConfirmed) {
      savePerson();
    }
  };

  const savePerson = () => {
    form.social_networks = socialData.value;
    form.post(route('person_information_update'), {
        errorBag: 'savePerson',
        preserveScroll: true,
        onSuccess: () => {
            showMessage('Actualizado con éxito.');
        },
    });
  }

  const showMessage = (msg = '', type = 'success') => {
        const toast = Swal2.mixin({
            toast: true,
            position: 'top',
            showConfirmButton: false,
            timer: 3000,
            customClass: { container: 'toast' },
        });
        toast.fire({
            icon: type,
            title: msg,
            padding: '10px 20px',
        });
    };
</script>
<template>
    <form @submit.prevent="submitForm">
        <div class="border border-[#ebedf2] dark:border-[#191e3a] rounded-md p-4 mb-5 bg-white dark:bg-[#0e1726]">
            <h6 class="text-lg font-bold mt-1">Información Personal</h6>
            <p class="mb-5 text-sm text-gray-600 dark:text-gray-400">
                Actualice la información del perfil y la dirección de correo electrónico de su cuenta.
            </p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                    <label for="names">Nombres</label>
                    <input id="names" type="text" v-model="form.names" class="form-input" required />
                    <InputError class="mt-2" :message="form.errors.names" />
                </div>
                <div>
                    <label for="father_lastname">Apellido paterno</label>
                    <input id="father_lastname" type="text" v-model="form.father_lastname" class="form-input" required />
                    <InputError class="mt-2" :message="form.errors.father_lastname" />
                </div>
                <div>
                    <label for="mother_lastname">Apellido materno</label>
                    <input id="mother_lastname" type="text" v-model="form.mother_lastname" class="form-input" required />
                    <InputError class="mt-2" :message="form.errors.mother_lastname" />
                </div>
                <div>
                    <label for="mother_lastname">Tipo de Identificación</label>
                    <select v-model="form.document_type_id" class="form-select text-white-dark" required>
                        <option value="99">Seleccionar</option>
                        <option v-for="(document_type, index) in document_types" :value="document_type.id">{{ document_type.description }}</option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.document_type_id" />
                </div>
                <div>
                    <label for="mother_lastname">Número de Identificación</label>
                    <input id="mother_lastname" type="text" v-model="form.number" class="form-input" required />
                    <InputError class="mt-2" :message="form.errors.number" />
                </div>
                <div>
                    <label for="birthdate">Fecha de nacimiento</label>
                    <flat-pickr v-model="form.birthdate" class="form-input" :config="basic"></flat-pickr>
                    <InputError class="mt-2" :message="form.errors.birthdate" />
                </div>
                <div>
                    <label for="telephone">Teléfono</label>
                    <input v-model="form.telephone" class="form-input" />
                    <InputError class="mt-2" :message="form.errors.telephone" />
                </div>
                <div>
                    <label for="email">Correo electrónico</label>
                    <input v-model="form.email" class="form-input bg-gray-100" disabled />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>
                <div>
                    <label for="address">Dirección</label>
                    <input v-model="form.address" class="form-input" />
                    <InputError class="mt-2" :message="form.errors.address" />
                </div>
                <!-- UBIGEO PERUANO -->
                <template v-if="!isForeignDocument">
                    <div class="col-span-3 sm:col-span-2 md:col-span-2">
                        <label for="ubigeo">Ciudad</label>
                        <multiselect
                            id="ubigeo"
                            v-model="form.ubigeo"
                            :options="ubigeo"
                            class="custom-multiselect"
                            :searchable="true"
                            placeholder="Buscar ciudad"
                            selected-label="seleccionado"
                            select-label="Elegir"
                            deselect-label="Quitar"
                            label="name_city"
                            track-by="district_id"
                            ></multiselect>
                        <InputError class="mt-2" :message="form.errors.ubigeo" />
                    </div>
                </template>

                <!-- UBICACIÓN EXTRANJERA -->
                <template v-else>
                    <div>
                        <label for="foreign_country_id">País</label>
                        <select v-model="form.foreign_country_id" class="form-select text-white-dark" required>
                            <option value="">Seleccionar país</option>
                            <option v-for="country in countries" :value="country.id">{{ country.description }}</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.foreign_country_id" />
                    </div>
                    <div>
                        <label for="foreign_state">Depto./Estado</label>
                        <input v-model="form.foreign_state" type="text" class="form-input" placeholder="Ej: California" required />
                        <InputError class="mt-2" :message="form.errors.foreign_state" />
                    </div>
                    <div>
                        <label for="foreign_city">Ciudad</label>
                        <input v-model="form.foreign_city" type="text" class="form-input" placeholder="Ej: Los Ángeles" required />
                        <InputError class="mt-2" :message="form.errors.foreign_city" />
                    </div>
                </template>
                <div>
                    <label for="gender">Sexo</label>
                    <select id="gender" v-model="form.gender" class="form-select text-white-dark" required>
                        <option value="M">Masculino</option>
                        <option value="F">Femenino</option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.gender" />
                </div>
                <div>
                    <label for="description">Descripción Personal</label>
                    <textarea v-model="form.description" id="description" rows="3" class="form-textarea"></textarea>
                    <InputError class="mt-2" :message="form.errors.description" />
                </div>
                <div>
                    <label for="ocupacion">Ocupación Profesional</label>
                    <textarea v-model="form.ocupacion" id="ocupacion" rows="3" class="form-textarea"></textarea>
                    <InputError class="mt-2" :message="form.errors.ocupacion" />
                </div>
                <div>
                    <label for="presentacion">Presentación Profesional</label>
                    <textarea v-model="form.presentacion" id="presentacion" rows="3" class="form-textarea"></textarea>
                    <InputError class="mt-2" :message="form.errors.presentacion" />
                </div>
            </div>
            <div class="sm:col-span-2 mt-3 w-full flex justify-end">
                <progress v-if="form.progress" :value="form.progress.percentage" max="100" class="mr-4" style="height: 10px;">
                    {{ form.progress.percentage }}%
                </progress>
                <button type="submit" :disabled="form.processing" class="btn btn-primary">Guardar</button>
            </div>
        </div>
        <div class="border border-[#ebedf2] dark:border-[#191e3a] rounded-md p-4 bg-white dark:bg-[#0e1726]">
            <h6 class="text-lg font-bold mb-5">Redes Sociales</h6>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="flex">
                    <div class="bg-[#eee] flex justify-center items-center rounded px-3 font-semibold dark:bg-[#1b2e4b] ltr:mr-2 rtl:ml-2">
                        <icon-linkedin class="w-5 h-5" />
                    </div>
                    <input v-model="socialData.linkedin" type="text" class="form-input" />
                </div>
                <div class="flex">
                    <div class="bg-[#eee] flex justify-center items-center rounded px-3 font-semibold dark:bg-[#1b2e4b] ltr:mr-2 rtl:ml-2">
                        <icon-twitter class="w-5 h-5" />
                    </div>
                    <input v-model="socialData.twitter" type="text" class="form-input" />
                </div>
                <div class="flex">
                    <div class="bg-[#eee] flex justify-center items-center rounded px-3 font-semibold dark:bg-[#1b2e4b] ltr:mr-2 rtl:ml-2">
                        <icon-facebook class="w-5 h-5" />
                    </div>
                    <input v-model="socialData.facebook" type="text" class="form-input" />
                </div>
                <div class="flex">
                    <div class="bg-[#eee] flex justify-center items-center rounded px-3 font-semibold dark:bg-[#1b2e4b] ltr:mr-2 rtl:ml-2">
                        <icon-instagram />
                    </div>
                    <input v-model="socialData.instagram"type="text" class="form-input" />
                </div>
            </div>
        </div>
    </form>
</template>
