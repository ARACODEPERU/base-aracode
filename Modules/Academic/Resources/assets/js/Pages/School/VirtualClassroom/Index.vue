<template>
    <AppLayout :menus="menus">
        <Head title="Aula Virtual" />
        <Navigation :route-link="route('module')" :title="$t('academic')" />

        <div class="container-fluid py-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <h1 class="h4 mb-0">Aula Virtual</h1>
            </div>

            <div v-if="!school" class="alert alert-warning">
                No hay un colegio configurado. Registre uno primero en "Colegios".
            </div>

            <template v-else>
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label">Año escolar</label>
                                <select class="form-select" v-model="selectedYear" @change="changeYear">
                                    <option v-for="y in years" :key="y.id" :value="y.id">
                                        {{ y.year }} {{ y.status === 'active' ? '(activo)' : '' }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-9">
                                <div v-if="!yearIsActive && yearId" class="alert alert-info mb-0">
                                    <strong>Modo historial:</strong> Solo puede visualizar el contenido del año {{ currentYearLabel }}.
                                    Las acciones de creación/edición se encuentran deshabilitadas.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="courses.length === 0" class="alert alert-light border">
                    No hay cursos asignados para este año escolar.
                </div>

                <div class="row g-3">
                    <div v-for="course in courses" :key="course.id" class="col-md-6 col-lg-4">
                        <div class="card h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">{{ course.name }}</h5>
                                <ul class="list-unstyled small text-muted mb-3">
                                    <li v-if="course.level_name"><strong>Nivel:</strong> {{ course.level_name }}</li>
                                    <li v-if="course.grade_name"><strong>Grado:</strong> {{ course.grade_name }}</li>
                                    <li v-if="course.section_name"><strong>Sección:</strong> {{ course.section_name }}</li>
                                    <li v-if="course.area_name"><strong>Área:</strong> {{ course.area_name }}</li>
                                    <li v-if="course.teacher_name"><strong>Docente:</strong> {{ course.teacher_name }}</li>
                                    <li><strong>Unidades:</strong> {{ course.units_count }}</li>
                                </ul>

                                <div class="mt-auto d-flex gap-2 flex-wrap">
                                    <Link
                                        :href="route('aca_virtual_classroom_show', course.id)"
                                        class="btn btn-primary btn-sm"
                                    >
                                        Ver curso
                                    </Link>
                                    <button
                                        v-if="yearIsActive && canManage && course.can_copy"
                                        type="button"
                                        class="btn btn-outline-secondary btn-sm"
                                        disabled
                                    >
                                        Copiar desde año anterior
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue'
import Navigation from '@/Components/vristo/layout/Navigation.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const props = defineProps({
    school: { type: Object, default: null },
    years: { type: Array, default: () => [] },
    yearId: { type: Number, default: null },
    activeYearId: { type: Number, default: null },
    yearIsActive: { type: Boolean, default: false },
    courses: { type: Array, default: () => [] },
    canManage: { type: Boolean, default: false },
})

const selectedYear = ref(props.yearId)

const currentYearLabel = computed(() => {
    const y = props.years.find((item) => item.id === props.yearId)
    return y ? y.year : ''
})

const changeYear = () => {
    router.get(
        route('aca_virtual_classroom_index'),
        { year_id: selectedYear.value },
        { preserveScroll: true, preserveState: false }
    )
}
</script>
