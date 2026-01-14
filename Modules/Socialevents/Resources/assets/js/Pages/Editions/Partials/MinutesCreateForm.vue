<script setup>
    import { useForm, Link } from '@inertiajs/vue3';
    import FormSection from '@/Components/FormSection.vue';
    import InputError from '@/Components/InputError.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import TextInput from '@/Components/TextInput.vue';
    import Keypad from '@/Components/Keypad.vue';
    import Swal2 from 'sweetalert2';
    import { ref, computed, onMounted } from 'vue';
    import { ConfigProvider, Select, TreeSelect } from 'ant-design-vue';
    import Multiselect from '@suadelabs/vue3-multiselect';
    import '@suadelabs/vue3-multiselect/dist/vue3-multiselect.css';
    import iconLoader from '@/Components/vristo/icon/icon-loader.vue';
    import esES from 'ant-design-vue/es/locale/es_ES';
    import FlatPickr from 'vue-flatpickr-component';
    import 'flatpickr/dist/flatpickr.css';
    import { Spanish } from "flatpickr/dist/l10n/es.js"
    import Editor from '@tinymce/tinymce-vue';
    import iconUpload from '@/Components/vristo/icon/icon-upload.vue';
    import SearchClients from '../../Teams/Partials/SearchClients.vue';
import IconX from '@/Components/vristo/icon/icon-x.vue';

    const props = defineProps({
        edicion: {
            type: Object,
            default: () => ({}),
        },
        typeSessions: {
            type: Array,
            default: () => ([]),
        },
        tinyApiKey: {
            type: String,
            default: null,
        },
        ubigeo: {
            type: Object,
            default: () => ({}),
        },
        documentTypes: {
            type: Object,
            default: () => ({}),
        }
    });

    // Obtiene la fecha y hora actual en formato YYYY-MM-DD HH:mm
    const now = new Date();
    const currentDateTime = now.getFullYear() + '-' +
    String(now.getMonth() + 1).padStart(2, '0') + '-' +
    String(now.getDate()).padStart(2, '0') + ' ' +
    String(now.getHours()).padStart(2, '0') + ':' +
    String(now.getMinutes()).padStart(2, '0');

    const basic = ref({
        enableTime: true,           // Activa la selección de tiempo
        time_24hr: true,            // Formato de 24 horas
        dateFormat: 'Y-m-d H:i',    // Año-Mes-Día Hora:Minutos
        locale: Spanish,
    });

    const form = useForm({
        edition_id: props.edicion.id,
        minutes_subject: '',
        minutes_type: 'delegados',
        minutes_code: '',
        meeting_date: currentDateTime,
        participants: [],
        minutes_body: '',
        status: 'pending',
        sql_errors: null
    });

    const createNow = () => {
        form.post(route('even_ediciones_accordance_store'), {
            forceFormData: true,
            errorBag: 'createNow',
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
            },
        });
    }

    const displayModalClientSearch = ref(false);

    const openModalClientSearch = () => {
        displayModalClientSearch.value = true;
    }
    const closeModalClientSearch = () => {
        displayModalClientSearch.value = false;
    }
    const getDataClient = async (data) => {
        let newPerson = {
            person_id: data.id,
            number: data.number,
            full_name: data.full_name
        };
        form.participants.push(newPerson);
        displayModalClientSearch.value = false;
    }

    const removeParticipant = (index) => {
        form.participants.splice(index, 1);
    }
</script>

