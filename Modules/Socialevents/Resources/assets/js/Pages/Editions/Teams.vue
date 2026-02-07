<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import { useForm } from '@inertiajs/vue3';
    import { domToPng } from 'modern-screenshot'
    import Swal2 from "sweetalert2";
    import { Link, router } from '@inertiajs/vue3';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { computed, nextTick , ref } from "vue";
    import Multiselect from '@suadelabs/vue3-multiselect';
    import '@suadelabs/vue3-multiselect/dist/vue3-multiselect.css';
    import iconTrashLines from '@/Components/vristo/icon/icon-trash-lines.vue';
    import IconPlus from '@/Components/vristo/icon/icon-plus.vue';
    import { Empty } from 'ant-design-vue';

    const props = defineProps({
        edicion: {
            type: Object,
            default: () => ({}),
        },
        teams: {
            type: Object,
            default: () => ({}),
        },
        currentEquipment: {
            type: Object,
            default: () => ({}),
        },
    });

    const form = useForm({
        edition_id: props.edicion.id,
        team_id: null,
        team_name: null
    });

    const xhttp =  assetUrl;

    const destroyTeam = (eId, tId) => {
        Swal2.fire({
            title: '¿Estas seguro?',
            text: "¡No podrás revertir esto!",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '¡Sí, Eliminar!',
            cancelButtonText: 'Cancelar',
            showLoaderOnConfirm: true,
            padding: '2em',
            customClass: 'sweet-alerts',
            preConfirm: () => {
                return axios.delete(route('even_ediciones_equipos_destroy', [eId, tId])).then((res) => {
                    if (!res.data.success) {
                        Swal2.showValidationMessage(res.data.message)
                    }
                    return res
                });
            },
            allowOutsideClick: () => !Swal2.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                Swal2.fire({
                    title: 'Enhorabuena',
                    text: 'Se Eliminó correctamente',
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                router.visit(route('even_ediciones_equipos', props.edicion.id), {
                    replace: false,
                    method: 'get',
                    preserveState: true,
                    preserveScroll: true,
                    only: ['urrentEquipment', 'teams'],
                });
            }
        });
    }

    const eventSelected = ref(null);

    const selectTeam = () => {
        form.team_name = eventSelected.value.name;
        form.team_id = eventSelected.value.id;
    }

    const addTeamSave = () => {
        form.post(route('even_ediciones_equipos_agregar', props.edicion.id), {
            forceFormData: true,
            errorBag: 'addTeamSave',
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

    const captureRef = ref(null);

    const exportImage = async () => {
        // 1. Esperamos a que Vue termine de renderizar cualquier cambio
        await nextTick()

        if (!captureRef.value) return

        try {
            // 2. Esperamos a que las fuentes del navegador estén cargadas
            await document.fonts.ready

            // 3. Generamos la imagen
            const dataUrl = await domToPng(captureRef.value, {
                quality: 1,
                scale: 3, // Mayor escala = mejor resolución para redes sociales
                backgroundColor: '#ffffff', // Fondo blanco sólido
                filter: (node) => {
                    // Si el nodo es un elemento y tiene la clase 'no-export', lo ignoramos
                    if (node.classList && node.classList.contains('no-export')) {
                        return false;
                    }
                    return true;
                }
            })

            // 4. Descarga automática
            const link = document.createElement('a')
            link.download = `tabla-posiciones-${new Date().getTime()}.png`
            link.href = dataUrl
            link.click()

        } catch (error) {
            Swal2.fire({
                title: 'Error al exportar',
                text: 'Descripción: '+ error,
                icon: 'error',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        }
    }
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
                        { route: route('even_ediciones_actas_listado', edicion.id), title: 'Actas', permissions: 'even_ediciones_actas'}
                    ]

                },
                {route:route('even_ediciones_editar', edicion.id), title: edicion.name},
                {title: 'Inscripciones y Tabla'}
            ]"
        />
        <div class="pt-5">
            <div class="mt-5">
                <div class="grid sm:grid-cols-2 gap-6 w-full">
                    <div class="flex items-center gap-6">
                        <div class="flex-1">
                            <Multiselect
                                id="single-select-object"
                                v-model="eventSelected"
                                track-by="id"
                                label="name"
                                placeholder="Seleccionar equipo"
                                selected-label="seleccionado"
                                select-label="Elegir"
                                deselect-label="Quitar"
                                :options="teams"
                                :allow-empty="false"
                                :searchable="true"
                                @update:model-value="selectTeam"
                            >
                            </Multiselect>
                        </div>
                        <button @click="addTeamSave" class="btn btn-primary uppercase text-xs" type="button"><icon-plus class="w-4 h-4 mr-1" />Agregar</button>
                    </div>
                    <div class="flex items-center justify-end gap-6">
                        <button @click="exportImage" class="btn btn-danger uppercase text-xs">
                            Exportar imagen
                        </button>
                        <Link :href="route('even_ediciones_listado')" :preserveState="true" class="btn btn-warning uppercase text-xs">
                            Ir atras
                        </Link>
                    </div>
                </div>
                <div class="mt-6">
                    <div ref="captureRef" class="panel" >
                        <h3 class="text-lg uppercase mb-4 font-medium">Tabla de posiciones</h3>
                        <div class="table-responsive">
                            <table class="w-full text-sm text-left rtl:text-right">
                                <thead class="bg-gradient-to-r from-gray-800 to-gray-700 text-white">
                                    <tr>
                                        <th scope="col" class="px-4 py-4 font-bold text-center">#</th>
                                        <th class="no-export px-4 py-4 font-bold text-center">Acciones</th>
                                        <th scope="col" class="px-4 py-4 font-bold text-left">Equipo</th>
                                        <th scope="col" class="px-4 py-4 font-bold text-center">PTS</th>
                                        <th scope="col" class="px-4 py-4 font-bold text-center">PJ</th>
                                        <th scope="col" class="px-4 py-4 font-bold text-center">PG</th>
                                        <th scope="col" class="px-4 py-4 font-bold text-center">PE</th>
                                        <th scope="col" class="px-4 py-4 font-bold text-center">PP</th>
                                        <th scope="col" class="px-4 py-4 font-bold text-center">DG</th>
                                        <th scope="col" class="px-4 py-4 font-bold text-center">GF</th>
                                        <th scope="col" class="px-4 py-4 font-bold text-center">GC</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <template v-if="currentEquipment.length > 0">
                                        <tr v-for="(team, index) in currentEquipment"
                                            :class="[
                                                'hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200',
                                                index === 0 ? 'bg-gradient-to-r from-yellow-50 to-yellow-100 dark:from-yellow-900/20 dark:to-yellow-800/20' :
                                                index === 1 ? 'bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-600/20 dark:to-gray-500/20' :
                                                index === 2 ? 'bg-gradient-to-r from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20' :
                                                index % 2 === 0 ? 'bg-white dark:bg-gray-800' : 'bg-gray-50 dark:bg-gray-700'
                                            ]"
                                        >
                                            <td class="px-4 py-2 text-center font-bold text-lg">
                                                <span :class="[
                                                    'inline-flex items-center justify-center w-8 h-8 rounded-full text-white font-bold',
                                                    index === 0 ? 'bg-yellow-500' :
                                                    index === 1 ? 'bg-gray-500' :
                                                    index === 2 ? 'bg-orange-600' :
                                                    'bg-blue-500'
                                                ]">
                                                    {{ index + 1 }}
                                                </span>
                                            </td>
                                            <td class="no-export px-4 py-2">
                                                <div class="flex items-center justify-center gap-2">
                                                    <Link v-can="'even_ediciones_equipo_jugadores'" :href="route('even_ediciones_equipo_jugadores',[team.edition_id, team.team_id])" class="no-export text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-colors" v-tippy="{ content: 'Jugadores', placement: 'bottom'}" type="button">
                                                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                                            <path fill="currentColor" d="M320 32c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 5.4c0 45-23.6 86.6-62.1 109.8l-4.6 2.8C131.4 184.7 96 247.1 96 314.6L96 384c0 17.7 14.3 32 32 32s32-14.3 32-32l0-69.4c0-16.7 3.3-33 9.4-48L359.2 500.2c11.1 13.7 31.3 15.8 45 4.7s15.8-31.3 4.7-45L295.2 320 400 320 438.4 371.2c10.6 14.1 30.7 17 44.8 6.4s17-30.7 6.4-44.8l-43.2-57.6C437.3 263.1 423.1 256 408 256l-89 0-62.9-75.5c40.3-36 63.9-87.9 63.9-143.1l0-5.4zM104 144a56 56 0 1 0 0-112 56 56 0 1 0 0 112z"/>
                                                        </svg>
                                                    </Link>
                                                    <button v-can="'even_ediciones_equipos_eliminar'" @click="destroyTeam(team.edition_id, team.team_id)" class="no-export text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 transition-colors" v-tippy="{ content: 'Eliminar equipo', placement: 'bottom'}" type="button">
                                                        <iconTrashLines class="w-5 h-5" />
                                                    </button>
                                                </div>
                                            </td>
                                            <td scope="row" class="flex items-center px-4 py-2 text-heading whitespace-nowrap gap-4">
                                                <img v-if="team.equipo.logo_path" class="w-12 h-12 rounded-full border-2 border-gray-300 dark:border-gray-600" :src="xhttp + 'storage/' + team.equipo.logo_path" :alt="team.equipo.short_name">
                                                <img v-else class="w-12 h-12 rounded-full border-2 border-gray-300 dark:border-gray-600" :src="`https://ui-avatars.com/api/?name=${team.equipo.short_name}&size=150&length=4`" :alt="team.equipo.short_name">
                                                <div class="text-base font-semibold text-gray-900 dark:text-white">{{ team.equipo.name }}</div>
                                            </td>
                                            <td class="px-4 py-2 text-center">
                                                <span class="bg-blue-600 text-white px-3 py-1 rounded-full font-bold text-lg">{{ team.points }}</span>
                                            </td>
                                            <td class="px-4 py-2 text-center font-medium text-gray-700 dark:text-gray-300">{{ team.matches_played }}</td>
                                            <td class="px-4 py-2 text-center font-medium text-green-600">{{ team.matches_won }}</td>
                                            <td class="px-4 py-2 text-center font-medium text-yellow-600">{{ team.matches_drawn }}</td>
                                            <td class="px-4 py-2 text-center font-medium text-red-600">{{ team.matches_lost }}</td>
                                            <td class="px-4 py-2 text-center font-medium" :class="team.goal_difference >= 0 ? 'text-green-600' : 'text-red-600'">{{ team.goal_difference }}</td>
                                            <td class="px-4 py-2 text-center font-medium text-gray-700 dark:text-gray-300">{{ team.goals_for }}</td>
                                            <td class="px-4 py-2 text-center font-medium text-gray-700 dark:text-gray-300">{{ team.goals_against }}</td>
                                        </tr>
                                    </template>
                                    <template v-else>
                                        <tr>
                                            <td colspan="12" ><Empty :description="'Sin equipos'" /></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
