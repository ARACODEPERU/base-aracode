<script setup>
    import { useForm, Link } from '@inertiajs/vue3';
    import FormSection from '@/Components/FormSection.vue';
    import InputError from '@/Components/InputError.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import TextInput from '@/Components/TextInput.vue';
    import Keypad from '@/Components/Keypad.vue';
    import Swal2 from 'sweetalert2';
    import { ref, computed, onMounted } from 'vue';
    import { ConfigProvider, Select, TreeSelect } from 'ant-design-vue';
    import Multiselect from '@suadelabs/vue3-multiselect';
    import '@suadelabs/vue3-multiselect/dist/vue3-multiselect.css';
    import iconLoader from '@/Components/vristo/icon/icon-loader.vue';
    import esES from 'ant-design-vue/es/locale/es_ES';
    import FlatPickr from 'vue-flatpickr-component';
    import 'flatpickr/dist/flatpickr.css';
    import { Spanish } from "flatpickr/dist/l10n/es.js"
    import Editor from '@tinymce/tinymce-vue';
    import iconUpload from '@/Components/vristo/icon/icon-upload.vue';
    import SearchClients from '../../Teams/Partials/SearchClients.vue';
    import IconX from '@/Components/vristo/icon/icon-x.vue';

    const props = defineProps({
        accordance: {
            type: Object,
            default: () => ({}),
        },
        edicion: {
            type: Object,
            default: () => ({}),
        },
        resolutionStatus: {
            type: Array,
            default: () => ([]),
        },
        tinyApiKey: {
            type: String,
            default: null,
        },
        ubigeo: {
            type: Object,
            default: () => ({}),
        },
        documentTypes: {
            type: Object,
            default: () => ({}),
        }
    });

    const form = useForm({
        // --- CAMPOS PARA IDENTIFICACIÓN (SÓLO LECTURA EN UI) ---
        id: props.accordance.id,
        minutes_subject_local: props.accordance.partido.equipolocal.name,
        minutes_subject_visitor: props.accordance.partido.equipovisitante.name,
        participants: [
            {
                person_id: props.accordance.partido.equipolocal.manager.id,
                full_name: props.accordance.partido.equipolocal.manager.full_name,
            },
            {
                person_id: props.accordance.partido.equipovisitante.manager.id,
                full_name: props.accordance.partido.equipovisitante.manager.full_name,
            }
        ],
        has_protest: props.accordance.has_protest || false,
        protest_details: props.accordance.protest_details || '',
        payment_arvitraje_h: props.accordance.payment_arvitraje_h || 0,
        payment_arvitraje_a: props.accordance.payment_arvitraje_a || 0,

        // --- CAMPOS EDITABLES (RESOLUCIÓN) ---
        observations: props.accordance.observations || '',        // Comentarios de la comisión
        resolution_details: props.accordance.resolution_details || '', // La solución final
        protest_status: props.accordance.protest_status || 'pending',  // pending, resolved, dismissed
        status: props.accordance.status || 'open',                // open o closed
    });
</script>

