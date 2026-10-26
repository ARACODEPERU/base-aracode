<script setup>
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal2 from 'sweetalert2';
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';

/**
 * Biblioteca de modelos de diploma del torneo.
 *
 * Cada fila es un diseño reutilizable, no un diploma entregado. Desde aquí se
 * crea y edita el modelo; en cada edición sólo se elige cuál usar por premio.
 */
const props = defineProps({
    sportsMode: { type: Boolean, default: false },
    event: { type: Object, required: true },
    editions: { type: Array, default: () => [] },
    templates: { type: Array, default: () => [] },
    types: { type: Array, default: () => [] },
    defaultEditionId: { type: [Number, String], default: null },
});

const showCreate = ref(false);
const creating = ref(false);
const form = ref({
    diploma_type: 'team',
    year: new Date().getFullYear(),
    edition_id: props.defaultEditionId,
    name: '',
    file: null,
});

const canCreate = computed(() => props.editions.length > 0);

const firstError = (error, fallback) => {
    const errors = error?.response?.data?.errors;

    if (errors) {
        const first = Object.values(errors)[0];

        if (Array.isArray(first) && first.length) return first[0];
    }

    return error?.response?.data?.message || fallback;
};

const openCreate = () => {
    form.value = {
        diploma_type: 'team',
        year: new Date().getFullYear(),
        edition_id: props.defaultEditionId,
        name: '',
        file: null,
    };
    showCreate.value = true;
};

const submit = () => {
    if (!form.value.file) {
        Swal2.fire({ icon: 'info', title: 'Falta el fondo', text: 'Sube la imagen que define el tamaño del diploma.' });
        return;
    }

    creating.value = true;

    router.post(
        route('even_eventos_diplomas_store', props.event.id),
        {
            diploma_type: form.value.diploma_type,
            year: form.value.year,
            edition_id: form.value.edition_id,
            name: form.value.name || 'Modelo de diploma',
            background: form.value.file,
        },
        {
            forceFormData: true,
            onFinish: () => {
                creating.value = false;
            },
        }
    );
};

const toggleActive = async (template) => {
    try {
        await axios.post(
            route('even_eventos_diplomas_activar', [props.event.id, template.id]),
            { active: !template.is_active }
        );
        router.reload({ only: ['templates'] });
    } catch (error) {
        Swal2.fire({ icon: 'error', title: 'No se pudo actualizar', text: firstError(error, 'Inténtalo de nuevo.') });
    }
};

const destroy = async (template) => {
    const result = await Swal2.fire({
        icon: 'warning',
        title: '¿Eliminar este modelo?',
        text: template.in_use
            ? 'Ya tiene diplomas emitidos o premios asignados: se desactivará para conservar las reimpresiones.'
            : 'Esta acción no se puede deshacer.',
        showCancelButton: true,
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626',
    });

    if (!result.isConfirmed) return;

    try {
        const { data } = await axios.delete(
            route('even_eventos_diplomas_destroy', [props.event.id, template.id])
        );

        router.reload({ only: ['templates'] });

        Swal2.fire({
            icon: data.deactivated ? 'info' : 'success',
            title: data.deactivated ? 'Modelo desactivado' : 'Modelo eliminado',
            text: data.message,
        });
    } catch (error) {
        Swal2.fire({ icon: 'error', title: 'No se pudo eliminar', text: firstError(error, 'Inténtalo de nuevo.') });
    }
};
</script>

