<script setup>
import GuestLayout from "@/Layouts/Vristo/AuthLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import IconLoader from "@/Components/vristo/icon/icon-loader.vue";
import { useForm, usePage } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import { Select } from "ant-design-vue";
import Swal2 from "sweetalert2";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { faCheckCircle, faFileImage, faMagnifyingGlass, faUpload, faXmark, faXmarkCircle } from "@fortawesome/free-solid-svg-icons";

const props = defineProps({
    negotiation: { type: Object, default: () => ({}) },
    identityDocumentTypes: { type: Array, default: () => [] },
    paymentMethodCatalog: { type: Array, default: () => [] },
    bankAccounts: { type: Array, default: () => [] },
});

const company = usePage().props.company;
const baseUrl = assetUrl;

const accepted = ref(false);
const searchLoading = ref(false);
const fileInput = ref(null);
const voucherPreview = ref(null);

const form = useForm({
    accepted: false,
    invoice_type: "boleta",
    document_type_id: "1",
    number: null,
    full_name: null,
    names: null,
    father_lastname: null,
    mother_lastname: null,
    gender: "M",
    email: null,
    telephone: null,
    ocupacion: null,
    profession: null,
    ruc: null,
    invoice_razon_social: null,
    invoice_direccion: null,
    invoice_estado: null,
    invoice_condicion: null,
    invoice_ubigeo: null,
    invoice_distrito: null,
    invoice_provincia: null,
    invoice_departamento: null,
    voucher: null,
});

const rucLoading = ref(false);
const rucValidated = ref(false);
const rucNotice = ref("");

const isRuc = computed(() => String(form.document_type_id) === "6");
const isBoleta = computed(() => form.invoice_type === "boleta");
const isFactura = computed(() => form.invoice_type === "factura");
const rucLengthOk = computed(() => Boolean(form.ruc) && String(form.ruc).length === 11);
const isApproved = computed(() => props.negotiation.status === "aprobada");
const isCancelled = computed(() => props.negotiation.status === "cancelada");
const isRejected = computed(() => props.negotiation.status === "rechazada");
const showForm = computed(() => !isApproved.value && !isCancelled.value);

const documentTypeOptions = computed(() => props.identityDocumentTypes.map((item) => ({
    value: String(item.id),
    label: item.description,
})));

const filterOption = (input, option) => option.label.toLowerCase().includes(input.toLowerCase());

const yapeNumber = computed(() => {
    const method = props.paymentMethodCatalog.find((item) => /yape/i.test(item.description));
    return method?.number || null;
});

const totalAmount = computed(() => {
    const value = Number(props.negotiation.total_price || 0);
    return `${props.negotiation.currency} ${value.toFixed(2)}`;
});

const itemsSum = computed(() => {
    const items = props.negotiation.items || [];
    return items.reduce((acc, item) => acc + (Number(item.price) || 0), 0);
});

const showItemPrices = computed(() => {
    const total = Number(props.negotiation.total_price || 0);
    return Math.abs(itemsSum.value - total) < 0.01;
});

const scheduleCount = computed(() => (props.negotiation.schedule || []).length || '--');

const onlyNumbers = () => {
    form.number = form.number ? String(form.number).replace(/\D/g, "") : null;
};

const fillForm = (person) => {
    form.document_type_id = String(person.document_type_id ?? form.document_type_id);
    form.number = person.number ?? person.document_number ?? form.number;
    form.full_name = person.full_name ?? person.razon_social ?? form.full_name;
    form.names = person.names ?? null;
    form.father_lastname = person.father_lastname ?? null;
    form.mother_lastname = person.mother_lastname ?? null;
    form.gender = person.gender ?? form.gender;
    form.email = person.email ?? form.email;
    form.telephone = person.telephone ?? form.telephone;
    form.ocupacion = person.ocupacion ?? form.ocupacion;
    form.profession = person.profession ?? form.profession;
};