<template>
    <FormSection @submitted="createNow" class="">
        <template #title>
            Acta Detalles
        </template>

        <template #description>
            Crear Acta, Los campos con * son obligatorios
        </template>

        <template #form>
            <ConfigProvider :locale="esES">

                <div class="col-span-1 md:col-span-4">
                    <InputLabel for="minutes_subject" value="Asunto o Motivo de la Reunión *" />
                    <TextInput
                        id="minutes_subject"
                        v-model="form.minutes_subject"
                        type="text"
                        class="block w-full mt-1"
                        placeholder="Ej: Resolución de conflictos jornada 5..."
                        required
                    />
                    <InputError :message="form.errors.minutes_subject" class="mt-2" />
                </div>

                <div class="col-span-1 md:col-span-2">
                    <InputLabel for="minutes_type" value="Tipo de Sesión *" />
                    <select
                        id="minutes_type"
                        v-model="form.minutes_type"
                        class="form-select"
                    >
                        <template v-for="type in typeSessions">
                            <option :value="type" >
                                {{
                                    type == 'delegados' ? 'Reunión de Delegados' :
                                    type == 'extraordinaria' ? 'Sesión Extraordinaria' :
                                    type == 'ordinaria' ? 'Sesión Ordinaria' :
                                    type == 'comite' ? 'Comité de Disciplina' : 'Reunion General'
                                }}
                            </option>
                        </template>
                    </select>
                    <InputError :message="form.errors.minutes_type" class="mt-2" />
                </div>

                <div class="col-span-1 md:col-span-3">
                    <InputLabel for="minutes_code" value="Código Interno / Correlativo" />
                    <TextInput
                        id="minutes_code"
                        v-model="form.minutes_code"
                        type="text"
                        class="block w-full mt-1 bg-gray-50"
                        placeholder="AUTOMATICO"
                        :disabled="true"
                    />
                    <InputError :message="form.errors.minutes_code" class="mt-2" />
                </div>

                <div class="col-span-1 md:col-span-3">
                    <InputLabel for="meeting_date" value="Fecha y Hora de la Reunión *" />
                    <flat-pickr id="meeting_date" v-model="form.meeting_date" class="form-input block w-full mt-1" :config="basic"></flat-pickr>
                    <InputError :message="form.errors.meeting_date" class="mt-2" />
                </div>

                <hr class="col-span-full border-gray-200 dark:border-gray-700" />

                <div class="col-span-full">
                    <div class="flex items-center justify-between mb-2">
                        <InputLabel value="Participantes y Asistentes" />
                        <button
                            type="button"
                            @click="openModalClientSearch"
                            class="btn btn-danger uppercase text-xs"
                        >
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Añadir Asistente
                        </button>
                    </div>
                    <div>
                        <SearchClients
                            :display="displayModalClientSearch"
                            :closeModalClient="closeModalClientSearch"
                            @clientId="getDataClient"
                            :ubigeo="ubigeo"
                            :documentTypes="documentTypes"
                        />
                    </div>
                    <div class="space-y-3 p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-800">
                        <div v-for="(person, index) in form.participants" :key="index" class="flex gap-3 items-center animate-fadeIn">
                            <div class="flex-none w-24">
                                <TextInput
                                    v-model="person.number"
                                    placeholder="ID/DNI"
                                    class="w-full text-xs"
                                />
                            </div>
                            <div class="flex-1">
                                <TextInput
                                    v-model="person.full_name"
                                    placeholder="Nombre completo"
                                    class="w-full text-xs"
                                />
                            </div>
                            <button
                                type="button"
                                @click="removeParticipant(index)"
                                class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-full transition"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>

                        <div v-if="form.participants.length === 0" class="text-center py-6">
                            <p class="text-sm text-gray-400 dark:text-gray-500 italic">No hay participantes registrados en esta acta.</p>
                        </div>
                    </div>
                </div>

                <div class="col-span-full">
                    <InputLabel for="minutes_body" value="Desarrollo de la Reunión y Acuerdos *" />
                    <Editor
                        placeholder="Puntos tratados..."
                        id="minutes_body"
                        :api-key="tinyApiKey"
                        v-model="form.minutes_body"
                        :init="{
                            plugins: 'anchor autolink charmap codesample emoticons link lists media searchreplace table visualblocks wordcount',
                            language: 'es',
                        }"

                    />
                    <InputError :message="form.errors.minutes_body" class="mt-2" />
                </div>

                <div class="col-span-full">
                    <InputLabel value="Estado del Acta" />
                    <div class="mt-2 grid grid-cols-2 gap-4">
                        <div
                            @click="form.status = 'pending'"
                            :class="form.status === 'pending' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-200 dark:border-gray-700'"
                            class="cursor-pointer border-2 p-3 rounded-lg flex items-center transition"
                        >
                            <input type="radio" v-model="form.status" value="pending" class="text-indigo-600 focus:ring-indigo-500">
                            <div class="ml-3">
                                <p class="text-sm font-bold text-gray-700 dark:text-gray-200">Abierto</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    Permite ediciones. El documento se considera <strong>no oficial</strong> hasta que se suba el acta firmada por los asistentes.
                                </p>
                            </div>
                        </div>

                        <div
                            @click="form.status = 'accepted'"
                            :class="form.status === 'accepted' ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : 'border-gray-200 dark:border-gray-700'"
                            class="cursor-pointer border-2 p-3 rounded-lg flex items-center transition"
                        >
                            <input type="radio" v-model="form.status" value="accepted" class="text-red-600 focus:ring-red-500">
                            <div class="ml-3">
                                <p class="text-sm font-bold text-gray-700 dark:text-gray-200">Cerrado</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    Bloquea ediciones. Use este estado solo cuando el acta ya esté firmada y lista para su archivo definitivo.
                                </p>
                            </div>
                        </div>
                    </div>
                    <InputError :message="form.errors.status" class="mt-2" />
                </div>
                <div v-if="form.errors.sql_errors" class="mt-6 col-span-6">
                    <div class="flex items-center p-3.5 rounded text-danger bg-danger-light dark:bg-danger-dark-light">
                        <span class="ltr:pr-2 rtl:pl-2">
                            <strong class="ltr:mr-1 rtl:ml-1">Error!</strong>
                            {{ form.errors.sql_errors }}
                        </span>
                        <button @click="form.setError('sql_errors', null)" type="button" class="ltr:ml-auto rtl:mr-auto hover:opacity-80">
                            <IconX class="w-5 h-5" />
                        </button>
                    </div>
                </div>
            </ConfigProvider>
        </template>

        <template #actions>
            <progress v-if="form.progress" :value="form.progress.percentage" max="100" class="mr-2">
                {{ form.progress.percentage }}%
            </progress>
            <Keypad>
                <template #botones>
                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        <icon-loader v-show="form.processing" class="w-4 h-4 animate-spin mr-1" />
                        Guardar
                    </PrimaryButton>
                    <Link :href="route('even_ediciones_actas_listado', edicion.id)"  class="ml-2 inline-block px-6 py-2.5 bg-green-500 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-green-600 hover:shadow-lg focus:bg-green-600 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-green-700 active:shadow-lg transition duration-150 ease-in-out">Ir al Listado</Link>
                </template>
            </Keypad>
        </template>
    </FormSection>
</template>
