<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { router } from '@inertiajs/vue3';
import Swal2 from 'sweetalert2';
import { computed, ref } from 'vue';
import { faBroom, faFlask, faRocket, faTriangleExclamation } from '@fortawesome/free-solid-svg-icons';

/*
 * Academico > Modos de Prueba.
 *
 * Solo la ve el rol admin (permiso aca_school_modo_prueba) y solo cuando el
 * tipo de negocio es Colegio (5) o Colegio y capacitaciones (6). El backend
 * vuelve a comprobar todo: permiso, tipo de negocio, bloqueo de modo produccion
 * y las confirmaciones de cada boton.
 */
const props = defineProps({
    summary: { type: Object, default: () => ({ total: 0, counts: [] }) },
    lock: { type: Object, default: () => ({}) },
    limits: { type: Object, default: () => ({}) },
    confirmations: { type: Object, default: () => ({ purge: '', production: '' }) },
    businessType: { type: String, default: '' },
});

const sectionsPerGrade = ref(props.limits?.sections_per_grade?.default ?? 1);
const studentsPerSection = ref(props.limits?.students_per_section?.default ?? 5);
const working = ref(false);

const locked = computed(() => props.lock?.locked === true);
const totalTest = computed(() => props.summary?.total ?? 0);
const busy = computed(() => working.value || locked.value);

const showMessage = (msg = '', type = 'success') => {
    const toast = Swal2.mixin({
        toast: true,
        position: 'top',
        showConfirmButton: false,
        timer: 4000,
        customClass: { container: 'toast' },
    });
    toast.fire({ icon: type, title: msg, padding: '10px 20px' });
};

/** Mensaje legible de un error de axios (validaciones incluidas). */
const errorMessage = (error) => {
    const data = error?.response?.data;

    if (data?.errors) {
        const first = Object.values(data.errors).flat();

        if (first.length > 0) {
            return first[0];
        }
    }

    return data?.message || 'No se pudo completar la operación.';
};

/** Envia la accion al backend y devuelve el mensaje del servidor. */
const send = async (routeName, payload = {}) => {
    const response = await axios.post(route(routeName), payload);

    return response.data;
};

const refresh = () => router.reload({ only: ['summary', 'lock'] });

/** Boton 1: llenar la base con datos de prueba. */
const fill = async () => {
    if (busy.value) {
        return;
    }

    const confirm = await Swal2.fire({
        title: 'Llenar con datos de prueba',
        html: `
            <p class="text-sm text-gray-500 text-left mb-3">
                Se crearán niveles, grados, secciones, jornadas, áreas, docentes, auxiliares,
                alumnos, apoderados, matrículas y bloques de horario de prueba
                (todos identificables con la marca <b>${'PRUEBA'}</b>).
                Lo que ya exista no se toca: solo se agrega lo que falta.
            </p>
            <p class="text-sm text-left">¿Crear los datos de prueba?</p>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, llenar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        buttonsStyling: false,
        customClass: {
            popup: 'sweet-alerts',
            confirmButton: 'btn btn-primary',
            cancelButton: 'btn btn-dark ltr:mr-3 rtl:ml-3',
        },
    });

    if (!confirm.isConfirmed) {
        return;
    }

    working.value = true;

    try {
        const data = await send('aca_school_test_mode_fill', {
            sections_per_grade: sectionsPerGrade.value,
            students_per_section: studentsPerSection.value,
        });
        showMessage(data.message);
        refresh();
    } catch (error) {
        showMessage(errorMessage(error), 'error');
    } finally {
        working.value = false;
    }
};

/** Boton 2: borrar los datos de prueba y lo generado con ellos. */
const purge = async () => {
    if (busy.value) {
        return;
    }

    const confirm = await Swal2.fire({
        title: 'Eliminar datos de prueba',
        html: `
            <p class="text-sm text-gray-500 text-left mb-3">
                Se borra todo lo creado por el modo de prueba y lo generado al usarlo
                (asistencias de aula y de puerta, avisos por SMS, calificaciones, cobros
                y cronogramas de pago). Los comprobantes enviados a SUNAT no se tocan.
            </p>
            <p class="text-sm text-left mb-1">Escribe <b>${props.confirmations.purge}</b> para confirmar:</p>
            <input id="swal-purge-confirm" class="form-input w-full" autocomplete="off" />
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        buttonsStyling: false,
        customClass: {
            popup: 'sweet-alerts',
            confirmButton: 'btn btn-danger',
            cancelButton: 'btn btn-dark ltr:mr-3 rtl:ml-3',
        },
        showLoaderOnConfirm: true,
        preConfirm: async () => {
            const confirmation = document.getElementById('swal-purge-confirm')?.value ?? '';

            if (confirmation !== props.confirmations.purge) {
                Swal2.showValidationMessage('La frase de confirmación no coincide.');

                return false;
            }

            try {
                const data = await send('aca_school_test_mode_purge', { confirmation });

                return data.message;
            } catch (error) {
                Swal2.showValidationMessage(errorMessage(error));

                return false;
            }
        },
        allowOutsideClick: () => !Swal2.isLoading(),
    });

    if (confirm.isConfirmed) {
        showMessage(confirm.value);
        refresh();
    }
};

