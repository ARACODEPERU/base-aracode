<script setup>
import { ref } from 'vue';
import ModalLarge from '@/Components/ModalLarge.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';
import IconDatabaseSearch from '@/Components/vristo/icon/icon-database-search.vue';
import IconCompany from '@/Components/vristo/icon/icon-company.vue';
import { bibSwal } from '../../../../utils/bibSwal.js';

const emit = defineEmits(['selected']);

const displayModal = ref(false);
const displayResults = ref(false);
const listSearch = ref('');
const persons = ref([]);
const listLoading = ref(false);
const dbLoading = ref(false);
const sunatLoading = ref(false);
const saveLoading = ref(false);

const modalForm = ref({
    id: null,
    number: '',
    full_name: '',
    email: '',
    telephone: '',
    address: '',
    ubigeo: null,
    ubigeo_description: null,
});

const errors = ref({});

const openModal = () => {
    displayModal.value = true;
};

const closeModal = () => {
    displayModal.value = false;
    displayResults.value = false;
};

const fillModalFromPerson = (person) => {
    modalForm.value.id = person.id ?? null;
    modalForm.value.number = person.number ?? person.numero_documento ?? '';
    modalForm.value.full_name = person.full_name ?? person.razon_social ?? '';
    modalForm.value.email = person.email ?? '';
    modalForm.value.telephone = person.telephone ?? '';
    modalForm.value.address = person.address ?? (person.direccion === '-' ? '' : person.direccion) ?? '';
    modalForm.value.ubigeo = person.ubigeo ? String(person.ubigeo) : null;
    modalForm.value.ubigeo_description = person.ubigeo_description
        ?? [person.departamento, person.provincia, person.distrito].filter(Boolean).join('-')
        ?? null;
};

const searchList = () => {
    if (!listSearch.value.trim()) {
        return;
    }
    listLoading.value = true;
    axios
        .post(route('bib_organizations_search_list'), { search: listSearch.value.trim() })
        .then((res) => {
            if (res.data.status) {
                persons.value = res.data.persons;
                displayResults.value = true;
            } else {
                persons.value = [];
                displayResults.value = false;
                bibSwal({ icon: 'warning', title: 'Sin resultados', text: res.data.alert });
            }
        })
        .finally(() => {
            listLoading.value = false;
        });
};

const pickFromList = (person) => {
    fillModalFromPerson(person);
    displayResults.value = false;
    persons.value = [];
    listSearch.value = person.full_name;
};

const searchInternal = () => {
    const number = String(modalForm.value.number).replace(/\D/g, '');
    if (number.length !== 11) {
        bibSwal({ icon: 'warning', title: 'RUC inválido', text: 'El RUC debe tener 11 dígitos.' });
        return;
    }
    dbLoading.value = true;
    axios
        .post(route('bib_organizations_search_internal'), { number })
        .then((res) => {
            if (res.data.status) {
                fillModalFromPerson(res.data.person);
                bibSwal({ icon: 'success', title: 'Encontrado', text: 'Datos cargados desde la base de datos.' });
            } else {
                bibSwal({ icon: 'info', title: 'No encontrado', text: res.data.alert });
            }
        })
        .finally(() => {
            dbLoading.value = false;
        });
};

const searchSunat = () => {
    const number = String(modalForm.value.number).replace(/\D/g, '');
    if (number.length !== 11) {
        bibSwal({ icon: 'warning', title: 'RUC inválido', text: 'El RUC debe tener 11 dígitos.' });
        return;
    }
    sunatLoading.value = true;
    axios
        .post(route('bib_organizations_search_sunat'), { number })
        .then((res) => {
            if (!res.data.success) {
                bibSwal({ icon: 'error', title: 'SUNAT', text: res.data.error || 'No se pudo consultar.' });
                return;
            }
            if (res.data.source === 'database') {
                fillModalFromPerson(res.data.person);
                bibSwal({ icon: 'info', title: 'Ya existe', text: res.data.message });
                return;
            }
            const person = res.data.person;
            modalForm.value.number = person.numero_documento ?? number;
            modalForm.value.full_name = person.razon_social ?? '';
            modalForm.value.address = person.direccion === '-' ? '' : person.direccion ?? '';
            modalForm.value.ubigeo = person.ubigeo ? String(person.ubigeo) : null;
            modalForm.value.ubigeo_description = [person.departamento, person.provincia, person.distrito]
                .filter(Boolean)
                .join('-');
            bibSwal({ icon: 'success', title: 'SUNAT', text: 'Datos consultados correctamente.' });
        })
        .finally(() => {
            sunatLoading.value = false;
        });
};

