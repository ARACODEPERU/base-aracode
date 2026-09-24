<script setup>
/**
 * Historial del Modo Super Editor.
 *
 * Es la prueba de que "todo cambio queda registrado": quién entró al modo,
 * qué cambió en borrador, qué se aplicó al salir, qué se descartó y qué
 * intentos con contraseña fallaron.
 */
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import DataTable from 'datatables.net-vue3';
import DataTablesCore from 'datatables.net';
import 'datatables.net-responsive';
import '@/Components/vristo/datatables/datatables.css';
import '@/Components/vristo/datatables/style.css';
import es_PE from '@/Components/vristo/datatables/datatables-es.js';

DataTable.use(DataTablesCore);

const props = defineProps({
    actions: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const form = useForm({
    search: props.filters.search ?? '',
    action: props.filters.action ?? '',
});

const search = () => {
    form.get(route('super_editor_log'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const columns = [
    { data: 'created_at', title: 'Fecha' },
    { data: 'user_name', title: 'Usuario' },
    { data: 'action_label', title: 'Acción' },
    { data: 'element', title: 'Elemento' },
    { data: 'permission_name', title: 'Permiso' },
    { data: 'role_name', title: 'Rol' },
    { data: 'state', title: 'Antes → Después' },
    { data: 'notes', title: 'Detalle' },
];

// `responsive` está importado en el componente pero no estaba activado: con 8
// columnas la tabla se salía del panel y el rol (la columna que importa) quedaba
// fuera de la pantalla a 1440px. Con responsive las columnas que no caben se
// pliegan en la fila hija en lugar de desbordar.
const options = {
    language: es_PE,
    order: [[0, 'desc']],
    responsive: true,
    autoWidth: false,
};
</script>

<template>
    <AppLayout title="Historial del Modo Super Editor">
        <Navigation>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Configuraciones</span>
            </li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Historial del Modo Super Editor</span>
            </li>
        </Navigation>

        <div class="mt-5">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl">Historial del Modo Super Editor</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Todo lo que se hizo en el modo editor: entradas, cambios en borrador, aplicación al salir,
                        descartes y contraseñas incorrectas.
                    </p>
                </div>
                <Link :href="route('roles.index')" class="btn btn-outline-primary">
                    Ver roles
                </Link>
            </div>

            <div class="panel mt-6 p-4">
                <form class="flex flex-wrap items-end gap-3" @submit.prevent="search">
                    <div class="min-w-[220px] flex-1">
                        <label class="text-xs font-semibold text-slate-500">Buscar</label>
                        <input
                            v-model="form.search"
                            type="text"
                            placeholder="Permiso, rol, elemento o detalle…"
                            class="form-input mt-1 w-full"
                        />
                    </div>
                    <div class="min-w-[200px]">
                        <label class="text-xs font-semibold text-slate-500">Acción</label>
                        <select v-model="form.action" class="form-select mt-1 w-full">
                            <option value="">Todas</option>
                            <option v-for="(label, key) in actions" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                </form>
            </div>

            <div class="panel mt-6 pb-1.5">
                <DataTable :options="options" :ajax="route('super_editor_log_data')" :columns="columns" class="table-hover whitespace-nowrap" />
            </div>
        </div>
    </AppLayout>
</template>
