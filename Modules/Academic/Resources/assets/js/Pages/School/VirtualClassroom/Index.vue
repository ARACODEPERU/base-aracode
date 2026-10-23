<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { Link, router } from '@inertiajs/vue3';
import Swal2 from 'sweetalert2';
import { computed, ref } from 'vue';
import { faCopy, faFolderOpen, faGraduationCap, faSearch, faWandMagicSparkles } from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
    school: { type: Object, default: null },
    years: { type: Array, default: () => [] },
    yearId: { type: Number, default: null },
    activeYearId: { type: Number, default: null },
    yearIsActive: { type: Boolean, default: false },
    courses: { type: Array, default: () => [] },
    canManage: { type: Boolean, default: false },
    canGenerate: { type: Boolean, default: false },
    canManageUnits: { type: Boolean, default: false },
    sectionsCount: { type: Number, default: 0 },
});

const selectedYear = ref(props.yearId);
const generating = ref(false);
const search = ref('');

const showToast = (title, icon = 'success') => {
    const toast = Swal2.mixin({
        toast: true,
        position: 'top',
        showConfirmButton: false,
        timer: 3000,
        customClass: { container: 'toast' },
    });
    toast.fire({ icon, title, padding: '10px 20px' });
};

const filteredCourses = computed(() => {
    const term = search.value.trim().toLowerCase();

    if (! term) {
        return props.courses;
    }

    return props.courses.filter((course) =>
        [course.name, course.level_name, course.grade_name, course.section_name, course.area_name, course.teacher_name]
            .filter(Boolean)
            .join(' ')
            .toLowerCase()
            .includes(term)
    );
});

const currentYearLabel = computed(() => {
    const year = props.years.find((item) => item.id === props.yearId);

    return year ? year.year : '';
});

const changeYear = () => {
    router.get(
        route('aca_virtual_classroom_index'),
        { year_id: selectedYear.value },
        { preserveScroll: true, preserveState: false }
    );
};

const generateCourses = () => {
    Swal2.fire({
        title: 'Generar cursos del año',
        text: 'Se crearán los cursos de cada sección con las áreas activas de su nivel. Los cursos que ya existen no se duplican.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, generar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        padding: '2em',
        customClass: 'sweet-alerts',
    }).then((result) => {
        if (! result.isConfirmed) {
            return;
        }

        generating.value = true;

        router.post(
            route('aca_virtual_classroom_generate'),
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    generating.value = false;
                },
                onSuccess: () => {
                    showToast('Cursos del año listos');
                },
            }
        );
    });
};