<template>
    <AppLayout :title="`Diplomas · ${event.title}`">
        <Navigation
            :routeModule="route('even_dashboard')"
            :titleModule="sportsMode ? 'GESTIÓN DEPORTIVA' : 'Eventos sociales'"
            :data="[
                { route: route('even_ediciones_listado'), title: 'Torneos' },
                { title: 'Modelos de diploma' },
            ]"
        />

        <main class="mx-auto mt-5 max-w-6xl space-y-5 px-3 pb-10 sm:px-5">
            <section class="diploma-header">
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-lime-300/80">Diplomas y reconocimientos</p>
                    <h1 class="mt-1 text-2xl font-black tracking-tight text-white sm:text-3xl">
                        Modelos de diploma <span class="text-lime-300">· {{ event.title }}</span>
                    </h1>
                    <p class="mt-2 max-w-2xl text-sm text-slate-300">
                        Diseña un modelo una sola vez y reutilízalo en todas las ediciones del torneo. Al imprimir, el
                        sistema completa el equipo o jugador premiado.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <Link :href="route('even_ediciones_listado')" class="diploma-action">← Torneos</Link>
                    <button
                        type="button"
                        class="diploma-action diploma-action--primary"
                        :disabled="!canCreate"
                        @click="openCreate"
                    >
                        + Nuevo modelo
                    </button>
                </div>
            </section>

            <p v-if="!canCreate" class="diploma-note">
                Crea primero una edición del torneo para poder guardar el fondo del modelo.
            </p>

            <section class="diploma-card diploma-card--table">
                <table class="diploma-table">
                    <thead>
                        <tr>
                            <th>Año</th>
                            <th>Modelo</th>
                            <th>Tipo</th>
                            <th>Vigente</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="template in templates" :key="template.id">
                            <td class="font-black text-slate-900">{{ template.year || '—' }}</td>
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="h-14 w-24 shrink-0 overflow-hidden rounded-md border border-slate-200 bg-slate-100">
                                        <img
                                            v-if="template.background_url"
                                            :src="template.background_url"
                                            :alt="template.name"
                                            class="h-full w-full object-cover"
                                        />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate font-bold text-slate-800">{{ template.name }}</p>
                                        <p class="text-xs text-slate-500">
                                            {{ template.width }} × {{ template.height }} px · {{ template.elements_count }} elementos
                                        </p>
                                        <p v-if="template.in_use" class="text-xs font-semibold text-amber-600">
                                            {{ template.diplomas_count }} diplomas · {{ template.awards_count }} premios asignados
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="diploma-chip" :class="template.diploma_type === 'team' ? 'diploma-chip--team' : 'diploma-chip--player'">
                                    {{ template.type_label }}
                                </span>
                            </td>
                            <td>
                                <span class="diploma-chip" :class="template.is_active ? 'diploma-chip--done' : 'diploma-chip--off'">
                                    {{ template.is_active ? 'Sí' : 'No' }}
                                </span>
                            </td>
                            <td>
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <Link
                                        :href="route('even_eventos_diplomas_editar', [event.id, template.id])"
                                        class="diploma-action"
                                    >
                                        Editar
                                    </Link>
                                    <button type="button" class="diploma-action" @click="toggleActive(template)">
                                        {{ template.is_active ? 'Desactivar' : 'Activar' }}
                                    </button>
                                    <button type="button" class="diploma-action diploma-action--danger" @click="destroy(template)">
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="!templates.length" class="diploma-empty">
                    <p class="text-lg font-black text-slate-800">Todavía no hay modelos</p>
                    <p class="mt-1 text-sm text-slate-500">
                        Crea un modelo por equipo (con la lista del plantel) y otro por jugador (goleador, mejor jugador…).
                    </p>
                    <button
                        type="button"
                        class="diploma-action diploma-action--primary mt-4"
                        :disabled="!canCreate"
                        @click="openCreate"
                    >
                        + Crear el primero
                    </button>
                </div>
            </section>
        </main>

        <div v-if="showCreate" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4" @click.self="showCreate = false">
            <div class="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl">
                <header class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <div>
                        <h2 class="text-lg font-black text-slate-900">Nuevo modelo de diploma</h2>
                        <p class="text-sm text-slate-500">Podrás reutilizarlo en todas las ediciones.</p>
                    </div>
                    <button type="button" class="rounded-full p-2 text-slate-500 hover:bg-slate-100" @click="showCreate = false">✕</button>
                </header>

                <div class="space-y-3 px-5 py-4">
                    <label class="diploma-field">
                        <span>Tipo de diploma</span>
                        <select v-model="form.diploma_type">
                            <option v-for="type in types" :key="type.value" :value="type.value">{{ type.label }}</option>
                        </select>
                    </label>

                    <div class="grid grid-cols-2 gap-3">
                        <label class="diploma-field">
                            <span>Año de referencia</span>
                            <input v-model.number="form.year" type="number" min="2000" max="2100" />
                        </label>

                        <label class="diploma-field">
                            <span>Edición de origen</span>
                            <select v-model.number="form.edition_id">
                                <option v-for="edition in editions" :key="edition.id" :value="edition.id">
                                    {{ edition.name || edition.year }}
                                </option>
                            </select>
                        </label>
                    </div>

                    <label class="diploma-field">
                        <span>Nombre del modelo</span>
                        <input v-model="form.name" type="text" maxlength="120" placeholder="Diploma campeón" />
                    </label>

                    <label class="diploma-field">
                        <span>Imagen de fondo (define el tamaño)</span>
                        <input type="file" accept="image/png,image/jpeg,image/webp" @change="form.file = $event.target.files?.[0] || null" />
                    </label>
                </div>

                <footer class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4">
                    <button type="button" class="diploma-action" @click="showCreate = false">Cancelar</button>
                    <button
                        type="button"
                        class="diploma-action diploma-action--primary"
                        :disabled="creating"
                        @click="submit"
                    >
                        {{ creating ? 'Creando…' : 'Crear y diseñar' }}
                    </button>
                </footer>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.diploma-header {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    border-radius: 1rem;
    border: 1px solid #234052;
    background: linear-gradient(135deg, #0b1f2b 0%, #0e2532 55%, #122c3a 100%);
    padding: 1.25rem 1.5rem;
    box-shadow: 0 20px 50px rgb(0 0 0 / 25%);
}

.diploma-action {
    display: inline-flex;
    min-height: 2.4rem;
    align-items: center;
    justify-content: center;
    gap: 0.3rem;
    border-radius: 0.7rem;
    border: 1px solid #2b4b5d;
    background: #122c3a;
    padding: 0 0.85rem;
    font-size: 0.78rem;
    font-weight: 800;
    color: #e9f4f2;
    transition: all 0.15s ease;
}

.diploma-action:hover:not(:disabled) {
    border-color: rgb(132 204 22 / 60%);
    color: #d9f99d;
}

.diploma-action:disabled {
    cursor: not-allowed;
    opacity: 0.45;
}

.diploma-action--primary {
    border-color: #84cc16;
    background: #c8f169;
    color: #08251a;
}

.diploma-action--primary:hover:not(:disabled) {
    background: #d9fb8d;
    color: #08251a;
}

.diploma-action--danger {
    border-color: rgb(248 113 113 / 40%);
    background: rgb(69 10 10 / 45%);
    color: #fecaca;
}

.diploma-card {
    border-radius: 1rem;
    border: 1px solid #e2e8f0;
    background: #fff;
    padding: 1rem 1.15rem;
    box-shadow: 0 12px 30px rgb(15 23 42 / 6%);
}

.diploma-card--table {
    overflow-x: auto;
    padding: 0.5rem 0.75rem;
}

.diploma-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85rem;
}

