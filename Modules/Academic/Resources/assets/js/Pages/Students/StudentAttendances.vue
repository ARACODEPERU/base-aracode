<script setup>
    import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { ref, computed } from 'vue';
    import axios from 'axios';
    import Multiselect from '@suadelabs/vue3-multiselect';
    import '@suadelabs/vue3-multiselect/dist/vue3-multiselect.css';

    const props = defineProps({
        courses: {
            type: Array,
            default: () => [],
        }
    });

    // Estado
    const selectedCourse = ref(null);
    const loading = ref(false);
    const attendances = ref([]);
    const hasSearched = ref(false);

    // Formulario de filtros
    const form = ref({
        date_start: '',
        date_end: '',
    });

    // Formatear fecha
    const formatDate = (date) => {
        if (!date) return '-';
        return new Date(date).toLocaleString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    // Obtener clase según asistencia
    const getAttendanceClass = (hasAttendance) => {
        return hasAttendance
            ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
            : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
    };

    // Obtener texto de asistencia
    const getAttendanceText = (hasAttendance) => {
        return hasAttendance ? 'Sí' : 'No';
    };

    // Buscar asistencias
    const searchAttendances = async () => {
        if (!selectedCourse.value) {
            alert('Por favor seleccione un curso');
            return;
        }

        loading.value = true;
        hasSearched.value = true;

        try {
            const response = await axios.get(route('aca_student_attendances_search'), {
                params: {
                    course_id: selectedCourse.value.id,
                    date_start: form.value.date_start || null,
                    date_end: form.value.date_end || null,
                }
            });

            attendances.value = response.data.attendances;
        } catch (error) {
            console.error('Error searching attendances:', error);
            attendances.value = [];
        } finally {
            loading.value = false;
        }
    };

    // Limpiar filtros
    const clearFilters = () => {
        selectedCourse.value = null;
        form.value.date_start = '';
        form.value.date_end = '';
        attendances.value = [];
        hasSearched.value = false;
    };
</script>

<template>
    <AppLayout title="Mis Asistencias">
        <Navigation
            :routeModule="route('aca_dashboard')"
            :titleModule="'Academico'"
            :data="[
                { route: route('aca_mycourses'), title: 'Mis Cursos' },
                { title: 'Mis Asistencias' }
            ]"
        />

        <div class="mt-5">
            <div class="panel p-0 rounded-2xl overflow-hidden">
                <!-- Header -->
                <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">Mis Asistencias</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Consulta tu historial de asistencia por curso
                    </p>
                </div>

                <!-- Filtros -->
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex flex-col lg:flex-row gap-3 items-end">
                        <!-- Multiselect de cursos -->
                        <div class="w-full lg:w-80">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Curso
                            </label>
                            <multiselect
                                v-model="selectedCourse"
                                :options="courses"
                                :searchable="true"
                                placeholder="Buscar curso..."
                                selected-label="seleccionado"
                                select-label="Elegir"
                                deselect-label="Quitar"
                                label="description"
                                track-by="id"
                                class="custom-multiselect"
                            ></multiselect>
                        </div>

                        <!-- Filtro fecha inicio -->
                        <div class="w-full lg:w-40">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Desde (Opcional)
                            </label>
                            <input
                                v-model="form.date_start"
                                type="date"
                                class="form-input w-full"
                            />
                        </div>

                        <!-- Filtro fecha fin -->
                        <div class="w-full lg:w-40">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Hasta (Opcional)
                            </label>
                            <input
                                v-model="form.date_end"
                                type="date"
                                class="form-input w-full"
                            />
                        </div>

                        <!-- Botón consultar -->
                        <button
                            @click="searchAttendances"
                            :disabled="loading || !selectedCourse"
                            class="btn btn-primary"
                            :class="{ 'opacity-50': loading || !selectedCourse }"
                        >
                            <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ loading ? 'Buscando...' : 'Consultar' }}
                        </button>

                        <!-- Botón limpiar -->
                        <button
                            v-if="hasSearched"
                            @click="clearFilters"
                            class="btn btn-outline-danger"
                        >
                            Limpiar
                        </button>
                    </div>
                </div>

                <!-- Resultados -->
                <div class="">
                    <!-- Loading -->
                    <div v-if="loading" class="text-center py-12">
                        <svg class="animate-spin mx-auto h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="mt-2 text-gray-500 dark:text-gray-400">Buscando asistencias...</p>
                    </div>

                    <!-- Sin resultados - Sin buscar -->
                    <div v-else-if="!hasSearched" class="text-center py-12">
                        <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Consulta tus asistencias</h3>
                        <p class="text-gray-500 dark:text-gray-400">
                            Selecciona un curso y haz clic en "Consultar" para ver tu historial de asistencia
                        </p>
                    </div>

                    <!-- Sin resultados - Buscado pero vacío -->
                    <div v-else-if="attendances.length === 0" class="text-center py-12">
                        <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No se encontraron registros</h3>
                        <p class="text-gray-500 dark:text-gray-400">
                            No hay asistencia registrada para el curso y fechas seleccionadas
                        </p>
                    </div>

                    <!-- Tabla de resultados -->
                    <div v-else class="table-responsive">
                        <table class="w-full">
                            <thead>
                                <tr>
                                    <th class="text-left">Módulo</th>
                                    <th class="text-left">Tema</th>
                                    <th class="text-left">Contenido</th>
                                    <th class="text-center">Asistió</th>
                                    <th class="text-left">Hora de Registro</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="attendance in attendances"
                                    :key="attendance.id"
                                    class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800"
                                >
                                    <td class="py-3 px-4 text-gray-900 dark:text-white">
                                        {{ attendance.module?.description || '-' }}
                                    </td>
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-300">
                                        {{ attendance.theme || '-' }}
                                    </td>
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-300">
                                        {{ attendance.content?.description || '-' }}
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                            :class="getAttendanceClass(attendance.has_attendance)"
                                        >
                                            {{ getAttendanceText(attendance.has_attendance) }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-300">
                                        {{ formatDate(attendance.registered_at) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Info de resultados -->
                        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                Total de registros: <strong>{{ attendances.length }}</strong>
                            </span>
                        </div>

                        <!-- Leyenda de colores -->
                        <div class="px-4 py-3 bg-blue-50 dark:bg-blue-900/20 border-t border-blue-100 dark:border-blue-800">
                            <div class="flex flex-wrap items-center gap-4 text-xs">
                                <span class="text-gray-600 dark:text-gray-300 font-medium">Leyenda de notas:</span>
                                <span class="inline-flex items-center gap-1">
                                    <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                                    <span class="text-blue-700 dark:text-blue-300">12 = Por asistencia</span>
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <span class="w-3 h-3 rounded-full bg-green-500"></span>
                                    <span class="text-green-700 dark:text-green-300">≥ 11 = Aprobado</span>
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <span class="w-3 h-3 rounded-full bg-red-500"></span>
                                    <span class="text-red-700 dark:text-red-300">&lt; 11 = Desaprobado</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
