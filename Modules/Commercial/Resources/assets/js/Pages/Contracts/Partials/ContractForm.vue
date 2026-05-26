<script setup>
import FormSection from "@/Components/FormSection.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import EditorAracode from "@/Components/EditorAracode.vue";
import IconLoader from "@/Components/vristo/icon/icon-loader.vue";
import { Link, useForm } from "@inertiajs/vue3";
import { computed, onMounted, ref } from "vue";
import { Select } from "ant-design-vue";
import Swal2 from "sweetalert2";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { faFilePdf, faMagnifyingGlass, faPen, faUpload, faXmark } from "@fortawesome/free-solid-svg-icons";
import SearchService from "./SearchService.vue";

const props = defineProps({
    contract: { type: Object, default: null },
    clients: { type: Array, default: () => [] },
    identityDocumentTypes: { type: Array, default: () => [] },
    currencyTypes: { type: Array, default: () => [] },
    contractTypes: { type: Array, default: () => [] },
});

const fileInput = ref(null);
const selectedPdfName = ref(null);
const responsibleSearchLoading = ref(false);
const searchServiceRef = ref(null);
const serviceDisplay = ref("");
const selectedService = ref(null);

const form = useForm({
    client_id: props.contract?.client_id ?? null,
    service_id: props.contract?.service_id ?? null,
    contract_type: props.contract?.contract_type ?? "new_development",
    title: props.contract?.title ?? null,
    start_date: props.contract?.start_date ?? null,
    end_date: props.contract?.end_date ?? null,
    amount: props.contract?.amount ?? null,
    currency: props.contract?.currency ?? "PEN",
    body: props.contract?.body ?? "",
    signed_pdf: null,
    remove_signed_pdf: false,
    responsible_document_type_id: props.contract?.responsible?.document_type_id ?? "1",
    responsible_number: props.contract?.responsible?.number ?? null,
    responsible_names: props.contract?.responsible?.names ?? null,
    responsible_father_lastname: props.contract?.responsible?.father_lastname ?? null,
    responsible_mother_lastname: props.contract?.responsible?.mother_lastname ?? null,
    responsible_gender: props.contract?.responsible?.gender ?? "M",
    responsible_email: props.contract?.responsible?.email ?? null,
    responsible_telephone: props.contract?.responsible?.telephone ?? null,
});

const isEdit = computed(() => !!props.contract?.id);
const selectedClient = computed(() => props.clients.find((item) => String(item.id) === String(form.client_id)));
const requiresResponsible = computed(() => String(selectedClient.value?.document_type_id) === "6");

const clientOptions = computed(() => props.clients.map((item) => ({
    value: item.id,
    label: `${item.full_name} - ${item.number}`,
})));

const currencyOptions = computed(() => props.currencyTypes.map((item) => ({
    value: item.id,
    label: `${item.id} - ${item.description}${item.symbol ? ` (${item.symbol})` : ""}`,
})));

const documentTypeOptions = computed(() => props.identityDocumentTypes.map((item) => ({
    value: String(item.id),
    label: item.description,
})));

const filterOption = (input, option) => option.label.toLowerCase().includes(input.toLowerCase());

const onlyResponsibleNumbers = () => {
    form.responsible_number = form.responsible_number ? String(form.responsible_number).replace(/\D/g, "") : null;
};

const parseSalePrices = (salePrices) => {
    if (!salePrices) return {};
    if (typeof salePrices === "string") {
        try {
            return JSON.parse(salePrices);
        } catch {
            return {};
        }
    }
    return salePrices;
};

const servicePrice = (service) => {
    const prices = parseSalePrices(service?.sale_prices);
    const value = prices?.high ?? prices?.medium ?? prices?.under ?? null;
    return value !== null && value !== undefined && value !== "" ? value : null;
};

const formatServiceLabel = (service) => {
    if (!service) return "";
    return `${service.interne || "SERV"} - ${service.description}`;
};

const applySelectedService = (service) => {
    if (!service?.id) return;
    selectedService.value = service;
    form.service_id = service.id;
    serviceDisplay.value = formatServiceLabel(service);
    const price = servicePrice(service);
    form.amount = price !== null && price !== undefined && price !== "" ? price : null;
};

const onServiceSelected = (service) => {
    applySelectedService(service);
};

