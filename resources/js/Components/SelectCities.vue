<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: null,
    },
    description: {
        type: String,
        default: '',
    },
    options: {
        type: [Array, Object],
        default: () => [],
    },
    label: {
        type: String,
        default: 'Ciudad',
    },
    required: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Buscar distrito, provincia o región',
    },
    emptyHint: {
        type: String,
        default: 'Escribe el distrito, la provincia o la región.',
    },
    noResultsText: {
        type: String,
        default: 'No encontramos distritos con ese nombre.',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    maxResults: {
        type: Number,
        default: 10,
    },
    separator: {
        type: String,
        default: '-',
    },
    id: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue', 'update:description', 'select']);

const uid = `select-cities-${Math.random().toString(36).slice(2, 9)}`;
const inputId = computed(() => props.id || uid);
const listId = computed(() => `${inputId.value}-list`);

const root = ref(null);
const inputRef = ref(null);
const query = ref(props.description ?? '');
const isOpen = ref(false);
const activeIndex = ref(-1);

const normalizeText = (value) => String(value ?? '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim();

const optionList = computed(() => (
    Array.isArray(props.options)
        ? props.options
        : Object.values(props.options ?? {})
));

const normalizedOptions = computed(() => optionList.value.map((option, index) => {
    const department = option?.department_name ?? option?.department ?? option?.region ?? '';
    const province = option?.province_name ?? option?.province ?? '';
    const district = option?.district_name ?? option?.district ?? option?.name ?? '';
    const description = option?.ubigeo_description
        ?? option?.city_name
        ?? option?.name_city
        ?? option?.city
        ?? [department, province, district].filter(Boolean).join(props.separator);
    const value = option?.district_id ?? option?.id ?? option?.value ?? '';

    return {
        key: `${value || 'option'}-${index}`,
        value,
        description,
        district: district || description,
        province,
        department,
        search: normalizeText([
            description,
            district,
            province,
            department,
        ].filter(Boolean).join(' ')),
        raw: option,
    };
}));

const searchTerm = computed(() => normalizeText(query.value));

const matches = computed(() => {
    if (!searchTerm.value) {
        return [];
    }

    const tokens = searchTerm.value.split(/\s+/).filter(Boolean);

    return normalizedOptions.value
        .filter((option) => tokens.every((token) => option.search.includes(token)))
        .sort((a, b) => {
            const rank = (option) => {
                const district = normalizeText(option.district);
                const description = normalizeText(option.description);

                if (district === searchTerm.value) {
                    return 0;
                }

                if (district.startsWith(searchTerm.value)) {
                    return 1;
                }

                if (description.startsWith(searchTerm.value)) {
                    return 2;
                }

                return 3;
            };

            return rank(a) - rank(b)
                || a.district.localeCompare(b.district, 'es')
                || a.description.localeCompare(b.description, 'es');
        });
});

const visibleOptions = computed(() => matches.value.slice(0, props.maxResults));

const totalMatches = computed(() => matches.value.length);

const isSelected = (option) => (
    props.modelValue !== null
    && props.modelValue !== undefined
    && props.modelValue !== ''
    && String(option.value) === String(props.modelValue)
);

const hasSelection = computed(() => (
    props.modelValue !== null
    && props.modelValue !== undefined
    && props.modelValue !== ''
));

const optionId = (index) => `${inputId.value}-option-${index}`;

const activeDescendant = computed(() => {
    if (!isOpen.value || activeIndex.value < 0 || !visibleOptions.value[activeIndex.value]) {
        return undefined;
    }

    return optionId(activeIndex.value);
});

const open = () => {
    if (props.disabled) {
        return;
    }

    isOpen.value = true;
};

const close = () => {
    isOpen.value = false;
    activeIndex.value = -1;
};

const scrollActiveIntoView = () => {
    nextTick(() => {
        const option = document.getElementById(optionId(activeIndex.value));

        option?.scrollIntoView({ block: 'nearest' });
    });
};

const onInput = (event) => {
    const value = event.target.value;
    query.value = value;
    isOpen.value = true;
    activeIndex.value = 0;
    emit('update:description', value);

    // Si el alumno cambia el texto, el distrito anterior deja de ser válido.
    if (props.modelValue !== null && props.modelValue !== undefined && props.modelValue !== '') {
        emit('update:modelValue', null);
    }
};

const onFocus = () => open();

const onKeydown = (event) => {
    if (props.disabled) {
        return;
    }

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        open();

        if (visibleOptions.value.length) {
            activeIndex.value = Math.min(activeIndex.value + 1, visibleOptions.value.length - 1);
            scrollActiveIntoView();
        }

        return;
    }

    if (event.key === 'ArrowUp') {
        event.preventDefault();
        open();

        if (visibleOptions.value.length) {
            activeIndex.value = Math.max(activeIndex.value - 1, 0);
            scrollActiveIntoView();
        }

        return;
    }

    if (event.key === 'Enter') {
        if (!isOpen.value && searchTerm.value) {
            event.preventDefault();
            open();
            return;
        }

        if (isOpen.value) {
            event.preventDefault();

            const option = visibleOptions.value[activeIndex.value];

            if (option) {
                selectOption(option);
            }
        }

        return;
    }

    if (event.key === 'Escape' && isOpen.value) {
        event.preventDefault();
        event.stopPropagation();
        close();
        return;
    }

    if (event.key === 'Tab') {
        close();
    }
};

const selectOption = (option) => {
    query.value = option.description;
    emit('update:description', option.description);
    emit('update:modelValue', option.value);
    emit('select', {
        value: option.value,
        description: option.description,
        option: option.raw,
    });
    close();
};

const clear = () => {
    query.value = '';
    emit('update:description', '');
    emit('update:modelValue', null);
    close();
};

const onDocumentMouseDown = (event) => {
    if (root.value && !root.value.contains(event.target)) {
        close();
    }
};

watch(() => props.description, (value) => {
    const next = value ?? '';

    if (next !== query.value) {
        query.value = next;
    }
});

watch(() => props.modelValue, (value) => {
    const selected = normalizedOptions.value.find((option) => String(option.value) === String(value));

    if (selected && !query.value) {
        query.value = selected.description;
    }
}, { immediate: true });

// Cuando el catálogo llega después del montaje (props asíncronas), completa
// el texto con la ciudad ya seleccionada para no dejar el campo vacío.
watch(normalizedOptions, (options) => {
    if (query.value || !hasSelection.value) {
        return;
    }

    const selected = options.find((option) => String(option.value) === String(props.modelValue));

    if (selected) {
        query.value = selected.description;
    }
});

watch(visibleOptions, (options) => {
    if (!options.length) {
        activeIndex.value = -1;
        return;
    }

    const selectedIndex = options.findIndex((option) => isSelected(option));
    activeIndex.value = selectedIndex >= 0 ? selectedIndex : 0;
}, { immediate: true });

onMounted(() => {
    document.addEventListener('mousedown', onDocumentMouseDown);
});

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', onDocumentMouseDown);
});

