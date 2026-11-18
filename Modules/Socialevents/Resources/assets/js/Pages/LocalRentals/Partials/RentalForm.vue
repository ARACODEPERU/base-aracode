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

import { computed, ref, watch, onMounted } from 'vue';

import SearchClients from '../../Teams/Partials/SearchClients.vue';

import AreasPanel from '../../Local/Partials/AreasPanel.vue';

import ModalLarge from '@/Components/ModalLarge.vue';

import iconLoader from '@/Components/vristo/icon/icon-loader.vue';



const props = defineProps({

    locals: { type: Array, default: () => [] },

    activeRates: { type: Array, default: () => [] },

    eventTypes: { type: Array, default: () => [] },

    areaTypes: { type: Array, default: () => [] },
    complexes: { type: Array, default: () => [] },

    ubigeo: { type: Object, default: () => ({}) },

    documentTypes: { type: Object, default: () => ({}) },

    mode: { type: String, default: 'create' },

    rental: { type: Object, default: null },

});



const isEdit = computed(() => props.mode === 'edit' && props.rental);



const localRates = ref([...props.activeRates]);



// Cada área seleccionada es una línea con sus propias fechas, tarifa y extras.
const form = useForm({

    local_id: '',

    customer_id: '',

    customer_name: '',

    event_type: 'party',

    includes_tables_chairs: false,

    includes_food: false,

    beer_provided_by: 'none',

    paid_amount: 0,

    total_price: 0,

    notes: '',

    areas: [],

    // Torneo: monto fijo para toda la serie. Solo se usa en áreas de campo deportivo.
    tournament: null,

});

const tournamentMode = ref(false);

const tournamentForm = ref({
    area_id: '',
    venue_name: '',
    start_date: '',
    end_date: '',
    day_of_week: 0,
    start_time: '',
    end_time: '',
    total_amount: 0,
});



const localAreas = ref([]);

const loadingAreas = ref(false);

// Solo los campos deportivos admiten torneo.
const sportsFieldAreas = computed(() => localAreas.value.filter((area) => area.area_type === 'sports_field'));

// Nombres de día para el selector de torneo (0 = domingo, igual que PHP Carbon).
const weekDayNames = {
    0: 'Domingo',
    1: 'Lunes',
    2: 'Martes',
    3: 'Miércoles',
    4: 'Jueves',
    5: 'Viernes',
    6: 'Sábado',
};

let lineIdSeq = 0;

const newLine = (areaId = '') => ({

    key: ++lineIdSeq,

    area_id: areaId,

    rental_rate_id: '',

    event_date: '',

    event_end_date: '',

    start_time: '',

    end_time: '',

    hourly_rate: 0,

    services: {

        tables_chairs: { on: false, amount: 80, detail: '' },

        food: { on: false, amount: 200, detail: '' },

        beer: { by: 'none', amount: 120, detail: '' },

    },

    manualExtras: [],

    newExtraDescription: '',

    newExtraAmount: '',

});



const buildServiceDescription = (label, detail) => {

    const trimmed = (detail || '').trim();

    return trimmed ? `${label} — ${trimmed}` : label;

};



// Extras de la línea: servicios marcados + cargos manuales. Van en el área de la línea.
const lineExtras = (line) => {

    const extras = [];

    if (line.services.tables_chairs.on) {

        extras.push({

            description: buildServiceDescription('Mesas y sillas', line.services.tables_chairs.detail),

            amount: Number(line.services.tables_chairs.amount) || 0,

            reason: 'planned',

        });

    }

    if (line.services.food.on) {

        extras.push({

            description: buildServiceDescription('Servicio de comida', line.services.food.detail),

            amount: Number(line.services.food.amount) || 0,

            reason: 'planned',

        });

    }

    if (line.services.beer.by !== 'none') {

        const label = line.services.beer.by === 'company' ? 'Cerveza (empresa)' : 'Cerveza (cliente)';

        extras.push({

            description: buildServiceDescription(label, line.services.beer.detail),

            amount: Number(line.services.beer.amount) || 0,

            reason: 'planned',

        });

    }

    return [...extras, ...line.manualExtras];

};



// Modal largo para agregar o editar las áreas del local seleccionado.
const displayModalAreas = ref(false);

