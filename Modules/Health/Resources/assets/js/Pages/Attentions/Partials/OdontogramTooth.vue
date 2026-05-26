<script setup>
import { computed, ref } from 'vue';

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

const emit = defineEmits(['update:modelValue', 'open-notes']);

const isHovered = ref(false);

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

const fullToothConditions = ['extraido', 'extraccion', 'corona', 'ausente', 'fractura_coronal', 'fractura_radicular', 'fractura_vertical'];

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

const toothPosition = computed(() => Number(props.tooth.number) % 10);
const isUpperArch = computed(() => props.tooth.arch === 'upper');

const rootPath = computed(() => {
    const pos = toothPosition.value;
    const upper = isUpperArch.value;
    
    if (pos >= 6) {
        // Molares (6, 7, 8)
        if (upper) {
            // Molar Superior - 3 raíces fusionadas/trifurcadas
            return 'M16 64 C16 80 19 90 22 96 C24 90 25 80 28 76 C29 80 30 84 32 87 C34 84 35 80 36 76 C39 80 40 90 42 96 C45 90 48 80 48 64 Z';
        } else {
            // Molar Inferior - 2 raíces gruesas/bifurcadas
            return 'M17 64 C17 82 19 92 23 98 C26 90 27 80 32 80 C37 80 38 90 41 98 C45 92 47 82 47 64 Z';
        }
    } else if (pos === 4 || pos === 5) {
        // Premolares (4, 5) - Raíz bifurcada sutilmente
        return 'M19 64 C19 80 23 90 26 96 C27 92 28 88 32 88 C36 88 37 92 38 96 C41 90 45 80 45 64 Z';
    } else {
        // Incisivos y Caninos (1, 2, 3) - Raíz cónica única
        return 'M22 64 C22 80 26 94 32 102 C38 94 42 80 42 64 Z';
    }
});

