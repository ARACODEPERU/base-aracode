<script setup>
    import iconBloodPressure from '@/Components/vristo/icon/icon-blood-pressure.vue';
    import iconLungs from '@/Components/vristo/icon/icon-lungs.vue';
    import iconX from '@/Components/vristo/icon/icon-x.vue';
    import IconHorizontalDots from '@/Components/vristo/icon/icon-horizontal-dots.vue';
    import IconThermometer from '@/Components/vristo/icon/icon-thermometer.vue';
    import IconPeso from '@/Components/vristo/icon/icon-peso.vue';
    import iconHeightPerson from '@/Components/vristo/icon/icon-height-person.vue';
    import IconHeartRate from '@/Components/vristo/icon/icon-heart-rate.vue';
    import IconStandingPerson from '@/Components/vristo/icon/icon-standing-person.vue';
    import iconAbdominal from '@/Components/vristo/icon/icon-abdominal.vue';
    import { onMounted, reactive } from 'vue';
    import { message  } from 'ant-design-vue';
    import { Link } from '@inertiajs/vue3';

    const props = defineProps({
        patient: {
            type: Object,
            default: () => ({})
        }
    });

    const vitals = reactive({
        loading: false,
        data: []
    });

    const getDataVitals = () => {
        vitals.loading = true; // Activa el estado de loading
        return axios({
            method: 'get',
            url: route('heal_patients_vitals_last', props.patient.id),
        }).then((response) => {
            vitals.data = response.data.vitals
        }).catch((error) => {
            message.error('Hubo un error al obtener los datos');
        }).finally(() => {
            vitals.loading = false;
        });
    }

    onMounted(() => {
        getDataVitals();
    });

    const formatFecha = (fecha) => {
        // Crear un objeto Date a partir del string de fecha
        const date = new Date(fecha);

        // Configuración para el formato en español
        const options = {
            day: '2-digit',
            month: 'short',
            hour: 'numeric',
            minute: 'numeric',
            hour12: true
        };

        // Formatear la fecha en español usando Intl.DateTimeFormat
        return new Intl.DateTimeFormat('es-ES', options).format(date);
    }