const searchPerson = () => {
    if (searchLoading.value) return;

    onlyNumbers();
    form.clearErrors();
    searchLoading.value = true;

    axios.post(route("comm_negotiations_public_search", props.negotiation.token), {
        document_type_id: form.document_type_id,
        number: form.number,
    }).then((res) => {
        if (!res.data.status) {
            Swal2.fire({
                title: "Sin resultados",
                text: res.data.message,
                icon: "warning",
                padding: "2em",
                customClass: "sweet-alerts",
            });
            return;
        }

        fillForm(res.data.person);

        Swal2.fire({
            title: "Cliente encontrado",
            text: "Se cargaron tus datos desde la base de datos.",
            icon: "success",
            padding: "2em",
            customClass: "sweet-alerts",
        });
    }).finally(() => {
        searchLoading.value = false;
    });
};

const onRucInput = () => {
    form.ruc = form.ruc ? String(form.ruc).replace(/\D/g, "") : null;
    rucValidated.value = false;
    rucNotice.value = "";
    form.invoice_razon_social = null;
    form.invoice_direccion = null;
    form.invoice_estado = null;
    form.invoice_condicion = null;
    form.invoice_ubigeo = null;
    form.invoice_distrito = null;
    form.invoice_provincia = null;
    form.invoice_departamento = null;
};

const validateRuc = () => {
    if (rucLoading.value) return;

    if (!rucLengthOk.value) {
        Swal2.fire({
            title: "RUC invalido",
            text: "El RUC debe tener 11 digitos.",
            icon: "warning",
            padding: "2em",
            customClass: "sweet-alerts",
        });
        return;
    }

    form.clearErrors(["ruc", "invoice_razon_social", "invoice_direccion", "invoice_estado", "invoice_condicion"]);
    rucLoading.value = true;
    rucNotice.value = "";

    axios.post(route("comm_negotiations_public_validate_ruc", props.negotiation.token), {
        ruc: form.ruc,
    }).then((res) => {
        if (!res.data || !res.data.success) {
            rucValidated.value = false;
            rucNotice.value = res.data?.error || "No se pudo validar el RUC.";
            return;
        }

        const person = res.data.person || {};
        form.invoice_razon_social = person.razon_social || null;
        form.invoice_direccion = person.direccion && person.direccion !== "-" ? person.direccion : null;
        form.invoice_estado = person.estado || null;
        form.invoice_condicion = person.condicion || null;
        form.invoice_ubigeo = person.ubigeo || null;
        form.invoice_distrito = person.distrito || null;
        form.invoice_provincia = person.provincia || null;
        form.invoice_departamento = person.departamento || null;

        const activo = String(form.invoice_estado).toUpperCase() === "ACTIVO";
        const habido = String(form.invoice_condicion).toUpperCase() === "HABIDO";
        rucValidated.value = activo && habido;

        if (rucValidated.value) {
            rucNotice.value = "";
            return;
        }

        rucNotice.value = "El RUC no esta ACTIVO y HABIDO. Estos datos son obligatorios; verifica o intenta con otro RUC.";
    }).finally(() => {
        rucLoading.value = false;
    });
};

watch(() => form.invoice_type, (value) => {
    if (value === "boleta") {
        form.ruc = null;
        form.invoice_razon_social = null;
        form.invoice_direccion = null;
        form.invoice_estado = null;
        form.invoice_condicion = null;
        form.invoice_ubigeo = null;
        form.invoice_distrito = null;
        form.invoice_provincia = null;
        form.invoice_departamento = null;
        rucValidated.value = false;
        rucNotice.value = "";
    }
});

const acceptAgreement = () => {
    Swal2.fire({
        title: "Confirmar acuerdo",
        text: "Al continuar confirmas que aceptas las condiciones de la negociacion y envias tus datos de contacto.",
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Si, aceptar",
        cancelButtonText: "Cancelar",
        padding: "2em",
        customClass: "sweet-alerts",
    }).then((result) => {
        if (result.isConfirmed) {
            accepted.value = true;
            form.clearErrors("accepted");
        }
    });
};

