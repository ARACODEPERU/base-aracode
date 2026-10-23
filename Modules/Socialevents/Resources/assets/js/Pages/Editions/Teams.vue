<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import { useForm } from '@inertiajs/vue3';
    import { domToPng } from 'modern-screenshot'
    import Swal2 from "sweetalert2";
    import { Link, router, usePage } from '@inertiajs/vue3';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { computed, nextTick , ref } from "vue";
    import Multiselect from '@suadelabs/vue3-multiselect';
    import '@suadelabs/vue3-multiselect/dist/vue3-multiselect.css';
    import iconTrashLines from '@/Components/vristo/icon/icon-trash-lines.vue';
    import IconPlus from '@/Components/vristo/icon/icon-plus.vue';
    import IconLoader from '@/Components/vristo/icon/icon-loader.vue';
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
        bonusHistory: {
            type: Array,
            default: () => [],
        },
        sanctionAdjustments: {
            type: Array,
            default: () => [],
        },
    });

    const page = usePage();
    // Mismo tema azul noche y lima que el listado y el fixture de la edición.
    const sportsMode = computed(() => page.props.sportsOrganization?.mode === true);

    // Sanciones administrativas por equipo (para los badges de la tabla)
    const sanctionsByTeam = computed(() => {
        const map = {};
        (props.sanctionAdjustments || []).forEach((adj) => {
            map[adj.team_id] = map[adj.team_id] || [];
            map[adj.team_id].push(adj);
        });
        return map;
    });

    const sanctionBadge = (teamId) => {
        const items = sanctionsByTeam.value[teamId];
        if (!items || !items.length) return null;
        const net = items.reduce((sum, a) => sum + (Number(a.points) || 0), 0);
        const lastReason = items[0]?.reason || '';
        const lastDate = items[0]?.created_at || '';
        return { net, lastReason, lastDate };
    };

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
                    only: ['currentEquipment', 'teams'],
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

    const downloadPdfRoster = async (team) => {
        try {
            const response = await axios.get(route('even_ediciones_equipos_pdf_roster', [team.edition_id, team.team_id]));
            if (response.data.url) {
                window.open(response.data.url, '_blank');
            }
        } catch (error) {
            Swal2.fire({
                title: 'Error',
                text: 'No se pudo generar la ficha.',
                icon: 'error',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        }
    }

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

    // ====== Puntos extra ======
    const displayModalBonus = ref(false);
    const bonusPoints = ref(null);
    const bonusReason = ref('');
    const bonusLoading = ref(false);
    const bonusEquipos = ref([]);
    const bonusHistoryLocal = ref(props.bonusHistory || []);

    const toggleBonusTeam = (teamId) => {
        const idx = bonusEquipos.value.indexOf(teamId);
        if (idx === -1) {
            bonusEquipos.value.push(teamId);
        } else {
            bonusEquipos.value.splice(idx, 1);
        }
    };

    const openModalBonus = () => {
        bonusPoints.value = null;
        bonusReason.value = '';
        bonusEquipos.value = [];
        displayModalBonus.value = true;
    };

    const closeModalBonus = () => {
        displayModalBonus.value = false;
    };

    const reloadAfterBonus = () => {
        router.visit(route('even_ediciones_equipos', props.edicion.id), {
            replace: false,
            method: 'get',
            preserveState: false,
            preserveScroll: true,
        });
    };

    const saveBonus = () => {
        const puntos = Number(bonusPoints.value);
        if (!puntos || puntos < 1) {
            Swal2.fire({ title: 'Atención', text: 'Ingresa al menos 1 punto.', icon: 'warning', padding: '2em', customClass: 'sweet-alerts' });
            return;
        }
        if (!bonusEquipos.value.length) {
            Swal2.fire({ title: 'Atención', text: 'Selecciona al menos un equipo.', icon: 'warning', padding: '2em', customClass: 'sweet-alerts' });
            return;
        }
        if (!bonusReason.value.trim()) {
            Swal2.fire({ title: 'Atención', text: 'Indica el motivo del punto extra.', icon: 'warning', padding: '2em', customClass: 'sweet-alerts' });
            return;
        }

        bonusLoading.value = true;
        axios.post(route('even_ediciones_equipos_bonus_store', props.edicion.id), {
            teams: bonusEquipos.value,
            points: puntos,
            reason: bonusReason.value.trim(),
        }).then((res) => {
            Swal2.fire({
                title: 'Enhorabuena',
                text: res.data?.message || 'Puntos extra otorgados.',
                icon: 'success',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
            closeModalBonus();
            reloadAfterBonus();
        }).catch((err) => {
            Swal2.fire({
                title: 'Error',
                text: err.response?.data?.message || 'No se pudo otorgar los puntos.',
                icon: 'error',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        }).finally(() => {
            bonusLoading.value = false;
        });
    };

    const destroyBonus = (bonusId) => {
        Swal2.fire({
            title: '¿Eliminar este punto extra?',
            text: 'Se revertirá el bono del equipo.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            padding: '2em',
            customClass: 'sweet-alerts',
        }).then((result) => {
            if (!result.isConfirmed) return;
            axios.delete(route('even_ediciones_equipos_bonus_destroy', [props.edicion.id, bonusId]))
                .then(() => {
                    Swal2.fire({ title: 'Eliminado', text: 'Punto extra eliminado.', icon: 'success', timer: 1500, showConfirmButton: false, padding: '2em', customClass: 'sweet-alerts' });
                    reloadAfterBonus();
                })
                .catch(() => {
                    Swal2.fire({ title: 'Error', text: 'No se pudo eliminar.', icon: 'error', padding: '2em', customClass: 'sweet-alerts' });
                });
        });
    };

    const recalculateLoading = ref(false);

    const recalculateTable = () => {
        Swal2.fire({
            title: '¿Recalcular tabla de puntos?',
            text: 'Se volverán a calcular los puntos, goles y posiciones de todos los equipos de esta edición.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, recalcular',
            cancelButtonText: 'Cancelar',
            padding: '2em',
            customClass: 'sweet-alerts',
        }).then((result) => {
            if (!result.isConfirmed) return;

            recalculateLoading.value = true;
            axios.post(route('even_ediciones_equipos_recalculate', props.edicion.id))
                .then((res) => {
                    Swal2.fire({
                        title: 'Recalculado',
                        text: res.data?.message || 'Tabla de posiciones recalculada.',
                        icon: 'success',
                        padding: '2em',
                        customClass: 'sweet-alerts',
                    });
                    reloadAfterBonus();
                })
                .catch((err) => {
                    Swal2.fire({
                        title: 'Error',
                        text: err.response?.data?.message || 'No se pudo recalcular la tabla.',
                        icon: 'error',
                        padding: '2em',
                        customClass: 'sweet-alerts',
                    });
                })
                .finally(() => {
                    recalculateLoading.value = false;
                });
        });
    };
</script>
<template>
    <AppLayout title="Ediciones">
        <Navigation :routeModule="route('even_dashboard')" :titleModule="sportsMode ? 'GESTIÓN DEPORTIVA' : 'Eventos sociales'"
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
                {route:route('even_ediciones_editar', edicion.id), title: edicion.name},
                {title: 'Inscripciones y Tabla'}
            ]"
        />
        <div class="pt-5" :class="sportsMode ? 'text-slate-100' : ''">
            <section
                class="rounded-2xl border p-5 sm:p-6"
                :class="sportsMode
                    ? 'border-white/10 bg-[#0b1f2b] text-white shadow-[0_26px_70px_rgba(0,0,0,0.38)]'
                    : 'border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900'"
            >
                <div class="min-w-0">
                    <div
                        class="mb-2 inline-flex items-center gap-2 rounded-full border px-3 py-1 text-[0.65rem] font-bold uppercase tracking-[0.2em]"
                        :class="sportsMode ? 'border-lime-300/25 bg-lime-300/10 text-lime-200' : 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200'"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-lime-400"></span> Inscripciones
                    </div>
                    <h1 class="text-2xl font-black tracking-tight sm:text-3xl" :class="sportsMode ? 'text-white' : 'text-gray-900 dark:text-white'">
                        Equipos y tabla de posiciones
                    </h1>
                    <p class="mt-1 text-sm" :class="sportsMode ? 'text-slate-300' : 'text-gray-600 dark:text-gray-300'">
                        {{ edicion.name }}<span v-if="edicion.year"> · {{ edicion.year }}</span> — inscribe equipos, ajusta puntos y revisa la tabla de la edición.
                    </p>
                </div>
            </section>
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
                        <button
                            @click="addTeamSave"
                            :disabled="form.processing"
                            class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-xs font-bold uppercase tracking-wider transition disabled:cursor-not-allowed disabled:opacity-50"
                            :class="[sportsMode ? 'bg-[#c8f169] text-[#08251a] hover:bg-[#d9fb8d]' : 'btn btn-primary text-xs', { 'opacity-25': form.processing }]"
                            type="button"
                        >
                            <IconLoader v-if="form.processing" class="w-4 h-4 animate-spin mr-1" />
                            <icon-plus v-else class="w-4 h-4 mr-1" />
                            Agregar
                        </button>
                    </div>
                    <div class="flex items-center justify-end gap-6">
                        <button
                            @click="openModalBonus"
                            class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-xs font-bold uppercase tracking-wider transition"
                            :class="sportsMode ? 'border border-[#2b4b5d] text-slate-200 hover:border-lime-300/60 hover:text-lime-200' : 'btn btn-secondary text-xs'"
                        >
                            <svg class="w-4 h-4 mr-1 inline" fill="currentColor" viewBox="0 0 384 512">
                                <path d="M240 32h64l70.3 140c8 16 8.1 35.5 0 51.5L278.6 384H192v-32c0-17.7-14.3-32-32-32s-32 14.3-32 32v32H88l-96.3-160.5c-8.1-16-8.1-35.5 0-51.5L80 32h64v32c0 17.7 14.3 32 32 32s32-14.3 32-32V32zm-80 96c-17.7 0-32 14.3-32 32s14.3 32 32 32s32-14.3 32-32s-14.3-32-32-32zm128 0c-17.7 0-32 14.3-32 32s14.3 32 32 32s32-14.3 32-32s-14.3-32-32-32z"/>
                            </svg>
                            Puntos extra
                        </button>
                        <button
                            @click="recalculateTable"
                            :disabled="recalculateLoading"
                            class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-xs font-bold uppercase tracking-wider transition disabled:cursor-not-allowed disabled:opacity-50"
                            :class="sportsMode ? 'border border-[#2b4b5d] text-slate-200 hover:border-lime-300/60 hover:text-lime-200' : 'btn btn-info text-xs'"
                        >
                            <svg v-if="recalculateLoading" class="mr-1 h-4 w-4 inline animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                            <svg v-else class="w-4 h-4 mr-1 inline" fill="currentColor" viewBox="0 0 512 512">
                                <path d="M463.5 224H472c13.3 0 24-10.7 24-24V72c0-9.7-5.8-18.5-14.8-22.2s-19.3-1.7-26.2 5.2L413.4 96.6c-87.6-86.5-228.7-86.2-315.8 1c-87.5 87.5-87.5 229.3 0 316.8s229.3 87.5 316.8 0c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0c-62.5 62.5-163.8 62.5-226.3 0s-62.5-163.8 0-226.3c62.2-62.2 162.7-62.5 225.3-1L327 183c-6.9 6.9-8.9 17.2-5.2 26.2s12.5 14.8 22.2 14.8H463.5z"/>
                            </svg>
                            Recalcular tabla
                        </button>
                        <button
                            @click="exportImage"
                            class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-xs font-bold uppercase tracking-wider transition"
                            :class="sportsMode ? 'border border-[#2b4b5d] text-slate-200 hover:border-lime-300/60 hover:text-lime-200' : 'btn btn-danger text-xs'"
                        >
                            Exportar imagen
                        </button>
                        <Link
                            :href="route('even_ediciones_listado')"
                            :preserveState="true"
                            class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-xs font-bold uppercase tracking-wider transition"
                            :class="sportsMode ? 'border border-[#2b4b5d] text-slate-200 hover:border-lime-300/60 hover:text-lime-200' : 'btn btn-warning text-xs'"
                        >
                            Ir atras
                        </Link>
                    </div>
                </div>
                <div class="mt-6">
                    <div
                        ref="captureRef"
                        :class="sportsMode ? 'rounded-2xl border border-[#234052] bg-[#0b1f2b] p-5 text-slate-100 shadow-[0_26px_70px_rgba(0,0,0,0.38)]' : 'panel'"
                    >
                        <h3 class="text-lg uppercase mb-4 font-medium" :class="sportsMode ? 'text-white' : ''">Tabla de posiciones</h3>
                        <div class="table-responsive">
                            <table class="w-full text-sm text-left rtl:text-right">
                                <thead
                                    class="bg-gradient-to-r text-white"
                                    :class="sportsMode ? 'from-[#122c3a] to-[#0e2532] text-lime-200' : 'from-gray-800 to-gray-700'"
                                >
                                    <tr>
                                        <th scope="col" class="px-4 py-4 font-bold text-center">#</th>
                                        <th class="no-export px-4 py-4 font-bold text-center">Acciones</th>
                                        <th scope="col" class="px-4 py-4 font-bold text-left">Equipo</th>
                                        <th scope="col" class="px-4 py-4 font-bold text-center">PTS</th>
                                        <th scope="col" class="px-4 py-4 font-bold text-center">P. Extra</th>
                                        <th scope="col" class="px-4 py-4 font-bold text-center">PJ</th>
                                        <th scope="col" class="px-4 py-4 font-bold text-center">PG</th>
                                        <th scope="col" class="px-4 py-4 font-bold text-center">PE</th>
                                        <th scope="col" class="px-4 py-4 font-bold text-center">PP</th>
                                        <th scope="col" class="px-4 py-4 font-bold text-center">DG</th>
                                        <th scope="col" class="px-4 py-4 font-bold text-center">GF</th>
                                        <th scope="col" class="px-4 py-4 font-bold text-center">GC</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y" :class="sportsMode ? 'divide-[#1b3544]' : 'divide-gray-200 dark:divide-gray-700'">
                                    <template v-if="currentEquipment.length > 0">
                                        <tr v-for="(team, index) in currentEquipment"
                                            :class="[
                                                'transition-colors duration-200',
                                                sportsMode
                                                    ? [
                                                        'hover:bg-[#122c3a]',
                                                        index === 0 ? 'bg-[#122c3a]' :
                                                        index === 1 ? 'bg-[#102734]' :
                                                        index === 2 ? 'bg-[#0e2532]' :
                                                        index % 2 === 0 ? 'bg-[#0b1f2b]' : 'bg-[#0d2230]'
                                                    ]
                                                    : [
                                                        'hover:bg-gray-50 dark:hover:bg-gray-700',
                                                        index === 0 ? 'bg-gradient-to-r from-yellow-50 to-yellow-100 dark:from-yellow-900/20 dark:to-yellow-800/20' :
                                                        index === 1 ? 'bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-600/20 dark:to-gray-500/20' :
                                                        index === 2 ? 'bg-gradient-to-r from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20' :
                                                        index % 2 === 0 ? 'bg-white dark:bg-gray-800' : 'bg-gray-50 dark:bg-gray-700'
                                                    ]
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
                                                    <button
                                                        v-can="'even_ediciones_equipos'"
                                                        @click="downloadPdfRoster(team)"
                                                        v-tippy="{content: 'Imprimir Ficha', placement: 'bottom'}"
                                                        class="p-1 hover:bg-gray-200 rounded-lg transition dark:hover:bg-zinc-700 no-export"
                                                    >
                                                        <svg class="w-5 h-5 text-orange-500" fill="currentColor" viewBox="0 0 512 512">
                                                            <path d="M128 0C92.7 0 64 28.7 64 64l0 96 64 0 0-96 226.7 0L384 93.3l0 66.7 64 0 0-80c0-17-6.7-33.3-18.7-45.3L354.7 18.7C342.7 6.7 326.5 0 309.3 0L128 0zM384 352l0-32 0-32 0-160L256 128l0 96c0 17.7-14.3 32-32 32l-96 0 0 96 0 32 0 32 256 0zm-256 96l256 0c35.3 0 64-28.7 64-64l0-128c0-35.3-28.7-64-64-64l-64 0 0-64L128 0 64 0C28.7 0 0 28.7 0 64L0 384c0 35.3 28.7 64 64 64l64 0z"/>
                                                        </svg>
                                                    </button>
                                                    <button v-can="'even_ediciones_equipos_eliminar'" @click="destroyTeam(team.edition_id, team.team_id)" class="no-export text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 transition-colors" v-tippy="{ content: 'Eliminar equipo', placement: 'bottom'}" type="button">
                                                        <iconTrashLines class="w-5 h-5" />
                                                    </button>
                                                </div>
                                            </td>
                                            <td scope="row" class="flex items-center px-4 py-2 text-heading whitespace-nowrap gap-4">
                                                <img v-if="team.equipo.logo_path" class="w-12 h-12 rounded-full border-2 border-gray-300 dark:border-gray-600" :src="xhttp + 'storage/' + team.equipo.logo_path" :alt="team.equipo.short_name">
                                                <img v-else class="w-12 h-12 rounded-full border-2 border-gray-300 dark:border-gray-600" :src="`https://ui-avatars.com/api/?name=${team.equipo.short_name}&size=150&length=4`" :alt="team.equipo.short_name">
                                                <div class="text-base font-semibold" :class="sportsMode ? 'text-slate-100' : 'text-gray-900 dark:text-white'">{{ team.equipo.name }}</div>
                                            </td>
                                            <td class="px-4 py-2 text-center">
                                                <span class="px-3 py-1 rounded-full font-bold text-lg" :class="sportsMode ? 'bg-[#c8f169] text-[#08251a]' : 'bg-blue-600 text-white'">{{ (Number(team.points) || 0) + (Number(team.bonus_points) || 0) }}</span>
                                                <span
                                                    v-if="sanctionBadge(team.team_id)"
                                                    class="inline-flex items-center ml-1 px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-xs font-bold align-middle"
                                                    :title="'Sanción administrativa (' + (sanctionBadge(team.team_id).lastReason || '') + ')' + (sanctionBadge(team.team_id).lastDate ? ' — ' + sanctionBadge(team.team_id).lastDate : '')"
                                                >
                                                    ⚖ {{ sanctionBadge(team.team_id).net > 0 ? '+' : '' }}{{ sanctionBadge(team.team_id).net }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2 text-center">
                                                <span
                                                    class="inline-flex items-center px-2 py-1 rounded-full text-sm font-bold"
                                                    :class="sportsMode ? 'bg-lime-400/15 text-lime-200' : 'bg-amber-100 text-amber-800'"
                                                >{{ team.bonus_points || 0 }}</span>
                                            </td>
                                            <td class="px-4 py-2 text-center font-medium" :class="sportsMode ? 'text-slate-300' : 'text-gray-700 dark:text-gray-300'">{{ team.matches_played }}</td>
                                            <td class="px-4 py-2 text-center font-medium" :class="sportsMode ? 'text-lime-300' : 'text-green-600'">{{ team.matches_won }}</td>
                                            <td class="px-4 py-2 text-center font-medium" :class="sportsMode ? 'text-amber-300' : 'text-yellow-600'">{{ team.matches_drawn }}</td>
                                            <td class="px-4 py-2 text-center font-medium" :class="sportsMode ? 'text-rose-300' : 'text-red-600'">{{ team.matches_lost }}</td>
                                            <td class="px-4 py-2 text-center font-medium" :class="sportsMode ? (team.goal_difference >= 0 ? 'text-lime-300' : 'text-rose-300') : (team.goal_difference >= 0 ? 'text-green-600' : 'text-red-600')">{{ team.goal_difference }}</td>
                                            <td class="px-4 py-2 text-center font-medium" :class="sportsMode ? 'text-slate-300' : 'text-gray-700 dark:text-gray-300'">{{ team.goals_for }}</td>
                                            <td class="px-4 py-2 text-center font-medium" :class="sportsMode ? 'text-slate-300' : 'text-gray-700 dark:text-gray-300'">{{ team.goals_against }}</td>
                                        </tr>
                                    </template>
                                    <template v-else>
                                        <tr>
                                            <td colspan="12" :class="sportsMode ? 'text-slate-400' : ''"><Empty :description="'Sin equipos'" /></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Puntos extra -->
        <div
            v-if="displayModalBonus"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            @click.self="closeModalBonus"
        >
            <div
                class="w-full max-w-2xl rounded-lg p-6 shadow-2xl max-h-[90vh] overflow-y-auto"
                :class="sportsMode ? 'border border-[#234052] bg-[#0b1f2b] text-slate-100' : 'bg-white dark:bg-slate-800'"
            >
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold" :class="sportsMode ? 'text-white' : 'text-slate-800 dark:text-white'">Asignar puntos extra</h3>
                    <button
                        type="button"
                        class="transition"
                        :class="sportsMode ? 'text-slate-400 hover:text-lime-200' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-200'"
                        @click="closeModalBonus"
                    >✕</button>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium" :class="sportsMode ? 'text-slate-300' : 'text-slate-700 dark:text-slate-200'">Puntos *</label>
                        <input
                            v-model.number="bonusPoints"
                            type="number"
                            min="1"
                            max="999"
                            class="form-input w-full"
                            placeholder="Ej: 1"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium" :class="sportsMode ? 'text-slate-300' : 'text-slate-700 dark:text-slate-200'">Motivo *</label>
                        <input
                            v-model="bonusReason"
                            type="text"
                            class="form-input w-full"
                            placeholder="Ej: Buena conducta / Inauguración"
                        />
                    </div>
                </div>

                <div class="mt-5">
                    <label class="mb-2 block text-sm font-medium" :class="sportsMode ? 'text-slate-300' : 'text-slate-700 dark:text-slate-200'">Equipos que reciben los puntos *</label>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 max-h-52 overflow-y-auto">
                        <label
                            v-for="team in currentEquipment"
                            :key="team.team_id"
                            class="flex cursor-pointer items-center gap-2 rounded-md border px-3 py-2 transition"
                            :class="[
                                sportsMode ? 'border-[#2b4b5d] hover:border-lime-300/60' : 'border-slate-200 hover:border-cyan-500 dark:border-slate-600',
                                bonusEquipos.includes(team.team_id)
                                    ? (sportsMode ? 'border-lime-300/70 bg-lime-400/10' : 'border-cyan-500 bg-cyan-50 dark:bg-cyan-900/20')
                                    : ''
                            ]"
                        >
                            <input
                                type="checkbox"
                                class="form-checkbox text-cyan-600"
                                :checked="bonusEquipos.includes(team.team_id)"
                                @change="toggleBonusTeam(team.team_id)"
                            />
                            <span class="text-sm" :class="sportsMode ? 'text-slate-200' : 'text-slate-700 dark:text-slate-200'">{{ team.equipo?.name }}</span>
                        </label>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-xs font-bold uppercase tracking-wider transition"
                        :class="sportsMode ? 'border border-[#2b4b5d] text-slate-200 hover:border-lime-300/60 hover:text-lime-200' : 'btn btn-outline-dark'"
                        @click="closeModalBonus"
                    >Cancelar</button>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-xs font-bold uppercase tracking-wider transition disabled:cursor-not-allowed disabled:opacity-50"
                        :class="sportsMode ? 'bg-[#c8f169] text-[#08251a] hover:bg-[#d9fb8d]' : 'btn btn-primary'"
                        :disabled="bonusLoading"
                        @click="saveBonus"
                    >
                        <svg v-if="bonusLoading" class="mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        Asignar puntos
                    </button>
                </div>

                <div v-if="bonusHistory.length" class="mt-6 border-t pt-4" :class="sportsMode ? 'border-[#234052]' : 'border-slate-200 dark:border-slate-600'">
                    <h4 class="mb-2 text-sm font-semibold" :class="sportsMode ? 'text-slate-300' : 'text-slate-700 dark:text-slate-200'">Historial de puntos extra</h4>
                    <ul class="space-y-2">
                        <li
                            v-for="bonus in bonusHistory"
                            :key="bonus.id"
                            class="flex items-center justify-between rounded-md px-3 py-2 text-sm"
                            :class="sportsMode ? 'bg-[#122c3a]' : 'bg-slate-50 dark:bg-slate-700'"
                        >
                            <div>
                                <span class="font-semibold" :class="sportsMode ? 'text-white' : 'text-slate-800 dark:text-white'">{{ bonus.team_name }}</span>
                                <span
                                    class="ml-2 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-bold"
                                    :class="sportsMode ? 'bg-lime-400/15 text-lime-200' : 'bg-amber-100 text-amber-800'"
                                >+{{ bonus.points }}</span>
                                <span class="block text-xs" :class="sportsMode ? 'text-slate-400' : 'text-slate-500 dark:text-slate-300'">{{ bonus.reason }}</span>
                            </div>
                            <button
                                type="button"
                                class="text-slate-400 hover:text-red-600"
                                title="Eliminar punto extra"
                                @click="destroyBonus(bonus.id)"
                            >
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M6 6l12 12M6 18L18 6"></path>
                                </svg>
                            </button>
                        </li>
                    </ul>
                </div>
                <p v-else class="mt-6 border-t pt-4 text-center text-sm" :class="sportsMode ? 'border-[#234052] text-slate-500' : 'border-slate-200 text-slate-400 dark:border-slate-600'">
                    No hay puntos extra otorgados.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