const openServiceSearch = () => {
    searchServiceRef.value?.openModal();
};

const openServiceEdit = () => {
    if (!form.service_id) return;
    searchServiceRef.value?.openModalForEdit(form.service_id);
};

onMounted(() => {
    if (props.contract?.service) {
        applySelectedService({
            id: props.contract.service.id,
            interne: props.contract.service.interne,
            description: props.contract.service.description,
            sale_prices: parseSalePrices(props.contract.service.sale_prices),
        });
    }
});

const fillResponsible = (person) => {
    form.responsible_document_type_id = String(person.document_type_id ?? form.responsible_document_type_id);
    form.responsible_number = person.number ?? person.document_number ?? person.numero_documento ?? form.responsible_number;
    form.responsible_names = person.names ?? form.responsible_names;
    form.responsible_father_lastname = person.father_lastname ?? form.responsible_father_lastname;
    form.responsible_mother_lastname = person.mother_lastname ?? form.responsible_mother_lastname;
    form.responsible_gender = person.gender ?? form.responsible_gender;
    form.responsible_email = person.email ?? form.responsible_email;
    form.responsible_telephone = person.telephone ?? form.responsible_telephone;
};

const searchResponsible = () => {
    if (responsibleSearchLoading.value) return;

    onlyResponsibleNumbers();
    form.clearErrors();
    responsibleSearchLoading.value = true;

    axios.post(route("comm_contracts_responsible_search"), {
        responsible_document_type_id: form.responsible_document_type_id,
        responsible_number: form.responsible_number,
    }).then((res) => {
        if (!res.data.success) {
            Swal2.fire({
                title: "Sin resultados",
                text: res.data.message || res.data.error || "No se encontro el responsable.",
                icon: "warning",
                padding: "2em",
                customClass: "sweet-alerts",
            });
            return;
        }

        if (res.data.source === "database") {
            fillResponsible(res.data.person);
        } else {
            const person = res.data.person;
            fillResponsible({
                document_type_id: form.responsible_document_type_id,
                number: person.document_number ?? form.responsible_number,
                names: person.names,
                father_lastname: person.father_lastname,
                mother_lastname: person.mother_lastname,
            });
        }

        Swal2.fire({
            title: "Responsable encontrado",
            text: res.data.source === "database" ? "Se cargaron los datos desde la base de datos." : "Se consulto RENIEC correctamente.",
            icon: "success",
            padding: "2em",
            customClass: "sweet-alerts",
        });
    }).catch((error) => {
        if (error.response?.data?.errors) {
            Object.entries(error.response.data.errors).forEach(([field, messages]) => form.setError(field, messages[0]));
            return;
        }

        Swal2.fire({
            title: "Error",
            text: error.response?.data?.message || "No se pudo realizar la busqueda.",
            icon: "error",
            padding: "2em",
            customClass: "sweet-alerts",
        });
    }).finally(() => {
        responsibleSearchLoading.value = false;
    });
};

const selectPdf = (event) => {
    const file = event.target.files[0] ?? null;
    form.signed_pdf = file;
    selectedPdfName.value = file?.name ?? null;
    form.remove_signed_pdf = false;
};

const clearPdf = () => {
    form.signed_pdf = null;
    selectedPdfName.value = null;
    form.remove_signed_pdf = true;
    if (fileInput.value) fileInput.value.value = null;
};

const openPdfSelector = () => {
    fileInput.value?.click();
};

const submit = () => {
    onlyResponsibleNumbers();

    const options = {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            Swal2.fire({
                title: "Enhorabuena",
                text: isEdit.value ? "Se actualizo correctamente" : "Se guardo correctamente",
                icon: "success",
                padding: "2em",
                customClass: "sweet-alerts",
            });

            if (!isEdit.value) {
                form.reset();
                form.contract_type = "new_development";
                form.currency = "PEN";
                form.body = "";
                form.responsible_document_type_id = "1";
                form.responsible_gender = "M";
                serviceDisplay.value = "";
                selectedService.value = null;
                selectedPdfName.value = null;
                if (fileInput.value) fileInput.value.value = null;
            }
        },
    };

    if (isEdit.value) {
        form.post(route("comm_contracts_update", props.contract.id), options);
        return;
    }

    form.post(route("comm_contracts_store"), options);
};
</script>

