<script setup>
/**
 * Interruptor del Modo Super Editor en la cabecera.
 *
 * Solo se pinta si el servidor dice que el usuario puede usarlo (rol admin).
 * Entrar no pide contraseña; la contraseña aparece al salir, y solo si hay un
 * borrador con cambios que aplicar.
 */
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useSuperEditorStore } from 'Modules/Security/Resources/assets/js/stores/superEditor';
import GearIcon from 'Modules/Security/Resources/assets/js/Components/SuperEditor/GearIcon.vue';

const store = useSuperEditorStore();

const open = ref(false);
const now = ref(Date.now());

let ticker = null;
let expiredNotified = false;

const remaining = computed(() => {
    if (!store.active || !store.expiresAt) {
        return 0;
    }

    return Math.max(0, Math.floor((new Date(store.expiresAt).getTime() - now.value) / 1000));
});

const countdown = computed(() => {
    const total = remaining.value;

    if (total <= 0) {
        return 'expirada';
    }

    const minutes = String(Math.floor(total / 60)).padStart(2, '0');
    const seconds = String(total % 60).padStart(2, '0');

    return `${minutes}:${seconds}`;
});

const exit = (mode) => {
    open.value = false;
    store.requestExit(mode);
};

onMounted(() => {
    ticker = window.setInterval(() => {
        now.value = Date.now();

        // Al vencer, el servidor cierra la sesión (descartando el borrador y
        // dejándolo auditado) y el modo desaparece: basta con recargar. Se usa
        // el reload del store para que el remount (y por tanto la limpieza de
        // los engranes) sea el mismo que al entrar y al salir.
        if (store.active && remaining.value <= 0 && !expiredNotified) {
            expiredNotified = true;
            store.reloadPage();
        }
    }, 1000);
});

onBeforeUnmount(() => {
    window.clearInterval(ticker);
});
</script>

<template>
    <div v-if="store.canUse" class="relative">
        <!-- Modo apagado -->
        <button
            v-if="!store.active"
            type="button"
            class="flex items-center gap-2 rounded-full border border-dashed border-slate-300 bg-white px-4 py-1.5 text-xs font-semibold text-slate-500 transition hover:border-blue-400 hover:text-blue-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:text-blue-300"
            title="Activar el Modo Super Editor (solo el rol admin)"
            @click="store.enter()"
        >
            <span class="flex h-2 w-2 rounded-full bg-slate-400"></span>
            <GearIcon :size="12" />
            Modo editor
        </button>

        <!-- Modo activado (como en la referencia: pill azul con el estado) -->
        <template v-else>
            <button
                type="button"
                class="flex items-center gap-2 rounded-full bg-blue-600 px-4 py-1.5 text-xs font-bold uppercase tracking-wide text-white shadow-sm shadow-blue-300/60 transition hover:bg-blue-700 dark:shadow-blue-950/40"
                title="Modo editor activado. Click para ver las opciones de salida."
                @click="open = !open"
            >
                <span class="flex h-2.5 w-2.5 rounded-full bg-white/90"></span>
                <GearIcon :size="13" />
                Modo editor: activado
                <span class="rounded-full bg-white/20 px-2 py-0.5 text-[10px] font-semibold normal-case">
                    {{ store.dirty }} sin aplicar
                </span>
                <span class="rounded-full bg-white/20 px-2 py-0.5 text-[10px] font-semibold normal-case" title="Tiempo restante de la sesión de edición">
                    {{ countdown }}
                </span>
            </button>

            <div
                v-if="open"
                class="absolute right-0 z-[96] mt-2 w-72 rounded-xl border border-slate-200 bg-white p-3 text-left shadow-xl dark:border-slate-700 dark:bg-slate-800"
            >
                <p class="text-xs font-semibold text-slate-700 dark:text-slate-200">
                    Sesión de edición abierta
                </p>
                <p class="mt-1 text-[11px] leading-relaxed text-slate-500 dark:text-slate-400">
                    Tienes <strong>{{ store.dirty }}</strong> cambio(s) en borrador. Al salir se aplican todos
                    juntos y quedan registrados en el historial.
                </p>

                <button
                    type="button"
                    class="mt-3 w-full rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700"
                    @click="exit('apply')"
                >
                    Salir y aplicar cambios
                </button>
                <button
                    type="button"
                    class="mt-1.5 w-full rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600"
                    @click="exit('discard')"
                >
                    Salir y descartar
                </button>
                <Link
                    :href="route('super_editor_log')"
                    class="mt-1.5 block w-full rounded-lg px-3 py-2 text-center text-xs font-semibold text-blue-600 transition hover:bg-blue-50 dark:text-blue-300 dark:hover:bg-slate-700"
                    @click="open = false"
                >
                    Ver historial de cambios
                </Link>
            </div>
        </template>
    </div>
</template>