defineExpose({
    focus: () => inputRef.value?.focus(),
    clear,
});
</script>

<template>
    <div ref="root" class="select-cities">
        <InputLabel v-if="label" :for="inputId">
            {{ label }}<span v-if="required" class="select-cities__required" aria-hidden="true">*</span>
        </InputLabel>

        <div class="select-cities__control">
            <span class="select-cities__icon" aria-hidden="true">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="7" />
                    <path stroke-linecap="round" d="m20 20-3.2-3.2" />
                </svg>
            </span>

            <input
                :id="inputId"
                ref="inputRef"
                :value="query"
                type="text"
                class="form-input select-cities__input"
                :class="{
                    'select-cities__input--error': error,
                    'select-cities__input--selected': hasSelection && !error,
                }"
                :placeholder="placeholder"
                :disabled="disabled"
                role="combobox"
                aria-autocomplete="list"
                :aria-required="required"
                :aria-invalid="error ? 'true' : undefined"
                :aria-expanded="isOpen"
                :aria-controls="listId"
                :aria-activedescendant="activeDescendant"
                autocomplete="off"
                @input="onInput"
                @focus="onFocus"
                @keydown="onKeydown"
            />

            <button
                v-if="query && !disabled"
                type="button"
                class="select-cities__clear"
                aria-label="Limpiar ciudad"
                @mousedown.prevent
                @click="clear"
            >
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" d="m6 6 12 12M18 6 6 18" />
                </svg>
            </button>

            <span v-else class="select-cities__chevron" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                </svg>
            </span>
        </div>

        <div
            v-if="isOpen"
            :id="listId"
            class="select-cities__panel"
            role="listbox"
            :aria-label="label"
        >
                <p v-if="!searchTerm" class="select-cities__hint">
                    {{ emptyHint }}
                </p>

                <template v-else-if="visibleOptions.length">
                    <button
                        v-for="(option, index) in visibleOptions"
                        :id="optionId(index)"
                        :key="option.key"
                        type="button"
                        role="option"
                        tabindex="-1"
                        class="select-cities__option"
                        :class="{
                            'is-active': index === activeIndex,
                            'is-selected': isSelected(option),
                        }"
                        :aria-selected="isSelected(option)"
                        @mouseenter="activeIndex = index"
                        @mousedown.prevent
                        @click="selectOption(option)"
                    >
                        <span class="select-cities__mark" aria-hidden="true">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s6-5.3 6-11a6 6 0 1 0-12 0c0 5.7 6 11 6 11Z" />
                                <circle cx="12" cy="10" r="2.2" />
                            </svg>
                        </span>

                        <span class="select-cities__copy">
                            <strong>{{ option.district }}</strong>
                            <small v-if="option.province || option.department">
                                {{ [option.province, option.department].filter(Boolean).join(' · ') }}
                            </small>
                        </span>

                        <svg
                            v-if="isSelected(option)"
                            class="select-cities__check"
                            width="17"
                            height="17"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.4"
                            aria-hidden="true"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 5 5L20 7" />
                        </svg>
                    </button>

                    <p v-if="totalMatches > visibleOptions.length" class="select-cities__more">
                        Mostrando {{ visibleOptions.length }} de {{ totalMatches }} coincidencias.
                        Escribe más para afinar la búsqueda.
                    </p>
                </template>

            <p v-else class="select-cities__empty">
                {{ noResultsText }}
            </p>
        </div>

        <InputError :message="error" class="mt-2" />
    </div>
