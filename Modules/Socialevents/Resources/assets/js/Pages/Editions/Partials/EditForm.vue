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
    import 'cropperjs/dist/cropper.css';
    import CropperImage from '@/Components/CropperImage.vue';
    import Multiselect from '@suadelabs/vue3-multiselect';
    import '@suadelabs/vue3-multiselect/dist/vue3-multiselect.css';
    import iconLoader from '@/Components/vristo/icon/icon-loader.vue';
    import esES from 'ant-design-vue/es/locale/es_ES';
    import FlatPickr from 'vue-flatpickr-component';
    import 'flatpickr/dist/flatpickr.css';
    import { Spanish } from "flatpickr/dist/l10n/es.js"
    import Editor from '@tinymce/tinymce-vue';
    import iconUpload from '@/Components/vristo/icon/icon-upload.vue';

    const props = defineProps({
        eventos: {
            type: Object,
            default: () => ({}),
        },
        formats: {
            type: Object,
            default: () => ({}),
        },
        tinyApiKey: {
            type: String,
            default: () => ({}),
        },
        edicion: {
            type: Object,
            default: () => ({}),
        }
    });

    const formatDateToYYYYMMDD = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0'); // Meses son 0-11
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    const currentYear = new Date().getFullYear();
    const dateRangeSelected = ref(null);
    const today = new Date();
    const todayFormatted = formatDateToYYYYMMDD(today);


    const basic = ref({
        dateFormat: 'Y-m-d',
        mode: 'range',
        locale: Spanish,
        defaultDate: [todayFormatted, todayFormatted]
    });

    const form = useForm({
        id: props.edicion.id,
        event_id: props.edicion.event_id,
        event_name: props.edicion.evento.title,
        year: props.edicion.year,
        name: props.edicion.name,
        start_date: props.edicion.start_date,
        end_date: props.edicion.end_date,
        competition_format: props.edicion.competition_format,
        score_points_win: props.edicion.score_points_win,
        score_points_draw: props.edicion.score_points_draw,
        score_points_loss: props.edicion.score_points_loss,
        inscription_fee: props.edicion.inscription_fee,
        min_players_per_team: props.edicion.min_players_per_team,
        max_players_per_team: props.edicion.max_players_per_team,
        prize_details: props.edicion.prize_details,
        details: props.edicion.details,
        status: props.edicion.status == 1 ? true : false,
        name_database_file: props.edicion.name_database_file,
        path_database_file: props.edicion.path_database_file,
        file: null
    });

    const updateEdition = () => {
        form.post(route('even_ediciones_update'), {
            forceFormData: true,
            errorBag: 'updateEdition',
            preserveScroll: true,
            onSuccess: () => {
                Swal2.fire({
                    title: 'Enhorabuena',
                    text: 'Actualizado correctamente',
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
            },
        });
    }

    const eventSelected = ref({
        id: props.edicion.event_id,
        title: props.edicion.evento.title
    });

    const selectEvent = () => {
        form.event_name = eventSelected.value.title;
        form.event_id = eventSelected.value.id;
    }

    const years = ref([]);

    const generateYears = () => {
        const startYear = currentYear - 10;
        const endYear = currentYear + 2;

        years.value = [];

        for (let year = startYear; year <= endYear; year++) {
            years.value.push(year);
        }
    };

    onMounted(() => {
        generateYears();
    });

    const fileInput = ref(null);

    const openFileExplorer = () => {
        fileInput.value.click();
    };

    const onFileSelected = (event) => {
        const file = event.target.files[0];

        if (!file) return;

        form.file = file;
        form.name_database_file = file.name;
    };

    const removeFile = () => {
        form.file = null;
        form.name_database_file = null;
        fileInput.value.value = '';
    };

    const selectDates = (selectedDates) => {
        if (!selectedDates.length) return;

        form.start_date = selectedDates[0]
            .toISOString()
            .split('T')[0];

        form.end_date = selectedDates[1]
            ? selectedDates[1].toISOString().split('T')[0]
            : null;
    };
</script>

<template>
    <FormSection @submitted="updateEdition" class="">
        <template #title>
            Edicion Detalles
        </template>

        <template #description>
            Modificar Edicion, Los campos con * son obligatorios
        </template>

        <template #form>
            <ConfigProvider :locale="esES">
                <div class="sm:col-span-4">
                    <div class="grid sm:grid-cols-6 gap-6">
                        <div class="col-span-6">
                            <InputLabel for="event_name" value="Evento *" class="mb-1" />
                            <Multiselect
                                id="event_name"
                                :model-value="eventSelected"
                                v-model="eventSelected"
                                :options="eventos"
                                class="custom-multiselect"
                                :searchable="true"
                                placeholder="Buscar"
                                selected-label="seleccionado"
                                select-label="Elegir"
                                deselect-label="Quitar"
                                label="title"
                                track-by="id"
                                @update:model-value="selectEvent"
                            ></Multiselect>
                            <InputError :message="form.errors.event_name" class="mt-2" />
                        </div>
                        <div class="sm:col-span-2">
                            <InputLabel for="anio" value="Año a realizar *" class="mb-1" />
                            <select v-model="form.year" class="form-select">
                                <option
                                    v-for="year in years"
                                    :key="year"
                                    :value="year"
                                    id="anio"
                                >
                                    {{ year }}
                                </option>
                            </select>
                            <InputError :message="form.errors.year" class="mt-2" />
                        </div>
                        <div class="sm:col-span-4">
                            <InputLabel for="name" value="Nombre *" class="mb-1" />
                            <TextInput v-model="form.name" />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>
                        <div class="sm:col-span-2">
                            <InputLabel for="date_range" value="Fechas desde - hasta *" class="mb-1" />
                            <flat-pickr id="date_range" v-model="dateRangeSelected" @on-change="selectDates" class="form-input" :config="basic"></flat-pickr>
                            <InputError :message="form.errors.start_date" class="mt-2" />
                        </div>
                        <div class="sm:col-span-2">
                            <InputLabel for="fforma" value="Formato de la competencia*" class="mb-1" />
                            <select v-model="form.competition_format" class="form-select">
                                <option
                                    v-for="fforma in formats"
                                    :key="fforma"
                                    :value="fforma"
                                    id="fforma"
                                >
                                    {{
                                        fforma == 'round_robin' ? 'Todos contra todos' :
                                        fforma == 'group_stage_and_playoffs' ? 'Fase de grupos' :
                                        'Eliminación simple'
                                    }}
                                </option>
                            </select>
                            <InputError :message="form.errors.competition_format" class="mt-2" />
                        </div>
                        <div class="sm:col-span-6">
                            <InputLabel for="details" value="Detalles" class="mb-1" />
                            <Editor
                                id="details"
                                :api-key="tinyApiKey"
                                v-model="form.details"
                                :init="{
                                    plugins: 'anchor autolink charmap codesample emoticons link lists media searchreplace table visualblocks wordcount',
                                    language: 'es',
                                }"

                            />
                            <InputError :message="form.errors.details" class="mt-2" />
                        </div>
                    </div>
                </div>
                <div class="sm:col-span-2">
                    <div class="grid sm:grid-cols-3 gap-6">
                        <div>
                            <InputLabel for="score_points_win" value="Puntos al ganador *" class="mb-1" />
                            <input v-model="form.score_points_win" id="score_points_win" class="form-input text-right" v-maska="'##'" maxlength="2" type="text" />
                            <InputError :message="form.errors.score_points_win" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="score_points_draw" value="Puntos empate *" class="mb-1" />
                            <input v-model="form.score_points_draw" id="score_points_draw" class="form-input text-right" v-maska="'##'" maxlength="2" type="text" />
                            <InputError :message="form.errors.score_points_draw" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="score_points_loss" value="Puntos perdedor *" class="mb-1" />
                            <input v-model="form.score_points_loss" id="score_points_loss" class="form-input text-right" placeholder="__" v-maska="'##'" maxlength="2" type="text" />
                            <InputError :message="form.errors.score_points_loss" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="inscription_fee" value="Precio de inscripción *" class="mb-1" />
                            <input v-model="form.inscription_fee" id="inscription_fee" class="form-input text-right" v-maska="'#####'" placeholder="_____" maxlength="5" type="text" />
                            <InputError :message="form.errors.inscription_fee" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="min_players_per_team" value="Cantidad jugadores en campo *" class="mb-1" />
                            <input v-model="form.min_players_per_team" id="min_players_per_team" class="form-input text-right" v-maska="'###'" placeholder="___" maxlength="3" type="text" />
                            <InputError :message="form.errors.min_players_per_team" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="max_players_per_team" value="Cantidad jugadores por equipo" class="mb-1" />
                            <input v-model="form.max_players_per_team" id="max_players_per_team" class="form-input text-right" v-maska="'###'" placeholder="___" maxlength="3" type="text" />
                            <InputError :message="form.errors.max_players_per_team" class="mt-2" />
                        </div>
                        <div class="sm:col-span-3">
                            <InputLabel for="prize_details" value="Premios" class="mb-1" />
                            <textarea v-model="form.prize_details" id="prize_details" class="form-textarea" rows="4"></textarea>
                            <InputError :message="form.errors.prize_details" class="mt-2" />
                        </div>
                        <div class="sm:col-span-3">
                            <InputLabel for="path_database_file" value="Bases de la competición (Archivos: PDF)" class="mb-1" />
                            <div class="space-y-3">
                                <!-- Botón subir archivo -->
                                <button
                                    type="button"
                                    @click="openFileExplorer"
                                    class="btn btn-outline-success uppercase"
                                >
                                    <icon-upload class="w-4 h-4 mr-1" />
                                    <span>Elejir archivo</span>
                                </button>

                                <!-- Input oculto -->
                                <input
                                    ref="fileInput"
                                    type="file"
                                    class="hidden"
                                    @change="onFileSelected"
                                    accept="application/pdf"
                                />

                                <!-- Archivo seleccionado -->
                                <div
                                    v-if="form.name_database_file"
                                    class="flex items-center justify-between gap-3 p-3 border rounded-lg bg-gray-50 dark:bg-neutral-800 dark:border-neutral-700"
                                >
                                    <!-- Izquierda -->
                                    <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                            <path fill="currentColor" d="M224.6 12.8c56.2-56.2 147.4-56.2 203.6 0s56.2 147.4 0 203.6l-164 164c-34.4 34.4-90.1 34.4-124.5 0s-34.4-90.1 0-124.5L292.5 103.3c12.5-12.5 32.8-12.5 45.3 0s12.5 32.8 0 45.3L185 301.3c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0l164-164c31.2-31.2 31.2-81.9 0-113.1s-81.9-31.2-113.1 0l-164 164c-53.1 53.1-53.1 139.2 0 192.3s139.2 53.1 192.3 0L428.3 284.3c12.5-12.5 32.8-12.5 45.3 0s12.5 32.8 0 45.3L343.4 459.6c-78.1 78.1-204.7 78.1-282.8 0s-78.1-204.7 0-282.8l164-164z"/>
                                        </svg>
                                        <span class="truncate max-w-[220px]">
                                            {{ form.name_database_file }}
                                        </span>
                                    </div>

                                    <!-- Derecha -->
                                    <button
                                        type="button"
                                        @click="removeFile"
                                        class="text-red-500 hover:text-red-700 transition"
                                        title="Eliminar archivo"
                                    >
                                        ❌
                                    </button>
                                </div>
                            </div>
                            <InputError :message="form.errors.path_database_file" class="mt-2" />
                        </div>
                        <div class="sm:col-span-3">
                            <label class="inline-flex">
                                <input v-model="form.status" type="checkbox" class="form-checkbox" />
                                <span>Activo</span>
                            </label>
                        </div>
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
                    <Link :href="route('even_ediciones_listado')"  class="ml-2 inline-block px-6 py-2.5 bg-green-500 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-green-600 hover:shadow-lg focus:bg-green-600 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-green-700 active:shadow-lg transition duration-150 ease-in-out">Ir al Listado</Link>
                </template>
            </Keypad>
        </template>
    </FormSection>
</template>
