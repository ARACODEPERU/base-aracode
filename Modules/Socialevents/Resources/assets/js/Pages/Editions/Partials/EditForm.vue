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
            default: null,
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
        status: props.edicion.status,
        name_database_file: props.edicion.name_database_file,
        path_database_file: props.edicion.path_database_file,
        file: null,
        yellow_price: props.edicion.yellow_price,
        direct_red_price: props.edicion.direct_red_price,
        double_yellow_price: props.edicion.double_yellow_price
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
                                        fforma == 'group_stage_and_playoffs' ? 'Fase de grupos y Eliminatorias' :
                                        fforma == 'round_robin_playoff' ? 'Todos contra todos y Eliminatorias' :
                                        fforma == 'single_elimination' ? 'Eliminación simple' :
                                        'relampago'
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
                        <div class="col-span-6">
                            <h4 class="text-lg font-bold mt-4 mb-2">Archivos</h4>
                        </div>
                        <div class="col-span-6">
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
                            <input v-model="form.inscription_fee" id="inscription_fee" class="form-input text-right" v-maska="'########'" placeholder="_____" maxlength="8" type="text" />
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
                        <div class="col-span-3">
                            <h4 class="text-lg font-bold mt-6 mb-2">Premios</h4>
                        </div>
                        <div class="sm:col-span-3 space-y-4">
                            <div>
                                <InputLabel for="primer_money" value="Primer puesto" class="mb-1" />
                                <div class="flex items-center gap-4">
                                    <input v-model="form.prize_details.first.money" id="primer_money" class="form-input w-20" placeholder="Dinero" />
                                    <input v-model="form.prize_details.first.gift" id="primer_regalo" class="form-input" placeholder="Regalo/Presente" />
                                </div>
                                <InputError :message="form.errors.prize_details" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="segundo_money" value="Segundo puesto" class="mb-1" />
                                <div class="flex items-center gap-4">
                                    <input v-model="form.prize_details.second.money" id="segundo_money" class="form-input" placeholder="Dinero" />
                                    <input v-model="form.prize_details.second.gift" id="segundo_regalo" class="form-input" placeholder="Regalo/Presente" />
                                </div>
                                <InputError :message="form.errors.prize_details" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="tercero_money" value="Tercer puesto" class="mb-1" />
                                <div class="flex items-center gap-4">
                                    <input v-model="form.prize_details.third.money" id="tercero_money" class="form-input" placeholder="Dinero" />
                                    <input v-model="form.prize_details.third.gift" id="tercero_regalo" class="form-input" placeholder="Regalo/Presente" />
                                </div>
                                <InputError :message="form.errors.prize_details" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="cuarto_money" value="Cuarto puesto" class="mb-1" />
                                <div class="flex items-center gap-4">
                                    <input v-model="form.prize_details.fourth.money" id="cuarto_money" class="form-input" placeholder="Dinero" />
                                    <input v-model="form.prize_details.fourth.gift" id="cuarto_regalo" class="form-input" placeholder="Regalo/Presente" />
                                </div>
                                <InputError :message="form.errors.prize_details" class="mt-2" />
                            </div>
                        </div>
                        <div class="col-span-3">
                            <h4 class="text-lg font-bold mt-6 mb-2">Sanciones</h4>
                        </div>
                        <div>
                            <InputLabel for="yellow_price" value="Precio Amarilla" class="mb-1" />
                            <input v-model="form.yellow_price" id="yellow_price" class="form-input text-right" v-maska="'#####'" placeholder="_____" maxlength="5" type="text" />
                            <InputError :message="form.errors.yellow_price" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="direct_red_price" value="Precio Roja directa" class="mb-1" />
                            <input v-model="form.direct_red_price" id="direct_red_price" class="form-input text-right" v-maska="'#####'" placeholder="_____" maxlength="5" type="text" />
                            <InputError :message="form.errors.direct_red_price" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="double_yellow_price" value="Precio Roja doble amarilla" class="mb-1" />
                            <input v-model="form.double_yellow_price" id="double_yellow_price" class="form-input text-right" v-maska="'#####'" placeholder="_____" maxlength="5" type="text" />
                            <InputError :message="form.errors.double_yellow_price" class="mt-2" />
                        </div>

                        <div class="sm:col-span-3">
                            <InputLabel value="Estado" />
                            <div class="flex flex-wrap justify-start gap-1.5 sm:gap-2">
                                <button @click="form.status = 'pending'"
                                    type="button"
                                    :class="[
                                        'py-1.5 px-2.5 inline-flex items-center gap-x-1.5 text-sm rounded-lg transition-colors border',
                                        form.status === 'pending'
                                            ? 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800'
                                            : 'bg-gray-100 text-gray-800 border-transparent hover:text-cyan-700 dark:bg-neutral-700 dark:text-neutral-200'
                                    ]">
                                    <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path fill="currentColor" d="M160 64C142.3 64 128 78.3 128 96C128 113.7 142.3 128 160 128L160 139C160 181.4 176.9 222.1 206.9 252.1L274.8 320L206.9 387.9C176.9 417.9 160 458.6 160 501L160 512C142.3 512 128 526.3 128 544C128 561.7 142.3 576 160 576L480 576C497.7 576 512 561.7 512 544C512 526.3 497.7 512 480 512L480 501C480 458.6 463.1 417.9 433.1 387.9L365.2 320L433.1 252.1C463.1 222.1 480 181.4 480 139L480 128C497.7 128 512 113.7 512 96C512 78.3 497.7 64 480 64L160 64zM224 139L224 128L416 128L416 139C416 164.5 405.9 188.9 387.9 206.9L320 274.8L252.1 206.9C234.1 188.9 224 164.4 224 139z"/></svg>
                                    Pendiente
                                </button>
                                <button @click="form.status = 'in_progress'"
                                    type="button"
                                    :class="[
                                        'py-1.5 px-2.5 inline-flex items-center gap-x-1.5 text-sm rounded-lg transition-colors border',
                                        form.status === 'in_progress'
                                            ? 'bg-success-100 text-success-700 border-success-200 dark:bg-success-900/30 dark:text-success-400 dark:border-success-800'
                                            : 'bg-gray-100 text-gray-800 border-transparent hover:text-cyan-700 dark:bg-neutral-700 dark:text-neutral-200'
                                    ]">
                                    <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path fill="currentColor" d="M352.5 32C383.4 32 408.5 57.1 408.5 88C408.5 118.9 383.4 144 352.5 144C321.6 144 296.5 118.9 296.5 88C296.5 57.1 321.6 32 352.5 32zM219.6 240C216.3 240 213.4 242 212.2 245L190.2 299.9C183.6 316.3 165 324.3 148.6 317.7C132.2 311.1 124.2 292.5 130.8 276.1L152.7 221.2C163.7 193.9 190.1 176 219.6 176L316.9 176C345.4 176 371.7 191.1 386 215.7L418.8 272L480.4 272C498.1 272 512.4 286.3 512.4 304C512.4 321.7 498.1 336 480.4 336L418.8 336C396 336 375 323.9 363.5 304.2L353.5 287.1L332.8 357.5L408.2 380.1C435.9 388.4 450 419.1 438.3 445.6L381.7 573C374.5 589.2 355.6 596.4 339.5 589.2C323.4 582 316.1 563.1 323.3 547L372.5 436.2L276.6 407.4C243.9 397.6 224.6 363.7 232.9 330.6L255.6 240L219.7 240zM211.6 421C224.9 435.9 242.3 447.3 262.8 453.4L267.5 454.8L260.6 474.1C254.8 490.4 244.6 504.9 231.3 515.9L148.9 583.8C135.3 595 115.1 593.1 103.9 579.5C92.7 565.9 94.6 545.7 108.2 534.5L190.6 466.6C195.1 462.9 198.4 458.1 200.4 452.7L211.6 421z"/></svg>
                                    En curso
                                </button>
                                <button @click="form.status = 'finished'"
                                    type="button"
                                    :class="[
                                        'py-1.5 px-2.5 inline-flex items-center gap-x-1.5 text-sm rounded-lg transition-colors border',
                                        form.status === 'finished'
                                            ? 'bg-purple-100 text-purple-700 border-purple-200 dark:bg-purple-900/30 dark:text-purple-400 dark:border-purple-800'
                                            : 'bg-gray-100 text-gray-800 border-transparent hover:text-cyan-700 dark:bg-neutral-700 dark:text-neutral-200'
                                    ]">
                                    <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path fill="currentColor" d="M128 64C145.7 64 160 78.3 160 96L160 112L229 94.8C267.1 85.3 307.3 89.7 342.5 107.3C388.8 130.5 443.3 130.5 489.6 107.3L499.2 102.5C519.8 92.1 544 107.1 544 130.1L544 409.8C544 423.1 535.7 435.1 523.2 439.8L488.5 452.8C442.3 470.1 390.9 467.4 346.8 445.4C308.9 426.4 265.4 421.7 224.3 432L160 448L160 544C160 561.7 145.7 576 128 576C110.3 576 96 561.7 96 544L96 96C96 78.3 110.3 64 128 64zM160 251.1L224 237.2L224 302.7L160 316.6L160 382.1L208.8 369.9C213.9 368.6 218.9 367.5 224 366.6L224 302.7L262.9 294.3C271.2 292.5 279.6 291.8 288 292.2L288 228.2C301.6 228.6 315.2 230.8 328.4 234.6L352 241.5L352 308.2L310.3 295.9C303 293.8 295.5 292.5 288 292.1L288 363.5C309.8 365.4 331.3 370.2 352 377.9L352 308.1L374.7 314.8C388.2 318.8 402 321.2 416 322.2L416 258C408.2 257.2 400.4 255.7 392.8 253.5L352 241.5L352 179.5C339 175.7 326.2 170.7 313.8 164.5C305.6 160.4 296.9 157.5 288 155.7L288 228.1C275 227.7 262 228.9 249.3 231.7L224 237.2L224 162L160 178L160 251.1zM416 399.7C432.8 401.2 449.9 399 466 392.9L480 387.7L480 316L472.1 317.8C453.7 322.1 434.8 323.5 416 322.3L416 399.7zM480 250.3L480 179.5C459.1 185.6 437.6 188.6 416 188.6L416 258C429.9 259.4 444 258.5 457.7 255.4L480 250.2z"/></svg>
                                    Finalizado
                                </button>
                            </div>
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
                        Actualizar
                    </PrimaryButton>
                    <Link :href="route('even_ediciones_listado')"  class="ml-2 inline-block px-6 py-2.5 bg-green-500 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-green-600 hover:shadow-lg focus:bg-green-600 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-green-700 active:shadow-lg transition duration-150 ease-in-out">Ir al Listado</Link>
                </template>
            </Keypad>
        </template>
    </FormSection>
</template>