</template>

<style scoped>
.select-cities {
    --sc-input-bg: #ffffff;
    --sc-input-border: #d1d5db;
    --sc-input-text: #111827;
    --sc-muted: #6b7280;
    --sc-accent: #4361ee;
    --sc-ring: rgb(67 97 238 / 20%);
    --sc-panel-bg: #ffffff;
    --sc-panel-border: #e5e7eb;
    --sc-panel-text: #111827;
    --sc-panel-muted: #6b7280;
    --sc-option-hover: #f3f4f6;
    --sc-option-active: #eef2ff;
    --sc-option-selected: #ecfdf5;
    --sc-option-selected-text: #065f46;
    --sc-option-selected-border: #a7f3d0;

    position: relative;
    width: 100%;
}

.select-cities__required {
    margin-left: 0.18rem;
    color: #ef4444;
}

.select-cities__control {
    position: relative;
    margin-top: 0.3rem;
}

.select-cities__icon,
.select-cities__chevron {
    position: absolute;
    top: 50%;
    display: grid;
    place-items: center;
    transform: translateY(-50%);
    color: var(--sc-muted);
    pointer-events: none;
}

.select-cities__icon {
    left: 0.85rem;
}

.select-cities__chevron {
    right: 0.8rem;
}

.select-cities__input {
    width: 100%;
    min-height: 2.85rem;
    padding-left: 2.55rem;
    padding-right: 2.6rem;
    border-color: var(--sc-input-border);
    border-radius: 0.75rem;
    background-color: var(--sc-input-bg);
    color: var(--sc-input-text);
    font-weight: 500;
    transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
}

.select-cities__input::placeholder {
    color: var(--sc-muted);
    font-weight: 400;
}

.select-cities__input--selected {
    border-color: #34d399;
}

.select-cities__input:focus {
    border-color: var(--sc-accent);
    outline: none;
    box-shadow: 0 0 0 3px var(--sc-ring);
}

.select-cities__input--error {
    border-color: #ef4444;
}

.select-cities__input--error:focus {
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgb(239 68 68 / 18%);
}

.select-cities__clear {
    position: absolute;
    top: 50%;
    right: 0.55rem;
    display: grid;
    place-items: center;
    width: 1.9rem;
    height: 1.9rem;
    border-radius: 9999px;
    color: var(--sc-muted);
    transform: translateY(-50%);
    transition: background-color 0.15s ease, color 0.15s ease;
}

.select-cities__clear:hover,
.select-cities__clear:focus-visible {
    background-color: var(--sc-option-hover);
    color: var(--sc-panel-text);
    outline: none;
}

.select-cities__panel {
    position: absolute;
    z-index: 60;
    left: 0;
    right: 0;
    max-height: 19rem;
    margin-top: 0.4rem;
    padding: 0.35rem;
    overflow-y: auto;
    border: 1px solid var(--sc-panel-border);
    border-radius: 0.9rem;
    background-color: var(--sc-panel-bg);
    color: var(--sc-panel-text);
    box-shadow: 0 18px 45px rgb(15 23 42 / 18%);
}

