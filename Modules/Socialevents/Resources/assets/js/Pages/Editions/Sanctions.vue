<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import { domToPng } from 'modern-screenshot'
    import Swal2 from "sweetalert2";
    import { Link, router, useForm } from '@inertiajs/vue3';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { computed, nextTick , ref } from "vue";
    import iconLoader from '@/Components/vristo/icon/icon-loader.vue';
    import '@suadelabs/vue3-multiselect/dist/vue3-multiselect.css';
    import ModalLarge from '@/Components/ModalLarge.vue';
    import { Empty } from 'ant-design-vue';
    import Keypad from '@/Components/Keypad.vue'
    import InputLabel from '@/Components/InputLabel.vue';
    import TextInput from '@/Components/TextInput.vue';

    const props = defineProps({
        edicion: {
            type: Object,
            default: () => ({}),
        },
        players: {
            type: Object,
            default: () => ({}),
        }
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

    // Función de traducción para sanciones
    const translateSanction = (type) => {
        const types = {
            'yellow': 'Amarilla',
            'double_yellow': 'Doble Amarilla',
            'red': 'Roja Directa'
        };
        return types[type] || type;
    };

    // Función para el color del badge
    const getSanctionColor = (type) => {
        const colors = {
            'yellow': 'bg-yellow-100 text-yellow-800 border-yellow-300',
            'double_yellow': 'bg-orange-100 text-orange-800 border-orange-300',
            'red': 'bg-red-100 text-red-800 border-red-300'
        };
        return colors[type] || 'bg-gray-100 text-gray-800';
    };

    const isSanctionCancelled = (currentSanction, allSanctions) => {
        // Si la sanción actual es una amarilla
        if (currentSanction.type === 'yellow') {
            // Buscamos si en el mismo partido (match_id) existe una doble amarilla
            const hasRedInSameMatch = allSanctions.some(s =>
                s.match_id === currentSanction.match_id &&
                s.type === 'double_yellow'
            );
            return hasRedInSameMatch;
        }
        return false;
    };

    const displayModalPaymmet = ref(false);
    const selectedPlayer = ref([]);
    const formSanction = useForm({
        player_id: null,
        responsable: null,
        items: [],
        payments: [],
        total: null
    });

    const openModalPaymmet = (player) => {
        const sanctionsToPay = player.sanctions.filter(s => {
            // Si es una amarilla, verificamos si hay una 'double_yellow' en ese mismo partido
            if (s.type === 'yellow') {
                const hasDoubleYellow = player.sanctions.some(all =>
                    all.match_id === s.match_id &&
                    all.type === 'double_yellow'
                );
                // Si hay doble amarilla, devolvemos false (para excluir esta amarilla simple)
                return !hasDoubleYellow;
            }
            // Si no es amarilla simple (es roja, doble amarilla, etc.), se queda
            return true;
        });

        selectedPlayer.value = player;
        formSanction.responsable = player.person.full_name;
        formSanction.player_id = player.person.id;
        formSanction.items = JSON.parse(JSON.stringify(sanctionsToPay));
        formSanction.total = player.total_debt;
        formSanction.payments.push({
            type:1,
            reference: null,
            amount: player.total_debt
        });
        displayModalPaymmet.value = true;
    }
    const closeModalPaymmet = () => {
        displayModalPaymmet.value = false;
    }

    const saveSale = async () => {
        formSanction.processing = true;
        if(formSanction.total > 0){
            axios.post(route('even_ediciones_pago_sanciones_store'), formSanction ).then((res) => {
                formSanction.reset();
                displayModalPaymmet.value = false;
                router.reload({
                    only: ['players'],
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: page => {
                        formSanction.processing = false;
                        Swal2.fire({
                            title: 'Venta registrada',
                            text: "¿Desea imprimir el ticket?",
                            icon: 'info',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Imprimir',
                            cancelButtonText: 'Cancelar',
                            padding: '2em',
                            customClass: 'sweet-alerts',
                        }).then((result) => {
                            if (result.isConfirmed) {
                                printPdf(res.data.id);
                            }
                        });
                     },
                });
            }).catch((error) => {
                let validationErrors = error.response.data.errors;
                if (validationErrors && validationErrors.payments) {
                    const paymentsErrors = validationErrors.payments;
                    for (let i = 0; i < paymentsErrors.length; i++) {
                        formSanction.setError('payments.'+index+'.amount', paymentsErrors[i]);
                    }
                }

                Swal2.close();
            });
        }else{
            alert('Agregar items para realizar la venta');
        }
    }

    const printPdf = (id) => {
        let url = route('ticketpdf_sales',id)
        window.open(url, "_blank");
    }
</script>
<template>
    <AppLayout title="Ediciones">
        <Navigation :routeModule="route('even_dashboard')" :titleModule="'Eventos sociales'">
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <Link :href="route('even_ediciones_listado')" class="text-primary hover:underline">Ediciones</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>Cobro de Sanciones</span>
            </li>
        </Navigation>
        <div class="mt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="grid grid-cols-3 w-full">
                    <div class="col-span-3 sm:col-span-1">
                        <h4 class="text-lg">Listado</h4>
                    </div>
                    <div class="col-span-3 sm:col-span-2">
                        <Keypad>
                            <template #botones>
                                <Link :href="route('even_ediciones_listado')" :preserveState="true" class="btn btn-warning uppercase text-xs">
                                    Ir atras
                                </Link>
                            </template>
                        </Keypad>
                    </div>
                </div>
            </div>
            <div class="mt-6">
                <div class="panel">
                    <div v-if="players.length > 0" class="">
                        <table>
                            <thead>
                                <tr>
                                    <th>Jugador</th>
                                    <th>Equipo</th>
                                    <th>Detalle sanciones</th>
                                    <th>Total sancions</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="player in players" :key="player.id">
                                    <td class="px-2 py-2">
                                        <div class="font-bold">{{ player.person.full_name }}</div>
                                        <div class="text-xs text-gray-500">DNI: {{ player.person.number }}</div>
                                    </td>
                                    <td class="px-2 py-2">{{ player.team.name }}</td>
                                    <td class="px-2 py-2">
                                        <div class="flex flex-wrap gap-1">
                                            <span v-for="s in player.sanctions" :key="s.id"
                                                :class="[
                                                    'px-2 py-1 rounded text-xs font-bold border transition-all',
                                                    getSanctionColor(s.type),
                                                    isSanctionCancelled(s, player.sanctions) ? 'opacity-40 grayscale line-through border-gray-300' : ''
                                                ]"
                                                :title="isSanctionCancelled(s, player.sanctions) ? 'Anulada por doble amarilla' : ''"
                                            >
                                                {{ translateSanction(s.type) }}
                                                <span class="ml-1 opacity-75">S/ {{ s.amount_fine }}</span>

                                                <span v-if="isSanctionCancelled(s, player.sanctions)" class="ml-1 text-xs uppercase">
                                                    (No suma)
                                                </span>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-2 py-2 font-black text-red-600">
                                        <div class="flex items-center justify-between w-16 m-auto">
                                            <span>S/.</span>
                                            <span>{{ player.total_debt }}</span>
                                        </div>
                                    </td>
                                    <td class="px-2 py-2">
                                        <button
                                            class="btn btn-sm"
                                            @click="openModalPaymmet(player)"
                                            :disabled="player.is_paid == 1"
                                            :class="parseFloat(player.total_debt) <= 0 ? 'btn-disabled' : 'btn-success'"
                                        >
                                            {{ parseFloat(player.total_debt) <= 0 ? 'Pagado' : 'Liquidar' }}
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="flex items-center justify-center p-6">
                        <Empty :description="'Sin jugadores sancionados'" />
                    </div>
                </div>
            </div>
        </div>
        <ModalLarge :show="displayModalPaymmet" :onClose="closeModalPaymmet" :icon="'/img/caja-registradora.png'">
            <template #title>{{ selectedPlayer.person.full_name }}</template>
            <template #message>Liquidar sancion</template>
            <template #content>
                <div>
                    <InputLabel value="A nombre de: " />
                    <TextInput v-model="formSanction.responsable" type="text" />
                </div>
                <div class="mt-6 p-0 border dark:border-gray-800">
                    <table >
                        <thead class="">
                            <tr>
                                <th class=""></th>
                                <th >
                                    Descripción
                                </th>
                                <th class="text-center">
                                    Importe
                                </th>
                            </tr>
                        </thead >
                        <tbody style="max-height: 250px;overflow-y: auto;overflow-x: hidden;">
                            <template v-if="formSanction.items.length > 0">
                                <tr v-for="(item, key) in formSanction.items" >
                                    <td class="text-center">
                                        <button @click="removeProduct(key)" type="button" v-tippy:delete>
                                            <icon-trash />
                                        </button>
                                        <tippy target="delete">Eliminar</tippy>
                                    </td>
                                    <td >
                                        {{ translateSanction(item.type)  }}
                                    </td>
                                    <td class="text-right px-4 w-40">
                                        {{ item.amount_fine  }}
                                    </td>
                                </tr>
                            </template>
                            <template v-else>
                                <tr >
                                    <td colspan="5" >
                                        <div class="flex p-4 text-sm border border-gray-300 bg-gray-50 dark:bg-gray-800 dark:text-white dark:border-yellow-800" role="alert">
                                            <svg aria-hidden="true" class="flex-shrink-0 inline w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                                            <span class="sr-only">Info</span>
                                            <div>
                                                <span class="font-medium">Vacío!</span> Agregar productos
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <div class="flex items-center justify-end gap-6 px-4 py-6">
                        <div>
                            Total a Cobrar
                        </div>
                        <div>
                            S/. {{ formSanction.total  }}
                        </div>
                    </div>
                </div>
            </template>
            <template #buttons>
                <button @click="saveSale()" type="button"
                    class="btn btn-primary text-xs uppercase"
                    :class="{ 'opacity-25': formSanction.processing }" :disabled="formSanction.processing"
                >
                    <icon-loader v-show="formSanction.processing" class="w-4 h-4 animate-spin mr-1" />
                    Cobrar
                </button>
            </template>
        </ModalLarge>
    </AppLayout>
</template>

