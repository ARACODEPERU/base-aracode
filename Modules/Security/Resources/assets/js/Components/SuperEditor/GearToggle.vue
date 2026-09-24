<script setup>
/**
 * El toggle del Modo Super Editor sobre un elemento concreto: un interruptor
 * con el engrane dentro, más claro de ver que un engrane suelto.
 *
 * El estado del permiso se lee en el color y en el lado del knob:
 *
 *   - azul   (knob a la derecha) tu rol ve el elemento.
 *   - naranja (knob a la izquierda) tu rol no lo ve; se muestra solo para poder
 *     configurarlo.
 *   - ámbar  tiene cambios en borrador sin aplicar.
 *
 * Es solo la parte visible: quién decide si un click es suyo vive en Gear.vue,
 * que lo coloca con coordenadas fijas. El toggle NO captura el puntero (ver
 * super-editor.css): su caja nunca le roba el click al elemento que decora.
 */
import { computed } from 'vue';
import GearIcon from './GearIcon.vue';

const props = defineProps({
    visible: { type: Boolean, default: false },
    top: { type: Number, default: 0 },
    left: { type: Number, default: 0 },
    zIndex: { type: Number, default: 90 },
    size: { type: String, default: 'md' },
    granted: { type: Boolean, default: true },
    staged: { type: Boolean, default: false },
    title: { type: String, default: 'Configurar permisos de acceso' },
});

const state = computed(() => {
    if (props.staged) {
        return 'staged';
    }

    return props.granted ? 'granted' : 'locked';
});

const compact = computed(() => props.size === 'sm');
</script>

<template>
    <button
        v-if="visible"
        type="button"
        data-super-gear="1"
        class="super-editor-gear super-editor-toggle"
        :class="[
            `super-editor-toggle--${state}`,
            compact ? 'super-editor-toggle--sm' : 'super-editor-toggle--md',
        ]"
        :style="{ top: `${top}px`, left: `${left}px`, zIndex }"
        :title="title"
    >
        <span
            class="super-editor-toggle__knob"
            :class="state === 'locked' ? 'super-editor-toggle__knob--start' : 'super-editor-toggle__knob--end'"
        >
            <GearIcon :size="compact ? 9 : 11" />
        </span>
    </button>
</template>

<style scoped>
.super-editor-toggle {
    border-width: 1px;
    border-style: solid;
    border-radius: 9999px;
    padding: 0;
    transition: background-color 140ms ease, border-color 140ms ease, box-shadow 140ms ease;
}

.super-editor-toggle--md {
    width: 38px;
    height: 20px;
}

.super-editor-toggle--sm {
    width: 30px;
    height: 16px;
}

/* Tu rol ve el elemento. */
.super-editor-toggle.super-editor-toggle--granted {
    background-color: #2563eb;
    border-color: #1d4ed8;
    color: #ffffff;
    box-shadow: 0 2px 10px rgba(37, 99, 235, 0.45);
}

/* Tu rol no lo ve: se muestra para poder configurarlo. */
.super-editor-toggle.super-editor-toggle--locked {
    background-color: #f97316;
    border-color: #ea580c;
    color: #ffffff;
    box-shadow: 0 2px 10px rgba(249, 115, 22, 0.45);
}

/* Tiene cambios en borrador (mismos colores que el panel). */
.super-editor-toggle.super-editor-toggle--staged {
    background-color: #fbbf24;
    border-color: #f59e0b;
    color: #78350f;
    box-shadow: 0 2px 10px rgba(245, 158, 11, 0.5);
}

.super-editor-toggle__knob {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    background-color: #ffffff;
    color: #1e293b;
    transition: left 140ms ease, right 140ms ease;
}

.super-editor-toggle--md .super-editor-toggle__knob {
    width: 16px;
    height: 16px;
}

.super-editor-toggle--sm .super-editor-toggle__knob {
    width: 12px;
    height: 12px;
}

.super-editor-toggle__knob--end {
    right: 2px;
}

.super-editor-toggle__knob--start {
    left: 2px;
}

.dark .super-editor-toggle__knob {
    background-color: #e2e8f0;
    color: #0f172a;
}
</style>
