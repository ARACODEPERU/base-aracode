<script setup>
    import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { Link, useForm, router } from '@inertiajs/vue3';
    import Pagination from "@/Components/Pagination.vue";

    const props = defineProps({
        exams: {
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

    // Formulario con useForm
    const form = useForm({
        search: props.filters.search || '',
        course_id: props.filters.course_id || '',
        per_page: props.filters.per_page || 10,
    });

    // Formatear tiempo
    const formatTime = (seconds) => {
        if (!seconds) return '0 seg';
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        if (mins > 0) {
            return `${mins} min ${secs} seg`;
        }
        return `${secs} seg`;
    };

    // Formatear fecha
    const formatDate = (date) => {
        if (!date) return '-';
        return new Date(date).toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    // Obtener clase de borde segun estado
    const getBorderColor = (exam) => {
        if (exam.status === 'revision_pendiente') return 'border-yellow-500';
        if (exam.status === 'completado') {
            return exam.punctuation >= 11 ? 'border-green-500' : 'border-red-500';
        }
        if (exam.status === 'timeout') return 'border-gray-500';
        if (exam.status === 'pendiente') return 'border-blue-500';
        return 'border-gray-300';
    };

    // Obtener informacion del estado
    const getStatusInfo = (exam) => {
        if (exam.status === 'revision_pendiente') {
            return { label: 'Revision Pendiente', class: 'bg-yellow-500 text-white', icon: '' };
        }
        if (exam.status === 'completado') {
            if (exam.punctuation >= 11) {
                return { label: 'Aprobado', class: 'bg-green-500 text-white', icon: '' };
            }
            return { label: 'Desaprobado', class: 'bg-red-500 text-white', icon: '' };
        }
        if (exam.status === 'timeout') {
            return { label: 'Tiempo Agotado', class: 'bg-gray-500 text-white', icon: '' };
        }
        if (exam.status === 'pendiente') {
            return { label: 'En Progreso', class: 'bg-blue-500 text-white', icon: '' };
        }
        return { label: 'Desconocido', class: 'bg-gray-400 text-white', icon: '' };
    };

    // Calcular nota total del examen
    const getTotalScore = (exam) => {
        if (!exam.exam?.questions) return 20;
        return exam.exam.questions.reduce((sum, q) => sum + (q.score || 0), 0);
    };

    // Verificar si puede descargar solucionario
    const canDownloadSolution = (exam) => {
        return exam.punctuation >= 11 && exam.exam?.file_resolved_path;
    };

    // Descargar PDF del examen
    const downloadExamPdf = (examId) => {
        window.open(route('aca_student_exam_download_pdf', examId), '_blank');
    };

    // Descargar solucionario
    const downloadSolution = (exam) => {
        if (!exam.exam?.file_resolved_path) return;
        const link = document.createElement('a');
        link.href = '/storage/' + exam.exam.file_resolved_path;
        link.download = exam.exam.file_resolved_name || 'solucionario.pdf';
        link.target = '_blank';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };

    // Buscar examenes
    const searchExams = () => {
        form.get(route('aca_student_exam_search'));
    };

    // Limpiar filtros
    const clearFilters = () => {
        form.search = '';
        form.course_id = '';
        form.per_page = 10;
        router.get(route('aca_student_exam_search'));
    };
</script>

<template>
    <AppLayout title="Mis Examenes">
        <Navigation
            :routeModule="route('aca_dashboard')"
            :titleModule="'Academico'"
            :data="[
                { route: route('aca_mycourses'), title: 'Mis Cursos' },
                { title: 'Mis Examenes' }
            ]"
        />

        <div class="mt-5">
            <div class="panel p-0">
                <!-- Header -->
                <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">Mis Examenes</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Encuentra todos tus examenes y revisa tus resultados
                    </p>
                </div>

                <!-- Filtros -->
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex flex-col md:flex-row gap-3 items-end">
                        <!-- Busqueda -->
                        <div class="w-full md:w-64">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Buscar</label>
                            <div class="relative">
                                <input
                                    v-model="form.search"
                                    type="text"
                                    placeholder="Examen o modulo..."
                                    class="form-input w-full pl-10"
                                    @keyup.enter="searchExams"
                                >
                                <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                        </div>

                        <!-- Filtro por curso -->
                        <div class="w-full md:w-64">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Curso</label>
                            <select v-model="form.course_id" class="form-select w-full">
                                <option value="">Todos los cursos</option>
                                <option v-for="course in courses" :key="course.id" :value="course.id">
                                    {{ course.description }}
                                </option>
                            </select>
                        </div>

                        <!-- Botones -->
                        <button
                            @click="searchExams"
                            class="btn btn-primary"
                        >
                            Buscar
                        </button>
                        <button
                            v-if="form.search || form.course_id"
                            @click="clearFilters"
                            class="btn btn-outline-danger"
                        >
                            Limpiar
                        </button>
                    </div>
                </div>

                <!-- Info de resultados -->
                <Pagination :data="exams">
                <!-- Lista de examenes -->
                <div class="p-5">
                    <!-- Sin resultados -->
                    <div v-if="!exams.data || exams.data.length === 0" class="text-center py-12">
                        <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No hay examenes</h3>
                        <p class="text-gray-500 dark:text-gray-400">
                            {{ form.search || form.course_id ? 'No se encontraron resultados con los filtros aplicados' : 'Aun no has realizado ningun examen' }}
                        </p>
                    </div>

                    <!-- Cards de examenes -->
                    <div v-else class="space-y-4">
                        <div
                            v-for="exam in exams.data"
                            :key="exam.id"
                            class="bg-white dark:bg-gray-800 rounded-lg border-l-4 p-4 shadow-sm"
                            :class="getBorderColor(exam)"
                        >
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <!-- Informacion del examen -->
                                <div class="flex-1">
                                    <!-- Nombre del examen -->
                                    <h3 class="font-bold text-gray-900 dark:text-white text-base">
                                        {{ exam.exam?.description || 'Examen' }}
                                    </h3>

                                    <!-- Curso y Modulo -->
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                        <span v-if="exam.exam?.course">{{ exam.exam.course.description }}</span>
                                        <span v-if="exam.exam?.course && exam.exam?.module"> | </span>
                                        <span v-if="exam.exam?.module">{{ exam.exam.module.description }}</span>
                                    </p>

                                    <!-- Estado, Nota y Tiempo -->
                                    <div class="flex flex-wrap items-center gap-3 mt-3">
                                        <!-- Estado -->
                                        <span
                                            class="text-xs px-2 py-1 rounded font-medium"
                                            :class="getStatusInfo(exam).class"
                                        >
                                            {{ getStatusInfo(exam).icon }} {{ getStatusInfo(exam).label }}
                                        </span>

                                        <!-- Nota -->
                                        <span class="text-sm">
                                            <span class="text-gray-500 dark:text-gray-400">Nota:</span>
                                            <span
                                                class="font-bold ml-1"
                                                :class="exam.punctuation >= 11 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                                            >
                                                {{ exam.punctuation || 0 }}
                                            </span>
                                            <span class="text-gray-500 dark:text-gray-400">/ {{ getTotalScore(exam) }}</span>
                                        </span>

                                        <!-- Tiempo -->
                                        <span class="text-sm text-gray-500 dark:text-gray-400">
                                            Tiempo: {{ formatTime(exam.time_spent_seconds) }}
                                        </span>

                                        <!-- Tiempo agotado -->
                                        <span v-if="exam.is_timed_out" class="text-xs text-red-500">
                                            Tiempo agotado
                                        </span>
                                    </div>

                                    <!-- Fecha -->
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">
                                        {{ exam.finished_at ? 'Finalizado: ' + formatDate(exam.finished_at) : 'Iniciado: ' + formatDate(exam.date_start) }}
                                    </p>
                                </div>

                                <!-- Botones de accion -->
                                <div class="flex flex-col sm:flex-row gap-2">
                                    <!-- Ver Resultado PDF -->
                                    <button
                                        v-if="exam.status !== 'pendiente'"
                                        @click="downloadExamPdf(exam.id)"
                                        class="btn btn-primary btn-sm"
                                    >
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                                        </svg>
                                        Ver Resultado
                                    </button>

                                    <!-- Continuar Examen -->
                                    <Link
                                        v-if="exam.status === 'pendiente'"
                                        :href="route('aca_student_module_exam_solve', exam.exam_id)"
                                        class="btn btn-info btn-sm"
                                    >
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                                        </svg>
                                        Continuar
                                    </Link>

                                    <!-- Solucionario -->
                                    <button
                                        v-if="canDownloadSolution(exam)"
                                        @click="downloadSolution(exam)"
                                        class="btn btn-success btn-sm"
                                    >
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                        </svg>
                                        Solucionario
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Paginacion -->
                </Pagination>
            </div>
        </div>
    </AppLayout>
</template>