const rootLinePath = computed(() => {
    const pos = toothPosition.value;
    const upper = isUpperArch.value;

    if (pos >= 6) {
        // Molares (Conductos múltiples)
        if (upper) {
            // Molar superior: 3 conductos
            return 'M22 66 L24 88 M32 66 L32 76 M42 66 L40 88';
        } else {
            // Molar inferior: 2 conductos
            return 'M23 66 L25 88 M41 66 L39 88';
        }
    } else if (pos === 4 || pos === 5) {
        // Premolares: 2 conductos
        return 'M26 66 L28 88 M38 66 L36 88';
    } else {
        // Incisivos/Caninos: 1 conducto central
        return 'M32 66 L32 98';
    }
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

    if (props.condition === 'sano') {
        emit('update:modelValue', {
            surfaces: {},
            status: null,
            root_status: null,
            notes: '',
        });
        return;
    }

    if (props.condition === 'fractura') {
        const currentStatus = props.modelValue?.status;
        let nextStatus = null;
        
        if (currentStatus === 'fractura_coronal') {
            nextStatus = 'fractura_radicular';
        } else if (currentStatus === 'fractura_radicular') {
            nextStatus = 'fractura_vertical';
        } else if (currentStatus === 'fractura_vertical') {
            nextStatus = null;
        } else {
            nextStatus = 'fractura_coronal';
        }
        
        emit('update:modelValue', {
            ...(props.modelValue || {}),
            status: nextStatus,
            surfaces: {},
        });
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
    if (props.disabled) {
        return;
    }

    if (props.condition === 'sano') {
        emit('update:modelValue', {
            surfaces: {},
            status: null,
            root_status: null,
            notes: '',
        });
        return;
    }

    if (props.condition === 'fractura') {
        const currentStatus = props.modelValue?.status;
        let nextStatus = null;
        
        if (currentStatus === 'fractura_coronal') {
            nextStatus = 'fractura_radicular';
        } else if (currentStatus === 'fractura_radicular') {
            nextStatus = 'fractura_vertical';
        } else if (currentStatus === 'fractura_vertical') {
            nextStatus = null;
        } else {
            nextStatus = 'fractura_coronal';
        }
        
        emit('update:modelValue', {
            ...(props.modelValue || {}),
            status: nextStatus,
            surfaces: {},
        });
        return;
    }

    if (!isRootCondition.value) {
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
    <div
        class="w-[72px] shrink-0 relative"
        @mouseenter="isHovered = true"
        @mouseleave="isHovered = false"
    >
        <div class="text-center text-xs font-bold text-slate-700 dark:text-slate-200 mb-1">
            {{ tooth.number }}
        </div>
        <svg viewBox="0 0 64 102" class="mx-auto block h-[88px] w-14 relative">
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
            <g :class="[fullToothStatus === 'ausente' ? 'tooth-ausente' : '']">
                <!-- Surfaces -->
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
                
                <!-- Text Labels -->
                <text x="32" y="14" text-anchor="middle" class="fill-current text-[8px] pointer-events-none text-slate-700 dark:text-slate-100">{{ surfaceByPosition.top.label }}</text>
                <text x="50" y="35" text-anchor="middle" class="fill-current text-[8px] pointer-events-none text-slate-700 dark:text-slate-100">{{ surfaceByPosition.right.label }}</text>
                <text x="32" y="54" text-anchor="middle" class="fill-current text-[8px] pointer-events-none text-slate-700 dark:text-slate-100">{{ surfaceByPosition.bottom.label }}</text>
                <text x="14" y="35" text-anchor="middle" class="fill-current text-[8px] pointer-events-none text-slate-700 dark:text-slate-100">{{ surfaceByPosition.left.label }}</text>
                <text x="32" y="35" text-anchor="middle" class="fill-current text-[9px] font-bold pointer-events-none text-slate-700 dark:text-slate-100">{{ surfaceByPosition.center.label }}</text>
                
                <!-- Extraido / Extraccion Cross -->
                <g
                    v-if="fullToothStatus === 'extraido' || fullToothStatus === 'extraccion'"
                    class="pointer-events-none"
                    :class="fullToothStatus === 'extraido' ? 'tooth-x-extraido' : 'tooth-x-extraccion'"
                >
                    <line x1="7" y1="7" x2="57" y2="57" class="tooth-x-line" />
                    <line x1="57" y1="7" x2="7" y2="57" class="tooth-x-line" />
                </g>

                <!-- Ausente Cross -->
                <g v-if="fullToothStatus === 'ausente'" class="pointer-events-none tooth-ausente-cross">
                    <line x1="7" y1="7" x2="57" y2="57" class="tooth-ausente-line" />
                    <line x1="57" y1="7" x2="7" y2="57" class="tooth-ausente-line" />
                </g>

                <!-- Corona Golden Ring -->
                <circle
                    v-if="fullToothStatus === 'corona'"
                    cx="32"
                    cy="32"
                    r="30"
                    class="tooth-corona pointer-events-none animate-pulse"
                />

            </g>

            <!-- Roots (Endodontic) -->
            <path
                :d="rootPath"
                class="root-shape transition-colors"
                :class="[
                    hasEndodonticRoot ? 'root-endodontic' : needsEndodonticRoot ? 'root-endodontic-required' : 'root-empty',
                    disabled || !isRootCondition ? 'cursor-default opacity-80' : 'cursor-pointer',
                    fullToothStatus === 'ausente' ? 'tooth-ausente' : ''
                ]"
                @click="toggleEndodonticRoot"
            />
            <path
                :d="rootLinePath"
                class="root-line pointer-events-none"
                :class="[
                    hasEndodonticRoot ? 'root-line-endodontic' : needsEndodonticRoot ? 'root-line-endodontic-required' : 'root-line-empty',
                    fullToothStatus === 'ausente' ? 'tooth-ausente' : ''
                ]"
            />

            <!-- Fractura Coronal Zigzag Path -->
            <path
                v-if="fullToothStatus === 'fractura_coronal'"
                d="M 12 32 L 22 28 L 32 36 L 42 28 L 52 32"
                class="tooth-fracture pointer-events-none"
            />

            <!-- Fractura Radicular Zigzag Path -->
            <path
                v-if="fullToothStatus === 'fractura_radicular'"
                d="M 18 80 L 25 76 L 32 84 L 39 76 L 46 80"
                class="tooth-fracture pointer-events-none"
            />

            <!-- Fractura Vertical Zigzag Path -->
            <path
                v-if="fullToothStatus === 'fractura_vertical'"
                d="M 32 10 L 28 26 L 36 44 L 28 62 L 36 80 L 32 98"
                class="tooth-fracture pointer-events-none"
            />

            <!-- Specific Tooth Note Indicator (green dot when tooth has notes) -->
            <circle
                v-if="modelValue?.notes"
                cx="54"
                cy="8"
                r="4.5"
                fill="#10b981"
                class="tooth-note-indicator pointer-events-none"
            />
        </svg>

        <!-- Floating Notes Icon Button (appears on hover) -->
        <transition name="note-icon-fade">
            <button
                v-if="isHovered && !disabled"
                type="button"
                class="tooth-notes-btn"
                :class="modelValue?.notes ? 'has-notes' : ''"
                title="Notas de la pieza"
                @click.stop="emit('open-notes')"
            >
                <!-- Clipboard/Notes SVG icon -->
                <svg viewBox="0 0 24 24" fill="none" class="w-3.5 h-3.5">
                    <path
                        d="M9 2C8.45 2 8 2.45 8 3V4H6C4.9 4 4 4.9 4 6V20C4 21.1 4.9 22 6 22H18C19.1 22 20 21.1 20 20V6C20 4.9 19.1 4 18 4H16V3C16 2.45 15.55 2 15 2H9ZM9 3H15V5H9V3ZM8 8H16V10H8V8ZM8 12H16V14H8V12ZM8 16H13V18H8V16Z"
                        fill="currentColor"
                    />
                </svg>
            </button>
        </transition>
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

/* Nuevas clases para los diagnósticos premium */
.tooth-corona {
    fill: none;
    stroke: #4361ee; /* changed to clinical blue */
    stroke-width: 2.5;
    stroke-dasharray: 4 2;
}

.tooth-ausente {
    opacity: 0.25;
}

.tooth-ausente-line {
    fill: none;
    stroke: #64748b; /* slate 500 */
    stroke-width: 4.5;
    stroke-linecap: round;
    vector-effect: non-scaling-stroke;
}

.tooth-fracture {
    fill: none;
    stroke: #ef4444; /* red 500 */
    stroke-width: 3.5;
    stroke-linecap: round;
    stroke-linejoin: round;
    vector-effect: non-scaling-stroke;
}

.tooth-note-indicator {
    stroke: #ffffff;
    stroke-width: 1;
    filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.4));
}

