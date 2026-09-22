<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Swal2 from "sweetalert2";
    import { Link, useForm } from '@inertiajs/vue3';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { computed, ref } from "vue";
    import iconLoader from '@/Components/vristo/icon/icon-loader.vue';
    import ModalLarge from '@/Components/ModalLarge.vue';
    import { Empty } from 'ant-design-vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import TextInput from '@/Components/TextInput.vue';
    import InputError from '@/Components/InputError.vue';
    import Multiselect from '@suadelabs/vue3-multiselect';
    import '@suadelabs/vue3-multiselect/dist/vue3-multiselect.css';

    const props = defineProps({
        edicion: {
            type: Object,
            default: () => ({}),
        },
        suspensions: {
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
        type: 'definitive',
        matches_count: 1,
        starts_at: null,
        ends_at: null,
        reason: null,
        suspended_by: null
    });

    const openModalAdd = () => {
        form.reset();
        form.type = 'definitive';
        form.matches_count = 1;
        selectedPlayer.value = null;
        displayModalAdd.value = true;
    };

    // Opciones planas para el buscador: "Nombre — Equipo" (el prop players viene agrupado por equipo).
    const playerOptions = computed(() => {
        const options = [];
        for (const [teamName, members] of Object.entries(props.players || {})) {
            for (const p of members) {
                options.push({
                    player_id: p.player_id,
                    name: p.name,
                    team_name: teamName,
                    label: `${p.name} — ${teamName}`,
                });
            }
        }
        return options;
    });

    const selectedPlayer = ref(null);

    const onPlayerSelect = (option) => {
        selectedPlayer.value = option;
        form.player_id = option ? option.player_id : null;
    };

    const closeModalAdd = () => {
        displayModalAdd.value = false;
    };

    const typeOptions = [
        { value: 'definitive', label: 'Definitiva', help: 'No juega ni aparece en rankings por todo el torneo.' },
        { value: 'matches', label: 'Por partidos', help: 'No puede jugar la cantidad de partidos indicada. El sistema los cuenta automáticamente.' },
        { value: 'date_range', label: 'Por fechas', help: 'No puede jugar entre la fecha de inicio y la fecha de fin.' },
    ];

    const saveSuspension = () => {
        form.post(route('even_ediciones_suspensiones_store', props.edicion.id), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                displayModalAdd.value = false;
                Swal2.fire({
                    title: 'Enhorabuena',
                    text: 'Jugador suspendido correctamente',
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
            }
        });
    };

    const liftSuspension = (suspensionId, playerName) => {
        Swal2.fire({
            title: '¿Levantar suspensión?',
            text: `${playerName} podrá volver a jugar y aparecerá nuevamente en los rankings.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '¡Sí, Levantar!',
            cancelButtonText: 'Cancelar',
            showLoaderOnConfirm: true,
            padding: '2em',
            customClass: 'sweet-alerts',
        }).then((result) => {
            if (result.isConfirmed) {
                useForm({}).delete(route('even_ediciones_suspensiones_destroy', [props.edicion.id, suspensionId]), {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        Swal2.fire({
                            title: 'Enhorabuena',
                            text: 'Suspensión levantada correctamente',
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

    const typeLabel = (suspension) => {
        switch (suspension.type) {
            case 'definitive': return 'Definitiva (todo el torneo)';
            case 'matches': return `Por ${suspension.matches_count} partido(s)`;
            case 'date_range': return 'Por fechas';
            default: return suspension.type;
        }
    };

    const statusLabel = (suspension) => {
        if (suspension.lifted_at) return 'Levantada';

        switch (suspension.type) {
            case 'definitive': return 'Vigente';
            case 'matches':
                return suspension.matches_served >= suspension.matches_count
                    ? 'Cumplida'
                    : `Vigente (${suspension.matches_count - suspension.matches_served} partido(s) restante(s))`;
            case 'date_range': {
                const today = new Date(); today.setHours(0,0,0,0);
                const start = suspension.starts_at ? new Date(suspension.starts_at) : null;
                const end = suspension.ends_at ? new Date(suspension.ends_at) : null;
                if (start && start > today) return 'Programada';
                if (end && end < today) return 'Finalizada';
                return 'Vigente';
            }
            default: return 'Vigente';
        }
    };

    const statusClass = (suspension) => {
        const label = statusLabel(suspension);
        if (label === 'Levantada' || label === 'Finalizada' || label === 'Cumplida') {
            return 'bg-gray-200 text-gray-700 dark:bg-neutral-700 dark:text-neutral-200';
        }
        if (label === 'Programada') {
            return 'bg-orange-200 text-orange-800 dark:bg-orange-900/40 dark:text-orange-200';
        }
        return 'bg-red-200 text-red-800 dark:bg-red-900/40 dark:text-red-200';
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
                        { route: route('even_ediciones_suspensiones', edicion.id), title: 'Suspensiones', permissions: 'even_ediciones_suspensiones'},
                        { route: route('even_ediciones_actas_listado', edicion.id), title: 'Actas', permissions: 'even_ediciones_actas'}
                    ]
                },
                {route: route('even_ediciones_editar', edicion.id), title: edicion.name},
                {title: 'Suspensiones de jugadores'}
            ]"
        />
        <div class="mt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="grid grid-cols-3 w-full">
                    <div class="col-span-3 sm:col-span-1">
                        <h4 class="text-lg">Suspensiones</h4>
                    </div>
                    <div class="col-span-3 sm:col-span-2">
                        <div class="flex justify-end gap-2">
                            <button @click="openModalAdd" type="button" class="btn btn-primary uppercase text-xs">
                                Suspender jugador
                            </button>
                            <Link :href="route('even_ediciones_listado')" :preserveState="true" class="btn btn-warning uppercase text-xs">
                                Ir atras
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel mt-4 p-4 text-sm border border-red-300 bg-red-50 dark:bg-red-900/20 dark:border-red-800" role="alert">
                <div class="flex items-start gap-2">
                    <svg aria-hidden="true" class="flex-shrink-0 inline w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                    <span>
                        Un jugador <b>suspendido no puede jugar</b> sus partidos mientras la suspensión esté vigente:
                        el sistema lo bloquea al registrar el acta y no permite marcarlo como Titular o Suplente.
                        Tampoco aparece en los rankings (goleadores, mejores jugadores y arqueros) durante la suspensión.
                        Sus estadísticas pasadas se conservan intactas.
                    </span>
                </div>
            </div>

            <div class="mt-6">
                <div class="panel">
                    <div v-if="suspensions.length > 0" class="">
                        <table>
                            <thead>
                                <tr>
                                    <th>Jugador</th>
                                    <th>Tipo</th>
                                    <th>Estado</th>
                                    <th>Motivo</th>
                                    <th>Suspendido por</th>
                                    <th>Fecha</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="suspension in suspensions" :key="suspension.id">
                                    <td class="px-2 py-2">
                                        <div class="font-bold">{{ suspension.player.full_name || 'Jugador' }}</div>
                                    </td>
                                    <td class="px-2 py-2">{{ typeLabel(suspension) }}</td>
                                    <td class="px-2 py-2">
                                        <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold" :class="statusClass(suspension)">
                                            {{ statusLabel(suspension) }}
                                        </span>
                                    </td>
                                    <td class="px-2 py-2">{{ suspension.reason }}</td>
                                    <td class="px-2 py-2">{{ suspension.suspended_by || '-' }}</td>
                                    <td class="px-2 py-2">{{ formatDate(suspension.suspended_at) }}</td>
                                    <td class="px-2 py-2">
                                        <button
                                            v-if="!suspension.lifted_at"
                                            class="btn btn-sm btn-success uppercase text-xs"
                                            @click="liftSuspension(suspension.id, suspension.player.full_name)"
                                        >
                                            Levantar
                                        </button>
                                        <span v-else class="text-xs text-gray-400">Levantada el {{ formatDate(suspension.lifted_at) }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="flex items-center justify-center p-6">
                        <Empty :description="'Sin jugadores suspendidos'" />
                    </div>
                </div>
            </div>
        </div>

        <ModalLarge :show="displayModalAdd" :onClose="closeModalAdd" :icon="'/img/pelota-de-futbol.png'">
            <template #title>Suspender jugador</template>
            <template #message>Mientras esté vigente la suspensión, el jugador no podrá jugar ni aparecer en los rankings</template>
            <template #content>
                <div class="grid gap-4">
                    <div>
                        <InputLabel value="Jugador" />
                        <div v-if="playerOptions.length > 0" class="mt-2">
                            <Multiselect
                                v-model="selectedPlayer"
                                track-by="player_id"
                                label="label"
                                placeholder="Buscar jugador por nombre o equipo..."
                                selected-label="seleccionado"
                                select-label="Elegir"
                                deselect-label="Quitar"
                                :options="playerOptions"
                                :searchable="true"
                                :allow-empty="false"
                                :close-on-select="true"
                                @update:model-value="onPlayerSelect"
                            >
                                <template #option="{ option }">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-sm">{{ option.name }}</span>
                                        <span class="text-xs px-2 py-0.5 rounded bg-gray-100 dark:bg-neutral-700 text-gray-600 dark:text-neutral-300 whitespace-nowrap">{{ option.team_name }}</span>
                                    </div>
                                </template>
                                <template #noOptions>
                                    <span class="text-sm text-gray-500 px-3 py-2 block">No hay jugadores inscritos disponibles.</span>
                                </template>
                            </Multiselect>
                        </div>
                        <div v-else class="text-sm text-gray-500 dark:text-neutral-400">
                            Todos los jugadores inscritos ya tienen una suspensión registrada o no hay jugadores registrados.
                        </div>
                        <InputError :message="form.errors.player_id" />
                    </div>

                    <div>
                        <InputLabel value="Tipo de suspensión" />
                        <div class="grid sm:grid-cols-3 gap-2 mt-2">
                            <label
                                v-for="opt in typeOptions"
                                :key="opt.value"
                                class="flex items-center gap-2 px-3 py-2 rounded-lg border cursor-pointer transition-colors"
                                :class="form.type === opt.value
                                    ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20'
                                    : 'border-gray-200 dark:border-neutral-700 hover:bg-gray-50 dark:hover:bg-neutral-800'"
                            >
                                <input type="radio" name="type" class="form-radio text-primary" :value="opt.value" v-model="form.type" />
                                <span class="text-sm font-medium">{{ opt.label }}</span>
                            </label>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-neutral-400 mt-1.5">
                            {{ typeOptions.find(o => o.value === form.type)?.help }}
                        </p>
                    </div>

                    <div v-if="form.type === 'matches'">
                        <InputLabel value="¿Cuántos partidos no podrá jugar?" />
                        <TextInput v-model.number="form.matches_count" class="mt-1" type="number" min="1" max="100" />
                        <p class="text-xs text-gray-500 dark:text-neutral-400 mt-1.5">
                            El sistema cuenta automáticamente los partidos de su equipo que se jueguen desde la suspensión.
                        </p>
                        <InputError :message="form.errors.matches_count" />
                    </div>

                    <div v-if="form.type === 'date_range'" class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Desde" />
                            <TextInput v-model="form.starts_at" class="mt-1" type="date" />
                            <InputError :message="form.errors.starts_at" />
                        </div>
                        <div>
                            <InputLabel value="Hasta" />
                            <TextInput v-model="form.ends_at" class="mt-1" type="date" />
                            <InputError :message="form.errors.ends_at" />
                        </div>
                    </div>

                    <div>
                        <InputLabel value="Motivo de la suspensión" />
                        <TextInput v-model="form.reason" class="mt-1" type="text" placeholder="Ej: Agresión a un rival en la fecha 3" />
                        <InputError :message="form.errors.reason" />
                    </div>

                    <div>
                        <InputLabel value="Suspendido por (quién tomó la decisión)" />
                        <TextInput v-model="form.suspended_by" class="mt-1" type="text" placeholder="Ej: Comisión de disciplina" />
                        <InputError :message="form.errors.suspended_by" />
                    </div>
                </div>
            </template>
            <template #buttons>
                <button @click="saveSuspension" type="button"
                    class="btn btn-primary text-xs uppercase"
                    :class="{ 'opacity-25': form.processing }" :disabled="form.processing"
                >
                    <icon-loader v-show="form.processing" class="w-4 h-4 animate-spin mr-1" />
                    Suspender
                </button>
            </template>
        </ModalLarge>
    </AppLayout>
</template>
