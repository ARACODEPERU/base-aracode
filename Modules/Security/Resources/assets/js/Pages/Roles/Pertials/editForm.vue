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

    const selectAllCheckbox = (event) => {
        if(event.target.checked){
            const allPermissions = props.permissions;
            for (let i = 0; i < allPermissions.length; i++) {
                form.permissions[i] = allPermissions[i].name;
            }
        }else{
            form.permissions = [];
        }
    }
</script>

<template>
    <!-- Barra de búsqueda centrada y fijada debajo del header principal. -->
    <div
        class="permissions-search-sticky sticky z-30 mb-6 border-b border-gray-200/80 bg-white/95 px-2 py-3 backdrop-blur dark:border-gray-700/60 dark:bg-[#0e1726]/95"
        :style="{ top: `${stickyTop}px` }"
    >
        <div class="flex flex-col items-center gap-3">
            <div class="relative w-full max-w-md">
                <input
                    v-model="search"
                    type="text"
                    id="permisos-search"
                    class="block w-full p-2 pr-9 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                    placeholder="Buscar permiso"
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
            Roles Detalles
        </template>

        <template #description>
            Editar Rol
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
                <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
                    <div class="flex items-center">
                        <input @change="selectAllCheckbox($event)" id="checkboxAll" type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                        <label for="checkboxAll" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                            Todos
                        </label>
                    </div>
                    <p v-if="hayFiltro && totalEncontrados > 0" class="text-sm text-gray-500 dark:text-gray-400">
                        {{ totalEncontrados }} permiso(s) encontrado(s)
                    </p>
                </div>
                <ul class="font-semibold">
                    <li v-for="(row, index) in filteredSorted" class="py-[5px]">
                        <button type="button" @click="toggleTreeview1(row.modulo_id)">
                            <icon-caret-down
                                class="w-5 h-5 text-primary inline relative -top-1 ltr:mr-2 rtl:ml-2"
                                :class="{ 'rotate-180': !isRowOpen(row) }"
                            />
                            <icon-folder class="text-primary inline relative -top-1 ltr:mr-2 rtl:ml-2" />
                            {{ row.modulo_description }}
                        </button>
                        <vue-collapsible :isOpen="isRowOpen(row)">
                            <ul class="mt-2 ltr:pl-14 rtl:pr-14">
                                <li class="py-[5px]">
                                    <div v-if="row.permissions && row.permissions.length > 0">
                                        <div v-for="(item, key) in row.permissions" class="flex items-center space-x-4">
                                            <!-- From Uiverse.io by catraco -->
                                            <div class="can-container">
                                                <input :id="'checkbox'+index+key" v-model="form.permissions" :value="item.name" checked="checked" type="checkbox">
                                                <svg viewBox="0 0 576 512" height="1em" xmlns="http://www.w3.org/2000/svg" class="lock"><path d="M352 144c0-44.2 35.8-80 80-80s80 35.8 80 80v48c0 17.7 14.3 32 32 32s32-14.3 32-32V144C576 64.5 511.5 0 432 0S288 64.5 288 144v48H64c-35.3 0-64 28.7-64 64V448c0 35.3 28.7 64 64 64H384c35.3 0 64-28.7 64-64V256c0-35.3-28.7-64-64-64H352V144z"></path></svg>
                                                <svg viewBox="0 0 448 512" height="1em" xmlns="http://www.w3.org/2000/svg" class="lock-open"><path d="M144 144v48H304V144c0-44.2-35.8-80-80-80s-80 35.8-80 80zM80 192V144C80 64.5 144.5 0 224 0s144 64.5 144 144v48h16c35.3 0 64 28.7 64 64V448c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V256c0-35.3 28.7-64 64-64H80z"></path></svg>
                                            </div>
                                            <label :for="'checkbox'+index+key">{{ item.name }}</label>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </vue-collapsible>
                    </li>
                </ul>
                <p v-if="hayFiltro && filteredSorted.length === 0" class="text-sm text-gray-500 dark:text-gray-400">
                    No se encontraron permisos con este filtro.
                </p>
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
<style>
/* From Uiverse.io by catraco */
/*------ Settings ------*/
.can-container {
    --color: #8b8b96;
    --size: 16px;
    display: flex;
    justify-content: center;
    align-items: center;
    position: relative;
    cursor: pointer;
    font-size: var(--size);
    user-select: none;
    fill: var(--color);
}

.can-container .lock-open {
    position: absolute;
    animation: keyframes-fill .5s;
}

.can-container .lock {
    position: absolute;
    display: none;
    animation: keyframes-fill .5s;
}

/* ------ On check event ------ */
.can-container input:checked ~ .lock-open {
    display: none;
}

.can-container input:checked ~ .lock {
    display: block;
}

/* ------ Hide the default checkbox ------ */
.can-container input {
    position: absolute;
    opacity: 0;
    cursor: pointer;
    height: 0;
    width: 0;
}

/* ------ Animation ------ */
@keyframes keyframes-fill {
    0% {
        transform: scale(0);
        opacity: 0;
    }

    50% {
        transform: scale(1.2);
    }
}
</style>
