<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Swal2 from "sweetalert2";
    import { Link, useForm } from '@inertiajs/vue3';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { ref } from "vue";
    import iconLoader from '@/Components/vristo/icon/icon-loader.vue';
    import ModalLarge from '@/Components/ModalLarge.vue';
    import { Empty } from 'ant-design-vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import TextInput from '@/Components/TextInput.vue';
    import InputError from '@/Components/InputError.vue';

    const props = defineProps({
        edicion: {
            type: Object,
            default: () => ({}),
        },
        exclusions: {
            type: Array,
            default: () => [],
        },
        players: {
            type: Object,
            default: () => ({}),
        }
    });

    const displayModalAdd = ref(false);

    const form = useForm({
        player_id: null,
        reason: null,
        excluded_by: null
    });

    const openModalAdd = () => {
        form.reset();
        displayModalAdd.value = true;
    };

    const closeModalAdd = () => {
        displayModalAdd.value = false;
    };

    const saveExclusion = () => {
        form.post(route('even_ediciones_exclusiones_store', props.edicion.id), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                displayModalAdd.value = false;
                Swal2.fire({
                    title: 'Enhorabuena',
                    text: 'Jugador excluido de los rankings de la edición',
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
            }
        });
    };

    const destroyExclusion = (exclusionId, playerName) => {
        Swal2.fire({
            title: '¿Estas seguro?',
            text: `${playerName} volverá a aparecer en los rankings de la edición.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '¡Sí, Restaurar!',
            cancelButtonText: 'Cancelar',
            showLoaderOnConfirm: true,
            padding: '2em',
            customClass: 'sweet-alerts',
        }).then((result) => {
            if (result.isConfirmed) {
                useForm({}).delete(route('even_ediciones_exclusiones_destroy', [props.edicion.id, exclusionId]), {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        Swal2.fire({
                            title: 'Enhorabuena',
                            text: 'Exclusión eliminada correctamente',
                            icon: 'success',
                            padding: '2em',
                            customClass: 'sweet-alerts',
                        });
                    }
                });
            }
        });
    };

    const formatDate = (value) => {
        if (!value) return '-';
        const date = new Date(value);
        if (isNaN(date.getTime())) return value;
        return date.toLocaleDateString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric' });
    };

    const flattenedPlayers = () => {
        const result = [];
        Object.keys(props.players).forEach((teamName) => {
            (props.players[teamName] || []).forEach((p) => {
                result.push({ ...p, team_name: teamName });
            });
        });
        return result;
    };
</script>
<template>
    <AppLayout title="Ediciones">
        <Navigation :routeModule="route('even_dashboard')" :titleModule="'Eventos sociales'"
            :data="[
                {
                    route: route('even_ediciones_listado'), title: 'Ediciones',
                    children: [
                        { route: route('even_ediciones_equipos', edicion.id), title: 'Equipos', permissions: 'even_ediciones_equipos'},
                        { route: route('even_ediciones_fixtures', edicion.id), title: 'Partidos', permissions: 'even_ediciones_fixtures'},
                        { route: route('even_ediciones_pago_sanciones', edicion.id), title: 'Sanciones', permissions: 'even_ediciones_sanciones'},
                        { route: route('even_ediciones_exclusiones', edicion.id), title: 'Exclusiones', permissions: 'even_ediciones_exclusiones'},
                        { route: route('even_ediciones_actas_listado', edicion.id), title: 'Actas', permissions: 'even_ediciones_actas'}
                    ]
                },
                {route: route('even_ediciones_editar', edicion.id), title: edicion.name},
                {title: 'Exclusiones de jugadores'}
            ]"
        />
        <div class="mt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="grid grid-cols-3 w-full">
                    <div class="col-span-3 sm:col-span-1">
                        <h4 class="text-lg">Listado</h4>
                    </div>
                    <div class="col-span-3 sm:col-span-2">
                        <div class="flex justify-end gap-2">
                            <button @click="openModalAdd" type="button" class="btn btn-primary uppercase text-xs">
                                Excluir jugador
                            </button>
                            <Link :href="route('even_ediciones_listado')" :preserveState="true" class="btn btn-warning uppercase text-xs">
                                Ir atras
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel mt-4 p-4 text-sm border border-blue-300 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-800" role="alert">
                <div class="flex items-start gap-2">
                    <svg aria-hidden="true" class="flex-shrink-0 inline w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                    <span>
                        Al excluir a un jugador, desaparece de los rankings de la edición
                        (goleadores, mejores jugadores y arqueros) sin borrar sus estadísticas.
                        Los goles, asistencias y tarjetas quedan intactos en el historial.
                    </span>
                </div>
            </div>

            <div class="mt-6">
                <div class="panel">
                    <div v-if="exclusions.length > 0" class="">
                        <table>
                            <thead>
                                <tr>
                                    <th>Jugador</th>
                                    <th>Motivo</th>
                                    <th>Excluido por</th>
                                    <th>Fecha</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="exclusion in exclusions" :key="exclusion.id">
                                    <td class="px-2 py-2">
                                        <div class="font-bold">{{ exclusion.player.full_name || 'Jugador' }}</div>
                                    </td>
                                    <td class="px-2 py-2">{{ exclusion.reason }}</td>
                                    <td class="px-2 py-2">{{ exclusion.excluded_by || '-' }}</td>
                                    <td class="px-2 py-2">{{ formatDate(exclusion.excluded_at) }}</td>
                                    <td class="px-2 py-2">
                                        <button
                                            class="btn btn-sm btn-danger uppercase text-xs"
                                            @click="destroyExclusion(exclusion.id, exclusion.player.full_name)"
                                        >
                                            Restaurar
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="flex items-center justify-center p-6">
                        <Empty :description="'Sin jugadores excluidos'" />
                    </div>
                </div>
            </div>
        </div>

        <ModalLarge :show="displayModalAdd" :onClose="closeModalAdd" :icon="'/img/pelota-de-futbol.png'">
            <template #title>Excluir jugador</template>
            <template #message>El jugador dejará de aparecer en los rankings de la edición</template>
            <template #content>
                <div class="grid gap-4">
                    <div>
                        <InputLabel value="Jugador" />
                        <div v-if="flattenedPlayers().length > 0" class="mt-2">
                            <div v-for="teamName in Object.keys(players)" :key="teamName" class="mb-4">
                                <div class="text-xs font-bold uppercase text-gray-500 dark:text-neutral-400 mb-1.5">{{ teamName }}</div>
                                <div class="grid gap-1.5">
                                    <label
                                        v-for="p in players[teamName]"
                                        :key="p.player_id"
                                        class="flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 dark:border-neutral-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-neutral-800 transition-colors"
                                    >
                                        <input
                                            type="radio"
                                            name="player_id"
                                            class="form-radio text-primary"
                                            :value="p.player_id"
                                            v-model="form.player_id"
                                        />
                                        <span class="text-sm">{{ p.name }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-sm text-gray-500 dark:text-neutral-400">
                            Todos los jugadores inscritos ya están excluidos o no hay jugadores registrados.
                        </div>
                        <InputError :message="form.errors.player_id" />
                    </div>

                    <div>
                        <InputLabel value="Motivo de la exclusión" />
                        <TextInput v-model="form.reason" class="mt-1" type="text" placeholder="Ej: Expulsión definitiva por agresión" />
                        <InputError :message="form.errors.reason" />
                    </div>

                    <div>
                        <InputLabel value="Excluido por (quién tomó la decisión)" />
                        <TextInput v-model="form.excluded_by" class="mt-1" type="text" placeholder="Ej: Comisión de disciplina" />
                        <InputError :message="form.errors.excluded_by" />
                    </div>
                </div>
            </template>
            <template #buttons>
                <button @click="saveExclusion" type="button"
                    class="btn btn-primary text-xs uppercase"
                    :class="{ 'opacity-25': form.processing }" :disabled="form.processing"
                >
                    <icon-loader v-show="form.processing" class="w-4 h-4 animate-spin mr-1" />
                    Excluir
                </button>
            </template>
        </ModalLarge>
    </AppLayout>
</template>