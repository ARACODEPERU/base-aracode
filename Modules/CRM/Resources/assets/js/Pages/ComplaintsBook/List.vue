<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import Keypad from '@/Components/Keypad.vue';
    import Pagination from '@/Components/Pagination.vue';
    import ModalLarge from '@/Components/ModalLarge.vue';
    import ModalLargeX from '@/Components/ModalLargeX.vue';
    import { ref } from 'vue';
    import { useForm } from '@inertiajs/vue3';
    import FlatPickr from 'vue-flatpickr-component';
    import 'flatpickr/dist/flatpickr.css';
    import { Spanish } from "flatpickr/dist/l10n/es.js"
    import InputError from '@/Components/InputError.vue';
    import { Link, router } from '@inertiajs/vue3';
    import iconTrash from '@/Components/vristo/icon/icon-trash.vue';

    import Swal2 from "sweetalert2";

    defineProps({
        books: {
            type: Object,
            default: () => ({})
        },
        monedas: {
            type: Object,
            default: () => ({})
        },
        priorities: {
            type: Object,
            default: () => ({})
        },
        meansCommunication: {
            type: Array,
            default: () => ([])
        }
    });

    const displayModalDetailsBook =  ref(false);
    const displayModalAtender =  ref(false);
    const bookDetails = ref(null);
    const poperDisplay = ref(null);
    let date = new Date();
    let formattedDate = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');

    const formAttend = useForm({
        book_id: null,
        status: 'AT',
        date_start: formattedDate,
        date_end: formattedDate,
        priority: 'Baja',
        description: null,
        response: null,
        attached_file: null,
        means_communication: null
    });

    const openModalDetailsBook = (item) => {
        displayModalDetailsBook.value = true;
        bookDetails.value = item;
        formAttend.book_id = item.id
    }
    const closeModalDetailsBook = () => {
        displayModalDetailsBook.value = false;
    }
    const openModalAtender = () => {
        displayModalAtender.value = true;
        displayModalDetailsBook.value = false;
    }
    const closeModalAtender = () => {
        displayModalAtender.value = false;
    }

    const configFlatPickr = {
        dateFormat: 'Y-m-d',
        minDate: 'today',  // No permite fechas anteriores a la actual
        disable: [
            function (date) {
                // Bloquear domingos (día 0 en JavaScript)
                return (date.getDay() === 0);
            }
        ],
        locale: Spanish,
    };

    const saveAttention = () => {
        formAttend.post(route('complaints_book_attention_store'), {
            forceFormData: true,
            preserveScroll: true,
            errorBag: "saveAttention",
            onSuccess: () => {
                Swal2.fire({
                    icon: 'success',
                    title: 'Enhorabuena',
                    text: 'Se registro correctamente',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                displayModalAtender.value = false;
                formAttend.reset();
            },
        });
    }
    const destroyAttention = (id, index) => {
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
                return axios.delete(route('complaints_book_attention_destroy', id)).then((res) => {
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
                }).then(() => {
                    removeTableItem(index);
                });
            }
        });
    }

    const removeTableItem = (index) => {
        bookDetails.value.attentions.splice(index, 1);
    }

    const xasset = assetUrl;

    const getPathFile = (path) => {
        return xasset + 'storage/'+ path;
    }
