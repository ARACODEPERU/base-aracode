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

    const props = defineProps({
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
        team_h: null,
        team_a: null,
        phase: null,
        round_number: null,
        group_name: null,
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
                <InputError :message="form.errors.description" class="mt-2" />
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
                <InputLabel for="team_a" value="Fase *" class="mb-1" />
                <select v-model="form.team_a" id="team_h" class="form-select">
                    <template v-for="teamA in teams">
                        <option :value="teamA.id">{{ teamA.name }}</option>
                    </template>
                </select>
                <InputError :message="form.errors.description" class="mt-2" />
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
                        <svg v-show="form.processing" aria-hidden="true" role="status" class="inline w-4 h-4 mr-3 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                            <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                        </svg>
                        Guardar
                    </PrimaryButton>
                    <Link :href="route('even_categories_list')"  class="ml-2 inline-block px-6 py-2.5 bg-green-500 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-green-600 hover:shadow-lg focus:bg-green-600 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-green-700 active:shadow-lg transition duration-150 ease-in-out">Ir al Listado</Link>
                </template>
            </Keypad>
        </template>
    </FormSection>
</template>
