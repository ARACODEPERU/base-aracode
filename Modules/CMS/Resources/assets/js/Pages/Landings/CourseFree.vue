<script setup>
    import { ref } from 'vue';
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { useForm } from '@inertiajs/vue3';
    import Swal2 from "sweetalert2";
    import FormConfCourses from './Partials/FormConfCourses.vue';
    import LandingUtmLinks from './Partials/LandingUtmLinks.vue';
    import LandingRegistros from './Partials/LandingRegistros.vue';
    import HelpTip from './Partials/HelpTip.vue';

    defineProps({
        courses: {
            type: Object,
            default: () => ({})
        },
        tinykey: {
            type: String,
            default: null,
        },
        landingPage: {
            type: Object,
            default: () => ({})
        },
        utmStats: {
            type: Object,
            default: () => ({ subscribers: [], social_detail: [], referrer_detail: [] })
        },
        // Enlace publico del landing, generado por el servidor.
        publicUrl: {
            type: String,
            default: '',
        },
        // Vista previa del mismo landing (no guarda formularios).
        previewUrl: {
            type: String,
            default: '',
        }
    });

    const activeTab = ref('general');
</script>
<template>
    <AppLayout title="Resumen">
        <Navigation :routeModule="route('cms_dashboard')" :titleModule="'CMS'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Configuracion Landing curso gratis</span>
            </li>
        </Navigation>
        <div class="mt-5">
            <!-- Ruta final del landing y acceso a la vista previa -->
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-800">
                <div class="min-w-0">
                    <span class="text-xs font-semibold uppercase text-gray-400">Ruta final del landing</span>
                    <p class="mt-0.5 break-all font-mono text-sm text-primary">{{ publicUrl || 'Guarda la configuración para generarla' }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a
                        v-if="previewUrl && landingPage?.id"
                        :href="previewUrl"
                        target="_blank"
                        rel="noopener"
                        class="btn btn-outline-primary text-xs uppercase"
                        title="Abre el landing tal como lo verá el visitante, sin guardar formularios"
                    >
                        Vista previa
                    </a>
                    <a
                        v-if="publicUrl && landingPage?.id"
                        :href="publicUrl"
                        target="_blank"
                        rel="noopener"
                        class="btn btn-primary text-xs uppercase"
                    >
                        Abrir landing
                    </a>
                    <span v-if="!landingPage?.id" class="text-xs text-gray-400">Guarda primero la configuración general.</span>
                </div>
            </div>

            <div class="mb-5 flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    class="btn text-xs uppercase"
                    :class="activeTab === 'general' ? 'btn-primary' : 'btn-outline-primary'"
                    @click="activeTab = 'general'"
                >
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                    Configuracion general
                </button>
                <HelpTip
                    text="Aquí defines el contenido del landing: título, descripción, imagen, cuántos cursos gratis muestra y los textos de su formulario."
                />
                <button
                    type="button"
                    class="btn text-xs uppercase"
                    :class="activeTab === 'utm' ? 'btn-primary' : 'btn-outline-primary'"
                    @click="activeTab = 'utm'"
                >
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 010 5.656l-3 3a4 4 0 01-5.656-5.656l1.5-1.5m3.828-4.5l1.5-1.5a4 4 0 015.656 5.656l-3 3a4 4 0 01-5.656 0" />
                    </svg>
                    Enlaces UTM
                </button>
                <HelpTip
                    text="Genera enlaces con UTM para cada red social y muestra de qué canal llegaron las personas que enviaron el formulario."
                />
                <button
                    type="button"
                    class="btn text-xs uppercase"
                    :class="activeTab === 'registros' ? 'btn-primary' : 'btn-outline-primary'"
                    @click="activeTab = 'registros'"
                >
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Registros
                </button>
                <HelpTip
                    text="Muestra u oculta los datos que dejaron en el formulario, en una tabla, y permite descargarlos en Excel o PDF."
                />
            </div>
            <div class="panel">
                <FormConfCourses v-if="activeTab === 'general'" :courses="courses" :tinykey="tinykey" :landingPage="landingPage" />
                <LandingUtmLinks v-else-if="activeTab === 'utm'" :landing="landingPage" :utmStats="utmStats" :publicUrl="publicUrl" />
                <LandingRegistros v-else :landing="landingPage" />
            </div>
        </div>
    </AppLayout>
</template>
