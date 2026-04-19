<script setup>
    import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
    import Pagination from '@/Components/Pagination.vue';
    import Swal2 from "sweetalert2";
    import { Link, router, useForm } from '@inertiajs/vue3';
    import { faXmark, faGears, faTrashAlt, faCheck, faSpellCheck, faDownload, faPlay, faFile, faFilm, faEye, faEdit, faUsers, faBook, faHandshake, faLayerGroup, faSearch, faFilter, faClipboardCheck, faComments, faLandmark } from "@fortawesome/free-solid-svg-icons";
    import ModalLarge from '@/Components/ModalLarge.vue';
    import { ref, computed, watch } from 'vue';
    import DangerButton from '@/Components/DangerButton.vue';
    import InputError from '@/Components/InputError.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import IconPlus from '@/Components/vristo/icon/icon-plus.vue';
    import IconSearch from '@/Components/vristo/icon/icon-search.vue';
    import IconLoader from '@/Components/vristo/icon/icon-loader.vue';

    const props = defineProps({
        courses: {
            type: Object,
            default: () => ({}),
        },
        institutions: {
            type: Object,
            default: () => ({}),
        },
        filters: {
            type: Object,
            default: () => ({}),
        },
        categories: {
            type: Object,
            default: () => ({}),
        },
        modalities: {
            type: Object,
            default: () => ({}),
        },
        types: {
            type: Array,
            default: () => ([]),
        },
        coursesActive: {
            type: Number,
            default: 0,
        }
    });

    const form = useForm({
        search: props.filters.search,
        category: props.filters.category,
        modality: props.filters.modality,
        status: props.filters.status ?? 9,
        sort_order: null,
        sort_by: null
    });

    // Examen de curso
    const displayCourseExamModal = ref(false);
    const durationHours = ref(0);
    const durationMinutes = ref(0);

    const courseExamForm = useForm({
        id: null,
        course_id: null,
        course_description: null,
        description: null,
        date_start: null,
        date_end: null,
        duration_minutes: null,
        answer_key_pdf: null,
        status: 1,
        attempts: 1,
        file_resolved_name: null
    });

    const openCourseExamModal = (course) => {
        courseExamForm.reset();
        courseExamForm.course_id = course.id;
        courseExamForm.course_description = course.description;

        if (course.exam) {
            courseExamForm.id = course.exam.id;
            courseExamForm.description = course.exam.description;
            courseExamForm.date_start = course.exam.date_start;
            courseExamForm.date_end = course.exam.date_end;
            courseExamForm.duration_minutes = course.exam.duration_minutes;
            courseExamForm.answer_key_pdf = course.exam.answer_key_pdf;
            courseExamForm.status = course.exam.status;
            courseExamForm.attempts = course.exam.attempts;
            courseExamForm.file_resolved_name = course.exam.file_resolved_name;

            // Convertir minutos a horas y minutos
            if (course.exam.duration_minutes) {
                durationHours.value = Math.floor(course.exam.duration_minutes / 60);
                durationMinutes.value = course.exam.duration_minutes % 60;
            }
        } else {
            durationHours.value = 0;
            durationMinutes.value = 0;
        }

        displayCourseExamModal.value = true;
    };

    const closeCourseExamModal = () => {
        displayCourseExamModal.value = false;
        courseExamForm.reset();
    };

    const updateCourseExamDuration = () => {
        courseExamForm.duration_minutes = (durationHours.value * 60) + durationMinutes.value;
    };

    const saveCourseExam = () => {
        updateCourseExamDuration();

        if (!courseExamForm.duration_minutes || courseExamForm.duration_minutes <= 0) {
            Swal2.fire({
                title: 'Advertencia',
                text: 'Por favor, establece una duración válida para el examen',
                icon: 'warning',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
            return;
        }

        courseExamForm.post(route('aca_course_exam_update_create'), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                closeCourseExamModal();
                Swal2.fire({
                    title: '¡Enhorabuena!',
                    text: 'Se registro correctamente',
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
            },
        });
    };

    const formatDuration = (minutes) => {
        if (!minutes) return '0 min';
        const h = Math.floor(minutes / 60);
        const m = minutes % 60;
        return `${h}h ${m}m`;
    };

    // Watch to update hours and minutes when loading existing exam
    watch(() => courseExamForm.duration_minutes, (newValue) => {
        if (newValue) {
            durationHours.value = Math.floor(newValue / 60);
            durationMinutes.value = newValue % 60;
        } else {
            durationHours.value = 0;
            durationMinutes.value = 0;
        }
    });


    const destroyCourse = (id) => {
        Swal2.fire({
            title: '¿Estas seguro?',
            text: "¡No podrás revertir esto!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '¡Sí, Eliminar!',
            cancelButtonText: 'Cancelar',
            showLoaderOnConfirm: true,
            padding: '2em',
            customClass: 'sweet-alerts',
            preConfirm: () => {
                return axios.delete(route('aca_courses_destroy', id)).then((res) => {
                    if (!res.data.success) {
                        Swal2.showValidationMessage(res.data.message)
                    }
                    return res
                });
            },
            allowOutsideClick: () => !Swal2.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                Swal2.fire({
                    title: 'Enhorabuena',
                    text: 'Se Eliminó correctamente',
                    icon: 'success',
                });
                router.visit(route('aca_courses_list'), {
                    replace: false,
                    method: 'get',
                    preserveState: true,
                    preserveScroll: true,
                });
            }
        });
    }

    const destroyAgreement = (id) => {
        Swal2.fire({
            title: '¿Estas seguro?',
            text: "¡No podrás revertir esto!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '¡Sí, Eliminar!',
            cancelButtonText: 'Cancelar',
            showLoaderOnConfirm: true,
            padding: '2em',
            customClass: 'sweet-alerts',
            preConfirm: () => {
                return axios.delete(route('aca_agreements_destroy', id)).then((res) => {
                    if (!res.data.success) {
                        Swal2.showValidationMessage(res.data.message)
                    }
                    return res
                });
            },
            allowOutsideClick: () => !Swal2.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                Swal2.fire({
                    title: 'Enhorabuena',
                    text: 'Se Eliminó correctamente',
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                getAgreements(formAgreement.course_id);
            }
        });
    }

    const formAgreement = useForm({
        course_id: null,
        course_name: null,
        institution_id: null,
        start_date: null,
        end_date: null,
        status: null,
        details:[]
    });

    const displayModalAgreements = ref(false);
    const arrayAgreements = ref([]);

    const openModalAgreements = (course) => {
        formAgreement.course_id = course.id;
        formAgreement.course_name = course.description;

        getAgreements(course.id);

        displayModalAgreements.value = true;
    }

    const closeModalAgreements = () => {
        displayModalAgreements.value = false;
        formAgreement.reset();
    }

    const saveAgreements = () => {
        formAgreement.progress = true;
        axios.post(route('aca_agreements_store'), formAgreement ).then((res) => {

            formAgreement.progress = false;
            Swal2.fire({
                title: 'Enhorabuena',
                text: 'Se registró correctamente',
                icon: 'success',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
            getAgreements(formAgreement.course_id);
            formAgreement.reset('institution_id', 'start_date','end_date')
            formAgreement.clearErrors();
        }).catch(function (error) {
            if (error.response.status === 422) {
                // Obtén los errores del objeto de respuesta JSON
                const errors = error.response.data.errors;

                for (let field in errors) {
                    formAgreement.setError(field, errors[field][0]);
                }

            }
            formAgreement.progress = false;
        });
    }

    const getAgreements = (id) => {
        axios.get(route('aca_agreements_list', id) ).then((res) => {
            arrayAgreements.value = res.data.agreements;
        });
    }

    const baseUrl = assetUrl;

    const getImage = (path) => {
        return baseUrl + 'storage/'+ path;
    }

    // Fallback image when course has no image
    const fallbackImage = '/img/course-placeholder.jpg';

    // Filter states
    const showFilters = ref(false);
    // Toggle filters panel
    const toggleFilters = () => {
        showFilters.value = !showFilters.value;
    };

    const clearSearch = () => {
        form.reset();

        router.visit(route('aca_courses_list'), {
            method: 'get',
            data: {},
            replace: false,
            preserveState: false,
            preserveScroll: false,
            only: ['courses', 'filters'],
        })
    }

</script>

<template>
    <AppLayout title="Cursos">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                {title: 'Cursos'}
            ]"
        />
        <div class="pt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="relative">
                    <input
                        type="text"
                        placeholder="Buscar"
                        class="form-input pl-8"
                        v-model="form.search"
                        @keyup.enter="form.get(route('aca_courses_list'))"
                    />
                    <div class="absolute left-[11px] top-1/2 -translate-y-1/2 peer-focus:text-primary">
                        <icon-search class="w-4 h-4" />
                    </div>
                </div>

                <div class="flex gap-3">
                    <button
                        @click="toggleFilters"
                        class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                    >
                        <font-awesome-icon :icon="faFilter" class="w-4 h-4" />
                        <span>Filtros</span>
                        <span v-if="form.category || form.status !== '' || form.modality" class="ml-1 px-2 py-0.5 bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-300 rounded-full text-xs">Activo</span>
                    </button>
                    <Link :href="route('aca_courses_create')" type="button" class="btn btn-primary">
                        <icon-plus class="ltr:mr-2 rtl:ml-2" />
                        Nuevo
                    </Link>
               </div>
            </div>
            <!-- Enhanced Search and Filters Section -->
            <div class="mt-5 space-y-4">
                <!-- Filters Panel -->
                <div v-if="showFilters" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 animate-in slide-in-from-top-2 duration-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                        <!-- Category Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Categoría</label>
                            <select @change="form.get(route('aca_courses_list'))" v-model="form.category" class="form-input w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option :value="null">Todas las categorías</option>
                                <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.description }}</option>
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Estado</label>
                            <select v-model="form.status" @change="form.get(route('aca_courses_list'))" class="form-input w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option :value="9">Todos los estados</option>
                                <option :value="1">Activos</option>
                                <option :value="0">Inactivos</option>
                            </select>
                        </div>

                        <!-- Modality Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Modalidad</label>
                            <select v-model="form.modality" @change="form.get(route('aca_courses_list'))" class="form-input w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option :value="null">Todas las modalidades</option>
                                <option v-for="modality in modalities" :key="modality.id" :value="modality.id">{{ modality.description }}</option>
                            </select>
                        </div>

                        <!-- <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Ordenar por</label>
                            <select v-model="form.sort_by" class="form-input w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="description">Nombre</option>
                                <option value="category_id">Categoría</option>
                                <option value="modality_id">Modalidad</option>
                                <option value="status">Estado</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Orden</label>
                            <select v-model="form.sort_order" class="form-input w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="asc">Ascendente</option>
                                <option value="desc">Descendente</option>
                            </select>
                        </div> -->
                    </div>

                    <!-- Clear Filters -->
                    <div class="mt-4 flex justify-end">
                        <button
                            @click="clearSearch"
                            class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition-colors"
                        >
                            Limpiar filtros
                        </button>
                    </div>
                </div>

            </div>

            <!-- Visual Gallery View (Image-focused design) -->
            <div class="mt-6">
                <!-- Active Courses Gallery -->
                <div>
                    <div class="grid sm:grid-cols-2 gap-6 w-full mb-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-4 h-4 bg-green-500 rounded-full"></div>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Cursos Activos</h3>
                                <span class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-3 py-1 rounded-full text-sm font-medium">
                                    {{ coursesActive }}
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center justify-end">
                            <div class="flex items-center gap-4">
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Mostrando <span class="font-medium text-gray-900 dark:text-white">{{ courses.total }}</span> cursos
                                </p>
                                <div v-if="form.category || form.status !== '' || form.modality" class="flex items-center gap-2">
                                    <span class="text-xs bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-300 px-2 py-1 rounded-full">
                                        Con filtros aplicados
                                    </span>
                                    <button
                                        @click="form.reset()"
                                        class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 underline"
                                    >
                                        Limpiar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-6">
                        <div v-for="course in courses.data" :key="course.id"
                             class="group relative bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 hover:scale-[1.02] flex flex-col h-full">
                            <!-- Course Image -->
                            <div class="relative h-48 overflow-hidden bg-gradient-to-br from-blue-50 to-purple-50 dark:from-gray-700 dark:to-gray-600">
                                <img :src="course.image ? getImage(course.image) : fallbackImage"
                                     :alt="course.description"
                                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                                     @error="$event.target.src = fallbackImage" />

                                <!-- Overlay with Quick Actions -->
                                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                    <div class="absolute bottom-4 left-4 right-4 flex gap-2">
                                        <Link :href="route('aca_courses_edit', course.id)"
                                              class="flex-1 bg-white/90 backdrop-blur-sm text-gray-900 px-3 py-2 rounded-lg text-sm font-medium hover:bg-white transition-colors flex items-center justify-center gap-1">
                                            <font-awesome-icon :icon="faEdit" class="text-xs" />
                                            Editar
                                        </Link>
                                        <Link v-can="'aca_cursos_listado_estudiantes'" :href="route('aca_enrolledstudents_list', course.id)"
                                              class="flex-1 bg-blue-500/90 backdrop-blur-sm text-white px-3 py-2 rounded-lg text-sm font-medium hover:bg-blue-600 transition-colors flex items-center justify-center gap-1">
                                            <font-awesome-icon :icon="faUsers" class="text-xs" />
                                            Alumnos
                                        </Link>
                                    </div>
                                </div>

                                <!-- Status Badge -->
                                <div class="absolute top-4 right-4">
                                    <span v-if="course.status" class="bg-green-500 text-white px-2 py-1 rounded-full text-xs font-medium flex items-center gap-1">
                                        <span class="w-2 h-2 bg-white rounded-full"></span>
                                        Activo
                                    </span>
                                    <span v-else class="bg-red-500 text-white px-2 py-1 rounded-full text-xs font-medium flex items-center gap-1">
                                        <span class="w-2 h-2 bg-white rounded-full"></span>
                                        Inactivo
                                    </span>
                                </div>
                            </div>

                            <!-- Course Info -->
                            <div class="p-4 flex flex-col flex-grow">
                                <h4 class="font-bold text-gray-900 dark:text-white mb-2 line-clamp-2 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                    {{ course.description }}
                                </h4>

                                <div class="flex flex-wrap gap-1.5 mb-3">
                                    <span v-if="course.category" class="text-xs bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300 px-2 py-1 rounded-full">
                                        {{ course.category.description }}
                                    </span>
                                    <span v-if="course.modality" class="text-xs bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300 px-2 py-1 rounded-full">
                                        {{ course.modality.description }}
                                    </span>
                                </div>

                                <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1 mb-4">
                                    <div class="flex items-center gap-1">
                                        <font-awesome-icon :icon="faBook" class="text-gray-400" />
                                        {{ course.sector_description || 'N/A' }}
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <font-awesome-icon :icon="faFile" class="text-gray-400" />
                                        {{ course.type_description || 'N/A' }}
                                    </div>
                                </div>

                                <!-- Indicador de examen configurado -->
                                <div v-if="course.exam" class="mb-3">
                                    <Link :href="route('aca_course_exam_view_details', [course.id, course.exam.id])"
                                        class="inline-flex items-center gap-1 text-xs bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300 px-2 py-1 rounded-full hover:bg-green-200 dark:hover:bg-green-800 transition-colors cursor-pointer">
                                        <font-awesome-icon :icon="faClipboardCheck" class="w-3 h-3" />
                                        Ver preguntas del examen
                                    </Link>
                                </div>

                                <!-- Spacer to push action bar to bottom -->
                                <div class="flex-grow"></div>

                                <!-- Action Bar mejorada -->
                            </div>
                            <div>
                                <div class="mt-auto border-t border-gray-200 dark:border-gray-700">
                                    <div class="flex items-top justify-between gap-3 bg-gray-50 dark:bg-gray-700/50 rounded-b-2xl p-3">

                                        <!-- Botones de acciones -->
                                        <div class="flex gap-2 flex-wrap">
                                            <!-- Convenios -->
                                            <button @click="openModalAgreements(course)"
                                                class="flex flex-col items-center gap-1 px-3 py-2 min-w-[70px] text-orange-600 bg-orange-100 dark:bg-orange-900/30 dark:text-orange-400 hover:bg-orange-200 dark:hover:bg-orange-900/50 rounded-lg transition-all"
                                                v-tippy="{content: 'Convenios', placement: 'top'}"
                                            >
                                                <font-awesome-icon :icon="faHandshake" class="text-lg" />
                                                <span class="text-[10px] font-medium">Convenios</span>
                                            </button>

                                            <!-- Información -->
                                            <Link :href="route('aca_courses_information', course.id)"
                                                class="flex flex-col items-center gap-1 px-3 py-2 min-w-[70px] text-purple-600 bg-purple-100 dark:bg-purple-900/30 dark:text-purple-400 hover:bg-purple-200 dark:hover:bg-purple-900/50 rounded-lg transition-all"
                                                v-tippy="{content: 'Información', placement: 'top'}"
                                            >
                                                <font-awesome-icon :icon="faEye" class="text-lg" />
                                                <span class="text-[10px] font-medium">Info</span>
                                            </Link>

                                            <!-- Módulos -->
                                            <Link :href="route('aca_courses_module_panel', course.id)"
                                                class="flex flex-col items-center gap-1 px-3 py-2 min-w-[70px] text-indigo-600 bg-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-400 hover:bg-indigo-200 dark:hover:bg-indigo-900/50 rounded-lg transition-all"
                                                v-tippy="{content: 'Módulos', placement: 'top'}"
                                            >
                                                <font-awesome-icon :icon="faLayerGroup" class="text-lg" />
                                                <span class="text-[10px] font-medium">Módulos</span>
                                            </Link>

                                            <!-- Examen (Editar/Crear) -->
                                            <button @click="openCourseExamModal(course)"
                                                class="flex flex-col items-center gap-1 px-3 py-2 min-w-[70px] rounded-lg transition-all"
                                                :class="course.exam ? 'text-green-600 bg-green-100 dark:bg-green-900/30 dark:text-green-400 hover:bg-green-200 dark:hover:bg-green-900/50' : 'text-gray-500 bg-gray-200 dark:bg-gray-600/30 dark:text-gray-400 hover:bg-gray-300 dark:hover:bg-gray-600/50'"
                                                v-tippy="{content: course.exam ? 'Editar Examen Final' : 'Crear Examen Final', placement: 'top'}"
                                                v-can="course.exam ? 'aca_cursos_examen_final_editar' : 'aca_cursos_examen_final_crear'"
                                            >
                                                <font-awesome-icon :icon="faClipboardCheck" class="text-lg" />
                                                <span class="text-[10px] font-medium">{{ course.exam ? 'Editar' : 'Crear' }}</span>
                                            </button>
                                            <!-- Configuracion de Landing -->
                                            <Link :href="route('aca_courses_landing_edit', course.id)"
                                                class="flex flex-col items-center gap-1 px-3 py-2 min-w-[70px] text-cyan-600 bg-cyan-100 dark:bg-cyan-900/30 dark:text-cyan-400 hover:bg-cyan-200 dark:hover:bg-cyan-900/50 rounded-lg transition-all"
                                                v-tippy="{content: 'Landing', placement: 'top'}"
                                            >
                                                <font-awesome-icon :icon="faLandmark" class="text-lg" />
                                                <span class="text-[10px] font-medium">Landing</span>
                                            </Link>
                                        </div>

                                        <div>
                                            <!-- Botón Eliminar -->
                                            <button @click="destroyCourse(course.id)"
                                                class="flex flex-col items-center gap-1 px-3 py-2 min-w-[60px] text-red-600 bg-red-100 dark:bg-red-900/30 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-900/50 rounded-lg transition-all"
                                                v-tippy="{content: 'Eliminar', placement: 'top'}"
                                            >
                                                <font-awesome-icon :icon="faTrashAlt" class="text-lg" />
                                                <span class="text-[10px] font-medium">Eliminar</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- No Results Message -->
            <div v-if="courses.length === 0" class="text-center py-12">
                <div class="text-gray-400 dark:text-gray-500 mb-4">
                    <font-awesome-icon :icon="faBook" class="text-6xl" />
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No se encontraron cursos</h3>
                <p class="text-gray-600 dark:text-gray-400">Intenta ajustar los filtros o buscar con otros términos.</p>
            </div>

            <!-- Pagination -->
            <div class="mt-8">
                <Pagination :data="courses" />
            </div>
        </div>


        <ModalLarge
            :onClose = "closeModalAgreements"
            :show="displayModalAgreements"
            :icon="'/img/convenio.png'"
        >
            <template #title>
                Convenio
            </template>
            <template #message>{{ formAgreement.course_name }}</template>
            <template #content>
                <div class="grid grid-cols-6 gap-6">
                    <div class="col-span-6 sm:col-span-2">
                        <label for="institution" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">institución</label>
                        <select v-model="formAgreement.institution_id" id="institution" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                            <option v-for="(institution) in institutions" :value="institution.id">{{ institution.name }}</option>
                        </select>
                        <InputError :message="formAgreement.errors.institution_id" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-2">
                        <label for="star_date" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Fecha Incio</label>
                        <input v-model="formAgreement.start_date" type="date" id="star_date" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="John" required>
                        <InputError :message="formAgreement.errors.start_date" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-2">
                        <label for="end_date" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Fecha Termino</label>
                        <input v-model="formAgreement.end_date" type="date" id="end_date" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="John" required>
                        <InputError :message="formAgreement.errors.end_date" class="mt-2" />
                    </div>
                </div>

                <div class="mt-4">
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <div class="border p-4" v-for="(xagreement) in arrayAgreements">
                            <div class="flex justify-end">
                                <button @click="destroyAgreement(xagreement.id)" type="button" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-full text-sm p-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                    <font-awesome-icon :icon="faTrashAlt" />
                                </button>
                            </div>
                            <img class="h-auto max-w-full rounded-lg" :src="xagreement.institution.image" :alt="xagreement.institution.name">
                        </div>
                    </div>
                </div>
            </template>
            <template #buttons>
                <progress v-if="formAgreement.progress" :value="formAgreement.progress.percentage" max="100" class="mr-2">
                    {{ formAgreement.progress.percentage }}%
                </progress>
                <DangerButton
                    :class="{ 'opacity-25': formAgreement.processing }" :disabled="formAgreement.processing"
                    class="mr-3"
                    @click="saveAgreements()"
                >
                Guardar
            </DangerButton>
            </template>
        </ModalLarge>

        <!-- Modal Examen Final del Curso -->
        <ModalLarge
            :onClose="closeCourseExamModal"
            :show="displayCourseExamModal"
            :icon="'/img/examen.png'"
        >
            <template #title>
                {{ courseExamForm.id ? 'Editar Examen' : 'Crear Examen' }} Final del Curso
            </template>
            <template #message>{{ courseExamForm.course_description }}</template>
            <template #content>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Descripción -->
                        <div class="col-span-2">
                            <label for="description" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Descripción del examen
                            </label>
                            <input v-model="courseExamForm.description" type="text" id="description"
                                class="form-input"
                                placeholder="Examen Final" required>
                            <InputError :message="courseExamForm.errors.description" class="mt-2" />
                        </div>

                        <!-- Duración con Selects -->
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Duración Máxima
                            </label>
                            <div class="space-y-3">
                                <!-- Selector de Horas y Minutos -->
                                <div class="flex items-center gap-2">
                                    <div class="flex-1">
                                        <label class="text-xs text-gray-600 dark:text-gray-400 font-medium mb-1 block">Horas</label>
                                        <select
                                            v-model.number="durationHours"
                                            @change="updateCourseExamDuration"
                                            class="form-select"
                                        >
                                            <option value="0">0 horas</option>
                                            <option v-for="h in 5" :key="h" :value="h">{{ h }} hora{{ h > 1 ? 's' : '' }}</option>
                                        </select>
                                    </div>
                                    <div class="flex-1">
                                        <label class="text-xs text-gray-600 dark:text-gray-400 font-medium mb-1 block">Minutos</label>
                                        <select
                                            v-model.number="durationMinutes"
                                            @change="updateCourseExamDuration"
                                            class="form-select"
                                        >
                                            <option value="0">0 min</option>
                                            <option v-for="m in 59" :key="m" :value="m">{{ m }} min</option>
                                        </select>
                                    </div>
                                </div>
                                <!-- Visualización del Total -->
                                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium text-blue-900 dark:text-blue-100">Tiempo total:</span>
                                        <span class="text-sm font-bold text-blue-700 dark:text-blue-300">
                                            {{ formatDuration(courseExamForm.duration_minutes) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <InputError :message="courseExamForm.errors.duration_minutes" class="mt-2" />
                        </div>
                        <!-- Archivo PDF del Examen Resuelto -->
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Examen Resuelto (PDF)
                            </label>
                            <div class="text-xs text-gray-600 dark:text-gray-400 font-medium mb-1 block">
                                Solo se permiten archivos PDF. Máximo 10MB.
                            </div>
                            <div class="space-y-3">
                                <!-- Input de Archivo -->
                                <div class="relative">
                                    <input
                                        type="file"
                                        @input="courseExamForm.answer_key_pdf = $event.target.files[0]"
                                        accept=".pdf"
                                        class="w-full text-sm text-gray-500 dark:text-gray-400
                                            file:mr-4 file:py-2 file:px-4
                                            file:rounded-lg file:border-0
                                            file:text-sm file:font-medium
                                            file:bg-blue-50 file:text-blue-700
                                            hover:file:bg-blue-100
                                            file:cursor-pointer file:transition-colors
                                            dark:file:bg-blue-900/50 dark:file:text-blue-300
                                            dark:hover:file:bg-blue-800/70
                                            border border-gray-200 dark:border-gray-600
                                            rounded-lg cursor-pointer
                                            hover:border-blue-300 dark:hover:border-blue-500"
                                    >
                                </div>
                                <!-- Información del Archivo Actual/Seleccionado -->
                                <div v-if="courseExamForm.answer_key_pdf || (courseExamForm.id && courseExamForm.file_resolved_name)"
                                     class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg p-3">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-5L9 2H4z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="text-sm text-gray-700 dark:text-gray-300 font-medium truncate">
                                            {{ typeof courseExamForm.answer_key_pdf === 'string' ? courseExamForm.answer_key_pdf.split('/').pop() :
                                                courseExamForm.answer_key_pdf?.name || (courseExamForm.file_resolved_name ?? 'Archivo PDF')
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Fecha inicio -->
                        <div>
                            <label for="date_start" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Fecha de Inicio
                            </label>
                            <input v-model="courseExamForm.date_start" type="datetime-local" id="date_start"
                                class="form-input"
                                required>
                            <InputError :message="courseExamForm.errors.date_start" class="mt-2" />
                        </div>

                        <!-- Fecha fin -->
                        <div>
                            <label for="date_end" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Fecha de Fin
                            </label>
                            <input v-model="courseExamForm.date_end" type="datetime-local" id="date_end"
                                class="form-input"
                                required>
                            <InputError :message="courseExamForm.errors.date_end" class="mt-2" />
                        </div>

                        <!-- Intentos -->
                        <div>
                            <label for="attempts" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Número de Intentos
                            </label>
                            <input v-model.number="courseExamForm.attempts" type="number" id="attempts" min="1" max="10"
                                class="form-input"
                                placeholder="1" required>
                            <InputError :message="courseExamForm.errors.attempts" class="mt-2" />
                        </div>

                        <!-- Estado -->
                        <div>
                            <label for="status" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Estado
                            </label>
                            <select v-model="courseExamForm.status" id="status"
                                class="form-select">
                                <option :value="1">Activo</option>
                                <option :value="0">Inactivo</option>
                            </select>
                            <InputError :message="courseExamForm.errors.status" class="mt-2" />
                        </div>
                    </div>
            </template>
            <template #buttons>
                <button
                    @click="saveCourseExam" type="button"
                    :disabled="courseExamForm.processing"
                    class="btn btn-primary uppercase text-xs">
                    <span v-if="courseExamForm.processing">
                        <IconLoader class="w-4 h-4 mr-2" />
                        Guardando...
                    </span>
                    <span v-else>{{ courseExamForm.id ? 'Actualizar' : 'Crear' }} Examen</span>
                </button>
            </template>
        </ModalLarge>
    </AppLayout>
</template>
