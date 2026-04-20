<script setup>

import { ref, computed, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';
import InputError from '@/Components/InputError.vue';
import IconSave from "@/Components/vristo/icon/icon-save.vue";
import * as fasIcons from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
    course: {
        type: Object,
        default: () => ({}),
    },
    landing: {
        type: Object,
        default: () => ({}),
    },
    languageOptions: {
        type: Object,
        default: () => ({}),
    },
});

const iconCategories = {
    marketing: [
        { value: 'fa-bullhorn', label: 'Megáfono' },
        { value: 'fa-bullseye', label: 'Objetivo' },
        { value: 'fa-rocket', label: 'Cohete' },
        { value: 'fa-chart-line', label: 'Gráfico Línea' },
        { value: 'fa-chart-pie', label: 'Gráfico Circular' },
        { value: 'fa-chart-bar', label: 'Gráfico Barras' },
        { value: 'fa-megaphone', label: 'Megáfono Alt' },
        { value: 'fa-search-dollar', label: 'Buscar Dinero' },
        { value: 'fa-funnel-dollar', label: 'Embudo Ventas' },
        { value: 'fa-poll', label: 'Encuesta' },
        { value: 'fa-mail-bulk', label: 'Correo Masivo' },
        { value: 'fa-bullseye', label: 'Diana' },
        { value: 'fa-layer-group', label: 'Capas' },
    ],
    contabilidad: [
        { value: 'fa-calculator', label: 'Calculadora' },
        { value: 'fa-coins', label: 'Monedas' },
        { value: 'fa-credit-card', label: 'Tarjeta Crédito' },
        { value: 'fa-file-invoice-dollar', label: 'Factura' },
        { value: 'fa-money-bill-wave', label: 'Billetes' },
        { value: 'fa-percentage', label: 'Porcentaje' },
        { value: 'fa-receipt', label: 'Recibo' },
        { value: 'fa-sack-dollar', label: 'Bolsa Dinero' },
        { value: 'fa-wallet', label: 'Cartera' },
        { value: 'fa-money-check', label: 'Cheque' },
        { value: 'fa-hand-holding-usd', label: 'Mano Dólares' },
        { value: 'fa-balance-scale', label: 'Balanza' },
    ],
    academico: [
        { value: 'fa-graduation-cap', label: 'Graduación' },
        { value: 'fa-book', label: 'Libro' },
        { value: 'fa-school', label: 'Escuela' },
        { value: 'fa-book-reader', label: 'Lectura' },
        { value: 'fa-chalkboard-teacher', label: 'Profesor' },
        { value: 'fa-university', label: 'Universidad' },
        { value: 'fa-certificate', label: 'Certificado' },
        { value: 'fa-award', label: 'Premio' },
        { value: 'fa-medal', label: 'Medalla' },
        { value: 'fa-trophy', label: 'Trofeo' },
        { value: 'fa-brain', label: 'Cerebro' },
        { value: 'fa-graduation-cap', label: 'Título' },
    ],
    empresas: [
        { value: 'fa-briefcase', label: 'Maletín' },
        { value: 'fa-building', label: 'Edificio' },
        { value: 'fa-store', label: 'Tienda' },
        { value: 'fa-handshake', label: 'Acuerdo' },
        { value: 'fa-users', label: 'Usuarios' },
        { value: 'fa-user-tie', label: 'Ejecutivo' },
        { value: 'fa-warehouse', label: 'Almacén' },
        { value: 'fa-industry', label: 'Industria' },
        { value: 'fa-hand-holding-dollar', label: 'Mano Dinero' },
        { value: 'fa-file-contract', label: 'Contrato' },
        { value: 'fa-briefcase-medical', label: 'Maletín Médico' },
        { value: 'fa-share-alt', label: 'Compartir' },
    ],
    administracion: [
        { value: 'fa-clipboard-list', label: 'Lista Clipboard' },
        { value: 'fa-tasks', label: 'Tareas' },
        { value: 'fa-calendar-check', label: 'Calendario Check' },
        { value: 'fa-user-cog', label: 'Config Usuario' },
        { value: 'fa-cogs', label: 'Configuración' },
        { value: 'fa-list-check', label: 'Lista Check' },
        { value: 'fa-project-diagram', label: 'Diagrama Proyecto' },
        { value: 'fa-pie-chart', label: 'Gráfico Circular' },
        { value: 'fa-timeline', label: 'Línea Tiempo' },
        { value: 'fa-clipboard-check', label: 'Clipboard Check' },
        { value: 'fa-calendar-alt', label: 'Calendario' },
        { value: 'fa-sort-amount-up', label: 'Ordenar' },
    ],
    basicos: [
        { value: 'fa-star', label: 'Estrella' },
        { value: 'fa-check', label: 'Check' },
        { value: 'fa-plus', label: 'Más' },
        { value: 'fa-edit', label: 'Editar' },
        { value: 'fa-save', label: 'Guardar' },
        { value: 'fa-search', label: 'Buscar' },
        { value: 'fa-filter', label: 'Filtrar' },
        { value: 'fa-download', label: 'Descargar' },
        { value: 'fa-globe', label: 'Globo' },
        { value: 'fa-clock', label: 'Reloj' },
        { value: 'fa-calendar', label: 'Calendario' },
        { value: 'fa-envelope', label: 'Correo' },
        { value: 'fa-phone', label: 'Teléfono' },
        { value: 'fa-laptop', label: 'Computadora' },
        { value: 'fa-video', label: 'Video' },
        { value: 'fa-book-open', label: 'Libro Abierto' },
        { value: 'fa-lightbulb', label: 'Idea' },
        { value: 'fa-tools', label: 'Herramientas' },
    ],
};

