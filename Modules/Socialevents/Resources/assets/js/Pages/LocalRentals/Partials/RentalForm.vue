<script setup>

import { useForm, Link } from '@inertiajs/vue3';

import FormSection from '@/Components/FormSection.vue';

import InputError from '@/Components/InputError.vue';

import InputLabel from '@/Components/InputLabel.vue';

import PrimaryButton from '@/Components/PrimaryButton.vue';

import GreenButton from '@/Components/GreenButton.vue';

import TextInput from '@/Components/TextInput.vue';

import Keypad from '@/Components/Keypad.vue';

import ModalSmall from '@/Components/ModalSmall.vue';

import Swal2 from 'sweetalert2';

import { computed, ref, watch, onMounted } from 'vue';

import SearchClients from '../../Teams/Partials/SearchClients.vue';

import iconLoader from '@/Components/vristo/icon/icon-loader.vue';



const props = defineProps({

    locals: { type: Array, default: () => [] },

    activeRates: { type: Array, default: () => [] },

    eventTypes: { type: Array, default: () => [] },

    ubigeo: { type: Object, default: () => ({}) },

    documentTypes: { type: Object, default: () => ({}) },

    mode: { type: String, default: 'create' },

    rental: { type: Object, default: null },

});



const isEdit = computed(() => props.mode === 'edit' && props.rental);



const localRates = ref([...props.activeRates]);



const form = useForm({

    local_id: '',

    customer_id: '',

    customer_name: '',

    rental_rate_id: '',

    event_type: 'party',

    event_date: '',

    event_end_date: '',

    start_time: '',

    end_time: '',

    total_hours: 0,

    hourly_rate: 0,

    base_amount: 0,

    includes_tables_chairs: false,

    includes_food: false,

    beer_provided_by: 'none',

    paid_amount: 0,

    total_price: 0,

    notes: '',

    extras: [],

});



const serviceAmounts = ref({

    tables_chairs: { amount: 80, detail: '' },

    food: { amount: 200, detail: '' },

    beer: { amount: 120, detail: '' },

});



const displayModalClientSearch = ref(false);

const displayModalRate = ref(false);

const loadingRates = ref(false);

const savingRate = ref(false);

const rateErrors = ref({});



const rateForm = ref({

    name: '',

    hourly_rate: '',

});



const newExtraDescription = ref('');

const newExtraAmount = ref('');



const ratesForLocal = computed(() =>

    localRates.value.filter((rate) => String(rate.local_id) === String(form.local_id))

);



const totalHours = computed(() => {

    if (!form.event_date || !form.event_end_date || !form.start_time || !form.end_time) {

        return 0;

    }



    const start = new Date(`${form.event_date}T${form.start_time}`);

    const end = new Date(`${form.event_end_date}T${form.end_time}`);



    if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime()) || end <= start) {

        return 0;

    }



    return Math.round(((end - start) / 3600000) * 100) / 100;

});



const addDaysToDate = (dateStr, days) => {

    const [y, m, d] = dateStr.split('-').map(Number);

    const date = new Date(y, m - 1, d);

    date.setDate(date.getDate() + days);

    const year = date.getFullYear();

    const month = String(date.getMonth() + 1).padStart(2, '0');

    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;

};



const suggestEndDate = () => {

    if (!form.event_date || !form.start_time || !form.end_time) {

        return;

    }



    const [sh, sm] = form.start_time.split(':').map(Number);

    const [eh, em] = form.end_time.split(':').map(Number);

    const startMinutes = sh * 60 + sm;

    const endMinutes = eh * 60 + em;



    if (endMinutes <= startMinutes) {

        form.event_end_date = addDaysToDate(form.event_date, 1);

    } else if (form.event_end_date === addDaysToDate(form.event_date, 1)) {

        form.event_end_date = form.event_date;

    }

};



const manualExtras = computed(() =>

    form.extras.filter((item) => !item.service_key)

);



const extrasTotal = computed(() =>

    form.extras.reduce((sum, item) => sum + Number(item.amount || 0), 0)

);



const baseAmount = computed(() =>

    Math.round(totalHours.value * Number(form.hourly_rate || 0) * 100) / 100

);



