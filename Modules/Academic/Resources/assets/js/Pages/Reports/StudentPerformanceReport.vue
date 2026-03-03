<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import iconSearch from '@/Components/vristo/icon/icon-search.vue';
    import iconLoader from '@/Components/vristo/icon/icon-loader.vue';
    import { ref } from 'vue';

    const props = defineProps({
        courses: {
            type: Array,
            default: () => []
        }
    });

    const form = ref({
        course_id: null,
        year: null,
        month: null
    });

    const dataStudents = ref([]);
    const loaderSearch = ref(false);

    const currentYear = new Date().getFullYear();
    const years = Array.from({ length: 5 }, (_, i) => currentYear - i);

    const months = [
        { value: 1, label: 'Enero' },
        { value: 2, label: 'Febrero' },
        { value: 3, label: 'Marzo' },
        { value: 4, label: 'Abril' },
        { value: 5, label: 'Mayo' },
        { value: 6, label: 'Junio' },
        { value: 7, label: 'Julio' },
        { value: 8, label: 'Agosto' },
        { value: 9, label: 'Septiembre' },
        { value: 10, label: 'Octubre' },
        { value: 11, label: 'Noviembre' },
        { value: 12, label: 'Diciembre' }
    ];

    const searchPerformanceTable = () => {
        loaderSearch.value = true;
        axios({
            method: 'post',
            url: route('aca_student_performance_report_table'),
            data: form.value
        }).then(function (response) {
            dataStudents.value = response.data.items;
        }).finally(() => {
            loaderSearch.value = false;
        });
    };
</script>

<template>
    <AppLayout title="Reportes">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                {route: route('aca_reports_dashboard'), title: 'Reportes'},
                {title: 'Reporte de Seguimiento de Desempeño de los Estudiantes'}
            ]"
        />
        <div class="mt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl">Filtros de Búsqueda</h2>
                <div class="flex flex-1 sm:flex-row flex-col sm:items-center sm:gap-3 gap-4 w-full sm:w-auto">
                    <div>
                        <select v-model="form.course_id" class="form-select">
                            <option :value="null">Todos los cursos</option>
                            <option v-for="course in courses" :key="course.id" :value="course.id">
                                {{ course.description }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <select v-model="form.year" class="form-select">
                            <option :value="null">Todos los años</option>
                            <option v-for="year in years" :key="year" :value="year">
                                {{ year }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <select v-model="form.month" class="form-select">
                            <option :value="null">Todos los meses</option>
                            <option v-for="month in months" :key="month.value" :value="month.value">
                                {{ month.label }}
                            </option>
                        </select>
                    </div>
                    <button @click="searchPerformanceTable" :class="{ 'opacity-25': loaderSearch }" :disabled="loaderSearch" type="button" class="btn btn-primary flex gap-4">
                        <icon-loader v-if="loaderSearch" class="w-4 h-4" />
                        <icon-search v-else class="w-4 h-4" />
                        Buscar
                    </button>
                </div>
            </div>
            <div class="panel mt-6">
                <div class="table-responsive">
                    <table class="rounded-t-2xl">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Estudiante</th>
                                <th>DNI</th>
                                <th>Curso</th>
                                <th>Fecha de Matrícula</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-if="dataStudents.length > 0">
                                <tr v-for="(item, key) in dataStudents" :key="item.id">
                                    <td>{{ key + 1 }}</td>
                                    <td>
                                        <div class="font-medium">{{ item.student?.full_name }}</div>
                                    </td>
                                    <td class="font-mono">
                                        {{ item.student?.dni }}
                                    </td>
                                    <td>
                                        {{ item.course?.description }}
                                    </td>
                                    <td>
                                        {{ item.created_at }}
                                    </td>
                                    <td>
                                        <span v-if="item.status === 1" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                            Activo
                                        </span>
                                        <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                            Inactivo
                                        </span>
                                    </td>
                                </tr>
                            </template>
                            <template v-else>
                                <tr>
                                    <td colspan="6" class="text-center py-8">
                                        <div class="flex justify-center items-center flex-col">
                                            <svg class="w-12 h-12 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                            </svg>
                                            <p class="text-gray-500">Seleccione los filtros y haga clic en Buscar</p>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
