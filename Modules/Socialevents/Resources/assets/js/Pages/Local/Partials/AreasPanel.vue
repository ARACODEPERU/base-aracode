<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import ModalLarge from '@/Components/ModalLarge.vue';

const props = defineProps({
    localId: { type: Number, required: true },
    areaTypes: { type: Array, default: () => [] },
});

// Áreas activas del local, cargadas desde el backend.
const areas = ref([]);
const cargando = ref(false);
const guardando = ref(false);
const errorCarga = ref('');
const errores = reactive({});

const formulario = reactive({
    name: '',
    area_type: props.areaTypes[0]?.value || 'event_hall',
    capacity: '',
});

// Etiqueta legible del tipo de área, con respaldo al valor técnico.
const etiquetaTipo = (valor) => props.areaTypes.find((t) => t.value === valor)?.label || valor;

const cargarAreas = async () => {
    cargando.value = true;
    errorCarga.value = '';
    try {
        const { data } = await axios.get(route('even_alquiler_local_areas_by_local', props.localId));
        areas.value = data.areas || [];
    } catch (error) {
        errorCarga.value = 'No se pudieron cargar las áreas. Intente de nuevo.';
    } finally {
        cargando.value = false;
    }
};

// Área que se está editando (id) y sus valores temporales.
const editandoId = ref(null);
const edicion = reactive({ name: '', area_type: '', capacity: '', schedules: [] });

// El área en edición según la lista; el modal se muestra mientras exista.
const areaEditando = computed(() => areas.value.find((a) => a.id === editandoId.value) || null);

// Días de la semana (0 = domingo, igual que el servidor) para el horario del campo.
const diasSemana = [
    { value: 1, label: 'Lunes' },
    { value: 2, label: 'Martes' },
    { value: 3, label: 'Miércoles' },
    { value: 4, label: 'Jueves' },
    { value: 5, label: 'Viernes' },
    { value: 6, label: 'Sábado' },
    { value: 0, label: 'Domingo' },
];

// Horario del campo: un día activo con apertura y cierre. Se arma desde el horario guardado.
const horarioPorDia = (area) => diasSemana.map((dia) => {
    const guardado = (area.schedules || []).find((s) => s.day_of_week === dia.value);
    return {
        day_of_week: dia.value,
        label: dia.label,
        activo: !!guardado,
        open_time: guardado ? guardado.open_time.slice(0, 5) : '07:00',
        close_time: guardado ? guardado.close_time.slice(0, 5) : '12:00',
    };
});
const guardandoEdicion = ref(false);
const errorEdicion = ref('');

const iniciarEdicion = (area) => {
    editandoId.value = area.id;
    edicion.name = area.name;
    edicion.area_type = area.area_type;
    edicion.capacity = area.capacity ?? '';
    edicion.schedules = horarioPorDia(area);
    errorEdicion.value = '';
};

// Si el admin cambia el tipo a campo deportivo, la rejilla de horario debe existir.
watch(() => edicion.area_type, (tipo) => {
    if (tipo === 'sports_field' && edicion.schedules.length === 0) {
        edicion.schedules = horarioPorDia({ schedules: [] });
    }
});

const cancelarEdicion = () => {
    editandoId.value = null;
    errorEdicion.value = '';
};

const guardarEdicion = async (area) => {
    guardandoEdicion.value = true;
    errorEdicion.value = '';
    try {
        const { data } = await axios.put(route('even_alquiler_local_areas_update', area.id), {
            name: edicion.name,
            area_type: edicion.area_type,
            capacity: edicion.capacity || null,
            // Solo los días activos; en áreas que no son campo el servidor limpia el horario.
            schedules: edicion.area_type === 'sports_field'
                ? edicion.schedules
                    .filter((d) => d.activo)
                    .map((d) => ({ day_of_week: d.day_of_week, open_time: d.open_time, close_time: d.close_time }))
                : [],
        });
        Object.assign(area, data.area);
        editandoId.value = null;
        window.alertaSistema('Enhorabuena', 'Área actualizada correctamente', 'success');
    } catch (error) {
        const respuesta = error.response?.data?.errors || {};
        errorEdicion.value = Object.values(respuesta)[0]?.[0] || 'No se pudo actualizar el área';
    } finally {
        guardandoEdicion.value = false;
    }
};

