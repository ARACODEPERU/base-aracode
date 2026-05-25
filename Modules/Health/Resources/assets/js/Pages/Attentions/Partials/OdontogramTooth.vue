<script setup>
import { computed } from 'vue';

const props = defineProps({
    tooth: {
        type: Object,
        required: true,
    },
    modelValue: {
        type: Object,
        default: () => ({}),
    },
    condition: {
        type: String,
        default: 'caries',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);

const centerSurface = computed(() => {
    return props.tooth.group === 'posterior' ? 'oclusal' : 'incisal';
});

const innerSurface = computed(() => {
    return props.tooth.arch === 'upper' ? 'palatino' : 'lingual';
});

const isRightSide = computed(() => {
    return [1, 4, 5, 8].includes(Math.floor(Number(props.tooth.number) / 10));
});

const surfaceByPosition = computed(() => {
    return {
        top: { key: 'vestibular', label: 'V' },
        bottom: { key: innerSurface.value, label: innerSurface.value === 'palatino' ? 'P' : 'L' },
        left: isRightSide.value ? { key: 'distal', label: 'D' } : { key: 'mesial', label: 'M' },
        right: isRightSide.value ? { key: 'mesial', label: 'M' } : { key: 'distal', label: 'D' },
        center: { key: centerSurface.value, label: centerSurface.value === 'oclusal' ? 'O' : 'I' },
    };
});

const conditionClasses = {
    caries: 'surface-caries',
    obturado: 'surface-obturado',
    filtracion: 'surface-filtracion',
};

const fullToothConditions = ['extraido', 'extraccion'];

const fullToothStatus = computed(() => {
    return fullToothConditions.includes(props.modelValue?.status) ? props.modelValue.status : null;
});

const hasEndodonticRoot = computed(() => {
    return props.modelValue?.root_status === 'endodontic';
});

const needsEndodonticRoot = computed(() => {
    return props.modelValue?.root_status === 'endodontic_required';
});

const rootStatus = computed(() => {
    return props.modelValue?.root_status || null;
});

const rootPath = computed(() => {
    return 'M23 64 C23 80 27 92 32 100 C37 92 41 80 41 64 Z';
});

const rootLinePath = computed(() => {
    return 'M32 66 L32 96';
});

const isRootCondition = computed(() => {
    return ['endodontic', 'endodontic_required'].includes(props.condition);
});

const surfaceClass = (key) => {
    const value = props.modelValue?.surfaces?.[key];
    return conditionClasses[value] || 'surface-empty';
};

const surfaceFill = (key) => {
    return props.modelValue?.surfaces?.[key] === 'filtracion'
        ? { fill: `url(#filtracion-${props.tooth.number}-${key})` }
        : null;
};

const toggleSurface = (surface) => {
    if (props.disabled) {
        return;
    }

    if (isRootCondition.value) {
        toggleEndodonticRoot();
        return;
    }

    if (fullToothConditions.includes(props.condition)) {
        emit('update:modelValue', {
            ...(props.modelValue || {}),
            status: props.modelValue?.status === props.condition ? null : props.condition,
            surfaces: {},
        });
        return;
    }

    const current = props.modelValue?.surfaces?.[surface.key];
    const nextSurfaces = {
        ...(props.modelValue?.surfaces || {}),
        [surface.key]: current === props.condition ? null : props.condition,
    };

    if (!nextSurfaces[surface.key]) {
        delete nextSurfaces[surface.key];
    }

    emit('update:modelValue', {
        ...(props.modelValue || {}),
        status: null,
        surfaces: nextSurfaces,
    });
};

const toggleEndodonticRoot = () => {
    if (props.disabled || !isRootCondition.value) {
        return;
    }

    const nextStatus = rootStatus.value === props.condition ? null : props.condition;
    const nextValue = {
        ...(props.modelValue || {}),
        root_status: nextStatus,
    };

    if (!nextValue.root_status) {
        delete nextValue.root_status;
    }

    emit('update:modelValue', nextValue);
};
</script>

<template>
    <div class="w-[72px] shrink-0">
        <div class="text-center text-xs font-bold text-slate-700 dark:text-slate-200 mb-1">
            {{ tooth.number }}
        </div>
        <svg viewBox="0 0 64 102" class="mx-auto block h-[88px] w-14">
            <defs>
                <pattern
                    v-for="surface in ['vestibular', 'mesial', 'distal', 'palatino', 'lingual', 'oclusal', 'incisal']"
                    :id="`filtracion-${tooth.number}-${surface}`"
                    :key="surface"
                    patternUnits="userSpaceOnUse"
                    width="8"
                    height="8"
                >
                    <rect width="4" height="4" fill="#e7515a" />
                    <rect x="4" width="4" height="4" fill="#4361ee" />
                    <rect y="4" width="4" height="4" fill="#4361ee" />
                    <rect x="4" y="4" width="4" height="4" fill="#e7515a" />
                </pattern>
            </defs>
            <g>
            <path
                d="M12 12 A28 28 0 0 1 52 12 L40 24 A12 12 0 0 0 24 24 Z"
                class="surface transition-colors"
                :class="[surfaceClass(surfaceByPosition.top.key), disabled ? 'cursor-not-allowed opacity-80' : 'cursor-pointer']"
                :style="surfaceFill(surfaceByPosition.top.key)"
                :aria-label="surfaceByPosition.top.key"
                @click="toggleSurface(surfaceByPosition.top)"
            />
            <path
                d="M52 12 A28 28 0 0 1 52 52 L40 40 A12 12 0 0 0 40 24 Z"
                class="surface transition-colors"
                :class="[surfaceClass(surfaceByPosition.right.key), disabled ? 'cursor-not-allowed opacity-80' : 'cursor-pointer']"
                :style="surfaceFill(surfaceByPosition.right.key)"
                :aria-label="surfaceByPosition.right.key"
                @click="toggleSurface(surfaceByPosition.right)"
            />
            <path
                d="M52 52 A28 28 0 0 1 12 52 L24 40 A12 12 0 0 0 40 40 Z"
                class="surface transition-colors"
                :class="[surfaceClass(surfaceByPosition.bottom.key), disabled ? 'cursor-not-allowed opacity-80' : 'cursor-pointer']"
                :style="surfaceFill(surfaceByPosition.bottom.key)"
                :aria-label="surfaceByPosition.bottom.key"
                @click="toggleSurface(surfaceByPosition.bottom)"
            />
            <path
                d="M12 52 A28 28 0 0 1 12 12 L24 24 A12 12 0 0 0 24 40 Z"
                class="surface transition-colors"
                :class="[surfaceClass(surfaceByPosition.left.key), disabled ? 'cursor-not-allowed opacity-80' : 'cursor-pointer']"
                :style="surfaceFill(surfaceByPosition.left.key)"
                :aria-label="surfaceByPosition.left.key"
                @click="toggleSurface(surfaceByPosition.left)"
            />
            <circle
                cx="32"
                cy="32"
                r="12"
                class="surface transition-colors"
                :class="[surfaceClass(surfaceByPosition.center.key), disabled ? 'cursor-not-allowed opacity-80' : 'cursor-pointer']"
                :style="surfaceFill(surfaceByPosition.center.key)"
                :aria-label="surfaceByPosition.center.key"
                @click="toggleSurface(surfaceByPosition.center)"
            />
            <circle cx="32" cy="32" r="28" class="outer-ring pointer-events-none" />
            <text x="32" y="14" text-anchor="middle" class="fill-current text-[8px] pointer-events-none text-slate-700 dark:text-slate-100">{{ surfaceByPosition.top.label }}</text>
            <text x="50" y="35" text-anchor="middle" class="fill-current text-[8px] pointer-events-none text-slate-700 dark:text-slate-100">{{ surfaceByPosition.right.label }}</text>
            <text x="32" y="54" text-anchor="middle" class="fill-current text-[8px] pointer-events-none text-slate-700 dark:text-slate-100">{{ surfaceByPosition.bottom.label }}</text>
            <text x="14" y="35" text-anchor="middle" class="fill-current text-[8px] pointer-events-none text-slate-700 dark:text-slate-100">{{ surfaceByPosition.left.label }}</text>
            <text x="32" y="35" text-anchor="middle" class="fill-current text-[9px] font-bold pointer-events-none text-slate-700 dark:text-slate-100">{{ surfaceByPosition.center.label }}</text>
            <g
                v-if="fullToothStatus"
                class="pointer-events-none"
                :class="fullToothStatus === 'extraido' ? 'tooth-x-extraido' : 'tooth-x-extraccion'"
            >
                <line x1="7" y1="7" x2="57" y2="57" class="tooth-x-line" />
                <line x1="57" y1="7" x2="7" y2="57" class="tooth-x-line" />
            </g>
            </g>
            <path
                :d="rootPath"
                class="root-shape transition-colors"
                :class="[
                    hasEndodonticRoot ? 'root-endodontic' : needsEndodonticRoot ? 'root-endodontic-required' : 'root-empty',
                    disabled || !isRootCondition ? 'cursor-default opacity-80' : 'cursor-pointer',
                ]"
                @click="toggleEndodonticRoot"
            />
            <path
                :d="rootLinePath"
                class="root-line pointer-events-none"
                :class="hasEndodonticRoot ? 'root-line-endodontic' : needsEndodonticRoot ? 'root-line-endodontic-required' : 'root-line-empty'"
            />
        </svg>
    </div>
</template>

<style scoped>
.surface {
    stroke: #111827;
    stroke-width: 1.35;
    vector-effect: non-scaling-stroke;
}

.dark .surface {
    stroke: #f8fafc;
}

.outer-ring {
    fill: none;
    stroke: #111827;
    stroke-width: 1.6;
    vector-effect: non-scaling-stroke;
}

.dark .outer-ring {
    stroke: #f8fafc;
}

.surface-empty {
    fill: #ffffff;
}

.dark .surface-empty {
    fill: #0f172a;
}

.surface-caries {
    fill: #e7515a;
}

.surface-obturado {
    fill: #4361ee;
}

.surface-filtracion {
    fill: #e7515a;
}

.root-shape {
    stroke-width: 1.8;
    vector-effect: non-scaling-stroke;
}

.root-empty {
    fill: #ffffff;
    stroke: #94a3b8;
}

.dark .root-empty {
    fill: #0f172a;
    stroke: #cbd5e1;
}

.root-endodontic {
    fill: rgba(128, 93, 202, 0.24);
    stroke: #805dca;
}

.root-endodontic-required {
    fill: rgba(231, 81, 90, 0.24);
    stroke: #e7515a;
}

.root-line {
    fill: none;
    stroke-width: 2.3;
    stroke-linecap: round;
    vector-effect: non-scaling-stroke;
}

.root-line-empty {
    stroke: #94a3b8;
}

.dark .root-line-empty {
    stroke: #cbd5e1;
}

.root-line-endodontic {
    stroke: #805dca;
}

.root-line-endodontic-required {
    stroke: #e7515a;
}

.tooth-x-line {
    fill: none;
    stroke-width: 5;
    stroke-linecap: round;
    vector-effect: non-scaling-stroke;
}

.tooth-x-extraido .tooth-x-line {
    stroke: #4361ee;
}

.tooth-x-extraccion .tooth-x-line {
    stroke: #e7515a;
}
</style>