const displayModalClientSearch = ref(false);

const displayModalRate = ref(false);

const loadingRates = ref(false);

const savingRate = ref(false);

const rateErrors = ref({});



const rateForm = ref({

    name: '',

    hourly_rate: '',

    area_id: '',

});



const ratesForLocal = computed(() =>

    localRates.value.filter((rate) => String(rate.local_id) === String(form.local_id))

);



const addDaysToDate = (dateStr, days) => {

    const [y, m, d] = dateStr.split('-').map(Number);

    const date = new Date(y, m - 1, d);

    date.setDate(date.getDate() + days);

    const year = date.getFullYear();

    const month = String(date.getMonth() + 1).padStart(2, '0');

    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;

};



// Sugiere la fecha de fin de una línea cuando la hora de fin es anterior a la de inicio (madrugada).
const suggestLineEndDate = (line) => {

    if (!line.event_date || !line.start_time || !line.end_time) {

        return;

    }

    const [sh, sm] = line.start_time.split(':').map(Number);

    const [eh, em] = line.end_time.split(':').map(Number);

    if (eh * 60 + em <= sh * 60 + sm) {

        line.event_end_date = addDaysToDate(line.event_date, 1);

    } else if (line.event_end_date === addDaysToDate(line.event_date, 1)) {

        line.event_end_date = line.event_date;

    }

};



const lineErrors = (index, field) => form.errors[`areas.${index}.${field}`];



const lineTotalHours = (line) => {

    if (!line.event_date || !line.event_end_date || !line.start_time || !line.end_time) {

        return 0;

    }

    const start = new Date(`${line.event_date}T${line.start_time}`);

    const end = new Date(`${line.event_end_date}T${line.end_time}`);

    if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime()) || end <= start) {

        return 0;

    }

    return Math.round(((end - start) / 3600000) * 100) / 100;

};



const lineBaseAmount = (line) =>

    Math.round(lineTotalHours(line) * Number(line.hourly_rate || 0) * 100) / 100;



const lineExtrasTotal = (line) =>

    lineExtras(line).reduce((sum, item) => sum + Number(item.amount || 0), 0);



const lineSubtotal = (line) =>

    Math.round((lineBaseAmount(line) + lineExtrasTotal(line)) * 100) / 100;



const totalHours = computed(() =>

    form.areas.reduce((sum, line) => sum + lineTotalHours(line), 0)

);



const baseAmount = computed(() =>

    form.areas.reduce((sum, line) => sum + lineBaseAmount(line), 0)

);



const totalPrice = computed(() =>

    Math.round(form.areas.reduce((sum, line) => sum + lineSubtotal(line), 0) * 100) / 100

);



const minimumAdvance = computed(() =>

    Math.ceil((totalPrice.value || 0) * 0.2 * 100) / 100

);



const canSubmitPayment = computed(() =>

    Number(form.paid_amount) >= totalPrice.value || Number(form.paid_amount) >= minimumAdvance.value

);



// Al cambiar el local se reinician las líneas, porque sus áreas y tarifas dependen del local.
// En edición el local viene cargado: no se borran las líneas guardadas al iniciar.
watch(() => form.local_id, (localId) => {

    // En edición, volver al local original conserva las líneas guardadas.
    if (isEdit.value && String(localId) === String(props.rental.local_id)) {
        return;
    }

    form.areas = [];

    localAreas.value = [];

    if (localId) {

        fetchAreasForLocal(localId);

        fetchRatesForLocal(localId);

    }

});



// Sincroniza la tarifa por hora de cada línea con la tarifa elegida.
watch(

    () => form.areas.map((line) => `${line.rental_rate_id}`).join('|'),

    () => {

        form.areas.forEach((line) => {

            const rate = localRates.value.find((item) => String(item.id) === String(line.rental_rate_id));

            if (rate) {
                line.hourly_rate = Number(rate.hourly_rate);
            }

        });

    }

);



const fetchAreasForLocal = async (localId) => {

    loadingAreas.value = true;

    try {

        const response = await axios.get(route('even_alquiler_local_areas_by_local', localId));

        localAreas.value = response.data.areas || [];

    } catch (error) {

        window.alertaSistema('Error', 'No se pudieron cargar las áreas del local.', 'error');

    } finally {

        loadingAreas.value = false;

    }

};



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

        window.alertaSistema('Error', 'No se pudieron cargar las tarifas del local.', 'error');

    } finally {

        loadingRates.value = false;

    }

};



