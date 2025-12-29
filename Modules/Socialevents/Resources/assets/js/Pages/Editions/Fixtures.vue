<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import { useForm } from '@inertiajs/vue3';
    import Keypad from '@/Components/Keypad.vue';
    import Swal2 from "sweetalert2";
    import { Link, router } from '@inertiajs/vue3';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import ModalLarge from '@/Components/ModalLarge.vue';
    import { ref, watch } from 'vue';
    import SuccessButton from '@/Components/SuccessButton.vue';
    import InputError from '@/Components/InputError.vue';
    import IconPencilPaper from '@/Components/vristo/icon/icon-pencil-paper.vue';
    import IconTrashLines from '@/Components/vristo/icon/icon-trash-lines.vue';
    import ModalSmall from '@/Components/ModalSmall.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import TextInput from '@/Components/TextInput.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import IconLoader from '@/Components/vristo/icon/icon-loader.vue';
    import flatPickr from 'vue-flatpickr-component';
    import 'flatpickr/dist/flatpickr.css';
    import { Spanish } from "flatpickr/dist/l10n/es.js"

    const props = defineProps({
        edition: {
            type: Object,
            default: () => ({}),
        },
        teams: {
            type: Object,
            default: () => ({}),
        },
        filters: {
            type: Object,
            default: () => ({}),
        },
        fixture: {
            type: Object,
            default: () => ({}),
        },
        locales: {
            type: Object,
            default: () => ({}),
        }
    });

    const form = useForm({
        search: props.filters.search,
    });

    const formTeams = useForm({
        teams: [],
        edition_id: props.edition.id,
        format: props.edition.competition_format
    });

    const xhttp =  assetUrl;

    const displayModalTeams = ref(false);

    const openModalTeams = () => {
        displayModalTeams.value = true;
    }
    const closeModalTeams = () => {
        displayModalTeams.value = false;
    }

    watch(() => props.teams, (newTeams) => {
        if (newTeams.length > 0) {
            formTeams.teams = newTeams.map(t => t.equipo.id);
        }
    }, { immediate: true });

    const generateFixture = () => {
        formTeams.put(route('even_ediciones_fixtures_generate', props.edition.id), {
            errorBag: 'generateFixture',
            preserveScroll: true,
            onSuccess: () => {
                Swal2.fire({
                    title: 'Enhorabuena',
                    text: 'Se registró correctamente',
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
            },
        });
    }

    // Función para formatear fecha (puedes usar date-fns o native)
    const formatDate = (dateString) => {
        const options = { weekday: 'short', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
        return new Date(dateString).toLocaleDateString('es-ES', options);
    };

    // Colores según el estado
    const statusBadge = (status) => {
        const classes = {
            'pending': 'text-orange-500',
            'finished': 'text-gray-400',
            'live': 'text-red-600 animate-pulse font-bold'
        };
        return classes[status] || 'text-gray-500';
    };

    const displayModalEdit = ref(false);
    const formMatch = useForm({
        id: null,
        match_date: null,
        equipo_h_name: null,
        equipo_a_name: null,
        round_number: null,
        group_name: null,
        match_date: null,
        location: null
    });

    const openModalEdit = (partido) => {
        formMatch.id = partido.id;
        formMatch.match_date = partido.match_date;
        formMatch.equipo_h_name = partido.equipolocal.name;
        formMatch.equipo_a_name = partido.equipovisitante.name;
        displayModalEdit.value = true;
    }

    const closeModalEdit = () => {
        displayModalEdit.value = false;
    }

    const updateFixture = () => {
        formMatch.put(route('even_ediciones_fixtures_update', formMatch.id), {
            errorBag: 'updateFixture',
            preserveScroll: true,
            onSuccess: () => {
                Swal2.fire({
                    title: 'Enhorabuena',
                    text: 'Se actualizo correctamente',
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                displayModalEdit.value = false;
                formMatch.reset();
            },
        });
    }

    const configFlatPickr = {
        enableTime: true,
        dateFormat: 'Y-m-d H:i',
        //minDate: 'today',
        locale: Spanish
    };
</script>
<template>
    <AppLayout title="Ediciones">
        <Navigation :routeModule="route('even_dashboard')" :titleModule="'Eventos sociales'">
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <Link :href="route('even_ediciones_listado')" class="text-primary hover:underline">Ediciones</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>{{ edition.name }}</span>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>Fixtures</span>
            </li>
        </Navigation>
        <div class="pt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="grid grid-cols-3 w-full">
                    <div class="col-span-3 sm:col-span-1">
                        <form id="form-search-items" @submit.prevent="form.get(route('even_ediciones_listado'))">
                            <label for="table-search" class="sr-only">Search</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path></svg>
                                </div>
                                <input v-model="form.search" type="text" class="block pl-10 form-input w-80" placeholder="Buscar por Descripción">
                            </div>
                        </form>
                    </div>
                    <div class="col-span-3 sm:col-span-2">
                        <Keypad>
                            <template #botones>
                                <button @click="openModalTeams" class="btn btn-warning uppercase text-xs">
                                    Generar Automático
                                </button>
                                <Link v-can="'even_ediciones_fixtures_nuevo'" :href="route('even_ediciones_fixtures_create', edition.id)" class="btn btn-primary uppercase text-xs">
                                    Nuevo
                                </Link>
                            </template>
                        </Keypad>
                    </div>
                </div>
            </div>
            <div class="mt-5">

                <div v-if="Object.keys(fixture).length > 0">

                    <div v-for="(matches, phase) in fixture" :key="phase" class="mb-10">

                        <h2 class="text-xl font-bold uppercase mb-4 text-gray-700 border-b pb-2 dark:border-gray-600">
                            {{ phase === 'league' ? 'Fase de Grupos' : phase.replace('_', ' ') }}
                        </h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                            <div
                                v-for="match in matches"
                                :key="match.id"
                                class="bg-white border rounded-xl shadow-sm hover:shadow-md transition-shadow overflow-hidden dark:bg-red-700 dark:border-red-900"
                            >
                                <div class="bg-gray-50 px-4 py-2 text-xs font-medium text-gray-500 flex justify-between dark-bg-red-900 dark:text-white">
                                    <span>{{ match.match_date ? formatDate(match.match_date) : 'Por definir' }}</span>
                                    <span :class="statusBadge(match.status)">{{ match.status }}</span>
                                </div>

                                <div class="p-4 flex flex-col gap-3">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center text-[10px] font-bold">
                                                <img v-if="match.equipolocal && match.equipolocal.logo_path" :src="xhttp + 'storage/' + match.equipolocal?.logo_path" class="w-8 h-8" />
                                                <img v-else-if="match.equipolocal && !match.equipolocal.logo_path" :src="'/img/escudo-deportivo.png'" class="w-8 h-8" />
                                                <template v-else><span class="text-lg font-bold">?</span></template>
                                            </div>
                                            <span class="font-semibold text-gray-800 dark:text-white">
                                                {{ match.equipolocal?.name || 'Por definir' }}
                                            </span>
                                        </div>
                                        <span class="text-lg font-bold dark:text-white">{{ match.score_h ?? '-' }}</span>
                                    </div>

                                    <div class="flex justify-center">
                                        <span class="text-[10px] font-bold text-gray-400 tracking-widest dark:text-white">VS</span>
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center text-[10px] font-bold">
                                                <img v-if="match.equipovisitante && match.equipovisitante.logo_path" :src="xhttp + 'storage/' + match.equipovisitante?.logo_path" class="w-8 h-8" />
                                                <img v-else-if="match.equipovisitante && !match.equipovisitante.logo_path" :src="'/img/escudo-deportivo.png'" class="w-8 h-8" />
                                                <template v-else><span class="text-lg font-bold">?</span></template>
                                            </div>
                                            <span class="font-semibold text-gray-800 dark:text-white">
                                                {{ match.equipovisitante?.name || 'Por definir' }}
                                            </span>
                                        </div>
                                        <span class="text-lg font-bold dark:text-white">{{ match.score_a ?? '-' }}</span>
                                    </div>
                                </div>
                                <div class="my-6 no-export">
                                    <div class="flex items-center justify-center gap-6">
                                        <button @click="openModalEdit(match)" type="button" v-tippy="{content: 'Editar', placement: 'bottom'}">
                                            <IconPencilPaper class="w-5 h-5" />
                                        </button>
                                        <button type="button" v-tippy="{content: 'Eliminar', placement: 'bottom'}">
                                            <IconTrashLines class="w-5 h-5" />
                                        </button>
                                        <button type="button" v-tippy="{content: 'Resultado', placement: 'bottom'}">
                                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                                                <path fill="currentColor" d="M192 64C156.7 64 128 92.7 128 128L128 512C128 547.3 156.7 576 192 576L448 576C483.3 576 512 547.3 512 512L512 128C512 92.7 483.3 64 448 64L192 64zM224 128L416 128C433.7 128 448 142.3 448 160L448 192C448 209.7 433.7 224 416 224L224 224C206.3 224 192 209.7 192 192L192 160C192 142.3 206.3 128 224 128zM240 296C240 309.3 229.3 320 216 320C202.7 320 192 309.3 192 296C192 282.7 202.7 272 216 272C229.3 272 240 282.7 240 296zM320 320C306.7 320 296 309.3 296 296C296 282.7 306.7 272 320 272C333.3 272 344 282.7 344 296C344 309.3 333.3 320 320 320zM448 296C448 309.3 437.3 320 424 320C410.7 320 400 309.3 400 296C400 282.7 410.7 272 424 272C437.3 272 448 282.7 448 296zM216 416C202.7 416 192 405.3 192 392C192 378.7 202.7 368 216 368C229.3 368 240 378.7 240 392C240 405.3 229.3 416 216 416zM344 392C344 405.3 333.3 416 320 416C306.7 416 296 405.3 296 392C296 378.7 306.7 368 320 368C333.3 368 344 378.7 344 392zM424 416C410.7 416 400 405.3 400 392C400 378.7 410.7 368 424 368C437.3 368 448 378.7 448 392C448 405.3 437.3 416 424 416zM192 488C192 474.7 202.7 464 216 464L328 464C341.3 464 352 474.7 352 488C352 501.3 341.3 512 328 512L216 512C202.7 512 192 501.3 192 488zM424 464C437.3 464 448 474.7 448 488C448 501.3 437.3 512 424 512C410.7 512 400 501.3 400 488C400 474.7 410.7 464 424 464z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-20 text-gray-500">
                    No hay partidos generados para esta edición.
                </div>

            </div>
        </div>
        <ModalLarge :show="displayModalTeams" :onClose="closeModalTeams" :icon="'/img/calendario.png'">
            <template #title>Generar encuentros</template>
            <template #message>Generar el Motor de Fixtures</template>
            <template #content>
                <ul v-if="teams.length > 0" class="w-full flex flex-col divide-y divide-gray-200 dark:divide-neutral-700">
                    <li
                        v-for="team in teams"
                        :key="team.equipo.id"
                        class="py-3 text-sm font-medium text-gray-800 dark:text-white"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                {{ team.equipo.name }}
                            </div>
                            <input
                                type="checkbox"
                                class="form-checkbox"
                                :value="team.equipo.id"
                                v-model="formTeams.teams"
                            />
                        </div>
                    </li>
                    <li>
                        <InputError :message="formTeams.errors.teams" />
                    </li>
                </ul>
            </template>
            <template #buttons>
                <SuccessButton @click="generateFixture" type="button"
                    :class="{ 'opacity-25': formTeams.processing }" :disabled="formTeams.processing"
                >
                    <IconLoader v-if="formTeams.processing" class="w-4 h-4 mr-1" />
                    Crear
                </SuccessButton>
            </template>
        </ModalLarge>
        <ModalSmall :show="displayModalEdit" :onClose="closeModalEdit" :icon="'/img/juego.png'">
            <template #title>{{ formMatch.equipo_h_name  }} VS {{ formMatch.equipo_a_name  }}</template>
            <template #message>Modificar información</template>
            <template #content>
                <div>
                    <InputLabel for="round_number" value="Jornada / Fecha" />
                    <TextInput
                        id="round_number"
                        v-model="formMatch.round_number"
                        v-mask="'##'"
                        maxlength="2"
                        type="text"
                    />
                    <InputError :message="formMatch.errors.round_number" />
                </div>
                <div class="mt-6">
                    <InputLabel for="group_name" value="Grupo (Opcional)" />
                    <TextInput
                        id="group_name"
                        v-model="formMatch.group_name"
                        maxlength="255"
                        type="text"
                    />
                    <InputError :message="formMatch.errors.round_number" />
                </div>
                <div>
                    <InputLabel for="match_date" value="Fecha y hora" />
                    <flat-pickr
                        v-model="formMatch.match_date"
                        :config="configFlatPickr"
                        class="form-input"
                        placeholder="Selecciona fecha"
                    />
                    <InputError :message="formMatch.errors.match_date" />
                </div>
                <div>
                    <InputLabel for="location" value="Cancha / lugar (Opcional)" />
                    <select
                        id="location"
                        v-model="formMatch.location"
                        class="form-select"
                    >
                        <template v-for="local in locales">
                            <option :value="local.names">{{ local.names }}</option>
                        </template>
                    </select>
                    <InputError :message="formMatch.errors.location" />
                </div>
            </template>
            <template #buttons>
                <PrimaryButton @click="updateFixture" :class="{ 'opacity-25': formMatch.processing }" :disabled="formMatch.processing">
                    <IconLoader v-if="formMatch.processing" class="w-4 h-4 mr-1" />
                    Guardar cambios
                </PrimaryButton>
            </template>
        </ModalSmall>
    </AppLayout>
</template>