const copyPrevious = (course) => {
    Swal2.fire({
        title: '¿Copiar contenido del año anterior?',
        text: `Se copiarán unidades, sesiones, recursos y actividades a "${course.name}". No se copian entregas ni calificaciones.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, copiar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        padding: '2em',
        customClass: 'sweet-alerts',
    }).then((result) => {
        if (! result.isConfirmed) {
            return;
        }

        router.post(
            route('aca_virtual_classroom_copy_previous', course.id),
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    showToast('Contenido copiado correctamente');
                },
            }
        );
    });
};

const levelBadge = (level) => {
    const code = (level || '').toLowerCase();

    if (code.startsWith('inicial')) {
        return 'bg-info';
    }

    if (code.startsWith('primaria')) {
        return 'bg-warning';
    }

    return 'bg-secondary';
};
</script>

<template>
    <AppLayout title="Aula Virtual">
        <Navigation
            :routeModule="route('aca_dashboard')"
            :titleModule="'Académico'"
            :data="[
                { route: route('aca_school_years_list'), title: 'Colegio' },
                { title: 'Aula Virtual' },
            ]"
        />

        <div class="pt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h2 class="text-xl">Aula Virtual</h2>
                    <p class="text-white-dark text-sm mt-1">
                        Cursos, unidades, recursos y actividades del colegio
                        <span v-if="school">· {{ school.name }}</span>
                    </p>
                </div>
                <button
                    v-if="canGenerate && yearIsActive"
                    type="button"
                    class="btn btn-primary"
                    :disabled="generating"
                    @click="generateCourses"
                >
                    <font-awesome-icon :icon="faWandMagicSparkles" class="w-4 h-4 ltr:mr-2 rtl:ml-2" />
                    {{ generating ? 'Generando...' : 'Generar cursos' }}
                </button>
            </div>

            <div v-if="$page.props.errors?.generate" class="mt-4 rounded-md bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900 p-4 text-red-600 dark:text-red-300 text-sm">
                {{ $page.props.errors.generate }}
            </div>

            <div v-if="!school" class="mt-5 panel p-8 text-center">
                <div class="mx-auto w-14 h-14 rounded-full bg-primary/10 flex items-center justify-center">
                    <font-awesome-icon :icon="faGraduationCap" class="w-6 h-6 text-primary" />
                </div>
                <h3 class="text-lg font-semibold mt-4">No hay un colegio configurado</h3>
                <p class="text-white-dark text-sm mt-1">Registra el colegio y su estructura académica para empezar a usar el aula virtual.</p>
                <Link :href="route('aca_school_years_list')" class="btn btn-primary mt-4">Ir a Colegio</Link>
            </div>

            <template v-else>
                <div class="mt-5 panel">
                    <div class="p-5 flex flex-wrap items-end gap-4">
                        <div class="w-48">
                            <label class="form-label">Año escolar</label>
                            <select v-model="selectedYear" class="form-select" @change="changeYear">
                                <option v-for="year in years" :key="year.id" :value="year.id">
                                    {{ year.year }} {{ year.status === 'active' ? '(activo)' : '' }}
                                </option>
                            </select>
                        </div>
                        <div class="w-full sm:w-80">
                            <label class="form-label">Buscar curso</label>
                            <div class="relative">
                                <input
                                    v-model="search"
                                    type="text"
                                    class="form-input ltr:pr-9 rtl:pl-9"
                                    placeholder="Curso, área o docente"
                                />
                                <font-awesome-icon :icon="faSearch" class="w-4 h-4 absolute top-1/2 -translate-y-1/2 ltr:right-3 rtl:left-3 text-white-dark" />
                            </div>
                        </div>
                        <div class="ltr:ml-auto rtl:mr-auto text-sm text-white-dark">
                            {{ filteredCourses.length }} curso(s) en {{ currentYearLabel }}
                        </div>
                    </div>
                </div>

                <div v-if="!yearIsActive && yearId" class="mt-4 rounded-md bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-900 p-4 text-sm text-blue-700 dark:text-blue-300">
                    <strong>Modo historial:</strong> el año {{ currentYearLabel }} ya cerró. Solo puedes consultar su contenido; las acciones de creación y edición están deshabilitadas.
                </div>

                <div v-if="filteredCourses.length === 0" class="mt-5 panel p-10 text-center">
                    <div class="mx-auto w-14 h-14 rounded-full bg-primary/10 flex items-center justify-center">
                        <font-awesome-icon :icon="faGraduationCap" class="w-6 h-6 text-primary" />
                    </div>
                    <h3 class="text-lg font-semibold mt-4">
                        {{ courses.length === 0 ? 'Aún no hay cursos en este año' : 'Ningún curso coincide con la búsqueda' }}
                    </h3>
                    <p class="text-white-dark text-sm mt-1 max-w-lg mx-auto">
                        <template v-if="courses.length === 0">
                            Genera los cursos a partir de las secciones registradas y las áreas curriculares activas de cada nivel.
                            <template v-if="sectionsCount">Este año tiene {{ sectionsCount }} sección(es).</template>
                            <template v-else>Primero registra las secciones en la estructura académica.</template>
                        </template>
                        <template v-else>Prueba con otro nombre de curso, área o docente.</template>
                    </p>
                    <button
                        v-if="courses.length === 0 && canGenerate && yearIsActive"
                        type="button"
                        class="btn btn-primary mt-4"
                        :disabled="generating"
                        @click="generateCourses"
                    >
                        <font-awesome-icon :icon="faWandMagicSparkles" class="w-4 h-4 ltr:mr-2 rtl:ml-2" />
                        {{ generating ? 'Generando...' : 'Generar cursos del año' }}
                    </button>
                </div>

                <div v-else class="mt-5 panel p-0 border-0 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table-striped table-hover">
                            <thead>
                                <tr>
                                    <th class="!text-center">Acciones</th>
                                    <th>Curso</th>
                                    <th>Nivel</th>
                                    <th>Grado / Sección</th>
                                    <th>Docente</th>
                                    <th class="!text-center">Unidades</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="course in filteredCourses" :key="course.id">
                                    <td>
                                        <div class="flex gap-1 items-center justify-center">
                                            <Link
                                                :href="route('aca_virtual_classroom_show', course.id)"
                                                class="btn btn-sm btn-outline-primary"
                                                title="Abrir curso"
                                            >
                                                <font-awesome-icon :icon="faFolderOpen" />
                                            </Link>
                                            <button
                                                v-if="yearIsActive && course.can_copy && canManageUnits"
                                                type="button"
                                                class="btn btn-sm btn-outline-info"
                                                title="Copiar contenido del año anterior"
                                                @click="copyPrevious(course)"
                                            >
                                                <font-awesome-icon :icon="faCopy" />
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <Link :href="route('aca_virtual_classroom_show', course.id)" class="font-semibold hover:text-primary">
                                            {{ course.name }}
                                        </Link>
                                        <div v-if="course.area_name" class="text-xs text-white-dark mt-0.5">{{ course.area_name }}</div>
                                    </td>
                                    <td>
                                        <span v-if="course.level_name" class="badge" :class="levelBadge(course.level_name)">
                                            {{ course.level_name }}
                                        </span>
                                        <span v-else class="text-white-dark">—</span>
                                    </td>
                                    <td>
                                        <span v-if="course.grade_name || course.section_name">
                                            {{ course.grade_name }} {{ course.section_name ? '· ' + course.section_name : '' }}
                                        </span>
                                        <span v-else class="text-white-dark">—</span>
                                    </td>
                                    <td>
                                        <span v-if="course.teacher_name">{{ course.teacher_name }}</span>
                                        <span v-else class="badge bg-warning">Sin docente</span>
                                    </td>
                                    <td class="!text-center">
                                        <span class="badge" :class="course.units_count > 0 ? 'bg-success' : 'bg-secondary'">
                                            {{ course.units_count }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>
        </div>
    </AppLayout>
</template>
