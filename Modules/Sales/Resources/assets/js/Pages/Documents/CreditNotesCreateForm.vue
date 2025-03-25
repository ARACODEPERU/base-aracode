<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import DangerButton from '@/Components/DangerButton.vue';
    import SearchClients from './Partials/SearchClients.vue';
    import SearchProducts from './Partials/SearchProducts.vue';
    import { faPlus, faXmark, faMagnifyingGlass } from "@fortawesome/free-solid-svg-icons";
    import { useForm } from '@inertiajs/vue3';
    import InputError from '@/Components/InputError.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import { ref, onMounted } from 'vue';
    import Swal from 'sweetalert2';
    import { Link, router } from '@inertiajs/vue3';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import flatPickr from 'vue-flatpickr-component';
    import 'flatpickr/dist/flatpickr.css';
    import axios from 'axios';
    import iconPencil from '@/Components/vristo/icon/icon-pencil.vue';
    import iconTrash from '@/Components/vristo/icon/icon-trash.vue';


    const props = defineProps({
        typeCreditNote: {
            type: Object,
            default: () => ({}),
        },
        typeDebitNote: {
            type: Object,
            default: () => ({}),
        },
        series: {
            type: Object,
            default: () => ({}),
        },
        unitTypes:{
            type: Object,
            default: () => ({}),
        },
        saleDocumentTypes:{
            type: Object,
            default: () => ({}),
        },
        departments: {
            type: Object,
            default: () => ({}),
        },
        noteSeries: {
            type: Object,
            default: () => ({}),
        },
        taxes: {
            type: Object,
            default: () => ({}),
        }
    });

    const formSeDoc = ref({
        serie: 1000,
        number: null,
        loading: false
    });

    const formNote = useForm({
        document_id: null,
        document_serie: null,
        document_number: null,
        document_client: null,
        document_client_type: null,
        document_client_ruc: null,
        document_client_address: null,
        document_client_email: null,
        document_sale_id: null,
        document_items: [],
        document_mto_taxed: null,
        document_mto_igv: null,
        document_mto_discount: null,
        document_mto_total: null,
        document_total_taxes: null,
        document_overall_total: null,
        note_type: 3,
        note_operation_type: null,
        note_id: null,
        note_serie: null,
        note_issue_date: null,
        note_due_date: null,
        note_reason_cancellation: null,
        note_overall_total: null,
        note_discount_total: null,
        note_mto_taxed: null,
        note_total_igv: null
    });

    const submitFormSearch = () => {
        formSeDoc.value.loading = true;
        axios.post(route('sale_credit_notes_search_invoice'), formSeDoc.value).then((res) => {
            if(res.data.success){
                formNote.document_id = res.data.document.id;
                formNote.document_client = res.data.document.client_number + ' ' +res.data.document.client_rzn_social;
                formSeDoc.value.loading = false;
                formNote.document_sale_id = res.data.document.sale_id;
                formNote.document_items = res.data.document.items;
                formNote.document_mto_taxed = res.data.document.invoice_mto_oper_taxed;
                formNote.document_mto_igv = res.data.document.invoice_mto_igv;
                formNote.document_mto_discount = res.data.document.invoice_mto_discount;
                formNote.document_mto_total = res.data.document.invoice_mto_imp_sale;
                formNote.document_serie = res.data.document.invoice_serie;
                formNote.document_number = res.data.document.invoice_correlative;
                formNote.document_client_ruc = res.data.document.client_number;
                formNote.document_client_type = res.data.document.client_type_doc;
                formNote.document_client_address = res.data.document.client_address;
                formNote.document_client_email = res.data.document.client_email;
                formNote.document_total_taxes = res.data.document.invoice_total_taxes;
                formNote.document_overall_total = res.data.document.overall_total;
                formNote.note_overall_total = res.data.document.invoice_mto_imp_sale;
                formNote.note_discount_total = res.data.document.invoice_mto_discount;
                formNote.note_mto_taxed = res.data.document.invoice_mto_oper_taxed;
                formNote.note_total_igv = res.data.document.invoice_mto_igv;
            }else{
                showMessage('No se encontró el documento','error');
            }
        });
    }

    const showMessage = (msg = '', type = 'success') => {
        const toast = Swal.mixin({
            toast: true,
            position: 'top',
            showConfirmButton: false,
            timer: 3000,
            customClass: { container: 'toast' },
        });
        toast.fire({
            icon: type,
            title: msg,
            padding: '10px 20px',
        });
    };

    const dataTypeNote = ref([]);
    const dataNoteSeries = ref([]);

    const selectTypeNote = () => {
        dataTypeNote.value = [];
        if(formNote.note_type == 3){
            dataTypeNote.value = props.typeCreditNote;
        }else{
            dataTypeNote.value = props.typeDebitNote;
        }
        getSeriesByDocumentType();
    }

    const fecha = new Date();
    const fechaLima = fecha.toLocaleDateString("es-PE", { timeZone: "America/Lima" });

    onMounted(() => {
        selectTypeNote();
        dataNoteSeries.value = props.noteSeries;
        formNote.note_issue_date = fechaLima;
        startTaxes();
    });

    const getSeriesByDocumentType = () => {
        let did = formNote.note_type;
        axios.get(route('sale_document_series',did)).then((res) => {
            if (res.data.status) {
                dataNoteSeries.value = res.data.series;
                formNote.note_serie = dataNoteSeries.value[0].id;
            } else {
                Swal.fire({
                    title: 'Información Importante',
                    text: 'No existe serie para este local o tipo de documento',
                    icon: 'info',
                    showDenyButton: true,
                    showCancelButton: false,
                    confirmButtonText: 'Ok',
                    denyButtonText: `Crear serie`,
                    padding: '2em',
                    customClass: 'sweet-alerts',
                }).then((result) => {
                    if (result.isDenied) {
                        router.visit(route('establishments.index'),{
                            method: 'get'
                        });
                    }
                })
            }
        });
    }

    const basic = ref({
        dateFormat: 'd/m/Y'
    });

    const storeNote = () => {

        // Send a POST request

        if(!formNote.note_operation_type || formNote.note_operation_type.trim() === ""){
            if(formNote.note_type == 3){
                showMessage('El tipo nota de crédito es obligatorio','error');
                formNote.setError('note_operation_type','El tipo nota de crédito es obligatorio');
            }else{
                showMessage('El tipo nota de debito es obligatorio','error');
                formNote.setError('note_operation_type','El tipo nota de debito es obligatorio');
            }
        }else{
            if(!formNote.note_reason_cancellation || formNote.note_reason_cancellation.trim() === ""){
                showMessage('La descripción es obligatorio','error');
                formNote.setError('note_reason_cancellation','La descripción es obligatorio');
            }else{
                let okey = false;
                let msg = null;
                if(formNote.note_type == 3){
                    okey = true;
                }else if(formNote.note_type == 4){
                    console.log('nota',formNote.note_overall_total)
                    console.log('factura',formNote.document_mto_total)
                    if(parseFloat(formNote.note_overall_total) > parseFloat(formNote.document_mto_total)){
                        okey = true;
                    }else{
                        okey = false;
                        msg = 'El monto total de la nota de débito debe ser mayor al de la factura referenciada.'
                    }
                }
                if(okey){
                    formNote.processing = true;
                    axios({
                        method: 'post',
                        url: route('sale_all_notes_store'),
                        data: formNote
                    }).then((response) => {
                        console.log('resp',response)
                        console.log('data',response.data)
                        if(response.data){
                            let iconMessage = 'success';

                            if (response.data.success) {
                                iconMessage = 'success';
                            }else{
                                iconMessage = 'error';
                            }

                            var cadena = "";
                            if(response.data.notes){
                                let array = JSON.parse(response.data.notes);
                                for (var i = 0; i < array.length; i++) {
                                    cadena += array[i] + "<br>";
                                }
                            }

                            Swal.fire({
                                title: `${response.data.message}`,
                                html: `${cadena}`,
                                icon: iconMessage,
                                padding: '2em',
                                customClass: 'sweet-alerts',
                            }).then(() => {
                                router.visit(route('sale_credit_notes_list'),{
                                    method: 'get'
                                });
                            });
                        }
                    }).catch((error) => {
                        console.log(error)
                        // let validationErrors = error.response.data.errors;
                        // console.log(validationErrors)
                        // if (validationErrors && validationErrors.payments) {
                        //     const paymentsErrors = validationErrors.payments;
                        //     for (let i = 0; i < paymentsErrors.length; i++) {
                        //         form.setError('payments.'+index+'.amount', paymentsErrors[i]);
                        //     }
                        // }
                    }).finally(() => {
                        formNote.processing = false;
                    });
                }else{
                    showMessage(msg,'error');
                }
            }
        }

    }

    const backList = () =>{
        router.visit(route('sale_credit_notes_list'),{
            method: 'get'
        });
    }

    const taxes = ref({});
    const startTaxes = () => {

        let igv = parseFloat(props.taxes.igv);
        let icbper = parseFloat(props.taxes.icbper);

        let xa = {
            nfactorIGV: (igv / 100) + 1,
            rfactorIGV: (igv / 100),
            icbper: icbper
        }
        taxes.value = xa;
    }

    const calculateTotals = (key) => {
        let c = parseFloat(formNote.document_items[key].quantity) ?? 0;
        let p = parseFloat(formNote.document_items[key].price_sale) ?? 0;
        let d = parseFloat(formNote.document_items[key].descuento) ?? 0;

        let vu = p / taxes.value.nfactorIGV; //valor unitario
        let fa = ((d * 100) / p) / 100; //factor del descuento
        let md = fa * vu * c; //monto del descuento
        let bi = (vu * c) - md; //base igv
        let mi = bi * taxes.value.rfactorIGV; //total igv por item
        let st = ((vu * c) - md) + mi;
        let vs = (vu * c) - md;
        // Verificar si el resultado es NaN y asignar 0 en su lugar
        if (isNaN(st)) {
            st = 0;
        }
        if (isNaN(mi)) {
            mi = 0;
        }
        if (isNaN(vs)) {
            vs = 0;
        }

        formNote.document_items[key].igv = mi.toFixed(2);
        formNote.document_items[key].mto_total = st.toFixed(2);
        formNote.document_items[key].mto_value_unit = vs.toFixed(2);

        // Calcular la suma de los totales de todos los items
        formNote.note_overall_total = formNote.document_items.reduce((acc, item) => acc + parseFloat(item.mto_total), 0).toFixed(2);
        formNote.note_discount_total = formNote.document_items.reduce((acc, item) => acc + (parseFloat(item.mto_discount)*c), 0).toFixed(2);
        formNote.note_mto_taxed = formNote.document_items.reduce((acc, item) => acc + parseFloat(item.mto_value_unit), 0).toFixed(2);
        formNote.note_total_igv = formNote.document_items.reduce((acc, item) => acc + parseFloat(item.igv), 0).toFixed(2);

    }
