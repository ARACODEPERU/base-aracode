<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { computed } from 'vue';
import { faClock, faChalkboardUser, faLocationDot } from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
    school: { type: Object, default: null },
    teacherName: { type: String, default: null },
    year: { type: [Number, String], default: null },
    blocks: { type: Array, default: () => [] },
    days: { type: Array, default: () => [] },
});

/* Solo se muestran los días que tienen clase, para que el docente vea su
 * semana real y no una grilla llena de días vacíos. */
const daysWithBlocks = computed(() => {
    const withBlocks = new Set(props.blocks.map((block) => block.weekday));

    return props.days.filter((day) => withBlocks.has(day.value));
});

const blocksByDay = computed(() => {
    const map = {};

    daysWithBlocks.value.forEach((day) => {
        map[day.value] = props.blocks
            .filter((block) => block.weekday === day.value)
            .sort((a, b) => (a.start_time > b.start_time ? 1 : -1));
    });

    return map;
});

const totalHours = computed(() => {
    let minutes = 0;

    props.blocks.forEach((block) => {
        const [sh, sm] = (block.start_time ?? '0:0').split(':').map(Number);
        const [eh, em] = (block.end_time ?? '0:0').split(':').map(Number);
        minutes += Math.max(0, (eh * 60 + em) - (sh * 60 + sm));
    });

    return (minutes / 60).toFixed(1);
});

const sectionCount = computed(() => new Set(props.blocks.map((block) => block.section_id)).size);
</script>

<template>
    <AppLayout title="Mi Horario">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                { route: route('aca_school_years_list'), title: 'Colegio' },
                { title: 'Mi Horario' },
            ]"
        />
        <div class="pt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl">
                    Mi Horario
                    <span v-if="teacherName" class="text-white-dark text-base">( {{ teacherName }} )</span>
                </h2>
                <div class="flex flex-wrap gap-2 text-sm">
                    <span v-if="year" class="badge bg-secondary">Año {{ year }}</span>
                    <span class="badge bg-info">{{ blocks.length }} bloques</span>
                    <span class="badge bg-warning">{{ totalHours }} horas</span>
                    <span class="badge bg-dark">{{ sectionCount }} secciones</span>
                </div>
            </div>

            <div v-if="!teacherName" class="mt-5 panel p-6 text-center text-white-dark">
                Su usuario no está vinculado a una persona del colegio. Pida al administrador que lo asocie a su ficha de docente.
            </div>

            <div v-else-if="blocks.length === 0" class="mt-5 panel p-6 text-center text-white-dark">
                Todavía no tiene bloques asignados en el horario del colegio.
            </div>

            <div v-else class="mt-5 space-y-4">
                <div v-for="day in daysWithBlocks" :key="day.value" class="panel p-0 overflow-hidden">
                    <div class="p-4 border-b border-white-light dark:border-dark flex items-center gap-3">
                        <h3 class="font-semibold">{{ day.label }}</h3>
                        <span class="text-xs text-white-dark">{{ blocksByDay[day.value].length }} bloques</span>
                    </div>
                    <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        <div
                            v-for="block in blocksByDay[day.value]"
                            :key="block.id"
                            class="border rounded-md p-3 border-white-light dark:border-dark"
                        >
                            <span class="badge bg-primary">
                                <font-awesome-icon :icon="faClock" class="w-3 h-3 mr-1" />
                                {{ block.start_time }} - {{ block.end_time }}
                            </span>
                            <p class="mt-2 font-semibold">{{ block.area_name }}</p>
                            <p class="text-xs text-white-dark">
                                <font-awesome-icon :icon="faChalkboardUser" class="w-3 h-3 mr-1" />
                                {{ block.level_name }} · {{ block.section_label }}
                            </p>
                            <p v-if="block.room" class="text-xs text-white-dark mt-1">
                                <font-awesome-icon :icon="faLocationDot" class="w-3 h-3 mr-1" />
                                {{ block.room }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
