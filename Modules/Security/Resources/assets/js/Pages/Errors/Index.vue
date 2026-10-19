<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import IconLoader from '@/Components/vristo/icon/icon-loader.vue';
    import Swal2 from 'sweetalert2';
    import { faBug, faEraser, faEye, faFilter, faTrash } from '@fortawesome/free-solid-svg-icons';
    import axios from 'axios';
    import { onMounted, ref } from 'vue';

    const props = defineProps({
        stats: {
            type: Object,
            default: () => ({ total: 0, size: '0 B', size_bytes: 0, oldest_at: null, newest_at: null, levels: {} }),
        },
        settings: {
            type: Object,
            default: () => ({ store_context: true, retention_days: 30, purge_enabled: true }),
        },
        levels: { type: Array, default: () => [] },
        canDelete: { type: Boolean, default: false },
    });

    const toast = Swal2.mixin({
        toast: true,
        position: 'bottom-end',
        showConfirmButton: false,
        timer: 3500,
        padding: '2em',
        customClass: { container: 'toast' },
    });

    const filters = ref({ level: '', search: '', date_start: '', date_end: '' });
    const rows = ref([]);
    const page = ref(1);
    const lastPage = ref(1);
    const total = ref(0);
    const loading = ref(false);
    const purging = ref(false);
    const clearing = ref(false);
    const deletingId = ref(null);

    const errorText = (error, fallback = 'Ocurrió un error inesperado.') => {
        const data = error?.response?.data;
        if (!data) return fallback;
        if (data.errors) {
            const first = Object.values(data.errors)[0];
            return Array.isArray(first) ? first[0] : first;
        }
        return data.message || fallback;
    };

    const levelClass = (level) => {
        const value = String(level || '').toLowerCase();
        if (value === 'emergency' || value === 'alert' || value === 'critical') {
            return 'bg-danger/10 text-danger';
        }
        if (value === 'error') {
            return 'bg-warning/10 text-warning';
        }
        return 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300';
    };

    const load = async (targetPage = 1) => {
        loading.value = true;

        try {
            const { data } = await axios.get(route('security_errors_data'), {
                params: {
                    page: targetPage,
                    level: filters.value.level || null,
                    search: filters.value.search || null,
                    date_start: filters.value.date_start || null,
                    date_end: filters.value.date_end || null,
                },
            });

            rows.value = data.data || [];
            page.value = data.current_page || 1;
            lastPage.value = data.last_page || 1;
            total.value = data.total || 0;
        } catch (error) {
            Swal2.fire({
                title: 'Error',
                text: errorText(error, 'No se pudo cargar el listado de errores.'),
                icon: 'error',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        } finally {
            loading.value = false;
        }
    };

    const clearFilters = () => {
        filters.value = { level: '', search: '', date_start: '', date_end: '' };
        load(1);
    };

    /** Borra un error concreto (con su contexto). Solo administradores. */
    const removeOne = (row) => {
        Swal2.fire({
            title: '¿Eliminar este error?',
            text: `Se borrará ${row.reference} con toda su traza y contexto. Esta acción no se puede deshacer.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: '¡Sí, eliminar!',
            cancelButtonText: 'Cancelar',
            padding: '2em',
            customClass: 'sweet-alerts',
            allowOutsideClick: () => !Swal2.isLoading(),
        }).then(async (result) => {
            if (!result.isConfirmed) return;

            deletingId.value = row.id;

            try {
                const { data } = await axios.delete(route('security_errors_destroy', row.id));
                rows.value = rows.value.filter((item) => item.id !== row.id);
                toast.fire({ icon: 'success', title: data.message || 'Error eliminado' });
            } catch (error) {
                Swal2.fire({
                    title: 'Error',
                    text: errorText(error, 'No se pudo eliminar el error.'),
                    icon: 'error',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
            } finally {
                deletingId.value = null;
            }
        });
    };

    /** Borra solo los errores más viejos que la retención configurada. */
    const purgeOld = () => {
        const days = props.settings.retention_days;

        if (!days || days <= 0) {
            Swal2.fire({
                title: 'Retención desactivada',
                text: 'La retención está en 0 días. Indica los días en Seguridad → Alertas para poder purgar por antigüedad.',
                icon: 'info',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
            return;
        }

        Swal2.fire({
            title: 'Purgar errores vencidos',
            text: `Se eliminarán los errores con más de ${days} día(s) de antigüedad y todo su contexto.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '¡Sí, purgar!',
            cancelButtonText: 'Cancelar',
            padding: '2em',
            customClass: 'sweet-alerts',
            allowOutsideClick: () => !Swal2.isLoading(),
        }).then(async (result) => {
            if (!result.isConfirmed) return;

            purging.value = true;

            try {
                const { data } = await axios.post(route('security_errors_purge'), { days });
                toast.fire({ icon: 'success', title: data.message || 'Purga completada' });
                await load(1);
            } catch (error) {
                Swal2.fire({
                    title: 'Error',
                    text: errorText(error, 'No se pudo purgar el contexto de errores.'),
                    icon: 'error',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
            } finally {
                purging.value = false;
            }
        });
    };

    /** Vacía por completo el contexto de errores guardado. */
    const clearAll = () => {
        Swal2.fire({
            title: '¿Eliminar TODOS los errores?',
            text: 'Se borrará todo el contexto guardado (trazas, peticiones y datos del usuario). Los avisos futuros seguirán llegando.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: '¡Sí, eliminar todo!',
            cancelButtonText: 'Cancelar',
            padding: '2em',
            customClass: 'sweet-alerts',
            allowOutsideClick: () => !Swal2.isLoading(),
        }).then(async (result) => {
            if (!result.isConfirmed) return;

            clearing.value = true;

            try {
                const { data } = await axios.delete(route('security_errors_clear'));
                toast.fire({ icon: 'success', title: data.message || 'Contexto eliminado' });
                await load(1);
            } catch (error) {
                Swal2.fire({
                    title: 'Error',
                    text: errorText(error, 'No se pudo eliminar el contexto de errores.'),
                    icon: 'error',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
            } finally {
                clearing.value = false;
            }
        });
    };

    onMounted(() => load(1));
</script>

<template>
    <AppLayout title="Errores">
        <Navigation
            :routeModule="route('security_dashboard')"
            :titleModule="'Seguridad'"
            :data="[{ title: 'Errores' }]"
        />

        <div class="mt-5 space-y-5">
            <div class="panel p-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-800 dark:text-white">
                            <font-awesome-icon :icon="faBug" class="text-primary" />
                            Errores guardados
                        </h2>
                        <p class="mt-1 max-w-3xl text-sm text-gray-500 dark:text-gray-400">
                            Es el contexto completo al que apunta el enlace del aviso de Telegram: traza, petición,
                            usuario y navegador. Se conservan
                            <span class="font-medium">{{ settings.retention_days }} día(s)</span> y luego se borran
                            automáticamente.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            v-if="canDelete"
                            type="button"
                            class="btn btn-outline-primary"
                            :disabled="purging"
                            @click="purgeOld"
                        >
                            <IconLoader v-if="purging" class="mr-2 h-4 w-4" />
                            <font-awesome-icon v-else :icon="faEraser" class="mr-1" />
                            Purgar vencidos
                        </button>
                        <button
                            v-if="canDelete"
                            type="button"
                            class="btn btn-outline-danger"
                            :disabled="clearing"
                            @click="clearAll"
                        >
                            <IconLoader v-if="clearing" class="mr-2 h-4 w-4" />
                            <font-awesome-icon v-else :icon="faTrash" class="mr-1" />
                            Eliminar todos
                        </button>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800/60">
                        <span class="block text-xs uppercase text-gray-500 dark:text-gray-400">Errores guardados</span>
                        <span class="mt-1 block text-xl font-semibold text-gray-800 dark:text-white">{{ stats.total }}</span>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800/60">
                        <span class="block text-xs uppercase text-gray-500 dark:text-gray-400">Espacio usado</span>
                        <span class="mt-1 block text-xl font-semibold text-gray-800 dark:text-white">{{ stats.size }}</span>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800/60">
                        <span class="block text-xs uppercase text-gray-500 dark:text-gray-400">Más antiguo</span>
                        <span class="mt-1 block text-sm font-medium text-gray-800 dark:text-white">{{ stats.oldest_at || '—' }}</span>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800/60">
                        <span class="block text-xs uppercase text-gray-500 dark:text-gray-400">Más reciente</span>
                        <span class="mt-1 block text-sm font-medium text-gray-800 dark:text-white">{{ stats.newest_at || '—' }}</span>
                    </div>
                </div>

                <p v-if="!settings.store_context" class="mt-3 rounded-lg bg-warning/10 p-3 text-sm text-warning">
                    El guardado de contexto está apagado en Seguridad → Alertas: los avisos llegan, pero sin enlace
                    al detalle completo.
                </p>
            </div>

            <div class="panel p-5">
                <h3 class="flex items-center gap-2 text-base font-semibold text-gray-800 dark:text-white">
                    <font-awesome-icon :icon="faFilter" class="text-primary" />
                    Filtros
                </h3>

                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-5">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nivel</label>
                        <select v-model="filters.level" class="form-select w-full">
                            <option value="">Todos</option>
                            <option v-for="level in levels" :key="level" :value="level">{{ level }}</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Buscar</label>
                        <input
                            v-model="filters.search"
                            type="text"
                            class="form-input w-full"
                            placeholder="Mensaje, excepción, archivo, ruta o usuario"
                            @keyup.enter="load(1)"
                        />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Desde</label>
                        <input v-model="filters.date_start" type="date" class="form-input w-full" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Hasta</label>
                        <input v-model="filters.date_end" type="date" class="form-input w-full" />
                    </div>
                </div>

                <div class="mt-3 flex gap-2">
                    <button type="button" class="btn btn-primary" :disabled="loading" @click="load(1)">
                        <IconLoader v-if="loading" class="mr-2 h-4 w-4" />
                        Consultar
                    </button>
                    <button type="button" class="btn btn-outline-secondary" :disabled="loading" @click="clearFilters">
                        Limpiar
                    </button>
                </div>
            </div>

            <div class="panel p-5">
                <div class="table-responsive">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/60">
                                <th class="w-20">Nº</th>
                                <th class="min-w-[140px]">Fecha</th>
                                <th class="w-24">Nivel</th>
                                <th class="min-w-[280px]">Mensaje</th>
                                <th class="min-w-[220px]">Origen</th>
                                <th class="min-w-[180px]">Petición</th>
                                <th class="min-w-[160px]">Usuario</th>
                                <th class="w-20 text-center">Veces</th>
                                <th class="w-32 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="loading">
                                <td colspan="9" class="py-8 text-center">
                                    <IconLoader class="mx-auto h-6 w-6" />
                                </td>
                            </tr>
                            <tr v-else-if="rows.length === 0">
                                <td colspan="9" class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                    No hay errores guardados con estos filtros.
                                </td>
                            </tr>
                            <tr
                                v-for="row in rows"
                                v-else
                                :key="row.id"
                                class="hover:bg-gray-50/70 dark:hover:bg-gray-800/30"
                            >
                                <td class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ row.reference }}</td>
                                <td class="text-sm text-gray-600 dark:text-gray-300">{{ row.created_at }}</td>
                                <td>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium" :class="levelClass(row.level)">
                                        {{ row.level }}
                                    </span>
                                </td>
                                <td>
                                    <span class="block font-medium text-gray-800 dark:text-gray-100">{{ row.message }}</span>
                                    <span v-if="row.exception_class" class="block text-xs text-gray-500 dark:text-gray-400">{{ row.exception_class }}</span>
                                </td>
                                <td class="max-w-xs break-all text-xs text-gray-600 dark:text-gray-300">{{ row.origin || '—' }}</td>
                                <td class="max-w-xs break-all text-xs text-gray-600 dark:text-gray-300">
                                    <span v-if="row.method || row.url">{{ row.method }} {{ row.url }}</span>
                                    <span v-else>—</span>
                                </td>
                                <td class="text-sm text-gray-600 dark:text-gray-300">{{ row.user_label || '—' }}</td>
                                <td class="text-center text-sm font-medium text-gray-700 dark:text-gray-200">{{ row.occurrences }}</td>
                                <td class="text-center">
                                    <a
                                        :href="route('security_errors_show', row.id)"
                                        class="mr-1 inline-flex h-8 w-8 items-center justify-center rounded-lg border border-primary/40 text-primary hover:bg-primary/10"
                                        title="Ver contexto completo"
                                    >
                                        <font-awesome-icon :icon="faEye" />
                                    </a>
                                    <button
                                        v-if="canDelete"
                                        type="button"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-danger/40 text-danger hover:bg-danger/10"
                                        title="Eliminar error y su contexto"
                                        :disabled="deletingId === row.id"
                                        @click="removeOne(row)"
                                    >
                                        <IconLoader v-if="deletingId === row.id" class="h-4 w-4" />
                                        <font-awesome-icon v-else :icon="faTrash" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        Página <strong>{{ page }}</strong> de <strong>{{ lastPage }}</strong> · {{ total }} error(es)
                    </span>
                    <div class="flex gap-2">
                        <button
                            type="button"
                            class="btn btn-outline-primary"
                            :disabled="loading || page <= 1"
                            @click="load(page - 1)"
                        >
                            Anterior
                        </button>
                        <button
                            type="button"
                            class="btn btn-outline-primary"
                            :disabled="loading || page >= lastPage"
                            @click="load(page + 1)"
                        >
                            Siguiente
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
