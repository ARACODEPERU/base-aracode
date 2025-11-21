<script setup>
    import { ref, onMounted, watch } from 'vue';
    import InputError from "@/Components/InputError.vue";
    import IconX from '@/Components/vristo/icon/icon-x.vue';
    import IconSave from '@/Components/vristo/icon/icon-save.vue';
    import { useForm, Link, router } from "@inertiajs/vue3";
    import Swal2 from 'sweetalert2';
    import { faMagnifyingGlass } from "@fortawesome/free-solid-svg-icons";
    import SearchClients from './Partials/SearchClients.vue';
    import ModalLarge from '@/Components/ModalLarge.vue';
    import TextInput from '@/Components/TextInput.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import { useAppStore } from '@/stores/index';
    import { calcularMontosPorCuota } from 'Modules/Sales/Resources/assets/js/utilities/paymentCalculations';
    import SuccessButton from '@/Components/SuccessButton.vue';

    const store = useAppStore();

    const props = defineProps({
        saleNote: {
            type: Object,
            default: () => ({}),
        },
        payments: {
            type: Object,
            default: () => ({}),
        },
        saleDocumentTypes: {
            type: Object,
            default: () => ({}),
        },
        taxes: {
            type: Object,
            default: () => ({}),
        },
        standardIdentityDocument: {
            type: Object,
            default: () => ({}),
        },
        departments: {
            type: Object,
            default: () => ({}),
        },
        student: {
            type: Object,
            default: () => ({}),
        }
    });


    const series = ref([]);

    const form = useForm({
        sale_id: props.saleNote.id,
        client_id: props.saleNote.client.id,
        client_name: props.saleNote.client.number+"-"+props.saleNote.client.full_name,
        client_rzn_social: props.saleNote.invoice_type == 1 ? props.saleNote.invoice_razon_social : props.saleNote.client.full_name,
        client_ubigeo: props.saleNote.client.ubigeo,
        client_dti: props.saleNote.invoice_type == 1 ? 6 : props.saleNote.client.document_type_id,
        client_number: props.saleNote.invoice_type == 1 ? props.saleNote.invoice_ruc : props.saleNote.client.number,
        client_ubigeo_description: props.saleNote.client.ubigeo_description,
        client_direction: props.saleNote.invoice_type == 1 ? props.saleNote.invoice_direccion : props.saleNote.client.address,
        client_phone: props.saleNote.client.telephone,
        client_email: props.saleNote.client.email,
        sale_documenttype_id: props.saleNote.invoice_type,
        student_id: props.student.id,
        serie: null,
        number: null,
        payments: [
            {
                type: 1,
                reference: null,
                amount: 0
            }
        ],
        additional_Information: null,
        date_end: null,
        date_issue: null,
        items:[],
        total_discount: 0,
        total_igv: 0,
        percentage_igv: 0,
        total: 0,
        total_taxed: 0,
        additional_description: null,
        forma_pago: 'Contado',
        quotas: {
            number: 1,
            days: 15,
            amounts: [],
            end_month: false,
            amount: null
        },
        nextPaymentDate: null
    });

    const getSeriesByDocumentType = () => {
        let did = form.sale_documenttype_id;
        axios.get(route('sale_document_series',did)).then((res) => {
            if (res.data.status) {
                series.value = res.data.series;
                form.serie = series.value[0].id;
            } else {
                Swal2.fire({
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

    const addItem = (data) => {
        // 1 = servicio 2 = course
        // console.log(JSON.parse(data.saleProduct));
        let price = JSON.parse(data.saleProduct).price;
        let is_product = false;
        let description = JSON.parse(data.saleProduct).description;
        let xItemId = JSON.parse(data.saleProduct).id
        let unitType = 'ZZ';

        const ddata = {
            id: xItemId,
            mode: data.entity_name_product = 'Modules\Academic\Entities\AcaSubscriptionType' ? 4 : 3 ,
            title: description,
            description: description,
            rate: 0,
            quantity: 1,
            actualPrice: price,
            amount: price - data.advancement,
            discount: 0,
            m_igv: 0,
            total: 0,
            v_sale: 0,
            afe_igv: 10,
            icbper: false,
            is_product: is_product,
            originId: data.id,
            unit_type: unitType,
            force_unlimited: false,
            advancement: data.advancement
        };

        calculateTotals(ddata)
    };

    const removeItem = (data, index) => {
        removeCalculateTotals(index);
        if(data.mode == 3){
            let cbx = document.getElementById('regCou_checkbox-' + data.originId);
            cbx.disabled = false
            cbx.checked = false;
        }else{
            let cbx = document.getElementById('subs_checkbox-' + data.originId);
            cbx.disabled = false
            cbx.checked = false;
        }

        form.items.splice(index,1);
    };
    const xasset = assetUrl;

    const addPayment = () => {
        let ar = {
            type: 1,
            reference: null,
            amount: 0
        };
        form.payments.push(ar);
    };

    const removePayment = (index) => {
        if(index>0){
            form.payments.splice(index,1);
        }
    };

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

    const getCurrentDate = () => {
        const currentDate = new Date();
        const year = currentDate.getFullYear();
        const month = String(currentDate.getMonth() + 1).padStart(2, '0'); // Los meses son base 0, por eso se suma 1
        const day = String(currentDate.getDate()).padStart(2, '0');

        // Formato de fecha: YYYY-MM-DD
        form.date_issue = `${year}-${month}-${day}`;
        form.date_end = `${year}-${month}-${day}`;

    };

    const calculateTotals = (data) => {

        let c = parseFloat(data.quantity) ?? 0;
        let p = parseFloat(data.amount) ?? 0;
        let d = parseFloat(data.discount) ?? 0;

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
        if (isNaN(md)) {
            md = 0;
        }

        data.m_igv = mi.toFixed(2);
        data.total = st.toFixed(2);
        data.v_sale = vs.toFixed(2);

        let tt = parseFloat(form.total) + st;
        let td = parseFloat(form.total_discount) + md;
        let tx = parseFloat(form.total_taxed) + vs;
        let ti = parseFloat(form.total_igv) + mi;
        // Calcular la suma de los totales de todos los items

        form.total = tt.toFixed(2);
        form.total_discount = td.toFixed(2);
        form.total_taxed = tx.toFixed(2);
        form.total_igv = ti.toFixed(2);
        form.items.push(data);
        form.payments[0].amount = form.total;
    }

    const calculateTotalsInputs = (key) => {

        let c = parseFloat(form.items[key].quantity) ?? 0;
        let p = parseFloat(form.items[key].amount) ?? 0;
        let d = parseFloat(form.items[key].discount) ?? 0;

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

        form.items[key].m_igv = mi.toFixed(2);
        form.items[key].total = st.toFixed(2);
        form.items[key].v_sale = vs.toFixed(2);

        // Calcular la suma de los totales de todos los items
        form.total = form.items.reduce((acc, item) => acc + parseFloat(item.total), 0).toFixed(2);
        form.total_discount = form.items.reduce((acc, item) => acc + (parseFloat(item.discount)*c), 0).toFixed(2);
        form.total_taxed = form.items.reduce((acc, item) => acc + parseFloat(item.v_sale), 0).toFixed(2);
        form.total_igv = form.items.reduce((acc, item) => acc + parseFloat(item.m_igv), 0).toFixed(2);
        form.payments[0].amount = form.total;
    }

    const removeCalculateTotals = (key) => {

        form.total = (parseFloat(form.total) - parseFloat(form.items[key].total)).toFixed(2);
        form.total_discount = (parseFloat(form.total_discount) - parseFloat(form.items[key].discount)).toFixed(2);
        form.total_taxed = (parseFloat(form.total_taxed) - parseFloat(form.items[key].v_sale)).toFixed(2);
        form.total_igv = (parseFloat(form.total_igv) - parseFloat(form.items[key].m_igv)).toFixed(2);
        form.payments[0].amount = form.total;

    }

    const saveDocument = () => {

        form.processing = true

        if(form.serie){
            if(form.client_dti != 6 && form.sale_documenttype_id == 1){
                Swal2.fire({
                    title: 'Información Importante',
                    text: "El cliente debe tener ruc para emitir una factura",
                    icon: 'error',
                });
                form.processing = false
                return;

            }

            axios.put(route('aca_student_space_sales_store', form.sale_id), form ).then((res) => {
                form.sale_documenttype_id = 2,
                form.type_operation = props.type_operation,
                form.serie = null
                form.items = [];
                form.total_discount = 0;
                form.total_igv = 0;
                form.total = 0;
                form.total_taxed = 0;
                form.payments = [{
                    type:1,
                    reference: null,
                    amount:0
                }];
                form.processing =  false;
                form.forma_pago = 'Contado;'
                Swal2.fire({
                    title: 'Comprobante creado con éxito',
                    text: "¿deseas enviar a sunat?",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Enviar Ahora',
                    cancelButtonText: 'Cerrar',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                }).then((result) => {
                    if (result.isConfirmed) {
                        if(res.data.invoice_type_doc == '01'){
                            sendSunatDocumentCreated(res.data)
                        }else{
                            Swal2.fire({
                                title: 'Información Importante',
                                text: "Las boletas se envian mediante un resumen",
                                icon: 'info',
                                padding: '2em',
                                customClass: 'sweet-alerts',
                            });
                        }
                    } else if (result.isDenied) {
                        downloadDocument(res.data.id,res.data.invoice_type_doc,'PDF')
                    }
                });
            }).catch(function (error) {
                setFormError(error);
            });
        }else{
            Swal2.fire({
                title: 'Información Importante',
                text: "Elejir serie de documento",
                icon: 'error',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
            form.processing = false
            return;
        }
    }

    const setFormError = (error) => {
        let validationErrors = error.response.data.errors;

        if(error.status == 422 && validationErrors){
            if (validationErrors.corelative) {
                const corelativeErrors = validationErrors.corelative;
                for (let i = 0; i < corelativeErrors.length; i++) {
                    form.setError('corelative', corelativeErrors[i]);
                }
            }
            if (validationErrors.serie) {
                const serieErrors = validationErrors.serie;
                for (let i = 0; i < serieErrors.length; i++) {
                    form.setError('serie', serieErrors[i]);
                }
            }
            if (validationErrors.client_number) {
                const client_numberErrors = validationErrors.client_number;
                for (let i = 0; i < client_numberErrors.length; i++) {
                    form.setError('client_number', client_numberErrors[i]);
                }
            }
            if (validationErrors.total) {
                const totalErrors = validationErrors.total;
                for (let i = 0; i < totalErrors.length; i++) {
                    form.setError('total', totalErrors[i]);
                }
            }

            if (validationErrors.client_rzn_social) {
                const client_rzn_socialErrors = validationErrors.client_rzn_social;
                for (let i = 0; i < client_rzn_socialErrors.length; i++) {
                    form.setError('client_rzn_social', client_rzn_socialErrors[i]);
                }
            }

            if (validationErrors.items) {
                const itemsErrors = validationErrors.items;

                for (let i = 0; i < itemsErrors.length; i++) {
                    form.setError('items.'+index+'.quantity', itemsErrors[i]);
                }
            }
            if (validationErrors.payments) {
                const paymentsErrors = validationErrors.payments;

                for (let i = 0; i < paymentsErrors.length; i++) {
                    form.setError('payments.'+index+'.amount', paymentsErrors[i]);
                }
            }
            if (validationErrors.date_issue) {
                const dateissueErrors = validationErrors.date_issue;

                for (let i = 0; i < dateissueErrors.length; i++) {
                    form.setError('date_issue', dateissueErrors[i]);
                }
            }
            if (validationErrors.date_end) {
                const dateEndErrors = validationErrors.date_end;

                for (let i = 0; i < dateEndErrors.length; i++) {
                    form.setError('date_end', dateEndErrors[i]);
                }
            }

            showAlertMessage('Faltan ingresar datos importantes','error');
        }else if(error.status == 500){
            showAlertMessage(error.message,'error');
        }
        form.processing =  false;
    }

    const showAlertMessage = async (msg, xicon = 'success') => {

        const toast = Swal2.mixin({
            toast: true,
            position: 'top',
            showConfirmButton: false,
            timer: 5000,
        });
        toast.fire({
            icon: xicon,
            title: msg,
            padding: '10px 20px',
        });
    }

    const downloadDocument = (id,type,file) => {
        let url = route('saledocuments_download',[id, type,file])
        window.open(url, "_blank");
    }

    const isEdit = ref(false);

    const displayModalClientSearch = ref(false);
    const saleDocumentTypesId = ref({});

    const openModalClientSearch = () => {
        displayModalClientSearch.value = true;
        saleDocumentTypesId.value = form.sale_documenttype_id
    }
    const closeModalClientSearch = () => {
        saleDocumentTypesId.value =  null;
        displayModalClientSearch.value = false;
    }

    const getDataClient = async (data) => {
        if(form.sale_documenttype_id == 2){
            //form.client_id = data.id;
            form.client_name = data.number+"-"+data.full_name;
            form.client_rzn_social = data.full_name;
            form.client_ubigeo_description = data.city;
            form.client_ubigeo = data.ubigeo;
            form.client_direction = data.address;
            form.client_dti = data.document_type_id;
            form.client_number = data.number;
            form.client_phone = data.telephone;
            form.client_email = data.email;
        }else{
            if(data.document_type_id == '6'){
                //form.client_id = data.id;
                form.client_name = data.number+"-"+data.full_name;
                form.client_ubigeo_description = data.city;
                form.client_ubigeo = data.ubigeo;
                form.client_direction = data.address;
                form.client_dti = data.document_type_id;
                form.client_number = data.number;
                form.client_phone = data.telephone;
                form.client_email = data.email;
                form.client_rzn_social = data.full_name;
            }else{
                Swal2.fire({
                    title: 'Información Importante',
                    text: "El cliente no cuenta con ruc para emitir una factura",
                    icon: 'info',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
            }
        }
        displayModalClientSearch.value = false;

    }

    const sendSunatDocumentCreated = (document) => {
        Swal2.fire({
            title: document.invoice_serie+'-'+document.number,
            text: 'Enviar documento',
            showCancelButton: true,
            confirmButtonText: 'Enviar',
            showLoaderOnConfirm: true,
            clickOutside: false,
            padding: '2em',
            customClass: 'sweet-alerts',
            preConfirm: () => {
                return axios.get(route('saledocuments_send', [document.id,document.invoice_type_doc])).then((res) => {
                    if (!res.data.success) {
                        var cadena = `Error código: ${res.data.code}<br>Descripción:${res.data.message}`;
                        let notes = res.data.notes;
                        if (notes) {
                            cadena += `<br>Nota: ${notes}`;
                        }
                        Swal2.showValidationMessage(cadena)
                        router.visit(route('aca_student_space_sales_list',props.student.id), {
                            replace: true
                        });
                    }
                    return res
                });
            },
            allowOutsideClick: () => !Swal2.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                var cadena = "";
                let array = JSON.parse(result.value.data.notes);
                for (var i = 0; i < array.length; i++) {
                    cadena += array[i] + "<br>";
                }

                Swal2.fire({
                    title: `${result.value.data.message}`,
                    html: `${cadena}`,
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                }).then(() => {
                    router.visit(route('aca_student_space_sales_list',props.student.id), {
                        replace: true
                    });
                });

            }
        });
    }

     // Watcher para recalcular las cuotas cuando cambian las dependencias
    watch(() => [
        form.total,
        form.quotas.number,
        form.quotas.end_month,
        form.quotas.days,
        form.date_issue,
        form.forma_pago
    ], ([newTotal, newNumber, newEndMonth, newDays, newDateIssue, newFormaPago]) => {
        if (newFormaPago === 'Credito') {
            form.quotas.amounts = calcularMontosPorCuota(
                newTotal,
                newNumber,
                newDateIssue,
                newEndMonth,
                newDays
            );
        } else {
            form.quotas.amounts = []; // Limpiar cuotas si no es a crédito
        }
    }, { immediate: true }); // 'immediate: true' para que se ejecute la primera vez al montar el componente

    const clientDefault = ref(null);

    onMounted(() => {
        getSeriesByDocumentType();
        getCurrentDate();
        startTaxes();
        let xProducts = props.saleNote.sale_product;
        xProducts.forEach(product => {
            addItem(product, 4);
        });
    });

    const closeMyPopup = () => {
        router.visit(route('aca_student_space_sales_list',props.student.id), {
            replace: true
        });
        window.close();
    }
</script>

<template>
    <div class="px-16 py-8">
        <div class="flex xl:flex-row flex-col">
            <div class="panel px-0 flex-1 py-6 ltr:xl:mr-6 rtl:xl:ml-6">
                <div class="px-4">
                    <div class="space-y-4">
                        <div class="items-center">
                            <!-- <label for="sale_documenttype_id" class="sm:col-span-1">Tipo </label> -->
                            <div >
                                <select @change="getSeriesByDocumentType" v-model="form.sale_documenttype_id" id="sale_documenttype_id" class="w-full appearance-none text-3xl rounded-xl text-center font-extrabold text-blue-800 border-4  py-6 px-4 bg-green-100">
                                    <option v-for="(type, index) in saleDocumentTypes" :value="type.id"> {{  type.description  }}</option>
                                </select>
                                <div class="flex-1 ">
                                    <InputError :message="form.errors.sale_documenttype_id" class="mt-2" />
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-4 items-center">
                            <label for="serie" class="sm:col-span-1">Serie </label>
                            <div class="sm:col-span-2">
                                <select @change="getSeriesByDocumentType" v-model="form.serie" id="serie" class="form-select text-white-dark">
                                    <option v-for="(serie, index) in series" :value="serie.id"> {{  serie.description  }}</option>
                                </select>
                                <div class="flex-1 ">
                                    <InputError :message="form.errors.serie" class="mt-2" />
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-4 items-center">
                            <label for="startDate" class="sm:col-span-1">fecha de emisión</label>
                            <div class="sm:col-span-2">
                                <input id="startDate" type="date" name="inv-date" class="form-input" v-model="form.date_issue" />
                                <InputError :message="form.errors.date_issue" class="mt-2" />
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-4 items-center">
                            <label for="dueDate" class="sm:col-span-1">Fecha de vencimiento</label>
                            <div class="sm:col-span-2">

                                <input id="dueDate" type="date" name="due-date" class="form-input" v-model="form.date_end" />
                                <InputError :message="form.errors.date_end" class="mt-2" />

                            </div>
                        </div>
                    </div>
                </div>
                <hr class="border-[#e0e6ed] dark:border-[#1b2e4b] my-6" />
                <div class="mt-8 px-4">
                    <div class="flex justify-between items-center">
                        <div class="w-full ltr:lg:mr-6 rtl:lg:ml-6 mb-6">
                            <div class="text-xl">Cliente</div>
                        </div>
                        <button @click="openModalClientSearch" type="button" class="btn btn-danger btn-sm text-xs uppercase">BUSCAR</button>
                        <SearchClients
                            :display="displayModalClientSearch"
                            :closeModalClient="closeModalClientSearch"
                            @clientId="getDataClient"
                            :clientDefault="clientDefault"
                            :documentTypes="standardIdentityDocument"
                            :saleDocumentTypes="saleDocumentTypesId"
                            :ubigeo="departments"
                        />
                    </div>
                    <div class="grid sm:grid-cols-2 gap-6">
                        <div class="">
                            <div class="mt-4 flex items-center">
                                <label for="client_number" class="ltr:mr-2 rtl:ml-2 w-1/3 mb-0">Tipo identificacion</label>
                                <div class="flex-1 ">
                                    <select v-model="form.client_dti" class="form-select form-select-sm">
                                        <template v-for="(typ, kio) in standardIdentityDocument">
                                            <option :value="typ.id" :selected="typ.id == saleNote.client.document_type_id">{{ typ.description }}</option>
                                        </template>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-4 flex items-center">
                                <label for="client_name" class="ltr:mr-2 rtl:ml-2 w-1/3 mb-0">Nombre</label>
                                <div class="flex-1 ">
                                    <input
                                        id="client_name"
                                        type="text"
                                        name="client_name"
                                        class="form-input form-input-sm"
                                        v-model="form.client_rzn_social"
                                    />
                                    <InputError :message="form.errors.client_rzn_social" class="mt-2" />
                                </div>
                            </div>
                            <div class="mt-4 flex items-center">
                                <label for="client_direction" class="ltr:mr-2 rtl:ml-2 w-1/3 mb-0">Dirección</label>
                                <input
                                    id="client_direction"
                                    type="text"
                                    name="client_direction"
                                    class="form-input flex-1 form-input-sm"
                                    v-model="form.client_direction"
                                />
                            </div>

                        </div>
                        <div class="">
                            <div class="mt-4 flex items-center">
                                <label for="client_number" class="ltr:mr-2 rtl:ml-2 w-1/3 mb-0">Num. identificación</label>
                                <div class="flex-1">
                                    <input
                                        id="client_number"
                                        type="text"
                                        name="client_number"
                                        class="form-input form-input-sm"
                                        v-model="form.client_number"
                                    />
                                    <InputError :message="form.errors.client_rzn_social" class="mt-2" />
                                </div>
                            </div>
                            <div class="mt-4 flex items-center">
                                <label for="client_email" class="ltr:mr-2 rtl:ml-2 w-1/3 mb-0">Email</label>
                                <input
                                    id="client_email"
                                    type="email"
                                    name="client_email"
                                    class="form-input flex-1 form-input-sm"
                                    v-model="form.client_email"
                                />
                            </div>
                            <div class="mt-4 flex items-center">
                                <label for="client_phone" class="ltr:mr-2 rtl:ml-2 w-1/3 mb-0">Número de teléfono</label>
                                <input
                                    id="client_phone"
                                    type="text"
                                    name="client_phone"
                                    class="form-input flex-1 form-input-sm"
                                    v-model="form.client_phone"
                                />
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="border-[#e0e6ed] dark:border-[#1b2e4b] my-6" />
                <div class="mt-8">
                    <div class="flex justify-between lg:flex-row flex-col">
                        <div class="w-full ltr:lg:mr-6 rtl:lg:ml-6 mb-6">
                            <div class="text-xl px-4">Detalles de la compra</div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="w-full text-sm text-left rtl:text-right">
                            <thead class="text-xs text-white uppercase bg-blue-600 border-b border-t border-blue-400 dark:text-white">
                                <tr>
                                    <th class="w-8 bg-blue-600"></th>
                                    <th class="bg-blue-600">Item</th>
                                    <th class="w-20 bg-blue-600">Cantidad</th>
                                    <th class="w-20 bg-blue-600">Precio</th>
                                    <th class="bg-blue-600">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="form.items.length <= 0">
                                    <tr>
                                        <td colspan="5" class="!text-center font-semibold text-primary">Ningún artículo disponible</td>
                                    </tr>
                                </template>
                                <template v-for="(item, i) in form.items" :key="i">
                                    <tr class="align-center">
                                        <td class="border-b border-blue-400">
                                            <button type="button" @click="removeItem(item, i)" class="text-primary p-0">
                                                <icon-x class="w-5 h-5" />
                                            </button>
                                        </td>
                                        <td class="text-primary border-b border-blue-400">
                                            <!-- <span>{{ item.title }}</span> -->
                                            <textarea class="form-textarea text-primary" placeholder="Enter Description" v-model="item.description"></textarea>
                                        </td>
                                        <td class="text-primary text-right border-b border-blue-400 w-20 px-1">{{ item.quantity }}</td>
                                        <td class="text-primary text-right border-b border-blue-400">

                                            <input v-model="item.amount"
                                            @input="calculateTotalsInputs(i)"
                                            :disabled="isEdit ? true : false"
                                            :style="isEdit ? 'cursor: not-allowed': ''"
                                            :class="isEdit ? 'bg-gray-100' : ''"
                                            class="form-input form-input-sm text-right w-20 px-1" type="text" />
                                        </td>
                                        <td class="text-primary text-right border-b border-blue-400">S/. {{ item.amount * item.quantity }}</td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div class="flex justify-end sm:flex-row flex-col mt-6 px-4 text-primary">
                        <div class="sm:w-2/5">
                            <div class="flex items-center justify-between">
                                <div>Subtotal</div>
                                <div>S/. {{ form.total_taxed }}</div>
                            </div>
                            <div class="flex items-center justify-between mt-4">
                                <div>IGV</div>
                                <div>S/. {{ form.total_igv }}</div>
                            </div>
                            <!-- <div class="flex items-center justify-between mt-4">
                                <div>Descuento</div>
                                <div>S/. {{ form.total_discount }}</div>
                            </div> -->
                            <div class="flex items-center justify-between mt-4 font-semibold">
                                <div>Total</div>
                                <div> S/. {{ form.total }}</div>
                            </div>
                            <InputError :message="form.errors.total" class="mt-2" />
                        </div>
                    </div>
                </div>
                <div class="mt-8 px-4">
                    <div>
                        <label for="additional_Information">Informacion Adicional</label>
                        <textarea id="additional_Information" name="additional_Information" class="form-textarea min-h-[130px]" v-model="form.additional_Information"></textarea>
                    </div>
                </div>
                <div class="mt-8 px-4">
                    <h4 class="font-bold">Medio de Pago <span @click="addPayment" class="text-primary italic" style="cursor: pointer;">(+) agregar</span></h4>
                    <div v-for="(pay, key) in form.payments" class="border rounded p-4 mt-4">
                        <div class="grid sm:grid-cols-3 grid-cols-1 gap-4">
                            <div>
                                <label for="reference">Tipo </label>
                                <select id="type" name="type" class="form-select" v-model="pay.type">
                                    <template v-for="(payment, i) in payments" :key="i">
                                        <option :value="payment.id">{{ payment.description }}</option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label for="reference">Referencia </label>
                                <input v-model="pay.reference" id="reference" type="number" name="reference" class="form-input" />
                            </div>
                            <div>
                                <label for="amount">Monto </label>
                                <input v-model="pay.amount" id="amount" type="number" name="amount" class="form-input" />
                            </div>
                            <div v-if="key > 0" class="col-span-1 sm:col-span-3 flex justify-end">
                                <button @click="removePayment(key)" type="button">
                                    <icon-x class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-8 px-4">
                    <div class="grid sm:grid-cols-2 grid-cols-1 gap-4 justify-between relative">

                        <div>
                            <label>FECHA PRÓXIMO PAGO</label>
                            <input v-model="form.nextPaymentDate" type="date" class="form-input max-w-[160px]" />
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <button @click="saveDocument" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" type="button" class="btn btn-success bottom-0">
                                <svg v-if="form.processing" aria-hidden="true" role="status" class="inline w-4 h-4 mr-2 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                                    <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                                </svg>
                                <icon-save v-else class="ltr:mr-2 rtl:ml-2 shrink-0" />
                                Generar
                            </button>

                            <button @click="closeMyPopup" class="btn btn-primary bottom-0">
                                <icon-x class="ltr:mr-2 rtl:ml-2 shrink-0" />
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