// Ejecuta el borrado en el servidor. La confirmación de Aracodito espera esta
// respuesta con la terminal del pecho animada; si falla, lanza el error.
const borrarArea = async (area) => {
    const { data } = await axios.delete(route('even_alquiler_local_areas_destroy', area.id));
    // Con reservas el backend solo desactiva el área: sale de la lista igualmente.
    areas.value = areas.value.filter((a) => a.id !== area.id);
    return data.message || 'Área eliminada correctamente.';
};

const eliminarArea = async (area) => {
    const mensajeError = (error) => error.response?.data?.message || 'No se pudo eliminar el área.';

    const resultado = await window.alertaSistema('¿Eliminar área?', `Se quitará "${area.name}" del local.`, 'warning', {
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        duration: 0,
        loadingText: 'Eliminando área...',
        successMessage: 'Área eliminada correctamente.',
        errorMessage: 'No se pudo eliminar el área.',
        showLoaderOnConfirm: true,
        onConfirm: async () => ({ message: await borrarArea(area), type: 'success' }),
        preConfirm: () => borrarArea(area).catch((error) => {
            throw new Error(mensajeError(error));
        }),
    });

    if (!resultado?.isConfirmed) {
        return;
    }

    // Aracodito ya mostró el resultado en su burbuja; con SweetAlert2 se avisa aquí.
    if (window.alertaSistema.enAracodito?.()) {
        return;
    }

    if (resultado.value) {
        window.alertaSistema('Listo', resultado.value, 'success');
    }
};

const guardarArea = async () => {
    guardando.value = true;
    Object.keys(errores).forEach((k) => delete errores[k]);
    try {
        const { data } = await axios.post(route('even_alquiler_local_areas_store'), {
            local_id: props.localId,
            name: formulario.name,
            area_type: formulario.area_type,
            capacity: formulario.capacity || null,
        });
        areas.value.push(data.area);
        formulario.name = '';
        formulario.capacity = '';
        window.alertaSistema('Enhorabuena', 'Área guardada correctamente', 'success');
    } catch (error) {
        const respuesta = error.response?.data?.errors || {};
        Object.entries(respuesta).forEach(([campo, mensajes]) => {
            errores[campo] = mensajes[0];
        });
        if (!Object.keys(respuesta).length) {
            window.alertaSistema('Error', 'No se pudo guardar el área', 'error');
        }
    } finally {
        guardando.value = false;
    }
};

onMounted(cargarAreas);
</script>

