<script setup>
import FormSection from "@/Components/FormSection.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import IconLoader from "@/Components/vristo/icon/icon-loader.vue";
import { Link, useForm } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import { Select } from "ant-design-vue";
import Swal2 from "sweetalert2";

const props = defineProps({
    client: {
        type: Object,
        default: null,
    },
    identityDocumentTypes: {
        type: Object,
        default: () => ({}),
    },
    countries: {
        type: Object,
        default: () => ({}),
    },
    ubigeo: {
        type: Object,
        default: () => ({}),
    },
});

const form = useForm({
    id: props.client?.id ?? null,
    document_type_id: props.client?.document_type_id ?? "1",
    number: props.client?.number ?? null,
    full_name: props.client?.full_name ?? null,
    names: props.client?.names ?? null,
    father_lastname: props.client?.father_lastname ?? null,
    mother_lastname: props.client?.mother_lastname ?? null,
    gender: props.client?.gender ?? "M",
    birthdate: props.client?.birthdate ?? null,
    country_id: props.client?.country_id ?? 1,
    ubigeo: props.client?.ubigeo ?? null,
    ubigeo_description: props.client?.ubigeo_description ?? null,
    address: props.client?.address ?? null,
    email: props.client?.email ?? null,
    telephone: props.client?.telephone ?? null,
});

const isEdit = computed(() => !!form.id);
const isRuc = computed(() => String(form.document_type_id) === "6");
const internalSearchLoading = ref(false);
const externalSearchLoading = ref(false);
const searchProcessing = computed(() => internalSearchLoading.value || externalSearchLoading.value);

const ubigeoOptions = computed(() => props.ubigeo.map((item) => ({
    value: String(item.district_id),
    label: item.city_name,
})));

const countryOptions = computed(() => props.countries.map((item) => ({
    value: item.id,
    label: item.description,
})));

const filterOption = (input, option) => option.label.toLowerCase().includes(input.toLowerCase());

const onlyNumbers = () => {
    form.number = form.number ? String(form.number).replace(/\D/g, "") : null;
};

const syncUbigeoDescription = (value, option) => {
    form.ubigeo_description = option?.label ?? null;
};

const fillForm = (person) => {
    form.id = person.id ?? null;
    form.document_type_id = String(person.document_type_id ?? form.document_type_id);
    form.number = person.number ?? person.document_number ?? person.numero_documento ?? form.number;
    form.full_name = person.full_name ?? person.razon_social ?? form.full_name;
    form.names = person.names ?? null;
    form.father_lastname = person.father_lastname ?? null;
    form.mother_lastname = person.mother_lastname ?? null;
    form.gender = person.gender ?? form.gender;
    form.birthdate = person.birthdate ?? form.birthdate;
    form.country_id = person.country_id ?? form.country_id;
    form.ubigeo = person.ubigeo ? String(person.ubigeo) : form.ubigeo;
    form.ubigeo_description = person.ubigeo_description ?? person.city_name ?? form.ubigeo_description;
    form.address = person.address ?? person.direccion ?? form.address;
    form.email = person.email ?? form.email;
    form.telephone = person.telephone ?? form.telephone;
};

const searchInternal = () => {
    if (searchProcessing.value) return;

    onlyNumbers();
    form.clearErrors();
    internalSearchLoading.value = true;

    axios.post(route("comm_clients_search_internal"), form).then((res) => {
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
            text: "Se cargaron los datos desde la base de datos.",
            icon: "success",
            padding: "2em",
            customClass: "sweet-alerts",
        });
    }).finally(() => {
        internalSearchLoading.value = false;
    });
};

const searchExternal = () => {
    if (searchProcessing.value) return;

    onlyNumbers();
    form.clearErrors();
    externalSearchLoading.value = true;

    axios.post(route("comm_clients_search_external"), form).then((res) => {
        if (!res.data.success) {
            Swal2.fire({
                title: "Sin resultados",
                text: res.data.error || "No se encontraron datos.",
                icon: "error",
                padding: "2em",
                customClass: "sweet-alerts",
            });
            return;
        }

        if (res.data.source === "database") {
            fillForm(res.data.person);
            Swal2.fire({
                title: "Ya existe",
                text: res.data.message,
                icon: "info",
                padding: "2em",
                customClass: "sweet-alerts",
            });
            return;
        }

        if (isRuc.value) {
            const person = res.data.person;
            form.full_name = person.razon_social;
            form.number = person.numero_documento ?? form.number;
            form.address = person.direccion === "-" ? null : person.direccion;
            form.ubigeo = person.ubigeo ? String(person.ubigeo) : null;
            form.ubigeo_description = [person.departamento, person.provincia, person.distrito].filter(Boolean).join("-");
        } else {
            const person = res.data.person;
            form.full_name = person.razon_social;
            form.names = person.names;
            form.father_lastname = person.father_lastname;
            form.mother_lastname = person.mother_lastname;
            form.number = person.document_number ?? form.number;
        }

        Swal2.fire({
            title: "Datos cargados",
            text: isRuc.value ? "Se consulto SUNAT correctamente." : "Se consulto RENIEC correctamente.",
            icon: "success",
            padding: "2em",
            customClass: "sweet-alerts",
        });
    }).catch((error) => {
        if (error.response?.data?.errors) {
            Object.entries(error.response.data.errors).forEach(([field, messages]) => form.setError(field, messages[0]));
        }
    }).finally(() => {
        externalSearchLoading.value = false;
    });
};

