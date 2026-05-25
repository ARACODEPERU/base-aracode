<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import Pagination from '@/Components/Pagination.vue';
import IconSearch from '@/Components/vristo/icon/icon-search.vue';
import IconCalendar from '@/Components/vristo/icon/icon-calendar.vue';
import IconFile from '@/Components/vristo/icon/icon-file.vue';

const props = defineProps({
    patients: {
        type: Object,
        default: () => ({}),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const form = useForm({
    search: props.filters.search || '',
});

const searchPatients = () => {
    form.get(route('heal_clinical_records_list'), {
        preserveState: true,
        replace: true,
    });
};

const calculateAge = (birthdate) => {
    if (!birthdate) {
        return 'Sin fecha';
    }

    const today = new Date();
    const born = new Date(birthdate);
    let age = today.getFullYear() - born.getFullYear();
    const month = today.getMonth() - born.getMonth();

    if (month < 0 || (month === 0 && today.getDate() < born.getDate())) {
        age -= 1;
    }

    return `${age} años`;
};

const genderLabel = (gender) => ({
    M: 'Masculino',
    F: 'Femenino',
}[gender] || 'No registrado');

const formatDate = (value) => {
    if (!value) {
        return 'Sin atenciones';
    }

    return new Intl.DateTimeFormat('es-PE', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};
</script>

<template>
    <AppLayout title="Historias Clínicas">
        <Navigation :routeModule="route('health_dashboard')" :titleModule="'Salud'">
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>Historias Clínicas</span>
            </li>
        </Navigation>

        <div class="pt-5">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl">Historias Clínicas</h2>
                    <p class="text-sm text-white-dark">Consulta solo lectura de pacientes y sus atenciones.</p>
                </div>
                <form class="relative w-full sm:w-80" @submit.prevent="searchPatients">
                    <input
                        v-model="form.search"
                        type="text"
                        class="form-input py-2 pr-11"
                        placeholder="Buscar paciente o DNI"
                    />
                    <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-white-dark hover:text-primary">
                        <IconSearch class="h-4 w-4" />
                    </button>
                </form>
            </div>

            <div class="panel p-0">
                <Pagination :data="patients">
                    <div class="table-responsive">
                        <table class="table-hover">
                            <thead>
                                <tr>
                                    <th>Paciente</th>
                                    <th>DNI</th>
                                    <th>Edad</th>
                                    <th>Género</th>
                                    <th>Última atención</th>
                                    <th class="!text-center">Atenciones</th>
                                    <th class="!text-center">Ver</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="patient in patients.data" :key="patient.id">
                                    <td>
                                        <div class="font-semibold">{{ patient.full_name }}</div>
                                        <div class="text-xs text-white-dark">{{ patient.patient_code || 'Sin código' }}</div>
                                    </td>
                                    <td>{{ patient.number || 'Sin DNI' }}</td>
                                    <td>{{ calculateAge(patient.birthdate) }}</td>
                                    <td>{{ genderLabel(patient.gender) }}</td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <IconCalendar class="h-4 w-4 text-primary" />
                                            <span>{{ formatDate(patient.last_attention_at) }}</span>
                                        </div>
                                    </td>
                                    <td class="text-center">{{ patient.attentions_count }}</td>
                                    <td class="text-center">
                                        <Link
                                            :href="route('heal_clinical_records_show', patient.id)"
                                            class="btn btn-sm btn-outline-primary"
                                        >
                                            <IconFile class="h-4 w-4 ltr:mr-1 rtl:ml-1" />
                                            Ver historia
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="!patients.data?.length">
                                    <td colspan="7" class="py-8 text-center text-white-dark">
                                        No se encontraron pacientes.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Pagination>
            </div>
        </div>
    </AppLayout>
</template>
