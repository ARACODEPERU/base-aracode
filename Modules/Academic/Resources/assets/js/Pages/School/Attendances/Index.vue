<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { Link } from '@inertiajs/vue3';
import { faChalkboardUser, faUserGraduate, faArrowRight, faCalendarCheck } from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
    sections: { type: Array, default: () => [] },
    teacherName: { type: String, default: '' },
    schoolName: { type: String, default: '' },
    months: { type: Array, default: () => [] },
});

const shiftLabels = { manana: 'Mañana', tarde: 'Tarde', noche: 'Noche' };
</script>

<template>
    <AppLayout title="Registro de Asistencias">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[{ title: 'Colegio' }, { title: 'Registro de Asistencias' }]" />

        <div class="panel mt-5">
            <div class="p-5 border-b border-[#ebedf2] dark:border-[#191e3a]">
                <h3 class="text-lg font-semibold">Registro de asistencia de la I.E.</h3>
                <p class="text-sm text-white-dark mt-1">
                    {{ teacherName }}<span v-if="schoolName"> · {{ schoolName }}</span>
                </p>
            </div>

            <div class="p-5">
                <div v-if="sections.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="section in sections" :key="section.id" class="border rounded-md p-4 border-[#ebedf2] dark:border-[#1b2e4b]">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <h4 class="font-semibold">{{ section.grade }} · {{ section.name }}</h4>
                                <p class="text-xs text-white-dark mt-1">
                                    {{ section.level }} · Turno {{ shiftLabels[section.shift] ?? section.shift ?? 'N/D' }}
                                </p>
                            </div>
                            <span class="badge" :class="section.is_tutor ? 'bg-primary' : 'bg-secondary'">
                                {{ section.is_tutor ? 'Tutor' : 'Auxiliar' }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between mt-4">
                            <span class="text-sm flex items-center gap-2">
                                <font-awesome-icon :icon="faUserGraduate" class="text-white-dark" />
                                {{ section.students }} alumno(s)
                            </span>
                            <span class="badge bg-info">
                                {{ months.length ? months.find(m => m.current)?.label ?? 'Marzo — Diciembre' : '' }}
                            </span>
                        </div>

                        <Link
                            :href="route('aca_school_attendance_show', section.id)"
                            class="btn btn-primary w-full mt-4"
                        >
                            <font-awesome-icon :icon="faCalendarCheck" class="mr-1" />
                            Registrar asistencia
                            <font-awesome-icon :icon="faArrowRight" class="ml-2" />
                        </Link>
                    </div>
                </div>

                <div v-else class="text-center py-10">
                    <font-awesome-icon :icon="faChalkboardUser" class="text-4xl text-white-dark" />
                    <p class="text-sm text-white-dark mt-3">
                        No tienes secciones asignadas como tutor o auxiliar. Solicita al administrador tu asignación en la estructura académica.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
