<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import { faGears } from '@fortawesome/free-solid-svg-icons';
    import { Link, useForm } from '@inertiajs/vue3';
    import { Dropdown, Menu, MenuItem, Input, Select, Textarea, Checkbox, message, Tag } from 'ant-design-vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import Keypad from '@/Components/Keypad.vue';
    import { reactive, computed } from 'vue';

    const props = defineProps({
        parameters: {
            type: Array,
            default: () => [],
        },
        filters: {
            type: Object,
            default: () => ({}),
        },
    });

    const form = useForm({
        search: props.filters.search ?? '',
    });

    const selectedMulti = reactive({});
    const savingIds = reactive({});

    props.parameters.forEach((parameter) => {
        if (['chq', 'chj'].includes(parameter.control_type)) {
            selectedMulti[parameter.id] = [...(parameter.selected_values ?? [])];
        }
    });

    const controlTypeLabels = {
        in: 'Texto',
        sq: 'Select SQL',
        sa: 'Select JSON',
        tx: 'Textarea',
        rdj: 'Radio JSON',
        chj: 'Checkbox JSON',
        chq: 'Checkbox SQL',
        chx: 'Switch',
    };

    const controlTypeColors = {
        in: 'blue',
        sq: 'purple',
        sa: 'geekblue',
        tx: 'cyan',
        rdj: 'gold',
        chj: 'orange',
        chq: 'green',
        chx: 'magenta',
    };

    const totalParameters = computed(() => props.parameters.length);

    const updateDefaultValue = async (id, value) => {
        savingIds[id] = true;

        try {
            const { data } = await axios.post(route('parameters_update_default_value', id), { value });

            if (data?.selected_values && selectedMulti[id] !== undefined) {
                selectedMulti[id] = [...data.selected_values];
            }

            message.success('Valor actualizado correctamente');
        } catch (error) {
            message.error('No se pudo guardar el valor');
        } finally {
            savingIds[id] = false;
        }
    };

    const onMultiChange = (parameter, values) => {
        selectedMulti[parameter.id] = values;
        updateDefaultValue(parameter.id, values);
    };

    const isTruthyCheckbox = (value) => {
        return value === true || value === 'true' || value === '1' || value === 1;
    };

    const onToggleSwitch = (parameter, checked) => {
        parameter.value_default = checked ? 'true' : 'false';
        updateDefaultValue(parameter.id, parameter.value_default);
    };
</script>