const categoryLabels = {
    marketing: 'Marketing',
    contabilidad: 'Contabilidad',
    academico: 'Académico',
    empresas: 'Empresas',
    administracion: 'Administración',
    basicos: 'Básicos',
};

const getInitialProfessionalData = () => {
    const data = props.landing.professional_section;
    if (data && typeof data === 'string') {
        try {
            return JSON.parse(data);
        } catch (e) {
            return null;
        }
    }
    return data || null;
};

const initialData = getInitialProfessionalData();

const formProfessional = useForm({
    name: initialData?.name || 'Actualización Profesional',
    title: initialData?.title || '',
    description: initialData?.description || '',
    items: initialData?.items || [
        { icon: '', title: '', description: '' },
        { icon: '', title: '', description: '' }
    ]
});

const saveProfessionalSettings = () => {
    formProfessional.put(route('aca_courses_landing_update_professional', props.course.id),{
        errorBag: 'saveProfessionalSettings',
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: 'Sección Actualización Profesional guardada correctamente',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        },
    });
};

const formatIconForVue = (iconName) => {
    if (!iconName) return fasIcons.faIcons; // Icono por defecto si está vacío

    // 1. Quitar 'fa-' si existe
    const cleanName = iconName.replace(/^fa-/, '');

    // 2. Convertir 'user-tie' a 'faUserTie' (PascalCase)
    const pascalName = 'fa' + cleanName
        .split('-')
        .map(part => part.charAt(0).toUpperCase() + part.slice(1))
        .join('');

    // 3. Retornar el objeto si existe, o uno por defecto
    return fasIcons[pascalName] || fasIcons.faIcons;
};