<template>
    <FormSection @submitted="updateAccordance" class="">
        <template #title>
            Acta Detalles
        </template>

        <template #description>
            Editar acta, Los campos con * son obligatorios
        </template>

        <template #form>
            <ConfigProvider :locale="esES">

                <div class="col-span-full bg-gray-50 dark:bg-gray-800/50 p-6 rounded-2xl border border-gray-200 dark:border-gray-700">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                        <div class="text-center sm:text-right flex-1 justify-end">
                            <p class="text-[10px] text-gray-500 uppercase font-bold tracking-widest">Local</p>
                            <h3 class="text-lg font-black text-indigo-700 dark:text-indigo-400">{{ form.minutes_subject_local }}</h3>
                            <p class="text-xs text-gray-600 dark:text-gray-400 italic">Delegado: {{ form.participants[0]?.full_name }}</p>
                        </div>

                        <div class="flex flex-col items-center px-8">
                            <div class="bg-gray-800 text-white px-4 py-1 rounded-lg text-xl font-mono tracking-tighter shadow-lg">
                                VS
                            </div>
                            <span class="text-[9px] mt-2 text-gray-400 uppercase">Resumen de Encuentro</span>
                        </div>

                        <div class="text-center sm:text-left flex-1">
                            <p class="text-[10px] text-gray-500 uppercase font-bold tracking-widest">Visitante</p>
                            <h3 class="text-lg font-black text-indigo-700 dark:text-indigo-400">{{ form.minutes_subject_visitor }}</h3>
                            <p class="text-xs text-gray-600 dark:text-gray-400 italic">Delegado: {{ form.participants[1]?.full_name }}</p>
                        </div>
                    </div>
                </div>

                <div v-if="form.has_protest" class="col-span-full md:col-span-4 bg-red-50 dark:bg-red-900/10 border-l-4 border-red-500 p-4 rounded-r-xl">
                    <div class="flex items-start">
                        <icon-alert class="w-5 h-5 text-red-600 mt-0.5 mr-3" />
                        <div>
                            <h4 class="text-sm font-bold text-red-800 dark:text-red-400">Detalles del Reclamo en Campo</h4>
                            <p class="text-xs text-red-700 dark:text-red-300/80 mt-1 leading-relaxed">
                                {{ form.protest_details || 'No se especificaron detalles del reclamo.' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-span-full md:col-span-2 grid grid-cols-2 gap-2">
                    <div class="bg-emerald-50 dark:bg-emerald-900/10 p-3 rounded-xl border border-emerald-100 dark:border-emerald-800">
                        <p class="text-[9px] text-emerald-600 font-bold uppercase">Arbitraje Local</p>
                        <p class="text-lg font-mono font-bold text-emerald-700 dark:text-emerald-400">S/ {{ form.payment_arvitraje_h || '0.00' }}</p>
                    </div>
                    <div class="bg-emerald-50 dark:bg-emerald-900/10 p-3 rounded-xl border border-emerald-100 dark:border-emerald-800">
                        <p class="text-[9px] text-emerald-600 font-bold uppercase">Arbitraje Vis.</p>
                        <p class="text-lg font-mono font-bold text-emerald-700 dark:text-emerald-400">S/ {{ form.payment_arvitraje_a || '0.00' }}</p>
                    </div>
                </div>

                <hr class="col-span-full border-gray-200 dark:border-gray-700 my-2" />

                <div class="col-span-full md:col-span-3">
                    <InputLabel for="observations" value="Observaciones Adicionales de la Comisión" />
                    <textarea
                        id="observations"
                        v-model="form.observations"
                        rows="4"
                        class="form-textarea border-l-4 border-l-indigo-500"
                        placeholder="Escriba incidentes no reportados o aclaraciones..."
                    ></textarea>
                    <InputError :message="form.errors.observations" class="mt-2" />
                </div>

                <div class="col-span-full md:col-span-3">
                    <InputLabel for="resolution_details" value="Resolución Final del Conflicto *" />
                    <textarea
                        id="resolution_details"
                        v-model="form.resolution_details"
                        rows="4"
                        class="form-textarea border-l-4 border-l-indigo-500"
                        placeholder="Describa la solución, sanciones o acuerdos finales..."
                        required
                    ></textarea>
                    <InputError :message="form.errors.resolution_details" class="mt-2" />
                </div>

                <div class="col-span-full md:col-span-3">
                    <InputLabel value="Estado de la Resolución" />
                    <select
                        v-model="form.protest_status"
                        class="form-select"
                    >
                        <template v-for="xStatus in resolutionStatus">
                            <option :value="xStatus">
                                {{
                                    xStatus == 'none' ? 'NinguNinguna Evaluación' :
                                    xStatus == 'pending' ? 'En Evaluación' :
                                    xStatus == 'resolved' ? 'Resuelto / Fundado' :
                                    xStatus == 'rejected' ? 'Rechazado / Infundado / Desestimado' :
                                    'Otros'
                                }}
                            </option>
                        </template>
                    </select>
                </div>

                <div class="col-span-full md:col-span-3">
                    <InputLabel value="Cierre de Acta" />
                    <div class="mt-3 flex items-center space-x-6">
                        <label class="inline-flex items-center group cursor-pointer">
                            <input type="radio" v-model="form.status" value="open" class="text-amber-500 focus:ring-amber-500">
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400 group-hover:text-amber-600 transition">Mantener Abierto</span>
                        </label>
                        <label class="inline-flex items-center group cursor-pointer">
                            <input type="radio" v-model="form.status" value="closed" class="text-indigo-600 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400 group-hover:text-indigo-600 transition">Cerrar y Oficializar</span>
                        </label>
                    </div>
                </div>

            </ConfigProvider>
        </template>

        <template #actions>
            <progress v-if="form.progress" :value="form.progress.percentage" max="100" class="mr-2">
                {{ form.progress.percentage }}%
            </progress>
            <Keypad>
                <template #botones>
                    <PrimaryButton
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        <icon-loader v-show="form.processing" class="w-4 h-4 animate-spin mr-1" />
                        Guardar
                    </PrimaryButton>
                    <Link :href="route('even_ediciones_actas_listado', edicion.id)"  class="ml-2 inline-block px-6 py-2.5 bg-green-500 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-green-600 hover:shadow-lg focus:bg-green-600 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-green-700 active:shadow-lg transition duration-150 ease-in-out">Ir al Listado</Link>
                </template>
            </Keypad>
        </template>
    </FormSection>
</template>