const submit = () => {
    onlyNumbers();

    if (isEdit.value) {
        form.put(route("comm_clients_update", form.id), {
            preserveScroll: true,
            onSuccess: () => {
                Swal2.fire({
                    title: "Enhorabuena",
                    text: "Se actualizo correctamente",
                    icon: "success",
                    padding: "2em",
                    customClass: "sweet-alerts",
                });
            },
        });
        return;
    }

    form.post(route("comm_clients_store"), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            form.id = null;
            form.document_type_id = "1";
            form.gender = "M";

            Swal2.fire({
                title: "Enhorabuena",
                text: "Se guardo correctamente",
                icon: "success",
                padding: "2em",
                customClass: "sweet-alerts",
            });
        },
    });
};
</script>

<template>
    <FormSection @submitted="submit">
        <template #title>
            {{ isEdit ? "Editar cliente" : "Nuevo cliente" }}
        </template>

        <template #description>
            Registra clientes en la tabla de personas, evita duplicados buscando por documento antes de guardar y completa los campos con * porque son obligatorios.
        </template>

        <template #form>
            <div class="col-span-6 sm:col-span-2">
                <InputLabel value="Tipo de documento *" />
                <select v-model="form.document_type_id" class="form-select text-white-dark">
                    <option value="">Seleccionar</option>
                    <option v-for="item in identityDocumentTypes" :key="item.id" :value="String(item.id)">
                        {{ item.description }}
                    </option>
                </select>
                <InputError :message="form.errors.document_type_id" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-2">
                <InputLabel for="number" value="Numero de documento *" />
                <TextInput id="number" v-model="form.number" type="text" inputmode="numeric" pattern="[0-9]*" autofocus @input="onlyNumbers" />
                <InputError :message="form.errors.number" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-2">
                <div class="flex gap-2 mt-6">
                    <button
                        type="button"
                        class="btn btn-secondary flex-1"
                        :class="{ 'opacity-50': internalSearchLoading || searchProcessing }"
                        :disabled="searchProcessing"
                        @click="searchInternal"
                    >
                        <IconLoader v-if="internalSearchLoading" class="w-4 h-4 mr-2 animate-spin" />
                        Buscar BD
                    </button>
                    <button
                        type="button"
                        class="btn btn-success flex-1"
                        :class="{ 'opacity-50': externalSearchLoading || searchProcessing }"
                        :disabled="searchProcessing"
                        @click="searchExternal"
                    >
                        <IconLoader v-if="externalSearchLoading" class="w-4 h-4 mr-2 animate-spin" />
                        {{ isRuc ? "SUNAT" : "RENIEC" }}
                    </button>
                </div>
            </div>

            <div v-if="isRuc" class="col-span-6 sm:col-span-4">
                <InputLabel for="full_name" value="Razon social *" />
                <TextInput id="full_name" v-model="form.full_name" type="text" />
                <InputError :message="form.errors.full_name" class="mt-2" />
            </div>

            <template v-else>
                <div class="col-span-6 sm:col-span-2">
                    <InputLabel for="names" value="Nombres *" />
                    <TextInput id="names" v-model="form.names" type="text" />
                    <InputError :message="form.errors.names" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <InputLabel for="father_lastname" value="Apellido paterno *" />
                    <TextInput id="father_lastname" v-model="form.father_lastname" type="text" />
                    <InputError :message="form.errors.father_lastname" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <InputLabel for="mother_lastname" value="Apellido materno *" />
                    <TextInput id="mother_lastname" v-model="form.mother_lastname" type="text" />
                    <InputError :message="form.errors.mother_lastname" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <InputLabel for="birthdate" value="Fecha nacimiento *" />
                    <TextInput id="birthdate" v-model="form.birthdate" type="date" />
                    <InputError :message="form.errors.birthdate" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <InputLabel value="Genero *" />
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
                    <InputError :message="form.errors.gender" class="mt-2" />
                </div>
            </template>

            <div class="col-span-6 sm:col-span-2">
                <InputLabel value="Pais" />
                <Select
                    v-model:value="form.country_id"
                    :options="countryOptions"
                    :filter-option="filterOption"
                    allow-clear
                    show-search
                    style="width: 100%"
                />
                <InputError :message="form.errors.country_id" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-3">
                <InputLabel value="Ciudad" />
                <Select
                    v-model:value="form.ubigeo"
                    :options="ubigeoOptions"
                    :filter-option="filterOption"
                    allow-clear
                    show-search
                    style="width: 100%"
                    @change="syncUbigeoDescription"
                />
                <InputError :message="form.errors.ubigeo" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-3">
                <InputLabel for="address" value="Direccion" />
                <TextInput id="address" v-model="form.address" type="text" />
                <InputError :message="form.errors.address" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-2">
                <InputLabel for="email" value="Email" />
                <TextInput id="email" v-model="form.email" type="email" />
                <InputError :message="form.errors.email" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-1">
                <InputLabel for="telephone" value="Telefono" />
                <TextInput id="telephone" v-model="form.telephone" type="text" />
                <InputError :message="form.errors.telephone" class="mt-2" />
            </div>
        </template>

        <template #actions>
            <PrimaryButton :class="{ 'opacity-50': form.processing }" :disabled="form.processing">
                <IconLoader v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
                {{ isEdit ? "Actualizar" : "Guardar" }}
            </PrimaryButton>
            <Link :href="route('comm_clients')" class="btn btn-success ml-2">
                Ir al listado
            </Link>
        </template>
    </FormSection>
</template>