</script>
<template>
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
            <svg class="w-5 h-5 mr-2 text-blue-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512">
                <path fill="currentColor" d="M48 195.8l209.2 86.1c9.8 4 20.2 6.1 30.8 6.1s21-2.1 30.8-6.1l242.4-99.8c9-3.7 14.8-12.4 14.8-22.1s-5.8-18.4-14.8-22.1L318.8 38.1C309 34.1 298.6 32 288 32s-21 2.1-30.8 6.1L14.8 137.9C5.8 141.6 0 150.3 0 160L0 456c0 13.3 10.7 24 24 24s24-10.7 24-24l0-260.2zm48 71.7L96 384c0 53 86 96 192 96s192-43 192-96l0-116.6-142.9 58.9c-15.6 6.4-32.2 9.7-49.1 9.7s-33.5-3.3-49.1-9.7L96 267.4z"/>
            </svg>
            Sección Actualización Profesional
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Configura la información de actualización profesional que se mostrará en la landing page.
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
                        v-model="formProfessional.name"
                        class="form-input"
                        placeholder="Actualización Profesional"
                    >
                    <InputError :message="formProfessional.errors.name" class="mt-2" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Título
                    </label>
                    <input
                        type="text"
                        v-model="formProfessional.title"
                        class="form-input"
                        placeholder="Título de la sección"
                    >
                    <InputError :message="formProfessional.errors.title" class="mt-2" />
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Descripción
                    </label>
                    <textarea
                        v-model="formProfessional.description"
                        rows="3"
                        class="form-textarea"
                        placeholder="Descripción de la sección..."
                    ></textarea>
                    <InputError :message="formProfessional.errors.description" class="mt-2" />
                </div>
            </div>
        </div>

        <!-- Items (Dos mini-secciones) -->
        <div class="">
            <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4 flex items-center">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-green-200 text-green-600 dark:bg-green-900/50 dark:text-green-400 mr-2">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                        <path fill="currentColor" d="M64 64C28.7 64 0 92.7 0 128L0 384c0 35.3 28.7 64 64 64l384 0c35.3 0 64-28.7 64-64l0-256c0-35.3-28.7-64-64-64L64 64zm96 256a32 32 0 1 1 -64 0 32 32 0 1 1 64 0zm-32-96a32 32 0 1 1 0-64 32 32 0 1 1 0 64zm120-56l144 0c13.3 0 24 10.7 24 24s-10.7 24-24 24l-144 0c-13.3 0-24-10.7-24-24s10.7-24 24-24zm0 128l144 0c13.3 0 24 10.7 24 24s-10.7 24-24 24l-144 0c-13.3 0-24-10.7-24-24s10.7-24 24-24z"/>
                    </svg>
                </span>
                Items de la Sección
            </h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div v-for="(item, index) in formProfessional.items" :key="index" class="space-y-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Item {{ index + 1 }}
                        </span>
                    </div>

                    <!-- Icono -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Icono
                        </label>
                        <div class="flex items-center gap-2">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-400 shrink-0">
                                <font-awesome-icon v-if="item.icon" :icon="formatIconForVue(item.icon)" />
                                <font-awesome-icon v-else :icon="['fas', 'icons']" class="w-4 h-4" />
                            </div>
                            <select
                                v-model="item.icon"
                                class="form-select"
                            >
                                <option value="">Seleccionar icono</option>
                                <optgroup v-for="(icons, category) in iconCategories" :key="category" :label="categoryLabels[category]">
                                    <option v-for="icon in icons" :key="icon.value" :value="icon.value">
                                        {{ icon.label }}
                                    </option>
                                </optgroup>
                            </select>
                        </div>
                    </div>

                    <!-- Título -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Título
                        </label>
                        <input
                            type="text"
                            v-model="item.title"
                            class="form-input"
                            :placeholder="'Título del item ' + (index + 1)"
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
                            class="form-textarea"
                            :placeholder="'Descripción del item ' + (index + 1)"
                        ></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex justify-end">
            <button
                @click="saveProfessionalSettings"
                :disabled="formProfessional.processing"
                :class="{ 'opacity-25': formProfessional.processing }"
                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <IconLoader v-if="formProfessional.processing" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" />
                <IconSave v-else class="mr-2" />
                {{ formProfessional.processing ? 'Guardando...' : 'Guardar Sección Actualización Profesional' }}
            </button>
        </div>
    </div>
</template>
