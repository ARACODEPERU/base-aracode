<script setup>
    import { useForm } from '@inertiajs/vue3';
    import FormSection from '@/Components/FormSection.vue';
    import InputError from '@/Components/InputError.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import TextInput from '@/Components/TextInput.vue';
    import Keypad from '@/Components/Keypad.vue';
    import swal from 'sweetalert';
    import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
    import VueCollapsible from 'vue-height-collapsible/vue3';
    import iconCaretDown from '@/Components/vristo/icon/icon-caret-down.vue';
    import iconFolder from '@/Components/vristo/icon/icon-folder.vue';
    import iconSearch from '@/Components/vristo/icon/icon-search.vue';
    import {
        contarPermisos,
        contarPorEstado,
        filtrarPermisos,
        ESTADO_HABILITADOS,
        ESTADO_SIN_HABILITADOS,
        ESTADO_TODOS,
    } from '@/utils/filtrarPermisos';

    const treeview1 = ref([]);
    const toggleTreeview1 = (name) => {
        if (treeview1.value.includes(name)) {
            treeview1.value = treeview1.value.filter((d) => d != name);
        } else {
            treeview1.value.push(name);
        }
    };

    const props = defineProps({
        role: {
            type: Object,
            default: () => ({}),
        },
        sorted: {
            type: Object,
            default: () => ({}),
        },
        permissions: {
            type: Object,
            default: () => ({}),
        },
        roleHasPermissions: {
            type: Object,
            default: () => ({}),
        }
    });

    const form = useForm({
        name: props.role.name,
        permissions: props.roleHasPermissions
    });

    const updateRol = () => {
        form.put(route('roles.update',props.role.id), {
            errorBag: 'updateRol',
            preserveScroll: true,
            onSuccess: () => {
                swal('Rol actualizado con éxito.');
            },
        });
    };

    // Texto escrito en el buscador; el filtrado se hace solo en el frontend
    const search = ref('');
    const searchTerm = computed(() => search.value.trim());

    // La barra pegajosa empieza debajo de la cabecera principal, dejando 4 px.
    const stickyTop = ref(4);
    let headerResizeObserver = null;
    const updateStickyTop = () => {
        const header = document.querySelector('.main-content > header');
        stickyTop.value = Math.ceil(header?.getBoundingClientRect().height ?? 0) + 4;
    };

    onMounted(() => {
        updateStickyTop();
        window.addEventListener('resize', updateStickyTop);

        const header = document.querySelector('.main-content > header');
        if (header && typeof ResizeObserver !== 'undefined') {
            headerResizeObserver = new ResizeObserver(updateStickyTop);
            headerResizeObserver.observe(header);
        }
    });

    onBeforeUnmount(() => {
        window.removeEventListener('resize', updateStickyTop);
        headerResizeObserver?.disconnect();
    });

    // Filtro de los botones: todos, solo los habilitados o solo los que faltan
    const estadoFiltro = ref(ESTADO_TODOS);
    const cambiarEstado = (estado) => {
        // Volver a pulsar el mismo botón quita el filtro
        estadoFiltro.value = estadoFiltro.value === estado ? ESTADO_TODOS : estado;
    };

    // Carpetas filtradas solo por el buscador: de aquí salen los números de los botones
    const soloPorTexto = computed(() => filtrarPermisos(props.sorted, search.value));
    const conteos = computed(() => contarPorEstado(soloPorTexto.value, form.permissions));

    // Carpetas con los permisos que coinciden con la búsqueda y el filtro de estado
    const filteredSorted = computed(() =>
        filtrarPermisos(soloPorTexto.value, '', estadoFiltro.value, form.permissions)
    );

    // Total de permisos encontrados en todas las carpetas de los módulos
    const totalEncontrados = computed(() => contarPermisos(filteredSorted.value));

    // Hay búsqueda o filtro de estado activo
    const hayFiltro = computed(() => searchTerm.value !== '' || estadoFiltro.value !== ESTADO_TODOS);

    // Con búsqueda o filtro, las carpetas con coincidencias se muestran abiertas
    const isRowOpen = (row) => hayFiltro.value || treeview1.value.includes(row.modulo_id);

    // Nombres de todos los permisos del sistema, para el interruptor «Todos».
    const nombresDePermisos = computed(() => (props.permissions ?? []).map((permiso) => permiso.name));

    // ¿El permiso está marcado en el formulario? Se usa para el contador de cada
    // carpeta y para resaltar la fila; el estado real lo lleva el checkbox.
    const estaMarcado = (name) => Array.isArray(form.permissions) && form.permissions.includes(name);

    const seleccionados = computed(
        () => nombresDePermisos.value.filter((name) => estaMarcado(name)).length
    );

    const todosSeleccionados = computed(
        () => nombresDePermisos.value.length > 0 && seleccionados.value === nombresDePermisos.value.length
    );

    // Estado intermedio del interruptor «Todos»: algunos sí y otros no.
    const seleccionParcial = computed(
        () => seleccionados.value > 0 && !todosSeleccionados.value
    );

    // El interruptor «Todos» reemplaza el arreglo completo: así el formulario
    // detecta el cambio y no queda ningún permiso a medias.
    const alternarTodos = (event) => {
        form.permissions = event.target.checked ? [...nombresDePermisos.value] : [];
    };

    // Cuántos permisos de una carpeta tiene marcados el rol, para el contador
    // que se ve en su cabecera.
    const habilitadosEnModulo = (row) =>
        (row?.permissions ?? []).filter((permiso) => estaMarcado(permiso.name)).length;
