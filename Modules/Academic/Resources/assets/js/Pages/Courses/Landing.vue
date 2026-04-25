<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { ref, computed, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';
import InputError from '@/Components/InputError.vue';
import IconSave from "@/Components/vristo/icon/icon-save.vue";
import LandingBanner from './Partials/LandingBanner.vue';
import LandingProfessional from './Partials/LandingProfessional.vue';
import LandingStaff from './Partials/LandingStaff.vue';
import LandingResults from './Partials/LandingResults.vue';
import LandingTestimonials from './Partials/LandingTestimonials.vue';
import LandingStudyPlan from './Partials/LandingStudyPlan.vue';
import LandingProblem from './Partials/LandingProblem.vue';
import LandingInvestment from './Partials/LandingInvestment.vue';
import LandingFaq from './Partials/LandingFaq.vue';
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
    teachers: {
        type: Array,
        default: () => ([]),
    },
    people: {
        type: Array,
        default: () => ([]),
    },
});

const activeTab = ref('banner');
const baseUrl = assetUrl;

const tabs = [
    { id: 'banner', label: 'Banner', icon: 'fa-image' },
    { id: 'professional', label: 'Actualización Profesional', icon: 'fa-graduation-cap' },
    { id: 'staff', label: 'Nuestro Staff', icon: 'fa-users' },
    { id: 'results', label: 'Resultados del Programa', icon: 'fa-chart-line' },
    { id: 'testimonials', label: 'Testimonios', icon: 'fa-comments' },
    { id: 'study_plan', label: 'Plan de Estudios', icon: 'fa-book-open' },
    { id: 'problem', label: 'El Problema', icon: 'fa-exclamation-triangle' },
    { id: 'investment', label: 'Inversión', icon: 'fa-microscope' },
    { id: 'faq', label: 'Preguntas Frecuentes', icon: 'fa-question-circle' },
];

const form = useForm({
    url_slug: props.landing.url_slug || '',
    whatsapp_link: props.landing.whatsapp_link || '',
    is_published: props.landing.is_published || false,
});



const copyToClipboard = async () => {
    const fullUrl = `${baseUrl}curso/${form.url_slug}`;
    try {
        await navigator.clipboard.writeText(fullUrl);
        Swal.fire({
            icon: 'success',
            title: 'Copiado',
            text: 'URL copiada al portapapeles',
            timer: 2000,
            showConfirmButton: false,
        });
    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo copiar la URL',
        });
    }
};

const saveGeneralSettings = () => {
    form.put(route('aca_courses_landing_update_general', props.course.id), {
        errorBag: 'saveGeneralSettings',
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: 'Se guardo correctamente',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        },
    });
};


const formatIconForVue = (iconName) => {
    if (!iconName) return fasIcons.faIcons;
    const cleanName = iconName.replace(/^fa-/, '');
    const pascalName = 'fa' + cleanName
        .split('-')
        .map(part => part.charAt(0).toUpperCase() + part.slice(1))
        .join('');
    return fasIcons[pascalName] || fasIcons.faIcons;
};


</script>