/** Boton 3: borrar todo y dejar el sistema en modo produccion (definitivo). */
const production = async () => {
    if (busy.value) {
        return;
    }

    const confirm = await Swal2.fire({
        title: 'Dejar en modo producción',
        html: `
            <p class="text-sm text-gray-500 text-left mb-3">
                Se eliminan los datos de prueba y lo generado con ellos, y después los botones
                de llenado y borrado quedan <b>bloqueados definitivamente</b>:
                esta acción no se puede deshacer desde el sistema.
            </p>
            <p class="text-sm text-left mb-1">Escribe <b>${props.confirmations.production}</b>:</p>
            <input id="swal-production-confirm" class="form-input w-full mb-3" autocomplete="off" />
            <p class="text-sm text-left mb-1">Tu contraseña:</p>
            <input id="swal-production-password" type="password" class="form-input w-full" autocomplete="current-password" />
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, dejar en producción',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        buttonsStyling: false,
        customClass: {
            popup: 'sweet-alerts',
            confirmButton: 'btn btn-danger',
            cancelButton: 'btn btn-dark ltr:mr-3 rtl:ml-3',
        },
        showLoaderOnConfirm: true,
        preConfirm: async () => {
            const confirmation = document.getElementById('swal-production-confirm')?.value ?? '';
            const password = document.getElementById('swal-production-password')?.value ?? '';

            if (confirmation !== props.confirmations.production) {
                Swal2.showValidationMessage('La frase de confirmación no coincide.');

                return false;
            }

            if (!password) {
                Swal2.showValidationMessage('Escribe tu contraseña.');

                return false;
            }

            try {
                const data = await send('aca_school_test_mode_production', { confirmation, password });

                return data.message;
            } catch (error) {
                Swal2.showValidationMessage(errorMessage(error));

                return false;
            }
        },
        allowOutsideClick: () => !Swal2.isLoading(),
    });

    if (confirm.isConfirmed) {
        showMessage(confirm.value);
        refresh();
    }
};
</script>

<template>
    <AppLayout title="Modos de Prueba">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                { route: route('aca_school_test_mode'), title: 'Modos de Prueba' },
            ]"
        />
        <div class="pt-5 space-y-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl">Modos de Prueba</h2>
                <span v-if="locked" class="badge bg-danger">Modo producción</span>
                <span v-else-if="totalTest > 0" class="badge bg-warning">Datos de prueba activos</span>
                <span v-else class="badge bg-secondary">Sin datos de prueba</span>
            </div>

            <!-- Aviso del bloqueo definitivo -->
            <div v-if="locked" class="panel border-l-4 border-danger">
                <div class="flex items-start gap-3">
                    <font-awesome-icon :icon="faTriangleExclamation" class="text-danger text-xl mt-0.5" />
                    <div>
                        <h5 class="font-semibold text-danger">El sistema está en modo producción</h5>
                        <p class="text-sm text-white-dark">
                            Los datos de prueba ya se borraron y el llenado quedó bloqueado de forma definitiva.
                            <span v-if="lock.locked_at">Se aplicó el {{ lock.locked_at }}<span v-if="lock.locked_by"> por {{ lock.locked_by }}</span>.</span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <!-- Llenar -->
                <div class="panel">
                    <div class="flex items-center gap-2 mb-3">
                        <font-awesome-icon :icon="faFlask" class="text-primary text-lg" />
                        <h5 class="font-semibold">Llenar datos de prueba</h5>
                    </div>
                    <p class="text-sm text-white-dark mb-4">
                        Crea la estructura del colegio y alumnos de prueba para recorrer el sistema
                        (portería, asistencia de aula, calificaciones, horarios y cobros).
                    </p>
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div>
                            <label class="text-xs text-white-dark">Secciones por grado</label>
                            <input
                                type="number"
                                class="form-input"
                                :min="limits?.sections_per_grade?.min ?? 1"
                                :max="limits?.sections_per_grade?.max ?? 3"
                                v-model.number="sectionsPerGrade"
                                :disabled="busy"
                            />
                        </div>
                        <div>
                            <label class="text-xs text-white-dark">Alumnos por sección</label>
                            <input
                                type="number"
                                class="form-input"
                                :min="limits?.students_per_section?.min ?? 1"
                                :max="limits?.students_per_section?.max ?? 20"
                                v-model.number="studentsPerSection"
                                :disabled="busy"
                            />
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary w-full" :disabled="busy" @click="fill">
                        <font-awesome-icon :icon="faFlask" class="ltr:mr-2 rtl:ml-2" />
                        Llenar datos de prueba
                    </button>
                </div>

                <!-- Borrar -->
                <div class="panel">
                    <div class="flex items-center gap-2 mb-3">
                        <font-awesome-icon :icon="faBroom" class="text-warning text-lg" />
                        <h5 class="font-semibold">Eliminar datos de prueba</h5>
                    </div>
                    <p class="text-sm text-white-dark mb-4">
                        Borra los datos creados y todo lo generado al usarlos. El colegio queda como
                        estaba: los datos reales no se tocan. Pide la frase de confirmación.
                    </p>
                    <p class="text-xs text-white-dark mb-4">
                        Frase requerida: <b>{{ confirmations.purge }}</b>
                    </p>
                    <button type="button" class="btn btn-outline-warning w-full" :disabled="busy" @click="purge">
                        <font-awesome-icon :icon="faBroom" class="ltr:mr-2 rtl:ml-2" />
                        Eliminar datos de prueba
                    </button>
                </div>

                <!-- Modo produccion -->
                <div class="panel">
                    <div class="flex items-center gap-2 mb-3">
                        <font-awesome-icon :icon="faRocket" class="text-danger text-lg" />
                        <h5 class="font-semibold">Dejar en modo producción</h5>
                    </div>
                    <p class="text-sm text-white-dark mb-4">
                        Borra los datos de prueba y <b>bloquea definitivamente</b> los botones de
                        llenado y borrado. Pide la frase de confirmación y tu contraseña.
                    </p>
                    <p class="text-xs text-white-dark mb-4">
                        Frase requerida: <b>{{ confirmations.production }}</b>
                    </p>
                    <button type="button" class="btn btn-outline-danger w-full" :disabled="busy" @click="production">
                        <font-awesome-icon :icon="faRocket" class="ltr:mr-2 rtl:ml-2" />
                        Dejar en modo producción
                    </button>
                </div>
            </div>

            <!-- Resumen de lo que hay cargado -->
            <div class="panel p-0 border-0 overflow-hidden">
                <div class="flex items-center justify-between flex-wrap gap-3 p-5">
                    <div>
                        <h5 class="font-semibold">Datos de prueba cargados</h5>
                        <p class="text-xs text-white-dark">
                            Total: {{ totalTest }} registros
                            <span v-if="lock.last_fill_at"> · Último llenado: {{ lock.last_fill_at }}</span>
                            <span v-if="lock.last_purge_at"> · Última limpieza: {{ lock.last_purge_at }}</span>
                        </p>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Dato</th>
                                <th class="!text-center">Registros</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in summary.counts" :key="row.entity">
                                <td>{{ row.label }}</td>
                                <td class="text-center">{{ row.total }}</td>
                            </tr>
                            <tr v-if="!summary.counts || summary.counts.length === 0">
                                <td colspan="2" class="text-center text-white-dark py-6">Sin información</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <p class="text-xs text-white-dark">
                Los datos de prueba se identifican con la marca <b>PRUEBA</b> en códigos y nombres, y con el
                documento <b>TEST…</b> en las personas. Al borrarlos se eliminan también las asistencias,
                avisos, calificaciones y cobros que se hayan generado con ellos.
            </p>
        </div>
    </AppLayout>
</template>