</script>

<template>
    <!-- Barra de búsqueda centrada y fijada debajo del header principal. -->
    <div
        class="permissions-search-sticky sticky z-30 mb-6 border-b border-gray-200/80 bg-white/95 px-2 py-3 backdrop-blur dark:border-gray-700/60 dark:bg-[#0e1726]/95"
        :style="{ top: `${stickyTop}px` }"
    >
        <div class="flex flex-col items-center gap-3 sm:flex-row sm:justify-center">
            <div class="relative w-full max-w-md">
                <input
                    v-model="search"
                    type="text"
                    id="permisos-search"
                    aria-label="Buscar permiso por nombre"
                    class="block w-full p-2 pr-9 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                    placeholder="Buscar permiso por nombre"
                >
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                    <icon-search class="w-4 h-4 text-gray-500 dark:text-gray-400" />
                </div>
            </div>
            <div class="flex flex-wrap items-center justify-center gap-2">
                <button
                    type="button"
                    id="filtro-habilitados"
                    :aria-pressed="estadoFiltro === ESTADO_HABILITADOS"
                    title="Ver solo los permisos habilitados para este rol. Pulsa de nuevo para ver todos."
                    class="px-4 py-2 text-xs font-medium uppercase rounded border transition duration-150 ease-in-out"
                    :class="estadoFiltro === ESTADO_HABILITADOS
                        ? 'bg-blue-600 text-white border-blue-600 hover:bg-blue-700'
                        : 'bg-gray-100 text-gray-700 border-gray-300 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600'"
                    @click="cambiarEstado(ESTADO_HABILITADOS)"
                >
                    Habilitados ({{ conteos.habilitados }})
                </button>
                <button
                    type="button"
                    id="filtro-sin-habilitados"
                    :aria-pressed="estadoFiltro === ESTADO_SIN_HABILITADOS"
                    title="Ver solo los permisos que este rol no tiene habilitados. Pulsa de nuevo para ver todos."
                    class="px-4 py-2 text-xs font-medium uppercase rounded border transition duration-150 ease-in-out"
                    :class="estadoFiltro === ESTADO_SIN_HABILITADOS
                        ? 'bg-blue-600 text-white border-blue-600 hover:bg-blue-700'
                        : 'bg-gray-100 text-gray-700 border-gray-300 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600'"
                    @click="cambiarEstado(ESTADO_SIN_HABILITADOS)"
                >
                    No habilitados ({{ conteos.sinHabilitados }})
                </button>
            </div>
        </div>
    </div>

    <FormSection @submitted="updateRol">
        <template #title>
            Permisos del rol
        </template>

        <template #description>
            Activa o desactiva los permisos de este rol. Busca por nombre o filtra por estado para encontrarlos más rápido.
        </template>

        <template #form>

            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="Nombre" value="Nombre" />
                <TextInput
                    id="Nombre"
                    v-model="form.name"
                    type="text"
                    class="block w-full mt-1"
                    autofocus
                />
                <InputError :message="form.errors.name" class="mt-2" />
            </div>
            <div class="col-span-6">
                <!-- Resumen del rol y atajo para marcar o desmarcar todo. -->
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-200 bg-gray-50/70 px-3 py-2.5 dark:border-gray-700 dark:bg-gray-900/40">
                    <label class="inline-flex cursor-pointer select-none items-center gap-2.5 text-sm font-medium text-gray-800 dark:text-gray-200">
                        <input
                            id="checkboxAll"
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-700 dark:focus:ring-offset-[#0e1726]"
                            :checked="todosSeleccionados"
                            :indeterminate.prop="seleccionParcial"
                            @change="alternarTodos"
                        >
                        <span>Habilitar todos</span>
                    </label>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        <span class="font-semibold text-gray-700 dark:text-gray-200">{{ seleccionados }}</span>
                        de {{ nombresDePermisos.length }} permisos habilitados
                        <span v-if="hayFiltro && totalEncontrados > 0" class="text-gray-400 dark:text-gray-500">
                            · {{ totalEncontrados }} en pantalla
                        </span>
                    </p>
                </div>
                <!-- Un panel con scroll propio: con cientos de permisos la página
                     no se alarga hasta el botón de guardar. -->
                <div class="permissions-panel rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-[#0e1726]">
                    <ul>
                        <li
                            v-for="row in filteredSorted"
                            :key="row.modulo_id"
                            class="border-b border-gray-200/80 last:border-b-0 dark:border-gray-700/60"
                        >
                            <button
                                type="button"
                                class="sticky top-0 z-10 flex w-full items-center gap-2.5 border-b border-transparent bg-white/95 px-3 py-2.5 text-left transition hover:bg-gray-50 dark:bg-[#0e1726]/95 dark:hover:bg-gray-800/70"
                                :aria-expanded="isRowOpen(row)"
                                @click="toggleTreeview1(row.modulo_id)"
                            >
                                <icon-caret-down
                                    class="h-4 w-4 shrink-0 text-primary transition-transform duration-200"
                                    :class="{ '-rotate-90': !isRowOpen(row) }"
                                />
                                <icon-folder class="h-4 w-4 shrink-0 text-primary" />
                                <span class="truncate text-sm font-bold text-gray-800 dark:text-gray-100">
                                    {{ row.modulo_description }}
                                </span>
                                <span class="ml-auto shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                    {{ habilitadosEnModulo(row) }}/{{ row.permissions.length }}
                                </span>
                            </button>

                            <vue-collapsible :isOpen="isRowOpen(row)">
                                <div
                                    v-if="row.permissions && row.permissions.length > 0"
                                    class="grid grid-cols-1 gap-1 px-2 pb-2.5 pt-1 sm:grid-cols-2 xl:grid-cols-3"
                                >
                                    <!-- El label envuelve al checkbox: el interruptor y el
                                         nombre responden al clic, sin ids que se repitan. -->
                                    <label
                                        v-for="item in row.permissions"
                                        :key="item.name"
                                        class="flex cursor-pointer items-center gap-2.5 rounded-lg px-2.5 py-1.5 transition"
                                        :class="estaMarcado(item.name)
                                            ? 'bg-primary/5 hover:bg-primary/10 dark:bg-primary/10 dark:hover:bg-primary/15'
                                            : 'hover:bg-gray-100 dark:hover:bg-gray-800/70'"
                                    >
                                        <input
                                            v-model="form.permissions"
                                            :value="item.name"
                                            type="checkbox"
                                            class="peer sr-only"
                                        >
                                        <span
                                            aria-hidden="true"
                                            class="relative inline-flex h-[18px] w-8 shrink-0 items-center rounded-full bg-gray-300 transition-colors duration-200 peer-checked:bg-primary peer-focus-visible:ring-2 peer-focus-visible:ring-primary peer-focus-visible:ring-offset-2 after:absolute after:left-[2px] after:h-[14px] after:w-[14px] after:rounded-full after:bg-white after:shadow-sm after:transition-transform after:duration-200 peer-checked:after:translate-x-[14px] dark:bg-gray-600 dark:peer-focus-visible:ring-offset-[#0e1726]"
                                        ></span>
                                        <span
                                            class="min-w-0 break-words text-[12px] font-medium leading-snug text-gray-700 dark:text-gray-300"
                                            :title="item.name"
                                        >
                                            {{ item.name }}
                                        </span>
                                    </label>
                                </div>
                            </vue-collapsible>
                        </li>
                    </ul>

                    <p v-if="hayFiltro && filteredSorted.length === 0" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                        No se encontraron permisos con este filtro.
                    </p>
                </div>
            </div>
        </template>

        <template #actions>
            <Keypad>
                <template #botones>
                    <PrimaryButton
                        :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Actualizar
                    </PrimaryButton>
                    <a :href="route('roles.index')"  class="inline-block px-6 py-2.5 bg-green-500 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-green-600 hover:shadow-lg focus:bg-green-600 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-green-700 active:shadow-lg transition duration-150 ease-in-out ml-2">Ir al Listado</a>
                </template>
            </Keypad>
        </template>
    </FormSection>
</template>
