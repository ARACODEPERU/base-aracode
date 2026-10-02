<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { useForm, router } from '@inertiajs/vue3';
import Swal2 from 'sweetalert2';
import { faRocket, faLock } from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
    years: { type: Object, default: () => ({}) },
    school: { type: Object, default: null },
    filters: { type: Object, default: () => ({}) },
});

const form = useForm({
    year: new Date().getFullYear(),
    observations: null,
});

const statusLabels = {
    announced: { text: 'Anunciado', badge: 'bg-secondary' },
    active: { text: 'Activo', badge: 'bg-success' },
    finished: { text: 'Cerrado', badge: 'bg-dark' },
};

const showMessage = (msg = '', type = 'success') => {
    const toast = Swal2.mixin({
        toast: true,
        position: 'top',
        showConfirmButton: false,
        timer: 3000,
        customClass: { container: 'toast' },
    });
    toast.fire({ icon: type, title: msg, padding: '10px 20px' });
};

const createYear = () => {
    form.post(route('aca_school_years_store'), {
        preserveScroll: true,
        onSuccess: () => {
            Swal2.fire({
                title: 'Enhorabuena',
                text: 'Año escolar registrado correctamente',
                icon: 'success',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
            form.reset();
        },
    });
};

const activateYear = (id) => {
    Swal2.fire({
        title: '¿Activar año escolar?',
        text: 'El año quedará activo para registrar matrículas. Los demás años activos del colegio se cerrarán.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, activar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        padding: '2em',
        customClass: 'sweet-alerts',
    }).then((result) => {
        if (result.isConfirmed) {
            router.put(route('aca_school_years_activate', id), {}, {
                preserveScroll: true,
                onSuccess: () => showMessage('Año escolar activado'),
            });
        }
    });
};

const closeYear = (id) => {
    Swal2.fire({
        title: '¿Cerrar año escolar?',
        text: 'El año pasará a estado Cerrado y ya no aceptará matrículas.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, cerrar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        padding: '2em',
        customClass: 'sweet-alerts',
    }).then((result) => {
        if (result.isConfirmed) {
            router.put(route('aca_school_years_close', id), {}, {
                preserveScroll: true,
                onSuccess: () => showMessage('Año escolar cerrado'),
            });
        }
    });
};
</script>

<template>
    <AppLayout title="Años Escolares">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                { route: route('aca_school_years_list'), title: 'Colegio' },
                { title: 'Años Escolares' },
            ]"
        />
        <div class="pt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl">Años Escolares <span class="text-white-dark text-base">( {{ school?.name }} )</span></h2>
            </div>

            <div class="mt-5 panel">
                <div class="p-5">
                    <h3 class="text-lg font-semibold mb-4">Nuevo año escolar</h3>
                    <form @submit.prevent="createYear" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                        <div>
                            <label class="form-label">Año *</label>
                            <input v-model="form.year" type="number" min="2000" max="2100" class="form-input" placeholder="2026" />
                            <template v-if="form.errors.year">
                                <p class="text-danger text-xs mt-1">{{ form.errors.year }}</p>
                            </template>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="form-label">Observaciones</label>
                            <input v-model="form.observations" type="text" class="form-input" placeholder="Opcional" />
                        </div>
                        <div>
                            <button type="submit" class="btn btn-primary w-full" :disabled="form.processing">Registrar</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="mt-5 panel p-0 border-0 overflow-hidden">
                <div class="table-responsive">
                    <table class="table-striped table-hover">
                        <thead>
                            <tr>
                                <th class="!text-center">Acciones</th>
                                <th>Año</th>
                                <th>Estado</th>
                                <th>Matrículas activas</th>
                                <th>Observaciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!years.data || years.data.length === 0">
                                <td colspan="5" class="text-center text-white-dark py-6">
                                    Aún no hay años escolares. Registre el primero y actívelo para empezar a matricular.
                                </td>
                            </tr>
                            <tr v-for="item in years.data" :key="item.id">
                                <td>
                                    <div class="flex gap-1 items-center justify-center">
                                        <button
                                            v-if="item.status !== 'active' && item.status !== 'finished'"
                                            v-tippy:bottom type="button"
                                            class="btn btn-sm btn-outline-success"
                                            @click="activateYear(item.id)"
                                        >
                                            <font-awesome-icon :icon="faRocket" />
                                        </button>
                                        <tippy target="bottom" placement="bottom">Activar</tippy>
                                        <button
                                            v-if="item.status === 'active'"
                                            v-tippy:bottom type="button"
                                            class="btn btn-sm btn-outline-dark"
                                            @click="closeYear(item.id)"
                                        >
                                            <font-awesome-icon :icon="faLock" />
                                        </button>
                                        <tippy target="bottom" placement="bottom">Cerrar año</tippy>
                                    </div>
                                </td>
                                <td class="font-semibold">{{ item.year }}</td>
                                <td class="text-center">
                                    <span class="badge" :class="statusLabels[item.status]?.badge">
                                        {{ statusLabels[item.status]?.text ?? item.status }}
                                    </span>
                                </td>
                                <td class="text-center">{{ item.enrollments_count ?? 0 }}</td>
                                <td>{{ item.observations }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