const savePerson = () => {
    errors.value = {};
    const number = String(modalForm.value.number).replace(/\D/g, '');
    if (!modalForm.value.full_name?.trim()) {
        errors.value.full_name = 'La razón social es obligatoria.';
        return;
    }
    if (number.length !== 11) {
        errors.value.number = 'El RUC debe tener 11 dígitos.';
        return;
    }
    saveLoading.value = true;
    axios
        .post(route('bib_organizations_search_save_person'), {
            id: modalForm.value.id,
            number,
            full_name: modalForm.value.full_name,
            email: modalForm.value.email,
            telephone: modalForm.value.telephone,
            address: modalForm.value.address,
            ubigeo: modalForm.value.ubigeo,
            ubigeo_description: modalForm.value.ubigeo_description,
        })
        .then((res) => {
            if (res.data.success) {
                fillModalFromPerson(res.data.person);
                bibSwal({ icon: 'success', title: 'Guardado', text: 'Cliente registrado o actualizado.' });
            }
        })
        .catch((err) => {
            if (err.response?.data?.errors) {
                errors.value = err.response.data.errors;
            }
        })
        .finally(() => {
            saveLoading.value = false;
        });
};

const selectOrganization = () => {
    if (!modalForm.value.full_name?.trim()) {
        bibSwal({ icon: 'warning', title: 'Datos incompletos', text: 'Complete la razón social antes de seleccionar.' });
        return;
    }
    const number = String(modalForm.value.number).replace(/\D/g, '');
    emit('selected', {
        person_id: modalForm.value.id,
        name: modalForm.value.full_name.trim(),
        document_number: number || modalForm.value.number,
        email: modalForm.value.email || '',
        phone: modalForm.value.telephone || '',
    });
    closeModal();
};

defineExpose({ openModal });
</script>

<template>
    <div>
        <button type="button" class="btn btn-info" @click="openModal">
            Buscar organización
        </button>

        <ModalLarge :show="displayModal" :on-close="closeModal" icon="/img/comunidad.png">
            <template #title>Buscar organización</template>
            <template #message>
                Clientes con RUC en la base de datos. Consulte SUNAT, registre o edite y luego seleccione.
            </template>
            <template #content>
                <div class="space-y-4">
                    <div class="relative">
                        <form class="flex gap-0" @submit.prevent="searchList">
                            <input
                                v-model="listSearch"
                                type="search"
                                class="form-input flex-1 rounded-r-none"
                                placeholder="Buscar por RUC o razón social..."
                            />
                            <button type="submit" class="btn btn-secondary rounded-l-none" :disabled="listLoading">
                                <IconLoader v-if="listLoading" class="w-4 h-4 animate-spin" />
                                <span v-else>Buscar</span>
                            </button>
                        </form>
                        <div
                            v-show="displayResults && persons.length"
                            class="absolute z-10 mt-1 w-full border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 shadow-lg max-h-48 overflow-y-auto"
                        >
                            <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                                <li
                                    v-for="person in persons"
                                    :key="person.id"
                                    class="p-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer text-sm"
                                    @click="pickFromList(person)"
                                >
                                    <span class="font-medium">{{ person.number }}</span>
                                    — {{ person.full_name }}
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="grid grid-cols-6 gap-4 border-t border-gray-200 dark:border-gray-600 pt-4">
                        <div class="col-span-6 sm:col-span-2">
                            <InputLabel value="RUC *" />
                            <input
                                v-model="modalForm.number"
                                type="text"
                                maxlength="11"
                                class="form-input w-full"
                                placeholder="11 dígitos"
                            />
                            <InputError :message="errors.number?.[0] || errors.number" class="mt-1" />
                        </div>
                        <div class="flex items-end gap-2 col-span-6 sm:col-span-4">
                            <button type="button" class="btn btn-primary" :disabled="sunatLoading" @click="searchSunat">
                                <IconLoader v-if="sunatLoading" class="w-4 h-4 animate-spin mr-1" />
                                <IconCompany v-else class="w-4 h-4 mr-1" />
                                SUNAT
                            </button>
                            <button type="button" class="btn btn-danger" :disabled="dbLoading" @click="searchInternal">
                                <IconLoader v-if="dbLoading" class="w-4 h-4 animate-spin mr-1" />
                                <IconDatabaseSearch v-else class="w-5 h-5 mr-1" />
                                Base de datos
                            </button>
                        </div>
                        <div class="col-span-6">
                            <InputLabel value="Razón social *" />
                            <TextInput v-model="modalForm.full_name" class="w-full" />
                            <InputError :message="errors.full_name?.[0] || errors.full_name" class="mt-1" />
                        </div>
                        <div class="col-span-6 sm:col-span-3">
                            <InputLabel value="Correo" />
                            <TextInput v-model="modalForm.email" type="email" class="w-full" />
                        </div>
                        <div class="col-span-6 sm:col-span-3">
                            <InputLabel value="Teléfono" />
                            <TextInput v-model="modalForm.telephone" class="w-full" />
                        </div>
                        <div class="col-span-6">
                            <InputLabel value="Dirección" />
                            <TextInput v-model="modalForm.address" class="w-full" />
                        </div>
                    </div>
                </div>
            </template>
            <template #buttons>
                <PrimaryButton type="button" class="mr-2" :disabled="saveLoading" @click="savePerson">
                    <IconLoader v-if="saveLoading" class="w-4 h-4 animate-spin mr-1 inline" />
                    Guardar en clientes
                </PrimaryButton>
                <PrimaryButton type="button" class="mr-2 btn-success" @click="selectOrganization">
                    Seleccionar
                </PrimaryButton>
            </template>
        </ModalLarge>
    </div>
</template>
