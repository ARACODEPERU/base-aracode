<script setup>
    import { useForm, Link } from '@inertiajs/vue3';
    import FormSection from '@/Components/FormSection.vue';
    import InputError from '@/Components/InputError.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import TextInput from '@/Components/TextInput.vue';
    import Keypad from '@/Components/Keypad.vue';
    import Swal2 from 'sweetalert2';
    import { ref, watch, onMounted } from 'vue';
    import flatPickr from 'vue-flatpickr-component';
    import 'flatpickr/dist/flatpickr.css';
    import { Spanish } from "flatpickr/dist/l10n/es.js"
    import IconLoader from '@/Components/vristo/icon/icon-loader.vue';

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
        }
    });

    const form = useForm({
        edition_id: props.edition.id,
        team_h: null,
        team_a: null,
        phase: null,
        round_number: null,
        group_name: null,
        match_date: null,
        location: null
    });

    const createNow = () => {
        form.post(route('even_ediciones_fixtures_store'), {
            forceFormData: true,
            errorBag: 'createNow',
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
                <select v-model="form.team_a" id="team_h" class="form-select">
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
                <select v-model="form.phase" id="team_h" class="form-select">
                    <option value="league">Fase de Liga</option>
                    <option value="quarterfinals">Cuartos de Final</option>
                    <option value="final">Gran Final</option>
                </select>
                <InputError :message="form.errors.phase" class="mt-2" />
            </div>
            <div class="sm:col-span-2">
                <InputLabel for="round_number" value="Jornada / Fecha" />
                <TextInput
                    id="round_number"
                    v-model="form.round_number"
                    v-mask="'##'"
                    maxlength="2"
                    type="text"
                />
                <InputError :message="form.errors.round_number" />
            </div>
            <div class="sm:col-span-2">
                <InputLabel for="group_name" value="Grupo (Opcional)" />
                <TextInput
                    id="group_name"
                    v-model="form.group_name"
                    maxlength="255"
                    type="text"
                />
                <InputError :message="form.errors.round_number" />
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
