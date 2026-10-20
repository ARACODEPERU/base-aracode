<script setup>
    import { useForm, Link } from '@inertiajs/vue3';
    import FormSection from '@/Components/FormSection.vue';
    import InputError from '@/Components/InputError.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import TextInput from '@/Components/TextInput.vue';
    import Keypad from '@/Components/Keypad.vue';
    import Swal2 from 'sweetalert2';
    import { ref, watch, onMounted, computed } from 'vue';
    import flatPickr from 'vue-flatpickr-component';
    import 'flatpickr/dist/flatpickr.css';
    import { Spanish } from "flatpickr/dist/l10n/es.js"
    import IconLoader from '@/Components/vristo/icon/icon-loader.vue';
    import { jornadaVuelta } from '../../../utils/fixtureJornadas.js';

    const props = defineProps({
        edition: {
            type: Object,
            default: () => ({}),
        },
        teams: {
            type: Object,
            default: () => ({}),
        },
        locales: {
            type: Object,
            default: () => ({}),
        },
        // Catálogo único de fases (lo envía TournamentPhaseLabels::options())
        phaseOptions: {
            type: Array,
            default: () => [],
        },
        // Formatos de cruce (los envía TournamentTieService::tieFormats())
        tieFormats: {
            type: Array,
            default: () => [],
        },
    });

    const form = useForm({
        edition_id: props.edition.id,
        team_h: null,
        team_a: null,
        phase: 'league',
        round_number: null,
        group_name: null,
        match_date: null,
        location: null,
        // Formato del cruce: opcional, por defecto partido único normal.
        tie_format: 'single',
        // Jornada de la vuelta: el cruce ocupa dos jornadas distintas.
        round_number_return: null,
        match_date_return: null,
        location_return: null,
    });

    // Las fases de eliminación son las únicas que admiten ida y vuelta o muerte súbita.
    const knockoutPhases = ['round_16', 'quarterfinals', 'semifinals', 'third_place', 'final', 'knockout', 'playoff'];
    const isKnockoutPhase = computed(() => knockoutPhases.includes(form.phase));
    const isHomeAndAway = computed(() => form.tie_format === 'home_and_away');

    // Al volver a una fase de liga, el formato de eliminación se descarta.
    watch(() => form.phase, (phase) => {
        if (! knockoutPhases.includes(phase)) {
            form.tie_format = 'single';
        }
    });

    // La ida y la vuelta no pueden compartir jornada: al elegir ida y vuelta se
    // propone la jornada 1 para la ida y la 2 para la vuelta.
    const sincronizarJornadaVuelta = () => {
        if (! isHomeAndAway.value) {
            return;
        }

        if (! form.round_number || Number(form.round_number) < 1) {
            form.round_number = 1;
        }

        form.round_number_return = jornadaVuelta(form.round_number);
    };

    watch(isHomeAndAway, (esIdaYVuelta) => {
        if (esIdaYVuelta) {
            sincronizarJornadaVuelta();
        }
    });

    // Si se corrige la jornada de la ida, la vuelta la sigue. Se respeta la
    // jornada de vuelta que el organizador ya haya escrito a mano.
    watch(() => form.round_number, (nueva, anterior) => {
        if (! isHomeAndAway.value) {
            return;
        }

        const sugerida = anterior ? jornadaVuelta(anterior) : null;

        if (! form.round_number_return || Number(form.round_number_return) === sugerida) {
            form.round_number_return = jornadaVuelta(nueva);
        }
    });

    const createNow = () => {
        form.post(route('even_ediciones_fixtures_store'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                Swal2.fire({
                    title: 'Enhorabuena',
                    text: 'Se registró correctamente',
                    icon: 'success',
                });
                form.reset()
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
    <FormSection @submitted="createNow" class="">
        <template #title>
            Categoría Detalles
        </template>

        <template #description>
            Crear nueva Categoría, Los campos con * son obligatorios
        </template>

        <template #form>
            <div class="col-span-2">
                <InputLabel for="team_h" value="Equipo Local *" class="mb-1" />
                <select v-model="form.team_h" id="team_h" class="form-select">
                    <template v-for="teamH in teams">
                        <option :value="teamH.id">{{ teamH.name }}</option>
                    </template>
                </select>
                <InputError :message="form.errors.team_h" class="mt-2" />
            </div>
            <div class="col-span-2">
                <InputLabel for="team_a" value="Equipo Visitante *" class="mb-1" />
                <select v-model="form.team_a" id="team_a" class="form-select">
                    <template v-for="teamA in teams">
                        <option :value="teamA.id">{{ teamA.name }}</option>
                    </template>
                </select>
                <InputError :message="form.errors.team_a" class="mt-2" />
            </div>
            <div class="col-span-2">
                <InputLabel for="match_date" value="Fecha y hora *" class="mb-1" />
                <flat-pickr
                    v-model="form.match_date"
                    :config="configFlatPickr"
                    class="form-input w-full"
                    placeholder="Selecciona fecha"
                />
                <InputError :message="form.errors.match_date" class="mt-2" />
            </div>
            <div class="sm:col-span-2">
                <InputLabel for="phase" value="Fase *" class="mb-1" />
                <select v-model="form.phase" id="phase" class="form-select">
                    <option v-for="option in phaseOptions" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
                <InputError :message="form.errors.phase" class="mt-2" />
            </div>
            <div class="sm:col-span-2">
                <InputLabel for="tie_format" value="Formato del cruce (Opcional)" class="mb-1" />
                <select v-model="form.tie_format" id="tie_format" class="form-select">
                    <option
                        v-for="format in tieFormats"
                        :key="format.value"
                        :value="format.value"
                        :disabled="format.value !== 'single' && !isKnockoutPhase"
                    >
                        {{ format.label }}
                    </option>
                </select>
                <p class="mt-1 text-xs text-gray-500">
                    {{ isKnockoutPhase
                        ? 'El formato se aplica al cruce completo: la ida y la vuelta quedan en la misma fase.'
                        : 'Los formatos de eliminación solo se usan en octavos, cuartos, semifinales, tercer puesto o final.' }}
                </p>
                <InputError :message="form.errors.tie_format" class="mt-2" />
            </div>
            <div v-if="isHomeAndAway" class="sm:col-span-2">
                <div class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Se crearán <strong>2 partidos</strong> (ida y vuelta) y <strong>2 cargos</strong> por registro.
                    La ida queda en una jornada y la vuelta en la siguiente, con los equipos invertidos.
                </div>
            </div>
            <div class="sm:col-span-2">
                <InputLabel for="round_number" :value="isHomeAndAway ? 'Jornada / Fecha (ida)' : 'Jornada / Fecha'" />
                <TextInput
                    id="round_number"
                    v-model="form.round_number"
                    v-mask="'##'"
                    maxlength="2"
                    type="text"
                />
                <InputError :message="form.errors.round_number" />
            </div>
            <div v-if="isHomeAndAway" class="sm:col-span-2">
                <InputLabel for="round_number_return" value="Jornada / Fecha (vuelta)" />
                <TextInput
                    id="round_number_return"
                    v-model="form.round_number_return"
                    v-mask="'##'"
                    maxlength="2"
                    type="text"
                />
                <p class="mt-1 text-xs text-gray-500">
                    Los partidos de vuelta se programan en la jornada siguiente a la ida.
                </p>
                <InputError :message="form.errors.round_number_return" />
            </div>
            <div v-if="isHomeAndAway" class="sm:col-span-2">
                <InputLabel for="match_date_return" value="Fecha y hora de la vuelta (Opcional)" class="mb-1" />
                <flat-pickr
                    v-model="form.match_date_return"
                    :config="configFlatPickr"
                    class="form-input w-full"
                    placeholder="Selecciona fecha de la vuelta"
                />
                <InputError :message="form.errors.match_date_return" class="mt-2" />
            </div>
            <div v-if="isHomeAndAway" class="sm:col-span-2">
                <InputLabel for="location_return" value="Cancha de la vuelta (Opcional)" class="mb-1" />
                <select id="location_return" v-model="form.location_return" class="form-select">
                    <option :value="null">Misma cancha de la ida</option>
                    <option v-for="local in locales" :key="local.names" :value="local.names">
                        {{ local.names }}
                    </option>
                </select>
                <InputError :message="form.errors.location_return" class="mt-2" />
            </div>
            <div class="sm:col-span-2">
                <InputLabel for="group_name" value="Grupo (Opcional)" />
                <TextInput
                    id="group_name"
                    v-model="form.group_name"
                    maxlength="1"
                    type="text"
                />
                <InputError :message="form.errors.group_name" />
            </div>
            <div class="sm:col-span-2">
                <InputLabel for="location" value="Cancha / lugar (Opcional)" />
                <select
                    id="location"
                    v-model="form.location"
                    class="form-select"
                >
                    <template v-for="local in locales">
                        <option :value="local.names">{{ local.names }}</option>
                    </template>
                </select>
                <InputError :message="form.errors.location" />
            </div>
            <div class="col-span-6">
                <InputError :message="form.errors.prepaid_balance" />
            </div>

            <!-- <div class="col-span-6">
                <div class="flex items-center">
                    <input id="checked-checkbox-2" type="checkbox" v-model="form.status" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                    <label for="checked-checkbox-2" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Activo</label>
                </div>
            </div> -->
        </template>

        <template #actions>
            <progress v-if="form.progress" :value="form.progress.percentage" max="100" class="mr-2">
                {{ form.progress.percentage }}%
            </progress>
            <Keypad>
                <template #botones>
                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        <IconLoader v-if="form.processing" class="w-4 h-4 mr-1" />
                        Guardar
                    </PrimaryButton>
                    <Link :href="route('even_ediciones_fixtures', edition.id)"  class="ml-2 inline-block px-6 py-2.5 bg-green-500 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-green-600 hover:shadow-lg focus:bg-green-600 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-green-700 active:shadow-lg transition duration-150 ease-in-out">Ir al Listado</Link>
                </template>
            </Keypad>
        </template>
    </FormSection>
</template>