const totalPrice = computed(() =>

    Math.round((baseAmount.value + extrasTotal.value) * 100) / 100

);



const minimumAdvance = computed(() =>

    Math.ceil((totalPrice.value || 0) * 0.2 * 100) / 100

);



const canSubmitPayment = computed(() =>

    Number(form.paid_amount) >= totalPrice.value || Number(form.paid_amount) >= minimumAdvance.value

);



const buildServiceDescription = (label, detail) => {

    const trimmed = (detail || '').trim();

    return trimmed ? `${label} — ${trimmed}` : label;

};



const syncServiceExtras = () => {

    const manual = form.extras.filter((item) => !item.service_key);



    const serviceExtras = [];



    if (form.includes_tables_chairs) {

        serviceExtras.push({

            service_key: 'tables_chairs',

            description: buildServiceDescription('Mesas y sillas', serviceAmounts.value.tables_chairs.detail),

            amount: Number(serviceAmounts.value.tables_chairs.amount) || 0,

            reason: 'planned',

        });

    }



    if (form.includes_food) {

        serviceExtras.push({

            service_key: 'food',

            description: buildServiceDescription('Servicio de comida', serviceAmounts.value.food.detail),

            amount: Number(serviceAmounts.value.food.amount) || 0,

            reason: 'planned',

        });

    }



    if (form.beer_provided_by === 'company') {

        serviceExtras.push({

            service_key: 'beer_company',

            description: buildServiceDescription('Cerveza (empresa)', serviceAmounts.value.beer.detail),

            amount: Number(serviceAmounts.value.beer.amount) || 0,

            reason: 'planned',

        });

    } else if (form.beer_provided_by === 'client') {

        serviceExtras.push({

            service_key: 'beer_client',

            description: buildServiceDescription('Cerveza (cliente)', serviceAmounts.value.beer.detail),

            amount: Number(serviceAmounts.value.beer.amount) || 0,

            reason: 'planned',

        });

    }



    form.extras = [...serviceExtras, ...manual];

};



watch(totalHours, (value) => {

    form.total_hours = value;

}, { immediate: true });



watch(() => form.event_date, (newDate, oldDate) => {

    if (!form.event_end_date || form.event_end_date === oldDate) {

        form.event_end_date = newDate;

    }

    suggestEndDate();

});



watch(() => [form.start_time, form.end_time], () => {

    suggestEndDate();

});



watch(baseAmount, (value) => {

    form.base_amount = value;

}, { immediate: true });



watch(totalPrice, (value) => {

    form.total_price = value;

}, { immediate: true });



watch(() => form.rental_rate_id, (rateId) => {

    const rate = localRates.value.find((item) => String(item.id) === String(rateId));

    form.hourly_rate = rate ? Number(rate.hourly_rate) : 0;

});



watch(() => form.local_id, (localId) => {

    form.rental_rate_id = '';

    form.hourly_rate = 0;



    if (localId) {

        fetchRatesForLocal(localId);

    }

});



watch(

    () => [

        form.includes_tables_chairs,

        form.includes_food,

        form.beer_provided_by,

        serviceAmounts.value.tables_chairs.amount,

        serviceAmounts.value.tables_chairs.detail,

        serviceAmounts.value.food.amount,

        serviceAmounts.value.food.detail,

        serviceAmounts.value.beer.amount,

        serviceAmounts.value.beer.detail,

    ],

    () => syncServiceExtras(),

    { deep: true }

);



const fetchRatesForLocal = async (localId) => {

    loadingRates.value = true;



    try {

        const response = await axios.get(route('even_alquiler_local_rates_by_local', localId));

        const fetched = response.data.rates || [];



        localRates.value = [

            ...localRates.value.filter((rate) => String(rate.local_id) !== String(localId)),

            ...fetched,

        ];

    } catch (error) {

        Swal2.fire({

            icon: 'error',

            title: 'Error',

            text: 'No se pudieron cargar las tarifas del local.',

            padding: '2em',

            customClass: 'sweet-alerts',

        });

    } finally {

        loadingRates.value = false;

    }

};



