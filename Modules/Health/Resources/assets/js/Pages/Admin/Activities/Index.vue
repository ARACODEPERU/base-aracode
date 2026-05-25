<script setup>
import { computed, ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import Pagination from '@/Components/Pagination.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import IconSearch from '@/Components/vristo/icon/icon-search.vue';
import IconEye from '@/Components/vristo/icon/icon-eye.vue';
import IconRestore from '@/Components/vristo/icon/icon-restore.vue';

import IconUser from '@/Components/vristo/icon/icon-user.vue';
import ActivityDetailModal from '@/Components/Health/ActivityDetailModal.vue';

const props = defineProps({
    activities: {
        type: Object,
        default: () => ({}),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    activityTypes: {
        type: Object,
        default: () => ({}),
    },
});

const selectedActivityId = ref(null);
const showDetailModal = ref(false);

const filters = useForm({
    search: props.filters.search || '',
    activity_type: props.filters.activity_type || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

const badgeClass = (type) => ({
    created: 'bg-success',
    updated: 'bg-info',
    deleted: 'bg-danger',
    signed: 'bg-primary',
    cancelled: 'bg-warning',
    restored: 'bg-secondary',
}[type] || 'bg-secondary');

const activityTypeLabel = (type) => props.activityTypes[type] || type;

const formatDateTime = (value) => {
    if (!value) return '-';
    return new Intl.DateTimeFormat('es-PE', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    }).format(new Date(value));
};

const search = () => {
    filters.get(route('heal_activities_list'), {
        preserveState: true,
        replace: true,
    });
};

const resetFilters = () => {
    filters.reset();
    filters.get(route('heal_activities_list'), {
        preserveState: true,
        replace: true,
    });
};

const openDetail = (activityId) => {
    selectedActivityId.value = activityId;
    showDetailModal.value = true;
};

const closeDetail = () => {
    showDetailModal.value = false;
    selectedActivityId.value = null;
};

const hasActiveFilters = computed(() => {
    return filters.search || filters.activity_type || filters.date_from || filters.date_to;
});
</script>

<template>
    <AppLayout title="Registro de Actividades">
        <Navigation :routeModule="route('health_dashboard')" :titleModule="'Salud'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Registro de Actividades</span>
            </li>
        </Navigation>

        <div class="pt-5 space-y-5">
            <!-- Header -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl">Registro de Actividades</h2>
                    <p class="text-sm text-white-dark">
                        Historial de todas las acciones realizadas en el módulo Salud.
                        <span class="text-warning">Solo lectura — no se puede modificar ni eliminar.</span>
                    </p>
                </div>
            </div>

            <!-- Filters -->
            <div class="panel p-4">
                <div class="flex flex-wrap items-end gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <InputLabel value="Buscar" />
                        <div class="relative">
                            <TextInput v-model="filters.search" type="text" class="form-input pr-10" placeholder="Buscar por persona, paciente o tipo..." @keyup.enter="search" />
                            <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-white-dark" @click="search">
                                <IconSearch class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                    <div class="w-44">
                        <InputLabel value="Tipo de Actividad" />
                        <select v-model="filters.activity_type" class="form-select" @change="search">
                            <option value="">Todos</option>
                            <option v-for="(label, type) in activityTypes" :key="type" :value="type">{{ label }}</option>
                        </select>
                    </div>
                    <div class="w-44">
                        <InputLabel value="Desde" />
                        <input v-model="filters.date_from" type="date" class="form-input" @change="search" />
                    </div>
                    <div class="w-44">
                        <InputLabel value="Hasta" />
                        <input v-model="filters.date_to" type="date" class="form-input" @change="search" />
                    </div>
                    <div>
                        <button v-if="hasActiveFilters" type="button" class="btn btn-outline-dark" @click="resetFilters">
                            Limpiar filtros
                        </button>
                    </div>
                </div>
            </div>

            <!-- Activities List -->
            <div class="panel p-0 overflow-hidden">
                <Pagination :data="activities">
                    <div class="p-5 pt-0 space-y-3">
                        <!-- Activity Card -->
                        <div
                            v-for="activity in activities.data"
                            :key="activity.id"
                            class="group flex flex-col gap-3 rounded-lg border border-slate-200 p-4 transition-all duration-150 hover:-translate-y-0.5 hover:scale-[1.005] hover:border-primary/40 hover:shadow-md dark:border-slate-700 dark:hover:border-primary/60"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <!-- Activity type badge -->
                                    <span class="badge whitespace-nowrap" :class="badgeClass(activity.activity_type)">
                                        {{ activityTypeLabel(activity.activity_type) }}
                                    </span>
                                    <!-- Actor -->
                                    <span class="flex items-center gap-1 text-sm">
                                        <IconUser class="h-3.5 w-3.5 text-primary" />
                                        <span class="font-semibold dark:text-white">{{ activity.actor_display || 'Sistema' }}</span>
                                    </span>
                                    <!-- Subject -->
                                    <span class="flex items-center gap-1 text-sm text-white-dark">
                                        <IconRestore class="h-3.5 w-3.5" />
                                        {{ activity.subject_label }} #{{ activity.subject_id }}
                                    </span>
                                    <!-- Patient -->
                                    <span v-if="activity.patient_display" class="text-sm text-white-dark">
                                        · {{ activity.patient_display }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="flex items-center gap-1 text-xs text-white-dark">
                                        <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                        {{ formatDateTime(activity.created_at) }}
                                    </span>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary p-1.5 opacity-0 transition-opacity group-hover:opacity-100"
                                        v-tippy="{ content: 'Ver detalle de la actividad', placement: 'left' }"
                                        @click="openDetail(activity.id)"
                                    >
                                        <IconEye class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>

                            <!-- Summary -->
                            <p class="text-sm text-white-dark line-clamp-2">{{ activity.summary }}</p>

                            <!-- Actor person id -->
                            <div v-if="activity.actor_person_id" class="text-xs text-white-dark">
                                Person ID: #{{ activity.actor_person_id }}
                            </div>

                            <!-- Metadata preview -->
                            <div v-if="activity.metadata?.changed_fields?.length" class="flex flex-wrap gap-1">
                                <span
                                    v-for="field in activity.metadata.changed_fields.slice(0, 5)"
                                    :key="field"
                                    class="rounded bg-info/10 px-2 py-0.5 text-xs text-info"
                                >
                                    {{ field }}
                                </span>
                                <span v-if="activity.metadata.changed_fields.length > 5" class="text-xs text-white-dark">
                                    +{{ activity.metadata.changed_fields.length - 5 }} más
                                </span>
                            </div>
                        </div>

                        <!-- Empty state -->
                        <div v-if="!activities.data?.length" class="flex flex-col items-center justify-center py-16 text-white-dark">
                            <svg class="mb-3 h-12 w-12 opacity-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <p class="text-lg">No se encontraron actividades</p>
                            <p class="text-sm">No hay registros de actividad que coincidan con los filtros aplicados.</p>
                        </div>
                    </div>
                </Pagination>
            </div>
        </div>

        <!-- Activity Detail Modal -->
        <ActivityDetailModal
            :activity-id="selectedActivityId"
            :show="showDetailModal"
            @close="closeDetail"
        />
    </AppLayout>
</template>
