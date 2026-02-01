<script setup>
    import { useForm } from '@inertiajs/vue3';
    import { domToPng } from 'modern-screenshot'
    import Swal2 from "sweetalert2";
    import { Link, router } from '@inertiajs/vue3';
    import ModalLarge from '@/Components/ModalLarge.vue';
    import { ref, reactive, computed, watch, nextTick } from 'vue';
    import SuccessButton from '@/Components/SuccessButton.vue';
    import InputError from '@/Components/InputError.vue';
    import IconPencilPaper from '@/Components/vristo/icon/icon-pencil-paper.vue';
    import IconTrashLines from '@/Components/vristo/icon/icon-trash-lines.vue';
    import ModalSmall from '@/Components/ModalSmall.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import TextInput from '@/Components/TextInput.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import IconBallSoccer from '@/Components/vristo/icon/icon-ball-soccer.vue';
    import flatPickr from 'vue-flatpickr-component';
    import 'flatpickr/dist/flatpickr.css';
    import { Spanish } from "flatpickr/dist/l10n/es.js"
    import ModalLargeXX from '@/Components/ModalLargeXX.vue';
    import IconMinus from '@/Components/vristo/icon/icon-minus.vue';
    import IconPlus from '@/Components/vristo/icon/icon-plus.vue';
    import IconStar from '@/Components/vristo/icon/icon-star.vue';
    import IconStarFiller from '@/Components/vristo/icon/icon-star-filler.vue';
    import ModalLargeX from '@/Components/ModalLargeX.vue';
    import SearchClients from '../../Teams/Partials/SearchClients.vue';
    import IconX from '@/Components/vristo/icon/icon-x.vue';
    import IconImages from '@/Components/vristo/icon/icon-images.vue';


    const props = defineProps({
        edition: {
            type: Object,
            default: () => ({}),
        },
        teams: {
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
        },
        ubigeo: {
            type: Object,
            default: () => ({}),
        },
        documentTypes: {
            type: Object,
            default: () => ({}),
        }
    });

    const { fixture } = props;


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
        equipo_h_id: null,
        equipo_a_id: null,
        round_number: null,
        group_name: null,
        phase: null,
        location: null,
        status: null,
    });

    const teamsSortedDesc = computed(() => {
        return [...props.teams].sort((a, b) => (b.points || 0) - (a.points || 0));
    });

    const teamsSortedAsc = computed(() => {
        return [...props.teams].sort((a, b) => (a.points || 0) - (b.points || 0));
    });

    const openModalEdit = (partido) => {
        if(partido.status != 'closed'){
            formMatch.id = partido.id;
            formMatch.match_date = partido.match_date;
            formMatch.equipo_h_name = partido.equipolocal?.name || null;
            formMatch.equipo_a_name = partido.equipovisitante?.name || null;
            formMatch.equipo_h_id = partido.equipolocal?.id || null;
            formMatch.equipo_a_id = partido.equipovisitante?.id || null;
            formMatch.round_number = partido.round_number;
            formMatch.group_name = partido.group_name;
            formMatch.phase = partido.phase;
            formMatch.location = partido.location;
            formMatch.status = partido.status;
            displayModalEdit.value = true;
        } else {
            showAlertStatus(partido.status)
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

    const configFlatPickr = {
        enableTime: true,
        dateFormat: 'Y-m-d H:i',
        //minDate: 'today',
        locale: Spanish
    };

    const formScore = useForm({
        id: null,
        edition_id: props.edition.id,
        price_red: props.edition.direct_red_price,
        double_yellow: props.edition.double_yellow_price,
        yellow: props.edition.yellow_price,
        equipo_h_name: null,
        equipo_a_name: null,
        equipo_h_id: null,
        equipo_a_id: null,
        score_h: null,
        score_a: null,
        status: null,
        players_h: [],
        players_a: []
    });

    const displayModalScore = ref(false);
    const playersh = ref([]);
    const playersa = ref([]);

    const openModalScore = (partido) => {
        formScore.id = partido.id;

        if(partido.status == 'pending' || partido.status == 'live'){

            if(partido.equipolocal && partido.equipovisitante){
                formScore.equipo_h_name = partido.equipolocal.name;
                formScore.equipo_a_name = partido.equipovisitante.name;
                formScore.equipo_h_id = partido.equipolocal.id;
                formScore.equipo_a_id = partido.equipovisitante.id;
                formScore.score_h = partido.score_h ?? 0;
                formScore.score_a = partido.score_a ?? 0;

                // Función auxiliar para procesar jugadores
                const mapPlayers = (players) => {
                    if (!players) return [];

                    return players.map(p => {
                        // En el back pusimos 'stats' como array
                        const s = (p.stats && p.stats.length > 0) ? p.stats[0] : {};

                        // Sanciones
                        const sanctions = p.sanctions || [];

                        // 1. Detectamos si tiene Doble Amarilla (ya sea por el booleano o buscando en el array)
                        const hasDoubleYellow = !!p.is_double_yellow || sanctions.some(s => s.type === 'double_yellow');

                        // 2. Detectamos si tiene Roja Directa
                        const hasDirectRed = sanctions.some(s => s.type === 'red');

                        return {
                            ...p,
                            // Usamos match_role que inyectamos directamente en el back
                            match_role: p.match_role || null,

                            // Estadísticas
                            goals: s.goals || 0,
                            assists: s.assists || 0,
                            saves: s.saves || 0,
                            is_mvp: !!s.is_mvp,
                            minutes_played: s.minutes_played || 0,

                            // Tarjetas
                            is_double_yellow: hasDoubleYellow,
                            // Si es doble amarilla, mostramos 2 amarillas en el contador
                            yellow_cards: hasDoubleYellow ? 2 : sanctions.filter(s => s.type === 'yellow').length,
                            // IMPORTANTE: La roja se marca si tiene ROJA DIRECTA o DOBLE AMARILLA
                            red_cards: (hasDirectRed || hasDoubleYellow) ? 1 : 0,
                        };
                    });
                };

                playersh.value = mapPlayers(partido.equipolocal.players);
                playersa.value = mapPlayers(partido.equipovisitante.players);
                displayModalScore.value = true;
            }else{
                Swal2.fire({
                    title: 'No puedes continuar',
                    text: 'Todavía no has agregado los equipos para este encuentro. Completa esa información y luego podrás continuar.',
                    icon: 'info',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
            }
        } else {
            showAlertStatus(partido.status)
        }

    }

    const showAlertStatus = (status) => {
        const statusMessages = {
            'finished': {
                title: 'Partido Finalizado',
                text: 'Este partido fue terminado. generar acta del partido para finalizar correctamente.',
                icon: 'success'
            },
            'suspended': {
                title: 'Encuentro en Revisión',
                text: 'El partido está suspendido. Debes habilitarlo en el panel administrativo para poder generar el acta final.',
                icon: 'warning'
            },
            'cancelled': {
                title: 'Partido Anulado',
                text: 'Este partido fue cancelado. No se permiten registros de goles ni actas oficiales.',
                icon: 'error'
            },
            'closed': {
                title: 'Acta Oficial Cerrada',
                text: 'Este encuentro ya fue finalizado y el acta ha sido generada. Los puntos ya están en la tabla de posiciones',
                icon: 'error'
            }
        };

        const msg = statusMessages[status];

        if (msg) {
            Swal2.fire({
                title: msg.title,
                text: msg.text,
                icon: msg.icon,
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#4f46e5', // Un color índigo/azul profesional
            });
        }
    };

    const closeModalScore = () => {
        displayModalScore.value = false;
    }

    // Función para el equipo Local
    const toggleYellowH = (player) => {
        // Ciclo: 0 -> 1 -> 2 -> 0
        player.yellow_cards = (player.yellow_cards + 1) % 3;

        // Si tiene 2 amarillas, activar roja (doble amarilla)
        if (player.yellow_cards === 2) {
            player.red_cards = 1;
            player.is_double_yellow = true;
        } else {
            player.red_cards = 0;
            player.is_double_yellow = false;
        }
    };

    // Función para Roja Directa Local
    const toggleRedH = (player) => {
        player.red_cards = player.red_cards === 0 ? 1 : 0;
        // Si es roja directa, reseteamos amarillas para no confundir
        if (player.red_cards === 1) {
            player.yellow_cards = 0;
            player.is_double_yellow = false;
        }
    };

    const toggleYellowA = (player) => {
        // Ciclo: 0 -> 1 -> 2 -> 0
        player.yellow_cards = (player.yellow_cards + 1) % 3;

        // Si tiene 2 amarillas, activar roja (doble amarilla)
        if (player.yellow_cards === 2) {
            player.red_cards = 1;
            player.is_double_yellow = true;
        } else {
            player.red_cards = 0;
            player.is_double_yellow = false;
        }
    };

    // Función para Roja Directa Local
    const toggleRedA = (player) => {
        player.red_cards = player.red_cards === 0 ? 1 : 0;
        // Si es roja directa, reseteamos amarillas para no confundir
        if (player.red_cards === 1) {
            player.yellow_cards = 0;
            player.is_double_yellow = false;
        }
    };

    // Al enviar el formulario, cargamos los datos de los refs al form de Inertia
    const updateMatchScore = () => {
        formScore.players_h = playersh.value;
        formScore.players_a = playersa.value;
        formScore.post(route('even_edition_match_score_update'), {
            preserveScroll: true,
            onSuccess: () => {
                Swal2.fire({
                    title: 'Enhorabuena',
                    text: 'Se actualizo correctamente',
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                displayModalScore.value = false;
                formScore.reset();
            }
        });
    };

    const deleteMatch = (match) => {
        if(match.status == 'pending'){
            Swal2.fire({
                title: '¿Estas seguro?',
                text: "¡No podrás revertir esto!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: '¡Sí, Eliminar!',
                cancelButtonText: 'Cancelar',
                showLoaderOnConfirm: true,
                padding: '2em',
                customClass: 'sweet-alerts',
                preConfirm: () => {
                    return axios.delete(route('even_edition_match_score_destroy', match.id)).then((res) => {
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
                    router.visit(route('even_ediciones_fixtures', props.edition.id), {
                        replace: true,
                        method: 'get',
                        only: ['fixture'],
                    });
                }
            });
        } else {
            showAlertStatus(match.status)
        }
    }

    const formatPhaseName = (phase) => {
        let translations = {
            'league': 'Fase de Grupos / Liga',
            'quarterfinals': 'Cuartos de Final',
            'semifinals': 'Semifinales',
            'third_place': 'Tercer Puesto',
            'final': 'Gran Final'
        };

        // Si la fase existe en el diccionario, la devuelve.
        // Si no, limpia el texto (quita guiones bajos) por si acaso.
        return translations[phase] || phase.replace('_', ' ');
    };

    const isPhaseCompleted = (phase) => {
        if (!props.fixture[phase]) return false;
        for (const roundNumber in props.fixture[phase]) {
            for (const match of props.fixture[phase][roundNumber]) {
                if (match.status !== 'finished' && match.status !== 'closed') {
                    return false;
                }
            }
        }
        return true;
    };

    const loaders = reactive({});
    const captureRefs = reactive({});

    const exportImage = async (phase, roundNumber) => {
        const key = phase + '-' + roundNumber;
        loaders[key] = true;
        // 1. Esperamos a que Vue termine de renderizar cualquier cambio
        await nextTick()

        if (!captureRefs[key]) return

        try {
            // 2. Esperamos a que las fuentes del navegador estén cargadas
            await document.fonts.ready

            // 3. Generamos la imagen
            const dataUrl = await domToPng(captureRefs[key], {
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
            link.download = `fixture-${phase}-fecha-${roundNumber}-${new Date().getTime()}.png`
            link.href = dataUrl
            link.click()
            loaders[key] = false;
        } catch (error) {
            Swal2.fire({
                title: 'Error al exportar',
                text: 'Descripción: '+ error,
                icon: 'error',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
            loaders[key] = false;
        }
    }

    const displayModalMatchReport = ref(false);
    const formMatchReport = useForm({
        match_id: null,
        equipo_h_name: null,
        equipo_a_name: null,
        equipo_h_id: null,
        equipo_a_id: null,
        score_h: null,
        score_a: null,
        status: 'closed', // finalized, suspended, walk_over
        referees: [],
        observations: '',
        has_protest: false,
        protest_details: {
            team_h: null,
            team_a: null
        }
    });

    const openModalMatchReport = (match) => {

        if(match.status == 'finished'){
            if(match.equipolocal && match.equipovisitante){
                formMatchReport.equipo_h_name = match.equipolocal.name;
                formMatchReport.equipo_a_name = match.equipovisitante.name;
                formMatchReport.equipo_h_id = match.equipolocal.id;
                formMatchReport.equipo_a_id = match.equipovisitante.id;
                formMatchReport.score_h = match.score_h ?? 0;
                formMatchReport.score_a = match.score_a ?? 0;
                formMatchReport.match_id = match.id;

                const mapPlayers = (players) => {
                    if (!players) return [];

                    return players.map(p => {
                        // En el back pusimos 'stats' como array
                        const s = (p.stats && p.stats.length > 0) ? p.stats[0] : {};

                        // Sanciones
                        const sanctions = p.sanctions || [];

                        // 1. Detectamos si tiene Doble Amarilla (ya sea por el booleano o buscando en el array)
                        const hasDoubleYellow = !!p.is_double_yellow || sanctions.some(s => s.type === 'double_yellow');

                        // 2. Detectamos si tiene Roja Directa
                        const hasDirectRed = sanctions.some(s => s.type === 'red');

                        return {
                            ...p,
                            // Usamos match_role que inyectamos directamente en el back
                            match_role: p.match_role || null,

                            // Estadísticas
                            goals: s.goals || 0,
                            assists: s.assists || 0,
                            saves: s.saves || 0,
                            is_mvp: !!s.is_mvp,
                            minutes_played: s.minutes_played || 0,

                            // Tarjetas
                            is_double_yellow: hasDoubleYellow,
                            // Si es doble amarilla, mostramos 2 amarillas en el contador
                            yellow_cards: hasDoubleYellow ? 2 : sanctions.filter(s => s.type === 'yellow').length,
                            // IMPORTANTE: La roja se marca si tiene ROJA DIRECTA o DOBLE AMARILLA
                            red_cards: (hasDirectRed || hasDoubleYellow) ? 1 : 0,
                        };
                    });
                };

                playersh.value = mapPlayers(match.equipolocal.players);
                playersa.value = mapPlayers(match.equipovisitante.players);

                displayModalMatchReport.value = true;
            }
        } else {
            showAlertStatus(match.status)
        }
    }
    const closeModalMatchReport = () => {
        displayModalMatchReport.value = false;
    }

    const submitReport = () => {
        formMatchReport.post(route('even_ediciones_partido_acta_store'), {
            onSuccess: () => {
                Swal2.fire({
                    title: 'Enhorabuena',
                    text: 'Se actualizo correctamente',
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                displayModalMatchReport.value = false;
                formMatchReport.reset();
            }
        });
    };

    const displayModalClientSearch = ref(false);

    const openModalClientSearch = () => {
        displayModalClientSearch.value = true;
    }
    const closeModalClientSearch = () => {
        displayModalClientSearch.value = false;
    }
    const getDataClient = async (data) => {
        formMatchReport.referees.push({
            person_id: data.id,
            full_name: data.full_name
        });
        displayModalClientSearch.value = false;

    }
    const removeReferee = (index) => {
        formMatchReport.referees.splice(index, 1);
    }
</script>
<template>

    <div v-if="Object.keys(fixture).length > 0" >
        <details v-for="(rounds, phase) in fixture" :key="phase" :open="!isPhaseCompleted(phase)" class="mb-12">
            <summary class="cursor-pointer text-2xl font-black uppercase mb-6 text-gray-800 border-l-4 border-red-600 pl-4 dark:text-white">
                {{ formatPhaseName(phase) }}
            </summary>

                <div v-for="(matches, roundNumber) in rounds" :key="roundNumber" class="mb-8">

                    <div class="flex items-center gap-4 mb-4">
                        <div class="flex items-center justify-between mb-6 flex-wrap gap-4">
                            <span class="bg-gradient-to-r from-blue-500 to-purple-600 text-white px-4 py-2 rounded-full text-sm font-bold shadow-lg">
                                FECHA {{ roundNumber }}
                            </span>
                             <button @click="exportImage(phase, roundNumber)" :class="{ 'opacity-25': loaders[phase + '-' + roundNumber] }" :disabled="loaders[phase + '-' + roundNumber]" class="bg-gradient-to-r from-green-500 to-teal-600 hover:from-green-600 hover:to-teal-700 text-white w-10 h-10 flex items-center justify-center rounded-full shadow-lg transition-all duration-200 transform hover:scale-105">
                                 <IconBallSoccer v-if="loaders[phase + '-' + roundNumber]" class="w-5 h-5 animate-bounce-ball" />
                                 <IconImages v-else class="w-5 h-5" />
                             </button>
                        </div>
                        <div class="h-0.5 bg-gradient-to-r from-gray-300 to-gray-400 flex-grow dark:from-gray-600 dark:to-gray-700 rounded-full"></div>
                    </div>

                     <div :ref="(el) => captureRefs[phase + '-' + roundNumber] = el" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        <div
                            v-for="match in matches"
                            :key="match.id"
                            class="bg-white border rounded-xl shadow-sm hover:shadow-md transition-shadow overflow-hidden dark:bg-zinc-800 dark:border-zinc-800"
                        >
                            <div class="bg-gray-50 px-4 py-2 text-xs font-medium text-gray-500 flex justify-between dark:bg-zinc-900 dark:text-gray-300">
                                <span>{{ match.match_date ? formatDate(match.match_date) : 'Horario por definir' }}</span>
                                <span :class="statusBadge(match.status)" class="uppercase font-bold">{{ match.status }}</span>
                            </div>

                            <div class="p-4 flex flex-col gap-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center overflow-hidden border dark:bg-zinc-800 dark:border-zinc-700">
                                            <img v-if="match.equipolocal?.logo_path" :src="xhttp + 'storage/' + match.equipolocal.logo_path" class="w-full h-full object-contain p-1" />
                                            <img v-else src="/img/escudo-deportivo.png" class="w-full h-full object-contain p-1 opacity-50" />
                                        </div>
                                        <span class="font-bold text-gray-700 dark:text-white truncate max-w-[120px]">
                                            {{ match.equipolocal?.name || match.placeholder_h || 'TBD' }}
                                        </span>
                                    </div>
                                    <span class="text-xl font-black text-red-600">{{ match.score_h ?? '-' }}</span>
                                </div>

                                <div class="flex justify-center items-center gap-2">
                                    <span class="text-[10px] font-black text-gray-400 tracking-widest">VS</span>
                                </div>

                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center overflow-hidden border dark:bg-zinc-800 dark:border-zinc-700">
                                            <img v-if="match.equipovisitante?.logo_path" :src="xhttp + 'storage/' + match.equipovisitante.logo_path" class="w-full h-full object-contain p-1" />
                                            <img v-else src="/img/escudo-deportivo.png" class="w-full h-full object-contain p-1 opacity-50" />
                                        </div>
                                        <span class="font-bold text-gray-700 dark:text-white truncate max-w-[120px]">
                                            {{ match.equipovisitante?.name || match.placeholder_a || 'TBD' }}
                                        </span>
                                    </div>
                                    <span class="text-xl font-black text-red-600">{{ match.score_a ?? '-' }}</span>
                                </div>
                            </div>

                            <div class="border-t px-4 py-3 bg-gray-50 flex justify-center gap-4 dark:bg-zinc-800/50 dark:border-zinc-800 no-export">
                                <button
                                    @click="openModalEdit(match)"
                                    v-tippy="{content: 'Editar', placement: 'bottom'}"
                                    class="p-1 hover:bg-gray-200 rounded-lg transition dark:hover:bg-zinc-700 no-export"
                                >
                                    <IconPencilPaper class="w-7 h-7 text-blue-600" />
                                </button>
                                <button
                                    v-can="'even_ediciones_partido_eliminar'"
                                    @click="deleteMatch(match)"
                                    v-tippy="{content: 'Eliminar', placement: 'bottom'}"
                                    class="p-1 hover:bg-red-100 rounded-lg transition dark:hover:bg-red-900/30 no-export"
                                >
                                    <IconTrashLines class="w-7 h-7 text-red-500" />
                                </button>
                                <button
                                    v-can="'even_ediciones_partido_resultado'"
                                    v-tippy="{content: 'Resultados de encuentro', placement: 'bottom'}"
                                    @click="openModalScore(match)"
                                    class="p-1 hover:bg-green-100 rounded-lg transition dark:hover:bg-green-900/30 no-export"
                                >
                                    <svg class="w-7 h-7 text-green-600" fill="currentColor" viewBox="0 0 640 640">
                                        <path d="M192 64C156.7 64 128 92.7 128 128L128 512C128 547.3 156.7 576 192 576L448 576C483.3 576 512 547.3 512 512L512 128C512 92.7 483.3 64 448 64L192 64zM224 128L416 128C433.7 128 448 142.3 448 160L448 192C448 209.7 433.7 224 416 224L224 224C206.3 224 192 209.7 192 192L192 160C192 142.3 206.3 128 224 128z"/>
                                    </svg>
                                </button>
                                <button
                                    v-can="'even_ediciones_partido_acta'"
                                    v-tippy="{content: 'Generar Acta y Finalizar Partido', placement: 'bottom'}"
                                    @click="openModalMatchReport(match)"
                                    class="p-1 hover:bg-green-100 rounded-lg transition dark:hover:bg-[#011403]/30 no-export"
                                >
                                    <svg class="w-7 h-7 text-[#067A21]" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                                        <path d="M64.1 128C64.1 92.7 92.8 64 128.1 64L277.6 64C294.6 64 310.9 70.7 322.9 82.7L429.3 189.3C441.3 201.3 448 217.6 448 234.6L448 332.1L316 464.1L273.9 464.1L257.8 410.5C253.1 394.8 238.7 384.1 222.3 384.1C211 384.1 200.4 389.2 193.4 398L133.3 473C125 483.3 126.7 498.5 137 506.7C147.3 514.9 162.5 513.3 170.7 502.9L217.8 444.1L233 494.8C236 505 245.4 511.9 256 511.9L287.5 511.9C286.6 515 285.8 518.2 285.2 521.4L274.3 575.9L128.1 575.9C92.8 575.9 64.1 547.2 64.1 511.9L64.1 127.9zM272.1 122.5L272.1 216C272.1 229.3 282.8 240 296.1 240L389.6 240L272.1 122.5zM332.3 530.9C334.8 518.5 340.9 507.1 349.8 498.2L468.7 379.3L548.7 459.3L429.8 578.2C420.9 587.1 409.5 593.2 397.1 595.7L337.5 607.6C336.6 607.8 335.6 607.9 334.6 607.9C326.6 607.9 320 601.4 320 593.3C320 592.3 320.1 591.4 320.3 590.4L332.2 530.8zM600.1 407.9L571.3 436.7L491.3 356.7L520.1 327.9C542.2 305.8 578 305.8 600.1 327.9C622.2 350 622.2 385.8 600.1 407.9z"/>
                                    </svg>
                                </button>

                            </div>
                        </div>
                    </div>
                </div>
        </details>
    </div>

    <div v-else class="text-center py-20 text-gray-500">
        <span class="text-lg font-bold">No hay partidos generados para esta edición.</span>
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
                    <IconBallSoccer v-if="formTeams.processing" class="w-4 h-4 animate-bounce-ball mr-1" />
                    Crear
                </SuccessButton>
        </template>
    </ModalLarge>
    <ModalLarge :show="displayModalEdit" :onClose="closeModalEdit" :icon="'/img/juego.png'">
        <template #title>{{ formMatch.equipo_h_name || 'TBD' }} VS {{ formMatch.equipo_a_name || 'TBD' }}</template>
        <template #message>Modificar información</template>
        <template #content>
            <div class="grid grid-cols-6 gap-4">
                <div class="col-span-6 md:col-span-2">
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
                <div class="col-span-6 md:col-span-2">
                    <InputLabel for="group_name" value="Grupo (Opcional)" />
                    <TextInput
                        id="group_name"
                        v-model="formMatch.group_name"
                        maxlength="255"
                        type="text"
                    />
                    <InputError :message="formMatch.errors.round_number" />
                </div>
                <div class="col-span-6 md:col-span-2">
                    <InputLabel for="match_date" value="Fecha y hora" />
                    <flat-pickr
                        v-model="formMatch.match_date"
                        :config="configFlatPickr"
                        class="form-input"
                        placeholder="Selecciona fecha"
                    />
                    <InputError :message="formMatch.errors.match_date" />
                </div>
                <div class="col-span-6 md:col-span-4">
                    <div >
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

                    <div>
                        <InputLabel for="equipo_h_id" value="Equipo Local" />
                        <select
                            id="equipo_h_id"
                            v-model="formMatch.equipo_h_id"
                            class="form-select"
                        >
                            <option value="">Selecciona equipo local</option>
                            <option v-for="team in teamsSortedDesc" :value="team.equipo.id">
                                {{ team.equipo.name }} ({{ team.points || 0 }} pts)
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
                                {{ team.equipo.name }} ({{ team.points || 0 }} pts)
                            </option>
                        </select>
                        <InputError :message="formMatch.errors.equipo_a_id" />
                    </div>
                </div>
                <div class="col-span-6 md:col-span-2">
                    <InputLabel value="Estado" />
                    <div class="flex flex-wrap justify-start gap-1.5 sm:gap-2">
                        <button @click="formMatch.status = 'pending'"
                            type="button"
                            :class="[
                                'py-1.5 px-2.5 inline-flex items-center gap-x-1.5 text-sm rounded-lg transition-colors border',
                                formMatch.status === 'pending'
                                    ? 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800'
                                    : 'bg-gray-100 text-gray-800 border-transparent hover:text-cyan-700 dark:bg-neutral-700 dark:text-neutral-200'
                            ]">
                            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path fill="currentColor" d="M160 64C142.3 64 128 78.3 128 96C128 113.7 142.3 128 160 128L160 139C160 181.4 176.9 222.1 206.9 252.1L274.8 320L206.9 387.9C176.9 417.9 160 458.6 160 501L160 512C142.3 512 128 526.3 128 544C128 561.7 142.3 576 160 576L480 576C497.7 576 512 561.7 512 544C512 526.3 497.7 512 480 512L480 501C480 458.6 463.1 417.9 433.1 387.9L365.2 320L433.1 252.1C463.1 222.1 480 181.4 480 139L480 128C497.7 128 512 113.7 512 96C512 78.3 497.7 64 480 64L160 64zM224 139L224 128L416 128L416 139C416 164.5 405.9 188.9 387.9 206.9L320 274.8L252.1 206.9C234.1 188.9 224 164.4 224 139z"/></svg>
                            Pendiente
                        </button>

                        <button @click="formMatch.status = 'live'"
                            type="button"
                            :class="[
                                'py-1.5 px-2.5 inline-flex items-center gap-x-1.5 text-sm rounded-lg transition-colors border',
                                formMatch.status === 'live'
                                    ? 'bg-green-100 text-green-700 border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800'
                                    : 'bg-gray-100 text-gray-800 border-transparent hover:text-cyan-700 dark:bg-neutral-700 dark:text-neutral-200'
                            ]">
                            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path fill="currentColor" d="M352.5 32C383.4 32 408.5 57.1 408.5 88C408.5 118.9 383.4 144 352.5 144C321.6 144 296.5 118.9 296.5 88C296.5 57.1 321.6 32 352.5 32zM219.6 240C216.3 240 213.4 242 212.2 245L190.2 299.9C183.6 316.3 165 324.3 148.6 317.7C132.2 311.1 124.2 292.5 130.8 276.1L152.7 221.2C163.7 193.9 190.1 176 219.6 176L316.9 176C345.4 176 371.7 191.1 386 215.7L418.8 272L480.4 272C498.1 272 512.4 286.3 512.4 304C512.4 321.7 498.1 336 480.4 336L418.8 336C396 336 375 323.9 363.5 304.2L353.5 287.1L332.8 357.5L408.2 380.1C435.9 388.4 450 419.1 438.3 445.6L381.7 573C374.5 589.2 355.6 596.4 339.5 589.2C323.4 582 316.1 563.1 323.3 547L372.5 436.2L276.6 407.4C243.9 397.6 224.6 363.7 232.9 330.6L255.6 240L219.7 240zM211.6 421C224.9 435.9 242.3 447.3 262.8 453.4L267.5 454.8L260.6 474.1C254.8 490.4 244.6 504.9 231.3 515.9L148.9 583.8C135.3 595 115.1 593.1 103.9 579.5C92.7 565.9 94.6 545.7 108.2 534.5L190.6 466.6C195.1 462.9 198.4 458.1 200.4 452.7L211.6 421z"/></svg>
                            En juego
                        </button>

                        <button @click="formMatch.status = 'finished'"
                            type="button"
                            :class="[
                                'py-1.5 px-2.5 inline-flex items-center gap-x-1.5 text-sm rounded-lg transition-colors border',
                                formMatch.status === 'finished'
                                    ? 'bg-purple-100 text-purple-700 border-purple-200 dark:bg-purple-900/30 dark:text-purple-400 dark:border-purple-800'
                                    : 'bg-gray-100 text-gray-800 border-transparent hover:text-cyan-700 dark:bg-neutral-700 dark:text-neutral-200'
                            ]">
                            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path fill="currentColor" d="M128 64C145.7 64 160 78.3 160 96L160 112L229 94.8C267.1 85.3 307.3 89.7 342.5 107.3C388.8 130.5 443.3 130.5 489.6 107.3L499.2 102.5C519.8 92.1 544 107.1 544 130.1L544 409.8C544 423.1 535.7 435.1 523.2 439.8L488.5 452.8C442.3 470.1 390.9 467.4 346.8 445.4C308.9 426.4 265.4 421.7 224.3 432L160 448L160 544C160 561.7 145.7 576 128 576C110.3 576 96 561.7 96 544L96 96C96 78.3 110.3 64 128 64zM160 251.1L224 237.2L224 302.7L160 316.6L160 382.1L208.8 369.9C213.9 368.6 218.9 367.5 224 366.6L224 302.7L262.9 294.3C271.2 292.5 279.6 291.8 288 292.2L288 228.2C301.6 228.6 315.2 230.8 328.4 234.6L352 241.5L352 308.2L310.3 295.9C303 293.8 295.5 292.5 288 292.1L288 363.5C309.8 365.4 331.3 370.2 352 377.9L352 308.1L374.7 314.8C388.2 318.8 402 321.2 416 322.2L416 258C408.2 257.2 400.4 255.7 392.8 253.5L352 241.5L352 179.5C339 175.7 326.2 170.7 313.8 164.5C305.6 160.4 296.9 157.5 288 155.7L288 228.1C275 227.7 262 228.9 249.3 231.7L224 237.2L224 162L160 178L160 251.1zM416 399.7C432.8 401.2 449.9 399 466 392.9L480 387.7L480 316L472.1 317.8C453.7 322.1 434.8 323.5 416 322.3L416 399.7zM480 250.3L480 179.5C459.1 185.6 437.6 188.6 416 188.6L416 258C429.9 259.4 444 258.5 457.7 255.4L480 250.2z"/></svg>
                            Finalizado
                        </button>

                        <button @click="formMatch.status = 'cancelled'"
                            type="button"
                            :class="[
                                'py-1.5 px-2.5 inline-flex items-center gap-x-1.5 text-sm rounded-lg transition-colors border',
                                formMatch.status === 'cancelled'
                                    ? 'bg-red-100 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800'
                                    : 'bg-gray-100 text-gray-800 border-transparent hover:text-cyan-700 dark:bg-neutral-700 dark:text-neutral-200'
                            ]">
                            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path fill="currentColor" d="M431.2 476.5L163.5 208.8C141.1 240.2 128 278.6 128 320C128 426 214 512 320 512C361.5 512 399.9 498.9 431.2 476.5zM476.5 431.2C498.9 399.8 512 361.4 512 320C512 214 426 128 320 128C278.5 128 240.1 141.1 208.8 163.5L476.5 431.2zM64 320C64 178.6 178.6 64 320 64C461.4 64 576 178.6 576 320C576 461.4 461.4 576 320 576C178.6 576 64 461.4 64 320z"/></svg>
                            Cancelado
                        </button>
                    </div>
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
    <ModalLargeXX :show="displayModalScore" :onClose="closeModalScore" :icon="'/img/pelota-de-futbol.png'">
        <template #title>{{ formScore.equipo_h_name  }} VS {{ formScore.equipo_a_name  }}</template>
        <template #message>Resultados</template>
        <template #content>
            <div class="grid sm:grid-cols-5 gap-2">
                <div class="sm:col-span-2 flex items-center justify-between">
                    <InputLabel :value="formScore.equipo_h_name" />
                    <TextInput v-model="formScore.score_h" class="w-16 border-4" />
                </div>
                <div class="sm:col-span-1 flex items-center justify-center">
                    <h3 class="text-lg font-bold">VS</h3>
                </div>
                <div class="sm:col-span-2 flex items-center justify-between">
                    <InputLabel :value="formScore.equipo_a_name" />
                    <TextInput v-model="formScore.score_a" class="w-16 border-4" />
                </div>
            </div>
            <div class="grid sm:grid-cols-4 gap-2 mt-4">
                <div class="sm:col-span-2">
                    <div class="w-full p-0 border border-default rounded-base shadow-xs dark:border-blue-900">
                        <div class="table-responsive">
                            <table class="w-full text-sm text-left rtl:text-right text-body">
                                <thead class="text-sm text-body border-b border-default dark:border-blue-900">
                                    <th class="px-6 py-2 bg-gray-50 font-blod text-sm dark:bg-blue-900 dark:text-white"></th>
                                    <th class="px-6 py-2 bg-gray-50 font-blod text-sm dark:bg-blue-900 dark:text-white">Nombre</th>
                                    <th class="px-6 py-2 bg-gray-50 font-blod text-sm dark:bg-blue-900 dark:text-white">Goles</th>
                                    <th class="px-6 py-2 bg-gray-50 font-blod text-sm dark:bg-blue-900 dark:text-white">Asistencias</th>
                                    <th class="px-6 py-2 bg-gray-50 font-blod text-sm dark:bg-blue-900 dark:text-white">Atajadas directas</th>
                                    <th class="px-6 py-2 bg-gray-50 font-blod text-sm dark:bg-blue-900 dark:text-white">Mejor jugador</th>
                                    <th class="px-6 py-2 bg-gray-50 font-blod text-sm dark:bg-blue-900 dark:text-white">Sanciones</th>
                                </thead>
                                <tbody>
                                    <tr v-for="(playerh, index) in playersh" :key="index" class="border-b border-default">
                                        <td class="px-2">
                                            <div class="flex-row items-center gap-2">
                                                <label class="inline-flex items-center cursor-pointer">
                                                    <input type="radio" :name="'role_' + index + playerh.team_id + formScore.id" value="starter"
                                                        v-model="playerh.match_role"
                                                        class="text-blue-600 focus:ring-blue-500">
                                                    <span class="ml-1 text-xs">Titular</span>
                                                </label>
                                                <label class="inline-flex items-center cursor-pointer">
                                                    <input type="radio" :name="'role_' + index + playerh.team_id + formScore.id" value="substitute"
                                                        v-model="playerh.match_role"
                                                        class="text-green-600 focus:ring-green-500">
                                                    <span class="ml-1 text-xs">Suplente</span>
                                                </label>
                                                <button @click="playerh.match_role = null" class="text-[11px] text-red-400 underline">
                                                    No participo
                                                </button>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 font-medium text-heading whitespace-nowrap bg-neutral-secondary-soft">
                                            <p class="font-medium text-heading truncate">{{ playerh.person.full_name }}</p>
                                            <p class="text-sm text-body truncate">
                                                Camiseta: {{ playerh.jersey_number }}
                                                Posición: {{ playerh.position }}
                                                <span v-if="playerh.role_in_team == 'Capitán' || playerh.role_in_team == 'Sub-Capitán'" class="text-sm font-bold text-red-900">
                                                    {{ ' / '+playerh.role_in_team }}
                                                </span>
                                            </p>
                                        </td>
                                        <td class="text-center">
                                            <div class="flex items-center bg-gray-100 rounded py-2 dark:bg-gray-600 dark:text-white">
                                                <button @click="playerh.goals > 0 ? playerh.goals-- : null" class="px-2">
                                                    <IconMinus class="w-5 h-5 " />
                                                </button>
                                                <span class="px-2 font-bold">{{ playerh.goals }}</span>
                                                <button @click="playerh.goals++" class="px-2">
                                                    <IconPlus class="w-5 h-5 " />
                                                </button>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="flex items-center bg-gray-100 rounded py-2 dark:bg-gray-600 dark:text-white">
                                                <button @click="playerh.assists > 0 ? playerh.assists-- : null" class="px-2">
                                                    <IconMinus class="w-5 h-5 " />
                                                </button>
                                                <span class="px-2 font-bold">{{ playerh.assists }}</span>
                                                <button @click="playerh.assists++" class="px-2">
                                                    <IconPlus class="w-5 h-5 " />
                                                </button>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="flex items-center bg-gray-100 rounded py-2 dark:bg-gray-600 dark:text-white">
                                                <button @click="playerh.saves > 0 ? playerh.saves-- : null" class="px-2">
                                                    <IconMinus class="w-5 h-5 " />
                                                </button>
                                                <span class="px-2 font-bold">{{ playerh.saves }}</span>
                                                <button @click="playerh.saves++" class="px-2">
                                                    <IconPlus class="w-5 h-5 " />
                                                </button>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <button
                                                @click="playerh.is_mvp = !playerh.is_mvp"
                                                :class="playerh.is_mvp ? 'text-yellow-500' : 'text-gray-300'"
                                                class="text-xl"
                                                title="Jugador del Partido"
                                            >
                                                <IconStarFiller v-if="playerh.is_mvp" class="w-5 h-5" />
                                                <IconStar v-else class="w-5 h-5" />
                                            </button>
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-2">
                                                <div class="flex items-center">
                                                    <button type="button"
                                                        @click="toggleYellowH(playerh)"
                                                        :class="[
                                                            playerh.yellow_cards > 0 ? 'bg-yellow-50 border-yellow-400' : 'bg-transparent border-gray-200',
                                                            'relative flex items-center justify-center border rounded-lg h-10 w-10 transition-all'
                                                        ]">
                                                        <img :src="'/img/tarjeta-amarilla.png'" class="w-6" :class="playerh.yellow_cards === 0 ? 'opacity-60' : ''" />
                                                        <span v-if="playerh.yellow_cards > 0"
                                                            class="absolute -right-2 -top-2 bg-yellow-500 text-white text-[10px] font-bold px-1.5 rounded-full shadow-sm">
                                                            {{ playerh.yellow_cards }}
                                                        </span>
                                                    </button>
                                                </div>

                                                <button type="button"
                                                    @click="toggleRedH(playerh)"
                                                    :class="[
                                                        playerh.red_cards > 0 ? 'bg-red-100 border-red-500 ring-2 ring-red-200' : 'bg-transparent border-gray-200',
                                                        'flex items-center justify-center border rounded-lg h-10 w-10 transition-all'
                                                    ]">
                                                    <img :src="'/img/tarjeta-roja.png'" class="w-6" :class="playerh.red_cards === 0 ? 'opacity-60' : ''" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="sm:col-span-2">
                    <div class="w-full p-0 border border-default rounded-base shadow-xs dark:border-blue-900">
                        <div class="table-responsive">
                            <table class="w-full text-sm text-left rtl:text-right text-body">
                                <thead class="text-sm text-body border-b border-default dark:border-blue-900">
                                    <th class="px-6 py-2 bg-gray-50 font-blod text-sm dark:bg-blue-900 dark:text-white"></th>
                                    <th class="px-6 py-2 bg-gray-50 font-blod text-sm dark:bg-blue-900 dark:text-white">Nombre</th>
                                    <th class="px-6 py-2 bg-gray-50 font-blod text-sm dark:bg-blue-900 dark:text-white">Goles</th>
                                    <th class="px-6 py-2 bg-gray-50 font-blod text-sm dark:bg-blue-900 dark:text-white">Asistencias</th>
                                    <th class="px-6 py-2 bg-gray-50 font-blod text-sm dark:bg-blue-900 dark:text-white">Atajadas directas</th>
                                    <th class="px-6 py-2 bg-gray-50 font-blod text-sm dark:bg-blue-900 dark:text-white">Mejor jugador</th>
                                    <th class="px-6 py-2 bg-gray-50 font-blod text-sm dark:bg-blue-900 dark:text-white">Sanciones</th>
                                </thead>
                                <tbody>
                                    <tr v-for="(playera, index) in playersa" :key="index" class="border-b border-default">
                                        <td class="px-2">
                                            <div class="flex-row items-center gap-2">
                                                <label class="inline-flex items-center cursor-pointer">
                                                    <input type="radio" :name="'role_' + index + playera.team_id + formScore.id" value="starter"
                                                        v-model="playera.match_role"
                                                        class="text-blue-600 focus:ring-blue-500">
                                                    <span class="ml-1 text-xs">Titular</span>
                                                </label>
                                                <label class="inline-flex items-center cursor-pointer">
                                                    <input type="radio" :name="'role_' + index + playera.team_id + formScore.id" value="substitute"
                                                        v-model="playera.match_role"
                                                        class="text-green-600 focus:ring-green-500">
                                                    <span class="ml-1 text-xs">Suplente</span>
                                                </label>
                                                <button @click="playera.match_role = null" class="text-[10px] text-red-400 underline">
                                                    No participo
                                                </button>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 font-medium text-heading whitespace-nowrap bg-neutral-secondary-soft">
                                            <p class="font-medium text-heading truncate">{{ playera.person.full_name }}</p>
                                            <p class="text-sm text-body truncate">
                                                Camiseta: {{ playera.jersey_number }}
                                                Posición: {{ playera.position }}
                                                <span v-if="playera.role_in_team == 'Capitán' || playera.role_in_team == 'Sub-Capitán'" class="text-sm font-bold text-red-900">
                                                    {{ ' / '+playera.role_in_team }}
                                                </span>
                                            </p>
                                        </td>
                                        <td class="text-center">
                                            <div class="flex items-center bg-gray-100 rounded py-2 dark:bg-gray-600 dark:text-white">
                                                <button @click="playera.goals > 0 ? playera.goals-- : null" class="px-2">
                                                    <IconMinus class="w-5 h-5 " />
                                                </button>
                                                <span class="px-2 font-bold">{{ playera.goals }}</span>
                                                <button @click="playera.goals++" class="px-2">
                                                    <IconPlus class="w-5 h-5 " />
                                                </button>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="flex items-center bg-gray-100 rounded py-2 dark:bg-gray-600 dark:text-white">
                                                <button @click="playera.assists > 0 ? playera.assists-- : null" class="px-2">
                                                    <IconMinus class="w-5 h-5 " />
                                                </button>
                                                <span class="px-2 font-bold">{{ playera.assists }}</span>
                                                <button @click="playera.assists++" class="px-2">
                                                    <IconPlus class="w-5 h-5 " />
                                                </button>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="flex items-center bg-gray-100 rounded py-2 dark:bg-gray-600 dark:text-white">
                                                <button @click="playera.saves > 0 ? playera.saves-- : null" class="px-2">
                                                    <IconMinus class="w-5 h-5 " />
                                                </button>
                                                <span class="px-2 font-bold">{{ playera.saves }}</span>
                                                <button @click="playera.saves++" class="px-2">
                                                    <IconPlus class="w-5 h-5 " />
                                                </button>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <button
                                                @click="playera.is_mvp = !playera.is_mvp"
                                                :class="playera.is_mvp ? 'text-yellow-500' : 'text-gray-300'"
                                                class="text-xl"
                                                title="Jugador del Partido"
                                            >
                                                <IconStarFiller v-if="playera.is_mvp" class="w-5 h-5" />
                                                <IconStar v-else class="w-5 h-5" />
                                            </button>
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-2">
                                                <div class="flex items-center">
                                                    <button type="button"
                                                        @click="toggleYellowA(playera)"
                                                        :class="[
                                                            playera.yellow_cards > 0 ? 'bg-yellow-50 border-yellow-400' : 'bg-transparent border-gray-200',
                                                            'relative flex items-center justify-center border rounded-lg h-10 w-10 transition-all'
                                                        ]">
                                                        <img :src="'/img/tarjeta-amarilla.png'" class="w-6" :class="playera.yellow_cards === 0 ? 'opacity-60' : ''" />
                                                        <span v-if="playera.yellow_cards > 0"
                                                            class="absolute -right-2 -top-2 bg-yellow-500 text-white text-[10px] font-bold px-1.5 rounded-full shadow-sm">
                                                            {{ playera.yellow_cards }}
                                                        </span>
                                                    </button>
                                                </div>

                                                <button type="button"
                                                    @click="toggleRedA(playera)"
                                                    :class="[
                                                        playera.red_cards > 0 ? 'bg-red-100 border-red-500 ring-2 ring-red-200' : 'bg-transparent border-gray-200',
                                                        'flex items-center justify-center border rounded-lg h-10 w-10 transition-all'
                                                    ]">
                                                    <img :src="'/img/tarjeta-roja.png'" class="w-6" :class="playera.red_cards === 0 ? 'opacity-60' : ''" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        <template #buttons>
            <PrimaryButton
                type="button"
                @click="updateMatchScore"
                :disabled="formScore.processing"
            >
                <div v-if="formScore.processing" class="mr-2">
                    <svg class="w-5 h-5 animate-bounce-ball" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                        <path fill="currentColor" d="M481.3 424.1L409.7 419.3C404.5 419 399.4 420.4 395.2 423.5C391 426.6 388 430.9 386.8 436L369.2 505.6C353.5 509.8 337 512 320 512C303 512 286.5 509.8 270.8 505.6L253.2 436C251.9 431 248.9 426.6 244.8 423.5C240.7 420.4 235.5 419 230.3 419.3L158.7 424.1C141.1 396.9 130.2 364.9 128.3 330.5L189 292.3C193.4 289.5 196.6 285.3 198.2 280.4C199.8 275.5 199.6 270.2 197.7 265.4L171 198.8C192 173.2 219.3 153 250.7 140.9L305.9 186.9C309.9 190.2 314.9 192 320 192C325.1 192 330.2 190.2 334.1 186.9L389.3 140.9C420.6 153 448 173.2 468.9 198.8L442.2 265.4C440.3 270.2 440.1 275.5 441.7 280.4C443.3 285.3 446.6 289.5 450.9 292.3L511.6 330.5C509.7 364.9 498.8 396.9 481.2 424.1zM320 576C461.4 576 576 461.4 576 320C576 178.6 461.4 64 320 64C178.6 64 64 178.6 64 320C64 461.4 178.6 576 320 576zM334.1 250.3C325.7 244.2 314.3 244.2 305.9 250.3L258 285C249.6 291.1 246.1 301.9 249.3 311.8L267.6 368.1C270.8 378 280 384.7 290.4 384.7L349.6 384.7C360 384.7 369.2 378 372.4 368.1L390.7 311.8C393.9 301.9 390.4 291.1 382 285L334.1 250.2z"/>
                    </svg>
                </div>

                <span>
                    {{ formScore.processing ? 'Procesando resultados...' : 'Guardar resultados' }}
                </span>
            </PrimaryButton>
        </template>
    </ModalLargeXX>
    <ModalLargeX :show="displayModalMatchReport" :onClose="closeModalMatchReport" :icon="'/img/cita.png'" >
        <template #title>{{ formMatchReport.equipo_h_name  }} VS {{ formMatchReport.equipo_a_name  }}</template>
        <template #message>Generar Acta y Finalizar Partido</template>
        <template #content>
            <div class="grid sm:grid-cols-5 gap-2">
                <div class="sm:col-span-2">
                    <div class="flex items-center justify-between mb-6">
                        <InputLabel :value="formMatchReport.equipo_h_name" />
                        <TextInput v-model="formMatchReport.score_h" class="w-16 bg-gray-50 border-4" disabled />
                    </div>
                    <div class="w-full p-0 border border-default rounded-base shadow-xs dark:border-blue-900">
                        <div class="table-responsive">
                            <table class="w-full text-sm text-left rtl:text-right text-body">
                                <thead class="text-sm text-body border-b border-default dark:border-blue-900">
                                    <th class="px-6 py-2 bg-gray-50 font-blod text-sm dark:bg-blue-900 dark:text-white">Jugadores</th>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="px-6 py-1.5 text-left bg-gray-50 font-bold">Titulares</td>
                                    </tr>
                                    <template v-for="(playerh, index) in playersh" >
                                        <tr v-if="playerh.match_role == 'starter'" :key="index" class="border-b border-default">
                                            <td class="px-6 py-4 font-medium text-heading whitespace-nowrap bg-neutral-secondary-soft">
                                                <p class="font-medium text-heading truncate">{{ playerh.person.full_name }}</p>
                                                <p class="text-sm text-body truncate">
                                                    Camiseta: {{ playerh.jersey_number }}
                                                    Posición: {{ playerh.position }}
                                                    <span v-if="playerh.role_in_team == 'Capitán' || playerh.role_in_team == 'Sub-Capitán'" class="text-sm font-bold text-red-900">
                                                        {{ ' / '+playerh.role_in_team }}
                                                    </span>
                                                </p>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr>
                                        <td class="px-6 py-1.5 text-left bg-gray-50 font-bold">Suplentes</td>
                                    </tr>
                                    <template v-for="(playerh, index) in playersh">
                                        <tr v-if="playerh.match_role == 'substitute'" :key="index">
                                            <td class="px-6 py-4 font-medium text-heading whitespace-nowrap bg-neutral-secondary-soft">
                                                <p class="font-medium text-heading truncate">{{ playerh.person.full_name }}</p>
                                                <p class="text-sm text-body truncate">
                                                    Camiseta: {{ playerh.jersey_number }}
                                                    Posición: {{ playerh.position }}
                                                    <span v-if="playerh.role_in_team == 'Capitán' || playerh.role_in_team == 'Sub-Capitán'" class="text-sm font-bold text-red-900">
                                                        {{ ' / '+playerh.role_in_team }}
                                                    </span>
                                                </p>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="sm:col-span-1 flex justify-center pt-2">
                    <h3 class="text-lg font-bold">VS</h3>
                </div>
                <div class="sm:col-span-2">
                    <div class="flex items-center justify-between mb-6">
                        <TextInput v-model="formMatchReport.score_a" class="w-16 bg-gray-50 border-4" disabled />
                        <InputLabel :value="formMatchReport.equipo_a_name" />
                    </div>
                    <div class="w-full p-0 border border-default rounded-base shadow-xs dark:border-blue-900">
                        <div class="table-responsive">
                            <table class="w-full text-sm text-left rtl:text-right text-body">
                                <thead class="text-sm text-body border-b border-default dark:border-blue-900">
                                    <th class="px-6 py-2 bg-gray-50 font-blod text-sm dark:bg-blue-900 dark:text-white">Jugador</th>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="px-6 py-1.5 text-left bg-gray-50 font-bold">Titulares</td>
                                    </tr>
                                    <template v-for="(playera, index) in playersa" >
                                        <tr v-if="playera.match_role == 'starter'" :key="index" class="border-b border-default">
                                            <td class="px-6 py-4 font-medium text-heading whitespace-nowrap bg-neutral-secondary-soft">
                                                <p class="font-medium text-heading truncate">{{ playera.person.full_name }}</p>
                                                <p class="text-sm text-body truncate">
                                                    Camiseta: {{ playera.jersey_number }}
                                                    Posición: {{ playera.position }}
                                                    <span v-if="playera.role_in_team == 'Capitán' || playera.role_in_team == 'Sub-Capitán'" class="text-sm font-bold text-red-900">
                                                        {{ ' / '+playera.role_in_team }}
                                                    </span>
                                                </p>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr>
                                        <td class="px-6 py-1.5 text-left bg-gray-50 font-bold">Suplentes</td>
                                    </tr>
                                    <template v-for="(playera, index) in playersa">
                                        <tr v-if="playera.match_role == 'substitute'" :key="index">
                                            <td class="px-6 py-4 font-medium text-heading whitespace-nowrap bg-neutral-secondary-soft">
                                                <p class="font-medium text-heading truncate">{{ playera.person.full_name }}</p>
                                                <p class="text-sm text-body truncate">
                                                    Camiseta: {{ playera.jersey_number }}
                                                    Posición: {{ playera.position }}
                                                    <span v-if="playera.role_in_team == 'Capitán' || playera.role_in_team == 'Sub-Capitán'" class="text-sm font-bold text-red-900">
                                                        {{ ' / '+playera.role_in_team }}
                                                    </span>
                                                </p>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-span-5">
                    <div class="flex items-center mt-6 gap-6">
                        <span>¿Hubo reclamo en cancha?</span>
                        <label class="w-12 h-6 relative">
                            <input v-model="formMatchReport.has_protest" type="checkbox" class="form-checkbox text-success" />
                        </label>
                    </div>
                </div>
            </div>
            <div class="grid sm:grid-cols-5 gap-2 mt-6" v-show="formMatchReport.has_protest">
                <div class="sm:col-span-2">
                    <textarea v-model="formMatchReport.protest_details.team_h" class="form-textarea" rows="4"></textarea>
                </div>
                <div></div>
                <div class="sm:col-span-2">
                    <textarea v-model="formMatchReport.protest_details.team_a" class="form-textarea" rows="4"></textarea>
                </div>
            </div>
            <div class="grid sm:grid-cols-6 gap-2 mt-6">
                <div class="col-span-6">
                    <div class="flex flex-wrap justify-center gap-1.5 sm:gap-2">
                        <button @click="openModalClientSearch" class="btn btn-danger uppercase text-xs">
                            Agregar Arbitro
                        </button>
                        <SearchClients
                            :display="displayModalClientSearch"
                            :closeModalClient="closeModalClientSearch"
                            @clientId="getDataClient"
                            :ubigeo="ubigeo"
                            :documentTypes="documentTypes"
                        />
                        <div>
                            <InputError :message="formMatchReport.errors.manager_name" class="mt-2" />
                        </div>
                    </div>
                    <div v-if="formMatchReport.referees.length > 0" class="flex flex-wrap justify-center gap-1.5 sm:gap-2 mt-6">
                        <span v-for="(referee, index) in formMatchReport.referees" class="py-1.5 px-2.5 inline-flex items-center gap-x-1.5 text-sm text-gray-800 bg-gray-100 hover:text-cyan-700 rounded-lg focus:outline-hidden focus:text-cyan-700 dark:bg-neutral-700 dark:text-neutral-200 dark:hover:text-neutral-400 dark:focus:text-neutral-400">
                            <IconX class="w-5 h-5" @click="removeReferee(index)" />
                            {{ referee.full_name }}
                        </span>
                    </div>
                </div>
                <div class="col-span-6">
                    <InputLabel :value="'Observaciones (Opcional)'" />
                    <textarea v-model="formMatchReport.observations" rows="4" class="form-textarea"></textarea>
                </div>
            </div>
        </template>
        <template #buttons>
            <PrimaryButton
                type="button"
                @click="submitReport"
                :disabled="formMatchReport.processing"
            >
                <div v-if="formMatchReport.processing" class="mr-2">
                    <IconBallSoccer class="w-4 h-4 animate-bounce-ball" />
                </div>

                <span>
                    {{ formMatchReport.processing ? 'Procesando...' : 'Guardar Acta y finalizar' }}
                </span>
            </PrimaryButton>
        </template>
    </ModalLargeX>

</template>