const openModalRate = () => {

    if (!form.local_id) {

        Swal2.fire({

            icon: 'warning',

            title: 'Seleccione un local',

            text: 'Primero elija el local para registrar la tarifa.',

            padding: '2em',

            customClass: 'sweet-alerts',

        });

        return;

    }



    rateErrors.value = {};

    rateForm.value = { name: '', hourly_rate: '' };

    displayModalRate.value = true;

};



const closeModalRate = () => {

    displayModalRate.value = false;

    rateErrors.value = {};

};



const saveRate = async () => {

    rateErrors.value = {};

    savingRate.value = true;



    try {

        const response = await axios.post(route('even_alquiler_local_rates_store'), {

            local_id: form.local_id,

            name: rateForm.value.name,

            hourly_rate: rateForm.value.hourly_rate,

        });



        const newRate = response.data.rate;

        localRates.value = [

            ...localRates.value.filter((rate) => rate.id !== newRate.id),

            newRate,

        ];



        form.rental_rate_id = newRate.id;

        form.hourly_rate = Number(newRate.hourly_rate);



        closeModalRate();



        Swal2.fire({

            title: 'Enhorabuena',

            text: 'Tarifa registrada correctamente',

            icon: 'success',

            padding: '2em',

            customClass: 'sweet-alerts',

        });

    } catch (error) {

        if (error.response?.status === 422) {

            rateErrors.value = error.response.data.errors || {};

        } else {

            Swal2.fire({

                icon: 'error',

                title: 'Error',

                text: 'No se pudo registrar la tarifa.',

                padding: '2em',

                customClass: 'sweet-alerts',

            });

        }

    } finally {

        savingRate.value = false;

    }

};



const openModalClientSearch = () => {

    displayModalClientSearch.value = true;

};



const closeModalClientSearch = () => {

    displayModalClientSearch.value = false;

};



const getDataClient = (data) => {

    form.customer_id = data.id;

    form.customer_name = data.full_name;

    displayModalClientSearch.value = false;

};



const addManualExtra = () => {

    if (!newExtraDescription.value || !Number(newExtraAmount.value)) {

        return;

    }



    form.extras.push({

        description: newExtraDescription.value,

        amount: Number(newExtraAmount.value),

        reason: 'planned',

    });



    newExtraDescription.value = '';

    newExtraAmount.value = '';

};



const removeManualExtra = (index) => {

    const manual = manualExtras.value[index];

    const globalIndex = form.extras.indexOf(manual);



    if (globalIndex >= 0) {

        form.extras.splice(globalIndex, 1);

    }

};



const initializeFromRental = () => {

    if (!props.rental) return;

    const rental = props.rental;

    form.local_id = rental.local_id;

    form.customer_id = rental.customer_id;

    form.customer_name = rental.customer?.full_name || '';

    form.rental_rate_id = rental.rental_rate_id || '';

    form.event_type = rental.event_type;

    form.event_date = rental.event_date?.substring?.(0, 10) || rental.event_date;

    form.event_end_date = (rental.event_end_date || rental.event_date)?.substring?.(0, 10) || rental.event_end_date;

    form.start_time = String(rental.start_time || '').substring(0, 5);

    form.end_time = String(rental.end_time || '').substring(0, 5);

    form.total_hours = rental.total_hours;

    form.hourly_rate = rental.hourly_rate;

    form.base_amount = rental.base_amount;

    form.includes_tables_chairs = rental.includes_tables_chairs;

    form.includes_food = rental.includes_food;

    form.beer_provided_by = rental.beer_provided_by;

    form.paid_amount = rental.paid_amount;

    form.total_price = rental.total_price;

    form.notes = rental.notes || '';

    form.extras = (rental.extra_charges || [])

        .filter((item) => item.phase === 'booking')

        .map((item) => ({

            description: item.description,

            amount: Number(item.amount),

            reason: item.reason || 'planned',

        }));

};



onMounted(() => {

    if (isEdit.value) {

        initializeFromRental();

    }

});



