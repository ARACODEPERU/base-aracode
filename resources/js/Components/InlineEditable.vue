<script setup>
    import { ref, computed } from 'vue';
    import { Popover } from 'ant-design-vue';
    import IconX from './vristo/icon/icon-x.vue';
    import IconCheck from './vristo/icon/icon-check.vue';
    import iconLoader from './vristo/icon/icon-loader.vue';

    const props = defineProps({
        modelValue: String,
        placeholder: {
            type: String,
            default: 'Click para editar'
        },
        title: {
            type: String,
            default: 'Editar'
        },
        element: {
            type: String,
            default: 'input'
        },
        data: {
            type: [Object, Array],
            default: () => []
        },
        loading: {
            type: Boolean,
            default: false
        }
    });

    const options = computed(() => {
        if (!Array.isArray(props.data)) return [];

        // ['Capitán', 'Sub-Capitán']
        if (typeof props.data[0] === 'string') {
            return props.data.map(value => ({
                value,
                label: value
            }));
        }

        // [{id:1,name:'hola'}]
        return props.data.map(item => ({
            value: item.id,
            label: item.name
        }));
    });

    const emit = defineEmits(['update:modelValue', 'save']);

    const editing = ref(false);
    const model = ref(props.modelValue);
    const placement = ref('bottom');
    const triggerRef = ref(null);

    const startEdit = () => {
        editing.value = true;
        model.value = props.modelValue;
    };

    const save = () => {
        //editing.value = false;
        emit('update:modelValue', model.value);
        emit('save', model.value);
    };

    const cancel = () => {
        editing.value = false;
        model.value = props.modelValue;
    };

    const calculatePlacement = () => {
        if (!triggerRef.value) return;

        const rect = triggerRef.value.getBoundingClientRect();
        const windowWidth = window.innerWidth;

        // Margen de seguridad
        const threshold = 150;

        if (rect.left < threshold) {
            placement.value = 'bottomLeft';
        } else if (windowWidth - rect.right < threshold) {
            placement.value = 'bottomRight';
        } else {
            placement.value = 'bottom';
        }
    };
</script>
<template>
    <div class="inline-editable">
        <Popover
            v-model:open="editing"
            trigger="click"
            :placement="placement"
        >
            <template #title>
                <div>
                    <h6 class="text-sm font-bold text-heading">{{ title }}</h6>
                </div>
            </template>
            <template #content>
                <div class="flex items-center gap-2">
                    <input
                        v-if="element == 'input'"
                        v-model="model"
                        class="form-input"
                        type="text"
                    />
                    <select
                        v-if="element == 'select'"
                        class="form-select"
                        v-model="model"
                    >
                        <option
                            v-for="opt in options"
                            :key="opt.value"
                            :value="opt.value"
                        >
                            {{ opt.label }}
                        </option>
                    </select>
                    <button @click="save" type="button">
                        <IconCheck v-if="!loading" class="w-5 h-5" />
                        <iconLoader v-else class="w-5 h-5" />
                    </button>
                    <button @click="cancel" type="button"><IconX class="w-5 h-5" /></button>
                </div>
            </template>
            <span
                class="cursor-pointer hover:bg-gray-100 p-2 rounded-lg"
                ref="triggerRef"
                @click="calculatePlacement"
            >
                {{ model || placeholder }}
            </span>
        </Popover>
    </div>
</template>