<template>
    <AppLayout title="Configuración de Landing">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                {route: route('aca_courses_list'), title: 'Cursos'},
                {route: route('aca_courses_edit', course.id), title: course.description},
                {title: 'Landing'}
            ]"
        />

        <div class="pt-5">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <!-- DATOS GENERALES (Siempre visible, antes de tabs) -->
                <div class="px-6 py-4">
                    <div class="flex items-center gap-2 ">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-400">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                <path fill="currentColor" d="M195.1 9.5C198.1-5.3 211.2-16 226.4-16l59.8 0c15.2 0 28.3 10.7 31.3 25.5L332 79.5c14.1 6 27.3 13.7 39.3 22.8l67.8-22.5c14.4-4.8 30.2 1.2 37.8 14.4l29.9 51.8c7.6 13.2 4.9 29.8-6.5 39.9L447 233.3c.9 7.4 1.3 15 1.3 22.7s-.5 15.3-1.3 22.7l53.4 47.5c11.4 10.1 14 26.8 6.5 39.9l-29.9 51.8c-7.6 13.1-23.4 19.2-37.8 14.4l-67.8-22.5c-12.1 9.1-25.3 16.7-39.3 22.8l-14.4 69.9c-3.1 14.9-16.2 25.5-31.3 25.5l-59.8 0c-15.2 0-28.3-10.7-31.3-25.5l-14.4-69.9c-14.1-6-27.2-13.7-39.3-22.8L73.5 432.3c-14.4 4.8-30.2-1.2-37.8-14.4L5.8 366.1c-7.6-13.2-4.9-29.8 6.5-39.9l53.4-47.5c-.9-7.4-1.3-15-1.3-22.7s.5-15.3 1.3-22.7L12.3 185.8c-11.4-10.1-14-26.8-6.5-39.9L35.7 94.1c7.6-13.2 23.4-19.2 37.8-14.4l67.8 22.5c12.1-9.1 25.3-16.7 39.3-22.8L195.1 9.5zM256.3 336a80 80 0 1 0 -.6-160 80 80 0 1 0 .6 160z"/>
                            </svg>
                        </span>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Configuración de la Landing
                        </h3>
                    </div>
                </div>
                <div class="p-6 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border-b border-gray-200 dark:border-gray-700">
                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                        <!-- Estado Publicar -->
                        <div class="lg:col-span-1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-0">
                                <span class="inline-flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Estado de Publicación
                                </span>
                            </label>
                            <div class="flex items-center gap-3 p-2 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                <label class="relative inline-flex items-center cursor-pointer p-0 mb-0">
                                    <input type="checkbox" v-model="form.is_published" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-green-500"></div>
                                    <span class="ml-3 text-sm font-medium" :class="form.is_published ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400'">
                                        {{ form.is_published ? '✅ Publicada' : '❌ No Publicada' }}
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- URL Slug -->
                        <div class="lg:col-span-3">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-0">
                                <span class="inline-flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                    </svg>
                                    URL Amigable
                                    <p class="mt-1 text-xs text-gray-500 ml-6">Solo letras minúsculas, números y guiones</p>
                                </span>
                            </label>
                            <div class="flex rounded-md shadow-sm">
                                <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-sm">
                                    {{ baseUrl }}academy/
                                </span>
                                <input
                                    type="text"
                                    v-model="form.url_slug"
                                    placeholder="nombre-del-curso"
                                    class="flex-1 block w-full rounded-none rounded-r-none border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 border"
                                >
                                <button
                                    @click="copyToClipboard"
                                    class="btn btn-success text-xs uppercase flex items-center justify-center rounded-l-none border-l-0"
                                >
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                    Copiar
                                </button>
                            </div>
                            <InputError :message="form.errors.url_slug"  />
                        </div>
                        <!-- WhatsApp Link -->
                        <div class="lg:col-span-3">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-0">
                                <span class="inline-flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                    </svg>
                                    WhatsApp
                                </span>
                            </label>
                            <input
                                type="text"
                                v-model="form.whatsapp_link"
                                placeholder="https://wa.me/..."
                                class="form-input"
                            >
                            <InputError :message="form.errors.whatsapp_link" class="mt-2" />
                        </div>

                        <!-- Botón Copiar + Guardar -->
                        <div class="lg:col-span-1 flex items-end">
                            <button
                                @click="saveGeneralSettings"
                                :disabled="form.processing"
                                :class="{ 'opacity-25': form.processing }"
                                class="btn btn-primary text-xs uppercase flex items-center justify-center w-full"
                            >
                                <IconLoader v-if="form.processing" class="animate-spin -ml-1 mr-1 h-4 w-4" />
                                <IconSave v-else class="mr-1" />
                                Guardar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabs Navigation -->
                <div class="p-4 bg-gray-50 dark:bg-gray-800/50">
                    <nav class="grid grid-cols-4 sm:grid-cols-5 md:grid-cols-6 lg:grid-cols-9 gap-2">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            :class="[
                                activeTab === tab.id
                                    ? 'bg-blue-600 text-white shadow-lg'
                                    : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                                'flex flex-col items-center justify-center p-3 rounded-lg transition-all duration-200 border border-gray-200 dark:border-gray-700'
                            ]"
                        >
                            <font-awesome-icon
                                class="w-8 h-8"
                                :icon="formatIconForVue(tab.icon)"
                                :class="['text-lg mb-1', activeTab === tab.id ? 'text-white' : 'text-gray-500 dark:text-gray-400']"
                            />
                            <span class="text-[12px] font-medium text-center leading-tight line-clamp-2">
                                {{ tab.label }}
                            </span>
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div class="p-6">
                    <!-- Banner Section -->
                    <div v-show="activeTab === 'banner'">
                        <LandingBanner
                            :course="course"
                            :landing="landing"
                            :language-options="languageOptions"
                        />
                    </div>

                    <!-- Professional Section -->
                    <div v-show="activeTab === 'professional'">
                        <LandingProfessional
                            :course="course"
                            :landing="landing"
                            :language-options="languageOptions"
                        />
                    </div>

                    <!-- Staff Section -->
                    <div v-show="activeTab === 'staff'">
                        <LandingStaff
                            :course="course"
                            :landing="landing"
                            :teachers="teachers"
                        />
                    </div>

                    <!-- Results Section -->
                    <div v-show="activeTab === 'results'">
                        <LandingResults
                            :course="course"
                            :landing="landing"
                            :language-options="languageOptions"
                        />
                    </div>

                    <!-- Testimonials Section -->
                    <div v-show="activeTab === 'testimonials'">
                        <LandingTestimonials
                            :course="course"
                            :landing="landing"
                            :people="people"
                        />
                    </div>

                    <!-- Study Plan Section -->
                    <div v-show="activeTab === 'study_plan'">
                        <LandingStudyPlan
                            :course="course"
                            :landing="landing"
                        />
                    </div>

                    <!-- Problem Section -->
                    <div v-show="activeTab === 'problem'">
                        <LandingProblem
                            :course="course"
                            :landing="landing"
                        />
                    </div>

                    <!-- Investment Section -->
                    <div v-show="activeTab === 'investment'">
                        <LandingInvestment
                            :course="course"
                            :landing="landing"
                        />
                    </div>

                    <!-- FAQ Section -->
                    <div v-show="activeTab === 'faq'">
                        <LandingFaq
                            :course="course"
                            :landing="landing"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