const submit = () => {

    syncServiceExtras();



    if (!isEdit.value && !canSubmitPayment.value) {

        Swal2.fire({

            icon: 'warning',

            title: 'Adelanto insuficiente',

            text: `El adelanto mínimo es S/ ${minimumAdvance.value.toFixed(2)} (20%).`,

            padding: '2em',

            customClass: 'sweet-alerts',

        });

        return;

    }



    const submitOptions = {

        preserveScroll: true,

        onSuccess: () => {

            Swal2.fire({

                title: 'Enhorabuena',

                text: isEdit.value ? 'La reserva se actualizó correctamente' : 'La reserva se registró correctamente',

                icon: 'success',

                padding: '2em',

                customClass: 'sweet-alerts',

            });

        },

    };



    if (isEdit.value) {

        form.put(route('even_alquiler_local_update', props.rental.id), submitOptions);

        return;

    }



    form.post(route('even_alquiler_local_store'), submitOptions);

};

</script>



<template>

    <FormSection @submitted="submit">

        <template #title>Datos de la reserva</template>

        <template #description>Complete la información del alquiler del local.</template>



        <template #form>

            <div class="col-span-6 sm:col-span-3">

                <InputLabel value="Local *" />

                <select v-model="form.local_id" class="form-select w-full">

                    <option value="">Seleccionar</option>

                    <option v-for="local in locals" :key="local.id" :value="local.id">

                        {{ local.description }}

                    </option>

                </select>

                <InputError :message="form.errors.local_id" class="mt-2" />

            </div>



            <div class="col-span-6 sm:col-span-3">

                <InputLabel value="Tarifa *" />

                <div class="flex gap-2 items-start">

                    <div class="flex-1 relative">

                        <select

                            v-model="form.rental_rate_id"

                            class="form-select w-full"

                            :disabled="!form.local_id || loadingRates"

                        >

                            <option value="">{{ loadingRates ? 'Cargando tarifas...' : 'Seleccionar' }}</option>

                            <option v-for="rate in ratesForLocal" :key="rate.id" :value="rate.id">

                                {{ rate.name }} — S/ {{ Number(rate.hourly_rate).toFixed(2) }}/h

                            </option>

                        </select>

                        <icon-loader

                            v-if="loadingRates"

                            class="w-4 h-4 animate-spin absolute right-8 top-3 text-gray-400"

                        />

                    </div>

                    <button

                        type="button"

                        class="btn btn-primary whitespace-nowrap"

                        :disabled="!form.local_id"

                        @click="openModalRate"

                    >

                        + Tarifa

                    </button>

                </div>

                <InputError :message="form.errors.rental_rate_id" class="mt-2" />

            </div>



            <div class="col-span-6 sm:col-span-3">

                <InputLabel value="Cliente *" />

                <div class="flex gap-2">

                    <TextInput :model-value="form.customer_name" type="text" class="w-full" readonly placeholder="Seleccione un cliente" />

                    <button type="button" class="btn btn-primary" @click="openModalClientSearch">Buscar</button>

                </div>

                <InputError :message="form.errors.customer_id" class="mt-2" />

            </div>



            <div class="col-span-6 sm:col-span-3">

                <InputLabel value="Tipo de evento *" />

                <select v-model="form.event_type" class="form-select w-full">

                    <option v-for="type in eventTypes" :key="type.value" :value="type.value">

                        {{ type.label }}

                    </option>

                </select>

                <InputError :message="form.errors.event_type" class="mt-2" />

            </div>



            <div class="col-span-6 sm:col-span-1">

                <InputLabel value="Fecha inicio *" />

                <input v-model="form.event_date" type="date" class="form-input w-full" />

                <InputError :message="form.errors.event_date" class="mt-2" />

            </div>



            <div class="col-span-6 sm:col-span-1">

                <InputLabel value="Hora inicio *" />

                <input v-model="form.start_time" type="time" class="form-input w-full" />

                <InputError :message="form.errors.start_time" class="mt-2" />

            </div>



            <div class="col-span-6 sm:col-span-1">

                <InputLabel value="Fecha fin *" />

                <input v-model="form.event_end_date" type="date" class="form-input w-full" :min="form.event_date" />

                <InputError :message="form.errors.event_end_date" class="mt-2" />

                <p class="text-xs text-gray-500 mt-1">Use el día siguiente si la fiesta termina en la madrugada.</p>

            </div>



            <div class="col-span-6 sm:col-span-1">

                <InputLabel value="Hora fin *" />

                <input v-model="form.end_time" type="time" class="form-input w-full" />

                <InputError :message="form.errors.end_time" class="mt-2" />

            </div>



            <div class="col-span-6 sm:col-span-1">

                <InputLabel value="Total horas" />

                <TextInput :model-value="totalHours > 0 ? totalHours.toFixed(2) : ''" type="text" readonly placeholder="0.00" />

            </div>



            <div class="col-span-6 sm:col-span-1">

                <InputLabel value="Tarifa por hora" />

                <TextInput :model-value="form.hourly_rate ? Number(form.hourly_rate).toFixed(2) : ''" type="text" readonly placeholder="0.00" />

            </div>



            <div class="col-span-6 sm:col-span-2">

                <InputLabel value="Monto base" />

                <TextInput :model-value="baseAmount > 0 ? baseAmount.toFixed(2) : ''" type="text" readonly placeholder="0.00" />

            </div>



            <div class="col-span-6">

                <InputLabel value="Servicios incluidos" />

                <div class="mt-3 space-y-3 panel p-4">

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">

                        <div class="md:col-span-3 flex items-center gap-2">

                            <input v-model="form.includes_tables_chairs" type="checkbox" class="form-checkbox" />

                            <span>Mesas y sillas</span>

                        </div>

                        <div class="md:col-span-3">

                            <InputLabel value="Monto (S/)" />

                            <TextInput

                                v-model="serviceAmounts.tables_chairs.amount"

                                type="number"

                                min="0"

                                step="0.01"

                                class="w-full"

                                :disabled="!form.includes_tables_chairs"

                            />

                        </div>

                        <div class="md:col-span-6">

                            <InputLabel value="Detalle (cantidad, unidades)" />

                            <TextInput

                                v-model="serviceAmounts.tables_chairs.detail"

                                type="text"

                                class="w-full"

                                placeholder="Ej. 10 mesas, 80 sillas"

                                :disabled="!form.includes_tables_chairs"

                            />

                        </div>

                    </div>



                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end border-t border-white-dark/10 pt-3">

                        <div class="md:col-span-3 flex items-center gap-2">

                            <input v-model="form.includes_food" type="checkbox" class="form-checkbox" />

                            <span>Comida</span>

                        </div>

                        <div class="md:col-span-3">

                            <InputLabel value="Monto (S/)" />

                            <TextInput

                                v-model="serviceAmounts.food.amount"

                                type="number"

                                min="0"

                                step="0.01"

                                class="w-full"

                                :disabled="!form.includes_food"

                            />

                        </div>

                        <div class="md:col-span-6">

                            <InputLabel value="Detalle (platos, porciones)" />

                            <TextInput

                                v-model="serviceAmounts.food.detail"

                                type="text"

                                class="w-full"

                                placeholder="Ej. 50 platos, buffet"

                                :disabled="!form.includes_food"

                            />

                        </div>

                    </div>



                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end border-t border-white-dark/10 pt-3">

                        <div class="md:col-span-3">

                            <InputLabel value="Cerveza" />

                            <select v-model="form.beer_provided_by" class="form-select w-full">

                                <option value="none">No incluye</option>

                                <option value="company">Empresa</option>

                                <option value="client">Cliente</option>

                            </select>

                        </div>

                        <div class="md:col-span-3">

                            <InputLabel value="Monto (S/)" />

                            <TextInput

                                v-model="serviceAmounts.beer.amount"

                                type="number"

                                min="0"

                                step="0.01"

                                class="w-full"

                                :disabled="form.beer_provided_by === 'none'"

                            />

                        </div>

                        <div class="md:col-span-6">

                            <InputLabel value="Detalle (cajas, unidades)" />

                            <TextInput

                                v-model="serviceAmounts.beer.detail"

                                type="text"

                                class="w-full"

                                placeholder="Ej. 5 cajas, 60 unidades"

                                :disabled="form.beer_provided_by === 'none'"

                            />

                        </div>

                    </div>

                </div>

            </div>



            <div class="col-span-6">

                <InputLabel value="Cargos extra al reservar" />

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-2">

                    <TextInput v-model="newExtraDescription" type="text" placeholder="Descripción" />

                    <TextInput v-model="newExtraAmount" type="number" min="0" step="0.01" placeholder="Monto" />

                    <button type="button" class="btn btn-outline-primary" @click="addManualExtra">Agregar cargo</button>

                </div>

                <div v-if="manualExtras.length" class="mt-3 panel p-3">

                    <div

                        v-for="(extra, index) in manualExtras"

                        :key="index"

                        class="flex justify-between items-center py-1 border-b border-white-dark/10"

                    >

                        <span>{{ extra.description }}</span>

                        <div class="flex items-center gap-3">

                            <span>S/ {{ Number(extra.amount).toFixed(2) }}</span>

                            <button type="button" class="text-danger text-sm" @click="removeManualExtra(index)">Quitar</button>

                        </div>

                    </div>

                </div>

            </div>



            <div class="col-span-6 sm:col-span-3">

                <InputLabel value="Total *" />

                <TextInput :model-value="totalPrice.toFixed(2)" type="text" readonly />

                <InputError :message="form.errors.total_price" class="mt-2" />

            </div>



            <div v-if="isEdit" class="col-span-6 sm:col-span-3 panel p-3">

                <InputLabel value="Pagado / Saldo" />

                <p class="font-semibold">S/ {{ Number(rental.paid_amount || 0).toFixed(2) }} / S/ {{ Number(rental.balance_amount || 0).toFixed(2) }}</p>

                <p class="text-xs text-gray-500 mt-1">Los abonos se registran desde «Ver pagos» en el listado.</p>

            </div>



            <div v-if="!isEdit" class="col-span-6 sm:col-span-3">

                <InputLabel value="Monto pagado (adelanto) *" />

                <TextInput v-model="form.paid_amount" type="number" min="0" step="0.01" />

                <p class="text-sm text-gray-500 mt-1">

                    Adelanto mínimo: S/ {{ minimumAdvance.toFixed(2) }} (20%)

                </p>

                <InputError :message="form.errors.paid_amount" class="mt-2" />

            </div>



            <div class="col-span-6">

                <InputLabel value="Notas" />

                <textarea v-model="form.notes" class="form-textarea w-full" rows="3" />

            </div>

        </template>



        <template #actions>

            <Keypad>

                <template #botones>
                    <PrimaryButton
                        :disabled="form.processing || (!isEdit && !canSubmitPayment)"
                        :class="{ 'opacity-25': form.processing }"
                    >
                        <icon-loader v-show="form.processing" class="w-4 h-4 animate-spin mr-1" />

                        {{ isEdit ? 'Actualizar reserva' : 'Guardar reserva' }}

                    </PrimaryButton>
                    <Link :href="route('even_alquiler_local_index')" class="btn btn-success">

                        Cancelar

                    </Link>

                </template>

            </Keypad>

        </template>

    </FormSection>



    <ModalSmall :show="displayModalRate" :on-close="closeModalRate" :icon="'/img/etiqueta-de-precio.png'">

        <template #title>Nueva tarifa</template>

        <template #message>Registre una tarifa por hora para el local seleccionado.</template>

        <template #content>

            <div class="space-y-4">

                <div>

                    <InputLabel value="Nombre *" />

                    <TextInput v-model="rateForm.name" type="text" class="w-full" placeholder="Ej. Tarifa fin de semana" />

                    <InputError :message="rateErrors.name?.[0]" class="mt-2" />

                </div>

                <div>

                    <InputLabel value="Monto por hora (S/) *" />

                    <TextInput v-model="rateForm.hourly_rate" type="number" min="0.01" step="0.01" class="w-full" />

                    <InputError :message="rateErrors.hourly_rate?.[0]" class="mt-2" />

                </div>

            </div>

        </template>

        <template #buttons>

            <GreenButton :disabled="savingRate" @click="saveRate">

                <icon-loader v-if="savingRate" class="w-4 h-4 animate-spin mr-1" />

                Guardar

            </GreenButton>

        </template>

    </ModalSmall>



    <SearchClients

        :display="displayModalClientSearch"

        :close-modal-client="closeModalClientSearch"

        :document-types="documentTypes"

        :ubigeo="ubigeo"

        @client-id="getDataClient"

    />

</template>