</script>
<template>
    <AppLayout title="Reclamos y Quejas">
        <Navigation >
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Libro de Reclamaciones</span>
            </li>
        </Navigation>
        <div class="mt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl">Lista de Reclamos y Quejas </h2>
                <div class="flex sm:flex-row flex-col sm:items-center sm:gap-3 gap-4 w-full sm:w-auto">
                    <div class="flex gap-3">
                        <Keypad>
                            <template #botones>
                                <Link v-can="'crm_envio_correo_masivo'" :href="route('crm_send_mass_mailing')" class="btn btn-primary uppercase text-xs">Nuevo</Link>
                            </template>
                        </Keypad>

                    </div>
                </div>
            </div>
            <div class="panel pb-1.5 mt-6">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Acciones</th>
                                <th>Num. Folio</th>
                                <th>Nombre</th>
                                <th>Num. Identificación</th>
                                <th>Email</th>
                                <th>Teléfono</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in books.data">
                                <td>
                                    <button @click="openModalDetailsBook(item)" v-tippy="{content: 'Ver detalles', placement: 'bottom'}" type="button" class="btn btn-outline-info text-xs btn-sm">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                            <path fill="currentColor" d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM216 336l24 0 0-64-24 0c-13.3 0-24-10.7-24-24s10.7-24 24-24l48 0c13.3 0 24 10.7 24 24l0 88 8 0c13.3 0 24 10.7 24 24s-10.7 24-24 24l-80 0c-13.3 0-24-10.7-24-24s10.7-24 24-24zm40-208a32 32 0 1 1 0 64 32 32 0 1 1 0-64z"/>
                                        </svg>
                                    </button>
                                </td>
                                <td>{{ item.composite_code }}</td>
                                <td>{{ item.names }}</td>
                                <td>{{ item.dni }}</td>
                                <td>{{ item.email }}</td>
                                <td>{{ item.telefono }}</td>
                                <td>
                                    <!-- primary -->
                                    <span v-if="item.status == 'RE'" class="badge bg-primary">Registrado</span>

                                    <!-- secondary -->
                                    <span v-if="item.status == 'ER'" class="badge bg-secondary">En Revisión</span>

                                    <!-- success -->
                                    <span v-if="item.status == 'AT'" class="badge bg-success">Atendido</span>

                                    <!-- danger -->
                                    <span v-if="item.status == 'RA'" class="badge bg-danger">Reabierto</span>

                                    <!-- danger outline -->
                                    <span v-if="item.status == 'ES'" class="badge badge-outline-danger">Escalado</span>

                                    <!-- warning -->
                                    <span v-if="item.status == 'EP'" class="badge bg-warning">En Proceso</span>

                                    <!-- info -->
                                    <span v-if="item.status == 'AS'" class="badge bg-info">Asignado</span>

                                    <!-- dark -->
                                    <span v-if="item.status == 'CE'" class="badge bg-dark">Cerrado</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <Pagination :data="books" />
                </div>
            </div>
        </div>
        <ModalLargeX :show="displayModalDetailsBook" :onClose="closeModalDetailsBook" :icon="'/img/lupa-documento.png'">
            <template #title>
                NUM. FOLIO: {{ bookDetails.composite_code }}
            </template>
            <template #message>
                {{ bookDetails.names }}
            </template>
            <template #content>
                <div class="space-y-3 m-auto w-3/5">
                    <div class="flex sm:flex-row flex-col">
                        <label for="cbxSer" class="mb-0 sm:w-2/5 sm:ltr:mr-2 rtl:ml-2">Tipo de bien contratado</label>
                        <select v-model="bookDetails.type_service" id="cbxSer" disabled class="form-select flex-1">
                            <option value="product">Producto </option>
                            <option value="service">Servicio</option>
                        </select>
                    </div>
                    <div class="flex sm:flex-row flex-col">
                        <label for="txtdesc" class="mb-0 sm:w-2/5 sm:ltr:mr-2 rtl:ml-2">Descripción del producto o servicio</label>
                        <textarea v-model="bookDetails.description_service" id="txtdesc" disabled class="form-textarea flex-1"></textarea>
                    </div>
                    <div class="flex sm:flex-row flex-col">
                        <label for="cbxTipoQueja" class="mb-0 sm:w-2/5 sm:ltr:mr-2 rtl:ml-2">Tipo de reclamación</label>
                        <select v-model="bookDetails.type_claim" id="cbxTipoQueja" disabled class="form-select flex-1">
                            <option value="queja">Queja </option>
                            <option value="reclamo">Reclamo</option>
                        </select>
                    </div>
                    <div class="flex sm:flex-row flex-col">
                        <label for="txtreclamo" class="mb-0 sm:w-2/5 sm:ltr:mr-2 rtl:ml-2">Reclamación</label>
                        <textarea v-model="bookDetails.claim" id="txtreclamo" disabled class="form-textarea flex-1"></textarea>
                    </div>
                    <div class="flex sm:flex-row flex-col">
                        <label for="txtpedido" class="mb-0 sm:w-2/5 sm:ltr:mr-2 rtl:ml-2">Pedido</label>
                        <textarea v-model="bookDetails.called" id="txtpedido" disabled class="form-textarea flex-1"></textarea>
                    </div>
                    <div class="flex sm:flex-row flex-col">
                        <label for="cbxMoneda" class="mb-0 sm:w-2/5 sm:ltr:mr-2 rtl:ml-2">Monto reclamado (moneda)</label>
                        <select v-model="bookDetails.currency" id="cbxMoneda" disabled class="form-select flex-1">
                            <template v-for="moneda in monedas">
                                <option :value="moneda.id">{{ moneda.description }} ({{ moneda.symbol }})</option>
                            </template>
                        </select>
                    </div>
                    <div class="flex sm:flex-row flex-col">
                        <label for="txtmonto" class="mb-0 sm:w-2/5 sm:ltr:mr-2 rtl:ml-2">Monto reclamado (valor)</label>
                        <input v-model="bookDetails.amount" id="txtmonto" type="number" disabled class="form-input flex-1" />
                    </div>
                </div>
                <div class="mt-6" v-if="bookDetails.attentions && bookDetails.attentions.length > 0">
                    <div class="relative">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Archivo</th>
                                    <th>Inicio</th>
                                    <th>Termino</th>
                                    <th>Medio de comunicación</th>
                                    <th colspan="2" class="text-center">Detalles</th>
                                    <th>Prioridad</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-for="(attention, index) in bookDetails.attentions" :key="attention.id">
                                    <tr>
                                        <td class="text-center">
                                            <a v-if="attention.attached_file"
                                                :href="getPathFile(attention.attached_file)"
                                                target="_blank"
                                                v-tippy="{content: 'Descargar archivo',placement: 'bottom'}"
                                            >
                                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                                                    <path fill="currentColor" d="M352 96C352 78.3 337.7 64 320 64C302.3 64 288 78.3 288 96L288 306.7L246.6 265.3C234.1 252.8 213.8 252.8 201.3 265.3C188.8 277.8 188.8 298.1 201.3 310.6L297.3 406.6C309.8 419.1 330.1 419.1 342.6 406.6L438.6 310.6C451.1 298.1 451.1 277.8 438.6 265.3C426.1 252.8 405.8 252.8 393.3 265.3L352 306.7L352 96zM160 384C124.7 384 96 412.7 96 448L96 480C96 515.3 124.7 544 160 544L480 544C515.3 544 544 515.3 544 480L544 448C544 412.7 515.3 384 480 384L433.1 384L376.5 440.6C345.3 471.8 294.6 471.8 263.4 440.6L206.9 384L160 384zM464 440C477.3 440 488 450.7 488 464C488 477.3 477.3 488 464 488C450.7 488 440 477.3 440 464C440 450.7 450.7 440 464 440z"/>
                                                </svg>
                                            </a>
                                            <a v-else>
                                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                                                    <path fill="currentColor" d="M96 320C96 302.3 110.3 288 128 288L512 288C529.7 288 544 302.3 544 320C544 337.7 529.7 352 512 352L128 352C110.3 352 96 337.7 96 320z"/>
                                                </svg>
                                            </a>
                                        </td>
                                        <td class="whitespace-nowrap">{{ attention.date_start }}</td>
                                        <td>{{ attention.date_end }}</td>
                                        <td>{{ attention.means_communication }}</td>
                                        <td>
                                            <Popper :placement="'auto'" :zIndex="1000" offsetDistance="8">
                                                <p class="flex items-center text-sm text-gray-500 dark:text-gray-400 cursor-pointer">Acciones realizadas</p>
                                                <template #content="{ close }">
                                                    <div class="opacity-100 inline-block text-sm text-gray-500 transition-opacity duration-300 bg-white border border-gray-200 rounded-lg shadow-xs w-72 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-400">
                                                        <div class="p-3 space-y-2" style="max-height: 300px; overflow-y: auto;">
                                                            <h3 class="font-semibold text-gray-900 dark:text-white">Descripción de acciones realizadas</h3>
                                                            <p>{{ attention.internal_management_notes }}</p>
                                                        </div>
                                                    </div>
                                                </template>
                                            </Popper>
                                        </td>
                                        <td>
                                            <Popper :placement="'auto'" :zIndex="1000" offsetDistance="8">
                                                <p class="flex items-center text-sm text-gray-500 dark:text-gray-400 cursor-pointer">Respuesta del consumidor</p>
                                                <template #content="{ close }">
                                                    <div class="opacity-100 inline-block text-sm text-gray-500 transition-opacity duration-300 bg-white border border-gray-200 rounded-lg shadow-xs w-72 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-400">
                                                        <div class="p-3 space-y-2" style="max-height: 300px; overflow-y: auto;">
                                                            <h3 class="font-semibold text-gray-900 dark:text-white">Respuesta del consumidor</h3>
                                                            <p>{{ attention.consumer_response }}</p>
                                                        </div>
                                                    </div>
                                                </template>
                                            </Popper>
                                        </td>
                                        <td>
                                            <!-- danger -->
                                            <span v-if="attention.priority == 'Alta'" class="badge bg-danger rounded-none">Alta</span>

                                            <!-- warning -->
                                            <span v-if="attention.priority == 'Media'" class="badge bg-warning rounded-none">Media</span>

                                            <!-- info -->
                                            <span v-if="attention.priority == 'Baja'" class="badge bg-info rounded-none">Baja</span>

                                            <!-- dark -->
                                            <span v-if="attention.priority == 'Crítica'" class="badge bg-dark rounded-none">Crítica</span>
                                        </td>
                                        <td class="text-center">
                                            <button @click="destroyAttention(attention.id, index)" type="button" v-tippy="{content: 'Eliminar', placement: 'bottom'}">
                                                <icon-trash class="w-4 h-4" />
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>
            <template #buttons>
                <button @click="openModalAtender" type="button" class="btn btn-success text-xs uppercase">
                    Atender
                </button>
            </template>
        </ModalLargeX>
        <ModalLargeX :show="displayModalAtender" :onClose="closeModalAtender" :icon="'/img/icons8-soporte-en-línea-48.png'">
            <template #title>
                NUM. FOLIO: {{ bookDetails.composite_code }}
            </template>
            <template #message>
                {{ bookDetails.names }}
            </template>
            <template #content>
                <form class="space-y-5">
                    <div class="grid grid-cols-1 lg:grid-cols-6 gap-4">
                        <div>
                            <label for="cbxStatus">Estado</label>
                            <select v-model="formAttend.status" id="cbxStatus" class="form-select">
                                <option value="RE">Registrado</option>
                                <option value="ER">En Revisión</option>
                                <option value="EP">En Proceso</option>
                                <option value="AS">Asignado</option>
                                <option value="AT">Atendido</option>
                                <option value="CE">Cerrado</option>
                                <option value="ES">Escalado</option>
                                <option value="RA">Reabierto</option>
                            </select>
                            <InputError :message="formAttend.errors.status" class="mt-2" />
                        </div>
                        <div>
                            <label for="fechainicio">Fecha atención</label>
                            <flat-pickr
                                v-model="formAttend.date_start"
                                :config="configFlatPickr"
                                class="form-input"
                                placeholder="Selecciona fecha"
                            />
                            <InputError :message="formAttend.errors.date_start" class="mt-2" />
                        </div>
                        <div>
                            <label for="fechaentrega">Fecha resuelto</label>
                            <flat-pickr
                                v-model="formAttend.date_end"
                                :config="configFlatPickr"
                                class="form-input"
                                placeholder="Selecciona fecha"
                            />
                            <InputError :message="formAttend.errors.date_end" class="mt-2" />
                        </div>
                        <div>
                            <label for="cbxPriority">Prioridad</label>
                            <select v-model="formAttend.priority" id="cbxPriority" class="form-select">
                                <template v-for="priority in priorities">
                                    <option :value="priority">{{ priority }}</option>
                                </template>
                            </select>
                            <InputError :message="formAttend.errors.priority" class="mt-2" />
                        </div>
                        <div class="lg:col-span-2">
                            <label for="cbxMedio">Medio de comunicación</label>
                            <select v-model="formAttend.means_communication" id="cbxMedio" class="form-select">
                                <template v-for="priority in meansCommunication">
                                    <option :value="priority">{{ priority }}</option>
                                </template>
                            </select>
                            <InputError :message="formAttend.errors.priority" class="mt-2" />
                        </div>
                        <div class="sm:col-span-3">
                            <label for="txtdecription">Descripción de acciones realizadas</label>
                            <textarea v-model="formAttend.description" class="form-textarea" id="txtdecription" rows="8"></textarea>
                            <InputError :message="formAttend.errors.description" class="mt-2" />
                        </div>
                        <div class="sm:col-span-3">
                            <label for="txtresponse">Respuesta del consumidor</label>
                            <textarea v-model="formAttend.response" class="form-textarea" id="txtresponse" rows="8"></textarea>
                            <InputError :message="formAttend.errors.response" class="mt-2" />
                        </div>
                        <div class="lg:col-span-6">
                            <label for="txtresponse">Adjuntar Archivo</label>
                            <div>
                                <label for="small-file-input" class="sr-only">Choose file</label>
                                <input type="file" name="small-file-input" id="small-file-input" class="block w-full border border-gray-200 shadow-sm rounded-lg text-sm focus:z-10 focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400
                                file:bg-gray-50 file:border-0
                                file:me-4
                                file:py-2 file:px-4
                                dark:file:bg-neutral-700 dark:file:text-neutral-400"
                                @input="formAttend.attached_file = $event.target.files[0]"
                            >
                            </div>
                            <InputError :message="formAttend.errors.response" class="mt-2" />
                        </div>
                    </div>
                </form>
            </template>
            <template #buttons>
                <button @click="saveAttention"
                    :class="{ 'opacity-25': formAttend.processing }" :disabled="formAttend.processing"
                    type="button" class="btn btn-primary text-xs uppercase">
                    <svg v-show="formAttend.processing" aria-hidden="true" role="status" class="inline w-4 h-4 mr-3 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                        <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                    </svg>
                    Guardar
                </button>
            </template>
        </ModalLargeX>
    </AppLayout>
</template>