.select-cities__option {
    display: flex;
    width: 100%;
    min-height: 2.9rem;
    align-items: center;
    gap: 0.65rem;
    padding: 0.5rem 0.6rem;
    border-radius: 0.7rem;
    color: var(--sc-panel-text);
    text-align: left;
    transition: background-color 0.13s ease, color 0.13s ease, box-shadow 0.13s ease;
}

.select-cities__option.is-active {
    background-color: var(--sc-option-active);
}

.select-cities__option.is-selected {
    background-color: var(--sc-option-selected);
    color: var(--sc-option-selected-text);
    box-shadow: inset 0 0 0 1px var(--sc-option-selected-border);
}

.select-cities__option.is-selected.is-active {
    box-shadow: inset 0 0 0 1px var(--sc-option-selected-border);
}

.select-cities__mark {
    display: grid;
    place-items: center;
    width: 1.85rem;
    height: 1.85rem;
    flex: 0 0 auto;
    border-radius: 0.6rem;
    background-color: var(--sc-option-hover);
    color: var(--sc-accent);
}

.select-cities__option.is-selected .select-cities__mark {
    background-color: var(--sc-option-selected-border);
    color: var(--sc-option-selected-text);
}

.select-cities__copy {
    display: flex;
    min-width: 0;
    flex: 1 1 auto;
    flex-direction: column;
    line-height: 1.2;
}

.select-cities__copy strong {
    overflow: hidden;
    font-size: 0.88rem;
    font-weight: 650;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.select-cities__copy small {
    margin-top: 0.15rem;
    overflow: hidden;
    color: var(--sc-panel-muted);
    font-size: 0.72rem;
    font-weight: 500;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.select-cities__option.is-selected .select-cities__copy small {
    color: var(--sc-option-selected-text);
    opacity: 0.78;
}

.select-cities__check {
    flex: 0 0 auto;
    color: var(--sc-option-selected-text);
}

.select-cities__hint,
.select-cities__empty,
.select-cities__more {
    padding: 0.6rem 0.7rem;
    color: var(--sc-panel-muted);
    font-size: 0.78rem;
    line-height: 1.45;
}

.select-cities__more {
    border-top: 1px solid var(--sc-panel-border);
    margin-top: 0.15rem;
}

.select-cities__empty {
    color: var(--sc-panel-text);
}

.select-cities__panel {
    animation: select-cities-in 0.14s ease;
}

@keyframes select-cities-in {
    from {
        opacity: 0;
        transform: translateY(-4px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

body.dark .select-cities {
    --sc-input-bg: #121e32;
    --sc-input-border: #1b2e4b;
    --sc-input-text: #e2e8f0;
    --sc-muted: #94a3b8;
    --sc-accent: #818cf8;
    --sc-ring: rgb(129 140 248 / 22%);
    --sc-panel-bg: #0e1726;
    --sc-panel-border: #1b2e4b;
    --sc-panel-text: #e2e8f0;
    --sc-panel-muted: #94a3b8;
    --sc-option-hover: #1b2e4b;
    --sc-option-active: #17263c;
    --sc-option-selected: #064e3b;
    --sc-option-selected-text: #d1fae5;
    --sc-option-selected-border: #10b981;
}

html.theme-torneos .select-cities {
    --sc-input-bg: var(--torneos-form-field);
    --sc-input-border: var(--torneos-form-field-border);
    --sc-input-text: var(--torneos-form-field-text);
    --sc-muted: var(--torneos-form-field-placeholder);
    --sc-accent: var(--torneos-lime);
    --sc-ring: rgb(184 243 74 / 22%);
    --sc-panel-bg: var(--torneos-dropdown-bg);
    --sc-panel-border: var(--torneos-dropdown-border);
    --sc-panel-text: var(--torneos-dropdown-text);
    --sc-panel-muted: var(--torneos-panel-muted-text);
    --sc-option-hover: var(--torneos-dropdown-option-hover);
    --sc-option-active: var(--torneos-dropdown-option-hover);
    --sc-option-selected: var(--torneos-dropdown-option-selected);
    --sc-option-selected-text: var(--torneos-dropdown-option-selected-text);
    --sc-option-selected-border: var(--torneos-dropdown-border);
}

html.theme-torneos .select-cities__panel {
    box-shadow: var(--torneos-dropdown-shadow);
}

@media (max-width: 640px) {
    .select-cities__panel {
        max-height: 15.5rem;
    }

    .select-cities__option {
        min-height: 3rem;
    }
}

@media (prefers-reduced-motion: reduce) {
    .select-cities__input,
    .select-cities__clear,
    .select-cities__option {
        transition: none;
    }

    .select-cities__panel {
        animation: none;
    }
}
</style>