</script>
<template>
    <div class="panel sm:col-span-2 p-0">
        <div class="flex items-center border-b px-5 py-2.5 dark:border-gray-800">
            <h5 class="font-semibold text-lg">ULTIMOS SIGNOS VITALES</h5>
            <div class="dropdown ltr:ml-auto rtl:mr-auto w-auto">
                <Popper :placement="'bottom-start'" offsetDistance="0" class="align-middle">
                    <a href="javascript:;">
                        <icon-horizontal-dots class="w-5 h-5 text-black/70 dark:text-white/70 hover:!text-primary" />
                    </a>
                    <template #content="{ close }">
                        <ul @click="close()">
                            <li>
                                <a href="javascript:;">Ver informe</a>
                            </li>
                            <li>
                                <button @click="getDataVitals" type="button">Actualizar</button>
                            </li>
                        </ul>
                    </template>
                </Popper>
            </div>
        </div>
        <div v-if="vitals.loading" role="status" class="max-w-md p-6 space-y-4 divide-y divide-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <div class="h-2.5 bg-gray-300 rounded-full dark:bg-gray-600 w-24 mb-2.5"></div>
                    <div class="w-32 h-2 bg-gray-200 rounded-full dark:bg-gray-700"></div>
                </div>
                <div class="h-2.5 bg-gray-300 rounded-full dark:bg-gray-700 w-12"></div>
            </div>
            <div class="flex items-center justify-between pt-4">
                <div>
                    <div class="h-2.5 bg-gray-300 rounded-full dark:bg-gray-600 w-24 mb-2.5"></div>
                    <div class="w-32 h-2 bg-gray-200 rounded-full dark:bg-gray-700"></div>
                </div>
                <div class="h-2.5 bg-gray-300 rounded-full dark:bg-gray-700 w-12"></div>
            </div>
            <div class="flex items-center justify-between pt-4">
                <div>
                    <div class="h-2.5 bg-gray-300 rounded-full dark:bg-gray-600 w-24 mb-2.5"></div>
                    <div class="w-32 h-2 bg-gray-200 rounded-full dark:bg-gray-700"></div>
                </div>
                <div class="h-2.5 bg-gray-300 rounded-full dark:bg-gray-700 w-12"></div>
            </div>
            <div class="flex items-center justify-between pt-4">
                <div>
                    <div class="h-2.5 bg-gray-300 rounded-full dark:bg-gray-600 w-24 mb-2.5"></div>
                    <div class="w-32 h-2 bg-gray-200 rounded-full dark:bg-gray-700"></div>
                </div>
                <div class="h-2.5 bg-gray-300 rounded-full dark:bg-gray-700 w-12"></div>
            </div>
            <div class="flex items-center justify-between pt-4">
                <div>
                    <div class="h-2.5 bg-gray-300 rounded-full dark:bg-gray-600 w-24 mb-2.5"></div>
                    <div class="w-32 h-2 bg-gray-200 rounded-full dark:bg-gray-700"></div>
                </div>
                <div class="h-2.5 bg-gray-300 rounded-full dark:bg-gray-700 w-12"></div>
            </div>
            <div class="flex items-center justify-between pt-4">
                <div>
                    <div class="h-2.5 bg-gray-300 rounded-full dark:bg-gray-600 w-24 mb-2.5"></div>
                    <div class="w-32 h-2 bg-gray-200 rounded-full dark:bg-gray-700"></div>
                </div>
                <div class="h-2.5 bg-gray-300 rounded-full dark:bg-gray-700 w-12"></div>
            </div>
            <span class="sr-only">Loading...</span>
        </div>
        <div v-else class="p-6">
            <div class="">
                <div class="text-sm cursor-pointer space-y-3">
                    <div class="flex">
                        <span
                            class="shrink-0 grid place-content-center w-9 h-9 rounded-md bg-secondary-light dark:bg-secondary text-secondary dark:text-secondary-light"
                        >
                            <icon-height-person class="w-6 h-6" />
                        </span>
                        <div class="px-3 flex-1">
                            <div>Estatura</div>
                            <div v-if="vitals.data.date_time_attention" class="text-xs text-white-dark dark:text-gray-500">
                                {{ formatFecha(vitals.data.date_time_attention) }}
                            </div>
                        </div>
                        <span class="text-danger text-base px-1 ltr:ml-auto rtl:mr-auto whitespace-pre">
                            {{ vitals.data.pex_talla }}
                        </span>
                    </div>
                    <div class="flex">
                        <span
                            class="shrink-0 grid place-content-center w-9 h-9 rounded-md bg-success-light dark:bg-success text-success dark:text-success-light"
                        >
                            <icon-peso class="w-6 h-6" />
                        </span>
                        <div class="px-3 flex-1">
                            <div>Peso</div>
                            <div v-if="vitals.data.date_time_attention" class="text-xs text-white-dark dark:text-gray-500">
                                {{ formatFecha(vitals.data.date_time_attention) }}
                            </div>
                        </div>
                        <span class="text-danger text-base px-1 ltr:ml-auto rtl:mr-auto whitespace-pre">
                            {{ vitals.data.pex_peso }}
                        </span>
                    </div>
                    <div class="flex">
                        <span
                            class="shrink-0 grid place-content-center w-9 h-9 rounded-md bg-info-light dark:bg-info text-info dark:text-info-light"
                        >
                            <icon-abdominal class="w-6 h-6" />
                        </span>
                        <div class="px-3 flex-1">
                            <div>Masa corporal</div>
                            <div v-if="vitals.data.date_time_attention" class="text-xs text-white-dark dark:text-gray-500">
                                {{ formatFecha(vitals.data.date_time_attention) }}
                            </div>
                        </div>
                        <span class="text-danger text-base px-1 ltr:ml-auto rtl:mr-auto whitespace-pre">
                            {{ vitals.data.pex_imc }}
                        </span>
                    </div>
                    <div class="flex">
                        <span
                            class="shrink-0 grid place-content-center w-9 h-9 rounded-md bg-warning-light dark:bg-warning text-warning dark:text-warning-light"
                        >
                            <icon-thermometer class="w-6 h-6"/>
                        </span>
                        <div class="px-3 flex-1">
                            <div>Temperatura</div>
                            <div v-if="vitals.data.date_time_attention" class="text-xs text-white-dark dark:text-gray-500">
                                {{ formatFecha(vitals.data.date_time_attention) }}
                            </div>
                        </div>
                        <span class="text-danger text-base px-1 ltr:ml-auto rtl:mr-auto whitespace-pre">
                            {{ vitals.data.pex_tem }}
                        </span>
                    </div>
                    <div class="flex">
                        <span
                            class="shrink-0 grid place-content-center w-9 h-9 rounded-md bg-danger-light dark:bg-danger text-danger dark:text-danger-light"
                        >
                            <icon-heart-rate class="w-6 h-6"/>
                        </span>
                        <div class="px-3 flex-1">
                            <div>Frecuencia cardiaca</div>
                            <div v-if="vitals.data.date_time_attention" class="text-xs text-white-dark dark:text-gray-500">
                                {{ formatFecha(vitals.data.date_time_attention) }}
                            </div>
                        </div>
                        <span class="text-danger text-base px-1 ltr:ml-auto rtl:mr-auto whitespace-pre">
                            {{ vitals.data.pex_fc }}
                        </span>
                    </div>
                    <div class="flex">
                        <span
                            class="shrink-0 grid place-content-center w-9 h-9 rounded-md bg-dark-light dark:bg-dark text-warning dark:text-warning-light"
                        >
                            <icon-lungs class="w-7 h-7"/>
                        </span>
                        <div class="px-3 flex-1">
                            <div>Frecuencia respiratoria</div>
                            <div v-if="vitals.data.date_time_attention" class="text-xs text-white-dark dark:text-gray-500">
                                {{ formatFecha(vitals.data.date_time_attention) }}
                            </div>
                        </div>
                        <span class="text-danger text-base px-1 ltr:ml-auto rtl:mr-auto whitespace-pre">
                            {{ vitals.data.pex_fr }}
                        </span>
                    </div>
                    <div class="flex">
                        <span
                            class="shrink-0 grid place-content-center w-9 h-9 rounded-md bg-danger-light dark:bg-danger text-danger dark:text-danger-light"
                        >
                            <icon-blood-pressure class="w-7 h-7"/>
                        </span>
                        <div class="px-3 flex-1">
                            <div>Presion Arterial</div>
                            <div v-if="vitals.data.date_time_attention" class="text-xs text-white-dark dark:text-gray-500">
                                {{ formatFecha(vitals.data.date_time_attention) }}
                            </div>
                        </div>
                        <span class="text-danger text-base px-1 ltr:ml-auto rtl:mr-auto whitespace-pre">
                            {{ vitals.data.pex_pa }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>