<template>
    <FormSection @submitted="submit">
        <template #title>
            {{ isEdit ? "Editar contrato" : "Nuevo contrato" }}
        </template>

        <template #description>
            Registra contratos vinculados a un cliente y a un servicio. Los campos con * son obligatorios; el cuerpo del contrato y el PDF firmado son opcionales.
        </template>

        <template #form>
            <div class="col-span-6 sm:col-span-3">
                <InputLabel value="Cliente *" />
                <Select
                    v-model:value="form.client_id"
                    :options="clientOptions"
                    :filter-option="filterOption"
                    show-search
                    style="width: 100%"
                />
                <InputError :message="form.errors.client_id" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-3">
                <InputLabel value="Servicio" />
                <div class="mt-1 flex items-start gap-2">
                    <button
                        v-if="form.service_id"
                        type="button"
                        v-tippy="{ content: 'Editar servicio', placement: 'bottom' }"
                        class="btn btn-outline-primary shrink-0 px-3"
                        @click="openServiceEdit"
                    >
                        <FontAwesomeIcon :icon="faPen" class="h-4 w-4" />
                    </button>
                    <TextInput
                        v-model="serviceDisplay"
                        type="text"
                        class="block min-w-0 flex-1"
                        readonly
                        placeholder="Seleccione un servicio..."
                    />
                    <button
                        type="button"
                        class="btn btn-info shrink-0 whitespace-nowrap"
                        @click="openServiceSearch"
                    >
                        <FontAwesomeIcon :icon="faMagnifyingGlass" class="mr-2 h-4 w-4" />
                        Buscar servicio
                    </button>
                </div>
                <InputError :message="form.errors.service_id" class="mt-2" />
                <SearchService ref="searchServiceRef" @selected="onServiceSelected" />
            </div>

            <div class="col-span-6 sm:col-span-2">
                <InputLabel value="Tipo de contrato *" />
                <select v-model="form.contract_type" class="form-select text-white-dark">
                    <option v-for="type in contractTypes" :key="type.value" :value="type.value">
                        {{ type.label }}
                    </option>
                </select>
                <InputError :message="form.errors.contract_type" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="title" value="Titulo *" />
                <TextInput id="title" v-model="form.title" type="text" />
                <InputError :message="form.errors.title" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-2">
                <InputLabel for="start_date" value="Fecha inicio" />
                <TextInput id="start_date" v-model="form.start_date" type="date" />
                <InputError :message="form.errors.start_date" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-2">
                <InputLabel for="end_date" value="Fecha fin" />
                <TextInput id="end_date" v-model="form.end_date" type="date" />
                <InputError :message="form.errors.end_date" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-1">
                <InputLabel for="currency" value="Moneda *" />
                <Select
                    v-model:value="form.currency"
                    :options="currencyOptions"
                    :filter-option="filterOption"
                    show-search
                    style="width: 100%"
                />
                <InputError :message="form.errors.currency" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-1">
                <InputLabel for="amount" value="Monto" />
                <TextInput id="amount" v-model="form.amount" type="number" step="0.01" />
                <InputError :message="form.errors.amount" class="mt-2" />
            </div>

            <template v-if="requiresResponsible">
                <div class="col-span-6">
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <h3 class="font-semibold text-gray-800 dark:text-white">Responsable de la empresa</h3>
                    </div>
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <InputLabel value="Tipo documento *" />
                    <Select
                        v-model:value="form.responsible_document_type_id"
                        :options="documentTypeOptions"
                        style="width: 100%"
                    />
                    <InputError :message="form.errors.responsible_document_type_id" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <InputLabel for="responsible_number" value="Numero documento *" />
                    <TextInput id="responsible_number" v-model="form.responsible_number" type="text" inputmode="numeric" pattern="[0-9]*" @input="onlyResponsibleNumbers" />
                    <InputError :message="form.errors.responsible_number" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <button
                        type="button"
                        class="btn btn-secondary mt-6 w-full"
                        :class="{ 'opacity-50': responsibleSearchLoading }"
                        :disabled="responsibleSearchLoading"
                        @click="searchResponsible"
                    >
                        <IconLoader v-if="responsibleSearchLoading" class="w-4 h-4 mr-2 animate-spin" />
                        <FontAwesomeIcon v-else :icon="faMagnifyingGlass" class="mr-2 h-4 w-4" />
                        Buscar DNI
                    </button>
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <InputLabel for="responsible_names" value="Nombres *" />
                    <TextInput id="responsible_names" v-model="form.responsible_names" type="text" />
                    <InputError :message="form.errors.responsible_names" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <InputLabel for="responsible_father_lastname" value="Apellido paterno *" />
                    <TextInput id="responsible_father_lastname" v-model="form.responsible_father_lastname" type="text" />
                    <InputError :message="form.errors.responsible_father_lastname" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <InputLabel for="responsible_mother_lastname" value="Apellido materno *" />
                    <TextInput id="responsible_mother_lastname" v-model="form.responsible_mother_lastname" type="text" />
                    <InputError :message="form.errors.responsible_mother_lastname" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <InputLabel value="Genero *" />
                    <div class="flex gap-4 mt-2">
                        <label class="inline-flex items-center">
                            <input v-model="form.responsible_gender" type="radio" value="M" class="form-radio" />
                            <span>Masculino</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input v-model="form.responsible_gender" type="radio" value="F" class="form-radio text-success" />
                            <span>Femenino</span>
                        </label>
                    </div>
                    <InputError :message="form.errors.responsible_gender" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-1">
                    <InputLabel for="responsible_telephone" value="Telefono" />
                    <TextInput id="responsible_telephone" v-model="form.responsible_telephone" type="text" />
                    <InputError :message="form.errors.responsible_telephone" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-1">
                    <InputLabel for="responsible_email" value="Email" />
                    <TextInput id="responsible_email" v-model="form.responsible_email" type="email" />
                    <InputError :message="form.errors.responsible_email" class="mt-2" />
                </div>
            </template>

            <div class="col-span-6">
                <InputLabel value="Cuerpo del contrato" />
                <EditorAracode
                    v-model="form.body"
                    minHeight="420px"
                    placeholder="Escribe el contrato aqui..."
                />
                <InputError :message="form.errors.body" class="mt-2" />
            </div>

            <div class="col-span-6">
                <InputLabel for="signed_pdf" value="PDF firmado" />
                <input ref="fileInput" id="signed_pdf" type="file" accept="application/pdf" class="hidden" @input="selectPdf" />

                <div class="mt-2 rounded-md border border-dashed border-gray-300 bg-gray-50 p-4 transition hover:border-primary dark:border-gray-700 dark:bg-gray-900/40">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex min-w-0 items-center gap-3">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-md bg-danger/10 text-danger">
                                <FontAwesomeIcon :icon="faFilePdf" class="h-5 w-5" />
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-gray-800 dark:text-white">
                                    {{ selectedPdfName || (contract?.signed_pdf_path && !form.remove_signed_pdf ? 'PDF firmado cargado' : 'Selecciona un PDF firmado') }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Archivo opcional en formato PDF, maximo 10 MB.
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" class="btn btn-outline-primary" @click="openPdfSelector">
                                <FontAwesomeIcon :icon="faUpload" class="mr-2 h-4 w-4" />
                                {{ selectedPdfName || contract?.signed_pdf_path ? 'Cambiar PDF' : 'Subir PDF' }}
                            </button>

                            <a
                                v-if="contract?.signed_pdf_path && !form.remove_signed_pdf"
                                :href="`/storage/${contract.signed_pdf_path}`"
                                target="_blank"
                                class="btn btn-outline-info"
                            >
                                Ver actual
                            </a>

                            <button
                                v-if="selectedPdfName || (contract?.signed_pdf_path && !form.remove_signed_pdf)"
                                type="button"
                                class="btn btn-outline-danger"
                                @click="clearPdf"
                            >
                                <FontAwesomeIcon :icon="faXmark" class="mr-2 h-4 w-4" />
                                Quitar
                            </button>
                        </div>
                    </div>
                </div>
                <InputError :message="form.errors.signed_pdf" class="mt-2" />
            </div>
        </template>

        <template #actions>
            <PrimaryButton :class="{ 'opacity-50': form.processing }" :disabled="form.processing">
                <IconLoader v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
                {{ isEdit ? "Actualizar" : "Guardar" }}
            </PrimaryButton>
            <Link :href="route('comm_contracts')" class="btn btn-success ml-2">
                Ir al listado
            </Link>
        </template>
    </FormSection>
</template>
