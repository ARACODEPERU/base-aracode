<script setup>
import { onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
    loading: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    // 'left' | 'right' para alinear el desplegable
    align: {
        type: String,
        default: 'left',
    },
    label: {
        type: String,
        default: 'IA',
    },
    title: {
        type: String,
        default: 'Corregir con inteligencia artificial',
    },
});

const emit = defineEmits(['select']);

const MENU_WIDTH = 272;
// El desplegable se dibuja con position: fixed fuera de la tabla, porque el
// contenedor de la tabla tiene overflow y lo recortaba.
const CLOSE_DELAY = 250;

const open = ref(false);
const menuStyle = ref({});
const triggerRef = ref(null);

let closeTimer = null;

const canOpen = () => !props.loading && !props.disabled;

const clearCloseTimer = () => {
    if (closeTimer) {
        clearTimeout(closeTimer);
        closeTimer = null;
    }
};

const positionMenu = () => {
    const trigger = triggerRef.value;

    if (!trigger) {
        return;
    }

    const rect = trigger.getBoundingClientRect();
    const margin = 8;
    let left = props.align === 'right' ? rect.right - MENU_WIDTH : rect.left;
    left = Math.min(Math.max(margin, left), Math.max(margin, window.innerWidth - MENU_WIDTH - margin));

    menuStyle.value = {
        top: `${rect.bottom + 4}px`,
        left: `${left}px`,
        width: `${MENU_WIDTH}px`,
    };
};

const openMenu = () => {
    if (!canOpen()) {
        return;
    }

    clearCloseTimer();
    positionMenu();
    open.value = true;
};

const closeMenu = () => {
    clearCloseTimer();
    open.value = false;
};

// Cierre con retardo: da tiempo a pasar del boton a las opciones sin que se
// cierre por el pequeño espacio entre ambos.
const scheduleClose = () => {
    clearCloseTimer();
    closeTimer = setTimeout(() => {
        open.value = false;
        closeTimer = null;
    }, CLOSE_DELAY);
};

const toggleMenu = () => {
    if (open.value) {
        closeMenu();

        return;
    }

    openMenu();
};

const choose = (mode) => {
    if (!canOpen()) {
        return;
    }

    clearCloseTimer();
    open.value = false;
    emit('select', mode);
};

const onDocumentPointerDown = (event) => {
    const trigger = triggerRef.value;

    if (trigger && trigger.contains(event.target)) {
        return;
    }

    if (event.target.closest && event.target.closest('[data-ia-correction-menu]')) {
        return;
    }

    closeMenu();
};

const onKeydown = (event) => {
    if (event.key === 'Escape') {
        closeMenu();
    }
};

const bindGlobalListeners = () => {
    document.addEventListener('pointerdown', onDocumentPointerDown, true);
    document.addEventListener('keydown', onKeydown);
    window.addEventListener('resize', closeMenu);
    window.addEventListener('scroll', closeMenu, true);
};

const unbindGlobalListeners = () => {
    document.removeEventListener('pointerdown', onDocumentPointerDown, true);
    document.removeEventListener('keydown', onKeydown);
    window.removeEventListener('resize', closeMenu);
    window.removeEventListener('scroll', closeMenu, true);
};

watch(open, (value) => {
    if (value) {
        bindGlobalListeners();
    } else {
        unbindGlobalListeners();
    }
});

// Si empieza a cargar mientras el menu esta abierto, se cierra.
watch(() => props.loading || props.disabled, (blocked) => {
    if (blocked) {
        closeMenu();
    }
});

onBeforeUnmount(() => {
    clearCloseTimer();
    unbindGlobalListeners();
});
</script>

<template>
    <div class="relative inline-block">
        <button ref="triggerRef"
                type="button"
                @mouseenter="openMenu"
                @mouseleave="scheduleClose"
                @focus="openMenu"
                @click="toggleMenu"
                :disabled="disabled || loading"
                :title="title"
                :aria-expanded="open"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-full text-xs font-bold border border-violet-300 text-violet-700 bg-violet-50 hover:bg-violet-100 focus:ring-4 focus:ring-violet-200 focus:outline-none disabled:opacity-60 dark:bg-violet-900/30 dark:text-violet-200 dark:border-violet-700">
            <svg v-if="loading"
                 class="animate-spin h-4 w-4"
                 xmlns="http://www.w3.org/2000/svg"
                 fill="none"
                 viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor"
                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <i v-else class="ri-sparkling-2-line text-base"></i>
            {{ label }}
            <i v-if="!loading" class="ri-arrow-down-s-line text-base"></i>
        </button>

        <Teleport to="body">
            <div v-if="open && !loading && !disabled"
                 data-ia-correction-menu
                 :style="menuStyle"
                 class="fixed z-[9999] rounded-lg border border-gray-200 bg-white shadow-xl overflow-hidden dark:bg-gray-800 dark:border-gray-600"
                 @mouseenter="clearCloseTimer"
                 @mouseleave="scheduleClose">
                <button type="button"
                        @click="choose('writing')"
                        class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-violet-50 dark:text-gray-200 dark:hover:bg-gray-700 flex items-center gap-2">
                    <i class="ri-sparkling-2-line text-violet-600 dark:text-violet-300"></i>
                    Corregir redacción y ortografía
                </button>
                <button type="button"
                        @click="choose('spelling')"
                        class="w-full text-left px-4 py-2.5 text-sm text-gray-700 border-t border-gray-100 hover:bg-violet-50 dark:text-gray-200 dark:border-gray-700 dark:hover:bg-gray-700 flex items-center gap-2">
                    <i class="ri-text-check text-violet-600 dark:text-violet-300"></i>
                    Corregir solo ortografía
                </button>
            </div>
        </Teleport>
    </div>
</template>
