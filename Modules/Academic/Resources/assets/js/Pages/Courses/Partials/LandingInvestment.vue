<script setup>

import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';
import InputError from '@/Components/InputError.vue';
import IconSave from "@/Components/vristo/icon/icon-save.vue";
import IconPlus from '@/Components/vristo/icon/icon-plus.vue';
import IconTrash from '@/Components/vristo/icon/icon-trash.vue';

const props = defineProps({
    course: {
        type: Object,
        default: () => ({}),
    },
    landing: {
        type: Object,
        default: () => ({}),
    },
});

const getInitialInvestmentData = () => {
    const data = props.landing.investment_section;
    if (data && typeof data === 'string') {
        try {
            return JSON.parse(data);
        } catch (e) {
            return null;
        }
    }
    return data || null;
};

const initialData = getInitialInvestmentData();

const getDefaultItems = () => {
    if (initialData?.items && initialData.items.length >= 2) {
        return initialData.items.map(item => ({
            ...item,
            price_before: item.price_before !== undefined ? item.price_before : '',
            price_before_text: item.price_before_text !== undefined ? item.price_before_text : '',
            price_before_visible: item.price_before_visible || false,
            price_now: item.price_now !== undefined ? item.price_now : '',
            price_now_text: item.price_now_text !== undefined ? item.price_now_text : '',
            features: item.features || []
        }));
    }
    return [
        {
            tag: '',
            title: '',
            description: '',
            price_before: '',
            price_before_text: '',
            price_before_visible: false,
            price_now: '',
            price_now_text: '',
            features: []
        },
        {
            tag: '',
            title: '',
            description: '',
            price_before: '',
            price_before_text: '',
            price_before_visible: false,
            price_now: '',
            price_now_text: '',
            features: []
        }
    ];
};

const formInvestment = useForm({
    name: initialData?.name || 'Inversión',
    title: initialData?.title || '',
    description: initialData?.description || '',
    items: getDefaultItems(),
});

const addFeature = (itemIndex) => {
    formInvestment.items[itemIndex].features.push('');
};

const removeFeature = (itemIndex, featureIndex) => {
    formInvestment.items[itemIndex].features.splice(featureIndex, 1);
};

const saveInvestmentSettings = () => {
    formInvestment.put(route('aca_courses_landing_update_investment', props.course.id),{
        errorBag: 'saveInvestmentSettings',
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: 'Sección Inversión guardada correctamente',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        },
    });
};