<template>
    <AppLayout title="Parametros">
        <Navigation>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Configuraciones</span>
            </li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Parametros</span>
            </li>
        </Navigation>

        <div class="mt-5 space-y-5">
            <div class="panel">
                <div class="flex flex-col gap-4 p-5 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Parámetros del sistema</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ totalParameters }} registros · edita el valor por defecto directamente en la tabla
                        </p>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <form @submit.prevent="form.get(route('parameters'))" class="w-full sm:w-auto">
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <svg class="h-4 w-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <input
                                    v-model="form.search"
                                    type="text"
                                    class="form-input w-full min-w-[260px] py-2 pl-10"
                                    placeholder="Buscar por descripción o código"
                                />
                            </div>
                        </form>

                        <Keypad>
                            <template #botones>
                                <Link
                                    :href="route('parameters_create')"
                                    class="btn btn-primary"
                                >
                                    Nuevo parámetro
                                </Link>
                            </template>
                        </Keypad>
                    </div>
                </div>
            </div>

            <div class="panel overflow-hidden p-0">
                <div class="table-responsive">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/60">
                                <th class="w-16 text-center">Acciones</th>
                                <th class="w-28">Código</th>
                                <th class="min-w-[220px]">Descripción</th>
                                <th class="w-32">Tipo</th>
                                <th class="min-w-[320px]">Valor por defecto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(parameter, index) in parameters"
                                :key="parameter.id"
                                class="align-top hover:bg-gray-50/70 dark:hover:bg-gray-800/30"
                            >
                                <td class="text-center">
                                    <Dropdown placement="bottomLeft" arrow>
                                        <button
                                            class="dropdown-button inline-flex h-9 w-9 items-center justify-center rounded-lg border text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700"
                                            type="button"
                                        >
                                            <font-awesome-icon :icon="faGears" />
                                        </button>
                                        <template #overlay>
                                            <Menu>
                                                <MenuItem>
                                                    <Link :href="route('parameters_edit', parameter.id)">Editar</Link>
                                                </MenuItem>
                                            </Menu>
                                        </template>
                                    </Dropdown>
                                </td>

                                <td>
                                    <span class="inline-flex rounded-md bg-primary/10 px-2 py-1 font-mono text-xs font-semibold text-primary">
                                        {{ parameter.parameter_code }}
                                    </span>
                                </td>

                                <td>
                                    <p class="font-medium text-gray-800 dark:text-gray-100">{{ parameter.description }}</p>
                                    <p v-if="savingIds[parameter.id]" class="mt-1 text-xs text-primary">Guardando...</p>
                                </td>

                                <td>
                                    <Tag :color="controlTypeColors[parameter.control_type] || 'default'">
                                        {{ controlTypeLabels[parameter.control_type] || parameter.control_type }}
                                    </Tag>
                                </td>

                                <td>
                                    <div class="rounded-lg border border-gray-200 bg-gray-50/60 p-3 dark:border-gray-700 dark:bg-gray-900/40">
                                        <template v-if="parameter.control_type === 'in'">
                                            <Input
                                                v-model:value="parameter.value_default"
                                                @pressEnter="updateDefaultValue(parameter.id, parameter.value_default)"
                                            />
                                            <p class="mt-2 text-xs text-gray-500">Enter para guardar</p>
                                        </template>

                                        <template v-else-if="parameter.control_type === 'sq'">
                                            <Select
                                                v-model:value="parameter.value_default"
                                                class="w-full"
                                                show-search
                                                option-filter-prop="label"
                                                :options="parameter.options"
                                                @change="updateDefaultValue(parameter.id, parameter.value_default)"
                                            />
                                        </template>

                                        <template v-else-if="parameter.control_type === 'sa'">
                                            <Select
                                                v-model:value="parameter.value_default"
                                                class="w-full"
                                                :options="parameter.options"
                                                @change="updateDefaultValue(parameter.id, parameter.value_default)"
                                            />
                                        </template>

                                        <template v-else-if="parameter.control_type === 'tx'">
                                            <Textarea
                                                v-model:value="parameter.value_default"
                                                class="w-full"
                                                show-count
                                                :maxlength="5000"
                                                :rows="4"
                                                @blur="updateDefaultValue(parameter.id, parameter.value_default)"
                                            />
                                            <p class="mt-2 text-xs text-gray-500">Se guarda al salir del campo</p>
                                        </template>

                                        <template v-else-if="parameter.control_type === 'rdj'">
                                            <div class="space-y-2">
                                                <label
                                                    v-for="option in parameter.options"
                                                    :key="`${parameter.id}-rdj-${option.value}`"
                                                    class="flex cursor-pointer items-start gap-2 rounded-md px-2 py-1 hover:bg-white dark:hover:bg-gray-800"
                                                >
                                                    <input
                                                        v-model="parameter.value_default"
                                                        type="radio"
                                                        :value="option.value"
                                                        :name="`radio-j-${index}`"
                                                        class="form-radio mt-1"
                                                        @change="updateDefaultValue(parameter.id, parameter.value_default)"
                                                    />
                                                    <span class="text-sm">{{ option.label }}</span>
                                                </label>
                                            </div>
                                        </template>

                                        <template v-else-if="parameter.control_type === 'chj'">
                                            <Checkbox.Group
                                                :value="selectedMulti[parameter.id]"
                                                class="parameter-checkbox-list"
                                                @change="(values) => onMultiChange(parameter, values)"
                                            >
                                                <Checkbox
                                                    v-for="option in parameter.options"
                                                    :key="`${parameter.id}-chj-${option.value}`"
                                                    :value="option.value"
                                                >
                                                    {{ option.label }}
                                                </Checkbox>
                                            </Checkbox.Group>
                                            <p class="mt-2 text-xs text-gray-500">
                                                Seleccionados: {{ (selectedMulti[parameter.id] || []).join(', ') || 'ninguno' }}
                                            </p>
                                        </template>

                                        <template v-else-if="parameter.control_type === 'chq'">
                                            <Checkbox.Group
                                                :value="selectedMulti[parameter.id]"
                                                class="parameter-checkbox-list parameter-checkbox-list--scroll"
                                                @change="(values) => onMultiChange(parameter, values)"
                                            >
                                                <Checkbox
                                                    v-for="option in parameter.options"
                                                    :key="`${parameter.id}-chq-${option.value}`"
                                                    :value="option.value"
                                                >
                                                    <span class="font-medium">{{ option.label }}</span>
                                                    <span class="ml-1 text-xs text-gray-400">({{ option.value }})</span>
                                                </Checkbox>
                                            </Checkbox.Group>
                                            <p v-if="!parameter.options?.length" class="text-sm text-danger">
                                                No hay opciones. Revisa la consulta SQL o JSON del parámetro.
                                            </p>
                                            <p class="mt-2 text-xs text-gray-500">
                                                Guardado como JSON:
                                                <code class="rounded bg-white px-1 py-0.5 dark:bg-gray-800">{{ JSON.stringify(selectedMulti[parameter.id] || []) }}</code>
                                            </p>
                                        </template>

                                        <template v-else-if="parameter.control_type === 'chx'">
                                            <label class="relative inline-flex h-6 w-12 cursor-pointer items-center">
                                                <input
                                                    type="checkbox"
                                                    class="peer sr-only"
                                                    :checked="isTruthyCheckbox(parameter.value_default)"
                                                    @change="onToggleSwitch(parameter, $event.target.checked)"
                                                />
                                                <span class="block h-full w-full rounded-full bg-gray-300 transition peer-checked:bg-primary dark:bg-gray-600"></span>
                                                <span class="absolute left-1 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-6"></span>
                                            </label>
                                            <p class="mt-2 text-xs text-gray-500">
                                                {{ isTruthyCheckbox(parameter.value_default) ? 'Activado' : 'Desactivado' }}
                                            </p>
                                        </template>

                                        <template v-else>
                                            <Input
                                                v-model:value="parameter.value_default"
                                                @pressEnter="updateDefaultValue(parameter.id, parameter.value_default)"
                                            />
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.parameter-checkbox-list {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    gap: 0.25rem;
    width: 100%;
}

.parameter-checkbox-list :deep(.ant-checkbox-wrapper) {
    display: flex !important;
    align-items: flex-start;
    width: 100%;
    margin-inline-start: 0 !important;
    padding: 0.5rem 0.625rem;
    border-radius: 0.375rem;
    border: 1px solid transparent;
    line-height: 1.4;
}

.parameter-checkbox-list :deep(.ant-checkbox-wrapper:hover) {
    border-color: rgb(229 231 235);
    background: rgb(255 255 255);
}

:root.dark .parameter-checkbox-list :deep(.ant-checkbox-wrapper:hover) {
    border-color: rgb(55 65 81);
    background: rgb(31 41 55);
}

.parameter-checkbox-list :deep(.ant-checkbox + span) {
    white-space: normal;
    word-break: break-word;
}

.parameter-checkbox-list--scroll {
    max-height: 14rem;
    overflow-y: auto;
    overflow-x: hidden;
    padding-right: 0.25rem;
}
</style>
