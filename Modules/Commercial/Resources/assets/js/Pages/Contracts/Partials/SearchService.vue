<script setup>
import { computed, ref } from 'vue';
import ModalLarge from '@/Components/ModalLarge.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';
import Swal from 'sweetalert2';

const emit = defineEmits(['selected']);

const displayModal = ref(false);
const displayResults = ref(false);
const listSearch = ref('');
const services = ref([]);
const listLoading = ref(false);
const saveLoading = ref(false);
const savedService = ref(null);
const autoSelectAfterSave = ref(false);

const modalForm = ref({
    id: null,
    usine: '',
    interne: '',
    description: '',
    sale_prices: {
        high: '',
        medium: '',
        under: '',
    },
});

const errors = ref({});

const canSelect = computed(() => !!savedService.value?.id);

const resetModalForm = () => {
    modalForm.value = {
        id: null,
        usine: '',
        interne: '',
        description: '',
        sale_prices: { high: '', medium: '', under: '' },
    };
    savedService.value = null;
    errors.value = {};
};

const fillModalFromService = (service) => {
    const prices = service.sale_prices ?? {};
    modalForm.value.id = service.id ?? null;
    modalForm.value.usine = service.usine ?? '';
    modalForm.value.interne = service.interne ?? '';
    modalForm.value.description = service.description ?? '';
    modalForm.value.sale_prices = {
        high: prices.high ?? '',
        medium: prices.medium ?? '',
        under: prices.under ?? '',
    };
    savedService.value = service.id ? { ...service, sale_prices: prices } : null;
};

const openModal = () => {
    resetModalForm();
    listSearch.value = '';
    services.value = [];
    displayResults.value = false;
    autoSelectAfterSave.value = false;
    displayModal.value = true;
};