</script>
<template>
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
            <svg class="mr-2 w-5 h-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                <path fill="currentColor" d="M176 0c-26.5 0-48 21.5-48 48l0 208c0 26.5 21.5 48 48 48l64 0c26.5 0 48-21.5 48-48l0-64 32 0c70.7 0 128 57.3 128 128S390.7 448 320 448L32 448c-17.7 0-32 14.3-32 32s14.3 32 32 32l448 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-16.9 0c30.4-34 48.9-78.8 48.9-128 0-106-86-192-192-192l-32 0 0-80c0-26.5-21.5-48-48-48L176 0zM120 352c-13.3 0-24 10.7-24 24s10.7 24 24 24l176 0c13.3 0 24-10.7 24-24s-10.7-24-24-24l-176 0z"/>
            </svg>
            Sección Inversión
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Configura los planes de precios de tu curso (2 opciones fijas).
        </p>
    </div>

    <div class="space-y-6">
        <!-- Datos Principales -->
        <div class="">
            <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4 flex items-center">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-400 mr-2">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 192 512">
                        <path fill="currentColor" d="M48 48a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zM0 192c0-17.7 14.3-32 32-32l64 0c17.7 0 32 14.3 32 32l0 256 32 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 512c-17.7 0-32-14.3-32-32s14.3-32 32-32l32 0 0-224-32 0c-17.7 0-32-14.3-32-32z"/>
                    </svg>
                </span>
                Datos Principales
            </h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Nombre
                    </label>
                    <input
                        type="text"
                        v-model="formInvestment.name"
                        class="form-input"
                        placeholder="Inversión"
                    >
                    <InputError :message="formInvestment.errors.name" class="mt-2" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Título
                    </label>
                    <input
                        type="text"
                        v-model="formInvestment.title"
                        class="form-input"
                        placeholder="Título de la sección"
                    >
                    <InputError :message="formInvestment.errors.title" class="mt-2" />
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Descripción
                    </label>
                    <textarea
                        v-model="formInvestment.description"
                        rows="3"
                        class="form-textarea"
                        placeholder="Descripción de la sección..."
                    ></textarea>
                    <InputError :message="formInvestment.errors.description" class="mt-2" />
                </div>
            </div>
        </div>

        <!-- Items (2 Fijos) -->
        <div class="">
            <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4 flex items-center">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-green-200 text-green-600 dark:bg-green-900/50 dark:text-green-400 mr-2">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                        <path fill="currentColor" d="M64 64C28.7 64 0 92.7 0 128L0 384c0 35.3 28.7 64 64 64l384 0c35.3 0 64-28.7 64-64l0-256c0-35.3-28.7-64-64-64L64 64zm96 256a32 32 0 1 1 -64 0 32 32 0 1 1 64 0zm-32-96a32 32 0 1 1 0-64 32 32 0 1 1 0 64zm120-56l144 0c13.3 0 24 10.7 24 24s-10.7 24-24 24l-144 0c-13.3 0-24-10.7-24-24s10.7-24 24-24zm0 128l144 0c13.3 0 24 10.7 24 24s-10.7 24-24 24l-144 0c-13.3 0-24-10.7-24-24s10.7-24 24-24z"/>
                    </svg>
                </span>
                Planes de Precios
            </h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div
                    v-for="(item, index) in formInvestment.items"
                    :key="index"
                    class="p-5 bg-gray-50 dark:bg-gray-700/50 rounded-lg border-2 border-gray-200 dark:border-gray-600 space-y-4"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-bold text-blue-600 dark:text-blue-400 uppercase">
                            Plan {{ index + 1 }}
                        </span>
                    </div>

                    <!-- Tag -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Etiqueta (Tag)
                        </label>
                        <input
                            type="text"
                            v-model="item.tag"
                            class="form-input text-sm mt-1"
                            placeholder="Ej: OFERTA ESPECIAL"
                        >
                    </div>

                    <!-- Título -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Título del Plan
                        </label>
                        <input
                            type="text"
                            v-model="item.title"
                            class="form-input text-sm mt-1"
                            placeholder="Ej: Plan Mensual"
                        >
                    </div>

                    <!-- Descripción -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Descripción
                        </label>
                        <textarea
                            v-model="item.description"
                            rows="2"
                            class="form-textarea text-sm mt-1"
                            placeholder="Breve descripción del plan..."
                        ></textarea>
                    </div>

                    <!-- Precios -->
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Precio Anterior (tachado)
                            </label>
                            <div class="flex items-center gap-2 mt-1">
                                <input
                                    type="number"
                                    v-model="item.price_before"
                                    class="form-input text-sm w-24"
                                    placeholder="200"
                                >
                                <input
                                    type="text"
                                    v-model="item.price_before_text"
                                    class="form-input text-sm flex-1"
                                    placeholder="Ej: /mensual"
                                >
                                <label class="inline-flex items-center cursor-pointer">
                                    <input
                                        type="checkbox"
                                        v-model="item.price_before_visible"
                                        class="sr-only peer"
                                    >
                                    <div class="relative w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                    <span class="ms-2 text-xs text-gray-500 dark:text-gray-400">Visible</span>
                                </label>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Precio Ahora
                                </label>
                                <input
                                    type="number"
                                    v-model="item.price_now"
                                    class="form-input text-sm mt-1 font-bold text-green-600 dark:text-green-400"
                                    placeholder="200"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Texto Precio
                                </label>
                                <input
                                    type="text"
                                    v-model="item.price_now_text"
                                    class="form-input text-sm mt-1"
                                    placeholder="Ej: /mensual"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Características -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Características
                            </label>
                            <button
                                type="button"
                                @click="addFeature(index)"
                                class="inline-flex items-center px-2 py-1 text-xs font-medium rounded text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20"
                            >
                                <IconPlus class="w-3 h-3 mr-1" />
                                Agregar
                            </button>
                        </div>

                        <div v-if="item.features && item.features.length > 0" class="space-y-2">
                            <div
                                v-for="(feature, fIndex) in item.features"
                                :key="fIndex"
                                class="flex items-center gap-2"
                            >
                                <i class="fa fa-check text-green-500 text-xs"></i>
                                <input
                                    type="text"
                                    v-model="item.features[fIndex]"
                                    class="form-input text-sm flex-1"
                                    placeholder="Ej: Atención 24/7"
                                >
                                <button
                                    type="button"
                                    @click="removeFeature(index, fIndex)"
                                    class="text-red-500 hover:text-red-700"
                                >
                                    <IconTrash class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <p v-else class="text-xs text-gray-400 italic">
                            No hay características. Agrega una arriba.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex justify-end">
            <button
                @click="saveInvestmentSettings"
                :disabled="formInvestment.processing"
                :class="{ 'opacity-25': formInvestment.processing }"
                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <IconLoader v-if="formInvestment.processing" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" />
                <IconSave v-else class="mr-2" />
                {{ formInvestment.processing ? 'Guardando...' : 'Guardar Sección Inversión' }}
            </button>
        </div>
    </div>
</template>
