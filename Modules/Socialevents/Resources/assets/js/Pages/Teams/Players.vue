<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import { useForm } from '@inertiajs/vue3';
    import { domToPng } from 'modern-screenshot'
    import Swal2 from "sweetalert2";
    import { Link, router } from '@inertiajs/vue3';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { computed, nextTick , ref } from "vue";
    import SearchClients from './Partials/SearchClients.vue';
    import IconBallSoccer from '@/Components/vristo/icon/icon-ball-soccer.vue';
    import InlineEditable from '@/Components/InlineEditable.vue';
    import InputError from '@/Components/InputError.vue';
    import IconTrashLines from '@/Components/vristo/icon/icon-trash-lines.vue';
    import IconPencilPaper from '@/Components/vristo/icon/icon-pencil-paper.vue';
    import IconPrinter from '@/Components/vristo/icon/icon-printer.vue';
    import { Empty } from 'ant-design-vue';
    import ModalSmall from '@/Components/ModalSmall.vue';
    import ModalLargeX from '@/Components/ModalLargeX.vue';
    import CropperImage from '@/Components/CropperImage.vue';
    import ImageCompressorjs from '@/Components/ImageCompressorjs.vue';

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

    const formImage = useForm({
        player_id: null,
        image: '',
        edition_id: props.edicion.id,
        team_id: props.equipo.id,
    });

    const displayModalImage = ref(false);

    const openModalImage = (player) => {
        formImage.player_name = player.person.full_name;
        formImage.player_id = player.person.id;
        displayModalImage.value = true;
    }

    const closeModalImage = () => {
        displayModalImage.value = false;
        formImage.reset();
    }

    const onCropImage = (base64) => {
        formImage.image = base64;
    }

    // Edición de los datos personales del jugador (nombres, documento, etc.)
    const formEdit = useForm({
        edition_id: props.edicion.id,
        team_id: props.equipo.id,
        player_id: null,
        names: '',
        father_lastname: '',
        mother_lastname: '',
        document_type_id: null,
        number: '',
        birthdate: '',
        image: '',
    });

    const displayModalEdit = ref(false);

    const openModalEdit = (player) => {
        formEdit.clearErrors();
        formEdit.player_id = player.person_id;
        formEdit.names = player.person.names ?? '';
        formEdit.father_lastname = player.person.father_lastname ?? '';
        formEdit.mother_lastname = player.person.mother_lastname ?? '';
        formEdit.document_type_id = player.person.document_type_id ?? null;
        formEdit.number = player.person.number ?? '';
        formEdit.birthdate = player.person.birthdate ?? '';
        formEdit.image = '';
        displayModalEdit.value = true;
    }

    // Abre el PDF del fotochet del jugador en una pestaña nueva.
    const printPlayerCard = async (player) => {
        try {
            const response = await axios.get(route('even_ediciones_equipos_player_pdf_card', [props.edicion.id, props.equipo.id, player.person_id]));
            if (response.data.url) {
                window.open(response.data.url, '_blank');
            }
        } catch (error) {
            window.alertaSistema('Error', 'No se pudo generar el fotochet.', 'error');
        }
    }

    // Jugadores marcados para imprimir sus carnets en un solo PDF A4.
    const jugadoresSeleccionados = ref([]);
    const todosSeleccionados = computed(() => props.players.length > 0 && jugadoresSeleccionados.value.length === props.players.length);

    // Marca o desmarca a todos los jugadores de la tabla.
    const toggleSeleccionarTodos = () => {
        jugadoresSeleccionados.value = todosSeleccionados.value ? [] : props.players.map((p) => p.person_id);
    }

    // Genera un PDF A4 con los carnets de los jugadores marcados y lo abre en una pestaña nueva.
    const printPlayerCardsA4 = async () => {
        if (jugadoresSeleccionados.value.length === 0) {
            window.alertaSistema('Atención', 'Selecciona al menos un jugador.', 'warning');
            return;
        }
        try {
            const response = await axios.post(
                route('even_ediciones_equipos_players_pdf_cards', [props.edicion.id, props.equipo.id]),
                { player_ids: jugadoresSeleccionados.value }
            );
            if (response.data.url) {
                window.open(response.data.url, '_blank');
            }
        } catch (error) {
            const mensaje = error.response?.data?.message || 'No se pudieron generar los carnets.';
            window.alertaSistema('Error', mensaje, 'error');
        }
    }

    const closeModalEdit = () => {
        displayModalEdit.value = false;
        formEdit.reset();
        formEdit.clearErrors();
    }

    // La foto es opcional: solo se envía si el usuario recorta una nueva.
    const onCropEditImage = (base64) => {
        formEdit.image = base64;
    }

    const saveEdit = () => {
        // Confirmación antes de guardar; el envío al servidor ocurre dentro del mismo diálogo.
        window.alertaSistema('Editar jugador', '¿Deseas guardar los cambios de este jugador?', 'question', {
            duration: 0,
            showCancelButton: true,
            confirmButtonText: 'Sí, guardar',
            cancelButtonText: 'Cancelar',
            showLoaderOnConfirm: true,
            preConfirm: () => new Promise((resolve) => {
                formEdit.post(route('even_team_player_person_update'), {
                    forceFormData: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        resolve(true);
                    },
                    onError: (errors) => {
                        // Muestra el error dentro del mismo diálogo y lo deja abierto para corregir.
                        // Resolver sin valor mantiene el modal abierto y quita el loader.
                        const mensaje = Object.values(errors || {})[0] || 'Revisa los datos del formulario.';
                        Swal2.showValidationMessage(mensaje);
                        resolve(false);
                    },
                });
            }),
        }).then((result) => {
            if (result?.isConfirmed) {
                closeModalEdit();
                window.alertaSistema('Enhorabuena', 'Jugador actualizado correctamente', 'success');
                // Recargar la lista de jugadores
                router.reload({ only: ['players'] });
            }
        });
    }

    const saveImage = () => {
        formImage.post(route('even_team_player_image_update'), {
            errorBag: 'saveImage',
            preserveScroll: true,
            onSuccess: () => {
                Swal2.fire({
                    title: 'Enhorabuena',
                    text: 'Imagen actualizada correctamente',
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                closeModalImage();
                // Reload players
                router.reload({ only: ['players'] });
            },
        });
    }

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
        <Navigation :routeModule="route('even_dashboard')" :titleModule="'Eventos sociales'"
            :data="[
                {
                    route: route('even_ediciones_listado'), title: 'Ediciones',
                    children: [
                        { route: route('even_ediciones_fixtures', edicion.id), title: 'Partidos', permissions: 'even_ediciones_fixtures'},
                        { route: route('even_ediciones_pago_sanciones', edicion.id), title: 'Sanciones', permissions: 'even_ediciones_sanciones'},
                        { route: route('even_ediciones_exclusiones', edicion.id), title: 'Exclusiones', permissions: 'even_ediciones_exclusiones'},
                        { route: route('even_ediciones_suspensiones', edicion.id), title: 'Suspensiones', permissions: 'even_ediciones_suspensiones'},
                        { route: route('even_ediciones_actas_listado', edicion.id), title: 'Actas', permissions: 'even_ediciones_actas'},
                    ]
                },
                {route: route('even_ediciones_equipos', edicion.id), title: 'Equipos'},
                {route: route('even_equipos_edit', equipo.id), title: equipo.name},
                {title: 'Jugadores'}
            ]"
        />
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
                        <div class="flex sm:col-span-2 gap-6 items-center">
                            <button @click="openModalClientSearch" class="btn btn-info uppercase text-xs">Buscar / Registrar</button>
                            <button @click="savePlayer" class="btn btn-primary uppercase text-xs">Agregar al equipo</button>
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
                    <button @click="printPlayerCardsA4" :disabled="jugadoresSeleccionados.length === 0" class="btn btn-success uppercase text-xs disabled:opacity-50">
                        Imprimir carnets seleccionados ({{ jugadoresSeleccionados.length }})
                    </button>
                    <button @click="exportImage" class="btn btn-danger uppercase text-xs">
                        Exportar imagen
                    </button>
                    <Link :href="route('even_ediciones_equipos', edicion.id)" :preserveState="true" class="btn btn-warning uppercase text-xs">
                        Ir atras
                    </Link>
                </div>
            </div>
            <div class="mt-6">
                <div ref="captureRef" class="panel">
                    <h3 class="text-lg uppercase mb-4 font-medium">Lista de jugadores — {{ equipo.name }}</h3>
                    <div class="table-responsive">
                        <table class="w-full text-sm text-left rtl:text-right border-collapse">
                            <thead class="text-sm text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-800">
                                <tr>
                                    <th class="px-6 py-4 font-medium text-left no-export w-16"></th>
                                    <th scope="col" class="px-6 py-4 font-medium text-left no-export w-12">
                                        <input type="checkbox" class="form-checkbox" :checked="todosSeleccionados" @change="toggleSeleccionarTodos" title="Seleccionar todos" />
                                    </th>
                                    <th scope="col" class="px-6 py-4 font-medium text-left">
                                        Nombres
                                    </th>
                                    <th scope="col" class="px-6 py-4 font-medium text-left">
                                        Fecha inscripción
                                    </th>
                                    <th scope="col" class="px-6 py-4 font-medium text-left">
                                        Número de camiseta
                                    </th>
                                    <th scope="col" class="px-6 py-4 font-medium text-left">
                                        Posición principal
                                    </th>
                                    <th scope="col" class="px-6 py-4 font-medium text-left">
                                        Rol
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="players.length > 0">
                                    <tr v-for="(player, index) in players" :class="index % 2 === 0 ? 'bg-white dark:bg-gray-900' : 'bg-gray-50 dark:bg-gray-800'">
                                        <td class="px-6 py-4 no-export">
                                            <button @click="openModalEdit(player)" v-tippy="{content: 'Editar datos', placement: 'bottom'}" class="text-primary hover:text-primary/80 mr-2">
                                                <IconPencilPaper class="w-5 h-5" />
                                            </button>
                                            <button @click="printPlayerCard(player)" v-tippy="{content: 'Imprimir fotochet', placement: 'bottom'}" class="text-primary hover:text-primary/80 mr-2">
                                                <IconPrinter class="w-5 h-5" />
                                            </button>
                                            <button @click="destroyPlayer(player)" v-tippy="{content: 'Eliminar', placement: 'bottom'}" class="text-red-500 hover:text-red-700">
                                                <IconTrashLines class="w-5 h-5" />
                                            </button>
                                        </td>
                                        <td class="px-6 py-4 no-export">
                                            <input type="checkbox" class="form-checkbox" :value="player.person_id" v-model="jugadoresSeleccionados" />
                                        </td>
                                        <td scope="row" class="px-6 py-4 text-gray-900 dark:text-white whitespace-nowrap">
                                            <div class="flex items-center">
                                                <img v-if="player.person.image" class="w-12 h-12 rounded-full cursor-pointer hover:opacity-80 transition" :src="xhttp + 'storage/' + player.person.image" alt="Imagen del jugador" @click="openModalImage(player)">
                                                <img v-else class="w-12 h-12 rounded-full cursor-pointer hover:opacity-80 transition" src="/img/icon-user.png" alt="Sin imagen" @click="openModalImage(player)">
                                                <div class="ps-3">
                                                    <div class="text-base font-semibold">{{ player.person.full_name }}</div>
                                                    <div class="font-normal text-gray-500 dark:text-gray-400">{{ player.person.number }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-2">
                                            {{ formatDate(player.registration_date) }}
                                        </td>
                                        <td class="px-6 py-2">
                                            <InlineEditable
                                                v-model="player.jersey_number"
                                                placeholder="S/N"
                                                title="Número de camiseta"
                                                element="input"
                                                :loading="savingPlayerId === getSavingId(player.person_id, 1)"
                                                @save="value => updatePlayer(value, player.person_id, 1)"
                                            />
                                        </td>
                                        <td class="px-6 py-2">
                                            <InlineEditable
                                                v-model="player.position"
                                                placeholder="S/R"
                                                title="Posición"
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
                                        <td class="px-6 py-2">
                                            <InlineEditable
                                                v-model="player.role_in_team"
                                                placeholder="S/R"
                                                title="Rol"
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
                                        <td colspan="7" ><Empty :description="'Sin jugadores'" /></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <ModalSmall :show="displayModalImage" :onClose="closeModalImage" :icon="'/img/atencion.png'">
            <template #title>{{ formImage.player_name }}</template>
            <template #message>Selecciona una nueva imagen</template>
            <template #content>
                <div class="mb-4">
                    <CropperImage
                        @onCrop="onCropImage"
                        aspectRatio="1"
                        viewMode="1"
                        imgDefault="/img/icon-user.png"
                    />
                    <InputError :message="formImage.errors.image" class="mt-2" />
                </div>
            </template>
            <template #buttons>
                <button @click="saveImage" class="btn btn-primary text-xs uppercase" :disabled="formImage.processing">
                    <IconBallSoccer v-if="formImage.processing" class="w-4 h-4 animate-bounce-ball mr-1" />
                    Guardar
                </button>
            </template>
        </ModalSmall>

        <ModalLargeX :show="displayModalEdit" :onClose="closeModalEdit" :icon="'/img/atencion.png'">
            <template #title>Editar jugador</template>
            <template #content>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Datos personales: columna izquierda -->
                    <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4 content-start">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold uppercase mb-1">Nombres</label>
                            <input v-model="formEdit.names" type="text" class="form-input" maxlength="255" />
                            <InputError :message="formEdit.errors.names" class="mt-1" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase mb-1">Apellido paterno</label>
                            <input v-model="formEdit.father_lastname" type="text" class="form-input" maxlength="255" />
                            <InputError :message="formEdit.errors.father_lastname" class="mt-1" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase mb-1">Apellido materno</label>
                            <input v-model="formEdit.mother_lastname" type="text" class="form-input" maxlength="255" />
                            <InputError :message="formEdit.errors.mother_lastname" class="mt-1" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase mb-1">Tipo de documento</label>
                            <select v-model="formEdit.document_type_id" class="form-select">
                                <option :value="null">Seleccione</option>
                                <option v-for="documentType in documentTypes" :key="documentType.id" :value="documentType.id">
                                    {{ documentType.description }}
                                </option>
                            </select>
                            <InputError :message="formEdit.errors.document_type_id" class="mt-1" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase mb-1">Número de documento</label>
                            <input v-model="formEdit.number" type="text" class="form-input" maxlength="20" />
                            <InputError :message="formEdit.errors.number" class="mt-1" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold uppercase mb-1">Fecha de nacimiento (opcional)</label>
                            <input v-model="formEdit.birthdate" type="date" class="form-input" />
                            <InputError :message="formEdit.errors.birthdate" class="mt-1" />
                        </div>
                    </div>
                    <!-- Foto: columna derecha, comprimida en el navegador antes de subir -->
                    <div class="md:col-span-1">
                        <label class="block text-xs font-semibold uppercase mb-1">Foto (opcional)</label>
                        <ImageCompressorjs :onImageCompressed="onCropEditImage" />
                        <InputError :message="formEdit.errors.image" class="mt-2" />
                    </div>
                </div>
            </template>
            <template #buttons>
                <button @click="closeModalEdit" class="btn btn-outline-danger text-xs uppercase" :disabled="formEdit.processing">
                    Cancelar
                </button>
                <button @click="saveEdit" class="btn btn-primary text-xs uppercase" :disabled="formEdit.processing">
                    <IconBallSoccer v-if="formEdit.processing" class="w-4 h-4 animate-bounce-ball mr-1" />
                    Guardar cambios
                </button>
            </template>
        </ModalLargeX>
    </AppLayout>
</template>
