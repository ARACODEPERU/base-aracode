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
    import SearchClients from './Partials/SearchClients.vue';
    import iconLoader from '@/Components/vristo/icon/icon-loader.vue';
    import InlineEditable from '@/Components/InlineEditable.vue';
    import InputError from '@/Components/InputError.vue';
    import IconTrashLines from '@/Components/vristo/icon/icon-trash-lines.vue';
    import { Empty } from 'ant-design-vue';

    const props = defineProps({
        equipo: {
            type: Object,
            default: () => ({})
        },
        edicion: {
            type: Object,
            default: () => ({})
        },
        ubigeo: {
            type: Object,
            default: () => ({}),
        },
        documentTypes: {
            type: Object,
            default: () => ({}),
        },
        players: {
            type: Object,
            default: () => ({}),
        }
    });

    const form = useForm({
        player_id: null,
        player_name: null,
        edition_id: props.edicion.id,
        team_id: props.equipo.id,
    });

    const displayModalClientSearch = ref(false);

    const openModalClientSearch = () => {
        displayModalClientSearch.value = true;
    }
    const closeModalClientSearch = () => {
        displayModalClientSearch.value = false;
    }
    const getDataPerson = async (data) => {
        form.player_id = data.id;
        form.player_name = data.full_name;
        displayModalClientSearch.value = false;
    }

    const savePlayer = () => {
        form.post(route('even_team_player_store'), {
            forceFormData: true,
            errorBag: 'savePlayer',
            preserveScroll: true,
            onSuccess: () => {
                Swal2.fire({
                    title: 'Enhorabuena',
                    text: 'Se registró correctamente',
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                form.reset();
            },
        });
    }

    const xhttp =  assetUrl;

    const formatDate = (date) => {
        if (!date) return '';
        const [year, month, day] = date.split('-');
        return `${day}-${month}-${year}`;
    };

    const formUpdate = useForm({
        player_id: null,
        edition_id: props.edicion.id,
        team_id: props.equipo.id,
        type: null,
        valor: null
    });

    const savingPlayerId = ref(null);
    const getSavingId = (person_id, index) => `${person_id}-${index}`;
    const updatePlayer = (valor, person_id, type) => {
        savingPlayerId.value = getSavingId(person_id, type);
        formUpdate.player_id = person_id;
        formUpdate.type = type;
        formUpdate.valor = valor;
        formUpdate.post(route('even_team_player_update'), {
            forceFormData: true,
            errorBag: 'savePlayer',
            preserveScroll: true,
            onSuccess: () => {
                Swal2.fire({
                    title: 'Enhorabuena',
                    text: 'Se registró correctamente',
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                formUpdate.reset();
                savingPlayerId.value = null;
            },
        });
    };

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

    const destroyPlayer = (player) => {
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
                return axios.delete(route('even_ediciones_equipos_player_destroy', [player.edition_id, player.team_id, player.person_id])).then((res) => {
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
                router.visit(route('even_ediciones_equipo_jugadores', [player.edition_id, player.team_id]), {
                    replace: false,
                    method: 'get',
                    preserveState: true,
                    preserveScroll: true,
                    only: ['players'],
                });
            }
        });
    }


</script>
<template>
    <AppLayout title="Ediciones">
        <Navigation :routeModule="route('even_dashboard')" :titleModule="'Eventos sociales'">
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <Link :href="route('even_ediciones_listado')" class="text-primary hover:underline">Ediciones</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <Link :href="route('even_ediciones_equipos', edicion.id)" class="text-primary hover:underline">Equipos</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>{{ equipo.name }}</span>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>Jugadores</span>
            </li>
        </Navigation>
        <div class="mt-5">
            <div class="grid sm:grid-cols-3 gap-6">
                <div class="sm:col-span-2">
                    <div class="grid sm:grid-cols-4 gap-6 w-full">
                        <div class="sm:col-span-2">
                            <input
                                :value="form.player_name"
                                class="form-input" type="text"
                                disabled
                            />
                        </div>
                        <div class="flex sm:col-span-2 gap-6">
                            <button @click="openModalClientSearch" class="btn btn-info uppercase">Buscar</button>
                            <button @click="savePlayer" class="btn btn-primary uppercase">Agregar al equipo</button>
                        </div>
                    </div>
                    <SearchClients
                        :display="displayModalClientSearch"
                        :closeModalClient="closeModalClientSearch"
                        @clientId="getDataPerson"
                        :ubigeo="ubigeo"
                        :documentTypes="documentTypes"
                    />
                    <div>
                        <InputError :message="form.errors.player_name" class="mt-2" />
                    </div>
                </div>
                <div class="flex items-center justify-end gap-6">
                    <button @click="exportImage" class="btn btn-danger uppercase">
                        Exportar imagen
                    </button>
                    <Link :href="route('even_ediciones_equipos', edicion.id)" :preserveState="true" class="btn btn-warning uppercase">
                        Ir atras
                    </Link>
                </div>
            </div>
            <div class="mt-6">
                <div ref="captureRef" class="panel">
                    <h3 class="text-lg uppercase mb-4 font-medium">Tabla de posiciones</h3>
                    <div class="table-responsive">
                        <table class="w-full text-sm text-left rtl:text-right">
                            <thead class="text-sm text-white border border-dark">
                                <tr>
                                    <th class="px-6 py-3 bg-dark font-medium text-left no-export"></th>
                                    <th scope="col" class="px-6 py-3 bg-dark font-medium text-left">
                                        Nombres
                                    </th>
                                    <th scope="col" class="px-6 py-3 bg-dark font-medium text-left">
                                        Fecha inscripción
                                    </th>
                                    <th scope="col" class="px-6 py-3 bg-dark font-medium text-left">
                                        Número de camiseta
                                    </th>
                                    <th scope="col" class="px-6 py-3 bg-dark font-medium text-left">
                                        Posición principal
                                    </th>
                                    <th scope="col" class="px-6 py-3 bg-dark font-medium text-left">
                                        Rol
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="players.length > 0">
                                    <tr v-for="player in players" class="bg-neutral-primary-soft border-b border-default hover:bg-neutral-secondary-medium">
                                        <td class="px-6 py-4 no-export">
                                            <button @click="destroyPlayer(player)" v-tippy="{content: 'Eliminar', placement: 'bottom'}" >
                                                <IconTrashLines class="w-5 h-5" />
                                            </button>
                                        </td>
                                        <td scope="row" class="flex items-center px-6 py-4 text-heading whitespace-nowrap">
                                            <img v-if="player.person.image" class="w-10 h-10 rounded-full" :src="xhttp + 'storage/' + player.person.image" alt="Jese image">
                                            <img v-else class="w-10 h-10 rounded-full" src="/img/icon-user.png" alt="Jese image">
                                            <div class="ps-3">
                                                <div class="text-base font-semibold">{{ player.person.full_name }}</div>
                                                <div class="font-normal text-body">{{ player.person.number }}</div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            {{ formatDate(player.registration_date) }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <InlineEditable
                                                v-model="player.jersey_number"
                                                placeholder="S/N"
                                                title="Número de camiseta"
                                                element="input"
                                                :loading="savingPlayerId === getSavingId(player.person_id, 1)"
                                                @save="value => updatePlayer(value, player.person_id, 1)"
                                            />
                                        </td>
                                        <td class="px-6 py-4">
                                            <InlineEditable
                                                v-model="player.position"
                                                placeholder="S/R"
                                                title="Número de camiseta"
                                                element="select"
                                                :data="[
                                                    'Ariete',
                                                    'Arquero',
                                                    'Box to box',
                                                    'Carrilero derecho',
                                                    'Carrilero izquierdo',
                                                    'Carrilero ofensivo',
                                                    'Defensa central',
                                                    'Delantero centro',
                                                    'Enganche',
                                                    'Extremo derecho',
                                                    'Extremo inverso',
                                                    'Extremo izquierdo',
                                                    'Falso 9',
                                                    'Guardameta',
                                                    'Interior',
                                                    'Interior invertido',
                                                    'Lateral derecho',
                                                    'Lateral izquierdo',
                                                    'Líbero',
                                                    'Media punta',
                                                    'Mediocampista mixto',
                                                    'Mediocentro defensivo'
                                                ]"
                                                :loading="savingPlayerId === getSavingId(player.person_id, 2)"
                                                @save="value => updatePlayer(value, player.person_id, 2)"
                                            />
                                        </td>
                                        <td class="px-6 py-4">
                                            <InlineEditable
                                                v-model="player.role_in_team"
                                                placeholder="S/R"
                                                title="Número de camiseta"
                                                element="select"
                                                :data="['Ninguno','Capitán', 'Sub-Capitán']"
                                                @save="value => updatePlayer(value, player.person_id, 3)"
                                                :loading="savingPlayerId === getSavingId(player.person_id, 3)"
                                            />
                                        </td>
                                    </tr>
                                </template>
                                <template v-else>
                                    <tr>
                                        <td colspan="6" ><Empty :description="'Sin jugadores'" /></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