.diploma-table th {
    padding: 0.65rem 0.6rem;
    text-align: left;
    font-size: 0.65rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #64748b;
    white-space: nowrap;
}

.diploma-table td {
    padding: 0.7rem 0.6rem;
    border-top: 1px solid #eef2f7;
    vertical-align: middle;
}

.diploma-chip {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    padding: 0.12rem 0.55rem;
    font-size: 0.65rem;
    font-weight: 800;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: #475569;
    white-space: nowrap;
}

.diploma-chip--team {
    border-color: #bfdbfe;
    background: #eff6ff;
    color: #1d4ed8;
}

.diploma-chip--player {
    border-color: #ddd6fe;
    background: #f5f3ff;
    color: #6d28d9;
}

.diploma-chip--done {
    border-color: #bbf7d0;
    background: #f0fdf4;
    color: #15803d;
}

.diploma-chip--off {
    border-color: #e2e8f0;
    background: #f8fafc;
    color: #94a3b8;
}

.diploma-empty {
    border-radius: 1rem;
    border: 1px dashed #cbd5e1;
    background: #fff;
    padding: 2.5rem 1.5rem;
    text-align: center;
}

.diploma-note {
    border-radius: 0.75rem;
    border: 1px solid #fde68a;
    background: #fffbeb;
    padding: 0.7rem 0.9rem;
    font-size: 0.8rem;
    font-weight: 700;
    color: #92400e;
}

.diploma-field {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: #475569;
}

.diploma-field select,
.diploma-field input {
    border: 1px solid #cbd5e1;
    border-radius: 0.6rem;
    padding: 0.5rem 0.65rem;
    font-size: 0.9rem;
    font-weight: 600;
    color: #0f172a;
    text-transform: none;
    letter-spacing: normal;
}
</style>