.dark .tooth-note-indicator {
    stroke: #0f172a;
}

/* Floating notes icon button */
.tooth-notes-btn {
    position: absolute;
    top: 0;
    right: -2px;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #e2e8f0;
    color: #475569;
    border: 1.5px solid #cbd5e1;
    cursor: pointer;
    z-index: 20;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
}

.tooth-notes-btn:hover {
    background: #10b981;
    color: #ffffff;
    border-color: #059669;
    transform: scale(1.15);
    box-shadow: 0 2px 6px rgba(16, 185, 129, 0.4);
}

.tooth-notes-btn.has-notes {
    background: #10b981;
    color: #ffffff;
    border-color: #059669;
}

.tooth-notes-btn.has-notes:hover {
    background: #059669;
    border-color: #047857;
}

.dark .tooth-notes-btn {
    background: #334155;
    color: #94a3b8;
    border-color: #475569;
}

.dark .tooth-notes-btn:hover {
    background: #10b981;
    color: #ffffff;
    border-color: #059669;
}

.dark .tooth-notes-btn.has-notes {
    background: #10b981;
    color: #ffffff;
    border-color: #059669;
}

/* Transition for the notes icon */
.note-icon-fade-enter-active,
.note-icon-fade-leave-active {
    transition: opacity 0.15s ease, transform 0.15s ease;
}

.note-icon-fade-enter-from,
.note-icon-fade-leave-to {
    opacity: 0;
    transform: scale(0.6);
}

.note-icon-fade-enter-to,
.note-icon-fade-leave-from {
    opacity: 1;
    transform: scale(1);
}

/* Mejora extrema en contraste y legibilidad para los textos en modo claro y oscuro */
text {
    paint-order: stroke;
    stroke: #ffffff;
    stroke-width: 2.5px;
    stroke-linejoin: round;
    font-weight: 800;
}

.dark text {
    stroke: #0f172a;
}
</style>