// Línea del área para la que se crea la tarifa (la tarifa queda ligada a esa área).
const rateTargetLine = ref(null);

const openModalRate = (line) => {

    if (!form.local_id) {

        window.alertaSistema('Seleccione un local', 'Primero elija el local para registrar la tarifa.', 'warning');

        return;

    }

    rateTargetLine.value = line;

    rateErrors.value = {};

    rateForm.value = { name: '', hourly_rate: '', area_id: line?.area_id || '' };

    displayModalRate.value = true;

};



// Cierra el modal de áreas.
const closeModalAreas = () => {
    displayModalAreas.value = false;

    // Recarga las áreas para reflejar altas, ediciones o bajas hechas en el modal.
    if (form.local_id) {
        fetchAreasForLocal(form.local_id);
    }
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

            area_id: rateForm.value.area_id,

            name: rateForm.value.name,

            hourly_rate: rateForm.value.hourly_rate,

        });



        const newRate = response.data.rate;

        localRates.value = [

            ...localRates.value.filter((rate) => rate.id !== newRate.id),

            newRate,

        ];



        if (rateTargetLine.value) {

            rateTargetLine.value.rental_rate_id = newRate.id;

            rateTargetLine.value.hourly_rate = Number(newRate.hourly_rate);

        }



        closeModalRate();

        window.alertaSistema('Enhorabuena', 'Tarifa registrada correctamente', 'success');

    } catch (error) {

        if (error.response?.status === 422) {

            rateErrors.value = error.response.data.errors || {};

        } else {

            window.alertaSistema('Error', 'No se pudo registrar la tarifa.', 'error');

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



// Un área ya elegida no se repite; cada una se agrega como una línea propia.
const isAreaSelected = (areaId) =>

    form.areas.some((line) => String(line.area_id) === String(areaId));



const toggleArea = (area) => {

    const index = form.areas.findIndex((line) => String(line.area_id) === String(area.id));



    if (index >= 0) {

        form.areas.splice(index, 1);

        return;

    }



    form.areas.push(newLine(area.id));

};



const removeLine = (index) => {

    form.areas.splice(index, 1);

};



const ratesForArea = (areaId) =>

    ratesForLocal.value.filter((rate) => String(rate.area_id) === String(areaId));



const areaName = (areaId) =>

    localAreas.value.find((area) => String(area.id) === String(areaId))?.name

    || props.rental?.items?.find((item) => String(item.area_id) === String(areaId))?.area?.name

    || 'Área';



const addManualExtraToLine = (line) => {

    if (!line.newExtraDescription || !Number(line.newExtraAmount)) {

        return;

    }



    line.manualExtras.push({

        description: line.newExtraDescription,

        amount: Number(line.newExtraAmount),

        reason: 'planned',

    });



    line.newExtraDescription = '';

    line.newExtraAmount = '';

};



const removeManualExtraFromLine = (line, index) => {

    line.manualExtras.splice(index, 1);

};



const initializeFromRental = () => {

    if (!props.rental) return;

    const rental = props.rental;

    form.local_id = rental.local_id;

    form.customer_id = rental.customer_id;

    form.customer_name = rental.customer?.full_name || '';

    form.event_type = rental.event_type;

    form.includes_tables_chairs = rental.includes_tables_chairs;

    form.includes_food = rental.includes_food;

    form.beer_provided_by = rental.beer_provided_by;

    form.paid_amount = rental.paid_amount;

    form.total_price = rental.total_price;

    form.notes = rental.notes || '';

    // Cada área guardada vuelve a ser una línea. Sus extras se cargan como cargos manuales.
    form.areas = (rental.items || []).map((item) => {

        const line = newLine(item.area_id);

        line.rental_rate_id = item.rental_rate_id;

        line.event_date = String(item.event_date || '').substring(0, 10);

        line.event_end_date = String(item.event_end_date || item.event_date || '').substring(0, 10);

        line.start_time = String(item.start_time || '').substring(0, 5);

        line.end_time = String(item.end_time || '').substring(0, 5);

        line.hourly_rate = Number(item.hourly_rate);

        return line;

    });

    localAreas.value = (rental.items || [])

        .filter((item) => item.area)

        .map((item) => ({ id: item.area_id, name: item.area.name, area_type: item.area.area_type }));

    if (rental.local_id) {

        fetchRatesForLocal(rental.local_id);

    }

};



onMounted(() => {

    if (isEdit.value) {

        initializeFromRental();

    }

});



const submit = () => {

    if (!isEdit.value && !canSubmitPayment.value) {

        window.alertaSistema('Adelanto insuficiente', `El adelanto mínimo es S/ ${minimumAdvance.value.toFixed(2)} (20%).`, 'warning');

        return;

    }



    const submitOptions = {

        preserveScroll: true,

        onSuccess: () => {

            window.alertaSistema(
                'Enhorabuena',
                isEdit.value ? 'La reserva se actualizó correctamente' : 'La reserva se registró correctamente',
                'success'
            );

        },

    };



    // El arreglo de áreas se arma solo al enviar; el estado editable de cada línea no cambia.
    form.transform((data) => ({

        ...data,

        // En torneo, el total es el monto fijo y las líneas por hora no se envían.
        total_price: tournamentMode.value ? Number(tournamentForm.value.total_amount) : totalPrice.value,

        tournament: tournamentMode.value ? { ...tournamentForm.value } : null,

        areas: tournamentMode.value ? [] : data.areas.map((line) => ({



            area_id: line.area_id,

            rental_rate_id: line.rental_rate_id,

            event_date: line.event_date,

            event_end_date: line.event_end_date,

            start_time: line.start_time,

            end_time: line.end_time,

            total_hours: lineTotalHours(line),

            hourly_rate: Number(line.hourly_rate),

            base_amount: lineBaseAmount(line),

            subtotal: lineSubtotal(line),

            extras: lineExtras(line),

        })),

    }));

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



            <!-- Torneo: solo para campos deportivos. Serie de fechas con un monto total. -->
            <div v-if="sportsFieldAreas.length > 0" class="col-span-6">

                <label class="inline-flex items-center gap-2 text-sm">
                    <input v-model="tournamentMode" type="checkbox" class="form-checkbox" />
                    Es un torneo (monto fijo para toda la serie)
                </label>

                <div v-if="tournamentMode" class="mt-3 grid grid-cols-6 gap-4">
                    <div class="col-span-6 sm:col-span-3">
                        <InputLabel value="Campo *" />
                        <select v-model="tournamentForm.area_id" class="form-select w-full">
                            <option value="">Seleccionar</option>
                            <option v-for="area in sportsFieldAreas" :key="area.id" :value="area.id">
                                {{ area.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors['tournament.area_id']" class="mt-2" />
                    </div>

                    <div class="col-span-6 sm:col-span-3">
                        <InputLabel value="Complejo deportivo (sede) *" />
                        <select v-model="tournamentForm.venue_name" class="form-select w-full">
                            <option value="">Seleccionar</option>
                            <option v-for="complex in complexes" :key="complex.id" :value="complex.names">
                                {{ complex.names }}
                            </option>
                        </select>
                        <InputError :message="form.errors['tournament.venue_name']" class="mt-2" />
                    </div>

                    <div class="col-span-6 sm:col-span-3">
                        <InputLabel value="Día de la semana *" />
                        <select v-model="tournamentForm.day_of_week" class="form-select w-full">
                            <option v-for="(name, value) in weekDayNames" :key="value" :value="Number(value)">
                                {{ name }}
                            </option>
                        </select>
                        <InputError :message="form.errors['tournament.day_of_week']" class="mt-2" />
                    </div>

                    <div class="col-span-6 sm:col-span-3">
                        <InputLabel value="Fecha de inicio *" />
                        <TextInput v-model="tournamentForm.start_date" type="date" class="w-full" />
                        <InputError :message="form.errors['tournament.start_date']" class="mt-2" />
                    </div>

                    <div class="col-span-6 sm:col-span-3">
                        <InputLabel value="Fecha de fin *" />
                        <TextInput v-model="tournamentForm.end_date" type="date" class="w-full" />
                        <InputError :message="form.errors['tournament.end_date']" class="mt-2" />
                    </div>

                    <div class="col-span-6 sm:col-span-3">
                        <InputLabel value="Hora de inicio *" />
                        <TextInput v-model="tournamentForm.start_time" type="time" class="w-full" />
                        <InputError :message="form.errors['tournament.start_time']" class="mt-2" />
                    </div>

                    <div class="col-span-6 sm:col-span-3">
                        <InputLabel value="Hora de fin *" />
                        <TextInput v-model="tournamentForm.end_time" type="time" class="w-full" />
                        <InputError :message="form.errors['tournament.end_time']" class="mt-2" />
                    </div>

                    <div class="col-span-6 sm:col-span-3">
                        <InputLabel value="Monto total del torneo (S/) *" />
                        <TextInput v-model="tournamentForm.total_amount" type="number" step="0.01" min="0" class="w-full" />
                        <InputError :message="form.errors['tournament.total_amount']" class="mt-2" />
                    </div>
                </div>
            </div>

            <div v-if="!tournamentMode" class="col-span-6 sm:col-span-6">

                <InputLabel value="Áreas a alquilar *" />

                <p v-if="!form.local_id" class="text-xs text-gray-500 mt-1">Primero seleccione el local.</p>

                <p v-else-if="loadingAreas" class="text-xs text-gray-500 mt-1">Cargando áreas...</p>

                <div v-else-if="localAreas.length === 0" class="mt-1 flex items-center gap-3">
                    <p class="text-xs text-gray-500">Este local no tiene áreas activas.</p>
                    <button type="button" class="btn btn-sm btn-outline-primary" @click="displayModalAreas = true">Agregar área</button>
                </div>

                <div v-else class="mt-2 flex flex-wrap gap-2">

                    <button

                        v-for="area in localAreas"

                        :key="area.id"

                        type="button"

                        class="btn btn-sm"

                        :class="isAreaSelected(area.id) ? 'btn-primary' : 'btn-outline-primary'"

                        :aria-pressed="isAreaSelected(area.id) ? 'true' : 'false'"

                        @click="toggleArea(area)"

                    >

                        {{ area.name }}

                    </button>

                </div>

                <div class="mt-2">
                    <button type="button" class="text-primary text-sm underline" @click="displayModalAreas = true">Administrar áreas del local</button>
                </div>

                <InputError :message="form.errors.areas" class="mt-2" />

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



            <div class="col-span-6 space-y-4">

                <div v-for="(line, index) in form.areas" :key="line.key" class="panel p-4">

                    <div class="flex justify-between items-center mb-3">

                        <h4 class="font-semibold">{{ areaName(line.area_id) }}</h4>

                        <button type="button" class="text-danger text-sm" @click="removeLine(index)">Quitar área</button>

                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">

                        <div class="md:col-span-4">

                            <InputLabel value="Tarifa *" />

                            <div class="flex gap-2">

                                <select v-model="line.rental_rate_id" class="form-select w-full" :disabled="loadingRates">

                                    <option value="">Seleccionar</option>

                                    <option v-for="rate in ratesForArea(line.area_id)" :key="rate.id" :value="rate.id">

                                        {{ rate.name }} — S/ {{ Number(rate.hourly_rate).toFixed(2) }}/h

                                    </option>

                                </select>

                                <button type="button" class="btn btn-primary whitespace-nowrap" @click="openModalRate(line)">+ Tarifa</button>

                            </div>

                            <InputError :message="lineErrors(index, 'rental_rate_id')" class="mt-2" />

                        </div>

                        <div class="md:col-span-2">

                            <InputLabel value="Fecha inicio *" />

                            <input v-model="line.event_date" type="date" class="form-input w-full" @change="suggestLineEndDate(line)" />

                            <InputError :message="lineErrors(index, 'event_date')" class="mt-2" />

                        </div>

                        <div class="md:col-span-2">

                            <InputLabel value="Hora inicio *" />

                            <input v-model="line.start_time" type="time" class="form-input w-full" @change="suggestLineEndDate(line)" />

                            <InputError :message="lineErrors(index, 'start_time')" class="mt-2" />

                        </div>

                        <div class="md:col-span-2">

                            <InputLabel value="Fecha fin *" />

                            <input v-model="line.event_end_date" type="date" class="form-input w-full" :min="line.event_date" />

                            <InputError :message="lineErrors(index, 'event_end_date')" class="mt-2" />

                        </div>

                        <div class="md:col-span-2">

                            <InputLabel value="Hora fin *" />

                            <input v-model="line.end_time" type="time" class="form-input w-full" @change="suggestLineEndDate(line)" />

                            <InputError :message="lineErrors(index, 'end_time')" class="mt-2" />

                        </div>

                    </div>

                    <div class="mt-4 space-y-2">

                        <label class="flex items-center gap-2">

                            <input v-model="line.services.tables_chairs.on" type="checkbox" class="form-checkbox" />

                            <span>Mesas y sillas (S/)</span>

                            <TextInput v-model="line.services.tables_chairs.amount" type="number" min="0" step="0.01" class="w-28" :disabled="!line.services.tables_chairs.on" />

                        </label>

                        <label class="flex items-center gap-2">

                            <input v-model="line.services.food.on" type="checkbox" class="form-checkbox" />

                            <span>Comida (S/)</span>

                            <TextInput v-model="line.services.food.amount" type="number" min="0" step="0.01" class="w-28" :disabled="!line.services.food.on" />

                        </label>

                        <div class="flex items-center gap-2">

                            <span>Cerveza</span>

                            <select v-model="line.services.beer.by" class="form-select w-40">

                                <option value="none">No incluye</option>

                                <option value="company">Empresa</option>

                                <option value="client">Cliente</option>

                            </select>

                            <TextInput v-model="line.services.beer.amount" type="number" min="0" step="0.01" class="w-28" :disabled="line.services.beer.by === 'none'" />

                        </div>

                    </div>

                    <div class="mt-4">

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2">

                            <TextInput v-model="line.newExtraDescription" type="text" placeholder="Descripción del cargo" />

                            <TextInput v-model="line.newExtraAmount" type="number" min="0" step="0.01" placeholder="Monto" />

                            <button type="button" class="btn btn-outline-primary" @click="addManualExtraToLine(line)">Agregar cargo</button>

                        </div>

                        <div v-for="(extra, eIndex) in line.manualExtras" :key="eIndex" class="flex justify-between items-center py-1 mt-2 border-b border-white-dark/10">

                            <span>{{ extra.description }}</span>

                            <div class="flex items-center gap-3">

                                <span>S/ {{ Number(extra.amount).toFixed(2) }}</span>

                                <button type="button" class="text-danger text-sm" @click="removeManualExtraFromLine(line, eIndex)">Quitar</button>

                            </div>

                        </div>

                    </div>

                    <p class="text-sm mt-3">

                        {{ lineTotalHours(line).toFixed(2) }} h · Base S/ {{ lineBaseAmount(line).toFixed(2) }} · Subtotal S/ {{ lineSubtotal(line).toFixed(2) }}

                    </p>

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



    <ModalLarge :show="displayModalAreas" :on-close="closeModalAreas" :icon="'/img/caja-registradora.png'">
        <template #title>Áreas del local</template>
        <template #message>Agregue, edite o quite las áreas que se pueden alquilar.</template>
        <template #content>
            <AreasPanel v-if="displayModalAreas && form.local_id" :key="form.local_id" :local-id="Number(form.local_id)" :area-types="areaTypes" />
        </template>
        <template #buttons>
            <button type="button" class="btn btn-primary" @click="closeModalAreas">Cerrar</button>
        </template>
    </ModalLarge>

    <ModalSmall :show="displayModalRate" :on-close="closeModalRate" :icon="'/img/etiqueta-de-precio.png'">

        <template #title>Nueva tarifa</template>

        <template #message>Registre una tarifa por hora para el local seleccionado.</template>

        <template #content>

            <div class="space-y-4">

                <div>

                    <InputLabel value="Área *" />

                    <select v-model="rateForm.area_id" class="form-select w-full">

                        <option value="">Seleccionar área</option>

                        <option v-for="area in localAreas" :key="area.id" :value="area.id">{{ area.name }}</option>

                    </select>

                    <InputError :message="rateErrors.area_id?.[0]" class="mt-2" />

                </div>

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