const selectVoucher = (event) => {
    const file = event.target.files[0] ?? null;
    form.voucher = file;

    if (file) {
        voucherPreview.value = URL.createObjectURL(file);
    } else {
        voucherPreview.value = null;
    }
};

const clearVoucher = () => {
    form.voucher = null;
    voucherPreview.value = null;
    if (fileInput.value) fileInput.value.value = null;
};

const openVoucherSelector = () => {
    fileInput.value?.click();
};

const submit = () => {
    form.accepted = true;

    if (isFactura.value && !rucValidated.value) {
        Swal2.fire({
            title: "RUC no validado",
            text: rucNotice.value || "Debes validar el RUC y confirmar que este ACTIVO y HABIDO para continuar.",
            icon: "warning",
            padding: "2em",
            customClass: "sweet-alerts",
        });
        return;
    }

    onlyNumbers();

    form.post(route("comm_negotiations_public_store", props.negotiation.token), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            Swal2.fire({
                title: "Enviado",
                text: "Tu acuerdo fue enviado correctamente. El asesor lo revisara.",
                icon: "success",
                padding: "2em",
                customClass: "sweet-alerts",
            });
        },
    });
};
</script>

<template>
    <GuestLayout title="Negociacion">
        <div class="min-h-screen bg-gray-100 px-4 py-8 dark:bg-[#060818] sm:px-6">
            <div class="mx-auto max-w-4xl">
                <div class="mb-6 flex items-center gap-3">
                    <img
                        v-if="company.logo"
                        :src="company.logo.startsWith('/') ? company.logo : `${baseUrl}storage/${company.logo}`"
                        alt="Logo"
                        class="h-10 w-auto"
                    />
                    <div>
                        <h1 class="text-xl font-bold dark:text-white">{{ negotiation.title }}</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Propuesta comercial</p>
                    </div>
                </div>

                <div v-if="isApproved" class="panel p-8 text-center">
                    <FontAwesomeIcon :icon="faCheckCircle" class="mx-auto mb-4 h-16 w-16 text-emerald-500" />
                    <h2 class="mb-2 text-2xl font-bold dark:text-white">Acuerdo aprobado</h2>
                    <p class="text-gray-600 dark:text-gray-400">
                        Esta negociacion ya fue aprobada. Gracias por tu preferencia.
                    </p>
                </div>

                <div v-else-if="isCancelled" class="panel p-8 text-center">
                    <h2 class="mb-2 text-2xl font-bold dark:text-white">Negociacion no disponible</h2>
                    <p class="text-gray-600 dark:text-gray-400">
                        Esta negociacion fue cancelada y ya no acepta envios.
                    </p>
                </div>

                <template v-else>
                    <div v-if="isRejected" class="mb-4">
                        <div class="rounded-md border border-red-200 border-l-4 border-l-red-500 bg-red-50 p-4 dark:border-red-900/60 dark:border-l-red-400 dark:bg-red-950/30">
                            <p class="font-semibold text-red-800 dark:text-red-200">Pago no acreditado</p>
                            <p v-if="negotiation.rejected_reason" class="text-sm text-red-700 dark:text-red-300">{{ negotiation.rejected_reason }}</p>
                            <p class="mt-1 text-sm text-red-700 dark:text-red-300">Puedes volver a enviar un nuevo voucher de pago.</p>
                        </div>
                    </div>

                    <div class="panel mb-4">
                        <div class="mb-4 flex items-start justify-between gap-4">
                            <div>
                                <h2 class="text-lg font-semibold dark:text-white">Detalle del acuerdo</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Revisa las condiciones antes de confirmar.</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Total</p>
                                <p class="text-2xl font-bold text-primary">{{ totalAmount }}</p>
                            </div>
                        </div>

                        <div v-if="negotiation.body" class="prose-sm max-w-none text-sm dark:text-gray-300" v-html="negotiation.body"></div>

                        <div class="mt-4 overflow-hidden rounded-md border border-gray-200 dark:border-gray-700">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Concepto</th>
                                        <th v-if="showItemPrices" class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Monto</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                    <tr v-for="item in negotiation.items" :key="item.title">
                                        <td class="px-4 py-3 text-sm">{{ item.title }}</td>
                                        <td v-if="showItemPrices" class="px-4 py-3 text-right text-sm">
                                            {{ item.price !== null ? `${negotiation.currency} ${item.price}` : '--' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div class="rounded-md border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800/40">
                                <p class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Modalidad</p>
                                <p class="mt-1 text-sm font-semibold dark:text-white">
                                    {{ negotiation.payment_type === 'installments' ? 'Cuotas' : 'Pago unico' }}
                                </p>
                            </div>
                            <div class="rounded-md border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800/40">
                                <p class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                                    {{ negotiation.payment_type === 'installments' ? 'Cuotas' : 'Plazo de pago' }}
                                </p>
                                <p class="mt-1 text-sm font-semibold dark:text-white">
                                    {{ negotiation.payment_type === 'installments' ? `${scheduleCount} cuotas` : `${negotiation.single_payment_days ?? '--'} dias` }}
                                </p>
                            </div>
                            <div class="rounded-md border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800/40">
                                <p class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Canal de contacto</p>
                                <p class="mt-1 text-sm font-semibold dark:text-white">{{ negotiation.contact_channel || '--' }}</p>
                                <p v-if="negotiation.contact_detail" class="text-xs text-gray-500">{{ negotiation.contact_detail }}</p>
                            </div>
                        </div>

                        <div v-if="negotiation.payment_type === 'installments' && negotiation.schedule?.length" class="mt-4 overflow-hidden rounded-md border border-gray-200 dark:border-gray-700">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Cuota</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Vencimiento</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Monto</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                    <tr v-for="(row, index) in negotiation.schedule" :key="index">
                                        <td class="px-4 py-3 text-sm">{{ index + 1 }}</td>
                                        <td class="px-4 py-3 text-sm">{{ row.due_date }}</td>
                                        <td class="px-4 py-3 text-right text-sm">{{ negotiation.currency }} {{ row.amount }}</td>
                                    </tr>
                                </tbody>
                            </table>
                            <p class="px-4 py-2 text-xs text-gray-500">Cronograma provisional; el cronograma final se define al aprobarse.</p>
                        </div>
                    </div>

                    <div v-if="!accepted && !isRejected" class="panel mb-4 p-6 text-center">
                        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                            Para continuar debes aceptar las condiciones de la negociacion.
                        </p>
                        <button type="button" class="btn btn-primary" @click="acceptAgreement">
                            Entendido, comenzar
                        </button>
                    </div>

                    <form v-if="accepted || isRejected" class="panel" @submit.prevent="submit">
                        <h2 class="mb-4 text-lg font-semibold dark:text-white">Tus datos</h2>
                        <InputError :message="form.errors.accepted" class="mb-3" />

                        <div class="grid grid-cols-6 gap-4">
                            <div class="col-span-6">
                                <InputLabel value="Tipo de comprobante *" />
                                <div class="flex flex-wrap gap-4 mt-2">
                                    <label class="inline-flex items-center">
                                        <input v-model="form.invoice_type" type="radio" value="boleta" class="form-radio" />
                                        <span>Boleta electronica</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input v-model="form.invoice_type" type="radio" value="factura" class="form-radio text-success" />
                                        <span>Factura electronica</span>
                                    </label>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">
                                    {{ isBoleta ? 'Los datos de la boleta seran los datos personales que ingreses a continuacion.' : 'Ademas de tus datos personales, ingresa el RUC de la empresa y validalo; son obligatorios para la factura.' }}
                                </p>
                                <InputError :message="form.errors.invoice_type" class="mt-1" />
                            </div>

                            <div class="col-span-6 sm:col-span-2">
                                <InputLabel value="Tipo de documento" />
                                <Select
                                    v-model:value="form.document_type_id"
                                    :options="documentTypeOptions"
                                    style="width: 100%"
                                />
                                <InputError :message="form.errors.document_type_id" class="mt-1" />
                            </div>

                            <div class="col-span-6 sm:col-span-2">
                                <InputLabel for="number" value="Numero de documento *" />
                                <TextInput id="number" v-model="form.number" type="text" inputmode="numeric" pattern="[0-9]*" @input="onlyNumbers" />
                                <InputError :message="form.errors.number" class="mt-1" />
                            </div>

                            <div class="col-span-6 sm:col-span-2">
                                <div class="mt-6">
                                    <button type="button" class="btn btn-secondary w-full" :class="{ 'opacity-50': searchLoading }" :disabled="searchLoading" @click="searchPerson">
                                        <IconLoader v-if="searchLoading" class="w-4 h-4 mr-2 animate-spin" />
                                        <FontAwesomeIcon v-else :icon="faMagnifyingGlass" class="mr-2 h-4 w-4" />
                                        Buscar datos
                                    </button>
                                </div>
                            </div>

                            <template v-if="isRuc">
                                <div class="col-span-6">
                                    <InputLabel for="full_name" value="Razon social *" />
                                    <TextInput id="full_name" v-model="form.full_name" type="text" />
                                    <InputError :message="form.errors.full_name" class="mt-1" />
                                </div>
                            </template>
                            <template v-else>
                                <div class="col-span-6 sm:col-span-2">
                                    <InputLabel for="names" value="Nombres *" />
                                    <TextInput id="names" v-model="form.names" type="text" />
                                    <InputError :message="form.errors.names" class="mt-1" />
                                </div>
                                <div class="col-span-6 sm:col-span-2">
                                    <InputLabel for="father_lastname" value="Apellido paterno *" />
                                    <TextInput id="father_lastname" v-model="form.father_lastname" type="text" />
                                    <InputError :message="form.errors.father_lastname" class="mt-1" />
                                </div>
                                <div class="col-span-6 sm:col-span-2">
                                    <InputLabel for="mother_lastname" value="Apellido materno *" />
                                    <TextInput id="mother_lastname" v-model="form.mother_lastname" type="text" />
                                    <InputError :message="form.errors.mother_lastname" class="mt-1" />
                                </div>
                            </template>

                            <div class="col-span-6 sm:col-span-2">
                                <InputLabel value="Genero" />
                                <div class="flex gap-4 mt-2">
                                    <label class="inline-flex items-center">
                                        <input v-model="form.gender" type="radio" value="M" class="form-radio" />
                                        <span>Masculino</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input v-model="form.gender" type="radio" value="F" class="form-radio text-success" />
                                        <span>Femenino</span>
                                    </label>
                                </div>
                                <InputError :message="form.errors.gender" class="mt-1" />
                            </div>

                            <div class="col-span-6 sm:col-span-2">
                                <InputLabel for="email" value="Email" />
                                <TextInput id="email" v-model="form.email" type="email" />
                                <InputError :message="form.errors.email" class="mt-1" />
                            </div>

                            <div class="col-span-6 sm:col-span-2">
                                <InputLabel for="telephone" value="Telefono" />
                                <TextInput id="telephone" v-model="form.telephone" type="text" />
                                <InputError :message="form.errors.telephone" class="mt-1" />
                            </div>

                            <div class="col-span-6 sm:col-span-3">
                                <InputLabel for="ocupacion" value="Ocupacion" />
                                <TextInput id="ocupacion" v-model="form.ocupacion" type="text" />
                                <InputError :message="form.errors.ocupacion" class="mt-1" />
                            </div>

                            <div class="col-span-6 sm:col-span-3">
                                <InputLabel for="profession" value="Especializacion" />
                                <TextInput id="profession" v-model="form.profession" type="text" />
                                <InputError :message="form.errors.profession" class="mt-1" />
                            </div>

                            <template v-if="isFactura">
                                <div class="col-span-6 mt-2 border-t border-gray-200 pt-4 dark:border-gray-700">
                                    <h3 class="font-semibold dark:text-white">Datos de la factura</h3>
                                    <p class="text-xs text-gray-500">Estos datos se usaran solo para la factura electronica.</p>
                                </div>

                                <div class="col-span-6 sm:col-span-3">
                                    <InputLabel for="ruc" value="RUC *" />
                                    <div class="flex">
                                        <TextInput id="ruc" v-model="form.ruc" type="text" inputmode="numeric" pattern="[0-9]*" class="ltr:rounded-r-none rtl:rounded-l-none" placeholder="Ingresa tu RUC" @input="onRucInput" />
                                        <button type="button" class="btn btn-secondary ltr:rounded-l-none rtl:rounded-r-none" :class="{ 'opacity-50': rucLoading }" :disabled="rucLoading" @click="validateRuc">
                                            <IconLoader v-if="rucLoading" class="w-4 h-4 mr-2 animate-spin" />
                                            <FontAwesomeIcon v-else :icon="faMagnifyingGlass" class="mr-2 h-4 w-4" />
                                            Validar
                                        </button>
                                    </div>
                                    <InputError :message="form.errors.ruc" class="mt-1" />
                                </div>

                                <div class="col-span-6 sm:col-span-3">
                                    <InputLabel for="invoice_razon_social" value="Razon social *" />
                                    <TextInput id="invoice_razon_social" v-model="form.invoice_razon_social" type="text" />
                                    <InputError :message="form.errors.invoice_razon_social" class="mt-1" />
                                </div>

                                <div class="col-span-6 sm:col-span-4">
                                    <InputLabel for="invoice_direccion" value="Direccion fiscal" />
                                    <TextInput id="invoice_direccion" v-model="form.invoice_direccion" type="text" />
                                    <InputError :message="form.errors.invoice_direccion" class="mt-1" />
                                </div>

                                <div class="col-span-6 sm:col-span-2">
                                    <InputLabel value="Estado del RUC" />
                                    <div class="flex flex-wrap gap-2 mt-2">
                                        <span v-if="form.invoice_estado" class="badge" :class="form.invoice_estado === 'ACTIVO' ? 'bg-success' : 'bg-danger'">
                                            <FontAwesomeIcon :icon="form.invoice_estado === 'ACTIVO' ? faCheckCircle : faXmarkCircle" class="mr-1 h-3 w-3" />
                                            {{ form.invoice_estado }}
                                        </span>
                                        <span v-if="form.invoice_condicion" class="badge" :class="form.invoice_condicion === 'HABIDO' ? 'bg-success' : 'bg-danger'">
                                            <FontAwesomeIcon :icon="form.invoice_condicion === 'HABIDO' ? faCheckCircle : faXmarkCircle" class="mr-1 h-3 w-3" />
                                            {{ form.invoice_condicion }}
                                        </span>
                                    </div>
                                    <p v-if="rucNotice" class="mt-1 text-xs text-danger">{{ rucNotice }}</p>
                                </div>
                            </template>
                        </div>

                        <div class="mt-6 border-t border-gray-200 pt-5 dark:border-gray-700">
                            <h3 class="mb-3 font-semibold dark:text-white">Como realizar el pago</h3>

                            <div v-if="negotiation.payment_method === 'transferencia'" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div v-for="account in bankAccounts" :key="account.id" class="rounded-md border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800/40">
                                    <p class="text-sm font-semibold dark:text-white">{{ account.bank?.short_name || 'Banco' }} - {{ account.description }}</p>
                                    <p class="text-xs text-gray-500">Cuenta: {{ account.number }}</p>
                                    <p v-if="account.cci" class="text-xs text-gray-500">CCI: {{ account.cci }}</p>
                                </div>
                            </div>

                            <div v-else-if="negotiation.payment_method === 'billetera_digital'" class="space-y-4">
                                <div
                                    v-for="billetera in negotiation.company_billeteras"
                                    :key="billetera.id"
                                    class="flex flex-col items-center gap-4 rounded-md border border-gray-200 bg-gray-50 p-5 text-center dark:border-gray-700 dark:bg-gray-800/40"
                                >
                                    <div>
                                        <p class="text-2xl font-bold dark:text-white">{{ billetera.nombre }}</p>
                                        <p class="text-lg font-medium text-gray-700 dark:text-gray-300">Titular: {{ billetera.titular || '--' }}</p>
                                    </div>
                                    <img
                                        v-if="billetera.qr_url"
                                        :src="billetera.qr_url"
                                        :alt="billetera.nombre"
                                        class="h-[350px] w-[350px] max-w-full rounded-md border border-gray-200 object-contain dark:border-gray-700"
                                    />
                                </div>
                                <p class="text-sm text-gray-500">Elige cualquiera de los medios, escanea el codigo QR y adjunta el voucher o captura del pago.</p>
                            </div>

                            <div v-else-if="negotiation.payment_method === 'mercadopago' || negotiation.payment_method === 'enlace'" class="rounded-md border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800/40">
                                <p class="text-sm font-semibold dark:text-white">Pago en linea</p>
                                <p class="mt-1 text-xs text-gray-500">Realiza tu pago en el siguiente enlace y adjunta la captura del comprobante.</p>
                                <a v-if="negotiation.payment_link" :href="negotiation.payment_link" target="_blank" class="btn btn-primary btn-sm mt-2">
                                    Ir a pagar
                                </a>
                            </div>

                            <p class="mt-2 text-xs text-gray-500">Despues de realizar el pago, adjunta el voucher o captura para que el asesor lo verifique.</p>
                        </div>

                        <div class="mt-6 border-t border-gray-200 pt-5 dark:border-gray-700">
                            <InputLabel for="voucher" value="Voucher de pago *" />
                            <input ref="fileInput" id="voucher" type="file" accept="image/*" class="hidden" @input="selectVoucher" />

                            <div class="mt-2 rounded-md border border-dashed border-gray-300 bg-gray-50 p-4 transition hover:border-primary dark:border-gray-700 dark:bg-gray-900/40">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
                                            <FontAwesomeIcon :icon="faFileImage" class="h-5 w-5" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-gray-800 dark:text-white">
                                                {{ form.voucher?.name || 'Selecciona una imagen' }}
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                Imagen JPG, PNG o WEBP, maximo 5 MB.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2">
                                        <button type="button" class="btn btn-outline-primary" @click="openVoucherSelector">
                                            <FontAwesomeIcon :icon="faUpload" class="mr-2 h-4 w-4" />
                                            {{ form.voucher ? 'Cambiar' : 'Subir voucher' }}
                                        </button>
                                        <button v-if="form.voucher" type="button" class="btn btn-outline-danger" @click="clearVoucher">
                                            <FontAwesomeIcon :icon="faXmark" class="mr-2 h-4 w-4" />
                                            Quitar
                                        </button>
                                    </div>
                                </div>

                                <img v-if="voucherPreview" :src="voucherPreview" alt="Voucher" class="mt-3 max-h-48 rounded-md border border-gray-200 object-contain dark:border-gray-700" />
                            </div>
                            <InputError :message="form.errors.voucher" class="mt-2" />
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="btn btn-primary" :class="{ 'opacity-50': form.processing }" :disabled="form.processing">
                                <IconLoader v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
                                {{ isRejected ? "Reenviar confirmacion" : "Enviar confirmacion" }}
                            </button>
                        </div>
                    </form>
                </template>
            </div>
        </div>
    </GuestLayout>
</template>
