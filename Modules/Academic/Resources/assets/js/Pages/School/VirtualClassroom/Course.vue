<template>
    <AppLayout :menus="menus">
        <Head :title="course.name" />
        <Navigation :route-link="route('aca_virtual_classroom_index')" :title="'Aula Virtual'" />

        <div class="container-fluid py-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h4 mb-1">{{ course.name }}</h1>
                    <p class="text-muted mb-0">
                        <span v-if="course.level_name">{{ course.level_name }}</span>
                        <span v-if="course.grade_name"> · {{ course.grade_name }}</span>
                        <span v-if="course.section_label"> · Sección {{ course.section_label }}</span>
                        <span v-if="course.area_name"> · {{ course.area_name }}</span>
                        <span v-if="course.year"> · Año {{ course.year }}</span>
                        <span v-if="course.teacher_name"> · Docente: {{ course.teacher_name }}</span>
                    </p>
                </div>
                <Link :href="route('aca_virtual_classroom_index', { year_id: course.year ? undefined : undefined })" class="btn btn-outline-secondary">
                    Volver al listado
                </Link>
            </div>

            <div v-if="!yearIsActive" class="alert alert-info">
                <strong>Modo historial:</strong> Este curso pertenece a un año pasado. Solo está disponible para consulta.
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Unidades Didácticas</h5>
                    <button v-if="canManage" type="button" class="btn btn-primary btn-sm" disabled>
                        Agregar unidad
                    </button>
                </div>
                <div class="card-body">
                    <div v-if="units.length === 0" class="alert alert-light border mb-0">
                        Aún no se han registrado unidades didácticas para este curso.
                    </div>

                    <div v-else class="accordion" id="unitsAccordion">
                        <div v-for="unit in units" :key="unit.id" class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" :data-bs-target="'#unit_' + unit.id">
                                    {{ unit.title }}
                                </button>
                            </h2>
                            <div :id="'unit_' + unit.id" class="accordion-collapse collapse" data-bs-parent="#unitsAccordion">
                                <div class="accordion-body">
                                    <p v-if="unit.purpose" class="mb-2"><strong>Propósito:</strong> {{ unit.purpose }}</p>
                                    <p class="text-muted small mb-0">Sesiones: {{ unit.lessons.length }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue'
import Navigation from '@/Components/vristo/layout/Navigation.vue'
import { Head, Link } from '@inertiajs/vue3'

const props = defineProps({
    course: { type: Object, required: true },
    units: { type: Array, default: () => [] },
    yearIsActive: { type: Boolean, default: false },
    canManage: { type: Boolean, default: false },
})
</script>