const openModalForEdit = async (serviceId) => {
    resetModalForm();
    listSearch.value = '';
    services.value = [];
    displayResults.value = false;
    autoSelectAfterSave.value = true;
    displayModal.value = true;

    try {
        const { data } = await axios.get(route('comm_contracts_services_show', serviceId));
        if (data.success && data.service) {
            fillModalFromService(data.service);
        }
    } catch {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo cargar el servicio.',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    }
};

const closeModal = () => {
    displayModal.value = false;
    displayResults.value = false;
};

const searchList = () => {
    listLoading.value = true;
    axios
        .post(route('comm_contracts_services_search'), {
            search: listSearch.value.trim(),
        })
        .then((res) => {
            if (res.data.success) {
                services.value = res.data.services ?? [];
                displayResults.value = true;
            }
        })
        .finally(() => {
            listLoading.value = false;
        });
};

const pickFromList = (service) => {
    fillModalFromService(service);
    displayResults.value = false;
    services.value = [];
    listSearch.value = service.description ?? '';
};

const saveService = () => {
    errors.value = {};
    if (!modalForm.value.interne?.trim()) {
        errors.value.interne = 'El código interno es obligatorio.';
    }
    if (!modalForm.value.description?.trim()) {
        errors.value.description = 'La descripción es obligatoria.';
    }
    if (modalForm.value.sale_prices.high === '' || modalForm.value.sale_prices.high === null) {
        errors.value.sale_prices_high = 'El precio de venta es obligatorio.';
    }
    if (Object.keys(errors.value).length) {
        return;
    }

    saveLoading.value = true;
    const payload = {
        usine: modalForm.value.usine,
        interne: modalForm.value.interne,
        description: modalForm.value.description,
        sale_prices: {
            high: modalForm.value.sale_prices.high,
            medium: modalForm.value.sale_prices.medium || null,
            under: modalForm.value.sale_prices.under || null,
        },
    };

    const request = modalForm.value.id
        ? axios.put(route('comm_contracts_services_update', modalForm.value.id), payload)
        : axios.post(route('comm_contracts_services_store'), payload);

    request
        .then((res) => {
            if (res.data.success && res.data.service) {
                fillModalFromService(res.data.service);
                Swal.fire({
                    icon: 'success',
                    title: 'Guardado',
                    text: res.data.message || 'Servicio guardado correctamente.',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                if (autoSelectAfterSave.value) {
                    selectService();
                }
            }
        })
        .catch((err) => {
            if (err.response?.data?.errors) {
                errors.value = err.response.data.errors;
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: err.response?.data?.message || 'No se pudo guardar el servicio.',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
            }
        })
        .finally(() => {
            saveLoading.value = false;
        });
};

const selectService = () => {
    if (!savedService.value?.id) {
        Swal.fire({
            icon: 'warning',
            title: 'Servicio incompleto',
            text: 'Guarde el servicio antes de seleccionarlo.',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
        return;
    }

    emit('selected', { ...savedService.value });
    closeModal();
};

const newService = () => {
    const interne = modalForm.value.interne;
    resetModalForm();
    modalForm.value.interne = interne;
    displayResults.value = false;
    services.value = [];
};

defineExpose({ openModal, openModalForEdit });
</script>

<template>
    <ModalLarge :show="displayModal" :on-close="closeModal" :icon="'/img/servicio-de-calidad.png'">
        <template #title>Buscar servicio</template>
        <template #message>
            Busque un servicio existente, regístrelo o edítelo y luego selecciónelo para el contrato.
        </template>
        <template #content>
            <div class="space-y-4">
                <div class="relative">
                    <form class="flex gap-0" @submit.prevent="searchList">
                        <input
                            v-model="listSearch"
                            type="search"
                            class="form-input flex-1 rounded-r-none"
                            placeholder="Buscar por código o descripción..."
                        />
                        <button type="submit" class="btn btn-secondary rounded-l-none" :disabled="listLoading">
                            <IconLoader v-if="listLoading" class="w-4 h-4 animate-spin" />
                            <span v-else>Buscar</span>
                        </button>
                    </form>
                    <div
                        v-show="displayResults && services.length"
                        class="absolute z-10 mt-1 w-full max-h-48 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-600 dark:bg-gray-800"
                    >
                        <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                            <li
                                v-for="service in services"
                                :key="service.id"
                                class="cursor-pointer p-3 text-sm hover:bg-gray-50 dark:hover:bg-gray-700"
                                @click="pickFromList(service)"
                            >
                                <span class="font-medium">{{ service.interne || 'SERV' }}</span>
                                — {{ service.description }}
                            </li>
                        </ul>
                    </div>
                    <p
                        v-if="displayResults && !services.length"
                        class="mt-2 text-sm text-gray-500"
                    >
                        No se encontraron servicios.
                    </p>
                </div>

                <div class="flex justify-end">
                    <button type="button" class="btn btn-outline-primary btn-sm" @click="newService">
                        Nuevo servicio
                    </button>
                </div>

                <div class="grid grid-cols-6 gap-4 border-t border-gray-200 pt-4 dark:border-gray-600">
                    <div class="col-span-6 sm:col-span-2">
                        <InputLabel value="Código SUNAT" />
                        <TextInput v-model="modalForm.usine" class="w-full" />
                        <InputError :message="errors.usine?.[0] || errors.usine" class="mt-1" />
                    </div>
                    <div class="col-span-6 sm:col-span-2">
                        <InputLabel value="Código interno *" />
                        <TextInput v-model="modalForm.interne" class="w-full" />
                        <InputError :message="errors.interne?.[0] || errors.interne" class="mt-1" />
                    </div>
                    <div class="col-span-6 sm:col-span-2">
                        <InputLabel value="Precio venta *" />
                        <TextInput
                            v-model="modalForm.sale_prices.high"
                            type="number"
                            step="0.01"
                            min="0"
                            class="w-full"
                        />
                        <InputError
                            :message="errors['sale_prices.high']?.[0] || errors.sale_prices_high"
                            class="mt-1"
                        />
                    </div>
                    <div class="col-span-6">
                        <InputLabel value="Descripción *" />
                        <textarea
                            v-model="modalForm.description"
                            rows="3"
                            class="form-textarea w-full"
                        />
                        <InputError :message="errors.description?.[0] || errors.description" class="mt-1" />
                    </div>
                    <div class="col-span-6 sm:col-span-3">
                        <InputLabel value="Precio venta medio" />
                        <TextInput
                            v-model="modalForm.sale_prices.medium"
                            type="number"
                            step="0.01"
                            min="0"
                            class="w-full"
                        />
                    </div>
                    <div class="col-span-6 sm:col-span-3">
                        <InputLabel value="Precio venta mínimo" />
                        <TextInput
                            v-model="modalForm.sale_prices.under"
                            type="number"
                            step="0.01"
                            min="0"
                            class="w-full"
                        />
                    </div>
                </div>
            </div>
        </template>
        <template #buttons>
            <PrimaryButton type="button" class="mr-2" :disabled="saveLoading" @click="saveService">
                <IconLoader v-if="saveLoading" class="mr-1 inline h-4 w-4 animate-spin" />
                Guardar
            </PrimaryButton>
            <PrimaryButton
                type="button"
                class="btn-success mr-2"
                :disabled="!canSelect"
                @click="selectService"
            >
                Seleccionar
            </PrimaryButton>
        </template>
    </ModalLarge>
</template>
