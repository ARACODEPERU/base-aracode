<script setup>
    import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { Link, useForm, router } from '@inertiajs/vue3';
    import Pagination from "@/Components/Pagination.vue";

    const props = defineProps({
        certificates: {
            type: Object,
            default: () => ({}),
        },
        courses: {
            type: Array,
            default: () => [],
        },
        filters: {
            type: Object,
            default: () => ({}),
        }
    });

    const form = useForm({
        course_id: props.filters.course_id || '',
        per_page: props.filters.per_page || 12,
    });

    const formatDate = (date) => {
        if (!date) return '-';
        return new Date(date).toLocaleDateString('es-ES', {
            day: '2-digit',
            month: 'long',
            year: 'numeric'
        });
    };

    const searchCertificates = () => {
        form.get(route('aca_student_certificates_all'));
    };

    const clearFilters = () => {
        form.course_id = '';
        form.per_page = 12;
        router.get(route('aca_student_certificates_all'));
    };

    const downloadCertificate = (certificateId) => {
        window.open(route('aca_image_download', certificateId), '_blank');
    };
</script>

<template>
    <AppLayout title="Mis Certificados">
        <Navigation
            :routeModule="route('aca_dashboard')"
            :titleModule="'Academico'"
            :data="[
                { route: route('aca_mycourses'), title: 'Mis Cursos' },
                { title: 'Mis Certificados' }
            ]"
        />

        <div class="mt-5">
            <div class="panel p-0">
                <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">Mis Certificados</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Descarga tus certificados obtenidos
                    </p>
                </div>

                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex flex-col md:flex-row gap-3 items-end">
                        <div class="w-full md:w-64">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Curso</label>
                            <select v-model="form.course_id" class="form-select w-full">
                                <option value="">Todos los cursos</option>
                                <option v-for="course in courses" :key="course.id" :value="course.id">
                                    {{ course.description }}
                                </option>
                            </select>
                        </div>

                        <button @click="searchCertificates" class="btn btn-primary">
                            Buscar
                        </button>
                        <button
                            v-if="form.course_id"
                            @click="clearFilters"
                            class="btn btn-outline-danger"
                        >
                            Limpiar
                        </button>
                    </div>
                </div>

                <Pagination :data="certificates">
                <div class="p-5">
                    <div v-if="!certificates.data || certificates.data.length === 0" class="text-center py-16">
                        <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 flex items-center justify-center">
                            <svg class="w-12 h-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No hay certificados</h3>
                        <p class="text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                            Aun no has obtenido ningun certificado. Completa tus cursos para obtener tus certificados.
                        </p>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div
                            v-for="certificate in certificates.data"
                            :key="certificate.id"
                            class="group relative bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300"
                        >
                            <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 via-purple-500/5 to-pink-500/5 dark:from-blue-500/10 dark:via-purple-500/10 dark:to-pink-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

                            <div class="relative h-48 bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 dark:from-gray-700 dark:via-gray-700 dark:to-gray-700 flex items-center justify-center overflow-hidden">
                                <div class="absolute inset-0 bg-gradient-to-t from-white/20 to-transparent dark:from-gray-600/20"></div>
                                <div class="relative z-10 text-center px-4">
                                    <div class="w-20 h-20 mx-auto mb-3 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center shadow-lg">
                                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                        </svg>
                                    </div>
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Certificado
                                    </span>
                                </div>
                                <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500"></div>
                            </div>

                            <div class="relative p-5">
                                <h3 class="font-bold text-gray-900 dark:text-white text-base mb-1 line-clamp-2">
                                    {{ certificate.course?.description || 'Curso' }}
                                </h3>
                                <p v-if="certificate.module" class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                                    {{ certificate.module.description }}
                                </p>
                                <div class="flex items-center justify-between mt-4">
                                    <div class="text-xs text-gray-400 dark:text-gray-500">
                                        <span class="block">Emitido</span>
                                        <span class="font-medium text-gray-600 dark:text-gray-300">{{ formatDate(certificate.created_at) }}</span>
                                    </div>
                                    <button
                                        @click="downloadCertificate(certificate.id)"
                                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gradient-to-r from-blue-500 to-purple-600 text-white text-sm font-medium hover:from-blue-600 hover:to-purple-700 transition-all duration-200 shadow-md hover:shadow-lg"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        Descargar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                </Pagination>
            </div>
        </div>
    </AppLayout>
</template>
