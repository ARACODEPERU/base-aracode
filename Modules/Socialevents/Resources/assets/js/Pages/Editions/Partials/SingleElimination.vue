<script setup>
    import { useForm } from '@inertiajs/vue3';
    import Swal2 from "sweetalert2";
    import { Link, router } from '@inertiajs/vue3';
    import ModalLarge from '@/Components/ModalLarge.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import TextInput from '@/Components/TextInput.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import InputError from '@/Components/InputError.vue';
    import flatPickr from 'vue-flatpickr-component';
    import 'flatpickr/dist/flatpickr.css';
    import { Spanish } from "flatpickr/dist/l10n/es.js"
    import IconBallSoccer from '@/Components/vristo/icon/icon-ball-soccer.vue';
    import { ref, computed } from 'vue';

    const props = defineProps({
        edition: { type: Object, default: () => ({}) },
        knockout: { type: Object, default: () => ({}) },
        locales: { type: Object, default: () => ({}), },
        ubigeo: { type: Object, default: () => ({}), },
        documentTypes: { type: Object, default: () => ({}),},
        teams: {
            type: Object,
            default: () => ({}),
        },
    });

    const displayModalEdit = ref(false);
    const formMatch = useForm({
        edition_id: null,
        id: null,
        match_date: null,
        equipo_h_name: null,
        equipo_a_name: null,
        equipo_h_id: null,
        equipo_a_id: null,
        score_h: null,
        score_a: null,
        round_number: null,
        group_name: null,
        phase: null,
        location: null,
        status: null,
    });

    const openModalEdit = (partido) => {
        if(partido.status != 'closed'){
            formMatch.edition_id = props.edition.id;
            formMatch.id = partido.id;
            formMatch.match_date = partido.match_date;
            formMatch.equipo_h_name = partido.equipolocal?.name || null;
            formMatch.equipo_a_name = partido.equipovisitante?.name || null;
            formMatch.equipo_h_id = partido.equipolocal?.id || null;
            formMatch.equipo_a_id = partido.equipovisitante?.id || null;
            formMatch.score_h = partido.score_h || null;
            formMatch.score_a = partido.score_a || null;
            formMatch.round_number = partido.round_number;
            formMatch.group_name = partido.group_name;
            formMatch.phase = partido.phase;
            formMatch.location = partido.location;
            formMatch.status = partido.status;
            displayModalEdit.value = true;
        } else {
            // Assuming showAlertStatus is defined or add a simple alert
            Swal2.fire({
                title: 'Partido Cerrado',
                text: 'Este partido ya está cerrado.',
                icon: 'error'
            });
        }
    }

    const closeModalEdit = () => {
        displayModalEdit.value = false;
        formMatch.reset();
    }

    const updateFixture = () => {
        formMatch.put(route('even_ediciones_fixtures_update', formMatch.id), {
            errorBag: 'updateFixture',
            preserveScroll: true,
            preserveState: true,
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

    const teamsSortedDesc = computed(() => {
        return [...props.teams].sort((a, b) => (b.equipo.points || 0) - (a.equipo.points || 0));
    });

    const teamsSortedAsc = computed(() => {
        return [...props.teams].sort((a, b) => (a.equipo.points || 0) - (b.equipo.points || 0));
    });

    const configFlatPickr = {
        enableTime: true,
        dateFormat: 'Y-m-d H:i',
        locale: Spanish
    };

    // Función para determinar si el partido es par o impar para la curva del conector
    const isEven = (index) => index % 2 === 0;
</script>

<template>
    <div class="panel min-h-screen">
        <div v-for="(rounds, phase) in knockout" :key="phase" class="mb-12">
            <div class="text-center mb-16">
                <h1 class="text-3xl font-black uppercase tracking-widest text-slate-700 dark:text-slate-300">Torneo Relámpago</h1>
                <p class="text-slate-500 dark:text-slate-400 text-xs font-bold uppercase tracking-tighter">Eliminación Directa</p>
            </div>

            <div class="flex flex-row items-stretch justify-center overflow-x-auto min-w-max">
                <template v-for="(matches, roundNumber, index) in rounds" :key="roundNumber">

                    <div class="flex flex-col min-w-[320px] relative">
                        <div class="text-center mb-4 font-black text-xs uppercase text-slate-600 dark:text-slate-400 tracking-widest">
                            Ronda {{ roundNumber }}
                        </div>

                        <div class="flex-grow flex flex-col justify-around relative">
                            <div v-for="(match, mIdx) in matches" :key="match.id"
                                 class="relative flex flex-col justify-center py-4 px-10 h-full w-full">

                                 <div class="relative z-20 flex flex-col gap-1 p-4 bg-slate-50 dark:bg-slate-800 rounded-xl shadow-md border border-slate-200 dark:border-slate-600 w-64 mx-auto cursor-pointer hover:shadow-lg transition-shadow" @click="openModalEdit(match)">
                                    <div class="flex justify-between items-center text-sm font-bold text-slate-800 dark:text-slate-200">
                                        <span class="truncate pr-2">{{ match.equipolocal?.short_name || match.placeholder_h || 'TBD' }}</span>
                                        <span class="text-slate-700 dark:text-slate-300 font-mono">{{ match.score_h ?? '-' }}</span>
                                    </div>
                                    <div class="border-t border-slate-200 dark:border-slate-600 my-2"></div>
                                    <div class="flex justify-between items-center text-sm font-bold text-slate-800 dark:text-slate-200">
                                        <span class="truncate pr-2">{{ match.equipovisitante?.short_name || match.placeholder_a || 'TBD' }}</span>
                                        <span class="text-slate-700 dark:text-slate-300 font-mono">{{ match.score_a ?? '-' }}</span>
                                    </div>
                                </div>

                                <template v-if="index < Object.keys(rounds).length - 1">
                                     <div class="absolute top-1/2 right-0 w-10 h-0.5 bg-slate-400 dark:bg-slate-500 z-10"></div>

                                     <div v-if="mIdx % 2 === 0"
                                          class="absolute right-0 top-1/2 border-r-2 border-slate-400 dark:border-slate-500 z-0"
                                          :style="{ height: '50%' }">
                                     </div>

                                     <div v-else
                                          class="absolute right-0 bottom-1/2 border-r-2 border-slate-400 dark:border-slate-500 z-0"
                                          :style="{ height: '50%' }">
                                     </div>
                                 </template>

                                 <div v-if="index > 0"
                                      class="absolute top-1/2 left-0 w-10 h-0.5 bg-slate-400 dark:bg-slate-500 z-10">
                                 </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <ModalLarge :show="displayModalEdit" :onClose="closeModalEdit" :icon="'/img/juego.png'">
            <template #title>{{ formMatch.equipo_h_name || 'TBD' }} VS {{ formMatch.equipo_a_name || 'TBD' }}</template>
            <template #message>Modificar información</template>
            <template #content>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                            <template v-for="local in props.locales">
                                <option :value="local.names">{{ local.names }}</option>
                            </template>
                        </select>
                        <InputError :message="formMatch.errors.location" />
                    </div>
                    <div>
                        <InputLabel for="equipo_h_id" value="Equipo Local" />
                        <select
                            id="equipo_h_id"
                            v-model="formMatch.equipo_h_id"
                            class="form-select"
                        >
                            <option value="">Selecciona equipo local</option>
                            <option v-for="team in teamsSortedDesc" :value="team.equipo.id">
                                {{ team.equipo.short_name }} ({{ team.points || 0 }} pts)
                            </option>
                        </select>
                        <InputError :message="formMatch.errors.equipo_h_id" />
                    </div>
                    <div>
                        <InputLabel for="equipo_a_id" value="Equipo Visitante" />
                        <select
                            id="equipo_a_id"
                            v-model="formMatch.equipo_a_id"
                            class="form-select"
                        >
                            <option value="">Selecciona equipo visitante</option>
                            <option v-for="team in teamsSortedAsc" :value="team.equipo.id">
                                {{ team.equipo.short_name }} ({{ team.points || 0 }} pts)
                            </option>
                        </select>
                        <InputError :message="formMatch.errors.equipo_a_id" />
                    </div>
                                        <div>
                        <InputLabel for="score_h" value="Marcador Local" />
                        <TextInput
                            id="score_h"
                            v-model="formMatch.score_h"
                            v-mask="'##'"
                            maxlength="2"
                            type="text"
                            placeholder="0"
                        />
                        <InputError :message="formMatch.errors.score_h" />
                    </div>
                    <div>
                        <InputLabel for="score_a" value="Marcador Visitante" />
                        <TextInput
                            id="score_a"
                            v-model="formMatch.score_a"
                            v-mask="'##'"
                            maxlength="2"
                            type="text"
                            placeholder="0"
                        />
                        <InputError :message="formMatch.errors.score_a" />
                    </div>
                </div>
            </template>
            <template #buttons>
                <PrimaryButton @click="updateFixture" :class="{ 'opacity-25': formMatch.processing }" :disabled="formMatch.processing">
                    <IconBallSoccer v-if="formMatch.processing" class="w-4 h-4 animate-bounce-ball mr-1" />
                    Guardar cambios
                </PrimaryButton>
            </template>
        </ModalLarge>
    </div>
</template>

<style scoped>
    /* Para que la ronda 3 se conecte, la clave es que cada "celda" de partido
    ocupe el 100% del espacio vertical disponible de forma equitativa.
    */
    .flex-grow > div {
        flex: 1 1 0%;
    }
</style>

<style scoped>
/* Evita que el bracket se vea apretado */
.flex-grow {
    min-height: 400px;
}
</style>