<template>
    <div class="panel mt-6">
        <div class="mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Áreas del local</h5>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Salón, campo deportivo u otras áreas que se pueden alquilar por separado.
            </p>
        </div>

        <p v-if="cargando" class="text-sm text-gray-500" role="status">Cargando áreas…</p>

        <div v-else-if="errorCarga" class="text-sm text-danger" role="alert">
            {{ errorCarga }}
            <button type="button" class="ml-2 underline" @click="cargarAreas">Reintentar</button>
        </div>

        <p v-else-if="!areas.length" class="text-sm text-gray-500">
            Este local aún no tiene áreas. Agregue la primera abajo.
        </p>

        <ul v-else class="divide-y divide-gray-200 dark:divide-gray-700 mb-6">
            <li v-for="area in areas" :key="area.id" class="flex items-center justify-between gap-4 py-3">
                <div class="min-w-0">
                    <span class="font-medium break-words">{{ area.name }}</span>
                    <span class="block text-sm text-gray-500">
                        {{ etiquetaTipo(area.area_type) }}
                        <template v-if="area.capacity"> · aforo {{ area.capacity }}</template>
                    </span>
                </div>
                <div class="flex gap-2 shrink-0">
                    <button type="button" class="btn btn-sm btn-outline-primary" @click="iniciarEdicion(area)">Editar</button>
                    <button type="button" class="btn btn-sm btn-outline-danger" @click="eliminarArea(area)">Eliminar</button>
                </div>
            </li>
        </ul>

        <!-- Formulario de nueva área -->
        <form class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end" @submit.prevent="guardarArea">
            <div>
                <label for="area-nombre" class="block text-sm font-medium mb-1">Nombre</label>
                <input id="area-nombre" v-model="formulario.name" type="text" class="form-input w-full" placeholder="Ej. Salón principal" />
                <p v-if="errores.name" class="text-danger text-xs mt-1">{{ errores.name }}</p>
            </div>
            <div>
                <label for="area-tipo" class="block text-sm font-medium mb-1">Tipo</label>
                <select id="area-tipo" v-model="formulario.area_type" class="form-select w-full">
                    <option v-for="tipo in areaTypes" :key="tipo.value" :value="tipo.value">
                        {{ tipo.label }}
                    </option>
                </select>
                <p v-if="errores.area_type" class="text-danger text-xs mt-1">{{ errores.area_type }}</p>
            </div>
            <div>
                <label for="area-aforo" class="block text-sm font-medium mb-1">Aforo (opcional)</label>
                <input id="area-aforo" v-model="formulario.capacity" type="number" min="1" class="form-input w-full" />
                <p v-if="errores.capacity" class="text-danger text-xs mt-1">{{ errores.capacity }}</p>
            </div>
            <div>
                <button type="submit" class="btn btn-primary w-full" :disabled="guardando">
                    {{ guardando ? 'Guardando…' : 'Agregar área' }}
                </button>
            </div>
        </form>

        <!-- Modal de edición de área: nombre, tipo, aforo y horario del campo -->
        <ModalLarge :show="!!areaEditando" :on-close="cancelarEdicion" :icon="'/img/caja-registradora.png'">
            <template #title>Editar área</template>
            <template #message>
                <span v-if="areaEditando">{{ areaEditando.name }}</span>
            </template>
            <template #content>
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label for="edit-area-nombre" class="block text-sm font-medium mb-1">Nombre</label>
                        <input id="edit-area-nombre" v-model="edicion.name" type="text" class="form-input w-full" />
                    </div>
                    <div>
                        <label for="edit-area-tipo" class="block text-sm font-medium mb-1">Tipo</label>
                        <select id="edit-area-tipo" v-model="edicion.area_type" class="form-select w-full">
                            <option v-for="tipo in areaTypes" :key="tipo.value" :value="tipo.value">
                                {{ tipo.label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label for="edit-area-aforo" class="block text-sm font-medium mb-1">Aforo (opcional)</label>
                        <input id="edit-area-aforo" v-model="edicion.capacity" type="number" min="1" class="form-input w-full" />
                    </div>

                    <!-- Horario semanal: solo para campos deportivos. Las reservas por hora se validan contra este horario. -->
                    <div v-if="edicion.area_type === 'sports_field'" class="border-t border-gray-200 dark:border-gray-600 pt-4">
                        <p class="text-sm font-medium mb-3">Horario del campo</p>
                        <div v-for="dia in edicion.schedules" :key="dia.day_of_week" class="grid grid-cols-1 sm:grid-cols-3 gap-2 items-center mb-2">
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input v-model="dia.activo" type="checkbox" class="form-checkbox" />
                                {{ dia.label }}
                            </label>
                            <input v-model="dia.open_time" type="time" class="form-input" :disabled="!dia.activo" :aria-label="`Apertura ${dia.label}`" />
                            <input v-model="dia.close_time" type="time" class="form-input" :disabled="!dia.activo" :aria-label="`Cierre ${dia.label}`" />
                        </div>
                    </div>

                    <p v-if="errorEdicion" class="text-danger text-sm" role="alert">{{ errorEdicion }}</p>
                </div>
            </template>
            <template #buttons>
                <button type="button" class="btn btn-primary" :disabled="guardandoEdicion" @click="guardarEdicion(areaEditando)">
                    {{ guardandoEdicion ? 'Guardando…' : 'Guardar cambios' }}
                </button>
            </template>
        </ModalLarge>
    </div>
</template>