</script>
<template>
    <AppLayout title="Punto de Ventas">
        <Navigation :routeModule="route('sales_dashboard')" :titleModule="'Facturación Electrónica'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Crear Nota</span>
            </li>
        </Navigation>
        <div class="mt-5 space-y-6">
            <div class="panel flex items-center overflow-x-auto whitespace-nowrap p-3 text-primary">
                <div class="rounded-full p-1.5 text-blue-600 ring-2 ring-primary/30 ltr:mr-3 rtl:ml-3">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512">
                        <path fill="currentColor" d="M64 0C28.7 0 0 28.7 0 64L0 448c0 35.3 28.7 64 64 64l256 0c35.3 0 64-28.7 64-64l0-288-128 0c-17.7 0-32-14.3-32-32L224 0 64 0zM256 0l0 128 128 0L256 0zM80 64l64 0c8.8 0 16 7.2 16 16s-7.2 16-16 16L80 96c-8.8 0-16-7.2-16-16s7.2-16 16-16zm0 64l64 0c8.8 0 16 7.2 16 16s-7.2 16-16 16l-64 0c-8.8 0-16-7.2-16-16s7.2-16 16-16zm16 96l192 0c17.7 0 32 14.3 32 32l0 64c0 17.7-14.3 32-32 32L96 352c-17.7 0-32-14.3-32-32l0-64c0-17.7 14.3-32 32-32zm0 32l0 64 192 0 0-64L96 256zM240 416l64 0c8.8 0 16 7.2 16 16s-7.2 16-16 16l-64 0c-8.8 0-16-7.2-16-16s7.2-16 16-16z"/>
                    </svg>
                </div>
                <span class="ltr:mr-3 rtl:ml-3">Documento: </span>
                <form @submit.prevent="submitFormSearch">
                    <div class="flex flex-col md:flex-row gap-4 items-center max-w-[900px] mx-auto">
                        <select v-model="formSeDoc.serie" id="ctnSelect1" class="form-select text-white-dark flex-1" required>
                            <option value="1000">Seleccionar serie</option>
                            <template v-for="(serie, index) in series">
                                <option :value="serie.id">{{ serie.description }}</option>
                            </template>
                        </select>
                        <input v-model="formSeDoc.number" id="facNumber" type="text" class="form-input flex-1" placeholder="Numero" />
                        <button type="submit" class="btn btn-primary text-xs uppercase">BUSCAR</button>
                    </div>
                </form>
            </div>
            <div v-if="formNote.document_id" class="panel">
                <div class="space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <div class="col-span-4 sm:col-span-1">
                            <InputLabel value="Tipo comprobante" />
                            <select  @change="selectTypeNote" v-model="formNote.note_type" id="ctnSelect1" class="form-select flex-1" required>
                                <template v-for="(dtype, index) in saleDocumentTypes">
                                    <option :value="dtype.id">{{ dtype.description }}</option>
                                </template>
                            </select>
                        </div>
                        <div class="col-span-4 sm:col-span-1">
                            <InputLabel for="note_serie" value="Serie" />
                            <select v-model="formNote.note_serie" id="note_serie" class="form-select flex-1" required>
                                <option :value="null">Seleccionar</option>
                                <template v-for="serie in dataNoteSeries">
                                    <option :value="serie.id">{{ serie.description }}</option>
                                </template>
                            </select>
                        </div>
                        <div class="col-span-4 sm:col-span-1">
                            <InputLabel for="note_operation_type" v-if="formNote.note_type == 3" value="Tipo nota de crédito" />
                            <InputLabel for="note_operation_type" v-else value="Tipo nota de débito" />
                            <select v-model="formNote.note_operation_type" id="note_operation_type" class="form-select flex-1" required>
                                <option :value="null">Seleccionar</option>
                                <template v-for="dtypeNote in dataTypeNote">
                                    <option :value="dtypeNote.id">{{ dtypeNote.description }}</option>
                                </template>
                            </select>
                            <InputError :message="formNote.errors.note_operation_type" class="mt-2" />
                        </div>
                        <div class="col-span-4 sm:col-span-1">
                            <InputLabel for="note_issue_date" value="Fec. Emisión" />
                            <flat-pickr v-model="formNote.note_issue_date" id="note_issue_date" class="form-input" :config="basic"></flat-pickr>

                        </div>
                        <div class="col-span-4 sm:col-span-2">
                            <label for="client_name">Cliente</label>
                            <input id="client_name" type="text" :value="formNote.document_client" class="form-input disabled:pointer-events-none disabled:bg-[#eee] dark:disabled:bg-[#1b2e4b] cursor-not-allowed" disabled />
                        </div>
                        <div class="col-span-4 sm:col-span-2">
                            <label for="reason_cancellation">Descripción</label>
                            <input id="reason_cancellation" type="text" v-model="formNote.note_reason_cancellation" class="form-input"  />
                            <InputError :message="formNote.errors.note_reason_cancellation" class="mt-2" />
                        </div>

                        <div class="col-span-4">
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th class="text-center">Acciones</th>
                                            <th>Descripción</th>
                                            <th class="text-center">Unidad</th>
                                            <th class="text-center">Cantidad</th>
                                            <th class="text-center">Precio Unitario</th>
                                            <th class="text-center">Descuento</th>
                                            <th class="text-center">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template v-for="(item, key) in formNote.document_items">
                                            <tr class="bg-dark-dark-light border-dark-dark-light">
                                                <td>
                                                    <div class="flex gap-4 items-center justify-center">
                                                        <button @click="item.editar = !item.editar" type="button" class="btn btn-sm btn-outline-primary text-xs">
                                                            <icon-pencil class="w-4 h-4" />
                                                        </button>
                                                    </div>
                                                </td>
                                                <td>
                                                   <div v-if="item.editar" class="flex items-center space-x-2 justify-between">
                                                        <span>{{ item.cod_product }}</span>
                                                        <textarea v-model="item.decription_product"
                                                            :ref="'item-description-' + key"
                                                            class="form-textarea form-input-sm">
                                                        </textarea>
                                                    </div>
                                                    <p v-else>
                                                        {{ item.cod_product + ' - ' + item.decription_product }}
                                                    </p>
                                                </td>
                                                <td class="text-center">
                                                    <template v-if="item.editar">
                                                        <select v-model="item.unit_type" class="form-select form-select-sm">
                                                            <template v-for="(unitType) in unitTypes">
                                                                <option :value="unitType.id" >{{ unitType.description }}</option>
                                                            </template>
                                                        </select>
                                                    </template>
                                                    <p v-else>{{ item.unit_type }}</p>
                                                </td>
                                                <td class="text-center">
                                                    <input
                                                        v-if="item.editar"
                                                        v-model="item.quantity"
                                                        class="form-input form-input-sm"
                                                        @input="calculateTotals(key)"
                                                    />
                                                    <p v-else>{{ item.quantity }}</p>
                                                </td>
                                                <td class="text-right">
                                                    <input
                                                        v-if="item.editar"
                                                        v-model="item.price_sale"
                                                        class="form-input form-input-sm"
                                                        @input="calculateTotals(key)"
                                                    />
                                                    <p v-else>{{ item.price_sale }}</p>
                                                </td>
                                                <td class="text-right">
                                                    <input
                                                        v-if="item.editar"
                                                        v-model="item.descuento"
                                                        class="form-input form-input-sm"
                                                        @input="calculateTotals(key)"
                                                    />
                                                    <p v-else>{{ item.descuento }}</p>
                                                </td>
                                                <td class="text-right">
                                                    {{ item.mto_total }}
                                                </td>
                                            </tr>
                                        </template>

                                        <tr class="border-none">
                                            <td colspan="4"></td>
                                            <td colspan="2" class="text-right text-xs uppercase border-b"><b>OP. GRAVADAS: S/</b></td>
                                            <td class="text-right border-b">{{ formNote.note_mto_taxed }}</td>
                                        </tr>
                                        <tr v-if="formNote.note_discount_total" class="border-none">
                                            <td colspan="4"></td>
                                            <td colspan="2" class="text-right text-xs uppercase border-b"><b>descuento: S/</b></td>
                                            <td class="text-right border-b">{{ formNote.note_discount_total ?? 0 }}</td>
                                        </tr>
                                        <tr class="border-none">
                                            <td colspan="4"></td>
                                            <td colspan="2" class="text-right text-xs uppercase border-b"><b>IGV: S/</b></td>
                                            <td class="text-right border-b">{{ formNote.note_total_igv }}</td>
                                        </tr>
                                        <tr class="border-none">
                                            <td colspan="4"></td>
                                            <td colspan="2" class="text-right text-xs uppercase"><b>TOTAL A PAGAR: S/</b></td>
                                            <td class="text-right font-bold">
                                                {{ formNote.note_overall_total }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-4 gap-4">
                        <div class="col-span-4 sm:col-span-3">
                        </div>
                        <div class="col-span-4 sm:col-span-1 space-y-2">
                            <PrimaryButton @click="storeNote" :class="{ 'opacity-25': formNote.processing }" :disabled="formNote.processing" class="w-full" type="button">
                                <svg v-show="formNote.processing" aria-hidden="true" role="status" class="inline w-4 h-4 mr-3 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                                    <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                                </svg>
                                Generar
                            </PrimaryButton>
                            <DangerButton @click="backList" class="w-full">
                                Cancelar
                            </DangerButton>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
